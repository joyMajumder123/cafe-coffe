<?php 
session_start();
if(!isset($_SESSION['admin'])){
    header("Location: login.php");
    exit();
}
include 'includes/db.php';

$notice = '';
$notice_type = 'success';

// Handle Add Staff
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_staff'])) {
    $name     = trim($_POST['name'] ?? '');
    $position = trim($_POST['position'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $status   = $_POST['staff_status'] ?? 'active';
    $hire_date = $_POST['hire_date'] ?? date('Y-m-d');

    if ($name === '') {
        $notice = 'Staff name is required.';
        $notice_type = 'danger';
    } else {
        $stmt = $conn->prepare("INSERT INTO staff (name, position, email, phone, status, hire_date) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $name, $position, $email, $phone, $status, $hire_date);
        if ($stmt->execute()) {
            $notice = 'Staff member added successfully!';
        } else {
            $notice = 'Failed to add staff member: ' . $stmt->error;
            $notice_type = 'danger';
        }
        $stmt->close();
    }
}

// Handle Edit Staff
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_staff'])) {
    $id       = intval($_POST['staff_id']);
    $name     = trim($_POST['name'] ?? '');
    $position = trim($_POST['position'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $status   = $_POST['staff_status'] ?? 'active';
    $hire_date = $_POST['hire_date'] ?? '';

    if ($name === '' || $id <= 0) {
        $notice = 'Invalid data provided.';
        $notice_type = 'danger';
    } else {
        $stmt = $conn->prepare("UPDATE staff SET name=?, position=?, email=?, phone=?, status=?, hire_date=? WHERE id=?");
        $stmt->bind_param("ssssssi", $name, $position, $email, $phone, $status, $hire_date, $id);
        if ($stmt->execute()) {
            $notice = 'Staff member updated successfully!';
        } else {
            $notice = 'Failed to update staff member.';
            $notice_type = 'danger';
        }
        $stmt->close();
    }
}

// Handle Delete Staff
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_staff'])) {
    $id = intval($_POST['staff_id']);
    if ($id > 0) {
        $stmt = $conn->prepare("DELETE FROM staff WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $notice = 'Staff member deleted successfully!';
        } else {
            $notice = 'Failed to delete staff member.';
            $notice_type = 'danger';
        }
        $stmt->close();
    }
}

// Fetch all staff
$staffResult = mysqli_query($conn, "SELECT * FROM staff ORDER BY created_at DESC");
$totalStaff  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM staff"))['total'] ?? 0;
$activeStaff = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM staff WHERE status='active'"))['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Management | Cafe Admin</title>
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
            <span class="navbar-brand">☕ Cafe Admin - Staff Management</span>
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
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-users me-2"></i>Staff Management</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStaffModal">
                        <i class="fas fa-plus me-1"></i> Add Staff
                    </button>
                </div>

                <?php if ($notice): ?>
                    <div class="alert alert-<?= $notice_type ?> alert-dismissible fade show">
                        <?= htmlspecialchars($notice) ?>
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
                                        <h6 class="card-title">Total Staff</h6>
                                        <h2><?= $totalStaff ?></h2>
                                    </div>
                                    <i class="fas fa-users fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card bg-success text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Active Staff</h6>
                                        <h2><?= $activeStaff ?></h2>
                                    </div>
                                    <i class="fas fa-user-check fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card bg-warning text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Inactive Staff</h6>
                                        <h2><?= $totalStaff - $activeStaff ?></h2>
                                    </div>
                                    <i class="fas fa-user-times fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Staff Table -->
                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>All Staff Members</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead style="background: #f8f9fa;">
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Position</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Status</th>
                                    <th>Hire Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($staffResult && mysqli_num_rows($staffResult) > 0):
                                    while ($staff = mysqli_fetch_assoc($staffResult)):
                                        $badge = $staff['status'] === 'active' ? 'success' : 'secondary';
                                ?>
                                <tr>
                                    <td><strong>#<?= $staff['id'] ?></strong></td>
                                    <td><?= htmlspecialchars($staff['name']) ?></td>
                                    <td><?= htmlspecialchars($staff['position'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($staff['email'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($staff['phone'] ?? '-') ?></td>
                                    <td><span class="badge bg-<?= $badge ?>"><?= ucfirst($staff['status']) ?></span></td>
                                    <td><?= $staff['hire_date'] ? date('d M Y', strtotime($staff['hire_date'])) : '-' ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="modal" data-bs-target="#editStaffModal<?= $staff['id'] ?>" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this staff member?');">
                                            <input type="hidden" name="staff_id" value="<?= $staff['id'] ?>">
                                            <button type="submit" name="delete_staff" class="btn btn-sm btn-outline-danger" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>

                                <!-- Edit Modal for this staff -->
                                <div class="modal fade" id="editStaffModal<?= $staff['id'] ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST">
                                                <div class="modal-header">
                                                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Staff Member</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <input type="hidden" name="staff_id" value="<?= $staff['id'] ?>">
                                                    <div class="mb-3">
                                                        <label class="form-label">Name <span class="text-danger">*</span></label>
                                                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($staff['name']) ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Position</label>
                                                        <input type="text" name="position" class="form-control" value="<?= htmlspecialchars($staff['position'] ?? '') ?>">
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Email</label>
                                                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($staff['email'] ?? '') ?>">
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Phone</label>
                                                        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($staff['phone'] ?? '') ?>">
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Status</label>
                                                        <select name="staff_status" class="form-select">
                                                            <option value="active" <?= $staff['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                                            <option value="inactive" <?= $staff['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                                        </select>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Hire Date</label>
                                                        <input type="date" name="hire_date" class="form-control" value="<?= $staff['hire_date'] ?? '' ?>">
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" name="edit_staff" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save Changes</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <?php endwhile; else: ?>
                                <tr><td colspan="8" class="text-center text-muted py-4">No staff members found. Click "Add Staff" to create one.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Staff Modal -->
    <div class="modal fade" id="addStaffModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Add New Staff Member</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="Full name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Position</label>
                            <input type="text" name="position" class="form-control" placeholder="e.g. Chef, Waiter, Manager">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" placeholder="email@example.com">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control" placeholder="Phone number">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="staff_status" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Hire Date</label>
                            <input type="date" name="hire_date" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_staff" class="btn btn-primary"><i class="fas fa-plus me-1"></i> Add Staff</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
