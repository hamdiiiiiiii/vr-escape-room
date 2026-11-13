<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Optional list of the wrong answers a player tried on a puzzle.
     * Nullable, so every existing row and every current client still works.
     * The learning features use it to name the student's mistakes.
     */
    public function up(): void
    {
        Schema::table('puzzle_results', function (Blueprint $table) {
            $table->json('wrong_answers')->nullable()->after('hints_used');
        });
    }

    public function down(): void
    {
        Schema::table('puzzle_results', function (Blueprint $table) {
            $table->dropColumn('wrong_answers');
        });
    }
};
