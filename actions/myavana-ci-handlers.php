<?php
/**
 * MYAVANA Community Improvements - AJAX Handlers
 *
 * All backend handlers for community improvement features
 * Naming convention: myavana_ci_* to avoid conflicts
 *
 * @package Myavana_Hair_Journey
 * @version 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AJAX Handler: Edit community post
 * Action: myavana_ci_edit_post
 */
function myavana_ci_edit_post_handler() {
    // Verify nonce
    check_ajax_referer('myavana_nonce', 'nonce');

    // Get current user
    $current_user_id = get_current_user_id();
    if (!$current_user_id) {
        wp_send_json_error('You must be logged in to edit posts');
    }

    // Get and validate inputs
    $post_id = absint($_POST['post_id'] ?? 0);

    if (!$post_id) {
        wp_send_json_error('Invalid post ID');
    }

    // Get post and verify ownership
    global $wpdb;
    $table_name = $wpdb->prefix . 'myavana_community_posts';
    $post = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE id = %d",
        $post_id
    ));

    if (!$post) {
        wp_send_json_error('Post not found');
    }

    if ($post->user_id != $current_user_id) {
        wp_send_json_error('You do not have permission to edit this post');
    }

    // Check if this is a pin/unpin operation
    if (isset($_POST['is_pinned'])) {
        $is_pinned = absint($_POST['is_pinned']);

        $updated = $wpdb->update(
            $table_name,
            ['is_pinned' => $is_pinned],
            ['id' => $post_id],
            ['%d'],
            ['%d']
        );

        if ($updated === false) {
            wp_send_json_error('Failed to update pin status');
        }

        wp_send_json_success([
            'message' => $is_pinned ? 'Post pinned' : 'Post unpinned',
            'is_pinned' => $is_pinned
        ]);
    }

    // Otherwise, update title and content
    $title = sanitize_text_field($_POST['title'] ?? '');
    $content = sanitize_textarea_field($_POST['content'] ?? '');

    if (!$title || !$content) {
        wp_send_json_error('Missing required fields');
    }

    // Update post
    $updated = $wpdb->update(
        $table_name,
        [
            'title' => $title,
            'content' => $content,
            'updated_at' => current_time('mysql')
        ],
        ['id' => $post_id],
        ['%s', '%s', '%s'],
        ['%d']
    );

    if ($updated === false) {
        wp_send_json_error('Failed to update post');
    }

    wp_send_json_success([
        'message' => 'Post updated successfully',
        'post' => [
            'id' => $post_id,
            'title' => $title,
            'content' => $content
        ]
    ]);
}
add_action('wp_ajax_myavana_ci_edit_post', 'myavana_ci_edit_post_handler');

/**
 * AJAX Handler: Delete community post
 * Action: myavana_ci_delete_post
 */
