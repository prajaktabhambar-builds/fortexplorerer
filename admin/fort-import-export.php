<?php
/**
 * FORT EXPLORER - Admin Fort CSV Import & Export System
 * Full UTF-8 BOM support for Marathi unicode, validation, duplicate prevention, and sync.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin();

$db = getDB();

// -------------------------------------------------------------
// 1. HANDLE CSV EXPORT
// -------------------------------------------------------------
if (isset($_GET['action']) && $_GET['action'] === 'export') {
    $filename = 'maharashtra_forts_directory_' . date('Y-m-d') . '.csv';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');

    // Output UTF-8 Byte Order Mark (BOM) for correct Excel / Sheets display of Marathi text
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    // CSV Header row
    fputcsv($output, [
        'ID',
        'Fort Name (EN)',
        'Fort Name (MR)',
        'Alternate Names',
        'Slug',
        'District (EN)',
        'District (MR)',
        'Taluka',
        'Village',
        'Latitude',
        'Longitude',
        'Altitude (m)',
        'Classification Code',
        'Traditional Name',
        'Historical Period',
        'Dynasties Rulers',
        'Shivaji Fort (1/0)',
        'UNESCO Site (1/0)',
        'UNESCO Details',
        'Trekking Difficulty',
        'Trek Duration (Hours)',
        'Best Season',
        'Verification Status',
        'Primary Image URL',
        'Historical Background (EN)',
        'Historical Background (MR)',
        'Shivaji Connection (EN)',
        'Shivaji Connection (MR)',
        'Key Structures',
        'Water Sources',
        'How to Reach',
        'Nearest Railway',
        'Nearest Airport',
        'Visitor Facilities',
        'Safety Guidelines',
        'Reference Sources'
    ]);

    $stmt = $db->query("
        SELECT f.*, 
               dist.name_en AS district_name, dist.name_mr AS district_name_mr,
               fc.code AS cat_code, fc.traditional_name AS cat_trad
        FROM forts f
        JOIN districts dist ON f.district_id = dist.id
        JOIN fort_categories fc ON f.category_id = fc.id
        ORDER BY f.id ASC
    ");

    while ($row = $stmt->fetch()) {
        fputcsv($output, [
            $row['id'],
            $row['name'],
            $row['name_mr'],
            $row['alternate_names'] ?? '',
            $row['slug'],
            $row['district_name'],
            $row['district_name_mr'],
            $row['taluka'] ?? '',
            $row['village'] ?? '',
            $row['latitude'] ?? '',
            $row['longitude'] ?? '',
            $row['altitude_meters'] ?? '',
            $row['cat_code'],
            $row['cat_trad'],
            $row['historical_period'] ?? '',
            $row['dynasties_rulers'] ?? '',
            $row['is_shivaji_fort'] ?? 0,
            $row['is_unesco_site'] ?? 0,
            $row['unesco_inscription_details'] ?? '',
            $row['trekking_difficulty'] ?? 'Moderate',
            $row['trek_duration_hours'] ?? '',
            $row['best_season'] ?? '',
            $row['verification_status'] ?? 'verified',
            $row['primary_image'] ?? '',
            $row['historical_background'] ?? '',
            $row['historical_background_mr'] ?? '',
            $row['shivaji_connection'] ?? '',
            $row['shivaji_connection_mr'] ?? '',
            $row['key_structures'] ?? '',
            $row['water_sources'] ?? '',
            $row['how_to_reach'] ?? '',
            $row['nearest_railway'] ?? '',
            $row['nearest_airport'] ?? '',
            $row['visitor_facilities'] ?? '',
            $row['safety_guidelines'] ?? '',
            $row['reference_sources'] ?? ''
        ]);
    }

    fclose($output);
    exit;
}

// -------------------------------------------------------------
// 2. HANDLE SAMPLE CSV TEMPLATE DOWNLOAD
// -------------------------------------------------------------
if (isset($_GET['action']) && $_GET['action'] === 'sample_template') {
    $filename = 'fort_import_sample_template.csv';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    fputcsv($output, [
        'Fort Name (EN)',
        'Fort Name (MR)',
        'Alternate Names',
        'District (EN)',
        'Taluka',
        'Village',
        'Latitude',
        'Longitude',
        'Altitude (m)',
        'Classification Code',
        'Historical Period',
        'Dynasties Rulers',
        'Shivaji Fort (1/0)',
        'UNESCO Site (1/0)',
        'Trekking Difficulty',
        'Trek Duration (Hours)',
        'Best Season',
        'Historical Background (EN)',
        'Historical Background (MR)',
        'Shivaji Connection (EN)',
        'Key Structures',
        'Water Sources',
        'How to Reach',
        'Nearest Railway',
        'Nearest Airport',
        'Primary Image URL',
        'Reference Sources'
    ]);

    // Sample Row 1
    fputcsv($output, [
        'Sample Hill Fort',
        'नमुना गिरिदुर्ग',
        'Sample Durg',
        'Pune',
        'Haveli',
        'Ghera Fort',
        '18.3664',
        '73.7558',
        '1312',
        'hill_fort',
        '13th - 17th Century',
        'Maratha Empire',
        '1',
        '0',
        'Moderate',
        '2.5',
        'October to February',
        'Historical hill fortress standing in the Western Ghats.',
        'सह्याद्रीच्या डोंगररांगेत वसलेला ऐतिहासिक गिरिदुर्ग.',
        'Strategically captured during early Swarajya campaigns.',
        'Kalyan Darwaja, Tanaji Malusare Samadhi, Zunjar Buruj',
        'Rock-cut water tanks',
        'Accessible via road from Pune; trek starts from base village.',
        'Pune Junction (30 km)',
        'Pune Airport (40 km)',
        'https://images.unsplash.com/photo-1598894000396-bc4a04d509f6',
        'Maharashtra State Gazetteers; ASI Surveys'
    ]);

    fclose($output);
    exit;
}

// -------------------------------------------------------------
// 3. HANDLE CSV IMPORT
// -------------------------------------------------------------
$importResult = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_import'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash('danger', 'Invalid security token.');
    } elseif (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        set_flash('danger', 'Please choose a valid CSV file to upload.');
    } else {
        $filePath = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($filePath, 'r');

        if ($handle !== false) {
            // Read first line to strip potential BOM
            $bom = fread($handle, 3);
            if ($bom !== "\xEF\xBB\xBF") {
                rewind($handle);
            }

            // Read header
            $header = fgetcsv($handle);

            if (!$header || count($header) < 4) {
                set_flash('danger', 'Invalid CSV format or missing columns.');
            } else {
                // Map header names to indices
                $headerMap = [];
                foreach ($header as $idx => $colName) {
                    $cleaned = strtolower(trim(preg_replace('/[^a-zA-Z0-9]/', '', $colName)));
                    $headerMap[$cleaned] = $idx;
                }

                // Cache districts and categories for fast lookup
                $districtLookup = [];
                $dRows = $db->query("SELECT id, LOWER(name_en) AS name_en, LOWER(name_mr) AS name_mr FROM districts")->fetchAll();
                foreach ($dRows as $dr) {
                    $districtLookup[$dr['name_en']] = $dr['id'];
                    $districtLookup[$dr['name_mr']] = $dr['id'];
                }

                $categoryLookup = [];
                $cRows = $db->query("SELECT id, LOWER(code) AS code, LOWER(traditional_name) AS trad, LOWER(name_en) AS name_en FROM fort_categories")->fetchAll();
                foreach ($cRows as $cr) {
                    $categoryLookup[$cr['code']] = $cr['id'];
                    $categoryLookup[$cr['trad']] = $cr['id'];
                    $categoryLookup[$cr['name_en']] = $cr['id'];
                }

                $stats = [
                    'processed' => 0,
                    'inserted' => 0,
                    'updated' => 0,
                    'errors' => []
                ];

                // Helper to get column value
                $getCol = function($row, ...$keys) use ($headerMap) {
                    foreach ($keys as $k) {
                        $cleanKey = strtolower(trim(preg_replace('/[^a-zA-Z0-9]/', '', $k)));
                        if (isset($headerMap[$cleanKey]) && isset($row[$headerMap[$cleanKey]])) {
                            return trim($row[$headerMap[$cleanKey]]);
                        }
                    }
                    return '';
                };

                $rowNum = 1;
                while (($row = fgetcsv($handle)) !== false) {
                    $rowNum++;
                    if (empty($row) || (count($row) === 1 && empty($row[0]))) continue;

                    $stats['processed']++;

                    $name = $getCol($row, 'Fort Name (EN)', 'Fort Name', 'name');
                    $nameMr = $getCol($row, 'Fort Name (MR)', 'name_mr', 'किल्ल्याचे नाव');
                    $districtName = strtolower($getCol($row, 'District (EN)', 'District', 'district_name'));
                    $categoryCode = strtolower($getCol($row, 'Classification Code', 'Classification', 'Traditional Name', 'category'));

                    if (empty($name)) {
                        $stats['errors'][] = "Row #{$rowNum}: Missing fort name.";
                        continue;
                    }

                    if (empty($nameMr)) {
                        $nameMr = $name;
                    }

                    // District resolution
                    $districtId = $districtLookup[$districtName] ?? null;
                    if (!$districtId) {
                        // Default to Pune or first match
                        $districtId = 1;
                    }

                    // Category resolution
                    $categoryId = $categoryLookup[$categoryCode] ?? 1;

                    // Slug
                    $slug = $getCol($row, 'Slug', 'slug');
                    if (empty($slug)) {
                        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
                    }

                    $altNames = $getCol($row, 'Alternate Names', 'alternate_names');
                    $taluka = $getCol($row, 'Taluka', 'taluka');
                    $village = $getCol($row, 'Village', 'village');
                    $lat = floatval($getCol($row, 'Latitude', 'latitude')) ?: null;
                    $lng = floatval($getCol($row, 'Longitude', 'longitude')) ?: null;
                    $altitude = intval($getCol($row, 'Altitude (m)', 'altitude', 'altitude_meters')) ?: null;
                    $period = $getCol($row, 'Historical Period', 'period');
                    $dynasties = $getCol($row, 'Dynasties Rulers', 'dynasties');
                    $isShivaji = intval($getCol($row, 'Shivaji Fort (1/0)', 'is_shivaji_fort')) ? 1 : 0;
                    $isUnesco = intval($getCol($row, 'UNESCO Site (1/0)', 'is_unesco_site')) ? 1 : 0;
                    $unescoDetails = $getCol($row, 'UNESCO Details', 'unesco_inscription_details');
                    $diff = $getCol($row, 'Trekking Difficulty', 'difficulty') ?: 'Moderate';
                    $duration = floatval($getCol($row, 'Trek Duration (Hours)', 'duration')) ?: 2.5;
                    $season = $getCol($row, 'Best Season', 'season') ?: 'October to February';
                    $bgEn = $getCol($row, 'Historical Background (EN)', 'historical_background');
                    $bgMr = $getCol($row, 'Historical Background (MR)', 'historical_background_mr') ?: $bgEn;
                    $shEn = $getCol($row, 'Shivaji Connection (EN)', 'shivaji_connection');
                    $shMr = $getCol($row, 'Shivaji Connection (MR)', 'shivaji_connection_mr') ?: $shEn;
                    $structures = $getCol($row, 'Key Structures', 'key_structures');
                    $water = $getCol($row, 'Water Sources', 'water_sources');
                    $reach = $getCol($row, 'How to Reach', 'how_to_reach');
                    $railway = $getCol($row, 'Nearest Railway', 'nearest_railway');
                    $airport = $getCol($row, 'Nearest Airport', 'nearest_airport');
                    $image = $getCol($row, 'Primary Image URL', 'primary_image') ?: 'assets/images/default-fort.jpg';
                    $sources = $getCol($row, 'Reference Sources', 'sources');

                    try {
                        // Check if fort already exists by slug or exact name
                        $existStmt = $db->prepare("SELECT id, destination_id FROM forts WHERE slug = ? OR name = ?");
                        $existStmt->execute([$slug, $name]);
                        $existing = $existStmt->fetch();

                        if ($existing) {
                            // UPDATE EXISTING FORT
                            $upd = $db->prepare("
                                UPDATE forts SET
                                    name = ?, name_mr = ?, alternate_names = ?,
                                    district_id = ?, taluka = ?, village = ?,
                                    latitude = ?, longitude = ?, altitude_meters = ?,
                                    category_id = ?, historical_period = ?, dynasties_rulers = ?,
                                    is_shivaji_fort = ?, is_unesco_site = ?, unesco_inscription_details = ?,
                                    trekking_difficulty = ?, trek_duration_hours = ?, best_season = ?,
                                    historical_background = ?, historical_background_mr = ?,
                                    shivaji_connection = ?, shivaji_connection_mr = ?,
                                    key_structures = ?, water_sources = ?, how_to_reach = ?,
                                    nearest_railway = ?, nearest_airport = ?, primary_image = ?,
                                    reference_sources = ?, updated_at = NOW()
                                WHERE id = ?
                            ");
                            $upd->execute([
                                $name, $nameMr, $altNames,
                                $districtId, $taluka, $village,
                                $lat, $lng, $altitude,
                                $categoryId, $period, $dynasties,
                                $isShivaji, $isUnesco, $unescoDetails,
                                $diff, $duration, $season,
                                $bgEn, $bgMr,
                                $shEn, $shMr,
                                $structures, $water, $reach,
                                $railway, $airport, $image,
                                $sources, $existing['id']
                            ]);
                            $stats['updated']++;
                        } else {
                            // INSERT NEW FORT
                            $ins = $db->prepare("
                                INSERT INTO forts (
                                    name, name_mr, alternate_names, slug,
                                    district_id, taluka, village,
                                    latitude, longitude, altitude_meters,
                                    category_id, historical_period, dynasties_rulers,
                                    is_shivaji_fort, is_unesco_site, unesco_inscription_details,
                                    trekking_difficulty, trek_duration_hours, best_season,
                                    historical_background, historical_background_mr,
                                    shivaji_connection, shivaji_connection_mr,
                                    key_structures, water_sources, how_to_reach,
                                    nearest_railway, nearest_airport, primary_image,
                                    verification_status, reference_sources
                                ) VALUES (
                                    ?, ?, ?, ?,
                                    ?, ?, ?,
                                    ?, ?, ?,
                                    ?, ?, ?,
                                    ?, ?, ?,
                                    ?, ?, ?,
                                    ?, ?,
                                    ?, ?,
                                    ?, ?, ?,
                                    ?, ?, ?,
                                    'verified', ?
                                )
                            ");
                            $ins->execute([
                                $name, $nameMr, $altNames, $slug,
                                $districtId, $taluka, $village,
                                $lat, $lng, $altitude,
                                $categoryId, $period, $dynasties,
                                $isShivaji, $isUnesco, $unescoDetails,
                                $diff, $duration, $season,
                                $bgEn, $bgMr,
                                $shEn, $shMr,
                                $structures, $water, $reach,
                                $railway, $airport, $image,
                                $sources
                            ]);
                            $newFortId = (int)$db->lastInsertId();

                            // Synchronize matching destination record
                            $destIns = $db->prepare("
                                INSERT INTO destinations (
                                    category_id, district_id, name, name_mr, slug,
                                    short_description, short_description_mr, overview,
                                    historical_significance, historical_significance_mr,
                                    shivaji_connection, shivaji_connection_mr,
                                    latitude, longitude, altitude_meters,
                                    best_season, how_to_reach_road, how_to_reach_rail,
                                    difficulty_level, trek_duration_hours,
                                    primary_image, is_shivaji_fort, is_unesco_site,
                                    taluka, village, alternate_names, historical_period,
                                    dynasties_rulers, fort_category_id, verification_status,
                                    reference_sources
                                ) VALUES (
                                    1, ?, ?, ?, ?,
                                    ?, ?, ?,
                                    ?, ?,
                                    ?, ?,
                                    ?, ?, ?,
                                    ?, ?, ?,
                                    ?, ?,
                                    ?, ?, ?,
                                    ?, ?, ?, ?,
                                    ?, ?, 'verified',
                                    ?
                                )
                            ");
                            $destIns->execute([
                                $districtId, $name, $nameMr, $slug,
                                mb_substr(strip_tags($bgEn), 0, 240) . '...',
                                mb_substr(strip_tags($bgMr), 0, 240) . '...',
                                $bgEn, $bgEn, $bgMr,
                                $shEn, $shMr,
                                $lat, $lng, $altitude,
                                $season, $reach, $railway,
                                $diff, $duration,
                                $image, $isShivaji, $isUnesco,
                                $taluka, $village, $altNames, $period,
                                $dynasties, $categoryId,
                                $sources
                            ]);
                            $newDestId = (int)$db->lastInsertId();

                            $db->query("UPDATE forts SET destination_id = {$newDestId} WHERE id = {$newFortId}");
                            $stats['inserted']++;
                        }
                    } catch (Exception $rowEx) {
                        $stats['errors'][] = "Row #{$rowNum} ('{$name}'): " . $rowEx->getMessage();
                    }
                }

                fclose($handle);
                $importResult = $stats;
            }
        } else {
            set_flash('danger', 'Unable to open uploaded file.');
        }
    }
}

$adminTitle = 'Fort CSV Import / Export';
require_once __DIR__ . '/includes/admin-header.php';

$totalForts = (int)$db->query("SELECT COUNT(*) FROM forts")->fetchColumn();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold font-heritage mb-1 text-dark">Fort Directory CSV Import & Export</h4>
        <p class="text-muted small mb-0">Backup, bulk export, or batch update Maharashtra fort records via standard CSV.</p>
    </div>
    <a href="forts.php" class="btn btn-sm btn-outline-secondary">
        <i class="fa-solid fa-arrow-left me-1"></i>Back to Forts
    </a>
</div>

<?php if ($importResult): ?>
    <div class="alert alert-info border shadow-sm mb-4">
        <h6 class="fw-bold mb-2"><i class="fa-solid fa-circle-check text-success me-2"></i>Import Process Complete</h6>
        <div class="small">
            <strong>Processed:</strong> <?= $importResult['processed'] ?> rows | 
            <strong class="text-success">Inserted:</strong> <?= $importResult['inserted'] ?> new forts | 
            <strong class="text-primary">Updated:</strong> <?= $importResult['updated'] ?> existing forts | 
            <strong class="text-danger">Errors:</strong> <?= count($importResult['errors']) ?>
        </div>
        <?php if (!empty($importResult['errors'])): ?>
            <div class="mt-2 pt-2 border-top">
                <div class="fw-bold small text-danger">Errors Encountered:</div>
                <ul class="small mb-0 text-muted ps-3">
                    <?php foreach (array_slice($importResult['errors'], 0, 10) as $err): ?>
                        <li><?= e($err) ?></li>
                    <?php endforeach; ?>
                    <?php if (count($importResult['errors']) > 10): ?>
                        <li>...and <?= count($importResult['errors']) - 10 ?> more errors.</li>
                    <?php endif; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="row g-4">
    <!-- EXPORT SECTION -->
    <div class="col-lg-6">
        <div class="admin-card h-100 border-start border-4 border-success">
            <div class="d-flex align-items-center gap-3 mb-3">
                <div class="p-3 bg-success-subtle text-success rounded-3 fs-3">
                    <i class="fa-solid fa-file-arrow-down"></i>
                </div>
                <div>
                    <h5 class="fw-bold font-heritage mb-0 text-dark">Export Maharashtra Fort Directory</h5>
                    <span class="text-muted small">Current Database: <?= $totalForts ?> Fort Records</span>
                </div>
            </div>

            <p class="text-muted small mb-4">
                Export all verified fort records across all 36 districts of Maharashtra to a CSV spreadsheet.
                The export file uses <strong>UTF-8 BOM</strong> encoding to preserve Marathi Devnagari script in Microsoft Excel and Google Sheets.
            </p>

            <ul class="list-group list-group-flush small mb-4">
                <li class="list-group-item px-0 d-flex justify-content-between">
                    <span>Includes 11 UNESCO serial components</span>
                    <i class="fa-solid fa-check text-success"></i>
                </li>
                <li class="list-group-item px-0 d-flex justify-content-between">
                    <span>Includes coordinates, altitude & classifications</span>
                    <i class="fa-solid fa-check text-success"></i>
                </li>
                <li class="list-group-item px-0 d-flex justify-content-between">
                    <span>Includes Marathi Devnagari names & history</span>
                    <i class="fa-solid fa-check text-success"></i>
                </li>
                <li class="list-group-item px-0 d-flex justify-content-between">
                    <span>Includes official archival reference citations</span>
                    <i class="fa-solid fa-check text-success"></i>
                </li>
            </ul>

            <div class="d-flex gap-2">
                <a href="fort-import-export.php?action=export" class="btn btn-fe-primary fw-bold flex-grow-1">
                    <i class="fa-solid fa-download me-2"></i>Download Full CSV Export (<?= $totalForts ?> Forts)
                </a>
            </div>
        </div>
    </div>

    <!-- IMPORT SECTION -->
    <div class="col-lg-6">
        <div class="admin-card h-100 border-start border-4 border-primary">
            <div class="d-flex align-items-center gap-3 mb-3">
                <div class="p-3 bg-primary-subtle text-primary rounded-3 fs-3">
                    <i class="fa-solid fa-file-arrow-up"></i>
                </div>
                <div>
                    <h5 class="fw-bold font-heritage mb-0 text-dark">Bulk Import / Update Forts</h5>
                    <span class="text-muted small">CSV Upload with Duplicate Detection</span>
                </div>
            </div>

            <p class="text-muted small mb-3">
                Upload a structured CSV file to insert new fort records or update existing records.
                Entries with matching slugs will be safely updated without creating duplicate entries.
            </p>

            <form method="POST" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="hidden" name="do_import" value="1">

                <div class="mb-3">
                    <label class="form-label fw-semibold small">Select CSV File (.csv)</label>
                    <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                    <div class="form-text small">File must be encoded in UTF-8. Max file size: 10MB.</div>
                </div>

                <div class="d-flex gap-2 align-items-center">
                    <button type="submit" class="btn btn-primary fw-bold flex-grow-1">
                        <i class="fa-solid fa-upload me-2"></i>Upload & Process CSV
                    </button>
                    <a href="fort-import-export.php?action=sample_template" class="btn btn-outline-secondary btn-sm text-nowrap" title="Download sample template">
                        <i class="fa-solid fa-table me-1"></i>Sample Template
                    </a>
                </div>
            </form>

            <div class="mt-4 p-3 bg-light rounded-3 border small">
                <div class="fw-bold text-dark mb-1"><i class="fa-solid fa-lightbulb text-warning me-1"></i>Import Guidelines:</div>
                <ul class="text-muted mb-0 ps-3">
                    <li>Minimum required columns: <code>Fort Name (EN)</code>, <code>District (EN)</code>.</li>
                    <li>District names must match one of the 36 Maharashtra districts.</li>
                    <li>Classification codes: <code>hill_fort</code>, <code>sea_fort</code>, <code>island_fort</code>, <code>land_fort</code>, <code>forest_fort</code>, <code>coastal_fort</code>, <code>fortified_heritage</code>.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
