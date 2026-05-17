@extends('layouts.app')

@section('title', 'Corleone Email OTP')
@section('bodyClass', 'auth-surface')

@section('content')
<div class="center-screen">
    <main class="card glass-card">
        <div class="brand">Corleone App Hub</div>
        <h1>Send OTP to Email</h1>
        <p class="muted">Enter your email address to receive a 6-digit verification code.</p>

        @if ($errors->any())
            <div class="warning">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('otp.email.send') }}">
            @csrf
            <label for="email">Email Address</label>
            <input id="email" name="email" type="email" placeholder="example@company.com" autocomplete="email" value="{{ old('email') }}">

            <button class="btn primary" type="submit">Send OTP</button>
        </form>

        <a class="link" href="{{ route('otp.phone') }}">Use phone instead</a>
        <a class="link subtle-link" href="{{ route('home') }}">Back to hub</a>
    </main>
</div>
@endsection
