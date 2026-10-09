/*
 * Service worker for the installable app.
 * - Pages always come from the network (they hold personal orders, invoices and prices) and are
 *   never cached; when offline, the offline screen is shown instead of a browser error.
 * - Static files (styles, scripts, fonts, icons, product thumbnails) are cached and refreshed in
 *   the background, so the app opens quickly.
 * - Anything that is not a GET (forms, payments, status changes) goes straight to the server.
 * Bump VERSION when this file's caching rules change.
 */
const VERSION = 'v1';
const STATIC_CACHE = 'dcf-static-' + VERSION;
const OFFLINE_URL = 'offline.html';
const PRECACHE = [
    OFFLINE_URL,
    'assets/icons/icon-192.png',
    'assets/icons/icon-512.png',
    'assets/vendor/bootstrap/css/bootstrap.min.css',
    'assets/vendor/bootstrap/js/bootstrap.bundle.min.js',
    'assets/vendor/bootstrap-icons/bootstrap-icons.min.css',
];

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(STATIC_CACHE).then((cache) => cache.addAll(PRECACHE)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((key) => key.startsWith('dcf-') && key !== STATIC_CACHE).map((key) => caches.delete(key))))
            .then(() => self.clients.claim())
    );
});

const isStaticAsset = (url) => url.pathname.includes('/assets/') || url.pathname.includes('/uploads/thumbs/');

self.addEventListener('fetch', (event) => {
    const request = event.request;
    if (request.method !== 'GET') {
        return;
    }
    const url = new URL(request.url);
    if (url.origin !== self.location.origin) {
        return;
    }

    if (request.mode === 'navigate') {
        event.respondWith(fetch(request).catch(() => caches.match(OFFLINE_URL)));
        return;
    }

    if (isStaticAsset(url)) {
        // Stale-while-revalidate: answer from cache at once, refresh it in the background.
        event.respondWith(
            caches.open(STATIC_CACHE).then(async (cache) => {
                const cached = await cache.match(request);
                const network = fetch(request)
                    .then((response) => {
                        if (response.ok) {
                            cache.put(request, response.clone());
                        }
                        return response;
                    })
                    .catch(() => cached);
                return cached || network;
            })
        );
    }
});
