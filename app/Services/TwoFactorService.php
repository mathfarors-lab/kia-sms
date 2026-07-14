<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * Thin wrapper around pragmarx/google2fa (the base TOTP library, not the
 * Laravel wrapper package — no auto-wired routes/views/middleware to
 * reason about, this app's own controllers own that integration
 * explicitly, same philosophy as the custom SmsChannel notification
 * channel rather than pulling in a full SMS package).
 */
class TwoFactorService
{
    private Google2FA $engine;

    public function __construct()
    {
        $this->engine = new Google2FA();
    }

    /** New, unconfirmed secret — caller persists it on the user (encrypted cast). */
    public function generateSecret(): string
    {
        return $this->engine->generateSecretKey();
    }

    /** QR code (SVG data URI) for the authenticator app to scan — same pattern as DocumentService::qrDataUri(). */
    public function qrCodeDataUri(User $user, string $secret): string
    {
        $otpauthUrl = $this->engine->getQRCodeUrl(
            config('app.name', 'KIA School System'),
            $user->email,
            $secret
        );

        $svg = QrCode::format('svg')->size(200)->margin(1)->generate($otpauthUrl);

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    public function verifyCode(string $secret, string $code): bool
    {
        // window(1) tolerates ~30s of clock drift either side, standard practice.
        return $this->engine->verifyKey($secret, $code, 1);
    }

    /** 10 one-time recovery codes, shown to the user exactly once at confirmation time. */
    public function generateRecoveryCodes(): array
    {
        return collect(range(1, 10))
            ->map(fn () => Str::upper(Str::random(4) . '-' . Str::random(4)))
            ->all();
    }

    /** Consumes a recovery code if valid — returns the remaining codes (caller persists), or null if invalid. */
    public function consumeRecoveryCode(array $codes, string $submitted): ?array
    {
        $submitted = Str::upper(trim($submitted));
        $index = array_search($submitted, $codes, true);

        if ($index === false) {
            return null;
        }

        unset($codes[$index]);
        return array_values($codes);
    }
}
