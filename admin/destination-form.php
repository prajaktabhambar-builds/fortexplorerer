<?php
/**
 * FORT EXPLORER - Admin Add / Edit Destination Form
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$db = getDB();
$destId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$destination = null;
$adminTitle = $destId ? 'Edit Destination' : 'Add New Destination';

$categories = $db->query("SELECT * FROM destination_categories ORDER BY name_en ASC")->fetchAll();
$districts = $db->query("SELECT * FROM districts ORDER BY name_en ASC")->fetchAll();

if ($destId) {
    $stmt = $db->prepare("SELECT * FROM destinations WHERE id = ?");
    $stmt->execute([$destId]);
    $destination = $stmt->fetch();
    if (!$destination) {
        set_flash('danger', 'Destination not found.');
        header("Location: destinations.php");
        exit;
    }
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $name = sanitize_input($_POST['name'] ?? '');
        $name_mr = sanitize_input($_POST['name_mr'] ?? '');
        $slug = sanitize_input($_POST['slug'] ?? '');
        if (empty($slug)) {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
        }

        $category_id = (int)$_POST['category_id'];
        $district_id = (int)$_POST['district_id'];
        $short_desc = sanitize_input($_POST['short_description'] ?? '');
        $short_desc_mr = sanitize_input($_POST['short_description_mr'] ?? '');
        $overview = $_POST['overview'] ?? '';
        $historical = $_POST['historical_significance'] ?? '';
        $historical_mr = $_POST['historical_significance_mr'] ?? '';
        $shivaji = $_POST['shivaji_connection'] ?? '';
        $shivaji_mr = $_POST['shivaji_connection_mr'] ?? '';
        $architecture = $_POST['architecture_notes'] ?? '';
        $major_attractions = $_POST['major_attractions'] ?? '';

        $latitude = (float)($_POST['latitude'] ?? 18.0);
        $longitude = (float)($_POST['longitude'] ?? 73.0);
        $altitude = !empty($_POST['altitude_meters']) ? (int)$_POST['altitude_meters'] : null;
        $address = sanitize_input($_POST['address'] ?? '');
        $best_season = sanitize_input($_POST['best_season'] ?? 'October to February');
        $opening_hours = sanitize_input($_POST['opening_hours'] ?? 'Open 24 hours');
        $entry_fees = sanitize_input($_POST['entry_fees'] ?? 'Free entry');
        $is_timing_verified = isset($_POST['is_timing_verified']) ? 1 : 0;

        $road = sanitize_input($_POST['how_to_reach_road'] ?? '');
        $rail = sanitize_input($_POST['how_to_reach_rail'] ?? '');
        $air = sanitize_input($_POST['how_to_reach_air'] ?? '');
        $dist_pune = !empty($_POST['distance_from_pune_km']) ? (int)$_POST['distance_from_pune_km'] : null;
        $dist_mumbai = !empty($_POST['distance_from_mumbai_km']) ? (int)$_POST['distance_from_mumbai_km'] : null;
        $facilities = sanitize_input($_POST['facilities'] ?? '');
        $safety = sanitize_input($_POST['safety_tips'] ?? '');
        $difficulty = sanitize_input($_POST['difficulty_level'] ?? 'Moderate');
        $trek_duration = (float)($_POST['trek_duration_hours'] ?? 2.5);
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $is_shivaji_fort = isset($_POST['is_shivaji_fort']) ? 1 : 0;

        // Image handling: File upload takes precedence over text URL
        $primary_image = $destination['primary_image'] ?? 'https://images.unsplash.com/photo-1598894000396-bc4a04d509f6?auto=format&fit=crop&w=1200&q=80';
        if (!empty($_POST['primary_image_url'])) {
            $primary_image = trim($_POST['primary_image_url']);
        }
        if (isset($_FILES['primary_image_file']) && $_FILES['primary_image_file']['error'] === UPLOAD_ERR_OK) {
            $up = upload_image($_FILES['primary_image_file'], 'uploads');
            if ($up['success']) {
                $primary_image = BASE_URL . $up['path'];
            }
        }

        try {
            if ($destId) {
                // UPDATE
                $upd = $db->prepare("
                    UPDATE destinations SET
                        category_id = ?, district_id = ?, name = ?, name_mr = ?, slug = ?,
                        short_description = ?, short_description_mr = ?, overview = ?,
                        historical_significance = ?, historical_significance_mr = ?,
                        shivaji_connection = ?, shivaji_connection_mr = ?,
                        architecture_notes = ?, major_attractions = ?,
                        latitude = ?, longitude = ?, altitude_meters = ?, address = ?,
                        best_season = ?, opening_hours = ?, entry_fees = ?, is_timing_verified = ?,
                        how_to_reach_road = ?, how_to_reach_rail = ?, how_to_reach_air = ?,
                        distance_from_pune_km = ?, distance_from_mumbai_km = ?,
                        facilities = ?, safety_tips = ?, difficulty_level = ?, trek_duration_hours = ?,
                        is_featured = ?, is_shivaji_fort = ?, primary_image = ?
                    WHERE id = ?
                ");
                $upd->execute([
                    $category_id, $district_id, $name, $name_mr, $slug,
                    $short_desc, $short_desc_mr, $overview,
                    $historical, $historical_mr,
                    $shivaji, $shivaji_mr,
                    $architecture, $major_attractions,
                    $latitude, $longitude, $altitude, $address,
                    $best_season, $opening_hours, $entry_fees, $is_timing_verified,
                    $road, $rail, $air,
                    $dist_pune, $dist_mumbai,
                    $facilities, $safety, $difficulty, $trek_duration,
                    $is_featured, $is_shivaji_fort, $primary_image,
                    $destId
                ]);
                set_flash('success', 'Destination updated successfully!');
            } else {
                // INSERT
                $ins = $db->prepare("
                    INSERT INTO destinations (
                        category_id, district_id, name, name_mr, slug,
                        short_description, short_description_mr, overview,
                        historical_significance, historical_significance_mr,
                        shivaji_connection, shivaji_connection_mr,
                        architecture_notes, major_attractions,
                        latitude, longitude, altitude_meters, address,
                        best_season, opening_hours, entry_fees, is_timing_verified,
                        how_to_reach_road, how_to_reach_rail, how_to_reach_air,
                        distance_from_pune_km, distance_from_mumbai_km,
                        facilities, safety_tips, difficulty_level, trek_duration_hours,
                        is_featured, is_shivaji_fort, primary_image
                    ) VALUES (
                        ?, ?, ?, ?, ?,
                        ?, ?, ?,
                        ?, ?,
                        ?, ?,
                        ?, ?,
                        ?, ?, ?, ?,
                        ?, ?, ?, ?,
                        ?, ?, ?,
                        ?, ?,
                        ?, ?, ?, ?,
                        ?, ?, ?
                    )
                ");
                $ins->execute([
                    $category_id, $district_id, $name, $name_mr, $slug,
                    $short_desc, $short_desc_mr, $overview,
                    $historical, $historical_mr,
                    $shivaji, $shivaji_mr,
                    $architecture, $major_attractions,
                    $latitude, $longitude, $altitude, $address,
                    $best_season, $opening_hours, $entry_fees, $is_timing_verified,
                    $road, $rail, $air,
                    $dist_pune, $dist_mumbai,
                    $facilities, $safety, $difficulty, $trek_duration,
                    $is_featured, $is_shivaji_fort, $primary_image
                ]);
                $destId = (int)$db->lastInsertId();
                set_flash('success', 'New destination added successfully!');
            }

            header("Location: destinations.php");
            exit;

        } catch (Exception $e) {
            set_flash('danger', 'Database error: ' . $e->getMessage());
        }
    }
}

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="admin-card">
    <div class="d-flex align-items-center justify-content-between mb-4 border-bottom pb-3">
        <h5 class="fw-bold font-heritage mb-0 text-success">
            <i class="fa-solid <?= $destId ? 'fa-pen-to-square' : 'fa-plus-circle' ?> me-2 text-warning"></i><?= $adminTitle ?>
        </h5>
        <a href="destinations.php" class="btn btn-sm btn-outline-secondary">
            <i class="fa-solid fa-arrow-left me-1"></i>Back to List
        </a>
    </div>

    <form method="POST" enctype="multipart/form-data">
        <?= csrf_field() ?>

        <!-- Basic Information -->
        <h6 class="fw-bold font-heritage text-primary mb-3">1. Identification & Classification</h6>
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <label class="form-label small fw-bold">Destination Name (English) *</label>
                <input type="text" name="name" class="form-control" value="<?= e($destination['name'] ?? '') ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label small fw-bold">Destination Name (Marathi - मराठी) *</label>
                <input type="text" name="name_mr" class="form-control font-marathi" value="<?= e($destination['name_mr'] ?? '') ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold">URL Slug (leave blank to auto-generate)</label>
                <input type="text" name="slug" class="form-control" value="<?= e($destination['slug'] ?? '') ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold">Category *</label>
                <select name="category_id" class="form-select" required>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= ($destination['category_id'] ?? 1) == $cat['id'] ? 'selected' : '' ?>>
                            <?= e($cat['name_en']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold">District *</label>
                <select name="district_id" class="form-select" required>
                    <?php foreach ($districts as $dist): ?>
                        <option value="<?= $dist['id'] ?>" <?= ($destination['district_id'] ?? 1) == $dist['id'] ? 'selected' : '' ?>>
                            <?= e($dist['name_en']) ?> (<?= e($dist['division']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <div class="form-check form-switch mt-2">
                    <input class="form-check-input" type="checkbox" name="is_shivaji_fort" id="isShivajiFortCheck" value="1" <?= (!empty($destination['is_shivaji_fort'])) ? 'checked' : '' ?>>
                    <label class="form-check-label fw-bold" for="isShivajiFortCheck">
                        <i class="fa-solid fa-crown text-warning me-1"></i>Is Fort of Chhatrapati Shivaji Maharaj?
                    </label>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-check form-switch mt-2">
                    <input class="form-check-input" type="checkbox" name="is_featured" id="isFeaturedCheck" value="1" <?= (!empty($destination['is_featured'])) ? 'checked' : '' ?>>
                    <label class="form-check-label fw-bold" for="isFeaturedCheck">
                        <i class="fa-solid fa-star text-warning me-1"></i>Feature on Homepage?
                    </label>
                </div>
            </div>
        </div>

        <!-- Descriptions -->
        <h6 class="fw-bold font-heritage text-primary mb-3">2. Overview & Historical Descriptions</h6>
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <label class="form-label small fw-bold">Short Description (English)</label>
                <textarea name="short_description" rows="2" class="form-control" required><?= e($destination['short_description'] ?? '') ?></textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label small fw-bold">Short Description (Marathi)</label>
                <textarea name="short_description_mr" rows="2" class="form-control font-marathi" required><?= e($destination['short_description_mr'] ?? '') ?></textarea>
            </div>
            <div class="col-12">
                <label class="form-label small fw-bold">Comprehensive Overview & Introduction</label>
                <textarea name="overview" rows="4" class="form-control" required><?= e($destination['overview'] ?? '') ?></textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label small fw-bold">Historical Background & Significance (English)</label>
                <textarea name="historical_significance" rows="4" class="form-control"><?= e($destination['historical_significance'] ?? '') ?></textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label small fw-bold">Historical Significance (Marathi)</label>
                <textarea name="historical_significance_mr" rows="4" class="form-control font-marathi"><?= e($destination['historical_significance_mr'] ?? '') ?></textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label small fw-bold">Connection with Chhatrapati Shivaji Maharaj (English)</label>
                <textarea name="shivaji_connection" rows="3" class="form-control"><?= e($destination['shivaji_connection'] ?? '') ?></textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label small fw-bold">Shivaji Maharaj Connection (Marathi)</label>
                <textarea name="shivaji_connection_mr" rows="3" class="form-control font-marathi"><?= e($destination['shivaji_connection_mr'] ?? '') ?></textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label small fw-bold">Architecture & Fortifications</label>
                <textarea name="architecture_notes" rows="3" class="form-control"><?= e($destination['architecture_notes'] ?? '') ?></textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label small fw-bold">Major Attractions Inside</label>
                <textarea name="major_attractions" rows="3" class="form-control"><?= e($destination['major_attractions'] ?? '') ?></textarea>
            </div>
        </div>

        <!-- Geography, Timings & Access -->
        <h6 class="fw-bold font-heritage text-primary mb-3">3. Geographic Coordinates, Timings & Access</h6>
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <label class="form-label small fw-bold">Latitude (Real Decimal GPS) *</label>
                <input type="number" step="any" name="latitude" class="form-control" value="<?= $destination['latitude'] ?? '18.234676' ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold">Longitude (Real Decimal GPS) *</label>
                <input type="number" step="any" name="longitude" class="form-control" value="<?= $destination['longitude'] ?? '73.441369' ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold">Altitude (Meters)</label>
                <input type="number" name="altitude_meters" class="form-control" value="<?= $destination['altitude_meters'] ?? '820' ?>">
            </div>
            <div class="col-12">
                <label class="form-label small fw-bold">Full Address / Location</label>
                <input type="text" name="address" class="form-control" value="<?= e($destination['address'] ?? '') ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold">Best Season</label>
                <input type="text" name="best_season" class="form-control" value="<?= e($destination['best_season'] ?? 'October to March') ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold">Opening Hours</label>
                <input type="text" name="opening_hours" class="form-control" value="<?= e($destination['opening_hours'] ?? '06:00 AM - 06:00 PM') ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold">Entry Fees</label>
                <input type="text" name="entry_fees" class="form-control" value="<?= e($destination['entry_fees'] ?? 'Free entry') ?>">
            </div>
            <div class="col-12">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_timing_verified" id="timingVerified" value="1" <?= (!isset($destination['is_timing_verified']) || $destination['is_timing_verified']) ? 'checked' : '' ?>>
                    <label class="form-check-label small" for="timingVerified">
                        Timings & fees are officially verified by administrative or ASI source
                    </label>
                </div>
            </div>
        </div>

        <!-- Trekking & Logistics -->
        <h6 class="fw-bold font-heritage text-primary mb-3">4. Trekking & Travel Logistics</h6>
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <label class="form-label small fw-bold">Difficulty Level</label>
                <select name="difficulty_level" class="form-select">
                    <option value="Easy" <?= ($destination['difficulty_level'] ?? '') === 'Easy' ? 'selected' : '' ?>>Easy</option>
                    <option value="Moderate" <?= ($destination['difficulty_level'] ?? 'Moderate') === 'Moderate' ? 'selected' : '' ?>>Moderate</option>
                    <option value="Challenging" <?= ($destination['difficulty_level'] ?? '') === 'Challenging' ? 'selected' : '' ?>>Challenging</option>
                    <option value="Difficult" <?= ($destination['difficulty_level'] ?? '') === 'Difficult' ? 'selected' : '' ?>>Difficult</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold">Trek Duration (Hours)</label>
                <input type="number" step="0.1" name="trek_duration_hours" class="form-control" value="<?= $destination['trek_duration_hours'] ?? '2.5' ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold">Distance from Pune (KM)</label>
                <input type="number" name="distance_from_pune_km" class="form-control" value="<?= $destination['distance_from_pune_km'] ?? '' ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold">Distance from Mumbai (KM)</label>
                <input type="number" name="distance_from_mumbai_km" class="form-control" value="<?= $destination['distance_from_mumbai_km'] ?? '' ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold">How to Reach (Road)</label>
                <textarea name="how_to_reach_road" rows="2" class="form-control"><?= e($destination['how_to_reach_road'] ?? '') ?></textarea>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold">How to Reach (Rail)</label>
                <textarea name="how_to_reach_rail" rows="2" class="form-control"><?= e($destination['how_to_reach_rail'] ?? '') ?></textarea>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold">How to Reach (Air)</label>
                <textarea name="how_to_reach_air" rows="2" class="form-control"><?= e($destination['how_to_reach_air'] ?? '') ?></textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label small fw-bold">Facilities Available</label>
                <input type="text" name="facilities" class="form-control" placeholder="e.g. Parking, Tea Stalls, Local Guides" value="<?= e($destination['facilities'] ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label small fw-bold">Safety Tips & Guidelines</label>
                <input type="text" name="safety_tips" class="form-control" placeholder="e.g. Wear high ankle boots, carry 2L water" value="<?= e($destination['safety_tips'] ?? '') ?>">
            </div>
        </div>

        <!-- Primary Photograph -->
        <h6 class="fw-bold font-heritage text-primary mb-3">5. Destination Photography</h6>
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <label class="form-label small fw-bold">Image Web URL</label>
                <input type="url" name="primary_image_url" class="form-control" placeholder="https://..." value="<?= e($destination['primary_image'] ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label small fw-bold">Or Upload New Image File</label>
                <input type="file" name="primary_image_file" class="form-control" accept="image/jpeg,image/png,image/webp">
            </div>
            <?php if (!empty($destination['primary_image'])): ?>
                <div class="col-12">
                    <span class="small text-muted d-block mb-1">Current Preview:</span>
                    <img src="<?= e($destination['primary_image']) ?>" alt="Preview" class="rounded-3 shadow-sm border" style="max-height: 180px; object-fit: cover;">
                </div>
            <?php endif; ?>
        </div>

        <div class="d-flex gap-3 pt-3 border-top">
            <button type="submit" class="btn btn-fe-primary py-2 px-4 fs-6">
                <i class="fa-solid fa-floppy-disk me-2"></i><?= $destId ? 'Update Destination' : 'Save Destination' ?>
            </button>
            <a href="destinations.php" class="btn btn-outline-secondary py-2 px-4">Cancel</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
