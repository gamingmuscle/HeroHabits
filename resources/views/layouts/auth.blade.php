<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Hero Habits') - Hero Habits</title>
    <link rel="icon" href="{{ asset('Assets/Icons & Logo/home.png') }}" type="image/png">

    {{-- Google Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    {{-- Consolidated CSS --}}
    <link rel="stylesheet" href="{{ asset('Assets/css/main.css') }}">

    @stack('styles')
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            @yield('content')
        </div>
    </div>

    @stack('scripts')
</body>
</html>
