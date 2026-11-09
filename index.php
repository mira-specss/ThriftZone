<?php
/**
 * index.php – ThriftZone Landing Page
 * Shows a randomised grid of up to 12 recent available items.
 */

session_start();
require_once __DIR__ . '/config/db.php';

$pageTitle = 'Beranda';

// Fetch the latest 40 available items, then shuffle and show 12
try {
    $db    = getDB();
    $stmt  = $db->query(
        'SELECT i.*, c.name AS category_name
         FROM items i
         JOIN categories c ON c.id = i.category_id
         WHERE i.is_sold = 0
         ORDER BY i.created_at DESC
         LIMIT 40'
    );
    $items = $stmt->fetchAll();
    shuffle($items);
    $items = array_slice($items, 0, 12);
} catch (PDOException $e) {
    $items = [];
}

include __DIR__ . '/includes/header.php';
?>

<main>
    <!-- Hero banner -->
    <section class="hero">
        <div class="container">
            <h1>Belanja Preloved, <span style="color:var(--color-teal-light)">Hemat &amp; Berkelas</span></h1>
            <p>Temukan barang second-hand berkualitas dari seluruh Indonesia. Jual barangmu yang tidak terpakai sekarang!</p>
            <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
                <a href="/category.php?id=1" class="btn btn-primary">Jelajahi Barang</a>
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <a href="/register.php" class="btn btn-secondary" style="background:rgba(255,255,255,.1);color:#fff;border-color:rgba(255,255,255,.4);">Daftar Gratis</a>
                <?php else: ?>
                    <a href="/sell.php" class="btn btn-secondary" style="background:rgba(255,255,255,.1);color:#fff;border-color:rgba(255,255,255,.4);">Jual Barang</a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <div class="container page-wrap">

        <!-- Flash messages -->
        <?php if (!empty($_SESSION['flash'])): ?>
            <div class="alert alert-<?= htmlspecialchars($_SESSION['flash']['type']) ?>">
                <i class="bi bi-info-circle"></i>
                <?= htmlspecialchars($_SESSION['flash']['message']) ?>
            </div>
            <?php unset($_SESSION['flash']); ?>
        <?php endif; ?>

        <h2 class="section-title"><i class="bi bi-lightning-charge-fill" style="color:var(--color-amber)"></i> Barang Terbaru</h2>

        <?php if (empty($items)): ?>
            <div class="empty-state">
                <i class="bi bi-box-open"></i>
                <h3>Belum ada barang</h3>
                <p>Import <code>database.sql</code> untuk menambahkan data contoh, atau jadilah yang pertama berjualan!</p>
                <a href="/sell.php" class="btn btn-primary mt-2">Jual Sekarang</a>
            </div>
        <?php else: ?>
            <div class="items-grid">
                <?php foreach ($items as $item): ?>
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
                                <span class="badge-condition"><?= htmlspecialchars($item['condition']) ?></span>
                                <span><?= htmlspecialchars($item['category_name']) ?></span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>

            <div style="text-align:center;margin-top:32px;">
                <a href="/category.php?id=1" class="btn btn-outline">Lihat Semua Barang</a>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
