<?php

namespace App\Jobs;

use App\Models\SwapDeposit;
use App\Services\Stellar\StellarDepositScanner;
use App\Services\Xrpl\XrplDepositScanner;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ScanDepositJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $depositId;

    public int $tries = 45;        // 45 × 20s ≈ 15 minutes
    public int $timeout = 20;

    public function __construct(int $depositId)
    {
        $this->depositId = $depositId;
    }

    public function handle(
        StellarDepositScanner $stellarScanner,
        XrplDepositScanner $xrplScanner
    ): void {
        $deposit = SwapDeposit::with(['swap', 'expectedToken'])->find($this->depositId);

        // Deposit removed or already processed
        if (!$deposit || $deposit->deposit_state_id !== 1) {
            return;
        }

        // Expired → mark failed
        if (now()->greaterThan($deposit->expires_at)) {
            $deposit->update([
                'deposit_state_id' => 4, // expired
            ]);

            $deposit->swap->update([
                'swap_state_id' => 5, // expired
            ]);

            return;
        }

        $blockchain = $deposit->swap->fromBlockchain->slug;

        $found = match ($blockchain) {
            'stellar' => $stellarScanner->scan($deposit),
            'xrpl'    => $xrplScanner->scan($deposit),
            default   => false,
        };

        // If not found, retry after delay
        if (!$found) {
            self::dispatch($this->depositId)->delay(now()->addSeconds(20));
        }
    }
}
