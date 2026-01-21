<?php

namespace App\Services\Stellar;

use App\Models\SwapDeposit;
use Illuminate\Support\Facades\Http;

class StellarDepositScanner
{
    public function scan(SwapDeposit $deposit): ?array
    {
        $address = $deposit->deposit_address;

        $url = rtrim(env('STELLAR_PUBLIC_ADDRESS'), '/')
            . "/accounts/{$address}/payments?order=desc&limit=100";

        $res = Http::timeout(15)->get($url);
        if ($res->failed()) {
            return null;
        }

        $payments = data_get($res->json(), '_embedded.records', []);

        foreach ($payments as $p) {
            if (($p['to'] ?? null) !== $address) continue;

            if (($p['asset_code'] ?? null) !== $deposit->expectedToken->asset_code) continue;
            if (($p['asset_issuer'] ?? null) !== $deposit->expectedToken->issuer_address) continue;

            if (bccomp((string)$p['amount'], (string)$deposit->expected_amount, 7) < 0) continue;

            $txHash = $p['transaction_hash'];

            $tx = Http::timeout(15)->get(
                rtrim(env('STELLAR_PUBLIC_ADDRESS'), '/') . "/transactions/{$txHash}"
            )->json();

            if (($tx['memo_type'] ?? null) !== 'id') continue;
            if ((string)$tx['memo'] !== (string)$deposit->routing_value) continue;

            return [
                'tx_hash' => $txHash,
                'sender' => $p['from'] ?? null,
                'amount' => $p['amount'],
            ];
        }

        return null;
    }
}
