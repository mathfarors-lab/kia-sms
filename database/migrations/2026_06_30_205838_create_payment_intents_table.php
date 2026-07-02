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
        Schema::create('payment_intents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->text('qr_string');                          // the full KHQR string shown to the payer
            $table->string('md5_hash', 32)->unique();           // md5($qr_string) — the polling key
            $table->string('bill_number', 100);                 // our merchant reference (invoice number)
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->timestamp('expires_at');                    // KHQR window (default 10 min from creation)
            // pending → polling; paid → confirmed; expired → window closed; flagged → review
            $table->string('status', 20)->default('pending')->index();
            $table->string('bakong_hash', 255)->nullable();     // transaction hash from confirmed response
            $table->string('error_reason', 100)->nullable();
            $table->timestamp('polled_at')->nullable();         // last time this intent was polled
            $table->timestamps();

            $table->index(['invoice_id', 'status']);
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_intents');
    }
};
