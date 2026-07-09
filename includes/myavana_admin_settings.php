<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin menu setup.
 */
function myavana_add_admin_menu()
{
    add_menu_page(
        'Myavana Intelligence Dashboard',
        'Myavana',
        'manage_options',
        'myavana-intelligence-dashboard',
        'myavana_intelligence_dashboard_page',
        'dashicons-chart-area',
        80
    );

    add_submenu_page(
        'myavana-intelligence-dashboard',
        'Overview Dashboard',
        'Overview',
        'manage_options',
        'myavana-intelligence-dashboard',
        'myavana_intelligence_dashboard_page'
    );

    add_submenu_page(
        'myavana-intelligence-dashboard',
        'Executive Intelligence',
        'Executive',
        'manage_options',
        'myavana-executive-intelligence',
        'myavana_intelligence_dashboard_page'
    );

    add_submenu_page(
        'myavana-intelligence-dashboard',
        'Growth Intelligence',
        'Growth',
        'manage_options',
        'myavana-growth-intelligence',
        'myavana_intelligence_dashboard_page'
    );

    add_submenu_page(
        'myavana-intelligence-dashboard',
        'Community Intelligence',
        'Community',
        'manage_options',
        'myavana-community-intelligence',
        'myavana_intelligence_dashboard_page'
    );

    add_submenu_page(
        'myavana-intelligence-dashboard',
        'Retention Intelligence',
        'Retention',
        'manage_options',
        'myavana-retention-intelligence',
        'myavana_intelligence_dashboard_page'
    );

    add_submenu_page(
        'myavana-intelligence-dashboard',
        'AI Intelligence',
        'AI Lab',
        'manage_options',
        'myavana-ai-intelligence',
        'myavana_intelligence_dashboard_page'
    );

    add_submenu_page(
        'myavana-intelligence-dashboard',
        'Operations Intelligence',
        'Operations',
        'manage_options',
        'myavana-operations-intelligence',
        'myavana_intelligence_dashboard_page'
    );

    add_submenu_page(
        'myavana-intelligence-dashboard',
        'Gamification Intelligence',
        'Gamification',
        'manage_options',
        'myavana-gamification-intelligence',
        'myavana_intelligence_dashboard_page'
    );

    add_submenu_page(
        'myavana-intelligence-dashboard',
        'Myavana Settings',
        'Settings',
        'manage_options',
        'myavana-settings',
        'myavana_settings_page'
    );
}
add_action('admin_menu', 'myavana_add_admin_menu');

/**
 * Redirect default WordPress dashboard to Myavana dashboard for admins.
 */
function myavana_redirect_wp_dashboard_to_myavana()
{
    if (!is_admin() || wp_doing_ajax() || !current_user_can('manage_options')) {
        return;
    }

    global $pagenow;
    if ($pagenow !== 'index.php') {
        return;
    }

    if (!empty($_GET['page']) || !empty($_GET['myavana_no_redirect'])) {
        return;
    }

    wp_safe_redirect(admin_url('admin.php?page=myavana-intelligence-dashboard'));
    exit;
}
add_action('admin_init', 'myavana_redirect_wp_dashboard_to_myavana');

/**
 * Load dashboard-only assets.
 */
function myavana_admin_intelligence_assets($hook_suffix)
{
    if (!is_admin()) {
        return;
    }

    $page = sanitize_text_field(wp_unslash($_GET['page'] ?? ''));
    $dashboard_pages = [
        'myavana-intelligence-dashboard',
        'myavana-executive-intelligence',
        'myavana-growth-intelligence',
        'myavana-community-intelligence',
        'myavana-retention-intelligence',
        'myavana-ai-intelligence',
        'myavana-operations-intelligence',
        'myavana-gamification-intelligence',
    ];

    if (!in_array($page, $dashboard_pages, true)) {
        return;
    }

    wp_enqueue_script('myavana-chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js', [], '4.4.1', true);
}
add_action('admin_enqueue_scripts', 'myavana_admin_intelligence_assets');

/**
 * Settings registration.
 */
function myavana_settings_init()
{
    register_setting('myavanaSettings', 'myavana_xai_api_key', [
        'sanitize_callback' => 'sanitize_text_field'
    ]);
    register_setting('myavanaSettings', 'myavana_openai_api_key', [
        'sanitize_callback' => 'sanitize_text_field'
    ]);
    register_setting('myavanaSettings', 'myavana_gemini_api_key', [
        'sanitize_callback' => 'sanitize_text_field'
    ]);

    add_settings_section(
        'myavana_api_section',
        'API Keys',
        null,
        'myavana-settings'
    );

    add_settings_field(
        'myavana_xai_api_key',
        'xAI API Key',
        'myavana_xai_api_key_render',
        'myavana-settings',
        'myavana_api_section'
    );

    add_settings_field(
        'myavana_openai_api_key',
        'OpenAI API Key',
        'myavana_openai_api_key_render',
        'myavana-settings',
        'myavana_api_section'
    );

    add_settings_field(
        'myavana_gemini_api_key',
        'Gemini API Key',
        'myavana_gemini_api_key_render',
        'myavana-settings',
        'myavana_api_section'
    );
}
add_action('admin_init', 'myavana_settings_init');

function myavana_xai_api_key_render()
{
    $value = get_option('myavana_xai_api_key', '');
    echo '<input type="password" name="myavana_xai_api_key" value="' . esc_attr($value) . '" size="50">';
}

function myavana_openai_api_key_render()
{
    $value = get_option('myavana_openai_api_key', '');
    echo '<input type="password" name="myavana_openai_api_key" value="' . esc_attr($value) . '" size="50">';
}

function myavana_gemini_api_key_render()
{
    $value = get_option('myavana_gemini_api_key', '');
    echo '<input type="password" name="myavana_gemini_api_key" value="' . esc_attr($value) . '" size="50">';
}

function myavana_settings_page()
{
?>
    <div class="wrap">
        <h1>Myavana Settings</h1>
        <form action="options.php" method="post">
            <?php
    settings_fields('myavanaSettings');
    do_settings_sections('myavana-settings');
    submit_button();
?>
        </form>
    </div>
    <?php
}

/**
 * Capture front-end page activity from tracker script.
 */
function myavana_track_page_activity()
{
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'myavana_site_intelligence')) {
        wp_send_json_error(['message' => 'Invalid request'], 403);
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'myavana_site_analytics';
    $table_exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table_name));
    if (!$table_exists) {
        wp_send_json_error(['message' => 'Analytics table not ready'], 503);
    }

    $session_id = sanitize_text_field(wp_unslash($_POST['session_id'] ?? ''));
    $path = sanitize_text_field(wp_unslash($_POST['path'] ?? '/'));
    $page_title = sanitize_text_field(wp_unslash($_POST['page_title'] ?? ''));
    $referrer = esc_url_raw(wp_unslash($_POST['referrer'] ?? ''));
    $device_type = sanitize_text_field(wp_unslash($_POST['device_type'] ?? 'unknown'));
    $viewport = sanitize_text_field(wp_unslash($_POST['viewport'] ?? ''));
    $time_spent = absint($_POST['time_spent_seconds'] ?? 0);

    if (empty($session_id) || empty($path)) {
        wp_send_json_error(['message' => 'Missing required fields'], 400);
    }

    if (strpos($path, '/wp-admin') === 0 || strpos($path, '/wp-json') === 0) {
        wp_send_json_success(['ignored' => true]);
    }

    $now = current_time('mysql');
    $user_id = is_user_logged_in() ? get_current_user_id() : null;

    $meta_payload = [
        'user_agent' => sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'] ?? '')),
        'ip_hash' => md5(sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'] ?? ''))),
    ];

    $existing = $wpdb->get_row($wpdb->prepare(
        "SELECT id, time_spent_seconds, last_seen_at FROM {$table_name}
         WHERE session_id = %s AND path = %s
         ORDER BY id DESC LIMIT 1",
        $session_id,
        $path
    ));

    $updated = false;
    if ($existing && !empty($existing->last_seen_at)) {
        $last_seen_ts = strtotime($existing->last_seen_at);
        if ($last_seen_ts && ($last_seen_ts >= (time() - HOUR_IN_SECONDS))) {
            $updated = $wpdb->update(
                $table_name,
            [
                'user_id' => $user_id,
                'page_title' => $page_title,
                'referrer' => $referrer,
                'device_type' => $device_type,
                'viewport' => $viewport,
                'time_spent_seconds' => max((int)$existing->time_spent_seconds, $time_spent),
                'last_seen_at' => $now,
                'meta_json' => wp_json_encode($meta_payload),
            ],
            ['id' => (int)$existing->id],
            ['%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s'],
            ['%d']
            );
        }
    }

    if (!$updated) {
        $wpdb->insert(
            $table_name,
        [
            'session_id' => $session_id,
            'user_id' => $user_id,
            'path' => $path,
            'page_title' => $page_title,
            'referrer' => $referrer,
            'device_type' => $device_type,
            'viewport' => $viewport,
            'time_spent_seconds' => $time_spent,
            'visited_at' => $now,
            'last_seen_at' => $now,
            'meta_json' => wp_json_encode($meta_payload),
        ],
        ['%s', '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s']
        );
    }

    wp_send_json_success(['tracked' => true]);
}
add_action('wp_ajax_myavana_track_page_activity', 'myavana_track_page_activity');
add_action('wp_ajax_nopriv_myavana_track_page_activity', 'myavana_track_page_activity');

/**
 * SQL prepare helper for dynamic parameter lists.
 */
function myavana_prepare_sql($sql, $params = [])
{
    global $wpdb;

    if (empty($params)) {
        return $sql;
    }

    $prepared_args = array_merge([$sql], $params);
    return call_user_func_array([$wpdb, 'prepare'], $prepared_args);
}

function myavana_validate_dashboard_date($date, $fallback)
{
    $value = sanitize_text_field((string)$date);
    $dt = DateTime::createFromFormat('Y-m-d', $value);
    if ($dt && $dt->format('Y-m-d') === $value) {
        return $value;
    }
    return $fallback;
}

/**
 * Build role filter SQL fragment for user alias.
 */
