<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bingo_subject_suggestions', function (Blueprint $table) {
            $table->id();
            $table->string('suggested_name');
            $table->string('suggested_slug')->nullable();
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('approved_bingo_subject_id')->nullable()->constrained('bingo_subjects')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('bingo_subject_suggestion_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bingo_subject_suggestion_id')->constrained()->cascadeOnDelete();
            $table->string('value');
            $table->unsignedInteger('sort_order')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bingo_subject_suggestion_values');
        Schema::dropIfExists('bingo_subject_suggestions');
    }
};
