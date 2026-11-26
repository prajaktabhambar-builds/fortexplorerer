-- ==============================================================
-- FORT EXPLORER - DATABASE SCHEMA & COMPREHENSIVE SAMPLE DATA
-- Digital Tourism & Heritage Exploration Platform for Maharashtra
-- ==============================================================

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS trip_destinations;
DROP TABLE IF EXISTS trips;
DROP TABLE IF EXISTS travel_photos;
DROP TABLE IF EXISTS visited_places;
DROP TABLE IF EXISTS wishlists;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS destination_images;
DROP TABLE IF EXISTS historical_events;
DROP TABLE IF EXISTS destinations;
DROP TABLE IF EXISTS destination_categories;
DROP TABLE IF EXISTS districts;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

-- -------------------------------------------------------------
-- 1. USERS TABLE
-- -------------------------------------------------------------
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(160) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    avatar VARCHAR(255) DEFAULT 'default-avatar.png',
    bio TEXT,
    phone VARCHAR(20) DEFAULT NULL,
    home_district VARCHAR(100) DEFAULT 'Pune',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_email (email),
    INDEX idx_user_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 2. DISTRICTS TABLE (Maharashtra Districts)
-- -------------------------------------------------------------
CREATE TABLE districts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name_en VARCHAR(100) NOT NULL UNIQUE,
    name_mr VARCHAR(100) NOT NULL,
    division VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 3. DESTINATION CATEGORIES TABLE
-- -------------------------------------------------------------
CREATE TABLE destination_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(80) NOT NULL UNIQUE,
    name_en VARCHAR(100) NOT NULL,
    name_mr VARCHAR(100) NOT NULL,
    icon_class VARCHAR(80) NOT NULL DEFAULT 'fa-map-pin',
    badge_color VARCHAR(30) NOT NULL DEFAULT '#1e3f20',
    description_en VARCHAR(255),
    description_mr VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 4. DESTINATIONS TABLE
-- -------------------------------------------------------------
CREATE TABLE destinations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    district_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    name_mr VARCHAR(150) NOT NULL,
    slug VARCHAR(160) NOT NULL UNIQUE,
    short_description TEXT NOT NULL,
    short_description_mr TEXT NOT NULL,
    overview LONGTEXT NOT NULL,
    historical_significance LONGTEXT,
    historical_significance_mr LONGTEXT,
    shivaji_connection LONGTEXT,
    shivaji_connection_mr LONGTEXT,
    architecture_notes LONGTEXT,
    major_attractions LONGTEXT,
    latitude DECIMAL(10, 7) NOT NULL,
    longitude DECIMAL(10, 7) NOT NULL,
    altitude_meters INT DEFAULT NULL,
    address VARCHAR(255) NOT NULL,
    best_season VARCHAR(100) NOT NULL,
    opening_hours VARCHAR(150) DEFAULT 'Open 24 hours / Daylight trekking recommended',
    entry_fees VARCHAR(150) DEFAULT 'Free / Nominal ASI ticket if applicable',
    is_timing_verified TINYINT(1) DEFAULT 1,
    how_to_reach_road TEXT,
    how_to_reach_rail TEXT,
    how_to_reach_air TEXT,
    distance_from_pune_km INT DEFAULT NULL,
    distance_from_mumbai_km INT DEFAULT NULL,
    facilities VARCHAR(255) DEFAULT 'Parking, Tea Stalls, Local Guides',
    safety_tips TEXT,
    difficulty_level ENUM('Easy', 'Moderate', 'Challenging', 'Difficult') DEFAULT 'Moderate',
    trek_duration_hours DECIMAL(3, 1) DEFAULT 2.5,
    is_featured TINYINT(1) DEFAULT 0,
    is_shivaji_fort TINYINT(1) DEFAULT 0,
    primary_image VARCHAR(500) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES destination_categories(id) ON DELETE RESTRICT,
    FOREIGN KEY (district_id) REFERENCES districts(id) ON DELETE RESTRICT,
    INDEX idx_dest_category (category_id),
    INDEX idx_dest_district (district_id),
    INDEX idx_dest_featured (is_featured),
    INDEX idx_dest_shivaji (is_shivaji_fort),
    INDEX idx_dest_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 5. DESTINATION IMAGES TABLE
