<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'admin/includes/db.php';
require_once 'includes/recommendations.php';

function slugify_category(string $label): string
{
    $label = strtolower(trim($label));
    $label = preg_replace('/[^a-z0-9]+/', '-', $label);
    $label = trim($label, '-');
    return $label !== '' ? $label : 'uncategorized';
}

$menu_items = [];
$category_lookup = [];
$categories_from_table = [];

$category_result = $conn->query("SELECT name FROM categories ORDER BY name ASC");
if ($category_result) {
    while ($category_row = $category_result->fetch_assoc()) {
        $name = trim($category_row['name'] ?? '');
        if ($name === '') {
            continue;
        }
        $categories_from_table[slugify_category($name)] = $name;
    }
}

$menu_query = $conn->query("SELECT id, name, description, category, price, image FROM menu_items WHERE status = 'active' ORDER BY created_at DESC");
if ($menu_query) {
    while ($row = $menu_query->fetch_assoc()) {
        $category_label = trim($row['category'] ?? '');
        if ($category_label === '') {
            $category_label = 'Chef Specials';
        }
        $category_slug = slugify_category($category_label);
        $category_lookup[$category_slug] = $category_label;
        $row['category_label'] = $category_label;
        $row['category_slug'] = $category_slug;
        $row['image_url'] = resolve_menu_image($row['image'] ?? '');
        $menu_items[] = $row;
    }
}

$filter_categories = [];
if (!empty($categories_from_table)) {
    foreach ($categories_from_table as $slug => $label) {
        if (isset($category_lookup[$slug])) {
            $filter_categories[$slug] = $label;
        }
    }
}
foreach ($category_lookup as $slug => $label) {
    if (!isset($filter_categories[$slug])) {
        $filter_categories[$slug] = $label;
    }
}

$customer_orders = [];
if (!empty($_SESSION['customer_id'])) {
    $customer_id = (int) $_SESSION['customer_id'];
    $order_stmt = $conn->prepare('SELECT id, items FROM orders WHERE customer_id = ? ORDER BY created_at DESC LIMIT 20');
    if ($order_stmt) {
        $order_stmt->bind_param('i', $customer_id);
        if ($order_stmt->execute()) {
            $result = $order_stmt->get_result();
            if ($result) {
                while ($order = $result->fetch_assoc()) {
                    $customer_orders[] = $order;
                }
            }
        }
        $order_stmt->close();
    }
}

include 'includes/header.php';
include 'includes/navbar.php';
?>

<header class="hero-wrapper" style="background-image: url('https://plus.unsplash.com/premium_photo-1674582097978-58ac9cca6b8b?q=80&w=687&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D');">
    <div class="hero-overlay"></div>
    <div class="container hero-content">
        <h1 class="hero-title">Our menu</h1>
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
        <div class="row g-4 align-items-start cart-layout" data-cart-layout data-cart-state="collapsed">
            <div class="col-12 col-lg-12 cart-menu-column" data-cart-menu-column>
                <?php if (!empty($filter_categories)): ?>
                    <div class="d-flex flex-wrap justify-content-center justify-content-lg-start gap-2 mb-4">
                        <button class="btn btn-filter active" data-category-filter="all">All</button>
                        <?php foreach ($filter_categories as $slug => $label): ?>
                            <button class="btn btn-filter" data-category-filter="<?php echo htmlspecialchars($slug); ?>"><?php echo htmlspecialchars($label); ?></button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="row g-4 menu-grid-wrapper" id="menu-list-container">
                    <?php if (!empty($menu_items)): ?>
                        <?php foreach ($menu_items as $item): ?>
                            <div class="col-md-6" data-menu-category="<?php echo htmlspecialchars($item['category_slug']); ?>">
                                <div class="recommendation-card border rounded h-100 d-flex flex-column p-3">
                                    <div class="ratio ratio-16x9 mb-3 rounded overflow-hidden bg-light">
                                        <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="w-100 h-100" style="object-fit: cover;">
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center gap-2 mb-1">
                                        <span class="badge bg-light text-dark text-uppercase small"><?php echo htmlspecialchars($item['category_label']); ?></span>
                                        <span class="text-muted small">Signature</span>
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
                            <div class="alert alert-info text-center mb-0">No dishes have been published yet. Check back soon!</div>
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
                            <span><i class="fas fa-receipt me-1"></i>Bill split ready</span>
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
                            <a class="btn btn-outline-gold" href="#menu-list-container">Add more</a>
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
            $recommendation_widget_title = 'Chef Suggestions';
            $recommendation_widget_subtitle = 'Auto-updated when you add new dishes';
            $recommendation_widget_id = 'menulist-recommendations';
            include 'includes/recommendation_widget.php';
            unset($recommendation_orders, $recommendation_widget_title, $recommendation_widget_subtitle, $recommendation_widget_id);
        ?>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var filterButtons = document.querySelectorAll('[data-category-filter]');
    var menuCards = document.querySelectorAll('[data-menu-category]');
    if (!filterButtons.length) {
        return;
    }
    filterButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            var filterValue = button.getAttribute('data-category-filter');
            filterButtons.forEach(function (btn) {
                btn.classList.toggle('active', btn === button);
            });
            menuCards.forEach(function (card) {
                var category = card.getAttribute('data-menu-category');
                if (filterValue === 'all' || category === filterValue) {
                    card.classList.remove('d-none');
                } else {
                    card.classList.add('d-none');
                }
            });
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>