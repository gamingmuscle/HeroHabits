<?php
require_once 'db_config.php';
require_once 'session.php';

requireLogin();

$conn = getDBConnection();
$user_id = getCurrentUserId();

// Get selected child
$selected_child_id = $_GET['child_id'] ?? getSelectedChildId();

// Get all children
$children = [];
$sql = "SELECT * FROM children WHERE user_id = ? ORDER BY created_at ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $children[] = $row;
}
$stmt->close();

if (!$selected_child_id && count($children) > 0) {
    $selected_child_id = $children[0]['id'];
}

if ($selected_child_id) {
    setSelectedChild($selected_child_id);
}

$child = null;
if ($selected_child_id) {
    $sql = "SELECT * FROM children WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $selected_child_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $child = $result->fetch_assoc();
    $stmt->close();
}

$message = '';
$message_type = '';

// Handle adding a new treasure
if (isset($_POST['add_treasure'])) {
    $title = sanitize($conn, $_POST['title']);
    $description = sanitize($conn, $_POST['description']);
    $gold_cost = (int)$_POST['gold_cost'];

    $sql = "INSERT INTO treasures (user_id, title, description, gold_cost) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issi", $user_id, $title, $description, $gold_cost);

    if ($stmt->execute()) {
        $message = "Treasure added successfully!";
        $message_type = "success";
    } else {
        $message = "Error adding treasure.";
        $message_type = "error";
    }
    $stmt->close();
}

// Handle purchasing a treasure
if (isset($_POST['purchase_treasure']) && $selected_child_id && $child) {
    $treasure_id = (int)$_POST['treasure_id'];

    // Get treasure details
    $sql = "SELECT * FROM treasures WHERE id = ? AND user_id = ? AND is_available = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $treasure_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $treasure = $result->fetch_assoc();
    $stmt->close();

    if ($treasure) {
        if ($child['gold_balance'] >= $treasure['gold_cost']) {
            // Record purchase
            $sql = "INSERT INTO treasure_purchases (treasure_id, child_id, gold_spent) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iii", $treasure_id, $selected_child_id, $treasure['gold_cost']);
            $stmt->execute();
            $stmt->close();

            // Deduct gold from child
            $sql = "UPDATE children SET gold_balance = gold_balance - ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $treasure['gold_cost'], $selected_child_id);
            $stmt->execute();
            $stmt->close();

            $message = "Treasure purchased! -" . $treasure['gold_cost'] . " gold spent!";
            $message_type = "success";

            // Refresh child data
            $sql = "SELECT * FROM children WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $selected_child_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $child = $result->fetch_assoc();
            $stmt->close();
        } else {
            $message = "Not enough gold! Need " . $treasure['gold_cost'] . " gold.";
            $message_type = "error";
        }
    } else {
        $message = "Treasure not available.";
        $message_type = "error";
    }
}

// Handle toggling treasure availability
if (isset($_POST['toggle_treasure'])) {
    $treasure_id = (int)$_POST['treasure_id'];

    $sql = "UPDATE treasures SET is_available = NOT is_available WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $treasure_id, $user_id);
    $stmt->execute();
    $stmt->close();

    $message = "Treasure status updated.";
    $message_type = "success";
}

// Handle deleting a treasure
if (isset($_POST['delete_treasure'])) {
    $treasure_id = (int)$_POST['treasure_id'];

    $sql = "DELETE FROM treasures WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $treasure_id, $user_id);
    $stmt->execute();
    $stmt->close();

    $message = "Treasure deleted.";
    $message_type = "success";
}

// Get treasures for user
$treasures = [];
$sql = "SELECT * FROM treasures WHERE user_id = ? ORDER BY is_available DESC, gold_cost ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $treasures[] = $row;
}
$stmt->close();

