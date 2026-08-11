// FlexiRide Service Worker - Offline Caching & Web Push Infrastructure
const CACHE_NAME = 'flexiride-v2.0';
const ASSETS_TO_CACHE = [
  '/',
  '/assets/js/theme.js',
  'https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap',
  'https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css'
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(ASSETS_TO_CACHE);
    }).then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => {
      return Promise.all(
        keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))
      );
    }).then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  if (event.request.method !== 'GET') return;
  event.respondWith(
    fetch(event.request).catch(() => caches.match(event.request))
  );
});

// Web Push Event Handler
self.addEventListener('push', (event) => {
  const data = event.data ? event.data.json() : { title: 'FlexiRide Alert', body: 'You have a new ride update!' };
  const options = {
    body: data.body,
    icon: '/assets/images/logo1.png',
    badge: '/assets/images/favvi.png',
    vibrate: [200, 100, 200]
  };
  event.waitUntil(self.registration.showNotification(data.title, options));
});
