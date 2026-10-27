<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/backend/admin/admin_actions.php';
require_once dirname(__DIR__, 2) . '/backend/admin/admin_data.php';

require_admin();

$pageTitle = 'Admin Panel';

$res = handle_admin_action($pdo);
if ($res !== null) {
    redirect('index.php#' . $res['tab']);
}

$data = get_admin_dashboard_data($pdo);
$categories   = $data['categories'];
$destinations = $data['destinations'];
$activities   = $data['activities'];
$users        = $data['users'];
$trips        = $data['trips'];
$recentTrips  = $data['recentTrips'];
$maxCategory  = $data['maxCategory'];
$enquiries    = $data['enquiries'];
$pendingEnquiriesCount = $data['pendingEnquiriesCount'];
$operations   = $data['operations'] ?? [];

$extraHead = '
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
';

$flashToast = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

$errorToast = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_error']);

require_once dirname(__DIR__) . '/includes/header.php';

/** Render the hidden fields shared by every admin form. */
function admin_fields(string $action, string $tab): void
{
    echo '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
    echo '<input type="hidden" name="action" value="' . e($action) . '">';
    echo '<input type="hidden" name="tab" value="' . e($tab) . '">';
}
?>

<!-- Toast Container for modern operation feedback -->
<div class="toast-container position-fixed top-0 end-0 p-3" id="adminToastContainer" style="z-index: 1090;" aria-live="polite" aria-atomic="true"></div>
<div id="admin-toast-data" data-flash="<?= e($flashToast) ?>" data-error="<?= e($errorToast) ?>" hidden></div>

