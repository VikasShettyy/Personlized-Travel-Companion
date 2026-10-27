<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/mailer.php';

/**
 * Validate password strength against security criteria.
 */
function validate_password_rules(string $password, string $confirm = ''): ?string
{
    if (strlen($password) < 8) {
        return 'Password must be at least 8 characters long.';
    }
    if (strlen($password) > 72) {
        return 'Password must not exceed 72 characters.';
    }
    if (!preg_match('/[A-Z]/', $password)) {
        return 'Password must include at least one uppercase letter (A-Z).';
    }
    if (!preg_match('/[a-z]/', $password)) {
        return 'Password must include at least one lowercase letter (a-z).';
    }
    if (!preg_match('/[0-9]/', $password)) {
        return 'Password must include at least one numeric digit (0-9).';
    }
    if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?~`]/', $password)) {
        return 'Password must include at least one special character (!@#$%^&*...).';
    }
    if ($confirm !== '' && $password !== $confirm) {
        return 'Passwords do not match.';
    }
    return null;
}

/**
 * Authenticate user with email and password, with brute-force lockout protection (3 attempts -> 20s lock).
 */
function auth_login(PDO $pdo, string $email, string $password): array
{
    $lockoutUntil = (int) ($_SESSION['login_lockout_until'] ?? 0);
    $now = time();
    if ($lockoutUntil > $now) {
        $remaining = $lockoutUntil - $now;
        return [
            'success'   => false,
            'error'     => "Too many failed attempts. You are temporarily locked out for {$remaining} more second" . ($remaining === 1 ? '' : 's') . ".",
            'locked'    => true,
            'remaining' => $remaining,
        ];
    }

    $email = strtolower(trim($email));

    $stmt = $pdo->prepare('SELECT id, full_name, email, password_hash, role, profile_picture FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        // Reset brute-force lockout counters
        unset($_SESSION['login_attempts'], $_SESSION['login_lockout_until']);

        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id'              => (int) $user['id'],
            'full_name'       => $user['full_name'],
            'email'           => $user['email'],
            'role'            => $user['role'],
            'profile_picture' => $user['profile_picture'] ?? '',
        ];
        return ['success' => true, 'error' => '', 'locked' => false, 'remaining' => 0];
    }

    // Login failed: track attempts
    $attempts = (int) ($_SESSION['login_attempts'] ?? 0) + 1;
    $_SESSION['login_attempts'] = $attempts;

    if ($attempts >= 3) {
        $lockDuration = 20;
        $_SESSION['login_lockout_until'] = time() + $lockDuration;
        $_SESSION['login_attempts'] = 0;
        return [
            'success'   => false,
            'error'     => "3 incorrect attempts detected! You are blocked from logging in for the next {$lockDuration} seconds.",
            'locked'    => true,
            'remaining' => $lockDuration,
        ];
    }

    $remainingAttempts = 3 - $attempts;
    return [
        'success'   => false,
        'error'     => "Incorrect email or password. Attempt {$attempts} of 3. ({$remainingAttempts} attempt" . ($remainingAttempts === 1 ? '' : 's') . " remaining before a 20-second lockout).",
        'locked'    => false,
        'remaining' => 0,
    ];
}

/**
 * Initiate Registration: validate inputs & password strength, then send verification OTP to email.
 */
function auth_request_registration_otp(PDO $pdo, string $name, string $email, string $password, string $confirm): array
{
    $name = trim($name);
    $email = strtolower(trim($email));

    if ($name === '') {
        return ['success' => false, 'error' => 'Full name is required.'];
    }
   if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    return ['success' => false, 'error' => 'Please enter a valid email address.'];
}

if (!str_ends_with($email, '@gmail.com')) {
    return [
        'success' => false,
        'error' => 'Only Gmail addresses ending in @gmail.com are allowed.'
    ];
}

    // Password validation
    $pwdError = validate_password_rules($password, $confirm);
    if ($pwdError !== null) {
        return ['success' => false, 'error' => $pwdError];
    }

    // Check if email already registered
    $check = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $check->execute([$email]);
    if ($check->fetch()) {
        return ['success' => false, 'error' => 'That email address is already registered. Please log in or use Forgot Password.'];
    }

    // Generate 6-digit OTP
    $otp = generate_email_otp($pdo, $email, 'register');

    // Send email with OTP
    $subject = 'Verify Your Email Address - Travel Companion';
    $body = "
        <div style='font-family: Arial, sans-serif; line-height: 1.6; color: #23352b;'>
            <h2 style='color: #247653;'>Welcome to Personalized Travel Companion!</h2>
            <p>Hello <strong>" . htmlspecialchars($name) . "</strong>,</p>
            <p>Thank you for registering. Please enter the following 6-digit verification code to complete your registration:</p>
            <div style='background: #eef7f2; border: 1px dashed #247653; border-radius: 8px; padding: 16px; margin: 20px 0; text-align: center;'>
                <span style='font-size: 32px; font-weight: bold; letter-spacing: 6px; color: #173b2a;'>{$otp}</span>
                <p style='margin: 8px 0 0; font-size: 12px; color: #64756b;'>This code expires in 10 minutes.</p>
            </div>
            <p>If you did not request this registration, please disregard this email.</p>
        </div>
    ";
    send_app_email($email, $subject, $body);

    // Store pending registration data temporarily in session
    $_SESSION['pending_registration'] = [
        'full_name'     => $name,
        'email'         => $email,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'created_time'  => time(),
    ];

    return [
        'success'  => true,
        'error'    => '',
        'otp'      => $otp, // available for local display simulation
        'email'    => $email,
    ];
}

/**
 * Verify OTP and finalize account creation.
 */
function auth_verify_and_complete_registration(PDO $pdo, string $email, string $otp): array
{
    $email = strtolower(trim($email));
    $otp = trim($otp);

    $pending = $_SESSION['pending_registration'] ?? null;
    if (!$pending || $pending['email'] !== $email) {
        return ['success' => false, 'error' => 'Registration session expired. Please fill in the registration form again.'];
    }

    if (!verify_email_otp($pdo, $email, $otp, 'register')) {
        return ['success' => false, 'error' => 'Invalid or expired verification code. Please check and try again.'];
    }

    try {
        $stmt = $pdo->prepare('INSERT INTO users (full_name, email, password_hash) VALUES (?, ?, ?)');
        $stmt->execute([
            $pending['full_name'],
            $pending['email'],
            $pending['password_hash'],
        ]);

        $newId = (int) $pdo->lastInsertId();
        unset($_SESSION['pending_registration']);

        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id'        => $newId,
            'full_name' => $pending['full_name'],
            'email'     => $pending['email'],
            'role'      => 'user',
        ];

        return ['success' => true, 'error' => ''];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => 'That email is already registered.'];
    }
}

/**
 * Initiate Forgot Password: check email and send 6-digit OTP.
 */
function auth_request_password_reset_otp(PDO $pdo, string $email): array
{
    $email = strtolower(trim($email));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'error' => 'Please enter a valid email address.'];
    }

    $stmt = $pdo->prepare('SELECT id, full_name FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        return ['success' => false, 'error' => 'No account found with that email address.'];
    }

    $otp = generate_email_otp($pdo, $email, 'forgot_password');

    $subject = 'Password Reset Code - Travel Companion';
    $body = "
        <div style='font-family: Arial, sans-serif; line-height: 1.6; color: #23352b;'>
            <h2 style='color: #247653;'>Password Reset Request</h2>
            <p>Hello <strong>" . htmlspecialchars($user['full_name']) . "</strong>,</p>
            <p>We received a request to reset your password. Use the following 6-digit OTP code to set a new password:</p>
            <div style='background: #eef7f2; border: 1px dashed #247653; border-radius: 8px; padding: 16px; margin: 20px 0; text-align: center;'>
                <span style='font-size: 32px; font-weight: bold; letter-spacing: 6px; color: #173b2a;'>{$otp}</span>
                <p style='margin: 8px 0 0; font-size: 12px; color: #64756b;'>This code expires in 10 minutes.</p>
            </div>
            <p>If you did not request a password reset, you can safely ignore this email.</p>
        </div>
    ";
    send_app_email($email, $subject, $body);

    return [
        'success' => true,
        'error'   => '',
        'otp'     => $otp,
        'email'   => $email,
    ];
}

/**
 * Verify OTP for forgot password without setting password yet (Step 2).
 */
function auth_verify_password_reset_otp(PDO $pdo, string $email, string $otp): array
{
    $email = strtolower(trim($email));
    $otp = trim($otp);

    if ($otp === '' || strlen($otp) !== 6 || !ctype_digit($otp)) {
        return ['success' => false, 'error' => 'Please enter a valid 6-digit verification code.'];
    }

    if (!verify_email_otp($pdo, $email, $otp, 'forgot_password')) {
        return ['success' => false, 'error' => 'Invalid or expired OTP code. Please enter the correct code or request a new one.'];
    }

    $_SESSION['password_reset_verified_email'] = $email;
    return ['success' => true, 'error' => ''];
}

/**
 * Set new password after OTP has been verified (Step 3).
 */
function auth_set_new_password(PDO $pdo, string $email, string $newPassword, string $confirmPassword): array
{
    $email = strtolower(trim($email));

    // Ensure OTP was verified for this email in current session
    $verifiedEmail = $_SESSION['password_reset_verified_email'] ?? '';
    if ($verifiedEmail === '' || strtolower($verifiedEmail) !== $email) {
        return ['success' => false, 'error' => 'OTP verification is required before you can set a new password.'];
    }

    $pwdError = validate_password_rules($newPassword, $confirmPassword);
    if ($pwdError !== null) {
        return ['success' => false, 'error' => $pwdError];
    }

    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE email = ?');
    $stmt->execute([$newHash, $email]);

    unset($_SESSION['password_reset_verified_email'], $_SESSION['reset_email']);

    return ['success' => true, 'error' => ''];
}

/**
 * Reset password using the verified OTP (all-in-one fallback).
 */
function auth_reset_password_with_otp(PDO $pdo, string $email, string $otp, string $newPassword, string $confirmPassword): array
{
    $email = strtolower(trim($email));
    $otp = trim($otp);

    $pwdError = validate_password_rules($newPassword, $confirmPassword);
    if ($pwdError !== null) {
        return ['success' => false, 'error' => $pwdError];
    }

    if (!verify_email_otp($pdo, $email, $otp, 'forgot_password')) {
        return ['success' => false, 'error' => 'Invalid or expired OTP code. Please request a new code.'];
    }

    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE email = ?');
    $stmt->execute([$newHash, $email]);

    return ['success' => true, 'error' => ''];
}

/**
 * Terminate user session and log out.
 */
function auth_logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $p['path'],
            $p['domain'],
            $p['secure'],
            $p['httponly']
        );
    }
    session_destroy();
}

/**
 * Update user profile details including optional profile picture.
 */
function auth_update_profile(PDO $pdo, int $userId, string $fullName, ?array $avatarFile = null): array
{
    $fullName = trim($fullName);
    if ($fullName === '') {
        return ['success' => false, 'error' => 'Full name cannot be empty.'];
    }

    $newAvatarRelPath = null;

    if ($avatarFile && isset($avatarFile['error']) && $avatarFile['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($avatarFile['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'File upload failed with error code ' . $avatarFile['error']];
        }

        // Limit size: 5MB
        if ($avatarFile['size'] > 5 * 1024 * 1024) {
            return ['success' => false, 'error' => 'Profile picture size must not exceed 5MB.'];
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($avatarFile['tmp_name']);
        $allowedMimes = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif',
        ];

        if (!array_key_exists($mime, $allowedMimes)) {
            return ['success' => false, 'error' => 'Only JPG, PNG, WEBP, or GIF image files are allowed.'];
        }

        $ext = $allowedMimes[$mime];
        $uploadDir = dirname(__DIR__) . '/frontend/images/avatars';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0777, true);
        }

        $filename = 'avatar_' . $userId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $targetFile = $uploadDir . '/' . $filename;

        $saved = is_uploaded_file($avatarFile['tmp_name'])
            ? move_uploaded_file($avatarFile['tmp_name'], $targetFile)
            : copy($avatarFile['tmp_name'], $targetFile);

        if (!$saved) {
            return ['success' => false, 'error' => 'Failed to save uploaded profile picture. Check folder permissions.'];
        }

        $newAvatarRelPath = 'images/avatars/' . $filename;
    }

    if ($newAvatarRelPath !== null) {
        $stmt = $pdo->prepare('UPDATE users SET full_name = ?, profile_picture = ? WHERE id = ?');
        $stmt->execute([$fullName, $newAvatarRelPath, $userId]);
        if (isset($_SESSION['user'])) {
            $_SESSION['user']['profile_picture'] = $newAvatarRelPath;
        }
    } else {
        $stmt = $pdo->prepare('UPDATE users SET full_name = ? WHERE id = ?');
        $stmt->execute([$fullName, $userId]);
    }

    if (isset($_SESSION['user'])) {
        $_SESSION['user']['full_name'] = $fullName;
    }

    return ['success' => true, 'error' => '', 'profile_picture' => $newAvatarRelPath];
}
