<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_qualifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained()->cascadeOnDelete();
            $table->string('degree');
            $table->string('institution');
            $table->unsignedSmallInteger('year');
            $table->timestamps();

            $table->index('staff_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_qualifications');
    }
};
