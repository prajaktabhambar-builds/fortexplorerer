/**
 * FORT EXPLORER - Interactive Leaflet & OpenStreetMap Explorer
 * Supports 367+ Forts & Destinations across 36 Districts of Maharashtra
 */

let feMap = null;
let feMarkersLayer = null;
let allDestinations = [];
let userLocationMarker = null;
let activeCategoryFilter = 'all';
let activeDistrictFilter = 'all';
let activeSearchQuery = '';

// Fort category and styling map
const FORT_CATEGORY_STYLES = {
    'hill_fort': { color: '#163820', icon: 'fa-mountain', label: 'Giridurg / Hill Fort' },
    'sea_fort': { color: '#0284c7', icon: 'fa-water', label: 'Jaldurg / Sea Fort' },
    'island_fort': { color: '#0369a1', icon: 'fa-anchor', label: 'Island Fort' },
    'land_fort': { color: '#8b2616', icon: 'fa-landmark', label: 'Bhuikot / Land Fort' },
    'forest_fort': { color: '#15803d', icon: 'fa-tree', label: 'Vanadurg / Forest Fort' },
    'coastal_fort': { color: '#d97706', icon: 'fa-ship', label: 'Coastal Fort' },
    'fortified_heritage': { color: '#4b5563', icon: 'fa-archway', label: 'Gadhi / Heritage' }
};

const DEST_CATEGORY_STYLES = {
    'historical-monuments': { color: '#6d28d9', icon: 'fa-monument', label: 'Monument' },
    'hill-stations': { color: '#15803d', icon: 'fa-mountain-sun', label: 'Hill Station' },
    'waterfalls': { color: '#0284c7', icon: 'fa-water', label: 'Waterfall' },
    'temples': { color: '#b45309', icon: 'fa-gopuram', label: 'Temple' },
    'nature-adventure': { color: '#0f766e', icon: 'fa-person-hiking', label: 'Adventure' }
};

/**
 * Initialize Interactive Map
 */
function initFortExplorerMap(destinationsData, mapElementId = 'fe-map', defaultZoom = 7) {
    const mapContainer = document.getElementById(mapElementId);
    if (!mapContainer) return;

    allDestinations = destinationsData || [];

    // Center of Maharashtra
    const defaultCenter = [19.0000, 75.0000];

    feMap = L.map(mapElementId, {
        zoomControl: true,
        scrollWheelZoom: true
    }).setView(defaultCenter, defaultZoom);

    // Add OpenStreetMap Tile Layer
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors | Fort Explorer Maharashtra',
        maxZoom: 18
    }).addTo(feMap);

    feMarkersLayer = L.layerGroup().addTo(feMap);

    // Render initial markers
    applyMapFilters();

    // Bind Category Filter Pills
    const filterButtons = document.querySelectorAll('[data-map-filter]');
    filterButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            filterButtons.forEach(b => {
                b.classList.remove('active', 'btn-fe-primary');
                if (!b.classList.contains('border-warning')) {
                    b.classList.add('btn-outline-secondary');
                }
            });

            btn.classList.add('active', 'btn-fe-primary');
            btn.classList.remove('btn-outline-secondary');

            activeCategoryFilter = btn.getAttribute('data-map-filter');
            applyMapFilters();
        });
    });

    // District Dropdown Filter
    const districtSelect = document.getElementById('mapDistrictSelect');
    if (districtSelect) {
        districtSelect.addEventListener('change', (e) => {
            activeDistrictFilter = e.target.value;
            applyMapFilters();
        });
    }

    // Search Input Filter
    const searchInput = document.getElementById('mapSearchInput');
    if (searchInput) {
        let debounceTimer = null;
        searchInput.addEventListener('input', (e) => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                activeSearchQuery = e.target.value.trim().toLowerCase();
                applyMapFilters();
            }, 250);
        });
    }

    // Locate Me handler
    const locateBtn = document.getElementById('btnLocateMe');
    if (locateBtn) {
        locateBtn.addEventListener('click', () => {
            locateUserOnMap();
        });
    }
}

/**
 * Filter destinations based on category, district, and search query
 */
