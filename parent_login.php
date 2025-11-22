<?php
require_once 'db_config.php';
require_once 'session_enhanced.php';

// Redirect if already logged in
if (isParentLoggedIn()) {
    header('Location: parent_portal.php');
    exit();
}

$message = '';
$message_type = '';

// Handle Registration
if (isset($_POST['register'])) {
    $conn = getDBConnection();
    $username = sanitize($conn, $_POST['new_username']);
    $password = $_POST['new_password'];
    $displayname = sanitize($conn, $_POST['displayname']);

    if (strlen($username) < 3) {
        $message = "Username must be at least 3 characters.";
        $message_type = "error";
    } elseif (strlen($password) < 4) {
        $message = "Password must be at least 4 characters.";
        $message_type = "error";
    } elseif (strlen($displayname) < 1) {
        $message = "Display name is required.";
        $message_type = "error";
    } else {
        // Hash password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO users (username, password_hash, displayname) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $username, $password_hash, $displayname);

        if ($stmt->execute()) {
            $message = "Registration successful! Please login.";
            $message_type = "success";
        } else {
            $message = "Username already exists. Please choose another.";
            $message_type = "error";
        }

        $stmt->close();
    }
    $conn->close();
}

// Handle Login
if (isset($_POST['login'])) {
    $conn = getDBConnection();
    $username = sanitize($conn, $_POST['username']);
    $password = $_POST['password'];

    $sql = "SELECT id, username, displayname, password_hash FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password_hash'])) {
            // Get children for this user
            $user_id = $row['id'];
            $sql_children = "SELECT id, name, avatar_image FROM children WHERE user_id = ?";
            $stmt_children = $conn->prepare($sql_children);
            $stmt_children->bind_param("i", $user_id);
            $stmt_children->execute();
            $result_children = $stmt_children->get_result();

            $children = [];
            while ($child = $result_children->fetch_assoc()) {
                $children[] = $child;
            }
            $stmt_children->close();

            loginParent($row['id'], $row['username'], $row['displayname'], $children);
            header('Location: parent_portal.php');
            exit();
        } else {
            $message = "Invalid password.";
            $message_type = "error";
        }
    } else {
        $message = "User not found.";
        $message_type = "error";
    }

    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Parent Login - Hero Habits</title>
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0px 10px 30px rgba(0,0,0,0.3);
            width: 400px;
        }
        h1 {
            color: #7E57C2;
            text-align: center;
            font-family: 'Luminari', fantasy;
            margin-bottom: 10px;
        }
        @font-face {
            font-family: Luminari;
            src: url('http://127.0.0.1/HeroHabits/Assets/Fonts/Luminari-Regular.ttf') format('ttf');
        }
        h2 {
            color: #7E57C2;
            margin-bottom: 15px;
            font-size: 1.3rem;
        }
        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
        }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 12px;
            margin: 8px 0;
            border: 2px solid #ddd;
            border-radius: 8px;
            box-sizing: border-box;
            font-size: 14px;
        }
        input:focus {
            outline: none;
            border-color: #7E57C2;
        }
        button {
            width: 100%;
            padding: 12px;
            margin-top: 10px;
            background: #7E57C2;
            border: none;
            color: white;
            font-weight: bold;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background: #5E35B1;
        }
        .message {
            padding: 12px;
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
        .divider {
            text-align: center;
            margin: 30px 0;
            color: #999;
            position: relative;
        }
        .divider:before {
            content: "";
            position: absolute;
            left: 0;
            top: 50%;
            width: 45%;
            height: 1px;
            background: #ddd;
        }
        .divider:after {
            content: "";
            position: absolute;
            right: 0;
            top: 50%;
            width: 45%;
            height: 1px;
            background: #ddd;
        }
        .child-portal-link {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }
        .child-portal-link a {
            color: #FFCA28;
            text-decoration: none;
            font-weight: bold;
            font-size: 16px;
        }
        .child-portal-link a:hover {
            text-decoration: underline;
        }
		.logo {
		  height: 160px;
		  width: auto;
		  display: block;
		  margin: -1rem 0;
		}
    </style>
</head>
<body>
<div class="container">
    <img src="http://127.0.0.1/HeroHabits/Assets/Icons & Logo/logo.png" alt="Hero Habits" class="logo">
    <p class="subtitle">Parent Portal</p>

    <?php if ($message): ?>
        <div class="message <?php echo $message_type; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <h2>Login</h2>
    <form method="POST">
        <input type="text" name="username" placeholder="Username" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit" name="login">Login</button>
    </form>

    <div class="divider">OR</div>

    <h2>Register</h2>
    <form method="POST">
        <input type="text" name="new_username" placeholder="Username" required>
        <input type="password" name="new_password" placeholder="Password" required>
        <input type="text" name="displayname" placeholder="Display Name" required>
        <button type="submit" name="register">Register</button>
    </form>

    <div class="child-portal-link">
        <a href="child_login.php">→ Child Portal</a>
    </div>
</div>
</body>
</html>
