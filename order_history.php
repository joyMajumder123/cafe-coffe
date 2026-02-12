<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['customer_id'])) {
    header('Location: customer_login.php?redirect=order_history.php');
    exit();
}

require_once __DIR__ . '/admin/includes/db.php';
require_once __DIR__ . '/includes/user_layout.php';

$customer_id = (int) $_SESSION['customer_id'];
$customer_name = $_SESSION['customer_name'] ?? 'Guest';

$order_stmt = $conn->prepare('SELECT * FROM orders WHERE customer_id = ? ORDER BY created_at DESC');
$order_stmt->bind_param('i', $customer_id);
$order_stmt->execute();
$orders_result = $order_stmt->get_result();
$orders = $orders_result ? $orders_result->fetch_all(MYSQLI_ASSOC) : [];
$order_stmt->close();

$total_orders = count($orders);
$active_statuses = ['pending', 'confirmed', 'preparing', 'ready'];
$active_orders = array_reduce($orders, function ($carry, $order) use ($active_statuses) {
    $status = strtolower($order['status'] ?? '');
    return $carry + (in_array($status, $active_statuses, true) ? 1 : 0);
}, 0);
$last_order_date = $orders[0]['created_at'] ?? null;

$status_title_map = [
    'pending' => 'Order Received',
    'confirmed' => 'Confirmed',
    'preparing' => 'In the Kitchen',
    'ready' => 'Ready for Pickup',
    'completed' => 'Completed',
    'delivered' => 'Delivered',
    'cancelled' => 'Cancelled',
];

$status_detail_map = [
    'pending' => 'We have your order and will confirm shortly.',
    'confirmed' => 'The cafe confirmed your order.',
    'preparing' => 'Our chefs are preparing your items.',
    'ready' => 'Everything is packed and ready.',
    'completed' => 'Enjoy your meal! This order is complete.',
    'delivered' => 'Order delivered successfully.',
    'cancelled' => 'This order was cancelled.',
];

$status_progress_map = [
    'pending' => 10,
    'confirmed' => 30,
    'preparing' => 60,
    'ready' => 85,
    'completed' => 100,
    'delivered' => 100,
    'cancelled' => 0,
];
?>

