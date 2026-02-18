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

        if (empty($txs)) {
            return null;
        }

        foreach ($txs as $entry) {
            $tx = $entry['tx'] ?? null;
            if (!$tx) continue;

            if (($tx['Destination'] ?? null) !== $deposit->deposit_address) continue;

            // Destination Tag = memo
            $txDestTag = (int)($tx['DestinationTag'] ?? -1);
            $expectedDestTag = (int)$deposit->deposit_routing_value;

            if ($txDestTag !== $expectedDestTag) {
                continue;
            }

            // XRP payment
            if (is_string($tx['Amount'])) {
                $amount = bcdiv($tx['Amount'], '1000000', 6);
                if (bccomp($amount, $deposit->expected_token_amount, 6) < 0) continue;

                return [
                    'tx_hash' => $tx['hash'],
                    'sender'  => $tx['Account'],
                    'amount'  => $amount,
                    'asset'   => 'XRP',
                ];
            }

            // IOU payment
            if (is_array($tx['Amount'])) {
                // Decode hex currency code if needed
                $txCurrency = $tx['Amount']['currency'] ?? '';
                $decodedCurrency = $this->decodeCurrency($txCurrency);

                if ($decodedCurrency !== $deposit->expectedToken->asset_code) {
                    continue;
                }

                if (($tx['Amount']['issuer'] ?? null) !== $deposit->expectedToken->issuer_address) {
                    continue;
                }

                if (bccomp($tx['Amount']['value'], $deposit->expected_token_amount, 18) < 0) {
                    continue;
                }

                return [
                    'tx_hash' => $tx['hash'],
                    'sender'  => $tx['Account'],
                    'amount'  => $tx['Amount']['value'],
                    'asset'   => $decodedCurrency,
                ];
            }
        }

        return null;
    }

    /**
     * Decode XRPL currency code from hex to ASCII
     * Standard codes (3 chars like USD, XRP) are returned as-is
     * Non-standard codes (like XYIELD) are hex-encoded and need decoding
     */
    private function decodeCurrency(string $currency): string
    {
        if (strlen($currency) === 3) {
            return $currency;
        }

        // Non-standard codes are 40-character hex strings
        if (strlen($currency) === 40) {
            $hex = rtrim($currency, '0');
            $decoded = '';

            for ($i = 0; $i < strlen($hex); $i += 2) {
                $decoded .= chr(hexdec(substr($hex, $i, 2)));
            }
            return trim($decoded);
        }
        return $currency;
    }
}