function myavana_build_role_filter_clause($selected_role, $user_alias = 'u')
{
    global $wpdb;

    if ($selected_role === 'all' || $selected_role === '') {
        return [
            'sql' => '',
            'params' => [],
        ];
    }

    $cap_meta_key = $wpdb->prefix . 'capabilities';
    $role_like = '%' . $wpdb->esc_like('"' . $selected_role . '"') . '%';

    return [
        'sql' => " AND EXISTS (
            SELECT 1 FROM {$wpdb->usermeta} role_um
            WHERE role_um.user_id = {$user_alias}.ID
              AND role_um.meta_key = %s
              AND role_um.meta_value LIKE %s
        )",
        'params' => [$cap_meta_key, $role_like],
    ];
}

/**
 * Build role filter SQL fragment for analytics alias.
 */
function myavana_build_analytics_role_filter_clause($selected_role, $analytics_alias = 'sa')
{
    global $wpdb;

    if ($selected_role === 'all' || $selected_role === '') {
        return [
            'sql' => '',
            'params' => [],
        ];
    }

    $cap_meta_key = $wpdb->prefix . 'capabilities';
    $role_like = '%' . $wpdb->esc_like('"' . $selected_role . '"') . '%';

    return [
        'sql' => " AND {$analytics_alias}.user_id IN (
            SELECT role_um.user_id
            FROM {$wpdb->usermeta} role_um
            WHERE role_um.meta_key = %s
              AND role_um.meta_value LIKE %s
        )",
        'params' => [$cap_meta_key, $role_like],
    ];
}

/**
 * Build a compact, actionable set of intelligence insights.
 */
function myavana_generate_intelligence_insights($metrics, $range_label)
{
    $insights = [];

    if ($metrics['total_visits'] > 0) {
        $insights[] = sprintf(
            'Traffic trend (%s): %d visits and %d unique visitors.',
            $range_label,
            $metrics['total_visits'],
            $metrics['unique_visitors']
        );
    }
    else {
        $insights[] = 'Traffic trend: no site-intelligence records yet. Keep tracker active for at least 24 hours.';
    }

    if ($metrics['analysis_users'] > 0 && $metrics['total_users'] > 0) {
        $coverage = round(($metrics['analysis_users'] / max(1, $metrics['total_users'])) * 100, 1);
        $insights[] = sprintf('AI adoption (selected role): %.1f%% of users have at least one analysis history record.', $coverage);
    }

    if (!empty($metrics['section_usage'])) {
        arsort($metrics['section_usage']);
        $top_section = array_key_first($metrics['section_usage']);
        $top_hits = (int)$metrics['section_usage'][$top_section];
        if ($top_hits > 0) {
            $insights[] = sprintf('Most-used section: %s (%d interactions in selected range).', ucfirst($top_section), $top_hits);
        }
    }

    if ($metrics['bounce_rate'] > 0) {
        $insights[] = sprintf('Low-engagement sessions (<=10s): %.1f%%. Improve first-screen relevance and CTA clarity.', $metrics['bounce_rate']);
    }

    return $insights;
}

function myavana_seconds_to_readable($seconds)
{
    $seconds = (int)$seconds;
    if ($seconds < 60) {
        return $seconds . 's';
    }

    $minutes = floor($seconds / 60);
    $remaining = $seconds % 60;
    return $minutes . 'm ' . $remaining . 's';
}

function myavana_sanitize_gamification_reward_settings($input)
{
    $defaults = Myavana_Gamification::get_reward_settings();
    $sanitized = [];

    foreach ($defaults as $key => $default) {
        $sanitized[$key] = max(0, intval($input[$key] ?? $default));
    }

    return $sanitized;
}

function myavana_sanitize_gamification_challenges($input)
{
    if (!is_array($input)) {
        return Myavana_Gamification::get_challenge_definitions();
    }

    $sanitized = [];
    foreach ($input as $row) {
        if (!is_array($row)) {
            continue;
        }

        $id = sanitize_key($row['id'] ?? '');
        $title = sanitize_text_field($row['title'] ?? '');
        if ($id === '' || $title === '') {
            continue;
        }

        $sanitized[] = [
            'id' => $id,
            'title' => $title,
            'description' => sanitize_textarea_field($row['description'] ?? ''),
            'metric' => sanitize_key($row['metric'] ?? 'entries_week'),
            'target' => max(1, intval($row['target'] ?? 1)),
            'reward_points' => max(0, intval($row['reward_points'] ?? 0)),
            'status' => sanitize_key($row['status'] ?? 'active'),
            'audience' => sanitize_key($row['audience'] ?? 'all'),
            'category' => sanitize_key($row['category'] ?? 'custom'),
            'start_date' => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($row['start_date'] ?? '')) ? sanitize_text_field($row['start_date']) : '',
            'end_date' => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($row['end_date'] ?? '')) ? sanitize_text_field($row['end_date']) : '',
        ];
    }

    return !empty($sanitized) ? $sanitized : Myavana_Gamification::get_challenge_definitions();
}

function myavana_get_gamification_admin_data($date_from_sql, $date_to_sql, $selected_role)
{
    global $wpdb;

    $history_table = $wpdb->prefix . 'myavana_points_history';
    $users_table = $wpdb->users;
    $table_exists = (bool) $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $history_table));
    if (!$table_exists) {
        return [
            'metrics' => [
                'reward_events' => 0,
                'points_awarded' => 0,
                'rewarded_users' => 0,
                'challenge_completions' => 0,
                'challenge_participants' => 0,
            ],
            'challenge_rows' => [],
            'reward_reason_rows' => [],
            'reward_user_rows' => [],
        ];
    }

    $role_filter = myavana_build_role_filter_clause($selected_role, 'u');
    $rows = $wpdb->get_results(myavana_prepare_sql(
        "SELECT ph.user_id, ph.reason, ph.points_change, ph.reference_type, ph.event_key, ph.meta_data, ph.created_at,
                u.display_name, u.user_email
         FROM {$history_table} ph
         INNER JOIN {$users_table} u ON u.ID = ph.user_id
         WHERE ph.created_at >= %s
           AND ph.created_at < %s" . $role_filter['sql'] . "
         ORDER BY ph.created_at DESC, ph.id DESC",
        array_merge([$date_from_sql, $date_to_sql], $role_filter['params'])
    ), ARRAY_A);

    $challenge_titles = [];
    foreach (Myavana_Gamification::get_challenge_definitions() as $definition) {
        $challenge_titles[$definition['id']] = $definition['title'];
    }

    $points_awarded = 0;
    $rewarded_users = [];
    $challenge_participants = [];
    $challenge_completions = 0;
    $challenge_rows = [];
    $reward_reason_rows = [];
    $reward_user_rows = [];

    foreach ($rows as $row) {
        $points = intval($row['points_change'] ?? 0);
        if ($points <= 0) {
            continue;
        }

        $user_id = intval($row['user_id']);
        $points_awarded += $points;
        $rewarded_users[$user_id] = true;

        $reason = sanitize_text_field($row['reason'] ?? 'Reward');
        if (!isset($reward_reason_rows[$reason])) {
            $reward_reason_rows[$reason] = ['reason' => $reason, 'events' => 0, 'points' => 0];
        }
        $reward_reason_rows[$reason]['events']++;
        $reward_reason_rows[$reason]['points'] += $points;

        if (!isset($reward_user_rows[$user_id])) {
            $reward_user_rows[$user_id] = [
                'user_id' => $user_id,
                'display_name' => $row['display_name'] ?: ('User #' . $user_id),
                'user_email' => $row['user_email'] ?? '',
                'events' => 0,
                'points' => 0,
            ];
        }
        $reward_user_rows[$user_id]['events']++;
        $reward_user_rows[$user_id]['points'] += $points;

        if (($row['reference_type'] ?? '') !== 'challenge') {
            continue;
        }

        $challenge_completions++;
        $challenge_participants[$user_id] = true;
        $meta = json_decode($row['meta_data'] ?? '', true);
        $challenge_id = sanitize_key($meta['challenge_id'] ?? '');
        if ($challenge_id === '' && !empty($row['event_key'])) {
            $challenge_id = sanitize_key(str_replace('challenge_completed:', '', (string) $row['event_key']));
        }
        $challenge_label = $challenge_titles[$challenge_id] ?? ($challenge_id ?: $reason);

        if (!isset($challenge_rows[$challenge_label])) {
            $challenge_rows[$challenge_label] = [
                'challenge_id' => $challenge_id,
                'title' => $challenge_label,
                'completions' => 0,
                'participants' => [],
                'points' => 0,
            ];
        }

        $challenge_rows[$challenge_label]['completions']++;
        $challenge_rows[$challenge_label]['participants'][$user_id] = true;
        $challenge_rows[$challenge_label]['points'] += $points;
    }

    foreach ($challenge_rows as &$challenge_row) {
        $challenge_row['participants'] = count($challenge_row['participants']);
    }
    unset($challenge_row);

    $challenge_rows = array_values($challenge_rows);
    $reward_reason_rows = array_values($reward_reason_rows);
    $reward_user_rows = array_values($reward_user_rows);

    usort($challenge_rows, static function ($a, $b) {
        return ($b['completions'] <=> $a['completions']) ?: ($b['points'] <=> $a['points']);
    });
    usort($reward_reason_rows, static function ($a, $b) {
        return ($b['points'] <=> $a['points']) ?: ($b['events'] <=> $a['events']);
    });
    usort($reward_user_rows, static function ($a, $b) {
        return ($b['points'] <=> $a['points']) ?: ($b['events'] <=> $a['events']);
    });

    return [
        'metrics' => [
            'reward_events' => count($rows),
            'points_awarded' => $points_awarded,
            'rewarded_users' => count($rewarded_users),
            'challenge_completions' => $challenge_completions,
            'challenge_participants' => count($challenge_participants),
        ],
        'challenge_rows' => array_slice($challenge_rows, 0, 10),
        'reward_reason_rows' => array_slice($reward_reason_rows, 0, 10),
        'reward_user_rows' => array_slice($reward_user_rows, 0, 10),
    ];
}

/**
 * Funnel drop-off for selected cohort of signups.
 */
