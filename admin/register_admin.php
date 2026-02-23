<?php
/**
 * Admin Self-Registration via Invite Code
 * New admin users can register using a valid invite code.
 */
session_start();

// Already logged in?
if (!empty($_SESSION['admin_user_id'])) {
    header("Location: dashboard.php");
    exit();
}

include 'includes/db.php';
require_once 'includes/rbac/RbacHelper.php';
require_once 'includes/rbac/audit_log.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code      = strtoupper(trim($_POST['invite_code'] ?? ''));
    $username  = trim($_POST['username'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';

    // Validate inputs
    if ($code === '' || $username === '' || $email === '' || $password === '') {
        $error = 'All fields are required.';
    } elseif (strlen($password) < 3) {
        $error = 'Password must be at least 3 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
        $error = 'Username must be 3-30 characters (letters, numbers, underscores only).';
    } else {
        // Validate invite code
        $stmt = $conn->prepare("
            SELECT ic.*, r.name as role_name
            FROM invite_codes ic
            JOIN roles r ON r.id = ic.role_id
            WHERE ic.code = ? AND ic.is_active = 1
            LIMIT 1
        ");
        $stmt->bind_param('s', $code);
        $stmt->execute();
        $invite = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$invite) {
            $error = 'Invalid or inactive invite code.';
        } elseif ($invite['expires_at'] && strtotime($invite['expires_at']) < time()) {
            $error = 'This invite code has expired.';
        } elseif ($invite['times_used'] >= $invite['max_uses']) {
            $error = 'This invite code has reached its maximum uses.';
        } else {
            // Create user
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $role_id = $invite['role_id'];

            $ins = $conn->prepare("INSERT INTO admin_users (username, email, password_hash, full_name, role_id) VALUES (?, ?, ?, ?, ?)");
            $ins->bind_param('ssssi', $username, $email, $hash, $full_name, $role_id);

            if ($ins->execute()) {
                $new_user_id = $ins->insert_id;

                // Increment invite usage
                $conn->query("UPDATE invite_codes SET times_used = times_used + 1 WHERE id = {$invite['id']}");

                audit_log($conn, 'user.register', "user:$new_user_id", "Registered via invite code: $code, role: {$invite['role_name']}");

                $success = "Account created! You've been assigned the <strong>{$invite['role_name']}</strong> role. You can now log in.";
            } else {
                $error = 'Registration failed. Username or email may already be taken.';
            }
            $ins->close();
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Register - Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-light">
<div class="container">
<div class="row justify-content-center mt-5">
<div class="col-md-5">
<div class="card shadow">
<div class="card-body">
<h3 class="card-title text-center mb-1"> Cafe Admin</h3>
<p class="text-center text-muted mb-4">Register with Invite Code</p>

<?php if ($error): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-1"></i><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle me-1"></i><?= $success ?></div>
    <div class="text-center"><a href="login.php" class="btn btn-primary"><i class="fas fa-sign-in-alt me-1"></i> Go to Login</a></div>
<?php else: ?>

<form method="post" autocomplete="off">
    <div class="mb-3">
        <label class="form-label">Invite Code <span class="text-danger">*</span></label>
        <input type="text" class="form-control text-uppercase" name="invite_code" placeholder="e.g. A1B2C3D4" required value="<?= htmlspecialchars($_POST['invite_code'] ?? $_GET['code'] ?? '') ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">Username <span class="text-danger">*</span></label>
        <input type="text" class="form-control" name="username" required pattern="[a-zA-Z0-9_]{3,30}" title="3-30 characters: letters, numbers, underscores" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">Email <span class="text-danger">*</span></label>
        <input type="email" class="form-control" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">Full Name</label>
        <input type="text" class="form-control" name="full_name" value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">Password <span class="text-danger">*</span> <small class="text-muted">(min 6 characters)</small></label>
        <input type="password" class="form-control" name="password" required minlength="6">
    </div>
    <div class="mb-3">
        <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
        <input type="password" class="form-control" name="confirm_password" required minlength="6">
    </div>
    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-user-plus me-1"></i> Register</button>
</form>
<hr>
<p class="text-center mb-0"><small class="text-muted">Already have an account? <a href="login.php">Login here</a></small></p>

<?php endif; ?>
</div>
</div>
</div>
</div>
</div>
</body>
</html>
