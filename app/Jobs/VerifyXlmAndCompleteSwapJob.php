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

class VerifyXlmAndCompleteSwapJob implements ShouldQueue
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
        if (!$exchange->payout_tx_id) {

            $exchange->update([
                'received_amount' => $receipt['amount_received'],
                'payout_tx_id' => $receipt['tx_hash'],
                'swap_exchange_state_id' => 5 //PROVIDER_COMPLETED,
            ]);

            $swap->update([
                'swap_state_id' => 7 //PROVIDER_COMPLETED,
            ]);

            SwapEvent::create([
                'swap_id' => $swap->id,
                'swap_event_type_id' => 11, //EXCHANGE_ORDER_COMPLETED,
                'message' => 'Exchange completed and funds received',
                'meta' => json_encode($receipt),
            ]);

            SwapEvent::create([
                'swap_id' => $swap->id,
                'swap_event_type_id' => 5,
                'message' => 'Internal swap started',
                'meta' => json_encode([
                    'leg' => 'destination'
                ])
            ]);
        }

        /*
    |--------------------------------------------------------------------------
    | STEP 3 — Internal destination swap (RESUMABLE)
    |--------------------------------------------------------------------------
    */
        $internalSwap = InternalSwap::firstOrCreate(
            [
                'swap_id' => $swap->id,
                'leg' => 'destination'
            ],
            [
                'blockchain_id' => $swap->to_blockchain_id,
                'from_token_id' => $swap->from_token_id,
                'to_token_id' => $swap->to_token_id,
                'amount_in' => $exchange->received_amount,
                'internal_swap_state_id' => 1
            ]
        );

        // run swap ONLY if not completed yet
        if (!$internalSwap->tx_hash) {

            Log::info("[JOB] Running internal XLM->Token swap for {$swap->id}");

            $xlmResult = $xlm->xlmToToken(
                tokenCode: $swap->toToken->asset_code,
                issuer: $swap->toToken->issuer_address,
                amountIn: $exchange->received_amount,
                minTokenOut: '0.0000001'
            );

            if (!($xlmResult['ok'] ?? false)) {

                SwapEvent::create([
                    'swap_id' => $swap->id,
                    'swap_event_type_id' => 7,
                    'message' => 'Internal swap failed',
                    'meta' => json_encode([
                        'leg' => 'destination'
                    ])
                ]);

                throw new RuntimeException(
                    $xlmResult['message'] ?? 'Internal payout swap failed'
                );
            }

            $internalSwap->update([
                'amount_out' => $xlmResult['amount_out'],
                'tx_hash' => $xlmResult['tx_hash'],
                'internal_swap_state_id' => 2,
                'meta' => json_encode($xlmResult)
            ]);

            SwapEvent::create([
                'swap_id' => $swap->id,
                'swap_event_type_id' => 6,
                'message' => 'Internal swap completed',
                'meta' => json_encode([
                    'leg' => 'destination'
                ])
            ]);
        }

        /*
    |--------------------------------------------------------------------------
    | STEP 4 — Move to payout processing
    |--------------------------------------------------------------------------
    */
        if ($swap->swap_state_id < 8) {
            $swap->update([
                'swap_state_id' => 8 // payout_processing
            ]);
        }

        /*
    |--------------------------------------------------------------------------
    | STEP 5 — Create payout (idempotent)
    |--------------------------------------------------------------------------
    */
        $payout = SwapPayout::firstOrCreate(
            ['swap_id' => $swap->id],
            [
                'blockchain_id' => $swap->to_blockchain_id,
                'token_id' => $swap->to_token_id,
                'amount' => $internalSwap->amount_out,
                'to_address' => $swap->destination_address,
                'swap_payout_state_id' => 1 // creating
            ]
        );

        /*
    |--------------------------------------------------------------------------
    | STEP 6 — Send token to user
    |--------------------------------------------------------------------------
    */
        if (!$payout->tx_hash) {

            Log::info("[JOB] Sending payout for swap {$swap->id}");

            $sendResult = $xlm->sendXlmTokenToDestination(
                amount: $internalSwap->amount_out,
                assetCode: $swap->toToken->asset_code,
                issuer: $swap->toToken->issuer_address,
                destination: $swap->destination_address
            );

            if (!($sendResult['ok'] ?? false)) {

                $payout->update([
                    'swap_payout_state_id' => 3 //FAILED,
                ]);

                $swap->update([
                    'swap_state_id'   => 11, // failed
                    'failure_reason' =>
                    'Failed sending payout: ' .
                        ($sendResult['message'] ?? 'unknown')
                ]);

                throw new RuntimeException('Final token transfer failed');
            }

            $payout->update([
                'tx_hash' => $sendResult['tx_hash'],
                'swap_payout_state_id' => 2 // sent
            ]);
        }

        /*
    |--------------------------------------------------------------------------
    | STEP 7 — Finalize swap
    |--------------------------------------------------------------------------
    */
        if ($swap->swap_state_id !== 9) {

            $swap->update([
                'swap_state_id' => 9,
            ]);

            SwapEvent::create([
                'swap_id' => $swap->id,
                'swap_event_type_id' => 17 //SWAP_COMPLETED,
            ]);
        }

        Log::info("[JOB] Swap {$swap->id} completed successfully.");
    }
}
