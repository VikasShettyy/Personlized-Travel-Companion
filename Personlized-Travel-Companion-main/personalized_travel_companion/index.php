<?php
declare(strict_types=1);

/**
 * Root Router / Entry Point
 * Seamlessly redirects requests to the frontend application.
 */

$queryString = $_SERVER['QUERY_STRING'] ?? '';
$target = 'frontend/index.php' . ($queryString !== '' ? '?' . $queryString : '');

header("Location: $target");
exit;
