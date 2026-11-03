<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\AdminUser;
use Illuminate\Http\Request;

class AdminSettingsController extends Controller
{
    public function index(Request $request)
    {
        $admins = AdminUser::orderBy('role')->orderBy('name')->get();
        $auditLogs = AdminAuditLog::with('adminUser')->latest('id')->paginate(30);

        return view('admin.settings.index', [
            'admins' => $admins,
            'auditLogs' => $auditLogs,
        ]);
    }

    public function storeAdmin(Request $request)
    {
        $currentAdmin = $request->attributes->get('current_admin');

        // Only Super Admin can register other administrators
        if (!$currentAdmin || !$currentAdmin->isSuperAdmin()) {
            abort(403, 'Only Super Admin can invite and authorize administrators.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:191', 'unique:admin_users,email'],
            'role' => ['required', 'string', 'in:SUPER_ADMIN,FINANCE_ADMIN,LISTING_MANAGER'],
        ]);

        $newAdmin = AdminUser::create([
            'name' => trim($validated['name']),
            'email' => strtolower(trim($validated['email'])),
            'role' => $validated['role'],
            'is_active' => true,
        ]);

        AdminAuditLog::log(
            $currentAdmin->id,
            'ADMIN_CREATED',
            'AdminUser',
            (string) $newAdmin->id,
            null,
            $newAdmin->toArray()
        );

        return redirect()->route('admin.settings.index')->with('success', "Administrator '{$newAdmin->name}' authorized successfully with role {$newAdmin->role}.");
    }

    public function toggleAdminStatus(Request $request, int $id)
    {
        $currentAdmin = $request->attributes->get('current_admin');

        if (!$currentAdmin || !$currentAdmin->isSuperAdmin()) {
            abort(403, 'Only Super Admin can modify administrator access.');
        }

        $target = AdminUser::findOrFail($id);

        if ($target->id === $currentAdmin->id) {
            return back()->with('error', 'You cannot deactivate your own administrative account.');
        }

        $old = $target->toArray();
        $target->is_active = !$target->is_active;
        $target->save();

        AdminAuditLog::log(
            $currentAdmin->id,
            'ADMIN_STATUS_TOGGLED',
            'AdminUser',
            (string) $target->id,
            $old,
            $target->fresh()->toArray()
        );

        $stateText = $target->is_active ? 'activated' : 'deactivated';
        return redirect()->route('admin.settings.index')->with('success', "Administrator {$target->name} has been {$stateText}.");
    }
}
