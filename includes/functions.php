<?php
/**
 * FORT EXPLORER - Global Utility Functions
 * Security, sanitization, CSRF, flash messages, distance calculations, and helpers
 */

require_once __DIR__ . '/../config/config.php';

/**
 * Escape HTML output to prevent XSS attacks
 */
function e(?string $string): string {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize incoming user string input
 */
function sanitize_input($data): string {
    if (is_array($data)) {
        return '';
    }
    $data = trim((string)$data);
    $data = stripslashes($data);
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

/**
 * Generate or retrieve CSRF token from session
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify submitted CSRF token
 */
function verify_csrf_token(?string $token): bool {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Render a hidden CSRF token input field
 */
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/**
 * Set a flash message in session
 */
function set_flash(string $type, string $message): void {
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    $_SESSION['flash_messages'][] = [
        'type' => $type, // 'success', 'danger', 'warning', 'info'
        'message' => $message
    ];
}

/**
 * Retrieve and clear flash messages
 */
function get_flash_messages(): array {
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $messages;
}

/**
 * Render flash messages alert boxes
 */
function render_flash_messages(): string {
    $messages = get_flash_messages();
    if (empty($messages)) {
        return '';
    }
    
    $html = '<div class="flash-container mb-4">';
    foreach ($messages as $msg) {
        $alertClass = match($msg['type']) {
            'success' => 'alert-success',
            'error', 'danger' => 'alert-danger',
            'warning' => 'alert-warning',
            default => 'alert-info'
        };
        $iconClass = match($msg['type']) {
            'success' => 'fa-circle-check',
            'error', 'danger' => 'fa-circle-exclamation',
            'warning' => 'fa-triangle-exclamation',
            default => 'fa-circle-info'
        };
        
        $html .= '<div class="alert ' . $alertClass . ' alert-dismissible fade show d-flex align-items-center shadow-sm" role="alert">';
        $html .= '<i class="fa-solid ' . $iconClass . ' me-2 fs-5"></i>';
        $html .= '<div>' . e($msg['message']) . '</div>';
        $html .= '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        $html .= '</div>';
    }
    $html .= '</div>';
    return $html;
}

/**
 * Format date nicely
 */
function format_date(?string $dateStr, string $format = 'd M Y'): string {
    if (empty($dateStr)) return 'N/A';
    try {
        $dt = new DateTime($dateStr);
        return $dt->format($format);
    } catch (Exception $e) {
        return $dateStr;
    }
}

/**
 * Calculate Haversine distance between two latitude/longitude points in kilometers
 */
function calculate_haversine_distance(float $lat1, float $lon1, float $lat2, float $lon2): float {
    $earthRadius = 6371; // Earth radius in kilometers

    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);

    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon / 2) * sin($dLon / 2);

    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return round($earthRadius * $c, 1);
}

/**
 * Render star rating display
 */
function render_rating_stars(float $rating, int $max = 5): string {
    $fullStars = floor($rating);
    $halfStar = ($rating - $fullStars) >= 0.5 ? 1 : 0;
    $emptyStars = $max - $fullStars - $halfStar;

    $html = '<div class="star-rating d-inline-flex align-items-center text-warning" title="' . number_format($rating, 1) . ' / 5">';
    for ($i = 0; $i < $fullStars; $i++) {
        $html .= '<i class="fa-solid fa-star"></i>';
    }
    if ($halfStar) {
        $html .= '<i class="fa-solid fa-star-half-stroke"></i>';
    }
    for ($i = 0; $i < $emptyStars; $i++) {
        $html .= '<i class="fa-regular fa-star text-muted opacity-50"></i>';
    }
    $html .= '<span class="ms-1 fw-bold text-dark rating-score">(' . number_format($rating, 1) . ')</span>';
    $html .= '</div>';

    return $html;
}

/**
 * Return badge for difficulty level
 */
function get_difficulty_badge(string $difficulty): string {
    $badgeStyle = match(strtolower($difficulty)) {
        'easy' => 'bg-success text-white',
        'moderate' => 'bg-primary text-white',
        'challenging' => 'bg-warning text-dark',
        'difficult' => 'bg-danger text-white',
        default => 'bg-secondary text-white'
    };
    return '<span class="badge ' . $badgeStyle . ' px-2.5 py-1 rounded-pill"><i class="fa-solid fa-gauge-high me-1"></i>' . e($difficulty) . '</span>';
}

/**
 * Secure file upload helper
 */
function upload_image(array $file, string $folder = 'uploads'): array {
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['success' => false, 'error' => 'Invalid file parameter.'];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Upload error code: ' . $file['error']];
    }

    // Check size (Max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        return ['success' => false, 'error' => 'File size exceeds maximum allowed limit of 5MB.'];
    }

    // Check MIME type using finfo
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    $allowedMimes = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp'
    ];

    $ext = array_search($mimeType, $allowedMimes, true);
    if ($ext === false) {
        return ['success' => false, 'error' => 'Invalid file type. Only JPG, PNG, and WebP are allowed.'];
    }

    // Generate unique file name
    $targetDir = ROOT_PATH . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . $folder;
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $fileName = sprintf('%s_%s.%s', uniqid('fe_', true), bin2hex(random_bytes(4)), $ext);
    $targetPath = $targetDir . DIRECTORY_SEPARATOR . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => false, 'error' => 'Failed to move uploaded file.'];
    }

    // Return relative web URL
    $webPath = 'assets/images/' . $folder . '/' . $fileName;
    return ['success' => true, 'path' => $webPath, 'fileName' => $fileName];
}

