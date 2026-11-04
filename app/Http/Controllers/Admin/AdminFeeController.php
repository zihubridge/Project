<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\PlatformFeeConfiguration;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdminFeeController extends Controller
{
    public function index()
    {
        $currentConfig = PlatformFeeConfiguration::getActive();
        $history = PlatformFeeConfiguration::with('changedByAdmin')->latest('id')->paginate(15);

        return view('admin.fees.index', [
            'currentConfig' => $currentConfig,
            'history' => $history,
        ]);
    }

    public function update(Request $request)
    {
        $admin = $request->attributes->get('current_admin');

        // Only Super Admin or Finance Admin can update platform fee
        if (!$admin || (!$admin->isSuperAdmin() && !$admin->isFinanceAdmin())) {
            abort(403, 'Only Super Admin or Finance Admin can update the platform fee schedule.');
        }

        // Allow entering fee directly in percentage (e.g. 0.1 for 0.1%, 1 for 1%)
        // or as raw basis points (BPS)
        if ($request->filled('fee_percentage')) {
            $rawPercent = (float) $request->input('fee_percentage');
            $request->merge([
                'fee_bps' => (int) round($rawPercent * 100),
            ]);
        }

        $validated = $request->validate([
            'fee_percentage' => ['nullable', 'numeric', 'min:0', 'max:10'],
            'fee_bps' => ['required_without:fee_percentage', 'integer', 'min:0', 'max:1000'], // Max 10% (1000 BPS)
            'fee_recipient' => ['nullable', 'string', 'max:191'],
            'is_enabled' => ['required', 'boolean'],
            'effective_from' => ['nullable', 'date'],
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $feeBps = isset($validated['fee_percentage'])
            ? (int) round(((float) $validated['fee_percentage']) * 100)
            : (int) $validated['fee_bps'];

        $effectiveFrom = !empty($validated['effective_from']) ? Carbon::parse($validated['effective_from']) : now();

        $previousConfig = PlatformFeeConfiguration::getActive();

        $newConfig = PlatformFeeConfiguration::create([
            'fee_bps' => $feeBps,
            'fee_recipient' => !empty($validated['fee_recipient']) ? trim($validated['fee_recipient']) : null,
            'is_enabled' => (bool) $validated['is_enabled'],
            'effective_from' => $effectiveFrom,
            'changed_by_admin_id' => $admin->id,
            'reason' => trim($validated['reason']),
        ]);

        AdminAuditLog::log(
            $admin->id,
            'PLATFORM_FEE_UPDATED',
            'PlatformFeeConfiguration',
            (string) $newConfig->id,
            $previousConfig ? $previousConfig->toArray() : null,
            $newConfig->toArray()
        );

        return redirect()->route('admin.fees.index')->with('success', 'Platform fee schedule updated successfully. Effective immediately for new quotes.');
    }
}
