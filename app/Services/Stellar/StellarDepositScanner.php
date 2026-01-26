<?php

namespace App\Services\Stellar;

use App\Models\SwapDeposit;
use Illuminate\Support\Facades\Http;

class StellarDepositScanner
{
    public function scan(SwapDeposit $deposit): ?array
    {
        $address = $deposit->deposit_address;

        $url = rtrim(env('STELLAR_HORIZON_MAINNET'), '/')
            . "/accounts/{$address}/payments?order=desc&limit=100";

        $res = Http::timeout(15)->get($url);
        if ($res->failed()) {
            return null;
        }

        $payments = data_get($res->json(), '_embedded.records', []);

        foreach ($payments as $p) {
            // Verify Destination Address
            if (($p['to'] ?? null) !== $address) continue;

            // Verify Asset (Code and Issuer)
            if (($p['asset_code'] ?? null) !== $deposit->expectedToken->asset_code) continue;
            if (($p['asset_issuer'] ?? null) !== $deposit->expectedToken->issuer_address) continue;

            // Verify Amount (Using bccomp for high-precision math)
            if (bccomp((string)$p['amount'], (string)$deposit->expected_amount, 7) < 0) continue;

            // Fetch Transaction Details to check the Memo
            $txHash = $p['transaction_hash'];

            // Note: Ensure 'services.stellar.horizon_url' is set in config/services.php
            $baseUrl = config('services.stellar.horizon_url', 'https://horizon.stellar.org');

            $tx = Http::timeout(15)->get(rtrim($baseUrl, '/') . "/transactions/{$txHash}")->json();


            // Verify Memo
            // We allow both 'id' and 'text' because different wallets send numeric memos differently
            $validMemoTypes = ['id', 'text'];
            $memoType = $tx['memo_type'] ?? null;
            $memoValue = (string)($tx['memo'] ?? '');
            $expectedMemo = (string)$deposit->routing_value;

            if (!in_array($memoType, $validMemoTypes)) {
                continue;
            }

            if ($memoValue !== $expectedMemo) {
                continue;
            }

            // Success: Return payment details

            return [
                'tx_hash' => $txHash,
                'sender' => $p['from'] ?? null,
                'amount' => $p['amount'],
            ];
        }

        return null;
    }
}
