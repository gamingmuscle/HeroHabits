<?php
require_once 'db_config.php';
require_once 'session.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$message = '';
$message_type = '';

// Handle Registration
if (isset($_POST['register'])) {
    $conn = getDBConnection();
    $username = sanitize($conn, $_POST['new_username']);
    $password = $_POST['new_password'];

    if (strlen($username) < 3) {
        $message = "Username must be at least 3 characters.";
        $message_type = "error";
    } elseif (strlen($password) < 4) {
        $message = "Password must be at least 4 characters.";
        $message_type = "error";
    } else {
        // Hash password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO users (username, password_hash,displayname) VALUES (?, ?,'')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $username, $password_hash);

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

    $sql = "SELECT id, password_hash FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password_hash'])) {
            login($row['id'], $username);
            header('Location: index.php');
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
<html>
	<head>
	    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(to right, #4facfe, #00f2fe);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0px 4px 10px rgba(0,0,0,0.2);
            width: 350px;
            text-align: center;
        }
        h2 {
            margin-bottom: 15px;
        }form
            margin: 8px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        button {
            width: 95%;
            padding: 10px;
            margin-top: 10px;
            background: #4facfe;
            border: none;
            color: white;
            font-weight: bold;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background: #00c6fb;
        }
        .message {
            margin-top: 15px;
            padding: 10px;
            border-radius: 5px;
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
        input[type="text"], input[type="password"] {
            width: 90%;
            padding: 10px;
            margin: 8px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
    </style>
</head>
	</head>
<body>
<div class="container">
	<?php if ($message): ?>
		<div class="message <?php echo $message_type; ?>">
			<?php echo htmlspecialchars($message); ?>
		</div>
	<?php endif; ?>

	<h2>Login</h2>
	<form method="POST">
		<input type="text" name="username" placeholder="Username" required><br>
		<input type="password" name="password" placeholder="Password" required><br>
		<button type="submit" name="login">Login</button>
	</form>

	<h2>Register</h2>
	<form method="POST">
		<input type="text" name="new_username" placeholder="New Username" required><br>
		<input type="password" name="new_password" placeholder="New Password" required><br>
		<button type="submit" name="register">Register</button>
	</form>
</div>
</body>
</html>