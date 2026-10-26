<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/backend/destinations.php';
require_once dirname(__DIR__) . '/backend/enquiries.php';

$id = (int) ($_GET['id'] ?? 0);

$destination = get_destination_by_id($pdo, $id);

if (!$destination) {
    http_response_code(404);
    exit('Destination not found.');
}

$activities = get_destination_activities($pdo, $id);

$pageTitle = $destination['name'];
$enquiryError = '';

// Handle Enquiry Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'submit_enquiry') {
    verify_csrf();

    $userId = logged_in() ? (int) $_SESSION['user']['id'] : null;
    $userName = trim($_POST['user_name'] ?? (logged_in() ? $_SESSION['user']['full_name'] : ''));
    $userEmail = trim($_POST['user_email'] ?? (logged_in() ? $_SESSION['user']['email'] : ''));
    $message = trim($_POST['message'] ?? '');

    $result = submit_enquiry($pdo, $userId, $id, $userName, $userEmail, $message);
    if ($result['success']) {
        $_SESSION['flash'] = "Your enquiry for {$destination['name']} was sent successfully! Our specialist will reply to your email and dashboard.";
        redirect("destination.php?id={$id}");
    } else {
        $enquiryError = $result['error'];
    }
}

$extraHead = '
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
';

require_once __DIR__ . '/includes/header.php';
?>

<?php if ($enquiryError): ?>
    <div class="flash error" style="margin-bottom: 20px;">
        <?= e($enquiryError) ?>
    </div>
<?php endif; ?>

<div class="card destination-detail">
    <div class="two-col">

        <div>
            <?php if (!empty($destination['image_url'])): ?>
                <img
                    class="destination-detail-image"
                    src="<?= e(asset_url($destination['image_url'])) ?>"
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
                ₹<?= number_format((float) $destination['estimated_cost']) ?>

                <span class="smalltext">
                    estimated base cost
                </span>
            </p>

            <div style="display: flex; gap: 12px; margin-top: 15px; flex-wrap: wrap; align-items: center;">
                <a
                    class="btn"
                    href="plan_trip.php?destination_id=<?= (int) $destination['id'] ?>"
                >
                    Plan this trip
                </a>

                <button
                    type="button"
                    class="btn secondary"
                    onclick="openEnquiryModal()"
                    style="display: inline-flex; align-items: center; gap: 6px; padding: 10px 18px;"
                >
                    <i class="bi bi-chat-dots-fill" style="color: #247653;"></i>
                    Enquire
                </button>
            </div>
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
                ₹<?= number_format((float) $activity['estimated_cost']) ?>
            </strong>

        </article>
    <?php endforeach; ?>
</div>

<?php if (!$activities): ?>
    <div class="card empty">
        Activities will be added by the administrator.
    </div>
<?php endif; ?>

<!-- ================= DESTINATION ENQUIRY MODAL ================= -->
<div id="enquiryModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1050; justify-content:center; align-items:center; padding: 15px;">
    <div style="background:#fff; padding:28px; border-radius:14px; width:100%; max-width:480px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); max-height: 90vh; overflow-y: auto;">
        
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
            <div>
                <h3 style="margin:0; font-size:20px; color:#171b19;">Enquire about <?= e($destination['name']) ?></h3>
                <p class="muted" style="margin:4px 0 0; font-size:13px;">Our travel guides will respond to your questions via email & dashboard.</p>
            </div>
            <button type="button" onclick="closeEnquiryModal()" style="background:none; border:none; font-size:22px; cursor:pointer; color:#68776e; line-height: 1;">&times;</button>
        </div>

        <form method="POST" action="destination.php?id=<?= $id ?>" class="stack" style="gap: 14px;">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="action" value="submit_enquiry">

            <label style="font-size: 13px; font-weight: bold;">
                Your Name <span style="color:#d32f2f;">*</span>
                <input
                    type="text"
                    name="user_name"
                    required
                    style="width:100%; padding:9px 12px; margin-top:4px; border:1px solid #ccd8ce; border-radius:8px;"
                    value="<?= e($_SESSION['user']['full_name'] ?? '') ?>"
                    placeholder="Enter your name"
                >
            </label>

            <label style="font-size: 13px; font-weight: bold;">
                Your Email Address <span style="color:#d32f2f;">*</span>
                <input
                    type="email"
                    name="user_email"
                    required
                    style="width:100%; padding:9px 12px; margin-top:4px; border:1px solid #ccd8ce; border-radius:8px;"
                    value="<?= e($_SESSION['user']['email'] ?? '') ?>"
                    placeholder="name@example.com"
                >
            </label>

            <label style="font-size: 13px; font-weight: bold;">
                Enquiry Message <span style="color:#d32f2f;">*</span>
                <textarea
                    name="message"
                    required
                    rows="4"
                    style="width:100%; padding:9px 12px; margin-top:4px; border:1px solid #ccd8ce; border-radius:8px; font-family: inherit; resize: vertical;"
                    placeholder="Ask about sightseeing, best timings, budget tips, local guides, or custom itinerary requests..."
                ></textarea>
            </label>

            <div style="display:flex; gap:10px; margin-top:8px;">
                <button
                    type="submit"
                    class="btn"
                    style="flex:1; padding:12px; background:#247653; color:#fff; border:none; border-radius:8px; font-weight:bold; cursor:pointer;"
                >
                    <i class="bi bi-send-fill" style="margin-right: 4px;"></i> Send Enquiry
                </button>
                <button
                    type="button"
                    onclick="closeEnquiryModal()"
                    class="btn secondary"
                    style="padding:12px 18px; border-radius:8px; font-weight:bold;"
                >
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openEnquiryModal() {
    const modal = document.getElementById('enquiryModal');
    if (modal) {
        modal.style.display = 'flex';
        modal.querySelector('textarea, input')?.focus();
    }
}
function closeEnquiryModal() {
    const modal = document.getElementById('enquiryModal');
    if (modal) modal.style.display = 'none';
}
window.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeEnquiryModal();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
