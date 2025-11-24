<?php

namespace App\Http\Controllers;

use App\Models\Blockchain;
use App\Models\Token;
use App\Models\WalletType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use League\Config\Exception\ValidationException;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\StellarSDK;

class GlobalController extends Controller
{
    private $sdk, $network;
    protected string $rpcUrl;
    private bool $isTestnet;

    public function __construct()
    {
        $stellarEnv = env('VITE_STELLAR_ENVIRONMENT');

        if ($stellarEnv === 'public') {
            $this->sdk = StellarSDK::getPublicNetInstance();
            $this->network = Network::public();
            $this->isTestnet = false;
        } else {
            $this->sdk = StellarSDK::getTestNetInstance();
            $this->network = Network::testnet();
            $this->isTestnet = true;
        }
        // 'rpc_url'     => env('XRPL_RPC_URL', 'https://s.altnet.rippletest.net:51234'),
        // 'network'     => env('XRPL_NETWORK', 'testnet'),

        // // Your pool wallet
        // 'main_wallet' => env('XRPL_MAIN_WALLET', ''),  
        // 'main_wallet_seed' => env('XRPL_MAIN_WALLET_SEED', ''),   
        // 'dest_tag'  => env('XRPL_DEST_TAG', null),    
        // 'memo'        => env('XRUSH_MEMO', 'XRUSH STAKING'),
        // 'issuer'        => env('XRPL_ISSUER', ''),
        // 'tokenCode'        => env('XRPL_TOKEN_CODE', ''),

        $this->rpcUrl = env('XRPL_RPC_URL', 'https://s.altnet.rippletest.net:51234');
    }

    public function check_xlm_balance(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'public_wallet' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 0,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $balance = $this->getXlmBalance($request->public_wallet);

        return response()->json([
            'status'    => 1,
            'total_xlm' => (float) $balance,
        ]);
    }

