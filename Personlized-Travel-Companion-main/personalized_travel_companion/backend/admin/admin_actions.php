<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';

function clean_image(string $value): string
{
    $value = trim($value);

    if ($value === '') {
        return '';
    }

    if (mb_strlen($value) > 500) {
        throw new RuntimeException('Image path must be 500 characters or fewer.');
    }

    if (preg_match('/^[a-z][a-z0-9+.\-]*:/i', $value)
        && !preg_match('/^https?:\/\//i', $value)) {
        throw new RuntimeException('Image must be a relative path or an http(s) URL.');
    }

    return $value;
}

function clean_cost($value): float
{
    $cost = (float) $value;

    if ($cost < 0 || $cost > 99999999.99) {
        throw new RuntimeException('Cost must be between 0 and 99,999,999.99.');
    }

    return round($cost, 2);
}

function clean_text(string $value, string $label, int $max, bool $required = true): string
{
    $value = trim($value);

    if ($required && $value === '') {
        throw new RuntimeException("$label is required.");
    }

    if (mb_strlen($value) > $max) {
        throw new RuntimeException("$label must be $max characters or fewer.");
    }

    return $value;
}

/**
 * Handle admin POST actions.
 * Returns array: ['success' => bool, 'message' => string, 'tab' => string, 'error' => string]
 */
