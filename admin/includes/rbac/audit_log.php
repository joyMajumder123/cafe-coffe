<?php
/**
 * Audit Log Helper
 * Records administrative actions for accountability and debugging.
 */

/**
 * Write an entry to the audit log.
 *
 * @param mysqli $conn    Database connection
 * @param string $action  Short action label (e.g. 'role.create', 'user.delete')
 * @param string $target  What was acted upon (e.g. 'role:5', 'user:admin')
 * @param string $details Free-text detail or JSON
 */
function audit_log(mysqli $conn, string $action, string $target = '', string $details = ''): void
{
    $user_id  = $_SESSION['admin_user_id'] ?? null;
    $username = $_SESSION['admin_username'] ?? 'system';
    $ip       = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    // Handle IPv6-mapped IPv4
    if (substr($ip, 0, 7) === '::ffff:') {
        $ip = substr($ip, 7);
    }

    $stmt = $conn->prepare("
        INSERT INTO audit_log (user_id, username, action, target, details, ip_address)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param('isssss', $user_id, $username, $action, $target, $details, $ip);
    $stmt->execute();
    $stmt->close();
}
