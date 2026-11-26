<?php
/**
 * FORT EXPLORER - Global Header Component
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/lang.php';

$currentUser = current_user();
$activePage = basename($_SERVER['PHP_SELF'] ?? 'index.php');
$pageTitle = $pageTitle ?? __( 'site_title' ) . ' - ' . __( 'site_tagline' );
?>
<!DOCTYPE html>
<html lang="<?= e(current_lang()) ?>" <?= is_marathi() ? 'class="lang-mr"' : '' ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Fort Explorer - Discover the glorious forts of Chhatrapati Shivaji Maharaj, waterfalls, temples, and natural wonders across Maharashtra.">
    <title><?= e($pageTitle) ?></title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800;900&family=Inter:wght@400;500;600;700&family=Noto+Sans+Devanagari:wght@400;600;700&display=swap" rel="stylesheet">

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Bootstrap 5.3 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">

    <!-- Leaflet 1.9.4 CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>

    <!-- PWA Manifest & Meta -->
    <link rel="manifest" href="<?= BASE_URL ?>manifest.json">
    <meta name="theme-color" content="#163820">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="apple-touch-icon" href="<?= BASE_URL ?>assets/images/logo.svg">

    <!-- Custom Heritage Design System CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css?v=<?= APP_VERSION ?>">
</head>
<body>

<!-- Navigation Bar -->
<nav class="navbar navbar-expand-lg sticky-top fe-navbar">
    <div class="container">
        <!-- Brand Logo & Name -->
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= BASE_URL ?>index.php">
            <img src="<?= BASE_URL ?>assets/images/logo.svg" alt="Fort Explorer Logo" class="fe-brand-logo">
        </a>

        <!-- Mobile Toggler -->
        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#feNavbarContent" aria-controls="feNavbarContent" aria-expanded="false" aria-label="Toggle navigation">
            <i class="fa-solid fa-bars-staggered fs-3 text-success"></i>
        </button>

        <!-- Navbar Links & Controls -->
        <div class="collapse navbar-collapse" id="feNavbarContent">
            <ul class="navbar-nav mx-auto mb-2 mb-lg-0 gap-1">
                <li class="nav-item">
                    <a class="nav-link fe-nav-link <?= $activePage === 'index.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>index.php">
                        <i class="fa-solid fa-house me-1"></i><?= __('nav_home') ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fe-nav-link <?= $activePage === 'explore.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>explore.php">
                        <i class="fa-solid fa-compass me-1"></i><?= __('nav_explore') ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fe-nav-link <?= $activePage === 'forts.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>forts.php">
                        <i class="fa-solid fa-shield-halved me-1 text-danger"></i><?= __('nav_forts') ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fe-nav-link <?= $activePage === 'history.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>history.php">
                        <i class="fa-solid fa-scroll me-1 text-warning"></i><?= __('nav_history') ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fe-nav-link <?= $activePage === 'map.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>map.php">
                        <i class="fa-solid fa-map-location-dot me-1 text-primary"></i><?= __('nav_map') ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fe-nav-link <?= $activePage === 'trip-planner.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>trip-planner.php">
                        <i class="fa-solid fa-route me-1 text-success"></i><?= __('nav_trip_planner') ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fe-nav-link <?= $activePage === 'community.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>community.php">
                        <i class="fa-solid fa-users-line me-1 text-info"></i><?= is_marathi() ? 'भ्रमंती कट्टा' : 'Community' ?>
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link fe-nav-link dropdown-toggle <?= in_array($activePage, ['budget-calculator.php', 'compare.php', 'trekking-guide.php'], true) ? 'active' : '' ?>" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fa-solid fa-toolbox me-1 text-warning"></i><?= is_marathi() ? 'अधिक साधने' : 'Tools' ?>
                    </a>
                    <ul class="dropdown-menu shadow-sm border-0 py-2">
                        <li><a class="dropdown-item py-1.5 small" href="<?= BASE_URL ?>budget-calculator.php"><i class="fa-solid fa-calculator me-2 text-success"></i><?= is_marathi() ? 'सहल खर्च गणक' : 'Trip Budget Calculator' ?></a></li>
                        <li><a class="dropdown-item py-1.5 small" href="<?= BASE_URL ?>compare.php"><i class="fa-solid fa-code-compare me-2 text-primary"></i><?= is_marathi() ? 'स्थळ तुलना (Compare)' : 'Compare Destinations' ?></a></li>
                        <li><a class="dropdown-item py-1.5 small" href="<?= BASE_URL ?>trekking-guide.php"><i class="fa-solid fa-person-hiking me-2 text-danger"></i><?= is_marathi() ? 'ट्रेकिंग व सुरक्षा मार्गदर्शक' : 'Trek & Safety Guide' ?></a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link fe-nav-link <?= $activePage === 'my-journey.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>my-journey.php">
                        <i class="fa-solid fa-award me-1" style="color:var(--fe-gold);"></i><?= __('nav_my_journey') ?>
                    </a>
                </li>
            </ul>

            <!-- Right Controls: Language Switcher & Auth Dropdown -->
            <div class="d-flex align-items-center gap-3">
                <!-- Language Switcher Toggle -->
                <?php
                $targetLang = is_marathi() ? 'en' : 'mr';
                $langLabel = is_marathi() ? 'English' : 'मराठी';
                $currentQuery = $_GET;
                $currentQuery['lang'] = $targetLang;
                $switchUrl = '?' . http_build_query($currentQuery);
                ?>
                <a href="<?= e($switchUrl) ?>" class="fe-lang-btn" title="Switch language">
                    <i class="fa-solid fa-language text-warning"></i>
                    <span><?= $langLabel ?></span>
                </a>

                <!-- User Account Actions -->
                <?php if ($currentUser): ?>
                    <div class="dropdown">
                        <button class="btn btn-outline-dark d-flex align-items-center gap-2 rounded-pill px-3 py-1.5" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa-solid fa-circle-user fs-5 text-success"></i>
                            <span class="fw-semibold small d-none d-md-inline"><?= e($currentUser['name']) ?></span>
                            <i class="fa-solid fa-chevron-down small text-muted"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 py-2" style="border-radius:12px; min-width:210px;">
                            <li class="px-3 py-2 border-bottom">
                                <p class="mb-0 fw-bold text-truncate text-dark"><?= e($currentUser['name']) ?></p>
                                <span class="badge bg-success-subtle text-success small"><?= e(ucfirst($currentUser['role'])) ?></span>
                            </li>
                            <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>my-journey.php"><i class="fa-solid fa-shoe-prints me-2 text-warning"></i><?= __('nav_my_journey') ?></a></li>
                            <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>trip-planner.php"><i class="fa-solid fa-route me-2 text-primary"></i><?= __('nav_trip_planner') ?></a></li>
                            <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>profile.php"><i class="fa-solid fa-id-card me-2 text-secondary"></i><?= __('nav_profile') ?></a></li>
                            <?php if (is_admin()): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item py-2 text-success fw-bold" href="<?= BASE_URL ?>admin/index.php"><i class="fa-solid fa-gauge-high me-2"></i><?= __('nav_admin') ?></a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item py-2 text-danger" href="<?= BASE_URL ?>logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i><?= __('nav_logout') ?></a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <div class="d-flex align-items-center gap-2">
                        <a href="<?= BASE_URL ?>login.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3 font-weight-bold">
                            <?= __('nav_login') ?>
                        </a>
                        <a href="<?= BASE_URL ?>register.php" class="btn btn-fe-primary btn-sm rounded-pill px-3">
                            <?= __('nav_register') ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<!-- Global Flash Messages Notification Banner -->
<div class="container mt-3">
    <?= render_flash_messages() ?>
</div>
