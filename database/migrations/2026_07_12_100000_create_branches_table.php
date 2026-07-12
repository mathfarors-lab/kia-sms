<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('name_en');
            $table->string('name_km')->nullable();
            $table->string('code', 10)->unique();   // short prefix used in per-branch numbering, e.g. MC
            $table->text('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Branch #1 exists from the moment the table does: every pre-existing
        // row in the system belongs to it (backfilled in the next migration),
        // so single-branch installs keep working with zero configuration.
        DB::table('branches')->insert([
            'id'         => 1,
            'name_en'    => 'Main Campus',
            'name_km'    => 'សាខាកណ្តាល',
            'code'       => 'MC',
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
