<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'admin/includes/rbac/csrf.php';
include 'includes/header.php'; 
include 'includes/navbar.php';

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
$name = $_GET['name'] ?? '';
$email = $_GET['email'] ?? '';
$phone = $_GET['phone'] ?? '';
$persons = $_GET['persons'] ?? '';
$location = $_GET['location'] ?? '';
$message = $_GET['message'] ?? '';
?>

<!-- Hero Section -->
<header class="hero-wrapper" style="background-image: url('https://images.unsplash.com/photo-1552566626-52f8b828add9?w=1600&q=80');">
    <div class="hero-overlay"></div>
    <div class="container hero-content">
        <h1 class="hero-title">Contact Us</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb justify-content-center">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Contact Us</li>
            </ol>
        </nav>
    </div>
</header>



<!-- Talk to Us / Form Section -->
<section class="form-section-wrap">
    <div class="container">
        <div class="row g-5 align-items-start">
            
            <!-- Left Side: Text & Hours -->
            <div class="col-lg-6">
                <div class="subtitle-wrap justify-content-start">
                    <span class="subtitle ps-0 ms-4">Contact Us</span>
                </div>
                <h2 class="main-title mb-4">Talk to Us Today</h2>
                <p class="text-muted mb-4">
                    Have a question, feedback, or need support? We're here to help! Reach out to our friendly team anytime—we're committed to providing prompt, professional assistance.
                </p>

                <div class="row hours-list">
                    <div class="col-md-6">
                        <h5 class="mb-3">Opening Hours:</h5>
                        <div class="hours-row"><span class="hours-label">Mon - Thu:</span> <span>10:00 am - 01:00 am</span></div>
                        <div class="hours-row"><span class="hours-label">Fri - Sat:</span> <span>10:00 am - 01:00 am</span></div>
                        <div class="hours-row"><span class="hours-label">Sunday:</span> <span>Off Day</span></div>
                    </div>
                    <div class="col-md-6">
                        <h5 class="mb-3">Available Hours:</h5>
                        <div class="hours-row"><span class="hours-label">Break Fast:</span> <span>07:00 am - 10:00 am</span></div>
                        <div class="hours-row"><span class="hours-label">Lunch:</span> <span>12:00 pm - 02:00 pm</span></div>
                        <div class="hours-row"><span class="hours-label">Dinner:</span> <span>07:00 - 10:00 pm</span></div>
                    </div>
                </div>
            </div>

            <!-- Right Side: Dark Form -->
            <div class="col-lg-6">
                <div class="contact-form-wrapper rounded">
                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <?= $success ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <?= $error ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="save_contact.php">
                        <?php echo csrf_field(); ?>
                        <div class="mb-3">
                            <label class="form-label text-white fw-bold">Full Name *</label>
                            <input type="text" class="form-control contact-input" name="name" placeholder="Enter full name" value="<?= htmlspecialchars($name) ?>" required>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label text-white fw-bold">Email *</label>
                                <input type="email" class="form-control contact-input" name="email" placeholder="Enter email" value="<?= htmlspecialchars($email) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-white fw-bold">Phone *</label>
                                <input type="tel" class="form-control contact-input" name="phone" placeholder="Enter phone number" value="<?= htmlspecialchars($phone) ?>" required>
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label text-white fw-bold">Persons</label>
                                <select class="form-select contact-input" name="persons">
                                    <option value="">Select</option>
                                    <option value="2" <?= $persons === '2' ? 'selected' : '' ?>>2 Persons</option>
                                    <option value="3" <?= $persons === '3' ? 'selected' : '' ?>>3 Persons</option>
                                    <option value="4+" <?= $persons === '4+' ? 'selected' : '' ?>>4+ Persons</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-white fw-bold">Location</label>
                                <select class="form-select contact-input" name="location">
                                    <option value="">Select Location</option>
                                    <option value="USA" <?= $location === 'USA' ? 'selected' : '' ?>>USA</option>
                                    <option value="UK" <?= $location === 'UK' ? 'selected' : '' ?>>UK</option>
                                    <option value="Canada" <?= $location === 'Canada' ? 'selected' : '' ?>>Canada</option>
                                    <option value="India" <?= $location === 'India' ? 'selected' : '' ?>>India</option>
                                    <option value="Other" <?= $location === 'Other' ? 'selected' : '' ?>>Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label text-white fw-bold">Message *</label>
                            <textarea class="form-control contact-input" name="message" rows="4" placeholder="Writing Message..." required><?= htmlspecialchars($message) ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-reservation w-100 py-3">SUBMIT REQUEST</button>
                    </form>
                </div>
            </div>

        </div>
    </div>
</section>
<!-- Contact Info Cards -->
<section class="py-5" style="background: #fdfdfd;">
    <div class="container mt-4">
        <div class="row g-4 text-center">
            <div class="col-md-4">
                <div class="contact-info-card">
                    <div class="icon-circle"><i class="fas fa-map-marker-alt"></i></div>
                    <h4>Our Location</h4>
                    <p class="text-muted">456 Elm Avenue, Metropolis NY<br>10001</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="contact-info-card">
                    <div class="icon-circle"><i class="fas fa-phone-alt"></i></div>
                    <h4>Contact Number</h4>
                    <p class="text-muted">+000 123 456 7890<br>+000 123 756 4352</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="contact-info-card">
                    <div class="icon-circle"><i class="fas fa-envelope"></i></div>
                    <h4>Email Address</h4>
                    <p class="text-muted">Contact@Example.com<br>Info@Example.com</p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>