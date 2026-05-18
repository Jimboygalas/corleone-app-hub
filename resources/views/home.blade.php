@extends('layouts.app')

@section('title', 'Corleone App Hub')
@section('bodyClass', 'auth-surface')

@section('content')
<div class="hero-shell">
    <a class="landing-brand" href="{{ route('home') }}" aria-label="Corleone App Hub home">
        <span class="logo-mark">@include('partials.icon', ['name' => 'logo'])</span>
        <span>
            <strong>Corleone</strong>
            <small>App Hub</small>
        </span>
    </a>

    <span class="pill hero-pill">HCI Final Prototype</span>

    <section class="hero-text">
        <h1>Your secure access, all in one place.</h1>
        <p>OTP login, validation, mailbox, and AI assistance in one clean SaaS-style workspace.</p>
    </section>

    <main class="card hub-card glass-card access-card">
        <div class="access-heading">
            <span class="access-icon">@include('partials.icon', ['name' => 'lock'])</span>
            <div>
                <h1>Choose a secure access flow</h1>
                <p class="muted">
                    Continue with OTP verification, open the mailbox dashboard, or ask the AI assistant.
                </p>
            </div>
        </div>

        <div class="feature-grid access-grid">
            <a class="feature-card primary-feature" href="{{ route('otp.phone') }}">
                <span class="card-icon icon-phone">@include('partials.icon', ['name' => 'phone-check'])</span>
                <em></em>
                <span>Phone OTP</span>
                <strong>Send verification code</strong>
                <small>We'll send a code to your mobile number.</small>
            </a>
            <a class="feature-card" href="{{ route('otp.email') }}">
                <span class="card-icon icon-mail">@include('partials.icon', ['name' => 'mail-check'])</span>
                <em></em>
                <span>Email OTP</span>
                <strong>Verify by email</strong>
                <small>Send a code to your email</small>
            </a>
            <a class="feature-card" href="{{ route('otp.verify') }}">
                <span class="card-icon icon-shield">@include('partials.icon', ['name' => 'shield-check'])</span>
                <em></em>
                <span>Validation</span>
                <strong>Enter OTP code</strong>
                <small>Validate your code here</small>
            </a>
            <a class="feature-card" href="{{ route('mailbox') }}">
                <span class="card-icon icon-inbox">@include('partials.icon', ['name' => 'inbox'])</span>
                <em></em>
                <span>Mailbox</span>
                <strong>Open dashboard</strong>
                <small>Access your mailbox and messages</small>
            </a>
            <a class="feature-card" href="{{ route('ai-chatbot') }}">
                <span class="card-icon icon-chat">@include('partials.icon', ['name' => 'chat'])</span>
                <em></em>
                <span>AI Chatbot</span>
                <strong>Get quick help</strong>
                <small>Ask our AI assistant anytime</small>
            </a>
        </div>

        <div class="divider"><span>OR</span></div>

        <div class="auth-action-row" aria-label="Account access">
            <a class="btn light" href="{{ route('login') }}">Sign In</a>
            <a class="btn primary" href="{{ route('register') }}">Create Account</a>
        </div>

        <button class="btn google" type="button" onclick="loginWithGoogle()">
            <img src="{{ asset('assets/Google_Favicon_2025.svg.webp') }}" alt="" height="26" width="26">
            Login with Google Account
        </button>

        <p class="note">
            Prototype screens keep the original front-end functions and localStorage flow.
        </p>
    </main>
</div>
@endsection
