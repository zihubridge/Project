<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fallback_routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('destination_network_id')
                ->constrained('blockchain_networks')
                ->cascadeOnDelete();
            $table->foreignId('destination_token_id')
                ->nullable()
                ->constrained('crypto_assets')
                ->nullOnDelete();
            $table->foreignId('intermediate_asset_id')
                ->constrained('crypto_assets')
                ->cascadeOnDelete();
            $table->foreignId('first_leg_provider_id')
                ->constrained('swap_providers')
                ->cascadeOnDelete();
            $table->string('second_leg_adapter_key', 64)->default('stellar_sdex')->index();
            $table->unsignedInteger('priority')->default(10)->index();
            $table->decimal('min_amount', 36, 18)->nullable();
            $table->decimal('max_amount', 36, 18)->nullable();
            $table->unsignedInteger('max_slippage_bps')->default(300);
            $table->decimal('min_liquidity_usd', 18, 2)->nullable();
            $table->string('required_wallet_type', 64)->nullable();
            $table->string('account_requirements_note', 255)->nullable();
            $table->boolean('is_enabled')->default(true)->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['destination_network_id', 'is_enabled'], 'fb_routes_net_active_idx');
        });

        Schema::table('swap_quotes', function (Blueprint $table) {
            $table->boolean('is_multi_hop')->default(false)->after('swap_type');
            $table->foreignId('intermediate_asset_id')
                ->nullable()
                ->after('is_multi_hop')
                ->constrained('crypto_assets')
                ->nullOnDelete();
            $table->decimal('intermediate_amount', 36, 18)->nullable()->after('intermediate_asset_id');
            $table->string('intermediate_amount_base_units', 100)->nullable()->after('intermediate_amount');
            $table->string('second_leg_adapter_key', 64)->nullable()->after('intermediate_amount_base_units');
            $table->json('routing_metadata_json')->nullable()->after('fee_breakdown_json');
        });

        Schema::table('swap_orders', function (Blueprint $table) {
            $table->boolean('is_multi_hop')->default(false)->after('swap_provider_id');
            $table->foreignId('intermediate_asset_id')
                ->nullable()
                ->after('is_multi_hop')
                ->constrained('crypto_assets')
                ->nullOnDelete();
            $table->decimal('intermediate_amount', 36, 18)->nullable()->after('intermediate_asset_id');
            $table->string('second_leg_adapter_key', 64)->nullable()->after('intermediate_amount');
            $table->string('second_leg_status', 32)->default('NONE')->after('status');
            $table->string('second_leg_tx_hash', 191)->nullable()->after('destination_tx_hash');
            $table->string('second_leg_explorer_url', 255)->nullable()->after('second_leg_tx_hash');
            $table->json('second_leg_payload_json')->nullable()->after('provider_context_json');
        });
    }

    public function down(): void
    {
        Schema::table('swap_orders', function (Blueprint $table) {
            $table->dropForeign(['intermediate_asset_id']);
            $table->dropColumn([
                'is_multi_hop',
                'intermediate_asset_id',
                'intermediate_amount',
                'second_leg_adapter_key',
                'second_leg_status',
                'second_leg_tx_hash',
                'second_leg_explorer_url',
                'second_leg_payload_json',
            ]);
        });

        Schema::table('swap_quotes', function (Blueprint $table) {
            $table->dropForeign(['intermediate_asset_id']);
            $table->dropColumn([
                'is_multi_hop',
                'intermediate_asset_id',
                'intermediate_amount',
                'intermediate_amount_base_units',
                'second_leg_adapter_key',
                'routing_metadata_json',
            ]);
        });

        Schema::dropIfExists('fallback_routes');
    }
};

