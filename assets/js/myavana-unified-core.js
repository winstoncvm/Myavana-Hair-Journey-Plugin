/**
 * MYAVANA Unified Core Framework
 * Global data synchronization and component communication system
 * Version: 1.0.0
 */

(function(window, $) {
    'use strict';

    // Prevent multiple initializations
    if (window.Myavana) {
        return;
    }

    /**
     * Core MYAVANA Framework
     */
    window.Myavana = {
        version: '1.0.0',
        initialized: false,
        debug: false,

        // Core modules
        Data: {},
        Events: {},
        UI: {},
        API: {},
        Components: {},
        Router: {},
        RealTime: {},
        Performance: {},
        PWA: {},
        Analytics: {},

        /**
         * Initialize the framework
         */
        init: function(config = {}) {
            if (this.initialized) return;

            this.debug = config.debug || false;
            this.log('Initializing MYAVANA Unified Framework v' + this.version);

            // Initialize core modules
            this.Events.init();
            this.Data.init();
            this.API.init();
            this.UI.init();
            this.Router.init();
            this.RealTime.init();
            this.Performance.init();
            this.PWA.init();
            this.Analytics.init();

            this.initialized = true;
            this.Events.trigger('framework:ready');
            this.log('Framework initialized successfully');
        },

        /**
         * Debug logging
         */
        log: function(...args) {
            if (this.debug && console && console.log) {
                console.log('[MYAVANA]', ...args);
            }
        },

        /**
         * Error logging
         */
        error: function(...args) {
            if (console && console.error) {
                console.error('[MYAVANA ERROR]', ...args);
            }
        }
    };

    /**
     * Event System - Cross-component communication
     */
    Myavana.Events = {
        listeners: new Map(),

        init: function() {
            Myavana.log('Events system initialized');
        },

        /**
         * Subscribe to events
         */
        on: function(event, callback, context = null) {
            if (!this.listeners.has(event)) {
                this.listeners.set(event, []);
            }

            this.listeners.get(event).push({
                callback: callback,
                context: context
            });

            Myavana.log('Event listener added:', event);
        },

        /**
         * Unsubscribe from events
         */
        off: function(event, callback = null) {
            if (!this.listeners.has(event)) return;

            if (callback) {
                const listeners = this.listeners.get(event);
                const index = listeners.findIndex(l => l.callback === callback);
                if (index > -1) {
                    listeners.splice(index, 1);
                }
            } else {
                this.listeners.delete(event);
            }
        },

        /**
         * Trigger events
         */
        trigger: function(event, data = {}) {
            Myavana.log('Event triggered:', event, data);

            if (!this.listeners.has(event)) return;

            this.listeners.get(event).forEach(listener => {
                try {
                    if (listener.context) {
                        listener.callback.call(listener.context, data);
                    } else {
                        listener.callback(data);
                    }
                } catch (error) {
                    Myavana.error('Event callback error:', error);
                }
            });

            // Also trigger as DOM event for broader compatibility
            $(document).trigger('myavana:' + event, [data]);
        }
    };

    /**
     * Unified Data Management
     */
    Myavana.Data = {
        cache: new Map(),
        storage: null,

        init: function() {
            // Initialize local storage for persistence
            try {
                this.storage = window.localStorage;
                this.loadFromStorage();
            } catch (e) {
                Myavana.error('Local storage not available');
            }

            Myavana.log('Data system initialized');
        },

        /**
         * Set data with event triggering
         */
        set: function(key, value, trigger = true) {
            const oldValue = this.cache.get(key);
            this.cache.set(key, value);

            // Persist to local storage
            if (this.storage) {
                try {
                    this.storage.setItem('myavana_' + key, JSON.stringify(value));
                } catch (e) {
                    Myavana.error('Failed to persist data:', key);
                }
            }

            if (trigger) {
                Myavana.Events.trigger('data:changed', {
                    key: key,
                    value: value,
                    oldValue: oldValue
                });

                Myavana.Events.trigger('data:changed:' + key, {
                    value: value,
                    oldValue: oldValue
                });
            }

            Myavana.log('Data set:', key, value);
        },

        /**
         * Get data
         */
        get: function(key, defaultValue = null) {
            return this.cache.has(key) ? this.cache.get(key) : defaultValue;
        },

        /**
         * Update specific properties of an object
         */
        update: function(key, updates) {
            const current = this.get(key, {});
            const updated = { ...current, ...updates };
            this.set(key, updated);
        },

        /**
         * Remove data
         */
        remove: function(key) {
            const oldValue = this.cache.get(key);
            this.cache.delete(key);

            if (this.storage) {
                this.storage.removeItem('myavana_' + key);
            }

            Myavana.Events.trigger('data:removed', {
                key: key,
                oldValue: oldValue
            });
        },

        /**
         * Load data from local storage
         */
        loadFromStorage: function() {
            if (!this.storage) return;

            for (let i = 0; i < this.storage.length; i++) {
                const key = this.storage.key(i);
                if (key && key.startsWith('myavana_')) {
                    const dataKey = key.replace('myavana_', '');
                    try {
                        const value = JSON.parse(this.storage.getItem(key));
                        this.cache.set(dataKey, value);
                    } catch (e) {
                        Myavana.error('Failed to load data from storage:', key);
                    }
                }
            }
        },

        /**
         * Clear all data
         */
        clear: function() {
            this.cache.clear();
            if (this.storage) {
                const keysToRemove = [];
                for (let i = 0; i < this.storage.length; i++) {
                    const key = this.storage.key(i);
                    if (key && key.startsWith('myavana_')) {
                        keysToRemove.push(key);
                    }
                }
                keysToRemove.forEach(key => this.storage.removeItem(key));
            }
        }
    };

    /**
     * Unified API Layer
     */
    Myavana.API = {
        endpoints: {},
        defaultHeaders: {},

        init: function() {
            // Set default headers for WordPress AJAX
            this.defaultHeaders = {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            };

            Myavana.log('API system initialized');
        },

        /**
         * Register API endpoint
         */
        register: function(name, config) {
            this.endpoints[name] = {
                url: config.url || (window.myavanaAjax && window.myavanaAjax.ajax_url) || '/wp-admin/admin-ajax.php',
                action: config.action,
                method: config.method || 'POST',
                cache: config.cache || false,
                ...config
            };
        },

        /**
         * Make API call
         */
        call: function(endpoint, data = {}, options = {}) {
            return new Promise((resolve, reject) => {
                const config = this.endpoints[endpoint];
                if (!config) {
                    reject(new Error('Unknown endpoint: ' + endpoint));
                    return;
                }

                // Prepare request data
                const requestData = {
                    action: config.action,
                    ...data
                };

                // Add nonce if available
                if (window.myavanaAjax && window.myavanaAjax.nonce) {
                    requestData.nonce = window.myavanaAjax.nonce;
                }

                // Check cache first
                const cacheKey = endpoint + ':' + JSON.stringify(requestData);
                if (config.cache && Myavana.Data.cache.has(cacheKey)) {
                    resolve(Myavana.Data.cache.get(cacheKey));
                    return;
                }

                $.ajax({
                    url: config.url,
                    method: config.method,
                    data: requestData,
                    headers: { ...this.defaultHeaders, ...config.headers },
                    ...options
                })
                .done(response => {
                    // Cache successful responses
                    if (config.cache && response.success) {
                        Myavana.Data.cache.set(cacheKey, response);
                    }

                    resolve(response);
                })
                .fail((xhr, status, error) => {
                    Myavana.error('API call failed:', endpoint, error);
                    reject({ xhr, status, error });
                });
            });
        }
    };

    /**
     * UI Utilities and Shared Components
     */
    Myavana.UI = {
        init: function() {
            this.setupGlobalStyles();
            this.setupNotificationSystem();
            // this.setupNavigation();
            this.setupKeyboardShortcuts();
            Myavana.log('UI system initialized');
        },

        /**
         * Setup global CSS variables and utilities
         */
        setupGlobalStyles: function() {
            if (!document.getElementById('myavana-global-styles')) {
                const style = document.createElement('style');
                style.id = 'myavana-global-styles';
                style.textContent = `
                    :root {
                        --myavana-z-modal: 10000;
                        --myavana-z-notification: 10001;
                        --myavana-z-tooltip: 10002;
                        --myavana-transition-fast: 0.2s ease;
                        --myavana-transition-normal: 0.3s ease;
                        --myavana-transition-slow: 0.5s ease;
                    }

                    .myavana-hidden { display: none !important; }
                    .myavana-loading { pointer-events: none; opacity: 0.6; }
                    .myavana-fade-in {
                        animation: myavanaFadeIn var(--myavana-transition-normal);
                    }
                    .myavana-fade-out {
                        animation: myavanaFadeOut var(--myavana-transition-normal);
                    }

                    @keyframes myavanaFadeIn {
                        from { opacity: 0; transform: translateY(10px); }
                        to { opacity: 1; transform: translateY(0); }
                    }

                    @keyframes myavanaFadeOut {
                        from { opacity: 1; transform: translateY(0); }
                        to { opacity: 0; transform: translateY(-10px); }
                    }

                    

                    .myavana-modal.active {
                        opacity: 1;
                        visibility: visible;
                    }

                    .myavana-modal-content {
                        background: var(--myavana-white);
                        border-radius: 12px;
                        box-shadow: 0 20px 40px rgba(34, 35, 35, 0.2);
                        max-width: 90vw;
                        max-height: 90vh;
                        overflow-y: auto;
                        transform: translateY(20px) scale(0.95);
                        transition: all 0.3s ease;
                        position: relative;
                    }

                    .myavana-modal.active .myavana-modal-content {
                        transform: translateY(0) scale(1);
                    }

                    .myavana-modal-header {
                        display: flex;
                        align-items: center;
                        justify-content: space-between;
                        padding: 20px 24px;
                        border-bottom: 1px solid var(--myavana-sand);
                    }

                    .myavana-modal-title {
                        font-family: 'Archivo Black', sans-serif;
                        font-size: 18px;
                        font-weight: 900;
                        color: var(--myavana-onyx);
                        text-transform: uppercase;
                        letter-spacing: 0.5px;
                    }

                    .myavana-modal-close {
                        background: none;
                        border: none;
                        font-size: 24px;
                        color: var(--myavana-blueberry);
                        cursor: pointer;
                        padding: 4px;
                        border-radius: 4px;
                        transition: all 0.3s ease;
                    }

                    .myavana-modal-close:hover {
                        background: var(--myavana-light-coral);
                        color: var(--myavana-coral);
                    }

                    .myavana-modal-body {
                        padding: 24px;
                    }

                    .myavana-modal-footer {
                        display: flex;
                        gap: 12px;
                        justify-content: flex-end;
                        padding: 20px 24px;
                        border-top: 1px solid var(--myavana-sand);
                    }

                    body.myavana-modal-open {
                        overflow: hidden;
                    }

                    /* Universal Button Styles */
                    .myavana-btn {
                        font-family: 'Archivo', sans-serif;
                        font-weight: 600;
                        text-transform: uppercase;
                        letter-spacing: 0.5px;
                        padding: 10px 20px;
                        border: none;
                        border-radius: 6px;
                        cursor: pointer;
                        transition: all 0.3s ease;
                        text-decoration: none;
                        display: inline-flex;
                        align-items: center;
                        gap: 8px;
                        font-size: 12px;
                    }

                    .myavana-btn-primary {
                        background: var(--myavana-coral);
                        color: var(--myavana-white);
                    }

                    .myavana-btn-primary:hover {
                        background: #d4956f;
                        transform: translateY(-2px);
                        box-shadow: 0 4px 12px rgba(231, 166, 144, 0.4);
                    }

                    .myavana-btn-secondary {
                        background: var(--myavana-light-coral);
                        color: var(--myavana-coral);
                    }

                    .myavana-btn-secondary:hover {
                        background: var(--myavana-coral);
                        color: var(--myavana-white);
                        transform: translateY(-2px);
                    }

                    .myavana-btn-outline {
                        background: transparent;
                        border: 2px solid var(--myavana-coral);
                        color: var(--myavana-coral);
                    }

                    .myavana-btn-outline:hover {
                        background: var(--myavana-coral);
                        color: var(--myavana-white);
                        transform: translateY(-2px);
                    }
                `;
                document.head.appendChild(style);
            }
        },

        /**
         * Unified notification system
         */
        setupNotificationSystem: function() {
            if (!document.getElementById('myavana-notifications')) {
                const container = document.createElement('div');
                container.id = 'myavana-notifications';
                container.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    z-index: var(--myavana-z-notification);
                    pointer-events: none;
                `;
                document.body.appendChild(container);
            }
        },

        /**
         * Universal navigation component
         */
        setupNavigation: function() {
            if (!document.getElementById('myavana-universal-nav')) {
                const nav = document.createElement('nav');
                nav.id = 'myavana-universal-nav';
                nav.className = 'myavana-universal-nav';
                nav.innerHTML = `
                    <div class="myavana-nav-container">
                        <div class="myavana-nav-brand">
                            <span class="myavana-logo">MYAVANA</span>
                        </div>
                        <div class="myavana-nav-menu">
                            <a href="#dashboard" class="myavana-nav-item" data-route="dashboard">
                                <span class="myavana-nav-icon">📊</span>
                                <span class="myavana-nav-label">Dashboard</span>
                            </a>
                            <a href="#profile" class="myavana-nav-item" data-route="profile">
                                <span class="myavana-nav-icon">👤</span>
                                <span class="myavana-nav-label">Profile</span>
                            </a>
                            <a href="#timeline" class="myavana-nav-item" data-route="timeline">
                                <span class="myavana-nav-icon">📅</span>
                                <span class="myavana-nav-label">Timeline</span>
                            </a>
                            <a href="#diary" class="myavana-nav-item" data-route="diary">
                                <span class="myavana-nav-icon">📝</span>
                                <span class="myavana-nav-label">Diary</span>
                            </a>
                            <a href="#analysis" class="myavana-nav-item" data-route="analysis">
                                <span class="myavana-nav-icon">🔍</span>
                                <span class="myavana-nav-label">AI Analysis</span>
                            </a>
                            <a href="#analytics" class="myavana-nav-item" data-route="analytics">
                                <span class="myavana-nav-icon">📈</span>
                                <span class="myavana-nav-label">Analytics</span>
                            </a>
                        </div>
                        <div class="myavana-nav-actions">
                            <button class="myavana-nav-toggle" aria-label="Toggle navigation">
                                <span></span>
                                <span></span>
                                <span></span>
                            </button>
                        </div>
                    </div>
                `;

                // Add navigation styles
                const navStyles = document.createElement('style');
                navStyles.id = 'myavana-nav-styles';
                navStyles.textContent = `
                    .myavana-universal-nav {
                        position: fixed;
                        top: 0;
                        left: 0;
                        width: 100%;
                        background: rgba(255, 255, 255, 0.95);
                        backdrop-filter: blur(10px);
                        border-bottom: 1px solid var(--myavana-sand);
                        z-index: var(--myavana-z-modal);
                        box-shadow: 0 2px 20px rgba(34, 35, 35, 0.1);
                    }

                    .myavana-nav-container {
                        display: flex;
                        align-items: center;
                        justify-content: space-between;
                        max-width: 1200px;
                        margin: 0 auto;
                        padding: 0 20px;
                        height: 60px;
                    }

                    .myavana-nav-brand .myavana-logo {
                        font-family: 'Archivo Black', sans-serif;
                        font-size: 18px;
                        font-weight: 900;
                        color: var(--myavana-onyx);
                        text-transform: uppercase;
                        letter-spacing: 0.5px;
                    }

                    .myavana-nav-menu {
                        display: flex;
                        gap: 8px;
                        align-items: center;
                    }

                    .myavana-nav-item {
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                        padding: 8px 12px;
                        border-radius: 8px;
                        text-decoration: none;
                        color: var(--myavana-blueberry);
                        font-family: 'Archivo', sans-serif;
                        font-size: 11px;
                        font-weight: 600;
                        text-transform: uppercase;
                        letter-spacing: 0.5px;
                        transition: all 0.3s ease;
                        cursor: pointer;
                        position: relative;
                    }

                    .myavana-nav-item:hover,
                    .myavana-nav-item.active {
                        background: var(--myavana-light-coral);
                        color: var(--myavana-coral);
                        transform: translateY(-2px);
                    }

                    .myavana-nav-icon {
                        font-size: 16px;
                        margin-bottom: 4px;
                    }

                    .myavana-nav-toggle {
                        display: none;
                        flex-direction: column;
                        background: none;
                        border: none;
                        cursor: pointer;
                        padding: 4px;
                        width: 24px;
                        height: 24px;
                    }

                    .myavana-nav-toggle span {
                        width: 100%;
                        height: 2px;
                        background: var(--myavana-onyx);
                        margin: 2px 0;
                        transition: 0.3s;
                    }

                    /* Mobile responsive */
                    @media (max-width: 768px) {
                        .myavana-nav-menu {
                            position: absolute;
                            top: 100%;
                            left: 0;
                            width: 100%;
                            background: var(--myavana-white);
                            border-top: 1px solid var(--myavana-sand);
                            flex-direction: column;
                            padding: 20px;
                            gap: 16px;
                            box-shadow: 0 8px 25px rgba(34, 35, 35, 0.15);
                            transform: translateY(-100%);
                            opacity: 0;
                            visibility: hidden;
                            transition: all 0.3s ease;
                        }

                        .myavana-nav-menu.active {
                            transform: translateY(0);
                            opacity: 1;
                            visibility: visible;
                        }

                        .myavana-nav-item {
                            flex-direction: row;
                            justify-content: flex-start;
                            padding: 12px 16px;
                            width: 100%;
                        }

                        .myavana-nav-icon {
                            margin-bottom: 0;
                            margin-right: 12px;
                        }

                        .myavana-nav-toggle {
                            display: flex;
                        }
                    }

                    /* Add top padding to body when nav is present */
                    body.myavana-nav-active {
                        padding-top: 60px;
                    }
                `;

                // Insert navigation and styles
                document.head.appendChild(navStyles);
                document.body.insertBefore(nav, document.body.firstChild);
                document.body.classList.add('myavana-nav-active');

                // Setup navigation event handlers
                this.setupNavigationEvents(nav);
            }
        },

        /**
         * Setup navigation event handlers
         */
        setupNavigationEvents: function(nav) {
            const toggle = nav.querySelector('.myavana-nav-toggle');
            const menu = nav.querySelector('.myavana-nav-menu');
            const navItems = nav.querySelectorAll('.myavana-nav-item');

            // Mobile menu toggle
            toggle.addEventListener('click', () => {
                menu.classList.toggle('active');
            });

            // Navigation item clicks
            navItems.forEach(item => {
                item.addEventListener('click', (e) => {
                    e.preventDefault();
                    const route = item.getAttribute('data-route');

                    // Update active state
                    navItems.forEach(nav => nav.classList.remove('active'));
                    item.classList.add('active');

                    // Close mobile menu
                    menu.classList.remove('active');

                    // Navigate using router
                    Myavana.Router.navigate(route);

                    // Trigger navigation event
                    Myavana.Events.trigger('nav:navigate', { route: route, element: item });
                });
            });

            // Close mobile menu on outside click
            document.addEventListener('click', (e) => {
                if (!nav.contains(e.target)) {
                    menu.classList.remove('active');
                }
            });
        },

        /**
         * Show notification
         */
        notify: function(message, type = 'info', duration = 4000) {
            const container = document.getElementById('myavana-notifications');
            if (!container) return;

            const notification = document.createElement('div');
            notification.className = `myavana-notification myavana-notification-${type}`;
            notification.style.cssText = `
                background: ${this.getNotificationColor(type)};
                color: var(--white);
                padding: var(--space-3) var(--space-4);
                margin-bottom: var(--space-2);
                border-radius: var(--radius-lg);
                box-shadow: 0 8px 25px rgba(34, 35, 35, 0.15);
                font-family: 'Archivo', sans-serif;
                font-weight: 600;
                max-width: 350px;
                opacity: 0;
                transform: translateX(100%);
                transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
                pointer-events: auto;
                backdrop-filter: blur(10px);
                display: flex;
                align-items: center;
                justify-content: space-between;
            `;

            notification.innerHTML = `
                <span>${message}</span>
                <button style="
                    background: none;
                    border: none;
                    color: var(--white);
                    font-size: 18px;
                    margin-left: var(--space-2);
                    cursor: pointer;
                    opacity: 0.8;
                    padding: 0;
                    width: 20px;
                    height: 20px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                ">&times;</button>
            `;

            // Close button functionality
            notification.querySelector('button').onclick = () => {
                this.removeNotification(notification);
            };

            container.appendChild(notification);

            // Animate in
            setTimeout(() => {
                notification.style.opacity = '1';
                notification.style.transform = 'translateX(0)';
            }, 100);

            // Auto-remove
            setTimeout(() => {
                this.removeNotification(notification);
            }, duration);

            Myavana.Events.trigger('ui:notification:shown', { message, type });
        },

        /**
         * Remove notification
         */
        removeNotification: function(notification) {
            notification.style.opacity = '0';
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 400);
        },

        /**
         * Get notification color by type
         */
        getNotificationColor: function(type) {
            const colors = {
                success: 'var(--coral)',
                error: '#dc3545',
                warning: '#f0ad4e',
                info: 'var(--myavana-blueberry)'
            };
            return colors[type] || colors.info;
        },

        /**
         * Setup keyboard shortcuts for navigation
         */
        setupKeyboardShortcuts: function() {
            document.addEventListener('keydown', (e) => {
                // Only handle shortcuts when not in input fields
                if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.isContentEditable) {
                    return;
                }

                // Handle keyboard shortcuts
                if (e.altKey || e.metaKey) {
                    switch (e.key) {
                        case '1':
                            e.preventDefault();
                            Myavana.Router.navigate('dashboard');
                            break;
                        case '2':
                            e.preventDefault();
                            Myavana.Router.navigate('profile');
                            break;
                        case '3':
                            e.preventDefault();
                            Myavana.Router.navigate('timeline');
                            break;
                        case '4':
                            e.preventDefault();
                            Myavana.Router.navigate('diary');
                            break;
                        case '5':
                            e.preventDefault();
                            Myavana.Router.navigate('analysis');
                            break;
                        case '6':
                            e.preventDefault();
                            Myavana.Router.navigate('analytics');
                            break;
                    }
                }

                // Handle escape key to close modals
                if (e.key === 'Escape') {
                    const openModal = document.querySelector('.myavana-modal.active');
                    if (openModal) {
                        openModal.classList.remove('active');
                        document.body.classList.remove('myavana-modal-open');
                    }
                }
            });

            Myavana.log('Keyboard shortcuts initialized');
        },

        /**
         * Show loading state
         */
        showLoading: function(element) {
            if (typeof element === 'string') {
                element = document.querySelector(element);
            }
            if (element) {
                element.classList.add('myavana-loading');
            }
        },

        /**
         * Hide loading state
         */
        hideLoading: function(element) {
            if (typeof element === 'string') {
                element = document.querySelector(element);
            }
            if (element) {
                element.classList.remove('myavana-loading');
            }
        },

        /**
         * Universal modal creation and management
         */
        createModal: function(options = {}) {
            const modal = document.createElement('div');
            modal.className = 'myavana-modal';
            modal.id = options.id || 'myavana-modal-' + Date.now();

            modal.innerHTML = `
                <div class="myavana-modal-content" style="width: ${options.width || 'auto'}; max-width: ${options.maxWidth || '90vw'};">
                    <div class="myavana-modal-header">
                        <h3 class="myavana-modal-title">${options.title || 'Modal'}</h3>
                        <button class="myavana-modal-close" aria-label="Close modal">&times;</button>
                    </div>
                    <div class="myavana-modal-body">
                        ${options.content || ''}
                    </div>
                    ${options.footer ? `<div class="myavana-modal-footer">${options.footer}</div>` : ''}
                </div>
            `;

            // Setup close handlers
            const closeBtn = modal.querySelector('.myavana-modal-close');
            const closeModal = () => {
                modal.classList.remove('active');
                document.body.classList.remove('myavana-modal-open');
                if (options.onClose) options.onClose();
                setTimeout(() => {
                    if (modal.parentNode) {
                        modal.parentNode.removeChild(modal);
                    }
                }, 300);
            };

            closeBtn.addEventListener('click', closeModal);
            modal.addEventListener('click', (e) => {
                if (e.target === modal) closeModal();
            });

            // Append to body
            document.body.appendChild(modal);

            // Show modal
            setTimeout(() => {
                modal.classList.add('active');
                document.body.classList.add('myavana-modal-open');
                if (options.onShow) options.onShow();
            }, 10);

            return {
                element: modal,
                close: closeModal,
                setContent: (content) => {
                    modal.querySelector('.myavana-modal-body').innerHTML = content;
                },
                setTitle: (title) => {
                    modal.querySelector('.myavana-modal-title').textContent = title;
                }
            };
        },

        /**
         * Create confirmation modal
         */
        confirm: function(message, options = {}) {
            return new Promise((resolve) => {
                const footer = `
                    <button class="myavana-btn myavana-btn-outline confirm-cancel">
                        ${options.cancelText || 'Cancel'}
                    </button>
                    <button class="myavana-btn myavana-btn-primary confirm-ok">
                        ${options.confirmText || 'Confirm'}
                    </button>
                `;

                const modal = this.createModal({
                    title: options.title || 'Confirm',
                    content: `<p style="font-family: 'Archivo', sans-serif; color: var(--myavana-onyx); line-height: 1.6;">${message}</p>`,
                    footer: footer,
                    width: '400px'
                });

                modal.element.querySelector('.confirm-cancel').addEventListener('click', () => {
                    modal.close();
                    resolve(false);
                });

                modal.element.querySelector('.confirm-ok').addEventListener('click', () => {
                    modal.close();
                    resolve(true);
                });
            });
        },

        /**
         * Create alert modal
         */
        alert: function(message, options = {}) {
            return new Promise((resolve) => {
                const footer = `
                    <button class="myavana-btn myavana-btn-primary alert-ok">
                        ${options.buttonText || 'OK'}
                    </button>
                `;

                const modal = this.createModal({
                    title: options.title || 'Alert',
                    content: `<p style="font-family: 'Archivo', sans-serif; color: var(--myavana-onyx); line-height: 1.6;">${message}</p>`,
                    footer: footer,
                    width: '400px'
                });

                modal.element.querySelector('.alert-ok').addEventListener('click', () => {
                    modal.close();
                    resolve();
                });
            });
        }
    };

    /**
     * Component Registry
     */
    Myavana.Components = {
        registry: new Map(),

        /**
         * Register a component
         */
        register: function(name, component) {
            this.registry.set(name, component);
            Myavana.log('Component registered:', name);
            Myavana.Events.trigger('component:registered', { name, component });
        },

        /**
         * Get a component
         */
        get: function(name) {
            return this.registry.get(name);
        },

        /**
         * Check if component exists
         */
        has: function(name) {
            return this.registry.has(name);
        }
    };

    /**
     * Router for deep linking and navigation
     */
    Myavana.Router = {
        routes: new Map(),
        currentRoute: null,

        init: function() {
            // Listen for hash changes
            window.addEventListener('hashchange', () => {
                this.handleRoute();
            });

            // Handle initial route
            this.handleRoute();

            Myavana.log('Router initialized');
        },

        /**
         * Register a route
         */
        route: function(path, handler) {
            this.routes.set(path, handler);
        },

        /**
         * Navigate to a route
         */
        navigate: function(path, data = {}) {
            window.location.hash = path;
            Myavana.Events.trigger('router:navigate', { path, data });
        },

        /**
         * Handle current route
         */
        handleRoute: function() {
            const hash = window.location.hash.slice(1);
            const [path, ...params] = hash.split('/');

            if (this.routes.has(path)) {
                this.currentRoute = { path, params };
                this.routes.get(path)(params);
                Myavana.Events.trigger('router:route-changed', { path, params });
            }
        }
    };

    /**
     * Auto-initialize when DOM is ready
     */
    $(document).ready(function() {
        // Initialize with WordPress AJAX data if available
        const config = {
            debug: window.myavanaDebug || false
        };

        Myavana.init(config);
    });

    /**
     * Real-Time Data Synchronization
     */
    Myavana.RealTime = {
        websocket: null,
        isConnected: false,
        reconnectAttempts: 0,
        maxReconnectAttempts: 5,
        heartbeatInterval: null,

        init: function() {
            this.setupWebSocket();
            this.setupHeartbeat();
            Myavana.log('Real-time system initialized');
        },

        setupWebSocket: function() {
            // Only initialize if WebSocket URL is available
            if (!window.myavanaAjax || !window.myavanaAjax.websocket_url) {
                Myavana.log('WebSocket URL not configured, skipping real-time features');
                return;
            }

            try {
                this.websocket = new WebSocket(window.myavanaAjax.websocket_url);

                this.websocket.onopen = () => {
                    this.isConnected = true;
                    this.reconnectAttempts = 0;
                    Myavana.log('WebSocket connected');

                    // Send authentication
                    this.send({
                        type: 'auth',
                        user_id: window.myavanaAjax.user_id,
                        nonce: window.myavanaAjax.nonce
                    });

                    Myavana.Events.trigger('realtime:connected');
                };

                this.websocket.onmessage = (event) => {
                    try {
                        const data = JSON.parse(event.data);
                        this.handleMessage(data);
                    } catch (error) {
                        Myavana.error('WebSocket message parse error:', error);
                    }
                };

                this.websocket.onclose = () => {
                    this.isConnected = false;
                    Myavana.log('WebSocket disconnected');
                    this.attemptReconnect();
                    Myavana.Events.trigger('realtime:disconnected');
                };

                this.websocket.onerror = (error) => {
                    Myavana.error('WebSocket error:', error);
                    Myavana.Events.trigger('realtime:error', { error });
                };

            } catch (error) {
                Myavana.error('WebSocket initialization failed:', error);
            }
        },

        setupHeartbeat: function() {
            this.heartbeatInterval = setInterval(() => {
                if (this.isConnected) {
                    this.send({ type: 'ping' });
                }
            }, 30000); // 30 seconds
        },

        handleMessage: function(data) {
            switch (data.type) {
                case 'data_update':
                    // Update local data and trigger events
                    if (data.key && data.value) {
                        Myavana.Data.set(data.key, data.value, false); // Don't trigger local events
                        Myavana.Events.trigger('realtime:data_updated', data);
                    }
                    break;

                case 'user_activity':
                    Myavana.Events.trigger('realtime:user_activity', data);
                    break;

                case 'notification':
                    if (Myavana.UI && data.message) {
                        Myavana.UI.notify(data.message, data.type || 'info');
                    }
                    break;

                case 'pong':
                    // Heartbeat response
                    break;

                default:
                    Myavana.Events.trigger('realtime:message', data);
            }
        },

        send: function(data) {
            if (this.isConnected && this.websocket) {
                this.websocket.send(JSON.stringify(data));
            }
        },

        attemptReconnect: function() {
            if (this.reconnectAttempts < this.maxReconnectAttempts) {
                this.reconnectAttempts++;
                const delay = Math.pow(2, this.reconnectAttempts) * 1000; // Exponential backoff

                setTimeout(() => {
                    Myavana.log(`Attempting WebSocket reconnect (${this.reconnectAttempts}/${this.maxReconnectAttempts})`);
                    this.setupWebSocket();
                }, delay);
            }
        },

        broadcast: function(type, data) {
            this.send({
                type: 'broadcast',
                event_type: type,
                data: data,
                user_id: window.myavanaAjax.user_id
            });
        }
    };

    /**
     * Performance Monitoring & Optimization
     */
    Myavana.Performance = {
        metrics: new Map(),
        observers: {},

        init: function() {
            this.setupPerformanceObservers();
            this.setupMetricsCollection();
            this.setupLazyLoading();
            Myavana.log('Performance monitoring initialized');
        },

        setupPerformanceObservers: function() {
            // Intersection Observer for lazy loading
            if ('IntersectionObserver' in window) {
                this.observers.intersection = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            Myavana.Events.trigger('element:visible', { element: entry.target });
                        }
                    });
                }, { threshold: 0.1 });
            }

            // Performance Observer for metrics
            if ('PerformanceObserver' in window) {
                try {
                    this.observers.performance = new PerformanceObserver((list) => {
                        list.getEntries().forEach(entry => {
                            this.recordMetric(entry.name, entry.duration);
                        });
                    });
                    this.observers.performance.observe({ entryTypes: ['measure', 'navigation'] });
                } catch (error) {
                    Myavana.log('Performance Observer not supported');
                }
            }
        },

        setupMetricsCollection: function() {
            // Collect Core Web Vitals
            this.measureCLS();
            this.measureLCP();
            this.measureFID();
        },

        measureCLS: function() {
            let clsValue = 0;
            let sessionValue = 0;
            let sessionEntries = [];

            if ('LayoutShift' in window) {
                new PerformanceObserver((list) => {
                    for (const entry of list.getEntries()) {
                        if (!entry.hadRecentInput) {
                            const firstSessionEntry = sessionEntries[0];
                            const lastSessionEntry = sessionEntries[sessionEntries.length - 1];

                            if (sessionValue && entry.startTime - lastSessionEntry.startTime < 1000 &&
                                entry.startTime - firstSessionEntry.startTime < 5000) {
                                sessionValue += entry.value;
                                sessionEntries.push(entry);
                            } else {
                                sessionValue = entry.value;
                                sessionEntries = [entry];
                            }

                            if (sessionValue > clsValue) {
                                clsValue = sessionValue;
                                this.recordMetric('CLS', clsValue);
                            }
                        }
                    }
                }).observe({ type: 'layout-shift', buffered: true });
            }
        },

        measureLCP: function() {
            if ('LargestContentfulPaint' in window) {
                new PerformanceObserver((list) => {
                    const entries = list.getEntries();
                    const lastEntry = entries[entries.length - 1];
                    this.recordMetric('LCP', lastEntry.startTime);
                }).observe({ type: 'largest-contentful-paint', buffered: true });
            }
        },

        measureFID: function() {
            if ('FirstInputDelay' in window) {
                new PerformanceObserver((list) => {
                    for (const entry of list.getEntries()) {
                        this.recordMetric('FID', entry.processingStart - entry.startTime);
                    }
                }).observe({ type: 'first-input', buffered: true });
            }
        },

        recordMetric: function(name, value) {
            this.metrics.set(name, {
                value: value,
                timestamp: Date.now()
            });

            // Send to analytics if threshold exceeded
            this.checkThresholds(name, value);
        },

        checkThresholds: function(name, value) {
            const thresholds = {
                'CLS': 0.1,
                'LCP': 2500,
                'FID': 100
            };

            if (thresholds[name] && value > thresholds[name]) {
                Myavana.Analytics.track('performance_threshold_exceeded', {
                    metric: name,
                    value: value,
                    threshold: thresholds[name]
                });
            }
        },

        setupLazyLoading: function() {
            // Auto-detect and lazy load images
            document.addEventListener('DOMContentLoaded', () => {
                const images = document.querySelectorAll('img[data-src]');
                images.forEach(img => {
                    if (this.observers.intersection) {
                        this.observers.intersection.observe(img);
                    }
                });

                // Listen for new images
                Myavana.Events.on('element:visible', (data) => {
                    const img = data.element;
                    if (img.tagName === 'IMG' && img.dataset.src) {
                        this.loadImage(img);
                    }
                });
            });
        },

        loadImage: function(img) {
            const src = img.dataset.src;
            if (src) {
                img.src = src;
                img.removeAttribute('data-src');
                this.observers.intersection.unobserve(img);

                img.onload = () => {
                    img.classList.add('loaded');
                };
            }
        },

        // Code splitting utilities
        loadComponent: function(componentName) {
            return new Promise((resolve, reject) => {
                if (Myavana.Components.has(componentName)) {
                    resolve(Myavana.Components.get(componentName));
                    return;
                }

                // Dynamic import simulation for WordPress
                const script = document.createElement('script');
                script.src = `${window.myavanaAjax.plugin_url}assets/js/components/${componentName}.js`;
                script.onload = () => {
                    resolve(Myavana.Components.get(componentName));
                };
                script.onerror = () => {
                    reject(new Error(`Failed to load component: ${componentName}`));
                };
                document.head.appendChild(script);
            });
        }
    };

    /**
     * Progressive Web App Features
     */
    Myavana.PWA = {
        serviceWorker: null,
        isOnline: navigator.onLine,

        init: function() {
            this.registerServiceWorker();
            this.setupOfflineHandling();
            this.setupInstallPrompt();
            Myavana.log('PWA features initialized');
        },

        registerServiceWorker: function() {
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.register(`${window.myavanaAjax.plugin_url}sw.js`)
                    .then((registration) => {
                        this.serviceWorker = registration;
                        Myavana.log('Service Worker registered');

                        // Listen for updates
                        registration.addEventListener('updatefound', () => {
                            const newWorker = registration.installing;
                            newWorker.addEventListener('statechange', () => {
                                if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                    this.showUpdateNotification();
                                }
                            });
                        });
                    })
                    .catch((error) => {
                        Myavana.log('Service Worker registration failed:', error);
                    });
            }
        },

        setupOfflineHandling: function() {
            window.addEventListener('online', () => {
                this.isOnline = true;
                Myavana.Events.trigger('pwa:online');
                Myavana.UI.notify('Connection restored', 'success');
                this.syncOfflineData();
            });

            window.addEventListener('offline', () => {
                this.isOnline = false;
                Myavana.Events.trigger('pwa:offline');
                Myavana.UI.notify('Working offline', 'warning');
            });
        },

        setupInstallPrompt: function() {
            let deferredPrompt;

            window.addEventListener('beforeinstallprompt', (e) => {
                e.preventDefault();
                deferredPrompt = e;
                this.showInstallBanner(deferredPrompt);
            });
        },

        showInstallBanner: function(deferredPrompt) {
            // Only show once per session
            if (sessionStorage.getItem('myavana_install_dismissed')) return;

            const banner = document.createElement('div');
            banner.className = 'myavana-install-banner';
            banner.innerHTML = `
                <div class="install-content">
                    <span class="install-icon">📱</span>
                    <div class="install-text">
                        <strong>Install MYAVANA</strong>
                        <p>Get quick access to your hair journey</p>
                    </div>
                    <div class="install-actions">
                        <button class="myavana-btn myavana-btn-primary install-btn">Install</button>
                        <button class="myavana-btn myavana-btn-outline dismiss-btn">Not now</button>
                    </div>
                </div>
            `;

            // Style the banner
            const style = document.createElement('style');
            style.textContent = `
                .myavana-install-banner {
                    position: fixed;
                    bottom: 20px;
                    left: 20px;
                    right: 20px;
                    background: var(--myavana-white);
                    border: 2px solid var(--myavana-coral);
                    border-radius: 12px;
                    box-shadow: 0 8px 25px rgba(34, 35, 35, 0.15);
                    z-index: var(--myavana-z-notification);
                    animation: slideUp 0.3s ease;
                }

                .install-content {
                    display: flex;
                    align-items: center;
                    gap: 16px;
                    padding: 16px;
                }

                .install-icon {
                    font-size: 24px;
                }

                .install-text strong {
                    font-family: 'Archivo Black', sans-serif;
                    color: var(--myavana-onyx);
                    font-size: 14px;
                }

                .install-text p {
                    font-family: 'Archivo', sans-serif;
                    color: var(--myavana-blueberry);
                    font-size: 12px;
                    margin: 2px 0 0 0;
                }

                .install-actions {
                    display: flex;
                    gap: 8px;
                    margin-left: auto;
                }

                @keyframes slideUp {
                    from { transform: translateY(100%); opacity: 0; }
                    to { transform: translateY(0); opacity: 1; }
                }

                @media (max-width: 768px) {
                    .myavana-install-banner {
                        left: 10px;
                        right: 10px;
                        bottom: 80px; /* Above navigation */
                    }

                    .install-content {
                        flex-direction: column;
                        text-align: center;
                        gap: 12px;
                    }

                    .install-actions {
                        margin-left: 0;
                        width: 100%;
                    }

                    .install-actions button {
                        flex: 1;
                    }
                }
            `;
            document.head.appendChild(style);

            // Event handlers
            banner.querySelector('.install-btn').addEventListener('click', () => {
                deferredPrompt.prompt();
                deferredPrompt.userChoice.then((choiceResult) => {
                    Myavana.Analytics.track('pwa_install_prompt', { result: choiceResult.outcome });
                    deferredPrompt = null;
                    banner.remove();
                });
            });

            banner.querySelector('.dismiss-btn').addEventListener('click', () => {
                sessionStorage.setItem('myavana_install_dismissed', 'true');
                banner.remove();
            });

            document.body.appendChild(banner);
        },

        showUpdateNotification: function() {
            Myavana.UI.confirm('A new version is available. Update now?', {
                title: 'Update Available',
                confirmText: 'Update'
            }).then((confirmed) => {
                if (confirmed) {
                    window.location.reload();
                }
            });
        },

        syncOfflineData: function() {
            // Sync any offline data when connection is restored
            const offlineData = Myavana.Data.get('offline_queue', []);
            if (offlineData.length > 0) {
                Myavana.log('Syncing offline data...');

                offlineData.forEach(async (item) => {
                    try {
                        await Myavana.API.call(item.endpoint, item.data);
                    } catch (error) {
                        Myavana.error('Failed to sync offline data:', error);
                    }
                });

                Myavana.Data.remove('offline_queue');
                Myavana.UI.notify('Data synchronized', 'success');
            }
        }
    };

    /**
     * Advanced Analytics & Tracking
     */
    Myavana.Analytics = {
        sessionId: null,
        events: [],

        init: function() {
            this.sessionId = this.generateSessionId();
            this.setupEventTracking();
            this.setupUserBehaviorTracking();
            this.startSession();
            Myavana.log('Analytics initialized');
        },

        generateSessionId: function() {
            return Date.now().toString(36) + Math.random().toString(36).substr(2);
        },

        startSession: function() {
            this.track('session_start', {
                user_agent: navigator.userAgent,
                screen_resolution: `${screen.width}x${screen.height}`,
                viewport: `${window.innerWidth}x${window.innerHeight}`,
                referrer: document.referrer,
                timestamp: Date.now()
            });
        },

        track: function(event, properties = {}) {
            const eventData = {
                event: event,
                properties: {
                    ...properties,
                    session_id: this.sessionId,
                    user_id: window.myavanaAjax?.user_id,
                    timestamp: Date.now(),
                    page_url: window.location.href,
                    page_title: document.title
                }
            };

            this.events.push(eventData);

            // Send immediately for critical events, batch others
            const criticalEvents = ['error', 'performance_threshold_exceeded', 'user_conversion'];
            if (criticalEvents.includes(event)) {
                this.flush();
            }

            Myavana.log('Analytics event tracked:', event, properties);
        },

        setupEventTracking: function() {
            // Track framework events
            Myavana.Events.on('framework:ready', () => {
                this.track('framework_ready');
            });

            Myavana.Events.on('nav:navigate', (data) => {
                this.track('navigation', { route: data.route });
            });

            Myavana.Events.on('modal:open', (data) => {
                this.track('modal_interaction', { action: 'open', modal: data.title });
            });

            // Track user interactions
            document.addEventListener('click', (e) => {
                if (e.target.classList.contains('myavana-btn')) {
                    this.track('button_click', {
                        button_text: e.target.textContent.trim(),
                        button_class: e.target.className
                    });
                }
            });
        },

        setupUserBehaviorTracking: function() {
            let scrollDepth = 0;
            let timeOnPage = Date.now();

            // Scroll depth tracking
            window.addEventListener('scroll', this.throttle(() => {
                const depth = Math.round((window.scrollY / (document.body.scrollHeight - window.innerHeight)) * 100);
                if (depth > scrollDepth && depth % 25 === 0) {
                    scrollDepth = depth;
                    this.track('scroll_depth', { depth: depth });
                }
            }, 1000));

            // Time on page
            window.addEventListener('beforeunload', () => {
                this.track('page_exit', {
                    time_on_page: Date.now() - timeOnPage,
                    scroll_depth: scrollDepth
                });
                this.flush();
            });

            // Form interactions
            document.addEventListener('submit', (e) => {
                if (e.target.classList.contains('myavana-form')) {
                    this.track('form_submit', {
                        form_id: e.target.id,
                        form_action: e.target.action
                    });
                }
            });
        },

        throttle: function(func, limit) {
            let inThrottle;
            return function() {
                const args = arguments;
                const context = this;
                if (!inThrottle) {
                    func.apply(context, args);
                    inThrottle = true;
                    setTimeout(() => inThrottle = false, limit);
                }
            };
        },

        // A/B Testing Framework
        getVariant: function(testName, variants = ['A', 'B']) {
            const userId = window.myavanaAjax?.user_id || 'anonymous';
            const hash = this.hashCode(userId + testName);
            const variantIndex = Math.abs(hash) % variants.length;
            const variant = variants[variantIndex];

            this.track('ab_test_assignment', {
                test_name: testName,
                variant: variant
            });

            return variant;
        },

        hashCode: function(str) {
            let hash = 0;
            for (let i = 0; i < str.length; i++) {
                const char = str.charCodeAt(i);
                hash = ((hash << 5) - hash) + char;
                hash = hash & hash; // Convert to 32-bit integer
            }
            return hash;
        },

        flush: function() {
            if (this.events.length === 0) return;

            const eventsToSend = [...this.events];
            this.events = [];

            // Send to WordPress backend
            if (window.myavanaAjax) {
                fetch(window.myavanaAjax.ajax_url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'myavana_track_analytics',
                        nonce: window.myavanaAjax.nonce,
                        events: JSON.stringify(eventsToSend)
                    })
                }).catch(error => {
                    Myavana.error('Analytics flush failed:', error);
                    // Re-add events to queue
                    this.events.unshift(...eventsToSend);
                });
            }
        }
    };

    // Auto-flush analytics every 30 seconds
    setInterval(() => {
        if (Myavana.Analytics && Myavana.Analytics.flush) {
            Myavana.Analytics.flush();
        }
    }, 30000);

})(window, jQuery);