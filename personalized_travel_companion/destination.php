<?php
require 'config.php';

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    'SELECT d.*, c.name AS category_name
     FROM destinations d
     LEFT JOIN categories c ON c.id = d.category_id
     WHERE d.id = ?'
);

$stmt->execute([$id]);
$destination = $stmt->fetch();

if (!$destination) {
    http_response_code(404);
    exit('Destination not found.');
}

$activityStmt = $pdo->prepare(
    'SELECT *
     FROM activities
     WHERE destination_id = ?
     ORDER BY name'
);

$activityStmt->execute([$id]);
$activities = $activityStmt->fetchAll();

$pageTitle = $destination['name'];

require 'header.php';
?>

<div class="card destination-detail">
    <div class="two-col">

        <div>
            <?php if (!empty($destination['image_url'])): ?>
                <img
                    class="destination-detail-image"
                    src="<?= e($destination['image_url']) ?>"
                    alt="<?= e($destination['name']) ?>"
                >
            <?php else: ?>
                <div class="destination-detail-placeholder">
                    Destination image
                </div>
            <?php endif; ?>
        </div>

        <div>
            <span class="tag">
                <?= e($destination['category_name'] ?? 'Explore') ?>
            </span>

            <h1><?= e($destination['name']) ?></h1>

            <p class="muted">
                <?= e($destination['district']) ?> District, Karnataka
            </p>

            <p>
                <?= nl2br(e($destination['description'] ?? '')) ?>
            </p>

            <p class="metric">
                ₹<?= number_format((float)$destination['estimated_cost']) ?>

                <span class="smalltext">
                    estimated base cost
                </span>
            </p>

            <a
                class="btn"
                href="plan_trip.php?destination_id=<?= (int)$destination['id'] ?>"
            >
                Plan this trip
            </a>
        </div>

    </div>
</div>

<h2>Available activities</h2>

<div class="grid">
    <?php foreach ($activities as $activity): ?>
        <article class="card">

            <h3><?= e($activity['name']) ?></h3>

            <p class="muted">
                <?= e($activity['description'] ?? '') ?>
            </p>

            <strong>
                Estimated cost:
                ₹<?= number_format((float)$activity['estimated_cost']) ?>
            </strong>

        </article>
    <?php endforeach; ?>
</div>

<?php if (!$activities): ?>
    <div class="card empty">
        Activities will be added by the administrator.
    </div>
<?php endif; ?>

<?php require 'footer.php'; ?>