<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/backend/auth.php';

if (logged_in()) {
    redirect('index.php');
}

$pageTitle = 'Forgot Password';
$error = '';
$info = '';

// Determine active step based on session state
$emailInSession = $_SESSION['reset_email'] ?? '';
$verifiedEmail = $_SESSION['password_reset_verified_email'] ?? '';

if (!empty($verifiedEmail)) {
    $step = 'new_password';
} elseif (!empty($emailInSession)) {
    $step = 'verify';
} else {
    $step = 'request';
}

// Handle action query params (restart, resend)
if (isset($_GET['action'])) {
    if ($_GET['action'] === 'restart') {
        unset($_SESSION['reset_email'], $_SESSION['password_reset_verified_email']);
        redirect('forgot_password.php');
    }
    if ($_GET['action'] === 'resend' && !empty($_SESSION['reset_email'])) {
        $email = $_SESSION['reset_email'];
        $res = auth_request_password_reset_otp($pdo, $email);
        $info = "A new verification code has been sent to {$email}.";
        if (!empty($res['otp'])) {
            $info .= " <br><span class='smalltext' style='color:#1a593c;'>[Local Test Code: <strong>" . e($res['otp']) . "</strong>]</span>";
        }
        $step = 'verify';
    }
}

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $postStep = $_POST['step'] ?? 'request';

    if ($postStep === 'request') {
        $email = strtolower(trim($_POST['email'] ?? ''));
        $res = auth_request_password_reset_otp($pdo, $email);
        if ($res['success']) {
            $_SESSION['reset_email'] = $email;
            unset($_SESSION['password_reset_verified_email']);
            $step = 'verify';
            $info = "We sent a 6-digit verification code to <strong>" . e($email) . "</strong>. Please enter it below to verify.";
            if (!empty($res['otp'])) {
                $info .= " <br><span class='smalltext' style='color:#1a593c;'>[Local Test Code: <strong>" . e($res['otp']) . "</strong>]</span>";
            }
        } else {
            $error = $res['error'];
            $step = 'request';
        }
    } elseif ($postStep === 'verify') {
        $email = strtolower(trim($_POST['email'] ?? ($_SESSION['reset_email'] ?? '')));
        $otp = trim($_POST['otp'] ?? '');

        $res = auth_verify_password_reset_otp($pdo, $email, $otp);
        if ($res['success']) {
            $_SESSION['password_reset_verified_email'] = $email;
            $step = 'new_password';
            $info = "Code verified successfully! Now create your new password.";
        } else {
            $step = 'verify';
            $error = $res['error'];
        }
    } elseif ($postStep === 'new_password') {
        $email = strtolower(trim($_POST['email'] ?? ($_SESSION['password_reset_verified_email'] ?? '')));
        $newPass = $_POST['new_password'] ?? '';
        $confirmPass = $_POST['confirm_password'] ?? '';

        $res = auth_set_new_password($pdo, $email, $newPass, $confirmPass);
        if ($res['success']) {
            $_SESSION['flash'] = 'Your password has been reset successfully! You can now log in with your new password.';
            redirect('login.php');
        } else {
            $step = 'new_password';
            $error = $res['error'];
        }
    }
}

