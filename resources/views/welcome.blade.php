<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hero Habits - Gamified Habit Tracking for Kids</title>
    <link rel="icon" href="{{ asset('Assets/Icons & Logo/home.png') }}" type="image/png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    {{-- Consolidated CSS --}}
    <link rel="stylesheet" href="{{ asset('Assets/css/main.css') }}">
</head>
<body class="welcome-page">
    <div class="splash-container">
        <div class="splash-header">
            <img src="{{ asset('Assets/Icons & Logo/logo.png') }}" alt="Hero Habits" class="logo">
            <h1 class="splash-title">Hero Habits</h1>
            <p class="splash-subtitle">Turn daily tasks into epic quests! 🎮✨</p>
        </div>

        <div class="login-cards">
            <div class="login-card">
                <div class="login-icon">👨‍👩‍👧‍👦</div>
                <h2 class="login-title">Parent Portal</h2>
                <p class="login-description">
                    Create quests, manage rewards, and track your children's progress
                </p>
                <a href="{{ route('parent.login') }}" class="login-btn">
                    Parent Login →
                </a>
            </div>

            <div class="login-card">
                <div class="login-icon">🧒</div>
                <h2 class="login-title">Child Portal</h2>
                <p class="login-description">
                    Complete quests, earn gold, and unlock awesome treasures!
                </p>
                <a href="{{ route('child.login') }}" class="login-btn child">
                    Child Login →
                </a>
            </div>
        </div>

        <div class="features">
            <h3 class="features-title">✨ Features</h3>
            <div class="features-grid">
                <div class="feature-item">
                    <div class="feature-icon">🎯</div>
                    <div class="feature-text">Create Custom Quests</div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">⭐</div>
                    <div class="feature-text">Earn Gold Rewards</div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">🏆</div>
                    <div class="feature-text">Unlock Treasures</div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">📊</div>
                    <div class="feature-text">Track Progress</div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
