<?php

namespace Tests\Feature;

use App\Jobs\SendAbsenceAlerts;
use App\Models\Invoice;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use App\Notifications\Channels\SmsChannel;
use App\Notifications\FeeDueReminder;
use App\Notifications\ResultPublished;
use App\Models\AcademicYear;
use App\Models\Exam;
use App\Services\SmsService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SmsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function usePlasgate(): void
    {
        config([
            'sms.driver'               => 'plasgate',
            'sms.plasgate.base_url'    => 'https://cloudapi.plasgate.com/rest/send',
            'sms.plasgate.private_key' => 'test-key',
            'sms.plasgate.secret'      => 'test-secret',
            'sms.plasgate.sender'      => 'KIA School',
        ]);
    }

    // ── Driver ───────────────────────────────────────────────────────────────

    public function test_plasgate_driver_posts_correct_payload(): void
    {
        $this->usePlasgate();
        Http::fake(['cloudapi.plasgate.com/*' => Http::response(['status' => 'ok'], 200)]);

        $ok = app(SmsService::class)->send('012 345 678', 'Hello from KIA');

        $this->assertTrue($ok);
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'private_key=test-key')
                && $request->hasHeader('X-Secret', 'test-secret')
                && $request['to'] === '85512345678'         // normalized from 012 345 678
                && $request['content'] === 'Hello from KIA'
                && $request['sender'] === 'KIA School';
        });
    }

    public function test_plasgate_without_credentials_falls_back_to_log_and_sends_nothing(): void
    {
        config(['sms.driver' => 'plasgate', 'sms.plasgate.private_key' => null, 'sms.plasgate.secret' => null]);
        Http::fake();

        $ok = app(SmsService::class)->send('012345678', 'Test');

        $this->assertTrue($ok); // log fallback reports success
        Http::assertNothingSent();
    }

    public function test_plasgate_api_error_returns_false(): void
    {
        $this->usePlasgate();
        Http::fake(['cloudapi.plasgate.com/*' => Http::response('unauthorized', 401)]);

        $this->assertFalse(app(SmsService::class)->send('012345678', 'Test'));
    }

    // ── Absence alerts job ───────────────────────────────────────────────────

    public function test_absence_job_sends_sms_to_primary_guardian(): void
    {
        $this->usePlasgate();
        Http::fake(['cloudapi.plasgate.com/*' => Http::response([], 200)]);

        $class   = SchoolClass::create(['name' => 'Grade 9']);
        $section = Section::create(['school_class_id' => $class->id, 'name' => 'A']);

        $student  = Student::create(['student_code' => 'S-' . uniqid(), 'name_en' => 'Absent Kid', 'gender' => 'male', 'status' => 'enrolled']);
        $guardian = User::factory()->create(['status' => 'active', 'phone' => '011222333']);
        $guardian->assignRole('parent');
        $student->guardians()->attach($guardian->id, ['relation' => 'parent', 'is_primary' => true]);

        (new SendAbsenceAlerts($section->id, [$student->id]))->handle(app(SmsService::class));

        Http::assertSent(fn ($request) => $request['to'] === '85511222333'
            && str_contains($request['content'], 'Absent Kid'));
    }

    public function test_absence_job_skips_students_without_guardian_phone(): void
    {
        $this->usePlasgate();
        Http::fake();

        $class   = SchoolClass::create(['name' => 'Grade 9']);
        $section = Section::create(['school_class_id' => $class->id, 'name' => 'A']);
        $student = Student::create(['student_code' => 'S-' . uniqid(), 'name_en' => 'No Guardian', 'gender' => 'male', 'status' => 'enrolled']);

        (new SendAbsenceAlerts($section->id, [$student->id]))->handle(app(SmsService::class));

        Http::assertNothingSent();
    }

    // ── Notification channel ─────────────────────────────────────────────────

    public function test_fee_due_reminder_includes_sms_channel_and_message(): void
    {
        $notification = new FeeDueReminder($this->makeInvoice());
        $user = User::factory()->make(['phone' => '012345678']);

        $this->assertContains(SmsChannel::class, $notification->via($user));
        $this->assertStringContainsString($notification->invoice->number, $notification->toSms($user));
    }

    public function test_result_published_sms_sent_to_user_with_phone_and_skipped_without(): void
    {
        $this->usePlasgate();
        Http::fake(['cloudapi.plasgate.com/*' => Http::response([], 200)]);

        $year = AcademicYear::create(['name' => 'Y-' . uniqid(), 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => true]);
        $exam = Exam::create(['academic_year_id' => $year->id, 'name' => 'SMS Exam', 'type' => 'midterm', 'semester' => 1, 'weight' => 1, 'is_published' => true]);

        $withPhone    = User::factory()->create(['status' => 'active', 'phone' => '012999888']);
        $withoutPhone = User::factory()->create(['status' => 'active', 'phone' => null]);

        $withPhone->notify(new ResultPublished($exam));
        $withoutPhone->notify(new ResultPublished($exam));

        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request['to'] === '85512999888'
            && str_contains($request['content'], 'SMS Exam'));
    }

    private function makeInvoice(): Invoice
    {
        $year    = AcademicYear::create(['name' => 'Y-' . uniqid(), 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => true]);
        $student = Student::create(['student_code' => 'S-' . uniqid(), 'name_en' => 'Payer', 'gender' => 'male', 'status' => 'enrolled']);

        return Invoice::create([
            'number' => 'INV-' . uniqid(), 'student_id' => $student->id,
            'academic_year_id' => $year->id, 'term' => 'term_1',
            'subtotal' => 100, 'discount' => 0, 'total' => 100, 'paid' => 0, 'status' => 'unpaid',
        ]);
    }
}
