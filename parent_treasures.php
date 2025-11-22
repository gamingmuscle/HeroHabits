<?php
require_once 'db_config.php';
require_once 'session_enhanced.php';

requireParentLogin();

$conn = getDBConnection();
$user_id = getParentUserId();

// Get selected child
$selected_child_id = $_GET['child_id'] ?? null;

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
  <title>Manage Treasures - Hero Habits</title>
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
    header .center-btn {
      flex: 1;
      display: flex;
      justify-content: center;
    }
    button, .btn {
      padding: 10px 16px;
      margin-bottom: 6px;
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
    .btn-gold {
      background-color: var(--gold);
      color: #333;
    }
    button.small {
      padding: 6px 12px;
      font-size: 0.9rem;
    }
    header .right-text {
      font-size: 18px;
      color: #333;
    }
    header .right-text img, span {
      vertical-align: middle;
    }
    div.main_container {
      display: flex;
      flex: 1;
    }
    .gold {
      color: var(--gold);
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
      border: 2px solid var(--gold);
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
      color: var(--gold);
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
      border-left: 4px solid var(--gold);
    }
    .treasure-list-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 15px;
    }
    .treasure-list-header h2 {
      margin: 0;
      color: var(--gold);
    }
    /* Modal styles */
    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      overflow: auto;
      background-color: rgba(0,0,0,0.5);
    }
    .modal-content {
      background-color: #fefefe;
      margin: 5% auto;
      padding: 30px;
      border-radius: 15px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.3);
      width: 90%;
      max-width: 600px;
      animation: slideDown 0.3s ease-out;
    }
    @keyframes slideDown {
      from {
        transform: translateY(-50px);
        opacity: 0;
      }
      to {
        transform: translateY(0);
        opacity: 1;
      }
    }
    .modal-header {
      margin-bottom: 20px;
    }
    .modal-header h2 {
      margin: 0;
      color: var(--gold);
    }
    .modal-buttons {
      display: flex;
      gap: 10px;
      justify-content: flex-end;
      margin-top: 20px;
    }
    .btn-cancel {
      background-color: #6c757d;
      color: white;
      padding: 10px 20px;
      border: none;
      border-radius: 8px;
      cursor: pointer;
    }
    .btn-cancel:hover {
      background-color: #5a6268;
    }
  </style>
</head>
<body>
  <?php $page_title = 'Manage Treasures'; include 'parent_header.php'; ?>
  <div class="main_container">
    <?php $current_page = 'treasures'; include 'parent_nav.php'; ?>
    <main class="main-content">
      <?php if ($message): ?>
        <div class="message <?php echo $message_type; ?>">
          <?php echo htmlspecialchars($message); ?>
        </div>
      <?php endif; ?>

      <!-- Treasure List -->
      <div class="content-box">
        <div class="treasure-list-header">
          <div>
            <h2>Treasure List</h2>
            <p style="color: #666; margin: 5px 0 0 0;">Children can redeem these treasures in the Child Portal.</p>
          </div>
          <button class="btn-gold" onclick="openAddTreasureModal()">+ Add Treasure</button>
        </div>

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
                <form method="POST" style="margin: 0;">
                  <input type="hidden" name="treasure_id" value="<?php echo $treasure['id']; ?>">
                  <button type="submit" name="toggle_treasure" class="btn-purple small">
                    <?php echo $treasure['is_available'] ? 'Hide' : 'Show'; ?>
                  </button>
                </form>
                <form method="POST" style="margin: 0;" onsubmit="return confirm('Delete this treasure?');">
                  <input type="hidden" name="treasure_id" value="<?php echo $treasure['id']; ?>">
                  <button type="submit" name="delete_treasure" class="small" style="background: #dc3545; color: white;">Delete</button>
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
          <h2 style="color: var(--gold); margin-top: 0;"><?php echo htmlspecialchars($child['name']); ?>'s Recent Purchases</h2>
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

  <!-- Add Treasure Modal -->
  <div id="addTreasureModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Add New Treasure</h2>
      </div>
      <form method="POST" id="addTreasureForm">
        <div class="form-row">
          <label>Treasure Name:</label>
          <input type="text" name="title" id="treasure_title" placeholder="e.g., Extra screen time" required>
        </div>
        <div class="form-row">
          <label>Description:</label>
          <textarea name="description" id="treasure_description" placeholder="Details about the reward..."></textarea>
        </div>
        <div class="form-row">
          <label>Gold Cost:</label>
          <input type="number" name="gold_cost" id="treasure_cost" min="1" value="10" required>
        </div>
        <div class="modal-buttons">
          <button type="button" class="btn btn-cancel" onclick="closeAddTreasureModal()">Cancel</button>
          <button type="submit" name="add_treasure" class="btn btn-gold">Add Treasure</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    function openAddTreasureModal() {
      document.getElementById('addTreasureModal').style.display = 'block';
    }

    function closeAddTreasureModal() {
      document.getElementById('addTreasureModal').style.display = 'none';
      // Clear form
      document.getElementById('addTreasureForm').reset();
    }

    // Close modal if user clicks outside of it
    window.onclick = function(event) {
      const modal = document.getElementById('addTreasureModal');
      if (event.target == modal) {
        closeAddTreasureModal();
      }
    }

    // Close modal with Escape key
    document.addEventListener('keydown', function(event) {
      if (event.key === 'Escape') {
        closeAddTreasureModal();
      }
    });
  </script>
</body>
</html>
