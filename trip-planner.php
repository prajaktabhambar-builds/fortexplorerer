<?php
/**
 * FORT EXPLORER - Interactive Smart Travel Route Planner & Itinerary Optimizer
 * Multi-destination trip creation, departure point selection, TSP route optimization,
 * driving time calculation, shareable route links, and Leaflet polyline visualization.
 */

require_once __DIR__ . '/includes/header.php';

$db = getDB();
$allDestinations = [];
$userTrips = [];
$activeTrip = null;
$tripId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$shareToken = trim($_GET['share'] ?? '');

if ($db) {
    try {
        // Fetch all destinations for selector
        $dStmt = $db->query("
            SELECT d.id, d.name, d.name_mr, d.latitude, d.longitude, d.primary_image, d.difficulty_level,
                   c.name_en AS category_name, dist.name_en AS district_name
            FROM destinations d
            JOIN destination_categories c ON d.category_id = c.id
            JOIN districts dist ON d.district_id = dist.id
            ORDER BY d.is_shivaji_fort DESC, d.name ASC
        ");
        $allDestinations = $dStmt->fetchAll();

        // If viewing via share token (public read access)
        if ($shareToken) {
            $shStmt = $db->prepare("
                SELECT t.*, u.name AS creator_name 
                FROM trips t
                JOIN users u ON t.user_id = u.id
                WHERE t.share_token = ?
            ");
            $shStmt->execute([$shareToken]);
            $activeTrip = $shStmt->fetch();

            if ($activeTrip) {
                $sStmt = $db->prepare("
                    SELECT td.*, d.name, d.latitude, d.longitude, d.difficulty_level, c.name_en AS category_name, dist.name_en AS district_name
                    FROM trip_destinations td
                    JOIN destinations d ON td.destination_id = d.id
                    JOIN destination_categories c ON d.category_id = c.id
                    JOIN districts dist ON d.district_id = dist.id
                    WHERE td.trip_id = ?
                    ORDER BY td.day_number ASC, td.visit_order ASC
                ");
                $sStmt->execute([$activeTrip['id']]);
                $activeTrip['stops'] = $sStmt->fetchAll();
            }
        }
        // Fetch user's saved trips if logged in
        elseif (is_logged_in()) {
            $tStmt = $db->prepare("SELECT * FROM trips WHERE user_id = ? ORDER BY created_at DESC");
            $tStmt->execute([$_SESSION['user_id']]);
            $userTrips = $tStmt->fetchAll();

            // If editing a specific trip
            if ($tripId) {
                $atStmt = $db->prepare("SELECT * FROM trips WHERE id = ? AND user_id = ?");
                $atStmt->execute([$tripId, $_SESSION['user_id']]);
                $activeTrip = $atStmt->fetch();

                if ($activeTrip) {
                    $sStmt = $db->prepare("
                        SELECT td.*, d.name, d.latitude, d.longitude, d.difficulty_level, c.name_en AS category_name, dist.name_en AS district_name
                        FROM trip_destinations td
                        JOIN destinations d ON td.destination_id = d.id
                        JOIN destination_categories c ON d.category_id = c.id
                        JOIN districts dist ON d.district_id = dist.id
                        WHERE td.trip_id = ?
                        ORDER BY td.day_number ASC, td.visit_order ASC
                    ");
                    $sStmt->execute([$tripId]);
                    $activeTrip['stops'] = $sStmt->fetchAll();
                }
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Handle Trip Save / Update Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_trip') {
    require_login();
    if (verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $tripTitle = sanitize_input($_POST['title'] ?? 'My Sahyadri Trip');
        $tripDesc = sanitize_input($_POST['description'] ?? '');
        $startLoc = sanitize_input($_POST['start_location'] ?? 'Pune');
        $startDate = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
        $endDate = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
        $stopsJson = $_POST['stops_json'] ?? '[]';
        $editTripId = filter_input(INPUT_POST, 'trip_id', FILTER_VALIDATE_INT);
        $totalKm = (float)($_POST['total_distance_km'] ?? 0);
        $estHours = (float)($_POST['estimated_duration_hours'] ?? 0);

        $stopsData = json_decode($stopsJson, true);

        if (empty($stopsData)) {
            set_flash('danger', 'Please add at least one destination stop to your itinerary.');
        } else {
            try {
                $db->beginTransaction();

                if ($editTripId) {
                    $upd = $db->prepare("
                        UPDATE trips 
                        SET title = ?, description = ?, start_location = ?, start_date = ?, end_date = ?, total_distance_km = ?, estimated_duration_hours = ? 
                        WHERE id = ? AND user_id = ?
                    ");
                    $upd->execute([$tripTitle, $tripDesc, $startLoc, $startDate, $endDate, $totalKm, $estHours, $editTripId, $_SESSION['user_id']]);
                    $targetTripId = $editTripId;

                    // Ensure share_token exists
                    $tCheck = $db->prepare("SELECT share_token FROM trips WHERE id = ?");
                    $tCheck->execute([$targetTripId]);
                    $tok = $tCheck->fetchColumn();
                    if (!$tok) {
                        $tok = bin2hex(random_bytes(16));
                        $db->prepare("UPDATE trips SET share_token = ? WHERE id = ?")->execute([$tok, $targetTripId]);
                    }

                    // Clear old stops
                    $del = $db->prepare("DELETE FROM trip_destinations WHERE trip_id = ?");
                    $del->execute([$targetTripId]);
                } else {
                    $newShareToken = bin2hex(random_bytes(16));
                    $ins = $db->prepare("
                        INSERT INTO trips (user_id, title, description, start_location, start_date, end_date, total_distance_km, estimated_duration_hours, share_token) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $ins->execute([$_SESSION['user_id'], $tripTitle, $tripDesc, $startLoc, $startDate, $endDate, $totalKm, $estHours, $newShareToken]);
                    $targetTripId = (int)$db->lastInsertId();
                }

                // Insert stops
                $insStop = $db->prepare("
                    INSERT INTO trip_destinations (trip_id, destination_id, day_number, visit_order, notes) 
                    VALUES (?, ?, ?, ?, ?)
                ");

                foreach ($stopsData as $order => $stop) {
                    $insStop->execute([
                        $targetTripId,
                        $stop['id'],
                        (int)($stop['day'] ?? 1),
                        $order + 1,
                        sanitize_input($stop['notes'] ?? '')
                    ]);
                }

                $db->commit();
                set_flash('success', 'Trip itinerary saved successfully!');
                header("Location: trip-planner.php?id=" . $targetTripId);
                exit;

            } catch (Exception $e) {
                $db->rollBack();
                set_flash('danger', 'Error saving trip: ' . $e->getMessage());
            }
        }
    } else {
        set_flash('danger', 'CSRF verification failed.');
    }
}

// Generate share link
$shareUrl = '';
if ($activeTrip && !empty($activeTrip['share_token'])) {
    $fullHost = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . ($_SERVER['HTTP_HOST'] ?? '127.0.0.1:8000');
    $shareUrl = $fullHost . "/trip-planner.php?share=" . urlencode($activeTrip['share_token']);
}
?>

<!-- Header Banner -->
<div class="py-5" style="background: linear-gradient(135deg, var(--fe-forest-dark) 0%, var(--fe-forest) 100%); color: #fff; border-bottom: 3px solid var(--fe-gold);">
    <div class="container py-3 text-center">
        <h1 class="display-5 fw-bold font-heritage mb-2">
            <i class="fa-solid fa-route me-2 text-warning"></i><?= __('nav_trip_planner') ?>
        </h1>
        <p class="lead text-white-50 max-w-600 mx-auto mb-0 fs-6">
            <?= is_marathi()
                ? 'आपल्या आवडीनुसार अनेक किल्ले निवडा, दिवसांनुसार क्रम लावा, अंतर मोजा आणि सुंदर सहल आराखडा तयार करा.'
                : 'Smart Sahyadri route optimizer: select forts, choose starting city, optimize travel order, and share itineraries.' ?>
        </p>
    </div>
</div>

<div class="container py-5">
    <?php if ($shareToken && $activeTrip): ?>
        <div class="alert alert-info d-flex align-items-center justify-content-between mb-4 rounded-4 shadow-sm">
            <div class="d-flex align-items-center gap-3">
                <i class="fa-solid fa-share-nodes fs-3 text-primary"></i>
                <div>
                    <strong>Shared Route Itinerary:</strong> <?= e($activeTrip['title']) ?> (Created by <?= e($activeTrip['creator_name'] ?? 'Explorer') ?>)
                </div>
            </div>
            <?php if (is_logged_in()): ?>
                <a href="trip-planner.php" class="btn btn-sm btn-dark">Create My Own Plan</a>
            <?php else: ?>
                <a href="register.php" class="btn btn-sm btn-success">Sign Up to Save</a>
            <?php endif; ?>
        </div>
    <?php elseif (!is_logged_in()): ?>
        <div class="alert alert-warning d-flex align-items-center justify-content-between mb-4 rounded-3 border-warning">
            <div class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-circle-exclamation fs-4"></i>
                <span>Please sign in to save and sync your itineraries to your personal travel journey.</span>
            </div>
            <a href="login.php?redirect=trip-planner.php" class="btn btn-sm btn-dark">Sign In</a>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Left Column: Trip Details & Itinerary Builder -->
        <div class="col-lg-7">
            <div class="bg-white p-4 rounded-4 shadow-sm border mb-4">
                <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-3">
                    <h5 class="font-heritage mb-0 text-success">
                        <i class="fa-solid fa-map me-2 text-warning"></i>
                        <?= $activeTrip ? 'Itinerary: ' . e($activeTrip['title']) : 'Create New Trip Itinerary' ?>
                    </h5>
                    <div class="d-flex gap-2">
                        <?php if ($activeTrip && !empty($shareUrl)): ?>
                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#shareTripModal">
                                <i class="fa-solid fa-share-nodes me-1"></i>Share
                            </button>
                        <?php endif; ?>
                        <?php if ($activeTrip && !$shareToken): ?>
                            <a href="trip-planner.php" class="btn btn-sm btn-outline-secondary">+ New</a>
                        <?php endif; ?>
                    </div>
                </div>

                <form method="POST" id="tripPlannerForm">
                    <input type="hidden" name="action" value="save_trip">
                    <input type="hidden" name="trip_id" value="<?= $activeTrip['id'] ?? '' ?>">
                    <input type="hidden" name="stops_json" id="tripStopsJsonInput" value="">
                    <input type="hidden" name="total_distance_km" id="tripTotalKmInput" value="0">
                    <input type="hidden" name="estimated_duration_hours" id="tripEstHoursInput" value="0">
                    <?= csrf_field() ?>

                    <div class="row g-3 mb-3">
                        <div class="col-md-7">
                            <label class="form-label small fw-bold">Trip Title</label>
                            <input type="text" name="title" class="form-control" placeholder="e.g. Sahyadri Weekend Fort Circuit" value="<?= e($activeTrip['title'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small fw-bold">Starting Base City</label>
                            <select name="start_location" id="tripStartLocationSelect" class="form-select">
                                <?php 
                                $start = $activeTrip['start_location'] ?? 'Pune';
                                $cities = ['Pune', 'Mumbai', 'Kolhapur', 'Nashik', 'Satara', 'Chhatrapati Sambhajinagar', 'Thane'];
                                foreach ($cities as $city): ?>
                                    <option value="<?= $city ?>" <?= $start === $city ? 'selected' : '' ?>><?= $city ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Start Date</label>
                            <input type="date" name="start_date" class="form-control" value="<?= $activeTrip['start_date'] ?? date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">End Date</label>
                            <input type="date" name="end_date" class="form-control" value="<?= $activeTrip['end_date'] ?? date('Y-m-d', strtotime('+2 days')) ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Trip Notes / Trek Goals</label>
                            <textarea name="description" rows="2" class="form-control" placeholder="Vehicle arrangements, companion list, sunrise treks..."><?= e($activeTrip['description'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <!-- Destination Stop Selector -->
                    <div class="p-3 bg-light rounded-3 border mb-4">
                        <label class="form-label small fw-bold text-success mb-2">
                            <i class="fa-solid fa-plus-circle me-1"></i>Add Destination to Route
                        </label>
                        <div class="d-flex gap-2">
                            <select id="destinationSelector" class="form-select">
                                <option value="">-- Choose a Fort or Destination --</option>
                                <?php foreach ($allDestinations as $d): ?>
                                    <option value="<?= $d['id'] ?>" 
                                            data-name="<?= htmlspecialchars($d['name']) ?>"
                                            data-category="<?= htmlspecialchars($d['category_name']) ?>"
                                            data-lat="<?= $d['latitude'] ?>"
                                            data-lng="<?= $d['longitude'] ?>"
                                            data-difficulty="<?= htmlspecialchars($d['difficulty_level'] ?? 'Moderate') ?>">
                                        <?= e(t_val($d['name'], $d['name_mr'])) ?> (<?= e($d['district_name']) ?> &bull; <?= e($d['category_name']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" class="btn btn-fe-primary text-nowrap" id="btnAddStopToTrip">
                                <i class="fa-solid fa-plus me-1"></i>Add Stop
                            </button>
                        </div>
                    </div>

                    <!-- Itinerary Stops List & Optimization Bar -->
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <h6 class="fw-bold font-heritage mb-0 text-dark"><i class="fa-solid fa-list-ol me-2 text-warning"></i>Itinerary Route Order</h6>
                        <button type="button" class="btn btn-sm btn-outline-success" id="btnOptimizeRoute">
                            <i class="fa-solid fa-wand-magic-sparkles me-1 text-warning"></i>Smart Optimize Route
                        </button>
                    </div>
                    
                    <div id="itineraryEmptyState" class="text-center py-4 text-muted small border rounded-3 bg-light">
                        <i class="fa-solid fa-route fa-2x mb-2 text-muted opacity-50"></i>
                        <p class="mb-0">No stops added yet. Select a destination above to start planning your route.</p>
                    </div>

                    <div id="itineraryStopsList" class="mb-4"></div>

                    <!-- Summary & Distance Box -->
                    <div id="itinerarySummaryBox" class="p-3 bg-success-subtle border border-success rounded-3 mb-4" style="display:none;">
                        <div class="row text-center g-2">
                            <div class="col-4">
                                <span class="small text-muted d-block" style="font-size:0.75rem;">Total Stops</span>
                                <strong class="fs-5 text-dark" id="totalStopsCount">0</strong>
                            </div>
                            <div class="col-4">
                                <span class="small text-muted d-block" style="font-size:0.75rem;">Est. Road Distance</span>
                                <strong class="fs-5 text-success" id="totalEstimatedDistance">~0 km</strong>
                            </div>
                            <div class="col-4">
                                <span class="small text-muted d-block" style="font-size:0.75rem;">Total Travel & Trek Time</span>
                                <strong class="fs-5 text-primary" id="totalEstimatedTime">~0 hrs</strong>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-fe-primary py-2.5 fs-6" <?= $shareToken ? 'disabled' : '' ?>>
                            <i class="fa-solid fa-floppy-disk me-2"></i>Save This Trip Plan
                        </button>
                    </div>
                </form>
            </div>

            <!-- Saved User Trips List -->
            <?php if (!empty($userTrips)): ?>
                <div class="bg-white p-4 rounded-4 shadow-sm border">
                    <h5 class="font-heritage mb-3 text-dark"><i class="fa-solid fa-bookmark me-2 text-warning"></i>My Saved Trips</h5>
                    <div class="list-group">
                        <?php foreach ($userTrips as $ut): ?>
                            <a href="trip-planner.php?id=<?= $ut['id'] ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?= ($activeTrip && $activeTrip['id'] == $ut['id']) ? 'active' : '' ?>">
                                <div>
                                    <h6 class="mb-1 fw-bold"><?= e($ut['title']) ?></h6>
                                    <span class="small opacity-75"><i class="fa-solid fa-calendar me-1"></i><?= format_date($ut['start_date']) ?> to <?= format_date($ut['end_date']) ?> &bull; From <?= e($ut['start_location'] ?? 'Pune') ?></span>
                                </div>
                                <i class="fa-solid fa-arrow-right"></i>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Right Column: Interactive Leaflet Route Map Canvas -->
        <div class="col-lg-5">
            <div class="bg-white p-3 rounded-4 shadow-sm border position-sticky" style="top: 90px;">
                <div class="d-flex justify-content-between align-items-center pb-2 border-bottom mb-2">
                    <h6 class="fw-bold font-heritage mb-0 text-dark">
                        <i class="fa-solid fa-map-location-dot me-2 text-warning"></i>Route Map
                    </h6>
                    <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" onclick="fitTripMapBounds()">
                        <i class="fa-solid fa-expand me-1"></i>Fit Bounds
                    </button>
                </div>
                <div id="tripRouteMap" style="height: 520px; border-radius: 12px;" class="border"></div>
                <p class="small text-muted mt-2 mb-0 fst-italic" style="font-size:0.78rem;">
                    <i class="fa-solid fa-circle-info me-1 text-primary"></i>The golden polyline connects your itinerary sequence. Distance estimates include Sahyadri mountain road curvature and realistic ghat travel times.
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Share Trip Modal -->
<?php if (!empty($shareUrl)): ?>
<div class="modal fade" id="shareTripModal" tabindex="-1" aria-labelledby="shareTripModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title font-heritage" id="shareTripModalLabel"><i class="fa-solid fa-share-nodes text-warning me-2"></i>Share Trip Itinerary</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p class="small text-muted mb-3">Anyone with this link can view this route, stops order, and interactive map.</p>
                <div class="input-group input-group-sm mb-3">
                    <input type="text" class="form-control" value="<?= e($shareUrl) ?>" id="shareUrlInput" readonly>
                    <button class="btn btn-dark" type="button" onclick="navigator.clipboard.writeText(document.getElementById('shareUrlInput').value); showToast('Link copied to clipboard!', 'success');">
                        <i class="fa-regular fa-copy"></i>
                    </button>
                </div>
                <div class="d-flex gap-2">
                    <a href="https://api.whatsapp.com/send?text=<?= urlencode('Check out my Maharashtra Fort Expedition plan on Fort Explorer: ' . $shareUrl) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-success w-100 rounded-pill">
                        <i class="fa-brands fa-whatsapp me-1"></i>Share on WhatsApp
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="<?= BASE_URL ?>assets/js/trip-planner.js?v=<?= APP_VERSION ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    initTripPlannerMap('tripRouteMap');

    // Add Stop Handler
    const addBtn = document.getElementById('btnAddStopToTrip');
    const selector = document.getElementById('destinationSelector');

    if (addBtn && selector) {
        addBtn.addEventListener('click', () => {
            const opt = selector.options[selector.selectedIndex];
            if (!opt.value) return;

            const destObj = {
                id: opt.value,
                name: opt.getAttribute('data-name'),
                category_name: opt.getAttribute('data-category'),
                latitude: parseFloat(opt.getAttribute('data-lat')),
                longitude: parseFloat(opt.getAttribute('data-lng')),
                difficulty_level: opt.getAttribute('data-difficulty') || 'Moderate'
            };

            addDestinationToTrip(destObj);
        });
    }

    // Smart TSP Optimize Route
    const optBtn = document.getElementById('btnOptimizeRoute');
    if (optBtn) {
        optBtn.addEventListener('click', () => {
            optimizeTripRouteOrder();
        });
    }

    // Pre-load active trip stops if editing or viewing shared
    <?php if ($activeTrip && !empty($activeTrip['stops'])): ?>
        const existingStops = <?= json_encode($activeTrip['stops']) ?>;
        existingStops.forEach(s => {
            tripStops.push({
                id: s.destination_id,
                name: s.name,
                category: s.category_name,
                lat: parseFloat(s.latitude),
                lng: parseFloat(s.longitude),
                difficulty: s.difficulty_level || 'Moderate',
                day: parseInt(s.day_number) || 1,
                notes: s.notes || ''
            });
        });
        renderTripItineraryUI();
        updateTripMapRoute();
    <?php endif; ?>
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
