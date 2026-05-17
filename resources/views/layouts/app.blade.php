<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Corleone App Hub')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/styles.css') }}">
</head>
<body class="@yield('bodyClass')">
    @yield('content')

    <script>
        window.prototypeRoutes = {
            home: @json(route('home')),
            login: @json(route('login')),
            register: @json(route('register')),
            otpPhone: @json(route('otp.phone')),
            otpEmail: @json(route('otp.email')),
            otpVerify: @json(route('otp.verify')),
            mailbox: @json(route('mailbox')),
            chatbot: @json(route('ai-chatbot')),
        };
    </script>
    <script src="{{ asset('assets/app.js') }}"></script>
    @stack('scripts')
</body>
</html>
