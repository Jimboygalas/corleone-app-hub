<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SmsController extends Controller
{
    public function send(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'min:7', 'max:25', 'regex:/^\+?[0-9\s().-]+$/'],
        ], [
            'phone.regex' => 'Please enter a valid phone number.',
        ]);

        $phone = trim($validated['phone']);
        $otp = $this->generateOtp();

        session([
            'otp.code' => $otp,
            'otp.target' => $phone,
            'otp.type' => 'phone',
        ]);

        $token = config('services.repohive_sms.token');

        if (! $token) {
            session()->forget('otp');

            return back()
                ->withInput()
                ->withErrors([
                    'phone' => 'OTP sending failed. Please check API configuration.',
                ]);
        }

        $response = Http::withToken($token)
            ->acceptJson()
            ->asJson()
            ->timeout(8)
            ->post($this->endpoint('repohive_sms', '/messages'), [
                'phone' => $phone,
                'to' => $phone,
                'message' => "Your Corleone App Hub OTP is {$otp}.",
                'body' => "Your Corleone App Hub OTP is {$otp}.",
            ]);

        if ($response->failed()) {
            session()->forget('otp');

            return back()
                ->withInput()
                ->withErrors([
                    'phone' => 'Repohive could not send the SMS OTP. Please check the API token, phone number, and Repohive account.',
                ]);
        }

        return redirect()
            ->route('otp.verify')
            ->with('success', 'SMS OTP sent. Please enter the 6-digit code.');
    }

    private function generateOtp(): string
    {
        return (string) random_int(100000, 999999);
    }

    private function endpoint(string $service, string $path): string
    {
        return Str::of(config("services.{$service}.base_url", 'https://repohive.com/api'))
            ->rtrim('/')
            ->append($path)
            ->toString();
    }
}
