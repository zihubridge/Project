<?php

namespace App\Services\Swap;

use App\Enums\SwapStatus;
use App\Jobs\PollSwapStatusJob;
use App\Jobs\SubmitSwapDepositTxJob;
use App\Models\ProviderSupportedAsset;
use App\Models\SwapExecutionStep;
use App\Models\SwapOrder;
use App\Models\SwapQuote;
use App\Models\SwapStatusHistory;
use App\Services\Providers\DTOs\NormalizedSwapStatusData;
use App\Services\Providers\DTOs\SwapInitiationData;
use App\Services\Providers\SwapProviderRegistry;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class SwapExecutionService
{
    public function __construct(
        private readonly SwapProviderRegistry $registry,
        private readonly ChainAddressValidator $addressValidator,
        private readonly \App\Services\Providers\LocalChainAdapterRegistry $adapterRegistry,
    ) {}

    /**
     * Initiate a live cross-chain swap from a validated quote.
     */
    public function initiateFromQuote(
        string $quoteUuid,
        string $recipientAddress,
        string $refundAddress,
        ?string $recipientMemo = null,
        ?string $refundMemo = null,
        ?string $connectedWalletAddress = null,
    ): SwapOrder {
        $quote = SwapQuote::with(['provider', 'sourceAsset.network', 'destinationAsset.network', 'order'])
            ->where('quote_uuid', $quoteUuid)
            ->first();

        if (!$quote) {
            throw new InvalidArgumentException('Quote not found.');
        }

        if ($quote->order) {
            return $quote->order->load([
                'provider',
                'sourceNetwork',
                'destinationNetwork',
                'sourceAsset',
                'destinationAsset',
                'steps',
                'histories',
            ]);
        }

        if ($quote->isExpired()) {
            throw new InvalidArgumentException('This quote has expired. Please request a fresh quote.');
        }

        $sourceNetwork = $quote->sourceAsset->network;
        $destNetwork = $quote->destinationAsset->network;

        $recipientValidation = $this->addressValidator->validate($destNetwork, $recipientAddress, $recipientMemo);
        if (!$recipientValidation['valid']) {
            throw new InvalidArgumentException('Recipient address invalid: ' . $recipientValidation['message']);
        }

        $refundValidation = $this->addressValidator->validate($sourceNetwork, $refundAddress, $refundMemo);
        if (!$refundValidation['valid']) {
            throw new InvalidArgumentException('Refund address invalid: ' . $refundValidation['message']);
        }

        // Enforce destination chain account prerequisites (e.g. Stellar trustline)
        if ($destNetwork->chain_family === 'stellar' || strtolower($destNetwork->slug) === 'stellar') {
            $stellarAdapter = $this->adapterRegistry->has('stellar_sdex')
                ? $this->adapterRegistry->get('stellar_sdex')
                : app(\App\Services\Providers\Adapters\StellarSdexAdapter::class);
            $check = $stellarAdapter->checkAccountRequirements($destNetwork, $recipientAddress, $quote->destinationAsset);
            if (isset($check['prerequisites_met']) && !$check['prerequisites_met']) {
                throw new RuntimeException(
                    $check['message'] ?: "Recipient wallet lacks an established trustline for {$quote->destinationAsset->symbol}."
                );
            }
        }

        $providerService = $this->registry->get($quote->provider->slug);

        $isMultiHop = (bool) $quote->is_multi_hop;
        $effectiveDestAsset = $isMultiHop ? $quote->intermediateAsset : $quote->destinationAsset;
        $effectiveDestNetwork = $effectiveDestAsset->network;

        $sourceMapping = ProviderSupportedAsset::where('swap_provider_id', $quote->swap_provider_id)
            ->where('crypto_asset_id', $quote->source_asset_id)
            ->where('is_active', true)
            ->first();

        $destMapping = ProviderSupportedAsset::where('swap_provider_id', $quote->swap_provider_id)
            ->where('crypto_asset_id', $effectiveDestAsset->id)
            ->where('is_active', true)
            ->first();

        if (!$sourceMapping || !$destMapping) {
            throw new RuntimeException('Selected assets are no longer active on the execution provider.');
        }

        $initiationData = new SwapInitiationData(
            originProviderAssetId: $sourceMapping->provider_asset_id,
            destinationProviderAssetId: $destMapping->provider_asset_id,
            originNetworkSlug: $sourceNetwork->slug,
            destinationNetworkSlug: $effectiveDestNetwork->slug,
            amountInBaseUnits: $quote->amount_in_base_units,
            originDecimals: $quote->sourceAsset->decimals,
            destinationDecimals: $effectiveDestAsset->decimals,
            slippageBps: $quote->slippage_bps,
            recipientAddress: trim($recipientAddress),
            recipientMemo: $recipientMemo !== null && trim($recipientMemo) !== '' ? trim($recipientMemo) : null,
            refundAddress: trim($refundAddress),
            refundMemo: $refundMemo !== null && trim($refundMemo) !== '' ? trim($refundMemo) : null,
            swapType: $quote->swap_type,
        );

        try {
            $intent = $providerService->initiateSwap($initiationData);
        } catch (RuntimeException $e) {
            throw new RuntimeException(
                AmountNormalizer::humanizeProviderError(
                    rawMessage: $e->getMessage(),
                    originSymbol: $quote->sourceAsset->symbol,
                    originDecimals: $quote->sourceAsset->decimals,
                    originNetworkName: $sourceNetwork->display_name,
                    destSymbol: $quote->destinationAsset->symbol,
                    destNetworkName: $destNetwork->display_name,
                    recipientAddress: $recipientAddress,
                ),
                $e->getCode(),
                $e
            );
        }

        $order = DB::transaction(function () use (
            $quote,
            $sourceNetwork,
            $destNetwork,
            $isMultiHop,
            $effectiveDestAsset,
            $intent,
            $recipientAddress,
            $recipientMemo,
            $refundAddress,
            $refundMemo,
            $connectedWalletAddress
        ) {
            $lockedQuote = SwapQuote::whereKey($quote->id)->lockForUpdate()->first();
            if ($lockedQuote && $lockedQuote->order) {
                return $lockedQuote->order;
            }

            $swapOrder = SwapOrder::create([
                'swap_uuid' => (string) Str::uuid(),
                'swap_quote_id' => $quote->id,
                'swap_provider_id' => $quote->swap_provider_id,
                'source_network_id' => $sourceNetwork->id,
                'destination_network_id' => $destNetwork->id,
                'is_multi_hop' => $isMultiHop,
                'intermediate_asset_id' => $quote->intermediate_asset_id,
                'intermediate_amount' => $quote->intermediate_amount,
                'second_leg_adapter_key' => $quote->second_leg_adapter_key,
                'second_leg_status' => $isMultiHop ? 'PENDING_FIRST_LEG' : 'NONE',
                'source_asset_id' => $quote->source_asset_id,
                'destination_asset_id' => $quote->destination_asset_id,
                'amount_in' => $intent->amountInHuman,
                'amount_in_base_units' => $intent->amountInBaseUnits,
                'expected_amount_out' => $isMultiHop ? $quote->expected_amount_out : $intent->expectedAmountOutHuman,
                'min_amount_out' => $isMultiHop ? $quote->min_amount_out : $intent->minAmountOutHuman,
                'slippage_bps' => $quote->slippage_bps,
                'connected_wallet_address' => $connectedWalletAddress ? trim($connectedWalletAddress) : null,
                'recipient_address' => trim($recipientAddress),
                'recipient_memo' => $recipientMemo !== null && trim($recipientMemo) !== '' ? trim($recipientMemo) : null,
                'refund_address' => trim($refundAddress),
                'refund_memo' => $refundMemo !== null && trim($refundMemo) !== '' ? trim($refundMemo) : null,
                'deposit_address' => $intent->depositAddress,
                'deposit_memo' => $intent->depositMemo,
                'status' => SwapStatus::DEPOSIT_PENDING->value,
                'provider_status' => 'PENDING_DEPOSIT',
                'deposit_expires_at' => $intent->expiresAt,
                'provider_context_json' => $intent->providerContext,
            ]);

            if ($isMultiHop) {
                $steps = [
                    [
                        'step_number' => 1,
                        'step_key' => 'DEPOSIT',
                        'label' => "Deposit {$quote->sourceAsset->symbol} on {$sourceNetwork->display_name}",
                        'status' => 'IN_PROGRESS',
                        'network_slug' => $sourceNetwork->slug,
                    ],
                    [
                        'step_number' => 2,
                        'step_key' => 'BRIDGE_TO_INTERMEDIATE',
                        'label' => "Bridge to {$effectiveDestAsset->symbol} via {$quote->provider->display_name}",
                        'status' => 'PENDING',
                        'network_slug' => $destNetwork->slug,
                    ],
                    [
                        'step_number' => 3,
                        'step_key' => 'DEX_EXECUTION',
                        'label' => "Local DEX Swap: {$effectiveDestAsset->symbol} to {$quote->destinationAsset->symbol}",
                        'status' => 'PENDING',
                        'network_slug' => $destNetwork->slug,
                    ],
                ];
            } else {
                $steps = [
                    [
                        'step_number' => 1,
                        'step_key' => 'DEPOSIT',
                        'label' => "Deposit {$quote->sourceAsset->symbol} on {$sourceNetwork->display_name}",
                        'status' => 'IN_PROGRESS',
                        'network_slug' => $sourceNetwork->slug,
                    ],
                    [
                        'step_number' => 2,
                        'step_key' => 'CONFIRMATION',
                        'label' => "Confirm Deposit on {$sourceNetwork->display_name}",
                        'status' => 'PENDING',
                        'network_slug' => $sourceNetwork->slug,
                    ],
                    [
                        'step_number' => 3,
                        'step_key' => 'EXECUTION',
                        'label' => "Cross-Chain Execution via {$quote->provider->display_name}",
                        'status' => 'PENDING',
                        'network_slug' => null,
                    ],
                    [
                        'step_number' => 4,
                        'step_key' => 'SETTLEMENT',
                        'label' => "Deliver {$quote->destinationAsset->symbol} on {$destNetwork->display_name}",
                        'status' => 'PENDING',
                        'network_slug' => $destNetwork->slug,
                    ],
                ];
            }

            foreach ($steps as $step) {
                SwapExecutionStep::create(array_merge($step, ['swap_order_id' => $swapOrder->id]));
            }

            SwapStatusHistory::create([
                'swap_order_id' => $swapOrder->id,
                'previous_status' => null,
                'new_status' => SwapStatus::DEPOSIT_PENDING->value,
                'provider_status' => 'PENDING_DEPOSIT',
                'triggered_by' => 'USER',
                'idempotency_key' => "init:{$swapOrder->id}",
                'message' => 'Swap order initiated and deposit address locked.',
            ]);

            return $swapOrder;
        });

        PollSwapStatusJob::dispatch($order->id);

        return $order->load([
            'provider',
            'sourceNetwork',
            'destinationNetwork',
            'sourceAsset',
            'destinationAsset',
            'steps',
            'histories',
        ]);
    }

    /**
     * Record a user's origin-chain deposit transaction hash and notify the provider.
     */
    public function recordDepositTransaction(SwapOrder $order, string $txHash): SwapOrder
    {
        $cleanHash = trim($txHash);
        if (!preg_match('/^[a-zA-Z0-9_\-:]{16,140}$/', $cleanHash)) {
            throw new InvalidArgumentException('Invalid transaction hash format.');
        }

        $updatedOrder = DB::transaction(function () use ($order, $cleanHash) {
            /** @var SwapOrder $locked */
            $locked = SwapOrder::with(['sourceNetwork', 'steps'])->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($locked->isTerminal()) {
                return $locked;
            }

            $previousStatus = $locked->status;
            $explorerUrl = $locked->sourceNetwork?->formatTxExplorerUrl($cleanHash);

            $locked->source_tx_hash = $cleanHash;
            $locked->source_explorer_url = $explorerUrl ?: $locked->source_explorer_url;
            $locked->deposit_submitted_at = $locked->deposit_submitted_at ?: Carbon::now();

            if ($locked->status === SwapStatus::DEPOSIT_PENDING->value) {
                $locked->status = SwapStatus::DEPOSIT_SUBMITTED->value;
            }
            $locked->save();

            $this->syncExecutionSteps($locked);

            $idempotencyKey = "tx_submit:{$locked->id}:{$cleanHash}";
            SwapStatusHistory::firstOrCreate(
                ['idempotency_key' => $idempotencyKey],
                [
                    'swap_order_id' => $locked->id,
                    'previous_status' => $previousStatus,
                    'new_status' => $locked->status,
                    'provider_status' => $locked->provider_status,
                    'triggered_by' => 'USER',
                    'message' => "Deposit transaction submitted: {$cleanHash}",
                ]
            );

            return $locked;
        });

        SubmitSwapDepositTxJob::dispatch($updatedOrder->id, $cleanHash);
        PollSwapStatusJob::dispatch($updatedOrder->id);

        return $updatedOrder->fresh([
            'provider',
            'sourceNetwork',
            'destinationNetwork',
            'sourceAsset',
            'destinationAsset',
            'steps',
            'histories',
        ]);
    }

    /**
     * Synchronize the swap order's status with the active execution provider idempotently.
     */
    public function syncOrderStatus(SwapOrder $order, string $triggeredBy = 'SYSTEM_POLLER'): SwapOrder
    {
        if ($order->isTerminal()) {
            return $order;
        }

        $lock = Cache::lock("swap_order_sync:{$order->id}", 15);
        if (!$lock->get()) {
            return $order;
        }

        try {
            $order->loadMissing(['provider', 'sourceNetwork', 'destinationNetwork', 'sourceAsset', 'destinationAsset']);
            $providerService = $this->registry->get($order->provider->slug);
            $statusData = $providerService->fetchSwapStatus($order);

            return $this->applyProviderStatus($order, $statusData, $triggeredBy);
        } finally {
            $lock->release();
        }
    }

    public function applyProviderStatus(
        SwapOrder $order,
        NormalizedSwapStatusData $statusData,
        string $triggeredBy = 'SYSTEM_POLLER',
    ): SwapOrder {
        return DB::transaction(function () use ($order, $statusData, $triggeredBy) {
            /** @var SwapOrder $locked */
            $locked = SwapOrder::with(['sourceNetwork', 'destinationNetwork', 'steps'])
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->isTerminal() && $statusData->normalizedStatus !== SwapStatus::REFUNDED) {
                return $locked;
            }

            $previousStatus = $locked->status;
            $newStatus = $statusData->normalizedStatus->value;

            if ($statusData->sourceTxHash) {
                $locked->source_tx_hash = $statusData->sourceTxHash;
                $locked->source_explorer_url = $statusData->sourceExplorerUrl
                    ?: $locked->sourceNetwork?->formatTxExplorerUrl($statusData->sourceTxHash);
            }

            if ($statusData->destinationTxHash) {
                $locked->destination_tx_hash = $statusData->destinationTxHash;
                $locked->destination_explorer_url = $statusData->destinationExplorerUrl
                    ?: $locked->destinationNetwork?->formatTxExplorerUrl($statusData->destinationTxHash);
            }

            if ($statusData->refundTxHash) {
                $locked->refund_tx_hash = $statusData->refundTxHash;
            }

            if ($statusData->actualAmountOutHuman !== null) {
                $locked->actual_amount_out = $statusData->actualAmountOutHuman;
            }

            if ($statusData->refundedAmountHuman !== null) {
                $locked->refunded_amount = $statusData->refundedAmountHuman;
            }

            if ($statusData->failureReason !== null) {
                $locked->failure_reason = $statusData->failureReason;
            }

            if (in_array($statusData->normalizedStatus, [SwapStatus::DEPOSIT_DETECTED, SwapStatus::PROCESSING, SwapStatus::COMPLETED], true) && !$locked->deposit_detected_at) {
                $locked->deposit_detected_at = Carbon::now();
            }

            if ($statusData->normalizedStatus === SwapStatus::COMPLETED && !$locked->completed_at) {
                $locked->completed_at = Carbon::now();
            }

            $locked->status = $newStatus;
            $locked->provider_status = $statusData->providerStatus;
            $locked->last_polled_at = Carbon::now();
            $locked->save();

            $this->syncExecutionSteps($locked);

            if ($previousStatus !== $newStatus) {
                $idempotencyKey = "transition:{$locked->id}:{$previousStatus}:{$newStatus}:{$statusData->providerStatus}";
                SwapStatusHistory::firstOrCreate(
                    ['idempotency_key' => $idempotencyKey],
                    [
                        'swap_order_id' => $locked->id,
                        'previous_status' => $previousStatus,
                        'new_status' => $newStatus,
                        'provider_status' => $statusData->providerStatus,
                        'triggered_by' => $triggeredBy,
                        'message' => $statusData->failureReason ?: "Status transitioned to {$statusData->normalizedStatus->label()}.",
                        'metadata_json' => [
                            'source_tx_hash' => $locked->source_tx_hash,
                            'destination_tx_hash' => $locked->destination_tx_hash,
                        ],
                    ]
                );
            }

            return $locked;
        });
    }

    private function syncExecutionSteps(SwapOrder $order): void
    {
        $status = $order->statusEnum();
        $now = Carbon::now();

        $steps = SwapExecutionStep::where('swap_order_id', $order->id)->orderBy('step_number')->get();

        foreach ($steps as $step) {
            if ($step->step_number === 1) {
                $step->tx_hash = $order->source_tx_hash ?: $step->tx_hash;
                $step->explorer_url = $order->source_explorer_url ?: $step->explorer_url;

                if ($status === SwapStatus::DEPOSIT_PENDING) {
                    $step->status = 'IN_PROGRESS';
                } elseif ($status === SwapStatus::EXPIRED) {
                    $step->status = 'FAILED';
                } else {
                    $step->status = 'COMPLETED';
                    $step->completed_at = $step->completed_at ?: $now;
                }
            } elseif ($step->step_number === 2) {
                $step->tx_hash = $order->source_tx_hash ?: $step->tx_hash;
                $step->explorer_url = $order->source_explorer_url ?: $step->explorer_url;

                if ($status === SwapStatus::DEPOSIT_SUBMITTED) {
                    $step->status = 'IN_PROGRESS';
                } elseif (in_array($status, [SwapStatus::DEPOSIT_DETECTED, SwapStatus::PROCESSING, SwapStatus::COMPLETED, SwapStatus::REFUNDED], true)) {
                    $step->status = 'COMPLETED';
                    $step->completed_at = $step->completed_at ?: $now;
                } elseif (in_array($status, [SwapStatus::FAILED, SwapStatus::EXPIRED], true)) {
                    $step->status = $order->deposit_detected_at ? 'COMPLETED' : 'FAILED';
                } else {
                    $step->status = 'PENDING';
                }
            } elseif ($step->step_number === 3) {
                if (in_array($status, [SwapStatus::DEPOSIT_DETECTED, SwapStatus::PROCESSING], true)) {
                    $step->status = 'IN_PROGRESS';
                } elseif ($status === SwapStatus::COMPLETED) {
                    $step->status = 'COMPLETED';
                    $step->completed_at = $step->completed_at ?: $now;
                } elseif (in_array($status, [SwapStatus::REFUNDED, SwapStatus::FAILED], true)) {
                    $step->status = 'FAILED';
                } else {
                    $step->status = 'PENDING';
                }
            } elseif ($step->step_number === 4) {
                $step->tx_hash = $order->destination_tx_hash ?: $order->refund_tx_hash ?: $step->tx_hash;
                $step->explorer_url = $order->destination_explorer_url ?: $step->explorer_url;

                if ($status === SwapStatus::COMPLETED) {
                    $step->status = 'COMPLETED';
                    $step->completed_at = $step->completed_at ?: $now;
                } elseif ($status === SwapStatus::REFUNDED) {
                    $step->label = 'Refunded to Origin Address';
                    $step->status = 'COMPLETED';
                    $step->completed_at = $step->completed_at ?: $now;
                } elseif ($status === SwapStatus::FAILED) {
                    $step->status = 'FAILED';
                } else {
                    $step->status = 'PENDING';
                }
            }

            $step->save();
        }
    }

    /**
     * Prepare the unsigned second-leg transaction envelope for user wallet signature.
     */
    public function prepareSecondLegTransaction(SwapOrder $order): array
    {
        if (!$order->is_multi_hop || empty($order->second_leg_adapter_key)) {
            return [
                'success' => false,
                'error' => 'This swap order is not a multi-hop route.',
            ];
        }

        if (!$this->adapterRegistry->has($order->second_leg_adapter_key)) {
            return [
                'success' => false,
                'error' => "Adapter {$order->second_leg_adapter_key} is not registered.",
            ];
        }

        $adapter = $this->adapterRegistry->get($order->second_leg_adapter_key);
        return $adapter->buildUnsignedTransaction($order, $order->recipient_address);
    }

    /**
     * Submit user-signed transaction data for the second leg.
     */
    public function submitSecondLegTransaction(SwapOrder $order, string $signedTxData): array
    {
        if (!$order->is_multi_hop || empty($order->second_leg_adapter_key)) {
            return [
                'success' => false,
                'error' => 'This swap order is not a multi-hop route.',
            ];
        }

        $adapter = $this->adapterRegistry->get($order->second_leg_adapter_key);
        $result = $adapter->submitSignedTransaction($order, $signedTxData);

        if ($result['success']) {
            $order->update([
                'second_leg_status' => 'SUBMITTED',
                'second_leg_tx_hash' => $result['tx_hash'],
                'second_leg_explorer_url' => $result['explorer_url'],
            ]);

            // Complete Step 3 in execution steps
            SwapExecutionStep::where('swap_order_id', $order->id)
                ->where('step_key', 'DEX_EXECUTION')
                ->update([
                    'status' => 'COMPLETED',
                    'tx_hash' => $result['tx_hash'],
                    'explorer_url' => $result['explorer_url'],
                    'completed_at' => now(),
                ]);
        }

        return $result;
    }
}
