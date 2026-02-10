<?php include 'auth.php'; ?>

<!DOCTYPE html>
<html>
<head>
<title>Admin Panel</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>

<nav class="navbar navbar-dark bg-dark">
    <div class="container-fluid">
        <span class="navbar-brand">Cafe Admin</span>
        <a href="logout.php" class="btn btn-outline-light">Logout</a>
    </div>
</nav>

<div class="container-fluid">
<div class="row">
<div class="col-md-2 bg-light vh-100 p-3">

<ul class="nav flex-column">

<li class="nav-item">
<a href="orders.php" class="nav-link">Orders</a>
</li>

<li class="nav-item">
<a href="menu.php" class="nav-link">Menu</a>
</li>

<li class="nav-item">
<a href="reservation.php" class="nav-link">Reservations</a>
</li>

<li class="nav-item">
<a href="our-chefs.php" class="nav-link">Chefs</a>
</li>

</ul>

</div>

<div class="col-md-10 p-4">
