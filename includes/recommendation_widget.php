<?php
/**
 * Shared recommendation widget.
 *
 * Expected globals before including:
 * - $conn (mysqli connection)
 * - Optional config overrides (orders, title, etc.)
 */

if (!isset($conn) || !($conn instanceof mysqli)) {
    return;
}

if (!function_exists('fetch_customer_recommendations')) {
    require_once __DIR__ . '/recommendations.php';
}

$recommendation_orders = isset($recommendation_orders) && is_array($recommendation_orders)
    ? $recommendation_orders
    : [];
$recommendation_widget_limit = isset($recommendation_widget_limit)
    ? (int) $recommendation_widget_limit
    : 4;
if ($recommendation_widget_limit <= 0) {
    $recommendation_widget_limit = 4;
}

$recommendation_widget_title = $recommendation_widget_title ?? 'Recommended For You';
$recommendation_widget_subtitle = $recommendation_widget_subtitle ?? 'Handpicked dishes guided by your taste.';
$recommendation_widget_primary_cta = $recommendation_widget_primary_cta ?? '';
$recommendation_widget_empty_message = $recommendation_widget_empty_message ?? 'Browse the menu to unlock personalized ideas.';
$recommendation_widget_empty_cta = $recommendation_widget_empty_cta ?? '<a href="menulist.php" class="btn btn-gold btn-sm">Explore Menu</a>';
$recommendation_widget_id = $recommendation_widget_id ?? ('rec-widget-' . substr(md5((string) microtime(true)), 0, 8));

$recommendation_reason_meta = [
    'favorite' => ['label' => 'You order this often', 'class' => 'bg-warning text-dark'],
    'category' => ['label' => 'Similar picks', 'class' => 'bg-info text-dark'],
    'taste' => ['label' => 'Inspired by your browsing', 'class' => 'bg-primary text-white'],
    'trending' => ['label' => 'Trending now', 'class' => 'bg-secondary text-white'],
];

$recommendations = fetch_customer_recommendations($conn, $recommendation_orders, $recommendation_widget_limit);
?>
<div class="card user-card recommendation-widget mb-4" id="<?php echo htmlspecialchars($recommendation_widget_id); ?>" data-recommendation-widget="<?php echo htmlspecialchars($recommendation_widget_id); ?>">
    <div class="card-header user-card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h5 class="mb-0"><?php echo htmlspecialchars($recommendation_widget_title); ?></h5>
            <?php if (!empty($recommendation_widget_subtitle)): ?>
                <span class="text-gold small"><?php echo htmlspecialchars($recommendation_widget_subtitle); ?></span>
            <?php endif; ?>
        </div>
        <?php if (!empty($recommendation_widget_primary_cta)): ?>
            <div class="ms-auto"><?php echo $recommendation_widget_primary_cta; ?></div>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <?php if (!empty($recommendations)): ?>
            <div class="row g-3 align-items-stretch">
                <?php foreach ($recommendations as $recommendation): ?>
                    <?php
                        $reason_key = $recommendation['reason'] ?? 'trending';
                        $reason_meta = $recommendation_reason_meta[$reason_key] ?? $recommendation_reason_meta['trending'];
                        $price_value = number_format((float) ($recommendation['price'] ?? 0), 2, '.', '');
                        $price_display = number_format((float) ($recommendation['price'] ?? 0), 2);
                    ?>
                    <div class="col-md-6 col-lg-3">
                        <div class="recommendation-card border rounded h-100 d-flex flex-column p-3">
                            <div class="ratio ratio-16x9 mb-3 rounded overflow-hidden bg-light">
                                <img src="<?php echo htmlspecialchars($recommendation['image']); ?>" alt="<?php echo htmlspecialchars($recommendation['name']); ?>" class="w-100 h-100" style="object-fit: cover;">
                            </div>
                            <div class="d-flex justify-content-between align-items-center gap-2 mb-1">
                                <h6 class="mb-0"><?php echo htmlspecialchars($recommendation['name']); ?></h6>
                                <span class="badge <?php echo $reason_meta['class']; ?>"><?php echo htmlspecialchars($reason_meta['label']); ?></span>
                            </div>
                            <p class="text-muted small mb-2 flex-grow-1">
                                <?php echo htmlspecialchars($recommendation['summary'] ?: ($recommendation['description'] ?? '')); ?>
                            </p>
                            <div class="d-flex justify-content-between align-items-center mt-auto">
                                <strong class="text-gold">₹<?php echo $price_display; ?></strong>
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-gold recommendation-add-btn"
                                    data-add-to-cart
                                    data-id="<?php echo (int) ($recommendation['id'] ?? 0); ?>"
                                    data-name="<?php echo htmlspecialchars($recommendation['name']); ?>"
                                    data-price="<?php echo $price_value; ?>"
                                    data-feedback-target="<?php echo htmlspecialchars($recommendation_widget_id); ?>"
                                >Add to cart</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                <div>
                    <h6 class="mb-1">No picks yet</h6>
                    <p class="text-muted small mb-0"><?php echo htmlspecialchars($recommendation_widget_empty_message); ?></p>
                </div>
                <?php if (!empty($recommendation_widget_empty_cta)): ?>
                    <div class="ms-md-auto">
                        <?php echo $recommendation_widget_empty_cta; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <div class="small mt-3 text-success d-none recommendation-feedback" data-cart-feedback="<?php echo htmlspecialchars($recommendation_widget_id); ?>"></div>
    </div>
</div>
