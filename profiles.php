<?php
require_once 'db_config.php';
require_once 'session.php';

requireLogin();

$conn = getDBConnection();
$user_id = getCurrentUserId();

$message = '';
$message_type = '';

// Handle adding a new child
if (isset($_POST['add_child'])) {
    $name = sanitize($conn, $_POST['name']);
    $age = (int)$_POST['age'];
    $avatar = sanitize($conn, $_POST['avatar']);

    if (strlen($name) > 0) {
        $sql = "INSERT INTO children (user_id, name, age, avatar_image) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isis", $user_id, $name, $age, $avatar);

        if ($stmt->execute()) {
            $message = "Child profile created successfully!";
            $message_type = "success";
        } else {
            $message = "Error creating profile.";
            $message_type = "error";
        }
        $stmt->close();
    } else {
        $message = "Name is required.";
        $message_type = "error";
    }
}

// Handle updating a child
if (isset($_POST['update_child'])) {
    $child_id = (int)$_POST['child_id'];
    $name = sanitize($conn, $_POST['name']);
    $age = (int)$_POST['age'];
    $avatar = sanitize($conn, $_POST['avatar']);

    $sql = "UPDATE children SET name = ?, age = ?, avatar_image = ? WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sisii", $name, $age, $avatar, $child_id, $user_id);

    if ($stmt->execute()) {
        $message = "Profile updated successfully!";
        $message_type = "success";
    } else {
        $message = "Error updating profile.";
        $message_type = "error";
    }
    $stmt->close();
}

// Handle deleting a child
if (isset($_POST['delete_child'])) {
    $child_id = (int)$_POST['child_id'];

    $sql = "DELETE FROM children WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $child_id, $user_id);

    if ($stmt->execute()) {
        $message = "Profile deleted.";
        $message_type = "success";
    } else {
        $message = "Error deleting profile.";
        $message_type = "error";
    }
    $stmt->close();
}

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

// Available avatars
$avatars = [
    'princess_2.png',
    'princess_3.png',
    'princess_3tr.png',
    'princess_laugh.png',
    'knight_girl_2.png',
    'knight_girl_3.png',
    'knight_girl_4.png'
];

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Child Profiles - Hero Habits</title>
  <link href="https://fonts.googleapis.com/css2?family=Baloo+2&display=swap" rel="stylesheet">
  <style>
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
    div.main_container {
      display: flex;
      flex: 1;
    }
    nav {
      width: 240px;
      background: white;
      padding: 20px;
    }
    main {
      flex: 1;
      padding: 30px;
      background-image: url('http://127.0.0.1/HeroHabits/Assets/hero_habits_bg_1.png');
      background-size: cover;
    }
    .child_list {
      list-style-type: none;
      padding: 0;
    }
    .content-box {
      background-color: rgba(255, 255, 255, 0.95);
      padding: 20px;
      border-radius: 10px;
      margin-bottom: 20px;
    }
    .child-card {
      background: white;
      border: 2px solid #7E57C2;
      border-radius: 10px;
      padding: 15px;
      margin-bottom: 15px;
      display: flex;
      align-items: center;
      gap: 20px;
    }
    .child-avatar {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      object-fit: cover;
    }
    .child-info {
      flex: 1;
    }
    .child-info h3 {
      margin: 0 0 5px 0;
      color: #7E57C2;
    }
    .child-actions {
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
    input[type="text"], input[type="number"], select {
      width: 100%;
      padding: 10px;
      margin: 5px 0;
      border: 1px solid #ccc;
      border-radius: 5px;
      box-sizing: border-box;
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
    .avatar-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 10px;
      margin-top: 10px;
    }
    .avatar-option {
      cursor: pointer;
      border: 3px solid transparent;
      border-radius: 10px;
      padding: 5px;
      transition: border-color 0.2s;
    }
    .avatar-option:hover {
      border-color: #B0BEC5;
    }
    .avatar-option.selected {
      border-color: #7E57C2;
    }
    .avatar-option img {
      width: 100%;
      height: auto;
      border-radius: 5px;
    }
    .gold {
      color: #FFCA28;
    }
  </style>
</head>
<body>
  <header>
    <h1>Child Profiles</h1>
    <div style="flex: 1;"></div>
    <div>
      <a href="index.php" style="margin-right: 15px; color: #333; text-decoration: none;">Back to Calendar</a>
      <a href="?logout=1" style="color: #333; text-decoration: none;">Logout</a>
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

      <!-- Add Child Form -->
      <div class="content-box">
        <h2 style="color: #7E57C2; margin-top: 0;">Add New Child Profile</h2>
        <form method="POST" id="addChildForm">
          <div class="form-row">
            <label>Name:</label>
            <input type="text" name="name" placeholder="Child's name" required>
          </div>
          <div class="form-row">
            <label>Age:</label>
            <input type="number" name="age" min="1" max="18" value="6" required>
          </div>
          <div class="form-row">
            <label>Choose Avatar:</label>
            <input type="hidden" name="avatar" id="selectedAvatar" value="princess_3tr.png">
            <div class="avatar-grid">
              <?php foreach ($avatars as $avatar): ?>
                <div class="avatar-option <?php echo ($avatar == 'princess_3tr.png') ? 'selected' : ''; ?>"
                     onclick="selectAvatar('<?php echo $avatar; ?>', this)">
                  <img src="http://127.0.0.1/HeroHabits/Assets/<?php echo $avatar; ?>" alt="Avatar">
                </div>
              <?php endforeach; ?>
            </div>
          </div>
          <button type="submit" name="add_child" class="quest">Add Child</button>
        </form>
      </div>

      <!-- Children List -->
      <div class="content-box">
        <h2 style="color: #7E57C2; margin-top: 0;">Your Children</h2>
        <?php if (count($children) > 0): ?>
          <?php foreach ($children as $child): ?>
            <div class="child-card">
              <img src="http://127.0.0.1/HeroHabits/Assets/<?php echo htmlspecialchars($child['avatar_image']); ?>"
                   alt="Avatar" class="child-avatar">
              <div class="child-info">
                <h3><?php echo htmlspecialchars($child['name']); ?></h3>
                <p>Age: <?php echo $child['age']; ?></p>
                <p><strong class="gold">Gold Balance: <?php echo $child['gold_balance']; ?></strong></p>
              </div>
              <div class="child-actions">
                <button class="quest small" onclick="window.location.href='index.php?child_id=<?php echo $child['id']; ?>'">
                  View Calendar
                </button>
                <form method="POST" style="margin: 0;" onsubmit="return confirm('Delete this profile? All quests and data will be lost.');">
                  <input type="hidden" name="child_id" value="<?php echo $child['id']; ?>">
                  <button type="submit" name="delete_child" class="small" style="background: #dc3545;">Delete</button>
                </form>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p>No child profiles yet. Add your first one above!</p>
        <?php endif; ?>
      </div>
    </main>
  </div>

  <script>
    function selectAvatar(avatarName, element) {
      // Remove selected class from all
      document.querySelectorAll('.avatar-option').forEach(opt => {
        opt.classList.remove('selected');
      });
      // Add selected class to clicked
      element.classList.add('selected');
      // Update hidden input
      document.getElementById('selectedAvatar').value = avatarName;
    }
  </script>
</body>
</html>
