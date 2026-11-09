<?php
/**
 * payment.php – Simulated QRIS payment page.
 * Displays the QRIS image, an "I have paid" button and a "Cancel order" button.
 *
 * To replace with Midtrans:
 *   1. Install Midtrans PHP SDK via Composer.
 *   2. Create a Snap transaction using the order total.
 *   3. Embed the Snap JS and use the snap_token to show the payment UI.
 *   4. Handle the payment notification via api/payment.php webhook.
 */

session_start();
require_once __DIR__ . '/config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$pageTitle = 'Pembayaran';
$orderId   = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$userId    = (int)$_SESSION['user_id'];

try {
    $db   = getDB();
    $stmt = $db->prepare(
        "SELECT o.*, u.name AS buyer_name
         FROM orders o
         JOIN users u ON u.id = o.user_id
         WHERE o.id = ? AND o.user_id = ?"
    );
    $stmt->execute([$orderId, $userId]);
    $order = $stmt->fetch();
} catch (PDOException $e) {
    $order = null;
}

if (!$order) {
    header('Location: /orders.php');
    exit;
}

// Cancelled orders go back to My Orders
if ($order['status'] === 'Cancelled') {
    header('Location: /orders.php');
    exit;
}

// If already paid, go to thank-you
if ($order['status'] !== 'Pending Payment') {
    header('Location: /thankyou.php?order_id=' . $orderId);
    exit;
}

include __DIR__ . '/includes/header.php';
?>

<main>
<div class="container">
<div class="payment-wrap">

    <h1 style="font-size:24px;font-weight:700;">Selesaikan Pembayaran</h1>
    <p class="text-muted">Scan kode QRIS di bawah dengan aplikasi dompet digital atau m-banking kamu.</p>

    <!-- QRIS box -->
    <div class="qris-box">
        <div style="font-size:13px;font-weight:600;color:var(--color-gray);margin-bottom:10px;text-transform:uppercase;letter-spacing:.5px;">
            QRIS ThriftZone
        </div>

        <!-- QRIS image: file is at assets/img/qris.png -->
        <img
            src="/assets/img/qris.png"
            alt="QRIS Pembayaran"
            id="qrisImg"
            style="max-width:300px;margin-inline:auto;"
        >

        <div style="margin-top:16px;font-size:13px;color:var(--color-gray);">Total yang harus dibayar:</div>
        <div class="payment-total">Rp<?= number_format($order['total'], 0, ',', '.') ?></div>
        <div style="font-size:12px;color:var(--color-gray);margin-top:4px;">
            Order #<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?>
        </div>
    </div>

    <!-- Instructions -->
    <div style="max-width:400px;text-align:left;background:var(--color-card);border-radius:var(--radius-md);padding:20px;box-shadow:var(--shadow-sm);">
        <h4 style="font-size:14px;font-weight:700;margin-bottom:12px;"><i class="bi bi-info-circle-fill" style="color:var(--color-teal)"></i> Cara Bayar</h4>
        <ol style="padding-left:18px;font-size:13px;color:var(--color-gray);line-height:2;">
            <li>Buka aplikasi e-wallet atau m-banking.</li>
            <li>Pilih menu <strong>Scan QR / QRIS</strong>.</li>
            <li>Arahkan kamera ke kode QR di atas.</li>
            <li>Pastikan nominal sesuai, lalu konfirmasi pembayaran.</li>
            <li>Tekan tombol <strong>"Saya Sudah Bayar"</strong> di bawah.</li>
        </ol>
    </div>

    <!-- Confirm payment button -->
    <button
        id="paidBtn"
        class="btn btn-primary"
        data-order-id="<?= $order['id'] ?>"
        style="min-width:220px;padding:14px 32px;font-size:16px;"
    >
        <i class="bi bi-check-circle-fill"></i> Saya Sudah Bayar
    </button>

    <!-- Cancel order button (handled by main.js) -->
    <button
        type="button"
        class="btn btn-cancel cancel-order-btn"
        data-order-id="<?= $order['id'] ?>"
        style="min-width:220px;"
    >
        <i class="bi bi-x-circle"></i> Batalkan Pesanan
    </button>

    <a href="/orders.php" class="text-muted" style="font-size:13px;">
        Kembali ke Pesanan Saya
    </a>

</div>
</div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>