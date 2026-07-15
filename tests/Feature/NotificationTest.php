<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Exam;
use App\Models\Student;
use App\Models\User;
use App\Notifications\ResultPublished;
use App\Notifications\StudentGateEvent;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function makeAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');
        return $user;
    }

    private function makeTeacher(): User
    {
        $user = User::factory()->create();
        $user->assignRole('teacher');
        return $user;
    }

    private function makeExam(): Exam
    {
        $year = AcademicYear::create([
            'name'       => 'Y2026',
            'start_date' => '2026-01-01',
            'end_date'   => '2026-12-31',
            'is_active'  => true,
        ]);
        return Exam::create([
            'academic_year_id' => $year->id,
            'name'             => 'Final Exam',
            'type'             => 'final',   // enum: monthly|midterm|final
            'semester'         => null,
            'weight'           => 100,
            'is_published'     => true,
        ]);
    }

    /** Insert a raw database notification for the given user. */
    private function createNotification(User $user, array $data = []): \Illuminate\Notifications\DatabaseNotification
    {
        return $user->notifications()->create([
            'id'   => (string) Str::uuid(),
            'type' => 'App\\Notifications\\TestNotification',
            'data' => array_merge([
                'title' => 'Test Notification',
                'body'  => 'This is a test notification.',
                'url'   => '/',
            ], $data),
        ]);
    }

    // ---------------------------------------------------------------
    // Notification channel: database row + mail channel not broken
    // ---------------------------------------------------------------

    public function test_result_published_writes_database_row(): void
    {
        Mail::fake();

        $user = $this->makeAdmin();
        $exam = $this->makeExam();

        $user->notify(new ResultPublished($exam));

        $this->assertDatabaseHas('notifications', [
            'notifiable_id'   => $user->id,
            'notifiable_type' => User::class,
        ]);
    }

    public function test_result_published_includes_mail_channel(): void
    {
        Notification::fake();

        $user = $this->makeAdmin();
        $exam = $this->makeExam();

        $user->notify(new ResultPublished($exam));

        Notification::assertSentTo($user, ResultPublished::class, function (ResultPublished $n) use ($user) {
            return in_array('mail', $n->via($user)) && in_array('database', $n->via($user));
        });
    }

    public function test_result_published_toarray_has_title_and_url(): void
    {
        $user = $this->makeAdmin();
        $exam = $this->makeExam();

        $payload = (new ResultPublished($exam))->toArray($user);

        $this->assertArrayHasKey('title', $payload);
        $this->assertArrayHasKey('url', $payload);
        $this->assertStringContainsString('Final Exam', $payload['title']);
    }

    /**
     * Mobile-polish regression: tapping a gate-arrival notification used to
     * fall back to the generic notifications list (no url in the payload)
     * instead of landing on anything showing the actual arrival time.
     */
    public function test_gate_event_toarray_has_url_to_the_childs_detail_page(): void
    {
        $student = Student::create([
            'name_en' => 'Gate Event Student', 'gender' => 'male',
            'student_code' => 'S-' . uniqid(), 'status' => 'enrolled',
        ]);
        $notification = new StudentGateEvent($student, 'arrival', Carbon::now());

        $payload = $notification->toArray($this->makeAdmin());

        $this->assertArrayHasKey('url', $payload);
        $this->assertEquals(route('parent.child.show', $student), $payload['url']);
    }

    // ---------------------------------------------------------------
    // Notification index — scoped to auth user
    // ---------------------------------------------------------------

    public function test_user_sees_own_notifications(): void
    {
        $admin = $this->makeAdmin();
        $other = $this->makeTeacher();

        $this->createNotification($admin, ['title' => 'Admin Notif']);
        $this->createNotification($other, ['title' => 'Other Notif']);

        $response = $this->actingAs($admin)->get(route('notifications.index'));

        $response->assertStatus(200);
        $response->assertSee('Admin Notif');
        $response->assertDontSee('Other Notif');
    }

    public function test_notifications_page_is_paginated(): void
    {
        $admin = $this->makeAdmin();

        // Insert 25 notifications (page size = 20) with distinct timestamps
        for ($i = 1; $i <= 25; $i++) {
            \Illuminate\Support\Facades\DB::table('notifications')->insert([
                'id'             => (string) Str::uuid(),
                'type'           => 'App\\Notifications\\TestNotification',
                'notifiable_type'=> User::class,
                'notifiable_id'  => $admin->id,
                'data'           => json_encode(['title' => "Notif #{$i}", 'body' => '', 'url' => '/']),
                'read_at'        => null,
                'created_at'     => now()->subSeconds(26 - $i)->toDateTimeString(),
                'updated_at'     => now()->subSeconds(26 - $i)->toDateTimeString(),
            ]);
        }

        $response = $this->actingAs($admin)->get(route('notifications.index'));

        $response->assertStatus(200);
        // 20 per page — should have a "page 2" link
        $response->assertSee('page=2', false);
    }

    // ---------------------------------------------------------------
    // Mark one as read (IDOR prevention)
    // ---------------------------------------------------------------

    public function test_read_and_go_marks_own_notification_as_read(): void
    {
        $admin = $this->makeAdmin();
        $notif = $this->createNotification($admin);

        $this->assertNull($notif->read_at);

        $this->actingAs($admin)->get(route('notifications.read-go', $notif->id));

        $this->assertNotNull($notif->fresh()->read_at);
    }

    public function test_read_and_go_returns_404_for_another_users_notification(): void
    {
        $admin = $this->makeAdmin();
        $other = $this->makeTeacher();
        $notif = $this->createNotification($other);

        $this->actingAs($admin)
             ->get(route('notifications.read-go', $notif->id))
             ->assertStatus(404);
    }

    // ---------------------------------------------------------------
    // Mark all read
    // ---------------------------------------------------------------

    public function test_mark_all_read_marks_only_auth_users_notifications(): void
    {
        $admin = $this->makeAdmin();
        $other = $this->makeTeacher();

        $n1 = $this->createNotification($admin);
        $n2 = $this->createNotification($admin);
        $n3 = $this->createNotification($other);

        $this->actingAs($admin)
             ->post(route('notifications.read-all'), ['_token' => csrf_token()])
             ->assertRedirect();

        $this->assertNotNull($n1->fresh()->read_at);
        $this->assertNotNull($n2->fresh()->read_at);
        $this->assertNull($n3->fresh()->read_at);
    }

    // ---------------------------------------------------------------
    // Topbar badge — asserted on notifications page (uses x-app-layout)
    // ---------------------------------------------------------------

    public function test_unread_badge_shows_in_layout(): void
    {
        $admin = $this->makeAdmin();
        $this->createNotification($admin);

        $response = $this->actingAs($admin)->get(route('notifications.index'));

        $response->assertStatus(200);
        $response->assertSee('kia-notif-badge');
    }

    public function test_no_badge_when_all_read(): void
    {
        $admin = $this->makeAdmin();
        $notif = $this->createNotification($admin);
        $notif->markAsRead();

        $response = $this->actingAs($admin)->get(route('notifications.index'));

        $response->assertStatus(200);
        $response->assertDontSee('kia-notif-badge');
    }
}
