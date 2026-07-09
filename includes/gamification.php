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
    const DB_VERSION = '1.2.0';
    const LEVEL_STEP = 100;
    private static $schema_checked = false;

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
    public function maybe_create_tables($force = false) {
        global $wpdb;

        $installed_version = get_option('myavana_gamification_db_version');

        if (!$force && $installed_version === self::DB_VERSION) {
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

        // Table 5: Points/Event history
        $table_points_history = $wpdb->prefix . 'myavana_points_history';
        $sql_points_history = "CREATE TABLE IF NOT EXISTS {$table_points_history} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            event_key VARCHAR(120) DEFAULT NULL,
            points_change INT(11) DEFAULT 0,
            reason VARCHAR(191) NOT NULL,
            reference_type VARCHAR(50) DEFAULT NULL,
            reference_id BIGINT(20) UNSIGNED DEFAULT NULL,
            meta_data LONGTEXT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_user_id (user_id),
            KEY idx_event_key (event_key),
            KEY idx_reference (reference_type, reference_id),
            KEY idx_created_at (created_at)
        ) $charset_collate;";

        // Table 6: Insights (Progressive Unlocking)
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

        // Table 7: User Unlocked Insights
        $table_user_insights = $wpdb->prefix . 'myavana_user_insights';
        $sql_user_insights = "CREATE TABLE IF NOT EXISTS {$table_user_insights} (
            user_id BIGINT(20) UNSIGNED NOT NULL,
            unlocked_insights TEXT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (user_id)
        ) $charset_collate;";

        // Table 8: AI Tips
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
        dbDelta($sql_points_history);
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
     * Ensure the required schema exists before frontend summary/stat calls.
     */
    private static function ensure_runtime_schema() {
        if (self::$schema_checked) {
            return;
        }

        self::$schema_checked = true;

        global $wpdb;

        $table = $wpdb->prefix . 'myavana_user_stats';
        $required_columns = [
            'longest_streak' => "ALTER TABLE {$table} ADD COLUMN longest_streak INT(11) DEFAULT 0 AFTER current_streak",
            'total_entries' => "ALTER TABLE {$table} ADD COLUMN total_entries INT(11) DEFAULT 0 AFTER last_check_in",
            'total_ai_analyses' => "ALTER TABLE {$table} ADD COLUMN total_ai_analyses INT(11) DEFAULT 0 AFTER total_entries",
            'level' => "ALTER TABLE {$table} ADD COLUMN level INT(11) DEFAULT 1 AFTER total_ai_analyses",
        ];
        $missing_columns = [];

        $table_exists = (bool) $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));
        if ($table_exists) {
            foreach (array_keys($required_columns) as $column) {
                $column_exists = (bool) $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$table} LIKE %s", $column));
                if (!$column_exists) {
                    $missing_columns[] = $column;
                }
            }
        }

        $installed_version = get_option('myavana_gamification_db_version');
        if (!$table_exists || !empty($missing_columns) || version_compare((string) $installed_version, self::DB_VERSION, '<')) {
            $instance = new self();
            $instance->maybe_create_tables(true);

            $table_exists = (bool) $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));
            if ($table_exists) {
                foreach ($missing_columns as $column) {
                    if (!isset($required_columns[$column])) {
                        continue;
                    }

                    $wpdb->query($required_columns[$column]);
                }
            }
        }
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
        self::ensure_runtime_schema();
        $table = $wpdb->prefix . 'myavana_user_stats';

        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT user_id FROM {$table} WHERE user_id = %d",
            $user_id
        ));

        if (!$exists) {
            $inserted = $wpdb->insert($table, array(
                'user_id' => $user_id,
                'total_points' => 0,
                'current_streak' => 0,
                'longest_streak' => 0,
                'level' => 1
            ));

            if ($inserted === false) {
                error_log('Myavana_Gamification::init_user_stats insert failed for user ' . (int) $user_id . ': ' . $wpdb->last_error);
                return false;
            }
        }

        return true;
    }

    /**
     * Get user stats
     */
    public static function get_user_stats($user_id) {
        global $wpdb;
        self::ensure_runtime_schema();
        $table = $wpdb->prefix . 'myavana_user_stats';

        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE user_id = %d",
            $user_id
        ), ARRAY_A);

        if (!$stats) {
            $initialized = self::init_user_stats($user_id);
            if (!$initialized) {
                return [
                    'user_id' => (int) $user_id,
                    'total_points' => 0,
                    'current_streak' => 0,
                    'longest_streak' => 0,
                    'last_check_in' => null,
                    'total_entries' => 0,
                    'total_ai_analyses' => 0,
                    'level' => 1,
                ];
            }

            $stats = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$table} WHERE user_id = %d",
                $user_id
            ), ARRAY_A);

            if (!$stats) {
                return [
                    'user_id' => (int) $user_id,
                    'total_points' => 0,
                    'current_streak' => 0,
                    'longest_streak' => 0,
                    'last_check_in' => null,
                    'total_entries' => 0,
                    'total_ai_analyses' => 0,
                    'level' => 1,
                ];
            }
        }

        return $stats;
    }

    /**
     * Recalculate totals that are derived from the user's content/activity.
     */
    public static function sync_user_totals($user_id) {
        global $wpdb;

        if (!$user_id) {
            return [
                'total_entries' => 0,
                'total_ai_analyses' => 0,
            ];
        }

        self::init_user_stats($user_id);

        $table = $wpdb->prefix . 'myavana_user_stats';

        $total_entries = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*)
             FROM {$wpdb->posts}
             WHERE post_author = %d
               AND post_type = 'hair_journey_entry'
               AND post_status = 'publish'",
            $user_id
        ));

        $meta_ai_count = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT p.ID)
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE p.post_author = %d
               AND p.post_type = 'hair_journey_entry'
               AND p.post_status = 'publish'
               AND (
                   (pm.meta_key = 'entry_type' AND pm.meta_value = 'AI Analysis')
                   OR pm.meta_key = 'analysis_data'
               )",
            $user_id
        ));

        $analysis_history = get_user_meta($user_id, 'myavana_hair_analysis_history', true);
        $analysis_history_count = is_array($analysis_history) ? count($analysis_history) : 0;
        $total_ai_analyses = max($meta_ai_count, $analysis_history_count);

        $wpdb->update(
            $table,
            [
                'total_entries' => $total_entries,
                'total_ai_analyses' => $total_ai_analyses,
            ],
            ['user_id' => $user_id],
            ['%d', '%d'],
            ['%d']
        );

        return [
            'total_entries' => $total_entries,
            'total_ai_analyses' => $total_ai_analyses,
        ];
    }

    /**
     * Award points through the canonical stats + event history path.
     */
    public static function award_points($user_id, $points, $reason, $reference_type = null, $reference_id = null, $event_key = '', $meta = []) {
        global $wpdb;

        $user_id = (int) $user_id;
        $points = (int) $points;

        if ($user_id <= 0 || $points === 0) {
            return false;
        }

        self::init_user_stats($user_id);

        $history_table = $wpdb->prefix . 'myavana_points_history';
        $stats_table = $wpdb->prefix . 'myavana_user_stats';
        $event_key = sanitize_text_field((string) $event_key);

        if ($event_key !== '') {
            $already_awarded = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$history_table} WHERE user_id = %d AND event_key = %s LIMIT 1",
                $user_id,
                $event_key
            ));

            if ($already_awarded) {
                return false;
            }
        }

        $wpdb->insert(
            $history_table,
            [
                'user_id' => $user_id,
                'event_key' => $event_key !== '' ? $event_key : null,
                'points_change' => $points,
                'reason' => sanitize_text_field($reason),
                'reference_type' => $reference_type ? sanitize_text_field($reference_type) : null,
                'reference_id' => $reference_id !== null ? (int) $reference_id : null,
                'meta_data' => !empty($meta) ? wp_json_encode($meta) : null,
            ],
            ['%d', '%s', '%d', '%s', '%s', '%d', '%s']
        );

        $wpdb->query($wpdb->prepare(
            "UPDATE {$stats_table}
             SET total_points = total_points + %d
             WHERE user_id = %d",
            $points,
            $user_id
        ));

        $updated_stats = self::get_user_stats($user_id);
        $new_level = self::level_from_points((int) $updated_stats['total_points']);

        $wpdb->update(
            $stats_table,
            ['level' => $new_level],
            ['user_id' => $user_id],
            ['%d'],
            ['%d']
        );

        update_user_meta($user_id, 'myavana_points', (int) $updated_stats['total_points']);
        if (function_exists('myavana_clear_user_cache')) {
            myavana_clear_user_cache($user_id);
        }

        if ($reference_type !== 'challenge') {
            self::maybe_award_completed_challenges($user_id);
        }

        return true;
    }

    /**
     * Increment a stats column in a safe, centralized way.
     */
    public static function increment_stat($user_id, $column, $amount = 1) {
        global $wpdb;

        $allowed_columns = ['total_entries', 'total_ai_analyses', 'current_streak', 'longest_streak'];
        if (!$user_id || !in_array($column, $allowed_columns, true)) {
            return false;
        }

        self::init_user_stats($user_id);

        $table = $wpdb->prefix . 'myavana_user_stats';
        $amount = (int) $amount;

        return (bool) $wpdb->query($wpdb->prepare(
            "UPDATE {$table} SET {$column} = {$column} + %d WHERE user_id = %d",
            $amount,
            $user_id
        ));
    }

    /**
     * Handle daily check-ins through the canonical service.
     */
    public static function record_daily_checkin($user_id, $mood = '') {
        global $wpdb;

        $user_id = (int) $user_id;
        if ($user_id <= 0) {
            return new WP_Error('not_logged_in', 'User not logged in');
        }

        self::init_user_stats($user_id);

        $checkins_table = $wpdb->prefix . 'myavana_daily_checkins';
        $stats_table = $wpdb->prefix . 'myavana_user_stats';
        $today = current_time('Y-m-d');

        $existing = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$checkins_table} WHERE user_id = %d AND check_in_date = %s LIMIT 1",
            $user_id,
            $today
        ));

        if ($existing) {
            return new WP_Error('already_checked_in', 'Already checked in today');
        }

        $stats = self::get_user_stats($user_id);
        $last_checkin = $stats['last_check_in'] ?? null;
        $current_streak = (int) ($stats['current_streak'] ?? 0);
        $yesterday = gmdate('Y-m-d', strtotime($today . ' -1 day'));

        if ($last_checkin === $yesterday) {
            $current_streak++;
        } else {
            $current_streak = 1;
        }

        $bonus_points = 0;
        $milestone = null;
        $base_points = self::get_reward_value('daily_checkin_base', 10);
        if ($current_streak === 7) {
            $bonus_points = self::get_reward_value('daily_checkin_streak_bonus_7', 50);
            $milestone = '7-day streak';
        } elseif ($current_streak === 30) {
            $bonus_points = self::get_reward_value('daily_checkin_streak_bonus_30', 200);
            $milestone = '30-day streak';
        } elseif ($current_streak > 0 && $current_streak % 10 === 0) {
            $bonus_points = self::get_reward_value('daily_checkin_streak_bonus_10x', 25);
            $milestone = $current_streak . '-day streak';
        }

        $points_earned = $base_points + $bonus_points;

        $wpdb->insert(
            $checkins_table,
            [
                'user_id' => $user_id,
                'check_in_date' => $today,
                'mood' => $mood ? sanitize_text_field($mood) : null,
                'points_earned' => $points_earned,
                'streak_count' => $current_streak,
            ],
            ['%d', '%s', '%s', '%d', '%d']
        );

        $longest_streak = max($current_streak, (int) ($stats['longest_streak'] ?? 0));
        $wpdb->update(
            $stats_table,
            [
                'current_streak' => $current_streak,
                'longest_streak' => $longest_streak,
                'last_check_in' => $today,
            ],
            ['user_id' => $user_id],
            ['%d', '%d', '%s'],
            ['%d']
        );

        self::award_points(
            $user_id,
            $points_earned,
            $milestone ? 'Daily hair check-in milestone' : 'Daily hair check-in',
            'daily_checkin',
            strtotime($today),
            'daily_checkin:' . $today,
            [
                'mood' => $mood,
                'bonus_points' => $bonus_points,
                'milestone' => $milestone,
            ]
        );

        $new_badges = function_exists('myavana_check_badge_unlocks') ? myavana_check_badge_unlocks($user_id) : [];
        $summary = self::get_summary($user_id);

        return [
            'points_earned' => $points_earned,
            'bonus_points' => $bonus_points,
            'streak' => $summary['current_streak'],
            'level' => $summary['level'],
            'total_points' => $summary['total_points'],
            'milestone' => $milestone,
            'new_badges' => $new_badges,
            'message' => $milestone ? "🔥 {$milestone} bonus!" : 'Check-in complete!',
        ];
    }

    public static function get_summary($user_id) {
        global $wpdb;

        $user_id = (int) $user_id;
        if ($user_id <= 0) {
            return self::get_empty_summary();
        }

        self::init_user_stats($user_id);
        $totals = self::sync_user_totals($user_id);
        $stats = self::get_user_stats($user_id);

        $entry_streak = self::calculate_entry_streak($user_id);
        $current_streak = max($entry_streak, (int) ($stats['current_streak'] ?? 0));
        $longest_streak = max($current_streak, (int) ($stats['longest_streak'] ?? 0));
        $total_points = (int) ($stats['total_points'] ?? 0);
        $level = self::level_from_points($total_points);
        $bounds = self::level_bounds($level);
        $badges_earned = self::get_badges_earned_count($user_id);
        $recent_badges = self::get_recent_badges($user_id, 3);

        return [
            'total_points' => $total_points,
            'current_streak' => $current_streak,
            'longest_streak' => $longest_streak,
            'level' => $level,
            'current_level_points' => $bounds['start'],
            'next_level_points' => $bounds['end'],
            'progress_to_next_level' => max(0, $total_points - $bounds['start']),
            'points_to_next_level' => max(0, $bounds['end'] - $total_points),
            'xp_progress_percent' => $bounds['end'] > $bounds['start']
                ? (int) round((($total_points - $bounds['start']) / ($bounds['end'] - $bounds['start'])) * 100)
                : 100,
            'badges_earned' => $badges_earned,
            'recent_badges' => $recent_badges,
            'next_badge' => self::get_next_badge($user_id, $current_streak, (int) $totals['total_entries'], (int) $totals['total_ai_analyses']),
            'checked_in_today' => self::has_checked_in_today($user_id),
            'total_entries' => (int) $totals['total_entries'],
            'total_ai_analyses' => (int) $totals['total_ai_analyses'],
            'daily_quests' => self::get_daily_quests($user_id),
            'weekly_quests' => self::get_weekly_quests($user_id),
            'active_challenges' => self::get_active_challenges($user_id),
            'recent_rewards' => self::get_recent_rewards($user_id, 5),
        ];
    }

    public static function get_reward_settings() {
        $defaults = [
            'daily_checkin_base' => 10,
            'daily_checkin_streak_bonus_7' => 50,
            'daily_checkin_streak_bonus_30' => 200,
            'daily_checkin_streak_bonus_10x' => 25,
            'entry_created' => 15,
            'entry_photo_bonus' => 5,
            'entry_video_bonus' => 10,
            'ai_entry_bonus' => 20,
            'ai_analysis_saved' => 25,
            'goal_created' => 20,
            'goal_milestone' => 12,
            'goal_completed' => 35,
            'routine_created' => 15,
            'routine_completed' => 10,
            'onboarding_completed' => 25,
        ];

        $saved = get_option('myavana_gamification_reward_settings', []);
        if (!is_array($saved)) {
            $saved = [];
        }

        return array_map('intval', wp_parse_args($saved, $defaults));
    }

    public static function get_reward_value($key, $default = 0) {
        $settings = self::get_reward_settings();
        return isset($settings[$key]) ? intval($settings[$key]) : intval($default);
    }

    public static function get_challenge_definitions() {
        $current_month_start = wp_date('Y-m-01');
        $current_month_end = wp_date('Y-m-t');

        $defaults = [
            [
                'id' => 'consistency_sprint',
                'title' => 'Consistency Sprint',
                'description' => 'Log 3 entries this week to build momentum.',
                'metric' => 'entries_week',
                'target' => 3,
                'reward_points' => 80,
                'status' => 'active',
                'audience' => 'all',
                'category' => 'weekly',
                'start_date' => '',
                'end_date' => '',
            ],
            [
                'id' => 'routine_master',
                'title' => 'Routine Master',
                'description' => 'Complete 4 routines this week.',
                'metric' => 'routines_week',
                'target' => 4,
                'reward_points' => 60,
                'status' => 'active',
                'audience' => 'all',
                'category' => 'routine',
                'start_date' => '',
                'end_date' => '',
            ],
            [
                'id' => 'ai_refresh',
                'title' => 'AI Refresh',
                'description' => 'Run 1 AI analysis this week.',
                'metric' => 'ai_week',
                'target' => 1,
                'reward_points' => 75,
                'status' => 'active',
                'audience' => 'all',
                'category' => 'ai',
                'start_date' => '',
                'end_date' => '',
            ],
            [
                'id' => 'streak_builder',
                'title' => 'Streak Builder',
                'description' => 'Reach a 7-day streak.',
                'metric' => 'current_streak',
                'target' => 7,
                'reward_points' => 120,
                'status' => 'active',
                'audience' => 'all',
                'category' => 'streak',
                'start_date' => '',
                'end_date' => '',
            ],
            [
                'id' => 'community_spotlight',
                'title' => 'Community Spotlight',
                'description' => 'Share 2 community posts this month.',
                'metric' => 'community_posts_month',
                'target' => 2,
                'reward_points' => 90,
                'status' => 'active',
                'audience' => 'all',
                'category' => 'community',
                'start_date' => $current_month_start,
                'end_date' => $current_month_end,
            ],
            [
                'id' => 'video_storyteller',
                'title' => 'Video Storyteller',
                'description' => 'Add 1 video entry this month.',
                'metric' => 'video_entries_month',
                'target' => 1,
                'reward_points' => 100,
                'status' => 'active',
                'audience' => 'all',
                'category' => 'media',
                'start_date' => $current_month_start,
                'end_date' => $current_month_end,
            ],
        ];

        $saved = get_option('myavana_gamification_active_challenges', []);
        if (!is_array($saved) || empty($saved)) {
            return $defaults;
        }

        $normalized = [];
        foreach ($saved as $challenge) {
            if (!is_array($challenge) || empty($challenge['id'])) {
                continue;
            }

            $normalized[] = [
                'id' => sanitize_key($challenge['id']),
                'title' => sanitize_text_field($challenge['title'] ?? 'Challenge'),
                'description' => sanitize_textarea_field($challenge['description'] ?? ''),
                'metric' => sanitize_key($challenge['metric'] ?? 'entries_week'),
                'target' => max(1, intval($challenge['target'] ?? 1)),
                'reward_points' => max(0, intval($challenge['reward_points'] ?? 0)),
                'status' => sanitize_key($challenge['status'] ?? 'active'),
                'audience' => sanitize_key($challenge['audience'] ?? 'all'),
                'category' => sanitize_key($challenge['category'] ?? 'custom'),
                'start_date' => self::sanitize_challenge_date($challenge['start_date'] ?? ''),
                'end_date' => self::sanitize_challenge_date($challenge['end_date'] ?? ''),
            ];
        }

        return !empty($normalized) ? $normalized : $defaults;
    }

    public static function level_from_points($points) {
        $points = max(0, (int) $points);
        return (int) floor($points / self::LEVEL_STEP) + 1;
    }

    public static function level_bounds($level) {
        $level = max(1, (int) $level);
        $start = ($level - 1) * self::LEVEL_STEP;
        $end = $level * self::LEVEL_STEP;

        return [
            'start' => $start,
            'end' => $end,
        ];
    }

    public static function has_checked_in_today($user_id) {
        global $wpdb;

        if (!$user_id) {
            return false;
        }

        $checkins_table = $wpdb->prefix . 'myavana_daily_checkins';
        $today = current_time('Y-m-d');

        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$checkins_table} WHERE user_id = %d AND check_in_date = %s LIMIT 1",
            $user_id,
            $today
        ));
    }

    public static function calculate_entry_streak($user_id) {
        global $wpdb;

        $dates = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT DATE(post_date) AS entry_date
             FROM {$wpdb->posts}
             WHERE post_author = %d
               AND post_type = 'hair_journey_entry'
               AND post_status = 'publish'
             ORDER BY entry_date DESC",
            $user_id
        ));

        if (empty($dates)) {
            return 0;
        }

        $today = current_time('Y-m-d');
        $yesterday = gmdate('Y-m-d', strtotime($today . ' -1 day'));
        if ($dates[0] !== $today && $dates[0] !== $yesterday) {
            return 0;
        }

        $streak = 0;
        $current_date = $dates[0];
        foreach ($dates as $date) {
            if ($date !== $current_date) {
                break;
            }
            $streak++;
            $current_date = gmdate('Y-m-d', strtotime($current_date . ' -1 day'));
        }

        return $streak;
    }

    private static function get_daily_quests($user_id) {
        $today = current_time('Y-m-d');
        $entries_today = self::count_entries_between($user_id, $today, $today);
        $routine_completions_today = self::count_routine_completions_between($user_id, $today, $today);

        return [
            [
                'id' => 'checkin_today',
                'label' => 'Check in today',
                'description' => 'Protect your streak and log how your hair feels.',
                'current' => self::has_checked_in_today($user_id) ? 1 : 0,
                'target' => 1,
                'reward_label' => '+' . self::get_reward_value('daily_checkin_base', 10) . ' pts',
            ],
            [
                'id' => 'entry_today',
                'label' => 'Log today\'s entry',
                'description' => 'Capture progress with notes, images, or video.',
                'current' => min(1, $entries_today),
                'target' => 1,
                'reward_label' => '+' . self::get_reward_value('entry_created', 15) . ' pts',
            ],
            [
                'id' => 'routine_today',
                'label' => 'Complete one routine',
                'description' => 'Mark one routine done to stay consistent.',
                'current' => min(1, $routine_completions_today),
                'target' => 1,
                'reward_label' => '+' . self::get_reward_value('routine_completed', 10) . ' pts',
            ],
        ];
    }

    private static function get_weekly_quests($user_id) {
        $today = new DateTimeImmutable('now', wp_timezone());
        $week_start = $today->modify('monday this week')->format('Y-m-d');
        $week_end = $today->modify('sunday this week')->format('Y-m-d');

        $entries_this_week = self::count_entries_between($user_id, $week_start, $week_end);
        $routine_completions_this_week = self::count_routine_completions_between($user_id, $week_start, $week_end);
        $ai_this_week = self::count_ai_analyses_between($user_id, $week_start, $week_end);

        return [
            [
                'id' => 'entries_week',
                'label' => 'Log 3 entries this week',
                'description' => 'Build a fuller timeline of your progress.',
                'current' => min(3, $entries_this_week),
                'target' => 3,
                'reward_label' => 'Consistency boost',
            ],
            [
                'id' => 'routines_week',
                'label' => 'Complete 2 routines',
                'description' => 'Turn your routine into measurable momentum.',
                'current' => min(2, $routine_completions_this_week),
                'target' => 2,
                'reward_label' => 'Momentum boost',
            ],
            [
                'id' => 'ai_week',
                'label' => 'Run 1 AI analysis',
                'description' => 'Keep your recommendations fresh with new analysis.',
                'current' => min(1, $ai_this_week),
                'target' => 1,
                'reward_label' => '+' . self::get_reward_value('ai_analysis_saved', 25) . ' pts',
            ],
        ];
    }

    private static function count_entries_between($user_id, $start_date, $end_date) {
        global $wpdb;

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*)
             FROM {$wpdb->posts}
             WHERE post_author = %d
               AND post_type = 'hair_journey_entry'
               AND post_status = 'publish'
               AND DATE(post_date) BETWEEN %s AND %s",
            $user_id,
            $start_date,
            $end_date
        ));
    }

    private static function count_routine_completions_between($user_id, $start_date, $end_date) {
        $completion_map = get_user_meta($user_id, 'myavana_routine_completions', true);
        if (!is_array($completion_map)) {
            return 0;
        }

        $total = 0;
        foreach ($completion_map as $date_key => $routine_ids) {
            if ($date_key < $start_date || $date_key > $end_date || !is_array($routine_ids)) {
                continue;
            }
            $total += count(array_unique(array_map('intval', $routine_ids)));
        }

        return $total;
    }

    private static function count_ai_analyses_between($user_id, $start_date, $end_date) {
        global $wpdb;

        $post_count = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT p.ID)
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE p.post_author = %d
               AND p.post_type = 'hair_journey_entry'
               AND p.post_status = 'publish'
               AND DATE(p.post_date) BETWEEN %s AND %s
               AND (
                   (pm.meta_key = 'entry_type' AND pm.meta_value = 'AI Analysis')
                   OR pm.meta_key = 'analysis_data'
               )",
            $user_id,
            $start_date,
            $end_date
        ));

        $history = get_user_meta($user_id, 'myavana_hair_analysis_history', true);
        $history_count = 0;
        if (is_array($history)) {
            foreach ($history as $item) {
                $date_value = isset($item['date']) ? substr((string) $item['date'], 0, 10) : '';
                if ($date_value && $date_value >= $start_date && $date_value <= $end_date) {
                    $history_count++;
                }
            }
        }

        return max($post_count, $history_count);
    }

    private static function get_recent_rewards($user_id, $limit = 5) {
        global $wpdb;

        $table = $wpdb->prefix . 'myavana_points_history';
        $rewards = $wpdb->get_results($wpdb->prepare(
            "SELECT reason, points_change, reference_type, reference_id, created_at
             FROM {$table}
             WHERE user_id = %d
             ORDER BY created_at DESC, id DESC
             LIMIT %d",
            $user_id,
            $limit
        ), ARRAY_A);

        if (empty($rewards)) {
            return [];
        }

        return array_map(static function ($reward) {
            $timestamp = !empty($reward['created_at']) ? strtotime($reward['created_at']) : false;
            return [
                'reason' => $reward['reason'],
                'points_change' => intval($reward['points_change']),
                'reference_type' => $reward['reference_type'],
                'reference_id' => isset($reward['reference_id']) ? intval($reward['reference_id']) : 0,
                'created_at' => $reward['created_at'],
                'relative_time' => $timestamp ? human_time_diff($timestamp, current_time('timestamp')) . ' ago' : '',
            ];
        }, $rewards);
    }

    private static function get_active_challenges($user_id) {
        $challenges = self::get_challenge_definitions();
        $summary = self::get_user_challenge_metrics($user_id);
        $active = [];

        foreach ($challenges as $challenge) {
            if (($challenge['status'] ?? 'active') !== 'active') {
                continue;
            }
            if (!self::challenge_matches_audience($user_id, $challenge['audience'] ?? 'all')) {
                continue;
            }
            if (!self::challenge_is_in_window($challenge)) {
                continue;
            }

            $metric = $challenge['metric'] ?? 'entries_week';
            $current = intval($summary[$metric] ?? 0);
            $target = max(1, intval($challenge['target'] ?? 1));
            $start_date = self::sanitize_challenge_date($challenge['start_date'] ?? '');
            $end_date = self::sanitize_challenge_date($challenge['end_date'] ?? '');
            $window_bits = [];
            if ($start_date !== '') {
                $window_bits[] = wp_date('M j', strtotime($start_date));
            }
            if ($end_date !== '') {
                $window_bits[] = wp_date('M j', strtotime($end_date));
            }

            $active[] = [
                'id' => $challenge['id'],
                'title' => $challenge['title'],
                'description' => $challenge['description'],
                'metric' => $metric,
                'current' => min($target, $current),
                'target' => $target,
                'progress_percent' => min(100, intval(round(($current / $target) * 100))),
                'reward_points' => intval($challenge['reward_points'] ?? 0),
                'completed' => $current >= $target,
                'category' => sanitize_key($challenge['category'] ?? 'custom'),
                'start_date' => $start_date,
                'end_date' => $end_date,
                'window_label' => !empty($window_bits) ? implode(' - ', $window_bits) : '',
            ];
        }

        return $active;
    }

    private static function get_user_challenge_metrics($user_id) {
        $today = new DateTimeImmutable('now', wp_timezone());
        $week_start = $today->modify('monday this week')->format('Y-m-d');
        $week_end = $today->modify('sunday this week')->format('Y-m-d');
        $totals = self::sync_user_totals($user_id);

        return [
            'entries_week' => self::count_entries_between($user_id, $week_start, $week_end),
            'routines_week' => self::count_routine_completions_between($user_id, $week_start, $week_end),
            'ai_week' => self::count_ai_analyses_between($user_id, $week_start, $week_end),
            'community_posts_week' => self::count_community_posts_between($user_id, $week_start, $week_end),
            'community_posts_month' => self::count_community_posts_between($user_id, $today->modify('first day of this month')->format('Y-m-d'), $today->modify('last day of this month')->format('Y-m-d')),
            'video_entries_month' => self::count_video_entries_between($user_id, $today->modify('first day of this month')->format('Y-m-d'), $today->modify('last day of this month')->format('Y-m-d')),
            'goals_completed_total' => self::count_completed_goals($user_id),
            'current_streak' => self::calculate_entry_streak($user_id),
            'total_entries' => intval($totals['total_entries'] ?? 0),
            'total_ai_analyses' => intval($totals['total_ai_analyses'] ?? 0),
            'checkins_week' => self::count_checkins_between($user_id, $week_start, $week_end),
        ];
    }

    private static function maybe_award_completed_challenges($user_id) {
        $metrics = self::get_user_challenge_metrics($user_id);
        foreach (self::get_challenge_definitions() as $challenge) {
            if (($challenge['status'] ?? 'active') !== 'active') {
                continue;
            }
            if (!self::challenge_matches_audience($user_id, $challenge['audience'] ?? 'all')) {
                continue;
            }
            if (!self::challenge_is_in_window($challenge)) {
                continue;
            }

            $metric = $challenge['metric'] ?? 'entries_week';
            $target = max(1, intval($challenge['target'] ?? 1));
            $current = intval($metrics[$metric] ?? 0);
            if ($current < $target) {
                continue;
            }

            self::award_points(
                $user_id,
                max(0, intval($challenge['reward_points'] ?? 0)),
                'Challenge completed: ' . sanitize_text_field($challenge['title'] ?? 'Challenge'),
                'challenge',
                null,
                'challenge_completed:' . sanitize_key($challenge['id']),
                [
                    'challenge_id' => sanitize_key($challenge['id']),
                    'metric' => $metric,
                    'target' => $target,
                ]
            );
        }
    }

    private static function challenge_matches_audience($user_id, $audience) {
        $audience = sanitize_key((string) $audience);
        if ($audience === '' || $audience === 'all') {
            return true;
        }

        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }

        return in_array($audience, (array) $user->roles, true);
    }

    private static function sanitize_challenge_date($value) {
        $value = sanitize_text_field((string) $value);
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : '';
    }

    private static function challenge_is_in_window($challenge) {
        $today = current_time('Y-m-d');
        $start_date = self::sanitize_challenge_date($challenge['start_date'] ?? '');
        $end_date = self::sanitize_challenge_date($challenge['end_date'] ?? '');

        if ($start_date !== '' && $today < $start_date) {
            return false;
        }

        if ($end_date !== '' && $today > $end_date) {
            return false;
        }

        return true;
    }

    private static function count_checkins_between($user_id, $start_date, $end_date) {
        global $wpdb;

        $table = $wpdb->prefix . 'myavana_daily_checkins';
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*)
             FROM {$table}
             WHERE user_id = %d
               AND check_in_date BETWEEN %s AND %s",
            $user_id,
            $start_date,
            $end_date
        ));
    }

    private static function count_community_posts_between($user_id, $start_date, $end_date) {
        global $wpdb;

        $table = $wpdb->prefix . 'myavana_community_posts';
        $exists = (bool) $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));
        if (!$exists) {
            return 0;
        }

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*)
             FROM {$table}
             WHERE user_id = %d
               AND DATE(created_at) BETWEEN %s AND %s",
            $user_id,
            $start_date,
            $end_date
        ));
    }

    private static function count_video_entries_between($user_id, $start_date, $end_date) {
        global $wpdb;

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT p.ID)
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE p.post_author = %d
               AND p.post_type = 'hair_journey_entry'
               AND p.post_status = 'publish'
               AND DATE(p.post_date) BETWEEN %s AND %s
               AND pm.meta_key = '_entry_videos'
               AND pm.meta_value <> ''",
            $user_id,
            $start_date,
            $end_date
        ));
    }

    private static function count_completed_goals($user_id) {
        $goals = get_user_meta($user_id, 'myavana_hair_goals_structured', true);
        if (!is_array($goals)) {
            return 0;
        }

        $count = 0;
        foreach ($goals as $goal) {
            $progress = intval($goal['progress'] ?? ($goal['progress_percent'] ?? 0));
            $status = strtolower((string) ($goal['status'] ?? 'active'));
            if ($status === 'completed' || $progress >= 100) {
                $count++;
            }
        }

        return $count;
    }

    private static function get_badges_earned_count($user_id) {
        global $wpdb;
        $table_user_badges = $wpdb->prefix . 'myavana_user_badges';

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_user_badges} WHERE user_id = %d",
            $user_id
        ));
    }

    private static function get_recent_badges($user_id, $limit = 3) {
        global $wpdb;

        $table_user_badges = $wpdb->prefix . 'myavana_user_badges';
        $table_badges = $wpdb->prefix . 'myavana_badges';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT b.badge_key, b.name, b.description, b.rarity, b.points_reward, ub.earned_at
             FROM {$table_user_badges} ub
             INNER JOIN {$table_badges} b ON ub.badge_id = b.id
             WHERE ub.user_id = %d
             ORDER BY ub.earned_at DESC
             LIMIT %d",
            $user_id,
            $limit
        ), ARRAY_A);
    }

    private static function get_next_badge($user_id, $current_streak, $total_entries, $total_ai_analyses) {
        global $wpdb;

        $table_badges = $wpdb->prefix . 'myavana_badges';
        $table_user_badges = $wpdb->prefix . 'myavana_user_badges';

        $candidates = $wpdb->get_results($wpdb->prepare(
            "SELECT b.*
             FROM {$table_badges} b
             LEFT JOIN {$table_user_badges} ub
               ON ub.badge_id = b.id
              AND ub.user_id = %d
             WHERE ub.id IS NULL
             ORDER BY b.display_order ASC, b.requirement_value ASC, b.id ASC",
            $user_id
        ), ARRAY_A);

        foreach ($candidates as $badge) {
            $requirement = (int) ($badge['requirement_value'] ?? 0);
            $progress = null;

            switch ($badge['requirement_type']) {
                case 'streak':
                    $progress = $current_streak;
                    break;
                case 'count':
                case 'milestone':
                    if (($badge['category'] ?? '') === 'ai') {
                        $progress = $total_ai_analyses;
                    } elseif (($badge['category'] ?? '') !== 'community') {
                        $progress = $total_entries;
                    }
                    break;
            }

            if ($progress === null) {
                continue;
            }

            return [
                'badge_key' => $badge['badge_key'],
                'name' => $badge['name'],
                'description' => $badge['description'],
                'rarity' => $badge['rarity'],
                'progress' => max(0, min($requirement, (int) $progress)),
                'requirement_value' => $requirement,
                'remaining' => max(0, $requirement - (int) $progress),
            ];
        }

        return null;
    }

    private static function get_empty_summary() {
        return [
            'total_points' => 0,
            'current_streak' => 0,
            'longest_streak' => 0,
            'level' => 1,
            'current_level_points' => 0,
            'next_level_points' => self::LEVEL_STEP,
            'progress_to_next_level' => 0,
            'points_to_next_level' => self::LEVEL_STEP,
            'xp_progress_percent' => 0,
            'badges_earned' => 0,
            'recent_badges' => [],
            'next_badge' => null,
            'checked_in_today' => false,
            'total_entries' => 0,
            'total_ai_analyses' => 0,
            'daily_quests' => [],
            'weekly_quests' => [],
            'active_challenges' => [],
            'recent_rewards' => [],
        ];
    }
}

// Initialize
new Myavana_Gamification();

if (!function_exists('myavana_award_points')) {
    function myavana_award_points($user_id, $points, $reason, $reference_type = null, $reference_id = null, $event_key = '', $meta = []) {
        return Myavana_Gamification::award_points($user_id, $points, $reason, $reference_type, $reference_id, $event_key, $meta);
    }
}

if (!function_exists('myavana_get_gamification_summary')) {
    function myavana_get_gamification_summary($user_id) {
        return Myavana_Gamification::get_summary($user_id);
    }
}
