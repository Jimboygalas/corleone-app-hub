<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials)) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'email' => 'Invalid email or password.',
                ]);
        }

        $request->session()->regenerate();
        $request->session()->forget('otp_verified');

        return $this->startLoginOtp(Auth::user()->email);
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->forget('otp_verified');

        return $this->startLoginOtp($user->email);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    private function startLoginOtp(string $email): RedirectResponse
    {
        $user = Auth::user();
        $name = trim((string) $user?->name);

        session()->forget('otp');

        session([
            'otp.target' => $email,
            'otp.type' => 'login',
            'otp.user_email' => $email,
            'otp.user_name' => $name,
        ]);

        return redirect()
            ->route('otp.select')
            ->with('success', 'Login accepted. Please choose an OTP method to continue.');
    }
}
