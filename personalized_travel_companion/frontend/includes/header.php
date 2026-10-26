<?php
$pageTitle = $pageTitle ?? 'Personalized Travel Companion';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | Travel Companion</title>

    <?= $extraHead ?? '' ?>

    <link rel="stylesheet" href="<?= asset_url('assets/style.css') ?>">
</head>
<body>

    <a class="skip-link" href="#main-content">Skip to main content</a>

    <header class="topbar">
        <a class="brand" href="<?= app_url('index.php') ?>">
            <span class="brand-icon">TC</span>
            Travel Companion
        </a>

        <nav aria-label="Main navigation">
            <a href="<?= app_url('index.php') ?>">Home</a>

            <?php if (logged_in()): ?>
                <a href="<?= app_url('my_trips.php') ?>">My Trips</a>
                <a href="<?= app_url('profile.php') ?>">Profile</a>

                <?php if (is_admin()): ?>
                    <a href="<?= admin_url('index.php') ?>">Admin</a>
                <?php endif; ?>

                <a href="<?= app_url('logout.php') ?>">Logout</a>
            <?php else: ?>
                <?php
                $currentScript = basename($_SERVER['SCRIPT_NAME'] ?? '');
                $isLogin = ($currentScript === 'login.php');
                ?>
                <a class="<?= $isLogin ? 'nav-cta' : '' ?>" href="<?= app_url('login.php') ?>">Login</a>
                <a class="<?= !$isLogin ? 'nav-cta' : '' ?>" href="<?= app_url('register.php') ?>">Register</a>
            <?php endif; ?>
        </nav>
    </header>

    <main class="container" id="main-content" tabindex="-1">
        <?= flash_message() ?>
