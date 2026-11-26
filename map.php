<?php
/**
 * FORT EXPLORER - Full Interactive Leaflet & OpenStreetMap Explorer
 * Real-time filtering across 367+ Forts & Destinations, 36 Districts, 
 * 11 UNESCO Sites, 7 Fort Classifications, search, and live directions.
 */

require_once __DIR__ . '/includes/header.php';

$db = getDB();
$destinations = [];
$categories = [];
$districts = [];
$fortCategories = [];

if ($db) {
    try {
        $cStmt = $db->query("SELECT * FROM destination_categories ORDER BY id ASC");
        $categories = $cStmt->fetchAll();

        $distStmt = $db->query("SELECT id, name_en, name_mr, division FROM districts ORDER BY name_en ASC");
        $districts = $distStmt->fetchAll();

        $fcStmt = $db->query("SELECT * FROM fort_categories ORDER BY id ASC");
        $fortCategories = $fcStmt->fetchAll();

        $dStmt = $db->query("
            SELECT d.id, d.name, d.name_mr, d.slug, d.short_description, d.short_description_mr,
                   d.latitude, d.longitude, d.altitude_meters, d.difficulty_level, d.primary_image,
                   d.is_unesco_site, d.is_shivaji_fort, d.taluka,
                   c.slug AS category_slug, c.name_en AS category_name, c.name_mr AS category_name_mr,
                   dist.id AS district_id, dist.name_en AS district_name, dist.name_mr AS district_name_mr,
                   fc.code AS fort_cat_code, fc.name_en AS fort_cat_name, fc.name_mr AS fort_cat_name_mr,
                   fc.traditional_name AS fort_cat_trad, fc.traditional_name_mr AS fort_cat_trad_mr
            FROM destinations d
            JOIN destination_categories c ON d.category_id = c.id
            JOIN districts dist ON d.district_id = dist.id
            LEFT JOIN fort_categories fc ON d.fort_category_id = fc.id
            ORDER BY d.is_unesco_site DESC, d.is_featured DESC, d.name ASC
        ");
        $destinations = $dStmt->fetchAll();
    } catch (Exception $e) {
        // Fallback
    }
}

$unescoCount = 0;
$shivajiCount = 0;
foreach ($destinations as $dst) {
    if (!empty($dst['is_unesco_site'])) $unescoCount++;
    if (!empty($dst['is_shivaji_fort'])) $shivajiCount++;
}
?>

<div class="container-fluid px-lg-4 py-4">
    <!-- Map Controls & Filter Header -->
    <div class="bg-white p-3 rounded-4 shadow-sm border mb-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <h4 class="font-heritage mb-1 text-success d-flex align-items-center gap-2">
                    <i class="fa-solid fa-map-location-dot text-warning"></i>
                    <span><?= is_marathi() ? 'महाराष्ट्र किल्ले व वारसा संवादी नकाशा' : 'Maharashtra Forts & Heritage Map' ?></span>
                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill fs-6 px-3">
                        <?= count($destinations) ?> <?= is_marathi() ? 'ठिकाणे' : 'Locations' ?>
                    </span>
                </h4>
                <p class="small text-muted mb-0">
                    <?= is_marathi() 
                        ? '३६ जिल्ह्यांमधील ३६७+ किल्ले, ११ युनेस्को नामांकित स्थळे, व ऐतिहासिक वास्तू नकाशावर शोधा.' 
                        : 'Explore 367+ verified forts across all 36 districts, 11 UNESCO serial nomination sites, and heritage monuments.' ?>
                </p>
            </div>

            <!-- Controls Toolbar: District Filter, Search & Actions -->
            <div class="d-flex flex-wrap align-items-center gap-2">
                <!-- District Dropdown -->
                <select id="mapDistrictSelect" class="form-select form-select-sm rounded-pill" style="min-width: 170px;">
                    <option value="all"><?= is_marathi() ? 'सर्व ३६ जिल्हे (All Districts)' : 'All 36 Districts' ?></option>
                    <?php foreach ($districts as $dst): ?>
                        <option value="<?= $dst['id'] ?>"><?= e(t_val($dst['name_en'], $dst['name_mr'])) ?></option>
                    <?php endforeach; ?>
                </select>

                <!-- Search Input -->
                <div class="input-group input-group-sm" style="max-width: 220px;">
                    <span class="input-group-text bg-light border-end-0 rounded-start-pill"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                    <input type="text" id="mapSearchInput" class="form-control border-start-0 rounded-end-pill" placeholder="<?= is_marathi() ? 'किल्ला शोधा...' : 'Search fort or place...' ?>">
                </div>

                <button type="button" id="btnLocateMe" class="btn btn-outline-dark btn-sm rounded-pill px-3">
                    <i class="fa-solid fa-crosshairs me-1 text-primary"></i><?= is_marathi() ? 'माझे स्थान' : 'Locate Me' ?>
                </button>
                <a href="forts.php" class="btn btn-fe-primary btn-sm rounded-pill px-3">
                    <i class="fa-solid fa-shield-halved me-1"></i><?= is_marathi() ? 'दुर्ग यादी' : 'Fort Directory' ?>
                </a>
            </div>
        </div>

        <!-- Category & Classification Filter Pills -->
        <div class="d-flex flex-wrap align-items-center gap-2 mt-3 pt-2 border-top overflow-x-auto pb-1" id="mapFilterPills">
            <button type="button" class="btn btn-sm btn-fe-primary active rounded-pill px-3 text-nowrap" data-map-filter="all">
                <i class="fa-solid fa-earth-asia me-1"></i><?= is_marathi() ? 'सर्व' : 'All' ?> (<?= count($destinations) ?>)
            </button>
            <button type="button" class="btn btn-sm btn-outline-warning rounded-pill px-3 text-nowrap fw-bold text-dark border-warning" data-map-filter="unesco">
                <i class="fa-solid fa-crown text-warning me-1"></i>UNESCO Nominated (<?= $unescoCount ?>)
            </button>
            <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 text-nowrap" data-map-filter="shivaji">
                <i class="fa-solid fa-flag text-danger me-1"></i><?= is_marathi() ? 'शिवकालीन किल्ले' : 'Shivaji Forts' ?> (<?= $shivajiCount ?>)
            </button>
            <?php foreach ($fortCategories as $fc): ?>
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 text-nowrap" data-map-filter="fc_<?= e($fc['code']) ?>">
                    <i class="fa-solid <?= e($fc['badge_icon']) ?> me-1"></i><?= e(t_val($fc['traditional_name'] . ' (' . $fc['name_en'] . ')', $fc['traditional_name_mr'])) ?>
                </button>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Map & Sidebar Split Layout -->
    <div class="row g-3">
        <!-- Interactive Leaflet Map Canvas -->
        <div class="col-lg-9">
            <div id="fe-map" style="height: 680px; border-radius: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.06); border: 1px solid #e0dbd0;"></div>
        </div>

        <!-- Quick Destinations List Sidebar -->
        <div class="col-lg-3">
            <div class="map-sidebar bg-white rounded-4 border p-3 shadow-sm" style="height: 680px; display: flex; flex-direction: column;">
                <div class="d-flex align-items-center justify-content-between mb-2 pb-2 border-bottom">
                    <h6 class="fw-bold font-heritage mb-0 text-dark">
                        <i class="fa-solid fa-location-dot me-2 text-warning"></i><?= is_marathi() ? 'यादी' : 'Locations' ?>
                    </h6>
                    <span id="mapListCounter" class="badge bg-secondary-subtle text-dark rounded-pill">
                        <?= count($destinations) ?>
                    </span>
                </div>
                
                <div class="overflow-y-auto flex-grow-1 pe-1 d-flex flex-column gap-2" id="mapDestList">
                    <?php foreach ($destinations as $dest): ?>
                        <div class="p-2 rounded-3 border bg-light map-list-item cursor-pointer" 
                             data-dest-id="<?= $dest['id'] ?>"
                             data-dest-district="<?= $dest['district_id'] ?>"
                             data-dest-unesco="<?= $dest['is_unesco_site'] ?>"
                             data-dest-shivaji="<?= $dest['is_shivaji_fort'] ?>"
                             data-dest-fortcat="<?= $dest['fort_cat_code'] ?>"
                             data-dest-name="<?= strtolower(e($dest['name'] . ' ' . $dest['name_mr'] . ' ' . ($dest['taluka'] ?? ''))) ?>"
                             onclick="focusOnMapPoint(<?= $dest['latitude'] ?>, <?= $dest['longitude'] ?>, '<?= addslashes(e(t_val($dest['name'], $dest['name_mr']))) ?>')"
                             style="cursor: pointer; transition: all 0.2s ease;">
                            <div class="d-flex align-items-center gap-2">
                                <img src="<?= e($dest['primary_image']) ?>" alt="<?= e($dest['name']) ?>" class="rounded-2 flex-shrink-0" style="width: 44px; height: 44px; object-fit: cover;">
                                <div class="overflow-hidden flex-grow-1">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <h6 class="mb-0 text-truncate small fw-bold text-success" style="font-size: 0.85rem;">
                                            <?= e(t_val($dest['name'], $dest['name_mr'])) ?>
                                        </h6>
                                        <?php if (!empty($dest['is_unesco_site'])): ?>
                                            <span class="badge bg-warning text-dark flex-shrink-0 ms-1" style="font-size:0.6rem;">UNESCO</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between text-muted mt-0.5" style="font-size: 0.72rem;">
                                        <span class="text-truncate">
                                            <i class="fa-solid fa-map-pin me-1 text-danger"></i><?= e(t_val($dest['district_name'], $dest['district_name_mr'])) ?>
                                            <?= !empty($dest['taluka']) ? ', ' . e($dest['taluka']) : '' ?>
                                        </span>
                                        <?php if (!empty($dest['altitude_meters'])): ?>
                                            <span class="text-nowrap ms-1 fw-semibold text-secondary"><?= $dest['altitude_meters'] ?> m</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= BASE_URL ?>assets/js/map.js?v=<?= APP_VERSION ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const destinations = <?= json_encode($destinations, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    initFortExplorerMap(destinations, 'fe-map', 7);
});

function focusOnMapPoint(lat, lng, name) {
    if (feMap && !isNaN(lat) && !isNaN(lng)) {
        feMap.flyTo([lat, lng], 13, { duration: 1.2 });
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
