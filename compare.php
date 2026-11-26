<?php
/**
 * FORT EXPLORER - Destination Comparison Matrix
 * Compare multiple forts and heritage destinations side-by-side across
 * history, difficulty, altitude, distances, best seasons, entry fees, and facilities.
 */

require_once __DIR__ . '/includes/header.php';

$db = getDB();
$allDestinations = [];
$selectedSlugs = [];

// Support query parameters e.g. ?dest1=raigad-fort&dest2=shivneri-fort
for ($i = 1; $i <= 4; $i++) {
    $param = trim($_GET["dest{$i}"] ?? '');
    if ($param) {
        $selectedSlugs[] = $param;
    }
}

// Fallback defaults if none selected
if (empty($selectedSlugs)) {
    $selectedSlugs = ['raigad-fort', 'shivneri-fort', 'sinhagad-fort'];
}

$comparedDestinations = [];

if ($db) {
    try {
        // Fetch all destinations for dropdown pickers
        $stmt = $db->query("
            SELECT id, name, name_mr, slug, primary_image 
            FROM destinations 
            ORDER BY is_shivaji_fort DESC, name ASC
        ");
        $allDestinations = $stmt->fetchAll();

        // Fetch selected destinations
        $placeholders = implode(',', array_fill(0, count($selectedSlugs), '?'));
        $cStmt = $db->prepare("
            SELECT d.*, 
                   c.name_en AS category_name, dist.name_en AS district_name, dist.name_mr AS district_name_mr
            FROM destinations d
            JOIN destination_categories c ON d.category_id = c.id
            JOIN districts dist ON d.district_id = dist.id
            WHERE d.slug IN ({$placeholders})
        ");
        $cStmt->execute($selectedSlugs);
        $comparedDestinations = $cStmt->fetchAll();

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!-- Header Banner -->
<div class="py-5" style="background: linear-gradient(135deg, var(--fe-forest-dark) 0%, var(--fe-forest) 100%); color: #fff; border-bottom: 3px solid var(--fe-gold);">
    <div class="container py-3 text-center">
        <span class="badge bg-warning text-dark px-3 py-1 rounded-pill small mb-2">
            <i class="fa-solid fa-code-compare me-1"></i>Side-by-Side Analysis
        </span>
        <h1 class="display-5 fw-bold font-heritage mb-2">Compare Heritage Destinations</h1>
        <p class="lead text-white-50 max-w-600 mx-auto mb-0 fs-6">
            Evaluate historical significance, trekking difficulty, altitude, distances, and visitor facilities to choose your next expedition.
        </p>
    </div>
</div>

<div class="container py-5">
    <!-- Destination Selection Form -->
    <div class="bg-white p-4 rounded-4 shadow-sm border mb-4">
        <h5 class="font-heritage text-success mb-3">
            <i class="fa-solid fa-layer-group me-2 text-warning"></i>Select Forts to Compare (Up to 4)
        </h5>
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3 col-6">
                <label class="form-label small fw-bold">Destination 1</label>
                <select name="dest1" class="form-select form-select-sm">
                    <?php foreach ($allDestinations as $d): ?>
                        <option value="<?= e($d['slug']) ?>" <?= in_array($d['slug'], array_slice($selectedSlugs, 0, 1), true) ? 'selected' : '' ?>>
                            <?= e($d['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 col-6">
                <label class="form-label small fw-bold">Destination 2</label>
                <select name="dest2" class="form-select form-select-sm">
                    <?php foreach ($allDestinations as $d): ?>
                        <option value="<?= e($d['slug']) ?>" <?= in_array($d['slug'], array_slice($selectedSlugs, 1, 1), true) ? 'selected' : '' ?>>
                            <?= e($d['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 col-6">
                <label class="form-label small fw-bold">Destination 3 (Optional)</label>
                <select name="dest3" class="form-select form-select-sm">
                    <option value="">-- None --</option>
                    <?php foreach ($allDestinations as $d): ?>
                        <option value="<?= e($d['slug']) ?>" <?= in_array($d['slug'], array_slice($selectedSlugs, 2, 1), true) ? 'selected' : '' ?>>
                            <?= e($d['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 col-6">
                <button type="submit" class="btn btn-fe-primary btn-sm w-100 py-2">
                    <i class="fa-solid fa-code-compare me-1"></i>Compare Now
                </button>
            </div>
        </form>
    </div>

    <!-- Comparison Table -->
    <?php if (!empty($comparedDestinations)): ?>
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0 text-center" style="min-width: 720px;">
                    <!-- Photos & Name Header -->
                    <thead class="table-light">
                        <tr>
                            <th style="width: 20%;" class="text-start bg-light">Destination</th>
                            <?php foreach ($comparedDestinations as $dest): ?>
                                <th style="width: <?= 80 / count($comparedDestinations) ?>%;">
                                    <img src="<?= e($dest['primary_image']) ?>" alt="<?= e($dest['name']) ?>" class="img-fluid rounded-3 mb-2 shadow-sm w-100" style="height: 140px; object-fit: cover;">
                                    <h5 class="fw-bold font-heritage mb-1 text-dark"><?= e($dest['name']) ?></h5>
                                    <span class="badge bg-dark text-white rounded-pill small"><?= e($dest['district_name']) ?></span>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Category & Type -->
                        <tr>
                            <td class="text-start fw-bold text-success bg-light"><i class="fa-solid fa-shield-halved me-2"></i>Classification</td>
                            <?php foreach ($comparedDestinations as $dest): ?>
                                <td>
                                    <span class="badge bg-success-subtle text-success px-3 py-1 rounded-pill">
                                        <?= e($dest['category_name']) ?>
                                    </span>
                                    <?php if ($dest['is_shivaji_fort']): ?>
                                        <div class="small text-warning fw-bold mt-1"><i class="fa-solid fa-crown me-1"></i>Shivaji Maharaj Fort</div>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>

                        <!-- Trekking Difficulty -->
                        <tr>
                            <td class="text-start fw-bold text-success bg-light"><i class="fa-solid fa-gauge-high me-2"></i>Trek Difficulty</td>
                            <?php foreach ($comparedDestinations as $dest): ?>
                                <td><?= get_difficulty_badge($dest['difficulty_level']) ?></td>
                            <?php endforeach; ?>
                        </tr>

                        <!-- Altitude / Height -->
                        <tr>
                            <td class="text-start fw-bold text-success bg-light"><i class="fa-solid fa-mountain me-2"></i>Elevation / Altitude</td>
                            <?php foreach ($comparedDestinations as $dest): ?>
                                <td class="fw-bold fs-6 text-dark">
                                    <?= $dest['altitude_meters'] ? $dest['altitude_meters'] . ' meters' : 'N/A' ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>

                        <!-- Historical Importance -->
                        <tr>
                            <td class="text-start fw-bold text-success bg-light"><i class="fa-solid fa-landmark me-2"></i>Historical Significance</td>
                            <?php foreach ($comparedDestinations as $dest): ?>
                                <td class="small text-secondary text-start" style="line-height: 1.6;">
                                    <?= nl2br(e(substr($dest['historical_significance'] ?: $dest['overview'], 0, 240))) ?>...
                                </td>
                            <?php endforeach; ?>
                        </tr>

                        <!-- Best Visiting Season -->
                        <tr>
                            <td class="text-start fw-bold text-success bg-light"><i class="fa-solid fa-calendar-days me-2"></i>Best Season</td>
                            <?php foreach ($comparedDestinations as $dest): ?>
                                <td class="small fw-bold text-primary">
                                    <i class="fa-solid fa-cloud-sun me-1"></i><?= e($dest['best_season']) ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>

                        <!-- Distance from Pune & Mumbai -->
                        <tr>
                            <td class="text-start fw-bold text-success bg-light"><i class="fa-solid fa-road me-2"></i>Distances</td>
                            <?php foreach ($comparedDestinations as $dest): ?>
                                <td class="small">
                                    <div><strong>Pune:</strong> <?= $dest['distance_from_pune_km'] ? $dest['distance_from_pune_km'] . ' km' : 'N/A' ?></div>
                                    <div><strong>Mumbai:</strong> <?= $dest['distance_from_mumbai_km'] ? $dest['distance_from_mumbai_km'] . ' km' : 'N/A' ?></div>
                                </td>
                            <?php endforeach; ?>
                        </tr>

                        <!-- Timings & Verification -->
                        <tr>
                            <td class="text-start fw-bold text-success bg-light"><i class="fa-solid fa-clock me-2"></i>Opening Hours</td>
                            <?php foreach ($comparedDestinations as $dest): ?>
                                <td class="small">
                                    <span class="badge <?= $dest['is_timing_verified'] ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-dark' ?> mb-1">
                                        <?= $dest['is_timing_verified'] ? 'Verified' : 'General' ?>
                                    </span>
                                    <div class="text-dark fw-bold"><?= e($dest['opening_hours']) ?></div>
                                </td>
                            <?php endforeach; ?>
                        </tr>

                        <!-- Entry Tickets -->
                        <tr>
                            <td class="text-start fw-bold text-success bg-light"><i class="fa-solid fa-ticket me-2"></i>Entry Fees</td>
                            <?php foreach ($comparedDestinations as $dest): ?>
                                <td class="small fw-bold text-dark">
                                    <?= e($dest['entry_fees']) ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>

                        <!-- Facilities Available -->
                        <tr>
                            <td class="text-start fw-bold text-success bg-light"><i class="fa-solid fa-restroom me-2"></i>Facilities</td>
                            <?php foreach ($comparedDestinations as $dest): ?>
                                <td class="small">
                                    <?php if ($dest['facilities']): ?>
                                        <?php foreach (explode(',', $dest['facilities']) as $f): ?>
                                            <span class="badge bg-light text-dark border me-1 mb-1"><?= trim(e($f)) ?></span>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span class="text-muted">Basic wilderness</span>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>

                        <!-- Action Buttons -->
                        <tr>
                            <td class="text-start bg-light"></td>
                            <?php foreach ($comparedDestinations as $dest): ?>
                                <td>
                                    <a href="destination.php?slug=<?= urlencode($dest['slug']) ?>" class="btn btn-sm btn-fe-primary rounded-pill px-3">
                                        Full Details <i class="fa-solid fa-arrow-right ms-1"></i>
                                    </a>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
