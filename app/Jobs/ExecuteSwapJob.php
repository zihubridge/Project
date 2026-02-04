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

        // Swap gone or already executed
        if (!$swap || $swap->swap_state_id !== 2) {
            return;
        }

        DB::beginTransaction();

        try {
            // Lock swap to prevent double execution
            $swap->update([
                'swap_state_id' => 8, // swapping to coin 
            ]);

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        try {

            $deposit = $swap->deposit;
            $xlmAmount = 0;

            $fromBlockchainId = $swap->fromBlockchain->id;

            // Stellar Token -> XLM -> XRP -> XRPL Token
            if ($fromBlockchainId === 1) { // Stellar
                Log::info('[SWAP] Stellar token → XLM');

                // XLM Token → XLM
                $stellarResult = $stellar->xlmTokenToXlm(
                    tokenCode: $swap->fromToken->asset_code,
                    issuer: $swap->fromToken->issuer_address,
                    amountIn: $deposit->received_amount,
                    minXlmOut: '0.0000001',
                    memo: $swap->routing_value, 
                    swapId: $swap->id
                );

                $xlmAmount = $stellarResult['min_out'];

                // XLM → XRP (ChangeNOW)
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
                    throw new \RuntimeException('ChangeNOW did not return toAmount');
                }

                $depositAddress = $exchange['payinAddress'];
                $depositMemo    = $exchange['payinExtraId'] ?? null;

                // we expect to get back in XRP
                $expectedXrp = (string) $exchange['toAmount'];

                // Send the XLM
                $stellar->sendXlmToExchange($depositAddress, (string)$xlmAmount, $depositMemo);

                //Verifying if changenow have sent xrp to platform wallet or not 
                VerifyXrpAndCompleteSwap::dispatch($deposit->swap_id);
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
