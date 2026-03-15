<?php


require_once '../database/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// Cart requires login
$user = getLoggedInUser();
if (!$user) {
    header('Location: /Shopora/account/login.php');
    exit;
}

$uid = (int)$user['user_id'];

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pid    = (int)($_POST['product_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($action === 'increase') {
        $conn->query("UPDATE cart SET quantity = quantity + 1
                      WHERE user_id = $uid AND product_id = $pid");

    } elseif ($action === 'decrease') {
        $conn->query("UPDATE cart SET quantity = quantity - 1
                      WHERE user_id = $uid AND product_id = $pid");
        $conn->query("DELETE FROM cart
                      WHERE user_id = $uid AND product_id = $pid AND quantity <= 0");

    } elseif ($action === 'remove') {

        $conn->query("DELETE FROM cart
                      WHERE user_id = $uid AND product_id = $pid");

    } elseif ($action === 'clear') {

        $conn->query("DELETE FROM cart WHERE user_id = $uid");
    }

    header('Location: /Shopora/cart/cart.php');
    exit;
}

// Fetch cart items joined with product details
$cartItems = $conn->query("
    SELECT c.product_id, c.quantity,
           p.name, p.price, p.category
    FROM cart c
    JOIN products p ON c.product_id = p.product_id
    WHERE c.user_id = $uid
    ORDER BY c.added_at ASC
");

// Calculate cart
$subtotal  = 0;
$totalQty  = 0;
$rows      = [];
while ($row = $cartItems->fetch_assoc()) {
    $subtotal += $row['price'] * $row['quantity'];
    $totalQty += $row['quantity'];
    $rows[]    = $row;
}
$shipping = $subtotal > 0 ? ($subtotal >= 50 ? 0 : 3.99) : 0;
$total    = $subtotal + $shipping;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart &mdash; Shopora</title>
    <link rel="stylesheet" href="/Shopora/style.css">
</head>
<body>

<?php require_once '../navbar.php'; ?>

<main class="main-content">
<div class="page-wrapper">
    <div class="cart-layout">


        <div>
            <h2 class="section-title" style="margin-bottom:20px;">My Cart</h2>

            <?php if (empty($rows)): ?>
                <!-- Empty state -->
                <div class="cart-empty">
                    <p>Your cart is empty.</p>
                    <a href="/Shopora/index.php" class="btn-primary">Continue Shopping</a>
                </div>

            <?php else: ?>
                <div class="cart-items">
                    <?php foreach ($rows as $item): ?>
                    <div class="cart-item">

                        <div class="cart-item-img">🛍️</div>

                        <div class="cart-item-info">
                            <div class="cart-item-name"><?= htmlspecialchars($item['name']) ?></div>
                            <div class="cart-item-cat">€<?= number_format($item['price'], 2) ?> each</div>
                        </div>


                        <div class="cart-item-qty">
                            <form method="POST" style="display:contents;">
                                <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                                <button class="qty-btn" name="action" value="decrease">−</button>
                                <span class="qty-num"><?= $item['quantity'] ?></span>
                                <button class="qty-btn" name="action" value="increase">+</button>
                            </form>
                        </div>


                        <span class="cart-item-price">
                            €<?= number_format($item['price'] * $item['quantity'], 2) ?>
                        </span>


                        <form method="POST">
                            <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                            <button class="cart-remove" name="action" value="remove" title="Remove">✕</button>
                        </form>

                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Clear entire cart -->
                <form method="POST" style="margin-top:16px;">
                    <button name="action" value="clear" class="btn-ghost"
                            onclick="return confirm('Clear entire cart?')">
                        Clear Cart
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <div>
            <div class="cart-summary">
                <h3>Order Summary</h3>

                <div class="summary-row">
                    <span>Subtotal (<?= $totalQty ?> items)</span>
                    <span>€<?= number_format($subtotal, 2) ?></span>
                </div>
                <div class="summary-row">
                    <span>Shipping</span>
                    <span><?= $shipping > 0 ? '€' . number_format($shipping, 2) : 'Free' ?></span>
                </div>
                <?php if ($subtotal > 0 && $subtotal < 50): ?>
                    <p class="shipping-hint">
                        Add €<?= number_format(50 - $subtotal, 2) ?> more for free shipping!
                    </p>
                <?php endif; ?>

                <div class="summary-total">
                    <span>Total</span>
                    <span>€<?= number_format($total, 2) ?></span>
                </div>

                <?php if (!empty($rows)): ?>
                    <button class="btn-submit" style="margin-top:20px;"
                        onclick="alert('Order placed! Thank you, <?= htmlspecialchars($user['first_name']) ?>!')">
                        Place Order
                    </button>
                <?php endif; ?>

                <a href="/Shopora/index.php" class="btn-ghost"
                   style="width:100%;justify-content:center;margin-top:10px;display:flex;">
                    &larr; Continue Shopping
                </a>
            </div>
        </div>

    </div>
</div>
</main>

<footer class="footer">
    <strong>Shopora</strong> &copy; <?= date('Y') ?> &mdash; All rights reserved.
</footer>

</body>
</html>
