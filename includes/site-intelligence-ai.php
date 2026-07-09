<?php
/**
 * Unified site intelligence and AI component system.
 *
 * Tracks behavioral events, stores AI recommendation components, and powers
 * lightweight personalized suggestion feeds across the app.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Myavana_Site_Intelligence
{
    const DB_VERSION = '1.0.0';
    const GEMINI_MODEL = 'gemini-3.1-flash-lite-preview';
    const COMPONENT_TTL = 43200; // 12 hours
    const COMPONENT_COOLDOWN = 14400; // 4 hours

    public static function init()
    {
        add_action('init', [__CLASS__, 'maybe_create_tables']);
        add_action('wp_ajax_myavana_track_site_event_batch', [__CLASS__, 'ajax_track_site_event_batch']);
        add_action('wp_ajax_nopriv_myavana_track_site_event_batch', [__CLASS__, 'ajax_track_site_event_batch']);
        add_action('wp_ajax_myavana_get_ai_intelligence_feed', [__CLASS__, 'ajax_get_ai_intelligence_feed']);
        add_action('wp_ajax_myavana_update_ai_component_state', [__CLASS__, 'ajax_update_ai_component_state']);
    }

    public static function maybe_create_tables($force = false)
    {
        global $wpdb;

        $installed = get_option('myavana_site_intelligence_db_version');
        if (!$force && $installed === self::DB_VERSION) {
            return;
        }

        $charset_collate = $wpdb->get_charset_collate();
        $events_table = self::events_table();
        $components_table = self::components_table();

        $events_sql = "CREATE TABLE {$events_table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            session_id VARCHAR(64) NOT NULL,
            user_id BIGINT(20) UNSIGNED DEFAULT NULL,
            page_context VARCHAR(40) DEFAULT NULL,
            path VARCHAR(255) DEFAULT NULL,
            section_key VARCHAR(120) DEFAULT NULL,
            event_type VARCHAR(40) NOT NULL,
            event_name VARCHAR(120) NOT NULL,
            event_value VARCHAR(191) DEFAULT NULL,
            dwell_seconds INT(10) UNSIGNED DEFAULT 0,
            event_count SMALLINT(5) UNSIGNED DEFAULT 1,
            meta_json LONGTEXT DEFAULT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY idx_session_id (session_id),
            KEY idx_user_id (user_id),
            KEY idx_page_context (page_context),
            KEY idx_event_type (event_type),
            KEY idx_event_name (event_name),
            KEY idx_created_at (created_at)
        ) {$charset_collate};";

        $components_sql = "CREATE TABLE {$components_table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            page_context VARCHAR(40) NOT NULL,
            component_key VARCHAR(120) DEFAULT NULL,
            component_type VARCHAR(40) NOT NULL,
            title VARCHAR(191) NOT NULL,
            body TEXT NOT NULL,
            cta_label VARCHAR(120) DEFAULT NULL,
            cta_action VARCHAR(255) DEFAULT NULL,
            priority SMALLINT(5) UNSIGNED DEFAULT 50,
            status VARCHAR(20) DEFAULT 'active',
            source_json LONGTEXT DEFAULT NULL,
            model_name VARCHAR(100) DEFAULT NULL,
            generated_at DATETIME NOT NULL,
            interacted_at DATETIME DEFAULT NULL,
            expires_at DATETIME DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY idx_user_page_status (user_id, page_context, status),
            KEY idx_component_type (component_type),
            KEY idx_generated_at (generated_at),
            KEY idx_expires_at (expires_at)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($events_sql);
        dbDelta($components_sql);

        update_option('myavana_site_intelligence_db_version', self::DB_VERSION);
    }

    public static function ajax_track_site_event_batch()
    {
        if (!self::verify_nonce($_POST['nonce'] ?? '')) {
            wp_send_json_error(['message' => 'Invalid request'], 403);
        }

        $raw_events = wp_unslash($_POST['events'] ?? '[]');
        $events = json_decode($raw_events, true);
        if (!is_array($events)) {
            wp_send_json_error(['message' => 'Invalid events payload'], 400);
        }

        $events = array_slice($events, 0, 100);
        $processed = 0;
        foreach ($events as $event) {
            if (self::insert_event($event)) {
                $processed++;
            }
        }

        wp_send_json_success([
            'processed' => $processed,
            'received' => count($events),
        ]);
    }

    public static function ajax_get_ai_intelligence_feed()
    {
        if (!is_user_logged_in()) {
            wp_send_json_success([
                'components' => [],
                'page_context' => self::normalize_page_context($_POST['page_context'] ?? ''),
            ]);
        }

        if (!self::verify_nonce($_POST['nonce'] ?? '')) {
            wp_send_json_error(['message' => 'Invalid request'], 403);
        }

        $page_context = self::normalize_page_context($_POST['page_context'] ?? '');
        $force_refresh = !empty($_POST['force_refresh']);
        $components = self::get_ai_components_for_user(get_current_user_id(), $page_context, $force_refresh);

        wp_send_json_success([
            'components' => $components,
            'page_context' => $page_context,
        ]);
    }

    public static function ajax_update_ai_component_state()
    {
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'Authentication required'], 401);
        }

        if (!self::verify_nonce($_POST['nonce'] ?? '')) {
            wp_send_json_error(['message' => 'Invalid request'], 403);
        }

        global $wpdb;
        $component_id = absint($_POST['component_id'] ?? 0);
        $new_state = sanitize_key($_POST['state'] ?? '');
        if ($component_id <= 0 || !in_array($new_state, ['saved', 'dismissed', 'applied'], true)) {
            wp_send_json_error(['message' => 'Invalid component action'], 400);
        }

        $table = self::components_table();
        $component = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d AND user_id = %d LIMIT 1",
            $component_id,
            get_current_user_id()
        ), ARRAY_A);

        if (!$component) {
            wp_send_json_error(['message' => 'Component not found'], 404);
        }

        $updated = $wpdb->update(
            $table,
            [
                'status' => $new_state,
                'interacted_at' => current_time('mysql'),
            ],
            ['id' => $component_id],
            ['%s', '%s'],
            ['%d']
        );

        if ($updated === false) {
            wp_send_json_error(['message' => 'Unable to update component'], 500);
        }

        self::track_server_event('ai_component_' . $new_state, [
            'event_type' => 'ai_component',
            'page_context' => $component['page_context'] ?? '',
            'path' => '',
            'event_value' => $component['component_type'] ?? '',
            'section_key' => 'ai_feed',
            'meta' => [
                'component_id' => $component_id,
                'title' => $component['title'] ?? '',
                'component_type' => $component['component_type'] ?? '',
            ],
        ]);

        if (function_exists('myavana_award_points') && in_array($new_state, ['saved', 'applied'], true)) {
            $reward_seed = class_exists('Myavana_Gamification')
                ? Myavana_Gamification::get_reward_value('ai_analysis_saved', 25)
                : 25;

            $points = $new_state === 'saved'
                ? max(5, min(10, (int) floor($reward_seed / 3)))
                : max(10, min(18, (int) floor($reward_seed / 2)));

            myavana_award_points(
                get_current_user_id(),
                $points,
                $new_state === 'saved' ? 'Saved AI suggestion' : 'Applied AI suggestion',
                'ai_component',
                $component_id,
                'ai_component_' . $new_state . ':' . $component_id,
                [
                    'page_context' => $component['page_context'] ?? '',
                    'component_type' => $component['component_type'] ?? '',
                ]
            );
        }

        wp_send_json_success([
            'component_id' => $component_id,
            'state' => $new_state,
        ]);
    }

    public static function track_server_event($event_name, $properties = [])
    {
        $payload = [
            'session_id' => sanitize_text_field((string) ($properties['session_id'] ?? 'server_' . wp_generate_uuid4())),
            'user_id' => isset($properties['user_id']) ? absint($properties['user_id']) : get_current_user_id(),
            'page_context' => self::normalize_page_context($properties['page_context'] ?? ''),
            'path' => sanitize_text_field((string) ($properties['path'] ?? self::current_request_path())),
            'section_key' => sanitize_key((string) ($properties['section_key'] ?? 'system')),
            'event_type' => sanitize_key((string) ($properties['event_type'] ?? 'server')),
            'event_name' => sanitize_key((string) $event_name),
            'event_value' => sanitize_text_field((string) ($properties['event_value'] ?? '')),
            'dwell_seconds' => absint($properties['dwell_seconds'] ?? 0),
            'event_count' => max(1, absint($properties['event_count'] ?? 1)),
            'meta' => $properties['meta'] ?? [],
            'created_at' => current_time('mysql'),
        ];

        return self::insert_event($payload);
    }

    public static function get_ai_components_for_user($user_id, $page_context, $force_refresh = false)
    {
        global $wpdb;

        $user_id = (int) $user_id;
        if ($user_id <= 0) {
            return [];
        }

        $page_context = self::normalize_page_context($page_context);
        $table = self::components_table();
        $now_mysql = current_time('mysql');

        if ($force_refresh) {
            $wpdb->query($wpdb->prepare(
                "UPDATE {$table}
                 SET status = 'expired', interacted_at = %s
                 WHERE user_id = %d
                   AND page_context = %s
                   AND status = 'active'",
                $now_mysql,
                $user_id,
                $page_context
            ));
        }

        $wpdb->query($wpdb->prepare(
            "UPDATE {$table}
             SET status = 'expired'
             WHERE user_id = %d
               AND page_context = %s
               AND status = 'active'
               AND expires_at IS NOT NULL
               AND expires_at < %s",
            $user_id,
            $page_context,
            $now_mysql
        ));

        if (!$force_refresh) {
            $active_rows = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$table}
                 WHERE user_id = %d
                   AND page_context = %s
                   AND status = 'active'
                   AND (expires_at IS NULL OR expires_at >= %s)
                 ORDER BY priority ASC, generated_at DESC
                 LIMIT 3",
                $user_id,
                $page_context,
                $now_mysql
            ), ARRAY_A);

            if (!empty($active_rows)) {
                return array_map([__CLASS__, 'format_component_row'], $active_rows);
            }

            $recent_cooldown = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$table}
                 WHERE user_id = %d
                   AND page_context = %s
                   AND status IN ('saved', 'dismissed', 'applied')
                   AND interacted_at IS NOT NULL
                   AND interacted_at >= DATE_SUB(%s, INTERVAL %d SECOND)",
                $user_id,
                $page_context,
                $now_mysql,
                self::COMPONENT_COOLDOWN
            ));

            if ($recent_cooldown > 0) {
                return [];
            }
        }

        $context = self::build_user_context($user_id, $page_context);
        $fallback_components = self::build_fallback_components($context, $page_context);
        if (empty($fallback_components)) {
            return [];
        }

        $generated_components = self::generate_components_with_ai($context, $page_context, $fallback_components);
        $expires_at = wp_date('Y-m-d H:i:s', time() + self::COMPONENT_TTL);

        foreach ($generated_components as $index => $component) {
            $component_type = sanitize_key($component['component_type'] ?? 'suggestion');
            $title = sanitize_text_field($component['title'] ?? '');
            $body = sanitize_textarea_field($component['body'] ?? '');
            if ($title === '' || $body === '') {
                continue;
            }

            $source_json = [
                'context' => $page_context,
                'generation_strategy' => sanitize_text_field($component['generation_strategy'] ?? 'fallback'),
                'source' => $component['source'] ?? [],
            ];

            $wpdb->insert(
                $table,
                [
                    'user_id' => $user_id,
                    'page_context' => $page_context,
                    'component_key' => sanitize_title($page_context . '-' . $component_type . '-' . $title . '-' . ($index + 1)),
                    'component_type' => $component_type,
                    'title' => $title,
                    'body' => $body,
                    'cta_label' => sanitize_text_field($component['cta_label'] ?? ''),
                    'cta_action' => esc_url_raw($component['cta_action'] ?? '') ?: sanitize_text_field($component['cta_action'] ?? ''),
                    'priority' => max(1, min(99, (int) ($component['priority'] ?? (20 + ($index * 10))))),
                    'status' => 'active',
                    'source_json' => wp_json_encode($source_json),
                    'model_name' => sanitize_text_field($component['model_name'] ?? 'rules'),
                    'generated_at' => $now_mysql,
                    'expires_at' => $expires_at,
                ],
                ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s']
            );
        }

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table}
             WHERE user_id = %d
               AND page_context = %s
               AND status = 'active'
               AND generated_at = %s
             ORDER BY priority ASC, id DESC
             LIMIT 3",
            $user_id,
            $page_context,
            $now_mysql
        ), ARRAY_A);

        return array_map([__CLASS__, 'format_component_row'], $rows);
    }

    private static function build_user_context($user_id, $page_context)
    {
        global $wpdb;

        $profiles_table = $wpdb->prefix . 'myavana_profiles';
        $profile = $wpdb->get_row($wpdb->prepare(
            "SELECT hair_type, hair_health_rating, hair_journey_stage, location
             FROM {$profiles_table}
             WHERE user_id = %d
             LIMIT 1",
            $user_id
        ), ARRAY_A);

        $goals_raw = get_user_meta($user_id, 'myavana_hair_goals_structured', true);
        $goals_raw = is_array($goals_raw) ? $goals_raw : [];
        $active_goals = [];
        foreach ($goals_raw as $goal) {
            $title = trim((string) ($goal['title'] ?? ($goal['goal_title'] ?? '')));
            if ($title === '') {
                continue;
            }

            $progress = max(0, min(100, (int) ($goal['progress'] ?? ($goal['progress_percent'] ?? 0))));
            $status = strtolower(trim((string) ($goal['status'] ?? 'active')));
            if ($status === '' || ($progress < 100 && $status !== 'paused')) {
                $status = 'active';
            }
            if ($progress >= 100) {
                $status = 'completed';
            }
            if ($status === 'completed') {
                continue;
            }

            $active_goals[] = [
                'title' => $title,
                'category' => sanitize_text_field((string) ($goal['goal_category'] ?? 'Goal')),
                'progress' => $progress,
                'target_date' => sanitize_text_field((string) ($goal['target_date'] ?? ($goal['end_date'] ?? ''))),
            ];
        }

        $routines_raw = get_user_meta($user_id, 'myavana_current_routine', true);
        $routines_raw = is_array($routines_raw) ? $routines_raw : [];
        $routines = [];
        foreach ($routines_raw as $routine) {
            $title = trim((string) ($routine['title'] ?? ($routine['routine_title'] ?? ($routine['name'] ?? ''))));
            if ($title === '') {
                continue;
            }

            $status = strtolower(trim((string) ($routine['status'] ?? 'active')));
            if ($status === 'paused') {
                continue;
            }

            $routines[] = [
                'title' => $title,
                'type' => sanitize_text_field((string) ($routine['routine_type'] ?? 'Routine')),
                'frequency' => sanitize_text_field((string) ($routine['frequency'] ?? ($routine['routine_frequency'] ?? 'Weekly'))),
                'time' => sanitize_text_field((string) ($routine['routine_time'] ?? ($routine['time'] ?? ''))),
            ];
        }

        $recent_entries = $wpdb->get_results($wpdb->prepare(
            "SELECT ID, post_title, post_date
             FROM {$wpdb->posts}
             WHERE post_author = %d
               AND post_type = 'hair_journey_entry'
               AND post_status = 'publish'
             ORDER BY post_date DESC
             LIMIT 5",
            $user_id
        ), ARRAY_A);

        $entries_last_30 = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*)
             FROM {$wpdb->posts}
             WHERE post_author = %d
               AND post_type = 'hair_journey_entry'
               AND post_status = 'publish'
               AND post_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
            $user_id
        ));

        $analysis_history = get_user_meta($user_id, 'myavana_hair_analysis_history', true);
        $analysis_history = is_array($analysis_history) ? $analysis_history : [];
        $analysis_count = count($analysis_history);
        $latest_analysis = !empty($analysis_history) ? end($analysis_history) : null;
        if ($latest_analysis !== false) {
            reset($analysis_history);
        }

        $routine_completions = get_user_meta($user_id, 'myavana_routine_completions', true);
        $routine_completions = is_array($routine_completions) ? $routine_completions : [];
        $today_key = current_time('Y-m-d');
        $completed_today = count($routine_completions[$today_key] ?? []);

        $gamification = class_exists('Myavana_Gamification')
            ? Myavana_Gamification::get_summary($user_id)
            : [
                'current_streak' => 0,
                'level' => 1,
                'daily_quests' => [],
                'weekly_quests' => [],
                'active_challenges' => [],
            ];

        return [
            'user_id' => $user_id,
            'page_context' => $page_context,
            'profile' => [
                'hair_type' => sanitize_text_field((string) ($profile['hair_type'] ?? '')),
                'hair_health_rating' => (float) ($profile['hair_health_rating'] ?? 0),
                'hair_journey_stage' => sanitize_text_field((string) ($profile['hair_journey_stage'] ?? '')),
                'location' => sanitize_text_field((string) ($profile['location'] ?? '')),
            ],
            'active_goals' => $active_goals,
            'routines' => $routines,
            'recent_entries' => array_map(static function ($entry) {
                return [
                    'title' => sanitize_text_field((string) ($entry['post_title'] ?? '')),
                    'post_date' => sanitize_text_field((string) ($entry['post_date'] ?? '')),
                ];
            }, $recent_entries),
            'entries_last_30' => $entries_last_30,
            'analysis_count' => $analysis_count,
            'latest_analysis_summary' => sanitize_text_field((string) ($latest_analysis['summary'] ?? ($latest_analysis['analysis_summary'] ?? ''))),
            'completed_today' => $completed_today,
            'gamification' => [
                'current_streak' => (int) ($gamification['current_streak'] ?? 0),
                'level' => (int) ($gamification['level'] ?? 1),
                'daily_quests' => is_array($gamification['daily_quests'] ?? null) ? $gamification['daily_quests'] : [],
                'weekly_quests' => is_array($gamification['weekly_quests'] ?? null) ? $gamification['weekly_quests'] : [],
                'active_challenges' => is_array($gamification['active_challenges'] ?? null) ? $gamification['active_challenges'] : [],
            ],
        ];
    }

    private static function build_fallback_components($context, $page_context)
    {
        $components = [];
        $profile = $context['profile'];
        $active_goals = $context['active_goals'];
        $routines = $context['routines'];
        $streak = (int) ($context['gamification']['current_streak'] ?? 0);
        $entries_last_30 = (int) ($context['entries_last_30'] ?? 0);
        $analysis_count = (int) ($context['analysis_count'] ?? 0);
        $completed_today = (int) ($context['completed_today'] ?? 0);
        $hair_type = strtolower((string) ($profile['hair_type'] ?? ''));

        $timeline_url = self::resolve_page_url('myavana_hair-journey-page', '/hair-journey/');
        $goals_url = self::resolve_page_url('myavana_goals_page', '/goals/');
        $routines_url = self::resolve_page_url('myavana_routines_page', '/routines/');
        $community_url = self::resolve_page_url('myavana_community_feed', '/community/');
        $insights_url = home_url('/hair-insights/');

        $hydration_routine_url = add_query_arg([
            'create' => 'routine',
            'template' => 'hydration-reset',
            'type' => 'Moisture',
            'title' => 'Hydration Reset Routine',
            'frequency' => '3x weekly',
        ], $routines_url);

        $repair_goal_url = add_query_arg([
            'create' => 'goal',
            'category' => 'Repair',
            'title' => 'Improve hair strength and moisture',
        ], $goals_url);

        $length_goal_url = add_query_arg([
            'create' => 'goal',
            'category' => 'Length',
            'title' => 'Retain length over the next 90 days',
        ], $goals_url);

        if ($page_context === 'home') {
            if (empty($active_goals)) {
                $components[] = [
                    'component_type' => 'goal_nudge',
                    'title' => 'Set one focused goal for this month',
                    'body' => $entries_last_30 > 0
                        ? 'You are already logging progress. Add a goal so the app can tie your entries and routine decisions to a clear target.'
                        : 'Start with a simple measurable goal so your timeline and routine suggestions have a direction.',
                    'cta_label' => 'Add Goal',
                    'cta_action' => strpos($hair_type, '4') !== false ? $repair_goal_url : $length_goal_url,
                    'priority' => 12,
                ];
            } else {
                $focus_goal = $active_goals[0];
                $components[] = [
                    'component_type' => 'insight',
                    'title' => 'Center today around "' . $focus_goal['title'] . '"',
                    'body' => 'Your strongest next move is to capture one entry or routine completion that directly supports this goal. That gives the timeline cleaner signals and sharper insights.',
                    'cta_label' => 'Open Timeline',
                    'cta_action' => 'navigate:' . $timeline_url,
                    'priority' => 12,
                ];
            }

            if (empty($routines)) {
                $components[] = [
                    'component_type' => 'routine_nudge',
                    'title' => 'Turn your care habits into a repeatable routine',
                    'body' => 'A lightweight routine makes it easier to stay consistent and helps future AI insights connect products, entries, and outcomes.',
                    'cta_label' => 'Build Routine',
                    'cta_action' => 'navigate:' . $hydration_routine_url,
                    'priority' => 18,
                ];
            } elseif ($completed_today === 0) {
                $components[] = [
                    'component_type' => 'gamification_nudge',
                    'title' => 'Complete one routine step today',
                    'body' => 'You already have active routines. Marking one complete today keeps your consistency score moving and feeds the gamification streak logic.',
                    'cta_label' => 'Open Routines',
                    'cta_action' => 'navigate:' . $routines_url,
                    'priority' => 20,
                ];
            }

            if ($analysis_count === 0) {
                $components[] = [
                    'component_type' => 'analysis_nudge',
                    'title' => 'Unlock a sharper baseline with one AI analysis',
                    'body' => 'Your personalized suggestions improve once the system has an analysis snapshot to compare against your entries, goals, and routines.',
                    'cta_label' => 'Run AI Analysis',
                    'cta_action' => 'action:open_ai_analysis',
                    'priority' => 24,
                ];
            } elseif ($streak < 3) {
                $components[] = [
                    'component_type' => 'gamification_nudge',
                    'title' => 'Aim for a three-day momentum streak',
                    'body' => 'A short streak is the fastest way to build enough activity for better insight quality and stronger challenge progress.',
                    'cta_label' => 'Add Entry',
                    'cta_action' => 'action:add_entry',
                    'priority' => 26,
                ];
            }
        } elseif ($page_context === 'goals') {
            $suggested_title = strpos($hair_type, '4') !== false
                ? 'Reduce dryness and breakage for 6 weeks'
                : 'Strengthen wash day results over 30 days';
            $components[] = [
                'component_type' => 'goal_nudge',
                'title' => 'AI goal idea: ' . $suggested_title,
                'body' => 'This suggestion is based on your current profile, activity level, and routine coverage. Starting with one narrow goal usually leads to clearer progress signals.',
                'cta_label' => 'Use This Goal',
                'cta_action' => 'navigate:' . add_query_arg([
                    'create' => 'goal',
                    'category' => strpos($hair_type, '4') !== false ? 'Moisture' : 'Strength',
                    'title' => $suggested_title,
                ], $goals_url),
                'priority' => 10,
            ];

            if (!empty($active_goals)) {
                $components[] = [
                    'component_type' => 'advice',
                    'title' => 'Break active goals into faster checkpoints',
                    'body' => 'If a goal feels broad, add weekly markers in your notes or entries. Smaller checkpoints make progress easier to notice and easier to reinforce.',
                    'cta_label' => 'View Timeline',
                    'cta_action' => 'navigate:' . $timeline_url,
                    'priority' => 18,
                ];
            }
        } elseif ($page_context === 'routines') {
            $components[] = [
                'component_type' => 'routine_nudge',
                'title' => 'AI routine starter for your current profile',
                'body' => strpos($hair_type, '4') !== false
                    ? 'Try a hydration-first rhythm with a moisture reset, midweek refresh, and scalp support. This helps build consistency without overloading wash day.'
                    : 'A simple cleanse, strengthen, and protect cadence can make your timeline easier to read and your progress easier to sustain.',
                'cta_label' => 'Create Suggested Routine',
                'cta_action' => 'navigate:' . $hydration_routine_url,
                'priority' => 10,
            ];

            if (!empty($active_goals)) {
                $components[] = [
                    'component_type' => 'insight',
                    'title' => 'Map one routine to your top goal',
                    'body' => 'You get better recommendation quality when at least one routine is clearly aligned to a current goal. That reduces noise in future AI guidance.',
                    'cta_label' => 'Review Goals',
                    'cta_action' => 'navigate:' . $goals_url,
                    'priority' => 18,
                ];
            }
        } elseif ($page_context === 'community') {
            $components[] = [
                'component_type' => 'gamification_nudge',
                'title' => 'Share a milestone when you have one clear signal',
                'body' => 'Community posts perform better when tied to a recent routine win, a timeline checkpoint, or a before-and-after moment rather than a general update.',
                'cta_label' => 'Open Timeline',
                'cta_action' => 'navigate:' . $timeline_url,
                'priority' => 14,
            ];
        } elseif ($page_context === 'profile') {
            $components[] = [
                'component_type' => 'advice',
                'title' => 'Keep your profile and timeline aligned',
                'body' => 'When your hair type, goals, and recent entries tell the same story, the AI suggestions become much more specific and much less noisy.',
                'cta_label' => 'Hair Insights',
                'cta_action' => 'navigate:' . $insights_url,
                'priority' => 14,
            ];
        } else {
            $components[] = [
                'component_type' => 'suggestion',
                'title' => 'Log one meaningful update this week',
                'body' => 'A recent entry gives the intelligence layer enough fresh activity to produce more relevant suggestions and keep your progress moving.',
                'cta_label' => 'Open Timeline',
                'cta_action' => 'navigate:' . $timeline_url,
                'priority' => 16,
            ];
        }

        $challenge = $context['gamification']['active_challenges'][0] ?? null;
        if (is_array($challenge) && !empty($challenge['title']) && count($components) < 3) {
            $components[] = [
                'component_type' => 'gamification_nudge',
                'title' => 'Challenge focus: ' . sanitize_text_field((string) $challenge['title']),
                'body' => sanitize_text_field((string) ($challenge['description'] ?? 'Use your next action to move one active challenge forward.')),
                'cta_label' => 'Keep Momentum',
                'cta_action' => 'navigate:' . $timeline_url,
                'priority' => 28,
            ];
        }

        return array_slice($components, 0, 3);
    }

    private static function generate_components_with_ai($context, $page_context, $fallback_components)
    {
        $api_key = trim((string) get_option('myavana_gemini_api_key', ''));
        if ($api_key === '') {
            return array_map(static function ($component) {
                $component['generation_strategy'] = 'fallback';
                $component['model_name'] = 'rules';
                return $component;
            }, $fallback_components);
        }

        $prompt = self::build_gemini_prompt($context, $page_context, $fallback_components);
        $url = sprintf(
            'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s',
            rawurlencode(self::GEMINI_MODEL),
            rawurlencode($api_key)
        );

        $response = wp_remote_post($url, [
            'headers' => ['Content-Type' => 'application/json'],
            'timeout' => 20,
            'body' => wp_json_encode([
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'temperature' => 0.35,
                    'topP' => 0.9,
                    'maxOutputTokens' => 1200,
                    'responseMimeType' => 'application/json',
                ],
            ]),
        ]);

        if (is_wp_error($response) || (int) wp_remote_retrieve_response_code($response) !== 200) {
            return array_map(static function ($component) {
                $component['generation_strategy'] = 'fallback';
                $component['model_name'] = 'rules';
                return $component;
            }, $fallback_components);
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        $text = $body['candidates'][0]['content']['parts'][0]['text'] ?? '';
        $decoded = json_decode($text, true);
        $components = [];

        if (is_array($decoded)) {
            if (isset($decoded['components']) && is_array($decoded['components'])) {
                $decoded = $decoded['components'];
            }

            foreach (array_slice($decoded, 0, 3) as $index => $component) {
                if (!is_array($component)) {
                    continue;
                }

                $title = sanitize_text_field((string) ($component['title'] ?? ''));
                $body_text = sanitize_textarea_field((string) ($component['body'] ?? ''));
                if ($title === '' || $body_text === '') {
                    continue;
                }

                $components[] = [
                    'component_type' => sanitize_key((string) ($component['component_type'] ?? ($fallback_components[$index]['component_type'] ?? 'suggestion'))),
                    'title' => $title,
                    'body' => substr($body_text, 0, 280),
                    'cta_label' => sanitize_text_field((string) ($component['cta_label'] ?? ($fallback_components[$index]['cta_label'] ?? 'Open'))),
                    'cta_action' => sanitize_text_field((string) ($component['cta_action'] ?? ($fallback_components[$index]['cta_action'] ?? ''))),
                    'priority' => max(1, min(99, (int) ($component['priority'] ?? ($fallback_components[$index]['priority'] ?? 20)))),
                    'generation_strategy' => 'gemini',
                    'model_name' => self::GEMINI_MODEL,
                    'source' => [
                        'fallback_seed' => $fallback_components[$index]['title'] ?? '',
                    ],
                ];
            }
        }

        if (empty($components)) {
            return array_map(static function ($component) {
                $component['generation_strategy'] = 'fallback';
                $component['model_name'] = 'rules';
                return $component;
            }, $fallback_components);
        }

        return $components;
    }

    private static function build_gemini_prompt($context, $page_context, $fallback_components)
    {
        $allowed_actions = [
            'navigate:' . self::resolve_page_url('myavana_hair-journey-page', '/hair-journey/'),
            'navigate:' . self::resolve_page_url('myavana_goals_page', '/goals/'),
            'navigate:' . self::resolve_page_url('myavana_routines_page', '/routines/'),
            'navigate:' . self::resolve_page_url('myavana_community_feed', '/community/'),
            'navigate:' . home_url('/hair-insights/'),
            'action:add_entry',
            'action:open_ai_analysis',
        ];

        $context_summary = [
            'page_context' => $page_context,
            'profile' => $context['profile'],
            'active_goals' => array_slice($context['active_goals'], 0, 2),
            'routines' => array_slice($context['routines'], 0, 2),
            'entries_last_30' => $context['entries_last_30'],
            'analysis_count' => $context['analysis_count'],
            'completed_today' => $context['completed_today'],
            'gamification' => [
                'current_streak' => $context['gamification']['current_streak'] ?? 0,
                'level' => $context['gamification']['level'] ?? 1,
            ],
            'fallback_components' => $fallback_components,
        ];

        return 'You are MYAVANA\'s in-app intelligence system. Return JSON only. Generate up to 3 concise, non-intrusive haircare suggestion components for the current page. Each component must include: component_type, title, body, cta_label, cta_action, priority. Keep title under 60 chars and body under 220 chars. Make them practical, encouraging, and tied to the user\'s recent activity, goals, routines, and gamification momentum. Prefer actionable suggestions over generic tips. Allowed cta_action values: ' . wp_json_encode($allowed_actions) . '. User context: ' . wp_json_encode($context_summary);
    }

    private static function insert_event($event)
    {
        global $wpdb;

        $table = self::events_table();
        $session_id = sanitize_text_field((string) ($event['session_id'] ?? ''));
        $event_type = sanitize_key((string) ($event['event_type'] ?? ''));
        $event_name = sanitize_key((string) ($event['event_name'] ?? ''));
        if ($session_id === '' || $event_type === '' || $event_name === '') {
            return false;
        }

        $meta = $event['meta'] ?? ($event['meta_json'] ?? []);
        if (is_string($meta)) {
            $decoded = json_decode($meta, true);
            $meta = is_array($decoded) ? $decoded : ['raw' => sanitize_text_field($meta)];
        }
        if (!is_array($meta)) {
            $meta = [];
        }

        return (bool) $wpdb->insert(
            $table,
            [
                'session_id' => $session_id,
                'user_id' => !empty($event['user_id']) ? absint($event['user_id']) : null,
                'page_context' => self::normalize_page_context($event['page_context'] ?? ''),
                'path' => sanitize_text_field((string) ($event['path'] ?? '')),
                'section_key' => sanitize_key((string) ($event['section_key'] ?? '')),
                'event_type' => $event_type,
                'event_name' => $event_name,
                'event_value' => sanitize_text_field((string) ($event['event_value'] ?? '')),
                'dwell_seconds' => absint($event['dwell_seconds'] ?? 0),
                'event_count' => max(1, absint($event['event_count'] ?? 1)),
                'meta_json' => wp_json_encode(self::sanitize_meta_payload($meta)),
                'created_at' => current_time('mysql'),
            ],
            ['%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s']
        );
    }

    private static function sanitize_meta_payload($meta)
    {
        $sanitized = [];
        foreach ($meta as $key => $value) {
            $safe_key = sanitize_key((string) $key);
            if ($safe_key === '') {
                continue;
            }

            if (is_scalar($value) || $value === null) {
                $sanitized[$safe_key] = sanitize_text_field((string) $value);
            } elseif (is_array($value)) {
                $sanitized[$safe_key] = array_map(static function ($item) {
                    return is_scalar($item) ? sanitize_text_field((string) $item) : '';
                }, array_slice($value, 0, 10));
            }
        }

        return $sanitized;
    }

    private static function format_component_row($row)
    {
        return [
            'id' => (int) ($row['id'] ?? 0),
            'page_context' => sanitize_key((string) ($row['page_context'] ?? '')),
            'component_type' => sanitize_key((string) ($row['component_type'] ?? 'suggestion')),
            'title' => sanitize_text_field((string) ($row['title'] ?? '')),
            'body' => sanitize_textarea_field((string) ($row['body'] ?? '')),
            'cta_label' => sanitize_text_field((string) ($row['cta_label'] ?? '')),
            'cta_action' => sanitize_text_field((string) ($row['cta_action'] ?? '')),
            'priority' => (int) ($row['priority'] ?? 50),
            'status' => sanitize_key((string) ($row['status'] ?? 'active')),
            'model_name' => sanitize_text_field((string) ($row['model_name'] ?? 'rules')),
        ];
    }

    private static function verify_nonce($nonce)
    {
        $nonce = (string) $nonce;
        return wp_verify_nonce($nonce, 'myavana_site_intelligence') || wp_verify_nonce($nonce, 'myavana_nonce');
    }

    private static function normalize_page_context($page_context)
    {
        $page_context = strtolower(trim((string) $page_context));
        if ($page_context === '') {
            $page_context = self::current_request_path();
        }

        if (strpos($page_context, 'goals') !== false) {
            return 'goals';
        }
        if (strpos($page_context, 'routines') !== false) {
            return 'routines';
        }
        if (strpos($page_context, 'community') !== false) {
            return 'community';
        }
        if (strpos($page_context, 'profile') !== false) {
            return 'profile';
        }
        if (strpos($page_context, 'hair-journey') !== false || $page_context === 'journey') {
            return 'journey';
        }
        if ($page_context === '/' || $page_context === 'home' || $page_context === '') {
            return 'home';
        }

        return sanitize_key($page_context);
    }

    private static function current_request_path()
    {
        $request_uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '/';
        $path = parse_url($request_uri, PHP_URL_PATH);
        return is_string($path) && $path !== '' ? $path : '/';
    }

    private static function resolve_page_url($shortcode, $fallback_path)
    {
        global $wpdb;

        $shortcode_candidates = [$shortcode];
        if (strpos($shortcode, '-') !== false) {
            $shortcode_candidates[] = str_replace('-', '_', $shortcode);
        }

        foreach ($shortcode_candidates as $candidate) {
            $page_id = $wpdb->get_var($wpdb->prepare(
                "SELECT ID
                 FROM {$wpdb->posts}
                 WHERE post_type = 'page'
                   AND post_status = 'publish'
                   AND post_content LIKE %s
                 ORDER BY ID ASC
                 LIMIT 1",
                '%[' . $wpdb->esc_like($candidate) . '%'
            ));

            if ($page_id) {
                $permalink = get_permalink((int) $page_id);
                if ($permalink) {
                    return $permalink;
                }
            }
        }

        return home_url($fallback_path);
    }

    private static function events_table()
    {
        global $wpdb;
        return $wpdb->prefix . 'myavana_site_events';
    }

    private static function components_table()
    {
        global $wpdb;
        return $wpdb->prefix . 'myavana_ai_components';
    }
}

Myavana_Site_Intelligence::init();

if (!function_exists('myavana_track_event')) {
    function myavana_track_event($event_name, $properties = [])
    {
        return Myavana_Site_Intelligence::track_server_event($event_name, $properties);
    }
}
