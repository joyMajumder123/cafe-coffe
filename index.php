<?php
include 'admin/includes/db.php';

$menu_items = [];
$menu_result = $conn->query("SELECT id, name, description, category, price, image FROM menu_items WHERE status = 'active' ORDER BY created_at DESC");
if ($menu_result) {
    while ($row = $menu_result->fetch_assoc()) {
        $menu_items[] = $row;
    }
}

include 'includes/header.php';
include 'includes/navbar.php';
?>


<!-- Hero Section -->
<header class="hero-wrapper" style="background-image: url('https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=1600&q=80');">
    <div class="hero-overlay">
        
    </div>
    <div class="container hero-content">
        <h1 class="hero-title">Classic Indian Resturant</h1>
        <p class="hero-subtitle">Experience the authentic flavors of India in every bite.</p>

        
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
<!-- Hero Slider Section 
<section class="hero-slider-area">
    <div id="heroCarousel" class="carousel slide carousel-fade" data-ride="carousel">
        <!-- Indicators (Optional) 
        <div class="carousel-indicators">
            <button type="button" data-target="#heroCarousel" data-slide-to="0" class="active"></button>
            <button type="button" data-target="#heroCarousel" data-slide-to="1"></button>
            <button type="button" data-target="#heroCarousel" data-slide-to="2"></button>
        </div>

        <div class="carousel-inner">
            <!-- Slide 1 
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

            <!-- Slide 2 
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

        <!-- Navigation Arrows 
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


<!-- Slidable Banner Section 
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
            <?php if (!empty($menu_items)): ?>
                <?php foreach ($menu_items as $item): ?>
                    <?php
                    $category = strtolower(trim($item['category'] ?? ''));
                    $category_attr = $category !== '' ? $category : 'all';
                    ?>
                    <div class="col-md-6 col-lg-4 menu-item" data-category="<?php echo htmlspecialchars($category_attr); ?>">
                        <div class="card menu-card h-100">
                            <div class="img-wrapper">
                                <?php if (!empty($item['image'])): ?>
                                    <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="Food">
                                <?php else: ?>
                                    <img src="https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=600&q=80" alt="Food">
                                <?php endif; ?>
                            </div>
                            <div class="card-body text-center">
                                <h5 class="card-title"><?php echo htmlspecialchars($item['name']); ?></h5>
                                <p class="text-muted"><?php echo htmlspecialchars($item['description']); ?></p>
                                <span class="price">₹<?php echo number_format((float) $item['price'], 2); ?></span>
                                <button
                                    class="btn btn-primary mt-2 add-to-cart"
                                    data-id="<?php echo (int) $item['id']; ?>"
                                    data-name="<?php echo htmlspecialchars($item['name']); ?>"
                                    data-price="<?php echo number_format((float) $item['price'], 2); ?>"
                                >Add to Cart</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info text-center">No menu items available yet.</div>
                </div>
            <?php endif; ?>
        </div>

        <div class="row mt-5">
            <div class="col-lg-8 mx-auto">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h4 class="mb-3">Your Cart</h4>
                        <div id="cart-items" class="mb-3 text-muted">No items added.</div>
                        <div class="d-flex justify-content-between align-items-center">
                            <strong>Total: ₹<span id="cart-total">0.00</span></strong>
                            <a class="btn btn-gold" id="checkout-btn" href="checkout.php">Go to Checkout</a>
                        </div>
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
    (function () {
        const cart = [];
        const cartItemsEl = document.getElementById('cart-items');
        const cartTotalEl = document.getElementById('cart-total');
        const checkoutBtn = document.getElementById('checkout-btn');
        const cartStorageKey = 'cafe_cart';

        function loadCart() {
            try {
                const saved = JSON.parse(localStorage.getItem(cartStorageKey) || '[]');
                if (Array.isArray(saved)) {
                    saved.forEach(function (item) { cart.push(item); });
                }
            } catch (error) {
                localStorage.removeItem(cartStorageKey);
            }
        }

        function saveCart() {
            localStorage.setItem(cartStorageKey, JSON.stringify(cart));
        }

        function renderCart() {
            if (cart.length === 0) {
                cartItemsEl.textContent = 'No items added.';
                cartTotalEl.textContent = '0.00';
                checkoutBtn.classList.add('disabled');
                checkoutBtn.setAttribute('aria-disabled', 'true');
                checkoutBtn.setAttribute('tabindex', '-1');
                return;
            }

            const list = document.createElement('ul');
            list.className = 'list-unstyled mb-0';
            let total = 0;

            cart.forEach(function (item, index) {
                total += item.price * item.quantity;
                const li = document.createElement('li');
                li.className = 'd-flex justify-content-between align-items-center mb-2';
                li.innerHTML =
                    '<div class="d-flex align-items-center gap-2">' +
                        '<button class="btn btn-sm btn-outline-secondary" data-action="decrease" data-index="' + index + '">-</button>' +
                        '<span>' + item.name + ' x ' + item.quantity + '</span>' +
                        '<button class="btn btn-sm btn-outline-secondary" data-action="increase" data-index="' + index + '">+</button>' +
                    '</div>' +
                    '<span>$' + (item.price * item.quantity).toFixed(2) + '</span>' +
                    '<button class="btn btn-sm btn-outline-danger ms-2" data-action="remove" data-index="' + index + '">Remove</button>';
                list.appendChild(li);
            });

            cartItemsEl.innerHTML = '';
            cartItemsEl.appendChild(list);
            cartTotalEl.textContent = total.toFixed(2);
            checkoutBtn.classList.remove('disabled');
            checkoutBtn.removeAttribute('aria-disabled');
            checkoutBtn.removeAttribute('tabindex');
            saveCart();
        }

        document.querySelectorAll('.add-to-cart').forEach(function (button) {
            button.addEventListener('click', function () {
                const id = parseInt(button.dataset.id, 10);
                const name = button.dataset.name;
                const price = parseFloat(button.dataset.price);
                const existing = cart.find(function (item) { return item.id === id; });
                if (existing) {
                    existing.quantity += 1;
                } else {
                    cart.push({ id: id, name: name, price: price, quantity: 1 });
                }
                renderCart();
            });
        });

        cartItemsEl.addEventListener('click', function (event) {
            const target = event.target;
            if (target.matches('button[data-index]')) {
                const index = parseInt(target.dataset.index, 10);
                const action = target.dataset.action;
                if (Number.isNaN(index)) {
                    return;
                }

                if (action === 'increase') {
                    cart[index].quantity += 1;
                } else if (action === 'decrease') {
                    cart[index].quantity -= 1;
                    if (cart[index].quantity <= 0) {
                        cart.splice(index, 1);
                    }
                } else {
                    cart.splice(index, 1);
                }

                renderCart();
            }
        });

        checkoutBtn.addEventListener('click', function (event) {
            if (cart.length === 0) {
                event.preventDefault();
            }
        });
        loadCart();
        renderCart();
    })();
</script>

</body> 
<?php include 'includes/footer.php'; ?>