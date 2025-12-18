<?php
/**
 * MYAVANA Gamification System
 *
 * Handles daily check-ins, streaks, badges, points, and engagement rewards
 *
 * @package Myavana_Hair_Journey
 * @version 2.3.5
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Myavana_Gamification {

    /**
     * Database version for migrations
     */
    const DB_VERSION = '1.0.0';

    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'register_database_tables'));
    }

    /**
     * Register and create database tables
     */
    public function register_database_tables() {
        // Only run on admin or during activation
        if (is_admin() || (defined('WP_CLI') && WP_CLI)) {
            $this->maybe_create_tables();
        }
    }

    /**
     * Create gamification tables if they don't exist
     */
    public function maybe_create_tables() {
        global $wpdb;

        $installed_version = get_option('myavana_gamification_db_version');

        if ($installed_version === self::DB_VERSION) {
            return; // Tables already created
        }

        $charset_collate = $wpdb->get_charset_collate();

        // Table 1: Daily Check-ins
        $table_checkins = $wpdb->prefix . 'myavana_daily_checkins';
        $sql_checkins = "CREATE TABLE IF NOT EXISTS {$table_checkins} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            check_in_date DATE NOT NULL,
            mood VARCHAR(50) DEFAULT NULL,
            points_earned INT(11) DEFAULT 10,
            streak_count INT(11) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uk_user_date (user_id, check_in_date),
            KEY idx_user_id (user_id),
            KEY idx_check_in_date (check_in_date)
        ) $charset_collate;";

        // Table 2: User Points & Stats
        $table_stats = $wpdb->prefix . 'myavana_user_stats';
        $sql_stats = "CREATE TABLE IF NOT EXISTS {$table_stats} (
            user_id BIGINT(20) UNSIGNED NOT NULL,
            total_points INT(11) DEFAULT 0,
            current_streak INT(11) DEFAULT 0,
            longest_streak INT(11) DEFAULT 0,
            last_check_in DATE DEFAULT NULL,
            total_entries INT(11) DEFAULT 0,
            total_ai_analyses INT(11) DEFAULT 0,
            level INT(11) DEFAULT 1,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (user_id)
        ) $charset_collate;";

        // Table 3: Badges
        $table_badges = $wpdb->prefix . 'myavana_badges';
        $sql_badges = "CREATE TABLE IF NOT EXISTS {$table_badges} (
            id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            badge_key VARCHAR(50) NOT NULL,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            icon_url VARCHAR(255) DEFAULT NULL,
            category VARCHAR(50) DEFAULT NULL,
            requirement_type VARCHAR(50) DEFAULT 'count',
            requirement_value INT(11) DEFAULT 1,
            points_reward INT(11) DEFAULT 100,
            rarity VARCHAR(20) DEFAULT 'common',
            display_order INT(11) DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY uk_badge_key (badge_key),
            KEY idx_category (category)
        ) $charset_collate;";

        // Table 4: User Badges
        $table_user_badges = $wpdb->prefix . 'myavana_user_badges';
        $sql_user_badges = "CREATE TABLE IF NOT EXISTS {$table_user_badges} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            badge_id INT(11) UNSIGNED NOT NULL,
            progress INT(11) DEFAULT 0,
            earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            notified TINYINT(1) DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY uk_user_badge (user_id, badge_id),
            KEY idx_user_id (user_id),
            KEY idx_earned_at (earned_at)
        ) $charset_collate;";

        // Table 5: Insights (Progressive Unlocking)
        $table_insights = $wpdb->prefix . 'myavana_insights';
        $sql_insights = "CREATE TABLE IF NOT EXISTS {$table_insights} (
            id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            insight_key VARCHAR(50) NOT NULL,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            unlock_requirement TEXT,
            display_order INT(11) DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY uk_insight_key (insight_key)
        ) $charset_collate;";

        // Table 6: User Unlocked Insights
        $table_user_insights = $wpdb->prefix . 'myavana_user_insights';
        $sql_user_insights = "CREATE TABLE IF NOT EXISTS {$table_user_insights} (
            user_id BIGINT(20) UNSIGNED NOT NULL,
            unlocked_insights TEXT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (user_id)
        ) $charset_collate;";

        // Table 7: AI Tips
        $table_ai_tips = $wpdb->prefix . 'myavana_ai_tips';
        $sql_ai_tips = "CREATE TABLE IF NOT EXISTS {$table_ai_tips} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            tip_text TEXT,
            tip_type VARCHAR(50) DEFAULT 'suggestion',
            based_on TEXT,
            shown_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            clicked TINYINT(1) DEFAULT 0,
            PRIMARY KEY (id),
            KEY idx_user_id (user_id),
            KEY idx_shown_at (shown_at)
        ) $charset_collate;";

        // Execute table creations
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        dbDelta($sql_checkins);
        dbDelta($sql_stats);
        dbDelta($sql_badges);
        dbDelta($sql_user_badges);
        dbDelta($sql_insights);
        dbDelta($sql_user_insights);
        dbDelta($sql_ai_tips);

        // Seed default badges
        $this->seed_default_badges();

        // Seed default insights
        $this->seed_default_insights();

        // Update version
        update_option('myavana_gamification_db_version', self::DB_VERSION);
    }

    /**
     * Seed default badges
     */
    private function seed_default_badges() {
        global $wpdb;
        $table = $wpdb->prefix . 'myavana_badges';

        $badges = array(
            // Consistency Badges
            array(
                'badge_key' => 'week_warrior',
                'name' => 'Week Warrior',
                'description' => 'Check in for 7 consecutive days',
                'category' => 'consistency',
                'requirement_type' => 'streak',
                'requirement_value' => 7,
                'points_reward' => 100,
                'rarity' => 'common'
            ),
            array(
                'badge_key' => 'monthly_maven',
                'name' => 'Monthly Maven',
                'description' => 'Check in for 30 consecutive days',
                'category' => 'consistency',
                'requirement_type' => 'streak',
                'requirement_value' => 30,
                'points_reward' => 500,
                'rarity' => 'rare'
            ),
            array(
                'badge_key' => 'century_club',
                'name' => 'Century Club',
                'description' => 'Create 100 hair journey entries',
                'category' => 'consistency',
                'requirement_type' => 'count',
                'requirement_value' => 100,
                'points_reward' => 1000,
                'rarity' => 'epic'
            ),
            // Journey Badges
            array(
                'badge_key' => 'moisture_master',
                'name' => 'Moisture Master',
                'description' => 'Complete the 7-day moisture challenge',
                'category' => 'journey',
                'requirement_type' => 'challenge',
                'requirement_value' => 7,
                'points_reward' => 200,
                'rarity' => 'rare'
            ),
            array(
                'badge_key' => 'growth_guru',
                'name' => 'Growth Guru',
                'description' => 'Track your growth for 90 consecutive days',
                'category' => 'journey',
                'requirement_type' => 'streak',
                'requirement_value' => 90,
                'points_reward' => 750,
                'rarity' => 'epic'
            ),
            array(
                'badge_key' => 'product_pro',
                'name' => 'Product Pro',
                'description' => 'Try 20 different hair products',
                'category' => 'journey',
                'requirement_type' => 'count',
                'requirement_value' => 20,
                'points_reward' => 300,
                'rarity' => 'rare'
            ),
            // AI Interaction Badges
            array(
                'badge_key' => 'ai_curious',
                'name' => 'AI Curious',
                'description' => 'Complete your first AI hair analysis',
                'category' => 'ai',
                'requirement_type' => 'milestone',
                'requirement_value' => 1,
                'points_reward' => 50,
                'rarity' => 'common'
            ),
            array(
                'badge_key' => 'data_driven',
                'name' => 'Data Driven',
                'description' => 'Complete 10 AI analyses',
                'category' => 'ai',
                'requirement_type' => 'count',
                'requirement_value' => 10,
                'points_reward' => 250,
                'rarity' => 'rare'
            ),
            array(
                'badge_key' => 'hair_scientist',
                'name' => 'Hair Scientist',
                'description' => 'Complete 50 AI analyses',
                'category' => 'ai',
                'requirement_type' => 'count',
                'requirement_value' => 50,
                'points_reward' => 1000,
                'rarity' => 'legendary'
            ),
            // Community Social Badges
            array(
                'badge_key' => 'community_starter',
                'name' => 'Community Starter',
                'description' => 'Share your first post to the community',
                'category' => 'community',
                'requirement_type' => 'milestone',
                'requirement_value' => 1,
                'points_reward' => 50,
                'rarity' => 'common'
            ),
            array(
                'badge_key' => 'trendsetter',
                'name' => 'Trendsetter',
                'description' => 'Get 100 likes on a single post',
                'category' => 'community',
                'requirement_type' => 'likes',
                'requirement_value' => 100,
                'points_reward' => 250,
                'rarity' => 'rare'
            ),
            array(
                'badge_key' => 'community_champion',
                'name' => 'Community Champion',
                'description' => 'Share 50 posts to the community',
                'category' => 'community',
                'requirement_type' => 'count',
                'requirement_value' => 50,
                'points_reward' => 500,
                'rarity' => 'epic'
            ),
            array(
                'badge_key' => 'mentor',
                'name' => 'Mentor',
                'description' => '10 people try your shared routine',
                'category' => 'community',
                'requirement_type' => 'routine_tries',
                'requirement_value' => 10,
                'points_reward' => 300,
                'rarity' => 'rare'
            ),
            array(
                'badge_key' => 'challenge_winner',
                'name' => 'Challenge Winner',
                'description' => 'Win a community challenge',
                'category' => 'community',
                'requirement_type' => 'challenge_win',
                'requirement_value' => 1,
                'points_reward' => 750,
                'rarity' => 'epic'
            ),
            array(
                'badge_key' => 'super_connector',
                'name' => 'Super Connector',
                'description' => 'Reach 50 followers',
                'category' => 'community',
                'requirement_type' => 'followers',
                'requirement_value' => 50,
                'points_reward' => 200,
                'rarity' => 'rare'
            ),
            array(
                'badge_key' => 'helpful_hero',
                'name' => 'Helpful Hero',
                'description' => 'Comment on 50 community posts',
                'category' => 'community',
                'requirement_type' => 'comments_made',
                'requirement_value' => 50,
                'points_reward' => 150,
                'rarity' => 'common'
            ),
            array(
                'badge_key' => 'transformation_star',
                'name' => 'Transformation Star',
                'description' => 'Share a before/after transformation post',
                'category' => 'community',
                'requirement_type' => 'transformation_post',
                'requirement_value' => 1,
                'points_reward' => 100,
                'rarity' => 'common'
            ),
            array(
                'badge_key' => 'routine_master',
                'name' => 'Routine Master',
                'description' => 'Share 5 routines to the community library',
                'category' => 'community',
                'requirement_type' => 'routines_shared',
                'requirement_value' => 5,
                'points_reward' => 250,
                'rarity' => 'rare'
            ),
            array(
                'badge_key' => 'challenge_enthusiast',
                'name' => 'Challenge Enthusiast',
                'description' => 'Complete 5 community challenges',
                'category' => 'community',
                'requirement_type' => 'challenges_completed',
                'requirement_value' => 5,
                'points_reward' => 400,
                'rarity' => 'epic'
            )
        );

        foreach ($badges as $badge) {
            // Check if badge already exists
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$table} WHERE badge_key = %s",
                $badge['badge_key']
            ));

            if (!$exists) {
                $wpdb->insert($table, $badge);
            }
        }
    }

    /**
     * Seed default insights
     */
    private function seed_default_insights() {
        global $wpdb;
        $table = $wpdb->prefix . 'myavana_insights';

        $insights = array(
            array(
                'insight_key' => 'product_effectiveness',
                'name' => 'Product Effectiveness Score',
                'description' => 'See which products work best for your hair',
                'unlock_requirement' => '{"type":"entry_count","value":3,"condition":"with_products"}',
                'display_order' => 1
            ),
            array(
                'insight_key' => 'shine_luster_trend',
                'name' => 'Shine & Luster Trend',
                'description' => 'Track your hair\'s shine over time',
                'unlock_requirement' => '{"type":"photo_count","value":1,"timeframe":"week"}',
                'display_order' => 2
            ),
            array(
                'insight_key' => 'ai_health_prediction',
                'name' => 'AI Hair Health Prediction',
                'description' => 'Get AI-powered predictions for your hair health',
                'unlock_requirement' => '{"type":"ai_analysis_count","value":5}',
                'display_order' => 3
            )
        );

        foreach ($insights as $insight) {
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$table} WHERE insight_key = %s",
                $insight['insight_key']
            ));

            if (!$exists) {
                $wpdb->insert($table, $insight);
            }
        }
    }

    /**
     * Initialize user stats
     */
    public static function init_user_stats($user_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'myavana_user_stats';

        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT user_id FROM {$table} WHERE user_id = %d",
            $user_id
        ));

        if (!$exists) {
            $wpdb->insert($table, array(
                'user_id' => $user_id,
                'total_points' => 0,
                'current_streak' => 0,
                'longest_streak' => 0,
                'level' => 1
            ));
        }
    }

    /**
     * Get user stats
     */
    public static function get_user_stats($user_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'myavana_user_stats';

        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE user_id = %d",
            $user_id
        ), ARRAY_A);

        if (!$stats) {
            self::init_user_stats($user_id);
            return self::get_user_stats($user_id);
        }

        return $stats;
    }
}

// Initialize
new Myavana_Gamification();
