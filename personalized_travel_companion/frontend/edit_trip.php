
<?php

require_once dirname(__DIR__) . '/backend/trips.php';

require_login();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$id = ($id !== false && $id !== null && $id > 0) ? $id : 0;

$userId = (int) ($_SESSION['user']['id'] ?? 0);

if ($id <= 0 || $userId <= 0) {
    redirect('my_trips.php');
}

$trip = get_trip_details($pdo, $id, $userId);

if (!$trip) {
    redirect('my_trips.php');
}

// Escape output safely.
$escape = static function ($value): string {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
};

$error = '';

$form = [
    'title'      => (string) ($trip['title'] ?? ''),
    'budget'     => (string) ($trip['budget'] ?? '0'),
    'start_date' => (string) ($trip['start_date'] ?? ''),
    'end_date'   => (string) ($trip['end_date'] ?? ''),
    'group_size' => (string) ($trip['group_size'] ?? '1'),
];

// Validate YYYY-MM-DD dates.
$isValidDate = static function (string $date): bool {
    $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);

    return $parsed !== false
        && $parsed->format('Y-m-d') === $date;
};

// Handle form submission.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    foreach ($form as $field => $value) {
        $form[$field] = trim(
            (string) ($_POST[$field] ?? $value)
        );
    }

    $title = $form['title'];
    $startDate = $form['start_date'];
    $endDate = $form['end_date'];

    $groupSize = filter_var(
        $form['group_size'],
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1, 'max_range' => 100]]
    );

    $budgetValid = $form['budget'] !== ''
        && is_numeric($form['budget'])
        && is_finite((float) $form['budget'])
        && (float) $form['budget'] >= 0;

    if ($title === '') {
        $error = 'Please enter a trip title.';
    } elseif (strlen($title) > 255) {
        $error = 'Trip title cannot exceed 255 characters.';
    } elseif (
        !$isValidDate($startDate) ||
        !$isValidDate($endDate)
    ) {
        $error = 'Please select valid start and end dates.';
    } elseif ($endDate < $startDate) {
        $error = 'End date cannot be before start date.';
    } elseif ($groupSize === false) {
        $error = 'Group size must be between 1 and 100.';
    } elseif (!$budgetValid) {
        $error = 'Please enter a valid, non-negative budget.';
    } else {
        try {
            update_trip($pdo, $id, $userId, [
                'title'      => $title,
                'start_date' => $startDate,
                'end_date'   => $endDate,
                'group_size' => $groupSize,
                'budget'     => (float) $form['budget'],
            ]);

            redirect('itinerary.php?id=' . $id);
        } catch (Throwable $e) {
            error_log('Trip update failed: ' . $e->getMessage());
            $error = 'Could not update trip. Please try again.';
        }
    }
}

$pageTitle = 'Edit Trip';

require_once __DIR__ . '/includes/header.php';
?>

