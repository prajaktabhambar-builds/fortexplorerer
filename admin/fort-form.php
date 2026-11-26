<?php
/**
 * FORT EXPLORER - Admin Add / Edit Maharashtra Fort Record
 * Manages comprehensive fort metadata and synchronizes with destinations directory.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$db = getDB();
$fortId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$fort = null;
$references = [];
$adminTitle = $fortId ? 'Edit Fort Record' : 'Add New Fort';

$categories = $db->query("SELECT * FROM fort_categories ORDER BY id ASC")->fetchAll();
$districts = $db->query("SELECT * FROM districts ORDER BY name_en ASC")->fetchAll();

if ($fortId) {
    $stmt = $db->prepare("SELECT * FROM forts WHERE id = ?");
    $stmt->execute([$fortId]);
    $fort = $stmt->fetch();
    if (!$fort) {
        set_flash('danger', 'Fort record not found.');
        header("Location: forts.php");
        exit;
    }

    $refStmt = $db->prepare("SELECT * FROM fort_references WHERE fort_id = ? ORDER BY id ASC");
    $refStmt->execute([$fortId]);
    $references = $refStmt->fetchAll();
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $name = sanitize_input($_POST['name'] ?? '');
        $name_mr = sanitize_input($_POST['name_mr'] ?? '');
        $alternate_names = sanitize_input($_POST['alternate_names'] ?? '');
        $slug = sanitize_input($_POST['slug'] ?? '');
        if (empty($slug)) {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
        }

        $district_id = (int)$_POST['district_id'];
        $taluka = sanitize_input($_POST['taluka'] ?? '');
        $village = sanitize_input($_POST['village'] ?? '');
        $category_id = (int)$_POST['category_id'];
        $sub_category = sanitize_input($_POST['sub_category'] ?? '');

        $latitude = !empty($_POST['latitude']) ? (float)$_POST['latitude'] : null;
        $longitude = !empty($_POST['longitude']) ? (float)$_POST['longitude'] : null;
        $altitude_meters = !empty($_POST['altitude_meters']) ? (int)$_POST['altitude_meters'] : null;

        $historical_period = sanitize_input($_POST['historical_period'] ?? '');
        $dynasties_rulers = sanitize_input($_POST['dynasties_rulers'] ?? '');
        $historical_background = $_POST['historical_background'] ?? '';
        $historical_background_mr = $_POST['historical_background_mr'] ?? '';

        $is_shivaji_fort = isset($_POST['is_shivaji_fort']) ? 1 : 0;
        $shivaji_connection = $_POST['shivaji_connection'] ?? '';
        $shivaji_connection_mr = $_POST['shivaji_connection_mr'] ?? '';

        $is_unesco_site = isset($_POST['is_unesco_site']) ? 1 : 0;
        $unesco_details = $_POST['unesco_inscription_details'] ?? '';

        $architecture_type = sanitize_input($_POST['architecture_type'] ?? '');
        $architecture_notes = $_POST['architecture_notes'] ?? '';
        $key_structures = $_POST['key_structures'] ?? '';
        $water_sources = $_POST['water_sources'] ?? '';

        $trekking_difficulty = sanitize_input($_POST['trekking_difficulty'] ?? 'Moderate');
        $trek_duration = !empty($_POST['trek_duration_hours']) ? (float)$_POST['trek_duration_hours'] : 2.5;
        $best_season = sanitize_input($_POST['best_season'] ?? 'October to February');

        $how_to_reach = sanitize_input($_POST['how_to_reach'] ?? '');
        $nearest_railway = sanitize_input($_POST['nearest_railway'] ?? '');
        $nearest_airport = sanitize_input($_POST['nearest_airport'] ?? '');
        $visitor_facilities = sanitize_input($_POST['visitor_facilities'] ?? '');
        $safety_guidelines = sanitize_input($_POST['safety_guidelines'] ?? '');

        $verification_status = sanitize_input($_POST['verification_status'] ?? 'verified');
        $verified_by = sanitize_input($_POST['verified_by'] ?? 'Heritage Research Archive');
        $reference_sources = sanitize_input($_POST['reference_sources'] ?? '');

        // Image Handling
        $primary_image = $fort['primary_image'] ?? 'assets/images/default-fort.jpg';
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
            $db->beginTransaction();

            if ($fortId) {
                // UPDATE FORT
                $upd = $db->prepare("
                    UPDATE forts SET
                        name = ?, name_mr = ?, alternate_names = ?, slug = ?,
                        district_id = ?, taluka = ?, village = ?,
                        latitude = ?, longitude = ?, altitude_meters = ?,
                        category_id = ?, sub_category = ?,
                        historical_period = ?, dynasties_rulers = ?,
                        historical_background = ?, historical_background_mr = ?,
                        is_shivaji_fort = ?, shivaji_connection = ?, shivaji_connection_mr = ?,
                        is_unesco_site = ?, unesco_inscription_details = ?,
                        architecture_type = ?, architecture_notes = ?,
                        key_structures = ?, water_sources = ?,
                        trekking_difficulty = ?, trek_duration_hours = ?, best_season = ?,
                        how_to_reach = ?, nearest_railway = ?, nearest_airport = ?,
                        visitor_facilities = ?, safety_guidelines = ?,
                        primary_image = ?, verification_status = ?, verified_by = ?,
                        reference_sources = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $upd->execute([
                    $name, $name_mr, $alternate_names, $slug,
                    $district_id, $taluka, $village,
                    $latitude, $longitude, $altitude_meters,
                    $category_id, $sub_category,
                    $historical_period, $dynasties_rulers,
                    $historical_background, $historical_background_mr,
                    $is_shivaji_fort, $shivaji_connection, $shivaji_connection_mr,
                    $is_unesco_site, $unesco_details,
                    $architecture_type, $architecture_notes,
                    $key_structures, $water_sources,
                    $trekking_difficulty, $trek_duration, $best_season,
                    $how_to_reach, $nearest_railway, $nearest_airport,
                    $visitor_facilities, $safety_guidelines,
                    $primary_image, $verification_status, $verified_by,
                    $reference_sources, $fortId
                ]);

                // Synchronize destination table
                $destId = $fort['destination_id'];
                if ($destId) {
                    $destUpd = $db->prepare("
                        UPDATE destinations SET
                            district_id = ?, name = ?, name_mr = ?, slug = ?,
                            short_description = ?, short_description_mr = ?,
                            overview = ?, historical_significance = ?, historical_significance_mr = ?,
                            shivaji_connection = ?, shivaji_connection_mr = ?,
                            architecture_notes = ?, major_attractions = ?,
                            latitude = ?, longitude = ?, altitude_meters = ?,
                            best_season = ?, how_to_reach_road = ?, how_to_reach_rail = ?,
                            facilities = ?, safety_tips = ?, difficulty_level = ?, trek_duration_hours = ?,
                            primary_image = ?, is_shivaji_fort = ?, is_unesco_site = ?,
                            taluka = ?, village = ?, alternate_names = ?, historical_period = ?,
                            dynasties_rulers = ?, fort_category_id = ?, verification_status = ?,
                            reference_sources = ?
                        WHERE id = ?
                    ");
                    $destUpd->execute([
                        $district_id, $name, $name_mr, $slug,
                        mb_substr(strip_tags($historical_background), 0, 240) . '...',
                        mb_substr(strip_tags($historical_background_mr), 0, 240) . '...',
                        $historical_background, $historical_background, $historical_background_mr,
                        $shivaji_connection, $shivaji_connection_mr,
                        $architecture_notes, $key_structures,
                        $latitude, $longitude, $altitude_meters,
                        $best_season, $how_to_reach, $nearest_railway,
                        $visitor_facilities, $safety_guidelines, $trekking_difficulty, $trek_duration,
                        $primary_image, $is_shivaji_fort, $is_unesco_site,
                        $taluka, $village, $alternate_names, $historical_period,
                        $dynasties_rulers, $category_id, $verification_status,
                        $reference_sources, $destId
                    ]);
                }

                set_flash('success', "Fort '{$name}' updated successfully.");

            } else {
                // INSERT NEW FORT
                $ins = $db->prepare("
                    INSERT INTO forts (
                        name, name_mr, alternate_names, slug,
                        district_id, taluka, village,
                        latitude, longitude, altitude_meters,
                        category_id, sub_category,
                        historical_period, dynasties_rulers,
                        historical_background, historical_background_mr,
                        is_shivaji_fort, shivaji_connection, shivaji_connection_mr,
                        is_unesco_site, unesco_inscription_details,
                        architecture_type, architecture_notes,
                        key_structures, water_sources,
                        trekking_difficulty, trek_duration_hours, best_season,
                        how_to_reach, nearest_railway, nearest_airport,
                        visitor_facilities, safety_guidelines,
                        primary_image, verification_status, verified_by,
                        reference_sources
                    ) VALUES (
                        ?, ?, ?, ?,
                        ?, ?, ?,
                        ?, ?, ?,
                        ?, ?,
                        ?, ?,
                        ?, ?,
                        ?, ?, ?,
                        ?, ?,
                        ?, ?,
                        ?, ?,
                        ?, ?, ?,
                        ?, ?, ?,
                        ?, ?,
                        ?, ?, ?,
                        ?
                    )
                ");
                $ins->execute([
                    $name, $name_mr, $alternate_names, $slug,
                    $district_id, $taluka, $village,
                    $latitude, $longitude, $altitude_meters,
                    $category_id, $sub_category,
                    $historical_period, $dynasties_rulers,
                    $historical_background, $historical_background_mr,
                    $is_shivaji_fort, $shivaji_connection, $shivaji_connection_mr,
                    $is_unesco_site, $unesco_details,
                    $architecture_type, $architecture_notes,
                    $key_structures, $water_sources,
                    $trekking_difficulty, $trek_duration, $best_season,
                    $how_to_reach, $nearest_railway, $nearest_airport,
                    $visitor_facilities, $safety_guidelines,
                    $primary_image, $verification_status, $verified_by,
                    $reference_sources
                ]);
                $fortId = (int)$db->lastInsertId();

                // Create matching destination record
                $destIns = $db->prepare("
                    INSERT INTO destinations (
                        category_id, district_id, name, name_mr, slug,
                        short_description, short_description_mr, overview,
                        historical_significance, historical_significance_mr,
                        shivaji_connection, shivaji_connection_mr,
                        architecture_notes, major_attractions,
                        latitude, longitude, altitude_meters, address,
                        best_season, how_to_reach_road, how_to_reach_rail,
                        facilities, safety_tips, difficulty_level, trek_duration_hours,
                        primary_image, is_featured, is_shivaji_fort, is_unesco_site,
                        taluka, village, alternate_names, historical_period,
                        dynasties_rulers, fort_category_id, verification_status,
                        reference_sources
                    ) VALUES (
                        1, ?, ?, ?, ?,
                        ?, ?, ?,
                        ?, ?,
                        ?, ?,
                        ?, ?,
                        ?, ?, ?, ?,
                        ?, ?, ?,
                        ?, ?, ?, ?,
                        ?, 0, ?, ?,
                        ?, ?, ?, ?,
                        ?, ?, ?,
                        ?
                    )
                ");
                $destIns->execute([
                    $district_id, $name, $name_mr, $slug,
                    mb_substr(strip_tags($historical_background), 0, 240) . '...',
                    mb_substr(strip_tags($historical_background_mr), 0, 240) . '...',
                    $historical_background,
                    $historical_background, $historical_background_mr,
                    $shivaji_connection, $shivaji_connection_mr,
                    $architecture_notes, $key_structures,
                    $latitude, $longitude, $altitude_meters, "{$taluka}, District {$districts[0]['name_en']}, Maharashtra",
                    $best_season, $how_to_reach, $nearest_railway,
                    $visitor_facilities, $safety_guidelines, $trekking_difficulty, $trek_duration,
                    $primary_image, $is_shivaji_fort, $is_unesco_site,
                    $taluka, $village, $alternate_names, $historical_period,
                    $dynasties_rulers, $category_id, $verification_status,
                    $reference_sources
                ]);
                $newDestId = (int)$db->lastInsertId();

                $db->query("UPDATE forts SET destination_id = {$newDestId} WHERE id = {$fortId}");

                set_flash('success', "New fort '{$name}' created and added to directory.");
            }

            // Optional new reference citation
            $refTitle = trim($_POST['ref_title'] ?? '');
            if (!empty($refTitle)) {
                $refType = $_POST['ref_type'] ?? 'state_gazetteer';
                $refAuthor = sanitize_input($_POST['ref_author'] ?? '');
                $refVolume = sanitize_input($_POST['ref_volume'] ?? '');
                $refUrl = sanitize_input($_POST['ref_url'] ?? '');

                $refIns = $db->prepare("
                    INSERT INTO fort_references (fort_id, source_title, source_type, author_or_body, volume_or_page, source_url, verification_date)
                    VALUES (?, ?, ?, ?, ?, ?, CURDATE())
                ");
                $refIns->execute([$fortId, $refTitle, $refType, $refAuthor, $refVolume, $refUrl]);
            }

            $db->commit();
            header("Location: forts.php");
            exit;

        } catch (Exception $e) {
            $db->rollBack();
            $error = $e->getMessage();
        }
    } else {
        $error = 'Invalid security token. Please resubmit.';
    }
}

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold font-heritage mb-1 text-dark"><?= $fort ? 'Edit Fort: ' . e($fort['name']) : 'Add New Maharashtra Fort' ?></h4>
        <p class="text-muted small mb-0">Fill in all verified historical, geographical, and architectural information.</p>
    </div>
    <div class="d-flex gap-2">
        <?php if ($fort): ?>
            <a href="../destination.php?slug=<?= urlencode($fort['slug']) ?>" target="_blank" class="btn btn-sm btn-outline-info">
                <i class="fa-solid fa-eye me-1"></i>View Live
            </a>
        <?php endif; ?>
        <a href="forts.php" class="btn btn-sm btn-outline-secondary">
            <i class="fa-solid fa-arrow-left me-1"></i>Back to Forts
        </a>
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger shadow-sm"><?= e($error) ?></div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <div class="row g-4">
        <!-- Main Form Column -->
        <div class="col-lg-8">
            <!-- Basic Identifiers -->
            <div class="admin-card mb-4">
                <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">
                    <i class="fa-solid fa-heading me-2 text-warning"></i>1. Nomenclature & District
                </h6>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Fort Name (English) *</label>
                        <input type="text" name="name" class="form-control" required value="<?= e($fort['name'] ?? '') ?>" placeholder="e.g. Raigad Fort">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">किल्ल्याचे नाव (मराठी) *</label>
                        <input type="text" name="name_mr" class="form-control font-marathi" required value="<?= e($fort['name_mr'] ?? '') ?>" placeholder="उदा. रायगड किल्ला">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Alternate Names / Aliases</label>
                        <input type="text" name="alternate_names" class="form-control" value="<?= e($fort['alternate_names'] ?? '') ?>" placeholder="e.g. Rairi, Gibralter of the East">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">URL Slug</label>
                        <input type="text" name="slug" class="form-control" value="<?= e($fort['slug'] ?? '') ?>" placeholder="auto-generated-if-empty">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">District (All 36 Districts) *</label>
                        <select name="district_id" class="form-select" required>
                            <option value="">Select District</option>
                            <?php foreach ($districts as $d): ?>
                                <option value="<?= $d['id'] ?>" <?= ($fort['district_id'] ?? '') == $d['id'] ? 'selected' : '' ?>>
                                    <?= e($d['name_en']) ?> (<?= e($d['name_mr']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">Taluka</label>
                        <input type="text" name="taluka" class="form-control" value="<?= e($fort['taluka'] ?? '') ?>" placeholder="e.g. Mahad">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">Base Village / Foot</label>
                        <input type="text" name="village" class="form-control" value="<?= e($fort['village'] ?? '') ?>" placeholder="e.g. Pachad">
                    </div>
                </div>
            </div>

            <!-- Geography & Coordinates -->
            <div class="admin-card mb-4">
                <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">
                    <i class="fa-solid fa-map-location-dot me-2 text-warning"></i>2. Geographic Location & Coordinates
                </h6>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">Latitude (Decimal)</label>
                        <input type="number" step="0.0000001" name="latitude" class="form-control" value="<?= e($fort['latitude'] ?? '') ?>" placeholder="e.g. 18.2346000">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">Longitude (Decimal)</label>
                        <input type="number" step="0.0000001" name="longitude" class="form-control" value="<?= e($fort['longitude'] ?? '') ?>" placeholder="e.g. 73.4428000">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">Altitude (Meters above MSL)</label>
                        <input type="number" name="altitude_meters" class="form-control" value="<?= e($fort['altitude_meters'] ?? '') ?>" placeholder="e.g. 820">
                    </div>
                </div>
            </div>

            <!-- Historical Background -->
            <div class="admin-card mb-4">
                <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">
                    <i class="fa-solid fa-scroll me-2 text-warning"></i>3. Historical Background & Chronology
                </h6>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Historical Period</label>
                        <input type="text" name="historical_period" class="form-control" value="<?= e($fort['historical_period'] ?? '') ?>" placeholder="e.g. 12th - 17th Century">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Dynasties & Ruling Powers</label>
                        <input type="text" name="dynasties_rulers" class="form-control" value="<?= e($fort['dynasties_rulers'] ?? '') ?>" placeholder="e.g. Yadavas, Maratha Empire, British">
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold small">Historical Background (English)</label>
                        <textarea name="historical_background" class="form-control" rows="4"><?= e($fort['historical_background'] ?? '') ?></textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold small">ऐतिहासिक पार्श्वभूमी (मराठी)</label>
                        <textarea name="historical_background_mr" class="form-control font-marathi" rows="4"><?= e($fort['historical_background_mr'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Shivaji Maharaj Connection -->
            <div class="admin-card mb-4 border-start border-4 border-danger">
                <div class="d-flex align-items-center justify-content-between border-bottom pb-2 mb-3">
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="fa-solid fa-crown me-2 text-danger"></i>4. Chhatrapati Shivaji Maharaj Connection
                    </h6>
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" name="is_shivaji_fort" id="swShivaji" value="1" <?= !empty($fort['is_shivaji_fort']) ? 'checked' : '' ?>>
                        <label class="form-check-label fw-bold text-danger small" for="swShivaji">Shivaji Maharaj Fort</label>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label fw-semibold small">Shivaji Maharaj Connection & Battles (English)</label>
                        <textarea name="shivaji_connection" class="form-control" rows="3"><?= e($fort['shivaji_connection'] ?? '') ?></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold small">छत्रपती शिवाजी महाराज संबंध व लढाया (मराठी)</label>
                        <textarea name="shivaji_connection_mr" class="form-control font-marathi" rows="3"><?= e($fort['shivaji_connection_mr'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Architecture & Engineering -->
            <div class="admin-card mb-4">
                <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">
                    <i class="fa-solid fa-monument me-2 text-warning"></i>5. Fort Architecture & Key Structures
                </h6>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Architecture Type</label>
                        <input type="text" name="architecture_type" class="form-control" value="<?= e($fort['architecture_type'] ?? '') ?>" placeholder="e.g. Sahyadri Basalt Stone Fortification">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Water Harvesting Sources</label>
                        <input type="text" name="water_sources" class="form-control" value="<?= e($fort['water_sources'] ?? '') ?>" placeholder="e.g. Rock-cut cisterns (Taake), Ganga Sagar lake">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold small">Key Bastions, Gates & Monuments</label>
                        <textarea name="key_structures" class="form-control" rows="3" placeholder="e.g. Maha Darwaja, Nagarkhana, Takmak Tok, Jagdishwar Temple"><?= e($fort['key_structures'] ?? '') ?></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold small">Architectural Engineering Notes</label>
                        <textarea name="architecture_notes" class="form-control" rows="3"><?= e($fort['architecture_notes'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Travel & Trekking Info -->
            <div class="admin-card mb-4">
                <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">
                    <i class="fa-solid fa-person-hiking me-2 text-warning"></i>6. Trekking & Travel Guide
                </h6>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">Trekking Difficulty</label>
                        <select name="trekking_difficulty" class="form-select">
                            <?php foreach (['Easy', 'Moderate', 'Challenging', 'Difficult'] as $diff): ?>
                                <option value="<?= $diff ?>" <?= ($fort['trekking_difficulty'] ?? 'Moderate') === $diff ? 'selected' : '' ?>><?= $diff ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">Trek Duration (Hours)</label>
                        <input type="number" step="0.5" name="trek_duration_hours" class="form-control" value="<?= e($fort['trek_duration_hours'] ?? '2.5') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">Best Season to Visit</label>
                        <input type="text" name="best_season" class="form-control" value="<?= e($fort['best_season'] ?? 'October to February') ?>">
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold small">How to Reach (Road & Trek Route)</label>
                        <textarea name="how_to_reach" class="form-control" rows="2"><?= e($fort['how_to_reach'] ?? '') ?></textarea>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Nearest Railway Station</label>
                        <input type="text" name="nearest_railway" class="form-control" value="<?= e($fort['nearest_railway'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Nearest Airport</label>
                        <input type="text" name="nearest_airport" class="form-control" value="<?= e($fort['nearest_airport'] ?? '') ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Visitor Facilities</label>
                        <input type="text" name="visitor_facilities" class="form-control" value="<?= e($fort['visitor_facilities'] ?? '') ?>" placeholder="e.g. Village homestays, drinking water tanks">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Safety & Trekking Advice</label>
                        <input type="text" name="safety_guidelines" class="form-control" value="<?= e($fort['safety_guidelines'] ?? '') ?>" placeholder="e.g. Slippery rock patches during monsoon">
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar Meta Column -->
        <div class="col-lg-4">
            <!-- Classification & Category -->
            <div class="admin-card mb-4">
                <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">
                    <i class="fa-solid fa-tags me-2 text-warning"></i>Fort Classification
                </h6>

                <div class="mb-3">
                    <label class="form-label fw-semibold small">Traditional Classification *</label>
                    <select name="category_id" class="form-select" required>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= ($fort['category_id'] ?? 1) == $cat['id'] ? 'selected' : '' ?>>
                                <?= e($cat['traditional_name']) ?> (<?= e($cat['name_en']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold small">Sub-Category</label>
                    <input type="text" name="sub_category" class="form-control" value="<?= e($fort['sub_category'] ?? '') ?>" placeholder="e.g. Mountain Pass Watchtower">
                </div>

                <!-- UNESCO Serial Nomination -->
                <div class="p-3 bg-warning-subtle rounded-3 border border-warning mb-2">
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="is_unesco_site" id="swUnesco" value="1" <?= !empty($fort['is_unesco_site']) ? 'checked' : '' ?>>
                        <label class="form-check-label fw-bold text-dark small" for="swUnesco">
                            <i class="fa-solid fa-crown text-warning me-1"></i>UNESCO Nominated Component
                        </label>
                    </div>
                    <label class="form-label fw-semibold small mb-1">Nomination Details</label>
                    <textarea name="unesco_inscription_details" class="form-control form-control-sm" rows="2" placeholder="e.g. Serial nomination: Maratha Military Landscapes of India"><?= e($fort['unesco_inscription_details'] ?? '') ?></textarea>
                </div>
            </div>

            <!-- Verification & Archival Status -->
            <div class="admin-card mb-4">
                <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">
                    <i class="fa-solid fa-certificate me-2 text-success"></i>Verification & Sources
                </h6>

                <div class="mb-3">
                    <label class="form-label fw-semibold small">Verification Status</label>
                    <select name="verification_status" class="form-select">
                        <option value="verified" <?= ($fort['verification_status'] ?? 'verified') === 'verified' ? 'selected' : '' ?>>Verified</option>
                        <option value="pending_verification" <?= ($fort['verification_status'] ?? '') === 'pending_verification' ? 'selected' : '' ?>>Pending Verification</option>
                        <option value="needs_correction" <?= ($fort['verification_status'] ?? '') === 'needs_correction' ? 'selected' : '' ?>>Needs Correction</option>
                        <option value="unverified" <?= ($fort['verification_status'] ?? '') === 'unverified' ? 'selected' : '' ?>>Unverified</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold small">Verified By Authority</label>
                    <input type="text" name="verified_by" class="form-control" value="<?= e($fort['verified_by'] ?? 'Heritage Research Archive') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold small">Reference Sources Summary</label>
                    <textarea name="reference_sources" class="form-control" rows="2" placeholder="e.g. ASI Survey; Maharashtra State Gazetteers"><?= e($fort['reference_sources'] ?? '') ?></textarea>
                </div>

                <!-- Add Reference Citation -->
                <div class="p-3 bg-light rounded-3 border">
                    <div class="fw-bold small text-dark mb-2">
                        <i class="fa-solid fa-book-bookmark me-1 text-primary"></i>Add Reference Citation
                    </div>
                    <div class="mb-2">
                        <input type="text" name="ref_title" class="form-control form-control-sm" placeholder="Source Title (e.g. Bombay Gazetteer Vol. XI)">
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <select name="ref_type" class="form-select form-select-sm">
                                <option value="state_gazetteer">State Gazetteer</option>
                                <option value="asi_record">ASI Record</option>
                                <option value="unesco_dossier">UNESCO Dossier</option>
                                <option value="historical_chronicle">Historical Chronicle</option>
                                <option value="academic_paper">Academic Paper</option>
                                <option value="mtdc_archive">MTDC Archive</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <input type="text" name="ref_author" class="form-control form-control-sm" placeholder="Author / Body">
                        </div>
                    </div>
                    <div class="mb-2">
                        <input type="text" name="ref_volume" class="form-control form-control-sm" placeholder="Volume / Page No.">
                    </div>
                    <input type="url" name="ref_url" class="form-control form-control-sm" placeholder="Digital URL (optional)">
                </div>
            </div>

            <!-- Primary Image -->
            <div class="admin-card mb-4">
                <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">
                    <i class="fa-solid fa-image me-2 text-warning"></i>Primary Photograph
                </h6>

                <?php if (!empty($fort['primary_image'])): ?>
                    <div class="mb-3 text-center">
                        <img src="<?= e($fort['primary_image']) ?>" alt="Preview" class="img-fluid rounded-3 shadow-sm" style="max-height: 180px; width: 100%; object-fit: cover;">
                    </div>
                <?php endif; ?>

                <div class="mb-3">
                    <label class="form-label fw-semibold small">Image Web URL</label>
                    <input type="url" name="primary_image_url" class="form-control form-control-sm" value="<?= e($fort['primary_image'] ?? '') ?>" placeholder="https://...">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold small">Or Upload Image File</label>
                    <input type="file" name="primary_image_file" class="form-control form-control-sm" accept="image/*">
                </div>
            </div>

            <!-- Submit Button -->
            <div class="admin-card">
                <button type="submit" class="btn btn-fe-primary w-100 py-2 fw-bold">
                    <i class="fa-solid fa-floppy-disk me-2"></i><?= $fort ? 'Update Fort Record' : 'Save & Publish Fort' ?>
                </button>
            </div>
        </div>
    </div>
</form>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
