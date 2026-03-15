<?php
require_once '../database/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$user = getLoggedInUser();
if (!$user) {
    header('Location: /Shopora/account/login.php');
    exit;
}

$uid     = (int)$user['user_id'];
$success = '';
$error   = '';

$current = $conn->query("SELECT * FROM customers WHERE user_id = $uid")->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first  = trim($conn->real_escape_string($_POST['first_name']));
    $last   = trim($conn->real_escape_string($_POST['last_name']));
    $phone  = trim($conn->real_escape_string($_POST['phone'] ?? ''));
    $email  = trim($conn->real_escape_string($_POST['email']));
    $pass   = $_POST['new_password'];
    $pass2  = $_POST['confirm_password'];
    $currPass = $_POST['current_password'];

    if (!$first || !$last || !$email) {
        $error = 'First name, last name and email are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (!password_verify($currPass, $current['password'])) {
        $error = 'Current password is incorrect.';
    } else {
        $emailCheck = $conn->query("SELECT user_id FROM customers WHERE email='$email' AND user_id != $uid");
        if ($emailCheck->num_rows > 0) {
            $error = 'That email is already used by another account.';
        } else {
            // Handle avatar upload
            $avatarFilename = $current['avatar'] ?? '';
            if (!empty($_FILES['avatar']['name'])) {
                $allowed  = ['image/jpeg','image/png','image/webp','image/gif'];
                $mimeType = mime_content_type($_FILES['avatar']['tmp_name']);
                $maxSize  = 3 * 1024 * 1024; // 3MB

                if (!in_array($mimeType, $allowed)) {
                    $error = 'Avatar must be JPG, PNG, WEBP or GIF.';
                } elseif ($_FILES['avatar']['size'] > $maxSize) {
                    $error = 'Avatar must be under 3MB.';
                } else {
                    $ext       = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
                    $newFile   = 'avatar_' . $uid . '_' . time() . '.' . $ext;
                    $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/Shopora/images/avatars/';

                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

                    if (move_uploaded_file($_FILES['avatar']['tmp_name'], $uploadDir . $newFile)) {
                        // Delete old avatar file from disk
                        if (!empty($current['avatar'])) {
                            $old = $uploadDir . $current['avatar'];
                            if (file_exists($old)) unlink($old);
                        }
                        $avatarFilename = $conn->real_escape_string($newFile);
                    } else {
                        $error = 'Failed to upload avatar. Check folder permissions.';
                    }
                }
            }

            if (!$error) {
                if ($pass !== '') {
                    if (strlen($pass) < 6) {
                        $error = 'New password must be at least 6 characters.';
                    } elseif ($pass !== $pass2) {
                        $error = 'New passwords do not match.';
                    } else {
                        $hashed = password_hash($pass, PASSWORD_DEFAULT);
                        $conn->query("
                            UPDATE customers
                            SET first_name='$first', last_name='$last', email='$email',
                                phone='$phone', password='$hashed', avatar='$avatarFilename'
                            WHERE user_id = $uid
                        ");
                    }
                } else {
                    $conn->query("
                        UPDATE customers
                        SET first_name='$first', last_name='$last', email='$email',
                            phone='$phone', avatar='$avatarFilename'
                        WHERE user_id = $uid
                    ");
                }

                if (!$error) {
                    // Update session
                    $_SESSION['first_name'] = $first;
                    $_SESSION['last_name']  = $last;
                    $_SESSION['email']      = $email;
                    $_SESSION['avatar']     = $avatarFilename;

                    $current = $conn->query("SELECT * FROM customers WHERE user_id = $uid")->fetch_assoc();
                    $success = 'Your account has been updated successfully.';
                }
            }
        }
    }
}

$avatarUrl = !empty($current['avatar'])
    ? '/Shopora/images/avatars/' . htmlspecialchars($current['avatar'])
    : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Account — Shopora</title>
    <link rel="stylesheet" href="/Shopora/style.css">
    <style>
        .avatar-upload-box {
            width: 110px; height: 110px;
            border-radius: 50%;
            border: 3px dashed var(--border);
            overflow: hidden; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            background: var(--purple-ultra);
            transition: border-color 0.2s;
            position: relative; flex-shrink: 0;
        }
        .avatar-upload-box:hover { border-color: var(--purple); }
        .avatar-upload-box img {
            width: 100%; height: 100%; object-fit: cover;
        }
        .avatar-upload-box .avatar-initials {
            font-size: 2rem; font-weight: 700;
            color: var(--purple);
        }
        .avatar-upload-box .avatar-overlay {
            position: absolute; inset: 0;
            background: rgba(106,27,154,0.55);
            display: flex; align-items: center; justify-content: center;
            opacity: 0; transition: opacity 0.2s; border-radius: 50%;
            color: white; font-size: 0.75rem; font-weight: 700; text-align: center;
        }
        .avatar-upload-box:hover .avatar-overlay { opacity: 1; }
        #avatarInput { display: none; }
    </style>
</head>
<body>

<?php require_once '../navbar.php'; ?>

<main class="main-content">
<div class="page-wrapper">
<div style="max-width:560px;margin:48px auto;">

    <div style="margin-bottom:24px;">
        <a href="/Shopora/index.php"
           style="font-size:0.85rem;color:var(--purple);text-decoration:none;">
            &larr; Back to Home
        </a>
    </div>

    <div class="auth-card" style="max-width:100%;">

        <!-- Avatar + name header -->
        <div style="display:flex;align-items:center;gap:20px;margin-bottom:28px;
                    padding-bottom:20px;border-bottom:1px solid var(--border);">

            <!-- Clickable avatar circle -->
            <div class="avatar-upload-box" onclick="document.getElementById('avatarInput').click()">
                <?php if ($avatarUrl): ?>
                    <img id="avatarPreview" src="<?= $avatarUrl ?>" alt="Avatar">
                <?php else: ?>
                    <span class="avatar-initials" id="avatarInitials">
                        <?= strtoupper(substr($current['first_name'], 0, 1)) ?>
                    </span>
                    <img id="avatarPreview" src="" alt="Avatar" style="display:none;">
                <?php endif; ?>
                <div class="avatar-overlay">📷<br>Change</div>
            </div>
            <input type="file" id="avatarInput" accept="image/*">

            <div>
                <div style="font-weight:700;font-size:1.05rem;">
                    <?= htmlspecialchars($current['first_name'] . ' ' . $current['last_name']) ?>
                </div>
                <div style="font-size:0.82rem;color:var(--text-light);">
                    <?= htmlspecialchars($current['email']) ?>
                </div>
                <div style="font-size:0.78rem;color:var(--text-light);margin-top:4px;">
                    Click the photo to change avatar
                </div>
            </div>
        </div>

        <h2 class="auth-title">Edit Account</h2>
        <p class="auth-sub">Current password is always required to save changes.</p>

        <?php if ($error):   ?><div class="msg msg-error"><?=   htmlspecialchars($error)   ?></div><?php endif; ?>
        <?php if ($success): ?><div class="msg msg-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

        <form method="POST" enctype="multipart/form-data" novalidate>
            <!-- Hidden file input connected to the avatar circle above -->
            <input type="file" name="avatar" id="avatarFormInput" accept="image/*" style="display:none;">

            <div class="form-row">
                <div class="form-group">
                    <label>First Name *</label>
                    <input type="text" name="first_name"
                           value="<?= htmlspecialchars($_POST['first_name'] ?? $current['first_name']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Last Name *</label>
                    <input type="text" name="last_name"
                           value="<?= htmlspecialchars($_POST['last_name'] ?? $current['last_name']) ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="email"
                       value="<?= htmlspecialchars($_POST['email'] ?? $current['email']) ?>" required>
            </div>

            <div class="form-group">
                <label>Phone</label>
                <input type="tel" name="phone"
                       value="<?= htmlspecialchars($_POST['phone'] ?? ($current['phone'] ?? '')) ?>"
                       placeholder="69XXXXXXXX">
            </div>

            <div style="border-top:1px solid var(--border);margin:20px 0;"></div>
            <p style="font-size:0.82rem;color:var(--text-light);margin-bottom:16px;">
                Leave new password blank to keep your current one.
            </p>

            <div class="form-row">
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="new_password" placeholder="At least 6 characters">
                </div>
                <div class="form-group">
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_password" placeholder="Repeat new password">
                </div>
            </div>

            <div style="border-top:1px solid var(--border);margin:20px 0;"></div>

            <div class="form-group">
                <label>Current Password * (required to save)</label>
                <input type="password" name="current_password"
                       placeholder="Enter your current password" required>
            </div>

            <div style="display:flex;gap:10px;margin-top:4px;">
                <button type="submit" class="btn-submit" style="flex:1;">Save Changes</button>
                <a href="/Shopora/account/delete_account.php"
                   style="padding:13px 16px;border-radius:var(--radius-sm);border:1.5px solid #f48fb1;
                          color:#c62828;font-weight:700;font-size:0.85rem;text-decoration:none;
                          display:flex;align-items:center;transition:background 0.2s;"
                   onmouseover="this.style.background='#fce4ec'"
                   onmouseout="this.style.background='transparent'">
                    Delete Account
                </a>
            </div>
        </form>

    </div>
</div>
</div>
</main>

<footer class="footer">
    <strong>Shopora</strong> &copy; <?= date('Y') ?> &mdash; All rights reserved.
</footer>

<script>
document.getElementById('avatarInput').addEventListener('change', function () {
    const file = this.files[0];
    if (!file) return;

    const dt = new DataTransfer();
    dt.items.add(file);
    document.getElementById('avatarFormInput').files = dt.files;

    const reader = new FileReader();
    reader.onload = function (e) {
        const preview  = document.getElementById('avatarPreview');
        const initials = document.getElementById('avatarInitials');
        preview.src          = e.target.result;
        preview.style.display = 'block';
        if (initials) initials.style.display = 'none';
    };
    reader.readAsDataURL(file);
});
</script>

</body>
</html>
