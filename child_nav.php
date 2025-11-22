<?php
/**
 * Child Portal Navigation Component - Modern Design
 *
 * Usage: Set $current_page variable before including this file
 * Example: $current_page = 'calendar';
 *
 * Valid values: 'calendar', 'quests', 'treasures'
 */

if (!isset($current_page)) {
    $current_page = '';
}
?>

<style>
/* Modern Sidebar Navigation */
.sidebar-nav {
  width: 280px;
  background: white;
  border-right: 1px solid var(--gray-200);
  padding: 1.5rem 1rem;
  height: calc(100vh - 73px);
  position: sticky;
  top: 73px;
  overflow-y: auto;
}

.nav-section {
  margin-bottom: 2rem;
}

.nav-section-title {
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--gray-500);
  padding: 0 0.75rem;
  margin-bottom: 0.75rem;
}

.nav-menu {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.nav-item {
  margin: 0;
}

.nav-link {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem;
  border-radius: var(--radius-lg);
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--gray-700);
  text-decoration: none;
  cursor: pointer;
  border: none;
  background: none;
  width: 100%;
  transition: all 0.2s ease;
  position: relative;
}

.nav-link:hover {
  background: var(--gray-100);
  color: var(--gray-900);
}

.nav-link.active {
  background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(139, 92, 246, 0.1) 100%);
  color: var(--purple);
  font-weight: 600;
}

.nav-link.active::before {
  content: '';
  position: absolute;
  left: 0;
  top: 50%;
  transform: translateY(-50%);
  width: 3px;
  height: 60%;
  background: linear-gradient(180deg, var(--purple) 0%, #8b5cf6 100%);
  border-radius: 0 3px 3px 0;
}

.nav-icon {
  width: 24px;
  height: 24px;
  flex-shrink: 0;
  object-fit: contain;
}

.nav-icon-emoji {
  font-size: 1.25rem;
  width: 24px;
  text-align: center;
  flex-shrink: 0;
}

.nav-text {
  flex: 1;
  text-align: left;
}

/* Specific nav item gradients on hover */
.nav-link-calendar:hover:not(.active) {
  background: linear-gradient(135deg, rgba(99, 102, 241, 0.05) 0%, rgba(139, 92, 246, 0.05) 100%);
}

.nav-link-quests:hover:not(.active) {
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.05) 0%, rgba(37, 99, 235, 0.05) 100%);
}

.nav-link-treasures:hover:not(.active) {
  background: linear-gradient(135deg, rgba(245, 158, 11, 0.05) 0%, rgba(217, 119, 6, 0.05) 100%);
}

/* Responsive */
@media (max-width: 968px) {
  .sidebar-nav {
    width: 240px;
  }
}

@media (max-width: 768px) {
  .sidebar-nav {
    display: none;
  }
}
</style>

<nav class="sidebar-nav">
  <div class="nav-section">
    <h3 class="nav-section-title">My Hero Journey</h3>
    <ul class="nav-menu">
      <li class="nav-item">
        <a href="child_calendar.php"
           class="nav-link nav-link-calendar <?php echo $current_page === 'calendar' ? 'active' : ''; ?>">
          <img src="Assets/Icons & Logo/home.png" alt="Calendar" class="nav-icon">
          <span class="nav-text">My Calendar</span>
        </a>
      </li>
      <li class="nav-item">
        <a href="child_quests.php"
           class="nav-link nav-link-quests <?php echo $current_page === 'quests' ? 'active' : ''; ?>">
          <img src="Assets/Icons & Logo/quest.png" alt="Quests" class="nav-icon">
          <span class="nav-text">My Quests</span>
        </a>
      </li>
      <li class="nav-item">
        <a href="child_treasures.php"
           class="nav-link nav-link-treasures <?php echo $current_page === 'treasures' ? 'active' : ''; ?>">
          <img src="Assets/Icons & Logo/treasure.png" alt="Treasures" class="nav-icon">
          <span class="nav-text">Treasure Shop</span>
        </a>
      </li>
    </ul>
  </div>
</nav>
