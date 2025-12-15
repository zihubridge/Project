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
        Schema::create('platform_wallets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('blockchain_id')->index();

            $table->string('label', 50);
            $table->string('public_address', 128)->unique();
            $table->text('secret_encrypted')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_Testnet')->default(false);

            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('platform_wallets');
    }
};
