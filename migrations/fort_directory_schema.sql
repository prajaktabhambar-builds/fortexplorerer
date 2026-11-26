-- ==============================================================
-- FORT EXPLORER - MAHARASHTRA FORT DIRECTORY EXPANDED SCHEMA
-- Normalized 36 Districts, Fort Categories, Forts, References, Locations,
-- Verification, History, and Images tables.
-- ==============================================================

-- 1. Ensure all 36 Districts of Maharashtra exist
CREATE TABLE IF NOT EXISTS districts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name_en VARCHAR(100) NOT NULL UNIQUE,
    name_mr VARCHAR(100) NOT NULL,
    division VARCHAR(100) DEFAULT 'Maharashtra',
    headquarters VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE districts ADD COLUMN IF NOT EXISTS division VARCHAR(100) DEFAULT 'Maharashtra';
ALTER TABLE districts ADD COLUMN IF NOT EXISTS headquarters VARCHAR(100) DEFAULT NULL;

-- Update existing 1-10 and insert 11-36 with exact modern district nomenclature
INSERT INTO districts (id, name_en, name_mr, division, headquarters) VALUES
(1, 'Pune', 'पुणे', 'Pune Division', 'Pune'),
(2, 'Raigad', 'रायगड', 'Konkan Division', 'Alibag'),
(3, 'Satara', 'सातारा', 'Pune Division', 'Satara'),
(4, 'Kolhapur', 'कोल्हापूर', 'Pune Division', 'Kolhapur'),
(5, 'Nashik', 'नाशिक', 'Nashik Division', 'Nashik'),
(6, 'Ahilyanagar', 'अहिल्यानगर', 'Nashik Division', 'Ahilyanagar'),
(7, 'Sindhudurg', 'सिंधुदुर्ग', 'Konkan Division', 'Oros'),
(8, 'Ratnagiri', 'रत्नागिरी', 'Konkan Division', 'Ratnagiri'),
(9, 'Chhatrapati Sambhajinagar', 'छत्रपती संभाजीनगर', 'Marathwada Division', 'Chhatrapati Sambhajinagar'),
(10, 'Thane', 'ठाणे', 'Konkan Division', 'Thane'),
(11, 'Palghar', 'पालघर', 'Konkan Division', 'Palghar'),
(12, 'Mumbai City', 'मुंबई शहर', 'Konkan Division', 'Mumbai'),
(13, 'Mumbai Suburban', 'मुंबई उपनगर', 'Konkan Division', 'Bandra'),
(14, 'Sangli', 'सांगली', 'Pune Division', 'Sangli'),
(15, 'Solapur', 'सोलापूर', 'Pune Division', 'Solapur'),
(16, 'Dhule', 'धुळे', 'Nashik Division', 'Dhule'),
(17, 'Jalgaon', 'जळगाव', 'Nashik Division', 'Jalgaon'),
(18, 'Nandurbar', 'नंदुरबार', 'Nashik Division', 'Nandurbar'),
(19, 'Jalna', 'जालना', 'Marathwada Division', 'Jalna'),
(20, 'Beed', 'बीड', 'Marathwada Division', 'Beed'),
(21, 'Latur', 'लातूर', 'Marathwada Division', 'Latur'),
(22, 'Dharashiv', 'धाराशिव', 'Marathwada Division', 'Dharashiv'),
(23, 'Nanded', 'नांदेड', 'Marathwada Division', 'Nanded'),
(24, 'Parbhani', 'परभणी', 'Marathwada Division', 'Parbhani'),
(25, 'Hingoli', 'हिंगोली', 'Marathwada Division', 'Hingoli'),
(26, 'Akola', 'अकोला', 'Amravati Division', 'Akola'),
(27, 'Amravati', 'अमरावती', 'Amravati Division', 'Amravati'),
(28, 'Buldhana', 'बुलढाणा', 'Amravati Division', 'Buldhana'),
(29, 'Washim', 'वाशीम', 'Amravati Division', 'Washim'),
(30, 'Yavatmal', 'यवतमाळ', 'Amravati Division', 'Yavatmal'),
(31, 'Nagpur', 'नागपूर', 'Nagpur Division', 'Nagpur'),
(32, 'Wardha', 'वर्धा', 'Nagpur Division', 'Wardha'),
(33, 'Bhandara', 'भंडारा', 'Nagpur Division', 'Bhandara'),
(34, 'Gondia', 'गोंदिया', 'Nagpur Division', 'Gondia'),
(35, 'Chandrapur', 'चंद्रपूर', 'Nagpur Division', 'Chandrapur'),
(36, 'Gadchiroli', 'गडचिरोली', 'Nagpur Division', 'Gadchiroli')
ON DUPLICATE KEY UPDATE 
    name_en = VALUES(name_en),
    name_mr = VALUES(name_mr),
    division = VALUES(division),
    headquarters = VALUES(headquarters);

