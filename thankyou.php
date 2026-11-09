<?php
/**
 * thankyou.php – Shown after successful payment confirmation.
 */

session_start();
require_once __DIR__ . '/config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /thriftzone/login.php');
    exit;
}

$pageTitle = 'Pesanan Dikonfirmasi';
$orderId   = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$userId    = (int)$_SESSION['user_id'];

try {
    $db   = getDB();
    $stmt = $db->prepare(
        'SELECT id, total, status, created_at, eta
         FROM orders
         WHERE id = ? AND user_id = ?'
    );
    $stmt->execute([$orderId, $userId]);
    $order = $stmt->fetch();
} catch (PDOException $e) {
    $order = null;
}

include __DIR__ . '/includes/header.php';
?>

<main>
<div class="container">
<div class="thankyou-wrap">

    <div class="thankyou-icon"><i class="bi bi-patch-check-fill"></i></div>

    <h1>Terima Kasih!</h1>

    <p>
        Pesananmu telah <?= ($order && $order['status'] !== 'Pending Payment') ? 'dikonfirmasi' : 'dibuat' ?> dan sedang diproses.
        Kami akan segera mengirimkan barangmu. 🎉
    </p>

    <?php if ($order): ?>
    <div style="background:var(--color-card);border-radius:var(--radius-lg);box-shadow:var(--shadow-sm);padding:24px;min-width:280px;text-align:left;">
        <div class="cart-summary-row">
            <span>Nomor Pesanan</span>
            <strong>#<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></strong>
        </div>
        <div class="cart-summary-row">
            <span>Total Bayar</span>
            <strong style="color:var(--color-teal);">Rp<?= number_format($order['total'], 0, ',', '.') ?></strong>
        </div>
        <div class="cart-summary-row">
            <span>Status</span>
            <span class="order-status status-<?= strtolower(str_replace(' ', '-', $order['status'])) ?>">
                <?= htmlspecialchars($order['status']) ?>
            </span>
        </div>
        <?php if (!empty($order['eta'])): ?>
        <div class="cart-summary-row" style="border:none;">
            <span>Estimasi Tiba</span>
            <strong><?= date('d M Y', strtotime($order['eta'])) ?></strong>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div style="display:flex;gap:12px;flex-wrap:wrap;justify-content:center;margin-top:8px;">
        <a href="/thriftzone/orders.php" class="btn btn-primary">
            <i class="bi bi-box-seam"></i> Lihat Pesanan Saya
        </a>
        <a href="/thriftzone/" class="btn btn-outline">
            <i class="bi bi-house"></i> Kembali ke Beranda
        </a>
    </div>

</div>
</div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

