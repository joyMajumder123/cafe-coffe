<?php
include 'includes/db.php';
include 'includes/header.php';
include 'includes/sidebar.php';

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
?>

    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Food Menu List</h2>
            <a class="btn btn-gold px-4" href="#menu-form">+ Add New Dish</a>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars(implode(' ', $errors)); ?>
            </div>
        <?php elseif ($success): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <div class="card mb-4" id="menu-form">
            <div class="card-header">
                <h5 class="mb-0"><?php echo $edit_item ? 'Edit Menu Item' : 'Add Menu Item'; ?></h5>
            </div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="action" value="<?php echo $edit_item ? 'update_menu' : 'add_menu'; ?>">
                    <?php if ($edit_item): ?>
                        <input type="hidden" name="id" value="<?php echo (int) $edit_item['id']; ?>">
                    <?php endif; ?>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Item Name</label>
                            <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($edit_item['name'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Category</label>
                            <select name="category" class="form-select">
                                <option value="">Select category</option>
                                <?php foreach ($categories as $category_item): ?>
                                    <?php $selected = ($edit_item['category'] ?? '') === $category_item['name'] ? 'selected' : ''; ?>
                                    <option value="<?php echo htmlspecialchars($category_item['name']); ?>" <?php echo $selected; ?>>
                                        <?php echo htmlspecialchars($category_item['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Price</label>
                            <input type="number" step="0.01" name="price" class="form-control" value="<?php echo htmlspecialchars($edit_item['price'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Image URL</label>
                            <input type="text" name="image" class="form-control" value="<?php echo htmlspecialchars($edit_item['image'] ?? ''); ?>">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($edit_item['description'] ?? ''); ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="active" <?php echo (($edit_item['status'] ?? '') === 'active') ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo (($edit_item['status'] ?? '') === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <?php echo $edit_item ? 'Update Dish' : 'Add Dish'; ?>
                            </button>
                            <?php if ($edit_item): ?>
                                <a href="menu.php" class="btn btn-secondary ms-2">Cancel</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead style="background: rgba(197, 160, 89, 0.1);">
                        <tr>
                            <th>Image</th><th>Item Name</th><th>Category</th><th>Price</th><th>Status</th><th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($items && $items->num_rows > 0): ?>
                            <?php while ($item = $items->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($item['image'])): ?>
                                            <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="Menu item" style="width:50px; height:50px; object-fit:cover;">
                                        <?php else: ?>
                                            <div style="width:50px; height:50px; background:#333;"></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                                    <td><?php echo htmlspecialchars($item['category']); ?></td>
                                    <td>$<?php echo number_format((float) $item['price'], 2); ?></td>
                                    <td>
                                        <span class="text-<?php echo $item['status'] === 'active' ? 'success' : 'muted'; ?>">
                                            <?php echo ucfirst($item['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a class="btn btn-sm btn-outline-info me-2" href="menu.php?edit_id=<?php echo (int) $item['id']; ?>">Edit</a>
                                        <form method="post" style="display:inline-block;">
                                            <input type="hidden" name="action" value="delete_menu">
                                            <input type="hidden" name="id" value="<?php echo (int) $item['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this menu item?');">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted">No menu items found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php include 'includes/footer.php'; ?>