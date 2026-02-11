<?php 
session_start();
if(!isset($_SESSION['admin'])){
    header("Location: login.php");
    exit();
}
include 'includes/db.php'; 

// --- Filter parameters ---
$date_from  = $_GET['date_from']  ?? '';
$date_to    = $_GET['date_to']    ?? '';
$status     = $_GET['status']     ?? '';

// Build WHERE clause
$where  = "WHERE 1=1";
$params = [];
$types  = '';

if ($date_from) {
    $where .= " AND DATE(o.created_at) >= ?";
    $params[] = $date_from;
    $types   .= 's';
}
if ($date_to) {
    $where .= " AND DATE(o.created_at) <= ?";
    $params[] = $date_to;
    $types   .= 's';
}
if ($status) {
    $where .= " AND o.status = ?";
    $params[] = $status;
    $types   .= 's';
}

// For revenue calculations, only count completed/delivered orders (unless explicitly filtering by other status)
$where_revenue = $where;
if (!$status) {
    // If no specific status filter, restrict to completed/delivered for revenue
    $where_revenue .= " AND o.status IN ('completed', 'delivered')";
}

// --- Summary stats (filtered) ---
$sql_total   = "SELECT COUNT(*) AS cnt FROM orders o $where";
$sql_revenue = "SELECT COALESCE(SUM(o.total_amount),0) AS revenue FROM orders o $where_revenue";
$sql_avg     = "SELECT COALESCE(AVG(o.total_amount),0) AS avg_val FROM orders o $where_revenue";

// Helper to run a prepared statement with dynamic params
function run_stat($conn, $sql, $types, $params) {
    $stmt = $conn->prepare($sql);
    if ($types) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $result;
}

$totalOrders   = run_stat($conn, $sql_total, $types, $params)['cnt'];
$totalRevenue  = run_stat($conn, $sql_revenue, $types, $params)['revenue'];
$avgOrder      = run_stat($conn, $sql_avg, $types, $params)['avg_val'];

// Status breakdown
$sql_status = "SELECT o.status, COUNT(*) AS cnt, COALESCE(SUM(o.total_amount),0) AS revenue 
               FROM orders o $where GROUP BY o.status ORDER BY cnt DESC";
$stmt_s = $conn->prepare($sql_status);
if ($types) $stmt_s->bind_param($types, ...$params);
$stmt_s->execute();
$statusBreakdown = $stmt_s->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_s->close();

// Payment method breakdown (for completed/delivered orders)
$sql_pay = "SELECT o.payment_method, COUNT(*) AS cnt, COALESCE(SUM(o.total_amount),0) AS revenue 
            FROM orders o $where_revenue GROUP BY o.payment_method ORDER BY cnt DESC";
$stmt_p = $conn->prepare($sql_pay);
if ($types) $stmt_p->bind_param($types, ...$params);
$stmt_p->execute();
$payBreakdown = $stmt_p->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_p->close();

// Daily revenue trend (for completed/delivered orders)
$sql_daily = "SELECT DATE(o.created_at) AS day, COUNT(*) AS cnt, SUM(o.total_amount) AS revenue 
              FROM orders o $where_revenue GROUP BY DATE(o.created_at) ORDER BY day DESC LIMIT 30";
$stmt_d = $conn->prepare($sql_daily);
if ($types) $stmt_d->bind_param($types, ...$params);
$stmt_d->execute();
$dailyTrend = $stmt_d->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_d->close();

