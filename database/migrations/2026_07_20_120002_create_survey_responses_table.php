<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * respondent_id is nullable and, for an anonymous survey, is NEVER
     * populated by the application — not merely hidden from the UI. There is
     * no other identifying column on this table (no IP, no user agent).
     * Duplicate-submission prevention and completion-rate reporting are
     * handled entirely by the separate survey_completions table instead,
     * which is not joinable back to this table's answer content.
     */
    public function up(): void
    {
        Schema::create('survey_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_id')->constrained()->cascadeOnDelete();
            $table->foreignId('respondent_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->timestamps();

            $table->index('survey_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_responses');
    }
};
