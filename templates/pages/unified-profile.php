<?php
/**
 * Unified Profile Management Page
 * Combines Hair Journey and Community features in one comprehensive profile
 *
 * @package Myavana_Hair_Journey
 * @version 1.0.0
 */

function upm_cm_unified_profile_shortcode($atts = []) {
    // Check login
    if (!is_user_logged_in()) {
        return '<div class="upm-cm-container"><div class="upm-cm-login-required"><h2>Please sign in to view your profile</h2></div></div>';
    }

    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;

    // Parse attributes
    $atts = shortcode_atts([
        'view_user_id' => $user_id, // Allow viewing other user's profiles
        'default_tab' => 'journey'
    ], (array) $atts, 'upm_cm_unified_profile');

    $view_user_id = intval($atts['view_user_id']);
    $is_own_profile = ($view_user_id === $user_id);

    // Get user data
    $view_user = get_userdata($view_user_id);
    if (!$view_user) {
        return '<div class="upm-cm-error">User not found</div>';
    }

    // Check privacy if viewing someone else's profile
    if (!$is_own_profile) {
        $can_view = Myavana_Profile_Privacy::can_view_profile($view_user_id, $user_id);
        if (!$can_view) {
            return '<div class="upm-cm-profile-private">
                        <div class="upm-cm-private-icon">🔒</div>
                        <h2>This Profile is Private</h2>
                        <p>' . esc_html($view_user->display_name) . ' has chosen to keep their profile private.</p>
                    </div>';
        }
    }

    // Fetch user data
    $shared_data = Myavana_Data_Manager::get_journey_data($view_user_id);
    $community_stats = Myavana_Community_Integration::get_community_stats($view_user_id);
    $social_stats = Myavana_Social_Features::get_user_social_stats($view_user_id);

    ob_start();
    ?>

    <div class="upm-cm-unified-profile" data-user-id="<?php echo esc_attr($view_user_id); ?>" data-is-own="<?php echo $is_own_profile ? 'true' : 'false'; ?>">

        <!-- Mobile Bottom Navigation -->
        <nav class="upm-cm-mobile-bottom-nav">
            <a href="/hair-journey/" class="upm-cm-nav-item">
                <svg class="upm-cm-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                </svg>
                <span>Home</span>
            </a>
            <a href="/hair-journey/" class="upm-cm-nav-item">
                <svg class="upm-cm-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="20" x2="18" y2="10"></line>
                    <line x1="12" y1="20" x2="12" y2="4"></line>
                    <line x1="6" y1="20" x2="6" y2="14"></line>
                </svg>
                <span>Journey</span>
            </a>
            <button class="upm-cm-nav-item upm-cm-create-btn" onclick="upmCmShowCreateMenu()">
                <svg class="upm-cm-nav-icon upm-cm-create-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="16"></line>
                    <line x1="8" y1="12" x2="16" y2="12"></line>
                </svg>
                <span>Create</span>
            </button>
            <a href="/community/" class="upm-cm-nav-item">
                <svg class="upm-cm-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                <span>Community</span>
            </a>
            <a href="/profile/" class="upm-cm-nav-item active">
                <svg class="upm-cm-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                <span>Profile</span>
            </a>
        </nav>

        <!-- Desktop Navigation -->
        <nav class="myavana-luxury-nav">
            <div class="myavana-luxury-nav-container">
                <a href="<?php echo home_url(); ?>" class="myavana-luxury-logo">
                    <div class="myavana-logo-section">
                        <img src="<?php echo esc_url(home_url()); ?>/wp-content/plugins/myavana-hair-journey/assets/images/myavana-primary-logo.png"
                            alt="Myavana Logo" class="myavana-logo" />
                    </div>
                </a>

                <div class="myavana-luxury-nav-menu">
                    <a href="/hair-journey/" class="myavana-luxury-nav-link">My Hair Journey</a>
                    <a href="/community/" class="myavana-luxury-nav-link">Community</a>
                    <a href="/profile/" class="myavana-luxury-nav-link active">Profile</a>
                    <a href="<?php echo wp_logout_url(home_url()); ?>" class="myavana-luxury-nav-link">Logout</a>
                </div>

                <button class="myavana-luxury-mobile-toggle" aria-label="Toggle menu">
                    <span></span><span></span><span></span>
                </button>
            </div>
        </nav>

        <!-- Profile Header/Hero Section -->
        <div class="upm-cm-profile-hero">
            <div class="upm-cm-cover-photo" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <!-- Cover photo can be customizable later -->
            </div>

            <div class="upm-cm-profile-header-content">
                <div class="upm-cm-profile-avatar-section">
                    <div class="upm-cm-avatar-wrapper">
                        <img src="<?php echo get_avatar_url($view_user_id, 150); ?>"
                             alt="<?php echo esc_attr($view_user->display_name); ?>"
                             class="upm-cm-profile-avatar">
                        <?php if ($is_own_profile): ?>
                        <button class="upm-cm-avatar-edit-btn" onclick="upmCmEditAvatar()">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 20h4L18.5 9.5a2.828 2.828 0 1 0-4-4L4 16v4z"></path>
                                <path d="M13.5 6.5l4 4"></path>
                            </svg>
                        </button>
                        <?php endif; ?>
                    </div>

                    <!-- Story Circles -->
                    <div class="upm-cm-story-highlights">
                        <button class="upm-cm-story-circle upm-cm-add-story" onclick="upmCmCreateStory()">
                            <div class="upm-cm-story-add-icon">+</div>
                            <span class="upm-cm-story-label">Add Story</span>
                        </button>
                        <!-- Highlights will be loaded via JS -->
                        <div id="upm-cm-highlights-container"></div>
                    </div>
                </div>

                <div class="upm-cm-profile-info">
                    <div class="upm-cm-profile-name-section">
                        <h1 class="upm-cm-profile-name"><?php echo esc_html($view_user->display_name); ?></h1>
                        <p class="upm-cm-profile-username">@<?php echo esc_html($view_user->user_login); ?></p>
                        <?php if (!empty($shared_data['about_me'])): ?>
                        <p class="upm-cm-profile-bio"><?php echo esc_html($shared_data['about_me']); ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Quick Stats Bar -->
                    <div class="upm-cm-stats-bar">
                        <div class="upm-cm-stat">
                            <span class="upm-cm-stat-value"><?php echo $shared_data['stats']['days_active']; ?></span>
                            <span class="upm-cm-stat-label">Days Active</span>
                        </div>
                        <div class="upm-cm-stat">
                            <span class="upm-cm-stat-value"><?php echo count($shared_data['entries']); ?></span>
                            <span class="upm-cm-stat-label">Entries</span>
                        </div>
                        <div class="upm-cm-stat">
                            <span class="upm-cm-stat-value"><?php echo $community_stats['posts_count']; ?></span>
                            <span class="upm-cm-stat-label">Posts</span>
                        </div>
                        <div class="upm-cm-stat">
                            <span class="upm-cm-stat-value"><?php echo $social_stats['followers_count']; ?></span>
                            <span class="upm-cm-stat-label">Followers</span>
                        </div>
                        <div class="upm-cm-stat">
                            <span class="upm-cm-stat-value"><?php echo $community_stats['community_points']; ?></span>
                            <span class="upm-cm-stat-label">Points</span>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="upm-cm-profile-actions">
                        <?php if ($is_own_profile): ?>
                        <button class="upm-cm-btn upm-cm-btn-primary" onclick="upmCmEditProfile()">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                            </svg>
                            Edit Profile
                        </button>
                        <button class="upm-cm-btn upm-cm-btn-secondary" onclick="upmCmOpenSettings()">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="3"></circle>
                                <path d="M12 1v6m0 6v6m5.657-12.657l-4.243 4.243m-2.828 2.828l-4.243 4.243m12.728 0l-4.243-4.243m-2.828-2.828L1.343 5.657"></path>
                            </svg>
                            Settings
                        </button>
                        <?php else: ?>
                        <button class="upm-cm-btn upm-cm-btn-primary upm-cm-follow-btn" data-user-id="<?php echo $view_user_id; ?>">
                            Follow
                        </button>
                        <button class="upm-cm-btn upm-cm-btn-secondary" onclick="upmCmSendMessage(<?php echo $view_user_id; ?>)">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                            </svg>
                            Message
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabbed Content Area -->
        <div class="upm-cm-tabs-container">
            <!-- Tab Navigation -->
            <nav class="upm-cm-tabs-nav">
                <button class="upm-cm-tab-btn active" data-tab="journey">
                    <svg class="upm-cm-tab-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="20" x2="18" y2="10"></line>
                        <line x1="12" y1="20" x2="12" y2="4"></line>
                        <line x1="6" y1="20" x2="6" y2="14"></line>
                    </svg>
                    <span>My Journey</span>
                </button>
                <button class="upm-cm-tab-btn" data-tab="community">
                    <svg class="upm-cm-tab-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="7"></rect>
                        <rect x="14" y="3" width="7" height="7"></rect>
                        <rect x="14" y="14" width="7" height="7"></rect>
                        <rect x="3" y="14" width="7" height="7"></rect>
                    </svg>
                    <span>Community</span>
                </button>
                <button class="upm-cm-tab-btn" data-tab="goals">
                    <svg class="upm-cm-tab-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                    </svg>
                    <span>Goals & Routines</span>
                </button>
                <button class="upm-cm-tab-btn" data-tab="analytics">
                    <svg class="upm-cm-tab-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                    </svg>
                    <span>Analytics</span>
                </button>
                <?php if ($is_own_profile): ?>
                <button class="upm-cm-tab-btn" data-tab="settings">
                    <svg class="upm-cm-tab-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3"></circle>
                        <path d="M12 1v6m0 6v6"></path>
                    </svg>
                    <span>Settings</span>
                </button>
                <?php endif; ?>
            </nav>

            <!-- Tab Content Panels -->
            <div class="upm-cm-tabs-content">

                <!-- My Journey Tab -->
                <div class="upm-cm-tab-panel active" id="upm-cm-tab-journey">
                    <div class="upm-cm-journey-header">
                        <h2>My Hair Journey Timeline</h2>
                        <?php if ($is_own_profile): ?>
                        <div class="upm-cm-journey-actions">
                            <button class="upm-cm-btn upm-cm-btn-sm" onclick="createEntry()">+ Entry</button>
                            <button class="upm-cm-btn upm-cm-btn-sm" onclick="openAIAnalysisModal()">Smart Entry</button>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- View Toggle -->
                    <div class="upm-cm-view-toggle">
                        <button class="upm-cm-view-btn active" data-view="timeline">Timeline</button>
                        <button class="upm-cm-view-btn" data-view="calendar">Calendar</button>
                        <button class="upm-cm-view-btn" data-view="grid">Grid</button>
                    </div>

                    <!-- Journey Content (will be loaded via existing timeline JS) -->
                    <div id="upm-cm-journey-content" class="upm-cm-content-area">
                        <!-- Timeline will be injected here -->
                    </div>
                </div>

                <!-- Community Activity Tab -->
                <div class="upm-cm-tab-panel" id="upm-cm-tab-community">
                    <div class="upm-cm-community-header">
                        <h2>Community Posts</h2>
                        <?php if ($is_own_profile): ?>
                        <button class="upm-cm-btn upm-cm-btn-sm" onclick="upmCmCreatePost()">+ New Post</button>
                        <?php endif; ?>
                    </div>

                    <!-- Community Stats -->
                    <div class="upm-cm-community-stats-grid">
                        <div class="upm-cm-stat-card">
                            <div class="upm-cm-stat-icon">📝</div>
                            <div class="upm-cm-stat-info">
                                <span class="upm-cm-stat-number"><?php echo $community_stats['posts_count']; ?></span>
                                <span class="upm-cm-stat-text">Posts</span>
                            </div>
                        </div>
                        <div class="upm-cm-stat-card">
                            <div class="upm-cm-stat-icon">❤️</div>
                            <div class="upm-cm-stat-info">
                                <span class="upm-cm-stat-number"><?php echo $community_stats['total_likes']; ?></span>
                                <span class="upm-cm-stat-text">Likes</span>
                            </div>
                        </div>
                        <div class="upm-cm-stat-card">
                            <div class="upm-cm-stat-icon">💬</div>
                            <div class="upm-cm-stat-info">
                                <span class="upm-cm-stat-number"><?php echo $community_stats['total_comments']; ?></span>
                                <span class="upm-cm-stat-text">Comments</span>
                            </div>
                        </div>
                        <div class="upm-cm-stat-card">
                            <div class="upm-cm-stat-icon">📊</div>
                            <div class="upm-cm-stat-info">
                                <span class="upm-cm-stat-number"><?php echo $community_stats['engagement_rate']; ?>%</span>
                                <span class="upm-cm-stat-text">Engagement</span>
                            </div>
                        </div>
                    </div>

                    <!-- User's Posts Grid -->
                    <div id="upm-cm-user-posts-grid" class="upm-cm-posts-grid">
                        <!-- Posts will be loaded via AJAX -->
                    </div>
                </div>

                <!-- Goals & Routines Tab -->
                <div class="upm-cm-tab-panel" id="upm-cm-tab-goals">
                    <div class="upm-cm-goals-routines-container">
                        <!-- Goals Section -->
                        <div class="upm-cm-section">
                            <div class="upm-cm-section-header">
                                <h2>Active Goals</h2>
                                <?php if ($is_own_profile): ?>
                                <button class="upm-cm-btn upm-cm-btn-sm" onclick="createGoal()">+ Goal</button>
                                <?php endif; ?>
                            </div>
                            <div id="upm-cm-goals-list" class="upm-cm-goals-list">
                                <!-- Goals loaded via JS -->
                            </div>
                        </div>

                        <!-- Routines Section -->
                        <div class="upm-cm-section">
                            <div class="upm-cm-section-header">
                                <h2>Current Routines</h2>
                                <?php if ($is_own_profile): ?>
                                <button class="upm-cm-btn upm-cm-btn-sm" onclick="createRoutine()">+ Routine</button>
                                <?php endif; ?>
                            </div>
                            <div id="upm-cm-routines-list" class="upm-cm-routines-list">
                                <!-- Routines loaded via JS -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Analytics Tab -->
                <div class="upm-cm-tab-panel" id="upm-cm-tab-analytics">
                    <div class="upm-cm-analytics-header">
                        <h2>Hair Journey Analytics</h2>
                        <div class="upm-cm-analytics-actions">
                            <select id="upm-cm-analytics-period" class="upm-cm-select">
                                <option value="30">Last 30 Days</option>
                                <option value="90">Last 3 Months</option>
                                <option value="180">Last 6 Months</option>
                                <option value="365">Last Year</option>
                                <option value="all">All Time</option>
                            </select>
                            <?php if ($is_own_profile): ?>
                            <button class="upm-cm-btn upm-cm-btn-sm" onclick="upmCmExportJourney()">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                    <polyline points="7 10 12 15 17 10"></polyline>
                                    <line x1="12" y1="15" x2="12" y2="3"></line>
                                </svg>
                                Export
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div id="upm-cm-analytics-content" class="upm-cm-analytics-grid">
                        <!-- Analytics charts and insights loaded via JS -->
                    </div>
                </div>

                <!-- Settings Tab (only for own profile) -->
                <?php if ($is_own_profile): ?>
                <div class="upm-cm-tab-panel" id="upm-cm-tab-settings">
                    <div class="upm-cm-settings-container">
                        <h2>Profile Settings</h2>

                        <!-- Settings sections loaded via JS -->
                        <div id="upm-cm-settings-content">
                            <!-- Privacy, notifications, etc. -->
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Floating Action Button (Mobile) -->
        <?php if ($is_own_profile): ?>
        <button class="upm-cm-fab" onclick="upmCmShowCreateMenu()" aria-label="Create">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
        </button>
        <?php endif; ?>
    </div>

    <!-- Create Menu Modal -->
    <div id="upm-cm-create-menu" class="upm-cm-modal" style="display: none;">
        <div class="upm-cm-modal-overlay" onclick="upmCmCloseCreateMenu()"></div>
        <div class="upm-cm-create-menu-content">
            <button class="upm-cm-create-option" onclick="createEntry(); upmCmCloseCreateMenu();">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 20h4L18.5 9.5a2.828 2.828 0 1 0-4-4L4 16v4z"></path>
                </svg>
                <span>Create Entry</span>
            </button>
            <button class="upm-cm-create-option" onclick="openAIAnalysisModal(); upmCmCloseCreateMenu();">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                </svg>
                <span>Smart Entry (AI)</span>
            </button>
            <button class="upm-cm-create-option" onclick="createGoal(); upmCmCloseCreateMenu();">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                </svg>
                <span>Create Goal</span>
            </button>
            <button class="upm-cm-create-option" onclick="createRoutine(); upmCmCloseCreateMenu();">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
                <span>Create Routine</span>
            </button>
            <button class="upm-cm-create-option" onclick="upmCmCreateStory(); upmCmCloseCreateMenu();">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                    <circle cx="12" cy="13" r="4"></circle>
                </svg>
                <span>Create Story</span>
            </button>
            <button class="upm-cm-create-option" onclick="upmCmCreatePost(); upmCmCloseCreateMenu();">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                    <circle cx="8.5" cy="8.5" r="1.5"></circle>
                    <polyline points="21 15 16 10 5 21"></polyline>
                </svg>
                <span>Create Post</span>
            </button>
        </div>
    </div>

    <script>
        // Pass data to JavaScript
        window.upmCmProfileData = {
            userId: <?php echo $view_user_id; ?>,
            currentUserId: <?php echo $user_id; ?>,
            isOwnProfile: <?php echo $is_own_profile ? 'true' : 'false'; ?>,
            ajaxUrl: '<?php echo admin_url('admin-ajax.php'); ?>',
            nonce: '<?php echo wp_create_nonce('myavana_nonce'); ?>',
            defaultTab: '<?php echo esc_js($atts['default_tab']); ?>'
        };
    </script>

    <?php
    return ob_get_clean();
}

// Register shortcode
add_shortcode('upm_cm_unified_profile', 'upm_cm_unified_profile_shortcode');
