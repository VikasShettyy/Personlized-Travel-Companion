<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/backend/destinations.php';
require_once dirname(__DIR__) . '/backend/trips.php';

require_login();

// 1. Get destination ID
$destId = (int) ($_GET['destination_id'] ?? $_POST['destination_id'] ?? 0);
if ($destId <= 0) {
    redirect('index.php');
}

// 2. Fetch destination details
$destination = get_destination_by_id($pdo, $destId);
if (!$destination) {
    redirect('index.php');
}

// 3. Fetch available activities
$activities = get_destination_activities($pdo, $destId);

$error = '';
$days = 0;

// 4. Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $title = trim($_POST['title'] ?? '');
    $startDate = $_POST['start_date'] ?? '';
    $endDate = $_POST['end_date'] ?? '';
    $groupSize = (int) ($_POST['group_size'] ?? 1);
    $budget = (float) ($_POST['budget'] ?? 0);
    $selectedActivities = $_POST['activities'] ?? [];

    $secDiff = strtotime($endDate) - strtotime($startDate);
    $days = max(1, floor($secDiff / (60 * 60 * 24)) + 1);

    $today = date('Y-m-d');
    if ($startDate === '' || $endDate === '') {
        $error = 'Please select both start and end dates.';
    } elseif ($startDate < $today) {
        $error = 'Start date cannot be in the past (yesterday or earlier).';
    } elseif ($endDate < $today) {
        $error = 'End date cannot be in the past (yesterday or earlier).';
    } elseif ($endDate < $startDate) {
        $error = 'End date cannot be before start date.';
    } elseif ($days > 10) {
        $error = 'Trip duration cannot exceed 10 days.';
    } elseif ($title === '') {
        $error = 'Please enter a trip title.';
    } else {
        try {
            $tripId = create_trip($pdo, (int) $_SESSION['user']['id'], [
                'destination_id' => $destId,
                'title'          => $title,
                'start_date'     => $startDate,
                'end_date'       => $endDate,
                'group_size'     => $groupSize,
                'budget'         => $budget,
                'activities'     => $selectedActivities,
            ]);

            redirect('itinerary.php?id=' . $tripId);
        } catch (Throwable $e) {
            $error = 'Could not save trip. Please try again.';
        }
    }
}

