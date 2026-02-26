<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'admin/includes/db.php';
require_once 'admin/includes/rbac/csrf.php';

header('Content-Type: application/json');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit();
}

// CSRF validation
if (!csrf_validate()) {
    echo json_encode(['success' => false, 'message' => 'Security token mismatch. Please reload the page and try again.']);
    exit();
}

$customer_name = trim($_POST['customer_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$address = trim($_POST['address'] ?? '');
$city = trim($_POST['city'] ?? '');
$items = $_POST['items'] ?? '[]';
$delivery_charge = floatval($_POST['delivery_charge'] ?? 0);
$tax_rate = 0.05; // 5% tax — single source of truth
$customer_id = isset($_SESSION['customer_id']) ? intval($_SESSION['customer_id']) : 0;

if ($customer_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Please login to place an order.']);
    exit();
}

if ($customer_name === '' || $email === '' || $phone === '') {
    echo json_encode(['success' => false, 'message' => 'Missing required order data.']);
    exit();
}

$decoded_items = json_decode($items, true);
if (!is_array($decoded_items) || empty($decoded_items)) {
    echo json_encode(['success' => false, 'message' => 'Cart is empty.']);
    exit();
}

// ── Server-side price validation ────────────────────────────────
// Collect all item IDs from the cart, look up real prices from the DB
$item_ids = [];
foreach ($decoded_items as $cart_item) {
    $id = isset($cart_item['id']) ? (int) $cart_item['id'] : 0;
    if ($id > 0) {
        $item_ids[] = $id;
    }
}

if (empty($item_ids)) {
    echo json_encode(['success' => false, 'message' => 'Invalid cart items.']);
    exit();
}

// Fetch real prices from DB
$placeholders = implode(',', array_fill(0, count($item_ids), '?'));
$types = str_repeat('i', count($item_ids));
$price_stmt = $conn->prepare("SELECT id, price FROM menu_items WHERE id IN ($placeholders) AND status = 'active'");
$price_stmt->bind_param($types, ...$item_ids);
$price_stmt->execute();
$price_result = $price_stmt->get_result();
$db_prices = [];
while ($row = $price_result->fetch_assoc()) {
    $db_prices[(int) $row['id']] = (float) $row['price'];
}
$price_stmt->close();

// Recalculate subtotal from DB prices
$subtotal = 0.0;
$validated_items = [];
foreach ($decoded_items as $cart_item) {
    $id = (int) ($cart_item['id'] ?? 0);
    $quantity = (int) ($cart_item['quantity'] ?? 1);
    if ($quantity <= 0) {
        $quantity = 1;
    }
    if (!isset($db_prices[$id])) {
        echo json_encode(['success' => false, 'message' => 'One or more items are no longer available.']);
        exit();
    }
    $real_price = $db_prices[$id];
    $subtotal += $real_price * $quantity;
    $validated_items[] = [
        'id' => $id,
        'name' => $cart_item['name'] ?? '',
        'price' => $real_price,
        'quantity' => $quantity,
    ];
}

$items = json_encode($validated_items);
$tax = round($subtotal * $tax_rate, 2);
$total_amount = round($subtotal + $delivery_charge + $tax, 2);

if ($total_amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Order total must be greater than zero.']);
    exit();
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
