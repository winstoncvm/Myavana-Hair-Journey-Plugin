<?php

function myavana_analytics_shortcode($atts = []) {
    $atts = shortcode_atts(['user_id' => get_current_user_id()], $atts);
    $user_id = intval($atts['user_id']);
    $is_owner = $user_id === get_current_user_id();

    if (!is_user_logged_in()) {
        return '<p>Please <a href="' . home_url('/login') . '">log in</a> to view your analytics.</p>';
    }

    if (!$is_owner) {
        return '<p style="color: var(--myavana-onyx);">You can only view analytics for your own profile.</p>';
    }

    wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js', [], '4.4.0', true);
    wp_enqueue_style('myavana-analytics-css', plugin_dir_url(__FILE__) . '../assets/css/analytics.css', [], '1.0.0');
    wp_enqueue_script('myavana-analytics-js', plugin_dir_url(__FILE__) . '../assets/js/analytics.js', ['jquery', 'chart-js'], '1.0.0', true);

    wp_localize_script('myavana-analytics-js', 'myavanaAnalytics', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('myavana_analytics'),
        'user_id' => $user_id
    ]);

    // Get analytics data
    $analytics_data = myavana_get_analytics_data_new($user_id);

    ob_start();
    ?>
    <div class="myavana-analytics-container">
        <div class="myavana-analytics-header">
            <div class="myavana-analytics-title">
                <svg class="myavana-analytics-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--myavana-coral)" stroke-width="2">
                    <path d="M3 3v18h18"/>
                    <path d="M18.7 8l-5.1 5.2-2.8-2.7L7 14.3"/>
                </svg>
                <h2>Hair Journey Analytics</h2>
            </div>
            <div class="myavana-analytics-period">
                <select id="myavanaAnalyticsPeriod" class="myavana-period-select">
                    <option value="7">Last 7 days</option>
                    <option value="30" selected>Last 30 days</option>
                    <option value="90">Last 90 days</option>
                    <option value="365">Last year</option>
                </select>
            </div>
        </div>

        <div class="myavana-analytics-stats-grid">
            <div class="myavana-stat-card myavana-stat-primary">
                <div class="myavana-stat-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2.69l5.66 5.66a8 8 0 11-11.31 0z"/>
                    </svg>
                </div>
                <div class="myavana-stat-content">
                    <div class="myavana-stat-number" id="totalEntries"><?php echo esc_html($analytics_data['total_entries']); ?></div>
                    <div class="myavana-stat-label">Total Entries</div>
                </div>
            </div>

            <div class="myavana-stat-card myavana-stat-success">
                <div class="myavana-stat-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 11-5.93-9.14"/>
                        <polyline points="22,4 12,14.01 9,11.01"/>
                    </svg>
                </div>
                <div class="myavana-stat-content">
                    <div class="myavana-stat-number" id="currentStreak"><?php echo esc_html($analytics_data['current_streak']); ?></div>
                    <div class="myavana-stat-label">Day Streak</div>
                </div>
            </div>

            <div class="myavana-stat-card myavana-stat-info">
                <div class="myavana-stat-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M8 12h8"/>
                        <path d="M12 8v8"/>
                    </svg>
                </div>
                <div class="myavana-stat-content">
                    <div class="myavana-stat-number" id="avgHealthScore"><?php echo esc_html(number_format($analytics_data['avg_health_score'], 1)); ?></div>
                    <div class="myavana-stat-label">Avg Health Score</div>
                </div>
            </div>

            <div class="myavana-stat-card myavana-stat-warning">
                <div class="myavana-stat-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>
                    </svg>
                </div>
                <div class="myavana-stat-content">
                    <div class="myavana-stat-number" id="totalPhotos"><?php echo esc_html($analytics_data['total_photos']); ?></div>
                    <div class="myavana-stat-label">Progress Photos</div>
                </div>
            </div>
        </div>

        <div class="myavana-analytics-charts-grid">
            <div class="myavana-chart-card">
                <div class="myavana-chart-header">
                    <h3>Health Score Trends</h3>
                    <div class="myavana-chart-legend">
                        <span class="myavana-legend-item">
                            <span class="myavana-legend-color" style="background: var(--myavana-coral);"></span>
                            Health Score
                        </span>
                    </div>
                </div>
                <div class="myavana-chart-container">
                    <canvas id="healthTrendChart" width="400" height="200"></canvas>
                </div>
            </div>

            <div class="myavana-chart-card">
                <div class="myavana-chart-header">
                    <h3>Entry Activity</h3>
                    <div class="myavana-chart-legend">
                        <span class="myavana-legend-item">
                            <span class="myavana-legend-color" style="background: var(--myavana-sage);"></span>
                            Entries
                        </span>
                    </div>
                </div>
                <div class="myavana-chart-container">
                    <canvas id="activityChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>

        <div class="myavana-analytics-insights">
            <div class="myavana-insight-card">
                <h3>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--myavana-coral)" stroke-width="2" style="margin-right: 8px;">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3"/>
                        <circle cx="12" cy="17" r="1"/>
                    </svg>
                    Hair Journey Insights
                </h3>
                <div class="myavana-insights-grid">
                    <div class="myavana-insight-item">
                        <div class="myavana-insight-label">Most Active Day</div>
                        <div class="myavana-insight-value" id="mostActiveDay"><?php echo esc_html($analytics_data['most_active_day']); ?></div>
                    </div>
                    <div class="myavana-insight-item">
                        <div class="myavana-insight-label">Favorite Mood</div>
                        <div class="myavana-insight-value" id="favoriteMood"><?php echo esc_html($analytics_data['favorite_mood']); ?></div>
                    </div>
                    <div class="myavana-insight-item">
                        <div class="myavana-insight-label">Best Health Month</div>
                        <div class="myavana-insight-value" id="bestHealthMonth"><?php echo esc_html($analytics_data['best_health_month']); ?></div>
                    </div>
                    <div class="myavana-insight-item">
                        <div class="myavana-insight-label">Progress Score</div>
                        <div class="myavana-insight-value myavana-progress-score" id="progressScore">
                            <div class="myavana-score-number"><?php echo esc_html($analytics_data['progress_score']); ?></div>
                            <div class="myavana-score-label">/100</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="myavana-analytics-actions">
            <button class="myavana-analytics-btn myavana-btn-primary" id="exportAnalytics">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                    <polyline points="7 10 12 15 17 10"/>
                    <line x1="12" y1="15" x2="12" y2="3"/>
                </svg>
                Export Report
            </button>
            <button class="myavana-analytics-btn myavana-btn-outline" id="shareProgress">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="18" cy="5" r="3"/>
                    <circle cx="6" cy="12" r="3"/>
                    <circle cx="18" cy="19" r="3"/>
                    <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/>
                    <line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/>
                </svg>
                Share Progress
            </button>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

