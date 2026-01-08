<?php

namespace App\Http\Controllers;

use App\Models\Swap;
use App\Models\SwapDeposit;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\StellarSDK;

class SwapController extends Controller
{
    private $sdk, $network;
    protected string $rpcUrl, $stellarUrl;

    public function __construct()
    {
        $stellarEnv = env('VITE_STELLAR_ENVIRONMENT');

        if ($stellarEnv === 'public') {
            $this->sdk = StellarSDK::getPublicNetInstance();
            $this->network = Network::public();
            $this->stellarUrl = env('STELLAR_HORIZON_MAINNET');
            $this->rpcUrl = env('XRPL_RPC_MAINNET');
        } else {
            $this->sdk = StellarSDK::getTestNetInstance();
            $this->network = Network::testnet();
            $this->stellarUrl = env('STELLAR_HORIZON_TESTNET');
            $this->rpcUrl = env('XRPL_RPC_TESTNET');
        }
    }

    public function start_swap(Request $request)
    {
        $data = $request->validate([
            'from_token_id' => 'required|integer',
            'to_token_id' => 'required|integer',
            'amount' => 'required|numeric|gt:0',
            'destination_address' => 'required|string',
        ]);

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
        } catch (ValidationException $e) {
            return response()->json([
                'status'  => 0,
                'message' => 'Validation error',
                'errors'  => $e->errors(),
            ], 422);
        }
    }
    public function createSwap($from_token_id, $to_token_id, $from_amount, $destination_address)
    {
        DB::beginTransaction();

        try {
            // 1) Generate unique MEMO_ID
            $memo = (string) random_int(100000000, 999999999);

            // 2) Create swap
            $swap = Swap::create([
                'swap_uuid' => Str::uuid(),
                'from_token_id' => $from_token_id,
                'to_token_id' => $to_token_id,
                'from_amount' => $from_amount,
                'routing_type' => 'memo_id',
                'routing_value' => $memo,
                'destination_address' => $destination_address,
                'swap_state_id' => 1, // waiting_deposit
                'expires_at' => now()->addMinutes(30),
            ]);

            // 3) Create swap deposit instruction
            SwapDeposit::create([
                'swap_id' => $swap->id,
                'platform_wallet_id' => config('bridge.stellar_wallet_id'),
                'deposit_address' => config('bridge.stellar_wallet_address'),
                'routing_type' => 'memo_id',
                'routing_value' => $memo,
                'expected_token_id' => $from_token_id,
                'expected_amount' => $from_amount,
                'deposit_state_id' => 1, // waiting
                'expires_at' => $swap->expires_at,
            ]);

            DB::commit();

            // 4) Return instructions to frontend
            return response()->json([
                'swap_id' => $swap->swap_uuid,
                'deposit_address' => config('bridge.stellar_wallet_address'),
                'memo' => $memo,
                'memo_type' => 'MEMO_ID',
                'amount' => $from_amount,
                'expires_at' => $swap->expires_at,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function detectDeposits(): void
    {
        $pendingDeposits = SwapDeposit::query()
            ->where('status', 'waiting')
            ->whereHas(
                'swap',
                fn($q) =>
                $q->where('expires_at', '>', now())
            )
            ->get();

        foreach ($pendingDeposits as $deposit) {
            $this->scanDeposit($deposit);
        }
    }

    private function scanDeposit(SwapDeposit $deposit): void
    {
        $blockchain = $deposit->swap->fromBlockchain->slug;

        match ($blockchain) {
            'stellar' => $this->scanStellarDeposit($deposit),
            'xrpl'    => $this->scanXrplDeposit($deposit),
            default   => throw new \RuntimeException("Unsupported blockchain: {$blockchain}")
        };
    }

    private function scanStellarDeposit(SwapDeposit $deposit): void
    {
        $address = $deposit->deposit_address;

        $url = rtrim($this->stellarUrl, '/') .
            "/accounts/{$address}/payments?order=desc&limit=100";

        $res = Http::timeout(15)->get($url);
        if ($res->failed()) return;

        $payments = data_get($res->json(), '_embedded.records', []);

        foreach ($payments as $p) {
            if (($p['to'] ?? null) !== $address) continue;

            // asset match
            if (($p['asset_code'] ?? null) !== $deposit->expectedToken->asset_code) continue;
            if (($p['asset_issuer'] ?? null) !== $deposit->expectedToken->issuer_address) continue;

            // amount match
            if (bccomp((string)$p['amount'], (string)$deposit->expected_amount, 7) < 0) continue;

            // fetch tx to check memo
            $txHash = $p['transaction_hash'];

            $txRes = Http::timeout(15)->acceptJson()->get(
                rtrim($this->stellarUrl, '/') . '/transactions/' . $txHash
            );

            if ($txRes->failed()) continue;

            $tx = $txRes->json();

            // memo validation (required)
            if (($tx['memo_type'] ?? null) !== 'id') continue;
            if ((string)($tx['memo'] ?? '') !== (string)$deposit->deposit_memo) continue;

            DB::transaction(function () use ($deposit, $p, $txHash) {
                $deposit->update([
                    'received_amount' => $p['amount'],
                    'tx_hash' => $txHash,
                    'sender_address' => $p['from'] ?? null,
                    'received_at' => now(),
                    'deposit_state_id' => 3, // confirmed
                ]);

                $deposit->swap->update([
                    'swap_state_id' => 2, // deposit_detected
                ]);
            });

            return;
        }
    }

    private function scanXrplDeposit(SwapDeposit $deposit): void
    {
        $res = Http::timeout(20)->post($this->rpcUrl, [
            'method' => 'account_tx',
            'params' => [[
                'account' => $deposit->deposit_address,
                'limit' => 50,
            ]]
        ]);

        if ($res->failed()) return;

        $txs = data_get($res->json(), 'result.transactions', []);

        foreach ($txs as $entry) {
            $tx = $entry['tx'] ?? null;
            if (!$tx) continue;

            if (($tx['Destination'] ?? null) !== $deposit->deposit_address) continue;

            // Destination Tag match (this is your memo)
            if (($tx['DestinationTag'] ?? null) != (int)$deposit->routing_value) continue;

            // XRP vs IOU handling
            if (is_string($tx['Amount'])) {
                // XRP payment
                $amount = bcdiv($tx['Amount'], '1000000', 6);
                if (bccomp($amount, $deposit->expected_amount, 6) < 0) continue;
            } else {
                // IOU token
                if (($tx['Amount']['currency'] ?? null) !== $deposit->expectedToken->asset_code) continue;
                if (($tx['Amount']['issuer'] ?? null) !== $deposit->expectedToken->issuer_address) continue;
                if (bccomp($tx['Amount']['value'], $deposit->expected_amount, 18) < 0) continue;
            }

            DB::transaction(function () use ($deposit, $tx) {
                $deposit->update([
                    'received_amount' => is_string($tx['Amount'])
                        ? bcdiv($tx['Amount'], '1000000', 6)
                        : $tx['Amount']['value'],
                    'tx_hash' => $tx['hash'],
                    'sender_address' => $tx['Account'],
                    'received_at' => now(),
                    'deposit_state_id' => 3, // confirmed
                ]);

                $deposit->swap->update([
                    'swap_state_id' => 2, // deposit_detected
                ]);
            });

            return;
        }
    }
}
