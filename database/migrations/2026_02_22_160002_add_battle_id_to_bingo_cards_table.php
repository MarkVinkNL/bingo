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
            $table->foreignId('battle_id')->nullable()->after('user_id')->constrained('bingo_battles')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bingo_cards', function (Blueprint $table) {
            $table->dropForeign(['battle_id']);
        });
    }
};