function handle_admin_action(PDO $pdo): ?array
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        return null;
    }

    verify_csrf();

    $validTabs = ['overview', 'destinations', 'categories', 'activities', 'enquiries', 'security'];
    $action = $_POST['action'] ?? '';
    $tab = in_array($_POST['tab'] ?? '', $validTabs, true) ? $_POST['tab'] : 'overview';
    $id = (int) ($_POST['id'] ?? 0);

    try {
        $msg = '';

        switch ($action) {
            case 'enquiry_reply':
                require_once dirname(__DIR__) . '/enquiries.php';
                $replyText = clean_text($_POST['admin_reply'] ?? '', 'Reply message', 5000);
                $res = reply_to_enquiry($pdo, $id, $replyText);
                if (!$res['success']) {
                    throw new RuntimeException($res['error']);
                }
                $msg = 'Reply sent to traveler and recorded in dashboard.';
                break;

            case 'category_add':
            case 'category_update':
                $name = clean_text($_POST['name'] ?? '', 'Category name', 80);

                if ($action === 'category_add') {
                    $pdo->prepare('INSERT INTO categories(name) VALUES(?)')->execute([$name]);
                    $msg = 'Category added.';
                } else {
                    $pdo->prepare('UPDATE categories SET name = ? WHERE id = ?')->execute([$name, $id]);
                    $msg = 'Category updated.';
                }
                break;

            case 'category_delete':
                $pdo->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
                $msg = 'Category deleted. Its destinations are now uncategorised.';
                break;

            case 'destination_add':
            case 'destination_update':
                $name = clean_text($_POST['name'] ?? '', 'Destination name', 120);
                $district = clean_text($_POST['district'] ?? '', 'District', 100);
                $cat = (int) ($_POST['category_id'] ?? 0);
                $desc = clean_text($_POST['description'] ?? '', 'Description', 5000, false);
                $cost = clean_cost($_POST['estimated_cost'] ?? 0);

                // Handle image upload from drive vs URL/existing
                $img = '';
                if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
                    $file = $_FILES['image_file'];
                    if ($file['size'] > 5 * 1024 * 1024) {
                        throw new RuntimeException('Image file from drive must be 5MB or less.');
                    }
                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                    $mime = $finfo->file($file['tmp_name']);
                    $allowedMimes = [
                        'image/jpeg' => 'jpg',
                        'image/png'  => 'png',
                        'image/webp' => 'webp',
                        'image/gif'  => 'gif',
                        'image/avif' => 'avif',
                    ];
                    if (!isset($allowedMimes[$mime])) {
                        throw new RuntimeException('Invalid image format. Allowed formats: JPG, PNG, WEBP, GIF, AVIF.');
                    }
                    $ext = $allowedMimes[$mime];
                    $baseName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', pathinfo($file['name'], PATHINFO_FILENAME));
                    $baseName = substr($baseName, 0, 30);
                    $newFileName = 'dest_' . time() . '_' . bin2hex(random_bytes(3)) . ($baseName ? '_' . $baseName : '') . '.' . $ext;

                    $targetDir = defined('UPLOAD_DIR') ? UPLOAD_DIR : (dirname(__DIR__, 2) . '/frontend/images/');
                    if (!is_dir($targetDir)) {
                        mkdir($targetDir, 0755, true);
                    }
                    if (!move_uploaded_file($file['tmp_name'], $targetDir . $newFileName)) {
                        throw new RuntimeException('Failed to save uploaded image. Check directory permissions.');
                    }
                    $img = 'images/' . $newFileName;
                } else {
                    $rawUrl = trim($_POST['image_url'] ?? '');
                    if ($rawUrl !== '') {
                        $img = clean_image($rawUrl);
                    } elseif ($action === 'destination_update' && $id > 0) {
                        $stmtImg = $pdo->prepare('SELECT image_url FROM destinations WHERE id = ?');
                        $stmtImg->execute([$id]);
                        $img = (string) ($stmtImg->fetchColumn() ?: '');
                    }
                }

                if ($action === 'destination_add') {
                    $pdo->prepare(
                        'INSERT INTO destinations (name, district, category_id, description, image_url, estimated_cost)
                         VALUES (?, ?, ?, ?, ?, ?)'
                    )->execute([$name, $district, $cat ?: null, $desc, $img, $cost]);
                    $msg = 'Destination added.';
                } else {
                    $pdo->prepare(
                        'UPDATE destinations
                         SET name = ?, district = ?, category_id = ?, description = ?, image_url = ?, estimated_cost = ?
                         WHERE id = ?'
                    )->execute([$name, $district, $cat ?: null, $desc, $img, $cost, $id]);
                    $msg = 'Destination updated.';
                }
                break;

            case 'destination_delete':
                $used = $pdo->prepare('SELECT COUNT(*) FROM trips WHERE destination_id = ?');
                $used->execute([$id]);

                if ((int) $used->fetchColumn() > 0) {
                    throw new RuntimeException('This destination is used by saved trips and cannot be deleted.');
                }

                $pdo->prepare('DELETE FROM destinations WHERE id = ?')->execute([$id]);
                $msg = 'Destination and its activities deleted.';
                break;

            case 'activity_add':
            case 'activity_update':
                $dest = (int) ($_POST['destination_id'] ?? 0);
                $name = clean_text($_POST['name'] ?? '', 'Activity name', 120);
                $desc = clean_text($_POST['description'] ?? '', 'Description', 5000, false);
                $cost = clean_cost($_POST['estimated_cost'] ?? 0);

                if ($dest <= 0) {
                    throw new RuntimeException('Please choose a destination.');
                }

                if ($action === 'activity_add') {
                    $pdo->prepare(
                        'INSERT INTO activities (destination_id, name, description, estimated_cost)
                         VALUES (?, ?, ?, ?)'
                    )->execute([$dest, $name, $desc, $cost]);
                    $msg = 'Activity added.';
                } else {
                    $pdo->prepare(
                        'UPDATE activities
                         SET destination_id = ?, name = ?, description = ?, estimated_cost = ?
                         WHERE id = ?'
                    )->execute([$dest, $name, $desc, $cost, $id]);
                    $msg = 'Activity updated.';
                }
                break;

            case 'activity_delete':
                $pdo->prepare('DELETE FROM activities WHERE id = ?')->execute([$id]);
                $msg = 'Activity deleted.';
                break;

            case 'admin_change_password':
                $currentPass = (string) ($_POST['current_password'] ?? '');
                $newPass = (string) ($_POST['new_password'] ?? '');
                $confirmPass = (string) ($_POST['confirm_password'] ?? '');

                if ($currentPass === '' || $newPass === '' || $confirmPass === '') {
                    throw new RuntimeException('All password fields are required.');
                }

                $adminId = (int) ($_SESSION['user']['id'] ?? 0);
                if ($adminId <= 0) {
                    throw new RuntimeException('Admin session not found. Please log in again.');
                }

                $userStmt = $pdo->prepare('SELECT id, password_hash FROM users WHERE id = ?');
                $userStmt->execute([$adminId]);
                $userData = $userStmt->fetch();

                if (!$userData || !password_verify($currentPass, $userData['password_hash'])) {
                    throw new RuntimeException('Current password does not match.');
                }

                if ($currentPass === $newPass) {
                    throw new RuntimeException('New password must be different from current password.');
                }

                if ($newPass !== $confirmPass) {
                    throw new RuntimeException('New password and confirmation do not match.');
                }

                if (mb_strlen($newPass) < 8) {
                    throw new RuntimeException('Password must be at least 8 characters long.');
                }

                if (mb_strlen($newPass) > 72) {
                    throw new RuntimeException('Password must not exceed 72 characters.');
                }

                if (!preg_match('/[A-Z]/', $newPass)) {
                    throw new RuntimeException('Password must include at least one uppercase letter (A-Z).');
                }

                if (!preg_match('/[a-z]/', $newPass)) {
                    throw new RuntimeException('Password must include at least one lowercase letter (a-z).');
                }

                if (!preg_match('/[0-9]/', $newPass)) {
                    throw new RuntimeException('Password must include at least one numeric digit (0-9).');
                }

                if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?~`]/', $newPass)) {
                    throw new RuntimeException('Password must include at least one special character.');
                }

                $newHash = password_hash($newPass, PASSWORD_DEFAULT);
                $updateStmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
                $updateStmt->execute([$newHash, $adminId]);
                session_regenerate_id(true);
                $msg = 'Password updated successfully.';
                break;

            default:
                throw new RuntimeException('Unknown action.');
        }

        $_SESSION['flash'] = $msg;
        return [
            'success' => true,
            'message' => $msg,
            'tab'     => $tab,
            'error'   => '',
        ];
    } catch (PDOException $e) {
        $code = (int) ($e->errorInfo[1] ?? 0);
        $error = match ($code) {
            1062 => 'That name already exists. Please use a different name.',
            1451 => 'This record is in use by other data and cannot be removed.',
            1452 => 'The selected category or destination no longer exists.',
            default => 'Could not save the changes. Please try again.',
        };
        $_SESSION['flash_error'] = $error;
        return [
            'success' => false,
            'message' => '',
            'tab'     => $tab,
            'error'   => $error,
        ];
    } catch (Throwable $e) {
        $error = $e->getMessage();
        $_SESSION['flash_error'] = $error;
        return [
            'success' => false,
            'message' => '',
            'tab'     => $tab,
            'error'   => $error,
        ];
    }
}
