<?php
/**
 * Export Sales Report to Excel (XLS format)
 * Supports same filters as reports.php: date_from, date_to, status
 */
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}
include 'includes/db.php';

// --- Filter parameters ---
$date_from = $_GET['date_from'] ?? '';
$date_to   = $_GET['date_to']   ?? '';
$status    = $_GET['status']     ?? '';

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

// Fetch orders
$sql = "SELECT o.id, o.customer_name, o.email, o.phone, o.address, o.city,
               o.subtotal, o.delivery_charge, o.tax, o.total_amount,
               o.payment_method, o.status, o.created_at, o.items
        FROM orders o $where ORDER BY o.created_at DESC";
$stmt = $conn->prepare($sql);
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Summary stats
$totalOrders  = count($orders);
$totalRevenue = array_sum(array_column($orders, 'total_amount'));
$avgOrder     = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;

// Build filename
$dateSuffix = '';
if ($date_from) $dateSuffix .= '_from_' . $date_from;
if ($date_to)   $dateSuffix .= '_to_' . $date_to;
if ($status)    $dateSuffix .= '_' . $status;
$filename = 'Sales_Report' . $dateSuffix . '_' . date('Y-m-d_His') . '.xls';

// Set headers for Excel download
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

// Output Excel-compatible HTML table
echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel">';
echo '<head><meta charset="UTF-8"><!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets>';
echo '<x:ExcelWorksheet><x:Name>Sales Report</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions>';
echo '</x:ExcelWorksheet>';
echo '<x:ExcelWorksheet><x:Name>Summary</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions>';
echo '</x:ExcelWorksheet>';
echo '</x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]--></head>';
echo '<body>';

// ---- Sheet 1: Summary ----
echo '<table border="1">';
echo '<tr><td colspan="4" style="font-size:18px;font-weight:bold;background:#2c3e50;color:#fff;">☕ Cafe Sales Report Summary</td></tr>';
echo '<tr><td colspan="4"></td></tr>';

// Filter info
echo '<tr><td style="font-weight:bold;">Filters Applied:</td><td colspan="3">';
$filterDesc = [];
if ($date_from) $filterDesc[] = "From: $date_from";
if ($date_to)   $filterDesc[] = "To: $date_to";
if ($status)    $filterDesc[] = "Status: " . ucfirst($status);
echo $filterDesc ? implode(' | ', $filterDesc) : 'None (All Data)';
echo '</td></tr>';
echo '<tr><td style="font-weight:bold;">Generated:</td><td colspan="3">' . date('d M Y, h:i A') . '</td></tr>';
echo '<tr><td colspan="4"></td></tr>';

// Summary stats
echo '<tr style="background:#198754;color:#fff;font-weight:bold;"><td>Metric</td><td>Value</td><td colspan="2"></td></tr>';
echo '<tr><td>Total Orders</td><td>' . number_format($totalOrders) . '</td><td colspan="2"></td></tr>';
echo '<tr><td>Total Revenue</td><td>₹' . number_format($totalRevenue, 2) . '</td><td colspan="2"></td></tr>';
echo '<tr><td>Average Order Value</td><td>₹' . number_format($avgOrder, 2) . '</td><td colspan="2"></td></tr>';
echo '<tr><td colspan="4"></td></tr>';

// Status breakdown
$statusCounts = [];
foreach ($orders as $o) {
    $s = $o['status'];
    if (!isset($statusCounts[$s])) $statusCounts[$s] = ['cnt' => 0, 'revenue' => 0];
    $statusCounts[$s]['cnt']++;
    $statusCounts[$s]['revenue'] += $o['total_amount'];
}
echo '<tr style="background:#0d6efd;color:#fff;font-weight:bold;"><td>Status</td><td>Orders</td><td>Revenue</td><td></td></tr>';
foreach ($statusCounts as $s => $d) {
    echo '<tr><td>' . ucfirst($s) . '</td><td>' . $d['cnt'] . '</td><td>₹' . number_format($d['revenue'], 2) . '</td><td></td></tr>';
}
echo '<tr><td colspan="4"></td></tr>';

// Payment method breakdown
$payCounts = [];
foreach ($orders as $o) {
    $p = $o['payment_method'] ?? 'N/A';
    if (!isset($payCounts[$p])) $payCounts[$p] = ['cnt' => 0, 'revenue' => 0];
    $payCounts[$p]['cnt']++;
    $payCounts[$p]['revenue'] += $o['total_amount'];
}
echo '<tr style="background:#6f42c1;color:#fff;font-weight:bold;"><td>Payment Method</td><td>Orders</td><td>Revenue</td><td></td></tr>';
foreach ($payCounts as $p => $d) {
    echo '<tr><td>' . ucwords(str_replace('_', ' ', $p)) . '</td><td>' . $d['cnt'] . '</td><td>₹' . number_format($d['revenue'], 2) . '</td><td></td></tr>';
}
echo '</table>';

// Page break for second sheet
echo '<br pagebreak="true">';

// ---- Sheet 2: Order Details ----
echo '<table border="1">';
echo '<tr style="background:#2c3e50;color:#fff;font-weight:bold;">';
echo '<td>Order ID</td><td>Customer Name</td><td>Email</td><td>Phone</td>';
echo '<td>Address</td><td>City</td><td>Items</td>';
echo '<td>Subtotal</td><td>Tax</td><td>Delivery</td><td>Total Amount</td>';
echo '<td>Payment Method</td><td>Status</td><td>Order Date</td>';
echo '</tr>';

foreach ($orders as $order) {
    // Parse items JSON to readable string
    $itemsList = '';
    $items = json_decode($order['items'], true);
    if (is_array($items)) {
        $parts = [];
        foreach ($items as $item) {
            $qty  = $item['quantity'] ?? $item['qty'] ?? 1;
            $name = $item['name'] ?? 'Unknown';
            $parts[] = "$name x$qty";
        }
        $itemsList = implode(', ', $parts);
    }

    echo '<tr>';
    echo '<td>' . $order['id'] . '</td>';
    echo '<td>' . htmlspecialchars($order['customer_name']) . '</td>';
    echo '<td>' . htmlspecialchars($order['email'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($order['phone'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($order['address'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($order['city'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($itemsList) . '</td>';
    echo '<td>' . number_format($order['subtotal'] ?? 0, 2) . '</td>';
    echo '<td>' . number_format($order['tax'] ?? 0, 2) . '</td>';
    echo '<td>' . number_format($order['delivery_charge'] ?? 0, 2) . '</td>';
    echo '<td>' . number_format($order['total_amount'], 2) . '</td>';
    echo '<td>' . ucwords(str_replace('_', ' ', $order['payment_method'] ?? 'N/A')) . '</td>';
    echo '<td>' . ucfirst($order['status']) . '</td>';
    echo '<td>' . date('d M Y, h:i A', strtotime($order['created_at'])) . '</td>';
    echo '</tr>';
}

if (empty($orders)) {
    echo '<tr><td colspan="14" style="text-align:center;">No orders found for the selected criteria</td></tr>';
}

echo '</table>';
echo '</body></html>';
exit();
