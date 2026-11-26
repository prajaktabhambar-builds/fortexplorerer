/**
 * FORT EXPLORER - Interactive Smart Travel Route Planner Engine
 * Handles multi-stop itinerary creation, route plotting on Leaflet map,
 * Traveling Salesperson (TSP) route optimization, and travel time estimation.
 */

let tripMap = null;
let tripMarkersLayer = null;
let tripRoutePolyline = null;
let tripStops = []; // Array of destination objects in current trip

const CITY_COORDINATES = {
    'Pune': [18.5204, 73.8567],
    'Mumbai': [19.0760, 72.8777],
    'Kolhapur': [16.7050, 74.2433],
    'Nashik': [19.9975, 73.7898],
    'Satara': [17.6805, 73.9997],
    'Chhatrapati Sambhajinagar': [19.8762, 75.3433],
    'Thane': [19.2183, 72.9781]
};

/**
 * Initialize Trip Planner Map
 */
function initTripPlannerMap(mapElementId = 'tripRouteMap') {
    const el = document.getElementById(mapElementId);
    if (!el) return;

    tripMap = L.map(mapElementId).setView([18.5204, 73.8567], 8);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}.png', {
        attribution: '&copy; OpenStreetMap contributors | Fort Explorer Route Engine',
        maxZoom: 18
    }).addTo(tripMap);

    tripMarkersLayer = L.layerGroup().addTo(tripMap);
}

/**
 * Calculate straight-line distance between two coordinates in km
 */
