/**
 * MYAVANA Service Worker
 * Provides offline functionality and caching for Progressive Web App features
 */

const CACHE_NAME = 'myavana-v1.0.0';
const OFFLINE_URL = '/offline.html';

// Assets to cache for offline functionality
const CACHE_ASSETS = [
    '/wp-content/plugins/myavana-hair-journey/assets/css/myavana-styles.css',
    '/wp-content/plugins/myavana-hair-journey/assets/js/myavana-unified-core.js',
    '/wp-content/plugins/myavana-hair-journey/assets/js/myavana-scripts.js',
    '/wp-content/plugins/myavana-hair-journey/assets/fonts/archivo-black.woff2',
    '/wp-content/plugins/myavana-hair-journey/assets/fonts/archivo-regular.woff2',
    '/wp-content/plugins/myavana-hair-journey/assets/images/myavana-logo.svg',
    OFFLINE_URL
];

// Critical API endpoints to cache
const API_CACHE_PATTERNS = [
    '/wp-admin/admin-ajax.php?action=myavana_get_user_profile',
    '/wp-admin/admin-ajax.php?action=myavana_get_dashboard_data',
    '/wp-admin/admin-ajax.php?action=myavana_get_hair_entries'
];

self.addEventListener('install', (event) => {
    console.log('[SW] Installing service worker...');

    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => {
                console.log('[SW] Caching app shell');
                return cache.addAll(CACHE_ASSETS);
            })
            .then(() => {
                console.log('[SW] Service worker installed');
                return self.skipWaiting();
            })
    );
});

self.addEventListener('activate', (event) => {
    console.log('[SW] Activating service worker...');

    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (cacheName !== CACHE_NAME) {
                        console.log('[SW] Deleting old cache:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        }).then(() => {
            console.log('[SW] Service worker activated');
            return self.clients.claim();
        })
    );
});

self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    // Handle navigation requests
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request)
                .catch(() => {
                    return caches.match(OFFLINE_URL);
                })
        );
        return;
    }

    // Handle API requests with network-first strategy
    if (isApiRequest(request)) {
        event.respondWith(
            networkFirstStrategy(request)
        );
        return;
    }

    // Handle static assets with cache-first strategy
    if (isStaticAsset(request)) {
        event.respondWith(
            cacheFirstStrategy(request)
        );
        return;
    }

    // Handle images with cache-first strategy and WebP conversion
    if (isImageRequest(request)) {
        event.respondWith(
            imageStrategy(request)
        );
        return;
    }

    // Default: network-first for everything else
    event.respondWith(
        networkFirstStrategy(request)
    );
});

// Handle background sync for offline actions
self.addEventListener('sync', (event) => {
    console.log('[SW] Background sync triggered:', event.tag);

    if (event.tag === 'myavana-offline-sync') {
        event.waitUntil(syncOfflineData());
    }
});

// Handle push notifications
self.addEventListener('push', (event) => {
    console.log('[SW] Push notification received');

    const options = {
        body: event.data ? event.data.text() : 'New update from MYAVANA',
        icon: '/wp-content/plugins/myavana-hair-journey/assets/images/icon-192x192.png',
        badge: '/wp-content/plugins/myavana-hair-journey/assets/images/badge-72x72.png',
        tag: 'myavana-notification',
        renotify: true,
        actions: [
            {
                action: 'open',
                title: 'Open App',
                icon: '/wp-content/plugins/myavana-hair-journey/assets/images/icon-open.png'
            },
            {
                action: 'dismiss',
                title: 'Dismiss',
                icon: '/wp-content/plugins/myavana-hair-journey/assets/images/icon-close.png'
            }
        ]
    };

    event.waitUntil(
        self.registration.showNotification('MYAVANA', options)
    );
});

// Handle notification clicks
self.addEventListener('notificationclick', (event) => {
    console.log('[SW] Notification clicked:', event.action);

    event.notification.close();

    if (event.action === 'open' || !event.action) {
        event.waitUntil(
            clients.openWindow('/')
        );
    }
});

/**
 * Caching Strategies
 */

function networkFirstStrategy(request) {
    return fetch(request)
        .then((response) => {
            // Clone response before caching
            const responseClone = response.clone();

            if (response.status === 200) {
                caches.open(CACHE_NAME)
                    .then((cache) => {
                        cache.put(request, responseClone);
                    });
            }

            return response;
        })
        .catch(() => {
            return caches.match(request);
        });
}

