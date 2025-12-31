/**
 * MYAVANA Service Worker
 * PWA functionality, offline caching, background sync
 *
 * @package Myavana_Hair_Journey
 * @version 1.0.0
 */

const CACHE_VERSION = 'myavana-v1.0.0';
const CACHE_STATIC = 'myavana-static-v1';
const CACHE_DYNAMIC = 'myavana-dynamic-v1';
const CACHE_IMAGES = 'myavana-images-v1';

// Assets to cache immediately on install
const STATIC_ASSETS = [
    '/',
    '/hair-journey/',
    '/community/',
    '/profile/',
    '/wp-content/plugins/myavana-hair-journey/assets/css/myavana-styles.css',
    '/wp-content/plugins/myavana-hair-journey/assets/css/myavana-responsive-fixes.css',
    '/wp-content/plugins/myavana-hair-journey/assets/css/myavana-mobile-components.css',
    '/wp-content/plugins/myavana-hair-journey/assets/js/myavana-unified-core.js',
    '/wp-content/plugins/myavana-hair-journey/assets/js/myavana-mobile-nav.js',
    '/wp-content/plugins/myavana-hair-journey/assets/js/myavana-gestures.js',
    '/wp-content/plugins/myavana-hair-journey/assets/js/myavana-cache.js',
    '/wp-content/plugins/myavana-hair-journey/manifest.json'
];

// Install event - cache static assets
self.addEventListener('install', (event) => {
    console.log('[Service Worker] Installing...');

    event.waitUntil(
        caches.open(CACHE_STATIC)
            .then((cache) => {
                console.log('[Service Worker] Caching static assets');
                return cache.addAll(STATIC_ASSETS.map(url => new Request(url, { cache: 'reload' })));
            })
            .then(() => {
                console.log('[Service Worker] Skip waiting');
                return self.skipWaiting();
            })
            .catch((error) => {
                console.error('[Service Worker] Installation failed:', error);
            })
    );
});

// Activate event - clean up old caches
self.addEventListener('activate', (event) => {
    console.log('[Service Worker] Activating...');

    event.waitUntil(
        caches.keys()
            .then((cacheNames) => {
                return Promise.all(
                    cacheNames.map((cacheName) => {
                        if (cacheName !== CACHE_STATIC &&
                            cacheName !== CACHE_DYNAMIC &&
                            cacheName !== CACHE_IMAGES) {
                            console.log('[Service Worker] Deleting old cache:', cacheName);
                            return caches.delete(cacheName);
                        }
                    })
                );
            })
            .then(() => {
                console.log('[Service Worker] Claiming clients');
                return self.clients.claim();
            })
    );
});

// Fetch event - serve from cache, fallback to network
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    // Skip cross-origin requests
    if (url.origin !== location.origin) {
        return;
    }

    // Skip admin and API requests
    if (url.pathname.includes('/wp-admin') ||
        url.pathname.includes('/wp-login') ||
        url.pathname.includes('admin-ajax.php')) {
        return;
    }

    // Handle different request types
    if (request.destination === 'image') {
        event.respondWith(handleImageRequest(request));
    } else if (request.destination === 'document') {
        event.respondWith(handleDocumentRequest(request));
    } else if (request.destination === 'script' || request.destination === 'style') {
        event.respondWith(handleAssetRequest(request));
    } else {
        event.respondWith(handleGenericRequest(request));
    }
});

/**
 * Handle image requests
 * Cache-first strategy with fallback to network
 */
async function handleImageRequest(request) {
    try {
        // Try cache first
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            return cachedResponse;
        }

        // Fetch from network
        const networkResponse = await fetch(request);

        // Cache successful responses
        if (networkResponse && networkResponse.status === 200) {
            const cache = await caches.open(CACHE_IMAGES);
            cache.put(request, networkResponse.clone());
        }

        return networkResponse;
    } catch (error) {
        console.error('[Service Worker] Image request failed:', error);

        // Return placeholder image
        return new Response(
            '<svg width="400" height="300" xmlns="http://www.w3.org/2000/svg"><rect fill="#f0f0f0" width="400" height="300"/><text x="50%" y="50%" text-anchor="middle" fill="#999">Image unavailable</text></svg>',
            { headers: { 'Content-Type': 'image/svg+xml' } }
        );
    }
}

