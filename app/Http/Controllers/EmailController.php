<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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

        $token = config('services.repohive_email.token');

        if (! $token) {
            session()->forget('otp');

            return redirect()->route('otp.phone');
        }

        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->asJson()
                ->timeout(8)
                ->post($this->endpoint(), $this->payload($email, $otp));
        } catch (ConnectionException $exception) {
            session()->forget('otp');

            Log::error('Repohive Email OTP connection failed.', [
                'endpoint' => $this->endpoint(),
                'target' => $email,
                'error' => $exception->getMessage(),
            ]);

            return redirect()->route('otp.phone');
        }

        if (! $this->sentSuccessfully($response)) {
            session()->forget('otp');
            $details = $this->responseDetails($response);

            Log::error('Repohive Email OTP sending failed.', $this->failureContext([
                'endpoint' => $this->endpoint(),
                'target' => $email,
                'status' => $response->status(),
                'response' => $details,
            ]));

            return redirect()->route('otp.phone');
        }

        return redirect()
            ->route('otp.verify')
            ->with('success', 'Email OTP sent. Please enter the 6-digit code.');
    }

    private function generateOtp(): string
    {
        return (string) random_int(100000, 999999);
    }

    private function endpoint(): string
    {
        return $this->url(
            config('services.repohive_email.base_url', 'https://repohive.com/api'),
            '/email/send',
        );
    }

    private function payload(string $email, string $otp): array
    {
        return [
            'to' => $email,
            'subject' => 'Your Corleone App Hub OTP',
            'html' => '<p>Your code is <strong>'.$otp.'</strong>.</p>',
            'text' => 'Your code is '.$otp.'.',
        ];
    }

    private function sentSuccessfully(Response $response): bool
    {
        if ($response->failed()) {
            return false;
        }

        return ! Str::of($response->header('Content-Type', ''))->contains('text/html');
    }

    private function responseDetails(Response $response): string
    {
        $json = $response->json();

        if (is_array($json)) {
            return Str::limit(json_encode($json), 500);
        }

        return Str::limit(trim(strip_tags($response->body())), 500);
    }

    private function failureContext(array $context): array
    {
        if (config('app.debug')) {
            return $context;
        }

        unset($context['response']);

        return $context;
    }

    private function url(string $baseUrl, string $path): string
    {
        return Str::of($baseUrl)
            ->rtrim('/')
            ->append(Str::start($path, '/'))
            ->toString();
    }
}
