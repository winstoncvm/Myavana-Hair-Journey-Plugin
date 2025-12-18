<?php
/**
 * MYAVANA Goal and Routine Update Handlers
 * Handles CRUD operations for hair goals and routines
 * @version 2.3.8
 */

if (!defined('ABSPATH')) exit;

/**
 * Update existing goal
 */
function myavana_update_goal() {
    // Verify nonce
    $nonce_verified = false;
    if (isset($_POST['security'])) {
        $nonce_verified = wp_verify_nonce($_POST['security'], 'myavana_update_goal');
    } elseif (isset($_POST['myavana_nonce'])) {
        $nonce_verified = wp_verify_nonce($_POST['myavana_nonce'], 'myavana_add_goal');
    }

    if (!$nonce_verified) {
        wp_send_json_error('Security check failed');
        return;
    }

    $user_id = get_current_user_id();
    $goal_index = isset($_POST['goal_id']) ? intval($_POST['goal_id']) : -1;

    error_log(sprintf('[MYAVANA] update_goal: goal_index=%d, user_id=%d', $goal_index, $user_id));

    if (!$user_id || $goal_index < 0) {
        wp_send_json_error('Invalid request');
        return;
    }

    // Goals are stored in user meta, not as posts
    $goals = get_user_meta($user_id, 'myavana_hair_goals_structured', true);
    if (!is_array($goals)) {
        $goals = [];
    }

    if (!isset($goals[$goal_index])) {
        error_log(sprintf('[MYAVANA] update_goal: Goal index %d not found in user goals', $goal_index));
        wp_send_json_error('Goal not found or access denied');
        return;
    }

    // Validate required fields
    if (empty($_POST['title'])) {
        wp_send_json_error('Title is required');
        return;
    }

    // Sanitize input data
    $title = sanitize_text_field($_POST['title']);
    $description = sanitize_textarea_field($_POST['description']);
    $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'active';
    $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : '';
    $target_date = isset($_POST['target_date']) ? sanitize_text_field($_POST['target_date']) : '';
    $progress = isset($_POST['progress']) ? max(0, min(100, intval($_POST['progress']))) : 0;

    // Update the goal in the array
    $goals[$goal_index] = array_merge($goals[$goal_index], [
        'title' => $title,
        'goal_title' => $title,
        'description' => $description,
        'notes' => $description,
        'status' => $status,
        'start_date' => $start_date,
        'start' => $start_date,
        'target_date' => $target_date,
        'end_date' => $target_date,
        'end' => $target_date,
        'progress' => $progress,
        'progress_percent' => $progress,
        'updated_at' => current_time('mysql')
    ]);

    // Save back to user meta
    $updated = update_user_meta($user_id, 'myavana_hair_goals_structured', $goals);

    if ($updated === false) {
        error_log(sprintf('[MYAVANA] update_goal: Failed to update user meta for user %d', $user_id));
        wp_send_json_error('Failed to update goal');
        return;
    }

    error_log(sprintf('[MYAVANA] Goal updated: Index=%d, User=%d, Title=%s', $goal_index, $user_id, $title));

    wp_send_json_success([
        'message' => 'Goal updated successfully!',
        'goal_id' => $goal_index,
        'goal' => $goals[$goal_index]
    ]);
}
add_action('wp_ajax_myavana_update_goal', 'myavana_update_goal');

/**
 * Get goal details for editing
 */