/**
 * Handle document (HTML) requests
 * Network-first strategy with cache fallback
 */
async function handleDocumentRequest(request) {
    try {
        // Try network first
        const networkResponse = await fetch(request);

        // Cache successful responses
        if (networkResponse && networkResponse.status === 200) {
            const cache = await caches.open(CACHE_DYNAMIC);
            cache.put(request, networkResponse.clone());
        }

        return networkResponse;
    } catch (error) {
        console.error('[Service Worker] Document request failed, trying cache');

        // Fallback to cache
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            return cachedResponse;
        }

        // Return offline page
        return caches.match('/offline.html').then(response => {
            if (response) {
                return response;
            }

            // If offline page not available, return basic HTML
            return new Response(
                `<!DOCTYPE html>
                <html>
                <head>
                    <title>Offline - MYAVANA</title>
                    <meta name="viewport" content="width=device-width, initial-scale=1">
                    <style>
                        body {
                            font-family: 'Archivo', sans-serif;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            height: 100vh;
                            margin: 0;
                            background: #f7f7f7;
                        }
                        .offline-message {
                            text-align: center;
                            padding: 2rem;
                        }
                        .offline-icon {
                            font-size: 4rem;
                            margin-bottom: 1rem;
                        }
                        h1 {
                            color: #1a1a1a;
                            margin-bottom: 0.5rem;
                        }
                        p {
                            color: #6c757d;
                        }
                        button {
                            background: #FF6B6B;
                            color: white;
                            border: none;
                            padding: 0.75rem 1.5rem;
                            border-radius: 8px;
                            font-size: 1rem;
                            cursor: pointer;
                            margin-top: 1rem;
                        }
                    </style>
                </head>
                <body>
                    <div class="offline-message">
                        <div class="offline-icon">📵</div>
                        <h1>You're Offline</h1>
                        <p>Please check your internet connection</p>
                        <button onclick="location.reload()">Try Again</button>
                    </div>
                </body>
                </html>`,
                {
                    headers: {
                        'Content-Type': 'text/html',
                        'Cache-Control': 'no-cache'
                    }
                }
            );
        });
    }
}

/**
 * Handle asset (JS/CSS) requests
 * Cache-first with network fallback
 */
async function handleAssetRequest(request) {
    try {
        // Try cache first
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            return cachedResponse;
        }

        // Fetch from network
        const networkResponse = await fetch(request);

        // Cache successful responses
        if (networkResponse && networkResponse.status === 200) {
            const cache = await caches.open(CACHE_STATIC);
            cache.put(request, networkResponse.clone());
        }

        return networkResponse;
    } catch (error) {
        console.error('[Service Worker] Asset request failed:', error);

        // Return empty response for failed assets
        return new Response('', {
            status: 408,
            statusText: 'Request Timeout'
        });
    }
}

/**
 * Handle generic requests
 * Network-first with cache fallback
 */
async function handleGenericRequest(request) {
    try {
        const networkResponse = await fetch(request);

        if (networkResponse && networkResponse.status === 200) {
            const cache = await caches.open(CACHE_DYNAMIC);
            cache.put(request, networkResponse.clone());
        }

        return networkResponse;
    } catch (error) {
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            return cachedResponse;
        }

        return new Response('Network error', {
            status: 408,
            headers: { 'Content-Type': 'text/plain' }
        });
    }
}

/**
 * Background Sync - Queue failed requests
 */
self.addEventListener('sync', (event) => {
    console.log('[Service Worker] Background sync:', event.tag);

    if (event.tag === 'sync-entries') {
        event.waitUntil(syncEntries());
    } else if (event.tag === 'sync-analytics') {
        event.waitUntil(syncAnalytics());
    }
});

/**
 * Sync queued entries
 */
