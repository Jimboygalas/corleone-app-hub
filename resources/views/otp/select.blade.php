@extends('layouts.app')

@section('title', 'Corleone OTP Selection')
@section('bodyClass', 'auth-surface')

@section('content')
<div class="center-screen">
    <main class="card glass-card">
        <div class="brand">Corleone App Hub</div>
        <h1>Choose OTP Method</h1>
        <p class="muted">
            Select how you want to receive your 6-digit verification code.
        </p>

        @if (session('success'))
            <div class="success">{{ session('success') }}</div>
        @endif

        <div class="actions-grid">
            <a class="btn primary" href="{{ route('otp.phone') }}">Phone OTP</a>
            <a class="btn light" href="{{ route('otp.email') }}">Email OTP</a>
        </div>

        <a class="link subtle-link" href="{{ route('home') }}">Back to hub</a>
    </main>
</div>
@endsection
