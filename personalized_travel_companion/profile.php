<?php
require 'config.php';
require_login();

$pageTitle = 'My Profile';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $name = trim($_POST['full_name'] ?? '');

    if ($name !== '') {
        $s = $pdo->prepare(
            'UPDATE users SET full_name = ? WHERE id = ?'
        );

        $s->execute([
            $name,
            $_SESSION['user']['id']
        ]);

        $_SESSION['user']['full_name'] = $name;
        $message = 'Profile updated.';
    }
}

require 'header.php';
?>

<div class="card form-card">
    <h1>My Profile</h1>

    <?php if ($message): ?>
        <div class="flash">
            <?= e($message) ?>
        </div>
    <?php endif; ?>

    <form class="stack" method="post">
        <input
            type="hidden"
            name="csrf"
            value="<?= csrf_token() ?>"
        >

        <label>
            Full name
            <input
                name="full_name"
                required
                value="<?= e($_SESSION['user']['full_name']) ?>"
            >
        </label>

        <label>
            Email
            <input
                value="<?= e($_SESSION['user']['email']) ?>"
                disabled
            >
        </label>

        <button class="btn" type="submit">
            Update profile
        </button>
    </form>
</div>

<?php require 'footer.php'; ?>