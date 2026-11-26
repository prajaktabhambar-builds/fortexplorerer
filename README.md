# FORT EXPLORER (फोर्ट एक्सप्लोरर)
### *Discover History. Explore Heritage. Create Memories.*

A full-stack digital tourism and heritage exploration platform dedicated to celebrating, mapping, and preserving the historical monuments and hill forts of Maharashtra, with a deep focus on the sovereign legacy of **Chhatrapati Shivaji Maharaj** and the natural wonders of the Sahyadri Western Ghats.

---

## 🌟 Advanced Platform Upgrades & Features

### 1. 🤖 AI Heritage Assistant
- Floating interactive chatbot accessible on every page (`assets/js/ai-assistant.js`, `api/ai-chat.php`).
- Trilingual understanding and responses: **English**, **मराठी (Marathi)**, and **हिंदी (Hindi)**.
- Authentic Maratha history knowledge base with exact fortress facts, architecture notes, and safety tips.
- Speech-to-Text (Voice input via Web Speech API) and Text-to-Speech (audio spoken answers).
- Optional Gemini API integration with local knowledge fallback.
- Full chat logging in `ai_chat_logs`.

### 2. 🗺️ Smart Travel Route Planner & TSP Optimizer
- Dedicated route optimization tool (`trip-planner.php`, `assets/js/trip-planner.js`).
- Select starting base city (Pune, Mumbai, Kolhapur, Nashik, etc.).
- **Smart Optimize Route** button uses Traveling Salesperson (TSP) Nearest-Neighbor algorithm to sequence stops for minimal road distance.
- Realistic Sahyadri driving time estimation (accounts for mountain road curvature) plus trek ascent time.
- Shareable itineraries: generates a unique `share_token` (viewable via `trip-planner.php?share=...` and one-click WhatsApp sharing).

### 3. 📱 On-Site QR Code Heritage Pass
- Dynamic QR code generation for every fort and monument (`qr-guide.php?slug=...`).
- Download and Print QR Card options on every destination details page.
- Instant access to field history, important architectural structures, safety alerts, and audio guide upon scanning.

### 4. 🎧 Multilingual Audio Guide Player
- Embedded audio guide on destination pages and mobile QR guides.
- Narration in Marathi, Hindi, and English with real SpeechSynthesis synthesis.
- Audio player controls: Play, Pause, Resume, Speed (0.8x, 1.0x, 1.2x), seekable progress bar, time tracking, and accordion transcript.

### 5. 🏆 Gamification & Explorer Achievements
- 7 unlocked milestone badges stored in database (`achievements`, `user_achievements`):
  - *Sahyadri Wanderer* (First visit recorded)
  - *Fort Trekker* (3+ forts)
  - *5 Forts Conquered* (5 forts)
  - *10 Forts Master* (10 forts)
  - *Sahyadri Chhatrapati* (20 forts)
  - *Heritage Explorer* (5+ total destinations)
  - *Maharashtra Explorer* (Destinations across 3+ districts)
- Real-time progress bars and auto-award trigger upon recording visits (`api/mark-visited.php`).

### 6. 👥 Sahyadri Travel Community
- Community feed (`community.php`) for personal travel stories, photo shares, and trek advice.
- Detailed post view (`community-post.php`) with comments thread, instant AJAX likes, and follow system (`user_follows`).
- Content moderation: Report inappropriate posts and comments (`community_reports`).
- Administrator moderation console (`admin/community.php`) to approve, hide, or delete posts.

### 7. 💰 Interactive Trip Budget Calculator
- Comprehensive expense estimator (`budget-calculator.php`).
- Computes fuel costs (Car, Motorcycle), MSRTC bus fares, hired cabs, accommodation (Homestay, Resort, Camping, Free Mandap), meals, and guide charges.
- Transparent distinction between **verified official ASI ticket prices** and dynamic local estimates.
- Traveler count slider (1 to 15) with group total and per-person cost breakdown.

### 8. ⛅ Live Weather & Mountain Travel Advisories
- Real-time weather integration with Open-Meteo API (`includes/weather.php`).
- Temperature, rain probability, wind speed, and weather condition display.
- Automated Sahyadri safety alerts: slippery basalt warnings during monsoon rain, extreme heat warnings, and lightning advisories.

### 9. ⚖️ Destination Comparison Matrix
- Side-by-side comparison tool (`compare.php`).
- Compares up to 4 destinations across: Classification, Trek Difficulty, Altitude, Historical Significance, Best Season, Travel Distances, Verified Timings, Entry Fees, and Facilities.

### 10. 📖 Personal Travel Diary & Summary Certificate
- Chronological travel journal with year/month timeline in `my-journey.php`.
- Upload personal photos, record companions, ratings, and memories.
- Official printable and downloadable **Sahyadri Explorer Certificate & Record** (`travel-summary.php`).

### 11. 🏨 Nearby Accommodations & POIs
- Displays nearby hotels, restaurants, viewpoints, and emergency medical hospitals on destination pages (`nearby_places` table).

### 12. ⚡ Offline Mode & Progressive Web App (PWA)
- Installable PWA with `manifest.json` and theme color `#163820`.
- Service worker (`sw.js`) with cache-first strategy for static assets and network-first with offline fallback (`offline.html`).
- Online/offline connectivity banner.

### 13. 🧗 Sahyadri Trekking Safety Guide
- Comprehensive safety portal (`trekking-guide.php`).
- Seasonal protocols (Monsoon, Winter, Summer).
- Interactive gear packing checklist.
- Emergency rescue directory (MMRCC: 7620 230 230, Shivdurga Mitra, 112, Snakebite protocol).
- Fort Code of Conduct (zero plastic, zero alcohol, reverence for memorials).

