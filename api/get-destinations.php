<?php
/**
 * FORT EXPLORER - JSON Destinations Endpoint (for Leaflet Map & Search)
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$db = getDB();
if (!$db) {
    echo json_encode(['error' => 'Database connection unavailable']);
    exit;
}

$categorySlug = $_GET['category'] ?? null;
$districtId = $_GET['district'] ?? null;
$onlyForts = isset($_GET['forts']) && $_GET['forts'] == 1;

$query = "
    SELECT 
        d.id, d.name, d.name_mr, d.slug, d.short_description, d.short_description_mr,
        d.latitude, d.longitude, d.altitude_meters, d.difficulty_level, d.primary_image,
        d.is_shivaji_fort, d.is_featured,
        c.slug AS category_slug, c.name_en AS category_name, c.name_mr AS category_name_mr,
        dist.name_en AS district_name, dist.name_mr AS district_name_mr
    FROM destinations d
    JOIN destination_categories c ON d.category_id = c.id
    JOIN districts dist ON d.district_id = dist.id
    WHERE 1=1
";
$params = [];

if ($onlyForts) {
    $query .= " AND d.is_shivaji_fort = 1";
}
if (!empty($categorySlug) && $categorySlug !== 'all') {
    $query .= " AND c.slug = ?";
    $params[] = $categorySlug;
}
if (!empty($districtId) && is_numeric($districtId)) {
    $query .= " AND d.district_id = ?";
    $params[] = (int)$districtId;
}

$query .= " ORDER BY d.is_featured DESC, d.name ASC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$destinations = $stmt->fetchAll();

echo json_encode($destinations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
