<?php 
include 'includes/auth.php';
include 'includes/db.php';
require_permission('orders.view');

// Order filter parameters
$filter_date_from = $_GET['date_from'] ?? '';
$filter_date_to   = $_GET['date_to']   ?? '';
$filter_status    = $_GET['status']     ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders & Inquiries | Cafe Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .sidebar { background-color: #2c3e50; min-height: 100vh; padding: 20px 0; }
        .sidebar .nav-link { color: #ecf0f1; padding: 12px 20px; margin: 5px 0; }
        .sidebar .nav-link:hover { background-color: #34495e; border-radius: 5px; }
        .sidebar .nav-link.active { background-color: #3498db; border-radius: 5px; }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark bg-dark">
        <div class="container-fluid">
            <span class="navbar-brand">☕ Cafe Admin - Orders & Inquiries</span>
            <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'includes/sidebar.php'; ?>


            <!-- Main Content -->
            <div class="col-md-10 p-4">
                <!-- Tabs for Orders and Contact Submissions -->
                <ul class="nav nav-tabs mb-4" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#orders" type="button">📦 Orders</button>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content">
                    <!-- Orders Tab -->
                    <div class="tab-pane fade show active" id="orders">
                        <h2 class="mb-4">Orders Management</h2>

                        <?php
                        // Handle status update for orders
                        if (isset($_POST['update_order_status'])) {
                            $order_id = intval($_POST['order_id']);
                            $status = $_POST['status'];
                            $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
                            $stmt->bind_param("si", $status, $order_id);
                            if ($stmt->execute()) {
                                echo "<div class='alert alert-success alert-dismissible fade show'>
                                    Order status updated successfully!
                                    <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                                </div>";
                            }
                            $stmt->close();
                        }

                        // Handle cancel order
                        if (isset($_POST['cancel_order'])) {
                            $order_id = intval($_POST['order_id']);
                            $stmt = $conn->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ? AND status IN ('pending', 'confirmed')");
                            $stmt->bind_param("i", $order_id);
                            if ($stmt->execute()) {
                                if ($stmt->affected_rows > 0) {
                                    echo "<div class='alert alert-warning alert-dismissible fade show'>
                                        Order cancelled successfully!
                                        <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                                    </div>";
                                } else {
                                    echo "<div class='alert alert-danger alert-dismissible fade show'>
                                        Cannot cancel this order - it must be in pending or confirmed status!
                                        <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                                    </div>";
                                }
                            }
                            $stmt->close();
                        }

                        // Get revenue ONLY from completed/delivered orders (with date filters if applied)
                        $revenue_where = "WHERE status IN ('completed', 'delivered')";
                        $revenue_params = [];
                        $revenue_types = '';
                        if ($filter_date_from) {
                            $revenue_where .= " AND DATE(created_at) >= ?";
                            $revenue_params[] = $filter_date_from;
                            $revenue_types .= 's';
                        }
                        if ($filter_date_to) {
                            $revenue_where .= " AND DATE(created_at) <= ?";
                            $revenue_params[] = $filter_date_to;
                            $revenue_types .= 's';
                        }
                        
                        // Get revenue with filters
                        $revenue_stmt = $conn->prepare("SELECT COALESCE(SUM(total_amount), 0) as revenue FROM orders $revenue_where");
                        if ($revenue_types) $revenue_stmt->bind_param($revenue_types, ...$revenue_params);
                        $revenue_stmt->execute();
                        $todayRevenue = $revenue_stmt->get_result()->fetch_assoc()['revenue'] ?? 0;
                        $revenue_stmt->close();

                        // Build order count query based on filters (includes all statuses)
                        $count_where = "WHERE 1=1";
                        $count_params = [];
                        $count_types  = '';
                        if ($filter_date_from) {
                            $count_where .= " AND DATE(created_at) >= ?";
                            $count_params[] = $filter_date_from;
                            $count_types .= 's';
                        }
                        if ($filter_date_to) {
                            $count_where .= " AND DATE(created_at) <= ?";
                            $count_params[] = $filter_date_to;
                            $count_types .= 's';
                        }
                        if ($filter_status) {
                            $count_where .= " AND status = ?";
                            $count_params[] = $filter_status;
                            $count_types .= 's';
                        }
                        
                        // Get total orders count with filters
                        $count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM orders $count_where");
                        if ($count_types) $count_stmt->bind_param($count_types, ...$count_params);
                        $count_stmt->execute();
                        $totalOrders = $count_stmt->get_result()->fetch_assoc()['total'] ?? 0;
                        $count_stmt->close();

                        echo "<div class='row mb-4'>
                            <div class='col-md-4'>
                                <div class='card bg-primary text-white'>
                                    <div class='card-body'>
                                        <h6 class='card-title'>Total Orders</h6>
                                        <h3>$totalOrders</h3>
                                    </div>
                                </div>
                            </div>
                            <div class='col-md-4'>
                                <div class='card bg-success text-white'>
                                    <div class='card-body'>
                                        <h6 class='card-title'>Today's Revenue</h6>
                                        <h3>₹" . number_format($todayRevenue ?? 0, 2) . "</h3>
                                    </div>
                                </div>
                            </div>
                        </div>";
                        ?>

                        <!-- Order Filters -->
                        <div class="card mb-3">
                            <div class="card-body">
                                <form method="GET" class="row g-3 align-items-end">
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">Date From</label>
                                        <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($filter_date_from) ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">Date To</label>
                                        <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($filter_date_to) ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">Order Status</label>
                                        <select name="status" class="form-select">
                                            <option value="">All Statuses</option>
                                            <?php
                                            $statuses = ['pending','confirmed','preparing','ready','completed','delivered','cancelled'];
                                            foreach ($statuses as $s): ?>
                                                <option value="<?= $s ?>" <?= $filter_status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3 d-flex gap-2">
                                        <button type="submit" class="btn btn-primary"><i class="fas fa-filter me-1"></i> Filter</button>
                                        <a href="orders.php" class="btn btn-outline-secondary"><i class="fas fa-times me-1"></i> Reset</a>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h5>All Orders</h5>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead style="background: #f8f9fa;">
                                        <tr>
                                            <th>Order ID</th>
                                            <th>Customer Name</th>
                                            <th>Email</th>
                                            <th>Phone</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // Build filtered query
                                        $order_where = "WHERE 1=1";
                                        $order_params = [];
                                        $order_types  = '';
                                        if ($filter_date_from) {
                                            $order_where .= " AND DATE(created_at) >= ?";
                                            $order_params[] = $filter_date_from;
                                            $order_types .= 's';
                                        }
                                        if ($filter_date_to) {
                                            $order_where .= " AND DATE(created_at) <= ?";
                                            $order_params[] = $filter_date_to;
                                            $order_types .= 's';
                                        }
                                        if ($filter_status) {
                                            $order_where .= " AND status = ?";
                                            $order_params[] = $filter_status;
                                            $order_types .= 's';
                                        }
                                        $order_sql = "SELECT * FROM orders $order_where ORDER BY created_at DESC";
                                        $order_stmt = $conn->prepare($order_sql);
                                        if ($order_types) $order_stmt->bind_param($order_types, ...$order_params);
                                        $order_stmt->execute();
                                        $orders = $order_stmt->get_result();
                                        if ($orders && $orders->num_rows > 0) {
                                            while ($order = $orders->fetch_assoc()) {
                                                $status_color = $order['status'] === 'completed' ? 'success' : 
                                                               ($order['status'] === 'delivered' ? 'info' :
                                                               ($order['status'] === 'pending' ? 'warning' : 
                                                               ($order['status'] === 'cancelled' ? 'danger' : 'secondary')));
                                                $can_cancel = in_array($order['status'], ['pending', 'confirmed']);

                                                // Decode items JSON for the detail row
                                                $items_json = $order['items'] ?? '[]';
                                                $items_arr = json_decode($items_json, true);
                                                if (!is_array($items_arr)) $items_arr = [];

                                                $detail_row_id = 'detail-order-' . $order['id'];

                                                echo "<tr>
                                                    <td><strong>#{$order['id']}</strong></td>
                                                    <td>{$order['customer_name']}</td>
                                                    <td>{$order['email']}</td>
                                                    <td>{$order['phone']}</td>
                                                    <td>₹{$order['total_amount']}</td>
                                                    <td><span class='badge bg-$status_color'>" . ucfirst($order['status']) . "</span></td>
                                                    <td>" . date('d M Y H:i', strtotime($order['created_at'])) . "</td>
                                                    <td>
                                                        <div class='d-flex gap-2 align-items-center'>
                                                            <button type='button' class='btn btn-sm btn-outline-primary order-view-btn' data-target='$detail_row_id' title='View details'>
                                                                <i class='fas fa-eye me-1'></i>View
                                                            </button>
                                                            <form method='POST' style='flex: 1;'>
                                                                <input type='hidden' name='order_id' value='{$order['id']}'>
                                                                <select name='status' class='form-select form-select-sm' onchange='this.form.submit()'>
                                                                    <option value='pending' " . ($order['status'] === 'pending' ? 'selected' : '') . ">Pending</option>
                                                                    <option value='confirmed' " . ($order['status'] === 'confirmed' ? 'selected' : '') . ">Confirmed</option>
                                                                    <option value='preparing' " . ($order['status'] === 'preparing' ? 'selected' : '') . ">Preparing</option>
                                                                    <option value='ready' " . ($order['status'] === 'ready' ? 'selected' : '') . ">Ready</option>
                                                                    <option value='completed' " . ($order['status'] === 'completed' ? 'selected' : '') . ">Completed</option>
                                                                    <option value='delivered' " . ($order['status'] === 'delivered' ? 'selected' : '') . ">Delivered</option>
                                                                </select>
                                                                <input type='hidden' name='update_order_status' value='1'>
                                                            </form>";
                                                if ($can_cancel) {
                                                    echo "<form method='POST' onsubmit=\"return confirm('Are you sure you want to cancel this order?');\" style='flex: 0;'>
                                                        <input type='hidden' name='order_id' value='{$order['id']}'>
                                                        <button type='submit' name='cancel_order' class='btn btn-sm btn-outline-danger' title='Cancel order'>
                                                            <i class='fas fa-times'></i>
                                                        </button>
                                                    </form>";
                                                }
                                                echo "
                                                        </div>
                                                    </td>
                                                </tr>";

                                                // ── Inline Detail Row (hidden by default) ──
                                                $address_display = htmlspecialchars(($order['address'] ?? '') . ($order['city'] ? ', ' . $order['city'] : ''));
                                                $payment_display = htmlspecialchars(ucwords(str_replace('_', ' ', $order['payment_method'] ?? 'N/A')));
                                                $subtotal_display = number_format((float)($order['subtotal'] ?? 0), 2);
                                                $delivery_display = number_format((float)($order['delivery_charge'] ?? 0), 2);
                                                $tax_display = number_format((float)($order['tax'] ?? 0), 2);
                                                $total_display = number_format((float)($order['total_amount'] ?? 0), 2);

                                                echo "<tr id='$detail_row_id' style='display:none;'>
                                                    <td colspan='8'>
                                                        <div class='p-3 bg-light rounded border'>
                                                            <div class='d-flex justify-content-between align-items-center mb-3'>
                                                                <h6 class='mb-0 fw-bold'><i class='fas fa-receipt me-2'></i>Order #{$order['id']} — Details</h6>
                                                                <button type='button' class='btn btn-sm btn-outline-secondary order-close-btn' data-target='$detail_row_id'><i class='fas fa-times me-1'></i>Close</button>
                                                            </div>
                                                            <div class='row mb-3'>
                                                                <div class='col-md-4'>
                                                                    <strong><i class='fas fa-user me-1'></i> Customer</strong><br>
                                                                    {$order['customer_name']}<br>
                                                                    <small class='text-muted'>{$order['email']} &bull; {$order['phone']}</small>
                                                                </div>
                                                                <div class='col-md-4'>
                                                                    <strong><i class='fas fa-info-circle me-1'></i> Status & Payment</strong><br>
                                                                    <span class='badge bg-$status_color'>" . ucfirst($order['status']) . "</span>
                                                                    &bull; $payment_display<br>
                                                                    <small class='text-muted'>" . date('d M Y, h:i A', strtotime($order['created_at'])) . "</small>
                                                                </div>";
                                                                if (!empty($address_display)) {
                                                                    echo "<div class='col-md-4'>
                                                                        <strong><i class='fas fa-map-marker-alt me-1'></i> Delivery Address</strong><br>
                                                                        $address_display
                                                                    </div>";
                                                                }
                                                echo "      </div>

                                                            <strong class='d-block mb-2'><i class='fas fa-utensils me-1'></i> Ordered Items</strong>";
                                                            if (count($items_arr) > 0) {
                                                                echo "<table class='table table-sm table-bordered mb-3 bg-white'>
                                                                    <thead class='table-light'>
                                                                        <tr><th>#</th><th>Item</th><th class='text-center'>Qty</th><th class='text-end'>Price</th><th class='text-end'>Total</th></tr>
                                                                    </thead><tbody>";
                                                                $item_num = 0;
                                                                foreach ($items_arr as $item) {
                                                                    $item_num++;
                                                                    $item_name = htmlspecialchars($item['name'] ?? 'Unknown Item');
                                                                    $item_qty = intval($item['quantity'] ?? 1);
                                                                    $item_price = floatval($item['price'] ?? 0);
                                                                    $item_total = $item_price * $item_qty;
                                                                    echo "<tr>
                                                                        <td>$item_num</td>
                                                                        <td>$item_name</td>
                                                                        <td class='text-center'>$item_qty</td>
                                                                        <td class='text-end'>₹" . number_format($item_price, 2) . "</td>
                                                                        <td class='text-end'>₹" . number_format($item_total, 2) . "</td>
                                                                    </tr>";
                                                                }
                                                                echo "</tbody></table>";
                                                            } else {
                                                                echo "<p class='text-muted'>No item details available.</p>";
                                                            }

                                                            echo "<div class='row'>
                                                                <div class='col-md-4 ms-auto'>
                                                                    <table class='table table-sm mb-0'>
                                                                        <tr><td>Subtotal</td><td class='text-end'>₹$subtotal_display</td></tr>
                                                                        <tr><td>Delivery</td><td class='text-end'>₹$delivery_display</td></tr>
                                                                        <tr><td>Tax</td><td class='text-end'>₹$tax_display</td></tr>
                                                                        <tr class='fw-bold'><td>Total</td><td class='text-end text-success'>₹$total_display</td></tr>
                                                                    </table>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>";
                                            }
                                        } else {
                                            echo "<tr><td colspan='8' class='text-center text-muted'>No orders found</td></tr>";
                                        }
                                        $order_stmt->close();
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/order-notifications.js"></script>
    <script>
    document.addEventListener('click', function(e) {
        var viewBtn = e.target.closest('.order-view-btn');
        if (viewBtn) {
            var targetId = viewBtn.getAttribute('data-target');
            var row = document.getElementById(targetId);
            if (row) {
                row.style.display = '';
                viewBtn.innerHTML = '<i class="fas fa-eye-slash me-1"></i>Hide';
                viewBtn.classList.remove('btn-outline-primary');
                viewBtn.classList.add('btn-outline-secondary');
                viewBtn.classList.remove('order-view-btn');
                viewBtn.classList.add('order-hide-btn');
            }
            return;
        }
        var hideBtn = e.target.closest('.order-hide-btn');
        if (hideBtn) {
            var targetId = hideBtn.getAttribute('data-target');
            var row = document.getElementById(targetId);
            if (row) {
                row.style.display = 'none';
                hideBtn.innerHTML = '<i class="fas fa-eye me-1"></i>View';
                hideBtn.classList.remove('btn-outline-secondary');
                hideBtn.classList.add('btn-outline-primary');
                hideBtn.classList.remove('order-hide-btn');
                hideBtn.classList.add('order-view-btn');
            }
            return;
        }
        var closeBtn = e.target.closest('.order-close-btn');
        if (closeBtn) {
            var targetId = closeBtn.getAttribute('data-target');
            var row = document.getElementById(targetId);
            if (row) {
                row.style.display = 'none';
                var viewBtn = document.querySelector('[data-target="' + targetId + '"].order-hide-btn');
                if (viewBtn) {
                    viewBtn.innerHTML = '<i class="fas fa-eye me-1"></i>View';
                    viewBtn.classList.remove('btn-outline-secondary');
                    viewBtn.classList.add('btn-outline-primary');
                    viewBtn.classList.remove('order-hide-btn');
                    viewBtn.classList.add('order-view-btn');
                }
            }
        }
    });
    </script>
</body>
</html>
