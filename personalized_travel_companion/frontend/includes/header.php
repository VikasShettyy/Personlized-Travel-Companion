<?php
$pageTitle = $pageTitle ?? 'Personalized Travel Companion';
$currentScript = basename($_SERVER['SCRIPT_NAME'] ?? '');
$isLogin = ($currentScript === 'login.php');
$isRegister = ($currentScript === 'register.php');
$inAdmin = strpos($_SERVER['SCRIPT_NAME'] ?? '', '/admin') !== false;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="/frontend/favicon.ico?v=3" type="image/x-icon">
    <meta name="description" content="Personalized Travel Companion - Explore Karnataka destinations, plan day-wise custom itineraries, choose local activities, and manage travel budgets effortlessly.">
    <meta name="keywords" content="Karnataka tourism, travel companion, Coorg, Hampi, Gokarna, Mysore, itinerary planner">
    <meta property="og:title" content="<?= e($pageTitle) ?> | Travel Companion">
    <meta property="og:description" content="Explore Karnataka destinations and plan custom day-wise trips within your budget.">
    <meta property="og:type" content="website">
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

        <!-- Desktop Navigation -->
        <nav class="desktop-nav" aria-label="Main navigation">
            <a href="<?= app_url('index.php') ?>">Home</a>

            <?php if (logged_in()): ?>
                <a href="<?= app_url('my_trips.php') ?>">My Trips</a>
                <a href="<?= app_url('profile.php') ?>">Profile</a>

                <?php if (is_admin()): ?>
                    <a href="<?= admin_url('index.php') ?>">Admin</a>
                <?php endif; ?>

                <a href="<?= app_url('logout.php') ?>">Logout</a>
            <?php else: ?>
                <a class="<?= $isLogin ? 'nav-cta' : '' ?>" href="<?= app_url('login.php') ?>">Login</a>
                <a class="<?= !$isLogin ? 'nav-cta' : '' ?>" href="<?= app_url('register.php') ?>">Register</a>
            <?php endif; ?>
        </nav>

        <!-- Mobile Hamburger Button -->
        <button type="button" class="mobile-menu-toggle" id="mobileMenuBtn" aria-label="Open navigation menu" aria-expanded="false" aria-controls="mobileSidebar">
            <span class="hamburger-bar"></span>
            <span class="hamburger-bar"></span>
            <span class="hamburger-bar"></span>
        </button>
    </header>

    <!-- Mobile Sidebar Navigation Drawer -->
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>
    <aside class="mobile-sidebar" id="mobileSidebar" aria-label="Mobile navigation drawer" aria-hidden="true">
        <div class="sidebar-header">
            <a class="brand" href="<?= app_url('index.php') ?>">
                <span class="brand-icon">TC</span>
                Travel Companion
            </a>
            <button type="button" class="sidebar-close-btn" id="sidebarCloseBtn" aria-label="Close navigation menu">&times;</button>
        </div>

        <nav class="mobile-sidebar-nav" aria-label="Mobile navigation">
            <?php if (logged_in()): ?>
                <div class="sidebar-user-card">
                    <span class="sidebar-user-role">Welcome back</span>
                    <strong class="sidebar-user-name"><?= e($_SESSION['user']['full_name'] ?? 'Traveler') ?></strong>
                </div>
            <?php endif; ?>

            <a href="<?= app_url('index.php') ?>" class="sidebar-link <?= $currentScript === 'index.php' ? 'active' : '' ?>">
                <span>Explore Destinations</span>
            </a>

            <?php if (logged_in()): ?>
                <a href="<?= app_url('my_trips.php') ?>" class="sidebar-link <?= $currentScript === 'my_trips.php' ? 'active' : '' ?>">
                    <span>My Trips</span>
                </a>
                <a href="<?= app_url('profile.php') ?>" class="sidebar-link <?= $currentScript === 'profile.php' ? 'active' : '' ?>">
                    <span>Traveler Profile</span>
                </a>

                <?php if (is_admin()): ?>
                    <a href="<?= admin_url('index.php') ?>" class="sidebar-link <?= $inAdmin ? 'active' : '' ?>">
                        <span>Admin Dashboard</span>
                    </a>
                <?php endif; ?>

                <a href="<?= app_url('logout.php') ?>" class="sidebar-link sidebar-logout">
                    <span>Logout</span>
                </a>
            <?php else: ?>
                <a href="<?= app_url('login.php') ?>" class="sidebar-link <?= $isLogin ? 'active' : '' ?>">
                    <span>Login</span>
                </a>
                <a href="<?= app_url('register.php') ?>" class="sidebar-btn-cta">
                    Register Free
                </a>
            <?php endif; ?>
        </nav>
    </aside>

    <main class="container" id="main-content" tabindex="-1">
        <?= flash_message() ?>
