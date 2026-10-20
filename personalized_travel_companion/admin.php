<?php
require 'config.php';
require_admin();

$pageTitle = 'Admin Panel';
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'category_add') {
            $name = trim($_POST['name'] ?? '');

            if ($name !== '') {
                $pdo->prepare(
                    'INSERT INTO categories(name) VALUES(?)'
                )->execute([$name]);
            }
        }

        if (
            $action === 'destination_add' ||
            $action === 'destination_update'
        ) {
            $name = trim($_POST['name'] ?? '');
            $district = trim($_POST['district'] ?? '');
            $cat = (int) ($_POST['category_id'] ?? 0);
            $desc = trim($_POST['description'] ?? '');
            $img = trim($_POST['image_url'] ?? '');
            $cost = max(
                0,
                (float) ($_POST['estimated_cost'] ?? 0)
            );

            if ($name === '' || $district === '') {
                throw new Exception(
                    'Destination name and district are required.'
                );
            }

            if ($action === 'destination_add') {
                $pdo->prepare(
                    'INSERT INTO destinations
                    (name, district, category_id, description,
                    image_url, estimated_cost)
                    VALUES (?, ?, ?, ?, ?, ?)'
                )->execute([
                    $name,
                    $district,
                    $cat ?: null,
                    $desc,
                    $img,
                    $cost
                ]);
            } else {
                $pdo->prepare(
                    'UPDATE destinations
                    SET name = ?, district = ?, category_id = ?,
                    description = ?, image_url = ?, estimated_cost = ?
                    WHERE id = ?'
                )->execute([
                    $name,
                    $district,
                    $cat ?: null,
                    $desc,
                    $img,
                    $cost,
                    (int) $_POST['id']
                ]);
            }
        }

        if ($action === 'destination_delete') {
            $pdo->prepare(
                'DELETE FROM destinations WHERE id = ?'
            )->execute([(int) $_POST['id']]);
        }

        if ($action === 'activity_add') {
            $pdo->prepare(
                'INSERT INTO activities
                (destination_id, name, description, estimated_cost)
                VALUES (?, ?, ?, ?)'
            )->execute([
                (int) $_POST['destination_id'],
                trim($_POST['name']),
                trim($_POST['description'] ?? ''),
                max(0, (float) $_POST['estimated_cost'])
            ]);
        }

        if ($action === 'activity_delete') {
            $pdo->prepare(
                'DELETE FROM activities WHERE id = ?'
            )->execute([(int) $_POST['id']]);
        }

        $_SESSION['flash'] = 'Admin changes saved.';
        redirect('admin.php');

    } catch (Throwable $e) {
        $error = $e instanceof PDOException
            ? 'Could not save. Check for duplicate names or related records.'
            : $e->getMessage();
    }
}

$categories = $pdo->query(
    'SELECT * FROM categories ORDER BY name'
)->fetchAll();

$destinations = $pdo->query(
    'SELECT d.*, c.name category_name
     FROM destinations d
     LEFT JOIN categories c ON c.id = d.category_id
     ORDER BY d.name'
)->fetchAll();

$activities = $pdo->query(
    'SELECT a.*, d.name destination_name
     FROM activities a
     JOIN destinations d ON d.id = a.destination_id
     ORDER BY d.name, a.name'
)->fetchAll();

$users = $pdo->query(
    'SELECT COUNT(*) c FROM users'
)->fetch()['c'];

$trips = $pdo->query(
    'SELECT COUNT(*) c FROM trips'
)->fetch()['c'];

require 'header.php';
?>

<h1>Administration Panel</h1>

<p class="muted">
    Manage destination content, categories and activities.
</p>

<?php if ($error): ?>
    <div class="flash error">
        <?= e($error) ?>
    </div>
<?php endif; ?>

<div class="grid">

    <div class="card">
        <div class="muted">Destinations</div>
        <div class="metric">
            <?= count($destinations) ?>
        </div>
    </div>

    <div class="card">
        <div class="muted">Users</div>
        <div class="metric">
            <?= $users ?>
        </div>
    </div>

    <div class="card">
        <div class="muted">Trips</div>
        <div class="metric">
            <?= $trips ?>
        </div>
    </div>

