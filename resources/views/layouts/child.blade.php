<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Child Portal') - Hero Habits</title>
    <link rel="icon" href="{{ asset('Assets/Icons & Logo/home.png') }}" type="image/png">

    {{-- Google Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    {{-- Consolidated CSS --}}
    <link rel="stylesheet" href="{{ asset('Assets/css/main.css') }}">
    <link rel="stylesheet" href="{{ asset('Assets/css/child.css') }}">

    @stack('styles')
</head>
<body>
    {{-- Child Header Component --}}
    <x-child-header :page-title="$pageTitle ?? 'Portal'" :child="$child" />

    <div class="main-container">
        {{-- Child Navigation Component --}}
        <x-child-nav :current-page="$currentPage ?? ''" />

        {{-- Main Content Area --}}
        <main class="main-content">
            @yield('content')
        </main>
    </div>

    {{-- Shared JavaScript Utilities --}}
    <script src="{{ asset('js/api-client.js') }}"></script>
    <script src="{{ asset('js/notifications.js') }}"></script>

    <script>
        // CSRF token for AJAX requests
        window.csrfToken = '{{ csrf_token() }}';

        // API base URL
        window.apiBaseUrl = '/api';

        // Check for level-up celebration
        document.addEventListener('DOMContentLoaded', function() {
            @if(session('child_level_up'))
                const levelUpData = @json(session('child_level_up'));

                // Small delay to ensure page is fully loaded
                setTimeout(() => {
                    notify.levelUp(
                        levelUpData.child_name,
                        levelUpData.new_level,
                        levelUpData.levels_gained
                    );
                }, 500);
            @endif
        });
    </script>

    @stack('scripts')
</body>
</html>
