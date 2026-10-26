<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/backend/trips.php';

require_login();

$id = (int) ($_GET['id'] ?? 0);
$userId = (int) $_SESSION['user']['id'];

// Fetch trip details
$trip = get_trip_details($pdo, $id, $userId);
if (!$trip) {
    http_response_code(404);
    exit('Trip not found or access denied.');
}

// Handle Form Actions (Confirm, Delete, Add Expense, Assign Day, Add Custom Activity)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $action = $_POST['action'] ?? '';

    if ($action === 'confirm') {
        confirm_trip($pdo, $id, $userId);
        redirect('itinerary.php?id=' . $id);
    }

    if ($action === 'delete') {
        delete_trip($pdo, $id, $userId);
        redirect('my_trips.php');
    }

    if ($action === 'update_activity_day') {
        $tripActivityId = (int) ($_POST['trip_activity_id'] ?? 0);
        $dayNumber = (int) ($_POST['day_number'] ?? 1);
        update_trip_activity_day($pdo, $id, $tripActivityId, $dayNumber);
        redirect('itinerary.php?id=' . $id);
    }

    if ($action === 'add_custom_activity') {
        $customName = trim($_POST['custom_name'] ?? '');
        $customCost = max(0, (float) ($_POST['custom_cost'] ?? 0));
        $targetDay = (int) ($_POST['target_day'] ?? 1);
        if ($customName !== '') {
            add_custom_activity_to_trip($pdo, $id, (int) $trip['destination_id'], $customName, $customCost, $targetDay);
        }
        redirect('itinerary.php?id=' . $id);
    }

    if ($action === 'expense') {
        $title = trim($_POST['expense_title'] ?? '');
        $category = trim($_POST['expense_category'] ?? 'Other');
        $est = max(0, (float) ($_POST['estimated_amount'] ?? 0));
        $actual = max(0, (float) ($_POST['actual_amount'] ?? 0));
        if ($title !== '') {
            add_trip_expense($pdo, $id, $title, $category, $est, $actual);
        }
        redirect('itinerary.php?id=' . $id);
    }
}

// Fetch activities mapped to this trip
$items = get_trip_activities($pdo, $id);
$expenses = get_trip_expenses($pdo, $id);

// Calculations
$activityTotal = array_sum(array_map(fn($x) => (float) $x['estimated_cost'], $items));
$expenseEstimate = array_sum(array_map(fn($x) => (float) $x['estimated_amount'], $expenses));
$baseCostPerPerson = (float) $trip['destination_base_cost'];
$groupSize = max(1, (int) $trip['group_size']);
$destinationEstimate = $baseCostPerPerson * $groupSize;

$estimated = $destinationEstimate + $activityTotal + $expenseEstimate;
$budget = (float) $trip['budget'];
$budgetPercentage = $budget > 0 ? min(100, ($estimated / $budget) * 100) : 0;

$pageTitle = 'Itinerary';
require_once __DIR__ . '/includes/header.php';
?>