$extraHead = '
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
.password-box { position: relative; }
.password-box input { padding-right: 42px; }
.password-toggle {
    position: absolute; right: 10px; top: 50%; transform: translateY(-50%);
    background: none; border: none; color: #64756b; cursor: pointer; padding: 4px 6px; font-size: 16px;
}
.rule-item { display: flex; align-items: center; gap: 8px; font-size: 12px; margin-bottom: 4px; color: #6c757d; }
.rule-item.valid { color: #198754; font-weight: 500; }
.rule-item.valid i { color: #198754; }
.rule-item i { font-size: 12px; }
.strength-track { height: 6px; background: #e9ecef; border-radius: 4px; overflow: hidden; margin-top: 6px; margin-bottom: 12px; }
.strength-fill { height: 100%; width: 0; transition: width 0.3s, background-color 0.3s; }
.otp-input {
    font-size: 26px !important; letter-spacing: 8px; text-align: center; font-weight: bold; padding: 12px !important;
}
</style>
';

require_once __DIR__ . '/includes/header.php';
?>

<div class="card form-card" style="max-width: 500px; margin: 30px auto;">

    <?php if ($step === 'new_password'): ?>
        <!-- ================= STEP 3: SET NEW PASSWORD (ONLY AFTER OTP VERIFIED) ================= -->
        <div class="between" style="align-items: center; margin-bottom: 8px;">
            <h1 style="margin: 0; font-size: 24px;">Set New Password</h1>
            <span class="tag" style="background:#eaf6ef; color:#17683e; font-weight:bold;">Step 3 of 3</span>
        </div>

        <p class="muted" style="margin-bottom: 16px;">
            OTP code verified! Set a strong new password for <strong><?= e($_SESSION['password_reset_verified_email'] ?? '') ?></strong>.
        </p>

        <?php if ($info): ?>
            <div class="flash good" style="background: #eaf6ef; color: #185c37; padding: 12px; border-radius: 8px; margin-bottom: 16px;">
                <i class="bi bi-shield-check" style="font-size: 18px; margin-right: 6px;"></i> <?= $info ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="flash error" style="margin-bottom: 16px;">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form class="stack" method="post" action="forgot_password.php">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="step" value="new_password">
            <input type="hidden" name="email" value="<?= e($_SESSION['password_reset_verified_email'] ?? '') ?>">

            <label>
                New password
                <div class="password-box">
                    <input
                        type="password"
                        name="new_password"
                        id="reset_password"
                        minlength="8"
                        maxlength="72"
                        required
                        placeholder="Create strong new password"
                        autocomplete="new-password"
                        autofocus
                    >
                    <button type="button" class="password-toggle" data-for="reset_password" aria-label="Toggle password visibility">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </label>

            <!-- Password Strength & Rules Checklist -->
            <div style="margin-top: -6px;">
                <div class="between" style="font-size: 12px; color: #68776e;">
                    <span>Password Strength:</span>
                    <strong id="strength_text">Too short</strong>
                </div>
                <div class="strength-track">
                    <div class="strength-fill" id="strength_fill"></div>
                </div>

                <div id="password_checklist" style="background: #fafcfb; border: 1px solid #e1e8e3; border-radius: 8px; padding: 10px 12px; margin-bottom: 12px;">
                    <div class="rule-item" id="rule_len">
                        <i class="bi bi-circle"></i> <span>At least 8 characters</span>
                    </div>
                    <div class="rule-item" id="rule_upper">
                        <i class="bi bi-circle"></i> <span>At least one uppercase letter (A-Z)</span>
                    </div>
                    <div class="rule-item" id="rule_lower">
                        <i class="bi bi-circle"></i> <span>At least one lowercase letter (a-z)</span>
                    </div>
                    <div class="rule-item" id="rule_num">
                        <i class="bi bi-circle"></i> <span>At least one numeric digit (0-9)</span>
                    </div>
                    <div class="rule-item" id="rule_spec">
                        <i class="bi bi-circle"></i> <span>At least one special symbol (!@#$%^&*...)</span>
                    </div>
                </div>
            </div>

            <label>
                Confirm new password
                <div class="password-box">
                    <input
                        type="password"
                        name="confirm_password"
                        id="reset_confirm"
                        minlength="8"
                        maxlength="72"
                        required
                        placeholder="Re-enter new password"
                        autocomplete="new-password"
                    >
                    <button type="button" class="password-toggle" data-for="reset_confirm" aria-label="Toggle password visibility">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
                <div id="match_feedback" style="font-size: 12px; margin-top: 4px; display: none;"></div>
            </label>

            <button class="btn" type="submit" style="padding: 12px; font-weight: bold; margin-top: 8px;">
                <i class="bi bi-check2-circle"></i> Save New Password & Log In
            </button>
        </form>

        <script>
        document.addEventListener('DOMContentLoaded', () => {
            const passInput = document.getElementById('reset_password');
            const confInput = document.getElementById('reset_confirm');
            const strengthText = document.getElementById('strength_text');
            const strengthFill = document.getElementById('strength_fill');
            const matchMsg = document.getElementById('match_feedback');

            const ruleLen = document.getElementById('rule_len');
            const ruleUpper = document.getElementById('rule_upper');
            const ruleLower = document.getElementById('rule_lower');
            const ruleNum = document.getElementById('rule_num');
            const ruleSpec = document.getElementById('rule_spec');

            document.querySelectorAll('.password-toggle').forEach(btn => {
                btn.addEventListener('click', () => {
                    const id = btn.dataset.for;
                    const input = document.getElementById(id);
                    if (!input) return;
                    const isPass = input.type === 'password';
                    input.type = isPass ? 'text' : 'password';
                    btn.querySelector('i').className = isPass ? 'bi bi-eye-slash' : 'bi bi-eye';
                });
            });

            function updateRule(el, valid) {
                if (valid) {
                    el.className = 'rule-item valid';
                    el.querySelector('i').className = 'bi bi-check-circle-fill';
                } else {
                    el.className = 'rule-item';
                    el.querySelector('i').className = 'bi bi-circle';
                }
            }

            function evaluatePassword() {
                const val = passInput.value;
                const lenOk = val.length >= 8 && val.length <= 72;
                const upperOk = /[A-Z]/.test(val);
                const lowerOk = /[a-z]/.test(val);
                const numOk = /[0-9]/.test(val);
                const specOk = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?~`]/.test(val);

                updateRule(ruleLen, lenOk);
                updateRule(ruleUpper, upperOk);
                updateRule(ruleLower, lowerOk);
                updateRule(ruleNum, numOk);
                updateRule(ruleSpec, specOk);

                const score = [lenOk, upperOk, lowerOk, numOk, specOk].filter(Boolean).length;

                if (val.length === 0) {
                    strengthFill.style.width = '0%';
                    strengthText.textContent = 'Too short';
                    strengthText.style.color = '#68776e';
                } else if (score <= 2) {
                    strengthFill.style.width = '30%';
                    strengthFill.style.backgroundColor = '#dc3545';
                    strengthText.textContent = 'Weak';
                    strengthText.style.color = '#dc3545';
                } else if (score <= 4) {
                    strengthFill.style.width = '65%';
                    strengthFill.style.backgroundColor = '#ffc107';
                    strengthText.textContent = 'Moderate';
                    strengthText.style.color = '#d39e00';
                } else {
                    strengthFill.style.width = '100%';
                    strengthFill.style.backgroundColor = '#198754';
                    strengthText.textContent = 'Strong';
                    strengthText.style.color = '#198754';
                }

                checkMatch();
            }

            function checkMatch() {
                if (!confInput.value) {
                    matchMsg.style.display = 'none';
                    return;
                }
                matchMsg.style.display = 'block';
                if (confInput.value === passInput.value) {
                    matchMsg.style.color = '#198754';
                    matchMsg.innerHTML = '<i class="bi bi-check2"></i> Passwords match';
                } else {
                    matchMsg.style.color = '#dc3545';
                    matchMsg.innerHTML = '<i class="bi bi-x-circle"></i> Passwords do not match';
                }
            }

            passInput?.addEventListener('input', evaluatePassword);
            confInput?.addEventListener('input', checkMatch);
        });
        </script>

    <?php elseif ($step === 'verify'): ?>
        <!-- ================= STEP 2: VERIFY OTP CODE ONLY ================= -->
        <div class="between" style="align-items: center; margin-bottom: 8px;">
            <h1 style="margin: 0; font-size: 24px;">Verify Code</h1>
            <span class="tag" style="background:#eaf6ef; color:#17683e; font-weight:bold;">Step 2 of 3</span>
        </div>

        <p class="muted" style="margin-bottom: 16px;">
            Enter the 6-digit OTP code sent to <strong><?= e($_SESSION['reset_email'] ?? '') ?></strong>.
        </p>

        <?php if ($info): ?>
            <div class="flash good" style="background: #eaf6ef; color: #185c37; padding: 12px; border-radius: 8px; margin-bottom: 16px;">
                <?= $info ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="flash error" style="margin-bottom: 16px;">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form class="stack" method="post" action="forgot_password.php">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="step" value="verify">
            <input type="hidden" name="email" value="<?= e($_SESSION['reset_email'] ?? '') ?>">

            <label>
                Enter 6-digit OTP code
                <input
                    type="text"
                    name="otp"
                    class="otp-input"
                    maxlength="6"
                    required
                    pattern="[0-9]{6}"
                    placeholder="••••••"
                    autofocus
                    autocomplete="one-time-code"
                >
            </label>

            <button class="btn" type="submit" style="padding: 12px; font-weight: bold; margin-top: 8px;">
                <i class="bi bi-shield-check"></i> Verify Code & Continue &rarr;
            </button>
        </form>

        <div class="between" style="margin-top: 20px; font-size: 13px;">
            <a href="forgot_password.php?action=resend" style="color: #247653; font-weight: bold; text-decoration: none;">
                <i class="bi bi-arrow-repeat"></i> Resend code
            </a>
            <a href="forgot_password.php?action=restart" style="color: #68776e; text-decoration: none;">
                Change email / start over
            </a>
        </div>

    <?php else: ?>
        <!-- ================= STEP 1: REQUEST OTP VIA EMAIL ================= -->
        <div class="between" style="align-items: center; margin-bottom: 8px;">
            <h1 style="margin: 0; font-size: 24px;">Forgot Password</h1>
            <span class="tag" style="background:#f3f4f6; color:#4b5563; font-weight:bold;">Step 1 of 3</span>
        </div>

        <p class="muted" style="margin-bottom: 16px;">
            Enter your registered email address and we'll send you a 6-digit verification code.
        </p>

        <?php if ($error): ?>
            <div class="flash error" style="margin-bottom: 16px;">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form class="stack" method="post" action="forgot_password.php">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="step" value="request">

            <label>
                Email address
                <input
                    type="email"
                    name="email"
                    required
                    placeholder="name@example.com"
                    value="<?= e($_POST['email'] ?? '') ?>"
                    autofocus
                >
            </label>

            <button class="btn" type="submit" style="padding: 12px; font-weight: bold; margin-top: 8px;">
                Send Verification Code &rarr;
            </button>
        </form>

        <p style="margin-top: 18px; text-align: center;">
            Remember your password?
            <a href="login.php" style="font-weight: bold; color: #247653;">Back to Login</a>
        </p>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
