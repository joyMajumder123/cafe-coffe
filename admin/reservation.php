<!DOCTYPE html>
<html lang="en">
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/css/style.css" rel="stylesheet">
    <title>Manage Reservations | Bistly</title>
</head>
<body>
    <?php include('includes/admin-nav.php'); ?>

    <div class="container py-5">
        <h2 class="mb-4">Customer Bookings</h2>
        <div class="card p-3">
            <table class="table">
                <thead>
                    <tr><th>Booking ID</th><th>Customer Name</th><th>Email</th><th>Date/Time</th><th>Guests</th><th>Action</th></tr>
                </thead>
                <tbody>
                    <tr>
                        <td>#B-8801</td>
                        <td>Michael Vance</td>
                        <td>m.vance@mail.com</td>
                        <td>Feb 14 | 07:00 PM</td>
                        <td>4 Person</td>
                        <td>
                            <button class="btn btn-sm btn-success">Confirm</button>
                            <button class="btn btn-sm btn-danger">Cancel</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>