<?php
require_once 'db_config.php';
require_once 'session_enhanced.php';

requireParentLogin();

$conn = getDBConnection();
$user_id = getParentUserId();

// Get child_id from URL
$child_id = isset($_GET['child_id']) ? (int)$_GET['child_id'] : 0;

// Verify child belongs to this parent
$sql = "SELECT * FROM children WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $child_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$child = $result->fetch_assoc();
$stmt->close();

if (!$child) {
    header('Location: parent_portal.php');
    exit();
}

// Get all quest completions for this child with quest details
$completions = [];
$sql = "SELECT qc.*, q.title, q.description, q.gold_reward,
        qc.completion_date as 'Submition Date',
        qc.approved_at as  'Approval Date'
        FROM quest_completions qc
        JOIN quests q ON qc.quest_id = q.id
        WHERE qc.child_id = ?
        ORDER BY qc.completion_date DESC, qc.approved_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $child_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $completions[] = $row;
}
$stmt->close();

$page_title = $child['name'] . "'s Quest History";
$current_page = 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Quest History - Hero Habits</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/modern-theme.css">
  <style>
    :root {
      --purple: #7E57C2;
      --gold: #FFCA28;
    }
    body {
      margin: 0;
      font-family: 'Inter', sans-serif;
      background: var(--gray-50);
    }
    .main_container {
      display: flex;
      flex: 1;
    }
    .content-box {
      background-color: rgba(255, 255, 255, 0.95);
      padding: 20px;
      border-radius: 10px;
      margin-bottom: 20px;
    }
    .child-header {
      display: flex;
      align-items: center;
      gap: 15px;
      margin-bottom: 20px;
      padding: 20px;
      background: linear-gradient(135deg, var(--purple) 0%, #9575CD 100%);
      border-radius: 10px;
      color: white;
    }
    .child-avatar {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      border: 3px solid white;
    }
    .child-info h2 {
      margin: 0;
      font-size: 2rem;
    }
    .child-info p {
      margin: 5px 0;
      opacity: 0.9;
    }
    .back-button {
      display: inline-block;
      padding: 10px 20px;
      background: var(--purple);
      color: white;
      text-decoration: none;
      border-radius: 8px;
      margin-bottom: 20px;
    }
    .back-button:hover {
      background: #5E35B1;
    }
    .quest-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }
    .quest-table th {
      background: var(--purple);
      color: white;
      padding: 12px;
      text-align: left;
      font-weight: 600;
    }
    .quest-table td {
      padding: 12px;
      border-bottom: 1px solid #ddd;
    }
    .quest-table tr:hover {
      background: #f5f5f5;
    }
    .status-badge {
      padding: 4px 12px;
      border-radius: 20px;
      font-size: 0.875rem;
      font-weight: 600;
      display: inline-block;
    }
    .status-accepted {
      background: #d4edda;
      color: #155724;
    }
    .status-pending {
      background: #fff3cd;
      color: #856404;
    }
    .status-denied {
      background: #f8d7da;
      color: #721c24;
    }
    .gold-amount {
      color: var(--gold);
      font-weight: bold;
    }
    .empty-state {
      text-align: center;
      padding: 40px;
      color: #666;
    }
  </style>
</head>
<body>
  <?php include 'parent_header.php'; ?>
  <div class="main_container">
    <?php include 'parent_nav.php'; ?>
    <main class="main-content">
      <a href="parent_portal.php" class="back-button">← Back to Dashboard</a>

      <div class="content-box">
        <div class="child-header">
          <img src="http://127.0.0.1/HeroHabits/Assets/<?php echo htmlspecialchars($child['avatar_image']); ?>"
               alt="Avatar" class="child-avatar">
          <div class="child-info">
            <h2><?php echo htmlspecialchars($child['name']); ?>'s Quest History</h2>
            <p>Age: <?php echo $child['age']; ?> | Gold Balance: <span class="gold-amount"><?php echo $child['gold_balance']; ?> ⭐</span></p>
          </div>
        </div>

        <?php if (count($completions) > 0): ?>
          <table class="quest-table">
            <thead>
              <tr>
                <th>Quest ID</th>
                <th>Quest Name</th>
                <th>Description</th>
                <th>Gold</th>
                <th>Status</th>
                <th>Submitted</th>
                <th>Approved/Updated</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($completions as $completion): ?>
                <tr>
                  <td><?php echo $completion['quest_id']; ?></td>
                  <td><strong><?php echo htmlspecialchars($completion['title']); ?></strong></td>
                  <td><?php echo htmlspecialchars($completion['description'] ?? 'N/A'); ?></td>
                  <td class="gold-amount"><?php echo $completion['gold_earned']; ?> ⭐</td>
                  <td>
                    <span class="status-badge status-<?php echo strtolower($completion['status']); ?>">
                      <?php
                        if ($completion['status'] === 'Accepted') echo '✅ Accepted';
                        elseif ($completion['status'] === 'Pending') echo '⏳ Pending';
                        else echo '❌ Denied';
                      ?>
                    </span>
                  </td>
                  <td><?php echo date('M j, Y g:i A', strtotime($completion['completed_at'])); ?></td>
                  <td>
                    <?php
                      if ($completion['status'] !== 'Pending' && $completion['approved_at']) {
                        echo date('M j, Y g:i A', strtotime($completion['approved_at']));
                      } else {
                        echo '-';
                      }
                    ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: ?>
          <div class="empty-state">
            <h3>No Quest History Yet</h3>
            <p><?php echo htmlspecialchars($child['name']); ?> hasn't completed any quests yet.</p>
          </div>
        <?php endif; ?>
      </div>
    </main>
  </div>
</body>
</html>

<?php
$conn->close();
?>
