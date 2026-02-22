<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bingo_cell_value_suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bingo_subject_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('bingo_cell_value_suggestion_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bingo_cell_value_suggestion_id')->constrained()->cascadeOnDelete();
            $table->string('value');
            $table->unsignedInteger('sort_order')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bingo_cell_value_suggestion_values');
        Schema::dropIfExists('bingo_cell_value_suggestions');
    }
};
