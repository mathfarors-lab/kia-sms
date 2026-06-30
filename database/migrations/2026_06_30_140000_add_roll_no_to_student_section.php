<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_section', function (Blueprint $table) {
            $table->string('roll_no', 20)->nullable()->after('academic_year_id');
        });
    }

    public function down(): void
    {
        Schema::table('student_section', function (Blueprint $table) {
            $table->dropColumn('roll_no');
        });
    }
};
