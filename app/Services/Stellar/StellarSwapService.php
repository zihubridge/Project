<?php

namespace App\Services\Stellar;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\AssetTypeNative;
use Soneso\StellarSDK\AssetTypeCreditAlphanum4;
use Soneso\StellarSDK\AssetTypeCreditAlphanum12;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Memo;
use Soneso\StellarSDK\MuxedAccount;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\PathPaymentStrictSendOperation;
use Soneso\StellarSDK\PathPaymentStrictSendOperationBuilder;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\StellarSDK;

class StellarSwapService
{
    private $sdk, $network;

    public function __construct()
    {
        $isPublic = config('services.stellar.horizon_url') === config('services.stellar.horizon_mainnet_url_check', 'https://horizon.stellar.org');

        if (env('ENVIRONMENT') === 'public') {
            $this->sdk = StellarSDK::getPublicNetInstance();
            $this->network = Network::public();
        } else {
            $this->sdk = StellarSDK::getTestNetInstance();
            $this->network = Network::testnet();
        }
    }

    private function asset(string $code, string $issuer): Asset
    {
        if ($code === 'XLM' || $code === 'native') {
            return new AssetTypeNative();
        }

        if (strlen($code) <= 4) {
            return new AssetTypeCreditAlphanum4($code, $issuer);
        }

        if (strlen($code) <= 12) {
            return new AssetTypeCreditAlphanum12($code, $issuer);
        }

        throw new \InvalidArgumentException("Invalid Stellar asset code");
    }

    public function xlmTokenToXlm(
        string $tokenCode,
        string $issuer,
        string $amountIn,
        string $minXlmOut,
        ?string $memo = null,
        int $swapId
    ): array {

        $seed = config('services.stellar.seed');
        $platformWallet = config('services.stellar.wallet');

        try {
            $kp = KeyPair::fromSeed($seed);
            $server = $this->sdk;
            $sourceAccount = $server->requestAccount($kp->getAccountId());

            Log::info('[Stellar Swap] Preparing path payment', [
                'swap_id' => $swapId,
                'asset' => $tokenCode . ':' . $issuer,
                'amount_in' => $amountIn,
            ]);

            $builder = new TransactionBuilder($sourceAccount, $this->network);

            $op = new PathPaymentStrictSendOperation(
                $this->asset($tokenCode, $issuer),
                $amountIn,
                MuxedAccount::fromAccountId($platformWallet),
                new AssetTypeNative(),
                $minXlmOut
            );

            $builder->addOperation($op);

            $tx = $builder->build();
            $tx->sign($kp, $this->network);

            $response = $server->submitTransaction($tx);

            if (!$response->isSuccessful()) {

                $extras = $response->getExtras();

                Log::error('[Stellar Swap] On-chain failure', [
                    'swap_id' => $swapId,
                    'codes' => $extras?->getResultCodes()
                ]);

                return [
                    'ok' => false,
                    'error' => 'onchain_failed',
                    'details' => $extras?->getResultCodes()
                ];
            }

            $txHash = $response->getHash();

            // Fetch real received amount
            $horizon = $this->horizonClient();
            $ops = $horizon->get("transactions/{$txHash}/operations");
            $data = json_decode($ops->getBody()->getContents(), true);

            $receivedXlm = null;

            foreach ($data['_embedded']['records'] ?? [] as $record) {
                if (
                    $record['type'] === 'path_payment_strict_send' &&
                    $record['asset_type'] === 'native'
                ) {
                    $receivedXlm = $record['amount'];
                    break;
                }
            }

            if (!$receivedXlm) {
                return [
                    'ok' => false,
                    'error' => 'no_native_received'
                ];
            }

            return [
                'ok' => true,
                'tx_hash' => $txHash,
                'amount_out' => $receivedXlm,
            ];
        } catch (\Throwable $e) {

            Log::error('[Stellar Swap] Exception', [
                'swap_id' => $swapId,
                'error' => $e->getMessage()
            ]);

            return [
                'ok' => false,
                'error' => 'exception',
                'message' => $e->getMessage()
            ];
        }
    }

    public function xlmToToken(
        string $tokenCode,
        string $issuer,
        string $amountIn,
        string $minTokenOut,
    ): array {
        $seed = config('services.stellar.seed');
        $sourceKp = KeyPair::fromSeed($seed);
        $myAddress = $sourceKp->getAccountId();

        // Fetch the account from Horizon to get the latest Sequence Number
        $sourceAccount = $this->sdk->requestAccount($myAddress);

        // Build the Path Payment Operation (Self-Swap)
        $sendAsset = Asset::native();
        $destAsset = Asset::createNonNativeAsset($tokenCode, $issuer);

        $op = (new PathPaymentStrictSendOperationBuilder(
            $sendAsset,     // Sending XLM
            $amountIn,      // Amount of XLM to spend
            $myAddress,     // Destination is the same wallet
            $destAsset,     // Receiving the Token
            $minTokenOut    // Minimum tokens to accept
        ))->build();

        // Wrap in a Transaction
        $builder = new TransactionBuilder($sourceAccount);
        $builder->addOperation($op);

        $transaction = $builder->build();

        // Sign the transaction
        $transaction->sign($sourceKp, $this->network);

        // Submit to Stellar Network
        $response = $this->sdk->submitTransaction($transaction);

        if ($response->isSuccessful()) {
            return [
                'ok' => true,
                'tx_hash' => $response->getHash(),
                'amount_out' => $minTokenOut,
            ];
        }

        // Handle Failure
        $error = 'swap_failed';
        if ($response->getExtras() && $response->getExtras()->getResultCodes()) {
            $error = $response->getExtras()->getResultCodes()->getTransactionResultCode();
        }

        return [
            'ok' => false,
            'error' => $error,
        ];
    }

