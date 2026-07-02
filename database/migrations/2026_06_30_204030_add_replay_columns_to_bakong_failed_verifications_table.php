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
        Schema::table('bakong_failed_verifications', function (Blueprint $table) {
            // Stored so admin replay can re-run HMAC against current secret
            $table->text('raw_body')->nullable()->after('raw_payload');
            $table->string('received_signature')->nullable()->after('raw_body');
            // Replay outcome tracking
            $table->timestamp('replayed_at')->nullable()->after('received_signature');
            $table->string('replay_result', 100)->nullable()->after('replayed_at');
        });
    }

    public function down(): void
    {
        Schema::table('bakong_failed_verifications', function (Blueprint $table) {
            $table->dropColumn(['raw_body', 'received_signature', 'replayed_at', 'replay_result']);
        });
    }
};
