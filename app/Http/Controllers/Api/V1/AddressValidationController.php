<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\BlockchainNetwork;
use App\Services\Swap\ChainAddressValidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AddressValidationController extends Controller
{
    public function __construct(
        private readonly ChainAddressValidator $validator,
    ) {}

    public function validateAddress(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'network_slug' => ['required', 'string', 'max:64'],
            'address' => ['required', 'string', 'max:191'],
            'memo' => ['nullable', 'string', 'max:128'],
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

        return response()->json([
            'status' => 1,
            'network_slug' => $network->slug,
            'valid' => $result['valid'],
            'requires_memo_or_tag' => $result['requires_memo_or_tag'],
            'message' => $result['message'],
        ]);
    }
}

