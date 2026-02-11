<?php


// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

//  database connection 
if (!isset($conn)) {
    require_once 'admin/includes/db.php';
}

// Include common header and navbar
include 'includes/header.php';
include 'includes/navbar.php';
?>
