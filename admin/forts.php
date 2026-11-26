<?php
/**
 * FORT EXPLORER - Admin Maharashtra Fort Directory Management
 * View, filter, verify, edit, and manage 350+ historical forts.
 */

$adminTitle = 'Maharashtra Fort Directory (350+)';
require_once __DIR__ . '/includes/admin-header.php';

$db = getDB();

// Quick Toggle Verification Status Action
if (isset($_GET['toggle_verify']) && is_numeric($_GET['toggle_verify'])) {
    $tId = (int)$_GET['toggle_verify'];
    $currentStatus = $db->query("SELECT verification_status, destination_id FROM forts WHERE id = {$tId}")->fetch();
    if ($currentStatus) {
        $newStatus = ($currentStatus['verification_status'] === 'verified') ? 'pending_verification' : 'verified';
        $db->query("UPDATE forts SET verification_status = '{$newStatus}' WHERE id = {$tId}");
        if (!empty($currentStatus['destination_id'])) {
            $db->query("UPDATE destinations SET verification_status = '{$newStatus}' WHERE id = " . (int)$currentStatus['destination_id']);
        }
        set_flash('success', "Fort verification status updated to " . ucfirst(str_replace('_', ' ', $newStatus)) . ".");
    }
    header("Location: forts.php" . (!empty($_SERVER['QUERY_STRING']) ? '?' . preg_replace('/&?toggle_verify=\d+/', '', $_SERVER['QUERY_STRING']) : ''));
    exit;
}

// Quick Toggle UNESCO Action
if (isset($_GET['toggle_unesco']) && is_numeric($_GET['toggle_unesco'])) {
    $tId = (int)$_GET['toggle_unesco'];
    $fRow = $db->query("SELECT is_unesco_site, destination_id FROM forts WHERE id = {$tId}")->fetch();
    if ($fRow) {
        $newUnesco = $fRow['is_unesco_site'] ? 0 : 1;
        $db->query("UPDATE forts SET is_unesco_site = {$newUnesco} WHERE id = {$tId}");
        if (!empty($fRow['destination_id'])) {
            $db->query("UPDATE destinations SET is_unesco_site = {$newUnesco} WHERE id = " . (int)$fRow['destination_id']);
        }
        set_flash('success', "UNESCO nomination status toggled successfully.");
    }
    header("Location: forts.php" . (!empty($_SERVER['QUERY_STRING']) ? '?' . preg_replace('/&?toggle_unesco=\d+/', '', $_SERVER['QUERY_STRING']) : ''));
    exit;
}

// Filter inputs
$search = trim($_GET['q'] ?? '');
$filterDistrict = !empty($_GET['district_id']) && is_numeric($_GET['district_id']) ? (int)$_GET['district_id'] : null;
$filterCategory = !empty($_GET['category_id']) && is_numeric($_GET['category_id']) ? (int)$_GET['category_id'] : null;
$filterStatus = trim($_GET['status'] ?? '');
$filterUnesco = isset($_GET['unesco']) && $_GET['unesco'] === '1' ? 1 : null;
$filterShivaji = isset($_GET['shivaji']) && $_GET['shivaji'] === '1' ? 1 : null;

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 30;
$offset = ($page - 1) * $perPage;

$districts = $db->query("SELECT id, name_en, name_mr FROM districts ORDER BY name_en ASC")->fetchAll();
$categories = $db->query("SELECT id, code, name_en, name_mr, traditional_name FROM fort_categories ORDER BY id ASC")->fetchAll();

// Statistics Queries
$stats = [
    'total' => 0,
    'verified' => 0,
    'unesco' => 0,
    'shivaji' => 0,
    'districts_count' => 0
];