function myavana_get_analytics_data_new($user_id) {
    global $wpdb;
    
    $entries = get_posts([
        'post_type' => 'hair_journey_entry',
        'author' => $user_id,
        'posts_per_page' => -1,
        'meta_query' => []
    ]);

    $total_entries = count($entries);
    $total_photos = 0;
    $health_scores = [];
    $mood_counts = [];
    $day_counts = [];
    
    foreach ($entries as $entry) {
        $photos = get_post_meta($entry->ID, '_myavana_photos', true);
        if (!empty($photos)) {
            $total_photos += count($photos);
        }
        
        $health_score = get_post_meta($entry->ID, '_myavana_health_rating', true);
        if ($health_score) {
            $health_scores[] = intval($health_score);
        }
        
        $mood = get_post_meta($entry->ID, '_myavana_mood', true);
        if ($mood) {
            $mood_counts[$mood] = ($mood_counts[$mood] ?? 0) + 1;
        }
        
        $day = date('l', strtotime($entry->post_date));
        $day_counts[$day] = ($day_counts[$day] ?? 0) + 1;
    }

    $current_streak = myavana_calculate_streak($user_id);
    $avg_health_score = !empty($health_scores) ? array_sum($health_scores) / count($health_scores) : 0;
    $favorite_mood = !empty($mood_counts) ? array_keys($mood_counts, max($mood_counts))[0] : 'Happy';
    $most_active_day = !empty($day_counts) ? array_keys($day_counts, max($day_counts))[0] : 'Monday';
    
    $progress_score = min(100, ($current_streak * 2) + ($avg_health_score * 2) + min(50, $total_entries * 2));

    return [
        'total_entries' => $total_entries,
        'current_streak' => $current_streak,
        'avg_health_score' => $avg_health_score,
        'total_photos' => $total_photos,
        'most_active_day' => $most_active_day,
        'favorite_mood' => $favorite_mood,
        'best_health_month' => date('F'),
        'progress_score' => round($progress_score)
    ];
}

/**
 * Generate dynamic AI insights based on user's hair journey data
 */
