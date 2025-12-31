/**
 * Unified Profile Management & Community - JavaScript
 * Handles all interactions for the unified profile and new features
 *
 * @package Myavana_Hair_Journey
 * @version 1.0.0
 */

(function($) {
    'use strict';

    // Global state management
    window.UPM_CM = {
        currentTab: 'journey',
        currentView: 'timeline',
        userId: null,
        currentUserId: null,
        isOwnProfile: false,
        ajaxUrl: '',
        nonce: '',

        // Cache
        cache: {
            stories: null,
            conversations: null,
            groups: null,
            recommendations: null
        }
    };

    /* ============================================
       INITIALIZATION
       ============================================ */
    $(document).ready(function() {
        initializeProfile();
        initializeTabs();
        initializeStories();
        initializeMessaging();
        initializeGroups();
        initializeAnalytics();
        initializeExport();
        loadInitialData();
    });

    function initializeProfile() {
        if (typeof window.upmCmProfileData === 'undefined') {
            console.warn('[UPM_CM] Profile data not found');
            return;
        }

        UPM_CM.userId = window.upmCmProfileData.userId;
        UPM_CM.currentUserId = window.upmCmProfileData.currentUserId;
        UPM_CM.isOwnProfile = window.upmCmProfileData.isOwnProfile;
        UPM_CM.ajaxUrl = window.upmCmProfileData.ajaxUrl;
        UPM_CM.nonce = window.upmCmProfileData.nonce;
        UPM_CM.currentTab = window.upmCmProfileData.defaultTab || 'journey';

        console.log('[UPM_CM] Profile initialized:', UPM_CM);
    }

    /* ============================================
       TAB SYSTEM
       ============================================ */
    function initializeTabs() {
        $('.upm-cm-tab-btn').on('click', function() {
            const tabName = $(this).data('tab');
            switchTab(tabName);
        });

        // Initialize default tab
        if (UPM_CM.currentTab) {
            switchTab(UPM_CM.currentTab);
        }
    }

    function switchTab(tabName) {
        // Update tab buttons
        $('.upm-cm-tab-btn').removeClass('active');
        $(`.upm-cm-tab-btn[data-tab="${tabName}"]`).addClass('active');

        // Update tab panels
        $('.upm-cm-tab-panel').removeClass('active');
        $(`#upm-cm-tab-${tabName}`).addClass('active');

        // Update state
        UPM_CM.currentTab = tabName;

        // Load tab content if needed
        loadTabContent(tabName);

        // Update URL without reload
        if (history.pushState) {
            const url = new URL(window.location);
            url.searchParams.set('tab', tabName);
            history.pushState({}, '', url);
        }
    }

    function loadTabContent(tabName) {
        switch (tabName) {
            case 'journey':
                loadJourneyContent();
                break;
            case 'community':
                loadCommunityPosts();
                break;
            case 'goals':
                loadGoalsAndRoutines();
                break;
            case 'analytics':
                loadAnalytics();
                break;
            case 'settings':
                loadSettings();
                break;
        }
    }

    /* ============================================
       JOURNEY TAB
       ============================================ */
    function loadJourneyContent() {
        // View toggle handlers
        $('.upm-cm-view-btn').on('click', function() {
            const view = $(this).data('view');
            $('.upm-cm-view-btn').removeClass('active');
            $(this).addClass('active');
            UPM_CM.currentView = view;
            renderJourneyView(view);
        });

        // Load initial view
        renderJourneyView(UPM_CM.currentView);
    }

    function renderJourneyView(view) {
        const $content = $('#upm-cm-journey-content');

        // This integrates with existing timeline functionality
        // The timeline scripts should already be loaded
        $content.html('<div class="upm-cm-loading"><div class="upm-cm-spinner"></div></div>');

        // Trigger existing timeline rendering based on view
        setTimeout(() => {
            if (typeof window.MyavanaTimeline !== 'undefined') {
                window.MyavanaTimeline.renderView(view);
            }
        }, 100);
    }

    /* ============================================
       COMMUNITY TAB
       ============================================ */
    function loadCommunityPosts() {
        const $grid = $('#upm-cm-user-posts-grid');
        $grid.html('<div class="upm-cm-loading"><div class="upm-cm-spinner"></div></div>');

        $.ajax({
            url: UPM_CM.ajaxUrl,
            type: 'POST',
            data: {
                action: 'get_community_feed',
                nonce: UPM_CM.nonce,
                filter: 'user_posts',
                user_id: UPM_CM.userId,
                page: 1,
                per_page: 50
            },
            success: function(response) {
                if (response.success && response.data.length > 0) {
                    renderCommunityPosts(response.data);
                } else {
                    showEmptyState($grid, 'No posts yet', 'Start sharing your hair journey with the community!');
                }
            },
            error: function() {
                showError($grid, 'Failed to load posts');
            }
        });
    }

    function renderCommunityPosts(posts) {
        const $grid = $('#upm-cm-user-posts-grid');
        $grid.empty();

        posts.forEach(post => {
            const $card = createPostCard(post);
            $grid.append($card);
        });
    }

    function createPostCard(post) {
        return $(`
            <div class="myavana-feed-card" data-post-id="${post.id}">
                ${post.image_url ? `<img src="${post.image_url}" alt="${post.title}" class="feed-card-image">` : ''}
                <div class="feed-card-content">
                    <h3 class="feed-card-title">${post.title}</h3>
                    <p class="feed-card-text">${truncateText(post.content, 100)}</p>
                    <div class="feed-card-meta">
                        <span><i class="icon-heart"></i> ${post.likes_count}</span>
                        <span><i class="icon-comment"></i> ${post.comments_count}</span>
                        <span class="feed-card-date">${post.formatted_date}</span>
                    </div>
                </div>
            </div>
        `);
    }

    /* ============================================
       GOALS & ROUTINES TAB
       ============================================ */
    function loadGoalsAndRoutines() {
        loadGoals();
        loadRoutines();
    }

    function loadGoals() {
        const $list = $('#upm-cm-goals-list');
        $list.html('<div class="upm-cm-loading"><div class="upm-cm-spinner"></div></div>');

        // Use existing goal fetching from Myavana_Data_Manager
        $.ajax({
            url: UPM_CM.ajaxUrl,
            type: 'POST',
            data: {
                action: 'myavana_get_goals',
                nonce: UPM_CM.nonce,
                user_id: UPM_CM.userId
            },
            success: function(response) {
                if (response.success && response.data.length > 0) {
                    renderGoals(response.data);
                } else {
                    showEmptyState($list, 'No active goals', 'Set a goal to track your hair journey progress!');
                }
            },
            error: function() {
                showError($list, 'Failed to load goals');
            }
        });
    }

    function loadRoutines() {
        const $list = $('#upm-cm-routines-list');
        $list.html('<div class="upm-cm-loading"><div class="upm-cm-spinner"></div></div>');

        $.ajax({
            url: UPM_CM.ajaxUrl,
            type: 'POST',
            data: {
                action: 'myavana_get_routines',
                nonce: UPM_CM.nonce,
                user_id: UPM_CM.userId
            },
            success: function(response) {
                if (response.success && response.data.length > 0) {
                    renderRoutines(response.data);
                } else {
                    showEmptyState($list, 'No routines yet', 'Create a routine to maintain consistency!');
                }
            },
            error: function() {
                showError($list, 'Failed to load routines');
            }
        });
    }

    function renderGoals(goals) {
        const $list = $('#upm-cm-goals-list');
        $list.empty();
        // Use existing goal rendering logic
        goals.forEach(goal => {
            const $card = createGoalCard(goal);
            $list.append($card);
        });
    }

    function renderRoutines(routines) {
        const $list = $('#upm-cm-routines-list');
        $list.empty();
        routines.forEach(routine => {
            const $card = createRoutineCard(routine);
            $list.append($card);
        });
    }

    function createGoalCard(goal) {
        return $(`
            <div class="upm-cm-goal-card">
                <h4>${goal.title}</h4>
                <div class="upm-cm-progress-bar">
                    <div class="upm-cm-progress-fill" style="width: ${goal.progress || 0}%"></div>
                </div>
                <p class="upm-cm-goal-status">${goal.progress || 0}% complete</p>
            </div>
        `);
    }

    function createRoutineCard(routine) {
        return $(`
            <div class="upm-cm-routine-card">
                <h4>${routine.title}</h4>
                <p>${routine.description || ''}</p>
                <span class="upm-cm-routine-frequency">${routine.frequency || 'Daily'}</span>
            </div>
        `);
    }

    /* ============================================
       STORIES & HIGHLIGHTS
       ============================================ */
    function initializeStories() {
        loadHighlights();
    }

    function loadHighlights() {
        $.ajax({
            url: UPM_CM.ajaxUrl,
            type: 'POST',
            data: {
                action: 'upm_cm_get_highlights',
                nonce: UPM_CM.nonce,
                user_id: UPM_CM.userId
            },
            success: function(response) {
                if (response.success) {
                    renderHighlights(response.data);
                }
            }
        });
    }

    function renderHighlights(highlights) {
        const $container = $('#upm-cm-highlights-container');
        $container.empty();

        Object.keys(highlights).forEach(title => {
            const stories = highlights[title];
            if (stories.length > 0) {
                const $highlight = createHighlightCircle(title, stories[0]);
                $container.append($highlight);
            }
        });
    }

    function createHighlightCircle(title, story) {
        return $(`
            <div class="upm-cm-story-circle" onclick="upmCmViewHighlight('${title}')">
                <div class="upm-cm-story-ring" style="background-image: url('${story.content_url}')"></div>
                <span class="upm-cm-story-label">${title}</span>
            </div>
        `);
    }

    window.upmCmCreateStory = function() {
        // Implementation for story creation
        // This would open a modal for uploading photo/video for story
        console.log('[UPM_CM] Create story clicked');
        alert('Story creation feature - Coming soon!');
    };

    window.upmCmViewHighlight = function(title) {
        console.log('[UPM_CM] View highlight:', title);
        // Open story viewer for highlight
    };

    /* ============================================
       DIRECT MESSAGING
       ============================================ */
    function initializeMessaging() {
        // Load conversations when needed
    }

    window.upmCmSendMessage = function(receiverId) {
        console.log('[UPM_CM] Send message to user:', receiverId);
        // Open messaging modal
        const $modal = createMessageModal(receiverId);
        $('body').append($modal);
        $modal.fadeIn(200);
    };

    function createMessageModal(receiverId) {
        return $(`
            <div class="upm-cm-message-modal">
                <div class="upm-cm-modal-overlay" onclick="$(this).parent().remove()"></div>
                <div class="upm-cm-modal-content">
                    <h3>Send Message</h3>
                    <textarea id="upm-cm-message-text" placeholder="Type your message..."></textarea>
                    <div class="upm-cm-modal-actions">
                        <button class="upm-cm-btn upm-cm-btn-secondary" onclick="$(this).closest('.upm-cm-message-modal').remove()">Cancel</button>
                        <button class="upm-cm-btn upm-cm-btn-primary" onclick="upmCmSendMessageAction(${receiverId})">Send</button>
                    </div>
                </div>
            </div>
        `);
    }

    window.upmCmSendMessageAction = function(receiverId) {
        const message = $('#upm-cm-message-text').val();

        if (!message.trim()) {
            alert('Please enter a message');
            return;
        }

        $.ajax({
            url: UPM_CM.ajaxUrl,
            type: 'POST',
            data: {
                action: 'upm_cm_send_message',
                nonce: UPM_CM.nonce,
                receiver_id: receiverId,
                message_text: message
            },
            success: function(response) {
                if (response.success) {
                    $('.upm-cm-message-modal').remove();
                    showNotification('Message sent successfully!', 'success');
                } else {
                    showNotification('Failed to send message', 'error');
                }
            }
        });
    };

    /* ============================================
       COMMUNITY GROUPS
       ============================================ */
    function initializeGroups() {
        // Initialize when needed
    }

    /* ============================================
       ANALYTICS
       ============================================ */
    function loadAnalytics() {
        const $content = $('#upm-cm-analytics-content');
        $content.html('<div class="upm-cm-loading"><div class="upm-cm-spinner"></div></div>');

        const period = $('#upm-cm-analytics-period').val() || '30';

        $.ajax({
            url: UPM_CM.ajaxUrl,
            type: 'POST',
            data: {
                action: 'upm_cm_get_advanced_analytics',
                nonce: UPM_CM.nonce,
                user_id: UPM_CM.userId,
                period: period
            },
            success: function(response) {
                if (response.success) {
                    renderAnalytics(response.data);
                } else {
                    showError($content, 'Failed to load analytics');
                }
            }
        });
    }

    function initializeAnalytics() {
        $('#upm-cm-analytics-period').on('change', function() {
            loadAnalytics();
        });
    }

    function renderAnalytics(data) {
        const $content = $('#upm-cm-analytics-content');
        $content.empty();

        // Render analytics charts and insights
        // This would integrate with existing analytics functionality
        $content.html(`
            <div class="upm-cm-analytics-section">
                <h3>Hair Health Trends</h3>
                <canvas id="upm-cm-health-chart"></canvas>
            </div>
            <div class="upm-cm-analytics-section">
                <h3>Progress Overview</h3>
                <canvas id="upm-cm-progress-chart"></canvas>
            </div>
        `);

        // Initialize charts if Chart.js is available
        if (typeof Chart !== 'undefined') {
            initializeCharts(data);
        }
    }

    function initializeCharts(data) {
        // Implementation would use Chart.js to render analytics
        console.log('[UPM_CM] Initialize charts with data:', data);
    }

    /* ============================================
       JOURNEY EXPORT
       ============================================ */
    function initializeExport() {
        // Export functionality
    }

    window.upmCmExportJourney = function() {
        const exportType = 'full_journey';
        const exportFormat = 'pdf';

        if (!confirm('Export your hair journey as a PDF? This may take a moment.')) {
            return;
        }

        $.ajax({
            url: UPM_CM.ajaxUrl,
            type: 'POST',
            data: {
                action: 'upm_cm_export_journey',
                nonce: UPM_CM.nonce,
                export_type: exportType,
                export_format: exportFormat
            },
            success: function(response) {
                if (response.success) {
                    showNotification('Export started! You will be notified when it\'s ready.', 'success');
                    // Poll for export status
                    pollExportStatus(response.data.export_id);
                } else {
                    showNotification('Failed to start export', 'error');
                }
            }
        });
    };

    function pollExportStatus(exportId) {
        const interval = setInterval(function() {
            $.ajax({
                url: UPM_CM.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'upm_cm_get_export_status',
                    nonce: UPM_CM.nonce,
                    export_id: exportId
                },
                success: function(response) {
                    if (response.success) {
                        if (response.data.status === 'completed') {
                            clearInterval(interval);
                            showNotification('Export ready! Downloading...', 'success');
                            window.location.href = response.data.file_url;
                        } else if (response.data.status === 'failed') {
                            clearInterval(interval);
                            showNotification('Export failed', 'error');
                        }
                    }
                }
            });
        }, 3000); // Poll every 3 seconds
    }

    /* ============================================
       SETTINGS
       ============================================ */
    function loadSettings() {
        const $content = $('#upm-cm-settings-content');
        $content.html(`
            <div class="upm-cm-settings-section">
                <h3>Privacy Settings</h3>
                <p>Control who can see your profile and content.</p>
                <!-- Privacy settings form -->
            </div>
            <div class="upm-cm-settings-section">
                <h3>Notification Preferences</h3>
                <p>Choose what notifications you want to receive.</p>
                <!-- Notification settings -->
            </div>
        `);
    }

    /* ============================================
       PROFILE EDITING
       ============================================ */
    window.upmCmEditProfile = function() {
        console.log('[UPM_CM] Edit profile clicked');
        // Open profile edit modal
    };

    window.upmCmEditAvatar = function() {
        console.log('[UPM_CM] Edit avatar clicked');
        // Open avatar upload modal
    };

    window.upmCmOpenSettings = function() {
        switchTab('settings');
    };

    /* ============================================
       CREATE MENU
       ============================================ */
    window.upmCmShowCreateMenu = function() {
        $('#upm-cm-create-menu').fadeIn(200);
    };

    window.upmCmCloseCreateMenu = function() {
        $('#upm-cm-create-menu').fadeOut(200);
    };

    window.upmCmCreatePost = function() {
        console.log('[UPM_CM] Create post clicked');
        // Trigger existing community post creation
        if (typeof window.myavanaCommunity !== 'undefined') {
            window.myavanaCommunity.openCreatePostModal();
        }
    };

    /* ============================================
       HELPER FUNCTIONS
       ============================================ */
    function loadInitialData() {
        // Load data for active tab
        loadTabContent(UPM_CM.currentTab);
    }

    function showEmptyState($container, title, message) {
        $container.html(`
            <div class="upm-cm-empty-state">
                <div class="upm-cm-empty-icon">📭</div>
                <h3>${title}</h3>
                <p>${message}</p>
            </div>
        `);
    }

    function showError($container, message) {
        $container.html(`
            <div class="upm-cm-error-state">
                <div class="upm-cm-error-icon">⚠️</div>
                <p>${message}</p>
            </div>
        `);
    }

    function showNotification(message, type = 'info') {
        const $notification = $(`
            <div class="upm-cm-notification upm-cm-notification-${type}">
                ${message}
            </div>
        `);

        $('body').append($notification);

        setTimeout(() => {
            $notification.addClass('show');
        }, 100);

        setTimeout(() => {
            $notification.removeClass('show');
            setTimeout(() => $notification.remove(), 300);
        }, 3000);
    }

    function truncateText(text, maxLength) {
        if (text.length <= maxLength) return text;
        return text.substr(0, maxLength) + '...';
    }

    /* ============================================
       FOLLOW/UNFOLLOW
       ============================================ */
    $(document).on('click', '.upm-cm-follow-btn', function() {
        const $btn = $(this);
        const userId = $btn.data('user-id');
        const isFollowing = $btn.hasClass('following');

        $.ajax({
            url: UPM_CM.ajaxUrl,
            type: 'POST',
            data: {
                action: 'follow_user',
                nonce: UPM_CM.nonce,
                user_id: userId
            },
            success: function(response) {
                if (response.success) {
                    if (isFollowing) {
                        $btn.removeClass('following').text('Follow');
                    } else {
                        $btn.addClass('following').text('Following');
                    }
                    showNotification(response.data.action === 'followed' ? 'Now following!' : 'Unfollowed', 'success');
                }
            }
        });
    });

    console.log('[UPM_CM] Unified Profile JavaScript initialized');

})(jQuery);
