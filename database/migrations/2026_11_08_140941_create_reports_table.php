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
        Schema::create('reports', function (Blueprint $table) {
           $table->id();
$table->foreignId('game_session_id')->unique()->constrained()->cascadeOnDelete();
$table->unsignedInteger('total_time_seconds');
$table->unsignedTinyInteger('session_score');
$table->string('level');
$table->json('concept_scores');
$table->json('ai_analysis')->nullable();
$table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
