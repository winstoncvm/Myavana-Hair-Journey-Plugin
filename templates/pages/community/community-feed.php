<?php
/**
 * Community Feed Template
 * Displays social feed of shared hair journeys
 *
 * MYAVANA Brand Standards Applied:
 * - Color palette: Onyx, Coral, Blueberry, Stone
 * - Typography: Archivo Black for headers, Archivo for body
 * - Mobile-first responsive design
 */

function myavana_community_feed_shortcode($atts = []) {
    // Check if user is logged in
    if (!is_user_logged_in()) {
        return '<div class="myavana-community-container">
                    <div class="myavana-community-empty">
                        <h2 class="myavana-subheader">Join Our Community</h2>
                        <p class="myavana-body">Please sign in to view and share hair journey inspiration.</p>
                    </div>
                </div>';
    }

    $current_user = wp_get_current_user();

    // Parse shortcode attributes
    $atts = shortcode_atts([
        'filter' => 'all', // all, following, trending, featured
        'per_page' => '10',
        'show_filters' => 'true',
        'show_create_post' => 'true'
    ], $atts, 'myavana_community_feed');

    ob_start();
    ?>

    <div class="myavana-community-container" data-theme="light">

        <!-- Community Header -->
        <header class="myavana-community-header">
            <div class="myavana-community-header-content">
                <div class="myavana-community-title-section">
                    <span class="myavana-preheader">MYAVANA COMMUNITY</span>
                    <h1 class="myavana-community-title">Hair Journey Inspiration</h1>
                    <p class="myavana-body">Share your progress, discover transformations, and connect with others on their hair journey.</p>
                </div>

                <?php if ($atts['show_create_post'] === 'true') : ?>
                <div class="myavana-community-actions">
                    <button class="myavana-btn-primary" id="myavana-create-post-btn">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        Create New Post
                    </button>
                    <button class="myavana-btn-secondary share-existing-entry-btn">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                            <circle cx="8.5" cy="8.5" r="1.5"></circle>
                            <polyline points="21 15 16 10 5 21"></polyline>
                        </svg>
                        Share Existing Entry
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </header>

        <!-- Filter Tabs -->
        <?php if ($atts['show_filters'] === 'true') : ?>
        <div class="myavana-feed-filters">
            <button class="myavana-filter-btn active" data-filter="all">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="7" height="7"></rect>
                    <rect x="14" y="3" width="7" height="7"></rect>
                    <rect x="14" y="14" width="7" height="7"></rect>
                    <rect x="3" y="14" width="7" height="7"></rect>
                </svg>
                All Posts
            </button>
            <button class="myavana-filter-btn" data-filter="following">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                Following
            </button>
            <button class="myavana-filter-btn" data-filter="trending">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
                    <polyline points="17 6 23 6 23 12"></polyline>
                </svg>
                Trending
            </button>
            <button class="myavana-filter-btn" data-filter="featured">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                </svg>
                Featured
            </button>
        </div>
        <?php endif; ?>

        <!-- Feed Container -->
        <div class="myavana-feed-content">

            <!-- Loading State -->
            <div class="myavana-feed-loading" id="myavana-feed-loading">
                <div class="myavana-loader-spinner"></div>
                <p class="myavana-body">Loading inspiring journeys...</p>
            </div>

            <!-- Posts Grid -->
            <div class="myavana-feed-grid" id="myavana-feed-grid">
                <!-- Posts will be dynamically loaded here via JavaScript -->
            </div>

            <!-- Empty State -->
            <div class="myavana-feed-empty" id="myavana-feed-empty" style="display: none;">
                <div class="myavana-empty-icon">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="var(--myavana-coral)" stroke-width="1.5">
                        <path d="M12 2L2 7l10 5 10-5-10-5z"></path>
                        <path d="M2 17l10 5 10-5M2 12l10 5 10-5"></path>
                    </svg>
                </div>
                <h3 class="myavana-subheader">No Posts Yet</h3>
                <p class="myavana-body">Be the first to share your hair journey with the community!</p>
                <button class="myavana-btn-primary" onclick="document.getElementById('myavana-create-post-btn').click()">
                    Share Your First Post
                </button>
            </div>

            <!-- Load More Button -->
            <div class="myavana-feed-load-more" id="myavana-feed-load-more" style="display: none;">
                <button class="myavana-btn-secondary" id="myavana-load-more-btn">
                    Load More Stories
                </button>
            </div>
        </div>
    </div>

    <!-- Create Post Modal -->
    <div class="myavana-modal" id="myavana-create-post-modal">
        <div class="myavana-modal-overlay"></div>
        <div class="myavana-modal-content">
            <div class="myavana-modal-header">
                <h2 class="myavana-subheader">Share Your Hair Journey</h2>
                <button class="myavana-modal-close" id="myavana-close-modal">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            <div class="myavana-modal-body">
                <form id="myavana-create-post-form">
                    <div class="myavana-form-group">
                        <label class="myavana-form-label">Title</label>
                        <input type="text"
                               name="title"
                               class="myavana-form-input"
                               placeholder="e.g., 6 months of growth! 🌱"
                               required>
                    </div>

                    <div class="myavana-form-group">
                        <label class="myavana-form-label">Share Your Story</label>
                        <textarea name="content"
                                  class="myavana-form-textarea"
                                  rows="4"
                                  placeholder="Tell the community about your journey, products you love, tips you've learned..."
                                  required></textarea>
                    </div>

                    <div class="myavana-form-group">
                        <label class="myavana-form-label">Add Photos (Optional)</label>
                        <div class="myavana-upload-area" id="myavana-upload-area">
                            <input type="file"
                                   id="myavana-post-image"
                                   name="post_image"
                                   accept="image/*"
                                   style="display: none;">
                            <div class="myavana-upload-prompt">
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="var(--myavana-coral)" stroke-width="2">
                                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                    <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                    <polyline points="21 15 16 10 5 21"></polyline>
                                </svg>
                                <p class="myavana-body">Click to upload or drag and drop</p>
                            </div>
                            <div class="myavana-upload-preview" id="myavana-upload-preview" style="display: none;"></div>
                        </div>
                    </div>

                    <div class="myavana-form-group">
                        <label class="myavana-form-label">Post Type</label>
                        <select name="post_type" class="myavana-form-select">
                            <option value="progress">Progress Update</option>
                            <option value="transformation">Before & After</option>
                            <option value="routine">Routine Share</option>
                            <option value="products">Product Review</option>
                            <option value="tips">Tips & Advice</option>
                            <option value="general">General</option>
                        </select>
                    </div>

                    <div class="myavana-form-group">
                        <label class="myavana-form-label">Privacy</label>
                        <div class="myavana-radio-group">
                            <label class="myavana-radio-label">
                                <input type="radio" name="privacy_level" value="public" checked>
                                <span class="myavana-radio-custom"></span>
                                <span class="myavana-body">Public - Everyone can see</span>
                            </label>
                            <label class="myavana-radio-label">
                                <input type="radio" name="privacy_level" value="followers">
                                <span class="myavana-radio-custom"></span>
                                <span class="myavana-body">Followers Only</span>
                            </label>
                        </div>
                    </div>

                    <div class="myavana-modal-footer">
                        <button type="button" class="myavana-btn-secondary" id="myavana-cancel-post">
                            Cancel
                        </button>
                        <button type="submit" class="myavana-btn-primary">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 2L11 13"></path>
                                <path d="M22 2L15 22L11 13L2 9L22 2Z"></path>
                            </svg>
                            Share Post
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Pass data to JavaScript
        window.myavanaCommunitySettings = {
            ajaxUrl: '<?php echo admin_url('admin-ajax.php'); ?>',
            nonce: '<?php echo wp_create_nonce('myavana_nonce'); ?>',
            userId: <?php echo get_current_user_id(); ?>,
            currentFilter: '<?php echo esc_js($atts['filter']); ?>',
            perPage: <?php echo intval($atts['per_page']); ?>,
            currentPage: 1
        };
    </script>

    <?php
    return ob_get_clean();
}
