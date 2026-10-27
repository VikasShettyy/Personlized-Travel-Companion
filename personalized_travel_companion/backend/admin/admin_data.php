<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';

/**
 * Retrieve all statistical and tabular data required by the Admin panel.
 */
function get_admin_dashboard_data(PDO $pdo): array
{
    $categories = $pdo->query(
        'SELECT c.*, COUNT(d.id) AS destination_count
         FROM categories c
         LEFT JOIN destinations d ON d.category_id = c.id
         GROUP BY c.id
         ORDER BY c.name'
    )->fetchAll();

    $destinations = $pdo->query(
        'SELECT d.*, c.name AS category_name,
                (SELECT COUNT(*) FROM activities a WHERE a.destination_id = d.id) AS activity_count
         FROM destinations d
         LEFT JOIN categories c ON c.id = d.category_id
         ORDER BY d.name'
    )->fetchAll();

    $activities = $pdo->query(
        'SELECT a.*, d.name AS destination_name
         FROM activities a
         JOIN destinations d ON d.id = a.destination_id
         ORDER BY d.name, a.name'
    )->fetchAll();

    $users = (int) $pdo->query('SELECT COUNT(*) AS c FROM users')->fetch()['c'];
    $trips = (int) $pdo->query('SELECT COUNT(*) AS c FROM trips')->fetch()['c'];

    $recentTrips = $pdo->query(
        'SELECT t.title, t.status, t.budget, u.full_name, d.name AS destination_name
         FROM trips t
         JOIN users u ON u.id = t.user_id
         JOIN destinations d ON d.id = t.destination_id
         ORDER BY t.created_at DESC
         LIMIT 5'
    )->fetchAll();

    $maxCategory = max(1, ...array_map(
        fn($c) => (int) $c['destination_count'],
        $categories ?: [['destination_count' => 1]]
    ));

    require_once dirname(__DIR__) . '/enquiries.php';
    $enquiries = get_admin_enquiries($pdo);
    $pendingEnquiriesCount = get_pending_enquiries_count($pdo);

    require_once __DIR__ . '/admin_actions.php';
    ensure_admin_operations_table($pdo);

    $operations = [];
    try {
        $opStmt = $pdo->query(
            'SELECT ao.*, u.email AS admin_email
             FROM admin_operations ao
             LEFT JOIN users u ON u.id = ao.admin_id
             ORDER BY ao.created_at DESC
             LIMIT 200'
        );
        $operations = $opStmt->fetchAll();
    } catch (Throwable $e) {
        $operations = [];
    }

    return [
        'categories'            => $categories,
        'destinations'          => $destinations,
        'activities'            => $activities,
        'users'                 => $users,
        'trips'                 => $trips,
        'recentTrips'           => $recentTrips,
        'maxCategory'           => $maxCategory,
        'enquiries'             => $enquiries,
        'pendingEnquiriesCount' => $pendingEnquiriesCount,
        'operations'            => $operations,
    ];
}

/**
 * Retrieve admin operations audit log.
 */
function get_admin_operations(PDO $pdo, int $limit = 200): array
{
    require_once __DIR__ . '/admin_actions.php';
    ensure_admin_operations_table($pdo);

    try {
        $stmt = $pdo->prepare(
            'SELECT ao.*, u.email AS admin_email
             FROM admin_operations ao
             LEFT JOIN users u ON u.id = ao.admin_id
             ORDER BY ao.created_at DESC
             LIMIT ?'
        );
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
}
