<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminLoginOtp extends Model
{
    protected $table = 'admin_login_otps';

    protected $fillable = [
        'email',
        'otp_hash',
        'expires_at',
        'attempts',
        'consumed_at',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'consumed_at' => 'datetime',
        'attempts' => 'integer',
    ];

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isConsumed(): bool
    {
        return $this->consumed_at !== null;
    }

    public function isMaxAttemptsReached(): bool
    {
        return $this->attempts >= 5;
    }
}
