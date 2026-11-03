<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('swap_providers', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 64)->unique();
            $table->string('display_name', 100);
            $table->string('driver_class', 191);
            $table->boolean('is_enabled')->default(true)->index();
            $table->unsignedInteger('priority')->default(100)->index();
            $table->json('config_json')->nullable();
            $table->timestamp('last_catalog_synced_at')->nullable();
            $table->timestamps();
        });

        Schema::create('blockchain_networks', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 64)->unique();
            $table->string('display_name', 100);
            $table->string('chain_family', 32)->default('other')->index();
            $table->string('native_symbol', 32)->nullable();
            $table->string('evm_chain_id', 32)->nullable();
            $table->boolean('requires_memo_or_tag')->default(false);
            $table->string('address_regex', 191)->nullable();
            $table->string('explorer_tx_url_pattern', 191)->nullable();
            $table->string('explorer_address_url_pattern', 191)->nullable();
            $table->string('logo_url', 191)->nullable();
            $table->boolean('is_enabled')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('crypto_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blockchain_network_id')
                ->constrained('blockchain_networks')
                ->cascadeOnDelete();
            $table->string('symbol', 64)->index();
            $table->string('display_name', 128);
            $table->boolean('is_native')->default(false);
            $table->string('contract_or_issuer', 191)->nullable();
            $table->unsignedTinyInteger('decimals')->default(18);
            $table->decimal('current_price_usd', 24, 8)->nullable();
            $table->timestamp('price_updated_at')->nullable();
            $table->string('logo_url', 191)->nullable();
            $table->boolean('is_enabled')->default(true)->index();
            $table->timestamps();

            $table->unique(
                ['blockchain_network_id', 'symbol', 'contract_or_issuer'],
                'crypto_assets_network_symbol_contract_unique'
            );
        });

        Schema::create('provider_supported_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('swap_provider_id')
                ->constrained('swap_providers')
                ->cascadeOnDelete();
            $table->foreignId('crypto_asset_id')
                ->constrained('crypto_assets')
                ->cascadeOnDelete();
            $table->string('provider_asset_id', 191);
            $table->string('provider_chain_key', 64)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->json('provider_metadata_json')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->unique(['swap_provider_id', 'provider_asset_id'], 'prov_supp_assets_provider_asset_unique');
            $table->index(['swap_provider_id', 'crypto_asset_id', 'is_active'], 'prov_supp_assets_lookup_idx');
        });

        Schema::create('swap_quotes', function (Blueprint $table) {
            $table->id();
            $table->uuid('quote_uuid')->unique();
            $table->foreignId('swap_provider_id')
                ->constrained('swap_providers')
                ->cascadeOnDelete();
            $table->foreignId('source_asset_id')
                ->constrained('crypto_assets')
                ->cascadeOnDelete();
            $table->foreignId('destination_asset_id')
                ->constrained('crypto_assets')
                ->cascadeOnDelete();
            $table->string('swap_type', 32)->default('EXACT_INPUT');
            $table->decimal('amount_in', 36, 18);
            $table->string('amount_in_base_units', 100);
            $table->decimal('expected_amount_out', 36, 18);
            $table->string('expected_amount_out_base_units', 100);
            $table->decimal('min_amount_out', 36, 18);
            $table->string('min_amount_out_base_units', 100);
            $table->decimal('exchange_rate', 36, 18);
            $table->decimal('amount_in_usd', 24, 8)->nullable();
            $table->decimal('amount_out_usd', 24, 8)->nullable();
            $table->unsignedInteger('slippage_bps')->default(100);
            $table->unsignedInteger('platform_fee_bps')->default(0);
            $table->unsignedInteger('estimated_duration_seconds')->nullable();
            $table->timestamp('expires_at')->index();
            $table->json('fee_breakdown_json')->nullable();
            $table->json('provider_quote_payload_json')->nullable();
            $table->timestamps();
        });

        Schema::create('swap_orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('swap_uuid')->unique();
            $table->foreignId('swap_quote_id')
                ->unique()
                ->constrained('swap_quotes')
                ->cascadeOnDelete();
            $table->foreignId('swap_provider_id')
                ->constrained('swap_providers')
                ->cascadeOnDelete();
            $table->foreignId('source_network_id')
                ->constrained('blockchain_networks')
                ->cascadeOnDelete();
            $table->foreignId('destination_network_id')
                ->constrained('blockchain_networks')
                ->cascadeOnDelete();
            $table->foreignId('source_asset_id')
                ->constrained('crypto_assets')
                ->cascadeOnDelete();
            $table->foreignId('destination_asset_id')
                ->constrained('crypto_assets')
                ->cascadeOnDelete();

            $table->decimal('amount_in', 36, 18);
            $table->string('amount_in_base_units', 100);
            $table->decimal('expected_amount_out', 36, 18);
            $table->decimal('min_amount_out', 36, 18);
            $table->decimal('actual_amount_out', 36, 18)->nullable();
            $table->decimal('refunded_amount', 36, 18)->nullable();
            $table->unsignedInteger('slippage_bps')->default(100);

            $table->string('connected_wallet_address', 191)->nullable()->index();
            $table->string('recipient_address', 191)->index();
            $table->string('recipient_memo', 128)->nullable();
            $table->string('refund_address', 191)->index();
            $table->string('refund_memo', 128)->nullable();

            $table->string('deposit_address', 191)->index();
            $table->string('deposit_memo', 128)->nullable();

            $table->string('source_tx_hash', 191)->nullable()->index();
            $table->string('source_explorer_url', 191)->nullable();
            $table->string('destination_tx_hash', 191)->nullable()->index();
            $table->string('destination_explorer_url', 191)->nullable();
            $table->string('refund_tx_hash', 191)->nullable();

            $table->string('status', 48)->index();
            $table->string('provider_status', 64)->nullable();
            $table->string('error_code', 64)->nullable();
            $table->text('failure_reason')->nullable();

            $table->timestamp('deposit_expires_at')->index();
            $table->timestamp('deposit_submitted_at')->nullable();
            $table->timestamp('deposit_detected_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('last_polled_at')->nullable();
            $table->json('provider_context_json')->nullable();
            $table->timestamps();
        });

        Schema::create('swap_execution_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('swap_order_id')
                ->constrained('swap_orders')
                ->cascadeOnDelete();
            $table->unsignedTinyInteger('step_number');
            $table->string('step_key', 48);
            $table->string('label', 128);
            $table->string('status', 32)->default('PENDING');
            $table->string('network_slug', 64)->nullable();
            $table->string('tx_hash', 191)->nullable();
            $table->string('explorer_url', 191)->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('details_json')->nullable();
            $table->timestamps();

            $table->unique(['swap_order_id', 'step_number'], 'swap_exec_steps_order_num_unique');
        });

        Schema::create('swap_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('swap_order_id')
                ->constrained('swap_orders')
                ->cascadeOnDelete();
            $table->string('previous_status', 48)->nullable();
            $table->string('new_status', 48);
            $table->string('provider_status', 64)->nullable();
            $table->string('triggered_by', 32)->default('SYSTEM');
            $table->string('idempotency_key', 191)->unique();
            $table->text('message')->nullable();
            $table->json('metadata_json')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['swap_order_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('swap_status_histories');
        Schema::dropIfExists('swap_execution_steps');
        Schema::dropIfExists('swap_orders');
        Schema::dropIfExists('swap_quotes');
        Schema::dropIfExists('provider_supported_assets');
        Schema::dropIfExists('crypto_assets');
        Schema::dropIfExists('blockchain_networks');
        Schema::dropIfExists('swap_providers');
    }
};

