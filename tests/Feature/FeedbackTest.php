<?php

namespace Tests\Feature;

use App\Models\FeedbackItem;
use App\Models\Student;
use App\Models\User;
use App\Notifications\FeedbackReplied;
use App\Notifications\FeedbackStatusChanged;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FeedbackTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('local');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function makeUser(string $role): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole($role);
        return $user;
    }

    private function makeStudentUser(): array
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('student');
        $student = Student::create([
            'user_id' => $user->id, 'student_code' => 'K-' . uniqid(),
            'name_en' => 'Student-' . uniqid(), 'name_km' => 'សិស្ស', 'gender' => 'female', 'status' => 'enrolled',
        ]);
        return [$user, $student];
    }

    private function makeParentFor(Student $student): User
    {
        $parentUser = User::factory()->create(['status' => 'active']);
        $parentUser->assignRole('parent');
        DB::table('student_guardian')->insert([
            'student_id' => $student->id, 'guardian_id' => $parentUser->id,
            'relation' => 'parent', 'is_primary' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        return $parentUser;
    }

    private function makeFeedback(User $submitter, array $overrides = []): FeedbackItem
    {
        return FeedbackItem::create(array_merge([
            'submitted_by' => $submitter->id,
            'category'     => 'academic',
            'subject'      => 'Test subject ' . uniqid(),
            'body'         => 'Test body',
            'status'       => 'open',
        ], $overrides));
    }

    // ── Submission ───────────────────────────────────────────────────────────

    public function test_parent_can_submit_feedback(): void
    {
        $child = Student::create([
            'user_id' => User::factory()->create()->id, 'student_code' => 'K-' . uniqid(),
            'name_en' => 'Kid', 'name_km' => 'កូន', 'gender' => 'male', 'status' => 'enrolled',
        ]);
        $parentUser = $this->makeParentFor($child);

        $response = $this->actingAs($parentUser)->post(route('feedback.store'), [
            'category' => 'facility', 'subject' => 'Broken AC', 'body' => 'The classroom AC is broken.',
            'student_id' => $child->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('feedback_items', [
            'submitted_by' => $parentUser->id, 'category' => 'facility', 'subject' => 'Broken AC',
            'student_id' => $child->id, 'status' => 'open',
        ]);
    }

    public function test_student_submission_is_scoped_to_their_own_student_id(): void
    {
        [$user, $student] = $this->makeStudentUser();

        $this->actingAs($user)->post(route('feedback.store'), [
            'category' => 'academic', 'subject' => 'Grading question', 'body' => 'Why was my exam marked this way?',
        ])->assertRedirect();

        $this->assertDatabaseHas('feedback_items', [
            'submitted_by' => $user->id, 'student_id' => $student->id,
        ]);
    }

    public function test_parent_cannot_attribute_feedback_to_another_familys_child(): void
    {
        [, $otherStudent] = $this->makeStudentUser();
        $parentUser = User::factory()->create(['status' => 'active']);
        $parentUser->assignRole('parent');

        $response = $this->actingAs($parentUser)->post(route('feedback.store'), [
            'category' => 'academic', 'subject' => 'x', 'body' => 'y', 'student_id' => $otherStudent->id,
        ]);

        $response->assertForbidden();
    }

    public function test_teacher_cannot_submit_feedback(): void
    {
        $this->actingAs($this->makeUser('teacher'))
            ->post(route('feedback.store'), ['category' => 'academic', 'subject' => 'x', 'body' => 'y'])
            ->assertForbidden();
    }

    // ── Access control / IDOR ───────────────────────────────────────────────

    public function test_principal_can_view_inbox(): void
    {
        $this->actingAs($this->makeUser('principal'))->get(route('feedback.index'))->assertOk();
    }

    public function test_teacher_cannot_view_inbox_or_index(): void
    {
        $this->actingAs($this->makeUser('teacher'))->get(route('feedback.index'))->assertForbidden();
    }

    public function test_submitter_can_view_own_item(): void
    {
        [$user] = $this->makeStudentUser();
        $item = $this->makeFeedback($user);

        $this->actingAs($user)->get(route('feedback.show', $item))->assertOk();
    }

    public function test_staff_with_permission_can_view_any_item(): void
    {
        [$user] = $this->makeStudentUser();
        $item = $this->makeFeedback($user);

        $this->actingAs($this->makeUser('admin'))->get(route('feedback.show', $item))->assertOk();
    }

    /** IDOR: a student must never reach another student's feedback thread. */
    public function test_student_cannot_view_another_students_feedback_item(): void
    {
        [$userA] = $this->makeStudentUser();
        [$userB] = $this->makeStudentUser();
        $item = $this->makeFeedback($userA);

        $this->actingAs($userB)->get(route('feedback.show', $item))->assertForbidden();
    }

    /** IDOR: a parent must never reach another family's feedback thread. */
    public function test_parent_cannot_view_another_familys_feedback_item(): void
    {
        [$studentUser] = $this->makeStudentUser();
        $item = $this->makeFeedback($studentUser);

        $unrelatedParent = User::factory()->create(['status' => 'active']);
        $unrelatedParent->assignRole('parent');

        $this->actingAs($unrelatedParent)->get(route('feedback.show', $item))->assertForbidden();
    }

    // ── Status transitions ───────────────────────────────────────────────────

    public function test_valid_status_transition_succeeds(): void
    {
        [$user] = $this->makeStudentUser();
        $item = $this->makeFeedback($user, ['status' => 'open']);

        $this->actingAs($this->makeUser('admin'))
            ->post(route('feedback.status', $item), ['status' => 'in_progress'])
            ->assertRedirect();

        $this->assertEquals('in_progress', $item->fresh()->status);
    }

    public function test_resolving_sets_resolved_at(): void
    {
        [$user] = $this->makeStudentUser();
        $item = $this->makeFeedback($user, ['status' => 'in_progress']);

        $this->actingAs($this->makeUser('admin'))
            ->post(route('feedback.status', $item), ['status' => 'resolved']);

        $this->assertNotNull($item->fresh()->resolved_at);
    }

    /** Cannot jump backwards from resolved to open through the normal status action. */
    public function test_resolved_to_open_is_rejected_without_reopen(): void
    {
        [$user] = $this->makeStudentUser();
        $item = $this->makeFeedback($user, ['status' => 'resolved']);

        $response = $this->actingAs($this->makeUser('admin'))
            ->post(route('feedback.status', $item), ['status' => 'open']);

        $response->assertSessionHasErrors('status');
        $this->assertEquals('resolved', $item->fresh()->status);
    }

    public function test_closed_to_in_progress_is_rejected(): void
    {
        [$user] = $this->makeStudentUser();
        $item = $this->makeFeedback($user, ['status' => 'closed']);

        $this->actingAs($this->makeUser('admin'))
            ->post(route('feedback.status', $item), ['status' => 'in_progress']);

        $this->assertEquals('closed', $item->fresh()->status);
    }

    public function test_reopen_succeeds_from_resolved(): void
    {
        [$user] = $this->makeStudentUser();
        $item = $this->makeFeedback($user, ['status' => 'resolved', 'resolved_at' => now()]);

        $this->actingAs($this->makeUser('admin'))
            ->post(route('feedback.reopen', $item))
            ->assertRedirect();

        $fresh = $item->fresh();
        $this->assertEquals('open', $fresh->status);
        $this->assertNull($fresh->resolved_at);
    }

    public function test_reopen_fails_when_not_resolved_or_closed(): void
    {
        [$user] = $this->makeStudentUser();
        $item = $this->makeFeedback($user, ['status' => 'open']);

        $this->actingAs($this->makeUser('admin'))
            ->post(route('feedback.reopen', $item))
            ->assertStatus(422);

        $this->assertEquals('open', $item->fresh()->status);
    }

    public function test_status_change_requires_manage_permission(): void
    {
        [$user] = $this->makeStudentUser();
        $item = $this->makeFeedback($user, ['status' => 'open']);

        $this->actingAs($this->makeUser('teacher'))
            ->post(route('feedback.status', $item), ['status' => 'in_progress'])
            ->assertForbidden();
    }

    // ── Replies + notifications ──────────────────────────────────────────────

    public function test_reply_triggers_notification_to_submitter(): void
    {
        Notification::fake();
        [$user] = $this->makeStudentUser();
        $item = $this->makeFeedback($user);
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)
            ->post(route('feedback.reply', $item), ['body' => 'We are looking into this.'])
            ->assertRedirect();

        $this->assertDatabaseHas('feedback_replies', ['feedback_item_id' => $item->id, 'user_id' => $admin->id]);
        Notification::assertSentTo($user, FeedbackReplied::class, function ($notification) use ($item) {
            return $notification->feedbackItem->id === $item->id;
        });
    }

    public function test_own_reply_does_not_self_notify(): void
    {
        Notification::fake();
        [$user] = $this->makeStudentUser();
        $item = $this->makeFeedback($user);

        $this->actingAs($user)->post(route('feedback.reply', $item), ['body' => 'Following up.']);

        Notification::assertNothingSentTo($user);
    }

    public function test_status_change_triggers_notification_to_submitter(): void
    {
        Notification::fake();
        [$user] = $this->makeStudentUser();
        $item = $this->makeFeedback($user, ['status' => 'open']);

        $this->actingAs($this->makeUser('admin'))
            ->post(route('feedback.status', $item), ['status' => 'in_progress']);

        Notification::assertSentTo($user, FeedbackStatusChanged::class, function ($notification) {
            return $notification->newStatus === 'in_progress';
        });
    }

    // ── Inbox filters ────────────────────────────────────────────────────────

    public function test_inbox_filters_by_status_and_category(): void
    {
        [$user] = $this->makeStudentUser();
        $this->makeFeedback($user, ['status' => 'open', 'category' => 'academic']);
        $this->makeFeedback($user, ['status' => 'resolved', 'category' => 'academic']);
        $this->makeFeedback($user, ['status' => 'open', 'category' => 'facility']);

        $response = $this->actingAs($this->makeUser('admin'))
            ->get(route('feedback.index', ['status' => 'open', 'category' => 'academic']));

        $response->assertOk();
        $items = $response->viewData('items');
        $this->assertCount(1, $items);
    }

    // ── Attachment ───────────────────────────────────────────────────────────

    public function test_attachment_upload_and_gated_download(): void
    {
        [$user] = $this->makeStudentUser();

        $this->actingAs($user)->post(route('feedback.store'), [
            'category' => 'facility', 'subject' => 'Broken window', 'body' => 'See photo.',
            'attachment' => UploadedFile::fake()->create('proof.jpg', 100, 'image/jpeg'),
        ])->assertRedirect();

        $item = FeedbackItem::where('submitted_by', $user->id)->firstOrFail();
        $this->assertNotNull($item->attachment_path);
        Storage::disk('local')->assertExists($item->attachment_path);

        $this->actingAs($user)->get(route('feedback.attachment', $item))->assertOk();

        [$otherUser] = $this->makeStudentUser();
        $this->actingAs($otherUser)->get(route('feedback.attachment', $item))->assertForbidden();
    }

    // ── Satisfaction dashboard ───────────────────────────────────────────────

    public function test_dashboard_numbers_match_seeded_data_exactly(): void
    {
        [$user] = $this->makeStudentUser();

        $this->makeFeedback($user, ['category' => 'academic', 'status' => 'open']);
        $this->makeFeedback($user, ['category' => 'academic', 'status' => 'open']);
        $this->makeFeedback($user, ['category' => 'academic', 'status' => 'in_progress']);

        $resolved = $this->makeFeedback($user, ['category' => 'facility', 'status' => 'resolved']);
        $resolved->forceFill(['created_at' => now()->subHours(10), 'resolved_at' => now()])->save();

        $this->makeFeedback($user, ['category' => 'facility', 'status' => 'closed']);

        $response = $this->actingAs($this->makeUser('admin'))->get(route('feedback.dashboard'));
        $response->assertOk();

        $byCategory = collect($response->viewData('byCategory'))->keyBy('category');

        $this->assertEquals(2, (int) $byCategory['academic']->open);
        $this->assertEquals(1, (int) $byCategory['academic']->in_progress);
        $this->assertEquals(3, (int) $byCategory['academic']->total);

        $this->assertEquals(1, (int) $byCategory['facility']->resolved);
        $this->assertEquals(1, (int) $byCategory['facility']->closed);
        $this->assertEquals(2, (int) $byCategory['facility']->total);

        $this->assertEquals(3, $response->viewData('totalOpen'));   // 2 open + 1 in_progress
        $this->assertEquals(2, $response->viewData('totalResolved')); // 1 resolved + 1 closed
        $this->assertEquals(10.0, $response->viewData('avgResolutionHours'));
    }

    public function test_dashboard_requires_feedback_view_permission(): void
    {
        $this->actingAs($this->makeUser('teacher'))->get(route('feedback.dashboard'))->assertForbidden();
    }
}
