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
     * Expose reload function globally for entry selector
     */
    window.MyavanaSocialFeed = {
        loadPosts: function() {
            currentPage = 1;
            loadPosts(false);
        },
        reload: function() {
            currentPage = 1;
            loadPosts(false);
        }
    };

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

                    <button class="myavana-action-btn bookmark-btn" data-post-id="${post.id}" title="Save post">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path>
                        </svg>
                    </button>
                </div>

                <!-- Comments Section -->
                <div class="myavana-post-comments" id="comments-${post.id}" style="display: none;">
                    <div class="myavana-comments-list"></div>
                    <div class="myavana-comment-form">
                        <img src="${settings.currentUserAvatar || ''}" alt="You" class="myavana-comment-avatar">
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
     * Handle comment button clicks - Toggle comments section
     */
    $(document).on('click', '.comment-btn', function(e) {
        e.preventDefault();
        const postId = $(this).data('post-id');
        const $commentsSection = $(`#comments-${postId}`);

        if ($commentsSection.is(':visible')) {
            $commentsSection.slideUp(300);
        } else {
            $commentsSection.slideDown(300);
            // Load comments if not already loaded
            if ($commentsSection.find('.myavana-comments-list').children().length === 0) {
                loadComments(postId);
            }
            // Focus on comment input
            $commentsSection.find('.myavana-comment-input').focus();
        }
    });

    /**
     * Load comments for a post
     */
    function loadComments(postId) {
        const $commentsList = $(`#comments-${postId} .myavana-comments-list`);

        $commentsList.html('<div class="myavana-comments-loading">Loading comments...</div>');

        $.ajax({
            url: settings.ajaxUrl,
            method: 'POST',
            data: {
                action: 'get_post_comments',
                nonce: settings.nonce,
                post_id: postId
            },
            success: function(response) {
                if (response.success && response.data) {
                    const comments = response.data;

                    if (comments.length === 0) {
                        $commentsList.html('<div class="myavana-comments-empty">Be the first to comment!</div>');
                    } else {
                        $commentsList.empty();
                        comments.forEach(comment => {
                            $commentsList.append(createCommentHTML(comment));
                        });
                    }
                } else {
                    $commentsList.html('<div class="myavana-comments-error">Failed to load comments</div>');
                }
            },
            error: function() {
                $commentsList.html('<div class="myavana-comments-error">Failed to load comments</div>');
            }
        });
    }

    /**
     * Create HTML for a comment
     */
    function createCommentHTML(comment) {
        return `
            <div class="myavana-comment" data-comment-id="${comment.id}">
                <img src="${escapeHtml(comment.user_avatar)}" alt="${escapeHtml(comment.display_name)}" class="myavana-comment-avatar">
                <div class="myavana-comment-content">
                    <div class="myavana-comment-header">
                        <span class="myavana-comment-author">${escapeHtml(comment.display_name)}</span>
                        <span class="myavana-comment-time">${escapeHtml(comment.formatted_date)}</span>
                    </div>
                    <p class="myavana-comment-text">${escapeHtml(comment.content)}</p>
                    <div class="myavana-comment-actions">
                        <button class="myavana-comment-action-btn reply-btn" data-comment-id="${comment.id}">Reply</button>
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * Handle comment submission
     */
    $(document).on('click', '.myavana-comment-submit', function(e) {
        e.preventDefault();

        const $btn = $(this);
        const postId = $btn.data('post-id');
        const $form = $btn.closest('.myavana-comment-form');
        const $input = $form.find('.myavana-comment-input');
        const content = $input.val().trim();

        if (!content) {
            showNotification('Please enter a comment', 'error');
            return;
        }

        // Disable button and show loading
        $btn.prop('disabled', true).addClass('loading');

        $.ajax({
            url: settings.ajaxUrl,
            method: 'POST',
            data: {
                action: 'comment_on_post',
                nonce: settings.nonce,
                post_id: postId,
                content: content,
                parent_id: 0
            },
            success: function(response) {
                if (response.success) {
                    // Clear input
                    $input.val('').css('height', 'auto');

                    // Add comment to list
                    const $commentsList = $(`#comments-${postId} .myavana-comments-list`);
                    const $emptyMsg = $commentsList.find('.myavana-comments-empty');

                    if ($emptyMsg.length) {
                        $emptyMsg.remove();
                    }

                    $commentsList.append(createCommentHTML(response.data.comment));

                    // Update comment count
                    const $commentBtn = $(`.comment-btn[data-post-id="${postId}"]`);
                    const $count = $commentBtn.find('.myavana-action-count');
                    const currentCount = parseInt($count.text()) || 0;
                    $count.text(currentCount + 1);

                    showNotification('Comment added!', 'success');
                } else {
                    showNotification(response.data || 'Failed to add comment', 'error');
                }
            },
            error: function() {
                showNotification('Network error. Please try again.', 'error');
            },
            complete: function() {
                $btn.prop('disabled', false).removeClass('loading');
            }
        });
    });

    /**
     * Auto-resize comment textarea
     */
    $(document).on('input', '.myavana-comment-input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });

    /**
     * Handle share button clicks - Open share modal
     */
    $(document).on('click', '.share-btn', function(e) {
        e.preventDefault();
        const postId = $(this).data('post-id');
        const $postCard = $(this).closest('.myavana-post-card');
        const title = $postCard.find('.myavana-post-title').text();
        const content = $postCard.find('.myavana-post-text').text();

        openShareModal(postId, title, content);
    });

    /**
     * Open share modal
     */
    function openShareModal(postId, title, description) {
        const shareUrl = window.location.origin + window.location.pathname + '?post=' + postId;

        // Try native Web Share API first (mobile browsers)
        if (navigator.share) {
            navigator.share({
                title: title,
                text: description,
                url: shareUrl
            }).then(() => {
                trackShare(postId, 'native');
                showNotification('Thanks for sharing!', 'success');
            }).catch((error) => {
                // User cancelled or error occurred, show custom modal
                if (error.name !== 'AbortError') {
                    showCustomShareModal(postId, shareUrl, title, description);
                }
            });
        } else {
            // Fallback to custom share modal
            showCustomShareModal(postId, shareUrl, title, description);
        }
    }

    /**
     * Show custom share modal
     */
    function showCustomShareModal(postId, shareUrl, title, description) {
        const encodedUrl = encodeURIComponent(shareUrl);
        const encodedTitle = encodeURIComponent(title);
        const encodedDesc = encodeURIComponent(description);

        const shareLinks = {
            facebook: `https://www.facebook.com/sharer/sharer.php?u=${encodedUrl}`,
            twitter: `https://twitter.com/intent/tweet?url=${encodedUrl}&text=${encodedTitle}`,
            linkedin: `https://www.linkedin.com/sharing/share-offsite/?url=${encodedUrl}`,
            whatsapp: `https://wa.me/?text=${encodedTitle}%20${encodedUrl}`,
            pinterest: `https://pinterest.com/pin/create/button/?url=${encodedUrl}&description=${encodedTitle}`,
            email: `mailto:?subject=${encodedTitle}&body=${encodedDesc}%0A%0A${encodedUrl}`
        };

        const $modal = $(`
            <div class="myavana-share-modal" id="share-modal-${postId}">
                <div class="myavana-modal-overlay"></div>
                <div class="myavana-share-modal-content">
                    <div class="myavana-share-header">
                        <h3>Share this post</h3>
                        <button class="myavana-share-close">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </button>
                    </div>
                    <div class="myavana-share-options">
                        <button class="myavana-share-option" data-platform="facebook" data-url="${shareLinks.facebook}">
                            <div class="myavana-share-icon facebook">
                                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                            </div>
                            <span>Facebook</span>
                        </button>
                        <button class="myavana-share-option" data-platform="twitter" data-url="${shareLinks.twitter}">
                            <div class="myavana-share-icon twitter">
                                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/></svg>
                            </div>
                            <span>Twitter</span>
                        </button>
                        <button class="myavana-share-option" data-platform="linkedin" data-url="${shareLinks.linkedin}">
                            <div class="myavana-share-icon linkedin">
                                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                            </div>
                            <span>LinkedIn</span>
                        </button>
                        <button class="myavana-share-option" data-platform="whatsapp" data-url="${shareLinks.whatsapp}">
                            <div class="myavana-share-icon whatsapp">
                                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.890-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                            </div>
                            <span>WhatsApp</span>
                        </button>
                        <button class="myavana-share-option" data-platform="pinterest" data-url="${shareLinks.pinterest}">
                            <div class="myavana-share-icon pinterest">
                                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12.017 0C5.396 0 .029 5.367.029 11.987c0 5.079 3.158 9.417 7.618 11.162-.105-.949-.199-2.403.041-3.439.219-.937 1.406-5.957 1.406-5.957s-.359-.72-.359-1.781c0-1.663.967-2.911 2.168-2.911 1.024 0 1.518.769 1.518 1.688 0 1.029-.653 2.567-.992 3.992-.285 1.193.6 2.165 1.775 2.165 2.128 0 3.768-2.245 3.768-5.487 0-2.861-2.063-4.869-5.008-4.869-3.41 0-5.409 2.562-5.409 5.199 0 1.033.394 2.143.889 2.741.099.12.112.225.085.345-.09.375-.293 1.199-.334 1.363-.053.225-.172.271-.401.165-1.495-.69-2.433-2.878-2.433-4.646 0-3.776 2.748-7.252 7.92-7.252 4.158 0 7.392 2.967 7.392 6.923 0 4.135-2.607 7.462-6.233 7.462-1.214 0-2.354-.629-2.758-1.379l-.749 2.848c-.269 1.045-1.004 2.352-1.498 3.146 1.123.345 2.306.535 3.55.535 6.607 0 11.985-5.365 11.985-11.987C23.97 5.39 18.592.026 11.985.026L12.017 0z"/></svg>
                            </div>
                            <span>Pinterest</span>
                        </button>
                        <button class="myavana-share-option" data-platform="email" data-url="${shareLinks.email}">
                            <div class="myavana-share-icon email">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                            </div>
                            <span>Email</span>
                        </button>
                    </div>
                    <div class="myavana-share-link">
                        <label>Or copy link</label>
                        <div class="myavana-copy-link-wrapper">
                            <input type="text" readonly value="${shareUrl}" class="myavana-copy-link-input">
                            <button class="myavana-copy-link-btn" data-url="${shareUrl}">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                                </svg>
                                Copy
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `);

        $('body').append($modal);
        setTimeout(() => $modal.addClass('active'), 10);

        // Close modal handlers
        $modal.find('.myavana-share-close, .myavana-modal-overlay').on('click', function(e) {
            if (e.target === this) {
                $modal.removeClass('active');
                setTimeout(() => $modal.remove(), 300);
            }
        });

        // Share option clicks
        $modal.find('.myavana-share-option').on('click', function() {
            const platform = $(this).data('platform');
            const url = $(this).data('url');

            window.open(url, '_blank', 'width=600,height=400');
            trackShare(postId, platform);
            showNotification('Sharing to ' + platform + '...', 'success');
        });

        // Copy link
        $modal.find('.myavana-copy-link-btn').on('click', function() {
            const url = $(this).data('url');
            const $input = $modal.find('.myavana-copy-link-input');

            $input.select();
            document.execCommand('copy');

            $(this).html('<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg> Copied!');

            trackShare(postId, 'copy_link');
            showNotification('Link copied to clipboard!', 'success');

            setTimeout(() => {
                $(this).html('<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg> Copy');
            }, 2000);
        });
    }

    /**
     * Track share analytics
     */
    function trackShare(postId, platform) {
        $.ajax({
            url: settings.ajaxUrl,
            method: 'POST',
            data: {
                action: 'track_post_share',
                nonce: settings.nonce,
                post_id: postId,
                platform: platform
            }
        });
    }

    /**
     * Handle bookmark button clicks
     */
    $(document).on('click', '.bookmark-btn', function(e) {
        e.preventDefault();

        const $btn = $(this);
        const postId = $btn.data('post-id');
        const $svg = $btn.find('svg');

        // Disable button during request
        $btn.prop('disabled', true);

        $.ajax({
            url: settings.ajaxUrl,
            method: 'POST',
            data: {
                action: 'bookmark_post',
                nonce: settings.nonce,
                post_id: postId,
                collection: 'saved'
            },
            success: function(response) {
                if (response.success) {
                    const {action, message} = response.data;

                    if (action === 'bookmarked') {
                        $btn.addClass('bookmarked');
                        $svg.attr('fill', 'var(--myavana-coral)');
                        showNotification(message, 'success');
                    } else {
                        $btn.removeClass('bookmarked');
                        $svg.attr('fill', 'none');
                        showNotification(message, 'info');
                    }
                } else {
                    showNotification(response.data || 'Failed to save post', 'error');
                }
            },
            error: function() {
                showNotification('Network error. Please try again.', 'error');
            },
            complete: function() {
                $btn.prop('disabled', false);
            }
        });
    });

    /**
     * Handle avatar/username clicks - Open user profile
     */
    $(document).on('click', '.clickable-avatar, .clickable-username', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const userId = $(this).data('user-id');
        openUserProfileModal(userId);
    });

    /**
     * Open user profile modal
     */
    function openUserProfileModal(userId) {
        // Show loading state
        const $loadingModal = $(`
            <div class="myavana-profile-modal active" id="profile-modal-${userId}">
                <div class="myavana-modal-overlay"></div>
                <div class="myavana-profile-modal-content">
                    <div class="myavana-profile-loading">
                        <div class="myavana-loader-spinner"></div>
                        <p>Loading profile...</p>
                    </div>
                </div>
            </div>
        `);

        $('body').append($loadingModal).css('overflow', 'hidden');

        // Fetch user profile data
        $.ajax({
            url: settings.ajaxUrl,
            method: 'POST',
            data: {
                action: 'get_user_profile',
                nonce: settings.nonce,
                user_id: userId
            },
            success: function(response) {
                if (response.success) {
                    $loadingModal.find('.myavana-profile-modal-content').html(renderUserProfile(response.data));
                } else {
                    $loadingModal.find('.myavana-profile-modal-content').html(`
                        <div class="myavana-profile-error">
                            <p>Failed to load profile</p>
                            <button class="myavana-btn-secondary" onclick="$(this).closest('.myavana-profile-modal').remove(); $('body').css('overflow', '');">Close</button>
                        </div>
                    `);
                }
            },
            error: function() {
                $loadingModal.find('.myavana-profile-modal-content').html(`
                    <div class="myavana-profile-error">
                        <p>Network error. Please try again.</p>
                        <button class="myavana-btn-secondary" onclick="$(this).closest('.myavana-profile-modal').remove(); $('body').css('overflow', '');">Close</button>
                    </div>
                `);
            }
        });
    }

    /**
     * Render user profile
     */
    function renderUserProfile(profile) {
        const isOwnProfile = profile.user_id == settings.userId;
        const isFollowing = profile.is_following || false;

        return `
            <div class="myavana-profile-header">
                <button class="myavana-profile-close">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>

            <div class="myavana-profile-info">
                <div class="myavana-profile-avatar-large">
                    <img src="${escapeHtml(profile.avatar)}" alt="${escapeHtml(profile.display_name)}">
                </div>
                <h2 class="myavana-profile-name">${escapeHtml(profile.display_name)}</h2>
                ${profile.bio ? `<p class="myavana-profile-bio">${escapeHtml(profile.bio)}</p>` : ''}

                <div class="myavana-profile-stats">
                    <div class="myavana-profile-stat">
                        <span class="myavana-stat-number">${profile.stats.posts_count || 0}</span>
                        <span class="myavana-stat-label">Posts</span>
                    </div>
                    <div class="myavana-profile-stat">
                        <span class="myavana-stat-number">${profile.stats.followers_count || 0}</span>
                        <span class="myavana-stat-label">Followers</span>
                    </div>
                    <div class="myavana-profile-stat">
                        <span class="myavana-stat-number">${profile.stats.following_count || 0}</span>
                        <span class="myavana-stat-label">Following</span>
                    </div>
                </div>

                ${!isOwnProfile ? `
                    <button class="myavana-btn-primary follow-user-btn ${isFollowing ? 'following' : ''}"
                            data-user-id="${profile.user_id}">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            ${isFollowing ?
                                '<polyline points="20 6 9 17 4 12"></polyline>' :
                                '<path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line>'
                            }
                        </svg>
                        ${isFollowing ? 'Following' : 'Follow'}
                    </button>
                ` : ''}
            </div>

            <div class="myavana-profile-tabs">
                <button class="myavana-profile-tab active" data-tab="posts">Posts</button>
                <button class="myavana-profile-tab" data-tab="journey">Hair Journey</button>
            </div>

            <div class="myavana-profile-content">
                <div class="myavana-profile-tab-content active" data-tab-content="posts">
                    <div class="myavana-profile-posts-grid">
                        ${profile.recent_posts && profile.recent_posts.length > 0 ?
                            profile.recent_posts.map(post => `
                                <div class="myavana-profile-post-item" data-post-id="${post.id}">
                                    ${post.image_url ? `
                                        <img src="${escapeHtml(post.image_url)}" alt="${escapeHtml(post.title)}">
                                    ` : `
                                        <div class="myavana-profile-post-no-image">
                                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                                <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                                <polyline points="21 15 16 10 5 21"></polyline>
                                            </svg>
                                        </div>
                                    `}
                                    <div class="myavana-profile-post-overlay">
                                        <div class="myavana-profile-post-stats">
                                            <span><svg width="20" height="20" viewBox="0 0 24 24" fill="white" stroke="white" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg> ${post.likes_count || 0}</span>
                                            <span><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg> ${post.comments_count || 0}</span>
                                        </div>
                                    </div>
                                </div>
                            `).join('') :
                            '<div class="myavana-profile-empty">No posts yet</div>'
                        }
                    </div>
                </div>

                <div class="myavana-profile-tab-content" data-tab-content="journey">
                    <div class="myavana-profile-journey">
                        ${profile.hair_journey_stats ? `
                            <div class="myavana-journey-stats">
                                <div class="myavana-journey-stat">
                                    <span class="myavana-journey-label">Total Entries</span>
                                    <span class="myavana-journey-value">${profile.hair_journey_stats.total_entries || 0}</span>
                                </div>
                                <div class="myavana-journey-stat">
                                    <span class="myavana-journey-label">Journey Started</span>
                                    <span class="myavana-journey-value">${profile.hair_journey_stats.journey_start || 'Recently'}</span>
                                </div>
                            </div>
                        ` : '<div class="myavana-profile-empty">No hair journey data yet</div>'}
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * Handle profile modal close
     */
    $(document).on('click', '.myavana-profile-close, .myavana-profile-modal .myavana-modal-overlay', function(e) {
        if (e.target === this) {
            $(this).closest('.myavana-profile-modal').removeClass('active');
            setTimeout(() => {
                $(this).closest('.myavana-profile-modal').remove();
                $('body').css('overflow', '');
            }, 300);
        }
    });

    /**
     * Handle profile tabs
     */
    $(document).on('click', '.myavana-profile-tab', function() {
        const tab = $(this).data('tab');
        const $modal = $(this).closest('.myavana-profile-modal-content');

        $modal.find('.myavana-profile-tab').removeClass('active');
        $(this).addClass('active');

        $modal.find('.myavana-profile-tab-content').removeClass('active');
        $modal.find(`[data-tab-content="${tab}"]`).addClass('active');
    });

    /**
     * Handle follow button in profile
     */
    $(document).on('click', '.follow-user-btn', function(e) {
        e.preventDefault();

        const $btn = $(this);
        const userId = $btn.data('user-id');
        const $svg = $btn.find('svg');

        $btn.prop('disabled', true);

        $.ajax({
            url: settings.ajaxUrl,
            method: 'POST',
            data: {
                action: 'follow_user',
                nonce: settings.nonce,
                user_id: userId
            },
            success: function(response) {
                if (response.success) {
                    const {action, follower_count} = response.data;

                    if (action === 'followed') {
                        $btn.addClass('following').html(`
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                            Following
                        `);
                    } else {
                        $btn.removeClass('following').html(`
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="8.5" cy="7" r="4"></circle>
                                <line x1="20" y1="8" x2="20" y2="14"></line>
                                <line x1="23" y1="11" x2="17" y2="11"></line>
                            </svg>
                            Follow
                        `);
                    }

                    // Update follower count
                    $btn.closest('.myavana-profile-modal-content').find('.myavana-profile-stats .myavana-stat-number').eq(1).text(follower_count);
                }
            },
            error: function() {
                showNotification('Failed to follow user', 'error');
            },
            complete: function() {
                $btn.prop('disabled', false);
            }
        });
    });

    /**
     * View my profile
     */
    window.viewMyProfile = function() {
        const userId = settings.userId || window.myavanaCommunitySettings?.userId;
        if (userId) {
            openUserProfileModal(userId);
        }
    };

    /**
     * Edit my profile (opens profile edit modal)
     */
    window.editMyProfile = function() {
        // TODO: Implement profile editing modal in Phase 4
        alert('Profile editing coming soon! For now, you can update your profile from WordPress settings.');
    };

    /**
     * View saved posts
     */
    window.viewSavedPosts = function() {
        // TODO: Implement saved posts page in Phase 4
        alert('Saved posts page coming soon!');
    };

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