-- 2. Fort Categories Table
CREATE TABLE IF NOT EXISTS fort_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(60) NOT NULL UNIQUE,
    name_en VARCHAR(100) NOT NULL,
    name_mr VARCHAR(100) NOT NULL,
    traditional_name VARCHAR(100) NOT NULL,
    traditional_name_mr VARCHAR(100) NOT NULL,
    description_en TEXT DEFAULT NULL,
    description_mr TEXT DEFAULT NULL,
    badge_icon VARCHAR(50) DEFAULT 'fa-shield-halved',
    badge_color VARCHAR(30) DEFAULT '#163820',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO fort_categories (id, code, name_en, name_mr, traditional_name, traditional_name_mr, description_en, description_mr, badge_icon, badge_color) VALUES
(1, 'hill_fort', 'Hill Fort', 'गिरिदुर्ग', 'Giridurg', 'गिरिदुर्ग', 'Fortresses perched atop rugged mountain cliffs, plateaus, and basalt ridges designed for territorial defense.', 'सह्याद्रीच्या उत्तुंग कड्यांवर आणि डोंगरमाथ्यावर नैसर्गिक संरक्षण असलेले अभेद्य किल्ले.', 'fa-mountain', '#163820'),
(2, 'sea_fort', 'Sea Fort', 'जलदुर्ग', 'Jaldurg', 'जलदुर्ग', 'Naval fortifications constructed on rock reefs or surrounded by the Arabian Sea to defend maritime sovereignty.', 'अरबी समुद्राच्या पाण्याने वेढलेले आणि सागरी सीमांचे रक्षण करणारे अभेद्य नौदल किल्ले.', 'fa-water', '#0284c7'),
(3, 'island_fort', 'Island Fort', 'बेटावरील किल्ला', 'Island Jaldurg', 'बेटावरील जलदुर्ग', 'Forts built entirely on offshore maritime islands with naval docks and independent fresh water reservoirs.', 'समुद्रातील स्वतंत्र बेटांवर उभारलेले दुर्ग जसे खांदेरी, उंदेरी, पद्मदुर्ग इत्यादी.', 'fa-anchor', '#0369a1'),
(4, 'land_fort', 'Land Fort', 'भुईकोट किल्ला', 'Bhuikot', 'भुईकोट', 'Plains fortresses surrounded by deep dry or wet moats, massive bastions, and thick stone ramparts.', 'सपाट मैदानावर खंदक, खंदकातील पाणी व भव्य बुरुजांनी वेढलेले प्रशासकीय भुईकोट किल्ले.', 'fa-landmark', '#8b2616'),
(5, 'forest_fort', 'Forest Fort', 'वनदुर्ग', 'Vanadurg', 'वनदुर्ग', 'Fortifications hidden deep within dense wilderness, forest gorges, and impenetrable woodland.', 'दाट अरण्य, खोल दर्‍या आणि घनदाट जंगलात नैसर्गिकरीत्या दडलेले किल्ले.', 'fa-tree', '#15803d'),
(6, 'coastal_fort', 'Coastal Fort', 'किनारी किल्ला', 'Coastal Fort', 'किनारी किल्ला', 'Headland and shoreline fortresses guarding creek estuaries, trade river mouths, and harbors.', 'खाडीची मुखे, सागरी व्यापारी बंदरे आणि समुद्रकिनाऱ्यावरील उंच टेकड्यांवर वसलेले किल्ले.', 'fa-ship', '#d97706'),
(7, 'fortified_heritage', 'Fortified Heritage / Gadhi', 'गढी व कोट', 'Gadhi / Kot', 'गढी / कोट', 'Historical fortified mansions, fortified wada settlements, watchtowers, and city defensive gates.', 'ऐतिहासिक गढ्या, तटबंदीयुक्त वाडे, कोट आणि संरक्षण बुरुज.', 'fa-archway', '#6b7280')
ON DUPLICATE KEY UPDATE 
    name_en = VALUES(name_en),
    name_mr = VALUES(name_mr),
    traditional_name = VALUES(traditional_name),
    traditional_name_mr = VALUES(traditional_name_mr);

