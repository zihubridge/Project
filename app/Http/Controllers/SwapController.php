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
use Illuminate\Support\Str;

class SwapController extends Controller
{
    private $stellarWallet, $rippleWallet;

    public function __construct()
    {
        $this->stellarWallet = config('services.stellar.wallet');
        $this->rippleWallet  = config('services.xrpl.wallet');
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

            $memo = (string) random_int(100000000, 999999999);

            $swap = $this->createSwap($data['from_blockchain'], $data['to_blockchain'], $from_token->id, $to_token->id, $data['amount'], $data['destination_address'], $memo, $deposit_address);
            ScanDepositJob::dispatch($swap->id);

            return view('pages.deposit', [
                'uuid' => $swap->swap_uuid,
                'deposit_address' => $deposit_address,
                'memo' => $swap->routing_value,
                'amount' => $swap->from_amount,
                'expires_at' => $swap->expires_at,

                'from_blockchain_name' => $from_blockchain->name,
                'from_blockchain_asset_code' => strtoupper($from_blockchain->asset_code),
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
}