function myavana_ci_delete_post_handler() {
    check_ajax_referer('myavana_nonce', 'nonce');

    $current_user_id = get_current_user_id();
    if (!$current_user_id) {
        wp_send_json_error('You must be logged in');
    }

    $post_id = absint($_POST['post_id'] ?? 0);
    if (!$post_id) {
        wp_send_json_error('Invalid post ID');
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'myavana_community_posts';

    // Verify ownership
    $post = $wpdb->get_row($wpdb->prepare(
        "SELECT user_id FROM $table_name WHERE id = %d",
        $post_id
    ));

    if (!$post || $post->user_id != $current_user_id) {
        wp_send_json_error('Permission denied');
    }

    // Delete related data first
    $comments_table = $wpdb->prefix . 'myavana_community_comments';
    $reactions_table = $wpdb->prefix . 'myavana_ci_post_reactions';
    $bookmarks_table = $wpdb->prefix . 'myavana_community_bookmarks';

    $wpdb->delete($comments_table, ['post_id' => $post_id], ['%d']);
    $wpdb->delete($reactions_table, ['post_id' => $post_id], ['%d']);
    $wpdb->delete($bookmarks_table, ['post_id' => $post_id], ['%d']);

    // Delete post
    $deleted = $wpdb->delete($table_name, ['id' => $post_id], ['%d']);

    if ($deleted) {
        wp_send_json_success(['message' => 'Post deleted successfully']);
    } else {
        wp_send_json_error('Failed to delete post');
    }
}
add_action('wp_ajax_myavana_ci_delete_post', 'myavana_ci_delete_post_handler');

/**
 * AJAX Handler: Like/Unlike comment
 * Action: myavana_ci_like_comment
 */
function myavana_ci_like_comment_handler() {
    check_ajax_referer('myavana_nonce', 'nonce');

    $current_user_id = get_current_user_id();
    if (!$current_user_id) {
        wp_send_json_error('You must be logged in');
    }

    $comment_id = absint($_POST['comment_id'] ?? 0);
    if (!$comment_id) {
        wp_send_json_error('Invalid comment ID');
    }

    global $wpdb;
    $likes_table = $wpdb->prefix . 'myavana_ci_comment_likes';

    // Check if already liked
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $likes_table WHERE comment_id = %d AND user_id = %d",
        $comment_id,
        $current_user_id
    ));

    if ($existing) {
        // Unlike
        $wpdb->delete(
            $likes_table,
            ['comment_id' => $comment_id, 'user_id' => $current_user_id],
            ['%d', '%d']
        );
        $action = 'unliked';
    } else {
        // Like
        $wpdb->insert(
            $likes_table,
            [
                'comment_id' => $comment_id,
                'user_id' => $current_user_id,
                'created_at' => current_time('mysql')
            ],
            ['%d', '%d', '%s']
        );
        $action = 'liked';
    }

    // Get updated count
    $likes_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $likes_table WHERE comment_id = %d",
        $comment_id
    ));

    wp_send_json_success([
        'action' => $action,
        'likes_count' => (int)$likes_count
    ]);
}
add_action('wp_ajax_myavana_ci_like_comment', 'myavana_ci_like_comment_handler');

/**
 * AJAX Handler: React to post (like, love, celebrate, insightful)
 * Action: myavana_ci_react_to_post
 */
function myavana_ci_react_to_post_handler() {
    check_ajax_referer('myavana_nonce', 'nonce');

    $current_user_id = get_current_user_id();
    if (!$current_user_id) {
        wp_send_json_error('You must be logged in');
    }

    $post_id = absint($_POST['post_id'] ?? 0);
    $reaction_type = sanitize_text_field($_POST['reaction_type'] ?? '');

    // Valid reaction types
    $valid_reactions = ['like', 'love', 'celebrate', 'insightful'];
    if (!$post_id || !in_array($reaction_type, $valid_reactions)) {
        wp_send_json_error('Invalid parameters');
    }

    global $wpdb;
    $reactions_table = $wpdb->prefix . 'myavana_ci_post_reactions';

    // Check existing reaction
    $existing = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $reactions_table WHERE post_id = %d AND user_id = %d",
        $post_id,
        $current_user_id
    ));

    if ($existing) {
        if ($existing->reaction_type === $reaction_type) {
            // Remove reaction (toggle off)
            $wpdb->delete(
                $reactions_table,
                ['post_id' => $post_id, 'user_id' => $current_user_id],
                ['%d', '%d']
            );
            $action = 'removed';
        } else {
            // Update to new reaction
            $wpdb->update(
                $reactions_table,
                ['reaction_type' => $reaction_type, 'created_at' => current_time('mysql')],
                ['post_id' => $post_id, 'user_id' => $current_user_id],
                ['%s', '%s'],
                ['%d', '%d']
            );
            $action = 'changed';
        }
    } else {
        // Add new reaction
        $wpdb->insert(
            $reactions_table,
            [
                'post_id' => $post_id,
                'user_id' => $current_user_id,
                'reaction_type' => $reaction_type,
                'created_at' => current_time('mysql')
            ],
            ['%d', '%d', '%s', '%s']
        );
        $action = 'added';
    }

    // Get all reaction counts for this post
    $reactions = $wpdb->get_results($wpdb->prepare(
        "SELECT reaction_type, COUNT(*) as count
         FROM $reactions_table
         WHERE post_id = %d
         GROUP BY reaction_type",
        $post_id
    ), ARRAY_A);

    $counts = [];
    foreach ($reactions as $r) {
        $counts[$r['reaction_type']] = (int)$r['count'];
    }

    // Check if current user has any reaction
    $user_reaction = $wpdb->get_var($wpdb->prepare(
        "SELECT reaction_type FROM $reactions_table
         WHERE post_id = %d AND user_id = %d",
        $post_id,
        $current_user_id
    ));

    // Ensure all reaction types are represented
    $all_reactions = ['like' => 0, 'love' => 0, 'celebrate' => 0, 'insightful' => 0];
    $reactions = array_merge($all_reactions, $counts);

    wp_send_json_success([
        'action' => $action,
        'reactions' => $reactions,
        'user_reaction' => $user_reaction,
        'total_count' => array_sum($reactions)
    ]);
}
add_action('wp_ajax_myavana_ci_react_to_post', 'myavana_ci_react_to_post_handler');

