<?php
/**
 * FORT EXPLORER - Travel Community Feed
 * Share travel stories, trek tips, photos, like, comment, and connect with fellow explorers.
 */

require_once __DIR__ . '/includes/header.php';

$db = getDB();
$destFilter = filter_input(INPUT_GET, 'dest', FILTER_VALIDATE_INT);
$diffFilter = sanitize_input($_GET['diff'] ?? '');
$sort = sanitize_input($_GET['sort'] ?? 'latest');

$allDestinations = [];
$posts = [];
$topExplorers = [];
$userLikedPostIds = [];
$userFollowingIds = [];

if ($db) {
    try {
        // Fetch destinations for selector
        $dStmt = $db->query("SELECT id, name, name_mr FROM destinations ORDER BY is_shivaji_fort DESC, name ASC");
        $allDestinations = $dStmt->fetchAll();

        // Build community posts query
        $whereSql = "WHERE cp.status = 'published'";
        $params = [];

        if ($destFilter) {
            $whereSql .= " AND cp.destination_id = ?";
            $params[] = $destFilter;
        }
        if ($diffFilter) {
            $whereSql .= " AND cp.trek_difficulty = ?";
            $params[] = $diffFilter;
        }

        $orderSql = match($sort) {
            'top' => "ORDER BY cp.likes_count DESC, cp.created_at DESC",
            default => "ORDER BY cp.created_at DESC"
        };

        $pStmt = $db->prepare("
            SELECT cp.*, u.name AS author_name, u.avatar AS author_avatar, u.role AS author_role,
                   d.name AS destination_name, d.slug AS destination_slug, d.is_shivaji_fort
            FROM community_posts cp
            JOIN users u ON cp.user_id = u.id
            LEFT JOIN destinations d ON cp.destination_id = d.id
            {$whereSql}
            {$orderSql}
            LIMIT 30
        ");
        $pStmt->execute($params);
        $posts = $pStmt->fetchAll();

        // User likes and follows
        if (is_logged_in()) {
            $userId = $_SESSION['user_id'];
            $lStmt = $db->prepare("SELECT post_id FROM community_likes WHERE user_id = ?");
            $lStmt->execute([$userId]);
            $userLikedPostIds = $lStmt->fetchAll(PDO::FETCH_COLUMN);

            $fStmt = $db->prepare("SELECT following_id FROM user_follows WHERE follower_id = ?");
            $fStmt->execute([$userId]);
            $userFollowingIds = $fStmt->fetchAll(PDO::FETCH_COLUMN);
        }

        // Top Explorers Leaderboard
        $leadStmt = $db->query("
            SELECT u.id, u.name, u.avatar, COUNT(cp.id) AS post_count, COALESCE(SUM(cp.likes_count), 0) AS total_likes
            FROM users u
            JOIN community_posts cp ON u.id = cp.user_id
            WHERE cp.status = 'published'
            GROUP BY u.id
            ORDER BY total_likes DESC, post_count DESC
            LIMIT 5
        ");
        $topExplorers = $leadStmt->fetchAll();

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Handle New Post Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_post') {
    require_login();
    if (verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $title = sanitize_input($_POST['title'] ?? '');
        $destId = filter_input(INPUT_POST, 'destination_id', FILTER_VALIDATE_INT) ?: null;
        $difficulty = sanitize_input($_POST['trek_difficulty'] ?? 'Moderate');
        $travelTips = sanitize_input($_POST['travel_tips'] ?? '');
        $content = sanitize_input($_POST['content'] ?? '');
        $imageUrl = null;

        if (isset($_FILES['post_photo']) && $_FILES['post_photo']['error'] === UPLOAD_ERR_OK) {
            $uploaded = upload_image($_FILES['post_photo'], 'uploads');
            if ($uploaded['success']) {
                $imageUrl = $uploaded['path'];
            }
        }

        if (!empty($title) && !empty($content)) {
            $ins = $db->prepare("
                INSERT INTO community_posts (user_id, destination_id, title, content, image_url, trek_difficulty, travel_tips, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'published')
            ");
            $ins->execute([$_SESSION['user_id'], $destId, $title, $content, $imageUrl, $difficulty, $travelTips]);
            set_flash('success', 'Your travel story has been published to the community!');
            header("Location: community.php");
            exit;
        } else {
            set_flash('danger', 'Please provide a story title and your travel experience.');
        }
    } else {
        set_flash('danger', 'CSRF verification failed.');
    }
}
?>

<!-- Header Banner -->
<div class="py-5" style="background: linear-gradient(135deg, var(--fe-forest-dark) 0%, var(--fe-forest) 100%); color: #fff; border-bottom: 3px solid var(--fe-gold);">
    <div class="container py-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <span class="badge bg-warning text-dark px-3 py-1 rounded-pill small mb-2">
                    <i class="fa-solid fa-users me-1"></i>Sahyadri Travelers & Trekkers
                </span>
                <h1 class="display-5 fw-bold font-heritage mb-1">Travel Community</h1>
                <p class="text-white-50 lead fs-6 max-w-600 mb-0">
                    Discover personal trek accounts, hidden trails, authentic travel advice, and breathtaking photographs from fellow Maharashtra explorers.
                </p>
            </div>
            <div>
                <?php if (is_logged_in()): ?>
                    <button type="button" class="btn btn-fe-gold py-2.5 px-4 rounded-pill fw-bold shadow" data-bs-toggle="modal" data-bs-target="#createPostModal">
                        <i class="fa-solid fa-feather me-2"></i>Publish Travel Story
                    </button>
                <?php else: ?>
                    <a href="login.php?redirect=community.php" class="btn btn-fe-gold py-2.5 px-4 rounded-pill fw-bold shadow">
                        <i class="fa-solid fa-user-plus me-2"></i>Sign In to Share
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="container py-5">
    <div class="row g-4">
        <!-- Main Feed Column -->
        <div class="col-lg-8">
            <!-- Filter & Sort Bar -->
            <div class="bg-white p-3 rounded-4 shadow-sm border mb-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex flex-wrap gap-2">
                    <a href="community.php" class="btn btn-sm <?= (!$destFilter && !$diffFilter && $sort === 'latest') ? 'btn-dark' : 'btn-outline-secondary' ?> rounded-pill">
                        All Stories
                    </a>
                    <a href="community.php?sort=top" class="btn btn-sm <?= $sort === 'top' ? 'btn-dark' : 'btn-outline-secondary' ?> rounded-pill">
                        <i class="fa-solid fa-fire me-1 text-danger"></i>Most Popular
                    </a>
                    <a href="community.php?diff=Easy" class="btn btn-sm <?= $diffFilter === 'Easy' ? 'btn-success' : 'btn-outline-secondary' ?> rounded-pill">
                        Easy Treks
                    </a>
                    <a href="community.php?diff=Challenging" class="btn btn-sm <?= $diffFilter === 'Challenging' ? 'btn-warning text-dark' : 'btn-outline-secondary' ?> rounded-pill">
                        Challenging
                    </a>
                </div>

                <div class="text-muted small">
                    Showing <strong><?= count($posts) ?></strong> community stories
                </div>
            </div>

            <!-- Feed Posts -->
            <?php if (!empty($posts)): ?>
                <div class="d-flex flex-column gap-4">
                    <?php foreach ($posts as $post): 
                        $isLiked = in_array($post['id'], $userLikedPostIds, true);
                        $isFollowing = in_array($post['user_id'], $userFollowingIds, true);
                        $isOwnPost = is_logged_in() && $_SESSION['user_id'] == $post['user_id'];
                    ?>
                        <article class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white" id="post-<?= $post['id'] ?>">
                            <div class="card-body p-4">
                                <!-- Author Header -->
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="rounded-circle bg-success text-white fw-bold d-flex align-items-center justify-content-center shadow-sm" style="width: 44px; height: 44px; font-size: 1.1rem;">
                                            <?= strtoupper(substr($post['author_name'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div class="d-flex align-items-center gap-2">
                                                <strong class="text-dark small"><?= e($post['author_name']) ?></strong>
                                                <?php if ($post['author_role'] === 'admin'): ?>
                                                    <span class="badge bg-warning-subtle text-dark border small" style="font-size:0.65rem;">Admin</span>
                                                <?php endif; ?>
                                            </div>
                                            <span class="text-muted" style="font-size: 0.75rem;">
                                                <i class="fa-regular fa-clock me-1"></i><?= format_date($post['created_at']) ?>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="d-flex align-items-center gap-2">
                                        <?php if (is_logged_in() && !$isOwnPost): ?>
                                            <button type="button" class="btn btn-sm <?= $isFollowing ? 'btn-outline-secondary' : 'btn-outline-success' ?> rounded-pill py-0.5 px-2.5 follow-btn-<?= $post['user_id'] ?>" style="font-size:0.75rem;" onclick="toggleFollowUser(<?= $post['user_id'] ?>)">
                                                <?= $isFollowing ? '<i class="fa-solid fa-check me-1"></i>Following' : '<i class="fa-solid fa-plus me-1"></i>Follow' ?>
                                            </button>
                                        <?php endif; ?>
                                        <button type="button" class="btn btn-sm btn-link text-muted p-0" title="Report post" onclick="openReportPostModal(<?= $post['id'] ?>)">
                                            <i class="fa-regular fa-flag"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Post Meta: Destination & Difficulty -->
                                <div class="d-flex flex-wrap gap-2 mb-2">
                                    <?php if ($post['destination_name']): ?>
                                        <a href="destination.php?slug=<?= urlencode($post['destination_slug']) ?>" class="badge bg-success-subtle text-success text-decoration-none px-2.5 py-1 rounded-pill">
                                            <i class="fa-solid fa-location-dot me-1"></i><?= e($post['destination_name']) ?>
                                        </a>
                                    <?php endif; ?>
                                    <?= get_difficulty_badge($post['trek_difficulty']) ?>
                                </div>

                                <!-- Title -->
                                <h4 class="h5 fw-bold font-heritage mb-2">
                                    <a href="community-post.php?id=<?= $post['id'] ?>" class="text-dark text-decoration-none hover-gold">
                                        <?= e($post['title']) ?>
                                    </a>
                                </h4>

                                <!-- Text Content Snippet -->
                                <p class="text-secondary small mb-3" style="line-height: 1.7;">
                                    <?= nl2br(e(substr($post['content'], 0, 320))) ?><?= strlen($post['content']) > 320 ? '...' : '' ?>
                                </p>

                                <!-- Travel Tips Box if available -->
                                <?php if (!empty($post['travel_tips'])): ?>
                                    <div class="p-2.5 bg-warning-subtle rounded-3 border border-warning mb-3 small">
                                        <strong class="text-dark"><i class="fa-solid fa-lightbulb text-warning me-1"></i>Trekker's Tip:</strong>
                                        <span class="text-secondary"><?= e($post['travel_tips']) ?></span>
                                    </div>
                                <?php endif; ?>

                                <!-- Post Image -->
                                <?php if (!empty($post['image_url'])): ?>
                                    <div class="mb-3 rounded-3 overflow-hidden border">
                                        <a href="community-post.php?id=<?= $post['id'] ?>">
                                            <img src="<?= e($post['image_url']) ?>" alt="<?= e($post['title']) ?>" class="img-fluid w-100" style="max-height: 420px; object-fit: cover;">
                                        </a>
                                    </div>
                                <?php endif; ?>

                                <!-- Actions Bar: Like, Comments, Read Full Story -->
                                <div class="d-flex align-items-center justify-content-between pt-2 border-top">
                                    <div class="d-flex gap-2">
                                        <!-- Like Button -->
                                        <button type="button" class="btn btn-sm <?= $isLiked ? 'btn-danger text-white' : 'btn-outline-danger' ?> rounded-pill px-3 py-1" onclick="togglePostLike(<?= $post['id'] ?>)">
                                            <i class="fa-solid fa-heart me-1"></i>
                                            <span id="post-like-cnt-<?= $post['id'] ?>"><?= (int)$post['likes_count'] ?></span>
                                        </button>

                                        <!-- Comments Link -->
                                        <a href="community-post.php?id=<?= $post['id'] ?>#comments" class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-1">
                                            <i class="fa-regular fa-comment me-1"></i><?= (int)$post['comments_count'] ?> Comments
                                        </a>
                                    </div>

                                    <a href="community-post.php?id=<?= $post['id'] ?>" class="btn btn-sm text-success fw-bold text-decoration-none">
                                        Read Full Story <i class="fa-solid fa-arrow-right ms-1"></i>
                                    </a>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="bg-white p-5 rounded-4 text-center border shadow-sm">
                    <i class="fa-solid fa-pen-nib fa-3x text-muted opacity-50 mb-3"></i>
                    <h5 class="fw-bold font-heritage">No stories shared yet</h5>
                    <p class="text-muted small mb-4">Be the first explorer to share your recent Sahyadri trek memory with the community!</p>
                    <?php if (is_logged_in()): ?>
                        <button type="button" class="btn btn-fe-gold rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#createPostModal">
                            <i class="fa-solid fa-feather me-2"></i>Publish First Story
                        </button>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-fe-primary rounded-pill px-4">Sign In to Post</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Right Sidebar -->
        <div class="col-lg-4">
            <!-- Community Guidelines Box -->
            <div class="bg-white p-4 rounded-4 shadow-sm border mb-4">
                <h5 class="font-heritage text-success mb-3"><i class="fa-solid fa-book-bookmark me-2 text-warning"></i>Community Etiquette</h5>
                <ul class="list-unstyled small text-muted mb-0 d-flex flex-column gap-2">
                    <li><i class="fa-solid fa-check text-success me-2"></i><strong>Respect Historical Sanctity:</strong> Honor Chhatrapati Shivaji Maharaj and the sacred heritage of Sahyadri forts.</li>
                    <li><i class="fa-solid fa-check text-success me-2"></i><strong>Accurate Trek Info:</strong> Share verified routes, actual water point states, and genuine difficulty ratings.</li>
                    <li><i class="fa-solid fa-check text-success me-2"></i><strong>Leave No Trace:</strong> Promote zero plastic and preservation of historical ramparts.</li>
                    <li><i class="fa-solid fa-circle-exclamation text-danger me-2"></i>Commercial spam or abusive posts will be moderated by administrators.</li>
                </ul>
            </div>

            <!-- Top Explorers Leaderboard -->
            <?php if (!empty($topExplorers)): ?>
                <div class="bg-white p-4 rounded-4 shadow-sm border mb-4">
                    <h5 class="font-heritage text-dark mb-3"><i class="fa-solid fa-trophy me-2 text-warning"></i>Top Contributors</h5>
                    <div class="d-flex flex-column gap-3">
                        <?php foreach ($topExplorers as $idx => $lead): ?>
                            <div class="d-flex align-items-center justify-content-between p-2 rounded-3 bg-light">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge <?= $idx === 0 ? 'bg-warning text-dark' : 'bg-dark text-white' ?> rounded-circle" style="width:24px;height:24px;display:flex;align-items:center;justify-content:center;">
                                        <?= $idx + 1 ?>
                                    </span>
                                    <div>
                                        <strong class="text-dark small d-block"><?= e($lead['name']) ?></strong>
                                        <span class="text-muted" style="font-size:0.75rem;"><?= $lead['post_count'] ?> stories published</span>
                                    </div>
                                </div>
                                <span class="badge bg-danger-subtle text-danger small">
                                    <i class="fa-solid fa-heart me-1"></i><?= $lead['total_likes'] ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Create Post Modal -->
<div class="modal fade" id="createPostModal" tabindex="-1" aria-labelledby="createPostModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header bg-success text-white border-0">
                <h5 class="modal-title font-heritage" id="createPostModalLabel"><i class="fa-solid fa-feather me-2 text-warning"></i>Share Your Travel Story</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="create_post">
                    <?= csrf_field() ?>

                    <div class="row g-3 mb-3">
                        <div class="col-12">
                            <label class="form-label small fw-bold">Story Title</label>
                            <input type="text" name="title" class="form-control" placeholder="e.g. Conquering Rajgad in Heavy Monsoon Mist" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Related Fort / Destination</label>
                            <select name="destination_id" class="form-select">
                                <option value="">-- General Sahyadri Trek --</option>
                                <?php foreach ($allDestinations as $d): ?>
                                    <option value="<?= $d['id'] ?>"><?= e(t_val($d['name'], $d['name_mr'])) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Trek Difficulty Experienced</label>
                            <select name="trek_difficulty" class="form-select">
                                <option value="Easy">Easy (Family / Beginner)</option>
                                <option value="Moderate" selected>Moderate (Standard Fort Hike)</option>
                                <option value="Challenging">Challenging (Steep rock-cut stairs / Ghat)</option>
                                <option value="Difficult">Difficult (Technical / High endurance)</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold"><i class="fa-solid fa-lightbulb text-warning me-1"></i>Key Practical Travel Tip for Fellow Trekkers</label>
                            <input type="text" name="travel_tips" class="form-control" placeholder="e.g. Start descent before 5 PM, fresh spring water available near Padmavati Temple">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Your Travel Experience / Story</label>
                            <textarea name="content" rows="6" class="form-control" placeholder="Describe the trail, historical wonders seen, view from bastions, and encounters..." required></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold"><i class="fa-solid fa-camera me-1"></i>Upload Photograph (Optional)</label>
                            <input type="file" name="post_photo" class="form-control" accept="image/jpeg,image/png,image/webp">
                            <span class="text-muted" style="font-size:0.75rem;">JPG, PNG, or WebP. Maximum file size 5MB.</span>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-fe-primary">
                            <i class="fa-solid fa-paper-plane me-1"></i>Publish Story
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Report Post Modal -->
<div class="modal fade" id="reportPostModal" tabindex="-1" aria-labelledby="reportPostModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title font-heritage" id="reportPostModalLabel"><i class="fa-regular fa-flag text-danger me-2"></i>Report Inappropriate Post</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="reportPostId" value="">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Reason for Report</label>
                    <select class="form-select" id="reportPostReason">
                        <option value="Commercial advertising / Spam">Commercial advertising / Spam</option>
                        <option value="Offensive or disrespectful language">Offensive or disrespectful language</option>
                        <option value="Misleading or dangerous travel advice">Misleading or dangerous travel advice</option>
                        <option value="Copyright violation or unauthorized photo">Copyright violation or unauthorized photo</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Additional Details</label>
                    <textarea class="form-control" id="reportPostDetails" rows="2" placeholder="Brief note for admin review..."></textarea>
                </div>
                <button type="button" class="btn btn-danger w-100" onclick="submitPostReport()">
                    <i class="fa-solid fa-paper-plane me-1"></i>Submit Report to Admins
                </button>
            </div>
        </div>
    </div>
</div>

<script>
async function togglePostLike(postId) {
    <?php if (!is_logged_in()): ?>
        window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.pathname + window.location.search);
        return;
    <?php endif; ?>

    try {
        const res = await fetch('api/community-actions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=like_post&post_id=${encodeURIComponent(postId)}`
        });
        const data = await res.json();
        if (data.status === 'success') {
            const cntSpan = document.getElementById(`post-like-cnt-${postId}`);
            if (cntSpan) cntSpan.textContent = data.count;
            showToast(data.message, 'success');
        } else {
            showToast(data.message || 'Error occurred', 'error');
        }
    } catch (e) {
        showToast('Network error', 'error');
    }
}

async function toggleFollowUser(targetUserId) {
    <?php if (!is_logged_in()): ?>
        window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.pathname + window.location.search);
        return;
    <?php endif; ?>

    try {
        const res = await fetch('api/community-actions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=toggle_follow&target_user_id=${encodeURIComponent(targetUserId)}`
        });
        const data = await res.json();
        if (data.status === 'success') {
            document.querySelectorAll(`.follow-btn-${targetUserId}`).forEach(btn => {
                if (data.following) {
                    btn.className = `btn btn-sm btn-outline-secondary rounded-pill py-0.5 px-2.5 follow-btn-${targetUserId}`;
                    btn.innerHTML = '<i class="fa-solid fa-check me-1"></i>Following';
                } else {
                    btn.className = `btn btn-sm btn-outline-success rounded-pill py-0.5 px-2.5 follow-btn-${targetUserId}`;
                    btn.innerHTML = '<i class="fa-solid fa-plus me-1"></i>Follow';
                }
            });
            showToast(data.message, 'success');
        } else {
            showToast(data.message || 'Error occurred', 'error');
        }
    } catch (e) {
        showToast('Network error', 'error');
    }
}

function openReportPostModal(postId) {
    <?php if (!is_logged_in()): ?>
        window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.pathname + window.location.search);
        return;
    <?php endif; ?>
    document.getElementById('reportPostId').value = postId;
    const modal = new bootstrap.Modal(document.getElementById('reportPostModal'));
    modal.show();
}

async function submitPostReport() {
    const postId = document.getElementById('reportPostId').value;
    const reason = document.getElementById('reportPostReason').value;
    const details = document.getElementById('reportPostDetails').value;

    try {
        const res = await fetch('api/community-actions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=report_post&post_id=${encodeURIComponent(postId)}&reason=${encodeURIComponent(reason)}&details=${encodeURIComponent(details)}`
        });
        const data = await res.json();
        showToast(data.message, data.status === 'success' ? 'success' : 'info');
        bootstrap.Modal.getInstance(document.getElementById('reportPostModal')).hide();
    } catch (e) {
        showToast('Network error submitting report', 'error');
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
