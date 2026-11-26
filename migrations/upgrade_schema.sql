-- ==============================================================
-- FORT EXPLORER - ADVANCED UPGRADE SCHEMA & MIGRATION
-- Safely expands existing schema without dropping or wiping data
-- ==============================================================

-- 1. Extend destinations table
ALTER TABLE destinations ADD COLUMN IF NOT EXISTS view_count INT DEFAULT 0;
ALTER TABLE destinations ADD COLUMN IF NOT EXISTS audio_summary_en LONGTEXT DEFAULT NULL;
ALTER TABLE destinations ADD COLUMN IF NOT EXISTS audio_summary_mr LONGTEXT DEFAULT NULL;
ALTER TABLE destinations ADD COLUMN IF NOT EXISTS audio_summary_hi LONGTEXT DEFAULT NULL;
ALTER TABLE destinations ADD COLUMN IF NOT EXISTS trekking_equipment TEXT DEFAULT NULL;
ALTER TABLE destinations ADD COLUMN IF NOT EXISTS water_food_guidelines TEXT DEFAULT NULL;
ALTER TABLE destinations ADD COLUMN IF NOT EXISTS weather_precautions TEXT DEFAULT NULL;
ALTER TABLE destinations ADD COLUMN IF NOT EXISTS emergency_contacts TEXT DEFAULT NULL;

-- 2. Extend reviews table
ALTER TABLE reviews ADD COLUMN IF NOT EXISTS photo_url VARCHAR(500) DEFAULT NULL;
ALTER TABLE reviews ADD COLUMN IF NOT EXISTS helpful_votes INT DEFAULT 0;

-- 3. Extend trips table
ALTER TABLE trips ADD COLUMN IF NOT EXISTS share_token VARCHAR(64) DEFAULT NULL;
ALTER TABLE trips ADD COLUMN IF NOT EXISTS start_location VARCHAR(150) DEFAULT 'Pune';
ALTER TABLE trips ADD COLUMN IF NOT EXISTS total_distance_km DECIMAL(8, 2) DEFAULT 0;
ALTER TABLE trips ADD COLUMN IF NOT EXISTS estimated_duration_hours DECIMAL(6, 2) DEFAULT 0;
ALTER TABLE trips ADD COLUMN IF NOT EXISTS budget_data LONGTEXT DEFAULT NULL;

