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
        Schema::create('swap_legs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('swap_id')->unique()->index();

            $table->unsignedSmallInteger('step_no');
            $table->unsignedBigInteger('execution_types');

            $table->unsignedBigInteger('from_token_id')->nullable()->index();
            $table->unsignedBigInteger('to_token_id')->nullable()->index();

            $table->decimal('from_amount', 36, 18)->nullable();
            $table->decimal('to_amount_expected', 36, 18)->nullable();
            $table->decimal('to_amount_actual', 36, 18)->nullable();

            $table->string('external_provider', 40)->nullable();
            $table->string('external_id', 100)->nullable();
            $table->unsignedSmallInteger('swap_states')->default(0);

            $table->json('meta')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('swap_legs');
    }
};
