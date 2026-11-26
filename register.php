<?php
/**
 * FORT EXPLORER - User Registration
 * Secure account creation with password hashing and session generation.
 */

require_once __DIR__ . '/includes/header.php';

if (is_logged_in()) {
    header("Location: index.php");
    exit;
}

$db = getDB();
$name = '';
$email = '';
$homeDistrict = 'Pune';

$districts = [];
if ($db) {
    try {
        $dStmt = $db->query("SELECT name_en, name_mr FROM districts ORDER BY name_en ASC");
        $districts = $dStmt->fetchAll();
    } catch (Exception $e) {}
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $name = sanitize_input($_POST['name'] ?? '');
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $homeDistrict = sanitize_input($_POST['home_district'] ?? 'Pune');
        $bio = sanitize_input($_POST['bio'] ?? '');

        if (empty($name) || !$email || empty($password)) {
            set_flash('danger', 'Please provide a valid name, email address, and password.');
        } elseif (strlen($password) < 6) {
            set_flash('danger', 'Password must be at least 6 characters long.');
        } elseif ($password !== $confirmPassword) {
            set_flash('danger', 'Passwords do not match. Please re-enter.');
        } elseif (!$db) {
            set_flash('danger', 'Database service is currently unreachable.');
        } else {
            try {
                // Check if email already registered
                $check = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
                $check->execute([$email]);
                if ($check->fetch()) {
                    set_flash('danger', 'An account with this email address already exists. Please sign in instead.');
                } else {
                    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                    $ins = $db->prepare("
                        INSERT INTO users (name, email, password_hash, role, home_district, bio)
                        VALUES (?, ?, ?, 'user', ?, ?)
                    ");
                    $ins->execute([$name, $email, $passwordHash, $homeDistrict, $bio]);
                    $newUserId = (int)$db->lastInsertId();

                    // Log user in directly
                    login_user([
                        'id' => $newUserId,
                        'name' => $name,
                        'email' => $email,
                        'role' => 'user'
                    ]);

                    set_flash('success', 'Welcome to Fort Explorer! Your account has been created.');
                    header("Location: my-journey.php");
                    exit;
                }
            } catch (Exception $e) {
                set_flash('danger', 'Registration error: ' . $e->getMessage());
            }
        }
    } else {
        set_flash('danger', 'Security validation failed.');
    }
}
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-6">
            <div class="bg-white p-4 p-md-5 rounded-4 shadow-sm border">
                <div class="text-center mb-4">
                    <div class="rounded-circle bg-success text-warning d-inline-flex align-items-center justify-content-center mb-2" style="width:58px; height:58px; font-size:1.6rem;">
                        <i class="fa-solid fa-user-plus"></i>
                    </div>
                    <h3 class="font-heritage mb-1 text-success">Join Fort Explorer</h3>
                    <p class="text-muted small">Begin tracking your Sahyadri expeditions and fort conquest records</p>
                </div>

                <form method="POST">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Full Name</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fa-solid fa-user text-muted"></i></span>
                            <input type="text" name="name" class="form-control" placeholder="e.g. Tanmay Patil" value="<?= e($name) ?>" required autofocus>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fa-solid fa-envelope text-muted"></i></span>
                            <input type="email" name="email" class="form-control" placeholder="name@example.com" value="<?= e($email) ?>" required>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label small fw-bold">Password</label>
                            <input type="password" name="password" class="form-control" placeholder="Minimum 6 characters" required>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small fw-bold">Confirm Password</label>
                            <input type="password" name="confirm_password" class="form-control" placeholder="Re-enter password" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Home District / City</label>
                        <select name="home_district" class="form-select">
                            <option value="Pune" <?= $homeDistrict === 'Pune' ? 'selected' : '' ?>>Pune</option>
                            <option value="Mumbai" <?= $homeDistrict === 'Mumbai' ? 'selected' : '' ?>>Mumbai</option>
                            <option value="Satara" <?= $homeDistrict === 'Satara' ? 'selected' : '' ?>>Satara</option>
                            <option value="Kolhapur" <?= $homeDistrict === 'Kolhapur' ? 'selected' : '' ?>>Kolhapur</option>
                            <option value="Raigad" <?= $homeDistrict === 'Raigad' ? 'selected' : '' ?>>Raigad</option>
                            <option value="Nashik" <?= $homeDistrict === 'Nashik' ? 'selected' : '' ?>>Nashik</option>
                            <option value="Thane" <?= $homeDistrict === 'Thane' ? 'selected' : '' ?>>Thane</option>
                            <option value="Chhatrapati Sambhajinagar" <?= $homeDistrict === 'Chhatrapati Sambhajinagar' ? 'selected' : '' ?>>Chhatrapati Sambhajinagar</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold">Travel Bio / Trekker Statement (Optional)</label>
                        <textarea name="bio" rows="2" class="form-control" placeholder="e.g. Enthusiast of Chhatrapati Shivaji Maharaj forts and Western Ghats waterfalls"></textarea>
                    </div>

                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-fe-primary py-2.5 fw-bold">
                            <i class="fa-solid fa-flag me-1"></i>Create Explorer Account
                        </button>
                    </div>

                    <div class="text-center small text-muted">
                        Already have an account? 
                        <a href="login.php" class="fw-bold text-success text-decoration-none">Sign In</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
