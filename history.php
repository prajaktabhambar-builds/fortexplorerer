<?php
/**
 * FORT EXPLORER - Historical Timeline of Chhatrapati Shivaji Maharaj
 * Chronological sequence of verified historical milestones, battles, coronations, and treaties.
 */

require_once __DIR__ . '/includes/header.php';

$db = getDB();
$events = [];

if ($db) {
    try {
        $stmt = $db->query("
            SELECT he.*, d.slug AS dest_slug, d.name AS dest_name, d.name_mr AS dest_name_mr
            FROM historical_events he
            LEFT JOIN destinations d ON he.related_destination_id = d.id
            ORDER BY he.event_year ASC, he.id ASC
        ");
        $events = $stmt->fetchAll();
    } catch (Exception $e) {
        // Fallback
    }
}
?>

<!-- Header Banner -->
<div class="py-5" style="background: linear-gradient(135deg, var(--fe-forest-dark) 0%, var(--fe-forest) 100%); color: #fff; border-bottom: 3px solid var(--fe-gold);">
    <div class="container py-4 text-center">
        <span class="badge px-3 py-2 rounded-pill mb-3" style="background: rgba(197, 155, 39, 0.25); border: 1px solid #c59b27; color: #f5d77f; letter-spacing: 1.5px;">
            <i class="fa-solid fa-scroll me-2"></i><?= is_marathi() ? 'शिवचरित्र आणि ऐतिहासिक कालपट' : 'Chronology of Maratha Sovereignty' ?>
        </span>
        <h1 class="display-4 fw-bold font-heritage mb-3">
            <?= is_marathi() ? 'छत्रपती शिवाजी महाराजांचा ऐतिहासिक कालपट' : 'The Historical Timeline of Chhatrapati Shivaji Maharaj' ?>
        </h1>
        <p class="lead text-white-50 max-w-700 mx-auto mb-0 fs-6">
            <?= is_marathi()
                ? '१६३० मधील शिवनेरीवरील जन्मापासून, स्वराज्याची तोरणे बांधत, १६७४ मधील सुवर्ण राज्याभिषेकापर्यंतचा प्रेरणादायी इतिहास.'
                : 'A verified chronological journey from the birth at Shivneri Fort in 1630 to the grand coronation at Raigad in 1674 and beyond.' ?>
        </p>
    </div>
</div>

<!-- Timeline Section -->
<div class="container py-5">
    <?php if (!empty($events)): ?>
        <div class="timeline-container">
            <?php foreach ($events as $idx => $event): ?>
                <?php
                $isLeft = ($idx % 2 === 0);
                $title = t_val($event['title_en'], $event['title_mr']);
                $desc = t_val($event['description_en'], $event['description_mr']);
                $relatedDestName = !empty($event['dest_name']) ? t_val($event['dest_name'], $event['dest_name_mr']) : null;
                $badgeStyle = match($event['importance_level']) {
                    'Coronation' => 'bg-warning text-dark',
                    'Battle' => 'bg-danger text-white',
                    'Treaty' => 'bg-info text-dark',
                    'Strategic' => 'bg-primary text-white',
                    default => 'bg-success text-white'
                };
                ?>
                <div class="timeline-item <?= $isLeft ? 'left' : 'right' ?>">
                    <div class="timeline-node">
                        <i class="fa-solid <?= e($event['icon_name'] ?: 'fa-landmark') ?>"></i>
                    </div>
                    <div class="timeline-card">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="timeline-year"><?= $event['event_year'] ?></span>
                            <span class="badge <?= $badgeStyle ?> rounded-pill px-2 py-1 small"><?= e($event['importance_level']) ?></span>
                        </div>

                        <?php if (!empty($event['date_formatted'])): ?>
                            <div class="small text-muted mb-2"><i class="fa-regular fa-calendar-check me-1"></i><?= e($event['date_formatted']) ?></div>
                        <?php endif; ?>

                        <h5 class="fw-bold font-heritage mb-2 text-dark"><?= e($title) ?></h5>
                        <p class="text-secondary small mb-3" style="line-height:1.7;"><?= nl2br(e($desc)) ?></p>

                        <?php if (!empty($relatedDestName) && !empty($event['dest_slug'])): ?>
                            <div class="pt-2 border-top">
                                <a href="destination.php?slug=<?= urlencode($event['dest_slug']) ?>" class="btn btn-sm btn-fe-outline py-1 px-2.5" style="font-size:0.8rem;">
                                    <i class="fa-solid fa-shield-halved me-1 text-warning"></i>Explore <?= e($relatedDestName) ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="text-center py-5">
            <p class="text-muted">No timeline records found. Please initialize the database with sample data.</p>
            <a href="install.php" class="btn btn-fe-primary">Import Database Now</a>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
