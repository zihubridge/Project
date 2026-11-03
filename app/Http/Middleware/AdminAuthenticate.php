<?php

namespace App\Http\Middleware;

use App\Models\AdminUser;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class AdminAuthenticate
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $adminId = $request->session()->get('admin_user_id');

        if (!$adminId) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated admin session.'], 401);
            }
            return redirect()->guest(route('admin.login'));
        }

        $admin = AdminUser::find($adminId);

        if (!$admin || !$admin->is_active) {
            $request->session()->forget(['admin_user_id', 'admin_role', 'admin_email']);
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Admin account inactive or revoked.'], 403);
            }
            return redirect()->route('admin.login')->withErrors(['email' => 'Admin session has expired or been revoked.']);
        }

        // Check specific role restrictions if passed (e.g. 'SUPER_ADMIN')
        if (!empty($roles) && !in_array($admin->role, $roles, true) && !$admin->isSuperAdmin()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Insufficient admin role permissions.'], 403);
            }
            abort(403, 'Unauthorized. Your admin role (' . $admin->role . ') cannot access this resource.');
        }

        // Share current authenticated admin with views
        View::share('currentAdmin', $admin);
        $request->attributes->set('current_admin', $admin);

        return $next($request);
    }
}
