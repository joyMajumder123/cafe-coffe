<?php
/**
 * Admin Authentication Guard
 * Include at the top of every admin page to enforce login + load RBAC session.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/rbac/RbacHelper.php';
require_once __DIR__ . '/rbac/csrf.php';
require_once __DIR__ . '/rbac/audit_log.php';

// Check if user is logged in
if (empty($_SESSION['admin']) || empty($_SESSION['admin_user_id'])) {
    // Backward compat: old session with just $_SESSION['admin'] = true but no user_id
    // Force re-login through the new system
    session_unset();
    session_destroy();
    header("Location: " . dirname($_SERVER['SCRIPT_NAME']) . "/login.php");
    if (basename($_SERVER['SCRIPT_NAME']) !== 'login.php') {
        header("Location: login.php");
    }
    exit();
}

// Refresh permissions cache every 5 minutes
if (
    empty($_SESSION['admin_perms_loaded']) ||
    (time() - $_SESSION['admin_perms_loaded']) > 300
) {
    require_once __DIR__ . '/db.php';
    rbac_refresh_session($conn);
}
?>
