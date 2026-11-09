<?php
/**
 * includes/header.php
 * Shared header: logo, search bar, navigation icons, category bar.
 * Include at the top of every page.
 *
 * Required before including this file:
 *   session_start() must already be called.
 *   $pageTitle (string) – used for <title>.
 */

// Count cart & wishlist badges (only when user is logged in)
$cartCount = 0;
$wishlistCount = 0;
if (isset($_SESSION['user_id'])) {
    try {
        $db = getDB();

        $stmt = $db->prepare('SELECT COUNT(*) FROM cart_items WHERE user_id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $cartCount = (int) $stmt->fetchColumn();

        $stmt = $db->prepare('SELECT COUNT(*) FROM wishlist WHERE user_id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $wishlistCount = (int) $stmt->fetchColumn();
    } catch (PDOException $e) {
        // Silently ignore – badges simply show 0
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'ThriftZone') ?> | ThriftZone</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/thriftzone/assets/img/logo.png">

    <!-- Google Fonts: Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Main stylesheet -->
    <link rel="stylesheet" href="/thriftzone/assets/css/style.css">
</head>
<body>

<!-- ===== TOP HEADER ===== -->
<header class="site-header">
    <div class="header-inner container">

        <!-- Logo -->
        <a href="/thriftzone/" class="header-logo" aria-label="ThriftZone Home">
            <img src="/thriftzone/assets/img/logo.png" alt="ThriftZone Logo" class="logo-img">
        </a>

        <!-- Search bar -->
        <form class="search-form" action="/thriftzone/search.php" method="GET" role="search">
            <input
                type="search"
                name="q"
                class="search-input"
                placeholder="Cari barang preloved…"
                value="<?= htmlspecialchars($_GET['q'] ?? '') ?>"
                aria-label="Search items"
            >
            <button type="submit" class="search-btn" aria-label="Search">
                <i class="bi bi-search"></i>
            </button>
        </form>

        <!-- Right-side nav icons -->
        <nav class="header-nav" aria-label="User navigation">

            <?php if (isset($_SESSION['user_id'])): ?>
                <!-- Sell button -->
                <a href="/thriftzone/sell.php" class="nav-btn btn-sell" title="Jual Barang">
                    <i class="bi bi-plus-circle"></i>
                    <span class="nav-label">Jual</span>
                </a>

                <!-- Wishlist -->
                <a href="/thriftzone/wishlist.php" class="nav-icon-link" title="Wishlist">
                    <i class="bi bi-heart"></i>
                    <?php if ($wishlistCount > 0): ?>
                        <span class="badge"><?= $wishlistCount ?></span>
                    <?php endif; ?>
                    <span class="nav-label">Wishlist</span>
                </a>

                <!-- Cart -->
                <a href="/thriftzone/cart.php" class="nav-icon-link" title="Keranjang">
                    <i class="bi bi-bag"></i>
                    <?php if ($cartCount > 0): ?>
                        <span class="badge"><?= $cartCount ?></span>
                    <?php endif; ?>
                    <span class="nav-label">Keranjang</span>
                </a>

                <!-- My Orders -->
                <a href="/thriftzone/orders.php" class="nav-icon-link" title="Pesanan Saya">
                    <i class="bi bi-box-seam"></i>
                    <span class="nav-label">Pesanan</span>
                </a>

                <!-- Logout -->
                <a href="/thriftzone/logout.php" class="nav-icon-link" title="Keluar">
                    <i class="bi bi-box-arrow-right"></i>
                    <span class="nav-label">Keluar</span>
                </a>

            <?php else: ?>
                <!-- Login / Register -->
                <a href="/thriftzone/login.php" class="nav-btn btn-login">Masuk</a>
                <a href="/thriftzone/register.php" class="nav-btn btn-register">Daftar</a>
            <?php endif; ?>
        </nav>

        <!-- Mobile hamburger (reveals nav on small screens) -->
        <button class="hamburger" id="hamburgerBtn" aria-label="Toggle menu" aria-expanded="false">
            <i class="bi bi-list"></i>
        </button>
    </div>

    <!-- Mobile nav drawer -->
    <div class="mobile-nav" id="mobileNav" hidden>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="/thriftzone/sell.php"><i class="bi bi-plus-circle"></i> Jual Barang</a>
            <a href="/thriftzone/wishlist.php"><i class="bi bi-heart"></i> Wishlist <?= $wishlistCount > 0 ? "($wishlistCount)" : '' ?></a>
            <a href="/thriftzone/cart.php"><i class="bi bi-bag"></i> Keranjang <?= $cartCount > 0 ? "($cartCount)" : '' ?></a>
            <a href="/thriftzone/orders.php"><i class="bi bi-box-seam"></i> Pesanan Saya</a>
            <a href="/thriftzone/logout.php"><i class="bi bi-box-arrow-right"></i> Keluar</a>
        <?php else: ?>
            <a href="/thriftzone/login.php"><i class="bi bi-person"></i> Masuk</a>
            <a href="/thriftzone/register.php"><i class="bi bi-person-plus"></i> Daftar</a>
        <?php endif; ?>
    </div>
</header>

<!-- ===== CATEGORY BAR ===== -->
<nav class="category-bar" aria-label="Product categories">
    <div class="container category-list">
        <?php
        // Load categories from DB for the bar
        try {
            $catDb = getDB();
            $cats  = $catDb->query('SELECT id, name, icon FROM categories ORDER BY id')->fetchAll();
            foreach ($cats as $cat):
                $active = (isset($_GET['id']) && (int)$_GET['id'] === (int)$cat['id']) ? ' active' : '';
        ?>
            <a href="/thriftzone/category.php?id=<?= $cat['id'] ?>"
               class="cat-link<?= $active ?>">
                <i class="bi <?= htmlspecialchars($cat['icon']) ?>"></i>
                <?= htmlspecialchars($cat['name']) ?>
            </a>
        <?php
            endforeach;
        } catch (PDOException $e) {
            // DB not ready yet – skip silently
        }
        ?>
    </div>
</nav>

