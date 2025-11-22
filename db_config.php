<?php
/**
 * Database Configuration
 * HeroHabits - Database Connection Manager
 */

// Database configuration constants
define('DB_HOST', '10.0.0.104');
define('DB_USER', 'dba'); // Change if needed
define('DB_PASS', 'myDB@!23'); // Your MySQL password
define('DB_NAME', 'hero_habits');

/**
 * Create and return a database connection
 * @return mysqli Database connection object
 */
function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Set charset to UTF-8
    $conn->set_charset("utf8mb4");
    return $conn;
}

/**
 * Sanitize input to prevent SQL injection
 * @param mysqli $conn Database connection
 * @param string $data Input data to sanitize
 * @return string Sanitized data
 */
function sanitize($conn, $data) {
    return mysqli_real_escape_string($conn, trim($data));
}

/**
 * Execute a prepared statement and return results
 * @param mysqli $conn Database connection
 * @param string $sql SQL query with placeholders
 * @param string $types Parameter types (e.g., "si" for string, int)
 * @param array $params Parameters to bind
 * @return mysqli_result|bool Query result
 */
function executeQuery($conn, $sql, $types = "", $params = []) {
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    if ($types && count($params) > 0) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    return $result;
}
?>
