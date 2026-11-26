<?php
/**
 * FORT EXPLORER - Admin Community Moderation
 * Review reported community posts, comments, moderate content, hide or delete.
 */

$adminTitle = 'Community Moderation - Fort Explorer';
require_once __DIR__ . '/includes/admin-header.php';

$db = getDB();

// Handle Actions (Dismiss, Hide, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $action = sanitize_input($_POST['action']);

        if ($action === 'dismiss_report') {
            $reportId = filter_input(INPUT_POST, 'report_id', FILTER_VALIDATE_INT);
            if ($reportId) {
                $db->prepare("UPDATE community_reports SET status = 'dismissed' WHERE id = ?")->execute([$reportId]);
                set_flash('success', 'Report dismissed.');
            }
        } elseif ($action === 'hide_post') {
            $postId = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);
            if ($postId) {
                $db->prepare("UPDATE community_posts SET status = 'hidden' WHERE id = ?")->execute([$postId]);
                $db->prepare("UPDATE community_reports SET status = 'resolved' WHERE post_id = ?")->execute([$postId]);
                set_flash('info', 'Post hidden from community feed.');
            }
        } elseif ($action === 'publish_post') {
            $postId = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);
            if ($postId) {
                $db->prepare("UPDATE community_posts SET status = 'published' WHERE id = ?")->execute([$postId]);
                set_flash('success', 'Post restored and published.');
            }
        } elseif ($action === 'delete_post') {
            $postId = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);
            if ($postId) {
                $db->prepare("DELETE FROM community_posts WHERE id = ?")->execute([$postId]);
                set_flash('success', 'Post permanently deleted.');
            }
        }
        header("Location: community.php");
        exit;
    }
}

// Fetch Reports
$reports = [];
$allPosts = [];

if ($db) {
    try {
        $rStmt = $db->query("
            SELECT cr.*, u.name AS reporter_name, cp.title AS post_title, cp.content AS post_content, cp.status AS post_status,
                   author.name AS author_name
            FROM community_reports cr
            JOIN users u ON cr.reporter_id = u.id
            LEFT JOIN community_posts cp ON cr.post_id = cp.id
            LEFT JOIN users author ON cp.user_id = author.id
            WHERE cr.status = 'pending'
            ORDER BY cr.created_at DESC
        ");
        $reports = $rStmt->fetchAll();

        // All community posts
        $pStmt = $db->query("
            SELECT cp.*, u.name AS author_name, d.name AS destination_name
            FROM community_posts cp
            JOIN users u ON cp.user_id = u.id
            LEFT JOIN destinations d ON cp.destination_id = d.id
            ORDER BY cp.created_at DESC
            LIMIT 50
        ");
        $allPosts = $pStmt->fetchAll();

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <h5 class="mb-0 fw-bold font-heritage text-danger">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i>Pending Community Reports (<?= count($reports) ?>)
                </h5>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($reports)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Reported Post</th>
                                    <th>Reported By</th>
                                    <th>Reason & Details</th>
                                    <th>Reported On</th>
                                    <th class="text-end">Moderation Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reports as $rep): ?>
                                    <tr>
                                        <td>
                                            <strong class="d-block text-dark"><?= e($rep['post_title'] ?: 'Untitled') ?></strong>
                                            <span class="small text-muted">Author: <?= e($rep['author_name'] ?: 'Unknown') ?></span>
                                            <p class="small text-secondary mb-0 mt-1"><?= e(substr($rep['post_content'], 0, 100)) ?>...</p>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border"><?= e($rep['reporter_name']) ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-danger-subtle text-danger mb-1 d-block w-auto"><?= e($rep['reason']) ?></span>
                                            <small class="text-muted"><?= e($rep['details'] ?: 'No notes') ?></small>
                                        </td>
                                        <td class="small text-muted"><?= format_date($rep['created_at']) ?></td>
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm">
                                                <!-- Dismiss -->
                                                <form method="POST" class="d-inline">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="action" value="dismiss_report">
                                                    <input type="hidden" name="report_id" value="<?= $rep['id'] ?>">
                                                    <button type="submit" class="btn btn-outline-secondary" title="Dismiss report as valid content">
                                                        <i class="fa-solid fa-check me-1"></i>Dismiss
                                                    </button>
                                                </form>

                                                <!-- Hide Post -->
                                                <form method="POST" class="d-inline">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="action" value="hide_post">
                                                    <input type="hidden" name="post_id" value="<?= $rep['post_id'] ?>">
                                                    <button type="submit" class="btn btn-outline-warning" title="Hide post from public view">
                                                        <i class="fa-solid fa-eye-slash me-1"></i>Hide
                                                    </button>
                                                </form>

                                                <!-- Delete Post -->
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Permanently delete this post?')">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="action" value="delete_post">
                                                    <input type="hidden" name="post_id" value="<?= $rep['post_id'] ?>">
                                                    <button type="submit" class="btn btn-outline-danger" title="Permanently delete post">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="p-4 text-center text-muted small">
                        <i class="fa-solid fa-circle-check text-success fa-2x mb-2"></i>
                        <p class="mb-0">No pending reports! All community posts are in good standing.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- All Recent Community Stories -->
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="mb-0 fw-bold font-heritage text-success">
                    <i class="fa-solid fa-newspaper me-2 text-warning"></i>All Community Stories (<?= count($allPosts) ?>)
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Title & Content</th>
                                <th>Author</th>
                                <th>Destination</th>
                                <th>Engagement</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allPosts as $p): ?>
                                <tr>
                                    <td style="max-width: 320px;">
                                        <a href="../community-post.php?id=<?= $p['id'] ?>" target="_blank" class="fw-bold text-dark text-decoration-none">
                                            <?= e($p['title']) ?>
                                        </a>
                                        <p class="small text-muted text-truncate mb-0"><?= e($p['content']) ?></p>
                                    </td>
                                    <td><?= e($p['author_name']) ?></td>
                                    <td>
                                        <span class="badge bg-light text-dark border"><?= e($p['destination_name'] ?: 'General Trek') ?></span>
                                    </td>
                                    <td class="small">
                                        <span class="me-2"><i class="fa-solid fa-heart text-danger me-1"></i><?= $p['likes_count'] ?></span>
                                        <span><i class="fa-solid fa-comment text-secondary me-1"></i><?= $p['comments_count'] ?></span>
                                    </td>
                                    <td>
                                        <span class="badge <?= $p['status'] === 'published' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-dark' ?>">
                                            <?= ucfirst($p['status']) ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <?php if ($p['status'] === 'hidden'): ?>
                                            <form method="POST" class="d-inline">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="publish_post">
                                                <input type="hidden" name="post_id" value="<?= $p['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-success">Restore</button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" class="d-inline">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="hide_post">
                                                <input type="hidden" name="post_id" value="<?= $p['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-warning">Hide</button>
                                            </form>
                                        <?php endif; ?>

                                        <form method="POST" class="d-inline" onsubmit="return confirm('Permanently delete this story?')">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete_post">
                                            <input type="hidden" name="post_id" value="<?= $p['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
