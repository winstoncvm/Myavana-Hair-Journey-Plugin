<?php
/**
 * Unified Profile Management & Community - Database Schema
 * Creates all necessary database tables for new features
 *
 * @package Myavana_Hair_Journey
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class UPM_CM_Database_Schema {

    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Stories Table
        $stories_table = $wpdb->prefix . 'upm_cm_stories';
        $stories_sql = "CREATE TABLE $stories_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            content_type varchar(20) DEFAULT 'image',
            content_url varchar(500) NOT NULL,
            thumbnail_url varchar(500),
            caption text,
            background_color varchar(20),
            duration int(11) DEFAULT 5,
            views_count int(11) DEFAULT 0,
            is_highlight tinyint(1) DEFAULT 0,
            highlight_title varchar(100),
            expires_at datetime NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY is_highlight (is_highlight),
            KEY expires_at (expires_at),
            KEY created_at (created_at)
        ) $charset_collate;";

        // Story Views Table
        $story_views_table = $wpdb->prefix . 'upm_cm_story_views';
        $story_views_sql = "CREATE TABLE $story_views_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            story_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            viewed_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY story_user (story_id, user_id),
            KEY story_id (story_id),
            KEY user_id (user_id)
        ) $charset_collate;";

        // Direct Messages Table
        $messages_table = $wpdb->prefix . 'upm_cm_messages';
        $messages_sql = "CREATE TABLE $messages_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            sender_id bigint(20) NOT NULL,
            receiver_id bigint(20) NOT NULL,
            message_text text NOT NULL,
            attachment_url varchar(500),
            attachment_type varchar(50),
            shared_entry_id bigint(20),
            shared_routine_id bigint(20),
            is_read tinyint(1) DEFAULT 0,
            read_at datetime,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY sender_id (sender_id),
            KEY receiver_id (receiver_id),
            KEY is_read (is_read),
            KEY created_at (created_at)
        ) $charset_collate;";

        // Message Threads Table (for conversation grouping)
        $threads_table = $wpdb->prefix . 'upm_cm_message_threads';
        $threads_sql = "CREATE TABLE $threads_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user1_id bigint(20) NOT NULL,
            user2_id bigint(20) NOT NULL,
            last_message_id bigint(20),
            last_message_at datetime,
            unread_count_user1 int(11) DEFAULT 0,
            unread_count_user2 int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_pair (user1_id, user2_id),
            KEY user1_id (user1_id),
            KEY user2_id (user2_id),
            KEY last_message_at (last_message_at)
        ) $charset_collate;";

        // Community Groups Table
        $groups_table = $wpdb->prefix . 'upm_cm_groups';
        $groups_sql = "CREATE TABLE $groups_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(200) NOT NULL,
            description text,
            group_type varchar(50) DEFAULT 'general',
            cover_image varchar(500),
            hair_type_filter varchar(50),
            goal_filter varchar(100),
            is_private tinyint(1) DEFAULT 0,
            creator_id bigint(20) NOT NULL,
            members_count int(11) DEFAULT 0,
            posts_count int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY creator_id (creator_id),
            KEY group_type (group_type),
            KEY is_private (is_private),
            KEY created_at (created_at)
        ) $charset_collate;";

        // Group Members Table
        $group_members_table = $wpdb->prefix . 'upm_cm_group_members';
        $group_members_sql = "CREATE TABLE $group_members_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            group_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            role varchar(20) DEFAULT 'member',
            joined_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY group_user (group_id, user_id),
            KEY group_id (group_id),
            KEY user_id (user_id),
            KEY role (role)
        ) $charset_collate;";

        // Group Posts Table
        $group_posts_table = $wpdb->prefix . 'upm_cm_group_posts';
        $group_posts_sql = "CREATE TABLE $group_posts_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            group_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            content text NOT NULL,
            media_url varchar(500),
            media_type varchar(20),
            likes_count int(11) DEFAULT 0,
            comments_count int(11) DEFAULT 0,
            is_pinned tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY group_id (group_id),
            KEY user_id (user_id),
            KEY is_pinned (is_pinned),
            KEY created_at (created_at)
        ) $charset_collate;";

        // Post Reactions Table (beyond just likes)
        $reactions_table = $wpdb->prefix . 'upm_cm_post_reactions';
        $reactions_sql = "CREATE TABLE $reactions_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id mediumint(9) NOT NULL,
            user_id bigint(20) NOT NULL,
            reaction_type varchar(20) DEFAULT 'like',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY post_user_reaction (post_id, user_id),
            KEY post_id (post_id),
            KEY user_id (user_id),
            KEY reaction_type (reaction_type)
        ) $charset_collate;";

        // Polls Table
        $polls_table = $wpdb->prefix . 'upm_cm_polls';
        $polls_sql = "CREATE TABLE $polls_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id mediumint(9) NOT NULL,
            question varchar(500) NOT NULL,
            allow_multiple tinyint(1) DEFAULT 0,
            ends_at datetime,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY ends_at (ends_at)
        ) $charset_collate;";

        // Poll Options Table
        $poll_options_table = $wpdb->prefix . 'upm_cm_poll_options';
        $poll_options_sql = "CREATE TABLE $poll_options_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            poll_id bigint(20) NOT NULL,
            option_text varchar(200) NOT NULL,
            votes_count int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY poll_id (poll_id)
        ) $charset_collate;";

        // Poll Votes Table
        $poll_votes_table = $wpdb->prefix . 'upm_cm_poll_votes';
        $poll_votes_sql = "CREATE TABLE $poll_votes_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            poll_id bigint(20) NOT NULL,
            option_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            voted_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY poll_option_user (poll_id, option_id, user_id),
            KEY poll_id (poll_id),
            KEY user_id (user_id)
        ) $charset_collate;";

        // User Mentions Table
        $mentions_table = $wpdb->prefix . 'upm_cm_mentions';
        $mentions_sql = "CREATE TABLE $mentions_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            mentioned_user_id bigint(20) NOT NULL,
            mentioning_user_id bigint(20) NOT NULL,
            post_id mediumint(9),
            comment_id mediumint(9),
            message_id bigint(20),
            is_read tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY mentioned_user_id (mentioned_user_id),
            KEY mentioning_user_id (mentioning_user_id),
            KEY is_read (is_read)
        ) $charset_collate;";

        // AI Recommendations Table
        $ai_recommendations_table = $wpdb->prefix . 'upm_cm_ai_recommendations';
        $ai_recommendations_sql = "CREATE TABLE $ai_recommendations_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            recommendation_type varchar(50) NOT NULL,
            recommendation_data longtext NOT NULL,
            confidence_score decimal(3,2),
            is_viewed tinyint(1) DEFAULT 0,
            is_dismissed tinyint(1) DEFAULT 0,
            is_accepted tinyint(1) DEFAULT 0,
            expires_at datetime,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY recommendation_type (recommendation_type),
            KEY is_viewed (is_viewed),
            KEY created_at (created_at)
        ) $charset_collate;";

        // Journey Exports Table
        $exports_table = $wpdb->prefix . 'upm_cm_journey_exports';
        $exports_sql = "CREATE TABLE $exports_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            export_type varchar(50) NOT NULL,
            export_format varchar(20) NOT NULL,
            file_url varchar(500),
            file_size int(11),
            date_range_start date,
            date_range_end date,
            status varchar(20) DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            completed_at datetime,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY export_type (export_type),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";

        // Product Database Table
        $products_table = $wpdb->prefix . 'upm_cm_products';
        $products_sql = "CREATE TABLE $products_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(200) NOT NULL,
            brand varchar(100),
            category varchar(50),
            product_type varchar(50),
            hair_type_suited varchar(100),
            image_url varchar(500),
            description text,
            average_rating decimal(3,2) DEFAULT 0,
            total_reviews int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY brand (brand),
            KEY category (category),
            KEY product_type (product_type),
            KEY average_rating (average_rating)
        ) $charset_collate;";

        // Product Reviews Table
        $product_reviews_table = $wpdb->prefix . 'upm_cm_product_reviews';
        $product_reviews_sql = "CREATE TABLE $product_reviews_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            product_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            rating int(11) NOT NULL,
            review_text text,
            works_for_my_hair tinyint(1) DEFAULT 0,
            entry_id bigint(20),
            helpful_count int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY product_user (product_id, user_id),
            KEY product_id (product_id),
            KEY user_id (user_id),
            KEY rating (rating)
        ) $charset_collate;";

        // Video Entries Table (extends regular entries)
        $video_entries_table = $wpdb->prefix . 'upm_cm_video_entries';
        $video_entries_sql = "CREATE TABLE $video_entries_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            entry_id bigint(20) NOT NULL,
            video_url varchar(500) NOT NULL,
            thumbnail_url varchar(500),
            duration int(11),
            video_type varchar(50),
            transcription text,
            views_count int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY entry_id (entry_id),
            KEY video_type (video_type)
        ) $charset_collate;";

        // Advanced Analytics Cache Table
        $analytics_cache_table = $wpdb->prefix . 'upm_cm_analytics_cache';
        $analytics_cache_sql = "CREATE TABLE $analytics_cache_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            metric_type varchar(50) NOT NULL,
            metric_data longtext NOT NULL,
            period varchar(20),
            calculated_at datetime DEFAULT CURRENT_TIMESTAMP,
            expires_at datetime,
            PRIMARY KEY (id),
            UNIQUE KEY user_metric_period (user_id, metric_type, period),
            KEY user_id (user_id),
            KEY metric_type (metric_type),
            KEY expires_at (expires_at)
        ) $charset_collate;";

        // Execute all table creation queries
        dbDelta($stories_sql);
        dbDelta($story_views_sql);
        dbDelta($messages_sql);
        dbDelta($threads_sql);
        dbDelta($groups_sql);
        dbDelta($group_members_sql);
        dbDelta($group_posts_sql);
        dbDelta($reactions_sql);
        dbDelta($polls_sql);
        dbDelta($poll_options_sql);
        dbDelta($poll_votes_sql);
        dbDelta($mentions_sql);
        dbDelta($ai_recommendations_sql);
        dbDelta($exports_sql);
        dbDelta($products_table);
        dbDelta($product_reviews_sql);
        dbDelta($video_entries_sql);
        dbDelta($analytics_cache_sql);

        // Set database version
        update_option('upm_cm_db_version', '1.0.0');
    }

    /**
     * Check if tables need to be created or updated
     */
    public static function maybe_update_database() {
        $current_version = get_option('upm_cm_db_version', '0');
        $target_version = '1.0.0';

        if (version_compare($current_version, $target_version, '<')) {
            self::create_tables();
        }
    }
}

// Initialize on plugin activation or admin init
add_action('admin_init', array('UPM_CM_Database_Schema', 'maybe_update_database'));
