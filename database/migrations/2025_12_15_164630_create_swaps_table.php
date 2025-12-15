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

            $table->uuid('swap_uuid')->unique(); // public ID ( b4c1d9a2-5b24-4c1a-8e9f-2f1a3a7c8e01 )
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('from_blockchain_id')->index();
            $table->unsignedBigInteger('to_blockchain_id')->index();

            $table->unsignedBigInteger('from_token_id')->index();
            $table->unsignedBigInteger('to_token_id')->index();

            $table->decimal('from_amount', 36, 18);
            $table->decimal('to_amount_estimated', 36, 18)->nullable();
            $table->decimal('to_amount_final', 36, 18)->nullable();

            $table->string('user_destination_address', 128);

            $table->decimal('slippage_bps', 10, 2)->default(50); // 0.50% = 50 bps
            $table->decimal('fee_amount', 36, 18)->default(0);
            $table->string('fee_token_symbol', 32)->nullable();

            $table->string('status', 32)->default('waiting_deposit');
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
