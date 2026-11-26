<?php
/**
 * FORT EXPLORER - Official Explorer Travel Summary & Certificate
 * Print-ready and downloadable explorer record card of Maharashtra expeditions.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

require_login();
$db = getDB();
$userId = $_SESSION['user_id'];
$currentUser = current_user();

$visitedList = [];
$achievements = [];
$fortsConquered = 0;
$districtsExplored = 0;
$totalAltitude = 0;

if ($db) {
    // Visited records
    $vStmt = $db->prepare("
        SELECT vp.*, d.name, d.name_mr, d.is_shivaji_fort, d.altitude_meters,
               c.name_en AS category_name, dist.name_en AS district_name
        FROM visited_places vp
        JOIN destinations d ON vp.destination_id = d.id
        JOIN destination_categories c ON d.category_id = c.id
        JOIN districts dist ON d.district_id = dist.id
        WHERE vp.user_id = ?
        ORDER BY vp.visit_date ASC
    ");
    $vStmt->execute([$userId]);
    $visitedList = $vStmt->fetchAll();

    foreach ($visitedList as $v) {
        if ($v['is_shivaji_fort']) $fortsConquered++;
        if ($v['altitude_meters']) $totalAltitude += (int)$v['altitude_meters'];
    }

    $distStmt = $db->prepare("SELECT COUNT(DISTINCT d.district_id) FROM visited_places vp JOIN destinations d ON vp.destination_id = d.id WHERE vp.user_id = ?");
    $distStmt->execute([$userId]);
    $districtsExplored = (int)$distStmt->fetchColumn();

    // Achievements earned
    $aStmt = $db->prepare("
        SELECT a.*, ua.awarded_at
        FROM achievements a
        JOIN user_achievements ua ON a.id = ua.achievement_id
        WHERE ua.user_id = ?
        ORDER BY ua.awarded_at ASC
    ");
    $aStmt->execute([$userId]);
    $achievements = $aStmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sahyadri Explorer Certificate - <?= e($currentUser['name']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f3ede2;
            font-family: 'Inter', sans-serif;
            color: #1e2124;
            padding: 30px 15px;
        }
        .cert-container {
            max-width: 850px;
            margin: 0 auto;
            background: #ffffff;
            border: 8px double #c59b27;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            position: relative;
        }
        .font-heritage {
            font-family: 'Cinzel', serif;
        }
        .stat-box {
            background: #faf8f5;
            border: 1px solid #e7ded0;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }
            .cert-container {
                border: 4px double #c59b27;
                box-shadow: none;
                padding: 25px;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>

<div class="text-center mb-4 no-print">
    <button type="button" class="btn btn-dark btn-sm rounded-pill px-4 me-2" onclick="window.print()">
        <i class="fa-solid fa-print me-1"></i>Print / Save as PDF
    </button>
    <a href="my-journey.php" class="btn btn-outline-secondary btn-sm rounded-pill px-4">
        <i class="fa-solid fa-arrow-left me-1"></i>Back to My Journey
    </a>
</div>

<div class="cert-container">
    <!-- Emblem Header -->
    <div class="text-center mb-4 pb-3 border-bottom border-2" style="border-color: #c59b27 !important;">
        <i class="fa-solid fa-shield-halved fa-3x text-warning mb-2"></i>
        <h2 class="font-heritage fw-bold tracking-wide mb-1" style="color: #163820;">FORT EXPLORER</h2>
        <h5 class="text-uppercase small tracking-wider text-muted mb-0">Maharashtra Tourism & Heritage Exploration Platform</h5>
        <div class="badge bg-dark text-warning px-3 py-1 mt-2 font-heritage">Official Sahyadri Explorer Record</div>
    </div>

    <!-- Explorer Name & Title -->
    <div class="text-center my-4">
        <span class="text-muted small text-uppercase tracking-widest d-block">This certifies that explorer</span>
        <h2 class="display-6 fw-bold font-heritage my-2" style="color: #8b2616;"><?= e($currentUser['name']) ?></h2>
        <p class="text-muted small">Home District: <strong><?= e($currentUser['home_district'] ?? 'Pune') ?></strong> &bull; Member ID: <strong>FE-<?= str_pad($userId, 5, '0', STR_PAD_LEFT) ?></strong></p>
    </div>

    <!-- Metrics Summary -->
    <div class="row g-3 mb-4">
        <div class="col-3">
            <div class="stat-box">
                <span class="small text-muted d-block">Destinations</span>
                <strong class="fs-4 font-heritage" style="color: #163820;"><?= count($visitedList) ?></strong>
            </div>
        </div>
        <div class="col-3">
            <div class="stat-box">
                <span class="small text-muted d-block">Forts Conquered</span>
                <strong class="fs-4 font-heritage text-danger"><?= $fortsConquered ?></strong>
            </div>
        </div>
        <div class="col-3">
            <div class="stat-box">
                <span class="small text-muted d-block">Districts Covered</span>
                <strong class="fs-4 font-heritage text-primary"><?= $districtsExplored ?></strong>
            </div>
        </div>
        <div class="col-3">
            <div class="stat-box">
                <span class="small text-muted d-block">Altitude Scaled</span>
                <strong class="fs-4 font-heritage text-warning"><?= number_format($totalAltitude) ?>m</strong>
            </div>
        </div>
    </div>

    <!-- Badges Earned -->
    <?php if (!empty($achievements)): ?>
        <div class="mb-4">
            <h6 class="font-heritage text-dark fw-bold mb-3 border-bottom pb-1"><i class="fa-solid fa-award text-warning me-2"></i>Honorary Badges & Achievements</h6>
            <div class="d-flex flex-wrap gap-2">
                <?php foreach ($achievements as $ach): ?>
                    <span class="badge p-2 bg-light text-dark border d-flex align-items-center gap-2">
                        <i class="fa-solid <?= e($ach['badge_icon']) ?>" style="color: <?= e($ach['badge_color']) ?>;"></i>
                        <span><strong><?= e($ach['title_en']) ?></strong> (<?= e($ach['title_mr']) ?>)</span>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Expeditions Record Table -->
    <div class="mb-4">
        <h6 class="font-heritage text-dark fw-bold mb-2 border-bottom pb-1"><i class="fa-solid fa-list-check text-success me-2"></i>Chronological Summit Log</h6>
        <?php if (!empty($visitedList)): ?>
            <div class="table-responsive">
                <table class="table table-sm table-bordered small">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Visit Date</th>
                            <th>Destination / Fort</th>
                            <th>District</th>
                            <th>Category</th>
                            <th>Rating</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($visitedList as $i => $v): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= format_date($v['visit_date']) ?></td>
                                <td><strong><?= e($v['name']) ?></strong> <?= $v['is_shivaji_fort'] ? '👑' : '' ?></td>
                                <td><?= e($v['district_name']) ?></td>
                                <td><?= e($v['category_name']) ?></td>
                                <td class="text-warning"><?= str_repeat('★', (int)$v['rating']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="small text-muted">No visited places logged yet.</p>
        <?php endif; ?>
    </div>

    <!-- Footer Stamp -->
    <div class="d-flex align-items-center justify-content-between pt-4 border-top text-muted small">
        <div>
            <span>Verified Digital Record &bull; Generated on <?= date('d M Y') ?></span>
        </div>
        <div class="text-end">
            <span class="font-heritage fw-bold text-success">FORT EXPLORER ARCHIVES</span>
        </div>
    </div>
</div>

</body>
</html>
