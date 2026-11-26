<?php
/**
 * FORT EXPLORER - Admin Users Management
 */

$adminTitle = 'Registered Users Management';
require_once __DIR__ . '/includes/admin-header.php';

$db = getDB();

// Handle Role Toggle
if (isset($_GET['toggle_role']) && is_numeric($_GET['toggle_role'])) {
    $targetUserId = (int)$_GET['toggle_role'];
    if ($targetUserId === (int)$_SESSION['user_id']) {
        set_flash('danger', 'You cannot change your own administrator role.');
    } else {
        $db->query("UPDATE users SET role = IF(role = 'admin', 'user', 'admin') WHERE id = {$targetUserId}");
        set_flash('success', 'User role updated.');
    }
    header("Location: users.php");
    exit;
}

$users = $db->query("
    SELECT u.*, 
           (SELECT COUNT(*) FROM visited_places vp WHERE vp.user_id = u.id) AS visited_count,
           (SELECT COUNT(*) FROM wishlists w WHERE w.user_id = u.id) AS wishlist_count
    FROM users u
    ORDER BY u.created_at DESC
")->fetchAll();
?>

<div class="admin-card">
    <div class="d-flex align-items-center justify-content-between mb-4 border-bottom pb-3">
        <div>
            <h5 class="fw-bold font-heritage mb-1 text-dark">Registered Explorers Directory</h5>
            <p class="text-muted small mb-0">Total <?= count($users) ?> users registered on the platform</p>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table admin-table align-middle">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Home District</th>
                    <th>Role</th>
                    <th>Visited</th>
                    <th>Wishlist</th>
                    <th>Joined</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle bg-secondary-subtle text-secondary fw-bold d-flex align-items-center justify-content-center" style="width:36px;height:36px;font-size:0.85rem;">
                                    <?= strtoupper(substr($u['name'], 0, 1)) ?>
                                </div>
                                <div>
                                    <div class="fw-bold text-dark"><?= e($u['name']) ?></div>
                                    <span class="small text-muted"><?= e($u['email']) ?></span>
                                </div>
                            </div>
                        </td>
                        <td><?= e($u['home_district'] ?? 'Pune') ?></td>
                        <td>
                            <span class="badge <?= $u['role'] === 'admin' ? 'bg-danger' : 'bg-success' ?>">
                                <?= e(ucfirst($u['role'])) ?>
                            </span>
                        </td>
                        <td><span class="badge bg-secondary"><?= $u['visited_count'] ?></span></td>
                        <td><span class="badge bg-light text-dark border"><?= $u['wishlist_count'] ?></span></td>
                        <td><span class="small text-muted"><?= format_date($u['created_at']) ?></span></td>
                        <td>
                            <?php if ($u['id'] !== (int)$_SESSION['user_id']): ?>
                                <a href="users.php?toggle_role=<?= $u['id'] ?>" class="btn btn-sm btn-outline-dark py-1 px-2.5 small" title="Toggle role">
                                    Make <?= $u['role'] === 'admin' ? 'User' : 'Admin' ?>
                                </a>
                            <?php else: ?>
                                <span class="badge bg-light text-muted border small">Current Session</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
