<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bakong_callbacks', function (Blueprint $table) {
            // Set when a verified callback can't be safely applied automatically.
            // Values: unmatched-ref | amount-mismatch | currency-mismatch
            // Payment is NOT applied when this is set; admin must manually reconcile.
            $table->string('flag_reason')->nullable()->after('signature_valid');
        });
    }

    public function down(): void
    {
        Schema::table('bakong_callbacks', function (Blueprint $table) {
            $table->dropColumn('flag_reason');
        });
    }
};
