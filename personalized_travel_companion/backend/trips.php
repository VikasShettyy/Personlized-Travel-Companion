<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

/**
 * Automatically synchronize trip statuses based on current date for non-draft trips.
 * If start_date <= today <= end_date, the trip is 'active'.
 * If start_date > today, the trip is 'upcoming'.
 * If end_date < today, the trip is 'past'.
 */
function sync_trip_statuses(PDO $pdo, ?int $userId = null): void
{
    try {
        $sql = "
            UPDATE trips
            SET status = CASE
                WHEN end_date < CURDATE() THEN 'past'
                WHEN start_date <= CURDATE() AND end_date >= CURDATE() THEN 'active'
                ELSE 'upcoming'
            END
            WHERE status != 'draft' AND start_date IS NOT NULL AND end_date IS NOT NULL
        ";
        if ($userId !== null && $userId > 0) {
            $sql .= " AND user_id = ?";
            $pdo->prepare($sql)->execute([$userId]);
        } else {
            $pdo->exec($sql);
        }
    } catch (Throwable $e) {
        // Table or sync fallback handled gracefully
    }
}

/**
 * Retrieve user trip statistics (counts by category/status).
 */
function get_user_trip_counts(PDO $pdo, int $userId): array
{
    sync_trip_statuses($pdo, $userId);

    $stmt = $pdo->prepare(
        "SELECT
            COUNT(*) AS total,
            COALESCE(SUM(
                CASE WHEN status = 'active' THEN 1 ELSE 0 END
            ), 0) AS active_count,
            COALESCE(SUM(
                CASE WHEN status = 'upcoming' THEN 1 ELSE 0 END
            ), 0) AS upcoming_count,
            COALESCE(SUM(
                CASE WHEN status = 'past' THEN 1 ELSE 0 END
            ), 0) AS past_count,
            COALESCE(SUM(
                CASE WHEN status = 'draft' THEN 1 ELSE 0 END
            ), 0) AS draft_count
         FROM trips
         WHERE user_id = ?"
    );
    $stmt->execute([$userId]);
    return $stmt->fetch() ?: [
        'total' => 0,
        'active_count' => 0,
        'upcoming_count' => 0,
        'past_count' => 0,
        'draft_count' => 0,
    ];
}

/**
 * Retrieve user trips filtered by active, upcoming, past, or draft.
 */
