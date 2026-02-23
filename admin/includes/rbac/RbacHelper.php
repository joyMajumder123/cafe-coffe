<?php
/**
 * RBAC Helper Functions
 * Core permission checking, session management, and hierarchy utilities.
 */

/**
 * Load the current admin user's permissions into $_SESSION.
 * Called once on login and can be refreshed via rbac_refresh_session().
 */
function rbac_load_session(mysqli $conn, int $user_id): bool
{
    $stmt = $conn->prepare("
        SELECT u.id, u.username, u.email, u.full_name, u.role_id, u.status,
               r.name AS role_name, r.hierarchy_level
        FROM admin_users u
        JOIN roles r ON r.id = u.role_id
        WHERE u.id = ? AND u.status = 'active'
        LIMIT 1
    ");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) {
        return false;
    }

    // Load permission keys for this role
    $perms = [];
    $pstmt = $conn->prepare("
        SELECT p.perm_key
        FROM role_permissions rp
        JOIN permissions p ON p.id = rp.permission_id
        WHERE rp.role_id = ?
    ");
    $pstmt->bind_param('i', $user['role_id']);
    $pstmt->execute();
    $result = $pstmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $perms[] = $row['perm_key'];
    }
    $pstmt->close();

    // Store in session
    $_SESSION['admin']              = true; // backward compat
    $_SESSION['admin_user_id']      = (int)$user['id'];
    $_SESSION['admin_username']     = $user['username'];
    $_SESSION['admin_email']        = $user['email'];
    $_SESSION['admin_full_name']    = $user['full_name'];
    $_SESSION['admin_role_id']      = (int)$user['role_id'];
    $_SESSION['admin_role_name']    = $user['role_name'];
    $_SESSION['admin_hierarchy']    = (int)$user['hierarchy_level'];
    $_SESSION['admin_permissions']  = $perms;
    $_SESSION['admin_perms_loaded'] = time();

    return true;
}

/**
 * Refresh cached permissions (call after role/permission changes).
 */
function rbac_refresh_session(mysqli $conn): bool
{
    if (empty($_SESSION['admin_user_id'])) {
        return false;
    }
    return rbac_load_session($conn, $_SESSION['admin_user_id']);
}

/**
 * Check if the current user has a specific permission.
 */
function has_permission(string $perm_key): bool
{
    if (empty($_SESSION['admin_permissions'])) {
        return false;
    }
    return in_array($perm_key, $_SESSION['admin_permissions'], true);
}

/**
 * Require a permission — redirect with error if not authorized.
 */
function require_permission(string $perm_key, string $redirect = ''): void
{
    if (has_permission($perm_key)) {
        return;
    }

    if ($redirect === '') {
        $redirect = 'dashboard.php';
    }

    $_SESSION['flash_error'] = 'You do not have permission to access that page.';
    header('Location: ' . $redirect);
    exit();
}

/**
 * Check if the current user can manage a target user (hierarchy check).
 * A user can only manage users with a LOWER hierarchy level.
 */
function can_manage_user(mysqli $conn, int $target_user_id): bool
{
    $my_level = $_SESSION['admin_hierarchy'] ?? 0;
    $my_id    = $_SESSION['admin_user_id'] ?? 0;

    if ($target_user_id === $my_id) {
        return true; // can edit own profile
    }

    $stmt = $conn->prepare("
        SELECT r.hierarchy_level
        FROM admin_users u JOIN roles r ON r.id = u.role_id
        WHERE u.id = ?
    ");
    $stmt->bind_param('i', $target_user_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        return false;
    }

    return $my_level > $row['hierarchy_level'];
}

/**
 * Check if the current user can manage a given role.
 */
function can_manage_role(mysqli $conn, int $role_id): bool
{
    $my_level = $_SESSION['admin_hierarchy'] ?? 0;

    $stmt = $conn->prepare("SELECT hierarchy_level, is_system FROM roles WHERE id = ?");
    $stmt->bind_param('i', $role_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        return false;
    }

    // Cannot edit system roles unless you're at the same or higher level
    return $my_level >= $row['hierarchy_level'];
}

/**
 * Get the current admin user's display name.
 */
function rbac_display_name(): string
{
    return $_SESSION['admin_full_name'] ?? $_SESSION['admin_username'] ?? 'Admin';
}

/**
 * Get the current admin user's role name.
 */
function rbac_role_name(): string
{
    return $_SESSION['admin_role_name'] ?? 'Unknown';
}