<div class="between" style="margin-bottom: 24px;">
    <div>
        <span class="tag" style="background: #eaf6ef; color: #17683e; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: bold;">
            <?= e(ucfirst($trip['status'])) ?> plan
        </span>
        <h1 style="margin: 8px 0 4px; font-size: 28px;">Your <?= e($trip['destination_name']) ?> Itinerary</h1>
        <p class="muted" style="margin: 0; font-size: 14px;">
            <?= (int) $trip['duration_days'] ?> days &bull; <?= (int) $trip['group_size'] ?> people &bull; Personalized trip plan
        </p>
    </div>
    <div class="actions">
        <a class="btn secondary" href="my_trips.php" style="padding: 8px 14px; font-size: 13px;">My Trips</a>
        <a class="btn secondary" href="edit_trip.php?id=<?= $id ?>" style="padding: 8px 14px; font-size: 13px;">Edit trip</a>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 24px; align-items: start;">

    <!-- Left Column: Day-wise Itinerary & Custom Activity Form -->
    <div class="card" style="background: #fff; border: 1px solid #dce7df; border-radius: 14px; padding: 22px;">
        
        <!-- Day Tabs Header -->
        <div style="display: flex; gap: 8px; margin-bottom: 20px; border-bottom: 1px solid #e2e8e3; padding-bottom: 15px; overflow-x: auto;">
            <?php for ($day = 1; $day <= (int) $trip['duration_days']; $day++): ?>
                <button type="button" class="day-tab-btn <?= $day === 1 ? 'active-tab' : '' ?>" onclick="switchDay(<?= $day ?>)" id="tab-btn-<?= $day ?>" style="padding: 6px 14px; border-radius: 8px; border: 1px solid #dce7df; background: <?= $day === 1 ? '#171b19' : '#f5f8f5' ?>; color: <?= $day === 1 ? '#fff' : '#23352b' ?>; font-weight: bold; font-size: 13px; cursor: pointer; white-space: nowrap;">
                    Day <?= $day ?>
                </button>
            <?php endfor; ?>
        </div>

        <!-- Day Panels with Day Reassignment Option -->
        <?php for ($day = 1; $day <= (int) $trip['duration_days']; $day++): ?>
            <div class="day-panel" id="day-panel-<?= $day ?>" style="display: <?= $day === 1 ? 'block' : 'none' ?>;">
                
                <?php
                $found = false;
                foreach ($items as $it):
                    if ((int) $it['day_number'] === $day):
                        $found = true;
                ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 14px; border: 1px solid #e2e8e3; border-radius: 8px; background: #fafcfb; margin-bottom: 10px;">
                        <div>
                            <span style="font-weight: 600; font-size: 14px; display: block;"><?= e($it['name']) ?></span>
                            <span class="muted" style="font-size: 12px;">₹<?= number_format((float) $it['estimated_cost']) ?></span>
                        </div>

                        <!-- Dropdown to move activity to another day -->
                        <form method="POST" style="margin: 0; display: flex; align-items: center; gap: 5px;">
                            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                            <input type="hidden" name="action" value="update_activity_day">
                            <input type="hidden" name="trip_activity_id" value="<?= $it['id'] ?>">
                            <select name="day_number" onchange="this.form.submit()" style="padding: 4px 8px; font-size: 12px; border-radius: 6px; border: 1px solid #ccd8ce;">
                                <?php for ($d = 1; $d <= (int) $trip['duration_days']; $d++): ?>
                                    <option value="<?= $d ?>" <?= $d === $day ? 'selected' : '' ?>>Day <?= $d ?></option>
                                <?php endfor; ?>
                            </select>
                        </form>
                    </div>
                <?php
                    endif;
                endforeach;
                ?>

                <?php if (!$found): ?>
                    <p class="muted" style="text-align: center; padding: 20px 0; font-size: 13px;">
                        No activities added for this day.
                    </p>
                <?php endif; ?>

            </div>
        <?php endfor; ?>

        <!-- Add Custom User Activity Form -->
        <div style="margin-top: 25px; padding-top: 15px; border-top: 1px dashed #dce7df;">
            <h4 style="margin: 0 0 10px; font-size: 14px;">Add Your Own Custom Activity</h4>
            <form method="POST" style="display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 8px; align-items: center;">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="add_custom_activity">

                <input type="text" name="custom_name" placeholder="Activity name..." required style="padding: 8px; font-size: 13px; border: 1px solid #ccd8ce; border-radius: 6px;">
                
                <input type="number" name="custom_cost" placeholder="Cost (Rs.)" min="0" value="0" style="padding: 8px; font-size: 13px; border: 1px solid #ccd8ce; border-radius: 6px;">

                <select name="target_day" style="padding: 8px; font-size: 13px; border: 1px solid #ccd8ce; border-radius: 6px;">
                    <?php for ($d = 1; $d <= (int) $trip['duration_days']; $d++): ?>
                        <option value="<?= $d ?>">Day <?= $d ?></option>
                    <?php endfor; ?>
                </select>

                <button type="submit" class="btn" style="padding: 8px 12px; font-size: 13px;">Add</button>
            </form>
        </div>

        <!-- Action Buttons -->
        <div style="display: flex; gap: 10px; margin-top: 25px; padding-top: 15px; border-top: 1px solid #e2e8e3;">
            <form method="post" style="margin: 0;">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="confirm">
                <button type="button" class="btn" onclick="openPaymentModal()" style="padding: 10px 16px; font-size: 14px;">Confirm & Pay</button>
            </form>

            <form method="post" data-confirm="Delete this trip permanently?" style="margin: 0;">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="delete">
                <button class="btn danger" style="padding: 10px 16px; font-size: 14px;">Delete Trip</button>
            </form>
        </div>

    </div>

    <!-- Right Column: Live Budget Tracker Card -->
    <div class="card" style="background: #fff; border: 1px solid #dce7df; border-radius: 14px; padding: 22px;">
        <h3 style="margin-top: 0; font-size: 18px; margin-bottom: 15px;">Live Budget Tracker</h3>

        <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 14px;">
            <span class="muted">Estimated Expenses</span>
            <strong>₹<?= number_format($estimated, 2) ?></strong>
        </div>

        <div style="display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 14px;">
            <span class="muted">Total Budget</span>
            <strong>₹<?= number_format($budget, 2) ?></strong>
        </div>

        <!-- Progress Bar -->
        <div style="height: 8px; background: #e5eae6; border-radius: 4px; overflow: hidden; margin-bottom: 18px;">
            <div style="height: 100%; width: <?= $budgetPercentage ?>%; background: <?= $estimated > $budget ? '#a62f2f' : 'linear-gradient(90deg, #0ca678, #36d9a4)' ?>; border-radius: 4px;"></div>
        </div>

        <!-- Budget Alert Box -->
        <div class="<?= $estimated > $budget ? 'warning' : 'good' ?>" style="padding: 12px; border-radius: 8px; font-size: 13px; margin-bottom: 20px;">
            <?php if ($estimated > $budget): ?>
                <strong>Budget Alert:</strong> Estimated cost exceeds your target by ₹<?= number_format($estimated - $budget, 2) ?>.
            <?php else: ?>
                <strong>Budget Status:</strong> Cost is within your planned budget.
            <?php endif; ?>
        </div>

        <div style="border: 1px dashed #ccd8ce; border-radius: 10px; padding: 20px; text-align: center; color: #68776e; font-size: 12px; font-weight: bold; letter-spacing: 1px; background: #fafcfb;">
            DESTINATION BASE: ₹<?= number_format($destinationEstimate, 2) ?>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/dummy_payment.php'; ?>

<script>
function switchDay(dayNum) {
    document.querySelectorAll('.day-panel').forEach(panel => {
        panel.style.display = 'none';
    });
    document.querySelectorAll('.day-tab-btn').forEach(btn => {
        btn.style.background = '#f5f8f5';
        btn.style.color = '#23352b';
    });

    document.getElementById('day-panel-' + dayNum).style.display = 'block';
    const activeBtn = document.getElementById('tab-btn-' + dayNum);
    activeBtn.style.background = '#171b19';
    activeBtn.style.color = '#fff';
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