async function syncEntries() {
    try {
        // Get queued entries from IndexedDB
        const db = await openDatabase();
        const tx = db.transaction(['entries'], 'readonly');
        const store = tx.objectStore('entries');
        const entries = await store.getAll();

        // Sync each entry
        for (const entry of entries) {
            try {
                const response = await fetch('/wp-admin/admin-ajax.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'myavana_create_entry',
                        ...entry
                    })
                });

                if (response.ok) {
                    // Remove from queue
                    const deleteTx = db.transaction(['entries'], 'readwrite');
                    const deleteStore = deleteTx.objectStore('entries');
                    await deleteStore.delete(entry.id);
                }
            } catch (error) {
                console.error('[Service Worker] Failed to sync entry:', error);
            }
        }

        console.log('[Service Worker] Entries synced');
    } catch (error) {
        console.error('[Service Worker] Sync entries failed:', error);
    }
}

/**
 * Sync analytics data
 */
async function syncAnalytics() {
    try {
        const db = await openDatabase();
        const tx = db.transaction(['analytics'], 'readonly');
        const store = tx.objectStore('analytics');
        const analytics = await store.getAll();

        // Batch sync analytics
        if (analytics.length > 0) {
            await fetch('/wp-admin/admin-ajax.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'myavana_sync_analytics',
                    data: analytics
                })
            });

            // Clear synced analytics
            const clearTx = db.transaction(['analytics'], 'readwrite');
            const clearStore = clearTx.objectStore('analytics');
            await clearStore.clear();
        }

        console.log('[Service Worker] Analytics synced');
    } catch (error) {
        console.error('[Service Worker] Sync analytics failed:', error);
    }
}

/**
 * Open IndexedDB
 */
function openDatabase() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('MyavanaDB', 1);

        request.onerror = () => reject(request.error);
        request.onsuccess = () => resolve(request.result);

        request.onupgradeneeded = (event) => {
            const db = event.target.result;

            if (!db.objectStoreNames.contains('entries')) {
                db.createObjectStore('entries', { keyPath: 'id', autoIncrement: true });
            }

            if (!db.objectStoreNames.contains('analytics')) {
                db.createObjectStore('analytics', { keyPath: 'id', autoIncrement: true });
            }
        };
    });
}

/**
 * Push notification event
 */
self.addEventListener('push', (event) => {
    console.log('[Service Worker] Push received');

    let notificationData = {
        title: 'MYAVANA',
        body: 'You have a new notification',
        icon: '/wp-content/plugins/myavana-hair-journey/assets/images/icon-192x192.png',
        badge: '/wp-content/plugins/myavana-hair-journey/assets/images/badge-72x72.png',
        tag: 'myavana-notification',
        requireInteraction: false
    };

    if (event.data) {
        try {
            const data = event.data.json();
            notificationData = { ...notificationData, ...data };
        } catch (e) {
            notificationData.body = event.data.text();
        }
    }

    event.waitUntil(
        self.registration.showNotification(notificationData.title, notificationData)
    );
});

/**
 * Notification click event
 */
self.addEventListener('notificationclick', (event) => {
    console.log('[Service Worker] Notification clicked');

    event.notification.close();

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true })
            .then((clientList) => {
                // Check if app is already open
                for (const client of clientList) {
                    if (client.url === '/' && 'focus' in client) {
                        return client.focus();
                    }
                }

                // Open new window if not open
                if (clients.openWindow) {
                    return clients.openWindow('/');
                }
            })
    );
});

/**
 * Message event - communication with client
 */
self.addEventListener('message', (event) => {
    console.log('[Service Worker] Message received:', event.data);

    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }

    if (event.data && event.data.type === 'CLEAR_CACHE') {
        event.waitUntil(
            caches.keys().then((cacheNames) => {
                return Promise.all(
                    cacheNames.map((cacheName) => caches.delete(cacheName))
                );
            })
        );
    }

    if (event.data && event.data.type === 'GET_VERSION') {
        event.ports[0].postMessage({ version: CACHE_VERSION });
    }
});

console.log('[Service Worker] Loaded - Version:', CACHE_VERSION);
