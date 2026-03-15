<?php

require_once '../database/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();
$user = getLoggedInUser();

$pid = (int)($_GET['id'] ?? 0);
if ($pid <= 0) {
    header('Location: /Shopora/products/products.php');
    exit;
}

// Fetch product with avg rating
$result = $conn->query("
    SELECT p.*,
           ROUND(AVG(r.vathmologia), 1) AS avg_rating,
           COUNT(r.id) AS review_count
    FROM products p
    LEFT JOIN reviews r ON r.product_id = p.product_id
    WHERE p.product_id = $pid
    GROUP BY p.product_id
");
if ($result->num_rows === 0) {
    header('Location: /Shopora/products/products.php');
    exit;
}
$product = $result->fetch_assoc();

$reviewError   = '';
$reviewSuccess = '';
$commentError  = '';
$commentSuccess= '';

// Handle review submission (star rating + comment)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (!$user) { header('Location: /Shopora/account/login.php'); exit; }
    $uid     = (int)$user['user_id'];
    $rating  = (int)$_POST['rating'];
    $comment = trim($conn->real_escape_string($_POST['comment'] ?? ''));

    if ($rating < 1 || $rating > 5) {
        $reviewError = 'Please select a star rating.';
    } else {
        $ok = $conn->query("
            INSERT INTO reviews (product_id, customer_id, vathmologia, sxolio)
            VALUES ($pid, $uid, $rating, '$comment')
        ");
        if ($ok) {
            $reviewSuccess = 'Your review has been submitted!';
            // Refresh avg rating
            $result  = $conn->query("
                SELECT p.*, ROUND(AVG(r.vathmologia),1) AS avg_rating, COUNT(r.id) AS review_count
                FROM products p LEFT JOIN reviews r ON r.product_id = p.product_id
                WHERE p.product_id = $pid GROUP BY p.product_id
            ");
            $product = $result->fetch_assoc();
        } else {
            $reviewError = 'You have already reviewed this product.';
        }
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_comment'])) {
    if (!$user) { header('Location: /Shopora/account/login.php'); exit; }
    $uid  = (int)$user['user_id'];
    $body = trim($conn->real_escape_string($_POST['body'] ?? ''));

    if (!$body) {
        $commentError = 'Comment cannot be empty.';
    } else {
        $conn->query("
            INSERT INTO comments (product_id, customer_id, body)
            VALUES ($pid, $uid, '$body')
        ");
        $commentSuccess = 'Your comment was posted!';
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    if (!$user) { header('Location: /Shopora/account/login.php'); exit; }
    $uid = (int)$user['user_id'];
    $conn->query("
        INSERT INTO cart (user_id, product_id, quantity)
        VALUES ($uid, $pid, 1)
        ON DUPLICATE KEY UPDATE quantity = quantity + 1
    ");
    header('Location: /Shopora/products/product.php?id=' . $pid);
    exit;
}

$reviews = $conn->query("
    SELECT r.vathmologia, r.sxolio, r.created_at, c.first_name, c.last_name, c.avatar
    FROM reviews r
    JOIN customers c ON c.user_id = r.customer_id
    WHERE r.product_id = $pid
    ORDER BY r.created_at DESC
");


$comments = $conn->query("
    SELECT cm.body, cm.created_at, c.first_name, c.last_name, c.avatar
    FROM comments cm
    JOIN customers c ON c.user_id = cm.customer_id
    WHERE cm.product_id = $pid
    ORDER BY cm.created_at ASC
");

// Έλεγχος αν ο συνδεδεμένος χρήστης είναι ιδιοκτήτης του προϊόντος
$isOwner = $user && (int)$product['owner_id'] === (int)$user['user_id'];

$alreadyReviewed = false;
if ($user) {
    $uid   = (int)$user['user_id'];
    $check = $conn->query("SELECT id FROM reviews WHERE product_id = $pid AND customer_id = $uid");
    $alreadyReviewed = $check->num_rows > 0;
}

$rating     = $product['avg_rating'] ?? 0;
$imageFile  = $product['image_path'] ?? '';
$imageUrl   = $imageFile ? '/Shopora/images/products/' . htmlspecialchars($imageFile) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['name']) ?> — Shopora</title>
    <link rel="stylesheet" href="/Shopora/style.css">
</head>
<body>

<?php require_once '../navbar.php'; ?>

<main class="main-content">
<div class="page-wrapper">


    <?php if (isset($_GET['added'])): ?>
    <div style="background:#e8f5e9;border:1px solid #a5d6a7;border-radius:var(--radius-sm);
                padding:12px 18px;margin-top:20px;font-size:0.875rem;font-weight:600;color:#2e7d32;
                display:flex;align-items:center;gap:10px;">
        ✅ Product published successfully! It is now visible to everyone.
        <a href="/Shopora/products/add_product.php"
           style="margin-left:auto;color:var(--purple);text-decoration:none;font-weight:700;">
            + Add another
        </a>
    </div>
    <?php endif; ?>

    <!-- Μήνυμα επιτυχίας μετά από επεξεργασία -->
    <?php if (isset($_GET['updated'])): ?>
    <div class="msg msg-success" style="margin:16px 0 0;">
        ✅ Το προϊόν ενημερώθηκε με επιτυχία.
    </div>
    <?php endif; ?>

    <!-- Breadcrumb -->
    <p style="padding-top:24px;font-size:0.85rem;color:var(--text-light);">
        <a href="/Shopora/index.php" style="color:var(--purple);text-decoration:none;">Home</a>
        &rsaquo;
        <a href="/Shopora/products/products.php?category=<?= urlencode($product['category']) ?>"
           style="color:var(--purple);text-decoration:none;">
            <?= htmlspecialchars($product['category']) ?>
        </a>
        &rsaquo; <?= htmlspecialchars($product['name']) ?>
    </p>

    <!-- Product detail layout -->
    <div class="product-detail">

        <!-- Product image -->
        <div class="product-detail-img">
            <?php if ($imageUrl): ?>
                <img src="<?= $imageUrl ?>" alt="<?= htmlspecialchars($product['name']) ?>">
            <?php else: ?>
                🛍️
            <?php endif; ?>
        </div>

        <!-- Product info -->
        <div class="product-detail-info">
            <span class="product-badge"><?= htmlspecialchars($product['category']) ?></span>
            <h1 class="product-detail-name"><?= htmlspecialchars($product['name']) ?></h1>

            <!-- Star rating display -->
            <div class="stars">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <span class="star <?= $i <= round($rating) ? 'filled' : '' ?>"
                          style="font-size:1.2rem;">★</span>
                <?php endfor; ?>
                <span class="rating-count" style="font-size:0.85rem;">
                    <?= $rating > 0
                        ? $rating . ' / 5 (' . $product['review_count'] . ' review' . ($product['review_count'] != 1 ? 's' : '') . ')'
                        : 'No reviews yet' ?>
                </span>
            </div>

            <!-- Euro price -->
            <div class="product-detail-price">€<?= number_format($product['price'], 2) ?></div>

            <p class="product-detail-desc">
                <?= nl2br(htmlspecialchars($product['description'] ?? 'No description available.')) ?>
            </p>

            <p style="font-size:0.85rem;color:var(--text-light);">
                Stock: <strong style="color:<?= $product['stock'] > 0 ? 'var(--text)' : '#c62828' ?>;">
                    <?= $product['stock'] > 0 ? $product['stock'] . ' available' : 'Out of stock' ?>
                </strong>
            </p>

            <!-- Κουμπιά ιδιοκτήτη -->
            <?php if ($isOwner): ?>
            <div style="display:flex;gap:10px;padding:14px 16px;background:var(--purple-ultra);
                        border:1px solid var(--border);border-radius:var(--radius-sm);align-items:center;">
                <span style="font-size:0.8rem;color:var(--text-mid);font-weight:600;flex:1;">
                    Είστε ιδιοκτήτης αυτού του προϊόντος
                </span>
                <a href="/Shopora/products/edit_product.php?edit=<?= $pid ?>"
                   class="btn-ghost" style="font-size:0.8rem;padding:6px 14px;">
                    ✏️ Επεξεργασία
                </a>
                <a href="/Shopora/products/remove_product.php?id=<?= $pid ?>"
                   style="font-size:0.8rem;padding:6px 14px;border-radius:var(--radius-sm);
                          border:1.5px solid #f48fb1;color:#c62828;text-decoration:none;
                          font-weight:700;transition:background 0.2s;"
                   onmouseover="this.style.background='#fce4ec'"
                   onmouseout="this.style.background='transparent'"
                   onclick="return confirm('Διαγραφή αυτού του προϊόντος;')">
                    🗑 Διαγραφή
                </a>
            </div>
            <?php endif; ?>

            <!-- Add to cart -->
            <?php if ($user): ?>
                <?php if ($product['stock'] > 0): ?>
                <form method="POST">
                    <button type="submit" name="add_to_cart"
                            class="btn-primary" style="padding:12px 28px;font-size:0.95rem;">
                        Προσθήκη στο Καλάθι
                    </button>
                </form>
                <?php else: ?>
                <button class="btn-primary" disabled
                        style="padding:12px 28px;font-size:0.95rem;opacity:0.5;cursor:not-allowed;">
                    Out of Stock
                </button>
                <?php endif; ?>
            <?php else: ?>
                <a href="/Shopora/account/login.php"
                   class="btn-primary" style="padding:12px 28px;font-size:0.95rem;">
                    Sign in to Add to Cart
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- ── Reviews Section ───────────────────────────── -->
    <div class="reviews-section">
        <h2 class="section-title" style="margin-bottom:20px;">
            Reviews
            <?php if ($product['review_count'] > 0): ?>
                <span style="font-size:1rem;font-weight:400;color:var(--text-light);font-family:'Nunito',sans-serif;">
                    (<?= $product['review_count'] ?>)
                </span>
            <?php endif; ?>
        </h2>

        <?php if ($reviews->num_rows === 0): ?>
            <p style="color:var(--text-light);margin-bottom:24px;">No reviews yet. Be the first!</p>
        <?php else: ?>
            <?php while ($r = $reviews->fetch_assoc()): ?>
            <div class="review-card">
                <div class="review-card-header">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <!-- Reviewer avatar -->
                        <div class="comment-avatar" style="flex-shrink:0;">
                            <?php if (!empty($r['avatar'])): ?>
                                <img src="/Shopora/images/avatars/<?= htmlspecialchars($r['avatar']) ?>"
                                     alt="" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
                            <?php else: ?>
                                <?= strtoupper(substr($r['first_name'], 0, 1)) ?>
                            <?php endif; ?>
                        </div>
                        <div>
                            <span class="reviewer-name">
                                <?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) ?>
                            </span>
                            <div class="stars" style="margin-top:4px;">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <span class="star <?= $i <= $r['vathmologia'] ? 'filled' : '' ?>">★</span>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </div>
                    <span class="review-date"><?= date('d M Y', strtotime($r['created_at'])) ?></span>
                </div>
                <?php if ($r['sxolio']): ?>
                    <p class="review-comment"><?= nl2br(htmlspecialchars($r['sxolio'])) ?></p>
                <?php endif; ?>
            </div>
            <?php endwhile; ?>
        <?php endif; ?>

        <!-- Review form -->
        <?php if (!$user): ?>
            <div class="guest-review-notice">
                <a href="/Shopora/account/login.php">Sign in</a> or
                <a href="/Shopora/account/register.php">register</a> to leave a review.
            </div>
        <?php elseif ($alreadyReviewed && !$reviewSuccess): ?>
            <div class="guest-review-notice">You have already reviewed this product.</div>
        <?php else: ?>
            <?php if (!$reviewSuccess): ?>
            <div class="review-form">
                <h4>Write a Review</h4>
                <?php if ($reviewError): ?>
                    <div class="msg msg-error"><?= htmlspecialchars($reviewError) ?></div>
                <?php endif; ?>
                <form method="POST">
                    <label style="font-weight:700;font-size:0.82rem;color:var(--text-mid);
                                  text-transform:uppercase;letter-spacing:0.03em;">Your Rating *</label>
                    <div class="star-select">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                        <input type="radio" name="rating" id="star<?= $i ?>" value="<?= $i ?>">
                        <label for="star<?= $i ?>">★</label>
                        <?php endfor; ?>
                    </div>
                    <div class="form-group" style="margin-top:12px;">
                        <label>Comment (optional)</label>
                        <textarea name="comment" rows="3"
                            style="width:100%;padding:11px 14px;border:1.5px solid var(--border);
                                   border-radius:var(--radius-sm);font-family:inherit;font-size:0.9rem;
                                   resize:vertical;outline:none;"
                            placeholder="Share your experience..."></textarea>
                    </div>
                    <button type="submit" name="submit_review"
                            class="btn-submit" style="width:auto;padding:10px 28px;">
                        Submit Review
                    </button>
                </form>
            </div>
            <?php else: ?>
                <div class="msg msg-success"><?= htmlspecialchars($reviewSuccess) ?></div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- ── Comments Section ──────────────────────────── -->
    <div class="comments-section">
        <h2 class="section-title" style="margin-bottom:20px;">
            Discussion
            <?php if ($comments->num_rows > 0): ?>
                <span style="font-size:1rem;font-weight:400;color:var(--text-light);font-family:'Nunito',sans-serif;">
                    (<?= $comments->num_rows ?>)
                </span>
            <?php endif; ?>
        </h2>

        <?php if ($comments->num_rows === 0): ?>
            <p style="color:var(--text-light);margin-bottom:24px;">
                No comments yet. Start the discussion!
            </p>
        <?php else: ?>
            <?php while ($cm = $comments->fetch_assoc()): ?>
            <div class="comment-card">
                <div class="comment-header">
                    <div class="comment-author">
                        <div class="comment-avatar">
                            <?php if (!empty($cm['avatar'])): ?>
                                <img src="/Shopora/images/avatars/<?= htmlspecialchars($cm['avatar']) ?>"
                                     alt="" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
                            <?php else: ?>
                                <?= strtoupper(substr($cm['first_name'], 0, 1)) ?>
                            <?php endif; ?>
                        </div>
                        <span class="comment-name">
                            <?= htmlspecialchars($cm['first_name'] . ' ' . $cm['last_name']) ?>
                        </span>
                    </div>
                    <span class="comment-date"><?= date('d M Y, H:i', strtotime($cm['created_at'])) ?></span>
                </div>
                <p class="comment-body"><?= nl2br(htmlspecialchars($cm['body'])) ?></p>
            </div>
            <?php endwhile; ?>
        <?php endif; ?>

        <!-- Comment form -->
        <?php if (!$user): ?>
            <div class="guest-review-notice">
                <a href="/Shopora/account/login.php">Sign in</a> or
                <a href="/Shopora/account/register.php">register</a> to join the discussion.
            </div>
        <?php else: ?>
            <div class="comment-form">
                <?php if ($commentError):   ?><div class="msg msg-error"><?=   htmlspecialchars($commentError)   ?></div><?php endif; ?>
                <?php if ($commentSuccess): ?><div class="msg msg-success"><?= htmlspecialchars($commentSuccess) ?></div><?php endif; ?>
                <form method="POST">
                    <textarea name="body" rows="3"
                              placeholder="Write a comment..."
                              required><?= htmlspecialchars($_POST['body'] ?? '') ?></textarea>
                    <button type="submit" name="submit_comment"
                            class="btn-primary" style="margin-top:10px;padding:9px 22px;">
                        Post Comment
                    </button>
                </form>
            </div>
        <?php endif; ?>

    </div>

</div>
</main>

<footer class="footer">
    <strong>Shopora</strong> &copy; <?= date('Y') ?> &mdash; All rights reserved.
</footer>

</body>
</html>
