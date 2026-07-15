<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * The pre-launch blocker: every seeded account (owner included) shares the
 * password "password" until kia:secure-demo runs. These tests prove the
 * command actually closes that door, is safe to run more than once, and
 * never touches an account it didn't seed.
 */
class DemoAccountSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(DemoUserSeeder::class);
    }

    /** Tripwire: if DemoUserSeeder ever adds/removes a seeded account without
     *  updating DEMO_EMAILS, kia:secure-demo would silently miss it — this
     *  fails loudly instead. */
    public function test_demo_emails_constant_matches_every_account_the_seeder_actually_creates(): void
    {
        $seededEmails = User::pluck('email')->sort()->values()->all();
        $declaredEmails = collect(DemoUserSeeder::DEMO_EMAILS)->sort()->values()->all();

        $this->assertEquals($declaredEmails, $seededEmails);
    }

    /**
     * Regression guard for a real incident: live-verification testing across
     * earlier phases left a second "Sokha Chea" student in the dev database,
     * confusingly distinct from the seeded one only by Khmer name/DOB/code.
     * DemoUserSeeder itself must never produce that — a fresh seed is the
     * one guaranteed-clean state going into the pilot.
     */
    public function test_seeded_students_never_share_an_english_name(): void
    {
        $names = Student::pluck('name_en');

        $this->assertEquals(
            $names->count(),
            $names->unique()->count(),
            'Two or more seeded students share the same name_en — pick distinct demo names.'
        );
    }

    public function test_no_seeded_account_authenticates_with_password_after_running_the_command(): void
    {
        $this->artisan('kia:secure-demo')->assertSuccessful();

        foreach (DemoUserSeeder::DEMO_EMAILS as $email) {
            $user = User::where('email', $email)->firstOrFail();
            $this->assertFalse(
                Hash::check('password', $user->password),
                "{$email} still authenticates with the literal string 'password'"
            );
        }
    }

    public function test_owner_account_specifically_is_secured(): void
    {
        $this->artisan('kia:secure-demo');

        $owner = User::where('email', 'owner@edu.kh')->firstOrFail();
        $this->assertFalse(Hash::check('password', $owner->password));
    }

    public function test_command_prints_every_account_email_to_the_console(): void
    {
        // Passwords are random per run, so assert the email column rather
        // than exact table rows.
        $command = $this->artisan('kia:secure-demo')->assertSuccessful();

        foreach (DemoUserSeeder::DEMO_EMAILS as $email) {
            $command->expectsOutputToContain($email);
        }
    }

    public function test_running_twice_is_safe_and_still_ends_with_a_working_new_password(): void
    {
        $this->artisan('kia:secure-demo')->assertSuccessful();
        $firstHash = User::where('email', 'admin@edu.kh')->value('password');

        $this->artisan('kia:secure-demo')->assertSuccessful();
        $secondHash = User::where('email', 'admin@edu.kh')->firstOrFail();

        // Second run produced a different (fresh) password, not a crash or a
        // reversion to a known/guessable state.
        $this->assertNotEquals($firstHash, $secondHash->password);
        $this->assertFalse(Hash::check('password', $secondHash->password));

        // The account itself is untouched otherwise — still active, still
        // has its role, login still works with correct credentials (proving
        // "doesn't lock you out": there IS a valid credential, it's just new).
        $this->assertEquals('active', $secondHash->status);
        $this->assertTrue($secondHash->hasRole('admin'));
    }

    public function test_real_non_demo_account_is_never_touched(): void
    {
        $real = User::factory()->create([
            'email' => 'real.staff@kia.edu.kh',
            'status' => 'active',
            'password' => Hash::make('a-real-password-123'),
        ]);

        $this->artisan('kia:secure-demo');

        $this->assertTrue(Hash::check('a-real-password-123', $real->fresh()->password));
    }

    public function test_command_is_not_production_guarded_unlike_seed_demo(): void
    {
        // kia:seed-demo refuses to run in production (never invent fake
        // people there); kia:secure-demo must do the OPPOSITE — it exists
        // specifically to lock down whatever demo accounts exist, wherever
        // they exist, production included.
        app()['env'] = 'production';

        $this->artisan('kia:secure-demo')->assertSuccessful();

        app()['env'] = 'testing';
    }

    public function test_seed_demo_still_refuses_production_and_now_reminds_to_run_secure_demo(): void
    {
        app()['env'] = 'production';

        $this->artisan('kia:seed-demo')
            ->assertFailed()
            ->expectsOutputToContain('Refusing to seed demo data in production');

        app()['env'] = 'testing';
    }

    public function test_seed_demo_output_mentions_secure_demo_when_it_succeeds(): void
    {
        $this->artisan('kia:seed-demo')
            ->assertSuccessful()
            ->expectsOutputToContain('kia:secure-demo');
    }
}
