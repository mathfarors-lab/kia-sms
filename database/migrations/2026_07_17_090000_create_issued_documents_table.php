<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('issued_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('student_id')->nullable()->constrained('students')->cascadeOnDelete();
            $table->foreignId('staff_id')->nullable()->constrained('staff')->cascadeOnDelete();
            $table->string('type'); // id_card | enrollment_cert | graduation_cert | leaving_cert
            $table->string('number')->nullable(); // certificate number — null for id_card
            $table->timestamp('issued_at');
            $table->timestamps();

            // A given student/staff member gets at most one row per document
            // type — this IS the idempotency guard (firstOrCreate backed by
            // a real unique index, not just an application-level check).
            $table->unique(['student_id', 'type']);
            $table->unique(['staff_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('issued_documents');
    }
};
