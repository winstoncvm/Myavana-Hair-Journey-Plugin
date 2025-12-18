/**
 * MYAVANA Timeline - Main Initialization & Orchestration
 * Coordinates all timeline modules and sets up event listeners
 *
 * @package Myavana_Hair_Journey
 * @version 2.3.5
 */

(function() {
    'use strict';

    // Wait for DOM and all modules to be ready
    document.addEventListener('DOMContentLoaded', function() {
        console.log('[Timeline Init] Starting initialization...');

        // Always hide loader on initialization (in case it's stuck)
        try {
            // Try using the global hide function first
            if (typeof window.hideMyavanaLoader === 'function') {
                window.hideMyavanaLoader();
                console.log('[Timeline Init] Loader hidden via global function');
            } else if (jQuery && jQuery('#myavanaLoader').length) {
                jQuery('#myavanaLoader').fadeOut(200, function() {
                    jQuery(this).remove();
                });
                console.log('[Timeline Init] Loader hidden via jQuery');
            }
        } catch (e) {
            // Fallback: force hide with vanilla JS
            const loader = document.getElementById('myavanaLoader');
            if (loader) {
                loader.style.display = 'none';
                loader.style.opacity = '0';
                setTimeout(() => loader.remove(), 300);
                console.log('[Timeline Init] Loader force-hidden (fallback)');
            }
        }

        // Verify all required modules are loaded
        if (!verifyModules()) {
            console.error('[Timeline Init] Required modules missing! Initialization aborted.');
            return;
        }

        console.log('[Timeline Init] All modules verified ✓');

        // Initialize modules in correct order
        initializeModules();

        // Setup global event listeners
        setupEventListeners();

        // Setup keyboard shortcuts
        setupKeyboardShortcuts();

        // Set initial view
        setInitialView();

        console.log('[Timeline Init] Initialization complete ✓');
    });

    /**
     * Verify all required modules are loaded
     */
    function verifyModules() {
        const requiredModules = [
            'State',
            'UI',
            'Offcanvas',
            'Navigation',
            'ListView',
            'View',
            'Forms',
            'Filters',
            'Comparison'
        ];

        let allPresent = true;
        requiredModules.forEach(function(moduleName) {
            if (!MyavanaTimeline[moduleName]) {
                console.error('[Timeline Init] Missing module:', moduleName);
                allPresent = false;
            }
        });

        return allPresent;
    }

    /**
     * Initialize all modules in correct order
     */
    function initializeModules() {
        console.log('[Timeline Init] Initializing modules...');

        // 1. State management (already initialized on load)
        console.log('  ✓ State module ready');

        // 2. UI State (theme, sidebar)
        if (MyavanaTimeline.UI && MyavanaTimeline.UI.init) {
            MyavanaTimeline.UI.init();
            console.log('  ✓ UI State initialized');
        }

        // 3. Navigation (slider, view switching)
        if (MyavanaTimeline.Navigation && MyavanaTimeline.Navigation.init) {
            MyavanaTimeline.Navigation.init();
            console.log('  ✓ Navigation initialized');
        }

        // 4. List View
        if (MyavanaTimeline.ListView && MyavanaTimeline.ListView.init) {
            MyavanaTimeline.ListView.init();
            console.log('  ✓ List View initialized');
        }

        // 5. Offcanvas (modals)
        if (MyavanaTimeline.Offcanvas && MyavanaTimeline.Offcanvas.initClickHandlers) {
            MyavanaTimeline.Offcanvas.initClickHandlers();
            console.log('  ✓ Offcanvas initialized');
        }

        // 6. Forms (create/edit)
        if (MyavanaTimeline.Forms && MyavanaTimeline.Forms.init) {
            MyavanaTimeline.Forms.init();
            console.log('  ✓ Forms initialized');
        }

        // 7. Filters
        if (MyavanaTimeline.Filters && MyavanaTimeline.Filters.initialize) {
            MyavanaTimeline.Filters.initialize();
            console.log('  ✓ Filters initialized');
        }

        // 8. Comparison (doesn't need init)
        console.log('  ✓ Comparison module ready');
    }

    /**
     * Setup global event listeners
     */
    function setupEventListeners() {
        console.log('[Timeline Init] Setting up event listeners...');

        // Theme toggle
        const themeToggleBtn = document.getElementById('themeToggle');
        if (themeToggleBtn) {
            themeToggleBtn.addEventListener('click', MyavanaTimeline.UI.toggleDarkMode);
            console.log('  ✓ Theme toggle');
        }

        // View buttons (header)
        document.querySelectorAll('.view-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const viewName = this.getAttribute('data-view');
                MyavanaTimeline.Navigation.switchView(viewName);
            });
        });
        console.log('  ✓ View buttons');

        // Timeline control tabs
        document.querySelectorAll('.tab').forEach(function(tab) {
            tab.addEventListener('click', function() {
                const onclickAttr = this.getAttribute('onclick');
                if (onclickAttr) {
                    const match = onclickAttr.match(/'([^']+)'/);
                    if (match) {
                        MyavanaTimeline.Navigation.switchView(match[1]);
                    }
                }
            });
        });
        console.log('  ✓ Timeline tabs');

        // Overlay click handlers
        const offcanvasOverlay = document.getElementById('offcanvasOverlay');
        if (offcanvasOverlay) {
            offcanvasOverlay.addEventListener('click', MyavanaTimeline.Offcanvas.close);
        }

        const viewOffcanvasOverlay = document.getElementById('viewOffcanvasOverlay');
        if (viewOffcanvasOverlay) {
            viewOffcanvasOverlay.addEventListener('click', MyavanaTimeline.Offcanvas.closeView);
        }
        console.log('  ✓ Overlay handlers');

        // Carousel items
        document.querySelectorAll('.carousel-item').forEach(function(item) {
            item.addEventListener('click', function() {
                document.querySelectorAll('.carousel-item').forEach(function(i) {
                    i.classList.remove('active');
                });
                this.classList.add('active');
            });
        });

        // Date markers
        document.querySelectorAll('.date-marker').forEach(function(marker) {
            marker.addEventListener('click', function() {
                const index = parseInt(this.dataset.index);
                const splide = MyavanaTimeline.State.get('splide');
                if (splide) {
                    splide.go(index);
                }
            });
        });

        // Goal items
        document.querySelectorAll('.goal-item').forEach(function(item) {
            item.addEventListener('click', function() {
                document.querySelectorAll('.goal-item').forEach(function(i) {
                    i.classList.remove('active');
                });
                this.classList.add('active');
            });
        });

        // Window resize
        window.addEventListener('resize', MyavanaTimeline.UI.handleResize);

        console.log('  ✓ All event listeners attached');
    }

    /**
     * Setup keyboard shortcuts
     */
    function setupKeyboardShortcuts() {
        document.addEventListener('keydown', function(e) {
            // Escape key - close any open offcanvas
            if (e.key === 'Escape') {
                MyavanaTimeline.Offcanvas.close();
            }
        });

        console.log('  ✓ Keyboard shortcuts enabled');
    }

    /**
     * Set initial view
     */
    function setInitialView() {
        // Set calendar as default view on page load
        MyavanaTimeline.Navigation.switchView('calendar');
        MyavanaTimeline.Navigation.setCalendarView('month');
        console.log('  ✓ Initial view set (Calendar - Month)');
    }

    /**
     * Public initialization API
     */
    window.MyavanaTimeline = window.MyavanaTimeline || {};
    MyavanaTimeline.init = function() {
        console.log('[Timeline Init] Manual initialization triggered');
        if (document.readyState === 'loading') {
            console.warn('[Timeline Init] DOM not ready yet, waiting...');
            return;
        }
        initializeModules();
        setupEventListeners();
        setupKeyboardShortcuts();
        setInitialView();
    };

    /**
     * Debug helper
     */
    MyavanaTimeline.debug = function() {
        console.log('=== MYAVANA Timeline Debug Info ===');
        console.log('State:', MyavanaTimeline.State.dump());
        console.log('Modules loaded:', Object.keys(MyavanaTimeline));
        console.log('Current view:', document.querySelector('.view-content.active')?.id);
        console.log('Current offcanvas:', MyavanaTimeline.State.get('currentOffcanvas'));
        console.log('===================================');
    };

})();
