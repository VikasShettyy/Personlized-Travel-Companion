<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/backend/auth.php';

if (logged_in()) {
    redirect('index.php');
}

$pageTitle = 'Create account';
$error = '';
$info = '';
$step = isset($_SESSION['pending_registration']) ? 'otp' : 'details';

// Handle Reset / Resend
if (isset($_GET['action'])) {
    if ($_GET['action'] === 'reset') {
        unset($_SESSION['pending_registration']);
        redirect('register.php');
    }
    if ($_GET['action'] === 'resend' && isset($_SESSION['pending_registration'])) {
        $p = $_SESSION['pending_registration'];
        $otp = generate_email_otp($pdo, $p['email'], 'register');
        send_app_email(
            $p['email'],
            'Your New Verification Code - Travel Companion',
            "<p>Your new verification code is: <strong>{$otp}</strong> (valid for 10 minutes).</p>"
        );
        $info = "A new verification code has been sent to {$p['email']}.";
        if (isset($otp)) {
            $info .= " (Test Code: <strong>{$otp}</strong>)";
        }
        $step = 'otp';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $postStep = $_POST['step'] ?? 'details';

    if ($postStep === 'details') {
        $name = trim($_POST['full_name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        $res = auth_request_registration_otp($pdo, $name, $email, $password, $confirm);
        if ($res['success']) {
            $step = 'otp';
            $info = "We sent a 6-digit verification code to <strong>" . e($email) . "</strong>. Please enter it below to activate your account.";
            if (!empty($res['otp'])) {
                $info .= " <br><span class='smalltext' style='color:#1a593c;'>[Local Test Code: <strong>" . e($res['otp']) . "</strong>]</span>";
            }
        } else {
            $error = $res['error'];
        }
    } elseif ($postStep === 'otp') {
        $otp = trim($_POST['otp'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ($_SESSION['pending_registration']['email'] ?? '')));

        $res = auth_verify_and_complete_registration($pdo, $email, $otp);
        if ($res['success']) {
            $_SESSION['flash'] = 'Welcome to Travel Companion! Your account has been verified and created.';
            redirect('index.php');
        } else {
            $step = 'otp';
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

    <?php if ($step === 'otp'): ?>
        <!-- ================= STEP 2: EMAIL OTP VERIFICATION ================= -->
        <div class="between" style="align-items: center; margin-bottom: 8px;">
            <h1 style="margin: 0; font-size: 24px;">Verify your email</h1>
            <span class="tag" style="background:#eaf6ef; color:#17683e; font-weight:bold;">Step 2 of 2</span>
        </div>

        <p class="muted" style="margin-bottom: 16px;">
            Please enter the 6-digit code sent to
            <strong><?= e($_SESSION['pending_registration']['email'] ?? 'your email') ?></strong>.
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

        <form class="stack" method="post" action="register.php">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="step" value="otp">
            <input type="hidden" name="email" value="<?= e($_SESSION['pending_registration']['email'] ?? '') ?>">

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

            <button class="btn" type="submit" style="padding: 12px; font-size: 15px; font-weight: bold;">
                <i class="bi bi-shield-check"></i> Verify & Complete Registration
            </button>
        </form>

        <div class="between" style="margin-top: 20px; font-size: 13px;">
            <a href="register.php?action=resend" style="color: #247653; font-weight: bold; text-decoration: none;">
                <i class="bi bi-arrow-repeat"></i> Resend code
            </a>
            <a href="register.php?action=reset" style="color: #68776e; text-decoration: none;">
                Change details / start over
            </a>
        </div>

    <?php else: ?>
        <!-- ================= STEP 1: REGISTRATION DETAILS & PASSWORD RULES ================= -->
        <h1>Create account</h1>

        <p class="muted">
            Start planning personalized trips with Karnataka Travel Companion.
        </p>

        <?php if ($error): ?>
            <div class="flash error" style="margin-bottom: 16px;">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form class="stack" method="post" action="register.php" id="registrationForm" novalidate>
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="step" value="details">

            <label>
                Full name
                <input
                    type="text"
                    name="full_name"
                    required
                    maxlength="100"
                    placeholder="e.g. Ramesh Kumar"
                    value="<?= e($_POST['full_name'] ?? '') ?>"
                >
            </label>

            <label>
                Email address
                <input
                    type="email"
                    name="email"
                    id="email_field"
                    required
                    placeholder="name@example.com"
                    value="<?= e($_POST['email'] ?? '') ?>"
                >
            </label>

            <label>
                Password
                <div class="password-box">
                    <input
                        type="password"
                        name="password"
                        id="reg_password"
                        minlength="8"
                        maxlength="72"
                        required
                        placeholder="Create strong password"
                        autocomplete="new-password"
                    >
                    <button type="button" class="password-toggle" data-for="reg_password" aria-label="Toggle password visibility">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </label>

            <!-- Real-time Password Strength Meter -->
            <div style="margin-top: -6px;">
                <div class="between" style="font-size: 12px; color: #68776e;">
                    <span>Password Strength:</span>
                    <strong id="strength_text">Too short</strong>
                </div>
                <div class="strength-track">
                    <div class="strength-fill" id="strength_fill"></div>
                </div>

                <!-- Validation Rules Checklist -->
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
                Confirm password
                <div class="password-box">
                    <input
                        type="password"
                        name="confirm_password"
                        id="reg_confirm"
                        minlength="8"
                        maxlength="72"
                        required
                        placeholder="Re-enter password"
                        autocomplete="new-password"
                    >
                    <button type="button" class="password-toggle" data-for="reg_confirm" aria-label="Toggle password visibility">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
                <div id="match_feedback" style="font-size: 12px; margin-top: 4px; display: none;"></div>
            </label>

            <button class="btn" type="submit" id="submit_register_btn" style="padding: 12px; font-weight: bold; margin-top: 8px;">
                Continue with Email OTP &rarr;
            </button>
        </form>

        <p style="margin-top: 18px; text-align: center;">
            Already registered?
            <a href="login.php" style="font-weight: bold; color: #247653;">Login here</a>
        </p>

        <script>
        document.addEventListener('DOMContentLoaded', () => {
            const passInput = document.getElementById('reg_password');
            const confInput = document.getElementById('reg_confirm');
            const strengthText = document.getElementById('strength_text');
            const strengthFill = document.getElementById('strength_fill');
            const matchMsg = document.getElementById('match_feedback');

            const ruleLen = document.getElementById('rule_len');
            const ruleUpper = document.getElementById('rule_upper');
            const ruleLower = document.getElementById('rule_lower');
            const ruleNum = document.getElementById('rule_num');
            const ruleSpec = document.getElementById('rule_spec');

            // Toggle show/hide password
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
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