// Top selling items (from completed/delivered orders)
$sql_items = "SELECT o.items FROM orders o $where_revenue";
$stmt_i = $conn->prepare($sql_items);
if ($types) $stmt_i->bind_param($types, ...$params);
$stmt_i->execute();
$itemsResult = $stmt_i->get_result();
$itemSales = [];
while ($row = $itemsResult->fetch_assoc()) {
    $items = json_decode($row['items'], true);
    if (is_array($items)) {
        foreach ($items as $item) {
            $name = $item['name'] ?? 'Unknown';
            $qty  = (int)($item['quantity'] ?? $item['qty'] ?? 1);
            $price = (float)($item['price'] ?? 0);
            if (!isset($itemSales[$name])) {
                $itemSales[$name] = ['qty' => 0, 'revenue' => 0];
            }
            $itemSales[$name]['qty']     += $qty;
            $itemSales[$name]['revenue'] += $qty * $price;
        }
    }
}
$stmt_i->close();
arsort($itemSales);
$topItems = array_slice($itemSales, 0, 10, true);

// Orders list for table
$sql_orders = "SELECT o.* FROM orders o $where ORDER BY o.created_at DESC LIMIT 500";
$stmt_o = $conn->prepare($sql_orders);
if ($types) $stmt_o->bind_param($types, ...$params);
$stmt_o->execute();
$orders = $stmt_o->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_o->close();

