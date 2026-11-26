<?php
/**
 * FORT EXPLORER - Admin Review Moderation
 */

$adminTitle = 'Moderate Visitor Reviews';
require_once __DIR__ . '/includes/admin-header.php';

$db = getDB();

// Handle status change
if (isset($_GET['action']) && isset($_GET['id']) && is_numeric($_GET['id'])) {
    $revId = (int)$_GET['id'];
    $action = $_GET['action'];

    if ($action === 'approve') {
        $db->query("UPDATE reviews SET status = 'approved' WHERE id = {$revId}");
        set_flash('success', 'Review approved.');
    } elseif ($action === 'reject') {
        $db->query("UPDATE reviews SET status = 'rejected' WHERE id = {$revId}");
        set_flash('warning', 'Review rejected.');
    } elseif ($action === 'delete') {
        $db->query("DELETE FROM reviews WHERE id = {$revId}");
        set_flash('info', 'Review deleted.');
    }
    header("Location: reviews.php");
    exit;
}

$statusFilter = $_GET['status'] ?? 'all';
$query = "
    SELECT r.*, d.name AS dest_name, u.name AS user_name, u.email AS user_email
    FROM reviews r
    JOIN destinations d ON r.destination_id = d.id
    JOIN users u ON r.user_id = u.id
    WHERE 1=1
";
$params = [];
if (in_array($statusFilter, ['approved', 'pending', 'rejected'], true)) {
    $query .= " AND r.status = ?";
    $params[] = $statusFilter;
}
$query .= " ORDER BY r.created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$reviews = $stmt->fetchAll();
?>

<div class="admin-card">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h5 class="fw-bold font-heritage mb-1 text-dark">Visitor Reviews Moderation</h5>
            <p class="text-muted small mb-0">Review, approve or reject traveler feedback</p>
        </div>
        <div class="btn-group btn-group-sm">
            <a href="reviews.php?status=all" class="btn <?= $statusFilter === 'all' ? 'btn-dark' : 'btn-outline-dark' ?>">All</a>
            <a href="reviews.php?status=pending" class="btn <?= $statusFilter === 'pending' ? 'btn-warning' : 'btn-outline-warning' ?>">Pending</a>
            <a href="reviews.php?status=approved" class="btn <?= $statusFilter === 'approved' ? 'btn-success' : 'btn-outline-success' ?>">Approved</a>
            <a href="reviews.php?status=rejected" class="btn <?= $statusFilter === 'rejected' ? 'btn-danger' : 'btn-outline-danger' ?>">Rejected</a>
        </div>
    </div>

    <?php if (!empty($reviews)): ?>
        <div class="table-responsive">
            <table class="table admin-table align-middle">
                <thead>
                    <tr>
                        <th>Destination</th>
                        <th>User</th>
                        <th>Rating</th>
                        <th>Review Title & Text</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reviews as $r): ?>
                        <tr>
                            <td><strong><?= e($r['dest_name']) ?></strong></td>
                            <td>
                                <div><?= e($r['user_name']) ?></div>
                                <span class="small text-muted"><?= e($r['user_email']) ?></span>
                            </td>
                            <td><span class="text-warning"><?= str_repeat('★', (int)$r['rating']) ?></span></td>
                            <td style="max-width:320px;">
                                <?php if ($r['review_title']): ?>
                                    <div class="fw-bold small text-dark"><?= e($r['review_title']) ?></div>
                                <?php endif; ?>
                                <span class="small text-secondary"><?= e(substr($r['review_text'], 0, 110)) ?>...</span>
                            </td>
                            <td>
                                <span class="badge <?= $r['status'] === 'approved' ? 'bg-success' : ($r['status'] === 'pending' ? 'bg-warning text-dark' : 'bg-danger') ?>">
                                    <?= e(ucfirst($r['status'])) ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <?php if ($r['status'] !== 'approved'): ?>
                                        <a href="reviews.php?action=approve&id=<?= $r['id'] ?>" class="btn btn-outline-success py-1" title="Approve"><i class="fa-solid fa-check"></i></a>
                                    <?php endif; ?>
                                    <?php if ($r['status'] !== 'rejected'): ?>
                                        <a href="reviews.php?action=reject&id=<?= $r['id'] ?>" class="btn btn-outline-warning py-1" title="Reject"><i class="fa-solid fa-ban"></i></a>
                                    <?php endif; ?>
                                    <a href="reviews.php?action=delete&id=<?= $r['id'] ?>" onclick="return confirm('Permanently delete review?');" class="btn btn-outline-danger py-1" title="Delete"><i class="fa-solid fa-trash-can"></i></a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="text-muted text-center py-4">No reviews found.</p>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
