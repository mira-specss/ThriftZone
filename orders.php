<?php
/**
 * orders.php – My Orders page.
 * Lists all orders for the logged-in user with status, items, total, ETA
 * and a cancel button for orders that are still pending payment.
 */

session_start();
require_once __DIR__ . '/config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /thriftzone/login.php');
    exit;
}

$pageTitle = 'Pesanan Saya';
$userId    = (int)$_SESSION['user_id'];

try {
    $db = getDB();

    // Load orders (newest first)
    $stmt = $db->prepare(
        'SELECT id, fullname, address, phone, subtotal, shipping, total, status, created_at, eta
         FROM orders
         WHERE user_id = ?
         ORDER BY created_at DESC'
    );
    $stmt->execute([$userId]);
    $orders = $stmt->fetchAll();

    // For each order, load its items
    $orderItemsStmt = $db->prepare(
        'SELECT oi.price, i.title, i.photo
         FROM order_items oi
         JOIN items i ON i.id = oi.item_id
         WHERE oi.order_id = ?'
    );
} catch (PDOException $e) {
    $orders = [];
}

// Status → CSS class map
$statusClass = [
    'Pending Payment' => 'status-pending',
    'Paid'            => 'status-paid',
    'Packed'          => 'status-packed',
    'Shipped'         => 'status-shipped',
    'Delivered'       => 'status-delivered',
    'Cancelled'       => 'status-cancelled',
];

include __DIR__ . '/includes/header.php';
?>

<main>
<div class="container page-wrap">

    <h1 class="section-title"><i class="bi bi-box-seam-fill" style="color:var(--color-teal)"></i> Pesanan Saya</h1>

    <?php if (empty($orders)): ?>
        <div class="empty-state">
            <i class="bi bi-box-seam"></i>
            <h3>Belum ada pesanan</h3>
            <p>Yuk, mulai belanja barang preloved favoritmu!</p>
            <a href="/thriftzone/" class="btn btn-primary mt-2">Belanja Sekarang</a>
        </div>
    <?php else: ?>
        <?php foreach ($orders as $order): ?>
            <?php
                // Load items for this order
                $orderItemsStmt->execute([$order['id']]);
                $orderItems = $orderItemsStmt->fetchAll();
                $css        = $statusClass[$order['status']] ?? 'status-pending';
                $cancelled  = ($order['status'] === 'Cancelled');
            ?>
            <div class="order-card"<?= $cancelled ? ' style="opacity:.8;"' : '' ?>>
                <!-- Order header -->
                <div class="order-card-header">
                    <div>
                        <div class="order-number">
                            Pesanan #<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?>
                        </div>
                        <div style="font-size:12px;color:var(--color-gray);">
                            <?= date('d M Y, H:i', strtotime($order['created_at'])) ?>
                        </div>
                    </div>
                    <span class="order-status <?= $css ?>">
                        <?= htmlspecialchars($order['status']) ?>
                    </span>
                </div>

                <!-- Order items thumbnails -->
                <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:14px;">
                    <?php foreach ($orderItems as $oi): ?>
                    <div style="display:flex;align-items:center;gap:8px;background:var(--color-bg);border-radius:var(--radius-sm);padding:6px 10px;font-size:13px;">
                        <img
                            src="/thriftzone/<?= htmlspecialchars($oi['photo']) ?>"
                            alt="<?= htmlspecialchars($oi['title']) ?>"
                            style="width:40px;height:40px;object-fit:cover;border-radius:4px;"
                            onerror="this.src='/thriftzone/assets/img/sample/placeholder.svg'"
                        >
                        <div>
                            <div style="font-weight:600;"><?= htmlspecialchars($oi['title']) ?></div>
                            <div style="color:var(--color-teal);">Rp<?= number_format($oi['price'], 0, ',', '.') ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Order footer: total + ETA + actions -->
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;border-top:1px solid var(--color-border);padding-top:12px;">
                    <div>
                        <div style="font-size:13px;color:var(--color-gray);">
                            Dikirim ke: <strong><?= htmlspecialchars($order['fullname']) ?></strong>
                        </div>
                        <?php if (!empty($order['eta']) && !$cancelled): ?>
                        <div style="font-size:13px;color:var(--color-gray);">
                            Estimasi tiba: <strong><?= date('d M Y', strtotime($order['eta'])) ?></strong>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div style="text-align:right;">
                        <div style="font-size:13px;color:var(--color-gray);">Total</div>
                        <div style="font-size:18px;font-weight:700;color:var(--color-teal);">
                            Rp<?= number_format($order['total'], 0, ',', '.') ?>
                        </div>
                        <?php if ($order['status'] === 'Pending Payment'): ?>
                        <div style="display:flex;gap:8px;justify-content:flex-end;flex-wrap:wrap;margin-top:8px;">
                            <button type="button" class="btn btn-cancel btn-sm cancel-order-btn"
                                    data-order-id="<?= $order['id'] ?>">
                                <i class="bi bi-x-circle"></i> Batalkan
                            </button>
                            <a href="/thriftzone/payment.php?order_id=<?= $order['id'] ?>" class="btn btn-primary btn-sm">
                                <i class="bi bi-credit-card"></i> Bayar Sekarang
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

</div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>