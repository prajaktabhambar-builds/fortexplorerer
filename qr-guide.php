<?php
/**
 * FORT EXPLORER - Mobile QR Heritage Guide & On-Site Experience
 * Opened directly when visitors on-site scan the destination QR code.
 */

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/weather.php';

$db = getDB();
$slug = trim($_GET['slug'] ?? '');
$destId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$slug && !$destId) {
    header("Location: explore.php");
    exit;
}

$destination = null;
if ($db) {
    $where = $slug ? "d.slug = ?" : "d.id = ?";
    $param = $slug ?: $destId;

    $stmt = $db->prepare("
        SELECT d.*, 
               c.slug AS category_slug, c.name_en AS category_name, c.name_mr AS category_name_mr,
               dist.name_en AS district_name, dist.name_mr AS district_name_mr
        FROM destinations d
        JOIN destination_categories c ON d.category_id = c.id
        JOIN districts dist ON d.district_id = dist.id
        WHERE {$where}
    ");
    
    $stmt->execute([$param]);
    $destination = $stmt->fetch();
}

if (!$destination) {
    set_flash('danger', 'Destination not found.');
    header("Location: explore.php");
    exit;
}

$destName = t_val($destination['name'], $destination['name_mr']);
$destDesc = t_val($destination['short_description'], $destination['short_description_mr']);
$destHist = t_val($destination['historical_significance'], $destination['historical_significance_mr']);
$destShivaji = t_val($destination['shivaji_connection'], $destination['shivaji_connection_mr']);
$districtName = t_val($destination['district_name'], $destination['district_name_mr']);

// Audio summary
$audioEn = !empty($destination['audio_summary_en']) ? $destination['audio_summary_en'] : strip_tags($destination['overview']);
$audioMr = !empty($destination['audio_summary_mr']) ? $destination['audio_summary_mr'] : $destDesc;
$audioHi = !empty($destination['audio_summary_hi']) ? $destination['audio_summary_hi'] : $destDesc;

// Live Weather
$weather = get_destination_weather((float)$destination['latitude'], (float)$destination['longitude']);
?>

<div class="qr-guide-wrapper py-3" style="max-width: 800px; margin: 0 auto; padding-left: 15px; padding-right: 15px;">
    <!-- On-Site Badge & Navigation -->
    <div class="d-flex align-items-center justify-content-between mb-3">
        <a href="destination.php?slug=<?= urlencode($destination['slug']) ?>" class="btn btn-sm btn-outline-secondary rounded-pill">
            <i class="fa-solid fa-arrow-left me-1"></i>Full Website Page
        </a>
        <span class="badge bg-success-subtle text-success border border-success px-3 py-1.5 rounded-pill small">
            <i class="fa-solid fa-qrcode me-1"></i>On-Site Scanned Heritage Pass
        </span>
    </div>

    <!-- Hero Card -->
    <div class="position-relative rounded-4 overflow-hidden shadow-sm mb-4" style="height: 240px; background-size: cover; background-position: center; background-image: linear-gradient(to bottom, rgba(0,0,0,0.1), rgba(0,0,0,0.85)), url('<?= e($destination['primary_image']) ?>');">
        <div class="position-absolute bottom-0 start-0 p-4 text-white">
            <div class="d-flex gap-2 mb-2">
                <span class="badge bg-warning text-dark px-2.5 py-1 rounded-pill small">
                    <i class="fa-solid fa-shield-halved me-1"></i><?= e($destination['category_name']) ?>
                </span>
                <span class="badge bg-black bg-opacity-60 text-white px-2.5 py-1 rounded-pill small">
                    <i class="fa-solid fa-location-dot me-1 text-danger"></i><?= e($districtName) ?>
                </span>
                <?php if ($destination['altitude_meters']): ?>
                    <span class="badge bg-black bg-opacity-60 text-white px-2.5 py-1 rounded-pill small">
                        <i class="fa-solid fa-mountain me-1"></i><?= $destination['altitude_meters'] ?>m
                    </span>
                <?php endif; ?>
            </div>
            <h1 class="h3 fw-bold font-heritage mb-1"><?= e($destName) ?></h1>
            <p class="small text-white-50 mb-0"><?= e($destDesc) ?></p>
        </div>
    </div>

    <!-- Weather & Safety Alert Card -->
    <div class="alert <?= $weather['advisory_level'] === 'danger' ? 'alert-danger' : ($weather['advisory_level'] === 'warning' ? 'alert-warning' : 'alert-info') ?> d-flex align-items-center justify-content-between p-3 rounded-4 shadow-sm mb-4">
        <div class="d-flex align-items-center gap-3">
            <div class="fs-2 text-warning">
                <i class="fa-solid <?= $weather['precipitation_probability'] > 40 ? 'fa-cloud-showers-heavy' : 'fa-sun' ?>"></i>
            </div>
            <div>
                <div class="fw-bold small"><?= e($weather['weather_description']) ?> &bull; <?= $weather['temperature'] ?>°C (Wind: <?= $weather['wind_speed'] ?> km/h)</div>
                <div class="small" style="font-size:0.82rem;"><?= e($weather['advisory']) ?></div>
            </div>
        </div>
    </div>

    <!-- On-Site Multilingual Audio Guide Player -->
    <div class="card border-0 shadow-sm rounded-4 mb-4" style="background: linear-gradient(135deg, #163820 0%, #1e4d2b 100%); color: #fff;">
        <div class="card-body p-4">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-warning text-dark rounded-circle d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                        <i class="fa-solid fa-headphones"></i>
                    </div>
                    <div>
                        <h5 class="mb-0 font-heritage text-white">Audio Guide / ऑडिओ गाईड</h5>
                        <span class="small text-white-50" style="font-size: 0.75rem;">Listen to authentic history while you walk</span>
                    </div>
                </div>
                <!-- Language Selector -->
                <div class="btn-group btn-group-sm" role="group">
                    <button type="button" class="btn btn-outline-light active qr-lang-btn" data-lang="mr">मराठी</button>
                    <button type="button" class="btn btn-outline-light qr-lang-btn" data-lang="en">EN</button>
                    <button type="button" class="btn btn-outline-light qr-lang-btn" data-lang="hi">हिंदी</button>
                </div>
            </div>

            <!-- Player Bar -->
            <div class="d-flex align-items-center gap-3 my-3">
                <button type="button" class="btn btn-warning rounded-circle shadow d-flex align-items-center justify-content-center" id="qrPlayBtn" style="width: 52px; height: 52px; font-size: 1.3rem;">
                    <i class="fa-solid fa-play"></i>
                </button>
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between text-white-50 small mb-1">
                        <span id="qrCurrentTime">00:00</span>
                        <span id="qrStatusText">Ready to Play</span>
                        <span id="qrTotalTime">~2:00</span>
                    </div>
                    <div class="progress" style="height: 6px; background-color: rgba(255,255,255,0.2);">
                        <div class="progress-bar bg-warning" id="qrProgressBar" role="progressbar" style="width: 0%"></div>
                    </div>
                </div>
            </div>

            <!-- Playback Speed Controls -->
            <div class="d-flex justify-content-between align-items-center pt-2 border-top border-white border-opacity-10">
                <span class="small text-white-50">Speed:</span>
                <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-sm btn-outline-light py-0 px-2 qr-speed-btn" data-speed="0.8">0.8x</button>
                    <button type="button" class="btn btn-sm btn-outline-light active py-0 px-2 qr-speed-btn" data-speed="1.0">1.0x</button>
                    <button type="button" class="btn btn-sm btn-outline-light py-0 px-2 qr-speed-btn" data-speed="1.2">1.2x</button>
                </div>
                <button class="btn btn-sm text-white-50 text-decoration-none" type="button" data-bs-toggle="collapse" data-bs-target="#qrTranscript">
                    <i class="fa-solid fa-align-left me-1"></i>Read Transcript
                </button>
            </div>

            <!-- Transcript Accordion -->
            <div class="collapse mt-3" id="qrTranscript">
                <div class="p-3 rounded-3" style="background-color: rgba(0,0,0,0.25); font-size: 0.88rem; line-height: 1.6;" id="qrTranscriptText">
                    <?= nl2br(e($audioMr)) ?>
                </div>
            </div>
        </div>
    </div>

    <!-- History & Significance -->
    <div class="bg-white p-4 rounded-4 shadow-sm border mb-4">
        <h5 class="font-heritage text-success mb-3"><i class="fa-solid fa-landmark me-2 text-warning"></i>Historical Importance</h5>
        <div class="text-secondary small" style="line-height: 1.8;">
            <?= nl2br(e($destHist ?: $destination['overview'])) ?>
        </div>

        <?php if (!empty($destShivaji)): ?>
            <div class="mt-4 p-3 bg-light-subtle rounded-3 border-start border-4 border-warning">
                <h6 class="font-heritage text-dark fw-bold mb-2"><i class="fa-solid fa-crown text-warning me-1"></i>Connection with Chhatrapati Shivaji Maharaj</h6>
                <div class="text-secondary small" style="line-height: 1.7;">
                    <?= nl2br(e($destShivaji)) ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Key Structures & Architecture -->
    <?php if ($destination['major_attractions'] || $destination['architecture_notes']): ?>
        <div class="bg-white p-4 rounded-4 shadow-sm border mb-4">
            <h5 class="font-heritage text-success mb-3"><i class="fa-solid fa-monument me-2 text-warning"></i>Key Structures to Visit Here</h5>
            <?php if ($destination['major_attractions']): ?>
                <div class="p-3 bg-light rounded-3 mb-3 text-secondary small" style="line-height: 1.8;">
                    <?= nl2br(e($destination['major_attractions'])) ?>
                </div>
            <?php endif; ?>
            <?php if ($destination['architecture_notes']): ?>
                <p class="small text-muted mb-0"><i class="fa-solid fa-archway me-1 text-primary"></i><?= nl2br(e($destination['architecture_notes'])) ?></p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Safety & Emergency Contacts -->
    <div class="bg-white p-4 rounded-4 shadow-sm border mb-4">
        <h5 class="font-heritage text-danger mb-3"><i class="fa-solid fa-life-ring me-2"></i>Visitor Guidelines & Emergency Help</h5>
        
        <?php if ($destination['water_food_guidelines']): ?>
            <div class="mb-3">
                <strong class="small text-dark d-block mb-1"><i class="fa-solid fa-bottle-water text-primary me-1"></i>Water & Food:</strong>
                <p class="small text-muted mb-0"><?= e($destination['water_food_guidelines']) ?></p>
            </div>
        <?php endif; ?>

        <?php if ($destination['emergency_contacts']): ?>
            <div class="mb-3">
                <strong class="small text-dark d-block mb-1"><i class="fa-solid fa-phone-volume text-danger me-1"></i>Local Emergency Contacts:</strong>
                <p class="small text-muted mb-0"><?= e($destination['emergency_contacts']) ?></p>
            </div>
        <?php endif; ?>

        <div class="d-flex flex-wrap gap-2 pt-2 border-top">
            <a href="tel:112" class="btn btn-sm btn-outline-danger rounded-pill">
                <i class="fa-solid fa-phone me-1"></i>State Emergency (112)
            </a>
            <a href="tel:7620230230" class="btn btn-sm btn-outline-dark rounded-pill">
                <i class="fa-solid fa-mountain me-1"></i>Sahyadri Rescue (MMRCC)
            </a>
            <a href="destination.php?slug=<?= urlencode($destination['slug']) ?>#reviews-section" class="btn btn-sm btn-fe-gold rounded-pill ms-auto">
                <i class="fa-solid fa-star me-1"></i>Leave a Review
            </a>
        </div>
    </div>
</div>

<script>
// Multilingual Audio Guide Speech Synthesis for QR Guide
(function() {
    const texts = {
        mr: <?= json_encode($audioMr) ?>,
        en: <?= json_encode($audioEn) ?>,
        hi: <?= json_encode($audioHi) ?>
    };

    let currentLang = 'mr';
    let currentSpeed = 1.0;
    let synth = window.speechSynthesis;
    let utterance = null;
    let isPlaying = false;
    let isPaused = false;
    let timer = null;
    let elapsedSeconds = 0;
    let totalEstSeconds = 120;

    const playBtn = document.getElementById('qrPlayBtn');
    const playIcon = playBtn ? playBtn.querySelector('i') : null;
    const progressBar = document.getElementById('qrProgressBar');
    const currentTimeEl = document.getElementById('qrCurrentTime');
    const totalTimeEl = document.getElementById('qrTotalTime');
    const statusTextEl = document.getElementById('qrStatusText');
    const transcriptEl = document.getElementById('qrTranscriptText');

    function formatTime(s) {
        const m = Math.floor(s / 60);
        const sec = s % 60;
        return `${m < 10 ? '0' : ''}${m}:${sec < 10 ? '0' : ''}${sec}`;
    }

    function calculateTotalEstimate() {
        const wordCount = (texts[currentLang] || '').split(/\s+/).length;
        totalEstSeconds = Math.max(30, Math.round((wordCount / (130 * currentSpeed)) * 60));
        if (totalTimeEl) totalTimeEl.textContent = formatTime(totalEstSeconds);
    }

    function updateTranscript() {
        if (transcriptEl) {
            transcriptEl.innerHTML = (texts[currentLang] || '').replace(/\n/g, '<br>');
        }
    }

    function startTimer() {
        clearInterval(timer);
        timer = setInterval(() => {
            if (isPlaying && !isPaused) {
                elapsedSeconds++;
                if (currentTimeEl) currentTimeEl.textContent = formatTime(elapsedSeconds);
                const pct = Math.min(100, (elapsedSeconds / totalEstSeconds) * 100);
                if (progressBar) progressBar.style.width = pct + '%';
            }
        }, 1000);
    }

    function stopPlayback() {
        if (synth) synth.cancel();
        isPlaying = false;
        isPaused = false;
        clearInterval(timer);
        elapsedSeconds = 0;
        if (playIcon) playIcon.className = 'fa-solid fa-play';
        if (progressBar) progressBar.style.width = '0%';
        if (currentTimeEl) currentTimeEl.textContent = '00:00';
        if (statusTextEl) statusTextEl.textContent = 'Ready to Play';
    }

    function playAudio() {
        if (!synth) {
            alert('Audio speech playback not supported in this browser.');
            return;
        }

        if (isPaused) {
            synth.resume();
            isPaused = false;
            isPlaying = true;
            if (playIcon) playIcon.className = 'fa-solid fa-pause';
            if (statusTextEl) statusTextEl.textContent = 'Playing...';
            return;
        }

        synth.cancel();
        const text = texts[currentLang] || texts.en;
        utterance = new SpeechSynthesisUtterance(text);

        const langMap = { mr: 'mr-IN', hi: 'hi-IN', en: 'en-IN' };
        utterance.lang = langMap[currentLang] || 'en-US';
        utterance.rate = currentSpeed;

        const voices = synth.getVoices();
        const targetVoice = voices.find(v => v.lang.startsWith(utterance.lang.slice(0, 2)));
        if (targetVoice) utterance.voice = targetVoice;

        utterance.onstart = () => {
            isPlaying = true;
            isPaused = false;
            if (playIcon) playIcon.className = 'fa-solid fa-pause';
            if (statusTextEl) statusTextEl.textContent = 'Playing...';
            startTimer();
        };

        utterance.onend = () => {
            stopPlayback();
            if (statusTextEl) statusTextEl.textContent = 'Completed';
        };

        utterance.onerror = () => {
            stopPlayback();
            if (statusTextEl) statusTextEl.textContent = 'Playback error';
        };

        synth.speak(utterance);
    }

    function pauseAudio() {
        if (synth && isPlaying) {
            synth.pause();
            isPaused = true;
            isPlaying = false;
            if (playIcon) playIcon.className = 'fa-solid fa-play';
            if (statusTextEl) statusTextEl.textContent = 'Paused';
        }
    }

    if (playBtn) {
        playBtn.addEventListener('click', () => {
            if (isPlaying) {
                pauseAudio();
            } else {
                playAudio();
            }
        });
    }

    // Language buttons
    document.querySelectorAll('.qr-lang-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            document.querySelectorAll('.qr-lang-btn').forEach(b => b.classList.remove('active'));
            e.currentTarget.classList.add('active');
            currentLang = e.currentTarget.dataset.lang;
            stopPlayback();
            calculateTotalEstimate();
            updateTranscript();
        });
    });

    // Speed buttons
    document.querySelectorAll('.qr-speed-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            document.querySelectorAll('.qr-speed-btn').forEach(b => b.classList.remove('active'));
            e.currentTarget.classList.add('active');
            currentSpeed = parseFloat(e.currentTarget.dataset.speed);
            calculateTotalEstimate();
            if (isPlaying) {
                stopPlayback();
                playAudio();
            }
        });
    });

    calculateTotalEstimate();
    updateTranscript();
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
