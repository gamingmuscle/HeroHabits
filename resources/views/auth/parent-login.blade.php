@extends('layouts.auth')

@section('title', 'Parent Login')

@section('content')
<div class="auth-header">
    <img src="{{ asset('Assets/Icons & Logo/logo.png') }}" alt="Hero Habits" class="auth-logo">
    <h1 class="auth-title">Parent Login</h1>
    <p class="auth-subtitle">Welcome back! Manage your children's quests.</p>
</div>

@if (session('success'))
    <div class="success-message">
        {{ session('success') }}
    </div>
@endif

@if (session('error'))
    <div class="error-message" style="margin-bottom: 20px; padding: 12px; background: #fee2e2; border-radius: 10px;">
        ⚠️ {{ session('error') }}
    </div>
@endif

<form action="{{ route('parent.login') }}" method="POST">
    @csrf

    <div class="form-group">
        <label for="username">Username</label>
        <input
            type="text"
            id="username"
            name="username"
            value="{{ old('username') }}"
            class="@error('username') error @enderror"
            required
            autofocus>
        @error('username')
            <div class="error-message">⚠️ {{ $message }}</div>
        @enderror
    </div>

    <div class="form-group">
        <label for="password">Password</label>
        <input
            type="password"
            id="password"
            name="password"
            class="@error('password') error @enderror"
            required>
        @error('password')
            <div class="error-message">⚠️ {{ $message }}</div>
        @enderror
    </div>

    <button type="submit" class="btn btn-primary">
        🔐 Login to Parent Portal
    </button>
</form>

<div class="auth-footer">
    Don't have an account?
    <a href="{{ route('parent.register') }}">Create one here</a>
</div>

<div class="divider">OR</div>

<a href="{{ route('child.login') }}" class="btn btn-secondary">
    👧 Child Login
</a>
@endsection
