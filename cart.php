<?php
/**
 * cart.php – Shopping cart page.
 * Displays items, allows removal, links to checkout.
 */

session_start();
require_once __DIR__ . '/config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$pageTitle = 'Keranjang';
$userId    = (int)$_SESSION['user_id'];

try {
    $db   = getDB();
    $stmt = $db->prepare(
        'SELECT i.id, i.title, i.price, i.photo, i.condition, i.is_sold
         FROM cart_items ci
         JOIN items i ON i.id = ci.item_id
         WHERE ci.user_id = ?
         ORDER BY ci.added_at DESC'
    );
    $stmt->execute([$userId]);
    $cartItems = $stmt->fetchAll();
} catch (PDOException $e) {
    $cartItems = [];
}

// Calculate subtotal (skip sold items)
$subtotal = 0;
foreach ($cartItems as $ci) {
    if (!$ci['is_sold']) $subtotal += $ci['price'];
}

include __DIR__ . '/includes/header.php';
?>

<main>
<div class="container page-wrap">

    <h1 class="section-title"><i class="bi bi-bag-fill" style="color:var(--color-teal)"></i> Keranjang Belanja</h1>

    <?php if (empty($cartItems)): ?>
        <div class="empty-state">
            <i class="bi bi-bag"></i>
            <h3>Keranjang masih kosong</h3>
            <p>Tambahkan barang dari halaman detail untuk mulai berbelanja.</p>
            <a href="/" class="btn btn-primary mt-2">Belanja Sekarang</a>
        </div>
    <?php else: ?>

        <!-- Cart table -->
        <div style="overflow-x:auto;">
        <table class="cart-table">
            <thead>
                <tr>
                    <th colspan="2">Barang</th>
                    <th>Kondisi</th>
                    <th>Harga</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($cartItems as $ci): ?>
                <tr id="cart-row-<?= $ci['id'] ?>">
                    <td style="width:70px;">
                        <img
                            src="/<?= htmlspecialchars($ci['photo']) ?>"
                            alt="<?= htmlspecialchars($ci['title']) ?>"
                            class="cart-thumb"
                            onerror="this.src='/assets/img/sample/placeholder.svg'"
                        >
                    </td>
                    <td>
                        <a href="/item.php?id=<?= $ci['id'] ?>" style="font-weight:600;color:var(--color-navy);">
                            <?= htmlspecialchars($ci['title']) ?>
                        </a>
                    </td>
                    <td><?= htmlspecialchars($ci['condition']) ?></td>
                    <td class="fw-bold" style="color:var(--color-teal);">
                        Rp<?= number_format($ci['price'], 0, ',', '.') ?>
                    </td>
                    <td>
                        <?php if ($ci['is_sold']): ?>
                            <span class="badge-sold">Terjual</span>
                        <?php else: ?>
                            <span class="badge-condition">Tersedia</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <button
                            class="btn btn-danger btn-sm remove-cart-btn"
                            data-item-id="<?= $ci['id'] ?>"
                            aria-label="Hapus dari keranjang"
                        >
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>

        <!-- Summary -->
        <div class="cart-summary">
            <div class="cart-summary-row">
                <span>Subtotal</span>
                <span id="cartSubtotal">Rp<?= number_format($subtotal, 0, ',', '.') ?></span>
            </div>
            <div class="cart-summary-row">
                <span>Ongkos Kirim</span>
                <span>Rp10.000</span>
            </div>
            <div class="cart-summary-row">
                <span>Total</span>
                <span style="color:var(--color-teal);">Rp<?= number_format($subtotal + 10000, 0, ',', '.') ?></span>
            </div>
            <a href="/checkout.php" class="btn btn-primary" style="width:100%;margin-top:12px;">
                <i class="bi bi-credit-card"></i> Lanjut ke Checkout
            </a>
        </div>

    <?php endif; ?>

</div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
