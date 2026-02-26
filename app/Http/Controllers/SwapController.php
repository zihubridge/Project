<?php

namespace App\Http\Controllers;

use App\Jobs\ScanDepositJob;
use App\Models\Blockchain;
use App\Models\Swap;
use App\Models\SwapDeposit;
use App\Models\SwapEvent;
use App\Models\Token;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SwapController extends Controller
{
    private $stellarWallet, $rippleWallet, $isPublic;

    public function __construct()
    {
        $this->isPublic = env('ENVIRONMENT') === 'public';
        $this->stellarWallet = config('services.stellar.wallet');
        $this->rippleWallet  = config('services.xrpl.wallet');
    }

    public function start(Request $request)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'estimated_token_amount' => ['required', 'numeric', 'gt:0'],

            'from_blockchain' => ['required', 'exists:blockchains,id'],
            'to_blockchain'   => ['required', 'exists:blockchains,id'],

            'from_asset_code'     => ['required', 'string', 'max:64'],
            'from_issuer_address' => ['required', 'string', 'max:128'],

            'to_asset_code'       => ['required', 'string', 'max:64'],
            'to_issuer_address'   => ['required', 'string', 'max:128'],

            'destination_address' => ['required', 'string', 'max:128'],
            'destination_tag'     => ['nullable', 'string', 'max:64'],
        ]);

        $fromBlockchain = Blockchain::findOrFail($data['from_blockchain']);

        $fromToken = Token::where('issuer_address', $data['from_issuer_address'])->firstOrFail();
        $toToken   = Token::where('issuer_address', $data['to_issuer_address'])->firstOrFail();

        // Decide platform deposit address
        if ($fromBlockchain->id === 1) {
            $depositAddress = $this->stellarWallet;
            $baseUrl = $this->isPublic
                ? config('services.explorers.stellar.mainnet')
                : config('services.explorers.stellar.testnet');

            $url = $baseUrl . $depositAddress;
            $routingType    = 'memo_id';
        } else {
            $depositAddress = $this->rippleWallet;
            $baseUrl = $this->isPublic
                ? config('services.explorers.xrpl.mainnet')
                : config('services.explorers.xrpl.testnet');

            $url = $baseUrl . $depositAddress;
            $routingType    = 'destination_tag';
        }

        // Generate routing value
        $routingValue = (string) random_int(100000000, 999999999);

        $swap = $this->createSwap(
            fromBlockchainId: $fromBlockchain->id,
            toBlockchainId: $data['to_blockchain'],
            fromTokenId: $fromToken->id,
            toTokenId: $toToken->id,
            fromTokenAmount: $data['amount'],
            estimatedTokenAmount: $data['estimated_token_amount'],
            destinationAddress: $data['destination_address'],
            destinationTag: $data['destination_tag'] ?? null,
            depositAddress: $depositAddress,
            depositRoutingType: $routingType,
            depositRoutingValue: $routingValue
        );

        ScanDepositJob::dispatch($swap->id);

        $currentStep = $swap->getFrontendStep();
        $stepName = $swap->getFrontendStepName();

        return view('pages.deposit', compact(
            'swap',
            'depositAddress',
            'routingValue',
            'url',
            'currentStep',
            'stepName'
        ));
    }


    public function createSwap(
        int $fromBlockchainId,
        int $toBlockchainId,
        int $fromTokenId,
        int $toTokenId,
        string $fromTokenAmount,
        string $estimatedTokenAmount,
        string $destinationAddress,
        ?string $destinationTag,
        string $depositAddress,
        string $depositRoutingType,
        string $depositRoutingValue
    ): Swap {
        return DB::transaction(function () use (
            $fromBlockchainId,
            $toBlockchainId,
            $fromTokenId,
            $toTokenId,
            $fromTokenAmount,
            $estimatedTokenAmount,
            $destinationAddress,
            $destinationTag,
            $depositAddress,
            $depositRoutingType,
            $depositRoutingValue
        ) {

            // CLEANUP OLD UNUSED SWAPS 24 hours old
            Swap::where('swap_state_id', 2) // waiting_for_deposit
                ->where('expires_at', '<', now()->subHours(24))
                ->whereNull('started_at')
                ->whereDoesntHave('exchange')
                ->whereDoesntHave('internalSwaps')
                ->whereDoesntHave('payout')
                ->whereHas('deposit', function ($q) {
                    $q->whereNull('tx_hash')
                        ->where('deposit_state_id', 1);
                })
                ->chunkById(100, function ($swaps) {
                    foreach ($swaps as $swap) {
                        DB::transaction(function () use ($swap) {
                            $swap->deposit()->delete();
                            $swap->events()->delete();
                            $swap->delete();
                        });
                    }
                });

            $swap = Swap::create([
                'swap_uuid'         => Str::uuid(),
                'from_blockchain_id' => $fromBlockchainId,
                'to_blockchain_id'  => $toBlockchainId,
                'from_token_id'     => $fromTokenId,
                'to_token_id'       => $toTokenId,
                'from_token_amount'       => $fromTokenAmount,
                'to_estimated_token_amount'       => $estimatedTokenAmount,
                'destination_address' => $destinationAddress,
                'destination_tag'   => $destinationTag,
                'swap_state_id'     => 2, //WAITING_FOR_DEPOSIT,
                'expires_at'        => now()->addMinutes(15),
            ]);

            SwapDeposit::create([
                'swap_id'              => $swap->id,
                'deposit_address'      => $depositAddress,
                'deposit_routing_type' => $depositRoutingType,
                'deposit_routing_value' => $depositRoutingValue,
                'expected_token_id'    => $fromTokenId,
                'expected_token_amount' => $fromTokenAmount,
                'deposit_state_id'     => 1, //WAITING,
                'expires_at'           => $swap->expires_at,
            ]);

            SwapEvent::create([
                'swap_id' => $swap->id,
                'swap_event_type_id' => 1, //DEPOSIT_DETECTED
                'message' => 'Swap created, waiting for user deposit',
            ]);

            return $swap;
        });
    }

    public function getStatus($uuid)
    {
        $swap = Swap::where('swap_uuid', $uuid)
            ->with(['fromToken', 'toToken', 'fromBlockchain', 'toBlockchain', 'swapState'])
            ->firstOrFail();

        return response()->json([
            'swap_state_id' => $swap->swap_state_id,
            'swap_state_name' => $swap->swapState->name,
            'current_step' => $swap->getFrontendStep(),
            'step_name' => $swap->getFrontendStepName(),
            'is_completed' => $swap->swapState->name === 'completed',
            'is_failed' => in_array($swap->swapState->name, ['expired', 'failed', 'refunded']),
            'failure_reason' => $swap->failure_reason,
            'to_final_token_amount' => $swap->to_final_token_amount,
        ]);
    }
}
