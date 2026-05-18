<?php

namespace App\Http\Controllers;

use App\Mail\OtpMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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

        session([
            'otp.code' => $otp,
            'otp.target' => $email,
            'otp.type' => 'email',
        ]);

        try {
            Mail::to($email)->send(new OtpMail($otp));
        } catch (Throwable $exception) {
            session()->forget('otp');

            Log::error('Email OTP SMTP sending failed.', [
                'target' => $email,
                'mailer' => config('mail.default'),
                'host' => config('mail.mailers.smtp.host'),
                'error' => $exception->getMessage(),
            ]);

            return back()
                ->withInput()
                ->withErrors([
                    'email' => 'Email OTP could not be sent. Please check Gmail SMTP configuration.',
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
}