$pageTitle = 'Plan Your Trip';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <h1>Plan Your Trip</h1>
    <p class="muted">Build your personalized <?= htmlspecialchars($destination['name']) ?> adventure.</p>

    <div class="destination-banner" style="background: #eaf6ef; padding: 15px; border-radius: 10px; margin-bottom: 20px;">
        <span class="muted" style="font-size: 13px;">Selected destination</span>
        <strong style="display: block; font-size: 18px;"><?= htmlspecialchars($destination['name']) ?></strong>
    </div>

    <?php if ($error): ?>
        <div class="flash error" style="background: #ffebeb; color: #a83232; padding: 12px; border-radius: 8px; margin-bottom: 20px;"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" id="tripForm">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <input type="hidden" name="destination_id" value="<?= $destId ?>">

        <div class="card" style="background: #fff; border: 1px solid #dce7df; border-radius: 12px; padding: 20px; margin-bottom: 20px;">
            <h2>Trip Details</h2>

            <?php $todayDate = date('Y-m-d'); ?>
            <div class="plan-form-grid">
                <div style="display: flex; flex-direction: column; gap: 5px;">
                    <label for="trip_title" style="font-weight: bold; font-size: 14px;">
                        Trip Title <span style="color: #c92a2a;">*</span>
                    </label>
                    <input type="text" id="trip_title" name="title" value="<?= htmlspecialchars($_POST['title'] ?? $destination['name'] . ' Trip') ?>" required style="padding: 10px; border: 1px solid #ccd8ce; border-radius: 8px;">
                </div>

                <div style="display: flex; flex-direction: column; gap: 5px;">
                    <label for="trip_budget" style="font-weight: bold; font-size: 14px;">
                        Total Budget (₹) <span style="color: #c92a2a;">*</span>
                    </label>
                    <input type="number" id="trip_budget" name="budget" min="0" step="0.01" value="<?= htmlspecialchars($_POST['budget'] ?? '10000') ?>" required style="padding: 10px; border: 1px solid #ccd8ce; border-radius: 8px;">
                </div>

                <div style="display: flex; flex-direction: column; gap: 5px;">
                    <label for="start_date" style="font-weight: bold; font-size: 14px;">
                        Start Date <span style="color: #c92a2a;">*</span>
                    </label>
                    <input type="date" id="start_date" name="start_date" min="<?= $todayDate ?>" value="<?= htmlspecialchars($_POST['start_date'] ?? $todayDate) ?>" required style="padding: 10px; border: 1px solid #ccd8ce; border-radius: 8px;">
                </div>

                <div style="display: flex; flex-direction: column; gap: 5px;">
                    <label for="end_date" style="font-weight: bold; font-size: 14px;">
                        End Date <span style="color: #c92a2a;">*</span> <span style="font-size: 12px; font-weight: normal; color: #526359;">(Max 10 days)</span>
                    </label>
                    <input type="date" id="end_date" name="end_date" min="<?= $todayDate ?>" value="<?= htmlspecialchars($_POST['end_date'] ?? date('Y-m-d', strtotime('+2 days'))) ?>" required style="padding: 10px; border: 1px solid #ccd8ce; border-radius: 8px;">
                </div>

                <!-- Trip Duration calculated box -->
                <div style="display: flex; flex-direction: column; gap: 5px;">
                    <label for="duration_display" style="font-weight: bold; font-size: 14px;">
                        Trip Duration <span style="font-size: 12px; font-weight: normal; color: #526359;">(Max 10 days limit)</span>
                    </label>
                    <input type="text" id="duration_display" value="Select your dates" readonly style="padding: 10px; border: 1px solid #ccd8ce; border-radius: 8px; background: #f0f5f1; font-weight: bold;">
                    <span id="duration_hint" class="smalltext" style="font-size: 12px; color: #526359;" role="status" aria-live="polite">Trips are limited to 10 days to keep your itinerary optimal.</span>
                </div>

                <div style="display: flex; flex-direction: column; gap: 5px;">
                    <label for="group_size" style="font-weight: bold; font-size: 14px;">
                        Group Size <span style="color: #c92a2a;">*</span>
                    </label>
                    <input type="number" id="group_size" name="group_size" min="1" max="100" value="<?= htmlspecialchars($_POST['group_size'] ?? '1') ?>" required style="padding: 10px; border: 1px solid #ccd8ce; border-radius: 8px;">
                </div>
            </div>
        </div>

        <div class="card" style="background: #fff; border: 1px solid #dce7df; border-radius: 12px; padding: 20px; margin-bottom: 20px;">
            <h2>Choose Activities</h2>
            <p class="muted" style="color: #68776e; margin-bottom: 15px;">Select activities from the available list to include in your trip.</p>

            <div class="activities-dual-grid">
                <!-- Available Activities Box -->
                <div style="border: 1px solid #dce7df; padding: 15px; border-radius: 10px; min-height: 250px;">
                    <h3 style="margin-top: 0; font-size: 16px;">Available Activities</h3>
                    <div id="availableList" style="display: flex; flex-direction: column; gap: 10px;">
                        <?php foreach ($activities as $act): ?>
                            <div class="item" data-id="<?= $act['id'] ?>" data-name="<?= htmlspecialchars($act['name']) ?>" data-cost="<?= $act['estimated_cost'] ?>" style="display: flex; justify-content: space-between; align-items: center; padding: 10px; border: 1px solid #e2e8e3; border-radius: 8px; background: #fafcfb; flex-wrap: wrap; gap: 8px;">
                                <div>
                                    <strong style="font-size: 14px; display: block;"><?= htmlspecialchars($act['name']) ?></strong>
                                    <span style="font-size: 12px; color: #68776e;">Estimated cost: ₹<?= $act['estimated_cost'] ?></span>
                                </div>
                                <button type="button" class="btn add-btn" style="padding: 7px 16px; background: #1f7a4d; color: #ffffff; border: 1px solid #196941; border-radius: 6px; cursor: pointer; font-weight: 700; font-size: 13px; box-shadow: 0 2px 4px rgba(31,122,77,0.2); transition: all 0.2s ease;">+ Add</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Selected Activities Box -->
                <div style="border: 1px solid #dce7df; padding: 15px; border-radius: 10px; min-height: 250px; display: flex; flex-direction: column;">
                    <h3 style="margin-top: 0; font-size: 16px;">Selected Activities</h3>
                    <div id="selectedList" style="display: flex; flex-direction: column; gap: 10px; flex-grow: 1;">
                        <p style="color: #68776e; font-size: 13px; text-align: center; margin: auto;" id="emptyMsg">No activities selected yet.</p>
                    </div>
                    <div style="margin-top: 15px; padding-top: 12px; border-top: 1px solid #dce7df; display: flex; justify-content: space-between; font-weight: bold;">
                        <span>Activities Total</span>
                        <span id="totalCost">₹0.00</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Live Per-Day Expense & Summary Preview Box -->
        <div class="card" id="tripSummaryCard" style="background: #f7faf8; border: 1px solid #d4eadc; border-radius: 12px; padding: 18px 20px; margin-bottom: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                <div>
                    <span class="muted" style="font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700; color: #406852;">Estimated Per Day Expense</span>
                    <div id="previewPerDay" style="font-size: 24px; font-weight: 800; color: var(--deep); margin-top: 2px;">₹0.00 / day</div>
                </div>
                <div style="text-align: right;">
                    <span class="muted" style="font-size: 12px;">Total Estimated Trip Cost</span>
                    <div id="previewTotalTrip" style="font-size: 18px; font-weight: 700; color: var(--ink); margin-top: 2px;">₹0.00</div>
                </div>
            </div>
            <div id="previewBreakdownText" style="margin-top: 10px; padding-top: 10px; border-top: 1px dashed #ccd8ce; font-size: 12.5px; color: #526359;">
                Includes base stay &amp; living (scales with days) plus selected activities.
            </div>
        </div>

        <div id="hiddenInputs"></div>

        <button type="submit" class="btn" id="submitButton" style="background: #247653; color: white; padding: 12px 20px; border: none; border-radius: 8px; font-size: 16px; font-weight: bold; cursor: pointer;">
            Continue to Itinerary &rarr;
        </button>
    </form>
