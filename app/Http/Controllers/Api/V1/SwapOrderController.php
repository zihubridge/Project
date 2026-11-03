<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SwapOrder;
use App\Services\Swap\AmountNormalizer;
use App\Services\Swap\SwapExecutionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Throwable;

class SwapOrderController extends Controller
{
    public function __construct(
        private readonly SwapExecutionService $executionService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'uuids' => ['nullable', 'array', 'max:50'],
            'uuids.*' => ['string', 'uuid'],
            'wallet_address' => ['nullable', 'string', 'max:191'],
        ]);

        $query = SwapOrder::with([
            'provider',
            'sourceNetwork',
            'destinationNetwork',
            'sourceAsset',
            'destinationAsset',
        ])->orderByDesc('created_at');

        $hasFilter = false;
        if (!empty($validated['uuids'])) {
            $query->whereIn('swap_uuid', $validated['uuids']);
            $hasFilter = true;
        }

        if (!empty($validated['wallet_address'])) {
            $addr = trim($validated['wallet_address']);
            $query->where(function ($q) use ($addr) {
                $q->where('connected_wallet_address', $addr)
                    ->orWhere('recipient_address', $addr)
                    ->orWhere('refund_address', $addr);
            });
            $hasFilter = true;
        }

        $orders = $hasFilter ? $query->limit(50)->get() : $query->limit(15)->get();

        return response()->json([
            'status' => 1,
            'count' => $orders->count(),
            'data' => $orders->map(fn (SwapOrder $order) => $this->formatOrderPayload($order))->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'quote_uuid' => ['required', 'uuid'],
            'recipient_address' => ['required', 'string', 'max:191'],
            'recipient_memo' => ['nullable', 'string', 'max:128'],
            'refund_address' => ['required', 'string', 'max:191'],
            'refund_memo' => ['nullable', 'string', 'max:128'],
            'connected_wallet_address' => ['nullable', 'string', 'max:191'],
        ]);

        try {
            $order = $this->executionService->initiateFromQuote(
                quoteUuid: $validated['quote_uuid'],
                recipientAddress: $validated['recipient_address'],
                refundAddress: $validated['refund_address'],
                recipientMemo: $validated['recipient_memo'] ?? null,
                refundMemo: $validated['refund_memo'] ?? null,
                connectedWalletAddress: $validated['connected_wallet_address'] ?? null,
            );

            return response()->json([
                'status' => 1,
                'message' => 'Swap initiated successfully.',
                'data' => $this->formatOrderPayload($order),
            ], 201);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'status' => 0,
                'message' => $e->getMessage(),
            ], 422);
        } catch (Throwable $e) {
            return response()->json([
                'status' => 0,
                'message' => $e->getMessage() ?: 'Unable to initiate swap order.',
            ], 400);
        }
    }

    public function show(Request $request, string $uuid): JsonResponse
    {
        $order = SwapOrder::with([
            'provider',
            'sourceNetwork',
            'destinationNetwork',
            'sourceAsset',
            'destinationAsset',
            'steps',
            'histories',
        ])->where('swap_uuid', $uuid)->firstOrFail();

        if (
            !$order->isTerminal() &&
            ($request->boolean('refresh', true)) &&
            (!$order->last_polled_at || $order->last_polled_at->diffInSeconds(now()) >= 5)
        ) {
            try {
                $order = $this->executionService->syncOrderStatus($order, 'API');
                $order->load([
                    'provider',
                    'sourceNetwork',
                    'destinationNetwork',
                    'sourceAsset',
                    'destinationAsset',
                    'steps',
                    'histories',
                ]);
            } catch (Throwable) {
                // Return cached DB state gracefully if provider status check times out
            }
        }

        return response()->json([
            'status' => 1,
            'data' => $this->formatOrderPayload($order),
        ]);
    }

    public function submitDepositTx(Request $request, string $uuid): JsonResponse
    {
        $validated = $request->validate([
            'tx_hash' => ['required', 'string', 'max:191'],
        ]);

        $order = SwapOrder::where('swap_uuid', $uuid)->firstOrFail();

        try {
            $updated = $this->executionService->recordDepositTransaction($order, $validated['tx_hash']);

            return response()->json([
                'status' => 1,
                'message' => 'Deposit transaction recorded.',
                'data' => $this->formatOrderPayload($updated),
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'status' => 0,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function formatOrderPayload(SwapOrder $order): array
    {
        $statusEnum = $order->statusEnum();

        return [
            'swap_uuid' => $order->swap_uuid,
            'status' => $order->status,
            'status_label' => $statusEnum->label(),
            'active_step_number' => $statusEnum->activeStepNumber(),
            'is_terminal' => $statusEnum->isTerminal(),
            'provider' => [
                'slug' => $order->provider?->slug,
                'display_name' => $order->provider?->display_name,
            ],
            'source' => [
                'network_slug' => $order->sourceNetwork?->slug,
                'network_name' => $order->sourceNetwork?->display_name,
                'chain_family' => $order->sourceNetwork?->chain_family,
                'evm_chain_id' => $order->sourceNetwork?->evm_chain_id,
                'asset_id' => $order->sourceAsset?->id,
                'symbol' => $order->sourceAsset?->symbol,
                'decimals' => $order->sourceAsset?->decimals,
                'is_native' => (bool) $order->sourceAsset?->is_native,
                'contract_or_issuer' => $order->sourceAsset?->contract_or_issuer,
                'amount_in' => AmountNormalizer::trimTrailingZeros((string) $order->amount_in),
                'amount_in_base_units' => $order->amount_in_base_units,
                'refund_address' => $order->refund_address,
                'refund_memo' => $order->refund_memo,
                'tx_hash' => $order->source_tx_hash,
                'explorer_url' => $order->source_explorer_url,
            ],
            'destination' => [
                'network_slug' => $order->destinationNetwork?->slug,
                'network_name' => $order->destinationNetwork?->display_name,
                'chain_family' => $order->destinationNetwork?->chain_family,
                'asset_id' => $order->destinationAsset?->id,
                'symbol' => $order->destinationAsset?->symbol,
                'decimals' => $order->destinationAsset?->decimals,
                'expected_amount_out' => AmountNormalizer::trimTrailingZeros((string) $order->expected_amount_out),
                'min_amount_out' => AmountNormalizer::trimTrailingZeros((string) $order->min_amount_out),
                'actual_amount_out' => $order->actual_amount_out !== null
                    ? AmountNormalizer::trimTrailingZeros((string) $order->actual_amount_out)
                    : null,
                'recipient_address' => $order->recipient_address,
                'recipient_memo' => $order->recipient_memo,
                'tx_hash' => $order->destination_tx_hash,
                'explorer_url' => $order->destination_explorer_url,
            ],
            'deposit_instructions' => [
                'deposit_address' => $order->deposit_address,
                'deposit_memo' => $order->deposit_memo,
                'memo_required' => $order->deposit_memo !== null && $order->deposit_memo !== '',
                'deposit_address_explorer_url' => $order->sourceNetwork?->formatAddressExplorerUrl($order->deposit_address),
                'expires_at' => $order->deposit_expires_at?->toIso8601String(),
                'expires_timestamp_ms' => $order->deposit_expires_at ? $order->deposit_expires_at->getTimestamp() * 1000 : null,
            ],
            'refund_info' => [
                'refunded_amount' => $order->refunded_amount !== null
                    ? AmountNormalizer::trimTrailingZeros((string) $order->refunded_amount)
                    : null,
                'refund_tx_hash' => $order->refund_tx_hash,
            ],
            'failure_reason' => $order->failure_reason,
            'steps' => $order->relationLoaded('steps')
                ? $order->steps->map(fn ($s) => [
                    'step_number' => $s->step_number,
                    'step_key' => $s->step_key,
                    'label' => $s->label,
                    'status' => $s->status,
                    'tx_hash' => $s->tx_hash,
                    'explorer_url' => $s->explorer_url,
                    'completed_at' => $s->completed_at?->toIso8601String(),
                ])->values()
                : [],
            'created_at' => $order->created_at?->toIso8601String(),
            'completed_at' => $order->completed_at?->toIso8601String(),
            'tracking_url' => route('swap.show', ['uuid' => $order->swap_uuid]),
        ];
    }
}

