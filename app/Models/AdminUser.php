<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class AdminUser extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'admin_users';

    protected $fillable = [
        'name',
        'email',
        'role',
        'is_active',
        'last_login_at',
        'last_login_ip',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
    ];

    public const ROLE_SUPER_ADMIN = 'SUPER_ADMIN';
    public const ROLE_FINANCE_ADMIN = 'FINANCE_ADMIN';
    public const ROLE_LISTING_MANAGER = 'LISTING_MANAGER';

    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    public function isFinanceAdmin(): bool
    {
        return in_array($this->role, [self::ROLE_SUPER_ADMIN, self::ROLE_FINANCE_ADMIN], true);
    }

    public function isListingManager(): bool
    {
        return in_array($this->role, [self::ROLE_SUPER_ADMIN, self::ROLE_LISTING_MANAGER], true);
    }

    public function auditLogs()
    {
        return $this->hasMany(AdminAuditLog::class, 'admin_user_id');
    }
}
