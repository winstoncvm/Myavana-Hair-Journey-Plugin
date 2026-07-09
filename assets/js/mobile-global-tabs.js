(function($) {
    'use strict';

    function hasKommunicateLauncher() {
        const selectors = [
            '#kommunicate-widget-launcher',
            '.mck-sidebox-launcher',
            '.mck-sidebox-launcher-icon',
            'iframe[title*="Kommunicate"]',
            'iframe[src*="kommunicate"]',
            'div[id^="kommunicate"]',
            'div[id*="kommunicate-widget"]'
        ];

        return selectors.some((selector) => document.querySelector(selector));
    }

    function syncChatbotState() {
        document.body.classList.toggle('myavana-chatbot-present', hasKommunicateLauncher());
    }

    function setBodyState() {
        const hasMobileViewport = window.matchMedia('(max-width: 991px)').matches;
        document.body.classList.toggle('myavana-global-tabs-visible', hasMobileViewport);
    }

    function hasOpenOffcanvas() {
        const openSelectors = [
            '.offcanvas-hjn.is-open',
            '.offcanvas-hjn.active',
            '.offcanvas.active',
            '.offcanvas-overlay.active',
            '.offcanvas-overlay-hjn.is-open',
            '.offcanvas-overlay-hjn.active',
            '#createOffcanvasOverlay.is-open',
            '#createOffcanvasOverlay.active',
            '#viewOffcanvasOverlay.active',
            '#offcanvasOverlay.active',
            '#myavanaUpEditOffcanvas.active',
            '.myavana-up-edit-offcanvas.active'
        ];

        return openSelectors.some(function(selector) {
            return document.querySelector(selector);
        });
    }

    function syncOffcanvasState() {
        const hasMobileViewport = window.matchMedia('(max-width: 991px)').matches;
        const isOpen = hasMobileViewport && hasOpenOffcanvas();
        document.body.classList.toggle('myavana-offcanvas-open', isOpen);
    }

    function syncTabsCollapsedState(collapsed) {
        const toggle = document.getElementById('myavanaMobileTabsToggle');
        document.body.classList.toggle('myavana-mobile-tabs-collapsed', collapsed);
        if (toggle) {
            toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
            toggle.setAttribute('aria-label', collapsed ? 'Expand navigation tabs' : 'Minimize navigation tabs');
        }
    }

    function bindOffcanvasObservers() {
        if (!window.MutationObserver) {
            return;
        }

        const offcanvasSelectors = [
            '.offcanvas-hjn',
            '.offcanvas',
            '.offcanvas-overlay',
            '.offcanvas-overlay-hjn',
            '#createOffcanvasOverlay',
            '#viewOffcanvasOverlay',
            '#offcanvasOverlay',
            '#myavanaUpEditOffcanvas',
            '.myavana-up-edit-offcanvas'
        ];

        const observer = new MutationObserver(function() {
            syncOffcanvasState();
        });

        document.querySelectorAll(offcanvasSelectors.join(',')).forEach(function(node) {
            observer.observe(node, {
                attributes: true,
                attributeFilter: ['class', 'style']
            });
        });
    }

    function syncActiveState() {
        const nav = document.getElementById('myavanaGlobalMobileTabs');
        if (!nav) {
            return;
        }

        const localizedActive = window.myavanaMobileTabsData && window.myavanaMobileTabsData.activePage
            ? window.myavanaMobileTabsData.activePage
            : '';
        const activePage = localizedActive || nav.getAttribute('data-active-page') || '';

        nav.querySelectorAll('.myavana-global-mobile-tab').forEach((tab) => {
            const page = tab.getAttribute('data-page');
            const pageGroup = tab.getAttribute('data-page-group');
            let isActive = page === activePage;

            if (!isActive && pageGroup) {
                isActive = pageGroup
                    .split(',')
                    .map((item) => item.trim())
                    .filter(Boolean)
                    .includes(activePage);
            }

            tab.classList.toggle('is-active', isActive);
            tab.setAttribute('aria-current', isActive ? 'page' : 'false');
        });
    }

    function setupMoreMenu() {
        const nav = document.getElementById('myavanaGlobalMobileTabs');
        const toggle = document.getElementById('myavanaMobileMoreToggle');
        const menu = document.getElementById('myavanaMobileMoreMenu');
        if (!nav || !toggle || !menu) {
            return;
        }

        const closeMenu = function() {
            menu.classList.remove('is-open');
            toggle.setAttribute('aria-expanded', 'false');
            menu.hidden = true;
        };

        const openMenu = function() {
            menu.hidden = false;
            window.requestAnimationFrame(function() {
                menu.classList.add('is-open');
            });
            toggle.setAttribute('aria-expanded', 'true');
        };

        toggle.addEventListener('click', function(event) {
            event.preventDefault();
            event.stopPropagation();
            const isOpen = menu.classList.contains('is-open');
            if (isOpen) {
                closeMenu();
                return;
            }
            openMenu();
        });

        document.addEventListener('click', function(event) {
            if (!nav.contains(event.target)) {
                closeMenu();
            }
        });

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeMenu();
            }
        });

        menu.querySelectorAll('a').forEach(function(link) {
            link.addEventListener('click', function() {
                closeMenu();
            });
        });

        window.addEventListener('resize', closeMenu);

        return closeMenu;
    }

    function setupCollapseToggle(closeMoreMenu) {
        const nav = document.getElementById('myavanaGlobalMobileTabs');
        const toggle = document.getElementById('myavanaMobileTabsToggle');

        if (!nav || !toggle) {
            return;
        }

        const syncDefaultState = function() {
            const isMobile = window.matchMedia('(max-width: 991px)').matches;
            if (!isMobile) {
                document.body.classList.remove('myavana-mobile-tabs-collapsed');
                toggle.setAttribute('aria-expanded', 'true');
                return;
            }

            syncTabsCollapsedState(false);
        };

        toggle.addEventListener('click', function(event) {
            event.preventDefault();
            event.stopPropagation();

            const collapsed = !document.body.classList.contains('myavana-mobile-tabs-collapsed');
            syncTabsCollapsedState(collapsed);
            if (collapsed && typeof closeMoreMenu === 'function') {
                closeMoreMenu();
            }
        });

        window.addEventListener('resize', syncDefaultState);
        syncDefaultState();
    }

    $(document).ready(function() {
        if (!document.getElementById('myavanaGlobalMobileTabs')) {
            return;
        }

        setBodyState();
        syncActiveState();
        syncChatbotState();
        syncOffcanvasState();
        const closeMoreMenu = setupMoreMenu();
        setupCollapseToggle(closeMoreMenu);
        $(window).on('resize.myavanaMobileTabs', function() {
            setBodyState();
            syncOffcanvasState();
        });

        // Chat launcher may mount after page load.
        let checks = 0;
        const maxChecks = 20;
        const poll = window.setInterval(function() {
            syncChatbotState();
            syncOffcanvasState();
            checks += 1;
            if (checks >= maxChecks) {
                window.clearInterval(poll);
            }
        }, 800);

        if (window.MutationObserver) {
            const bodyObserver = new MutationObserver(function() {
                syncChatbotState();
                syncOffcanvasState();
            });

            bodyObserver.observe(document.body, {
                childList: true,
                subtree: true
            });
        }

        bindOffcanvasObservers();
    });
})(jQuery);
