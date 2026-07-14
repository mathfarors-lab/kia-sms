<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\TwoFactorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Self-service 2FA enrollment — opt-in for every user, not enforced.
 * Recommended (not required) for owner/admin/accountant/principal;
 * see User::shouldBeStronglyEncouragedToEnable2fa() for the UI banner.
 */
class TwoFactorSettingsController extends Controller
{
    public function __construct(private TwoFactorService $twoFactor) {}

    public function show(Request $request)
    {
        return view('auth.two-factor-settings', ['user' => $request->user()]);
    }

    /** Generates (or regenerates) an unconfirmed secret and shows the QR code. Not "enabled" until confirm() succeeds. */
    public function enable(Request $request)
    {
        $user = $request->user();
        $secret = $this->twoFactor->generateSecret();

        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => null,
            'two_factor_recovery_codes' => null,
        ])->save();

        $qrCode = $this->twoFactor->qrCodeDataUri($user, $secret);

        return view('auth.two-factor-enable', ['qrCode' => $qrCode, 'secret' => $secret]);
    }

    public function confirm(Request $request)
    {
        $data = $request->validate(['code' => ['required', 'string']]);
        $user = $request->user();

        abort_if(!$user->two_factor_secret, 400);

        if (!$this->twoFactor->verifyCode($user->two_factor_secret, $data['code'])) {
            return back()->withErrors(['code' => __('auth.two_factor_invalid')]);
        }

        $recoveryCodes = $this->twoFactor->generateRecoveryCodes();

        $user->forceFill([
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => $recoveryCodes,
        ])->save();

        // Shown exactly once — not persisted anywhere in plain text, and
        // this session key is read-once by the view below.
        return redirect()->route('two-factor.recovery-codes.show')
            ->with('fresh_recovery_codes', $recoveryCodes);
    }

    public function showRecoveryCodes(Request $request)
    {
        $codes = $request->session()->get('fresh_recovery_codes');
        abort_unless($codes, 404);

        return view('auth.two-factor-recovery-codes', ['codes' => $codes]);
    }

    public function disable(Request $request)
    {
        $request->validate(['password' => ['required', 'current_password']]);

        $request->user()->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        return redirect()->route('two-factor.settings')->with('success', __('auth.two_factor_disabled'));
    }
}
