<?php
/**
 * FORT EXPLORER - Admin Destinations List & Management
 */

$adminTitle = 'Manage Destinations & Forts';
require_once __DIR__ . '/includes/admin-header.php';

$db = getDB();

// Handle Quick Toggle Featured Action
if (isset($_GET['toggle_featured']) && is_numeric($_GET['toggle_featured'])) {
    $tId = (int)$_GET['toggle_featured'];
    $db->query("UPDATE destinations SET is_featured = IF(is_featured = 1, 0, 1) WHERE id = {$tId}");
    set_flash('success', 'Destination featured status updated.');
    header("Location: destinations.php");
    exit;
}

$search = trim($_GET['q'] ?? '');
$destinations = [];

if ($db) {
    try {
        $query = "
            SELECT d.*, c.name_en AS category_name, dist.name_en AS district_name,
                   (SELECT COUNT(*) FROM reviews r WHERE r.destination_id = d.id) AS review_count
            FROM destinations d
            JOIN destination_categories c ON d.category_id = c.id
            JOIN districts dist ON d.district_id = dist.id
            WHERE 1=1
        ";
        $params = [];
        if ($search !== '') {
            $query .= " AND (d.name LIKE ? OR d.name_mr LIKE ? OR dist.name_en LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        $query .= " ORDER BY d.id DESC";

        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $destinations = $stmt->fetchAll();
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<div class="admin-card">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h5 class="fw-bold font-heritage mb-1 text-dark">Destinations & Forts Directory</h5>
            <p class="text-muted small mb-0">Total <?= count($destinations) ?> destinations cataloged</p>
        </div>
        <div class="d-flex gap-2">
            <form method="GET" class="d-flex gap-2">
                <input type="text" name="q" class="form-control form-control-sm" placeholder="Search name or district..." value="<?= e($search) ?>">
                <button type="submit" class="btn btn-sm btn-outline-dark">Search</button>
            </form>
            <a href="destination-form.php" class="btn btn-sm btn-success text-nowrap">
                <i class="fa-solid fa-plus me-1"></i>Add Destination
            </a>
        </div>
    </div>

    <?php if (!empty($destinations)): ?>
        <div class="table-responsive">
            <table class="table admin-table align-middle">
                <thead>
                    <tr>
                        <th>Photo</th>
                        <th>Name</th>
                        <th>District</th>
                        <th>Category</th>
                        <th>Difficulty</th>
                        <th>Featured</th>
                        <th>Shivaji Fort</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($destinations as $d): ?>
                        <tr>
                            <td>
                                <img src="<?= e($d['primary_image']) ?>" alt="Thumbnail" class="table-thumb">
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= e($d['name']) ?></div>
                                <span class="small text-muted font-marathi"><?= e($d['name_mr']) ?></span>
                            </td>
                            <td><?= e($d['district_name']) ?></td>
                            <td><span class="badge bg-secondary-subtle text-dark"><?= e($d['category_name']) ?></span></td>
                            <td><?= get_difficulty_badge($d['difficulty_level']) ?></td>
                            <td>
                                <a href="destinations.php?toggle_featured=<?= $d['id'] ?>" class="text-decoration-none">
                                    <span class="badge <?= $d['is_featured'] ? 'bg-warning text-dark' : 'bg-light text-muted border' ?>">
                                        <?= $d['is_featured'] ? 'Featured' : 'Standard' ?>
                                    </span>
                                </a>
                            </td>
                            <td>
                                <?php if ($d['is_shivaji_fort']): ?>
                                    <span class="badge bg-danger-subtle text-danger"><i class="fa-solid fa-crown me-1"></i>Yes</span>
                                <?php else: ?>
                                    <span class="text-muted small">No</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="../destination.php?slug=<?= urlencode($d['slug']) ?>" target="_blank" class="btn btn-sm btn-outline-info py-1 px-2" title="View live">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    <a href="destination-form.php?id=<?= $d['id'] ?>" class="btn btn-sm btn-outline-primary py-1 px-2" title="Edit">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                    <form method="POST" action="destination-delete.php" onsubmit="return confirm('Are you sure you want to delete this destination? This cannot be undone.');" class="d-inline">
                                        <input type="hidden" name="id" value="<?= $d['id'] ?>">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-danger py-1 px-2" title="Delete">
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
    <?php else: ?>
        <p class="text-muted text-center py-4">No destinations found matching your query.</p>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
