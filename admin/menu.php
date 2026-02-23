<?php 
include 'includes/auth.php';
include 'includes/db.php';
require_permission('menu.view');

$errors = [];
$success = '';

$categories = [];
$category_result = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
if ($category_result) {
    while ($row = $category_result->fetch_assoc()) {
        $categories[] = $row;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_menu' || $action === 'update_menu') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $image = trim($_POST['image'] ?? '');
        $status = trim($_POST['status'] ?? 'active');

        if ($name === '') {
            $errors[] = 'Item name is required.';
        }

        if (empty($errors)) {
            if ($action === 'add_menu') {
                $stmt = $conn->prepare("INSERT INTO menu_items (name, description, category, price, image, status) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param('sssdss', $name, $description, $category, $price, $image, $status);
                if ($stmt->execute()) {
                    $success = 'Menu item added successfully.';
                } else {
                    $errors[] = 'Failed to add menu item.';
                }
                $stmt->close();
            } else {
                $id = intval($_POST['id'] ?? 0);
                $stmt = $conn->prepare("UPDATE menu_items SET name = ?, description = ?, category = ?, price = ?, image = ?, status = ? WHERE id = ?");
                $stmt->bind_param('sssdssi', $name, $description, $category, $price, $image, $status, $id);
                if ($stmt->execute()) {
                    $success = 'Menu item updated successfully.';
                } else {
                    $errors[] = 'Failed to update menu item.';
                }
                $stmt->close();
            }
        }
    }

    if ($action === 'delete_menu') {
        $id = intval($_POST['id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM menu_items WHERE id = ?");
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            $success = 'Menu item deleted successfully.';
        } else {
            $errors[] = 'Failed to delete menu item.';
        }
        $stmt->close();
    }
}

$edit_item = null;
if (isset($_GET['edit_id'])) {
    $edit_id = intval($_GET['edit_id']);
    $stmt = $conn->prepare("SELECT * FROM menu_items WHERE id = ?");
    $stmt->bind_param('i', $edit_id);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $edit_item = $result->fetch_assoc();
    }
    $stmt->close();
}

$items = $conn->query("SELECT * FROM menu_items ORDER BY created_at DESC");
$totalItems = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM menu_items"))['total'] ?? 0;
$activeItems = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM menu_items WHERE status='active'"))['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Management | Cafe Admin</title>
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
            <span class="navbar-brand">☕ Cafe Admin - Menu Management</span>
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
                    <li class="nav-item"><a href="menu.php" class="nav-link active">🍽️ Menu</a></li>
                    <li class="nav-item"><a href="categories.php" class="nav-link">📂 Categories</a></li>
                    <li class="nav-item"><a href="reservation.php" class="nav-link">📅 Reservations</a></li>
                    <li class="nav-item"><a href="staff.php" class="nav-link">👥 Staff</a></li>
                    
                </ul>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-utensils me-2"></i>Menu Management</h2>
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
                                        <h6 class="card-title">Total Items</h6>
                                        <h2><?= $totalItems ?></h2>
                                    </div>
                                    <i class="fas fa-hamburger fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card bg-success text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Active Items</h6>
                                        <h2><?= $activeItems ?></h2>
                                    </div>
                                    <i class="fas fa-check-circle fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card bg-warning text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Inactive Items</h6>
                                        <h2><?= $totalItems - $activeItems ?></h2>
                                    </div>
                                    <i class="fas fa-pause-circle fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Add / Edit Form -->
                <div class="card mb-4" id="menu-form">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0"><i class="fas fa-<?= $edit_item ? 'edit' : 'plus' ?> me-2"></i><?= $edit_item ? 'Edit Menu Item' : 'Add Menu Item' ?></h5>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <input type="hidden" name="action" value="<?= $edit_item ? 'update_menu' : 'add_menu' ?>">
                            <?php if ($edit_item): ?>
                                <input type="hidden" name="id" value="<?= (int)$edit_item['id'] ?>">
                            <?php endif; ?>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Item Name <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($edit_item['name'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Category</label>
                                    <select name="category" class="form-select">
                                        <option value="">Select category</option>
                                        <?php foreach ($categories as $category_item): ?>
                                            <option value="<?= htmlspecialchars($category_item['name']) ?>" <?= ($edit_item['category'] ?? '') === $category_item['name'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($category_item['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Price (₹) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" name="price" class="form-control" value="<?= htmlspecialchars($edit_item['price'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Image URL</label>
                                    <input type="text" name="image" class="form-control" value="<?= htmlspecialchars($edit_item['image'] ?? '') ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-select">
                                        <option value="active" <?= (($edit_item['status'] ?? '') === 'active') ? 'selected' : '' ?>>Active</option>
                                        <option value="inactive" <?= (($edit_item['status'] ?? '') === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                                    </select>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Description</label>
                                    <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($edit_item['description'] ?? '') ?></textarea>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> <?= $edit_item ? 'Update Item' : 'Add Item' ?></button>
                                    <?php if ($edit_item): ?>
                                        <a href="menu.php" class="btn btn-secondary ms-2">Cancel</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Menu Items Table -->
                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>All Menu Items</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead style="background: #f8f9fa;">
                                <tr>
                                    <th>Image</th>
                                    <th>Item Name</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($items && $items->num_rows > 0): ?>
                                    <?php while ($item = $items->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <?php if (!empty($item['image'])): ?>
                                                    <img src="<?= htmlspecialchars($item['image']) ?>" alt="Menu item" style="width:50px; height:50px; object-fit:cover; border-radius:5px;">
                                                <?php else: ?>
                                                    <div style="width:50px; height:50px; background:#dee2e6; border-radius:5px;" class="d-flex align-items-center justify-content-center"><i class="fas fa-image text-muted"></i></div>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($item['name']) ?></td>
                                            <td><?= htmlspecialchars($item['category']) ?></td>
                                            <td>₹<?= number_format((float)$item['price'], 2) ?></td>
                                            <td><span class="badge bg-<?= $item['status'] === 'active' ? 'success' : 'secondary' ?>"><?= ucfirst($item['status']) ?></span></td>
                                            <td>
                                                <a class="btn btn-sm btn-outline-primary me-1" href="menu.php?edit_id=<?= (int)$item['id'] ?>#menu-form" title="Edit"><i class="fas fa-edit"></i></a>
                                                <form method="post" style="display:inline-block;">
                                                    <input type="hidden" name="action" value="delete_menu">
                                                    <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this menu item?');" title="Delete"><i class="fas fa-trash"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="6" class="text-center text-muted py-4">No menu items found. Add one above.</td></tr>
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