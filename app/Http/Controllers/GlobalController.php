<?php

namespace App\Http\Controllers;

use App\Models\Blockchain;
use App\Models\Swap;
use App\Models\Token;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\StellarSDK;
use Illuminate\Http\Client\RequestException;

use function Illuminate\Log\log;

class GlobalController extends Controller
{
    private bool $isTestnet;
    private $sdk, $network;
    protected string $rpcUrl, $stellarUrl;

    public function __construct()
    {
        $this->isTestnet = env('ENVIRONMENT') !== 'public';

        // Stellar
        $this->sdk        = $this->isTestnet ? StellarSDK::getTestNetInstance() : StellarSDK::getPublicNetInstance();
        $this->network    = $this->isTestnet ? Network::testnet() : Network::public();
        $this->stellarUrl = config('services.stellar.horizon_url');

        // Ripple
        $this->rpcUrl          = config('services.xrpl.rpc');
    }

    public function tokenSwappingAmount(Request $request)
    {
        try {
            $data = $request->validate([
                'amount' => ['required', 'numeric', 'gt:0'],

                'from_blockchain' => ['required'],
                'to_blockchain' => ['required'],

                'from_asset_code' => ['required', 'string', 'max:64'],
                'from_issuer_address' => ['required', 'string', 'max:128'],

                'to_asset_code' => ['required', 'string', 'max:64'],
                'to_issuer_address' => ['required', 'string', 'max:128'],
            ]);

            $amount = (string)$data['amount'];

            if (!is_numeric($amount) || bccomp($amount, '0', 7) <= 0) {
                return response()->json([
                    'status' => 0,
                    'message' => 'Invalid amount'
                ], 422);
            }

            if ($data['from_blockchain'] === $data['to_blockchain']) {
                return response()->json([
                    'status' => 0,
                    'message' => 'Same-chain swap is not allowed'
                ], 422);
            }

            if ($data['from_blockchain'] === 'xlm' && $data['to_blockchain'] === 'xrp') {
                return $this->estimateXlmToXrp($data, $amount);
            }

            if ($data['from_blockchain'] === 'xrp' && $data['to_blockchain'] === 'xlm') {
                return $this->estimateXrpToXlm($data, $amount);
            }

            return response()->json([
                'status' => 0,
                'message' => 'Unsupported swap pair'
            ], 422);
        } catch (ValidationException $e) {

            return response()->json([
                'status'  => 0,
                'message' => 'Validation error',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {

            return response()->json([
                'status'  => 0,
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | XLM → XRP
    |--------------------------------------------------------------------------
    */
    private function estimateXlmToXrp(array $data, string $amount)
    {
        try {

            $token = $this->getTokenOrFail($data['from_issuer_address']);

            if (!$token->pool_id) {
                throw new \Exception("Pool ID missing for token: {$token->asset_code}");
            }

            // Step 1: Token → XLM
            $xlmQuote = $this->estimateXlmOutFromPool(
                $token->pool_id,
                $token->asset_code,
                $token->issuer_address,
                $amount
            );

            $estimatedXlm = (string)($xlmQuote['estimated_xlm'] ?? '0');

            if (bccomp($estimatedXlm, '0', 7) <= 0) {
                throw new \RuntimeException('Could not estimate XLM output from Stellar pool.');
            }

            $estimatedXlm = bcadd($estimatedXlm, '0', 7);

            // Step 2: XLM → XRP (ChangeNOW)
            $xrpQuote = $this->getChangeNowEstimatedAmount(
                fromCurrency: 'xlm',
                toCurrency: 'xrp',
                fromNetwork: 'xlm',
                toNetwork: 'xrp',
                fromAmount: $estimatedXlm,
                flow: 'fixed-rate',
                type: 'direct',
                useRateId: true
            );

            $estimatedXrp = (string)($xrpQuote['toAmount'] ?? '0');

            if (bccomp($estimatedXrp, '0', 6) <= 0) {
                throw new \RuntimeException('Could not estimate XRP output.');
            }

            // Now quote XRP -> XRPL toke
            $xrplTokenQuote = $this->xrpToXRPToken(
                xrpAmount: $estimatedXrp,
                currency: $data['to_asset_code'],
                issuer: $data['to_issuer_address'],
                isTestnet: $this->isTestnet
            );

            $tokenOut = $xrplTokenQuote['token_out_estimated'] ?? null;

            if (!$tokenOut) {
                return response()->json([
                    'status' => 0,
                    'message' => 'Could not estimate XRPL token output (no liquidity).'
                ], 422);
            }

            return response()->json([
                'status' => 1,
                'estimated_amount' => (string)$tokenOut,
                'meta' => [
                    'estimated_xlm' => $estimatedXlm,
                    'estimated_xrp' => bcadd($estimatedXrp, '0', 6),
                ],
            ]);
        } catch (HorizonRequestException $e) {

            return response()->json([
                'status'  => 0,
                'message' => 'Horizon error',
                'code'    => $e->getStatusCode(),
            ], 502);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | XRP → XLM
    |--------------------------------------------------------------------------
    */
    private function estimateXrpToXlm(array $data, string $amount)
    {
        try {

            $token = $this->getTokenOrFail($data['from_issuer_address']);

            // Check ChangeNOW limits
            $limits = $this->getChangeNowMinMaxAmount(
                fromCurrency: 'xrp',
                toCurrency: 'xlm',
                fromNetwork: 'xrp',
                toNetwork: 'xlm',
                flow: 'standard'
            );

            log('limit', $limits);

            $minXrpRequired = $limits['minAmount'];

            // Minimum token needed
            $minQuote = $this->getMinimumTokenForXrp(
                tokenCurrency: $token->asset_code,
                tokenIssuer: $token->issuer_address,
                tokenAmount: $amount,
                targetXrp: $minXrpRequired,
                isTestnet: $this->isTestnet
            );

            log('minQuote', $minQuote);

            if (!($minQuote['is_enough'] ?? false)) {

                $minTokenRequired = $this->findMinimumTokenAmountForXrp(
                    currency: $token->asset_code,
                    issuer: $token->issuer_address,
                    targetXrp: $minQuote['target_xrp'],
                    currentAmount: $amount,
                    currentXrpOut: $minQuote['xrp_out'],
                    isTestnet: $this->isTestnet
                );

                return response()->json([
                    'status' => 0,
                    'message' => "Minimum amount required is {$minTokenRequired} {$token->asset_code}",
                    'min_amount' => $minTokenRequired,
                    'your_amount' => $amount,
                    'estimated_xrp' => $minQuote['xrp_out'],
                    'required_xrp' => $minQuote['target_xrp'],
                ], 400);
            }

            // Token → XRP
            $xrpQuote = $this->xrpTokenToXrp(
                tokenAmount: $amount,
                currency: $token->asset_code,
                issuer: $token->issuer_address,
                isTestnet: $this->isTestnet
            );

            $xrpOut = $xrpQuote['xrp_out_estimated'] ?? '0';

            // XRP → XLM
            $xlmQuote = $this->getChangeNowEstimatedAmount(
                fromCurrency: 'xrp',
                toCurrency: 'xlm',
                fromNetwork: 'xrp',
                toNetwork: 'xlm',
                fromAmount: $xrpOut,
                flow: 'fixed-rate',
                type: 'direct',
                useRateId: true
            );

            $estimatedXlm = (string)($xlmQuote['toAmount'] ?? '0');

            if (bccomp($estimatedXlm, '0', 7) <= 0) {
                throw new \RuntimeException('Could not estimate XLM output.');
            }

            // XLM → Stellar Token
            $xlmToken = $this->getTokenOrFail($data['to_issuer_address']);

            if (!$xlmToken->pool_id) {
                throw new \Exception("Pool ID missing for token: {$xlmToken->asset_code}");
            }

            $tokenQuote = $this->xlmToXlmToken(
                poolId: $xlmToken->pool_id,
                assetCode: $xlmToken->asset_code,
                issuerAddress: $xlmToken->issuer_address,
                xlmAmountIn: $estimatedXlm
            );

            $tokenOut = $tokenQuote['estimated_token'] ?? '0';

            if (bccomp($tokenOut, '0', 7) <= 0) {
                throw new \RuntimeException('Could not estimate token output.');
            }

            return response()->json([
                'status' => 1,
                'estimated_amount' => $tokenOut,
            ]);
        } catch (HorizonRequestException $e) {
            return response()->json([
                'status'  => 0,
                'message' => 'Horizon error',
                'code'    => $e->getStatusCode(),
            ], 502);
        }
    }

    private function getTokenOrFail(string $issuerAddress): Token
    {
        $token = Token::where('issuer_address', $issuerAddress)->first();

        if (!$token) {
            throw new \Exception("Token not found for issuer address");
        }

        return $token;
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

        $url = $this->stellarUrl . '/liquidity_pools/' . $poolId;

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
                'estimated_xlm'  => $xlmOut7,
            ];
        } catch (\Throwable $e) {
            Log::error('[LP:estimate_XlmOut_FromPool] Exception', [
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }

    private function xrpToXRPToken(
        string $xrpAmount,            // XRP amount as string, e.g. "25.5"
        string $currency,             // token currency, e.g. "USD" or 40-char HEX
        string $issuer,               // r..... issuer
        bool $isTestnet = false,
    ): ?array {

        $cur = $this->xrplCurrency($currency);

        $payload = [
            'jsonrpc' => '2.0',
            'id'      => 1,
            'method'  => 'amm_info',
            'params'  => [[
                'asset'  => ['currency' => 'XRP'],
                'asset2' => ['currency' => $cur, 'issuer' => $issuer],
            ]]
        ];

        $res = Http::timeout(20)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($this->rpcUrl, $payload);


        if ($res->failed()) {
            return null;
        }

        $amm = data_get($res->json(), 'result.amm');
        if (!$amm) {
            return [
                'xrp_in' => $xrpAmount,
                'token_out_estimated' => '0',
                'reason' => 'amm_not_found',
            ];
        }

        $amount  = $amm['amount']  ?? null;
        $amount2 = $amm['amount2'] ?? null;

        if ($amount === null || $amount2 === null) {
            return null;
        }

        // We requested asset=XRP, so amount is expected to be XRP in drops (string).
        $xrpReserveDrops = is_string($amount) ? $amount : null;
        $tokenReserve    = is_array($amount2) ? (string)($amount2['value'] ?? '0') : '0';

        if ($xrpReserveDrops === null || !ctype_digit($xrpReserveDrops)) {
            // Some servers might return swapped ordering; handle safely:
            if (is_string($amount2) && ctype_digit($amount2) && is_array($amount)) {
                $xrpReserveDrops = $amount2;
                $tokenReserve    = (string)($amount['value'] ?? '0');
            } else {
                return null;
            }
        }

        if (bccomp($tokenReserve, '0', 18) <= 0) {
            return [
                'xrp_in' => $xrpAmount,
                'token_out_estimated' => '0',
                'reason' => 'no_liquidity',
            ];
        }

        // Convert XRP amounts to XRP units (not drops) so math aligns with token decimals
        $scale = 18;

        $xrpReserve = bcdiv($xrpReserveDrops, '1000000', $scale);   // drops -> XRP
        $xrpIn      = bcadd($xrpAmount, '0', $scale);

        if (bccomp($xrpIn, '0', $scale) <= 0) {
            return [
                'xrp_in' => $xrpAmount,
                'token_out_estimated' => '0',
                'reason' => 'invalid_xrp_amount',
            ];
        }

        // trading_fee units: 1/100,000 (per xrpl.org)
        $feeUnits = (string)($amm['trading_fee'] ?? 0); // e.g. 600 means 0.6%
        $feeMultiplier = bcdiv(bcsub('100000', $feeUnits, 0), '100000', $scale);

        // amountInWithFee = xrpIn * feeMultiplier
        $xrpInWithFee = bcmul($xrpIn, $feeMultiplier, $scale);

        // constant product: out = (tokenReserve * xrpInWithFee) / (xrpReserve + xrpInWithFee)
        $numerator   = bcmul($tokenReserve, $xrpInWithFee, $scale);
        $denominator = bcadd($xrpReserve, $xrpInWithFee, $scale);

        if (bccomp($denominator, '0', $scale) === 0) {
            return null;
        }

        $tokenOut = bcdiv($numerator, $denominator, $scale);

        return [
            'token_out_estimated' => bcadd($tokenOut, '0', 3), // display precision
        ];
    }

    private function xrplCurrency(string $currency): string
    {
        $currency = strtoupper(trim($currency));

        // 3-char currency codes are allowed as-is
        if (strlen($currency) === 3) {
            return $currency;
        }

        // Otherwise must be 40-char HEX (ASCII bytes padded with 00)
        $hex = strtoupper(bin2hex($currency));
        return str_pad($hex, 40, '0', STR_PAD_RIGHT);
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
                ->get($baseUrl . '/v2/exchange/estimated-amount', $params);

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

    private function getChangeNowMinMaxAmount(
        string $fromCurrency,
        string $toCurrency,
        ?string $fromNetwork = null,
        ?string $toNetwork = null,
        string $flow = 'standard'
    ): array {
        $baseUrl = rtrim(config('services.changenow.base_url', 'https://api.changenow.io'), '/');
        $apiKey = config('services.changenow.api_key');

        if (!$apiKey) {
            throw new \RuntimeException('CHANGENOW_API_KEY is missing.');
        }

        $params = [
            'fromCurrency' => strtolower($fromCurrency),
            'toCurrency' => strtolower($toCurrency),
            'flow' => $flow,
        ];

        if ($fromNetwork) $params['fromNetwork'] = strtolower($fromNetwork);
        if ($toNetwork) $params['toNetwork'] = strtolower($toNetwork);

        try {
            $res = Http::timeout(20)
                ->acceptJson()
                ->withHeaders(['x-changenow-api-key' => $apiKey])
                ->get($baseUrl . '/v2/exchange/range', $params);

            return $res->json();
        } catch (\Exception $e) {
            Log::error('ChangeNow min/max fetch failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    private function xrpTokenToXrp(
        string $tokenAmount,
        string $currency,
        string $issuer,
        bool $isTestnet = false
    ): ?array {
        if (!function_exists('bcadd')) {
            throw new \RuntimeException('BCMath extension is required.');
        }

        $cur = $this->xrplCurrency($currency);

        // Query AMM reserves for XRP <-> Token
        $payload = [
            'jsonrpc' => '2.0',
            'id'      => 1,
            'method'  => 'amm_info',
            'params'  => [[
                'asset'  => ['currency' => 'XRP'],
                'asset2' => ['currency' => $cur, 'issuer' => $issuer],
            ]]
        ];

        $res = Http::timeout(20)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($this->rpcUrl, $payload);

        if ($res->failed()) {
            return null;
        }

        $amm = data_get($res->json(), 'result.amm');
        if (!$amm) {
            return [
                'token_in' => $tokenAmount,
                'xrp_out_estimated' => '0',
                'reason' => 'amm_not_found',
            ];
        }

        $amount  = $amm['amount']  ?? null;
        $amount2 = $amm['amount2'] ?? null;

        if ($amount === null || $amount2 === null) {
            return null;
        }

        // CRITICAL FIX: Determine which is XRP and which is token
        // XRP is always a string of drops, tokens are always objects with 'value'
        $xrpReserveDrops = null;
        $tokenReserve = null;

        if (is_string($amount) && ctype_digit($amount)) {
            // amount is XRP (drops)
            $xrpReserveDrops = $amount;
            $tokenReserve = is_array($amount2) ? (string)($amount2['value'] ?? '0') : '0';
        } elseif (is_string($amount2) && ctype_digit($amount2)) {
            // amount2 is XRP (drops), amount is token
            $xrpReserveDrops = $amount2;
            $tokenReserve = is_array($amount) ? (string)($amount['value'] ?? '0') : '0';
        } else {
            Log::error('[xrpTokenToXrp] Could not determine reserve types', [
                'amount' => $amount,
                'amount2' => $amount2,
            ]);
            return null;
        }

        if (bccomp($tokenReserve, '0', 18) <= 0 || bccomp($xrpReserveDrops, '0', 0) <= 0) {
            return [
                'token_in' => $tokenAmount,
                'xrp_out_estimated' => '0',
                'reason' => 'no_liquidity',
            ];
        }

        $scale = 18;

        // Convert XRP reserve to XRP units (not drops)
        $xrpReserve = bcdiv($xrpReserveDrops, '1000000', $scale);

        // Token input
        $tokenIn = bcadd($tokenAmount, '0', $scale);
        if (bccomp($tokenIn, '0', $scale) <= 0) {
            return [
                'token_in' => $tokenAmount,
                'xrp_out_estimated' => '0',
                'reason' => 'invalid_token_amount',
            ];
        }

        // trading_fee units: 1/100,000
        $feeUnits = (string)($amm['trading_fee'] ?? 0);
        $feeMultiplier = bcdiv(bcsub('100000', $feeUnits, 0), '100000', $scale);

        // Apply fee on input
        $tokenInWithFee = bcmul($tokenIn, $feeMultiplier, $scale);

        // Constant product:
        // outXrp = (xrpReserve * tokenInWithFee) / (tokenReserve + tokenInWithFee)
        $numerator   = bcmul($xrpReserve, $tokenInWithFee, $scale);
        $denominator = bcadd($tokenReserve, $tokenInWithFee, $scale);

        if (bccomp($denominator, '0', $scale) === 0) {
            return null;
        }

        $xrpOut = bcdiv($numerator, $denominator, $scale);

        // Display to 6 decimals for XRP
        return [
            'xrp_out_estimated' => bcadd($xrpOut, '0', 6),
            'fee_units' => (int)$feeUnits,
        ];
    }

    private function xlmToXlmToken(
        string $poolId,
        string $assetCode,
        string $issuerAddress,
        string $xlmAmountIn,      // XLM amount in (string for precision)
        string $feeBps = '30'     // fee in basis points (e.g. 30 = 0.30%)
    ): ?array {

        $url = $this->stellarUrl . '/liquidity_pools/' . $poolId;

        try {
            $res = Http::timeout(10)->acceptJson()->get($url);

            if ($res->failed()) {
                Log::warning('[LP:estimate_TokenOut_FromPool] Horizon request failed', [
                    'status' => $res->status(),
                    'body'   => mb_substr($res->body(), 0, 800),
                ]);
                return null;
            }

            $data = $res->json();
            $rawReserves = $data['reserves'] ?? null;

            if (!is_array($rawReserves)) {
                Log::warning('[LP:estimate_TokenOut_FromPool] reserves missing or not an array');
                return null;
            }

            $xlmReserve = null;
            $tokenReserve = null;

            foreach ($rawReserves as $r) {
                $asset  = $r['asset']  ?? null;
                $amt    = $r['amount'] ?? null;

                if ($asset === 'native') {
                    $xlmReserve = (string)$amt;
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
                    $tokenReserve = (string)$amt;
                }
            }

            if ($xlmReserve === null || $tokenReserve === null) {
                Log::warning('[LP:estimate_TokenOut_FromPool] Could not match both XLM and token reserves', [
                    'asset'  => $assetCode,
                    'issuer' => $issuerAddress,
                    'raw'    => $rawReserves,
                ]);
                return null;
            }

            // ---------- AMM math (bc for precision) ----------
            $scale = 18;

            if (!function_exists('bcadd')) {
                throw new \RuntimeException('BCMath extension is required for precise AMM estimation.');
            }

            // feeBps => multiplier = (10000 - feeBps) / 10000
            $feeMultiplier = bcdiv(bcsub('10000', (string)$feeBps, 0), '10000', $scale);

            // amountInWithFee = xlmAmountIn * feeMultiplier
            $amountInWithFee = bcmul($xlmAmountIn, $feeMultiplier, $scale);

            // tokenOut = (tokenReserve * amountInWithFee) / (xlmReserve + amountInWithFee)
            $numerator   = bcmul($tokenReserve, $amountInWithFee, $scale);
            $denominator = bcadd($xlmReserve, $amountInWithFee, $scale);

            if (bccomp($denominator, '0', $scale) === 0) {
                return null;
            }

            $tokenOut = bcdiv($numerator, $denominator, $scale);

            // Stellar assets usually use 3 decimals; display to 3
            $tokenOut7 = bcadd($tokenOut, '0', 3);

            return [
                'estimated_token' => $tokenOut7,
            ];
        } catch (\Throwable $e) {
            Log::error('[LP:estimate_TokenOut_FromPool] Exception', [
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function destinationWallet(Request $request)
    {
        try {
            $data = $request->validate([
                'amount' => ['required', 'numeric', 'gt:0'],
                'to_blockchain' => ['required', 'in:xlm,xrp'],
                'to_asset_code' => ['required', 'string', 'max:64'],
                'to_issuer_address' => ['nullable', 'string', 'max:128'],
                'destination_address' => ['required', 'string', 'max:128'],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 0,
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);
        }

        $dest = $data['destination_address'];

        if ($data['to_blockchain'] === 'xlm') {
            if (!$this->isStellarAddress($dest)) {
                return response()->json(['status' => 1, 'valid' => false, 'needs_trustline' => false, 'message' => 'Invalid Stellar address.']);
            }

            $check = $this->stellarDestinationCanReceive(
                $dest,
                $data['to_asset_code'],
                (string)($data['to_issuer_address'] ?? '')
            );

            return response()->json(['status' => 1] + $check);
        }

        // XRPL
        if (!$this->isXrplAddress($dest)) {
            return response()->json(['status' => 1, 'valid' => false, 'needs_trustline' => false, 'message' => 'Invalid XRPL address.']);
        }

        $check = $this->xrplDestinationCanReceive(
            $dest,
            $data['to_asset_code'],
            (string)($data['to_issuer_address'] ?? '')
        );

        return response()->json(['status' => 1] + $check);
    }

    private function isStellarAddress(string $address): bool
    {
        return str_starts_with($address, 'G') || str_starts_with($address, 'M');
    }

    private function stellarDestinationCanReceive(string $dest, string $assetCode, string $issuer): array
    {
        // Native XLM: account must exist
        if ($assetCode === 'XLM' || $issuer === null || $issuer === '') {
            $accRes = Http::timeout(15)->acceptJson()->get(rtrim($this->stellarUrl, '/') . "/accounts/{$dest}");
            if ($accRes->status() === 404) {
                return ['valid' => false, 'needs_trustline' => false, 'message' => 'Stellar account does not exist (needs activation).'];
            }
            if ($accRes->failed()) {
                return ['valid' => false, 'needs_trustline' => false, 'message' => 'Could not check Stellar account.'];
            }
            return ['valid' => true, 'needs_trustline' => false, 'message' => 'OK'];
        }

        $accRes = Http::timeout(15)->acceptJson()->get(rtrim($this->stellarUrl, '/') . "/accounts/{$dest}");

        if ($accRes->status() === 404) {
            return ['valid' => false, 'needs_trustline' => true, 'message' => 'Destination Stellar account does not exist (cannot hold tokens yet).'];
        }
        if ($accRes->failed()) {
            return ['valid' => false, 'needs_trustline' => false, 'message' => 'Could not check Stellar destination account.'];
        }

        $balances = data_get($accRes->json(), 'balances', []);
        $expectedType = strlen($assetCode) <= 4 ? 'credit_alphanum4' : 'credit_alphanum12';

        foreach ($balances as $b) {
            if (($b['asset_type'] ?? '') === $expectedType &&
                ($b['asset_code'] ?? '') === $assetCode &&
                ($b['asset_issuer'] ?? '') === $issuer
            ) {
                return ['valid' => true, 'needs_trustline' => false, 'message' => 'OK'];
            }
        }

        return ['valid' => false, 'needs_trustline' => true, 'message' => "Destination wallet is missing trustline for {$assetCode}."];
    }

    private function isXrplAddress(string $address): bool
    {
        return str_starts_with($address, 'r');
    }

    private function xrplDestinationCanReceive(string $dest, string $currency, string $issuer): array
    {
        if (strtoupper($currency) === 'XRP') {
            return ['valid' => true, 'needs_trustline' => false, 'message' => 'OK'];
        }

        $payload = [
            'method' => 'account_lines',
            'params' => [[
                'account' => $dest,
                'limit' => 400,
            ]]
        ];

        $res = Http::timeout(20)->post($this->rpcUrl, $payload);

        if ($res->failed()) {
            return ['valid' => false, 'needs_trustline' => false, 'message' => 'Could not check XRPL destination.'];
        }

        $result = $res->json('result');

        if (($result['status'] ?? '') !== 'success') {
            return ['valid' => false, 'needs_trustline' => false, 'message' => $result['error_message'] ?? 'XRPL check failed.'];
        }

        $lines = $result['lines'] ?? [];
        $cur = $this->xrplCurrency($currency);

        foreach ($lines as $l) {
            if (($l['currency'] ?? '') === $cur && ($l['account'] ?? '') === $issuer) {
                return ['valid' => true, 'needs_trustline' => false, 'message' => 'OK'];
            }
        }

        return ['valid' => false, 'needs_trustline' => true, 'message' => "Destination wallet is missing trustline for {$currency}."];
    }

    private function getMinimumTokenForXrp(
        string $tokenCurrency,
        string $tokenIssuer,
        string $tokenAmount,
        string $targetXrp,
        bool $isTestnet = false
    ): array {

        try {

            // Get XRP output for the PROVIDED token amount
            $quote = $this->xrpTokenToXrp(
                tokenAmount: $tokenAmount,
                currency: $tokenCurrency,
                issuer: $tokenIssuer,
                isTestnet: $isTestnet
            );

            Log::debug('xrpTokenToXrp', $quote);

            $xrpOut = $quote['xrp_out_estimated'] ?? '0';

            if (!is_numeric($xrpOut) || bccomp($xrpOut, '0', 18) <= 0) {
                throw new \RuntimeException('Cannot determine XRP output');
            }

            // Check if provided amount satisfies minimum XRP requirement
            $enough = bccomp($xrpOut, $targetXrp, 6) >= 0;

            return [
                'token_amount'   => $tokenAmount,
                'xrp_out'        => $xrpOut,
                'target_xrp'     => $targetXrp,
                'is_enough'      => $enough,
                'short_by'       => $enough
                    ? '0'
                    : bcsub($targetXrp, $xrpOut, 6),
            ];
        } catch (\Throwable $e) {

            Log::error('Min token calculation failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'token_amount' => $tokenAmount,
                'xrp_out'      => '0',
                'target_xrp'   => $targetXrp,
                'is_enough'    => false,
                'short_by'     => $targetXrp,
                'error'        => $e->getMessage(),
            ];
        }
    }

    private function findMinimumTokenAmountForXrp(
        string $currency,
        string $issuer,
        string $targetXrp,
        string $currentAmount,
        string $currentXrpOut,
        bool $isTestnet = false
    ): string {

        $ratio = bcdiv($targetXrp, $currentXrpOut, 18);
        $estimated = bcmul($currentAmount, $ratio, 6);

        $low  = bcmul($estimated, '0.8', 6);
        $high = bcmul($estimated, '1.2', 6);

        // 8–10 iterations needed now
        for ($i = 0; $i < 5; $i++) {

            $mid = bcdiv(bcadd($low, $high, 18), '2', 18);

            $quote = $this->xrpTokenToXrp(
                tokenAmount: $mid,
                currency: $currency,
                issuer: $issuer,
                isTestnet: $isTestnet
            );

            $xrpOut = $quote['xrp_out_estimated'] ?? '0';

            if (bccomp($xrpOut, $targetXrp, 6) >= 0) {
                $high = $mid;
            } else {
                $low = $mid;
            }
        }

        // $buffered = bcmul($high, '1.03', 6);

        // return bcadd($buffered, '0', 6);
        return bcadd($high, '0', 6);
    }

    public function getEstimatedSwapTimeSeconds(): ?float
    {
        return Swap::query()
            ->where('swap_state_id', 9)
            ->whereNotNull('started_at')
            ->whereNotNull('completed_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, started_at, completed_at)) as avg_seconds')
            ->value('avg_seconds');
    }

    public function getEstimatedSwapTimeHuman()
    {
        $seconds = $this->getEstimatedSwapTimeSeconds();

        if (!$seconds) {
            return response()->json([
                'estimated_time' => '2m 10s'
            ]);
        }

        $minutes = floor($seconds / 60);
        $remaining = $seconds % 60;

        return response()->json([
            'estimated_time' => "{$minutes}m {$remaining}s",
        ]);
    }
}
