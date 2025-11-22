<?php
require_once 'db_config.php';
require_once 'session_enhanced.php';

requireChildLogin();

$child_id = getCurrentChildId();
$child_name = getCurrentChildName();

$conn = getDBConnection();

// Get the parent's user_id for this child
$sql = "SELECT user_id FROM children WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $child_id);
$stmt->execute();
$result = $stmt->get_result();
$child_data = $result->fetch_assoc();
$user_id = $child_data['user_id'];
$stmt->close();

$message = '';
$message_type = '';

// Handle quest submission
if (isset($_POST['submit_quest'])) {
    $quest_id = (int)$_POST['quest_id'];
    $completion_date = date('Y-m-d');

    // Get quest details
    $sql = "SELECT gold_reward FROM quests WHERE id = ? AND user_id = ? AND is_active = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $quest_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $quest = $result->fetch_assoc();
    $stmt->close();

    if ($quest) {
        // Check if already submitted today
        $sql = "SELECT id, status FROM quest_completions WHERE quest_id = ? AND completion_date = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $quest_id, $completion_date);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 0) {
            // Submit for approval (status = 'Pending')
            $sql = "INSERT INTO quest_completions (quest_id, child_id, completion_date, gold_earned, status)
                    VALUES (?, ?, ?, ?, 'Pending')";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iisi", $quest_id, $child_id, $completion_date, $quest['gold_reward']);
            $stmt->execute();
            $stmt->close();

            $message = "🎉 Quest submitted! Waiting for parent approval.";
            $message_type = "success";
        } else {
            $existing = $result->fetch_assoc();
            if ($existing['status'] === 'Pending') {
                $message = "⏳ You already submitted this quest today! Waiting for approval.";
            } elseif ($existing['status'] === 'Accepted') {
                $message = "✅ You already completed this quest today!";
            } else {
                $message = "❌ This quest was denied. Try again tomorrow!";
            }
            $message_type = "error";
        }
        
    } else {
        $message = "Quest not found or not active.";
        $message_type = "error";
    }
}

// Get active quests with today's status
$quests = [];
$sql = "SELECT q.*,
        (SELECT status FROM quest_completions qc
         WHERE qc.quest_id = q.id AND qc.child_id = ? AND qc.completion_date = CURDATE()) as today_status
        FROM quests q
        WHERE q.user_id = ? AND q.is_active = 1
        ORDER BY q.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $child_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $quests[] = $row;
}
$stmt->close();