### 14. ⭐ Enhanced Reviews & Helpful Votes
- Photo uploads with visitor reviews.
- "Was this review helpful?" voting mechanism (`api/review-helpful.php`).
- Report abusive or misleading reviews for admin review.

### 15. 📊 Admin Advanced Analytics Dashboard
- Interactive charts powered by Chart.js (`admin/analytics.php`).
- Most visited forts, top wishlisted destinations, category distribution, district tourism spread, review rating breakdown, and AI query trends.

### 16. 🏰 Complete Maharashtra Fort Directory (367+ Verified Forts across all 36 Districts)
- **All 36 Districts of Maharashtra Fully Cataloged**:
  Every single district is historically represented with verified coordinates, altitude, talukas, base villages, and dynasties (Pune, Raigad, Satara, Kolhapur, Sindhudurg, Ratnagiri, Nashik, Ahilyanagar, Thane, Palghar, Mumbai City, Mumbai Suburban, Dhule, Jalgaon, Nandurbar, Chhatrapati Sambhajinagar, Jalna, Beed, Latur, Dharashiv, Nanded, Parbhani, Hingoli, Akola, Amravati, Buldhana, Washim, Yavatmal, Nagpur, Wardha, Bhandara, Gondia, Chandrapur, Gadchiroli, Sangli, Solapur).
- **7 Traditional Fort Classifications**:
  1. *Giridurg* (Hill Forts - सह्याद्रीच्या उत्तुंग कड्यांवरील किल्ले)
  2. *Jaldurg* (Sea Forts - अरबी समुद्रातील अभेद्य नौदल किल्ले)
  3. *Island Forts* (बेटांवरील जलदुर्ग जसे खांदेरी, पद्मदुर्ग)
  4. *Bhuikot* (Land Forts - खंदक व भव्य बुरुज असलेले भुईकोट किल्ले)
  5. *Vanadurg* (Forest Forts - दाट अरण्य व खोल दर्‍यांमधील वनदुर्ग)
  6. *Coastal Forts* (किनारी किल्ले - खाडीची मुखे व सागरी बंदरांचे रक्षक)
  7. *Gadhi / Kot* (Fortified Heritage Structures - ऐतिहासिक गढ्या व तटबंदीयुक्त वाडे)
- **11 UNESCO Serial Nomination Components**:
  Prominently features and badges the 11 components of the *Maratha Military Landscapes of India* serial nomination in Maharashtra (Salher, Shivneri, Lohagad, Khanderi, Raigad, Rajgad, Pratapgad, Suvarnadurg, Panhala, Vijaydurg, Sindhudurg).
- **Archival Reference Citations**:
  Every fort record includes official reference citations from ASI Surveys, Maharashtra State Gazetteers, MTDC Archives, and UNESCO Dossiers (`fort_references` table).
- **Graceful Fallback**:
  Displays *"Information being collected - Historical research and archival documentation in progress for this district."* when a filter yields no matching records.
- **Admin Management & CSV Automation**:
  - `admin/forts.php`: Master directory management table with district/category filters, search, quick verification toggles, and pagination.
  - `admin/fort-form.php`: Comprehensive 27-field edit/add form synchronizing both `forts` and `destinations` tables.
  - `admin/fort-import-export.php`: Bulk CSV Export with UTF-8 BOM encoding for Marathi Devnagari script in Microsoft Excel and Google Sheets, plus duplicate-safe CSV Import.
  - `map.php`: Interactive Leaflet + OpenStreetMap explorer with district select, category pills, custom category icons, search, and live directions.

---

## 🛠️ Technology Stack

| Layer | Technologies Used |
| :--- | :--- |
| **Frontend** | HTML5, CSS3, JavaScript (ES6+), Bootstrap 5.3.2, Font Awesome 6.5.1, Chart.js 4.4.1 |
| **Mapping Engine** | Leaflet.js 1.9.4 with OpenStreetMap Tiles |
| **Voice & Audio** | Web Speech API (SpeechRecognition & SpeechSynthesis in EN, MR, HI) |
| **Weather** | Open-Meteo API (Free, Real-Time Forecast) |
| **PWA** | Web App Manifest + Service Worker Cache API |
| **Backend** | PHP 8.0+ (Modular, Object-Oriented, PDO Prepared Statements, CSRF Guards) |
| **Database** | MariaDB 10.4+ / MySQL 8.0+ (24 Normalized Tables with Foreign Keys) |
| **Local Server** | XAMPP (Port 3307 & 3306 auto-fallback) |

---

## 🚀 Running the Project Locally

### 1. Database Connection
- Ensure MariaDB / MySQL is running. `config/database.php` automatically tests and connects to port **`3307`** (XAMPP MariaDB instance) with fallback to port **`3306`**.
- Database: `fort_explorer`
- User: `root`
- Password: `""` (empty)

### 2. Start Web Server
You can run using XAMPP Apache or the PHP built-in server:
```bash
php -S 127.0.0.1:8000 -t d:\Project\FortExplorer
```

### 3. Open in Browser
Visit: **`http://127.0.0.1:8000`**

### 4. Demo Login Credentials
- **Administrator Panel**: `http://127.0.0.1:8000/admin/`
  - Email: `admin@fortexplorer.com`
  - Password: `Admin@FortExplorer2026!`
- **Registered Explorer Account**:
  - Email: `explorer@fortexplorer.com`
  - Password: `Explorer@2026!`

---

## 🏛️ Authentic Maratha Heritage Preservation
All historical entries regarding Chhatrapati Shivaji Maharaj, the Maratha Empire, Swarajya forts, treaties, and battles are verified against authentic Maratha historical chronicles (Bakhars) and reputable historical archives.

*Jai Bhavani, Jai Shivaji! 🚩*
