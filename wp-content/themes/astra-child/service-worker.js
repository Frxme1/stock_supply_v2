const CACHE_NAME = 'stock-supply-pwa-v1';

// Add URLs you want to cache here
const urlsToCache = [
  '/wp-content/themes/astra-child/manifest.json',
  // We will add css/mobile_app.css here once it's created
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        return cache.addAll(urlsToCache);
      })
  );
  self.skipWaiting();
});

self.addEventListener('activate', event => {
  const cacheWhitelist = [CACHE_NAME];
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheWhitelist.indexOf(cacheName) === -1) {
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
  self.clients.claim();
});

// Network First strategy
self.addEventListener('fetch', event => {
  if (event.request.mode === 'navigate') {
    event.respondWith(
      fetch(event.request).catch(() => {
        return caches.match(event.request);
      })
    );
  } else {
    // Stale-While-Revalidate for other requests like CSS/JS
    event.respondWith(
      caches.match(event.request).then(cachedResponse => {
        const fetchPromise = fetch(event.request).then(networkResponse => {
          caches.open(CACHE_NAME).then(cache => {
            if (event.request.url.startsWith('http')) {
              cache.put(event.request, networkResponse.clone());
            }
          });
          return networkResponse;
        }).catch(() => {
          // Ignore errors
        });
        return cachedResponse || fetchPromise;
      })
    );
  }
});
