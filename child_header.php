<?php
/**
 * Child Portal Header Component - Modern Design
 *
 * Usage: Set $page_title variable before including this file
 * Example: $page_title = 'My Quests';
 */

if (!isset($page_title)) {
    $page_title = 'Portal';
}

$child_name = getCurrentChildName();
$child_id = getCurrentChildId();

// Get child details
$conn = getDBConnection();
$sql = "SELECT * FROM children WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $child_id);
$stmt->execute();
$result = $stmt->get_result();
$child = $result->fetch_assoc();
$stmt->close();
$conn->close();
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

/* Child Profile Display */
.child-profile-display {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 0.5rem 1rem;
  background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
  border: 2px solid #bae6fd;
  border-radius: var(--radius-full);
}

.child-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  border: 2px solid var(--purple);
  object-fit: cover;
}

.child-info {
  display: flex;
  flex-direction: column;
  gap: 0.125rem;
}

.child-info-name {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--gray-700);
}

.child-info-gold {
  font-size: 0.875rem;
  font-weight: 700;
  color: var(--gold);
}

/* Logout Button */
.logout-btn {
  padding: 0.5rem 1rem;
  background: white;
  border: 2px solid var(--gray-200);
  border-radius: var(--radius-full);
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--gray-700);
  cursor: pointer;
  transition: all 0.2s ease;
  text-decoration: none;
  display: inline-block;
}

.logout-btn:hover {
  border-color: #ef4444;
  background: #fee2e2;
  color: #ef4444;
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
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

  .child-info-name {
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
      <div class="child-profile-display">
        <img src="http://127.0.0.1/HeroHabits/Assets/<?php echo htmlspecialchars($child['avatar_image']); ?>"
             alt="Avatar" class="child-avatar">
        <div class="child-info">
          <div class="child-info-name"><?php echo htmlspecialchars($child_name); ?></div>
          <div class="child-info-gold">⭐ <?php echo $child['gold_balance']; ?> Gold</div>
        </div>
      </div>

      <a href="?logout=1" class="logout-btn">Logout</a>
    </div>
  </div>
</header>