-- -------------------------------------------------------------
-- 4. ACHIEVEMENTS & GAMIFICATION
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(60) NOT NULL UNIQUE,
    title_en VARCHAR(120) NOT NULL,
    title_mr VARCHAR(120) NOT NULL,
    title_hi VARCHAR(120) NOT NULL,
    description_en VARCHAR(255) NOT NULL,
    description_mr VARCHAR(255) NOT NULL,
    description_hi VARCHAR(255) NOT NULL,
    badge_icon VARCHAR(60) NOT NULL DEFAULT 'fa-award',
    badge_color VARCHAR(30) NOT NULL DEFAULT '#163820',
    criteria_type VARCHAR(60) NOT NULL, -- 'first_visit', 'forts_count', 'total_visits', 'districts_count'
    threshold_value INT NOT NULL DEFAULT 1,
    points INT DEFAULT 50,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    achievement_id INT NOT NULL,
    awarded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_achievement (user_id, achievement_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (achievement_id) REFERENCES achievements(id) ON DELETE CASCADE,
    INDEX idx_ua_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 5. COMMUNITY POSTS, COMMENTS, LIKES, FOLLOWS & REPORTS
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS community_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    destination_id INT DEFAULT NULL,
    title VARCHAR(200) NOT NULL,
    content LONGTEXT NOT NULL,
    image_url VARCHAR(500) DEFAULT NULL,
    trek_difficulty VARCHAR(50) DEFAULT 'Moderate',
    travel_tips TEXT DEFAULT NULL,
    likes_count INT DEFAULT 0,
    comments_count INT DEFAULT 0,
    status ENUM('published', 'flagged', 'hidden') DEFAULT 'published',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (destination_id) REFERENCES destinations(id) ON DELETE SET NULL,
    INDEX idx_cp_user (user_id),
    INDEX idx_cp_dest (destination_id),
    INDEX idx_cp_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS community_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    parent_id INT DEFAULT NULL,
    comment_text TEXT NOT NULL,
    status ENUM('published', 'hidden') DEFAULT 'published',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES community_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES community_comments(id) ON DELETE CASCADE,
    INDEX idx_cc_post (post_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS community_likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_post_user_like (post_id, user_id),
    FOREIGN KEY (post_id) REFERENCES community_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_follows (
    id INT AUTO_INCREMENT PRIMARY KEY,
    follower_id INT NOT NULL,
    following_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_follow (follower_id, following_id),
    FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (following_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS community_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reporter_id INT NOT NULL,
    post_id INT DEFAULT NULL,
    comment_id INT DEFAULT NULL,
    reason VARCHAR(100) NOT NULL,
    details TEXT DEFAULT NULL,
    status ENUM('pending', 'resolved', 'dismissed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (post_id) REFERENCES community_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (comment_id) REFERENCES community_comments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 6. REVIEW HELPFUL VOTES & REPORTS
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS review_helpful_votes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    review_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_review_helpful (review_id, user_id),
    FOREIGN KEY (review_id) REFERENCES reviews(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS review_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    review_id INT NOT NULL,
    reporter_id INT NOT NULL,
    reason VARCHAR(150) NOT NULL,
    status ENUM('pending', 'reviewed', 'dismissed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (review_id) REFERENCES reviews(id) ON DELETE CASCADE,
    FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 7. NEARBY PLACES / POIs (Hotels, Restaurants, Medical, Viewpoints)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS nearby_places (
    id INT AUTO_INCREMENT PRIMARY KEY,
    destination_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    place_type ENUM('hotel', 'restaurant', 'temple', 'waterfall', 'viewpoint', 'hospital', 'fuel_station', 'fort') NOT NULL,
    distance_km DECIMAL(5, 2) NOT NULL,
    latitude DECIMAL(10, 7) DEFAULT NULL,
    longitude DECIMAL(10, 7) DEFAULT NULL,
    contact_phone VARCHAR(50) DEFAULT NULL,
    address VARCHAR(255) DEFAULT NULL,
    description VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (destination_id) REFERENCES destinations(id) ON DELETE CASCADE,
    INDEX idx_np_dest (destination_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 8. AI HERITAGE ASSISTANT CHAT LOGS & KNOWLEDGE SNIPPETS
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ai_chat_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    session_id VARCHAR(100) NOT NULL,
    user_query TEXT NOT NULL,
    bot_reply LONGTEXT NOT NULL,
    detected_lang VARCHAR(10) DEFAULT 'en',
    helpful_vote TINYINT DEFAULT NULL, -- 1 = Up, -1 = Down
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_ai_sess (session_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ai_knowledge_snippets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category VARCHAR(60) NOT NULL,
    keywords VARCHAR(255) NOT NULL,
    question_pattern VARCHAR(255) NOT NULL,
    answer_en LONGTEXT NOT NULL,
    answer_mr LONGTEXT NOT NULL,
    answer_hi LONGTEXT NOT NULL,
    related_destination_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (related_destination_id) REFERENCES destinations(id) ON DELETE SET NULL,
    INDEX idx_aik_cat (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ==============================================================
-- INITIAL SAMPLE DATA FOR EXPANDED MODULES
-- ==============================================================

-- 1. Insert Achievements
INSERT IGNORE INTO achievements (id, code, title_en, title_mr, title_hi, description_en, description_mr, description_hi, badge_icon, badge_color, criteria_type, threshold_value, points) VALUES
(1, 'first_fort_visited', 'First Fort Conquered', 'पहिला गड सर', 'पहला किला फतह', 'Recorded your very first verified visit to a historic fort.', 'ऐतिहासिक दुर्गास दिलेली पहिली अधिकृत भेट.', 'किसी ऐतिहासिक किले की पहली आधिकारिक यात्रा दर्ज की।', 'fa-compass', '#15803d', 'first_visit', 1, 50),
(2, 'fort_enthusiast', 'Fort Enthusiast', 'दुर्ग भ्रमंतीकार', 'किला प्रेमी', 'Visited 3 or more historic Sahyadri forts.', '३ किंवा अधिक सह्याद्रीच्या दुर्गांची यशस्वी सफर.', '३ या अधिक सह्याद्री किलों की सफल यात्रा।', 'fa-shield-halved', '#8b2616', 'forts_count', 3, 100),
(3, 'five_forts_completed', '5 Forts Conquered', 'पंचदुर्ग विजेता', 'पांच दुर्ग विजेता', 'Conquered 5 legendary forts of Maharashtra.', 'महाराष्ट्रातील ५ अजिंक्य किल्ले सर केले.', 'महाराष्ट्र के ५ किलों की यात्रा पूर्ण की।', 'fa-flag', '#c59b27', 'forts_count', 5, 200),
(4, 'ten_forts_completed', '10 Forts Master', 'दशदुर्ग स्वामी', 'दस दुर्ग स्वामी', 'Conquered 10 forts across Maharashtra.', 'महाराष्ट्रातील १० ऐतिहासिक किल्ले सर केले.', '१० ऐतिहासिक किलों की यात्रा पूर्ण की।', 'fa-crown', '#d97706', 'forts_count', 10, 400),
(5, 'twenty_forts_completed', 'Sahyadri Chhatrapati', 'सह्याद्री दुर्गसम्राट', 'सह्याद्रि दुर्गसम्राट', 'Achieved the legendary summit milestone of 20 forts.', '२० किल्ल्यांची भव्य सफर पूर्ण करणारा सर्वोच्च सन्मान.', '२० किलों की यात्रा पूर्ण करने वाला सर्वोच्च सम्मान।', 'fa-trophy', '#4f46e5', 'forts_count', 20, 1000),
(6, 'heritage_explorer', 'Heritage Explorer', 'वारसा अभ्यासक', 'धरोहर अन्वेषक', 'Visited at least 5 different tourist and heritage destinations.', 'किमान ५ वेगवेगळ्या ऐतिहासिक वास्तू व वारसा स्थळांना भेट दिली.', 'कम से कम ५ विभिन्न ऐतिहासिक व पर्यटन स्थलों का दौरा किया।', 'fa-landmark', '#6d28d9', 'total_visits', 5, 150),
(7, 'maharashtra_explorer', 'Maharashtra Explorer', 'महाराष्ट्र प्रवासी', 'महाराष्ट्र यात्री', 'Explored destinations across 3 or more administrative districts.', '३ किंवा अधिक जिल्ह्यांमधील पर्यटन स्थळांचा प्रवास केला.', '३ या अधिक जिलों के पर्यटन स्थलों का भ्रमण किया।', 'fa-map-location-dot', '#0284c7', 'districts_count', 3, 250);

-- Award existing user achievements based on visited_places
INSERT IGNORE INTO user_achievements (user_id, achievement_id)
SELECT vp.user_id, 1 FROM visited_places vp GROUP BY vp.user_id HAVING COUNT(*) >= 1;

INSERT IGNORE INTO user_achievements (user_id, achievement_id)
SELECT vp.user_id, 2 FROM visited_places vp 
JOIN destinations d ON vp.destination_id = d.id 
WHERE d.is_shivaji_fort = 1 GROUP BY vp.user_id HAVING COUNT(*) >= 3;

-- 2. Populate Audio Summaries & Trekking Guides for key destinations
UPDATE destinations SET 
audio_summary_en = 'Welcome to Raigad Fort, the sovereign capital of the Maratha Empire under Chhatrapati Shivaji Maharaj. Perched at 820 meters in the Sahyadris, it is surrounded by sheer cliffs and deep gorges. Highlights include the magnificent Maha Darwaja, the royal acoustic court of Nagarkhana where even whispers travel to the throne, the queen palaces, the holy Samadhi of Chhatrapati Shivaji Maharaj, and the thrilling Takmak Tok precipice. We encourage you to honor the sanctity of this sacred ground and keep the plateau plastic-free.',
audio_summary_mr = 'किल्ले रायगडावर आपले मनःपूर्वक स्वागत! छत्रपती शिवाजी महाराजांच्या हिंदवी स्वराज्याची ही पवित्र राजधानी आहे. सह्याद्रीच्या अथांग कड्यांवर वसलेला हा गिरिदुर्ग स्थापत्यकलेचा सर्वोच्च नमुना आहे. येथील भव्य महादरवाजा, नाणी व ध्वनीशास्त्राचा चमत्कार दाखवणारा नगारखाना, राजसभा, राणीवसा, आणि युगपुरुष छत्रपती शिवरायांची पवित्र समाधी आपल्याला प्रेरणा देतात. या पवित्र भूमीचे पावित्र्य जपा आणि स्वच्छता राखा.',
audio_summary_hi = 'किले रायगढ़ में आपका हार्दिक स्वागत है! यह छत्रपति शिवाजी महाराज के हिंदवी स्वराज्य की ऐतिहासिक राजधानी है। सह्याद्रि की चोटियों पर स्थित इस किले में भव्य महादरवाजा, नगाड़ाखाना राजदरबार, और छत्रपति शिवाजी महाराज की पवित्र समाधि स्थित है। इस पवित्र धरोहर का सम्मान करें और स्वच्छता बनाए रखें।',
trekking_equipment = 'Sturdy trekking shoes with deep lug grip, minimum 2.5 liters of drinking water per person, energy bars/dry fruits, rain cover/poncho during monsoon, lightweight trekking pole, first aid kit with crepe bandage, headlamp if descending near dusk.',
water_food_guidelines = 'Fresh spring water available at Ganga Sagar Lake and local village points. Steaming traditional Pithla-Bhakri and thecha available at local stalls atop the plateau.',
weather_precautions = 'During monsoon (July-August), heavy gale-force winds and low cloud mist reduce visibility. Stay away from the edge of Takmak Tok and Hirkani Buruj. In summer, carry electrolyte salts and begin descent before noon heat.',
emergency_contacts = 'Mahad Taluka Police: 02145-222100 | Raigad Ropeway Medical Helpline: 02145-202122 | Maharashtra State Emergency Services: 112'
WHERE slug = 'raigad-fort';

UPDATE destinations SET 
audio_summary_en = 'Welcome to Shivneri Fort, the revered birthplace of Chhatrapati Shivaji Maharaj on February 19, 1630. Guarded by seven sequential arched gateways, this fortress was deemed virtually impregnable. Explore the Shivkunj memorial hall where Maharaj was born, the Shivai Devi Temple after whom he was named, Badami Talav, and the ancient Ganga-Jamuna rock-cut water cisterns.',
audio_summary_mr = 'किल्ले शिवनेरीवर आपले स्वागत! १९ फेब्रुवारी १६३० रोजी या पवित्र गडावर रयतेचा राजा छत्रपती शिवाजी महाराजांचा जन्म झाला. सात भव्य दरवाज्यांची तटबंदी असलेला हा किल्ला इतिहासाची अमर साक्ष देतो. येथे जन्मस्थान शिवकुंज, शिवाई देवीचे मंदिर, बदामी तलाव आणि गंगा-जमुना पाण्याच्या टाक्या अवश्य पहा.',
audio_summary_hi = 'किले शिवनेरी में आपका स्वागत है! १९ फरवरी १६३० को इस पावन भूमि पर छत्रपति शिवाजी महाराज का जन्म हुआ था। सात मजबूत द्वारों से सुरक्षित यह किला शौर्य की प्रेरणा है। यहाँ शिवकुंज जन्मस्थान और शिवाई देवी मंदिर प्रमुख आकर्षण हैं।',
trekking_equipment = 'Comfortable walking shoes, drinking water, sun hat or cap, camera.',
water_food_guidelines = 'Fresh drinking water points at fort gates. Junnar town at the base offers traditional vegetarian and Maharashtrian meals.',
weather_precautions = 'Very safe stone-paved stairs. Monkeys are present near temple steps; avoid carrying open plastic bags or unsealed food.',
emergency_contacts = 'Junnar Police Station: 02132-222033 | District Health Center Junnar: 02132-222045 | Emergency: 112'
WHERE slug = 'shivneri-fort';

UPDATE destinations SET 
audio_summary_en = 'Welcome to Sinhagad Fort, historically known as Kondhana. Situated 30 kilometers from Pune, it is celebrated for the heroic 1670 battle led by Subedar Tanaji Malusare. Marvel at the Kalyan Darwaja looking down upon the Konkan valley, the memorial of Tanaji Malusare, and relish steaming Pithla Bhakri and matka curd.',
audio_summary_mr = 'किल्ले सिंहगडावर आपले स्वागत! १६७० मधील सुभेदार तानाजी मालुसरे यांच्या अद्वितीय पराक्रमाची साक्ष देणारा हा गड आहे. ''गड आला पण सिंह गेला'' ही महाराजांची अमर भावना या गडाशी जोडलेली आहे. तानाजींचे समाधी स्मारक, कल्याण दरवाजा आणि देवटाके येथील अमृततुल्य पाणी अवश्य अनुभवा.',
audio_summary_hi = 'किले सिंहगढ़ में आपका स्वागत है! सूबेदार तानाजी मालुसरे के सर्वोच्च बलिदान की यह अमर भूमि है। यहाँ कल्याण दरवाजा, तानाजी स्मारक और ठंडी घाट हवाएं पर्यटकों को आकर्षित करती हैं।',
trekking_equipment = 'High traction trekking shoes, water bottle, light rain jacket in monsoon.',
water_food_guidelines = 'Natural cold sweet spring water at Devtaki tanks. Legendary fresh hot Pithla-Bhakri, Kanda Bhaji, and Matka Dahi stalls throughout the ridge.',
weather_precautions = 'Heavy weekend mist in monsoon. Do not cross protective fencing at Kadelot point.',
emergency_contacts = 'Haveli Rural Police: 020-24388100 | Sinhagad Forest Department: 020-25635400 | Emergency: 112'
WHERE slug = 'sinhagad-fort';

-- 3. Populate Nearby Places / POIs
INSERT IGNORE INTO nearby_places (destination_id, name, place_type, distance_km, contact_phone, address, description) VALUES
(1, 'MTDC Resort Raigad', 'hotel', 0.2, '+91 2145 202122', 'Top of Raigad Fort plateau', 'Government operated cottages with panoramic sunset valley views.'),
(1, 'Hotel Hirkani Pachad', 'restaurant', 1.8, '+91 98223 45678', 'Pachad Base Village', 'Authentic traditional Maharashtrian vegetarian and Kokani thalis.'),
(1, 'Pachad Rural Hospital', 'hospital', 3.2, '02145-224411', 'Pachad-Mahad Road', 'Primary rural healthcare and emergency trauma center.'),
(1, 'BPCL Petrol Pump Mahad', 'fuel_station', 22.0, '02145-222333', 'NH 66 Mahad Bypass', 'Fuel, air, and 24-hour convenience store.'),
(1, 'Gandharpale Buddhist Caves', 'viewpoint', 24.5, NULL, 'Near Mahad on NH 66', 'Ancient rock-cut Buddhist caves overlooking Savitri river.'),

(3, 'Sinhagad Forest Cafeteria', 'restaurant', 0.1, NULL, 'Upper parking lot, Sinhagad', 'Authentic wood-fire cooked Pithla Bhakri and Matka Dahi.'),
(3, 'Khadakwasla Dam Backwaters', 'viewpoint', 12.0, NULL, 'Khadakwasla-Donje Road', 'Scenic lakeside viewpoint popular for sunset strolls.'),
(3, 'Donje Primary Health Clinic', 'hospital', 8.5, '020-24388200', 'Donje Village Phata', 'Local primary clinic and ambulance services.'),
(3, 'Indian Oil Petrol Pump Kirkatwadi', 'fuel_station', 14.0, NULL, 'Sinhagad Road Kirkatwadi', 'Full service fuel station and tyre puncture repair.'),

(4, 'Pratap Heritage Hotel', 'hotel', 18.0, '+91 2168 260341', 'Old Mahabaleshwar Road', 'Comfortable family resort with garden and dining.'),
(4, 'Afzal Khan Dargah Stalls', 'restaurant', 0.8, NULL, 'Pratapgad base trail', 'Local strawberry shakes, corn, and warm tea.'),
(4, 'Mahabaleshwar Municipal Hospital', 'hospital', 21.0, '02168-260222', 'Mahabaleshwar town', 'Full civil medical facility with 24x7 emergency ward.');

-- 4. Populate AI Knowledge Snippets (Bilingual/Trilingual FAQs)
INSERT IGNORE INTO ai_knowledge_snippets (id, category, keywords, question_pattern, answer_en, answer_mr, answer_hi, related_destination_id) VALUES
(1, 'history', 'capital raigad coronation 1674 gaga bhatt', 'Why was Raigad chosen as the capital of the Maratha Empire?', 
'Chhatrapati Shivaji Maharaj chose Raigad (formerly Rairi) as his capital in 1674 due to its impregnable natural geography. Surrounded on three sides by deep gorges and soaring 820 meters high in the Sahyadris, it was practically inaccessible to artillery attacks and provided a commanding vantage over both the Deccan plateau and coastal Konkan trade routes.',
'छत्रपती शिवाजी महाराजांनी १६७४ मध्ये रायगड (पूर्वीचा रायरी) या किल्ल्याची निवड त्याच्या अजिंक्य भौगोलिक रचनेमुळे केली. तिन्ही बाजूंनी खोल दऱ्या आणि ८२० मीटर उंचीमुळे तो तोफांच्या माऱ्यापासून सुरक्षित होता. तसेच येथून दख्खनचे पठार आणि कोकण किनारपट्टी या दोन्हीवर नियंत्रण ठेवणे सहज शक्य होते.',
'छत्रपती शिवाजी महाराज ने १६७४ में रायगढ़ को अपनी राजधानी इसलिए चुना क्योंकि इसकी प्राकृतिक भौगोलिक बनावट अत्यंत अभेद्य थी। तीनों ओर से गहरी घाटियों से घिरा यह किला तोपों के हमले से सुरक्षित था और दक्कन तथा कोंकण दोनों क्षेत्रों पर नियंत्रण रखता था।',
1),

(2, 'timing_fees', 'raigad ropeway timings ticket cost opening closing', 'What are the ropeway timings and ticket prices at Raigad Fort?',
'The Raigad Ropeway operates daily from 08:00 AM to 05:30 PM. Round-trip ropeway tickets typically cost approx. INR 350 to 400 per person. General ASI entry ticket to the fort is INR 25 for Indian citizens and free for children under 15 years.',
'रायगड रोप-वे दररोज सकाळी ०८:०० ते संध्याकाळी ०५:३० या वेळेत सुरू असतो. रोप-वेचे दोन्ही बाजूंचे तिकीट साधारणपणे ₹३५० ते ₹४०० प्रतिव्यक्ती असते. पुरातत्व विभागाचे गडाचे प्रवेश शुल्क भारतीयांसाठी ₹२५ असून १५ वर्षांखालील मुलांना मोफत प्रवेश आहे.',
'रायगढ़ रोपवे प्रतिदिन सुबह ०८:०० से शाम ०५:३० बजे तक संचालित होता है। आने-जाने का टिकट लगभग ₹३५० से ₹४०० प्रति व्यक्ति है। किले का सामान्य प्रवेश टिकट ₹२५ है तथा १५ वर्ष से कम उम्र के बच्चों के लिए निःशुल्क है।',
1),

(3, 'history', 'shivneri birthplace birth date february 19 1630 jijabai', 'Where was Chhatrapati Shivaji Maharaj born?',
'Chhatrapati Shivaji Maharaj was born on February 19, 1630 at the hill fortress of Shivneri, located near Junnar in Pune district. He was born in the Shivkunj building to Rajmata Jijabai and Shahaji Raje Bhosle, and named after Goddess Shivai.',
'छत्रपती शिवाजी महाराजांचा जन्म १९ फेब्रुवारी १६३० रोजी पुणे जिल्ह्यातील जुन्नर जवळील किल्ले शिवनेरीवर झाला. राजमाता जिजाऊंच्या पोटी शिवकुंज इमारतीत त्यांचा जन्म झाला आणि शिवाई देवीच्या नावावरून त्यांचे नाव ''शिवाजी'' ठेवण्यात आले.',
'छत्रपति शिवाजी महाराज का जन्म १९ फरवरी १६३० को पुणे जिले के जुन्नर स्थित शिवनेरी किले में हुआ था। राजमाता जीजाबाई ने शिवकुंज में उन्हें जन्म दिया और देवी शिवाई के नाम पर उनका नाम शिवाजी रखा गया।',
2),

(4, 'best_season', 'best time visit sinhagad monsoon rain weather', 'What is the best season to visit Sinhagad Fort?',
'The best season to visit Sinhagad Fort is during the Monsoon (July to September) for lush greenery, waterfalls, and misty fog, and Winter (October to February) for clear panoramic views and pleasant trekking temperatures.',
'सिंहगडाला भेट देण्यासाठी सर्वोत्तम काळ मान्सून (जुलै ते सप्टेंबर) आहे जेव्हा संपूर्ण गड हिरवागार असतो आणि धुक्याची चादर असते. तसेच हिवाळ्यात (ऑक्टोबर ते फेब्रुवारी) सुखद वातावरणात ट्रेकिंगचा उत्तम आनंद मिळतो.',
'सिंहगढ़ किले की यात्रा के लिए सबसे अच्छा समय मानसून (जुलाई से सितंबर) है, जब किला हरी वादियों और कोहरे से ढका होता है। सर्दियों (अक्टूबर से फरवरी) में भी यहाँ का मौसम अत्यंत सुहावना रहता है।',
3),

(5, 'travel_plan', 'pratapgad how to reach mahabaleshwar distance', 'How do I reach Pratapgad Fort from Mahabaleshwar?',
'Pratapgad Fort is situated approximately 21 kilometers west of Mahabaleshwar town along the Poladpur road. Regular state transport buses (MSRTC) and private cabs are readily available from Mahabaleshwar central taxi stand. The drive takes about 45 minutes through scenic ghat roads.',
'प्रतापगड महाबळेश्वर शहरापासून पश्चिमेस पोलादपूर रस्त्यावर साधारण २१ किमी अंतरावर आहे. महाबळेश्वर बस स्थानकावरून एसटी बसेस आणि खाजगी टॅक्सी उपलब्ध असतात. निसर्गरम्य घाटातून वाहनाने जाण्यास सुमारे ४५ मिनिटे लागतात.',
'प्रतापगढ़ किला महाबलेश्वर शहर से पश्चिम में पोलादपुर मार्ग पर लगभग २१ किमी की दूरी पर है। महाबलेश्वर से नियमित बसें और टैक्सियां उपलब्ध हैं। सुंदर घाटों से होते हुए लगभग ४५ मिनट का समय लगता है।',
4);

-- 5. Seed Initial Community Posts
INSERT IGNORE INTO community_posts (id, user_id, destination_id, title, content, image_url, trek_difficulty, travel_tips, likes_count, comments_count) VALUES
(1, 2, 1, 'Sunrise over the Clouds at Raigad Capital', 'Had an unforgettable experience camping near Pachad and reaching the Balekilla right as the sun broke through the Sahyadri morning mist. Standing in front of the holy Samadhi of Chhatrapati Shivaji Maharaj was deeply humbling. The sheer drop at Takmak Tok is majestic yet intimidating!', 'https://images.unsplash.com/photo-1598894000396-bc4a04d509f6?auto=format&fit=crop&w=1200&q=80', 'Moderate', 'Start climbing by 5:30 AM to beat the mid-day heat. Carry minimum 2L water per person.', 8, 2),
(2, 3, 3, 'Monsoon Splendor & Authentic Pithla Bhakri on Sinhagad', 'Climbed Sinhagad through the traditional Donje route yesterday. The waterfall cascades on the route were refreshing. Rewarded ourselves at the top with hot wood-fired Pithla Bhakri, fiery thecha, and fresh thick earthen pot curd (Matka Dahi). A must-do trek for every Punekar!', 'https://images.unsplash.com/photo-1589308078059-be1415eab4c3?auto=format&fit=crop&w=1200&q=80', 'Moderate', 'Wear shoes with deep lug patterns; the rock steps near Pune Darwaja become slippery when wet.', 12, 3);

INSERT IGNORE INTO community_comments (post_id, user_id, comment_text) VALUES
(1, 3, 'Breathtaking photo Tanmay! Did you take the ropeway or climb the traditional stairs?'),
(1, 2, 'Climbed all 1700 steps up for the authentic experience, and took the ropeway down! Highly recommend.');