try {
    $stats['total'] = (int)$db->query("SELECT COUNT(*) FROM forts")->fetchColumn();
    $stats['verified'] = (int)$db->query("SELECT COUNT(*) FROM forts WHERE verification_status = 'verified'")->fetchColumn();
    $stats['unesco'] = (int)$db->query("SELECT COUNT(*) FROM forts WHERE is_unesco_site = 1")->fetchColumn();
    $stats['shivaji'] = (int)$db->query("SELECT COUNT(*) FROM forts WHERE is_shivaji_fort = 1")->fetchColumn();
    $stats['districts_count'] = (int)$db->query("SELECT COUNT(DISTINCT district_id) FROM forts")->fetchColumn();

    // Base query
    $whereClauses = ["1=1"];
    $params = [];

    if ($search !== '') {
        $whereClauses[] = "(f.name LIKE ? OR f.name_mr LIKE ? OR f.taluka LIKE ? OR f.alternate_names LIKE ?)";
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
    }

    if ($filterDistrict) {
        $whereClauses[] = "f.district_id = ?";
        $params[] = $filterDistrict;
    }

    if ($filterCategory) {
        $whereClauses[] = "f.category_id = ?";
        $params[] = $filterCategory;
    }

    if ($filterStatus !== '' && in_array($filterStatus, ['verified', 'pending_verification', 'needs_correction', 'unverified'], true)) {
        $whereClauses[] = "f.verification_status = ?";
        $params[] = $filterStatus;
    }

    if ($filterUnesco === 1) {
        $whereClauses[] = "f.is_unesco_site = 1";
    }

    if ($filterShivaji === 1) {
        $whereClauses[] = "f.is_shivaji_fort = 1";
    }

    $whereSQL = implode(' AND ', $whereClauses);

    // Count filtered records
    $countStmt = $db->prepare("SELECT COUNT(*) FROM forts f WHERE {$whereSQL}");
    $countStmt->execute($params);
    $totalFiltered = (int)$countStmt->fetchColumn();
    $totalPages = max(1, ceil($totalFiltered / $perPage));

    // Fetch records
    $querySQL = "
        SELECT f.*, 
               dist.name_en AS district_name, dist.name_mr AS district_name_mr,
               fc.name_en AS category_name, fc.name_mr AS category_name_mr, 
               fc.traditional_name, fc.badge_color, fc.badge_icon,
               d.slug AS destination_slug
        FROM forts f
        JOIN districts dist ON f.district_id = dist.id
        JOIN fort_categories fc ON f.category_id = fc.id
        LEFT JOIN destinations d ON f.destination_id = d.id
        WHERE {$whereSQL}
        ORDER BY f.is_unesco_site DESC, f.id ASC
        LIMIT {$perPage} OFFSET {$offset}
    ";

    $stmt = $db->prepare($querySQL);
    $stmt->execute($params);
    $forts = $stmt->fetchAll();

} catch (Exception $e) {
    $error = $e->getMessage();
    $forts = [];
    $totalFiltered = 0;
    $totalPages = 1;
}
?>

<!-- Statistics Overview Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-sm-6">
        <div class="admin-stat-card border-start border-4 border-success">
            <div class="admin-stat-icon" style="background:#163820;">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
            <div>
                <div class="admin-stat-number"><?= $stats['total'] ?></div>
                <div class="admin-stat-label">Total Documented Forts</div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6">
        <div class="admin-stat-card border-start border-4 border-info">
            <div class="admin-stat-icon" style="background:#0284c7;">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <div>
                <div class="admin-stat-number"><?= $stats['verified'] ?></div>
                <div class="admin-stat-label">Verified Historical Records</div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6">
        <div class="admin-stat-card border-start border-4 border-warning">
            <div class="admin-stat-icon" style="background:#c59b27;">
                <i class="fa-solid fa-crown"></i>
            </div>
            <div>
                <div class="admin-stat-number"><?= $stats['unesco'] ?></div>
                <div class="admin-stat-label">UNESCO Maratha Landscapes</div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6">
        <div class="admin-stat-card border-start border-4 border-danger">
            <div class="admin-stat-icon" style="background:#8b2616;">
                <i class="fa-solid fa-map-location-dot"></i>
            </div>
            <div>
                <div class="admin-stat-number"><?= $stats['districts_count'] ?> / 36</div>
                <div class="admin-stat-label">Districts Represented</div>
            </div>
        </div>
    </div>
</div>

