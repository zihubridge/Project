<?php

namespace App\Services\Ripple;

use Hardcastle\XRPL_PHP\Client\JsonRpcClient;
use Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\Payment;
use Hardcastle\XRPL_PHP\Wallet\Wallet as WalletWallet;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;

class XrplSwapService
{
    protected JsonRpcClient $client;
    protected string $hotWallet;
    protected string $hotWalletSeed;
    protected string $rpcUrl;

    public function __construct()
    {
        $this->rpcUrl = config('services.xrpl.rpc');
        $rpc = config('services.xrpl.rpc');
        $this->hotWallet = config('services.xrpl.wallet');
        $this->hotWalletSeed = config('services.xrpl.seed');

        if (!$rpc || !$this->hotWallet || !$this->hotWalletSeed) {
            throw new RuntimeException('XRPL configuration missing');
        }

        // Initialize the library client here
        $this->client = new JsonRpcClient($rpc);
    }

    /* -------------------------------------------------
     |  Low-level helpers
     |--------------------------------------------------*/

    protected function xrplPost(array $payload): array
    {
        $res = Http::timeout(20)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($this->rpcUrl, $payload);

        return $res->json() ?? [];
    }

    protected function pathFind(
        string $source,
        string $destination,
        array|string $destinationAmount,
        array|string|null $sendMax = null
    ): array {
        $params = [
            'source_account' => $source,
            'destination_account' => $destination,
            'destination_amount' => $destinationAmount,
        ];

        if ($sendMax !== null) {
            $params['send_max'] = $sendMax;
        }

        return $this->xrplPost([
            'method' => 'ripple_path_find',
            'params' => [$params],
        ]);
    }

