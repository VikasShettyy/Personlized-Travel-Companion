<?php
require 'config.php';

require_login();

$pageTitle = 'My Trips';

$userId = (int)$_SESSION['user']['id'];

$filter = strtolower(trim($_GET['filter'] ?? 'upcoming'));

$allowedFilters = ['upcoming', 'past', 'draft'];

if (!in_array($filter, $allowedFilters, true)) {
    $filter = 'upcoming';
}

// Get trip counts based on end date and status
$countStmt = $pdo->prepare(
    "SELECT
        COUNT(*) AS total,

        COALESCE(SUM(
            CASE
                WHEN status != 'draft'
                     AND end_date >= CURDATE()
                THEN 1
                ELSE 0
            END
        ), 0) AS upcoming_count,

        COALESCE(SUM(
            CASE
                WHEN status != 'draft'
                     AND end_date < CURDATE()
                THEN 1
                ELSE 0
            END
        ), 0) AS past_count,

        COALESCE(SUM(
            CASE
                WHEN status = 'draft' THEN 1
                ELSE 0
            END
        ), 0) AS draft_count

     FROM trips
     WHERE user_id = ?"
);

$countStmt->execute([$userId]);

$counts = $countStmt->fetch() ?: [
    'total' => 0,
    'upcoming_count' => 0,
    'past_count' => 0,
    'draft_count' => 0
];

// Get trips for the selected filter
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

if ($filter === 'upcoming') {
    $sql .= " AND t.status != 'draft'
              AND t.end_date >= CURDATE()";
} elseif ($filter === 'past') {
    $sql .= " AND t.status != 'draft'
              AND t.end_date < CURDATE()";
} else {
    $sql .= " AND t.status = 'draft'";
}

$sql .= " ORDER BY t.updated_at DESC, t.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$trips = $stmt->fetchAll();

require 'header.php';
?>

<section class="dashboard-profile card">

    <div class="profile-avatar" aria-hidden="true">
        <?= e(
            strtoupper(
                mb_substr(
                    $_SESSION['user']['full_name'] ?? 'U',
                    0,
                    1,
                    'UTF-8'
                )
            )
        ) ?>
    </div>

    <div class="profile-summary">

        <span class="smalltext">MY ACCOUNT</span>

        <h1>
            <?= e($_SESSION['user']['full_name'] ?? 'Traveller') ?>
        </h1>

        <p class="muted">
            <?= e($_SESSION['user']['email'] ?? '') ?>
        </p>

        <div class="profile-stats">

            <span>
                <strong><?= (int)$counts['total'] ?></strong>
                saved
                <?= (int)$counts['total'] === 1 ? 'trip' : 'trips' ?>
            </span>

            <span>
                <strong><?= (int)$counts['upcoming_count'] ?></strong>
                upcoming
            </span>

            <span>
                <strong><?= (int)$counts['past_count'] ?></strong>
                past
            </span>

        </div>

    </div>

    <a class="btn secondary profile-edit" href="profile.php">
        Edit profile
    </a>

</section>

<div class="between dashboard-heading">

    <div>
        <h2>My Trips</h2>

        <p class="muted">
            Your saved Karnataka journeys, all in one place.
        </p>
    </div>

    <a class="btn" href="index.php">
        Plan a new trip
    </a>

</div>

<nav class="trip-tabs" aria-label="Filter trips">

    <a
        class="<?= $filter === 'upcoming' ? 'active' : '' ?>"
        href="my_trips.php?filter=upcoming"
    >
        Upcoming Trips
        <span><?= (int)$counts['upcoming_count'] ?></span>
    </a>

    <a
        class="<?= $filter === 'past' ? 'active' : '' ?>"
        href="my_trips.php?filter=past"
    >
        Past Trips
        <span><?= (int)$counts['past_count'] ?></span>
    </a>

    <a
        class="<?= $filter === 'draft' ? 'active' : '' ?>"
        href="my_trips.php?filter=draft"
    >
        Draft Trips
        <span><?= (int)$counts['draft_count'] ?></span>
    </a>

</nav>

<?php if (!$trips): ?>

    <div class="card empty trips-empty">

        <div class="empty-icon">✈</div>

        <h3>
            <?php if ($filter === 'upcoming'): ?>
                No upcoming trips yet
            <?php elseif ($filter === 'past'): ?>
                No past trips yet
            <?php else: ?>
                No draft trips yet
            <?php endif; ?>
        </h3>

        <p>
            <?php if ($filter === 'draft'): ?>
                Start planning a journey and save it as a draft
                to see it here.
            <?php else: ?>
                Explore Karnataka and create a personalized
                itinerary to see it here.
            <?php endif; ?>
        </p>

        <a class="btn" href="index.php">
            Explore destinations
        </a>

    </div>

<?php else: ?>

    <div class="trips-list">

        <?php foreach ($trips as $trip): ?>

            <article class="trip-card card">

                <div class="trip-image">

                    <?php if (!empty($trip['image_url'])): ?>

                        <img
                            src="<?= e($trip['image_url']) ?>"
                            alt="<?= e($trip['destination_name']) ?>"
                            loading="lazy"
                        >

                    <?php else: ?>

                        <div class="trip-image-placeholder">
                            TRIP IMAGE
                        </div>

                    <?php endif; ?>

                </div>

                <div class="trip-card-content">

                    <div class="between trip-card-top">

                        <?php
                            $today = date('Y-m-d');

                            if ($trip['status'] === 'draft') {
                                $displayStatus = 'Draft';
                            } elseif (!empty($trip['end_date']) && $trip['end_date'] < $today) {
                                $displayStatus = 'Past';
                            } elseif (!empty($trip['start_date']) && $trip['start_date'] > $today) {
                                $displayStatus = 'Upcoming';
                            } else {
                                $displayStatus = 'Active';
                            }
                        ?>
                        <span class="tag status-<?= strtolower($displayStatus) ?>">
                            <?= e($displayStatus) ?>
                        </span>

                        <span class="smalltext">
                            Saved
                            <?= e(
                                date(
                                    'd M Y',
                                    strtotime($trip['created_at'])
                                )
                            ) ?>
                        </span>

                    </div>

                    <h3><?= e($trip['title']) ?></h3>

                    <p class="muted">
                        <?= e($trip['destination_name']) ?>

                        <?= !empty($trip['district'])
                            ? ' · ' . e($trip['district']) . ' District'
                            : '' ?>
                    </p>

                    <div class="trip-meta">

                        <span>
                            <strong>
                                <?= (int)$trip['duration_days'] ?>
                            </strong>
                            days
                        </span>

                        <span>
                            <strong>
                                <?= (int)$trip['group_size'] ?>
                            </strong>
                            people
                        </span>

                        <span>
                            Budget
                            <strong>
                                ₹<?= number_format((float)$trip['budget'], 0) ?>
                            </strong>
                        </span>

                    </div>

                    <div class="actions trip-card-actions">

                        <a
                            class="btn small"
                            href="itinerary.php?id=<?= (int)$trip['id'] ?>"
                        >
                            View trip
                        </a>

                        <a
                            class="btn secondary small"
                            href="edit_trip.php?id=<?= (int)$trip['id'] ?>"
                        >
                            Edit
                        </a>

                    </div>

                </div>

            </article>

        <?php endforeach; ?>

    </div>

<?php endif; ?>

<?php require 'footer.php'; ?>