<?php
declare(strict_types=1);

/**
 * Shared helper functions for frontend and backend.
 */

if (!function_exists('e')) {
    function e(?string $value): string {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('logged_in')) {
    function logged_in(): bool {
        return isset($_SESSION['user']);
    }
}

if (!function_exists('is_admin')) {
    function is_admin(): bool {
        return logged_in() && (($_SESSION['user']['role'] ?? '') === 'admin');
    }
}

if (!function_exists('app_url')) {
    function app_url(string $path = ''): string {
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        $inAdmin = strpos($script, '/admin') !== false;
        if ($inAdmin) {
            return $path === '' ? '../index.php' : '../' . ltrim($path, '/');
        }
        return $path === '' ? 'index.php' : ltrim($path, '/');
    }
}

if (!function_exists('admin_url')) {
    function admin_url(string $path = ''): string {
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        $inAdmin = strpos($script, '/admin') !== false;
        if ($inAdmin) {
            return $path === '' ? 'index.php' : ltrim($path, '/');
        }
        return 'admin/' . ($path === '' ? 'index.php' : ltrim($path, '/'));
    }
}

if (!function_exists('asset_url')) {
    function asset_url(string $path): string {
        if ($path === '' || preg_match('/^https?:\/\//i', $path)) {
            return $path;
        }
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        $inAdmin = strpos($script, '/admin') !== false;
        $prefix = $inAdmin ? '../' : '';
        return $prefix . ltrim($path, '/');
    }
}

if (!function_exists('require_login')) {
    function require_login(): void {
        if (!logged_in()) {
            $_SESSION['flash'] = 'Please log in or register to plan and save a trip.';
            $target = app_url('login.php');
            header("Location: $target");
            exit;
        }
    }
}

if (!function_exists('require_admin')) {
    function require_admin(): void {
        if (!is_admin()) {
            http_response_code(403);
            exit('Access denied. Admin login required.');
        }
    }
}

if (!function_exists('flash_message')) {
    function flash_message(): string {
        if (empty($_SESSION['flash'])) {
            return '';
        }
        $message = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return '<div class="flash" role="status">' . e($message) . '</div>';
    }
}

if (!function_exists('set_flash')) {
    function set_flash(string $message): void {
        $_SESSION['flash'] = $message;
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string {
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf'];
    }
}

if (!function_exists('verify_csrf')) {
    function verify_csrf(): void {
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            $sent = $_POST['csrf'] ?? '';
            if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
                http_response_code(419);
                exit('Invalid request token. Refresh and try again.');
            }
        }
    }
}

if (!function_exists('redirect')) {
    function redirect(string $to): never {
        header("Location: $to");
        exit;
    }
}
