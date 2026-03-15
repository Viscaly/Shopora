<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$user = getLoggedInUser();

$cartCount = 0;
if ($user) {
    $cid = (int)$user['user_id'];
    $res = $conn->query("SELECT SUM(quantity) AS total FROM cart WHERE user_id = $cid");
    if ($res) {
        $cartCount = (int)($res->fetch_assoc()['total'] ?? 0);
    }
}

$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir  = basename(dirname($_SERVER['PHP_SELF']));
?>

<nav class="navbar">
    <div class="nav-container">

        <!-- Logo -->
        <a href="/Shopora/index.php" class="brand">
            <img src="/Shopora/images/shopora.webp" alt="Shopora" class="brand-logo">
            <span class="brand-name">Shopora</span>
        </a>

        <!-- Navigation links -->
        <ul class="nav-links">
            <li><a href="/Shopora/index.php"
                class="<?= $currentPage === 'index.php' && $currentDir !== 'products' ? 'active' : '' ?>">
                Αρχική
            </a></li>
            <li><a href="/Shopora/products/products.php"
                class="<?= $currentDir === 'products' ? 'active' : '' ?>">
                Προϊόντα
            </a></li>
            <?php if ($user): ?>
            <li><a href="/Shopora/cart/cart.php"
                class="<?= $currentPage === 'cart.php' ? 'active' : '' ?>">
                Cart<?= $cartCount > 0 ? " ($cartCount)" : '' ?>
            </a></li>
            <?php endif; ?>
        </ul>

        <!-- Search -->
        <form class="nav-search" action="/Shopora/products/products.php" method="GET">
            <input type="text" name="search"
                   placeholder="Αναζήτηση Προϊόντων..."
                   value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
            <button type="submit">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2.5"
                     stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"/>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
            </button>
        </form>

        <!-- Right side actions -->
        <div class="nav-actions">
            <?php if ($user): ?>

                <!-- Add product button — only for logged-in users -->
                <a href="/Shopora/products/add_product.php" class="btn-add-product" title="Add Product">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2.5"
                         stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"/>
                        <line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                </a>

                <!-- Cart icon button -->
                <a href="/Shopora/cart/cart.php" class="cart-btn">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round">
                        <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
                        <line x1="3" y1="6" x2="21" y2="6"/>
                        <path d="M16 10a4 4 0 0 1-8 0"/>
                    </svg>
                    <?php if ($cartCount > 0): ?>
                        <span class="cart-badge"><?= $cartCount ?></span>
                    <?php endif; ?>
                </a>

                <!-- Account dropdown — opens on hover -->
                <div class="account-menu">
                    <div class="account-trigger">
                        <!-- Show avatar photo if set, otherwise show initials -->
                        <div class="account-avatar">
                            <?php if (!empty($user['avatar'])): ?>
                                <img src="/Shopora/images/avatars/<?= htmlspecialchars($user['avatar']) ?>"
                                     alt="avatar"
                                     style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
                            <?php else: ?>
                                <?= strtoupper(substr($user['first_name'], 0, 1)) ?>
                            <?php endif; ?>
                        </div>
                        <?= htmlspecialchars($user['first_name']) ?>
                        <span class="chevron-down">▼</span>
                    </div>

                    <div class="account-dropdown">
                        <!-- User info header -->
                        <div class="dropdown-header">
                            <strong><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></strong>
                            <span><?= htmlspecialchars($user['email']) ?></span>
                        </div>

                        <!-- Edit account -->
                        <a href="/Shopora/account/edit_account.php">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="2"
                                 stroke-linecap="round" stroke-linejoin="round">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                            </svg>
                            Επεξεργασία Λογαριασμού
                        </a>

                        <!-- Manage products link -->
                        <a href="/Shopora/products/edit_product.php">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="2"
                                 stroke-linecap="round" stroke-linejoin="round">
                                <rect x="2" y="3" width="20" height="14" rx="2"/>
                                <path d="M8 21h8M12 17v4"/>
                            </svg>
                            Διαχείριση Προϊόντων
                        </a>

                        <div class="dropdown-divider"></div>

                        <!-- Delete account -->
                        <a href="/Shopora/account/delete_account.php" class="danger">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="2"
                                 stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="3 6 5 6 21 6"/>
                                <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                                <path d="M10 11v6M14 11v6"/>
                                <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
                            </svg>
                            Delete Account
                        </a>

                        <div class="dropdown-divider"></div>

                        <!-- Sign out -->
                        <a href="/Shopora/account/logout.php">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="2"
                                 stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                                <polyline points="16 17 21 12 16 7"/>
                                <line x1="21" y1="12" x2="9" y2="12"/>
                            </svg>
                            Sign Out
                        </a>
                    </div>
                </div>

            <?php else: ?>
                <!-- Guest: show Sign In and Register -->
                <a href="/Shopora/account/login.php"    class="btn-ghost">Σύνδεση</a>
                <a href="/Shopora/account/register.php" class="btn-primary">Εγγραφή</a>
            <?php endif; ?>
        </div>

    </div>
</nav>
