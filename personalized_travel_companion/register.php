<?php
require 'config.php';

if (logged_in()) {
    redirect('index.php');
}

$pageTitle = 'Create account';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $name = trim($_POST['full_name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (
        $name === '' ||
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        strlen($password) < 8 ||
        $password !== $confirm
    ) {
        $error = 'Enter a name, valid email, matching passwords, and a password with at least 8 characters.';
    } else {
        try {
            $s = $pdo->prepare(
                'INSERT INTO users (full_name, email, password_hash)
                 VALUES (?, ?, ?)'
            );

            $s->execute([
                $name,
                $email,
                password_hash($password, PASSWORD_DEFAULT)
            ]);

            $_SESSION['user'] = [
                'id' => (int) $pdo->lastInsertId(),
                'full_name' => $name,
                'email' => $email,
                'role' => 'user'
            ];

            session_regenerate_id(true);

            redirect('index.php');
        } catch (PDOException $e) {
            $error = 'That email may already be registered.';
        }
    }
}

require 'header.php';
?>

<div class="card form-card">
    <h1>Create account</h1>

    <p class="muted">
        Start planning personalized trips.
    </p>

    <?php if ($error): ?>
        <div class="flash error">
            <?= e($error) ?>
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
                type="text"
                name="full_name"
                required
                maxlength="100"
                value="<?= e($_POST['full_name'] ?? '') ?>"
            >
        </label>

        <label>
            Email
            <input
                type="email"
                name="email"
                required
                value="<?= e($_POST['email'] ?? '') ?>"
            >
        </label>

        <label>
            Password
            <input
                type="password"
                name="password"
                minlength="8"
                required
            >
        </label>

        <label>
            Confirm password
            <input
                type="password"
                name="confirm_password"
                minlength="8"
                required
            >
        </label>

        <button class="btn" type="submit">
            Create account
        </button>
    </form>

    <p>
        Already registered?
        <a href="login.php">Login</a>
    </p>
</div>

<?php require 'footer.php'; ?>
