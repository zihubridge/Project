<?php

namespace App\Jobs;

use App\Models\InternalSwap;
use App\Models\SwapDeposit;
use App\Models\SwapEvent;
use App\Jobs\ExecuteSwapJob;
use App\Services\Stellar\StellarDepositScanner;
use App\Services\Ripple\XrplDepositScanner;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
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
        
        $swap = $SwapDeposit->swap;

        // Only process if still waiting
        if ($SwapDeposit->deposit_state_id !== 1) {
            Log::info('[ScanDepositJob] Deposit already processed', [
                'deposit_id' => $SwapDeposit->id,
                'state' => $SwapDeposit->deposit_state_id,
            ]);
            return;
        }

        // If swap already failed or expired, stop
        if (in_array($swap->swap_state_id, [10, 11, 12])) {
            Log::info('[ScanDepositJob] Swap already closed', [
                'swap_id' => $swap->id,
            ]);
            return;
        }

        // Expired → mark failed
        if (now()->greaterThan($SwapDeposit->expires_at) && $swap->swap_state_id == 2) {
            Log::info('[ScanDepositJob] Deposit expired', [
                'deposit_id' => $SwapDeposit->id,
            ]);
            $SwapDeposit->update([
                'deposit_state_id' => 4, // expired
            ]);

            $swap->update([
                'swap_state_id' => 10, // expired
            ]);

            SwapEvent::create([
                'swap_id' => $swap->id,
                'swap_event_type_id' => 4, // Deposit Expired
                'message' => 'User did not deposit before expiry',
            ]);

            return;
        }

        $blockchainId = $swap->fromBlockchain->id;

        Log::info('[ScanDepositJob] Scanning blockchain', [
            'deposit_id' => $SwapDeposit->id,
            'blockchain_id' => $blockchainId,
        ]);

        $found = match ($blockchainId) {
            1 => $stellarScanner->scan($SwapDeposit),
            2 => $xrplScanner->scan($SwapDeposit),
            default => false,
        };

        if (!$found || !is_array($found) || !isset($found['tx_hash'], $found['sender'], $found['amount'])) {
            $this->release(20);
            return;
        }

        DB::transaction(function () use ($SwapDeposit, $swap, $found) {

            // 1. Update deposit first
            $SwapDeposit->update([
                'deposit_state_id' => 3, // confirmed
                'tx_hash' => $found['tx_hash'],
                'sender_address' => $found['sender'],
                'received_token_amount' => $found['amount'],
                'received_at' => now(),
            ]);

            // 2. Update swap state
            $swap->update([
                'swap_state_id' => 3,
                'started_at' => now(),
            ]);

            // 3. Create internal swap row safely (idempotent)
            InternalSwap::firstOrCreate(
                [
                    'swap_id' => $swap->id,
                    'leg'     => 'source',
                ],
                [
                    'blockchain_id'          => $swap->from_blockchain_id,
                    'from_token_id'          => $swap->from_token_id,
                    'to_token_id'            => $swap->to_token_id,
                    'amount_in'              => $SwapDeposit->received_token_amount,
                    'internal_swap_state_id' => 1, // creating
                ]
            );

            // 4. Log event
            SwapEvent::create([
                'swap_id' => $swap->id,
                'swap_event_type_id' => 2,
                'message' => 'Deposit confirmed on chain',
                'meta' => json_encode($found),
            ]);
        });

        // Dispatch outside transaction
        ExecuteSwapJob::dispatch($swap->id);
    }
}
