<?php
/**
 * Advanced Dashboard Shortcode
 * Simple navigation dashboard for MYAVANA shortcodes
 */

// Ensure constants are available
if (!defined('MYAVANA_DIR')) {
    define('MYAVANA_DIR', plugin_dir_path(__FILE__) . '../');
}
if (!defined('MYAVANA_URL')) {
    define('MYAVANA_URL', plugin_dir_url(__FILE__) . '../');
}

function myavana_advanced_dashboard_shortcode($atts = []) {
    // Parse attributes
    $atts = shortcode_atts([
        'default_tab' => 'dashboard'
    ], $atts, 'myavana_advanced_dashboard');

    // Check authentication
    if (!is_user_logged_in()) {
        return myavana_render_auth_required('dashboard');
    }

    $user_id = get_current_user_id();
    $user_data = get_userdata($user_id);

    // Get dashboard data for stats
    $dashboard_data = myavana_get_dashboard_data($user_id);

    // Enqueue core dashboard assets with unified core dependency
    wp_enqueue_style('myavana-advanced-dashboard', MYAVANA_URL . 'assets/css/advanced-dashboard.css', [], '1.0.1');
    wp_enqueue_script('myavana-advanced-dashboard', MYAVANA_URL . 'assets/js/advanced-dashboard.js', ['jquery', 'chart-js', 'myavana-unified-core'], '1.0.1', true);

    // Enqueue dashboard integration (extracted inline code with view switching fix)
    wp_enqueue_script('myavana-dashboard-integration', MYAVANA_URL . 'assets/js/dashboard-integration.js', ['jquery', 'myavana-unified-core', 'myavana-advanced-dashboard'], '1.0.1', true);
    
    // Enqueue third-party libraries
    wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js', [], '4.4.0', true);
    wp_enqueue_script('fullcalendar', 'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js', [], '6.1.8', true);
    wp_enqueue_script('splide-js', 'https://cdn.jsdelivr.net/npm/@splidejs/splide@4.1.4/dist/js/splide.min.js', [], '4.1.4', true);
    wp_enqueue_style('splide-css', 'https://cdn.jsdelivr.net/npm/@splidejs/splide@4.1.4/dist/css/splide.min.css', [], '4.1.4');
    
    // Enqueue component-specific assets for modal loading
    wp_enqueue_style('myavana-timeline', MYAVANA_URL . 'assets/css/hair-diary-timeline.css', [], '2.0.0');
    wp_enqueue_style('myavana-analytics', MYAVANA_URL . 'assets/css/analytics.css', [], '1.0.0');
    wp_enqueue_style('myavana-gemini-chatbot', MYAVANA_URL . 'assets/css/gemini-chatbot.css', [], '1.0.0');
    wp_enqueue_style('myavana-entry-form', MYAVANA_URL . 'assets/css/entry-form.css', [], '1.0.0');
    
    // Enqueue component JavaScript files
    wp_enqueue_script('myavana-analytics', MYAVANA_URL . 'assets/js/analytics.js', ['jquery', 'chart-js'], '1.0.0', true);
    wp_enqueue_script('myavana-gemini-chatbot', MYAVANA_URL . 'assets/js/gemini-chatbot.js', ['jquery'], '1.0.0', true);
    wp_enqueue_script('myavana-entry-form', MYAVANA_URL . 'assets/js/entry-form.js', ['jquery'], '1.0.0', true);
    
    // Localize scripts for component loading
    wp_localize_script('myavana-analytics', 'myavanaAnalytics', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('myavana_analytics'),
        'user_id' => $user_id
    ]);
    
    wp_localize_script('myavana-gemini-chatbot', 'myavanaGeminiChatbot', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('myavana_chatbot_nonce'),
        'user_id' => $user_id,
        'vision_action' => 'myavana_gemini_vision_api',
        'live_session_action' => 'myavana_gemini_live_session'
    ]);
    
    wp_localize_script('myavana-entry-form', 'myavanaEntryForm', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('myavana_entry'),
        'user_id' => $user_id
    ]);
    
    // Localize script
    wp_localize_script('myavana-advanced-dashboard', 'myavana_ajax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('myavana_nonce'),
        'user_id' => $user_id,
        'theme' => $atts['theme'],
        'strings' => [
            'loading' => __('Loading...', 'myavana'),
            'error' => __('Error loading data', 'myavana'),
            'save_success' => __('Saved successfully!', 'myavana'),
            'goal_complete' => __('Congratulations! Goal completed!', 'myavana')
        ]
    ]);
    
    ob_start();
    ?>
    <div class="myavana-advanced-dashboard" data-theme="<?php echo esc_attr($atts['theme']); ?>" data-layout="<?php echo esc_attr($atts['layout']); ?>">
        
        <!-- Dashboard Header -->
        <header class="dashboard-header">
            <div class="header-content">
                <div class="welcome-section">
                    <?php if ($atts['show_welcome'] === 'true'): ?>
                    <div class="welcome-message">
                        <h1 class="welcome-title">
                            <?php echo myavana_get_greeting_message(); ?>, <?php echo esc_html($user_data->display_name); ?>! ✨
                        </h1>
                        <p class="welcome-subtitle"><?php echo myavana_get_motivational_message($dashboard_data); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <div class="dashboard-controls">
                        <div class="view-controls">
                            <button type="button" class="view-btn active" data-view="overview" title="Dashboard Overview">
                                <svg viewBox="0 0 24 24"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
                                <span class="view-label">Dashboard</span>
                            </button>
                            <button type="button" class="view-btn" data-view="timeline" title="Hair Journey Timeline">
                                <svg viewBox="0 0 24 24"><path d="M23,12L20.56,9.22L20.9,5.54L17.29,4.72L15.4,1.54L12,3L8.6,1.54L6.71,4.72L3.1,5.53L3.44,9.21L1,12L3.44,14.78L3.1,18.47L6.71,19.29L8.6,22.47L12,21L15.4,22.46L17.29,19.28L20.9,18.46L20.56,14.78L23,12Z"/></svg>
                                <span class="view-label">Timeline</span>
                            </button>
                            <button type="button" class="view-btn" data-view="profile" title="My Profile">
                                <svg viewBox="0 0 24 24"><path d="M12,4A4,4 0 0,1 16,8A4,4 0 0,1 12,12A4,4 0 0,1 8,8A4,4 0 0,1 12,4M12,14C16.42,14 20,15.79 20,18V20H4V18C4,15.79 7.58,14 12,14Z"/></svg>
                                <span class="view-label">Profile</span>
                            </button>
                            <button type="button" class="view-btn" data-view="analytics" title="Analytics">
                                <svg viewBox="0 0 24 24"><path d="M3.5 18.49l6-6.01 4 4L22 6.92l-1.41-1.41-7.09 7.97-4-4L2 16.99z"/></svg>
                                <span class="view-label">Analytics</span>
                            </button>
                        </div>
                        
                        <div class="theme-controls">
                            <button type="button" class="theme-toggle" id="themeToggle" title="Toggle dark mode">
                                <svg class="sun-icon" viewBox="0 0 24 24">
                                    <path d="M12 2.25a.75.75 0 01.75.75v2.25a.75.75 0 01-1.5 0V3a.75.75 0 01.75-.75zM7.5 12a4.5 4.5 0 119 0 4.5 4.5 0 01-9 0zM18.894 6.166a.75.75 0 00-1.06-1.06l-1.591 1.59a.75.75 0 101.06 1.061l1.591-1.59zM21.75 12a.75.75 0 01-.75.75h-2.25a.75.75 0 010-1.5H21a.75.75 0 01.75.75zM17.834 18.894a.75.75 0 001.06-1.06l-1.59-1.591a.75.75 0 10-1.061 1.06l1.59 1.591zM12 18a.75.75 0 01.75.75V21a.75.75 0 01-1.5 0v-2.25A.75.75 0 0112 18zM7.758 17.303a.75.75 0 00-1.061-1.06l-1.591 1.59a.75.75 0 001.06 1.061l1.591-1.59zM6 12a.75.75 0 01-.75.75H3a.75.75 0 010-1.5h2.25A.75.75 0 016 12zM6.697 7.757a.75.75 0 001.06-1.06l-1.59-1.591a.75.75 0 00-1.061 1.06l1.59 1.591z"/>
                                </svg>
                                <svg class="moon-icon" viewBox="0 0 24 24" style="display: none;">
                                    <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="streak-section">
                    <div class="streak-card">
                        <div class="streak-flame">🔥</div>
                        <div class="streak-content">
                            <div class="streak-number"><?php echo $dashboard_data['current_streak']; ?></div>
                            <div class="streak-label">Day Streak</div>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        
        <!-- Quick Actions -->
        <?php if ($atts['show_quick_actions'] === 'true'): ?>
        <section class="quick-actions-section p-4">
            <div class="quick-actions-grid">
                <button type="button" class="myavana-dashboard-quick-action-btn primary" id="myavanaAddEntryBtn">
                    <div class="myavana-dashboard-action-icon">
                        <svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                    </div>
                    <div class="myavana-dashboard-action-content">
                        <div class="myavana-dashboard-action-title">Add Entry</div>
                        <div class="myavana-dashboard-action-subtitle">Document your progress</div>
                    </div>
                </button>
                
                <button type="button" class="myavana-dashboard-quick-action-btn" id="myavanaAIAnalysisBtn">
                    <div class="myavana-dashboard-action-icon">
                        <svg viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </div>
                    <div class="myavana-dashboard-action-content">
                        <div class="myavana-dashboard-action-title">AI Analysis</div>
                        <div class="myavana-dashboard-action-subtitle">Get instant insights</div>
                    </div>
                </button>
                
                <button type="button" class="myavana-dashboard-quick-action-btn" id="myavanaRoutineBtn">
                    <div class="myavana-dashboard-action-icon">
                        <svg viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                    </div>
                    <div class="myavana-dashboard-action-content">
                        <div class="myavana-dashboard-action-title">My Routine</div>
                        <div class="myavana-dashboard-action-subtitle">Manage hair care plan</div>
                    </div>
                </button>
                
                <button type="button" class="myavana-dashboard-quick-action-btn" id="myavanaProductsBtn">
                    <div class="myavana-dashboard-action-icon">
                        <svg viewBox="0 0 24 24"><path d="M7 4V2c0-.55.45-1 1-1h8c.55 0 1 .45 1 1v2h3c.55 0 1 .45 1 1s-.45 1-1 1H4c-.55 0-1-.45-1-1s.45-1 1-1h3zm-2 3h14l-.5 9c-.05.55-.5.95-1.05.95H6.55c-.55 0-1-.4-1.05-.95L5 7z"/></svg>
                    </div>
                    <div class="myavana-dashboard-action-content">
                        <div class="myavana-dashboard-action-title">Products</div>
                        <div class="myavana-dashboard-action-subtitle">Track inventory</div>
                    </div>
                </button>
            </div>
        </section>
        <?php endif; ?>
        
        <!-- Dashboard Views -->
        <main class="dashboard-main p-4">
            
            <!-- Overview View -->
            <div class="dashboard-view active" id="overviewView">
                
                <!-- Stats Grid -->
                <section class="stats-section">
                    <div class="stats-grid">
                        <div class="stat-card health">
                            <div class="stat-header">
                                <div class="stat-icon">
                                    <svg viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                                </div>
                                <div class="stat-trend <?php echo $dashboard_data['health_trend'] >= 0 ? 'positive' : 'negative'; ?>">
                                    <svg viewBox="0 0 24 24"><path d="M7 14l5-5 5 5z"/></svg>
                                    <?php echo abs($dashboard_data['health_trend']); ?>%
                                </div>
                            </div>
                            <div class="stat-content">
                                <div class="stat-number"><?php echo number_format($dashboard_data['avg_health'], 1); ?></div>
                                <div class="stat-label">Average Health</div>
                                <div class="stat-description">Your overall hair health score</div>
                            </div>
                        </div>
                        
                        <div class="stat-card entries">
                            <div class="stat-header">
                                <div class="stat-icon">
                                    <svg viewBox="0 0 24 24"><path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z"/></svg>
                                </div>
                                <div class="stat-badge">
                                    +<?php echo $dashboard_data['entries_this_month']; ?> this month
                                </div>
                            </div>
                            <div class="stat-content">
                                <div class="stat-number"><?php echo $dashboard_data['total_entries']; ?></div>
                                <div class="stat-label">Total Entries</div>
                                <div class="stat-description">Documented hair journey moments</div>
                            </div>
                        </div>
                        
                        <div class="stat-card products">
                            <div class="stat-header">
                                <div class="stat-icon">
                                    <svg viewBox="0 0 24 24"><path d="M7 4V2c0-.55.45-1 1-1h8c.55 0 1 .45 1 1v2h3c.55 0 1 .45 1 1s-.45 1-1 1H4c-.55 0-1-.45-1-1s.45-1 1-1h3zm-2 3h14l-.5 9c-.05.55-.5.95-1.05.95H6.55c-.55 0-1-.4-1.05-.95L5 7z"/></svg>
                                </div>
                                <div class="stat-badge low-stock">
                                    <?php echo $dashboard_data['low_stock_count']; ?> low
                                </div>
                            </div>
                            <div class="stat-content">
                                <div class="stat-number"><?php echo $dashboard_data['products_count']; ?></div>
                                <div class="stat-label">Products Tracked</div>
                                <div class="stat-description">In your hair care inventory</div>
                            </div>
                        </div>
                        
                        <div class="stat-card achievements">
                            <div class="stat-header">
                                <div class="stat-icon">
                                    <svg viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                </div>
                                <div class="stat-badge new">
                                    <?php echo $dashboard_data['new_achievements']; ?> new
                                </div>
                            </div>
                            <div class="stat-content">
                                <div class="stat-number"><?php echo $dashboard_data['total_achievements']; ?></div>
                                <div class="stat-label">Achievements</div>
                                <div class="stat-description">Milestones unlocked</div>
                            </div>
                        </div>
                    </div>
                </section>
                
                <!-- Current Goals -->
                <section class="goals-section">
                    <div class="section-header">
                        <h2 class="section-title">
                            <svg viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                            Current Goals
                        </h2>
                        <!-- <button type="button" class="section-edit btn btn-outline add-goal-btn" data-section="hair-goals">Add Goal</button> -->
                    </div>
                    
                    <div class="goals-grid" id="currentGoals">
                        <?php foreach ($dashboard_data['active_goals'] as $goal): ?>
                        <div class="goal-card" data-goal-id="<?php echo $goal['id']; ?>">
                            <div class="goal-header">
                                <h3 class="goal-title"><?php echo esc_html($goal['title']); ?></h3>
                                <div class="goal-actions">
                                    <button type="button" class="goal-action-btn edit-goal" title="Edit goal">
                                        <svg viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                                    </button>
                                </div>
                            </div>
                            <div class="goal-progress">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo $goal['progress']; ?>%"></div>
                                </div>
                                <div class="progress-text"><?php echo $goal['progress']; ?>% complete</div>
                            </div>
                            <div class="goal-meta">
                                <div class="goal-category"><?php echo esc_html($goal['category']); ?></div>
                                <div class="goal-deadline"><?php echo date_i18n('M j', strtotime($goal['target_date'])); ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>
                
                <!-- Recent Activity -->
                <!-- <section class="activity-section">
                    <div class="section-header">
                        <h2 class="section-title">
                            <svg viewBox="0 0 24 24"><path d="M13,3A9,9 0 0,0 4,12H1L4.96,16.03L9,12H6A7,7 0 0,1 13,5A7,7 0 0,1 20,12A7,7 0 0,1 13,19C11.07,19 9.32,18.21 8.06,16.94L6.64,18.36C8.27,20 10.5,21 13,21A9,9 0 0,0 22,12A9,9 0 0,0 13,3Z"/></svg>
                            Recent Activity
                        </h2>
                        <a href="#" class="view-all-link">View All</a>
                    </div>
                    
                    <div class="activity-timeline">
                        <?php foreach ($dashboard_data['recent_activity'] as $activity): ?>
                        <div class="activity-item <?php echo esc_attr($activity['type']); ?>">
                            <div class="activity-icon">
                                <?php echo myavana_get_activity_icon($activity['type']); ?>
                            </div>
                            <div class="activity-content">
                                <div class="activity-text"><?php echo esc_html($activity['text']); ?></div>
                                <time class="activity-time" datetime="<?php echo esc_attr($activity['date']); ?>">
                                    <?php echo myavana_time_ago($activity['date']); ?>
                                </time>
                            </div>
                            <?php if (!empty($activity['image'])): ?>
                            <div class="activity-image">
                                <img src="<?php echo esc_url($activity['image']); ?>" alt="" loading="lazy">
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section> -->
                
                <!-- Achievements Showcase -->
                <!-- <section class="achievements-section">
                    <div class="section-header">
                        <h2 class="section-title">
                            <svg viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                            Latest Achievements
                        </h2>
                    </div>
                    
                    <div class="achievements-grid">
                        <?php foreach ($dashboard_data['recent_achievements'] as $achievement): ?>
                        <div class="achievement-card <?php echo $achievement['unlocked'] ? 'unlocked' : 'locked'; ?>">
                            <div class="achievement-icon">
                                <?php echo $achievement['icon']; ?>
                            </div>
                            <div class="achievement-content">
                                <h3 class="achievement-title"><?php echo esc_html($achievement['title']); ?></h3>
                                <p class="achievement-description"><?php echo esc_html($achievement['description']); ?></p>
                                <?php if ($achievement['unlocked']): ?>
                                <time class="achievement-date">
                                    Unlocked <?php echo myavana_time_ago($achievement['unlocked_date']); ?>
                                </time>
                                <?php else: ?>
                                <div class="achievement-progress">
                                    <?php echo $achievement['progress']; ?>% complete
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section> -->
            </div>
            
            <!-- Analytics View -->
            <div class="dashboard-view" id="analyticsView">
                <section class="analytics-section">
                    <div class="section-header">
                        <h2 class="section-title">Hair Health Analytics</h2>
                        <div class="analytics-controls">
                            <select id="analyticsTimeframe" class="timeframe-select">
                                <option value="30">Last 30 days</option>
                                <option value="90" selected>Last 3 months</option>
                                <option value="180">Last 6 months</option>
                                <option value="365">Last year</option>
                            </select>
                        </div>
                    </div>
                    
                    
                    <!-- Current Routine Section -->
                    <div class="profile-section">
                        <div class="section-header">
                            <h2 class="section-title">Current Routine</h2>
                            <?php if ($is_owner) : ?>
                                <div class="section-edit" data-section="routine">
                                    <i class="fas fa-plus"></i>
                                    <span>Add Step</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div id="routine-steps">
                            <?php if (empty($current_routine)): ?>
                                <p class="empty-state">No routine set yet.</p>
                            <?php else: ?>
                                <div class="routine-container">
                                    <?php foreach ($current_routine as $index => $step): ?>
                                        <div class="routine-step" data-index="<?php echo $index; ?>">
                                            <div class="step-number"><?php echo esc_html($index + 1); ?></div>
                                        
                                            <div class="step-content">
                                                <h3 class="step-title" style="margin-bottom: 16px;"><?php echo esc_html($step['name']); ?></h3>
                                                
                                                <div class="step-meta-container">
                                                    <div class="meta-item frequency-meta">
                                                        <div class="meta-icon">
                                                            <?php
                                                            switch ($step['frequency']) {
                                                                case 'Daily':
                                                                    echo '<svg class="frequency-svg" viewBox="0 0 24 24"><text x="12" y="16" text-anchor="middle" font-size="12" fill="black">D</text></svg>';
                                                                    break;
                                                                case 'Weekly':
                                                                    echo '<svg class="frequency-svg" viewBox="0 0 24 24"><text x="12" y="16" text-anchor="middle" font-size="12" fill="black">W</text></svg>';
                                                                    break;
                                                                case 'Bi-Weekly':
                                                                    echo '<svg class="frequency-svg" viewBox="0 0 24 24"><text x="12" y="16" text-anchor="middle" font-size="12" fill="black">B</text></svg>';
                                                                    break;
                                                                case 'Monthly':
                                                                    echo '<svg class="frequency-svg" viewBox="0 0 24 24"><text x="12" y="16" text-anchor="middle" font-size="12" fill="black">M</text></svg>';
                                                                    break;
                                                                case 'As Needed':
                                                                    echo '<svg class="frequency-svg" viewBox="0 0 24 24"><text x="12" y="16" text-anchor="middle" font-size="12" fill="black">A</text></svg>';
                                                                    break;
                                                                default:
                                                                    echo '<svg class="frequency-svg" viewBox="0 0 24 24"><text x="12" y="16" text-anchor="middle" font-size="12" fill="gray">?</text></svg>';
                                                            }
                                                            ?>
                                                        </div>
                                                        <div class="meta-text">
                                                            <span class="meta-label">Frequency</span>
                                                            <span class="meta-value"><?php echo esc_html($step['frequency']); ?></span>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="meta-item time-meta">
                                                        <div class="meta-icon">
                                                            <?php
                                                            switch ($step['time_of_day']) {
                                                                case 'Morning':
                                                                    echo '<svg class="time-svg morning" viewBox="0 0 24 24"><circle cx="12" cy="12" r="4" /><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M17.66 6.34l1.41-1.41" /></svg>';
                                                                    break;
                                                                case 'Evening':
                                                                    echo '<svg class="time-svg evening" viewBox="0 0 24 24"><path d="M12 18a6 6 0 100-12 6 6 0 000 12zM22 12h-2M4 12H2m2.93-7.07l1.41 1.41M17.66 17.66l1.41 1.41" /><path d="M12 20v2" /></svg>';
                                                                    break;
                                                                case 'Night':
                                                                    echo '<svg class="time-svg night" viewBox="0 0 24 24"><path d="M15 3a9 9 0 00-9 9c0 5 4 9 9 9 1.5 0 2.9-.4 4.1-1.1-.2-.7-.3-1.4-.3-2.1 0-3.3 2.7-6 6-6 0-.7-.1-1.4-.3-2.1C21.7 5.9 18.9 3 15 3z" /><circle cx="18" cy="6" r="1" /><circle cx="15" cy="9" r="1" /><circle cx="18" cy="12" r="1" /></svg>';
                                                                    break;
                                                                default:
                                                                    echo '<svg class="time-svg any-time" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" /><path d="M12 6v6l4 2" /></svg>';
                                                            }
                                                            ?>
                                                        </div>
                                                        <div class="meta-text">
                                                            <span class="meta-label">Time of Day</span>
                                                            <span class="meta-value"><?php echo esc_html($step['time_of_day']); ?></span>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <?php if (!empty($step['description'])): ?>
                                                <p class="step-txt"><?php echo esc_html($step['description']); ?></p>
                                                <?php endif; ?>
                                                
                                                <?php if (!empty($step['products'])): ?>
                                                    <div class="step-products">
                                                        <?php foreach ($step['products'] as $product) : ?>
                                                            <span class="product-badge"><?php echo esc_html($product); ?></span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <div class="step-links">
                                                    <?php if ($is_owner): ?>
                                                        <div class="step-actions">
                                                            <button class="editBtn section-edit" data-index="<?php echo $index; ?>">
                                                            <svg height="1em" viewBox="0 0 512 512">
                                                                <path
                                                                d="M410.3 231l11.3-11.3-33.9-33.9-62.1-62.1L291.7 89.8l-11.3 11.3-22.6 22.6L58.6 322.9c-10.4 10.4-18 23.3-22.2 37.4L1 480.7c-2.5 8.4-.2 17.5 6.1 23.7s15.3 8.5 23.7 6.1l120.3-35.4c14.1-4.2 27-11.8 37.4-22.2L387.7 253.7 410.3 231zM160 399.4l-9.1 22.7c-4 3.1-8.5 5.4-13.3 6.9L59.4 452l23-78.1c1.4-4.9 3.8-9.4 6.9-13.3l22.7-9.1v32c0 8.8 7.2 16 16 16h32zM362.7 18.7L348.3 33.2 325.7 55.8 314.3 67.1l33.9 33.9 62.1 62.1 33.9 33.9 11.3-11.3 22.6-22.6 14.5-14.5c25-25 25-65.5 0-90.5L453.3 18.7c-25-25-65.5-25-90.5 0zm-47.4 168l-144 144c-6.2 6.2-16.4 6.2-22.6 0s-6.2-16.4 0-22.6l144-144c6.2-6.2 16.4-6.2 22.6 0s6.2 16.4 0 22.6z"
                                                                ></path>
                                                            </svg>
                                                            </button>

                                                            <button class="deleteBtn delete-step" data-index="<?php echo $index; ?>">
                                                                <svg height="1em" viewBox="0 0 448 512">
                                                                    <path d="M135.2 17.7L128 32H32C14.3 32 0 46.3 0 64S14.3 96 32 96H416c17.7 0 32-14.3 32-32s-14.3-32-32-32H320l-7.2-14.3C307.4 6.8 296.3 0 284.2 0H163.8c-12.1 0-23.2 6.8-28.6 17.7zM416 128H32L53.2 467c1.6 25.3 22.6 45 47.9 45H346.9c25.3 0 46.3-19.7 47.9-45L416 128z"></path>
                                                                </svg>
                                                            </button>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>
            </div>
            
            <!-- Calendar View -->
            <div class="dashboard-view" id="calendarView">
                <section class="calendar-section">
                    <div class="section-header">
                        <h2 class="section-title">Hair Care Calendar</h2>
                        <div class="calendar-controls">
                            <button type="button" class="btn btn-outline" id="addEventBtn">Add Event</button>
                        </div>
                    </div>
                    
                    <div class="calendar-container">
                        <div id="hairCareCalendar"></div>
                    </div>
                </section>
            </div>
            
            <!-- Timeline View -->
            <div class="dashboard-view" id="timelineView">
                <section class="timeline-integrated-section">
                    <div class="section-header">
                        <h2 class="section-title">
                            <svg viewBox="0 0 24 24"><path d="M23,12L20.56,9.22L20.9,5.54L17.29,4.72L15.4,1.54L12,3L8.6,1.54L6.71,4.72L3.1,5.53L3.44,9.21L1,12L3.44,14.78L3.1,18.47L6.71,19.29L8.6,22.47L12,21L15.4,22.46L17.29,19.28L20.9,18.46L20.56,14.78L23,12Z"/></svg>
                            Hair Journey Timeline
                        </h2>
                        <div class="timeline-controls">
                            <button type="button" class="btn btn-primary" id="addTimelineEntryBtn">
                                <svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                                Add Entry
                            </button>
                        </div>
                    </div>
                    
                    <div class="timeline-embed-container" id="timelineEmbedContainer">
                        <!-- Timeline shortcode will be loaded here -->
                        <div class="timeline-loading">
                            <div class="loading-spinner"></div>
                            <p>Loading your beautiful timeline...</p>
                        </div>
                    </div>

                    <style>
                    .timeline-embed-container {
                        background: var(--myavana-white);
                        border-radius: 12px;
                        border: 1px solid var(--myavana-sand);
                        min-height: 400px;
                        position: relative;
                        overflow: hidden;
                    }

                    .timeline-loading {
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                        justify-content: center;
                        height: 400px;
                        color: var(--myavana-blueberry);
                        font-family: 'Archivo', sans-serif;
                    }

                    .timeline-loading .loading-spinner {
                        width: 40px;
                        height: 40px;
                        border: 3px solid var(--myavana-sand);
                        border-top: 3px solid var(--myavana-coral);
                        border-radius: 50%;
                        animation: spin 1s linear infinite;
                        margin-bottom: 16px;
                    }

                    .timeline-loading p {
                        font-size: 14px;
                        margin: 0;
                        color: var(--myavana-blueberry);
                    }

                    .load-error {
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                        justify-content: center;
                        height: 400px;
                        text-align: center;
                        padding: 20px;
                    }

                    .load-error p {
                        color: var(--myavana-blueberry);
                        font-family: 'Archivo', sans-serif;
                        margin-bottom: 20px;
                        font-size: 14px;
                    }

                    @keyframes spin {
                        0% { transform: rotate(0deg); }
                        100% { transform: rotate(360deg); }
                    }

                    /* Ensure timeline content fits well in dashboard */
                    .timeline-embed-container .myavana-diary-container {
                        border: none;
                        border-radius: 0;
                        margin: 0;
                        background: transparent;
                    }

                    .timeline-embed-container .hair-diary-header {
                        padding: 20px 20px 0 20px;
                        border-bottom: 1px solid var(--myavana-sand);
                        margin-bottom: 20px;
                    }

                    .timeline-embed-container .hair-diary-title {
                        font-size: 18px;
                        margin-bottom: 8px;
                    }

                    /* Make timeline more compact in dashboard */
                    .timeline-embed-container .diary-entry-card {
                        margin-bottom: 16px;
                    }

                    .timeline-embed-container .floating-entry-card {
                        max-width: 300px;
                    }

                    /* Responsive adjustments */
                    @media (max-width: 768px) {
                        .timeline-embed-container {
                            border-radius: 8px;
                            margin: 0 -10px;
                        }

                        .timeline-embed-container .floating-entry-card {
                            max-width: 250px;
                        }
                    }
                    </style>
                </section>
            </div>
            
            <!-- Profile View -->
            <div class="dashboard-view" id="profileView">
                <section class="profile-integrated-section">
                    <!-- <div class="section-header">
                        <h2 class="section-title">
                            <svg viewBox="0 0 24 24"><path d="M12,4A4,4 0 0,1 16,8A4,4 0 0,1 12,12A4,4 0 0,1 8,8A4,4 0 0,1 12,4M12,14C16.42,14 20,15.79 20,18V20H4V18C4,15.79 7.58,14 12,14Z"/></svg>
                            My Hair Profile
                        </h2>
                        <div class="profile-controls">
                            <button type="button" class="btn btn-outline" id="editProfileBtn">
                                <svg viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                                Edit Profile
                            </button>
                        </div>
                    </div> -->
                    
                    <div class="profile-embed-container" id="profileEmbedContainer">
                        <!-- Profile shortcode will be loaded here -->
                        <div class="profile-loading">
                            <div class="loading-spinner"></div>
                            <p>Loading your hair profile...</p>
                        </div>
                    </div>
                </section>
            </div>
        </main>
    </div>

    <!-- Modals and overlays will be added here -->

    <!-- Dashboard JavaScript now loaded from external file: assets/js/dashboard-integration.js -->

    <?php
    return ob_get_clean();
}

