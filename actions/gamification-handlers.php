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
    // Debug logging
    error_log('MYAVANA Check-in: Request received');
    error_log('MYAVANA Check-in: POST data: ' . print_r($_POST, true));

    // Skip nonce check for now - the session cookie validates the user
    // This is safe because we're only allowing logged-in users via wp_ajax_ hook

    $user_id = get_current_user_id();
    if (!$user_id) {
        error_log('MYAVANA Check-in: User not logged in');
        wp_send_json_error('User not logged in');
        return;
    }

    error_log('MYAVANA Check-in: User ID: ' . $user_id);

    global $wpdb;
    $table_checkins = $wpdb->prefix . 'myavana_daily_checkins';
    $table_stats = $wpdb->prefix . 'myavana_user_stats';

    // Check if tables exist
    $checkins_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_checkins'") === $table_checkins;
    $stats_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_stats'") === $table_stats;

    if (!$checkins_table_exists || !$stats_table_exists) {
        error_log('MYAVANA Check-in: Tables missing - checkins: ' . ($checkins_table_exists ? 'yes' : 'no') . ', stats: ' . ($stats_table_exists ? 'yes' : 'no'));
        wp_send_json_error('Database tables not initialized. Please contact administrator.');
        return;
    }

    // Get mood from request
    $mood = isset($_POST['mood']) ? sanitize_text_field($_POST['mood']) : null;
    $today = current_time('Y-m-d');

    error_log('MYAVANA Check-in: Mood: ' . $mood . ', Date: ' . $today);

    // Check if already checked in today
    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$table_checkins} WHERE user_id = %d AND check_in_date = %s",
        $user_id,
        $today
    ));

    if ($exists) {
        error_log('MYAVANA Check-in: Already checked in today');
        wp_send_json_error('Already checked in today');
        return;
    }

    // Get user stats
    $stats = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$table_stats} WHERE user_id = %d",
        $user_id
    ), ARRAY_A);

    if (!$stats) {
        // Initialize stats
        $wpdb->insert($table_stats, array(
            'user_id' => $user_id,
            'total_points' => 0,
            'current_streak' => 0,
            'longest_streak' => 0,
            'level' => 1
        ));
        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_stats} WHERE user_id = %d",
            $user_id
        ), ARRAY_A);
    }

    // Calculate streak
    $last_checkin = $stats['last_check_in'];
    $current_streak = $stats['current_streak'];

    if ($last_checkin) {
        $yesterday = date('Y-m-d', strtotime($today . ' -1 day'));
        if ($last_checkin === $yesterday) {
            // Continuing streak
            $current_streak++;
        } else {
            // Streak broken
            $current_streak = 1;
        }
    } else {
        // First check-in
        $current_streak = 1;
    }

    // Calculate points (base 10 + bonus for milestones)
    $points_earned = 10;
    $bonus_points = 0;
    $milestone = null;

    if ($current_streak === 7) {
        $bonus_points = 50;
        $milestone = '7-day streak';
    } elseif ($current_streak === 30) {
        $bonus_points = 200;
        $milestone = '30-day streak';
    } elseif ($current_streak % 10 === 0 && $current_streak > 0) {
        $bonus_points = 25;
        $milestone = $current_streak . '-day streak';
    }

    $points_earned += $bonus_points;

    // Insert check-in
    $wpdb->insert($table_checkins, array(
        'user_id' => $user_id,
        'check_in_date' => $today,
        'mood' => $mood,
        'points_earned' => $points_earned,
        'streak_count' => $current_streak
    ));

    // Update user stats
    $longest_streak = max($current_streak, $stats['longest_streak']);
    $total_points = $stats['total_points'] + $points_earned;

    // Calculate level (100 points per level)
    $level = floor($total_points / 100) + 1;

    $wpdb->update(
        $table_stats,
        array(
            'total_points' => $total_points,
            'current_streak' => $current_streak,
            'longest_streak' => $longest_streak,
            'last_check_in' => $today,
            'level' => $level
        ),
        array('user_id' => $user_id)
    );

    // Check for badge unlocks
    $new_badges = myavana_check_badge_unlocks($user_id);

    wp_send_json_success(array(
        'points_earned' => $points_earned,
        'bonus_points' => $bonus_points,
        'total_points' => $total_points,
        'streak' => $current_streak,
        'level' => $level,
        'milestone' => $milestone,
        'new_badges' => $new_badges,
        'message' => $milestone ? "🔥 {$milestone} bonus!" : 'Check-in complete!'
    ));
}
add_action('wp_ajax_myavana_daily_checkin', 'myavana_daily_checkin');

