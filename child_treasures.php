<?php
require_once 'db_config.php';
require_once 'session_enhanced.php';

requireChildLogin();

$child_id = getCurrentChildId();
$child_name = getCurrentChildName();

$conn = getDBConnection();

$message = '';
$message_type = '';

// Get child details
$sql = "SELECT * FROM children WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $child_id);
$stmt->execute();
$result = $stmt->get_result();
$child = $result->fetch_assoc();
$stmt->close();

// Handle treasure purchase
if (isset($_POST['purchase_treasure'])) {
    $treasure_id = (int)$_POST['treasure_id'];

    // Get treasure details
    $sql = "SELECT * FROM treasures WHERE id = ? AND is_available = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $treasure_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $treasure = $result->fetch_assoc();
    $stmt->close();

    if ($treasure) {
        if ($child['gold_balance'] >= $treasure['gold_cost']) {
            // Record purchase
            $sql = "INSERT INTO treasure_purchases (treasure_id, child_id, gold_spent) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iii", $treasure_id, $child_id, $treasure['gold_cost']);
            $stmt->execute();
            $stmt->close();

            // Deduct gold from child
            $sql = "UPDATE children SET gold_balance = gold_balance - ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $treasure['gold_cost'], $child_id);
            $stmt->execute();
            $stmt->close();

            $message = "🎉 Treasure claimed! You spent " . $treasure['gold_cost'] . " gold. Show this to your parent!";
            $message_type = "success";

            // Refresh child data
            $sql = "SELECT * FROM children WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $child_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $child = $result->fetch_assoc();
            $stmt->close();
        } else {
            $needed = $treasure['gold_cost'] - $child['gold_balance'];
            $message = "Not enough gold! You need " . $needed . " more gold.";
            $message_type = "error";
        }
    } else {
        $message = "Treasure not available.";
        $message_type = "error";
    }
}

// Get available treasures (user_id from parent)
$sql = "SELECT user_id FROM children WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $child_id);
$stmt->execute();
$result = $stmt->get_result();
$child_data = $result->fetch_assoc();
$user_id = $child_data['user_id'];
$stmt->close();

$treasures = [];
$sql = "SELECT * FROM treasures WHERE user_id = ? AND is_available = 1 ORDER BY gold_cost ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $treasures[] = $row;
}
$stmt->close();

// Get recent purchases
$recent_purchases = [];
$sql = "SELECT tp.*, t.title, t.gold_cost
        FROM treasure_purchases tp
        JOIN treasures t ON tp.treasure_id = t.id
        WHERE tp.child_id = ?
        ORDER BY tp.purchased_at DESC
        LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $child_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $recent_purchases[] = $row;
}
$stmt->close();