$page_title = 'My Quests';
$current_page = 'quests';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Quests - Hero Habits</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/modern-theme.css">
  <style>
    body {
      font-family: 'Inter', sans-serif;
      margin: 0;
      background: var(--gray-50);
    }

    .app-container {
      display: flex;
      min-height: calc(100vh - 73px);
    }
	.quest-icon {
	  width: 36x;
	  height: 36px;
	  flex-shrink: 0;
	  object-fit: contain;
	}
	.content-box {
      background-color: rgba(255, 255, 255, 0.95);
      padding: 20px;
      border-radius: 10px;
      margin-bottom: 20px;
    }
    .main-content {
      flex: 1;
      padding: 2rem;
      max-width: 1400px;
      margin: 0 auto;
      width: 100%;
    }

    .page-header {
      margin-bottom: 2rem;
    }

    .page-header h1 {
      font-size: 2rem;
      font-weight: 700;
      color: var(--gray-900);
      margin: 0 0 0.5rem 0;
    }

    .page-header p {
      font-size: 1rem;
      color: var(--gray-600);
      margin: 0;
    }

    .message {
      padding: 1rem 1.5rem;
      border-radius: var(--radius-xl);
      margin-bottom: 1.5rem;
      font-weight: 600;
      box-shadow: var(--shadow-sm);
    }

    .message.success {
      background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
      border: 1px solid #6ee7b7;
      color: #065f46;
    }

    .message.error {
      background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
      border: 1px solid #fca5a5;
      color: #991b1b;
    }

    .quests-grid {
      display: grid;
      gap: 1.5rem;
    }

    .quest-card {
      background: white;
      border: 2px solid var(--gray-200);
      border-radius: var(--radius-xl);
      padding: 1.5rem;
      transition: all 0.2s ease;
    }

    .quest-card:hover {
      transform: translateY(-2px);
      box-shadow: var(--shadow-lg);
      border-color: var(--purple);
    }

    .quest-header {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      margin-bottom: 1rem;
    }

    .quest-title {
      font-size: 1.25rem;
      font-weight: 700;
      color: var(--gray-900);
      margin: 0;
    }

    .status-badge {
      padding: 0.5rem 1rem;
      border-radius: var(--radius-full);
      font-size: 0.875rem;
      font-weight: 600;
      white-space: nowrap;
    }

    .status-pending {
      background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
      color: #92400e;
      border: 1px solid #fbbf24;
    }

    .status-approved {
      background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
      color: #065f46;
      border: 1px solid #6ee7b7;
    }

    .status-rejected {
      background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
      color: #991b1b;
      border: 1px solid #fca5a5;
    }

    .quest-description {
      color: var(--gray-600);
      margin: 0 0 1rem 0;
      line-height: 1.6;
    }

    .quest-reward {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      font-size: 1.125rem;
      font-weight: 700;
      color: var(--gold);
      margin-bottom: 1rem;
    }

    .quest-button {
      width: 100%;
      padding: 0.875rem 1.5rem;
      border: none;
      border-radius: var(--radius-lg);
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s ease;
      font-family: 'Inter', sans-serif;
    }

    .quest-button:hover:not(:disabled) {
      transform: translateY(-1px);
      box-shadow: var(--shadow-md);
    }

    .quest-button.submit {
      background: linear-gradient(135deg, #10b981 0%, #059669 100%);
      color: white;
    }

    .quest-button.submit:hover {
      background: linear-gradient(135deg, #059669 0%, #047857 100%);
    }

    .quest-button.pending {
      background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
      color: #78350f;
      cursor: not-allowed;
    }

    .quest-button.approved {
      background: var(--gray-200);
      color: var(--gray-600);
      cursor: not-allowed;
    }

    .quest-button.rejected {
      background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
      color: white;
      cursor: not-allowed;
    }

    .empty-state {
      text-align: center;
      padding: 4rem 2rem;
      background: white;
      border-radius: var(--radius-xl);
      border: 2px dashed var(--gray-300);
    }

    .empty-state-icon {
      font-size: 4rem;
      margin-bottom: 1rem;
    }

    .empty-state-text {
      font-size: 1.125rem;
      color: var(--gray-600);
    }

    @media (max-width: 768px) {
      .main-content {
        padding: 1rem;
      }

      .quest-header {
        flex-direction: column;
        gap: 0.75rem;
      }
    }
  </style>
</head>
<body>
  <?php include 'child_header.php'; ?>

  <div class="app-container">
    <?php include 'child_nav.php'; ?>

    <main class="main-content">
		<div class="content-box">
      <div class="page-header">
        <h1><img src="Assets/Icons & Logo/quest.png" alt="Quests" class="quest-icon"> My Quests</h1>
        <p>Complete your quests and submit them for parent approval!</p>
      </div>

      <?php if ($message): ?>
        <div class="message <?php echo $message_type; ?>">
          <?php echo $message; ?>
        </div>
      <?php endif; ?>

      <?php if (count($quests) > 0): ?>
        <div class="quests-grid">
          <?php foreach ($quests as $quest): ?>
            <div class="quest-card">
              <div class="quest-header">
                <h3 class="quest-title"><?php echo htmlspecialchars($quest['title']); ?></h3>
                <?php if ($quest['today_status']): ?>
                  <?php if ($quest['today_status'] === 'Pending'): ?>
                    <span class="status-badge status-pending">⏳ Waiting for Approval</span>
                  <?php elseif ($quest['today_status'] === 'Accepted'): ?>
                    <span class="status-badge status-approved">✅ Completed Today!</span>
                  <?php elseif ($quest['today_status'] === 'Denied'): ?>
                    <span class="status-badge status-rejected">❌ Denied - Try Tomorrow</span>
                  <?php endif; ?>
                <?php endif; ?>
              </div>

              <?php if ($quest['description']): ?>
                <p class="quest-description"><?php echo htmlspecialchars($quest['description']); ?></p>
              <?php endif; ?>

              <div class="quest-reward">
                <span>Reward:</span>
                <span><?php echo $quest['gold_reward']; ?> Gold ⭐</span>
              </div>

              <form method="POST">
                <input type="hidden" name="quest_id" value="<?php echo $quest['id']; ?>">
                <?php if (!$quest['today_status']): ?>
                  <button type="submit" name="submit_quest" class="quest-button submit">
                    ✓ I Did This!
                  </button>
                <?php elseif ($quest['today_status'] === 'Pending'): ?>
                  <button type="button" class="quest-button pending" disabled>
                    ⏳ Waiting for Parent...
                  </button>
                <?php elseif ($quest['today_status'] === 'Accepted'): ?>
                  <button type="button" class="quest-button approved" disabled>
                    ✅ Already Completed
                  </button>
                <?php elseif ($quest['today_status'] === 'Denied'): ?>
                  <button type="button" class="quest-button rejected" disabled>
                    ❌ Try Again Tomorrow
                  </button>
                <?php endif; ?>
              </form>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="empty-state">
          <div class="empty-state-icon">⚔️</div>
          <p class="empty-state-text">No quests yet! Ask a parent to add some quests for you.</p>
        </div>
      <?php endif; ?>
	  </div>
    </main>
  </div>
</body>
</html>

<?php
// Handle logout
if (isset($_GET['logout'])) {
    logoutChild();
}

$conn->close();
?>
