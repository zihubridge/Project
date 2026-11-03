<?php

namespace Database\Seeders;

use App\Models\AdminUser;
use App\Models\PlatformFeeConfiguration;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Initial Super Admin
        $defaultAdminEmail = env('ADMIN_DEFAULT_EMAIL', 'admin@zihubridge.com');
        $admin = AdminUser::firstOrCreate(
            ['email' => strtolower(trim($defaultAdminEmail))],
            [
                'name' => 'Primary Super Admin',
                'role' => AdminUser::ROLE_SUPER_ADMIN,
                'is_active' => true,
            ]
        );

        // 2. Initial Platform Fee Configuration (100 BPS = 1.00%)
        if (PlatformFeeConfiguration::count() === 0) {
            PlatformFeeConfiguration::create([
                'fee_bps' => (int) env('NEAR_INTENTS_PARTNER_FEE_BPS', 100),
                'fee_recipient' => env('NEAR_INTENTS_PARTNER_FEE_RECIPIENT'),
                'is_enabled' => true,
                'effective_from' => now(),
                'changed_by_admin_id' => $admin->id,
                'reason' => 'Initial platform launch fee configuration (1.00%)',
            ]);
        }
    }
}
