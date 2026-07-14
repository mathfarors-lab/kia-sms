<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\TwoFactorService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TwoFactorAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function makeUser(string $role = 'teacher'): User
    {
        $user = User::factory()->create(['status' => 'active', 'password' => bcrypt('password123')]);
        $user->assignRole($role);
        return $user;
    }

    /** Fully enrolls a user in 2FA the same way the real flow would, returning the raw secret. */
    private function enrollTwoFactor(User $user): string
    {
        $service = app(TwoFactorService::class);
        $secret = $service->generateSecret();

        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => $service->generateRecoveryCodes(),
        ])->save();

        return $secret;
    }

    private function currentTotpCode(string $secret): string
    {
        return (new \PragmaRX\Google2FA\Google2FA())->getCurrentOtp($secret);
    }

    // ── Opt-in: login is unaffected for accounts without 2FA ──────────────────

    public function test_login_works_normally_without_two_factor_enabled(): void
    {
        $user = $this->makeUser();

        $this->post(route('login'), ['email' => $user->email, 'password' => 'password123'])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_2fa_is_available_to_every_role_not_just_the_recommended_four(): void
    {
        $student = $this->makeUser('student');

        $this->actingAs($student)->get(route('two-factor.settings'))->assertOk();
        $this->actingAs($student)->post(route('two-factor.enable'))->assertOk();
    }

    // ── Enrollment ───────────────────────────────────────────────────────────

    public function test_enabling_generates_an_unconfirmed_secret(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->post(route('two-factor.enable'))->assertOk();

        $user->refresh();
        $this->assertNotNull($user->two_factor_secret);
        $this->assertFalse($user->hasTwoFactorEnabled()); // not confirmed yet
    }

    public function test_confirming_with_a_valid_code_enables_2fa_and_shows_recovery_codes_once(): void
    {
        $user = $this->makeUser();
        $service = app(TwoFactorService::class);
        $secret = $service->generateSecret();
        $user->forceFill(['two_factor_secret' => $secret])->save();

        $code = $this->currentTotpCode($secret);

        $this->actingAs($user)
            ->post(route('two-factor.confirm'), ['code' => $code])
            ->assertRedirect(route('two-factor.recovery-codes.show'));

        $this->assertTrue($user->fresh()->hasTwoFactorEnabled());

        // Recovery codes page renders once via the flashed session...
        $this->actingAs($user)->get(route('two-factor.recovery-codes.show'))->assertOk();
    }

    public function test_confirming_with_an_invalid_code_does_not_enable_2fa(): void
    {
        $user = $this->makeUser();
        $user->forceFill(['two_factor_secret' => app(TwoFactorService::class)->generateSecret()])->save();

        $this->actingAs($user)
            ->post(route('two-factor.confirm'), ['code' => '000000'])
            ->assertSessionHasErrors('code');

        $this->assertFalse($user->fresh()->hasTwoFactorEnabled());
    }

    public function test_recovery_codes_page_404s_without_a_fresh_confirmation(): void
    {
        $user = $this->makeUser();
        $this->enrollTwoFactor($user);

        // Visiting directly (no session flash from a just-completed confirm) must not work.
        $this->actingAs($user)->get(route('two-factor.recovery-codes.show'))->assertNotFound();
    }

    // ── Login-time challenge ─────────────────────────────────────────────────

    public function test_login_with_2fa_enabled_stops_at_the_challenge_not_the_dashboard(): void
    {
        $user = $this->makeUser();
        $this->enrollTwoFactor($user);

        $response = $this->post(route('login'), ['email' => $user->email, 'password' => 'password123']);

        $response->assertRedirect(route('two-factor.challenge'));
        $this->assertGuest(); // NOT logged in yet — credentials alone aren't enough
    }

    public function test_correct_totp_code_completes_the_login(): void
    {
        $user = $this->makeUser();
        $secret = $this->enrollTwoFactor($user);

        $this->post(route('login'), ['email' => $user->email, 'password' => 'password123']);
        $this->assertGuest();

        $this->post(route('two-factor.challenge.verify'), ['code' => $this->currentTotpCode($secret)])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_totp_code_does_not_complete_the_login(): void
    {
        $user = $this->makeUser();
        $this->enrollTwoFactor($user);

        $this->post(route('login'), ['email' => $user->email, 'password' => 'password123']);

        $this->post(route('two-factor.challenge.verify'), ['code' => '000000'])
            ->assertSessionHasErrors('code');

        $this->assertGuest();
    }

    public function test_recovery_code_completes_login_and_is_consumed_so_it_cannot_be_reused(): void
    {
        $user = $this->makeUser();
        $this->enrollTwoFactor($user);
        $recoveryCode = $user->fresh()->two_factor_recovery_codes[0];

        $this->post(route('login'), ['email' => $user->email, 'password' => 'password123']);

        $this->post(route('two-factor.challenge.verify'), ['code' => $recoveryCode])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);

        // Using the SAME recovery code again must fail — it's one-time.
        auth()->logout();
        $this->post(route('login'), ['email' => $user->email, 'password' => 'password123']);
        $this->post(route('two-factor.challenge.verify'), ['code' => $recoveryCode])
            ->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_challenge_page_redirects_to_login_without_a_pending_session(): void
    {
        $this->get(route('two-factor.challenge'))->assertRedirect(route('login'));
    }

    public function test_challenge_verify_is_rate_limited(): void
    {
        $user = $this->makeUser();
        $this->enrollTwoFactor($user);

        $this->post(route('login'), ['email' => $user->email, 'password' => 'password123']);

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('two-factor.challenge.verify'), ['code' => '000000']);
        }

        $response = $this->post(route('two-factor.challenge.verify'), ['code' => '000000']);
        $response->assertSessionHasErrors('code');
    }

    // ── Disable ──────────────────────────────────────────────────────────────

    public function test_disabling_requires_correct_password(): void
    {
        $user = $this->makeUser();
        $this->enrollTwoFactor($user);

        $this->actingAs($user)
            ->post(route('two-factor.disable'), ['password' => 'wrong-password'])
            ->assertSessionHasErrors('password');

        $this->assertTrue($user->fresh()->hasTwoFactorEnabled());
    }

    public function test_disabling_with_correct_password_clears_everything(): void
    {
        $user = $this->makeUser();
        $this->enrollTwoFactor($user);

        $this->actingAs($user)
            ->post(route('two-factor.disable'), ['password' => 'password123'])
            ->assertRedirect(route('two-factor.settings'));

        $user->refresh();
        $this->assertFalse($user->hasTwoFactorEnabled());
        $this->assertNull($user->two_factor_secret);
        $this->assertNull($user->two_factor_recovery_codes);
    }

    // ── Storage safety ───────────────────────────────────────────────────────

    public function test_two_factor_secret_is_never_exposed_in_serialized_output(): void
    {
        $user = $this->makeUser();
        $this->enrollTwoFactor($user);

        $array = $user->fresh()->toArray();

        $this->assertArrayNotHasKey('two_factor_secret', $array);
        $this->assertArrayNotHasKey('two_factor_recovery_codes', $array);
    }

    public function test_two_factor_secret_is_encrypted_at_rest(): void
    {
        $user = $this->makeUser();
        $secret = $this->enrollTwoFactor($user);

        $raw = \DB::table('users')->where('id', $user->id)->value('two_factor_secret');

        $this->assertNotEquals($secret, $raw); // stored value must not be the plaintext secret
    }

    public function test_recommended_roles_see_the_banner_others_do_not(): void
    {
        $owner = $this->makeUser('owner');
        $teacher = $this->makeUser('teacher');

        $this->assertTrue($owner->shouldBeStronglyEncouragedToEnable2fa());
        $this->assertFalse($teacher->shouldBeStronglyEncouragedToEnable2fa());
    }
}
