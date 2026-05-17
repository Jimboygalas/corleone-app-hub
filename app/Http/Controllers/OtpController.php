<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class OtpController extends Controller
{
    public function verify(Request $request): RedirectResponse
    {
        $storedOtp = session('otp.code');

        if (! $storedOtp) {
            return back()->withErrors([
                'otp' => 'Please request an OTP first',
            ]);
        }

        $otp = $this->otpFromRequest($request);

        if (strlen($otp) !== 6) {
            return back()->withErrors([
                'otp' => 'Please enter the complete 6-digit OTP.',
            ]);
        }

        if (! hash_equals($storedOtp, $otp)) {
            return back()->withErrors([
                'otp' => 'Wrong OTP. Please try again.',
            ]);
        }

        if (! Auth::check()) {
            Auth::login($this->demoUser());
        }

        session(['otp_verified' => true]);
        session()->forget('otp');

        return redirect()->route('mailbox');
    }

    private function otpFromRequest(Request $request): string
    {
        $digits = $request->input('otp_digits', []);

        if (is_array($digits) && $digits !== []) {
            return collect($digits)
                ->map(fn ($digit) => Str::of((string) $digit)->replaceMatches('/\D/', '')->substr(0, 1)->toString())
                ->implode('');
        }

        return Str::of($request->string('otp')->toString())
            ->replaceMatches('/\D/', '')
            ->substr(0, 6)
            ->toString();
    }

    private function demoUser(): User
    {
        return User::firstOrCreate(
            ['email' => 'demo@corleone.test'],
            [
                'name' => 'Demo User',
                'password' => Str::random(32),
            ],
        );
    }
}
