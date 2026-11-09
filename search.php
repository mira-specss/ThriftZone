<?php
/**
 * search.php – Full-text search results.
 * Searches item titles and descriptions.
 */

session_start();
require_once __DIR__ . '/config/db.php';

$pageTitle = 'Cari Barang';
$query     = trim($_GET['q'] ?? '');
$items     = [];

if ($query !== '') {
    try {
        $db   = getDB();
        $like = '%' . $query . '%';
        $stmt = $db->prepare(
            'SELECT i.id, i.title, i.price, i.photo, i.condition, i.is_sold, c.name AS category_name
             FROM items i
             JOIN categories c ON c.id = i.category_id
             WHERE i.title LIKE ? OR i.description LIKE ?
             ORDER BY i.created_at DESC
             LIMIT 60'
        );
        $stmt->execute([$like, $like]);
        $items = $stmt->fetchAll();
    } catch (PDOException $e) {
        $items = [];
    }
}

include __DIR__ . '/includes/header.php';
?>

<main>
<div class="container page-wrap">

    <h1 class="section-title">
        <i class="bi bi-search" style="color:var(--color-teal)"></i>
        Hasil pencarian untuk: "<?= htmlspecialchars($query) ?>"
    </h1>

    <?php if ($query === ''): ?>
        <div class="alert alert-info"><i class="bi bi-info-circle"></i> Masukkan kata kunci di kotak pencarian di atas.</div>
    <?php elseif (empty($items)): ?>
        <div class="empty-state">
            <i class="bi bi-search"></i>
            <h3>Barang tidak ditemukan</h3>
            <p>Coba kata kunci yang berbeda atau jelajahi kategori.</p>
            <a href="/" class="btn btn-primary mt-2">Kembali ke Beranda</a>
        </div>
    <?php else: ?>
        <p class="text-muted mb-2"><?= count($items) ?> barang ditemukan</p>
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
                            <?php if ($item['is_sold']): ?>
                                <span class="badge-sold">Terjual</span>
                            <?php else: ?>
                                <span class="badge-condition"><?= htmlspecialchars($item['condition']) ?></span>
                            <?php endif; ?>
                            <span><?= htmlspecialchars($item['category_name']) ?></span>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