function myavana_get_goal_details() {
    // Flexible nonce verification - accept multiple nonce types
    $nonce_verified = false;
    if (isset($_POST['security'])) {
        // Try multiple nonce actions that might be used
        $nonce_verified = wp_verify_nonce($_POST['security'], 'myavana_get_goal_details') ||
                         wp_verify_nonce($_POST['security'], 'myavana_get_entry_details') ||
                         wp_verify_nonce($_POST['security'], 'myavana-timeline-nonce') ||
                         wp_verify_nonce($_POST['security'], 'myavana_timeline_nonce');
    }

    if (!$nonce_verified) {
        error_log('[MYAVANA] get_goal_details nonce verification failed');
        wp_send_json_error('Security check failed');
        return;
    }

    $goal_index = isset($_POST['goal_id']) ? intval($_POST['goal_id']) : -1;
    $user_id = get_current_user_id();

    error_log(sprintf('[MYAVANA] get_goal_details: goal_index=%d, user_id=%d', $goal_index, $user_id));

    if ($goal_index < 0) {
        error_log('[MYAVANA] get_goal_details: Invalid goal index');
        wp_send_json_error('Invalid goal ID');
        return;
    }

    // Goals are stored in user meta, not as posts
    $goals = get_user_meta($user_id, 'myavana_hair_goals_structured', true);
    if (!is_array($goals)) {
        $goals = [];
    }

    error_log(sprintf('[MYAVANA] get_goal_details: Found %d goals in user meta', count($goals)));

    if (!isset($goals[$goal_index])) {
        error_log(sprintf('[MYAVANA] get_goal_details: Goal index %d not found in user goals', $goal_index));
        wp_send_json_error('Goal not found or access denied');
        return;
    }

    $goal = $goals[$goal_index];
    error_log(sprintf('[MYAVANA] get_goal_details: Found goal at index %d: %s', $goal_index, json_encode($goal)));

    // Prepare goal data for response
    $goal_data = [
        'id' => $goal_index,
        'goal_id' => $goal_index,
        'title' => $goal['title'] ?? $goal['goal_title'] ?? 'Untitled Goal',
        'description' => $goal['description'] ?? $goal['notes'] ?? '',
        'start_date' => $goal['start_date'] ?? $goal['start'] ?? '',
        'target_date' => $goal['end_date'] ?? $goal['end'] ?? $goal['target_date'] ?? '',
        'status' => $goal['status'] ?? 'active',
        'progress' => isset($goal['progress']) ? intval($goal['progress']) : (isset($goal['progress_percent']) ? intval($goal['progress_percent']) : 0)
    ];

    error_log(sprintf('[MYAVANA] get_goal_details: Returning goal data: %s', json_encode($goal_data)));
    wp_send_json_success($goal_data);
}
add_action('wp_ajax_myavana_get_goal_details', 'myavana_get_goal_details');

/**
 * Update existing routine
 */
function myavana_update_routine() {
    // Verify nonce
    $nonce_verified = false;
    if (isset($_POST['security'])) {
        $nonce_verified = wp_verify_nonce($_POST['security'], 'myavana_update_routine');
    } elseif (isset($_POST['myavana_nonce'])) {
        $nonce_verified = wp_verify_nonce($_POST['myavana_nonce'], 'myavana_add_routine');
    }

    if (!$nonce_verified) {
        wp_send_json_error('Security check failed');
        return;
    }

    $user_id = get_current_user_id();
    $routine_index = isset($_POST['routine_id']) ? intval($_POST['routine_id']) : -1;

    error_log(sprintf('[MYAVANA] update_routine: routine_index=%d, user_id=%d', $routine_index, $user_id));

    if (!$user_id || $routine_index < 0) {
        wp_send_json_error('Invalid request');
        return;
    }

    // Routines are stored in user meta, not as posts
    $routines = get_user_meta($user_id, 'myavana_current_routine', true);
    if (!is_array($routines)) {
        $routines = [];
    }

    if (!isset($routines[$routine_index])) {
        error_log(sprintf('[MYAVANA] update_routine: Routine index %d not found in user routines', $routine_index));
        wp_send_json_error('Routine not found or access denied');
        return;
    }

    // Validate required fields
    if (empty($_POST['title'])) {
        wp_send_json_error('Title is required');
        return;
    }

    // Sanitize input data
    $title = sanitize_text_field($_POST['title']);
    $description = sanitize_textarea_field($_POST['description']);
    $frequency = isset($_POST['frequency']) ? sanitize_text_field($_POST['frequency']) : 'Weekly';
    $products = isset($_POST['products']) ? sanitize_text_field($_POST['products']) : '';
    $duration = isset($_POST['duration']) ? sanitize_text_field($_POST['duration']) : '';

    // Update the routine in the array
    $routines[$routine_index] = array_merge($routines[$routine_index], [
        'title' => $title,
        'routine_title' => $title,
        'description' => $description,
        'steps' => $description,
        'frequency' => $frequency,
        'products' => $products,
        'duration' => $duration,
        'updated_at' => current_time('mysql')
    ]);

    // Save back to user meta
    $updated = update_user_meta($user_id, 'myavana_current_routine', $routines);

    if ($updated === false) {
        error_log(sprintf('[MYAVANA] update_routine: Failed to update user meta for user %d', $user_id));
        wp_send_json_error('Failed to update routine');
        return;
    }

    error_log(sprintf('[MYAVANA] Routine updated: Index=%d, User=%d, Title=%s', $routine_index, $user_id, $title));

    wp_send_json_success([
        'message' => 'Routine updated successfully!',
        'routine_id' => $routine_index,
        'routine' => $routines[$routine_index]
    ]);
}
add_action('wp_ajax_myavana_update_routine', 'myavana_update_routine');

