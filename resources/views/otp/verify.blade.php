@extends('layouts.app')

@section('title', 'Corleone OTP Validation')
@section('bodyClass', 'auth-surface')

@section('content')
<div class="center-screen">
    <main class="card glass-card">
        <div class="brand">Corleone App Hub</div>
        <h1>Validate OTP</h1>
        <p class="muted">
            Code sent to: <strong id="otpTarget">{{ session('otp.target', 'your account') }}</strong>
        </p>

        @if (session('success'))
            <div class="success">{{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ route('otp.verify.submit') }}" data-server-otp="true">
            @csrf
            <div class="otp-box" aria-label="One-time password">
                <input maxlength="1" name="otp_digits[]" class="otp" inputmode="numeric" aria-label="OTP digit 1">
                <input maxlength="1" name="otp_digits[]" class="otp" inputmode="numeric" aria-label="OTP digit 2">
                <input maxlength="1" name="otp_digits[]" class="otp" inputmode="numeric" aria-label="OTP digit 3">
                <input maxlength="1" name="otp_digits[]" class="otp" inputmode="numeric" aria-label="OTP digit 4">
                <input maxlength="1" name="otp_digits[]" class="otp" inputmode="numeric" aria-label="OTP digit 5">
                <input maxlength="1" name="otp_digits[]" class="otp" inputmode="numeric" aria-label="OTP digit 6">
            </div>

            <button class="btn primary" type="submit">Verify OTP</button>
        </form>

        <p id="message" class="muted center">
            {{ $errors->first('otp') ?: (session('otp.code') ? '' : 'Please request an OTP first') }}
        </p>
        <a class="link subtle-link" href="{{ route('home') }}">Back to hub</a>
    </main>
</div>
@endsection
