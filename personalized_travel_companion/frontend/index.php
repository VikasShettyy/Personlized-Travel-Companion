<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/backend/destinations.php';

$pageTitle = 'Explore Karnataka';

$q = trim($_GET['q'] ?? '');
$category = (int) ($_GET['category'] ?? 0);
$minBudget = trim($_GET['budget'] ?? '');

$destinations = get_all_destinations($pdo, $q, $category, $minBudget);
$categories = get_destination_categories($pdo);

require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Section -->
<section class="hero">

    <div class="eyebrow">
        Your Karnataka, your journey
    </div>

    <h1>Explore Karnataka at your own pace</h1>

    <p>
        From the misty coffee hills of Coorg to the ancient ruins of Hampi and quiet beaches of Gokarna — 
        pick where you want to go, choose activities you care about, and plan a realistic day-wise trip within your budget.
    </p>

    <form class="searchbox" method="get" action="index.php" role="search" aria-label="Search destinations">

        <label for="search_query" class="visually-hidden">Search places or districts</label>
        <input
            id="search_query"
            type="search"
            name="q"
            placeholder="Search places or districts (e.g. Coorg, Hampi, Udupi)..."
            value="<?= e($q) ?>"
        >

        <label for="search_category" class="visually-hidden">Filter by interest or category</label>
        <select id="search_category" name="category">
            <option value="0">All interests</option>
            <?php foreach ($categories as $c): ?>
                <option
                    value="<?= $c['id'] ?>"
                    <?= $category === (int) $c['id'] ? 'selected' : '' ?>
                >
                    <?= e($c['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="search_budget" class="visually-hidden">Maximum budget in rupees</label>
        <input
            id="search_budget"
            type="number"
            min="0"
            step="100"
            name="budget"
            placeholder="Max budget (₹)"
            value="<?= e($minBudget) ?>"
        >

        <button type="submit" class="btn">Find places</button>

    </form>

    <!-- Quick Interest Pills -->
    <div class="pills-bar">
        <a href="index.php" class="pill <?= ($category === 0 && empty($q) && empty($minBudget)) ? 'active' : '' ?>">
            All places
        </a>
        <?php foreach ($categories as $c): ?>
            <a
                href="index.php?category=<?= (int) $c['id'] ?>"
                class="pill <?= ($category === (int) $c['id']) ? 'active' : '' ?>"
            >
                <?= e($c['name']) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <p class="smalltext" style="margin-top: 14px; margin-bottom: 0;">
        You can explore places freely as a guest. Log in whenever you want to save your custom itinerary.
    </p>

</section>

<!-- Destinations List Header -->
<div class="between" style="margin-top: 28px; margin-bottom: 14px;">
    <div>
        <h2 style="margin: 0;">Karnataka destinations</h2>
        <span class="muted">
            <?= count($destinations) ?> <?= count($destinations) === 1 ? 'place' : 'places' ?> found
        </span>
    </div>

    <?php if ($q !== '' || $category > 0 || $minBudget !== ''): ?>
        <a class="btn small secondary" href="index.php">
            Clear filters
        </a>
    <?php endif; ?>
</div>

<!-- Destinations Grid -->
<div class="grid">

    <?php foreach ($destinations as $d): ?>

        <article class="card destination">

            <?php if (!empty($d['image_url'])): ?>
                <img
                    src="<?= e(asset_url($d['image_url'])) ?>"
                    alt="<?= e($d['name']) ?> in <?= e($d['district']) ?> district"
                    loading="lazy"
                    onerror="this.style.display='none'; if(this.nextElementSibling) this.nextElementSibling.style.display='flex';"
                >
                <div style="display: none; height: 175px; background: #e8eee9; align-items: center; justify-content: center; color: #738479; font-weight: 600;">
                    <?= e($d['name']) ?>
                </div>
            <?php else: ?>
                <div style="height: 175px; background: #e8eee9; display: flex; align-items: center; justify-content: center; color: #738479; font-weight: 600;">
                    <?= e($d['name']) ?>
                </div>
            <?php endif; ?>

            <div class="body">

                <span class="tag">
                    <?= e($d['category_name'] ?? 'Explore') ?>
                </span>

                <h3 style="margin-top: 8px; margin-bottom: 4px;"><?= e($d['name']) ?></h3>

                <p class="muted" style="margin-top: 0; margin-bottom: 10px; font-size: 13px;">
                    <?= e($d['district']) ?> District
                </p>

                <p style="font-size: 14px; line-height: 1.5; color: #434c46; margin-bottom: 16px;">
                    <?= e(mb_strimwidth($d['description'] ?? '', 0, 120, '…')) ?>
                </p>

                <div class="between" style="border-top: 1px solid #eef3ef; padding-top: 12px; align-items: center;">

                    <div>
                        <span class="muted" style="display: block; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">Starting from</span>
                        <strong style="color: var(--deep); font-size: 16px;">
                            ₹<?= number_format((float) $d['estimated_cost']) ?>
                        </strong>
                    </div>

                    <div style="display: flex; gap: 8px;">
                        <a
                            class="btn small"
                            href="plan_trip.php?destination_id=<?= (int) $d['id'] ?>"
                            aria-label="Plan a trip to <?= e($d['name']) ?>"
                        >
                            Plan trip
                        </a>

                        <a
                            class="btn small secondary"
                            href="destination.php?id=<?= (int) $d['id'] ?>"
                            aria-label="View details for <?= e($d['name']) ?>"
                        >
                            Details
                        </a>
                    </div>

                </div>

            </div>

        </article>

    <?php endforeach; ?>

</div>

<?php if (!$destinations): ?>

    <div class="card empty">
        <h3 style="margin-top: 0; margin-bottom: 6px;">No destinations match those preferences</h3>
        <p class="muted" style="margin-bottom: 16px;">
            Try checking your spelling, removing filters, or increasing your budget limit.
        </p>
        <a class="btn small" href="index.php">Show all destinations</a>
    </div>

<?php endif; ?>

<!-- Friendly How It Works Section -->
<section style="margin-top: 50px; padding: 34px 26px; background: #ffffff; border: 1px solid var(--line); border-radius: 18px;">
    <div style="text-align: center; max-width: 620px; margin: 0 auto 26px;">
        <span class="eyebrow">Trip Planning Made Simple</span>
        <h2 style="margin: 8px 0 6px;">How it works</h2>
        <p class="muted" style="margin: 0; font-size: 14px;">
            No complicated spreadsheets. Just pick where you want to go and organize your days effortlessly.
        </p>
    </div>

    <div class="grid" style="gap: 20px;">
        <div style="padding: 12px;">
            <div style="font-size: 26px; margin-bottom: 8px;" aria-hidden="true">📍</div>
            <h3 style="font-size: 16px; margin: 0 0 6px;">1. Pick a spot</h3>
            <p class="muted" style="font-size: 14px; margin: 0; line-height: 1.5;">
                Browse curated destinations across Karnataka — whether you want mountain breezes, quiet beaches, or historic temples.
            </p>
        </div>

        <div style="padding: 12px;">
            <div style="font-size: 26px; margin-bottom: 8px;" aria-hidden="true">🎒</div>
            <h3 style="font-size: 16px; margin: 0 0 6px;">2. Add activities</h3>
            <p class="muted" style="font-size: 14px; margin: 0; line-height: 1.5;">
                Choose viewpoints, treks, water activities, or local food walks with upfront estimated costs so there are no surprises.
            </p>
        </div>

        <div style="padding: 12px;">
            <div style="font-size: 26px; margin-bottom: 8px;" aria-hidden="true">📅</div>
            <h3 style="font-size: 16px; margin: 0 0 6px;">3. Organize &amp; track budget</h3>
            <p class="muted" style="font-size: 14px; margin: 0; line-height: 1.5;">
                Build an interactive day-by-day plan, move activities between days, and keep your total spend within your target budget.
            </p>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
