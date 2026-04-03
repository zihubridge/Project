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
        Schema::create('tokens', function (Blueprint $table) {
            $table->id();
            $table->string('name', 64);
            $table->string('asset_code', 64);
            $table->string('image')->nullable();
            $table->unsignedBigInteger('blockchain_id')->index();

            $table->string('issuer_address')->nullable();
            $table->string('pool_id', 64)->nullable();

            $table->string('contract_address', 128)->nullable();
            $table->unsignedSmallInteger('decimals')->default(7);

            $table->json('meta')->nullable();
            $table->tinyInteger('status')->default(1); // 1=active, 0=inactive

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tokens');
    }
};
