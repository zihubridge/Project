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
use Illuminate\Support\Facades\Crypt;

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

    public function check_token_balance(Request $request)
    {
        try {
            $data = $request->validate([
                'public_wallet' => ['required', 'string'],
                'amount' => ['required', 'numeric'],
                'blockchain' => ['required', 'numeric'],
                'issuer_address' => ['required', 'numeric'],
                'asset_code' => ['required', 'numeric'],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status'  => 0,
                'message' => 'Validation error',
                'errors'  => $e->errors(),
            ], 422);
        }

        try {
            $amount = $data['blockchain'] ?? null;

            //stellar
            if ($data['blockchain'] == 1) {
                try {

                    $token = Token::where('issuer_address', $data['issuer_address'])->first();

                    if (!$token) {
                        throw new \Exception("Token not found for issuer address");
                    }

                    $poolId = $token->pool_id;
                    $assetCode = $token->asset_code;
                    $issuerAddress = $token->issuer_address;
                    $xlm = $this->getStellarPoolReserves($poolId, $assetCode, $issuerAddress);
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


    public function checkStellarDepositAmount(string $publicKey, string $issuer, string $assetCode, ?float $minAmount = null): float|bool
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

            return $xlm;
        } catch (\Throwable $e) {
            Log::error('[LP:getPoolReserves] Exception', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            return null;
        }
    }
}
