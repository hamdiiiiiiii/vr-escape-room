<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A squad is a small team of classmates (2 to 4 players). A student who plays alone has no squad row.
        Schema::create('squads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leader_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('max_players')->default(4);
            $table->timestamps();
        });

        Schema::create('squad_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('squad_id')->constrained('squads')->cascadeOnDelete();
            // unique: a student can only be in one squad at a time
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('squad_members');
        Schema::dropIfExists('squads');
    }
};
