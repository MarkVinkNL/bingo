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
        Schema::table('bingo_battles', function (Blueprint $table) {
            $table->foreignId('winner_id')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            $table->timestamp('won_at')->nullable()->after('winner_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bingo_battles', function (Blueprint $table) {
            $table->dropForeign(['winner_id']);
            $table->dropColumn('won_at');
        });
    }
};
