<?php
require_once 'db_config.php';
require_once 'session_enhanced.php';

requireParentLogin();

$conn = getDBConnection();
$user_id = getParentUserId();

$message = '';
$message_type = '';

// Handle adding a new child
if (isset($_POST['add_child'])) {
    $name = sanitize($conn, $_POST['name']);
    $age = (int)$_POST['age'];
    $avatar = sanitize($conn, $_POST['avatar']);
    $pin = $_POST['pin'];

    if (strlen($name) < 1) {
        $message = "Name is required.";
        $message_type = "error";
    } elseif (!preg_match('/^\d{4}$/', $pin)) {
        $message = "PIN must be exactly 4 digits.";
        $message_type = "error";
    } else {
        $sql = "INSERT INTO children (user_id, name, age, avatar_image, pin) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isiss", $user_id, $name, $age, $avatar, $pin);

        if ($stmt->execute()) {
            $message = "Child profile created successfully!";
            $message_type = "success";
        } else {
            $message = "Error creating profile.";
            $message_type = "error";
        }
        $stmt->close();
    }
}

// Handle updating PIN
if (isset($_POST['update_pin'])) {
    $child_id = (int)$_POST['child_id'];
    $new_pin = $_POST['new_pin'];

    if (!preg_match('/^\d{4}$/', $new_pin)) {
        $message = "PIN must be exactly 4 digits.";
        $message_type = "error";
    } else {
        $sql = "UPDATE children SET pin = ? WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sii", $new_pin, $child_id, $user_id);

        if ($stmt->execute()) {
            $message = "PIN updated successfully!";
            $message_type = "success";
        } else {
            $message = "Error updating PIN.";
            $message_type = "error";
        }
        $stmt->close();
    }
}