function myavana_calculate_funnel_data($date_from_sql, $date_to_sql, $selected_role)
{
    global $wpdb;

    $users_table = $wpdb->users;
    $posts_table = $wpdb->posts;
    $community_posts_table = $wpdb->prefix . 'myavana_community_posts';
    $community_posts_exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $community_posts_table));

    $role_filter = myavana_build_role_filter_clause($selected_role, 'u');

    $signup_sql = "SELECT COUNT(*) FROM {$users_table} u
                   WHERE u.user_registered >= %s AND u.user_registered < %s" . $role_filter['sql'];
    $signup_count = (int)$wpdb->get_var(myavana_prepare_sql($signup_sql, array_merge([$date_from_sql, $date_to_sql], $role_filter['params'])));

    $onboarding_sql = "SELECT COUNT(DISTINCT u.ID)
                       FROM {$users_table} u
                       INNER JOIN {$wpdb->usermeta} om
                            ON om.user_id = u.ID
                           AND om.meta_key = 'myavana_onboarding_status'
                           AND om.meta_value = 'completed'
                       WHERE u.user_registered >= %s AND u.user_registered < %s" . $role_filter['sql'];
    $onboarding_count = (int)$wpdb->get_var(myavana_prepare_sql($onboarding_sql, array_merge([$date_from_sql, $date_to_sql], $role_filter['params'])));

    $entry_sql = "SELECT COUNT(DISTINCT u.ID)
                  FROM {$users_table} u
                  INNER JOIN {$posts_table} p
                          ON p.post_author = u.ID
                         AND p.post_type = 'hair_journey_entry'
                         AND p.post_status = 'publish'
                  WHERE u.user_registered >= %s AND u.user_registered < %s" . $role_filter['sql'];
    $entry_count = (int)$wpdb->get_var(myavana_prepare_sql($entry_sql, array_merge([$date_from_sql, $date_to_sql], $role_filter['params'])));

    $analysis_sql = "SELECT COUNT(DISTINCT u.ID)
                     FROM {$users_table} u
                     INNER JOIN {$wpdb->usermeta} am
                             ON am.user_id = u.ID
                            AND am.meta_key = 'myavana_hair_analysis_history'
                            AND am.meta_value <> ''
                            AND am.meta_value <> 'a:0:{}'
                     WHERE u.user_registered >= %s AND u.user_registered < %s" . $role_filter['sql'];
    $analysis_count = (int)$wpdb->get_var(myavana_prepare_sql($analysis_sql, array_merge([$date_from_sql, $date_to_sql], $role_filter['params'])));

    $community_count = 0;
    if ($community_posts_exists) {
        $community_sql = "SELECT COUNT(DISTINCT u.ID)
                          FROM {$users_table} u
                          INNER JOIN {$community_posts_table} cp ON cp.user_id = u.ID
                          WHERE u.user_registered >= %s AND u.user_registered < %s" . $role_filter['sql'];
        $community_count = (int)$wpdb->get_var(myavana_prepare_sql($community_sql, array_merge([$date_from_sql, $date_to_sql], $role_filter['params'])));
    }

    $steps = [
        ['label' => 'Signup', 'count' => $signup_count],
        ['label' => 'Onboarding Completed', 'count' => $onboarding_count],
        ['label' => 'First Hair Entry', 'count' => $entry_count],
        ['label' => 'AI Analysis Used', 'count' => $analysis_count],
        ['label' => 'Community Shared', 'count' => $community_count],
    ];

    $previous = null;
    foreach ($steps as $index => $step) {
        $count = (int)$step['count'];
        $drop_off = 0.0;

        if ($previous !== null && $previous > 0) {
            $drop_off = round((($previous - $count) / $previous) * 100, 1);
            if ($drop_off < 0) {
                $drop_off = 0.0;
            }
        }

        $conversion = $signup_count > 0 ? round(($count / $signup_count) * 100, 1) : 0.0;

        $steps[$index]['drop_off'] = $drop_off;
        $steps[$index]['conversion'] = $conversion;
        $previous = $count;
    }

    return $steps;
}

/**
 * Cohort retention by registration week.
 */
function myavana_calculate_cohort_retention($date_from, $date_to, $selected_role, $analytics_table_exists, $analytics_table)
{
    global $wpdb;

    $users_table = $wpdb->users;
    $posts_table = $wpdb->posts;
    $role_filter = myavana_build_role_filter_clause($selected_role, 'u');

    $start = new DateTimeImmutable($date_from);
    $start = $start->modify('monday this week');
    $end = new DateTimeImmutable($date_to);
    $end = $end->modify('monday next week');

    $cohorts = [];

    for ($cursor = $start; $cursor <= $end; $cursor = $cursor->modify('+1 week')) {
        $cohort_start = $cursor->format('Y-m-d 00:00:00');
        $cohort_end = $cursor->modify('+1 week')->format('Y-m-d 00:00:00');

        $cohort_sql = "SELECT u.ID
                       FROM {$users_table} u
                       WHERE u.user_registered >= %s AND u.user_registered < %s" . $role_filter['sql'];
        $cohort_user_ids = $wpdb->get_col(myavana_prepare_sql($cohort_sql, array_merge([$cohort_start, $cohort_end], $role_filter['params'])));

        $cohort_size = count($cohort_user_ids);
        $retention_week_1 = 0.0;
        $retention_week_4 = 0.0;

        if ($cohort_size > 0) {
            $id_list = implode(',', array_map('intval', $cohort_user_ids));

            $w1_start = $cursor->modify('+1 week')->format('Y-m-d 00:00:00');
            $w1_end = $cursor->modify('+2 week')->format('Y-m-d 00:00:00');

            $w4_start = $cursor->modify('+4 week')->format('Y-m-d 00:00:00');
            $w4_end = $cursor->modify('+5 week')->format('Y-m-d 00:00:00');

            if ($analytics_table_exists) {
                $active_w1 = (int)$wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(DISTINCT user_id)
                     FROM {$analytics_table}
                     WHERE user_id IN ({$id_list})
                       AND visited_at >= %s
                       AND visited_at < %s",
                    $w1_start,
                    $w1_end
                ));

                $active_w4 = (int)$wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(DISTINCT user_id)
                     FROM {$analytics_table}
                     WHERE user_id IN ({$id_list})
                       AND visited_at >= %s
                       AND visited_at < %s",
                    $w4_start,
                    $w4_end
                ));
            }
            else {
                $active_w1 = (int)$wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(DISTINCT post_author)
                     FROM {$posts_table}
                     WHERE post_author IN ({$id_list})
                       AND post_type = 'hair_journey_entry'
                       AND post_status = 'publish'
                       AND post_date >= %s
                       AND post_date < %s",
                    $w1_start,
                    $w1_end
                ));

                $active_w4 = (int)$wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(DISTINCT post_author)
                     FROM {$posts_table}
                     WHERE post_author IN ({$id_list})
                       AND post_type = 'hair_journey_entry'
                       AND post_status = 'publish'
                       AND post_date >= %s
                       AND post_date < %s",
                    $w4_start,
                    $w4_end
                ));
            }

            $retention_week_1 = round(($active_w1 / $cohort_size) * 100, 1);
            $retention_week_4 = round(($active_w4 / $cohort_size) * 100, 1);
        }

        $cohorts[] = [
            'label' => $cursor->format('M d'),
            'cohort_size' => $cohort_size,
            'week_1' => $retention_week_1,
            'week_4' => $retention_week_4,
        ];
    }

    return array_slice($cohorts, -12);
}

/**
 * CSV export for current dashboard filters and snapshots.
 */
function myavana_export_intelligence_csv($filters, $summary_rows, $top_pages, $funnel, $cohorts)
{
    if (!current_user_can('manage_options')) {
        return;
    }

    nocache_headers();

    $filename = sprintf(
        'myavana-intelligence-%s-to-%s-%s.csv',
        $filters['date_from'],
        $filters['date_to'],
        gmdate('Ymd-His')
    );

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);

    $output = fopen('php://output', 'w');
    if (!$output) {
        exit;
    }

    fputcsv($output, ['Myavana Intelligence Export']);
    fputcsv($output, ['Date From', $filters['date_from']]);
    fputcsv($output, ['Date To', $filters['date_to']]);
    fputcsv($output, ['Role Filter', $filters['role']]);
    fputcsv($output, []);

    fputcsv($output, ['Summary']);
    fputcsv($output, ['Metric', 'Value']);
    foreach ($summary_rows as $row) {
        fputcsv($output, [$row['metric'], $row['value']]);
    }
    fputcsv($output, []);

    fputcsv($output, ['Top Pages']);
    fputcsv($output, ['Path', 'Visits', 'Avg Time (seconds)', 'Last Seen']);
    foreach ($top_pages as $row) {
        fputcsv($output, [
            $row->path,
            (int)$row->visits,
            round((float)$row->avg_time, 2),
            $row->last_seen,
        ]);
    }
    fputcsv($output, []);

    fputcsv($output, ['Funnel Drop-Off']);
    fputcsv($output, ['Step', 'Users', 'Drop-Off %', 'Conversion %']);
    foreach ($funnel as $step) {
        fputcsv($output, [
            $step['label'],
            (int)$step['count'],
            (float)$step['drop_off'],
            (float)$step['conversion'],
        ]);
    }
    fputcsv($output, []);

    fputcsv($output, ['Cohort Retention']);
    fputcsv($output, ['Cohort Week', 'Cohort Size', 'Week 1 Retention %', 'Week 4 Retention %']);
    foreach ($cohorts as $cohort) {
        fputcsv($output, [
            $cohort['label'],
            (int)$cohort['cohort_size'],
            (float)$cohort['week_1'],
            (float)$cohort['week_4'],
        ]);
    }

    fclose($output);
    exit;
}

/**
 * Intelligence dashboard page.
 */
