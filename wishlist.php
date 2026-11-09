<?php
/**
 * wishlist.php – Shows the logged-in user's wishlist.
 */

session_start();
require_once __DIR__ . '/config/db.php';

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$pageTitle = 'Wishlist';
$userId    = (int)$_SESSION['user_id'];

try {
    $db   = getDB();
    $stmt = $db->prepare(
        'SELECT i.id, i.title, i.price, i.photo, i.condition, i.is_sold, c.name AS category_name
         FROM wishlist w
         JOIN items i ON i.id = w.item_id
         JOIN categories c ON c.id = i.category_id
         WHERE w.user_id = ?
         ORDER BY w.created_at DESC'
    );
    $stmt->execute([$userId]);
    $items = $stmt->fetchAll();
} catch (PDOException $e) {
    $items = [];
}

include __DIR__ . '/includes/header.php';
?>

<main>
<div class="container page-wrap">

    <h1 class="section-title"><i class="bi bi-heart-fill" style="color:var(--color-teal)"></i> Wishlist Saya</h1>

    <?php if (empty($items)): ?>
        <div class="empty-state">
            <i class="bi bi-heart"></i>
            <h3>Wishlist masih kosong</h3>
            <p>Tambahkan barang yang kamu suka ke wishlist dari halaman detail barang.</p>
            <a href="/" class="btn btn-primary mt-2">Jelajahi Barang</a>
        </div>
    <?php else: ?>
        <div class="items-grid" id="wishlistGrid">
            <?php foreach ($items as $item): ?>
            <div class="item-card-wrap" style="position:relative;">
                <a href="/item.php?id=<?= $item['id'] ?>" class="item-card">
                    <img
                        src="/<?= htmlspecialchars($item['photo']) ?>"
                        alt="<?= htmlspecialchars($item['title']) ?>"
                        class="item-card-img"
                        onerror="this.src='/assets/img/sample/placeholder.svg'"
                    >
                    <div class="item-card-body">
                        <div class="item-card-title"><?= htmlspecialchars($item['title']) ?></div>
                        <div class="item-card-price">Rp<?= number_format($item['price'], 0, ',', '.') ?></div>
                        <div class="item-card-meta">
                            <?php if ($item['is_sold']): ?>
                                <span class="badge-sold">Terjual</span>
                            <?php else: ?>
                                <span class="badge-condition"><?= htmlspecialchars($item['condition']) ?></span>
                            <?php endif; ?>
                            <span><?= htmlspecialchars($item['category_name']) ?></span>
                        </div>
                    </div>
                </a>
                <!-- Remove button -->
                <button
                    class="btn btn-danger btn-sm remove-wish-btn"
                    data-item-id="<?= $item['id'] ?>"
                    style="position:absolute;top:8px;right:8px;padding:4px 8px;border-radius:50px;"
                    title="Hapus dari Wishlist"
                    aria-label="Hapus dari Wishlist"
                >
                    <i class="bi bi-x"></i>
                </button>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
