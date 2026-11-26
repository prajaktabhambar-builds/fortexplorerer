<?php
/**
 * FORT EXPLORER - Multilingual Localization (English & Marathi)
 */

if (session_status() === PHP_SESSION_NONE && php_sapi_name() !== 'cli') {
    session_start();
}

// Handle language toggle query parameter
if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'mr'], true)) {
    $_SESSION['lang'] = $_GET['lang'];
    setcookie('fe_lang', $_GET['lang'], time() + (86400 * 365), '/');
} elseif (!isset($_SESSION['lang'])) {
    if (isset($_COOKIE['fe_lang']) && in_array($_COOKIE['fe_lang'], ['en', 'mr'], true)) {
        $_SESSION['lang'] = $_COOKIE['fe_lang'];
    } else {
        $_SESSION['lang'] = 'en'; // Default language
    }
}

/**
 * Get current active language code ('en' or 'mr')
 */
function current_lang(): string {
    return $_SESSION['lang'] ?? 'en';
}

/**
 * Check if Marathi is active
 */
function is_marathi(): bool {
    return current_lang() === 'mr';
}

/**
 * Multilingual Dictionary
 */
$GLOBALS['FE_TRANSLATIONS'] = [
    'en' => [
        // Brand & Tagline
        'site_title' => 'FORT EXPLORER',
        'site_tagline' => 'Discover History. Explore Heritage. Create Memories.',
        'hero_subtitle' => 'Unveil the timeless saga of Chhatrapati Shivaji Maharajs invincible forts and the natural wonders of the Sahyadri ranges.',
        
        // Navigation
        'nav_home' => 'Home',
        'nav_explore' => 'Explore Destinations',
        'nav_forts' => 'Shivaji Maharaj Forts',
        'nav_history' => 'Historical Timeline',
        'nav_map' => 'Interactive Map',
        'nav_trip_planner' => 'Trip Planner',
        'nav_my_journey' => 'My Journey',
        'nav_login' => 'Sign In',
        'nav_register' => 'Register',
        'nav_profile' => 'My Profile',
        'nav_admin' => 'Admin Panel',
        'nav_logout' => 'Log Out',
        
        // Buttons & Actions
        'btn_explore' => 'Explore Destinations',
        'btn_view_details' => 'View Details',
        'btn_save_wishlist' => 'Save to Wishlist',
        'btn_saved_wishlist' => 'In Wishlist',
        'btn_mark_visited' => 'Mark as Visited',
        'btn_visited' => 'Visited',
        'btn_search' => 'Search',
        'btn_filter' => 'Filter',
        'btn_clear' => 'Reset Filters',
        'btn_get_directions' => 'Get Directions',
        'btn_plan_trip' => 'Plan a Trip',
        'btn_submit_review' => 'Submit Review',
        'btn_upload_photo' => 'Upload Travel Photo',
        'btn_save_changes' => 'Save Changes',
        
        // Search & Filters
        'search_placeholder' => 'Search forts, temples, waterfalls, hill stations, districts...',
        'filter_category' => 'All Categories',
        'filter_district' => 'All Districts',
        'filter_difficulty' => 'All Difficulty Levels',
        'filter_sort' => 'Sort By',
        'sort_popular' => 'Most Popular',
        'sort_rating' => 'Highest Rated',
        'sort_name_asc' => 'Name (A to Z)',
        'sort_difficulty' => 'Difficulty',
        
        // Sections
        'section_featured_forts' => 'Featured Forts of Chhatrapati Shivaji Maharaj',
        'section_featured_forts_sub' => 'The architectural strongholds that laid the sovereign foundation of Hindavi Swarajya.',
        'section_popular_destinations' => 'Popular Destinations in Maharashtra',
        'section_popular_destinations_sub' => 'From cascading waterfalls to ancient rock sanctuaries in the Sahyadris.',
        'section_why_explore' => 'Why Explore With Fort Explorer?',
        'section_stats' => 'Our Living Heritage Network',
        'stat_destinations' => 'Heritage Destinations',
        'stat_forts' => 'Historic Forts',
        'stat_districts' => 'Districts Covered',
        'stat_explorers' => 'Active Explorers',
        'stat_reviews' => 'Visitor Reviews',
        
        // Destination Detail Labels
        'label_overview' => 'Overview & Introduction',
        'label_history' => 'Historical Significance',
        'label_shivaji_connection' => 'Connection with Chhatrapati Shivaji Maharaj',
        'label_architecture' => 'Architecture & Bastions',
        'label_major_attractions' => 'Major Attractions Inside',
        'label_best_season' => 'Best Season & Timing',
        'label_opening_hours' => 'Opening & Closing Hours',
        'label_entry_fees' => 'Entry Fees & Permits',
        'label_how_to_reach' => 'How to Reach',
        'label_by_road' => 'By Road',
        'label_by_rail' => 'By Rail',
        'label_by_air' => 'By Air',
        'label_distances' => 'Distance from Major Cities',
        'label_from_pune' => 'From Pune',
        'label_from_mumbai' => 'From Mumbai',
        'label_facilities' => 'Available Facilities',
        'label_safety_tips' => 'Safety & Trekking Guidelines',
        'label_nearby' => 'Nearby Attractions',
        'label_reviews_ratings' => 'Visitor Reviews & Experiences',
        'label_write_review' => 'Write a Review',
        'verified_tag' => 'Verified Official Timing',
        'unverified_tag' => 'Timings subject to local weather and forest authorities',
        
        // Journey & Dashboard
        'journey_title' => 'My Sahyadri Travel Journey',
        'journey_subtitle' => 'Track your visited fortresses, personal expedition notes, and wishlisted wonders.',
        'tab_wishlist' => 'Want to Visit (Wishlist)',
        'tab_visited' => 'Visited Destinations',
        'tab_photos' => 'My Travel Photos',
        'tab_badges' => 'Explorer Badges',
        
        // Common
        'footer_about' => 'Fort Explorer is dedicated to celebrating, preserving, and exploring the glorious forts and natural wonders of Maharashtra, with special focus on the timeless vision of Chhatrapati Shivaji Maharaj.',
        'copyright' => 'All rights reserved. Dedicated to the heritage of Maharashtra.'
    ],
    
    'mr' => [
        // Brand & Tagline
        'site_title' => 'फोर्ट एक्सप्लोरर',
        'site_tagline' => 'इतिहास अनुभवा. वारसा जपा. स्मृती जपा.',
        'hero_subtitle' => 'छत्रपती शिवाजी महाराजांच्या अजिंक्य दुर्गांची आणि सह्याद्रीच्या निसर्गवैभवाची गौरवशाली सफर.',
        
        // Navigation
        'nav_home' => 'मुखपृष्ठ',
        'nav_explore' => 'पर्यटन स्थळे',
        'nav_forts' => 'शिवकालीन किल्ले',
        'nav_history' => 'ऐतिहासिक कालपट',
        'nav_map' => 'संवादी नकाशा',
        'nav_trip_planner' => 'सहल नियोजन',
        'nav_my_journey' => 'माझी सफर',
        'nav_login' => 'प्रवेश करा',
        'nav_register' => 'नोंदणी करा',
        'nav_profile' => 'माझे प्रोफाईल',
        'nav_admin' => 'प्रशासक फलक',
        'nav_logout' => 'बाहेर पडा',
        
        // Buttons & Actions
        'btn_explore' => 'स्थळे एक्सप्लोर करा',
        'btn_view_details' => 'सविस्तर माहिती',
        'btn_save_wishlist' => 'भेट यादीत जोडा',
        'btn_saved_wishlist' => 'भेट यादीत आहे',
        'btn_mark_visited' => 'भेट दिली म्हणून नोंदवा',
        'btn_visited' => 'भेट दिलेली स्थळे',
        'btn_search' => 'शोधा',
        'btn_filter' => 'फिल्टर',
        'btn_clear' => 'पूर्ववत करा',
        'btn_get_directions' => 'दिशा व मार्ग',
        'btn_plan_trip' => 'सहलीचे नियोजन करा',
        'btn_submit_review' => 'अभिप्राय पाठवा',
        'btn_upload_photo' => 'प्रवास छायाचित्र जोडा',
        'btn_save_changes' => 'बदल जतन करा',
        
        // Search & Filters
        'search_placeholder' => 'किल्ले, मंदिरे, धबधबे, थंड हवेची ठिकाणे, जिल्हे शोधा...',
        'filter_category' => 'सर्व वर्गवारी',
        'filter_district' => 'सर्व जिल्हे',
        'filter_difficulty' => 'काठिण्य पातळी',
        'filter_sort' => 'क्रमवारी लावा',
        'sort_popular' => 'सर्वाधिक लोकप्रिय',
        'sort_rating' => 'उत्कृष्ट मानांकन',
        'sort_name_asc' => 'नावानुसार (अ ते ज्ञ)',
        'sort_difficulty' => 'काठिण्य पातळीनुसार',
        
        // Sections
        'section_featured_forts' => 'छत्रपती शिवाजी महाराजांचे वैशिष्ट्यपूर्ण किल्ले',
        'section_featured_forts_sub' => 'ज्या अभेद्य दुर्गांच्या आधारे रयतेचे हिंदवी स्वराज्य उभे राहिले.',
        'section_popular_destinations' => 'महाराष्ट्रातील लोकप्रिय पर्यटन स्थळे',
        'section_popular_destinations_sub' => 'सह्याद्रीच्या कुशीतील फेसाळणारे धबधबे ते प्राचीन पाषाण लेणी.',
        'section_why_explore' => 'फोर्ट एक्सप्लोरर का निवडावे?',
        'section_stats' => 'आपला ऐतिहासिक परिवार',
        'stat_destinations' => 'वारसा स्थळे',
        'stat_forts' => 'ऐतिहासिक किल्ले',
        'stat_districts' => 'समाविष्ट जिल्हे',
        'stat_explorers' => 'सक्रिय दुर्गप्रेमी',
        'stat_reviews' => 'पर्यटक अभिप्राय',
        
        // Destination Detail Labels
        'label_overview' => 'प्रास्ताविक व विहंगावलोकन',
        'label_history' => 'ऐतिहासिक महत्त्व व पार्श्वभूमी',
        'label_shivaji_connection' => 'छत्रपती शिवाजी महाराजांशी ऐतिहासिक संबंध',
        'label_architecture' => 'स्थापत्य आणि तटबंदी रचना',
        'label_major_attractions' => 'गडावरील / ठिकाणावरील प्रमुख आकर्षणे',
        'label_best_season' => 'भेट देण्यासाठी उत्तम काळ व ऋतू',
        'label_opening_hours' => 'वेळा व प्रवेश अवधी',
        'label_entry_fees' => 'प्रवेश शुल्क आणि नियम',
        'label_how_to_reach' => 'कसे पोहोचावे',
        'label_by_road' => 'रस्त्याने',
        'label_by_rail' => 'रेल्वेने',
        'label_by_air' => 'विमानाने',
        'label_distances' => 'प्रमुख शहरांपासूनचे अंतर',
        'label_from_pune' => 'पुण्यापासून',
        'label_from_mumbai' => 'मुंबईपासून',
        'label_facilities' => 'उपलब्ध सुविधा',
        'label_safety_tips' => 'सुरक्षा व ट्रेकिंग सूचना',
        'label_nearby' => 'परिसरातील इतर पर्यटन स्थळे',
        'label_reviews_ratings' => 'पर्यटकांचे अनुभव व अभिप्राय',
        'label_write_review' => 'तुमचा अनुभव नोंदवा',
        'verified_tag' => 'प्रमाणित अधिकृत वेळ',
        'unverified_tag' => 'स्थानिक हवामान आणि वनखात्याच्या नियमांवर अवलंबून',
        
        // Journey & Dashboard
        'journey_title' => 'माझी सह्याद्री भ्रमंती डायरी',
        'journey_subtitle' => 'भेट दिलेले किल्ले, वैयक्तिक प्रवासाच्या आठवणी आणि भविष्यातील भटकंतीचे नियोजन.',
        'tab_wishlist' => 'भेट देण्याची इच्छा (विशलिस्ट)',
        'tab_visited' => 'भेट दिलेली स्थळे',
        'tab_photos' => 'माझी प्रवास छायाचित्रे',
        'tab_badges' => 'दुर्गप्रेमी सन्मान बॅजेस',
        
        // Common
        'footer_about' => 'फोर्ट एक्सप्लोरर हे महाराष्ट्राचा समृद्ध इतिहास, छत्रपती शिवाजी महाराजांचे अजिंक्य किल्ले आणि सह्याद्रीच्या निसर्गसंपदेचा अभ्यास व संवर्धन करण्यासाठी समर्पित डिजिटल व्यासपीठ आहे.',
        'copyright' => 'सर्व हक्क राखीव. महाराष्ट्राच्या गौरवशाली इतिहासाला सादर समर्पित.'
    ]
];

/**
 * Return translated text by key
 */
function __(string $key, string $fallback = ''): string {
    $lang = current_lang();
    global $FE_TRANSLATIONS;
    
    if (isset($FE_TRANSLATIONS[$lang][$key])) {
        return $FE_TRANSLATIONS[$lang][$key];
    }
    if (isset($FE_TRANSLATIONS['en'][$key])) {
        return $FE_TRANSLATIONS['en'][$key];
    }
    return $fallback !== '' ? $fallback : $key;
}

/**
 * Return bilingual model field value based on active language
 */
function t_val(?string $enValue, ?string $mrValue): string {
    if (is_marathi() && !empty($mrValue)) {
        return $mrValue;
    }
    return $enValue ?? '';
}
