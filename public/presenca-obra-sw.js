const CACHE_NAME = 'omega286-presenca-obra-v1';
const OFFLINE_URLS = [
    '/presenca-obra/identificar',
    '/presenca-obra/modo-offline',
    '/medicao/presenca-obra',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.addAll(OFFLINE_URLS)).then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim());
});

self.addEventListener('fetch', (event) => {
    const { request } = event;

    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    if (!OFFLINE_URLS.includes(url.pathname)) {
        return;
    }

    event.respondWith(
        caches.open(CACHE_NAME).then(async (cache) => {
            try {
                const response = await fetch(request);
                if (response.ok) {
                    cache.put(request, response.clone());
                }

                return response;
            } catch {
                const cached = await cache.match(request);
                if (cached) {
                    return cached;
                }

                throw new Error('offline');
            }
        })
    );
});
