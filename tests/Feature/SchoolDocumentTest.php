<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\SchoolDocument;
use App\Models\User;
use App\Support\BranchContext;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SchoolDocumentTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branchA;

    private Branch $branchB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('local');

        $this->branchA = Branch::findOrFail(1);
        $this->branchB = Branch::create(['name_en' => 'Riverside Campus', 'code' => 'RC', 'is_active' => true]);

        BranchContext::clear();
    }

    protected function tearDown(): void
    {
        BranchContext::clear();
        parent::tearDown();
    }

    private function makeUserIn(Branch $branch, string $role): User
    {
        $user = User::factory()->create(['status' => 'active', 'branch_id' => $branch->id]);
        $user->assignRole($role);

        return $user;
    }

    // ── Upload + manage-vs-view permission split ────────────────────────────

    public function test_admin_can_upload_a_branch_specific_document(): void
    {
        $admin = $this->makeUserIn($this->branchA, 'admin');
        $file = UploadedFile::fake()->create('handbook.pdf', 200, 'application/pdf');

        $this->actingAs($admin)->post(route('school-documents.store'), [
            'title' => 'Staff Handbook', 'category' => 'policy', 'file' => $file,
        ])->assertRedirect();

        $document = SchoolDocument::where('title', 'Staff Handbook')->firstOrFail();
        $this->assertEquals($this->branchA->id, $document->branch_id);
    }

    public function test_admin_can_upload_an_all_branches_document(): void
    {
        $admin = $this->makeUserIn($this->branchA, 'admin');
        $file = UploadedFile::fake()->create('policy.pdf', 200, 'application/pdf');

        $this->actingAs($admin)->post(route('school-documents.store'), [
            'title' => 'Code of Conduct', 'category' => 'policy', 'file' => $file, 'all_branches' => '1',
        ])->assertRedirect();

        $document = SchoolDocument::where('title', 'Code of Conduct')->firstOrFail();
        $this->assertNull($document->branch_id);
    }

    public function test_viewer_cannot_upload_a_document(): void
    {
        $teacher = $this->makeUserIn($this->branchA, 'teacher'); // documents.view, not documents.manage
        $file = UploadedFile::fake()->create('handbook.pdf', 200, 'application/pdf');

        $this->actingAs($teacher)->post(route('school-documents.store'), [
            'title' => 'Staff Handbook', 'category' => 'policy', 'file' => $file,
        ])->assertForbidden();
    }

    public function test_viewer_cannot_delete_a_document(): void
    {
        $document = SchoolDocument::create([
            'title' => 'Staff Handbook', 'category' => 'policy', 'path' => 'school-documents/x.pdf',
            'original_name' => 'x.pdf', 'branch_id' => null,
        ]);
        $teacher = $this->makeUserIn($this->branchA, 'teacher');

        $this->actingAs($teacher)
            ->delete(route('school-documents.destroy', $document))
            ->assertForbidden();

        $this->assertDatabaseHas('school_documents', ['id' => $document->id]);
    }

    public function test_role_without_documents_view_cannot_browse(): void
    {
        $parent = $this->makeUserIn($this->branchA, 'parent'); // no documents.view grant

        $this->actingAs($parent)
            ->get(route('school-documents.index'))
            ->assertForbidden();
    }

    // ── Branch scoping — mirrors Setting::allForCurrentBranch(), not BelongsToBranch ──

    public function test_branch_specific_document_is_only_visible_within_that_branch(): void
    {
        SchoolDocument::create([
            'title' => 'Branch A Only', 'category' => 'form', 'path' => 'school-documents/a.pdf',
            'original_name' => 'a.pdf', 'branch_id' => $this->branchA->id,
        ]);

        $userInA = $this->makeUserIn($this->branchA, 'teacher');
        $userInB = $this->makeUserIn($this->branchB, 'teacher');

        $this->actingAs($userInA)->get(route('school-documents.index'))->assertSee('Branch A Only');
        $this->actingAs($userInB)->get(route('school-documents.index'))->assertDontSee('Branch A Only');
    }

    public function test_all_branches_document_is_visible_in_every_branch(): void
    {
        SchoolDocument::create([
            'title' => 'Everyone Sees This', 'category' => 'policy', 'path' => 'school-documents/all.pdf',
            'original_name' => 'all.pdf', 'branch_id' => null,
        ]);

        $userInA = $this->makeUserIn($this->branchA, 'teacher');
        $userInB = $this->makeUserIn($this->branchB, 'teacher');

        $this->actingAs($userInA)->get(route('school-documents.index'))->assertSee('Everyone Sees This');
        $this->actingAs($userInB)->get(route('school-documents.index'))->assertSee('Everyone Sees This');
    }

    public function test_download_of_a_branch_specific_document_is_blocked_from_another_branch(): void
    {
        Storage::disk('local')->put('school-documents/a.pdf', 'fake contents');
        $document = SchoolDocument::create([
            'title' => 'Branch A Only', 'category' => 'form', 'path' => 'school-documents/a.pdf',
            'original_name' => 'a.pdf', 'branch_id' => $this->branchA->id,
        ]);

        $userInB = $this->makeUserIn($this->branchB, 'teacher');

        $this->actingAs($userInB)
            ->get(route('school-documents.download', $document))
            ->assertForbidden();
    }

    public function test_category_filter_only_returns_matching_documents(): void
    {
        SchoolDocument::create(['title' => 'A Policy', 'category' => 'policy', 'path' => 'x', 'original_name' => 'x', 'branch_id' => null]);
        SchoolDocument::create(['title' => 'A Form', 'category' => 'form', 'path' => 'y', 'original_name' => 'y', 'branch_id' => null]);

        $admin = $this->makeUserIn($this->branchA, 'admin');

        $response = $this->actingAs($admin)->get(route('school-documents.index', ['category' => 'policy']));

        $response->assertSee('A Policy');
        $response->assertDontSee('A Form');
    }
}
