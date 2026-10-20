<?php
declare(strict_types=1);
session_start();

$host = 'localhost';
$db   = 'travel_companion';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    exit('Database connection failed. Check config.php and import database.sql in phpMyAdmin.');
}

function e(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}
function logged_in(): bool { return isset($_SESSION['user']); }
function is_admin(): bool { return logged_in() && ($_SESSION['user']['role'] ?? '') === 'admin'; }
function require_login(): void {
    if (!logged_in()) {
        $_SESSION['flash'] = 'Please log in or register to plan and save a trip.';
        header('Location: login.php'); exit;
    }
}
function require_admin(): void {
    if (!is_admin()) { http_response_code(403); exit('Access denied. Admin login required.'); }
}
function flash_message(): string {
    if (empty($_SESSION['flash'])) return '';
    $message = $_SESSION['flash']; unset($_SESSION['flash']);
    return '<div class="flash">'.e($message).'</div>';
}
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function verify_csrf(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $sent = $_POST['csrf'] ?? '';
        if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
            http_response_code(419); exit('Invalid request token. Refresh and try again.');
        }
    }
}
function redirect(string $to): never { header("Location: $to"); exit; }
