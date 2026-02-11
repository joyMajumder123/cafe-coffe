<?php 
session_start();
if(!isset($_SESSION['admin'])){
    header("Location: login.php");
    exit();
}
include 'includes/db.php'; 

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
            <div class="col-md-2 sidebar">
                <ul class="nav flex-column">
                    <li class="nav-item"><a href="dashboard.php" class="nav-link">📊 Dashboard</a></li>
                    <li class="nav-item"><a href="reports.php" class="nav-link">📈 Reports</a></li>
                    <li class="nav-item"><a href="orders.php" class="nav-link">📦 Orders</a></li>
                    <li class="nav-item"><a href="inquiries.php" class="nav-link">💬 Inquiries</a></li>
                    <li class="nav-item"><a href="menu.php" class="nav-link">🍽️ Menu</a></li>
                    <li class="nav-item"><a href="categories.php" class="nav-link">📂 Categories</a></li>
                    <li class="nav-item"><a href="reservation.php" class="nav-link">📅 Reservations</a></li>
                    <li class="nav-item"><a href="staff.php" class="nav-link active">👥 Staff</a></li>
                
                </ul>
            </div>


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

                        $totalOrders = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM orders"))['total'] ?? 0;
                        $todayRevenue = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total_amount) as revenue FROM orders WHERE DATE(created_at)=CURDATE()"))['revenue'] ?? 0;

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
                                                echo "<tr>
                                                    <td><strong>#{$order['id']}</strong></td>
                                                    <td>{$order['customer_name']}</td>
                                                    <td>{$order['email']}</td>
                                                    <td>{$order['phone']}</td>
                                                    <td>₹{$order['total_amount']}</td>
                                                    <td><span class='badge bg-$status_color'>" . ucfirst($order['status']) . "</span></td>
                                                    <td>" . date('d M Y', strtotime($order['created_at'])) . "</td>
                                                    <td>
                                                        <div class='d-flex gap-2'>
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
</body>
</html>
