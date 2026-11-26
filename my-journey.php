<?php
/**
 * FORT EXPLORER - My Travel Journey & Personal Dashboard
 * Visited records, personal reflections, travel photo uploads, wishlist,
 * gamification achievements from database, travel timeline diary, and print summary.
 */

require_once __DIR__ . '/includes/header.php';
require_login();

$db = getDB();
$userId = $_SESSION['user_id'];
$currentUser = current_user();

// Handle User Travel Photo Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_travel_photo') {
    if (verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $destinationId = filter_input(INPUT_POST, 'destination_id', FILTER_VALIDATE_INT);
        $visitedId = filter_input(INPUT_POST, 'visited_id', FILTER_VALIDATE_INT);
        $caption = sanitize_input($_POST['caption'] ?? '');

        if ($destinationId && isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = upload_image($_FILES['photo'], 'uploads');
            if ($uploadResult['success']) {
                $pStmt = $db->prepare("
                    INSERT INTO travel_photos (user_id, destination_id, visited_id, image_path, caption)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $pStmt->execute([$userId, $destinationId, $visitedId ?: null, $uploadResult['path'], $caption]);
                set_flash('success', 'Travel photo uploaded and saved to your memories!');
                header("Location: my-journey.php?tab=photos");
                exit;
            } else {
                set_flash('danger', $uploadResult['error'] ?? 'Photo upload failed.');
            }
        } else {
            set_flash('danger', 'Please select a valid image file.');
        }
    } else {
        set_flash('danger', 'Security token invalid.');
    }
}

// Handle Update Travel Memory Note
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_visited_entry') {
    if (verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $visitedId = filter_input(INPUT_POST, 'visited_id', FILTER_VALIDATE_INT);
        $visitDate = sanitize_input($_POST['visit_date'] ?? date('Y-m-d'));
        $rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT) ?: 5;
        $notes = sanitize_input($_POST['experience_notes'] ?? '');
        $companion = sanitize_input($_POST['companion_type'] ?? 'Solo');

        if ($visitedId) {
            $upd = $db->prepare("
                UPDATE visited_places 
                SET visit_date = ?, rating = ?, experience_notes = ?, companion_type = ? 
                WHERE id = ? AND user_id = ?
            ");
            $upd->execute([$visitDate, $rating, $notes, $companion, $visitedId, $userId]);
            set_flash('success', 'Travel memory updated successfully!');
            header("Location: my-journey.php?tab=timeline");
            exit;
        }
    }
}

// Handle Delete Visited Record
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_visited_entry') {
    if (verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $visitedId = filter_input(INPUT_POST, 'visited_id', FILTER_VALIDATE_INT);
        if ($visitedId) {
            $del = $db->prepare("DELETE FROM visited_places WHERE id = ? AND user_id = ?");
            $del->execute([$visitedId, $userId]);
            set_flash('info', 'Visited record removed from your travel journey.');
            header("Location: my-journey.php?tab=visited");
            exit;
        }
    }
}

// Handle Remove from Wishlist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'remove_wishlist') {
    if (verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $wishlistId = filter_input(INPUT_POST, 'wishlist_id', FILTER_VALIDATE_INT);
        if ($wishlistId) {
            $del = $db->prepare("DELETE FROM wishlists WHERE id = ? AND user_id = ?");
            $del->execute([$wishlistId, $userId]);
            set_flash('info', 'Destination removed from your wishlist.');
            header("Location: my-journey.php?tab=wishlist");
            exit;
        }
    }
}

// Fetch Visited Places, Wishlist, Photos & Achievements
$visitedList = [];
$wishlistList = [];
$photosList = [];
$achievementsList = [];
$fortsConquered = 0;
$districtsExplored = 0;
$totalAltitudeClimbed = 0;

