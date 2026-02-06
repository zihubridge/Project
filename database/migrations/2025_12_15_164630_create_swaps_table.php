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
        Schema::create('swaps', function (Blueprint $table) {
            $table->id();

            $table->uuid('swap_uuid')->unique();
            $table->unsignedBigInteger('user_id')->nullable()->index();

            $table->unsignedBigInteger('from_blockchain_id')->index();
            $table->unsignedBigInteger('to_blockchain_id')->index();

            $table->unsignedBigInteger('from_token_id')->index();
            $table->unsignedBigInteger('to_token_id')->index();

            $table->decimal('from_amount', 36, 18);
            $table->decimal('to_amount_estimated', 36, 18)->nullable();
            $table->decimal('to_amount_final', 36, 18)->nullable();

            $table->decimal('expected_xrp_amount', 36, 18)->nullable();
            $table->decimal('expected_xlm_amount', 36, 18)->nullable();

            // deposit routing (memo / tag)
            $table->string('routing_type', 16);
            $table->string('routing_value', 64)->unique();

            // destination
            $table->string('destination_address', 128);
            $table->string('destination_tag', 64)->nullable();

            // execution & fees
            $table->unsignedInteger('slippage_bps')->default(50);
            $table->decimal('fee_amount', 36, 18)->default(0);
            $table->unsignedBigInteger('fee_token_id')->nullable();

            $table->unsignedSmallInteger('swap_state_id')->default(1);

            // The hash of the transaction WE sent to ChangeNOW
            $table->string('external_tx_id')->nullable();
    
            // The hash of the transaction ChangeNOW sends to US
            $table->string('incoming_tx_id')->nullable();

            $table->string('failure_reason', 255)->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    { 
        Schema::dropIfExists('swaps');
    }
};
