<?php
/**
 * Social Features and Community Integration
 * 
 * This class provides social networking features for the hair journey community:
 * - User profiles and following system
 * - Community posts and sharing
 * - Comments and reactions
 * - Hair journey sharing and inspiration
 * - Community challenges and groups
 */

if (!defined('ABSPATH')) {
    exit;
}

class Myavana_Social_Features {
    
    private $user_id;
    
    public function __construct() {
        $this->user_id = get_current_user_id();
        $this->init();
    }
    
    private function init() {
        // AJAX handlers
        add_action('wp_ajax_get_community_feed', array($this, 'get_community_feed'));
        add_action('wp_ajax_nopriv_get_community_feed', array($this, 'get_community_feed'));
        add_action('wp_ajax_create_community_post', array($this, 'create_community_post'));
        add_action('wp_ajax_like_post', array($this, 'like_post'));
        add_action('wp_ajax_comment_on_post', array($this, 'comment_on_post'));
        add_action('wp_ajax_follow_user', array($this, 'follow_user'));
        add_action('wp_ajax_join_challenge', array($this, 'join_challenge'));
        add_action('wp_ajax_get_user_followers', array($this, 'get_user_followers'));
        add_action('wp_ajax_get_trending_posts', array($this, 'get_trending_posts'));
        
        // Database setup
        add_action('init', array($this, 'create_social_tables'));
    }
    
