<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\BlockchainNetwork;
use App\Models\CryptoAsset;
use App\Services\Providers\Adapters\StellarSdexAdapter;
use App\Services\Swap\ChainAddressValidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AddressValidationController extends Controller
{
    public function __construct(
        private readonly ChainAddressValidator $validator,
        private readonly StellarSdexAdapter $stellarAdapter,
    ) {}

    public function validateAddress(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'network_slug' => ['required', 'string', 'max:64'],
            'address' => ['required', 'string', 'max:191'],
            'memo' => ['nullable', 'string', 'max:128'],
            'asset_id' => ['nullable', 'integer', 'exists:crypto_assets,id'],
        ]);

        $network = BlockchainNetwork::where('slug', strtolower(trim($validated['network_slug'])))
            ->where('is_enabled', true)
            ->first();

        if (!$network) {
            return response()->json([
                'status' => 0,
                'valid' => false,
                'message' => 'Selected blockchain network is not supported.',
            ], 422);
        }

        $result = $this->validator->validate(
            $network,
            $validated['address'],
            $validated['memo'] ?? null
        );

        if (!$result['valid']) {
            return response()->json([
                'status' => 1,
                'network_slug' => $network->slug,
                'valid' => false,
                'requires_memo_or_tag' => $result['requires_memo_or_tag'],
                'message' => $result['message'],
            ]);
        }

        // On-chain account & trustline requirement check for destination assets
        if (!empty($validated['asset_id'])) {
            $asset = CryptoAsset::with('network')->find($validated['asset_id']);
            if ($asset && $asset->blockchain_network_id === $network->id) {
                if ($network->chain_family === 'stellar' || strtolower($network->slug) === 'stellar') {
                    $accountCheck = $this->stellarAdapter->checkAccountRequirements($network, $validated['address'], $asset);
                    if (isset($accountCheck['prerequisites_met']) && !$accountCheck['prerequisites_met']) {
                        return response()->json([
                            'status' => 1,
                            'network_slug' => $network->slug,
                            'valid' => false,
                            'trustline_missing' => !($accountCheck['requirements']['trustline_present'] ?? true),
                            'account_activated' => $accountCheck['requirements']['account_activated'] ?? true,
                            'requires_memo_or_tag' => $result['requires_memo_or_tag'],
                            'message' => $accountCheck['message'] ?: "Missing {$asset->symbol} trustline on recipient wallet.",
                        ]);
                    }
                }
            }
        }

        return response()->json([
            'status' => 1,
            'network_slug' => $network->slug,
            'valid' => true,
            'requires_memo_or_tag' => $result['requires_memo_or_tag'],
            'message' => $result['message'],
        ]);
    }
}