$page_title = 'Treasure Shop';
$current_page = 'treasures';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Treasures - Hero Habits</title>
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

    .gold-balance-card {
      background: linear-gradient(135deg, var(--gold) 0%, #fbbf24 100%);
      border-radius: var(--radius-xl);
      padding: 2rem;
      text-align: center;
      margin-bottom: 2rem;
      box-shadow: var(--shadow-lg);
    }

    .gold-balance-label {
      font-size: 1rem;
      font-weight: 600;
      color: #78350f;
      margin-bottom: 0.5rem;
    }

    .gold-balance-amount {
      font-size: 3rem;
      font-weight: 800;
      color: white;
      text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
    }

    .section-title {
      font-size: 1.5rem;
      font-weight: 700;
      color: var(--gray-900);
      margin: 2rem 0 1.5rem 0;
    }

    .treasures-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      gap: 1.5rem;
      margin-bottom: 2rem;
    }

    .treasure-card {
      background: white;
      border: 2px solid var(--gray-200);
      border-radius: var(--radius-xl);
      padding: 1.5rem;
      text-align: center;
      transition: all 0.2s ease;
      display: flex;
      flex-direction: column;
    }

    .treasure-card:hover {
      transform: translateY(-2px);
      box-shadow: var(--shadow-lg);
      border-color: var(--gold);
    }

    .treasure-icon {
      font-size: 3rem;
      margin-bottom: 1rem;
    }

    .treasure-title {
      font-size: 1.25rem;
      font-weight: 700;
      color: var(--gray-900);
      margin: 0 0 0.75rem 0;
    }

    .treasure-description {
      color: var(--gray-600);
      margin: 0 0 1rem 0;
      line-height: 1.6;
      flex-grow: 1;
      min-height: 3rem;
    }

    .treasure-cost {
      font-size: 1.5rem;
      font-weight: 700;
      color: var(--gold);
      margin-bottom: 1rem;
    }

    .treasure-button {
      width: 100%;
      padding: 0.875rem 1.5rem;
      border: none;
      border-radius: var(--radius-lg);
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s ease;
      font-family: 'Inter', sans-serif;
      background: linear-gradient(135deg, var(--gold) 0%, #fbbf24 100%);
      color: #78350f;
    }

    .treasure-button:hover:not(:disabled) {
      transform: translateY(-1px);
      box-shadow: var(--shadow-md);
      background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
    }

    .treasure-button:disabled {
      background: var(--gray-200);
      color: var(--gray-500);
      cursor: not-allowed;
    }

    .recent-purchases-container {
      background: white;
      border-radius: var(--radius-xl);
      padding: 1.5rem;
      box-shadow: var(--shadow-md);
    }

    .purchase-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 1rem;
      margin-bottom: 0.75rem;
      background: var(--gray-50);
      border-radius: var(--radius-lg);
      border-left: 4px solid var(--gold);
    }

    .purchase-item:last-child {
      margin-bottom: 0;
    }

    .purchase-info {
      flex: 1;
    }

    .purchase-title {
      font-weight: 600;
      color: var(--gray-900);
      margin-bottom: 0.25rem;
    }

    .purchase-date {
      font-size: 0.875rem;
      color: var(--gray-500);
    }

    .purchase-cost {
      font-weight: 700;
      color: var(--gold);
      white-space: nowrap;
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

      .gold-balance-amount {
        font-size: 2.5rem;
      }

      .treasures-grid {
        grid-template-columns: 1fr;
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
			<h1>💎 Treasure Shop</h1>
		  </div>

		  <?php if ($message): ?>
			<div class="message <?php echo $message_type; ?>">
			  <?php echo $message; ?>
			</div>
		  <?php endif; ?>

		  <div class="gold-balance-card">
			<div class="gold-balance-label">Your Gold Balance</div>
			<div class="gold-balance-amount">⭐ <?php echo $child['gold_balance']; ?></div>
		  </div>

		  <?php if (count($treasures) > 0): ?>
			<div class="treasures-grid">
			  <?php foreach ($treasures as $treasure): ?>
				<div class="treasure-card">
				  <div class="treasure-icon">💎</div>
				  <h3 class="treasure-title"><?php echo htmlspecialchars($treasure['title']); ?></h3>

				  <?php if ($treasure['description']): ?>
					<p class="treasure-description"><?php echo htmlspecialchars($treasure['description']); ?></p>
				  <?php else: ?>
					<p class="treasure-description">&nbsp;</p>
				  <?php endif; ?>

				  <div class="treasure-cost">
					<?php echo $treasure['gold_cost']; ?> Gold ⭐
				  </div>

				  <form method="POST" onsubmit="return confirm('Are you sure you want to claim this treasure?');">
					<input type="hidden" name="treasure_id" value="<?php echo $treasure['id']; ?>">
					<button type="submit" name="purchase_treasure" class="treasure-button"
					  <?php echo ($child['gold_balance'] < $treasure['gold_cost']) ? 'disabled' : ''; ?>>
					  <?php if ($child['gold_balance'] >= $treasure['gold_cost']): ?>
						💎 Claim This!
					  <?php else: ?>
						🔒 Not Enough Gold
					  <?php endif; ?>
					</button>
				  </form>
				</div>
			  <?php endforeach; ?>
			</div>
		  <?php else: ?>
			<div class="empty-state">
			  <div class="empty-state-icon">💎</div>
			  <p class="empty-state-text">No treasures available yet! Ask a parent to add some.</p>
			</div>
		  <?php endif; ?>

		  <?php if (count($recent_purchases) > 0): ?>
			<h2 class="section-title">🎁 My Recent Treasures</h2>
			<div class="recent-purchases-container">
			  <?php foreach ($recent_purchases as $purchase): ?>
				<div class="purchase-item">
				  <div class="purchase-info">
					<div class="purchase-title"><?php echo htmlspecialchars($purchase['title']); ?></div>
					<div class="purchase-date"><?php echo date('M j, Y', strtotime($purchase['purchased_at'])); ?></div>
				  </div>
				  <div class="purchase-cost">
					<?php echo $purchase['gold_spent']; ?> Gold ⭐
				  </div>
				</div>
			  <?php endforeach; ?>
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
