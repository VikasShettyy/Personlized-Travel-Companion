$pageTitle = 'Edit Trip';
require_once __DIR__ . '/includes/header.php';
?>

<style>
    .edit-trip-page {
        width: min(800px, calc(100% - 32px));
        box-sizing: border-box;
        margin: 30px auto;
        padding: 30px;
        background: #fff;
        border: 1px solid #dce7df;
        border-radius: 14px;
        box-shadow: 0 8px 24px rgba(20, 60, 40, .05);
    }
    .edit-trip-page h1 { margin: 0 0 8px; color: #183c2b; }
    .edit-trip-page .subtitle { margin: 0 0 24px; color: #66756b; }
    .edit-trip-page .fields { display: grid; gap: 18px; }
    .edit-trip-page .field { display: block; min-width: 0; color: #263b2e; font-size: 14px; font-weight: 600; }
    .edit-trip-page .field input {
        display: block; width: 100%; min-width: 0; box-sizing: border-box;
        margin-top: 7px; padding: 12px; color: #23352b; background: #fff;
        border: 1px solid #ccd8ce; border-radius: 8px; font: inherit; font-weight: 400;
    }
    .edit-trip-page .field input:focus { outline: 2px solid rgba(36,118,83,.2); border-color: #247653; }
    .edit-trip-page .date-fields { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
    .edit-trip-page .form-error { padding: 12px 14px; margin-bottom: 20px; color: #a83232; background: #ffebeb; border: 1px solid #f3caca; border-radius: 8px; }
    .edit-trip-page .actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 26px; }
    .edit-trip-page .action-btn { display: inline-flex; align-items: center; justify-content: center; padding: 12px 20px; border: 0; border-radius: 8px; font: inherit; font-weight: 600; text-decoration: none; cursor: pointer; }
    .edit-trip-page .save-btn { color: #fff; background: #247653; }
    .edit-trip-page .save-btn:hover { background: #195c3e; }
    .edit-trip-page .cancel-btn { color: #23352b; background: #e9efeb; }
    @media (max-width: 600px) {
        .edit-trip-page { margin: 16px auto; padding: 20px 16px; }
        .edit-trip-page .date-fields { grid-template-columns: minmax(0, 1fr); }
        .edit-trip-page .actions { flex-direction: column; }
        .edit-trip-page .action-btn { width: 100%; box-sizing: border-box; }
    }
</style>

<main class="container edit-trip-page">
    <h1>Edit Trip</h1>
    <p class="subtitle">
        Update the details for <strong><?= $escape($trip['destination_name'] ?? 'your destination') ?></strong>.
    </p>

    <?php if ($error !== ''): ?>
        <div class="form-error" role="alert"><?= $escape($error) ?></div>
    <?php endif; ?>

    <form method="post" action="edit_trip.php?id=<?= (int) $id ?>">
        <input type="hidden" name="csrf" value="<?= $escape(csrf_token()) ?>">

        <div class="fields">
            <label class="field" for="title">
                Trip Title
                <input type="text" id="title" name="title" maxlength="255"
                       value="<?= $escape($form['title']) ?>" placeholder="Enter trip title" required>
            </label>

            <label class="field" for="budget">
                Total Budget (₹)
                <input type="number" id="budget" name="budget" min="0" step="0.01"
                       value="<?= $escape($form['budget']) ?>" placeholder="Enter total budget" required>
            </label>

            <div class="date-fields">
                <label class="field" for="start_date">
                    Start Date
                    <input type="date" id="start_date" name="start_date"
                           value="<?= $escape($form['start_date']) ?>" required>
                </label>
                <label class="field" for="end_date">
                    End Date
                    <input type="date" id="end_date" name="end_date"
                           value="<?= $escape($form['end_date']) ?>" required>
                </label>
            </div>

            <label class="field" for="group_size">
                Group Size
                <input type="number" id="group_size" name="group_size" min="1" max="100" step="1"
                       value="<?= $escape($form['group_size']) ?>" required>
            </label>
        </div>

        <div class="actions">
            <button type="submit" class="action-btn save-btn">Save Changes</button>
            <a href="itinerary.php?id=<?= (int) $id ?>" class="action-btn cancel-btn">Cancel</a>
        </div>
    </form>
</main>

<script>
(() => {
    const start = document.getElementById('start_date');
    const end = document.getElementById('end_date');

    function validateDates() {
        end.min = start.value || '';
        end.setCustomValidity(
            start.value && end.value && end.value < start.value
                ? 'End date cannot be before the start date.'
                : ''
        );
    }

    start.addEventListener('change', validateDates);
    end.addEventListener('change', validateDates);
    validateDates();
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
