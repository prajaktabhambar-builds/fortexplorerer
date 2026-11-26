<?php
/**
 * FORT EXPLORER - Admin Historical Timeline Events Management
 */

$adminTitle = 'Manage Shivaji Maharaj Timeline Events';
require_once __DIR__ . '/includes/admin-header.php';

$db = getDB();

// Handle Delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    $db->query("DELETE FROM historical_events WHERE id = {$delId}");
    set_flash('success', 'Timeline event deleted.');
    header("Location: historical-events.php");
    exit;
}

// Handle Add / Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_event') {
    if (verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $year = (int)$_POST['event_year'];
        $dateFormatted = sanitize_input($_POST['date_formatted'] ?? '');
        $titleEn = sanitize_input($_POST['title_en'] ?? '');
        $titleMr = sanitize_input($_POST['title_mr'] ?? '');
        $descEn = $_POST['description_en'] ?? '';
        $descMr = $_POST['description_mr'] ?? '';
        $destId = !empty($_POST['related_destination_id']) ? (int)$_POST['related_destination_id'] : null;
        $icon = sanitize_input($_POST['icon_name'] ?? 'fa-landmark');
        $level = sanitize_input($_POST['importance_level'] ?? 'Milestone');

        try {
            $ins = $db->prepare("
                INSERT INTO historical_events (event_year, date_formatted, title_en, title_mr, description_en, description_mr, related_destination_id, icon_name, importance_level)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $ins->execute([$year, $dateFormatted, $titleEn, $titleMr, $descEn, $descMr, $destId, $icon, $level]);
            set_flash('success', 'New historical milestone added.');
            header("Location: historical-events.php");
            exit;
        } catch (Exception $e) {
            set_flash('danger', 'Error adding event: ' . $e->getMessage());
        }
    }
}

$events = $db->query("
    SELECT he.*, d.name AS dest_name 
    FROM historical_events he 
    LEFT JOIN destinations d ON he.related_destination_id = d.id 
    ORDER BY he.event_year ASC
")->fetchAll();

$destinations = $db->query("SELECT id, name FROM destinations ORDER BY name ASC")->fetchAll();
?>

<div class="row g-4">
    <!-- Events List -->
    <div class="col-lg-8">
        <div class="admin-card">
            <h5 class="fw-bold font-heritage mb-3 text-dark">Chronological Events List (<?= count($events) ?>)</h5>
            <div class="table-responsive">
                <table class="table admin-table align-middle">
                    <thead>
                        <tr>
                            <th>Year</th>
                            <th>Title (English)</th>
                            <th>Title (Marathi)</th>
                            <th>Related Place</th>
                            <th>Type</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($events as $ev): ?>
                            <tr>
                                <td><span class="badge bg-dark fw-bold"><?= $ev['event_year'] ?></span></td>
                                <td><strong><?= e($ev['title_en']) ?></strong></td>
                                <td class="font-marathi"><?= e($ev['title_mr']) ?></td>
                                <td><?= e($ev['dest_name'] ?: '-') ?></td>
                                <td><span class="badge bg-primary-subtle text-primary"><?= e($ev['importance_level']) ?></span></td>
                                <td>
                                    <a href="historical-events.php?delete=<?= $ev['id'] ?>" onclick="return confirm('Delete event?');" class="btn btn-sm btn-outline-danger py-1 px-2" title="Delete">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Event Form -->
    <div class="col-lg-4">
        <div class="admin-card">
            <h5 class="fw-bold font-heritage mb-3 text-success">Add Timeline Milestone</h5>
            <form method="POST">
                <input type="hidden" name="action" value="save_event">
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Year (e.g. 1674) *</label>
                    <input type="number" name="event_year" class="form-control form-control-sm" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Date String (e.g. June 6, 1674)</label>
                    <input type="text" name="date_formatted" class="form-control form-control-sm">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Event Title (English) *</label>
                    <input type="text" name="title_en" class="form-control form-control-sm" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Event Title (Marathi - मराठी) *</label>
                    <input type="text" name="title_mr" class="form-control form-control-sm font-marathi" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Importance / Category</label>
                    <select name="importance_level" class="form-select form-select-sm">
                        <option value="Milestone">Milestone</option>
                        <option value="Battle">Battle</option>
                        <option value="Coronation">Coronation</option>
                        <option value="Treaty">Treaty</option>
                        <option value="Strategic">Strategic</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Related Destination</label>
                    <select name="related_destination_id" class="form-select form-select-sm">
                        <option value="">-- None / General --</option>
                        <?php foreach ($destinations as $d): ?>
                            <option value="<?= $d['id'] ?>"><?= e($d['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Icon (FontAwesome)</label>
                    <input type="text" name="icon_name" class="form-control form-control-sm" value="fa-landmark">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Description (English)</label>
                    <textarea name="description_en" rows="3" class="form-control form-control-sm" required></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Description (Marathi)</label>
                    <textarea name="description_mr" rows="3" class="form-control form-control-sm font-marathi" required></textarea>
                </div>

                <button type="submit" class="btn btn-sm btn-fe-primary w-100">
                    <i class="fa-solid fa-plus me-1"></i>Save Milestone
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
