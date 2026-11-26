<?php
/**
 * FORT EXPLORER - AI Heritage Assistant API
 * Intelligent chatbot for Maharashtra forts, history, travel planning, and timings.
 * Supports Marathi, Hindi, and English with verified database retrieval and Gemini API support.
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$db = getDB();
if (!$db) {
    echo json_encode([
        'status' => 'error',
        'reply' => 'Database connection unavailable. Please check server configuration.'
    ]);
    exit;
}

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

$userQuery = trim($data['message'] ?? ($_POST['message'] ?? ''));
$lang = trim($data['lang'] ?? ($_POST['lang'] ?? 'en'));
$sessionId = trim($data['session_id'] ?? ($_POST['session_id'] ?? session_id() ?? 'anon_sess'));
$userId = $_SESSION['user_id'] ?? null;

if (empty($userQuery)) {
    echo json_encode([
        'status' => 'error',
        'reply' => 'Please ask a question about Maharashtra forts or destinations.'
    ]);
    exit;
}

// Auto-detect Devanagari script for Marathi / Hindi if not explicitly set
if (preg_match('/[\x{0900}-\x{097F}]/u', $userQuery)) {
    if ($lang === 'en') {
        // Simple heuristic: if query contains Marathi specific words like 'आहे', 'सांगा', 'किल्ला', 'कधी' -> mr
        if (preg_match('/(आहे|सांगा|किल्ला|कधी|कसा|गडावर|महाराज|स्वराज्य)/u', $userQuery)) {
            $lang = 'mr';
        } else {
            $lang = 'hi';
        }
    }
}

// Normalize search tokens
$tokens = preg_split('/[\s,\.\?!;:]+/u', mb_strtolower($userQuery, 'UTF-8'));
$cleanTokens = array_filter($tokens, function($t) { return mb_strlen($t, 'UTF-8') >= 3; });

$reply = '';
$matchedSnippet = null;
$relatedDestination = null;
$suggestions = [];

// 1. Search in AI Knowledge Snippets
$keywordsSql = [];
$params = [];
foreach ($cleanTokens as $token) {
    $keywordsSql[] = "(keywords LIKE ? OR question_pattern LIKE ?)";
    $params[] = "%{$token}%";
    $params[] = "%{$token}%";
}

if (!empty($keywordsSql)) {
    $sql = "
        SELECT * FROM ai_knowledge_snippets 
        WHERE " . implode(' OR ', $keywordsSql) . " 
        LIMIT 1
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $matchedSnippet = $stmt->fetch();
}

if ($matchedSnippet) {
    if ($lang === 'mr' && !empty($matchedSnippet['answer_mr'])) {
        $reply = $matchedSnippet['answer_mr'];
    } elseif ($lang === 'hi' && !empty($matchedSnippet['answer_hi'])) {
        $reply = $matchedSnippet['answer_hi'];
    } else {
        $reply = $matchedSnippet['answer_en'];
    }

    if (!empty($matchedSnippet['related_destination_id'])) {
        $dStmt = $db->prepare("SELECT id, name, name_mr, slug FROM destinations WHERE id = ?");
        $dStmt->execute([$matchedSnippet['related_destination_id']]);
        $relatedDestination = $dStmt->fetch();
    }
}

// 2. If no direct snippet, search Destinations Table (Name, Overview, History, Shivaji Connection)
if (empty($reply) && !empty($cleanTokens)) {
    $destMatches = [];
    foreach ($cleanTokens as $token) {
        $dSearch = $db->prepare("
            SELECT id, name, name_mr, slug, short_description, short_description_mr,
                   overview, historical_significance, historical_significance_mr,
                   shivaji_connection, shivaji_connection_mr, opening_hours, entry_fees,
                   best_season, difficulty_level, altitude_meters
            FROM destinations 
            WHERE name LIKE ? OR name_mr LIKE ? OR slug LIKE ?
            LIMIT 1
        ");
        $like = "%{$token}%";
        $dSearch->execute([$like, $like, $like]);
        $found = $dSearch->fetch();
        if ($found) {
            $destMatches = $found;
            break;
        }
    }

    if (!empty($destMatches)) {
        $relatedDestination = [
            'id' => $destMatches['id'],
            'name' => $destMatches['name'],
            'name_mr' => $destMatches['name_mr'],
            'slug' => $destMatches['slug']
        ];

        // Format intelligent response based on query intent
        $isTimingQuery = preg_match('/(time|timing|hour|open|close|ticket|fee|वेळ|तिकीट|शुल्क|समय)/i', $userQuery);
        $isSeasonQuery = preg_match('/(season|weather|month|when|visit|हवामान|ऋतू|कधी|मौसम)/i', $userQuery);
        $isHistoryQuery = preg_match('/(history|shivaji|story|battle|इतिहास|महाराज|लढा|कथा)/i', $userQuery);

        if ($lang === 'mr') {
            $dName = $destMatches['name_mr'] ?: $destMatches['name'];
            if ($isTimingQuery) {
                $reply = "{$dName} च्या वेळा व प्रवेश शुल्क:\n" .
                         "• वेळा: " . ($destMatches['opening_hours'] ?: 'सूर्योदय ते सूर्यास्त') . "\n" .
                         "• प्रवेश शुल्क: " . ($destMatches['entry_fees'] ?: 'मोफत प्रवेश') . "\n" .
                         "कृपया प्रवासापूर्वी स्थानिक हवामानाची खात्री करा.";
            } elseif ($isSeasonQuery) {
                $reply = "{$dName} ला भेट देण्यासाठी सर्वोत्तम काळ:\n" .
                         "• उत्तम ऋतू: " . ($destMatches['best_season'] ?: 'ऑक्टोबर ते फेब्रुवारी') . "\n" .
                         "• काठिण्य पातळी: " . $destMatches['difficulty_level'] . "\n" .
                         "• उंची: " . ($destMatches['altitude_meters'] ? $destMatches['altitude_meters'] . ' मीटर' : 'गिरिदुर्ग');
            } elseif ($isHistoryQuery && !empty($destMatches['shivaji_connection_mr'])) {
                $reply = "{$dName} आणि छत्रपती शिवाजी महाराज:\n" . $destMatches['shivaji_connection_mr'];
            } else {
                $reply = "{$dName} बद्दल माहिती:\n" . 
                         ($destMatches['short_description_mr'] ?: $destMatches['short_description']) . "\n\n" .
                         "• सर्वोत्तम काळ: " . $destMatches['best_season'] . "\n" .
                         "• काठिण्य पातळी: " . $destMatches['difficulty_level'];
            }
        } elseif ($lang === 'hi') {
            $dName = $destMatches['name'];
            if ($isTimingQuery) {
                $reply = "{$dName} के समय और प्रवेश शुल्क:\n" .
                         "• खुलने का समय: " . ($destMatches['opening_hours'] ?: 'सूर्योदय से सूर्यास्त') . "\n" .
                         "• प्रवेश शुल्क: " . ($destMatches['entry_fees'] ?: 'निःशुल्क प्रवेश');
            } elseif ($isSeasonQuery) {
                $reply = "{$dName} की यात्रा के लिए सबसे अच्छा समय:\n" .
                         "• सर्वोत्तम मौसम: " . ($destMatches['best_season'] ?: 'अक्टूबर से फरवरी') . "\n" .
                         "• चढ़ाई स्तर: " . $destMatches['difficulty_level'];
            } else {
                $reply = "{$dName} के बारे में ऐतिहासिक जानकारी:\n" .
                         $destMatches['short_description'] . "\n\n" .
                         "• सर्वोत्तम मौसम: " . $destMatches['best_season'] . "\n" .
                         "• ट्रेकिंग स्तर: " . $destMatches['difficulty_level'];
            }
        } else {
            // English default
            $dName = $destMatches['name'];
            if ($isTimingQuery) {
                $reply = "**{$dName} - Timings & Entry Permits**:\n" .
                         "• **Hours**: " . ($destMatches['opening_hours'] ?: 'Open 24 hours / Daylight trekking recommended') . "\n" .
                         "• **Entry Fees**: " . ($destMatches['entry_fees'] ?: 'Free entry') . "\n" .
                         "• **Trek Duration**: Approx. " . ($destMatches['trek_duration_hours'] ?? '2.5') . " hours.";
            } elseif ($isSeasonQuery) {
                $reply = "**{$dName} - Best Season & Trek Overview**:\n" .
                         "• **Best Season**: " . $destMatches['best_season'] . "\n" .
                         "• **Difficulty Level**: " . $destMatches['difficulty_level'] . "\n" .
                         "• **Altitude**: " . ($destMatches['altitude_meters'] ? $destMatches['altitude_meters'] . ' meters' : 'Hill Top') . ".";
            } elseif ($isHistoryQuery && !empty($destMatches['shivaji_connection'])) {
                $reply = "**Chhatrapati Shivaji Maharaj & {$dName}**:\n" . $destMatches['shivaji_connection'];
            } else {
                $reply = "**{$dName} Overview**:\n" . 
                         $destMatches['short_description'] . "\n\n" .
                         "• **Best Season**: " . $destMatches['best_season'] . "\n" .
                         "• **Difficulty**: " . $destMatches['difficulty_level'] . "\n" .
                         "• **Distance**: Approx. " . ($destMatches['distance_from_pune_km'] ? $destMatches['distance_from_pune_km'] . ' km from Pune' : 'Accessible via highway') . ".";
            }
        }
    }
}

// 3. Optional External Gemini API Fallback if configured
$geminiApiKey = defined('GEMINI_API_KEY') ? GEMINI_API_KEY : getenv('GEMINI_API_KEY');
if (empty($reply) && !empty($geminiApiKey)) {
    try {
        $ch = curl_init("https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . urlencode($geminiApiKey));
        $promptText = "You are the FortExplorer Heritage AI Assistant for Maharashtra Tourism. Answer factually, respectfully, and without inventing facts. Reply in language: {$lang}. User question: " . $userQuery;
        
        $payload = json_encode([
            'contents' => [
                ['parts' => [['text' => $promptText]]]
            ]
        ]);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 6);

        $apiRes = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $apiRes) {
            $json = json_decode($apiRes, true);
            $genText = $json['candidates'][0]['content']['parts'][0]['text'] ?? null;
            if ($genText) {
                $reply = trim($genText);
            }
        }
    } catch (Exception $ex) {
        // Fallback to default
    }
}

// 4. Default Fallback Knowledge Response if still empty
if (empty($reply)) {
    if ($lang === 'mr') {
        $reply = "मी महाराष्ट्राचे किल्ले, छत्रपती शिवाजी महाराजांचा इतिहास, प्रवास वेळा आणि पर्यटन माहितीबद्दल मार्गदर्शन करू शकतो. आपण किल्ले रायगड, शिवनेरी, सिंहगड, प्रतापगड किंवा इतर कोणत्याही ठिकाणाबद्दल विचारू शकता!";
        $suggestions = ["किल्ले रायगडाचा इतिहास", "शिवनेरीची वेळ", "सिंहगड सर्वोत्तम ऋतू", "प्रतापगडाचे महत्त्व"];
    } elseif ($lang === 'hi') {
        $reply = "मैं महाराष्ट्र के किलों, छत्रपति शिवाजी महाराज के इतिहास, यात्रा समय और मौसम के बारे में जानकारी दे सकता हूँ। आप रायगढ़, शिवनेरी, सिंहगढ़ या प्रतापगढ़ के बारे में पूछ सकते हैं!";
        $suggestions = ["रायगढ़ किला इतिहास", "शिवनेरी कब जाएं", "सिंहगढ़ का महत्व", "प्रतापगढ़ कैसे पहुंचें"];
    } else {
        $reply = "I can assist you with the history of Maharashtra forts, Chhatrapati Shivaji Maharaj's legacy, opening hours, best visiting seasons, trek difficulty, and route planning. Try asking about a specific fort like Raigad, Shivneri, Sinhagad, or Pratapgad!";
        $suggestions = ["History of Raigad Fort", "Best time to visit Sinhagad", "Shivneri Fort Birthplace", "How to reach Pratapgad"];
    }
} else {
    // Generate contextual suggestions
    if ($lang === 'mr') {
        $suggestions = ["इतर किल्ले दाखवा", "नकाशावर पहा", "सहल नियोजन कसे करावे?"];
    } elseif ($lang === 'hi') {
        $suggestions = ["अन्य किले देखें", "मानचित्र पर स्थान", "टूर प्लान कैसे बनाएं?"];
    } else {
        $suggestions = ["Explore other forts", "View on interactive map", "How to plan a trip?"];
    }
}

// 5. Log interaction to ai_chat_logs
try {
    $logStmt = $db->prepare("
        INSERT INTO ai_chat_logs (user_id, session_id, user_query, bot_reply, detected_lang)
        VALUES (?, ?, ?, ?, ?)
    ");
    $logStmt->execute([$userId, $sessionId, $userQuery, $reply, $lang]);
} catch (Exception $e) {
    // Ignore log fail
}

echo json_encode([
    'status' => 'success',
    'reply' => $reply,
    'language' => $lang,
    'related_destination' => $relatedDestination,
    'suggestions' => $suggestions,
    'timestamp' => date('H:i')
], JSON_UNESCAPED_UNICODE);
