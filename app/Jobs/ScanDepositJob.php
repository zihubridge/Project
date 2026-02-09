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
use Illuminate\Support\Facades\Log;

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

        Log::info('[ScanDepositJob] Job started', [
            'deposit_id' => $this->depositId,
        ]);

        $deposit = SwapDeposit::with(['swap.fromBlockchain', 'expectedToken'])->find($this->depositId);

        // Deposit removed or already processed
        if (!$deposit || $deposit->deposit_state_id !== 1) {
            Log::info('[ScanDepositJob] Deposit already processed', [
                'deposit_id' => $deposit->id,
                'state' => $deposit->deposit_state_id,
            ]);
            return;
        }

        // Expired → mark failed
        if (now()->greaterThan($deposit->expires_at) && $deposit->swap->swap_state_id == 2) {
            Log::info('[ScanDepositJob] Deposit expired', [
                'deposit_id' => $deposit->id,
            ]);
            $deposit->update([
                'deposit_state_id' => 4, // expired
            ]);

            $deposit->swap->update([
                'swap_state_id' => 11, // expired
            ]);

            return;
        }

        $blockchainId = $deposit->swap->fromBlockchain->id;

        Log::info('[ScanDepositJob] Scanning blockchain', [
            'deposit_id' => $deposit->id,
            'blockchain_id' => $blockchainId,
        ]);

        $found = match ($blockchainId) {
            1 => $stellarScanner->scan($deposit),
            2 => $xrplScanner->scan($deposit),
            default   => false,
        };

        // Handle Results
        if ($found) {

            Log::info('[ScanDepositJob] Deposit found', [
                'deposit_id' => $deposit->id,
                'tx_hash' => $found['tx_hash'] ?? null,
            ]);

            $deposit->update([
                'deposit_state_id' => 3, // confirmed
                'tx_hash' => $found['tx_hash'],
                'sender_address' => $found['sender'],
                'received_amount' => $found['amount'],
                'received_at' => now(),
            ]);

            $deposit->swap->update(['swap_state_id' => 3]); //deposit received

            ExecuteSwapJob::dispatch($deposit->swap_id);

            Log::info('[ScanDepositJob] Swap job dispatched', [
                'swap_id' => $deposit->swap_id,
            ]);
            return; // Job finished successfully
        }

        Log::info('[ScanDepositJob] Deposit not found, retrying', [
            'deposit_id' => $deposit->id,
        ]);

        // If not found, release back to queue for retry
        $this->release(now()->addSeconds(20));
    }
}
