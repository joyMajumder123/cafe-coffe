<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'admin/includes/db.php';
require_once 'admin/includes/rbac/csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: contact.php');
    exit();
}

// CSRF validation
if (!csrf_validate()) {
    header('Location: contact.php?error=' . urlencode('Security token expired. Please try again.'));
    exit();
}

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$persons = trim($_POST['persons'] ?? '');
$location = trim($_POST['location'] ?? '');
$message = trim($_POST['message'] ?? '');

$error = '';
if ($name === '' || $email === '' || $phone === '' || $message === '') {
    $error = 'All fields are required!';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = 'Invalid email address!';
}

if ($error !== '') {
    $params = http_build_query([
        'error' => $error,
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'persons' => $persons,
        'location' => $location,
        'message' => $message,
    ]);
    header('Location: contact.php?' . $params);
    exit();
}

$status = 'new';
$stmt = $conn->prepare("INSERT INTO contact_submissions (name, email, phone, persons, location, message, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
if (!$stmt) {
    header('Location: contact.php?error=' . urlencode('Database error. Please try again.'));
    exit();
}

$stmt->bind_param('sssssss', $name, $email, $phone, $persons, $location, $message, $status);
if ($stmt->execute()) {
    header('Location: contact.php?success=' . urlencode("Thank you! Your message has been received. We'll get back to you soon!"));
} else {
    header('Location: contact.php?error=' . urlencode('Error submitting form. Please try again.'));
}
$stmt->close();
?>
