<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The activity_log table ships with an index on log_name only.
 * Audit queries filter by causer_id and date — add indexes so the
 * viewer stays fast with hundreds of thousands of rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_log', function (Blueprint $table) {
            $table->index('causer_id',   'activity_log_causer_id_index');
            $table->index('created_at',  'activity_log_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('activity_log', function (Blueprint $table) {
            $table->dropIndex('activity_log_causer_id_index');
            $table->dropIndex('activity_log_created_at_index');
        });
    }
};
