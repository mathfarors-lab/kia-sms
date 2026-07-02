<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_classes', function (Blueprint $table) {
            // Nullable: null = final grade (student graduates on pass, not promoted)
            $table->foreignId('next_class_id')
                  ->nullable()
                  ->after('capacity')
                  ->constrained('school_classes')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('school_classes', function (Blueprint $table) {
            $table->dropForeign(['next_class_id']);
            $table->dropColumn('next_class_id');
        });
    }
};
