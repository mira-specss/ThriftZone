<?php
/**
 * api/cart.php – JSON API for cart operations.
 * Accepts POST JSON: { action: 'add'|'remove', item_id: N }
 * Returns: { success: bool, message: string, cart_count: N, subtotal: N }
 */

session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Silakan masuk terlebih dahulu.']);
    exit;
}

// Parse JSON body
$body   = json_decode(file_get_contents('php://input'), true);
$action = $body['action'] ?? '';
$itemId = (int)($body['item_id'] ?? 0);
$userId = (int)$_SESSION['user_id'];

if (!$itemId) {
    echo json_encode(['success' => false, 'message' => 'Item tidak valid.']);
    exit;
}

try {
    $db = getDB();

    if ($action === 'add') {
        // Check if item exists and not sold
        $stmt = $db->prepare('SELECT id, is_sold FROM items WHERE id = ?');
        $stmt->execute([$itemId]);
        $item = $stmt->fetch();

        if (!$item) {
            echo json_encode(['success' => false, 'message' => 'Barang tidak ditemukan.']);
            exit;
        }
        if ($item['is_sold']) {
            echo json_encode(['success' => false, 'message' => 'Barang sudah terjual.']);
            exit;
        }

        // Insert or ignore if already in cart
        $ins = $db->prepare(
            'INSERT IGNORE INTO cart_items (user_id, item_id) VALUES (?, ?)'
        );
        $ins->execute([$userId, $itemId]);

    } elseif ($action === 'remove') {
        $del = $db->prepare('DELETE FROM cart_items WHERE user_id = ? AND item_id = ?');
        $del->execute([$userId, $itemId]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenal.']);
        exit;
    }

    // Return updated cart count and subtotal
    $countStmt = $db->prepare('SELECT COUNT(*) FROM cart_items WHERE user_id = ?');
    $countStmt->execute([$userId]);
    $cartCount = (int)$countStmt->fetchColumn();

    // Subtotal
    $subStmt = $db->prepare(
        'SELECT COALESCE(SUM(i.price),0)
         FROM cart_items ci
         JOIN items i ON i.id = ci.item_id
         WHERE ci.user_id = ?'
    );
    $subStmt->execute([$userId]);
    $subtotal = (int)$subStmt->fetchColumn();

    echo json_encode([
        'success'    => true,
        'message'    => $action === 'add' ? 'Ditambahkan ke keranjang.' : 'Dihapus dari keranjang.',
        'cart_count' => $cartCount,
        'subtotal'   => $subtotal,
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}