-- 3. Extend `destinations` table with fort-specific metadata if not present
ALTER TABLE destinations ADD COLUMN IF NOT EXISTS taluka VARCHAR(100) DEFAULT NULL;
ALTER TABLE destinations ADD COLUMN IF NOT EXISTS village VARCHAR(150) DEFAULT NULL;
ALTER TABLE destinations ADD COLUMN IF NOT EXISTS alternate_names VARCHAR(255) DEFAULT NULL;
ALTER TABLE destinations ADD COLUMN IF NOT EXISTS is_unesco_site TINYINT(1) DEFAULT 0;
ALTER TABLE destinations ADD COLUMN IF NOT EXISTS unesco_details TEXT DEFAULT NULL;
ALTER TABLE destinations ADD COLUMN IF NOT EXISTS historical_period VARCHAR(100) DEFAULT NULL;
ALTER TABLE destinations ADD COLUMN IF NOT EXISTS dynasties_rulers TEXT DEFAULT NULL;
ALTER TABLE destinations ADD COLUMN IF NOT EXISTS fort_category_id INT DEFAULT NULL;
ALTER TABLE destinations ADD COLUMN IF NOT EXISTS verification_status ENUM('verified', 'pending_verification', 'needs_correction', 'unverified') DEFAULT 'verified';
ALTER TABLE destinations ADD COLUMN IF NOT EXISTS reference_sources TEXT DEFAULT NULL;
ALTER TABLE destinations ADD COLUMN IF NOT EXISTS verified_by VARCHAR(150) DEFAULT 'Heritage Research Archive';
ALTER TABLE destinations ADD COLUMN IF NOT EXISTS last_verified_at DATE DEFAULT NULL;

