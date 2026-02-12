<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['customer_id'])) {
    header('Location: customer_login.php?redirect=profile.php');
    exit();
}

include 'admin/includes/db.php';
require_once 'includes/customer_meta.php';
require_once 'includes/recommendations.php';
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
        $stmt = $conn->prepare('SELECT id FROM customers WHERE email = ? AND id <> ? LIMIT 1');
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
            $stmt = $conn->prepare('UPDATE customers SET name = ?, email = ?, phone = ?, password_hash = ? WHERE id = ?');
            $stmt->bind_param('ssssi', $name, $email, $phone, $password_hash, $customer_id);
        } else {
            $stmt = $conn->prepare('UPDATE customers SET name = ?, email = ?, phone = ? WHERE id = ?');
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

$stmt = $conn->prepare('SELECT id, name, email, phone, created_at FROM customers WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $customer_id);
$stmt->execute();
$customer = $stmt->get_result()->fetch_assoc();
$stmt->close();

$order_stmt = $conn->prepare('SELECT * FROM orders WHERE customer_id = ? ORDER BY created_at DESC');
$order_stmt->bind_param('i', $customer_id);
$order_stmt->execute();
$orders_result = $order_stmt->get_result();
$orders = $orders_result ? $orders_result->fetch_all(MYSQLI_ASSOC) : [];
$order_stmt->close();
$recommendations = fetch_customer_recommendations($conn, $orders, 4);

ensure_customer_meta_tables($conn);
ensure_address_has_default($conn, $customer_id);
$addresses = fetch_customer_addresses($conn, $customer_id);
$preferred_payment = fetch_customer_payment_preference($conn, $customer_id);

$show_edit_form = ($profile_notice !== '' || $profile_error !== '');

$status_progress_map = [
    'pending'   => 10,
    'confirmed' => 25,
    'preparing' => 55,
    'ready'     => 75,
    'completed' => 90,
    'delivered' => 100,
];
$status_title_map = [
    'pending'   => 'Pending',
    'confirmed' => 'Confirmed',
    'preparing' => 'Preparing',
    'ready'     => 'Ready',
    'completed' => 'Completed',
    'delivered' => 'Delivered',
    'cancelled' => 'Cancelled',
];
$status_detail_map = [
    'pending'   => 'Waiting for confirmation',
    'confirmed' => 'Order confirmed, starting prep',
    'preparing' => 'Kitchen is preparing your items',
    'ready'     => 'Ready for pickup or dispatch',
    'completed' => 'Completed at the counter',
    'delivered' => 'Delivered. Enjoy your meal!',
    'cancelled' => 'This order was cancelled',
];
$recommendation_reason_meta = [
    'favorite' => ['label' => 'You order this often', 'class' => 'bg-warning text-dark'],
    'category' => ['label' => 'Similar to your picks', 'class' => 'bg-info text-dark'],
    'trending' => ['label' => 'Popular this week', 'class' => 'bg-secondary text-white'],
];
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
                        <div class="mt-3 d-grid gap-2">
                            <button type="button" class="btn btn-outline-gold w-100" id="toggle-edit-btn">Edit Profile</button>
                            <a href="checkout.php" class="btn btn-gold w-100">Go to Checkout</a>
                        </div>
                    </div>
                </div>
                <div class="card user-card mt-4 <?php echo $show_edit_form ? '' : 'd-none'; ?>" id="edit-profile-card">
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
                <div class="card user-card mb-4">
                    <div class="card-header user-card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h5 class="mb-0">Recommended For You</h5>
                            <span class="text-gold small">Personalized picks from your activity</span>
                        </div>
                        <a href="menulist.php" class="btn btn-outline-gold btn-sm">Browse Menu</a>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($recommendations)): ?>
                            <div class="row g-3 align-items-stretch">
                                <?php foreach ($recommendations as $recommendation): ?>
                                    <?php
                                        $reason_key = $recommendation['reason'] ?? 'trending';
                                        $reason_meta = $recommendation_reason_meta[$reason_key] ?? $recommendation_reason_meta['trending'];
                                        $data_price = number_format((float) ($recommendation['price'] ?? 0), 2, '.', '');
                                    ?>
                                    <div class="col-md-6">
                                        <div class="recommendation-card border rounded h-100 d-flex flex-column p-3">
                                            <div class="ratio ratio-16x9 mb-3 rounded overflow-hidden bg-light">
                                                <img src="<?php echo htmlspecialchars($recommendation['image']); ?>" alt="<?php echo htmlspecialchars($recommendation['name']); ?>" class="w-100 h-100" style="object-fit: cover;">
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center gap-2 mb-1">
                                                <h6 class="mb-0"><?php echo htmlspecialchars($recommendation['name']); ?></h6>
                                                <span class="badge <?php echo $reason_meta['class']; ?>"><?php echo htmlspecialchars($reason_meta['label']); ?></span>
                                            </div>
                                            <p class="text-muted small mb-2 flex-grow-1">
                                                <?php echo htmlspecialchars($recommendation['summary'] ?: $recommendation['description']); ?>
                                            </p>
                                            <div class="d-flex justify-content-between align-items-center mt-auto">
                                                <strong class="text-gold">₹<?php echo number_format((float) $recommendation['price'], 2); ?></strong>
                                                <button type="button" class="btn btn-sm btn-outline-gold add-rec-to-cart" data-menu-id="<?php echo (int) $recommendation['id']; ?>" data-menu-name="<?php echo htmlspecialchars($recommendation['name']); ?>" data-menu-price="<?php echo $data_price; ?>">Add to cart</button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                                <div>
                                    <h6 class="mb-1">No picks yet</h6>
                                    <p class="text-muted small mb-0">Place an order to unlock personalized dishes tailored to you.</p>
                                </div>
                                <a href="menulist.php" class="btn btn-gold btn-sm">Start exploring</a>
                            </div>
                        <?php endif; ?>
                        <div class="small mt-3 text-success d-none" id="recommendations-feedback"></div>
                    </div>
                </div>
                <div class="card user-card mb-4">
                    <div class="card-header user-card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h5 class="mb-0">Saved Addresses</h5>
                            <span class="text-gold small">Manage delivery locations</span>
                        </div>
                        <button class="btn btn-outline-gold btn-sm" id="add-address-btn">Add Address</button>
                    </div>
                    <div class="card-body">
                        <div id="address-form-panel" class="border rounded p-3 mb-3 d-none">
                            <div class="mb-3">
                                <h6 class="mb-1" id="address-form-title">Add Address</h6>
                                <p class="text-muted small mb-0">Save a delivery location for faster checkout.</p>
                            </div>
                            <form id="address-form" class="row g-3" novalidate>
                                <input type="hidden" name="address_id" value="">
                                <div class="col-md-6">
                                    <label class="form-label">Label</label>
                                    <input type="text" name="label" class="form-control" placeholder="Home, Office" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Phone</label>
                                    <input type="text" name="phone" class="form-control" placeholder="Optional">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Address Line 1</label>
                                    <input type="text" name="address_line1" class="form-control" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Address Line 2</label>
                                    <input type="text" name="address_line2" class="form-control" placeholder="Apartment, floor, etc.">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">City</label>
                                    <input type="text" name="city" class="form-control" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">State</label>
                                    <input type="text" name="state" class="form-control">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Postal Code</label>
                                    <input type="text" name="postal_code" class="form-control">
                                </div>
                                <div class="col-12">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="is_default" value="1" id="address-default-switch">
                                        <label class="form-check-label" for="address-default-switch">Set as default delivery address</label>
                                    </div>
                                </div>
                                <div class="col-12 d-flex gap-2 mt-2">
                                    <button type="button" class="btn btn-outline-secondary flex-fill" id="cancel-address-btn">Cancel</button>
                                    <button type="button" class="btn btn-gold flex-fill" id="save-address-btn">Save Address</button>
                                </div>
                            </form>
                        </div>
                        <div id="address-list">
                            <?php if (!empty($addresses)): ?>
                                <?php foreach ($addresses as $address): ?>
                                <div
                                    class="border rounded p-3 mb-3 address-card"
                                    data-address-id="<?php echo (int) $address['id']; ?>"
                                    data-label="<?php echo htmlspecialchars($address['label'], ENT_QUOTES); ?>"
                                    data-line1="<?php echo htmlspecialchars($address['address_line1'], ENT_QUOTES); ?>"
                                    data-line2="<?php echo htmlspecialchars($address['address_line2'] ?? '', ENT_QUOTES); ?>"
                                    data-city="<?php echo htmlspecialchars($address['city'], ENT_QUOTES); ?>"
                                    data-state="<?php echo htmlspecialchars($address['state'] ?? '', ENT_QUOTES); ?>"
                                    data-postal="<?php echo htmlspecialchars($address['postal_code'] ?? '', ENT_QUOTES); ?>"
                                    data-phone="<?php echo htmlspecialchars($address['phone'] ?? '', ENT_QUOTES); ?>"
                                    data-default="<?php echo !empty($address['is_default']) ? '1' : '0'; ?>"
                                >
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <strong><?php echo htmlspecialchars($address['label']); ?></strong>
                                            <?php if (!empty($address['is_default'])): ?>
                                                <span class="badge bg-success ms-2">Default</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-secondary edit-address-btn">Edit</button>
                                            <button class="btn btn-outline-danger delete-address-btn">Delete</button>
                                        </div>
                                    </div>
                                    <div class="small mt-2 text-muted">
                                        <?php echo htmlspecialchars($address['address_line1']); ?><br>
                                        <?php if (!empty($address['address_line2'])): ?>
                                            <?php echo htmlspecialchars($address['address_line2']); ?><br>
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($address['city']); ?><?php if (!empty($address['state'])): ?>, <?php echo htmlspecialchars($address['state']); ?><?php endif; ?> <?php echo htmlspecialchars($address['postal_code']); ?><br>
                                        <?php if (!empty($address['phone'])): ?>
                                            Phone: <?php echo htmlspecialchars($address['phone']); ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="form-check mt-2">
                                        <input class="form-check-input set-default-address" type="radio" name="default_address" value="<?php echo (int) $address['id']; ?>" <?php echo !empty($address['is_default']) ? 'checked' : ''; ?>>
                                        <label class="form-check-label">Use as default delivery address</label>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-muted">No saved addresses yet. Add one to speed up checkout.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="card user-card mb-4">
                    <div class="card-header user-card-header">
                        <h5 class="mb-0">Payment Preference</h5>
                        <span class="text-gold small">Your go-to payment method</span>
                    </div>
                    <div class="card-body">
                        <form id="payment-preference-form" class="d-flex flex-wrap gap-3 align-items-center">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" value="cash" <?php echo $preferred_payment === 'cash' || $preferred_payment === null ? 'checked' : ''; ?>>
                                <label class="form-check-label">Cash on Delivery</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" value="card" <?php echo $preferred_payment === 'card' ? 'checked' : ''; ?>>
                                <label class="form-check-label">Card</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" value="upi" <?php echo $preferred_payment === 'upi' ? 'checked' : ''; ?>>
                                <label class="form-check-label">UPI</label>
                            </div>
                            <button type="submit" class="btn btn-outline-gold btn-sm ms-auto" id="save-payment-pref">Save Preference</button>
                        </form>
                        <div class="small text-muted mt-2" id="payment-pref-feedback"></div>
                    </div>
                </div>

                <div class="card user-card">
                    <div class="card-header user-card-header">
                        <h5 class="mb-0">Order History</h5>
                        <span class="text-gold small">Track your orders</span>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($orders)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>Order ID</th>
                                            <th>Items</th>
                                            <th>Total</th>
                                            <th>Status</th>
                                            <th>Progress</th>
                                            <th>Date</th>
                                            
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($orders as $order): ?>
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
                                            $status_key = strtolower($order['status'] ?? '');
                                            $status_class = preg_replace('/[^a-z0-9_-]/i', '', $status_key);
                                            if ($status_class === '') {
                                                $status_class = 'unknown';
                                            }
                                            $status_title = $status_title_map[$status_key] ?? ucfirst($status_key);
                                            $status_detail = $status_detail_map[$status_key] ?? 'Status updated';
                                            $progress_value = $status_progress_map[$status_key] ?? 0;
                                            $is_cancelled = ($status_key === 'cancelled');
                                            ?>
                                            <tr data-order-row="<?php echo (int) $order['id']; ?>">
                                                <td>#<?php echo (int) $order['id']; ?></td>
                                                <td><?php echo htmlspecialchars(implode(', ', $item_summary)); ?></td>
                                                <td>₹<?php echo number_format((float) $order['total_amount'], 2); ?></td>
                                                <td>
                                                    <span class="badge status-badge status-<?php echo htmlspecialchars($status_class); ?> order-status-badge" data-order-id="<?php echo (int) $order['id']; ?>">
                                                        <?php echo htmlspecialchars($status_title ?: 'Pending'); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($is_cancelled): ?>
                                                        <span class="text-danger fw-semibold">Cancelled</span>
                                                    <?php else: ?>
                                                        <div class="progress order-progress" data-order-progress="<?php echo (int) $order['id']; ?>">
                                                            <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo (int) $progress_value; ?>%" aria-valuenow="<?php echo (int) $progress_value; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                        </div>
                                                        <div class="small text-muted mt-1" data-order-stage="<?php echo (int) $order['id']; ?>"><?php echo htmlspecialchars($status_detail); ?></div>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars(date('d M Y', strtotime($order['created_at']))); ?></td>
                                                
                                            </tr>
                                            <tr class="collapse" id="order-<?php echo (int) $order['id']; ?>">
                                                <td colspan="7">
                                                    <div class="order-detail">
                                                        <div class="row g-3">
                                                            <div class="col-md-6">
                                                                <div class="detail-label">Delivery Address</div>
                                                                <div class="detail-value"><?php echo htmlspecialchars($order['address'] ?: 'Not provided'); ?></div>
                                                                <div class="detail-value"><?php echo htmlspecialchars($order['city'] ?: ''); ?></div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div class="detail-label">Order Summary</div>
                                                                <div class="detail-value">Subtotal: ₹<?php echo number_format((float) $order['subtotal'], 2); ?></div>
                                                                <div class="detail-value">Delivery: ₹<?php echo number_format((float) $order['delivery_charge'], 2); ?></div>
                                                                <div class="detail-value">Tax: ₹<?php echo number_format((float) $order['tax'], 2); ?></div>
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
                                        <?php endforeach; ?>
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    var toggleBtn = document.getElementById('toggle-edit-btn');
    var editCard = document.getElementById('edit-profile-card');

    if (toggleBtn && editCard) {
        var setButtonState = function () {
            var isHidden = editCard.classList.contains('d-none');
            toggleBtn.textContent = isHidden ? 'Edit Profile' : 'Cancel Edit';
            toggleBtn.setAttribute('aria-expanded', isHidden ? 'false' : 'true');
        };

        setButtonState();

        toggleBtn.addEventListener('click', function () {
            var isHidden = editCard.classList.contains('d-none');
            if (isHidden) {
                editCard.classList.remove('d-none');
                editCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
            } else {
                editCard.classList.add('d-none');
            }
            setButtonState();
        });
    }

    var recommendationButtons = document.querySelectorAll('.add-rec-to-cart');
    var recommendationsFeedback = document.getElementById('recommendations-feedback');
    var cartStorageKey = 'cafe_cart';
    var addressList = document.getElementById('address-list');
    var addAddressBtn = document.getElementById('add-address-btn');
    var addressFormPanel = document.getElementById('address-form-panel');
    var addressForm = document.getElementById('address-form');
    var addressFormTitle = document.getElementById('address-form-title');
    var cancelAddressBtn = document.getElementById('cancel-address-btn');
    var saveAddressBtn = document.getElementById('save-address-btn');
    var paymentPrefForm = document.getElementById('payment-preference-form');
    var paymentPrefFeedback = document.getElementById('payment-pref-feedback');

    var escapeHtml = function (str) {
        if (str === null || str === undefined) {
            return '';
        }
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    };

    var resetAddressForm = function () {
        if (!addressForm) {
            return;
        }
        addressForm.reset();
        var idField = addressForm.querySelector('input[name="address_id"]');
        if (idField) {
            idField.value = '';
        }
        var defaultSwitch = addressForm.querySelector('input[name="is_default"]');
        if (defaultSwitch) {
            defaultSwitch.checked = false;
        }
    };

    var hideAddressForm = function () {
        if (!addressFormPanel) {
            return;
        }
        resetAddressForm();
        addressFormPanel.classList.add('d-none');
    };

    var openAddressForm = function (mode, data) {
        if (!addressFormPanel || !addressForm) {
            return;
        }
        resetAddressForm();
        if (mode === 'edit' && data) {
            populateAddressForm(data);
            if (addressFormTitle) {
                addressFormTitle.textContent = 'Edit Address';
            }
        } else {
            if (addressFormTitle) {
                addressFormTitle.textContent = 'Add Address';
            }
        }
        addressFormPanel.classList.remove('d-none');
        addressFormPanel.scrollIntoView({ behavior: 'smooth', block: 'center' });
    };

    var populateAddressForm = function (data) {
        if (!addressForm) {
            return;
        }
        addressForm.querySelector('input[name="address_id"]').value = data.id || '';
        addressForm.querySelector('input[name="label"]').value = data.label || '';
        addressForm.querySelector('input[name="address_line1"]').value = data.address_line1 || '';
        addressForm.querySelector('input[name="address_line2"]').value = data.address_line2 || '';
        addressForm.querySelector('input[name="city"]').value = data.city || '';
        addressForm.querySelector('input[name="state"]').value = data.state || '';
        addressForm.querySelector('input[name="postal_code"]').value = data.postal_code || '';
        addressForm.querySelector('input[name="phone"]').value = data.phone || '';
        var defaultSwitch = addressForm.querySelector('input[name="is_default"]');
        defaultSwitch.checked = Boolean(parseInt(data.is_default || 0, 10));
    };

    var buildAddressCard = function (address) {
        var badge = address.is_default ? ' <span class="badge bg-success ms-2">Default</span>' : '';
        var line2 = address.address_line2 ? `${escapeHtml(address.address_line2)}<br>` : '';
        var stateText = address.state ? `, ${escapeHtml(address.state)}` : '';
        var postalText = address.postal_code ? ` ${escapeHtml(address.postal_code)}` : '';
        var phoneText = address.phone ? `Phone: ${escapeHtml(address.phone)}` : '';

        return `
            <div class="border rounded p-3 mb-3 address-card"
                data-address-id="${address.id}"
                data-label="${escapeHtml(address.label || '')}"
                data-line1="${escapeHtml(address.address_line1 || '')}"
                data-line2="${escapeHtml(address.address_line2 || '')}"
                data-city="${escapeHtml(address.city || '')}"
                data-state="${escapeHtml(address.state || '')}"
                data-postal="${escapeHtml(address.postal_code || '')}"
                data-phone="${escapeHtml(address.phone || '')}"
                data-default="${address.is_default ? '1' : '0'}">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <strong>${escapeHtml(address.label || '')}</strong>${badge}
                    </div>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-secondary edit-address-btn">Edit</button>
                        <button class="btn btn-outline-danger delete-address-btn">Delete</button>
                    </div>
                </div>
                <div class="small mt-2 text-muted">
                    ${escapeHtml(address.address_line1 || '')}<br>
                    ${line2}
                    ${escapeHtml(address.city || '')}${stateText}${postalText}<br>
                    ${phoneText}
                </div>
                <div class="form-check mt-2">
                    <input class="form-check-input set-default-address" type="radio" name="default_address" value="${address.id}" ${address.is_default ? 'checked' : ''}>
                    <label class="form-check-label">Use as default delivery address</label>
                </div>
            </div>`;
    };

    var renderAddresses = function (addresses) {
        if (!addressList) {
            return;
        }
        if (!Array.isArray(addresses) || !addresses.length) {
            addressList.innerHTML = '<div class="text-muted">No saved addresses yet. Add one to speed up checkout.</div>';
            return;
        }
        addressList.innerHTML = addresses.map(buildAddressCard).join('');
    };

    var submitAddressForm = function () {
        if (!addressForm) {
            return;
        }
        var formData = new FormData(addressForm);
        fetch('save_address.php', {
            method: 'POST',
            credentials: 'same-origin',
            body: formData
        })
            .then(function (response) { return response.json(); })
            .then(function (payload) {
                if (payload && payload.success) {
                    renderAddresses(payload.addresses || []);
                    hideAddressForm();
                } else {
                    alert((payload && payload.message) || 'Failed to save address.');
                }
            })
            .catch(function () {
                alert('Unable to save address at the moment.');
            });
    };

    var deleteAddress = function (addressId) {
        if (!addressId) {
            return;
        }
        if (!window.confirm('Delete this address?')) {
            return;
        }
        var formData = new FormData();
        formData.append('address_id', addressId);
        fetch('delete_address.php', {
            method: 'POST',
            credentials: 'same-origin',
            body: formData
        })
            .then(function (response) { return response.json(); })
            .then(function (payload) {
                if (payload && payload.success) {
                    renderAddresses(payload.addresses || []);
                } else {
                    alert((payload && payload.message) || 'Unable to delete address.');
                }
            })
            .catch(function () {
                alert('Unable to delete address right now.');
            });
    };

    var setDefaultAddress = function (addressId) {
        if (!addressId) {
            return;
        }
        var formData = new FormData();
        formData.append('address_id', addressId);
        fetch('set_default_address.php', {
            method: 'POST',
            credentials: 'same-origin',
            body: formData
        })
            .then(function (response) { return response.json(); })
            .then(function (payload) {
                if (payload && payload.success) {
                    renderAddresses(payload.addresses || []);
                }
            })
            .catch(function () {
                console.warn('Unable to set default address right now.');
            });
    };

    var cardDataFromElement = function (card) {
        return {
            id: card.getAttribute('data-address-id') || '',
            label: card.getAttribute('data-label') || '',
            address_line1: card.getAttribute('data-line1') || '',
            address_line2: card.getAttribute('data-line2') || '',
            city: card.getAttribute('data-city') || '',
            state: card.getAttribute('data-state') || '',
            postal_code: card.getAttribute('data-postal') || '',
            phone: card.getAttribute('data-phone') || '',
            is_default: card.getAttribute('data-default') || '0'
        };
    };

    var loadCartFromStorage = function () {
        if (typeof window.localStorage === 'undefined') {
            return [];
        }
        try {
            var stored = JSON.parse(localStorage.getItem(cartStorageKey) || '[]');
            return Array.isArray(stored) ? stored : [];
        } catch (error) {
            localStorage.removeItem(cartStorageKey);
            return [];
        }
    };

    var saveCartToStorage = function (items) {
        if (typeof window.localStorage === 'undefined') {
            return;
        }
        localStorage.setItem(cartStorageKey, JSON.stringify(items));
    };

    var handleRecommendationAdd = function (button) {
        if (!button) {
            return;
        }
        var id = parseInt(button.getAttribute('data-menu-id'), 10);
        if (!id) {
            return;
        }
        var name = button.getAttribute('data-menu-name') || 'Menu Item';
        var price = parseFloat(button.getAttribute('data-menu-price')) || 0;
        var cartItems = loadCartFromStorage();
        var existing = null;
        for (var i = 0; i < cartItems.length; i++) {
            if (parseInt(cartItems[i].id, 10) === id) {
                existing = cartItems[i];
                break;
            }
        }
        if (existing) {
            existing.quantity = (existing.quantity || 1) + 1;
        } else {
            cartItems.push({ id: id, name: name, price: price, quantity: 1 });
        }
        saveCartToStorage(cartItems);
        if (recommendationsFeedback) {
            recommendationsFeedback.textContent = name + ' added to your cart.';
            recommendationsFeedback.classList.remove('d-none', 'text-danger');
            recommendationsFeedback.classList.add('text-success');
            recommendationsFeedback.setAttribute('role', 'status');
            setTimeout(function () {
                recommendationsFeedback.classList.add('d-none');
            }, 3000);
        }
        button.disabled = true;
        var originalText = button.textContent;
        button.textContent = 'Added';
        setTimeout(function () {
            button.disabled = false;
            button.textContent = originalText;
        }, 1500);
    };

    if (recommendationButtons && recommendationButtons.length) {
        recommendationButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                handleRecommendationAdd(button);
            });
        });
    }

    if (addAddressBtn) {
        addAddressBtn.addEventListener('click', function () {
            openAddressForm('add');
        });
    }

    if (cancelAddressBtn) {
        cancelAddressBtn.addEventListener('click', hideAddressForm);
    }

    if (saveAddressBtn) {
        saveAddressBtn.addEventListener('click', function (event) {
            event.preventDefault();
            submitAddressForm();
        });
    }

    if (addressList) {
        addressList.addEventListener('click', function (event) {
            var target = event.target;
            var card = target.closest('.address-card');
            if (!card) {
                return;
            }
            var data = cardDataFromElement(card);
            if (target.classList.contains('edit-address-btn')) {
                openAddressForm('edit', data);
            }
            if (target.classList.contains('delete-address-btn')) {
                deleteAddress(data.id);
            }
        });

        addressList.addEventListener('change', function (event) {
            if (event.target.classList.contains('set-default-address')) {
                setDefaultAddress(event.target.value);
            }
        });
    }

    if (paymentPrefForm) {
        paymentPrefForm.addEventListener('submit', function (event) {
            event.preventDefault();
            var formData = new FormData(paymentPrefForm);
            fetch('save_payment_preference.php', {
                method: 'POST',
                credentials: 'same-origin',
                body: formData
            })
                .then(function (response) { return response.json(); })
                .then(function (payload) {
                    if (payload && payload.success) {
                        paymentPrefFeedback.textContent = 'Preference saved.';
                        paymentPrefFeedback.classList.remove('text-danger');
                    } else {
                        paymentPrefFeedback.textContent = (payload && payload.message) || 'Unable to save preference.';
                        paymentPrefFeedback.classList.add('text-danger');
                    }
                })
                .catch(function () {
                    paymentPrefFeedback.textContent = 'Unable to save preference right now.';
                    paymentPrefFeedback.classList.add('text-danger');
                });
        });
    }

    var statusBadges = document.querySelectorAll('.order-status-badge');
    if (!statusBadges.length) {
        return;
    }

    var statusTitleMap = <?php echo json_encode($status_title_map, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
    var statusDetailMap = <?php echo json_encode($status_detail_map, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
    var statusProgressMap = <?php echo json_encode($status_progress_map, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;

    var formatTitle = function (status) {
        if (!status) return '';
        return statusTitleMap[status] || status.charAt(0).toUpperCase() + status.slice(1);
    };

    var updateOrderVisuals = function (orderId, status) {
        if (!orderId || !status) return;
        status = String(status).toLowerCase();
        var badge = document.querySelector('.order-status-badge[data-order-id="' + orderId + '"]');
        if (badge) {
            badge.textContent = formatTitle(status);
            badge.className = 'badge status-badge status-' + status + ' order-status-badge';
        }

        var progressWrapper = document.querySelector('[data-order-progress="' + orderId + '"]');
        var stageLabel = document.querySelector('[data-order-stage="' + orderId + '"]');

        if (progressWrapper) {
            if (status === 'cancelled') {
                progressWrapper.innerHTML = '<span class="text-danger fw-semibold">Cancelled</span>';
                if (stageLabel) {
                    stageLabel.textContent = statusDetailMap[status] || 'This order was cancelled';
                    stageLabel.classList.add('text-danger');
                }
            } else {
                var bar = progressWrapper.querySelector('.progress-bar');
                if (bar) {
                    var pct = statusProgressMap[status];
                    bar.style.width = (typeof pct === 'number' ? pct : 0) + '%';
                    bar.setAttribute('aria-valuenow', typeof pct === 'number' ? pct : 0);
                }
                if (stageLabel) {
                    stageLabel.textContent = statusDetailMap[status] || 'Status updated';
                    stageLabel.classList.remove('text-danger');
                }
            }
        }
    };

    var pollStatuses = function () {
        fetch('order_status.php', { credentials: 'same-origin' })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Unable to fetch order statuses');
                }
                return response.json();
            })
            .then(function (payload) {
                if (!payload || !Array.isArray(payload.orders)) {
                    return;
                }
                payload.orders.forEach(function (order) {
                    if (!order || typeof order.id === 'undefined') {
                        return;
                    }
                    var currentStatus = order.status ? String(order.status).toLowerCase() : '';
                    updateOrderVisuals(order.id, currentStatus);
                });
            })
            .catch(function (error) {
                console.warn('Order status refresh failed:', error);
            });
    };

    pollStatuses();
    setInterval(pollStatuses, 20000);
});
</script>

<?php
include 'includes/footer.php';
?>
