<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per transfer/withdrawal event — a dedicated table rather than
     * columns on `students`, so a student who re-enrolls after leaving and
     * later leaves again keeps a full, queryable history instead of the
     * second event silently overwriting the first.
     */
    public function up(): void
    {
        Schema::create('student_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // transfer, withdrawal
            $table->string('reason_category');
            $table->text('reason_note')->nullable();
            $table->date('effective_date');
            $table->foreignId('destination_branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('destination_name')->nullable(); // free-text external school, when not one of our branches
            $table->decimal('outstanding_balance_at_time', 10, 2)->nullable();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['student_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_transfers');
    }
};
