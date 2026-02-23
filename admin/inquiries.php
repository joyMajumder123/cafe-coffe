<?php 
include 'includes/auth.php';
include 'includes/db.php';
require_permission('inquiries.view');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Inquiries | Cafe Admin</title>
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
            <span class="navbar-brand">☕ Cafe Admin - Contact Inquiries</span>
            <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a href="dashboard.php" class="nav-link">📊 Dashboard</a>
                    </li>
                    <li class="nav-item"><a href="reports.php" class="nav-link">📈 Reports</a></li>
                    <li class="nav-item">
                        <a href="orders.php" class="nav-link">📦 Orders</a>
                    </li>
                    <li class="nav-item">
                        <a href="inquiries.php" class="nav-link active">💬 Inquiries</a>
                    </li>
                    <li class="nav-item">
                        <a href="menu.php" class="nav-link">🍽️ Menu</a>
                    </li>
                    <li class="nav-item">
                        <a href="categories.php" class="nav-link">📂 Categories</a>
                    </li>
                    <li class="nav-item">
                        <a href="reservation.php" class="nav-link">📅 Reservations</a>
                    </li>
                    <li class="nav-item">
                        <a href="staff.php" class="nav-link">👥 Staff</a>
                    </li>
                   
                </ul>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 p-4">
                <h2 class="mb-4">Contact Inquiries Management</h2>

                <?php
                // Handle status update for inquiries
                if (isset($_POST['update_inquiry_status'])) {
                    $inquiry_id = intval($_POST['inquiry_id']);
                    $status = $_POST['inquiry_status'];
                    $stmt = $conn->prepare("UPDATE contact_submissions SET status = ? WHERE id = ?");
                    $stmt->bind_param("si", $status, $inquiry_id);
                    if ($stmt->execute()) {
                        echo "<div class='alert alert-success alert-dismissible fade show'>
                            Inquiry status updated successfully!
                            <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                        </div>";
                    }
                    $stmt->close();
                }

                // Handle delete inquiry
                if (isset($_POST['delete_inquiry'])) {
                    $inquiry_id = intval($_POST['inquiry_id']);
                    $stmt = $conn->prepare("DELETE FROM contact_submissions WHERE id = ?");
                    $stmt->bind_param("i", $inquiry_id);
                    if ($stmt->execute()) {
                        echo "<div class='alert alert-danger alert-dismissible fade show'>
                            Inquiry deleted successfully!
                            <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                        </div>";
                    }
                    $stmt->close();
                }

                $totalInquiries = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM contact_submissions"))['total'] ?? 0;
                $newInquiries = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM contact_submissions WHERE status='new'"))['total'] ?? 0;
                $processingInquiries = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM contact_submissions WHERE status='processing'"))['total'] ?? 0;

                echo "<div class='row mb-4'>
                    <div class='col-md-4'>
                        <div class='card bg-info text-white'>
                            <div class='card-body'>
                                <h6 class='card-title'>Total Inquiries</h6>
                                <h3>$totalInquiries</h3>
                            </div>
                        </div>
                    </div>
                    <div class='col-md-4'>
                        <div class='card bg-danger text-white'>
                            <div class='card-body'>
                                <h6 class='card-title'>New Inquiries</h6>
                                <h3>$newInquiries</h3>
                            </div>
                        </div>
                    </div>
                    <div class='col-md-4'>
                        <div class='card bg-warning text-white'>
                            <div class='card-body'>
                                <h6 class='card-title'>Processing</h6>
                                <h3>$processingInquiries</h3>
                            </div>
                        </div>
                    </div>
                </div>";
                ?>

                <div class="card">
                    <div class="card-header">
                        <h5>Contact Form Submissions</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead style="background: #f8f9fa;">
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Persons</th>
                                    <th>Message</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $inquiries = mysqli_query($conn, "SELECT * FROM contact_submissions ORDER BY created_at DESC");
                                if ($inquiries && mysqli_num_rows($inquiries) > 0) {
                                    while ($inquiry = mysqli_fetch_assoc($inquiries)) {
                                        $status_color = $inquiry['status'] === 'new' ? 'danger' : 
                                                       ($inquiry['status'] === 'processing' ? 'warning' : 'success');
                                        $message = substr($inquiry['message'], 0, 50) . (strlen($inquiry['message']) > 50 ? '...' : '');
                                        echo "<tr>
                                            <td><strong>#{$inquiry['id']}</strong></td>
                                            <td>{$inquiry['name']}</td>
                                            <td>{$inquiry['email']}</td>
                                            <td>{$inquiry['phone']}</td>
                                            <td>{$inquiry['persons']}</td>
                                            <td><small title='{$inquiry['message']}'>{$message}</small></td>
                                            <td><span class='badge bg-$status_color'>" . ucfirst($inquiry['status']) . "</span></td>
                                            <td>" . date('d M Y, h:i A', strtotime($inquiry['created_at'])) . "</td>
                                            <td>
                                                <div class='d-flex gap-2'>
                                                    <button type='button' class='btn btn-sm btn-outline-primary' data-bs-toggle='modal' data-bs-target='#viewModal{$inquiry['id']}'><i class='fas fa-eye'></i></button>
                                                    <form method='POST' style='display: inline;'>
                                                        <input type='hidden' name='inquiry_id' value='{$inquiry['id']}'>
                                                        <select name='inquiry_status' class='form-select form-select-sm' onchange='this.form.submit()'>
                                                            <option value='new' " . ($inquiry['status'] === 'new' ? 'selected' : '') . ">New</option>
                                                            <option value='processing' " . ($inquiry['status'] === 'processing' ? 'selected' : '') . ">Processing</option>
                                                            <option value='resolved' " . ($inquiry['status'] === 'resolved' ? 'selected' : '') . ">Resolved</option>
                                                        </select>
                                                        <input type='hidden' name='update_inquiry_status' value='1'>
                                                    </form>
                                                    <form method='POST' onsubmit=\"return confirm('Are you sure you want to delete this inquiry?');\" style='display: inline;'>
                                                        <input type='hidden' name='inquiry_id' value='{$inquiry['id']}'>
                                                        <button type='submit' name='delete_inquiry' class='btn btn-sm btn-outline-danger' title='Delete inquiry'>
                                                            <i class='fas fa-trash'></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>";

                                        // View Modal
                                        echo "
                                        <div class='modal fade' id='viewModal{$inquiry['id']}' tabindex='-1'>
                                            <div class='modal-dialog'>
                                                <div class='modal-content'>
                                                    <div class='modal-header'>
                                                        <h5 class='modal-title'>Inquiry Details</h5>
                                                        <button type='button' class='btn-close' data-bs-dismiss='modal'></button>
                                                    </div>
                                                    <div class='modal-body'>
                                                        <p><strong>Name:</strong> {$inquiry['name']}</p>
                                                        <p><strong>Email:</strong> {$inquiry['email']}</p>
                                                        <p><strong>Phone:</strong> {$inquiry['phone']}</p>
                                                        <p><strong>Persons:</strong> {$inquiry['persons']}</p>
                                                        <p><strong>Location:</strong> {$inquiry['location']}</p>
                                                        <p><strong>Message:</strong></p>
                                                        <p style='background: #f8f9fa; padding: 10px; border-radius: 5px;'>{$inquiry['message']}</p>
                                                        <p><strong>Submitted:</strong> " . date('d M Y, h:i A', strtotime($inquiry['created_at'])) . "</p>
                                                    </div>
                                                    <div class='modal-footer'>
                                                        <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Close</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>";
                                    }
                                } else {
                                    echo "<tr><td colspan='9' class='text-center text-muted'>No inquiries found</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/order-notifications.js"></script>
</body>
</html>
