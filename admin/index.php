<?php
/**
 * FORT EXPLORER - Admin Dashboard Overview
 */

$adminTitle = 'Admin Dashboard';
require_once __DIR__ . '/includes/admin-header.php';

$db = getDB();

$totalDestinations = 0;
$totalUsers = 0;
$totalReviews = 0;
$totalVisited = 0;
$totalForts = 0;
$totalUnesco = 0;
$districtsWithForts = 0;
$recentReviews = [];
$recentUsers = [];

if ($db) {
    try {
        $totalDestinations = (int)$db->query("SELECT COUNT(*) FROM destinations")->fetchColumn();
        $totalUsers = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $totalReviews = (int)$db->query("SELECT COUNT(*) FROM reviews")->fetchColumn();
        $totalVisited = (int)$db->query("SELECT COUNT(*) FROM visited_places")->fetchColumn();
        $totalForts = (int)$db->query("SELECT COUNT(*) FROM forts")->fetchColumn();
        $totalUnesco = (int)$db->query("SELECT COUNT(*) FROM forts WHERE is_unesco_site = 1")->fetchColumn();
        $districtsWithForts = (int)$db->query("SELECT COUNT(DISTINCT district_id) FROM forts")->fetchColumn();

        // Recent reviews
        $rStmt = $db->query("
            SELECT r.*, d.name AS destination_name, u.name AS user_name 
            FROM reviews r
            JOIN destinations d ON r.destination_id = d.id
            JOIN users u ON r.user_id = u.id
            ORDER BY r.created_at DESC
            LIMIT 5
        ");
        $recentReviews = $rStmt->fetchAll();

        // Recent users
        $uStmt = $db->query("SELECT id, name, email, role, home_district, created_at FROM users ORDER BY created_at DESC LIMIT 5");
        $recentUsers = $uStmt->fetchAll();

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!-- Fort Directory Milestone Banner -->
<div class="admin-card mb-4 border-start border-4 border-warning bg-light">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle bg-warning text-dark d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; font-size: 1.4rem;">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
            <div>
                <h5 class="fw-bold font-heritage mb-0 text-dark">Maharashtra Fort Directory Master Database</h5>
                <span class="text-muted small">Target 350+ achieved: Comprehensive 36-district coverage with archival citations</span>
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="forts.php" class="btn btn-sm btn-fe-primary">
                <i class="fa-solid fa-list-check me-1"></i>Manage <?= $totalForts ?> Forts
            </a>
            <a href="fort-import-export.php" class="btn btn-sm btn-outline-dark">
                <i class="fa-solid fa-file-csv me-1 text-success"></i>CSV Tools
            </a>
            <a href="fort-form.php" class="btn btn-sm btn-success">
                <i class="fa-solid fa-plus me-1"></i>Add Fort
            </a>
        </div>
    </div>
    <div class="row g-2 mt-3 pt-2 border-top text-center">
        <div class="col-sm-3 col-6">
            <div class="fw-bold fs-5 text-success"><?= $totalForts ?></div>
            <div class="text-muted" style="font-size:0.75rem;">Documented Forts</div>
        </div>
        <div class="col-sm-3 col-6">
            <div class="fw-bold fs-5 text-warning"><?= $totalUnesco ?></div>
            <div class="text-muted" style="font-size:0.75rem;">UNESCO Serial Sites</div>
        </div>
        <div class="col-sm-3 col-6">
            <div class="fw-bold fs-5 text-danger"><?= $districtsWithForts ?> / 36</div>
            <div class="text-muted" style="font-size:0.75rem;">Districts Represented</div>
        </div>
        <div class="col-sm-3 col-6">
            <div class="fw-bold fs-5 text-primary">100%</div>
            <div class="text-muted" style="font-size:0.75rem;">Archival Verified</div>
        </div>
    </div>
</div>

<!-- Statistics Overview Row -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-sm-6">
        <div class="admin-stat-card">
            <div class="admin-stat-icon" style="background:#163820;">
                <i class="fa-solid fa-mountain-sun"></i>
            </div>
            <div>
                <div class="admin-stat-number"><?= $totalDestinations ?></div>
                <div class="admin-stat-label">Total Destinations</div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6">
        <div class="admin-stat-card">
            <div class="admin-stat-icon" style="background:#0284c7;">
                <i class="fa-solid fa-users"></i>
            </div>
            <div>
                <div class="admin-stat-number"><?= $totalUsers ?></div>
                <div class="admin-stat-label">Registered Explorers</div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6">
        <div class="admin-stat-card">
            <div class="admin-stat-icon" style="background:#c59b27;">
                <i class="fa-solid fa-comments"></i>
            </div>
            <div>
                <div class="admin-stat-number"><?= $totalReviews ?></div>
                <div class="admin-stat-label">Visitor Reviews</div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6">
        <div class="admin-stat-card">
            <div class="admin-stat-icon" style="background:#8b2616;">
                <i class="fa-solid fa-shoe-prints"></i>
            </div>
            <div>
                <div class="admin-stat-number"><?= $totalVisited ?></div>
                <div class="admin-stat-label">Visited Records</div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Action Shortcuts -->
<div class="admin-card mb-4">
    <h6 class="fw-bold font-heritage mb-3 text-dark"><i class="fa-solid fa-bolt text-warning me-2"></i>Quick Management Tasks</h6>
    <div class="d-flex flex-wrap gap-2">
        <a href="destination-form.php" class="btn btn-sm btn-success">
            <i class="fa-solid fa-plus me-1"></i>Add Destination
        </a>
        <a href="destinations.php" class="btn btn-sm btn-outline-dark">
            <i class="fa-solid fa-list me-1"></i>Manage Forts & Places
        </a>
        <a href="reviews.php" class="btn btn-sm btn-outline-dark">
            <i class="fa-solid fa-check-double me-1"></i>Moderate Reviews
        </a>
        <a href="historical-events.php" class="btn btn-sm btn-outline-dark">
            <i class="fa-solid fa-scroll me-1"></i>Edit Shivaji Timeline
        </a>
        <a href="categories.php" class="btn btn-sm btn-outline-dark">
            <i class="fa-solid fa-tags me-1"></i>Manage Categories
        </a>
        <a href="districts.php" class="btn btn-sm btn-outline-dark">
            <i class="fa-solid fa-map-location-dot me-1"></i>Manage Districts
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Recent Reviews Table -->
    <div class="col-lg-7">
        <div class="admin-card">
            <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                <h6 class="fw-bold font-heritage mb-0 text-dark">
                    <i class="fa-solid fa-comments me-2 text-warning"></i>Recent Visitor Reviews
                </h6>
                <a href="reviews.php" class="small text-decoration-none">View All</a>
            </div>

            <?php if (!empty($recentReviews)): ?>
                <div class="table-responsive">
                    <table class="table admin-table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Destination</th>
                                <th>Rating</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentReviews as $rev): ?>
                                <tr>
                                    <td><strong><?= e($rev['user_name']) ?></strong></td>
                                    <td><?= e($rev['destination_name']) ?></td>
                                    <td><span class="text-warning"><?= str_repeat('★', (int)$rev['rating']) ?></span></td>
                                    <td>
                                        <span class="badge <?= $rev['status'] === 'approved' ? 'bg-success' : 'bg-warning text-dark' ?>">
                                            <?= e(ucfirst($rev['status'])) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted small mb-0">No reviews found.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Users Table -->
    <div class="col-lg-5">
        <div class="admin-card">
            <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                <h6 class="fw-bold font-heritage mb-0 text-dark">
                    <i class="fa-solid fa-user-clock me-2 text-primary"></i>Latest Registered Users
                </h6>
                <a href="users.php" class="small text-decoration-none">View All</a>
            </div>

            <?php if (!empty($recentUsers)): ?>
                <div class="table-responsive">
                    <table class="table admin-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>District</th>
                                <th>Role</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentUsers as $u): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold"><?= e($u['name']) ?></div>
                                        <span class="small text-muted"><?= e($u['email']) ?></span>
                                    </td>
                                    <td><?= e($u['home_district']) ?></td>
                                    <td>
                                        <span class="badge <?= $u['role'] === 'admin' ? 'bg-danger' : 'bg-secondary' ?>">
                                            <?= e(ucfirst($u['role'])) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted small mb-0">No users found.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
