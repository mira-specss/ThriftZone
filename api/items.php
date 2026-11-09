<?php
/**
 * api/items.php – JSON endpoint for category page filtering.
 * Accepts GET params: category_id, target, subtype, condition, sort
 * Returns: { "items": [...] }
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';

try {
    $db = getDB();

    // ── Build query dynamically ──────────────────────────────
    $where  = ['i.category_id = :cat'];
    $params = [':cat' => (int)($_GET['category_id'] ?? 1)];

    // Target filter (comma-separated values)
    if (!empty($_GET['target'])) {
        $targets    = array_filter(explode(',', $_GET['target']));
        $validTargets = ['Women','Men','Unisex','Toddler'];
        $targets    = array_intersect($targets, $validTargets);
        if ($targets) {
            $in = implode(',', array_fill(0, count($targets), '?'));
            $where[] = "i.target IN ($in)";
            // We'll append positional params below
        }
    }

    // Subtype filter
    if (!empty($_GET['subtype'])) {
        $subtypes = array_filter(explode(',', $_GET['subtype']));
        if ($subtypes) {
            $in = implode(',', array_fill(0, count($subtypes), '?'));
            $where[] = "i.subtype IN ($in)";
        }
    }

    // Condition filter
    if (!empty($_GET['condition'])) {
        $conditions = array_filter(explode(',', $_GET['condition']));
        $validCond  = ['Like new','Good','Fair'];
        $conditions = array_intersect($conditions, $validCond);
        if ($conditions) {
            $in = implode(',', array_fill(0, count($conditions), '?'));
            $where[] = "i.`condition` IN ($in)";
        }
    }

    // Sort
    $sortMap = [
        'newest'     => 'i.created_at DESC',
        'price_asc'  => 'i.price ASC',
        'price_desc' => 'i.price DESC',
    ];
    $sort    = $sortMap[$_GET['sort'] ?? 'newest'] ?? 'i.created_at DESC';

    $sql = 'SELECT i.id, i.title, i.price, i.photo, i.condition,
                   i.target, i.subtype, i.is_sold, c.name AS category_name
            FROM items i
            JOIN categories c ON c.id = i.category_id
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY ' . $sort . '
            LIMIT 100';

    // Build positional param array for the IN clauses
    $posParams = [];
    if (!empty($targets))    $posParams = array_merge($posParams, array_values($targets));
    if (!empty($subtypes))   $posParams = array_merge($posParams, array_values($subtypes));
    if (!empty($conditions)) $posParams = array_merge($posParams, array_values($conditions));

    // We used named param for category, switch to full positional
    // Rebuild query with all positional params
    $sql2 = 'SELECT i.id, i.title, i.price, i.photo, i.condition,
                    i.target, i.subtype, i.is_sold, c.name AS category_name
             FROM items i
             JOIN categories c ON c.id = i.category_id
             WHERE i.category_id = ?';

    $allParams = [(int)($_GET['category_id'] ?? 1)];

    if (!empty($targets)) {
        $in = implode(',', array_fill(0, count($targets), '?'));
        $sql2 .= " AND i.target IN ($in)";
        $allParams = array_merge($allParams, array_values($targets));
    }
    if (!empty($subtypes)) {
        $in = implode(',', array_fill(0, count($subtypes), '?'));
        $sql2 .= " AND i.subtype IN ($in)";
        $allParams = array_merge($allParams, array_values($subtypes));
    }
    if (!empty($conditions)) {
        $in = implode(',', array_fill(0, count($conditions), '?'));
        $sql2 .= " AND i.`condition` IN ($in)";
        $allParams = array_merge($allParams, array_values($conditions));
    }

    $sql2 .= ' ORDER BY ' . $sort . ' LIMIT 100';

    $stmt  = $db->prepare($sql2);
    $stmt->execute($allParams);
    $items = $stmt->fetchAll();

    echo json_encode(['items' => $items], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error', 'items' => []]);
}

