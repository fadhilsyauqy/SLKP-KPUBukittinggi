/**
 * Service Worker untuk SLKP KPU Kota Bukittinggi (PWA)
 * Versi Cache: slkp-kpu-v1.0.0
 */

const CACHE_NAME = 'slkp-kpu-cache-v1.0.0';
const STATIC_ASSETS = [
    '/',
    '/manifest.json',
    '/assets/css/app-layout.css',
    '/assets/img/logo-kpu.png',
    '/assets/img/logo-kpu.svg'
];

// Event Install: Pre-cache static assets
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(STATIC_ASSETS).catch((err) => {
                console.warn('PWA Pre-cache notice:', err);
            });
        }).then(() => self.skipWaiting())
    );
});

// Event Activate: Clean up old caches
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((name) => {
                    if (name !== CACHE_NAME) {
                        return caches.delete(name);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

// Event Fetch: Network first for navigation, cache first / stale-while-revalidate for static assets
self.addEventListener('fetch', (event) => {
    const request = event.request;

    // Hanya tangani HTTP / HTTPS GET requests
    if (request.method !== 'GET' || !request.url.startsWith('http')) {
        return;
    }

    // Untuk navigasi dokumen (HTML) gunakan Network-First dengan fallback cache
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request)
                .then((response) => {
                    if (response && response.status === 200) {
                        const copy = response.clone();
                        caches.open(CACHE_NAME).then((cache) => cache.put(request, copy));
                    }
                    return response;
                })
                .catch(() => {
                    return caches.match(request).then((cachedResponse) => {
                        return cachedResponse || caches.match('/');
                    });
                })
        );
        return;
    }

    // Untuk file statis (CSS, JS, Gambar, Font) gunakan Cache-First dengan network fallback
    event.respondWith(
        caches.match(request).then((cachedResponse) => {
            if (cachedResponse) {
                // Background refresh (stale-while-revalidate)
                fetch(request).then((networkResponse) => {
                    if (networkResponse && networkResponse.status === 200) {
                        caches.open(CACHE_NAME).then((cache) => cache.put(request, networkResponse));
                    }
                }).catch(() => {});
                return cachedResponse;
            }

            return fetch(request).then((networkResponse) => {
                if (networkResponse && networkResponse.status === 200) {
                    const copy = networkResponse.clone();
                    caches.open(CACHE_NAME).then((cache) => cache.put(request, copy));
                }
                return networkResponse;
            });
        })
    );
});
