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
        Schema::create('swap_exchanges', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('swap_id')->index();

            $table->unsignedBigInteger('from_token_id')->index();
            $table->unsignedBigInteger('to_token_id')->index();

            $table->unsignedTinyInteger('exchange_provider_id');

            // Exchange order reference
            $table->string('exchange_order_id', 128)->nullable();

            // Where YOU must send funds
            $table->string('payin_address', 128);
            $table->string('payin_memo', 64)->nullable();
            $table->string('payin_tx_id', 128)->nullable();

            // Provider → Platform
            $table->string('payout_address', 128)->nullable();
            $table->string('payout_memo', 64)->nullable();
            $table->string('payout_tx_id', 128)->nullable();

            $table->decimal('from_amount', 36, 18)->nullable();
            $table->decimal('expected_amount', 36, 18)->nullable();
            $table->decimal('received_amount', 36, 18)->nullable();

            // Status tracking
            $table->unsignedSmallInteger('swap_exchange_state_id')->default(1);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('swap_exchanges');
    }
};
