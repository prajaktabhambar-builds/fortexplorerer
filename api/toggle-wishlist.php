<?php
/**
 * FORT EXPLORER - AJAX Wishlist Toggle API
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (!is_logged_in()) {
    echo json_encode([
        'status' => 'unauthorized',
        'message' => 'Please sign in to save destinations to your wishlist.'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method.'
    ]);
    exit;
}

$destinationId = filter_input(INPUT_POST, 'destination_id', FILTER_VALIDATE_INT);
if (!$destinationId) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid destination ID.'
    ]);
    exit;
}

$db = getDB();
if (!$db) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database connection failed.'
    ]);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    // Check if already in wishlist
    $stmt = $db->prepare("SELECT id FROM wishlists WHERE user_id = ? AND destination_id = ?");
    $stmt->execute([$userId, $destinationId]);
    $existing = $stmt->fetch();

    if ($existing) {
        // Remove from wishlist
        $delStmt = $db->prepare("DELETE FROM wishlists WHERE id = ?");
        $delStmt->execute([$existing['id']]);

        echo json_encode([
            'status' => 'success',
            'action' => 'removed',
            'message' => 'Removed from your Wishlist.'
        ]);
    } else {
        // Add to wishlist
        $insStmt = $db->prepare("INSERT INTO wishlists (user_id, destination_id) VALUES (?, ?)");
        $insStmt->execute([$userId, $destinationId]);

        echo json_encode([
            'status' => 'success',
            'action' => 'added',
            'message' => 'Successfully saved to your Wishlist!'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database operation error: ' . $e->getMessage()
    ]);
}
