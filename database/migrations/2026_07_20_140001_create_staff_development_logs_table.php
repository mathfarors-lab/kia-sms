<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** A simple CPD/training log — no visibility workflow, same audience as qualifications (G1). */
    public function up(): void
    {
        Schema::create('staff_development_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('provider')->nullable();
            $table->date('completed_date');
            $table->decimal('hours', 5, 2)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('added_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('staff_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_development_logs');
    }
};
