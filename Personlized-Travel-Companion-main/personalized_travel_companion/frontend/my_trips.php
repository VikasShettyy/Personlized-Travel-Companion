<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/backend/trips.php';
require_once dirname(__DIR__) . '/backend/enquiries.php';

require_login();

$pageTitle = 'My Trips & Enquiries';
$userId = (int) $_SESSION['user']['id'];
$userEmail = (string) ($_SESSION['user']['email'] ?? '');
$filter = strtolower(trim($_GET['filter'] ?? 'upcoming'));

$allowedFilters = ['upcoming', 'past', 'draft', 'enquiries'];
if (!in_array($filter, $allowedFilters, true)) {
    $filter = 'upcoming';
}

$counts = get_user_trip_counts($pdo, $userId);
$trips = ($filter !== 'enquiries') ? get_user_trips($pdo, $userId, $filter) : [];
$userEnquiries = get_user_enquiries($pdo, $userId, $userEmail);

$userAvatar = (string) ($_SESSION['user']['profile_picture'] ?? '');
if (empty($userAvatar)) {
    try {
        $userStmt = $pdo->prepare('SELECT profile_picture FROM users WHERE id = ?');
        $userStmt->execute([$userId]);
        $userAvatar = (string) ($userStmt->fetchColumn() ?: '');
        $_SESSION['user']['profile_picture'] = $userAvatar;
    } catch (Throwable $e) {}
}

$extraHead = '
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
';

require_once __DIR__ . '/includes/header.php';
?>

<section class="dashboard-profile card">

    <div class="profile-avatar" aria-hidden="true" style="overflow: hidden; padding: 0;">
        <?php if (!empty($userAvatar)): ?>
            <img src="<?= e(asset_url($userAvatar)) ?>" alt="Profile Picture" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%; display: block;">
        <?php else: ?>
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
        <?php endif; ?>
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
                <strong><?= (int) $counts['total'] ?></strong>
                saved
                <?= (int) $counts['total'] === 1 ? 'trip' : 'trips' ?>
            </span>

            <span>
                <strong><?= (int) $counts['upcoming_count'] ?></strong>
                upcoming
            </span>

            <span>
                <strong><?= count($userEnquiries) ?></strong>
                <?= count($userEnquiries) === 1 ? 'enquiry' : 'enquiries' ?>
            </span>

        </div>

    </div>

    <a class="btn secondary profile-edit" href="profile.php">
        Edit profile
    </a>

</section>

<div class="between dashboard-heading">

    <div>
        <h2>
            <?= $filter === 'enquiries' ? 'My Enquiries' : 'My Trips' ?>
        </h2>

        <p class="muted">
            <?= $filter === 'enquiries'
                ? 'Destination questions you submitted and responses from our travel team.'
                : 'Your saved Karnataka journeys, all in one place.' ?>
        </p>
    </div>

    <a class="btn" href="index.php">
        <i class="bi bi-compass"></i> Explore destinations
    </a>

</div>

<nav class="trip-tabs" aria-label="Filter trips">

    <a
        class="<?= $filter === 'upcoming' ? 'active' : '' ?>"
        href="my_trips.php?filter=upcoming"
    >
        Upcoming Trips
        <span><?= (int) $counts['upcoming_count'] ?></span>
    </a>

    <a
        class="<?= $filter === 'past' ? 'active' : '' ?>"
        href="my_trips.php?filter=past"
    >
        Past Trips
        <span><?= (int) $counts['past_count'] ?></span>
    </a>

    <a
        class="<?= $filter === 'draft' ? 'active' : '' ?>"
        href="my_trips.php?filter=draft"
    >
        Draft Trips
        <span><?= (int) $counts['draft_count'] ?></span>
    </a>

    <a
        class="<?= $filter === 'enquiries' ? 'active' : '' ?>"
        href="my_trips.php?filter=enquiries"
    >
        <i class="bi bi-chat-dots-fill" style="margin-right: 4px;"></i> My Enquiries
        <span><?= count($userEnquiries) ?></span>
    </a>

</nav>

