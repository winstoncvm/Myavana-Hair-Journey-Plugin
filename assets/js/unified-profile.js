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
    function loadUserCommunityPosts() {
        const userId = myavanaUpSettings.userId;
        const $grid = $('#userPostsGrid');

        // Show loading
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
            success: function(response) {
                if (response.success) {
                    const posts = response.data;

                    if (posts.length === 0) {
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
                    } else {
                        let postsHTML = posts.map(post => `
                            <div class="myavana-user-post-card" data-post-id="${post.id}">
                                ${post.image_url ? `
                                    <div class="myavana-post-card-image" style="background-image: url('${post.image_url}')"></div>
                                ` : ''}
                                <div class="myavana-post-card-content">
                                    <p>${post.content.substring(0, 150)}${post.content.length > 150 ? '...' : ''}</p>
                                    <div class="myavana-post-card-meta">
                                        <span>
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                                            </svg>
                                            ${post.likes_count}
                                        </span>
                                        <span>
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                                            </svg>
                                            ${post.comments_count}
                                        </span>
                                        <span class="myavana-post-card-date">${post.formatted_date}</span>
                                    </div>
                                </div>
                            </div>
                        `).join('');

                        $grid.html(postsHTML);
                    }
                } else {
                    $grid.html(`
                        <div class="myavana-empty-state">
                            <p>Failed to load posts</p>
                        </div>
                    `);
                }
            },
            error: function() {
                $grid.html(`
                    <div class="myavana-empty-state">
                        <p>Error loading posts. Please refresh the page.</p>
                    </div>
                `);
            }
        });
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
