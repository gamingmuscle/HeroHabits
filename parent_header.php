<?php
/**
 * Parent Portal Header Component - Modern Design
 *
 * Usage: Set $page_title variable before including this file
 * Example: $page_title = 'Dashboard';
 */

if (!isset($page_title)) {
    $page_title = 'Portal';
}

$displayname = getParentDisplayname();
$time_remaining = getSessionTimeRemaining();
$minutes_remaining = floor($time_remaining / 60);
?>

<link rel="stylesheet" href="assets/css/modern-theme.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
:root {
  --purple: #6366f1;
  --gold: #f59e0b;
}

/* Modern Header */
header {
  background: white;
  border-bottom: 1px solid var(--gray-200);
  padding: 1rem 2rem;
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
  position: sticky;
  top: 0;
  z-index: 50;
  backdrop-filter: blur(10px);
  background: rgba(255, 255, 255, 0.95);
}

.header-container {
  max-width: 1400px;
  margin: 0 auto;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.header-title {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.header-title .logo {
  height: 80px;
  width: auto;
  display: block;
  margin: -1rem 0;
}

.header-title .page-name {
  font-size: 1.5rem;
  font-weight: 600;
  color: var(--gray-700);
  margin: 0;
}

.header-divider {
  color: var(--gray-300);
  margin: 0 0.5rem;
}

.header-right {
  display: flex;
  align-items: center;
  gap: 1.5rem;
}

/* Modern Session Timer */
.session-timer {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
  border: 1px solid #bae6fd;
  border-radius: var(--radius-full);
  font-size: 0.875rem;
  font-weight: 600;
  color: #0369a1;
}

.session-timer-icon {
  font-size: 1rem;
}

/* Modern User Menu */
.user-menu-container {
  position: relative;
}

.user-menu-trigger {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.5rem 1rem;
  background: white;
  border: 2px solid var(--gray-200);
  border-radius: var(--radius-full);
  cursor: pointer;
  transition: all 0.2s ease;
  font-weight: 600;
  color: var(--gray-700);
}

.user-menu-trigger:hover {
  border-color: var(--purple);
  background: var(--gray-50);
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
}

.user-avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--purple) 0%, #8b5cf6 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 700;
  font-size: 0.875rem;
}

.user-name {
  font-size: 0.875rem;
}

.dropdown-arrow {
  font-size: 0.75rem;
  transition: transform 0.2s ease;
}

.user-menu-trigger.active .dropdown-arrow {
  transform: rotate(180deg);
}

/* Modern Dropdown Menu */
.user-dropdown {
  position: absolute;
  top: calc(100% + 0.5rem);
  right: 0;
  background: white;
  border: 1px solid var(--gray-200);
  border-radius: var(--radius-xl);
  box-shadow: var(--shadow-xl);
  min-width: 220px;
  opacity: 0;
  visibility: hidden;
  transform: translateY(-10px);
  transition: all 0.2s ease;
  overflow: hidden;
}

.user-dropdown.show {
  opacity: 1;
  visibility: visible;
  transform: translateY(0);
}

.dropdown-header {
  padding: 1rem 1.25rem;
  border-bottom: 1px solid var(--gray-100);
  background: var(--gray-50);
}

.dropdown-header-name {
  font-weight: 600;
  color: var(--gray-900);
  font-size: 0.875rem;
}

.dropdown-header-role {
  font-size: 0.75rem;
  color: var(--gray-500);
  margin-top: 0.25rem;
}

.dropdown-menu {
  padding: 0.5rem;
}

.dropdown-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  color: var(--gray-700);
  text-decoration: none;
  border-radius: var(--radius-md);
  font-size: 0.875rem;
  font-weight: 500;
  transition: all 0.15s ease;
}

.dropdown-item:hover {
  background: var(--gray-100);
  color: var(--gray-900);
}

.dropdown-item-icon {
  font-size: 1.125rem;
  width: 20px;
  text-align: center;
}

.dropdown-divider {
  height: 1px;
  background: var(--gray-200);
  margin: 0.5rem 0;
}

/* Responsive Design */
@media (max-width: 768px) {
  header {
    padding: 1rem;
  }

  .header-title .logo {
    height: 60px;
    margin: -0.75rem 0;
  }

  .header-title .page-name {
    font-size: 1.125rem;
  }

  .session-timer {
    display: none;
  }

  .user-name {
    display: none;
  }
}
</style>

<header>
  <div class="header-container">
    <div class="header-title">
      <img src="http://127.0.0.1/HeroHabits/Assets/Icons & Logo/logo.png" alt="Hero Habits" class="logo">
      <span class="header-divider">—</span>
      <span class="page-name"><?php echo htmlspecialchars($page_title); ?></span>
    </div>

    <div class="header-right">
      <div class="session-timer">
        <span class="session-timer-icon">⏱</span>
        <span><?php echo $minutes_remaining; ?> min remaining</span>
      </div>

      <div class="user-menu-container">
        <button class="user-menu-trigger" onclick="toggleUserMenu(event)">
          <div class="user-avatar">
            <?php echo strtoupper(substr($displayname, 0, 1)); ?>
          </div>
          <span class="user-name"><?php echo htmlspecialchars($displayname); ?></span>
          <span class="dropdown-arrow">▼</span>
        </button>

        <div class="user-dropdown" id="userDropdown">
          <div class="dropdown-header">
            <div class="dropdown-header-name"><?php echo htmlspecialchars($displayname); ?></div>
            <div class="dropdown-header-role">Parent Account</div>
          </div>
          <div class="dropdown-menu">
            <a href="parent_account.php" class="dropdown-item">
              <span class="dropdown-item-icon">⚙️</span>
              <span>Account Settings</span>
            </a>
            <div class="dropdown-divider"></div>
            <a href="?logout=1" class="dropdown-item">
              <span class="dropdown-item-icon">🚪</span>
              <span>Logout</span>
            </a>
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
