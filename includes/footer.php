<?php
/**
 * FORT EXPLORER - Global Footer Component
 */
?>
<!-- Global Footer -->
<footer class="fe-footer">
    <div class="container">
        <div class="row g-4 justify-content-between">
            <!-- Brand & Legacy Col -->
            <div class="col-lg-4 col-md-6">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <img src="<?= BASE_URL ?>assets/images/logo.svg" alt="Fort Explorer" style="height:44px; filter:brightness(0) invert(1);">
                </div>
                <p class="small text-white-50 mb-3" style="line-height:1.7;">
                    <?= __('footer_about') ?>
                </p>
                <div class="d-flex gap-2 text-warning fs-5">
                    <span title="Chhatrapati Shivaji Maharaj Heritage"><i class="fa-solid fa-crown"></i></span>
                    <span title="Sahyadri Western Ghats"><i class="fa-solid fa-mountain-sun"></i></span>
                    <span title="Trekking & Exploration"><i class="fa-solid fa-compass"></i></span>
                    <span title="History & Archaeology"><i class="fa-solid fa-landmark"></i></span>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="col-lg-2 col-md-3 col-6">
                <h5><?= is_marathi() ? 'महत्त्वाचे दुवे' : 'Explore' ?></h5>
                <ul class="list-unstyled small d-flex flex-column gap-2 mb-0">
                    <li><a href="<?= BASE_URL ?>index.php"><i class="fa-solid fa-angle-right me-1 text-warning"></i><?= __('nav_home') ?></a></li>
                    <li><a href="<?= BASE_URL ?>explore.php"><i class="fa-solid fa-angle-right me-1 text-warning"></i><?= __('nav_explore') ?></a></li>
                    <li><a href="<?= BASE_URL ?>forts.php"><i class="fa-solid fa-angle-right me-1 text-warning"></i><?= __('nav_forts') ?></a></li>
                    <li><a href="<?= BASE_URL ?>history.php"><i class="fa-solid fa-angle-right me-1 text-warning"></i><?= __('nav_history') ?></a></li>
                    <li><a href="<?= BASE_URL ?>map.php"><i class="fa-solid fa-angle-right me-1 text-warning"></i><?= __('nav_map') ?></a></li>
                    <li><a href="<?= BASE_URL ?>trip-planner.php"><i class="fa-solid fa-angle-right me-1 text-warning"></i><?= __('nav_trip_planner') ?></a></li>
                    <li><a href="<?= BASE_URL ?>community.php"><i class="fa-solid fa-angle-right me-1 text-warning"></i><?= is_marathi() ? 'भ्रमंती कट्टा' : 'Travel Community' ?></a></li>
                    <li><a href="<?= BASE_URL ?>budget-calculator.php"><i class="fa-solid fa-angle-right me-1 text-warning"></i><?= is_marathi() ? 'खर्च गणक' : 'Budget Calculator' ?></a></li>
                    <li><a href="<?= BASE_URL ?>compare.php"><i class="fa-solid fa-angle-right me-1 text-warning"></i><?= is_marathi() ? 'स्थळ तुलना' : 'Compare Places' ?></a></li>
                    <li><a href="<?= BASE_URL ?>trekking-guide.php"><i class="fa-solid fa-angle-right me-1 text-warning"></i><?= is_marathi() ? 'ट्रेकिंग व सुरक्षा' : 'Trek & Safety Guide' ?></a></li>
                </ul>
            </div>

            <!-- Legendary Forts -->
            <div class="col-lg-3 col-md-3 col-6">
                <h5><?= is_marathi() ? 'प्रमुख ऐतिहासिक दुर्ग' : 'Legendary Forts' ?></h5>
                <ul class="list-unstyled small d-flex flex-column gap-2 mb-0">
                    <li><a href="<?= BASE_URL ?>destination.php?slug=raigad-fort"><i class="fa-solid fa-shield-halved me-1 text-warning"></i><?= is_marathi() ? 'किल्ले रायगड (राजधानी)' : 'Raigad Fort (Capital)' ?></a></li>
                    <li><a href="<?= BASE_URL ?>destination.php?slug=shivneri-fort"><i class="fa-solid fa-shield-halved me-1 text-warning"></i><?= is_marathi() ? 'किल्ले शिवनेरी (जन्मस्थान)' : 'Shivneri Fort (Birthplace)' ?></a></li>
                    <li><a href="<?= BASE_URL ?>destination.php?slug=sinhagad-fort"><i class="fa-solid fa-shield-halved me-1 text-warning"></i><?= is_marathi() ? 'किल्ले सिंहगड (कोंढाणा)' : 'Sinhagad Fort (Kondhana)' ?></a></li>
                    <li><a href="<?= BASE_URL ?>destination.php?slug=pratapgad-fort"><i class="fa-solid fa-shield-halved me-1 text-warning"></i><?= is_marathi() ? 'किल्ले प्रतापगड' : 'Pratapgad Fort' ?></a></li>
                    <li><a href="<?= BASE_URL ?>destination.php?slug=rajgad-fort"><i class="fa-solid fa-shield-halved me-1 text-warning"></i><?= is_marathi() ? 'किल्ले राजगड' : 'Rajgad Fort' ?></a></li>
                    <li><a href="<?= BASE_URL ?>destination.php?slug=torna-fort"><i class="fa-solid fa-shield-halved me-1 text-warning"></i><?= is_marathi() ? 'किल्ले तोरणा (प्रचंडगड)' : 'Torna Fort' ?></a></li>
                </ul>
            </div>

            <!-- Categories & Explorer Community -->
            <div class="col-lg-3 col-md-6">
                <h5><?= is_marathi() ? 'दुर्ग संवर्धन व आचारसंहिता' : 'Conservation & Safety' ?></h5>
                <p class="small text-white-50">
                    <?= is_marathi() 
                        ? 'किल्ल्यांचे पावित्र्य राखा. प्लास्टिक कचरा करू नका. ऐतिहासिक वास्तूंचे रक्षण करणे हे प्रत्येक शिवभक्ताचे आणि नागरिकाचे कर्तव्य आहे.' 
                        : 'Preserve our forts and heritage. Avoid single-use plastic, stay on marked trails, and uphold the dignity of Maratha monuments.' ?>
                </p>
                <div class="p-3 rounded" style="background: rgba(0,0,0,0.25); border: 1px solid rgba(197, 155, 39, 0.3);">
                    <div class="small fw-bold text-warning mb-1"><i class="fa-solid fa-mountain me-1"></i>Sahyadri Emergency Helpline:</div>
                    <div class="small text-white-50">Maharashtra Emergency Services: <strong>112</strong></div>
                </div>
            </div>
        </div>

        <!-- Footer Bottom Bar -->
        <div class="fe-footer-bottom d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                &copy; <?= date('Y') ?> <strong><?= APP_NAME ?></strong>. <?= __('copyright') ?>
            </div>
            <div class="small text-white-50">
                Crafted with pride for Maharashtra Tourism & Maratha Heritage.
            </div>
        </div>
    </div>
