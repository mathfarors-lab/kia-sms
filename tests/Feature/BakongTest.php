<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\BakongCallback;
use App\Models\BakongFailedVerification;
use App\Models\Invoice;
use App\Models\Student;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BakongTest extends TestCase
{
    use RefreshDatabase;

    private const TEST_SECRET = 'test-bakong-secret-32bytes-padded';

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.bakong.webhook_secret'    => self::TEST_SECRET,
            'services.bakong.signature_header'  => 'X-Bakong-Signature',
            'services.bakong.signature_algo'    => 'sha256',
            // These tests exercise the push-webhook path — re-enable it.
            'services.bakong.disable_webhook'   => false,
        ]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** Returns [data, sig] for a valid signed POST. */
    private function signedPost(array $data, string $secret = self::TEST_SECRET): array
    {
        $body = json_encode($data);
        $sig  = hash_hmac('sha256', $body, $secret);
        return [$data, $sig];
    }

    private function basePayload(array $overrides = []): array
    {
        return array_merge([
            'transaction_id' => 'TXN-' . uniqid(),
            'amount'         => '50.00',
            'currency'       => 'USD',
            'status'         => 'confirmed',
            'payerAccount'   => '855123456789@wing',
            'merchantRef'    => 'INV-2026-001',
        ], $overrides);
    }

    private function makeInvoice(string $number = 'INV-2026-001', string $total = '50.00'): Invoice
    {
        $year    = AcademicYear::create(['name' => 'Test', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => true]);
        $user    = User::factory()->create(['status' => 'active']);
        $student = Student::create([
            'user_id'      => $user->id,
            'name_en'      => 'Test Student',
            'name_km'      => 'ត',
            'student_code' => 'S-' . uniqid(),
            'gender'       => 'male',
            'status'       => 'enrolled',
        ]);
        return Invoice::create([
            'number'           => $number,
            'student_id'       => $student->id,
            'academic_year_id' => $year->id,
            'term'             => 'term_1',
            'subtotal'         => $total,
            'discount'         => '0.00',
            'total'            => $total,
            'status'           => 'unpaid',
        ]);
    }

    // -------------------------------------------------------------------------
    // ✅ Happy path
    // -------------------------------------------------------------------------

    public function test_valid_signed_callback_is_recorded(): void
    {
        [$data, $sig] = $this->signedPost(
            $this->basePayload(['transaction_id' => 'TXN-VALID-001'])
        );

        $this->postJson(route('webhooks.bakong'), $data, ['X-Bakong-Signature' => $sig])
            ->assertOk()
            ->assertJson(['received' => true]);

        $this->assertDatabaseHas('bakong_callbacks', [
            'transaction_reference' => 'TXN-VALID-001',
            'status'                => 'confirmed',
            'signature_valid'       => true,
        ]);
        $this->assertDatabaseEmpty('bakong_failed_verifications');
    }

    public function test_valid_callback_applies_payment_to_invoice(): void
    {
        $invoice = $this->makeInvoice('INV-PAY-001', '75.00');

        [$data, $sig] = $this->signedPost($this->basePayload([
            'transaction_id' => 'TXN-PAY-001',
            'amount'         => '75.00',
            'merchantRef'    => 'INV-PAY-001',
        ]));

        $this->postJson(route('webhooks.bakong'), $data, ['X-Bakong-Signature' => $sig])
            ->assertOk();

        $this->assertDatabaseHas('payments', [
            'invoice_id' => $invoice->id,
            'amount'     => '75.00',
            'method'     => 'khqr',
            'reference'  => 'TXN-PAY-001',
        ]);
        $this->assertEquals('paid', $invoice->fresh()->status);
    }

    // Regression — applyPayment() set `status` on full settlement but never updated
    // the cached `paid` column, so remainingBalance() (and the cash-payment form's
    // pre-filled amount) still showed the full original total after a partial KHQR
    // payment. A second, manual payment could then double-collect on top of it.

    public function test_partial_khqr_payment_updates_invoice_paid_and_status(): void
    {
        $invoice = $this->makeInvoice('INV-PARTIAL-001', '100.00');

        [$data, $sig] = $this->signedPost($this->basePayload([
            'transaction_id' => 'TXN-PARTIAL-001',
            'amount'         => '40.00',
            'merchantRef'    => 'INV-PARTIAL-001',
        ]));

        $this->postJson(route('webhooks.bakong'), $data, ['X-Bakong-Signature' => $sig])
            ->assertOk();

        $invoice->refresh();
        $this->assertEquals('40.00', $invoice->paid);
        $this->assertEquals('partial', $invoice->status);
        $this->assertEquals('60.00', $invoice->remainingBalance());
    }

    public function test_second_khqr_payment_completes_a_partially_paid_invoice(): void
    {
        $invoice = $this->makeInvoice('INV-PARTIAL-002', '100.00');

        [$data1, $sig1] = $this->signedPost($this->basePayload([
            'transaction_id' => 'TXN-PARTIAL-002A',
            'amount'         => '40.00',
            'merchantRef'    => 'INV-PARTIAL-002',
        ]));
        $this->postJson(route('webhooks.bakong'), $data1, ['X-Bakong-Signature' => $sig1])->assertOk();

        [$data2, $sig2] = $this->signedPost($this->basePayload([
            'transaction_id' => 'TXN-PARTIAL-002B',
            'amount'         => '60.00',
            'merchantRef'    => 'INV-PARTIAL-002',
        ]));
        $this->postJson(route('webhooks.bakong'), $data2, ['X-Bakong-Signature' => $sig2])->assertOk();

        $invoice->refresh();
        $this->assertEquals('100.00', $invoice->paid);
        $this->assertEquals('paid', $invoice->status);
        $this->assertEquals('0.00', $invoice->remainingBalance());
    }

    // -------------------------------------------------------------------------
    // 🔒 Idempotency — issue 1 core fix
    // -------------------------------------------------------------------------

    /**
     * A forged callback must NOT poison the idempotency slot for the real one.
     * Sequence: attacker posts forged → real callback arrives → payment applied once.
     */
    public function test_forged_then_valid_same_ref_payment_applied_exactly_once(): void
    {
        $invoice = $this->makeInvoice('INV-POISON-001', '50.00');
        $ref     = 'TXN-POISON-001';

        // 1. Attacker posts a forged callback for this reference
        $forgery = hash_hmac('sha256', json_encode($this->basePayload(['transaction_id' => $ref])), 'wrong-key');
        $this->postJson(
            route('webhooks.bakong'),
            $this->basePayload(['transaction_id' => $ref, 'merchantRef' => 'INV-POISON-001']),
            ['X-Bakong-Signature' => $forgery]
        )->assertOk();

        // Forged attempt goes to audit table, NOT to bakong_callbacks
        $this->assertDatabaseEmpty('bakong_callbacks');
        $this->assertDatabaseCount('bakong_failed_verifications', 1);
        $this->assertDatabaseEmpty('payments');

        // 2. Real callback arrives with the same reference
        [$data, $sig] = $this->signedPost($this->basePayload([
            'transaction_id' => $ref,
            'amount'         => '50.00',
            'merchantRef'    => 'INV-POISON-001',
        ]));
        $this->postJson(route('webhooks.bakong'), $data, ['X-Bakong-Signature' => $sig])
            ->assertOk();

        // Real callback is processed — idempotency slot was not poisoned
        $this->assertDatabaseCount('bakong_callbacks', 1);
        $this->assertDatabaseHas('bakong_callbacks', [
            'transaction_reference' => $ref,
            'signature_valid'       => true,
        ]);

        // Payment applied exactly once
        $this->assertDatabaseCount('payments', 1);
        $this->assertEquals('paid', $invoice->fresh()->status);
    }

    /**
     * Valid callback → duplicate valid callback → payment applied exactly once.
     */
    public function test_valid_then_duplicate_valid_no_double_payment(): void
    {
        $invoice = $this->makeInvoice('INV-DUP-001', '40.00');

        [$data, $sig] = $this->signedPost($this->basePayload([
            'transaction_id' => 'TXN-DUP-PAY-001',
            'amount'         => '40.00',
            'merchantRef'    => 'INV-DUP-001',
        ]));

        $this->postJson(route('webhooks.bakong'), $data, ['X-Bakong-Signature' => $sig])->assertOk();
        $this->postJson(route('webhooks.bakong'), $data, ['X-Bakong-Signature' => $sig])->assertOk();

        // Exactly one callback row
        $this->assertDatabaseCount('bakong_callbacks', 1);
        // Exactly one payment — NOT two
        $this->assertDatabaseCount('payments', 1);
        $this->assertEquals('paid', $invoice->fresh()->status);
    }

    public function test_duplicate_callback_no_invoice_still_noop(): void
    {
        [$data, $sig] = $this->signedPost(
            $this->basePayload(['transaction_id' => 'TXN-DUP-NOINV'])
        );

        $this->postJson(route('webhooks.bakong'), $data, ['X-Bakong-Signature' => $sig])->assertOk();
        $this->postJson(route('webhooks.bakong'), $data, ['X-Bakong-Signature' => $sig])->assertOk();

        $this->assertDatabaseCount('bakong_callbacks', 1);
    }

    // -------------------------------------------------------------------------
    // 🚫 Signature failures — must NOT apply payment, must write audit row
    // -------------------------------------------------------------------------

    public function test_forged_signature_is_rejected(): void
    {
        $data    = $this->basePayload(['transaction_id' => 'TXN-FORGED-001']);
        $forgery = hash_hmac('sha256', json_encode($data), 'wrong-secret');

        $this->postJson(route('webhooks.bakong'), $data, ['X-Bakong-Signature' => $forgery])
            ->assertOk(); // 200 — don't leak rejection reason to caller

        $this->assertDatabaseHas('bakong_failed_verifications', [
            'transaction_reference' => 'TXN-FORGED-001',
            'reason'                => 'bad-sig',
        ]);
        $this->assertDatabaseEmpty('bakong_callbacks');
        $this->assertDatabaseEmpty('payments');
    }

    public function test_missing_signature_is_rejected(): void
    {
        $data = $this->basePayload(['transaction_id' => 'TXN-NOSIG-001']);

        $this->postJson(route('webhooks.bakong'), $data) // no signature header
            ->assertOk();

        $this->assertDatabaseHas('bakong_failed_verifications', [
            'transaction_reference' => 'TXN-NOSIG-001',
            'reason'                => 'missing-header',
        ]);
        $this->assertDatabaseEmpty('bakong_callbacks');
    }

    public function test_no_secret_configured_rejects_all(): void
    {
        config(['services.bakong.webhook_secret' => null]);

        [$data, $sig] = $this->signedPost(
            $this->basePayload(['transaction_id' => 'TXN-NOSECRET-001'])
        );

        $this->postJson(route('webhooks.bakong'), $data, ['X-Bakong-Signature' => $sig])
            ->assertOk();

        $this->assertDatabaseHas('bakong_failed_verifications', [
            'transaction_reference' => 'TXN-NOSECRET-001',
            'reason'                => 'secret-unset',
        ]);
        $this->assertDatabaseEmpty('bakong_callbacks');
        $this->assertDatabaseEmpty('payments');
    }

    // -------------------------------------------------------------------------
    // 📊 Dashboard surfacing — issue 3
    // -------------------------------------------------------------------------

    public function test_failed_verification_count_increments_and_is_visible_to_admin(): void
    {
        // Set up admin user
        Permission::firstOrCreate(['name' => Permissions::ANALYTICS_VIEW]);
        Permission::firstOrCreate(['name' => Permissions::SETTINGS_MANAGE]);
        $role = Role::firstOrCreate(['name' => 'admin']);
        $role->syncPermissions([Permissions::ANALYTICS_VIEW, Permissions::SETTINGS_MANAGE]);
        $admin = User::factory()->create(['status' => 'active']);
        $admin->assignRole('admin');

        // Post two failed callbacks
        $data1 = $this->basePayload(['transaction_id' => 'TXN-COUNT-A']);
        $data2 = $this->basePayload(['transaction_id' => 'TXN-COUNT-B']);
        $bad   = hash_hmac('sha256', json_encode($data1), 'wrong');

        $this->postJson(route('webhooks.bakong'), $data1, ['X-Bakong-Signature' => $bad])->assertOk();
        $this->postJson(route('webhooks.bakong'), $data2)->assertOk(); // missing header

        $this->assertDatabaseCount('bakong_failed_verifications', 2);

        // Admin can reach the audit list
        $this->actingAs($admin)
            ->get(route('admin.bakong.failed'))
            ->assertOk()
            ->assertSee('TXN-COUNT-A')
            ->assertSee('TXN-COUNT-B');
    }

    // -------------------------------------------------------------------------
    // 🔧 Configurable signing scheme — issue 2
    // -------------------------------------------------------------------------

    public function test_configurable_header_name_is_used(): void
    {
        config(['services.bakong.signature_header' => 'X-Custom-Sig']);

        $data = $this->basePayload(['transaction_id' => 'TXN-CUSTOMHDR']);
        $body = json_encode($data);
        $sig  = hash_hmac('sha256', $body, self::TEST_SECRET);

        // Correct sig in custom header → valid
        $this->postJson(route('webhooks.bakong'), $data, ['X-Custom-Sig' => $sig])
            ->assertOk();

        $this->assertDatabaseHas('bakong_callbacks', [
            'transaction_reference' => 'TXN-CUSTOMHDR',
            'signature_valid'       => true,
        ]);
    }

    public function test_configurable_algo_is_used(): void
    {
        config(['services.bakong.signature_algo' => 'sha512']);

        $data = $this->basePayload(['transaction_id' => 'TXN-SHA512']);
        $body = json_encode($data);
        $sig  = hash_hmac('sha512', $body, self::TEST_SECRET);

        $this->postJson(route('webhooks.bakong'), $data, ['X-Bakong-Signature' => $sig])
            ->assertOk();

        $this->assertDatabaseHas('bakong_callbacks', [
            'transaction_reference' => 'TXN-SHA512',
            'signature_valid'       => true,
        ]);
    }

    // -------------------------------------------------------------------------
    // 📋 Input validation
    // -------------------------------------------------------------------------

    public function test_missing_transaction_id_returns_422(): void
    {
        $this->postJson(route('webhooks.bakong'), ['amount' => '10.00'])
            ->assertStatus(422);
    }

    // -------------------------------------------------------------------------
    // ⚡ Concurrent idempotency — insert gates payment
    // -------------------------------------------------------------------------

    /**
     * Simulates the concurrent-arrival race: both requests pass the pre-check
     * (neither sees an existing row), then both call applyVerifiedCallback().
     * The DB UNIQUE constraint ensures only one INSERT wins and only one payment
     * is created. The catch branch returns the existing row WITHOUT calling
     * applyPayment() — preventing double-pay.
     */
    public function test_concurrent_verified_callbacks_apply_payment_exactly_once(): void
    {
        $invoice = $this->makeInvoice('INV-CONC-001', '50.00');
        $ref     = 'TXN-CONC-001';
        $data    = $this->basePayload([
            'transaction_id' => $ref,
            'amount'         => '50.00',
            'merchantRef'    => 'INV-CONC-001',
        ]);
        $body    = json_encode($data);
        $payload = array_merge($data, ['rawBody' => $body, 'signature' => hash_hmac('sha256', $body, self::TEST_SECRET)]);

        $service = app(\App\Services\BakongWebhookService::class);

        // Both calls bypass the controller — simulating two processes that both
        // passed the pre-check (saw no existing row) before either inserted.
        $cb1 = $service->applyVerifiedCallback($ref, $payload);
        $cb2 = $service->applyVerifiedCallback($ref, $payload);

        $this->assertDatabaseCount('bakong_callbacks', 1);
        $this->assertDatabaseCount('payments', 1);
        $this->assertEquals('paid', $invoice->fresh()->status);

        // First call created the row; second found the existing row.
        $this->assertTrue($cb1->wasRecentlyCreated);
        $this->assertFalse($cb2->wasRecentlyCreated);
    }

    // -------------------------------------------------------------------------
    // ✅ Payload validation
    // -------------------------------------------------------------------------

    public function test_unknown_invoice_reference_is_flagged_as_unmatched(): void
    {
        [$data, $sig] = $this->signedPost($this->basePayload([
            'transaction_id' => 'TXN-UNKNOWN-REF',
            'merchantRef'    => 'INV-DOES-NOT-EXIST',
        ]));

        $this->postJson(route('webhooks.bakong'), $data, ['X-Bakong-Signature' => $sig])
            ->assertOk();

        // Verified callback is recorded but payment is NOT applied.
        $this->assertDatabaseHas('bakong_callbacks', [
            'transaction_reference' => 'TXN-UNKNOWN-REF',
            'flag_reason'           => 'unmatched-ref',
            'signature_valid'       => true,
        ]);
        $this->assertDatabaseEmpty('payments');
    }

    public function test_overpayment_is_flagged_as_amount_mismatch(): void
    {
        $this->makeInvoice('INV-OVER-001', '50.00');

        [$data, $sig] = $this->signedPost($this->basePayload([
            'transaction_id' => 'TXN-OVER-001',
            'amount'         => '99.99',   // more than the $50.00 outstanding
            'merchantRef'    => 'INV-OVER-001',
        ]));

        $this->postJson(route('webhooks.bakong'), $data, ['X-Bakong-Signature' => $sig])
            ->assertOk();

        $this->assertDatabaseHas('bakong_callbacks', [
            'transaction_reference' => 'TXN-OVER-001',
            'flag_reason'           => 'amount-mismatch',
            'signature_valid'       => true,
        ]);
        $this->assertDatabaseEmpty('payments');
    }

    public function test_currency_mismatch_is_flagged(): void
    {
        $this->makeInvoice('INV-KHR-001', '50.00');

        [$data, $sig] = $this->signedPost($this->basePayload([
            'transaction_id' => 'TXN-KHR-001',
            'currency'       => 'KHR',
            'merchantRef'    => 'INV-KHR-001',
        ]));

        $this->postJson(route('webhooks.bakong'), $data, ['X-Bakong-Signature' => $sig])
            ->assertOk();

        $this->assertDatabaseHas('bakong_callbacks', [
            'transaction_reference' => 'TXN-KHR-001',
            'flag_reason'           => 'currency-mismatch',
        ]);
        $this->assertDatabaseEmpty('payments');
    }

    // -------------------------------------------------------------------------
    // 🔁 Admin replay
    // -------------------------------------------------------------------------

    public function test_replay_applies_payment_after_correct_secret_is_configured(): void
    {
        $invoice = $this->makeInvoice('INV-REPLAY-001', '60.00');

        // 1. Callback arrives when wrong secret is configured → fails verification.
        $data     = $this->basePayload([
            'transaction_id' => 'TXN-REPLAY-001',
            'amount'         => '60.00',
            'merchantRef'    => 'INV-REPLAY-001',
        ]);
        $body     = json_encode($data);
        $realSig  = hash_hmac('sha256', $body, self::TEST_SECRET);

        // Use the wrong secret → bad-sig
        $badSig   = hash_hmac('sha256', $body, 'wrong-secret');
        $this->postJson(route('webhooks.bakong'), $data, ['X-Bakong-Signature' => $badSig])
            ->assertOk();

        $this->assertDatabaseCount('bakong_failed_verifications', 1);
        $this->assertDatabaseEmpty('payments');

        // 2. Operator fixes the secret (already configured in setUp) and replays.
        $this->setupAdminUser();
        $failed = BakongFailedVerification::first();

        // The row was stored with the real signature, but under the wrong-secret window.
        // We need to simulate that the stored received_signature is the real one.
        $failed->update(['received_signature' => $realSig, 'raw_body' => $body]);

        $this->actingAs($this->adminUser())
            ->post(route('admin.bakong.replay', $failed))
            ->assertRedirect();

        $failed->refresh();
        $this->assertEquals('applied', $failed->replay_result);
        $this->assertNotNull($failed->replayed_at);

        $this->assertDatabaseHas('bakong_callbacks', [
            'transaction_reference' => 'TXN-REPLAY-001',
            'signature_valid'       => true,
        ]);
        $this->assertDatabaseCount('payments', 1);
        $this->assertEquals('paid', $invoice->fresh()->status);
    }

    public function test_replay_is_idempotent_when_already_applied(): void
    {
        $invoice = $this->makeInvoice('INV-REPLAY-IDEM-001', '30.00');

        $data    = $this->basePayload([
            'transaction_id' => 'TXN-IDEM-001',
            'amount'         => '30.00',
            'merchantRef'    => 'INV-REPLAY-IDEM-001',
        ]);
        $body    = json_encode($data);
        $sig     = hash_hmac('sha256', $body, self::TEST_SECRET);

        // Failed verification row (simulates misconfigured window)
        $failed  = BakongFailedVerification::create([
            'transaction_reference' => 'TXN-IDEM-001',
            'reason'                => 'bad-sig',
            'raw_payload'           => $data,
            'raw_body'              => $body,
            'received_signature'    => $sig,
        ]);

        // Apply the payment directly (simulates a concurrent correct callback arriving)
        $service = app(\App\Services\BakongWebhookService::class);
        $service->applyVerifiedCallback('TXN-IDEM-001', array_merge($data, ['rawBody' => $body, 'signature' => $sig]));

        $this->assertDatabaseCount('payments', 1);

        // Replay — should detect existing row and return duplicate, no new payment.
        $this->setupAdminUser();
        $this->actingAs($this->adminUser())
            ->post(route('admin.bakong.replay', $failed))
            ->assertRedirect();

        $failed->refresh();
        $this->assertEquals('duplicate', $failed->replay_result);
        $this->assertDatabaseCount('payments', 1); // still exactly one
        $this->assertEquals('paid', $invoice->fresh()->status);
    }

    // -------------------------------------------------------------------------
    // Private test helpers
    // -------------------------------------------------------------------------

    private ?object $cachedAdmin = null;

    private function setupAdminUser(): void
    {
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => \App\Support\Permissions::SETTINGS_MANAGE]);
        $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']);
        $role->syncPermissions([\App\Support\Permissions::SETTINGS_MANAGE]);
        $this->cachedAdmin = User::factory()->create(['status' => 'active']);
        $this->cachedAdmin->assignRole('admin');
    }

    private function adminUser(): object
    {
        return $this->cachedAdmin;
    }

    /*
    |--------------------------------------------------------------------------
    | REAL PROVIDER SANDBOX FIXTURE — paste here before go-live
    |--------------------------------------------------------------------------
    | Copy a real callback from the Bakong merchant sandbox, paste the raw
    | JSON body and header value below, then confirm this test passes.
    | If it doesn't, the header name or algorithm in config/services.php is wrong.
    |
    | public function test_real_provider_sandbox_fixture(): void
    | {
    |     config([
    |         'services.bakong.webhook_secret'   => 'your-sandbox-secret',
    |         'services.bakong.signature_header' => 'X-Bakong-Signature',   // confirm in docs
    |         'services.bakong.signature_algo'   => 'sha256',               // confirm in docs
    |     ]);
    |
    |     $rawBody = '{"transaction_id":"SANDBOX-TXN-001","amount":"10.00","status":"confirmed",...}';
    |     $realSig = 'the-actual-header-value-from-the-sandbox-request';
    |
    |     $data = json_decode($rawBody, true);
    |     $this->postJson(route('webhooks.bakong'), $data, [
    |         config('services.bakong.signature_header') => $realSig,
    |     ])->assertOk();
    |
    |     $this->assertDatabaseHas('bakong_callbacks', [
    |         'transaction_reference' => 'SANDBOX-TXN-001',
    |         'signature_valid'       => true,
    |     ]);
    | }
    */
}
