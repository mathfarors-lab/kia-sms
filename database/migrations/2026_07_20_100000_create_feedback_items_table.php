<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedback_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submitted_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('student_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category');
            $table->string('subject');
            $table->text('body');
            $table->string('status')->default('open');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('attachment_path')->nullable();
            $table->string('attachment_original_name')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            // Two single-column indexes, not a composite — this database's
            // tables are MyISAM (confirmed server-wide, not just here; see
            // the G4-deploy investigation), whose 1000-byte max key length
            // a composite of two utf8mb4 string(191) columns exceeds
            // (764+764=1528 bytes). Query planners can still use both via
            // index merge for a combined WHERE; that's the tradeoff for
            // staying inside the limit without truncating either column.
            $table->index('status');
            $table->index('category');
            $table->index('submitted_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback_items');
    }
};