function applyMapFilters() {
    let filtered = allDestinations.filter(d => {
        // District filter
        if (activeDistrictFilter !== 'all' && String(d.district_id) !== String(activeDistrictFilter)) {
            return false;
        }

        // Category filter
        if (activeCategoryFilter === 'unesco') {
            if (parseInt(d.is_unesco_site) !== 1) return false;
        } else if (activeCategoryFilter === 'shivaji') {
            if (parseInt(d.is_shivaji_fort) !== 1) return false;
        } else if (activeCategoryFilter.startsWith('fc_')) {
            const fcCode = activeCategoryFilter.replace('fc_', '');
            if (d.fort_cat_code !== fcCode) return false;
        } else if (activeCategoryFilter !== 'all') {
            if (d.category_slug !== activeCategoryFilter) return false;
        }

        // Search text filter
        if (activeSearchQuery !== '') {
            const nameEn = (d.name || '').toLowerCase();
            const nameMr = (d.name_mr || '').toLowerCase();
            const taluka = (d.taluka || '').toLowerCase();
            const distName = (d.district_name || '').toLowerCase();
            if (!nameEn.includes(activeSearchQuery) && 
                !nameMr.includes(activeSearchQuery) && 
                !taluka.includes(activeSearchQuery) && 
                !distName.includes(activeSearchQuery)) {
                return false;
            }
        }

        return true;
    });

    renderMapMarkers(filtered);
    updateSidebarList(filtered);
}

/**
 * Render Markers for Given Destinations Array
 */
function renderMapMarkers(destList) {
    if (!feMarkersLayer) return;
    feMarkersLayer.clearLayers();

    if (destList.length === 0) return;

    const bounds = L.latLngBounds();

    destList.forEach(dest => {
        const lat = parseFloat(dest.latitude);
        const lng = parseFloat(dest.longitude);
        if (isNaN(lat) || isNaN(lng)) return;

        // Determine styling
        let style = { color: '#163820', icon: 'fa-shield-halved', label: 'Fort' };
        if (dest.fort_cat_code && FORT_CATEGORY_STYLES[dest.fort_cat_code]) {
            style = FORT_CATEGORY_STYLES[dest.fort_cat_code];
        } else if (dest.category_slug && DEST_CATEGORY_STYLES[dest.category_slug]) {
            style = DEST_CATEGORY_STYLES[dest.category_slug];
        }

        // UNESCO Gold pin styling
        const isUnesco = parseInt(dest.is_unesco_site) === 1;
        const pinBg = isUnesco ? '#c59b27' : style.color;
        const pinBorder = isUnesco ? '#ffffff' : '#ffffff';
        const iconClass = isUnesco ? 'fa-crown' : style.icon;

        // Custom Leaflet DivIcon
        const customIcon = L.divIcon({
            className: 'fe-custom-pin-marker',
            html: `
                <div style="background-color: ${pinBg}; width: 32px; height: 32px; border-radius: 50% 50% 50% 0; transform: rotate(-45deg); display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(0,0,0,0.35); border: 2px solid ${pinBorder};">
                    <i class="fa-solid ${iconClass}" style="transform: rotate(45deg); color: #ffffff; font-size: 13px;"></i>
                </div>
            `,
            iconSize: [32, 32],
            iconAnchor: [16, 32],
            popupAnchor: [0, -30]
        });

        const marker = L.marker([lat, lng], { icon: customIcon });

        // Popup content
        const directionUrl = `https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}`;
        const unescoBadge = isUnesco 
            ? `<span class="badge bg-warning text-dark mb-1 me-1"><i class="fa-solid fa-crown me-1"></i>UNESCO Nominated</span>` 
            : '';
        const fortTypeBadge = dest.fort_cat_trad 
            ? `<span class="badge mb-1" style="background-color: ${style.color}; font-size: 0.7rem;">${dest.fort_cat_trad} (${dest.fort_cat_name || ''})</span>`
            : `<span class="badge mb-1" style="background-color: ${style.color}; font-size: 0.7rem;">${dest.category_name || style.label}</span>`;

        const altitudeInfo = dest.altitude_meters 
            ? `<span class="text-secondary small fw-semibold"><i class="fa-solid fa-mountain me-1"></i>${dest.altitude_meters} m</span>` 
            : '';

        const popupContent = `
            <div class="map-popup-inner" style="min-width: 220px;">
                <img src="${dest.primary_image || 'assets/images/default-fort.jpg'}" alt="${dest.name}" class="map-popup-img" style="width: 100%; height: 115px; object-fit: cover; border-radius: 6px 6px 0 0;">
                <div class="map-popup-content p-2">
                    <div class="mb-1">${unescoBadge}${fortTypeBadge}</div>
                    <h6 class="map-popup-title mb-1 fw-bold text-dark" style="font-size: 0.95rem;">${dest.name}</h6>
                    <div class="text-muted small mb-1">
                        <i class="fa-solid fa-location-dot me-1 text-danger"></i>${dest.district_name || ''}${dest.taluka ? ' (' + dest.taluka + ')' : ''}
                    </div>
                    ${altitudeInfo ? `<div class="mb-2">${altitudeInfo}</div>` : ''}
                    <div class="d-flex gap-2 mt-2 pt-1 border-top">
                        <a href="destination.php?slug=${encodeURIComponent(dest.slug)}" class="btn btn-sm btn-fe-primary py-1 px-2 flex-grow-1 text-center" style="font-size:0.75rem;">
                            <i class="fa-solid fa-circle-info me-1"></i>Details
                        </a>
                        <a href="${directionUrl}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-dark py-1 px-2" style="font-size:0.75rem;" title="Directions on Google Maps">
                            <i class="fa-solid fa-diamond-turn-right me-1"></i>Route
                        </a>
                    </div>
                </div>
            </div>
        `;

        marker.bindPopup(popupContent, { className: 'map-marker-popup', maxWidth: 290 });
        feMarkersLayer.addLayer(marker);
        bounds.extend([lat, lng]);
    });

    if (destList.length > 0 && feMap) {
        feMap.fitBounds(bounds, { padding: [40, 40], maxZoom: 12 });
    }
}

