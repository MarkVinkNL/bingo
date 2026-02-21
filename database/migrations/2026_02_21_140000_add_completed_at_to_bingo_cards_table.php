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
        Schema::table('bingo_cards', function (Blueprint $table) {
            $table->timestamp('completed_at')->nullable()->after('generated_at');
        });

        Schema::table('bingo_cards', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'bingo_subject_id']);
        });

        Schema::table('bingo_cards', function (Blueprint $table) {
            $table->index(['user_id', 'bingo_subject_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bingo_cards', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'bingo_subject_id']);
        });

        Schema::table('bingo_cards', function (Blueprint $table) {
            $table->unique(['user_id', 'bingo_subject_id']);
        });

        Schema::table('bingo_cards', function (Blueprint $table) {
            $table->dropColumn('completed_at');
        });
    }
};