/**
 * AJAX Handler: Reply to comment (threaded comments)
 * Action: myavana_ci_reply_to_comment
 */
function myavana_ci_reply_to_comment_handler() {
    check_ajax_referer('myavana_nonce', 'nonce');

    $current_user_id = get_current_user_id();
    if (!$current_user_id) {
        wp_send_json_error('You must be logged in');
    }

    $post_id = absint($_POST['post_id'] ?? 0);
    $parent_comment_id = absint($_POST['parent_comment_id'] ?? 0);
    $content = sanitize_textarea_field($_POST['content'] ?? '');

    if (!$post_id || !$parent_comment_id || !$content) {
        wp_send_json_error('Missing required fields');
    }

    global $wpdb;
    $comments_table = $wpdb->prefix . 'myavana_community_comments';

    // Verify parent comment exists
    $parent_exists = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $comments_table WHERE id = %d AND post_id = %d",
        $parent_comment_id,
        $post_id
    ));

    if (!$parent_exists) {
        wp_send_json_error('Parent comment not found');
    }

    // Insert reply
    $inserted = $wpdb->insert(
        $comments_table,
        [
            'post_id' => $post_id,
            'user_id' => $current_user_id,
            'parent_id' => $parent_comment_id,
            'content' => $content,
            'created_at' => current_time('mysql')
        ],
        ['%d', '%d', '%d', '%s', '%s']
    );

    if (!$inserted) {
        wp_send_json_error('Failed to add reply');
    }

    $comment_id = $wpdb->insert_id;

    // Get user info
    $user = get_userdata($current_user_id);

    wp_send_json_success([
        'message' => 'Reply added successfully',
        'comment' => [
            'id' => $comment_id,
            'post_id' => $post_id,
            'parent_id' => $parent_comment_id,
            'user_id' => $current_user_id,
            'display_name' => $user->display_name,
            'user_avatar' => get_avatar_url($current_user_id),
            'content' => $content,
            'likes_count' => 0,
            'is_liked' => false,
            'formatted_date' => 'Just now'
        ]
    ]);
}
add_action('wp_ajax_myavana_ci_reply_to_comment', 'myavana_ci_reply_to_comment_handler');

/**
 * AJAX Handler: Report inappropriate content
 * Action: myavana_ci_report_content
 */
function myavana_ci_report_content_handler() {
    check_ajax_referer('myavana_nonce', 'nonce');

    $current_user_id = get_current_user_id();
    if (!$current_user_id) {
        wp_send_json_error('You must be logged in');
    }

    $content_type = sanitize_text_field($_POST['content_type'] ?? '');
    $content_id = absint($_POST['content_id'] ?? 0);
    $reason = sanitize_text_field($_POST['reason'] ?? '');
    $details = sanitize_textarea_field($_POST['details'] ?? '');

    if (!$content_type || !$content_id || !$reason) {
        wp_send_json_error('Missing required fields');
    }

    if (!in_array($content_type, ['post', 'comment'])) {
        wp_send_json_error('Invalid content type');
    }

    global $wpdb;
    $reports_table = $wpdb->prefix . 'myavana_ci_content_reports';

    // Check if already reported by this user
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $reports_table
         WHERE content_type = %s AND content_id = %d AND reporter_user_id = %d",
        $content_type,
        $content_id,
        $current_user_id
    ));

    if ($existing) {
        wp_send_json_error('You have already reported this content');
    }

    // Insert report
    $inserted = $wpdb->insert(
        $reports_table,
        [
            'content_type' => $content_type,
            'content_id' => $content_id,
            'reporter_user_id' => $current_user_id,
            'reason' => $reason,
            'details' => $details,
            'status' => 'pending',
            'created_at' => current_time('mysql')
        ],
        ['%s', '%d', '%d', '%s', '%s', '%s', '%s']
    );

    if (!$inserted) {
        wp_send_json_error('Failed to submit report');
    }

    wp_send_json_success([
        'message' => 'Thank you for your report. We will review it shortly.'
    ]);
}
add_action('wp_ajax_myavana_ci_report_content', 'myavana_ci_report_content_handler');

