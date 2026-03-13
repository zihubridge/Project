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
        Schema::create('chain_states', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('blockchain_id')->index();
            $table->unsignedBigInteger('chain_state_key_id')->index();
            $table->text('value');      // cursor string / ledger number
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chain_states');
    }
};
