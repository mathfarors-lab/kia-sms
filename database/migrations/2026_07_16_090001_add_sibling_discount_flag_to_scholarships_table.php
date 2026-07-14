<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Marks a scholarship row as auto-managed by the sibling-discount sync
     * (InvoiceService), distinguishing it from a scholarship a staff member
     * entered by hand — so the sync can find and update/remove ITS OWN row
     * without ever touching a real, manually-granted scholarship.
     */
    public function up(): void
    {
        Schema::table('scholarships', function (Blueprint $table) {
            $table->boolean('is_sibling_discount')->default(false)->after('reason');
        });
    }

    public function down(): void
    {
        Schema::table('scholarships', function (Blueprint $table) {
            $table->dropColumn('is_sibling_discount');
        });
    }
};