/**
 * AJAX Handler: Load more comments with pagination
 * Action: myavana_ci_load_comments
 */
function myavana_ci_load_comments_handler() {
    check_ajax_referer('myavana_nonce', 'nonce');

    $post_id = absint($_POST['post_id'] ?? 0);
    $page = absint($_POST['page'] ?? 1);
    $per_page = absint($_POST['per_page'] ?? 10);
    $offset = ($page - 1) * $per_page;

    if (!$post_id) {
        wp_send_json_error('Invalid post ID');
    }

    global $wpdb;
    $current_user_id = get_current_user_id();
    $comments_table = $wpdb->prefix . 'myavana_community_comments';
    $likes_table = $wpdb->prefix . 'myavana_ci_comment_likes';

    // Get comments (only top-level)
    $comments = $wpdb->get_results($wpdb->prepare(
        "SELECT c.*, u.display_name,
         (SELECT COUNT(*) FROM $likes_table WHERE comment_id = c.id) as likes_count,
         (SELECT COUNT(*) FROM $likes_table WHERE comment_id = c.id AND user_id = %d) as is_liked,
         (SELECT COUNT(*) FROM $comments_table WHERE parent_id = c.id) as replies_count
         FROM $comments_table c
         LEFT JOIN {$wpdb->users} u ON c.user_id = u.ID
         WHERE c.post_id = %d AND (c.parent_id = 0 OR c.parent_id IS NULL)
         ORDER BY c.created_at DESC
         LIMIT %d OFFSET %d",
        $current_user_id,
        $post_id,
        $per_page,
        $offset
    ), ARRAY_A);

    // Get total count
    $total = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $comments_table
         WHERE post_id = %d AND (parent_id = 0 OR parent_id IS NULL)",
        $post_id
    ));

    // Format comments
    $formatted_comments = [];
    foreach ($comments as $comment) {
        $formatted_comments[] = [
            'id' => (int)$comment['id'],
            'post_id' => (int)$post_id,
            'user_id' => (int)$comment['user_id'],
            'display_name' => $comment['display_name'],
            'user_avatar' => get_avatar_url($comment['user_id']),
            'content' => $comment['content'],
            'likes_count' => (int)$comment['likes_count'],
            'is_liked' => (bool)$comment['is_liked'],
            'reply_count' => (int)$comment['replies_count'],
            'formatted_date' => human_time_diff(strtotime($comment['created_at']), current_time('timestamp')) . ' ago'
        ];
    }

    wp_send_json_success([
        'comments' => $formatted_comments,
        'has_more' => ($offset + $per_page) < $total,
        'total_count' => (int)$total
    ]);
}
add_action('wp_ajax_myavana_ci_load_comments', 'myavana_ci_load_comments_handler');
add_action('wp_ajax_nopriv_myavana_ci_load_comments', 'myavana_ci_load_comments_handler');

/**
 * AJAX Handler: Get comment replies
 * Action: myavana_ci_get_replies
 */
function myavana_ci_get_replies_handler() {
    check_ajax_referer('myavana_nonce', 'nonce');

    $comment_id = absint($_POST['comment_id'] ?? 0);

    if (!$comment_id) {
        wp_send_json_error('Invalid comment ID');
    }

    global $wpdb;
    $current_user_id = get_current_user_id();
    $comments_table = $wpdb->prefix . 'myavana_community_comments';
    $likes_table = $wpdb->prefix . 'myavana_ci_comment_likes';

    // Get replies
    $replies = $wpdb->get_results($wpdb->prepare(
        "SELECT c.*, u.display_name,
         (SELECT COUNT(*) FROM $likes_table WHERE comment_id = c.id) as likes_count,
         (SELECT COUNT(*) FROM $likes_table WHERE comment_id = c.id AND user_id = %d) as is_liked
         FROM $comments_table c
         LEFT JOIN {$wpdb->users} u ON c.user_id = u.ID
         WHERE c.parent_id = %d
         ORDER BY c.created_at ASC",
        $current_user_id,
        $comment_id
    ), ARRAY_A);

    // Format replies
    $formatted_replies = [];
    foreach ($replies as $reply) {
        $formatted_replies[] = [
            'id' => (int)$reply['id'],
            'user_id' => (int)$reply['user_id'],
            'display_name' => $reply['display_name'],
            'user_avatar' => get_avatar_url($reply['user_id']),
            'content' => $reply['content'],
            'likes_count' => (int)$reply['likes_count'],
            'is_liked' => (bool)$reply['is_liked'],
            'formatted_date' => human_time_diff(strtotime($reply['created_at']), current_time('timestamp')) . ' ago'
        ];
    }

    wp_send_json_success([
        'replies' => $formatted_replies
    ]);
}
add_action('wp_ajax_myavana_ci_get_replies', 'myavana_ci_get_replies_handler');
add_action('wp_ajax_nopriv_myavana_ci_get_replies', 'myavana_ci_get_replies_handler');

