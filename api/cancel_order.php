<?php
/**
 * api/cancel_order.php – Cancel a pending order.
 * POST JSON: { "order_id": 123 }
 * Rules:
 *   - user must be logged in and own the order
 *   - only orders with status "Pending Payment" can be cancelled
 *   - items of the order become available again (is_sold = 0)
 */

session_start();
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Silakan login terlebih dahulu.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metode tidak diizinkan.']);
    exit;
}

$input   = json_decode(file_get_contents('php://input'), true);
$orderId = (int) ($input['order_id'] ?? 0);
$userId  = (int) $_SESSION['user_id'];

if ($orderId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Pesanan tidak valid.']);
    exit;
}

try {
    $db = getDB();
    $db->beginTransaction();

    // Lock the order row so it cannot be paid and cancelled at the same time
    $stmt = $db->prepare('SELECT id, status FROM orders WHERE id = ? AND user_id = ? FOR UPDATE');
    $stmt->execute([$orderId, $userId]);
    $order = $stmt->fetch();

    if (!$order) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Pesanan tidak ditemukan.']);
        exit;
    }

    if ($order['status'] !== 'Pending Payment') {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Hanya pesanan yang belum dibayar yang bisa dibatalkan.']);
        exit;
    }

    // 1) Mark the order as cancelled
    $db->prepare("UPDATE orders SET status = 'Cancelled' WHERE id = ?")->execute([$orderId]);

    // 2) Make the items available again
    $db->prepare(
        'UPDATE items SET is_sold = 0
         WHERE id IN (SELECT item_id FROM order_items WHERE order_id = ?)'
    )->execute([$orderId]);

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Pesanan dibatalkan.']);

} catch (PDOException $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan server.']);
}