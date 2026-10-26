<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/backend/auth.php';

auth_logout();
redirect('index.php');
