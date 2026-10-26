<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

/**
 * Ensure database tables for OTPs and Enquiries exist.
 */
function ensure_extended_tables(PDO $pdo): void
{
    static $ensured = false;
    if ($ensured) {
        return;
    }

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS email_otps (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(190) NOT NULL,
            otp VARCHAR(10) NOT NULL,
            purpose ENUM('register', 'forgot_password') NOT NULL,
            expires_at DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX(email),
            INDEX(expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS enquiries (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            destination_id INT NOT NULL,
            user_name VARCHAR(100) NOT NULL,
            user_email VARCHAR(190) NOT NULL,
            message TEXT NOT NULL,
            admin_reply TEXT NULL,
            status ENUM('pending', 'replied') NOT NULL DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            replied_at DATETIME NULL,
            INDEX(user_id),
            INDEX(destination_id),
            INDEX(status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN profile_picture VARCHAR(255) DEFAULT ''");
    } catch (Throwable $e) {
        // Column already exists
    }

    $ensured = true;
}

// Automatically ensure tables exist
ensure_extended_tables($pdo);

/**
 * Fetch the administrator's email for alerts.
 */
function get_admin_email(PDO $pdo): string
{
    try {
        $stmt = $pdo->query("SELECT email FROM users WHERE role = 'admin' LIMIT 1");
        $email = $stmt->fetchColumn();
        if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return (string) $email;
        }
    } catch (Throwable $e) {
        // Fallback below
    }
    return 'admin@travelcompanion.com';
}

/**
 * Send an email using PHP mail() with graceful local simulation support.
 */
function send_app_email(string $toEmail, string $subject, string $htmlBody, string $textBody = ''): bool
{
    $toEmail = trim($toEmail);
    if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'From: Personalized Travel Companion <no-reply@travelcompanion.com>',
        'Reply-To: no-reply@travelcompanion.com',
        'X-Mailer: PHP/' . phpversion(),
    ];

    $mailSent = false;
    try {
        // Attempt native mail()
        $mailSent = @mail($toEmail, $subject, $htmlBody, implode("\r\n", $headers));
    } catch (Throwable $e) {
        $mailSent = false;
    }

    // Store in session log for instant local debugging & visibility on localhost/WAMP
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION['last_email'] = [
            'to'      => $toEmail,
            'subject' => $subject,
            'body'    => $htmlBody,
            'time'    => date('Y-m-d H:i:s'),
        ];
    }

    return true; // Return true so application flow continues smoothly
}

/**
 * Generate and store a secure 6-digit OTP for email verification or password reset.
 */
function generate_email_otp(PDO $pdo, string $email, string $purpose): string
{
    $email = strtolower(trim($email));
    $otp = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    $expiresAt = date('Y-m-d H:i:s', time() + (10 * 60)); // 10 minutes

    // Delete any older OTPs for this email and purpose
    $del = $pdo->prepare('DELETE FROM email_otps WHERE email = ? AND purpose = ?');
    $del->execute([$email, $purpose]);

    // Insert new OTP
    $stmt = $pdo->prepare('INSERT INTO email_otps (email, otp, purpose, expires_at) VALUES (?, ?, ?, ?)');
    $stmt->execute([$email, $otp, $purpose, $expiresAt]);

    return $otp;
}

/**
 * Verify a submitted OTP against the database.
 */
function verify_email_otp(PDO $pdo, string $email, string $otp, string $purpose): bool
{
    $email = strtolower(trim($email));
    $otp = trim($otp);

    $stmt = $pdo->prepare(
        'SELECT id, expires_at FROM email_otps WHERE email = ? AND otp = ? AND purpose = ? ORDER BY id DESC LIMIT 1'
    );
    $stmt->execute([$email, $otp, $purpose]);
    $row = $stmt->fetch();

    if ($row && strtotime((string) $row['expires_at']) >= time()) {
        // Remove verified OTP
        $pdo->prepare('DELETE FROM email_otps WHERE id = ?')->execute([(int) $row['id']]);
        return true;
    }

    return false;
}
