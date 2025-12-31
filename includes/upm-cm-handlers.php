<?php
/**
 * Unified Profile Management & Community - Backend Handlers
 * Handles all AJAX requests and business logic for new features
 *
 * @package Myavana_Hair_Journey
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class UPM_CM_Handlers {

    private $user_id;

    public function __construct() {
        $this->user_id = get_current_user_id();
        $this->init_hooks();
    }

    /**
     * Initialize all WordPress hooks
     */
    private function init_hooks() {
        // Stories & Highlights
        add_action('wp_ajax_upm_cm_create_story', array($this, 'create_story'));
        add_action('wp_ajax_upm_cm_get_stories', array($this, 'get_stories'));
        add_action('wp_ajax_upm_cm_view_story', array($this, 'view_story'));
        add_action('wp_ajax_upm_cm_delete_story', array($this, 'delete_story'));
        add_action('wp_ajax_upm_cm_save_to_highlights', array($this, 'save_to_highlights'));
        add_action('wp_ajax_upm_cm_get_highlights', array($this, 'get_highlights'));

        // Direct Messaging
        add_action('wp_ajax_upm_cm_send_message', array($this, 'send_message'));
        add_action('wp_ajax_upm_cm_get_conversations', array($this, 'get_conversations'));
        add_action('wp_ajax_upm_cm_get_messages', array($this, 'get_messages'));
        add_action('wp_ajax_upm_cm_mark_messages_read', array($this, 'mark_messages_read'));
        add_action('wp_ajax_upm_cm_delete_message', array($this, 'delete_message'));

        // Community Groups
        add_action('wp_ajax_upm_cm_create_group', array($this, 'create_group'));
        add_action('wp_ajax_upm_cm_get_groups', array($this, 'get_groups'));
        add_action('wp_ajax_upm_cm_get_group_details', array($this, 'get_group_details'));
        add_action('wp_ajax_upm_cm_join_group', array($this, 'join_group'));
        add_action('wp_ajax_upm_cm_leave_group', array($this, 'leave_group'));
        add_action('wp_ajax_upm_cm_create_group_post', array($this, 'create_group_post'));
        add_action('wp_ajax_upm_cm_get_group_posts', array($this, 'get_group_posts'));

        // Reactions & Engagement
        add_action('wp_ajax_upm_cm_add_reaction', array($this, 'add_reaction'));
        add_action('wp_ajax_upm_cm_remove_reaction', array($this, 'remove_reaction'));
        add_action('wp_ajax_upm_cm_get_post_reactions', array($this, 'get_post_reactions'));

        // Polls
        add_action('wp_ajax_upm_cm_create_poll', array($this, 'create_poll'));
        add_action('wp_ajax_upm_cm_vote_poll', array($this, 'vote_poll'));
        add_action('wp_ajax_upm_cm_get_poll_results', array($this, 'get_poll_results'));

        // Enhanced Search
        add_action('wp_ajax_upm_cm_search_users', array($this, 'search_users'));
        add_action('wp_ajax_upm_cm_search_content', array($this, 'search_content'));
        add_action('wp_ajax_upm_cm_get_trending_hashtags', array($this, 'get_trending_hashtags'));

        // Video Support
        add_action('wp_ajax_upm_cm_upload_video', array($this, 'upload_video'));
        add_action('wp_ajax_upm_cm_get_video_entry', array($this, 'get_video_entry'));

        // AI Recommendations
        add_action('wp_ajax_upm_cm_get_recommendations', array($this, 'get_recommendations'));
        add_action('wp_ajax_upm_cm_generate_recommendations', array($this, 'generate_recommendations'));
        add_action('wp_ajax_upm_cm_dismiss_recommendation', array($this, 'dismiss_recommendation'));

        // Analytics & Exports
        add_action('wp_ajax_upm_cm_get_advanced_analytics', array($this, 'get_advanced_analytics'));
        add_action('wp_ajax_upm_cm_export_journey', array($this, 'export_journey'));
        add_action('wp_ajax_upm_cm_get_export_status', array($this, 'get_export_status'));

        // Product Reviews
        add_action('wp_ajax_upm_cm_add_product_review', array($this, 'add_product_review'));
        add_action('wp_ajax_upm_cm_get_product_reviews', array($this, 'get_product_reviews'));
        add_action('wp_ajax_upm_cm_search_products', array($this, 'search_products'));

        // Cron jobs for cleanup
        add_action('upm_cm_cleanup_expired_stories', array($this, 'cleanup_expired_stories'));
    }

    /* ============================================
       STORIES & HIGHLIGHTS
       ============================================ */

    public function create_story() {
        check_ajax_referer('myavana_nonce', 'nonce');

        $content_type = sanitize_text_field($_POST['content_type'] ?? 'image');
        $caption = sanitize_textarea_field($_POST['caption'] ?? '');
        $background_color = sanitize_hex_color($_POST['background_color'] ?? '');
        $duration = intval($_POST['duration'] ?? 5);

        // Handle file upload
        $content_url = '';
        $thumbnail_url = '';

        if (!empty($_FILES['content'])) {
            $upload_result = $this->upm_cm_handle_media_upload($_FILES['content'], $content_type);
            if (is_wp_error($upload_result)) {
                wp_send_json_error($upload_result->get_error_message());
                return;
            }
            $content_url = $upload_result['url'];
            $thumbnail_url = $upload_result['thumbnail'] ?? '';
        }

        global $wpdb;
        $table = $wpdb->prefix . 'upm_cm_stories';

        // Stories expire in 24 hours
        $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));

        $result = $wpdb->insert(
            $table,
            array(
                'user_id' => $this->user_id,
                'content_type' => $content_type,
                'content_url' => $content_url,
                'thumbnail_url' => $thumbnail_url,
                'caption' => $caption,
                'background_color' => $background_color,
                'duration' => $duration,
                'expires_at' => $expires_at
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s')
        );

        if ($result) {
            wp_send_json_success(array(
                'message' => 'Story created successfully',
                'story_id' => $wpdb->insert_id,
                'expires_at' => $expires_at
            ));
        } else {
            wp_send_json_error('Failed to create story');
        }
    }

    public function get_stories() {
        check_ajax_referer('myavana_nonce', 'nonce');

        global $wpdb;
        $stories_table = $wpdb->prefix . 'upm_cm_stories';
        $followers_table = $wpdb->prefix . 'myavana_user_followers';
        $users_table = $wpdb->users;

        // Get stories from people user follows + own stories
        $sql = "SELECT s.*, u.display_name, u.user_email
                FROM $stories_table s
                LEFT JOIN $users_table u ON s.user_id = u.ID
                WHERE s.expires_at > NOW()
                AND s.is_highlight = 0
                AND (s.user_id = %d OR s.user_id IN (
                    SELECT following_id FROM $followers_table WHERE follower_id = %d
                ))
                ORDER BY s.created_at DESC
                LIMIT 50";

        $stories = $wpdb->get_results($wpdb->prepare($sql, $this->user_id, $this->user_id));

        // Add avatar and view status
        foreach ($stories as &$story) {
            $story->user_avatar = get_avatar_url($story->user_id, 80);
            $story->viewed_by_me = $this->upm_cm_has_viewed_story($story->id, $this->user_id);
        }

        wp_send_json_success($stories);
    }

    public function view_story() {
        check_ajax_referer('myavana_nonce', 'nonce');

        $story_id = intval($_POST['story_id']);

        global $wpdb;
        $views_table = $wpdb->prefix . 'upm_cm_story_views';

        // Record view
        $wpdb->replace(
            $views_table,
            array(
                'story_id' => $story_id,
                'user_id' => $this->user_id
            ),
            array('%d', '%d')
        );

        // Update view count
        $stories_table = $wpdb->prefix . 'upm_cm_stories';
        $wpdb->query($wpdb->prepare(
            "UPDATE $stories_table SET views_count = views_count + 1 WHERE id = %d",
            $story_id
        ));

        wp_send_json_success('Story viewed');
    }

    public function save_to_highlights() {
        check_ajax_referer('myavana_nonce', 'nonce');

        $story_id = intval($_POST['story_id']);
        $highlight_title = sanitize_text_field($_POST['highlight_title'] ?? 'Highlights');

        global $wpdb;
        $table = $wpdb->prefix . 'upm_cm_stories';

        $result = $wpdb->update(
            $table,
            array(
                'is_highlight' => 1,
                'highlight_title' => $highlight_title
            ),
            array('id' => $story_id, 'user_id' => $this->user_id),
            array('%d', '%s'),
            array('%d', '%d')
        );

        if ($result !== false) {
            wp_send_json_success('Saved to highlights');
        } else {
            wp_send_json_error('Failed to save to highlights');
        }
    }

    public function get_highlights() {
        check_ajax_referer('myavana_nonce', 'nonce');

        $user_id = intval($_POST['user_id'] ?? $this->user_id);

        global $wpdb;
        $table = $wpdb->prefix . 'upm_cm_stories';

        $highlights = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE user_id = %d AND is_highlight = 1 ORDER BY created_at DESC",
            $user_id
        ));

        // Group by highlight_title
        $grouped_highlights = array();
        foreach ($highlights as $highlight) {
            $title = $highlight->highlight_title ?? 'Highlights';
            if (!isset($grouped_highlights[$title])) {
                $grouped_highlights[$title] = array();
            }
            $grouped_highlights[$title][] = $highlight;
        }

        wp_send_json_success($grouped_highlights);
    }

    /* ============================================
       DIRECT MESSAGING
       ============================================ */

    public function send_message() {
        check_ajax_referer('myavana_nonce', 'nonce');

        $receiver_id = intval($_POST['receiver_id']);
        $message_text = sanitize_textarea_field($_POST['message_text']);
        $shared_entry_id = intval($_POST['shared_entry_id'] ?? 0);
        $shared_routine_id = intval($_POST['shared_routine_id'] ?? 0);

        global $wpdb;
        $messages_table = $wpdb->prefix . 'upm_cm_messages';
        $threads_table = $wpdb->prefix . 'upm_cm_message_threads';

        // Insert message
        $result = $wpdb->insert(
            $messages_table,
            array(
                'sender_id' => $this->user_id,
                'receiver_id' => $receiver_id,
                'message_text' => $message_text,
                'shared_entry_id' => $shared_entry_id > 0 ? $shared_entry_id : null,
                'shared_routine_id' => $shared_routine_id > 0 ? $shared_routine_id : null
            ),
            array('%d', '%d', '%s', '%d', '%d')
        );

        if ($result) {
            $message_id = $wpdb->insert_id;

            // Update or create thread
            $this->upm_cm_update_message_thread($this->user_id, $receiver_id, $message_id);

            // Get the created message
            $message = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $messages_table WHERE id = %d",
                $message_id
            ));

            wp_send_json_success(array(
                'message' => 'Message sent',
                'data' => $message
            ));
        } else {
            wp_send_json_error('Failed to send message');
        }
    }

    public function get_conversations() {
        check_ajax_referer('myavana_nonce', 'nonce');

        global $wpdb;
        $threads_table = $wpdb->prefix . 'upm_cm_message_threads';
        $messages_table = $wpdb->prefix . 'upm_cm_messages';
        $users_table = $wpdb->users;

        $sql = "SELECT t.*,
                       CASE
                           WHEN t.user1_id = %d THEN t.user2_id
                           ELSE t.user1_id
                       END as other_user_id,
                       CASE
                           WHEN t.user1_id = %d THEN t.unread_count_user1
                           ELSE t.unread_count_user2
                       END as unread_count,
                       m.message_text as last_message,
                       u.display_name as other_user_name
                FROM $threads_table t
                LEFT JOIN $messages_table m ON t.last_message_id = m.id
                LEFT JOIN $users_table u ON (CASE WHEN t.user1_id = %d THEN t.user2_id ELSE t.user1_id END) = u.ID
                WHERE t.user1_id = %d OR t.user2_id = %d
                ORDER BY t.last_message_at DESC";

        $conversations = $wpdb->get_results($wpdb->prepare($sql, $this->user_id, $this->user_id, $this->user_id, $this->user_id, $this->user_id));

        // Add avatars
        foreach ($conversations as &$conv) {
            $conv->other_user_avatar = get_avatar_url($conv->other_user_id, 60);
        }

        wp_send_json_success($conversations);
    }

    public function get_messages() {
        check_ajax_referer('myavana_nonce', 'nonce');

        $other_user_id = intval($_POST['other_user_id']);
        $limit = intval($_POST['limit'] ?? 50);
        $offset = intval($_POST['offset'] ?? 0);

        global $wpdb;
        $table = $wpdb->prefix . 'upm_cm_messages';

        $sql = "SELECT m.*, u.display_name as sender_name
                FROM $table m
                LEFT JOIN {$wpdb->users} u ON m.sender_id = u.ID
                WHERE (m.sender_id = %d AND m.receiver_id = %d)
                   OR (m.sender_id = %d AND m.receiver_id = %d)
                ORDER BY m.created_at DESC
                LIMIT %d OFFSET %d";

        $messages = $wpdb->get_results($wpdb->prepare(
            $sql,
            $this->user_id, $other_user_id,
            $other_user_id, $this->user_id,
            $limit, $offset
        ));

        // Add avatars
        foreach ($messages as &$msg) {
            $msg->sender_avatar = get_avatar_url($msg->sender_id, 40);
        }

        wp_send_json_success(array_reverse($messages));
    }

    public function mark_messages_read() {
        check_ajax_referer('myavana_nonce', 'nonce');

        $other_user_id = intval($_POST['other_user_id']);

        global $wpdb;
        $messages_table = $wpdb->prefix . 'upm_cm_messages';
        $threads_table = $wpdb->prefix . 'upm_cm_message_threads';

        // Mark messages as read
        $wpdb->query($wpdb->prepare(
            "UPDATE $messages_table SET is_read = 1, read_at = NOW()
             WHERE sender_id = %d AND receiver_id = %d AND is_read = 0",
            $other_user_id, $this->user_id
        ));

        // Reset unread count in thread
        $wpdb->query($wpdb->prepare(
            "UPDATE $threads_table
             SET unread_count_user1 = CASE WHEN user1_id = %d THEN 0 ELSE unread_count_user1 END,
                 unread_count_user2 = CASE WHEN user2_id = %d THEN 0 ELSE unread_count_user2 END
             WHERE (user1_id = %d AND user2_id = %d) OR (user1_id = %d AND user2_id = %d)",
            $this->user_id, $this->user_id,
            $this->user_id, $other_user_id,
            $other_user_id, $this->user_id
        ));

        wp_send_json_success('Messages marked as read');
    }

    /* ============================================
       COMMUNITY GROUPS
       ============================================ */

    public function create_group() {
        check_ajax_referer('myavana_nonce', 'nonce');

        $name = sanitize_text_field($_POST['name']);
        $description = sanitize_textarea_field($_POST['description'] ?? '');
        $group_type = sanitize_text_field($_POST['group_type'] ?? 'general');
        $is_private = intval($_POST['is_private'] ?? 0);
        $hair_type_filter = sanitize_text_field($_POST['hair_type_filter'] ?? '');
        $goal_filter = sanitize_text_field($_POST['goal_filter'] ?? '');

        global $wpdb;
        $groups_table = $wpdb->prefix . 'upm_cm_groups';
        $members_table = $wpdb->prefix . 'upm_cm_group_members';

        // Create group
        $result = $wpdb->insert(
            $groups_table,
            array(
                'name' => $name,
                'description' => $description,
                'group_type' => $group_type,
                'is_private' => $is_private,
                'hair_type_filter' => $hair_type_filter,
                'goal_filter' => $goal_filter,
                'creator_id' => $this->user_id,
                'members_count' => 1
            ),
            array('%s', '%s', '%s', '%d', '%s', '%s', '%d', '%d')
        );

        if ($result) {
            $group_id = $wpdb->insert_id;

            // Add creator as admin member
            $wpdb->insert(
                $members_table,
                array(
                    'group_id' => $group_id,
                    'user_id' => $this->user_id,
                    'role' => 'admin'
                ),
                array('%d', '%d', '%s')
            );

            wp_send_json_success(array(
                'message' => 'Group created successfully',
                'group_id' => $group_id
            ));
        } else {
            wp_send_json_error('Failed to create group');
        }
    }

    public function get_groups() {
        check_ajax_referer('myavana_nonce', 'nonce');

        $filter = sanitize_text_field($_POST['filter'] ?? 'all');
        $hair_type = sanitize_text_field($_POST['hair_type'] ?? '');
        $goal = sanitize_text_field($_POST['goal'] ?? '');

        global $wpdb;
        $groups_table = $wpdb->prefix . 'upm_cm_groups';
        $members_table = $wpdb->prefix . 'upm_cm_group_members';

        $sql = "SELECT g.*,
                       (SELECT COUNT(*) FROM $members_table WHERE group_id = g.id AND user_id = %d) as is_member
                FROM $groups_table g
                WHERE 1=1";

        $params = array($this->user_id);

        if ($filter === 'my_groups') {
            $sql .= " AND g.id IN (SELECT group_id FROM $members_table WHERE user_id = %d)";
            $params[] = $this->user_id;
        }

        if ($hair_type) {
            $sql .= " AND (g.hair_type_filter = %s OR g.hair_type_filter = '')";
            $params[] = $hair_type;
        }

        if ($goal) {
            $sql .= " AND (g.goal_filter = %s OR g.goal_filter = '')";
            $params[] = $goal;
        }

        $sql .= " ORDER BY g.members_count DESC LIMIT 50";

        $groups = $wpdb->get_results($wpdb->prepare($sql, $params));

        wp_send_json_success($groups);
    }

    public function join_group() {
        check_ajax_referer('myavana_nonce', 'nonce');

        $group_id = intval($_POST['group_id']);

        global $wpdb;
        $groups_table = $wpdb->prefix . 'upm_cm_groups';
        $members_table = $wpdb->prefix . 'upm_cm_group_members';

        // Check if already a member
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $members_table WHERE group_id = %d AND user_id = %d",
            $group_id, $this->user_id
        ));

        if ($exists) {
            wp_send_json_error('Already a member');
            return;
        }

        // Join group
        $result = $wpdb->insert(
            $members_table,
            array(
                'group_id' => $group_id,
                'user_id' => $this->user_id,
                'role' => 'member'
            ),
            array('%d', '%d', '%s')
        );

        if ($result) {
            // Update member count
            $wpdb->query($wpdb->prepare(
                "UPDATE $groups_table SET members_count = members_count + 1 WHERE id = %d",
                $group_id
            ));

            wp_send_json_success('Joined group successfully');
        } else {
            wp_send_json_error('Failed to join group');
        }
    }

    public function create_group_post() {
        check_ajax_referer('myavana_nonce', 'nonce');

        $group_id = intval($_POST['group_id']);
        $content = sanitize_textarea_field($_POST['content']);
        $media_type = sanitize_text_field($_POST['media_type'] ?? '');

        // Verify membership
        if (!$this->upm_cm_is_group_member($group_id, $this->user_id)) {
            wp_send_json_error('Not a group member');
            return;
        }

        global $wpdb;
        $posts_table = $wpdb->prefix . 'upm_cm_group_posts';
        $groups_table = $wpdb->prefix . 'upm_cm_groups';

        $result = $wpdb->insert(
            $posts_table,
            array(
                'group_id' => $group_id,
                'user_id' => $this->user_id,
                'content' => $content,
                'media_type' => $media_type
            ),
            array('%d', '%d', '%s', '%s')
        );

        if ($result) {
            // Update group posts count
            $wpdb->query($wpdb->prepare(
                "UPDATE $groups_table SET posts_count = posts_count + 1 WHERE id = %d",
                $group_id
            ));

            wp_send_json_success('Post created successfully');
        } else {
            wp_send_json_error('Failed to create post');
        }
    }

    /* ============================================
       REACTIONS & ENGAGEMENT
       ============================================ */

    public function add_reaction() {
        check_ajax_referer('myavana_nonce', 'nonce');

        $post_id = intval($_POST['post_id']);
        $reaction_type = sanitize_text_field($_POST['reaction_type'] ?? 'like');

        // Allowed reaction types
        $allowed_reactions = array('like', 'love', 'celebrate', 'support', 'insightful');
        if (!in_array($reaction_type, $allowed_reactions)) {
            $reaction_type = 'like';
        }

        global $wpdb;
        $table = $wpdb->prefix . 'upm_cm_post_reactions';

        // Replace existing reaction
        $wpdb->replace(
            $table,
            array(
                'post_id' => $post_id,
                'user_id' => $this->user_id,
                'reaction_type' => $reaction_type
            ),
            array('%d', '%d', '%s')
        );

        // Get updated reaction counts
        $reactions = $this->upm_cm_get_reaction_counts($post_id);

        wp_send_json_success(array(
            'message' => 'Reaction added',
            'reactions' => $reactions
        ));
    }

    public function remove_reaction() {
        check_ajax_referer('myavana_nonce', 'nonce');

        $post_id = intval($_POST['post_id']);

        global $wpdb;
        $table = $wpdb->prefix . 'upm_cm_post_reactions';

        $wpdb->delete(
            $table,
            array('post_id' => $post_id, 'user_id' => $this->user_id),
            array('%d', '%d')
        );

        $reactions = $this->upm_cm_get_reaction_counts($post_id);

        wp_send_json_success(array(
            'message' => 'Reaction removed',
            'reactions' => $reactions
        ));
    }

    /* ============================================
       POLLS
       ============================================ */

    public function create_poll() {
        check_ajax_referer('myavana_nonce', 'nonce');

        $post_id = intval($_POST['post_id']);
        $question = sanitize_text_field($_POST['question']);
        $options = array_map('sanitize_text_field', $_POST['options'] ?? array());
        $allow_multiple = intval($_POST['allow_multiple'] ?? 0);
        $duration_hours = intval($_POST['duration_hours'] ?? 24);

        if (count($options) < 2) {
            wp_send_json_error('At least 2 options required');
            return;
        }

        global $wpdb;
        $polls_table = $wpdb->prefix . 'upm_cm_polls';
        $options_table = $wpdb->prefix . 'upm_cm_poll_options';

        $ends_at = date('Y-m-d H:i:s', strtotime("+{$duration_hours} hours"));

        // Create poll
        $result = $wpdb->insert(
            $polls_table,
            array(
                'post_id' => $post_id,
                'question' => $question,
                'allow_multiple' => $allow_multiple,
                'ends_at' => $ends_at
            ),
            array('%d', '%s', '%d', '%s')
        );

        if ($result) {
            $poll_id = $wpdb->insert_id;

            // Create options
            foreach ($options as $option_text) {
                $wpdb->insert(
                    $options_table,
                    array(
                        'poll_id' => $poll_id,
                        'option_text' => $option_text
                    ),
                    array('%d', '%s')
                );
            }

            wp_send_json_success(array(
                'message' => 'Poll created',
                'poll_id' => $poll_id
            ));
        } else {
            wp_send_json_error('Failed to create poll');
        }
    }

    public function vote_poll() {
        check_ajax_referer('myavana_nonce', 'nonce');

        $poll_id = intval($_POST['poll_id']);
        $option_ids = array_map('intval', $_POST['option_ids'] ?? array());

        if (empty($option_ids)) {
            wp_send_json_error('No options selected');
            return;
        }

        global $wpdb;
        $votes_table = $wpdb->prefix . 'upm_cm_poll_votes';
        $options_table = $wpdb->prefix . 'upm_cm_poll_options';

        // Remove previous votes
        $wpdb->delete(
            $votes_table,
            array('poll_id' => $poll_id, 'user_id' => $this->user_id),
            array('%d', '%d')
        );

        // Add new votes
        foreach ($option_ids as $option_id) {
            $wpdb->insert(
                $votes_table,
                array(
                    'poll_id' => $poll_id,
                    'option_id' => $option_id,
                    'user_id' => $this->user_id
                ),
                array('%d', '%d', '%d')
            );

            // Update vote count
            $wpdb->query($wpdb->prepare(
                "UPDATE $options_table SET votes_count = votes_count + 1 WHERE id = %d",
                $option_id
            ));
        }

        wp_send_json_success('Vote recorded');
    }

    /* ============================================
       ENHANCED SEARCH
       ============================================ */

    public function search_users() {
        check_ajax_referer('myavana_nonce', 'nonce');

        $search_term = sanitize_text_field($_POST['search_term'] ?? '');
        $hair_type = sanitize_text_field($_POST['hair_type'] ?? '');
        $goal = sanitize_text_field($_POST['goal'] ?? '');
        $location = sanitize_text_field($_POST['location'] ?? '');

        if (strlen($search_term) < 2 && !$hair_type && !$goal && !$location) {
            wp_send_json_error('Search term too short');
            return;
        }

        global $wpdb;
        $users_table = $wpdb->users;
        $profiles_table = $wpdb->prefix . 'myavana_profiles';

        $sql = "SELECT u.ID, u.display_name, u.user_login, p.hair_type, p.location, p.hair_goals
                FROM $users_table u
                LEFT JOIN $profiles_table p ON u.ID = p.user_id
                WHERE 1=1";

        $params = array();

        if ($search_term) {
            $sql .= " AND (u.display_name LIKE %s OR u.user_login LIKE %s)";
            $params[] = '%' . $wpdb->esc_like($search_term) . '%';
            $params[] = '%' . $wpdb->esc_like($search_term) . '%';
        }

        if ($hair_type) {
            $sql .= " AND p.hair_type = %s";
            $params[] = $hair_type;
        }

        if ($location) {
            $sql .= " AND p.location LIKE %s";
            $params[] = '%' . $wpdb->esc_like($location) . '%';
        }

        $sql .= " LIMIT 50";

        $users = $wpdb->get_results($wpdb->prepare($sql, $params));

        // Add avatars
        foreach ($users as &$user) {
            $user->avatar = get_avatar_url($user->ID, 80);
        }

        wp_send_json_success($users);
    }

    /* ============================================
       AI RECOMMENDATIONS
       ============================================ */

    public function generate_recommendations() {
        check_ajax_referer('myavana_nonce', 'nonce');

        // This would integrate with AI service
        // For now, we'll create sample recommendations based on user data

        global $wpdb;
        $entries_table = $wpdb->prefix . 'hair_journey_entry';

        // Get user's recent entries
        $recent_entries = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $entries_table WHERE post_author = %d ORDER BY post_date DESC LIMIT 10",
            $this->user_id
        ));

        $recommendations = array();

        // Product recommendations (sample logic)
        $recommendations[] = array(
            'type' => 'product',
            'title' => 'Recommended Product',
            'description' => 'Based on your hair health trends',
            'confidence' => 0.85,
            'data' => array('product_id' => 123)
        );

        // Routine recommendations
        $recommendations[] = array(
            'type' => 'routine',
            'title' => 'Try this routine',
            'description' => 'Popular with users with similar hair type',
            'confidence' => 0.78,
            'data' => array('routine_id' => 456)
        );

        // Save recommendations to database
        $recs_table = $wpdb->prefix . 'upm_cm_ai_recommendations';
        foreach ($recommendations as $rec) {
            $wpdb->insert(
                $recs_table,
                array(
                    'user_id' => $this->user_id,
                    'recommendation_type' => $rec['type'],
                    'recommendation_data' => json_encode($rec),
                    'confidence_score' => $rec['confidence'],
                    'expires_at' => date('Y-m-d H:i:s', strtotime('+7 days'))
                ),
                array('%d', '%s', '%s', '%f', '%s')
            );
        }

        wp_send_json_success($recommendations);
    }

    public function get_recommendations() {
        check_ajax_referer('myavana_nonce', 'nonce');

        global $wpdb;
        $table = $wpdb->prefix . 'upm_cm_ai_recommendations';

        $recommendations = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table
             WHERE user_id = %d
             AND is_dismissed = 0
             AND (expires_at IS NULL OR expires_at > NOW())
             ORDER BY confidence_score DESC, created_at DESC
             LIMIT 10",
            $this->user_id
        ));

        foreach ($recommendations as &$rec) {
            $rec->recommendation_data = json_decode($rec->recommendation_data, true);
        }

        wp_send_json_success($recommendations);
    }

    /* ============================================
       JOURNEY EXPORT
       ============================================ */

    public function export_journey() {
        check_ajax_referer('myavana_nonce', 'nonce');

        $export_type = sanitize_text_field($_POST['export_type'] ?? 'full_journey');
        $export_format = sanitize_text_field($_POST['export_format'] ?? 'pdf');
        $date_range_start = sanitize_text_field($_POST['date_range_start'] ?? '');
        $date_range_end = sanitize_text_field($_POST['date_range_end'] ?? '');

        global $wpdb;
        $table = $wpdb->prefix . 'upm_cm_journey_exports';

        // Create export record
        $result = $wpdb->insert(
            $table,
            array(
                'user_id' => $this->user_id,
                'export_type' => $export_type,
                'export_format' => $export_format,
                'date_range_start' => $date_range_start ?: null,
                'date_range_end' => $date_range_end ?: null,
                'status' => 'processing'
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s')
        );

        if ($result) {
            $export_id = $wpdb->insert_id;

            // Queue background processing
            wp_schedule_single_event(time() + 10, 'upm_cm_process_journey_export', array($export_id));

            wp_send_json_success(array(
                'message' => 'Export started',
                'export_id' => $export_id
            ));
        } else {
            wp_send_json_error('Failed to start export');
        }
    }

    /* ============================================
       HELPER METHODS
       ============================================ */

    private function upm_cm_handle_media_upload($file, $type = 'image') {
        if (!function_exists('wp_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }

        $upload_overrides = array('test_form' => false);
        $uploaded = wp_handle_upload($file, $upload_overrides);

        if (isset($uploaded['error'])) {
            return new WP_Error('upload_error', $uploaded['error']);
        }

        return array(
            'url' => $uploaded['url'],
            'path' => $uploaded['file'],
            'type' => $uploaded['type']
        );
    }

    private function upm_cm_has_viewed_story($story_id, $user_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'upm_cm_story_views';

        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE story_id = %d AND user_id = %d",
            $story_id, $user_id
        ));
    }

    private function upm_cm_update_message_thread($user1_id, $user2_id, $message_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'upm_cm_message_threads';

        // Ensure user1_id is always the smaller ID for consistency
        if ($user1_id > $user2_id) {
            $temp = $user1_id;
            $user1_id = $user2_id;
            $user2_id = $temp;
        }

        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE user1_id = %d AND user2_id = %d",
            $user1_id, $user2_id
        ));

        if ($existing) {
            // Update existing thread
            $wpdb->update(
                $table,
                array(
                    'last_message_id' => $message_id,
                    'last_message_at' => current_time('mysql'),
                    'unread_count_user2' => $existing->unread_count_user2 + 1
                ),
                array('id' => $existing->id),
                array('%d', '%s', '%d'),
                array('%d')
            );
        } else {
            // Create new thread
            $wpdb->insert(
                $table,
                array(
                    'user1_id' => $user1_id,
                    'user2_id' => $user2_id,
                    'last_message_id' => $message_id,
                    'last_message_at' => current_time('mysql'),
                    'unread_count_user2' => 1
                ),
                array('%d', '%d', '%d', '%s', '%d')
            );
        }
    }

    private function upm_cm_is_group_member($group_id, $user_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'upm_cm_group_members';

        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE group_id = %d AND user_id = %d",
            $group_id, $user_id
        ));
    }

    private function upm_cm_get_reaction_counts($post_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'upm_cm_post_reactions';

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT reaction_type, COUNT(*) as count
             FROM $table
             WHERE post_id = %d
             GROUP BY reaction_type",
            $post_id
        ), ARRAY_A);

        $reactions = array();
        foreach ($results as $row) {
            $reactions[$row['reaction_type']] = intval($row['count']);
        }

        return $reactions;
    }

    public function cleanup_expired_stories() {
        global $wpdb;
        $table = $wpdb->prefix . 'upm_cm_stories';

        // Delete expired non-highlight stories
        $wpdb->query(
            "DELETE FROM $table WHERE is_highlight = 0 AND expires_at < NOW()"
        );
    }
}

// Initialize
new UPM_CM_Handlers();
