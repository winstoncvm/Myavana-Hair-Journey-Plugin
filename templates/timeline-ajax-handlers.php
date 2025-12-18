<?php
/**
 * MYAVANA Improved Timeline - AJAX Handlers
 * Secure endpoints for timeline functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class Myavana_Timeline_Ajax_Handlers {

    public function __construct() {
        add_action('wp_ajax_myavana_get_timeline_entries', array($this, 'get_timeline_entries'));
        add_action('wp_ajax_nopriv_myavana_get_timeline_entries', array($this, 'get_timeline_entries'));

        add_action('wp_ajax_myavana_get_entry_details', array($this, 'get_entry_details'));
        add_action('wp_ajax_nopriv_myavana_get_entry_details', array($this, 'get_entry_details'));

        add_action('wp_ajax_myavana_get_timeline_stats', array($this, 'get_timeline_stats'));
        add_action('wp_ajax_nopriv_myavana_get_timeline_stats', array($this, 'get_timeline_stats'));
    }

    /**
     * Get timeline entries with pagination
     */
    public function get_timeline_entries() {
        try {
            // Verify nonce
            if (!wp_verify_nonce($_POST['nonce'], 'myavana_timeline_nonce')) {
                wp_send_json_error(array('message' => 'Invalid security token'));
                return;
            }

            // Sanitize input
            $page = intval($_POST['page'] ?? 1);
            $per_page = intval($_POST['per_page'] ?? 10);
            $user_id = intval($_POST['user_id'] ?? get_current_user_id());

            // Validate pagination
            $page = max(1, $page);
            $per_page = min(50, max(1, $per_page)); // Limit to 50 entries per page

            if (!$user_id) {
                wp_send_json_error(array('message' => 'User not authenticated'));
                return;
            }

            global $wpdb;

            // Calculate offset
            $offset = ($page - 1) * $per_page;

            // Get total count
            $total_query = $wpdb->prepare(
                "SELECT COUNT(*)
                 FROM {$wpdb->posts} p
                 WHERE p.post_type = 'hair_journey_entry'
                 AND p.post_author = %d
                 AND p.post_status = 'publish'",
                $user_id
            );

            $total = $wpdb->get_var($total_query);

            // Get entries with pagination
            $entries_query = $wpdb->prepare(
                "SELECT p.ID, p.post_title, p.post_content, p.post_date
                 FROM {$wpdb->posts} p
                 WHERE p.post_type = 'hair_journey_entry'
                 AND p.post_author = %d
                 AND p.post_status = 'publish'
                 ORDER BY p.post_date DESC
                 LIMIT %d OFFSET %d",
                $user_id, $per_page, $offset
            );

            $entries_data = $wpdb->get_results($entries_query);

            // Format entries
            $entries = array();
            foreach ($entries_data as $entry) {
                $formatted_entry = $this->format_entry($entry);
                if ($formatted_entry) {
                    $entries[] = $formatted_entry;
                }
            }

            // Get user stats
            $stats = $this->get_user_stats($user_id);

            wp_send_json_success(array(
                'entries' => $entries,
                'total' => intval($total),
                'page' => $page,
                'per_page' => $per_page,
                'total_pages' => ceil($total / $per_page),
                'stats' => $stats
            ));

        } catch (Exception $e) {
            error_log('Myavana Timeline Ajax Error - get_timeline_entries: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'An error occurred while loading entries'));
        }
    }

    /**
     * Get detailed entry information
     */
    public function get_entry_details() {
        try {
            // Verify nonce
            if (!wp_verify_nonce($_POST['nonce'], 'myavana_timeline_nonce')) {
                wp_send_json_error(array('message' => 'Invalid security token'));
                return;
            }

            $entry_id = intval($_POST['entry_id'] ?? 0);

            if (!$entry_id) {
                wp_send_json_error(array('message' => 'Invalid entry ID'));
                return;
            }

            $post = get_post($entry_id);

            if (!$post || $post->post_type !== 'hair_journey_entry') {
                wp_send_json_error(array('message' => 'Entry not found'));
                return;
            }

            // Check if user has permission to view this entry
            $current_user_id = get_current_user_id();
            if ($post->post_author != $current_user_id && !current_user_can('edit_others_posts')) {
                wp_send_json_error(array('message' => 'Access denied'));
                return;
            }

            $detailed_entry = $this->format_entry_detailed($post);

            wp_send_json_success($detailed_entry);

        } catch (Exception $e) {
            error_log('Myavana Timeline Ajax Error - get_entry_details: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'An error occurred while loading entry details'));
        }
    }

    /**
     * Get timeline statistics
     */
    public function get_timeline_stats() {
        try {
            // Verify nonce
            if (!wp_verify_nonce($_POST['nonce'], 'myavana_timeline_nonce')) {
                wp_send_json_error(array('message' => 'Invalid security token'));
                return;
            }

            $user_id = intval($_POST['user_id'] ?? get_current_user_id());

            if (!$user_id) {
                wp_send_json_error(array('message' => 'User not authenticated'));
                return;
            }

            $stats = $this->get_user_stats($user_id);

            wp_send_json_success($stats);

        } catch (Exception $e) {
            error_log('Myavana Timeline Ajax Error - get_timeline_stats: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'An error occurred while loading statistics'));
        }
    }

    /**
     * Format entry for timeline display
     */
    private function format_entry($entry) {
        if (!$entry) return null;

        try {
            // Get meta data
            $entry_type = get_post_meta($entry->ID, 'entry_type', true) ?: 'note';
            $mood_rating = get_post_meta($entry->ID, 'mood_rating', true);
            $hair_health_rating = get_post_meta($entry->ID, 'hair_health_rating', true);
            $hair_length = get_post_meta($entry->ID, 'hair_length', true);

            // Get images
            $images = $this->get_entry_images($entry->ID);

            // Check if entry is featured (has high ratings or multiple images)
            $featured = false;
            if (($mood_rating && $mood_rating >= 8) ||
                ($hair_health_rating && $hair_health_rating >= 8) ||
                (count($images) >= 2)) {
                $featured = true;
            }

            return array(
                'id' => $entry->ID,
                'entry_title' => wp_strip_all_tags($entry->post_title),
                'entry_content' => wp_strip_all_tags($entry->post_content),
                'entry_date' => $entry->post_date,
                'entry_type' => sanitize_text_field($entry_type),
                'mood_rating' => $mood_rating ? intval($mood_rating) : null,
                'hair_health_rating' => $hair_health_rating ? intval($hair_health_rating) : null,
                'hair_length' => sanitize_text_field($hair_length),
                'images' => $images,
                'featured' => $featured
            );

        } catch (Exception $e) {
            error_log('Myavana Timeline Error - format_entry: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Format detailed entry information
     */
    private function format_entry_detailed($post) {
        if (!$post) return null;

        try {
            // Get all meta data
            $meta_data = get_post_meta($post->ID);

            // Get images with full URLs
            $images = $this->get_entry_images($post->ID, true);

            return array(
                'id' => $post->ID,
                'entry_title' => wp_strip_all_tags($post->post_title),
                'entry_content' => wpautop($post->post_content),
                'entry_date' => $post->post_date,
                'entry_type' => sanitize_text_field($meta_data['entry_type'][0] ?? 'note'),
                'mood_rating' => isset($meta_data['mood_rating'][0]) ? intval($meta_data['mood_rating'][0]) : null,
                'hair_health_rating' => isset($meta_data['hair_health_rating'][0]) ? intval($meta_data['hair_health_rating'][0]) : null,
                'hair_length' => sanitize_text_field($meta_data['hair_length'][0] ?? ''),
                'scalp_condition' => sanitize_text_field($meta_data['scalp_condition'][0] ?? ''),
                'products_used' => sanitize_text_field($meta_data['products_used'][0] ?? ''),
                'styling_method' => sanitize_text_field($meta_data['styling_method'][0] ?? ''),
                'weather_humidity' => sanitize_text_field($meta_data['weather_humidity'][0] ?? ''),
                'sleep_quality' => isset($meta_data['sleep_quality'][0]) ? intval($meta_data['sleep_quality'][0]) : null,
                'stress_level' => isset($meta_data['stress_level'][0]) ? intval($meta_data['stress_level'][0]) : null,
                'images' => $images
            );

        } catch (Exception $e) {
            error_log('Myavana Timeline Error - format_entry_detailed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get entry images
     */
    private function get_entry_images($entry_id, $full_size = false) {
        $images = array();

        try {
            // Get attached images
            $attachments = get_attached_media('image', $entry_id);

            foreach ($attachments as $attachment) {
                $image_data = array(
                    'id' => $attachment->ID,
                    'url' => wp_get_attachment_url($attachment->ID),
                    'alt' => get_post_meta($attachment->ID, '_wp_attachment_image_alt', true)
                );

                if (!$full_size) {
                    $image_data['thumbnail'] = wp_get_attachment_image_url($attachment->ID, 'thumbnail');
                }

                $images[] = $image_data;
            }

            // Also check for base64 images in meta
            $base64_images = get_post_meta($entry_id, 'entry_images', true);
            if ($base64_images && is_array($base64_images)) {
                foreach ($base64_images as $base64_image) {
                    if (isset($base64_image['url'])) {
                        $images[] = array(
                            'id' => 'base64_' . md5($base64_image['url']),
                            'url' => esc_url($base64_image['url']),
                            'thumbnail' => esc_url($base64_image['url']),
                            'alt' => 'Hair journey image'
                        );
                    }
                }
            }

        } catch (Exception $e) {
            error_log('Myavana Timeline Error - get_entry_images: ' . $e->getMessage());
        }

        return $images;
    }

    /**
     * Get user statistics for timeline
     */
    private function get_user_stats($user_id) {
        global $wpdb;

        try {
            // Get total entries
            $total_entries = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*)
                 FROM {$wpdb->posts}
                 WHERE post_type = 'hair_journey_entry'
                 AND post_author = %d
                 AND post_status = 'publish'",
                $user_id
            ));

            // Get journey start date
            $start_date = $wpdb->get_var($wpdb->prepare(
                "SELECT MIN(post_date)
                 FROM {$wpdb->posts}
                 WHERE post_type = 'hair_journey_entry'
                 AND post_author = %d
                 AND post_status = 'publish'",
                $user_id
            ));

            // Calculate journey days
            $journey_days = 0;
            if ($start_date) {
                $start = new DateTime($start_date);
                $now = new DateTime();
                $journey_days = $start->diff($now)->days;
            }

            // Get average ratings
            $avg_health_rating = $wpdb->get_var($wpdb->prepare(
                "SELECT AVG(CAST(pm.meta_value AS DECIMAL(3,1)))
                 FROM {$wpdb->postmeta} pm
                 INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
                 WHERE pm.meta_key = 'hair_health_rating'
                 AND p.post_type = 'hair_journey_entry'
                 AND p.post_author = %d
                 AND p.post_status = 'publish'
                 AND pm.meta_value != ''",
                $user_id
            ));

            // Get hair growth data (simplified)
            $hair_growth = $this->calculate_hair_growth($user_id);

            // Get current streak
            $current_streak = $this->calculate_current_streak($user_id);

            return array(
                'total_entries' => intval($total_entries),
                'journey_days' => $journey_days,
                'avg_health_rating' => $avg_health_rating ? round($avg_health_rating, 1) : null,
                'hair_growth' => $hair_growth,
                'current_streak' => $current_streak,
                'start_date' => $start_date
            );

        } catch (Exception $e) {
            error_log('Myavana Timeline Error - get_user_stats: ' . $e->getMessage());
            return array(
                'total_entries' => 0,
                'journey_days' => 0,
                'avg_health_rating' => null,
                'hair_growth' => 'N/A',
                'current_streak' => 0
            );
        }
    }

    /**
     * Calculate hair growth progress
     */
    private function calculate_hair_growth($user_id) {
        global $wpdb;

        try {
            // Get first and last hair length measurements
            $length_data = $wpdb->get_results($wpdb->prepare(
                "SELECT pm.meta_value, p.post_date
                 FROM {$wpdb->postmeta} pm
                 INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
                 WHERE pm.meta_key = 'hair_length'
                 AND p.post_type = 'hair_journey_entry'
                 AND p.post_author = %d
                 AND p.post_status = 'publish'
                 AND pm.meta_value != ''
                 ORDER BY p.post_date ASC",
                $user_id
            ));

            if (count($length_data) < 2) {
                return 'N/A';
            }

            $first_length = floatval($length_data[0]->meta_value);
            $last_length = floatval(end($length_data)->meta_value);

            $growth = $last_length - $first_length;

            if ($growth > 0) {
                return '+' . number_format($growth, 1) . ' inches';
            } elseif ($growth < 0) {
                return number_format($growth, 1) . ' inches';
            } else {
                return 'No change';
            }

        } catch (Exception $e) {
            error_log('Myavana Timeline Error - calculate_hair_growth: ' . $e->getMessage());
            return 'N/A';
        }
    }

    /**
     * Calculate current streak of consecutive days with entries
     */
    private function calculate_current_streak($user_id) {
        global $wpdb;

        try {
            // Get recent entry dates
            $recent_dates = $wpdb->get_col($wpdb->prepare(
                "SELECT DISTINCT DATE(post_date) as entry_date
                 FROM {$wpdb->posts}
                 WHERE post_type = 'hair_journey_entry'
                 AND post_author = %d
                 AND post_status = 'publish'
                 AND post_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                 ORDER BY entry_date DESC",
                $user_id
            ));

            if (empty($recent_dates)) {
                return 0;
            }

            $streak = 0;
            $current_date = new DateTime();

            foreach ($recent_dates as $entry_date) {
                $entry_datetime = new DateTime($entry_date);
                $diff = $current_date->diff($entry_datetime)->days;

                if ($diff <= $streak + 1) {
                    $streak++;
                    $current_date = $entry_datetime;
                } else {
                    break;
                }
            }

            return $streak;

        } catch (Exception $e) {
            error_log('Myavana Timeline Error - calculate_current_streak: ' . $e->getMessage());
            return 0;
        }
    }
}

// Initialize AJAX handlers
new Myavana_Timeline_Ajax_Handlers();