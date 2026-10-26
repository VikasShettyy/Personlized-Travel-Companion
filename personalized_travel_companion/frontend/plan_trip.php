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

    if ($startDate === '' || $endDate === '') {
        $error = 'Please select both start and end dates.';
    } elseif ($endDate < $startDate) {
        $error = 'End date cannot be before start date.';
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

            <div class="grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <label style="display: flex; flex-direction: column; gap: 5px; font-weight: bold; font-size: 14px;">
                    Trip Title
                    <input type="text" name="title" value="<?= htmlspecialchars($_POST['title'] ?? $destination['name'] . ' Trip') ?>" required style="padding: 10px; border: 1px solid #ccd8ce; border-radius: 8px;">
                </label>

                <label style="display: flex; flex-direction: column; gap: 5px; font-weight: bold; font-size: 14px;">
                    Total Budget (₹)
                    <input type="number" name="budget" min="0" step="0.01" value="<?= htmlspecialchars($_POST['budget'] ?? '10000') ?>" required style="padding: 10px; border: 1px solid #ccd8ce; border-radius: 8px;">
                </label>

                <label style="display: flex; flex-direction: column; gap: 5px; font-weight: bold; font-size: 14px;">
                    Start Date
                    <input type="date" id="start_date" name="start_date" value="<?= htmlspecialchars($_POST['start_date'] ?? date('Y-m-d')) ?>" required style="padding: 10px; border: 1px solid #ccd8ce; border-radius: 8px;">
                </label>

                <label style="display: flex; flex-direction: column; gap: 5px; font-weight: bold; font-size: 14px;">
                    End Date
                    <input type="date" id="end_date" name="end_date" value="<?= htmlspecialchars($_POST['end_date'] ?? date('Y-m-d', strtotime('+2 days'))) ?>" required style="padding: 10px; border: 1px solid #ccd8ce; border-radius: 8px;">
                </label>

                <!-- Trip Duration calculated box -->
                <label style="display: flex; flex-direction: column; gap: 5px; font-weight: bold; font-size: 14px;">
                    Trip duration
                    <input type="text" id="duration_display" value="Select your dates" readonly style="padding: 10px; border: 1px solid #ccd8ce; border-radius: 8px; background: #f0f5f1; font-weight: bold;">
                    <span class="smalltext" style="font-size: 12px; color: #68776e; font-weight: normal;">Calculated automatically from your dates.</span>
                </label>

                <label style="display: flex; flex-direction: column; gap: 5px; font-weight: bold; font-size: 14px;">
                    Group Size
                    <input type="number" name="group_size" min="1" max="100" value="<?= htmlspecialchars($_POST['group_size'] ?? '1') ?>" required style="padding: 10px; border: 1px solid #ccd8ce; border-radius: 8px;">
                </label>
            </div>
        </div>

        <div class="card" style="background: #fff; border: 1px solid #dce7df; border-radius: 12px; padding: 20px; margin-bottom: 20px;">
            <h2>Choose Activities</h2>
            <p class="muted" style="color: #68776e; margin-bottom: 15px;">Select activities from the available list to include in your trip.</p>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <!-- Available Activities Box -->
                <div style="border: 1px solid #dce7df; padding: 15px; border-radius: 10px; min-height: 250px;">
                    <h3 style="margin-top: 0; font-size: 16px;">Available Activities</h3>
                    <div id="availableList" style="display: flex; flex-direction: column; gap: 10px;">
                        <?php foreach ($activities as $act): ?>
                            <div class="item" data-id="<?= $act['id'] ?>" data-name="<?= htmlspecialchars($act['name']) ?>" data-cost="<?= $act['estimated_cost'] ?>" style="display: flex; justify-content: space-between; align-items: center; padding: 10px; border: 1px solid #e2e8e3; border-radius: 8px; background: #fafcfb;">
                                <div>
                                    <strong style="font-size: 14px; display: block;"><?= htmlspecialchars($act['name']) ?></strong>
                                    <span style="font-size: 12px; color: #68776e;">Estimated cost: ₹<?= $act['estimated_cost'] ?></span>
                                </div>
                                <button type="button" class="btn add-btn" style="padding: 6px 12px; background: #eef5ef; border: 1px solid #ccd8ce; border-radius: 6px; cursor: pointer; font-weight: bold;">Add</button>
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
                        <span>Total</span>
                        <span id="totalCost">₹0.00</span>
                    </div>
                </div>
            </div>
        </div>

        <div id="hiddenInputs"></div>

        <button type="submit" class="btn" id="submitButton" style="background: #247653; color: white; padding: 12px 20px; border: none; border-radius: 8px; font-size: 16px; font-weight: bold; cursor: pointer;">
            Continue to Itinerary &rarr;
        </button>
    </form>
</div>

<script>
const startInput = document.getElementById('start_date');
const endInput = document.getElementById('end_date');
const durationDisplay = document.getElementById('duration_display');
const submitButton = document.getElementById('submitButton');
const selectedIds = new Set();

function updateDuration() {
    const start = new Date(startInput.value);
    const end = new Date(endInput.value);

    if (startInput.value && endInput.value) {
        const diffDays = Math.round((end - start) / (1000 * 60 * 60 * 24)) + 1;
        if (diffDays > 0) {
            durationDisplay.value = diffDays + (diffDays === 1 ? ' day' : ' days');
            submitButton.disabled = false;
        } else {
            durationDisplay.value = 'Invalid date range';
            submitButton.disabled = true;
        }
    } else {
        durationDisplay.value = 'Select your dates';
    }
}

startInput.addEventListener('change', updateDuration);
endInput.addEventListener('change', updateDuration);
updateDuration();

document.getElementById('availableList').addEventListener('click', function(e) {
    if (e.target.classList.contains('add-btn')) {
        const item = e.target.closest('.item');
        const id = item.dataset.id;
        
        if (selectedIds.has(id)) {
            selectedIds.delete(id);
            e.target.textContent = 'Add';
            e.target.style.background = '#eef5ef';
            e.target.style.color = '#23352b';
        } else {
            selectedIds.add(id);
            e.target.textContent = 'Remove';
            e.target.style.background = '#ffebeb';
            e.target.style.color = '#a83232';
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
    document.getElementById('totalCost').textContent = '₹' + total.toFixed(2);
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
