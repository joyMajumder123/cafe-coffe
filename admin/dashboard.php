<?php 
include 'includes/auth.php';
include 'includes/db.php';
require_permission('dashboard.view');



$inquiry_notice = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_inquiry'])) {
    $inquiry_id = intval($_POST['inquiry_id'] ?? 0);
    if ($inquiry_id > 0) {
        $stmt = $conn->prepare("DELETE FROM contact_submissions WHERE id = ?");
        $stmt->bind_param('i', $inquiry_id);
        if ($stmt->execute()) {
            $inquiry_notice = "Inquiry deleted successfully.";
        } else {
            $inquiry_notice = "Failed to delete inquiry.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Cafe Admin</title>
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
            <span class="navbar-brand">☕ Cafe Admin - Dashboard</span>
            <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'includes/sidebar.php'; ?>

            <!-- Main Content -->
            <div class="col-md-10 p-4">
                <h2 class="mb-4">Dashboard Overview</h2>

                <?php
                // Total Orders
                $totalOrders = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM orders"))['total'] ?? 0;
                
                // Revenue calculations - ONLY from completed/delivered orders
                $todayRevenue = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(total_amount), 0) as revenue FROM orders WHERE DATE(created_at)=CURDATE() AND status IN ('completed', 'delivered')"))['revenue'] ?? 0;
                $totalRevenue = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(total_amount), 0) as revenue FROM orders WHERE status IN ('completed', 'delivered')"))['revenue'] ?? 0;
                
                // Order statuses
                $completedOrders = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM orders WHERE status IN ('completed', 'delivered')"))['total'] ?? 0;
                $pendingOrders = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM orders WHERE status='pending'"))['total'] ?? 0;
                $preparingOrders = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM orders WHERE status IN ('confirmed', 'preparing', 'ready')"))['total'] ?? 0;
                
                // Other metrics
                $totalUsers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM customers"))['total'] ?? 0;
                $totalMenuItems = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM menu_items"))['total'] ?? 0;
                $totalReservations = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM reservations"))['total'] ?? 0;
                $totalInquiries = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM contact_submissions"))['total'] ?? 0;
                ?>

                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card bg-primary text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Total Orders</h6>
                                        <h2><?= $totalOrders ?></h2>
                                    </div>
                                    <i class="fas fa-shopping-cart fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card bg-success text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Today's Revenue <small>(Completed)</small></h6>
                                        <h2>₹<?= number_format($todayRevenue, 2) ?></h2>
                                    </div>
                                    <i class="fas fa-rupee-sign fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card bg-info text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Total Users</h6>
                                        <h2><?= $totalUsers ?></h2>
                                    </div>
                                    <i class="fas fa-users fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card bg-warning text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Pending Orders</h6>
                                        <h2><?= $pendingOrders ?></h2>
                                    </div>
                                    <i class="fas fa-clock fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card bg-primary text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Preparing Orders</h6>
                                        <h2><?= $preparingOrders ?></h2>
                                    </div>
                                    <i class="fas fa-spinner fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card bg-dark text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Total Revenue (All Time)</h6>
                                        <h2>₹<?= number_format($totalRevenue, 2) ?></h2>
                                    </div>
                                    <i class="fas fa-chart-bar fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card bg-secondary text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Contact Inquiries</h6>
                                        <h2><?= $totalInquiries ?></h2>
                                    </div>
                                    <i class="fas fa-envelope fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Recent Orders -->
                    <div class="col-md-8 mb-4">
                        <div class="card">
                            <div class="card-header bg-dark text-white">
                                <h5 class="mb-0"><i class="fas fa-list me-2"></i>Recent Orders</h5>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead style="background: #f8f9fa;">
                                        <tr>
                                            <th>Order ID</th>
                                            <th>Customer</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                            <th>Details</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $orders = mysqli_query($conn, "SELECT * FROM orders ORDER BY created_at DESC LIMIT 5");
                                        if ($orders && mysqli_num_rows($orders) > 0) {
                                            while ($order = mysqli_fetch_assoc($orders)) {
                                                $status_class = $order['status'] === 'completed' ? 'success' : ($order['status'] === 'pending' ? 'warning' : 'info');
                                                $dash_detail_id = 'dash-detail-' . $order['id'];

                                                // Decode items
                                                $items_json = $order['items'] ?? '[]';
                                                $items_arr = json_decode($items_json, true);
                                                if (!is_array($items_arr)) $items_arr = [];
                                                $payment_display = htmlspecialchars(ucwords(str_replace('_', ' ', $order['payment_method'] ?? 'N/A')));

                                                echo "<tr>
                                                    <td><strong>#{$order['id']}</strong></td>
                                                    <td>{$order['customer_name']}</td>
                                                    <td>₹{$order['total_amount']}</td>
                                                    <td><span class='badge bg-{$status_class}'>" . ucfirst($order['status']) . "</span></td>
                                                    <td>" . date('d M Y', strtotime($order['created_at'])) . "</td>
                                                    <td>
                                                        <button type='button' class='btn btn-sm btn-outline-primary order-view-btn' data-target='$dash_detail_id'>
                                                            <i class='fas fa-eye me-1'></i>View
                                                        </button>
                                                    </td>
                                                </tr>";

                                                // Inline detail row
                                                echo "<tr id='$dash_detail_id' style='display:none;'>
                                                    <td colspan='6'>
                                                        <div class='p-3 bg-light rounded border'>
                                                            <div class='d-flex justify-content-between align-items-center mb-2'>
                                                                <h6 class='mb-0 fw-bold'><i class='fas fa-receipt me-2'></i>Order #{$order['id']} — Details</h6>
                                                                <button type='button' class='btn btn-sm btn-outline-secondary order-close-btn' data-target='$dash_detail_id'><i class='fas fa-times me-1'></i>Close</button>
                                                            </div>
                                                            <div class='row mb-2'>
                                                                <div class='col-md-6'>
                                                                    <strong>Customer:</strong> {$order['customer_name']}<br>
                                                                    <small class='text-muted'>{$order['email']} &bull; {$order['phone']}</small>
                                                                </div>
                                                                <div class='col-md-6'>
                                                                    <strong>Payment:</strong> $payment_display &bull;
                                                                    <span class='badge bg-{$status_class}'>" . ucfirst($order['status']) . "</span>
                                                                </div>
                                                            </div>";
                                                            if (count($items_arr) > 0) {
                                                                echo "<table class='table table-sm table-bordered mb-2 bg-white'>
                                                                    <thead class='table-light'>
                                                                        <tr><th>#</th><th>Item</th><th class='text-center'>Qty</th><th class='text-end'>Price</th><th class='text-end'>Total</th></tr>
                                                                    </thead><tbody>";
                                                                $n = 0;
                                                                foreach ($items_arr as $item) {
                                                                    $n++;
                                                                    $iname = htmlspecialchars($item['name'] ?? 'Unknown');
                                                                    $iqty = intval($item['quantity'] ?? 1);
                                                                    $iprice = floatval($item['price'] ?? 0);
                                                                    $itotal = $iprice * $iqty;
                                                                    echo "<tr><td>$n</td><td>$iname</td><td class='text-center'>$iqty</td><td class='text-end'>₹" . number_format($iprice,2) . "</td><td class='text-end'>₹" . number_format($itotal,2) . "</td></tr>";
                                                                }
                                                                echo "</tbody></table>";
                                                            } else {
                                                                echo "<p class='text-muted mb-1'>No item details available.</p>";
                                                            }
                                                            echo "<div class='text-end fw-bold'>Total: <span class='text-success'>₹" . number_format((float)$order['total_amount'], 2) . "</span></div>";
                                                echo "      </div>
                                                    </td>
                                                </tr>";
                                            }
                                        } else {
                                            echo "<tr><td colspan='6' class='text-center text-muted'>No orders yet</td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Stats -->
                    <div class="col-md-4 mb-4">
                        <div class="card">
                            <div class="card-header bg-dark text-white">
                                <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Quick Stats</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between py-2 border-bottom">
                                    <span>Menu Items</span>
                                    <strong><?= $totalMenuItems ?></strong>
                                </div>
                                <div class="d-flex justify-content-between py-2 border-bottom">
                                    <span>Reservations</span>
                                    <strong><?= $totalReservations ?></strong>
                                </div>
                                <div class="d-flex justify-content-between py-2 border-bottom">
                                    <span>Total Users</span>
                                    <strong><?= $totalUsers ?></strong>
                                </div>
                                <div class="d-flex justify-content-between py-2 border-bottom">
                                    <span>Contact Inquiries</span>
                                    <strong><?= $totalInquiries ?></strong>
                                </div>
                                <div class="d-flex justify-content-between py-2">
                                    <span>Pending Orders</span>
                                    <strong class="text-warning"><?= $pendingOrders ?></strong>
                                </div>
                            </div>
                        </div>
                        <div class="card mt-4">
                            <div class="card-header bg-dark text-white">
                                <h5 class="mb-0"><i class="fas fa-envelope-open-text me-2"></i>Recent Inquiries</h5>
                            </div>
                            <div class="card-body">
                                <?php
                                $inquiries = mysqli_query($conn, "SELECT name, message, created_at FROM contact_submissions ORDER BY created_at DESC LIMIT 5");
                                if ($inquiries && mysqli_num_rows($inquiries) > 0) {
                                    echo '<ul class="list-unstyled mb-0">';
                                    while ($inquiry = mysqli_fetch_assoc($inquiries)) {
                                        $short_message = substr($inquiry['message'], 0, 60);
                                        if (strlen($inquiry['message']) > 60) {
                                            $short_message .= '...';
                                        }
                                        echo '<li class="mb-3">'
                                            . '<div class="fw-bold">' . htmlspecialchars($inquiry['name']) . '</div>'
                                            . '<div class="text-muted small">' . htmlspecialchars($short_message) . '</div>'
                                            . '<div class="text-muted small">' . date('d M Y, h:i A', strtotime($inquiry['created_at'])) . '</div>'
                                            . '</li>';
                                    }
                                    echo '</ul>';
                                } else {
                                    echo '<div class="text-muted">No inquiries yet.</div>';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12 mb-4">
                        <div class="card">
                            <div class="card-header bg-dark text-white">
                                <h5 class="mb-0"><i class="fas fa-envelope me-2"></i>Contact Inquiries</h5>
                            </div>
                            <div class="card-body">
                                <?php if ($inquiry_notice): ?>
                                    <div class="alert alert-success">
                                        <?php echo htmlspecialchars($inquiry_notice); ?>
                                    </div>
                                <?php endif; ?>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead style="background: #f8f9fa;">
                                            <tr>
                                                <th>ID</th>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>Phone</th>
                                                <th>Status</th>
                                                <th>Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $inquiries_full = mysqli_query($conn, "SELECT * FROM contact_submissions ORDER BY created_at DESC LIMIT 20");
                                            if ($inquiries_full && mysqli_num_rows($inquiries_full) > 0) {
                                                while ($inquiry = mysqli_fetch_assoc($inquiries_full)) {
                                                    $status_color = $inquiry['status'] === 'new' ? 'danger' : ($inquiry['status'] === 'processing' ? 'warning' : 'success');
                                                    $preview = substr($inquiry['message'], 0, 60);
                                                    if (strlen($inquiry['message']) > 60) {
                                                        $preview .= '...';
                                                    }
                                                    $collapse_id = 'inquiry-' . (int) $inquiry['id'];
                                                    echo "<tr>
                                                        <td><strong>#{$inquiry['id']}</strong></td>
                                                        <td>" . htmlspecialchars($inquiry['name']) . "</td>
                                                        <td>" . htmlspecialchars($inquiry['email']) . "</td>
                                                        <td>" . htmlspecialchars($inquiry['phone']) . "</td>
                                                        <td><span class='badge bg-$status_color'>" . ucfirst($inquiry['status']) . "</span></td>
                                                        <td>" . date('d M Y, h:i A', strtotime($inquiry['created_at'])) . "</td>
                                                        <td>
                                                            <button class='btn btn-sm btn-outline-secondary me-2' type='button' data-bs-toggle='collapse' data-bs-target='#$collapse_id' aria-expanded='false'>View</button>
                                                            <form method='post' style='display:inline-block;'>
                                                                <input type='hidden' name='inquiry_id' value='{$inquiry['id']}'>
                                                                <button type='submit' name='delete_inquiry' class='btn btn-sm btn-outline-danger' onclick=return confirm('Delete this inquiry?');'>Delete</button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                    <tr class='collapse' id='$collapse_id'>
                                                        <td colspan='7'>
                                                            <strong>Message:</strong> " . htmlspecialchars($inquiry['message']) . "
                                                            <div class='text-muted small mt-2'>Preview: " . htmlspecialchars($preview) . "</div>
                                                        </td>
                                                    </tr>";
                                                }
                                            } else {
                                                echo "<tr><td colspan='7' class='text-center text-muted'>No inquiries found</td></tr>";
                                            }
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
