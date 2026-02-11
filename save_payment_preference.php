<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (empty($_SESSION['customer_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid method.']);
    exit;
}

require_once __DIR__ . '/admin/includes/db.php';
require_once __DIR__ . '/includes/customer_meta.php';

ensure_customer_meta_tables($conn);

$customer_id = (int) $_SESSION['customer_id'];
$method = trim($_POST['payment_method'] ?? '');

if ($method === '') {
    echo json_encode(['success' => false, 'message' => 'Payment method is required.']);
    exit;
}

$allowed = ['cash', 'card', 'upi'];
if (!in_array($method, $allowed, true)) {
    echo json_encode(['success' => false, 'message' => 'Unsupported payment method.']);
    exit;
}

$success = save_customer_payment_preference($conn, $customer_id, $method);

echo json_encode(['success' => $success, 'payment_method' => $method]);
exit;
