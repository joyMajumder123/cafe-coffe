<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>

<header class="hero-wrapper" style="background-image: url('https://images.unsplash.com/photo-1544025162-d76694265947?w=1600&q=80');">
    <div class="hero-overlay"></div>
    <div class="container hero-content">
        <h1 class="hero-title">Grilled Salmon</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb justify-content-center">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="menugrid.php">Menu</a></li>
                <li class="breadcrumb-item active">Grilled Salmon</li>
            </ol>
        </nav>
    </div>
</header>

<section class="py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <img src="https://images.unsplash.com/photo-1563379926898-05f4575a45d8?w=800&q=80" class="img-fluid rounded shadow" alt="Details">
            </div>
            <div class="col-lg-6 ps-lg-5 mt-4 mt-lg-0">
                <h2 class="display-5 fw-bold text-dark">Grilled Salmon Steak</h2>
                <p class="price display-6 my-3">$25.00</p>
                <p class="text-muted lead">
                    Fresh Atlantic salmon grilled to perfection, served with a side of steamed asparagus and a drizzle of our signature lemon butter garlic sauce.
                </p>
                
                <h5 class="mt-4">Ingredients:</h5>
                <ul class="text-muted list-unstyled">
                    <li><i class="fas fa-check text-gold me-2"></i> Fresh Salmon</li>
                    <li><i class="fas fa-check text-gold me-2"></i> Organic Asparagus</li>
                    <li><i class="fas fa-check text-gold me-2"></i> Lemon Butter Sauce</li>
                    <li><i class="fas fa-check text-gold me-2"></i> Garlic & Herbs</li>
                </ul>

                <div class="mt-4">
                    <a href="contact.php" class="btn btn-reservation">ORDER NOW</a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>