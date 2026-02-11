<?php
include 'includes/db.php';
include 'includes/header.php';
include 'includes/sidebar.php';

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
?>

<h3>Categories Management</h3>

<?php if (!empty($errors)): ?>
	<div class="alert alert-danger">
		<?php echo htmlspecialchars(implode(' ', $errors)); ?>
	</div>
<?php elseif ($success): ?>
	<div class="alert alert-success">
		<?php echo htmlspecialchars($success); ?>
	</div>
<?php endif; ?>

<div class="card mb-4">
	<div class="card-header">
		<h5 class="mb-0"><?php echo $edit_category ? 'Edit Category' : 'Add Category'; ?></h5>
	</div>
	<div class="card-body">
		<form method="post">
			<input type="hidden" name="action" value="<?php echo $edit_category ? 'update_category' : 'add_category'; ?>">
			<?php if ($edit_category): ?>
				<input type="hidden" name="id" value="<?php echo (int) $edit_category['id']; ?>">
			<?php endif; ?>
			<div class="row g-3">
				<div class="col-md-6">
					<label class="form-label">Name</label>
					<input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($edit_category['name'] ?? ''); ?>" required>
				</div>
				<div class="col-md-6">
					<label class="form-label">Image URL</label>
					<input type="text" name="image" class="form-control" value="<?php echo htmlspecialchars($edit_category['image'] ?? ''); ?>">
				</div>
				<div class="col-md-12">
					<label class="form-label">Description</label>
					<textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($edit_category['description'] ?? ''); ?></textarea>
				</div>
				<div class="col-12">
					<button type="submit" class="btn btn-primary">
						<?php echo $edit_category ? 'Update Category' : 'Add Category'; ?>
					</button>
					<?php if ($edit_category): ?>
						<a href="categories.php" class="btn btn-secondary ms-2">Cancel</a>
					<?php endif; ?>
				</div>
			</div>
		</form>
	</div>
</div>

<div class="mt-4">
	<h4>All Categories</h4>
	<table class="table table-striped">
		<thead>
			<tr>
				<th>ID</th>
				<th>Name</th>
				<th>Description</th>
				<th>Actions</th>
			</tr>
		</thead>
		<tbody>
			<?php if ($categories && $categories->num_rows > 0): ?>
				<?php while ($category = $categories->fetch_assoc()): ?>
					<tr>
						<td><?php echo (int) $category['id']; ?></td>
						<td><?php echo htmlspecialchars($category['name']); ?></td>
						<td><?php echo htmlspecialchars($category['description']); ?></td>
						<td>
							<a class="btn btn-sm btn-primary" href="categories.php?edit_id=<?php echo (int) $category['id']; ?>">Edit</a>
							<form method="post" style="display:inline-block;">
								<input type="hidden" name="action" value="delete_category">
								<input type="hidden" name="id" value="<?php echo (int) $category['id']; ?>">
								<button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this category?');">Delete</button>
							</form>
						</td>
					</tr>
				<?php endwhile; ?>
			<?php else: ?>
				<tr>
					<td colspan="4" class="text-center text-muted">No categories found.</td>
				</tr>
			<?php endif; ?>
		</tbody>
	</table>
</div>

<?php include 'includes/footer.php'; ?>
