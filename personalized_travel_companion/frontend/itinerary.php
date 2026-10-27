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
        $updatedTrip = get_trip_details($pdo, $id, $userId);
        $status = strtolower($updatedTrip['status'] ?? 'active');
        $targetFilter = ($status === 'active') ? 'active' : 'upcoming';

        if (!empty($_POST['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'status' => $status,
                'redirect' => 'my_trips.php?filter=' . $targetFilter . '&confirmed=1'
            ]);
            exit;
        }

        $_SESSION['trip_confirmed_dialog'] = [
            'id' => $id,
            'title' => $trip['title'],
            'destination' => $trip['destination_name'],
            'status' => $status
        ];
        redirect('my_trips.php?filter=' . $targetFilter . '&confirmed=1');
    }

    if ($action === 'delete') {
        delete_trip($pdo, $id, $userId);
        redirect('my_trips.php');
    }

    if ($action === 'update_activity_day') {
        $tripActivityId = (int) ($_POST['trip_activity_id'] ?? 0);
        $dayNumber = (int) ($_POST['day_number'] ?? 1);
        update_trip_activity_day($pdo, $id, $tripActivityId, $dayNumber);
        redirect('itinerary.php?id=' . $id . '&day=' . $dayNumber);
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

// Calculations - Day-dependent expense
$duration = max(1, (int) ($trip['duration_days'] ?? 1));
$activeDay = (int) ($_GET['day'] ?? 1);
if ($activeDay < 1 || $activeDay > $duration) {
    $activeDay = 1;
}
$groupSize = max(1, (int) ($trip['group_size'] ?? 1));
$baseCostPerPerson = (float) $trip['destination_base_cost'];

// Destination base cost increases proportionally with duration in days
$destinationEstimate = $baseCostPerPerson * $groupSize * $duration;

$activityTotal = array_sum(array_map(fn($x) => (float) $x['estimated_cost'], $items));
$expenseEstimate = array_sum(array_map(fn($x) => (float) $x['estimated_amount'], $expenses));

$estimated = $destinationEstimate + $activityTotal + $expenseEstimate;
$perDayExpense = $estimated / $duration;

$budget = (float) $trip['budget'];
$perDayBudget = $budget / $duration;
$budgetPercentage = $budget > 0 ? min(100, ($estimated / $budget) * 100) : 0;

$pageTitle = 'Itinerary';
require_once __DIR__ . '/includes/header.php';
?>

<div class="between" style="margin-bottom: 24px;">
    <div>
        <?php
            $tripStatus = strtolower($trip['status'] ?? 'draft');
            $statusLabel = ucfirst($tripStatus);
            $badgeBg = '#eaf6ef';
            $badgeColor = '#17683e';

            if ($tripStatus === 'active') {
                $badgeBg = '#d1fae5';
                $badgeColor = '#065f46';
            } elseif ($tripStatus === 'upcoming') {
                $badgeBg = '#e0f2fe';
                $badgeColor = '#0369a1';
            } elseif ($tripStatus === 'past') {
                $badgeBg = '#f1f5f9';
                $badgeColor = '#475569';
            }
        ?>
        <span class="tag status-<?= e($tripStatus) ?>" style="background: <?= $badgeBg ?>; color: <?= $badgeColor ?>; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; display: inline-flex; align-items: center; gap: 6px;">
            <?php if ($tripStatus === 'active'): ?>
                <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #059669;"></span>
            <?php endif; ?>
            <?= e($statusLabel) ?> plan
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

<div class="itinerary-main-grid">

    <!-- Left Column: Day-wise Itinerary -->
    <div class="card" style="background: #fff; border: 1px solid #dce7df; border-radius: 14px; padding: 22px;">
        
        <!-- Day Selector Dropdown (Replaces horizontal tab buttons to prevent mobile UI overflow) -->
        <div style="margin-bottom: 20px; border-bottom: 1px solid #e2e8e3; padding-bottom: 16px;">
            <label for="daySelectDropdown" style="display: flex; justify-content: space-between; align-items: center; font-weight: 700; font-size: 13px; color: #23352b; margin-bottom: 8px;">
                <span>📅 Select Itinerary Day:</span>
                <span class="muted" style="font-weight: 500; font-size: 12px;"><?= $duration ?> <?= $duration === 1 ? 'Day Total' : 'Days Total' ?></span>
            </label>
            <div style="position: relative; width: 100%;">
                <select
                    id="daySelectDropdown"
                    onchange="switchDay(this.value)"
                    aria-label="Select itinerary day"
                    style="width: 100%; box-sizing: border-box; padding: 11px 14px; font-size: 14.5px; font-weight: 600; color: #173b2a; background: #f4f8f5; border: 1.5px solid #bcd4c4; border-radius: 9px; cursor: pointer; outline: none; transition: border-color 0.2s;"
                >
                    <?php for ($day = 1; $day <= $duration; $day++): 
                        $dayActivityCount = count(array_filter($items, fn($it) => (int)$it['day_number'] === $day));
                    ?>
                        <option value="<?= $day ?>" <?= $day === $activeDay ? 'selected' : '' ?>>
                            Day <?= $day ?> (<?= $dayActivityCount ?> <?= $dayActivityCount === 1 ? 'activity' : 'activities' ?>)
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
        </div>

        <!-- Day Panels with Day Reassignment Option -->
        <?php for ($day = 1; $day <= $duration; $day++): 
            $dayActivityCount = count(array_filter($items, fn($it) => (int)$it['day_number'] === $day));
        ?>
            <div class="day-panel" id="day-panel-<?= $day ?>" style="display: <?= $day === $activeDay ? 'block' : 'none' ?>;">
                
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; padding-bottom: 8px; border-bottom: 1px dashed #e2e8e3;">
                    <h3 style="margin: 0; font-size: 16px; color: #183c2b; font-weight: 700;">Day <?= $day ?> Schedule</h3>
                    <span class="tag" style="background: #eaf6ef; color: #17683e; font-size: 12px; font-weight: 600; padding: 3px 9px; border-radius: 12px;">
                        <?= $dayActivityCount ?> <?= $dayActivityCount === 1 ? 'activity' : 'activities' ?>
                    </span>
                </div>
                
                <?php
                $found = false;
                foreach ($items as $it):
                    if ((int) $it['day_number'] === $day):
                        $found = true;
                ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 14px; border: 1px solid #e2e8e3; border-radius: 8px; background: #fafcfb; margin-bottom: 10px; flex-wrap: wrap; gap: 10px;">
                        <div>
                            <span style="font-weight: 600; font-size: 14px; display: block;"><?= e($it['name']) ?></span>
                            <span class="muted" style="font-size: 12px;">₹<?= number_format((float) $it['estimated_cost']) ?></span>
                        </div>

                        <!-- Dropdown to move activity to another day -->
                        <form method="POST" style="margin: 0; display: flex; align-items: center; gap: 5px;">
                            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                            <input type="hidden" name="action" value="update_activity_day">
                            <input type="hidden" name="trip_activity_id" value="<?= $it['id'] ?>">
                            <select name="day_number" onchange="this.form.submit()" aria-label="Move <?= e($it['name']) ?> to another day" style="padding: 4px 8px; font-size: 12px; border-radius: 6px; border: 1px solid #ccd8ce;">
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
                        No activities scheduled for this day.
                    </p>
                <?php endif; ?>

            </div>
        <?php endfor; ?>

        <!-- Action Buttons -->
        <div style="display: flex; gap: 10px; margin-top: 25px; padding-top: 15px; border-top: 1px solid #e2e8e3; align-items: center; justify-content: space-between; flex-wrap: wrap;">
            <?php if ($trip['status'] === 'draft'): ?>
                <form method="post" style="margin: 0;">
                    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                    <input type="hidden" name="action" value="confirm">
                    <button type="button" class="btn" onclick="openPaymentModal()" style="padding: 10px 18px; font-size: 14px;">Confirm &amp; Pay</button>
                </form>
            <?php else: ?>
                <div style="display: inline-flex; align-items: center; gap: 8px; background: #e8f7ee; color: #166534; padding: 9px 15px; border-radius: 8px; font-weight: 700; font-size: 13.5px; border: 1px solid #bbf7d0;">
                    <span style="font-size: 16px;">✓</span> Booking Confirmed &amp; Paid
                </div>
            <?php endif; ?>

            <form method="post" data-confirm="Are you sure you want to delete this trip permanently?" style="margin: 0;">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="delete">
                <button class="btn danger" style="padding: 9px 14px; font-size: 13px;">Delete Trip</button>
            </form>
        </div>

    </div>

    <!-- Right Column: Live Budget Tracker Card -->
    <div class="card" style="background: #fff; border: 1px solid #dce7df; border-radius: 14px; padding: 22px;">
        <h3 style="margin-top: 0; font-size: 18px; margin-bottom: 14px;">Live Budget Tracker</h3>

        <!-- Prominent Per Day Expense Box -->
        <div style="background: #f4f8f5; border: 1px solid #d4eadc; border-radius: 12px; padding: 16px; margin-bottom: 18px; text-align: center;">
            <span class="muted" style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.6px; font-weight: 700; color: #406852;">Estimated Per Day Expense</span>
            <div style="font-size: 26px; font-weight: 800; color: var(--deep); margin: 6px 0 2px;">
                ₹<?= number_format($perDayExpense, 2) ?> <span style="font-size: 13px; font-weight: 600; color: #526359;">/ day</span>
            </div>
            <span style="font-size: 12.5px; color: #526359;">
                Planned target: <strong>₹<?= number_format($perDayBudget, 2) ?></strong> / day
            </span>
        </div>

        <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 14px;">
            <span class="muted">Total Trip Estimate (<?= $duration ?> <?= $duration === 1 ? 'day' : 'days' ?>)</span>
            <strong>₹<?= number_format($estimated, 2) ?></strong>
        </div>

        <div style="display: flex; justify-content: space-between; margin-bottom: 14px; font-size: 14px;">
            <span class="muted">Total Trip Budget</span>
            <strong>₹<?= number_format($budget, 2) ?></strong>
        </div>

        <!-- Progress Bar -->
        <div style="height: 8px; background: #e5eae6; border-radius: 4px; overflow: hidden; margin-bottom: 16px;">
            <div style="height: 100%; width: <?= $budgetPercentage ?>%; background: <?= $estimated > $budget ? '#a62f2f' : 'linear-gradient(90deg, #0ca678, #36d9a4)' ?>; border-radius: 4px;"></div>
        </div>

        <!-- Budget Status Alert Box -->
        <div class="<?= $estimated > $budget ? 'warning' : 'good' ?>" style="padding: 12px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 18px; line-height: 1.45;">
            <?php if ($estimated > $budget): ?>
                <strong>Budget Alert:</strong> Total estimated cost exceeds your planned budget by ₹<?= number_format($estimated - $budget, 2) ?> (≈ ₹<?= number_format(($estimated - $budget) / $duration, 2) ?> / day).
            <?php else: ?>
                <strong>Budget On Track:</strong> Your estimated expense is within budget with ₹<?= number_format($budget - $estimated, 2) ?> remaining buffer.
            <?php endif; ?>
        </div>

        <!-- Cost Breakdown List -->
        <div style="background: #fafcfb; border: 1px solid #e2ebe4; border-radius: 10px; padding: 14px; font-size: 13px; display: flex; flex-direction: column; gap: 8px;">
            <span style="font-weight: 700; color: var(--ink); font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Expense Breakdown</span>
            <div style="display: flex; justify-content: space-between;">
                <span class="muted">Base stay &amp; living (₹<?= number_format($baseCostPerPerson) ?> &times; <?= $duration ?>d &times; <?= $groupSize ?>p)</span>
                <strong>₹<?= number_format($destinationEstimate, 2) ?></strong>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span class="muted">Activities total (<?= count($items) ?> items)</span>
                <strong>₹<?= number_format($activityTotal, 2) ?></strong>
            </div>
            <?php if ($expenseEstimate > 0): ?>
                <div style="display: flex; justify-content: space-between;">
                    <span class="muted">Additional logged expenses</span>
                    <strong>₹<?= number_format($expenseEstimate, 2) ?></strong>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/dummy_payment.php'; ?>

<script>
function switchDay(dayNum) {
    dayNum = parseInt(dayNum, 10);
    document.querySelectorAll('.day-panel').forEach(panel => {
        panel.style.display = 'none';
    });

    const targetPanel = document.getElementById('day-panel-' + dayNum);
    if (targetPanel) {
        targetPanel.style.display = 'block';
    }

    const dropdown = document.getElementById('daySelectDropdown');
    if (dropdown && parseInt(dropdown.value, 10) !== dayNum) {
        dropdown.value = dayNum;
    }

    if (window.history && window.history.replaceState) {
        const url = new URL(window.location);
        url.searchParams.set('day', dayNum);
        window.history.replaceState({}, '', url);
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
