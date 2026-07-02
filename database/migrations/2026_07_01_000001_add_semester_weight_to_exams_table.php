<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->tinyInteger('semester')->unsigned()->nullable()->after('type')
                  ->comment('1 = Semester 1, 2 = Semester 2, null = not term-grouped');
            $table->decimal('weight', 5, 2)->default(1.00)->after('semester')
                  ->comment('Relative weight when consolidating multiple exams into a term result');
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->dropColumn(['semester', 'weight']);
        });
    }
};
