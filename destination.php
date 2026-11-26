<?php
/**
 * FORT EXPLORER - Destination Details Page
 * Comprehensive destination showcase: gallery, history, Shivaji Maharaj connection,
 * architecture, transport, timings, entry fees, interactive map, reviews, wishlist & visited actions.
 * Upgraded with: Multilingual Audio Guide, QR Pass Generator, Live Weather & Mountain Advisories,
 * Nearby POIs & Facilities, Review Photos, and Helpful Votes.
 */

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/weather.php';

$db = getDB();
$slug = trim($_GET['slug'] ?? '');
$destId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$slug && !$destId) {
    header("Location: explore.php");
    exit;
}

$destination = null;
$galleryImages = [];
$reviews = [];
$nearbyDestinations = [];
$nearbyPlaces = [];
$userHelpfulVotes = [];
$isWishlisted = false;
$isVisited = false;
$visitedRecord = null;

if ($db) {
    try {
        // Fetch destination
        if ($slug) {
            $stmt = $db->prepare("
                SELECT d.*, 
                       c.slug AS category_slug, c.name_en AS category_name, c.name_mr AS category_name_mr,
                       dist.name_en AS district_name, dist.name_mr AS district_name_mr
                FROM destinations d
                JOIN destination_categories c ON d.category_id = c.id
                JOIN districts dist ON d.district_id = dist.id
                WHERE d.slug = ?
            ");
            $stmt->execute([$slug]);
        } else {
            $stmt = $db->prepare("
                SELECT d.*, 
                       c.slug AS category_slug, c.name_en AS category_name, c.name_mr AS category_name_mr,
                       dist.name_en AS district_name, dist.name_mr AS district_name_mr
                FROM destinations d
                JOIN destination_categories c ON d.category_id = c.id
                JOIN districts dist ON d.district_id = dist.id
                WHERE d.id = ?
            ");
            $stmt->execute([$destId]);
        }
        $destination = $stmt->fetch();

        if (!$destination) {
            set_flash('danger', 'Destination not found.');
            header("Location: explore.php");
            exit;
        }

        $destId = $destination['id'];

        // Fort Specific Record & References
        $fortData = null;
        $fortReferences = [];
        $fStmt = $db->prepare("
            SELECT f.*, 
                   fc.name_en AS fort_category_name_en, fc.name_mr AS fort_category_name_mr,
                   fc.traditional_name AS fort_trad_name, fc.traditional_name_mr AS fort_trad_name_mr,
                   fc.badge_icon, fc.badge_color
            FROM forts f
            LEFT JOIN fort_categories fc ON f.category_id = fc.id
            WHERE f.destination_id = ? OR f.slug = ?
            LIMIT 1
        ");
        $fStmt->execute([$destId, $destination['slug']]);
        $fortData = $fStmt->fetch(PDO::FETCH_ASSOC);

        if ($fortData) {
            $refStmt = $db->prepare("SELECT * FROM fort_references WHERE fort_id = ? ORDER BY id ASC");
            $refStmt->execute([$fortData['id']]);
            $fortReferences = $refStmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // Gallery images
        $imgStmt = $db->prepare("SELECT * FROM destination_images WHERE destination_id = ? ORDER BY is_primary DESC, display_order ASC");
        $imgStmt->execute([$destId]);
        $galleryImages = $imgStmt->fetchAll();

        // If no extra gallery images, add primary image
        if (empty($galleryImages)) {
            $galleryImages[] = [
                'image_url' => $destination['primary_image'],
                'caption' => $destination['name'],
                'attribution' => 'Heritage Archives'
            ];
        }

        // Reviews
        $revStmt = $db->prepare("
            SELECT r.*, u.name AS user_name, u.avatar AS user_avatar 
            FROM reviews r
            JOIN users u ON r.user_id = u.id
            WHERE r.destination_id = ? AND r.status = 'approved'
            ORDER BY r.helpful_votes DESC, r.created_at DESC
        ");
        $revStmt->execute([$destId]);
        $reviews = $revStmt->fetchAll();

        // Check user wishlist and visited state
        if (is_logged_in()) {
            $userId = $_SESSION['user_id'];
            $wCheck = $db->prepare("SELECT id FROM wishlists WHERE user_id = ? AND destination_id = ?");
            $wCheck->execute([$userId, $destId]);
            $isWishlisted = (bool)$wCheck->fetch();

            $vCheck = $db->prepare("SELECT * FROM visited_places WHERE user_id = ? AND destination_id = ?");
            $vCheck->execute([$userId, $destId]);
            $visitedRecord = $vCheck->fetch();
            $isVisited = (bool)$visitedRecord;

            $hvStmt = $db->prepare("SELECT review_id FROM review_helpful_votes WHERE user_id = ?");
            $hvStmt->execute([$userId]);
            $userHelpfulVotes = $hvStmt->fetchAll(PDO::FETCH_COLUMN);
        }

        // Fetch Nearby Destinations in same or adjacent district
        $nearStmt = $db->prepare("
            SELECT id, name, name_mr, slug, primary_image, difficulty_level, altitude_meters,
                   c.name_en AS category_name, dist.name_en AS district_name
            FROM destinations d
            JOIN destination_categories c ON d.category_id = c.id
            JOIN districts dist ON d.district_id = dist.id
            WHERE d.id != ? AND (d.district_id = ? OR d.category_id = ?)
            LIMIT 4
        ");
        $nearStmt->execute([$destId, $destination['district_id'], $destination['category_id']]);
        $nearbyDestinations = $nearStmt->fetchAll();

        // Fetch Nearby Places / POIs (Hotels, Food, Medical, etc.)
        $npStmt = $db->prepare("SELECT * FROM nearby_places WHERE destination_id = ? ORDER BY distance_km ASC LIMIT 10");
        $npStmt->execute([$destId]);
        $nearbyPlaces = $npStmt->fetchAll();

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Handle Review Submission with optional Photo Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_review') {
    require_login();
    if (verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $reviewRating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT) ?: 5;
        $reviewTitle = sanitize_input($_POST['review_title'] ?? '');
        $reviewText = sanitize_input($_POST['review_text'] ?? '');
        $visitSeason = sanitize_input($_POST['visit_season'] ?? 'Winter');
        $photoUrl = null;

        if (isset($_FILES['review_photo']) && $_FILES['review_photo']['error'] === UPLOAD_ERR_OK) {
            $uploaded = upload_image($_FILES['review_photo'], 'uploads');
            if ($uploaded['success']) {
                $photoUrl = $uploaded['path'];
            }
        }

        if (!empty($reviewText)) {
            $inRev = $db->prepare("
                INSERT INTO reviews (destination_id, user_id, rating, review_title, review_text, visit_season, photo_url, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'approved')
            ");
            $inRev->execute([$destId, $_SESSION['user_id'], $reviewRating, $reviewTitle, $reviewText, $visitSeason, $photoUrl]);
            set_flash('success', 'Thank you! Your experience and review have been recorded.');
            header("Location: destination.php?slug=" . urlencode($destination['slug']) . "#reviews-section");
            exit;
        } else {
            set_flash('danger', 'Please write your review feedback.');
        }
    } else {
        set_flash('danger', 'Security token invalid. Please try again.');
    }
}

// Bilingual & Audio fields
$destName = t_val($destination['name'], $destination['name_mr']);
$destDesc = t_val($destination['short_description'], $destination['short_description_mr']);
$destHist = t_val($destination['historical_significance'], $destination['historical_significance_mr']);
$destShivaji = t_val($destination['shivaji_connection'], $destination['shivaji_connection_mr']);
$districtName = t_val($destination['district_name'], $destination['district_name_mr']);
$categoryName = t_val($destination['category_name'], $destination['category_name_mr']);

$audioEn = !empty($destination['audio_summary_en']) ? $destination['audio_summary_en'] : strip_tags($destination['overview']);
$audioMr = !empty($destination['audio_summary_mr']) ? $destination['audio_summary_mr'] : $destDesc;
$audioHi = !empty($destination['audio_summary_hi']) ? $destination['audio_summary_hi'] : $destDesc;

// Live Weather & Mountain Advisory
$weather = get_destination_weather((float)$destination['latitude'], (float)$destination['longitude']);

// Dynamic QR Code link
$fullHost = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . ($_SERVER['HTTP_HOST'] ?? '127.0.0.1:8000');
$qrTargetUrl = $fullHost . "/qr-guide.php?slug=" . urlencode($destination['slug']);
$qrCodeImg = "https://api.qrserver.com/v1/create-qr-code/?size=280x280&margin=10&data=" . urlencode($qrTargetUrl);
?>

<div class="container py-4">
    <!-- Breadcrumb Navigation -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="index.php"><i class="fa-solid fa-house me-1"></i>Home</a></li>
            <li class="breadcrumb-item"><a href="explore.php">Destinations</a></li>
            <li class="breadcrumb-item"><a href="explore.php?cat=<?= e($destination['category_slug']) ?>"><?= e($categoryName) ?></a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= e($destName) ?></li>
        </ol>
    </nav>

    <!-- Header Banner Card -->
    <div class="dest-header-banner" style="background-image: url('<?= e($destination['primary_image']) ?>');">
        <div class="dest-header-overlay">
            <div class="d-flex flex-wrap gap-2 mb-2">
                <span class="badge bg-dark bg-opacity-75 border border-warning text-warning px-3 py-1.5 rounded-pill">
                    <i class="fa-solid fa-shield-halved me-1"></i><?= e($categoryName) ?>
                </span>
                <span class="badge bg-light text-dark px-3 py-1.5 rounded-pill">
                    <i class="fa-solid fa-location-dot me-1 text-danger"></i><?= e($districtName) ?>
                </span>
                <?= get_difficulty_badge($destination['difficulty_level']) ?>
                <?php if ($destination['altitude_meters']): ?>
                    <span class="badge bg-black bg-opacity-50 text-white px-3 py-1.5 rounded-pill">
                        <i class="fa-solid fa-mountain me-1"></i><?= $destination['altitude_meters'] ?> meters
                    </span>
                <?php endif; ?>
            </div>

            <h1 class="display-5 fw-bold text-white font-heritage mb-2"><?= e($destName) ?></h1>
            <p class="text-white-50 lead fs-6 max-w-700 mb-4"><?= e($destDesc) ?></p>

            <!-- Actions Bar: Wishlist, Visited, Directions, QR Pass, Audio Guide -->
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <!-- Wishlist Toggle -->
                <button type="button" class="btn btn-sm btn-light fe-wishlist-toggle-btn-detail <?= $isWishlisted ? 'btn-danger text-white' : 'btn-outline-light text-dark bg-white' ?> py-2 px-3 fw-bold rounded-pill" onclick="toggleDetailWishlist(<?= $destId ?>)">
                    <i class="fa-solid fa-heart me-1 <?= $isWishlisted ? 'text-white' : 'text-danger' ?>"></i>
                    <span id="wishlistBtnText"><?= $isWishlisted ? __('btn_saved_wishlist') : __('btn_save_wishlist') ?></span>
                </button>

                <!-- Mark as Visited Modal Trigger -->
                <button type="button" class="btn btn-sm <?= $isVisited ? 'btn-success' : 'btn-fe-gold' ?> py-2 px-3 fw-bold rounded-pill" data-bs-toggle="modal" data-bs-target="#visitedModal">
                    <i class="fa-solid <?= $isVisited ? 'fa-circle-check' : 'fa-shoe-prints' ?> me-1"></i>
                    <span><?= $isVisited ? __('btn_visited') . ' (' . format_date($visitedRecord['visit_date']) . ')' : __('btn_mark_visited') ?></span>
                </button>

                <!-- QR Code On-Site Pass Modal Trigger -->
                <button type="button" class="btn btn-sm btn-dark bg-opacity-75 text-white py-2 px-3 fw-bold rounded-pill border border-white border-opacity-25" data-bs-toggle="modal" data-bs-target="#qrModal">
                    <i class="fa-solid fa-qrcode me-1 text-warning"></i><span>On-Site QR Pass</span>
                </button>

                <!-- Audio Guide Shortcut -->
                <a href="#audioGuideSection" class="btn btn-sm btn-dark bg-opacity-75 text-white py-2 px-3 fw-bold rounded-pill border border-white border-opacity-25">
                    <i class="fa-solid fa-headphones me-1 text-info"></i><span>Audio Guide</span>
                </a>

                <!-- External Directions -->
                <a href="https://www.google.com/maps/dir/?api=1&destination=<?= $destination['latitude'] ?>,<?= $destination['longitude'] ?>" target="_blank" rel="noopener" class="btn btn-sm btn-dark bg-opacity-75 py-2 px-3 fw-bold rounded-pill">
                    <i class="fa-solid fa-diamond-turn-right me-1 text-info"></i><?= __('btn_get_directions') ?>
                </a>
            </div>
        </div>
    </div>

    <!-- Main Content Layout -->
    <div class="row g-4">
        <!-- Left Main Content Column -->
        <div class="col-lg-8">

            <!-- MULTILINGUAL AUDIO GUIDE PLAYER (FEATURE 4) -->
            <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden" id="audioGuideSection" style="background: linear-gradient(135deg, #163820 0%, #1e4d2b 100%); color: #fff;">
                <div class="card-body p-4">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3 border-bottom border-white border-opacity-10 pb-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-warning text-dark rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 44px; height: 44px; font-size: 1.2rem;">
                                <i class="fa-solid fa-headphones"></i>
                            </div>
                            <div>
                                <h5 class="mb-0 font-heritage text-white">Multilingual Audio Guide</h5>
                                <span class="small text-white-50">सह्याद्री ऑडिओ गाईड &bull; Marathi, Hindi, English narration</span>
                            </div>
                        </div>

                        <!-- Language Selector Tabs -->
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-light active main-audio-lang-btn" data-lang="mr">मराठी</button>
                            <button type="button" class="btn btn-outline-light main-audio-lang-btn" data-lang="en">English</button>
                            <button type="button" class="btn btn-outline-light main-audio-lang-btn" data-lang="hi">हिंदी</button>
                        </div>
                    </div>

                    <!-- Audio Player Controls & Progress -->
                    <div class="d-flex align-items-center gap-3 my-3">
                        <button type="button" class="btn btn-warning rounded-circle shadow d-flex align-items-center justify-content-center flex-shrink-0" id="mainAudioPlayBtn" style="width: 52px; height: 52px; font-size: 1.3rem;">
                            <i class="fa-solid fa-play"></i>
                        </button>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between text-white-50 small mb-1">
                                <span id="mainAudioCurrentTime">00:00</span>
                                <span id="mainAudioStatusText"><i class="fa-solid fa-volume-high me-1"></i>Ready to Play</span>
                                <span id="mainAudioTotalTime">~2:00</span>
                            </div>
                            <div class="progress" style="height: 7px; background-color: rgba(255,255,255,0.2); cursor: pointer;" id="mainAudioProgressContainer">
                                <div class="progress-bar bg-warning" id="mainAudioProgressBar" role="progressbar" style="width: 0%"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Playback Controls, Speed & Transcript Accordion Toggle -->
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 pt-2 border-top border-white border-opacity-10">
                        <div class="d-flex align-items-center gap-2">
                            <span class="small text-white-50">Playback Speed:</span>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-sm btn-outline-light py-0 px-2 main-audio-speed-btn" data-speed="0.8">0.8x</button>
                                <button type="button" class="btn btn-sm btn-outline-light active py-0 px-2 main-audio-speed-btn" data-speed="1.0">1.0x</button>
                                <button type="button" class="btn btn-sm btn-outline-light py-0 px-2 main-audio-speed-btn" data-speed="1.2">1.2x</button>
                            </div>
                        </div>
                        <button class="btn btn-sm text-white-50 text-decoration-none" type="button" data-bs-toggle="collapse" data-bs-target="#audioTranscriptCollapse">
                            <i class="fa-solid fa-align-left me-1"></i>View Transcript
                        </button>
                    </div>

                    <!-- Transcript Content -->
                    <div class="collapse mt-3" id="audioTranscriptCollapse">
                        <div class="p-3 rounded-3" style="background-color: rgba(0,0,0,0.3); font-size: 0.9rem; line-height: 1.7;" id="mainAudioTranscriptText">
                            <?= nl2br(e($audioMr)) ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- UNESCO World Heritage Maratha Military Landscapes Component Badge -->
            <?php if (!empty($destination['is_unesco_site']) || ($fortData && !empty($fortData['is_unesco_site']))): ?>
                <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden" style="background: linear-gradient(135deg, #2b1f02 0%, #4a3809 100%); color: #fff; border: 2px solid #c59b27 !important;">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center gap-3 mb-2">
                            <div class="bg-warning text-dark rounded-circle d-flex align-items-center justify-content-center shadow" style="width: 50px; height: 50px; font-size: 1.4rem;">
                                <i class="fa-solid fa-crown"></i>
                            </div>
                            <div>
                                <span class="badge px-3 py-1 rounded-pill mb-1" style="background: rgba(197, 155, 39, 0.35); border: 1px solid #c59b27; color: #f5d77f; font-size: 0.75rem;">
                                    UNESCO WORLD HERITAGE TENTATIVE NOMINATION
                                </span>
                                <h4 class="mb-0 font-heritage text-white">
                                    <?= is_marathi() ? 'मराठा सैनिकी भूदृश्ये (UNESCO जागतिक वारसा घटक)' : 'Maratha Military Landscapes Component' ?>
                                </h4>
                            </div>
                        </div>
                        <p class="text-white-50 small mb-0 mt-2" style="line-height: 1.7;">
                            <?= e($fortData['unesco_inscription_details'] ?? $destination['unesco_details'] ?? 'Nominated as part of the 11 components of the Maratha Military Landscapes of India, reflecting exceptional universal military strategy, stone masonry, and natural landscape defense under Chhatrapati Shivaji Maharaj.') ?>
                        </p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- 1. Overview & Image Gallery -->
            <div class="dest-detail-card">
                <h4 class="font-heritage mb-3 text-success">
                    <i class="fa-solid fa-eye me-2 text-warning"></i><?= __('label_overview') ?>
                </h4>
                <div class="text-secondary fs-6 mb-4" style="line-height: 1.8;">
                    <?= nl2br(e($destination['overview'])) ?>
                </div>

                <!-- Gallery Grid & Main Viewer -->
                <?php if (count($galleryImages) > 1): ?>
                    <h5 class="fw-bold font-heritage mb-3 text-dark"><i class="fa-solid fa-images me-2 text-primary"></i>Photo Gallery</h5>
                    <div class="mb-3">
                        <img id="mainGalleryImage" src="<?= e($galleryImages[0]['image_url']) ?>" alt="<?= e($destName) ?>" class="img-fluid rounded-3 w-100 shadow-sm" style="max-height: 420px; object-fit: cover;">
                    </div>
                    <div class="row g-2">
                        <?php foreach ($galleryImages as $idx => $img): ?>
                            <div class="col-3 col-sm-2">
                                <img src="<?= e($img['image_url']) ?>" alt="Thumbnail" class="img-fluid dest-gallery-thumb w-100 <?= $idx === 0 ? 'active' : '' ?>" data-full-img="<?= e($img['image_url']) ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- 2. Historical Background & Significance -->
            <?php if (!empty($destHist)): ?>
                <div class="dest-detail-card">
                    <h4 class="font-heritage mb-3 text-success">
                        <i class="fa-solid fa-landmark me-2 text-warning"></i><?= __('label_history') ?>
                    </h4>
                    <div class="text-secondary fs-6" style="line-height: 1.8;">
                        <?= nl2br(e($destHist)) ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- 3. Connection with Chhatrapati Shivaji Maharaj -->
            <?php if (!empty($destShivaji)): ?>
                <div class="dest-detail-card border-start border-4 border-warning bg-light-subtle">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <i class="fa-solid fa-crown text-warning fs-4"></i>
                        <h4 class="font-heritage mb-0 text-dark"><?= __('label_shivaji_connection') ?></h4>
                    </div>
                    <div class="text-secondary fs-6" style="line-height: 1.8;">
                        <?= nl2br(e($destShivaji)) ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- 4. Architecture & Major Attractions -->
            <div class="dest-detail-card">
                <h4 class="font-heritage mb-3 text-success">
                    <i class="fa-solid fa-monument me-2 text-warning"></i><?= __('label_architecture') ?>
                </h4>
                <?php if ($destination['architecture_notes']): ?>
                    <p class="text-secondary mb-4"><?= nl2br(e($destination['architecture_notes'])) ?></p>
                <?php endif; ?>

                <?php if ($destination['major_attractions']): ?>
                    <h5 class="fw-bold font-heritage mb-2 text-dark"><i class="fa-solid fa-star me-2 text-warning"></i><?= __('label_major_attractions') ?></h5>
                    <div class="p-3 bg-light rounded-3">
                        <?= nl2br(e($destination['major_attractions'])) ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- 4B. Fort Classification, Dynasties & Key Structures (When Available) -->
            <?php if ($fortData): ?>
                <div class="dest-detail-card border-top border-3 border-success">
                    <h4 class="font-heritage mb-3 text-success">
                        <i class="fa-solid fa-shield-halved me-2 text-warning"></i><?= is_marathi() ? 'दुर्ग स्थापत्य, राजघराणी व जलव्यवस्थापन' : 'Fortification, Dynasties & Water Engineering' ?>
                    </h4>

                    <div class="row g-3 mb-3">
                        <!-- Traditional Category -->
                        <div class="col-md-6">
                            <div class="p-3 rounded-3 bg-light border">
                                <span class="small text-muted d-block fw-bold"><?= is_marathi() ? 'पारंपरिक दुर्ग प्रकार' : 'Traditional Classification' ?></span>
                                <strong class="text-success fs-6">
                                    <i class="fa-solid <?= e($fortData['badge_icon'] ?: 'fa-shield') ?> me-1"></i>
                                    <?= e(t_val($fortData['fort_trad_name'] . ' (' . $fortData['fort_category_name_en'] . ')', $fortData['fort_trad_name_mr'])) ?>
                                </strong>
                            </div>
                        </div>

                        <!-- Historical Period / Dynasties -->
                        <div class="col-md-6">
                            <div class="p-3 rounded-3 bg-light border">
                                <span class="small text-muted d-block fw-bold"><?= is_marathi() ? 'ऐतिहासिक कालखंड व राजवटी' : 'Historical Period & Rulers' ?></span>
                                <strong class="text-dark small">
                                    <i class="fa-solid fa-hourglass-half text-warning me-1"></i><?= e($fortData['historical_period'] ?: 'Maratha Swarajya') ?>
                                </strong>
                                <?php if (!empty($fortData['dynasties_rulers'])): ?>
                                    <div class="text-muted small mt-1" style="font-size:0.75rem;"><?= e($fortData['dynasties_rulers']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Key Bastions & Structures -->
                        <?php if (!empty($fortData['key_structures'])): ?>
                            <div class="col-12">
                                <div class="p-3 rounded-3 bg-light border">
                                    <span class="small text-muted d-block fw-bold mb-1"><i class="fa-solid fa-archway text-primary me-1"></i><?= is_marathi() ? 'महत्त्वाचे बुरुज, दरवाजे व वास्तू' : 'Key Bastions, Gates & Citadel Structures' ?></span>
                                    <span class="text-dark small"><?= e($fortData['key_structures']) ?></span>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Water Sources -->
                        <?php if (!empty($fortData['water_sources'])): ?>
                            <div class="col-12">
                                <div class="p-3 rounded-3 bg-light border">
                                    <span class="small text-muted d-block fw-bold mb-1"><i class="fa-solid fa-droplet text-info me-1"></i><?= is_marathi() ? 'जलस्त्रोत व कातळ-टाके' : 'Perennial Water Reservoirs & Rock Tanks' ?></span>
                                    <span class="text-dark small"><?= e($fortData['water_sources']) ?></span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 4C. Official References & Archival Citations -->
                <?php if (!empty($fortReferences) || !empty($fortData['reference_sources'])): ?>
                    <div class="dest-detail-card border-top border-3 border-secondary">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h4 class="font-heritage mb-0 text-dark">
                                <i class="fa-solid fa-book-bookmark me-2 text-warning"></i><?= is_marathi() ? 'अधिकृत संदर्भ व गॅझेटिअर नोंदी' : 'Official Historical References & Gazetteers' ?>
                            </h4>
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1">
                                <i class="fa-solid fa-check-double me-1"></i>Verified Record
                            </span>
                        </div>
                        <p class="text-muted small mb-3">
                            <?= is_marathi()
                                ? 'या किल्ल्याची ऐतिहासिक माहिती भारतीय पुरातत्व सर्वेक्षण (ASI), महाराष्ट्र राज्य गॅझेटिअर्स आणि समकालीन शिवकालीन दस्तऐवजांच्या आधारे प्रमाणित करण्यात आली आहे.'
                                : 'All historical information, dynasties, and strategic importance have been verified against Archaeological Survey of India (ASI) protected monument registers and Maharashtra State Gazetteers.' ?>
                        </p>
                        <div class="p-3 rounded-3 bg-light border">
                            <ul class="mb-0 ps-3 small text-secondary">
                                <?php if (!empty($fortReferences)): ?>
                                    <?php foreach ($fortReferences as $ref): ?>
                                        <li class="mb-1"><strong><?= e($ref['source_title']) ?></strong> &bull; <?= e($ref['author_or_body'] ?: 'State Archives') ?></li>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <li class="mb-1"><?= e($fortData['reference_sources']) ?></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- 5. How to Reach & Distances -->
            <div class="dest-detail-card">
                <h4 class="font-heritage mb-3 text-success">
                    <i class="fa-solid fa-route me-2 text-warning"></i><?= __('label_how_to_reach') ?>
                </h4>
                
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="p-3 border rounded-3 bg-white h-100">
                            <h6 class="fw-bold text-success mb-2"><i class="fa-solid fa-car me-2"></i><?= __('label_by_road') ?></h6>
                            <p class="small text-muted mb-0"><?= e($destination['how_to_reach_road'] ?: 'Well-connected via state highways. Private vehicle or ST bus recommended.') ?></p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 border rounded-3 bg-white h-100">
                            <h6 class="fw-bold text-primary mb-2"><i class="fa-solid fa-train me-2"></i><?= __('label_by_rail') ?></h6>
                            <p class="small text-muted mb-0"><?= e($destination['how_to_reach_rail'] ?: 'Connect via nearest major junction.') ?></p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 border rounded-3 bg-white h-100">
                            <h6 class="fw-bold text-danger mb-2"><i class="fa-solid fa-plane me-2"></i><?= __('label_by_air') ?></h6>
                            <p class="small text-muted mb-0"><?= e($destination['how_to_reach_air'] ?: 'Pune or Mumbai International Airports.') ?></p>
                        </div>
                    </div>
                </div>

                <!-- Distance from Pune & Mumbai -->
                <div class="p-3 rounded-3 bg-light border d-flex flex-wrap gap-4 align-items-center">
                    <div>
                        <span class="small text-muted d-block"><?= __('label_from_pune') ?></span>
                        <strong class="fs-5 text-dark"><?= $destination['distance_from_pune_km'] ? $destination['distance_from_pune_km'] . ' km' : 'Approx 100 km' ?></strong>
                    </div>
                    <div class="vr d-none d-sm-block"></div>
                    <div>
                        <span class="small text-muted d-block"><?= __('label_from_mumbai') ?></span>
                        <strong class="fs-5 text-dark"><?= $destination['distance_from_mumbai_km'] ? $destination['distance_from_mumbai_km'] . ' km' : 'Approx 180 km' ?></strong>
                    </div>
                </div>
            </div>

            <!-- 6. NEARBY PLACES & POIs (FEATURE 11) -->
            <?php if (!empty($nearbyPlaces)): ?>
                <div class="dest-detail-card">
                    <h4 class="font-heritage mb-3 text-success">
                        <i class="fa-solid fa-hotel me-2 text-warning"></i>Nearby Accommodations, Food & Medical
                    </h4>
                    <div class="row g-3">
                        <?php foreach ($nearbyPlaces as $poi): 
                            $poiIcon = match($poi['place_type']) {
                                'hotel' => 'fa-bed text-primary',
                                'restaurant' => 'fa-utensils text-warning',
                                'hospital' => 'fa-hospital text-danger',
                                'temple' => 'fa-om text-warning',
                                'waterfall' => 'fa-water text-info',
                                'fuel_station' => 'fa-gas-pump text-secondary',
                                default => 'fa-map-pin text-success'
                            };
                        ?>
                            <div class="col-md-6">
                                <div class="p-3 border rounded-3 bg-white h-100 d-flex gap-3 align-items-start">
                                    <div class="fs-4 mt-1"><i class="fa-solid <?= $poiIcon ?>"></i></div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-baseline">
                                            <strong class="text-dark small"><?= e($poi['name']) ?></strong>
                                            <span class="badge bg-light text-dark border"><?= $poi['distance_km'] ?> km</span>
                                        </div>
                                        <span class="text-muted d-block" style="font-size:0.75rem;"><?= e(ucfirst(str_replace('_', ' ', $poi['place_type']))) ?> &bull; <?= e($poi['address'] ?? 'Nearby base village') ?></span>
                                        <?php if (!empty($poi['contact_phone'])): ?>
                                            <a href="tel:<?= e($poi['contact_phone']) ?>" class="small text-decoration-none mt-1 d-inline-block text-success">
                                                <i class="fa-solid fa-phone me-1"></i><?= e($poi['contact_phone']) ?>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- 7. Interactive Map -->
            <div class="dest-detail-card">
                <h4 class="font-heritage mb-3 text-success">
                    <i class="fa-solid fa-map-location-dot me-2 text-warning"></i><?= is_marathi() ? 'भौगोलिक स्थान व नकाशा' : 'Geographic Map & Directions' ?>
                </h4>
                <p class="small text-muted mb-3"><i class="fa-solid fa-location-dot text-danger me-1"></i><?= e($destination['address']) ?></p>
                <div id="destMap" style="height: 380px; border-radius: 12px;" class="border"></div>
            </div>

            <!-- 8. Visitor Reviews & Rating Section (FEATURE 14) -->
            <div class="dest-detail-card" id="reviews-section">
                <div class="d-flex align-items-center justify-content-between mb-4 border-bottom pb-3">
                    <h4 class="font-heritage mb-0 text-success">
                        <i class="fa-solid fa-comments me-2 text-warning"></i><?= __('label_reviews_ratings') ?> (<?= count($reviews) ?>)
                    </h4>
                    <?php if (is_logged_in()): ?>
                        <button type="button" class="btn btn-sm btn-fe-primary" data-bs-toggle="collapse" data-bs-target="#writeReviewCollapse">
                            <i class="fa-solid fa-pen-nib me-1"></i><?= __('label_write_review') ?>
                        </button>
                    <?php endif; ?>
                </div>

                <!-- Write Review Form (Collapsible) -->
                <?php if (is_logged_in()): ?>
                    <div class="collapse mb-4" id="writeReviewCollapse">
                        <div class="p-4 bg-light rounded-4 border">
                            <h5 class="fw-bold font-heritage mb-3 text-dark"><?= __('label_write_review') ?></h5>
                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="submit_review">
                                <?= csrf_field() ?>

                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">Rating</label>
                                        <div class="rating-picker d-flex align-items-center gap-2 text-warning fs-5">
                                            <input type="hidden" name="rating" value="5">
                                            <i class="fa-solid fa-star rating-star-icon" data-value="1" role="button"></i>
                                            <i class="fa-solid fa-star rating-star-icon" data-value="2" role="button"></i>
                                            <i class="fa-solid fa-star rating-star-icon" data-value="3" role="button"></i>
                                            <i class="fa-solid fa-star rating-star-icon" data-value="4" role="button"></i>
                                            <i class="fa-solid fa-star rating-star-icon" data-value="5" role="button"></i>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">Visit Season</label>
                                        <select name="visit_season" class="form-select form-select-sm">
                                            <option value="Monsoon">Monsoon (June - Sept)</option>
                                            <option value="Winter" selected>Winter (Oct - Feb)</option>
                                            <option value="Summer">Summer (March - May)</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small fw-bold">Review Title</label>
                                        <input type="text" name="review_title" class="form-control" placeholder="Brief summary of your experience" required>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small fw-bold">Your Review & Practical Tips</label>
                                        <textarea name="review_text" rows="4" class="form-control" placeholder="Share your experience, trekking trail conditions, food availability, etc." required></textarea>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small fw-bold"><i class="fa-solid fa-camera me-1"></i>Upload Trek Photo (Optional)</label>
                                        <input type="file" name="review_photo" class="form-control form-control-sm" accept="image/jpeg,image/png,image/webp">
                                        <span class="text-muted" style="font-size:0.75rem;">JPG, PNG, or WebP. Max 5MB.</span>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-fe-primary">
                                    <i class="fa-solid fa-paper-plane me-1"></i><?= __('btn_submit_review') ?>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-light border mb-4 d-flex align-items-center justify-content-between">
                        <span class="small text-muted"><i class="fa-solid fa-lock me-2 text-warning"></i>Sign in to share your travel experience and rating.</span>
                        <a href="login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="btn btn-sm btn-outline-dark">Sign In</a>
                    </div>
                <?php endif; ?>

                <!-- Reviews Listing -->
                <?php if (!empty($reviews)): ?>
                    <div class="d-flex flex-column gap-3">
                        <?php foreach ($reviews as $rev): 
                            $isHelpful = in_array($rev['id'], $userHelpfulVotes, true);
                        ?>
                            <div class="p-3 border rounded-3 bg-white" id="review-card-<?= $rev['id'] ?>">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-circle bg-success text-white fw-bold d-flex align-items-center justify-content-center" style="width:34px;height:34px;font-size:0.85rem;">
                                            <?= strtoupper(substr($rev['user_name'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <strong class="d-block text-dark small"><?= e($rev['user_name']) ?></strong>
                                            <span class="text-muted" style="font-size:0.75rem;"><i class="fa-solid fa-cloud-sun me-1"></i>Visited in <?= e($rev['visit_season']) ?> &bull; <?= format_date($rev['created_at']) ?></span>
                                        </div>
                                    </div>
                                    <div>
                                        <?= render_rating_stars((float)$rev['rating']) ?>
                                    </div>
                                </div>

                                <?php if (!empty($rev['review_title'])): ?>
                                    <h6 class="fw-bold mb-1 text-dark"><?= e($rev['review_title']) ?></h6>
                                <?php endif; ?>
                                <p class="text-secondary small mb-2"><?= nl2br(e($rev['review_text'])) ?></p>

                                <?php if (!empty($rev['photo_url'])): ?>
                                    <div class="mb-2">
                                        <img src="<?= e($rev['photo_url']) ?>" alt="User travel photo" class="img-fluid rounded-3 border" style="max-height: 220px; object-fit: cover;">
                                    </div>
                                <?php endif; ?>

                                <!-- Helpful votes & report actions -->
                                <div class="d-flex align-items-center justify-content-between pt-2 border-top">
                                    <button type="button" class="btn btn-sm <?= $isHelpful ? 'btn-success text-white' : 'btn-outline-secondary' ?> py-1 px-2.5 rounded-pill helpful-btn" style="font-size:0.75rem;" onclick="voteHelpfulReview(<?= $rev['id'] ?>)">
                                        <i class="fa-solid fa-thumbs-up me-1"></i>Helpful (<span id="helpful-cnt-<?= $rev['id'] ?>"><?= (int)$rev['helpful_votes'] ?></span>)
                                    </button>
                                    <button type="button" class="btn btn-sm btn-link text-muted p-0 text-decoration-none" style="font-size:0.75rem;" onclick="openReportReviewModal(<?= $rev['id'] ?>)">
                                        <i class="fa-regular fa-flag me-1"></i>Report
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted small fst-italic mb-0">No reviews yet. Be the first to review this historic place!</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right Quick Info & Travel Sidebar Column -->
        <div class="col-lg-4">
            <!-- LIVE WEATHER & SAHYADRI ADVISORY WIDGET (FEATURE 8) -->
            <div class="dest-detail-card border-top border-4 border-warning mb-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h5 class="font-heritage mb-0 text-dark"><i class="fa-solid fa-cloud-sun me-2 text-warning"></i>Live Weather</h5>
                    <span class="badge bg-secondary-subtle text-dark small">Live Forecast</span>
                </div>

                <div class="d-flex align-items-center gap-3 mb-3 p-3 bg-light rounded-3">
                    <div class="display-6 text-warning">
                        <i class="fa-solid <?= $weather['precipitation_probability'] > 40 ? 'fa-cloud-showers-heavy' : 'fa-sun' ?>"></i>
                    </div>
                    <div>
                        <div class="h3 fw-bold mb-0 text-dark"><?= $weather['temperature'] ?>°C</div>
                        <span class="small text-muted fw-bold"><?= e($weather['weather_description']) ?></span>
                    </div>
                </div>

                <div class="row g-2 text-center mb-3">
                    <div class="col-6">
                        <div class="p-2 border rounded-2 bg-white">
                            <span class="text-muted d-block" style="font-size:0.75rem;">Rain Probability</span>
                            <strong class="text-dark small"><i class="fa-solid fa-umbrella text-primary me-1"></i><?= $weather['precipitation_probability'] ?>%</strong>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 border rounded-2 bg-white">
                            <span class="text-muted d-block" style="font-size:0.75rem;">Wind Speed</span>
                            <strong class="text-dark small"><i class="fa-solid fa-wind text-secondary me-1"></i><?= $weather['wind_speed'] ?> km/h</strong>
                        </div>
                    </div>
                </div>

                <!-- Advisory Alert -->
                <div class="alert <?= $weather['advisory_level'] === 'danger' ? 'alert-danger' : ($weather['advisory_level'] === 'warning' ? 'alert-warning' : 'alert-info') ?> p-2.5 rounded-3 mb-0" style="font-size:0.8rem;">
                    <i class="fa-solid <?= $weather['advisory_level'] === 'danger' ? 'fa-triangle-exclamation text-danger' : 'fa-circle-info' ?> me-1"></i>
                    <strong>Sahyadri Advisory:</strong> <?= e($weather['advisory']) ?>
                </div>
            </div>

            <!-- Practical Travel Info Box -->
            <div class="dest-detail-card border-top border-4 border-success mb-4">
                <h5 class="font-heritage mb-3 text-dark"><i class="fa-solid fa-clipboard-check me-2 text-warning"></i>Practical Info</h5>

                <!-- Best Season -->
                <div class="dest-info-pill">
                    <span class="small text-muted d-block fw-bold"><?= __('label_best_season') ?></span>
                    <strong class="text-dark small"><i class="fa-solid fa-calendar-days me-1 text-primary"></i><?= e($destination['best_season']) ?></strong>
                </div>

                <!-- Opening Hours with Verification Tag -->
                <div class="dest-info-pill">
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="small text-muted fw-bold"><?= __('label_opening_hours') ?></span>
                        <span class="badge <?= $destination['is_timing_verified'] ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-dark' ?>" style="font-size: 0.68rem;">
                            <?= $destination['is_timing_verified'] ? __('verified_tag') : __('unverified_tag') ?>
                        </span>
                    </div>
                    <strong class="text-dark small d-block mt-1"><i class="fa-solid fa-clock me-1 text-warning"></i><?= e($destination['opening_hours']) ?></strong>
                </div>

                <!-- Entry Fees -->
                <div class="dest-info-pill">
                    <span class="small text-muted d-block fw-bold"><?= __('label_entry_fees') ?></span>
                    <strong class="text-dark small"><i class="fa-solid fa-ticket me-1 text-danger"></i><?= e($destination['entry_fees']) ?></strong>
                </div>

                <!-- Facilities -->
                <?php if ($destination['facilities']): ?>
                    <div class="mb-3">
                        <span class="small text-muted d-block fw-bold mb-1"><?= __('label_facilities') ?></span>
                        <div class="d-flex flex-wrap gap-1">
                            <?php foreach (explode(',', $destination['facilities']) as $fac): ?>
                                <span class="badge bg-secondary-subtle text-dark border small"><?= trim(e($fac)) ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Safety Guidelines -->
                <?php if ($destination['safety_tips']): ?>
                    <div class="p-3 bg-warning-subtle rounded-3 border border-warning mb-4">
                        <h6 class="small fw-bold text-dark mb-1"><i class="fa-solid fa-triangle-exclamation me-1 text-danger"></i><?= __('label_safety_tips') ?></h6>
                        <p class="small text-secondary mb-0"><?= nl2br(e($destination['safety_tips'])) ?></p>
                    </div>
                <?php endif; ?>

                <!-- Nearby Attractions -->
                <?php if (!empty($nearbyDestinations)): ?>
                    <h6 class="font-heritage mb-2 text-dark"><i class="fa-solid fa-compass me-1 text-success"></i><?= __('label_nearby') ?></h6>
                    <div class="d-flex flex-column gap-2 mb-3">
                        <?php foreach ($nearbyDestinations as $near): ?>
                            <a href="destination.php?slug=<?= urlencode($near['slug']) ?>" class="p-2 border rounded-3 text-decoration-none d-flex align-items-center gap-2 bg-white text-dark hover-shadow">
                                <img src="<?= e($near['primary_image']) ?>" alt="<?= e($near['name']) ?>" class="rounded-2" style="width: 50px; height: 50px; object-fit: cover;">
                                <div class="overflow-hidden">
                                    <h6 class="mb-0 text-truncate small fw-bold text-success"><?= e(t_val($near['name'], $near['name_mr'])) ?></h6>
                                    <span class="text-muted" style="font-size: 0.75rem;"><?= e($near['district_name']) ?> &bull; <?= e($near['category_name']) ?></span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- ON-SITE QR CODE CARD (FEATURE 3) -->
            <div class="dest-detail-card border-top border-4 border-dark text-center">
                <span class="badge bg-warning text-dark px-3 py-1 rounded-pill small mb-2">
                    <i class="fa-solid fa-qrcode me-1"></i>On-Site Smart Guide
                </span>
                <h6 class="font-heritage text-dark fw-bold mb-2">Scan at the Fort</h6>
                <div class="p-2 bg-white rounded-3 border d-inline-block shadow-sm mb-3">
                    <img src="<?= e($qrCodeImg) ?>" alt="QR Code Guide" class="img-fluid" style="width: 170px; height: 170px;">
                </div>
                <p class="small text-muted mb-3" style="font-size:0.8rem;">
                    Scan with your mobile device on arrival to access the instant Audio Guide, history timeline, and emergency services.
                </p>
                <div class="d-flex gap-2 justify-content-center">
                    <a href="<?= e($qrTargetUrl) ?>" target="_blank" class="btn btn-sm btn-outline-dark rounded-pill">
                        <i class="fa-solid fa-arrow-up-right-from-square me-1"></i>Open Guide
                    </a>
                    <a href="<?= e($qrCodeImg) ?>" download="fortexplorer-qr-<?= e($destination['slug']) ?>.png" class="btn btn-sm btn-fe-gold rounded-pill">
                        <i class="fa-solid fa-download me-1"></i>Save QR
                    </a>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- QR Modal -->
<div class="modal fade" id="qrModal" tabindex="-1" aria-labelledby="qrModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg text-center p-4">
            <div class="modal-header border-0 pb-0 justify-content-end">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <div class="badge bg-warning text-dark px-3 py-1.5 rounded-pill mb-2">
                    <i class="fa-solid fa-shield-halved me-1"></i>Fort Explorer Heritage Pass
                </div>
                <h4 class="font-heritage fw-bold text-dark mb-1"><?= e($destName) ?></h4>
                <p class="text-muted small mb-3">Direct link to Mobile Heritage Audio Guide & Field Information</p>
                
                <div class="p-3 bg-light rounded-4 border d-inline-block mb-3 shadow-sm">
                    <img src="<?= e($qrCodeImg) ?>" alt="QR Code" class="img-fluid" style="width: 220px; height: 220px;">
                </div>

                <div class="input-group input-group-sm mb-3">
                    <input type="text" class="form-control" value="<?= e($qrTargetUrl) ?>" id="qrTargetInput" readonly>
                    <button class="btn btn-dark" type="button" onclick="navigator.clipboard.writeText(document.getElementById('qrTargetInput').value); showToast('Link copied to clipboard!', 'info');">
                        <i class="fa-regular fa-copy"></i>
                    </button>
                </div>

                <div class="d-flex justify-content-center gap-2">
                    <a href="<?= e($qrCodeImg) ?>" download="fortexplorer-qr-<?= e($destination['slug']) ?>.png" class="btn btn-sm btn-success rounded-pill px-3">
                        <i class="fa-solid fa-download me-1"></i>Download QR Image
                    </a>
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" onclick="window.print()">
                        <i class="fa-solid fa-print me-1"></i>Print Card
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Mark as Visited Modal -->
<div class="modal fade" id="visitedModal" tabindex="-1" aria-labelledby="visitedModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header bg-success text-white border-0">
                <h5 class="modal-title font-heritage" id="visitedModalLabel"><i class="fa-solid fa-shoe-prints me-2 text-warning"></i>Mark as Visited</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <?php if (is_logged_in()): ?>
                    <form id="visitedRecordForm">
                        <input type="hidden" name="destination_id" value="<?= $destId ?>">
                        
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Date of Visit</label>
                            <input type="date" name="visit_date" class="form-control" value="<?= $visitedRecord['visit_date'] ?? date('Y-m-d') ?>" required>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-bold">Companion</label>
                                <select name="companion_type" class="form-select">
                                    <option value="Solo" <?= ($visitedRecord['companion_type'] ?? '') === 'Solo' ? 'selected' : '' ?>>Solo Trek</option>
                                    <option value="Friends" <?= ($visitedRecord['companion_type'] ?? '') === 'Friends' ? 'selected' : '' ?>>With Friends</option>
                                    <option value="Family" <?= ($visitedRecord['companion_type'] ?? '') === 'Family' ? 'selected' : '' ?>>With Family</option>
                                    <option value="Trekking Club" <?= ($visitedRecord['companion_type'] ?? '') === 'Trekking Club' ? 'selected' : '' ?>>Trekking Group</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold">Rating (1-5)</label>
                                <select name="rating" class="form-select">
                                    <option value="5" <?= ($visitedRecord['rating'] ?? 5) == 5 ? 'selected' : '' ?>>5 - Exceptional</option>
                                    <option value="4" <?= ($visitedRecord['rating'] ?? 5) == 4 ? 'selected' : '' ?>>4 - Very Good</option>
                                    <option value="3" <?= ($visitedRecord['rating'] ?? 5) == 3 ? 'selected' : '' ?>>3 - Good</option>
                                    <option value="2" <?= ($visitedRecord['rating'] ?? 5) == 2 ? 'selected' : '' ?>>2 - Challenging</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Personal Travel Memories & Experience</label>
                            <textarea name="experience_notes" rows="3" class="form-control" placeholder="Memorable viewpoints, sunrise/sunset, or obstacles encountered..."><?= e($visitedRecord['experience_notes'] ?? '') ?></textarea>
                        </div>

                        <div class="d-grid">
                            <button type="button" class="btn btn-fe-primary" onclick="submitVisitedForm()">
                                <i class="fa-solid fa-save me-1"></i>Save Journey Record
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="text-center py-3">
                        <i class="fa-solid fa-user-lock text-warning fa-3x mb-3"></i>
                        <h6 class="fw-bold">Sign In Required</h6>
                        <p class="text-muted small mb-3">Please sign in to keep a personal record of places you have visited.</p>
                        <a href="login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="btn btn-fe-primary btn-sm">Sign In Now</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Report Review Modal -->
<div class="modal fade" id="reportReviewModal" tabindex="-1" aria-labelledby="reportReviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title font-heritage" id="reportReviewModalLabel"><i class="fa-regular fa-flag text-danger me-2"></i>Report Review</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="reportReviewId" value="">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Reason for Report</label>
                    <select class="form-select" id="reportReviewReason">
                        <option value="Inappropriate or abusive language">Inappropriate or abusive language</option>
                        <option value="Fake or misleading historical facts">Fake or misleading historical facts</option>
                        <option value="Commercial spam or advertising">Commercial spam or advertising</option>
                        <option value="Other concern">Other concern</option>
                    </select>
                </div>
                <button type="button" class="btn btn-danger w-100" onclick="submitReviewReport()">
                    <i class="fa-solid fa-paper-plane me-1"></i>Submit Report to Admins
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // 1. Initialize Single Destination Leaflet Map
    const lat = <?= (float)$destination['latitude'] ?>;
    const lng = <?= (float)$destination['longitude'] ?>;

    if (!isNaN(lat) && !isNaN(lng)) {
        const destMap = L.map('destMap').setView([lat, lng], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}.png', {
            attribution: '&copy; OpenStreetMap contributors | Fort Explorer',
            maxZoom: 18
        }).addTo(destMap);

        const customPin = L.divIcon({
            className: 'fe-custom-pin',
            html: `<div style="background-color:#8b2616;width:34px;height:34px;border-radius:50% 50% 50% 0;transform:rotate(-45deg);display:flex;align-items:center;justify-content:center;border:2px solid #fff;box-shadow:0 4px 10px rgba(0,0,0,0.3);"><i class="fa-solid fa-shield-halved" style="transform:rotate(45deg);color:#fff;font-size:14px;"></i></div>`,
            iconSize: [34, 34],
            iconAnchor: [17, 34]
        });

        L.marker([lat, lng], { icon: customPin }).addTo(destMap)
            .bindPopup("<strong><?= addslashes(e($destName)) ?></strong><br><?= addslashes(e($destination['address'])) ?>").openPopup();
    }

    // 2. Multilingual Audio Guide Player Implementation
    const audioTexts = {
        mr: <?= json_encode($audioMr) ?>,
        en: <?= json_encode($audioEn) ?>,
        hi: <?= json_encode($audioHi) ?>
    };

    let audioLang = 'mr';
    let audioSpeed = 1.0;
    let synth = window.speechSynthesis;
    let audioUtterance = null;
    let isAudioPlaying = false;
    let isAudioPaused = false;
    let audioTimer = null;
    let audioElapsedSec = 0;
    let audioTotalEstSec = 120;

    const mainPlayBtn = document.getElementById('mainAudioPlayBtn');
    const mainPlayIcon = mainPlayBtn ? mainPlayBtn.querySelector('i') : null;
    const mainProgressBar = document.getElementById('mainAudioProgressBar');
    const mainCurTime = document.getElementById('mainAudioCurrentTime');
    const mainTotTime = document.getElementById('mainAudioTotalTime');
    const mainStatus = document.getElementById('mainAudioStatusText');
    const mainTranscript = document.getElementById('mainAudioTranscriptText');

    function fmt(s) {
        const m = Math.floor(s / 60);
        const sec = s % 60;
        return `${m < 10 ? '0' : ''}${m}:${sec < 10 ? '0' : ''}${sec}`;
    }

    function calcEstimate() {
        const words = (audioTexts[audioLang] || '').split(/\s+/).length;
        audioTotalEstSec = Math.max(30, Math.round((words / (130 * audioSpeed)) * 60));
        if (mainTotTime) mainTotTime.textContent = fmt(audioTotalEstSec);
    }

    function updateTranscript() {
        if (mainTranscript) {
            mainTranscript.innerHTML = (audioTexts[audioLang] || '').replace(/\n/g, '<br>');
        }
    }

    function stopMainAudio() {
        if (synth) synth.cancel();
        isAudioPlaying = false;
        isAudioPaused = false;
        clearInterval(audioTimer);
        audioElapsedSec = 0;
        if (mainPlayIcon) mainPlayIcon.className = 'fa-solid fa-play';
        if (mainProgressBar) mainProgressBar.style.width = '0%';
        if (mainCurTime) mainCurTime.textContent = '00:00';
        if (mainStatus) mainStatus.innerHTML = '<i class="fa-solid fa-volume-high me-1"></i>Ready to Play';
    }

    function playMainAudio() {
        if (!synth) {
            showToast('Audio playback is not supported by your browser.', 'error');
            return;
        }

        if (isAudioPaused) {
            synth.resume();
            isAudioPaused = false;
            isAudioPlaying = true;
            if (mainPlayIcon) mainPlayIcon.className = 'fa-solid fa-pause';
            if (mainStatus) mainStatus.innerHTML = '<i class="fa-solid fa-volume-high me-1 text-warning"></i>Playing narration...';
            return;
        }

        synth.cancel();
        const text = audioTexts[audioLang] || audioTexts.en;
        audioUtterance = new SpeechSynthesisUtterance(text);

        const langMap = { mr: 'mr-IN', hi: 'hi-IN', en: 'en-IN' };
        audioUtterance.lang = langMap[audioLang] || 'en-US';
        audioUtterance.rate = audioSpeed;

        const voices = synth.getVoices();
        const targetVoice = voices.find(v => v.lang.startsWith(audioUtterance.lang.slice(0, 2)));
        if (targetVoice) audioUtterance.voice = targetVoice;

        audioUtterance.onstart = () => {
            isAudioPlaying = true;
            isAudioPaused = false;
            if (mainPlayIcon) mainPlayIcon.className = 'fa-solid fa-pause';
            if (mainStatus) mainStatus.innerHTML = '<i class="fa-solid fa-volume-high me-1 text-warning"></i>Playing narration...';
            
            clearInterval(audioTimer);
            audioTimer = setInterval(() => {
                if (isAudioPlaying && !isAudioPaused) {
                    audioElapsedSec++;
                    if (mainCurTime) mainCurTime.textContent = fmt(audioElapsedSec);
                    const pct = Math.min(100, (audioElapsedSec / audioTotalEstSec) * 100);
                    if (mainProgressBar) mainProgressBar.style.width = pct + '%';
                }
            }, 1000);
        };

        audioUtterance.onend = () => {
            stopMainAudio();
            if (mainStatus) mainStatus.innerHTML = '<i class="fa-solid fa-circle-check me-1 text-success"></i>Audio completed';
        };

        audioUtterance.onerror = () => {
            stopMainAudio();
        };

        synth.speak(audioUtterance);
    }

    function pauseMainAudio() {
        if (synth && isAudioPlaying) {
            synth.pause();
            isAudioPaused = true;
            isAudioPlaying = false;
            if (mainPlayIcon) mainPlayIcon.className = 'fa-solid fa-play';
            if (mainStatus) mainStatus.innerHTML = '<i class="fa-solid fa-pause me-1"></i>Paused';
        }
    }

    if (mainPlayBtn) {
        mainPlayBtn.addEventListener('click', () => {
            if (isAudioPlaying) {
                pauseMainAudio();
            } else {
                playMainAudio();
            }
        });
    }

    document.querySelectorAll('.main-audio-lang-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            document.querySelectorAll('.main-audio-lang-btn').forEach(b => b.classList.remove('active'));
            e.currentTarget.classList.add('active');
            audioLang = e.currentTarget.dataset.lang;
            stopMainAudio();
            calcEstimate();
            updateTranscript();
        });
    });

    document.querySelectorAll('.main-audio-speed-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            document.querySelectorAll('.main-audio-speed-btn').forEach(b => b.classList.remove('active'));
            e.currentTarget.classList.add('active');
            audioSpeed = parseFloat(e.currentTarget.dataset.speed);
            calcEstimate();
            if (isAudioPlaying) {
                stopMainAudio();
                playMainAudio();
            }
        });
    });

    calcEstimate();
    updateTranscript();
});

// Wishlist toggle
async function toggleDetailWishlist(destId) {
    try {
        const res = await fetch('api/toggle-wishlist.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `destination_id=${encodeURIComponent(destId)}`
        });
        const data = await res.json();
        if (data.status === 'success') {
            const btn = document.querySelector('.fe-wishlist-toggle-btn-detail');
            const btnText = document.getElementById('wishlistBtnText');
            if (data.action === 'added') {
                btn.className = 'btn btn-sm btn-danger text-white py-2 px-3 fw-bold rounded-pill';
                btnText.textContent = 'In Wishlist';
                showToast(data.message, 'success');
            } else {
                btn.className = 'btn btn-sm btn-outline-light text-dark bg-white py-2 px-3 fw-bold rounded-pill';
                btnText.textContent = 'Save to Wishlist';
                showToast(data.message, 'info');
            }
        } else if (data.status === 'unauthorized') {
            window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.pathname + window.location.search);
        } else {
            showToast(data.message || 'Error occurred', 'error');
        }
    } catch (e) {
        showToast('Network error', 'error');
    }
}

// Visited form
async function submitVisitedForm() {
    const form = document.getElementById('visitedRecordForm');
    if (!form) return;
    const formData = new FormData(form);

    try {
        const res = await fetch('api/mark-visited.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.status === 'success') {
            showToast(data.message, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast(data.message || 'Failed to update visited record', 'error');
        }
    } catch (e) {
        showToast('Network error while saving visited record', 'error');
    }
}

// Helpful review vote
async function voteHelpfulReview(reviewId) {
    try {
        const res = await fetch('api/review-helpful.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=vote_helpful&review_id=${encodeURIComponent(reviewId)}`
        });
        const data = await res.json();
        if (data.status === 'success') {
            const cntSpan = document.getElementById(`helpful-cnt-${reviewId}`);
            if (cntSpan) cntSpan.textContent = data.count;
            showToast(data.message, 'success');
        } else if (data.status === 'unauthorized') {
            showToast('Please sign in to vote.', 'warning');
        } else {
            showToast(data.message || 'Error voting', 'error');
        }
    } catch (e) {
        showToast('Network error', 'error');
    }
}

// Report review modal
function openReportReviewModal(reviewId) {
    document.getElementById('reportReviewId').value = reviewId;
    const modal = new bootstrap.Modal(document.getElementById('reportReviewModal'));
    modal.show();
}

async function submitReviewReport() {
    const revId = document.getElementById('reportReviewId').value;
    const reason = document.getElementById('reportReviewReason').value;

    try {
        const res = await fetch('api/review-helpful.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=report_review&review_id=${encodeURIComponent(revId)}&reason=${encodeURIComponent(reason)}`
        });
        const data = await res.json();
        showToast(data.message, data.status === 'success' ? 'success' : 'info');
        bootstrap.Modal.getInstance(document.getElementById('reportReviewModal')).hide();
    } catch (e) {
        showToast('Network error submitting report', 'error');
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
