<?php
/**
 * User Profile Page Template
 * Displays public user profiles with privacy controls
 *
 * MYAVANA Brand Standards Applied
 */

function myavana_user_profile_shortcode($atts = []) {
    // Parse attributes
    $atts = shortcode_atts([
        'user_id' => get_current_user_id(), // Default to current user
    ], $atts, 'myavana_user_profile');

    $profile_user_id = intval($atts['user_id']);
    $current_user_id = get_current_user_id();

    // Get profile user data
    $profile_user = get_userdata($profile_user_id);
    if (!$profile_user) {
        return '<div class="myavana-profile-error"><p>User not found.</p></div>';
    }

    $is_own_profile = ($profile_user_id === $current_user_id);

    // Check privacy permissions
    if (!$is_own_profile) {
        $can_view = Myavana_Profile_Privacy::can_view_profile($profile_user_id, $current_user_id);
        if (!$can_view) {
            return '<div class="myavana-profile-private">
                        <div class="myavana-profile-private-icon">
                            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="var(--myavana-coral)" stroke-width="2">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                            </svg>
                        </div>
                        <h2 class="myavana-subheader">This Profile is Private</h2>
                        <p class="myavana-body">' . esc_html($profile_user->display_name) . ' has chosen to keep their profile private.</p>
                    </div>';
        }
    }

    // Get user data
    $privacy_settings = Myavana_Profile_Privacy::get_privacy_settings($profile_user_id);
    $shared_data = Myavana_Data_Manager::get_journey_data($profile_user_id);
    $social_stats = Myavana_Social_Features::get_user_social_stats($profile_user_id);

    // Check if current user follows this profile
    $is_following = false;
    if (!$is_own_profile && $current_user_id) {
        global $wpdb;
        $followers_table = $wpdb->prefix . 'myavana_user_followers';
        $is_following = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $followers_table WHERE follower_id = %d AND following_id = %d",
            $current_user_id, $profile_user_id
        )) > 0;
    }

    ob_start();
    ?>

    <div class="myavana-user-profile-container" data-user-id="<?php echo esc_attr($profile_user_id); ?>">

        <!-- Profile Header -->
        <div class="myavana-profile-header">
            <div class="myavana-profile-cover"></div>
            <div class="myavana-profile-header-content">
                <div class="myavana-profile-avatar-section">
                    <div class="myavana-profile-avatar-large">
                        <?php echo get_avatar($profile_user_id, 150); ?>
                    </div>
                </div>

                <div class="myavana-profile-info-section">
                    <h1 class="myavana-profile-display-name"><?php echo esc_html($profile_user->display_name); ?></h1>
                    <p class="myavana-profile-username">@<?php echo esc_html($profile_user->user_login); ?></p>

                    <?php if ($shared_data['about_me']): ?>
                        <p class="myavana-profile-bio"><?php echo esc_html($shared_data['about_me']); ?></p>
                    <?php endif; ?>

                    <!-- Social Stats -->
                    <div class="myavana-profile-stats">
                        <div class="myavana-profile-stat">
                            <span class="myavana-stat-number"><?php echo esc_html($social_stats['posts_count'] ?? 0); ?></span>
                            <span class="myavana-stat-label">Posts</span>
                        </div>
                        <div class="myavana-profile-stat">
                            <span class="myavana-stat-number"><?php echo esc_html($social_stats['followers_count'] ?? 0); ?></span>
                            <span class="myavana-stat-label">Followers</span>
                        </div>
                        <div class="myavana-profile-stat">
                            <span class="myavana-stat-number"><?php echo esc_html($social_stats['following_count'] ?? 0); ?></span>
                            <span class="myavana-stat-label">Following</span>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="myavana-profile-actions">
                        <?php if ($is_own_profile): ?>
                            <button class="myavana-btn-secondary" id="myavana-edit-profile-btn">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                </svg>
                                Edit Profile
                            </button>
                            <button class="myavana-btn-secondary" id="myavana-privacy-settings-btn">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="3"></circle>
                                    <path d="M12 1v6m0 6v6m5.657-12.657l-4.243 4.243m-2.828 2.828l-4.243 4.243m12.728 0l-4.243-4.243m-2.828-2.828L1.343 5.657"></path>
                                </svg>
                                Privacy Settings
                            </button>
                        <?php else: ?>
                            <button class="myavana-btn-primary myavana-follow-btn <?php echo $is_following ? 'following' : ''; ?>"
                                    data-user-id="<?php echo esc_attr($profile_user_id); ?>">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="8.5" cy="7" r="4"></circle>
                                    <line x1="20" y1="8" x2="20" y2="14"></line>
                                    <line x1="23" y1="11" x2="17" y2="11"></line>
                                </svg>
                                <span class="follow-text"><?php echo $is_following ? 'Following' : 'Follow'; ?></span>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Profile Content Tabs -->
        <div class="myavana-profile-tabs">
            <button class="myavana-profile-tab active" data-tab="posts">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="7" height="7"></rect>
                    <rect x="14" y="3" width="7" height="7"></rect>
                    <rect x="14" y="14" width="7" height="7"></rect>
                    <rect x="3" y="14" width="7" height="7"></rect>
                </svg>
                Posts
            </button>
            <?php if ($privacy_settings['show_hair_stats']): ?>
            <button class="myavana-profile-tab" data-tab="hair-stats">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="20" x2="18" y2="10"></line>
                    <line x1="12" y1="20" x2="12" y2="4"></line>
                    <line x1="6" y1="20" x2="6" y2="14"></line>
                </svg>
                Hair Stats
            </button>
            <?php endif; ?>
            <?php if ($privacy_settings['show_goals'] && !empty($shared_data['hair_goals'])): ?>
            <button class="myavana-profile-tab" data-tab="goals">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                </svg>
                Goals
            </button>
            <?php endif; ?>
        </div>

        <!-- Tab Content -->
        <div class="myavana-profile-content">

            <!-- Posts Tab -->
            <div class="myavana-profile-tab-content active" id="posts-tab">
                <div class="myavana-feed-grid" id="profile-posts-grid">
                    <!-- Posts will be loaded via AJAX -->
                </div>
                <div class="myavana-feed-loading" id="profile-posts-loading">
                    <div class="myavana-loader-spinner"></div>
                    <p class="myavana-body">Loading posts...</p>
                </div>
                <div class="myavana-feed-empty" id="profile-posts-empty" style="display: none;">
                    <p class="myavana-body">No posts yet</p>
                </div>
            </div>

            <!-- Hair Stats Tab -->
            <?php if ($privacy_settings['show_hair_stats']): ?>
            <div class="myavana-profile-tab-content" id="hair-stats-tab">
                <div class="myavana-stats-grid">
                    <div class="myavana-stat-card">
                        <h3 class="myavana-preheader">JOURNEY STATS</h3>
                        <div class="myavana-stat-row">
                            <span class="myavana-body">Days Active:</span>
                            <strong><?php echo esc_html($shared_data['stats']['days_active']); ?></strong>
                        </div>
                        <div class="myavana-stat-row">
                            <span class="myavana-body">Current Streak:</span>
                            <strong><?php echo esc_html($shared_data['analytics']['current_streak']); ?> days</strong>
                        </div>
                        <div class="myavana-stat-row">
                            <span class="myavana-body">Avg Health Score:</span>
                            <strong><?php echo esc_html(number_format($shared_data['analytics']['avg_health_score'], 1)); ?>/10</strong>
                        </div>
                    </div>

                    <div class="myavana-stat-card">
                        <h3 class="myavana-preheader">HAIR PROFILE</h3>
                        <div class="myavana-stat-row">
                            <span class="myavana-body">Hair Type:</span>
                            <strong><?php echo esc_html($shared_data['profile']->hair_type ?: '--'); ?></strong>
                        </div>
                        <div class="myavana-stat-row">
                            <span class="myavana-body">Porosity:</span>
                            <strong><?php echo esc_html($shared_data['hair_porosity']); ?></strong>
                        </div>
                        <div class="myavana-stat-row">
                            <span class="myavana-body">Length:</span>
                            <strong><?php echo esc_html($shared_data['hair_length']); ?></strong>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Goals Tab -->
            <?php if ($privacy_settings['show_goals'] && !empty($shared_data['hair_goals'])): ?>
            <div class="myavana-profile-tab-content" id="goals-tab">
                <div class="myavana-goals-list">
                    <?php foreach ($shared_data['hair_goals'] as $goal): ?>
                        <div class="myavana-goal-card">
                            <h4 class="myavana-subheader"><?php echo esc_html($goal['title']); ?></h4>
                            <?php if (!empty($goal['description'])): ?>
                                <p class="myavana-body"><?php echo esc_html($goal['description']); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>

    <!-- Privacy Settings Modal (Only for own profile) -->
    <?php if ($is_own_profile): ?>
    <div class="myavana-modal" id="myavana-privacy-settings-modal">
        <div class="myavana-modal-overlay"></div>
        <div class="myavana-modal-content">
            <div class="myavana-modal-header">
                <h2 class="myavana-subheader">Privacy Settings</h2>
                <button class="myavana-modal-close" id="myavana-close-privacy-modal">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            <div class="myavana-modal-body">
                <form id="myavana-privacy-settings-form">

                    <div class="myavana-form-group">
                        <label class="myavana-form-label">Profile Visibility</label>
                        <div class="myavana-radio-group">
                            <label class="myavana-radio-label">
                                <input type="radio" name="profile_visibility" value="private"
                                       <?php checked($privacy_settings['profile_visibility'], 'private'); ?>>
                                <span class="myavana-radio-custom"></span>
                                <div class="myavana-radio-text">
                                    <strong class="myavana-body">Private</strong>
                                    <p class="myavana-body" style="opacity: 0.7; font-size: 12px;">Only you can see your profile</p>
                                </div>
                            </label>
                            <label class="myavana-radio-label">
                                <input type="radio" name="profile_visibility" value="public"
                                       <?php checked($privacy_settings['profile_visibility'], 'public'); ?>>
                                <span class="myavana-radio-custom"></span>
                                <div class="myavana-radio-text">
                                    <strong class="myavana-body">Public</strong>
                                    <p class="myavana-body" style="opacity: 0.7; font-size: 12px;">Everyone can see your profile</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="myavana-form-group">
                        <label class="myavana-form-label">What to Show on Public Profile</label>
                        <div class="myavana-checkbox-group">
                            <label class="myavana-checkbox-label">
                                <input type="checkbox" name="show_hair_stats" value="1"
                                       <?php checked($privacy_settings['show_hair_stats'], 1); ?>>
                                <span class="myavana-checkbox-custom"></span>
                                <span class="myavana-body">Hair Stats & Journey Progress</span>
                            </label>
                            <label class="myavana-checkbox-label">
                                <input type="checkbox" name="show_goals" value="1"
                                       <?php checked($privacy_settings['show_goals'], 1); ?>>
                                <span class="myavana-checkbox-custom"></span>
                                <span class="myavana-body">Hair Goals</span>
                            </label>
                            <label class="myavana-checkbox-label">
                                <input type="checkbox" name="show_routine" value="1"
                                       <?php checked($privacy_settings['show_routine'], 1); ?>>
                                <span class="myavana-checkbox-custom"></span>
                                <span class="myavana-body">Hair Care Routine</span>
                            </label>
                        </div>
                    </div>

                    <div class="myavana-modal-footer">
                        <button type="button" class="myavana-btn-secondary" id="myavana-cancel-privacy">
                            Cancel
                        </button>
                        <button type="submit" class="myavana-btn-primary">
                            Save Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script>
        // Pass data to JavaScript
        window.myavanaProfileSettings = {
            ajaxUrl: '<?php echo admin_url('admin-ajax.php'); ?>',
            nonce: '<?php echo wp_create_nonce('myavana_nonce'); ?>',
            profileUserId: <?php echo $profile_user_id; ?>,
            currentUserId: <?php echo $current_user_id; ?>,
            isOwnProfile: <?php echo $is_own_profile ? 'true' : 'false'; ?>
        };
    </script>

    <?php
    return ob_get_clean();
}
