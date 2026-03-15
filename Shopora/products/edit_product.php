<?php
require_once '../database/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$user = getLoggedInUser();
if (!$user) {
    header('Location: /Shopora/account/login.php');
    exit;
}

$uid   = (int)$user['user_id'];
$error = '';

// Αποθήκευση αλλαγών
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_edit'])) {
    $eid      = (int)$_POST['edit_id'];
    $name     = trim($conn->real_escape_string($_POST['name']));
    $desc     = trim($conn->real_escape_string($_POST['description'] ?? ''));
    $price    = (float)$_POST['price'];
    $stock    = (int)$_POST['stock'];
    $category = trim($conn->real_escape_string($_POST['category']));

    // Επιβεβαίωση ότι το προϊόν ανήκει στον χρήστη
    $own = $conn->query("SELECT product_id FROM products WHERE product_id = $eid AND owner_id = $uid");

    if ($own->num_rows === 0) {
        $error = 'Δεν έχετε δικαίωμα επεξεργασίας αυτού του προϊόντος.';
    } elseif (!$name || !$category || $price <= 0) {
        $error = 'Όνομα, κατηγορία και έγκυρη τιμή είναι υποχρεωτικά.';
    } else {
        $conn->query("
            UPDATE products
            SET name='$name', description='$desc', price=$price, stock=$stock, category='$category'
            WHERE product_id = $eid AND owner_id = $uid
        ");
        header('Location: /Shopora/products/product.php?id=' . $eid . '&updated=1');
        exit;
    }
}

// Ποιο προϊόν επεξεργαζόμαστε
$editId  = (int)($_GET['edit'] ?? 0);
$editing = null;
if ($editId > 0) {
    $res = $conn->query("SELECT * FROM products WHERE product_id = $editId AND owner_id = $uid");
    $editing = $res->num_rows > 0 ? $res->fetch_assoc() : null;
}