    /* -------------------------------------------------
     |  Swap: XRP → XRPL Token
     |--------------------------------------------------*/
    public function xrpToToken(
        string $xrpAmount,
        string $tokenCurrency,
        string $tokenIssuer,
        string $minTokenOut
    ): array {
        try {
            $cur = $this->xrplCurrency($tokenCurrency);
            $sendMaxDrops = bcmul($xrpAmount, '1000000', 0);

            $tx = [
                'TransactionType' => 'Payment',
                'Account'         => $this->hotWallet,
                'Destination'     => $this->hotWallet,

                'Amount' => [
                    'currency' => $cur,
                    'issuer'   => $tokenIssuer,
                    'value'    => '999999999',
                ],

                'DeliverMin' => [
                    'currency' => $cur,
                    'issuer'   => $tokenIssuer,
                    'value'    => $minTokenOut,
                ],

                'SendMax' => $sendMaxDrops,
                'Flags'   => 0x00020000, // tfPartialPayment
            ];

            $wallet = WalletWallet::fromSeed($this->hotWalletSeed);

            $autofilled = $this->client->autofill($tx);

            $paymentTx = new Payment($autofilled);
            $signed    = $wallet->sign($paymentTx);

            $txBlob = $signed['tx_blob'] ?? null;
            if (empty($txBlob)) {
                Log::error('Local XRPL signing error: no tx_blob returned', ['signed' => $signed]);
                return ['ok' => false, 'error' => 'Signing failed', 'status' => 500, 'context' => $signed];
            }

            // Submit
            $submitRes = Http::post($this->rpcUrl, [
                'method' => 'submit',
                'params' => [['tx_blob' => $txBlob]],
            ]);

            if (! $submitRes->ok()) {
                Log::error('XRPL submit HTTP failed', ['body' => $submitRes->body()]);
                return ['ok' => false, 'error' => 'Transaction submit HTTP failed', 'status' => $submitRes->status()];
            }

            $submitJson   = $submitRes->json();

            $txHash = data_get($submitJson, 'result.tx_json.hash');
            if (!$txHash) {
                throw new RuntimeException('XRPL submission succeeded but hash missing');
            }

            // Poll for validation (up to 10 seconds)
            $maxAttempts = 20;
            $attempt = 0;
            $validated = false;
            $txDetails = null;

            while ($attempt < $maxAttempts && !$validated) {
                usleep(500000); // 0.5 seconds between attempts

                $txDetailsRes = Http::post($this->rpcUrl, [
                    'method' => 'tx',
                    'params' => [[
                        'transaction' => $txHash,
                        'binary' => false,
                    ]]
                ]);

                $txDetails = $txDetailsRes->json();
                $validated = data_get($txDetails, 'result.validated', false);

                $attempt++;
            }

            if (!$validated) {
                Log::error('Transaction not validated in time', ['hash' => $txHash]);
                throw new RuntimeException('Transaction submitted but not validated within timeout');
            }

            // Now safely extract delivered_amount
            $delivered = data_get($txDetails, 'result.meta.delivered_amount');

            if (!$delivered) {
                throw new RuntimeException('Delivered amount not found in validated transaction');
            }

            if (is_array($delivered)) {
                $amountOut = $delivered['value'];
            } else {
                $amountOut = bcdiv($delivered, '1000000', 18);
            }

            return [
                'ok'         => true,
                'tx_hash'    => $txHash,
                'amount_out' => (string) $amountOut,
            ];
        } catch (\Throwable $e) {
            Log::error('[XRPL XRP→TOKEN FAILED]', ['error' => $e->getMessage()]);
            return [
                'ok'      => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    private function xrplCurrency(string $currency): string
    {
        $currency = strtoupper(trim($currency));

        // 3-letter codes
        if (strlen($currency) === 3) {
            return $currency;
        }

        // Already hex (force uppercase)
        if (preg_match('/^[A-F0-9]{40}$/i', $currency)) {
            return strtoupper($currency);
        }

        // Encode ASCII → XRPL hex (UPPERCASE!)
        if (strlen($currency) > 3 && strlen($currency) <= 20) {
            return strtoupper(str_pad(bin2hex($currency), 40, '0'));
        }

        throw new InvalidArgumentException("Invalid XRPL currency: {$currency}");
    }

    /* -------------------------------------------------
     |  Swap: XRPL Token → XRP
     |--------------------------------------------------*/

    public function xrpTokenToXrp(
        string $tokenAmount,
        string $tokenCurrency,
        string $tokenIssuer,
        string $minXrpOut
    ): array {
        try {
            $cur = $this->xrplCurrency($tokenCurrency);

            // Calculate DeliverMin in drops (minimum XRP to receive)
            $minXrpDrops = bcmul($minXrpOut, '1000000', 0);
            if (bccomp($minXrpDrops, '100', 0) < 0) {
                $minXrpDrops = '100';
            }

            // Maximum XRP ceiling (10,000 XRP)
            $maxXrpDrops = '10000000000';

            $tx = [
                'TransactionType' => 'Payment',
                'Account' => $this->hotWallet,
                'Destination' => $this->hotWallet,
                'Amount' => $maxXrpDrops,
                'SendMax' => [
                    'currency' => $cur,
                    'issuer' => $tokenIssuer,
                    'value' => $this->xrplNormalizeAmount($tokenAmount),
                ],
                'DeliverMin' => $minXrpDrops,
                'Flags' => 131072, // tfPartialPayment (already correct)
            ];

            $wallet = WalletWallet::fromSeed($this->hotWalletSeed);

            $autofilled = $this->client->autofill($tx);

            $paymentTx = new Payment($autofilled);
            $signed = $wallet->sign($paymentTx);

            $txBlob = $signed['tx_blob'] ?? null;
            if (!$txBlob) {
                throw new RuntimeException('Signing failed');
            }

            $submitRes = Http::post($this->rpcUrl, [
                'method' => 'submit',
                'params' => [[
                    'tx_blob' => $txBlob
                ]]
            ]);

            $submitJson = $submitRes->json();

            $txHash = data_get($submitJson, 'result.tx_json.hash');
            if (!$txHash) {
                throw new RuntimeException('Missing tx hash');
            }

            // Poll for validation
            $validated = false;
            $txDetails = null;

            for ($i = 0; $i < 20; $i++) {
                usleep(500000); // 0.5 seconds

                $res = Http::post($this->rpcUrl, [
                    'method' => 'tx',
                    'params' => [[
                        'transaction' => $txHash,
                        'binary' => false
                    ]]
                ]);

                $txDetails = $res->json();

                if (data_get($txDetails, 'result.validated')) {
                    $validated = true;
                    break;
                }
            }

            if (!$validated) {
                throw new RuntimeException('Transaction not validated');
            }

            // Extract delivered_amount (XRP will be a string in drops)
            $delivered = data_get($txDetails, 'result.meta.delivered_amount');

            if (!is_string($delivered)) {
                throw new RuntimeException('Unexpected delivered amount format');
            }

            $amountOut = bcdiv($delivered, '1000000', 6);

            return [
                'ok' => true,
                'tx_hash' => $txHash,
                'amount_out' => $amountOut,
            ];
        } catch (\Throwable $e) {
            Log::error('[XRPL Token→XRP FAILED]', ['error' => $e->getMessage()]);
            return [
                'ok' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    private function xrplNormalizeAmount(string $amount): string
    {
        // remove trailing zeros
        $amount = rtrim(rtrim($amount, '0'), '.');

        // fallback
        if ($amount === '') {
            $amount = '0';
        }

        // limit precision to 15 significant digits
        return sprintf('%.15g', (float)$amount);
    }


    //check if xrp has been received in official ripple wallet from exchange or not
    public function checkXrpReceipt(string $destinationTag): array
    {
        // Fetch config values
        $rpcUrl = config('services.xrpl.rpc');
        $platformAddress = config('services.xrpl.wallet');

        try {
            // Query the XRPL for the last 10 transactions
            $response = Http::post($rpcUrl, [
                'method' => 'account_tx',
                'params' => [[
                    'account' => $platformAddress,
                    'ledger_index_min' => -1,
                    'forward' => false,
                    'limit' => 100,
                ]]
            ]);

            if ($response->failed()) {
                throw new \RuntimeException($response->body());
            }

            $transactions = $response->json()['result']['transactions'] ?? [];

            foreach ($transactions as $txData) {

                $tx = $txData['tx'];

                if (
                    ($tx['TransactionType'] ?? '') !== 'Payment' ||
                    (string)($tx['DestinationTag'] ?? '') !== (string)$destinationTag
                ) {
                    continue;
                }

                $delivered = $txData['meta']['delivered_amount'] ?? null;

                if (!is_string($delivered)) {
                    continue;
                }

                return [
                    'status' => 'success',
                    'tx_hash' => $tx['hash'],
                    'amount_received' => bcdiv($delivered, '1000000', 6),
                    'from' => $tx['Account'] ?? null,
                    'ledger_index' => $tx['ledger_index'] ?? null,
                    'message' => null,
                ];
            }

            return [
                'status' => 'pending',
                'message' => 'Payment not found',
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    public function sendXrpTokenToDestination(
        string $tokenAmount,
        string $tokenCurrency,
        string $tokenIssuer,
        string $destination
    ): array {
        try {
            $cur = $this->xrplCurrency($tokenCurrency);

            $tx = [
                'TransactionType' => 'Payment',
                'Account'         => $this->hotWallet,
                'Destination'     => $destination,
                'Amount'          => [
                    'currency' => $cur,
                    'issuer'   => $tokenIssuer,
                    'value'    => $tokenAmount,
                ],
            ];

            $wallet = WalletWallet::fromSeed($this->hotWalletSeed);

            $autofilled = $this->client->autofill($tx);
            $paymentTx = new Payment($autofilled);
            $signed    = $wallet->sign($paymentTx);

            $txBlob = $signed['tx_blob'] ?? null;
            if (!$txBlob) {
                throw new RuntimeException('Signing failed');
            }

            $submitRes = Http::post($this->rpcUrl, [
                'method' => 'submit',
                'params' => [['tx_blob' => $txBlob]],
            ]);

            $json = $submitRes->json();
            $engine = data_get($json, 'result.engine_result');
            $hash   = data_get($json, 'result.tx_json.hash');

            if ($engine !== 'tesSUCCESS') {
                throw new RuntimeException("XRPL send failed: {$engine}");
            }

            return [
                'ok'      => true,
                'tx_hash' => $hash,
            ];
        } catch (\Throwable $e) {
            Log::error('[XRPL SEND TOKEN FAILED]', ['error' => $e->getMessage()]);
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Sends native XRP to a specific address (ChangeNOW)
     * * @param string $toAddress The ChangeNOW payinAddress
     * @param string $amount XRP amount (e.g., "10.5")
     * @param string|null $destinationTag The ChangeNOW payinExtraId
     * @return string The transaction hash
     */
    public function sendXrpToExchange(string $toAddress, string $amount, ?string $destinationTag = null): string
    {
        try {
            // Convert XRP to Drops (e.g., "1" XRP becomes "1000000" Drops)
            $amountInDrops = bcmul($amount, '1000000', 0);

            if (bccomp($amountInDrops, '1', 0) <= 0) {
                throw new RuntimeException('Invalid XRP amount');
            }

            // Build the transaction array
            $tx = [
                'TransactionType' => 'Payment',
                'Account' => $this->hotWallet,
                'Destination' => $toAddress,
                'Amount' => $amountInDrops, // For native XRP, this is a string of drops
            ];

            // Add Destination Tag if provided by ChangeNOW
            if (!empty($destinationTag)) {
                $tx['DestinationTag'] = (int) $destinationTag;
            }


            $wallet = WalletWallet::fromSeed($this->hotWalletSeed);
            $autofilled = $this->client->autofill($tx);

            $paymentTx = new Payment($autofilled);
            $signed = $wallet->sign($paymentTx);

            $txBlob = $signed['tx_blob'] ?? null;

            if (!$txBlob) {
                throw new RuntimeException('Signing failed');
            }

            // Submit transaction
            $submitRes = Http::post($this->rpcUrl, [
                'method' => 'submit',
                'params' => [[
                    'tx_blob' => $txBlob
                ]]
            ]);

            $submitJson = $submitRes->json();

            $engineResult = data_get($submitJson, 'result.engine_result');

            if ($engineResult !== 'tesSUCCESS') {
                throw new RuntimeException(
                    data_get($submitJson, 'result.engine_result_message', 'Unknown submit error')
                );
            }

            $txHash = data_get($submitJson, 'result.tx_json.hash');

            if (!$txHash) {
                throw new RuntimeException('Missing transaction hash');
            }

            $validated = false;

            for ($i = 0; $i < 20; $i++) {

                usleep(500000);

                $res = Http::post($this->rpcUrl, [
                    'method' => 'tx',
                    'params' => [[
                        'transaction' => $txHash,
                        'binary' => false
                    ]]
                ]);

                $txDetails = $res->json();

                if (data_get($txDetails, 'result.validated')) {
                    $validated = true;
                    break;
                }
            }

            if (!$validated) {
                throw new RuntimeException('Transaction not validated');
            }

            return $txHash;
        } catch (\Throwable $e) {

            Log::error('[XRPL XRP→EXCHANGE FAILED]', [
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    public function refundToken(
        string $amount,
        string $currency,
        string $issuer,
        string $destination,
        string $memo
    ): array {
        try {
            $cur = $this->xrplCurrency($currency);

            $tx = [
                'TransactionType' => 'Payment',
                'Account'         => $this->hotWallet,
                'Destination'     => $destination,
                'Amount'          => [
                    'currency' => $cur,
                    'issuer'   => $issuer,
                    'value'    => $amount,
                ],
            ];

            if (!empty($memo)) {
                $memo = substr($memo, 0, 256);

                $tx['Memos'] = [
                    [
                        'Memo' => [
                            'MemoType' => strtoupper(bin2hex('text')),
                            'MemoData' => strtoupper(bin2hex($memo)),
                        ]
                    ]
                ];
            }

            $wallet = WalletWallet::fromSeed($this->hotWalletSeed);

            $autofilled = $this->client->autofill($tx);
            $paymentTx = new Payment($autofilled);
            $signed    = $wallet->sign($paymentTx);

            $txBlob = $signed['tx_blob'] ?? null;
            if (!$txBlob) {
                throw new RuntimeException('Signing failed');
            }

            $submitRes = Http::post($this->rpcUrl, [
                'method' => 'submit',
                'params' => [['tx_blob' => $txBlob]],
            ]);

            $json = $submitRes->json();
            $engine = data_get($json, 'result.engine_result');
            $hash   = data_get($json, 'result.tx_json.hash');

            if ($engine !== 'tesSUCCESS') {
                throw new RuntimeException("XRPL send failed: {$engine}");
            }

            return [
                'ok'      => true,
                'tx_hash' => $hash,
            ];
        } catch (\Throwable $e) {
            Log::error('[XRPL SEND TOKEN FAILED]', ['error' => $e->getMessage()]);
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }
}
