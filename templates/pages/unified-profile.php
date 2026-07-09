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
    $gamification_data = $shared_data['gamification'] ?? [];
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
    $ai_insight_summary = '';
    $ai_insight_recommendations = [];
    if (is_array($ai_insight)) {
        $ai_insight_summary = $ai_insight['summary'] ?? '';
        $ai_insight_recommendations = !empty($ai_insight['recommendations']) && is_array($ai_insight['recommendations'])
            ? $ai_insight['recommendations']
            : [];
    } elseif (is_string($ai_insight)) {
        $ai_insight_summary = $ai_insight;
    }

    // Fetch Community data
    global $wpdb;
    $posts_table = $wpdb->prefix . 'myavana_community_posts';
    $followers_table = $wpdb->prefix . 'myavana_user_followers';
    $likes_table = $wpdb->prefix . 'myavana_post_likes';

    // Get community stats
    $total_posts = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $posts_table WHERE user_id = %d",
            $user_id
    ));
    $total_likes = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(p.likes_count) FROM $posts_table p WHERE p.user_id = %d",
            $user_id
    )) ?: 0;
    $total_comments = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT AVG(p.likes_count + p.comments_count)
        FROM $posts_table p
        WHERE p.user_id = %d
        AND p.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
        $user_id
    )) ?: 0;

    $follower_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $followers_table WHERE following_id = %d",
        $user_id
    ));

    $following_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $followers_table WHERE follower_id = %d",
        $user_id
    ));

    $total_points = intval($gamification_data['total_points'] ?? 0);
    $current_streak = intval($gamification_data['current_streak'] ?? ($analytics_data['current_streak'] ?? 0));
    $current_level = intval($gamification_data['level'] ?? 1);
    $badges_earned = intval($gamification_data['badges_earned'] ?? 0);
    $checked_in_today = !empty($gamification_data['checked_in_today']);
    $xp_progress_percent = intval($gamification_data['xp_progress_percent'] ?? 0);
    $points_to_next_level = intval($gamification_data['points_to_next_level'] ?? 100);
    $next_badge = $gamification_data['next_badge'] ?? null;
    $daily_quests = !empty($gamification_data['daily_quests']) && is_array($gamification_data['daily_quests']) ? $gamification_data['daily_quests'] : [];
    $weekly_quests = !empty($gamification_data['weekly_quests']) && is_array($gamification_data['weekly_quests']) ? $gamification_data['weekly_quests'] : [];
    $active_challenges = !empty($gamification_data['active_challenges']) && is_array($gamification_data['active_challenges']) ? $gamification_data['active_challenges'] : [];
    $recent_rewards = !empty($gamification_data['recent_rewards']) && is_array($gamification_data['recent_rewards']) ? $gamification_data['recent_rewards'] : [];
    $recent_badges = !empty($gamification_data['recent_badges']) && is_array($gamification_data['recent_badges']) ? $gamification_data['recent_badges'] : [];
    $routine_tracking = function_exists('myavana_get_routine_tracking_context')
        ? myavana_get_routine_tracking_context($user_id, (array) $current_routine)
        : [];
    $routine_tracking_summary = !empty($routine_tracking['summary']) && is_array($routine_tracking['summary'])
        ? $routine_tracking['summary']
        : [];
    $routine_tracking_notifications = !empty($routine_tracking['notifications']) && is_array($routine_tracking['notifications'])
        ? $routine_tracking['notifications']
        : [];

    // Get additional user profile data
    $user_location = get_user_meta($user_id, 'myavana_up_location', true);
    $user_website = get_user_meta($user_id, 'myavana_up_website', true);
    $hair_concerns = get_user_meta($user_id, 'myavana_up_hair_concerns', true) ?: [];
    $hair_porosity = get_user_meta($user_id, 'hair_porosity', true);
    $hair_length = get_user_meta($user_id, 'hair_length', true);
    $structured_goals = get_user_meta($user_id, 'myavana_hair_goals_structured', true) ?: [];

    // Profile completion and suggested next actions
    $completion_checks = [
        'bio' => !empty($user_profile->bio),
        'location' => !empty($user_location),
        'hair_type' => !empty($user_profile->hair_type),
        'porosity' => !empty($hair_porosity),
        'length' => !empty($hair_length),
        'concerns' => !empty($hair_concerns) && is_array($hair_concerns),
        'goals' => !empty($structured_goals) && is_array($structured_goals),
        'routine' => !empty($current_routine),
    ];
    $missing_profile_labels = [
        'bio' => 'Bio',
        'location' => 'Location',
        'hair_type' => 'Hair Type',
        'porosity' => 'Porosity',
        'length' => 'Hair Length',
        'concerns' => 'Hair Concerns',
        'goals' => 'Hair Goals',
        'routine' => 'Routine',
    ];
    $completed_fields = count(array_filter($completion_checks));
    $profile_completion = (int) round(($completed_fields / max(1, count($completion_checks))) * 100);
    $missing_profile_fields = [];
    foreach ($completion_checks as $key => $is_complete) {
        if (!$is_complete && isset($missing_profile_labels[$key])) {
            $missing_profile_fields[] = $missing_profile_labels[$key];
        }
    }

    $next_steps = [];
    if (empty($hair_goals)) {
        $next_steps[] = 'Create your first hair goal to track outcomes over time.';
    }
    if (empty($current_routine)) {
        $next_steps[] = 'Build a routine so AI recommendations can map to your regimen.';
    }
    if (empty($snapshots) && $can_analyze) {
        $next_steps[] = 'Run your first AI analysis to unlock personalized trend insights.';
    } elseif (!empty($snapshots) && $analysis_count < $analysis_limit && $can_analyze) {
        $next_steps[] = 'Add a fresh AI analysis this week to measure progress changes.';
    }
    if ($profile_completion < 100) {
        $next_steps[] = 'Complete your profile details to improve recommendation quality.';
    }
    if (empty($next_steps)) {
        $next_steps[] = 'You are fully set up. Share your latest progress with the community.';
    }

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
                        <span><?php echo esc_html($current_streak); ?> Day Streak</span>
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
                    <button class="myavana-up-btn myavana-up-btn-secondary" onclick="createEntry()">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        New Entry
                    </button>
                    <button class="myavana-up-btn myavana-up-btn-secondary" onclick="createGoal()">
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
                        <div class="myavana-up-streak-count"><?php echo esc_html($current_streak); ?> Day Streak</div>
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
                                <div class="myavana-up-stat-number"><?php echo esc_html($current_streak); ?></div>
                                <div class="myavana-up-stat-text">Current Streak</div>
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
                <?php if (!empty($ai_insight_summary)): ?>
                <div class="myavana-up-info-card myavana-up-card-wide">
                    <div class="myavana-up-card-header">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                        </svg>
                        <h3>AI Insights</h3>
                    </div>
                    <div class="myavana-up-card-body">
                        <div class="myavana-up-ai-insight">
                            <p><?php echo esc_html($ai_insight_summary); ?></p>
                            <?php if (!empty($ai_insight_recommendations)): ?>
                            <div class="myavana-up-recommendations">
                                <h4>Recommendations:</h4>
                                <ul>
                                    <?php foreach ($ai_insight_recommendations as $recommendation): ?>
                                    <li><?php echo esc_html($recommendation); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="myavana-up-info-card">
                    <div class="myavana-up-card-header">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 12h-4l-3 9L9 3 6 12H2"></path>
                        </svg>
                        <h3>Profile Completion</h3>
                    </div>
                    <div class="myavana-up-card-body">
                        <div class="myavana-up-completion-track">
                            <div class="myavana-up-completion-fill" style="width: <?php echo esc_attr($profile_completion); ?>%;"></div>
                        </div>
                        <div class="myavana-up-completion-meta">
                            <strong><?php echo esc_html($profile_completion); ?>%</strong>
                            <span>Complete</span>
                        </div>
                        <?php if (!empty($missing_profile_fields)): ?>
                        <div class="myavana-up-missing-fields">
                            <?php foreach (array_slice($missing_profile_fields, 0, 4) as $missing_field): ?>
                            <span class="myavana-up-missing-tag"><?php echo esc_html($missing_field); ?></span>
                            <?php endforeach; ?>
                            <?php if (count($missing_profile_fields) > 4): ?>
                            <span class="myavana-up-missing-tag">+<?php echo esc_html(count($missing_profile_fields) - 4); ?> more</span>
                            <?php endif; ?>
                        </div>
                        <?php else: ?>
                        <p class="myavana-up-empty-state">Profile completed. Your personalization is fully tuned.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="myavana-up-info-card myavana-up-card-wide">
                    <div class="myavana-up-card-header">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 20V10"></path>
                            <path d="M18 20V4"></path>
                            <path d="M6 20v-6"></path>
                        </svg>
                        <h3>Action Center</h3>
                    </div>
                    <div class="myavana-up-card-body">
                        <ul class="myavana-up-action-list">
                            <?php foreach (array_slice($next_steps, 0, 3) as $next_step): ?>
                            <li><?php echo esc_html($next_step); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <div class="myavana-up-action-row">
                            <button class="myavana-btn-secondary" onclick="switchProfileTab('analytics')">Open Analytics</button>
                            <?php if ($is_owner): ?>
                            <button class="myavana-btn-primary" onclick="return goToMyavanaAiTool()">Run AI Analysis</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
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
        <div class="myavana-profile-quick-strip">
            <button class="myavana-profile-quick-chip active" data-tab="journey" onclick="switchProfileTab('journey')">Journey Overview</button>
            <button class="myavana-profile-quick-chip" data-tab="analytics" onclick="switchProfileTab('analytics')">Analytics</button>
            <button class="myavana-profile-quick-chip" data-tab="community" onclick="switchProfileTab('community')">Community Activity</button>
            <?php if ($is_owner): ?>
            <button class="myavana-profile-quick-chip" onclick="createEntry()">New Entry</button>
            <?php endif; ?>
            <button class="myavana-profile-quick-chip" onclick="copyProfileLink()">Copy Profile Link</button>
        </div>

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
                                    <path d="M12 2l2.9 6.26 6.85.69-5.05 4.5 1.42 6.55L12 16.9 5.88 20l1.42-6.55-5.05-4.5 6.85-.69L12 2z"></path>
                                </svg>
                                Journey Rewards
                            </h3>
                            <div class="myavana-ai-insights-content">
                                <div class="myavana-quick-stats">
                                    <div class="myavana-quick-stat-item">
                                        <div class="myavana-quick-stat-label">Points</div>
                                        <div class="myavana-quick-stat-value"><?php echo esc_html($total_points); ?></div>
                                    </div>
                                    <div class="myavana-quick-stat-item">
                                        <div class="myavana-quick-stat-label">Level</div>
                                        <div class="myavana-quick-stat-value">Level <?php echo esc_html($current_level); ?></div>
                                    </div>
                                    <div class="myavana-quick-stat-item">
                                        <div class="myavana-quick-stat-label">Badges</div>
                                        <div class="myavana-quick-stat-value"><?php echo esc_html($badges_earned); ?></div>
                                    </div>
                                    <div class="myavana-quick-stat-item">
                                        <div class="myavana-quick-stat-label">XP Progress</div>
                                        <div class="myavana-quick-stat-value"><?php echo esc_html($xp_progress_percent); ?>%</div>
                                    </div>
                                </div>
                                <p>
                                    <?php echo $checked_in_today ? 'Daily check-in completed today.' : 'Check in today to protect your streak and earn points.'; ?>
                                </p>
                                <?php if (!empty($next_badge['name'])): ?>
                                <p>
                                    Next badge: <strong><?php echo esc_html($next_badge['name']); ?></strong>
                                    · <?php echo esc_html(intval($next_badge['remaining'])); ?> to go
                                </p>
                                <?php else: ?>
                                <p><?php echo esc_html($points_to_next_level); ?> points to your next level.</p>
                                <?php endif; ?>
                                <?php if (!empty($recent_badges)): ?>
                                <div class="myavana-gamification-chip-list">
                                    <?php foreach (array_slice($recent_badges, 0, 3) as $badge): ?>
                                    <span class="myavana-gamification-chip"><?php echo esc_html($badge['name'] ?? 'Badge'); ?></span>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="myavana-profile-sidebar-card">
                            <h3 class="myavana-profile-sidebar-title">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--myavana-coral)" stroke-width="2">
                                    <path d="M8 2v4"></path>
                                    <path d="M16 2v4"></path>
                                    <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                                    <path d="M3 10h18"></path>
                                </svg>
                                Routine Cadence
                            </h3>
                            <div class="myavana-routine-health-grid">
                                <div class="myavana-routine-health-stat">
                                    <strong><?php echo esc_html(intval($routine_tracking_summary['due_today'] ?? 0)); ?></strong>
                                    <span>Due today</span>
                                </div>
                                <div class="myavana-routine-health-stat">
                                    <strong><?php echo esc_html(intval($routine_tracking_summary['completed_today'] ?? 0)); ?></strong>
                                    <span>Completed</span>
                                </div>
                                <div class="myavana-routine-health-stat">
                                    <strong><?php echo esc_html(intval($routine_tracking_summary['overdue'] ?? 0)); ?></strong>
                                    <span>Overdue</span>
                                </div>
                                <div class="myavana-routine-health-stat">
                                    <strong><?php echo esc_html(intval($routine_tracking_summary['adherence_30d'] ?? 0)); ?>%</strong>
                                    <span>30-day adherence</span>
                                </div>
                            </div>
                            <div class="myavana-routine-health-banner">
                                <strong><?php echo esc_html(intval($routine_tracking_summary['best_routine_streak'] ?? 0)); ?>-session best streak</strong>
                                <span><?php echo esc_html(intval($routine_tracking_summary['pending_today'] ?? 0)); ?> routine reminders still open today.</span>
                            </div>
                            <?php if (!empty($routine_tracking_notifications)): ?>
                            <div class="myavana-routine-health-stack">
                                <?php foreach (array_slice($routine_tracking_notifications, 0, 3) as $routine_notice): ?>
                                <div class="myavana-routine-health-row is-<?php echo esc_attr($routine_notice['type'] ?? 'due'); ?>">
                                    <div>
                                        <strong><?php echo esc_html($routine_notice['title'] ?? 'Routine'); ?></strong>
                                        <span><?php echo esc_html($routine_notice['message'] ?? 'Scheduled in your planner.'); ?></span>
                                    </div>
                                    <em><?php echo esc_html(!empty($routine_notice['date']) ? date_i18n('M j', strtotime($routine_notice['date'])) : 'Soon'); ?></em>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php else: ?>
                            <p>Your routine planner is clear right now. Keep logging sessions from the calendar to feed your profile stats.</p>
                            <?php endif; ?>
                            <div class="myavana-routine-health-actions">
                                <a href="<?php echo esc_url(home_url('/hair-journey/')); ?>" class="myavana-btn-primary-small">Open Calendar</a>
                                <a href="<?php echo esc_url(home_url('/routines/')); ?>" class="myavana-btn-secondary">Manage Routines</a>
                            </div>
                        </div>

                        <div class="myavana-profile-sidebar-card">
                            <h3 class="myavana-profile-sidebar-title">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--myavana-coral)" stroke-width="2">
                                    <path d="M9 11l3 3L22 4"></path>
                                    <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                                </svg>
                                Active Quests
                            </h3>
                            <div class="myavana-gamification-stack">
                                <?php foreach (array_slice($daily_quests, 0, 3) as $quest): ?>
                                <?php
                                $quest_current = intval($quest['current'] ?? 0);
                                $quest_target = max(1, intval($quest['target'] ?? 1));
                                $quest_done = $quest_current >= $quest_target;
                                $quest_pct = min(100, intval(round(($quest_current / $quest_target) * 100)));
                                ?>
                                <div class="myavana-gamification-quest<?php echo $quest_done ? ' is-complete' : ''; ?>">
                                    <div class="myavana-gamification-quest-copy">
                                        <strong><?php echo esc_html($quest['label'] ?? 'Quest'); ?></strong>
                                        <span><?php echo esc_html($quest['description'] ?? ''); ?></span>
                                    </div>
                                    <div class="myavana-gamification-quest-meta">
                                        <span><?php echo esc_html($quest_current); ?>/<?php echo esc_html($quest_target); ?></span>
                                        <div class="myavana-gamification-quest-bar"><span style="width:<?php echo esc_attr($quest_pct); ?>%"></span></div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                                <?php foreach (array_slice($weekly_quests, 0, 1) as $quest): ?>
                                <?php
                                $quest_current = intval($quest['current'] ?? 0);
                                $quest_target = max(1, intval($quest['target'] ?? 1));
                                $quest_pct = min(100, intval(round(($quest_current / $quest_target) * 100)));
                                ?>
                                <div class="myavana-gamification-quest is-weekly">
                                    <div class="myavana-gamification-quest-copy">
                                        <strong><?php echo esc_html($quest['label'] ?? 'Weekly quest'); ?></strong>
                                        <span><?php echo esc_html($quest['reward_label'] ?? ''); ?></span>
                                    </div>
                                    <div class="myavana-gamification-quest-meta">
                                        <span><?php echo esc_html($quest_current); ?>/<?php echo esc_html($quest_target); ?></span>
                                        <div class="myavana-gamification-quest-bar"><span style="width:<?php echo esc_attr($quest_pct); ?>%"></span></div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                                <?php foreach (array_slice($active_challenges, 0, 2) as $challenge): ?>
                                <div class="myavana-gamification-quest is-weekly<?php echo !empty($challenge['completed']) ? ' is-complete' : ''; ?>">
                                    <div class="myavana-gamification-quest-copy">
                                        <strong><?php echo esc_html($challenge['title'] ?? 'Challenge'); ?></strong>
                                        <span><?php echo esc_html($challenge['description'] ?? ''); ?><?php echo !empty($challenge['window_label']) ? ' · ' . esc_html($challenge['window_label']) : ''; ?></span>
                                    </div>
                                    <div class="myavana-gamification-quest-meta">
                                        <span><?php echo esc_html(intval($challenge['current'] ?? 0)); ?>/<?php echo esc_html(intval($challenge['target'] ?? 1)); ?> · +<?php echo esc_html(intval($challenge['reward_points'] ?? 0)); ?> pts</span>
                                        <div class="myavana-gamification-quest-bar"><span style="width:<?php echo esc_attr(intval($challenge['progress_percent'] ?? 0)); ?>%"></span></div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="myavana-profile-sidebar-card">
                            <h3 class="myavana-profile-sidebar-title">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--myavana-coral)" stroke-width="2">
                                    <path d="M12 8v4l3 3"></path>
                                    <circle cx="12" cy="12" r="9"></circle>
                                </svg>
                                Recent Rewards
                            </h3>
                            <div class="myavana-gamification-stack">
                                <?php if (!empty($recent_rewards)): ?>
                                    <?php foreach (array_slice($recent_rewards, 0, 4) as $reward): ?>
                                    <div class="myavana-gamification-reward-row">
                                        <div>
                                            <strong><?php echo esc_html($reward['reason'] ?? 'Reward earned'); ?></strong>
                                            <span><?php echo esc_html($reward['relative_time'] ?? ''); ?></span>
                                        </div>
                                        <em>+<?php echo esc_html(intval($reward['points_change'] ?? 0)); ?></em>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                <p>No rewards yet. Complete a quest or add a new journey update.</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="myavana-profile-sidebar-card">
                            <h3 class="myavana-profile-sidebar-title">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--myavana-coral)" stroke-width="2">
                                    <path d="M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M12,4A8,8 0 0,1 20,12A8,8 0 0,1 12,20A8,8 0 0,1 4,12A8,8 0 0,1 12,4Z"/>
                                </svg>
                                AI Insights
                            </h3>
                            <div class="myavana-ai-insights-content">
                                <p><?php echo esc_html($ai_insight_summary ?: 'Capture a new AI analysis to unlock guided recommendations.'); ?></p>
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
                                    <div class="myavana-quick-stat-value"><?php echo esc_html($current_streak); ?> days</div>
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
                                <div class="myavana-community-stat-value" id="totalLikesReceived"><?php echo esc_html($total_likes); ?></div>
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
                                <!-- lets write total comments as int -->
                                <div class="myavana-community-stat-value" id="totalCommentsReceived"><?php echo esc_html($total_comments); ?></div>
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

                                                            <div class="analysis-meta-tags">
                                                                <?php if (!empty($snapshot['hair_analysis']['porosity'])): ?>
                                                                    <span class="analysis-meta-tag">Porosity: <?php echo esc_html($snapshot['hair_analysis']['porosity']); ?></span>
                                                                <?php endif; ?>
                                                                <?php if (!empty($snapshot['hair_analysis']['texture'])): ?>
                                                                    <span class="analysis-meta-tag">Texture: <?php echo esc_html($snapshot['hair_analysis']['texture']); ?></span>
                                                                <?php endif; ?>
                                                                <?php if (!empty($snapshot['hair_analysis']['density'])): ?>
                                                                    <span class="analysis-meta-tag">Density: <?php echo esc_html($snapshot['hair_analysis']['density']); ?></span>
                                                                <?php endif; ?>
                                                                <?php if (!empty($snapshot['hair_analysis']['length'])): ?>
                                                                    <span class="analysis-meta-tag">Length: <?php echo esc_html($snapshot['hair_analysis']['length']); ?></span>
                                                                <?php endif; ?>
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
                                                                    'full_context' => $snapshot['full_context'] ?? '',
                                                                    'environment' => $snapshot['environment'] ?? '',
                                                                    'user_description' => $snapshot['user_description'] ?? '',
                                                                    'mood_demeanor' => $snapshot['mood_demeanor'] ?? '',
                                                                    'confidence_level' => $snapshot['confidence_level'] ?? 0,
                                                                    'hair_analysis' => [
                                                                        'health_score' => $snapshot['hair_analysis']['health_score'] ?? 0,
                                                                        'hydration' => $snapshot['hair_analysis']['hydration'] ?? 0,
                                                                        'elasticity' => $snapshot['hair_analysis']['elasticity'] ?? 0,
                                                                        'type' => $snapshot['hair_analysis']['type'] ?? '--',
                                                                        'curl_pattern' => $snapshot['hair_analysis']['curl_pattern'] ?? '--',
                                                                        'porosity' => $snapshot['hair_analysis']['porosity'] ?? '--',
                                                                        'length' => $snapshot['hair_analysis']['length'] ?? '--',
                                                                        'texture' => $snapshot['hair_analysis']['texture'] ?? '--',
                                                                        'density' => $snapshot['hair_analysis']['density'] ?? '--',
                                                                        'hairstyle' => $snapshot['hair_analysis']['hairstyle'] ?? '--',
                                                                        'damage' => $snapshot['hair_analysis']['damage'] ?? '--',
                                                                        'scalp_health' => $snapshot['hair_analysis']['scalp_health'] ?? '--',
                                                                        'hair_color' => $snapshot['hair_analysis']['hair_color'] ?? '--',
                                                                        'strand_thickness' => $snapshot['hair_analysis']['strand_thickness'] ?? '--',
                                                                        'growth_pattern' => $snapshot['hair_analysis']['growth_pattern'] ?? '--'
                                                                    ],
                                                                    'recommendations' => $snapshot['recommendations'] ?? [],
                                                                    'products' => $snapshot['products'] ?? [],
                                                                    'recommendations_priority' => $snapshot['recommendations_priority'] ?? []
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
                                            <?php $history_summary = $analysis['summary'] ?? ($analysis['full_analysis']['summary'] ?? ''); ?>
                                            <div class="myavana-history-card">
                                                <div class="myavana-history-header">
                                                    <h4 class="o-6"><?php echo esc_html(date('M j, Y', strtotime($analysis['date']))); ?></h4>
                                                    <div class="myavana-history-score">
                                                        <?php echo esc_html($analysis['full_analysis']['hair_analysis']['health_score'] ?? '--'); ?>%
                                                        <span>Health</span>
                                                    </div>
                                                </div>

                                                <div class="myavana-history-preview">
                                                    <p><?php echo esc_html(wp_trim_words($history_summary, 15)); ?></p>
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
                                                    'summary' => $history_summary,
                                                    'full_context' => $analysis['full_analysis']['full_context'] ?? '',
                                                    'environment' => $analysis['full_analysis']['environment'] ?? '',
                                                    'user_description' => $analysis['full_analysis']['user_description'] ?? '',
                                                    'mood_demeanor' => $analysis['full_analysis']['mood_demeanor'] ?? '',
                                                    'confidence_level' => $analysis['full_analysis']['confidence_level'] ?? 0,
                                                    'hair_analysis' => [
                                                        'health_score' => $analysis['full_analysis']['hair_analysis']['health_score'] ?? 0,
                                                        'hydration' => $analysis['full_analysis']['hair_analysis']['hydration'] ?? 0,
                                                        'elasticity' => $analysis['full_analysis']['hair_analysis']['elasticity'] ?? 0,
                                                        'type' => $analysis['full_analysis']['hair_analysis']['type'] ?? '--',
                                                        'curl_pattern' => $analysis['full_analysis']['hair_analysis']['curl_pattern'] ?? '--',
                                                        'porosity' => $analysis['full_analysis']['hair_analysis']['porosity'] ?? '--',
                                                        'length' => $analysis['full_analysis']['hair_analysis']['length'] ?? '--',
                                                        'texture' => $analysis['full_analysis']['hair_analysis']['texture'] ?? '--',
                                                        'density' => $analysis['full_analysis']['hair_analysis']['density'] ?? '--',
                                                        'hairstyle' => $analysis['full_analysis']['hair_analysis']['hairstyle'] ?? '--',
                                                        'damage' => $analysis['full_analysis']['hair_analysis']['damage'] ?? '--',
                                                        'scalp_health' => $analysis['full_analysis']['hair_analysis']['scalp_health'] ?? '--',
                                                        'hair_color' => $analysis['full_analysis']['hair_analysis']['hair_color'] ?? '--',
                                                        'strand_thickness' => $analysis['full_analysis']['hair_analysis']['strand_thickness'] ?? '--',
                                                        'growth_pattern' => $analysis['full_analysis']['hair_analysis']['growth_pattern'] ?? '--'
                                                    ],
                                                    'recommendations' => $analysis['full_analysis']['recommendations'] ?? [],
                                                    'products' => $analysis['full_analysis']['products'] ?? [],
                                                    'recommendations_priority' => $analysis['full_analysis']['recommendations_priority'] ?? []
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
                                    $goal_description = $goal['description'] ?? $goal['notes'] ?? '';
                                    $milestones = $goal['milestones'] ?? [];
                                    $goal_id = $goal['id'] ?? $goal['goal_id'] ?? $idx;
                                    $goal_progress_history = $goal['progress_history'] ?? [];
                                    $goal_progress_notes = $goal['progress_text'] ?? [];

                                    $completed_milestones = 0;
                                    foreach ($milestones as $milestone) {
                                        if (isset($milestone['achieved']) && $milestone['achieved']) {
                                            $completed_milestones++;
                                        }
                                    }
                                ?>
                                <div
                                    class="myavana-goal-card"
                                    data-goal-index="<?php echo esc_attr($idx); ?>"
                                    data-goal-id="<?php echo esc_attr($goal_id); ?>"
                                    data-goal-description="<?php echo esc_attr($goal_description); ?>"
                                    data-goal-milestones="<?php echo esc_attr(wp_json_encode($milestones)); ?>"
                                    data-goal-progress-history="<?php echo esc_attr(wp_json_encode($goal_progress_history)); ?>"
                                    data-goal-progress-notes="<?php echo esc_attr(wp_json_encode($goal_progress_notes)); ?>"
                                    onclick="openViewOffcanvas('goal', <?php echo esc_js($idx); ?>)">
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
                                    $routine_id = $step['id'] ?? $step['routine_id'] ?? $r_idx;
                                    $routine_steps = $step['steps'] ?? [];
                                    $routine_products = $step['products'] ?? [];
                                ?>
                                <div
                                    class="myavana-routine-card"
                                    data-routine-index="<?php echo esc_attr($r_idx); ?>"
                                    data-routine-id="<?php echo esc_attr($routine_id); ?>"
                                    data-routine-frequency="<?php echo esc_attr($step['frequency'] ?? ''); ?>"
                                    data-routine-time="<?php echo esc_attr($step['time'] ?? $step['routine_time'] ?? ''); ?>"
                                    data-routine-duration="<?php echo esc_attr($step['duration'] ?? $step['routine_duration'] ?? ''); ?>"
                                    data-routine-description="<?php echo esc_attr($description); ?>"
                                    data-routine-steps="<?php echo esc_attr(wp_json_encode($routine_steps)); ?>"
                                    data-routine-products="<?php echo esc_attr(wp_json_encode($routine_products)); ?>"
                                    onclick="openViewOffcanvas('routine', <?php echo esc_js($r_idx); ?>)">
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
                                <div class="myavana-stat-number"><?php echo esc_html($current_streak); ?></div>
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
        if (!isset($partials_dir) || !is_dir($partials_dir)) {
            $partials_dir = __DIR__ . '/partials';
        }

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
        const MYAVANA_AI_TOOL_URL = 'https://www.myavana.com/pages/consumer';

        function goToMyavanaAiTool() {
            window.myavanaAiToolUrl = window.myavanaAiToolUrl || MYAVANA_AI_TOOL_URL;
            window.location.href = window.myavanaAiToolUrl;
            return false;
        }

        // Force override on profile page so stale cached handlers cannot open legacy AI modals.
        window.myavanaAiToolUrl = window.myavanaAiToolUrl || MYAVANA_AI_TOOL_URL;
        window.openAIAnalysisModal = goToMyavanaAiTool;

        // Intercept legacy analysis triggers early (capture phase).
        document.addEventListener('click', function(event) {
            const trigger = event.target.closest('#addAnalysisBtn, #start-first-analysis, button.section-edit[data-section="analysis"]');
            if (!trigger) {
                return;
            }

            event.preventDefault();
            event.stopImmediatePropagation();
            window.location.href = window.myavanaAiToolUrl || MYAVANA_AI_TOOL_URL;
        }, true);

        // Profile tab switching
        function switchProfileTab(tabName) {
            if (tabName === 'ai-analysis') {
                goToMyavanaAiTool();
                return;
            }

            // Update tab buttons
            document.querySelectorAll('.myavana-profile-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelector(`.myavana-profile-tab[data-tab="${tabName}"]`)?.classList.add('active');
            document.querySelectorAll('.myavana-profile-quick-chip[data-tab]').forEach(btn => {
                btn.classList.remove('active');
            });
            document.querySelector(`.myavana-profile-quick-chip[data-tab="${tabName}"]`)?.classList.add('active');

            // Update tab content
            document.querySelectorAll('.myavana-profile-tab-content').forEach(content => {
                content.classList.remove('active');
            });
            document.getElementById(tabName + 'TabContent')?.classList.add('active');

            // Save active tab preference
            localStorage.setItem('myavanaActiveProfileTab', tabName);
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

        function copyProfileLink() {
            const profileUrl = window.location.href.split('#')[0];
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(profileUrl).then(() => {
                    alert('Profile link copied.');
                }).catch(() => {
                    alert('Unable to copy profile link.');
                });
                return;
            }

            const tempInput = document.createElement('input');
            tempInput.value = profileUrl;
            document.body.appendChild(tempInput);
            tempInput.select();
            document.execCommand('copy');
            document.body.removeChild(tempInput);
            alert('Profile link copied.');
        }

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
            let activeTab = localStorage.getItem('myavanaActiveProfileTab') || localStorage.getItem('activeProfileTab') || 'journey';
            if (activeTab === 'ai-analysis') {
                activeTab = 'journey';
                localStorage.setItem('myavanaActiveProfileTab', 'journey');
                localStorage.setItem('activeProfileTab', 'journey');
            }
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
