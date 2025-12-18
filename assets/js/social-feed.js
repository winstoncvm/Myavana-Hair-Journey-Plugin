/**
 * MYAVANA Community Feed JavaScript
 * Handles feed loading, filtering, likes, comments, and post creation
 *
 * Dependencies: jQuery
 */

(function($) {
    'use strict';

    // Settings from PHP
    const settings = window.myavanaCommunitySettings || {};

    // State
    let currentFilter = settings.currentFilter || 'all';
    let currentPage = 1;
    let isLoading = false;
    let hasMorePosts = true;

    /**
     * Initialize community feed
     */
    function initCommunityFeed() {
        // Load initial posts
        loadPosts();

        // Setup event listeners
        setupFilterButtons();
        setupCreatePostModal();
        setupLoadMore();
        setupInfiniteScroll();
    }

    /**
     * Load posts from server
     */
    function loadPosts(append = false) {
        if (isLoading) return;

        isLoading = true;
        showLoading();

        $.ajax({
            url: settings.ajaxUrl,
            method: 'POST',
            data: {
                action: 'get_community_feed',
                nonce: settings.nonce,
                filter: currentFilter,
                page: currentPage,
                per_page: settings.perPage || 10
            },
            success: function(response) {
                if (response.success) {
                    const posts = response.data;

                    if (posts.length === 0) {
                        if (currentPage === 1) {
                            showEmptyState();
                        } else {
                            hasMorePosts = false;
                            $('#myavana-feed-load-more').hide();
                        }
                    } else {
                        if (append) {
                            appendPosts(posts);
                        } else {
                            renderPosts(posts);
                        }

                        // Check if there are more posts
                        if (posts.length < settings.perPage) {
                            hasMorePosts = false;
                            $('#myavana-feed-load-more').hide();
                        } else {
                            $('#myavana-feed-load-more').show();
                        }
                    }
                } else {
                    showError('Failed to load posts. Please try again.');
                }
            },
            error: function() {
                showError('Network error. Please check your connection.');
            },
            complete: function() {
                isLoading = false;
                hideLoading();
            }
        });
    }

    /**
     * Render posts to the grid
     */
    function renderPosts(posts) {
        const $grid = $('#myavana-feed-grid');
        $grid.empty();

        posts.forEach(post => {
            $grid.append(createPostCard(post));
        });

        $('#myavana-feed-empty').hide();
        $grid.show();
    }

    /**
     * Append posts to existing grid
     */
    function appendPosts(posts) {
        const $grid = $('#myavana-feed-grid');

        posts.forEach(post => {
            $grid.append(createPostCard(post));
        });
    }

    /**
     * Create HTML for a single post card
     */
    function createPostCard(post) {
        const isLiked = post.is_liked ? 'liked' : '';
        const likeFillColor = post.is_liked ? 'var(--myavana-coral)' : 'none';

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

        return `
            <article class="myavana-post-card" data-post-id="${post.id}">
                <div class="myavana-post-header">
                    <img src="${escapeHtml(post.user_avatar)}"
                         alt="${escapeHtml(post.display_name)}"
                         class="myavana-post-avatar">
                    <div class="myavana-post-user-info">
                        <h3 class="myavana-post-username">${escapeHtml(post.display_name)}</h3>
                        <time class="myavana-post-time">${escapeHtml(post.formatted_date)}</time>
                    </div>
                    <span class="myavana-post-type-badge">${typeLabels[post.post_type] || 'General'}</span>
                </div>

                ${imageHtml}

                <div class="myavana-post-content">
                    <h2 class="myavana-post-title">${escapeHtml(post.title)}</h2>
                    <p class="myavana-post-text ${post.content.length > 200 ? 'truncated' : ''}">${escapeHtml(post.content)}</p>
                    ${post.content.length > 200 ? '<a href="#" class="myavana-read-more">Read more</a>' : ''}
                </div>

                <div class="myavana-post-actions">
                    <button class="myavana-action-btn like-btn ${isLiked}" data-post-id="${post.id}">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="${likeFillColor}" stroke="currentColor" stroke-width="2">
                            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                        </svg>
                        <span class="myavana-action-count">${post.likes_count || 0}</span>
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
                </div>
            </article>
        `;
    }

    /**
     * Setup filter button handlers
     */
    function setupFilterButtons() {
        $('.myavana-filter-btn').on('click', function() {
            const filter = $(this).data('filter');

            if (filter === currentFilter) return;

            // Update UI
            $('.myavana-filter-btn').removeClass('active');
            $(this).addClass('active');

            // Update state and reload
            currentFilter = filter;
            currentPage = 1;
            hasMorePosts = true;

            loadPosts();
        });
    }

    /**
     * Setup create post modal
     */
    function setupCreatePostModal() {
        const $modal = $('#myavana-create-post-modal');
        const $form = $('#myavana-create-post-form');
        const $uploadArea = $('#myavana-upload-area');
        const $fileInput = $('#myavana-post-image');
        const $uploadPreview = $('#myavana-upload-preview');

        console.log('Modal setup:', {
            modal: $modal.length,
            form: $form.length,
            uploadArea: $uploadArea.length,
            fileInput: $fileInput.length
        });

        // Open modal
        $('#myavana-create-post-btn, .myavana-feed-empty .myavana-btn-primary').on('click', function() {
            console.log('Opening modal...');
            $modal.addClass('active');
            $('body').css('overflow', 'hidden');
        });

        // Close modal - Fixed to prevent closing when clicking inside modal
        $('#myavana-close-modal, #myavana-cancel-post').on('click', function() {
            $modal.removeClass('active');
            $('body').css('overflow', '');
            $form[0].reset();
            $uploadPreview.hide().empty();
            $('.myavana-upload-prompt').show();
        });

        // Close modal when clicking overlay (outside modal content)
        $('.myavana-modal-overlay').on('click', function(e) {
            if (e.target === this) {
                $modal.removeClass('active');
                $('body').css('overflow', '');
                $form[0].reset();
                $uploadPreview.hide().empty();
                $('.myavana-upload-prompt').show();
            }
        });

        // File upload handling - Fixed to prevent infinite loop
        $uploadArea.on('click', function(e) {
            // Don't trigger if clicking the file input itself
            if (e.target !== $fileInput[0]) {
                e.preventDefault();
                e.stopPropagation();
                $fileInput.trigger('click');
            }
        });

        $fileInput.on('change', function(e) {
            const file = e.target.files[0];
            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    $uploadPreview.html(`
                        <img src="${event.target.result}" alt="Preview">
                        <button type="button" class="myavana-remove-image" style="position: absolute; top: 8px; right: 8px; background: var(--myavana-coral); color: white; border: none; border-radius: 50%; width: 32px; height: 32px; cursor: pointer;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </button>
                    `).show();
                    $uploadArea.find('.myavana-upload-prompt').hide();
                };
                reader.readAsDataURL(file);
            }
        });

        // Remove image
        $uploadPreview.on('click', '.myavana-remove-image', function(e) {
            e.stopPropagation();
            $fileInput.val('');
            $uploadPreview.hide().empty();
            $uploadArea.find('.myavana-upload-prompt').show();
        });

        // Form submission
        $form.on('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            formData.append('action', 'create_community_post');
            formData.append('nonce', settings.nonce);

            // Get file if exists
            const fileInput = $fileInput[0];
            if (fileInput.files.length > 0) {
                formData.append('image', fileInput.files[0]);
            }

            $.ajax({
                url: settings.ajaxUrl,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        // Close modal
                        $modal.removeClass('active');
                        $('body').css('overflow', '');
                        $form[0].reset();
                        $uploadPreview.hide().empty();

                        // Reload feed
                        currentPage = 1;
                        loadPosts();

                        // Show success message
                        showNotification('Your post has been shared successfully!', 'success');
                    } else {
                        showNotification(response.data || 'Failed to create post', 'error');
                    }
                },
                error: function() {
                    showNotification('Network error. Please try again.', 'error');
                }
            });
        });
    }

    /**
     * Setup load more button
     */
    function setupLoadMore() {
        $('#myavana-load-more-btn').on('click', function() {
            if (!isLoading && hasMorePosts) {
                currentPage++;
                loadPosts(true);
            }
        });
    }

    /**
     * Setup infinite scroll (optional enhancement)
     */
    function setupInfiniteScroll() {
        $(window).on('scroll', function() {
            if (!isLoading && hasMorePosts) {
                const scrollPosition = $(window).scrollTop() + $(window).height();
                const documentHeight = $(document).height();

                if (scrollPosition > documentHeight - 500) {
                    currentPage++;
                    loadPosts(true);
                }
            }
        });
    }

    /**
     * Handle like button clicks
     */
    $(document).on('click', '.like-btn', function(e) {
        e.preventDefault();

        const $btn = $(this);
        const postId = $btn.data('post-id');
        const $count = $btn.find('.myavana-action-count');
        const $svg = $btn.find('svg');

        $.ajax({
            url: settings.ajaxUrl,
            method: 'POST',
            data: {
                action: 'like_post',
                nonce: settings.nonce,
                post_id: postId
            },
            success: function(response) {
                if (response.success) {
                    const { action, likes_count } = response.data;

                    // Update UI
                    $count.text(likes_count);

                    if (action === 'liked') {
                        $btn.addClass('liked');
                        $svg.attr('fill', 'var(--myavana-coral)');
                    } else {
                        $btn.removeClass('liked');
                        $svg.attr('fill', 'none');
                    }
                }
            }
        });
    });

    /**
     * Handle comment button clicks (placeholder - can be expanded)
     */
    $(document).on('click', '.comment-btn', function(e) {
        e.preventDefault();
        const postId = $(this).data('post-id');
        // TODO: Open comment modal or expand inline comment section
        console.log('Comment on post:', postId);
        showNotification('Comment feature coming soon!', 'info');
    });

    /**
     * Handle share button clicks (placeholder)
     */
    $(document).on('click', '.share-btn', function(e) {
        e.preventDefault();
        const postId = $(this).data('post-id');
        // TODO: Implement share functionality
        console.log('Share post:', postId);
        showNotification('Share feature coming soon!', 'info');
    });

    /**
     * Handle read more clicks
     */
    $(document).on('click', '.myavana-read-more', function(e) {
        e.preventDefault();
        const $text = $(this).prev('.myavana-post-text');
        $text.removeClass('truncated');
        $(this).remove();
    });

    /**
     * Show loading state
     */
    function showLoading() {
        $('#myavana-feed-loading').show();
        $('#myavana-feed-grid').hide();
        $('#myavana-feed-empty').hide();
    }

    /**
     * Hide loading state
     */
    function hideLoading() {
        $('#myavana-feed-loading').hide();
        $('#myavana-feed-grid').show();
    }

    /**
     * Show empty state
     */
    function showEmptyState() {
        $('#myavana-feed-loading').hide();
        $('#myavana-feed-grid').hide();
        $('#myavana-feed-empty').show();
    }

    /**
     * Show error message
     */
    function showError(message) {
        showNotification(message, 'error');
        hideLoading();
    }

    /**
     * Show notification toast
     */
    function showNotification(message, type = 'info') {
        const colors = {
            success: 'var(--myavana-coral)',
            error: '#dc3545',
            info: 'var(--myavana-blueberry)'
        };

        const $notification = $(`
            <div class="myavana-notification" style="
                position: fixed;
                bottom: 24px;
                right: 24px;
                background: ${colors[type]};
                color: white;
                padding: 16px 24px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.2);
                z-index: 10000;
                font-family: 'Archivo', sans-serif;
                font-weight: 600;
                animation: slideInUp 0.3s ease;
            ">
                ${escapeHtml(message)}
            </div>
        `);

        $('body').append($notification);

        setTimeout(() => {
            $notification.fadeOut(300, function() {
                $(this).remove();
            });
        }, 3000);
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

    // Initialize when document is ready
    $(document).ready(function() {
        if ($('.myavana-community-container').length) {
            initCommunityFeed();
        }
    });

})(jQuery);
