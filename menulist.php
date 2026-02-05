<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>

<header class="hero-wrapper" style="background-image: url('https://images.unsplash.com/photo-1550966871-3ed3c47e2ce2?w=1600&q=80');">
    <div class="hero-overlay"></div>
    <div class="container hero-content">
        <h1 class="hero-title">Menu List</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb justify-content-center">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item active">Menu List</li>
            </ol>
        </nav>
    </div>
</header>

<section class="py-5">
    <div class="container">
        <div class="row">
            <!-- Left Column Menu -->
            <div class="col-lg-6 mb-4">
                <h3 class="mb-4 text-gold">Starters</h3>
                
                <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-3">
                    <div>
                        <h5 class="mb-1"><a href="menuitems.php" class="text-decoration-none text-dark">Tomato Bruschetta</a></h5>
                        <p class="text-muted mb-0 small">Tomatoes, Olive Oil, Cheese</p>
                    </div>
                    <h5 class="text-gold fw-bold">$8.00</h5>
                </div>

                <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-3">
                    <div>
                        <h5 class="mb-1"><a href="menuitems.php" class="text-decoration-none text-dark">Avocado Shells</a></h5>
                        <p class="text-muted mb-0 small">Avocado, Corn, Lime</p>
                    </div>
                    <h5 class="text-gold fw-bold">$12.50</h5>
                </div>
            </div>

            <!-- Right Column Menu -->
            <div class="col-lg-6 mb-4">
                <h3 class="mb-4 text-gold">Main Course</h3>
                
                <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-3">
                    <div>
                        <h5 class="mb-1"><a href="menuitems.php" class="text-decoration-none text-dark">Grilled Salmon</a></h5>
                        <p class="text-muted mb-0 small">Salmon, Asparagus, Lemon</p>
                    </div>
                    <h5 class="text-gold fw-bold">$25.00</h5>
                </div>

                <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-3">
                    <div>
                        <h5 class="mb-1"><a href="menuitems.php" class="text-decoration-none text-dark">Roast Beef</a></h5>
                        <p class="text-muted mb-0 small">Beef, Potatoes, Rosemary</p>
                    </div>
                    <h5 class="text-gold fw-bold">$28.00</h5>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>