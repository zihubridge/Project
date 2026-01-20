<?php

namespace App\Http\Controllers;

use App\Jobs\ScanDepositJob;
use App\Models\Blockchain;
use App\Models\Swap;
use App\Models\SwapDeposit;
use App\Models\Token;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\StellarSDK;
use Illuminate\Http\Client\RequestException;

class SwapController extends Controller
{
    private $sdk, $network, $stellarWallet, $stellarWalletKey, $rippleWallet, $rippleWalletKey;
    protected string $rpcUrl, $stellarUrl;

    public function __construct()
    {
        $stellarEnv = env('VITE_STELLAR_ENVIRONMENT');

        if ($stellarEnv === 'public') {
            $this->sdk = StellarSDK::getPublicNetInstance();
            $this->network = Network::public();
            $this->stellarWallet = env('STELLAR_PUBLIC_ADDRESS');
            $this->stellarWalletKey = env('STELLAR_SECRET_KEY');
            $this->stellarUrl = env('STELLAR_HORIZON_MAINNET');

            $this->rippleWallet = env('XRPL_PUBLIC_ADDRESS');
            $this->rippleWalletKey = env('XRPL_SECRET_KEY');
            $this->rpcUrl = env('XRPL_RPC_MAINNET');
        } else {
            $this->sdk = StellarSDK::getTestNetInstance();
            $this->network = Network::testnet();
            $this->stellarUrl = env('STELLAR_HORIZON_TESTNET');
            $this->stellarWallet = env('STELLAR_TESTNET_PUBLIC_ADDRESS');
            $this->stellarWalletKey = env('STELLAR_TESTNET_SECRET_KEY');

            $this->rippleWallet = env('XRPL_TESTNET_PUBLIC_ADDRESS');
            $this->rippleWalletKey = env('XRPL_TESTNET_SECRET_KEY');
            $this->rpcUrl = env('XRPL_RPC_TESTNET');
        }
    }