function get_user_trips(PDO $pdo, int $userId, string $filter = 'active'): array
{
    sync_trip_statuses($pdo, $userId);

    $allowed = ['active', 'upcoming', 'past', 'draft'];
    if (!in_array($filter, $allowed, true)) {
        $filter = 'active';
    }

    $sql = "SELECT
                t.*,
                d.name AS destination_name,
                d.district,
                d.image_url,
                d.estimated_cost AS destination_cost
            FROM trips t
            JOIN destinations d ON d.id = t.destination_id
            WHERE t.user_id = ?";

    $params = [$userId];

    if ($filter === 'active') {
        $sql .= " AND t.status = 'active'";
    } elseif ($filter === 'upcoming') {
        $sql .= " AND t.status = 'upcoming'";
    } elseif ($filter === 'past') {
        $sql .= " AND t.status = 'past'";
    } else {
        $sql .= " AND t.status = 'draft'";
    }

    $sql .= " ORDER BY t.updated_at DESC, t.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Create a new planned trip along with selected activities.
 */
function create_trip(PDO $pdo, int $userId, array $data): int
{
    $destId = (int) ($data['destination_id'] ?? 0);
    $title = trim($data['title'] ?? '');
    $startDate = $data['start_date'] ?? '';
    $endDate = $data['end_date'] ?? '';
    $groupSize = max(1, (int) ($data['group_size'] ?? 1));
    $budget = max(0, (float) ($data['budget'] ?? 0));
    $selectedActivities = (array) ($data['activities'] ?? []);

    $today = date('Y-m-d');
    if ($startDate === '' || $endDate === '' || $endDate < $startDate || $startDate < $today || $endDate < $today) {
        throw new InvalidArgumentException('Invalid trip dates. Dates cannot be in the past (yesterday or earlier).');
    }

    $secDiff = strtotime($endDate) - strtotime($startDate);
    $days = max(1, floor($secDiff / (60 * 60 * 24)) + 1);

    if ($days > 10) {
        throw new InvalidArgumentException('Trip duration cannot exceed 10 days.');
    }

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO trips (user_id, destination_id, title, start_date, end_date, duration_days, group_size, budget, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, "draft")'
        );
        $stmt->execute([
            $userId,
            $destId,
            $title,
            $startDate,
            $endDate,
            $days,
            $groupSize,
            $budget,
        ]);

        $tripId = (int) $pdo->lastInsertId();

        if (!empty($selectedActivities)) {
            $actStmt = $pdo->prepare(
                'INSERT INTO trip_activities (trip_id, activity_id, day_number) VALUES (?, ?, 1)'
            );
            foreach ($selectedActivities as $actId) {
                $actStmt->execute([$tripId, (int) $actId]);
            }
        }

        $pdo->commit();
        return $tripId;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

/**
 * Retrieve trip details for a specific user.
 */
function get_trip_details(PDO $pdo, int $tripId, int $userId): ?array
{
    sync_trip_statuses($pdo, $userId);

    $stmt = $pdo->prepare(
        'SELECT t.*, d.name AS destination_name, d.estimated_cost AS destination_base_cost
         FROM trips t
         JOIN destinations d ON d.id = t.destination_id
         WHERE t.id = ? AND t.user_id = ?'
    );
    $stmt->execute([$tripId, $userId]);
    $res = $stmt->fetch();
    return $res ?: null;
}

/**
 * Retrieve activities mapped to a trip.
 */
function get_trip_activities(PDO $pdo, int $tripId): array
{
    $stmt = $pdo->prepare(
        'SELECT ta.*, a.name, a.estimated_cost
         FROM trip_activities ta
         JOIN activities a ON a.id = ta.activity_id
         WHERE ta.trip_id = ?
         ORDER BY day_number, ta.id'
    );
    $stmt->execute([$tripId]);
    return $stmt->fetchAll();
}

/**
 * Retrieve expenses logged for a trip.
 */
function get_trip_expenses(PDO $pdo, int $tripId): array
{
    $stmt = $pdo->prepare(
        'SELECT * FROM expenses WHERE trip_id = ? ORDER BY created_at DESC'
    );
    $stmt->execute([$tripId]);
    return $stmt->fetchAll();
}

/**
 * Update trip basic details.
 */
function update_trip(PDO $pdo, int $tripId, int $userId, array $data): bool
{
    $title = trim($data['title'] ?? '');
    $startDate = $data['start_date'] ?? '';
    $endDate = $data['end_date'] ?? '';
    $groupSize = max(1, (int) ($data['group_size'] ?? 1));
    $budget = max(0, (float) ($data['budget'] ?? 0));

    $today = date('Y-m-d');
    if ($startDate === '' || $endDate === '' || $endDate < $startDate || $startDate < $today || $endDate < $today) {
        throw new InvalidArgumentException('Invalid dates. Dates cannot be in the past (yesterday or earlier).');
    }
    if ($title === '') {
        throw new InvalidArgumentException('Title cannot be empty.');
    }

    $secDiff = strtotime($endDate) - strtotime($startDate);
    $days = max(1, floor($secDiff / (60 * 60 * 24)) + 1);

    if ($days > 10) {
        throw new InvalidArgumentException('Trip duration cannot exceed 10 days.');
    }

    $stmt = $pdo->prepare(
        'UPDATE trips
         SET title = ?, start_date = ?, end_date = ?, duration_days = ?, group_size = ?, budget = ?, status = "draft"
         WHERE id = ? AND user_id = ?'
    );
    return $stmt->execute([
        $title,
        $startDate,
        $endDate,
        $days,
        $groupSize,
        $budget,
        $tripId,
        $userId,
    ]);
}

/**
 * Confirm a trip (transitions status from draft to active, upcoming, or past based on dates).
 */
function confirm_trip(PDO $pdo, int $tripId, int $userId): bool
{
    $stmt = $pdo->prepare("SELECT start_date, end_date FROM trips WHERE id = ? AND user_id = ?");
    $stmt->execute([$tripId, $userId]);
    $t = $stmt->fetch();

    $today = date('Y-m-d');
    $status = 'upcoming';
    if ($t && !empty($t['start_date']) && !empty($t['end_date'])) {
        if ($t['end_date'] < $today) {
            $status = 'past';
        } elseif ($t['start_date'] <= $today && $t['end_date'] >= $today) {
            $status = 'active';
        } else {
            $status = 'upcoming';
        }
    }

    $updateStmt = $pdo->prepare("UPDATE trips SET status = ? WHERE id = ? AND user_id = ?");
    return $updateStmt->execute([$status, $tripId, $userId]);
}

/**
 * Delete a trip.
 */
function delete_trip(PDO $pdo, int $tripId, int $userId): bool
{
    $stmt = $pdo->prepare('DELETE FROM trips WHERE id = ? AND user_id = ?');
    return $stmt->execute([$tripId, $userId]);
}

/**
 * Reassign an activity to a specific day in the itinerary.
 */
function update_trip_activity_day(PDO $pdo, int $tripId, int $tripActivityId, int $dayNumber): bool
{
    $stmt = $pdo->prepare('UPDATE trip_activities SET day_number = ? WHERE id = ? AND trip_id = ?');
    return $stmt->execute([$dayNumber, $tripActivityId, $tripId]);
}

/**
 * Add a custom user activity to an itinerary day.
 */
function add_custom_activity_to_trip(PDO $pdo, int $tripId, int $destinationId, string $name, float $cost, int $dayNumber): bool
{
    $name = trim($name);
    if ($name === '') {
        return false;
    }
    $cost = max(0, $cost);

    $actStmt = $pdo->prepare('INSERT INTO activities (destination_id, name, estimated_cost) VALUES (?, ?, ?)');
    $actStmt->execute([$destinationId, $name, $cost]);
    $newActivityId = (int) $pdo->lastInsertId();

    $linkStmt = $pdo->prepare('INSERT INTO trip_activities (trip_id, activity_id, day_number) VALUES (?, ?, ?)');
    return $linkStmt->execute([$tripId, $newActivityId, $dayNumber]);
}

/**
 * Add an expense item to a trip.
 */
function add_trip_expense(PDO $pdo, int $tripId, string $title, string $category, float $estimated, float $actual): bool
{
    $title = trim($title);
    if ($title === '') {
        return false;
    }
    $category = trim($category) ?: 'Other';
    $estimated = max(0, $estimated);
    $actual = max(0, $actual);

    $stmt = $pdo->prepare('INSERT INTO expenses (trip_id, title, category, estimated_amount, actual_amount) VALUES (?, ?, ?, ?, ?)');
    return $stmt->execute([$tripId, $title, $category, $estimated, $actual]);
}