if ($db) {
    try {
        // Visited records
        $vStmt = $db->prepare("
            SELECT vp.*, d.name, d.name_mr, d.slug, d.primary_image, d.difficulty_level, d.is_shivaji_fort, d.altitude_meters,
                   c.name_en AS category_name, dist.name_en AS district_name, dist.name_mr AS district_name_mr
            FROM visited_places vp
            JOIN destinations d ON vp.destination_id = d.id
            JOIN destination_categories c ON d.category_id = c.id
            JOIN districts dist ON d.district_id = dist.id
            WHERE vp.user_id = ?
            ORDER BY vp.visit_date DESC
        ");
        $vStmt->execute([$userId]);
        $visitedList = $vStmt->fetchAll();

        // Wishlist
        $wStmt = $db->prepare("
            SELECT w.id AS wishlist_id, w.notes AS wishlist_notes, w.created_at AS added_on,
                   d.*, c.name_en AS category_name, dist.name_en AS district_name, dist.name_mr AS district_name_mr
            FROM wishlists w
            JOIN destinations d ON w.destination_id = d.id
            JOIN destination_categories c ON d.category_id = c.id
            JOIN districts dist ON d.district_id = dist.id
            WHERE w.user_id = ?
            ORDER BY w.created_at DESC
        ");
        $wStmt->execute([$userId]);
        $wishlistList = $wStmt->fetchAll();

        // User photos
        $pStmt = $db->prepare("
            SELECT tp.*, d.name AS destination_name, d.slug AS destination_slug
            FROM travel_photos tp
            JOIN destinations d ON tp.destination_id = d.id
            WHERE tp.user_id = ?
            ORDER BY tp.created_at DESC
        ");
        $pStmt->execute([$userId]);
        $photosList = $pStmt->fetchAll();

        // Count stats
        foreach ($visitedList as $v) {
            if ($v['is_shivaji_fort']) {
                $fortsConquered++;
            }
            if ($v['altitude_meters']) {
                $totalAltitudeClimbed += (int)$v['altitude_meters'];
            }
        }

        $distCountStmt = $db->prepare("
            SELECT COUNT(DISTINCT d.district_id) 
            FROM visited_places vp 
            JOIN destinations d ON vp.destination_id = d.id 
            WHERE vp.user_id = ?
        ");
        $distCountStmt->execute([$userId]);
        $districtsExplored = (int)$distCountStmt->fetchColumn();

        // Fetch all achievements with user unlocked status from database
        $achStmt = $db->prepare("
            SELECT a.*, ua.awarded_at
            FROM achievements a
            LEFT JOIN user_achievements ua ON a.id = ua.achievement_id AND ua.user_id = ?
            ORDER BY a.threshold_value ASC, a.id ASC
        ");
        $achStmt->execute([$userId]);
        $achievementsList = $achStmt->fetchAll();

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$activeTab = $_GET['tab'] ?? 'visited';
?>

<!-- Header Profile Dashboard Banner -->
<div class="py-5" style="background: linear-gradient(135deg, var(--fe-forest-dark) 0%, var(--fe-forest) 100%); color: #fff; border-bottom: 3px solid var(--fe-gold);">
    <div class="container py-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-4">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle bg-warning text-dark fw-bold d-flex align-items-center justify-content-center shadow" style="width:70px; height:70px; font-size:1.8rem;">
                    <?= strtoupper(substr($currentUser['name'], 0, 1)) ?>
                </div>
                <div>
                    <span class="badge bg-warning text-dark px-3 py-1 rounded-pill small mb-1">
                        <i class="fa-solid fa-compass me-1"></i>Explorer Profile
                    </span>
                    <h2 class="font-heritage mb-1 text-white"><?= e($currentUser['name']) ?></h2>
                    <p class="text-white-50 small mb-0">
                        <i class="fa-solid fa-location-dot me-1 text-warning"></i>Base: <?= e($currentUser['home_district'] ?? 'Pune') ?> &bull; Member since <?= format_date($currentUser['created_at']) ?>
                    </p>
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2">
                <a href="travel-summary.php" target="_blank" class="btn btn-sm btn-fe-gold fw-bold">
                    <i class="fa-solid fa-certificate me-1"></i>Print / Download Summary
                </a>
                <a href="profile.php" class="btn btn-sm btn-fe-outline-light">
                    <i class="fa-solid fa-user-pen me-1"></i>Edit Profile
                </a>
                <a href="explore.php" class="btn btn-sm btn-outline-light">
                    <i class="fa-solid fa-plus me-1"></i>Discover Places
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container py-5">
    <!-- 1. Statistics Cards Row -->
    <div class="row g-3 mb-5">
        <div class="col-lg-3 col-6">
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(22, 56, 32, 0.1); color:#163820;">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
                <div class="stat-number"><?= count($visitedList) ?></div>
                <div class="stat-label">Visited Places</div>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(139, 38, 22, 0.1); color:#8b2616;">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <div class="stat-number"><?= $fortsConquered ?></div>
                <div class="stat-label">Forts Conquered</div>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(2, 132, 199, 0.1); color:#0284c7;">
                    <i class="fa-solid fa-map-location-dot"></i>
                </div>
                <div class="stat-number"><?= $districtsExplored ?></div>
                <div class="stat-label">Districts Covered</div>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(197, 155, 39, 0.15); color:#c59b27;">
                    <i class="fa-solid fa-mountain"></i>
                </div>
                <div class="stat-number"><?= number_format($totalAltitudeClimbed) ?> m</div>
                <div class="stat-label">Total Altitude Scaled</div>
            </div>
        </div>
    </div>

    <!-- 2. Badges & Gamification Section (FEATURE 5) -->
    <div class="mb-5">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h4 class="font-heritage mb-0 text-success">
                <i class="fa-solid fa-award me-2 text-warning"></i>Explorer Achievements & Gamification
            </h4>
            <span class="badge bg-light text-dark border small">Verified Visit Milestones</span>
        </div>

        <div class="row g-3">
            <?php foreach ($achievementsList as $ach): 
                $unlocked = !empty($ach['awarded_at']);
                $currentProgress = match($ach['criteria_type']) {
                    'first_visit', 'total_visits' => count($visitedList),
                    'forts_count' => $fortsConquered,
                    'districts_count' => $districtsExplored,
                    default => 0
                };
                $pct = min(100, round(($currentProgress / max(1, $ach['threshold_value'])) * 100));
            ?>
                <div class="col-lg-4 col-md-6">
                    <div class="p-3 bg-white rounded-4 border shadow-sm h-100 <?= $unlocked ? 'border-success' : 'opacity-75' ?>">
                        <div class="d-flex align-items-center gap-3 mb-2">
                            <div class="rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 46px; height: 46px; background-color: <?= $unlocked ? $ach['badge_color'] : '#9ca3af' ?>; color: #fff; font-size: 1.2rem;">
                                <i class="fa-solid <?= e($ach['badge_icon']) ?>"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-0 text-dark"><?= e(t_val($ach['title_en'], $ach['title_mr'])) ?></h6>
                                <span class="badge <?= $unlocked ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-muted' ?>" style="font-size:0.68rem;">
                                    <?= $unlocked ? 'Unlocked &bull; ' . format_date($ach['awarded_at']) : 'In Progress' ?>
                                </span>
                            </div>
                        </div>
                        <p class="small text-muted mb-2" style="font-size:0.8rem;"><?= e(t_val($ach['description_en'], $ach['description_mr'])) ?></p>
                        
                        <!-- Progress Bar -->
                        <div class="d-flex justify-content-between small text-muted mb-1" style="font-size:0.75rem;">
                            <span>Progress</span>
                            <span><strong><?= min($ach['threshold_value'], $currentProgress) ?></strong> / <?= $ach['threshold_value'] ?></span>
                        </div>
                        <div class="progress" style="height: 5px;">
                            <div class="progress-bar <?= $unlocked ? 'bg-success' : 'bg-warning' ?>" role="progressbar" style="width: <?= $pct ?>%"></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- 3. Tabs Navigation -->
    <ul class="nav nav-pills nav-fill mb-4 bg-white p-2 rounded-4 shadow-sm border" id="journeyTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <a class="nav-link <?= $activeTab === 'visited' ? 'active btn-fe-primary' : 'text-dark' ?> rounded-pill fw-bold" href="my-journey.php?tab=visited">
                <i class="fa-solid fa-shoe-prints me-2 text-warning"></i><?= __('tab_visited') ?> (<?= count($visitedList) ?>)
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link <?= $activeTab === 'timeline' ? 'active btn-fe-primary' : 'text-dark' ?> rounded-pill fw-bold" href="my-journey.php?tab=timeline">
                <i class="fa-solid fa-timeline me-2 text-info"></i>Travel Timeline & Diary
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link <?= $activeTab === 'wishlist' ? 'active btn-fe-primary' : 'text-dark' ?> rounded-pill fw-bold" href="my-journey.php?tab=wishlist">
                <i class="fa-solid fa-heart me-2 text-danger"></i><?= __('tab_wishlist') ?> (<?= count($wishlistList) ?>)
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link <?= $activeTab === 'photos' ? 'active btn-fe-primary' : 'text-dark' ?> rounded-pill fw-bold" href="my-journey.php?tab=photos">
                <i class="fa-solid fa-camera-retro me-2 text-primary"></i><?= __('tab_photos') ?> (<?= count($photosList) ?>)
            </a>
        </li>
    </ul>

    <!-- 4. Tab Content -->
    <div class="tab-content">
        <!-- TAB 1: VISITED PLACES (CARDS) -->
        <?php if ($activeTab === 'visited'): ?>
            <?php if (!empty($visitedList)): ?>
                <div class="row g-4">
                    <?php foreach ($visitedList as $v): ?>
                        <div class="col-lg-6">
                            <div class="bg-white p-4 rounded-4 shadow-sm border h-100 d-flex flex-column justify-content-between">
                                <div>
                                    <div class="d-flex align-items-center gap-3 mb-3">
                                        <img src="<?= e($v['primary_image']) ?>" alt="<?= e($v['name']) ?>" class="rounded-3 shadow-sm" style="width: 80px; height: 80px; object-fit: cover;">
                                        <div>
                                            <div class="d-flex align-items-center gap-2">
                                                <h5 class="font-heritage mb-0 text-success"><?= e(t_val($v['name'], $v['name_mr'])) ?></h5>
                                                <?php if ($v['is_shivaji_fort']): ?>
                                                    <span class="badge bg-danger-subtle text-danger small"><i class="fa-solid fa-crown me-1"></i>Fort</span>
                                                <?php endif; ?>
                                            </div>
                                            <span class="text-muted small"><i class="fa-solid fa-location-dot me-1 text-danger"></i><?= e($v['district_name']) ?> &bull; <?= e($v['category_name']) ?></span>
                                            <div class="mt-1">
                                                <?= render_rating_stars((float)$v['rating']) ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="p-3 bg-light rounded-3 mb-3">
                                        <div class="d-flex flex-wrap justify-content-between small text-muted mb-2 border-bottom pb-2">
                                            <span><i class="fa-regular fa-calendar-check me-1 text-primary"></i>Visited on: <strong><?= format_date($v['visit_date']) ?></strong></span>
                                            <span><i class="fa-solid fa-user-group me-1 text-success"></i><?= e($v['companion_type']) ?></span>
                                        </div>
                                        <?php if (!empty($v['experience_notes'])): ?>
                                            <p class="small text-secondary mb-0 fst-italic">"<?= nl2br(e($v['experience_notes'])) ?>"</p>
                                        <?php else: ?>
                                            <p class="small text-muted mb-0">No personal reflection recorded yet.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="d-flex align-items-center justify-content-between pt-2 border-top">
                                    <button type="button" class="btn btn-sm btn-outline-dark" data-bs-toggle="modal" data-bs-target="#photoModal<?= $v['destination_id'] ?>">
                                        <i class="fa-solid fa-camera me-1 text-primary"></i>Add Photo
                                    </button>
                                    <div class="d-flex gap-2">
                                        <a href="destination.php?slug=<?= urlencode($v['slug']) ?>" class="btn btn-sm btn-fe-primary">
                                            View Details <i class="fa-solid fa-arrow-right ms-1"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Modal for uploading photo to this visit -->
                        <div class="modal fade" id="photoModal<?= $v['destination_id'] ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content rounded-4 border-0">
                                    <div class="modal-header bg-success text-white border-0">
                                        <h5 class="modal-title font-heritage"><i class="fa-solid fa-camera me-2 text-warning"></i>Upload Memory: <?= e($v['name']) ?></h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body p-4">
                                        <form method="POST" enctype="multipart/form-data">
                                            <input type="hidden" name="action" value="upload_travel_photo">
                                            <input type="hidden" name="destination_id" value="<?= $v['destination_id'] ?>">
                                            <input type="hidden" name="visited_id" value="<?= $v['id'] ?>">
                                            <?= csrf_field() ?>

                                            <div class="mb-3">
                                                <label class="form-label small fw-bold">Select Photograph</label>
                                                <input type="file" name="photo" class="form-control" accept="image/jpeg,image/png,image/webp" required>
                                                <span class="form-text small">Accepted formats: JPG, PNG, WebP (Max 5MB)</span>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label small fw-bold">Photo Caption</label>
                                                <input type="text" name="caption" class="form-control" placeholder="e.g. Sunrise from Maha Darwaja" required>
                                            </div>

                                            <div class="d-grid">
                                                <button type="submit" class="btn btn-fe-primary">
                                                    <i class="fa-solid fa-cloud-arrow-up me-1"></i>Upload Photo
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-5 bg-white rounded-4 border p-5">
                    <i class="fa-solid fa-shoe-prints text-muted fa-4x mb-3 opacity-50"></i>
                    <h5 class="font-heritage fw-bold text-dark">No Visited Places Recorded Yet</h5>
                    <p class="text-muted small max-w-500 mx-auto mb-4">
                        Start marking destinations you have visited in Maharashtra to unlock prestigious explorer badges and build your personal travel diary.
                    </p>
                    <a href="explore.php" class="btn btn-fe-primary">Browse Destinations</a>
                </div>
            <?php endif; ?>

        <!-- TAB 2: TRAVEL TIMELINE & PERSONAL DIARY (FEATURE 10) -->
        <?php elseif ($activeTab === 'timeline'): ?>
            <div class="bg-white p-4 p-md-5 rounded-4 shadow-sm border">
                <div class="d-flex align-items-center justify-content-between mb-4 border-bottom pb-3">
                    <div>
                        <h4 class="font-heritage text-success mb-1"><i class="fa-solid fa-book-journal-whills me-2 text-warning"></i>Chronological Travel Timeline</h4>
                        <span class="text-muted small">Your personal expeditions ordered by date of conquest</span>
                    </div>
                    <a href="travel-summary.php" target="_blank" class="btn btn-sm btn-outline-dark rounded-pill">
                        <i class="fa-solid fa-print me-1"></i>Print Timeline
                    </a>
                </div>

                <?php if (!empty($visitedList)): ?>
                    <div class="position-relative ps-4" style="border-left: 3px solid var(--fe-gold);">
                        <?php foreach ($visitedList as $v): ?>
                            <div class="position-relative mb-5">
                                <!-- Marker Pin on timeline -->
                                <div class="position-absolute rounded-circle bg-success border border-white shadow" style="width: 18px; height: 18px; left: -34px; top: 4px;"></div>
                                
                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                                    <span class="badge bg-dark text-white rounded-pill small">
                                        <i class="fa-regular fa-calendar-check me-1 text-warning"></i><?= format_date($v['visit_date']) ?>
                                    </span>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-secondary py-0 px-2" data-bs-toggle="modal" data-bs-target="#editVisitModal<?= $v['id'] ?>">
                                            <i class="fa-solid fa-pen me-1"></i>Edit Reflection
                                        </button>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Remove this visit record from your journey?')">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete_visited_entry">
                                            <input type="hidden" name="visited_id" value="<?= $v['id'] ?>">
                                            <button type="submit" class="btn btn-outline-danger py-0 px-2">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>

                                <div class="p-3 bg-light rounded-3 border">
                                    <div class="d-flex align-items-center gap-3 mb-2">
                                        <img src="<?= e($v['primary_image']) ?>" alt="<?= e($v['name']) ?>" class="rounded-2" style="width: 50px; height: 50px; object-fit: cover;">
                                        <div>
                                            <h6 class="fw-bold mb-0 text-dark"><?= e(t_val($v['name'], $v['name_mr'])) ?></h6>
                                            <span class="small text-muted"><?= e($v['district_name']) ?> &bull; Companion: <?= e($v['companion_type']) ?> &bull; <?= render_rating_stars((float)$v['rating']) ?></span>
                                        </div>
                                    </div>
                                    <?php if (!empty($v['experience_notes'])): ?>
                                        <p class="small text-secondary mb-0 mt-2 fst-italic">"<?= nl2br(e($v['experience_notes'])) ?>"</p>
                                    <?php else: ?>
                                        <span class="small text-muted fst-italic">No journal reflections recorded yet.</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Edit Visit Modal -->
                            <div class="modal fade" id="editVisitModal<?= $v['id'] ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content rounded-4 border-0 shadow">
                                        <div class="modal-header bg-success text-white border-0">
                                            <h5 class="modal-title font-heritage">Edit Memory: <?= e($v['name']) ?></h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body p-4">
                                            <form method="POST">
                                                <input type="hidden" name="action" value="edit_visited_entry">
                                                <input type="hidden" name="visited_id" value="<?= $v['id'] ?>">
                                                <?= csrf_field() ?>

                                                <div class="mb-3">
                                                    <label class="form-label small fw-bold">Date of Visit</label>
                                                    <input type="date" name="visit_date" class="form-control" value="<?= $v['visit_date'] ?>" required>
                                                </div>

                                                <div class="row g-2 mb-3">
                                                    <div class="col-6">
                                                        <label class="form-label small fw-bold">Companion</label>
                                                        <select name="companion_type" class="form-select">
                                                            <option value="Solo" <?= $v['companion_type'] === 'Solo' ? 'selected' : '' ?>>Solo Trek</option>
                                                            <option value="Friends" <?= $v['companion_type'] === 'Friends' ? 'selected' : '' ?>>With Friends</option>
                                                            <option value="Family" <?= $v['companion_type'] === 'Family' ? 'selected' : '' ?>>With Family</option>
                                                            <option value="Trekking Club" <?= $v['companion_type'] === 'Trekking Club' ? 'selected' : '' ?>>Trekking Group</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-6">
                                                        <label class="form-label small fw-bold">Rating</label>
                                                        <select name="rating" class="form-select">
                                                            <option value="5" <?= $v['rating'] == 5 ? 'selected' : '' ?>>5 - Exceptional</option>
                                                            <option value="4" <?= $v['rating'] == 4 ? 'selected' : '' ?>>4 - Very Good</option>
                                                            <option value="3" <?= $v['rating'] == 3 ? 'selected' : '' ?>>3 - Good</option>
                                                            <option value="2" <?= $v['rating'] == 2 ? 'selected' : '' ?>>2 - Challenging</option>
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label small fw-bold">Personal Memory Notes</label>
                                                    <textarea name="experience_notes" rows="4" class="form-control"><?= e($v['experience_notes']) ?></textarea>
                                                </div>

                                                <button type="submit" class="btn btn-fe-primary w-100">
                                                    <i class="fa-solid fa-save me-1"></i>Save Changes
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center py-4 mb-0">No travel diary entries yet. Visit a destination to start your timeline!</p>
                <?php endif; ?>
            </div>

        <!-- TAB 3: WISHLIST (WANT TO VISIT) -->
        <?php elseif ($activeTab === 'wishlist'): ?>
            <?php if (!empty($wishlistList)): ?>
                <div class="row g-4">
                    <?php foreach ($wishlistList as $w): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="fe-card">
                                <div class="fe-card-img-wrap">
                                    <img src="<?= e($w['primary_image']) ?>" alt="<?= e($w['name']) ?>" class="fe-card-img">
                                    <span class="fe-card-category-badge"><?= e($w['category_name']) ?></span>
                                    <span class="fe-card-district-badge"><?= e($w['district_name']) ?></span>
                                </div>
                                <div class="fe-card-body">
                                    <h5 class="fe-card-title"><?= e(t_val($w['name'], $w['name_mr'])) ?></h5>
                                    <?php if (!empty($w['wishlist_notes'])): ?>
                                        <p class="small text-muted fst-italic mb-2">Note: <?= e($w['wishlist_notes']) ?></p>
                                    <?php endif; ?>
                                    <p class="fe-card-desc"><?= e(t_val($w['short_description'], $w['short_description_mr'])) ?></p>
                                    
                                    <div class="fe-card-footer">
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="action" value="remove_wishlist">
                                            <input type="hidden" name="wishlist_id" value="<?= $w['wishlist_id'] ?>">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm btn-outline-danger py-1 px-2" title="Remove from wishlist">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </form>
                                        <a href="destination.php?slug=<?= urlencode($w['slug']) ?>" class="btn btn-sm btn-fe-primary">
                                            View Details <i class="fa-solid fa-arrow-right ms-1"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-5 bg-white rounded-4 border p-5">
                    <i class="fa-regular fa-heart text-muted fa-4x mb-3 opacity-50"></i>
                    <h5 class="font-heritage fw-bold text-dark">Your Wishlist is Empty</h5>
                    <p class="text-muted small max-w-500 mx-auto mb-4">
                        Discover forts, temples, and waterfalls across Maharashtra and tap the heart icon on any card to save places you want to visit!
                    </p>
                    <a href="explore.php" class="btn btn-fe-primary">Explore Places to Add</a>
                </div>
            <?php endif; ?>

        <!-- TAB 4: TRAVEL PHOTOS GALLERY -->
        <?php elseif ($activeTab === 'photos'): ?>
            <?php if (!empty($photosList)): ?>
                <div class="row g-3">
                    <?php foreach ($photosList as $p): ?>
                        <div class="col-md-4 col-sm-6">
                            <div class="bg-white p-2 rounded-3 shadow-sm border h-100">
                                <img src="<?= BASE_URL . e($p['image_path']) ?>" alt="<?= e($p['caption']) ?>" class="img-fluid rounded-2 w-100" style="height: 220px; object-fit: cover;">
                                <div class="p-2">
                                    <h6 class="small fw-bold mb-1 text-dark"><?= e($p['caption']) ?></h6>
                                    <a href="destination.php?slug=<?= urlencode($p['destination_slug']) ?>" class="small text-success text-decoration-none">
                                        <i class="fa-solid fa-location-dot me-1"></i><?= e($p['destination_name']) ?>
                                    </a>
                                    <div class="text-muted small mt-1" style="font-size:0.75rem;">Uploaded on <?= format_date($p['created_at']) ?></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-5 bg-white rounded-4 border p-5">
                    <i class="fa-solid fa-camera text-muted fa-4x mb-3 opacity-50"></i>
                    <h5 class="font-heritage fw-bold text-dark">No Travel Photos Uploaded Yet</h5>
                    <p class="text-muted small max-w-500 mx-auto mb-4">
                        After marking destinations as visited, upload your own trek photography and summit views to maintain a lifelong visual diary.
                    </p>
                    <a href="my-journey.php?tab=visited" class="btn btn-fe-primary">View Visited Destinations</a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
