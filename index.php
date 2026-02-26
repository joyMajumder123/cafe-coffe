<?php
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.use_strict_mode', 1);
    session_start();
}

require_once 'admin/includes/db.php';
require_once 'includes/recommendations.php';
require_once 'includes/menu_helpers.php';

$menu_data = fetch_menu_data($conn);
$menu_items = $menu_data['menu_items'];
$filter_categories = $menu_data['filter_categories'];

$customer_orders = [];
if (!empty($_SESSION['customer_id'])) {
    $customer_orders = fetch_customer_order_history($conn, (int) $_SESSION['customer_id']);
}

include 'includes/header.php';
include 'includes/navbar.php';
?>


<!-- Hero Section -->
<header class="hero-wrapper home-hero" style="background-image: url('https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=1600&q=80');">
    <div class="hero-overlay"></div>
    <div class="container hero-content text-center">
        <p class="hero-eyebrow text-uppercase text-gold mb-2">Artisanal brews & bites</p>
        <h1 class="hero-title">Classic Indian Restaurant</h1>
        <p class="hero-subtitle mx-auto">Experience the authentic flavors of India in every bite.</p>
        <div class="mt-4 d-flex gap-3 justify-content-center flex-wrap">
            <a href="menulist.php" class="btn btn-gold">Explore Menu</a>
            <a href="contact.php" class="btn btn-outline-gold">Book a Table</a>
        </div>
    </div>
</header>