/**
 * Get routine details for editing
 */
function myavana_get_routine_details() {
    // Flexible nonce verification - accept multiple nonce types
    $nonce_verified = false;
    if (isset($_POST['security'])) {
        // Try multiple nonce actions that might be used
        $nonce_verified = wp_verify_nonce($_POST['security'], 'myavana_get_routine_details') ||
                         wp_verify_nonce($_POST['security'], 'myavana_get_entry_details') ||
                         wp_verify_nonce($_POST['security'], 'myavana-timeline-nonce') ||
                         wp_verify_nonce($_POST['security'], 'myavana_timeline_nonce');
    }

    if (!$nonce_verified) {
        error_log('[MYAVANA] get_routine_details nonce verification failed');
        wp_send_json_error('Security check failed');
        return;
    }

    $routine_index = isset($_POST['routine_id']) ? intval($_POST['routine_id']) : -1;
    $user_id = get_current_user_id();

    error_log(sprintf('[MYAVANA] get_routine_details: routine_index=%d, user_id=%d', $routine_index, $user_id));

    if (!$user_id || $routine_index < 0) {
        wp_send_json_error('Invalid routine ID');
        return;
    }

    // Routines are stored in user meta, not as posts
    $routines = get_user_meta($user_id, 'myavana_current_routine', true);
    if (!is_array($routines)) {
        $routines = [];
    }

    if (!isset($routines[$routine_index])) {
        error_log(sprintf('[MYAVANA] get_routine_details: Routine index %d not found in user routines', $routine_index));
        wp_send_json_error('Routine not found or access denied');
        return;
    }

    $routine = $routines[$routine_index];

    // Gather routine data
    $routine_data = [
        'id' => $routine_index,
        'title' => $routine['title'] ?? $routine['routine_title'] ?? 'Untitled Routine',
        'description' => $routine['description'] ?? $routine['steps'] ?? '',
        'frequency' => $routine['frequency'] ?? 'Weekly',
        'products' => $routine['products'] ?? '',
        'duration' => $routine['duration'] ?? ''
    ];

    error_log(sprintf('[MYAVANA] get_routine_details: Returning routine data: %s', json_encode($routine_data)));
    wp_send_json_success($routine_data);
}
add_action('wp_ajax_myavana_get_routine_details', 'myavana_get_routine_details');

/**
 * Add new goal
 */
