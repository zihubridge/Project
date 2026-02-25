<?php

namespace App\Jobs;

use App\Models\InternalSwap;
use App\Services\Stellar\StellarSwapService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;


use App\Models\Swap;
use App\Models\SwapEvent;
use App\Models\SwapPayout;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class VerifyXlmAndCompleteSwap implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $swapId;
    public $tries = 60; // Retry for 60 minutes before failing

    public function __construct(int $swapId)
    {
        $this->swapId = $swapId;
    }

    public function handle(StellarSwapService $xlm)
    {
        // Reload the swap to get the latest data from the DB
        $swap = Swap::with(['toToken', 'exchange'])->findOrFail($this->swapId);

        // Safety Check: If already completed or failed, stop.
        if (in_array($swap->swap_state_id, [9, 12])) {
            return;
        }

        $exchange = $swap->exchange;

        if (!$exchange) {
            Log::error("[JOB] Swap #{$this->swapId} has no exchange row.");
            return;
        }

        // ------------------------------------------------------------------
        // STEP 1: Check XLM receipt from Exchange
        // ------------------------------------------------------------------
        $receipt = $xlm->checkXlmReceipt((string) $exchange->payout_memo);

        if (!$receipt['received']) {

            // Move once: sent_to_provider -> waiting_provider
            if ($exchange->swap_exchange_state_id === 2) {
                $exchange->update([
                    'swap_exchange_state_id' => 3 // waiting_provider
                ]);
            }

            Log::info("[POLLING] XLM for Swap #{$this->swapId} not found yet. Retrying in 60s...");

            return $this->release(60);
        }

        // ------------------------------------------------------------------
        // STEP 2: Mark Exchange → XLM received
        // ------------------------------------------------------------------
        $exchange->update([
            'received_amount' => $receipt['amount_received'],
            'payout_tx_id'  => $receipt['tx_hash'],
            'swap_exchange_state_id' => 5 //PROVIDER_COMPLETED,
        ]);

        $swap->update([
            'swap_state_id' => 7 //PROVIDER_COMPLETED,
        ]);
        Log::info("[JOB] XLM received from provider for Swap #{$this->swapId}.");

         SwapEvent::create([
            'swap_id' => $swap->id,
            'swap_event_type_id' => 11, //EXCHANGE_ORDER_COMPLETED,
            'meta' => json_encode($receipt),
        ]);

        $internalSwap = InternalSwap::create([
            'swap_id' => $swap->id,
            'blockchain_id' => $swap->to_blockchain_id,
            'from_token_id' => $swap->from_token_id,
            'to_token_id' => $swap->to_token_id,
            'leg' => 'destination',
            'amount_in' => $exchange->received_amount,
        ]);


        try {
            // ------------------------------------------------------------------
            // STEP 3: Internal XLM → Token swap
            // ------------------------------------------------------------------
            $xlmResult = $xlm->xlmToToken(
                tokenCode: $swap->toToken->asset_code,
                issuer: $swap->toToken->issuer_address,
                amountIn: $exchange->expected_amount,
                minTokenOut: $swap->expected_token_amount ?? $swap->expected_xlm_amount
            );

            if (!($xlmResult['ok'] ?? true)) {
                throw new \RuntimeException(
                    $xlmResult['message'] ?? 'Internal payout swap failed'
                );
            }

            $internalSwap->update(['amount_out' => $xlmResult['amount_out'], 'tx_hash' => $xlmResult['tx_hash'], 'internal_swap_state_id' => 2 ]);

            $swap->update(['swap_state_id' => 8]); // payout processing

            $payout = SwapPayout::create([
                'swap_id'       => $swap->id,
                'blockchain_id' => $swap->to_blockchain_id,
                'token_id'      => $swap->to_token_id,
                'amount'        => $internalSwap->amount_out,
                'to_address'    => $swap->destination_address,
                'swap_payout_state_id'      => 1 //CREATING,
            ]);

            // -------------------------------------------------
            // STEP 4 — Send token to user
            // -------------------------------------------------
           $sendResult = $xlm->sendXlmTokenToDestination(
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
            ]);

            $payout->update([
                'tx_hash'  => $sendResult['tx_hash'],
                'swap_payout_state_id' => 2 //SENT,
            ]);

            SwapEvent::create([
                'swap_id' => $swap->id,
                'swap_event_type_id' => 17 //SWAP_COMPLETED,
            ]);

            Log::info("[JOB] Swap #{$this->swapId} successfully completed.");
        } catch (\Throwable $e) {

            Log::error("[JOB ERROR] Swap #{$this->swapId} failed: " . $e->getMessage());
            $swap->update([
                'swap_state_id' => 11, // failed
                'failure_reason' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
