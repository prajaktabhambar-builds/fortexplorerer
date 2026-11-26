<?php
/**
 * FORT EXPLORER - Admin Districts Management
 */

$adminTitle = 'Manage Districts';
require_once __DIR__ . '/includes/admin-header.php';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_district') {
    if (verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $name_en = sanitize_input($_POST['name_en'] ?? '');
        $name_mr = sanitize_input($_POST['name_mr'] ?? '');
        $division = sanitize_input($_POST['division'] ?? 'Pune Division');

        try {
            $ins = $db->prepare("INSERT INTO districts (name_en, name_mr, division) VALUES (?, ?, ?)");
            $ins->execute([$name_en, $name_mr, $division]);
            set_flash('success', 'District added successfully.');
            header("Location: districts.php");
            exit;
        } catch (Exception $e) {
            set_flash('danger', 'Error adding district: ' . $e->getMessage());
        }
    }
}

$districts = $db->query("
    SELECT dist.*, (SELECT COUNT(*) FROM destinations d WHERE d.district_id = dist.id) AS dest_count
    FROM districts dist
    ORDER BY dist.division ASC, dist.name_en ASC
")->fetchAll();
?>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="admin-card">
            <h5 class="fw-bold font-heritage mb-3 text-dark">Districts of Maharashtra</h5>
            <div class="table-responsive">
                <table class="table admin-table align-middle">
                    <thead>
                        <tr>
                            <th>District Name</th>
                            <th>Marathi Name</th>
                            <th>Division</th>
                            <th>Destinations</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($districts as $dist): ?>
                            <tr>
                                <td><strong><?= e($dist['name_en']) ?></strong></td>
                                <td class="font-marathi"><?= e($dist['name_mr']) ?></td>
                                <td><span class="badge bg-secondary-subtle text-dark"><?= e($dist['division']) ?></span></td>
                                <td><span class="badge bg-success"><?= $dist['dest_count'] ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="admin-card">
            <h5 class="fw-bold font-heritage mb-3 text-success">Add New District</h5>
            <form method="POST">
                <input type="hidden" name="action" value="add_district">
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label class="form-label small fw-bold">District Name (English)</label>
                    <input type="text" name="name_en" class="form-control form-control-sm" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">District Name (Marathi)</label>
                    <input type="text" name="name_mr" class="form-control form-control-sm font-marathi" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Administrative Division</label>
                    <select name="division" class="form-select form-select-sm">
                        <option value="Pune Division">Pune Division</option>
                        <option value="Konkan Division">Konkan Division</option>
                        <option value="Nashik Division">Nashik Division</option>
                        <option value="Marathwada Division">Marathwada Division</option>
                        <option value="Amravati Division">Amravati Division</option>
                        <option value="Nagpur Division">Nagpur Division</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-sm btn-fe-primary w-100">
                    <i class="fa-solid fa-plus me-1"></i>Add District
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
