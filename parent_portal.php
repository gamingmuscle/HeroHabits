<?php
require_once 'db_config.php';
require_once 'session_enhanced.php';

requireParentLogin();

$conn = getDBConnection();
$user_id = getParentUserId();
$displayname = getParentDisplayname();

// Get all children with their stats
$children = [];
$sql = "SELECT c.*,
        (SELECT COUNT(*) FROM quest_completions qc WHERE qc.child_id = c.id AND qc.status = 'Accepted') as completed_quests,
        (SELECT COUNT(*) FROM quest_completions qc WHERE qc.child_id = c.id AND qc.status = 'Pending') as pending_approvals,
        (SELECT COUNT(*) FROM quest_completions qc WHERE qc.child_id = c.id AND qc.completion_date = CURDATE() AND qc.status = 'Accepted') as completed_today
        FROM children c
        WHERE c.user_id = ?
        ORDER BY c.name ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $children[] = $row;
}
$stmt->close();

// Get total pending approvals across all children
$sql = "SELECT COUNT(*) as total_pending
        FROM quest_completions qc
        JOIN children c ON qc.child_id = c.id
        WHERE c.user_id = ? AND qc.status = 'Pending'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$pending_data = $result->fetch_assoc();
$total_pending = $pending_data['total_pending'];
$stmt->close();

$conn->close();

// Get session time remaining
$time_remaining = getSessionTimeRemaining();
$minutes_remaining = floor($time_remaining / 60);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Parent Portal - Hero Habits</title>
  <link href="https://fonts.googleapis.com/css2?family=Baloo+2&display=swap" rel="stylesheet">
  <style>
    :root {
      --purple: #7E57C2;
      --gold: #FFCA28;
      --bg: #f9f9f9;
    }
    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background: var(--bg);
      display: flex;
      flex-direction: column;
      min-height: 100vh;
    }
    header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      background: #B0BEC5;
      padding: 15px 30px;
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
	.content-box {
      background-color: rgba(255, 255, 255, 0.95);
      padding: 20px;
      border-radius: 10px;
      margin-bottom: 20px;
    }
    @font-face {
      font-family: Luminari;
      src: url('http://127.0.0.1/HeroHabits/Assets/Fonts/Luminari-Regular.ttf') format('ttf');
    }
    header h1 {
      margin: 0;
      font-size: 2rem;
      font-weight: bold;
      color: var(--purple);
      font-family: 'Luminari', fantasy;
    }
    .header-right {
      display: flex;
      align-items: center;
      gap: 20px;
    }
    .session-timer {
      background: rgba(255,255,255,0.3);
      padding: 8px 15px;
      border-radius: 8px;
      font-size: 14px;
    }
    button, .btn {
      padding: 10px 20px;
      border: none;
      border-radius: 8px;
      color: white;
      font-size: 1rem;
      cursor: pointer;
      text-decoration: none;
      display: inline-block;
    }
    .btn-purple {
      background-color: var(--purple);
    }
    .btn-purple:hover {
      background-color: #5E35B1;
    }
    .btn-gold {
      background-color: var(--gold);
      color: #333;
    }
    .btn-gold:hover {
      background-color: #FFB300;
    }
    .main-container {
      display: flex;
      flex: 1;
    }
    main {
      flex: 1;
      padding: 30px;
    }
    .dashboard-header {
      margin-bottom: 30px;
    }
    .dashboard-header h2 {
      color: var(--purple);
      margin-bottom: 10px;
    }
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }
    .stat-card {
      background: white;
      padding: 20px;
      border-radius: 12px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      text-align: center;
    }
    .stat-number {
      font-size: 3rem;
      font-weight: bold;
      color: var(--purple);
    }
    .stat-label {
      color: #666;
      margin-top: 5px;
    }
    .children-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      gap: 20px;
    }
    .child-card {
      background: white;
      border-radius: 12px;
      padding: 20px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      transition: transform 0.2s;
    }
    .child-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 5px 20px rgba(0,0,0,0.15);
    }
    .child-header {
      display: flex;
      align-items: center;
      margin-bottom: 15px;
    }
    .child-avatar {
      width: 60px;
      height: 60px;
      border-radius: 50%;
      margin-right: 15px;
    }
    .child-info h3 {
      margin: 0;
      color: var(--purple);
    }
    .child-stats {
      margin: 15px 0;
      padding: 15px;
      background: #f8f9fa;
      border-radius: 8px;
    }
    .child-stat {
      display: flex;
      justify-content: space-between;
      margin: 8px 0;
    }
    .gold {
      color: var(--gold);
      font-weight: bold;
    }
    .pending-badge {
      background: #ff6b6b;
      color: white;
      padding: 4px 12px;
      border-radius: 20px;
      font-size: 0.9rem;
      font-weight: bold;
    }
  </style>
