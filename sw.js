const CACHE_NAME = 'fortexplorer-v1.2';
const CORE_ASSETS = [
  '/',
  '/index.php',
  '/explore.php',
  '/forts.php',
  '/history.php',
  '/assets/css/style.css',
  '/assets/js/main.js',
  '/assets/js/ai-assistant.js',
  '/assets/images/logo.svg',
  '/assets/images/default-fort.svg',
  '/offline.html'
];

// Install Service Worker & Precache Shell Assets
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      console.log('[SW] Precaching essential FortExplorer app shell');
      return cache.addAll(CORE_ASSETS).catch(err => console.log('[SW] Precache asset skipped:', err));
    }).then(() => self.skipWaiting())
  );
});

// Activate & Cleanup Old Caches
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keyList) => {
      return Promise.all(
        keyList.map((key) => {
          if (key !== CACHE_NAME) {
            console.log('[SW] Removing old cache version', key);
            return caches.delete(key);
          }
        })
      );
    }).then(() => self.clients.claim())
  );
});

// Fetch Strategy: Network First for PHP / HTML, Cache First for Static Assets
self.addEventListener('fetch', (event) => {
  const req = event.request;
  const url = new URL(req.url);

  // Avoid caching non-GET requests or admin/api mutations
  if (req.method !== 'GET') return;
  if (url.pathname.includes('/admin/') || url.pathname.includes('/api/toggle') || url.pathname.includes('/api/mark')) return;

  // Static Assets: Cache First with Network Fallback
  if (req.destination === 'style' || req.destination === 'script' || req.destination === 'image' || req.destination === 'font') {
    event.respondWith(
      caches.match(req).then((cached) => {
        return cached || fetch(req).then((response) => {
          if (response.status === 200) {
            const clone = response.clone();
            caches.open(CACHE_NAME).then((cache) => cache.put(req, clone));
          }
          return response;
        });
      })
    );
    return;
  }

  // HTML Pages: Network First with Cache & Offline Fallback
  event.respondWith(
    fetch(req).then((networkRes) => {
      if (networkRes.status === 200) {
        const clone = networkRes.clone();
        caches.open(CACHE_NAME).then((cache) => cache.put(req, clone));
      }
      return networkRes;
    }).catch(() => {
      return caches.match(req).then((cached) => {
        return cached || caches.match('/offline.html');
      });
    })
  );
});
