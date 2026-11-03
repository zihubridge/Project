<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\AdminUser;
use App\Services\Admin\AdminAuthService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AdminAuthController extends Controller
{
    public function __construct(
        protected AdminAuthService $authService
    ) {}

    public function showLoginForm()
    {
        if (session()->has('admin_user_id')) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login');
    }

    public function sendOtp(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:191'],
        ]);

        try {
            $result = $this->authService->requestOtp(
                $validated['email'],
                $request->ip(),
                $request->userAgent()
            );

            session()->put('admin_auth_email', strtolower(trim($validated['email'])));

            if (isset($result['dev_otp'])) {
                session()->flash('dev_otp', $result['dev_otp']);
            }

            return redirect()->route('admin.login.verify')->with('status', $result['message']);
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        }
    }

    public function showVerifyForm(Request $request)
    {
        $email = session('admin_auth_email', $request->query('email'));

        if (!$email) {
            return redirect()->route('admin.login');
        }

        return view('admin.auth.verify', [
            'email' => $email,
            'devOtp' => session('dev_otp'),
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:191'],
            'code' => ['required', 'string', 'size:6'],
        ]);

        try {
            $admin = $this->authService->verifyOtp(
                $validated['email'],
                $validated['code'],
                $request->ip(),
                $request->userAgent()
            );

            // Secure session regeneration
            $request->session()->regenerate();
            $request->session()->put('admin_user_id', $admin->id);
            $request->session()->put('admin_role', $admin->role);
            $request->session()->put('admin_email', $admin->email);
            $request->session()->forget('admin_auth_email');

            return redirect()->intended(route('admin.dashboard'))->with('success', "Welcome back, {$admin->name}.");
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        }
    }

    public function logout(Request $request)
    {
        $adminId = $request->session()->get('admin_user_id');
        if ($adminId) {
            AdminAuditLog::log($adminId, 'ADMIN_LOGOUT', 'AdminUser', (string) $adminId);
        }

        $request->session()->forget(['admin_user_id', 'admin_role', 'admin_email']);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')->with('status', 'You have been securely logged out.');
    }
}
