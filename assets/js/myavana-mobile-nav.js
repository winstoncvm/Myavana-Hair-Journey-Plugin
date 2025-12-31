/**
 * MYAVANA Mobile Navigation System
 * Global mobile-first navigation with gesture support
 *
 * @package Myavana_Hair_Journey
 * @version 1.0.0
 */

(function($) {
    'use strict';

    // Global mobile navigation state
    window.MyavanaMobileNav = {
        currentPage: 'home',
        isVisible: true,
        createMenuOpen: false,
        gesturesEnabled: true
    };

    /**
     * Initialize Mobile Navigation
     */
    function initMobileNavigation() {
        if (isMobileNavigationPresent()) {
            console.log('[Mobile Nav] Already initialized');
            return;
        }

        injectMobileNavigation();
        bindNavigationEvents();
        detectCurrentPage();
        initializeGestures();

        console.log('[Mobile Nav] Initialized successfully');
    }

    /**
     * Check if mobile nav already exists
     */
    function isMobileNavigationPresent() {
        return $('#myavana-mobile-bottom-nav').length > 0;
    }

    /**
     * Inject Mobile Navigation HTML
     */
    function injectMobileNavigation() {
        const navHTML = `
            <!-- Global Mobile Bottom Navigation -->
            <nav id="myavana-mobile-bottom-nav" class="myavana-mobile-nav">
                <a href="${getPageUrl('home')}" class="myavana-nav-item" data-page="home">
                    <svg class="myavana-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                        <polyline points="9 22 9 12 15 12 15 22"></polyline>
                    </svg>
                    <span>Home</span>
                </a>

                <a href="${getPageUrl('journey')}" class="myavana-nav-item" data-page="journey">
                    <svg class="myavana-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="20" x2="18" y2="10"></line>
                        <line x1="12" y1="20" x2="12" y2="4"></line>
                        <line x1="6" y1="20" x2="6" y2="14"></line>
                    </svg>
                    <span>Journey</span>
                </a>

                <button class="myavana-nav-item myavana-create-btn" data-page="create">
                    <svg class="myavana-nav-icon myavana-create-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="16"></line>
                        <line x1="8" y1="12" x2="16" y2="12"></line>
                    </svg>
                    <span>Create</span>
                </button>

                <a href="${getPageUrl('community')}" class="myavana-nav-item" data-page="community">
                    <svg class="myavana-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                    <span>Community</span>
                </a>

                <a href="${getPageUrl('profile')}" class="myavana-nav-item" data-page="profile">
                    <svg class="myavana-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    <span>Profile</span>
                </a>
            </nav>

            <!-- Create Menu Modal -->
            <div id="myavana-create-menu-modal" class="myavana-modal" style="display: none;">
                <div class="myavana-modal-overlay"></div>
                <div class="myavana-create-menu-content">
                    <div class="myavana-create-menu-header">
                        <h3>Create</h3>
                        <button class="myavana-close-btn">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </button>
                    </div>
                    <div class="myavana-create-options">
                        <button class="myavana-create-option" data-action="entry">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 20h4L18.5 9.5a2.828 2.828 0 1 0-4-4L4 16v4z"></path>
                                <path d="M13.5 6.5l4 4"></path>
                            </svg>
                            <div>
                                <strong>Create Entry</strong>
                                <small>Log your hair journey progress</small>
                            </div>
                        </button>

                        <button class="myavana-create-option" data-action="smart-entry">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                                <line x1="12" y1="17" x2="12.01" y2="17"></line>
                            </svg>
                            <div>
                                <strong>Smart Entry (AI)</strong>
                                <small>AI-powered analysis with photo</small>
                            </div>
                        </button>

                        <button class="myavana-create-option" data-action="goal">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                            </svg>
                            <div>
                                <strong>Create Goal</strong>
                                <small>Set a new hair care goal</small>
                            </div>
                        </button>

                        <button class="myavana-create-option" data-action="routine">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                            <div>
                                <strong>Create Routine</strong>
                                <small>Define your hair care routine</small>
                            </div>
                        </button>

                        <button class="myavana-create-option" data-action="story">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                                <circle cx="12" cy="13" r="4"></circle>
                            </svg>
                            <div>
                                <strong>Create Story</strong>
                                <small>Share a 24-hour story</small>
                            </div>
                        </button>

                        <button class="myavana-create-option" data-action="post">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                <polyline points="21 15 16 10 5 21"></polyline>
                            </svg>
                            <div>
                                <strong>Community Post</strong>
                                <small>Share with the community</small>
                            </div>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Pull to Refresh Indicator -->
            <div id="myavana-pull-refresh" class="myavana-pull-refresh" style="display: none;">
                <div class="myavana-pull-refresh-spinner"></div>
                <span class="myavana-pull-refresh-text">Pull to refresh</span>
            </div>
        `;

        $('body').append(navHTML);
    }

    /**
     * Get page URL from navigation item
     */
    function getPageUrl(page) {
        const urls = {
            home: window.location.origin,
            journey: window.location.origin + '/hair-journey/',
            community: window.location.origin + '/community/',
            profile: window.location.origin + '/profile/'
        };
        return urls[page] || window.location.origin;
    }

    /**
     * Bind Navigation Events
     */
    function bindNavigationEvents() {
        // Create button
        $('.myavana-create-btn').on('click', function(e) {
            e.preventDefault();
            openCreateMenu();
        });

        // Create menu overlay close
        $('#myavana-create-menu-modal .myavana-modal-overlay, #myavana-create-menu-modal .myavana-close-btn').on('click', function() {
            closeCreateMenu();
        });

        // Create options
        $('.myavana-create-option').on('click', function() {
            const action = $(this).data('action');
            handleCreateAction(action);
            closeCreateMenu();
        });

        // Page navigation tracking
        $('.myavana-nav-item[data-page]').on('click', function() {
            const page = $(this).data('page');
            if (page !== 'create') {
                MyavanaMobileNav.currentPage = page;
            }
        });

        // Hide/show nav on scroll
        let lastScrollTop = 0;
        $(window).on('scroll', function() {
            const scrollTop = $(this).scrollTop();

            if (scrollTop > lastScrollTop && scrollTop > 100) {
                // Scrolling down - hide nav
                hideBottomNav();
            } else {
                // Scrolling up - show nav
                showBottomNav();
            }

            lastScrollTop = scrollTop;
        });
    }

    /**
     * Detect Current Page
     */
    function detectCurrentPage() {
        const path = window.location.pathname;
        let currentPage = 'home';

        if (path.includes('hair-journey')) {
            currentPage = 'journey';
        } else if (path.includes('community')) {
            currentPage = 'community';
        } else if (path.includes('profile')) {
            currentPage = 'profile';
        }

        MyavanaMobileNav.currentPage = currentPage;
        $(`.myavana-nav-item[data-page="${currentPage}"]`).addClass('active');
    }

    /**
     * Open Create Menu
     */
    function openCreateMenu() {
        $('#myavana-create-menu-modal').fadeIn(200);
        MyavanaMobileNav.createMenuOpen = true;
        $('body').addClass('myavana-modal-open');
    }

    /**
     * Close Create Menu
     */
    function closeCreateMenu() {
        $('#myavana-create-menu-modal').fadeOut(200);
        MyavanaMobileNav.createMenuOpen = false;
        $('body').removeClass('myavana-modal-open');
    }

    /**
     * Handle Create Action
     */
    function handleCreateAction(action) {
        switch (action) {
            case 'entry':
                if (typeof createEntry === 'function') {
                    createEntry();
                } else {
                    console.warn('[Mobile Nav] createEntry function not found');
                }
                break;

            case 'smart-entry':
                if (typeof openAIAnalysisModal === 'function') {
                    openAIAnalysisModal();
                } else {
                    console.warn('[Mobile Nav] openAIAnalysisModal function not found');
                }
                break;

            case 'goal':
                if (typeof createGoal === 'function') {
                    createGoal();
                } else {
                    console.warn('[Mobile Nav] createGoal function not found');
                }
                break;

            case 'routine':
                if (typeof createRoutine === 'function') {
                    createRoutine();
                } else {
                    console.warn('[Mobile Nav] createRoutine function not found');
                }
                break;

            case 'story':
                if (typeof upmCmCreateStory === 'function') {
                    upmCmCreateStory();
                } else {
                    console.warn('[Mobile Nav] upmCmCreateStory function not found');
                }
                break;

            case 'post':
                if (typeof upmCmCreatePost === 'function') {
                    upmCmCreatePost();
                } else if (typeof window.myavanaCommunity !== 'undefined') {
                    window.myavanaCommunity.openCreatePostModal();
                } else {
                    console.warn('[Mobile Nav] Post creation function not found');
                }
                break;

            default:
                console.warn('[Mobile Nav] Unknown action:', action);
        }
    }

    /**
     * Hide Bottom Navigation
     */
    function hideBottomNav() {
        $('#myavana-mobile-bottom-nav').addClass('hidden');
        MyavanaMobileNav.isVisible = false;
    }

    /**
     * Show Bottom Navigation
     */
    function showBottomNav() {
        $('#myavana-mobile-bottom-nav').removeClass('hidden');
        MyavanaMobileNav.isVisible = true;
    }

    /**
     * Initialize Gesture Support
     */
    function initializeGestures() {
        // Import gesture handlers
        if (typeof MyavanaGestures !== 'undefined') {
            MyavanaGestures.init();
        }
    }

    /**
     * Check if on mobile device
     */
    function isMobileDevice() {
        return window.innerWidth < 768;
    }

    /**
     * Show/hide based on screen size
     */
    function handleResize() {
        if (isMobileDevice()) {
            $('#myavana-mobile-bottom-nav').show();
        } else {
            $('#myavana-mobile-bottom-nav').hide();
        }
    }

    // Public API
    window.MyavanaMobileNav.open = openCreateMenu;
    window.MyavanaMobileNav.close = closeCreateMenu;
    window.MyavanaMobileNav.hide = hideBottomNav;
    window.MyavanaMobileNav.show = showBottomNav;

    // Initialize on document ready
    $(document).ready(function() {
        initMobileNavigation();
        handleResize();

        $(window).on('resize', handleResize);
    });

    console.log('[Mobile Nav] Module loaded');

})(jQuery);