function myavana_intelligence_dashboard_page()
{
    if (!current_user_can('manage_options')) {
        return;
    }

    global $wpdb;
    $gamification_notice = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['myavana_gamification_action'])) {
        check_admin_referer('myavana_save_gamification_settings', 'myavana_gamification_nonce');
        $action = sanitize_key(wp_unslash($_POST['myavana_gamification_action']));

        if ($action === 'save_rewards') {
            $settings = myavana_sanitize_gamification_reward_settings(wp_unslash($_POST['myavana_reward_settings'] ?? []));
            update_option('myavana_gamification_reward_settings', $settings, false);
            $gamification_notice = 'Reward settings updated.';
        } elseif ($action === 'save_challenges') {
            $challenges = myavana_sanitize_gamification_challenges(wp_unslash($_POST['myavana_challenges'] ?? []));
            update_option('myavana_gamification_active_challenges', $challenges, false);
            $gamification_notice = 'Challenge configuration updated.';
        }
    }

    $analytics_table = $wpdb->prefix . 'myavana_site_analytics';
    $reports_table = $wpdb->prefix . 'myavana_ci_content_reports';
    $profiles_table = $wpdb->prefix . 'myavana_profiles';
    $users_table = $wpdb->users;
    $posts_table = $wpdb->posts;
    $postmeta_table = $wpdb->postmeta;
    $analytics_table_exists = (bool)$wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $analytics_table));
    $reports_table_exists = (bool)$wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $reports_table));

    $default_date_to = wp_date('Y-m-d');
    $default_date_from = wp_date('Y-m-d', strtotime('-29 days'));

    $date_from = myavana_validate_dashboard_date($_GET['date_from'] ?? $default_date_from, $default_date_from);
    $date_to = myavana_validate_dashboard_date($_GET['date_to'] ?? $default_date_to, $default_date_to);

    if (strtotime($date_from) > strtotime($date_to)) {
        $temp = $date_from;
        $date_from = $date_to;
        $date_to = $temp;
    }

    $roles_data = function_exists('wp_roles') ? wp_roles() : null;
    $available_roles = $roles_data ? $roles_data->roles : [];

    $selected_role = sanitize_key(wp_unslash($_GET['user_role'] ?? 'all'));
    if ($selected_role !== 'all' && !isset($available_roles[$selected_role])) {
        $selected_role = 'all';
    }

    $date_from_sql = $date_from . ' 00:00:00';
    $date_to_sql = wp_date('Y-m-d 00:00:00', strtotime($date_to . ' +1 day'));
    $range_label = $date_from . ' to ' . $date_to;

    $role_filter = myavana_build_role_filter_clause($selected_role, 'u');
    $analytics_role_filter = myavana_build_analytics_role_filter_clause($selected_role, 'sa');
    $reward_settings = Myavana_Gamification::get_reward_settings();
    $challenge_definitions = Myavana_Gamification::get_challenge_definitions();

    // --- New Implementation using Myavana_Analytics_Model ---

    // 1. Overview Metrics
    $overview_metrics = Myavana_Analytics_Model::get_overview_metrics($date_from, $date_to, $selected_role);
    $total_users = $overview_metrics['total_users'];
    $new_users = $overview_metrics['new_users'];
    $analysis_users = $overview_metrics['analysis_users'];
    $ai_entries = $overview_metrics['ai_entries'];
    $total_visits = $overview_metrics['total_visits'];
    $unique_visitors = $overview_metrics['unique_visitors'];
    $bounce_rate = $overview_metrics['bounce_rate'];
    $section_usage = $overview_metrics['section_usage'];

    // 2. Engagement Metrics
    $engagement_metrics = Myavana_Analytics_Model::get_engagement_metrics($date_from, $date_to, $selected_role);
    $avg_time_site = $engagement_metrics['avg_time_site'];
    $session_depth = $engagement_metrics['session_depth'];
    $returning_session_rate = $engagement_metrics['returning_session_rate'];
    $behavior_metrics = Myavana_Analytics_Model::get_behavior_metrics($date_from, $date_to, $selected_role);
    $tracked_events = $behavior_metrics['total_events'];
    $tracked_sessions = $behavior_metrics['tracked_sessions'];
    $tracked_actions = $behavior_metrics['tracked_actions'];
    $avg_section_dwell = $behavior_metrics['avg_section_dwell'];
    $top_sections = $behavior_metrics['top_sections'];
    $top_actions = $behavior_metrics['top_actions'];
    $ai_component_metrics = Myavana_Analytics_Model::get_ai_component_metrics($date_from, $date_to, $selected_role);
    $ai_components_generated = $ai_component_metrics['generated'];
    $ai_components_active = $ai_component_metrics['active'];
    $ai_components_saved = $ai_component_metrics['saved'];
    $ai_components_dismissed = $ai_component_metrics['dismissed'];
    $ai_components_applied = $ai_component_metrics['applied'];
    $ai_component_impressions = $ai_component_metrics['impressions'];
    $ai_top_pages = $ai_component_metrics['top_pages'];
    $ai_top_types = $ai_component_metrics['top_types'];
    $ai_recent_components = $ai_component_metrics['recent_components'];

    // 3. Top Pages
    $top_pages = Myavana_Analytics_Model::get_top_pages($date_from, $date_to, $selected_role);

    // Active users snapshot
    $active_users = Myavana_Analytics_Model::get_active_users($date_from, $date_to, $selected_role);

    // Hair type distribution
    $hair_type_distribution = Myavana_Analytics_Model::get_hair_type_distribution($date_from, $date_to, $selected_role);

    // Role distribution
    $role_distribution = Myavana_Analytics_Model::get_role_distribution($date_from, $date_to, $available_roles);

    // Funnel & Cohort
    $funnel_data = Myavana_Analytics_Model::get_funnel_data($date_from, $date_to, $selected_role);
    $cohort_retention = Myavana_Analytics_Model::get_cohort_retention($date_from, $date_to, $selected_role);
    $gamification_admin = myavana_get_gamification_admin_data($date_from_sql, $date_to_sql, $selected_role);
    $gamification_metrics = $gamification_admin['metrics'];
    $challenge_rows = $gamification_admin['challenge_rows'];
    $reward_reason_rows = $gamification_admin['reward_reason_rows'];
    $reward_user_rows = $gamification_admin['reward_user_rows'];

    $metrics = [
        'total_users' => $total_users,
        'analysis_users' => $analysis_users,
        'total_visits' => $total_visits,
        'unique_visitors' => $unique_visitors,
        'section_usage' => $section_usage,
        'bounce_rate' => $bounce_rate,
    ];
    $insights = myavana_generate_intelligence_insights($metrics, $range_label);
    if ($tracked_actions > 0 && $tracked_sessions > 0) {
        $insights[] = sprintf(
            'Behavior stream: %d tracked actions across %d instrumented sessions, averaging %.1f seconds of section dwell.',
            $tracked_actions,
            $tracked_sessions,
            round($avg_section_dwell, 1)
        );
    }
    if ($ai_components_generated > 0) {
        $insights[] = sprintf(
            'AI feed usage: %d components generated, %d saved, %d dismissed, and %d applied in the selected range.',
            $ai_components_generated,
            $ai_components_saved,
            $ai_components_dismissed,
            $ai_components_applied
        );
    }

    $selected_role_label = 'All Roles';
    if ($selected_role !== 'all' && isset($available_roles[$selected_role])) {
        $selected_role_label = translate_user_role($available_roles[$selected_role]['name']);
    }

    $signup_conversion_rate = $unique_visitors > 0 ? round(($new_users / $unique_visitors) * 100, 1) : 0.0;
    $analysis_activation_rate = $new_users > 0 ? round(($ai_entries / $new_users) * 100, 1) : 0.0;

    $summary_rows = [
        ['metric' => 'Role Filter', 'value' => $selected_role_label],
        ['metric' => 'Date Range', 'value' => $range_label],
        ['metric' => 'Total Users', 'value' => $total_users],
        ['metric' => 'New Users (Range)', 'value' => $new_users],
        ['metric' => 'Users With AI Analysis', 'value' => $analysis_users],
        ['metric' => 'AI Analysis Entries (Range)', 'value' => $ai_entries],
        ['metric' => 'Visits (Range)', 'value' => $total_visits],
        ['metric' => 'Unique Visitors (Range)', 'value' => $unique_visitors],
        ['metric' => 'Average Time On Page (s)', 'value' => round($avg_time_site, 2)],
        ['metric' => 'Low Engagement Sessions (%)', 'value' => round($bounce_rate, 1)],
        ['metric' => 'Visitor-to-Signup Conversion (%)', 'value' => $signup_conversion_rate],
        ['metric' => 'AI Activation from New Users (%)', 'value' => $analysis_activation_rate],
    ];

    $export_requested = sanitize_text_field(wp_unslash($_GET['myavana_export'] ?? ''));
    if ($export_requested === 'csv') {
        myavana_export_intelligence_csv(
        [
            'date_from' => $date_from,
            'date_to' => $date_to,
            'role' => $selected_role_label,
        ],
            $summary_rows,
            $top_pages,
            $funnel_data,
            $cohort_retention
        );
    }

    // 5. Active User Trends (DAU/WAU/MAU)
    $active_user_trends = Myavana_Analytics_Model::get_active_user_trends($selected_role);
    $dau = $active_user_trends['dau'];
    $wau = $active_user_trends['wau'];
    $mau = $active_user_trends['mau'];
    $stickiness = $active_user_trends['stickiness'];

    // 6. Charts & Tables Data
    $daily_visits = Myavana_Analytics_Model::get_daily_visits($date_from, $date_to, $selected_role);
    $device_mix_rows = Myavana_Analytics_Model::get_device_mix($date_from, $date_to, $selected_role);
    $referrer_rows = Myavana_Analytics_Model::get_referrers($date_from, $date_to, $selected_role);
    $page_engagement_rows = Myavana_Analytics_Model::get_page_engagement($date_from, $date_to, $selected_role);
    
    $signup_trend_rows = Myavana_Analytics_Model::get_signup_trend($date_from, $date_to, $selected_role);
    $ai_entry_trend_rows = Myavana_Analytics_Model::get_ai_entry_trend($date_from, $date_to, $selected_role);

    // 7. Community Stats
    $community_stats = Myavana_Analytics_Model::get_community_stats($date_from, $date_to, $selected_role);
    $community_posts_count = $community_stats['posts_count'];
    $community_video_count = $community_stats['video_count'];
    $community_interactions = $community_stats['interactions'];
    $top_creators = $community_stats['top_creators'];

    // 8. Moderation
    $moderation_stats = Myavana_Analytics_Model::get_moderation_reports();
    $pending_reports = $moderation_stats['pending'];
    $recent_reports = $moderation_stats['recent'];

    $chart_section_labels = array_map('ucfirst', array_keys($section_usage));
    $chart_section_values = array_values($section_usage);

    $chart_role_labels = array_map(function ($item) {
        return $item['label']; }, $role_distribution);
    $chart_role_values = array_map(function ($item) {
        return (int)$item['count']; }, $role_distribution);

    $chart_funnel_labels = array_map(function ($step) {
        return $step['label']; }, $funnel_data);
    $chart_funnel_counts = array_map(function ($step) {
        return (int)$step['count']; }, $funnel_data);

    $chart_cohort_labels = array_map(function ($cohort) {
        return $cohort['label']; }, $cohort_retention);
    $chart_cohort_week1 = array_map(function ($cohort) {
        return (float)$cohort['week_1']; }, $cohort_retention);
    $chart_cohort_week4 = array_map(function ($cohort) {
        return (float)$cohort['week_4']; }, $cohort_retention);

    $chart_daily_labels = array_map(function ($row) {
        return $row->visit_date; }, $daily_visits);
    $chart_daily_values = array_map(function ($row) {
        return (int)$row->visits; }, $daily_visits);

    $chart_device_labels = array_map(function ($row) {
        return ucfirst($row->device_type); }, $device_mix_rows);
    $chart_device_values = array_map(function ($row) {
        return (int)$row->total; }, $device_mix_rows);

    $chart_post_mix_labels = ['Image/Text Posts', 'Video Posts'];
    $chart_post_mix_values = [max(0, $community_posts_count - $community_video_count), $community_video_count];
    $chart_hair_type_labels = array_map(function ($row) {
        return $row->hair_type; }, $hair_type_distribution);
    $chart_hair_type_values = array_map(function ($row) {
        return (int)$row->total; }, $hair_type_distribution);

    $chart_signup_labels = array_map(function ($row) {
        return $row->signup_date; }, $signup_trend_rows);
    $chart_signup_values = array_map(function ($row) {
        return (int)$row->total; }, $signup_trend_rows);

    $chart_ai_entry_labels = array_map(function ($row) {
        return $row->entry_date; }, $ai_entry_trend_rows);
    $chart_ai_entry_values = array_map(function ($row) {
        return (int)$row->total; }, $ai_entry_trend_rows);

    $chart_page_time_labels = array_map(function ($row) {
        return $row->path; }, $page_engagement_rows);
    $chart_page_time_values = array_map(function ($row) {
        return round((float)$row->avg_time, 2); }, $page_engagement_rows);
    $chart_challenge_labels = array_map(function ($row) {
        return $row['title']; }, $challenge_rows);
    $chart_challenge_values = array_map(function ($row) {
        return (int) $row['completions']; }, $challenge_rows);
    $chart_reward_reason_labels = array_map(function ($row) {
        return $row['reason']; }, $reward_reason_rows);
    $chart_reward_reason_values = array_map(function ($row) {
        return (int) $row['points']; }, $reward_reason_rows);

    $dashboard_page = sanitize_text_field(wp_unslash($_GET['page'] ?? 'myavana-intelligence-dashboard'));
    $dashboard_tabs = [
        'myavana-intelligence-dashboard' => ['id' => 'overview', 'label' => 'Overview'],
        'myavana-executive-intelligence' => ['id' => 'executive', 'label' => 'Executive'],
        'myavana-growth-intelligence' => ['id' => 'growth', 'label' => 'Growth'],
        'myavana-community-intelligence' => ['id' => 'community', 'label' => 'Community'],
        'myavana-retention-intelligence' => ['id' => 'retention', 'label' => 'Retention'],
        'myavana-ai-intelligence' => ['id' => 'ai', 'label' => 'AI Lab'],
        'myavana-operations-intelligence' => ['id' => 'operations', 'label' => 'Operations'],
        'myavana-gamification-intelligence' => ['id' => 'gamification', 'label' => 'Gamification'],
    ];
    $active_tab = $dashboard_tabs[$dashboard_page]['id'] ?? 'overview';

    $show_executive = in_array($active_tab, ['overview', 'executive'], true);
    $show_growth = in_array($active_tab, ['overview', 'growth'], true);
    $show_community = in_array($active_tab, ['overview', 'community'], true);
    $show_retention = in_array($active_tab, ['overview', 'retention'], true);
    $show_ai = in_array($active_tab, ['overview', 'ai'], true);
    $show_operations = in_array($active_tab, ['overview', 'operations'], true);
    $show_gamification = $active_tab === 'gamification';

    $current_user = wp_get_current_user();
    $is_administrator = in_array('administrator', (array)$current_user->roles, true) || current_user_can('manage_network');

    $base_query_for_links = [
        'date_from' => $date_from,
        'date_to' => $date_to,
        'user_role' => $selected_role,
    ];
    $reward_field_labels = [
        'daily_checkin_base' => 'Daily check-in base',
        'daily_checkin_streak_bonus_7' => '7-day streak bonus',
        'daily_checkin_streak_bonus_30' => '30-day streak bonus',
        'daily_checkin_streak_bonus_10x' => '10-day milestone bonus',
        'entry_created' => 'Entry created',
        'entry_photo_bonus' => 'Photo bonus',
        'entry_video_bonus' => 'Video bonus',
        'ai_entry_bonus' => 'AI entry bonus',
        'ai_analysis_saved' => 'AI analysis saved',
        'goal_created' => 'Goal created',
        'goal_milestone' => 'Goal milestone',
        'goal_completed' => 'Goal completed',
        'routine_created' => 'Routine created',
        'routine_completed' => 'Routine completed',
        'onboarding_completed' => 'Onboarding completed',
    ];
    $challenge_metric_options = [
        'entries_week' => 'Entries this week',
        'routines_week' => 'Routines completed this week',
        'ai_week' => 'AI analyses this week',
        'community_posts_week' => 'Community posts this week',
        'community_posts_month' => 'Community posts this month',
        'video_entries_month' => 'Video entries this month',
        'current_streak' => 'Current streak',
        'goals_completed_total' => 'Goals completed total',
        'total_entries' => 'Total entries',
        'total_ai_analyses' => 'Total AI analyses',
        'checkins_week' => 'Check-ins this week',
    ];
