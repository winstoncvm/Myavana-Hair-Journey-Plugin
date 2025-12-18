<?php
/**
 * Profile Privacy Management
 * Handles user profile visibility settings
 */

if (!defined('ABSPATH')) {
    exit;
}

class Myavana_Profile_Privacy {

    public function __construct() {
        // Add privacy column to profiles table
        add_action('init', array($this, 'update_profiles_table'));

        // AJAX handlers
        add_action('wp_ajax_update_profile_privacy', array($this, 'update_profile_privacy'));
        add_action('wp_ajax_get_profile_privacy_settings', array($this, 'get_profile_privacy_settings'));
    }

    /**
     * Update profiles table to include privacy settings
     */
    public function update_profiles_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'myavana_profiles';

        // Check if column exists
        $column_exists = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = %s
                AND TABLE_NAME = %s
                AND COLUMN_NAME = 'profile_visibility'",
                DB_NAME,
                $table_name
            )
        );

        // Add column if it doesn't exist
        if (empty($column_exists)) {
            $wpdb->query(
                "ALTER TABLE $table_name
                ADD COLUMN profile_visibility VARCHAR(20) DEFAULT 'private' AFTER hair_goals,
                ADD COLUMN show_hair_stats TINYINT(1) DEFAULT 1,
                ADD COLUMN show_journey_entries TINYINT(1) DEFAULT 0,
                ADD COLUMN show_goals TINYINT(1) DEFAULT 0,
                ADD COLUMN show_routine TINYINT(1) DEFAULT 0,
                ADD COLUMN allow_messages TINYINT(1) DEFAULT 0"
            );
        }
    }

    /**
     * Get user's privacy settings
     */
    public static function get_privacy_settings($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'myavana_profiles';

        $settings = $wpdb->get_row($wpdb->prepare(
            "SELECT profile_visibility, show_hair_stats, show_journey_entries,
                    show_goals, show_routine, allow_messages
             FROM $table_name
             WHERE user_id = %d",
            $user_id
        ));

        // Default values if no settings exist
        if (!$settings) {
            return array(
                'profile_visibility' => 'private',
                'show_hair_stats' => 1,
                'show_journey_entries' => 0,
                'show_goals' => 0,
                'show_routine' => 0,
                'allow_messages' => 0
            );
        }

        return (array) $settings;
    }

    /**
     * Check if a profile is viewable by current user
     */
    public static function can_view_profile($profile_user_id, $viewer_user_id = null) {
        if ($viewer_user_id === null) {
            $viewer_user_id = get_current_user_id();
        }

        // Users can always view their own profile
        if ($profile_user_id == $viewer_user_id) {
            return true;
        }

        $settings = self::get_privacy_settings($profile_user_id);

        // Check profile visibility
        switch ($settings['profile_visibility']) {
            case 'public':
                return true;

            case 'followers':
                // Check if viewer follows this user
                return self::is_following($viewer_user_id, $profile_user_id);

            case 'private':
            default:
                return false;
        }
    }

    /**
     * Check if viewer is following profile user
     */
    private static function is_following($follower_id, $following_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'myavana_user_followers';

        $is_following = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table
             WHERE follower_id = %d AND following_id = %d",
            $follower_id,
            $following_id
        ));

        return $is_following > 0;
    }

    /**
     * AJAX: Update profile privacy settings
     */
    public function update_profile_privacy() {
        if (!wp_verify_nonce($_POST['nonce'], 'myavana_nonce')) {
            wp_send_json_error('Security check failed');
            return;
        }

        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error('Not logged in');
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'myavana_profiles';

        // Sanitize inputs
        $profile_visibility = sanitize_text_field($_POST['profile_visibility'] ?? 'private');
        $show_hair_stats = intval($_POST['show_hair_stats'] ?? 1);
        $show_journey_entries = intval($_POST['show_journey_entries'] ?? 0);
        $show_goals = intval($_POST['show_goals'] ?? 0);
        $show_routine = intval($_POST['show_routine'] ?? 0);
        $allow_messages = intval($_POST['allow_messages'] ?? 0);

        // Validate visibility value
        $allowed_values = array('private', 'public', 'followers');
        if (!in_array($profile_visibility, $allowed_values)) {
            $profile_visibility = 'private';
        }

        // Update settings
        $result = $wpdb->update(
            $table_name,
            array(
                'profile_visibility' => $profile_visibility,
                'show_hair_stats' => $show_hair_stats,
                'show_journey_entries' => $show_journey_entries,
                'show_goals' => $show_goals,
                'show_routine' => $show_routine,
                'allow_messages' => $allow_messages
            ),
            array('user_id' => $user_id),
            array('%s', '%d', '%d', '%d', '%d', '%d'),
            array('%d')
        );

        if ($result !== false) {
            // Clear user cache
            Myavana_Data_Manager::clear_user_cache($user_id);

            wp_send_json_success(array(
                'message' => 'Privacy settings updated successfully',
                'settings' => self::get_privacy_settings($user_id)
            ));
        } else {
            wp_send_json_error('Failed to update privacy settings');
        }
    }

    /**
     * AJAX: Get profile privacy settings
     */
    public function get_profile_privacy_settings() {
        if (!wp_verify_nonce($_POST['nonce'], 'myavana_nonce')) {
            wp_send_json_error('Security check failed');
            return;
        }

        $user_id = intval($_POST['user_id'] ?? get_current_user_id());
        $current_user_id = get_current_user_id();

        // Only return settings if viewing own profile
        if ($user_id !== $current_user_id) {
            wp_send_json_error('Unauthorized');
            return;
        }

        $settings = self::get_privacy_settings($user_id);
        wp_send_json_success($settings);
    }
}

// Initialize
new Myavana_Profile_Privacy();
