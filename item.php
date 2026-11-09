<?php
/**
 * item.php – Item detail page.
 * Shows photo, title, price, condition, seller, and add-to-cart/wishlist buttons.
 */

session_start();
require_once __DIR__ . '/config/db.php';

$itemId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

try {
    $db   = getDB();
    $stmt = $db->prepare(
        'SELECT i.*, c.name AS category_name, u.name AS seller_name
         FROM items i
         JOIN categories c ON c.id = i.category_id
         JOIN users u ON u.id = i.user_id
         WHERE i.id = ?'
    );
    $stmt->execute([$itemId]);
    $item = $stmt->fetch();
} catch (PDOException $e) {
    $item = null;
}

if (!$item) {
    http_response_code(404);
    $pageTitle = 'Barang tidak ditemukan';
    include __DIR__ . '/includes/header.php';
    echo '<div class="container page-wrap"><div class="empty-state"><i class="bi bi-exclamation-circle"></i><h3>Barang tidak ditemukan</h3><a href="/" class="btn btn-primary mt-2">Kembali ke Beranda</a></div></div>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

$pageTitle = $item['title'];

// Check if item is in wishlist (for logged-in users)
$wished = false;
if (isset($_SESSION['user_id'])) {
    try {
        $ws = $db->prepare('SELECT 1 FROM wishlist WHERE user_id = ? AND item_id = ?');
        $ws->execute([$_SESSION['user_id'], $itemId]);
        $wished = (bool) $ws->fetchColumn();
    } catch (PDOException $e) {}
}

include __DIR__ . '/includes/header.php';
?>

<main>
<div class="container">
<div class="item-detail-wrap">

    <!-- Left: Photo -->
    <div>
        <img
            src="/<?= htmlspecialchars($item['photo']) ?>"
            alt="<?= htmlspecialchars($item['title']) ?>"
            class="item-detail-img"
            onerror="this.src='/assets/img/sample/placeholder.svg'"
        >
    </div>

    <!-- Right: Info -->
    <div class="item-detail-info">

        <!-- Category & badges -->
        <div class="item-detail-meta">
            <span class="badge-condition"><?= htmlspecialchars($item['condition']) ?></span>
            <?php if ($item['is_sold']): ?>
                <span class="badge-sold">Terjual</span>
            <?php endif; ?>
            <span class="text-muted" style="font-size:13px;"><?= htmlspecialchars($item['category_name']) ?></span>
            <?php if ($item['subtype']): ?>
                <span class="text-muted" style="font-size:13px;">&bull; <?= htmlspecialchars($item['subtype']) ?></span>
            <?php endif; ?>
        </div>

        <h1 class="item-detail-title"><?= htmlspecialchars($item['title']) ?></h1>

        <div class="item-detail-price">Rp<?= number_format($item['price'], 0, ',', '.') ?></div>

        <!-- Meta: target, size, seller -->
        <div style="display:flex;flex-direction:column;gap:6px;font-size:14px;color:var(--color-gray);">
            <span><i class="bi bi-person-fill"></i> Target: <strong><?= htmlspecialchars($item['target']) ?></strong></span>
            <?php if ($item['size']): ?>
            <span><i class="bi bi-rulers"></i> Ukuran: <strong><?= htmlspecialchars($item['size']) ?></strong></span>
            <?php endif; ?>
            <span class="item-detail-seller">
                <i class="bi bi-shop"></i> Dijual oleh:
                <strong><?= htmlspecialchars($item['seller_name']) ?></strong>
            </span>
        </div>

        <!-- Description -->
        <div class="item-detail-desc">
            <?= nl2br(htmlspecialchars($item['description'] ?? '')) ?>
        </div>

        <!-- Action buttons -->
        <?php if (!$item['is_sold']): ?>
            <?php if (isset($_SESSION['user_id'])): ?>
            <div class="item-detail-actions">
                <button
                    id="addCartBtn"
                    class="btn btn-primary"
                    data-item-id="<?= $item['id'] ?>">
                    <i class="bi bi-bag-plus"></i> Tambah Keranjang
                </button>
                <button
                    id="addWishBtn"
                    class="btn <?= $wished ? 'btn-primary' : 'btn-outline' ?>"
                    data-item-id="<?= $item['id'] ?>"
                    data-wished="<?= $wished ? '1' : '0' ?>">
                    <i class="bi <?= $wished ? 'bi-heart-fill' : 'bi-heart' ?>"></i>
                    <?= $wished ? 'Di Wishlist' : 'Tambah Wishlist' ?>
                </button>
            </div>
            <?php else: ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i>
                <a href="/login.php">Masuk</a> atau <a href="/register.php">daftar</a> untuk membeli barang ini.
            </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle"></i>
                Barang ini sudah <strong>terjual</strong>.
            </div>
            <div class="item-detail-actions">
                <button class="btn btn-primary" disabled><i class="bi bi-bag-x"></i> Sudah Terjual</button>
                <button class="btn btn-outline" disabled><i class="bi bi-heart"></i> Tambah Wishlist</button>
            </div>
        <?php endif; ?>

    </div><!-- /info -->
</div><!-- /wrap -->
</div><!-- /container -->
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
