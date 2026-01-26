<?php

namespace App\Services\Stellar;

use App\Models\SwapDeposit;
use Illuminate\Support\Facades\Http;

class StellarDepositScanner
{
    public function scan(SwapDeposit $deposit): ?array
    {
        $address = $deposit->deposit_address;

        $baseUrl = config('services.stellar.horizon_url');

        // Fetch recent payments for this account
        $url = rtrim($baseUrl, '/') . "/accounts/{$address}/payments?order=desc&limit=100";

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

            // Verify Amount (Matches exactly or more)
            if (bccomp((string)$p['amount'], (string)$deposit->expected_amount, 7) < 0) continue;

            // Fetch Transaction Details to check the Memo
            $txHash = $p['transaction_hash'];

            // We call the transaction endpoint because the payments endpoint 
            // does not always include the memo in the basic record
            $tx = Http::timeout(15)->get(rtrim($baseUrl, '/') . "/transactions/{$txHash}")->json();

            // Verify Memo Logic
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

            // Success: Return payment details for the Job to process
            return [
                'tx_hash' => $txHash,
                'sender' => $p['from'] ?? null,
                'amount' => $p['amount'],
            ];
        }

        return null;
    }
}
