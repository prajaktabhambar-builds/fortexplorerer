<?php
/**
 * FORT EXPLORER - Authentication & Session Helper
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Check if a user is currently logged in
 */
function is_logged_in(): bool {
    return !empty($_SESSION['user_id']);
}

/**
 * Check if the currently logged in user is an administrator
 */
function is_admin(): bool {
    return is_logged_in() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Get the currently logged-in user details
 */
function current_user(): ?array {
    if (!is_logged_in()) {
        return null;
    }

    static $cachedUser = null;
    if ($cachedUser !== null) {
        return $cachedUser;
    }

    $db = getDB();
    if (!$db) {
        return [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'] ?? 'User',
            'email' => $_SESSION['user_email'] ?? '',
            'role' => $_SESSION['user_role'] ?? 'user'
        ];
    }

    $stmt = $db->prepare("SELECT id, name, email, role, avatar, bio, phone, home_district, created_at FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if ($user) {
        $cachedUser = $user;
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        return $user;
    }

    // User was deleted or invalid
    logout_user();
    return null;
}

/**
 * Enforce that the user is logged in
 */
function require_login(?string $redirectUrl = null): void {
    if (!is_logged_in()) {
        $redirect = $redirectUrl ?? $_SERVER['REQUEST_URI'];
        set_flash('warning', 'Please sign in or register to access this feature.');
        header('Location: ' . BASE_URL . 'login.php?redirect=' . urlencode($redirect));
        exit;
    }
}

/**
 * Enforce that the user has admin role
 */
function require_admin(): void {
    require_login();
    if (!is_admin()) {
        set_flash('danger', 'Access denied. Administrator privileges required.');
        header('Location: ' . BASE_URL . 'index.php');
        exit;
    }
}

/**
 * Log in a user securely and regenerate session id
 */
function login_user(array $user): void {
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['logged_in_time'] = time();
}

/**
 * Log out user and destroy session
 */
function logout_user(): void {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    session_destroy();
}
