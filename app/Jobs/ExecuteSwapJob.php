<?php

namespace App\Jobs;

use App\Models\Swap;
use App\Services\Stellar\StellarSwapService;
use App\Services\Swap\ChangeNowService;
use App\Services\Ripple\XrplSwapService;
use App\Services\Ripple\XrplDepositScanner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ExecuteSwapJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $swapId;

    public int $tries = 5;
    public int $timeout = 120;

    public function __construct(int $swapId)
    {
        $this->swapId = $swapId;
    }

    public function handle(
        StellarSwapService $stellar,
        ChangeNowService $changeNow,
        XrplSwapService $xrpl
    ): void {
        $swap = Swap::with([
            'deposit',
            'fromToken',
            'toToken',
            'fromBlockchain',
            'toBlockchain'
        ])->find($this->swapId);

        // Ensure we only process if in 'deposit_received' (ID 3)
        if (!$swap || $swap->swap_state_id !== 3) {
            return;
        }

        try {
            $deposit = $swap->deposit;
            $fromBlockchainId = $swap->fromBlockchain->id;

            // ------------------------------------------------------------------
            // STEP 1: Internal Swap (Token -> XLM/XRP)
            // ------------------------------------------------------------------
            $swap->update(['swap_state_id' => 8]); // 'swapping_to_coin'

            if ($fromBlockchainId === 1) { // Stellar
                Log::info('[SWAP] Stellar token → XLM');

                $stellarResult = $stellar->xlmTokenToXlm(
                    tokenCode: $swap->fromToken->asset_code,
                    issuer: $swap->fromToken->issuer_address,
                    amountIn: $deposit->received_amount,
                    minXlmOut: '0.0000001',
                    memo: $swap->routing_value,
                    swapId: $swap->id
                );

                $xlmAmount = $stellarResult['min_out'];

                // ------------------------------------------------------------------
                // STEP 2: Initiate ChangeNOW Exchange
                // ------------------------------------------------------------------
                Log::info('[SWAP] XLM → XRP via ChangeNOW');
                $destinationTag = rand(100000, 999999);

                $exchange = $changeNow->createExchange(
                    fromCurrency: 'xlm',
                    toCurrency: 'xrp',
                    destinationAddress: config('services.xrpl.wallet'),
                    extraId: (string)$destinationTag,
                    fromNetwork: 'xlm',
                    toNetwork: 'xrp',
                    fromAmount: (string)$xlmAmount
                );

                if (empty($exchange['payinAddress'])) {
                    throw new \RuntimeException('ChangeNOW did not return payinAddress');
                }

                // Save the ChangeNOW data for the next job to find
                $swap->update([
                    'expected_xrp_amount' => (string) $exchange['toAmount'],
                    'destination_tag'     => (string) $destinationTag,
                    'swap_state_id'       => 4, // 'sent_to_changenow'
                ]);

                $depositAddress = $exchange['payinAddress'];
                $depositMemo    = $exchange['payinExtraId'] ?? null;

                // ------------------------------------------------------------------
                // STEP 3: Send funds to ChangeNOW
                // ------------------------------------------------------------------
                try {
                    // Send the XLM to ChangeNOW
                    $txHash = $stellar->sendXlmToExchange($depositAddress, (string)$xlmAmount, $depositMemo);

                    // On success, update the swap state to 'waiting_changenow' (ID 5)
                    $swap->update([
                        'swap_state_id' => 5,
                        'external_tx_id' => $txHash // Highly recommended to add this column
                    ]);

                    Log::info("[SWAP] XLM sent to ChangeNOW. Hash: $txHash. Moving to State 5.");

                    // Dispatch the Verifier to watch for the return XRP
                    VerifyXrpAndCompleteSwap::dispatch($swap->id);
                } catch (Throwable $e) {
                    Log::error('[SWAP ERROR] ' . $e->getMessage());
                    $swap->update([
                        'swap_state_id' => 12, // 'failed'
                        'failure_reason' => 'Stellar to ChangeNOW transfer failed: ' . $e->getMessage()
                    ]);
                    throw $e;
                }
            }

            // Ripple Token -> XRP -> XLM -> XLM Token
            else if ($fromBlockchainId === 2) {
            } else {
                throw new \RuntimeException('Unsupported from blockchain');
            }
        } catch (Throwable $e) {
            Log::error('[SWAP] Swap failed', [
                'swap_id' => $swap->id,
                'error' => $e->getMessage(),
            ]);

            DB::transaction(function () use ($swap, $e) {
                $swap->update([
                    'swap_state_id' => 6,
                    'failure_reason' => $e->getMessage(),
                ]);
            });

            throw $e;
        }
    }
}
