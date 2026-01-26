<?php

namespace App\Services\Stellar;

use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\AssetTypeNative;
use Soneso\StellarSDK\AssetTypeCreditAlphanum4;
use Soneso\StellarSDK\AssetTypeCreditAlphanum12;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\PathPaymentStrictSendOperation;
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

    private function submitTx(array $operations, ?string $memo = null): string
    {
        $kp = env('STELLAR_SECRET_KEY');
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

    public function tokenToXlm(
        string $tokenCode,
        string $issuer,
        string $amountIn,
        string $minXlmOut,
        ?string $memo = null
    ): array {
        $kp = KeyPair::fromSeed(config('services.stellar.seed'));
        $hot = $kp->getAccountId();

        $op = new PathPaymentStrictSendOperation(
            $this->asset($tokenCode, $issuer),
            $amountIn,
            $hot,
            new AssetTypeNative(),
            $minXlmOut,
            []
        );

        $hash = $this->submitTx([$op], $memo);

        return [
            'tx_hash' => $hash,
            'min_out' => $minXlmOut,
        ];
    }

    public function xlmToToken(
        string $tokenCode,
        string $issuer,
        string $amountIn,
        string $minTokenOut,
        ?string $memo = null
    ): array {
        $kp = KeyPair::fromSeed(config('services.stellar.seed'));
        $hot = $kp->getAccountId();

        $op = new PathPaymentStrictSendOperation(
            new AssetTypeNative(),
            $amountIn,
            $hot,
            $this->asset($tokenCode, $issuer),
            $minTokenOut,
            []
        );

        $hash = $this->submitTx([$op], $memo);

        return [
            'tx_hash' => $hash,
            'min_out' => $minTokenOut,
        ];
    }
}