<!-- Menu Section -->
<section class="menu-section py-5" id="menu-grid">
    <div class="container">
        <div class="row mb-5 text-center">
            <div class="col-12">
                <div class="subtitle-wrap"><span class="subtitle">Our Food Menu</span></div>
                <h2 class="main-title">Choose Your Food</h2>
            </div>
        </div>

        <div class="row g-4 align-items-start cart-layout" data-cart-layout data-cart-state="collapsed">
            <div class="col-12 col-lg-12 cart-menu-column" data-cart-menu-column>
                <?php if (!empty($filter_categories)): ?>
                    <div class="d-flex flex-wrap gap-2 mb-4 justify-content-center justify-content-lg-start">
                        <button class="btn btn-filter active" data-category-filter="all">All</button>
                        <?php foreach ($filter_categories as $slug => $label): ?>
                            <button class="btn btn-filter" data-category-filter="<?php echo htmlspecialchars($slug); ?>"><?php echo htmlspecialchars($label); ?></button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="row g-4 menu-grid-wrapper" id="home-menu-grid">
                    <?php if (!empty($menu_items)): ?>
                        <?php foreach ($menu_items as $item): ?>
                            <div class="col-sm-6" data-menu-category="<?php echo htmlspecialchars($item['category_slug']); ?>">
                                <div class="recommendation-card border rounded h-100 d-flex flex-column p-3">
                                    <div class="ratio ratio-16x9 mb-3 rounded overflow-hidden bg-light">
                                        <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="w-100 h-100" style="object-fit: cover;">
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center gap-2 mb-1">
                                        <span class="badge bg-light text-dark text-uppercase small"><?php echo htmlspecialchars($item['category_label']); ?></span>
                                        <span class="text-muted small">Chef's pick</span>
                                    </div>
                                    <h5 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h5>
                                    <p class="text-muted small flex-grow-1 mb-2"><?php echo htmlspecialchars($item['description'] ?? ''); ?></p>
                                    <div class="d-flex justify-content-between align-items-center mt-auto">
                                        <strong class="text-gold">₹<?php echo number_format((float) $item['price'], 2); ?></strong>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-gold"
                                            data-add-to-cart
                                            data-id="<?php echo (int) $item['id']; ?>"
                                            data-name="<?php echo htmlspecialchars($item['name']); ?>"
                                            data-price="<?php echo number_format((float) $item['price'], 2, '.', ''); ?>"
                                        >Add to cart</button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="alert alert-info text-center mb-0">No menu items available yet.</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-4" data-cart-panel-container hidden>
                <div class="cart-panel card sticky-lg-top is-hidden" data-cart-panel style="top: 1.5rem;">
                    <div class="cart-panel-head">
                        <div class="cart-panel-identity">
                            <div class="cart-panel-icon"><i class="fas fa-bag-shopping"></i></div>
                            <div>
                                <p class="cart-panel-eyebrow mb-1">Live order bag</p>
                                <h5 class="mb-0">Quick Cart</h5>
                                <small class="text-muted" data-cart-summary>It is empty right now</small>
                            </div>
                        </div>
                        <button type="button" class="btn btn-link btn-sm text-decoration-none cart-clear-btn" data-cart-clear>Clear</button>
                    </div>
                    <div class="cart-panel-body">
                        <div class="cart-delivery-meta">
                            <span><i class="fas fa-bolt text-warning me-1"></i>25 min avg</span>
                            <span class="dot"></span>
                            <span><i class="fas fa-store me-1"></i>Pickup & Delivery</span>
                        </div>
                        <div class="cart-perk" data-cart-threshold>
                            <div class="d-flex justify-content-between align-items-center">
                                <small>₹499 away from free dessert</small>
                                <strong data-cart-progress-remaining>₹499 left</strong>
                            </div>
                            <div class="progress mt-2">
                                <div class="progress-bar" role="progressbar" data-cart-progress-bar style="width: 0%"></div>
                            </div>
                            <div class="small text-success mt-2 d-none" data-cart-progress-message>Sweet! reward unlocked</div>
                        </div>
                        <div data-cart-items class="text-muted small">Your cart is empty.</div>
                    </div>
                    <div class="cart-panel-footer">
                        <div>
                            <small class="text-muted d-block">Payable</small>
                            <strong>₹<span data-cart-total>0.00</span></strong>
                        </div>
                        <div class="d-flex flex-column gap-2">
                            <a class="btn btn-outline-gold" href="menulist.php">Add more</a>
                            <a class="btn btn-gold" href="checkout.php" data-cart-checkout>Checkout</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5 bg-light">
    <div class="container">
        <?php
            $recommendation_orders = $customer_orders;
            $recommendation_widget_title = 'Recommended Today';
            $recommendation_widget_subtitle = 'Fresh picks inspired by your recent cravings';
            $recommendation_widget_id = 'home-recommendations';
            $recommendation_widget_primary_cta = '<a href="menulist.php" class="btn btn-outline-gold btn-sm">See full menu</a>';
            include 'includes/recommendation_widget.php';
            unset(
                $recommendation_orders,
                $recommendation_widget_title,
                $recommendation_widget_subtitle,
                $recommendation_widget_id,
                $recommendation_widget_primary_cta
            );
        ?>
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
            <div class="col-md-4"><img src="https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=500&q=80" class="img-fluid rounded shadow" alt="Gallery" loading="lazy"></div>
            <div class="col-md-4"><img src="https://images.unsplash.com/photo-1559339352-11d035aa65de?w=500&q=80" class="img-fluid rounded shadow" alt="Gallery" loading="lazy"></div>
            <div class="col-md-4"><img src="https://images.unsplash.com/photo-1552566626-52f8b828add9?w=500&q=80" class="img-fluid rounded shadow" alt="Gallery" loading="lazy"></div>
            <div class="col-md-4"><img src="https://images.unsplash.com/photo-1514362545857-3bc16c4c7d1b?w=500&q=80" class="img-fluid rounded shadow" alt="Gallery" loading="lazy"></div>
            <div class="col-md-4"><img src="https://images.unsplash.com/photo-1551024709-8f23befc6f87?w=500&q=80" class="img-fluid rounded shadow" alt="Gallery" loading="lazy"></div>
            <div class="col-md-4"><img src="https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=500&q=80" class="img-fluid rounded shadow" alt="Gallery" loading="lazy"></div>
        </div>
    </div>
</section>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var filterButtons = Array.prototype.slice.call(document.querySelectorAll('[data-category-filter]'));
    var menuCards = Array.prototype.slice.call(document.querySelectorAll('#home-menu-grid [data-menu-category]'));
    if (!filterButtons.length || !menuCards.length) {
        return;
    }

    var applyFilter = function (target) {
        var normalizedTarget = (target || 'all').toLowerCase();
        menuCards.forEach(function (card) {
            var category = (card.getAttribute('data-menu-category') || '').toLowerCase();
            if (normalizedTarget === 'all' || category === normalizedTarget) {
                card.classList.remove('d-none');
            } else {
                card.classList.add('d-none');
            }
        });
    };

    filterButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            filterButtons.forEach(function (btn) {
                btn.classList.toggle('active', btn === button);
            });
            applyFilter(button.getAttribute('data-category-filter'));
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>