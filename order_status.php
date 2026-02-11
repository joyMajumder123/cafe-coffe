<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (empty($_SESSION['customer_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/admin/includes/db.php';

$customer_id = (int) $_SESSION['customer_id'];

$stmt = $conn->prepare("SELECT id, status, created_at FROM orders WHERE customer_id = ? ORDER BY created_at DESC");
$stmt->bind_param('i', $customer_id);
$stmt->execute();
$result = $stmt->get_result();

$orders = [];
while ($row = $result->fetch_assoc()) {
    $orders[] = [
        'id' => (int) ($row['id'] ?? 0),
        'status' => strtolower($row['status'] ?? ''),
        'created_at' => $row['created_at'] ?? null,
    ];
}

$stmt->close();

echo json_encode(['orders' => $orders]);
exit;
