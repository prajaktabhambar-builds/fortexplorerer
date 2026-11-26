<?php
/**
 * FORT EXPLORER - Comprehensive Maharashtra Fort Directory
 * The digital archive of 365+ verified forts across all 36 districts of Maharashtra.
 * Covers Giridurg (Hill), Jaldurg (Sea), Island, Bhuikot (Land), Vanadurg (Forest), Coastal, and Gadhi/Kot.
 * Highlights the 11 UNESCO Maratha Military Landscapes components.
 */

require_once __DIR__ . '/includes/header.php';

$db = getDB();

// 1. Fetch Categories for Filter Tabs
$categories = [];
$catMap = [];
if ($db) {
    $catStmt = $db->query("SELECT * FROM fort_categories ORDER BY id ASC");
    $categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($categories as $c) {
        $catMap[$c['id']] = $c;
        $catMap[$c['code']] = $c;
    }
}

// 2. Fetch all 36 Districts with their Fort Counts
$districts = [];
if ($db) {
    $distStmt = $db->query("
        SELECT dist.id, dist.name_en, dist.name_mr, dist.division,
               COUNT(f.id) AS fort_count
        FROM districts dist
        LEFT JOIN forts f ON dist.id = f.district_id
        GROUP BY dist.id, dist.name_en, dist.name_mr, dist.division
        ORDER BY dist.division ASC, dist.name_en ASC
    ");
    $districts = $distStmt->fetchAll(PDO::FETCH_ASSOC);
}

// 3. Overall Directory Statistics
$totalFortsCount = 0;
$totalDistrictsCount = 36;
$unescoCount = 11;
if ($db) {
    $totalFortsCount = (int)$db->query("SELECT COUNT(*) FROM forts")->fetchColumn();
}

// 4. Request Filters & Parameters
$page = max(1, filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1);
$perPage = 18;
$offset = ($page - 1) * $perPage;

$selectedDistrictId = filter_input(INPUT_GET, 'district', FILTER_VALIDATE_INT);
$selectedCategoryCode = sanitize_input($_GET['category'] ?? '');
$isUnescoFilter = isset($_GET['unesco']) && $_GET['unesco'] === '1';
$isShivajiFilter = isset($_GET['shivaji']) && $_GET['shivaji'] === '1';
$searchQuery = sanitize_input($_GET['q'] ?? '');
$selectedDifficulty = sanitize_input($_GET['difficulty'] ?? '');
$sortBy = sanitize_input($_GET['sort'] ?? 'significance');

// 5. Build Dynamic SQL Query
$where = ["1=1"];
$params = [];

if ($selectedDistrictId) {
    $where[] = "f.district_id = ?";
    $params[] = $selectedDistrictId;
}

if ($selectedCategoryCode && isset($catMap[$selectedCategoryCode])) {
    $where[] = "f.category_id = ?";
    $params[] = $catMap[$selectedCategoryCode]['id'];
}

if ($isUnescoFilter) {
    $where[] = "f.is_unesco_site = 1";
}

if ($isShivajiFilter) {
    $where[] = "f.is_shivaji_fort = 1";
}

if (!empty($searchQuery)) {
    $where[] = "(f.name LIKE ? OR f.name_mr LIKE ? OR f.alternate_names LIKE ? OR f.taluka LIKE ? OR f.village LIKE ?)";
    $likeQ = '%' . $searchQuery . '%';
    $params[] = $likeQ;
    $params[] = $likeQ;
    $params[] = $likeQ;
    $params[] = $likeQ;
    $params[] = $likeQ;
}

if (!empty($selectedDifficulty) && in_array($selectedDifficulty, ['Easy', 'Moderate', 'Challenging', 'Difficult'], true)) {
    $where[] = "f.trekking_difficulty = ?";
    $params[] = $selectedDifficulty;
}

$whereSql = implode(" AND ", $where);

// Sorting logic
$orderSql = "f.is_unesco_site DESC, f.is_shivaji_fort DESC, f.altitude_meters DESC";
if ($sortBy === 'name_asc') {
    $orderSql = "f.name ASC";
} elseif ($sortBy === 'altitude_desc') {
    $orderSql = "f.altitude_meters DESC";
} elseif ($sortBy === 'altitude_asc') {
    $orderSql = "f.altitude_meters ASC";
} elseif ($sortBy === 'district') {
    $orderSql = "dist.name_en ASC, f.name ASC";
}

// Count matching records
$totalMatching = 0;
$forts = [];
$userWishlistIds = [];

if ($db) {
    $countStmt = $db->prepare("
        SELECT COUNT(*)
        FROM forts f
        JOIN districts dist ON f.district_id = dist.id
        WHERE $whereSql
    ");
    $countStmt->execute($params);
    $totalMatching = (int)$countStmt->fetchColumn();

    // Fetch Paginated Forts
    $fortStmt = $db->prepare("
        SELECT f.*, 
               dist.name_en AS district_name, dist.name_mr AS district_name_mr,
               fc.name_en AS category_name, fc.name_mr AS category_name_mr,
               fc.traditional_name, fc.traditional_name_mr,
               fc.badge_icon, fc.badge_color,
               COALESCE(AVG(r.rating), 4.9) AS avg_rating,
               COUNT(r.id) AS review_count
        FROM forts f
        JOIN districts dist ON f.district_id = dist.id
        JOIN fort_categories fc ON f.category_id = fc.id
        LEFT JOIN destinations d ON f.destination_id = d.id
        LEFT JOIN reviews r ON d.id = r.destination_id AND r.status = 'approved'
        WHERE $whereSql
        GROUP BY f.id
        ORDER BY $orderSql
        LIMIT $perPage OFFSET $offset
    ");
    $fortStmt->execute($params);
    $forts = $fortStmt->fetchAll(PDO::FETCH_ASSOC);

    // Wishlist for logged in user
    if (is_logged_in()) {
        $wStmt = $db->prepare("SELECT destination_id FROM wishlists WHERE user_id = ?");
        $wStmt->execute([$_SESSION['user_id']]);
        $userWishlistIds = $wStmt->fetchAll(PDO::FETCH_COLUMN);
    }
}

$totalPages = max(1, ceil($totalMatching / $perPage));

// Selected district name if filtered
$currentDistrictName = '';
if ($selectedDistrictId) {
    foreach ($districts as $d) {
        if ($d['id'] === $selectedDistrictId) {
            $currentDistrictName = t_val($d['name_en'], $d['name_mr']);
            break;
        }
    }
}
?>

<!-- Hero Banner & Stats -->
<section class="py-5" style="background: linear-gradient(135deg, #0e2415 0%, #163820 60%, #1e4b2c 100%); color: #fff; border-bottom: 3px solid var(--fe-gold);">
    <div class="container py-3">
        <div class="text-center max-w-900 mx-auto">
            <span class="badge px-3 py-2 rounded-pill mb-3" style="background: rgba(197, 155, 39, 0.25); border: 1px solid #c59b27; color: #f5d77f; letter-spacing: 1.5px;">
                <i class="fa-solid fa-crown me-2"></i><?= is_marathi() ? 'महाराष्ट्र दुर्ग संचिका व ऐतिहासिक वारसा' : 'Official Maharashtra Fort Directory' ?>
            </span>
            <h1 class="display-4 fw-bold font-heritage mb-3">
                <?= is_marathi() ? 'महाराष्ट्रातील किल्ले व संरक्षित दुर्ग' : 'The Forts of Maharashtra' ?>
            </h1>
            <p class="lead text-white-50 mb-4 fs-6">
                <?= is_marathi()
                    ? 'सह्याद्रीच्या उत्तुंग गिरिदुर्गांपासून अरबी समुद्रातील अभेद्य जलदुर्गांपर्यंत — सर्व ३६ जिल्ह्यांतील ऐतिहासिकदृष्ट्या दस्तऐवजीकरण झालेली संपूर्ण दुर्ग संचिका.'
                    : 'From the towering mountain citadels of the Sahyadris to the invincible marine fortresses of the Arabian Sea — an authentic directory covering all 36 districts of Maharashtra.' ?>
            </p>

            <!-- Statistics Badges -->
            <div class="row g-3 justify-content-center pt-2">
                <div class="col-6 col-md-3">
                    <div class="p-3 rounded-4" style="background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15);">
                        <div class="display-6 fw-bold text-warning font-heritage"><?= $totalFortsCount ?>+</div>
                        <div class="small text-white-50"><?= is_marathi() ? 'दस्तऐवजीकृत किल्ले' : 'Verified Forts' ?></div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="p-3 rounded-4" style="background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15);">
                        <div class="display-6 fw-bold text-warning font-heritage"><?= $totalDistrictsCount ?></div>
                        <div class="small text-white-50"><?= is_marathi() ? 'महाराष्ट्र जिल्हे' : 'Districts Covered' ?></div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="p-3 rounded-4" style="background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15);">
                        <div class="display-6 fw-bold text-warning font-heritage">11</div>
                        <div class="small text-white-50"><?= is_marathi() ? 'युनेस्को जागतिक वारसा स्थळे' : 'UNESCO Sites' ?></div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="p-3 rounded-4" style="background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15);">
                        <div class="display-6 fw-bold text-warning font-heritage">7</div>
                        <div class="small text-white-50"><?= is_marathi() ? 'पारंपरिक दुर्ग प्रकार' : 'Fort Categories' ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Main Directory Interface -->
<div class="container py-4">

    <!-- Search & Filter Controls -->
    <div class="card border-0 shadow-sm rounded-4 mb-4" style="background: #ffffff; border: 1px solid #e5e7eb !important;">
        <div class="card-body p-4">
            <form action="forts.php" method="GET" id="fortFilterForm">
                <!-- Search bar and key filters -->
                <div class="row g-3 align-items-center mb-3">
                    <div class="col-lg-5">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 text-muted">
                                <i class="fa-solid fa-magnifying-glass"></i>
                            </span>
                            <input type="text" name="q" class="form-control border-start-0 ps-0" 
                                   placeholder="<?= is_marathi() ? 'किल्ला, तालुका किंवा गावाचे नाव शोधा...' : 'Search fort name, taluka, or village...' ?>"
                                   value="<?= e($searchQuery) ?>">
                            <?php if (!empty($searchQuery)): ?>
                                <a href="forts.php" class="input-group-text bg-white text-muted text-decoration-none">
                                    <i class="fa-solid fa-xmark"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- District Dropdown -->
                    <div class="col-md-3 col-lg-3">
                        <select name="district" class="form-select" onchange="this.form.submit()">
                            <option value=""><?= is_marathi() ? 'सर्व ३६ जिल्हे (महाराष्ट्र)' : 'All 36 Districts (Maharashtra)' ?></option>
                            <?php
                            $currDiv = '';
                            foreach ($districts as $d):
                                if ($d['division'] !== $currDiv):
                                    if ($currDiv !== '') echo '</optgroup>';
                                    $currDiv = $d['division'];
                                    echo '<optgroup label="' . e($currDiv) . '">';
                                endif;
                            ?>
                                <option value="<?= $d['id'] ?>" <?= $selectedDistrictId === (int)$d['id'] ? 'selected' : '' ?>>
                                    <?= e(t_val($d['name_en'], $d['name_mr'])) ?> (<?= $d['fort_count'] ?>)
                                </option>
                            <?php endforeach; if ($currDiv !== '') echo '</optgroup>'; ?>
                        </select>
                    </div>

                    <!-- Difficulty Dropdown -->
                    <div class="col-md-2 col-lg-2">
                        <select name="difficulty" class="form-select" onchange="this.form.submit()">
                            <option value=""><?= is_marathi() ? 'सर्व काठिण्य पातळी' : 'All Difficulties' ?></option>
                            <option value="Easy" <?= $selectedDifficulty === 'Easy' ? 'selected' : '' ?>><?= is_marathi() ? 'सोपी (Easy)' : 'Easy' ?></option>
                            <option value="Moderate" <?= $selectedDifficulty === 'Moderate' ? 'selected' : '' ?>><?= is_marathi() ? 'मध्यम (Moderate)' : 'Moderate' ?></option>
                            <option value="Challenging" <?= $selectedDifficulty === 'Challenging' ? 'selected' : '' ?>><?= is_marathi() ? 'आव्हानधारक (Challenging)' : 'Challenging' ?></option>
                            <option value="Difficult" <?= $selectedDifficulty === 'Difficult' ? 'selected' : '' ?>><?= is_marathi() ? 'कठीण (Difficult)' : 'Difficult' ?></option>
                        </select>
                    </div>

                    <!-- Sort Dropdown -->
                    <div class="col-md-2 col-lg-2">
                        <select name="sort" class="form-select" onchange="this.form.submit()">
                            <option value="significance" <?= $sortBy === 'significance' ? 'selected' : '' ?>><?= is_marathi() ? 'महत्त्व व वैशिष्ट्य' : 'Significance' ?></option>
                            <option value="altitude_desc" <?= $sortBy === 'altitude_desc' ? 'selected' : '' ?>><?= is_marathi() ? 'उंची (जास्त ते कमी)' : 'Altitude (High to Low)' ?></option>
                            <option value="altitude_asc" <?= $sortBy === 'altitude_asc' ? 'selected' : '' ?>><?= is_marathi() ? 'उंची (कमी ते जास्त)' : 'Altitude (Low to High)' ?></option>
                            <option value="name_asc" <?= $sortBy === 'name_asc' ? 'selected' : '' ?>><?= is_marathi() ? 'नाव (A - Z)' : 'Name (A to Z)' ?></option>
                            <option value="district" <?= $sortBy === 'district' ? 'selected' : '' ?>><?= is_marathi() ? 'जिल्हा' : 'District' ?></option>
                        </select>
                    </div>
                </div>

                <!-- Category Pills Bar -->
                <div class="d-flex flex-wrap gap-2 pt-2 border-top align-items-center">
                    <span class="small fw-bold text-muted me-2"><i class="fa-solid fa-filter me-1"></i><?= is_marathi() ? 'प्रकार:' : 'Type:' ?></span>

                    <!-- All Forts -->
                    <a href="forts.php<?= $selectedDistrictId ? '?district=' . $selectedDistrictId : '' ?>" 
                       class="btn btn-sm rounded-pill px-3 <?= (!$selectedCategoryCode && !$isUnescoFilter && !$isShivajiFilter) ? 'btn-fe-primary' : 'btn-outline-secondary' ?>">
                        <i class="fa-solid fa-border-all me-1"></i><?= is_marathi() ? 'सर्व किल्ले' : 'All Forts' ?>
                    </a>

                    <!-- UNESCO Filter Tab -->
                    <a href="forts.php?unesco=1<?= $selectedDistrictId ? '&district=' . $selectedDistrictId : '' ?>" 
                       class="btn btn-sm rounded-pill px-3 <?= $isUnescoFilter ? 'btn-warning text-dark fw-bold' : 'btn-outline-warning text-dark' ?>" 
                       style="border-color: #c59b27;">
                        <i class="fa-solid fa-crown me-1 text-warning"></i><?= is_marathi() ? 'युनेस्को जागतिक वारसा (11)' : 'UNESCO Maratha Military Landscapes (11)' ?>
                    </a>

                    <!-- Shivaji Forts Tab -->
                    <a href="forts.php?shivaji=1<?= $selectedDistrictId ? '&district=' . $selectedDistrictId : '' ?>" 
                       class="btn btn-sm rounded-pill px-3 <?= $isShivajiFilter ? 'btn-danger' : 'btn-outline-danger' ?>">
                        <i class="fa-solid fa-shield-halved me-1"></i><?= is_marathi() ? 'शिवकालीन किल्ले' : 'Shivaji Maharaj Forts' ?>
                    </a>

                    <!-- 7 Authentic Fort Categories -->
                    <?php foreach ($categories as $cat): ?>
                        <a href="forts.php?category=<?= urlencode($cat['code']) ?><?= $selectedDistrictId ? '&district=' . $selectedDistrictId : '' ?>" 
                           class="btn btn-sm rounded-pill px-3 <?= $selectedCategoryCode === $cat['code'] ? 'btn-fe-primary' : 'btn-outline-secondary' ?>">
                            <i class="fa-solid <?= e($cat['badge_icon']) ?> me-1"></i><?= e(t_val($cat['traditional_name'] . ' (' . $cat['name_en'] . ')', $cat['traditional_name_mr'])) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Active Filter Indicators & Results Count -->
    <div class="d-flex flex-wrap align-items-center justify-content-between mb-3 text-muted small">
        <div>
            <?= is_marathi() ? 'एकूण आढळलेले किल्ले:' : 'Showing verified forts:' ?> 
            <strong class="text-dark fs-6"><?= $totalMatching ?></strong>
            <?php if ($currentDistrictName): ?>
                <span class="badge bg-success ms-2 px-2.5 py-1.5"><?= is_marathi() ? 'जिल्हा:' : 'District:' ?> <?= e($currentDistrictName) ?></span>
            <?php endif; ?>
            <?php if ($isUnescoFilter): ?>
                <span class="badge bg-warning text-dark ms-2 px-2.5 py-1.5"><i class="fa-solid fa-crown me-1"></i>UNESCO Maratha Military Landscapes</span>
            <?php endif; ?>
            <?php if ($selectedCategoryCode && isset($catMap[$selectedCategoryCode])): ?>
                <span class="badge bg-primary ms-2 px-2.5 py-1.5"><?= e(t_val($catMap[$selectedCategoryCode]['traditional_name'], $catMap[$selectedCategoryCode]['traditional_name_mr'])) ?></span>
            <?php endif; ?>
        </div>

        <div>
            <a href="map.php" class="btn btn-sm btn-outline-success rounded-pill px-3 me-2">
                <i class="fa-solid fa-map-location-dot me-1"></i><?= is_marathi() ? 'नकाशावर पहा' : 'View on Interactive Map' ?>
            </a>
            <?php if ($selectedDistrictId || $selectedCategoryCode || $isUnescoFilter || $isShivajiFilter || $searchQuery || $selectedDifficulty): ?>
                <a href="forts.php" class="text-danger text-decoration-none">
                    <i class="fa-solid fa-arrow-rotate-left me-1"></i><?= is_marathi() ? 'फिल्टर रीसेट करा' : 'Reset Filters' ?>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Forts Grid -->
    <?php if (!empty($forts)): ?>
        <div class="row g-4 mb-4">
            <?php foreach ($forts as $fort): ?>
                <?php
                $destName = t_val($fort['name'], $fort['name_mr']);
                $destDesc = t_val($fort['historical_background'], $fort['historical_background_mr']);
                $districtName = t_val($fort['district_name'], $fort['district_name_mr']);
                $tradCategoryName = t_val($fort['traditional_name'], $fort['traditional_name_mr']);
                $isWishlisted = in_array((int)($fort['destination_id'] ?? 0), $userWishlistIds, true);
                ?>
                <div class="col-lg-4 col-md-6">
                    <div class="fe-card h-100 d-flex flex-column">
                        <div class="fe-card-img-wrap position-relative">
                            <img src="<?= e($fort['primary_image']) ?>" alt="<?= e($destName) ?>" class="fe-card-img" loading="lazy">

                            <!-- Category Badge -->
                            <span class="fe-card-category-badge" style="border-left-color: <?= e($fort['badge_color'] ?: '#163820') ?>;">
                                <i class="fa-solid <?= e($fort['badge_icon'] ?: 'fa-shield-halved') ?> me-1"></i><?= e($tradCategoryName) ?>
                            </span>

                            <!-- District Badge -->
                            <span class="fe-card-district-badge">
                                <i class="fa-solid fa-location-dot me-1 text-danger"></i><?= e($districtName) ?><?= !empty($fort['taluka']) ? ', ' . e($fort['taluka']) : '' ?>
                            </span>

                            <!-- UNESCO Maratha Military Landscapes Floating Crown -->
                            <?php if ($fort['is_unesco_site']): ?>
                                <span class="badge position-absolute top-0 end-0 m-3 px-2.5 py-1.5 shadow" 
                                      style="background: #c59b27; color: #163820; font-weight: 700; z-index: 2; border: 1px solid #fff;">
                                    <i class="fa-solid fa-crown me-1 text-dark"></i>UNESCO
                                </span>
                            <?php endif; ?>

                            <!-- Wishlist Toggle -->
                            <?php if (!empty($fort['destination_id'])): ?>
                                <button type="button" class="fe-wishlist-toggle-btn <?= $isWishlisted ? 'active' : '' ?>" 
                                        data-dest-id="<?= $fort['destination_id'] ?>" title="Save to Wishlist"
                                        style="<?= $fort['is_unesco_site'] ? 'top: 48px;' : '' ?>">
                                    <i class="<?= $isWishlisted ? 'fa-solid fa-heart text-danger' : 'fa-regular fa-heart' ?>"></i>
                                </button>
                            <?php endif; ?>
                        </div>

                        <div class="fe-card-body d-flex flex-column flex-grow-1 p-3.5">
                            <!-- Difficulty & Altitude -->
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <?= get_difficulty_badge($fort['trekking_difficulty'] ?? 'Moderate') ?>
                                <span class="small text-muted">
                                    <i class="fa-solid fa-mountain me-1 text-secondary"></i><?= $fort['altitude_meters'] ? $fort['altitude_meters'] . ' m' : 'Plain / Sea level' ?>
                                </span>
                            </div>

                            <h5 class="fe-card-title mb-1"><?= e($destName) ?></h5>
                            <?php if (!empty($fort['alternate_names'])): ?>
                                <div class="small text-muted mb-2 fst-italic" style="font-size:0.8rem;"><?= e($fort['alternate_names']) ?></div>
                            <?php endif; ?>

                            <!-- Star Rating -->
                            <div class="mb-2">
                                <?= render_rating_stars((float)$fort['avg_rating']) ?>
                                <span class="text-muted small ms-1">(<?= $fort['review_count'] ?>)</span>
                            </div>

                            <!-- Historical snippet -->
                            <p class="fe-card-desc flex-grow-1" style="display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;">
                                <?= e($destDesc) ?>
                            </p>

                            <!-- Shivaji Maharaj Note Callout -->
                            <?php if ($fort['is_shivaji_fort'] && !empty($fort['shivaji_connection'])): ?>
                                <div class="p-2 mb-3 rounded-2 bg-light border-start border-3 border-warning small text-secondary">
                                    <strong class="text-dark d-block mb-1" style="font-size: 0.78rem;">
                                        <i class="fa-solid fa-crown text-warning me-1"></i><?= is_marathi() ? 'शिवकालीन संदर्भ:' : 'Swarajya Connection:' ?>
                                    </strong>
                                    <span style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;font-size:0.78rem;">
                                        <?= e(t_val($fort['shivaji_connection'], $fort['shivaji_connection_mr'])) ?>
                                    </span>
                                </div>
                            <?php endif; ?>

                            <!-- Card Footer -->
                            <div class="fe-card-footer pt-2 border-top mt-auto d-flex justify-content-between align-items-center">
                                <span class="small text-muted">
                                    <i class="fa-solid fa-person-walking me-1"></i><?= $fort['trek_duration_hours'] ?: '1.5' ?> hrs
                                </span>
                                <a href="destination.php?slug=<?= urlencode($fort['slug']) ?>" class="btn btn-sm btn-fe-primary">
                                    <?= __('btn_view_details') ?> <i class="fa-solid fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <nav aria-label="Forts directory navigation" class="mt-4 mb-5">
                <ul class="pagination justify-content-center">
                    <?php
                    // Helper to build URL with existing query params
                    $buildPageUrl = function($p) {
                        $params = $_GET;
                        $params['page'] = $p;
                        return 'forts.php?' . http_build_query($params);
                    };
                    ?>
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= e($buildPageUrl($page - 1)) ?>">
                            <i class="fa-solid fa-chevron-left me-1"></i><?= is_marathi() ? 'मागे' : 'Previous' ?>
                        </a>
                    </li>

                    <?php
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);

                    if ($startPage > 1): ?>
                        <li class="page-item"><a class="page-link" href="<?= e($buildPageUrl(1)) ?>">1</a></li>
                        <?php if ($startPage > 2): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
                    <?php endif; ?>

                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="<?= e($buildPageUrl($i)) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($endPage < $totalPages): ?>
                        <?php if ($endPage < $totalPages - 1): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
                        <li class="page-item"><a class="page-link" href="<?= e($buildPageUrl($totalPages)) ?>"><?= $totalPages ?></a></li>
                    <?php endif; ?>

                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= e($buildPageUrl($page + 1)) ?>">
                            <?= is_marathi() ? 'पुढे' : 'Next' ?><i class="fa-solid fa-chevron-right ms-1"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>

    <?php else: ?>
        <!-- EMPTY STATE / DISTRICT FALLBACK -->
        <div class="card border-0 shadow-sm rounded-4 p-5 text-center my-4">
            <div class="my-4">
                <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                    <i class="fa-solid fa-compass-drafting text-warning fs-1"></i>
                </div>
                <h4 class="font-heritage text-dark mb-2">
                    <?= is_marathi() ? 'माहिती संकलन व ऐतिहासिक संशोधन सुरू आहे' : 'Information being collected' ?>
                </h4>
                <p class="text-muted max-w-600 mx-auto mb-4">
                    <?= is_marathi()
                        ? 'या जिल्ह्यासाठी किंवा निवडलेल्या फिल्टरसाठी ऐतिहासिक पुरावे व गॅझेटिअर तपासणीचे काम सुरू आहे. बनावट किंवा असत्यापित नोंदी टाळण्यासाठी आम्ही केवळ ऐतिहासिकदृष्ट्या प्रमाणित माहिती प्रदर्शित करतो.'
                        : 'Historical research, archaeological citations, and gazetteer verification are in progress for this selection. To prevent fabricated facts, Fort Explorer publishes only thoroughly verified records.' ?>
                </p>
                <div class="d-flex justify-content-center gap-3">
                    <a href="forts.php" class="btn btn-fe-primary">
                        <i class="fa-solid fa-arrow-rotate-left me-1"></i><?= is_marathi() ? 'सर्व किल्ले पहा' : 'View All 350+ Forts' ?>
                    </a>
                    <a href="explore.php" class="btn btn-outline-secondary">
                        <i class="fa-solid fa-mountain-sun me-1"></i><?= is_marathi() ? 'इतर पर्यटन स्थळे' : 'Explore All Destinations' ?>
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>

</div>

<!-- UNESCO Maratha Military Landscapes Educational Section -->
<section class="py-5 bg-white border-top">
    <div class="container">
        <div class="row align-items-center g-4">
            <div class="col-lg-5">
                <span class="badge bg-warning text-dark px-3 py-2 rounded-pill mb-3">
                    <i class="fa-solid fa-award me-1"></i>UNESCO World Heritage Tentative Nomination
                </span>
                <h2 class="font-heritage fw-bold text-success mb-3">
                    <?= is_marathi() ? 'मराठा सैनिकी भूदृश्ये (Maratha Military Landscapes)' : 'Maratha Military Landscapes of India' ?>
                </h2>
                <p class="text-secondary mb-3">
                    <?= is_marathi()
                        ? 'छत्रपती शिवाजी महाराजांच्या दूरदृष्टीने सह्याद्रीच्या नैसर्गिक डोंगरमाथ्यांवर आणि अरबी समुद्रातील खडकांवर उभारलेली ही ११ दुर्ग-रत्ने जागतिक वारसा यादीत नामांकित आहेत. प्रत्येक किल्ला मध्ययुगीन भारतीय लष्करी स्थापत्यकलेचा कळस मानला जातो.'
                        : 'Erected between the 17th and 18th centuries under the vision of Chhatrapati Shivaji Maharaj, these 11 extraordinary components across Maharashtra represent the pinnacle of mountain warfare, guerrilla defense, and naval power.' ?>
                </p>
                <div class="d-flex flex-wrap gap-2">
                    <span class="badge bg-light text-dark border p-2"><i class="fa-solid fa-check text-success me-1"></i>Salher</span>
                    <span class="badge bg-light text-dark border p-2"><i class="fa-solid fa-check text-success me-1"></i>Shivneri</span>
                    <span class="badge bg-light text-dark border p-2"><i class="fa-solid fa-check text-success me-1"></i>Lohagad</span>
                    <span class="badge bg-light text-dark border p-2"><i class="fa-solid fa-check text-success me-1"></i>Khanderi</span>
                    <span class="badge bg-light text-dark border p-2"><i class="fa-solid fa-check text-success me-1"></i>Raigad</span>
                    <span class="badge bg-light text-dark border p-2"><i class="fa-solid fa-check text-success me-1"></i>Rajgad</span>
                    <span class="badge bg-light text-dark border p-2"><i class="fa-solid fa-check text-success me-1"></i>Pratapgad</span>
                    <span class="badge bg-light text-dark border p-2"><i class="fa-solid fa-check text-success me-1"></i>Suvarnadurg</span>
                    <span class="badge bg-light text-dark border p-2"><i class="fa-solid fa-check text-success me-1"></i>Panhala</span>
                    <span class="badge bg-light text-dark border p-2"><i class="fa-solid fa-check text-success me-1"></i>Vijaydurg</span>
                    <span class="badge bg-light text-dark border p-2"><i class="fa-solid fa-check text-success me-1"></i>Sindhudurg</span>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="p-3 rounded-4 bg-light border text-center">
                            <i class="fa-solid fa-mountain text-success fs-2 mb-2"></i>
                            <h6 class="fw-bold mb-1">Giridurg (Hill Forts)</h6>
                            <p class="small text-muted mb-0">Salher, Shivneri, Lohagad, Raigad, Rajgad, Pratapgad, Panhala</p>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 rounded-4 bg-light border text-center">
                            <i class="fa-solid fa-water text-primary fs-2 mb-2"></i>
                            <h6 class="fw-bold mb-1">Jaldurg & Island Forts</h6>
                            <p class="small text-muted mb-0">Khanderi, Suvarnadurg, Vijaydurg, Sindhudurg</p>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="p-3 rounded-4" style="background: rgba(197, 155, 39, 0.12); border: 1px dashed #c59b27;">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <h6 class="fw-bold text-dark mb-1"><i class="fa-solid fa-scroll text-warning me-2"></i>Explore all 11 UNESCO Sites Together</h6>
                                    <span class="small text-muted">Filter the directory to view photographs, historical battles, and trekking routes of these 11 monuments.</span>
                                </div>
                                <a href="forts.php?unesco=1" class="btn btn-sm btn-fe-gold px-3 text-nowrap">
                                    View 11 Sites <i class="fa-solid fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
