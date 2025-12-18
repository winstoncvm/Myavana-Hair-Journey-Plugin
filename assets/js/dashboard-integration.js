/**
 * MYAVANA Advanced Dashboard Integration
 * Handles view switching, modals, and component loading
 */

(function($) {
    'use strict';

    // Wait for DOM and framework ready
    $(document).ready(function() {
        if (window.Myavana && window.Myavana.initialized) {
            initializeDashboard();
        } else {
            $(document).on('myavana:framework:ready', initializeDashboard);
        }

        function initializeDashboard() {
            // Register dashboard component with unified system
            if (window.Myavana && window.Myavana.Components) {
                Myavana.Components.register('dashboard', window.MyavanaDashboard);
            }

            // Register API endpoints for dashboard data
            if (window.Myavana && window.Myavana.API) {
                Myavana.API.register('dashboard_data', {
                    action: 'myavana_get_dashboard_data',
                    cache: true
                });

                Myavana.API.register('dashboard_stats', {
                    action: 'myavana_get_dashboard_stats',
                    cache: true
                });

                Myavana.API.register('load_timeline_embed', {
                    action: 'myavana_load_timeline_embed_dash',
                    cache: false
                });

                Myavana.API.register('load_profile_embed', {
                    action: 'myavana_load_profile_embed',
                    cache: false
                });

                Myavana.API.register('load_entry_form', {
                    action: 'myavana_load_component',
                    cache: false
                });

                Myavana.API.register('load_chatbot_embed', {
                    action: 'myavana_load_component',
                    cache: false
                });

                // Set up router for deep linking
                Myavana.Router.route('dashboard', () => window.MyavanaDashboard.switchView('overview'));
                Myavana.Router.route('timeline', () => window.MyavanaDashboard.switchView('timeline'));
                Myavana.Router.route('profile', () => window.MyavanaDashboard.switchView('profile'));
                Myavana.Router.route('analytics', () => window.MyavanaDashboard.switchView('analytics'));

                // Listen for navigation events
                Myavana.Events.on('nav:navigate', (data) => {
                    if (data.route === 'dashboard') {
                        window.MyavanaDashboard.switchView('overview');
                    }
                });
            }
        }
    });

    // Dashboard Controller
    window.MyavanaDashboard = {
        currentView: 'overview',

        init() {
            console.log('Dashboard: Starting initialization...');

            this.setupViewSwitching();
            this.setupQuickActions();
            this.setupResponsiveFeatures();
            this.loadInitialData();
            this.setupNotifications();

            console.log('Dashboard: Initialization complete');
            if (window.Myavana) {
                Myavana.log('Dashboard initialized');
            }
        },

        setupViewSwitching() {
            console.log('Dashboard: Setting up view switching...');
            console.log('Dashboard: Found', $('.view-btn').length, 'view buttons');

            $('.view-btn').on('click', (e) => {
                e.preventDefault();
                const $btn = $(e.currentTarget);
                const viewName = $btn.data('view');
                console.log('Dashboard: Tab clicked ->', viewName);
                this.switchView(viewName);
            });
        },

        switchView(viewName) {
            console.log('Dashboard: Switching to view:', viewName);

            // Update active button
            $('.view-btn').removeClass('active');
            $(`.view-btn[data-view="${viewName}"]`).addClass('active');

            // Hide all views (remove inline styles to let CSS handle it)
            $('.dashboard-view').removeClass('active').css('display', '');

            // Show selected view
            const $targetView = $(`#${viewName}View`);
            $targetView.addClass('active');

            this.currentView = viewName;

            // Update unified data with current view
            if (window.Myavana) {
                Myavana.Data.set('dashboard_current_view', viewName);
                Myavana.Events.trigger('dashboard:view-changed', {
                    view: viewName,
                    timestamp: Date.now()
                });
                Myavana.Router.navigate(viewName);
            }

            // Load view-specific content
            this.loadViewContent(viewName);
        },

        loadViewContent(viewName) {
            switch(viewName) {
                case 'timeline':
                    this.loadTimelineView();
                    break;
                case 'profile':
                    this.loadProfileView();
                    break;
                case 'analytics':
                    this.loadAnalyticsView();
                    break;
                default:
                    // Overview is already loaded
                    break;
            }
        },

        loadTimelineView() {
            console.log('Dashboard: Loading timeline view...');
            const $container = $('#timelineEmbedContainer');

            if ($container.hasClass('myavana-timeline-loaded')) {
                console.log('Dashboard: Timeline already loaded, skipping');
                return;
            }

            $container.html(`
                <div class="timeline-loading">
                    <div class="loading-spinner"></div>
                    <p>Loading your beautiful timeline...</p>
                </div>
            `);

            // Use unified API if available
            if (window.Myavana && Myavana.API) {
                Myavana.API.call('load_timeline_embed').then(response => {
                    if (response.success) {
                        $container.html(response.data.html);
                        $container.addClass('myavana-timeline-loaded');

                        if (typeof initializeTimelineShortcode === 'function') {
                            initializeTimelineShortcode();
                        }

                        this.notify('Timeline loaded successfully', 'success');
                    } else {
                        $container.html(`
                            <div class="load-error">
                                <p>Failed to load timeline</p>
                                <button class="myavana-btn myavana-btn-outline retry-timeline-btn">Retry</button>
                            </div>
                        `);
                    }
                }).catch(error => {
                    $container.html(`
                        <div class="load-error">
                            <p>Error loading timeline. Please try again.</p>
                            <button class="myavana-btn myavana-btn-outline retry-timeline-btn">Retry</button>
                        </div>
                    `);
                    this.notify('Failed to load timeline', 'error');
                });
            }

            // Setup retry functionality
            $container.on('click', '.retry-timeline-btn', () => {
                $container.removeClass('myavana-timeline-loaded');
                this.loadTimelineView();
            });
        },

        loadProfileView() {
            console.log('Dashboard: Loading profile view...');
            const $container = $('#profileEmbedContainer');

            if ($container.hasClass('myavana-profile-loaded')) {
                console.log('Dashboard: Profile already loaded, skipping');
                return;
            }

            $container.html(`
                <div class="profile-loading">
                    <div class="loading-spinner"></div>
                    <p>Loading your hair profile...</p>
                </div>
            `);

            // Use unified API if available
            if (window.Myavana && Myavana.API) {
                Myavana.API.call('load_profile_embed').then(response => {
                    if (response.success) {
                        $container.html(response.data.html);
                        $container.addClass('myavana-profile-loaded');

                        if (typeof initializeProfileShortcode === 'function') {
                            initializeProfileShortcode();
                        }

                        this.notify('Profile loaded successfully', 'success');
                    } else {
                        $container.html(`
                            <div class="load-error">
                                <p>Failed to load profile</p>
                                <button class="myavana-btn myavana-btn-outline retry-profile-btn">Retry</button>
                            </div>
                        `);
                    }
                }).catch(error => {
                    $container.html(`
                        <div class="load-error">
                            <p>Error loading profile. Please try again.</p>
                            <button class="myavana-btn myavana-btn-outline retry-profile-btn">Retry</button>
                        </div>
                    `);
                    this.notify('Failed to load profile', 'error');
                });
            }

            // Setup retry functionality
            $container.on('click', '.retry-profile-btn', () => {
                $container.removeClass('myavana-profile-loaded');
                this.loadProfileView();
            });
        },

        loadAnalyticsView() {
            console.log('Dashboard: Loading analytics view...');
            // Analytics view content is already in the DOM
            // Just trigger any chart initializations if needed
            if (typeof window.AdvancedDashboard !== 'undefined') {
                window.AdvancedDashboard.setupCharts();
            }
        },

        setupNotifications() {
            this.notify = (message, type = 'info') => {
                if (window.Myavana && Myavana.UI) {
                    Myavana.UI.notify(message, type);
                } else {
                    console.log(`[${type.toUpperCase()}] ${message}`);
                }
            };
        },

        setupQuickActions() {
            $('#myavanaAddEntryBtn, #addTimelineEntryBtn').on('click', () => {
                this.showAddEntryModal();
            });

            $('#myavanaAIAnalysisBtn').on('click', () => {
                this.loadChatbotModal();
            });

            $('#myavanaRoutineBtn').on('click', () => {
                this.switchView('analytics');
            });

            $('#myavanaProductsBtn').on('click', () => {
                this.showProductsModal();
            });
        },

        showAddEntryModal() {
            if (window.Myavana && Myavana.UI) {
                Myavana.UI.showLoading('.quick-actions');

                Myavana.API.call('load_entry_form', { component: 'entry_form' }).then(response => {
                    if (response.success) {
                        const modal = Myavana.UI.createModal({
                            title: 'Add New Entry',
                            content: response.data.html,
                            width: '600px',
                            onShow: () => {
                                if (typeof initializeEntryForm === 'function') {
                                    initializeEntryForm();
                                }
                            },
                            onClose: () => {
                                this.loadInitialData();
                                const $timelineContainer = $('#timelineEmbedContainer');
                                if ($timelineContainer.hasClass('myavana-timeline-loaded')) {
                                    $timelineContainer.removeClass('myavana-timeline-loaded');
                                    this.loadTimelineView();
                                }
                            }
                        });
                    } else {
                        this.notify('Failed to load entry form', 'error');
                    }
                }).catch(() => {
                    this.notify('Failed to load entry form', 'error');
                }).finally(() => {
                    Myavana.UI.hideLoading('.quick-actions');
                });
            }
        },

        loadChatbotModal() {
            if (window.Myavana && Myavana.UI) {
                Myavana.UI.showLoading('.quick-actions');

                Myavana.API.call('load_chatbot_embed', { component: 'chatbot_embed' }).then(response => {
                    if (response.success) {
                        const modal = Myavana.UI.createModal({
                            title: 'AI Hair Analysis',
                            content: response.data.html,
                            width: '800px',
                            maxWidth: '95vw',
                            onShow: () => {
                                if (typeof initializeChatbot === 'function') {
                                    initializeChatbot();
                                }
                            }
                        });
                    } else {
                        this.notify('Failed to load AI chatbot', 'error');
                    }
                }).catch(() => {
                    this.notify('Failed to load AI chatbot', 'error');
                }).finally(() => {
                    Myavana.UI.hideLoading('.quick-actions');
                });
            }
        },

        showProductsModal() {
            const comingSoonHTML = `
                <div class="myavana-coming-soon">
                    <div class="coming-soon-icon">🚀</div>
                    <h3>Products Manager</h3>
                    <p>Track your hair care products, get low-stock alerts, and discover new products tailored to your hair type.</p>
                    <div class="coming-soon-features">
                        <div class="feature">✨ Product Library</div>
                        <div class="feature">📊 Usage Tracking</div>
                        <div class="feature">🔔 Reorder Alerts</div>
                        <div class="feature">🎯 Personalized Recommendations</div>
                    </div>
                    <p class="coming-soon-note">Coming soon to your MYAVANA experience!</p>
                </div>
            `;

            if (window.Myavana && Myavana.UI) {
                Myavana.UI.createModal({
                    title: 'Products Manager',
                    content: comingSoonHTML,
                    width: '500px'
                });
            }
        },

        setupResponsiveFeatures() {
            if (window.innerWidth <= 768) {
                this.setupMobileNavigation();
            }

            $(window).on('resize', () => this.handleResize());
        },

        setupMobileNavigation() {
            const $viewControls = $('.view-controls');
            $viewControls.addClass('mobile-bottom-nav');

            let startX, endX;
            $('.dashboard-main').on('touchstart', (e) => {
                startX = e.touches[0].clientX;
            }).on('touchend', (e) => {
                endX = e.changedTouches[0].clientX;
                this.handleSwipe(startX, endX);
            });
        },

        handleSwipe(startX, endX) {
            const diff = startX - endX;
            if (Math.abs(diff) > 50) {
                const views = ['overview', 'timeline', 'profile', 'analytics'];
                const currentIndex = views.indexOf(this.currentView);

                if (diff > 0 && currentIndex < views.length - 1) {
                    this.switchView(views[currentIndex + 1]);
                } else if (diff < 0 && currentIndex > 0) {
                    this.switchView(views[currentIndex - 1]);
                }
            }
        },

        handleResize() {
            const $header = $('.dashboard-header');
            const $controls = $('.view-controls');

            if (window.innerWidth <= 768) {
                $header.addClass('mobile');
                $controls.addClass('mobile-bottom-nav');
            } else {
                $header.removeClass('mobile');
                $controls.removeClass('mobile-bottom-nav');
            }
        },

        loadInitialData() {
            this.updateDashboardStats();

            if (window.Myavana) {
                Myavana.Events.on('data:changed:user_profile', () => {
                    this.updateDashboardStats();
                });

                Myavana.Events.on('data:changed:hair_entries', () => {
                    this.updateDashboardStats();
                });
            }
        },

        updateDashboardStats() {
            if (window.Myavana && Myavana.API) {
                Myavana.API.call('dashboard_stats').then(response => {
                    if (response.success) {
                        Myavana.Data.set('dashboard_stats', response.data);
                    }
                }).catch(() => {
                    this.notify('Failed to load dashboard stats', 'error');
                });
            }
        }
    };

    // Auto-initialize when script loads
    if (document.readyState === 'loading') {
        $(document).ready(() => window.MyavanaDashboard.init());
    } else {
        window.MyavanaDashboard.init();
    }

})(jQuery);