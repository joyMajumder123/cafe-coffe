<?php 
include 'includes/auth.php';
include 'includes/db.php';
require_permission('reservations.view');

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_reservation') {
        $name = trim($_POST['customer_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $date = trim($_POST['reservation_date'] ?? '');
        $time = trim($_POST['reservation_time'] ?? '');
        $guests = intval($_POST['guests'] ?? 1);
        $requests = trim($_POST['special_requests'] ?? '');
        $status = trim($_POST['status'] ?? 'pending');

        if ($name === '' || $date === '' || $time === '') {
            $errors[] = 'Name, date, and time are required.';
        }

        if (empty($errors)) {
            $stmt = $conn->prepare("INSERT INTO reservations (customer_name, email, phone, reservation_date, reservation_time, guests, special_requests, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('ssssisss', $name, $email, $phone, $date, $time, $guests, $requests, $status);
            if ($stmt->execute()) {
                $success = 'Reservation added successfully.';
            } else {
                $errors[] = 'Failed to add reservation.';
            }
            $stmt->close();
        }
    }

    if ($action === 'update_status') {
        $id = intval($_POST['id'] ?? 0);
        $status = trim($_POST['status'] ?? 'pending');
        $stmt = $conn->prepare("UPDATE reservations SET status = ? WHERE id = ?");
        $stmt->bind_param('si', $status, $id);
        if ($stmt->execute()) {
            $success = 'Reservation status updated.';
        } else {
            $errors[] = 'Failed to update reservation status.';
        }
        $stmt->close();
    }

    if ($action === 'delete_reservation') {
        $id = intval($_POST['id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM reservations WHERE id = ?");
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            $success = 'Reservation deleted successfully.';
        } else {
            $errors[] = 'Failed to delete reservation.';
        }
        $stmt->close();
    }
}

$reservations = $conn->query("SELECT * FROM reservations ORDER BY reservation_date DESC, reservation_time DESC");
$totalReservations = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM reservations"))['total'] ?? 0;
$pendingReservations = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM reservations WHERE status='pending'"))['total'] ?? 0;
$confirmedReservations = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM reservations WHERE status='confirmed'"))['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservations | Cafe Admin</title>
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
            <span class="navbar-brand">☕ Cafe Admin - Reservations</span>
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
                    <li class="nav-item"><a href="reservation.php" class="nav-link active">📅 Reservations</a></li>
                    <li class="nav-item"><a href="staff.php" class="nav-link">👥 Staff</a></li>
                    
                </ul>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-calendar-alt me-2"></i>Reservations</h2>
                    <button class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#addReservationForm">
                        <i class="fas fa-plus me-1"></i> Add Reservation
                    </button>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?= htmlspecialchars(implode(' ', $errors)) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php elseif ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?= htmlspecialchars($success) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Stats -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card bg-primary text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Total Reservations</h6>
                                        <h2><?= $totalReservations ?></h2>
                                    </div>
                                    <i class="fas fa-calendar fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card bg-warning text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Pending</h6>
                                        <h2><?= $pendingReservations ?></h2>
                                    </div>
                                    <i class="fas fa-clock fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card bg-success text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Confirmed</h6>
                                        <h2><?= $confirmedReservations ?></h2>
                                    </div>
                                    <i class="fas fa-check-circle fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Add Reservation Form (Collapsible) -->
                <div class="collapse mb-4" id="addReservationForm">
                    <div class="card">
                        <div class="card-header bg-dark text-white">
                            <h5 class="mb-0"><i class="fas fa-plus me-2"></i>Add Reservation</h5>
                        </div>
                        <div class="card-body">
                            <form method="post">
                                <input type="hidden" name="action" value="add_reservation">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Customer Name <span class="text-danger">*</span></label>
                                        <input type="text" name="customer_name" class="form-control" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Email</label>
                                        <input type="email" name="email" class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Phone</label>
                                        <input type="text" name="phone" class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Date <span class="text-danger">*</span></label>
                                        <input type="date" name="reservation_date" class="form-control" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Time <span class="text-danger">*</span></label>
                                        <input type="time" name="reservation_time" class="form-control" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Guests</label>
                                        <input type="number" name="guests" class="form-control" min="1" value="1">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Status</label>
                                        <select name="status" class="form-select">
                                            <option value="pending">Pending</option>
                                            <option value="confirmed">Confirmed</option>
                                            <option value="rejected">Rejected</option>
                                            <option value="completed">Completed</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Special Requests</label>
                                        <textarea name="special_requests" class="form-control" rows="2"></textarea>
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Add Reservation</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Reservations Table -->
                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>All Reservations</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead style="background: #f8f9fa;">
                                <tr>
                                    <th>ID</th>
                                    <th>Customer</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Date / Time</th>
                                    <th>Guests</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($reservations && $reservations->num_rows > 0): ?>
                                    <?php while ($r = $reservations->fetch_assoc()):
                                        $badge = ['pending'=>'warning','confirmed'=>'success','rejected'=>'danger','completed'=>'info'][$r['status']] ?? 'secondary';
                                    ?>
                                    <tr>
                                        <td><strong>#<?= (int)$r['id'] ?></strong></td>
                                        <td><?= htmlspecialchars($r['customer_name']) ?></td>
                                        <td><?= htmlspecialchars($r['email'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($r['phone'] ?? '') ?></td>
                                        <td><?= date('d M Y', strtotime($r['reservation_date'])) ?> <span class="text-muted">|</span> <?= date('h:i A', strtotime($r['reservation_time'])) ?></td>
                                        <td><?= (int)$r['guests'] ?></td>
                                        <td><span class="badge bg-<?= $badge ?>"><?= ucfirst($r['status']) ?></span></td>
                                        <td>
                                            <form method="post" class="d-inline">
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                                <select name="status" class="form-select form-select-sm d-inline-block w-auto" onchange="this.form.submit()">
                                                    <option value="pending" <?= $r['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                                    <option value="confirmed" <?= $r['status'] === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                                    <option value="rejected" <?= $r['status'] === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                                                    <option value="completed" <?= $r['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                                                </select>
                                            </form>
                                            <form method="post" class="d-inline ms-1">
                                                <input type="hidden" name="action" value="delete_reservation">
                                                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this reservation?');" title="Delete"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="8" class="text-center text-muted py-4">No reservations found.</td></tr>
                                <?php endif; ?>
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