    public function sendXlmToExchange(string $toAddress, string $amount, string $memoId): string
    {
        //7 decimal places
        $amount = number_format((float) $amount, 7, '.', '');
        $seed = config('services.stellar.seed');
        $kp = KeyPair::fromSeed($seed);
        $sourceAccount = $this->sdk->requestAccount($kp->getAccountId());

        // Build the transaction
        $builder = new TransactionBuilder($sourceAccount);

        // Add the Payment Operation
        $payment = (new PaymentOperationBuilder($toAddress, Asset::native(), $amount))->build();
        $builder->addOperation($payment);

        // Add the Memo
        $builder->addMemo(new Memo(Memo::MEMO_TYPE_TEXT, $memoId));

        $tx = $builder->build();
        $tx->sign($kp, $this->network);

        $response = $this->sdk->submitTransaction($tx);

        if (!$response->isSuccessful()) {
            throw new \RuntimeException("Failed to send XLM to ChangeNOW: " . json_encode($response->getExtras()->getResultCodes()));
        }

        return $response->getHash();
    }

    public function sendXlmTokenToDestination(
        string $amount,
        string $assetCode,
        string $issuer,
        string $destination,
        ?string $memoText = null
    ): array {

        try {

            // -------------------------------------------------
            // Prepare wallet
            // -------------------------------------------------

            $amount = number_format((float) $amount, 7, '.', '');
            $kp = KeyPair::fromSeed(config('services.stellar.seed'));
            $sourceAccount = $this->sdk->requestAccount($kp->getAccountId());

            // -------------------------------------------------
            // Build asset
            // -------------------------------------------------
            $asset = $this->asset($assetCode, $issuer);

            // -------------------------------------------------
            // Build payment operation
            // -------------------------------------------------

            $builder = new TransactionBuilder($sourceAccount);
            $payment = (new PaymentOperationBuilder($destination, $asset, $amount))->build();

            if ($memoText) {
                $builder->addMemo(
                    Memo::text(substr($memoText, 0, 28))
                );
            }

            // -------------------------------------------------
            // Build and Sign
            // -------------------------------------------------
            $builder->addOperation($payment);

            $tx = $builder->build();
            $tx->sign($kp, $this->network);

            // -------------------------------------------------
            // Submit
            // -------------------------------------------------
            $response = $this->sdk->submitTransaction($tx);

            if ($response->isSuccessful()) {

                $hash = $response->getHash();

                return [
                    'ok' => true,
                    'tx_hash' => $hash,
                ];
            }

            // -------------------------------------------------
            // Extract error
            // -------------------------------------------------
            $error = 'unknown_stellar_error';

            if (
                $response->getExtras() &&
                $response->getExtras()->getResultCodes()
            ) {
                $error = $response
                    ->getExtras()
                    ->getResultCodes()
                    ->getTransactionResultCode();
            }

            Log::error('[STELLAR] Token payout failed', [
                'error' => $error,
            ]);

            return [
                'ok' => false,
                'message' => $error,
            ];
        } catch (\Throwable $e) {

            Log::error('[STELLAR] Exception while sending token', [
                'message' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Polling function to check if ChangeNOW has sent XLM to our wallet.
     */
    public function checkXlmReceipt(string $memoId): array
    {
        try {
            $paymentsResponse = $this->sdk->payments()
                ->forAccount(config('services.stellar.wallet'))
                ->order('desc')
                ->limit(50)
                ->execute();

            foreach ($paymentsResponse->getOperations() as $payment) {

                // only normal payments
                if (
                    !$payment instanceof
                        \Soneso\StellarSDK\Responses\Operations\PaymentOperationResponse
                ) {
                    continue;
                }

                $txHash = $payment->getTransactionHash();
                $transaction = $this->sdk->requestTransaction($txHash);

                $memo = $transaction->getMemo();
                $actualMemoValue = '';

                if ($memo) {
                    $actualMemoValue = trim((string)$memo->valueAsString());
                }

                $memoMatches =
                    $actualMemoValue === trim((string)$memoId);

                if ($memoMatches) {
                    return [
                        'received' => true,
                        'tx_hash' => $txHash,
                        'amount_received' => $payment->getAmount(),
                        'from' => $payment->getFrom(),
                    ];
                }
            }
        } catch (\Throwable $e) {
            Log::error("[STELLAR CHECK] Receipt verification failed: " . $e->getMessage());
        }

        return ['received' => false];
    }

    private function horizonClient(): Client
    {
        return new Client([
            'base_uri' => rtrim(config('services.stellar.horizon_url'), '/') . '/',
            'timeout'  => 10,
        ]);
    }
}