    public function start(Request $request)
    {
        try {
            try {
                $data = $request->validate([
                    'amount' => ['required', 'numeric', 'gt:0'],

                    'from_blockchain' => ['required'],
                    'to_blockchain' => ['required'],

                    'from_asset_code' => ['required', 'string', 'max:64'],
                    'from_issuer_address' => ['required', 'string', 'max:128'],

                    'to_asset_code' => ['required', 'string', 'max:64'],
                    'to_issuer_address' => ['required', 'string', 'max:128'],
                    'destination_address' => ['required', 'string', 'max:128'],
                    'memo' => ['required', 'string', 'max:128'],
                ]);
            } catch (ValidationException $e) {
                return redirect()->back()
                    ->withErrors($e->validator)
                    ->withInput();
            }

            $from_blockchain = Blockchain::find($data['from_blockchain']);

            $from_token = Token::where('issuer_address', $data['from_issuer_address'])->firstOrFail();

            $to_token = Token::where('issuer_address', $data['to_issuer_address'])->firstOrFail();

            $assetCode = $from_token->asset_code;
            if (!$assetCode) {
                throw new \Exception("Asset Code not found");
            }

            $issuerAddress = $from_token->issuer_address;
            if (!$issuerAddress) {
                throw new \Exception("Issuer Address not found");
            }

            $deposit_address = null;
            if ($data['from_blockchain'] == 1) {
                $deposit_address = $this->stellarWallet;
            } else {
                $deposit_address = $this->rippleWallet;
            }
            $swap = $this->createSwap($data['from_blockchain'], $data['to_blockchain'], $from_token->id, $to_token->id, $data['amount'], $data['destination_address'], $data['memo'], $deposit_address);
            ScanDepositJob::dispatch($swap->id);

            return view('pages.deposit', [
                'uuid' => $swap->swap_uuid,
                'deposit_address' => config('bridge.stellar_wallet_address'),
                'memo' => $swap->routing_value,
                'amount' => $swap->from_amount,
                'expires_at' => $swap->expires_at,

                'from_blockchain_name' => $from_blockchain->name,
                'from_blockchain_asset_code' => $from_blockchain->asset_code,
                'from_token' => $from_token->asset_code,
            ]);
        } catch (ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->validator)
                ->withInput();
        }
    }

    public function createSwap($from_blockchain, $to_blockchain, $from_token_id, $to_token_id, $from_amount, $destination_address, $memo, $deposit_address)
    {
        DB::beginTransaction();

        try {
            // Create swap
            $swap = Swap::create([
                'swap_uuid' => Str::uuid(),
                'from_blockchain_id' => $from_blockchain,
                'to_blockchain_id' => $to_blockchain,
                'from_token_id' => $from_token_id,
                'to_token_id' => $to_token_id,
                'from_amount' => $from_amount,
                'routing_type' => 'memo_id',
                'routing_value' => $memo,
                'destination_address' => $destination_address,
                'swap_state_id' => 1, // pending
                'expires_at' => now()->addMinutes(15),
            ]);

            // Create swap deposit instruction
            SwapDeposit::create([
                'swap_id' => $swap->id,
                'deposit_address' => $deposit_address,
                'routing_type' => 'memo_id',
                'routing_value' => $memo,
                'expected_token_id' => $from_token_id,
                'expected_amount' => $from_amount,
                'deposit_state_id' => 1, // waiting
                'expires_at' => $swap->expires_at,
            ]);

            DB::commit();

            // Return instructions to frontend
            return $swap;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }


    private function xrplPost(array $payload): array
    {
        $res = Http::timeout(20)->withHeaders(['Content-Type' => 'application/json'])->post($this->rpcUrl, $payload);
        return $res->json() ?? [];
    }

    private function xrplCurrency(string $currency): string
    {
        // you already have this; ensure:
        // - if 3 chars -> uppercase
        // - else 40 hex
        $c = strtoupper($currency);
        if (strlen($c) === 3) return $c;
        if (preg_match('/^[A-F0-9]{40}$/', $c)) return $c;
        throw new \InvalidArgumentException("Invalid XRPL currency: {$currency}");
    }

    private function xrplPathFind(
        string $source,
        string $destination,
        array $destinationAmount, // XRP string drops OR IOU object
        array|string|null $sendMax = null
    ): array {
        $params = [
            'source_account' => $source,
            'destination_account' => $destination,
            'destination_amount' => $destinationAmount,
        ];
        if ($sendMax !== null) $params['send_max'] = $sendMax;

        return $this->xrplPost([
            'method' => 'ripple_path_find',
            'params' => [$params],
        ]);
    }


    private function xrpToXrpToken(
        string $xrpAmountIn,      // XRP units, e.g. "25.5"
        string $tokenCurrency,    // 3-char or 40-hex
        string $tokenIssuer,
        string $minTokenOut       // enforce slippage
    ): array {
        $seed = env('XRPL_MAIN_WALLET_SEED');
        $hot = env('XRPL_MAIN_WALLET'); // r....

        $cur = $this->xrplCurrency($tokenCurrency);

        $sendMaxDrops = (string) bcmul($xrpAmountIn, '1000000', 0);

        // Ask XRPL for route/paths
        $pathRes = $this->xrplPathFind(
            source: $hot,
            destination: $hot,
            destinationAmount: ['currency' => $cur, 'issuer' => $tokenIssuer, 'value' => $minTokenOut],
            sendMax: $sendMaxDrops
        );

        $alts = data_get($pathRes, 'result.alternatives', []);
        if (!$alts) {
            return ['ok' => false, 'reason' => 'no_path'];
        }

        $best = $alts[0];
        $paths = $best['paths_computed'] ?? [];

        $tx = [
            'TransactionType' => 'Payment',
            'Account' => $hot,
            'Destination' => $hot,
            'Amount' => ['currency' => $cur, 'issuer' => $tokenIssuer, 'value' => $best['destination_amount']['value'] ?? $minTokenOut],
            'SendMax' => $sendMaxDrops,
            'DeliverMin' => ['currency' => $cur, 'issuer' => $tokenIssuer, 'value' => $minTokenOut],
            'Paths' => $paths,
            'Flags' => 0x00020000, // tfPartialPayment
        ];

        $submit = $this->xrplSignAndSubmit($tx);

        return [
            'ok' => true,
            'tx_hash' => $submit['hash'] ?? null,
            'engine_result' => $submit['engine_result'] ?? null,
        ];
    }

    private function xrpTokenToXrp(
        string $tokenAmountIn,
        string $tokenCurrency,
        string $tokenIssuer,
        string $minXrpOut          // XRP units, e.g. "1.25"
    ): array {
        $hot = env('XRPL_MAIN_WALLET'); // r....

        $cur = $this->xrplCurrency($tokenCurrency);

        $deliverMinDrops = (string) bcmul($minXrpOut, '1000000', 0);

        // find path: want XRP out
        $pathRes = $this->xrplPathFind(
            source: $hot,
            destination: $hot,
            destinationAmount: $deliverMinDrops,
            sendMax: ['currency' => $cur, 'issuer' => $tokenIssuer, 'value' => $tokenAmountIn]
        );

        $alts = data_get($pathRes, 'result.alternatives', []);
        if (!$alts) {
            return ['ok' => false, 'reason' => 'no_path'];
        }

        $best = $alts[0];
        $paths = $best['paths_computed'] ?? [];

        // For XRP Amount, it's a string drops
        $tx = [
            'TransactionType' => 'Payment',
            'Account' => $hot,
            'Destination' => $hot,
            'Amount' => $best['destination_amount'] ?? $deliverMinDrops,
            'SendMax' => ['currency' => $cur, 'issuer' => $tokenIssuer, 'value' => $tokenAmountIn],
            'DeliverMin' => $deliverMinDrops,
            'Paths' => $paths,
            'Flags' => 0x00020000, // tfPartialPayment
        ];

        $submit = $this->xrplSignAndSubmit($tx);

        return [
            'ok' => true,
            'tx_hash' => $submit['hash'] ?? null,
            'engine_result' => $submit['engine_result'] ?? null,
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
            // 'flow'         => $flow,
            // 'type'         => $type,
            // 'useRateId'    => $useRateId ? 'true' : 'false',
            // 'isTopUp'      => $isTopUp ? 'true' : 'false',
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
}
