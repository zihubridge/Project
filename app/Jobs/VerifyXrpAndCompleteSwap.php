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
    public $tries = 60; // Retry for 60 minutes before failing

    public function __construct(int $swapId)
    {
        $this->swapId = $swapId;
    }

    public function handle(XrplSwapService $xrpl)
    {
        // Reload the swap to get the latest data from the DB
        $swap = Swap::with(['toToken'])->findOrFail($this->swapId);

        // Safety Check: If already completed or failed, stop.
        if (in_array($swap->swap_state_id, [9, 12])) {
            return;
        }

        // Check if XRP arrived in our platform wallet
        // We use the destination_tag and expected_amount saved in the previous job
        $receipt = $xrpl->checkXrpReceipt($swap->destination_tag, $swap->expected_xrp_amount);

        if (!$receipt['received']) {
            // We release it back to the queue to try again in 60 seconds.
            Log::info("[POLLING] XRP for Swap #{$this->swapId} not found yet. Retrying in 60s...");
            return $this->release(60);
        }

        // FUNDS RECEIVED! Update state to 'changenow_received' (ID 6)
         $swap->update([
            'swap_state_id' => 6,
            'incoming_tx_id' => $receipt['tx_hash']
        ]);
        Log::info("[JOB] XRP received from ChangeNOW for Swap #{$this->swapId}.");

        try {
            // Update state to 'swapping_to_token' (ID 7)
            $swap->update(['swap_state_id' => 7]);

            $xrplResult = $xrpl->xrpToToken(
                xrpAmount: $swap->expected_xrp_amount,
                tokenCurrency: $swap->toToken->asset_code,
                tokenIssuer: $swap->toToken->issuer_address,
                minTokenOut: '0.0000001'
            );

            // Update state to 'sending_to_user' (ID 9)
            $swap->update(['swap_state_id' => 9]);

            $xrpl->sendXrpTokenToDestination(
                tokenAmount: $xrplResult['amount_out'],
                tokenCurrency: $swap->toToken->asset_code,
                tokenIssuer: $swap->toToken->issuer_address,
                destination: $swap->destination_address
            );

            // Finalize: 'completed' (ID 10)
            $swap->update([
                'swap_state_id' => 10,
                'completed_at' => now()
            ]);

            Log::info("[JOB] Swap #{$this->swapId} successfully completed.");
        } catch (\Throwable $e) {
            Log::error("[JOB ERROR] Swap #{$this->swapId} failed during final steps: " . $e->getMessage());
            $swap->update([
                'swap_state_id' => 12, // failed
                'failure_reason' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
