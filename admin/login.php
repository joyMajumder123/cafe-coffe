<?php
session_start();
<<<<<<< HEAD

// Already logged in?
if (!empty($_SESSION['admin_user_id'])) {
    header("Location: dashboard.php");
    exit();
}

include 'includes/db.php';
require_once 'includes/rbac/RbacHelper.php';
require_once 'includes/rbac/audit_log.php';

$error = '';
$MAX_ATTEMPTS = 5;
$LOCKOUT_MINUTES = 15;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    // Rate limiting — check recent failed attempts from this IP
    $cutoff = date('Y-m-d H:i:s', time() - ($LOCKOUT_MINUTES * 60));
    $rl = $conn->prepare("SELECT COUNT(*) as cnt FROM login_attempts WHERE ip_address = ? AND success = 0 AND attempted_at > ?");
    $rl->bind_param('ss', $ip, $cutoff);
    $rl->execute();
    $attempts = $rl->get_result()->fetch_assoc()['cnt'] ?? 0;
    $rl->close();

    if ($attempts >= $MAX_ATTEMPTS) {
        $error = "Too many failed attempts. Please try again in {$LOCKOUT_MINUTES} minutes.";
    } elseif ($username === '' || $password === '') {
        $error = "Please enter both username and password.";
=======
require_once __DIR__ . '/includes/auth_config.php';

if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    if ($username == ADMIN_USERNAME && password_verify($password, ADMIN_PASSWORD_HASH)) {
        $_SESSION['admin'] = true;
        header("Location: dashboard.php");
        exit();
>>>>>>> 0a326b648898bf45f29b4008031d761173890da3
    } else {
        // Look up the user
        $stmt = $conn->prepare("
            SELECT u.id, u.username, u.password_hash, u.status, u.role_id
            FROM admin_users u
            WHERE u.username = ?
            LIMIT 1
        ");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password_hash'])) {
            if ($user['status'] !== 'active') {
                $error = "Your account is {$user['status']}. Contact an administrator.";
                // Log attempt
                $la = $conn->prepare("INSERT INTO login_attempts (ip_address, username, success) VALUES (?, ?, 0)");
                $la->bind_param('ss', $ip, $username);
                $la->execute();
                $la->close();
            } else {
                // Successful login
                session_regenerate_id(true);
                rbac_load_session($conn, $user['id']);

                // Update last login
                $conn->prepare("UPDATE admin_users SET last_login = NOW(), login_attempts = 0 WHERE id = ?")->bind_param('i', $user['id']) || true;
                $ul = $conn->prepare("UPDATE admin_users SET last_login = NOW(), login_attempts = 0 WHERE id = ?");
                $ul->bind_param('i', $user['id']);
                $ul->execute();
                $ul->close();

                // Record successful attempt
                $la = $conn->prepare("INSERT INTO login_attempts (ip_address, username, success) VALUES (?, ?, 1)");
                $la->bind_param('ss', $ip, $username);
                $la->execute();
                $la->close();

                audit_log($conn, 'auth.login', 'user:' . $user['id'], 'Login successful');

                header("Location: dashboard.php");
                exit();
            }
        } else {
            $error = "Invalid username or password.";
            // Record failed attempt
            $la = $conn->prepare("INSERT INTO login_attempts (ip_address, username, success) VALUES (?, ?, 0)");
            $la->bind_param('ss', $ip, $username);
            $la->execute();
            $la->close();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Admin Login</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-light">
<div class="container">
<div class="row justify-content-center mt-5">
<div class="col-md-4">
<div class="card shadow">
<div class="card-body">
<h3 class="card-title text-center mb-1">☕ Cafe Admin</h3>
<p class="text-center text-muted mb-4">Sign in to your account</p>
<?php if ($error): ?>
    <div class='alert alert-danger'><i class="fas fa-exclamation-circle me-1"></i><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<form method="post" autocomplete="off">
<div class="mb-3">
<label for="username" class="form-label">Username</label>
<input type="text" class="form-control" id="username" name="username" required autofocus value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
</div>
<div class="mb-3">
<label for="password" class="form-label">Password</label>
<input type="password" class="form-control" id="password" name="password" required>
</div>
<button type="submit" name="login" class="btn btn-primary w-100"><i class="fas fa-sign-in-alt me-1"></i> Login</button>
</form>
<hr>
<p class="text-center mb-0"><small class="text-muted">Have an invite code? <a href="register_admin.php">Register here</a></small></p>
</div>
</div>
</div>
</div>
</div>
</body>
</html>
