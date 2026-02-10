<?php include 'includes/db.php'; ?>
<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<h3>Payments Management</h3>

<p>Manage payments here.</p>

<!-- Placeholder for payments list -->
<div class="mt-4">
<h4>All Payments</h4>
<table class="table table-striped">
<thead>
<tr>
<th>ID</th>
<th>Order ID</th>
<th>Amount</th>
<th>Status</th>
<th>Actions</th>
</tr>
</thead>
<tbody>
<!-- Sample data or dynamic content -->
<tr>
<td>1</td>
<td>101</td>
<td>₹500</td>
<td>Paid</td>
<td><button class="btn btn-sm btn-primary">View</button></td>
</tr>
</tbody>
</table>
</div>

<?php include 'includes/footer.php'; ?>
