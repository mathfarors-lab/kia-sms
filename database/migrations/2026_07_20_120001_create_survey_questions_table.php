<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('survey_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('order')->default(0);
            $table->string('type'); // multiple_choice, rating_scale, free_text, yes_no
            $table->string('question_text_en');
            $table->string('question_text_km')->nullable();
            $table->json('options')->nullable(); // multiple_choice only
            $table->boolean('required')->default(true);
            $table->timestamps();

            $table->index(['survey_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_questions');
    }
};
