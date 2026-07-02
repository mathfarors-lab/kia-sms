<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bakong_callbacks', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_reference')->unique(); // idempotency key
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('status');          // confirmed, failed
            $table->string('payer_account')->nullable();
            $table->json('raw_payload');
            $table->boolean('signature_valid')->default(false);
            $table->timestamps();

            $table->index('invoice_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bakong_callbacks');
    }
};