<div class="admin-card">
    <!-- Header & Action Buttons -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <div>
            <h5 class="fw-bold font-heritage mb-1 text-dark">Maharashtra Historical Forts Master Directory</h5>
            <p class="text-muted small mb-0">
                Showing <?= count($forts) ?> of <?= $totalFiltered ?> matching fort records (Page <?= $page ?> of <?= $totalPages ?>)
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="fort-import-export.php" class="btn btn-sm btn-outline-secondary">
                <i class="fa-solid fa-file-csv me-1 text-success"></i>CSV Import / Export
            </a>
            <a href="fort-form.php" class="btn btn-sm btn-success">
                <i class="fa-solid fa-plus me-1"></i>Add New Fort
            </a>
        </div>
    </div>

    <!-- Advanced Filter Bar -->
    <form method="GET" class="p-3 bg-light rounded-3 border mb-4">
        <div class="row g-2 align-items-center">
            <div class="col-lg-3 col-md-6">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="text" name="q" class="form-control" placeholder="Search name, taluka, alt..." value="<?= e($search) ?>">
                </div>
            </div>

            <div class="col-lg-2 col-md-6">
                <select name="district_id" class="form-select form-select-sm">
                    <option value="">All 36 Districts</option>
                    <?php foreach ($districts as $d): ?>
                        <option value="<?= $d['id'] ?>" <?= $filterDistrict === $d['id'] ? 'selected' : '' ?>>
                            <?= e($d['name_en']) ?> (<?= e($d['name_mr']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-lg-2 col-md-4">
                <select name="category_id" class="form-select form-select-sm">
                    <option value="">All Classifications</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $filterCategory === $cat['id'] ? 'selected' : '' ?>>
                            <?= e($cat['traditional_name']) ?> (<?= e($cat['name_en']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-lg-2 col-md-4">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Verification Statuses</option>
                    <option value="verified" <?= $filterStatus === 'verified' ? 'selected' : '' ?>>Verified</option>
                    <option value="pending_verification" <?= $filterStatus === 'pending_verification' ? 'selected' : '' ?>>Pending Verification</option>
                    <option value="needs_correction" <?= $filterStatus === 'needs_correction' ? 'selected' : '' ?>>Needs Correction</option>
                    <option value="unverified" <?= $filterStatus === 'unverified' ? 'selected' : '' ?>>Unverified</option>
                </select>
            </div>

            <div class="col-lg-3 col-md-4 d-flex align-items-center gap-2">
                <div class="form-check form-check-inline small mb-0">
                    <input class="form-check-input" type="checkbox" name="unesco" value="1" id="chkUnesco" <?= $filterUnesco === 1 ? 'checked' : '' ?>>
                    <label class="form-check-label text-nowrap" for="chkUnesco">UNESCO</label>
                </div>
                <div class="form-check form-check-inline small mb-0">
                    <input class="form-check-input" type="checkbox" name="shivaji" value="1" id="chkShivaji" <?= $filterShivaji === 1 ? 'checked' : '' ?>>
                    <label class="form-check-label text-nowrap" for="chkShivaji">Shivaji</label>
                </div>
                <button type="submit" class="btn btn-sm btn-dark px-3">Filter</button>
                <?php if ($search !== '' || $filterDistrict || $filterCategory || $filterStatus !== '' || $filterUnesco || $filterShivaji): ?>
                    <a href="forts.php" class="btn btn-sm btn-outline-secondary" title="Clear Filters"><i class="fa-solid fa-rotate-left"></i></a>
                <?php endif; ?>
            </div>
        </div>
    </form>

    <!-- Forts Table -->
    <?php if (!empty($forts)): ?>
        <div class="table-responsive">
            <table class="table admin-table align-middle">
                <thead>
                    <tr>
                        <th style="width: 50px;">Thumb</th>
                        <th>Fort Name</th>
                        <th>District & Taluka</th>
                        <th>Classification</th>
                        <th>Altitude</th>
                        <th>Significance</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($forts as $f): ?>
                        <tr>
                            <td>
                                <img src="<?= e($f['primary_image'] ?: BASE_URL . 'assets/images/default-fort.jpg') ?>" 
                                     alt="<?= e($f['name']) ?>" 
                                     class="rounded-2" 
                                     style="width: 46px; height: 46px; object-fit: cover;">
                            </td>
                            <td>
                                <div class="fw-bold text-dark d-flex align-items-center gap-1">
                                    <span><?= e($f['name']) ?></span>
                                    <?php if ($f['is_unesco_site']): ?>
                                        <span class="badge bg-warning text-dark small" title="UNESCO Maratha Military Landscapes Component">
                                            <i class="fa-solid fa-crown me-1"></i>UNESCO
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="small text-muted font-marathi"><?= e($f['name_mr']) ?></div>
                                <?php if (!empty($f['alternate_names'])): ?>
                                    <div class="text-secondary" style="font-size: 0.72rem;">Alias: <?= e($f['alternate_names']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark"><?= e($f['district_name']) ?></div>
                                <div class="small text-muted"><?= !empty($f['taluka']) ? e($f['taluka']) . ' Taluka' : 'District Center' ?></div>
                            </td>
                            <td>
                                <span class="badge" style="background-color: <?= e($f['badge_color'] ?: '#163820') ?>; font-size: 0.75rem;">
                                    <i class="fa-solid <?= e($f['badge_icon'] ?: 'fa-shield-halved') ?> me-1"></i><?= e($f['traditional_name']) ?>
                                </span>
                            </td>
                            <td>
                                <?= !empty($f['altitude_meters']) ? '<span class="fw-semibold">' . number_format($f['altitude_meters']) . ' m</span>' : '<span class="text-muted">—</span>' ?>
                            </td>
                            <td>
                                <div class="d-flex flex-column gap-1">
                                    <?php if ($f['is_shivaji_fort']): ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size:0.68rem; width: fit-content;">
                                            <i class="fa-solid fa-flag me-1"></i>Shivaji Period
                                        </span>
                                    <?php endif; ?>
                                    <span class="badge bg-light text-secondary border" style="font-size:0.68rem; width: fit-content;">
                                        <?= e($f['trekking_difficulty']) ?> Trek
                                    </span>
                                </div>
                            </td>
                            <td>
                                <?php
                                $statusClass = match ($f['verification_status']) {
                                    'verified' => 'bg-success text-white',
                                    'pending_verification' => 'bg-warning text-dark',
                                    'needs_correction' => 'bg-danger text-white',
                                    default => 'bg-secondary text-white'
                                };
                                ?>
                                <a href="forts.php?toggle_verify=<?= $f['id'] ?>" class="text-decoration-none" title="Click to toggle verification status">
                                    <span class="badge <?= $statusClass ?> p-1.5" style="font-size: 0.72rem;">
                                        <?= ucfirst(str_replace('_', ' ', $f['verification_status'])) ?>
                                    </span>
                                </a>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <a href="../destination.php?slug=<?= urlencode($f['slug']) ?>" target="_blank" class="btn btn-sm btn-outline-info py-1 px-2" title="View live page">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    <a href="fort-form.php?id=<?= $f['id'] ?>" class="btn btn-sm btn-outline-primary py-1 px-2" title="Edit fort metadata">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                    <form method="POST" action="fort-delete.php" onsubmit="return confirm('Delete <?= addslashes(e($f['name'])) ?>? This cannot be undone.');" class="d-inline">
                                        <input type="hidden" name="id" value="<?= $f['id'] ?>">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-danger py-1 px-2" title="Delete fort">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination Controls -->
        <?php if ($totalPages > 1): ?>
            <div class="d-flex flex-wrap justify-content-between align-items-center mt-4 pt-3 border-top gap-3">
                <span class="small text-muted">
                    Page <?= $page ?> of <?= $totalPages ?> (Total <?= $totalFiltered ?> forts)
                </span>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <?php
                        $queryParams = $_GET;
                        $createPageUrl = function($p) use ($queryParams) {
                            $queryParams['page'] = $p;
                            return 'forts.php?' . http_build_query($queryParams);
                        };
                        ?>
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= $createPageUrl($page - 1) ?>">&laquo; Prev</a>
                        </li>
                        
                        <?php 
                        $startPage = max(1, $page - 3);
                        $endPage = min($totalPages, $page + 3);
                        for ($p = $startPage; $p <= $endPage; $p++): 
                        ?>
                            <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                                <a class="page-link" href="<?= $createPageUrl($p) ?>"><?= $p ?></a>
                            </li>
                        <?php endfor; ?>

                        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= $createPageUrl($page + 1) ?>">Next &raquo;</a>
                        </li>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <div class="text-center py-5">
            <i class="fa-solid fa-folder-open text-muted fs-1 mb-3"></i>
            <h5 class="fw-bold text-dark">No fort records found</h5>
            <p class="text-muted small">Try broadening your search keywords or resetting the district and classification filters.</p>
            <a href="forts.php" class="btn btn-sm btn-fe-primary">Reset Filters</a>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
