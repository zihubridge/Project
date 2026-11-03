<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\BlockchainNetwork;
use App\Models\CryptoAsset;
use App\Models\TokenListingRequest;
use Illuminate\Http\Request;

class AdminListingController extends Controller
{
    public function index(Request $request)
    {
        $query = TokenListingRequest::with(['blockchainNetwork', 'reviewedByAdmin']);

        if ($request->filled('status')) {
            $query->where('review_status', $request->query('status'));
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->query('payment_status'));
        }

        if ($request->filled('search')) {
            $s = trim($request->query('search'));
            $query->where(function ($q) use ($s) {
                $q->where('token_name', 'like', "%{$s}%")
                    ->orWhere('token_symbol', 'like', "%{$s}%")
                    ->orWhere('project_name', 'like', "%{$s}%");
            });
        }

        $listings = $query->latest('id')->paginate(20)->withQueryString();

        return view('admin.listings.index', [
            'listings' => $listings,
            'filters' => $request->all(),
        ]);
    }

    public function show(int $id)
    {
        $listing = TokenListingRequest::with(['blockchainNetwork', 'reviewedByAdmin', 'publishedCryptoAsset'])->findOrFail($id);

        return view('admin.listings.show', [
            'listing' => $listing,
        ]);
    }

    public function updateStatus(Request $request, int $id)
    {
        $listing = TokenListingRequest::findOrFail($id);
        $admin = $request->attributes->get('current_admin');

        $validated = $request->validate([
            'review_status' => ['required', 'string', 'in:DRAFT,PENDING_PAYMENT,PAID,UNDER_REVIEW,NEEDS_CHANGES,APPROVED,REJECTED,PUBLISHED,SUSPENDED'],
            'payment_status' => ['required', 'string', 'in:PENDING_PAYMENT,PAID,REFUNDED'],
            'payment_tx_hash' => ['nullable', 'string', 'max:191'],
            'listing_fee_usd' => ['required', 'numeric', 'min:0'],
            'reviewer_notes' => ['nullable', 'string', 'max:1000'],
            'publish_to_catalog' => ['nullable', 'boolean'],
        ]);

        $old = $listing->toArray();

        $listing->review_status = $validated['review_status'];
        $listing->payment_status = $validated['payment_status'];
        $listing->payment_tx_hash = $validated['payment_tx_hash'] ? trim($validated['payment_tx_hash']) : $listing->payment_tx_hash;
        $listing->listing_fee_usd = $validated['listing_fee_usd'];
        $listing->reviewer_notes = $validated['reviewer_notes'] ? trim($validated['reviewer_notes']) : $listing->reviewer_notes;
        $listing->reviewed_by_admin_id = $admin?->id;

        if ($validated['review_status'] === TokenListingRequest::STATUS_APPROVED && !$listing->approved_at) {
            $listing->approved_at = now();
        }

        // If explicitly requested to publish to public catalog
        if (!empty($validated['publish_to_catalog']) && $validated['review_status'] === TokenListingRequest::STATUS_PUBLISHED) {
            $listing->published_at = now();

            $existing = CryptoAsset::where('blockchain_network_id', $listing->blockchain_network_id)
                ->where('symbol', $listing->token_symbol)
                ->where('contract_or_issuer', $listing->contract_or_issuer)
                ->first();

            if (!$existing) {
                $asset = CryptoAsset::create([
                    'blockchain_network_id' => $listing->blockchain_network_id,
                    'symbol' => strtoupper($listing->token_symbol),
                    'display_name' => $listing->token_name,
                    'is_native' => false,
                    'contract_or_issuer' => $listing->contract_or_issuer,
                    'decimals' => $listing->decimals,
                    'logo_url' => $listing->logo_url,
                    'is_enabled' => true,
                ]);
                $listing->published_crypto_asset_id = $asset->id;
            } else {
                $listing->published_crypto_asset_id = $existing->id;
            }
        }

        $listing->save();

        AdminAuditLog::log(
            $admin?->id,
            'LISTING_STATUS_UPDATED',
            'TokenListingRequest',
            (string) $listing->id,
            $old,
            $listing->fresh()->toArray()
        );

        return redirect()->route('admin.listings.show', $listing->id)->with('success', "Listing request status updated to {$listing->review_status}.");
    }
}
