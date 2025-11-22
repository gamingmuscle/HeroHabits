<?php
require_once 'db_config.php';
require_once 'session_enhanced.php';

// Redirect if already logged in as child
if (isChildLoggedIn()) {
    header('Location: child_calendar.php');
    exit();
}

// Check if parent cookie exists
$has_parent_cookie = hasParentCookie();
$children_from_cookie = getChildrenFromCookie();

$message = '';
$message_type = '';

// Handle child login
if (isset($_POST['child_login'])) {
    $conn = getDBConnection();
    $child_id = (int)$_POST['child_id'];
    $pin = $_POST['pin'];

    // Verify child and PIN
    $sql = "SELECT c.*, u.id as parent_user_id
            FROM children c
            JOIN users u ON c.user_id = u.id
            WHERE c.id = ? AND c.pin = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $child_id, $pin);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        loginChild($row['id'], $row['name'], $row['parent_user_id']);
        header('Location: child_calendar.php');
        exit();
    } else {
        $message = "Incorrect PIN. Please try again.";
        $message_type = "error";
    }

    $stmt->close();
    $conn->close();
}

// Get all children for dropdown (if no cookie)
$all_children = [];
if (!$has_parent_cookie) {
    $conn = getDBConnection();
    $sql = "SELECT id, name, avatar_image FROM children ORDER BY name ASC";
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        $all_children[] = $row;
    }
    $conn->close();
}

