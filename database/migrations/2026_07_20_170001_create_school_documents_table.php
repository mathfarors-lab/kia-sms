<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_documents', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('category');
            $table->string('path');
            $table->string('original_name');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            // Nullable = visible to every branch. Deliberately NOT the
            // BelongsToBranch trait's global scope (strict equality — a null
            // row would be invisible to a branch-scoped user, the opposite
            // of "all branches"). See SchoolDocument::scopeVisibleToBranch(),
            // which mirrors Setting::allForCurrentBranch() instead.
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->timestamps();

            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_documents');
    }
};
