<?php
/**
 * Onboarding Database Schema Updates
 *
 * Adds necessary fields to track onboarding completion and store onboarding data
 *
 * @package Myavana_Hair_Journey
 * @version 2.3.6
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Update database schema for onboarding
 * Run this once to add onboarding tracking fields
 */
function myavana_update_onboarding_schema() {
    global $wpdb;

    $profiles_table = $wpdb->prefix . 'myavana_profiles';

    // Check if table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$profiles_table'") === $profiles_table;

    if (!$table_exists) {
        error_log('MYAVANA Onboarding: Profiles table does not exist');
        return false;
    }

    // Add onboarding columns if they don't exist
    $columns_to_add = [
        'onboarding_completed' => "ALTER TABLE {$profiles_table} ADD COLUMN onboarding_completed TINYINT(1) DEFAULT 0",
        'onboarding_step' => "ALTER TABLE {$profiles_table} ADD COLUMN onboarding_step INT DEFAULT 0",
        'onboarding_data' => "ALTER TABLE {$profiles_table} ADD COLUMN onboarding_data LONGTEXT NULL",
        'onboarding_date' => "ALTER TABLE {$profiles_table} ADD COLUMN onboarding_date DATETIME NULL"
    ];

    foreach ($columns_to_add as $column => $sql) {
        // Check if column exists
        $column_exists = $wpdb->get_var("
            SELECT COLUMN_NAME
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = '{$profiles_table}'
            AND COLUMN_NAME = '{$column}'
        ");

        if (!$column_exists) {
            $result = $wpdb->query($sql);
            if ($result === false) {
                error_log("MYAVANA Onboarding: Failed to add column {$column}: " . $wpdb->last_error);
            } else {
                error_log("MYAVANA Onboarding: Added column {$column} successfully");
            }
        }
    }

    return true;
}

/**
 * Check if user has completed onboarding
 *
 * @param int $user_id WordPress user ID
 * @return bool
 */
function myavana_user_completed_onboarding($user_id) {
    global $wpdb;
    $profiles_table = $wpdb->prefix . 'myavana_profiles';

    $completed = $wpdb->get_var($wpdb->prepare(
        "SELECT onboarding_completed FROM {$profiles_table} WHERE user_id = %d",
        $user_id
    ));

    return (bool) $completed;
}

/**
 * Mark onboarding as completed for user
 *
 * @param int $user_id WordPress user ID
 * @param array $onboarding_data Complete onboarding data
 * @return bool
 */
function myavana_mark_onboarding_complete($user_id, $onboarding_data = []) {
    global $wpdb;
    $profiles_table = $wpdb->prefix . 'myavana_profiles';

    $result = $wpdb->update(
        $profiles_table,
        [
            'onboarding_completed' => 1,
            'onboarding_step' => 3,
            'onboarding_data' => json_encode($onboarding_data),
            'onboarding_date' => current_time('mysql')
        ],
        ['user_id' => $user_id],
        ['%d', '%d', '%s', '%s'],
        ['%d']
    );

    // If user doesn't have a profile yet, create one
    if ($result === false || $result === 0) {
        $wpdb->insert(
            $profiles_table,
            [
                'user_id' => $user_id,
                'onboarding_completed' => 1,
                'onboarding_step' => 3,
                'onboarding_data' => json_encode($onboarding_data),
                'onboarding_date' => current_time('mysql')
            ],
            ['%d', '%d', '%d', '%s', '%s']
        );
    }

    return true;
}

/**
 * Save onboarding progress
 *
 * @param int $user_id WordPress user ID
 * @param int $step Current step (1, 2, or 3)
 * @param array $step_data Data for this step
 * @return bool
 */
function myavana_save_onboarding_progress($user_id, $step, $step_data) {
    global $wpdb;
    $profiles_table = $wpdb->prefix . 'myavana_profiles';

    // Get existing data
    $existing_data = $wpdb->get_var($wpdb->prepare(
        "SELECT onboarding_data FROM {$profiles_table} WHERE user_id = %d",
        $user_id
    ));

    $all_data = $existing_data ? json_decode($existing_data, true) : [];
    $all_data["step_{$step}"] = $step_data;

    $result = $wpdb->update(
        $profiles_table,
        [
            'onboarding_step' => $step,
            'onboarding_data' => json_encode($all_data)
        ],
        ['user_id' => $user_id],
        ['%d', '%s'],
        ['%d']
    );

    // If user doesn't have a profile yet, create one
    if ($result === false || $result === 0) {
        $wpdb->insert(
            $profiles_table,
            [
                'user_id' => $user_id,
                'onboarding_step' => $step,
                'onboarding_data' => json_encode($all_data)
            ],
            ['%d', '%d', '%s']
        );
    }

    return true;
}

/**
 * Reset onboarding for testing purposes
 *
 * @param int $user_id WordPress user ID
 * @return bool
 */
function myavana_reset_onboarding($user_id) {
    global $wpdb;
    $profiles_table = $wpdb->prefix . 'myavana_profiles';

    return $wpdb->update(
        $profiles_table,
        [
            'onboarding_completed' => 0,
            'onboarding_step' => 0,
            'onboarding_data' => null,
            'onboarding_date' => null
        ],
        ['user_id' => $user_id],
        ['%d', '%d', '%s', '%s'],
        ['%d']
    );
}

// Run schema update on plugin activation/update
add_action('admin_init', 'myavana_update_onboarding_schema');
