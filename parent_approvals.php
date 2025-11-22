<?php
require_once 'db_config.php';
require_once 'session_enhanced.php';

requireParentLogin();

$conn = getDBConnection();
$user_id = getParentUserId();

$message = '';
$message_type = '';

// Handle acceptance/denial
if (isset($_POST['accept']) || isset($_POST['deny'])) {
    $completion_id = (int)$_POST['completion_id'];
    $action = isset($_POST['accept']) ? 'Accepted' : 'Denied';

    // Verify this completion belongs to user's child
    $sql = "SELECT qc.*, c.gold_balance
            FROM quest_completions qc
            JOIN children c ON qc.child_id = c.id
            WHERE qc.id = ? AND c.user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $completion_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $completion = $result->fetch_assoc();
    $stmt->close();

    if ($completion) {
        // Update completion status
        $sql = "UPDATE quest_completions
                SET status = ?, approved_by = ?, approved_at = NOW()
                WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sii", $action, $user_id, $completion_id);
        $stmt->execute();
        $stmt->close();

        // If accepted, add gold to child's balance
        if ($action === 'Accepted') {
            $sql = "UPDATE children SET gold_balance = gold_balance + ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $completion['gold_earned'], $completion['child_id']);
            $stmt->execute();
            $stmt->close();

            $message = "Quest accepted! +" . $completion['gold_earned'] . " gold awarded.";
            $message_type = "success";
        } else {
            $message = "Quest denied.";
            $message_type = "success";
        }
    }
}

// Get all pending approvals
$pending = [];
$sql = "SELECT qc.*, q.title as quest_title, q.description, c.name as child_name, c.avatar_image
        FROM quest_completions qc
        JOIN quests q ON qc.quest_id = q.id
        JOIN children c ON qc.child_id = c.id
        WHERE c.user_id = ? AND qc.status = 'Pending'
        ORDER BY qc.completed_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $pending[] = $row;
}
$stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Quest Approvals - Hero Habits</title>
  <link href="https://fonts.googleapis.com/css2?family=Baloo+2&display=swap" rel="stylesheet">
  <style>
    :root {
      --purple: #7E57C2;
      --gold: #FFCA28;
    }
    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background: #f9f9f9;
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
    }
    @font-face {
      font-family: Luminari;
      src: url('http://127.0.0.1/HeroHabits/Assets/Fonts/Luminari-Regular.ttf') format('ttf');
    }
    header h1 {
      margin: 0;
      font-size: 2rem;
      color: var(--purple);
      font-family: 'Luminari', fantasy;
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
    .btn-purple { background-color: var(--purple); }
    .btn-purple:hover { background-color: #5E35B1; }
    .btn-gold { background-color: var(--gold); color: #333; }
    .btn-gold:hover { background-color: #FFB300; }
    .btn-green { background-color: #28a745; }
    .btn-green:hover { background-color: #218838; }
    .btn-red { background-color: #dc3545; }
    .btn-red:hover { background-color: #c82333; }
    .main-container {
      display: flex;
      flex: 1;
    }
    main {
      flex: 1;
      padding: 30px;
    }
    .message {
      padding: 15px;
      border-radius: 8px;
      margin-bottom: 20px;
      font-weight: bold;
    }
    .message.success {
      background: #d4edda;
      color: #155724;
    }
    .approval-card {
      background: white;
      border-radius: 12px;
      padding: 20px;
      margin-bottom: 20px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .approval-header {
      display: flex;
      align-items: center;
      margin-bottom: 15px;
    }
    .child-avatar {
      width: 50px;
      height: 50px;
      border-radius: 50%;
      margin-right: 15px;
    }
    .approval-actions {
      display: flex;
      gap: 10px;
      margin-top: 15px;
    }
    .gold {
      color: var(--gold);
      font-weight: bold;
    }
  </style>
</head>
<body>
  <?php $page_title = 'Quest Approvals'; include 'parent_header.php'; ?>

  <div class="main-container">
    <?php $current_page = 'approvals'; include 'parent_nav.php'; ?>

    <main class="main-content">
      <?php if ($message): ?>
        <div class="message <?php echo $message_type; ?>">
          <?php echo htmlspecialchars($message); ?>
        </div>
      <?php endif; ?>

      <h2 style="color: var(--purple);">Pending Quest Approvals</h2>

      <?php if (count($pending) > 0): ?>
        <?php foreach ($pending as $item): ?>
          <div class="approval-card">
            <div class="approval-header">
              <img src="http://127.0.0.1/HeroHabits/Assets/<?php echo htmlspecialchars($item['avatar_image']); ?>"
                   alt="Avatar" class="child-avatar">
              <div>
                <h3 style="margin: 0; color: var(--purple);"><?php echo htmlspecialchars($item['child_name']); ?></h3>
                <p style="margin: 5px 0; color: #666;">
                  Completed: <?php echo date('M j, Y g:i A', strtotime($item['completed_at'])); ?>
                </p>
              </div>
            </div>

            <h4 style="color: var(--purple); margin: 10px 0;">
              <?php echo htmlspecialchars($item['quest_title']); ?>
            </h4>

            <?php if ($item['description']): ?>
              <p style="color: #666;"><?php echo htmlspecialchars($item['description']); ?></p>
            <?php endif; ?>

            <p>
              <strong>Reward:</strong>
              <span class="gold"><?php echo $item['gold_earned']; ?> Gold ⭐</span>
            </p>

            <div class="approval-actions">
              <form method="POST" style="display: inline;">
                <input type="hidden" name="completion_id" value="<?php echo $item['id']; ?>">
                <button type="submit" name="accept" class="btn btn-green">
                  ✓ Accept
                </button>
              </form>
              <form method="POST" style="display: inline;">
                <input type="hidden" name="completion_id" value="<?php echo $item['id']; ?>">
                <button type="submit" name="deny" class="btn btn-red"
                        onclick="return confirm('Are you sure you want to deny this quest?')">
                  ✗ Deny
                </button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="approval-card">
          <p style="text-align: center; color: #666; font-size: 1.2rem;">
            🎉 No pending approvals! All caught up!
          </p>
        </div>
      <?php endif; ?>
    </main>
  </div>
</body>
</html>
