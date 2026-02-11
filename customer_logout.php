<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

unset($_SESSION['customer_id'], $_SESSION['customer_name'], $_SESSION['customer_email']);
header('Location: index.php');
exit();
