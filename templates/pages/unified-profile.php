<?php
/**
 * Unified Profile Page - Hair Journey + Community
 *
 * This template creates a comprehensive profile page that integrates both
 * Hair Journey and Community features, reusing existing components while
 * adding community-specific functionality.
 *
 * @package Myavana_Hair_Journey
 * @version 1.0.0
 */

function myavana_unified_profile_shortcode($atts = [], $content = null) {
    // Check login
    $is_logged_in = is_user_logged_in();
    $current_user = wp_get_current_user();

    if (!$is_logged_in) {
        return '<div class="myavana-profile-container"><div class="myavana-profile-empty"><h2>Please sign in to view your profile</h2></div></div>';
    }

    // Parse attributes
    $atts = shortcode_atts([
        'user_id' => get_current_user_id(),
        'view' => 'overview'
    ], (array) $atts, 'myavana_unified_profile');

    $user_id = intval($atts['user_id']);
    $is_owner = ($user_id === get_current_user_id());

    // Fetch Hair Journey data using centralized data manager
    $shared_data = Myavana_Data_Manager::get_journey_data($user_id);

    // Extract commonly used variables
    $user_data = $shared_data['user_data'];
    $user_profile = $shared_data['profile'];
    $hair_goals = $shared_data['hair_goals'];
    $current_routine = $shared_data['current_routine'];
    $user_stats = $shared_data['stats'];
    $analytics_data = $shared_data['analytics'];
    $total_entries = $analytics_data['total_entries'];
    $analysis_history = $shared_data['analysis_history'];

    // Extract analysis snapshots from profile
    $snapshots = $user_profile->hair_analysis_snapshots ? json_decode($user_profile->hair_analysis_snapshots, true) : [];
    usort($snapshots, function($a, $b) {
        return strtotime($b['timestamp']) <=> strtotime($a['timestamp']);
    });

    // Analysis limit info
    $analysis_limit_info = $shared_data['analysis_limit_info'];
    $analysis_limit = $analysis_limit_info['limit'];
    $analysis_count = $analysis_limit_info['count'];
    $can_analyze = $analysis_limit_info['can_analyze'];

    // Generate dynamic AI insight
    $ai_insight = myavana_generate_ai_insights_new($user_id);

    // Fetch Community data
    global $wpdb;
    $posts_table = $wpdb->prefix . 'myavana_community_posts';
    $followers_table = $wpdb->prefix . 'myavana_user_followers';

    // Get community stats
    $total_posts = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $posts_table WHERE user_id = %d AND status = 'published'",
        $user_id
    ));

    $follower_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $followers_table WHERE following_id = %d",
        $user_id
    ));

    $following_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $followers_table WHERE follower_id = %d",
        $user_id
    ));

    // Calculate gamification points (placeholder - should be from gamification system)
    $total_points = ($total_entries * 10) + ($total_posts * 15) + ($user_stats['days_active'] * 5);

    // Get additional user profile data
    $user_location = get_user_meta($user_id, 'myavana_up_location', true);
    $user_website = get_user_meta($user_id, 'myavana_up_website', true);
    $hair_concerns = get_user_meta($user_id, 'myavana_up_hair_concerns', true) ?: [];
    $hair_porosity = get_user_meta($user_id, 'hair_porosity', true);
    $hair_length = get_user_meta($user_id, 'hair_length', true);
    $structured_goals = get_user_meta($user_id, 'myavana_hair_goals_structured', true) ?: [];

    // Time-based greeting
    $hour = date('G');
    if ($hour < 12) {
        $greeting = 'Good Morning';
        $greeting_icon = '🌅';
    } elseif ($hour < 18) {
        $greeting = 'Good Afternoon';
        $greeting_icon = '☀️';
    } else {
        $greeting = 'Good Evening';
        $greeting_icon = '🌙';
    }
    $partials_dir = __DIR__ . '/partials';

    ob_start();
    ?>
    <div class="myavana-unified-profile-container" data-theme="light"> 
        <!-- Luxury Navigation -->
        <nav class="myavana-luxury-nav">
            <div class="myavana-luxury-nav-container">
                <a href="<?php echo home_url(); ?>" class="myavana-luxury-logo">
                    <div class="myavana-logo-section">
                        <img src="<?php echo esc_url(home_url()); ?>/wp-content/plugins/myavana-hair-journey/assets/images/myavana-primary-logo.png"
                            alt="Myavana Logo" class="myavana-logo" />
                    </div>
                </a>

                <?php if (!$is_logged_in): ?>
                    <!-- GUEST NAV -->
                    <div class="myavana-luxury-nav-menu">
                        <a href="#features" class="myavana-luxury-nav-link">Features</a>
                        <a href="#how-it-works" class="myavana-luxury-nav-link">How It Works</a>
                        <a href="#" onclick="showMyavanaModal('login'); return false;" class="myavana-luxury-nav-link myavana-nav-signin-mobile">
                            Sign In
                        </a>
                    </div>

                    <div class="myavana-luxury-nav-actions">
                        <button class="myavana-luxury-btn-secondary" onclick="showMyavanaModal('login')">Sign In</button>
                        <button class="myavana-luxury-btn-primary" onclick="showMyavanaModal('register')">Start Your Journey</button>
                    </div>

                <?php else: ?>
                    <!-- LOGGED-IN NAV -->
                    <div class="myavana-luxury-nav-menu" id="mainNavMenu">
                        <a href="/hair-journey/" class="myavana-luxury-nav-link">My Hair Journey</a>
                        <a href="/community/" class="myavana-luxury-nav-link">Community</a>
                        <!-- <a href="/members/admin/hair_insights/" class="myavana-luxury-nav-link">Analytics</a> -->
                        <a style="cursor: pointer;" class="myavana-luxury-nav-link" onclick="createGoal()">+ Goal</a>
                            <a style="cursor: pointer;" class="myavana-luxury-nav-link" onclick="createRoutine()">+ Routine</a>
                            <a style="cursor: pointer;" class="myavana-luxury-nav-link" onclick="openAIAnalysisModal()">Smart Entry</a>
                            <a style="cursor: pointer;" class="myavana-luxury-nav-link" onclick="createEntry()">+ Entry</a>
                        <!-- Action Buttons - Desktop -->
                        <!-- <div class="myavana-luxury-nav-action-buttons desktop-only">
                           
                        </div> -->

                        <!-- Logout always visible on desktop -->
                        <a href="<?php echo wp_logout_url(home_url()); ?>" class="myavana-luxury-nav-link myavana-nav-logout-desktop">
                            Logout
                        </a>
                    </div>

                    

                    <!-- Mobile Menu Toggle -->
                    <!-- CORRECT — only jQuery handles it -->
                    <button class="myavana-luxury-mobile-toggle" aria-label="Toggle menu">
                        <span></span><span></span><span></span>
                    </button>
                <?php endif; ?>

                <!-- MOBILE SLIDE-OUT MENU (only for logged-in users) -->
                <?php if ($is_logged_in): ?>
                <div class="myavana-mobile-menu-overlay" id="mobileMenuOverlay" onclick="toggleMobileMenu()"></div>
                <div class="myavana-mobile-menu-panel" id="mobileMenuPanel">
                    <div class="mobile-menu-header">
                        <div class="mobile-menu-user">
                            <img src="<?php echo get_avatar_url($current_user->ID, ['size' => 60]); ?>" alt="Avatar" class="mobile-menu-avatar">
                            <div>
                                <strong><?php echo esc_html($current_user->display_name); ?></strong>
                                <small>Welcome back!</small>
                            </div>
                        </div>
                    </div>

                    <div class="mobile-menu-links">
                        <a href="/hair-journey/">My Hair Journey</a>
                        <a href="/members/admin/hair_insights/">Analytics</a>
                        <hr>
                        <button type="button" class="mobile-menu-action" onclick="createGoal(); toggleMobileMenu()">+ Goal</button>
                        <button type="button" class="mobile-menu-action" onclick="createRoutine(); toggleMobileMenu()">+ Routine</button>
                        <button type="button" class="mobile-menu-action smart" onclick="openAIAnalysisModal(); toggleMobileMenu()">Smart Entry</button>
                        <button type="button" class="mobile-menu-action primary" onclick="createEntry(); toggleMobileMenu()">+ Entry</button>
                        <hr>
                        <a href="<?php echo wp_logout_url(home_url()); ?>" class="mobile-menu-logout">Logout</a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </nav>
        <!-- Dashboard Header -->
        <div class="myavana-up-dashboard-header">
            <div class="myavana-up-header-top">
                <div class="myavana-up-profile-section">
                    <div class="myavana-up-avatar-wrapper">
                        <?php echo get_avatar($user_id, 80); ?>
                        <?php if ($is_owner): ?>
                        <button class="myavana-up-avatar-edit" onclick="myavanaUpOpenEditOffcanvas()" title="Edit Profile Picture">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 20h9"></path>
                                <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                            </svg>
                        </button>
                        <?php endif; ?>
                    </div>
                    <div class="myavana-up-profile-details">
                        <div class="myavana-up-greeting"><?php echo esc_html($greeting_icon . ' ' . $greeting); ?>, <?php echo esc_html($current_user->display_name); ?></div>
                        <?php if ($user_profile->bio): ?>
                        <p class="myavana-up-bio"><?php echo esc_html($user_profile->bio); ?></p>
                        <?php endif; ?>
                        <div class="myavana-up-meta-info">
                            <span class="myavana-up-username">@<?php echo esc_html($current_user->user_login); ?></span>
                            <?php if ($user_location): ?>
                            <span class="myavana-up-location">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                    <circle cx="12" cy="10" r="3"></circle>
                                </svg>
                                <?php echo esc_html($user_location); ?>
                            </span>
                            <?php endif; ?>
                            <?php if ($user_website): ?>
                            <a href="<?php echo esc_url($user_website); ?>" class="myavana-up-website" target="_blank" rel="noopener">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="2" y1="12" x2="22" y2="12"></line>
                                    <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
                                </svg>
                                <?php echo esc_html(parse_url($user_website, PHP_URL_HOST)); ?>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="myavana-up-header-stats">
                    <div class="myavana-up-stat-pill">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2.69l5.66 5.66a8 8 0 11-11.31 0z"/>
                        </svg>
                        <span><?php echo esc_html($user_stats['days_active']); ?> Days Active</span>
                    </div>
                    <div class="myavana-up-stat-pill">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                        </svg>
                        <span><?php echo esc_html($total_entries); ?> Entries</span>
                    </div>
                    <div class="myavana-up-stat-pill">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                        </svg>
                        <span><?php echo esc_html($total_posts); ?> Posts</span>
                    </div>
                    <div class="myavana-up-stat-pill clickable" onclick="openFollowersModal('followers')">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                        </svg>
                        <span><?php echo esc_html($follower_count); ?> Followers</span>
                    </div>
                    <div class="myavana-up-stat-pill clickable" onclick="openFollowersModal('following')">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                        <span><?php echo esc_html($following_count); ?> Following</span>
                    </div>
                    <div class="myavana-up-stat-pill">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                        </svg>
                        <span><?php echo esc_html($total_points); ?> Points</span>
                    </div>
                </div>
            </div>

            <div class="myavana-up-header-bottom">
                <?php if ($is_owner): ?>
                <div class="myavana-up-action-buttons">
                    <button class="myavana-up-btn myavana-up-btn-primary" onclick="createGoal()">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                        New Goal
                    </button>
                    <button class="myavana-up-btn myavana-up-btn-secondary" onclick="createRoutine()">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                        New Routine
                    </button>
                    <button class="myavana-up-btn myavana-up-btn-secondary" onclick="createSmartEntry()">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                        </svg>
                        Smart Entry
                    </button>
                    <button class="myavana-up-btn myavana-up-btn-secondary" onclick="createEntry()">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        New Entry
                    </button>
                    <button class="myavana-up-btn myavana-up-btn-outline" onclick="myavanaUpOpenEditOffcanvas()">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                        Edit Profile
                    </button>
                </div>
                <?php else: ?>
                <div class="myavana-up-action-buttons">
                    <button class="myavana-up-btn myavana-up-btn-primary follow-user-btn" data-user-id="<?php echo esc_attr($user_id); ?>">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="8.5" cy="7" r="4"></circle>
                            <line x1="20" y1="8" x2="20" y2="14"></line>
                            <line x1="23" y1="11" x2="17" y2="11"></line>
                        </svg>
                        Follow
                    </button>
                    <button class="myavana-up-btn myavana-up-btn-secondary" onclick="sendMessage(<?php echo esc_js($user_id); ?>)">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                        </svg>
                        Message
                    </button>
                </div>
                <?php endif; ?>

                <div class="myavana-up-streak-card">
                    <div class="myavana-up-streak-icon">🔥</div>
                    <div class="myavana-up-streak-info">
                        <div class="myavana-up-streak-count"><?php echo esc_html($user_stats['days_active']); ?> Day Streak</div>
                        <div class="myavana-up-streak-label">Keep it going!</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Profile Information Cards -->
        <div class="myavana-up-profile-info-section">
            <div class="myavana-up-info-grid">
                <!-- Hair Profile Card -->
                <div class="myavana-up-info-card">
                    <div class="myavana-up-card-header">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2.69l5.66 5.66a8 8 0 11-11.31 0z"/>
                        </svg>
                        <h3>Hair Profile</h3>
                    </div>
                    <div class="myavana-up-card-body">
                        <div class="myavana-up-profile-grid">
                            <div class="myavana-up-profile-item">
                                <div class="myavana-up-profile-label">Hair Type</div>
                                <div class="myavana-up-profile-value">
                                    <?php echo esc_html($user_profile->hair_type ?: 'Not set'); ?>
                                </div>
                            </div>
                            <div class="myavana-up-profile-item">
                                <div class="myavana-up-profile-label">Porosity</div>
                                <div class="myavana-up-profile-value">
                                    <?php echo esc_html($hair_porosity ?: 'Not set'); ?>
                                </div>
                            </div>
                            <div class="myavana-up-profile-item">
                                <div class="myavana-up-profile-label">Length</div>
                                <div class="myavana-up-profile-value">
                                    <?php echo esc_html($hair_length ?: 'Not set'); ?>
                                </div>
                            </div>
                            <div class="myavana-up-profile-item">
                                <div class="myavana-up-profile-label">Journey Stage</div>
                                <div class="myavana-up-profile-value">
                                    <?php echo esc_html($user_profile->hair_journey_stage ?: 'Not set'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Hair Concerns Card -->
                <div class="myavana-up-info-card">
                    <div class="myavana-up-card-header">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                        <h3>Hair Concerns</h3>
                    </div>
                    <div class="myavana-up-card-body">
                        <?php if (!empty($hair_concerns) && is_array($hair_concerns)): ?>
                        <div class="myavana-up-concerns-list">
                            <?php foreach ($hair_concerns as $concern): ?>
                            <span class="myavana-up-concern-tag"><?php echo esc_html($concern); ?></span>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <p class="myavana-up-empty-state">No concerns specified</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Goals Card -->
                <div class="myavana-up-info-card myavana-up-card-wide">
                    <div class="myavana-up-card-header">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                        <h3>Hair Goals</h3>
                    </div>
                    <div class="myavana-up-card-body">
                        <?php if (!empty($structured_goals) && is_array($structured_goals)): ?>
                        <div class="myavana-up-goals-list">
                            <?php foreach ($structured_goals as $goal): ?>
                            <div class="myavana-up-goal-item-display">
                                <div class="myavana-up-goal-info">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                    </svg>
                                    <span class="myavana-up-goal-title"><?php echo esc_html($goal['title']); ?></span>
                                </div>
                                <div class="myavana-up-goal-progress">
                                    <div class="myavana-up-progress-bar">
                                        <div class="myavana-up-progress-fill" style="width: <?php echo esc_attr($goal['progress'] ?? 0); ?>%"></div>
                                    </div>
                                    <span class="myavana-up-progress-text"><?php echo esc_html($goal['progress'] ?? 0); ?>%</span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <p class="myavana-up-empty-state">No goals set yet. Click Edit Profile to add your hair goals!</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Stats Card -->
                <div class="myavana-up-info-card">
                    <div class="myavana-up-card-header">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22,21H2V3H4V19H6V10H10V19H12V6H16V19H18V14H22V21Z"/>
                        </svg>
                        <h3>Journey Stats</h3>
                    </div>
                    <div class="myavana-up-card-body">
                        <div class="myavana-up-stats-grid">
                            <div class="myavana-up-stat-item">
                                <div class="myavana-up-stat-number"><?php echo esc_html($user_stats['days_active']); ?></div>
                                <div class="myavana-up-stat-text">Days Active</div>
                            </div>
                            <div class="myavana-up-stat-item">
                                <div class="myavana-up-stat-number"><?php echo esc_html($total_entries); ?></div>
                                <div class="myavana-up-stat-text">Total Entries</div>
                            </div>
                            <div class="myavana-up-stat-item">
                                <div class="myavana-up-stat-number"><?php echo esc_html($total_posts); ?></div>
                                <div class="myavana-up-stat-text">Community Posts</div>
                            </div>
                            <div class="myavana-up-stat-item">
                                <div class="myavana-up-stat-number"><?php echo esc_html($total_points); ?></div>
                                <div class="myavana-up-stat-text">Total Points</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- AI Insights Card -->
                <?php if ($ai_insight && !empty($ai_insight['summary'])): ?>
                <div class="myavana-up-info-card myavana-up-card-wide">
                    <div class="myavana-up-card-header">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                        </svg>
                        <h3>AI Insights</h3>
                    </div>
                    <div class="myavana-up-card-body">
                        <div class="myavana-up-ai-insight">
                            <p><?php echo esc_html($ai_insight['summary']); ?></p>
                            <?php if (!empty($ai_insight['recommendations'])): ?>
                            <div class="myavana-up-recommendations">
                                <h4>Recommendations:</h4>
                                <ul>
                                    <?php foreach ($ai_insight['recommendations'] as $recommendation): ?>
                                    <li><?php echo esc_html($recommendation); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Profile Navigation Tabs -->
        <nav class="myavana-profile-nav">
            <div class="myavana-profile-nav-container">
                <button class="myavana-profile-tab active" data-tab="journey" onclick="switchProfileTab('journey')">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2.69l5.66 5.66a8 8 0 11-11.31 0z"/>
                    </svg>
                    My Journey
                </button>
                <button class="myavana-profile-tab" data-tab="community" onclick="switchProfileTab('community')">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                    Community
                </button>
                <button class="myavana-profile-tab" data-tab="ai-analysis" onclick="switchProfileTab('ai-analysis')">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M12,4A8,8 0 0,1 20,12A8,8 0 0,1 12,20A8,8 0 0,1 4,12A8,8 0 0,1 12,4M11,16.5L18,9.5L16.59,8.09L11,13.67L7.91,10.59L6.5,12L11,16.5Z"/>
                    </svg>
                    AI Analysis
                </button>
                <button class="myavana-profile-tab" data-tab="goals" onclick="switchProfileTab('goals')">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12,2A10,10 0 0,1 22,12A10,10 0 0,1 12,22A10,10 0 0,1 2,12A10,10 0 0,1 12,2M12,4A8,8 0 0,0 4,12A8,8 0 0,0 12,20A8,8 0 0,0 20,12A8,8 0 0,0 12,4Z"/>
                    </svg>
                    Goals & Routines
                </button>
                <button class="myavana-profile-tab" data-tab="analytics" onclick="switchProfileTab('analytics')">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22,21H2V3H4V19H6V10H10V19H12V6H16V19H18V14H22V21Z"/>
                    </svg>
                    Analytics
                </button>
                <button class="myavana-profile-tab" data-tab="settings" onclick="switchProfileTab('settings')">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3"></circle>
                        <path d="M12 1v6m0 6v6m0-12h6m-6 12H6m6-6V1m0 12h6M6 12H1"></path>
                    </svg>
                    Settings
                </button>
            </div>
        </nav>

        <!-- Profile Content Area -->
        <div class="myavana-profile-content">
            <!-- My Journey Tab -->
            <div class="myavana-profile-tab-content active" id="journeyTabContent">
                <div class="myavana-profile-grid">
                    <!-- Main Content Area -->
                    <div class="myavana-profile-main">
                        <?php
                        // Include the view-list.php partial to display entries, goals, and routines
                        if (file_exists($partials_dir . '/view-list-profile.php')) {
                            include $partials_dir . '/view-list-profile.php';
                        } else {
                            echo '<div class="myavana-empty-state"><p>No entries yet. Start your journey!</p></div>';
                        }
                        ?>
                    </div>

                    <!-- Sidebar with AI Insights -->
                    <div class="myavana-profile-sidebar">
                        <div class="myavana-profile-sidebar-card">
                            <h3 class="myavana-profile-sidebar-title">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--myavana-coral)" stroke-width="2">
                                    <path d="M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M12,4A8,8 0 0,1 20,12A8,8 0 0,1 12,20A8,8 0 0,1 4,12A8,8 0 0,1 12,4Z"/>
                                </svg>
                                AI Insights
                            </h3>
                            <div class="myavana-ai-insights-content">
                                <?php
                                $ai_insight = myavana_generate_ai_insights_new($user_id);
                                echo '<p>' . esc_html($ai_insight) . '</p>';
                                ?>
                            </div>
                        </div>

                        <!-- Quick Stats Widget -->
                        <div class="myavana-profile-sidebar-card">
                            <h3 class="myavana-profile-sidebar-title">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--myavana-coral)" stroke-width="2">
                                    <path d="M22 11.08V12a10 10 0 11-5.93-9.14"/>
                                    <polyline points="22,4 12,14.01 9,11.01"/>
                                </svg>
                                Quick Stats
                            </h3>
                            <div class="myavana-quick-stats">
                                <div class="myavana-quick-stat-item">
                                    <div class="myavana-quick-stat-label">Current Streak</div>
                                    <div class="myavana-quick-stat-value"><?php echo esc_html($analytics_data['current_streak']); ?> days</div>
                                </div>
                                <div class="myavana-quick-stat-item">
                                    <div class="myavana-quick-stat-label">Avg Health Score</div>
                                    <div class="myavana-quick-stat-value"><?php echo esc_html(number_format($analytics_data['avg_health_score'], 1)); ?>/10</div>
                                </div>
                                <div class="myavana-quick-stat-item">
                                    <div class="myavana-quick-stat-label">Total Photos</div>
                                    <div class="myavana-quick-stat-value"><?php echo esc_html($analytics_data['total_photos']); ?></div>
                                </div>
                            </div>
                        </div>

                        <!-- Hair Profile Card -->
                        <div class="myavana-profile-sidebar-card">
                            <h3 class="myavana-profile-sidebar-title">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--myavana-coral)" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"/>
                                </svg>
                                Hair Profile
                            </h3>
                            <div class="myavana-hair-profile-grid">
                                <div class="myavana-hair-profile-item">
                                    <div class="myavana-hair-profile-label">Type</div>
                                    <div class="myavana-hair-profile-value"><?php echo esc_html($user_profile->hair_type ?: '--'); ?></div>
                                </div>
                                <div class="myavana-hair-profile-item">
                                    <div class="myavana-hair-profile-label">Porosity</div>
                                    <div class="myavana-hair-profile-value"><?php echo esc_html($shared_data['hair_porosity'] ?: '--'); ?></div>
                                </div>
                                <div class="myavana-hair-profile-item">
                                    <div class="myavana-hair-profile-label">Length</div>
                                    <div class="myavana-hair-profile-value"><?php echo esc_html($shared_data['hair_length'] ?: '--'); ?></div>
                                </div>
                                <div class="myavana-hair-profile-item">
                                    <div class="myavana-hair-profile-label">Journey Stage</div>
                                    <div class="myavana-hair-profile-value"><?php echo esc_html($user_profile->hair_journey_stage ?: '--'); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Community Tab -->
            <div class="myavana-profile-tab-content" id="communityTabContent">
                <div class="myavana-community-profile-content">
                    <div class="myavana-community-stats-grid">
                        <div class="myavana-community-stat-card">
                            <div class="myavana-community-stat-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path>
                                    <polyline points="14 2 14 8 20 8"></polyline>
                                </svg>
                            </div>
                            <div class="myavana-community-stat-content">
                                <div class="myavana-community-stat-value"><?php echo esc_html($total_posts); ?></div>
                                <div class="myavana-community-stat-label">Total Posts</div>
                            </div>
                        </div>

                        <div class="myavana-community-stat-card">
                            <div class="myavana-community-stat-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                                </svg>
                            </div>
                            <div class="myavana-community-stat-content">
                                <div class="myavana-community-stat-value" id="totalLikesReceived">--</div>
                                <div class="myavana-community-stat-label">Likes Received</div>
                            </div>
                        </div>

                        <div class="myavana-community-stat-card">
                            <div class="myavana-community-stat-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                                </svg>
                            </div>
                            <div class="myavana-community-stat-content">
                                <div class="myavana-community-stat-value" id="totalCommentsReceived">--</div>
                                <div class="myavana-community-stat-label">Comments</div>
                            </div>
                        </div>

                        <div class="myavana-community-stat-card">
                            <div class="myavana-community-stat-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="9" cy="7" r="4"></circle>
                                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                </svg>
                            </div>
                            <div class="myavana-community-stat-content">
                                <div class="myavana-community-stat-value"><?php echo esc_html($follower_count); ?></div>
                                <div class="myavana-community-stat-label">Followers</div>
                            </div>
                        </div>
                    </div>

                    <!-- Community Posts Grid -->
                    <div class="myavana-community-posts-section">
                        <div class="myavana-section-header">
                            <h2 class="myavana-section-title">Community Posts</h2>
                            <div class="myavana-view-toggle">
                                <button class="myavana-view-btn active" data-view="grid" onclick="togglePostView('grid')">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="3" y="3" width="7" height="7"></rect>
                                        <rect x="14" y="3" width="7" height="7"></rect>
                                        <rect x="14" y="14" width="7" height="7"></rect>
                                        <rect x="3" y="14" width="7" height="7"></rect>
                                    </svg>
                                </button>
                                <button class="myavana-view-btn" data-view="list" onclick="togglePostView('list')">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <line x1="8" y1="6" x2="21" y2="6"></line>
                                        <line x1="8" y1="12" x2="21" y2="12"></line>
                                        <line x1="8" y1="18" x2="21" y2="18"></line>
                                        <line x1="3" y1="6" x2="3.01" y2="6"></line>
                                        <line x1="3" y1="12" x2="3.01" y2="12"></line>
                                        <line x1="3" y1="18" x2="3.01" y2="18"></line>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div class="myavana-user-posts-grid" id="userPostsGrid">
                            <!-- Posts will be loaded via JavaScript -->
                            <div class="myavana-posts-loading">
                                <div class="myavana-spinner"></div>
                                <p>Loading posts...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- AI Analysis Tab -->
            <div class="myavana-profile-tab-content" id="ai-analysisTabContent">
                <div class="myavana-ai-analysis-container">
                    <!-- AI Insights Section -->
                    <div class="myavana-profile-sidebar-card">
                        <h3 class="myavana-profile-sidebar-title">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--myavana-coral)" stroke-width="2">
                                <path d="M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M12,4A8,8 0 0,1 20,12A8,8 0 0,1 12,20A8,8 0 0,1 4,12A8,8 0 0,1 12,4Z"/>
                            </svg>
                            ✨ AI Insights
                        </h3>
                        <div class="myavana-ai-insights-content">
                            <p><?php echo esc_html($ai_insight); ?></p>
                        </div>
                    </div>

                    <div class="myavana-insights-container" data-category="all">
                        <!-- Insights will render here via JavaScript -->
                    </div>

                    <!-- Hair Analysis Section -->
                    <div class="profile-section">
                        <div class="section-header">
                            <h2 class="section-title">Hair Analysis</h2>
                            <?php if ($is_owner && $can_analyze) : ?>
                                <div class="section-edit" data-section="analysis" id="addAnalysisBtn">
                                    <i class="fas fa-plus"></i>
                                    <span>Add Analysis</span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="hair-analysis-container">
                            <?php if ($is_owner && $can_analyze): ?>
                                <div class="analysis-limit">
                                    <p>Weekly analysis limit: <?php echo $analysis_count; ?>/<?php echo $analysis_limit; ?> used</p>
                                </div>
                            <?php elseif ($is_owner && !$can_analyze): ?>
                                <p class="limit-reached">You've reached your weekly analysis limit. New analyses will be available next week.</p>
                            <?php endif; ?>

                            <!-- Hair Analysis Slider with Splide.js -->
                            <div class="analysis-slider-container">
                                <?php if (!empty($snapshots)) : ?>
                                    <div class="splide analysis-splide" id="hair-analysis-splide">
                                        <div class="splide__track">
                                            <ul class="splide__list">
                                                <?php foreach ($snapshots as $index => $snapshot) : ?>
                                                    <li class="splide__slide analysis-slide">
                                                        <div class="analysis-slide-visual">
                                                            <?php if ($snapshot['image_url'] ?? false) : ?>
                                                                <img src="<?php echo esc_url($snapshot['image_url']); ?>" alt="Hair Analysis" class="analysis-slide-image">
                                                            <?php else : ?>
                                                                <div class="analysis-slide-placeholder">
                                                                    <i class="fas fa-camera"></i>
                                                                    <span>No image available</span>
                                                                </div>
                                                            <?php endif; ?>
                                                            <div class="analysis-slide-date">
                                                                <?php echo esc_html(date('M j, Y', strtotime($snapshot['timestamp']))); ?>
                                                            </div>
                                                        </div>

                                                        <div class="analysis-slide-content">
                                                            <div class="analysis-hair-type">
                                                                <div class="hair-type-icon">
                                                                    <img src="<?php echo MYAVANA_URL; ?>assets/images/washing-hair-icon.png" alt="washing-hair-icon">
                                                                </div>
                                                                <div class="hair-type-info">
                                                                    <h3><?php echo esc_html($snapshot['hair_analysis']['curl_pattern'] ?? '--'); ?></h3>
                                                                    <p><?php echo esc_html($snapshot['hair_analysis']['type'] ?? 'Type not set'); ?></p>
                                                                </div>
                                                            </div>

                                                            <div class="analysis-metrics-grid">
                                                                <div class="analysis-metric">
                                                                    <div class="metric-value"><?php echo esc_html($snapshot['hair_analysis']['health_score'] ?? '--'); ?>%</div>
                                                                    <div class="metric-label">Health</div>
                                                                    <div class="metric-progress">
                                                                        <div class="metric-progress-fill" style="width: <?php echo esc_attr(($snapshot['hair_analysis']['health_score'] ?? 0) . '%'); ?>"></div>
                                                                    </div>
                                                                </div>
                                                                <div class="analysis-metric">
                                                                    <div class="metric-value"><?php echo esc_html($snapshot['hair_analysis']['hydration'] ?? '--'); ?>%</div>
                                                                    <div class="metric-label">Hydration</div>
                                                                    <div class="metric-progress">
                                                                        <div class="metric-progress-fill" style="width: <?php echo esc_attr(($snapshot['hair_analysis']['hydration'] ?? 0) . '%'); ?>"></div>
                                                                    </div>
                                                                </div>
                                                                <div class="analysis-metric">
                                                                    <div class="metric-value"><?php echo esc_html($snapshot['hair_analysis']['elasticity'] ?? '--'); ?></div>
                                                                    <div class="metric-label">Elasticity</div>
                                                                    <div class="metric-progress">
                                                                        <div class="metric-progress-fill" style="width: <?php echo esc_attr(($snapshot['hair_analysis']['elasticity'] ?? 0) . '%'); ?>"></div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="analysis-summary">
                                                                <?php echo esc_html(wp_trim_words($snapshot['summary'] ?? '', 25)); ?>
                                                            </div>

                                                            <div class="analysis-actions">
                                                                <?php
                                                                // Ensure required data exists
                                                                $analysisData = [
                                                                    'timestamp' => $snapshot['timestamp'] ?? '',
                                                                    'image_url' => $snapshot['image_url'] ?? '',
                                                                    'summary' => $snapshot['summary'] ?? '',
                                                                    'hair_analysis' => [
                                                                        'health_score' => $snapshot['hair_analysis']['health_score'] ?? 0,
                                                                        'hydration' => $snapshot['hair_analysis']['hydration'] ?? 0,
                                                                        'elasticity' => $snapshot['hair_analysis']['elasticity'] ?? 0,
                                                                        'type' => $snapshot['hair_analysis']['type'] ?? '--',
                                                                        'curl_pattern' => $snapshot['hair_analysis']['curl_pattern'] ?? '--',
                                                                        'porosity' => $snapshot['hair_analysis']['porosity'] ?? '--'
                                                                    ],
                                                                    'recommendations' => $snapshot['recommendations'] ?? []
                                                                ];
                                                                ?>
                                                                <button class="analysis-action-btn action-view" data-analysis='<?php echo htmlspecialchars(json_encode($analysisData), ENT_QUOTES, 'UTF-8'); ?>'>
                                                                    <i class="fas fa-search"></i> View Details
                                                                </button>
                                                                <button class="analysis-action-btn action-compare" onclick="openCompareModal()">
                                                                    <i class="fas fa-exchange-alt"></i> Compare
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    </div>
                                <?php else : ?>
                                    <div class="myavana-no-analysis">
                                        <i class="fas fa-camera-retro"></i>
                                        <p style="margin-bottom: 24px;">No hair analysis data available</p>
                                        <?php if ($is_owner && $can_analyze) : ?>
                                            <button class="myavana-button-two" id="start-first-analysis">
                                                <div class="default-btn">
                                                    <span> Create First Analysis</span>
                                                </div>
                                                <div class="hover-btn">
                                                    <span>With Myavana Ai</span>
                                                </div>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Analysis History Section -->
                            <div class="myavana-analysis-history">
                                <h3 class="myavana-history-title mb-4">Analysis History</h3>
                                <?php if (empty($analysis_history)): ?>
                                    <div class="myavana-empty-history">
                                        <i class="fas fa-history"></i>
                                        <p>No analysis history yet</p>
                                    </div>
                                <?php else: ?>
                                    <div class="myavana-history-grid">
                                        <?php foreach ($analysis_history as $index => $analysis): ?>
                                            <div class="myavana-history-card">
                                                <div class="myavana-history-header">
                                                    <h4 class="o-6"><?php echo esc_html(date('M j, Y', strtotime($analysis['date']))); ?></h4>
                                                    <div class="myavana-history-score">
                                                        <?php echo esc_html($analysis['full_analysis']['hair_analysis']['health_score'] ?? '--'); ?>%
                                                        <span>Health</span>
                                                    </div>
                                                </div>

                                                <div class="myavana-history-preview">
                                                    <p><?php echo esc_html(wp_trim_words($analysis['summary'], 15)); ?></p>
                                                </div>

                                                <div class="myavana-history-meta">
                                                    <span class="myavana-history-meta-item">
                                                        <i class="fas fa-curl"></i>
                                                        <?php echo esc_html($analysis['full_analysis']['hair_analysis']['curl_pattern'] ?? '--'); ?>
                                                    </span>
                                                    <span class="myavana-history-meta-item">
                                                        <i class="fas fa-tint"></i>
                                                        <?php echo esc_html($analysis['full_analysis']['hair_analysis']['hydration'] ?? '--'); ?>%
                                                    </span>
                                                </div>

                                                <?php
                                                // Ensure required data exists for history view
                                                $historyAnalysisData = [
                                                    'timestamp' => $analysis['date'],
                                                    'image_url' => $analysis['full_analysis']['image_url'] ?? '',
                                                    'summary' => $analysis['summary'] ?? '',
                                                    'hair_analysis' => [
                                                        'health_score' => $analysis['full_analysis']['hair_analysis']['health_score'] ?? 0,
                                                        'hydration' => $analysis['full_analysis']['hair_analysis']['hydration'] ?? 0,
                                                        'elasticity' => $analysis['full_analysis']['hair_analysis']['elasticity'] ?? 0,
                                                        'type' => $analysis['full_analysis']['hair_analysis']['type'] ?? '--',
                                                        'curl_pattern' => $analysis['full_analysis']['hair_analysis']['curl_pattern'] ?? '--',
                                                        'porosity' => $analysis['full_analysis']['hair_analysis']['porosity'] ?? '--'
                                                    ],
                                                    'recommendations' => $analysis['full_analysis']['recommendations'] ?? []
                                                ];
                                                ?>
                                                <button class="myavana-history-details-btn" data-analysis='<?php echo htmlspecialchars(json_encode($historyAnalysisData), ENT_QUOTES, 'UTF-8'); ?>'>
                                                    View Full Analysis <i class="fas fa-chevron-right"></i>
                                                </button>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Goals & Routines Tab -->
            <div class="myavana-profile-tab-content" id="goalsTabContent">
                <div class="myavana-goals-routines-grid">
                    <!-- Goals Section -->
                    <div class="myavana-goals-section">
                        <div class="myavana-section-header">
                            <h2 class="myavana-section-title">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M12,2L14.39,8.26L21,9.27L16.5,13.65L17.61,20.24L12,17.27L6.39,20.24L7.5,13.65L3,9.27L9.61,8.26L12,2Z"/>
                                </svg>
                                Hair Goals
                            </h2>
                            <?php if ($is_owner): ?>
                            <button class="myavana-btn-primary-small" onclick="createGoal()">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="12" y1="5" x2="12" y2="19"></line>
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                </svg>
                                Add Goal
                            </button>
                            <?php endif; ?>
                        </div>

                        <div class="myavana-goals-list">
                            <?php if (empty($hair_goals)): ?>
                            <div class="myavana-empty-state">
                                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path d="M12,2L14.39,8.26L21,9.27L16.5,13.65L17.61,20.24L12,17.27L6.39,20.24L7.5,13.65L3,9.27L9.61,8.26L12,2Z"/>
                                </svg>
                                <h3>No Goals Yet</h3>
                                <p>Set your first hair goal to start tracking your progress</p>
                                <?php if ($is_owner): ?>
                                <button class="myavana-btn-primary" onclick="createGoal()">Create First Goal</button>
                                <?php endif; ?>
                            </div>
                            <?php else: ?>
                                <?php foreach ($hair_goals as $idx => $goal):
                                    $title = $goal['title'] ?? 'Untitled Goal';
                                    $progress = isset($goal['progress']) ? intval($goal['progress']) : 0;
                                    $start = $goal['start_date'] ?? '';
                                    $end = $goal['end_date'] ?? '';
                                    $milestones = $goal['milestones'] ?? [];

                                    $completed_milestones = 0;
                                    foreach ($milestones as $milestone) {
                                        if (isset($milestone['achieved']) && $milestone['achieved']) {
                                            $completed_milestones++;
                                        }
                                    }
                                ?>
                                <div class="myavana-goal-card" onclick="openViewOffcanvas('goal', <?php echo esc_js($idx); ?>)">
                                    <div class="myavana-goal-header">
                                        <h3 class="myavana-goal-title"><?php echo esc_html($title); ?></h3>
                                        <span class="myavana-goal-progress-badge"><?php echo $progress; ?>%</span>
                                    </div>
                                    <?php if ($start || $end): ?>
                                    <div class="myavana-goal-dates">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                            <line x1="16" y1="2" x2="16" y2="6"></line>
                                            <line x1="8" y1="2" x2="8" y2="6"></line>
                                            <line x1="3" y1="10" x2="21" y2="10"></line>
                                        </svg>
                                        <?php
                                        if ($start) echo date('M j, Y', strtotime($start));
                                        if ($start && $end) echo ' - ';
                                        if ($end) echo date('M j, Y', strtotime($end));
                                        ?>
                                    </div>
                                    <?php endif; ?>
                                    <?php if (!empty($milestones)): ?>
                                    <div class="myavana-goal-milestones">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="20 6 9 17 4 12"></polyline>
                                        </svg>
                                        <?php echo $completed_milestones; ?>/<?php echo count($milestones); ?> milestones completed
                                    </div>
                                    <?php endif; ?>
                                    <div class="myavana-goal-progress-bar">
                                        <div class="myavana-goal-progress-fill" style="width: <?php echo $progress; ?>%"></div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Routines Section -->
                    <div class="myavana-routines-section">
                        <div class="myavana-section-header">
                            <h2 class="myavana-section-title">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                                Current Routine
                            </h2>
                            <?php if ($is_owner): ?>
                            <button class="myavana-btn-primary-small" onclick="createRoutine()">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="12" y1="5" x2="12" y2="19"></line>
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                </svg>
                                Add Step
                            </button>
                            <?php endif; ?>
                        </div>

                        <div class="myavana-routines-list">
                            <?php if (empty($current_routine)): ?>
                            <div class="myavana-empty-state">
                                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                                <h3>No Routine Set</h3>
                                <p>Create your hair care routine to stay consistent</p>
                                <?php if ($is_owner): ?>
                                <button class="myavana-btn-primary" onclick="createRoutine()">Create Routine</button>
                                <?php endif; ?>
                            </div>
                            <?php else: ?>
                                <?php foreach ($current_routine as $r_idx => $step):
                                    $r_title = $step['title'] ?? $step['name'] ?? 'Routine Step';
                                    $schedule = $step['schedule'] ?? $step['frequency'] ?? '';
                                    $description = $step['description'] ?? $step['notes'] ?? '';
                                ?>
                                <div class="myavana-routine-card" onclick="openViewOffcanvas('routine', <?php echo esc_js($r_idx); ?>)">
                                    <div class="myavana-routine-header">
                                        <div class="myavana-routine-icon">
                                            <?php echo strtoupper(substr($schedule, 0, 1)); ?>
                                        </div>
                                        <div class="myavana-routine-info">
                                            <h3 class="myavana-routine-title"><?php echo esc_html($r_title); ?></h3>
                                            <div class="myavana-routine-schedule"><?php echo esc_html($schedule); ?></div>
                                        </div>
                                    </div>
                                    <?php if ($description): ?>
                                    <p class="myavana-routine-description"><?php echo esc_html(wp_trim_words($description, 15)); ?></p>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Analytics Tab -->
            <div class="myavana-profile-tab-content" id="analyticsTabContent">
                <?php
                // Reuse analytics content from header-and-sidebar.php
                // This will include the full analytics dashboard
                ?>
                <div class="myavana-analytics-container">
                    <div class="myavana-analytics-header">
                        <div class="myavana-analytics-title">
                            <svg class="myavana-analytics-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--myavana-coral)" stroke-width="2">
                                <path d="M3 3v18h18"/>
                                <path d="M18.7 8l-5.1 5.2-2.8-2.7L7 14.3"/>
                            </svg>
                            <h2>Hair Journey Analytics</h2>
                        </div>
                        <div class="myavana-analytics-period">
                            <select id="myavanaAnalyticsPeriod" class="myavana-period-select">
                                <option value="7">Last 7 days</option>
                                <option value="30" selected>Last 30 days</option>
                                <option value="90">Last 90 days</option>
                                <option value="365">Last year</option>
                            </select>
                        </div>
                    </div>

                    <div class="myavana-analytics-stats-grid">
                        <div class="myavana-stat-card myavana-stat-primary">
                            <div class="myavana-stat-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M12 2.69l5.66 5.66a8 8 0 11-11.31 0z"/>
                                </svg>
                            </div>
                            <div class="myavana-stat-content">
                                <div class="myavana-stat-number"><?php echo esc_html($analytics_data['total_entries']); ?></div>
                                <div class="myavana-stat-label">Total Entries</div>
                            </div>
                        </div>

                        <div class="myavana-stat-card myavana-stat-success">
                            <div class="myavana-stat-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M22 11.08V12a10 10 0 11-5.93-9.14"/>
                                    <polyline points="22,4 12,14.01 9,11.01"/>
                                </svg>
                            </div>
                            <div class="myavana-stat-content">
                                <div class="myavana-stat-number"><?php echo esc_html($analytics_data['current_streak']); ?></div>
                                <div class="myavana-stat-label">Day Streak</div>
                            </div>
                        </div>

                        <div class="myavana-stat-card myavana-stat-info">
                            <div class="myavana-stat-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"/>
                                </svg>
                            </div>
                            <div class="myavana-stat-content">
                                <div class="myavana-stat-number"><?php echo esc_html(number_format($analytics_data['avg_health_score'], 1)); ?></div>
                                <div class="myavana-stat-label">Avg Health Score</div>
                            </div>
                        </div>

                        <div class="myavana-stat-card myavana-stat-warning">
                            <div class="myavana-stat-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>
                                </svg>
                            </div>
                            <div class="myavana-stat-content">
                                <div class="myavana-stat-number"><?php echo esc_html($analytics_data['total_photos']); ?></div>
                                <div class="myavana-stat-label">Progress Photos</div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Grid -->
                    <div class="myavana-analytics-charts-grid">
                        <div class="myavana-chart-card">
                            <div class="myavana-chart-header">
                                <h3>Health Score Trends</h3>
                            </div>
                            <div class="myavana-chart-container">
                                <canvas id="healthTrendChart"></canvas>
                            </div>
                        </div>

                        <div class="myavana-chart-card">
                            <div class="myavana-chart-header">
                                <h3>Entry Activity</h3>
                            </div>
                            <div class="myavana-chart-container">
                                <canvas id="activityChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="myavana-analytics-actions">
                        <button class="myavana-btn-primary" id="exportAnalytics">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                <polyline points="7 10 12 15 17 10"/>
                                <line x1="12" y1="15" x2="12" y2="3"/>
                            </svg>
                            Export Report
                        </button>
                    </div>
                </div>
            </div>

            <!-- Settings Tab -->
            <div class="myavana-profile-tab-content" id="settingsTabContent">
                <div class="myavana-settings-container">
                    <div class="myavana-settings-section">
                        <h3 class="myavana-settings-section-title">Privacy Settings</h3>
                        <div class="myavana-setting-item">
                            <div class="myavana-setting-info">
                                <h4>Profile Visibility</h4>
                                <p>Control who can see your profile</p>
                            </div>
                            <select class="myavana-setting-select" id="profileVisibility">
                                <option value="public">Public</option>
                                <option value="followers">Followers Only</option>
                                <option value="private">Private</option>
                            </select>
                        </div>

                        <div class="myavana-setting-item">
                            <div class="myavana-setting-info">
                                <h4>Show Activity Status</h4>
                                <p>Let others see when you're active</p>
                            </div>
                            <label class="myavana-toggle">
                                <input type="checkbox" id="showActivityStatus" checked>
                                <span class="myavana-toggle-slider"></span>
                            </label>
                        </div>
                    </div>

                    <div class="myavana-settings-section">
                        <h3 class="myavana-settings-section-title">Notification Settings</h3>
                        <div class="myavana-setting-item">
                            <div class="myavana-setting-info">
                                <h4>Email Notifications</h4>
                                <p>Receive email updates</p>
                            </div>
                            <label class="myavana-toggle">
                                <input type="checkbox" id="emailNotifications" checked>
                                <span class="myavana-toggle-slider"></span>
                            </label>
                        </div>

                        <div class="myavana-setting-item">
                            <div class="myavana-setting-info">
                                <h4>Community Updates</h4>
                                <p>Get notified about likes, comments, and follows</p>
                            </div>
                            <label class="myavana-toggle">
                                <input type="checkbox" id="communityNotifications" checked>
                                <span class="myavana-toggle-slider"></span>
                            </label>
                        </div>
                    </div>

                    <div class="myavana-settings-section">
                        <h3 class="myavana-settings-section-title">Data Management</h3>
                        <div class="myavana-setting-item">
                            <div class="myavana-setting-info">
                                <h4>Export Your Data</h4>
                                <p>Download all your hair journey data</p>
                            </div>
                            <button class="myavana-btn-secondary" onclick="exportUserData()">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                    <polyline points="7 10 12 15 17 10"/>
                                    <line x1="12" y1="15" x2="12" y2="3"/>
                                </svg>
                                Export Data
                            </button>
                        </div>
                    </div>

                    <div class="myavana-settings-actions">
                        <button class="myavana-btn-primary" onclick="saveSettings()">Save Changes</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mobile Bottom Navigation -->
        <nav class="myavana-mobile-bottom-nav">
            <button class="myavana-mobile-nav-btn active" data-tab="journey" onclick="switchProfileTab('journey')">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 2.69l5.66 5.66a8 8 0 11-11.31 0z"/>
                </svg>
                <span>Journey</span>
            </button>
            <button class="myavana-mobile-nav-btn" data-tab="community" onclick="switchProfileTab('community')">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                <span>Community</span>
            </button>
            <button class="myavana-mobile-nav-btn myavana-fab" onclick="openCreateMenu()">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
            </button>
            <button class="myavana-mobile-nav-btn" data-tab="goals" onclick="switchProfileTab('goals')">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="16"></line>
                    <line x1="8" y1="12" x2="16" y2="12"></line>
                </svg>
                <span>Goals</span>
            </button>
            <button class="myavana-mobile-nav-btn" data-tab="settings" onclick="switchProfileTab('settings')">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="3"></circle>
                    <path d="M12 1v6m0 6v6"></path>
                </svg>
                <span>Settings</span>
            </button>
        </nav>

        <!-- FAB Create Menu -->
        <div class="myavana-fab-menu" id="fabMenu" style="display: none;">
            <button class="myavana-fab-menu-item" onclick="createEntry(); closeFabMenu();">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 2.69l5.66 5.66a8 8 0 11-11.31 0z"/>
                </svg>
                <span>New Entry</span>
            </button>
            <button class="myavana-fab-menu-item" onclick="document.getElementById('myavana-create-post-btn').click(); closeFabMenu();">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path>
                </svg>
                <span>New Post</span>
            </button>
            <button class="myavana-fab-menu-item" onclick="createGoal(); closeFabMenu();">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12,2L14.39,8.26L21,9.27L16.5,13.65L17.61,20.24L12,17.27L6.39,20.24L7.5,13.65L3,9.27L9.61,8.26L12,2Z"/>
                </svg>
                <span>New Goal</span>
            </button>
            <button class="myavana-fab-menu-item" onclick="createRoutine(); closeFabMenu();">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
                <span>New Routine</span>
            </button>
        </div>

        <!-- Compare Analysis Modal -->
        <div id="compareAnalysisModal" class="modal" style="display: none;">
            <div class="modal-content" style="max-width: 1200px;">
                <div class="modal-header">
                    <h2 style="font-family: 'Archivo Black', sans-serif; color: var(--myavana-onyx); margin: 0;">Compare Hair Analyses</h2>
                    <span class="modal-close" onclick="closeCompareModal()">&times;</span>
                </div>
                <div class="modal-body">
                    <div class="compare-selection" style="margin-bottom: 2rem;">
                        <p style="color: var(--myavana-blueberry); margin-bottom: 1rem;">Select two analyses to compare:</p>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                            <div>
                                <label style="font-family: 'Archivo', sans-serif; font-weight: 600; color: var(--myavana-onyx); display: block; margin-bottom: 0.5rem;">First Analysis</label>
                                <select id="compareAnalysis1" class="compare-select" style="width: 100%; padding: 0.75rem; border: 1px solid var(--myavana-border); border-radius: 8px; font-family: 'Archivo', sans-serif;">
                                    <option value="">Select an analysis...</option>
                                </select>
                            </div>
                            <div>
                                <label style="font-family: 'Archivo', sans-serif; font-weight: 600; color: var(--myavana-onyx); display: block; margin-bottom: 0.5rem;">Second Analysis</label>
                                <select id="compareAnalysis2" class="compare-select" style="width: 100%; padding: 0.75rem; border: 1px solid var(--myavana-border); border-radius: 8px; font-family: 'Archivo', sans-serif;">
                                    <option value="">Select an analysis...</option>
                                </select>
                            </div>
                        </div>
                        <button id="startComparison" onclick="generateComparison()" style="margin-top: 1.5rem; padding: 0.75rem 2rem; background: var(--myavana-coral); color: var(--myavana-white); border: none; border-radius: 8px; font-family: 'Archivo', sans-serif; font-weight: 600; cursor: pointer; transition: all 0.3s;">
                            Compare Selected Analyses
                        </button>
                    </div>
                    <div id="comparisonResults" style="display: none;">
                        <!-- Comparison results will be populated here -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Include necessary partials -->
        <?php
        $partials_dir = dirname(dirname(__FILE__)) . '/partials';

        // Include view offcanvas for viewing details
        if (file_exists($partials_dir . '/view-offcanvas.php')) {
            include $partials_dir . '/view-offcanvas.php';
        }

        // Include create offcanvas for creating/editing
        if (file_exists($partials_dir . '/create-offcanvas.php')) {
            include $partials_dir . '/create-offcanvas.php';
        }

        // Include unified profile edit offcanvas
        $up_edit_offcanvas = dirname(__FILE__) . '/partials/unified-profile-edit-offcanvas.php';
        if (file_exists($up_edit_offcanvas)) {
            include $up_edit_offcanvas;
        }
        ?>
    </div>

    <script>
        // Profile tab switching
        function switchProfileTab(tabName) {
            // Update tab buttons
            document.querySelectorAll('.myavana-profile-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelector(`.myavana-profile-tab[data-tab="${tabName}"]`)?.classList.add('active');

            // Update mobile nav
            document.querySelectorAll('.myavana-mobile-nav-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            document.querySelector(`.myavana-mobile-nav-btn[data-tab="${tabName}"]`)?.classList.add('active');

            // Update tab content
            document.querySelectorAll('.myavana-profile-tab-content').forEach(content => {
                content.classList.remove('active');
            });
            document.getElementById(tabName + 'TabContent')?.classList.add('active');

            // Save active tab preference
            localStorage.setItem('activeProfileTab', tabName);

            // Load tab-specific content
            if (tabName === 'community') {
                loadUserCommunityPosts();
            }

            // Add active class to listView when journey tab is clicked
            if (tabName === 'journey') {
                setTimeout(function() {
                    const listView = document.getElementById('listView');
                    console.log('Journey tab clicked, listView element:', listView);
                    if (listView) {
                        listView.classList.add('active');
                        console.log('Added active class to listView');
                    } else {
                        console.warn('listView element not found in journey tab');
                    }
                }, 50);
            } else {
                const listView = document.getElementById('listView');
                if (listView) {
                    listView.classList.remove('active');
                    console.log('Removed active class from listView');
                }
            }
        }

        // FAB menu
        function openCreateMenu() {
            const fabMenu = document.getElementById('fabMenu');
            fabMenu.style.display = 'flex';
            setTimeout(() => fabMenu.classList.add('active'), 10);
        }

        function closeFabMenu() {
            const fabMenu = document.getElementById('fabMenu');
            fabMenu.classList.remove('active');
            setTimeout(() => fabMenu.style.display = 'none', 300);
        }

        // Close FAB menu when clicking outside
        document.addEventListener('click', function(e) {
            const fabMenu = document.getElementById('fabMenu');
            const fab = document.querySelector('.myavana-fab');
            if (fabMenu && !fabMenu.contains(e.target) && !fab.contains(e.target)) {
                closeFabMenu();
            }
        });

        // Load user community posts
        function loadUserCommunityPosts() {
            const userId = <?php echo $user_id; ?>;
            const grid = document.getElementById('userPostsGrid');

            jQuery.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                method: 'POST',
                data: {
                    action: 'get_user_community_posts',
                    nonce: '<?php echo wp_create_nonce('myavana_ajax_nonce'); ?>',
                    user_id: userId
                },
                success: function(response) {
                    if (response.success) {
                        const posts = response.data;
                        if (posts.length === 0) {
                            grid.innerHTML = '<div class="myavana-empty-state"><p>No community posts yet</p></div>';
                        } else {
                            grid.innerHTML = posts.map(post => `
                                <div class="myavana-user-post-card" onclick="window.location.href='/community/'">
                                    ${post.image_url ? `<img src="${post.image_url}" alt="${post.content.substring(0, 50)}" class="myavana-post-card-image">` : ''}
                                    <div class="myavana-post-card-content">
                                        <p>${post.content.substring(0, 100)}...</p>
                                        <div class="myavana-post-card-meta">
                                            <span>${post.likes_count} likes</span>
                                            <span>${post.comments_count} comments</span>
                                        </div>
                                    </div>
                                </div>
                            `).join('');
                        }
                    }
                }
            });
        }

        // Toggle post view (grid/list)
        function togglePostView(view) {
            document.querySelectorAll('.myavana-view-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            document.querySelector(`.myavana-view-btn[data-view="${view}"]`).classList.add('active');

            const grid = document.getElementById('userPostsGrid');
            if (view === 'list') {
                grid.classList.add('list-view');
            } else {
                grid.classList.remove('list-view');
            }
        }

        // Open followers/following modal
        function openFollowersModal(type) {
            // TODO: Implement followers/following modal
            console.log('Open', type, 'modal');
        }

        // Save settings
        function saveSettings() {
            const settings = {
                profileVisibility: document.getElementById('profileVisibility').value,
                showActivityStatus: document.getElementById('showActivityStatus').checked,
                emailNotifications: document.getElementById('emailNotifications').checked,
                communityNotifications: document.getElementById('communityNotifications').checked
            };

            jQuery.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                method: 'POST',
                data: {
                    action: 'save_profile_settings',
                    nonce: '<?php echo wp_create_nonce('myavana_ajax_nonce'); ?>',
                    settings: settings
                },
                success: function(response) {
                    if (response.success) {
                        alert('Settings saved successfully!');
                    }
                }
            });
        }

        // Export user data
        function exportUserData() {
            window.location.href = '<?php echo admin_url('admin-ajax.php'); ?>?action=export_user_data&nonce=<?php echo wp_create_nonce('myavana_ajax_nonce'); ?>';
        }

        // Load active tab on page load
        jQuery(document).ready(function($) {
            const activeTab = localStorage.getItem('activeProfileTab') || 'journey';
            switchProfileTab(activeTab);

            // Ensure listView has active class if journey tab is active
            if (activeTab === 'journey') {
                setTimeout(function() {
                    const listView = document.getElementById('listView');
                    if (listView) {
                        listView.classList.add('active');
                        console.log('Added active class to listView on page load');
                    } else {
                        console.warn('listView element not found');
                    }
                }, 100);
            }
        });
    </script>
    <?php
    return ob_get_clean();
}
