<?php
/**
 * FORT EXPLORER - Search Autocomplete API
 * Provides fast live search suggestions for destinations, forts, and districts.
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$query = trim($_GET['q'] ?? '');

if (strlen($query) < 2) {
    echo json_encode(['results' => []]);
    exit;
}

$db = getDB();
if (!$db) {
    echo json_encode(['results' => []]);
    exit;
}

try {
    $searchParam = '%' . $query . '%';
    $stmt = $db->prepare("
        SELECT d.id, d.name, d.name_mr, d.slug, d.primary_image, d.is_shivaji_fort, d.difficulty_level,
               c.name_en AS category_name, dist.name_en AS district_name
        FROM destinations d
        JOIN destination_categories c ON d.category_id = c.id
        JOIN districts dist ON d.district_id = dist.id
        WHERE d.name LIKE ? OR d.name_mr LIKE ? OR dist.name_en LIKE ? OR c.name_en LIKE ?
        ORDER BY d.is_shivaji_fort DESC, d.name ASC
        LIMIT 6
    ");
    $stmt->execute([$searchParam, $searchParam, $searchParam, $searchParam]);
    $items = $stmt->fetchAll();

    $results = [];
    foreach ($items as $item) {
        $results[] = [
            'id' => (int)$item['id'],
            'title' => $item['name'],
            'title_mr' => $item['name_mr'],
            'slug' => $item['slug'],
            'url' => 'destination.php?slug=' . urlencode($item['slug']),
            'image' => $item['primary_image'],
            'subtitle' => $item['district_name'] . ' • ' . $item['category_name'],
            'is_fort' => (bool)$item['is_shivaji_fort'],
            'difficulty' => $item['difficulty_level']
        ];
    }

    echo json_encode(['results' => $results]);
} catch (Exception $e) {
    echo json_encode(['results' => []]);
}
