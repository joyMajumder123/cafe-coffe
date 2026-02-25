<?php
/**
 * RBAC Database Tables Migration
 * Auto-creates all role-based access control tables.
 * Safe to call multiple times (CREATE IF NOT EXISTS + column checks).
 */

function rbac_run_migrations(mysqli $conn): void
{
    $db_name = DB_NAME;

    // ── 1. Roles ─────────────────────────────────────────────────────
    $conn->query("CREATE TABLE IF NOT EXISTS `roles` (
        `id`              INT AUTO_INCREMENT PRIMARY KEY,
        `name`            VARCHAR(60)  NOT NULL UNIQUE,
        `description`     VARCHAR(255) DEFAULT '',
        `hierarchy_level` INT          NOT NULL DEFAULT 0,
        `is_system`       TINYINT(1)   NOT NULL DEFAULT 0,
        `created_at`      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
        `updated_at`      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // ── 2. Permissions ───────────────────────────────────────────────
    $conn->query("CREATE TABLE IF NOT EXISTS `permissions` (
        `id`          INT AUTO_INCREMENT PRIMARY KEY,
        `perm_key`    VARCHAR(80)  NOT NULL UNIQUE,
        `label`       VARCHAR(120) NOT NULL,
        `group_name`  VARCHAR(60)  NOT NULL DEFAULT 'General',
        `description` VARCHAR(255) DEFAULT '',
        `created_at`  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
        `updated_at`  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // ── 3. Role ↔ Permission junction ────────────────────────────────
    $conn->query("CREATE TABLE IF NOT EXISTS `role_permissions` (
        `role_id`       INT NOT NULL,
        `permission_id` INT NOT NULL,
        `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`role_id`, `permission_id`),
        FOREIGN KEY (`role_id`)       REFERENCES `roles`(`id`)       ON DELETE CASCADE,
        FOREIGN KEY (`permission_id`) REFERENCES `permissions`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // ── 4. Admin Users ───────────────────────────────────────────────
    $conn->query("CREATE TABLE IF NOT EXISTS `admin_users` (
        `id`              INT AUTO_INCREMENT PRIMARY KEY,
        `username`        VARCHAR(60)  NOT NULL UNIQUE,
        `email`           VARCHAR(120) NOT NULL UNIQUE,
        `password_hash`   VARCHAR(255) NOT NULL,
        `full_name`       VARCHAR(120) NOT NULL DEFAULT '',
        `role_id`         INT          NOT NULL,
        `status`          ENUM('active','inactive','locked') NOT NULL DEFAULT 'active',
        `login_attempts`  INT          NOT NULL DEFAULT 0,
        `last_attempt_at` DATETIME     NULL,
        `last_login`      DATETIME     NULL,
        `created_by`      INT          NULL,
        `created_at`      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
        `updated_at`      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`role_id`)    REFERENCES `roles`(`id`) ON DELETE RESTRICT,
        FOREIGN KEY (`created_by`) REFERENCES `admin_users`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // ── 5. Invite Codes ──────────────────────────────────────────────
    $conn->query("CREATE TABLE IF NOT EXISTS `invite_codes` (
        `id`          INT AUTO_INCREMENT PRIMARY KEY,
        `code`        VARCHAR(64)  NOT NULL UNIQUE,
        `role_id`     INT          NOT NULL,
        `max_uses`    INT          NOT NULL DEFAULT 1,
        `times_used`  INT          NOT NULL DEFAULT 0,
        `expires_at`  DATETIME     NULL,
        `is_active`   TINYINT(1)   NOT NULL DEFAULT 1,
        `created_by`  INT          NULL,
        `created_at`  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`role_id`)    REFERENCES `roles`(`id`)       ON DELETE CASCADE,
        FOREIGN KEY (`created_by`) REFERENCES `admin_users`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // ── 6. Audit Log ─────────────────────────────────────────────────
    $conn->query("CREATE TABLE IF NOT EXISTS `audit_log` (
        `id`          BIGINT AUTO_INCREMENT PRIMARY KEY,
        `user_id`     INT          NULL,
        `username`    VARCHAR(60)  NOT NULL DEFAULT 'system',
        `action`      VARCHAR(100) NOT NULL,
        `target`      VARCHAR(100) DEFAULT NULL,
        `details`     TEXT         NULL,
        `ip_address`  VARCHAR(45)  NOT NULL DEFAULT '',
        `created_at`  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_audit_user`    (`user_id`),
        INDEX `idx_audit_action`  (`action`),
        INDEX `idx_audit_created` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // ── 7. Login Attempts (rate limiting) ────────────────────────────
    $conn->query("CREATE TABLE IF NOT EXISTS `login_attempts` (
        `id`         INT AUTO_INCREMENT PRIMARY KEY,
        `ip_address` VARCHAR(45)  NOT NULL,
        `username`   VARCHAR(60)  NOT NULL DEFAULT '',
        `success`    TINYINT(1)   NOT NULL DEFAULT 0,
        `attempted_at` TIMESTAMP  DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_attempts_ip` (`ip_address`, `attempted_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // ── Seed default data ────────────────────────────────────────────
    rbac_seed_defaults($conn);
}

/**
 * Seeds default roles, permissions, and the initial Super Admin account.
 * Idempotent — skips if data already exists.
 */
function rbac_seed_defaults(mysqli $conn): void
{
    // ── Default Roles ────────────────────────────────────────────────
    $default_roles = [
        ['Super Admin', 'Full system access. Cannot be deleted.', 100, 1],
        ['Manager',     'Manages day-to-day operations.',        70,  0],
        ['Staff',       'Basic view access for daily tasks.',    30,  0],
    ];
    foreach ($default_roles as [$name, $desc, $level, $is_sys]) {
        $check = $conn->prepare("SELECT id FROM roles WHERE name = ?");
        $check->bind_param('s', $name);
        $check->execute();
        if ($check->get_result()->num_rows === 0) {
            $ins = $conn->prepare("INSERT INTO roles (name, description, hierarchy_level, is_system) VALUES (?, ?, ?, ?)");
            $ins->bind_param('ssii', $name, $desc, $level, $is_sys);
            $ins->execute();
            $ins->close();
        }
        $check->close();
    }

    // ── Default Permissions ──────────────────────────────────────────
    $perms = rbac_get_permissions_list();
    foreach ($perms as $group => $group_perms) {
        foreach ($group_perms as $key => $label) {
            $check = $conn->prepare("SELECT id FROM permissions WHERE perm_key = ?");
            $check->bind_param('s', $key);
            $check->execute();
            if ($check->get_result()->num_rows === 0) {
                $ins = $conn->prepare("INSERT INTO permissions (perm_key, label, group_name) VALUES (?, ?, ?)");
                $ins->bind_param('sss', $key, $label, $group);
                $ins->execute();
                $ins->close();
            }
            $check->close();
        }
    }

    // ── Assign ALL permissions to Super Admin ────────────────────────
    $sa_role = $conn->query("SELECT id FROM roles WHERE name = 'Super Admin' LIMIT 1")->fetch_assoc();
    if ($sa_role) {
        $all_perms = $conn->query("SELECT id FROM permissions");
        while ($p = $all_perms->fetch_assoc()) {
            $conn->query("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES ({$sa_role['id']}, {$p['id']})");
        }
    }

    // ── Assign Manager permissions ───────────────────────────────────
    $mgr_role = $conn->query("SELECT id FROM roles WHERE name = 'Manager' LIMIT 1")->fetch_assoc();
    if ($mgr_role) {
        $mgr_exclude = ['roles.manage', 'admin_users.manage', 'invite_codes.manage', 'audit.view'];
        $placeholders = implode(',', array_fill(0, count($mgr_exclude), '?'));
        $stmt = $conn->prepare("SELECT id FROM permissions WHERE perm_key NOT IN ($placeholders)");
        $types = str_repeat('s', count($mgr_exclude));
        $stmt->bind_param($types, ...$mgr_exclude);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($p = $result->fetch_assoc()) {
            $conn->query("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES ({$mgr_role['id']}, {$p['id']})");
        }
        $stmt->close();
    }

    // ── Assign Staff permissions ─────────────────────────────────────
    $staff_role = $conn->query("SELECT id FROM roles WHERE name = 'Staff' LIMIT 1")->fetch_assoc();
    if ($staff_role) {
        $staff_perms = ['dashboard.view', 'orders.view', 'reservations.view', 'menu.view', 'categories.view'];
        foreach ($staff_perms as $pk) {
            $p = $conn->query("SELECT id FROM permissions WHERE perm_key = '" . $conn->real_escape_string($pk) . "' LIMIT 1")->fetch_assoc();
            if ($p) {
                $conn->query("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES ({$staff_role['id']}, {$p['id']})");
            }
        }
    }

    // ── Seed Super Admin user (backward compat with hardcoded login) ─
    $check = $conn->prepare("SELECT id FROM admin_users WHERE username = 'admin'");
    $check->execute();
    if ($check->get_result()->num_rows === 0 && $sa_role) {
        $hash = password_hash('123', PASSWORD_DEFAULT);
        $username = 'admin';
        $email = 'admin@cafe.local';
        $fullname = 'Administrator';
        $role_id = $sa_role['id'];
        $ins = $conn->prepare("INSERT INTO admin_users (username, email, password_hash, full_name, role_id) VALUES (?, ?, ?, ?, ?)");
        $ins->bind_param('ssssi', $username, $email, $hash, $fullname, $role_id);
        $ins->execute();
        $ins->close();
    }
    $check->close();
}

/**
 * Returns the master permissions list grouped by module.
 */
function rbac_get_permissions_list(): array
{
    return [
        'Dashboard' => [
            'dashboard.view' => 'View Dashboard',
        ],
        'Orders' => [
            'orders.view'   => 'View Orders',
            'orders.manage' => 'Manage Orders (update status, cancel)',
        ],
        'Menu' => [
            'menu.view'   => 'View Menu Items',
            'menu.manage' => 'Manage Menu (add, edit, delete)',
        ],
        'Categories' => [
            'categories.view'   => 'View Categories',
            'categories.manage' => 'Manage Categories',
        ],
        'Reservations' => [
            'reservations.view'   => 'View Reservations',
            'reservations.manage' => 'Manage Reservations',
        ],
        'Staff' => [
            'staff.view'   => 'View Staff',
            'staff.manage' => 'Manage Staff (add, edit, delete)',
        ],
        'Inquiries' => [
            'inquiries.view'   => 'View Inquiries',
            'inquiries.manage' => 'Manage Inquiries',
            'inquiries.delete' => 'Delete Inquiries',
        ],
        'Reports' => [
            'reports.view'   => 'View Reports',
            'reports.export' => 'Export Reports',
        ],
        'Access Control' => [
            'roles.view'          => 'View Roles',
            'roles.manage'        => 'Manage Roles & Permissions',
            'admin_users.view'    => 'View Admin Users',
            'admin_users.manage'  => 'Manage Admin Users',
            'invite_codes.manage' => 'Manage Invite Codes',
        ],
        'Audit' => [
            'audit.view' => 'View Audit Log',
        ],
    ];
}
