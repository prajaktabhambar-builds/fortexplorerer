<?php
/**
 * FORT EXPLORER - Admin Advanced Analytics Dashboard
 * Visual metrics: Most visited forts, wishlists, district distribution,
 * ratings breakdown, community engagement, and AI assistant query trends.
 */

$adminTitle = 'Advanced Analytics & Insights - Fort Explorer';
require_once __DIR__ . '/includes/admin-header.php';

$db = getDB();

// Aggregated Stats
$totalDestinations = 0;
$totalUsers = 0;
$totalVisits = 0;
$totalWishlists = 0;
$totalReviews = 0;
$totalStories = 0;
$totalAiQueries = 0;

$topVisited = [];
$topWishlisted = [];
$categoryDistribution = [];
$districtDistribution = [];
$ratingDistribution = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
$aiLangDistribution = ['en' => 0, 'mr' => 0, 'hi' => 0];

if ($db) {
    try {
        $totalDestinations = (int)$db->query("SELECT COUNT(*) FROM destinations")->fetchColumn();
        $totalUsers = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $totalVisits = (int)$db->query("SELECT COUNT(*) FROM visited_places")->fetchColumn();
        $totalWishlists = (int)$db->query("SELECT COUNT(*) FROM wishlists")->fetchColumn();
        $totalReviews = (int)$db->query("SELECT COUNT(*) FROM reviews WHERE status = 'approved'")->fetchColumn();
        $totalStories = (int)$db->query("SELECT COUNT(*) FROM community_posts WHERE status = 'published'")->fetchColumn();
        $totalAiQueries = (int)$db->query("SELECT COUNT(*) FROM ai_chat_logs")->fetchColumn();

        // 1. Top 8 Most Visited Destinations
        $vStmt = $db->query("
            SELECT d.name, COUNT(vp.id) AS visit_count
            FROM destinations d
            LEFT JOIN visited_places vp ON d.id = vp.destination_id
            GROUP BY d.id
            ORDER BY visit_count DESC, d.is_shivaji_fort DESC
            LIMIT 8
        ");
        $topVisited = $vStmt->fetchAll();

        // 2. Top 8 Most Wishlisted
        $wStmt = $db->query("
            SELECT d.name, COUNT(w.id) AS wishlist_count
            FROM destinations d
            LEFT JOIN wishlists w ON d.id = w.destination_id
            GROUP BY d.id
            ORDER BY wishlist_count DESC, d.is_shivaji_fort DESC
            LIMIT 8
        ");
        $topWishlisted = $wStmt->fetchAll();

        // 3. Destinations by Category
        $cStmt = $db->query("
            SELECT c.name_en AS category_name, COUNT(d.id) AS dest_count
            FROM destination_categories c
            LEFT JOIN destinations d ON c.id = d.category_id
            GROUP BY c.id
            ORDER BY dest_count DESC
        ");
        $categoryDistribution = $cStmt->fetchAll();

        // 4. District Distribution
        $dStmt = $db->query("
            SELECT dist.name_en AS district_name, COUNT(d.id) AS dest_count
            FROM districts dist
            LEFT JOIN destinations d ON dist.id = d.district_id
            GROUP BY dist.id
            HAVING dest_count > 0
            ORDER BY dest_count DESC
            LIMIT 8
        ");
        $districtDistribution = $dStmt->fetchAll();

        // 5. Rating Distribution
        $rStmt = $db->query("SELECT rating, COUNT(*) AS cnt FROM reviews WHERE status = 'approved' GROUP BY rating");
        while ($row = $rStmt->fetch()) {
            $r = (int)$row['rating'];
            if (isset($ratingDistribution[$r])) {
                $ratingDistribution[$r] = (int)$row['cnt'];
            }
        }

        // 6. AI Queries Language
        $aiStmt = $db->query("SELECT detected_lang, COUNT(*) AS cnt FROM ai_chat_logs GROUP BY detected_lang");
        while ($row = $aiStmt->fetch()) {
            $lang = strtolower($row['detected_lang'] ?? 'en');
            if (isset($aiLangDistribution[$lang])) {
                $aiLangDistribution[$lang] = (int)$row['cnt'];
            }
        }

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
            <span class="small text-muted d-block" style="font-size:0.75rem;">Destinations</span>
            <div class="h4 fw-bold text-success font-heritage mb-0"><?= $totalDestinations ?></div>
            <span class="text-muted" style="font-size:0.7rem;">In Database</span>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
            <span class="small text-muted d-block" style="font-size:0.75rem;">Registered Users</span>
            <div class="h4 fw-bold text-primary font-heritage mb-0"><?= $totalUsers ?></div>
            <span class="text-muted" style="font-size:0.7rem;">Explorers</span>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
            <span class="small text-muted d-block" style="font-size:0.75rem;">Visited Check-ins</span>
            <div class="h4 fw-bold text-danger font-heritage mb-0"><?= $totalVisits ?></div>
            <span class="text-muted" style="font-size:0.7rem;">Verified Journeys</span>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
            <span class="small text-muted d-block" style="font-size:0.75rem;">Wishlist Places</span>
            <div class="h4 fw-bold text-warning font-heritage mb-0"><?= $totalWishlists ?></div>
            <span class="text-muted" style="font-size:0.7rem;">Future Treks</span>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
            <span class="small text-muted d-block" style="font-size:0.75rem;">Community Stories</span>
            <div class="h4 fw-bold text-info font-heritage mb-0"><?= $totalStories ?></div>
            <span class="text-muted" style="font-size:0.7rem;">Trekker Posts</span>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
            <span class="small text-muted d-block" style="font-size:0.75rem;">AI Heritage Chats</span>
            <div class="h4 fw-bold font-heritage mb-0" style="color:#8b2616;"><?= $totalAiQueries ?></div>
            <span class="text-muted" style="font-size:0.7rem;">Chatbot Queries</span>
        </div>
    </div>
</div>

<!-- Charts Grid -->
<div class="row g-4">
    <!-- Chart 1: Top Visited Destinations -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
            <h6 class="fw-bold font-heritage text-success mb-3">
                <i class="fa-solid fa-shoe-prints me-2 text-warning"></i>Most Visited Destinations (Check-ins)
            </h6>
            <div style="height: 280px;">
                <canvas id="visitedChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Chart 2: Top Wishlisted Destinations -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
            <h6 class="fw-bold font-heritage text-danger mb-3">
                <i class="fa-solid fa-heart me-2 text-danger"></i>Most Wishlisted Destinations
            </h6>
            <div style="height: 280px;">
                <canvas id="wishlistChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Chart 3: Category Distribution -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
            <h6 class="fw-bold font-heritage text-dark mb-3">
                <i class="fa-solid fa-shield-halved me-2 text-warning"></i>Destinations by Category
            </h6>
            <div style="height: 260px;">
                <canvas id="categoryChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Chart 4: District Tourism Distribution -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
            <h6 class="fw-bold font-heritage text-dark mb-3">
                <i class="fa-solid fa-map-location-dot me-2 text-primary"></i>District Tourism Spread
            </h6>
            <div style="height: 260px;">
                <canvas id="districtChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Chart 5: AI Assistant Queries Language -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
            <h6 class="fw-bold font-heritage text-dark mb-3">
                <i class="fa-solid fa-robot me-2 text-info"></i>AI Assistant Queries by Language
            </h6>
            <div style="height: 260px;">
                <canvas id="aiLangChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // 1. Visited Chart
    const visitedLabels = <?= json_encode(array_column($topVisited, 'name')) ?>;
    const visitedCounts = <?= json_encode(array_map('intval', array_column($topVisited, 'visit_count'))) ?>;

    new Chart(document.getElementById('visitedChart'), {
        type: 'bar',
        data: {
            labels: visitedLabels,
            datasets: [{
                label: 'Check-ins',
                data: visitedCounts,
                backgroundColor: '#163820',
                borderRadius: 6
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { beginAtZero: true, ticks: { stepSize: 1 } }
            }
        }
    });

    // 2. Wishlist Chart
    const wishlistLabels = <?= json_encode(array_column($topWishlisted, 'name')) ?>;
    const wishlistCounts = <?= json_encode(array_map('intval', array_column($topWishlisted, 'wishlist_count'))) ?>;

    new Chart(document.getElementById('wishlistChart'), {
        type: 'bar',
        data: {
            labels: wishlistLabels,
            datasets: [{
                label: 'Wishlists',
                data: wishlistCounts,
                backgroundColor: '#8b2616',
                borderRadius: 6
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { beginAtZero: true, ticks: { stepSize: 1 } }
            }
        }
    });

    // 3. Category Distribution Chart
    const catLabels = <?= json_encode(array_column($categoryDistribution, 'category_name')) ?>;
    const catCounts = <?= json_encode(array_map('intval', array_column($categoryDistribution, 'dest_count'))) ?>;

    new Chart(document.getElementById('categoryChart'), {
        type: 'doughnut',
        data: {
            labels: catLabels,
            datasets: [{
                data: catCounts,
                backgroundColor: ['#163820', '#c59b27', '#8b2616', '#0284c7', '#10b981', '#6b7280']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } }
            }
        }
    });

    // 4. District Distribution Chart
    const distLabels = <?= json_encode(array_column($districtDistribution, 'district_name')) ?>;
    const distCounts = <?= json_encode(array_map('intval', array_column($districtDistribution, 'dest_count'))) ?>;

    new Chart(document.getElementById('districtChart'), {
        type: 'bar',
        data: {
            labels: distLabels,
            datasets: [{
                label: 'Destinations',
                data: distCounts,
                backgroundColor: '#c59b27',
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } }
            }
        }
    });

    // 5. AI Assistant Language Chart
    new Chart(document.getElementById('aiLangChart'), {
        type: 'pie',
        data: {
            labels: ['English', 'Marathi (मराठी)', 'Hindi (हिंदी)'],
            datasets: [{
                data: [
                    <?= (int)($aiLangDistribution['en'] ?? 0) ?>,
                    <?= (int)($aiLangDistribution['mr'] ?? 0) ?>,
                    <?= (int)($aiLangDistribution['hi'] ?? 0) ?>
                ],
                backgroundColor: ['#0284c7', '#f59e0b', '#10b981']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } }
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
