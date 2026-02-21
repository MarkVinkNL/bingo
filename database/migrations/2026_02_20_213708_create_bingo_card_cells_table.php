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
        Schema::create('bingo_card_cells', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bingo_card_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bingo_cell_value_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position'); // 0 to N²-1
            $table->boolean('is_marked')->default(false);
            $table->timestamp('marked_at')->nullable();
            $table->timestamps();

            $table->unique(['bingo_card_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bingo_card_cells');
    }
};
