<?php
/**
 * Database Setup Script
 * Run this once to create the HeroHabits database and tables
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>HeroHabits - Database Setup</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(to right, #7E57C2, #B0BEC5);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0px 4px 20px rgba(0,0,0,0.3);
            max-width: 600px;
        }
        h1 {
            color: #7E57C2;
            font-family: 'Luminari', fantasy;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        button {
            background: #7E57C2;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 20px;
        }
        button:hover {
            background: #5E35B1;
        }
        pre {
            background: #f5f5f5;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Hero Habits - Database Setup</h1>

    <?php
    if (isset($_POST['setup'])) {
		require_once 'db_config.php';
        // First, try to connect without database selection to create it
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS);

        if ($conn->connect_error) {
            echo '<div class="error"><strong>Connection Error:</strong> ' . $conn->connect_error . '</div>';
            echo '<div class="info">Please check your database credentials in <code>db_config.php</code></div>';
        } else {
            echo '<div class="success">Connected to MySQL server successfully!</div>';

            // Create database if it doesn't exist
            $sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
            if ($conn->query($sql) === TRUE) {
                echo '<div class="success">Database "' . DB_NAME . '" created or already exists.</div>';
            } else {
                echo '<div class="error">Error creating database: ' . $conn->error . '</div>';
            }

            $conn->close();

            // Now connect to the specific database
            require_once 'db_config.php';
            $conn = getDBConnection();

            // Read and execute schema
            $schema = file_get_contents('schema.sql');
            $queries = explode(';', $schema);

            $success_count = 0;
            $error_count = 0;

            foreach ($queries as $query) {
                $query = trim($query);
                if (!empty($query)) {
                    if ($conn->query($query) === TRUE) {
                        $success_count++;
                    } else {
                        $error_count++;
                        echo '<div class="error">Error executing query: ' . $conn->error . '</div>';
                    }
                }
            }

            echo '<div class="success"><strong>Setup Complete!</strong><br>';
            echo "Successfully executed $success_count SQL statements.</div>";

            if ($error_count > 0) {
                echo '<div class="error">Encountered $error_count errors during setup.</div>';
            }

            echo '<div class="info">';
            echo '<strong>Next Steps:</strong><br>';
            echo '1. Go to <a href="splash.php">splash.php</a> to create your account<br>';
            echo '2. After logging in, create a child profile<br>';
            echo '3. Add some quests and treasures<br>';
            echo '4. Start tracking habits!<br><br>';
            echo '<strong>Security Note:</strong> Delete or restrict access to this setup.php file after setup.';
            echo '</div>';

            $conn->close();
        }
    } else {
        require_once 'db_config.php';
        ?>

        <div class="info">
            <strong>Current Database Configuration:</strong><br>
            Host: <?php echo DB_HOST; ?><br>
            User: <?php echo DB_USER; ?><br>
            Database: <?php echo DB_NAME; ?>
        </div>

        <div class="info">
            <strong>What this will do:</strong><br>
            1. Create the database "<?php echo DB_NAME; ?>" if it doesn't exist<br>
            2. Create all required tables (users, children, quests, etc.)<br>
            3. Set up proper relationships between tables<br>
            <br>
            This operation is safe to run multiple times.
        </div>

        <form method="POST">
            <button type="submit" name="setup">Run Database Setup</button>
        </form>

    <?php } ?>
</div>
</body>
</html>
