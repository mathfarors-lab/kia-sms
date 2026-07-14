<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * The login-time second-factor step. Reached only via
 * AuthenticatedSessionController::store() stashing session('2fa_user_id')
 * after primary credentials already passed — there is no other way to
 * arrive here with a "pending" identity, and nothing here trusts that
 * session value beyond looking up which user to challenge.
 */
class TwoFactorChallengeController extends Controller
{
    public function __construct(private TwoFactorService $twoFactor) {}

    public function show(Request $request)
    {
        if (!$request->session()->has('2fa_user_id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    public function verify(Request $request)
    {
        $userId = $request->session()->get('2fa_user_id');
        if (!$userId) {
            return redirect()->route('login');
        }

        $throttleKey = 'two-factor:' . $userId;
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            throw ValidationException::withMessages([
                'code' => __('auth.throttle', ['seconds' => RateLimiter::availableIn($throttleKey)]),
            ]);
        }

        $data = $request->validate(['code' => ['required', 'string']]);
        $user = User::findOrFail($userId);

        $verified = $this->twoFactor->verifyCode($user->two_factor_secret, $data['code']);

        if (!$verified) {
            $remaining = $this->twoFactor->consumeRecoveryCode($user->two_factor_recovery_codes ?? [], $data['code']);
            if ($remaining !== null) {
                $user->forceFill(['two_factor_recovery_codes' => $remaining])->save();
                $verified = true;
            }
        }

        if (!$verified) {
            RateLimiter::hit($throttleKey);
            throw ValidationException::withMessages(['code' => __('auth.two_factor_invalid')]);
        }

        RateLimiter::clear($throttleKey);

        $remember = $request->session()->pull('2fa_remember', false);
        $intended = $request->session()->pull('2fa_intended', route('dashboard'));
        $request->session()->forget('2fa_user_id');

        Auth::login($user, $remember);
        $request->session()->regenerate();

        return redirect()->to($intended);
    }
}
