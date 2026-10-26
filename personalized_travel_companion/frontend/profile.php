<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/backend/auth.php';

require_login();

$pageTitle = 'My Profile';
$message = '';
$error = '';
$userId = (int) $_SESSION['user']['id'];

// Refresh user record from database to make sure session is up-to-date
$stmt = $pdo->prepare('SELECT id, full_name, email, role, profile_picture FROM users WHERE id = ?');
$stmt->execute([$userId]);
$currentUser = $stmt->fetch();
if ($currentUser) {
    $_SESSION['user']['profile_picture'] = $currentUser['profile_picture'] ?? '';
    $_SESSION['user']['full_name'] = $currentUser['full_name'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $name = trim($_POST['full_name'] ?? '');
    $avatarFile = $_FILES['profile_picture'] ?? null;

    $res = auth_update_profile($pdo, $userId, $name, $avatarFile);
    if ($res['success']) {
        $message = 'Your profile and profile picture have been updated successfully!';
        // Refresh local variable
        if (!empty($res['profile_picture'])) {
            $currentUser['profile_picture'] = $res['profile_picture'];
        }
        $currentUser['full_name'] = $name;
    } else {
        $error = $res['error'];
    }
}

$avatarPath = $currentUser['profile_picture'] ?? '';
$initial = strtoupper(mb_substr($currentUser['full_name'] ?? 'U', 0, 1, 'UTF-8'));

$extraHead = '
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
.profile-card-header {
    display: flex;
    align-items: center;
    gap: 20px;
    margin-bottom: 24px;
    padding-bottom: 20px;
    border-bottom: 1px solid #e5e7eb;
}
.avatar-preview-wrapper {
    position: relative;
    width: 96px;
    height: 96px;
    border-radius: 50%;
    overflow: hidden;
    background: #247653;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    flex-shrink: 0;
    border: 3px solid #ffffff;
    outline: 2px solid #247653;
}
.avatar-preview-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}
.avatar-initial {
    color: #ffffff;
    font-size: 38px;
    font-weight: 700;
    line-height: 1;
}
.avatar-upload-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    background: #f3f4f6;
    color: #374151;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}
.avatar-upload-btn:hover {
    background: #e5e7eb;
    color: #111827;
}
</style>
';

require_once __DIR__ . '/includes/header.php';
?>

<div class="card form-card" style="max-width: 600px; margin: 30px auto;">

    <h1>My Profile</h1>
    <p class="muted">Manage your personal account details and profile photo.</p>

    <?php if ($message): ?>
        <div class="flash good" style="background: #eaf6ef; color: #185c37; padding: 12px; border-radius: 8px; margin-bottom: 20px;">
            <i class="bi bi-check-circle-fill" style="margin-right: 6px;"></i> <?= e($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="flash error" style="margin-bottom: 20px;">
            <i class="bi bi-exclamation-triangle-fill" style="margin-right: 6px;"></i> <?= e($error) ?>
        </div>
    <?php endif; ?>

    <form class="stack" method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">

        <div class="profile-card-header">
            <div class="avatar-preview-wrapper" id="avatar_wrapper">
                <?php if (!empty($avatarPath)): ?>
                    <img src="<?= e(asset_url($avatarPath)) ?>" alt="Profile Picture" class="avatar-preview-img" id="avatar_img">
                <?php else: ?>
                    <span class="avatar-initial" id="avatar_initial"><?= e($initial) ?></span>
                <?php endif; ?>
            </div>

            <div style="flex-grow: 1;">
                <label for="profile_picture_input" class="avatar-upload-btn">
                    <i class="bi bi-camera"></i> Change Profile Photo
                </label>
                <input
                    type="file"
                    id="profile_picture_input"
                    name="profile_picture"
                    accept="image/png, image/jpeg, image/jpg, image/webp, image/gif"
                    style="display: none;"
                >
                <div class="smalltext" style="color: #6b7280; margin-top: 6px;">
                    Supports JPG, PNG, WEBP, or GIF (max 5MB).
                </div>
                <div id="file_selected_name" style="font-size: 12px; color: #047857; margin-top: 4px; font-weight: 500;"></div>
            </div>
        </div>

        <label>
            Full name
            <input
                type="text"
                name="full_name"
                required
                value="<?= e($currentUser['full_name'] ?? '') ?>"
                placeholder="Your full name"
            >
        </label>

        <label>
            Email address
            <input
                type="email"
                value="<?= e($currentUser['email'] ?? '') ?>"
                disabled
                style="background: #f9fafb; cursor: not-allowed; color: #6b7280;"
            >
            <span class="smalltext" style="color: #6b7280; margin-top: 4px;">Email address cannot be modified directly.</span>
        </label>

        <div class="between" style="align-items: center; margin-top: 10px;">
            <button class="btn" type="submit" style="padding: 12px 24px; font-weight: bold;">
                <i class="bi bi-check2"></i> Save Changes
            </button>
            <a href="my_trips.php" style="color: #6b7280; text-decoration: none; font-size: 14px;">
                Back to Dashboard
            </a>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const fileInput = document.getElementById('profile_picture_input');
    const avatarWrapper = document.getElementById('avatar_wrapper');
    const fileNameDisplay = document.getElementById('file_selected_name');

    if (!fileInput) return;

    fileInput.addEventListener('change', function() {
        const file = this.files && this.files[0];
        if (!file) return;

        fileNameDisplay.textContent = 'Selected: ' + file.name;

        // Preview image in avatar box
        const reader = new FileReader();
        reader.onload = function(e) {
            avatarWrapper.innerHTML = '<img src="' + e.target.result + '" alt="Profile Preview" class="avatar-preview-img">';
        };
        reader.readAsDataURL(file);
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
