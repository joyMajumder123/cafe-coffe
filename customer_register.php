<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'admin/includes/db.php';

$errors = [];
$success = '';
$redirect = $_GET['redirect'] ?? 'profile.php';
if (!preg_match('/^[a-zA-Z0-9_\/-]+\.php$/', $redirect)) {
    $redirect = 'profile.php';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($name === '' || $email === '' || $password === '') {
        $errors[] = 'Name, email, and password are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email address.';
    } elseif ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM customers WHERE email = ? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = 'An account with this email already exists.';
        }
        $stmt->close();
    }

    if (empty($errors)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO customers (name, email, phone, password_hash) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('ssss', $name, $email, $phone, $password_hash);
        if ($stmt->execute()) {
            $_SESSION['customer_id'] = $stmt->insert_id;
            $_SESSION['customer_name'] = $name;
            $_SESSION['customer_email'] = $email;
            header('Location: ' . $redirect);
            exit();
        }
        $stmt->close();
        $errors[] = 'Registration failed. Please try again.';
    }
}

include 'includes/header.php';
include 'includes/navbar.php';
?>

<section class="py-5 customer-page">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="card customer-card">
                    <div class="card-header customer-header">
                        <h5 class="mb-0">Create Account</h5>
                        <span class="text-gold small">Join our cafe</span>
                    </div>
                    <div class="card-body p-4">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <?php echo htmlspecialchars(implode(' ', $errors)); ?>
                            </div>
                        <?php elseif ($success): ?>
                            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                        <?php endif; ?>
                        <form id="customer-register-form" method="post" action="customer_register.php?redirect=<?php echo urlencode($redirect); ?>" novalidate>
                            <div class="mb-3">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="name" class="form-control" required>
                                <div class="invalid-feedback">Please enter your name.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" required>
                                <div class="invalid-feedback">Please enter a valid email address.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control" required>
                                <div class="invalid-feedback">Please enter a password.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Confirm Password</label>
                                <input type="password" name="confirm_password" class="form-control" required>
                                <div class="invalid-feedback">Please confirm your password.</div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Register</button>
                        </form>
                        <div class="mt-3 text-center">
                            <a href="customer_login.php">Already have an account? Login</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
    .customer-page {
        background: linear-gradient(180deg, rgba(253, 248, 240, 0.7), rgba(255, 255, 255, 1));
    }

    .customer-card {
        border: 1px solid rgba(197, 160, 89, 0.2);
        border-radius: 16px;
        box-shadow: 0 12px 30px rgba(22, 22, 22, 0.08);
        overflow: hidden;
    }

    .customer-header {
        background: #1f1f1f;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 16px 20px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }

    .customer-card .card-body {
        padding: 24px;
    }

    .customer-card .form-control {
        border-radius: 10px;
        border-color: rgba(197, 160, 89, 0.35);
    }

    .customer-card .form-control:focus {
        border-color: #c5a059;
        box-shadow: 0 0 0 0.2rem rgba(197, 160, 89, 0.2);
    }
</style>

<script>
    (function () {
        const form = document.getElementById('customer-register-form');
        if (!form) {
            return;
        }

        form.addEventListener('submit', function (event) {
            const nameInput = form.querySelector('input[name="name"]');
            const emailInput = form.querySelector('input[name="email"]');
            const passwordInput = form.querySelector('input[name="password"]');
            const confirmInput = form.querySelector('input[name="confirm_password"]');
            let hasError = false;

            [nameInput, emailInput, passwordInput, confirmInput].forEach(function (input) {
                input.classList.remove('is-invalid');
            });

            if (!nameInput.value.trim()) {
                nameInput.classList.add('is-invalid');
                hasError = true;
            }

            if (!emailInput.value.trim() || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailInput.value)) {
                emailInput.classList.add('is-invalid');
                hasError = true;
            }

            if (!passwordInput.value.trim()) {
                passwordInput.classList.add('is-invalid');
                hasError = true;
            }

            if (!confirmInput.value.trim() || confirmInput.value !== passwordInput.value) {
                confirmInput.classList.add('is-invalid');
                hasError = true;
            }

            if (hasError) {
                event.preventDefault();
            }
        });
    })();
</script>

<?php include 'includes/footer.php'; ?>
