<?php
/**
 * FORT EXPLORER - 404 Not Found Page
 * Heritage-themed lost traveler landing page with helpful shortcuts.
 */

http_response_code(404);
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5 text-center my-5">
    <div style="max-width: 650px; margin: 0 auto;" class="bg-white p-5 rounded-5 shadow-sm border">
        <div class="rounded-circle bg-warning bg-opacity-10 d-inline-flex align-items-center justify-content-center p-4 mb-3" style="width: 100px; height: 100px;">
            <i class="fa-solid fa-compass text-warning fa-3x fa-spin" style="--fa-animation-duration: 15s;"></i>
        </div>

        <h1 class="display-4 fw-bold font-heritage text-success mb-2">404</h1>
        <h3 class="font-heritage fw-bold text-dark mb-3">
            <?= is_marathi() ? 'सह्याद्रीच्या धुक्यात वाट चुकलात का?' : 'Lost in the Sahyadri Mists?' ?>
        </h3>
        <p class="text-secondary fs-6 mb-4" style="line-height: 1.8;">
            <?= is_marathi() 
                ? 'आपण शोधत असलेले पान अस्तित्वात नाही किंवा हलवण्यात आले आहे. चला, स्वराज्याच्या मूळ वाटेवर परत जाऊया.' 
                : 'The fort trail or page you are looking for does not exist or may have been moved. Let us guide you back to the main route.' ?>
        </p>

        <!-- Quick Search -->
        <form action="explore.php" method="GET" class="input-group mb-4 shadow-sm">
            <input type="text" name="q" class="form-control" placeholder="Search forts, waterfalls, temples..." required>
            <button class="btn btn-fe-primary px-4" type="submit">
                <i class="fa-solid fa-magnifying-glass me-1"></i>Search
            </button>
        </form>

        <!-- Quick Links -->
        <div class="d-flex flex-wrap gap-2 justify-content-center">
            <a href="index.php" class="btn btn-fe-gold rounded-pill px-4">
                <i class="fa-solid fa-house me-1"></i>Home
            </a>
            <a href="explore.php" class="btn btn-outline-success rounded-pill px-4">
                <i class="fa-solid fa-mountain me-1"></i>Explore All Places
            </a>
            <a href="forts.php" class="btn btn-outline-dark rounded-pill px-4">
                <i class="fa-solid fa-shield-halved me-1 text-danger"></i>Shivaji Forts
            </a>
            <a href="community.php" class="btn btn-outline-primary rounded-pill px-4">
                <i class="fa-solid fa-users me-1"></i>Community
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
