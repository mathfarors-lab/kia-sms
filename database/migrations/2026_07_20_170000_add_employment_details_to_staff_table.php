<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->string('contract_type')->nullable()->after('joined_at');
            $table->date('contract_end_date')->nullable()->after('contract_type');
            $table->string('employment_status')->default('active')->after('contract_end_date');
        });
    }

    public function down(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->dropColumn(['contract_type', 'contract_end_date', 'employment_status']);
        });
    }
};
