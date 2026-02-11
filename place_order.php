<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'admin/includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit();
}

$customer_name = trim($_POST['customer_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$address = trim($_POST['address'] ?? '');
$city = trim($_POST['city'] ?? '');
$items = $_POST['items'] ?? '[]';
$subtotal = floatval($_POST['subtotal'] ?? 0);
$delivery_charge = floatval($_POST['delivery_charge'] ?? 0);
$tax = floatval($_POST['tax'] ?? 0);
$total_amount = floatval($_POST['total_amount'] ?? 0);
$customer_id = isset($_SESSION['customer_id']) ? intval($_SESSION['customer_id']) : 0;

if ($customer_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Please login to place an order.']);
    exit();
}

if ($customer_name === '' || $email === '' || $phone === '' || $total_amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Missing required order data.']);
    exit();
}

$decoded_items = json_decode($items, true);
if (!is_array($decoded_items)) {
    $items = '[]';
}

if ($subtotal <= 0) {
    $subtotal = $total_amount;
}
if ($total_amount <= 0) {
    $total_amount = $subtotal + $delivery_charge + $tax;
}
$payment_method = trim($_POST['payment_method'] ?? 'cash_on_delivery');
$status = 'pending';

$stmt = $conn->prepare("INSERT INTO orders (customer_id, customer_name, email, phone, address, city, items, subtotal, delivery_charge, tax, total_amount, payment_method, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error.']);
    exit();
}

$stmt->bind_param(
    'issssssddddss',
    $customer_id,
    $customer_name,
    $email,
    $phone,
    $address,
    $city,
    $items,
    $subtotal,
    $delivery_charge,
    $tax,
    $total_amount,
    $payment_method,
    $status
);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'order_id' => $stmt->insert_id, 'status' => $status]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to place order.']);
}

$stmt->close();
?>
