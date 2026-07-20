<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Draft evaluations are visible only to staff-evaluations.manage holders
     * (principal/admin/owner) — never the teacher being evaluated. Only once
     * status flips to 'finalized' does the teacher's own self-view carve-out
     * start showing it. One-way transition, no un-finalizing.
     */
    public function up(): void
    {
        Schema::create('staff_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained()->cascadeOnDelete();
            $table->foreignId('evaluated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('evaluation_date');
            $table->unsignedTinyInteger('overall_rating'); // 1-5
            $table->text('strengths')->nullable();
            $table->text('areas_for_improvement')->nullable();
            $table->text('comments')->nullable();
            $table->string('status')->default('draft'); // draft, finalized
            $table->timestamp('finalized_at')->nullable();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->timestamps();

            $table->index(['staff_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_evaluations');
    }
};