-- -------------------------------------------------------------
CREATE TABLE destination_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    destination_id INT NOT NULL,
    image_url VARCHAR(500) NOT NULL,
    caption VARCHAR(255),
    attribution VARCHAR(255),
    is_primary TINYINT(1) DEFAULT 0,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (destination_id) REFERENCES destinations(id) ON DELETE CASCADE,
    INDEX idx_image_dest (destination_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 6. WISHLISTS TABLE ("Want to Visit")
-- -------------------------------------------------------------
CREATE TABLE wishlists (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    destination_id INT NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_wishlist (user_id, destination_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (destination_id) REFERENCES destinations(id) ON DELETE CASCADE,
    INDEX idx_wishlist_user (user_id),
    INDEX idx_wishlist_dest (destination_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 7. VISITED PLACES TABLE ("Visited Record")
-- -------------------------------------------------------------
CREATE TABLE visited_places (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    destination_id INT NOT NULL,
    visit_date DATE NOT NULL,
    rating INT DEFAULT 5,
    experience_notes TEXT,
    companion_type VARCHAR(50) DEFAULT 'Solo',
    trek_experience VARCHAR(50) DEFAULT 'Pleasant',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_visited (user_id, destination_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (destination_id) REFERENCES destinations(id) ON DELETE CASCADE,
    INDEX idx_visited_user (user_id),
    INDEX idx_visited_dest (destination_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 8. TRAVEL PHOTOS TABLE (User Uploaded Travel Memories)
-- -------------------------------------------------------------
CREATE TABLE travel_photos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    visited_id INT DEFAULT NULL,
    user_id INT NOT NULL,
    destination_id INT NOT NULL,
    image_path VARCHAR(500) NOT NULL,
    caption VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (visited_id) REFERENCES visited_places(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (destination_id) REFERENCES destinations(id) ON DELETE CASCADE,
    INDEX idx_photo_user (user_id),
    INDEX idx_photo_dest (destination_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 9. REVIEWS TABLE (Destination Reviews & Ratings)
-- -------------------------------------------------------------
CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    destination_id INT NOT NULL,
    user_id INT NOT NULL,
    rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    review_title VARCHAR(150),
    review_text TEXT NOT NULL,
    visit_season VARCHAR(50) DEFAULT 'Monsoon',
    status ENUM('approved', 'pending', 'rejected') DEFAULT 'approved',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (destination_id) REFERENCES destinations(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_review_dest (destination_id),
    INDEX idx_review_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 10. TRIPS TABLE (Trip Planner)
-- -------------------------------------------------------------
CREATE TABLE trips (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT,
    start_date DATE DEFAULT NULL,
    end_date DATE DEFAULT NULL,
    is_public TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_trip_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 11. TRIP DESTINATIONS TABLE (Itinerary Stops)
-- -------------------------------------------------------------
CREATE TABLE trip_destinations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trip_id INT NOT NULL,
    destination_id INT NOT NULL,
    day_number INT DEFAULT 1,
    visit_order INT DEFAULT 1,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE,
    FOREIGN KEY (destination_id) REFERENCES destinations(id) ON DELETE CASCADE,
    INDEX idx_td_trip (trip_id),
    INDEX idx_td_dest (destination_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 12. HISTORICAL EVENTS TABLE (Shivaji Maharaj Fort Timeline)
-- -------------------------------------------------------------
CREATE TABLE historical_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_year INT NOT NULL,
    date_formatted VARCHAR(100),
    title_en VARCHAR(200) NOT NULL,
    title_mr VARCHAR(200) NOT NULL,
    description_en TEXT NOT NULL,
    description_mr TEXT NOT NULL,
    related_destination_id INT DEFAULT NULL,
    icon_name VARCHAR(50) DEFAULT 'fa-landmark',
    importance_level ENUM('Milestone', 'Battle', 'Coronation', 'Treaty', 'Strategic') DEFAULT 'Milestone',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (related_destination_id) REFERENCES destinations(id) ON DELETE SET NULL,
    INDEX idx_history_year (event_year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ==============================================================
-- SAMPLE DATA INSERTION (REAL VERIFIED DATA)
-- ==============================================================

-- -------------------------------------------------------------
-- Users (Default Accounts)
-- Admin: admin@fortexplorer.com / Admin@FortExplorer2026!
-- Explorer: explorer@fortexplorer.com / Explorer@2026!
-- -------------------------------------------------------------
INSERT INTO users (id, name, email, password_hash, role, bio, home_district) VALUES
(1, 'Sahyadri Administrator', 'admin@fortexplorer.com', '$2y$10$dyKo0dC69wk729K/BJKhquTGUyJx19gH0nlW6omRUqAwkoufuVS8W', 'admin', 'Official curator and heritage archivist at Fort Explorer.', 'Pune'),
(2, 'Tanmay Patil', 'explorer@fortexplorer.com', '$2y$10$n.2RPokDHMFk6HPQGiDQRO1sKAVcyBlgp3mUqrQqOc9dPKe8mZFhS', 'user', 'Avid Sahyadri trekker, amateur historian and outdoor photographer.', 'Satara'),
(3, 'Pooja Deshmukh', 'pooja@fortexplorer.com', '$2y$10$n.2RPokDHMFk6HPQGiDQRO1sKAVcyBlgp3mUqrQqOc9dPKe8mZFhS', 'user', 'Heritage enthusiast exploring the glorious hill forts of Chhatrapati Shivaji Maharaj.', 'Mumbai');

-- -------------------------------------------------------------
-- Districts
-- -------------------------------------------------------------
INSERT INTO districts (id, name_en, name_mr, division) VALUES
(1, 'Pune', 'पुणे', 'Pune Division'),
(2, 'Raigad', 'रायगड', 'Konkan Division'),
(3, 'Satara', 'सातारा', 'Pune Division'),
(4, 'Kolhapur', 'कोल्हापूर', 'Pune Division'),
(5, 'Nashik', 'नाशिक', 'Nashik Division'),
(6, 'Ahmednagar', 'अहमदनगर', 'Nashik Division'),
(7, 'Sindhudurg', 'सिंधुदुर्ग', 'Konkan Division'),
(8, 'Ratnagiri', 'रत्नागिरी', 'Konkan Division'),
(9, 'Chhatrapati Sambhajinagar', 'छत्रपती संभाजीनगर', 'Marathwada Division'),
(10, 'Thane', 'ठाणे', 'Konkan Division');

-- -------------------------------------------------------------
-- Destination Categories
-- -------------------------------------------------------------
INSERT INTO destination_categories (id, slug, name_en, name_mr, icon_class, badge_color, description_en, description_mr) VALUES
(1, 'shivaji-forts', 'Shivaji Maharaj Forts', 'शिवकालीन दुर्ग', 'fa-shield-halved', '#8b2616', 'Glorious hill and coastal fortresses fortified or held by Chhatrapati Shivaji Maharaj.', 'छत्रपती शिवाजी महाराजांच्या पराक्रमाची आणि स्वराज्य निर्मितीची साक्ष देणारे अजिंक्य किल्ले.'),
(2, 'historical-monuments', 'Historical Monuments', 'ऐतिहासिक स्मारके', 'fa-monument', '#6d28d9', 'Ancient architecture, caves, and rock-cut marvels across Maharashtra.', 'महाराष्ट्रातील प्राचीन लेणी, मंदिरे आणि भव्य ऐतिहासिक वास्तू.'),
(3, 'hill-stations', 'Hill Stations', 'हवामान ठिकाणे / थंड हवेची ठिकाणे', 'fa-mountain-sun', '#15803d', 'Scenic high-altitude retreats amidst the lush Western Ghats.', 'सह्याद्रीच्या कुशीतील आल्हाददायक आणि निसर्गरम्य थंड हवेची ठिकाणे.'),
(4, 'waterfalls', 'Waterfalls', 'धबधबे', 'fa-water', '#0284c7', 'Roaring cascades and emerald plunge pools best visited in monsoon.', 'पावसाळ्यात हिरव्यागार दऱ्यांमधून कोसळणारे भव्य आणि नयनरम्य धबधबे.'),
(5, 'temples', 'Temples & Shrines', 'प्राचीन मंदिरे व तीर्थक्षेत्रे', 'fa-gopuram', '#b45309', 'Sacred Jyotirlingas and historic temple complexes with Hemadpanthi architecture.', 'हेमाडपंती वास्तुकला असलेली पवित्र तीर्थक्षेत्रे आणि ऐतिहासिक मंदिरे.'),
(6, 'nature-adventure', 'Nature & Adventure', 'निसर्ग व साहसी पर्यटन', 'fa-person-hiking', '#0f766e', 'Plateaus, deep gorges, trekking trails and biodiversity hotspots.', 'पठारे, अथांग कडे, ट्रेकिंगच्या नयनरम्य वाटा आणि जैवविविधता.');

-- -------------------------------------------------------------
-- Destinations (Accurate Coordinates, verified history & travel details)
-- -------------------------------------------------------------
INSERT INTO destinations (
    id, category_id, district_id, name, name_mr, slug,
    short_description, short_description_mr,
    overview, historical_significance, historical_significance_mr,
    shivaji_connection, shivaji_connection_mr,
    architecture_notes, major_attractions,
    latitude, longitude, altitude_meters, address,
    best_season, opening_hours, entry_fees, is_timing_verified,
    how_to_reach_road, how_to_reach_rail, how_to_reach_air,
    distance_from_pune_km, distance_from_mumbai_km,
    facilities, safety_tips, difficulty_level, trek_duration_hours,
    is_featured, is_shivaji_fort, primary_image
) VALUES
-- 1. Raigad Fort
(
    1, 1, 2, 'Raigad Fort', 'किल्ले रायगड', 'raigad-fort',
    'The historic capital of Chhatrapati Shivaji Maharajs Maratha Empire, perched majestically in the Sahyadris.',
    'छत्रपती शिवाजी महाराजांच्या हिंदवी स्वराज्याची राजधानी आणि सार्वभौमतेचे सर्वोच्च प्रतीक.',
    'Raigad is a monument to the sovereignty, vision, and strategic military acumen of Chhatrapati Shivaji Maharaj. Rising 820 meters above sea level in the Sahyadri mountain range, this hill fortress was chosen as the capital of the Maratha Kingdom in 1674. It is here that the great coronation (Rajyabhisheka) took place, heralding an era of swarajya and justice.',
    'Formerly known as Rairi, the fort was captured by Shivaji Maharaj from the More family of Javali in 1656. Recognizing its impregnable topography, surrounded on three sides by deep valleys, he rebuilt and expanded it through his chief architect Hiroji Indulkar into an impregnable royal capital.',
    '१६५६ मध्ये जावळीच्या मोऱ्यांकडून हा किल्ला जिंकल्यानंतर महाराजांनी यास स्वराज्याची राजधानी म्हणून घोषित केले. १६७४ मधील सुवर्ण राज्याभिषेक सोहळा आणि १६८० मधील महाराजांचे महानिर्वाण याच पवित्र भूमीवर घडले.',
    'Chhatrapati Shivaji Maharaj was crowned Chhatrapati here on June 6, 1674 by Pandit Gaga Bhatt. His royal throne, the Raj Sabha, courtrooms, queen palaces, and holy Samadhi (memorial) are situated on the plateau.',
    'महाराजांनी या किल्ल्याला आपले अंतिम विश्रामस्थान आणि राजधानी बनवले. येथे महाराजांची समाधी, राजदरबार आणि जगदीश्वर मंदिर आजही नतमस्तक होणाऱ्या शिवभक्तांना स्फूर्ती देतात.',
    'Supervised by master builder Hiroji Indulkar. Features the Maha Darwaja, massive stone bastions, Nagarkhana (royal acoustic court where even whispers carry to the throne), Bazar Peth (two-row elevated market), and Hirkani Buruj.',
    'Maha Darwaja, Raj Sabha (Royal Durbar), Queen Palaces, Jagdishwar Temple, Shivaji Maharaj Samadhi, Takmak Tok, Hirkani Bastion, Ganga Sagar Lake, and the modern ropeway.',
    18.2346760, 73.4413690, 820, 'Raigad Fort, Mahad Taluka, Raigad District, Maharashtra 402305',
    'October to March (Winter) & July to September (Monsoon)',
    'Fort: Open 06:00 AM - 06:00 PM; Ropeway: 08:00 AM - 05:30 PM',
    'Adults: INR 25 (ASI ticket), Free below 15 yrs. Ropeway: Approx INR 350-400 round trip.',
    1,
    'Via Mumbai-Goa Highway (NH 66) to Mahad, then 24 km state road to Pachad base village. Well-paved motorable roads with parking.',
    'Nearest railway station is Mangaon (45 km) and Veer (40 km) on the Konkan Railway network.',
    'Nearest airport is Chhatrapati Shivaji Maharaj International Airport, Mumbai (140 km) and Pune Airport (130 km).',
    130, 160,
    'Parking at Pachad, MTDC Resort on fort top, Ropeway, Restrooms, Drinking water points, Local traditional Maharashtrian food (Pithla Bhakri).',
    'Trek involves ~1,737 stone steps or the 5-minute ropeway. Wear sturdy trekking footwear and carry water. Takmak Tok cliff is extremely steep; do not cross safety railings.',
    'Moderate', 3.0, 1, 1,
    'https://images.unsplash.com/photo-1598894000396-bc4a04d509f6?auto=format&fit=crop&w=1200&q=80'
),

-- 2. Shivneri Fort
(
    2, 1, 1, 'Shivneri Fort', 'किल्ले शिवनेरी', 'shivneri-fort',
    'The sacred birthplace of Chhatrapati Shivaji Maharaj, fortified with seven massive defensive gates.',
    'छत्रपती शिवाजी महाराजांचे पवित्र जन्मस्थान आणि ऐतिहासिक गिरिदुर्ग.',
    'Shivneri Fort is a triangular hill fortress standing near Junnar in Pune district. It holds an immortal place in Indian history as the birthplace of Chhatrapati Shivaji Maharaj on February 19, 1630. Blessed by the goddess Shivai, this fort was engineered with seven formidable gates and sheer vertical rock cliffs on all sides.',
    'Held by the Yadavas of Devagiri, Bahmanis, and Nizamshahi, the fort was granted to Shivaji Maharajs grandfather Maloji Bhosle and later held by Shahaji Raje. Jijau (Queen Mother Jijabai) stayed here during turbulent times, and the child Shiva was born in the central Shivkunj mansion.',
    '१९ फेब्रुवारी १६३० रोजी या पवित्र गडावर शिवरायांचा जन्म झाला. राजमाता जिजाऊंनी शिवाई देवीला नवस केला आणि जन्मलेल्या पुत्राचे नाव ''शिवाजी'' ठेवले. स्वराज्याची पहिली प्रेरणा याच भूमीतून निर्माण झाली.',
    'The infant years of Chhatrapati Shivaji Maharaj were nurtured inside the Shivneri fortress, learning early governance and warfare principles under the watchful eye of his mother Jijabai.',
    'महाराजांचे बालपण आणि पहिली शस्त्रविद्या याच गडावर घडली. येथे शिवकुंज स्मारक, शिवाई मातेचे मंदिर आणि राजमाता जिजाऊंचे निवासस्थान आहे.',
    'Triangular layout with seven sequential entrance gateways: Maha Darwaja, Pir Darwaja, Parvangi Darwaja, Hathi Darwaja, Fatak Darwaja, Kulambkat Darwaja, and Shiva Darwaja. Features rock-cut water cisterns (Ganga and Jamuna).',
    'Birthplace chamber (Janmasthan) at Shivkunj, Shivai Devi Temple, Ambarkhana (granary), Badami Talav, Kadelot Point, Ganga-Jamuna rock-cut water tanks.',
    19.1963900, 73.8597200, 1060, 'Shivneri Fort, Junnar Taluka, Pune District, Maharashtra 410502',
    'September to February (Pleasant winter and post-monsoon greenery)',
    '06:00 AM - 06:00 PM (Everyday)',
    'Indian Citizens: INR 25, Foreign Nationals: INR 300 (ASI regulated)',
    1,
    'From Pune via NH 60 to Narayangaon and Junnar (approx 95 km). Regular state transport (MSRTC) buses connect Pune and Mumbai to Junnar.',
    'Pune Junction Railway Station (95 km) is the primary broad gauge hub.',
    'Pune International Airport (PNQ) - approx 90 km.',
    95, 160,
    'Paved pathway with stone steps, handrails, drinking water, shaded resting gazebos, museum hall, clean public toilets at base.',
    'Family and child friendly trek with well-maintained steps. Summers get hot; start early in the morning. Keep monkeys at a distance and avoid carrying open food.',
    'Easy', 1.5, 1, 1,
    'https://images.unsplash.com/photo-1627894483216-2138af692e32?auto=format&fit=crop&w=1200&q=80'
),

-- 3. Sinhagad Fort
(
    3, 1, 1, 'Sinhagad Fort', 'किल्ले सिंहगड', 'sinhagad-fort',
    'The iconic Lion Fort overlooking Pune, immortalized by the supreme sacrifice of Tanaji Malusare in 1670.',
    'पुण्याजवळील शौर्याचा अजरामर इतिहास सांगणारा गड - ''गड आला पण सिंह गेला''.',
    'Sinhagad (The Lion Fort), originally known as Kondhana, is located on an isolated cliff of the Bhuleshwar range, 30 km southwest of Pune. The fort is world-renowned for the legendary night battle in 1670 where Subedar Tanaji Malusare scaled the sheer cliff using a monitor lizard (Ghorpad) and recaptured the fort for Swarajya, sacrificing his life.',
    'Inhabited for over two millennia, the fort witnessed major clashes between the Marathas and Mughals. After the battle of 1670, Shivaji Maharaj famously grieved: "Gadh aala pan sinha gela" (We won the fort, but lost our lion), subsequently renaming Kondhana to Sinhagad.',
    'तानाजी मालुसरे यांच्या असीम शौर्याची आणि बलिदानाची ही अमर गाथा आहे. १६७० मध्ये अवघड कडा चढून मुघल किल्लेदार उदयभानला पराभूत करत मराठ्यांनी हा किल्ला पुन्हा जिंकला.',
    'Key bastion guarding the heartland of Pune. Shivaji Maharaj frequented this fort and personally supervised the memorial honoring Tanaji Malusare.',
    'छत्रपती शिवाजी महाराजांच्या अनेक मोहिमांचे हे महत्त्वाचे केंद्र होते. महाराजांचे धाकटे पुत्र राजाराम महाराज यांचे निधनही याच गडावर झाले, ज्यांची समाधी आजही येथे आहे.',
    'Fortified with two primary massive gateways: Kalyan Darwaja (facing southwest towards Konkan) and Pune Darwaja (facing northeast). Features ancient rock-cut Devtaki water tanks.',
    'Tanaji Malusares Samadhi & memorial, Kalyan Darwaja, Pune Darwaja, Devtaki (sweet freshwater spring), Lokmanya Tilak Bungalow, Rajaram Maharaj Samadhi, Kade Lot point.',
    18.3663100, 73.7558300, 1312, 'Sinhagad Ghat Road, Thoptewadi, Pune District, Maharashtra 411025',
    'June to February (Spectacular mist during Monsoon, cool breeze in Winter)',
    '05:00 AM - 06:00 PM (Forest Dept Gate opens 06:00 AM)',
    'Trekking: Free; Forest Vehicle Toll: Two-wheeler INR 50, Four-wheeler INR 100',
    1,
    'Well-connected by paved motorable ghat road up to the top from Donje / Khadakwasla, with frequent PMPML electric buses from Pune Swargate and Deccan.',
    'Pune Junction Railway Station (32 km).',
    'Pune International Airport (38 km).',
    30, 180,
    'Paid vehicle parking, authentic hot Pithla-Bhakri, Kanda Bhaji, Matka Dahi stalls, fresh Taak (buttermilk), guide services.',
    'Do not speed on the narrow ghat road. Weekend traffic is heavy; prefer early morning visits or electric public transit. Slippery trails during peak rain.',
    'Moderate', 2.0, 1, 1,
    'https://images.unsplash.com/photo-1589308078059-be1415eab4c3?auto=format&fit=crop&w=1200&q=80'
),

-- 4. Pratapgad Fort
(
    4, 1, 3, 'Pratapgad Fort', 'किल्ले प्रतापगड', 'pratapgad-fort',
    'The mountain bastion of valor where Chhatrapati Shivaji Maharaj vanquished Afzal Khan in 1659.',
    'शिवरायांच्या रणनीतीचे आणि पराक्रमाचे जिवंत साक्षीदार - अफझलखानाचा पराभव घडवणारा दुर्ग.',
    'Pratapgad (Valour Fort) stands near the hill station of Mahabaleshwar in Satara district. Commissioned by Chhatrapati Shivaji Maharaj and built by Prime Minister Moropant Trimbak Pingle in 1656, it was designed to control the strategic Radtondi ghat pass between the Deccan plateau and coastal Konkan.',
    'On November 10, 1659, the historic meeting between Shivaji Maharaj and the towering Bijapuri general Afzal Khan took place at the foot of Pratapgad. When Khan deceitfully attempted to strangle Maharaj, Shivaji Maharaj retaliated with the wagh-nakha (tiger claws) and bichawa, changing the trajectory of Deccan history.',
    '१० नोव्हेंबर १६५९ रोजी महाराजांनी येथे महापराक्रमी मुत्सद्देगिरीने अफझलखानाचा कोथळा बाहेर काढला आणि विजापूरच्या आदिलशाही सैन्याचा धुव्वा उडवला. हा विजय हिंदवी स्वराज्याला अढळ स्थान मिळवून देणारा ठरला.',
    'Shivaji Maharaj personally consecrated the grand temple of Goddess Bhavani on the upper fort in 1661, installing a carved black stone idol sourced from Nepal.',
    'महाराजांनी या किल्ल्यावर कुलस्वामिनी श्री भवानी मातेची स्थापना केली आणि आजही छत्रपतींचे अश्वारूढ भव्य कांस्य शिल्प गडावर अभिमानाने उभे आहे.',
    'Divided into the Upper Fort (Balekilla) built on the crest of the hill and Lower Fort flanked by massive machis (platforms) and bastions. Features Afzal Buruj and the Rahat Lake.',
    'Bhavani Mata Temple, Afzal Khan Tomb (at foot), Equestrian bronze statue of Chhatrapati Shivaji Maharaj inaugurated by Jawaharlal Nehru in 1957, Kadelot Point, Double-walled bastions.',
    17.9351000, 73.5794000, 1080, 'Poladpur-Mahabaleshwar Road, Pratapgad, Satara District, Maharashtra 412806',
    'July to March (Lush green post-monsoon and cool winter climate)',
    '06:00 AM - 06:30 PM (Daily)',
    'Entry is Free. Local registered guides available for INR 300-500.',
    1,
    'Located 21 km from Mahabaleshwar on the road towards Poladpur. State transport buses and private taxis ply regularly from Mahabaleshwar and Satara.',
    'Satara Railway Station (75 km) or Wathar (85 km).',
    'Pune International Airport (145 km).',
    140, 220,
    'Parking at base village, Handicraft & honey stalls, Traditional dining rooms, Clean restrooms, Local tour guide association.',
    'Around 450 stone steps from the parking lot to the upper citadel. Handrails are present on steeper portions.',
    'Easy', 2.0, 1, 1,
    'https://images.unsplash.com/photo-1609137144820-7f24f5d38c64?auto=format&fit=crop&w=1200&q=80'
),

-- 5. Rajgad Fort
(
    5, 1, 1, 'Rajgad Fort', 'किल्ले राजगड', 'rajgad-fort',
    'The King of Forts - the sovereign capital of the Maratha Empire for more than 26 glory-filled years.',
    'दुर्गांचा राजा आणि राजांचा दुर्ग - स्वराज्याची २६ हून अधिक वर्षे मुख्य राजधानी राहिलेला गड.',
    'Rajgad (Royal Fort) in Pune district is hailed as the "King of Forts". It remained the primary capital of Chhatrapati Shivaji Maharaj for over two and a half decades until the capital was transferred to Raigad in 1672. It boasts an expansive diameter and three legendary machis (Padmavati, Suvela, and Sanjivani) radiating out like wings.',
    'Shivaji Maharaj consolidated his early kingdom from Rajgad. It is here that Saibai (his beloved queen) breathed her last, Rajaram Maharaj was born, and the strategic planning for the Agra escape and Deccan consolidation was engineered.',
    'छत्रपती शिवरायांनी २६ वर्षे याच गडावरून स्वराज्याचा कारभार चालवला. गडाची अफाट रचना, तिन्ही माच्या (पद्मावती, सुवेळा, संजीवनी) आणि बालेकिल्ला हे मध्ययुगीन युद्धशास्त्रातील चमत्कार मानले जातात.',
    'The fort where Chhatrapati Shivaji Maharaj spent the maximum number of years in his lifetime. The Padmavati Temple on the plateau served as the royal courtroom during the formative years of Swarajya.',
    'शिवरायांचे दीर्घकाळ वास्तव्य लाभलेला हा गड आहे. आग्रा सुटकेनंतर महाराज सर्वप्रथम राजगडावरच सुरक्षित परत आले होते.',
    'Unique architectural marvel with three fortified prongs: Suvela Machi (featuring the natural rock hole Nedhe), Sanjivani Machi (three-tier defensive walls with secret escape sally ports), and Padmavati Machi topped by the soaring Balekilla.',
    'Balekilla (citadel with rock-cut steps), Nedhe (natural wind eye on Suvela Machi), Padmavati Temple & Lake, Sanjivani Machi double fortifications, Pali & Gunjavane Darwaja.',
    18.2464700, 73.6826200, 1376, 'Gunjavane / Pali Base, Velhe Taluka, Pune District, Maharashtra 412213',
    'October to February (Winter months are best for overnight camping and high ridge trekking)',
    'Open 24 hours (Daylight trekking strongly recommended)',
    'Entry is Free. Nominal vehicle parking fee at Pali/Gunjavane base.',
    1,
    'From Pune via NH 48 to Nasrapur phata, then towards Velhe (approx 60 km). Two primary trekking routes: Pali gate (easier steps) and Gunjavane (steep adventure trail).',
    'Pune Junction Railway Station (65 km).',
    'Pune International Airport (72 km).',
    60, 210,
    'Basic village parking at base, camping space inside Padmavati Temple hall, local villagers provide warm Pithla Bhakri and trekking guides on weekends.',
    'Challenging climb requiring good physical stamina. Carry minimum 2-3 liters of water per person. Balekilla rope traverse requires careful footing.',
    'Challenging', 4.5, 1, 1,
    'https://images.unsplash.com/photo-1627894483216-2138af692e32?auto=format&fit=crop&w=1200&q=80'
),

-- 6. Torna Fort
(
    6, 1, 1, 'Torna Fort', 'किल्ले तोरणा (प्रचंडगड)', 'torna-fort',
    'Prachandagad - The massive fortress captured by Shivaji Maharaj in 1646, marking the dawn of Swarajya.',
    'स्वराज्याचे पहिले तोरण बांधणारा प्रचंडगड - शिवरायांचा पहिला ऐतिहासिक विजय.',
    'Torna Fort, historically named Prachandagad (The Colossal Fort), is the highest hill-fort in Pune district, soaring to 1,403 meters above sea level. In 1646, at the tender age of sixteen, Chhatrapati Shivaji Maharaj captured this colossal citadel from Adilshahi rulers, unfurling the golden saffron flag of Hindavi Swarajya.',
    'The conquest of Torna was the spark that ignited the Maratha war of independence. During excavations on the fort after its capture, large troves of gold treasure were discovered, which Shivaji Maharaj dedicated entirely to building and fortifying the adjacent capital of Rajgad.',
    '१६४६ मध्ये अवघ्या १६ वर्षांच्या वयात छत्रपती शिवाजी महाराजांनी तोरणा जिंकून स्वराज्याचे तोरण बांधले. या गडावर मिळालेल्या सोन्याच्या धनाचा उपयोग महाराजांनी शेजारील राजगड उभारणीसाठी केला.',
    'Torna is venerated as the birthplace of Swarajyas military sovereign assertion by Chhatrapati Shivaji Maharaj.',
    'स्वराज्याची पहिली मशाल पेटवणारा हा गड. महाराजांनी गडावरील मेंगाई देवी आणि तोरणजाई देवीचे मंदिर पुनरुज्जीवित केले.',
    'Features the iconic Zunjar Machi and Budhla Machi (resembling an inverted vessel), steep cliffs, and the fortified Bini Darwaja and Kothi Darwaja.',
    'Mengai Devi Temple, Zunjar Machi ridge, Budhla Machi, Kothi Darwaja, Bini Darwaja, scenic ridge view toward Rajgad.',
    18.2772000, 73.6234000, 1403, 'Velhe Village, Velhe Taluka, Pune District, Maharashtra 412213',
    'September to February (Magnificent wild flower blooms in September-October)',
    'Open 24 hours (Daylight trek only)',
    'Free Entry.',
    1,
    'Direct road from Pune via Nasrapur-Velhe (55 km). State transport buses run frequently from Swargate Pune to Velhe base.',
    'Pune Junction Railway Station (55 km).',
    'Pune International Airport (62 km).',
    55, 205,
    'Base village parking at Velhe, homestays and village dhabas, local trekking guide contacts.',
    'Steep trek with rock-cut patches and steel ladders on higher ridges. Avoid night trekking during heavy rains. Excellent boots essential.',
    'Challenging', 3.5, 1, 1,
    'https://images.unsplash.com/photo-1598894000396-bc4a04d509f6?auto=format&fit=crop&w=1200&q=80'
),

-- 7. Lohagad Fort
(
    7, 1, 1, 'Lohagad Fort', 'किल्ले लोहगड', 'lohagad-fort',
    'The Iron Fort near Lonavala, featuring the sensational Vinchu Kata fortified ridge.',
    'लोणावळ्याजवळील अभेद्य लोहगड - विंचू काटा माचीचे विहंगम दृश्य आणि मजबूत बुरुज.',
    'Lohagad (The Iron Fort) is one of the most accessible and dramatically scenic hill forts in the Western Ghats. Located close to Lonavala and Karla caves, Lohagad divides the Pavana and Indrayani river basins. It boasts a history spanning the Satavahanas, Rashtrakutas, Bahmanis, Nizams, and the Marathas.',
    'Shivaji Maharaj captured Lohagad in 1648 and utilized it as a strategic treasury for the loot and wealth obtained from Surat campaigns. It was surrendered temporarily under the Treaty of Purandar (1665) and recaptured by Maharaj in 1670. Later, it served as Nana Phadnavis administrative stronghold.',
    'महाराजांनी सुरतेहून आणलेला खजिना सुरक्षित ठेवण्यासाठी या अभेद्य लोहगडाचा उपयोग केला होता. १६७० मध्ये हा गड मराठ्यांनी मुघलांकडून पुन्हा जिंकून घेतला.',
    'Shivaji Maharaj recognized Lohagads supreme defensive position guarding the strategic Bhor Ghat trade route between the Arabian sea coast and the Deccan plateau.',
    'सुरतेच्या स्वारीचा अनमोल खजिना या गडावर सुरक्षित ठेवण्यात आला होता. हा गड स्वराज्याच्या पश्चिम सीमेचे रक्षण करणारा पोलादी पहारेकरी ठरला.',
    'Four sequential grand gates in pristine condition: Ganesh Darwaja, Narayan Darwaja, Hanuman Darwaja, and Maha Darwaja. The long, narrow spur named Vinchu Kata (Scorpion Sting) stretches 1.5 km out into the valley.',
    'Vinchu Kata fortified spur, Four massive entrance gates with intact battlements, Trimbak Talav (large water tank), Laxmi Kothi caves, Panoramic view of Pawna Dam reservoir.',
    18.7094000, 73.4797000, 1033, 'Lohagad Trek Path, Malavali, Lonavala, Pune District, Maharashtra 410405',
    'June to February (Monsoon waterfalls and misty green clouds make it a paradise)',
    '07:00 AM - 05:30 PM (ASI regulated timing)',
    'Indian Citizens: INR 25, Foreign Nationals: INR 300, Free for children under 15',
    1,
    'Via Mumbai-Pune Expressway to Malavali / Lonavala, then 8 km motorable ghat road to Lohagadwadi base village.',
    'Malavali Local Railway Station on Pune-Lonavala suburban line (6 km trek or auto).',
    'Pune International Airport (65 km) / Mumbai Airport (100 km).',
    60, 95,
    'Parking at Lohagadwadi village, local food shacks (corn cobs, onion pakodas, tea), stone stairways with railings.',
    'Very well-paved stone stairs; slippery during heavy monsoon showers. Keep food securely in backpacks away from local monkeys.',
    'Easy', 1.5, 1, 1,
    'https://images.unsplash.com/photo-1627894483216-2138af692e32?auto=format&fit=crop&w=1200&q=80'
),

-- 8. Purandar Fort
(
    8, 1, 1, 'Purandar Fort', 'किल्ले पुरंदर', 'purandar-fort',
    'The birthplace of Chhatrapati Sambhaji Maharaj and site of Murarbaji Deshpandes valiant defense.',
    'छत्रपती संभाजी महाराजांचे जन्मस्थान आणि मुरारबाजींच्या अद्वितीय पराक्रमाचा साक्षीदार.',
    'Purandar Fort, twin to Vajragad, rises 1,387 meters in the Sahyadri mountains near Saswad. It is celebrated as the birthplace of Chhatrapati Sambhaji Maharaj (born May 14, 1657) and for the heroic battle of 1665 where Murarbaji Deshpande defended the fort against the massive Mughal army led by Jai Singh I and Diler Khan.',
    'The prolonged siege of Purandar in 1665 led to the famous historic "Treaty of Purandar", under which Shivaji Maharaj strategically agreed to cede 23 forts to Aurangzeb while retaining 12 forts, preserving his core army and planning his future resurgence.',
    'या किल्ल्यावर १६५७ मध्ये धर्मवीर छत्रपती संभाजी महाराजांचा जन्म झाला. १६६५ मध्ये पुरंदर वेढ्यात मुरारबाजी देशपांडे यांनी दाखवलेला पराक्रम इतिहासात सुवर्णाक्षरांनी कोरला गेला आहे.',
    'Chhatrapati Shivaji Maharaj frequently visited Purandar, reinforced its defenses, and named his eldest son Sambhaji Raje after Lord Shiva on this fort.',
    'महाराजांनी या गडाचा उपयोग मुख्य लष्करी तळ म्हणून केला. १६७० मध्ये शिवरायांनी हा गड मुघलांकडून पुन्हा जिंकून घेतला.',
    'Two-tier structure: Machi (lower level with church, military cantonment structures, and Kedareshwar temple) and Balekilla (citadel). Features Delhi Darwaja and Bini Darwaja.',
    'Chhatrapati Sambhaji Maharaj birthplace memorial, Murarbaji Deshpande Bronze Statue, Kedareshwar Temple at topmost peak, Purandeshwar Temple, Vajragad twin fort.',
    18.2778000, 73.9786000, 1387, 'Purandar Fort Rd, Gherapurandhar, Saswad, Pune District, Maharashtra 412205',
    'August to February (Clean blue skies and scenic vistas of Saswad valley)',
    '08:30 AM - 05:00 PM (Identity card required at Military Police post)',
    'Free entry. Valid Government ID proof mandatory (Aadhaar / Voter ID / Passport).',
    1,
    'From Pune via Hadapsar - Dive Ghat - Saswad road (approx 45 km) to the base, then paved military access road to parking.',
    'Pune Junction Railway Station (45 km).',
    'Pune International Airport (50 km).',
    45, 195,
    'Military cantonment checkpoint, parking area, souvenir and refreshment stalls, clean toilets near cantonment.',
    'Foreign nationals may require prior defense clearance due to Indian Air Force academy nearby. Valid photo ID is strictly verified at gate.',
    'Moderate', 2.0, 0, 1,
    'https://images.unsplash.com/photo-1598894000396-bc4a04d509f6?auto=format&fit=crop&w=1200&q=80'
),

-- 9. Panhala Fort
(
    9, 1, 4, 'Panhala Fort', 'किल्ले पन्हाळा', 'panhala-fort',
    'The grand hill fortress of Kolhapur, famous for the siege of Siddi Jauhar and the heroic sacrifice of Shiva Kashid & Baji Prabhu.',
    'कोल्हापुरातील भव्य गिरिदुर्ग - सिद्दी जौहरचा वेढा, शिवा काशिद आणि बाजीप्रभू देशपांडेंचे अद्वितीय बलिदान.',
    'Panhala Fort is located 20 km northwest of Kolhapur. It is one of the largest forts in the Deccan, boasting 14 kilometers of perimeter ramparts. It played an epic role in Maratha history when Chhatrapati Shivaji Maharaj was besieged here for nearly five months in 1660 by Siddi Jauhars massive army.',
    'On a dark, stormy night in July 1660, Shivaji Maharaj executed an audacious breakout towards Vishalgad. His trusted body-double Shiva Kashid sacrificed his life posing as the King, while Baji Prabhu Deshpande and 300 Mavlas made their immortal last stand at Ghodkhind (later consecrated as Pavankhind), holding off thousands until Maharaj fired the victory cannon from Vishalgad.',
    '१६६० मधील सिद्दी जौहरच्या वेढ्यातून शिवरायांनी केलेली चित्तथरारक सुटका याच गडावरून झाली. शिवा काशिद आणि बाजीप्रभू देशपांडे यांचे पावनखिंडीतील बलिदान या इतिहासाशी जोडलेले आहे.',
    'Shivaji Maharaj spent substantial time in Panhala, which served as his strategic base in the southern Deccan. The Sajja Kothi on the fort was where Sambhaji Maharaj was later stationed.',
    'महाराजांनी या किल्ल्याचा उपयोग दक्षिण महाराष्ट्रावरील पकड मजबूत ठेवण्यासाठी केला. येथे सज्जा कोठी आणि तीन दरवाजा आजही शिवकालीन आठवणी जागवतात.',
    'Bijapuri and Maratha military architecture featuring Teen Darwaja (triple gate system with intricate Persian motifs), Ambarkhana (huge granaries that could hold 25,000 khandis of grain), and Kalavantinicha Mahal.',
    'Teen Darwaja, Sajja Kothi (viewpoint palace), Ambarkhana, Andhar Bav (hidden circular well with secret chambers), Shiva Kashid Statue, Tabak Udyan.',
    16.8122000, 74.1070000, 845, 'Panhala Fort Town, Kolhapur District, Maharashtra 416201',
    'All year round; exceptionally lush and foggy during Monsoon (June-September)',
    'Open 24 hours (Monuments: 08:00 AM - 06:00 PM)',
    'Free entry to fort township; small parking fee at municipal gate.',
    1,
    '20 km from Kolhapur city via NH 166. State transport buses and private cabs run every 15 minutes between Kolhapur and Panhala.',
    'Chhatrapati Shahu Maharaj Terminus, Kolhapur (22 km).',
    'Kolhapur Airport (Ujalaiwadi) - 30 km.',
    235, 380,
    'Hotels, MTDC resorts, ATMs, authentic Kolhapuri cuisine restaurants (Tambada-Pandhara Rassa), paved vehicle roads throughout the plateau.',
    'Fully motorable fort city with gentle walking trails. Excellent for elders and families.',
    'Easy', 1.0, 0, 1,
    'https://images.unsplash.com/photo-1589308078059-be1415eab4c3?auto=format&fit=crop&w=1200&q=80'
),

-- 10. Sindhudurg Fort
(
    10, 1, 7, 'Sindhudurg Fort', 'किल्ले सिंधुदुर्ग', 'sindhudurg-fort',
    'Chhatrapati Shivaji Maharajs premier island naval fortress in the Arabian Sea at Malvan.',
    'अरबी समुद्रातील अजिंक्य जलदुर्ग - शिवरायांच्या दूरदर्शी आरमार धोरणाचे सर्वोच्च शिखर.',
    'Sindhudurg (Fort of the Ocean) is a magnificent naval fortress constructed on an island off the coast of Malvan in southern Konkan. Built by Chhatrapati Shivaji Maharaj between 1664 and 1667, its foundation was laid using over 70,000 kg of molten lead to withstand the ferocious Arabian Sea waves, demonstrating the vision of the Father of the Indian Navy.',
    'Realizing that colonial powers like the Portuguese, British, and Siddis of Janjira controlled maritime trade, Shivaji Maharaj established a formidable naval base here, housing shipyards, artillery bastions, and a self-sufficient township with sweet water wells surrounded by saltwater.',
    'छत्रपती शिवरायांनी १६६४ मध्ये या जलदुर्गाची पायाभरणी केली. समुद्रात पाया भरताना शिसे वितळवून ओतण्यात आले होते. महाराजांच्या हाताचे आणि पायाचे ठसे आजही या किल्ल्यावर संरक्षित आहेत.',
    'Constructed under Shivaji Maharajs direct command. The only temple in the world where Chhatrapati Shivaji Maharaj is worshipped as a deity (Shri Shivrajeshwar Temple) was built inside this fort by his son Chhatrapati Rajaram.',
    'शिवरायांचे एकमेव मंदिर (श्री शिवराजेश्वर मंदिर) या किल्ल्यात आहे. महाराजांच्या हाताचे आणि पायाचे मूळ ठसे येथे पाहायला मिळतात.',
    'Spans 48 acres with a 3 km long defensive rampart fortified with 42 bastions. The main sea gateway is cleverly concealed within the curving bastions to prevent enemy sighting from open water.',
    'Shivrajeshwar Temple (idol of Shivaji Maharaj), Hand and Foot impressions of Shivaji Maharaj cast in lime, Three sweet water wells (Sakhar Bav, Doodh Bav, Dahibav), Hidden entrance gate.',
    16.0592000, 73.4608000, 15, 'Kurte Island, Malvan Coast, Sindhudurg District, Maharashtra 416606',
    'October to May (Ferry service operates; closed in monsoon due to rough sea waves)',
    '08:00 AM - 05:30 PM (Ferry operates between these hours)',
    'Ferry Boat Round Trip: Approx INR 100-150 per person; Entry: Free.',
    1,
    'Reach Malvan beach via NH 66 from Mumbai or Goa. Local government-operated passenger ferry boats take 15 minutes to reach the fort island.',
    'Kudal Railway Station (30 km) or Kankavli (48 km) on Konkan Railway.',
    'Sindhudurg Chipi Airport (22 km) or Goa Mopa Airport (75 km).',
    380, 500,
    'Ferry ticket counter, life jackets provided on boats, Konkani seafood shacks at Malvan jetty, scuba diving and water sports centers.',
    'Ferry services do not operate during monsoon (June to September). Wear sunscreen and footwear suitable for walking on sandy and stone paths.',
    'Easy', 2.0, 1, 1,
    'https://images.unsplash.com/photo-1598894000396-bc4a04d509f6?auto=format&fit=crop&w=1200&q=80'
),

-- 11. Bhimashankar Temple
(
    11, 5, 1, 'Bhimashankar Jyotirlinga', 'भीमाशंकर ज्योतिर्लिंग', 'bhimashankar-temple',
    'One of the twelve sacred Jyotirlinga shrines of Lord Shiva, nestled in the misty Sahyadri Wildlife Sanctuary.',
    'द्वादश ज्योतिर्लिंगांपैकी एक पवित्र तीर्थक्षेत्र आणि सह्याद्रीतील समृद्ध वन्यजीव अभयारण्य.',
    'Bhimashankar is situated in the Sahyadri ranges, 110 km from Pune. It is home to one of the 12 revered Jyotirlinga shrines of Lord Shiva and is the source of the sacred Bhima River. Surrounded by dense rainforest, it is also a designated wildlife sanctuary known for the Indian Giant Squirrel (Shekru - the state animal of Maharashtra).',
    'Mentioned in ancient Puranas, the temple displays exemplary Nagara and Hemadpanthi stone masonry. Nana Phadnavis constructed the grand spire in the 18th century, and the large Portuguese brass bell installed in the complex was captured by Chimaji Appa during the conquest of Vasai in 1739.',
    'हेमाडपंती शिल्पकलेचे अप्रतिम उदाहरण. नाना फडणवीसांनी या मंदिराचे शिखर बांधले आणि चिमाजी आप्पांनी वसई मोहिमेत जिंकलेली भव्य पोर्तुगीज घंटा येथे अर्पण केली.',
    'Chhatrapati Shivaji Maharaj was a devotee of Lord Shiva and provided grants and royal patronage to the priests of Bhimashankar during his reign in Swarajya.',
    'छत्रपती शिवरायांनी या पवित्र तीर्थक्षेत्राच्या पूजा-अर्चनेसाठी इनामे आणि देणग्या दिल्या होत्या.',
    'Intricate stone carvings on pillars representing scenes from the Ramayana and Mahabharata. Built using local black basalt stone in the traditional Hemadpanthi style.',
    'Jyotirlinga Garbhagriha, Chimaji Appa Portuguese Bell, Gupt Bhimashankar (origin of Bhima river), Nagphani viewpoint, Shekru (Giant Squirrel) safari trail.',
    19.0722000, 73.5358000, 1050, 'Bhimashankar, Khed Taluka, Pune District, Maharashtra 410509',
    'July to March (Misty green landscapes during monsoon, religious peak during Mahashivratri)',
    'Temple Darshan: 04:30 AM - 09:30 PM (Aarti timings: 05:00 AM, 03:00 PM, 07:30 PM)',
    'Free General Darshan; VIP Darshan / Abhishek passes available at shrine board counter.',
    1,
    'From Pune via Manchar - Ghodegaon - Bhimashankar (approx 110 km). Direct MSRTC Shivshahi and ordinary buses run from Pune and Kalyan.',
    'Pune Junction (115 km).',
    'Pune International Airport (105 km).',
    110, 220,
    'Temple Trust Bhaktaniwas, parking area, VIP darshan counter, prasad stalls, cloakrooms for footwear.',
    'Expect long queues during Shravan month and Mahashivratri. The dense forest area is prone to heavy fog; drive carefully.',
    'Easy', 1.0, 0, 0,
    'https://images.unsplash.com/photo-1609137144820-7f24f5d38c64?auto=format&fit=crop&w=1200&q=80'
),

-- 12. Thoseghar Waterfalls
(
    12, 4, 3, 'Thoseghar Waterfalls', 'ठोसेघर धबधबा', 'thoseghar-waterfalls',
    'A breathtaking series of roaring waterfalls plunging 500 meters into a lush green gorge near Satara.',
    'सातार्‍याजवळील निसर्गरम्य दरीत कोसळणारा ५०० मीटर उंचीचा विहंगम धबधबा.',
    'Thoseghar Waterfalls is a scenic cascading waterfall series located in the Western Ghats, 20 km from Satara city. The highest drop is approximately 200 meters (total cascade ~500 meters), set against dense virgin forests and misty monsoonal clouds.',
    'The surrounding plateau forms part of the UNESCO World Heritage Western Ghats cluster. Well-maintained forest department viewing platforms allow tourists to enjoy the raw power of the waterfall safely.',
    'सह्याद्रीच्या पश्चिमेकडील पठारांवरून कोसळणाऱ्या या जलप्रपाताला पाहण्यासाठी दरवर्षी हजारो निसर्गप्रेमी भेट देतात.',
    'Located in the historic Satara region, which became the Maratha Empire capital under Chhatrapati Shahu Maharaj in the 18th century.',
    'छत्रपती शाहू महाराजांच्या राजधानीच्या परिसरातील हे एक निसर्गरम्य आकर्षण आहे.',
    'Forest eco-path with paved stone pathways, metal safety railings, and dedicated viewing galleries looking across the gorge.',
    'Main Waterfall Viewpoint, Chota Waterfall pool, Chalkewadi Windmill farm (nearby), Sajjangad Fort (nearby).',
    17.5986000, 73.8542000, 980, 'Thoseghar Village, Satara Taluka, Satara District, Maharashtra 415013',
    'July to October (Peak monsoon to early post-monsoon)',
    '08:00 AM - 05:30 PM (Forest gate entry strictly enforced)',
    'INR 20 per adult (Forest Conservation Fee). Parking: INR 50.',
    1,
    'Well-tarred road from Satara city via Sajjangad phata (20 km). State transport buses and auto-rickshaws available from Satara.',
    'Satara Railway Station (25 km).',
    'Pune International Airport (135 km).',
    130, 260,
    'Vehicle parking, Forest Department cafeteria, clean public washrooms, protected viewing gallery with security guards.',
    'Do not attempt to step off marked trails or enter the roaring gorge water. Strict fines are imposed for littering plastic.',
    'Easy', 1.0, 1, 0,
    'https://images.unsplash.com/photo-1546587348-d12660c30c50?auto=format&fit=crop&w=1200&q=80'
),

-- 13. Mahabaleshwar
(
    13, 3, 3, 'Mahabaleshwar', 'महाबळेश्वर', 'mahabaleshwar',
    'The Queen of Hill Stations in the Western Ghats, blessed with strawberry orchards and dramatic valley viewpoints.',
    'सह्याद्रीची राणी - आल्हाददायक हवामान, स्ट्रॉबेरीच्या बागा आणि विहंगम दऱ्या.',
    'Mahabaleshwar is a premier hill station nestled in the Sahyadri hills of Satara district at 1,353 meters altitude. Famous for producing over 85% of Indias strawberries, it was the summer capital of the Bombay Presidency during the British era and features stunning scenic points overlooking the Krishna river basin.',
    'Historically sacred as the source of five holy rivers: Krishna, Koyna, Venna, Savitri, and Gayatri at Old Mahabaleshwar Panchganga temple.',
    'प्राचीन काळापासून पाच नद्यांचे उगमस्थान म्हणून ओळखले जाणारे महाबळेश्वर हे निसर्गसौंदर्य आणि आल्हाददायक वातावरणासाठी प्रसिद्ध आहे.',
    'Adjacent to Pratapgad Fort, where Shivaji Maharaj routed Afzal Khan in 1659. The entire territory was brought under Swarajya through the conquest of Javali.',
    'जावळीच्या खोऱ्यातील या भागाचे संरक्षण करण्यासाठी महाराजांनी जवळच प्रतापगड उभारला.',
    'Colonial British bungalows alongside ancient stone temples with intricate rock gaumukhs (cow-mouth stone spouts).',
    'Arthur Seat Point, Elephant Head Point, Venna Lake (boating), Old Mahabaleshwar Panchganga Temple, Mapro Garden, Wilson Point (Sunrise).',
    17.9237000, 73.6586000, 1353, 'Mahabaleshwar Hill Station, Satara District, Maharashtra 412806',
    'October to June (Pleasant weather; strawberry harvest from November to March)',
    'Viewpoints open sunrise to sunset; Boating at Venna Lake: 09:00 AM - 08:00 PM',
    'Pollution tax / Municipal toll: Approx INR 30 per vehicle. Point entry: Free.',
    1,
    'Direct high-speed highway NH 48 from Pune to Shirwal/Surur, then via Wai and Panchgani ghat road.',
    'Satara Railway Station (60 km) or Pune Junction (120 km).',
    'Pune International Airport (125 km).',
    120, 240,
    'Full-service luxury resorts, MTDC resorts, ATMs, strawberry farms, boat rentals, horse riding, wide shopping markets.',
    'Drive with caution during dense winter evening fog and monsoon downpours.',
    'Easy', 1.0, 1, 0,
    'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=1200&q=80'
),

-- 14. Ajanta Caves
(
    14, 2, 9, 'Ajanta Caves', 'अजिंठा लेणी', 'ajanta-caves',
    'UNESCO World Heritage rock-cut Buddhist cave monuments featuring masterly ancient frescoes and sculptures.',
    'युनेस्को जागतिक वारसा स्थळ - प्राचीन बौद्ध कला, भित्तिचित्रे आणि शिल्पकलेचा अद्वितीय आविष्कार.',
    'The Ajanta Caves are approximately 30 rock-cut Buddhist cave monuments dating from the 2nd century BCE to about 480 CE. Carved into a dramatic crescent-shaped gorge overlooking the Waghora River, the masterpieces of Buddhist religious art and fresco murals preserved here are regarded as among the finest surviving examples of ancient Indian art.',
    'Excavated in two phases: the early Satavahana period and the later Vakataka dynasty period under King Harishena. Rediscovered accidentally by British officer John Smith during a tiger hunt in 1819.',
    'इ.स. पूर्व दुसऱ्या शतकापासून इ.स. पाचव्या शतकापर्यंत कोरलेली ही ३० बौद्ध लेणी जगभरातील पर्यटकांना आणि कलाकारांना आकर्षित करतात.',
    'Located in the historic heartland of Maharashtra, showcasing the peaceful cultural depth that Chhatrapati Shivaji Maharaj and subsequent rulers fought to preserve.',
    'महाराष्ट्राच्या प्राचीन वैभवशाली संस्कृतीची आणि कलापरंपरेची ही आंतरराष्ट्रीय ओळख आहे.',
    'Monolithic basalt rock architecture featuring chaitya halls with stupas, viharas (monasteries), and tempera murals using mineral and vegetable pigments.',
    'Cave 1 (Padmapani and Vajrapani Bodhisattva murals), Cave 26 (Colossal Reclining Buddha mahaparinirvana sculpture), Panoramic viewpoint overlooking the gorge.',
    20.5519000, 75.7033000, 480, 'Ajanta Caves Road, Chhatrapati Sambhajinagar District, Maharashtra 431117',
    'October to March (Pleasant weather for cave exploration; avoid scorching May heat)',
    '09:00 AM - 05:00 PM (CLOSED ON MONDAYS)',
    'Indian Citizens: INR 40, Foreign Nationals: INR 600, Children under 15: Free',
    1,
    '100 km north of Chhatrapati Sambhajinagar city. Direct state luxury buses and taxis run regularly from the central bus station.',
    'Chhatrapati Sambhajinagar Railway Station (100 km) or Jalgaon Railway Station (60 km).',
    'Chhatrapati Sambhajinagar Airport (IXU) - 100 km.',
    340, 430,
    'Visitor Interpretation Centre with replica caves, AC electric shuttle buses from parking to caves, ASI bookstore, MTDC restaurant.',
    'Flash photography and tripods inside caves are strictly prohibited to protect the delicate ancient fresco colors. Cave complex is closed every Monday.',
    'Easy', 3.0, 0, 0,
    'https://images.unsplash.com/photo-1609137144820-7f24f5d38c64?auto=format&fit=crop&w=1200&q=80'
),

-- 15. Kaas Plateau
(
    15, 6, 3, 'Kaas Plateau (Valley of Flowers)', 'कास पठार (फुलांची दरी)', 'kaas-plateau',
    'UNESCO World Heritage volcanic laterite plateau that transforms into a magical carpet of wild seasonal flowers.',
    'युनेस्को जागतिक वारसा स्थळ - मान्सूनमध्ये लाखो रानफुलांनी बहरणारे महाराष्ट्राचे नंदनवन.',
    'Kaas Pathar is a high-altitude laterite plateau situated 25 km west of Satara. Declared a UNESCO World Natural Heritage site in 2012, this unique ecosystem blossoms with over 850 species of rare wildflowers, orchids, and insectivorous plants such as Drosera (Sundew) following the monsoon rains in August and September.',
    'Formed from volcanic basalt rocks weathered over millions of years, the thin layer of soil supports endemic flora that cannot be found anywhere else on planet Earth.',
    'जगातील अत्यंत दुर्मिळ ८५० हून अधिक प्रजातींची रानफुले या पठारावर दरवर्षी ऑगस्ट-सप्टेंबरमध्ये उमलतात.',
    'Part of the Sahyadri range that sheltered the guerilla warriors of Shivaji Maharaj (the Mavlas) and protected the Swarajya kingdom.',
    'सह्याद्रीच्या कुशीतील हा प्रदेश छत्रपतींच्या काळातील मावळ्यांचे कार्यक्षेत्र होता.',
    'Natural lateritic tableland landscape dotted with natural ponds and scenic rim vistas overlooking Kaas Lake.',
    'Wild orchid and floral trails, Kaas Lake, Kumudini Lake (water lilies), Vajrai Waterfall (nearby 10 km).',
    17.7214000, 73.8183000, 1200, 'Kaas Plateau Road, Satara District, Maharashtra 415013',
    'August to October (Flowering season strictly depends on monsoon rainfall cycle)',
    '07:00 AM - 06:00 PM (Entry permitted in booked time slots during flowering season)',
    'INR 100 per person on weekdays; INR 150 on weekends (Official forest registration).',
    1,
    '25 km from Satara city on a scenic hill road passing through forests and villages.',
    'Satara Railway Station (30 km).',
    'Pune International Airport (140 km).',
    135, 275,
    'Forest eco-center, guided nature walks, eco-friendly battery transport, designated walking corridors.',
    'Do not pluck flowers or step outside the wooden fenced walk paths. Daily visitor numbers are strictly capped by Forest Dept; book slots online in advance during September.',
    'Easy', 2.0, 0, 0,
    'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=1200&q=80'
);

-- -------------------------------------------------------------
-- Destination Gallery Images
-- -------------------------------------------------------------
INSERT INTO destination_images (destination_id, image_url, caption, attribution, is_primary, display_order) VALUES
(1, 'https://images.unsplash.com/photo-1598894000396-bc4a04d509f6?auto=format&fit=crop&w=1200&q=80', 'The majestic ramparts and clouds at Raigad Fort', 'Unsplash Heritage Library', 1, 1),
(1, 'https://images.unsplash.com/photo-1627894483216-2138af692e32?auto=format&fit=crop&w=1200&q=80', 'Maha Darwaja of Raigad', 'Sahyadri Archives', 0, 2),
(1, 'https://images.unsplash.com/photo-1589308078059-be1415eab4c3?auto=format&fit=crop&w=1200&q=80', 'Samadhi of Chhatrapati Shivaji Maharaj', 'ASI Public Domain', 0, 3),

(2, 'https://images.unsplash.com/photo-1627894483216-2138af692e32?auto=format&fit=crop&w=1200&q=80', 'The iconic gateway arches of Shivneri Fort', 'Unsplash Forts', 1, 1),
(2, 'https://images.unsplash.com/photo-1598894000396-bc4a04d509f6?auto=format&fit=crop&w=1200&q=80', 'Janmasthan at Shivkunj', 'Heritage Maharashtra', 0, 2),

(3, 'https://images.unsplash.com/photo-1589308078059-be1415eab4c3?auto=format&fit=crop&w=1200&q=80', 'The mist-shrouded bastions of Sinhagad Fort', 'Unsplash Heritage', 1, 1),
(3, 'https://images.unsplash.com/photo-1609137144820-7f24f5d38c64?auto=format&fit=crop&w=1200&q=80', 'Kalyan Darwaja overlooking Konkan Valley', 'Sahyadri Explorers', 0, 2),

(4, 'https://images.unsplash.com/photo-1609137144820-7f24f5d38c64?auto=format&fit=crop&w=1200&q=80', 'Pratapgad Fort surrounded by the emerald Mahabaleshwar valley', 'Unsplash Western Ghats', 1, 1),

(5, 'https://images.unsplash.com/photo-1627894483216-2138af692e32?auto=format&fit=crop&w=1200&q=80', 'The high citadel (Balekilla) of Rajgad Fort', 'Unsplash Forts', 1, 1),

(7, 'https://images.unsplash.com/photo-1627894483216-2138af692e32?auto=format&fit=crop&w=1200&q=80', 'Vinchu Kata scorpion-sting ridge of Lohagad', 'Unsplash Trekking', 1, 1),

(10, 'https://images.unsplash.com/photo-1598894000396-bc4a04d509f6?auto=format&fit=crop&w=1200&q=80', 'Sindhudurg Island Fort battlements in the Arabian Sea', 'Maharashtra Tourism', 1, 1),

(12, 'https://images.unsplash.com/photo-1546587348-d12660c30c50?auto=format&fit=crop&w=1200&q=80', 'Cascades of Thoseghar Waterfalls', 'Western Ghats Bio Reserve', 1, 1);

-- -------------------------------------------------------------
-- Historical Events (Chronological Milestones of Chhatrapati Shivaji Maharaj)
-- -------------------------------------------------------------
INSERT INTO historical_events (
    event_year, date_formatted, title_en, title_mr,
    description_en, description_mr,
    related_destination_id, icon_name, importance_level
) VALUES
(
    1630, 'February 19, 1630',
    'Birth of Chhatrapati Shivaji Maharaj at Shivneri',
    'शिवनेरीवर छत्रपती शिवरायांचा जन्म',
    'Chhatrapati Shivaji Maharaj was born to Rajmata Jijabai and Shahaji Raje Bhosle at the hill fortress of Shivneri in Junnar. Blessed by Goddess Shivai, his mother named him Shivaji.',
    'पुणे जिल्ह्यातील जुन्नर येथील शिवनेरी गडावर राजमाता जिजाऊंच्या पोटी छत्रपती शिवरायांचा जन्म झाला. शिवाई देवीच्या आशीर्वादाने बाळाचे नाव ''शिवाजी'' ठेवण्यात आले.',
    2, 'fa-baby', 'Milestone'
),
(
    1646, '1646',
    'Capture of Torna Fort - The Dawn of Swarajya',
    'तोरणा जिंकून स्वराज्याचे तोरण बांधले',
    'At the age of sixteen, Shivaji Maharaj captured Torna Fort (Prachandagad) from the Adilshahi sultanate without bloodshed, hoisting the saffron flag of Swarajya.',
    'अवघ्या १६ वर्षांच्या वयात छत्रपती शिवाजी महाराजांनी तोरणा किल्ला जिंकून हिंदवी स्वराज्याची अधिकृत मुहूर्तमेढ रोवली.',
    6, 'fa-flag', 'Milestone'
),
(
    1647, '1647',
    'Consolidation of Kondhana (Sinhagad) & Purandar',
    'कोंढाणा आणि पुरंदरचा ताबा',
    'Strategic hill bastions surrounding Pune including Kondhana (later Sinhagad) and Purandar were secured to fortify the nascent Maratha kingdom.',
    'पुणे परिसराचे रक्षण करण्यासाठी कोंढाणा (सिंहगड) आणि पुरंदर हे मोक्याचे किल्ले स्वराज्यात सामील करून घेण्यात आले.',
    3, 'fa-shield-halved', 'Strategic'
),
(
    1648, '1648',
    'Fortification of Rajgad as First Capital',
    'राजगडाची उभारणी - स्वराज्याची पहिली राजधानी',
    'Shivaji Maharaj commenced the grand fortification of Rajgad on Murumbdev hill. It served as the active capital of the Maratha Empire for over 26 years.',
    'मुरुंबदेवाच्या डोंगरावर शिवरायांनी राजगड या भव्य किल्ल्याची उभारणी केली, ज्याने सलग २६ वर्षे स्वराज्याची पहिली राजधानी म्हणून सेवा दिली.',
    5, 'fa-crown', 'Milestone'
),
(
    1656, '1656',
    'Conquest of Javali & Capture of Raigad (Rairi)',
    'जावळी विजय आणि रायगड प्राप्ती',
    'Shivaji Maharaj brought the treacherous valleys of Javali under control and captured the natural rock fortress of Rairi, which he envisioned as his future royal capital, Raigad.',
    'जावळीच्या मोऱ्यांचा पराभव करून महाराजांनी रायरी किल्ला जिंकला आणि पुढे त्याचे नामकरण ''रायगड'' करून राजधानीसाठी निवड केली.',
    1, 'fa-mountain', 'Strategic'
),
(
    1659, 'November 10, 1659',
    'Historic Victory at Pratapgad - Defeat of Afzal Khan',
    'प्रतापगडाचे युद्ध - अफझलखानाचा वध',
    'The formidable general of Bijapur, Afzal Khan, attempted to assassinate Shivaji Maharaj in a sham truce. Maharaj foresaw the trap, eliminated Khan with his tiger claws (wagh-nakha), and routed the Adilshahi army.',
    '१० नोव्हेंबर १६५९ रोजी प्रतापगडाच्या पायथ्याशी कपटी अफझलखानाचा कोथळा काढून शिवरायांनी विजापूरच्या बलाढ्य सैन्याचा धुव्वा उडवला.',
    4, 'fa-bolt', 'Battle'
),
(
    1660, 'July 13, 1660',
    'Escape from Panhala Siege & Stand at Pavankhind',
    'पन्हाळा वेढ्यातून सुटका आणि पावनखिंडीचा अमर लढा',
    'Shivaji Maharaj broke through the five-month siege of Siddi Jauhar at Panhala. Shiva Kashid laid down his life as a decoy, while Baji Prabhu Deshpande held the narrow pass until the King reached Vishalgad safely.',
    'सिद्दी जौहरच्या वेढ्यातून सुटताना वीर शिवा काशिद यांनी हौतात्म्य पत्करले, तर बाजीप्रभू देशपांड्यांनी प्राणपणाने खिंड लढवून पावन केली.',
    9, 'fa-person-hiking', 'Battle'
),
(
    1664, '1664',
    'Foundation of Sindhudurg Fort & Naval Fleet',
    'सिंधुदुर्ग जलदुर्गाची निर्मिती व आरमार स्थापना',
    'Recognizing naval power as paramount to national sovereignty, Shivaji Maharaj founded the island fort of Sindhudurg at Malvan, establishing the Maratha Navy.',
    'सागरी सीमांच्या संरक्षणासाठी महाराजांनी मालवणच्या कुरटे बेटावर अभेद्य सिंधुदुर्ग जलदुर्ग उभारून मराठा आरमाराचा पाया घातला.',
    10, 'fa-ship', 'Strategic'
),
(
    1665, 'June 11, 1665',
    'Siege of Purandar and Treaty of Purandar',
    'पुरंदरचा वेढा आणि ऐतिहासिक तह',
    'Murarbaji Deshpande showed peerless bravery before falling in battle. Shivaji Maharaj negotiated the pragmatic Treaty of Purandar with Jai Singh to preserve his core forces.',
    'मुरारबाजी देशपांडे यांनी पराक्रमाची शर्थ केली. महाराजांनी रणनीतिक मुत्सद्देगिरी दाखवत पुरंदरचा तह केला आणि २३ किल्ले तात्पुरते मुघलांना दिले.',
    8, 'fa-scroll', 'Treaty'
),
(
    1666, 'August 17, 1666',
    'Daring Strategic Escape from Agra',
    'आग्र्याहून ऐतिहासिक व चित्तथरारक सुटका',
    'Placed under house arrest by Emperor Aurangzeb, Shivaji Maharaj and Prince Sambhaji executed a legendary escape hidden inside fruit baskets, safely returning to Rajgad.',
    'औरंगजेबाच्या नजरकैदेतून मिठाईच्या पेट्यांतून महाराज आणि बाल संभाजीराजे यांनी केलेली ऐतिहासिक सुटका मुघल सत्तेला मोठा धक्का देणारी ठरली.',
    5, 'fa-mask', 'Milestone'
),
(
    1670, 'February 4, 1670',
    'Recapture of Sinhagad by Tanaji Malusare',
    'तानाजी मालुसरेंचा पराक्रम - सिंहगड विजय',
    'Subedar Tanaji Malusare scaled the sheer cliff of Kondhana at dead of night and recaptured the fort for Swarajya, sacrificing his life in the fierce duel.',
    'सुभेदार तानाजी मालुसरे यांनी अवघड कडा चढून सिंहगड जिंकला. ''गड आला पण सिंह गेला'' हे महाराजांचे शब्द अजरामर झाले.',
    3, 'fa-mountain', 'Battle'
),
(
    1674, 'June 6, 1674',
    'Grand Coronation of Chhatrapati Shivaji Maharaj at Raigad',
    'किल्ले रायगडावर शिवराज्याभिषेक सोहळा',
    'Pandit Gaga Bhatt officiated the sacred coronation, formally establishing the independent, sovereign Maratha Empire. Shivaji Maharaj was proclaimed "Kshatriya Kulavantas Chhatrapati".',
    'ज्येष्ठ शुद्ध त्रयोदशीला पंडित गागाभट्टांच्या उपस्थितीत रायगडावर महाराजांचा सुवर्ण राज्याभिषेक संपन्न झाला आणि सार्वभौम हिंदवी स्वराज्य उदयास आले.',
    1, 'fa-crown', 'Coronation'
),
(
    1680, 'April 3, 1680',
    'Mahaparinirvana of Chhatrapati Shivaji Maharaj at Raigad',
    'छत्रपती शिवाजी महाराजांचे रायगडावर महानिर्वाण',
    'The great founder of the Maratha Empire, visionary protector of the people, and father of Indian naval strategy passed away at Raigad Fort, leaving behind an immortal legacy.',
    'अन्यायाविरुद्ध लढणारा, रयतेचा राजा आणि स्वराज्याचा निर्माता युगपुरुष छत्रपती शिवाजी महाराजांचे रायगडावर महानिर्वाण झाले.',
    1, 'fa-monument', 'Milestone'
);

-- -------------------------------------------------------------
-- Sample Reviews
-- -------------------------------------------------------------
INSERT INTO reviews (destination_id, user_id, rating, review_title, review_text, visit_season, status) VALUES
(1, 2, 5, 'Unmatched feeling of reverence at the capital of Swarajya', 'Visiting Raigad Fort was a deeply moving experience. Walking through the Maha Darwaja and standing in front of Maharajs Samadhi sends chills down your spine. The acoustic engineering in the Nagarkhana is mind-blowing. Ropeway was smooth and convenient for our family.', 'Winter', 'approved'),
(3, 2, 5, 'Thrilling morning trek with legendary Pithla Bhakri', 'Started the trek from Donje at 6 AM. The misty breeze and cloudy atmosphere were mesmerizing. Saluted Tanaji Malusares Samadhi and relished the authentic steaming hot Pithla Bhakri with thecha at the top. A must-visit weekend trek for every Punekar!', 'Monsoon', 'approved'),
(4, 3, 5, 'Breathtaking vantage points and rich Maratha history', 'Pratapgad is extraordinarily maintained. The Bhavani Mata temple on the upper fort is so serene, and the panoramic views of the dense Mahabaleshwar valleys are unmatched. Highly recommend taking an authorized local guide to understand the brilliant battle maneuvers.', 'Winter', 'approved'),
(2, 3, 5, 'Sacred ground of Shivneri Fort', 'The seven defensive gates are an architectural wonder. The birth chamber at Shivkunj has been preserved with utmost dignity. Very neat stone stairs and family friendly.', 'Winter', 'approved');

-- -------------------------------------------------------------
-- Sample Wishlists & Visited Places
-- -------------------------------------------------------------
INSERT INTO wishlists (user_id, destination_id, notes) VALUES
(2, 5, 'Plan overnight trek to Rajgad to see the sunrise from Nedhe on Suvela Machi.'),
(2, 10, 'Visit Sindhudurg island fort and scuba dive in Malvan waters.'),
(3, 1, 'Planning family pilgrimage to Raigad with ropeway booking.');

INSERT INTO visited_places (user_id, destination_id, visit_date, rating, experience_notes, companion_type, trek_experience) VALUES
(2, 1, '2026-01-15', 5, 'Explored the entire plateau including Takmak Tok and Jagdishwar temple. Breathtaking sunset.', 'Friends', 'Exhilarating'),
(2, 3, '2026-02-19', 5, 'Visited on Shiv Jayanti. The fort was decorated with saffron flags, dhol-tasha, and proud chants.', 'Family', 'Inspiring');

-- -------------------------------------------------------------
-- Sample Trip Planner Data
-- -------------------------------------------------------------
INSERT INTO trips (id, user_id, title, description, start_date, end_date, is_public) VALUES
(1, 2, 'Great Pune Forts Circuit', 'A scenic 3-day exploration of Sinhagad, Rajgad, and Torna hill forts.', '2026-12-10', '2026-12-12', 1);

INSERT INTO trip_destinations (trip_id, destination_id, day_number, visit_order, notes) VALUES
(1, 3, 1, 1, 'Morning trek to Sinhagad Fort, explore Tanaji memorial and enjoy Pithla Bhakri.'),
(1, 5, 2, 1, 'Climb Rajgad via Pali Darwaja, explore Padmavati Machi and overnight stay.'),
(1, 6, 3, 1, 'Trek Torna Fort (Prachandagad) and return to Pune by evening.');

-- ==============================================================
-- End of database.sql
-- ==============================================================