</footer>

<!-- Offline Indicator for PWA -->
<div id="fe-offline-indicator">
    <i class="fa-solid fa-plane-slash me-1"></i> You are currently offline. Viewing cached heritage content.
</div>

<!-- Bootstrap 5 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Leaflet 1.9.4 JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<!-- Main App Scripts -->
<script src="<?= BASE_URL ?>assets/js/main.js?v=<?= APP_VERSION ?>"></script>

<!-- AI Heritage Assistant Floating Chatbot -->
<script src="<?= BASE_URL ?>assets/js/ai-assistant.js?v=<?= APP_VERSION ?>"></script>

<!-- PWA Service Worker Registration & Online/Offline Listener -->
<script>
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('<?= BASE_URL ?>sw.js')
            .then(reg => console.log('PWA ServiceWorker registered with scope:', reg.scope))
            .catch(err => console.log('ServiceWorker registration error:', err));
    });
}

// Connection Status Monitor
const offlineBanner = document.getElementById('fe-offline-indicator');
function updateOnlineStatus() {
    if (offlineBanner) {
        if (!navigator.onLine) {
            offlineBanner.style.display = 'block';
        } else {
            offlineBanner.style.display = 'none';
        }
    }
}
window.addEventListener('online', updateOnlineStatus);
window.addEventListener('offline', updateOnlineStatus);
updateOnlineStatus();
</script>

</body>
</html>