function myavana_add_goal() {
    // Verify nonce
    $nonce_verified = false;
    if (isset($_POST['security'])) {
        $nonce_verified = wp_verify_nonce($_POST['security'], 'myavana_add_goal');
    }

    if (!$nonce_verified) {
        wp_send_json_error('Security check failed');
        return;
    }

    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error('User not logged in');
        return;
    }

    // Validate required fields
    if (empty($_POST['goal_title'])) {
        wp_send_json_error('Goal title is required');
        return;
    }

    if (empty($_POST['goal_description'])) {
        wp_send_json_error('Description is required');
        return;
    }

    if (empty($_POST['target_date'])) {
        wp_send_json_error('Target date is required');
        return;
    }

    // Sanitize input data
    $title = sanitize_text_field($_POST['goal_title']);
    $description = sanitize_textarea_field($_POST['goal_description']);
    $target_date = sanitize_text_field($_POST['target_date']);
    $priority = isset($_POST['priority']) ? sanitize_text_field($_POST['priority']) : 'Medium';

    // Get existing goals
    $goals = get_user_meta($user_id, 'myavana_hair_goals_structured', true);
    if (!is_array($goals)) {
        $goals = [];
    }

    // Create new goal
    $new_goal = [
        'title' => $title,
        'goal_title' => $title,
        'description' => $description,
        'notes' => $description,
        'start_date' => current_time('Y-m-d'),
        'start' => current_time('Y-m-d'),
        'target_date' => $target_date,
        'end_date' => $target_date,
        'end' => $target_date,
        'priority' => $priority,
        'status' => 'active',
        'progress' => 0,
        'progress_percent' => 0,
        'created_at' => current_time('mysql'),
        'updated_at' => current_time('mysql')
    ];

    // Add to goals array
    $goals[] = $new_goal;

    // Save to user meta
    $updated = update_user_meta($user_id, 'myavana_hair_goals_structured', $goals);

    if ($updated === false) {
        wp_send_json_error('Failed to save goal');
        return;
    }

    error_log(sprintf('[MYAVANA] Goal added: User=%d, Title=%s', $user_id, $title));

    wp_send_json_success([
        'message' => 'Goal added successfully!',
        'goal_id' => count($goals) - 1,
        'goal' => $new_goal
    ]);
}
add_action('wp_ajax_myavana_add_goal', 'myavana_add_goal');

/**
 * Add new routine
 */
function myavana_add_routine() {
    // Verify nonce
    $nonce_verified = false;
    if (isset($_POST['security'])) {
        $nonce_verified = wp_verify_nonce($_POST['security'], 'myavana_add_routine');
    }

    if (!$nonce_verified) {
        wp_send_json_error('Security check failed');
        return;
    }

    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error('User not logged in');
        return;
    }

    // Validate required fields
    if (empty($_POST['routine_name'])) {
        wp_send_json_error('Routine name is required');
        return;
    }

    if (empty($_POST['frequency'])) {
        wp_send_json_error('Frequency is required');
        return;
    }

    // Sanitize input data
    $name = sanitize_text_field($_POST['routine_name']);
    $frequency = sanitize_text_field($_POST['frequency']);
    $notes = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';
    $steps = isset($_POST['steps']) ? json_decode(stripslashes($_POST['steps']), true) : [];

    // Get existing routines
    $routines = get_user_meta($user_id, 'myavana_current_routine', true);
    if (!is_array($routines)) {
        $routines = [];
    }

    // Create new routine
    $new_routine = [
        'title' => $name,
        'routine_title' => $name,
        'description' => $notes,
        'notes' => $notes,
        'frequency' => $frequency,
        'steps' => $steps,
        'created_at' => current_time('mysql'),
        'updated_at' => current_time('mysql')
    ];

    // Add to routines array
    $routines[] = $new_routine;

    // Save to user meta
    $updated = update_user_meta($user_id, 'myavana_current_routine', $routines);

    if ($updated === false) {
        wp_send_json_error('Failed to save routine');
        return;
    }

    error_log(sprintf('[MYAVANA] Routine added: User=%d, Name=%s', $user_id, $name));

    wp_send_json_success([
        'message' => 'Routine added successfully!',
        'routine_id' => count($routines) - 1,
        'routine' => $new_routine
    ]);
}
add_action('wp_ajax_myavana_add_routine', 'myavana_add_routine');

/**
 * Delete a goal
 */
