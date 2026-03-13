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
        Schema::create('swap_payouts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('swap_id')->unique();

            $table->unsignedBigInteger('blockchain_id')->index();
            $table->unsignedBigInteger('token_id')->index();
            $table->decimal('amount', 36, 18);

            $table->string('from_address', 128)->nullable();
            $table->string('to_address', 128);

            $table->string('tx_hash', 128)->nullable()->unique();
            $table->unsignedSmallInteger('swap_payout_state_id')->default(1);

            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('swap_payouts');
    }
};
