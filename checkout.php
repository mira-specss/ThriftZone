<?php
/**
 * checkout.php – Order form: collects address details and creates the order.
 * Validates on both client (JS in main.js) and server.
 */

session_start();
require_once __DIR__ . '/config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$pageTitle = 'Checkout';
$userId    = (int)$_SESSION['user_id'];

// Load cart items
try {
    $db   = getDB();
    $stmt = $db->prepare(
        'SELECT i.id, i.title, i.price, i.photo, i.is_sold
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

// Redirect if cart is empty
if (empty($cartItems)) {
    header('Location: /cart.php');
    exit;
}

// Filter out sold items
$cartItems = array_filter($cartItems, fn($ci) => !$ci['is_sold']);
if (empty($cartItems)) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Semua barang di keranjang sudah terjual.'];
    header('Location: /cart.php');
    exit;
}

$subtotal = array_sum(array_column($cartItems, 'price'));
$shipping = 10000;
$total    = $subtotal + $shipping;

$errors = [];
$old    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['fullname'] = trim($_POST['fullname'] ?? '');
    $old['address']  = trim($_POST['address']  ?? '');
    $old['phone']    = trim($_POST['phone']    ?? '');
    $old['postal']   = trim($_POST['postal']   ?? '');

    // ── Server-side validation ──────────────────────────────
    if (mb_strlen($old['fullname']) < 3) {
        $errors['fullname'] = 'Nama lengkap minimal 3 karakter.';
    }
    if (mb_strlen($old['address']) < 10) {
        $errors['address'] = 'Alamat terlalu singkat.';
    }
    if (!preg_match('/^\d{8,15}$/', preg_replace('/\s+/', '', $old['phone']))) {
        $errors['phone'] = 'Nomor telepon harus 8–15 digit angka.';
    }
    if (!preg_match('/^\d{5}$/', $old['postal'])) {
        $errors['postal'] = 'Kode pos harus 5 digit angka.';
    }

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            // 1. Create order
            $ins = $db->prepare(
                'INSERT INTO orders (user_id, fullname, address, phone, postal, subtotal, shipping, total)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $ins->execute([
                $userId,
                $old['fullname'],
                $old['address'],
                $old['phone'],
                $old['postal'],
                $subtotal,
                $shipping,
                $total,
            ]);
            $orderId = (int)$db->lastInsertId();

            // 2. Insert order items and mark items as sold
            $insOI  = $db->prepare('INSERT INTO order_items (order_id, item_id, price) VALUES (?, ?, ?)');
            $markSold = $db->prepare('UPDATE items SET is_sold = 1 WHERE id = ?');
            $delCart  = $db->prepare('DELETE FROM cart_items WHERE user_id = ? AND item_id = ?');

            foreach ($cartItems as $ci) {
                $insOI->execute([$orderId, $ci['id'], $ci['price']]);
                $markSold->execute([$ci['id']]);
                $delCart->execute([$userId, $ci['id']]);
            }

            $db->commit();

            // Redirect to payment
            header('Location: /payment.php?order_id=' . $orderId);
            exit;

        } catch (PDOException $e) {
            $db->rollBack();
            $errors['general'] = 'Gagal membuat pesanan. Silakan coba lagi.';
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<main>
<div class="container">
<div class="checkout-wrap">

    <!-- Left: Address form -->
    <div class="checkout-form-card">
        <h3><i class="bi bi-geo-alt-fill" style="color:var(--color-teal)"></i> Alamat Pengiriman</h3>

        <?php if (!empty($errors['general'])): ?>
            <div class="alert alert-error"><i class="bi bi-x-circle"></i> <?= htmlspecialchars($errors['general']) ?></div>
        <?php endif; ?>

        <form method="POST" action="" id="checkoutForm">

            <div class="form-group">
                <label class="form-label" for="fullname">Nama Lengkap</label>
                <input
                    type="text" id="fullname" name="fullname"
                    class="form-control <?= isset($errors['fullname']) ? 'is-invalid' : '' ?>"
                    value="<?= htmlspecialchars($old['fullname'] ?? '') ?>"
                    placeholder="Nama penerima"
                    required
                >
                <?php if (isset($errors['fullname'])): ?>
                    <div class="invalid-feedback" style="display:block;"><?= htmlspecialchars($errors['fullname']) ?></div>
                <?php endif; ?>
                <div class="invalid-feedback">Nama lengkap wajib diisi.</div>
            </div>

            <div class="form-group">
                <label class="form-label" for="phone">Nomor Telepon</label>
                <input
                    type="tel" id="phone" name="phone"
                    class="form-control <?= isset($errors['phone']) ? 'is-invalid' : '' ?>"
                    value="<?= htmlspecialchars($old['phone'] ?? '') ?>"
                    placeholder="081234567890"
                    required
                >
                <?php if (isset($errors['phone'])): ?>
                    <div class="invalid-feedback" style="display:block;"><?= htmlspecialchars($errors['phone']) ?></div>
                <?php endif; ?>
                <div class="invalid-feedback">Nomor telepon tidak valid.</div>
            </div>

            <div class="form-group">
                <label class="form-label" for="address">Alamat Lengkap</label>
                <textarea
                    id="address" name="address"
                    class="form-control <?= isset($errors['address']) ? 'is-invalid' : '' ?>"
                    placeholder="Jalan, nomor rumah, kelurahan, kecamatan, kota/kabupaten, provinsi"
                    rows="4"
                    required
                ><?= htmlspecialchars($old['address'] ?? '') ?></textarea>
                <?php if (isset($errors['address'])): ?>
                    <div class="invalid-feedback" style="display:block;"><?= htmlspecialchars($errors['address']) ?></div>
                <?php endif; ?>
                <div class="invalid-feedback">Alamat wajib diisi.</div>
            </div>

            <div class="form-group">
                <label class="form-label" for="postal">Kode Pos</label>
                <input
                    type="text" id="postal" name="postal"
                    class="form-control <?= isset($errors['postal']) ? 'is-invalid' : '' ?>"
                    value="<?= htmlspecialchars($old['postal'] ?? '') ?>"
                    placeholder="12345"
                    maxlength="5"
                    required
                >
                <?php if (isset($errors['postal'])): ?>
                    <div class="invalid-feedback" style="display:block;"><?= htmlspecialchars($errors['postal']) ?></div>
                <?php endif; ?>
                <div class="invalid-feedback">Kode pos harus 5 digit angka.</div>
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%;">
                <i class="bi bi-lock-fill"></i> Buat Pesanan &amp; Bayar
            </button>
        </form>
    </div>

    <!-- Right: Order summary -->
    <div class="checkout-summary-card">
        <h3><i class="bi bi-receipt" style="color:var(--color-teal)"></i> Ringkasan Pesanan</h3>

        <!-- Items -->
        <?php foreach ($cartItems as $ci): ?>
        <div class="order-item-row">
            <img
                src="/<?= htmlspecialchars($ci['photo']) ?>"
                alt="<?= htmlspecialchars($ci['title']) ?>"
                class="order-thumb"
                onerror="this.src='/assets/img/sample/placeholder.svg'"
            >
            <div style="flex:1;min-width:0;">
                <div style="font-weight:600;font-size:14px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                    <?= htmlspecialchars($ci['title']) ?>
                </div>
                <div style="color:var(--color-teal);font-weight:700;font-size:14px;">
                    Rp<?= number_format($ci['price'], 0, ',', '.') ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <!-- Totals -->
        <div style="margin-top:16px;">
            <div class="cart-summary-row">
                <span>Subtotal</span>
                <span>Rp<?= number_format($subtotal, 0, ',', '.') ?></span>
            </div>
            <div class="cart-summary-row">
                <span>Ongkos Kirim</span>
                <span>Rp<?= number_format($shipping, 0, ',', '.') ?></span>
            </div>
            <div class="cart-summary-row">
                <span style="font-weight:700;">Total</span>
                <span style="color:var(--color-teal);font-weight:700;font-size:17px;">
                    Rp<?= number_format($total, 0, ',', '.') ?>
                </span>
            </div>
        </div>
    </div>

</div>
</div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
