// KIA School System — minimal service worker.
//
// Scope: installability ("Add to Home Screen") + caching the static app
// shell (built CSS/JS, icons). Deliberately does NOT cache pages or any
// server data (attendance, invoices, notifications, etc.) — this app is
// inherently server-dependent, and serving a stale cached page while
// offline would look identical to a live one, misleading a parent into
// thinking outdated data is current. Navigation/data requests are always
// passed straight to the network.

const CACHE_NAME = 'kia-shell-v1';

const STATIC_PATTERNS = [
    /^\/build\/assets\//,
    /^\/icons\//,
    /^\/manifest\.json$/,
];

function isStaticAsset(url) {
    return STATIC_PATTERNS.some((pattern) => pattern.test(url.pathname));
}

self.addEventListener('install', (event) => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((names) =>
            Promise.all(
                names
                    .filter((name) => name !== CACHE_NAME)
                    .map((name) => caches.delete(name))
            )
        ).then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const url = new URL(event.request.url);

    // Only ever intercept same-origin, GET requests for known static assets.
    if (event.request.method !== 'GET' || url.origin !== self.location.origin || !isStaticAsset(url)) {
        return;
    }

    event.respondWith(
        caches.open(CACHE_NAME).then((cache) =>
            cache.match(event.request).then((cached) => {
                const network = fetch(event.request).then((response) => {
                    if (response.ok) {
                        cache.put(event.request, response.clone());
                    }
                    return response;
                }).catch(() => cached);

                // Stale-while-revalidate: serve the cached copy instantly if
                // we have one, refresh it in the background either way.
                return cached || network;
            })
        )
    );
});