</div>

<script>
const baseDailyRate = <?= (float) ($destination['estimated_cost'] ?? 0) ?>;
const todayStr = '<?= $todayDate ?>';
const startInput = document.getElementById('start_date');
const endInput = document.getElementById('end_date');
const budgetInput = document.getElementById('trip_budget');
const groupInput = document.getElementById('group_size');
const durationDisplay = document.getElementById('duration_display');
const submitButton = document.getElementById('submitButton');
const previewPerDay = document.getElementById('previewPerDay');
const previewTotalTrip = document.getElementById('previewTotalTrip');
const previewBreakdownText = document.getElementById('previewBreakdownText');
const selectedIds = new Set();
let currentActivityCost = 0;

function enforceDateLimits() {
    startInput.min = todayStr;
    if (startInput.value && startInput.value < todayStr) {
        startInput.value = todayStr;
        if (typeof window.showCustomAlert === 'function') {
            window.showCustomAlert('Start date cannot be in the past (yesterday or earlier).', 'warning');
        }
    }

    if (!startInput.value) return;

    const start = new Date(startInput.value);
    if (isNaN(start.getTime())) return;
    
    // Max 10 days inclusive => start + 9 days
    const maxEnd = new Date(start);
    maxEnd.setDate(maxEnd.getDate() + 9);

    const minStr = (startInput.value && startInput.value > todayStr) ? startInput.value : todayStr;
    const maxStr = maxEnd.toISOString().split('T')[0];

    endInput.min = minStr;
    endInput.max = maxStr;

    if (endInput.value) {
        if (endInput.value < minStr) {
            endInput.value = minStr;
            if (typeof window.showCustomAlert === 'function') {
                window.showCustomAlert('End date cannot be earlier than start date or in the past.', 'warning');
            }
        } else if (endInput.value > maxStr) {
            endInput.value = maxStr;
            if (typeof window.showCustomAlert === 'function') {
                window.showCustomAlert('Trip duration is capped at a maximum of 10 days.', 'warning');
            }
        }
    }
}

