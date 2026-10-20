<?php
require 'config.php';

if (logged_in()) {
    redirect('index.php');
}

$pageTitle = 'Login';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    $s = $pdo->prepare(
        'SELECT id, full_name, email, password_hash, role
         FROM users
         WHERE email = ?'
    );

    $s->execute([$email]);
    $u = $s->fetch();

    if ($u && password_verify($password, $u['password_hash'])) {
        session_regenerate_id(true);

        $_SESSION['user'] = [
            'id' => (int)$u['id'],
            'full_name' => $u['full_name'],
            'email' => $u['email'],
            'role' => $u['role']
        ];

        redirect('index.php');
    }

    $error = 'Email or password is incorrect.';
}

require 'header.php';
?>

<div class="card form-card">

    <h1>Welcome back</h1>

    <p class="muted">
        Login to manage your trips.
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
                required
            >
        </label>

        <button class="btn">Login</button>

    </form>

    <p>
        Don't have an account?
        <a href="register.php">Register</a>
    </p>

</div>

<?php require 'footer.php'; ?>