<?php
/**
 * FORT EXPLORER - Explore Destinations Directory
 * Searchable and filterable directory with district, category, difficulty, and sort filters.
 */

require_once __DIR__ . '/includes/header.php';

$db = getDB();

// Input parameters
$search = trim($_GET['q'] ?? '');
$categorySlug = trim($_GET['cat'] ?? '');
$districtId = filter_input(INPUT_GET, 'district', FILTER_VALIDATE_INT);
$difficulty = trim($_GET['difficulty'] ?? '');
$sort = trim($_GET['sort'] ?? 'popular');

$categories = [];
$districts = [];
$destinations = [];
$userWishlistIds = [];

if ($db) {
    try {
        // Fetch all categories for filter
        $catStmt = $db->query("SELECT * FROM destination_categories ORDER BY id ASC");
        $categories = $catStmt->fetchAll();

        // Fetch all districts for filter
        $distStmt = $db->query("SELECT * FROM districts ORDER BY name_en ASC");
        $districts = $distStmt->fetchAll();

        // Build filtered destinations query
        $query = "
            SELECT d.*, 
                   c.slug AS category_slug, c.name_en AS category_name, c.name_mr AS category_name_mr,
                   dist.name_en AS district_name, dist.name_mr AS district_name_mr,
                   COALESCE(AVG(r.rating), 4.8) AS avg_rating,
                   COUNT(r.id) AS review_count
            FROM destinations d
            JOIN destination_categories c ON d.category_id = c.id
            JOIN districts dist ON d.district_id = dist.id
            LEFT JOIN reviews r ON d.id = r.destination_id AND r.status = 'approved'
            WHERE 1=1
        ";
        $params = [];

        // Search filter
        if ($search !== '') {
            $query .= " AND (d.name LIKE ? OR d.name_mr LIKE ? OR d.short_description LIKE ? OR dist.name_en LIKE ? OR dist.name_mr LIKE ?)";
            $searchTerm = "%{$search}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        // Category filter
        if ($categorySlug !== '' && $categorySlug !== 'all') {
            $query .= " AND c.slug = ?";
            $params[] = $categorySlug;
        }

        // District filter
        if ($districtId) {
            $query .= " AND d.district_id = ?";
            $params[] = $districtId;
        }

        // Difficulty filter
        if ($difficulty !== '' && in_array(ucfirst($difficulty), ['Easy', 'Moderate', 'Challenging', 'Difficult'], true)) {
            $query .= " AND d.difficulty_level = ?";
            $params[] = ucfirst($difficulty);
        }

        $query .= " GROUP BY d.id ";

        // Sorting
        $query .= match ($sort) {
            'rating' => " ORDER BY avg_rating DESC, d.is_featured DESC ",
            'name' => " ORDER BY d.name ASC ",
            'duration' => " ORDER BY d.trek_duration_hours ASC ",
            default => " ORDER BY d.is_featured DESC, avg_rating DESC, d.id ASC "
        };

        $destStmt = $db->prepare($query);
        $destStmt->execute($params);
        $destinations = $destStmt->fetchAll();

        // Wishlist IDs for logged-in user
        if (is_logged_in()) {
            $wStmt = $db->prepare("SELECT destination_id FROM wishlists WHERE user_id = ?");
            $wStmt->execute([$_SESSION['user_id']]);
            $userWishlistIds = $wStmt->fetchAll(PDO::FETCH_COLUMN);
        }

    } catch (Exception $e) {
        $errorMessage = $e->getMessage();
    }
}
?>

<!-- Header Banner -->
<div class="py-5" style="background: linear-gradient(135deg, var(--fe-forest-dark) 0%, var(--fe-forest) 100%); color: #fff;">
    <div class="container py-3 text-center">
        <h1 class="display-5 fw-bold font-heritage mb-2"><?= __('nav_explore') ?></h1>
        <p class="lead fs-6 text-white-50 max-w-600 mx-auto mb-0">
            <?= is_marathi()
                ? 'सह्याद्रीचे दुर्ग, प्राचीन मंदिरे, खळाळणारे धबधबे आणि निसर्गरम्य पर्यटन स्थळांची संपूर्ण सूची.'
                : 'Filter, discover, and plan visits across the glorious fortresses and scenic wonders of Maharashtra.' ?>
        </p>
    </div>
</div>

<!-- Main Filter & Directory Section -->
<div class="container py-5">
    <div class="row g-4">
        <!-- Sidebar Filters -->
        <div class="col-lg-3">
            <div class="bg-white p-4 rounded-4 shadow-sm border position-sticky" style="top: 90px;">
                <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                    <h5 class="font-heritage mb-0 text-success"><i class="fa-solid fa-filter me-2 text-warning"></i><?= __('btn_filter') ?></h5>
                    <a href="explore.php" class="small text-danger text-decoration-none fw-semibold">
                        <i class="fa-solid fa-rotate-left me-1"></i><?= __('btn_clear') ?>
                    </a>
                </div>

                <form method="GET" action="explore.php">
                    <!-- Search Input -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted"><?= __('btn_search') ?></label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                            <input type="text" name="q" class="form-control" placeholder="<?= is_marathi() ? 'नाव किंवा जिल्हा...' : 'Name or district...' ?>" value="<?= e($search) ?>">
                        </div>
                    </div>

                    <!-- Category Filter -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted"><?= is_marathi() ? 'वर्गवारी' : 'Category' ?></label>
                        <select name="cat" class="form-select form-select-sm">
                            <option value=""><?= __('filter_category') ?></option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= e($cat['slug']) ?>" <?= $categorySlug === $cat['slug'] ? 'selected' : '' ?>>
                                    <?= e(t_val($cat['name_en'], $cat['name_mr'])) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- District Filter -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted"><?= is_marathi() ? 'जिल्हा' : 'District' ?></label>
                        <select name="district" class="form-select form-select-sm">
                            <option value=""><?= __('filter_district') ?></option>
                            <?php foreach ($districts as $dist): ?>
                                <option value="<?= $dist['id'] ?>" <?= $districtId === $dist['id'] ? 'selected' : '' ?>>
                                    <?= e(t_val($dist['name_en'], $dist['name_mr'])) ?> (<?= e($dist['division']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Difficulty Filter -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted"><?= is_marathi() ? 'काठिण्य पातळी' : 'Difficulty' ?></label>
                        <select name="difficulty" class="form-select form-select-sm">
                            <option value=""><?= __('filter_difficulty') ?></option>
                            <option value="easy" <?= strtolower($difficulty) === 'easy' ? 'selected' : '' ?>>Easy</option>
                            <option value="moderate" <?= strtolower($difficulty) === 'moderate' ? 'selected' : '' ?>>Moderate</option>
                            <option value="challenging" <?= strtolower($difficulty) === 'challenging' ? 'selected' : '' ?>>Challenging</option>
                            <option value="difficult" <?= strtolower($difficulty) === 'difficult' ? 'selected' : '' ?>>Difficult</option>
                        </select>
                    </div>

                    <!-- Sort Order -->
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted"><?= __('filter_sort') ?></label>
                        <select name="sort" class="form-select form-select-sm">
                            <option value="popular" <?= $sort === 'popular' ? 'selected' : '' ?>><?= __('sort_popular') ?></option>
                            <option value="rating" <?= $sort === 'rating' ? 'selected' : '' ?>><?= __('sort_rating') ?></option>
                            <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>><?= __('sort_name_asc') ?></option>
                            <option value="duration" <?= $sort === 'duration' ? 'selected' : '' ?>>Trek Duration (Shortest)</option>
                        </select>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-fe-primary btn-sm py-2">
                            <i class="fa-solid fa-filter me-1"></i><?= is_marathi() ? 'फिल्टर लागू करा' : 'Apply Filters' ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Destination Cards Grid -->
        <div class="col-lg-9">
            <!-- Results Bar -->
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 bg-white p-3 rounded-3 border">
                <div>
                    <span class="fw-bold text-dark fs-6"><?= count($destinations) ?></span>
                    <span class="text-muted small"><?= is_marathi() ? 'पर्यटन स्थळे उपलब्ध' : 'destinations found' ?></span>
                    <?php if ($search !== ''): ?>
                        <span class="badge bg-secondary-subtle text-secondary ms-2">Keyword: "<?= e($search) ?>"</span>
                    <?php endif; ?>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <a href="map.php" class="btn btn-sm btn-outline-dark">
                        <i class="fa-solid fa-map-location-dot me-1 text-primary"></i><?= is_marathi() ? 'नकाशावर पहा' : 'View on Map' ?>
                    </a>
                </div>
            </div>

            <!-- Destinations Row -->
            <?php if (!empty($destinations)): ?>
                <div class="row g-4">
                    <?php foreach ($destinations as $dest): ?>
                        <?php
                        $isWishlisted = in_array($dest['id'], $userWishlistIds);
                        $destName = t_val($dest['name'], $dest['name_mr']);
                        $destDesc = t_val($dest['short_description'], $dest['short_description_mr']);
                        $districtName = t_val($dest['district_name'], $dest['district_name_mr']);
                        $catName = t_val($dest['category_name'], $dest['category_name_mr']);
                        ?>
                        <div class="col-md-6 col-xl-4">
                            <div class="fe-card">
                                <div class="fe-card-img-wrap">
                                    <img src="<?= e($dest['primary_image']) ?>" alt="<?= e($destName) ?>" class="fe-card-img" loading="lazy">
                                    <span class="fe-card-category-badge">
                                        <?= e($catName) ?>
                                    </span>
                                    <span class="fe-card-district-badge">
                                        <i class="fa-solid fa-location-dot me-1 text-danger"></i><?= e($districtName) ?>
                                    </span>
                                    <button type="button" class="fe-wishlist-toggle-btn <?= $isWishlisted ? 'active' : '' ?>" data-dest-id="<?= $dest['id'] ?>" title="Save to Wishlist">
                                        <i class="<?= $isWishlisted ? 'fa-solid fa-heart text-danger' : 'fa-regular fa-heart' ?>"></i>
                                    </button>
                                </div>
                                <div class="fe-card-body">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <?= get_difficulty_badge($dest['difficulty_level']) ?>
                                        <span class="small text-muted"><i class="fa-solid fa-clock me-1"></i><?= $dest['trek_duration_hours'] ?> hrs</span>
                                    </div>
                                    <h5 class="fe-card-title"><?= e($destName) ?></h5>
                                    <div class="mb-2">
                                        <?= render_rating_stars((float)$dest['avg_rating']) ?>
                                    </div>
                                    <p class="fe-card-desc"><?= e($destDesc) ?></p>
                                    <div class="fe-card-footer">
                                        <span class="small text-muted">
                                            <i class="fa-solid fa-car me-1 text-secondary"></i><?= $dest['distance_from_pune_km'] ? $dest['distance_from_pune_km'] . ' km' : 'Accessible' ?>
                                        </span>
                                        <a href="destination.php?slug=<?= urlencode($dest['slug']) ?>" class="btn btn-sm btn-fe-primary">
                                            <?= __('btn_view_details') ?> <i class="fa-solid fa-arrow-right ms-1"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <!-- Empty State -->
                <div class="text-center py-5 bg-white rounded-4 border p-5">
                    <i class="fa-solid fa-compass-slash text-muted fa-4x mb-3"></i>
                    <h4 class="fw-bold text-dark font-heritage"><?= is_marathi() ? 'कोणतेही ठिकाण आढळले नाही' : 'No Destinations Found' ?></h4>
                    <p class="text-muted max-w-500 mx-auto mb-4">
                        <?= is_marathi()
                            ? 'आपण निवडलेल्या निकषांशी जुळणारे ठिकाण सापडले नाही. कृपया वेगळा शब्द शोधा किंवा फिल्टर रीसेट करा.'
                            : 'No destinations match your exact search criteria. Try loosening your filters or searching with a different keyword.' ?>
                    </p>
                    <a href="explore.php" class="btn btn-fe-primary">
                        <i class="fa-solid fa-rotate-left me-2"></i><?= __('btn_clear') ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
