<?php
/**
 * api/payment.php – Marks an order as "Paid" after simulated QRIS payment.
 * Accepts POST JSON: { order_id: N }
 * Returns: { success: bool, message: string }
 *
 * To integrate a real payment gateway (e.g. Midtrans):
 *   1. Verify the payment notification from Midtrans webhook here.
 *   2. Check the order_id and amount match.
 *   3. Update status to 'Paid' only after verification.
 */

session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Tidak diizinkan.']);
    exit;
}

$body    = json_decode(file_get_contents('php://input'), true);
$orderId = (int)($body['order_id'] ?? 0);
$userId  = (int)$_SESSION['user_id'];

if (!$orderId) {
    echo json_encode(['success' => false, 'message' => 'Order tidak valid.']);
    exit;
}

try {
    $db = getDB();

    // Ensure the order belongs to the logged-in user and is still Pending
    $stmt = $db->prepare(
        "SELECT id FROM orders WHERE id = ? AND user_id = ? AND status = 'Pending Payment'"
    );
    $stmt->execute([$orderId, $userId]);
    $order = $stmt->fetch();

    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order tidak ditemukan atau sudah diproses.']);
        exit;
    }

    // Mark as Paid
    $upd = $db->prepare("UPDATE orders SET status = 'Paid' WHERE id = ?");
    $upd->execute([$orderId]);

    echo json_encode(['success' => true, 'message' => 'Pembayaran berhasil dicatat.']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}

