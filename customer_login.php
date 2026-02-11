<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'admin/includes/db.php';

$errors = [];
$redirect = $_GET['redirect'] ?? 'profile.php';
if (!preg_match('/^[a-zA-Z0-9_\/-]+\.php$/', $redirect)) {
    $redirect = 'profile.php';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $errors[] = 'Email and password are required.';
    } else {
        $stmt = $conn->prepare("SELECT id, name, email, password_hash FROM customers WHERE email = ? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $customer = $result->fetch_assoc();
        $stmt->close();

        if ($customer && password_verify($password, $customer['password_hash'])) {
            $_SESSION['customer_id'] = (int) $customer['id'];
            $_SESSION['customer_name'] = $customer['name'];
            $_SESSION['customer_email'] = $customer['email'];
            header('Location: ' . $redirect);
            exit();
        }
        $errors[] = 'Invalid email or password.';
    }
}

include 'includes/header.php';
include 'includes/navbar.php';
?>

<section class="py-5 customer-page">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-5">
                <div class="card customer-card">
                    <div class="card-header customer-header">
                        <h5 class="mb-0">Customer Login</h5>
                        <span class="text-gold small">Welcome back</span>
                    </div>
                    <div class="card-body p-4">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <?php echo htmlspecialchars(implode(' ', $errors)); ?>
                            </div>
                        <?php endif; ?>
                        <form id="customer-login-form" method="post" action="customer_login.php?redirect=<?php echo urlencode($redirect); ?>" novalidate>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" required>
                                <div class="invalid-feedback">Please enter a valid email address.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control" required>
                                <div class="invalid-feedback">Please enter your password.</div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Login</button>
                        </form>
                        <div class="mt-3 text-center">
                            <a href="customer_register.php">New here? Create an account</a>
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
        const form = document.getElementById('customer-login-form');
        if (!form) {
            return;
        }

        form.addEventListener('submit', function (event) {
            const emailInput = form.querySelector('input[name="email"]');
            const passwordInput = form.querySelector('input[name="password"]');
            let hasError = false;

            [emailInput, passwordInput].forEach(function (input) {
                input.classList.remove('is-invalid');
            });

            if (!emailInput.value.trim() || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailInput.value)) {
                emailInput.classList.add('is-invalid');
                hasError = true;
            }

            if (!passwordInput.value.trim()) {
                passwordInput.classList.add('is-invalid');
                hasError = true;
            }

            if (hasError) {
                event.preventDefault();
            }
        });
    })();
</script>

<?php include 'includes/footer.php'; ?>
