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

            $table->unsignedBigInteger('from_blockchain_id')->index();
            $table->unsignedBigInteger('to_blockchain_id')->index();

            $table->unsignedBigInteger('from_token_id')->index();
            $table->unsignedBigInteger('to_token_id')->index();

            $table->decimal('from_token_amount', 36, 18);
            $table->decimal('to_estimated_token_amount', 36, 18)->nullable();
            $table->decimal('to_final_token_amount', 36, 18)->nullable();

            // destination
            $table->string('destination_address', 128);
            $table->string('destination_tag', 64)->nullable();

            // execution & fees
            $table->unsignedInteger('slippage_bps')->default(50);
            $table->decimal('fee_amount', 36, 18)->default(0);
            $table->unsignedBigInteger('fee_token_id')->nullable();

            $table->unsignedSmallInteger('swap_state_id')->default(1);
            $table->string('failure_reason', 255)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

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