    public function check_token_balance(Request $request)
    {
        try {
            $data = $request->validate([
                'public_wallet' => ['required', 'string'],
                'amount' => ['nullable', 'numeric'],
                'blockchain' => ['nullable', 'numeric'],
                'issuer_address' => ['nullable', 'numeric'],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status'  => 0,
                'message' => 'Validation error',
                'errors'  => $e->errors(),
            ], 422);
        }

        try {
            $amount = $data['amount'] ?? null;

            //stellar
            if ($data['amount'] == 1) {
                try {
                    $result = $this->getStellarTokenBalance($data['public_wallet'], $amount);

                    if ($amount !== null) {
                        return response()->json([
                            'status' => 1,
                            'hasMin' => (bool) $result,
                            'amount'    => (float) $amount,
                        ]);
                    }

                    return response()->json([
                        'status'    => 1,
                        'balance' => (float) $result,
                    ]);
                } catch (HorizonRequestException $e) {
                    if ($e->getStatusCode() === 404) {
                        return response()->json([
                            'status'      => 1,
                            'balance' => 0.0,
                        ]);
                    }
                    return response()->json([
                        'status'  => 0,
                        'message' => 'Horizon error',
                        'code'    => $e->getStatusCode(),
                    ], 502);
                }
            }
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 0,
                'message' => 'Unexpected error',
            ], 500);
        }
    }


    public function wallet_types()
    {
        try {
            $wallets = WalletType::where('status', 1)
                ->select('id', 'name', 'key', 'blockchain_id')
                ->get();

            return response()->json([
                'status' => 'success',
                'wallets' => $wallets,
            ]);
        } catch (\Throwable $e) {
            Log::error('wallet_types error', ['message' => $e->getMessage()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch wallet types.',
            ], 500);
        }
    }

    public function blockchains()
    {
        try {
            $blockchains = Blockchain::query()
                ->orderBy('name')
                ->get();

            return response()->json([
                'status'      => 'success',
                'blockchains' => $blockchains,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Failed to fetch blockchains', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            $payload = [
                'status'  => 'error',
                'message' => 'Failed to load blockchains. Please try again later.',
            ];

            if (config('app.debug')) {
                $payload['debug'] = $e->getMessage();
            }

            return response()->json($payload, 500);
        }
    }

    public function getXlmBalance(string $publicKey): float
    {
        try {
            $account = $this->sdk->requestAccount($publicKey);

            foreach ($account->getBalances() as $bal) {
                if ($bal->getAssetType() === 'native') {
                    return (float) $bal->getBalance();
                }
            }
            return 0.0;
        } catch (HorizonRequestException $e) {
            return $e->getStatusCode() == 404 ? 0.0 : throw $e;
        }
    }

    public function getStellarTokenBalance(string $publicKey, string $issuer, string $assetCode, ?float $minAmount = null): float|bool
    {
        if (!$issuer) {
            return $minAmount === null ? 0.0 : false;
        }

        try {
            $account = $this->sdk->requestAccount($publicKey);

            $expectedAssetType = strlen($assetCode) <= 4
                ? 'credit_alphanum4'
                : 'credit_alphanum12';

            foreach ($account->getBalances() as $bal) {
                if (
                    $bal->getAssetType()  === $expectedAssetType &&
                    $bal->getAssetCode()  === $assetCode &&
                    $bal->getAssetIssuer() === $issuer
                ) {
                    $balance = (float) $bal->getBalance();

                    return $minAmount === null ? $balance : $balance >= $minAmount;
                }
            }

            return $minAmount === null ? 0.0 : false;
        } catch (HorizonRequestException $e) {
            if ($e->getStatusCode() == 404) {
                return $minAmount === null ? 0.0 : false;
            }
            throw $e;
        }
    }

    public function getRippleTokenBalance(string $publicKey, string $issuer, string $assetCode, ?float $minAmount = null): float|bool
    {
        $data = $request->validate([
            'from' => ['required', 'string', 'regex:/^r[1-9A-HJ-NP-Za-km-z]{25,}$/'],
        ]);

        $res = \Http::post($this->rpcUrl, [
            'method' => 'account_lines',
            'params' => [['account' => $data['from'], 'ledger_index' => 'validated', 'peer' => $this->issuer]]
        ])->json();


        $lines = data_get($res, 'result.lines', []);
        foreach ($lines as $line) {
            if (strtoupper($line['currency'] ?? '') === $this->tokenCode) {
                return response()->json([
                    'balance' => $line['balance']
                ]);
            }
        }
        return false;
    }

    private function swappingTokenAmount(string $issueAddress, float $Amount): float|bool
    {
        if (empty($issueAddress)) {
            return false;
        }

        if ($Amount <= 0) {
            return false;
        }

        $token = Token::where('issuer_address', $issueAddress)->first();

        if (!$token) {
            throw new \Exception("Token not found for issuer address");
        }

        //Stellar
        if ($token->blockchain_id == 1) {
            $pollId = $token->pool_id;
            $assetCode = $token->asset_code;
            $issuerAddress = $token->issuer_address;
            $this->getStellarPoolReserves($pollId, $assetCode, $issuerAddress);
        }

        #Ripple
        elseif ($token->blockchain_id == 2) {
        }

        return false;
    }

    private function getStellarPoolReserves(string $poolId, string $assetCode, string $issuerAddress): ?array
    {
        $base = $this->isTestnet
            ? 'https://horizon-testnet.stellar.org'
            : 'https://horizon.stellar.org';

        $url = $base . '/liquidity_pools/' . $poolId;

        try {
            $res = Http::timeout(10)->acceptJson()->get($url);

            if ($res->failed()) {
                Log::warning('[LP:getPoolReserves] Horizon request failed', [
                    'status' => $res->status(),
                    'body'   => mb_substr($res->body(), 0, 800),
                ]);
                return null;
            }

            $data = $res->json();

            $rawReserves = $data['reserves'] ?? null;

            if (!is_array($rawReserves)) {
                Log::warning('[LP:getPoolReserves] reserves missing or not an array');
                return null;
            }

            $xlm = null;
            $assetAmount = null;

            foreach ($rawReserves as $r) {
                $asset  = $r['asset']  ?? null;
                $amount = $r['amount'] ?? null;

                if ($asset === 'native') {
                    $xlm = $amount;
                    continue;
                }

                if (!is_string($asset)) {
                    continue;
                }

                $parts = explode(':', $asset);

                if (count($parts) === 2) {
                    [$code, $issuer] = $parts;
                } elseif (count($parts) === 3) {
                    [, $code, $issuer] = $parts;
                } else {
                    continue;
                }

                if ($code === $assetCode && $issuer === $issuerAddress) {
                    $assetAmount = $amount;
                }
            }

            if ($xlm === null || $assetAmount === null) {
                Log::warning('[LP:getPoolReserves] Could not match both XLM and ' . $assetCode . ' in reserves', [
                    'asset'  => $assetCode,
                    'issuer' => $issuerAddress,
                    'raw'    => $rawReserves,
                ]);
                return null;
            }

            return ['xlm' => $xlm, 'tkg' => $assetAmount];
        } catch (\Throwable $e) {
            Log::error('[LP:getPoolReserves] Exception', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            return null;
        }
    }
}