    /**
     * Create necessary database tables for social features
     */
    public function create_social_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Community posts table
        $posts_table = $wpdb->prefix . 'myavana_community_posts';
        $posts_sql = "CREATE TABLE $posts_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            title varchar(255) NOT NULL,
            content longtext NOT NULL,
            image_url varchar(500),
            post_type varchar(50) DEFAULT 'general',
            privacy_level varchar(20) DEFAULT 'public',
            likes_count int(11) DEFAULT 0,
            comments_count int(11) DEFAULT 0,
            shares_count int(11) DEFAULT 0,
            is_featured tinyint(1) DEFAULT 0,
            source_entry_id bigint(20) DEFAULT NULL,
            ai_metadata longtext DEFAULT NULL,
            hashtags varchar(500) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY post_type (post_type),
            KEY created_at (created_at),
            KEY source_entry_id (source_entry_id)
        ) $charset_collate;";
        
        // Post likes table
        $likes_table = $wpdb->prefix . 'myavana_post_likes';
        $likes_sql = "CREATE TABLE $likes_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id mediumint(9) NOT NULL,
            user_id bigint(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY post_user (post_id, user_id),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        // Post comments table
        $comments_table = $wpdb->prefix . 'myavana_post_comments';
        $comments_sql = "CREATE TABLE $comments_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id mediumint(9) NOT NULL,
            user_id bigint(20) NOT NULL,
            parent_id mediumint(9) DEFAULT 0,
            content text NOT NULL,
            likes_count int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY user_id (user_id),
            KEY parent_id (parent_id)
        ) $charset_collate;";
        
        // User followers table
        $followers_table = $wpdb->prefix . 'myavana_user_followers';
        $followers_sql = "CREATE TABLE $followers_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            follower_id bigint(20) NOT NULL,
            following_id bigint(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY follow_relationship (follower_id, following_id),
            KEY follower_id (follower_id),
            KEY following_id (following_id)
        ) $charset_collate;";
        
        // Community challenges table
        $challenges_table = $wpdb->prefix . 'myavana_community_challenges';
        $challenges_sql = "CREATE TABLE $challenges_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            description text NOT NULL,
            challenge_type varchar(50) NOT NULL,
            start_date datetime NOT NULL,
            end_date datetime NOT NULL,
            participants_count int(11) DEFAULT 0,
            prize_description text,
            rules longtext,
            hashtag varchar(100),
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY challenge_type (challenge_type),
            KEY start_date (start_date),
            KEY is_active (is_active)
        ) $charset_collate;";
        
        // Challenge participants table
        $challenge_participants_table = $wpdb->prefix . 'myavana_challenge_participants';
        $challenge_participants_sql = "CREATE TABLE $challenge_participants_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            challenge_id mediumint(9) NOT NULL,
            user_id bigint(20) NOT NULL,
            progress_data longtext,
            completion_status varchar(20) DEFAULT 'active',
            joined_at datetime DEFAULT CURRENT_TIMESTAMP,
            completed_at datetime NULL,
            PRIMARY KEY (id),
            UNIQUE KEY challenge_user (challenge_id, user_id),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        // Shared entries tracking table
        $shared_entries_table = $wpdb->prefix . 'myavana_shared_entries';
        $shared_entries_sql = "CREATE TABLE $shared_entries_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            entry_id bigint(20) NOT NULL,
            community_post_id mediumint(9) NOT NULL,
            user_id bigint(20) NOT NULL,
            shared_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY entry_id (entry_id),
            KEY user_id (user_id),
            KEY community_post_id (community_post_id)
        ) $charset_collate;";

        // Routine library table
        $routines_table = $wpdb->prefix . 'myavana_shared_routines';
        $routines_sql = "CREATE TABLE $routines_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            title varchar(255) NOT NULL,
            description longtext NOT NULL,
            routine_data longtext NOT NULL,
            hair_type varchar(50),
            goal_type varchar(100),
            products_used text,
            frequency varchar(50),
            effectiveness_score decimal(3,2) DEFAULT 0.00,
            times_tried int(11) DEFAULT 0,
            likes_count int(11) DEFAULT 0,
            privacy_level varchar(20) DEFAULT 'public',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY hair_type (hair_type),
            KEY goal_type (goal_type),
            KEY effectiveness_score (effectiveness_score)
        ) $charset_collate;";

        // Routine bookmarks table
        $routine_bookmarks_table = $wpdb->prefix . 'myavana_routine_bookmarks';
        $routine_bookmarks_sql = "CREATE TABLE $routine_bookmarks_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            routine_id mediumint(9) NOT NULL,
            user_id bigint(20) NOT NULL,
            tried tinyint(1) DEFAULT 0,
            effectiveness_rating int(11) DEFAULT NULL,
            notes text,
            bookmarked_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY routine_user (routine_id, user_id),
            KEY user_id (user_id)
        ) $charset_collate;";

        // Notifications table
        $notifications_table = $wpdb->prefix . 'myavana_notifications';
        $notifications_sql = "CREATE TABLE $notifications_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            type varchar(50) NOT NULL,
            title varchar(255) NOT NULL,
            message text NOT NULL,
            action_url varchar(500),
            related_user_id bigint(20) DEFAULT NULL,
            related_post_id mediumint(9) DEFAULT NULL,
            is_read tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY is_read (is_read),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        dbDelta($posts_sql);
        dbDelta($likes_sql);
        dbDelta($comments_sql);
        dbDelta($followers_sql);
        dbDelta($challenges_sql);
        dbDelta($challenge_participants_sql);
        dbDelta($shared_entries_sql);
        dbDelta($routines_sql);
        dbDelta($routine_bookmarks_sql);
        dbDelta($notifications_sql);
    }
    
    /**
     * Get community feed for the user
     */
    public function get_community_feed() {
        if (!wp_verify_nonce($_POST['nonce'], 'myavana_nonce')) {
            wp_die('Security check failed');
        }
        
        $page = intval($_POST['page'] ?? 1);
        $per_page = intval($_POST['per_page'] ?? 10);
        $filter = sanitize_text_field($_POST['filter'] ?? 'all');
        
        global $wpdb;
        
        $posts_table = $wpdb->prefix . 'myavana_community_posts';
        $users_table = $wpdb->users;
        $followers_table = $wpdb->prefix . 'myavana_user_followers';
        
        $offset = ($page - 1) * $per_page;
        
        // Build query based on filter
        $where_clause = "WHERE p.privacy_level = 'public'";
        
        switch ($filter) {
            case 'following':
                $where_clause = "WHERE p.privacy_level = 'public' AND p.user_id IN (
                    SELECT following_id FROM $followers_table WHERE follower_id = %d
                )";
                break;
            case 'trending':
                $where_clause = "WHERE p.privacy_level = 'public' AND p.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                break;
            case 'featured':
                $where_clause = "WHERE p.privacy_level = 'public' AND p.is_featured = 1";
                break;
        }
        
        $sql = "
            SELECT p.*, u.display_name, u.user_email,
                   (SELECT COUNT(*) FROM {$wpdb->prefix}myavana_post_likes WHERE post_id = p.id) as likes_count,
                   (SELECT COUNT(*) FROM {$wpdb->prefix}myavana_post_comments WHERE post_id = p.id) as comments_count
            FROM $posts_table p
            LEFT JOIN $users_table u ON p.user_id = u.ID
            $where_clause
            ORDER BY p.created_at DESC
            LIMIT %d OFFSET %d
        ";
        
        if ($filter === 'following') {
            $posts = $wpdb->get_results($wpdb->prepare($sql, $this->user_id, $per_page, $offset));
        } else {
            $posts = $wpdb->get_results($wpdb->prepare($sql, $per_page, $offset));
        }
        
        // Enhance posts with additional data
        foreach ($posts as &$post) {
            $post->user_avatar = get_avatar_url($post->user_id);
            $post->user_profile_url = '#'; // Could be customized
            $post->is_liked = $this->is_post_liked($post->id, $this->user_id);
            $post->recent_comments = $this->get_recent_comments($post->id, 3);
            $post->formatted_date = human_time_diff(strtotime($post->created_at)) . ' ago';
        }
        
        wp_send_json_success($posts);
    }
    
    /**
     * Create a new community post
     */
    public function create_community_post() {
        if (!wp_verify_nonce($_POST['nonce'], 'myavana_nonce')) {
            wp_die('Security check failed');
        }
        
        $title = sanitize_text_field($_POST['title']);
        $content = sanitize_textarea_field($_POST['content']);
        $post_type = sanitize_text_field($_POST['post_type'] ?? 'general');
        $privacy_level = sanitize_text_field($_POST['privacy_level'] ?? 'public');
        
        // Handle image upload
        $image_url = '';
        if (!empty($_FILES['image'])) {
            $upload_result = $this->handle_image_upload($_FILES['image']);
            if ($upload_result['success']) {
                $image_url = $upload_result['url'];
            }
        }
        
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'myavana_community_posts';
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'user_id' => $this->user_id,
                'title' => $title,
                'content' => $content,
                'image_url' => $image_url,
                'post_type' => $post_type,
                'privacy_level' => $privacy_level,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        if ($result) {
            $post_id = $wpdb->insert_id;

            // Award points for creating post
            Myavana_Community_Integration::award_community_points($this->user_id, 'create_post');

            // Get the created post data
            $post = $wpdb->get_row($wpdb->prepare(
                "SELECT p.*, u.display_name FROM $table_name p
                 LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
                 WHERE p.id = %d",
                $post_id
            ));

            wp_send_json_success(array(
                'message' => 'Post created successfully',
                'post' => $post
            ));
        } else {
            wp_send_json_error('Failed to create post');
        }
    }
    
    /**
     * Like/unlike a post
     */
    public function like_post() {
        if (!wp_verify_nonce($_POST['nonce'], 'myavana_nonce')) {
            wp_die('Security check failed');
        }
        
        $post_id = intval($_POST['post_id']);
        
        global $wpdb;
        
        $likes_table = $wpdb->prefix . 'myavana_post_likes';
        $posts_table = $wpdb->prefix . 'myavana_community_posts';
        
        // Check if already liked
        $existing_like = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $likes_table WHERE post_id = %d AND user_id = %d",
            $post_id, $this->user_id
        ));
        
        if ($existing_like) {
            // Unlike the post
            $wpdb->delete(
                $likes_table,
                array('post_id' => $post_id, 'user_id' => $this->user_id),
                array('%d', '%d')
            );
            
            // Decrease likes count
            $wpdb->query($wpdb->prepare(
                "UPDATE $posts_table SET likes_count = likes_count - 1 WHERE id = %d",
                $post_id
            ));
            
            $action = 'unliked';
        } else {
            // Like the post
            $wpdb->insert(
                $likes_table,
                array(
                    'post_id' => $post_id,
                    'user_id' => $this->user_id,
                    'created_at' => current_time('mysql')
                ),
                array('%d', '%d', '%s')
            );
            
            // Increase likes count
            $wpdb->query($wpdb->prepare(
                "UPDATE $posts_table SET likes_count = likes_count + 1 WHERE id = %d",
                $post_id
            ));

            $action = 'liked';

            // Get post owner
            $post_owner = $wpdb->get_var($wpdb->prepare(
                "SELECT user_id FROM $posts_table WHERE id = %d",
                $post_id
            ));

            // Award points to post owner for first like
            $like_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $likes_table WHERE post_id = %d",
                $post_id
            ));

            if ($like_count == 1 && $post_owner) {
                Myavana_Community_Integration::award_community_points($post_owner, 'first_like');
            }

            // Create notification for post owner
            if ($post_owner && $post_owner != $this->user_id) {
                Myavana_Community_Integration::create_notification($post_owner, 'new_like', [
                    'user' => wp_get_current_user()->display_name,
                    'related_user_id' => $this->user_id,
                    'related_post_id' => $post_id,
                    'action_url' => '#post-' . $post_id
                ]);
            }
        }

        // Get updated likes count
        $likes_count = $wpdb->get_var($wpdb->prepare(
            "SELECT likes_count FROM $posts_table WHERE id = %d",
            $post_id
        ));

        wp_send_json_success(array(
            'action' => $action,
            'likes_count' => $likes_count
        ));
    }
    
    /**
     * Comment on a post
     */
    public function comment_on_post() {
        if (!wp_verify_nonce($_POST['nonce'], 'myavana_nonce')) {
            wp_die('Security check failed');
        }
        
        $post_id = intval($_POST['post_id']);
        $content = sanitize_textarea_field($_POST['content']);
        $parent_id = intval($_POST['parent_id'] ?? 0);
        
        global $wpdb;
        
        $comments_table = $wpdb->prefix . 'myavana_post_comments';
        $posts_table = $wpdb->prefix . 'myavana_community_posts';
        
        $result = $wpdb->insert(
            $comments_table,
            array(
                'post_id' => $post_id,
                'user_id' => $this->user_id,
                'parent_id' => $parent_id,
                'content' => $content,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%d', '%d', '%s', '%s')
        );
        
        if ($result) {
            // Update comments count
            $wpdb->query($wpdb->prepare(
                "UPDATE $posts_table SET comments_count = comments_count + 1 WHERE id = %d",
                $post_id
            ));

            $comment_id = $wpdb->insert_id;

            // Get post owner
            $post_owner = $wpdb->get_var($wpdb->prepare(
                "SELECT user_id FROM $posts_table WHERE id = %d",
                $post_id
            ));

            // Award points for helping someone (commenting)
            Myavana_Community_Integration::award_community_points($this->user_id, 'help_someone');

            // Award points to post owner for first comment
            $comment_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $comments_table WHERE post_id = %d",
                $post_id
            ));

            if ($comment_count == 1 && $post_owner) {
                Myavana_Community_Integration::award_community_points($post_owner, 'first_comment');
            }

            // Create notification for post owner
            if ($post_owner && $post_owner != $this->user_id) {
                Myavana_Community_Integration::create_notification($post_owner, 'new_comment', [
                    'user' => wp_get_current_user()->display_name,
                    'related_user_id' => $this->user_id,
                    'related_post_id' => $post_id,
                    'action_url' => '#post-' . $post_id
                ]);
            }

            // Get the created comment with user data
            $comment = $wpdb->get_row($wpdb->prepare(
                "SELECT c.*, u.display_name FROM $comments_table c
                 LEFT JOIN {$wpdb->users} u ON c.user_id = u.ID
                 WHERE c.id = %d",
                $comment_id
            ));

            $comment->user_avatar = get_avatar_url($comment->user_id);
            $comment->formatted_date = human_time_diff(strtotime($comment->created_at)) . ' ago';

            wp_send_json_success(array(
                'message' => 'Comment added successfully',
                'comment' => $comment
            ));
        } else {
            wp_send_json_error('Failed to add comment');
        }
    }
    
    /**
     * Follow/unfollow a user
     */
    public function follow_user() {
        if (!wp_verify_nonce($_POST['nonce'], 'myavana_nonce')) {
            wp_die('Security check failed');
        }
        
        $user_to_follow = intval($_POST['user_id']);
        
        if ($user_to_follow === $this->user_id) {
            wp_send_json_error('Cannot follow yourself');
            return;
        }
        
        global $wpdb;
        
        $followers_table = $wpdb->prefix . 'myavana_user_followers';
        
        // Check if already following
        $existing_follow = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $followers_table WHERE follower_id = %d AND following_id = %d",
            $this->user_id, $user_to_follow
        ));
        
        if ($existing_follow) {
            // Unfollow
            $wpdb->delete(
                $followers_table,
                array('follower_id' => $this->user_id, 'following_id' => $user_to_follow),
                array('%d', '%d')
            );
            
            $action = 'unfollowed';
        } else {
            // Follow
            $wpdb->insert(
                $followers_table,
                array(
                    'follower_id' => $this->user_id,
                    'following_id' => $user_to_follow,
                    'created_at' => current_time('mysql')
                ),
                array('%d', '%d', '%s')
            );
            
            $action = 'followed';
        }
        
        // Get updated follower counts
        $follower_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $followers_table WHERE following_id = %d",
            $user_to_follow
        ));
        
        $following_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $followers_table WHERE follower_id = %d",
            $user_to_follow
        ));
        
        wp_send_json_success(array(
            'action' => $action,
            'follower_count' => $follower_count,
            'following_count' => $following_count
        ));
    }
    
    /**
     * Join a community challenge
     */
    public function join_challenge() {
        if (!wp_verify_nonce($_POST['nonce'], 'myavana_nonce')) {
            wp_die('Security check failed');
        }
        
        $challenge_id = intval($_POST['challenge_id']);
        
        global $wpdb;
        
        $participants_table = $wpdb->prefix . 'myavana_challenge_participants';
        $challenges_table = $wpdb->prefix . 'myavana_community_challenges';
        
        // Check if already joined
        $existing_participation = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $participants_table WHERE challenge_id = %d AND user_id = %d",
            $challenge_id, $this->user_id
        ));
        
        if ($existing_participation) {
            wp_send_json_error('Already joined this challenge');
            return;
        }
        
        // Join the challenge
        $result = $wpdb->insert(
            $participants_table,
            array(
                'challenge_id' => $challenge_id,
                'user_id' => $this->user_id,
                'completion_status' => 'active',
                'joined_at' => current_time('mysql')
            ),
            array('%d', '%d', '%s', '%s')
        );
        
        if ($result) {
            // Update participants count
            $wpdb->query($wpdb->prepare(
                "UPDATE $challenges_table SET participants_count = participants_count + 1 WHERE id = %d",
                $challenge_id
            ));
            
            wp_send_json_success('Successfully joined the challenge!');
        } else {
            wp_send_json_error('Failed to join challenge');
        }
    }
    
    /**
     * Get trending posts
     */
    public function get_trending_posts() {
        if (!wp_verify_nonce($_POST['nonce'], 'myavana_nonce')) {
            wp_die('Security check failed');
        }
        
        global $wpdb;
        
        $posts_table = $wpdb->prefix . 'myavana_community_posts';
        $users_table = $wpdb->users;
        
        $trending_posts = $wpdb->get_results("
            SELECT p.*, u.display_name, u.user_email,
                   (p.likes_count + p.comments_count + p.shares_count) as engagement_score
            FROM $posts_table p
            LEFT JOIN $users_table u ON p.user_id = u.ID
            WHERE p.privacy_level = 'public' 
                AND p.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            ORDER BY engagement_score DESC, p.created_at DESC
            LIMIT 10
        ");
        
        // Enhance posts with additional data
        foreach ($trending_posts as &$post) {
            $post->user_avatar = get_avatar_url($post->user_id);
            $post->is_liked = $this->is_post_liked($post->id, $this->user_id);
            $post->formatted_date = human_time_diff(strtotime($post->created_at)) . ' ago';
        }
        
        wp_send_json_success($trending_posts);
    }
    
    /**
     * Get active community challenges
     */
    public function get_active_challenges() {
        global $wpdb;
        
        $challenges_table = $wpdb->prefix . 'myavana_community_challenges';
        
        $challenges = $wpdb->get_results("
            SELECT *
            FROM $challenges_table
            WHERE is_active = 1 
                AND end_date > NOW()
            ORDER BY start_date DESC
        ");
        
        return $challenges;
    }
    
    /**
     * Get user's social stats
     */
    public function get_user_social_stats($user_id = null) {
        if (!$user_id) {
            $user_id = $this->user_id;
        }
        
        global $wpdb;
        
        $posts_table = $wpdb->prefix . 'myavana_community_posts';
        $followers_table = $wpdb->prefix . 'myavana_user_followers';
        $likes_table = $wpdb->prefix . 'myavana_post_likes';
        
        $stats = array();
        
        // Posts count
        $stats['posts_count'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $posts_table WHERE user_id = %d",
            $user_id
        ));
        
        // Followers count
        $stats['followers_count'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $followers_table WHERE following_id = %d",
            $user_id
        ));
        
        // Following count
        $stats['following_count'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $followers_table WHERE follower_id = %d",
            $user_id
        ));
        
        // Total likes received
        $stats['total_likes'] = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(p.likes_count) FROM $posts_table p WHERE p.user_id = %d",
            $user_id
        )) ?: 0;
        
        // Engagement rate (last 30 days)
        $stats['recent_engagement'] = $wpdb->get_var($wpdb->prepare(
            "SELECT AVG(p.likes_count + p.comments_count) 
             FROM $posts_table p 
             WHERE p.user_id = %d AND p.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
            $user_id
        )) ?: 0;
        
        return $stats;
    }
    
    // Helper methods
    private function is_post_liked($post_id, $user_id) {
        global $wpdb;
        
        $likes_table = $wpdb->prefix . 'myavana_post_likes';
        
        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $likes_table WHERE post_id = %d AND user_id = %d",
            $post_id, $user_id
        ));
    }
    
    private function get_recent_comments($post_id, $limit = 3) {
        global $wpdb;
        
        $comments_table = $wpdb->prefix . 'myavana_post_comments';
        $users_table = $wpdb->users;
        
        $comments = $wpdb->get_results($wpdb->prepare(
            "SELECT c.*, u.display_name FROM $comments_table c 
             LEFT JOIN $users_table u ON c.user_id = u.ID 
             WHERE c.post_id = %d AND c.parent_id = 0 
             ORDER BY c.created_at DESC LIMIT %d",
            $post_id, $limit
        ));
        
        foreach ($comments as &$comment) {
            $comment->user_avatar = get_avatar_url($comment->user_id, 32);
            $comment->formatted_date = human_time_diff(strtotime($comment->created_at)) . ' ago';
        }
        
        return $comments;
    }
    
    private function handle_image_upload($file) {
        if (!function_exists('wp_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }
        
        $upload_overrides = array('test_form' => false);
        $uploaded_file = wp_handle_upload($file, $upload_overrides);
        
        if (isset($uploaded_file['error'])) {
            return array('success' => false, 'error' => $uploaded_file['error']);
        }
        
        return array(
            'success' => true, 
            'url' => $uploaded_file['url'],
            'path' => $uploaded_file['file']
        );
    }
}

// Initialize the social features system
new Myavana_Social_Features();