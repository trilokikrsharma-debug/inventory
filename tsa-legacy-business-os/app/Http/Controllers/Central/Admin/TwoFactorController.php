<?php

namespace App\Http\Controllers\Central\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use PragmaRX\Google2FALaravel\Facade as Google2FA;

class TwoFactorController extends Controller
{
    public function setup(Request $request): View
    {
        $user = $request->user();

        if (! $user || ! $user->is_platform_admin) {
            abort(403);
        }

        if (! $user->two_factor_secret) {
            $user->update([
                'two_factor_secret' => Crypt::encryptString(Google2FA::generateSecretKey()),
            ]);
        }

        $secret = Crypt::decryptString($user->two_factor_secret);
        $qrCode = Google2FA::getQRCodeInline(
            config('app.name', 'TSA Legacy Business OS'),
            $user->email,
            $secret
        );

        return view('central.admin.two-factor.setup', [
            'user' => $user,
            'secret' => $secret,
            'qrCode' => $qrCode,
            'alreadyConfirmed' => ! is_null($user->two_factor_confirmed_at),
        ]);
    }

    public function confirm(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'otp' => ['required', 'digits:6'],
        ]);

        $user = $request->user();

        if (! $user || ! $user->is_platform_admin || ! $user->two_factor_secret) {
            throw ValidationException::withMessages([
                'otp' => 'Two-factor setup is not ready.',
            ]);
        }

        $secret = Crypt::decryptString($user->two_factor_secret);
        $isValid = Google2FA::verifyKey($secret, $validated['otp']);

        if (! $isValid) {
            throw ValidationException::withMessages([
                'otp' => 'Invalid OTP code.',
            ]);
        }

        $user->update([
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => json_encode($this->makeRecoveryCodes(), JSON_THROW_ON_ERROR),
        ]);

        $request->session()->put('auth.two_factor_verified', true);

        return redirect()->route('admin.tenants.index')
            ->with('status', 'Two-factor authentication enabled successfully.');
    }

    public function challenge(): View
    {
        return view('central.admin.two-factor.challenge');
    }

    public function verifyChallenge(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'otp' => ['required', 'digits:6'],
        ]);

        $user = $request->user();

        if (! $user || ! $user->is_platform_admin || ! $user->two_factor_secret || ! $user->two_factor_confirmed_at) {
            throw ValidationException::withMessages([
                'otp' => 'Two-factor challenge is not available.',
            ]);
        }

        $secret = Crypt::decryptString($user->two_factor_secret);
        $isValid = Google2FA::verifyKey($secret, $validated['otp']);

        if (! $isValid) {
            throw ValidationException::withMessages([
                'otp' => 'Invalid OTP code.',
            ]);
        }

        $request->session()->put('auth.two_factor_verified', true);

        return redirect()->intended(route('admin.tenants.index'));
    }

    public function disable(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string'],
        ]);

        $user = $request->user();

        if (! $user || ! Hash::check($validated['password'], (string) $user->password)) {
            throw ValidationException::withMessages([
                'password' => 'Password verification failed.',
            ]);
        }

        $user->update([
            'two_factor_secret' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_recovery_codes' => null,
        ]);

        $request->session()->forget('auth.two_factor_verified');

        return back()->with('status', 'Two-factor authentication disabled.');
    }

    /**
     * @return list<string>
     */
    private function makeRecoveryCodes(): array
    {
        $codes = [];

        for ($i = 0; $i < 8; $i++) {
            $codes[] = strtoupper(bin2hex(random_bytes(4)));
        }

        return $codes;
    }
}
