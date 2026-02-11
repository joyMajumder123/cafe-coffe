<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (empty($_SESSION['customer_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in first.']);
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
$address_id = isset($_POST['address_id']) ? (int) $_POST['address_id'] : 0;

if ($address_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid address id.']);
    exit;
}

$success = set_default_customer_address($conn, $customer_id, $address_id);
$addresses = fetch_customer_addresses($conn, $customer_id);

echo json_encode([
    'success' => $success,
    'addresses' => $addresses,
]);
exit;
