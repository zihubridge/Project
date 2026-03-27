<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('internal_swaps', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('swap_id')->index();

            // first leg (token -> base coin)
            // second leg (base coin -> token)
            $table->enum('leg', ['source', 'destination']);

            $table->unsignedBigInteger('blockchain_id')->index();

            $table->decimal('amount_in', 36, 18);
            $table->decimal('amount_out', 36, 18)->nullable();

            $table->string('tx_hash', 128)->nullable()->unique();

            $table->unsignedSmallInteger('internal_swap_state_id')->default(1);

            $table->json('meta')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('internal_swaps');
    }
};
