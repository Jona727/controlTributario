const CACHE_NAME = 'tributario-v1';
const STATIC_ASSETS = [
  './assets/css/app.css',
  './assets/js/app.js',
  './manifest.json',
  './assets/images/icon-192.png',
  './assets/images/icon-512.png'
];

// Install Event
self.addEventListener('install', (e) => {
  e.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      console.log('[Service Worker] Caching static assets');
      return cache.addAll(STATIC_ASSETS);
    }).then(() => self.skipWaiting())
  );
});

// Activate Event
self.addEventListener('activate', (e) => {
  e.waitUntil(
    caches.keys().then((keys) => {
      return Promise.all(
        keys.map((key) => {
          if (key !== CACHE_NAME) {
            console.log('[Service Worker] Removing old cache', key);
            return caches.delete(key);
          }
        })
      );
    }).then(() => self.clients.claim())
  );
});

// Fetch Event (Network-first with offline cache fallback for documents, Cache-first for static assets)
self.addEventListener('fetch', (e) => {
  const url = new URL(e.request.url);

  // Skip non-GET requests and external resources
  if (e.request.method !== 'GET') return;

  // Cache-first strategy for static assets
  const isStatic = url.pathname.includes('/assets/css/') || 
                   url.pathname.includes('/assets/js/') || 
                   url.pathname.includes('/assets/images/') || 
                   url.pathname.endsWith('manifest.json');

  if (isStatic) {
    e.respondWith(
      caches.match(e.request).then((cachedResponse) => {
        return cachedResponse || fetch(e.request).then((networkResponse) => {
          return caches.open(CACHE_NAME).then((cache) => {
            cache.put(e.request, networkResponse.clone());
            return networkResponse;
          });
        });
      })
    );
    return;
  }

  // Network-first strategy for pages and API requests to ensure real-time data
  e.respondWith(
    fetch(e.request)
      .then((networkResponse) => {
        // Cache the fetched page for offline usage if it is a successful response
        if (networkResponse.status === 200) {
          const responseClone = networkResponse.clone();
          caches.open(CACHE_NAME).then((cache) => {
            cache.put(e.request, responseClone);
          });
        }
        return networkResponse;
      })
      .catch(() => {
        // Fallback to cache when offline
        return caches.match(e.request);
      })
  );
});
