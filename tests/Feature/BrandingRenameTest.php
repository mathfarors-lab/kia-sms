<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * "KIA School" + "Management System" → the single combined product name
 * "KIA School System", everywhere it's system-level chrome. The regression
 * guard half of this file exists because the two are easy to conflate: a
 * document's own letterhead (a branch's real identity) must never pick up
 * the platform's name just because both are Blade views.
 */
class BrandingRenameTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    // ── System-level chrome shows the new combined name ───────────────────────

    public function test_login_page_shows_combined_name(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('KIA School System')
            ->assertDontSee('KIA School</h1>', false); // the old bare h1 text, unescaped-HTML check
    }

    public function test_browser_tab_title_shows_combined_name_on_login(): void
    {
        $html = $this->get('/login')->getContent();
        $this->assertMatchesRegularExpression('/<title>[^<]*KIA School System[^<]*<\/title>/', $html);
    }

    public function test_sidebar_and_authenticated_tab_title_show_combined_name(): void
    {
        $admin = User::factory()->create(['status' => 'active']);
        $admin->assignRole('admin');

        $html = $this->actingAs($admin)->get(route('dashboard.admin'))->assertOk()->getContent();

        $this->assertStringContainsString('KIA School System', $html);
        $this->assertMatchesRegularExpression('/<title>[^<]*KIA School System[^<]*<\/title>/', $html);
        // The old two-line pattern must be gone, not just superseded.
        $this->assertStringNotContainsString('>Management System<', $html);
    }

    public function test_sidebar_footer_shows_combined_name(): void
    {
        $admin = User::factory()->create(['status' => 'active']);
        $admin->assignRole('admin');

        $html = $this->actingAs($admin)->get(route('dashboard.admin'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/KIA School System\s*&copy;/', $html);
    }

    public function test_app_config_name_is_the_combined_string(): void
    {
        $this->assertEquals('KIA School System', config('app.name'));
    }

    // ── Regression guard: a document's own identity is never overwritten ──────

    public function test_branch_names_are_unaffected_by_the_rename(): void
    {
        // Branches keep their own real identity — never coerced toward the
        // platform's name by the rename. (Branch #1 "Main Campus"/"MC" is
        // already seeded by the M1 migration itself — use a fresh code here.)
        $branch = Branch::create(['name_en' => 'North Campus', 'name_km' => 'សាខាខាងជើង', 'code' => 'NC']);

        $this->assertEquals('North Campus', $branch->fresh()->name_en);
        $this->assertNotEquals('KIA School System', $branch->fresh()->name_en);
    }

    public function test_pdf_document_letterhead_does_not_pick_up_the_platform_name(): void
    {
        // finance-report.blade.php hardcodes its own letterhead independent
        // of config('app.name') / the KIA School System lang key — rendering
        // it directly (skipping actual PDF binary conversion, which isn't
        // reliably text-searchable) proves the rename never leaked in.
        $html = view('pdf.finance-report', ['invoices' => collect()])->render();

        $this->assertStringNotContainsString('KIA School System', $html);
        $this->assertStringContainsString('Khmer Intellectual Academy', $html);
    }

    public function test_reports_pdf_templates_do_not_pick_up_the_platform_name(): void
    {
        $year = \App\Models\AcademicYear::create([
            'name' => 'Y-' . uniqid(), 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => true,
        ]);

        $enrollment = view('reports.pdf.enrollment', ['year' => $year, 'students' => collect()])->render();
        $attendance = view('reports.pdf.attendance', ['year' => $year, 'query' => collect()])->render();
        $fee        = view('reports.pdf.fee', ['year' => $year, 'rows' => collect(), 'totalCollected' => 0])->render();

        foreach (['enrollment' => $enrollment, 'attendance' => $attendance, 'fee' => $fee] as $name => $html) {
            $this->assertStringNotContainsString('KIA School System', $html, "reports.pdf.{$name} picked up the platform name");
        }
    }
}
