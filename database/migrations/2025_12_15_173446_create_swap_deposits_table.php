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
            $table->unsignedBigInteger('swap_id')->unique();

            $table->unsignedBigInteger('platform_wallet_id')->index(); // which hot wallet is receiving

            // deposit routing identity:
            // Stellar: muxed_id + muxed_address
            // XRPL: destination_tag
            $table->string('deposit_address', 128); // the "send to" address (M... or r... or G...)
            $table->string('deposit_tag', 64)->nullable(); // muxed_id or destination tag (store as string)
            $table->string('deposit_memo', 64)->nullable(); // if you ever use memo in other flows

            $table->unsignedBigInteger('expected_token_id')->index();
            $table->decimal('expected_amount', 36, 18);

            $table->decimal('received_amount', 36, 18)->nullable();
            $table->string('tx_hash', 128)->nullable()->unique();
            $table->string('sender_address', 128)->nullable();
            $table->timestamp('received_at')->nullable();

            $table->string('status', 32)->default('waiting'); // waiting|confirmed|expired|failed

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