// Μόνο τα προϊόντα του συνδεδεμένου χρήστη
$products  = $conn->query("SELECT * FROM products WHERE owner_id = $uid ORDER BY category, name");
$catResult = $conn->query("SELECT DISTINCT category FROM products ORDER BY category");
$catList   = [];
while ($c = $catResult->fetch_assoc()) $catList[] = $c['category'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Διαχείριση Προϊόντων — Shopora</title>
    <link rel="stylesheet" href="/Shopora/style.css">
    <style>
        .manage-table { width:100%; border-collapse:collapse; background:var(--white);
                        border-radius:var(--radius); overflow:hidden; border:1px solid var(--border); }
        .manage-table th { background:var(--purple-pale); color:var(--purple);
                           font-size:0.8rem; text-transform:uppercase; letter-spacing:0.04em;
                           padding:12px 16px; text-align:left; }
        .manage-table td { padding:12px 16px; font-size:0.875rem;
                           border-top:1px solid var(--border); vertical-align:middle; }
        .manage-table tr:hover td { background:var(--purple-ultra); }
        .btn-edit { color:var(--purple); font-weight:700; font-size:0.8rem; text-decoration:none;
                    padding:5px 12px; border-radius:6px; border:1.5px solid var(--purple); transition:all 0.15s; }
        .btn-edit:hover { background:var(--purple); color:white; }
        .btn-del  { color:#c62828; font-weight:700; font-size:0.8rem; text-decoration:none;
                    padding:5px 12px; border-radius:6px; border:1.5px solid #f48fb1; transition:all 0.15s; }
        .btn-del:hover { background:#c62828; color:white; border-color:#c62828; }
    </style>
</head>
<body>

<?php require_once '../navbar.php'; ?>

<main class="main-content">
<div class="page-wrapper">
<section class="section">

    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;">
        <h2 class="section-title" style="margin:0;">Τα Προϊόντα μου</h2>
        <a href="/Shopora/products/add_product.php" class="btn-primary">+ Προσθήκη</a>
    </div>

    <?php if (isset($_GET['removed'])): ?>
        <div class="msg msg-success">
            Το προϊόν "<?= htmlspecialchars($_GET['removed']) ?>" διαγράφηκε.
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="msg msg-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Φόρμα επεξεργασίας  -->
    <?php if ($editing): ?>
    <div class="auth-card" style="margin-bottom:28px;max-width:100%;">
        <h3 style="font-family:'Cormorant Garamond',serif;font-size:1.2rem;font-weight:700;margin-bottom:16px;">
            Επεξεργασία: <?= htmlspecialchars($editing['name']) ?>
        </h3>
        <form method="POST" novalidate>
            <input type="hidden" name="edit_id" value="<?= $editing['product_id'] ?>">
            <div class="form-row">
                <div class="form-group">
                    <label>Όνομα *</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($editing['name']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Κατηγορία *</label>
                    <select name="category"
                            style="width:100%;padding:11px 14px;border:1.5px solid var(--border);
                                   border-radius:var(--radius-sm);font-family:inherit;font-size:0.9rem;
                                   background:var(--off-white);outline:none;">
                        <?php foreach ($catList as $cat): ?>
                        <option value="<?= htmlspecialchars($cat) ?>"
                            <?= $editing['category'] === $cat ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Περιγραφή</label>
                <textarea name="description" rows="2"
                    style="width:100%;padding:11px 14px;border:1.5px solid var(--border);
                           border-radius:var(--radius-sm);font-family:inherit;font-size:0.9rem;
                           resize:vertical;outline:none;background:var(--off-white);">
                    <?= htmlspecialchars($editing['description'] ?? '') ?>
                </textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Τιμή (€) *</label>
                    <input type="number" name="price" step="0.01" min="0.01"
                           value="<?= $editing['price'] ?>" required>
                </div>
                <div class="form-group">
                    <label>Απόθεμα</label>
                    <input type="number" name="stock" min="0" value="<?= $editing['stock'] ?>">
                </div>
            </div>
            <div style="display:flex;gap:10px;">
                <button type="submit" name="save_edit" class="btn-submit" style="width:auto;padding:10px 24px;">
                    Αποθήκευση
                </button>
                <a href="/Shopora/products/edit_product.php" class="btn-ghost">Ακύρωση</a>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <!-- Πίνακας προϊόντων -->
    <table class="manage-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Όνομα</th>
                <th>Κατηγορία</th>
                <th>Τιμή</th>
                <th>Απόθεμα</th>
                <th>Ενέργειες</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($products->num_rows === 0): ?>
            <tr><td colspan="6" style="text-align:center;color:var(--text-light);padding:32px;">
                Δεν έχετε προσθέσει προϊόντα ακόμα.
                <a href="/Shopora/products/add_product.php" style="color:var(--purple);">Προσθέστε ένα</a>.
            </td></tr>
        <?php else: ?>
            <?php while ($p = $products->fetch_assoc()): ?>
            <tr>
                <td style="color:var(--text-light);"><?= $p['product_id'] ?></td>
                <td>
                    <a href="/Shopora/products/product.php?id=<?= $p['product_id'] ?>"
                       style="color:var(--text);font-weight:700;text-decoration:none;">
                        <?= htmlspecialchars($p['name']) ?>
                    </a>
                </td>
                <td><?= htmlspecialchars($p['category']) ?></td>
                <td>€<?= number_format($p['price'], 2) ?></td>
                <td><?= $p['stock'] ?></td>
                <td style="display:flex;gap:8px;">
                    <a href="?edit=<?= $p['product_id'] ?>" class="btn-edit">Επεξεργασία</a>
                    <a href="/Shopora/products/remove_product.php?id=<?= $p['product_id'] ?>"
                       class="btn-del"
                       onclick="return confirm('Διαγραφή: <?= htmlspecialchars(addslashes($p['name'])) ?>?')">
                        Διαγραφή
                    </a>
                </td>
            </tr>
            <?php endwhile; ?>
        <?php endif; ?>
        </tbody>
    </table>

</section>
</div>
</main>

<footer class="footer">
    <strong>Shopora</strong> &copy; <?= date('Y') ?> &mdash; All rights reserved.
</footer>

</body>
</html>