function calculateDistanceBetweenPoints(lat1, lon1, lat2, lon2) {
    const R = 6371; // Earth's radius in km
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a =
        Math.sin(dLat / 2) * Math.sin(dLat / 2) +
        Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
        Math.sin(dLon / 2) * Math.sin(dLon / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return R * c;
}

/**
 * Add a destination to the trip itinerary
 */
function addDestinationToTrip(destination) {
    const existingIndex = tripStops.findIndex(s => s.id == destination.id);
    if (existingIndex !== -1) {
        showToast('This destination is already added to your itinerary.', 'info');
        return;
    }

    tripStops.push({
        id: destination.id,
        name: destination.name,
        category: destination.category_name,
        lat: parseFloat(destination.latitude),
        lng: parseFloat(destination.longitude),
        difficulty: destination.difficulty_level || 'Moderate',
        day: 1,
        notes: ''
    });

    renderTripItineraryUI();
    updateTripMapRoute();
    showToast(`Added ${destination.name} to route.`, 'success');
}

/**
 * Remove a stop from the itinerary
 */
function removeTripStop(index) {
    if (index >= 0 && index < tripStops.length) {
        const removed = tripStops.splice(index, 1)[0];
        renderTripItineraryUI();
        updateTripMapRoute();
        showToast(`Removed ${removed.name} from itinerary.`, 'info');
    }
}

/**
 * Move stop up or down in sequence
 */
function moveTripStop(index, direction) {
    const target = index + direction;
    if (target < 0 || target >= tripStops.length) return;
    const temp = tripStops[index];
    tripStops[index] = tripStops[target];
    tripStops[target] = temp;
    renderTripItineraryUI();
    updateTripMapRoute();
}

/**
 * Update notes or day for a stop
 */
function updateTripStopData(index, field, value) {
    if (tripStops[index]) {
        tripStops[index][field] = value;
        const hiddenStopsInput = document.getElementById('tripStopsJsonInput');
        if (hiddenStopsInput) {
            hiddenStopsInput.value = JSON.stringify(tripStops);
        }
    }
}

/**
 * Smart Route Optimizer (Nearest-Neighbor Heuristic TSP)
 */
function optimizeTripRouteOrder() {
    if (tripStops.length < 2) {
        showToast('Please add at least 2 destinations to optimize the route.', 'info');
        return;
    }

    const startLocName = document.getElementById('tripStartLocationSelect')?.value || 'Pune';
    const startCoord = CITY_COORDINATES[startLocName] || [18.5204, 73.8567];

    const unvisited = [...tripStops];
    const optimized = [];

    // Find nearest stop to starting city
    let currentPoint = { lat: startCoord[0], lng: startCoord[1] };

    while (unvisited.length > 0) {
        let nearestIdx = 0;
        let shortestDist = Infinity;

        for (let i = 0; i < unvisited.length; i++) {
            const dist = calculateDistanceBetweenPoints(currentPoint.lat, currentPoint.lng, unvisited[i].lat, unvisited[i].lng);
            if (dist < shortestDist) {
                shortestDist = dist;
                nearestIdx = i;
            }
        }

        const nextStop = unvisited.splice(nearestIdx, 1)[0];
        optimized.push(nextStop);
        currentPoint = { lat: nextStop.lat, lng: nextStop.lng };
    }

    tripStops = optimized;
    renderTripItineraryUI();
    updateTripMapRoute();
    showToast(`Route optimized from ${startLocName}!`, 'success');
}

/**
 * Render the Itinerary UI list
 */
function renderTripItineraryUI() {
    const container = document.getElementById('itineraryStopsList');
    const emptyState = document.getElementById('itineraryEmptyState');
    const summaryBox = document.getElementById('itinerarySummaryBox');
    const hiddenStopsInput = document.getElementById('tripStopsJsonInput');
    const hiddenTotalKm = document.getElementById('tripTotalKmInput');
    const hiddenEstHours = document.getElementById('tripEstHoursInput');

    if (!container) return;

    if (tripStops.length === 0) {
        if (emptyState) emptyState.style.display = 'block';
        if (summaryBox) summaryBox.style.display = 'none';
        container.innerHTML = '';
        if (hiddenStopsInput) hiddenStopsInput.value = '';
        if (hiddenTotalKm) hiddenTotalKm.value = '0';
        if (hiddenEstHours) hiddenEstHours.value = '0';
        return;
    }

    if (emptyState) emptyState.style.display = 'none';
    if (summaryBox) summaryBox.style.display = 'block';

    const startLocName = document.getElementById('tripStartLocationSelect')?.value || 'Pune';
    const startCoord = CITY_COORDINATES[startLocName] || [18.5204, 73.8567];

    let html = '';
    let totalEstimatedKm = 0;

    // First leg: Starting City -> First Stop
    const firstLeg = Math.round(calculateDistanceBetweenPoints(startCoord[0], startCoord[1], tripStops[0].lat, tripStops[0].lng) * 1.28);
    totalEstimatedKm += firstLeg;

    html += `
        <div class="p-2 mb-2 bg-light rounded-3 text-muted small d-flex align-items-center justify-content-between border">
            <span><i class="fa-solid fa-flag-checkered text-primary me-2"></i><strong>Start:</strong> ${startLocName}</span>
            <span class="badge bg-secondary-subtle text-dark">~${firstLeg} km to first fort</span>
        </div>
    `;

    tripStops.forEach((stop, idx) => {
        if (idx > 0) {
            const prev = tripStops[idx - 1];
            const dist = calculateDistanceBetweenPoints(prev.lat, prev.lng, stop.lat, stop.lng);
            const roadKm = Math.round(dist * 1.28);
            totalEstimatedKm += roadKm;
            html += `<div class="text-center my-2 text-muted small"><i class="fa-solid fa-arrow-down me-1 text-warning"></i>~${roadKm} km driving distance</div>`;
        }

        html += `
            <div class="trip-stop-card border p-3 rounded-3 mb-2 bg-white shadow-sm" data-stop-index="${idx}">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-dark rounded-circle" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">${idx + 1}</span>
                        <h6 class="mb-0 fw-bold text-success">${escapeHtml(stop.name)}</h6>
                    </div>
                    <div class="d-flex gap-1">
                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" onclick="moveTripStop(${idx}, -1)" ${idx === 0 ? 'disabled' : ''} title="Move Up">
                            <i class="fa-solid fa-arrow-up"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" onclick="moveTripStop(${idx}, 1)" ${idx === tripStops.length - 1 ? 'disabled' : ''} title="Move Down">
                            <i class="fa-solid fa-arrow-down"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="removeTripStop(${idx})" title="Remove Stop">
                            <i class="fa-solid fa-trash-can"></i>
                        </button>
                    </div>
                </div>
                <div class="row g-2">
                    <div class="col-sm-4">
                        <label class="form-label small text-muted mb-0">Visit Day</label>
                        <select class="form-select form-select-sm" onchange="updateTripStopData(${idx}, 'day', this.value)">
                            <option value="1" ${stop.day == 1 ? 'selected' : ''}>Day 1</option>
                            <option value="2" ${stop.day == 2 ? 'selected' : ''}>Day 2</option>
                            <option value="3" ${stop.day == 3 ? 'selected' : ''}>Day 3</option>
                            <option value="4" ${stop.day == 4 ? 'selected' : ''}>Day 4</option>
                        </select>
                    </div>
                    <div class="col-sm-8">
                        <label class="form-label small text-muted mb-0">Trek Notes / Morning Start</label>
                        <input type="text" class="form-control form-control-sm" placeholder="e.g. Sunrise trek, carry 3L water" value="${escapeHtml(stop.notes || '')}" oninput="updateTripStopData(${idx}, 'notes', this.value)">
                    </div>
                </div>
            </div>
        `;
    });

    container.innerHTML = html;

    // Driving time @ ~38km/h in Sahyadris + ~2.5 hrs trek time per fort
    const drivingHours = (totalEstimatedKm / 38);
    const trekHours = tripStops.length * 2.5;
    const totalEstHours = Math.round((drivingHours + trekHours) * 10) / 10;

    // Update summary elements
    const distEl = document.getElementById('totalEstimatedDistance');
    const stopsEl = document.getElementById('totalStopsCount');
    const timeEl = document.getElementById('totalEstimatedTime');

    if (distEl) distEl.textContent = `~${totalEstimatedKm} km`;
    if (stopsEl) stopsEl.textContent = tripStops.length;
    if (timeEl) timeEl.textContent = `~${totalEstHours} hrs`;

    if (hiddenStopsInput) hiddenStopsInput.value = JSON.stringify(tripStops);
    if (hiddenTotalKm) hiddenTotalKm.value = totalEstimatedKm;
    if (hiddenEstHours) hiddenEstHours.value = totalEstHours;
}

/**
 * Plot markers and connect them with an itinerary polyline on the Leaflet map
 */
function updateTripMapRoute() {
    if (!tripMap || !tripMarkersLayer) return;

    tripMarkersLayer.clearLayers();
    if (tripRoutePolyline) {
        tripMap.removeLayer(tripRoutePolyline);
        tripRoutePolyline = null;
    }

    if (tripStops.length === 0) return;

    const latlngs = [];
    const bounds = L.latLngBounds();

    // Plot departure city pin
    const startLocName = document.getElementById('tripStartLocationSelect')?.value || 'Pune';
    const startCoord = CITY_COORDINATES[startLocName] || [18.5204, 73.8567];
    latlngs.push(startCoord);
    bounds.extend(startCoord);

    const startIcon = L.divIcon({
        className: 'fe-trip-start-pin',
        html: `
            <div style="background-color:#0284c7;color:#fff;border:2px solid #fff;width:30px;height:30px;border-radius:50%;display:flex;align-items:center;justify-content:center;box-shadow:0 3px 8px rgba(0,0,0,0.3);font-size:12px;">
                <i class="fa-solid fa-house"></i>
            </div>
        `,
        iconSize: [30, 30],
        iconAnchor: [15, 15]
    });
    L.marker(startCoord, { icon: startIcon }).addTo(tripMarkersLayer).bindPopup(`<strong>Base City: ${startLocName}</strong>`);

    // Plot itinerary stops
    tripStops.forEach((stop, idx) => {
        const pt = [stop.lat, stop.lng];
        latlngs.push(pt);
        bounds.extend(pt);

        const numberedIcon = L.divIcon({
            className: 'fe-trip-route-pin',
            html: `
                <div style="background-color: #163820; color: #c59b27; border: 2px solid #ffffff; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px; box-shadow: 0 4px 10px rgba(0,0,0,0.35);">
                    ${idx + 1}
                </div>
            `,
            iconSize: [32, 32],
            iconAnchor: [16, 16]
        });

        const marker = L.marker(pt, { icon: numberedIcon }).addTo(tripMarkersLayer);
        marker.bindPopup(`<strong>Stop ${idx + 1}: ${stop.name}</strong><br>Day ${stop.day} &bull; ${stop.category}`);
    });

    // Draw connecting polyline route
    if (latlngs.length > 1) {
        tripRoutePolyline = L.polyline(latlngs, {
            color: '#c59b27',
            weight: 4,
            opacity: 0.9,
            dashArray: '8, 6',
            lineJoin: 'round'
        }).addTo(tripMap);
    }

    tripMap.fitBounds(bounds, { padding: [40, 40] });
}

function fitTripMapBounds() {
    if (tripMap && tripStops.length > 0) {
        updateTripMapRoute();
    }
}

function escapeHtml(text) {
    if (!text) return '';
    return String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
