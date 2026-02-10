<!DOCTYPE html>
<html lang="en">
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="main/css/style.css" rel="stylesheet">
    <title>Manage Chefs | Bistly</title>
</head>
<body>
    <?php include('includes/admin-nav.php'); ?>

    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Our Culinary Team</h2>
            <button class="btn btn-gold">+ Add Chef</button>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="card text-center p-4">
                    <div class="rounded-circle bg-secondary mx-auto mb-3" style="width:100px; height:100px;"></div>
                    <h5>John Smith</h5>
                    <p class="text-primary">Executive Chef</p>
                    <div class="mt-3">
                        <button class="btn btn-sm btn-outline-light">Edit Profile</button>
                    </div>
                </div>
            </div>
            <!-- More chef cards as needed -->
        </div>
    </div>
</body>
</html>