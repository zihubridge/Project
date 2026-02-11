<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('swap_deposits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('swap_id')->index();

            // $table->unsignedBigInteger('platform_wallet_id')->index();

            // where user sent funds
            $table->string('deposit_address', 128);

            // routing identity (memo / tag / muxed)
            $table->string('deposit_routing_type', 16);
            $table->string('deposit_routing_value', 64)->unique();

            $table->unsignedBigInteger('expected_token_id')->index();
            $table->decimal('expected_amount', 36, 18);

            $table->decimal('received_amount', 36, 18)->nullable();
            $table->string('tx_hash', 128)->nullable()->unique();
            $table->string('sender_address', 128)->nullable();

            $table->unsignedSmallInteger('deposit_state_id')->default(1);
            $table->unsignedBigInteger('detected_ledger')->nullable();

            $table->timestamp('received_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('swap_deposits');
    }
};
