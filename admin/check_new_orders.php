<?php
/**
 * AJAX endpoint for checking new orders
 * Called periodically by the admin notification system
 */
include 'includes/auth.php';
include 'includes/db.php';

header('Content-Type: application/json');
header('Cache-Control: no-store');

// Get the last known order ID from the client
$last_order_id = intval($_GET['last_order_id'] ?? 0);

$response = [
    'new_orders' => [],
    'latest_order_id' => $last_order_id,
    'pending_count' => 0
];

// Get count of all pending orders
$pending_stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM orders WHERE status = 'pending'");
$pending_stmt->execute();
$response['pending_count'] = $pending_stmt->get_result()->fetch_assoc()['cnt'] ?? 0;
$pending_stmt->close();

// If we have a last_order_id, fetch any orders newer than that
if ($last_order_id > 0) {
    $stmt = $conn->prepare("SELECT id, customer_name, total_amount, status, created_at FROM orders WHERE id > ? ORDER BY id ASC LIMIT 20");
    $stmt->bind_param('i', $last_order_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $response['new_orders'][] = [
            'id' => (int)$row['id'],
            'customer_name' => $row['customer_name'],
            'total_amount' => number_format((float)$row['total_amount'], 2),
            'status' => $row['status'],
            'created_at' => $row['created_at']
        ];
        $response['latest_order_id'] = (int)$row['id'];
    }
    $stmt->close();
} else {
    // First load — just get the latest order ID so we know where to start
    $result = $conn->query("SELECT MAX(id) as max_id FROM orders");
    $row = $result->fetch_assoc();
    $response['latest_order_id'] = (int)($row['max_id'] ?? 0);
}

echo json_encode($response);
