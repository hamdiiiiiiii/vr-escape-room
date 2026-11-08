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
        Schema::create('puzzle_results', function (Blueprint $table) {
           $table->id();
$table->foreignId('game_session_id')->constrained()->cascadeOnDelete();
$table->string('puzzle_id');
$table->string('concept_tag');
$table->boolean('solved');
$table->unsignedTinyInteger('attempts');
$table->unsignedInteger('time_seconds');
$table->unsignedTinyInteger('hints_used')->default(0);
$table->unsignedTinyInteger('score')->default(0);
$table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('puzzle_results');
    }
};
