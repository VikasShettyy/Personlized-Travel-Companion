<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

/**
 * Retrieve destinations with optional search and filter criteria.
 */
function get_all_destinations(PDO $pdo, string $q = '', int $categoryId = 0, $budget = null): array
{
    $sql = "SELECT d.*, c.name AS category_name
            FROM destinations d
            LEFT JOIN categories c ON c.id = d.category_id
            WHERE 1=1";
    $params = [];

    $q = trim($q);
    if ($q !== '') {
        $sql .= " AND (
            d.name LIKE ?
            OR d.district LIKE ?
            OR d.description LIKE ?
        )";
        $like = "%$q%";
        array_push($params, $like, $like, $like);
    }

    if ($categoryId > 0) {
        $sql .= " AND d.category_id = ?";
        $params[] = $categoryId;
    }

    if ($budget !== null && $budget !== '' && is_numeric($budget)) {
        $sql .= " AND d.estimated_cost <= ?";
        $params[] = (float) $budget;
    }

    $sql .= " ORDER BY d.name";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Retrieve all categories ordered by name.
 */
function get_destination_categories(PDO $pdo): array
{
    return $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
}

/**
 * Retrieve single destination with its category details.
 */
function get_destination_by_id(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare(
        'SELECT d.*, c.name AS category_name
         FROM destinations d
         LEFT JOIN categories c ON c.id = d.category_id
         WHERE d.id = ?'
    );
    $stmt->execute([$id]);
    $res = $stmt->fetch();
    return $res ?: null;
}

/**
 * Retrieve activities for a destination.
 */
function get_destination_activities(PDO $pdo, int $destinationId): array
{
    $stmt = $pdo->prepare(
        'SELECT * FROM activities WHERE destination_id = ? ORDER BY name'
    );
    $stmt->execute([$destinationId]);
    return $stmt->fetchAll();
}