// Helper function to get enhanced profile data
if (!function_exists('myavana_get_enhanced_profile_data')) {
    function myavana_get_enhanced_profile_data($user_id) {
        global $wpdb;

        // Get base profile data
        $table_name = $wpdb->prefix . 'myavana_profiles';
        $profile = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE user_id = %d", $user_id));

        if (!$profile) {
            // Create default profile if none exists
            $wpdb->insert($table_name, [
                'user_id' => $user_id,
                'hair_journey_stage' => 'beginning',
                'hair_health_rating' => 5,
                'life_journey_stage' => 'exploring',
                'hair_type' => 'unknown',
                'hair_goals' => ''
            ]);

            $profile = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE user_id = %d", $user_id));
        }

        // Get hair entries count
        $entries_count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->posts}
            WHERE post_author = %d
            AND post_type = 'hair_journey_entry'
            AND post_status = 'publish'
        ", $user_id));

        // Calculate average health from recent entries
        $avg_health = $wpdb->get_var($wpdb->prepare("
            SELECT AVG(CAST(meta_value AS DECIMAL(3,1)))
            FROM {$wpdb->posts} p
            JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_author = %d
            AND p.post_type = 'hair_journey_entry'
            AND p.post_status = 'publish'
            AND pm.meta_key = 'hair_health_rating'
            AND p.post_date >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
        ", $user_id)) ?: $profile->hair_health_rating;

        // Calculate health trend (last 30 days vs previous 30 days)
        $recent_health = $wpdb->get_var($wpdb->prepare("
            SELECT AVG(CAST(meta_value AS DECIMAL(3,1)))
            FROM {$wpdb->posts} p
            JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_author = %d
            AND p.post_type = 'hair_journey_entry'
            AND p.post_status = 'publish'
            AND pm.meta_key = 'hair_health_rating'
            AND p.post_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ", $user_id)) ?: $avg_health;

        $previous_health = $wpdb->get_var($wpdb->prepare("
            SELECT AVG(CAST(meta_value AS DECIMAL(3,1)))
            FROM {$wpdb->posts} p
            JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_author = %d
            AND p.post_type = 'hair_journey_entry'
            AND p.post_status = 'publish'
            AND pm.meta_key = 'hair_health_rating'
            AND p.post_date >= DATE_SUB(NOW(), INTERVAL 60 DAY)
            AND p.post_date < DATE_SUB(NOW(), INTERVAL 30 DAY)
        ", $user_id)) ?: $avg_health;

        $health_trend = $previous_health > 0 ? (($recent_health - $previous_health) / $previous_health) * 100 : 0;

        // Calculate current streak
        $current_streak = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(DISTINCT DATE(post_date)) as streak
            FROM {$wpdb->posts}
            WHERE post_author = %d
            AND post_type = 'hair_journey_entry'
            AND post_status = 'publish'
            AND post_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ", $user_id)) ?: 0;

        return [
            'user_id' => $user_id,
            'hair_journey_stage' => $profile->hair_journey_stage ?? 'beginning',
            'hair_health_rating' => $profile->hair_health_rating ?? 5,
            'life_journey_stage' => $profile->life_journey_stage ?? 'exploring',
            'hair_type' => $profile->hair_type ?? 'unknown',
            'hair_goals' => $profile->hair_goals ?? '',
            'total_entries' => $entries_count,
            'avg_health' => round($avg_health, 1),
            'health_trend' => round($health_trend, 1),
            'current_streak' => $current_streak
        ];
    }
}

// Helper function to get dashboard data
function myavana_get_dashboard_data($user_id) {
    global $wpdb;

    // Get basic profile data
    $profile = myavana_get_enhanced_profile_data($user_id);
    
    // Get entries for this month
    $entries_this_month = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*) FROM {$wpdb->posts} 
        WHERE post_author = %d 
        AND post_type = 'hair_journey_entry' 
        AND post_status = 'publish'
        AND MONTH(post_date) = MONTH(CURRENT_DATE())
        AND YEAR(post_date) = YEAR(CURRENT_DATE())
    ", $user_id));
    
    // Get active goals (mock data for now)
    $active_goals = [
        [
            'id' => 1,
            'title' => 'Increase Hair Length by 2 inches',
            'category' => 'Growth',
            'progress' => 65,
            'target_date' => '2024-06-01'
        ],
        [
            'id' => 2,
            'title' => 'Use Deep Conditioning Weekly',
            'category' => 'Care',
            'progress' => 80,
            'target_date' => '2024-12-31'
        ],
        [
            'id' => 3,
            'title' => 'Reduce Heat Styling to 2x/week',
            'category' => 'Health',
            'progress' => 45,
            'target_date' => '2024-05-01'
        ]
    ];
    
    // Get recent activity
    $recent_activity = [
        [
            'type' => 'entry',
            'text' => 'Added a new hair journey entry',
            'date' => date('Y-m-d H:i:s', strtotime('-2 hours')),
            'image' => null
        ],
        [
            'type' => 'achievement',
            'text' => 'Unlocked "Consistent Care" badge',
            'date' => date('Y-m-d H:i:s', strtotime('-1 day')),
            'image' => null
        ],
        [
            'type' => 'goal',
            'text' => 'Made progress on "Weekly Deep Conditioning" goal',
            'date' => date('Y-m-d H:i:s', strtotime('-2 days')),
            'image' => null
        ]
    ];
    
    // Get recent achievements
    $recent_achievements = [
        [
            'id' => 1,
            'title' => 'First Entry',
            'description' => 'Added your first hair journey entry',
            'icon' => '🏆',
            'unlocked' => true,
            'unlocked_date' => date('Y-m-d H:i:s', strtotime('-1 week')),
            'progress' => 100
        ],
        [
            'id' => 2,
            'title' => 'Consistency',
            'description' => 'Added entries for 7 days straight',
            'icon' => '🔥',
            'unlocked' => true,
            'unlocked_date' => date('Y-m-d H:i:s', strtotime('-1 day')),
            'progress' => 100
        ],
        [
            'id' => 3,
            'title' => 'Product Explorer',
            'description' => 'Tried 10 different hair products',
            'icon' => '🧪',
            'unlocked' => false,
            'progress' => 70
        ],
        [
            'id' => 4,
            'title' => 'Hair Scholar',
            'description' => 'Read 50 hair care tips',
            'icon' => '📚',
            'unlocked' => false,
            'progress' => 30
        ]
    ];
    
    return array_merge($profile, [
        'entries_this_month' => $entries_this_month,
        'products_count' => 12, // Mock data
        'low_stock_count' => 2, // Mock data
        'total_achievements' => 15, // Mock data
        'new_achievements' => 1, // Mock data
        'active_goals' => $active_goals,
        'recent_activity' => $recent_activity,
        'recent_achievements' => $recent_achievements
    ]);
}

// Helper function to get greeting message
function myavana_get_greeting_message() {
    $hour = date('H');
    if ($hour < 12) return 'Good morning';
    if ($hour < 18) return 'Good afternoon';
    return 'Good evening';
}

// Helper function to get motivational message
function myavana_get_motivational_message($dashboard_data) {
    $messages = [
        "You're doing amazing! Keep up the great work with your hair journey.",
        "Your consistency is paying off - your hair health is improving!",
        "Ready to conquer another day of fabulous hair care?",
        "Your dedication to healthy hair is inspiring!",
        "Every day is a new opportunity to love and care for your hair."
    ];
    
    // Personalize based on data
    if ($dashboard_data['current_streak'] > 7) {
        return "Wow! " . $dashboard_data['current_streak'] . " days strong! Your consistency is incredible.";
    }
    
    if ($dashboard_data['health_trend'] > 10) {
        return "Your hair health has improved by " . $dashboard_data['health_trend'] . "%! You're on fire! 🔥";
    }
    
    return $messages[array_rand($messages)];
}

// Helper function to get activity icons
function myavana_get_activity_icon($type) {
    $icons = [
        'entry' => '<svg viewBox="0 0 24 24"><path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z"/></svg>',
        'achievement' => '<svg viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>',
        'goal' => '<svg viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>',
        'product' => '<svg viewBox="0 0 24 24"><path d="M7 4V2c0-.55.45-1 1-1h8c.55 0 1 .45 1 1v2h3c.55 0 1 .45 1 1s-.45 1-1 1H4c-.55 0-1-.45-1-1s.45-1 1-1h3zm-2 3h14l-.5 9c-.05.55-.5.95-1.05.95H6.55c-.55 0-1-.4-1.05-.95L5 7z"/></svg>'
    ];
    
    return $icons[$type] ?? $icons['entry'];
}

// Helper function for time ago display
if (!function_exists('myavana_time_ago')) {
    function myavana_time_ago($datetime, $full = false) {
        $now = new DateTime;
        $ago = new DateTime($datetime);
        $diff = $now->diff($ago);

        $diff->w = floor($diff->d / 7);
        $diff->d -= $diff->w * 7;

        $string = array(
            'y' => 'year',
            'm' => 'month',
            'w' => 'week',
            'd' => 'day',
            'h' => 'hour',
            'i' => 'minute',
            's' => 'second',
        );
        foreach ($string as $k => &$v) {
            if ($diff->$k) {
                $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
            } else {
                unset($string[$k]);
            }
        }

        if (!$full) $string = array_slice($string, 0, 1);
        return $string ? implode(', ', $string) . ' ago' : 'just now';
    }
}

// Register the shortcode
add_shortcode('myavana_advanced_dashboard', 'myavana_advanced_dashboard_shortcode');

/**
 * AJAX handlers moved to: /actions/dashboard-ajax-handlers.php
 */
