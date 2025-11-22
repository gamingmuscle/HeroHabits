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

// Handle adding a new quest
if (isset($_POST['add_quest']) && $selected_child_id) {
    $title = sanitize($conn, $_POST['title']);
    $description = sanitize($conn, $_POST['description']);
    $gold_reward = (int)$_POST['gold_reward'];

    $sql = "INSERT INTO quests (child_id, title, description, gold_reward) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issi", $selected_child_id, $title, $description, $gold_reward);

    if ($stmt->execute()) {
        $message = "Quest added successfully!";
        $message_type = "success";
    } else {
        $message = "Error adding quest.";
        $message_type = "error";
    }
    $stmt->close();
}

// Handle completing a quest
if (isset($_POST['complete_quest']) && $selected_child_id) {
    $quest_id = (int)$_POST['quest_id'];
    $completion_date = date('Y-m-d');

    // Get quest details
    $sql = "SELECT gold_reward FROM quests WHERE id = ? AND child_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $quest_id, $selected_child_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $quest = $result->fetch_assoc();
    $stmt->close();

    if ($quest) {
        // Check if already completed today
        $sql = "SELECT id FROM quest_completions WHERE quest_id = ? AND completion_date = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $quest_id, $completion_date);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 0) {
            // Add completion
            $sql = "INSERT INTO quest_completions (quest_id, child_id, completion_date, gold_earned) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iisi", $quest_id, $selected_child_id, $completion_date, $quest['gold_reward']);
            $stmt->execute();
            $stmt->close();

            // Update child's gold balance
            $sql = "UPDATE children SET gold_balance = gold_balance + ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $quest['gold_reward'], $selected_child_id);
            $stmt->execute();
            $stmt->close();

            $message = "Quest completed! +" . $quest['gold_reward'] . " gold earned!";
            $message_type = "success";
        } else {
            $message = "This quest was already completed today!";
            $message_type = "error";
        }
    }
}

// Handle toggling quest active status
if (isset($_POST['toggle_quest']) && $selected_child_id) {
    $quest_id = (int)$_POST['quest_id'];

    $sql = "UPDATE quests SET is_active = NOT is_active WHERE id = ? AND child_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $quest_id, $selected_child_id);
    $stmt->execute();
    $stmt->close();

    $message = "Quest status updated.";
    $message_type = "success";
}

// Handle deleting a quest
if (isset($_POST['delete_quest']) && $selected_child_id) {
    $quest_id = (int)$_POST['quest_id'];

    $sql = "DELETE FROM quests WHERE id = ? AND child_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $quest_id, $selected_child_id);
    $stmt->execute();
    $stmt->close();

    $message = "Quest deleted.";
    $message_type = "success";
}

// Get quests for selected child
$quests = [];
if ($selected_child_id) {
    $sql = "SELECT q.*,
            (SELECT COUNT(*) FROM quest_completions qc WHERE qc.quest_id = q.id AND qc.completion_date = CURDATE()) as completed_today
            FROM quests q
            WHERE q.child_id = ?
            ORDER BY q.is_active DESC, q.created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $selected_child_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $quests[] = $row;
    }
    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Quests - Hero Habits</title>
  <link href="https://fonts.googleapis.com/css2?family=Baloo+2&display=swap" rel="stylesheet">
  <style>
    :root {
      --nav-width: 240px;
      --bg: #F5F5F5;
      --item: #1f2937;
      --text: #e5e7eb;
      --accent: #22c55e;
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
      top: 0px;
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
      display: block;
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
    .purple {
      color: white;
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
    .quest-card {
      background: white;
      border: 2px solid #7E57C2;
      border-radius: 10px;
      padding: 15px;
      margin-bottom: 15px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .quest-card.inactive {
      opacity: 0.6;
      border-color: #ccc;
    }
    .quest-card.completed {
      background: #d4edda;
      border-color: #28a745;
    }
    .quest-info h3 {
      margin: 0 0 5px 0;
      color: #7E57C2;
    }
    .quest-info p {
      margin: 5px 0;
      color: #666;
    }
    .quest-actions {
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

      <?php if ($child): ?>
        <!-- Add Quest Form -->
        <div class="content-box">
          <h2 style="color: #7E57C2; margin-top: 0;">Add New Quest</h2>
          <form method="POST">
            <div class="form-row">
              <label>Quest Title:</label>
              <input type="text" name="title" placeholder="e.g., Brush teeth" required>
            </div>
            <div class="form-row">
              <label>Description:</label>
              <textarea name="description" placeholder="Details about the quest..."></textarea>
            </div>
            <div class="form-row">
              <label>Gold Reward:</label>
              <input type="number" name="gold_reward" min="1" value="1" required>
            </div>
            <button type="submit" name="add_quest" class="quest">Add Quest</button>
          </form>
        </div>

        <!-- Quest List -->
        <div class="content-box">
          <h2 style="color: #7E57C2; margin-top: 0;">Active Quests</h2>
          <?php if (count($quests) > 0): ?>
            <?php foreach ($quests as $quest): ?>
              <div class="quest-card <?php echo !$quest['is_active'] ? 'inactive' : ''; ?> <?php echo $quest['completed_today'] > 0 ? 'completed' : ''; ?>">
                <div class="quest-info">
                  <h3><?php echo htmlspecialchars($quest['title']); ?></h3>
                  <?php if ($quest['description']): ?>
                    <p><?php echo htmlspecialchars($quest['description']); ?></p>
                  <?php endif; ?>
                  <p><strong class="gold">Reward: <?php echo $quest['gold_reward']; ?> Gold</strong></p>
                  <?php if ($quest['completed_today'] > 0): ?>
                    <p style="color: #28a745; font-weight: bold;">✓ Completed Today!</p>
                  <?php endif; ?>
                </div>
                <div class="quest-actions">
                  <?php if ($quest['is_active'] && $quest['completed_today'] == 0): ?>
                    <form method="POST" style="margin: 0;">
                      <input type="hidden" name="quest_id" value="<?php echo $quest['id']; ?>">
                      <button type="submit" name="complete_quest" class="quest small">Complete</button>
                    </form>
                  <?php endif; ?>
                  <form method="POST" style="margin: 0;">
                    <input type="hidden" name="quest_id" value="<?php echo $quest['id']; ?>">
                    <button type="submit" name="toggle_quest" class="treasure small">
                      <?php echo $quest['is_active'] ? 'Deactivate' : 'Activate'; ?>
                    </button>
                  </form>
                  <form method="POST" style="margin: 0;" onsubmit="return confirm('Delete this quest?');">
                    <input type="hidden" name="quest_id" value="<?php echo $quest['id']; ?>">
                    <button type="submit" name="delete_quest" class="small" style="background: #dc3545;">Delete</button>
                  </form>
                </div>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <p>No quests yet. Add your first quest above!</p>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <div class="content-box">
          <p>Please create a child profile first to manage quests.</p>
        </div>
      <?php endif; ?>
    </main>
  </div>
</body>
</html>
