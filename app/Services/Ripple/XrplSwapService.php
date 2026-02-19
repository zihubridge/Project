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

            Log::info('[XRPL XRP→TOKEN TX BUILT]', $tx);

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
            Log::info('submitJson', $submitJson);

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

            Log::info('txDetails', $txDetails);

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

    /* -------------------------------------------------
     |  Signing & Submit (stub)
     |--------------------------------------------------*/

    private function signAndSubmit(array $tx): array
    {
        Log::info('[XRPL] signAndSubmit started', [
            'tx' => $tx,
        ]);

        try {
            $wallet = WalletWallet::fromSeed($this->hotWalletSeed);

            Log::info('[XRPL] Wallet loaded', [
                'address' => method_exists($wallet, 'getAddress') ? $wallet->getAddress() : null,
            ]);

            $preparedTx = $this->client->autofill($tx);

            Log::info('[XRPL] Transaction autofilled', [
                'prepared_tx' => $preparedTx,
            ]);

            $signedTx = $wallet->sign($preparedTx);

            Log::info('[XRPL] Transaction signed', [
                'has_tx_blob' => isset($signedTx['tx_blob']),
                'tx_blob_length' => isset($signedTx['tx_blob']) ? strlen($signedTx['tx_blob']) : 0,
            ]);

            if (empty($signedTx['tx_blob'])) {
                Log::error('[XRPL] Signing failed: tx_blob missing', [
                    'signed_tx' => $signedTx,
                ]);

                return [
                    'ok' => false,
                    'reason' => 'signing_failed',
                    'message' => 'tx_blob missing after signing',
                ];
            }

            Log::info('[XRPL] Submitting transaction');

            $response = $this->client->submitAndWait($signedTx);
            $result = $response->getResult();

            Log::info('[XRPL] Submit response received', [
                'result' => $result,
            ]);

            $engineResult = $result['engine_result'] ?? null;

            if ($engineResult === 'tesSUCCESS') {
                Log::info('[XRPL] Transaction successful', [
                    'tx_hash' => data_get($result, 'tx_json.hash'),
                ]);

                return [
                    'ok' => true,
                    'tx_hash' => $result['tx_json']['hash'] ?? null,
                    'amount_out' => $result['tx_json']['Amount']['value'] ?? $result['tx_json']['Amount'],
                    'engine_result' => $engineResult
                ];
            }

            Log::error('[XRPL] Transaction rejected by network', [
                'engine_result' => $engineResult,
                'engine_message' => $result['engine_result_message'] ?? null,
                'full_result' => $result,
            ]);

            return [
                'ok' => false,
                'reason' => $engineResult,
                'message' => $result['engine_result_message'] ?? 'Unknown error'
            ];
        } catch (\Throwable $e) {
            Log::error("XRPL Critical Failure: " . $e->getMessage());
            return ['ok' => false, 'reason' => 'exception', 'message' => $e->getMessage()];
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

            Log::info('[XRPL Token→XRP] Starting swap', [
                'token_amount' => $tokenAmount,
                'token_currency' => $tokenCurrency,
                'token_currency_encoded' => $cur,
                'token_issuer' => $tokenIssuer,
                'min_xrp_out' => $minXrpOut,
            ]);

            // Calculate DeliverMin in drops (minimum XRP to receive)
            $minXrpDrops = bcmul($minXrpOut, '1000000', 0);
            if (bccomp($minXrpDrops, '100', 0) < 0) {
                $minXrpDrops = '100';
            }

            $maxXrpDrops = bcmul($minXrpOut, '1200000', 0);

            if (bccomp($maxXrpDrops, '100', 0) < 0) {
                $maxXrpDrops = '100'; 
            }

            $tx = [
                'TransactionType' => 'Payment',
                'Account' => $this->hotWallet,
                'Destination' => $this->hotWallet,
                'Amount' => (string) $maxXrpDrops, // Ensure it's a string
                'SendMax' => [
                    'currency' => $cur,
                    'issuer' => $tokenIssuer,
                    'value' => $this->xrplNormalizeAmount($tokenAmount),
                ],
                'DeliverMin' => (string) $minXrpDrops, // Ensure it's a string
                'Flags' => 131072, // tfPartialPayment (already correct)
            ];

            Log::info('[XRPL Token→XRP TX BUILT]', $tx);

            $wallet = WalletWallet::fromSeed($this->hotWalletSeed);

            $autofilled = $this->client->autofill($tx);

            $paymentTx = new Payment($autofilled);
            $signed = $wallet->sign($paymentTx);

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

            if (!$submitRes->ok()) {
                Log::error('XRPL submit HTTP failed', ['body' => $submitRes->body()]);
                return ['ok' => false, 'error' => 'Transaction submit HTTP failed', 'status' => $submitRes->status()];
            }

            $submitJson = $submitRes->json();
            Log::info('submitJson', $submitJson);

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
                usleep(500000); // 0.5 seconds

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

            Log::info('txDetails', $txDetails);

            // Extract delivered_amount (XRP will be a string in drops)
            $delivered = data_get($txDetails, 'result.meta.delivered_amount');

            if (!$delivered) {
                throw new RuntimeException('Delivered amount not found in validated transaction');
            }

            // For XRP, delivered_amount is a string of drops
            if (is_string($delivered)) {
                $amountOut = bcdiv($delivered, '1000000', 6); // Convert drops to XRP
            } else {
                // Shouldn't happen for XRP, but handle just in case
                $amountOut = $delivered;
            }

            return [
                'ok' => true,
                'tx_hash' => $txHash,
                'amount_out' => (string) $amountOut,
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


    //check if xrp has been received in official ripple wallet from change now or not
    public function checkXrpReceipt(string $destinationTag, float $expectedXrpAmount): array
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
                throw new \RuntimeException("XRPL RPC Error: " . $response->body());
            }

            $result = $response->json()['result'];
            $transactions = $result['transactions'] ?? [];

            foreach ($transactions as $txData) {
                $tx = $txData['tx'];

                // Check if it's a Payment and matches our Destination Tag
                if (($tx['TransactionType'] ?? '') === 'Payment' &&
                    isset($tx['DestinationTag']) && (string) $tx['DestinationTag'] === (string) $destinationTag
                ) {

                    // Convert expected XRP to drops (XRP Ledger uses integers for XRP)
                    $expectedDrops = bcmul($expectedXrpAmount, '1000000', 0);

                    // 0.5% tolerance
                    $toleranceDrops = bcdiv(
                        bcmul($expectedDrops, '5', 0),
                        '1000',
                        0
                    );

                    $minAcceptable = bcsub($expectedDrops, $toleranceDrops, 0);

                    // Use meta.delivered_amount ONLY
                    $delivered = $txData['meta']['delivered_amount'] ?? null;

                    // Must be XRP (drops string)
                    if (!is_string($delivered)) {
                        continue;
                    }

                    $deliveredDrops = $delivered;

                    if (bccomp($deliveredDrops, $minAcceptable, 0) >= 0) {
                        return [
                            'status'          => 'success',
                            'tx_hash'         => $tx['hash'],
                            'amount_received' => bcdiv($deliveredDrops, '1000000', 6),
                            'ledger_index'    => $tx['ledger_index'],
                        ];
                    }
                }
            }

            return ['status' => 'pending', 'message' => 'Payment not found in recent transactions.'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
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

            Log::info('[XRPL SEND TOKEN TX BUILT]', $tx);

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
        // Convert XRP to Drops (e.g., "1" XRP becomes "1000000" Drops)
        $amountInDrops = bcmul($amount, '1000000', 0);

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

        // Sign and Submit using your established helper
        $result = $this->signAndSubmit($tx);

        // Check for success
        if (($result['engine_result'] ?? '') !== 'tesSUCCESS') {
            throw new \RuntimeException(
                "Failed to send XRP to ChangeNOW: " . ($result['engine_result_message'] ?? 'Unknown Error')
            );
        }

        // Return the hash so we can save it to 'external_tx_id'
        return $result['tx_json']['hash'];
    }
}
