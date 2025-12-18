<?php
/**
 * Share to Community Integration
 * Allows users to share their hair journey entries to the community feed
 */

if (!defined('ABSPATH')) {
    exit;
}

class Myavana_Share_To_Community {

    public function __construct() {
        // AJAX handler for sharing entry to community
        add_action('wp_ajax_share_entry_to_community', array($this, 'share_entry_to_community'));
    }

    /**
     * Share a hair journey entry to the community feed
     */
    public function share_entry_to_community() {
        // Security check
        if (!wp_verify_nonce($_POST['nonce'], 'myavana_nonce')) {
            wp_send_json_error('Security check failed');
            return;
        }

        $entry_id = intval($_POST['entry_id']);
        $user_id = get_current_user_id();

        // Verify this is the user's entry
        $entry = get_post($entry_id);
        if (!$entry || $entry->post_type !== 'hair_journey_entry' || $entry->post_author != $user_id) {
            wp_send_json_error('Invalid entry');
            return;
        }

        // Check if already shared
        $shared_post_id = get_post_meta($entry_id, 'shared_community_post_id', true);
        if ($shared_post_id) {
            // Already shared, check if post still exists
            $existing_post = get_post($shared_post_id);
            if ($existing_post) {
                wp_send_json_error('This entry is already shared to the community');
                return;
            }
        }

        // Get entry data
        $title = $entry->post_title;
        $content = wp_strip_all_tags($entry->post_content);
        $health_rating = get_post_meta($entry_id, 'health_rating', true);
        $mood = get_post_meta($entry_id, 'mood_demeanor', true);

        // Get featured image
        $image_url = '';
        if (has_post_thumbnail($entry_id)) {
            $image_url = get_the_post_thumbnail_url($entry_id, 'large');
        }

        // Build post content with journey context
        $community_content = $content;
        if ($health_rating) {
            $community_content .= "\n\n✨ Hair Health: " . $health_rating . "/10";
        }
        if ($mood) {
            $community_content .= "\n💭 Feeling: " . ucfirst($mood);
        }

        // Create community post
        global $wpdb;
        $posts_table = $wpdb->prefix . 'myavana_community_posts';

        $result = $wpdb->insert(
            $posts_table,
            array(
                'user_id' => $user_id,
                'title' => $title,
                'content' => $community_content,
                'image_url' => $image_url,
                'post_type' => 'progress',
                'privacy_level' => 'public',
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s')
        );

        if ($result) {
            $community_post_id = $wpdb->insert_id;

            // Link entry to community post
            update_post_meta($entry_id, 'shared_community_post_id', $community_post_id);
            update_post_meta($entry_id, 'shared_to_community', '1');
            update_post_meta($entry_id, 'shared_date', current_time('mysql'));

            wp_send_json_success(array(
                'message' => 'Entry shared to community successfully!',
                'community_post_id' => $community_post_id
            ));
        } else {
            wp_send_json_error('Failed to share entry');
        }
    }

    /**
     * Check if an entry has been shared
     */
    public static function is_entry_shared($entry_id) {
        return get_post_meta($entry_id, 'shared_to_community', true) === '1';
    }

    /**
     * Get the community post ID for a shared entry
     */
    public static function get_community_post_id($entry_id) {
        return get_post_meta($entry_id, 'shared_community_post_id', true);
    }
}

// Initialize
new Myavana_Share_To_Community();

/**
 * Helper function to render share button for entries
 * Add this to your entry templates/views
 */
function myavana_render_share_button($entry_id) {
    $is_shared = Myavana_Share_To_Community::is_entry_shared($entry_id);

    if ($is_shared) {
        echo '<button class="myavana-btn-secondary myavana-share-entry-btn shared" data-entry-id="' . esc_attr($entry_id) . '" disabled>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                Shared to Community
              </button>';
    } else {
        echo '<button class="myavana-btn-secondary myavana-share-entry-btn" data-entry-id="' . esc_attr($entry_id) . '">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="18" cy="5" r="3"></circle>
                    <circle cx="6" cy="12" r="3"></circle>
                    <circle cx="18" cy="19" r="3"></circle>
                    <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line>
                    <line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line>
                </svg>
                Share to Community
              </button>';
    }
}
