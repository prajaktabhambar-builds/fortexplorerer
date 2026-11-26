<?php
/**
 * FORT EXPLORER - Interactive Trip Budget Calculator
 * Detailed travel expense estimator for Maharashtra heritage trips.
 * Calculates fuel, transport, food, accommodation, verified entry tickets, and per-person split.
 */

require_once __DIR__ . '/includes/header.php';

$db = getDB();
$destinations = [];

if ($db) {
    try {
        $stmt = $db->query("
            SELECT id, name, name_mr, slug, entry_fees, primary_image, distance_from_pune_km, distance_from_mumbai_km
            FROM destinations
            ORDER BY is_shivaji_fort DESC, name ASC
        ");
        $destinations = $stmt->fetchAll();
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!-- Header Banner -->
<div class="py-5" style="background: linear-gradient(135deg, var(--fe-forest-dark) 0%, var(--fe-forest) 100%); color: #fff; border-bottom: 3px solid var(--fe-gold);">
    <div class="container py-3 text-center">
        <span class="badge bg-warning text-dark px-3 py-1 rounded-pill small mb-2">
            <i class="fa-solid fa-calculator me-1"></i>Trip Cost Estimator
        </span>
        <h1 class="display-5 fw-bold font-heritage mb-2">Trip Budget Calculator</h1>
        <p class="lead text-white-50 max-w-600 mx-auto mb-0 fs-6">
            Plan your Sahyadri expedition expenses accurately. Calculate fuel, public transport, homestays, authentic meals, and official entry fees.
        </p>
    </div>
</div>

<div class="container py-5">
    <div class="row g-4">
        <!-- Input Configuration Column -->
        <div class="col-lg-7">
            <div class="bg-white p-4 p-md-5 rounded-4 shadow-sm border mb-4">
                <h5 class="font-heritage text-success mb-4 border-bottom pb-3">
                    <i class="fa-solid fa-sliders me-2 text-warning"></i>Customize Your Trip Parameters
                </h5>

                <form id="budgetCalcForm">
                    <!-- 1. Destination & Route Distance -->
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-dark">Select Primary Destination</label>
                        <select class="form-select" id="budgetDestinationSelect">
                            <option value="0" data-pune="100" data-mumbai="180" data-fee="25">-- Custom / Other Fort (Default 100 km) --</option>
                            <?php foreach ($destinations as $d): 
                                $puneKm = (int)($d['distance_from_pune_km'] ?: 100);
                                $mumbaiKm = (int)($d['distance_from_mumbai_km'] ?: 180);
                                $cleanFee = preg_replace('/[^0-9]/', '', $d['entry_fees']);
                                $feeNum = !empty($cleanFee) ? (int)$cleanFee : 25;
                            ?>
                                <option value="<?= $d['id'] ?>" 
                                        data-pune="<?= $puneKm ?>" 
                                        data-mumbai="<?= $mumbaiKm ?>"
                                        data-fee="<?= $feeNum ?>"
                                        data-name="<?= htmlspecialchars($d['name']) ?>">
                                    <?= e(t_val($d['name'], $d['name_mr'])) ?> (Entry: <?= e($d['entry_fees'] ?: 'Free') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- 2. Starting Point & Distance Slider -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Starting City</label>
                            <select class="form-select" id="budgetStartCity">
                                <option value="pune" selected>Pune (Base)</option>
                                <option value="mumbai">Mumbai (Base)</option>
                                <option value="custom">Custom Location</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">One-Way Road Distance (km)</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="budgetDistanceInput" value="100" min="10" max="1000">
                                <span class="input-group-text bg-light text-muted">km</span>
                            </div>
                        </div>
                    </div>

                    <!-- 3. Travelers Count & Duration -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark d-flex justify-content-between">
                                <span>Number of Travelers</span>
                                <span class="badge bg-success" id="travelersBadge">2 People</span>
                            </label>
                            <input type="range" class="form-range" id="travelersRange" min="1" max="15" value="2">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark d-flex justify-content-between">
                                <span>Trip Duration (Days)</span>
                                <span class="badge bg-primary" id="daysBadge">1 Day</span>
                            </label>
                            <input type="range" class="form-range" id="daysRange" min="1" max="5" value="1">
                        </div>
                    </div>

                    <!-- 4. Mode of Transport -->
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-dark">Mode of Travel</label>
                        <div class="row g-2">
                            <div class="col-6 col-sm-3">
                                <input type="radio" class="btn-check" name="transportMode" id="transCar" value="car" checked>
                                <label class="btn btn-outline-success w-100 py-2 small" for="transCar">
                                    <i class="fa-solid fa-car d-block mb-1 fs-5"></i>Personal Car
                                </label>
                            </div>
                            <div class="col-6 col-sm-3">
                                <input type="radio" class="btn-check" name="transportMode" id="transBike" value="bike">
                                <label class="btn btn-outline-success w-100 py-2 small" for="transBike">
                                    <i class="fa-solid fa-motorcycle d-block mb-1 fs-5"></i>Motorcycle
                                </label>
                            </div>
                            <div class="col-6 col-sm-3">
                                <input type="radio" class="btn-check" name="transportMode" id="transBus" value="bus">
                                <label class="btn btn-outline-success w-100 py-2 small" for="transBus">
                                    <i class="fa-solid fa-bus d-block mb-1 fs-5"></i>ST Bus / Train
                                </label>
                            </div>
                            <div class="col-6 col-sm-3">
                                <input type="radio" class="btn-check" name="transportMode" id="transCab" value="cab">
                                <label class="btn btn-outline-success w-100 py-2 small" for="transCab">
                                    <i class="fa-solid fa-taxi d-block mb-1 fs-5"></i>Hired Cab
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- 5. Accommodation Type -->
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-dark">Night Stay / Accommodation</label>
                        <select class="form-select" id="budgetStaySelect">
                            <option value="0">Day Trip / No Overnight Stay (₹0)</option>
                            <option value="600" selected>Village Homestay / Trekker Dorm (~₹600 / person/night)</option>
                            <option value="1500">Standard Heritage Lodge / Hotel (~₹1,500 / room/night)</option>
                            <option value="350">Tent Camping Rental (~₹350 / person/night)</option>
                        </select>
                    </div>

                    <!-- 6. Food & Dining Preference -->
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-dark">Meal Plan Preference</label>
                        <select class="form-select" id="budgetFoodSelect">
                            <option value="300" selected>Authentic Local Meals (Pithla-Bhakri, Missal, Tea) ~₹300 / person/day</option>
                            <option value="650">Restaurant Dining & Dhaba Meals ~₹650 / person/day</option>
                            <option value="150">Self-packed Dry Rations & Snacks ~₹150 / person/day</option>
                        </select>
                    </div>

                    <!-- 7. Guide & Incidentals -->
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="budgetGuideCheck">
                            <label class="form-check-label small fw-bold" for="budgetGuideCheck">
                                Include Local Certified Heritage Fort Guide (~₹800 per group)
                            </label>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Expense Output & Breakdown Column -->
        <div class="col-lg-5">
            <div class="bg-white p-4 p-md-5 rounded-4 shadow-sm border position-sticky" style="top: 90px;" id="budgetPrintSection">
                <span class="badge bg-success-subtle text-success small mb-2">Estimated Expense Summary</span>
                <h3 class="fw-bold font-heritage text-dark mb-4">Trip Budget Estimate</h3>

                <!-- Grand Total Display -->
                <div class="p-3 bg-light rounded-4 text-center border mb-4">
                    <span class="small text-muted d-block fw-bold text-uppercase" style="font-size:0.75rem;">Total Trip Cost</span>
                    <div class="display-5 fw-bold text-success font-heritage" id="totalBudgetDisplay">₹0</div>
                    <div class="small text-muted mt-1">
                        <strong class="text-dark" id="perPersonBudgetDisplay">₹0</strong> per person for <span id="summaryTravelerCount">2</span> traveler(s)
                    </div>
                </div>

                <!-- Cost Breakdown Table -->
                <div class="d-flex flex-column gap-2 mb-4">
                    <!-- Transport -->
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span class="small text-secondary"><i class="fa-solid fa-gas-pump me-2 text-warning"></i>Transport & Fuel:</span>
                        <strong class="text-dark small" id="costTransport">₹0</strong>
                    </div>

                    <!-- Lodging -->
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span class="small text-secondary"><i class="fa-solid fa-bed me-2 text-primary"></i>Accommodation:</span>
                        <strong class="text-dark small" id="costLodging">₹0</strong>
                    </div>

                    <!-- Food -->
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span class="small text-secondary"><i class="fa-solid fa-utensils me-2 text-success"></i>Food & Dining:</span>
                        <strong class="text-dark small" id="costFood">₹0</strong>
                    </div>

                    <!-- Verified Entry Tickets -->
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span class="small text-secondary"><i class="fa-solid fa-ticket me-2 text-danger"></i>Verified Entry Tickets:</span>
                        <strong class="text-dark small" id="costTickets">₹0</strong>
                    </div>

                    <!-- Guide & Misc -->
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span class="small text-secondary"><i class="fa-solid fa-compass me-2 text-info"></i>Guide & Sundry:</span>
                        <strong class="text-dark small" id="costMisc">₹0</strong>
                    </div>
                </div>

                <!-- Verification Tag & Disclaimer -->
                <div class="p-3 rounded-3 bg-light border mb-4 small" style="font-size: 0.8rem; line-height: 1.5;">
                    <div class="d-flex align-items-center gap-1 mb-1 text-success fw-bold">
                        <i class="fa-solid fa-circle-check"></i>
                        <span>Verified vs. Estimated Transparency</span>
                    </div>
                    <span class="text-muted">
                        Official fort entry fees and ASI tickets are verified where available. Fuel, dining, and homestays are real-world estimates based on current Maharashtra rates (Petrol ~₹105/L, MSRTC tariff, local homestay standards).
                    </span>
                </div>

                <!-- Actions -->
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill w-100" onclick="window.print()">
                        <i class="fa-solid fa-print me-1"></i>Print Summary
                    </button>
                    <a href="trip-planner.php" class="btn btn-sm btn-fe-gold rounded-pill w-100">
                        <i class="fa-solid fa-route me-1"></i>Plan Full Route
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const destSelect = document.getElementById('budgetDestinationSelect');
    const startSelect = document.getElementById('budgetStartCity');
    const distInput = document.getElementById('budgetDistanceInput');
    const travelersRange = document.getElementById('travelersRange');
    const travelersBadge = document.getElementById('travelersBadge');
    const daysRange = document.getElementById('daysRange');
    const daysBadge = document.getElementById('daysBadge');
    const staySelect = document.getElementById('budgetStaySelect');
    const foodSelect = document.getElementById('budgetFoodSelect');
    const guideCheck = document.getElementById('budgetGuideCheck');

    function updateDistance() {
        const opt = destSelect.options[destSelect.selectedIndex];
        const city = startSelect.value;
        if (city === 'pune') {
            distInput.value = opt.getAttribute('data-pune') || 100;
        } else if (city === 'mumbai') {
            distInput.value = opt.getAttribute('data-mumbai') || 180;
        }
        calculateBudget();
    }

    function calculateBudget() {
        const travelers = parseInt(travelersRange.value);
        const days = parseInt(daysRange.value);
        travelersBadge.textContent = `${travelers} ${travelers === 1 ? 'Person' : 'People'}`;
        daysBadge.textContent = `${days} ${days === 1 ? 'Day' : 'Days'}`;

        const oneWayKm = parseFloat(distInput.value) || 100;
        const roundTripKm = (oneWayKm * 2) * 1.15; // 15% local movement factor

        // Transport
        const transMode = document.querySelector('input[name="transportMode"]:checked')?.value || 'car';
        let transportCost = 0;
        const fuelPrice = 105; // Rs per litre in Maharashtra

        if (transMode === 'car') {
            const carsNeeded = Math.ceil(travelers / 4);
            const liters = (roundTripKm / 14) * carsNeeded; // 14 km/L
            const toll = (oneWayKm > 100 ? 300 : 100) * 2 * carsNeeded;
            transportCost = (liters * fuelPrice) + toll;
        } else if (transMode === 'bike') {
            const bikesNeeded = Math.ceil(travelers / 2);
            const liters = (roundTripKm / 38) * bikesNeeded; // 38 km/L
            transportCost = liters * fuelPrice;
        } else if (transMode === 'bus') {
            transportCost = (roundTripKm * 1.85) * travelers; // ~1.85 Rs/km MSRTC fare
        } else if (transMode === 'cab') {
            const cabsNeeded = Math.ceil(travelers / 4);
            transportCost = (roundTripKm * 15) * cabsNeeded; // ~15 Rs/km
        }

        // Accommodation
        const stayRate = parseFloat(staySelect.value);
        let stayCost = 0;
        if (stayRate > 0 && days > 1) {
            const nights = days - 1;
            if (stayRate === 1500) {
                // Hotel room per 2 people
                const rooms = Math.ceil(travelers / 2);
                stayCost = rooms * stayRate * nights;
            } else {
                stayCost = travelers * stayRate * nights;
            }
        }

        // Food
        const foodDaily = parseFloat(foodSelect.value);
        const foodCost = travelers * foodDaily * days;

        // Verified Tickets
        const opt = destSelect.options[destSelect.selectedIndex];
        const ticketFee = parseFloat(opt.getAttribute('data-fee') || 25);
        const ticketsCost = travelers * ticketFee;

        // Guide
        const guideCost = guideCheck.checked ? (800 * days) : 0;

        const grandTotal = Math.round(transportCost + stayCost + foodCost + ticketsCost + guideCost);
        const perPerson = Math.round(grandTotal / travelers);

        // Update UI
        document.getElementById('totalBudgetDisplay').textContent = `₹${grandTotal.toLocaleString('en-IN')}`;
        document.getElementById('perPersonBudgetDisplay').textContent = `₹${perPerson.toLocaleString('en-IN')}`;
        document.getElementById('summaryTravelerCount').textContent = travelers;

        document.getElementById('costTransport').textContent = `₹${Math.round(transportCost).toLocaleString('en-IN')}`;
        document.getElementById('costLodging').textContent = `₹${Math.round(stayCost).toLocaleString('en-IN')}`;
        document.getElementById('costFood').textContent = `₹${Math.round(foodCost).toLocaleString('en-IN')}`;
        document.getElementById('costTickets').textContent = `₹${Math.round(ticketsCost).toLocaleString('en-IN')}`;
        document.getElementById('costMisc').textContent = `₹${Math.round(guideCost).toLocaleString('en-IN')}`;
    }

    destSelect.addEventListener('change', updateDistance);
    startSelect.addEventListener('change', updateDistance);
    distInput.addEventListener('input', calculateBudget);
    travelersRange.addEventListener('input', calculateBudget);
    daysRange.addEventListener('input', calculateBudget);
    staySelect.addEventListener('change', calculateBudget);
    foodSelect.addEventListener('change', calculateBudget);
    guideCheck.addEventListener('change', calculateBudget);
    document.querySelectorAll('input[name="transportMode"]').forEach(r => r.addEventListener('change', calculateBudget));

    calculateBudget();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