function myavana_generate_ai_insights_new($user_id) {
    global $wpdb;

    // Get analytics data
    $analytics = myavana_get_analytics_data_new($user_id);

    // Get entries from last 30 days
    $thirty_days_ago = date('Y-m-d H:i:s', strtotime('-30 days'));
    $recent_entries = get_posts([
        'post_type' => 'hair_journey_entry',
        'author' => $user_id,
        'posts_per_page' => -1,
        'date_query' => [
            'after' => $thirty_days_ago
        ]
    ]);

    // Calculate health score trend
    $health_scores = [];
    $old_scores = [];
    $new_scores = [];

    foreach ($recent_entries as $entry) {
        $score = get_post_meta($entry->ID, '_myavana_health_rating', true);
        if ($score) {
            $health_scores[] = intval($score);
            $entry_date = strtotime($entry->post_date);

            // Split into first 15 days vs last 15 days
            if ($entry_date < strtotime('-15 days')) {
                $old_scores[] = intval($score);
            } else {
                $new_scores[] = intval($score);
            }
        }
    }

    // Calculate improvement percentage
    $improvement = 0;
    if (!empty($old_scores) && !empty($new_scores)) {
        $old_avg = array_sum($old_scores) / count($old_scores);
        $new_avg = array_sum($new_scores) / count($new_scores);
        $improvement = round((($new_avg - $old_avg) / $old_avg) * 100);
    }

    // Generate insights based on data
    $insights = [];

    // Health improvement insight
    if ($improvement > 5) {
        $insights[] = "🎉 Your hair health has improved {$improvement}% this month! Keep up with your current routine for best results.";
    } elseif ($improvement < -5) {
        $insights[] = "📊 Your hair health has decreased {$improvement}% this month. Consider reviewing your routine or trying new products.";
    } else {
        $insights[] = "✨ Your hair health is stable. Consistency is key to maintaining healthy hair!";
    }

    // Streak insight
    if ($analytics['current_streak'] >= 7) {
        $insights[] = "🔥 Amazing! You're on a {$analytics['current_streak']}-day streak. Your dedication is paying off!";
    } elseif ($analytics['current_streak'] >= 3) {
        $insights[] = "💪 You're on a {$analytics['current_streak']}-day streak. Keep the momentum going!";
    }

    // Entry frequency insight
    $entries_this_month = count($recent_entries);
    if ($entries_this_month >= 15) {
        $insights[] = "⭐ You've logged {$entries_this_month} entries this month - that's excellent documentation!";
    } elseif ($entries_this_month < 4) {
        $insights[] = "📝 Try logging more entries to better track your progress. Aim for 2-3 entries per week.";
    }

    // Health score insight
    if ($analytics['avg_health_score'] >= 8) {
        $insights[] = "💎 Your average health score is {$analytics['avg_health_score']}/10 - your hair is thriving!";
    } elseif ($analytics['avg_health_score'] >= 6) {
        $insights[] = "🌟 Your average health score is {$analytics['avg_health_score']}/10. You're making good progress!";
    } elseif ($analytics['avg_health_score'] > 0) {
        $insights[] = "💡 Your average health score is {$analytics['avg_health_score']}/10. Focus on protein treatments and deep conditioning.";
    }

    // Return random insight or first if only one
    return !empty($insights) ? $insights[array_rand($insights)] : "Start your hair journey by logging your first entry!";
}

function myavana_calculate_streak($user_id) {
    $entries = get_posts([
        'post_type' => 'hair_journey_entry',
        'author' => $user_id,
        'posts_per_page' => 30,
        'orderby' => 'date',
        'order' => 'DESC'
    ]);

    if (empty($entries)) return 0;

    $streak = 0;
    $current_date = new DateTime();
    $last_entry_date = new DateTime($entries[0]->post_date);
    
    if ($current_date->diff($last_entry_date)->days > 1) {
        return 0;
    }

    $dates = [];
    foreach ($entries as $entry) {
        $dates[] = date('Y-m-d', strtotime($entry->post_date));
    }
    $dates = array_unique($dates);
    sort($dates);
    $dates = array_reverse($dates);

    $expected_date = new DateTime();
    foreach ($dates as $date) {
        $entry_date = new DateTime($date);
        if ($entry_date->format('Y-m-d') === $expected_date->format('Y-m-d')) {
            $streak++;
            $expected_date->sub(new DateInterval('P1D'));
        } else {
            break;
        }
    }

    return $streak;
}

// AJAX handlers
add_action('wp_ajax_myavana_get_analytics_data_new', 'myavana_handle_analytics_data');
function myavana_handle_analytics_data() {
    check_ajax_referer('myavana_analytics', 'nonce');
    
    $user_id = intval($_POST['user_id']);
    $period = intval($_POST['period']);
    
    if (!$user_id || $user_id !== get_current_user_id()) {
        wp_send_json_error(['message' => 'Invalid user']);
        return;
    }

    $data = myavana_get_chart_data($user_id, $period);
    wp_send_json_success($data);
}

function myavana_get_chart_data($user_id, $days = 30) {
    $start_date = date('Y-m-d', strtotime("-{$days} days"));
    
    $entries = get_posts([
        'post_type' => 'hair_journey_entry',
        'author' => $user_id,
        'date_query' => [
            'after' => $start_date,
            'inclusive' => true
        ],
        'posts_per_page' => -1
    ]);

    $health_data = [];
    $activity_data = [];
    $labels = [];

    for ($i = $days - 1; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-{$i} days"));
        $label = date('M d', strtotime($date));
        $labels[] = $label;
        
        $day_entries = array_filter($entries, function($entry) use ($date) {
            return date('Y-m-d', strtotime($entry->post_date)) === $date;
        });
        
        $activity_data[] = count($day_entries);
        
        $health_scores = [];
        foreach ($day_entries as $entry) {
            $score = get_post_meta($entry->ID, '_myavana_health_rating', true);
            if ($score) {
                $health_scores[] = intval($score);
            }
        }
        
        $health_data[] = !empty($health_scores) ? array_sum($health_scores) / count($health_scores) : 0;
    }

    return [
        'labels' => $labels,
        'health_data' => $health_data,
        'activity_data' => $activity_data
    ];
}

add_shortcode('myavana_analytics', 'myavana_analytics_shortcode');
?>