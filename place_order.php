<?php
include 'admin/includes/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $customer_name = $_POST['customer_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $items = $_POST['items'];
    $total_amount = $_POST['total_amount'];

    $sql = "INSERT INTO orders (customer_name, email, phone, items, total_amount) VALUES ('$customer_name', '$email', '$phone', '$items', '$total_amount')";

    if (mysqli_query($conn, $sql)) {
        echo "Order placed successfully!";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}

$conn->close();
?>
