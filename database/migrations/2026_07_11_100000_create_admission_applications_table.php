<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admission_applications', function (Blueprint $table) {
            $table->id();
            $table->string('application_no')->unique();            // ADM-26-0001

            // Applicant
            $table->string('name_en');
            $table->string('name_km')->nullable();
            $table->enum('gender', ['male', 'female', 'other']);
            $table->date('date_of_birth')->nullable();
            $table->text('address')->nullable();

            // Guardian contact (pre-enrollment, so no User FK yet)
            $table->string('guardian_name')->nullable();
            $table->string('guardian_phone')->nullable();
            $table->string('guardian_relation')->nullable();

            // Target
            $table->foreignId('desired_class_id')->nullable()->constrained('school_classes')->nullOnDelete();
            $table->foreignId('academic_year_id')->nullable()->constrained()->nullOnDelete();

            // Pipeline: enquiry → applied → under_review → accepted/rejected → converted
            $table->string('status')->default('enquiry');
            $table->text('notes')->nullable();

            // Supporting document (private disk)
            $table->string('document_path')->nullable();
            $table->string('document_original_name')->nullable();

            // Review + conversion audit trail
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('student_id')->nullable()->constrained()->nullOnDelete(); // set once converted — idempotency guard

            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_applications');
    }
};
