<?php
/**
 * FORT EXPLORER - User Profile Management
 */

require_once __DIR__ . '/includes/header.php';
require_login();

$db = getDB();
$user = current_user();
$userId = $_SESSION['user_id'];

// Handle Profile Information Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    if (verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $name = sanitize_input($_POST['name'] ?? '');
        $phone = sanitize_input($_POST['phone'] ?? '');
        $district = sanitize_input($_POST['home_district'] ?? 'Pune');
        $bio = sanitize_input($_POST['bio'] ?? '');

        if (!empty($name)) {
            $upd = $db->prepare("UPDATE users SET name = ?, phone = ?, home_district = ?, bio = ? WHERE id = ?");
            $upd->execute([$name, $phone, $district, $bio, $userId]);
            $_SESSION['user_name'] = $name;
            set_flash('success', 'Profile details updated successfully!');
            header("Location: profile.php");
            exit;
        } else {
            set_flash('danger', 'Name cannot be blank.');
        }
    }
}

// Handle Password Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    if (verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $currentPass = $_POST['current_password'] ?? '';
        $newPass = $_POST['new_password'] ?? '';
        $confirmPass = $_POST['confirm_password'] ?? '';

        $stmt = $db->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();

        if (!$row || !password_verify($currentPass, $row['password_hash'])) {
            set_flash('danger', 'Current password entered is incorrect.');
        } elseif (strlen($newPass) < 6) {
            set_flash('danger', 'New password must be at least 6 characters.');
        } elseif ($newPass !== $confirmPass) {
            set_flash('danger', 'New passwords do not match.');
        } else {
            $newHash = password_hash($newPass, PASSWORD_DEFAULT);
            $updPass = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $updPass->execute([$newHash, $userId]);
            set_flash('success', 'Password updated successfully!');
            header("Location: profile.php");
            exit;
        }
    }
}
?>

<div class="container py-5">
    <div class="row g-4 justify-content-center">
        <div class="col-lg-4">
            <!-- Profile Card -->
            <div class="bg-white p-4 rounded-4 shadow-sm border text-center mb-4">
                <div class="rounded-circle bg-success text-warning fw-bold d-inline-flex align-items-center justify-content-center shadow mb-3" style="width:80px; height:80px; font-size:2rem;">
                    <?= strtoupper(substr($user['name'], 0, 1)) ?>
                </div>
                <h4 class="font-heritage mb-1 text-dark"><?= e($user['name']) ?></h4>
                <p class="text-muted small mb-2"><?= e($user['email']) ?></p>
                <span class="badge bg-success-subtle text-success px-3 py-1 rounded-pill small mb-3">
                    <i class="fa-solid fa-award me-1"></i><?= ucfirst(e($user['role'])) ?>
                </span>

                <div class="p-3 bg-light rounded-3 text-start small mb-3">
                    <div class="mb-1"><strong><i class="fa-solid fa-location-dot me-2 text-danger"></i>Base:</strong> <?= e($user['home_district'] ?? 'Pune') ?></div>
                    <div class="mb-1"><strong><i class="fa-solid fa-phone me-2 text-primary"></i>Phone:</strong> <?= e($user['phone'] ?? 'Not set') ?></div>
                    <div><strong><i class="fa-solid fa-calendar me-2 text-warning"></i>Joined:</strong> <?= format_date($user['created_at']) ?></div>
                </div>

                <a href="my-journey.php" class="btn btn-fe-primary btn-sm w-100">
                    <i class="fa-solid fa-shoe-prints me-1"></i>View My Travel Journey
                </a>
            </div>
        </div>

        <div class="col-lg-8">
            <!-- Edit Profile Form -->
            <div class="bg-white p-4 rounded-4 shadow-sm border mb-4">
                <h5 class="font-heritage mb-3 text-success pb-2 border-bottom">
                    <i class="fa-solid fa-user-gear me-2 text-warning"></i>Account Details
                </h5>
                <form method="POST">
                    <input type="hidden" name="action" value="update_profile">
                    <?= csrf_field() ?>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Full Name</label>
                            <input type="text" name="name" class="form-control" value="<?= e($user['name']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Phone Number</label>
                            <input type="text" name="phone" class="form-control" placeholder="+91 98765 43210" value="<?= e($user['phone'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Home District</label>
                            <input type="text" name="home_district" class="form-control" value="<?= e($user['home_district'] ?? 'Pune') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Email (Cannot be modified)</label>
                            <input type="email" class="form-control bg-light" value="<?= e($user['email']) ?>" readonly>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Trekker Bio</label>
                            <textarea name="bio" rows="2" class="form-control" placeholder="Share your trekking interests and heritage passion"><?= e($user['bio'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-fe-primary btn-sm py-2 px-4">
                        <i class="fa-solid fa-floppy-disk me-1"></i>Save Details
                    </button>
                </form>
            </div>

            <!-- Change Password Form -->
            <div class="bg-white p-4 rounded-4 shadow-sm border">
                <h5 class="font-heritage mb-3 text-danger pb-2 border-bottom">
                    <i class="fa-solid fa-lock me-2 text-warning"></i>Security & Password
                </h5>
                <form method="POST">
                    <input type="hidden" name="action" value="change_password">
                    <?= csrf_field() ?>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Current Password</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">New Password</label>
                            <input type="password" name="new_password" class="form-control" placeholder="Min 6 characters" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Confirm New</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-outline-danger btn-sm py-2 px-4">
                        <i class="fa-solid fa-key me-1"></i>Update Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
