<?php

require_once 'database/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();
$user = getLoggedInUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    if (!$user) {
        header('Location: /Shopora/account/login.php');
        exit;
    }
    $uid = (int)$user['user_id'];
    $pid = (int)$_POST['product_id'];

    $conn->query("
        INSERT INTO cart (user_id, product_id, quantity)
        VALUES ($uid, $pid, 1)
        ON DUPLICATE KEY UPDATE quantity = quantity + 1
    ");
    header('Location: /Shopora/index.php#featured');
    exit;
}

$featured = $conn->query("
    SELECT p.*,
           ROUND(AVG(r.vathmologia), 1) AS avg_rating,
           COUNT(r.id) AS review_count
    FROM products p
    LEFT JOIN reviews r ON r.product_id = p.product_id
    GROUP BY p.product_id
    ORDER BY avg_rating DESC, p.product_id ASC
    LIMIT 4
");

$categories = $conn->query("SELECT DISTINCT category FROM products ORDER BY category");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopora</title>
    <link rel="stylesheet" href="/Shopora/style.css">
</head>
<body>

<?php require_once 'navbar.php'; ?>

<?php if ($user): ?>
<div style="background:var(--purple);color:white;text-align:center;
            padding:10px 20px;font-size:0.9rem;font-weight:600;">
    Καλώς ήρθες, <strong><?= htmlspecialchars($user['first_name']) ?> <?= htmlspecialchars($user['last_name']) ?></strong>! 🎉
</div>
<?php endif; ?>

<?php if (isset($_GET['deleted'])): ?>
<div style="background:#e8f5e9;border-bottom:1px solid #a5d6a7;padding:12px 32px;
            text-align:center;font-size:0.875rem;font-weight:600;color:#2e7d32;">
    Ο λογαριασμός σας έχει διαγραφεί. Λυπούμαστε που σας βλέπουμε να φεύγετε.
</div>
<?php endif; ?>

<main class="main-content">
    <!-- Hero -->
    <section class="hero">
        <div class="hero-content">
            <h1>Ανακαλύψτε τα Καλύτερα Προϊόντα.</h1>
            <p>Χιλιάδες προϊόντα σε ανταγωνιστικές τιμές. Γρήγορη παράδοση, εύκολες αγορές.</p>
            <div class="hero-btns">
                <a href="#featured" class="btn-white">Αγοράστε Τώρα</a>
                <?php if (!$user): ?>
                    <a href="/Shopora/account/register.php" class="btn-outline-white">Εγγραφείτε Δωρεάν</a>
                <?php endif; ?>
            </div>
        </div>
    </section>


    <!-- Παρουσίαση εταιρείας -->
    <div class="page-wrapper">
        <section class="section" id="about">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:48px;align-items:center;">

                <!-- Κείμενο παρουσίασης -->
                <div>
                    <span style="display:inline-block;background:var(--purple-pale);color:var(--purple);
                                 font-size:0.78rem;font-weight:700;padding:4px 12px;
                                 border-radius:var(--radius-pill);letter-spacing:0.05em;
                                 text-transform:uppercase;margin-bottom:16px;">
                        Ποιοι είμαστε
                    </span>
                    <h2 class="section-title">Καλώς ήρθατε στη Shopora</h2>
                    <p style="color:var(--text-mid);line-height:1.8;font-size:0.95rem;margin-top:12px;">
                        Η <strong>Shopora</strong> είναι ένα σύγχρονο ηλεκτρονικό κατάστημα που προσφέρει
                        μια μεγάλη ποικιλία προϊόντων σε ανταγωνιστικές τιμές. Από τεχνολογία και gaming
                        μέχρι μόδα και βιβλία έχουμε ό,τι χρειάζεστε.
                    </p>
                    <p style="color:var(--text-mid);line-height:1.8;font-size:0.95rem;margin-top:12px;">
                        Στόχος μας είναι να κάνουμε τις online αγορές σας γρήγορες, ασφαλείς και ευχάριστες.
                        Κάθε προϊόν αξιολογείται από την κοινότητά μας ώστε να επιλέγετε πάντα με σιγουριά.
                    </p>

                    <!-- Στατιστικά -->
                    <div style="display:flex;gap:32px;margin-top:28px;">
                        <div style="text-align:center;">
                            <div style="font-family:'Cormorant Garamond',serif;font-size:2rem;font-weight:700;color:var(--purple);">
                                <?= $conn->query("SELECT COUNT(*) AS c FROM products")->fetch_assoc()['c'] ?>+
                            </div>
                            <div style="font-size:0.8rem;color:var(--text-light);font-weight:600;">Προϊόντα</div>
                        </div>
                        <div style="text-align:center;">
                            <div style="font-family:'Cormorant Garamond',serif;font-size:2rem;font-weight:700;color:var(--purple);">
                                <?= $conn->query("SELECT COUNT(*) AS c FROM customers")->fetch_assoc()['c'] ?>+
                            </div>
                            <div style="font-size:0.8rem;color:var(--text-light);font-weight:600;">Πελάτες</div>
                        </div>
                        <div style="text-align:center;">
                            <div style="font-family:'Cormorant Garamond',serif;font-size:2rem;font-weight:700;color:var(--purple);">
                                <?= $conn->query("SELECT COUNT(DISTINCT category) AS c FROM products")->fetch_assoc()['c'] ?>
                            </div>
                            <div style="font-size:0.8rem;color:var(--text-light);font-weight:600;">Κατηγορίες</div>
                        </div>
                    </div>
                </div>

                <!-- Χαρακτηριστικά -->
                <div style="display:flex;flex-direction:column;gap:16px;">
                    <?php
                    $features = [
                        ['🔒', 'Ασφαλείς Συναλλαγές',   'Τα δεδομένα σας προστατεύονται πάντα.'],
                        ['⚡', 'Γρήγορη Παράδοση',       'Λάβετε τα προϊόντα σας το συντομότερο δυνατό.'],
                        ['⭐', 'Αξιολογήσεις Πελατών',   'Διαβάστε πραγματικές κριτικές πριν αγοράσετε.'],
                        ['↩️', 'Εύκολες Επιστροφές',     'Απλή διαδικασία επιστροφής χωρίς ταλαιπωρία.'],
                    ];
                    foreach ($features as $f): ?>
                    <div style="display:flex;align-items:flex-start;gap:14px;background:var(--white);
                                border:1px solid var(--border);border-radius:var(--radius);padding:16px 20px;">
                        <span style="font-size:1.4rem;line-height:1;flex-shrink:0;"><?= $f[0] ?></span>
                        <div>
                            <div style="font-weight:700;font-size:0.9rem;color:var(--text);margin-bottom:3px;">
                                <?= $f[1] ?>
                            </div>
                            <div style="font-size:0.82rem;color:var(--text-mid);"><?= $f[2] ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

            </div>
        </section>
    </div>
    <hr style="border:none;border-top:1px solid var(--border);">

    <div class="page-wrapper">
        <section class="section" id="categories">
            <h2 class="section-title">Περιήγηση ανά Κατηγορία</h2>
            <p class="section-sub">Επιλέξτε μια κατηγορία για να εξερευνήσετε όλα τα προϊόντα</p>
            <div class="cat-grid">
                <?php
                $catIcons = ['Technology'=>'💻','Games'=>'🎮','Fashion'=>'👗','Books'=>'📚','Home & Living'=>'🏠','Sport'=>'🏃','Beauty'=>'💄','Toys'=>'🧸'];
                while ($cat = $categories->fetch_assoc()):
                    $icon = $catIcons[$cat['category']] ?? '📦';
                ?>
                <a href="/Shopora/products/products.php?category=<?= urlencode($cat['category']) ?>"
                   class="cat-card">
                    <span class="cat-icon"><?= $icon ?></span>
                    <?= htmlspecialchars($cat['category']) ?>
                </a>
                <?php endwhile; ?>
            </div>
        </section>

        <section class="section" id="featured">
            <h2 class="section-title">Προτεινόμενα Προϊόντα</h2>
            <p class="section-sub">Με την υψηλότερη βαθμολογία από τους πελάτες μας</p>

            <div class="product-grid">
            <?php if ($featured->num_rows === 0): ?>
                <p style="color:var(--text-light);">No products yet.</p>
            <?php else: ?>
                <?php while ($p = $featured->fetch_assoc()):
                    $rating = $p['avg_rating'] ?? 0;
                    $count  = $p['review_count'];
                ?>
                <div class="product-card">
                    <div class="product-img">
                    <?php if (!empty($p['image_path'])): ?>
                        <img src="/Shopora/images/products/<?= htmlspecialchars($p['image_path']) ?>"
                             alt="<?= htmlspecialchars($p['name']) ?>">
                    <?php else: ?>
                        🛍️
                    <?php endif; ?>
                </div>
                    <div class="product-info">
                        <div class="product-name"><?= htmlspecialchars($p['name']) ?></div>
                        <div class="product-cat"><?= htmlspecialchars($p['category']) ?></div>

                        <!-- Star rating display -->
                        <div class="stars" style="margin:6px 0;">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <span class="star <?= $i <= round($rating) ? 'filled' : '' ?>">★</span>
                            <?php endfor; ?>
                            <span class="rating-count">
                                <?= $rating > 0 ? $rating . ' (' . $count . ')' : 'No reviews yet' ?>
                            </span>
                        </div>

                        <div class="product-footer">
                            <span class="product-price">€<?= number_format($p['price'], 2) ?></span>
                            <div style="display:flex;gap:6px;">
                                <!-- View details button -->
                                <a href="/Shopora/products/product.php?id=<?= $p['product_id'] ?>"
                                   class="btn-ghost" style="padding:6px 12px;font-size:0.78rem;">
                                    Λεπτομέρειες
                                </a>
                                <!-- Add to cart -->
                                <?php if ($user): ?>
                                <form method="POST">
                                    <input type="hidden" name="product_id" value="<?= $p['product_id'] ?>">
                                    <button type="submit" name="add_to_cart" class="add-to-cart">
                                        + Καλάθι
                                    </button>
                                </form>
                                <?php else: ?>
                                <a href="/Shopora/account/login.php" class="add-to-cart"
                                   style="text-decoration:none;display:inline-flex;align-items:center;">
                                    + Καλάθι
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php endif; ?>
            </div>

            <div style="text-align:center;margin-top:32px;">
                <a href="/Shopora/products/products.php" class="btn-primary">Προβολή όλων των Προϊόντων</a>
            </div>
        </section>

    </div>

</main>

<footer class="footer">
    <strong>Shopora</strong> &copy; <?= date('Y') ?> &mdash; All rights reserved.
</footer>

</body>
</html>