?>
    <div class="wrap myavana-intelligence-wrap">
        <style>
            .myavana-intelligence-wrap { background: linear-gradient(180deg, #f5f7fb 0%, #f4f2ef 100%); margin: 0; padding: 20px 14px 40px; border-radius: 16px; }
            .myavana-intelligence-wrap .myavana-hero { background: radial-gradient(circle at 10% 20%, #2e4f7f 0%, #1a2940 55%, #111820 100%); color: #fff; padding: 22px; border-radius: 16px; box-shadow: 0 14px 42px rgba(25, 38, 58, 0.35); margin-bottom: 16px; }
            .myavana-intelligence-wrap .myavana-hero h1 { margin: 0 0 8px; font-size: 28px; font-weight: 800; letter-spacing: .01em; color: #f8fafc; }
            .myavana-intelligence-wrap .myavana-hero p { margin: 0; opacity: .92; font-size: 14px; }
            .myavana-intelligence-wrap .myavana-nav { display:flex; flex-wrap:wrap; gap:10px; margin: 14px 0 18px; }
            .myavana-intelligence-wrap .myavana-nav-link { display:inline-flex; align-items:center; text-decoration:none; border:1px solid rgba(26, 41, 64, .12); background:#fff; color:#1f2937; border-radius:999px; padding:8px 14px; font-size:12px; font-weight:700; letter-spacing:.04em; text-transform:uppercase; }
            .myavana-intelligence-wrap .myavana-nav-link.active { background:#1f3b61; color:#fff; border-color:#1f3b61; }
            .myavana-intelligence-wrap .myavana-grid { display:grid; grid-template-columns: repeat(auto-fit, minmax(220px,1fr)); gap:16px; margin:16px 0 20px; }
            .myavana-intelligence-wrap .myavana-card, .myavana-intelligence-wrap .chart-wrap { background:#fff; border:1px solid rgba(26, 41, 64, .08); border-radius:14px; padding:16px; box-shadow:0 8px 22px rgba(30, 41, 59, .08); }
            .myavana-intelligence-wrap .metric-label { color:#5b6472; font-size:11px; text-transform:uppercase; letter-spacing:.08em; font-weight:700; }
            .myavana-intelligence-wrap .metric-value { font-size:30px; font-weight:800; margin-top:4px; color:#0f172a; }
            .myavana-intelligence-wrap .metric-subtle { color:#64748b; font-size:12px; margin-top:4px; }
            .myavana-intelligence-wrap .myavana-section-title { margin:24px 0 10px; font-size:18px; letter-spacing:.01em; color:#132238; }
            .myavana-intelligence-wrap .myavana-filter-form { display:flex; flex-wrap:wrap; gap:12px; align-items:flex-end; margin:8px 0 20px; }
            .myavana-intelligence-wrap .myavana-filter-group { display:flex; flex-direction:column; gap:4px; }
            .myavana-intelligence-wrap .myavana-filter-group label { font-weight:700; font-size:11px; color:#4b5563; text-transform:uppercase; letter-spacing:.06em; }
            .myavana-intelligence-wrap .myavana-filter-group input,
            .myavana-intelligence-wrap .myavana-filter-group select { min-width:160px; border:1px solid #cbd5e1; border-radius:8px; padding:7px 10px; }
            .myavana-intelligence-wrap .myavana-filter-actions { display:flex; gap:8px; }
            .myavana-intelligence-wrap .myavana-chip { display:inline-flex; align-items:center; border-radius:999px; background:#e6eef8; color:#1f3b61; padding:6px 11px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; margin-right:8px; }
            .myavana-intelligence-wrap table { width:100%; border-collapse: collapse; background:#fff; }
            .myavana-intelligence-wrap th, .myavana-intelligence-wrap td { padding:10px; border:1px solid #e5eaf2; text-align:left; vertical-align:top; font-size:13px; }
            .myavana-intelligence-wrap th { background:#f7f9fc; font-size:12px; text-transform:uppercase; letter-spacing:.05em; color:#4a5568; }
            .myavana-intelligence-wrap .insight-list li { margin:8px 0; }
            .myavana-intelligence-wrap .chart-wrap canvas { height:300px !important; max-height:300px; }
            @media (max-width: 782px) {
                .myavana-intelligence-wrap .myavana-hero h1 { font-size: 22px; }
                .myavana-intelligence-wrap .myavana-grid { grid-template-columns: 1fr; }
            }
        </style>

        <div class="myavana-hero">
            <h1>Myavana Intelligence Suite</h1>
            <p>Enterprise-grade analytics for growth, retention, community quality, and AI adoption.</p>
        </div>

        <div class="myavana-nav">
            <?php foreach ($dashboard_tabs as $page_slug => $tab): ?>
                <?php
        $tab_url = add_query_arg(array_merge(['page' => $page_slug], $base_query_for_links), admin_url('admin.php'));
        $is_active_tab = $active_tab === $tab['id'];
?>
                <a class="myavana-nav-link<?php echo $is_active_tab ? ' active' : ''; ?>" href="<?php echo esc_url($tab_url); ?>">
                    <?php echo esc_html($tab['label']); ?>
                </a>
            <?php
    endforeach; ?>
        </div>

        <form class="myavana-filter-form" method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>">
            <input type="hidden" name="page" value="<?php echo esc_attr($dashboard_page); ?>" />

            <div class="myavana-filter-group">
                <label for="myavana-date-from">Date From</label>
                <input id="myavana-date-from" type="date" name="date_from" value="<?php echo esc_attr($date_from); ?>" />
            </div>

            <div class="myavana-filter-group">
                <label for="myavana-date-to">Date To</label>
                <input id="myavana-date-to" type="date" name="date_to" value="<?php echo esc_attr($date_to); ?>" />
            </div>

            <div class="myavana-filter-group">
                <label for="myavana-user-role">User Role</label>
                <select id="myavana-user-role" name="user_role">
                    <option value="all" <?php selected($selected_role, 'all'); ?>>All Roles</option>
                    <?php foreach ($available_roles as $role_key => $role_meta): ?>
                        <option value="<?php echo esc_attr($role_key); ?>" <?php selected($selected_role, $role_key); ?>>
                            <?php echo esc_html(translate_user_role($role_meta['name'])); ?>
                        </option>
                    <?php
    endforeach; ?>
                </select>
            </div>

            <div class="myavana-filter-actions">
                <button type="submit" class="button button-primary">Apply Filters</button>
                <a class="button" href="<?php echo esc_url(add_query_arg(array_merge([
        'page' => $dashboard_page,
        'myavana_export' => 'csv',
    ], $base_query_for_links), admin_url('admin.php'))); ?>">Export CSV</a>
            </div>
        </form>

        <p>
            <span class="myavana-chip">Tab: <?php echo esc_html(ucfirst($active_tab)); ?></span>
            <span class="myavana-chip">Role: <?php echo esc_html($selected_role_label); ?></span>
            <span class="myavana-chip">Range: <?php echo esc_html($range_label); ?></span>
        </p>

        <?php if ($gamification_notice !== ''): ?>
            <div class="notice notice-success is-dismissible"><p><?php echo esc_html($gamification_notice); ?></p></div>
        <?php endif; ?>

        <div class="myavana-grid">
            <div class="myavana-card"><div class="metric-label">Total Users</div><div class="metric-value"><?php echo esc_html(number_format_i18n($total_users)); ?></div></div>
            <div class="myavana-card"><div class="metric-label">New Users (Range)</div><div class="metric-value"><?php echo esc_html(number_format_i18n($new_users)); ?></div></div>
            <div class="myavana-card"><div class="metric-label">Users With AI Analysis</div><div class="metric-value"><?php echo esc_html(number_format_i18n($analysis_users)); ?></div></div>
            <div class="myavana-card"><div class="metric-label">AI Analysis Entries</div><div class="metric-value"><?php echo esc_html(number_format_i18n($ai_entries)); ?></div></div>
            <div class="myavana-card"><div class="metric-label">Visits (Range)</div><div class="metric-value"><?php echo esc_html(number_format_i18n($total_visits)); ?></div></div>
            <div class="myavana-card"><div class="metric-label">Unique Visitors</div><div class="metric-value"><?php echo esc_html(number_format_i18n($unique_visitors)); ?></div></div>
            <div class="myavana-card"><div class="metric-label">Avg Time On Page</div><div class="metric-value"><?php echo esc_html(myavana_seconds_to_readable((int)round($avg_time_site))); ?></div></div>
            <div class="myavana-card"><div class="metric-label">Low Engagement</div><div class="metric-value"><?php echo esc_html(number_format_i18n((float)$bounce_rate, 1)); ?>%</div></div>
            <div class="myavana-card"><div class="metric-label">DAU / MAU</div><div class="metric-value"><?php echo esc_html(number_format_i18n((float)$stickiness, 1)); ?>%</div><div class="metric-subtle"><?php echo esc_html($dau); ?> daily / <?php echo esc_html($mau); ?> monthly</div></div>
            <div class="myavana-card"><div class="metric-label">WAU</div><div class="metric-value"><?php echo esc_html(number_format_i18n($wau)); ?></div><div class="metric-subtle">Weekly active users</div></div>
            <div class="myavana-card"><div class="metric-label">Session Depth</div><div class="metric-value"><?php echo esc_html(number_format_i18n((float)$session_depth, 2)); ?></div><div class="metric-subtle">Pages per session</div></div>
            <div class="myavana-card"><div class="metric-label">Returning Session Rate</div><div class="metric-value"><?php echo esc_html(number_format_i18n((float)$returning_session_rate, 1)); ?>%</div><div class="metric-subtle">Sessions with 2+ page views</div></div>
            <div class="myavana-card"><div class="metric-label">Visitor-to-Signup</div><div class="metric-value"><?php echo esc_html(number_format_i18n((float)$signup_conversion_rate, 1)); ?>%</div><div class="metric-subtle">Conversion in selected range</div></div>
            <div class="myavana-card"><div class="metric-label">Pending Reports</div><div class="metric-value"><?php echo esc_html(number_format_i18n($pending_reports)); ?></div><div class="metric-subtle">Community moderation queue</div></div>
            <div class="myavana-card"><div class="metric-label">Tracked Actions</div><div class="metric-value"><?php echo esc_html(number_format_i18n($tracked_actions)); ?></div><div class="metric-subtle">Clicks, forms, and AI interactions</div></div>
            <div class="myavana-card"><div class="metric-label">Avg Section Dwell</div><div class="metric-value"><?php echo esc_html(number_format_i18n((float)$avg_section_dwell, 1)); ?>s</div><div class="metric-subtle">Observed section engagement</div></div>
            <div class="myavana-card"><div class="metric-label">AI Components Saved</div><div class="metric-value"><?php echo esc_html(number_format_i18n($ai_components_saved)); ?></div><div class="metric-subtle">Suggestion save actions</div></div>
            <div class="myavana-card"><div class="metric-label">AI Components Applied</div><div class="metric-value"><?php echo esc_html(number_format_i18n($ai_components_applied)); ?></div><div class="metric-subtle">CTA-driven follow-through</div></div>
        </div>

        <h2 class="myavana-section-title">AI Website Intelligence Insights</h2>
        <div class="myavana-card">
            <ul class="insight-list">
                <?php foreach ($insights as $insight): ?>
                    <li><?php echo esc_html($insight); ?></li>
                <?php
    endforeach; ?>
            </ul>
        </div>

        <?php if ($show_executive): ?>
            <h2 class="myavana-section-title">Executive Command Center</h2>
            <div class="myavana-grid">
                <div class="chart-wrap">
                    <h3>Daily Traffic Trend</h3>
                    <canvas id="myavanaDailyTrafficChart"></canvas>
                </div>
                <div class="chart-wrap">
                    <h3>Device Mix</h3>
                    <canvas id="myavanaDeviceMixChart"></canvas>
                </div>
            </div>

            <div class="myavana-card">
                <h3>Top Referrers</h3>
                <table>
                    <thead><tr><th>Referrer</th><th>Visits</th></tr></thead>
                    <tbody>
                    <?php if (!empty($referrer_rows)): ?>
                        <?php foreach ($referrer_rows as $ref): ?>
                            <tr>
                                <td><?php echo esc_html($ref->referrer); ?></td>
                                <td><?php echo esc_html(number_format_i18n((int)$ref->total)); ?></td>
                            </tr>
                        <?php
            endforeach; ?>
                    <?php
        else: ?>
                        <tr><td colspan="2">No referrer data in selected range.</td></tr>
                    <?php
        endif; ?>
                    </tbody>
                </table>
            </div>
        <?php
    endif; ?>

        <?php if ($show_growth): ?>
            <h2 class="myavana-section-title">Growth Lab</h2>
            <div class="myavana-grid">
                <div class="chart-wrap">
                    <h3>User Journey Funnel</h3>
                    <canvas id="myavanaFunnelChart"></canvas>
                </div>
                <div class="chart-wrap">
                    <h3>User Role Distribution</h3>
                    <canvas id="myavanaRoleDistributionChart"></canvas>
                </div>
            </div>
            <div class="myavana-card">
                <table>
                    <thead>
                        <tr><th>Step</th><th>Users</th><th>Drop-Off</th><th>Conversion</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($funnel_data as $step): ?>
                            <tr>
                                <td><?php echo esc_html($step['label']); ?></td>
                                <td><?php echo esc_html(number_format_i18n((int)$step['count'])); ?></td>
                                <td><?php echo esc_html(number_format_i18n((float)$step['drop_off'], 1)); ?>%</td>
                                <td><?php echo esc_html(number_format_i18n((float)$step['conversion'], 1)); ?>%</td>
                            </tr>
                        <?php
        endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php
    endif; ?>

        <?php if ($show_ai): ?>
            <h2 class="myavana-section-title">AI Intelligence Lab</h2>
            <div class="myavana-grid">
                <div class="myavana-card"><div class="metric-label">AI Activation Rate</div><div class="metric-value"><?php echo esc_html(number_format_i18n((float)$analysis_activation_rate, 1)); ?>%</div><div class="metric-subtle">AI entries per new user</div></div>
                <div class="myavana-card"><div class="metric-label">AI Analyses Total</div><div class="metric-value"><?php echo esc_html(number_format_i18n($analysis_users)); ?></div><div class="metric-subtle">Users with analysis history</div></div>
                <div class="myavana-card"><div class="metric-label">AI Entries (Range)</div><div class="metric-value"><?php echo esc_html(number_format_i18n($ai_entries)); ?></div><div class="metric-subtle">Journal entries created from AI</div></div>
                <div class="myavana-card"><div class="metric-label">AI Components Generated</div><div class="metric-value"><?php echo esc_html(number_format_i18n($ai_components_generated)); ?></div><div class="metric-subtle">Dynamic in-app suggestions</div></div>
                <div class="myavana-card"><div class="metric-label">AI Feed Impressions</div><div class="metric-value"><?php echo esc_html(number_format_i18n($ai_component_impressions)); ?></div><div class="metric-subtle">Rendered suggestion cards</div></div>
                <div class="myavana-card"><div class="metric-label">Active AI Components</div><div class="metric-value"><?php echo esc_html(number_format_i18n($ai_components_active)); ?></div><div class="metric-subtle">Currently available to users</div></div>
                <div class="myavana-card"><div class="metric-label">Saved AI Components</div><div class="metric-value"><?php echo esc_html(number_format_i18n($ai_components_saved)); ?></div><div class="metric-subtle">Saved for later</div></div>
                <div class="myavana-card"><div class="metric-label">Dismissed AI Components</div><div class="metric-value"><?php echo esc_html(number_format_i18n($ai_components_dismissed)); ?></div><div class="metric-subtle">Signals for tuning relevance</div></div>
                <div class="myavana-card"><div class="metric-label">Applied AI Components</div><div class="metric-value"><?php echo esc_html(number_format_i18n($ai_components_applied)); ?></div><div class="metric-subtle">Users acted on the guidance</div></div>
            </div>

            <div class="myavana-grid">
                <div class="chart-wrap">
                    <h3>Daily Signups Trend</h3>
                    <canvas id="myavanaSignupTrendChart"></canvas>
                </div>
                <div class="chart-wrap">
                    <h3>Daily AI Entry Trend</h3>
                    <canvas id="myavanaAiEntryTrendChart"></canvas>
                </div>
                <div class="chart-wrap">
                    <h3>Hair Type Distribution (New Users)</h3>
                    <canvas id="myavanaHairTypeChart"></canvas>
                </div>
            </div>

            <div class="myavana-card">
                <h3>Hair Type Breakdown</h3>
                <table>
                    <thead><tr><th>Hair Type</th><th>Users</th></tr></thead>
                    <tbody>
                        <?php if (!empty($hair_type_distribution)): ?>
                            <?php foreach ($hair_type_distribution as $row): ?>
                                <tr>
                                    <td><?php echo esc_html($row->hair_type); ?></td>
                                    <td><?php echo esc_html(number_format_i18n((int)$row->total)); ?></td>
                                </tr>
                            <?php
            endforeach; ?>
                        <?php
        else: ?>
                            <tr><td colspan="2">No hair profile data in selected range.</td></tr>
                        <?php
        endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="myavana-grid">
                <div class="myavana-card">
                    <h3>Top AI Feed Pages</h3>
                    <table>
                        <thead><tr><th>Page</th><th>Generated</th></tr></thead>
                        <tbody>
                            <?php if (!empty($ai_top_pages)): ?>
                                <?php foreach ($ai_top_pages as $row): ?>
                                    <tr>
                                        <td><?php echo esc_html(ucfirst($row['page_context'])); ?></td>
                                        <td><?php echo esc_html(number_format_i18n((int) $row['total'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="2">No AI component page data yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="myavana-card">
                    <h3>Top AI Component Types</h3>
                    <table>
                        <thead><tr><th>Type</th><th>Total</th></tr></thead>
                        <tbody>
                            <?php if (!empty($ai_top_types)): ?>
                                <?php foreach ($ai_top_types as $row): ?>
                                    <tr>
                                        <td><?php echo esc_html(ucwords(str_replace('_', ' ', (string) $row['component_type']))); ?></td>
                                        <td><?php echo esc_html(number_format_i18n((int) $row['total'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="2">No AI component type data yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="myavana-card">
                <h3>Recent AI Components</h3>
                <table>
                    <thead><tr><th>Title</th><th>Type</th><th>Page</th><th>Status</th><th>Generated</th></tr></thead>
                    <tbody>
                        <?php if (!empty($ai_recent_components)): ?>
                            <?php foreach ($ai_recent_components as $row): ?>
                                <tr>
                                    <td><?php echo esc_html($row['title']); ?></td>
                                    <td><?php echo esc_html(ucwords(str_replace('_', ' ', (string) $row['component_type']))); ?></td>
                                    <td><?php echo esc_html(ucfirst((string) $row['page_context'])); ?></td>
                                    <td><?php echo esc_html(ucfirst((string) $row['status'])); ?></td>
                                    <td><?php echo esc_html((string) $row['generated_at']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5">No AI components generated in the selected range.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php
    endif; ?>

        <?php if ($show_operations): ?>
            <h2 class="myavana-section-title">Operations Intelligence</h2>
            <div class="myavana-grid">
                <div class="myavana-card"><div class="metric-label">Returning Session Rate</div><div class="metric-value"><?php echo esc_html(number_format_i18n((float)$returning_session_rate, 1)); ?>%</div><div class="metric-subtle">Operational stickiness</div></div>
                <div class="myavana-card"><div class="metric-label">Session Depth</div><div class="metric-value"><?php echo esc_html(number_format_i18n((float)$session_depth, 2)); ?></div><div class="metric-subtle">Pages visited per session</div></div>
                <div class="myavana-card"><div class="metric-label">Avg Time on Site</div><div class="metric-value"><?php echo esc_html(myavana_seconds_to_readable((int)round($avg_time_site))); ?></div><div class="metric-subtle">Range average</div></div>
                <div class="myavana-card"><div class="metric-label">Tracked Events</div><div class="metric-value"><?php echo esc_html(number_format_i18n($tracked_events)); ?></div><div class="metric-subtle">Behavioral event stream volume</div></div>
                <div class="myavana-card"><div class="metric-label">Tracked Sessions</div><div class="metric-value"><?php echo esc_html(number_format_i18n($tracked_sessions)); ?></div><div class="metric-subtle">Sessions with event telemetry</div></div>
                <div class="myavana-card"><div class="metric-label">Avg Section Dwell</div><div class="metric-value"><?php echo esc_html(number_format_i18n((float)$avg_section_dwell, 1)); ?>s</div><div class="metric-subtle">Section-level engagement</div></div>
            </div>

            <div class="myavana-grid">
                <div class="chart-wrap">
                    <h3>Top Pages by Avg Time Spent</h3>
                    <canvas id="myavanaPageTimeChart"></canvas>
                </div>
            </div>

            <div class="myavana-card">
                <h3>Operational Page Engagement</h3>
                <table>
                    <thead><tr><th>Path</th><th>Visits</th><th>Avg Time (s)</th></tr></thead>
                    <tbody>
                        <?php if (!empty($page_engagement_rows)): ?>
                            <?php foreach ($page_engagement_rows as $row): ?>
                                <tr>
                                    <td><?php echo esc_html($row->path); ?></td>
                                    <td><?php echo esc_html(number_format_i18n((int)$row->visits)); ?></td>
                                    <td><?php echo esc_html(number_format_i18n((float)$row->avg_time, 2)); ?></td>
                                </tr>
                            <?php
            endforeach; ?>
                        <?php
        else: ?>
                            <tr><td colspan="3">No page engagement diagnostics in selected range.</td></tr>
                        <?php
        endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="myavana-grid">
                <div class="myavana-card">
                    <h3>Top Sections by Dwell</h3>
                    <table>
                        <thead><tr><th>Section</th><th>Views</th><th>Total Dwell (s)</th></tr></thead>
                        <tbody>
                            <?php if (!empty($top_sections)): ?>
                                <?php foreach ($top_sections as $row): ?>
                                    <tr>
                                        <td><?php echo esc_html(ucwords(str_replace(['_', '-'], ' ', (string) $row['section_key']))); ?></td>
                                        <td><?php echo esc_html(number_format_i18n((int) $row['views'])); ?></td>
                                        <td><?php echo esc_html(number_format_i18n((int) $row['total_dwell'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="3">No section-level dwell data yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="myavana-card">
                    <h3>Top Tracked Actions</h3>
                    <table>
                        <thead><tr><th>Action</th><th>Total</th></tr></thead>
                        <tbody>
                            <?php if (!empty($top_actions)): ?>
                                <?php foreach ($top_actions as $row): ?>
                                    <tr>
                                        <td><?php echo esc_html(ucwords(str_replace('_', ' ', (string) $row['event_name']))); ?></td>
                                        <td><?php echo esc_html(number_format_i18n((int) $row['total'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="2">No tracked action data yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php
    endif; ?>

        <?php if ($show_community): ?>
            <h2 class="myavana-section-title">Community Command</h2>
            <div class="myavana-grid">
                <div class="myavana-card"><div class="metric-label">Community Posts</div><div class="metric-value"><?php echo esc_html(number_format_i18n($community_posts_count)); ?></div></div>
                <div class="myavana-card"><div class="metric-label">Video Posts</div><div class="metric-value"><?php echo esc_html(number_format_i18n($community_video_count)); ?></div></div>
                <div class="myavana-card"><div class="metric-label">Community Interactions</div><div class="metric-value"><?php echo esc_html(number_format_i18n($community_interactions)); ?></div></div>
                <div class="myavana-card"><div class="metric-label">Pending Reports</div><div class="metric-value"><?php echo esc_html(number_format_i18n($pending_reports)); ?></div></div>
            </div>

            <div class="myavana-grid">
                <div class="chart-wrap">
                    <h3>Community Post Mix</h3>
                    <canvas id="myavanaCommunityPostMixChart"></canvas>
                </div>
                <div class="chart-wrap">
                    <h3>Section Usage</h3>
                    <canvas id="myavanaSectionUsageChart"></canvas>
                </div>
            </div>

            <div class="myavana-card">
                <h3>Top Creators</h3>
                <table>
                    <thead><tr><th>Creator</th><th>Posts</th><th>Engagement</th></tr></thead>
                    <tbody>
                        <?php if (!empty($top_creators)): ?>
                            <?php foreach ($top_creators as $creator): ?>
                                <tr>
                                    <td><?php echo esc_html($creator->display_name ?: 'User #' . (int)$creator->ID); ?></td>
                                    <td><?php echo esc_html(number_format_i18n((int)$creator->posts_count)); ?></td>
                                    <td><?php echo esc_html(number_format_i18n((int)$creator->engagement)); ?></td>
                                </tr>
                            <?php
            endforeach; ?>
                        <?php
        else: ?>
                            <tr><td colspan="3">No creator data in selected range.</td></tr>
                        <?php
        endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($reports_table_exists): ?>
                <div class="myavana-card">
                    <h3>Recent Moderation Reports</h3>
                    <table>
                        <thead><tr><th>Type</th><th>Content ID</th><th>Reason</th><th>Status</th><th>Created</th></tr></thead>
                        <tbody>
                            <?php if (!empty($recent_reports)): ?>
                                <?php foreach ($recent_reports as $report): ?>
                                    <tr>
                                        <td><?php echo esc_html(ucfirst($report->content_type)); ?></td>
                                        <td><?php echo esc_html((int)$report->content_id); ?></td>
                                        <td><?php echo esc_html($report->reason); ?></td>
                                        <td><?php echo esc_html(ucfirst($report->status)); ?></td>
                                        <td><?php echo esc_html($report->created_at); ?></td>
                                    </tr>
                                <?php
                endforeach; ?>
                            <?php
            else: ?>
                                <tr><td colspan="5">No moderation reports available.</td></tr>
                            <?php
            endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php
        endif; ?>
        <?php
    endif; ?>

        <?php if ($show_retention && $is_administrator): ?>
            <h2 class="myavana-section-title">Retention Intelligence</h2>
            <div class="myavana-grid">
                <div class="chart-wrap">
                    <h3>Week 1 vs Week 4 Retention</h3>
                    <canvas id="myavanaCohortRetentionChart"></canvas>
                </div>
                <div class="myavana-card">
                    <table>
                        <thead><tr><th>Cohort Week</th><th>Size</th><th>Week 1</th><th>Week 4</th></tr></thead>
                        <tbody>
                            <?php if (!empty($cohort_retention)): ?>
                                <?php foreach ($cohort_retention as $cohort): ?>
                                    <tr>
                                        <td><?php echo esc_html($cohort['label']); ?></td>
                                        <td><?php echo esc_html(number_format_i18n((int)$cohort['cohort_size'])); ?></td>
                                        <td><?php echo esc_html(number_format_i18n((float)$cohort['week_1'], 1)); ?>%</td>
                                        <td><?php echo esc_html(number_format_i18n((float)$cohort['week_4'], 1)); ?>%</td>
                                    </tr>
                                <?php
            endforeach; ?>
                            <?php
        else: ?>
                                <tr><td colspan="4">No cohort data for selected filters.</td></tr>
                            <?php
        endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php
    endif; ?>

        <?php
        if ($show_gamification && function_exists('myavana_render_gamification_dashboard_section')) {
            myavana_render_gamification_dashboard_section([
                'gamification_metrics' => $gamification_metrics,
                'challenge_rows' => $challenge_rows,
                'reward_reason_rows' => $reward_reason_rows,
                'reward_user_rows' => $reward_user_rows,
                'reward_field_labels' => $reward_field_labels,
                'reward_settings' => $reward_settings,
                'challenge_definitions' => $challenge_definitions,
                'challenge_metric_options' => $challenge_metric_options,
                'role_options' => $available_roles,
            ]);
        }
        ?>

        <h2 class="myavana-section-title">Top Pages Visited</h2>
        <div class="myavana-card">
            <table>
                <thead><tr><th>Path</th><th>Visits</th><th>Avg Time</th><th>Last Seen</th></tr></thead>
                <tbody>
                    <?php if (!empty($top_pages)): ?>
                        <?php foreach ($top_pages as $row): ?>
                            <tr>
                                <td><?php echo esc_html($row->path); ?></td>
                                <td><?php echo esc_html(number_format_i18n((int)$row->visits)); ?></td>
                                <td><?php echo esc_html(myavana_seconds_to_readable((int)round($row->avg_time))); ?></td>
                                <td><?php echo esc_html($row->last_seen); ?></td>
                            </tr>
                        <?php
        endforeach; ?>
                    <?php
    else: ?>
                        <tr><td colspan="4">No analytics data in selected range.</td></tr>
                    <?php
    endif; ?>
                </tbody>
            </table>
        </div>

        <h2 class="myavana-section-title">User Activity Snapshot</h2>
        <div class="myavana-card">
            <table>
                <thead>
                    <tr><th>User</th><th>Email</th><th>Hair Type</th><th>Hair Journey Entries</th><th>Visits (Range)</th><th>AI Analyses</th></tr>
                </thead>
                <tbody>
                    <?php if (!empty($active_users)): ?>
                        <?php foreach ($active_users as $user): ?>
                            <?php $history = get_user_meta((int)$user->ID, 'myavana_hair_analysis_history', true);
            $analysis_count = is_array($history) ? count($history) : 0; ?>
                            <tr>
                                <td><?php echo esc_html($user->display_name ?: 'User #' . (int)$user->ID); ?></td>
                                <td><?php echo esc_html($user->user_email); ?></td>
                                <td><?php echo esc_html($user->hair_type ?: 'Not set'); ?></td>
                                <td><?php echo esc_html(number_format_i18n((int)$user->entries_count)); ?></td>
                                <td><?php echo esc_html(number_format_i18n((int)$user->visits_in_range)); ?></td>
                                <td><?php echo esc_html(number_format_i18n($analysis_count)); ?></td>
                            </tr>
                        <?php
        endforeach; ?>
                    <?php
    else: ?>
                        <tr><td colspan="6">No users match the selected filters.</td></tr>
                    <?php
    endif; ?>
                </tbody>
            </table>
        </div>

        <script>
            (function() {
                if (typeof Chart === 'undefined') return;

                const palette = ['#193a63', '#2f6d9a', '#5096c0', '#7db9d8', '#b8d9e8', '#1f2937'];

                const roleLabels = <?php echo wp_json_encode($chart_role_labels); ?>;
                const roleValues = <?php echo wp_json_encode($chart_role_values); ?>;
                const sectionLabels = <?php echo wp_json_encode($chart_section_labels); ?>;
                const sectionValues = <?php echo wp_json_encode($chart_section_values); ?>;
                const funnelLabels = <?php echo wp_json_encode($chart_funnel_labels); ?>;
                const funnelCounts = <?php echo wp_json_encode($chart_funnel_counts); ?>;
                const cohortLabels = <?php echo wp_json_encode($chart_cohort_labels); ?>;
                const cohortWeek1 = <?php echo wp_json_encode($chart_cohort_week1); ?>;
                const cohortWeek4 = <?php echo wp_json_encode($chart_cohort_week4); ?>;
                const dailyLabels = <?php echo wp_json_encode($chart_daily_labels); ?>;
                const dailyValues = <?php echo wp_json_encode($chart_daily_values); ?>;
                const deviceLabels = <?php echo wp_json_encode($chart_device_labels); ?>;
                const deviceValues = <?php echo wp_json_encode($chart_device_values); ?>;
                const postMixLabels = <?php echo wp_json_encode($chart_post_mix_labels); ?>;
                const postMixValues = <?php echo wp_json_encode($chart_post_mix_values); ?>;
                const hairTypeLabels = <?php echo wp_json_encode($chart_hair_type_labels); ?>;
                const hairTypeValues = <?php echo wp_json_encode($chart_hair_type_values); ?>;
                const signupLabels = <?php echo wp_json_encode($chart_signup_labels); ?>;
                const signupValues = <?php echo wp_json_encode($chart_signup_values); ?>;
                const aiEntryLabels = <?php echo wp_json_encode($chart_ai_entry_labels); ?>;
                const aiEntryValues = <?php echo wp_json_encode($chart_ai_entry_values); ?>;
                const pageTimeLabels = <?php echo wp_json_encode($chart_page_time_labels); ?>;
                const pageTimeValues = <?php echo wp_json_encode($chart_page_time_values); ?>;

                function createChart(id, config) {
                    const ctx = document.getElementById(id);
                    if (!ctx) return;
                    new Chart(ctx, config);
                }

                createChart('myavanaRoleDistributionChart', {
                    type: 'doughnut',
                    data: { labels: roleLabels, datasets: [{ data: roleValues, backgroundColor: palette, borderWidth: 0 }] },
                    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
                });

                createChart('myavanaSectionUsageChart', {
                    type: 'bar',
                    data: { labels: sectionLabels, datasets: [{ label: 'Interactions', data: sectionValues, backgroundColor: '#193a63' }] },
                    options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }
                });

                createChart('myavanaDailyTrafficChart', {
                    type: 'line',
                    data: { labels: dailyLabels, datasets: [{ label: 'Visits', data: dailyValues, borderColor: '#2f6d9a', backgroundColor: 'rgba(47,109,154,.15)', fill: true, tension: .25 }] },
                    options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }
                });

                createChart('myavanaDeviceMixChart', {
                    type: 'pie',
                    data: { labels: deviceLabels, datasets: [{ data: deviceValues, backgroundColor: ['#193a63','#2f6d9a','#7db9d8','#b8d9e8'] }] },
                    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
                });

                createChart('myavanaFunnelChart', {
                    type: 'bar',
                    data: { labels: funnelLabels, datasets: [{ label: 'Users', data: funnelCounts, backgroundColor: '#2f6d9a' }] },
                    options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }
                });

                createChart('myavanaCohortRetentionChart', {
                    type: 'line',
                    data: {
                        labels: cohortLabels,
                        datasets: [
                            { label: 'Week 1 Retention %', data: cohortWeek1, borderColor: '#193a63', backgroundColor: 'rgba(25,58,99,.12)', fill: false, tension: .25 },
                            { label: 'Week 4 Retention %', data: cohortWeek4, borderColor: '#5096c0', backgroundColor: 'rgba(80,150,192,.12)', fill: false, tension: .25 }
                        ]
                    },
                    options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, max: 100 } } }
                });

                createChart('myavanaCommunityPostMixChart', {
                    type: 'doughnut',
                    data: { labels: postMixLabels, datasets: [{ data: postMixValues, backgroundColor: ['#193a63', '#5096c0'] }] },
                    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
                });

                createChart('myavanaHairTypeChart', {
                    type: 'doughnut',
                    data: { labels: hairTypeLabels, datasets: [{ data: hairTypeValues, backgroundColor: palette, borderWidth: 0 }] },
                    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
                });

                createChart('myavanaSignupTrendChart', {
                    type: 'line',
                    data: { labels: signupLabels, datasets: [{ label: 'Signups', data: signupValues, borderColor: '#1f3b61', backgroundColor: 'rgba(31,59,97,.12)', fill: true, tension: .25 }] },
                    options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }
                });

                createChart('myavanaAiEntryTrendChart', {
                    type: 'line',
                    data: { labels: aiEntryLabels, datasets: [{ label: 'AI Entries', data: aiEntryValues, borderColor: '#5096c0', backgroundColor: 'rgba(80,150,192,.14)', fill: true, tension: .25 }] },
                    options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }
                });

                createChart('myavanaPageTimeChart', {
                    type: 'bar',
                    data: { labels: pageTimeLabels, datasets: [{ label: 'Avg Time (s)', data: pageTimeValues, backgroundColor: '#2f6d9a' }] },
                    options: { responsive: true, maintainAspectRatio: false, indexAxis: 'y', scales: { x: { beginAtZero: true } } }
                });
            })();
        </script>
    </div>
    <?php
}
?>
