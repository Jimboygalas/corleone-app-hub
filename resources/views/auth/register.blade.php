@extends('layouts.app')

@section('title', 'Corleone App Hub Register')
@section('bodyClass', 'auth-surface')

@section('content')
<div class="center-screen">
    <main class="card glass-card">
        <div class="brand">Corleone App Hub</div>
        <h1>Create account</h1>
        <p class="muted">Register a prototype account and continue to OTP verification.</p>

        @if ($errors->any())
            <div class="warning">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('register.submit') }}">
            @csrf
            <label for="registerName">Full Name</label>
            <input id="registerName" name="name" type="text" placeholder="Student User" autocomplete="name" value="{{ old('name') }}">

            <label for="registerEmail">Email Address</label>
            <input id="registerEmail" name="email" type="email" placeholder="student@example.com" autocomplete="email" value="{{ old('email') }}">

            <label for="registerPassword">Password</label>
            <input id="registerPassword" name="password" type="password" placeholder="Create password" autocomplete="new-password">

            <button class="btn primary" type="submit">Create account</button>
        </form>

        <a class="link" href="{{ route('login') }}">Already have an account</a>
        <a class="link subtle-link" href="{{ route('home') }}">Back to hub</a>
    </main>
</div>
@endsection