$children_list = $has_parent_cookie ? $children_from_cookie : $all_children;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Child Login - Hero Habits</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/modern-theme.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 1rem;
        }

        .login-container {
            background: white;
            padding: 3rem;
            border-radius: var(--radius-2xl);
            box-shadow: var(--shadow-2xl);
            width: 100%;
            max-width: 500px;
            text-align: center;
        }

        .logo {
            width: 200px;
            height: auto;
            margin-bottom: 1.5rem;
        }

        .login-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 0.5rem;
        }

        .login-subtitle {
            font-size: 1rem;
            color: var(--gray-600);
            margin-bottom: 2rem;
        }

        .message {
            padding: 1rem 1.5rem;
            border-radius: var(--radius-lg);
            margin-bottom: 1.5rem;
            font-weight: 600;
        }

        .message.error {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            border: 1px solid #fca5a5;
            color: #991b1b;
        }

        .child-selector {
            margin: 2rem 0;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 1.5rem;
        }

        .child-option {
            cursor: pointer;
            transition: transform 0.2s ease;
        }

        .child-option:hover {
            transform: scale(1.05);
        }

        .child-option input[type="radio"] {
            display: none;
        }

        .child-option label {
            cursor: pointer;
            display: block;
        }

        .child-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 4px solid var(--gray-200);
            transition: all 0.2s ease;
            object-fit: cover;
        }

        .child-option input[type="radio"]:checked + label .child-avatar {
            border-color: var(--purple);
            box-shadow: 0 0 20px rgba(99, 102, 241, 0.5);
        }

        .child-name {
            margin-top: 0.75rem;
            color: var(--gray-700);
            font-weight: 600;
            font-size: 1rem;
        }

        .form-group {
            margin: 1.5rem 0;
        }

        .form-label {
            display: block;
            text-align: left;
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 0.5rem;
        }

        .pin-input {
            width: 100%;
            padding: 1rem 1.5rem;
            border: 2px solid var(--gray-300);
            border-radius: var(--radius-lg);
            font-size: 1.5rem;
            text-align: center;
            letter-spacing: 0.5rem;
            font-weight: 700;
            transition: all 0.2s ease;
            font-family: 'Inter', sans-serif;
        }

        .pin-input:focus {
            outline: none;
            border-color: var(--purple);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .login-button {
            width: 100%;
            padding: 1rem 1.5rem;
            margin-top: 1.5rem;
            background: linear-gradient(135deg, var(--primary) 0%, #8b5cf6 100%);
            border: none;
            color: white;
            font-weight: 600;
            font-size: 1rem;
            border-radius: var(--radius-lg);
            cursor: pointer;
            transition: all 0.2s ease;
            font-family: 'Inter', sans-serif;
        }

        .login-button:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow-lg);
        }

        .login-button:active {
            transform: translateY(0);
        }

        .dropdown-select {
            width: 100%;
            padding: 1rem 1.5rem;
            border: 2px solid var(--gray-300);
            border-radius: var(--radius-lg);
            font-size: 1rem;
            font-family: 'Inter', sans-serif;
            transition: all 0.2s ease;
        }

        .dropdown-select:focus {
            outline: none;
            border-color: var(--purple);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .portal-switch {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--gray-200);
        }

        .portal-switch a {
            color: var(--purple);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .portal-switch a:hover {
            text-decoration: underline;
        }

        .empty-state {
            padding: 2rem;
            color: var(--gray-600);
            font-size: 1rem;
        }

        @media (max-width: 640px) {
            .login-container {
                padding: 2rem;
            }

            .logo {
                width: 150px;
            }

            .child-avatar {
                width: 80px;
                height: 80px;
            }
        }
    </style>
</head>
<body>
<div class="login-container">
    <img src="http://127.0.0.1/HeroHabits/Assets/Icons & Logo/logo.png" alt="Hero Habits" class="logo">
    <h1 class="login-title">Welcome, Hero!</h1>
    <p class="login-subtitle">Select your profile and enter your PIN</p>

    <?php if ($message): ?>
        <div class="message <?php echo $message_type; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <?php if (count($children_list) > 0): ?>
        <form method="POST">
            <?php if ($has_parent_cookie && count($children_list) <= 4): ?>
                <!-- Show avatar selection if 4 or fewer children -->
                <div class="child-selector">
                    <?php foreach ($children_list as $child): ?>
                        <div class="child-option">
                            <input type="radio" name="child_id" id="child_<?php echo $child['id']; ?>"
                                   value="<?php echo $child['id']; ?>" required>
                            <label for="child_<?php echo $child['id']; ?>">
                                <img src="http://127.0.0.1/HeroHabits/Assets/<?php echo htmlspecialchars($child['avatar_image']); ?>"
                                     alt="Avatar" class="child-avatar">
                                <div class="child-name"><?php echo htmlspecialchars($child['name']); ?></div>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <!-- Show dropdown for many children or no cookie -->
                <div class="form-group">
                    <label class="form-label">Select Your Name</label>
                    <select name="child_id" class="dropdown-select" required>
                        <option value="">Choose your name...</option>
                        <?php foreach ($children_list as $child): ?>
                            <option value="<?php echo $child['id']; ?>">
                                <?php echo htmlspecialchars($child['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <div class="form-group">
                <label class="form-label">Enter Your PIN</label>
                <input type="password" name="pin" class="pin-input" maxlength="4"
                       placeholder="• • • •" required pattern="\d{4}" inputmode="numeric"
                       title="Enter your 4-digit PIN">
            </div>

            <button type="submit" name="child_login" class="login-button">
                Start My Adventure!!
            </button>
        </form>
    <?php else: ?>
        <div class="empty-state">
            <p>No child profiles found. Please ask a parent to create your profile first.</p>
        </div>
    <?php endif; ?>

    <div class="portal-switch">
        <a href="parent_login.php">Parent Portal →</a>
    </div>
</div>

<script>
// Auto-focus PIN input when child is selected
document.querySelectorAll('input[name="child_id"]').forEach(radio => {
    radio.addEventListener('change', function() {
        document.querySelector('.pin-input').focus();
    });
});

// Only allow numbers in PIN field
const pinInput = document.querySelector('.pin-input');
if (pinInput) {
    pinInput.addEventListener('keypress', function(e) {
        if (e.key < '0' || e.key > '9') {
            e.preventDefault();
        }
    });
}
</script>
</body>
</html>