// Build export URL with same filters
$exportParams = http_build_query(array_filter([
    'date_from' => $date_from,
    'date_to'   => $date_to,
    'status'    => $status,
]));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Reports | Cafe Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        body { background-color: #f8f9fa; }
        .sidebar { background-color: #2c3e50; min-height: 100vh; padding: 20px 0; }
        .sidebar .nav-link { color: #ecf0f1; padding: 12px 20px; margin: 5px 0; }
        .sidebar .nav-link:hover { background-color: #34495e; border-radius: 5px; }
        .sidebar .nav-link.active { background-color: #3498db; border-radius: 5px; }
        .stat-card { border: none; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
        .stat-card .card-body { padding: 20px 24px; }
        .filter-bar { background: #fff; border-radius: 10px; padding: 20px; box-shadow: 0 1px 4px rgba(0,0,0,.06); }
        .table th { font-weight: 600; font-size: .85rem; text-transform: uppercase; letter-spacing: .5px; }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark bg-dark">
        <div class="container-fluid">
            <span class="navbar-brand">☕ Cafe Admin - Sales Reports</span>
            <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar">
                <ul class="nav flex-column">
                    <li class="nav-item"><a href="dashboard.php" class="nav-link">📊 Dashboard</a></li>
                    <li class="nav-item"><a href="reports.php" class="nav-link active">📈 Reports</a></li>
                    <li class="nav-item"><a href="orders.php" class="nav-link">📦 Orders</a></li>
                    <li class="nav-item"><a href="inquiries.php" class="nav-link">💬 Inquiries</a></li>
                    <li class="nav-item"><a href="menu.php" class="nav-link">🍽️ Menu</a></li>
                    <li class="nav-item"><a href="categories.php" class="nav-link">📂 Categories</a></li>
                    <li class="nav-item"><a href="reservation.php" class="nav-link">📅 Reservations</a></li>
                    <li class="nav-item"><a href="staff.php" class="nav-link">👥 Staff</a></li>
                    
                </ul>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 p-4">
                <h2 class="mb-4"><i class="fas fa-chart-line me-2"></i>Sales Reports</h2>

                <!-- Filter Bar -->
                <div class="filter-bar mb-4">
                    <form method="GET" class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Date From</label>
                            <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($date_from) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Date To</label>
                            <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($date_to) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Order Status</label>
                            <select name="status" class="form-select">
                                <option value="">All Statuses</option>
                                <?php 
                                $statuses = ['pending','confirmed','preparing','ready','completed'];
                                foreach ($statuses as $s): ?>
                                    <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-filter me-1"></i> Filter</button>
                            <a href="reports.php" class="btn btn-outline-secondary"><i class="fas fa-times me-1"></i> Reset</a>
                            <a href="export_report.php?<?= $exportParams ?>" class="btn btn-success"><i class="fas fa-file-excel me-1"></i> Export</a>
                        </div>
                    </form>
                </div>

                <!-- Summary Stat Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card stat-card bg-primary text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="text-uppercase small opacity-75">Total Orders</div>
                                        <h2 class="mb-0"><?= number_format($totalOrders) ?></h2>
                                    </div>
                                    <i class="fas fa-shopping-bag fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card stat-card bg-success text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="text-uppercase small opacity-75">Total Revenue</div>
                                        <h2 class="mb-0">₹<?= number_format($totalRevenue, 2) ?></h2>
                                    </div>
                                    <i class="fas fa-rupee-sign fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card stat-card bg-info text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="text-uppercase small opacity-75">Avg. Order Value</div>
                                        <h2 class="mb-0">₹<?= number_format($avgOrder, 2) ?></h2>
                                    </div>
                                    <i class="fas fa-calculator fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card stat-card bg-warning text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="text-uppercase small opacity-75">Items Sold</div>
                                        <h2 class="mb-0"><?= number_format(array_sum(array_column($itemSales, 'qty'))) ?></h2>
                                    </div>
                                    <i class="fas fa-utensils fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="row mb-4">
                    <!-- Daily Revenue Chart -->
                    <div class="col-md-8 mb-3">
                        <div class="card h-100">
                            <div class="card-header bg-dark text-white"><h6 class="mb-0"><i class="fas fa-chart-area me-2"></i>Daily Revenue Trend</h6></div>
                            <div class="card-body">
                                <canvas id="revenueChart" height="280"></canvas>
                            </div>
                        </div>
                    </div>
                    <!-- Status Breakdown Pie -->
                    <div class="col-md-4 mb-3">
                        <div class="card h-100">
                            <div class="card-header bg-dark text-white"><h6 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Orders by Status</h6></div>
                            <div class="card-body d-flex align-items-center justify-content-center">
                                <canvas id="statusChart" height="260"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Breakdown Tables Row -->
                <div class="row mb-4">
                    <!-- Status Breakdown -->
                    <div class="col-md-4 mb-3">
                        <div class="card h-100">
                            <div class="card-header bg-dark text-white"><h6 class="mb-0">Status Breakdown</h6></div>
                            <div class="table-responsive">
                                <table class="table table-sm mb-0">
                                    <thead><tr><th>Status</th><th>Orders</th><th>Revenue</th></tr></thead>
                                    <tbody>
                                        <?php foreach ($statusBreakdown as $sb): 
                                            $colors = ['pending'=>'warning','confirmed'=>'info','preparing'=>'secondary','ready'=>'primary','completed'=>'success'];
                                            $badge = $colors[$sb['status']] ?? 'dark';
                                        ?>
                                        <tr>
                                            <td><span class="badge bg-<?= $badge ?>"><?= ucfirst($sb['status']) ?></span></td>
                                            <td><?= $sb['cnt'] ?></td>
                                            <td>₹<?= number_format($sb['revenue'], 2) ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php if (empty($statusBreakdown)): ?>
                                        <tr><td colspan="3" class="text-muted text-center">No data</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <!-- Payment Method Breakdown -->
                    <div class="col-md-4 mb-3">
                        <div class="card h-100">
                            <div class="card-header bg-dark text-white"><h6 class="mb-0">Payment Methods</h6></div>
                            <div class="table-responsive">
                                <table class="table table-sm mb-0">
                                    <thead><tr><th>Method</th><th>Orders</th><th>Revenue</th></tr></thead>
                                    <tbody>
                                        <?php foreach ($payBreakdown as $pb): ?>
                                        <tr>
                                            <td><?= ucwords(str_replace('_', ' ', $pb['payment_method'])) ?></td>
                                            <td><?= $pb['cnt'] ?></td>
                                            <td>₹<?= number_format($pb['revenue'], 2) ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php if (empty($payBreakdown)): ?>
                                        <tr><td colspan="3" class="text-muted text-center">No data</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <!-- Top Selling Items -->
                    <div class="col-md-4 mb-3">
                        <div class="card h-100">
                            <div class="card-header bg-dark text-white"><h6 class="mb-0">Top 10 Items</h6></div>
                            <div class="table-responsive">
                                <table class="table table-sm mb-0">
                                    <thead><tr><th>Item</th><th>Qty</th><th>Revenue</th></tr></thead>
                                    <tbody>
                                        <?php foreach ($topItems as $name => $data): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($name) ?></td>
                                            <td><?= $data['qty'] ?></td>
                                            <td>₹<?= number_format($data['revenue'], 2) ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php if (empty($topItems)): ?>
                                        <tr><td colspan="3" class="text-muted text-center">No data</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Orders Table -->
                <div class="card mb-4">
                    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="fas fa-table me-2"></i>Order Details (<?= count($orders) ?> records)</h6>
                        <a href="export_report.php?<?= $exportParams ?>" class="btn btn-sm btn-success"><i class="fas fa-file-excel me-1"></i> Export to Excel</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0" id="ordersTable">
                            <thead style="background: #f8f9fa;">
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Subtotal</th>
                                    <th>Tax</th>
                                    <th>Delivery</th>
                                    <th>Total</th>
                                    <th>Payment</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($orders)): ?>
                                    <?php foreach ($orders as $order):
                                        $colors = ['pending'=>'warning','confirmed'=>'info','preparing'=>'secondary','ready'=>'primary','completed'=>'success'];
                                        $badge = $colors[$order['status']] ?? 'dark';
                                    ?>
                                    <tr>
                                        <td><strong>#<?= $order['id'] ?></strong></td>
                                        <td><?= htmlspecialchars($order['customer_name']) ?></td>
                                        <td><?= htmlspecialchars($order['email'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($order['phone'] ?? '') ?></td>
                                        <td>₹<?= number_format($order['subtotal'] ?? 0, 2) ?></td>
                                        <td>₹<?= number_format($order['tax'] ?? 0, 2) ?></td>
                                        <td>₹<?= number_format($order['delivery_charge'] ?? 0, 2) ?></td>
                                        <td><strong>₹<?= number_format($order['total_amount'], 2) ?></strong></td>
                                        <td><?= ucwords(str_replace('_', ' ', $order['payment_method'] ?? 'N/A')) ?></td>
                                        <td><span class="badge bg-<?= $badge ?>"><?= ucfirst($order['status']) ?></span></td>
                                        <td><?= date('d M Y, h:i A', strtotime($order['created_at'])) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="11" class="text-center text-muted py-4">No orders match the selected filters</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Daily Revenue Chart
    const dailyData = <?= json_encode(array_reverse($dailyTrend)) ?>;
    new Chart(document.getElementById('revenueChart'), {
        type: 'line',
        data: {
            labels: dailyData.map(d => d.day),
            datasets: [{
                label: 'Revenue (₹)',
                data: dailyData.map(d => parseFloat(d.revenue)),
                borderColor: '#198754',
                backgroundColor: 'rgba(25,135,84,.1)',
                fill: true,
                tension: 0.3,
                pointRadius: 4
            }, {
                label: 'Orders',
                data: dailyData.map(d => parseInt(d.cnt)),
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13,110,253,.1)',
                fill: false,
                tension: 0.3,
                pointRadius: 4,
                yAxisID: 'y1'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            scales: {
                y:  { beginAtZero: true, title: { display: true, text: 'Revenue (₹)' } },
                y1: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false }, title: { display: true, text: 'Orders' } }
            }
        }
    });

    // Status Pie Chart
    const statusData = <?= json_encode($statusBreakdown) ?>;
    const statusColors = { pending: '#ffc107', confirmed: '#0dcaf0', preparing: '#6c757d', ready: '#0d6efd', completed: '#198754' };
    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: statusData.map(s => s.status.charAt(0).toUpperCase() + s.status.slice(1)),
            datasets: [{
                data: statusData.map(s => parseInt(s.cnt)),
                backgroundColor: statusData.map(s => statusColors[s.status] || '#343a40')
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } }
        }
    });
    </script>
</body>
</html>