// Handle updating avatar
if (isset($_POST['update_avatar'])) {
    $child_id = (int)$_POST['child_id'];
    $new_avatar = sanitize($conn, $_POST['new_avatar']);

    $sql = "UPDATE children SET avatar_image = ? WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $new_avatar, $child_id, $user_id);

    if ($stmt->execute()) {
        $message = "Avatar updated successfully!";
        $message_type = "success";
    } else {
        $message = "Error updating avatar.";
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
  <title>Manage Profiles - Hero Habits</title>
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
      padding: 10px 16px;
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
    div.main_container {
      display: flex;
      flex: 1;
    }
    .content-box {
      background-color: rgba(255, 255, 255, 0.95);
      padding: 20px;
      border-radius: 10px;
      margin-bottom: 20px;
    }
    .child-card {
      background: white;
      border: 2px solid var(--purple);
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
      color: var(--purple);
    }
    .child-actions {
      display: flex;
      gap: 10px;
      flex-direction: column;
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
    input[type="text"], input[type="number"], input[type="password"], select {
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
      border-color: var(--purple);
    }
    .avatar-option img {
      width: 100%;
      height: auto;
      border-radius: 5px;
    }
    .gold {
      color: var(--gold);
    }
    .pin-display {
      font-family: monospace;
      font-size: 1.2rem;
      background: #f8f9fa;
      padding: 5px 10px;
      border-radius: 5px;
    }

    /* Modal Styles */
    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5);
      overflow: auto;
    }

    .modal-content {
      background-color: white;
      margin: 5% auto;
      padding: 30px;
      border-radius: 10px;
      width: 90%;
      max-width: 600px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
      position: relative;
    }

    .modal-header {
      margin-bottom: 20px;
    }

    .modal-header h2 {
      color: var(--purple);
      margin: 0;
    }

    .modal-footer {
      margin-top: 20px;
      display: flex;
      gap: 10px;
      justify-content: flex-end;
    }

    .btn-cancel {
      background-color: #6c757d;
    }

    .btn-cancel:hover {
      background-color: #5a6268;
    }
  </style>
</head>
<body>
  <?php $page_title = 'Manage Profiles'; include 'parent_header.php'; ?>
  <div class="main_container">
    <?php $current_page = 'profiles'; include 'parent_nav.php'; ?>
    <main class="main-content">
      <?php if ($message): ?>
        <div class="message <?php echo $message_type; ?>">
          <?php echo htmlspecialchars($message); ?>
        </div>
      <?php endif; ?>

      <!-- Children List -->
      <div class="content-box">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
          <h2 style="color: var(--purple); margin: 0;">Your Children</h2>
          <button class="btn-purple" onclick="showAddChildModal()">+ Add New Child</button>
        </div>
        <?php if (count($children) > 0): ?>
          <?php foreach ($children as $child): ?>
            <div class="child-card">
              <img src="http://127.0.0.1/HeroHabits/Assets/<?php echo htmlspecialchars($child['avatar_image']); ?>"
                   alt="Avatar" class="child-avatar">
              <div class="child-info">
                <h3><?php echo htmlspecialchars($child['name']); ?></h3>
                <p style="margin: 5px 0;">Age: <?php echo $child['age']; ?></p>
                <p style="margin: 5px 0;"><strong class="gold">Gold Balance: <?php echo $child['gold_balance']; ?></strong></p>
                <p style="margin: 5px 0;">PIN: <span class="pin-display"><?php echo htmlspecialchars($child['pin']); ?></span></p>
              </div>
              <div class="child-actions">
                <button class="btn-purple small" onclick="showAvatarForm(<?php echo $child['id']; ?>)">Change Avatar</button>
                <button class="btn-purple small" onclick="showPinForm(<?php echo $child['id']; ?>)">Change PIN</button>
                <button class="btn-gold small" onclick="window.location.href='parent_quests.php?child_id=<?php echo $child['id']; ?>'">
                  Manage Quests
                </button>
                <form method="POST" style="margin: 0;" onsubmit="return confirm('Delete this profile? All quests and data will be lost.');">
                  <input type="hidden" name="child_id" value="<?php echo $child['id']; ?>">
                  <button type="submit" name="delete_child" class="small" style="background: #dc3545; color: white;">Delete</button>
                </form>
              </div>
            </div>

            <!-- Avatar Update Form (hidden by default) -->
            <div id="avatar-form-<?php echo $child['id']; ?>" style="display: none;" class="content-box">
              <h3>Change Avatar for <?php echo htmlspecialchars($child['name']); ?></h3>
              <form method="POST">
                <input type="hidden" name="child_id" value="<?php echo $child['id']; ?>">
                <div class="form-row">
                  <label>Choose New Avatar:</label>
                  <input type="hidden" name="new_avatar" id="selectedAvatar-<?php echo $child['id']; ?>" value="<?php echo htmlspecialchars($child['avatar_image']); ?>">
                  <div class="avatar-grid">
                    <?php foreach ($avatars as $avatar): ?>
                      <div class="avatar-option <?php echo ($avatar == $child['avatar_image']) ? 'selected' : ''; ?>"
                           onclick="selectAvatarForChild('<?php echo $avatar; ?>', this, <?php echo $child['id']; ?>)">
                        <img src="http://127.0.0.1/HeroHabits/Assets/<?php echo $avatar; ?>" alt="Avatar">
                      </div>
                    <?php endforeach; ?>
                  </div>
                </div>
                <button type="submit" name="update_avatar" class="btn-purple">Update Avatar</button>
                <button type="button" class="btn-gold" onclick="hideAvatarForm(<?php echo $child['id']; ?>)">Cancel</button>
              </form>
            </div>

            <!-- PIN Update Form (hidden by default) -->
            <div id="pin-form-<?php echo $child['id']; ?>" style="display: none;" class="content-box">
              <h3>Change PIN for <?php echo htmlspecialchars($child['name']); ?></h3>
              <form method="POST">
                <input type="hidden" name="child_id" value="<?php echo $child['id']; ?>">
                <div class="form-row">
                  <label>New 4-Digit PIN:</label>
                  <input type="password" name="new_pin" placeholder="0000" maxlength="4" pattern="\d{4}" required inputmode="numeric">
                </div>
                <button type="submit" name="update_pin" class="btn-purple">Update PIN</button>
                <button type="button" class="btn-gold" onclick="hidePinForm(<?php echo $child['id']; ?>)">Cancel</button>
              </form>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p>No child profiles yet. Add your first one above!</p>
        <?php endif; ?>
      </div>
    </main>
  </div>

  <!-- Add Child Modal -->
  <div id="addChildModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Add New Child Profile</h2>
      </div>
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
          <label>4-Digit PIN (for child login):</label>
          <input type="password" name="pin" placeholder="0000" maxlength="4" pattern="\d{4}" required inputmode="numeric" title="Enter a 4-digit PIN">
          <small style="color: #666;">This PIN will be used by the child to log into their portal.</small>
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
        <div class="modal-footer">
          <button type="button" class="btn btn-cancel" onclick="closeAddChildModal()">Cancel</button>
          <button type="submit" name="add_child" class="btn btn-purple">Add Child</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    // For modal avatar selection
    function selectAvatar(avatarName, element) {
      // Remove selected class from all in modal
      const modal = document.getElementById('addChildModal');
      modal.querySelectorAll('.avatar-option').forEach(opt => {
        opt.classList.remove('selected');
      });
      // Add selected class to clicked
      element.classList.add('selected');
      // Update hidden input
      document.getElementById('selectedAvatar').value = avatarName;
    }

    // For child-specific avatar selection
    function selectAvatarForChild(avatarName, element, childId) {
      // Get the specific avatar form for this child
      const form = document.getElementById('avatar-form-' + childId);
      // Remove selected class from all in this form
      form.querySelectorAll('.avatar-option').forEach(opt => {
        opt.classList.remove('selected');
      });
      // Add selected class to clicked
      element.classList.add('selected');
      // Update hidden input for this child
      document.getElementById('selectedAvatar-' + childId).value = avatarName;
    }

    function showAvatarForm(childId) {
      document.getElementById('avatar-form-' + childId).style.display = 'block';
    }

    function hideAvatarForm(childId) {
      document.getElementById('avatar-form-' + childId).style.display = 'none';
    }

    function showPinForm(childId) {
      document.getElementById('pin-form-' + childId).style.display = 'block';
    }

    function hidePinForm(childId) {
      document.getElementById('pin-form-' + childId).style.display = 'none';
    }

    function showAddChildModal() {
      document.getElementById('addChildModal').style.display = 'block';
    }

    function closeAddChildModal() {
      document.getElementById('addChildModal').style.display = 'none';
    }

    // Close modal when clicking outside of it
    window.onclick = function(event) {
      const modal = document.getElementById('addChildModal');
      if (event.target === modal) {
        closeAddChildModal();
      }
    }

    // Close modal on Escape key
    document.addEventListener('keydown', function(event) {
      if (event.key === 'Escape') {
        closeAddChildModal();
      }
    });
  </script>
</body>
</html>
