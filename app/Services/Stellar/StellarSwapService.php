<?php

namespace App\Services\Stellar;

use Illuminate\Support\Facades\Log;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\AssetTypeNative;
use Soneso\StellarSDK\AssetTypeCreditAlphanum4;
use Soneso\StellarSDK\AssetTypeCreditAlphanum12;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Memo;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\PathPaymentStrictSendOperation;
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
        $swapId
    ): array {
        $seed = config('services.stellar.seed');
        $kp = KeyPair::fromSeed($seed);
        $sourceAccountId = $kp->getAccountId();
        $destination = config('services.stellar.wallet');

        try {
            $server = $this->sdk;
            $sourceAccount = $server->requestAccount($sourceAccountId);

            //Initialize the Builder with the loaded account
            $builder = new TransactionBuilder($sourceAccount);

            // Add the Operation
            $op = new PathPaymentStrictSendOperation(
                $this->asset($tokenCode, $issuer), // Send Asset
                $amountIn,                        // Send Amount
                $destination,                     // Destination Address
                new AssetTypeNative(),            // Receive Asset (XLM)
                $minXlmOut,                       // Min Receive Amount
                []                                // Path (empty if direct)
            );
            $builder->addOperation($op);

            // 5. Build, Sign, and Submit
            $tx = $builder->build();
            $tx->sign($kp, $this->network);
            $response = $server->submitTransaction($tx);

            if ($response->isSuccessful()) {
                return [
                    'tx_hash' => $response->getHash(),
                    'min_out' => $minXlmOut,
                ];
            }

            // Handle Failure
            $extras = $response->getExtras();
            Log::error('Stellar Path Payment Failed', [
                'codes' => $extras ? $extras->getResultCodes() : 'unknown',
                'swap_id' => $memo
            ]);

            return [
                'status' => 'error',
                'message' => 'Transaction failed on-chain.',
                'error_codes' => $extras ? $extras->getResultCodes() : null
            ];
        } catch (\Throwable $e) {
            Log::error('Stellar Service Error: ' . $e->getMessage());
            return [
                'status' => 'error',
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
        $destination = config('services.stellar.wallet');

        $op = new PathPaymentStrictSendOperation(
            new AssetTypeNative(),
            $amountIn,
            $destination,
            $this->asset($tokenCode, $issuer),
            $minTokenOut,
            []
        );

        $hash = $this->submitTx([$op]);

        return [
            'tx_hash' => $hash,
            'min_out' => $minTokenOut,
        ];
    }

    public function sendXlmToExchange(string $toAddress, string $amount, string $memoId): string
    {
        $seed = config('services.stellar.seed');
        $kp = KeyPair::fromSeed($seed);
        $sourceAccount = $this->sdk->requestAccount($kp->getAccountId());

        // Build the transaction
        $builder = new TransactionBuilder($sourceAccount);

        // Add the Payment Operation
        $payment = (new PaymentOperationBuilder($toAddress, Asset::native(), $amount))->build();
        $builder->addOperation($payment);

        // Add the Memo
        // ChangeNOW uses MEMO_ID (numeric) for Stellar swaps
        $builder->addMemo(new Memo(Memo::MEMO_TYPE_ID, $memoId));

        $tx = $builder->build();
        $tx->sign($kp, $this->network);

        $response = $this->sdk->submitTransaction($tx);

        if (!$response->isSuccessful()) {
            throw new \RuntimeException("Failed to send XLM to ChangeNOW: " . json_encode($response->getExtras()->getResultCodes()));
        }

        return $response->getHash();
    }

    public function sendXlmTokenToDestination(array $operations, ?string $memo = null): string
    {
        $kp = config('services.stellar.seed');
        $accountId = $kp->getAccountId();

        $server = $this->sdk->getServer();
        $account = $server->accounts()->account($accountId);

        $builder = new TransactionBuilder($account);

        foreach ($operations as $op) {
            $builder->addOperation($op);
        }

        $builder->setTimeout(60);

        if ($memo) {
            $builder->addMemoText(substr($memo, 0, 28));
        }

        $tx = $builder->build();
        $tx->sign($kp, $this->network);

        $res = $server->submitTransaction($tx);

        return (string) $res->getHash();
    }

    /**
     * Polling function to check if ChangeNOW has sent XLM to our wallet.
     */
    public function checkXlmReceipt(string $memoId, string $expectedXlmAmount): array
    {
        try {
            // 1. Get the most recent payments for your platform wallet
            // We limit to 10-20 to keep the check fast
            $payments = $this->sdk->payments()
                ->forAccount(config('services.stellar.wallet'))
                ->order('desc')
                ->limit(20)
                ->execute();

            foreach ($payments->getRecords() as $payment) {
                // Only look for "payment" types and native XLM (type 'native')
                if ($payment->getType() !== 'payment' || $payment->getAssetType() !== 'native') {
                    continue;
                }

                // 2. To check the Memo, we must fetch the full Transaction for this payment
                // Payments in Stellar don't show Memos in the list; only Transactions do.
                $txHash = $payment->getTransactionHash();
                $transaction = $this->sdk->requestTransaction($txHash);

                // 3. Compare Memo and Amount
                // ChangeNOW uses MEMO_ID (numeric)
                $memoMatches = ((string)$transaction->getMemoValue() === (string)$memoId);

                // Stellar amounts are strings, but we use bccomp for precision safety
                $amountMatches = (bccomp($payment->getAmount(), $expectedXlmAmount, 7) === 0);

                if ($memoMatches && $amountMatches) {
                    return [
                        'received' => true,
                        'tx_hash'  => $txHash,
                        'amount'   => $payment->getAmount()
                    ];
                }
            }
        } catch (\Throwable $e) {
            Log::error("[STELLAR CHECK] Error polling for receipt: " . $e->getMessage());
        }

        return ['received' => false];
    }
}
