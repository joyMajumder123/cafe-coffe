<?php 
include 'includes/auth.php';
include 'includes/db.php';
require_permission('categories.view');

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$action = $_POST['action'] ?? '';

	if ($action === 'add_category' || $action === 'update_category') {
		$name = trim($_POST['name'] ?? '');
		$description = trim($_POST['description'] ?? '');
		$image = trim($_POST['image'] ?? '');

		if ($name === '') {
			$errors[] = 'Category name is required.';
		}

		if (empty($errors)) {
			if ($action === 'add_category') {
				$stmt = $conn->prepare("INSERT INTO categories (name, description, image) VALUES (?, ?, ?)");
				$stmt->bind_param('sss', $name, $description, $image);
				if ($stmt->execute()) {
					$success = 'Category added successfully.';
				} else {
					$errors[] = 'Failed to add category.';
				}
				$stmt->close();
			} else {
				$id = intval($_POST['id'] ?? 0);
				$stmt = $conn->prepare("UPDATE categories SET name = ?, description = ?, image = ? WHERE id = ?");
				$stmt->bind_param('sssi', $name, $description, $image, $id);
				if ($stmt->execute()) {
					$success = 'Category updated successfully.';
				} else {
					$errors[] = 'Failed to update category.';
				}
				$stmt->close();
			}
		}
	}

	if ($action === 'delete_category') {
		$id = intval($_POST['id'] ?? 0);
		$stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
		$stmt->bind_param('i', $id);
		if ($stmt->execute()) {
			$success = 'Category deleted successfully.';
		} else {
			$errors[] = 'Failed to delete category.';
		}
		$stmt->close();
	}
}

$edit_category = null;
if (isset($_GET['edit_id'])) {
	$edit_id = intval($_GET['edit_id']);
	$stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
	$stmt->bind_param('i', $edit_id);
	if ($stmt->execute()) {
		$result = $stmt->get_result();
		$edit_category = $result->fetch_assoc();
	}
	$stmt->close();
}

$categories = $conn->query("SELECT * FROM categories ORDER BY created_at DESC");
$totalCategories = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM categories"))['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories | Cafe Admin</title>
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
            <span class="navbar-brand">☕ Cafe Admin - Categories</span>
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
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-folder-open me-2"></i>Categories Management</h2>
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
                                        <h6 class="card-title">Total Categories</h6>
                                        <h2><?= $totalCategories ?></h2>
                                    </div>
                                    <i class="fas fa-tags fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Add / Edit Form -->
                <div class="card mb-4">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0"><i class="fas fa-<?= $edit_category ? 'edit' : 'plus' ?> me-2"></i><?= $edit_category ? 'Edit Category' : 'Add Category' ?></h5>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <input type="hidden" name="action" value="<?= $edit_category ? 'update_category' : 'add_category' ?>">
                            <?php if ($edit_category): ?>
                                <input type="hidden" name="id" value="<?= (int)$edit_category['id'] ?>">
                            <?php endif; ?>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Name <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($edit_category['name'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Image URL</label>
                                    <input type="text" name="image" class="form-control" value="<?= htmlspecialchars($edit_category['image'] ?? '') ?>">
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Description</label>
                                    <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($edit_category['description'] ?? '') ?></textarea>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> <?= $edit_category ? 'Update Category' : 'Add Category' ?></button>
                                    <?php if ($edit_category): ?>
                                        <a href="categories.php" class="btn btn-secondary ms-2">Cancel</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Categories Table -->
                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>All Categories</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead style="background: #f8f9fa;">
                                <tr>
                                    <th>ID</th>
                                    <th>Image</th>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($categories && $categories->num_rows > 0): ?>
                                    <?php while ($category = $categories->fetch_assoc()): ?>
                                        <tr>
                                            <td><strong>#<?= (int)$category['id'] ?></strong></td>
                                            <td>
                                                <?php if (!empty($category['image'])): ?>
                                                    <img src="<?= htmlspecialchars($category['image']) ?>" alt="" style="width:40px; height:40px; object-fit:cover; border-radius:5px;">
                                                <?php else: ?>
                                                    <div style="width:40px; height:40px; background:#dee2e6; border-radius:5px;" class="d-flex align-items-center justify-content-center"><i class="fas fa-image text-muted small"></i></div>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($category['name']) ?></td>
                                            <td><small><?= htmlspecialchars(substr($category['description'] ?? '', 0, 80)) ?><?= strlen($category['description'] ?? '') > 80 ? '...' : '' ?></small></td>
                                            <td><?= date('d M Y', strtotime($category['created_at'])) ?></td>
                                            <td>
                                                <a class="btn btn-sm btn-outline-primary me-1" href="categories.php?edit_id=<?= (int)$category['id'] ?>" title="Edit"><i class="fas fa-edit"></i></a>
                                                <form method="post" style="display:inline-block;">
                                                    <input type="hidden" name="action" value="delete_category">
                                                    <input type="hidden" name="id" value="<?= (int)$category['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this category?');" title="Delete"><i class="fas fa-trash"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="6" class="text-center text-muted py-4">No categories found. Add one above.</td></tr>
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
