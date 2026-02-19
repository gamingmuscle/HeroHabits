@props(['pageTitle' => 'Portal'])

@php
    $user = Auth::user();
    $displayname = $user->displayname ?? 'Parent';
    $timeRemaining = session('parent_last_activity')
        ? (config('session.lifetime') * 60) - (time() - session('parent_last_activity'))
        : 0;
    $minutesRemaining = floor($timeRemaining / 60);
@endphp

<header>
  <div class="header-container">
    <div class="header-title">
      <img src="{{ asset('Assets/Icons & Logo/logo.png') }}" alt="Hero Habits" class="logo">
      <span class="header-divider">—</span>
      <span class="page-name">{{ $pageTitle }}</span>
    </div>

    <div class="header-right">
      <div class="user-menu-container">
        <button class="user-menu-trigger" onclick="toggleUserMenu(event)">
          <div class="user-avatar">
            {{ strtoupper(substr($displayname, 0, 1)) }}
          </div>
          <span class="user-name">{{ $displayname }}</span>
          <span class="dropdown-arrow">▼</span>
        </button>

        <div class="user-dropdown" id="userDropdown">
          <div class="dropdown-header">
            <div class="dropdown-header-name">{{ $displayname }}</div>
            <div class="dropdown-header-role">Parent Account</div>
          </div>
          <div class="dropdown-menu">
            <a href="{{ route('parent.account') }}" class="dropdown-item">
              <span class="dropdown-item-icon">⚙️</span>
              <span>Account Settings</span>
            </a>
            <div class="dropdown-divider"></div>
            <form action="{{ route('parent.logout') }}" method="POST" style="margin: 0;">
              @csrf
              <button type="submit" class="dropdown-item" style="width: 100%; border: none; background: none; cursor: pointer; text-align: left;">
                <span class="dropdown-item-icon">🚪</span>
                <span>Logout</span>
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</header>

<script>
function toggleUserMenu(event) {
  event.stopPropagation();
  const trigger = event.currentTarget;
  const dropdown = document.getElementById('userDropdown');

  trigger.classList.toggle('active');
  dropdown.classList.toggle('show');
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
  const dropdown = document.getElementById('userDropdown');
  const trigger = document.querySelector('.user-menu-trigger');

  if (dropdown && dropdown.classList.contains('show')) {
    dropdown.classList.remove('show');
    if (trigger) trigger.classList.remove('active');
  }
});

// Prevent dropdown from closing when clicking inside it
document.getElementById('userDropdown')?.addEventListener('click', function(event) {
  event.stopPropagation();
});
</script>