-- 4. Create Master `forts` Table
CREATE TABLE IF NOT EXISTS forts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    destination_id INT DEFAULT NULL,
    name VARCHAR(150) NOT NULL,
    name_mr VARCHAR(150) NOT NULL,
    alternate_names VARCHAR(255) DEFAULT NULL,
    slug VARCHAR(160) NOT NULL UNIQUE,
    district_id INT NOT NULL,
    taluka VARCHAR(100) DEFAULT NULL,
    village VARCHAR(150) DEFAULT NULL,
    latitude DECIMAL(10, 7) DEFAULT NULL,
    longitude DECIMAL(10, 7) DEFAULT NULL,
    altitude_meters INT DEFAULT NULL,
    category_id INT NOT NULL DEFAULT 1,
    sub_category VARCHAR(100) DEFAULT NULL,
    historical_period VARCHAR(100) DEFAULT NULL,
    dynasties_rulers TEXT DEFAULT NULL,
    historical_background LONGTEXT DEFAULT NULL,
    historical_background_mr LONGTEXT DEFAULT NULL,
    is_shivaji_fort TINYINT(1) DEFAULT 0,
    shivaji_connection LONGTEXT DEFAULT NULL,
    shivaji_connection_mr LONGTEXT DEFAULT NULL,
    is_unesco_site TINYINT(1) DEFAULT 0,
    unesco_inscription_details TEXT DEFAULT NULL,
    architecture_type VARCHAR(150) DEFAULT NULL,
    architecture_notes LONGTEXT DEFAULT NULL,
    key_structures LONGTEXT DEFAULT NULL,
    water_sources TEXT DEFAULT NULL,
    trekking_difficulty ENUM('Easy', 'Moderate', 'Challenging', 'Difficult') DEFAULT 'Moderate',
    trek_duration_hours DECIMAL(4, 1) DEFAULT NULL,
    best_season VARCHAR(100) DEFAULT 'Winter & Post-Monsoon',
    how_to_reach TEXT DEFAULT NULL,
    nearest_railway VARCHAR(150) DEFAULT NULL,
    nearest_airport VARCHAR(150) DEFAULT NULL,
    visitor_facilities VARCHAR(255) DEFAULT NULL,
    safety_guidelines TEXT DEFAULT NULL,
    primary_image VARCHAR(500) DEFAULT NULL,
    verification_status ENUM('verified', 'pending_verification', 'needs_correction', 'unverified') DEFAULT 'verified',
    verification_notes TEXT DEFAULT NULL,
    verified_by VARCHAR(150) DEFAULT 'Maharashtra Heritage Directorate',
    last_verified_at DATE DEFAULT NULL,
    reference_sources TEXT DEFAULT NULL,
    view_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (district_id) REFERENCES districts(id) ON DELETE RESTRICT,
    FOREIGN KEY (category_id) REFERENCES fort_categories(id) ON DELETE RESTRICT,
    FOREIGN KEY (destination_id) REFERENCES destinations(id) ON DELETE SET NULL,
    INDEX idx_fort_district (district_id),
    INDEX idx_fort_cat (category_id),
    INDEX idx_fort_unesco (is_unesco_site),
    INDEX idx_fort_status (verification_status),
    INDEX idx_fort_shivaji (is_shivaji_fort)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Create `fort_references` Table
CREATE TABLE IF NOT EXISTS fort_references (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fort_id INT NOT NULL,
    source_title VARCHAR(255) NOT NULL,
    source_type ENUM('asi_record', 'state_gazetteer', 'unesco_dossier', 'historical_chronicle', 'academic_paper', 'mtdc_archive') NOT NULL DEFAULT 'state_gazetteer',
    author_or_body VARCHAR(200) DEFAULT NULL,
    volume_or_page VARCHAR(100) DEFAULT NULL,
    source_url VARCHAR(500) DEFAULT NULL,
    verification_date DATE DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (fort_id) REFERENCES forts(id) ON DELETE CASCADE,
    INDEX idx_fr_fort (fort_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Create `fort_history` Table (Chronological Events)
CREATE TABLE IF NOT EXISTS fort_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fort_id INT NOT NULL,
    event_year VARCHAR(50) DEFAULT NULL,
    event_title VARCHAR(255) NOT NULL,
    event_title_mr VARCHAR(255) DEFAULT NULL,
    description TEXT NOT NULL,
    description_mr TEXT DEFAULT NULL,
    rulers_involved VARCHAR(200) DEFAULT NULL,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (fort_id) REFERENCES forts(id) ON DELETE CASCADE,
    INDEX idx_fh_fort (fort_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Create `fort_images` Table
CREATE TABLE IF NOT EXISTS fort_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fort_id INT NOT NULL,
    image_url VARCHAR(500) NOT NULL,
    caption VARCHAR(255) DEFAULT NULL,
    attribution VARCHAR(200) DEFAULT 'Heritage Archives',
    is_historical TINYINT(1) DEFAULT 0,
    is_primary TINYINT(1) DEFAULT 0,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (fort_id) REFERENCES forts(id) ON DELETE CASCADE,
    INDEX idx_fi_fort (fort_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Create `fort_verification` Table
CREATE TABLE IF NOT EXISTS fort_verification (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fort_id INT NOT NULL,
    reviewer_id INT DEFAULT NULL,
    previous_status VARCHAR(50) DEFAULT NULL,
    new_status ENUM('verified', 'pending_verification', 'needs_correction', 'rejected') NOT NULL,
    notes TEXT DEFAULT NULL,
    verified_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (fort_id) REFERENCES forts(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_fv_fort (fort_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