// Get purchase history for selected child
$purchase_history = [];
if ($selected_child_id) {
    $sql = "SELECT tp.*, t.title, t.gold_cost
            FROM treasure_purchases tp
            JOIN treasures t ON tp.treasure_id = t.id
            WHERE tp.child_id = ?
            ORDER BY tp.purchased_at DESC
            LIMIT 10";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $selected_child_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $purchase_history[] = $row;
    }
    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Treasures - Hero Habits</title>
  <link href="https://fonts.googleapis.com/css2?family=Baloo+2&display=swap" rel="stylesheet">
  <style>
    :root {
      --nav-width: 240px;
      --bg: #F5F5F5;
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
      padding: 15px 20px;
      font-family: 'Luminari', fantasy;
    }
    @font-face {
      font-family: Luminari;
      src: url('http://127.0.0.1/HeroHabits/Assets/Fonts/Luminari-Regular.ttf') format('ttf');
    }
    header h1 {
      margin: 0;
      font-size: 2rem;
      font-weight: bold;
      color: #7E57C2;
    }
    header .center-btn {
      flex: 1;
      display: flex;
      justify-content: center;
    }
    button {
      padding: 10px 16px;
      margin-bottom: 6px;
      border: none;
      border-radius: 8px;
      color: white;
      font-size: 1rem;
      cursor: pointer;
      font-family: 'Quicksand', cursive;
    }
    button.quest {
      background-color: #7E57C2;
    }
    button.treasure {
      background-color: #FFCA28;
      color: #333;
    }
    button.small {
      padding: 6px 12px;
      font-size: 0.9rem;
    }
    header .right-text {
      font-size: 20px;
      color: #333;
    }
    header .right-text img, span {
      vertical-align: middle;
    }
    div.main_container {
      display: flex;
      flex: 1;
    }
    nav {
      width: 240px;
      height: 100%;
      position: sticky;
    }
    main {
      flex: 1;
      padding: 30px;
      background-image: url('http://127.0.0.1/HeroHabits/Assets/hero_habits_bg_1.png');
      background-size: cover;
    }
    .child_list {
      list-style-type: none;
    }
    .gold {
      color: #FFCA28;
    }
    img.profile_pic {
      height: 50px;
      width: 50px;
    }
    .content-box {
      background-color: rgba(255, 255, 255, 0.95);
      padding: 20px;
      border-radius: 10px;
      margin-bottom: 20px;
    }
    .treasure-card {
      background: white;
      border: 2px solid #FFCA28;
      border-radius: 10px;
      padding: 15px;
      margin-bottom: 15px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .treasure-card.inactive {
      opacity: 0.6;
      border-color: #ccc;
    }
    .treasure-info h3 {
      margin: 0 0 5px 0;
      color: #FFCA28;
    }
    .treasure-info p {
      margin: 5px 0;
      color: #666;
    }
    .treasure-actions {
      display: flex;
      gap: 10px;
    }
    .message {
      padding: 15px;
      border-radius: 5px;
      margin-bottom: 20px;
      font-weight: bold;
    }
    .message.success {
      background: #d4edda;
      color: #155724;
    }
    .message.error {
      background: #f8d7da;
      color: #721c24;
    }
    input[type="text"], input[type="number"], textarea {
      width: 100%;
      padding: 10px;
      margin: 5px 0;
      border: 1px solid #ccc;
      border-radius: 5px;
      box-sizing: border-box;
    }
    textarea {
      resize: vertical;
      min-height: 60px;
    }
    .form-row {
      margin-bottom: 15px;
    }
    label {
      display: block;
      font-weight: bold;
      margin-bottom: 5px;
      color: #333;
    }
    .purchase-history {
      list-style: none;
      padding: 0;
    }
    .purchase-history li {
      background: #f8f9fa;
      padding: 10px;
      margin: 5px 0;
      border-radius: 5px;
      border-left: 4px solid #FFCA28;
    }
  </style>
</head>
<body>
  <header>
    <h1>Hero Habits</h1>
    <div class="center-btn">
      <?php if (count($children) > 0): ?>
        <select onchange="window.location.href='?child_id=' + this.value" style="padding: 8px; border-radius: 5px; font-size: 14px;">
          <?php foreach ($children as $c): ?>
            <option value="<?php echo $c['id']; ?>" <?php echo ($c['id'] == $selected_child_id) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($c['name']); ?>
            </option>
          <?php endforeach; ?>
        </select>
      <?php endif; ?>
    </div>
    <div class="right-text">
      <?php if ($child): ?>
        <img class="profile_pic" src="http://127.0.0.1/HeroHabits/Assets/<?php echo htmlspecialchars($child['avatar_image']); ?>">
        <span><?php echo htmlspecialchars($child['name']); ?></span>
        <span class="gold"><?php echo $child['gold_balance']; ?> Gold</span>
      <?php else: ?>
        <span>No child selected</span>
      <?php endif; ?>
      <a href="?logout=1" style="margin-left: 15px; color: #333; text-decoration: none;">Logout</a>
    </div>
  </header>
  <div class="main_container">
    <nav>
      <ul class="child_list">
        <li>
          <button class="quest" onclick="window.location.href='quests.php'">Quests</button>
        </li>
        <li>
          <button class="treasure" onclick="window.location.href='treasures.php'">Treasures</button>
        </li>
        <li>
          <button class="quest" onclick="window.location.href='index.php'">Calendar</button>
        </li>
        <li>
          <button class="treasure" onclick="window.location.href='profiles.php'">Profiles</button>
        </li>
      </ul>
    </nav>
    <main>
      <?php if ($message): ?>
        <div class="message <?php echo $message_type; ?>">
          <?php echo htmlspecialchars($message); ?>
        </div>
      <?php endif; ?>

      <!-- Add Treasure Form -->
      <div class="content-box">
        <h2 style="color: #FFCA28; margin-top: 0;">Add New Treasure</h2>
        <form method="POST">
          <div class="form-row">
            <label>Treasure Name:</label>
            <input type="text" name="title" placeholder="e.g., Extra screen time" required>
          </div>
          <div class="form-row">
            <label>Description:</label>
            <textarea name="description" placeholder="Details about the reward..."></textarea>
          </div>
          <div class="form-row">
            <label>Gold Cost:</label>
            <input type="number" name="gold_cost" min="1" value="10" required>
          </div>
          <button type="submit" name="add_treasure" class="treasure">Add Treasure</button>
        </form>
      </div>

      <!-- Treasure Shop -->
      <div class="content-box">
        <h2 style="color: #FFCA28; margin-top: 0;">Treasure Shop</h2>
        <?php if ($child): ?>
          <p style="font-size: 1.2rem;"><strong>Current Balance: <span class="gold"><?php echo $child['gold_balance']; ?> Gold</span></strong></p>
        <?php endif; ?>

        <?php if (count($treasures) > 0): ?>
          <?php foreach ($treasures as $treasure): ?>
            <div class="treasure-card <?php echo !$treasure['is_available'] ? 'inactive' : ''; ?>">
              <div class="treasure-info">
                <h3><?php echo htmlspecialchars($treasure['title']); ?></h3>
                <?php if ($treasure['description']): ?>
                  <p><?php echo htmlspecialchars($treasure['description']); ?></p>
                <?php endif; ?>
                <p><strong class="gold">Cost: <?php echo $treasure['gold_cost']; ?> Gold</strong></p>
              </div>
              <div class="treasure-actions">
                <?php if ($treasure['is_available'] && $child): ?>
                  <form method="POST" style="margin: 0;">
                    <input type="hidden" name="treasure_id" value="<?php echo $treasure['id']; ?>">
                    <button type="submit" name="purchase_treasure" class="treasure small"
                      <?php echo ($child['gold_balance'] < $treasure['gold_cost']) ? 'disabled' : ''; ?>>
                      Purchase
                    </button>
                  </form>
                <?php endif; ?>
                <form method="POST" style="margin: 0;">
                  <input type="hidden" name="treasure_id" value="<?php echo $treasure['id']; ?>">
                  <button type="submit" name="toggle_treasure" class="quest small">
                    <?php echo $treasure['is_available'] ? 'Hide' : 'Show'; ?>
                  </button>
                </form>
                <form method="POST" style="margin: 0;" onsubmit="return confirm('Delete this treasure?');">
                  <input type="hidden" name="treasure_id" value="<?php echo $treasure['id']; ?>">
                  <button type="submit" name="delete_treasure" class="small" style="background: #dc3545;">Delete</button>
                </form>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p>No treasures yet. Add your first treasure above!</p>
        <?php endif; ?>
      </div>

      <!-- Purchase History -->
      <?php if ($child && count($purchase_history) > 0): ?>
        <div class="content-box">
          <h2 style="color: #FFCA28; margin-top: 0;">Recent Purchases</h2>
          <ul class="purchase-history">
            <?php foreach ($purchase_history as $purchase): ?>
              <li>
                <strong><?php echo htmlspecialchars($purchase['title']); ?></strong> -
                <span class="gold"><?php echo $purchase['gold_spent']; ?> Gold</span> -
                <?php echo date('M j, Y', strtotime($purchase['purchased_at'])); ?>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>
    </main>
  </div>
</body>
</html>
