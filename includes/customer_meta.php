<?php
/**
 * Utility helpers for managing customer addresses and payment preferences.
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

function ensure_customer_meta_tables(mysqli $conn): void
{
    $conn->query(
        "CREATE TABLE IF NOT EXISTS customer_addresses (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            customer_id INT NOT NULL,
            label VARCHAR(60) NOT NULL,
            address_line1 VARCHAR(255) NOT NULL,
            address_line2 VARCHAR(255) NULL,
            city VARCHAR(150) NOT NULL,
            state VARCHAR(150) NULL,
            postal_code VARCHAR(20) NULL,
            phone VARCHAR(40) NULL,
            is_default TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_customer_addresses_customer (customer_id),
            CONSTRAINT fk_customer_addresses_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $conn->query(
        "CREATE TABLE IF NOT EXISTS customer_preferences (
            customer_id INT NOT NULL PRIMARY KEY,
            preferred_payment_method VARCHAR(30) NULL,
            category_preferences TEXT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_customer_preferences_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $columnCheck = $conn->query("SHOW COLUMNS FROM customer_preferences LIKE 'category_preferences'");
    if ($columnCheck && $columnCheck->num_rows === 0) {
        $conn->query("ALTER TABLE customer_preferences ADD COLUMN category_preferences TEXT NULL AFTER preferred_payment_method");
    }
    if ($columnCheck instanceof mysqli_result) {
        $columnCheck->free();
    }
}

function fetch_customer_addresses(mysqli $conn, int $customer_id): array
{
    $stmt = $conn->prepare("SELECT id, label, address_line1, address_line2, city, state, postal_code, phone, is_default FROM customer_addresses WHERE customer_id = ? ORDER BY is_default DESC, created_at DESC");
    $stmt->bind_param('i', $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $addresses = [];
    while ($row = $result->fetch_assoc()) {
        $addresses[] = $row;
    }
    $stmt->close();
    return $addresses;
}

function set_default_customer_address(mysqli $conn, int $customer_id, int $address_id): bool
{
    $stmt = $conn->prepare("UPDATE customer_addresses SET is_default = CASE WHEN id = ? THEN 1 ELSE 0 END WHERE customer_id = ?");
    $stmt->bind_param('ii', $address_id, $customer_id);
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}

function ensure_address_has_default(mysqli $conn, int $customer_id): void
{
    $stmt = $conn->prepare("SELECT COUNT(*) AS total_default FROM customer_addresses WHERE customer_id = ? AND is_default = 1");
    $stmt->bind_param('i', $customer_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (($result['total_default'] ?? 0) > 0) {
        return;
    }

    $stmt = $conn->prepare("UPDATE customer_addresses SET is_default = 1 WHERE customer_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->bind_param('i', $customer_id);
    $stmt->execute();
    $stmt->close();
}

function delete_customer_address(mysqli $conn, int $customer_id, int $address_id): bool
{
    $stmt = $conn->prepare("DELETE FROM customer_addresses WHERE id = ? AND customer_id = ?");
    $stmt->bind_param('ii', $address_id, $customer_id);
    $success = $stmt->execute();
    $stmt->close();

    if ($success) {
        ensure_address_has_default($conn, $customer_id);
    }

    return $success;
}

function fetch_customer_payment_preference(mysqli $conn, int $customer_id): ?string
{
    $stmt = $conn->prepare("SELECT preferred_payment_method FROM customer_preferences WHERE customer_id = ? LIMIT 1");
    $stmt->bind_param('i', $customer_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row['preferred_payment_method'] ?? null;
}

function save_customer_payment_preference(mysqli $conn, int $customer_id, string $method): bool
{
    $stmt = $conn->prepare("INSERT INTO customer_preferences (customer_id, preferred_payment_method) VALUES (?, ?) ON DUPLICATE KEY UPDATE preferred_payment_method = VALUES(preferred_payment_method)");
    $stmt->bind_param('is', $customer_id, $method);
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}

function fetch_customer_category_preferences(mysqli $conn, int $customer_id): array
{
    $stmt = $conn->prepare("SELECT category_preferences FROM customer_preferences WHERE customer_id = ? LIMIT 1");
    $stmt->bind_param('i', $customer_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $decoded = [];
    if (!empty($row['category_preferences'])) {
        $decoded = json_decode($row['category_preferences'], true);
    }
    if (!is_array($decoded)) {
        return [];
    }

    $clean = [];
    foreach ($decoded as $slug => $meta) {
        $key = cafe_normalize_category_slug(is_string($slug) ? $slug : '');
        if ($key === '') {
            continue;
        }
        $label = '';
        $score = 0;
        if (is_array($meta)) {
            $label = isset($meta['label']) ? trim((string) $meta['label']) : '';
            $score = isset($meta['score']) ? (float) $meta['score'] : 0.0;
        } elseif (is_numeric($meta)) {
            $score = (float) $meta;
        }
        if ($label === '') {
            $label = ucwords(str_replace('-', ' ', $key));
        }
        $clean[$key] = [
            'label' => $label,
            'score' => max($score, 0.0),
        ];
    }

    return $clean;
}

function save_customer_category_preferences(mysqli $conn, int $customer_id, array $preferences): bool
{
    $encoded = json_encode($preferences, JSON_UNESCAPED_UNICODE);
    $stmt = $conn->prepare("INSERT INTO customer_preferences (customer_id, category_preferences) VALUES (?, ?) ON DUPLICATE KEY UPDATE category_preferences = VALUES(category_preferences)");
    $stmt->bind_param('is', $customer_id, $encoded);
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}

function record_customer_category_preference(mysqli $conn, int $customer_id, string $category_slug, string $category_label, float $weight = 1.0): bool
{
    $slug = cafe_normalize_category_slug($category_slug ?: $category_label);
    if ($slug === '') {
        return false;
    }
    $label = trim($category_label) !== '' ? trim($category_label) : ucwords(str_replace('-', ' ', $slug));
    if ($label === '') {
        $label = ucwords(str_replace('-', ' ', $slug));
    }

    $preferences = fetch_customer_category_preferences($conn, $customer_id);
    $currentScore = $preferences[$slug]['score'] ?? 0;
    $preferences[$slug] = [
        'label' => $label,
        'score' => min($currentScore + max($weight, 0.1), 25),
    ];

    uasort($preferences, function ($a, $b) {
        return ($b['score'] ?? 0) <=> ($a['score'] ?? 0);
    });

    $trimmed = array_slice($preferences, 0, 12, true);
    return save_customer_category_preferences($conn, $customer_id, $trimmed);
}
