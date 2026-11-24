<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('puzzle_results', function (Blueprint $table) {
            // true when the server checked the player's answer itself
            $table->boolean('verified')->default(false)->after('solved');
        });
    }

    public function down(): void
    {
        Schema::table('puzzle_results', function (Blueprint $table) {
            $table->dropColumn('verified');
        });
    }
};