/**
 * AJAX Handler: Manage bookmark collections
 * Action: myavana_ci_manage_collection
 */
function myavana_ci_manage_collection_handler() {
    check_ajax_referer('myavana_nonce', 'nonce');

    $current_user_id = get_current_user_id();
    if (!$current_user_id) {
        wp_send_json_error('You must be logged in');
    }

    $action_type = sanitize_text_field($_POST['action_type'] ?? '');
    $collection_id = absint($_POST['collection_id'] ?? 0);
    $collection_name = sanitize_text_field($_POST['collection_name'] ?? '');
    $post_id = absint($_POST['post_id'] ?? 0);

    global $wpdb;
    $collections_table = $wpdb->prefix . 'myavana_ci_bookmark_collections';
    $collection_items_table = $wpdb->prefix . 'myavana_ci_collection_items';

    switch ($action_type) {
        case 'create':
            if (!$collection_name) {
                wp_send_json_error('Collection name is required');
            }

            $inserted = $wpdb->insert(
                $collections_table,
                [
                    'user_id' => $current_user_id,
                    'name' => $collection_name,
                    'created_at' => current_time('mysql')
                ],
                ['%d', '%s', '%s']
            );

            if ($inserted) {
                wp_send_json_success([
                    'collection_id' => $wpdb->insert_id,
                    'name' => $collection_name
                ]);
            } else {
                wp_send_json_error('Failed to create collection');
            }
            break;

        case 'delete':
            $owner = $wpdb->get_var($wpdb->prepare(
                "SELECT user_id FROM $collections_table WHERE id = %d",
                $collection_id
            ));

            if ($owner != $current_user_id) {
                wp_send_json_error('Permission denied');
            }

            $wpdb->delete($collection_items_table, ['collection_id' => $collection_id], ['%d']);
            $wpdb->delete($collections_table, ['id' => $collection_id], ['%d']);

            wp_send_json_success(['message' => 'Collection deleted']);
            break;

        case 'add_post':
            $owner = $wpdb->get_var($wpdb->prepare(
                "SELECT user_id FROM $collections_table WHERE id = %d",
                $collection_id
            ));

            if ($owner != $current_user_id) {
                wp_send_json_error('Permission denied');
            }

            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $collection_items_table
                 WHERE collection_id = %d AND post_id = %d",
                $collection_id,
                $post_id
            ));

            if ($exists) {
                wp_send_json_error('Post already in this collection');
            }

            $wpdb->insert(
                $collection_items_table,
                [
                    'collection_id' => $collection_id,
                    'post_id' => $post_id,
                    'added_at' => current_time('mysql')
                ],
                ['%d', '%d', '%s']
            );

            wp_send_json_success(['message' => 'Added to collection']);
            break;

        case 'remove_post':
            $wpdb->delete(
                $collection_items_table,
                ['collection_id' => $collection_id, 'post_id' => $post_id],
                ['%d', '%d']
            );

            wp_send_json_success(['message' => 'Removed from collection']);
            break;

        case 'list':
            $collections = $wpdb->get_results($wpdb->prepare(
                "SELECT c.*, COUNT(ci.id) as post_count
                 FROM $collections_table c
                 LEFT JOIN $collection_items_table ci ON c.id = ci.collection_id
                 WHERE c.user_id = %d
                 GROUP BY c.id
                 ORDER BY c.created_at DESC",
                $current_user_id
            ), ARRAY_A);

            wp_send_json_success(['collections' => $collections]);
            break;

        default:
            wp_send_json_error('Invalid action type');
    }
}
add_action('wp_ajax_myavana_ci_manage_collection', 'myavana_ci_manage_collection_handler');

