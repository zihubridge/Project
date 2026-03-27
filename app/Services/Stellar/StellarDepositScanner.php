<?php

namespace App\Services\Stellar;

use App\Models\SwapDeposit;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class StellarDepositScanner
{
    public function scan(SwapDeposit $deposit): array
    {
        $address = $deposit->deposit_address;
        $baseUrl = config('services.stellar.horizon_url');

        $url = rtrim($baseUrl, '/') . "/accounts/{$address}/payments?order=desc&limit=100";

        try {
            $res = Http::timeout(15)->get($url);

            if ($res->failed()) {
                Log::error('[StellarDepositScanner] Horizon payments failed', [
                    'status' => $res->status(),
                    'body' => $res->body(),
                ]);

                return [
                    'status' => 'error',
                    'reason' => 'horizon_payments_failed',
                ];
            }

            $payments = data_get($res->json(), '_embedded.records', []);

            foreach ($payments as $p) {

                if (($p['to'] ?? null) !== $address) {
                    continue;
                }

                if (($p['asset_code'] ?? null) !== $deposit->expectedToken->asset_code) {
                    continue;
                }

                if (($p['asset_issuer'] ?? null) !== $deposit->expectedToken->issuer_address) {
                    continue;
                }

                if (bccomp((string)$p['amount'], (string)$deposit->expected_amount, 7) < 0) {
                    continue;
                }

                $txHash = $p['transaction_hash'] ?? null;
                if (!$txHash) {
                    continue;
                }

                $txUrl = rtrim($baseUrl, '/') . "/transactions/{$txHash}";
                $txRes = Http::timeout(15)->get($txUrl);

                if ($txRes->failed()) {
                    Log::error('[StellarDepositScanner] Horizon transaction fetch failed', [
                        'tx_hash' => $txHash,
                        'status' => $txRes->status(),
                    ]);
                    continue;
                }

                $tx = $txRes->json();

                $memoType = $tx['memo_type'] ?? null;
                $memoValue = (string)($tx['memo'] ?? '');
                $expectedMemo = (string)$deposit->deposit_routing_value;

                if (!in_array($memoType, ['id', 'text'], true)) {
                    continue;
                }

                if ($memoValue !== $expectedMemo) {
                    continue;
                }

                return [
                    'status' => 'success',
                    'tx_hash' => $txHash,
                    'sender' => $p['from'] ?? null,
                    'amount' => $p['amount'],
                    'ledger' => $p['ledger'] ?? null,
                ];
            }

            return [
                'status' => 'not_found',
            ];
        } catch (\Throwable $e) {

            Log::error('[StellarDepositScanner] Unexpected exception', [
                'deposit_id' => $deposit->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => 'error',
                'reason' => 'exception',
                'message' => $e->getMessage(),
            ];
        }
    }
}
