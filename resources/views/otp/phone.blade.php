@extends('layouts.app')

@section('title', 'Corleone Phone OTP')
@section('bodyClass', 'auth-surface')

@section('content')
<div class="center-screen">
    <main class="card glass-card">
        <div class="brand">Corleone App Hub</div>
        <h1>Send OTP to Phone</h1>
        <p class="muted">Enter your phone number to receive a 6-digit verification code.</p>

        @if ($errors->any())
            <div class="warning">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('otp.phone.send') }}">
            @csrf
            <label for="phone">Phone Number</label>
            <input id="phone" name="phone" type="tel" placeholder="+63 900 000 0000" autocomplete="tel" value="{{ old('phone') }}">

            <button class="btn primary" type="submit">Send OTP</button>
        </form>

        <a class="link" href="{{ route('otp.email') }}">Use email instead</a>
        <a class="link subtle-link" href="{{ route('home') }}">Back to hub</a>
    </main>
</div>
@endsection
