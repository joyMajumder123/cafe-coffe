<!DOCTYPE html>
<html lang="en">
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <title>Manage Menu | Bistly</title>
</head>
<body>
<?php include('includes/header.php'); ?>
<?php include('includes/sidebar.php'); ?>

    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Food Menu List</h2>
            <button class="btn btn-gold px-4">+ Add New Dish</button>
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
                        <tr>
                            <td><div style="width:50px; height:50px; background:#333;"></div></td>
                            <td>Lobster Mixed Salad</td>
                            <td>Breakfast</td>
                            <td>$45.00</td>
                            <td><span class="text-success">Active</span></td>
                            <td>
                                <button class="btn btn-sm btn-outline-info me-2">Edit</button>
                                <button class="btn btn-sm btn-outline-danger">Delete</button>
                            </td>
                        </tr>
                        <tr>
                            <td><div style="width:50px; height:50px; background:#333;"></div></td>
                            <td>Italian Grilled Chicken</td>
                            <td>Lunch</td>
                            <td>$32.00</td>
                            <td><span class="text-success">Active</span></td>
                            <td>
                                <button class="btn btn-sm btn-outline-info me-2">Edit</button>
                                <button class="btn btn-sm btn-outline-danger">Delete</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>