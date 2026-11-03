<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Admin Users
        Schema::create('admin_users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('email', 191)->unique();
            $table->string('role', 32)->default('SUPER_ADMIN')->index(); // SUPER_ADMIN, FINANCE_ADMIN, LISTING_MANAGER
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            $table->timestamps();
        });

        // 2. Admin Login OTPs (Single-use, 5-minute expiry, hashed)
        Schema::create('admin_login_otps', function (Blueprint $table) {
            $table->id();
            $table->string('email', 191)->index();
            $table->string('otp_hash', 191);
            $table->timestamp('expires_at')->index();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('consumed_at')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['email', 'consumed_at', 'expires_at'], 'admin_otps_validity_idx');
        });

        // 3. Platform Fee Configurations & Audit
        Schema::create('platform_fee_configurations', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('fee_bps')->default(100); // 100 bps = 1.00%
            $table->string('fee_recipient', 191)->nullable();
            $table->boolean('is_enabled')->default(true)->index();
            $table->timestamp('effective_from')->nullable()->index();
            $table->foreignId('changed_by_admin_id')
                ->nullable()
                ->constrained('admin_users')
                ->nullOnDelete();
            $table->text('reason')->nullable();
            $table->timestamps();
        });

        // 4. Platform Revenue Records (Accounting Ledger)
        Schema::create('platform_revenue_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('swap_order_id')
                ->constrained('swap_orders')
                ->cascadeOnDelete();
            $table->foreignId('swap_provider_id')
                ->constrained('swap_providers')
                ->cascadeOnDelete();
            $table->unsignedInteger('fee_bps')->default(0);
            $table->decimal('expected_fee_amount', 36, 18)->default(0);
            $table->decimal('confirmed_fee_amount', 36, 18)->nullable();
            $table->string('fee_asset_symbol', 64);
            $table->decimal('fee_asset_usd', 24, 8)->nullable();
            $table->string('fee_recipient', 191)->nullable();
            $table->string('status', 32)->default('PENDING')->index(); // PENDING, COLLECTED, FAILED, UNAVAILABLE, REFUNDED
            $table->string('fee_tx_hash', 191)->nullable();
            $table->timestamp('collected_at')->nullable()->index();
            $table->timestamps();

            $table->unique('swap_order_id', 'rev_records_swap_order_unique');
        });

        // 5. Token Listing Requests Pipeline
        Schema::create('token_listing_requests', function (Blueprint $table) {
            $table->id();
            $table->string('project_name', 128);
            $table->string('contact_email', 191);
            $table->string('contact_telegram_or_x', 128)->nullable();
            $table->string('token_name', 128);
            $table->string('token_symbol', 64)->index();
            $table->foreignId('blockchain_network_id')
                ->constrained('blockchain_networks')
                ->cascadeOnDelete();
            $table->string('contract_or_issuer', 191)->nullable();
            $table->unsignedTinyInteger('decimals')->default(18);
            $table->string('logo_url', 255)->nullable();
            $table->string('website_url', 255)->nullable();
            $table->text('description')->nullable();
            $table->decimal('listing_fee_usd', 12, 2)->default(0);
            $table->string('payment_status', 32)->default('PENDING_PAYMENT')->index(); // PENDING_PAYMENT, PAID, REFUNDED
            $table->string('payment_tx_hash', 191)->nullable();
            $table->string('review_status', 32)->default('UNDER_REVIEW')->index(); // DRAFT, PENDING_PAYMENT, PAID, UNDER_REVIEW, NEEDS_CHANGES, APPROVED, REJECTED, PUBLISHED, SUSPENDED
            $table->text('reviewer_notes')->nullable();
            $table->foreignId('reviewed_by_admin_id')
                ->nullable()
                ->constrained('admin_users')
                ->nullOnDelete();
            $table->foreignId('published_crypto_asset_id')
                ->nullable()
                ->constrained('crypto_assets')
                ->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        // 6. Admin Audit Logs (Immutable security tracking)
        Schema::create('admin_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_user_id')
                ->nullable()
                ->constrained('admin_users')
                ->nullOnDelete();
            $table->string('action', 64)->index(); // e.g. FEE_UPDATED, TOKEN_APPROVED, ADMIN_LOGIN
            $table->string('resource_type', 64)->index(); // e.g. PlatformFee, TokenListing, CryptoAsset
            $table->string('resource_id', 64)->nullable();
            $table->json('old_values_json')->nullable();
            $table->json('new_values_json')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });

        // 7. Advertising Campaigns (Phase 6 Schema Foundation)
        Schema::create('advertising_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('title', 128);
            $table->string('advertiser_name', 128);
            $table->string('advertiser_email', 191);
            $table->string('campaign_type', 32)->default('BANNER'); // BANNER, FEATURED_TOKEN, SPONSORED
            $table->foreignId('target_asset_id')
                ->nullable()
                ->constrained('crypto_assets')
                ->nullOnDelete();
            $table->string('banner_image_url', 255)->nullable();
            $table->string('target_url', 255)->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status', 32)->default('DRAFT')->index(); // DRAFT, ACTIVE, PAUSED, COMPLETED
            $table->decimal('budget_usd', 12, 2)->default(0);
            $table->unsignedBigInteger('impressions_count')->default(0);
            $table->unsignedBigInteger('clicks_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('advertising_campaigns');
        Schema::dropIfExists('admin_audit_logs');
        Schema::dropIfExists('token_listing_requests');
        Schema::dropIfExists('platform_revenue_records');
        Schema::dropIfExists('platform_fee_configurations');
        Schema::dropIfExists('admin_login_otps');
        Schema::dropIfExists('admin_users');
    }
};
