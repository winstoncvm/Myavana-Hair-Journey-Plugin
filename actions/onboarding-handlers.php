<?php
/**
 * MYAVANA Onboarding AJAX Handlers
 *
 * Handles the 3-step onboarding process:
 * - Step 1: Hair Profile (hair type, concerns, length)
 * - Step 2: Goals (user's hair goals with meta fields)
 * - Step 3: Routine (initial hair care routine)
 *
 * @package Myavana_Hair_Journey
 * @version 2.3.6
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once MYAVANA_PATH . 'includes/onboarding-db-schema.php';

/**
 * Save Onboarding Step 1: Hair Profile
 * Saves to user profile meta
 */
function myavana_save_onboarding_step_1() {
    // Verify nonce
    $nonce_valid = false;
    if (isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], 'myavana_nonce')) {
        $nonce_valid = true;
    } elseif (isset($_POST['security']) && wp_verify_nonce($_POST['security'], 'myavana_onboarding')) {
        $nonce_valid = true;
    }

    if (!$nonce_valid) {
        wp_send_json_error('Invalid security token');
        return;
    }

    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error('User not logged in');
        return;
    }

    // Get and sanitize data
    $hair_type = isset($_POST['hairType']) ? sanitize_text_field($_POST['hairType']) : '';
    $concerns = isset($_POST['concerns']) ? array_map('sanitize_text_field', $_POST['concerns']) : [];
    $hair_length = isset($_POST['hairLength']) ? floatval($_POST['hairLength']) : 0;
    $additional_notes = isset($_POST['additionalNotes']) ? sanitize_textarea_field($_POST['additionalNotes']) : '';

    if (empty($hair_type)) {
        wp_send_json_error('Hair type is required');
        return;
    }

    // Save to user profile meta
    update_user_meta($user_id, 'myavana_hair_type', $hair_type);
    update_user_meta($user_id, 'myavana_hair_concerns', $concerns);
    update_user_meta($user_id, 'myavana_hair_length', $hair_length);

    if (!empty($additional_notes)) {
        update_user_meta($user_id, 'myavana_profile_notes', $additional_notes);
    }

    // Save step data to onboarding progress
    $step_data = [
        'hair_type' => $hair_type,
        'concerns' => $concerns,
        'hair_length' => $hair_length,
        'additional_notes' => $additional_notes,
        'completed_at' => current_time('mysql')
    ];

    myavana_save_onboarding_progress($user_id, 1, $step_data);

    wp_send_json_success([
        'message' => 'Hair profile saved successfully',
        'step' => 1,
        'data' => $step_data
    ]);
}
add_action('wp_ajax_myavana_save_onboarding_step_1', 'myavana_save_onboarding_step_1');

/**
 * Save Onboarding Step 2: Hair Goals
 * Saves goals with proper meta structure matching existing goals system
 */
function myavana_save_onboarding_step_2() {
    // Verify nonce
    $nonce_valid = false;
    if (isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], 'myavana_nonce')) {
        $nonce_valid = true;
    } elseif (isset($_POST['security']) && wp_verify_nonce($_POST['security'], 'myavana_onboarding')) {
        $nonce_valid = true;
    }

    if (!$nonce_valid) {
        wp_send_json_error('Invalid security token');
        return;
    }

    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error('User not logged in');
        return;
    }

    // Get goals data
    $goals = isset($_POST['goals']) ? $_POST['goals'] : [];

    if (empty($goals)) {
        wp_send_json_error('At least one goal is required');
        return;
    }

    // Format goals with proper structure
    $formatted_goals = [];
    foreach ($goals as $index => $goal) {
        $formatted_goals[] = [
            'id' => uniqid('goal_'),
            'title' => sanitize_text_field($goal['title']),
            'category' => sanitize_text_field($goal['category']),
            'start_date' => sanitize_text_field($goal['startDate']),
            'target_date' => sanitize_text_field($goal['targetDate']),
            'description' => sanitize_textarea_field($goal['description']),
            'progress' => 0,
            'status' => 'active',
            'created_at' => current_time('mysql')
        ];
    }

    // Save to user meta using the existing structure
    update_user_meta($user_id, 'myavana_hair_goals_structured', $formatted_goals);

    // Save step data to onboarding progress
    $step_data = [
        'goals' => $formatted_goals,
        'completed_at' => current_time('mysql')
    ];

    myavana_save_onboarding_progress($user_id, 2, $step_data);

    wp_send_json_success([
        'message' => 'Goals saved successfully',
        'step' => 2,
        'goals_count' => count($formatted_goals),
        'data' => $step_data
    ]);
}
add_action('wp_ajax_myavana_save_onboarding_step_2', 'myavana_save_onboarding_step_2');

/**
 * Save Onboarding Step 3: Routine
 * Saves routine with proper meta structure matching existing routines system
 */
