<?php
/**
 * FORT EXPLORER - Admin Categories Management
 */

$adminTitle = 'Manage Destination Categories';
require_once __DIR__ . '/includes/admin-header.php';

$db = getDB();

// Handle Add / Edit Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_category') {
    if (verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $name_en = sanitize_input($_POST['name_en'] ?? '');
        $name_mr = sanitize_input($_POST['name_mr'] ?? '');
        $slug = sanitize_input($_POST['slug'] ?? '');
        if (empty($slug)) {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name_en)));
        }
        $icon = sanitize_input($_POST['icon_class'] ?? 'fa-map-pin');
        $color = sanitize_input($_POST['badge_color'] ?? '#163820');
        $desc_en = sanitize_input($_POST['description_en'] ?? '');
        $desc_mr = sanitize_input($_POST['description_mr'] ?? '');
        $editId = filter_input(INPUT_POST, 'cat_id', FILTER_VALIDATE_INT);

        try {
            if ($editId) {
                $upd = $db->prepare("UPDATE destination_categories SET name_en = ?, name_mr = ?, slug = ?, icon_class = ?, badge_color = ?, description_en = ?, description_mr = ? WHERE id = ?");
                $upd->execute([$name_en, $name_mr, $slug, $icon, $color, $desc_en, $desc_mr, $editId]);
                set_flash('success', 'Category updated successfully.');
            } else {
                $ins = $db->prepare("INSERT INTO destination_categories (name_en, name_mr, slug, icon_class, badge_color, description_en, description_mr) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $ins->execute([$name_en, $name_mr, $slug, $icon, $color, $desc_en, $desc_mr]);
                set_flash('success', 'New category created successfully.');
            }
            header("Location: categories.php");
            exit;
        } catch (Exception $e) {
            set_flash('danger', 'Error saving category: ' . $e->getMessage());
        }
    }
}

$categories = $db->query("
    SELECT c.*, (SELECT COUNT(*) FROM destinations d WHERE d.category_id = c.id) AS dest_count
    FROM destination_categories c
    ORDER BY c.id ASC
")->fetchAll();
?>

<div class="row g-4">
    <!-- Categories List -->
    <div class="col-lg-8">
        <div class="admin-card">
            <h5 class="fw-bold font-heritage mb-3 text-dark">Existing Categories</h5>
            <div class="table-responsive">
                <table class="table admin-table align-middle">
                    <thead>
                        <tr>
                            <th>Icon</th>
                            <th>Category Name</th>
                            <th>Marathi Name</th>
                            <th>Slug</th>
                            <th>Destinations</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td>
                                    <div class="rounded-circle d-flex align-items-center justify-content-center text-white" style="width:36px; height:36px; background:<?= e($cat['badge_color']) ?>;">
                                        <i class="fa-solid <?= e($cat['icon_class']) ?>"></i>
                                    </div>
                                </td>
                                <td><strong><?= e($cat['name_en']) ?></strong></td>
                                <td class="font-marathi"><?= e($cat['name_mr']) ?></td>
                                <td><code><?= e($cat['slug']) ?></code></td>
                                <td><span class="badge bg-secondary"><?= $cat['dest_count'] ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Category Form -->
    <div class="col-lg-4">
        <div class="admin-card">
            <h5 class="fw-bold font-heritage mb-3 text-success">Add New Category</h5>
            <form method="POST">
                <input type="hidden" name="action" value="save_category">
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Name (English)</label>
                    <input type="text" name="name_en" class="form-control form-control-sm" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Name (Marathi)</label>
                    <input type="text" name="name_mr" class="form-control form-control-sm font-marathi" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Slug (Optional)</label>
                    <input type="text" name="slug" class="form-control form-control-sm" placeholder="e.g. caves">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Icon Class (FontAwesome)</label>
                    <input type="text" name="icon_class" class="form-control form-control-sm" value="fa-map-pin">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Color Code (Hex)</label>
                    <input type="color" name="badge_color" class="form-control form-control-color" value="#163820">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Description (English)</label>
                    <textarea name="description_en" rows="2" class="form-control form-control-sm"></textarea>
                </div>

                <button type="submit" class="btn btn-sm btn-fe-primary w-100">
                    <i class="fa-solid fa-plus me-1"></i>Save Category
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