<section class="py-5 user-page">
    <div class="container" style="margin-top: 60px;">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
            <div>
                <p class="text-gold text-uppercase small mb-1">Order History</p>
                <h2 class="mb-1">Hello, <?php echo htmlspecialchars($customer_name); ?></h2>
                <p class="text-muted mb-0">Track every coffee run, delivery, and dine-in order from one dashboard.</p>
            </div>
            <a href="menulist.php" class="btn btn-gold">Order Something New</a>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="border rounded p-3 h-100">
                    <p class="text-muted mb-1">Total Orders</p>
                    <h4 class="mb-0"><?php echo number_format($total_orders); ?></h4>
                </div>
            </div>
            <div class="col-md-4">
                <div class="border rounded p-3 h-100">
                    <p class="text-muted mb-1">Active Orders</p>
                    <h4 class="mb-0"><?php echo number_format($active_orders); ?></h4>
                </div>
            </div>
            <div class="col-md-4">
                <div class="border rounded p-3 h-100">
                    <p class="text-muted mb-1">Last Order</p>
                    <h4 class="mb-0">
                        <?php echo $last_order_date ? htmlspecialchars(date('d M Y', strtotime($last_order_date))) : 'Not yet'; ?>
                    </h4>
                </div>
            </div>
        </div>

        <div class="card user-card">
            <div class="card-header user-card-header d-flex justify-content-between flex-wrap gap-2 align-items-center">
                <div>
                    <h5 class="mb-0">All Orders</h5>
                    <span class="text-gold small">Status updates refresh automatically</span>
                </div>
                <div class="btn-group btn-group-sm" role="group" aria-label="Order Filters">
                    <button class="btn btn-outline-gold active" data-order-filter="all">All</button>
                    <button class="btn btn-outline-gold" data-order-filter="active">Active</button>
                    <button class="btn btn-outline-gold" data-order-filter="completed">Completed</button>
                </div>
            </div>
            <div class="card-body">
                <?php if (!empty($orders)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle" id="order-history-table">
                            <thead>
                                <tr>
                                    <th scope="col">Order</th>
                                    <th scope="col">Items</th>
                                    <th scope="col">Total</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Progress</th>
                                    <th scope="col">Placed</th>
                                    <th scope="col" class="text-end">Details</th>
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
                                        $status_key = strtolower($order['status'] ?? 'pending');
                                        $status_class = preg_replace('/[^a-z0-9_-]/i', '', $status_key) ?: 'unknown';
                                        $status_title = $status_title_map[$status_key] ?? ucfirst($status_key);
                                        $status_detail = $status_detail_map[$status_key] ?? 'Status updated';
                                        $progress_value = $status_progress_map[$status_key] ?? 0;
                                        $is_cancelled = ($status_key === 'cancelled');
                                        $order_id = (int) ($order['id'] ?? 0);
                                        $detail_target = 'order-' . $order_id;
                                        $items_list = is_array($items) && !empty($items) ? $items : [];
                                        $is_completed = in_array($status_key, ['completed', 'delivered'], true);
                                    ?>
                                    <tr class="order-row" data-order-status="<?php echo htmlspecialchars($status_key); ?>">
                                        <td>#<?php echo $order_id; ?></td>
                                        <td><?php echo htmlspecialchars(implode(', ', $item_summary)); ?></td>
                                        <td>₹<?php echo number_format((float) ($order['total_amount'] ?? 0), 2); ?></td>
                                        <td>
                                            <span class="badge status-badge status-<?php echo htmlspecialchars($status_class); ?> order-status-badge" data-order-id="<?php echo $order_id; ?>">
                                                <?php echo htmlspecialchars($status_title ?: 'Pending'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($is_cancelled): ?>
                                                <span class="text-danger fw-semibold">Cancelled</span>
                                            <?php else: ?>
                                                <div class="progress order-progress" data-order-progress="<?php echo $order_id; ?>">
                                                    <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo (int) $progress_value; ?>%" aria-valuenow="<?php echo (int) $progress_value; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                                <div class="small text-muted mt-1" data-order-stage="<?php echo $order_id; ?>"><?php echo htmlspecialchars($status_detail); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars(date('d M Y', strtotime($order['created_at'] ?? 'now'))); ?></td>
                                        <td class="text-end">
                                            <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#<?php echo $detail_target; ?>" aria-expanded="false" aria-controls="<?php echo $detail_target; ?>">
                                                View
                                            </button>
                                        </td>
                                    </tr>
                                    <tr class="collapse" id="<?php echo $detail_target; ?>">
                                        <td colspan="7">
                                            <div class="order-detail border rounded p-3">
                                                <div class="row g-3">
                                                    <div class="col-md-4">
                                                        <div class="detail-label text-muted small">Delivery Address</div>
                                                        <div class="detail-value fw-semibold"><?php echo htmlspecialchars($order['address'] ?: 'Not provided'); ?></div>
                                                        <div class="detail-value"><?php echo htmlspecialchars($order['city'] ?: ''); ?></div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="detail-label text-muted small">Payment Summary</div>
                                                        <ul class="list-unstyled mb-1 small">
                                                            <li>Subtotal: ₹<?php echo number_format((float) ($order['subtotal'] ?? 0), 2); ?></li>
                                                            <li>Delivery: ₹<?php echo number_format((float) ($order['delivery_charge'] ?? 0), 2); ?></li>
                                                            <li>Tax: ₹<?php echo number_format((float) ($order['tax'] ?? 0), 2); ?></li>
                                                        </ul>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="detail-label text-muted small">Status Timeline</div>
                                                        <p class="mb-0 small">Updated <?php echo htmlspecialchars(date('d M Y h:i A', strtotime($order['updated_at'] ?? $order['created_at'] ?? 'now'))); ?></p>
                                                    </div>
                                                    <div class="col-12">
                                                        <div class="detail-label text-muted small">Items</div>
                                                        <ul class="mb-0">
                                                            <?php if (!empty($items_list)): ?>
                                                                <?php foreach ($items_list as $item): ?>
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
                    <div class="text-center py-5">
                        <p class="text-muted mb-2">You have not placed any orders yet.</p>
                        <a href="menulist.php" class="btn btn-gold">Browse Menu</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var filterButtons = document.querySelectorAll('[data-order-filter]');
    var orderRows = document.querySelectorAll('#order-history-table tbody tr.order-row');

    var applyFilter = function (filter) {
        if (!orderRows.length) {
            return;
        }
        orderRows.forEach(function (row) {
            var status = row.getAttribute('data-order-status');
            var shouldShow = true;
            if (filter === 'active') {
                shouldShow = ['pending', 'confirmed', 'preparing', 'ready'].indexOf(status) !== -1;
            } else if (filter === 'completed') {
                shouldShow = ['completed', 'delivered'].indexOf(status) !== -1;
            }
            row.classList.toggle('d-none', !shouldShow);
            var detailRow = row.nextElementSibling;
            if (detailRow && detailRow.matches('.collapse')) {
                detailRow.classList.toggle('d-none', !shouldShow);
                if (!shouldShow) {
                    detailRow.classList.remove('show');
                }
            }
        });
    };

    if (filterButtons.length) {
        filterButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                filterButtons.forEach(function (btn) { btn.classList.remove('active'); });
                button.classList.add('active');
                applyFilter(button.getAttribute('data-order-filter'));
            });
        });
    }
    applyFilter('all');

    var statusBadges = document.querySelectorAll('.order-status-badge');
    if (!statusBadges.length) {
        return;
    }

    var statusTitleMap = <?php echo json_encode($status_title_map, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
    var statusDetailMap = <?php echo json_encode($status_detail_map, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
    var statusProgressMap = <?php echo json_encode($status_progress_map, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;

    var formatTitle = function (status) {
        if (!status) {
            return '';
        }
        return statusTitleMap[status] || status.charAt(0).toUpperCase() + status.slice(1);
    };

    var updateOrderVisuals = function (orderId, status) {
        if (!orderId || !status) {
            return;
        }
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
require_once __DIR__ . '/includes/footer.php';
?>
