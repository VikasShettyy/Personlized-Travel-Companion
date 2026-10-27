<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = 'sql306.infinityfree.com';
$db   = 'if0_43270553_database';
$user = 'if0_43270553';
$pass = 'FfKT1RbWJ6gQ50o';
$charset = 'utf8mb4';

// $host = 'localhost';
// $db   = 'travel_companion';
// $user = 'root';
// $pass = '';
// $charset = 'utf8mb4';


$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    exit('Database connection failed. Check backend/config.php and import database.sql in phpMyAdmin.');
}

if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', dirname(__DIR__));
    define('BACKEND_DIR', __DIR__);
    define('FRONTEND_DIR', ROOT_DIR . '/frontend');
    define('UPLOAD_DIR', FRONTEND_DIR . '/images/');
}

require_once __DIR__ . '/helpers.php';
