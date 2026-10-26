<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/backend/auth.php';

if (logged_in()) {
    redirect('index.php');
}

$pageTitle = 'Login';
$error = '';

$lockoutUntil = (int) ($_SESSION['login_lockout_until'] ?? 0);
$now = time();
$isLocked = ($lockoutUntil > $now);
$remaining = $isLocked ? ($lockoutUntil - $now) : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $res = auth_login($pdo, $email, $password);
    if ($res['success']) {
        redirect('index.php');
    } else {
        $error = $res['error'];
        $isLocked = !empty($res['locked']);
        $remaining = (int) ($res['remaining'] ?? 0);
    }
}

$extraHead = '
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
';

require_once __DIR__ . '/includes/header.php';
?>

<div class="card form-card">

    <h1>Welcome back</h1>

    <p class="muted">
        Login to manage your trips.
    </p>

    <?php if ($isLocked && $remaining > 0): ?>
        <div id="lockout-banner" class="flash error" style="background: #fef2f2; border: 1px solid #f87171; color: #991b1b; padding: 14px 16px; border-radius: 8px; margin-bottom: 20px;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <i class="bi bi-shield-lock-fill" style="font-size: 24px; color: #dc2626;"></i>
                <div>
                    <strong>Too many failed attempts!</strong>
                    <div style="margin-top: 4px; font-size: 14px;">
                        Login temporarily blocked. Please wait <span id="lockout-countdown" style="font-weight: 800; font-size: 16px; color: #b91c1c;"><?= $remaining ?></span> seconds.
                    </div>
                </div>
            </div>
        </div>
    <?php elseif ($error): ?>
        <div class="flash error">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <form class="stack" method="post" id="login-form">

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
                id="login-email"
                required
                value="<?= e($_POST['email'] ?? '') ?>"
                <?= ($isLocked && $remaining > 0) ? 'disabled' : '' ?>
            >
        </label>

        <label>
            <div class="between" style="align-items: center; margin-bottom: 4px;">
                <span>Password</span>
                <a href="forgot_password.php" style="font-size: 13px; color: #247653; text-decoration: none; font-weight: 500;">
                    Forgot password?
                </a>
            </div>

            <input
                type="password"
                name="password"
                id="login-password"
                required
                <?= ($isLocked && $remaining > 0) ? 'disabled' : '' ?>
            >
        </label>

        <button 
            class="btn" 
            id="login-submit-btn" 
            style="padding: 12px; font-weight: bold; margin-top: 4px; <?= ($isLocked && $remaining > 0) ? 'opacity: 0.6; cursor: not-allowed;' : '' ?>"
            <?= ($isLocked && $remaining > 0) ? 'disabled' : '' ?>
        >
            Login
        </button>

    </form>

    <?php if ($isLocked && $remaining > 0): ?>
    <script>
    (function() {
        var timeLeft = <?= (int) $remaining ?>;
        var countdownEl = document.getElementById('lockout-countdown');
        var bannerEl = document.getElementById('lockout-banner');
        var submitBtn = document.getElementById('login-submit-btn');
        var emailInput = document.getElementById('login-email');
        var passInput = document.getElementById('login-password');

        var timer = setInterval(function() {
            timeLeft--;
            if (countdownEl) {
                countdownEl.textContent = timeLeft;
            }
            if (timeLeft <= 0) {
                clearInterval(timer);
                if (bannerEl) {
                    bannerEl.style.background = '#ecfdf5';
                    bannerEl.style.borderColor = '#6ee7b7';
                    bannerEl.style.color = '#065f46';
                    bannerEl.innerHTML = '<div style="display: flex; align-items: center; gap: 10px;"><i class="bi bi-check-circle-fill" style="font-size: 22px; color: #059669;"></i><div><strong>Lockout Expired</strong><div style="font-size: 13px;">You may now enter your credentials to login.</div></div></div>';
                }
                if (emailInput) emailInput.disabled = false;
                if (passInput) passInput.disabled = false;
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.style.opacity = '1';
                    submitBtn.style.cursor = 'pointer';
                }
            }
        }, 1000);
    })();
    </script>
    <?php endif; ?>

    <p style="margin-top: 20px; text-align: center;">
        Don't have an account?
        <a href="register.php" style="color: #247653; font-weight: bold;">Register here</a>
    </p>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
