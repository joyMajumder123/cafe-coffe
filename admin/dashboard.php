<?php 
session_start();
if(!isset($_SESSION['admin'])){
    header("Location: login.php");
    exit();
}
include 'includes/db.php'; 



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
            <div class="col-md-2 sidebar">
                <ul class="nav flex-column">
                    <li class="nav-item"><a href="dashboard.php" class="nav-link">📊 Dashboard</a></li>
                    <li class="nav-item"><a href="reports.php" class="nav-link">📈 Reports</a></li>
                    <li class="nav-item"><a href="orders.php" class="nav-link">📦 Orders</a></li>
                    <li class="nav-item"><a href="inquiries.php" class="nav-link">💬 Inquiries</a></li>
                    <li class="nav-item"><a href="menu.php" class="nav-link">🍽️ Menu</a></li>
                    <li class="nav-item"><a href="categories.php" class="nav-link active">📂 Categories</a></li>
                    <li class="nav-item"><a href="reservation.php" class="nav-link">📅 Reservations</a></li>
                    <li class="nav-item"><a href="staff.php" class="nav-link">👥 Staff</a></li>
                    
                </ul>
            </div>

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
                $totalUsers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users"))['total'] ?? 0;
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
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $orders = mysqli_query($conn, "SELECT * FROM orders ORDER BY created_at DESC LIMIT 5");
                                        if ($orders && mysqli_num_rows($orders) > 0) {
                                            while ($order = mysqli_fetch_assoc($orders)) {
                                                $status_class = $order['status'] === 'completed' ? 'success' : ($order['status'] === 'pending' ? 'warning' : 'info');
                                                echo "<tr>
                                                    <td><strong>#{$order['id']}</strong></td>
                                                    <td>{$order['customer_name']}</td>
                                                    <td>₹{$order['total_amount']}</td>
                                                    <td><span class='badge bg-{$status_class}'>" . ucfirst($order['status']) . "</span></td>
                                                    <td>" . date('d M Y', strtotime($order['created_at'])) . "</td>
                                                </tr>";
                                            }
                                        } else {
                                            echo "<tr><td colspan='5' class='text-center text-muted'>No orders yet</td></tr>";
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
</body>
</html>
