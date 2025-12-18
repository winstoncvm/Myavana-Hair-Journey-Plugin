<?php
/**
 * MYAVANA Progressive Insight Handlers
 * Unlock analytics based on engagement milestones
 * @version 2.3.5
 */

if (!defined('ABSPATH')) exit;

/**
 * Get user's insights (both locked and unlocked)
 */
function myavana_get_user_insights() {
    check_ajax_referer('myavana_gamification', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'User not authenticated']);
        return;
    }

    $user_id = get_current_user_id();
    global $wpdb;

    $insights_table = $wpdb->prefix . 'myavana_insights';
    $user_insights_table = $wpdb->prefix . 'myavana_user_insights';

    // Get all available insights
    $all_insights = $wpdb->get_results(
        "SELECT * FROM $insights_table WHERE is_active = 1 ORDER BY unlock_threshold ASC",
        ARRAY_A
    );

    // Get user's unlocked insights
    $user_unlocked = $wpdb->get_results($wpdb->prepare(
        "SELECT insight_key, unlocked_at FROM $user_insights_table WHERE user_id = %d",
        $user_id
    ), OBJECT_K); // OBJECT_K uses insight_key as array key

    wp_send_json_success([
        'all_insights' => $all_insights,
        'user_insights' => $user_unlocked
    ]);
}
add_action('wp_ajax_myavana_get_user_insights', 'myavana_get_user_insights');

/**
 * Check if user has unlocked new insights
 * Called after entry creation, check-in, or level up
 */
function myavana_check_insight_unlocks() {
    check_ajax_referer('myavana_gamification', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'User not authenticated']);
        return;
    }

    $user_id = get_current_user_id();
    global $wpdb;

    $insights_table = $wpdb->prefix . 'myavana_insights';
    $user_insights_table = $wpdb->prefix . 'myavana_user_insights';
    $user_stats_table = $wpdb->prefix . 'myavana_user_stats';

    // Get user stats
    $user_stats = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $user_stats_table WHERE user_id = %d",
        $user_id
    ));

    if (!$user_stats) {
        wp_send_json_success(['new_unlocks' => []]);
        return;
    }

    // Get total entries
    $total_entries = wp_count_posts('hair_journey_entry');
    $published_entries = $total_entries ? $total_entries->publish : 0;

    // Get locked insights
    $locked_insights = $wpdb->get_results($wpdb->prepare(
        "SELECT i.* FROM $insights_table i
         LEFT JOIN $user_insights_table ui ON i.insight_key = ui.insight_key AND ui.user_id = %d
         WHERE i.is_active = 1 AND ui.insight_key IS NULL
         ORDER BY i.unlock_threshold ASC",
        $user_id
    ), ARRAY_A);

    $new_unlocks = [];

    foreach ($locked_insights as $insight) {
        $should_unlock = false;

        // Parse unlock requirement
        $requirement = strtolower($insight['unlock_requirement']);

        if (strpos($requirement, 'entries') !== false) {
            // "Log 3 entries" -> check total entries
            preg_match('/(\d+)\s*entries/', $requirement, $matches);
            if ($matches && $published_entries >= intval($matches[1])) {
                $should_unlock = true;
            }
        } elseif (strpos($requirement, 'streak') !== false) {
            // "Maintain a 7-day streak"
            preg_match('/(\d+)-day streak/', $requirement, $matches);
            if ($matches && $user_stats->current_streak >= intval($matches[1])) {
                $should_unlock = true;
            }
        } elseif (strpos($requirement, 'ai analyses') !== false) {
            // "Complete 10 AI analyses"
            preg_match('/(\d+)\s*ai analyses/i', $requirement, $matches);
            if ($matches && $user_stats->total_ai_analyses >= intval($matches[1])) {
                $should_unlock = true;
            }
        } elseif (strpos($requirement, 'level') !== false) {
            // "Reach level 5"
            preg_match('/level\s*(\d+)/i', $requirement, $matches);
            if ($matches && $user_stats->current_level >= intval($matches[1])) {
                $should_unlock = true;
            }
        } elseif (strpos($requirement, 'points') !== false) {
            // "Earn 500 points"
            preg_match('/(\d+)\s*points/', $requirement, $matches);
            if ($matches && $user_stats->total_points >= intval($matches[1])) {
                $should_unlock = true;
            }
        } elseif (strpos($requirement, 'badges') !== false) {
            // "Unlock 3 badges"
            preg_match('/(\d+)\s*badges/', $requirement, $matches);
            if ($matches) {
                $user_badges_table = $wpdb->prefix . 'myavana_user_badges';
                $badge_count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $user_badges_table WHERE user_id = %d",
                    $user_id
                ));
                if ($badge_count >= intval($matches[1])) {
                    $should_unlock = true;
                }
            }
        }

        if ($should_unlock) {
            // Unlock the insight
            $wpdb->insert(
                $user_insights_table,
                [
                    'user_id' => $user_id,
                    'insight_key' => $insight['insight_key'],
                    'unlocked_at' => current_time('mysql')
                ],
                ['%d', '%s', '%s']
            );

            // Award points
            if ($insight['points_reward'] > 0) {
                $wpdb->update(
                    $user_stats_table,
                    [
                        'total_points' => $user_stats->total_points + $insight['points_reward'],
                        'current_level' => floor(($user_stats->total_points + $insight['points_reward']) / 100) + 1
                    ],
                    ['user_id' => $user_id],
                    ['%d', '%d'],
                    ['%d']
                );
            }

            $new_unlocks[] = $insight;

            error_log(sprintf(
                '[Progressive Insights] User %d unlocked insight: %s (+%d points)',
                $user_id,
                $insight['title'],
                $insight['points_reward']
            ));
        }
    }

    wp_send_json_success([
        'new_unlocks' => $new_unlocks,
        'count' => count($new_unlocks)
    ]);
}
add_action('wp_ajax_myavana_check_insight_unlocks', 'myavana_check_insight_unlocks');

