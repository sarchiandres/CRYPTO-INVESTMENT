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
    Schema::create('price_histories', function (Blueprint $table) {

        $table->id();

        $table->foreignId('cryptocurrency_id')
            ->constrained()
            ->cascadeOnDelete();

        $table->decimal('price',20,8);

        $table->decimal('volume_24h',20,2);

        $table->decimal('percent_change_24h',10,2);

        $table->decimal('market_cap',20,2);

        $table->timestamp('captured_at');

        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('price_histories');
    }
};
