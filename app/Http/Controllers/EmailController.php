<?php

namespace App\Http\Controllers;

use App\Mail\OtpMail;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class EmailController extends Controller
{
    public function send(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $email = trim($validated['email']);
        $otp = $this->generateOtp();
        $name = $this->completeName($email);
        $subject = $name;

        session([
            'otp.code' => $otp,
            'otp.target' => $email,
            'otp.type' => 'email',
        ]);

        $sentViaRepohive = $this->sendViaRepohive($email, $otp, $name, $subject);
        $sentViaGmail = false;

        if (! $sentViaRepohive) {
            $sentViaGmail = $this->sendViaGmailSmtp($email, $otp, $name, $subject);
        }

        if (! $sentViaRepohive && ! $sentViaGmail) {
            session()->forget('otp');

            return back()
                ->withInput()
                ->withErrors([
                    'email' => 'Email OTP could not be sent. Please try again or use Phone OTP.',
                ]);
        }

        return redirect()
            ->route('otp.verify')
            ->with('success', 'Email OTP sent. Please enter the 6-digit code.');
    }

    private function generateOtp(): string
    {
        return (string) random_int(100000, 999999);
    }

    private function sendViaRepohive(string $email, string $otp, string $name, string $subject): bool
    {
        $token = trim((string) config('services.repohive_email.token'));
        $from = trim((string) config('services.repohive_email.from', 'noreply@repohive.com'));
        $fromName = trim((string) config('services.repohive_email.from_name', 'RepoHive IT Solution'));

        if (! $token) {
            Log::error('Repohive Email API token is missing.', [
                'target' => $email,
            ]);

            return false;
        }

        $text = view('emails.otp-text', [
            'otp' => $otp,
            'name' => $name,
            'expiresInMinutes' => 10,
        ])->render();
        $html = view('emails.otp', [
            'otp' => $otp,
            'name' => $name,
            'expiresInMinutes' => 10,
        ])->render();

        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->asJson()
                ->timeout(10)
                ->post($this->endpoint('repohive_email', config('services.repohive_email.path', '/email/send')), [
                    'from' => $from,
                    'from_name' => $fromName,
                    'to' => $email,
                    'subject' => $subject,
                    'text' => $text,
                    'html' => $html,
                ]);
        } catch (Throwable $exception) {
            Log::error('Repohive Email API request failed.', [
                'target' => $email,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }

        if ($response->successful()) {
            return true;
        }

        Log::error('Repohive Email API returned an error.', [
            'target' => $email,
            'status' => $response->status(),
            'is_502' => $response->status() === 502,
            'response_preview' => Str::limit($response->body(), 500),
        ]);

        return false;
    }

    private function sendViaGmailSmtp(string $email, string $otp, string $name, string $subject): bool
    {
        try {
            Mail::to($email)->send(new OtpMail($otp, $subject, $name));

            return true;
        } catch (Throwable $exception) {
            Log::error('Email OTP Gmail SMTP fallback failed.', [
                'target' => $email,
                'mailer' => config('mail.default'),
                'host' => config('mail.mailers.smtp.host'),
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    private function completeName(string $email): string
    {
        $user = Auth::user();
        $name = trim((string) $user?->name);

        if ($name !== '') {
            return $name;
        }

        $name = trim((string) User::where('email', $email)->value('name'));

        if ($name !== '') {
            return $name;
        }

        $username = Str::before($email, '@');

        return Str::of($username)
            ->replace(['.', '_', '-'], ' ')
            ->squish()
            ->title()
            ->toString();
    }

    private function endpoint(string $service, string $path): string
    {
        return Str::of(config("services.{$service}.base_url", 'https://repohive.com/api'))
            ->rtrim('/')
            ->append($path)
            ->toString();
    }
}
