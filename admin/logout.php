<?php
session_start();

// Log the logout action before destroying session
if (!empty($_SESSION['admin_user_id'])) {
    include 'includes/db.php';
    require_once 'includes/rbac/audit_log.php';
    audit_log($conn, 'auth.logout', 'user:' . $_SESSION['admin_user_id']);
}

// Clear all session data
$_SESSION = [];

// Delete session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();
header("Location: login.php");
exit();
?>
