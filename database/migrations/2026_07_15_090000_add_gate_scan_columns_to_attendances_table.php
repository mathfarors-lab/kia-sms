<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->enum('method', ['manual', 'gate_scan'])->default('manual')->after('status');
            $table->time('arrival_time')->nullable()->after('method');
            $table->time('departure_time')->nullable()->after('arrival_time');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn(['method', 'arrival_time', 'departure_time']);
        });
    }
};
