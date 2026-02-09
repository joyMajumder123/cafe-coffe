<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<!-- Hero Section -->
<header class="hero-wrapper" style="background-image: url('https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=1600&q=80');">
    <div class="hero-overlay">
        
    </div>
    <div class="container hero-content">
        <h1 class="hero-title">Classic Indian Resturant</h1>
        <p class="hero-subtitle">Experience the authentic flavors of India in every bite.</p>

        <!-- Sliding Icons Section -->
        <div class="sliding-icons mt-4">
            <div id="iconsCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="3000">
                <div class="carousel-inner">
                    <div class="carousel-item active">
                        <div class="d-flex justify-content-center">
                            <div class="icon-item">
                                <i class="fas fa-utensils"></i>
                                <p>Menu</p>
                            </div>
                            <div class="icon-item">
                                <i class="fas fa-calendar-check"></i>
                                <p>Reserve</p>
                            </div>
                            <div class="icon-item">
                                <i class="fas fa-phone"></i>
                                <p>Contact</p>
                            </div>
                        </div>
                    </div>
                    <div class="carousel-item">
                        <div class="d-flex justify-content-center">
                            <div class="icon-item">
                                <i class="fas fa-star"></i>
                                <p>Reviews</p>
                            </div>
                            <div class="icon-item">
                                <i class="fas fa-map-marker-alt"></i>
                                <p>Location</p>
                            </div>
                            <div class="icon-item">
                                <i class="fas fa-clock"></i>
                                <p>Hours</p>
                            </div>
                        </div>
                    </div>
                </div>
                <button class="carousel-control-prev" type="button" data-bs-target="#iconsCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon"></span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#iconsCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon"></span>
                </button>
            </div>
        </div>
    </div>
</header>
<!-- Hero Slider Section -->
<section class="hero-slider-area">
    <div id="heroCarousel" class="carousel slide carousel-fade" data-ride="carousel">
        <!-- Indicators (Optional) -->
        <div class="carousel-indicators">
            <button type="button" data-target="#heroCarousel" data-slide-to="0" class="active"></button>
            <button type="button" data-target="#heroCarousel" data-slide-to="1"></button>
            <button type="button" data-target="#heroCarousel" data-slide-to="2"></button>
        </div>

        <div class="carousel-inner">
            <!-- Slide 1 -->
            <div class="carousel-item active">
                <div class="hero-slide-bg" style="background-image: url('https://images.unsplash.com/photo-1514362545857-3bc16c4c7d1b?auto=format&fit=crop&w=1920&q=80');"></div>
                <div class="carousel-caption d-flex align-items-center">
                    <div class="container text-start">
                        <div class="row">
                            <div class="col-lg-7">
                                <h6 class="text-primary text-uppercase fw-bold mb-3 animate-up">Welcome to Bistly</h6>
                                <h1 class="display-2 mb-4 animate-up delay-1">Experience Pure Pleasure on <span class="italic">Every Plate</span></h1>
                                <p class="lead mb-5 animate-up delay-2">Artfully prepared dishes, fresh ingredients, and bold flavors that awaken your senses.</p>
                                <div class="animate-up delay-3">
                                    <a href="menugrid.php" class="btn btn-gold btn-lg me-3">Explore Menu</a>
                                    <a href="contact.php" class="btn btn-outline-light btn-lg">Book A Table</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Slide 2 -->
            <div class="carousel-item">
                <div class="hero-slide-bg" style="background-image: url('https://images.unsplash.com/photo-1559339352-11d035aa65de?auto=format&fit=crop&w=1920&q=80');"></div>
                <div class="carousel-caption d-flex align-items-center">
                    <div class="container text-start">
                        <div class="row">
                            <div class="col-lg-7">
                                <h6 class="text-primary text-uppercase fw-bold mb-3 animate-up">Fine Dining Experience</h6>
                                <h1 class="display-2 mb-4 animate-up delay-1">Traditional Taste <br><span class="italic">Modern Twist</span></h1>
                                <p class="lead mb-5 animate-up delay-2">We bring you the authentic flavors of Italy served with a contemporary touch.</p>
                                <div class="animate-up delay-3">
                                    <a href="menugrid.php" class="btn btn-gold btn-lg me-3">View Menu</a>
                                    <a href="contact.php" class="btn btn-outline-light btn-lg">Reservation</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation Arrows -->
        <a class="carousel-control-prev" href="#heroCarousel" role="button" data-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="sr-only">Previous</span>
        </a>
        <a class="carousel-control-next" href="#heroCarousel" role="button" data-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="sr-only">Next</span>
        </a>
    </div>
