<?php
/**
 * Personalized recommendation helpers.
 */
if (!function_exists('cafe_normalize_category_slug')) {
    function cafe_normalize_category_slug(string $label): string
    {
        $label = strtolower(trim($label));
        $label = preg_replace('/[^a-z0-9]+/', '-', $label);
        $label = trim($label, '-');
        return $label !== '' ? $label : 'general';
    }
}

function normalize_category_preferences_payload(array $raw): array
{
    $normalized = [];
    foreach ($raw as $key => $value) {
        $slug = is_string($key) ? $key : '';
        if (is_array($value)) {
            $slug = $value['slug'] ?? $slug;
        }
        $slug = cafe_normalize_category_slug($slug);
        if ($slug === '') {
            continue;
        }
        $score = 0.0;
        $label = '';
        if (is_array($value)) {
            $label = isset($value['label']) ? trim((string) $value['label']) : '';
            $score = isset($value['score']) ? (float) $value['score'] : 0.0;
        } elseif (is_numeric($value)) {
            $score = (float) $value;
        }
        if ($label === '') {
            $label = ucwords(str_replace('-', ' ', $slug));
        }
        $normalized[$slug] = [
            'label' => $label,
            'score' => max($score, 0.0),
        ];
    }
    return $normalized;
}
function extract_order_item_signals(array $orders): array
{
    $scores = [];

    foreach ($orders as $order) {
        $rawItems = json_decode($order['items'] ?? '[]', true);
        if (!is_array($rawItems)) {
            continue;
        }
        foreach ($rawItems as $item) {
            $itemId = isset($item['id']) ? (int) $item['id'] : 0;
            $name = isset($item['name']) ? trim((string) $item['name']) : '';
            $quantity = isset($item['quantity']) ? (int) $item['quantity'] : 1;
            if ($quantity <= 0) {
                $quantity = 1;
            }
            $key = $itemId > 0 ? 'id_' . $itemId : 'name_' . strtolower($name);
            if (!isset($scores[$key])) {
                $scores[$key] = [
                    'id' => $itemId,
                    'name' => $name,
                    'score' => 0,
                ];
            }
            $scores[$key]['score'] += $quantity;
        }
    }

    uasort($scores, function ($a, $b) {
        return $b['score'] <=> $a['score'];
    });

    $favoriteIds = [];
    $fallbackNames = [];
    foreach ($scores as $row) {
        if ($row['id'] > 0) {
            $favoriteIds[] = $row['id'];
        } elseif ($row['name'] !== '') {
            $fallbackNames[] = $row['name'];
        }
    }

    return [$favoriteIds, $fallbackNames];
}

function summarize_menu_copy(?string $text, int $limit = 20): string
{
    $text = trim((string) $text);
    if ($text === '') {
        return '';
    }
    $words = preg_split('/\s+/', $text);
    if (count($words) <= $limit) {
        return $text;
    }
    $snippet = array_slice($words, 0, $limit);
    return implode(' ', $snippet) . '…';
}

function resolve_menu_image(?string $image): string
{
    $image = trim((string) $image);
    if ($image === '') {
        return 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=800&q=80';
    }
    if (preg_match('/^https?:\/\//i', $image)) {
        return $image;
    }
    if ($image[0] === '/') {
        return ltrim($image, '/');
    }
    return 'admin/uploads/' . $image;
}

function build_recommendation_payload(array $row, string $reason): array
{
    $categoryLabel = $row['category'] ?? '';
    return [
        'id' => (int) ($row['id'] ?? 0),
        'name' => $row['name'] ?? 'Menu Item',
        'description' => $row['description'] ?? '',
        'summary' => summarize_menu_copy($row['description'] ?? ''),
        'price' => isset($row['price']) ? (float) $row['price'] : 0.0,
        'image' => resolve_menu_image($row['image'] ?? ''),
        'category' => $categoryLabel,
        'category_label' => $categoryLabel,
        'category_slug' => cafe_normalize_category_slug($categoryLabel),
        'reason' => $reason,
    ];
}

function fetch_menu_items_by_ids(mysqli $conn, array $ids): array
{
    if (empty($ids)) {
        return [];
    }
    $ids = array_values(array_unique(array_map('intval', $ids)));
    if (empty($ids)) {
        return [];
    }
    $idList = implode(',', $ids);
    $query = "SELECT id, name, description, price, image, category, status FROM menu_items WHERE id IN ($idList)";
    $result = $conn->query($query);
    if (!$result) {
        return [];
    }
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[(int) $row['id']] = $row;
    }
    return $items;
}

function fetch_menu_items_by_names(mysqli $conn, array $names): array
{
    $names = array_values(array_unique(array_filter(array_map('trim', $names))));
    if (empty($names)) {
        return [];
    }
    $escaped = array_map(function ($name) use ($conn) {
        return "'" . $conn->real_escape_string(strtolower($name)) . "'";
    }, $names);
    $nameList = implode(',', $escaped);
    $query = "SELECT id, name, description, price, image, category, status FROM menu_items WHERE LOWER(name) IN ($nameList)";
    $result = $conn->query($query);
    if (!$result) {
        return [];
    }
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[(int) $row['id']] = $row;
    }
    return $items;
}

