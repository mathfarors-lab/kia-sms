<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\BakongCallback;
use App\Models\Invoice;
use App\Models\PaymentIntent;
use App\Models\Student;
use App\Models\User;
use App\Services\BakongPollingService;
use App\Services\BakongTokenService;
use App\Services\KhqrService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BakongPollingTest extends TestCase
{
    use RefreshDatabase;

    private const BASE_URL    = 'https://api.bakong.test';
    private const MERCHANT_EMAIL = 'merchant@kia.test';

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.bakong.base_url'      => self::BASE_URL,
            'services.bakong.email'         => self::MERCHANT_EMAIL,
            'services.bakong.fake_mode'     => false,
            'services.bakong.qr_ttl_minutes'=> 10,
        ]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeInvoice(string $number = 'INV-POLL-001', string $total = '50.00'): Invoice
    {
        $year    = AcademicYear::create(['name' => 'Test', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => true]);
        $user    = User::factory()->create(['status' => 'active']);
        $student = Student::create([
            'user_id'      => $user->id,
            'name_en'      => 'Poll Student',
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

    private function makeIntent(Invoice $invoice, array $overrides = []): PaymentIntent
    {
        $qrString = 'KHQR-TEST|' . $invoice->number . '|' . $invoice->total . '|USD|' . now()->timestamp;
        return PaymentIntent::create(array_merge([
            'invoice_id'  => $invoice->id,
            'qr_string'   => $qrString,
            'md5_hash'    => md5($qrString),
            'bill_number' => $invoice->number,
            'amount'      => $invoice->total,
            'currency'    => 'USD',
            'expires_at'  => now()->addMinutes(10),
            'status'      => 'pending',
        ], $overrides));
    }

    /** Fake a successful check_transaction_by_md5 response for a given transaction. */
    private function fakePollResponse(string $md5, string $hash, string $amount = '50.00', string $currency = 'USD'): void
    {
        Http::fake([
            self::BASE_URL . '/v1/auth/login' => Http::response([
                'responseCode' => 0,
                'data'         => ['token' => 'test-token', 'expiresIn' => 7200],
            ]),
            self::BASE_URL . '/v1/merchant/check-transaction-by-md5' => Http::response([
                'responseCode' => 0,
                'data'         => [[
                    'md5'           => $md5,
                    'hash'          => $hash,
                    'fromAccountId' => '855012345678@wing',
                    'toAccountId'   => '855987654321@aba',
                    'currency'      => $currency,
                    'amount'        => (float) $amount,
                    'description'   => 'Invoice payment',
                ]],
            ]),
        ]);
    }

    // -------------------------------------------------------------------------
    // 1. Token auto-renewal
    // -------------------------------------------------------------------------

    public function test_token_is_requested_after_cache_expires(): void
    {
        Http::fake([
            self::BASE_URL . '/v1/auth/login' => Http::sequence()
                ->push(['responseCode' => 0, 'data' => ['token' => 'token-first', 'expiresIn' => 7200]])
                ->push(['responseCode' => 0, 'data' => ['token' => 'token-renewed', 'expiresIn' => 7200]]),
        ]);

        $service = app(BakongTokenService::class);

        // First call → hits login, caches token.
        $t1 = $service->getToken();
        $this->assertEquals('token-first', $t1);

        // Simulate expiry by forgetting the cache.
        Cache::forget('bakong:api_token');

        // Second call → cache miss → hits login again, gets new token.
        $t2 = $service->getToken();
        $this->assertEquals('token-renewed', $t2);

        Http::assertSentCount(2);
    }

    public function test_force_renew_clears_cache_and_requests_fresh_token(): void
    {
        Http::fake([
            self::BASE_URL . '/v1/auth/login' => Http::sequence()
                ->push(['responseCode' => 0, 'data' => ['token' => 'first', 'expiresIn' => 7200]])
                ->push(['responseCode' => 0, 'data' => ['token' => 'forced', 'expiresIn' => 7200]]),
        ]);

        $service = app(BakongTokenService::class);
        $service->getToken(); // prime cache

        $renewed = $service->forceRenew();
        $this->assertEquals('forced', $renewed);

        // Cache now holds the forced token.
        $this->assertEquals('forced', $service->getToken());
        Http::assertSentCount(2); // login × 2, not 3
    }

    // -------------------------------------------------------------------------
    // 2. Pending intent confirmed by poll → payment applied exactly once
    // -------------------------------------------------------------------------

    public function test_pending_intent_confirmed_by_poll_applies_payment(): void
    {
        $invoice = $this->makeInvoice('INV-POLL-CONF', '60.00');
        $intent  = $this->makeIntent($invoice, ['amount' => '60.00']);
        $hash    = 'TXN-POLL-HASH-001';

        $this->fakePollResponse($intent->md5_hash, $hash, '60.00', 'USD');

        $poller = app(BakongPollingService::class);
        $poller->checkChunk(PaymentIntent::pending()->with('invoice')->get());

        // Payment recorded on the invoice.
        $this->assertDatabaseHas('payments', ['reference' => $hash, 'amount' => '60.00', 'method' => 'khqr']);
        $this->assertEquals('paid', $invoice->fresh()->status);

        // Intent marked paid with the Bakong hash.
        $intent->refresh();
        $this->assertEquals('paid', $intent->status);
        $this->assertEquals($hash, $intent->bakong_hash);

        // Exactly one bakong_callbacks row.
        $this->assertDatabaseCount('bakong_callbacks', 1);
        $this->assertDatabaseHas('bakong_callbacks', ['transaction_reference' => $hash]);
    }

    // -------------------------------------------------------------------------
    // 3. Re-polling confirmed transaction → no double payment
    // -------------------------------------------------------------------------

    public function test_re_polling_confirmed_transaction_does_not_double_pay(): void
    {
        $invoice = $this->makeInvoice('INV-POLL-IDEM', '40.00');
        $intent  = $this->makeIntent($invoice, ['amount' => '40.00']);
        $hash    = 'TXN-IDEM-POLL-001';

        $this->fakePollResponse($intent->md5_hash, $hash, '40.00');

        $poller = app(BakongPollingService::class);

        // First poll — applies payment.
        $poller->checkChunk(PaymentIntent::where('id', $intent->id)->with('invoice')->get());

        // Second poll — same response — must be idempotent.
        $poller->checkChunk(PaymentIntent::where('id', $intent->id)->with('invoice')->get());

        $this->assertDatabaseCount('bakong_callbacks', 1);
        $this->assertDatabaseCount('payments', 1);
        $this->assertEquals('paid', $invoice->fresh()->status);
    }

    // -------------------------------------------------------------------------
    // 4. Currency mismatch from poll → flagged, not applied
    // -------------------------------------------------------------------------

    public function test_currency_mismatch_from_poll_is_flagged_for_review(): void
    {
        $invoice = $this->makeInvoice('INV-POLL-KHR', '50.00');
        $intent  = $this->makeIntent($invoice);
        $hash    = 'TXN-KHR-POLL-001';

        $this->fakePollResponse($intent->md5_hash, $hash, '50.00', 'KHR'); // wrong currency

        $poller = app(BakongPollingService::class);
        $poller->checkChunk(PaymentIntent::pending()->with('invoice')->get());

        // Callback is recorded (verified) but payment NOT applied.
        $this->assertDatabaseHas('bakong_callbacks', [
            'transaction_reference' => $hash,
            'flag_reason'           => 'currency-mismatch',
        ]);
        $this->assertDatabaseEmpty('payments');

        // Intent is flagged.
        $intent->refresh();
        $this->assertEquals('flagged', $intent->status);
        $this->assertEquals('currency-mismatch', $intent->error_reason);
    }

    // -------------------------------------------------------------------------
    // 5. Expired intent → marked expired, not polled
    // -------------------------------------------------------------------------

    public function test_expired_intent_is_marked_expired_and_not_polled(): void
    {
        Http::fake([
            self::BASE_URL . '/v1/auth/login'                        => Http::response(['responseCode' => 0, 'data' => ['token' => 't', 'expiresIn' => 7200]]),
            self::BASE_URL . '/v1/merchant/check-transaction-by-md5' => Http::response(['responseCode' => 0, 'data' => []]),
        ]);

        $invoice = $this->makeInvoice('INV-POLL-EXP', '50.00');
        $this->makeIntent($invoice, ['expires_at' => now()->subMinutes(5)]); // already expired

        $khqr = app(KhqrService::class);
        $expired = $khqr->expireStaleIntents();
        $this->assertEquals(1, $expired);

        // The pending() scope now returns zero intents.
        $this->assertEquals(0, PaymentIntent::pending()->count());

        // Invoice is still unpaid.
        $this->assertEquals('unpaid', $invoice->fresh()->status);
        $this->assertDatabaseEmpty('payments');
        $this->assertDatabaseEmpty('bakong_callbacks');

        // Check endpoint was not called (no pending intents to poll).
        Http::assertNothingSent();
    }
}
