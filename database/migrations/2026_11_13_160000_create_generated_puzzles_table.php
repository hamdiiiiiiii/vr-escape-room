<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Puzzles written by the AI. They start as "pending" and only an
     * instructor can approve them. Students never see pending or rejected ones.
     */
    public function up(): void
    {
        Schema::create('generated_puzzles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            // The student it was made for. Null means "for the whole class".
            $table->foreignId('student_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('concept_tag');
            $table->string('type');
            $table->string('difficulty');
            $table->json('content');
            $table->string('status')->default('pending');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'requested_by']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generated_puzzles');
    }
};
