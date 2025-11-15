/**
 * Service Worker - Controllo Domini v4.2.1
 *
 * PWA Features:
 * - Offline fallback
 * - Asset caching
 * - Network-first strategy for dynamic content
 * - Cache-first strategy for static assets
 *
 * @package ControlDomini
 * @version 4.2.1
 */

const CACHE_VERSION = 'controllo-domini-v4.2.1';
const STATIC_CACHE = `${CACHE_VERSION}-static`;
const DYNAMIC_CACHE = `${CACHE_VERSION}-dynamic`;
const MAX_DYNAMIC_CACHE_SIZE = 50;

// Static assets to cache on install
const STATIC_ASSETS = [
    '/',
    '/index.php',
    '/assets/css/style.css',
    '/assets/css/modern-ui.css',
    '/assets/css/minimal-professional.css',
    '/assets/css/enhancements.css',
    '/assets/js/logger.js',
    '/assets/js/main.js',
    '/assets/js/enhancements.js',
    '/offline.html',
    '/assets/images/logo.svg',
    '/assets/images/favicon-32x32.png',
    '/assets/images/favicon-16x16.png'
];

// Install event - cache static assets
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then(cache => {
                // Don't fail if some assets are missing
                return Promise.allSettled(
                    STATIC_ASSETS.map(url => {
                        return cache.add(url).catch(err => {
                            // Silent fail for missing assets
                        });
                    })
                );
            })
            .then(() => self.skipWaiting())
    );
});

// Activate event - clean up old caches
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys()
            .then(cacheNames => {
                return Promise.all(
                    cacheNames
                        .filter(name => name !== STATIC_CACHE && name !== DYNAMIC_CACHE)
                        .map(name => caches.delete(name))
                );
            })
            .then(() => self.clients.claim())
    );
});

// Fetch event - serve from cache with network fallback
self.addEventListener('fetch', event => {
    const { request } = event;
    const url = new URL(request.url);

    // Skip non-GET requests
    if (request.method !== 'GET') {
        return;
    }

    // Skip chrome extensions and external requests
    if (!url.origin.includes(self.location.origin)) {
        return;
    }

    // Skip API requests (handle separately)
    if (url.pathname.startsWith('/api/')) {
        event.respondWith(networkFirst(request));
        return;
    }

    // Static assets - cache first
    if (isStaticAsset(url.pathname)) {
        event.respondWith(cacheFirst(request));
        return;
    }

    // HTML pages - network first with cache fallback
    if (request.headers.get('accept').includes('text/html')) {
        event.respondWith(networkFirst(request));
        return;
    }

    // Default - cache first
    event.respondWith(cacheFirst(request));
});

/**
 * Cache-first strategy
 * Try cache first, then network, then offline fallback
 */
async function cacheFirst(request) {
    try {
        // Try cache
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            return cachedResponse;
        }

        // Try network
        const networkResponse = await fetch(request);

        // Cache successful responses
        if (networkResponse.ok) {
            const cache = await caches.open(DYNAMIC_CACHE);
            cache.put(request, networkResponse.clone());

            // Limit cache size
            limitCacheSize(DYNAMIC_CACHE, MAX_DYNAMIC_CACHE_SIZE);
        }

        return networkResponse;
    } catch (error) {
        // Return offline page for navigation requests
        if (request.headers.get('accept').includes('text/html')) {
            const offlinePage = await caches.match('/offline.html');
            if (offlinePage) return offlinePage;
        }

        throw error;
    }
}

/**
 * Network-first strategy
 * Try network first, then cache, then offline fallback
 */
async function networkFirst(request) {
    try {
        // Try network with timeout
        const networkResponse = await fetchWithTimeout(request, 5000);

        // Cache successful responses
        if (networkResponse.ok) {
            const cache = await caches.open(DYNAMIC_CACHE);
            cache.put(request, networkResponse.clone());
        }

        return networkResponse;
    } catch (error) {
        // Try cache
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            return cachedResponse;
        }

        // Return offline page for navigation requests
        if (request.headers.get('accept').includes('text/html')) {
            const offlinePage = await caches.match('/offline.html');
            if (offlinePage) return offlinePage;
        }

        throw error;
    }
}

/**
 * Fetch with timeout
 */
function fetchWithTimeout(request, timeout) {
    return Promise.race([
        fetch(request),
        new Promise((_, reject) =>
            setTimeout(() => reject(new Error('Request timeout')), timeout)
        )
    ]);
}

/**
 * Check if URL is a static asset
 */
function isStaticAsset(pathname) {
    const staticExtensions = ['.css', '.js', '.jpg', '.jpeg', '.png', '.gif', '.svg', '.webp', '.woff', '.woff2', '.ttf', '.eot'];
    return staticExtensions.some(ext => pathname.endsWith(ext));
}

/**
 * Limit cache size by removing oldest entries
 */
async function limitCacheSize(cacheName, maxSize) {
    const cache = await caches.open(cacheName);
    const keys = await cache.keys();

    if (keys.length > maxSize) {
        // Delete oldest entries
        const deleteCount = keys.length - maxSize;
        for (let i = 0; i < deleteCount; i++) {
            await cache.delete(keys[i]);
        }
    }
}

/**
 * Background sync for failed requests (if supported)
 */
if ('sync' in self.registration) {
    self.addEventListener('sync', event => {
        if (event.tag === 'sync-analytics') {
            event.waitUntil(syncAnalytics());
        }
    });
}

async function syncAnalytics() {
    // Sync any pending analytics data when online
    // Implementation would go here
}

/**
 * Push notifications (if supported)
 */
self.addEventListener('push', event => {
    if (!event.data) return;

    const data = event.data.json();
    const options = {
        body: data.body,
        icon: '/assets/images/icon-192.png',
        badge: '/assets/images/badge-72.png',
        vibrate: [200, 100, 200],
        data: {
            url: data.url || '/'
        }
    };

    event.waitUntil(
        self.registration.showNotification(data.title, options)
    );
});

// Handle notification clicks
self.addEventListener('notificationclick', event => {
    event.notification.close();

    event.waitUntil(
        clients.openWindow(event.notification.data.url)
    );
});

// Message handler for communication with main thread
self.addEventListener('message', event => {
    if (event.data.action === 'skipWaiting') {
        self.skipWaiting();
    }

    if (event.data.action === 'clearCache') {
        event.waitUntil(
            caches.keys().then(cacheNames => {
                return Promise.all(
                    cacheNames.map(name => caches.delete(name))
                );
            })
        );
    }
});

// Service Worker v4.2.1 loaded
