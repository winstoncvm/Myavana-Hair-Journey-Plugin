<?php
/**
 * Community Integration Helper Class
 * Connects hair journey entries, goals, routines with community features
 *
 * @package Myavana_Hair_Journey
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Myavana_Community_Integration {

    /**
     * Check if an entry can be shared to community
     */
    public static function is_entry_shareable($entry_id) {
        global $wpdb;

        // Check if entry exists
        $table = $wpdb->prefix . 'myavana_hair_diary_entries';
        $entry = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $entry_id
        ));

        if (!$entry) {
            return false;
        }

        // Check if already shared
        $shared_table = $wpdb->prefix . 'myavana_shared_entries';
        $already_shared = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $shared_table WHERE entry_id = %d",
            $entry_id
        ));

        return !$already_shared;
    }

    /**
     * Share entry to community
     */
    public static function share_entry($entry_id, $privacy = 'public') {
        global $wpdb;

        // Get entry data
        $table = $wpdb->prefix . 'myavana_hair_diary_entries';
        $entry = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $entry_id
        ));

        if (!$entry) {
            return new WP_Error('invalid_entry', 'Entry not found');
        }

        // Get user ID
        $user_id = get_current_user_id();

        // Prepare post content
        $title = !empty($entry->title) ? $entry->title : 'My Hair Journey Update';
        $content = !empty($entry->notes) ? $entry->notes : '';

        // Determine post type based on entry data
        $post_type = self::determine_post_type($entry);

        // Extract hashtags from content
        $hashtags = self::extract_hashtags($content);

        // Get AI metadata if available
        $ai_metadata = null;
        if (!empty($entry->ai_analysis)) {
            $ai_metadata = json_encode([
                'health_rating' => $entry->health_rating ?? null,
                'analysis' => $entry->ai_analysis ?? null,
                'recommendations' => $entry->ai_recommendations ?? null
            ]);
        }

        // Get image URL
        $image_url = '';
        if (!empty($entry->photo_url)) {
            $image_url = $entry->photo_url;
        }

        // Create community post
        $posts_table = $wpdb->prefix . 'myavana_community_posts';
        $result = $wpdb->insert(
            $posts_table,
            [
                'user_id' => $user_id,
                'title' => $title,
                'content' => $content,
                'image_url' => $image_url,
                'post_type' => $post_type,
                'privacy_level' => $privacy,
                'source_entry_id' => $entry_id,
                'ai_metadata' => $ai_metadata,
                'hashtags' => $hashtags,
                'created_at' => current_time('mysql')
            ],
            ['%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s']
        );

        if (!$result) {
            return new WP_Error('share_failed', 'Failed to create community post');
        }

        $post_id = $wpdb->insert_id;

        // Track shared entry
        $shared_table = $wpdb->prefix . 'myavana_shared_entries';
        $wpdb->insert(
            $shared_table,
            [
                'entry_id' => $entry_id,
                'community_post_id' => $post_id,
                'user_id' => $user_id,
                'shared_at' => current_time('mysql')
            ],
            ['%d', '%d', '%d', '%s']
        );

        // Award points for sharing
        self::award_community_points($user_id, 'share_entry');

        // Create notification for followers
        self::notify_followers($user_id, $post_id, 'new_post');

        return $post_id;
    }

    /**
     * Determine post type from entry data
     */
    private static function determine_post_type($entry) {
        // Check for transformation indicators
        if (!empty($entry->before_photo) && !empty($entry->after_photo)) {
            return 'transformation';
        }

        // Check for routine/products
        if (!empty($entry->products_used) || !empty($entry->routine_details)) {
            return 'routine';
        }

        // Check for product review
        if (!empty($entry->product_review)) {
            return 'products';
        }

        // Default to progress
        return 'progress';
    }

    /**
     * Extract hashtags from content
     */
    public static function extract_hashtags($content) {
        if (empty($content)) {
            return '';
        }

        // Match hashtags
        preg_match_all('/#(\w+)/', $content, $matches);

        if (empty($matches[0])) {
            return '';
        }

        // Return comma-separated hashtags
        return implode(',', array_unique($matches[0]));
    }

    /**
     * Award community points for actions
     */
    public static function award_community_points($user_id, $action) {
        // Point values for different actions
        $point_values = [
            'share_entry' => 15,
            'first_like' => 5,
            'first_comment' => 10,
            'complete_challenge' => 100,
            'help_someone' => 3,
            'weekly_top_contributor' => 200,
            'create_post' => 10
        ];

        $points = isset($point_values[$action]) ? $point_values[$action] : 0;

        if ($points > 0) {
            global $wpdb;
            $table = $wpdb->prefix . 'myavana_user_stats';

            // Update user points
            $wpdb->query($wpdb->prepare(
                "UPDATE $table SET total_points = total_points + %d WHERE user_id = %d",
                $points, $user_id
            ));

            // If no row was updated, insert new record
            if ($wpdb->rows_affected === 0) {
                $wpdb->insert(
                    $table,
                    [
                        'user_id' => $user_id,
                        'total_points' => $points,
                        'level' => 1
                    ],
                    ['%d', '%d', '%d']
                );
            }

            // Check for badge eligibility
            self::check_badge_eligibility($user_id);
        }

        return $points;
    }

    /**
     * Check and award badges based on activity
     */
    private static function check_badge_eligibility($user_id) {
        global $wpdb;

        // Get user's community activity stats
        $posts_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}myavana_community_posts WHERE user_id = %d",
            $user_id
        ));

        $shared_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}myavana_shared_entries WHERE user_id = %d",
            $user_id
        ));

        // Community starter badge
        if ($posts_count >= 1) {
            self::award_badge($user_id, 'community_starter');
        }

        // Community champion badge
        if ($posts_count >= 50) {
            self::award_badge($user_id, 'community_champion');
        }
    }

    /**
     * Award a badge to user
     */
    private static function award_badge($user_id, $badge_key) {
        global $wpdb;

        $badges_table = $wpdb->prefix . 'myavana_badges';
        $user_badges_table = $wpdb->prefix . 'myavana_user_badges';

        // Get badge info
        $badge = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $badges_table WHERE badge_key = %s",
            $badge_key
        ));

        if (!$badge) {
            return false;
        }

        // Check if user already has this badge
        $has_badge = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $user_badges_table WHERE user_id = %d AND badge_id = %d",
            $user_id, $badge->id
        ));

        if ($has_badge) {
            return false;
        }

        // Award badge
        $result = $wpdb->insert(
            $user_badges_table,
            [
                'user_id' => $user_id,
                'badge_id' => $badge->id,
                'earned_at' => current_time('mysql'),
                'notified' => 0
            ],
            ['%d', '%d', '%s', '%d']
        );

        if ($result) {
            // Award badge points
            self::award_community_points($user_id, 'badge_earned');

            // Create notification
            self::create_notification($user_id, 'badge_earned', [
                'badge_name' => $badge->name,
                'badge_description' => $badge->description
            ]);
        }

        return $result;
    }

    /**
     * Get recommended posts for user
     */
    public static function get_recommended_posts($user_id, $limit = 10) {
        global $wpdb;

        // Get user's hair profile
        $profile_table = $wpdb->prefix . 'myavana_profiles';
        $profile = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $profile_table WHERE user_id = %d",
            $user_id
        ));

        $hair_type = $profile->hair_type ?? null;

        // Get user's goals
        $goals = get_user_meta($user_id, 'myavana_hair_goals_structured', true);

        $posts_table = $wpdb->prefix . 'myavana_community_posts';

        // Build recommendation query
        $sql = "SELECT p.*, u.display_name
                FROM $posts_table p
                LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
                WHERE p.privacy_level = 'public'
                AND p.user_id != %d";

        // Add hair type matching if available
        if ($hair_type) {
            $sql .= $wpdb->prepare(" AND p.ai_metadata LIKE %s", '%' . $wpdb->esc_like($hair_type) . '%');
        }

        $sql .= " ORDER BY (p.likes_count + p.comments_count) DESC, p.created_at DESC LIMIT %d";

        $posts = $wpdb->get_results($wpdb->prepare($sql, $user_id, $limit));

        return $posts;
    }

    /**
     * Create notification for user
     */
    public static function create_notification($user_id, $type, $data = []) {
        global $wpdb;

        $notification_templates = [
            'new_like' => [
                'title' => 'New Like',
                'message' => '{user} liked your post',
            ],
            'new_comment' => [
                'title' => 'New Comment',
                'message' => '{user} commented on your post',
            ],
            'new_follower' => [
                'title' => 'New Follower',
                'message' => '{user} started following you',
            ],
            'badge_earned' => [
                'title' => 'Badge Earned!',
                'message' => 'You earned the {badge_name} badge!',
            ],
            'challenge_milestone' => [
                'title' => 'Challenge Progress',
                'message' => 'You reached {milestone}% in {challenge_name}',
            ],
            'new_post' => [
                'title' => 'New Post',
                'message' => '{user} shared a new post',
            ]
        ];

        if (!isset($notification_templates[$type])) {
            return false;
        }

        $template = $notification_templates[$type];
        $message = $template['message'];

        // Replace placeholders
        foreach ($data as $key => $value) {
            $message = str_replace('{' . $key . '}', $value, $message);
        }

        $table = $wpdb->prefix . 'myavana_notifications';
        return $wpdb->insert(
            $table,
            [
                'user_id' => $user_id,
                'type' => $type,
                'title' => $template['title'],
                'message' => $message,
                'action_url' => $data['action_url'] ?? '',
                'related_user_id' => $data['related_user_id'] ?? null,
                'related_post_id' => $data['related_post_id'] ?? null,
                'is_read' => 0,
                'created_at' => current_time('mysql')
            ],
            ['%d', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%s']
        );
    }

    /**
     * Notify followers about new post
     */
    private static function notify_followers($user_id, $post_id, $type = 'new_post') {
        global $wpdb;

        // Get user's followers
        $followers_table = $wpdb->prefix . 'myavana_user_followers';
        $followers = $wpdb->get_col($wpdb->prepare(
            "SELECT follower_id FROM $followers_table WHERE following_id = %d",
            $user_id
        ));

        if (empty($followers)) {
            return;
        }

        $user = get_userdata($user_id);
        $display_name = $user->display_name;

        // Create notification for each follower
        foreach ($followers as $follower_id) {
            self::create_notification($follower_id, $type, [
                'user' => $display_name,
                'related_user_id' => $user_id,
                'related_post_id' => $post_id,
                'action_url' => '#post-' . $post_id
            ]);
        }
    }

    /**
     * Convert goal to community challenge
     */
    public static function create_challenge_from_goal($goal_data, $user_id) {
        global $wpdb;

        $table = $wpdb->prefix . 'myavana_community_challenges';

        // Generate hashtag from goal title
        $hashtag = '#' . str_replace(' ', '', ucwords($goal_data['title']));

        $result = $wpdb->insert(
            $table,
            [
                'title' => $goal_data['title'],
                'description' => $goal_data['description'] ?? '',
                'challenge_type' => $goal_data['goal_type'] ?? 'general',
                'start_date' => $goal_data['start_date'] ?? current_time('mysql'),
                'end_date' => $goal_data['target_date'] ?? date('Y-m-d H:i:s', strtotime('+30 days')),
                'participants_count' => 1,
                'hashtag' => $hashtag,
                'is_active' => 1,
                'created_at' => current_time('mysql')
            ],
            ['%s', '%s', '%s', '%s', '%s', '%d', '%s', '%d', '%s']
        );

        if ($result) {
            $challenge_id = $wpdb->insert_id;

            // Auto-join creator to challenge
            $participants_table = $wpdb->prefix . 'myavana_challenge_participants';
            $wpdb->insert(
                $participants_table,
                [
                    'challenge_id' => $challenge_id,
                    'user_id' => $user_id,
                    'completion_status' => 'active',
                    'joined_at' => current_time('mysql')
                ],
                ['%d', '%d', '%s', '%s']
            );

            return $challenge_id;
        }

        return false;
    }

    /**
     * Share routine to community library
     */
    public static function share_routine($routine_data, $user_id) {
        global $wpdb;

        $table = $wpdb->prefix . 'myavana_shared_routines';

        // Get user's hair type
        $profile = $wpdb->get_row($wpdb->prepare(
            "SELECT hair_type FROM {$wpdb->prefix}myavana_profiles WHERE user_id = %d",
            $user_id
        ));

        $result = $wpdb->insert(
            $table,
            [
                'user_id' => $user_id,
                'title' => $routine_data['title'],
                'description' => $routine_data['description'] ?? '',
                'routine_data' => json_encode($routine_data),
                'hair_type' => $profile->hair_type ?? 'unknown',
                'goal_type' => $routine_data['goal_type'] ?? null,
                'products_used' => $routine_data['products'] ?? '',
                'frequency' => $routine_data['frequency'] ?? 'daily',
                'privacy_level' => $routine_data['privacy'] ?? 'public',
                'created_at' => current_time('mysql')
            ],
            ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
        );

        if ($result) {
            self::award_community_points($user_id, 'share_routine');
            return $wpdb->insert_id;
        }

        return false;
    }

    /**
     * Get user's community stats
     */
    public static function get_community_stats($user_id) {
        global $wpdb;

        $stats = [];

        // Posts count
        $stats['posts_count'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}myavana_community_posts WHERE user_id = %d",
            $user_id
        ));

        // Shared entries count
        $stats['shared_entries'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}myavana_shared_entries WHERE user_id = %d",
            $user_id
        ));

        // Followers
        $stats['followers'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}myavana_user_followers WHERE following_id = %d",
            $user_id
        ));

        // Following
        $stats['following'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}myavana_user_followers WHERE follower_id = %d",
            $user_id
        ));

        // Total likes received
        $stats['total_likes'] = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(p.likes_count)
             FROM {$wpdb->prefix}myavana_community_posts p
             WHERE p.user_id = %d",
            $user_id
        )) ?: 0;

        // Total comments received
        $stats['total_comments'] = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(p.comments_count)
             FROM {$wpdb->prefix}myavana_community_posts p
             WHERE p.user_id = %d",
            $user_id
        )) ?: 0;

        // Engagement rate
        if ($stats['posts_count'] > 0) {
            $stats['engagement_rate'] = round(
                ($stats['total_likes'] + $stats['total_comments']) / $stats['posts_count'],
                2
            );
        } else {
            $stats['engagement_rate'] = 0;
        }

        // Challenges joined
        $stats['challenges_joined'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}myavana_challenge_participants WHERE user_id = %d",
            $user_id
        ));

        // Challenges completed
        $stats['challenges_completed'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}myavana_challenge_participants
             WHERE user_id = %d AND completion_status = 'completed'",
            $user_id
        ));

        // Community points
        $stats['community_points'] = $wpdb->get_var($wpdb->prepare(
            "SELECT total_points FROM {$wpdb->prefix}myavana_user_stats WHERE user_id = %d",
            $user_id
        )) ?: 0;

        // Community level
        $stats['community_level'] = $wpdb->get_var($wpdb->prepare(
            "SELECT level FROM {$wpdb->prefix}myavana_user_stats WHERE user_id = %d",
            $user_id
        )) ?: 1;

        return $stats;
    }
}
