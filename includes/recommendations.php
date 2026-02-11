<?php
/**
 * Personalized recommendation helpers.
 */
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

function fetch_customer_recommendations(mysqli $conn, array $orders, int $limit = 4): array
{
    if ($limit <= 0) {
        return [];
    }

    [$favoriteIds, $fallbackNames] = extract_order_item_signals($orders);
    $recommendations = [];

    if (!empty($favoriteIds)) {
        $menuItems = fetch_menu_items_by_ids($conn, $favoriteIds);
        foreach ($favoriteIds as $favId) {
            if (!isset($menuItems[$favId])) {
                continue;
            }
            $row = $menuItems[$favId];
            if (($row['status'] ?? 'active') !== 'inactive') {
                $recommendations[] = [
                    'id' => (int) $row['id'],
                    'name' => $row['name'],
                    'description' => $row['description'] ?? '',
                    'summary' => summarize_menu_copy($row['description'] ?? ''),
                    'price' => (float) $row['price'],
                    'image' => resolve_menu_image($row['image'] ?? ''),
                    'category' => $row['category'] ?? '',
                    'reason' => 'favorite',
                ];
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
            $recommendations[] = [
                'id' => $rowId,
                'name' => $row['name'],
                'description' => $row['description'] ?? '',
                'summary' => summarize_menu_copy($row['description'] ?? ''),
                'price' => (float) $row['price'],
                'image' => resolve_menu_image($row['image'] ?? ''),
                'category' => $row['category'] ?? '',
                'reason' => 'favorite',
            ];
            $usedIds[] = $rowId;
            if (count($recommendations) >= $limit) {
                break;
            }
        }
    }

    $categories = [];
    foreach ($recommendations as $rec) {
        $catKey = strtolower($rec['category'] ?? '');
        if ($catKey !== '') {
            $categories[$catKey] = ($categories[$catKey] ?? 0) + 1;
        }
    }

    if (empty($categories) && !empty($favoriteIds)) {
        $items = fetch_menu_items_by_ids($conn, $favoriteIds);
        foreach ($items as $row) {
            $catKey = strtolower($row['category'] ?? '');
            if ($catKey !== '') {
                $categories[$catKey] = ($categories[$catKey] ?? 0) + 1;
            }
        }
    }

    arsort($categories);
    $usedIds = array_column($recommendations, 'id');
    foreach (array_keys($categories) as $category) {
        if (count($recommendations) >= $limit) {
            break;
        }
        $stmt = $conn->prepare("SELECT id, name, description, price, image, category FROM menu_items WHERE status = 'active' AND LOWER(category) = ? ORDER BY created_at DESC LIMIT 6");
        if (!$stmt) {
            continue;
        }
        $stmt->bind_param('s', $category);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $rowId = (int) $row['id'];
                if (in_array($rowId, $usedIds, true)) {
                    continue;
                }
                $recommendations[] = [
                    'id' => $rowId,
                    'name' => $row['name'],
                    'description' => $row['description'] ?? '',
                    'summary' => summarize_menu_copy($row['description'] ?? ''),
                    'price' => (float) $row['price'],
                    'image' => resolve_menu_image($row['image'] ?? ''),
                    'category' => $row['category'] ?? '',
                    'reason' => 'category',
                ];
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
                $recommendations[] = [
                    'id' => $rowId,
                    'name' => $row['name'],
                    'description' => $row['description'] ?? '',
                    'summary' => summarize_menu_copy($row['description'] ?? ''),
                    'price' => (float) $row['price'],
                    'image' => resolve_menu_image($row['image'] ?? ''),
                    'category' => $row['category'] ?? '',
                    'reason' => 'trending',
                ];
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
