<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bakong_failed_verifications', function (Blueprint $table) {
            $table->id();
            // NOT UNIQUE — forged callbacks must not occupy the idempotency slot
            // used by the real callback for the same reference.
            $table->string('transaction_reference')->nullable();
            $table->string('reason'); // secret-unset | missing-header | bad-sig
            $table->json('raw_payload'); // stored without rawBody/signature blobs
            $table->timestamps();

            $table->index('transaction_reference');
            $table->index('created_at'); // fast time-windowed counts for dashboard
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bakong_failed_verifications');
    }
};
