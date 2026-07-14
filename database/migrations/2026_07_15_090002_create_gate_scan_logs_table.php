<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Raw scan events, separate from the attendance record they may (or may
     * not) produce — needed for the debounce window lookup and for auditing
     * scan activity (including rejected/unmatched/wrong-branch attempts)
     * independent of whatever attendance ended up being marked.
     */
    public function up(): void
    {
        Schema::create('gate_scan_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('scanned_code');
            $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->foreignId('staff_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->enum('result', ['matched', 'unmatched', 'duplicate', 'wrong_branch']);
            $table->enum('event', ['arrival', 'departure'])->nullable();
            $table->foreignId('scanned_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('scanned_at');
            $table->timestamps();

            $table->index(['student_id', 'scanned_at']);
            $table->index(['staff_id', 'scanned_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gate_scan_logs');
    }
};