/**
 * Get specific insight data (for viewing unlocked insights)
 */
function myavana_get_insight_data() {
    check_ajax_referer('myavana_gamification', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'User not authenticated']);
        return;
    }

    $user_id = get_current_user_id();
    $insight_key = isset($_POST['insight_key']) ? sanitize_text_field($_POST['insight_key']) : '';

    if (empty($insight_key)) {
        wp_send_json_error(['message' => 'Insight key required']);
        return;
    }

    global $wpdb;
    $user_insights_table = $wpdb->prefix . 'myavana_user_insights';

    // Verify user has unlocked this insight
    $is_unlocked = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $user_insights_table WHERE user_id = %d AND insight_key = %s",
        $user_id,
        $insight_key
    ));

    if (!$is_unlocked) {
        wp_send_json_error(['message' => 'Insight not unlocked']);
        return;
    }

    // Generate insight data based on key
    $data = [];

    switch ($insight_key) {
        case 'health_progression':
            $data = myavana_generate_health_progression_data($user_id);
            break;

        case 'product_effectiveness':
            $data = myavana_generate_product_effectiveness_data($user_id);
            break;

        case 'routine_impact':
            $data = myavana_generate_routine_impact_data($user_id);
            break;

        case 'seasonal_trends':
            $data = myavana_generate_seasonal_trends_data($user_id);
            break;

        case 'ai_insights':
            $data = myavana_generate_ai_insights_data($user_id);
            break;

        default:
            $data = ['message' => 'Insight data not available yet'];
    }

    wp_send_json_success($data);
}
add_action('wp_ajax_myavana_get_insight_data', 'myavana_get_insight_data');

/**
 * Helper: Generate health progression data
 */
function myavana_generate_health_progression_data($user_id) {
    global $wpdb;

    // Get entries with health ratings over time
    $entries = $wpdb->get_results($wpdb->prepare(
        "SELECT post_date, meta_value as health_rating
         FROM {$wpdb->posts} p
         LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = 'health_rating'
         WHERE p.post_type = 'hair_journey_entry'
         AND p.post_author = %d
         AND p.post_status = 'publish'
         AND pm.meta_value IS NOT NULL
         ORDER BY p.post_date ASC",
        $user_id
    ));

    $chart_data = [];
    foreach ($entries as $entry) {
        $chart_data[] = [
            'date' => date('M j', strtotime($entry->post_date)),
            'rating' => intval($entry->health_rating)
        ];
    }

    return [
        'chart_data' => $chart_data,
        'average_rating' => count($chart_data) > 0 ? array_sum(array_column($chart_data, 'rating')) / count($chart_data) : 0,
        'trend' => count($chart_data) >= 2 ? ($chart_data[count($chart_data) - 1]['rating'] > $chart_data[0]['rating'] ? 'improving' : 'declining') : 'stable'
    ];
}

/**
 * Helper: Generate product effectiveness data
 */
function myavana_generate_product_effectiveness_data($user_id) {
    // Placeholder - would analyze product mentions in entries
    return [
        'message' => 'Product effectiveness tracking coming soon',
        'top_products' => []
    ];
}

/**
 * Helper: Generate routine impact data
 */
function myavana_generate_routine_impact_data($user_id) {
    // Placeholder - would analyze routine changes vs health ratings
    return [
        'message' => 'Routine impact analysis coming soon',
        'routines' => []
    ];
}

/**
 * Helper: Generate seasonal trends data
 */
function myavana_generate_seasonal_trends_data($user_id) {
    // Placeholder - would analyze entries by season
    return [
        'message' => 'Seasonal trends analysis coming soon',
        'seasons' => []
    ];
}

/**
 * Helper: Generate AI insights data
 */
function myavana_generate_ai_insights_data($user_id) {
    global $wpdb;

    // Count AI analyses
    $profile_table = $wpdb->prefix . 'myavana_profiles';
    $ai_count = $wpdb->get_var($wpdb->prepare(
        "SELECT ai_analyses_used FROM $profile_table WHERE user_id = %d",
        $user_id
    ));

    return [
        'total_analyses' => intval($ai_count),
        'message' => 'AI insights aggregation in development',
        'recommendations' => []
    ];
}
