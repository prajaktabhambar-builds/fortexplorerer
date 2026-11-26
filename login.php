<?php
/**
 * FORT EXPLORER - User Login
 * Secure authentication using PHP password_verify() and session protection.
 */

require_once __DIR__ . '/includes/header.php';

// Redirect if already logged in
if (is_logged_in()) {
    header("Location: index.php");
    exit;
}

$db = getDB();
$redirect = $_GET['redirect'] ?? 'my-journey.php';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $email = sanitize_input($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $redirectUrl = $_POST['redirect'] ?? 'my-journey.php';

        if (empty($email) || empty($password)) {
            set_flash('danger', 'Please enter both email and password.');
        } elseif (!$db) {
            set_flash('danger', 'Database service is currently unreachable.');
        } else {
            try {
                $stmt = $db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password_hash'])) {
                    // Authenticate user
                    login_user($user);
                    set_flash('success', 'Welcome back, ' . $user['name'] . '!');

                    // Redirect to intended or dashboard
                    $target = !empty($redirectUrl) && !str_starts_with($redirectUrl, 'http') ? $redirectUrl : 'my-journey.php';
                    header("Location: " . $target);
                    exit;
                } else {
                    set_flash('danger', 'Invalid email or password credentials. Please try again.');
                }
            } catch (Exception $e) {
                set_flash('danger', 'Login error: ' . $e->getMessage());
            }
        }
    } else {
        set_flash('danger', 'Security validation failed. Please refresh and try again.');
    }
}
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="bg-white p-4 p-md-5 rounded-4 shadow-sm border">
                <div class="text-center mb-4">
                    <div class="rounded-circle bg-success text-warning d-inline-flex align-items-center justify-content-center mb-2" style="width:58px; height:58px; font-size:1.6rem;">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <h3 class="font-heritage mb-1 text-success">Sign In</h3>
                    <p class="text-muted small">Access your saved forts, wishlist, and travel memories</p>
                </div>

                <form method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="redirect" value="<?= e($redirect) ?>">

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fa-solid fa-envelope text-muted"></i></span>
                            <input type="email" name="email" class="form-control" placeholder="name@example.com" value="<?= e($email) ?>" required autofocus>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="d-flex justify-content-between">
                            <label class="form-label small fw-bold">Password</label>
                        </div>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fa-solid fa-lock text-muted"></i></span>
                            <input type="password" name="password" class="form-control" placeholder="Enter your password" required>
                        </div>
                    </div>

                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-fe-primary py-2.5 fw-bold">
                            <i class="fa-solid fa-right-to-bracket me-1"></i>Sign In
                        </button>
                    </div>

                    <div class="p-3 bg-light rounded-3 small border mb-3">
                        <strong class="text-dark d-block mb-1"><i class="fa-solid fa-key text-warning me-1"></i>Demo Accounts:</strong>
                        <div class="text-muted">
                            <div><strong>Admin:</strong> <code>admin@fortexplorer.com</code> | <code>Admin@FortExplorer2026!</code></div>
                            <div><strong>Explorer:</strong> <code>explorer@fortexplorer.com</code> | <code>Explorer@2026!</code></div>
                        </div>
                    </div>

                    <div class="text-center small text-muted">
                        Don't have an explorer account yet? 
                        <a href="register.php" class="fw-bold text-success text-decoration-none">Create Account</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
