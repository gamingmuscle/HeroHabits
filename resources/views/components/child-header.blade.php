@props(['pageTitle' => 'Portal', 'child'])

<header>
  <div class="header-container">
    <div class="header-title">
      <img src="{{ asset('Assets/Icons & Logo/logo.png') }}" alt="Hero Habits" class="logo">
      <span class="header-divider">—</span>
      <span class="page-name">{{ $pageTitle }}</span>
    </div>

    <div class="header-right">
      <div class="child-profile-display">
        <img src="{{ $child->avatar_image ? asset('Assets/Profile/' . $child->avatar_image) : asset('Assets/Profile/princess_3tr.png') }}"
             alt="Avatar" class="child-avatar">
        <div class="child-info">
          <div class="child-info-name">{{ $child->name }}</div>
          <div class="child-info-gold">
            <img src="{{ asset('Assets/Icons & Logo/gold_coin.png') }}" alt="Gold" class="gold-pile">
            {{ $child->gold_balance }} Gold
          </div>
        </div>
      </div>

    </div>
  </div>
</header>
