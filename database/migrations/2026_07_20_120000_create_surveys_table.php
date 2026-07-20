<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surveys', function (Blueprint $table) {
            $table->id();
            $table->string('title_en');
            $table->string('title_km')->nullable();
            $table->text('description_en')->nullable();
            $table->text('description_km')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();

            // Audience targeting — same enum-plus-shared-target_id shape as
            // announcements, extended to the 5 dimensions this feature needs.
            // target_id means: role_id / branch_id / school_class_id / section_id
            // depending on audience; null when audience = 'all'.
            $table->string('audience');
            $table->unsignedBigInteger('target_id')->nullable();

            $table->boolean('is_anonymous')->default(false);
            $table->timestamp('opens_at')->nullable();
            $table->timestamp('closes_at')->nullable();
            $table->string('status')->default('draft'); // draft, open, closed
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->timestamps();

            $table->index(['audience', 'target_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surveys');
    }
};
