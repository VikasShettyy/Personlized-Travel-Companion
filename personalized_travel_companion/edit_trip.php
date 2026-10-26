<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/backend/trips.php';

require_login();

$id = (int) ($_GET['id'] ?? 0);
$userId = (int) $_SESSION['user']['id'];

// Fetch the trip to ensure it belongs to the logged-in user
$trip = get_trip_details($pdo, $id, $userId);
if (!$trip) {
    redirect('my_trips.php');
}

$error = '';

// Handle Form Submission for Updating
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $title = trim($_POST['title'] ?? '');
    $startDate = $_POST['start_date'] ?? '';
    $endDate = $_POST['end_date'] ?? '';
    $groupSize = (int) ($_POST['group_size'] ?? 1);
    $budget = (float) ($_POST['budget'] ?? 0);

    if ($startDate === '' || $endDate === '') {
        $error = 'Please select both start and end dates.';
    } elseif ($endDate < $startDate) {
        $error = 'End date cannot be before start date.';
    } elseif ($title === '') {
        $error = 'Please enter a trip title.';
    } else {
        try {
            update_trip($pdo, $id, $userId, [
                'title'      => $title,
                'start_date' => $startDate,
                'end_date'   => $endDate,
                'group_size' => $groupSize,
                'budget'     => $budget,
            ]);

            redirect('itinerary.php?id=' . $id);
        } catch (Throwable $e) {
            $error = 'Could not update trip. Please try again.';
        }
    }
}

$pageTitle = 'Edit Trip';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="max-width: 800px; margin: 30px auto; background: #fff; padding: 30px; border-radius: 12px; border: 1px solid #dce7df;">
    <h1>Edit Trip</h1>
    <p class="muted">Modify your travel details for <?= htmlspecialchars($trip['destination_name']) ?>.</p>

    <?php if ($error): ?>
        <div class="flash error" style="background: #ffebeb; color: #a83232; padding: 12px; border-radius: 8px; margin-bottom: 20px;">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">

        <div style="display: flex; flex-direction: column; gap: 15px;">
            <label style="font-weight: bold; font-size: 14px;">
                Trip Title
                <input type="text" name="title" value="<?= htmlspecialchars($_POST['title'] ?? $trip['title']) ?>" required style="width: 100%; padding: 10px; border: 1px solid #ccd8ce; border-radius: 8px; margin-top: 5px;">
            </label>

            <label style="font-weight: bold; font-size: 14px;">
                Total Budget (₹)
                <input type="number" name="budget" min="0" step="0.01" value="<?= htmlspecialchars($_POST['budget'] ?? $trip['budget']) ?>" required style="width: 100%; padding: 10px; border: 1px solid #ccd8ce; border-radius: 8px; margin-top: 5px;">
            </label>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <label style="font-weight: bold; font-size: 14px;">
                    Start Date
                    <input type="date" id="start_date" name="start_date" value="<?= htmlspecialchars($_POST['start_date'] ?? $trip['start_date']) ?>" required style="width: 100%; padding: 10px; border: 1px solid #ccd8ce; border-radius: 8px; margin-top: 5px;">
                </label>

                <label style="font-weight: bold; font-size: 14px;">
                    End Date
                    <input type="date" id="end_date" name="end_date" value="<?= htmlspecialchars($_POST['end_date'] ?? $trip['end_date']) ?>" required style="width: 100%; padding: 10px; border: 1px solid #ccd8ce; border-radius: 8px; margin-top: 5px;">
                </label>
            </div>

            <label style="font-weight: bold; font-size: 14px;">
                Group Size
                <input type="number" name="group_size" min="1" max="100" value="<?= htmlspecialchars($_POST['group_size'] ?? $trip['group_size']) ?>" required style="width: 100%; padding: 10px; border: 1px solid #ccd8ce; border-radius: 8px; margin-top: 5px;">
            </label>
        </div>

        <div style="margin-top: 25px; display: flex; gap: 10px;">
            <button type="submit" class="btn" style="background: #247653; color: white; padding: 12px 20px; border: none; border-radius: 8px; font-weight: bold; cursor: pointer;">
                Save Changes
            </button>
            <a href="itinerary.php?id=<?= $id ?>" class="btn secondary" style="padding: 12px 20px; background: #e9efeb; color: #23352b; border-radius: 8px; text-decoration: none; font-weight: bold;">
                Cancel
            </a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