function cacheFirstStrategy(request) {
    return caches.match(request)
        .then((response) => {
            if (response) {
                return response;
            }

            return fetch(request)
                .then((response) => {
                    if (response.status === 200) {
                        const responseClone = response.clone();
                        caches.open(CACHE_NAME)
                            .then((cache) => {
                                cache.put(request, responseClone);
                            });
                    }
                    return response;
                });
        });
}

function imageStrategy(request) {
    return caches.match(request)
        .then((response) => {
            if (response) {
                return response;
            }

            return fetch(request)
                .then((response) => {
                    if (response.status === 200) {
                        const responseClone = response.clone();

                        // Cache the original image
                        caches.open(CACHE_NAME)
                            .then((cache) => {
                                cache.put(request, responseClone);
                            });
                    }
                    return response;
                })
                .catch(() => {
                    // Return placeholder image for offline
                    return caches.match('/wp-content/plugins/myavana-hair-journey/assets/images/placeholder.jpg');
                });
        });
}

/**
 * Request Type Detection
 */

function isApiRequest(request) {
    const url = new URL(request.url);
    return url.pathname.includes('admin-ajax.php') ||
           url.pathname.includes('/wp-json/') ||
           API_CACHE_PATTERNS.some(pattern => request.url.includes(pattern));
}

function isStaticAsset(request) {
    const url = new URL(request.url);
    return url.pathname.match(/\.(css|js|woff|woff2|ttf|eot)$/);
}

function isImageRequest(request) {
    const url = new URL(request.url);
    return url.pathname.match(/\.(jpg|jpeg|png|gif|webp|svg)$/);
}

/**
 * Background Sync Functions
 */

async function syncOfflineData() {
    try {
        console.log('[SW] Syncing offline data...');

        // Get offline queue from IndexedDB
        const offlineQueue = await getOfflineQueue();

        for (const item of offlineQueue) {
            try {
                const response = await fetch(item.request.url, {
                    method: item.request.method,
                    headers: item.request.headers,
                    body: item.request.body
                });

                if (response.ok) {
                    console.log('[SW] Synced offline action:', item.id);
                    await removeFromOfflineQueue(item.id);
                }
            } catch (error) {
                console.error('[SW] Failed to sync item:', item.id, error);
            }
        }

        // Notify main thread
        const clients = await self.clients.matchAll();
        clients.forEach(client => {
            client.postMessage({
                type: 'SYNC_COMPLETE',
                data: { synced: offlineQueue.length }
            });
        });

    } catch (error) {
        console.error('[SW] Sync failed:', error);
    }
}

function getOfflineQueue() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('myavana-offline', 1);

        request.onerror = () => reject(request.error);

        request.onsuccess = () => {
            const db = request.result;
            const transaction = db.transaction(['queue'], 'readonly');
            const store = transaction.objectStore('queue');
            const getAllRequest = store.getAll();

            getAllRequest.onsuccess = () => resolve(getAllRequest.result);
            getAllRequest.onerror = () => reject(getAllRequest.error);
        };

        request.onupgradeneeded = () => {
            const db = request.result;
            if (!db.objectStoreNames.contains('queue')) {
                db.createObjectStore('queue', { keyPath: 'id', autoIncrement: true });
            }
        };
    });
}

function removeFromOfflineQueue(id) {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('myavana-offline', 1);

        request.onsuccess = () => {
            const db = request.result;
            const transaction = db.transaction(['queue'], 'readwrite');
            const store = transaction.objectStore('queue');
            const deleteRequest = store.delete(id);

            deleteRequest.onsuccess = () => resolve();
            deleteRequest.onerror = () => reject(deleteRequest.error);
        };
    });
}

/**
 * Cache Management
 */

// Periodic cache cleanup
setInterval(() => {
    caches.open(CACHE_NAME).then((cache) => {
        cache.keys().then((requests) => {
            requests.forEach((request) => {
                // Remove old cached responses (older than 24 hours)
                cache.match(request).then((response) => {
                    if (response) {
                        const cacheDate = new Date(response.headers.get('date'));
                        const now = new Date();
                        const hoursDiff = (now - cacheDate) / (1000 * 60 * 60);

                        if (hoursDiff > 24) {
                            cache.delete(request);
                        }
                    }
                });
            });
        });
    });
}, 60000 * 60); // Run every hour

console.log('[SW] Service Worker loaded');