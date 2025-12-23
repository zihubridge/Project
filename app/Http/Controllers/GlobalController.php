<?php

namespace App\Http\Controllers;

use App\Models\Blockchain;
use App\Models\Token;
use App\Models\WalletType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\StellarSDK;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Http\Client\RequestException;

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
                'amount' => ['required', 'numeric', 'gt:0'],

                'from_blockchain' => ['required', 'integer'],
                'to_blockchain' => ['required', 'integer'],

                'from_asset_code' => ['required', 'string', 'max:64'],
                'from_issuer_address' => ['required', 'string', 'max:128'],

                'to_asset_code' => ['required', 'string', 'max:64'],
                'to_issuer_address' => ['required', 'string', 'max:128'],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status'  => 0,
                'message' => 'Validation error',
                'errors'  => $e->errors(),
            ], 422);
        }

        try {
            $amount = (string) ($data['amount'] ?? '0');

            if (!is_numeric($amount) || bccomp($amount, '0', 7) <= 0) {
                throw new \Exception("Invalid amount");
            }

            //Stellar to Ripple 
            if ($data['from_blockchain'] == 1 && $data['from_blockchain'] == 2) {
                try {

                    $token = Token::where('issuer_address', $data['from_issuer_address'])->first();

                    if (!$token) {
                        throw new \Exception("Token not found for issuer address");
                    }

                    $poolId = $token->pool_id;

                    if (!$token->pool_id) {
                        throw new \Exception("Pool ID missing for token: {$token->asset_code}");
                    }

                    $assetCode = $token->asset_code;
                    if (!$assetCode) {
                        throw new \Exception("Asset Code not found");
                    }
                    $issuerAddress = $token->issuer_address;
                    if (!$issuerAddress) {
                        throw new \Exception("Issuer Address not found");
                    }
                    $xlmQuote = $this->estimateXlmOutFromPool($poolId, $assetCode, $issuerAddress, $amount);

                    if (!$xlmQuote || empty($xlmQuote['estimated_xlm'])) {
                        throw new \RuntimeException('Could not estimate XLM output from Stellar pool.');
                    }

                    $estimatedXlm = (string) $xlmQuote['estimated_xlm'];

                    // Optional: ChangeNOW often expects a reasonable decimal format.
                    // Keep 7 decimals (XLM standard) or trim trailing zeros if you want.
                    $estimatedXlm = bcadd($estimatedXlm, '0', 7);

                    $xrp = $this->getChangeNowEstimatedAmount(
                        fromCurrency: 'xlm',
                        toCurrency: 'xrp',
                        fromNetwork: 'xlm',
                        toNetwork: 'xrp',
                        fromAmount: $estimatedXlm,
                        flow: 'fixed-rate',
                        type: 'direct',
                        useRateId: true
                    );

                    // ChangeNOW usually returns estimatedAmount for "to"
                    $estimatedXrp = (string)($xrpQuote['estimatedAmount'] ?? '0');

                    if (!is_numeric($estimatedXrp) || bccomp($estimatedXrp, '0', 6) <= 0) {
                        throw new \RuntimeException('Could not estimate XRP output from ChangeNOW.');
                    }

                    // Now quote XRP -> XRPL token
                    $xrplTokenQuote = $this->xrplQuoteXrpToToken(
                        xrpAmount: $estimatedXrp,
                        currency: $data['to_asset_code'],          // for XRPL this must be currency or hex
                        issuer: $data['to_issuer_address'],        // XRPL issuer address
                        isTestnet: $this->isTestnet
                    );
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
            //Ripple to Stellar
            else if ($data['from_blockchain'] == 2 && $data['to_blockchain'] == 1) {
                try {
                    $amount = (string) $data['amount'];

                    if (!is_numeric($amount) || bccomp($amount, '0', 6) <= 0) {
                        throw new \RuntimeException('Invalid amount');
                    }

                    /**
                     * STEP 1: XRPL TOKEN → XRP (orderbook quote)
                     */
                    $xrpQuote = $this->xrplQuoteTokenToXrp(
                        tokenAmount: $amount,
                        currency: $data['from_asset_code'],
                        issuer: $data['from_issuer_address'],
                        isTestnet: $this->isTestnet
                    );

                    if (
                        !$xrpQuote ||
                        empty($xrpQuote['xrp_out_estimated']) ||
                        bccomp($xrpQuote['xrp_out_estimated'], '0', 6) <= 0
                    ) {
                        throw new \RuntimeException('Could not estimate XRP from XRPL token');
                    }

                    $estimatedXrp = (string) $xrpQuote['xrp_out_estimated'];

                    /**
                     * STEP 2: XRP → XLM (ChangeNOW)
                     */
                    $xlmQuote = $this->getChangeNowEstimatedAmount(
                        fromCurrency: 'xrp',
                        toCurrency: 'xlm',
                        fromNetwork: 'xrp',
                        toNetwork: 'xlm',
                        fromAmount: $estimatedXrp,
                        flow: 'fixed-rate',
                        type: 'direct',
                        useRateId: true
                    );

                    $estimatedXlm = (string) ($xlmQuote['estimatedAmount'] ?? '0');

                    if (!is_numeric($estimatedXlm) || bccomp($estimatedXlm, '0', 7) <= 0) {
                        throw new \RuntimeException('Could not estimate XLM from ChangeNOW');
                    }

                    $estimatedXlm = bcadd($estimatedXlm, '0', 7);

                    /**
                     * STEP 3: XLM → STELLAR TOKEN (AMM pool)
                     */
                    $toToken = Token::where('blockchain_id', 1)
                        ->where('asset_code', $data['to_asset_code'])
                        ->where('issuer_address', $data['to_issuer_address'])
                        ->first();

                    if (!$toToken) {
                        throw new \RuntimeException('Destination Stellar token not supported');
                    }

                    if (empty($toToken->pool_id)) {
                        throw new \RuntimeException("Pool ID missing for token: {$toToken->asset_code}");
                    }

                    $stellarTokenQuote = $this->estimateTokenOutFromXlmPool(
                        poolId: $toToken->pool_id,
                        assetCode: $toToken->asset_code,
                        issuerAddress: $toToken->issuer_address,
                        xlmAmount: $estimatedXlm
                    );

                    if (
                        !$stellarTokenQuote ||
                        empty($stellarTokenQuote['estimated_token'])
                    ) {
                        throw new \RuntimeException('Could not estimate Stellar token output');
                    }

                    return response()->json([
                        'status' => 1,
                        'route' => 'xrpl_to_stellar',
                        'quotes' => [
                            'xrpl_token_to_xrp' => $xrpQuote,
                            'xrp_to_xlm' => $xlmQuote,
                            'xlm_to_stellar_token' => $stellarTokenQuote,
                        ],
                    ]);
                } catch (\Throwable $e) {
                    return response()->json([
                        'status' => 0,
                        'message' => $e->getMessage(),
                    ], 422);
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

    public function tokens(Request $request)
    {
        try {
            $data = $request->validate([
                'asset_code' => ['required', 'string', 'exists:blockchains,asset_code'],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation error',
                'errors'  => $e->errors(),
            ], 422);
        }
        try {
            $blockchainId = Blockchain::where('asset_code', $data['asset_code'])->value('id');

            $tokens = Token::where('blockchain_id', $blockchainId)
                ->orderBy('name')
                ->get();

            return response()->json([
                'status' => 'success',
                'tokens' => $tokens,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Failed to fetch tokens', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to load tokens. Please try again later.',
            ], 500);
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

    private function estimateXlmOutFromPool(
        string $poolId,
        string $assetCode,
        string $issuerAddress,
        string $amountIn,          // token amount in (string for precision)
        string $feeBps = '30'      // fee in basis points (e.g., 30 = 0.30%). Adjust if your pool differs.
    ): ?array {
        $base = $this->isTestnet
            ? 'https://horizon-testnet.stellar.org'
            : 'https://horizon.stellar.org';

        $url = $base . '/liquidity_pools/' . $poolId;

        try {
            $res = Http::timeout(10)->acceptJson()->get($url);

            if ($res->failed()) {
                Log::warning('[LP:estimate_XlmOut_FromPool] Horizon request failed', [
                    'status' => $res->status(),
                    'body'   => mb_substr($res->body(), 0, 800),
                ]);
                return null;
            }

            $data = $res->json();
            $rawReserves = $data['reserves'] ?? null;

            if (!is_array($rawReserves)) {
                Log::warning('[LP:estimate_XlmOut_FromPool] reserves missing or not an array');
                return null;
            }

            $xlmReserve = null;
            $tokenReserve = null;

            foreach ($rawReserves as $r) {
                $asset  = $r['asset']  ?? null;
                $amt    = $r['amount'] ?? null;

                if ($asset === 'native') {
                    $xlmReserve = (string) $amt;
                    continue;
                }

                if (!is_string($asset) || $amt === null) continue;

                $parts = explode(':', $asset);

                if (count($parts) === 2) {
                    [$code, $issuer] = $parts;
                } elseif (count($parts) === 3) {
                    [, $code, $issuer] = $parts;
                } else {
                    continue;
                }

                if ($code === $assetCode && $issuer === $issuerAddress) {
                    $tokenReserve = (string) $amt;
                }
            }

            if ($xlmReserve === null || $tokenReserve === null) {
                Log::warning('[LP:estimate_XlmOut_FromPool] Could not match both XLM and token reserves', [
                    'asset'  => $assetCode,
                    'issuer' => $issuerAddress,
                    'raw'    => $rawReserves,
                ]);
                return null;
            }

            // ---------- AMM math (use bc for precision) ----------
            // Stellar assets are 7 decimals; we’ll keep 7 in final output.
            $scale = 18; // internal calc precision

            if (!function_exists('bcadd')) {
                throw new \RuntimeException('BCMath extension is required for precise AMM estimation.');
            }

            // feeBps (e.g. 30) => multiplier = (10000 - feeBps) / 10000
            $feeMultiplier = bcdiv(bcsub('10000', (string)$feeBps, 0), '10000', $scale);

            $amountInWithFee = bcmul($amountIn, $feeMultiplier, $scale);

            // amountOut = (reserveOut * amountInWithFee) / (reserveIn + amountInWithFee)
            $numerator   = bcmul($xlmReserve, $amountInWithFee, $scale);
            $denominator = bcadd($tokenReserve, $amountInWithFee, $scale);

            if (bccomp($denominator, '0', $scale) === 0) {
                return null;
            }

            $xlmOut = bcdiv($numerator, $denominator, $scale);

            // Round/display to 7 decimals for XLM
            $xlmOut7 = bcadd($xlmOut, '0', 7);

            return [
                'pool_id'        => $poolId,
                'token'          => $assetCode,
                'issuer'         => $issuerAddress,
                'amount_in'      => $amountIn,
                'fee_bps'        => (int)$feeBps,
                'token_reserve'  => $tokenReserve,
                'xlm_reserve'    => $xlmReserve,
                'estimated_xlm'  => $xlmOut7,
            ];
        } catch (\Throwable $e) {
            Log::error('[LP:estimate_XlmOut_FromPool] Exception', [
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }

    private function xrplQuoteXrpToToken(
        string $xrpAmount,            // XRP amount as string, e.g. "25.5"
        string $currency,             // token currency, e.g. "USD" or 40-char HEX
        string $issuer,               // r..... issuer
        bool $isTestnet = false,
        int $limit = 50
    ): ?array {
        $rpc = $isTestnet
            ? 'https://s.altnet.rippletest.net:51234'
            : 'https://xrplcluster.com';

        // XRP in drops (1 XRP = 1,000,000 drops)
        $xrpDrops = bcmul($xrpAmount, '1000000', 0);

        $payload = [
            'method' => 'book_offers',
            'params' => [[
                // We are spending XRP
                'taker_gets' => 'XRP',

                // We want the issued token
                'taker_pays' => [
                    'currency' => $currency,
                    'issuer'   => $issuer,
                ],

                'limit' => $limit,
            ]]
        ];

        $res = Http::timeout(20)->acceptJson()->post($rpc, $payload);

        if ($res->failed()) {
            return null;
        }

        $offers = data_get($res->json(), 'result.offers', []);
        if (!is_array($offers) || count($offers) === 0) {
            return [
                'xrp_in' => $xrpAmount,
                'token_out_estimated' => '0',
                'reason' => 'no_liquidity',
            ];
        }

        // We simulate filling offers until we spend xrpDrops
        $remainingDrops = $xrpDrops;
        $tokenOut = '0';

        foreach ($offers as $offer) {
            if (bccomp($remainingDrops, '0', 0) <= 0) break;

            // offer taker_gets: XRP (drops)
            // offer taker_pays: issued token amount (string)
            $gets = $offer['TakerGets'] ?? null;
            $pays = $offer['TakerPays'] ?? null;

            if (!$gets || !$pays) continue;

            // TakerGets is XRP in drops (string or int)
            $offerXrpDrops = (string)$gets;

            // TakerPays is either array for IOU or string; for IOU it’s array {currency, issuer, value}
            $offerTokenValue = is_array($pays) ? (string)($pays['value'] ?? '0') : (string)$pays;

            if (bccomp($offerXrpDrops, '0', 0) <= 0 || bccomp($offerTokenValue, '0', 18) <= 0) {
                continue;
            }

            // If we can take whole offer
            if (bccomp($remainingDrops, $offerXrpDrops, 0) >= 0) {
                $remainingDrops = bcsub($remainingDrops, $offerXrpDrops, 0);
                $tokenOut = bcadd($tokenOut, $offerTokenValue, 18);
            } else {
                // Partial fill proportional
                // fraction = remainingDrops / offerXrpDrops
                $fraction = bcdiv($remainingDrops, $offerXrpDrops, 18);
                $partialToken = bcmul($offerTokenValue, $fraction, 18);

                $tokenOut = bcadd($tokenOut, $partialToken, 18);
                $remainingDrops = '0';
            }
        }

        return [
            'xrp_in' => $xrpAmount,
            'xrp_in_drops' => $xrpDrops,
            'token_out_estimated' => bcadd($tokenOut, '0', 8), // show 8 decimals (adjust per token)
            'unfilled_xrp_drops' => $remainingDrops,
        ];
    }

    private function getChangeNowEstimatedAmount(
        string $fromCurrency,
        string $toCurrency,
        ?string $fromNetwork = null,
        ?string $toNetwork = null,
        ?string $fromAmount = null,   // required if type=direct
        ?string $toAmount = null,     // required if type=reverse
        string $flow = 'standard',    // standard | fixed-rate
        string $type = 'direct',      // direct | reverse
        bool $useRateId = false,
        bool $isTopUp = false
    ): array {
        // Basic validation
        $type = strtolower($type);
        $flow = strtolower($flow);

        if (!in_array($type, ['direct', 'reverse'], true)) {
            throw new \InvalidArgumentException("Invalid type. Use 'direct' or 'reverse'.");
        }
        if (!in_array($flow, ['standard', 'fixed-rate'], true)) {
            throw new \InvalidArgumentException("Invalid flow. Use 'standard' or 'fixed-rate'.");
        }

        if ($type === 'direct' && (!$fromAmount || (float)$fromAmount <= 0)) {
            throw new \InvalidArgumentException("fromAmount is required and must be > 0 for type=direct.");
        }
        if ($type === 'reverse' && (!$toAmount || (float)$toAmount <= 0)) {
            throw new \InvalidArgumentException("toAmount is required and must be > 0 for type=reverse.");
        }

        $baseUrl = rtrim(config('services.changenow.base_url', env('CHANGENOW_BASE_URL', 'https://api.changenow.io')), '/');
        $apiKey  = config('services.changenow.api_key', env('CHANGENOW_API_KEY'));

        if (!$apiKey) {
            throw new \RuntimeException('CHANGENOW_API_KEY is missing.');
        }

        $params = [
            'fromCurrency' => strtolower($fromCurrency),
            'toCurrency'   => strtolower($toCurrency),
            'flow'         => $flow,
            'type'         => $type,
            'useRateId'    => $useRateId ? 'true' : 'false',
            'isTopUp'      => $isTopUp ? 'true' : 'false',
        ];

        // Amount parameters depend on direction
        if ($type === 'direct') {
            $params['fromAmount'] = (string)$fromAmount;
        } else {
            $params['toAmount'] = (string)$toAmount;
        }

        // Optional networks
        if (!empty($fromNetwork)) $params['fromNetwork'] = strtolower($fromNetwork);
        if (!empty($toNetwork))   $params['toNetwork']   = strtolower($toNetwork);

        try {
            $res = Http::timeout(20)
                ->acceptJson()
                ->withHeaders([
                    'x-changenow-api-key' => $apiKey,
                ])
                ->get($baseUrl . '/v2/exchange/estimated-amount', $params)
                ->throw();

            // Return full response so you can use rateId/validUntil/etc.
            return $res->json();
        } catch (RequestException $e) {
            // Useful error structure for logging/handling
            $body = optional($e->response)->json() ?? optional($e->response)->body();

            throw new \RuntimeException(
                'ChangeNOW estimate failed: ' . (is_string($body) ? $body : json_encode($body)),
                $e->getCode(),
                $e
            );
        }
    }
}