<?php if ($filter === 'enquiries'): ?>

    <!-- ================= USER ENQUIRIES LIST ================= -->
    <?php if (!$userEnquiries): ?>

        <div class="card empty trips-empty">
            <div class="empty-icon">💬</div>
            <h3>No enquiries yet</h3>
            <p>
                Have questions about sightseeing, weather, or custom routes?
                Click "Enquire" on any destination page to send your questions directly to our travel experts.
            </p>
            <a class="btn" href="index.php">
                Browse destinations
            </a>
        </div>

    <?php else: ?>

        <div class="stack" style="gap: 16px;">
            <?php foreach ($userEnquiries as $eq): ?>
                <article class="card" style="padding: 20px; border-radius: 12px; border: 1px solid #dce7df; background: #fff;">
                    <div class="between" style="align-items: flex-start; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
                        <div>
                            <span class="tag" style="background:#f4f8f5; color:#17683e; font-weight:bold; margin-bottom: 4px;">
                                <?= e($eq['destination_name']) ?> (<?= e($eq['district']) ?>)
                            </span>
                            <span class="smalltext text-muted" style="display:block;">
                                Submitted on <?= date('d M Y, h:i A', strtotime($eq['created_at'])) ?>
                            </span>
                        </div>

                        <div>
                            <?php if ($eq['status'] === 'replied'): ?>
                                <span class="tag" style="background: #eaf6ef; color: #185c37; font-weight: bold;">
                                    <i class="bi bi-check-circle-fill"></i> Replied by Admin
                                </span>
                            <?php else: ?>
                                <span class="tag" style="background: #fff3cd; color: #856404; font-weight: bold;">
                                    <i class="bi bi-hourglass-split"></i> Awaiting Reply
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Traveler's original question -->
                    <div style="background: #fafcfb; border: 1px solid #e2e8e3; border-radius: 8px; padding: 12px 14px; margin-bottom: 12px;">
                        <span class="smalltext text-muted" style="font-weight: bold; display: block; margin-bottom: 4px;">
                            Your Message:
                        </span>
                        <p style="margin: 0; font-size: 14px; color: #23352b; white-space: pre-wrap;">
                            <?= e($eq['message']) ?>
                        </p>
                    </div>

                    <!-- Admin's response if replied -->
                    <?php if (!empty($eq['admin_reply'])): ?>
                        <div style="background: #eef7f2; border-left: 4px solid #247653; border-radius: 6px; padding: 14px; margin-bottom: 12px;">
                            <div class="between" style="margin-bottom: 6px;">
                                <strong style="color: #173b2a; font-size: 14px;">
                                    <i class="bi bi-chat-left-quote-fill" style="color: #247653;"></i> Admin Response:
                                </strong>
                                <span class="smalltext text-muted">
                                    <?= $eq['replied_at'] ? date('d M Y, h:i A', strtotime($eq['replied_at'])) : '' ?>
                                </span>
                            </div>
                            <p style="margin: 0; font-size: 14px; color: #173b2a; white-space: pre-wrap; line-height: 1.5;">
                                <?= nl2br(e($eq['admin_reply'])) ?>
                            </p>
                        </div>
                    <?php else: ?>
                        <p class="smalltext text-muted" style="margin: 6px 0 12px;">
                            <i class="bi bi-info-circle"></i> Our team will respond shortly. You'll receive an email notification as well.
                        </p>
                    <?php endif; ?>

                    <div style="display: flex; gap: 10px; justify-content: flex-end;">
                        <a class="btn small secondary" href="destination.php?id=<?= (int) $eq['destination_id'] ?>">
                            View <?= e($eq['destination_name']) ?>
                        </a>
                        <a class="btn small" href="plan_trip.php?destination_id=<?= (int) $eq['destination_id'] ?>">
                            Plan trip
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

    <?php endif; ?>

<?php else: ?>

    <!-- ================= TRIPS LIST ================= -->
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
                                src="<?= e(asset_url($trip['image_url'])) ?>"
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
                                    <?= (int) $trip['duration_days'] ?>
                                </strong>
                                days
                            </span>

                            <span>
                                <strong>
                                    <?= (int) $trip['group_size'] ?>
                                </strong>
                                people
                            </span>

                            <span>
                                Budget
                                <strong>
                                    ₹<?= number_format((float) $trip['budget'], 0) ?>
                                </strong>
                            </span>

                        </div>

                        <div class="actions trip-card-actions">

                            <a
                                class="btn small"
                                href="itinerary.php?id=<?= (int) $trip['id'] ?>"
                            >
                                View trip
                            </a>

                            <a
                                class="btn secondary small"
                                href="edit_trip.php?id=<?= (int) $trip['id'] ?>"
                            >
                                Edit
                            </a>

                        </div>

                    </div>

                </article>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
