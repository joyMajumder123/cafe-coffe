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

<header class="hero-wrapper" style="background-image: url('https://images.unsplash.com/photo-1550966871-3ed3c47e2ce2?w=1600&q=80');">
    <div class="hero-overlay"></div>
    <div class="container hero-content">
        <h1 class="hero-title">Our Menu</h1>
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
        <?php if (!empty($filter_categories)): ?>
            <div class="row mb-4">
                <div class="col-12 d-flex flex-wrap justify-content-center gap-2">
                    <button class="btn btn-filter active" data-category-filter="all">All</button>
                    <?php foreach ($filter_categories as $slug => $label): ?>
                        <button class="btn btn-filter" data-category-filter="<?php echo htmlspecialchars($slug); ?>"><?php echo htmlspecialchars($label); ?></button>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="row g-4" id="menu-list-container">
            <?php if (!empty($menu_items)): ?>
                <?php foreach ($menu_items as $item): ?>
                    <div class="col-lg-6" data-menu-category="<?php echo htmlspecialchars($item['category_slug']); ?>">
                        <div class="border rounded-4 p-3 d-flex flex-column flex-sm-row gap-3 h-100">
                            <div class="ratio ratio-1x1 flex-shrink-0 w-100" style="max-width: 120px;">
                                <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="w-100 h-100 rounded-3" style="object-fit: cover;">
                            </div>
                            <div class="flex-grow-1 d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-start gap-3 mb-2 flex-wrap">
                                    <div>
                                        <h5 class="mb-1"><a href="menulist.php#menu-list-container" class="text-decoration-none text-dark"><?php echo htmlspecialchars($item['name']); ?></a></h5>
                                        <span class="badge bg-light text-dark text-uppercase small"><?php echo htmlspecialchars($item['category_label']); ?></span>
                                    </div>
                                    <h5 class="text-gold mb-0">₹<?php echo number_format((float) $item['price'], 2); ?></h5>
                                </div>
                                <p class="text-muted small mb-0"><?php echo htmlspecialchars($item['description'] ?? ''); ?></p>
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