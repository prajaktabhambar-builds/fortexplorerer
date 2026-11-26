<?php
/**
 * FORT EXPLORER - Admin Panel Header
 * Enforces admin authorization and renders sidebar navigation.
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/lang.php';

// Strict Admin Access Check
require_admin();

$currentUser = current_user();
$activeScript = basename($_SERVER['PHP_SELF'] ?? 'index.php');
$adminTitle = $adminTitle ?? 'Admin Dashboard - Fort Explorer';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($adminTitle) ?></title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Bootstrap 5 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">

    <!-- Admin CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin.css?v=<?= APP_VERSION ?>">
</head>
<body class="admin-body">

<div class="admin-wrapper">
    <!-- Sidebar -->
    <aside class="admin-sidebar">
        <div class="admin-brand">
            <i class="fa-solid fa-shield-halved text-warning fs-4"></i>
            <div>
                <h4>FORT EXPLORER</h4>
                <span class="small text-white-50" style="font-size:0.75rem;">Admin Management</span>
            </div>
        </div>

        <ul class="admin-menu">
            <li class="admin-menu-heading">Main Overview</li>
            <li class="admin-menu-item">
                <a href="<?= BASE_URL ?>admin/index.php" class="admin-menu-link <?= $activeScript === 'index.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-gauge-high"></i> Dashboard
                </a>
            </li>
            <li class="admin-menu-item">
                <a href="<?= BASE_URL ?>admin/analytics.php" class="admin-menu-link <?= $activeScript === 'analytics.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-chart-pie text-warning"></i> Advanced Analytics
                </a>
            </li>

            <li class="admin-menu-heading">Fort Directory (350+)</li>
            <li class="admin-menu-item">
                <a href="<?= BASE_URL ?>admin/forts.php" class="admin-menu-link <?= in_array($activeScript, ['forts.php', 'fort-form.php'], true) ? 'active' : '' ?>">
                    <i class="fa-solid fa-shield-halved text-warning"></i> Maharashtra Forts
                </a>
            </li>
            <li class="admin-menu-item">
                <a href="<?= BASE_URL ?>admin/fort-form.php" class="admin-menu-link <?= $activeScript === 'fort-form.php' && !isset($_GET['id']) ? 'active' : '' ?>">
                    <i class="fa-solid fa-plus-circle"></i> Add New Fort
                </a>
            </li>
            <li class="admin-menu-item">
                <a href="<?= BASE_URL ?>admin/fort-import-export.php" class="admin-menu-link <?= $activeScript === 'fort-import-export.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-file-csv text-info"></i> Fort Import / Export
                </a>
            </li>

            <li class="admin-menu-heading">Content Management</li>
            <li class="admin-menu-item">
                <a href="<?= BASE_URL ?>admin/destinations.php" class="admin-menu-link <?= in_array($activeScript, ['destinations.php', 'destination-form.php'], true) ? 'active' : '' ?>">
                    <i class="fa-solid fa-mountain-sun"></i> Destinations & Forts
                </a>
            </li>
            <li class="admin-menu-item">
                <a href="<?= BASE_URL ?>admin/destination-form.php" class="admin-menu-link">
                    <i class="fa-solid fa-plus-circle"></i> Add New Destination
                </a>
            </li>
            <li class="admin-menu-item">
                <a href="<?= BASE_URL ?>admin/categories.php" class="admin-menu-link <?= $activeScript === 'categories.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-tags"></i> Categories
                </a>
            </li>
            <li class="admin-menu-item">
                <a href="<?= BASE_URL ?>admin/districts.php" class="admin-menu-link <?= $activeScript === 'districts.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-map-location-dot"></i> Districts
                </a>
            </li>
            <li class="admin-menu-item">
                <a href="<?= BASE_URL ?>admin/historical-events.php" class="admin-menu-link <?= $activeScript === 'historical-events.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-scroll"></i> Timeline Events
                </a>
            </li>

            <li class="admin-menu-heading">Community & Moderation</li>
            <li class="admin-menu-item">
                <a href="<?= BASE_URL ?>admin/community.php" class="admin-menu-link <?= $activeScript === 'community.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-users-gear text-info"></i> Community Stories
                </a>
            </li>
            <li class="admin-menu-item">
                <a href="<?= BASE_URL ?>admin/reviews.php" class="admin-menu-link <?= $activeScript === 'reviews.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-comments"></i> Visitor Reviews
                </a>
            </li>
            <li class="admin-menu-item">
                <a href="<?= BASE_URL ?>admin/users.php" class="admin-menu-link <?= $activeScript === 'users.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-users"></i> Registered Users
                </a>
            </li>

            <li class="admin-menu-heading">Quick Actions</li>
            <li class="admin-menu-item">
                <a href="<?= BASE_URL ?>index.php" target="_blank" class="admin-menu-link">
                    <i class="fa-solid fa-arrow-up-right-from-square text-warning"></i> View Live Site
                </a>
            </li>
            <li class="admin-menu-item">
                <a href="<?= BASE_URL ?>logout.php" class="admin-menu-link text-danger">
                    <i class="fa-solid fa-right-from-bracket"></i> Sign Out
                </a>
            </li>
        </ul>
    </aside>

    <!-- Main Content View -->
    <main class="admin-main">
        <!-- Topbar -->
        <header class="admin-topbar">
            <div>
                <h5 class="mb-0 fw-bold text-dark font-heritage"><?= e($adminTitle) ?></h5>
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="text-end">
                    <span class="d-block fw-bold small text-dark"><?= e($currentUser['name']) ?></span>
                    <span class="badge bg-success-subtle text-success small">Administrator</span>
                </div>
                <div class="rounded-circle bg-success text-white fw-bold d-flex align-items-center justify-content-center" style="width:38px; height:38px;">
                    <?= strtoupper(substr($currentUser['name'], 0, 1)) ?>
                </div>
            </div>
        </header>

        <!-- Admin Body Content -->
        <div class="admin-content">
            <?= render_flash_messages() ?>
