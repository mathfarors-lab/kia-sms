<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Every branch-owned table gets a nullable branch_id FK, backfilled to
     * Main Campus (branch 1). Nullable on purpose: a NULL branch means
     * "no branch context" (console jobs, legacy rows, the owner account) and
     * the BranchScope only filters when a context is active — this is what
     * keeps all pre-M1 tests and single-branch behavior working unchanged.
     */
    private const TABLES = [
        'students', 'staff', 'school_classes', 'sections',
        'attendances', 'exams', 'exam_marks', 'exam_results', 'term_results',
        'invoices', 'payments', 'fee_structures', 'scholarships',
        'announcements', 'homework', 'books', 'book_issues',
        'transport_routes', 'leaves', 'admission_applications',
        'invoice_sequences', 'settings', 'users',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->foreignId('branch_id')->nullable()
                  ->constrained('branches')->nullOnDelete()
                  ->after('id');
            });

            DB::table($table)->whereNull('branch_id')->update(['branch_id' => 1]);
        }

        // Per-branch numbering: two branches may each have INV sequence rows
        // for the same year. (Generated invoice numbers embed the branch code,
        // so invoice.number stays globally unique.)
        Schema::table('invoice_sequences', function (Blueprint $t) {
            $t->dropUnique('invoice_sequences_year_unique');
            $t->unique(['year', 'branch_id'], 'invoice_sequences_year_branch_unique');
        });

        // Per-branch settings: same key may exist once per branch, plus one
        // global (branch_id NULL) fallback row.
        Schema::table('settings', function (Blueprint $t) {
            $t->dropUnique('settings_key_unique');
            $t->unique(['key', 'branch_id'], 'settings_key_branch_unique');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $t) {
            $t->dropUnique('settings_key_branch_unique');
            $t->unique('key');
        });

        Schema::table('invoice_sequences', function (Blueprint $t) {
            $t->dropUnique('invoice_sequences_year_branch_unique');
            $t->unique('year');
        });

        foreach (array_reverse(self::TABLES) as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropConstrainedForeignId('branch_id');
            });
        }
    }
};
