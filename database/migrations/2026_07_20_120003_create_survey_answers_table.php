<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('survey_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('response_id')->constrained('survey_responses')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('survey_questions')->cascadeOnDelete();
            $table->text('answer_text')->nullable();
            $table->decimal('answer_value', 5, 2)->nullable(); // rating_scale only, so AVG() needs no CAST
            $table->timestamps();

            $table->index(['question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_answers');
    }
};
