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
            $amountOut = data_get($submitJson, 'result.tx_json.Amount.value');

            if (!$txHash || !$amountOut) {
                throw new RuntimeException('XRPL submission succeeded but output amount missing');
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
        $cur = $this->xrplCurrency($tokenCurrency);
        $deliverMinDrops = bcmul($minXrpOut, '1000000', 0);

        // 1. Path Finding (Your existing logic)
        $paths = $this->pathFind(
            source: $this->hotWallet,
            destination: $this->hotWallet,
            destinationAmount: $deliverMinDrops,
            sendMax: [
                'currency' => $cur,
                'issuer' => $tokenIssuer,
                'value' => $tokenAmount,
            ]
        );

        $alts = data_get($paths, 'result.alternatives', []);
        if (!$alts) {
            throw new RuntimeException('XRPL path not found (Token → XRP)');
        }

        $best = $alts[0];

        // 2. Prepare the Transaction Data
        $tx = [
            'TransactionType' => 'Payment',
            'Account' => $this->hotWallet,
            'Destination' => $this->hotWallet,
            'Amount' => $best['destination_amount'],
            'SendMax' => [
                'currency' => $cur,
                'issuer' => $tokenIssuer,
                'value' => $tokenAmount,
            ],
            'DeliverMin' => $deliverMinDrops,
            'Paths' => $best['paths_computed'] ?? [],
            'Flags' => 0x00020000, // tfPartialPayment
        ];

        return $this->signAndSubmit($tx);
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
                    (isset($tx['DestinationTag']) && (string)$tx['DestinationTag'] === (string)$destinationTag)
                ) {

                    // Convert expected XRP to drops (XRP Ledger uses integers for XRP)
                    $expectedDrops = bcmul($expectedXrpAmount, '1000000', 0);

                    // IMPORTANT: Use delivered_amount from metadata to prevent "Partial Payment" exploits
                    $deliveredDrops = $txData['meta']['delivered_amount'] ?? $tx['Amount'];

                    if (bccomp($deliveredDrops, $expectedDrops, 0) >= 0) {
                        return [
                            'status' => 'success',
                            'tx_hash' => $tx['hash'],
                            'amount_received' => $expectedXrpAmount,
                            'ledger_index' => $tx['ledger_index']
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
        $cur = $this->xrplCurrency($tokenCurrency);

        // For tokens, Amount is an array: ['currency', 'issuer', 'value']
        $tx = [
            'TransactionType' => 'Payment',
            'Account' => $this->hotWallet,
            'Destination' => $destination,
            'Amount' => [
                'currency' => $cur,
                'issuer' => $tokenIssuer,
                'value' => $tokenAmount,
            ],
        ];

        // We reuse your clean signAndSubmit method
        return $this->signAndSubmit($tx);
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