function myavana_save_onboarding_step_3() {
    // Verify nonce
    $nonce_valid = false;
    if (isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], 'myavana_nonce')) {
        $nonce_valid = true;
    } elseif (isset($_POST['security']) && wp_verify_nonce($_POST['security'], 'myavana_onboarding')) {
        $nonce_valid = true;
    }

    if (!$nonce_valid) {
        wp_send_json_error('Invalid security token');
        return;
    }

    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error('User not logged in');
        return;
    }

    // Check if skipped
    $skipped = isset($_POST['skipped']) && $_POST['skipped'] === 'true';

    $step_data = ['skipped' => $skipped];

    if (!$skipped) {
        // Get routine data
        $routine_name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $frequency = isset($_POST['frequency']) ? sanitize_text_field($_POST['frequency']) : '';
        $duration = isset($_POST['duration']) ? intval($_POST['duration']) : 0;
        $time_of_day = isset($_POST['timeOfDay']) ? sanitize_text_field($_POST['timeOfDay']) : '';
        $steps = isset($_POST['steps']) ? array_map('sanitize_text_field', $_POST['steps']) : [];

        if (empty($routine_name) || empty($frequency)) {
            wp_send_json_error('Routine name and frequency are required');
            return;
        }

        // Get existing routines
        $existing_routines = get_user_meta($user_id, 'myavana_current_routine', true) ?: [];
        if (!is_array($existing_routines)) {
            $existing_routines = [];
        }

        // Format routine with proper structure
        $new_routine = [
            'id' => uniqid('routine_'),
            'name' => $routine_name,
            'frequency' => $frequency,
            'duration' => $duration,
            'time_of_day' => $time_of_day,
            'steps' => $steps,
            'active' => true,
            'created_at' => current_time('mysql'),
            'is_onboarding' => true
        ];

        // Add to routines array
        $existing_routines[] = $new_routine;

        // Save to user meta using the existing structure
        update_user_meta($user_id, 'myavana_current_routine', $existing_routines);

        $step_data['routine'] = $new_routine;
    }

    $step_data['completed_at'] = current_time('mysql');

    myavana_save_onboarding_progress($user_id, 3, $step_data);

    // Mark onboarding as complete
    myavana_mark_onboarding_complete($user_id, [
        'completed_at' => current_time('mysql'),
        'skipped_routine' => $skipped
    ]);

    // Award gamification points
    if (class_exists('Myavana_Gamification')) {
        global $wpdb;
        $stats_table = $wpdb->prefix . 'myavana_user_stats';

        // Add 50 points for completing onboarding
        $wpdb->query($wpdb->prepare(
            "INSERT INTO {$stats_table} (user_id, total_points, level)
             VALUES (%d, 50, 1)
             ON DUPLICATE KEY UPDATE total_points = total_points + 50",
            $user_id
        ));
    }

    wp_send_json_success([
        'message' => 'Onboarding completed successfully!',
        'step' => 3,
        'points_earned' => 50,
        'skipped_routine' => $skipped,
        'redirect' => home_url('/hair-journey/'), // Adjust redirect URL as needed
        'data' => $step_data
    ]);
}
add_action('wp_ajax_myavana_save_onboarding_step_3', 'myavana_save_onboarding_step_3');

/**
 * Get onboarding progress
 */
function myavana_get_onboarding_progress() {
    // Verify nonce
    $nonce_valid = false;
    if (isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], 'myavana_nonce')) {
        $nonce_valid = true;
    } elseif (isset($_POST['security']) && wp_verify_nonce($_POST['security'], 'myavana_onboarding')) {
        $nonce_valid = true;
    }

    if (!$nonce_valid) {
        wp_send_json_error('Invalid security token');
        return;
    }

    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error('User not logged in');
        return;
    }

    global $wpdb;
    $profiles_table = $wpdb->prefix . 'myavana_profiles';

    $progress = $wpdb->get_row($wpdb->prepare(
        "SELECT onboarding_completed, onboarding_step, onboarding_data
         FROM {$profiles_table}
         WHERE user_id = %d",
        $user_id
    ), ARRAY_A);

    if (!$progress) {
        $progress = [
            'onboarding_completed' => false,
            'onboarding_step' => 0,
            'onboarding_data' => null
        ];
    } else {
        $progress['onboarding_data'] = $progress['onboarding_data'] ? json_decode($progress['onboarding_data'], true) : null;
    }

    wp_send_json_success($progress);
}
add_action('wp_ajax_myavana_get_onboarding_progress', 'myavana_get_onboarding_progress');

/**
 * Reset onboarding (for testing)
 */
function myavana_reset_onboarding_handler() {
    // Verify nonce
    $nonce_valid = false;
    if (isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], 'myavana_nonce')) {
        $nonce_valid = true;
    } elseif (isset($_POST['security']) && wp_verify_nonce($_POST['security'], 'myavana_onboarding')) {
        $nonce_valid = true;
    }

    if (!$nonce_valid) {
        wp_send_json_error('Invalid security token');
        return;
    }

    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error('User not logged in');
        return;
    }

    // Only allow in development/debug mode
    if (!defined('WP_DEBUG') || !WP_DEBUG) {
        wp_send_json_error('Reset only available in debug mode');
        return;
    }

    myavana_reset_onboarding($user_id);

    wp_send_json_success([
        'message' => 'Onboarding reset successfully',
        'user_id' => $user_id
    ]);
}
add_action('wp_ajax_myavana_reset_onboarding', 'myavana_reset_onboarding_handler');
