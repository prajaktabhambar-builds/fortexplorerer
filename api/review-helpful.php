<?php
/**
 * FORT EXPLORER - Review Helpful Vote & Report API
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (!is_logged_in()) {
    echo json_encode([
        'status' => 'unauthorized',
        'message' => 'Please sign in to vote or report reviews.'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$action = sanitize_input($_POST['action'] ?? 'vote_helpful');
$reviewId = filter_input(INPUT_POST, 'review_id', FILTER_VALIDATE_INT);

if (!$reviewId) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid review ID.']);
    exit;
}

$db = getDB();
if (!$db) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed.']);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    if ($action === 'vote_helpful') {
        // Check if user already voted
        $chk = $db->prepare("SELECT id FROM review_helpful_votes WHERE review_id = ? AND user_id = ?");
        $chk->execute([$reviewId, $userId]);
        $existing = $chk->fetch();

        if ($existing) {
            // Remove helpful vote (toggle)
            $del = $db->prepare("DELETE FROM review_helpful_votes WHERE id = ?");
            $del->execute([$existing['id']]);

            $upd = $db->prepare("UPDATE reviews SET helpful_votes = GREATEST(0, helpful_votes - 1) WHERE id = ?");
            $upd->execute([$reviewId]);

            $cntStmt = $db->prepare("SELECT helpful_votes FROM reviews WHERE id = ?");
            $cntStmt->execute([$reviewId]);
            $count = (int)$cntStmt->fetchColumn();

            echo json_encode([
                'status' => 'success',
                'voted' => false,
                'count' => $count,
                'message' => 'Vote removed.'
            ]);
        } else {
            // Add helpful vote
            $ins = $db->prepare("INSERT INTO review_helpful_votes (review_id, user_id) VALUES (?, ?)");
            $ins->execute([$reviewId, $userId]);

            $upd = $db->prepare("UPDATE reviews SET helpful_votes = helpful_votes + 1 WHERE id = ?");
            $upd->execute([$reviewId]);

            $cntStmt = $db->prepare("SELECT helpful_votes FROM reviews WHERE id = ?");
            $cntStmt->execute([$reviewId]);
            $count = (int)$cntStmt->fetchColumn();

            echo json_encode([
                'status' => 'success',
                'voted' => true,
                'count' => $count,
                'message' => 'Marked review as helpful!'
            ]);
        }
    } elseif ($action === 'report_review') {
        $reason = sanitize_input($_POST['reason'] ?? 'Inappropriate content');
        
        $chk = $db->prepare("SELECT id FROM review_reports WHERE review_id = ? AND reporter_id = ?");
        $chk->execute([$reviewId, $userId]);
        if ($chk->fetch()) {
            echo json_encode(['status' => 'info', 'message' => 'You have already reported this review. Our team will review it.']);
            exit;
        }

        $ins = $db->prepare("INSERT INTO review_reports (review_id, reporter_id, reason) VALUES (?, ?, ?)");
        $ins->execute([$reviewId, $userId, $reason]);

        echo json_encode([
            'status' => 'success',
            'message' => 'Thank you. The review has been flagged for administrator moderation.'
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Unknown action.']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
}