/**
 * Get user stats and gamification badges
 */
function get_user_journey_badges(int $visitedCount, int $fortCount, int $districtCount): array {
    $badges = [];

    // Milestone 1: Beginner
    if ($visitedCount >= 1) {
        $badges[] = [
            'title' => 'Sahyadri Wanderer',
            'title_mr' => 'सह्याद्री वाटसरू',
            'desc' => 'Began the journey by visiting your first destination.',
            'icon' => 'fa-compass',
            'color' => '#15803d',
            'unlocked' => true
        ];
    } else {
        $badges[] = [
            'title' => 'Sahyadri Wanderer',
            'title_mr' => 'सह्याद्री वाटसरू',
            'desc' => 'Visit your first destination to unlock.',
            'icon' => 'fa-compass',
            'color' => '#6b7280',
            'unlocked' => false
        ];
    }

    // Milestone 2: Fort Enthusiast
    if ($fortCount >= 3) {
        $badges[] = [
            'title' => 'Fort Trekker',
            'title_mr' => 'दुर्ग भ्रमंतीकार',
            'desc' => 'Conquered 3 or more historic forts of Maharashtra.',
            'icon' => 'fa-shield-halved',
            'color' => '#8b2616',
            'unlocked' => true
        ];
    } else {
        $badges[] = [
            'title' => 'Fort Trekker',
            'title_mr' => 'दुर्ग भ्रमंतीकार',
            'desc' => 'Conquer 3 forts to unlock (' . $fortCount . '/3 conquered).',
            'icon' => 'fa-shield-halved',
            'color' => '#6b7280',
            'unlocked' => false
        ];
    }

    // Milestone 3: Shivaji Maharaj Historian
    if ($fortCount >= 6) {
        $badges[] = [
            'title' => 'Maratha Heritage Master',
            'title_mr' => 'मराठा दुर्ग अभ्यासक',
            'desc' => 'Explored 6+ legendary forts of Chhatrapati Shivaji Maharaj.',
            'icon' => 'fa-crown',
            'color' => '#c59b27',
            'unlocked' => true
        ];
    } else {
        $badges[] = [
            'title' => 'Maratha Heritage Master',
            'title_mr' => 'मराठा दुर्ग अभ्यासक',
            'desc' => 'Explore 6 Shivaji Maharaj forts to unlock (' . $fortCount . '/6).',
            'icon' => 'fa-crown',
            'color' => '#6b7280',
            'unlocked' => false
        ];
    }

    // Milestone 4: District Voyager
    if ($districtCount >= 3) {
        $badges[] = [
            'title' => 'Maharashtra Explorer',
            'title_mr' => 'महाराष्ट्र प्रवासी',
            'desc' => 'Explored destinations across 3 or more districts.',
            'icon' => 'fa-map-location-dot',
            'color' => '#0284c7',
            'unlocked' => true
        ];
    } else {
        $badges[] = [
            'title' => 'Maharashtra Explorer',
            'title_mr' => 'महाराष्ट्र प्रवासी',
            'desc' => 'Explore across 3 districts to unlock (' . $districtCount . '/3 districts).',
            'icon' => 'fa-map-location-dot',
            'color' => '#6b7280',
            'unlocked' => false
        ];
    }

    return $badges;
}