/**
 * AJAX Handler: Get post analytics
 * Action: myavana_ci_get_post_analytics
 */
function myavana_ci_get_post_analytics_handler() {
    check_ajax_referer('myavana_nonce', 'nonce');

    $current_user_id = get_current_user_id();
    if (!$current_user_id) {
        wp_send_json_error('You must be logged in');
    }

    $post_id = absint($_POST['post_id'] ?? 0);
    $analytics_type = sanitize_text_field($_POST['analytics_type'] ?? '');

    if (!$post_id) {
        wp_send_json_error('Invalid post ID');
    }

    global $wpdb;
    $posts_table = $wpdb->prefix . 'myavana_community_posts';
    $post_owner = $wpdb->get_var($wpdb->prepare(
        "SELECT user_id FROM $posts_table WHERE id = %d",
        $post_id
    ));

    if ($post_owner != $current_user_id) {
        wp_send_json_error('You can only view analytics for your own posts');
    }

    $data = [];

    switch ($analytics_type) {
        case 'reactions':
            $reactions_table = $wpdb->prefix . 'myavana_ci_post_reactions';
            $reactions = $wpdb->get_results($wpdb->prepare(
                "SELECT r.reaction_type, r.created_at, u.display_name, u.ID as user_id
                 FROM $reactions_table r
                 LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID
                 WHERE r.post_id = %d
                 ORDER BY r.created_at DESC
                 LIMIT 100",
                $post_id
            ), ARRAY_A);

            foreach ($reactions as &$reaction) {
                $reaction['user_avatar'] = get_avatar_url($reaction['user_id']);
                $reaction['formatted_date'] = human_time_diff(strtotime($reaction['created_at']), current_time('timestamp')) . ' ago';
            }

            $data['reactions'] = $reactions;
            break;

        default:
            wp_send_json_error('Invalid analytics type');
    }

    wp_send_json_success($data);
}
add_action('wp_ajax_myavana_ci_get_post_analytics', 'myavana_ci_get_post_analytics_handler');

/**
 * AJAX Handler: Manage draft posts
 * Action: myavana_ci_manage_draft
 */
function myavana_ci_manage_draft_handler() {
    check_ajax_referer('myavana_nonce', 'nonce');

    $current_user_id = get_current_user_id();
    if (!$current_user_id) {
        wp_send_json_error('You must be logged in');
    }

    $action_type = sanitize_text_field($_POST['action_type'] ?? '');
    $draft_id = absint($_POST['draft_id'] ?? 0);

    global $wpdb;
    $drafts_table = $wpdb->prefix . 'myavana_ci_post_drafts';

    switch ($action_type) {
        case 'save':
            $title = sanitize_text_field($_POST['title'] ?? '');
            $content = sanitize_textarea_field($_POST['content'] ?? '');
            $post_type = sanitize_text_field($_POST['post_type'] ?? 'general');

            if ($draft_id) {
                $wpdb->update(
                    $drafts_table,
                    [
                        'title' => $title,
                        'content' => $content,
                        'post_type' => $post_type,
                        'updated_at' => current_time('mysql')
                    ],
                    ['id' => $draft_id, 'user_id' => $current_user_id],
                    ['%s', '%s', '%s', '%s'],
                    ['%d', '%d']
                );

                wp_send_json_success([
                    'draft_id' => $draft_id,
                    'message' => 'Draft updated'
                ]);
            } else {
                $wpdb->insert(
                    $drafts_table,
                    [
                        'user_id' => $current_user_id,
                        'title' => $title,
                        'content' => $content,
                        'post_type' => $post_type,
                        'created_at' => current_time('mysql'),
                        'updated_at' => current_time('mysql')
                    ],
                    ['%d', '%s', '%s', '%s', '%s', '%s']
                );

                wp_send_json_success([
                    'draft_id' => $wpdb->insert_id,
                    'message' => 'Draft saved'
                ]);
            }
            break;

        case 'load':
            $drafts = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $drafts_table
                 WHERE user_id = %d
                 ORDER BY updated_at DESC",
                $current_user_id
            ), ARRAY_A);

            wp_send_json_success(['drafts' => $drafts]);
            break;

        case 'delete':
            $wpdb->delete(
                $drafts_table,
                ['id' => $draft_id, 'user_id' => $current_user_id],
                ['%d', '%d']
            );

            wp_send_json_success(['message' => 'Draft deleted']);
            break;

        case 'publish':
            $draft = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $drafts_table
                 WHERE id = %d AND user_id = %d",
                $draft_id,
                $current_user_id
            ), ARRAY_A);

            if (!$draft) {
                wp_send_json_error('Draft not found');
            }

            $posts_table = $wpdb->prefix . 'myavana_community_posts';
            $wpdb->insert(
                $posts_table,
                [
                    'user_id' => $current_user_id,
                    'title' => $draft['title'],
                    'content' => $draft['content'],
                    'post_type' => $draft['post_type'],
                    'created_at' => current_time('mysql')
                ],
                ['%d', '%s', '%s', '%s', '%s']
            );

            $post_id = $wpdb->insert_id;
            $wpdb->delete($drafts_table, ['id' => $draft_id], ['%d']);

            wp_send_json_success([
                'post_id' => $post_id,
                'message' => 'Draft published successfully'
            ]);
            break;

        default:
            wp_send_json_error('Invalid action type');
    }
}
add_action('wp_ajax_myavana_ci_manage_draft', 'myavana_ci_manage_draft_handler');