</section>

<!-- Slidable Banner Section -->
<section class="slidable-banner py-5">
    <div class="container">
        <div id="bannerCarousel" class="carousel slide" data-ride="carousel" data-interval="3000">
            <div class="carousel-indicators">
                <button type="button" data-target="#bannerCarousel" data-slide-to="0" class="active"></button>
                <button type="button" data-target="#bannerCarousel" data-slide-to="1"></button>
                <button type="button" data-target="#bannerCarousel" data-slide-to="2"></button>
            </div>
            <div class="carousel-inner">
                <div class="carousel-item active">
                    <img src="https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=1200&q=80" class="d-block w-100" alt="Delicious Food Banner">
                </div>
                <div class="carousel-item">
                    <img src="https://images.unsplash.com/photo-1565299624946-b28f40a0ca4b?w=1200&q=80" class="d-block w-100" alt="Fresh Ingredients Banner">
                </div>
                <div class="carousel-item">
                    <img src="https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=1200&q=80" class="d-block w-100" alt="Restaurant Interior Banner">
                </div>
            </div>
            <a class="carousel-control-prev" href="#bannerCarousel" role="button" data-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="sr-only">Previous</span>
            </a>
            <a class="carousel-control-next" href="#bannerCarousel" role="button" data-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="sr-only">Next</span>
            </a>
        </div>
    </div>
</section>