</div>

<div class="two-col">

    <div class="card">
        <h2>Add destination</h2>

        <form class="stack" method="post">
            <input
                type="hidden"
                name="csrf"
                value="<?= csrf_token() ?>"
            >

            <input
                type="hidden"
                name="action"
                value="destination_add"
            >

            <label>
                Name
                <input name="name" required>
            </label>

            <label>
                District
                <input name="district" required>
            </label>

            <label>
                Category
                <select name="category_id">
                    <option value="0">Choose category</option>

                    <?php foreach ($categories as $c): ?>
                        <option value="<?= $c['id'] ?>">
                            <?= e($c['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>
                Estimated base cost (₹)
                <input
                    type="number"
                    min="0"
                    step=".01"
                    name="estimated_cost"
                    value="0"
                >
            </label>

            <label>
                Image URL
                <input type="url" name="image_url">
            </label>

            <label>
                Description
                <textarea name="description"></textarea>
            </label>

            <button class="btn">Save destination</button>
        </form>
    </div>

    <div class="card">
        <h2>Add category</h2>

        <form method="post" class="stack">
            <input
                type="hidden"
                name="csrf"
                value="<?= csrf_token() ?>"
            >

            <input
                type="hidden"
                name="action"
                value="category_add"
            >

            <label>
                Category name
                <input name="name" required>
            </label>

            <button class="btn">Add category</button>
        </form>

        <h2>Add activity</h2>

        <form method="post" class="stack">
            <input
                type="hidden"
                name="csrf"
                value="<?= csrf_token() ?>"
            >

            <input
                type="hidden"
                name="action"
                value="activity_add"
            >

            <label>
                Destination
                <select name="destination_id">
                    <?php foreach ($destinations as $d): ?>
                        <option value="<?= $d['id'] ?>">
                            <?= e($d['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>
                Activity name
                <input name="name" required>
            </label>

            <label>
                Description
                <textarea name="description"></textarea>
            </label>

            <label>
                Estimated cost (₹)
                <input
                    type="number"
                    min="0"
                    step=".01"
                    name="estimated_cost"
                    value="0"
                >
            </label>

            <button class="btn">Add activity</button>
        </form>
    </div>

</div>

<h2>Destinations</h2>

<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>District</th>
                <th>Category</th>
                <th>Cost</th>
                <th>Action</th>
            </tr>
        </thead>

        <tbody>
            <?php foreach ($destinations as $d): ?>
                <tr>
                    <td><?= e($d['name']) ?></td>

                    <td><?= e($d['district']) ?></td>

                    <td>
                        <?= e($d['category_name'] ?? '') ?>
                    </td>

                    <td>
                        ₹<?= number_format(
                            (float) $d['estimated_cost']
                        ) ?>
                    </td>

                    <td>
                        <form
                            method="post"
                            data-confirm="Delete this destination and its activities?"
                        >
                            <input
                                type="hidden"
                                name="csrf"
                                value="<?= csrf_token() ?>"
                            >

                            <input
                                type="hidden"
                                name="action"
                                value="destination_delete"
                            >

                            <input
                                type="hidden"
                                name="id"
                                value="<?= $d['id'] ?>"
                            >

                            <button class="btn danger small">
                                Delete
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<h2>Activities</h2>

<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Activity</th>
                <th>Destination</th>
                <th>Cost</th>
                <th>Action</th>
            </tr>
        </thead>

        <tbody>
            <?php foreach ($activities as $a): ?>
                <tr>
                    <td><?= e($a['name']) ?></td>

                    <td><?= e($a['destination_name']) ?></td>

                    <td>
                        ₹<?= number_format(
                            (float) $a['estimated_cost']
                        ) ?>
                    </td>

                    <td>
                        <form
                            method="post"
                            data-confirm="Delete this activity?"
                        >
                            <input
                                type="hidden"
                                name="csrf"
                                value="<?= csrf_token() ?>"
                            >

                            <input
                                type="hidden"
                                name="action"
                                value="activity_delete"
                            >

                            <input
                                type="hidden"
                                name="id"
                                value="<?= $a['id'] ?>"
                            >

                            <button class="btn danger small">
                                Delete
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require 'footer.php'; ?>