<?php
/**
 * AJAX handlers for RBAC management operations.
 * Called from settings.php via fetch() requests.
 */
include 'includes/auth.php';
include 'includes/db.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    // ══════════════════════════════════════════════════════════════
    //  ROLES
    // ══════════════════════════════════════════════════════════════

    case 'create_role':
        if (!has_permission('roles.manage')) { deny(); }
        csrf_require();
        $name  = trim($_POST['name'] ?? '');
        $desc  = trim($_POST['description'] ?? '');
        $level = intval($_POST['hierarchy_level'] ?? 10);

        if ($name === '') { fail('Role name is required.'); }
        if ($level >= ($_SESSION['admin_hierarchy'] ?? 0)) {
            fail('You cannot create a role at or above your own hierarchy level.');
        }

        $stmt = $conn->prepare("INSERT INTO roles (name, description, hierarchy_level) VALUES (?, ?, ?)");
        $stmt->bind_param('ssi', $name, $desc, $level);
        if ($stmt->execute()) {
            audit_log($conn, 'role.create', 'role:' . $stmt->insert_id, "Created role: $name");
            ok(['id' => $stmt->insert_id, 'message' => "Role '$name' created."]);
        } else {
            fail('Failed to create role. Name may already exist.');
        }
        $stmt->close();
        break;

    case 'update_role':
        if (!has_permission('roles.manage')) { deny(); }
        csrf_require();
        $id    = intval($_POST['role_id'] ?? 0);
        $name  = trim($_POST['name'] ?? '');
        $desc  = trim($_POST['description'] ?? '');
        $level = intval($_POST['hierarchy_level'] ?? 10);

        if ($id <= 0 || $name === '') { fail('Invalid data.'); }
        if (!can_manage_role($conn, $id)) { fail('You cannot edit this role.'); }

        // Don't allow renaming system roles
        $sys_check = $conn->query("SELECT is_system FROM roles WHERE id = $id")->fetch_assoc();
        if ($sys_check && $sys_check['is_system']) {
            // Allow description change but not name/level
            $stmt = $conn->prepare("UPDATE roles SET description = ? WHERE id = ?");
            $stmt->bind_param('si', $desc, $id);
        } else {
            if ($level >= ($_SESSION['admin_hierarchy'] ?? 0)) {
                fail('Cannot set hierarchy at or above your own level.');
            }
            $stmt = $conn->prepare("UPDATE roles SET name = ?, description = ?, hierarchy_level = ? WHERE id = ?");
            $stmt->bind_param('ssii', $name, $desc, $level, $id);
        }
        if ($stmt->execute()) {
            audit_log($conn, 'role.update', "role:$id", "Updated role: $name");
            ok(['message' => "Role updated."]);
        } else {
            fail('Failed to update role.');
        }
        $stmt->close();
        break;

    case 'delete_role':
        if (!has_permission('roles.manage')) { deny(); }
        csrf_require();
        $id = intval($_POST['role_id'] ?? 0);
        if ($id <= 0) { fail('Invalid role.'); }
        if (!can_manage_role($conn, $id)) { fail('You cannot delete this role.'); }

        // Check if system role
        $r = $conn->query("SELECT name, is_system FROM roles WHERE id = $id")->fetch_assoc();
        if (!$r) { fail('Role not found.'); }
        if ($r['is_system']) { fail('System roles cannot be deleted.'); }

        // Check if any users are assigned
        $uc = $conn->query("SELECT COUNT(*) as cnt FROM admin_users WHERE role_id = $id")->fetch_assoc();
        if ($uc['cnt'] > 0) { fail('Cannot delete — ' . $uc['cnt'] . ' user(s) are assigned to this role. Reassign them first.'); }

        $conn->query("DELETE FROM roles WHERE id = $id AND is_system = 0");
        audit_log($conn, 'role.delete', "role:$id", "Deleted role: {$r['name']}");
        ok(['message' => "Role '{$r['name']}' deleted."]);
        break;

    case 'get_roles':
        if (!has_permission('roles.view')) { deny(); }
        $roles = [];
        $result = $conn->query("SELECT r.*, (SELECT COUNT(*) FROM admin_users WHERE role_id = r.id) as user_count FROM roles r ORDER BY hierarchy_level DESC");
        while ($row = $result->fetch_assoc()) {
            $roles[] = $row;
        }
        ok(['roles' => $roles]);
        break;

    // ══════════════════════════════════════════════════════════════
    //  PERMISSIONS
    // ══════════════════════════════════════════════════════════════

    case 'get_role_permissions':
        if (!has_permission('roles.view')) { deny(); }
        $role_id = intval($_GET['role_id'] ?? 0);
        if ($role_id <= 0) { fail('Invalid role.'); }

        // All permissions grouped
        $all = [];
        $res = $conn->query("SELECT * FROM permissions ORDER BY group_name, perm_key");
        while ($r = $res->fetch_assoc()) {
            $all[] = $r;
        }

        // Assigned permission IDs
        $assigned = [];
        $res2 = $conn->query("SELECT permission_id FROM role_permissions WHERE role_id = $role_id");
        while ($r2 = $res2->fetch_assoc()) {
            $assigned[] = (int)$r2['permission_id'];
        }

        ok(['permissions' => $all, 'assigned' => $assigned]);
        break;

    case 'save_role_permissions':
        if (!has_permission('roles.manage')) { deny(); }
        csrf_require();
        $role_id = intval($_POST['role_id'] ?? 0);
        if ($role_id <= 0) { fail('Invalid role.'); }
        if (!can_manage_role($conn, $role_id)) { fail('You cannot edit permissions for this role.'); }

        $perm_ids = json_decode($_POST['permission_ids'] ?? '[]', true);
        if (!is_array($perm_ids)) $perm_ids = [];
        $perm_ids = array_map('intval', $perm_ids);

        // Replace all permissions
        $conn->query("DELETE FROM role_permissions WHERE role_id = $role_id");
        $ins = $conn->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
        foreach ($perm_ids as $pid) {
            $ins->bind_param('ii', $role_id, $pid);
            $ins->execute();
        }
        $ins->close();

        audit_log($conn, 'role.permissions_updated', "role:$role_id", count($perm_ids) . ' permissions assigned');
        ok(['message' => 'Permissions saved.']);
        break;

    // ══════════════════════════════════════════════════════════════
    //  ADMIN USERS
    // ══════════════════════════════════════════════════════════════

    case 'get_admin_users':
        if (!has_permission('admin_users.view')) { deny(); }
        $users = [];
        $result = $conn->query("
            SELECT u.id, u.username, u.email, u.full_name, u.role_id, u.status, u.last_login, u.created_at,
                   r.name as role_name, r.hierarchy_level
            FROM admin_users u
            JOIN roles r ON r.id = u.role_id
            ORDER BY r.hierarchy_level DESC, u.username ASC
        ");
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        ok(['users' => $users]);
        break;

    case 'create_admin_user':
        if (!has_permission('admin_users.manage')) { deny(); }
        csrf_require();
        $username  = trim($_POST['username'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $full_name = trim($_POST['full_name'] ?? '');
        $password  = $_POST['password'] ?? '';
        $role_id   = intval($_POST['role_id'] ?? 0);

        if ($username === '' || $email === '' || $password === '' || $role_id <= 0) {
            fail('All fields are required.');
        }
        if (strlen($password) < 6) { fail('Password must be at least 6 characters.'); }
        if (!can_manage_role($conn, $role_id)) { fail('You cannot assign this role.'); }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $created_by = $_SESSION['admin_user_id'];
        $stmt = $conn->prepare("INSERT INTO admin_users (username, email, password_hash, full_name, role_id, created_by) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('ssssii', $username, $email, $hash, $full_name, $role_id, $created_by);
        if ($stmt->execute()) {
            audit_log($conn, 'user.create', 'user:' . $stmt->insert_id, "Created user: $username");
            ok(['message' => "User '$username' created."]);
        } else {
            fail('Failed to create user. Username or email may already exist.');
        }
        $stmt->close();
        break;

    case 'update_admin_user':
        if (!has_permission('admin_users.manage')) { deny(); }
        csrf_require();
        $id        = intval($_POST['user_id'] ?? 0);
        $email     = trim($_POST['email'] ?? '');
        $full_name = trim($_POST['full_name'] ?? '');
        $role_id   = intval($_POST['role_id'] ?? 0);
        $status    = $_POST['status'] ?? 'active';
        $password  = $_POST['password'] ?? '';

        if ($id <= 0) { fail('Invalid user.'); }
        if (!can_manage_user($conn, $id)) { fail('You cannot edit this user.'); }
        if ($role_id > 0 && !can_manage_role($conn, $role_id)) { fail('You cannot assign this role.'); }

        if ($password !== '' && strlen($password) < 6) { fail('Password must be at least 6 characters.'); }

        if ($password !== '') {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE admin_users SET email=?, full_name=?, role_id=?, status=?, password_hash=? WHERE id=?");
            $stmt->bind_param('ssissi', $email, $full_name, $role_id, $status, $hash, $id);
        } else {
            $stmt = $conn->prepare("UPDATE admin_users SET email=?, full_name=?, role_id=?, status=? WHERE id=?");
            $stmt->bind_param('ssisi', $email, $full_name, $role_id, $status, $id);
        }
        if ($stmt->execute()) {
            audit_log($conn, 'user.update', "user:$id", "Updated user fields");
            ok(['message' => 'User updated.']);
        } else {
            fail('Failed to update user.');
        }
        $stmt->close();
        break;

    case 'delete_admin_user':
        if (!has_permission('admin_users.manage')) { deny(); }
        csrf_require();
        $id = intval($_POST['user_id'] ?? 0);
        if ($id <= 0) { fail('Invalid user.'); }
        if ($id === ($_SESSION['admin_user_id'] ?? 0)) { fail('You cannot delete your own account.'); }
        if (!can_manage_user($conn, $id)) { fail('You cannot delete this user.'); }

        $u = $conn->query("SELECT username FROM admin_users WHERE id = $id")->fetch_assoc();
        $conn->query("DELETE FROM admin_users WHERE id = $id");
        audit_log($conn, 'user.delete', "user:$id", "Deleted user: " . ($u['username'] ?? ''));
        ok(['message' => 'User deleted.']);
        break;

    // ══════════════════════════════════════════════════════════════
    //  INVITE CODES
    // ══════════════════════════════════════════════════════════════

    case 'get_invite_codes':
        if (!has_permission('invite_codes.manage')) { deny(); }
        $codes = [];
        $result = $conn->query("
            SELECT ic.*, r.name as role_name, au.username as created_by_name
            FROM invite_codes ic
            JOIN roles r ON r.id = ic.role_id
            LEFT JOIN admin_users au ON au.id = ic.created_by
            ORDER BY ic.created_at DESC
        ");
        while ($row = $result->fetch_assoc()) {
            $codes[] = $row;
        }
        ok(['codes' => $codes]);
        break;

    case 'create_invite_code':
        if (!has_permission('invite_codes.manage')) { deny(); }
        csrf_require();
        $role_id    = intval($_POST['role_id'] ?? 0);
        $max_uses   = intval($_POST['max_uses'] ?? 1);
        $expires_in = intval($_POST['expires_hours'] ?? 48); // hours

        if ($role_id <= 0) { fail('Select a role.'); }
        if (!can_manage_role($conn, $role_id)) { fail('You cannot create invites for this role.'); }

        $code = strtoupper(bin2hex(random_bytes(4))); // 8-char hex code
        $expires_at = date('Y-m-d H:i:s', time() + ($expires_in * 3600));
        $created_by = $_SESSION['admin_user_id'];

        $stmt = $conn->prepare("INSERT INTO invite_codes (code, role_id, max_uses, expires_at, created_by) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param('siisi', $code, $role_id, $max_uses, $expires_at, $created_by);
        if ($stmt->execute()) {
            audit_log($conn, 'invite.create', "code:$code", "Role: $role_id, Max uses: $max_uses");
            ok(['message' => 'Invite code created.', 'code' => $code]);
        } else {
            fail('Failed to create invite code.');
        }
        $stmt->close();
        break;

    case 'deactivate_invite':
        if (!has_permission('invite_codes.manage')) { deny(); }
        csrf_require();
        $id = intval($_POST['invite_id'] ?? 0);
        if ($id <= 0) { fail('Invalid invite.'); }
        $conn->query("UPDATE invite_codes SET is_active = 0 WHERE id = $id");
        audit_log($conn, 'invite.deactivate', "invite:$id");
        ok(['message' => 'Invite code deactivated.']);
        break;

    // ══════════════════════════════════════════════════════════════
    //  AUDIT LOG
    // ══════════════════════════════════════════════════════════════

    case 'get_audit_log':
        if (!has_permission('audit.view')) { deny(); }
        $page   = max(1, intval($_GET['page'] ?? 1));
        $limit  = 50;
        $offset = ($page - 1) * $limit;

        $total = $conn->query("SELECT COUNT(*) as cnt FROM audit_log")->fetch_assoc()['cnt'];
        $rows = [];
        $result = $conn->query("SELECT * FROM audit_log ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
        while ($r = $result->fetch_assoc()) {
            $rows[] = $r;
        }
        ok(['logs' => $rows, 'total' => (int)$total, 'page' => $page, 'per_page' => $limit]);
        break;

    default:
        fail('Unknown action.');
}

// ── Response helpers ─────────────────────────────────────────────

function ok(array $data = []): void
{
    echo json_encode(array_merge(['success' => true], $data));
    exit();
}

function fail(string $msg): void
{
    echo json_encode(['success' => false, 'message' => $msg]);
    exit();
}

function deny(): void
{
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Permission denied.']);
    exit();
}