function myavana_delete_goal() {
    // Verify nonce - be more flexible with verification
    $nonce_verified = false;
    if (isset($_POST['security'])) {
        // Try multiple nonce actions
        $nonce_verified = wp_verify_nonce($_POST['security'], 'myavana_delete_goal') ||
                         wp_verify_nonce($_POST['security'], 'myavana_nonce') ||
                         wp_verify_nonce($_POST['security'], 'myavana_add_goal');
    }

    if (!$nonce_verified) {
        error_log('[MYAVANA] Delete goal nonce verification failed. Nonce: ' . (isset($_POST['security']) ? substr($_POST['security'], 0, 10) . '...' : 'not set'));
        wp_send_json_error('Security check failed');
        return;
    }

    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error('User not logged in');
        return;
    }

    // Validate goal_id
    if (!isset($_POST['goal_id']) || $_POST['goal_id'] === '') {
        wp_send_json_error('Goal ID is required');
        return;
    }

    $goal_id = intval($_POST['goal_id']);

    // Get existing goals
    $goals = get_user_meta($user_id, 'myavana_hair_goals_structured', true);
    if (!is_array($goals)) {
        wp_send_json_error('No goals found');
        return;
    }

    // Check if goal exists
    if (!isset($goals[$goal_id])) {
        wp_send_json_error('Goal not found');
        return;
    }

    // Remove the goal
    array_splice($goals, $goal_id, 1);

    // Save updated goals array
    $updated = update_user_meta($user_id, 'myavana_hair_goals_structured', $goals);

    if ($updated === false) {
        wp_send_json_error('Failed to delete goal');
        return;
    }

    error_log(sprintf('[MYAVANA] Goal deleted: User=%d, GoalID=%d', $user_id, $goal_id));

    wp_send_json_success([
        'message' => 'Goal deleted successfully!',
        'goal_id' => $goal_id
    ]);
}
add_action('wp_ajax_myavana_delete_goal', 'myavana_delete_goal');

/**
 * Delete a routine
 */
function myavana_delete_routine() {
    // Verify nonce - be more flexible with verification
    $nonce_verified = false;
    if (isset($_POST['security'])) {
        // Try multiple nonce actions
        $nonce_verified = wp_verify_nonce($_POST['security'], 'myavana_delete_routine') ||
                         wp_verify_nonce($_POST['security'], 'myavana_nonce') ||
                         wp_verify_nonce($_POST['security'], 'myavana_add_routine');
    }

    if (!$nonce_verified) {
        error_log('[MYAVANA] Delete routine nonce verification failed. Nonce: ' . (isset($_POST['security']) ? substr($_POST['security'], 0, 10) . '...' : 'not set'));
        wp_send_json_error('Security check failed');
        return;
    }

    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error('User not logged in');
        return;
    }

    // Validate routine_id
    if (!isset($_POST['routine_id']) || $_POST['routine_id'] === '') {
        wp_send_json_error('Routine ID is required');
        return;
    }

    $routine_id = intval($_POST['routine_id']);

    // Get existing routines
    $routines = get_user_meta($user_id, 'myavana_current_routine', true);
    if (!is_array($routines)) {
        wp_send_json_error('No routines found');
        return;
    }

    // Check if routine exists
    if (!isset($routines[$routine_id])) {
        wp_send_json_error('Routine not found');
        return;
    }

    // Remove the routine
    array_splice($routines, $routine_id, 1);

    // Save updated routines array
    $updated = update_user_meta($user_id, 'myavana_current_routine', $routines);

    if ($updated === false) {
        wp_send_json_error('Failed to delete routine');
        return;
    }

    error_log(sprintf('[MYAVANA] Routine deleted: User=%d, RoutineID=%d', $user_id, $routine_id));

    wp_send_json_success([
        'message' => 'Routine deleted successfully!',
        'routine_id' => $routine_id
    ]);
}
add_action('wp_ajax_myavana_delete_routine', 'myavana_delete_routine');
