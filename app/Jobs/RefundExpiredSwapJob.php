<?php

namespace App\Jobs;

use App\Models\Swap;
use App\Models\SwapDeposit;
use App\Models\SwapEvent;
use App\Services\Ripple\XrplSwapService;
use App\Services\Stellar\StellarSwapService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RefundExpiredSwapJob implements ShouldQueue
{
    use Queueable;

    public int $swapId;

    public function __construct(int $swapId)
    {
        $this->swapId = $swapId;
    }

    public function handle(
        StellarSwapService $stellar,
        XrplSwapService $xrpl
    ): void {

        DB::transaction(function () use ($stellar, $xrpl) {

            $swap = Swap::lockForUpdate()
                ->with(['deposit', 'fromToken'])
                ->find($this->swapId);

            if (!$swap) {
                Log::error('[RefundExpiredSwapJob] Swap not found', [
                    'swap_id' => $this->swapId
                ]);
                return;
            }

            $deposit = SwapDeposit::lockForUpdate()
                ->where('swap_id', $swap->id)
                ->first();

            if (!$deposit) {
                Log::error('[RefundExpiredSwapJob] Deposit not found', [
                    'swap_id' => $swap->id
                ]);
                return;
            }

            // CRITICAL: prevent double refund
            if ($deposit->deposit_state_id == 6 || $deposit->refund_tx_hash) {
                Log::info('[RefundExpiredSwapJob] Already refunded', [
                    'swap_id' => $swap->id
                ]);
                return;
            }

            if (!$deposit->sender_address || !$deposit->received_token_amount) {
                Log::error('[RefundExpiredSwapJob] Missing deposit info', [
                    'swap_id' => $swap->id
                ]);
                return;
            }

            // --------------------------------
            // Execute refund
            // --------------------------------

            if ($swap->from_blockchain_id == 1) {

                $result = $stellar->refundToken(
                    amount: $deposit->received_token_amount,
                    assetCode: $swap->fromToken->code,
                    issuer: $swap->fromToken->issuer,
                    destination: $deposit->sender_address,
                    memoText: "Refund Zihu Swap"
                );
            } else if ($swap->from_blockchain_id == 2) {

                $result = $xrpl->refundToken(
                    amount: $deposit->received_token_amount,
                    currency: $swap->fromToken->code,
                    issuer: $swap->fromToken->issuer,
                    destination: $deposit->sender_address,
                    memo: "Refund Zihu Swap"
                );
            } else {

                Log::error('[RefundExpiredSwapJob] Unknown blockchain', [
                    'swap_id' => $swap->id
                ]);
                return;
            }

            if (!$result['ok']) {

                Log::error('[RefundExpiredSwapJob] Refund failed', [
                    'swap_id' => $swap->id,
                    'error' => $result['message']
                ]);

                return;
            }

            // --------------------------------
            // Update database AFTER success
            // --------------------------------

            $deposit->update([
                'deposit_state_id' => 6, // refunded
                'refund_tx_hash' => $result['tx_hash'],
                'refunded_at' => now(),
            ]);

            $swap->update([
                'swap_state_id' => 13, // refunded
            ]);

            SwapEvent::create([
                'swap_id' => $swap->id,
                'swap_event_type_id' => 20,
                'message' => 'Refunded',
                'meta' => json_encode([
                    'tx_hash' => $result['tx_hash']
                ])
            ]);
        });
    }
}
