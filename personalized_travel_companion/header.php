<?php
$pageTitle = $pageTitle ?? 'Personalized Travel Companion';
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>
        <?= e($pageTitle) ?> | Travel Companion
    </title>

    <link rel="stylesheet" href="assets/style.css">
</head>

<body>

    <header class="topbar">

        <a class="brand" href="index.php">
            <span class="brand-icon">TC</span>
            Travel Companion
        </a>

        <nav>
            <a href="index.php">Home</a>

            <?php if (logged_in()): ?>

                <a href="my_trips.php">My Trips</a>

                <?php if (is_admin()): ?>
                    <a href="admin.php">Admin</a>
                <?php endif; ?>

                <a href="logout.php">Logout</a>

            <?php else: ?>

                <a href="login.php">Login</a>

                <a class="nav-cta" href="register.php">
                    Register
                </a>

            <?php endif; ?>
        </nav>

    </header>

    <main class="container">

        <?= flash_message() ?>