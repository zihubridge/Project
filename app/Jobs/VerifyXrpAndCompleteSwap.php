<?php

namespace App\Jobs;

use App\Models\Swap;
use App\Services\Ripple\XrplSwapService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class VerifyXrpAndCompleteSwap implements ShouldQueue
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

        // Hard stop if already completed or failed
        if (in_array($swap->swap_state_id, [10, 12], true)) {
            Log::info('[VerifyXrp] Swap already finalized', [
                'swap_id' => $this->swapId,
                'state'   => $swap->swap_state_id,
            ]);
            return;
        }

        // ------------------------------------------------------------------
        // STEP 1: Check XRP receipt from ChangeNOW
        // ------------------------------------------------------------------
        $receipt = $xrpl->checkXrpReceipt(
            $swap->destination_tag,
            $swap->expected_xrp_amount
        );

        if (($receipt['status'] ?? null) !== 'success') {
            Log::info('[VerifyXrp] XRP not received yet', [
                'swap_id' => $this->swapId,
                'destination_tag' => $swap->destination_tag,
                'expected_xrp' => $swap->expected_xrp_amount,
            ]);

            // IMPORTANT:
            // Throwing forces Laravel to retry using backoff()
            throw new \RuntimeException('XRP not received yet');
        }

        // ------------------------------------------------------------------
        // STEP 2: Mark ChangeNOW → XRP received
        // ------------------------------------------------------------------
        $swap->update([
            'swap_state_id'  => 6, // changenow_received
            'incoming_tx_id' => $receipt['tx_hash'],
        ]);

        Log::info('[VerifyXrp] XRP received from ChangeNOW', [
            'swap_id' => $this->swapId,
            'tx_hash' => $receipt['tx_hash'],
        ]);

        try {
            // ------------------------------------------------------------------
            // STEP 3: Swap XRP → destination token
            // ------------------------------------------------------------------
            $swap->update(['swap_state_id' => 7]); // swapping_to_token

            $xrplResult = $xrpl->xrpToToken(
                xrpAmount: $swap->expected_xrp_amount,
                tokenCurrency: $swap->toToken->asset_code,
                tokenIssuer: $swap->toToken->issuer_address,
                minTokenOut: '0.0000001'
            );

            // ------------------------------------------------------------------
            // STEP 4: Send token to user
            // ------------------------------------------------------------------
            $swap->update(['swap_state_id' => 9]); // sending_to_user

            $xrpl->sendXrpTokenToDestination(
                tokenAmount: $xrplResult['amount_out'],
                tokenCurrency: $swap->toToken->asset_code,
                tokenIssuer: $swap->toToken->issuer_address,
                destination: $swap->destination_address
            );

            // ------------------------------------------------------------------
            // STEP 5: Finalize swap
            // ------------------------------------------------------------------
            $swap->update([
                'swap_state_id' => 10, // completed
                'completed_at' => now(),
            ]);

            Log::info('[VerifyXrp] Swap completed successfully', [
                'swap_id' => $this->swapId,
            ]);
        } catch (\Throwable $e) {
            Log::error('[VerifyXrp] Finalization failed', [
                'swap_id' => $this->swapId,
                'error'   => $e->getMessage(),
            ]);

            // $swap->update([
            //     'swap_state_id' => 12, // failed
            //     'failure_reason' => $e->getMessage(),
            // ]);

            throw $e;
        }
    }
}