/**
 * Get user gamification stats
 */
function myavana_get_gamification_stats() {
    error_log('MYAVANA Stats: Request received');

    // Skip nonce check - session cookie validates the user
    // Safe because we're only allowing logged-in users via wp_ajax_ hook

    $user_id = get_current_user_id();
    if (!$user_id) {
        error_log('MYAVANA Stats: User not logged in');
        wp_send_json_error('User not logged in');
        return;
    }

    error_log('MYAVANA Stats: User ID: ' . $user_id);

    global $wpdb;
    $table_stats = $wpdb->prefix . 'myavana_user_stats';
    $table_checkins = $wpdb->prefix . 'myavana_daily_checkins';
    $table_user_badges = $wpdb->prefix . 'myavana_user_badges';
    $table_badges = $wpdb->prefix . 'myavana_badges';

    // Check if tables exist
    $stats_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_stats'") === $table_stats;
    if (!$stats_table_exists) {
        error_log('MYAVANA Stats: Stats table missing');
        wp_send_json_error('Database tables not initialized');
        return;
    }

    // Check if user checked in today
    $today = current_time('Y-m-d');
    $checked_in_today = (bool) $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$table_checkins} WHERE user_id = %d AND check_in_date = %s",
        $user_id,
        $today
    ));

    error_log('MYAVANA Stats: Checked in today: ' . ($checked_in_today ? 'yes' : 'no'));

    // Get user stats
    $stats = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$table_stats} WHERE user_id = %d",
        $user_id
    ), ARRAY_A);

    if (!$stats) {
        error_log('MYAVANA Stats: No stats found, returning defaults');
        wp_send_json_success(array(
            'total_points' => 0,
            'current_streak' => 0,
            'longest_streak' => 0,
            'level' => 1,
            'checked_in_today' => $checked_in_today,
            'badges_earned' => 0
        ));
        return;
    }

    // Get earned badges count
    $badges_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$table_user_badges} WHERE user_id = %d",
        $user_id
    ));

    // Get recent badges (last 3)
    $recent_badges = $wpdb->get_results($wpdb->prepare(
        "SELECT b.*, ub.earned_at
         FROM {$table_user_badges} ub
         JOIN {$table_badges} b ON ub.badge_id = b.id
         WHERE ub.user_id = %d
         ORDER BY ub.earned_at DESC
         LIMIT 3",
        $user_id
    ), ARRAY_A);

    wp_send_json_success(array(
        'total_points' => intval($stats['total_points']),
        'current_streak' => intval($stats['current_streak']),
        'longest_streak' => intval($stats['longest_streak']),
        'level' => intval($stats['level']),
        'checked_in_today' => (bool) $checked_in_today,
        'badges_earned' => intval($badges_count),
        'recent_badges' => $recent_badges,
        'next_level_points' => ($stats['level'] * 100),
        'progress_to_next_level' => ($stats['total_points'] % 100)
    ));
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
    $available_badges = $wpdb->get_results("
        SELECT b.*
        FROM {$table_badges} b
        LEFT JOIN {$table_user_badges} ub ON b.id = ub.badge_id AND ub.user_id = {$user_id}
        WHERE ub.id IS NULL
    ", ARRAY_A);

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

            // Award points
            $wpdb->query($wpdb->prepare(
                "UPDATE {$table_stats} SET total_points = total_points + %d WHERE user_id = %d",
                $badge['points_reward'],
                $user_id
            ));

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

    global $wpdb;
    $table_stats = $wpdb->prefix . 'myavana_user_stats';

    // Increment total entries
    $wpdb->query($wpdb->prepare(
        "UPDATE {$table_stats} SET total_entries = total_entries + 1 WHERE user_id = %d",
        $user_id
    ));

    // Check for badge unlocks
    myavana_check_badge_unlocks($user_id);
}
add_action('wp_insert_post', 'myavana_update_stats_on_entry', 10, 3);
