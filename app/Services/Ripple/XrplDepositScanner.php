<?php

namespace App\Services\Ripple;

use App\Models\SwapDeposit;
use Illuminate\Support\Facades\Http;

class XrplDepositScanner
{
    protected string $rpcUrl;

    public function __construct()
    {
        $this->rpcUrl = config('services.xrpl.rpc');
    }

    public function scan(SwapDeposit $deposit): ?array
    {
        $res = Http::timeout(20)->post($this->rpcUrl, [
            'method' => 'account_tx',
            'params' => [[
                'account' => $deposit->deposit_address,
                'limit'   => 50,
            ]]
        ]);

        if ($res->failed()) {
            return null;
        }

        $txs = data_get($res->json(), 'result.transactions', []);

        foreach ($txs as $entry) {
            $tx = $entry['tx'] ?? null;
            if (!$tx) continue;

            if (($tx['Destination'] ?? null) !== $deposit->deposit_address) continue;

            // Destination Tag = memo
            if ((int)($tx['DestinationTag'] ?? -1) !== (int)$deposit->routing_value) continue;

            // XRP payment
            if (is_string($tx['Amount'])) {
                $amount = bcdiv($tx['Amount'], '1000000', 6);
                if (bccomp($amount, $deposit->expected_amount, 6) < 0) continue;

                return [
                    'tx_hash' => $tx['hash'],
                    'sender'  => $tx['Account'],
                    'amount'  => $amount,
                    'asset'   => 'XRP',
                ];
            }

            // IOU payment
            if (is_array($tx['Amount'])) {
                if (($tx['Amount']['currency'] ?? null) !== $deposit->expectedToken->asset_code) continue;
                if (($tx['Amount']['issuer'] ?? null) !== $deposit->expectedToken->issuer_address) continue;

                if (bccomp($tx['Amount']['value'], $deposit->expected_amount, 18) < 0) continue;

                return [
                    'tx_hash' => $tx['hash'],
                    'sender'  => $tx['Account'],
                    'amount'  => $tx['Amount']['value'],
                    'asset'   => $tx['Amount']['currency'],
                ];
            }
        }

        return null;
    }
}