<!-- Menu Section -->
<section class="menu-section py-5" id="menu-grid">
    <div class="container">
        
        <div class="row mb-5 text-center">
            <div class="col-12">
                <div class="subtitle-wrap"><span class="subtitle">Our Food Menu</span></div>
                <h2 class="main-title">Choose Your Food</h2>
            </div>
        </div>

        <div class="row mb-5">
            <div class="col-12 d-flex justify-content-center flex-wrap gap-3">
                <button class="btn btn-filter active" data-filter="all">All</button>
                <button class="btn btn-filter" data-filter="breakfast">Breakfast</button>
                <button class="btn btn-filter" data-filter="lunch">Lunch</button>
                <button class="btn btn-filter" data-filter="dinner">Dinner</button>
                <button class="btn btn-filter" data-filter="drinks">Drinks</button>
            </div>
        </div>

        <div class="row g-4" id="menu-items-container">
            <!-- Item 1 -->
            <div class="col-md-6 col-lg-4 menu-item" data-category="breakfast">
                <div class="card menu-card h-100">
                    <div class="img-wrapper"><img src="https://images.unsplash.com/photo-1574484284002-952d92456975?w=600&q=80" alt="Food"></div>
                    <div class="card-body text-center">
                        <h5 class="card-title">Grilled Chicken Salad</h5>
                        <p class="text-muted">Chicken, Olive Oil, Fresh Veggies, Cheese</p>
                        <span class="price">$12.99</span>
                        <button class="btn btn-primary mt-2 add-to-cart" data-name="Grilled Chicken Salad" data-price="12.99">Add to Cart</button>
                    </div>
                </div>
            </div>
            <!-- Item 2 -->
            <div class="col-md-6 col-lg-4 menu-item" data-category="lunch">
                <div class="card menu-card h-100">
                    <div class="img-wrapper"><img src="https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=600&q=80" alt="Food"></div>
                    <div class="card-body text-center">
                        <h5 class="card-title">Classic Beef Burger</h5>
                        <p class="text-muted">Beef Patty, Cheddar, Lettuce, Tomato</p>
                        <span class="price">$18.50</span>
                        <button class="btn btn-primary mt-2 add-to-cart" data-name="Classic Beef Burger" data-price="18.50">Add to Cart</button>
                    </div>
                </div>
            </div>
            <!-- Item 3 -->
            <div class="col-md-6 col-lg-4 menu-item" data-category="dinner">
                <div class="card menu-card h-100">
                    <div class="img-wrapper"><img src="https://images.unsplash.com/photo-1563379926898-05f4575a45d8?w=600&q=80" alt="Food"></div>
                    <div class="card-body text-center">
                        <h5 class="card-title">Seafood Pasta</h5>
                        <p class="text-muted">Shrimp, Squid, Tomato Sauce, Basil</p>
                        <span class="price">$22.00</span>
                        <button class="btn btn-primary mt-2 add-to-cart" data-name="Seafood Pasta" data-price="22.00">Add to Cart</button>
                    </div>
                </div>
            </div>
             <!-- Item 4 -->
             <div class="col-md-6 col-lg-4 menu-item" data-category="drinks">
                <div class="card menu-card h-100">
                    <div class="img-wrapper"><img src="https://images.unsplash.com/photo-1513558161293-cdaf765ed2fd?w=600&q=80" alt="Food"></div>
                    <div class="card-body text-center">
                        <h5 class="card-title">Fresh Mojito</h5>
                        <p class="text-muted">Lime, Mint, Soda, Sugar, Ice</p>
                        <span class="price">$8.50</span>
                        <button class="btn btn-primary mt-2 add-to-cart" data-name="Fresh Mojito" data-price="8.50">Add to Cart</button>
                    </div>
                </div>
            </div>
             <!-- Item 5 -->
             <div class="col-md-6 col-lg-4 menu-item" data-category="lunch">
                <div class="card menu-card h-100">
                    <div class="img-wrapper"><img src="https://images.unsplash.com/photo-1574484284002-952d92456975?w=600&q=80" alt="Food"></div>
                    <div class="card-body text-center">
                        <h5 class="card-title">Salmon Steak</h5>
                        <p class="text-muted">Salmon, Asparagus, Lemon</p>
                        <span class="price">$25.50</span>
                        <button class="btn btn-primary mt-2 add-to-cart" data-name="Salmon Steak" data-price="25.50">Add to Cart</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cart Section -->
        <div class="mt-5">
            <h3>Your Cart</h3>
            <div id="cart-items"></div>
            <div id="cart-total" class="mt-3"></div>
            <button id="place-order-btn" class="btn btn-success mt-3" style="display:none;">Place Order</button>
        </div>

        <!-- Order Modal -->
        <div class="modal fade" id="orderModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Place Your Order</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="order-form">
                            <div class="mb-3">
                                <label>Name</label>
                                <input type="text" class="form-control" name="customer_name" required>
                            </div>
                            <div class="mb-3">
                                <label>Email</label>
                                <input type="email" class="form-control" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label>Phone</label>
                                <input type="text" class="form-control" name="phone" required>
                            </div>
                            <input type="hidden" name="items" id="order-items">
                            <input type="hidden" name="total_amount" id="order-total">
                            <button type="submit" class="btn btn-primary">Submit Order</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- NEW: Booking / Reservation Section -->
<section class="booking-section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="subtitle-wrap">
                    <span class="subtitle">Book Now</span>
                </div>
                <h2 class="booking-title">Booking In Your Table</h2>
                <a href="contact.php" class="btn btn-outline-gold">MAKE A RESERVATION</a>
            </div>
        </div>
    </div>
</section>


<section class="py-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4"><img src="https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=500&q=80" class="img-fluid rounded shadow w-300
        " alt="Gallery"></div>
            <div class="col-md-4"><img src="https://images.unsplash.com/photo-1559339352-11d035aa65de?w=500&q=80" class="img-fluid rounded shadow w-300
        " alt="Gallery"></div>
            <div class="col-md-4"><img src="https://images.unsplash.com/photo-1552566626-52f8b828add9?w=500&q=80" class="img-fluid rounded shadow w-300
        " alt="Gallery"></div>
            <div class="col-md-4"><img src="https://images.unsplash.com/photo-1514362545857-3bc16c4c7d1b?w=500&q=80" class="img-fluid rounded shadow w-300
        " alt="Gallery"></div>
            <div class="col-md-4"><img src="https://images.unsplash.com/photo-1551024709-8f23befc6f87?w=500&q=80" class="img-fluid rounded shadow w-300
        " alt="Gallery"></div>
            <div class="col-md-4"><img src="https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=500&q=80" class="img-fluid rounded shadow w-300
        " alt="Gallery"></div>
        </div>
    </div>
</section>
</head>
<body
 <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>

</body> 
<?php include 'includes/footer.php'; ?>