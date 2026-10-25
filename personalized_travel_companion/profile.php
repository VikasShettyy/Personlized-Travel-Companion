<?php
require 'config.php';
require_login();
$pageTitle = 'My Profile';
$message = '';
$error = '';
$redirect = false;

$userId = $_SESSION['user']['id'];

// Get the current profile photo
$s = $pdo->prepare('SELECT profile_photo FROM users WHERE id = ?');
$s->execute([$userId]);
$profilePhoto = $s->fetchColumn() ?: '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $name = trim($_POST['full_name'] ?? '');
    $newPhoto = $profilePhoto;

    if ($name === '') {
        $error = 'Please enter your full name.';
    } else {
        // Check whether a new photo was selected
        if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] !== UPLOAD_ERR_NO_FILE) {
            $file = $_FILES['profile_photo'];

            if ($file['error'] !== UPLOAD_ERR_OK) {
                $error = 'Photo upload failed.';
            } elseif ($file['size'] > 3 * 1024 * 1024) {
                $error = 'Photo must be smaller than 3 MB.';
            } else {
                $imageInfo = getimagesize($file['tmp_name']);

                $allowedTypes = [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/webp' => 'webp'
                ];

                if ($imageInfo === false ||!isset($allowedTypes[$imageInfo['mime']])) {
                        $error = 'Please select a JPG, PNG, or WebP image.';
                } else {
                    $folder = __DIR__ . '/uploads/profile/';

                    if (!is_dir($folder)) {
                        mkdir($folder, 0755, true);
                    }

                    $filename = bin2hex(random_bytes(16)) . '.' .
                        $allowedTypes[$imageInfo['mime']];

                    if (move_uploaded_file(
                        $file['tmp_name'],
                        $folder . $filename
                    )) {
                        $newPhoto = 'uploads/profile/' . $filename;
                    } else {
                        $error = 'Could not save the photo.';
                    }
                }
            }
        }

        // Update profile details
        if ($error === '') {
            $s = $pdo->prepare(
                'UPDATE users
                 SET full_name = ?, profile_photo = ?
                 WHERE id = ?'
            );

            $s->execute([
                $name,
                $newPhoto ?: null,
                $userId
            ]);

            $_SESSION['user']['full_name'] = $name;
            $profilePhoto = $newPhoto;

            $redirect = true;
        }
    }
}

require 'header.php';
?>

<div class="card form-card">
    <h1>My Profile</h1>

    <?php if ($error !== ''): ?>
        <div class="flash">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <form
        class="stack"
        method="post"
        enctype="multipart/form-data"
    >
        <input
            type="hidden"
            name="csrf"
            value="<?= csrf_token() ?>"
        >

        <label>
            Profile photo

            <?php if ($profilePhoto !== ''): ?>
                <img
                    src="<?= e($profilePhoto) ?>"
                    alt="Your profile photo"
                    width="100"
                    height="100"
                    style="display:block; object-fit:cover; border-radius:50%; margin:10px 0;"
                >
            <?php endif; ?>

            <input
                type="file"
                name="profile_photo"
                accept="image/jpeg,image/png,image/webp"
            >
        </label>

        <label>
            Full name
            <input
                name="full_name"
                required
                value="<?= e($_SESSION['user']['full_name']) ?>"
            >
        </label>

        <label>
            Email
            <input
                value="<?= e($_SESSION['user']['email']) ?>"
                disabled
            >
        </label>

        <button class="btn" type="submit">
            Update profile
        </button>
    </form>
</div>

<?php if ($redirect): ?>
    <script>
        alert('Profile updated successfully!');
        window.location.href = 'my_trips.php';
    </script>
<?php endif; ?>

<?php require 'footer.php'; ?>