/**
 * AJAX Handler: Check for new activity
 * Action: myavana_ci_check_activity
 */
function myavana_ci_check_activity_handler() {
    // Verify nonce
    check_ajax_referer('myavana_nonce', 'nonce');

    // Get current user
    $current_user_id = get_current_user_id();
    if (!$current_user_id) {
        wp_send_json_error('You must be logged in');
    }

    // Get last check timestamp
    $last_check = absint($_POST['last_check'] ?? 0);
    $filter = sanitize_text_field($_POST['filter'] ?? 'all');

    global $wpdb;
    $posts_table = $wpdb->prefix . 'myavana_community_posts';

    // Build query based on filter
    $where_clauses = ["p.created_at > FROM_UNIXTIME(%d)"];
    $query_args = [$last_check];

    switch ($filter) {
        case 'following':
            $following_ids = get_user_meta($current_user_id, 'myavana_following', true);
            if (empty($following_ids)) {
                wp_send_json_success([
                    'has_new_activity' => false,
                    'count' => 0
                ]);
                return;
            }
            $placeholders = implode(',', array_fill(0, count($following_ids), '%d'));
            $where_clauses[] = "p.user_id IN ($placeholders)";
            $query_args = array_merge($query_args, $following_ids);
            break;

        case 'my-posts':
            $where_clauses[] = "p.user_id = %d";
            $query_args[] = $current_user_id;
            break;

        case 'all':
        default:
            // No additional filter needed
            break;
    }

    $where_sql = implode(' AND ', $where_clauses);

    $count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $posts_table p WHERE $where_sql",
        ...$query_args
    ));

    wp_send_json_success([
        'has_new_activity' => $count > 0,
        'count' => intval($count)
    ]);
}
add_action('wp_ajax_myavana_ci_check_activity', 'myavana_ci_check_activity_handler');

/**
 * AJAX Handler: Get user community posts for profile page
 * Action: get_user_community_posts
 */
function myavana_get_user_community_posts_handler() {
    // Verify nonce
    check_ajax_referer('myavana_ajax_nonce', 'nonce');

    // Get current user
    $current_user_id = get_current_user_id();
    if (!$current_user_id) {
        wp_send_json_error('You must be logged in');
    }

    // Get user ID (can view own or others' posts)
    $user_id = absint($_POST['user_id'] ?? $current_user_id);

    global $wpdb;
    $posts_table = $wpdb->prefix . 'myavana_community_posts';
    $likes_table = $wpdb->prefix . 'myavana_post_likes';
    $comments_table = $wpdb->prefix . 'myavana_post_comments';

    // Get user's posts
    $posts = $wpdb->get_results($wpdb->prepare(
        "SELECT id, user_id, content, image_url, visibility, created_at
         FROM $posts_table
         WHERE user_id = %d AND status = 'published'
         ORDER BY created_at DESC
         LIMIT 50",
        $user_id
    ));

    // Add engagement counts for each post
    foreach ($posts as &$post) {
        $post->likes_count = intval($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $likes_table WHERE post_id = %d",
            $post->id
        )));

        $post->comments_count = intval($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $comments_table WHERE post_id = %d",
            $post->id
        )));

        $post->formatted_date = human_time_diff(strtotime($post->created_at)) . ' ago';
    }

    wp_send_json_success($posts);
}
add_action('wp_ajax_get_user_community_posts', 'myavana_get_user_community_posts_handler');

