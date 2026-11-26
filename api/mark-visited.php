<?php
/**
 * FORT EXPLORER - Mark Visited Destination API with Gamification Achievement Awarder
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (!is_logged_in()) {
    echo json_encode([
        'status' => 'unauthorized',
        'message' => 'Please sign in to record your visited destinations.'
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
$visitDate = sanitize_input($_POST['visit_date'] ?? date('Y-m-d'));
$rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT) ?: 5;
$notes = sanitize_input($_POST['experience_notes'] ?? '');
$companion = sanitize_input($_POST['companion_type'] ?? 'Solo');
$experience = sanitize_input($_POST['trek_experience'] ?? 'Pleasant');

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
    // Check if already visited
    $stmt = $db->prepare("SELECT id FROM visited_places WHERE user_id = ? AND destination_id = ?");
    $stmt->execute([$userId, $destinationId]);
    $existing = $stmt->fetch();

    $actionTaken = 'created';
    if ($existing) {
        $upd = $db->prepare("
            UPDATE visited_places 
            SET visit_date = ?, rating = ?, experience_notes = ?, companion_type = ?, trek_experience = ? 
            WHERE id = ?
        ");
        $upd->execute([$visitDate, $rating, $notes, $companion, $experience, $existing['id']]);
        $actionTaken = 'updated';
    } else {
        $ins = $db->prepare("
            INSERT INTO visited_places (user_id, destination_id, visit_date, rating, experience_notes, companion_type, trek_experience)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $ins->execute([$userId, $destinationId, $visitDate, $rating, $notes, $companion, $experience]);
    }

    // Auto-award Gamification Achievements
    $newBadges = [];
    
    // Count stats for user
    $vCountStmt = $db->prepare("SELECT COUNT(*) FROM visited_places WHERE user_id = ?");
    $vCountStmt->execute([$userId]);
    $totalVisits = (int)$vCountStmt->fetchColumn();

    $fCountStmt = $db->prepare("
        SELECT COUNT(*) 
        FROM visited_places vp 
        JOIN destinations d ON vp.destination_id = d.id 
        WHERE vp.user_id = ? AND d.is_shivaji_fort = 1
    ");
    $fCountStmt->execute([$userId]);
    $totalForts = (int)$fCountStmt->fetchColumn();

    $dCountStmt = $db->prepare("
        SELECT COUNT(DISTINCT d.district_id) 
        FROM visited_places vp 
        JOIN destinations d ON vp.destination_id = d.id 
        WHERE vp.user_id = ?
    ");
    $dCountStmt->execute([$userId]);
    $totalDistricts = (int)$dCountStmt->fetchColumn();

    // Check achievements
    $achieveList = [
        ['id' => 1, 'earned' => $totalVisits >= 1],
        ['id' => 2, 'earned' => $totalForts >= 3],
        ['id' => 3, 'earned' => $totalForts >= 5],
        ['id' => 4, 'earned' => $totalForts >= 10],
        ['id' => 5, 'earned' => $totalForts >= 20],
        ['id' => 6, 'earned' => $totalVisits >= 5],
        ['id' => 7, 'earned' => $totalDistricts >= 3]
    ];

    $checkBadge = $db->prepare("SELECT id FROM user_achievements WHERE user_id = ? AND achievement_id = ?");
    $awardBadge = $db->prepare("INSERT IGNORE INTO user_achievements (user_id, achievement_id) VALUES (?, ?)");
    $getBadgeInfo = $db->prepare("SELECT title_en, title_mr, badge_icon FROM achievements WHERE id = ?");

    foreach ($achieveList as $ach) {
        if ($ach['earned']) {
            $checkBadge->execute([$userId, $ach['id']]);
            if (!$checkBadge->fetch()) {
                $awardBadge->execute([$userId, $ach['id']]);
                $getBadgeInfo->execute([$ach['id']]);
                $badgeInfo = $getBadgeInfo->fetch();
                if ($badgeInfo) {
                    $newBadges[] = $badgeInfo['title_en'];
                }
            }
        }
    }

    $msg = $actionTaken === 'updated' 
        ? 'Updated your visited journey record!' 
        : 'Congratulations! Added to your Visited Journey records.';

    if (!empty($newBadges)) {
        $msg .= ' 🏆 You unlocked new badge(s): ' . implode(', ', $newBadges) . '!';
    }

    echo json_encode([
        'status' => 'success',
        'action' => $actionTaken,
        'new_badges' => $newBadges,
        'message' => $msg
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
