<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Did not previously exist anywhere in the app (no model, no migration,
     * no HR view) despite being a stated dependency of gate-based staff
     * check-in — building the minimal version needed here: enough to record
     * a gate-scan-driven arrival/departure and support the punctuality
     * report. Not a full manual-marking module — there is no UI need for
     * that yet since every row here originates from a gate scan.
     */
    public function up(): void
    {
        Schema::create('staff_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->date('date');
            $table->enum('status', ['present', 'late', 'absent'])->default('present');
            $table->enum('method', ['manual', 'gate_scan'])->default('gate_scan');
            $table->time('arrival_time')->nullable();
            $table->time('departure_time')->nullable();
            $table->timestamps();

            $table->unique(['staff_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_attendances');
    }
};
