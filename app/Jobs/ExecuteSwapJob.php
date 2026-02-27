<?php

namespace App\Jobs;

use App\Models\InternalSwap;
use App\Models\Swap;
use App\Models\SwapEvent;
use App\Models\SwapExchange;
use App\Services\Stellar\StellarSwapService;
use App\Services\Swap\ChangeNowService;
use App\Services\Ripple\XrplSwapService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ExecuteSwapJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $swapId) {}

    public function handle(
        StellarSwapService $stellar,
        ChangeNowService $changeNow,
        XrplSwapService $xrpl
    ): void {

        $swap = Swap::with([
            'deposit',
            'fromToken',
            'toToken',
            'fromBlockchain',
            'toBlockchain'
        ])->find($this->swapId);

        if (!$swap) {
            return;
        }

        // Stop if final
        if (in_array($swap->swap_state_id, [9, 10, 11, 12])) {
            return;
        }

        switch ($swap->swap_state_id) {

            case 3: // deposit_confirmed
                $this->handleInternalSwap($swap, $stellar, $xrpl);
                break;

            case 5: // internal_swap_completed
                $this->handleProviderCreation($swap, $changeNow);
                break;

            case 6: // provider_processing
                $this->handleSendToProvider($swap, $stellar, $xrpl);
                break;

            default:
                return;
        }
    }

    private function handleInternalSwap(
        Swap $swap,
        StellarSwapService $stellar,
        XrplSwapService $xrpl
    ): void {

        $internalSwap = InternalSwap::where('swap_id', $swap->id)
        ->where('leg', 'source')
        ->first();

        if (!$internalSwap) return;
        if ($internalSwap->tx_hash) return;

        $swap->update(['swap_state_id' => 4]); // internal_swap_started

        SwapEvent::create([
            'swap_id' => $swap->id,
            'swap_event_type_id' => 6,
            'message' => 'Internal swap started'
        ]);

        $deposit = $swap->deposit;
        $blockchainId = $swap->fromBlockchain->id;

        if ($blockchainId === 1) {

            $result = $stellar->xlmTokenToXlm(
                $swap->fromToken->asset_code,
                $swap->fromToken->issuer_address,
                $deposit->received_token_amount,
                '0.0000001',
                null,
                $swap->id
            );
        } else if ($blockchainId === 2) {

            $result = $xrpl->xrpTokenToXrp(
                tokenAmount: $deposit->received_token_amount,
                tokenCurrency: $swap->fromToken->asset_code,
                tokenIssuer: $swap->fromToken->issuer_address,
                minXrpOut: '0.0000001'
            );
        } else {
            $message = "Unsupported blockchain for internal swap. Blockchain ID: {$blockchainId}";

            SwapEvent::create([
                'swap_id' => $swap->id,
                'swap_event_type_id' => 8, // internal swap failed
                'message' => $message
            ]);

            $swap->update([
                'swap_state_id' => 11 // failed
            ]);

            throw new \RuntimeException($message);
        }

        if (!($result['ok'] ?? false)) {

            $swap->update([
                'swap_state_id' => 11,
                'failure_reason' => $result['error'] ?? 'internal_swap_failed'
            ]);

            SwapEvent::create([
                'swap_id' => $swap->id,
                'swap_event_type_id' => 8,
                'message' => 'Internal swap failed',
                'meta' => json_encode($result)
            ]);

            return;
        }

        $swap->update([
            'swap_state_id' => 5
        ]);

        $internalSwap->update([
            'amount_out' => $result['amount_out'],
            'tx_hash' => $result['tx_hash'],
            'internal_swap_state_id' => 2,
            'meta' => json_encode($result)
        ]);

        SwapEvent::create([
            'swap_id' => $swap->id,
            'swap_event_type_id' => 7,
            'message' => 'Internal swap completed',
            'meta' => json_encode($result)
        ]);

        // Continue automatically
        self::dispatch($swap->id);
    }

    private function handleProviderCreation(
        Swap $swap,
        ChangeNowService $changeNow
    ): void {

        // prevent duplicate exchange rows
        if ($swap->exchange()->exists()) {
            $swap->update(['swap_state_id' => 6]);
            self::dispatch($swap->id);
            return;
        }

        $sourceInternalSwap = $swap->sourceInternalSwap;

        if (!$sourceInternalSwap || !$sourceInternalSwap->amount_out) {
            return;
        }

        $coinAmount = $sourceInternalSwap->amount_out;

        $swap->update(['swap_state_id' => 6]); // provider_processing

        try {
            $payoutTag = random_int(100000, 999999);
            $exchange = $changeNow->createExchange(
                fromCurrency: $swap->fromBlockchain->asset_code,
                toCurrency: $swap->toBlockchain->asset_code,
                destinationAddress: $swap->toBlockchain->asset_code === 'xrp'
                    ? config('services.xrpl.wallet')
                    : config('services.stellar.wallet'),
                extraId: $payoutTag,
                fromNetwork: $swap->fromBlockchain->asset_code,
                toNetwork: $swap->toBlockchain->asset_code,
                fromAmount: (string) $coinAmount
            );
        } catch (Throwable $e) {

            $swap->update([
                'swap_state_id' => 5, // revert so retry continues here
                'failure_reason' => $e->getMessage()
            ]);

            return;
        }

        SwapExchange::create([
            'swap_id' => $swap->id,
            'from_token_id' => $swap->from_token_id,
            'to_token_id' => $swap->to_token_id,
            'exchange_provider_id' => 1,
            'exchange_order_id' => $exchange['id'] ?? null,
            'payin_address' => $exchange['payinAddress'],
            'payin_memo' => $exchange['payinExtraId'] ?? null,
            'payout_address' => config('services.xrpl.wallet'),
            'payout_memo'    => $payoutTag,
            'from_amount' => $coinAmount,
            'expected_amount' => $exchange['toAmount'] ?? null,
        ]);

        SwapEvent::create([
            'swap_id' => $swap->id,
            'swap_event_type_id' => 9,
            'message' => 'Exchange order creating'
        ]);

        self::dispatch($swap->id);
    }

    private function handleSendToProvider(
        Swap $swap,
        StellarSwapService $stellar,
        XrplSwapService $xrpl
    ): void {

        $exchange = $swap->exchange;

        if (!$exchange) {
            Log::error("Swap {$swap->id} has no exchange.");
            return;
        }

        if ($exchange->exchange_tx_id) {
            return; // already sent
        }

        if ($swap->fromBlockchain->id === 1) {

            $tx = $stellar->sendXlmToExchange(
                $exchange->payin_address,
                $exchange->from_amount,
                $exchange->payin_memo
            );
        } else {

            $tx = $xrpl->sendXrpToExchange(
                $exchange->payin_address,
                $exchange->from_amount,
                $exchange->payin_memo
            );
        }

        $exchange->update([
            'payin_tx_id' => $tx,
            'swap_exchange_state_id' => 2 //sent_to_provider
        ]);

        SwapEvent::create([
            'swap_id' => $swap->id,
            'swap_event_type_id' => 10,
            'message' => 'Funds sent to provider',
            'meta' => json_encode(['tx' => $tx])
        ]);

        if ($swap->fromBlockchain->asset_code === 'xlm') {
            VerifyXrpAndCompleteSwapJob::dispatch($swap->id);
        } else {
            VerifyXlmAndCompleteSwapJob::dispatch($swap->id);
        }
    }
}
