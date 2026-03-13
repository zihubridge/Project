<?php

namespace App\Jobs;

use App\Models\InternalSwap;
use App\Models\Swap;
use App\Models\SwapEvent;
use App\Models\SwapPayout;
use App\Services\Ripple\XrplSwapService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class VerifyXrpAndCompleteSwapJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $swapId;

    public int $tries = 60; // Retry for 60 minutes before failing

    public function __construct(int $swapId)
    {
        $this->swapId = $swapId;
    }

    /**
     * Retry delay strategy (seconds)
     */
    public function backoff(): array
    {
        return [60, 60, 60, 120, 300];
    }

    public function handle(XrplSwapService $xrpl): void
    {
        $swap = Swap::with('toToken')->findOrFail($this->swapId);

        // Hard stop if already completed or failed or refunded
        if (in_array($swap->swap_state_id, [10, 11, 12], true)) {
            return;
        }

        $exchange = $swap->exchange;

        if (!$exchange) {
            throw new RuntimeException('Swap exchange record missing');
        }

        // ------------------------------------------------------------------
        // STEP 1: Check XRP receipt from ChangeNOW
        // ------------------------------------------------------------------
        $receipt = $xrpl->checkXrpReceipt(
            $exchange->payout_memo
        );

         if (($receipt['status'] ?? null) !== 'success') {

            // Move once: sent_to_provider -> waiting_provider
            if ($exchange->swap_exchange_state_id === 2) {
                $exchange->update([
                    'swap_exchange_state_id' => 3 // waiting_provider
                ]);
            }

            Log::info("XRP not received yet from exchange for Swap #{$this->swapId}. Retrying in 60s...");

            $this->release(60);
            return;
        }

        // ------------------------------------------------------------------
        // STEP 2: Mark ChangeNOW → XRP received
        // ------------------------------------------------------------------
        $exchange->update([
            'received_amount' => $receipt['amount_received'],
            'payout_tx_id'  => $receipt['tx_hash'],
            'swap_exchange_state_id' => 5 //PROVIDER_COMPLETED,
        ]);

        $swap->update([
            'swap_state_id' => 7 //PROVIDER_COMPLETED,
        ]);

        SwapEvent::create([
            'swap_id' => $swap->id,
            'swap_event_type_id' => 12, //EXCHANGE_ORDER_COMPLETED,
            'meta' => json_encode($receipt),
        ]);

        $internalSwap = InternalSwap::create([
            'swap_id' => $swap->id,
            'blockchain_id' => $swap->to_blockchain_id,
            'leg' => 'destination',
            'amount_in' => $exchange->received_amount,
            'internal_swap_state_id' => 1, //creating
        ]);

        try {
            // ------------------------------------------------------------------
            // STEP 3: Internal XRP → Token swap
            // ------------------------------------------------------------------
            $swap->update(['swap_state_id' => 4]); // swapping_to_token

            $xrplResult = $xrpl->xrpToToken(
                xrpAmount: $exchange->expected_amount,
                tokenCurrency: $swap->toToken->asset_code,
                tokenIssuer: $swap->toToken->issuer_address,
                minTokenOut: '0.0000001'
            );

            if (!$xrplResult['ok']) {
                $swap->update([
                    'swap_state_id' => 11, //FAILED,
                    'failure_reason' => 'Internal XRP Token swap failed'
                ]);
                throw new RuntimeException('XRP to token swap failed');
            }

             $internalSwap->update([
                'amount_out' => $xrplResult['amount_out'],
                'tx_hash' => $xrplResult['tx_hash'],
                'internal_swap_state_id' => 2,
                'meta' => json_encode($xrplResult)
            ]);

            // ------------------------------------------------------------------
            // STEP 4: Send token to user
            // ------------------------------------------------------------------
            $swap->update(['swap_state_id' => 8]); // payout processing

            $payout = SwapPayout::create([
                'swap_id'       => $swap->id,
                'blockchain_id' => $swap->to_blockchain_id,
                'token_id'      => $swap->to_token_id,
                'amount'        => $internalSwap->amount_out,
                'to_address'    => $swap->destination_address,
                'swap_payout_state_id'      => 1 //CREATING,
            ]);

            $sendResult = $xrpl->sendXrpTokenToDestination(
                tokenAmount: $internalSwap->amount_out,
                tokenCurrency: $swap->toToken->asset_code,
                tokenIssuer: $swap->toToken->issuer_address,
                destination: $swap->destination_address
            );

            if (!($sendResult['ok'] ?? false)) {
                Log::error('[SWAP] Token send to user failed', [
                    'swap_id' => $swap->id,
                    'error'   => $sendResult['message'] ?? 'unknown error',
                ]);

                $payout->update([
                    'swap_payout_state_id' => 3 //FAILED,
                ]);

                $swap->update([
                    'swap_state_id'   => 11, // failed
                    'failure_reason' => 'Failed to send token to destination: ' .
                        ($sendResult['message'] ?? 'unknown'),
                ]);

                throw new RuntimeException('Final token transfer failed');
            }

            // ------------------------------------------------------------------
            // STEP 5: Finalize swap
            // ------------------------------------------------------------------
            $swap->update([
                'swap_state_id' => 9, // complete
                'to_final_token_amount' => $internalSwap->amount_out,
                'completed_at' => now()
            ]);

            $payout->update([
                'tx_hash'  => $sendResult['tx_hash'],
                'swap_payout_state_id' => 2 //SENT,
            ]);

            SwapEvent::create([
                'swap_id' => $swap->id,
                'swap_event_type_id' => 18 //SWAP_COMPLETED,
            ]);
        } catch (\Throwable $e) {
            Log::error('[VerifyXrp] Finalization failed', [
                'swap_id' => $this->swapId,
                'error'   => $e->getMessage(),
            ]);

            $swap->update([
                'swap_state_id' => 11, // failed
                'failure_reason' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
