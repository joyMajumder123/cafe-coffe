<?php
/**
 * Shared menu data helpers.
 * Eliminates duplicated category / menu-item / order-history logic
 * that was copy-pasted between index.php and menulist.php.
 */

if (!function_exists('slugify_category')) {
    /**
     * Convert a human-readable category label into a URL-safe slug.
     */
    function slugify_category(string $label): string
    {
        $label = strtolower(trim($label));
        $label = preg_replace('/[^a-z0-9]+/', '-', $label);
        $label = trim($label, '-');
        return $label !== '' ? $label : 'uncategorized';
    }
}

/**
 * Fetch all active menu items together with their resolved image URLs
 * and a merged category filter list.
 *
 * @return array{menu_items: array, filter_categories: array}
 */
function fetch_menu_data(mysqli $conn): array
{
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

    $menu_result = $conn->query(
        "SELECT id, name, description, category, price, image
         FROM menu_items
         WHERE status = 'active'
         ORDER BY created_at DESC"
    );
    if ($menu_result) {
        while ($row = $menu_result->fetch_assoc()) {
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

    // Build merged filter list: categories table first, then any extras from items
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

    return [
        'menu_items' => $menu_items,
        'filter_categories' => $filter_categories,
    ];
}

/**
 * Fetch recent order history for a customer (used by recommendation engine).
 *
 * @return array
 */
function fetch_customer_order_history(mysqli $conn, int $customer_id, int $limit = 20): array
{
    $orders = [];
    if ($customer_id <= 0) {
        return $orders;
    }
    $stmt = $conn->prepare('SELECT id, items FROM orders WHERE customer_id = ? ORDER BY created_at DESC LIMIT ?');
    if ($stmt) {
        $stmt->bind_param('ii', $customer_id, $limit);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($result) {
                while ($order = $result->fetch_assoc()) {
                    $orders[] = $order;
                }
            }
        }
        $stmt->close();
    }
    return $orders;
}
