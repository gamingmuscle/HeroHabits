<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Parent Portal') - Hero Habits</title>
    <link rel="icon" href="{{ asset('Assets/Icons & Logo/home.png') }}" type="image/png">

    {{-- Google Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    {{-- Consolidated CSS --}}
    <link rel="stylesheet" href="{{ asset('Assets/css/main.css') }}">
    <link rel="stylesheet" href="{{ asset('Assets/css/parent.css') }}">

    @stack('styles')
</head>
<body>
    {{-- Parent Header Component --}}
    <x-parent-header :page-title="$pageTitle ?? 'Portal'" />

    <div class="main-container">
        {{-- Parent Navigation Component --}}
        <x-parent-nav :current-page="$currentPage ?? ''" />

        {{-- Main Content Area --}}
        <main class="main-content">
            @yield('content')
        </main>
    </div>

    {{-- Chart.js Library (if needed) --}}
    @stack('scripts-before')

    {{-- Shared JavaScript Utilities --}}
    <script src="{{ asset('js/api-client.js') }}"></script>
    <script src="{{ asset('js/notifications.js') }}"></script>

    <script>
        // CSRF token for AJAX requests
        window.csrfToken = '{{ csrf_token() }}';

        // API base URL
        window.apiBaseUrl = '/api';
    </script>

    @stack('scripts')
</body>
</html>
