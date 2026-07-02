<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('term_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->tinyInteger('semester')->unsigned()->nullable()
                  ->comment('1 = Semester 1, 2 = Semester 2, null = Annual');
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('section_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('total', 8, 2)->default(0);
            $table->decimal('average', 5, 2)->default(0);
            $table->decimal('gpa', 4, 2)->default(0);
            $table->integer('rank')->nullable();
            $table->enum('result', ['pass', 'fail'])->default('fail');
            $table->boolean('is_published')->default(false);
            $table->boolean('is_finalized')->default(false);
            $table->boolean('has_missing_marks')->default(false);
            $table->text('teacher_remark')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('term_results');
    }
};