</head>
<body>
  <?php $page_title = 'Dashboard'; include 'parent_header.php'; ?>

  <div class="main-container">
    <?php $current_page = 'dashboard'; include 'parent_nav.php'; ?>

    <main class="main-content">
      <div class="content-box">
        <div class="dashboard-header">
          <h2>Dashboard</h2>
          <p>Overview of all your children's progress</p>
        </div>

        <?php if ($total_pending > 0): ?>
          <div class="stats-grid">
            <div class="stat-card" style="border-left: 5px solid #ff6b6b;">
              <div class="stat-number"><?php echo $total_pending; ?></div>
              <div class="stat-label">Pending Approvals</div>
              <button class="btn btn-gold" style="margin-top: 15px;" onclick="window.location.href='parent_approvals.php'">
                Review Now
              </button>
            </div>
          </div>
        <?php endif; ?>

        <h3 style="color: var(--purple); margin-bottom: 20px;">Your Children</h3>

        <?php if (count($children) > 0): ?>
          <div class="children-grid">
            <?php foreach ($children as $child): ?>
              <div class="child-card">
                <div class="child-header">
                  <img src="http://127.0.0.1/HeroHabits/Assets/<?php echo htmlspecialchars($child['avatar_image']); ?>"
                       alt="Avatar" class="child-avatar">
                  <div class="child-info">
                    <h3><?php echo htmlspecialchars($child['name']); ?></h3>
                    <p style="margin: 5px 0; color: #666;">Age: <?php echo $child['age']; ?></p>
                  </div>
                </div>

                <div class="child-stats">
                  <div class="child-stat">
                    <span>Gold Balance:</span>
                    <span class="gold"><?php echo $child['gold_balance']; ?> ⭐</span>
                  </div>
                  <div class="child-stat">
                    <span>Completed Quests:</span>
                    <span><?php echo $child['completed_quests']; ?></span>
                  </div>
                  <div class="child-stat">
                    <span>Completed Today:</span>
                    <span><?php echo $child['completed_today']; ?></span>
                  </div>
                  <?php if ($child['pending_approvals'] > 0): ?>
                    <div class="child-stat">
                      <span>Pending Approval:</span>
                      <span class="pending-badge"><?php echo $child['pending_approvals']; ?></span>
                    </div>
                  <?php endif; ?>
                </div>

                <button class="btn btn-purple" style="width: 100%;"
                        onclick="window.location.href='parent_child_quest_history.php?child_id=<?php echo $child['id']; ?>'">
                  View Quest History
                </button>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="stat-card">
            <p>No child profiles yet.</p>
            <button class="btn btn-purple" onclick="window.location.href='parent_profiles.php'">
              Add Your First Child
            </button>
          </div>
        <?php endif; ?>
      </div>
    </main>
  </div>

  <script>
    // Auto-refresh session timer every minute
    setInterval(function() {
      location.reload();
    }, 60000);
  </script>
</body>
</html>

<?php
// Handle logout
if (isset($_GET['logout'])) {
    logoutParent();
}
?>
