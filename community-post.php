<?php
/**
 * FORT EXPLORER - Single Community Travel Story & Discussion
 * Full story view, author card, follow toggle, comments section, and report modal.
 */

require_once __DIR__ . '/includes/header.php';

$db = getDB();
$postId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$postId) {
    header("Location: community.php");
    exit;
}

$post = null;
$comments = [];
$isLiked = false;
$isFollowing = false;

if ($db) {
    try {
        $pStmt = $db->prepare("
            SELECT cp.*, u.name AS author_name, u.avatar AS author_avatar, u.role AS author_role, u.home_district AS author_district,
                   d.name AS destination_name, d.slug AS destination_slug, d.primary_image AS dest_image
            FROM community_posts cp
            JOIN users u ON cp.user_id = u.id
            LEFT JOIN destinations d ON cp.destination_id = d.id
            WHERE cp.id = ?
        ");
        $pStmt->execute([$postId]);
        $post = $pStmt->fetch();

        if (!$post) {
            set_flash('danger', 'Community story not found.');
            header("Location: community.php");
            exit;
        }

        // Fetch comments
        $cStmt = $db->prepare("
            SELECT cc.*, u.name AS user_name, u.avatar AS user_avatar 
            FROM community_comments cc
            JOIN users u ON cc.user_id = u.id
            WHERE cc.post_id = ? AND cc.status = 'published'
            ORDER BY cc.created_at ASC
        ");
        $cStmt->execute([$postId]);
        $comments = $cStmt->fetchAll();

        // User like & follow state
        if (is_logged_in()) {
            $userId = $_SESSION['user_id'];
            $lChk = $db->prepare("SELECT id FROM community_likes WHERE post_id = ? AND user_id = ?");
            $lChk->execute([$postId, $userId]);
            $isLiked = (bool)$lChk->fetch();

            $fChk = $db->prepare("SELECT id FROM user_follows WHERE follower_id = ? AND following_id = ?");
            $fChk->execute([$userId, $post['user_id']]);
            $isFollowing = (bool)$fChk->fetch();
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$isOwnPost = is_logged_in() && $_SESSION['user_id'] == $post['user_id'];
?>

<div class="container py-4">
    <!-- Breadcrumb Navigation -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="index.php"><i class="fa-solid fa-house me-1"></i>Home</a></li>
            <li class="breadcrumb-item"><a href="community.php">Community Stories</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= e($post['title']) ?></li>
        </ol>
    </nav>

    <div class="row g-4">
        <!-- Main Story Content Column -->
        <div class="col-lg-8">
            <article class="bg-white p-4 p-md-5 rounded-4 shadow-sm border mb-4">
                <!-- Header Meta -->
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 pb-3 border-bottom">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-success text-white fw-bold d-flex align-items-center justify-content-center shadow-sm" style="width: 50px; height: 50px; font-size: 1.25rem;">
                            <?= strtoupper(substr($post['author_name'], 0, 1)) ?>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <h6 class="mb-0 fw-bold text-dark"><?= e($post['author_name']) ?></h6>
                                <?php if ($post['author_role'] === 'admin'): ?>
                                    <span class="badge bg-warning-subtle text-dark border small">Admin</span>
                                <?php endif; ?>
                            </div>
                            <span class="text-muted small">
                                <i class="fa-regular fa-calendar me-1"></i>Published on <?= format_date($post['created_at']) ?>
                            </span>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <?php if (is_logged_in() && !$isOwnPost): ?>
                            <button type="button" class="btn btn-sm <?= $isFollowing ? 'btn-outline-secondary' : 'btn-outline-success' ?> rounded-pill px-3 follow-btn-<?= $post['user_id'] ?>" onclick="toggleFollowUser(<?= $post['user_id'] ?>)">
                                <?= $isFollowing ? '<i class="fa-solid fa-check me-1"></i>Following' : '<i class="fa-solid fa-plus me-1"></i>Follow' ?>
                            </button>
                        <?php endif; ?>
                        <button type="button" class="btn btn-sm btn-outline-danger rounded-pill" onclick="openReportPostModal(<?= $post['id'] ?>)">
                            <i class="fa-regular fa-flag me-1"></i>Report
                        </button>
                    </div>
                </div>

                <!-- Destination Pill & Difficulty -->
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <?php if ($post['destination_name']): ?>
                        <a href="destination.php?slug=<?= urlencode($post['destination_slug']) ?>" class="badge bg-success-subtle text-success text-decoration-none px-3 py-1.5 rounded-pill fs-6">
                            <i class="fa-solid fa-location-dot me-1"></i><?= e($post['destination_name']) ?>
                        </a>
                    <?php endif; ?>
                    <?= get_difficulty_badge($post['trek_difficulty']) ?>
                </div>

                <!-- Title -->
                <h1 class="display-6 fw-bold font-heritage mb-4 text-dark"><?= e($post['title']) ?></h1>

                <!-- Photo if present -->
                <?php if (!empty($post['image_url'])): ?>
                    <div class="mb-4 rounded-4 overflow-hidden shadow-sm border">
                        <img src="<?= e($post['image_url']) ?>" alt="<?= e($post['title']) ?>" class="img-fluid w-100" style="max-height: 520px; object-fit: cover;">
                    </div>
                <?php endif; ?>

                <!-- Travel Tips Box -->
                <?php if (!empty($post['travel_tips'])): ?>
                    <div class="p-3 bg-warning-subtle rounded-3 border border-warning mb-4">
                        <h6 class="fw-bold font-heritage text-dark mb-1"><i class="fa-solid fa-lightbulb text-warning me-2"></i>Trekker's Practical Tip</h6>
                        <p class="small text-secondary mb-0"><?= nl2br(e($post['travel_tips'])) ?></p>
                    </div>
                <?php endif; ?>

                <!-- Full Story Narrative -->
                <div class="fs-6 text-secondary mb-4" style="line-height: 1.9;">
                    <?= nl2br(e($post['content'])) ?>
                </div>

                <!-- Like & Share Footer -->
                <div class="d-flex align-items-center justify-content-between pt-4 border-top">
                    <button type="button" class="btn btn-sm <?= $isLiked ? 'btn-danger text-white' : 'btn-outline-danger' ?> rounded-pill px-4 py-2 fw-bold" onclick="togglePostLike(<?= $post['id'] ?>)">
                        <i class="fa-solid fa-heart me-1"></i>
                        <span id="post-like-cnt-<?= $post['id'] ?>"><?= (int)$post['likes_count'] ?></span> Likes
                    </button>

                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" onclick="navigator.clipboard.writeText(window.location.href); showToast('Story link copied!', 'info');">
                            <i class="fa-solid fa-link me-1"></i>Copy Link
                        </button>
                    </div>
                </div>
            </article>

            <!-- Comments Discussion Section -->
            <div class="bg-white p-4 p-md-5 rounded-4 shadow-sm border" id="comments">
                <h4 class="font-heritage text-success mb-4"><i class="fa-regular fa-comments me-2 text-warning"></i>Trekker Comments (<?= count($comments) ?>)</h4>

                <!-- Add Comment Form -->
                <?php if (is_logged_in()): ?>
                    <div class="mb-4 p-3 bg-light rounded-3 border">
                        <label class="form-label small fw-bold text-dark">Join the Discussion</label>
                        <div class="d-flex gap-2">
                            <input type="text" id="commentTextInput" class="form-control" placeholder="Ask a question about the trail or share your thoughts...">
                            <button type="button" class="btn btn-fe-primary text-nowrap" onclick="submitComment()">
                                <i class="fa-solid fa-paper-plane me-1"></i>Post
                            </button>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-light border mb-4 d-flex align-items-center justify-content-between">
                        <span class="small text-muted"><i class="fa-solid fa-lock me-2 text-warning"></i>Sign in to join the conversation and comment.</span>
                        <a href="login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="btn btn-sm btn-outline-dark">Sign In</a>
                    </div>
                <?php endif; ?>

                <!-- Comments List -->
                <div class="d-flex flex-column gap-3" id="commentsList">
                    <?php if (!empty($comments)): ?>
                        <?php foreach ($comments as $c): ?>
                            <div class="p-3 border rounded-3 bg-light">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-circle bg-success text-white fw-bold d-flex align-items-center justify-content-center" style="width: 30px; height: 30px; font-size: 0.8rem;">
                                            <?= strtoupper(substr($c['user_name'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <strong class="text-dark small"><?= e($c['user_name']) ?></strong>
                                            <span class="text-muted" style="font-size:0.75rem;"> &bull; <?= format_date($c['created_at']) ?></span>
                                        </div>
                                    </div>
                                </div>
                                <p class="text-secondary small mb-0"><?= nl2br(e($c['comment_text'])) ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted small fst-italic mb-0" id="noCommentsMsg">No comments yet. Start the conversation!</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Right Column: Author Profile & Destination Card -->
        <div class="col-lg-4">
            <!-- Author Card -->
            <div class="bg-white p-4 rounded-4 shadow-sm border mb-4 text-center">
                <div class="rounded-circle bg-warning text-dark fw-bold d-inline-flex align-items-center justify-content-center shadow mb-3" style="width: 72px; height: 72px; font-size: 2rem;">
                    <?= strtoupper(substr($post['author_name'], 0, 1)) ?>
                </div>
                <h5 class="fw-bold font-heritage mb-1"><?= e($post['author_name']) ?></h5>
                <p class="text-muted small mb-3"><i class="fa-solid fa-location-dot me-1 text-danger"></i>Based in <?= e($post['author_district'] ?? 'Maharashtra') ?></p>

                <?php if (is_logged_in() && !$isOwnPost): ?>
                    <button type="button" class="btn btn-sm <?= $isFollowing ? 'btn-outline-secondary' : 'btn-success' ?> rounded-pill px-4 follow-btn-<?= $post['user_id'] ?>" onclick="toggleFollowUser(<?= $post['user_id'] ?>)">
                        <?= $isFollowing ? '<i class="fa-solid fa-check me-1"></i>Following' : '<i class="fa-solid fa-user-plus me-1"></i>Follow Explorer' ?>
                    </button>
                <?php endif; ?>
            </div>

            <!-- Destination Card if attached -->
            <?php if ($post['destination_name']): ?>
                <div class="bg-white p-4 rounded-4 shadow-sm border mb-4">
                    <h6 class="font-heritage text-success mb-3"><i class="fa-solid fa-shield-halved me-1 text-warning"></i>Featured Fort</h6>
                    <img src="<?= e($post['dest_image']) ?>" alt="<?= e($post['destination_name']) ?>" class="img-fluid rounded-3 mb-3 w-100" style="max-height: 180px; object-fit: cover;">
                    <h6 class="fw-bold text-dark mb-1"><?= e($post['destination_name']) ?></h6>
                    <a href="destination.php?slug=<?= urlencode($post['destination_slug']) ?>" class="btn btn-sm btn-fe-gold rounded-pill w-100 mt-2">
                        Explore Full Destination Details
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Report Post Modal -->
<div class="modal fade" id="reportPostModal" tabindex="-1" aria-labelledby="reportPostModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title font-heritage" id="reportPostModalLabel"><i class="fa-regular fa-flag text-danger me-2"></i>Report Story</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Reason for Report</label>
                    <select class="form-select" id="reportReasonSelect">
                        <option value="Commercial advertising / Spam">Commercial advertising / Spam</option>
                        <option value="Offensive or disrespectful language">Offensive or disrespectful language</option>
                        <option value="Misleading or dangerous travel advice">Misleading or dangerous travel advice</option>
                    </select>
                </div>
                <button type="button" class="btn btn-danger w-100" onclick="submitPostReportDetail()">
                    <i class="fa-solid fa-paper-plane me-1"></i>Submit Report
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
                    btn.className = `btn btn-sm btn-outline-secondary rounded-pill px-4 follow-btn-${targetUserId}`;
                    btn.innerHTML = '<i class="fa-solid fa-check me-1"></i>Following';
                } else {
                    btn.className = `btn btn-sm btn-success rounded-pill px-4 follow-btn-${targetUserId}`;
                    btn.innerHTML = '<i class="fa-solid fa-user-plus me-1"></i>Follow Explorer';
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

async function submitComment() {
    const input = document.getElementById('commentTextInput');
    const text = input.value.trim();
    if (!text) return;

    try {
        const res = await fetch('api/community-actions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=add_comment&post_id=<?= $postId ?>&comment_text=${encodeURIComponent(text)}`
        });
        const data = await res.json();
        if (data.status === 'success') {
            input.value = '';
            const noMsg = document.getElementById('noCommentsMsg');
            if (noMsg) noMsg.remove();

            const c = data.comment;
            const newCard = document.createElement('div');
            newCard.className = 'p-3 border rounded-3 bg-light';
            newCard.innerHTML = `
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <div class="rounded-circle bg-success text-white fw-bold d-flex align-items-center justify-content-center" style="width:30px;height:30px;font-size:0.8rem;">
                            ${c.user_name.charAt(0).toUpperCase()}
                        </div>
                        <div>
                            <strong class="text-dark small">${escapeHtml(c.user_name)}</strong>
                            <span class="text-muted" style="font-size:0.75rem;"> &bull; ${c.created_at}</span>
                        </div>
                    </div>
                </div>
                <p class="text-secondary small mb-0">${escapeHtml(c.text)}</p>
            `;
            document.getElementById('commentsList').prepend(newCard);
            showToast('Comment posted!', 'success');
        } else {
            showToast(data.message || 'Error', 'error');
        }
    } catch (e) {
        showToast('Network error', 'error');
    }
}

function openReportPostModal(postId) {
    const modal = new bootstrap.Modal(document.getElementById('reportPostModal'));
    modal.show();
}

async function submitPostReportDetail() {
    const reason = document.getElementById('reportReasonSelect').value;
    try {
        const res = await fetch('api/community-actions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=report_post&post_id=<?= $postId ?>&reason=${encodeURIComponent(reason)}`
        });
        const data = await res.json();
        showToast(data.message, 'success');
        bootstrap.Modal.getInstance(document.getElementById('reportPostModal')).hide();
    } catch (e) {
        showToast('Network error', 'error');
    }
}

function escapeHtml(t) {
    return String(t).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
