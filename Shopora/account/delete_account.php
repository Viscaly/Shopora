<?php
require_once '../database/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$user = getLoggedInUser();
if (!$user) {
    header('Location: /Shopora/account/login.php');
    exit;
}

$uid     = (int)$user['user_id'];
$error   = '';
$current = $conn->query("SELECT password, first_name, avatar FROM customers WHERE user_id = $uid")->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    $pass = $_POST['password'];

    if (!$pass) {
        $error = 'Εισάγετε τον κωδικό σας για επιβεβαίωση.';
    } elseif (!password_verify($pass, $current['password'])) {
        $error = 'Λάθος κωδικός. Ο λογαριασμός δεν διαγράφηκε.';
    } else {
        // 1. Διαγραφή αρχείων εικόνων προϊόντων
        $productImages = $conn->query("SELECT image_path FROM products WHERE owner_id = $uid");
        $productDir    = $_SERVER['DOCUMENT_ROOT'] . '/Shopora/images/products/';
        while ($img = $productImages->fetch_assoc()) {
            if (!empty($img['image_path'])) {
                $file = $productDir . $img['image_path'];
                if (file_exists($file)) unlink($file);
            }
        }

        // 2. Διαγραφή αρχείου avatar 
        if (!empty($current['avatar'])) {
            $avatarFile = $_SERVER['DOCUMENT_ROOT'] . '/Shopora/images/avatars/' . $current['avatar'];
            if (file_exists($avatarFile)) unlink($avatarFile);
        }

        // 3. διαγραφή όλων των δεδομένων του χρήστη από τη βάση
        $conn->query("DELETE FROM comments WHERE customer_id = $uid");
        $conn->query("DELETE FROM reviews  WHERE customer_id = $uid");
        $conn->query("DELETE FROM cart     WHERE user_id     = $uid");
        $conn->query("DELETE FROM products WHERE owner_id    = $uid");

        // 4. Διαγραφή του ίδιου του λογαριασμού
        $conn->query("DELETE FROM customers WHERE user_id = $uid");

        session_destroy();
        header('Location: /Shopora/index.php?deleted=1');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Διαγραφή Λογαριασμού — Shopora</title>
    <link rel="stylesheet" href="/Shopora/style.css">
</head>
<body>

<?php require_once '../navbar.php'; ?>

<main class="main-content">
<div class="page-wrapper">
<div style="max-width:480px;margin:48px auto;">

    <div style="margin-bottom:24px;">
        <a href="/Shopora/account/edit_account.php"
           style="font-size:0.85rem;color:var(--purple);text-decoration:none;">
            &larr; Πίσω στο Λογαριασμό
        </a>
    </div>

    <div class="auth-card" style="max-width:100%;border-color:#f48fb1;">

        <div style="text-align:center;margin-bottom:24px;">
            <div style="width:56px;height:56px;background:#fce4ec;border-radius:50%;
                        display:inline-flex;align-items:center;justify-content:center;
                        font-size:1.6rem;margin-bottom:12px;">⚠️</div>
            <h2 class="auth-title" style="color:#c62828;">Διαγραφή Λογαριασμού</h2>
            <p class="auth-sub">
                Γεια, <strong><?= htmlspecialchars($current['first_name']) ?></strong>.
                Αυτή η ενέργεια είναι <strong>μόνιμη και δεν αναιρείται</strong>.
            </p>
        </div>

        <div style="background:#fce4ec;border-radius:var(--radius-sm);padding:14px 16px;
                    margin-bottom:24px;font-size:0.875rem;color:#c62828;">
            Θα διαγραφούν μόνιμα:
            <ul style="margin:8px 0 0 16px;line-height:1.8;">
                <li>Το προφίλ και τα στοιχεία σύνδεσής σας</li>
                <li>Όλα τα προϊόντα που έχετε προσθέσει</li>
                <li>Το καλάθι αγορών σας</li>
                <li>Όλες οι αξιολογήσεις και τα σχόλιά σας</li>
            </ul>
        </div>

        <?php if ($error): ?>
            <div class="msg msg-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" novalidate>
            <div class="form-group">
                <label>Εισάγετε τον κωδικό σας για επιβεβαίωση *</label>
                <input type="password" name="password"
                       placeholder="Ο τρέχων κωδικός σας" required autofocus>
            </div>
            <div style="display:flex;gap:10px;margin-top:8px;">
                <a href="/Shopora/account/edit_account.php"
                   class="btn-ghost" style="flex:1;justify-content:center;display:flex;padding:12px;">
                    Ακύρωση
                </a>
                <button type="submit" name="confirm_delete"
                        style="flex:1;padding:12px;background:#c62828;color:white;border:none;
                               border-radius:var(--radius-sm);font-family:inherit;font-size:0.9rem;
                               font-weight:700;cursor:pointer;transition:background 0.2s;"
                        onmouseover="this.style.background='#b71c1c'"
                        onmouseout="this.style.background='#c62828'"
                        onclick="return confirm('Είστε απολύτως σίγουροι; Αυτό δεν αναιρείται.')">
                    Ναι, Διαγραφή Λογαριασμού
                </button>
            </div>
        </form>

    </div>
</div>
</div>
</main>

<footer class="footer">
    <strong>Shopora</strong> &copy; <?= date('Y') ?> &mdash; All rights reserved.
</footer>

</body>
</html>
