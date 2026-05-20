<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

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

        $name = $this->completeName($request);

        $otpMessage = "Your verification code is {$otp}. Do not share this with anyone.";
        $notificationMessages = [
            'reminder' => "Reminder for {$name}: Please complete your Corleone App Hub verification today. This is automated. Please do not reply.",
            'welcome' => "Welcome {$name} to Corleone App Hub. This is an automated SMS notification. Please do not reply.",
        ];

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

        try {
            $response = $this->sendSms($token, $phone, $otpMessage);

            if ($response->failed()) {
                session()->forget('otp');

                return back()
                    ->withInput()
                    ->withErrors([
                        'phone' => 'Repohive could not send the OTP SMS. Please check the API token, phone number, and Repohive account.',
                    ]);
            }
        } catch (Throwable) {
            session()->forget('otp');

            return back()
                ->withInput()
                ->withErrors([
                    'phone' => 'Repohive could not send the OTP SMS. Please check the API token, phone number, and Repohive account.',
                ]);
        }

        foreach ($notificationMessages as $type => $message) {
            try {
                $response = $this->sendSms($token, $phone, $message);

                if ($response->failed()) {
                    Log::warning('Repohive supplemental SMS failed.', [
                        'type' => $type,
                        'target' => $phone,
                        'status' => $response->status(),
                    ]);
                }
            } catch (Throwable $exception) {
                Log::warning('Repohive supplemental SMS request failed.', [
                    'type' => $type,
                    'target' => $phone,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return redirect()
            ->route('otp.verify')
            ->with('success', 'SMS OTP sent. Please enter the 6-digit code.');
    }

    private function generateOtp(): string
    {
        return (string) random_int(100000, 999999);
    }

    private function completeName(Request $request): string
    {
        // 1. Logged-in user
        $name = trim((string) Auth::user()?->name);

        if ($name !== '') {
            return $name;
        }

        // 2. Session stored user name
        $name = trim((string) session('otp.user_name'));

        if ($name !== '') {
            return $name;
        }

        // 3. Session email lookup
        $name = $this->nameForEmail(session('otp.user_email'))
            ?? $this->nameForEmail(session('otp.target'));

        if ($name !== null) {
            return $name;
        }

        // 4. Submitted email lookup
        $submittedEmail = $request->string('email')->toString();

        $name = $this->nameForEmail($submittedEmail);

        if ($name !== null) {
            return $name;
        }

        // 5. Final fallback
        return 'User';
    }

    private function nameForEmail(mixed $email): ?string
    {
        if (! is_string($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        $name = trim((string) User::where('email', $email)->value('name'));

        return $name !== '' ? $name : null;
    }

    private function sendSms(string $token, string $phone, string $message): \Illuminate\Http\Client\Response
    {
        return Http::withToken($token)
            ->acceptJson()
            ->asJson()
            ->timeout(8)
            ->post($this->endpoint('repohive_sms', '/messages'), [
                'phone' => $phone,
                'to' => $phone,
                'message' => $message,
                'body' => $message,
            ]);
    }

    private function endpoint(string $service, string $path): string
    {
        return Str::of(config("services.{$service}.base_url", 'https://repohive.com/api'))
            ->rtrim('/')
            ->append($path)
            ->toString();
    }
}
