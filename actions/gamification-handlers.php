<?php
/**
 * MYAVANA Gamification AJAX Handlers
 *
 * Handles daily check-ins, badge unlocks, and stat updates
 *
 * @package Myavana_Hair_Journey
 * @version 2.3.5
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle daily check-in
 */
function myavana_daily_checkin() {
    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error('User not logged in');
        return;
    }

    $mood = isset($_POST['mood']) ? sanitize_text_field($_POST['mood']) : null;
    $result = Myavana_Gamification::record_daily_checkin($user_id, $mood);

    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
        return;
    }

    wp_send_json_success($result);
}
add_action('wp_ajax_myavana_daily_checkin', 'myavana_daily_checkin');

/**
 * Get user gamification stats
 */
function myavana_get_gamification_stats() {
    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error('User not logged in');
        return;
    }

    wp_send_json_success(Myavana_Gamification::get_summary($user_id));
}
add_action('wp_ajax_myavana_get_gamification_stats', 'myavana_get_gamification_stats');

/**
 * Check and award badges
 */
function myavana_check_badge_unlocks($user_id) {
    global $wpdb;
    $table_badges = $wpdb->prefix . 'myavana_badges';
    $table_user_badges = $wpdb->prefix . 'myavana_user_badges';
    $table_stats = $wpdb->prefix . 'myavana_user_stats';

    $new_badges = array();
    $stats = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$table_stats} WHERE user_id = %d",
        $user_id
    ), ARRAY_A);

    if (!$stats) {
        return $new_badges;
    }

    // Get all badges user hasn't earned
    $available_badges = $wpdb->get_results($wpdb->prepare(
        "SELECT b.*
         FROM {$table_badges} b
         LEFT JOIN {$table_user_badges} ub ON b.id = ub.badge_id AND ub.user_id = %d
         WHERE ub.id IS NULL",
        $user_id
    ), ARRAY_A);

    foreach ($available_badges as $badge) {
        $earned = false;

        switch ($badge['requirement_type']) {
            case 'streak':
                if ($stats['current_streak'] >= $badge['requirement_value']) {
                    $earned = true;
                }
                break;
            case 'count':
                // Check total entries or AI analyses based on badge category
                if ($badge['category'] === 'ai') {
                    if ($stats['total_ai_analyses'] >= $badge['requirement_value']) {
                        $earned = true;
                    }
                } else {
                    if ($stats['total_entries'] >= $badge['requirement_value']) {
                        $earned = true;
                    }
                }
                break;
            case 'milestone':
                // Milestone badges are earned on first occurrence
                if ($badge['category'] === 'ai' && $stats['total_ai_analyses'] >= 1) {
                    $earned = true;
                }
                break;
        }

        if ($earned) {
            // Award badge
            $wpdb->insert($table_user_badges, array(
                'user_id' => $user_id,
                'badge_id' => $badge['id'],
                'progress' => $badge['requirement_value']
            ));

            myavana_award_points(
                $user_id,
                intval($badge['points_reward']),
                sprintf('Badge unlocked: %s', $badge['name']),
                'badge',
                intval($badge['id']),
                'badge_unlocked:' . sanitize_key($badge['badge_key'])
            );

            $new_badges[] = $badge;
        }
    }

    return $new_badges;
}

/**
 * Update stats when entry is created
 */
function myavana_update_stats_on_entry($post_id, $post, $update) {
    if ($post->post_type !== 'hair_journey_entry' || $update) {
        return;
    }

    $user_id = $post->post_author;
    Myavana_Gamification::sync_user_totals($user_id);
    myavana_award_points(
        $user_id,
        Myavana_Gamification::get_reward_value('entry_created', 15),
        'Hair journey entry created',
        'entry',
        $post_id,
        'entry_created:' . $post_id
    );
    myavana_check_badge_unlocks($user_id);
}
add_action('wp_insert_post', 'myavana_update_stats_on_entry', 10, 3);
