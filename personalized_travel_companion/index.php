<?php
require 'config.php';

$pageTitle = 'Explore Karnataka';

$q = trim($_GET['q'] ?? '');
$category = (int)($_GET['category'] ?? 0);
$minBudget = trim($_GET['budget'] ?? '');

$sql = "SELECT d.*, c.name AS category_name
        FROM destinations d
        LEFT JOIN categories c ON c.id = d.category_id
        WHERE 1=1";

$params = [];

if ($q !== '') {
    $sql .= " AND (
        d.name LIKE ?
        OR d.district LIKE ?
        OR d.description LIKE ?
    )";

    $like = "%$q%";

    array_push($params, $like, $like, $like);
}

if ($category > 0) {
    $sql .= " AND d.category_id = ?";
    $params[] = $category;
}

if ($minBudget !== '' && is_numeric($minBudget)) {
    $sql .= " AND d.estimated_cost <= ?";
    $params[] = (float)$minBudget;
}

$sql .= " ORDER BY d.name";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$destinations = $stmt->fetchAll();

$categories = $pdo->query(
    "SELECT * FROM categories ORDER BY name"
)->fetchAll();

require 'header.php';
?>

<section class="hero">

    <div class="eyebrow">
        Your Karnataka, your journey
    </div>

    <h1>Explore Karnataka</h1>

    <p>
        Discover places, choose activities, plan a day-wise
        itinerary and keep your trip within budget.
    </p>

    <form class="searchbox" method="get" action="index.php">

        <input
            name="q"
            placeholder="Search destinations or districts"
            value="<?= e($q) ?>"
        >

        <select name="category">
            <option value="0">All interests</option>

            <?php foreach ($categories as $c): ?>
                <option
                    value="<?= $c['id'] ?>"
                    <?= $category === (int)$c['id'] ? 'selected' : '' ?>
                >
                    <?= e($c['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <input
            type="number"
            min="0"
            step="100"
            name="budget"
            placeholder="Max estimated cost (₹)"
            value="<?= e($minBudget) ?>"
        >

        <button class="btn">Find places</button>

    </form>

    <p class="smalltext">
        You can explore as a guest. Login is required to save a trip.
    </p>

</section>

<div class="between">
    <h2>Karnataka destinations</h2>

    <span class="muted">
        <?= count($destinations) ?> places
    </span>
</div>

<div class="grid">

    <?php foreach ($destinations as $d): ?>

        <article class="card destination">

            <?php if (!empty($d['image_url'])): ?>

                <img
                    src="<?= e($d['image_url']) ?>"
                    alt="<?= e($d['name']) ?>"
                    loading="lazy"
                >

            <?php else: ?>

                <div style="height: 165px; background: #e8eee9;"></div>

            <?php endif; ?>

            <div class="body">

                <span class="tag">
                    <?= e($d['category_name'] ?? 'Explore') ?>
                </span>

                <h3><?= e($d['name']) ?></h3>

                <p class="muted">
                    <?= e($d['district']) ?> District
                </p>

                <p>
                    <?= e(
                        mb_strimwidth(
                            $d['description'] ?? '',
                            0,
                            125,
                            '…'
                        )
                    ) ?>
                </p>

                <div class="between">

                    <strong>
                        From ₹<?= number_format((float)$d['estimated_cost']) ?>
                    </strong>

                    <a
                        class="btn small"
                        href="destination.php?id=<?= (int)$d['id'] ?>"
                    >
                        View details
                    </a>

                </div>

            </div>

        </article>

    <?php endforeach; ?>

</div>

<?php if (!$destinations): ?>

    <div class="card empty">
        No destinations match those preferences.
        Try changing the search or budget.
    </div>

<?php endif; ?>

<?php require 'footer.php'; ?>
