<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Mirrors student_documents exactly — same private-disk, gated-download pattern. */
    public function up(): void
    {
        Schema::create('staff_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->string('path');
            $table->string('original_name');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('staff_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_documents');
    }
};
