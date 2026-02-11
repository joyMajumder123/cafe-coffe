<?php
/**
 * Utility helpers for managing customer addresses and payment preferences.
 */
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
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_customer_preferences_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
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
