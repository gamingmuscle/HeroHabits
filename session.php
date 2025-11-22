<?php
/**
 * Session Management
 * HeroHabits - User Authentication and Session Handling
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if a user is logged in
 * @return bool True if logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Require login - redirect to splash page if not logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: splash.php');
        exit();
    }
}

/**
 * Log in a user
 * @param int $user_id User's database ID
 * @param string $username User's username
 */
function login($user_id, $username) {
    $_SESSION['user_id'] = $user_id;
    $_SESSION['username'] = $username;
}

/**
 * Log out the current user
 */
function logout() {
    session_destroy();
    header('Location: splash.php');
    exit();
}

/**
 * Get current user ID
 * @return int|null User ID or null if not logged in
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current username
 * @return string|null Username or null if not logged in
 */
function getCurrentUsername() {
    return $_SESSION['username'] ?? null;
}

/**
 * Set the currently selected child in session
 * @param int $child_id Child's database ID
 */
function setSelectedChild($child_id) {
    $_SESSION['selected_child_id'] = $child_id;
}

/**
 * Get the currently selected child ID
 * @return int|null Child ID or null if none selected
 */
function getSelectedChildId() {
    return $_SESSION['selected_child_id'] ?? null;
}
?>
