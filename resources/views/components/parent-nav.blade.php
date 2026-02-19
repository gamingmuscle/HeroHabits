@props(['currentPage' => ''])

<nav class="sidebar-nav">
  <div class="nav-section">
    <h3 class="nav-section-title">Main Menu</h3>
    <ul class="nav-menu">
      <li class="nav-item">
        <a href="{{ route('parent.dashboard') }}"
           class="nav-link nav-link-dashboard {{ $currentPage === 'dashboard' ? 'active' : '' }}">
          <img src="{{ asset('Assets/Icons & Logo/dashboard.png') }}" alt="Dashboard" class="nav-icon">
          <span class="nav-text">Dashboard</span>
        </a>
      </li>
      <li class="nav-item">
        <a href="{{ route('parent.approvals') }}"
           class="nav-link nav-link-approvals {{ $currentPage === 'approvals' ? 'active' : '' }}">
          <img src="{{ asset('Assets/Icons & Logo/approval.png') }}" alt="Approvals" class="nav-icon">
          <span class="nav-text">Approvals</span>
        </a>
      </li>
      <li class="nav-item">
        <a href="{{ route('parent.quests') }}"
           class="nav-link nav-link-quests {{ $currentPage === 'quests' ? 'active' : '' }}">
          <img src="{{ asset('Assets/Icons & Logo/quest.png') }}" alt="Quests" class="nav-icon">
          <span class="nav-text">Manage Quests</span>
        </a>
      </li>
      <li class="nav-item">
        <a href="{{ route('parent.treasures') }}"
           class="nav-link nav-link-treasures {{ $currentPage === 'treasures' ? 'active' : '' }}">
          <img src="{{ asset('Assets/Icons & Logo/treasure.png') }}" alt="Treasures" class="nav-icon">
          <span class="nav-text">Manage Treasures</span>
        </a>
      </li>
      <li class="nav-item">
        <a href="{{ route('parent.profiles') }}"
           class="nav-link nav-link-profiles {{ $currentPage === 'profiles' ? 'active' : '' }}">
          <img src="{{ asset('Assets/Icons & Logo/manage.png') }}" alt="Profiles" class="nav-icon">
          <span class="nav-text">Manage Profiles</span>
        </a>
      </li>
    </ul>
  </div>

  <div class="nav-section">
    <h3 class="nav-section-title">Settings</h3>
    <ul class="nav-menu">
      <li class="nav-item">
        <a href="{{ route('parent.account') }}"
           class="nav-link nav-link-account {{ $currentPage === 'account' ? 'active' : '' }}">
          <span class="nav-icon-emoji">⚙️</span>
          <span class="nav-text">Account</span>
        </a>
      </li>
    </ul>
  </div>
</nav>