<div class="admin-page">

    <header class="between admin-header" aria-label="Admin Page Header">
        <div>
            <p class="eyebrow">Administration</p>
            <h1>Admin panel</h1>
            <p class="muted">
                Manage the destinations, categories and activities shown to users.
            </p>
        </div>

        <div class="actions">
            <!-- Screen Reader Toggle Controls -->
            <button type="button" class="btn secondary screen-reader-btn" id="btn-screen-reader"
                    aria-pressed="false" aria-label="Toggle screen reader to read screen content aloud"
                    title="Click to start or stop screen reader">
                <i class="bi bi-volume-up-fill" id="screen-reader-icon" aria-hidden="true"></i>
                <span id="screen-reader-label">Screen Reader</span>
            </button>
            <button type="button" class="btn danger small" id="btn-stop-reader"
                    style="display: none;" aria-label="Stop screen reading" title="Stop screen reading">
                <i class="bi bi-stop-circle" aria-hidden="true"></i> Stop
            </button>
        </div>
    </header>

    <?php if ($errorToast): ?>
        <div class="flash error" role="alert" id="admin-error" tabindex="-1">
            <i class="bi bi-exclamation-octagon" aria-hidden="true"></i>
            <span class="visually-hidden">Error:</span>
            <?= e($errorToast) ?>
        </div>
    <?php endif; ?>

    <nav class="admin-tabs-nav" aria-label="Admin Sections Navigation">
        <div role="tablist" aria-label="Admin sections" class="admin-tabs nav">
            <button class="nav-link active" id="tab-overview" type="button"
                    role="tab" data-bs-toggle="pill" data-bs-target="#panel-overview"
                    aria-controls="panel-overview" aria-selected="true">
                <i class="bi bi-speedometer2" aria-hidden="true"></i> Overview
            </button>
            <button class="nav-link" id="tab-destinations" type="button"
                    role="tab" data-bs-toggle="pill" data-bs-target="#panel-destinations"
                    aria-controls="panel-destinations" aria-selected="false" tabindex="-1">
                <i class="bi bi-geo-alt" aria-hidden="true"></i> Destinations
                <span><?= count($destinations) ?></span>
            </button>
            <button class="nav-link" id="tab-categories" type="button"
                    role="tab" data-bs-toggle="pill" data-bs-target="#panel-categories"
                    aria-controls="panel-categories" aria-selected="false" tabindex="-1">
                <i class="bi bi-tags" aria-hidden="true"></i> Categories
                <span><?= count($categories) ?></span>
            </button>
            <button class="nav-link" id="tab-activities" type="button"
                    role="tab" data-bs-toggle="pill" data-bs-target="#panel-activities"
                    aria-controls="panel-activities" aria-selected="false" tabindex="-1">
                <i class="bi bi-compass" aria-hidden="true"></i> Activities
                <span><?= count($activities) ?></span>
            </button>
            <button class="nav-link" id="tab-enquiries" type="button"
                    role="tab" data-bs-toggle="pill" data-bs-target="#panel-enquiries"
                    aria-controls="panel-enquiries" aria-selected="false" tabindex="-1">
                <i class="bi bi-chat-left-text" aria-hidden="true"></i> Enquiries
                <?php if ($pendingEnquiriesCount > 0): ?>
                    <span class="badge bg-danger" style="font-size: 11px;"><?= $pendingEnquiriesCount ?></span>
                <?php else: ?>
                    <span><?= count($enquiries) ?></span>
                <?php endif; ?>
            </button>
            <button class="nav-link" id="tab-operations" type="button"
                    role="tab" data-bs-toggle="pill" data-bs-target="#panel-operations"
                    aria-controls="panel-operations" aria-selected="false" tabindex="-1">
                <i class="bi bi-clock-history" aria-hidden="true"></i> Admin Operations
                <span><?= count($operations) ?></span>
            </button>
            <button class="nav-link" id="tab-security" type="button"
                    role="tab" data-bs-toggle="pill" data-bs-target="#panel-security"
                    aria-controls="panel-security" aria-selected="false" tabindex="-1">
                <i class="bi bi-shield-lock" aria-hidden="true"></i> Security & Password
            </button>
        </div>
    </nav>

    <div class="tab-content">

        <!-- ============ OVERVIEW ============ -->
        <section class="tab-pane fade show active" id="panel-overview" role="tabpanel"
                 aria-labelledby="tab-overview" tabindex="0">

            <h2 class="visually-hidden">Overview</h2>

            <dl class="admin-stats">
                <?php
                $stats = [
                    ['Destinations', count($destinations), 'bi-geo-alt'],
                    ['Categories', count($categories), 'bi-tags'],
                    ['Activities', count($activities), 'bi-compass'],
                    ['Enquiries', count($enquiries), 'bi-chat-left-text'],
                    ['Operations', count($operations), 'bi-clock-history'],
                    ['Users', $users, 'bi-people'],
                    ['Trips', $trips, 'bi-suitcase2'],
                ];
                foreach ($stats as [$label, $value, $icon]): ?>
                    <div class="card stat-card">
                        <span class="stat-icon" aria-hidden="true">
                            <i class="bi <?= $icon ?>"></i>
                        </span>
                        <div>
                            <dt class="muted"><?= $label ?></dt>
                            <dd class="metric"><?= $value ?></dd>
                        </div>
                    </div>
                <?php endforeach; ?>
            </dl>

            <div class="two-col admin-overview">
                <div class="card">
                    <h3>Destinations by category</h3>

                    <?php if (!$categories): ?>
                        <p class="muted">No categories yet.</p>
                    <?php else: ?>
                        <ul class="bar-list">
                            <?php foreach ($categories as $c): ?>
                                <li>
                                    <div class="between">
                                        <span><?= e($c['name']) ?></span>
                                        <strong>
                                            <?= (int) $c['destination_count'] ?>
                                            <span class="visually-hidden">destinations</span>
                                        </strong>
                                    </div>
                                    <div class="progress" aria-hidden="true">
                                        <span style="width: <?= round(
                                            (int) $c['destination_count'] / $maxCategory * 100
                                        ) ?>%"></span>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>

                <div class="card">
                    <h3>Recent trips</h3>

                    <?php if (!$recentTrips): ?>
                        <p class="muted">No trips have been created yet.</p>
                    <?php else: ?>
                        <ul class="recent-list">
                            <?php foreach ($recentTrips as $t): ?>
                                <li>
                                    <div class="between">
                                        <strong><?= e($t['title']) ?></strong>
                                        <span class="tag status-<?= e($t['status']) ?>">
                                            <?= e(ucfirst($t['status'])) ?>
                                        </span>
                                    </div>
                                    <span class="smalltext">
                                        <?= e($t['full_name']) ?> to
                                        <?= e($t['destination_name']) ?>,
                                        budget &#8377;<?= number_format((float) $t['budget']) ?>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- ============ DESTINATIONS ============ -->
        <section class="tab-pane fade" id="panel-destinations" role="tabpanel"
                 aria-labelledby="tab-destinations" tabindex="0">

            <div class="between admin-section-head">
                <h2>Destinations</h2>
                <button type="button" class="btn" data-bs-toggle="modal"
                        data-bs-target="#destinationModal">
                    <i class="bi bi-plus-lg" aria-hidden="true"></i> Add destination
                </button>
            </div>

            <div class="admin-toolbar">
                <label for="destination-filter">
                    Search destinations
                    <input type="search" id="destination-filter"
                           placeholder="Name, district or category"
                           data-filter-table="destination-table"
                           data-filter-count="destination-count"
                           data-filter-noun="destinations"
                           autocomplete="off">
                </label>
            </div>

            <p class="smalltext" id="destination-count" role="status">
                Showing <?= count($destinations) ?> of <?= count($destinations) ?> destinations.
            </p>

            <div class="table-wrap">
                <table class="admin-table" id="destination-table">
                    <caption class="visually-hidden">
                        Destinations with category, activity count, cost and actions
                    </caption>
                    <thead>
                        <tr>
                            <th scope="col">Image</th>
                            <th scope="col">Destination</th>
                            <th scope="col">Category</th>
                            <th scope="col">Activities</th>
                            <th scope="col">Base cost</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($destinations as $d): ?>
                            <tr data-search="<?= e(strtolower(
                                $d['name'] . ' ' . $d['district'] . ' ' . ($d['category_name'] ?? '')
                            )) ?>">
                                <td data-label="Image">
                                    <?php if ($d['image_url']): ?>
                                        <img class="thumb" src="<?= e(asset_url($d['image_url'])) ?>"
                                             alt="" width="64" height="48" loading="lazy">
                                    <?php else: ?>
                                        <span class="thumb thumb-empty">
                                            <i class="bi bi-image" aria-hidden="true"></i>
                                            <span class="visually-hidden">No image</span>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Destination">
                                    <strong><?= e($d['name']) ?></strong><br>
                                    <span class="smalltext"><?= e($d['district']) ?></span>
                                </td>
                                <td data-label="Category">
                                    <?php if ($d['category_name']): ?>
                                        <span class="tag"><?= e($d['category_name']) ?></span>
                                    <?php else: ?>
                                        <span class="muted">Uncategorised</span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Activities"><?= (int) $d['activity_count'] ?></td>
                                <td data-label="Base cost">
                                    &#8377;<?= number_format((float) $d['estimated_cost']) ?>
                                </td>
                                <td data-label="Actions">
                                    <div class="actions">
                                        <button type="button" class="btn secondary small"
                                                data-bs-toggle="modal"
                                                data-bs-target="#destinationModal"
                                                data-id="<?= (int) $d['id'] ?>"
                                                data-name="<?= e($d['name']) ?>"
                                                data-district="<?= e($d['district']) ?>"
                                                data-category="<?= (int) $d['category_id'] ?>"
                                                data-cost="<?= e((string) $d['estimated_cost']) ?>"
                                                data-image="<?= e($d['image_url']) ?>"
                                                data-description="<?= e($d['description']) ?>"
                                                aria-label="Edit <?= e($d['name']) ?>">
                                            <i class="bi bi-pencil-square" aria-hidden="true"></i>
                                            Edit
                                        </button>
                                        <button type="button" class="btn danger small"
                                                data-bs-toggle="modal"
                                                data-bs-target="#deleteModal"
                                                data-action="destination_delete"
                                                data-tab="destinations"
                                                data-id="<?= (int) $d['id'] ?>"
                                                data-name="<?= e($d['name']) ?>"
                                                data-note="All activities of this destination will also be deleted."
                                                aria-label="Delete <?= e($d['name']) ?>">
                                            <i class="bi bi-trash" aria-hidden="true"></i>
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <tr class="no-results" <?= $destinations ? 'hidden' : '' ?>>
                            <td colspan="6" class="empty">No destinations found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- ============ CATEGORIES ============ -->
        <section class="tab-pane fade" id="panel-categories" role="tabpanel"
                 aria-labelledby="tab-categories" tabindex="0">

            <div class="between admin-section-head">
                <h2>Categories</h2>
                <button type="button" class="btn" data-bs-toggle="modal"
                        data-bs-target="#categoryModal">
                    <i class="bi bi-plus-lg" aria-hidden="true"></i> Add category
                </button>
            </div>

            <div class="table-wrap">
                <table class="admin-table admin-table-narrow">
                    <caption class="visually-hidden">
                        Categories with destination counts and actions
                    </caption>
                    <thead>
                        <tr>
                            <th scope="col">Category</th>
                            <th scope="col">Destinations</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $c): ?>
                            <tr>
                                <td data-label="Category"><strong><?= e($c['name']) ?></strong></td>
                                <td data-label="Destinations"><?= (int) $c['destination_count'] ?></td>
                                <td data-label="Actions">
                                    <div class="actions">
                                        <button type="button" class="btn secondary small"
                                                data-bs-toggle="modal"
                                                data-bs-target="#categoryModal"
                                                data-id="<?= (int) $c['id'] ?>"
                                                data-name="<?= e($c['name']) ?>"
                                                aria-label="Edit <?= e($c['name']) ?>">
                                            <i class="bi bi-pencil-square" aria-hidden="true"></i>
                                            Edit
                                        </button>
                                        <button type="button" class="btn danger small"
                                                data-bs-toggle="modal"
                                                data-bs-target="#deleteModal"
                                                data-action="category_delete"
                                                data-tab="categories"
                                                data-id="<?= (int) $c['id'] ?>"
                                                data-name="<?= e($c['name']) ?>"
                                                data-note="Destinations in this category will become uncategorised."
                                                aria-label="Delete <?= e($c['name']) ?>">
                                            <i class="bi bi-trash" aria-hidden="true"></i>
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (!$categories): ?>
                            <tr><td colspan="3" class="empty">No categories yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- ============ ACTIVITIES ============ -->
        <section class="tab-pane fade" id="panel-activities" role="tabpanel"
                 aria-labelledby="tab-activities" tabindex="0">

            <div class="between admin-section-head">
                <h2>Activities</h2>
                <button type="button" class="btn" data-bs-toggle="modal"
                        data-bs-target="#activityModal"
                        <?= $destinations ? '' : 'disabled aria-disabled="true"' ?>>
                    <i class="bi bi-plus-lg" aria-hidden="true"></i> Add activity
                </button>
            </div>

            <?php if (!$destinations): ?>
                <p class="warning">Add a destination before creating activities.</p>
            <?php endif; ?>

            <div class="admin-toolbar">
                <label for="activity-filter">
                    Search activities
                    <input type="search" id="activity-filter"
                           placeholder="Activity or description"
                           data-filter-table="activity-table"
                           data-filter-count="activity-count"
                           data-filter-noun="activities"
                           data-filter-select="activity-destination-filter"
                           autocomplete="off">
                </label>

                <label for="activity-destination-filter">
                    Filter by destination
                    <select id="activity-destination-filter">
                        <option value="">All destinations</option>
                        <?php foreach ($destinations as $d): ?>
                            <option value="<?= (int) $d['id'] ?>"><?= e($d['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>

            <p class="smalltext" id="activity-count" role="status">
                Showing <?= count($activities) ?> of <?= count($activities) ?> activities.
            </p>

            <div class="table-wrap">
                <table class="admin-table" id="activity-table">
                    <caption class="visually-hidden">
                        Activities with destination, cost and actions
                    </caption>
                    <thead>
                        <tr>
                            <th scope="col">Activity</th>
                            <th scope="col">Destination</th>
                            <th scope="col">Cost</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($activities as $a): ?>
                            <tr data-dest="<?= (int) $a['destination_id'] ?>"
                                data-search="<?= e(strtolower(
                                    $a['name'] . ' ' . $a['description'] . ' ' . $a['destination_name']
                                )) ?>">
                                <td data-label="Activity">
                                    <strong><?= e($a['name']) ?></strong>
                                    <?php if ($a['description']): ?>
                                        <br><span class="smalltext"><?= e($a['description']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Destination"><?= e($a['destination_name']) ?></td>
                                <td data-label="Cost">
                                    &#8377;<?= number_format((float) $a['estimated_cost']) ?>
                                </td>
                                <td data-label="Actions">
                                    <div class="actions">
                                        <button type="button" class="btn secondary small"
                                                data-bs-toggle="modal"
                                                data-bs-target="#activityModal"
                                                data-id="<?= (int) $a['id'] ?>"
                                                data-destination="<?= (int) $a['destination_id'] ?>"
                                                data-name="<?= e($a['name']) ?>"
                                                data-cost="<?= e((string) $a['estimated_cost']) ?>"
                                                data-description="<?= e($a['description']) ?>"
                                                aria-label="Edit <?= e($a['name']) ?>">
                                            <i class="bi bi-pencil-square" aria-hidden="true"></i>
                                            Edit
                                        </button>
                                        <button type="button" class="btn danger small"
                                                data-bs-toggle="modal"
                                                data-bs-target="#deleteModal"
                                                data-action="activity_delete"
                                                data-tab="activities"
                                                data-id="<?= (int) $a['id'] ?>"
                                                data-name="<?= e($a['name']) ?>"
                                                data-note=""
                                                aria-label="Delete <?= e($a['name']) ?>">
                                            <i class="bi bi-trash" aria-hidden="true"></i>
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <tr class="no-results" <?= $activities ? 'hidden' : '' ?>>
                            <td colspan="4" class="empty">No activities found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- ============ ENQUIRIES ============ -->
        <section class="tab-pane fade" id="panel-enquiries" role="tabpanel"
                 aria-labelledby="tab-enquiries" tabindex="0">

            <div class="between admin-section-head">
                <div>
                    <h2>Destination Enquiries</h2>
                    <p class="muted smalltext">
                        Customer questions from destination pages. Replying sends an email to the traveler and updates their dashboard.
                    </p>
                </div>
            </div>

            <div class="admin-toolbar">
                <label for="enquiry-filter">
                    Search enquiries
                    <input type="search" id="enquiry-filter"
                           placeholder="Traveler name, email, destination or message..."
                           data-filter-table="enquiry-table"
                           data-filter-count="enquiry-count"
                           data-filter-noun="enquiries"
                           autocomplete="off">
                </label>
            </div>

            <p class="smalltext" id="enquiry-count" role="status">
                Showing <?= count($enquiries) ?> of <?= count($enquiries) ?> enquiries.
            </p>

            <div class="table-wrap">
                <table class="admin-table" id="enquiry-table">
                    <caption class="visually-hidden">Destination enquiries from travelers</caption>
                    <thead>
                        <tr>
                            <th scope="col">Date</th>
                            <th scope="col">Destination</th>
                            <th scope="col">Traveler</th>
                            <th scope="col" style="min-width: 280px;">Message & Response</th>
                            <th scope="col">Status</th>
                            <th scope="col">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($enquiries as $eq): ?>
                            <tr data-search="<?= e(strtolower(
                                $eq['user_name'] . ' ' . $eq['user_email'] . ' ' . $eq['destination_name'] . ' ' . $eq['message']
                            )) ?>">
                                <td data-label="Date">
                                    <span class="smalltext"><?= date('d M Y, h:i A', strtotime($eq['created_at'])) ?></span>
                                </td>
                                <td data-label="Destination">
                                    <strong><?= e($eq['destination_name']) ?></strong><br>
                                    <span class="smalltext text-muted"><?= e($eq['district']) ?></span>
                                </td>
                                <td data-label="Traveler">
                                    <strong><?= e($eq['user_name']) ?></strong><br>
                                    <a href="mailto:<?= e($eq['user_email']) ?>" class="smalltext" style="color: #247653; text-decoration: none;">
                                        <?= e($eq['user_email']) ?>
                                    </a>
                                </td>
                                <td data-label="Message & Response">
                                    <div style="font-size: 13px; line-height: 1.4; color: #23352b;">
                                        <?= nl2br(e($eq['message'])) ?>
                                    </div>
                                    <?php if (!empty($eq['admin_reply'])): ?>
                                        <div style="margin-top: 8px; padding: 8px 10px; background: #eaf6ef; border-left: 3px solid #247653; border-radius: 4px; font-size: 12px; color: #173b2a;">
                                            <strong>Admin Reply:</strong> <?= nl2br(e($eq['admin_reply'])) ?>
                                            <div class="smalltext text-muted" style="margin-top: 3px;">
                                                Replied <?= $eq['replied_at'] ? date('d M Y, h:i A', strtotime($eq['replied_at'])) : '' ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Status">
                                    <?php if ($eq['status'] === 'replied'): ?>
                                        <span class="tag" style="background:#eaf6ef; color:#185c37;">
                                            <i class="bi bi-check2-all" aria-hidden="true"></i> Replied
                                        </span>
                                    <?php else: ?>
                                        <span class="tag" style="background:#fff3cd; color:#856404;">
                                            <i class="bi bi-hourglass-split" aria-hidden="true"></i> Pending
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Action">
                                    <button type="button" class="btn small <?= $eq['status'] === 'replied' ? 'secondary' : '' ?>"
                                            data-bs-toggle="modal"
                                            data-bs-target="#enquiryReplyModal"
                                            data-id="<?= (int) $eq['id'] ?>"
                                            data-destination="<?= e($eq['destination_name']) ?>"
                                            data-name="<?= e($eq['user_name']) ?>"
                                            data-email="<?= e($eq['user_email']) ?>"
                                            data-message="<?= e($eq['message']) ?>"
                                            data-reply="<?= e($eq['admin_reply'] ?? '') ?>"
                                            aria-label="Reply to enquiry from <?= e($eq['user_name']) ?>">
                                        <i class="bi bi-reply-fill" aria-hidden="true"></i>
                                        <?= $eq['status'] === 'replied' ? 'Edit reply' : 'Reply' ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <tr class="no-results" <?= $enquiries ? 'hidden' : '' ?>>
                            <td colspan="6" class="empty">No enquiries found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- ============ ADMIN OPERATIONS ============ -->
        <section class="tab-pane fade" id="panel-operations" role="tabpanel"
                 aria-labelledby="tab-operations" tabindex="0">

            <header class="admin-section-head">
                <div>
                    <h2>Admin Operations Log</h2>
                    <p class="muted smalltext">
                        Complete audit record of administrative operations, content updates, and system activities.
                    </p>
                </div>
            </header>

            <div class="admin-toolbar">
                <label for="operations-filter">
                    Search operations
                    <input type="search" id="operations-filter"
                           placeholder="Filter by operation, admin, or description..."
                           data-filter-table="operations-table"
                           data-filter-count="operations-count"
                           data-filter-noun="operations">
                </label>
            </div>

            <p class="smalltext" id="operations-count" role="status">
                Showing <?= count($operations) ?> of <?= count($operations) ?> operations.
            </p>

            <div class="table-wrap">
                <table class="admin-table" id="operations-table">
                    <caption class="visually-hidden">
                        Audit table showing admin operation history, users, targets, and dates
                    </caption>
                    <thead>
                        <tr>
                            <th scope="col" style="width: 50px;">#</th>
                            <th scope="col" style="width: 160px;">Date &amp; Time</th>
                            <th scope="col" style="width: 140px;">Administrator</th>
                            <th scope="col" style="width: 150px;">Operation</th>
                            <th scope="col" style="width: 110px;">Target</th>
                            <th scope="col">Description &amp; Details</th>
                            <th scope="col" style="width: 130px;">IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($operations as $idx => $op):
                            $type = $op['operation_type'] ?? '';
                            $badgeColor = '#247653';
                            $badgeBg = '#eaf6ef';

                            if (str_contains($type, 'Delete')) {
                                $badgeColor = '#a83232';
                                $badgeBg = '#feebeb';
                            } elseif (str_contains($type, 'Update')) {
                                $badgeColor = '#1d4ed8';
                                $badgeBg = '#eff6ff';
                            } elseif (str_contains($type, 'Reply')) {
                                $badgeColor = '#6b21a8';
                                $badgeBg = '#f3e8ff';
                            } elseif (str_contains($type, 'Password')) {
                                $badgeColor = '#b45309';
                                $badgeBg = '#fef3c7';
                            }
                            $searchText = strtolower($op['operation_type'] . ' ' . $op['admin_name'] . ' ' . ($op['admin_email'] ?? '') . ' ' . $op['target_type'] . ' ' . $op['description'] . ' ' . ($op['details'] ?? '') . ' ' . $op['ip_address']);
                        ?>
                            <tr data-search="<?= e($searchText) ?>">
                                <td data-label="#">
                                    <span class="muted smalltext">#<?= (int) ($op['id'] ?? ($idx + 1)) ?></span>
                                </td>
                                <td data-label="Date & Time">
                                    <strong style="display: block; font-size: 13px;">
                                        <?= date('d M Y', strtotime($op['created_at'])) ?>
                                    </strong>
                                    <span class="muted smalltext">
                                        <?= date('h:i:s A', strtotime($op['created_at'])) ?>
                                    </span>
                                </td>
                                <td data-label="Administrator">
                                    <strong style="font-size: 13px;"><?= e($op['admin_name']) ?></strong>
                                    <?php if (!empty($op['admin_email'])): ?>
                                        <span class="muted smalltext" style="display: block; font-size: 11px;">
                                            <?= e($op['admin_email']) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Operation">
                                    <span class="tag" style="background: <?= $badgeBg ?>; color: <?= $badgeColor ?>; font-weight: 700; font-size: 11px; padding: 3px 8px; border-radius: 4px;">
                                        <?= e($op['operation_type']) ?>
                                    </span>
                                </td>
                                <td data-label="Target">
                                    <span class="tag secondary" style="font-size: 11px; text-transform: uppercase;">
                                        <?= e($op['target_type'] ?: 'system') ?>
                                    </span>
                                </td>
                                <td data-label="Description">
                                    <div style="font-size: 13px; color: #23352b; font-weight: 500;">
                                        <?= e($op['description']) ?>
                                    </div>
                                    <?php if (!empty($op['details'])): ?>
                                        <div class="muted smalltext" style="margin-top: 3px; font-size: 12px; color: #68776e;">
                                            <?= e($op['details']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td data-label="IP Address">
                                    <code style="font-size: 11px; padding: 2px 6px; background: #f0f4f1; border-radius: 4px; color: #36493f;">
                                        <?= e($op['ip_address'] ?: '127.0.0.1') ?>
                                    </code>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <tr class="no-results" <?= $operations ? 'hidden' : '' ?>>
                            <td colspan="7" class="empty">No admin operations logged yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- ============ SECURITY & PASSWORD ============ -->
        <section class="tab-pane fade" id="panel-security" role="tabpanel"
                 aria-labelledby="tab-security" tabindex="0">

            <header class="admin-section-head">
                <div>
                    <h2>Admin Security & Password</h2>
                    <p class="muted smalltext">
                        Update your administrator credentials with real-time password validation.
                    </p>
                </div>
            </header>

            <div class="card password-card-wrap">
                <form class="stack" method="post" action="index.php" id="adminPasswordForm" novalidate>
                    <?php admin_fields('admin_change_password', 'security'); ?>

                    <fieldset class="password-fieldset">
                        <legend class="visually-hidden">Change Administrator Password</legend>

                        <div class="form-group mb-3">
                            <label for="current_password">
                                <span>Current Password <span class="req" aria-hidden="true">*</span></span>
                            </label>
                            <div class="password-input-group">
                                <input type="password" id="current_password" name="current_password"
                                       required autocomplete="current-password"
                                       placeholder="Enter current password">
                                <button type="button" class="password-toggle-btn"
                                        data-toggle-for="current_password"
                                        aria-label="Toggle current password visibility">
                                    <i class="bi bi-eye" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label for="new_password">
                                <span>New Password <span class="req" aria-hidden="true">*</span></span>
                            </label>
                            <div class="password-input-group">
                                <input type="password" id="new_password" name="new_password"
                                       required autocomplete="new-password"
                                       minlength="8" maxlength="72"
                                       placeholder="Enter strong new password"
                                       aria-describedby="password-strength-wrap">
                                <button type="button" class="password-toggle-btn"
                                        data-toggle-for="new_password"
                                        aria-label="Toggle new password visibility">
                                    <i class="bi bi-eye" aria-hidden="true"></i>
                                </button>
                            </div>

                            <!-- Password strength meter -->
                            <div class="password-strength-wrap" id="password-strength-wrap" aria-live="polite">
                                <div class="between mb-1">
                                    <span class="smalltext">Password Strength:</span>
                                    <span class="smalltext strength-label" id="strength-label">Too short</span>
                                </div>
                                <div class="strength-bar-bg">
                                    <div class="strength-bar-fill" id="strength-bar"></div>
                                </div>
                            </div>

                            <!-- Requirements checklist -->
                            <ul class="password-checklist" id="password-checklist" aria-label="Password Requirements">
                                <li id="rule-length" class="rule-invalid">
                                    <i class="bi bi-circle" aria-hidden="true"></i>
                                    <span>At least 8 characters</span>
                                </li>
                                <li id="rule-upper" class="rule-invalid">
                                    <i class="bi bi-circle" aria-hidden="true"></i>
                                    <span>At least one uppercase letter (A-Z)</span>
                                </li>
                                <li id="rule-lower" class="rule-invalid">
                                    <i class="bi bi-circle" aria-hidden="true"></i>
                                    <span>At least one lowercase letter (a-z)</span>
                                </li>
                                <li id="rule-number" class="rule-invalid">
                                    <i class="bi bi-circle" aria-hidden="true"></i>
                                    <span>At least one numeric digit (0-9)</span>
                                </li>
                                <li id="rule-special" class="rule-invalid">
                                    <i class="bi bi-circle" aria-hidden="true"></i>
                                    <span>At least one special character (!@#$%^&*...)</span>
                                </li>
                            </ul>
                        </div>

                        <div class="form-group mb-3">
                            <label for="confirm_password">
                                <span>Confirm New Password <span class="req" aria-hidden="true">*</span></span>
                            </label>
                            <div class="password-input-group">
                                <input type="password" id="confirm_password" name="confirm_password"
                                       required autocomplete="new-password"
                                       placeholder="Re-enter new password">
                                <button type="button" class="password-toggle-btn"
                                        data-toggle-for="confirm_password"
                                        aria-label="Toggle confirm password visibility">
                                    <i class="bi bi-eye" aria-hidden="true"></i>
                                </button>
                            </div>
                            <div id="match-feedback" class="smalltext match-feedback" aria-live="polite"></div>
                        </div>

                        <div class="actions mt-3">
                            <button type="submit" class="btn" id="btn-save-password" disabled>
                                <i class="bi bi-shield-check" aria-hidden="true"></i>
                                Update Password
                            </button>
                        </div>
                    </fieldset>
                </form>
            </div>
        </section>
    </div>


    <!-- ============ DESTINATION MODAL ============ -->
    <div class="modal fade" id="destinationModal" tabindex="-1"
         aria-labelledby="destinationModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <form class="modal-content" method="post" action="index.php" enctype="multipart/form-data">
                <?php admin_fields('destination_add', 'destinations'); ?>
                <input type="hidden" name="id" value="">
                <input type="hidden" name="existing_image" id="dest-existing-image" value="">

                <div class="modal-header">
                    <h2 class="modal-title" id="destinationModalTitle">Add destination</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="Close dialog"></button>
                </div>

                <div class="modal-body stack">
                    <p class="smalltext">Fields marked with <span class="req">*</span> are required.</p>

                    <label for="dest-name">
                        <span>Name <span class="req" aria-hidden="true">*</span></span>
                        <input id="dest-name" name="name" required maxlength="120" placeholder="e.g. Coorg, Hampi">
                    </label>

                    <label for="dest-district">
                        <span>District <span class="req" aria-hidden="true">*</span></span>
                        <input id="dest-district" name="district" required maxlength="100" placeholder="e.g. Kodagu">
                    </label>

                    <label for="dest-category">
                        Category
                        <select id="dest-category" name="category_id">
                            <option value="0">No category</option>
                            <?php foreach ($categories as $c): ?>
                                <option value="<?= (int) $c['id'] ?>"><?= e($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label for="dest-cost">
                        Estimated base cost (&#8377;)
                        <input id="dest-cost" type="number" name="estimated_cost"
                               min="0" step="0.01" value="0" inputmode="decimal">
                    </label>

                    <!-- Image Upload from Drive / URL -->
                    <fieldset class="image-upload-fieldset">
                        <legend class="form-legend">Destination Image</legend>

                        <div class="image-upload-card" id="image-upload-card">
                            <!-- Upload button to browse from local drive -->
                            <div class="upload-controls">
                                <label for="dest-image-file" class="btn secondary upload-btn" id="btn-upload-file">
                                    <i class="bi bi-cloud-arrow-up-fill" aria-hidden="true"></i>
                                    <span>Upload from Drive</span>
                                </label>
                                <input type="file" id="dest-image-file" name="image_file"
                                       accept="image/jpeg,image/png,image/webp,image/gif,image/avif"
                                       class="visually-hidden">
                                <span class="smalltext text-muted" id="file-chosen-text">
                                    Max 5MB (JPG, PNG, WEBP, GIF, AVIF)
                                </span>
                            </div>

                            <!-- Live preview box -->
                            <div class="image-preview-wrapper" id="dest-image-preview-wrapper" style="display: none;">
                                <figure class="preview-figure">
                                    <img id="dest-image-preview" src="" alt="Destination preview" class="preview-img">
                                    <figcaption id="dest-image-filename" class="smalltext text-truncate"></figcaption>
                                </figure>
                                <button type="button" class="btn danger small" id="btn-remove-image" title="Remove selected image">
                                    <i class="bi bi-x-circle" aria-hidden="true"></i> Remove
                                </button>
                            </div>

                            <!-- Manual URL option -->
                            <details class="image-url-details">
                                <summary class="smalltext">Or specify an image URL / path manually</summary>
                                <div class="mt-2">
                                    <label for="dest-image">
                                        <span class="smalltext">Image path or URL</span>
                                        <input id="dest-image" name="image_url" maxlength="500"
                                               placeholder="images/Coorg.jpg or https://..."
                                               aria-describedby="dest-image-hint">
                                        <span class="smalltext" id="dest-image-hint">
                                            Example: images/Coorg.jpg or https://example.com/photo.jpg
                                        </span>
                                    </label>
                                </div>
                            </details>
                        </div>
                    </fieldset>

                    <label for="dest-description">
                        Description
                        <textarea id="dest-description" name="description" maxlength="5000" rows="3"></textarea>
                    </label>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn secondary" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="btn">
                        <i class="bi bi-check-lg" aria-hidden="true"></i>
                        <span data-submit-label>Save destination</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ============ CATEGORY MODAL ============ -->
    <div class="modal fade" id="categoryModal" tabindex="-1"
         aria-labelledby="categoryModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content" method="post" action="index.php">
                <?php admin_fields('category_add', 'categories'); ?>
                <input type="hidden" name="id" value="">

                <div class="modal-header">
                    <h2 class="modal-title" id="categoryModalTitle">Add category</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="Close dialog"></button>
                </div>

                <div class="modal-body stack">
                    <label for="cat-name">
                        <span>Category name <span class="req" aria-hidden="true">*</span></span>
                        <input id="cat-name" name="name" required maxlength="80">
                    </label>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn secondary" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="btn">
                        <i class="bi bi-check-lg" aria-hidden="true"></i>
                        <span data-submit-label>Save category</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ============ ACTIVITY MODAL ============ -->
    <div class="modal fade" id="activityModal" tabindex="-1"
         aria-labelledby="activityModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <form class="modal-content" method="post" action="index.php">
                <?php admin_fields('activity_add', 'activities'); ?>
                <input type="hidden" name="id" value="">

                <div class="modal-header">
                    <h2 class="modal-title" id="activityModalTitle">Add activity</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="Close dialog"></button>
                </div>

                <div class="modal-body stack">
                    <p class="smalltext">Fields marked with * are required.</p>

                    <label for="act-destination">
                        <span>Destination <span class="req" aria-hidden="true">*</span></span>
                        <select id="act-destination" name="destination_id" required>
                            <?php foreach ($destinations as $d): ?>
                                <option value="<?= (int) $d['id'] ?>"><?= e($d['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label for="act-name">
                        <span>Activity name <span class="req" aria-hidden="true">*</span></span>
                        <input id="act-name" name="name" required maxlength="120">
                    </label>

                    <label for="act-cost">
                        Estimated cost (&#8377;)
                        <input id="act-cost" type="number" name="estimated_cost"
                               min="0" step="0.01" value="0" inputmode="decimal">
                    </label>

                    <label for="act-description">
                        Description
                        <textarea id="act-description" name="description" maxlength="5000"></textarea>
                    </label>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn secondary" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="btn">
                        <i class="bi bi-check-lg" aria-hidden="true"></i>
                        <span data-submit-label>Save activity</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ============ DELETE CONFIRMATION MODAL ============ -->
    <div class="modal fade" id="deleteModal" tabindex="-1" role="alertdialog"
         aria-labelledby="deleteModalTitle" aria-describedby="deleteModalText"
         aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content" method="post" action="index.php">
                <?php admin_fields('', 'overview'); ?>
                <input type="hidden" name="id" value="">

                <div class="modal-header">
                    <h2 class="modal-title" id="deleteModalTitle">Confirm deletion</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="Close dialog"></button>
                </div>

                <div class="modal-body" id="deleteModalText">
                    <p>
                        Delete <strong data-delete-name></strong>?
                        This cannot be undone.
                    </p>
                    <p class="warning" data-delete-note hidden></p>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn secondary" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="btn danger">
                        <i class="bi bi-trash" aria-hidden="true"></i> Delete
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ============ ENQUIRY REPLY MODAL ============ -->
    <div class="modal fade" id="enquiryReplyModal" tabindex="-1"
         aria-labelledby="enquiryReplyModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <form class="modal-content" method="post" action="index.php">
                <?php admin_fields('enquiry_reply', 'enquiries'); ?>
                <input type="hidden" name="id" id="reply-enquiry-id" value="">

                <div class="modal-header">
                    <h2 class="modal-title" id="enquiryReplyModalTitle">
                        <i class="bi bi-reply-fill text-success" aria-hidden="true"></i> Respond to Traveler Enquiry
                    </h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close dialog"></button>
                </div>

                <div class="modal-body stack" style="gap: 16px;">
                    <div style="background: #f8faf8; border: 1px solid #dce7df; border-radius: 10px; padding: 16px;">
                        <div class="between mb-2">
                            <span class="tag" style="background:#eaf6ef; color:#17683e; font-weight:bold;" id="reply-destination-tag">Destination</span>
                            <span class="smalltext text-muted" id="reply-user-email-text">user@example.com</span>
                        </div>
                        <div style="margin-bottom: 8px;">
                            <span class="smalltext text-muted">From Traveler:</span>
                            <strong id="reply-user-name-text">Traveler Name</strong>
                        </div>
                        <div style="background: #fff; border: 1px solid #e1e8e3; border-radius: 8px; padding: 12px;">
                            <span class="smalltext text-muted" style="display:block; margin-bottom:4px;">Enquiry Message:</span>
                            <p id="reply-message-text" style="margin: 0; font-size: 14px; white-space: pre-wrap; color: #23352b; font-style: italic;"></p>
                        </div>
                    </div>

                    <label for="reply-textarea">
                        <span>Official Admin Response <span class="req" aria-hidden="true">*</span></span>
                        <textarea id="reply-textarea" name="admin_reply" required rows="5"
                                  placeholder="Type your response to the traveler. This will be sent to their email and displayed in their account dashboard..."
                                  style="resize: vertical; padding: 12px; font-size: 14px; border-radius: 8px; border: 1px solid #ccd8ce;"></textarea>
                        <span class="smalltext text-muted" style="margin-top: 4px;">
                            Your reply will be sent via email to the traveler and will appear on their personal dashboard.
                        </span>
                    </label>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn secondary" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="btn">
                        <i class="bi bi-send-fill" aria-hidden="true"></i> Send Response
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<?php
$extraScripts = '
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/admin.js"></script>
';
require_once dirname(__DIR__) . '/includes/footer.php';
