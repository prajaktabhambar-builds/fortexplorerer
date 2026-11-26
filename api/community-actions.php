<?php
/**
 * FORT EXPLORER - Community AJAX Actions API
 * Likes, comments, follows, and reports.
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (!is_logged_in()) {
    echo json_encode([
        'status' => 'unauthorized',
        'message' => 'Please sign in to participate in the travel community.'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$action = sanitize_input($_POST['action'] ?? '');
$db = getDB();
if (!$db) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed.']);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    // 1. Like / Unlike a Post
    if ($action === 'like_post') {
        $postId = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);
        if (!$postId) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid post ID.']);
            exit;
        }

        $chk = $db->prepare("SELECT id FROM community_likes WHERE post_id = ? AND user_id = ?");
        $chk->execute([$postId, $userId]);
        $existing = $chk->fetch();

        if ($existing) {
            $del = $db->prepare("DELETE FROM community_likes WHERE id = ?");
            $del->execute([$existing['id']]);

            $upd = $db->prepare("UPDATE community_posts SET likes_count = GREATEST(0, likes_count - 1) WHERE id = ?");
            $upd->execute([$postId]);

            $cntStmt = $db->prepare("SELECT likes_count FROM community_posts WHERE id = ?");
            $cntStmt->execute([$postId]);
            $count = (int)$cntStmt->fetchColumn();

            echo json_encode([
                'status' => 'success',
                'liked' => false,
                'count' => $count,
                'message' => 'Post unliked.'
            ]);
        } else {
            $ins = $db->prepare("INSERT INTO community_likes (post_id, user_id) VALUES (?, ?)");
            $ins->execute([$postId, $userId]);

            $upd = $db->prepare("UPDATE community_posts SET likes_count = likes_count + 1 WHERE id = ?");
            $upd->execute([$postId]);

            $cntStmt = $db->prepare("SELECT likes_count FROM community_posts WHERE id = ?");
            $cntStmt->execute([$postId]);
            $count = (int)$cntStmt->fetchColumn();

            echo json_encode([
                'status' => 'success',
                'liked' => true,
                'count' => $count,
                'message' => 'Post liked!'
            ]);
        }
    }
    // 2. Add Comment to Post
    elseif ($action === 'add_comment') {
        $postId = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);
        $commentText = sanitize_input($_POST['comment_text'] ?? '');
        $parentId = filter_input(INPUT_POST, 'parent_id', FILTER_VALIDATE_INT) ?: null;

        if (!$postId || empty($commentText)) {
            echo json_encode(['status' => 'error', 'message' => 'Please provide a valid comment text.']);
            exit;
        }

        $ins = $db->prepare("
            INSERT INTO community_comments (post_id, user_id, parent_id, comment_text) 
            VALUES (?, ?, ?, ?)
        ");
        $ins->execute([$postId, $userId, $parentId, $commentText]);
        $newId = $db->lastInsertId();

        $upd = $db->prepare("UPDATE community_posts SET comments_count = comments_count + 1 WHERE id = ?");
        $upd->execute([$postId]);

        $uStmt = $db->prepare("SELECT name FROM users WHERE id = ?");
        $uStmt->execute([$userId]);
        $userName = $uStmt->fetchColumn() ?: 'Explorer';

        echo json_encode([
            'status' => 'success',
            'message' => 'Comment posted successfully!',
            'comment' => [
                'id' => $newId,
                'user_name' => $userName,
                'text' => $commentText,
                'created_at' => date('d M Y, h:i A')
            ]
        ]);
    }
    // 3. Follow / Unfollow User
    elseif ($action === 'toggle_follow') {
        $targetUserId = filter_input(INPUT_POST, 'target_user_id', FILTER_VALIDATE_INT);
        if (!$targetUserId || $targetUserId === $userId) {
            echo json_encode(['status' => 'error', 'message' => 'Cannot follow this user.']);
            exit;
        }

        $chk = $db->prepare("SELECT id FROM user_follows WHERE follower_id = ? AND following_id = ?");
        $chk->execute([$userId, $targetUserId]);
        $existing = $chk->fetch();

        if ($existing) {
            $del = $db->prepare("DELETE FROM user_follows WHERE id = ?");
            $del->execute([$existing['id']]);

            echo json_encode([
                'status' => 'success',
                'following' => false,
                'message' => 'Unfollowed user.'
            ]);
        } else {
            $ins = $db->prepare("INSERT INTO user_follows (follower_id, following_id) VALUES (?, ?)");
            $ins->execute([$userId, $targetUserId]);

            echo json_encode([
                'status' => 'success',
                'following' => true,
                'message' => 'Now following this explorer!'
            ]);
        }
    }
    // 4. Report Post
    elseif ($action === 'report_post') {
        $postId = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);
        $reason = sanitize_input($_POST['reason'] ?? 'Inappropriate or spam');
        $details = sanitize_input($_POST['details'] ?? '');

        if (!$postId) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid post ID.']);
            exit;
        }

        $chk = $db->prepare("SELECT id FROM community_reports WHERE post_id = ? AND reporter_id = ?");
        $chk->execute([$postId, $userId]);
        if ($chk->fetch()) {
            echo json_encode(['status' => 'info', 'message' => 'You have already reported this post. Our moderators are on it.']);
            exit;
        }

        $ins = $db->prepare("INSERT INTO community_reports (reporter_id, post_id, reason, details) VALUES (?, ?, ?, ?)");
        $ins->execute([$userId, $postId, $reason, $details]);

        echo json_encode([
            'status' => 'success',
            'message' => 'Thank you for helping keep the community respectful. Post reported to administrators.'
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Unknown action requested.']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()]);
}
