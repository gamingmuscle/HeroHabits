<?php
require_once 'db_config.php';
require_once 'session_enhanced.php';

requireParentLogin();

$conn = getDBConnection();
$user_id = getParentUserId();
$displayname = getParentDisplayname();

// Get user information
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

$message = '';
$message_type = '';

// Handle display name update
if (isset($_POST['update_displayname'])) {
    $new_displayname = sanitize($conn, $_POST['displayname']);

    if (!empty($new_displayname)) {
        $sql = "UPDATE users SET displayname = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $new_displayname, $user_id);

        if ($stmt->execute()) {
            $_SESSION['displayname'] = $new_displayname;
            setcookie('hero_parent', $new_displayname, time() + (30 * 24 * 60 * 60), '/');
            $message = "Display name updated successfully!";
            $message_type = "success";
            $displayname = $new_displayname;
        } else {
            $message = "Error updating display name.";
            $message_type = "error";
        }
        $stmt->close();
    }
}

// Handle password reset
if (isset($_POST['reset_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Verify current password
    if (password_verify($current_password, $user['password'])) {
        if ($new_password === $confirm_password) {
            if (strlen($new_password) >= 6) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $sql = "UPDATE users SET password = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $hashed_password, $user_id);

                if ($stmt->execute()) {
                    $message = "Password updated successfully!";
                    $message_type = "success";
                } else {
                    $message = "Error updating password.";
                    $message_type = "error";
                }
                $stmt->close();
            } else {
                $message = "New password must be at least 6 characters.";
                $message_type = "error";
            }
        } else {
            $message = "New passwords do not match.";
            $message_type = "error";
        }
    } else {
        $message = "Current password is incorrect.";
        $message_type = "error";
    }
}

// Handle account deactivation
if (isset($_POST['deactivate_account'])) {
    $confirm_password = $_POST['deactivate_password'];

    if (password_verify($confirm_password, $user['password'])) {
        // Delete all related data
        // Children will cascade delete quests, completions, and purchases
        $sql = "DELETE FROM users WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);

        if ($stmt->execute()) {
            $stmt->close();
            $conn->close();
            logoutParent();
            header("Location: splash.php?deactivated=1");
            exit();
        } else {
            $message = "Error deactivating account.";
            $message_type = "error";
        }
        $stmt->close();
    } else {
        $message = "Password is incorrect. Account not deactivated.";
        $message_type = "error";
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Account Settings - Hero Habits</title>
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
    .btn-danger { background-color: #dc3545; }
    .btn-danger:hover { background-color: #c82333; }
    .main-container {
      display: flex;
      flex: 1;
    }
    main {
      flex: 1;
      padding: 30px;
    }
    .content-box {
      background: white;
      border-radius: 12px;
      padding: 25px;
      margin-bottom: 25px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .content-box h2 {
      color: var(--purple);
      margin-top: 0;
      margin-bottom: 15px;
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
    input[type="text"], input[type="password"] {
      width: 100%;
      padding: 10px;
      border: 1px solid #ccc;
      border-radius: 5px;
      box-sizing: border-box;
      font-size: 1rem;
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
    .message.error {
      background: #f8d7da;
      color: #721c24;
    }
    .danger-zone {
      border: 2px solid #dc3545;
      background: #fff5f5;
    }
    .danger-zone h2 {
      color: #dc3545;
    }
    .warning-text {
      color: #dc3545;
      font-weight: bold;
      margin: 10px 0;
    }
    .info-text {
      color: #666;
      font-size: 0.9rem;
      margin-top: 5px;
    }
  </style>
</head>
<body>
  <?php $page_title = 'Account Settings'; include 'parent_header.php'; ?>

  <div class="main-container">
    <?php $current_page = 'account'; include 'parent_nav.php'; ?>

    <main class="main-content">
      <?php if ($message): ?>
        <div class="message <?php echo $message_type; ?>">
          <?php echo htmlspecialchars($message); ?>
        </div>
      <?php endif; ?>

      <!-- Display Name Section -->
      <div class="content-box">
        <h2>Display Name</h2>
        <p class="info-text">This is how you'll be identified in the app.</p>
        <form method="POST">
          <div class="form-row">
            <label>Display Name:</label>
            <input type="text" name="displayname" value="<?php echo htmlspecialchars($displayname); ?>" required>
          </div>
          <button type="submit" name="update_displayname" class="btn-purple">Update Display Name</button>
        </form>
      </div>

      <!-- Password Reset Section -->
      <div class="content-box">
        <h2>Change Password</h2>
        <form method="POST">
          <div class="form-row">
            <label>Current Password:</label>
            <input type="password" name="current_password" required>
          </div>
          <div class="form-row">
            <label>New Password:</label>
            <input type="password" name="new_password" minlength="6" required>
            <p class="info-text">Must be at least 6 characters</p>
          </div>
          <div class="form-row">
            <label>Confirm New Password:</label>
            <input type="password" name="confirm_password" minlength="6" required>
          </div>
          <button type="submit" name="reset_password" class="btn-purple">Change Password</button>
        </form>
      </div>

      <!-- Account Info -->
      <div class="content-box">
        <h2>Account Information</h2>
        <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
        <p><strong>Account Created:</strong> <?php echo date('F j, Y', strtotime($user['created_at'])); ?></p>
      </div>

      <!-- Deactivate Account Section -->
      <div class="content-box danger-zone">
        <h2>⚠️ Danger Zone</h2>
        <p class="warning-text">Deactivating your account is permanent and cannot be undone!</p>
        <p class="info-text">This will delete:</p>
        <ul class="info-text">
          <li>Your account</li>
          <li>All child profiles</li>
          <li>All quests and quest completions</li>
          <li>All treasures and purchase history</li>
        </ul>
        <form method="POST" onsubmit="return confirm('Are you ABSOLUTELY SURE you want to deactivate your account? This cannot be undone!');">
          <div class="form-row">
            <label>Enter your password to confirm:</label>
            <input type="password" name="deactivate_password" required>
          </div>
          <button type="submit" name="deactivate_account" class="btn btn-danger">Deactivate Account</button>
        </form>
      </div>
    </main>
  </div>
</body>
</html>
