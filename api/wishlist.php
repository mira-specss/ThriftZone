<?php
/**
 * api/wishlist.php – JSON API for wishlist operations.
 * Accepts POST JSON: { action: 'add'|'remove', item_id: N }
 * Returns: { success: bool, wished: bool, message: string }
 */

session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Silakan masuk terlebih dahulu.']);
    exit;
}

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
        $ins = $db->prepare('INSERT IGNORE INTO wishlist (user_id, item_id) VALUES (?, ?)');
        $ins->execute([$userId, $itemId]);
        echo json_encode(['success' => true, 'wished' => true, 'message' => 'Ditambahkan ke wishlist.']);
    } elseif ($action === 'remove') {
        $del = $db->prepare('DELETE FROM wishlist WHERE user_id = ? AND item_id = ?');
        $del->execute([$userId, $itemId]);
        echo json_encode(['success' => true, 'wished' => false, 'message' => 'Dihapus dari wishlist.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenal.']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}

