<?php

namespace App\Services\Admin;

use App\Models\AdminAuditLog;
use App\Models\AdminLoginOtp;
use App\Models\AdminUser;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class AdminAuthService
{
    public const OTP_TTL_MINUTES = 5;
    public const MAX_ATTEMPTS = 5;
    public const REQUEST_COOLDOWN_SECONDS = 60;

    /**
     * Request an OTP code.
     * Prevents email enumeration by returning a uniform success response.
     */
    public function requestOtp(string $email, string $ip, ?string $userAgent): array
    {
        $cleanEmail = strtolower(trim($email));
        $admin = AdminUser::where('email', $cleanEmail)->where('is_active', true)->first();

        // Check if there is an active recent OTP within cooldown
        $recentOtp = AdminLoginOtp::where('email', $cleanEmail)
            ->where('created_at', '>=', now()->subSeconds(self::REQUEST_COOLDOWN_SECONDS))
            ->first();

        if ($recentOtp) {
            throw ValidationException::withMessages([
                'email' => 'Please wait a moment before requesting another verification code.',
            ]);
        }

        // Invalidate prior unconsumed OTPs for this email
        AdminLoginOtp::where('email', $cleanEmail)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);

        if (!$admin) {
            // Emulate processing time to prevent timing attacks
            usleep(random_int(100000, 250000));
            return [
                'sent' => true,
                'message' => 'If this email is an authorized administrator, a verification code has been dispatched.',
            ];
        }

        // Generate cryptographically secure 6-digit OTP
        $rawOtp = (string) random_int(100000, 999999);
        $otpHash = hash_hmac('sha256', $rawOtp, (string) config('app.key'));

        $otpRecord = AdminLoginOtp::create([
            'email' => $cleanEmail,
            'otp_hash' => $otpHash,
            'expires_at' => now()->addMinutes(self::OTP_TTL_MINUTES),
            'attempts' => 0,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
        ]);

        // Dispatch email notification
        try {
            Mail::raw("Your ZihuBridge Admin one-time verification code is: {$rawOtp}\n\nThis code will expire in 5 minutes. If you did not request this code, ignore this email.", function ($message) use ($cleanEmail) {
                $message->to($cleanEmail)
                    ->subject('ZihuBridge Admin Verification Code');
            });
        } catch (\Throwable $e) {
            Log::error("Failed to dispatch admin OTP email to {$cleanEmail}: " . $e->getMessage());
        }

        // Always log for local audit/dev
        Log::info("Admin OTP generated for {$cleanEmail}: {$rawOtp}");

        $response = [
            'sent' => true,
            'message' => 'If this email is an authorized administrator, a verification code has been dispatched.',
        ];

        // In local/testing mode, return the code for development convenience
        if (app()->environment('local', 'testing') || config('app.debug')) {
            $response['dev_otp'] = $rawOtp;
        }

        return $response;
    }

    /**
     * Verify an OTP code and authenticate admin user.
     */
    public function verifyOtp(string $email, string $code, string $ip, ?string $userAgent): AdminUser
    {
        $cleanEmail = strtolower(trim($email));
        $admin = AdminUser::where('email', $cleanEmail)->where('is_active', true)->first();

        if (!$admin) {
            throw ValidationException::withMessages([
                'code' => 'Invalid or expired verification code.',
            ]);
        }

        $latestOtp = AdminLoginOtp::where('email', $cleanEmail)
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if (!$latestOtp) {
            throw ValidationException::withMessages([
                'code' => 'Verification code expired or not found. Please request a new code.',
            ]);
        }

        if ($latestOtp->attempts >= self::MAX_ATTEMPTS) {
            $latestOtp->update(['consumed_at' => now()]);
            throw ValidationException::withMessages([
                'code' => 'Maximum verification attempts exceeded. Please request a new code.',
            ]);
        }

        $latestOtp->increment('attempts');

        $expectedHash = hash_hmac('sha256', trim($code), (string) config('app.key'));
        if (!hash_equals($latestOtp->otp_hash, $expectedHash)) {
            $remaining = self::MAX_ATTEMPTS - $latestOtp->attempts;
            $msg = $remaining > 0
                ? "Invalid verification code. {$remaining} attempts remaining."
                : 'Maximum verification attempts exceeded. Please request a new code.';

            throw ValidationException::withMessages([
                'code' => $msg,
            ]);
        }

        // Successfully verified
        $latestOtp->update(['consumed_at' => now()]);

        $admin->update([
            'last_login_at' => now(),
            'last_login_ip' => $ip,
        ]);

        AdminAuditLog::log(
            $admin->id,
            'ADMIN_LOGIN_SUCCESS',
            'AdminUser',
            (string) $admin->id,
            null,
            ['ip' => $ip, 'user_agent' => $userAgent]
        );

        return $admin;
    }
}
