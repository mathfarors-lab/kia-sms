<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tracks WHO has responded, deliberately separate from survey_responses
     * (which holds the answer content). For an anonymous survey these two
     * tables are not joinable back to each other — this one has no content,
     * that one has no respondent — so duplicate-submission prevention and
     * completion-rate reporting never need to touch the answer content.
     */
    public function up(): void
    {
        Schema::create('survey_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('completed_at');

            $table->unique(['survey_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_completions');
    }
};
