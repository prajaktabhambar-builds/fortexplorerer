<?php
/**
 * FORT EXPLORER - Homepage
 * Discover History. Explore Heritage. Create Memories.
 */

require_once __DIR__ . '/includes/header.php';

$db = getDB();

// Fetch live database statistics
$stats = [
    'destinations' => 15,
    'forts' => 9,
    'districts' => 10,
    'users' => 120,
    'reviews' => 45
];

$featuredForts = [];
$popularDestinations = [];
$userWishlistIds = [];

if ($db) {
    try {
        // Stats
        $stats['destinations'] = (int)$db->query("SELECT COUNT(*) FROM destinations")->fetchColumn();
        $stats['forts'] = (int)$db->query("SELECT COUNT(*) FROM destinations WHERE is_shivaji_fort = 1")->fetchColumn();
        $stats['districts'] = (int)$db->query("SELECT COUNT(DISTINCT district_id) FROM destinations")->fetchColumn();
        $stats['users'] = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $stats['reviews'] = (int)$db->query("SELECT COUNT(*) FROM reviews WHERE status = 'approved'")->fetchColumn();

        // Featured Shivaji Maharaj Forts
        $stmtForts = $db->query("
            SELECT d.*, c.slug as category_slug, c.name_en as category_name, c.name_mr as category_name_mr,
                   dist.name_en as district_name, dist.name_mr as district_name_mr,
                   COALESCE(AVG(r.rating), 5.0) as avg_rating,
                   COUNT(r.id) as review_count
            FROM destinations d
            JOIN destination_categories c ON d.category_id = c.id
            JOIN districts dist ON d.district_id = dist.id
            LEFT JOIN reviews r ON d.id = r.destination_id AND r.status = 'approved'
            WHERE d.is_shivaji_fort = 1
            GROUP BY d.id
            ORDER BY d.is_featured DESC, d.id ASC
            LIMIT 4
        ");
        $featuredForts = $stmtForts->fetchAll();

        // Popular Tourist Destinations (Non-forts or diverse highlights)
        $stmtPopular = $db->query("
            SELECT d.*, c.slug as category_slug, c.name_en as category_name, c.name_mr as category_name_mr,
                   dist.name_en as district_name, dist.name_mr as district_name_mr,
                   COALESCE(AVG(r.rating), 4.9) as avg_rating,
                   COUNT(r.id) as review_count
            FROM destinations d
            JOIN destination_categories c ON d.category_id = c.id
            JOIN districts dist ON d.district_id = dist.id
            LEFT JOIN reviews r ON d.id = r.destination_id AND r.status = 'approved'
            WHERE d.category_id != 1
            GROUP BY d.id
            ORDER BY d.is_featured DESC, d.id ASC
            LIMIT 4
        ");
        $popularDestinations = $stmtPopular->fetchAll();

        // If user logged in, get their wishlist destination IDs for active heart state
        if (is_logged_in()) {
            $wStmt = $db->prepare("SELECT destination_id FROM wishlists WHERE user_id = ?");
            $wStmt->execute([$_SESSION['user_id']]);
            $userWishlistIds = $wStmt->fetchAll(PDO::FETCH_COLUMN);
        }

    } catch (Exception $e) {
        // Fallback gracefully
    }
}
?>

<!-- 1. HERO SECTION -->
<header class="fe-hero">
    <div class="container">
        <span class="fe-hero-emblem">
            <i class="fa-solid fa-crown text-warning me-1"></i><?= is_marathi() ? 'महाराष्ट्र पर्यटन व ऐतिहासिक वारसा' : 'Maharashtra Tourism & Heritage' ?>
        </span>
        <h1 class="fe-hero-title">
            <?= is_marathi() ? 'किल्ले आणि निसर्गाची भव्य सफर' : 'FORT EXPLORER' ?>
        </h1>
        <p class="fe-hero-tagline">
            <?= __('site_tagline') ?>
        </p>

        <!-- Search Bar with Instant Query Forwarding -->
        <form action="explore.php" method="GET" class="fe-hero-searchbox">
            <i class="fa-solid fa-magnifying-glass text-muted ms-3 fs-5"></i>
            <input type="text" name="q" placeholder="<?= __('search_placeholder') ?>" aria-label="Search" required>
            <button type="submit" class="btn btn-fe-primary">
                <i class="fa-solid fa-compass me-1"></i><?= __('btn_search') ?>
            </button>
        </form>

        <!-- Quick Filter Pills -->
        <div class="fe-hero-pills">
            <span class="text-white-50 small align-self-center me-1"><?= is_marathi() ? 'लोकप्रिय:' : 'Popular:' ?></span>
            <a href="explore.php?cat=shivaji-forts" class="fe-hero-pill"><i class="fa-solid fa-shield-halved me-1 text-danger"></i><?= is_marathi() ? 'शिवकालीन किल्ले' : 'Shivaji Maharaj Forts' ?></a>
            <a href="explore.php?cat=waterfalls" class="fe-hero-pill"><i class="fa-solid fa-water me-1 text-info"></i><?= is_marathi() ? 'धबधबे' : 'Waterfalls' ?></a>
            <a href="explore.php?cat=temples" class="fe-hero-pill"><i class="fa-solid fa-gopuram me-1 text-warning"></i><?= is_marathi() ? 'मंदिरे' : 'Temples' ?></a>
            <a href="explore.php?cat=hill-stations" class="fe-hero-pill"><i class="fa-solid fa-mountain-sun me-1 text-success"></i><?= is_marathi() ? 'थंड हवेची ठिकाणे' : 'Hill Stations' ?></a>
            <a href="explore.php?district=1" class="fe-hero-pill"><i class="fa-solid fa-location-dot me-1 text-danger"></i>Pune</a>
            <a href="explore.php?district=2" class="fe-hero-pill"><i class="fa-solid fa-location-dot me-1 text-danger"></i>Raigad</a>
            <a href="explore.php?district=3" class="fe-hero-pill"><i class="fa-solid fa-location-dot me-1 text-danger"></i>Satara</a>
        </div>
    </div>
</header>

<!-- 2. QUICK CTA & INTRODUCTION -->
<section class="py-5 bg-white border-bottom">
    <div class="container">
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <h3 class="mb-2 fw-bold text-success font-heritage">
                    <?= is_marathi() ? 'सह्याद्रीच्या रांगांमध्ये दडलेला पराक्रमाचा इतिहास अनुभवा' : 'Explore the Inviolable Bastions of Chhatrapati Shivaji Maharaj' ?>
                </h3>
                <p class="text-muted mb-0 lead fs-6">
                    <?= is_marathi()
                        ? 'छत्रपती शिवाजी महाराजांनी सह्याद्रीच्या दुर्गम कड्यांवर स्वराज्य स्थापन केले. फोर्ट एक्सप्लोरर आपल्याला या ऐतिहासिक स्थळांची अचूक माहिती, भौगोलिक नकाशे, प्रवास सूचना आणि वैयक्तिक सहल नियोजनाची सुविधा देते.'
                        : 'From the coronation citadel of Raigad to the birthplace at Shivneri, immerse yourself in authentic historical records, accurate GPS coordinates, real trekking routes, and interactive trip planners.' ?>
                </p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <a href="explore.php" class="btn btn-fe-gold py-2.5 px-4 shadow-sm">
                    <i class="fa-solid fa-compass me-2"></i><?= __('btn_explore') ?>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- 3. FEATURED SHIVAJI MAHARAJ FORTS -->
<section class="py-5" style="background-color: var(--fe-offwhite);">
    <div class="container py-3">
        <div class="section-header">
            <span class="section-tag"><?= is_marathi() ? 'अजिंक्य गडकिल्ले' : 'Sovereign Citadels' ?></span>
            <h2 class="section-title"><?= __('section_featured_forts') ?></h2>
            <p class="section-subtitle"><?= __('section_featured_forts_sub') ?></p>
        </div>

        <div class="row g-4">
            <?php if (!empty($featuredForts)): ?>
                <?php foreach ($featuredForts as $fort): ?>
                    <?php
                    $isWishlisted = in_array($fort['id'], $userWishlistIds);
                    $destName = t_val($fort['name'], $fort['name_mr']);
                    $destDesc = t_val($fort['short_description'], $fort['short_description_mr']);
                    $districtName = t_val($fort['district_name'], $fort['district_name_mr']);
                    $catName = t_val($fort['category_name'], $fort['category_name_mr']);
                    ?>
                    <div class="col-lg-3 col-md-6">
                        <div class="fe-card">
                            <div class="fe-card-img-wrap">
                                <img src="<?= e($fort['primary_image']) ?>" alt="<?= e($destName) ?>" class="fe-card-img" loading="lazy">
                                <span class="fe-card-category-badge" style="border-left-color: #c59b27;">
                                    <i class="fa-solid fa-shield-halved me-1 text-warning"></i><?= e($catName) ?>
                                </span>
                                <span class="fe-card-district-badge">
                                    <i class="fa-solid fa-location-dot me-1 text-danger"></i><?= e($districtName) ?>
                                </span>
                                <button type="button" class="fe-wishlist-toggle-btn <?= $isWishlisted ? 'active' : '' ?>" data-dest-id="<?= $fort['id'] ?>" title="Save to Wishlist">
                                    <i class="<?= $isWishlisted ? 'fa-solid fa-heart text-danger' : 'fa-regular fa-heart' ?>"></i>
                                </button>
                            </div>
                            <div class="fe-card-body">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <?= get_difficulty_badge($fort['difficulty_level']) ?>
                                    <span class="small text-muted"><i class="fa-solid fa-clock me-1"></i><?= $fort['trek_duration_hours'] ?> hrs</span>
                                </div>
                                <h5 class="fe-card-title"><?= e($destName) ?></h5>
                                <div class="mb-2">
                                    <?= render_rating_stars((float)$fort['avg_rating']) ?>
                                </div>
                                <p class="fe-card-desc"><?= e($destDesc) ?></p>
                                <div class="fe-card-footer">
                                    <span class="small fw-semibold text-secondary">
                                        <i class="fa-solid fa-mountain me-1"></i><?= $fort['altitude_meters'] ? $fort['altitude_meters'] . ' m' : 'Hill Top' ?>
                                    </span>
                                    <a href="destination.php?slug=<?= urlencode($fort['slug']) ?>" class="btn btn-sm btn-fe-primary">
                                        <?= __('btn_view_details') ?> <i class="fa-solid fa-arrow-right ms-1"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-4">
                    <p class="text-muted">Destinations database initializing. <a href="install.php" class="btn btn-sm btn-fe-gold ms-2">Click to Seed Data</a></p>
                </div>
            <?php endif; ?>
        </div>

        <div class="text-center mt-4 pt-2">
            <a href="forts.php" class="btn btn-fe-outline">
                <i class="fa-solid fa-shield-halved me-2"></i><?= is_marathi() ? 'सर्व शिवकालीन किल्ले पहा' : 'View All Shivaji Maharaj Forts' ?>
            </a>
        </div>
    </div>
</section>

<!-- 4. HISTORICAL HERITAGE SPOTLIGHT -->
<section class="py-5" style="background: linear-gradient(135deg, #0e2415 0%, #163820 100%); color: #fff;">
    <div class="container py-4">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <span class="badge px-3 py-2 rounded-pill mb-3" style="background: rgba(197, 155, 39, 0.25); border: 1px solid #c59b27; color: #f5d77f; letter-spacing: 1px;">
                    <i class="fa-solid fa-landmark me-2"></i><?= is_marathi() ? 'हिंदवी स्वराज्याची तेजस्वी गाथा' : 'The Saga of Hindavi Swarajya' ?>
                </span>
                <h2 class="display-6 fw-bold text-white font-heritage mb-3">
                    <?= is_marathi() ? 'प्रतिपच्चंद्रलेखेव वर्धिष्णुर्विश्ववंदिता शाहसूनोः शिवस्यैषा मुद्रा भद्राय राजते' : 'The Unbroken Spirit of the Sahyadri Fortress Empire' ?>
                </h2>
                <p class="text-light opacity-75 mb-4 fs-6" style="line-height:1.8;">
                    <?= is_marathi()
                        ? 'छत्रपती शिवाजी महाराजांनी सह्याद्रीच्या खडतर डोंगररांगांचा आणि दऱ्याखोऱ्यांचा वापर करून गनिमी काव्याचे अद्वितीय युद्धतंत्र विकसित केले. ३०० हून अधिक गिरिदुर्ग, जलदुर्ग आणि भुईकोट किल्ल्यांच्या माध्यमातून त्यांनी जुलमी सत्तांना पराभूत करून रयतेचे कल्याणकारी राज्य निर्माण केले.'
                        : 'Chhatrapati Shivaji Maharaj transformed the Sahyadri mountains into an impenetrable defense network. With over 300 hill, coastal, and land forts, he pioneered asymmetric guerilla warfare (Ganimi Kava) and laid the foundation of modern Indian maritime defense.' ?>
                </p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="history.php" class="btn btn-fe-gold">
                        <i class="fa-solid fa-scroll me-2"></i><?= is_marathi() ? 'ऐतिहासिक कालपट उघडा' : 'Explore Historical Timeline' ?>
                    </a>
                    <a href="map.php" class="btn btn-fe-outline-light">
                        <i class="fa-solid fa-map-location-dot me-2"></i><?= is_marathi() ? 'नकाशावर पहा' : 'Interactive Map' ?>
                    </a>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="p-4 rounded-4 shadow-lg" style="background: rgba(255, 255, 255, 0.06); border: 1px solid rgba(197, 155, 39, 0.35); backdrop-filter: blur(10px);">
                    <div class="d-flex align-items-center gap-3 mb-3 border-bottom pb-3 border-white border-opacity-10">
                        <i class="fa-solid fa-quote-left text-warning fs-3"></i>
                        <h5 class="mb-0 text-white font-heritage"><?= is_marathi() ? 'ऐतिहासिक सुवर्ण अक्षरे' : 'Words of Heritage' ?></h5>
                    </div>
                    <blockquote class="blockquote mb-3 text-white fs-5 fst-italic">
                        "दुर्ग म्हणजे राज्याचे मूळ. ज्यांचे दुर्ग, त्याचे राज्य. दुर्ग नसता देशाचे रक्षण कोण करील?"
                    </blockquote>
                    <p class="small text-warning mb-0 text-end">— रामचंद्रपंत अमात्य (आज्ञापत्र / Adnyapatra)</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 5. POPULAR TOURIST DESTINATIONS (Waterfalls, Hill Stations, Temples) -->
<section class="py-5 bg-white">
    <div class="container py-3">
        <div class="section-header">
            <span class="section-tag"><?= is_marathi() ? 'निसर्ग व तीर्थक्षेत्रे' : 'Nature & Sanctuaries' ?></span>
            <h2 class="section-title"><?= __('section_popular_destinations') ?></h2>
            <p class="section-subtitle"><?= __('section_popular_destinations_sub') ?></p>
        </div>

        <div class="row g-4">
            <?php if (!empty($popularDestinations)): ?>
                <?php foreach ($popularDestinations as $dest): ?>
                    <?php
                    $isWishlisted = in_array($dest['id'], $userWishlistIds);
                    $destName = t_val($dest['name'], $dest['name_mr']);
                    $destDesc = t_val($dest['short_description'], $dest['short_description_mr']);
                    $districtName = t_val($dest['district_name'], $dest['district_name_mr']);
                    $catName = t_val($dest['category_name'], $dest['category_name_mr']);
                    ?>
                    <div class="col-lg-3 col-md-6">
                        <div class="fe-card">
                            <div class="fe-card-img-wrap">
                                <img src="<?= e($dest['primary_image']) ?>" alt="<?= e($destName) ?>" class="fe-card-img" loading="lazy">
                                <span class="fe-card-category-badge">
                                    <i class="fa-solid fa-location-dot me-1 text-warning"></i><?= e($catName) ?>
                                </span>
                                <span class="fe-card-district-badge">
                                    <i class="fa-solid fa-map-pin me-1 text-danger"></i><?= e($districtName) ?>
                                </span>
                                <button type="button" class="fe-wishlist-toggle-btn <?= $isWishlisted ? 'active' : '' ?>" data-dest-id="<?= $dest['id'] ?>" title="Save to Wishlist">
                                    <i class="<?= $isWishlisted ? 'fa-solid fa-heart text-danger' : 'fa-regular fa-heart' ?>"></i>
                                </button>
                            </div>
                            <div class="fe-card-body">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="badge bg-secondary-subtle text-dark px-2 py-1 rounded-pill small">
                                        <i class="fa-solid fa-calendar-check me-1 text-primary"></i><?= e($dest['best_season']) ?>
                                    </span>
                                </div>
                                <h5 class="fe-card-title"><?= e($destName) ?></h5>
                                <div class="mb-2">
                                    <?= render_rating_stars((float)$dest['avg_rating']) ?>
                                </div>
                                <p class="fe-card-desc"><?= e($destDesc) ?></p>
                                <div class="fe-card-footer">
                                    <span class="small text-muted">
                                        <i class="fa-solid fa-car me-1"></i><?= $dest['distance_from_pune_km'] ? $dest['distance_from_pune_km'] . ' km from Pune' : 'Accessible' ?>
                                    </span>
                                    <a href="destination.php?slug=<?= urlencode($dest['slug']) ?>" class="btn btn-sm btn-fe-primary">
                                        <?= __('btn_view_details') ?> <i class="fa-solid fa-arrow-right ms-1"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="text-center mt-4 pt-2">
            <a href="explore.php" class="btn btn-fe-outline">
                <i class="fa-solid fa-compass me-2"></i><?= __('btn_explore') ?>
            </a>
        </div>
    </div>
</section>

<!-- 6. WHY EXPLORE WITH FORT EXPLORER? -->
<section class="py-5" style="background-color: var(--fe-beige);">
    <div class="container py-3">
        <div class="section-header">
            <span class="section-tag"><?= is_marathi() ? 'वैशिष्ट्ये' : 'Why Choose Us' ?></span>
            <h2 class="section-title"><?= __('section_why_explore') ?></h2>
            <p class="section-subtitle"><?= is_marathi() ? 'महाराष्ट्राच्या पर्यटनाला अचूकता, वारसा आणि तंत्रज्ञानाची जोड' : 'Built for passionate trekkers, history lovers, and conscious explorers.' ?></p>
        </div>

        <div class="row g-4">
            <div class="col-lg-3 col-md-6">
                <div class="bg-white p-4 rounded-4 shadow-sm h-100 border-top border-4 border-success">
                    <div class="stat-icon mb-3">
                        <i class="fa-solid fa-certificate text-success"></i>
                    </div>
                    <h5 class="fw-bold font-heritage mb-2"><?= is_marathi() ? 'प्रमाणित इतिहास' : 'Verified Historical Accuracy' ?></h5>
                    <p class="text-muted small mb-0">
                        <?= is_marathi()
                            ? 'अधिकृत संदर्भांवर आधारित अचूक ऐतिहासिक नोंदी, तारखा आणि छत्रपती शिवरायांचे कर्तृत्व.'
                            : 'Authentic information sourced from verified archaeological records and historical archives with zero fabrication.' ?>
                    </p>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="bg-white p-4 rounded-4 shadow-sm h-100 border-top border-4 border-warning">
                    <div class="stat-icon mb-3">
                        <i class="fa-solid fa-map-location-dot text-warning"></i>
                    </div>
                    <h5 class="fw-bold font-heritage mb-2"><?= is_marathi() ? 'संवादी नकाशे' : 'Interactive Maps & GPS' ?></h5>
                    <p class="text-muted small mb-0">
                        <?= is_marathi()
                            ? 'प्रत्येक किल्ल्याचे आणि पर्यटन स्थळाचे अचूक स्थान, दिशा आणि गुगल मॅप्स थेट मार्ग.'
                            : 'Powered by Leaflet.js and OpenStreetMap with exact GPS coordinates and external navigation links.' ?>
                    </p>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="bg-white p-4 rounded-4 shadow-sm h-100 border-top border-4 border-primary">
                    <div class="stat-icon mb-3">
                        <i class="fa-solid fa-route text-primary"></i>
                    </div>
                    <h5 class="fw-bold font-heritage mb-2"><?= is_marathi() ? 'सहल नियोजन' : 'Smart Trip Planner' ?></h5>
                    <p class="text-muted small mb-0">
                        <?= is_marathi()
                            ? 'एकाच वेळी अनेक किल्ल्यांची सफर जोडा, अंतर मोजा आणि सुंदर प्रवास आराखडा तयार करा.'
                            : 'Organize multi-stop itineraries, plot the trail on map, estimate driving distances, and save your plans.' ?>
                    </p>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="bg-white p-4 rounded-4 shadow-sm h-100 border-top border-4 border-danger">
                    <div class="stat-icon mb-3">
                        <i class="fa-solid fa-award text-danger"></i>
                    </div>
                    <h5 class="fw-bold font-heritage mb-2"><?= is_marathi() ? 'वैयक्तिक प्रवास डायरी' : 'My Travel Journey' ?></h5>
                    <p class="text-muted small mb-0">
                        <?= is_marathi()
                            ? 'भेट दिलेल्या किल्ल्यांची नोंद ठेवा, फोटो अपलोड करा, अनुभव लिहा आणि सन्मान बॅजेस मिळवा.'
                            : 'Mark visited places, rate experiences, upload travel photography, and unlock prestigious Sahyadri badges.' ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 7. LIVE DATABASE STATISTICS COUNTERS -->
<section class="py-5 bg-white border-top border-bottom">
    <div class="container py-2">
        <div class="row g-4 justify-content-center">
            <div class="col-lg-2 col-md-4 col-6">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fa-solid fa-mountain-sun"></i>
                    </div>
                    <div class="stat-number"><?= $stats['destinations'] ?>+</div>
                    <div class="stat-label"><?= __('stat_destinations') ?></div>
                </div>
            </div>

            <div class="col-lg-2 col-md-4 col-6">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fa-solid fa-shield-halved text-danger"></i>
                    </div>
                    <div class="stat-number"><?= $stats['forts'] ?>+</div>
                    <div class="stat-label"><?= __('stat_forts') ?></div>
                </div>
            </div>

            <div class="col-lg-2 col-md-4 col-6">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fa-solid fa-map-location text-primary"></i>
                    </div>
                    <div class="stat-number"><?= $stats['districts'] ?></div>
                    <div class="stat-label"><?= __('stat_districts') ?></div>
                </div>
            </div>

            <div class="col-lg-2 col-md-4 col-6">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fa-solid fa-users text-success"></i>
                    </div>
                    <div class="stat-number"><?= $stats['users'] ?>+</div>
                    <div class="stat-label"><?= __('stat_explorers') ?></div>
                </div>
            </div>

            <div class="col-lg-2 col-md-4 col-6">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fa-solid fa-comments text-warning"></i>
                    </div>
                    <div class="stat-number"><?= $stats['reviews'] ?>+</div>
                    <div class="stat-label"><?= __('stat_reviews') ?></div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 8. COMMUNITY TESTIMONIALS -->
<section class="py-5" style="background-color: var(--fe-offwhite);">
    <div class="container py-3">
        <div class="section-header">
            <span class="section-tag"><?= is_marathi() ? 'पर्यटकांचे अनुभव' : 'Traveler Voices' ?></span>
            <h2 class="section-title"><?= is_marathi() ? 'दुर्गप्रेमींचे मनोगत' : 'Words from Fellow Explorers' ?></h2>
            <p class="section-subtitle"><?= is_marathi() ? 'सह्याद्रीची भ्रमंती करणाऱ्या सदस्यांनी नोंदवलेले अनुभव' : 'Real experiences shared by passionate Sahyadri trekkers.' ?></p>
        </div>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="bg-white p-4 rounded-4 shadow-sm h-100 d-flex flex-column justify-content-between border">
                    <div>
                        <div class="text-warning mb-3">
                            <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
                        </div>
                        <p class="text-secondary small fst-italic mb-3">
                            "The historical accuracy and verified timing information for Raigad and Shivneri made our family trip hassle-free. The distance indicators and food details were spot on!"
                        </p>
                    </div>
                    <div class="d-flex align-items-center gap-3 pt-3 border-top">
                        <div class="rounded-circle bg-success-subtle text-success fw-bold d-flex align-items-center justify-content-center" style="width:42px;height:42px;">TP</div>
                        <div>
                            <h6 class="mb-0 fw-bold">Tanmay Patil</h6>
                            <span class="small text-muted">Pune, Maharashtra</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="bg-white p-4 rounded-4 shadow-sm h-100 d-flex flex-column justify-content-between border">
                    <div>
                        <div class="text-warning mb-3">
                            <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
                        </div>
                        <p class="text-secondary small fst-italic mb-3">
                            "The Trip Planner allowed our trekking group to map Sinhagad, Rajgad, and Torna in one weekend. The elevation numbers and trek duration estimates are extremely helpful."
                        </p>
                    </div>
                    <div class="d-flex align-items-center gap-3 pt-3 border-top">
                        <div class="rounded-circle bg-warning-subtle text-warning fw-bold d-flex align-items-center justify-content-center" style="width:42px;height:42px;">PD</div>
                        <div>
                            <h6 class="mb-0 fw-bold">Pooja Deshmukh</h6>
                            <span class="small text-muted">Mumbai, Maharashtra</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="bg-white p-4 rounded-4 shadow-sm h-100 d-flex flex-column justify-content-between border">
                    <div>
                        <div class="text-warning mb-3">
                            <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
                        </div>
                        <p class="text-secondary small fst-italic mb-3">
                            "Being able to switch seamlessly to Marathi makes this platform feel authentically rooted in our Maharashtra culture. Truly a wonderful tribute to Chhatrapati Shivaji Maharaj."
                        </p>
                    </div>
                    <div class="d-flex align-items-center gap-3 pt-3 border-top">
                        <div class="rounded-circle bg-primary-subtle text-primary fw-bold d-flex align-items-center justify-content-center" style="width:42px;height:42px;">SK</div>
                        <div>
                            <h6 class="mb-0 fw-bold">Sagar Kulkarni</h6>
                            <span class="small text-muted">Satara, Maharashtra</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
