<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Swap\AmountNormalizer;
use App\Services\Swap\QuoteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Throwable;

class QuoteController extends Controller
{
    public function __construct(
        private readonly QuoteService $quoteService,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'source_asset_id' => ['required', 'integer', 'exists:crypto_assets,id'],
            'destination_asset_id' => ['required', 'integer', 'exists:crypto_assets,id', 'different:source_asset_id'],
            'amount' => ['required', 'string', 'max:64'],
            'slippage_bps' => ['nullable', 'integer', 'min:1', 'max:5000'],
            'recipient_address' => ['nullable', 'string', 'max:191'],
            'refund_address' => ['nullable', 'string', 'max:191'],
        ]);

        try {
            $quote = $this->quoteService->createQuote(
                sourceAssetId: (int) $validated['source_asset_id'],
                destinationAssetId: (int) $validated['destination_asset_id'],
                amountInHuman: (string) $validated['amount'],
                slippageBps: isset($validated['slippage_bps']) ? (int) $validated['slippage_bps'] : 100,
                recipientAddress: $validated['recipient_address'] ?? null,
                refundAddress: $validated['refund_address'] ?? null,
            );

            return response()->json([
                'status' => 1,
                'data' => [
                    'quote_uuid' => $quote->quote_uuid,
                    'provider' => [
                        'slug' => $quote->provider->slug,
                        'display_name' => $quote->provider->display_name,
                    ],
                    'source_asset' => [
                        'id' => $quote->sourceAsset->id,
                        'symbol' => $quote->sourceAsset->symbol,
                        'display_name' => $quote->sourceAsset->display_name,
                        'network_slug' => $quote->sourceAsset->network?->slug,
                        'network_name' => $quote->sourceAsset->network?->display_name,
                        'decimals' => $quote->sourceAsset->decimals,
                    ],
                    'destination_asset' => [
                        'id' => $quote->destinationAsset->id,
                        'symbol' => $quote->destinationAsset->symbol,
                        'display_name' => $quote->destinationAsset->display_name,
                        'network_slug' => $quote->destinationAsset->network?->slug,
                        'network_name' => $quote->destinationAsset->network?->display_name,
                        'decimals' => $quote->destinationAsset->decimals,
                    ],
                    'amount_in' => AmountNormalizer::trimTrailingZeros((string) $quote->amount_in),
                    'amount_in_base_units' => $quote->amount_in_base_units,
                    'expected_amount_out' => AmountNormalizer::trimTrailingZeros((string) $quote->expected_amount_out),
                    'min_amount_out' => AmountNormalizer::trimTrailingZeros((string) $quote->min_amount_out),
                    'exchange_rate' => AmountNormalizer::trimTrailingZeros((string) $quote->exchange_rate),
                    'amount_in_usd' => $quote->amount_in_usd !== null ? (string) $quote->amount_in_usd : null,
                    'amount_out_usd' => $quote->amount_out_usd !== null ? (string) $quote->amount_out_usd : null,
                    'slippage_bps' => $quote->slippage_bps,
                    'platform_fee_bps' => $quote->platform_fee_bps,
                    'fee_breakdown' => $quote->fee_breakdown_json,
                    'estimated_duration_seconds' => $quote->estimated_duration_seconds,
                    'expires_at' => $quote->expires_at?->toIso8601String(),
                ],
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'status' => 0,
                'message' => $e->getMessage(),
            ], 422);
        } catch (Throwable $e) {
            return response()->json([
                'status' => 0,
                'message' => $e->getMessage() ?: 'Could not retrieve a quote for the selected pair.',
            ], 400);
        }
    }
}

