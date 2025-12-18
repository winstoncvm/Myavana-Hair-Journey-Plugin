<?php
/**
 * Community Shortcode Functions
 *
 * @package Myavana_Hair_Journey
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * User Profile Shortcode
 * [myavana_user_profile user_id="123"]
 */
function myavana_user_profile_shortcode($atts) {
    $atts = shortcode_atts([
        'user_id' => get_current_user_id()
    ], $atts);

    $user_id = intval($atts['user_id']);

    if (!$user_id) {
        return '<p>Invalid user ID</p>';
    }

    $user = get_userdata($user_id);
    if (!$user) {
        return '<p>User not found</p>';
    }

    // Get community stats
    $stats = Myavana_Community_Integration::get_community_stats($user_id);

    // Get user's recent posts
    global $wpdb;
    $posts_table = $wpdb->prefix . 'myavana_community_posts';
    $recent_posts = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $posts_table WHERE user_id = %d AND privacy_level = 'public' ORDER BY created_at DESC LIMIT 12",
        $user_id
    ));

    ob_start();
    ?>
    <div class="myavana-user-profile">
        <div class="profile-header">
            <div class="profile-avatar">
                <?php echo get_avatar($user_id, 120); ?>
            </div>
            <div class="profile-info">
                <h2><?php echo esc_html($user->display_name); ?></h2>
                <div class="profile-stats">
                    <div class="stat">
                        <strong><?php echo $stats['posts_count']; ?></strong>
                        <span>Posts</span>
                    </div>
                    <div class="stat">
                        <strong><?php echo $stats['followers']; ?></strong>
                        <span>Followers</span>
                    </div>
                    <div class="stat">
                        <strong><?php echo $stats['following']; ?></strong>
                        <span>Following</span>
                    </div>
                    <div class="stat">
                        <strong><?php echo $stats['community_points']; ?></strong>
                        <span>Points</span>
                    </div>
                </div>
                <?php if ($user_id != get_current_user_id()): ?>
                    <button class="myavana-btn follow-user-btn" data-user-id="<?php echo $user_id; ?>">
                        Follow
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <div class="profile-content">
            <h3>Recent Posts</h3>
            <div class="myavana-feed-grid">
                <?php if (!empty($recent_posts)): ?>
                    <?php foreach ($recent_posts as $post): ?>
                        <div class="feed-card">
                            <?php if (!empty($post->image_url)): ?>
                                <img src="<?php echo esc_url($post->image_url); ?>" alt="<?php echo esc_attr($post->title); ?>">
                            <?php endif; ?>
                            <div class="card-content">
                                <h4><?php echo esc_html($post->title); ?></h4>
                                <p><?php echo esc_html(wp_trim_words($post->content, 20)); ?></p>
                                <div class="card-meta">
                                    <span><i class="icon-heart"></i> <?php echo $post->likes_count; ?></span>
                                    <span><i class="icon-comment"></i> <?php echo $post->comments_count; ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No posts yet</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Community Challenges Shortcode
 * [myavana_challenges]
 */
