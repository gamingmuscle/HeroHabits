<?php
/**
 * Enhanced Session Management
 * HeroHabits - Parent and Child Session Handling with Timeout
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Session timeout: 30 minutes for parents
define('PARENT_TIMEOUT', 1800); // 30 minutes in seconds

/**
 * Check if a parent user is logged in
 * @return bool True if parent logged in, false otherwise
 */
function isParentLoggedIn() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
        return false;
    }

    if ($_SESSION['user_type'] !== 'parent') {
        return false;
    }

    // Check for session timeout
    if (isset($_SESSION['last_activity'])) {
        $elapsed = time() - $_SESSION['last_activity'];
        if ($elapsed > PARENT_TIMEOUT) {
            // Session timed out
            logoutParent();
            return false;
        }
    }

    // Update last activity time
    $_SESSION['last_activity'] = time();

    return true;
}

/**
 * Check if a child is logged in
 * @return bool True if child logged in, false otherwise
 */
function isChildLoggedIn() {
    return isset($_SESSION['child_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'child';
}

/**
 * Require parent login - redirect to parent login if not logged in
 */
function requireParentLogin() {
    if (!isParentLoggedIn()) {
        header('Location: parent_login.php');
        exit();
    }
}

/**
 * Require child login - redirect to child login if not logged in
 */
function requireChildLogin() {
    if (!isChildLoggedIn()) {
        header('Location: child_login.php');
        exit();
    }
}

/**
 * Log in a parent user
 * @param int $user_id User's database ID
 * @param string $username User's username
 * @param string $displayname User's display name
 * @param array $children Array of children data
 */
function loginParent($user_id, $username, $displayname, $children = []) {
    $_SESSION['user_id'] = $user_id;
    $_SESSION['username'] = $username;
    $_SESSION['displayname'] = $displayname;
    $_SESSION['user_type'] = 'parent';
    $_SESSION['last_activity'] = time();

    // Store children info in cookie (expires in 30 days)
    if (!empty($children)) {
        $children_data = json_encode($children);
        setcookie('hero_children', $children_data, time() + (30 * 24 * 60 * 60), '/');
    }

    // Store display name in cookie
    setcookie('hero_parent', $displayname, time() + (30 * 24 * 60 * 60), '/');
}

/**
 * Log in a child
 * @param int $child_id Child's database ID
 * @param string $child_name Child's name
 * @param int $user_id Parent's user ID
 */
function loginChild($child_id, $child_name, $user_id) {
    $_SESSION['child_id'] = $child_id;
    $_SESSION['child_name'] = $child_name;
    $_SESSION['parent_user_id'] = $user_id;
    $_SESSION['user_type'] = 'child';
    $_SESSION['last_activity'] = time();
}

/**
 * Log out parent
 */
function logoutParent() {
    // Clear session
    $_SESSION = [];

    // Destroy session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }

    session_destroy();

    header('Location: parent_login.php');
    exit();
}

/**
 * Log out child
 */
function logoutChild() {
    // Clear child session but keep parent cookies
    unset($_SESSION['child_id']);
    unset($_SESSION['child_name']);
    unset($_SESSION['parent_user_id']);
    unset($_SESSION['user_type']);

    header('Location: child_login.php');
    exit();
}

/**
 * Get current parent user ID
 * @return int|null User ID or null if not logged in
 */
function getParentUserId() {
    if (isParentLoggedIn()) {
        return $_SESSION['user_id'];
    }
    return null;
}

/**
 * Get current parent username
 * @return string|null Username or null if not logged in
 */
function getParentUsername() {
    if (isParentLoggedIn()) {
        return $_SESSION['username'];
    }
    return null;
}

/**
 * Get current parent display name
 * @return string|null Display name or null if not logged in
 */
function getParentDisplayname() {
    if (isParentLoggedIn()) {
        return $_SESSION['displayname'];
    }
    return null;
}

/**
 * Get current child ID
 * @return int|null Child ID or null if not logged in
 */
function getCurrentChildId() {
    if (isChildLoggedIn()) {
        return $_SESSION['child_id'];
    }
    return null;
}

/**
 * Get current child name
 * @return string|null Child name or null if not logged in
 */
function getCurrentChildName() {
    if (isChildLoggedIn()) {
        return $_SESSION['child_name'];
    }
    return null;
}

/**
 * Check if parent has logged in recently (cookie exists)
 * @return bool True if parent cookie exists
 */
function hasParentCookie() {
    return isset($_COOKIE['hero_parent']);
}

/**
 * Get children from cookie
 * @return array Array of children or empty array
 */
function getChildrenFromCookie() {
    if (isset($_COOKIE['hero_children'])) {
        $data = json_decode($_COOKIE['hero_children'], true);
        return is_array($data) ? $data : [];
    }
    return [];
}

/**
 * Get remaining session time in seconds
 * @return int Seconds remaining before timeout
 */
function getSessionTimeRemaining() {
    if (!isParentLoggedIn()) {
        return 0;
    }

    $elapsed = time() - $_SESSION['last_activity'];
    $remaining = PARENT_TIMEOUT - $elapsed;

    return max(0, $remaining);
}
?>
