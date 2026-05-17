<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOtpVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->session()->get('otp_verified') !== true) {
            return redirect()
                ->route('otp.verify')
                ->withErrors([
                    'otp' => 'Please complete OTP verification first.',
                ]);
        }

        return $next($request);
    }
}
