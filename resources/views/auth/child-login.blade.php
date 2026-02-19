@extends('layouts.auth')

@section('title', 'Child Login')



@section('content')
<div class="auth-header">
    <img src="{{ asset('Assets/Icons & Logo/logo.png') }}" alt="Hero Habits" class="auth-logo">
    <h1 class="auth-title">Child Login</h1>
    <p class="auth-subtitle">Choose your profile and enter your PIN</p>
</div>

@if (session('error'))
    <div class="error-message" style="margin-bottom: 20px; padding: 12px; background: #fee2e2; border-radius: 10px;">
        ⚠️ {{ session('error') }}
    </div>
@endif

<div class="helper-text" id="helperText">
    👇 Select your profile
</div>

<div class="child-selector" id="childSelector">
    <!-- Children will be loaded via JavaScript -->
    <div style="grid-column: 1/-1; text-align: center; padding: 40px; color: #999;">
        Loading profiles...
    </div>
</div>

<div class="pin-container" id="pinContainer">
    <div class="pin-display">
        <div class="pin-dot" id="dot1"></div>
        <div class="pin-dot" id="dot2"></div>
        <div class="pin-dot" id="dot3"></div>
        <div class="pin-dot" id="dot4"></div>
    </div>

    <div class="pin-keypad">
        <button type="button" class="pin-key" onclick="addDigit('1')">1</button>
        <button type="button" class="pin-key" onclick="addDigit('2')">2</button>
        <button type="button" class="pin-key" onclick="addDigit('3')">3</button>
        <button type="button" class="pin-key" onclick="addDigit('4')">4</button>
        <button type="button" class="pin-key" onclick="addDigit('5')">5</button>
        <button type="button" class="pin-key" onclick="addDigit('6')">6</button>
        <button type="button" class="pin-key" onclick="addDigit('7')">7</button>
        <button type="button" class="pin-key" onclick="addDigit('8')">8</button>
        <button type="button" class="pin-key" onclick="addDigit('9')">9</button>
        <button type="button" class="pin-key empty"></button>
        <button type="button" class="pin-key" onclick="addDigit('0')">0</button>
        <button type="button" class="pin-key clear" onclick="clearPin()">Clear</button>
    </div>
</div>

<div class="auth-footer">
    <a href="{{ route('parent.login') }}">👨 Parent Login</a>
</div>

<form id="loginForm" action="{{ route('child.login') }}" method="POST" style="display: none;">
    @csrf
    <input type="hidden" name="child_id" id="childId">
    <input type="hidden" name="pin" id="pin">
</form>
@endsection

@push('scripts')
<script>
let selectedChildId = null;
let pinValue = '';
let children = [];

// Load children from API
document.addEventListener('DOMContentLoaded', loadChildren);

async function loadChildren() {
    try {
        const response = await fetch('{{ route('child.all') }}');
        const data = await response.json();

        if (data.success && data.children) {
            children = data.children;
            renderChildren();
        } else {
            showError('No child profiles found. Please ask your parent to create a profile for you.');
        }
    } catch (error) {
        console.error('Failed to load children', error);
        showError('Failed to load profiles. Please refresh the page.');
    }
}

function renderChildren() {
    const container = document.getElementById('childSelector');

    if (children.length === 0) {
        container.innerHTML = `
            <div style="grid-column: 1/-1; text-align: center; padding: 40px;">
                <p style="color: #666; margin-bottom: 15px;">No child profiles yet!</p>
                <a href="{{ route('parent.login') }}" style="color: var(--purple); font-weight: 600;">Ask your parent to create one</a>
            </div>
        `;
        return;
    }

    container.innerHTML = children.map(child => `
        <div class="child-option" onclick="selectChild(${child.id})">
            <img src="{{asset('/Assets/Profile/')}}/${child.avatar_image}" alt="${child.name}" class="child-avatar">
            <div class="child-name">${escapeHtml(child.name)}</div>
        </div>
    `).join('');
}

function selectChild(childId) {
    selectedChildId = childId;

    // Update UI
    document.querySelectorAll('.child-option').forEach(opt => {
        opt.classList.remove('selected');
    });
    event.currentTarget.classList.add('selected');

    // Enable PIN entry
    document.getElementById('pinContainer').classList.add('active');
    document.getElementById('helperText').textContent = '🔢 Enter your 4-digit PIN';

    // Clear any previous PIN
    clearPin();
}

function addDigit(digit) {
    if (!selectedChildId) {
        return;
    }

    if (pinValue.length < 4) {
        pinValue += digit;
        updatePinDisplay();

        // Auto-submit when 4 digits entered
        if (pinValue.length === 4) {
            setTimeout(submitLogin, 300);
        }
    }
}

function clearPin() {
    pinValue = '';
    updatePinDisplay();
}

function updatePinDisplay() {
    for (let i = 1; i <= 4; i++) {
        const dot = document.getElementById(`dot${i}`);
        if (i <= pinValue.length) {
            dot.classList.add('filled');
        } else {
            dot.classList.remove('filled');
        }
    }
}

function submitLogin() {
    document.getElementById('childId').value = selectedChildId;
    document.getElementById('pin').value = pinValue;
    document.getElementById('loginForm').submit();
}

function showError(message) {
    document.getElementById('childSelector').innerHTML = `
        <div style="grid-column: 1/-1; text-align: center; padding: 40px;">
            <div style="font-size: 3rem; margin-bottom: 15px;">⚠️</div>
            <p style="color: #666;">${message}</p>
        </div>
    `;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Handle wrong PIN (shake animation)
@if (session('error'))
    document.addEventListener('DOMContentLoaded', () => {
        const pinDisplay = document.querySelector('.pin-display');
        if (pinDisplay) {
            pinDisplay.classList.add('error-shake');
            setTimeout(() => {
                pinDisplay.classList.remove('error-shake');
            }, 500);
        }
    });
@endif
</script>
@endpush
