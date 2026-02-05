<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>

<header class="hero-wrapper" style="background-image: url('https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=1600&q=80');">
    <div class="hero-overlay"></div>
    <div class="container hero-content">
        <h1 class="hero-title">Menu Grid</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb justify-content-center">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item active">Menu Grid</li>
            </ol>
        </nav>
    </div>
</header>

<section class="menu-section py-5">
    <div class="container">
        <!-- Filter Buttons -->
        <div class="row mb-5">
            <div class="col-12 d-flex justify-content-center flex-wrap gap-3">
                <button class="btn btn-filter active" data-filter="all">All</button>
                <button class="btn btn-filter" data-filter="breakfast">Breakfast</button>
                <button class="btn btn-filter" data-filter="lunch">Lunch</button>
                <button class="btn btn-filter" data-filter="dinner">Dinner</button>
            </div>
        </div>

        <!-- Grid Items -->
        <div class="row g-4" id="menu-grid">
            <div class="col-md-6 col-lg-4 menu-item" data-category="breakfast">
                <div class="card menu-card h-100">
                    <div class="img-wrapper"><img src="https://images.unsplash.com/photo-1574484284002-952d92456975?w=600&q=80" alt="Food"></div>
                    <div class="card-body text-center">
                        <h5 class="card-title"><a href="menuitems.php">Grilled Chicken Salad</a></h5>
                        <p class="text-muted">Chicken, Olive Oil, Fresh Veggies</p>
                        <span class="price">$12.99</span>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4 menu-item" data-category="lunch">
                <div class="card menu-card h-100">
                    <div class="img-wrapper"><img src="https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=600&q=80" alt="Food"></div>
                    <div class="card-body text-center">
                        <h5 class="card-title"><a href="menuitems.php">Classic Beef Burger</a></h5>
                        <p class="text-muted">Beef Patty, Cheddar, Lettuce</p>
                        <span class="price">$18.50</span>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4 menu-item" data-category="dinner">
                <div class="card menu-card h-100">
                    <div class="img-wrapper"><img src="https://images.unsplash.com/photo-1563379926898-05f4575a45d8?w=600&q=80" alt="Food"></div>
                    <div class="card-body text-center">
                        <h5 class="card-title"><a href="menuitems.php">Seafood Pasta</a></h5>
                        <p class="text-muted">Shrimp, Squid, Tomato Sauce</p>
                        <span class="price">$22.00</span>
                    </div>

                </div>
            </div>
            <div class="col-md-6 col-lg-4 menu-item" data-category="drinks">
                <div class="card menu-card h-100">
                    <div class="img-wrapper"><img src="https://images.unsplash.com/photo-1513558161293-cdaf765ed2fd?w=600&q=80" alt="Food"></div>
                    <div class="card-body text-center">
                        <h5 class="card-title"><a href="#">Fresh Mojito</a></h5>
                        <p class="text-muted">Lime, Mint, Soda, Sugar, Ice</p>
                        <span class="price">$8.50</span>
                    </div>
                </div>
            </div>
             <!-- Item 5 -->
             <div class="col-md-6 col-lg-4 menu-item" data-category="lunch">
                <div class="card menu-card h-100">
                    <div class="img-wrapper"><img src="https://images.unsplash.com/photo-1574484284002-952d92456975?w=600&q=80" alt="Food"></div>
                    <div class="card-body text-center">
                        <h5 class="card-title"><a href="#">Salmon Steak</a></h5>
                        <p class="text-muted">Salmon, Asparagus, Lemon</p>
                        <span class="price">$25.50</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>