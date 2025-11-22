<?php
require_once 'db_config.php';
require_once 'session_enhanced.php';

requireParentLogin();

$conn = getDBConnection();
$user_id = getParentUserId();

$message = '';
$message_type = '';

// Handle adding a new quest
if (isset($_POST['add_quest'])) {
    $title = sanitize($conn, $_POST['title']);
    $description = sanitize($conn, $_POST['description']);
    $gold_reward = (int)$_POST['gold_reward'];

    $sql = "INSERT INTO quests (user_id, title, description, gold_reward) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issi", $user_id, $title, $description, $gold_reward);

    if ($stmt->execute()) {
        $message = "Quest added successfully!";
        $message_type = "success";
    } else {
        $message = "Error adding quest.";
        $message_type = "error";
    }
    $stmt->close();
}

// Handle toggling quest active status
if (isset($_POST['toggle_quest'])) {
    $quest_id = (int)$_POST['quest_id'];

    $sql = "UPDATE quests SET is_active = NOT is_active WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $quest_id, $user_id);
    $stmt->execute();
    $stmt->close();

    $message = "Quest status updated.";
    $message_type = "success";
}

// Handle deleting a quest
if (isset($_POST['delete_quest'])) {
    $quest_id = (int)$_POST['quest_id'];

    $sql = "DELETE FROM quests WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $quest_id, $user_id);
    $stmt->execute();
    $stmt->close();

    $message = "Quest deleted.";
    $message_type = "success";
}

// Get quests for this parent with pending count across all children
$quests = [];
$sql = "SELECT q.*,
        (SELECT COUNT(*) FROM quest_completions qc
         WHERE qc.quest_id = q.id AND qc.status = 'Pending') as pending_count
        FROM quests q
        WHERE q.user_id = ?
        ORDER BY q.is_active DESC, q.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $quests[] = $row;
}
$stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Quests - Hero Habits</title>
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
    .header-user {
      display: flex;
      align-items: center;
      gap: 15px;
      position: relative;
    }
    .user-display-name {
      font-size: 1.1rem;
      font-weight: bold;
      color: #333;
    }
    .user-menu-btn {
      background: white;
      color: #333;
      border: 2px solid var(--purple);
      padding: 8px 16px;
      border-radius: 8px;
      cursor: pointer;
      font-weight: bold;
    }
    .user-menu-btn:hover {
      background: var(--purple);
      color: white;
    }
    .user-dropdown {
      display: none;
      position: absolute;
      top: 100%;
      right: 0;
      background: white;
      border: 2px solid var(--purple);
      border-radius: 8px;
      margin-top: 5px;
      min-width: 180px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.2);
      z-index: 1000;
    }
    .user-dropdown.show {
      display: block;
    }
    .user-dropdown a {
      display: block;
      padding: 12px 20px;
      color: #333;
      text-decoration: none;
      border-bottom: 1px solid #eee;
    }
    .user-dropdown a:last-child {
      border-bottom: none;
    }
    .user-dropdown a:hover {
      background: var(--purple);
      color: white;
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
    button.quest, .btn-purple {
      background-color: var(--purple);
    }
    button.treasure, .btn-gold {
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
    .quest-card {
      background: white;
      border: 2px solid var(--purple);
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
    .quest-info h3 {
      margin: 0 0 5px 0;
      color: var(--purple);
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
    .pending-badge {
      background: #ff6b6b;
      color: white;
      padding: 4px 10px;
      border-radius: 15px;
      font-size: 0.85rem;
      font-weight: bold;
    }
    .quest-list-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 15px;
    }
    .quest-list-header h2 {
      margin: 0;
      color: var(--purple);
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
      color: var(--purple);
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
    }
    .btn-cancel:hover {
      background-color: #5a6268;
    }
  </style>
</head>
<body>
  <?php $page_title = 'Manage Quests'; include 'parent_header.php'; ?>
  <div class="main_container">
    <?php $current_page = 'quests'; include 'parent_nav.php'; ?>
    <main class="main-content">
      <?php if ($message): ?>
        <div class="message <?php echo $message_type; ?>">
          <?php echo htmlspecialchars($message); ?>
        </div>
      <?php endif; ?>

        <!-- Quest List -->
        <div class="content-box">
          <div class="quest-list-header">
            <div>
              <h2>Quest List</h2>
              <p style="color: #666; margin: 5px 0 0 0;">Children submit quests - go to Approvals to approve/reject them.</p>
            </div>
            <button class="btn-purple" onclick="openAddQuestModal()">+ Add Quest</button>
          </div>

          <?php if (count($quests) > 0): ?>
            <?php foreach ($quests as $quest): ?>
              <div class="quest-card <?php echo !$quest['is_active'] ? 'inactive' : ''; ?>">
                <div class="quest-info">
                  <h3>
                    <?php echo htmlspecialchars($quest['title']); ?>
                    <?php if ($quest['pending_count'] > 0): ?>
                      <span class="pending-badge"><?php echo $quest['pending_count']; ?> pending</span>
                    <?php endif; ?>
                  </h3>
                  <?php if ($quest['description']): ?>
                    <p><?php echo htmlspecialchars($quest['description']); ?></p>
                  <?php endif; ?>
                  <p><strong class="gold">Reward: <?php echo $quest['gold_reward']; ?> Gold</strong></p>
                </div>
                <div class="quest-actions">
                  <form method="POST" style="margin: 0;">
                    <input type="hidden" name="quest_id" value="<?php echo $quest['id']; ?>">
                    <button type="submit" name="toggle_quest" class="treasure small">
                      <?php echo $quest['is_active'] ? 'Deactivate' : 'Activate'; ?>
                    </button>
                  </form>
                  <form method="POST" style="margin: 0;" onsubmit="return confirm('Delete this quest? All completions will be lost.');">
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
    </main>
  </div>

  <!-- Add Quest Modal -->
  <div id="addQuestModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Add New Quest</h2>
      </div>
      <form method="POST" id="addQuestForm">
        <div class="form-row">
          <label>Quest Title:</label>
          <input type="text" name="title" id="quest_title" placeholder="e.g., Brush teeth" required>
        </div>
        <div class="form-row">
          <label>Description:</label>
          <textarea name="description" id="quest_description" placeholder="Details about the quest..."></textarea>
        </div>
        <div class="form-row">
          <label>Gold Reward:</label>
          <input type="number" name="gold_reward" id="quest_gold" min="1" value="1" required>
        </div>
        <div class="modal-buttons">
          <button type="button" class="btn btn-cancel" onclick="closeAddQuestModal()">Cancel</button>
          <button type="submit" name="add_quest" class="btn btn-purple">Add Quest</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    // Modal functions
    function openAddQuestModal() {
      document.getElementById('addQuestModal').style.display = 'block';
    }

    function closeAddQuestModal() {
      document.getElementById('addQuestModal').style.display = 'none';
      // Clear form
      document.getElementById('addQuestForm').reset();
    }

    // Close modal if user clicks outside of it
    window.onclick = function(event) {
      const modal = document.getElementById('addQuestModal');
      if (event.target == modal) {
        closeAddQuestModal();
      }
    }

    // Close modal with Escape key
    document.addEventListener('keydown', function(event) {
      if (event.key === 'Escape') {
        closeAddQuestModal();
      }
    });
  </script>
</body>
</html>
