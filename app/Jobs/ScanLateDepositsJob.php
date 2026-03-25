<?php

namespace App\Jobs;

use App\Models\SwapDeposit;
use App\Models\SwapEvent;
use App\Services\Ripple\XrplDepositScanner;
use App\Services\Stellar\StellarDepositScanner;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ScanLateDepositsJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(
        StellarDepositScanner $stellarScanner,
        XrplDepositScanner $xrplScanner
    ) {
        SwapDeposit::whereIn('deposit_state_id', [4, 7]) // expired/ late recevied deteced but amount not refuded yet
            ->whereNull('refund_tx_hash')
            ->chunkById(100, function ($deposits) use ($stellarScanner, $xrplScanner) {

                foreach ($deposits as $deposit) {

                    $swap = $deposit->swap;

                    $scanner = $swap->from_blockchain_id == 1 ? $stellarScanner : $xrplScanner;

                    $found = $scanner->scan($deposit);

                    if ($found && isset($found['tx_hash'])) {

                        DB::transaction(function () use ($deposit, $swap, $found) {

                            $deposit->update([
                                'deposit_state_id' => 6,
                                'refund_tx_hash' => $found['tx_hash'],
                                'sender_address' => $found['sender'],
                                'received_token_amount' => $found['amount'],
                                'received_at' => now(),
                            ]);

                            SwapEvent::create([
                                'swap_id' => $swap->id,
                                'swap_event_type_id' => 3,
                                'message' => 'Late deposit detected via scheduled scan',
                                'meta' => json_encode([
                                    'tx_hash' => $found['tx_hash']
                                ])
                            ]);

                            Log::info('[Late Deposit] Dispatching refund job', [
                                'swap_id' => $swap->id,
                                'deposit_id' => $deposit->id,
                                'tx_hash' => $found['tx_hash'],
                                'amount' => $found['amount'],
                                'blockchain' => $swap->from_blockchain_id == 1 ? 'stellar' : 'xrpl',
                            ]);

                            RefundExpiredSwapJob::dispatch($swap->id);
                        });
                    }
                }
            });
    }
}
