<?php
require_once '../database/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$user = getLoggedInUser();
if (!$user) {
    header('Location: /Shopora/account/login.php');
    exit;
}

$error      = '';
$categories = ['Technology', 'Games', 'Fashion', 'Books'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uid      = (int)$user['user_id'];
    $name     = trim($conn->real_escape_string($_POST['name']));
    $desc     = trim($conn->real_escape_string($_POST['description'] ?? ''));
    $price    = (float)$_POST['price'];
    $stock    = (int)$_POST['stock'];
    $category = in_array($_POST['category'], $categories) ? $_POST['category'] : '';
    $imagePath = '';

    if (!$name || !$category || $price <= 0) {
        $error = 'Name, category and a valid price are required.';
    } else {
        if (!empty($_FILES['image']['name'])) {
            $allowed  = ['image/jpeg','image/png','image/webp','image/gif'];
            $mimeType = mime_content_type($_FILES['image']['tmp_name']);
            $maxSize  = 5 * 1024 * 1024;

            if (!in_array($mimeType, $allowed)) {
                $error = 'Only JPG, PNG, WEBP or GIF images are allowed.';
            } elseif ($_FILES['image']['size'] > $maxSize) {
                $error = 'Image must be under 5MB.';
            } else {
                $filename  = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $_FILES['image']['name']);
                $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/Shopora/images/products/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename)) {
                    $imagePath = $conn->real_escape_string($filename);
                } else {
                    $error = 'Failed to upload image. Check folder permissions.';
                }
            }
        }

        if (!$error) {
            // Αποθήκευση owner_id ώστε το προϊόν να συνδέεται με τον χρήστη
            $conn->query("
                INSERT INTO products (owner_id, name, description, price, stock, category, image_path)
                VALUES ($uid, '$name', '$desc', $price, $stock, '$category', '$imagePath')
            ");
            $newId = $conn->insert_id;
            header('Location: /Shopora/products/add_product.php?success=' . $newId);
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product — Shopora</title>
    <link rel="stylesheet" href="/Shopora/style.css">
    <style>
        .image-preview-box {
            width:100%; aspect-ratio:4/3;
            border:2px dashed var(--border); border-radius:var(--radius);
            display:flex; align-items:center; justify-content:center;
            overflow:hidden; cursor:pointer; background:var(--purple-ultra);
            position:relative; transition:border-color 0.2s;
        }
        .image-preview-box:hover { border-color:var(--purple); }
        .image-preview-box img   { width:100%; height:100%; object-fit:cover; display:none; }
        .placeholder             { text-align:center; color:var(--text-light); font-size:0.875rem; }
        #imageInput              { display:none; }
    </style>
</head>
<body>

<?php require_once '../navbar.php'; ?>

<main class="main-content">
<div class="page-wrapper">
<div style="max-width:640px;margin:48px auto;">

    <div style="margin-bottom:24px;">
        <a href="/Shopora/products/products.php"
           style="font-size:0.85rem;color:var(--purple);text-decoration:none;">
            &larr; Πίσω στα Προϊόντα
        </a>
    </div>

    <div class="auth-card" style="max-width:100%;">
        <h2 class="auth-title">Προσθήκη Νέου Προϊόντος</h2>
        <p class="auth-sub">Συμπληρώστε τα στοιχεία. Το προϊόν θα είναι ορατό σε όλους.</p>

        <?php if ($error): ?>
            <div class="msg msg-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['success'])): ?>
            <div class="msg msg-success">
                Το προϊόν προστέθηκε επιτυχώς!
                <a href="/Shopora/products/product.php?id=<?= (int)$_GET['success'] ?>"
                   style="color:var(--purple);font-weight:700;">Προβολή</a>
                &nbsp;|&nbsp;
                <a href="/Shopora/products/products.php"
                   style="color:var(--purple);font-weight:700;">Όλα τα Προϊόντα</a>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" novalidate>

            <div class="form-group">
                <label>Εικόνα Προϊόντος</label>
                <div class="image-preview-box" onclick="document.getElementById('imageInput').click()">
                    <img id="imagePreview" src="" alt="Preview">
                    <div class="placeholder" id="imagePlaceholder">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none"
                             stroke="var(--purple)" stroke-width="1.5"
                             stroke-linecap="round" stroke-linejoin="round" style="margin-bottom:8px;">
                            <rect x="3" y="3" width="18" height="18" rx="2"/>
                            <circle cx="8.5" cy="8.5" r="1.5"/>
                            <polyline points="21 15 16 10 5 21"/>
                        </svg>
                        <p>Κλικ για μεταφόρτωση</p>
                        <p style="font-size:0.75rem;">JPG, PNG, WEBP ή GIF &mdash; max 5MB</p>
                    </div>
                </div>
                <input type="file" id="imageInput" name="image" accept="image/*">
            </div>

            <div class="form-group">
                <label>Όνομα Προϊόντος *</label>
                <input type="text" name="name"
                       value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                       placeholder="π.χ. Ασύρματα Ακουστικά" required>
            </div>

            <div class="form-group">
                <label>Περιγραφή</label>
                <textarea name="description" rows="3"
                    style="width:100%;padding:11px 14px;border:1.5px solid var(--border);
                           border-radius:var(--radius-sm);font-family:inherit;font-size:0.9rem;
                           resize:vertical;outline:none;background:var(--off-white);"
                    placeholder="Περιγράψτε το προϊόν..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Τιμή (€) *</label>
                    <div style="position:relative;">
                        <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);
                                     color:var(--text-mid);font-weight:600;">€</span>
                        <input type="number" name="price" step="0.01" min="0.01"
                               value="<?= htmlspecialchars($_POST['price'] ?? '') ?>"
                               placeholder="0.00" required style="padding-left:28px;">
                    </div>
                </div>
                <div class="form-group">
                    <label>Απόθεμα</label>
                    <input type="number" name="stock" min="0"
                           value="<?= htmlspecialchars($_POST['stock'] ?? '100') ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Κατηγορία *</label>
                <select name="category" required
                        style="width:100%;padding:11px 14px;border:1.5px solid var(--border);
                               border-radius:var(--radius-sm);font-family:inherit;font-size:0.9rem;
                               background:var(--off-white);outline:none;cursor:pointer;">
                    <option value="">— Επιλέξτε κατηγορία —</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat ?>"
                            <?= (($_POST['category'] ?? '') === $cat) ? 'selected' : '' ?>>
                            <?= $cat ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn-submit">Δημοσίευση Προϊόντος</button>
        </form>
    </div>

</div>
</div>
</main>

<footer class="footer">
    <strong>Shopora</strong> &copy; <?= date('Y') ?> &mdash; All rights reserved.
</footer>

<script>
document.getElementById('imageInput').addEventListener('change', function () {
    const file = this.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
        const preview     = document.getElementById('imagePreview');
        const placeholder = document.getElementById('imagePlaceholder');
        preview.src               = e.target.result;
        preview.style.display     = 'block';
        placeholder.style.display = 'none';
    };
    reader.readAsDataURL(file);
});
</script>

</body>
</html>