function updateCalculations() {
    enforceDateLimits();

    const start = new Date(startInput.value);
    const end = new Date(endInput.value);

    if (startInput.value && endInput.value && !isNaN(start.getTime()) && !isNaN(end.getTime())) {
        let diffDays = Math.round((end - start) / (1000 * 60 * 60 * 24)) + 1;

        if (diffDays > 10) {
            diffDays = 10;
            const maxEnd = new Date(start);
            maxEnd.setDate(maxEnd.getDate() + 9);
            endInput.value = maxEnd.toISOString().split('T')[0];
        }

        if (diffDays > 0) {
            durationDisplay.value = diffDays + (diffDays === 1 ? ' day' : ' days');
            submitButton.disabled = false;

            const group = Math.max(1, parseInt(groupInput.value || '1', 10));
            const budgetVal = parseFloat(budgetInput.value || '0');

            // Daily base expense scales with days!
            const stayTotal = baseDailyRate * group * diffDays;
            const totalEst = stayTotal + currentActivityCost;
            const perDayEst = totalEst / diffDays;
            const perDayBudget = (budgetVal > 0 && diffDays > 0) ? (budgetVal / diffDays) : 0;

            previewPerDay.innerHTML = `₹${perDayEst.toLocaleString('en-IN', {maximumFractionDigits: 2})} <span style="font-size: 13px; font-weight: 600; color: #526359;">/ day</span>`;
            previewTotalTrip.textContent = `₹${totalEst.toLocaleString('en-IN', {maximumFractionDigits: 2})}`;

            previewBreakdownText.innerHTML = `
                Base stay &amp; living (₹${baseDailyRate.toLocaleString('en-IN')} &times; ${diffDays}d &times; ${group}p = <strong>₹${stayTotal.toLocaleString('en-IN')}</strong>) 
                + Activities (<strong>₹${currentActivityCost.toLocaleString('en-IN')}</strong>)
                ${budgetVal > 0 ? ` &bull; Planned budget: ≈ <strong>₹${perDayBudget.toFixed(2)}/day</strong> (Total ₹${budgetVal.toLocaleString('en-IN')})` : ''}
            `;
        } else {
            durationDisplay.value = 'Invalid date range';
            submitButton.disabled = true;
        }
    } else {
        durationDisplay.value = 'Select your dates';
    }
}

startInput.addEventListener('change', updateCalculations);
endInput.addEventListener('change', updateCalculations);
budgetInput.addEventListener('input', updateCalculations);
groupInput.addEventListener('input', updateCalculations);
updateCalculations();

document.getElementById('availableList').addEventListener('click', function(e) {
    if (e.target.classList.contains('add-btn')) {
        const item = e.target.closest('.item');
        const id = item.dataset.id;
        
        if (selectedIds.has(id)) {
            selectedIds.delete(id);
            e.target.innerHTML = '+ Add';
            e.target.style.background = '#1f7a4d';
            e.target.style.color = '#ffffff';
            e.target.style.border = '1px solid #196941';
            e.target.style.boxShadow = '0 2px 4px rgba(31,122,77,0.2)';
        } else {
            selectedIds.add(id);
            e.target.innerHTML = '&#10005; Remove';
            e.target.style.background = '#dc2626';
            e.target.style.color = '#ffffff';
            e.target.style.border = '1px solid #b91c1c';
            e.target.style.boxShadow = '0 2px 4px rgba(220,38,38,0.2)';
        }
        renderSelected();
    }
});

function renderSelected() {
    const selectedList = document.getElementById('selectedList');
    const hiddenInputs = document.getElementById('hiddenInputs');
    const emptyMsg = document.getElementById('emptyMsg');
    
    selectedList.innerHTML = '';
    hiddenInputs.innerHTML = '';
    
    let total = 0;
    let count = 0;

    document.querySelectorAll('.item').forEach(item => {
        const id = item.dataset.id;
        if (selectedIds.has(id)) {
            count++;
            const name = item.dataset.name;
            const cost = parseFloat(item.dataset.cost);
            total += cost;

            const div = document.createElement('div');
            div.style.cssText = "display: flex; justify-content: space-between; align-items: center; padding: 8px 10px; border: 1px solid #dce7df; border-radius: 6px; background: #fff;";
            div.innerHTML = `<span style="font-weight: 500; font-size: 13px;">${name}</span> <span style="font-size: 13px; font-weight: bold;">₹${cost.toFixed(0)}</span>`;
            selectedList.appendChild(div);

            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'activities[]';
            input.value = id;
            hiddenInputs.appendChild(input);
        }
    });

    if (count === 0) {
        selectedList.appendChild(emptyMsg);
    }
    currentActivityCost = total;
    document.getElementById('totalCost').textContent = '₹' + total.toFixed(2);
    updateCalculations();
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
