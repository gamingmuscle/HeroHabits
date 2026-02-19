@props(['currentPage' => ''])

<nav class="sidebar-nav">
  <div class="nav-section">
    <h3 class="nav-section-title">Menu</h3>
    <ul class="nav-menu">
      <li class="nav-item">
        <a href="{{ route('child.character') }}"
           class="nav-link nav-link-character {{ $currentPage === 'character' ? 'active' : '' }}">
          <img src="{{ asset('Assets/Icons & Logo/character_sheet.png') }}" alt="My Character" class="nav-icon">
          <span class="nav-text">My Hero</span>
        </a>
      </li>
      <li class="nav-item">
        <a href="{{ route('child.quests') }}"
           class="nav-link nav-link-quests {{ $currentPage === 'quests' ? 'active' : '' }}">
          <img src="{{ asset('Assets/Icons & Logo/quest.png') }}" alt="My Quests" class="nav-icon">
          <span class="nav-text">Quests</span>
        </a>
      </li>
      <li class="nav-item">
        <a href=""
           class="nav-link nav-link-calendar {{ $currentPage === 'journeys' ? 'active' : '' }}">
          <img src="{{ asset('Assets/Icons & Logo/dashboard.png') }}" alt="My Calendar" class="nav-icon">
          <span class="nav-text">Journeys</span>
        </a>
      </li>
      <li class="nav-item">
        <a href="{{ route('child.treasures') }}"
           class="nav-link nav-link-treasures {{ $currentPage === 'treasures' ? 'active' : '' }}">
          <img src="{{ asset('Assets/Icons & Logo/treasure.png') }}" alt="Treasure Shop" class="nav-icon">
          <span class="nav-text">Treasure Shop</span>
        </a>
      </li>
      <li class="nav-item">
        <form action="{{ route('child.logout') }}" method="POST" style="margin: 0;">
          @csrf
          <button type="submit" class="nav-link nav-link-logout">
            <img src="{{ asset('Assets/Icons & Logo/campfire.png') }}" alt="Camp" class="nav-icon">
            <span class="nav-text">Camp</span>
          </button>
        </form>
      </li>
    </ul>
  </div>
</nav>
