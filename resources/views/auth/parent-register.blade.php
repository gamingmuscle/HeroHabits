@extends('layouts.auth')

@section('title', 'Parent Registration')

@section('content')
<div class="auth-header">
    <img src="{{ asset('Assets/Icons & Logo/logo.png') }}" alt="Hero Habits" class="auth-logo">
    <h1 class="auth-title">Create Account</h1>
    <p class="auth-subtitle">Start your family's quest adventure!</p>
</div>

@if ($errors->any())
    <div class="error-message" style="margin-bottom: 20px; padding: 12px; background: #fee2e2; border-radius: 10px;">
        <strong>⚠️ Please fix the following errors:</strong>
        <ul style="margin: 8px 0 0 20px;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ route('parent.register') }}" method="POST">
    @csrf

    <div class="form-group">
        <label for="displayname">Display Name</label>
        <input
            type="text"
            id="displayname"
            name="displayname"
            value="{{ old('displayname') }}"
            placeholder="Mom, Dad, etc."
            class="@error('displayname') error @enderror"
            required
            autofocus>
        @error('displayname')
            <div class="error-message">⚠️ {{ $message }}</div>
        @enderror
    </div>

    @if(config('herohabits.registration.invitation_only'))
        <div class="form-group">
            <label for="invitation_code">Invitation Code</label>
            <input
                type="text"
                id="invitation_code"
                name="invitation_code"
                value="{{ old('invitation_code') }}"
                placeholder="Enter your invitation code"
                class="@error('invitation_code') error @enderror"
                style="text-transform: uppercase;"
                required>
            @error('invitation_code')
                <div class="error-message">⚠️ {{ $message }}</div>
            @enderror
            <small style="color: #666; font-size: 0.85rem; display: block; margin-top: 5px;">
                Registration requires an invitation code
            </small>
        </div>
    @endif

    <div class="form-group">
        <label for="username">Username</label>
        <input
            type="text"
            id="username"
            name="username"
            value="{{ old('username') }}"
            placeholder="Choose a unique username"
            class="@error('username') error @enderror"
            required>
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
            placeholder="At least 6 characters"
            class="@error('password') error @enderror"
            required>
        @error('password')
            <div class="error-message">⚠️ {{ $message }}</div>
        @enderror
    </div>

    <div class="form-group">
        <label for="password_confirmation">Confirm Password</label>
        <input
            type="password"
            id="password_confirmation"
            name="password_confirmation"
            placeholder="Re-enter your password"
            class="@error('password_confirmation') error @enderror"
            required>
        @error('password_confirmation')
            <div class="error-message">⚠️ {{ $message }}</div>
        @enderror
    </div>

    <button type="submit" class="btn btn-primary">
        ✨ Create Parent Account
    </button>
</form>

<div class="auth-footer">
    Already have an account?
    <a href="{{ route('parent.login') }}">Login here</a>
</div>
@endsection

@push('scripts')
<script>
// Auto-uppercase invitation code
@if(config('herohabits.registration.invitation_only'))
const invitationInput = document.getElementById('invitation_code');
if (invitationInput) {
    invitationInput.addEventListener('input', function(e) {
        this.value = this.value.toUpperCase();
    });
}
@endif

// Client-side validation
document.querySelector('form').addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    const passwordConfirm = document.getElementById('password_confirmation').value;

    if (password !== passwordConfirm) {
        e.preventDefault();
        alert('Passwords do not match!');
        return false;
    }

    if (password.length < 6) {
        e.preventDefault();
        alert('Password must be at least 6 characters long!');
        return false;
    }
});
</script>
@endpush
