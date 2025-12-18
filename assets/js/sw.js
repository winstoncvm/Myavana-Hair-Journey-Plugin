const CACHE_NAME = 'myavana-hair-journey-v1.0.0';
const urlsToCache = [
    '/wp-content/plugins/myavana-hair-journey/assets/css/advanced-dashboard.css',
    '/wp-content/plugins/myavana-hair-journey/assets/css/enhanced-timeline.css',
    '/wp-content/plugins/myavana-hair-journey/assets/css/enhanced-profile.css',
    '/wp-content/plugins/myavana-hair-journey/assets/js/advanced-dashboard.js',
    '/wp-content/plugins/myavana-hair-journey/assets/js/enhanced-timeline.js',
    '/wp-content/plugins/myavana-hair-journey/assets/js/enhanced-profile.js',
    // Add more assets as needed
];

// Install event - cache resources
self.addEventListener('install', function(event) {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(function(cache) {
                console.log('Opened cache');
                return cache.addAll(urlsToCache);
            })
    );
});

// Fetch event - serve from cache when offline
self.addEventListener('fetch', function(event) {
    event.respondWith(
        caches.match(event.request)
            .then(function(response) {
                // Return cached version or fetch from network
                if (response) {
                    return response;
                }
                return fetch(event.request);
            }
        )
    );
});

// Activate event - clean up old caches
self.addEventListener('activate', function(event) {
    event.waitUntil(
        caches.keys().then(function(cacheNames) {
            return Promise.all(
                cacheNames.map(function(cacheName) {
                    if (cacheName !== CACHE_NAME) {
                        console.log('Deleting old cache:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
});

// Background sync for offline data submission
self.addEventListener('sync', function(event) {
    if (event.tag == 'background-sync') {
        event.waitUntil(doBackgroundSync());
    }
});

function doBackgroundSync() {
    return new Promise(function(resolve, reject) {
        // Handle offline data synchronization
        // This would sync any data that was queued while offline
        console.log('Background sync completed');
        resolve();
    });
}

// Push notifications
self.addEventListener('push', function(event) {
    const options = {
        body: event.data ? event.data.text() : 'New hair journey update!',
        icon: '/wp-content/plugins/myavana-hair-journey/assets/images/icon-192x192.png',
        badge: '/wp-content/plugins/myavana-hair-journey/assets/images/badge-72x72.png',
        vibrate: [100, 50, 100],
        data: {
            dateOfArrival: Date.now(),
            primaryKey: 1
        },
        actions: [
            {
                action: 'explore',
                title: 'View Details',
                icon: '/wp-content/plugins/myavana-hair-journey/assets/images/checkmark.png'
            },
            {
                action: 'close',
                title: 'Close',
                icon: '/wp-content/plugins/myavana-hair-journey/assets/images/xmark.png'
            }
        ]
    };

    event.waitUntil(
        self.registration.showNotification('Myavana Hair Journey', options)
    );
});

// Notification click handling
self.addEventListener('notificationclick', function(event) {
    console.log('Notification click received.');

    event.notification.close();

    if (event.action === 'explore') {
        // Open the app to the relevant page
        event.waitUntil(
            clients.openWindow('/wp-admin/admin.php?page=myavana-hair-journey')
        );
    } else if (event.action === 'close') {
        // Just close the notification
        console.log('Notification closed by user action');
    } else {
        // Default action - open the app
        event.waitUntil(
            clients.openWindow('/wp-admin/admin.php?page=myavana-hair-journey')
        );
    }
});