function myavana_challenges_shortcode($atts) {
    if (!is_user_logged_in()) {
        return '<p>Please log in to view challenges</p>';
    }

    global $wpdb;
    $challenges_table = $wpdb->prefix . 'myavana_community_challenges';

    // Get active challenges
    $challenges = $wpdb->get_results("
        SELECT * FROM $challenges_table
        WHERE is_active = 1 AND end_date > NOW()
        ORDER BY start_date DESC
    ");

    $user_id = get_current_user_id();

    ob_start();
    ?>
    <div class="myavana-challenges">
        <div class="challenges-header">
            <h2>Community Challenges</h2>
            <button class="myavana-btn create-challenge-btn">Create Challenge</button>
        </div>

        <div class="challenges-grid">
            <?php if (!empty($challenges)): ?>
                <?php foreach ($challenges as $challenge): ?>
                    <?php
                    // Check if user has joined
                    $participants_table = $wpdb->prefix . 'myavana_challenge_participants';
                    $is_joined = $wpdb->get_var($wpdb->prepare(
                        "SELECT id FROM $participants_table WHERE challenge_id = %d AND user_id = %d",
                        $challenge->id, $user_id
                    ));

                    $days_left = ceil((strtotime($challenge->end_date) - time()) / (60 * 60 * 24));
                    ?>
                    <div class="challenge-card">
                        <div class="challenge-badge">
                            <?php echo esc_html($challenge->challenge_type); ?>
                        </div>
                        <h3><?php echo esc_html($challenge->title); ?></h3>
                        <p><?php echo esc_html($challenge->description); ?></p>

                        <div class="challenge-meta">
                            <div class="meta-item">
                                <strong><?php echo $challenge->participants_count; ?></strong>
                                <span>Participants</span>
                            </div>
                            <div class="meta-item">
                                <strong><?php echo $days_left; ?></strong>
                                <span>Days Left</span>
                            </div>
                        </div>

                        <?php if (!empty($challenge->hashtag)): ?>
                            <div class="challenge-hashtag"><?php echo esc_html($challenge->hashtag); ?></div>
                        <?php endif; ?>

                        <button class="myavana-btn <?php echo $is_joined ? 'joined' : ''; ?> join-challenge-btn"
                                data-challenge-id="<?php echo $challenge->id; ?>"
                                <?php echo $is_joined ? 'disabled' : ''; ?>>
                            <?php echo $is_joined ? 'Joined' : 'Join Challenge'; ?>
                        </button>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No active challenges at the moment. Check back soon!</p>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Trending Posts Shortcode
 * [myavana_trending_posts limit="10"]
 */
function myavana_trending_posts_shortcode($atts) {
    $atts = shortcode_atts([
        'limit' => 10
    ], $atts);

    global $wpdb;
    $posts_table = $wpdb->prefix . 'myavana_community_posts';

    $trending_posts = $wpdb->get_results($wpdb->prepare("
        SELECT p.*, u.display_name,
               (p.likes_count + p.comments_count + p.shares_count) as engagement_score
        FROM $posts_table p
        LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
        WHERE p.privacy_level = 'public'
            AND p.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ORDER BY engagement_score DESC, p.created_at DESC
        LIMIT %d
    ", $atts['limit']));

    ob_start();
    ?>
    <div class="myavana-trending-posts">
        <h3>Trending This Week</h3>
        <div class="myavana-feed-grid">
            <?php if (!empty($trending_posts)): ?>
                <?php foreach ($trending_posts as $post): ?>
                    <div class="feed-card trending">
                        <div class="trending-badge">🔥 Trending</div>
                        <?php if (!empty($post->image_url)): ?>
                            <img src="<?php echo esc_url($post->image_url); ?>" alt="<?php echo esc_attr($post->title); ?>">
                        <?php endif; ?>
                        <div class="card-content">
                            <div class="card-author">
                                <?php echo get_avatar($post->user_id, 32); ?>
                                <span><?php echo esc_html($post->display_name); ?></span>
                            </div>
                            <h4><?php echo esc_html($post->title); ?></h4>
                            <p><?php echo esc_html(wp_trim_words($post->content, 20)); ?></p>
                            <div class="card-meta">
                                <span><i class="icon-heart"></i> <?php echo $post->likes_count; ?></span>
                                <span><i class="icon-comment"></i> <?php echo $post->comments_count; ?></span>
                                <span><i class="icon-fire"></i> <?php echo $post->engagement_score; ?> engagement</span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No trending posts this week</p>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Routine Library Shortcode
 * [myavana_routine_library]
 */
function myavana_routine_library_shortcode($atts) {
    global $wpdb;
    $routines_table = $wpdb->prefix . 'myavana_shared_routines';

    // Get all public routines
    $routines = $wpdb->get_results("
        SELECT r.*, u.display_name
        FROM $routines_table r
        LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID
        WHERE r.privacy_level = 'public'
        ORDER BY r.effectiveness_score DESC, r.times_tried DESC, r.created_at DESC
        LIMIT 50
    ");

    ob_start();
    ?>
    <div class="myavana-routine-library">
        <div class="library-header">
            <h2>Community Routine Library</h2>
            <button class="myavana-btn share-routine-btn">Share Your Routine</button>
        </div>

        <div class="library-filters">
            <select id="hair-type-filter">
                <option value="">All Hair Types</option>
                <option value="4C">4C</option>
                <option value="4B">4B</option>
                <option value="4A">4A</option>
                <option value="3C">3C</option>
                <option value="3B">3B</option>
                <option value="3A">3A</option>
            </select>
            <select id="goal-filter">
                <option value="">All Goals</option>
                <option value="growth">Growth</option>
                <option value="moisture">Moisture</option>
                <option value="strength">Strength</option>
                <option value="definition">Definition</option>
            </select>
        </div>

        <div class="routines-grid">
            <?php if (!empty($routines)): ?>
                <?php foreach ($routines as $routine): ?>
                    <div class="routine-card" data-hair-type="<?php echo esc_attr($routine->hair_type); ?>" data-goal="<?php echo esc_attr($routine->goal_type); ?>">
                        <div class="routine-header">
                            <h3><?php echo esc_html($routine->title); ?></h3>
                            <div class="routine-author">
                                By <?php echo esc_html($routine->display_name); ?>
                            </div>
                        </div>

                        <p><?php echo esc_html(wp_trim_words($routine->description, 30)); ?></p>

                        <div class="routine-tags">
                            <?php if ($routine->hair_type): ?>
                                <span class="tag"><?php echo esc_html($routine->hair_type); ?></span>
                            <?php endif; ?>
                            <?php if ($routine->goal_type): ?>
                                <span class="tag"><?php echo esc_html($routine->goal_type); ?></span>
                            <?php endif; ?>
                            <span class="tag"><?php echo esc_html($routine->frequency); ?></span>
                        </div>

                        <div class="routine-stats">
                            <div class="stat">
                                <strong><?php echo number_format($routine->effectiveness_score, 1); ?></strong>
                                <span>Effectiveness</span>
                            </div>
                            <div class="stat">
                                <strong><?php echo $routine->times_tried; ?></strong>
                                <span>People Tried</span>
                            </div>
                            <div class="stat">
                                <strong><?php echo $routine->likes_count; ?></strong>
                                <span>Likes</span>
                            </div>
                        </div>

                        <div class="routine-actions">
                            <button class="myavana-btn-secondary view-routine-btn" data-routine-id="<?php echo $routine->id; ?>">
                                View Details
                            </button>
                            <button class="myavana-btn bookmark-routine-btn" data-routine-id="<?php echo $routine->id; ?>">
                                Bookmark
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No routines shared yet. Be the first to share yours!</p>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Community Stats Dashboard Shortcode
 * [myavana_community_stats]
 */
function myavana_community_stats_shortcode($atts) {
    if (!is_user_logged_in()) {
        return '<p>Please log in to view your community stats</p>';
    }

    $user_id = get_current_user_id();
    $stats = Myavana_Community_Integration::get_community_stats($user_id);

    ob_start();
    ?>
    <div class="myavana-community-stats-dashboard">
        <h2>Your Community Impact</h2>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📝</div>
                <div class="stat-value"><?php echo $stats['posts_count']; ?></div>
                <div class="stat-label">Posts Shared</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">❤️</div>
                <div class="stat-value"><?php echo $stats['total_likes']; ?></div>
                <div class="stat-label">Likes Received</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">💬</div>
                <div class="stat-value"><?php echo $stats['total_comments']; ?></div>
                <div class="stat-label">Comments Received</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-value"><?php echo $stats['followers']; ?></div>
                <div class="stat-label">Followers</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">📊</div>
                <div class="stat-value"><?php echo $stats['engagement_rate']; ?></div>
                <div class="stat-label">Engagement Rate</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">🏆</div>
                <div class="stat-value"><?php echo $stats['challenges_completed']; ?></div>
                <div class="stat-label">Challenges Completed</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">⭐</div>
                <div class="stat-value"><?php echo $stats['community_points']; ?></div>
                <div class="stat-label">Community Points</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">🎯</div>
                <div class="stat-value">Level <?php echo $stats['community_level']; ?></div>
                <div class="stat-label">Community Level</div>
            </div>
        </div>

        <div class="stats-insights">
            <h3>Your Community Journey</h3>
            <div class="insight-card">
                <p>You've shared <strong><?php echo $stats['shared_entries']; ?></strong> hair journey entries with the community!</p>
            </div>
            <?php if ($stats['engagement_rate'] > 10): ?>
                <div class="insight-card success">
                    <p>🎉 Great job! Your posts have a <strong><?php echo $stats['engagement_rate']; ?></strong> average engagement rate!</p>
                </div>
            <?php endif; ?>
            <?php if ($stats['challenges_joined'] > 0): ?>
                <div class="insight-card">
                    <p>You're participating in <strong><?php echo $stats['challenges_joined']; ?></strong> community challenge(s).</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
