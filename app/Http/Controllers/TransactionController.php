<?php

namespace App\Http\Controllers;

use App\Models\Token;
use Illuminate\Http\Request;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\StellarSDK;

class TransactionController extends Controller
{
    protected string $stellarWallet;
    protected string $rippleWallet;
    private $sdk, $network;
    protected string $rpcUrl;

    public function __construct()
    {
        $this->stellarWallet = env('STELLAR_WALLET');
        $this->rippleWallet = env('RIPPLE_WALLET');

        $stellarEnv = env('VITE_STELLAR_ENVIRONMENT');

        if ($stellarEnv === 'public') {
            $this->sdk = StellarSDK::getPublicNetInstance();
            $this->network = Network::public();
        } else {
            $this->sdk = StellarSDK::getTestNetInstance();
            $this->network = Network::testnet();
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

    /**
     * Simulates checking the Stellar Horizon API for a received payment.
     * In a real application, this would involve using a Stellar SDK
     * to query the Horizon endpoint for payments to $address with the specific $expectedAmount and $memo.
     *
     * @param string $address The Stellar wallet address (account ID).
     * @param float $expectedAmount The amount expected to be received.
     * @param string|null $memo The transaction memo used for reconciliation (optional).
     * @return array
     */
    private function simulateStellarHorizonCall(string $address, float $expectedAmount, ?string $memo): array
    {
        // --- REAL-WORLD SCENARIO: ---
        // 1. Use a Stellar SDK to query the Horizon API payments endpoint for the wallet address.
        // 2. Filter the results by recent time, expected amount, and transaction memo.
        // 3. If a match is found, return the transaction details.

        // --- SIMULATION LOGIC: ---
        // For demonstration, we'll simulate a success only if the expected amount is 50.0
        // and the memo is exactly 'PAYMENT_CONFIRMED'.
        $isReceived = $expectedAmount === 50.0 && $memo === 'PAYMENT_CONFIRMED';

        if ($isReceived) {
            return [
                'received' => true,
                'tx_id' => 'tx_' . md5(uniqid()), // Simulated Transaction ID
                'amount_found' => $expectedAmount,
                'memo_used' => $memo,
                'timestamp' => now()->toDateTimeString(),
            ];
        } else {
            return [
                'received' => false,
                'message' => 'Payment not yet found. The client must re-poll this endpoint.',
                'searching_for' => "Amount: {$expectedAmount}, Memo: " . ($memo ?? 'N/A'),
            ];
        }
    }

    /**
     * Checks if a specific amount has been received in the Stellar wallet.
     *
     * @param Request $request Requires 'amount' (float) and optionally 'memo' (string).
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkStellarReceipt(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.0000001',
            'memo' => 'nullable|string|max:28', // Stellar memos are max 28 bytes
        ]);

        $expectedAmount = (float) $request->input('amount');
        $memo = $request->input('memo');

        // Perform the check against the (simulated) external API
        $checkResult = $this->simulateStellarHorizonCall(
            $this->stellarWallet,
            $expectedAmount,
            $memo
        );

        if ($checkResult['received']) {
            return response()->json([
                'status' => 'success',
                'message' => 'Payment found and confirmed!',
                'data' => $checkResult,
            ], 200); // 200 OK
        }

        // Return a 202 Accepted response, indicating the server is still processing/awaiting
        return response()->json([
            'status' => 'pending',
            'message' => 'Awaiting payment confirmation. Please try again shortly.',
            'details' => $checkResult['searching_for'] ?? 'Check performed.',
        ], 202);
    }

    public function checkReceiveTokens(Request $request)
    {
        $data = $request->validate([
            'public_wallet' => ['required', 'string'],
            'amount' => ['required', 'numeric'],
            'blockchain' => ['required', 'numeric'],
            'asset_code' => ['required', 'numeric'],
        ]);

        $token_data = Token::where('asset_code', $data['asset_code'])->where('blockchain', $data['blockchain'])->first();

        //stellar
        if ($data['blockchain'] == 1) {
            try {
                $account = $this->sdk->requestAccount($data['publicKey']);

                $expectedAssetType = strlen($token_data->assetCode) <= 4
                    ? 'credit_alphanum4'
                    : 'credit_alphanum12';

                foreach ($account->getBalances() as $bal) {
                    if (
                        $bal->getAssetType()  === $expectedAssetType &&
                        $bal->getAssetCode()  === $token_data->assetCode &&
                        $bal->getAssetIssuer() === $token_data->issuer_address
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
    }
}