<style>
    .edit-trip {
        width: calc(100% - 32px);
        max-width: 800px;
        margin: 30px auto;
        padding: 30px;
        box-sizing: border-box;
        background: #fff;
        border: 1px solid #dce7df;
        border-radius: 16px;
        box-shadow: 0 8px 24px rgba(20, 60, 40, 0.05);
    }

    .edit-trip h1 {
        margin: 0 0 8px;
        color: #183c2b;
        font-size: 28px;
    }

    .edit-trip .muted {
        margin: 0 0 25px;
        color: #66756b;
        line-height: 1.6;
    }

    .edit-trip .form-fields {
        display: flex;
        flex-direction: column;
        gap: 18px;
    }

    .edit-trip .field {
        display: block;
        min-width: 0;
        color: #263b2e;
        font-size: 14px;
        font-weight: 600;
    }

    .edit-trip .field input {
        display: block;
        width: 100%;
        min-width: 0;
        box-sizing: border-box;
        margin-top: 7px;
        padding: 12px;
        color: #23352b;
        background: #fff;
        border: 1px solid #ccd8ce;
        border-radius: 8px;
        font: inherit;
        font-weight: 400;
    }

    .edit-trip .field input:focus {
        outline: 2px solid #247653;
        outline-offset: 1px;
        border-color: #247653;
    }

    .edit-trip .date-fields {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px;
    }

    .edit-trip .form-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin-top: 26px;
    }

    .edit-trip .action-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        box-sizing: border-box;
        padding: 12px 20px;
        border: none;
        border-radius: 8px;
        font: inherit;
        font-weight: 600;
        text-decoration: none;
        cursor: pointer;
    }

    .edit-trip .save-btn {
        color: #fff;
        background: #247653;
    }

    .edit-trip .save-btn:hover {
        background: #195c3e;
    }

    .edit-trip .cancel-btn {
        color: #23352b;
        background: #e9efeb;
    }

    .edit-trip .cancel-btn:hover {
        background: #dce6df;
    }

    .edit-trip .error-message {
        margin-bottom: 22px;
        padding: 13px 15px;
        color: #a83232;
        background: #ffebeb;
        border: 1px solid #f3caca;
        border-radius: 8px;
    }

    @media (max-width: 600px) {
        .edit-trip {
            margin: 16px auto;
            padding: 20px 16px;
        }

        .edit-trip h1 {
            font-size: 24px;
        }

        .edit-trip .date-fields {
            grid-template-columns: minmax(0, 1fr);
        }

        .edit-trip .form-actions {
            flex-direction: column;
        }

        .edit-trip .action-btn {
            width: 100%;
        }
    }
</style>

<main class="container edit-trip">

    <h1>Edit Trip</h1>

    <p class="muted">
        Update the details for
        <strong><?= $escape($trip['destination_name'] ?? 'your destination') ?></strong>.
    </p>

    <?php if ($error !== ''): ?>
        <div class="error-message" role="alert">
            <?= $escape($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="edit_trip.php?id=<?= $id ?>">

        <input
            type="hidden"
            name="csrf"
            value="<?= $escape(csrf_token()) ?>"
        >

        <div class="form-fields">

            <label class="field">
                Trip Title
                <input
                    type="text"
                    name="title"
                    maxlength="255"
                    value="<?= $escape($form['title']) ?>"
                    placeholder="Enter your trip title"
                    required
                >
            </label>

            <label class="field">
                Total Budget (₹)
                <input
                    type="number"
                    name="budget"
                    min="0"
                    step="0.01"
                    value="<?= $escape($form['budget']) ?>"
                    placeholder="Enter your budget"
                    required
                >
            </label>

            <div class="date-fields">

                <label class="field">
                    Start Date
                    <input
                        type="date"
                        id="start_date"
                        name="start_date"
                        value="<?= $escape($form['start_date']) ?>"
                        required
                    >
                </label>

                <label class="field">
                    End Date
                    <input
                        type="date"
                        id="end_date"
                        name="end_date"
                        value="<?= $escape($form['end_date']) ?>"
                        required
                    >
                </label>

            </div>

            <label class="field">
                Group Size
                <input
                    type="number"
                    name="group_size"
                    min="1"
                    max="100"
                    step="1"
                    value="<?= $escape($form['group_size']) ?>"
                    required
                >
            </label>

        </div>

        <div class="form-actions">

            <button
                type="submit"
                class="action-btn save-btn"
            >
                Save Changes
            </button>

            <a
                href="itinerary.php?id=<?= $id ?>"
                class="action-btn cancel-btn"
            >
                Cancel
            </a>

        </div>

    </form>

</main>

<script>
    const startInput = document.getElementById('start_date');
    const endInput = document.getElementById('end_date');

    function validateDates() {
        endInput.min = startInput.value;

        if (
            startInput.value &&
            endInput.value &&
            endInput.value < startInput.value
        ) {
            endInput.setCustomValidity(
                'End date cannot be before the start date.'
            );
        } else {
            endInput.setCustomValidity('');
        }
    }

    startInput.addEventListener('change', validateDates);
    endInput.addEventListener('change', validateDates);

    validateDates();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
