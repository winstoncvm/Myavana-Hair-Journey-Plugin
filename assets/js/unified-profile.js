/**
 * Unified Profile Page JavaScript
 *
 * Handles all interactions for the unified profile page including
 * profile editing, avatar upload, tab switching, and data loading
 *
 * @package Myavana_Hair_Journey
 * @version 1.0.0
 */

(function($) {
    'use strict';

    /**
     * Open Profile Edit Offcanvas
     */
    window.myavanaUpOpenEditOffcanvas = function() {
        $('#myavanaUpEditOffcanvas').addClass('active');
        $('body').css('overflow', 'hidden');
    };

    /**
     * Close Profile Edit Offcanvas
     */
    window.myavanaUpCloseEditOffcanvas = function() {
        $('#myavanaUpEditOffcanvas').removeClass('active');
        $('body').css('overflow', '');
    };

    /**
     * Avatar Upload and Preview
     */
    let avatarFile = null;

    $('#myavanaUpAvatarInput').on('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;

        // Validate file type
        if (!file.type.match('image.*')) {
            alert('Please select an image file');
            return;
        }

        // Validate file size (2MB max)
        if (file.size > 2 * 1024 * 1024) {
            alert('Image size must be less than 2MB');
            return;
        }

        avatarFile = file;

        // Preview image
        const reader = new FileReader();
        reader.onload = function(event) {
            $('#myavanaUpAvatarPreview').attr('src', event.target.result);
        };
        reader.readAsDataURL(file);
    });

    // Click avatar preview to trigger upload
    $('.myavana-up-avatar-preview').on('click', function() {
        $('#myavanaUpAvatarInput').click();
    });

    // Remove avatar
    $('#myavanaUpRemoveAvatar').on('click', function() {
        if (confirm('Are you sure you want to remove your profile picture?')) {
            $('#myavanaUpAvatarPreview').attr('src', myavanaUpSettings.defaultAvatar);
            $('#myavanaUpAvatarInput').val('');
            avatarFile = null;
        }
    });

    /**
     * Bio Character Counter
     */
    $('#myavanaUpBio').on('input', function() {
        const length = $(this).val().length;
        $('#myavanaUpBioCounter').text(length);
    });

    // Initialize counter
    $('#myavanaUpBioCounter').text($('#myavanaUpBio').val().length);

    /**
     * Add Goal
     */
    window.myavanaUpAddGoal = function() {
        const index = $('.myavana-up-goal-item').length;
        const goalHTML = `
            <div class="myavana-up-goal-item" data-index="${index}">
                <input
                    type="text"
                    name="goals[${index}][title]"
                    class="myavana-up-field-input"
                    placeholder="Goal title..."
                >
                <button type="button" class="myavana-up-btn-icon myavana-up-remove-goal" onclick="myavanaUpRemoveGoal(this)">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
        `;
        $('#myavanaUpGoalsList').append(goalHTML);
    };

    /**
     * Remove Goal
     */
    window.myavanaUpRemoveGoal = function(btn) {
        $(btn).closest('.myavana-up-goal-item').remove();
    };

    /**
     * Form Submission
     */
    $('#myavanaUpEditForm').on('submit', function(e) {
        e.preventDefault();

        const $btn = $('#myavanaUpSaveProfileBtn');
        const originalText = $btn.html();

        // Disable button and show loading
        $btn.prop('disabled', true).html(`
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="myavana-up-spinner">
                <line x1="12" y1="2" x2="12" y2="6"></line>
                <line x1="12" y1="18" x2="12" y2="22"></line>
                <line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line>
                <line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line>
                <line x1="2" y1="12" x2="6" y2="12"></line>
                <line x1="18" y1="12" x2="22" y2="12"></line>
                <line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line>
                <line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line>
            </svg>
            Saving...
        `);

        const formData = new FormData(this);
        formData.append('action', 'myavana_up_save_profile');

        // Add avatar if changed
        if (avatarFile) {
            formData.append('avatar', avatarFile);
        }

        $.ajax({
            url: myavanaUpSettings.ajaxUrl,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    // Show success message
                    myavanaUpShowNotification('Profile updated successfully!', 'success');

                    // Update UI with new data
                    if (response.data.avatar_url) {
                        $('.myavana-profile-avatar img').attr('src', response.data.avatar_url);
                    }
                    if (response.data.display_name) {
                        $('.myavana-profile-name').text(response.data.display_name);
                    }
                    if (response.data.bio) {
                        $('.myavana-profile-bio').text(response.data.bio);
                    }

                    // Close offcanvas after short delay
                    setTimeout(() => {
                        myavanaUpCloseEditOffcanvas();

                        // Reload page to show all updates
                        setTimeout(() => location.reload(), 500);
                    }, 1000);
                } else {
                    myavanaUpShowNotification(response.data || 'Failed to update profile', 'error');
                }
            },
            error: function() {
                myavanaUpShowNotification('Network error. Please try again.', 'error');
            },
            complete: function() {
                $btn.prop('disabled', false).html(originalText);
            }
        });
    });

    /**
     * Show Notification
     */
    window.myavanaUpShowNotification = function(message, type = 'info') {
        const iconMap = {
            success: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>',
            error: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>',
            info: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>'
        };

        const notification = $(`
            <div class="myavana-up-notification myavana-up-notification-${type}">
                <div class="myavana-up-notification-icon">${iconMap[type]}</div>
                <div class="myavana-up-notification-message">${message}</div>
            </div>
        `);

        $('body').append(notification);

        setTimeout(() => notification.addClass('show'), 100);
        setTimeout(() => {
            notification.removeClass('show');
            setTimeout(() => notification.remove(), 300);
        }, 4000);
    };

    /**
     * Tab Switching
     */
    window.switchProfileTab = function(tabName) {
        // Update tab buttons
        $('.myavana-profile-tab').removeClass('active');
        $(`.myavana-profile-tab[data-tab="${tabName}"]`).addClass('active');

        // Update mobile nav
        $('.myavana-mobile-nav-btn').removeClass('active');
        $(`.myavana-mobile-nav-btn[data-tab="${tabName}"]`).addClass('active');

        // Update tab content
        $('.myavana-profile-tab-content').removeClass('active');
        $(`#${tabName}TabContent`).addClass('active');

        // Save active tab
        localStorage.setItem('myavanaActiveProfileTab', tabName);

        // Load tab-specific content
        if (tabName === 'community') {
            loadUserCommunityPosts();
        }
    };

    /**
     * Load User Community Posts
     */
    /**
     * Load User Community Posts
     */
    function loadUserCommunityPosts() {
        const userId = myavanaUpSettings.userId;
        const $grid = $('#userPostsGrid');

        // Loading state
        $grid.html(`
            <div class="myavana-posts-loading">
                <div class="myavana-spinner"></div>
                <p>Loading posts...</p>
            </div>
        `);

        $.ajax({
            url: myavanaUpSettings.ajaxUrl,
            method: 'POST',
            data: {
                action: 'get_user_community_posts',
                nonce: myavanaUpSettings.nonce,
                user_id: userId
            },
            success(response) {
                console.log('Community Posts Response:', response);

                if (!response.success) {
                    renderUserPostsError($grid);
                    return;
                }

                const posts = response.data || [];

                if (!posts.length) {
                    renderUserPostsEmptyState($grid);
                    return;
                }

                // 🔥 Render using createPostCard
                const postsHTML = posts.map(post => createPostCard(post)).join('');
                $grid.html(postsHTML);

                // Optional: attach listeners if needed
                initPostCardInteractions?.();
            },
            error() {
                renderUserPostsError($grid);
            }
        });
    }
    function renderUserPostsEmptyState($grid) {
        $grid.html(`
            <div class="myavana-empty-state">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                </svg>
                <h3>No community posts yet</h3>
                <p>Share your hair journey with the community</p>
            </div>
        `);
    }

    function renderUserPostsError($grid) {
        $grid.html(`
            <div class="myavana-empty-state">
                <p>Failed to load posts. Please refresh.</p>
            </div>
        `);
    }
    /**
     * Escape HTML to prevent XSS
     */
    function escapeHtml(text) {
        // Handle null, undefined, or non-string values
        if (text === null || text === undefined) {
            return '';
        }

        // Convert to string if not already
        text = String(text);

        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }
    /**
     * Parse text with @mentions and #hashtags
     */
    function parseTextWithMentionsAndHashtags(text) {
        if (!text) return '';

        // Parse @mentions
        text = text.replace(/@(\w+)/g, '<span class="myavana-mention">@$1</span>');

        // Parse #hashtags
        text = text.replace(/#(\w+)/g, '<span class="myavana-hashtag">#$1</span>');

        // Parse simple formatting (bold, italic)
        text = text.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
        text = text.replace(/\*(.+?)\*/g, '<em>$1</em>');

        return text;
    }

     /**
     * Create HTML for a single post card
     */
    function createPostCard(post) {
        const reactions = post.reactions || {};
        const userReaction = post.user_reaction || null;
        const isLiked = post.is_liked || false;

        // Calculate total reactions - use likes_count if reactions are not available
        const totalReactions = post.likes_count || Object.values(reactions).reduce((sum, count) => sum + count, 0);

        const imageHtml = post.image_url ? `
            <div class="myavana-post-image-wrapper">
                <img src="${escapeHtml(post.image_url)}"
                     alt="${escapeHtml(post.title)}"
                     class="myavana-post-image"
                     loading="lazy">
            </div>
        ` : '';

        const typeLabels = {
            'progress': 'Progress Update',
            'transformation': 'Transformation',
            'routine': 'Routine',
            'products': 'Product Review',
            'tips': 'Tips & Advice',
            'general': 'General'
        };

        // Reaction counts HTML
        const reactionEmojis = {
            'like': '❤️',
            'love': '😍',
            'celebrate': '🎉',
            'insightful': '💡'
        };

        let reactionCountsHtml = '';
        if (totalReactions > 0) {
            const reactionItems = Object.entries(reactions)
                .filter(([type, count]) => count > 0)
                .map(([type, count]) => `
                    <div class="myavana-reaction-count-item" data-reaction="${type}">
                        <span class="reaction-emoji">${reactionEmojis[type]}</span>
                        <span class="reaction-count">${count}</span>
                    </div>
                `).join('');

            reactionCountsHtml = `
                <div class="myavana-reaction-counts">
                    ${reactionItems}
                </div>
            `;
        }

        return `
            <article class="myavana-post-card" data-post-id="${post.id}">
                <div class="myavana-post-header">
                    <img src="${escapeHtml(post.user_avatar)}"
                         alt="${escapeHtml(post.display_name)}"
                         class="myavana-post-avatar clickable-avatar"
                         data-user-id="${post.user_id}"
                         title="View ${escapeHtml(post.display_name)}'s profile">
                    <div class="myavana-post-user-info">
                        <h3 class="myavana-post-username clickable-username" data-user-id="${post.user_id}">${escapeHtml(post.display_name)}</h3>
                        <time class="myavana-post-time">${escapeHtml(post.formatted_date)}</time>
                    </div>
                    <span class="myavana-post-type-badge">${typeLabels[post.post_type] || 'General'}</span>
                    
                </div>

                ${imageHtml}

                <div class="myavana-post-content">
                    <h2 class="myavana-post-title">${escapeHtml(post.title)}</h2>
                    <p class="myavana-post-text ${post.content.length > 200 ? 'truncated' : ''}">${parseTextWithMentionsAndHashtags(escapeHtml(post.content))}</p>
                    ${post.content.length > 200 ? '<a href="#" class="myavana-read-more">Read more</a>' : ''}
                </div>

                ${reactionCountsHtml}

                <div class="myavana-post-actions">
                    <div class="myavana-reactions-picker" data-post-id="${post.id}">
                        <button class="myavana-reaction-option" data-reaction="like" title="Like">❤️</button>
                        <button class="myavana-reaction-option" data-reaction="love" title="Love">😍</button>
                        <button class="myavana-reaction-option" data-reaction="celebrate" title="Celebrate">🎉</button>
                        <button class="myavana-reaction-option" data-reaction="insightful" title="Insightful">💡</button>
                    </div>

                    <button class="myavana-action-btn myavana-ci-react-btn ${(userReaction || isLiked) ? 'reacted' : ''}"
                            data-post-id="${post.id}"
                            data-user-reaction="${userReaction || (isLiked ? 'like' : '')}">
                        <span class="reaction-display">${userReaction ? reactionEmojis[userReaction] : '❤️'}</span>
                        <span class="myavana-action-count">${totalReactions || 0}</span>
                    </button>

                    <button class="myavana-action-btn comment-btn" data-post-id="${post.id}">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                        </svg>
                        <span class="myavana-action-count">${post.comments_count || 0}</span>
                    </button>

                    <button class="myavana-action-btn share-btn" data-post-id="${post.id}">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="18" cy="5" r="3"></circle>
                            <circle cx="6" cy="12" r="3"></circle>
                            <circle cx="18" cy="19" r="3"></circle>
                            <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line>
                            <line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line>
                        </svg>
                    </button>

                    <button class="myavana-action-btn bookmark-btn ${post.is_bookmarked || post.is_saved ? 'bookmarked' : ''}" data-post-id="${post.id}" title="${post.is_bookmarked || post.is_saved ? 'Saved' : 'Save post'}">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="${post.is_bookmarked || post.is_saved ? 'var(--myavana-coral)' : 'none'}" stroke="currentColor" stroke-width="2">
                            <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path>
                        </svg>
                    </button>

                    
                </div>

                <!-- Comments Section -->
                <div class="myavana-post-comments" id="comments-${post.id}" style="display: none;">
                    <div class="myavana-comments-list"></div>
                    <div class="myavana-comment-form">
                        <div class="myavana-comment-input-wrapper">
                            <textarea class="myavana-comment-input" placeholder="Write a comment..." rows="1"></textarea>
                            <button class="myavana-comment-submit" data-post-id="${post.id}">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="22" y1="2" x2="11" y2="13"></line>
                                    <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </article>
        `;
    }

    /**
     * Toggle Post View (Grid/List)
     */
    window.togglePostView = function(view) {
        $('.myavana-view-btn').removeClass('active');
        $(`.myavana-view-btn[data-view="${view}"]`).addClass('active');

        const $grid = $('#userPostsGrid');
        if (view === 'list') {
            $grid.addClass('list-view');
        } else {
            $grid.removeClass('list-view');
        }
    };

    /**
     * FAB Menu
     */
    window.openCreateMenu = function() {
        const $menu = $('#fabMenu');
        $menu.css('display', 'flex');
        setTimeout(() => $menu.addClass('active'), 10);
    };

    window.closeFabMenu = function() {
        const $menu = $('#fabMenu');
        $menu.removeClass('active');
        setTimeout(() => $menu.css('display', 'none'), 300);
    };

    // Close FAB menu when clicking outside
    $(document).on('click', function(e) {
        const $menu = $('#fabMenu');
        const $fab = $('.myavana-fab');
        if ($menu.length && !$menu.is(e.target) && !$menu.has(e.target).length &&
            !$fab.is(e.target) && !$fab.has(e.target).length) {
            closeFabMenu();
        }
    });

    /**
     * Initialize on Document Ready
     */
    $(document).ready(function() {
        // Load active tab from localStorage
        const activeTab = localStorage.getItem('myavanaActiveProfileTab') || 'journey';
        switchProfileTab(activeTab);

        // Make profile edit button work
        $('.myavana-avatar-edit-btn, #myavanaUpEditProfileBtn').on('click', function(e) {
            e.preventDefault();
            myavanaUpOpenEditOffcanvas();
        });

        // Keyboard shortcuts
        $(document).on('keydown', function(e) {
            // ESC to close offcanvas
            if (e.key === 'Escape' && $('#myavanaUpEditOffcanvas').hasClass('active')) {
                myavanaUpCloseEditOffcanvas();
            }
        });

        // Add spinner animation CSS
        if (!$('#myavanaUpSpinnerStyle').length) {
            $('head').append(`
                <style id="myavanaUpSpinnerStyle">
                    @keyframes myavanaUpSpin {
                        to { transform: rotate(360deg); }
                    }
                    .myavana-up-spinner {
                        animation: myavanaUpSpin 1s linear infinite;
                    }
                </style>
            `);
        }
    });

})(jQuery);