function fetch_customer_recommendations(mysqli $conn, array $orders, int $limit = 4, array $options = []): array
{
    if ($limit <= 0) {
        return [];
    }

    [$favoriteIds, $fallbackNames] = extract_order_item_signals($orders);
    $categoryPreferences = [];
    if (!empty($options['category_preferences']) && is_array($options['category_preferences'])) {
        $categoryPreferences = normalize_category_preferences_payload($options['category_preferences']);
    }

    $cartItems = [];
    if (!empty($options['cart_items']) && is_array($options['cart_items'])) {
        foreach ($options['cart_items'] as $item) {
            if (!is_array($item)) {
                continue;
            }
            $cartItems[] = [
                'id' => isset($item['id']) ? (int) $item['id'] : 0,
                'name' => isset($item['name']) ? trim((string) $item['name']) : '',
                'category_label' => isset($item['category_label']) ? trim((string) $item['category_label']) : ($item['category'] ?? ''),
                'category_slug' => isset($item['category_slug']) ? trim((string) $item['category_slug']) : ($item['category'] ?? ''),
            ];
        }
    }

    if (!empty($cartItems)) {
        foreach ($cartItems as $cartItem) {
            if (!empty($cartItem['id'])) {
                $favoriteIds[] = (int) $cartItem['id'];
            } elseif (!empty($cartItem['name'])) {
                $fallbackNames[] = $cartItem['name'];
            }
            $prefSlug = cafe_normalize_category_slug($cartItem['category_slug'] ?: $cartItem['category_label']);
            if ($prefSlug !== '') {
                $label = $cartItem['category_label'] ?: ucwords(str_replace('-', ' ', $prefSlug));
                $currentScore = $categoryPreferences[$prefSlug]['score'] ?? 0;
                $categoryPreferences[$prefSlug] = [
                    'label' => $label,
                    'score' => $currentScore + 1.5,
                ];
            }
        }
    }

    $favoriteIds = array_values(array_unique(array_filter(array_map('intval', $favoriteIds))));
    $recommendations = [];

    if (!empty($favoriteIds)) {
        $menuItems = fetch_menu_items_by_ids($conn, $favoriteIds);
        foreach ($favoriteIds as $favId) {
            if (!isset($menuItems[$favId])) {
                continue;
            }
            $row = $menuItems[$favId];
            if (($row['status'] ?? 'active') !== 'inactive') {
                $recommendations[] = build_recommendation_payload($row, 'favorite');
            }
            if (count($recommendations) >= $limit) {
                break;
            }
        }
    }

    $usedIds = array_column($recommendations, 'id');
    if (!is_array($usedIds)) {
        $usedIds = [];
    }

    if (count($recommendations) < $limit && !empty($fallbackNames)) {
        $menuItems = fetch_menu_items_by_names($conn, $fallbackNames);
        foreach ($menuItems as $row) {
            if (($row['status'] ?? 'active') === 'inactive') {
                continue;
            }
            $rowId = (int) $row['id'];
            if (in_array($rowId, $usedIds, true)) {
                continue;
            }
            $recommendations[] = build_recommendation_payload($row, 'favorite');
            $usedIds[] = $rowId;
            if (count($recommendations) >= $limit) {
                break;
            }
        }
    }

    $categoryPriority = [];
    foreach ($recommendations as $rec) {
        $catKey = strtolower($rec['category'] ?? '');
        if ($catKey === '') {
            continue;
        }
        if (!isset($categoryPriority[$catKey])) {
            $categoryPriority[$catKey] = [
                'label' => $rec['category'],
                'score' => 0,
                'source' => 'category',
            ];
        }
        $categoryPriority[$catKey]['score'] += 1;
    }

    foreach ($categoryPreferences as $slug => $meta) {
        $label = $meta['label'] ?? $slug;
        $key = strtolower($label);
        if ($key === '') {
            $key = str_replace('-', ' ', $slug);
        }
        if ($key === '') {
            continue;
        }
        if (!isset($categoryPriority[$key])) {
            $categoryPriority[$key] = [
                'label' => $label,
                'score' => 0,
                'source' => 'taste',
            ];
        }
        $categoryPriority[$key]['score'] += $meta['score'] ?? 0;
        if (empty($categoryPriority[$key]['label'])) {
            $categoryPriority[$key]['label'] = $label;
        }
        if ($categoryPriority[$key]['source'] !== 'category') {
            $categoryPriority[$key]['source'] = 'taste';
        }
    }

    uasort($categoryPriority, function ($a, $b) {
        return ($b['score'] ?? 0) <=> ($a['score'] ?? 0);
    });

    $usedIds = array_column($recommendations, 'id');
    $usedIds = is_array($usedIds) ? $usedIds : [];

    foreach ($categoryPriority as $categoryKey => $meta) {
        if (count($recommendations) >= $limit) {
            break;
        }
        $label = $meta['label'] ?: ucwords($categoryKey);
        $reason = $meta['source'] === 'taste' ? 'taste' : 'category';
        $stmt = $conn->prepare("SELECT id, name, description, price, image, category FROM menu_items WHERE status = 'active' AND LOWER(category) = ? ORDER BY created_at DESC LIMIT 6");
        if (!$stmt) {
            continue;
        }
        $categoryParam = strtolower($label);
        $stmt->bind_param('s', $categoryParam);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $rowId = (int) $row['id'];
                if (in_array($rowId, $usedIds, true)) {
                    continue;
                }
                $recommendations[] = build_recommendation_payload($row, $reason);
                $usedIds[] = $rowId;
                if (count($recommendations) >= $limit) {
                    break;
                }
            }
        }
        $stmt->close();
    }

    if (count($recommendations) < $limit) {
        $stmt = $conn->prepare("SELECT id, name, description, price, image, category FROM menu_items WHERE status = 'active' ORDER BY created_at DESC LIMIT 8");
        if ($stmt && $stmt->execute()) {
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $rowId = (int) $row['id'];
                if (in_array($rowId, $usedIds, true)) {
                    continue;
                }
                $recommendations[] = build_recommendation_payload($row, 'trending');
                $usedIds[] = $rowId;
                if (count($recommendations) >= $limit) {
                    break;
                }
            }
        }
        if ($stmt) {
            $stmt->close();
        }
    }

    return array_slice($recommendations, 0, $limit);
}
?>
