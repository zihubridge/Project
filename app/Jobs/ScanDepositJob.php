<?php

namespace App\Jobs;

use App\Models\SwapDeposit;
use App\Services\Stellar\StellarDepositScanner;
use App\Services\Ripple\XrplDepositScanner;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ScanDepositJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $depositId;

    public int $tries = 45;        // 45 × 20s ≈ 15 minutes

    public function __construct(int $depositId)
    {
        $this->depositId = $depositId;
    }

    public function handle(
        StellarDepositScanner $stellarScanner,
        XrplDepositScanner $xrplScanner
    ): void {
        $deposit = SwapDeposit::with(['swap.fromBlockchain', 'expectedToken'])->find($this->depositId);

        // Deposit removed or already processed
        if (!$deposit || $deposit->deposit_state_id !== 1) {
            return;
        }

        // Expired → mark failed
        if (now()->greaterThan($deposit->expires_at) && $deposit->swap->swap_state_id == 2) {
            $deposit->update([
                'deposit_state_id' => 4, // expired
            ]);

            $deposit->swap->update([
                'swap_state_id' => 10, // expired
            ]);

            return;
        }

        $blockchainId = $deposit->swap->fromBlockchain->id;

        $found = match ($blockchainId) {
            1 => $stellarScanner->scan($deposit),
            2 => $xrplScanner->scan($deposit),
            default   => false,
        };

        // Handle Results
        if ($found) {
            $deposit->update([
                'deposit_state_id' => 3, // confirmed
                'tx_hash' => $found['tx_hash'],
                'sender_address' => $found['sender'],
                'received_amount' => $found['amount'],
                'received_at' => now(),
            ]);

            $deposit->swap->update(['swap_state_id' => 2]);

            ExecuteSwapJob::dispatch($deposit->swap_id);
            return; // Job finished successfully
        }

        // If not found, release back to queue for retry
        $this->release(now()->addSeconds(20));
    }
}
