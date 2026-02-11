<?php
/**
 * User Page Layout Template
 * Include this at the top of user pages to get consistent header, navbar, and styling
 * Usage: include 'includes/user_layout.php';
 */

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection if not already included
if (!isset($conn)) {
    require_once 'admin/includes/db.php';
}

// Include common header and navbar
include 'includes/header.php';
include 'includes/navbar.php';
?>
