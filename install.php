<?php
/**
 * FORT EXPLORER - One-Click Database Installer & Setup Wizard
 * Initializes MySQL database and imports schema + sample data automatically.
 */

require_once __DIR__ . '/config/config.php';

$message = '';
$messageType = '';
$installed = false;

// If form submitted or triggered
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'install') {
    $host = $_POST['db_host'] ?? DB_HOST;
    $user = $_POST['db_user'] ?? DB_USER;
    $pass = $_POST['db_pass'] ?? DB_PASS;
    $name = $_POST['db_name'] ?? DB_NAME;

    try {
        // Step 1: Connect to MySQL server trying ports 3307 and 3306
        $portsToTry = [3307, 3306];
        $pdo = null;
        $lastErr = '';
        foreach ($portsToTry as $port) {
            try {
                $pdo = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                ]);
                break;
            } catch (Exception $ex) {
                $lastErr = $ex->getMessage();
            }
        }
        if (!$pdo) {
            throw new Exception("Could not connect to MySQL on ports 3307 or 3306: " . $lastErr);
        }

        // Step 2: Create database if not exists
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `{$name}`");

        // Step 3: Read and execute database.sql
        $sqlFile = __DIR__ . '/database.sql';
        if (!file_exists($sqlFile)) {
            throw new Exception("database.sql file not found at " . $sqlFile);
        }

        $sql = file_get_contents($sqlFile);
        
        // Execute queries
        $pdo->exec($sql);

        $message = "Database '{$name}' created and seeded successfully with verified forts and destinations!";
        $messageType = 'success';
        $installed = true;

    } catch (Exception $e) {
        $message = "Installation failed: " . $e->getMessage();
        $messageType = 'danger';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup Wizard - Fort Explorer</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Bootstrap 5 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <style>
        :root {
            --fe-forest: #163820;
            --fe-forest-dark: #0e2415;
            --fe-gold: #c59b27;
            --fe-beige: #f5efe6;
            --fe-charcoal: #1e2124;
        }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0e2415 0%, #163820 50%, #244b2f 100%);
            min-height: 100vh;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px 15px;
        }
        .setup-card {
            background: #ffffff;
            color: var(--fe-charcoal);
            border-radius: 16px;
            box-shadow: 0 20px 45px rgba(0,0,0,0.35);
            max-width: 650px;
            width: 100%;
            overflow: hidden;
            border: 1px solid rgba(197, 155, 39, 0.3);
        }
        .setup-header {
            background: var(--fe-forest);
            color: #fff;
            padding: 30px 35px;
            text-align: center;
            border-bottom: 3px solid var(--fe-gold);
        }
        .setup-header h1 {
            font-family: 'Cinzel', serif;
            font-size: 26px;
            letter-spacing: 1px;
            margin-bottom: 5px;
            color: #fff;
        }
        .setup-header p {
            color: #eaddcf;
            font-size: 14px;
            margin: 0;
        }
        .setup-body {
            padding: 35px;
        }
        .btn-heritage {
            background: var(--fe-forest);
            color: #fff;
            border: 1px solid var(--fe-gold);
            font-weight: 600;
            padding: 12px 24px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .btn-heritage:hover {
            background: var(--fe-gold);
            color: var(--fe-forest-dark);
            border-color: var(--fe-forest);
        }
        .credentials-box {
            background: #f8fafc;
            border-left: 4px solid var(--fe-gold);
            padding: 15px 20px;
            border-radius: 6px;
            margin-top: 20px;
        }
    </style>
</head>
<body>

<div class="setup-card">
    <div class="setup-header">
        <i class="fa-solid fa-shield-halved fa-2x text-warning mb-2"></i>
        <h1>FORT EXPLORER</h1>
        <p>Database Installation & Setup Wizard</p>
    </div>
    
    <div class="setup-body">
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= $messageType ?> d-flex align-items-center mb-4">
                <i class="fa-solid <?= $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle' ?> fs-4 me-3"></i>
                <div><?= htmlspecialchars($message) ?></div>
            </div>
        <?php endif; ?>

        <?php if ($installed): ?>
            <div class="text-center py-3">
                <i class="fa-solid fa-circle-check text-success fa-4x mb-3"></i>
                <h3 class="fw-bold text-success mb-2">Installation Complete!</h3>
                <p class="text-muted">The database schema has been imported with verified forts, destinations, timeline events, and sample user profiles.</p>
                
                <div class="credentials-box text-start my-3">
                    <h6 class="fw-bold mb-2 text-dark"><i class="fa-solid fa-key me-2 text-warning"></i>Default System Accounts:</h6>
                    <ul class="list-unstyled mb-0 small text-secondary">
                        <li class="mb-1"><strong>Admin Account:</strong> <code>admin@fortexplorer.com</code> | Password: <code>Admin@FortExplorer2026!</code></li>
                        <li><strong>Demo Explorer:</strong> <code>explorer@fortexplorer.com</code> | Password: <code>Explorer@2026!</code></li>
                    </ul>
                </div>

                <div class="d-flex gap-3 justify-content-center mt-4">
                    <a href="index.php" class="btn btn-heritage"><i class="fa-solid fa-house me-2"></i>Go to Homepage</a>
                    <a href="admin/index.php" class="btn btn-outline-dark"><i class="fa-solid fa-gauge-high me-2"></i>Admin Dashboard</a>
                </div>
            </div>
        <?php else: ?>
            <p class="text-muted mb-4">
                Welcome to <strong>Fort Explorer</strong>! Click the button below to initialize your local MySQL database. This will create the <code>fort_explorer</code> schema and populate it with rich historical data for Maharashtra forts.
            </p>

            <form method="POST">
                <input type="hidden" name="action" value="install">
                
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Database Host</label>
                        <input type="text" name="db_host" class="form-control" value="<?= htmlspecialchars(DB_HOST) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Database Name</label>
                        <input type="text" name="db_name" class="form-control" value="<?= htmlspecialchars(DB_NAME) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Database Username</label>
                        <input type="text" name="db_user" class="form-control" value="<?= htmlspecialchars(DB_USER) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Database Password</label>
                        <input type="password" name="db_pass" class="form-control" value="<?= htmlspecialchars(DB_PASS) ?>" placeholder="Default empty in XAMPP">
                    </div>
                </div>

                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-heritage py-3 fs-6">
                        <i class="fa-solid fa-database me-2"></i>Initialize & Import Database Now
                    </button>
                </div>
            </form>

            <div class="mt-4 pt-3 border-top text-center text-muted small">
                Alternatively, you can manually import <code>database.sql</code> via phpMyAdmin.
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
