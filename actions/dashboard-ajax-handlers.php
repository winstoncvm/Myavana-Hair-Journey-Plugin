<?php
/**
 * Dashboard AJAX Handlers
 *
 * Handles all AJAX requests related to the advanced dashboard shortcode
 *
 * @package Myavana_Hair_Journey
 * @since 2.3.5
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AJAX handler to load timeline embed for dashboard
 */
if (!function_exists('myavana_load_timeline_embed_dash')) {
    function myavana_load_timeline_embed_dash() {
        // Add debugging
        error_log('Timeline AJAX handler called');

        // Check nonce
        if (!check_ajax_referer('myavana_nonce', 'nonce', false)) {
            error_log('Timeline AJAX: Nonce verification failed');
            wp_send_json_error('Security check failed');
            return;
        }

        if (!is_user_logged_in()) {
            error_log('Timeline AJAX: User not logged in');
            wp_send_json_error('User not logged in');
            return;
        }

        error_log('Timeline AJAX: Authentication passed, loading timeline...');

        // Check if timeline function exists
        if (!function_exists('myavana_hair_journey_timeline_shortcode')) {
            error_log('Timeline AJAX: Timeline shortcode function not found');

            // Try to include the timeline shortcode file
            $timeline_file = MYAVANA_DIR . 'templates/hair-diary-timeline-shortcode.php';
            if (file_exists($timeline_file)) {
                require_once $timeline_file;
                error_log('Timeline AJAX: Timeline file included');
            } else {
                wp_send_json_error('Timeline function not available');
                return;
            }
        }

        try {
            // Get the timeline shortcode content with dashboard-specific parameters
            $timeline_content = myavana_hair_journey_timeline_shortcode([
                'show_progress' => 'true',
                'show_stats' => 'true',
                'autoplay' => 'false',
                'entries_per_page' => '8', // Fewer entries for dashboard view
                'theme' => 'dashboard'
            ]);

            error_log('Timeline AJAX: Timeline content generated, length: ' . strlen($timeline_content));

            wp_send_json_success([
                'html' => $timeline_content,
                'component' => 'timeline_embed'
            ]);
        } catch (Exception $e) {
            error_log('Timeline AJAX: Error generating timeline: ' . $e->getMessage());
            wp_send_json_error('Error generating timeline: ' . $e->getMessage());
        }
    }
}
add_action('wp_ajax_myavana_load_timeline_embed_dash', 'myavana_load_timeline_embed_dash');
add_action('wp_ajax_nopriv_myavana_load_timeline_embed_dash', 'myavana_load_timeline_embed_dash');

/**
 * AJAX handler to load profile embed
 */
if (!function_exists('myavana_load_profile_embed')) {
    function myavana_load_profile_embed() {
        check_ajax_referer('myavana_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error('User not logged in');
            return;
        }

        // Check if profile function exists
        if (!function_exists('myavana_profile_shortcode')) {
            // Try to include the profile shortcode file
            $profile_file = MYAVANA_DIR . 'templates/profile-shortcode.php';
            if (file_exists($profile_file)) {
                require_once $profile_file;
            } else {
                wp_send_json_error('Profile function not available');
                return;
            }
        }

        try {
            // Get the profile shortcode content
            $profile_content = myavana_profile_shortcode();

            wp_send_json_success([
                'html' => $profile_content,
                'component' => 'profile_embed'
            ]);
        } catch (Exception $e) {
            wp_send_json_error('Error generating profile: ' . $e->getMessage());
        }
    }
}
add_action('wp_ajax_myavana_load_profile_embed', 'myavana_load_profile_embed');

/**
 * AJAX handler to get dashboard stats
 */
if (!function_exists('myavana_get_dashboard_stats_ajax')) {
    function myavana_get_dashboard_stats_ajax() {
        check_ajax_referer('myavana_diary', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error('User not logged in');
            return;
        }

        $user_id = get_current_user_id();

        // Load the dashboard shortcode file to access helper function
        $dashboard_file = MYAVANA_PATH . 'templates/advanced-dashboard-shortcode.php';
        if (!function_exists('myavana_get_dashboard_data') && file_exists($dashboard_file)) {
            require_once $dashboard_file;
        }

        $stats = myavana_get_dashboard_data($user_id);

        wp_send_json_success($stats);
    }
}
add_action('wp_ajax_myavana_get_dashboard_stats', 'myavana_get_dashboard_stats_ajax');

/**
 * AJAX handler to load chatbot embed
 */
if (!function_exists('myavana_load_chatbot_embed')) {
    function myavana_load_chatbot_embed() {
        check_ajax_referer('myavana_diary', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error('User not logged in');
            return;
        }

        // Get the chatbot shortcode content
        if (!function_exists('myavana_test_shortcode')) {
            // Try to include the test shortcode file
            $test_file = MYAVANA_DIR . 'templates/test-shortcode.php';
            if (file_exists($test_file)) {
                require_once $test_file;
            } else {
                wp_send_json_error('Chatbot function not available');
                return;
            }
        }

        $chatbot_content = myavana_test_shortcode();

        wp_send_json_success($chatbot_content);
    }
}
add_action('wp_ajax_myavana_load_chatbot_embed', 'myavana_load_chatbot_embed');

/**
 * AJAX handler to load entry form
 */
if (!function_exists('myavana_load_entry_form_dash')) {
    function myavana_load_entry_form_dash() {
        // Check authentication
        if (!is_user_logged_in()) {
            wp_send_json_error('User not logged in');
            return;
        }

        // Verify nonce - support multiple nonce names for compatibility
        $nonce_verified = false;
        if (isset($_POST['nonce'])) {
            $nonce_verified = wp_verify_nonce($_POST['nonce'], 'myavana_diary') ||
                             wp_verify_nonce($_POST['nonce'], 'myavana_nonce') ||
                             wp_verify_nonce($_POST['nonce'], 'myavana_hair_diary_nonce');
        }

        if (!$nonce_verified) {
            wp_send_json_error('Security check failed');
            return;
        }

        // Load the entry form component directly
        $component_path = MYAVANA_PATH . 'templates/components/entry-form.php';

        if (file_exists($component_path)) {
            ob_start();
            include $component_path;
            $entry_form_content = ob_get_clean();
            wp_send_json_success($entry_form_content);
        } else {
            wp_send_json_error('Entry form component not found');
        }
    }
}
add_action('wp_ajax_myavana_load_entry_form_dash', 'myavana_load_entry_form_dash');