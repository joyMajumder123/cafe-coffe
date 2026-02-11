<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['customer_id'])) {
    header('Location: customer_login.php?redirect=profile.php');
    exit();
}

include 'admin/includes/db.php';
include 'includes/user_layout.php';

$customer_id = (int) $_SESSION['customer_id'];
$profile_notice = '';
$profile_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($name === '' || $email === '') {
        $profile_error = 'Name and email are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $profile_error = 'Invalid email address.';
    } elseif ($password !== '' && $password !== $confirm) {
        $profile_error = 'Passwords do not match.';
    }

    if ($profile_error === '') {
        $stmt = $conn->prepare("SELECT id FROM customers WHERE email = ? AND id <> ? LIMIT 1");
        $stmt->bind_param('si', $email, $customer_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $profile_error = 'This email is already in use.';
        }
        $stmt->close();
    }

    if ($profile_error === '') {
        if ($password !== '') {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE customers SET name = ?, email = ?, phone = ?, password_hash = ? WHERE id = ?");
            $stmt->bind_param('ssssi', $name, $email, $phone, $password_hash, $customer_id);
        } else {
            $stmt = $conn->prepare("UPDATE customers SET name = ?, email = ?, phone = ? WHERE id = ?");
            $stmt->bind_param('sssi', $name, $email, $phone, $customer_id);
        }

        if ($stmt->execute()) {
            $_SESSION['customer_name'] = $name;
            $_SESSION['customer_email'] = $email;
            $profile_notice = 'Profile updated successfully.';
        } else {
            $profile_error = 'Failed to update profile.';
        }
        $stmt->close();
    }
}

$stmt = $conn->prepare("SELECT id, name, email, phone, created_at FROM customers WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $customer_id);
$stmt->execute();
$customer = $stmt->get_result()->fetch_assoc();
$stmt->close();

$order_stmt = $conn->prepare("SELECT * FROM orders WHERE customer_id = ? ORDER BY created_at DESC");
$order_stmt->bind_param('i', $customer_id);
$order_stmt->execute();
$orders_result = $order_stmt->get_result();
?>

<section class="py-5 user-page">
    <div class="container" style="margin-top: 50px;">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card user-card">
                    <div class="card-header user-card-header">
                        <h5 class="mb-0">My Profile</h5>
                        <span class="text-gold small">Account overview</span>
                    </div>
                    <div class="card-body">
                        <?php if ($profile_notice): ?>
                            <div class="alert alert-success"><?php echo htmlspecialchars($profile_notice); ?></div>
                        <?php elseif ($profile_error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($profile_error); ?></div>
                        <?php endif; ?>
                        <div class="mb-2"><strong>Name:</strong> <?php echo htmlspecialchars($customer['name'] ?? ''); ?></div>
                        <div class="mb-2"><strong>Email:</strong> <?php echo htmlspecialchars($customer['email'] ?? ''); ?></div>
                        <div class="mb-2"><strong>Phone:</strong> <?php echo htmlspecialchars($customer['phone'] ?? ''); ?></div>
                        <div class="text-muted small">Member since <?php echo htmlspecialchars(date('d M Y', strtotime($customer['created_at'] ?? 'now'))); ?></div>
                        <div class="mt-3">
                            <a href="checkout.php" class="btn btn-gold w-100">Go to Checkout</a>
                        </div>
                    </div>
                </div>
                <div class="card user-card mt-4">
                    <div class="card-header user-card-header">
                        <h5 class="mb-0">Edit Profile</h5>
                        <span class="text-gold small">Update your details</span>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <input type="hidden" name="update_profile" value="1">
                            <div class="mb-3">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($customer['name'] ?? ''); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($customer['email'] ?? ''); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($customer['phone'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">New Password (optional)</label>
                                <input type="password" name="password" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Confirm Password</label>
                                <input type="password" name="confirm_password" class="form-control">
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Save Changes</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="card user-card">
                    <div class="card-header user-card-header">
                        <h5 class="mb-0">Order History</h5>
                        <span class="text-gold small">Track your orders</span>
                    </div>
                    <div class="card-body">
                        <?php if ($orders_result && $orders_result->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>Order ID</th>
                                            <th>Items</th>
                                            <th>Total</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                            <th>Details</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($order = $orders_result->fetch_assoc()): ?>
                                            <?php
                                            $items = json_decode($order['items'] ?? '[]', true);
                                            $item_summary = [];
                                            if (is_array($items)) {
                                                foreach ($items as $item) {
                                                    if (!empty($item['name'])) {
                                                        $qty = isset($item['quantity']) ? (int) $item['quantity'] : 1;
                                                        $item_summary[] = $item['name'] . ' x ' . $qty;
                                                    }
                                                }
                                            }
                                            ?>
                                            <tr>
                                                <td>#<?php echo (int) $order['id']; ?></td>
                                                <td><?php echo htmlspecialchars(implode(', ', $item_summary)); ?></td>
                                                <td>$<?php echo number_format((float) $order['total_amount'], 2); ?></td>
                                                <td>
                                                    <span class="badge status-badge status-<?php echo htmlspecialchars($order['status']); ?>">
                                                        <?php echo htmlspecialchars(ucfirst($order['status'])); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars(date('d M Y', strtotime($order['created_at']))); ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-gold" type="button" data-bs-toggle="collapse" data-bs-target="#order-<?php echo (int) $order['id']; ?>" aria-expanded="false">View</button>
                                                </td>
                                            </tr>
                                            <tr class="collapse" id="order-<?php echo (int) $order['id']; ?>">
                                                <td colspan="6">
                                                    <div class="order-detail">
                                                        <div class="row g-3">
                                                            <div class="col-md-6">
                                                                <div class="detail-label">Delivery Address</div>
                                                                <div class="detail-value"><?php echo htmlspecialchars($order['address'] ?: 'Not provided'); ?></div>
                                                                <div class="detail-value"><?php echo htmlspecialchars($order['city'] ?: ''); ?></div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div class="detail-label">Order Summary</div>
                                                                <div class="detail-value">Subtotal: $<?php echo number_format((float) $order['subtotal'], 2); ?></div>
                                                                <div class="detail-value">Delivery: $<?php echo number_format((float) $order['delivery_charge'], 2); ?></div>
                                                                <div class="detail-value">Tax: $<?php echo number_format((float) $order['tax'], 2); ?></div>
                                                            </div>
                                                            <div class="col-12">
                                                                <div class="detail-label">Items</div>
                                                                <ul class="mb-0">
                                                                    <?php if (is_array($items) && !empty($items)): ?>
                                                                        <?php foreach ($items as $item): ?>
                                                                            <li><?php echo htmlspecialchars($item['name'] ?? 'Item'); ?> x <?php echo (int) ($item['quantity'] ?? 1); ?></li>
                                                                        <?php endforeach; ?>
                                                                    <?php else: ?>
                                                                        <li>No item details found.</li>
                                                                    <?php endif; ?>
                                                                </ul>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-muted">No orders yet.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
$order_stmt->close();
include 'includes/footer.php';
?>
