<?php include 'includes/db.php'; ?>
<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<h3>Settings</h3>

<p>Configure application settings here.</p>

<!-- Placeholder for settings form -->
<div class="mt-4">
<form>
<div class="mb-3">
<label for="siteName" class="form-label">Site Name</label>
<input type="text" class="form-control" id="siteName" value="Cafe App">
</div>
<div class="mb-3">
<label for="email" class="form-label">Admin Email</label>
<input type="email" class="form-control" id="email" value="admin@cafe.com">
</div>
<button type="submit" class="btn btn-primary">Save Settings</button>
</form>
</div>

<?php include 'includes/footer.php'; ?>
