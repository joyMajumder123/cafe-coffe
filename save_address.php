<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['customer_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in first.']);
    exit;
}

require_once __DIR__ . '/admin/includes/db.php';
require_once __DIR__ . '/includes/customer_meta.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid method.']);
    exit;
}

ensure_customer_meta_tables($conn);

$customer_id = (int) $_SESSION['customer_id'];
$address_id = isset($_POST['address_id']) ? (int) $_POST['address_id'] : 0;
$label = trim($_POST['label'] ?? '');
$line1 = trim($_POST['address_line1'] ?? '');
$line2 = trim($_POST['address_line2'] ?? '');
$city = trim($_POST['city'] ?? '');
$state = trim($_POST['state'] ?? '');
$postal = trim($_POST['postal_code'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$is_default = isset($_POST['is_default']) ? (int) !!$_POST['is_default'] : 0;

if ($label === '' || $line1 === '' || $city === '') {
    echo json_encode(['success' => false, 'message' => 'Label, address, and city are required.']);
    exit;
}

if ($address_id > 0) {
    $stmt = $conn->prepare("UPDATE customer_addresses SET label = ?, address_line1 = ?, address_line2 = ?, city = ?, state = ?, postal_code = ?, phone = ?, is_default = ? WHERE id = ? AND customer_id = ?");
    $stmt->bind_param('sssssssiii', $label, $line1, $line2, $city, $state, $postal, $phone, $is_default, $address_id, $customer_id);
    $success = $stmt->execute();
    $stmt->close();
} else {
    $stmt = $conn->prepare("INSERT INTO customer_addresses (customer_id, label, address_line1, address_line2, city, state, postal_code, phone, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('isssssssi', $customer_id, $label, $line1, $line2, $city, $state, $postal, $phone, $is_default);
    $success = $stmt->execute();
    $address_id = $stmt->insert_id;
    $stmt->close();
}

if ($success && $is_default) {
    set_default_customer_address($conn, $customer_id, $address_id);
} elseif ($success) {
    ensure_address_has_default($conn, $customer_id);
}

$addresses = fetch_customer_addresses($conn, $customer_id);

echo json_encode([
    'success' => $success,
    'addresses' => $addresses,
]);
exit;
