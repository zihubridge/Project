<?php

namespace App\Jobs;

use App\Models\SwapDeposit;
use App\Models\SwapEvent;
use App\Services\Stellar\StellarDepositScanner;
use App\Services\Ripple\XrplDepositScanner;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
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

        $SwapDeposit = SwapDeposit::with(['swap.fromBlockchain', 'expectedToken'])->find($this->depositId);

        if (!$SwapDeposit) {
            Log::warning('[ScanDepositJob] Deposit not found', [
                'deposit_id' => $this->depositId,
            ]);
            return;
        }

        // Only process if still waiting
        if ($SwapDeposit->deposit_state_id !== 1) {
            Log::info('[ScanDepositJob] Deposit already processed', [
                'deposit_id' => $SwapDeposit->id,
                'state' => $SwapDeposit->deposit_state_id,
            ]);
            return;
        }

        // If swap already failed or expired, stop
        if (in_array($SwapDeposit->swap->swap_state_id, [10, 11, 12])) {
            Log::info('[ScanDepositJob] Swap already closed', [
                'swap_id' => $SwapDeposit->swap_id,
            ]);
            return;
        }

        // Expired → mark failed
        if (now()->greaterThan($SwapDeposit->expires_at) && $SwapDeposit->swap->swap_state_id == 2) {
            Log::info('[ScanDepositJob] Deposit expired', [
                'deposit_id' => $SwapDeposit->id,
            ]);
            $SwapDeposit->update([
                'deposit_state_id' => 4, // expired
            ]);

            $SwapDeposit->swap->update([
                'swap_state_id' => 10, // expired
            ]);

            SwapEvent::create([
                'swap_id' => $SwapDeposit->swap_id,
                'swap_event_type_id' => 3, // Deposit Expired
                'message' => 'User did not deposit before expiry',
            ]);

            return;
        }

        $blockchainId = $SwapDeposit->swap->fromBlockchain->id;

        Log::info('[ScanDepositJob] Scanning blockchain', [
            'deposit_id' => $SwapDeposit->id,
            'blockchain_id' => $blockchainId,
        ]);

        $found = match ($blockchainId) {
            1 => $stellarScanner->scan($SwapDeposit),
            2 => $xrplScanner->scan($SwapDeposit),
            default   => false,
        };

        // Handle Results
        if ($found) {

            Log::info('[ScanDepositJob] Deposit found', [
                'deposit_id' => $SwapDeposit->id,
                'tx_hash' => $found['tx_hash'] ?? null,
            ]);

            $SwapDeposit->update([
                'deposit_state_id' => 3, // confirmed
                'tx_hash' => $found['tx_hash'],
                'sender_address' => $found['sender'],
                'received_amount' => $found['amount'],
                'received_at' => now(),
            ]);

            $SwapDeposit->swap->update(['swap_state_id' => 3]); //deposit received

            SwapEvent::create([
                'swap_id' => $SwapDeposit->swap_id,
                'swap_event_type_id' => 2, //Deposit Confirmed
                'message' => 'Deposit confirmed on chain',
                'meta' => json_encode($found),
            ]);

            ExecuteSwapJob::dispatch($SwapDeposit->swap_id);

            Log::info('[ScanDepositJob] Swap job dispatched', [
                'swap_id' => $SwapDeposit->swap_id,
            ]);
            return; // Job finished successfully
        }

        Log::info('[ScanDepositJob] Deposit not foundss, retrying', [
            'deposit_id' => $SwapDeposit->id,
        ]);

        $this->release(20);
    }
}
