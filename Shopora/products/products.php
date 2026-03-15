<?php

require_once '../database/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();
$user = getLoggedInUser();

// Handle add-to-cart
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
    header('Location: /Shopora/products/products.php?category=' . urlencode($_POST['current_category'] ?? ''));
    exit;
}

$search   = trim($conn->real_escape_string($_GET['search']   ?? ''));
$category = trim($conn->real_escape_string($_GET['category'] ?? ''));

$where = [];
if ($search)   $where[] = "(p.name LIKE '%$search%' OR p.description LIKE '%$search%')";
if ($category) $where[] = "p.category = '$category'";
$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';


$products = $conn->query("
    SELECT p.*,
           ROUND(AVG(r.vathmologia), 1) AS avg_rating,
           COUNT(r.id) AS review_count
    FROM products p
    LEFT JOIN reviews r ON r.product_id = p.product_id
    $whereSQL
    GROUP BY p.product_id
    ORDER BY p.name ASC
");


$categories = $conn->query("SELECT DISTINCT category FROM products ORDER BY category");
$catList = [];
while ($c = $categories->fetch_assoc()) $catList[] = $c['category'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $category ? htmlspecialchars($category) . ' — ' : '' ?>Products — Shopora</title>
    <link rel="stylesheet" href="/Shopora/style.css">
</head>
<body>

<?php require_once '../navbar.php'; ?>

<main class="main-content">
<div class="page-wrapper">
<section class="section">

    <h2 class="section-title">
        <?= $search   ? 'Results for "' . htmlspecialchars($search) . '"'
          : ($category ? htmlspecialchars($category)
          : 'All Products') ?>
    </h2>

    <!-- Category filter tabs -->
    <div class="cat-tabs">
        <a href="/Shopora/products/products.php"
           class="cat-tab <?= !$category && !$search ? 'active' : '' ?>">All</a>
        <?php foreach ($catList as $cat): ?>
        <a href="/Shopora/products/products.php?category=<?= urlencode($cat) ?>"
           class="cat-tab <?= $category === $cat ? 'active' : '' ?>">
            <?= htmlspecialchars($cat) ?>
        </a>
        <?php endforeach; ?>
    </div>

    <p class="section-sub" style="margin-top:12px;">
        <?= $products->num_rows ?> product<?= $products->num_rows !== 1 ? 's' : '' ?> found
    </p>

    <div class="product-grid">
    <?php if ($products->num_rows === 0): ?>
        <p style="color:var(--text-light);grid-column:1/-1;">No products found.</p>
    <?php else: ?>
        <?php while ($p = $products->fetch_assoc()):
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

                <div class="stars" style="margin:6px 0;">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <span class="star <?= $i <= round($rating) ? 'filled' : '' ?>">★</span>
                    <?php endfor; ?>
                    <span class="rating-count">
                        <?= $rating > 0 ? $rating . ' (' . $count . ')' : 'No reviews' ?>
                    </span>
                </div>

                <div class="product-footer">
                    <span class="product-price">€<?= number_format($p['price'], 2) ?></span>
                    <div style="display:flex;gap:6px;">
                        <a href="/Shopora/products/product.php?id=<?= $p['product_id'] ?>"
                           class="btn-ghost" style="padding:6px 12px;font-size:0.78rem;">
                            Λεπτομέρειες
                        </a>
                        <?php if ($user): ?>
                        <form method="POST">
                            <input type="hidden" name="product_id"       value="<?= $p['product_id'] ?>">
                            <input type="hidden" name="current_category" value="<?= htmlspecialchars($category) ?>">
                            <button type="submit" name="add_to_cart" class="add-to-cart">+ Cart</button>
                        </form>
                        <?php else: ?>
                        <a href="/Shopora/account/login.php" class="add-to-cart"
                           style="text-decoration:none;display:inline-flex;align-items:center;">
                            + Cart
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    <?php endif; ?>
    </div>

</section>
</div>
</main>

<footer class="footer">
    <strong>Shopora</strong> &copy; <?= date('Y') ?> &mdash; All rights reserved.
</footer>

</body>
</html>
