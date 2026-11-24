<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // the public player name shown in the game (built from approved words, never free text)
            $table->string('callsign', 40)->nullable()->unique()->after('name');
            // the chosen look: ids from config/avatar.php (head, outfit, headgear, gear, suit_color, team_color, name parts)
            $table->json('avatar')->nullable()->after('callsign');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['callsign']);
            $table->dropColumn(['callsign', 'avatar']);
        });
    }
};