/**
 * AJAX Handler: Save profile settings
 * Action: save_profile_settings
 */
function myavana_save_profile_settings_handler() {
    // Verify nonce
    check_ajax_referer('myavana_ajax_nonce', 'nonce');

    // Get current user
    $current_user_id = get_current_user_id();
    if (!$current_user_id) {
        wp_send_json_error('You must be logged in');
    }

    // Get settings from POST
    $settings = $_POST['settings'] ?? [];

    if (empty($settings)) {
        wp_send_json_error('No settings provided');
    }

    // Save each setting to user meta
    $allowed_settings = [
        'profileVisibility',
        'showActivityStatus',
        'emailNotifications',
        'communityNotifications'
    ];

    foreach ($allowed_settings as $setting_key) {
        if (isset($settings[$setting_key])) {
            $value = $settings[$setting_key];
            // Convert boolean values
            if ($value === 'true' || $value === true) {
                $value = 1;
            } elseif ($value === 'false' || $value === false) {
                $value = 0;
            }
            update_user_meta($current_user_id, 'myavana_' . $setting_key, $value);
        }
    }

    wp_send_json_success('Settings saved successfully');
}
add_action('wp_ajax_save_profile_settings', 'myavana_save_profile_settings_handler');

/**
 * AJAX Handler: Export user data
 * Action: export_user_data
 */
function myavana_export_user_data_handler() {
    // Verify nonce
    if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'myavana_ajax_nonce')) {
        wp_die('Security check failed');
    }

    // Get current user
    $current_user_id = get_current_user_id();
    if (!$current_user_id) {
        wp_die('You must be logged in');
    }

    global $wpdb;

    // Gather all user data
    $export_data = [];

    // User info
    $user = get_userdata($current_user_id);
    $export_data['user_info'] = [
        'username' => $user->user_login,
        'email' => $user->user_email,
        'display_name' => $user->display_name,
        'registered' => $user->user_registered
    ];

    // Hair journey entries
    $entries_args = [
        'post_type' => 'hair_journey_entry',
        'author' => $current_user_id,
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'orderby' => 'post_date',
        'order' => 'DESC',
    ];
    $entries = get_posts($entries_args);
    $export_data['hair_journey_entries'] = [];

    foreach ($entries as $entry) {
        $export_data['hair_journey_entries'][] = [
            'title' => $entry->post_title,
            'content' => $entry->post_content,
            'date' => $entry->post_date,
            'health_rating' => get_post_meta($entry->ID, 'health_rating', true),
            'mood' => get_post_meta($entry->ID, 'mood_demeanor', true),
            'products' => get_post_meta($entry->ID, 'products_used', true)
        ];
    }

    // Community posts
    $posts_table = $wpdb->prefix . 'myavana_community_posts';
    $community_posts = $wpdb->get_results($wpdb->prepare(
        "SELECT content, image_url, visibility, created_at
         FROM $posts_table
         WHERE user_id = %d",
        $current_user_id
    ), ARRAY_A);
    $export_data['community_posts'] = $community_posts;

    // Goals
    $export_data['goals'] = get_user_meta($current_user_id, 'myavana_hair_goals_structured', true) ?: [];

    // Routine
    $export_data['routine'] = get_user_meta($current_user_id, 'myavana_current_routine', true) ?: [];

    // Hair profile
    $profile_table = $wpdb->prefix . 'myavana_profiles';
    $hair_profile = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $profile_table WHERE user_id = %d",
        $current_user_id
    ), ARRAY_A);
    $export_data['hair_profile'] = $hair_profile;

    // Create JSON file
    $json_data = json_encode($export_data, JSON_PRETTY_PRINT);
    $filename = 'myavana-data-export-' . $current_user_id . '-' . date('Y-m-d') . '.json';

    // Set headers for download
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($json_data));
    header('Cache-Control: must-revalidate');
    header('Pragma: public');

    echo $json_data;
    exit;
}
add_action('wp_ajax_export_user_data', 'myavana_export_user_data_handler');
