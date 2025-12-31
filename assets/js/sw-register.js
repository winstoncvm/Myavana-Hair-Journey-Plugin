/**
 * MYAVANA Service Worker Registration
 * Register and manage PWA service worker
 *
 * @package Myavana_Hair_Journey
 * @version 1.0.0
 */

(function() {
    'use strict';

    // Check if service workers are supported
    if (!('serviceWorker' in navigator)) {
        console.warn('[SW Register] Service workers not supported');
        return;
    }

    /**
     * Register service worker
     */
    function registerServiceWorker() {
        const swPath = '/wp-content/plugins/myavana-hair-journey/assets/js/service-worker.js';

        navigator.serviceWorker.register(swPath)
            .then(function(registration) {
                console.log('[SW Register] Service worker registered:', registration.scope);

                // Check for updates periodically
                setInterval(function() {
                    registration.update();
                }, 3600000); // Check every hour

                // Handle waiting service worker
                if (registration.waiting) {
                    promptUserToUpdate(registration.waiting);
                }

                // Handle installing service worker
                registration.addEventListener('updatefound', function() {
                    const newWorker = registration.installing;

                    newWorker.addEventListener('statechange', function() {
                        if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                            // New service worker available
                            promptUserToUpdate(newWorker);
                        }
                    });
                });
            })
            .catch(function(error) {
                console.error('[SW Register] Registration failed:', error);
            });

        // Handle controller change
        navigator.serviceWorker.addEventListener('controllerchange', function() {
            console.log('[SW Register] Controller changed, reloading page');
            window.location.reload();
        });
    }

    /**
     * Prompt user to update to new service worker
     *
     * @param {ServiceWorker} worker Service worker instance
     */
    function promptUserToUpdate(worker) {
        console.log('[SW Register] New version available');

        // Check if we should auto-update or prompt user
        const autoUpdate = localStorage.getItem('myavana_auto_update_sw') === 'true';

        if (autoUpdate) {
            activateNewWorker(worker);
        } else {
            showUpdateNotification(worker);
        }
    }

    /**
     * Show update notification to user
     *
     * @param {ServiceWorker} worker Service worker instance
     */
    function showUpdateNotification(worker) {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = 'myavana-sw-update-notification';
        notification.innerHTML = `
            <div class="myavana-sw-update-content">
                <div class="myavana-sw-update-icon">🔄</div>
                <div class="myavana-sw-update-text">
                    <strong>Update Available</strong>
                    <p>A new version of MYAVANA is available</p>
                </div>
                <button class="myavana-sw-update-btn" id="myavana-sw-update">
                    Update Now
                </button>
                <button class="myavana-sw-dismiss-btn" id="myavana-sw-dismiss">
                    Later
                </button>
            </div>
        `;

        // Add styles if not already present
        if (!document.getElementById('myavana-sw-update-styles')) {
            const styles = document.createElement('style');
            styles.id = 'myavana-sw-update-styles';
            styles.textContent = `
                .myavana-sw-update-notification {
                    position: fixed;
                    bottom: 1rem;
                    left: 50%;
                    transform: translateX(-50%);
                    background: white;
                    border-radius: 12px;
                    box-shadow: 0 4px 24px rgba(0, 0, 0, 0.15);
                    z-index: 10000;
                    max-width: 500px;
                    width: calc(100% - 2rem);
                    animation: slideUp 0.3s ease;
                }

                @keyframes slideUp {
                    from {
                        transform: translateX(-50%) translateY(100px);
                        opacity: 0;
                    }
                    to {
                        transform: translateX(-50%) translateY(0);
                        opacity: 1;
                    }
                }

                .myavana-sw-update-content {
                    display: flex;
                    align-items: center;
                    gap: 1rem;
                    padding: 1rem;
                }

                .myavana-sw-update-icon {
                    font-size: 2rem;
                    flex-shrink: 0;
                }

                .myavana-sw-update-text {
                    flex: 1;
                }

                .myavana-sw-update-text strong {
                    display: block;
                    color: #1a1a1a;
                    font-size: 1rem;
                    margin-bottom: 0.25rem;
                }

                .myavana-sw-update-text p {
                    margin: 0;
                    color: #6c757d;
                    font-size: 0.875rem;
                }

                .myavana-sw-update-btn,
                .myavana-sw-dismiss-btn {
                    border: none;
                    padding: 0.5rem 1rem;
                    border-radius: 6px;
                    font-weight: 500;
                    cursor: pointer;
                    font-size: 0.875rem;
                    transition: all 0.2s ease;
                }

                .myavana-sw-update-btn {
                    background: #FF6B6B;
                    color: white;
                }

                .myavana-sw-update-btn:hover {
                    background: #ff5252;
                }

                .myavana-sw-dismiss-btn {
                    background: #f8f9fa;
                    color: #6c757d;
                }

                .myavana-sw-dismiss-btn:hover {
                    background: #e9ecef;
                }

                @media (max-width: 767px) {
                    .myavana-sw-update-content {
                        flex-direction: column;
                        text-align: center;
                    }

                    .myavana-sw-update-btn,
                    .myavana-sw-dismiss-btn {
                        width: 100%;
                    }
                }
            `;
            document.head.appendChild(styles);
        }

        // Add to page
        document.body.appendChild(notification);

        // Bind update button
        document.getElementById('myavana-sw-update').addEventListener('click', function() {
            activateNewWorker(worker);
            notification.remove();
        });

        // Bind dismiss button
        document.getElementById('myavana-sw-dismiss').addEventListener('click', function() {
            notification.remove();
        });

        // Auto-dismiss after 30 seconds
        setTimeout(function() {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 30000);
    }

    /**
     * Activate new service worker
     *
     * @param {ServiceWorker} worker Service worker instance
     */
    function activateNewWorker(worker) {
        console.log('[SW Register] Activating new service worker');

        worker.postMessage({ type: 'SKIP_WAITING' });
    }

    /**
     * Check if app should prompt for installation
     */
    function checkInstallPrompt() {
        let deferredPrompt = null;

        window.addEventListener('beforeinstallprompt', function(event) {
            console.log('[SW Register] Install prompt available');

            // Prevent default install prompt
            event.preventDefault();

            // Save for later use
            deferredPrompt = event;

            // Show custom install button/banner if desired
            showInstallPrompt(deferredPrompt);
        });

        window.addEventListener('appinstalled', function() {
            console.log('[SW Register] App installed');
            deferredPrompt = null;

            // Hide install prompt
            hideInstallPrompt();
        });
    }

    /**
     * Show install prompt
     *
     * @param {Event} promptEvent Install prompt event
     */
    function showInstallPrompt(promptEvent) {
        // Check if user has dismissed install prompt before
        const dismissed = localStorage.getItem('myavana_install_prompt_dismissed');
        if (dismissed) {
            return;
        }

        // Create install banner
        const banner = document.createElement('div');
        banner.id = 'myavana-install-banner';
        banner.className = 'myavana-install-banner';
        banner.innerHTML = `
            <div class="myavana-install-content">
                <div class="myavana-install-icon">
                    <img src="/wp-content/plugins/myavana-hair-journey/assets/images/icon-72x72.png" alt="MYAVANA" />
                </div>
                <div class="myavana-install-text">
                    <strong>Install MYAVANA</strong>
                    <p>Get quick access from your home screen</p>
                </div>
                <button class="myavana-install-btn" id="myavana-install-app">Install</button>
                <button class="myavana-install-dismiss" id="myavana-install-dismiss">×</button>
            </div>
        `;

        document.body.appendChild(banner);

        // Add install button handler
        document.getElementById('myavana-install-app').addEventListener('click', function() {
            promptEvent.prompt();

            promptEvent.userChoice.then(function(choiceResult) {
                if (choiceResult.outcome === 'accepted') {
                    console.log('[SW Register] User accepted install');
                } else {
                    console.log('[SW Register] User dismissed install');
                }
            });

            banner.remove();
        });

        // Add dismiss button handler
        document.getElementById('myavana-install-dismiss').addEventListener('click', function() {
            localStorage.setItem('myavana_install_prompt_dismissed', 'true');
            banner.remove();
        });
    }

    /**
     * Hide install prompt
     */
    function hideInstallPrompt() {
        const banner = document.getElementById('myavana-install-banner');
        if (banner) {
            banner.remove();
        }
    }

    /**
     * Initialize PWA features
     */
    function initPWA() {
        registerServiceWorker();
        checkInstallPrompt();

        // Log PWA status
        if (window.matchMedia('(display-mode: standalone)').matches) {
            console.log('[SW Register] Running as PWA');
        } else {
            console.log('[SW Register] Running in browser');
        }
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPWA);
    } else {
        initPWA();
    }

})();
