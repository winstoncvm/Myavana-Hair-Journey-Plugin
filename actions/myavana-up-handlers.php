<?php
/**
 * Unified Profile - AJAX Handlers
 *
 * Backend handlers for unified profile page functionality
 * Prefix: myavana_up_* (unified profile)
 *
 * @package Myavana_Hair_Journey
 * @version 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AJAX Handler: Save Profile
 * Action: myavana_up_save_profile
 */
function myavana_up_save_profile_handler() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'myavana_up_edit_profile')) {
        wp_send_json_error('Security check failed');
    }

    // Get current user
    $current_user_id = get_current_user_id();
    if (!$current_user_id) {
        wp_send_json_error('You must be logged in');
    }

    $user_id = absint($_POST['user_id'] ?? $current_user_id);

    // Verify user can edit this profile
    if ($user_id !== $current_user_id) {
        wp_send_json_error('You cannot edit this profile');
    }

    global $wpdb;
    $profile_table = $wpdb->prefix . 'myavana_profiles';

    // Prepare response data
    $response_data = [];

    // Update display name
    if (!empty($_POST['display_name'])) {
        $display_name = sanitize_text_field($_POST['display_name']);
        wp_update_user([
            'ID' => $user_id,
            'display_name' => $display_name
        ]);
        $response_data['display_name'] = $display_name;
    }

    // Update bio in profile table
    if (isset($_POST['bio'])) {
        $bio = sanitize_textarea_field($_POST['bio']);
        $wpdb->update(
            $profile_table,
            ['bio' => $bio],
            ['user_id' => $user_id],
            ['%s'],
            ['%d']
        );
        $response_data['bio'] = $bio;
    }

    // Update location and website
    if (isset($_POST['location'])) {
        update_user_meta($user_id, 'myavana_up_location', sanitize_text_field($_POST['location']));
    }

    if (isset($_POST['website'])) {
        update_user_meta($user_id, 'myavana_up_website', esc_url_raw($_POST['website']));
    }

    // Update hair profile
    $hair_profile_fields = [
        'hair_type' => 'hair_type',
        'hair_porosity' => 'hair_porosity',
        'hair_length' => 'hair_length',
        'journey_stage' => 'hair_journey_stage'
    ];

    foreach ($hair_profile_fields as $post_key => $db_field) {
        if (isset($_POST[$post_key])) {
            $value = sanitize_text_field($_POST[$post_key]);
            if ($post_key === 'hair_porosity' || $post_key === 'hair_length') {
                update_user_meta($user_id, $post_key, $value);
            } else {
                $wpdb->update(
                    $profile_table,
                    [$db_field => $value],
                    ['user_id' => $user_id],
                    ['%s'],
                    ['%d']
                );
            }
        }
    }

    // Update hair concerns
    if (isset($_POST['hair_concerns'])) {
        $concerns = array_map('sanitize_text_field', (array)$_POST['hair_concerns']);
        update_user_meta($user_id, 'myavana_up_hair_concerns', $concerns);
    } else {
        update_user_meta($user_id, 'myavana_up_hair_concerns', []);
    }

    // Update hair goals
    if (isset($_POST['goals']) && is_array($_POST['goals'])) {
        $goals = [];
        foreach ($_POST['goals'] as $goal) {
            if (!empty($goal['title'])) {
                $goals[] = [
                    'title' => sanitize_text_field($goal['title']),
                    'progress' => 0
                ];
            }
        }
        update_user_meta($user_id, 'myavana_hair_goals_structured', $goals);
    }

    // Update preferences
    $preferences = [
        'public_profile' => isset($_POST['public_profile']) ? 1 : 0,
        'show_activity' => isset($_POST['show_activity']) ? 1 : 0,
        'email_notifications' => isset($_POST['email_notifications']) ? 1 : 0,
        'community_notifications' => isset($_POST['community_notifications']) ? 1 : 0
    ];

    foreach ($preferences as $key => $value) {
        update_user_meta($user_id, 'myavana_up_' . $key, $value);
    }

    // Handle avatar upload
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $upload = wp_handle_upload($_FILES['avatar'], ['test_form' => false]);

        if (!isset($upload['error'])) {
            // Create attachment
            $attachment = [
                'post_mime_type' => $upload['type'],
                'post_title' => sanitize_file_name($upload['file']),
                'post_content' => '',
                'post_status' => 'inherit'
            ];

            $attach_id = wp_insert_attachment($attachment, $upload['file']);
            $attach_data = wp_generate_attachment_metadata($attach_id, $upload['file']);
            wp_update_attachment_metadata($attach_id, $attach_data);

            // Update user avatar (using WordPress native method or custom meta)
            update_user_meta($user_id, 'myavana_up_avatar_id', $attach_id);
            update_user_meta($user_id, 'myavana_up_avatar_url', $upload['url']);

            $response_data['avatar_url'] = $upload['url'];
        }
    }

    wp_send_json_success($response_data);
}
add_action('wp_ajax_myavana_up_save_profile', 'myavana_up_save_profile_handler');

/**
 * Get custom avatar URL for user
 */
function myavana_up_get_avatar_url($user_id) {
    $custom_avatar = get_user_meta($user_id, 'myavana_up_avatar_url', true);
    if ($custom_avatar) {
        return $custom_avatar;
    }
    return get_avatar_url($user_id);
}

/**
 * Filter to use custom avatar if available
 */
add_filter('get_avatar_url', function($url, $id_or_email, $args) {
    $user = false;

    if (is_numeric($id_or_email)) {
        $user_id = (int) $id_or_email;
    } elseif (is_string($id_or_email) && ($user = get_user_by('email', $id_or_email))) {
        $user_id = $user->ID;
    } elseif (is_object($id_or_email) && !empty($id_or_email->user_id)) {
        $user_id = (int) $id_or_email->user_id;
    }

    if (isset($user_id)) {
        $custom_avatar = get_user_meta($user_id, 'myavana_up_avatar_url', true);
        if ($custom_avatar) {
            return $custom_avatar;
        }
    }

    return $url;
}, 10, 3);