/**
 * Dynamically filter the sidebar list items
 */
function updateSidebarList(filteredList) {
    const listContainer = document.getElementById('mapDestList');
    const counterBadge = document.getElementById('mapListCounter');
    if (counterBadge) {
        counterBadge.innerText = filteredList.length;
    }

    if (!listContainer) return;

    const visibleIds = new Set(filteredList.map(d => String(d.id)));
    const allItems = listContainer.querySelectorAll('.map-list-item');

    let shownCount = 0;
    allItems.forEach(item => {
        const id = item.getAttribute('data-dest-id');
        if (visibleIds.has(id)) {
            item.style.display = '';
            shownCount++;
        } else {
            item.style.display = 'none';
        }
    });

    // Empty state if nothing matches
    let emptyNotice = document.getElementById('mapListEmpty');
    if (shownCount === 0) {
        if (!emptyNotice) {
            emptyNotice = document.createElement('div');
            emptyNotice = document.createElement('div');
            emptyNotice.id = 'mapListEmpty';
            emptyNotice.className = 'text-center text-muted p-4 small';
            emptyNotice.innerHTML = '<i class="fa-solid fa-folder-open mb-2 fs-4 text-secondary d-block"></i>No locations match the selected filters.';
            listContainer.appendChild(emptyNotice);
        }
        emptyNotice.style.display = '';
    } else if (emptyNotice) {
        emptyNotice.style.display = 'none';
    }
}

/**
 * Request user's geolocation only with explicit permission
 */
function locateUserOnMap() {
    if (!navigator.geolocation) {
        alert('Geolocation is not supported by your browser.');
        return;
    }

    const locateBtn = document.getElementById('btnLocateMe');
    if (locateBtn) locateBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i>Locating...';

    navigator.geolocation.getCurrentPosition(
        (position) => {
            const userLat = position.coords.latitude;
            const userLng = position.coords.longitude;

            if (userLocationMarker) {
                feMap.removeLayer(userLocationMarker);
            }

            const userIcon = L.divIcon({
                className: 'fe-user-location-pin',
                html: `
                    <div style="background-color: #0284c7; width: 22px; height: 22px; border-radius: 50%; border: 3px solid #ffffff; box-shadow: 0 0 0 6px rgba(2, 132, 199, 0.35); animation: pulse 2s infinite;"></div>
                `,
                iconSize: [22, 22],
                iconAnchor: [11, 11]
            });

            userLocationMarker = L.marker([userLat, userLng], { icon: userIcon }).addTo(feMap);
            userLocationMarker.bindPopup("<strong>You are here!</strong><br>Showing proximity to Maharashtra forts & heritage.").openPopup();

            feMap.setView([userLat, userLng], 10);
            if (locateBtn) locateBtn.innerHTML = '<i class="fa-solid fa-crosshairs me-1 text-success"></i>Located';
        },
        (error) => {
            let msg = 'Unable to retrieve your location.';
            if (error.code === error.PERMISSION_DENIED) {
                msg = 'Location access was denied. Please allow location permissions in your browser.';
            }
            alert(msg);
            if (locateBtn) locateBtn.innerHTML = '<i class="fa-solid fa-crosshairs me-1"></i>Locate Me';
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 }
    );
}
