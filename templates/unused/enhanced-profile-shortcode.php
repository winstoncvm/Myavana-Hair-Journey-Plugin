<?php
/**
 * Enhanced Profile Shortcode
 * Modern, comprehensive user profile with analytics and AI insights
 */

function myavana_enhanced_profile_shortcode($atts = []) {
    // Parse attributes
    $atts = shortcode_atts([
        'user_id' => get_current_user_id(),
        'show_analytics' => 'true',
        'show_edit' => 'true',
        'theme' => 'default' // default, minimal, detailed
    ], $atts, 'myavana_enhanced_profile');
    
    $user_id = intval($atts['user_id']);
    $current_user_id = get_current_user_id();
    $is_owner = $user_id === $current_user_id;
    
    // Authentication check
    if (!is_user_logged_in()) {
        return myavana_render_auth_required('profile');
    }
    
    // Validate user
    $user_data = get_userdata($user_id);
    if (!$user_data) {
        return '<div class="profile-error">User profile not found.</div>';
    }
    
    // Get profile data
    $profile_data = myavana_get_enhanced_profile_data($user_id);
    
    // Enqueue assets with higher priority
    wp_enqueue_style('myavana-enhanced-profile', MYAVANA_URL . 'assets/css/enhanced-profile.css', [], '1.0.1');
    wp_add_inline_style('myavana-enhanced-profile', '
        .myavana-enhanced-profile { 
            visibility: visible !important; 
            display: block !important; 
        }
    ');
    wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.umd.js', [], '3.9.1', true);
    wp_enqueue_script('myavana-enhanced-profile', MYAVANA_URL . 'assets/js/enhanced-profile.js', ['jquery', 'chart-js'], '1.0.1', true);
    
    // Localize script
    wp_localize_script('myavana-enhanced-profile', 'myavanaProfile', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('myavana_profile'),
        'user_id' => $user_id,
        'is_owner' => $is_owner,
        'strings' => [
            'save_success' => __('Profile updated successfully!', 'myavana'),
            'save_error' => __('Error updating profile. Please try again.', 'myavana'),
            'confirm_delete' => __('Are you sure you want to delete this analysis?', 'myavana')
        ]
    ]);
    
    ob_start();
    ?>
    <div class="myavana-enhanced-profile" data-theme="<?php echo esc_attr($atts['theme']); ?>">
        
        <!-- Profile Header -->
        <header class="profile-header">
            <div class="profile-cover">
                <div class="cover-gradient"></div>
                <?php if ($is_owner && $atts['show_edit'] === 'true'): ?>
                <button type="button" class="edit-cover-btn" title="Change cover">
                    <svg viewBox="0 0 24 24">
                        <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/>
                    </svg>
                </button>
                <?php endif; ?>
            </div>
            
            <div class="profile-info">
                <div class="avatar-section">
                    <div class="profile-avatar">
                        <?php echo get_avatar($user_id, 120, '', '', ['class' => 'avatar-image']); ?>
                        <?php if ($is_owner && $atts['show_edit'] === 'true'): ?>
                        <button type="button" class="edit-avatar-btn" title="Change avatar">
                            <svg viewBox="0 0 24 24">
                                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                            </svg>
                        </button>
                        <?php endif; ?>
                    </div>
                    
                    <div class="profile-status">
                        <div class="status-indicator <?php echo myavana_get_user_status($user_id); ?>"></div>
                        <span class="status-text"><?php echo myavana_get_user_status_text($user_id); ?></span>
                    </div>
                </div>
                
                <div class="profile-details">
                    <h1 class="profile-name">
                        <?php echo esc_html($user_data->display_name); ?>
                        <?php if (myavana_is_verified_user($user_id)): ?>
                        <span class="verified-badge" title="Verified User">
                            <svg viewBox="0 0 24 24">
                                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                            </svg>
                        </span>
                        <?php endif; ?>
                    </h1>
                    
                    <div class="profile-meta">
                        <div class="meta-item">
                            <span class="meta-label">Member since:</span>
                            <time class="meta-value" datetime="<?php echo esc_attr($user_data->user_registered); ?>">
                                <?php echo date_i18n('F Y', strtotime($user_data->user_registered)); ?>
                            </time>
                        </div>
                        
                        <?php if (!empty($profile_data['location'])): ?>
                        <div class="meta-item">
                            <span class="meta-label">Location:</span>
                            <span class="meta-value"><?php echo esc_html($profile_data['location']); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="meta-item">
                            <span class="meta-label">Journey Stage:</span>
                            <span class="meta-value journey-stage <?php echo sanitize_html_class(strtolower($profile_data['hair_journey_stage'])); ?>">
                                <?php echo esc_html($profile_data['hair_journey_stage']); ?>
                            </span>
                        </div>
                    </div>
                    
                    <?php if (!empty($profile_data['about_me'])): ?>
                    <div class="profile-bio">
                        <p><?php echo nl2br(esc_html($profile_data['about_me'])); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <div class="profile-actions">
                        <?php if ($is_owner && $atts['show_edit'] === 'true'): ?>
                        <button type="button" class="btn btn-primary edit-profile-btn">
                            <svg viewBox="0 0 24 24">
                                <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/>
                            </svg>
                            Edit Profile
                        </button>
                        <?php endif; ?>
                        
                        <button type="button" class="btn btn-secondary share-profile-btn">
                            <svg viewBox="0 0 24 24">
                                <path d="M18 16.08c-.76 0-1.44.3-1.96.77L8.91 12.7c.05-.23.09-.46.09-.7s-.04-.47-.09-.7l7.05-4.11c.54.5 1.25.81 2.04.81 1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3c0 .24.04.47.09.7L8.04 9.81C7.5 9.31 6.79 9 6 9c-1.66 0-3 1.34-3 3s1.34 3 3 3c.79 0 1.50-.31 2.04-.81l7.12 4.16c-.05.21-.08.43-.08.65 0 1.61 1.31 2.92 2.92 2.92s2.92-1.31 2.92-2.92-1.31-2.92-2.92-2.92z"/>
                            </svg>
                            Share Profile
                        </button>
                    </div>
                </div>
            </div>
        </header>
        
        <!-- Profile Content -->
        <div class="profile-content">
            
            <!-- Quick Stats -->
            <section class="profile-stats">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon health">
                            <svg viewBox="0 0 24 24">
                                <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                            </svg>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number"><?php echo number_format($profile_data['avg_health_rating'], 1); ?></div>
                            <div class="stat-label">Avg Health</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon entries">
                            <svg viewBox="0 0 24 24">
                                <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/>
                            </svg>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number"><?php echo $profile_data['total_entries']; ?></div>
                            <div class="stat-label">Total Entries</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon streak">
                            <svg viewBox="0 0 24 24">
                                <path d="M13.5.67s.74 2.65.74 4.8c0 2.06-1.35 3.73-3.41 3.73-2.07 0-3.63-1.67-3.63-3.73l.03-.36C5.21 7.51 4 10.62 4 14c0 4.42 3.58 8 8 8s8-3.58 8-8C20 8.61 17.41 3.8 13.5.67zM11.71 19c-1.78 0-3.22-1.4-3.22-3.14 0-1.62 1.05-2.76 2.81-3.12 1.77-.36 3.6-1.21 4.62-2.58.39 1.29.59 2.65.59 4.04 0 2.65-2.15 4.8-4.8 4.8z"/>
                            </svg>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number"><?php echo $profile_data['current_streak']; ?></div>
                            <div class="stat-label">Day Streak</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon improvement">
                            <svg viewBox="0 0 24 24">
                                <path d="M16 6l2.29 2.29-4.88 4.88-4-4L2 16.59 3.41 18l6-6 4 4 6.3-6.29L22 12V6z"/>
                            </svg>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number <?php echo $profile_data['health_trend'] >= 0 ? 'positive' : 'negative'; ?>">
                                <?php echo ($profile_data['health_trend'] >= 0 ? '+' : '') . number_format($profile_data['health_trend'], 1); ?>%
                            </div>
                            <div class="stat-label">Health Trend</div>
                        </div>
                    </div>
                </div>
            </section>
            
            <!-- Hair Profile -->
            <section class="hair-profile-section">
                <div class="section-header">
                    <h2 class="section-title">
                        <svg viewBox="0 0 24 24">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                        </svg>
                        Hair Profile
                    </h2>
                    <?php if ($is_owner && $atts['show_edit'] === 'true'): ?>
                    <button type="button" class="btn btn-outline edit-hair-profile-btn">Edit</button>
                    <?php endif; ?>
                </div>
                
                <div class="hair-profile-content">
                    <div class="hair-attributes">
                        <?php if (!empty($profile_data['hair_type'])): ?>
                        <div class="attribute-card">
                            <div class="attribute-icon">
                                <svg viewBox="0 0 24 24">
                                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                </svg>
                            </div>
                            <div class="attribute-content">
                                <div class="attribute-label">Hair Type</div>
                                <div class="attribute-value"><?php echo esc_html($profile_data['hair_type']); ?></div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($profile_data['hair_goals'])): ?>
                        <div class="attribute-card goals">
                            <div class="attribute-icon">
                                <svg viewBox="0 0 24 24">
                                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                </svg>
                            </div>
                            <div class="attribute-content">
                                <div class="attribute-label">Hair Goals</div>
                                <div class="attribute-value"><?php echo esc_html($profile_data['hair_goals']); ?></div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
            
            <?php if ($atts['show_analytics'] === 'true' && !empty($profile_data['chart_data'])): ?>
            <!-- Analytics Section -->
            <section class="analytics-section">
                <div class="section-header">
                    <h2 class="section-title">
                        <svg viewBox="0 0 24 24">
                            <path d="M3.5 18.49l6-6.01 4 4L22 6.92l-1.41-1.41-7.09 7.97-4-4L2 16.99z"/>
                        </svg>
                        Hair Health Analytics
                    </h2>
                    <div class="analytics-controls">
                        <select id="analyticsTimeframe" class="timeframe-select">
                            <option value="30">Last 30 days</option>
                            <option value="90" selected>Last 3 months</option>
                            <option value="180">Last 6 months</option>
                            <option value="365">Last year</option>
                        </select>
                    </div>
                </div>
                
                <div class="analytics-content">
                    <div class="chart-container">
                        <canvas id="healthChart" width="400" height="200"></canvas>
                    </div>
                    
                    <div class="analytics-insights">
                        <div class="insight-card">
                            <div class="insight-icon">
                                <svg viewBox="0 0 24 24">
                                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                </svg>
                            </div>
                            <div class="insight-content">
                                <div class="insight-title">Best Health Period</div>
                                <div class="insight-value"><?php echo esc_html($profile_data['best_period']); ?></div>
                            </div>
                        </div>
                        
                        <div class="insight-card">
                            <div class="insight-icon">
                                <svg viewBox="0 0 24 24">
                                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                </svg>
                            </div>
                            <div class="insight-content">
                                <div class="insight-title">Most Used Product</div>
                                <div class="insight-value"><?php echo esc_html($profile_data['top_product']); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <?php endif; ?>
            
            <!-- Recent Activity -->
            <section class="recent-activity-section">
                <div class="section-header">
                    <h2 class="section-title">
                        <svg viewBox="0 0 24 24">
                            <path d="M13,3A9,9 0 0,0 4,12H1L4.96,16.03L9,12H6A7,7 0 0,1 13,5A7,7 0 0,1 20,12A7,7 0 0,1 13,19C11.07,19 9.32,18.21 8.06,16.94L6.64,18.36C8.27,20 10.5,21 13,21A9,9 0 0,0 22,12A9,9 0 0,0 13,3Z"/>
                        </svg>
                        Recent Activity
                    </h2>
                    <a href="#" class="view-all-link">View All</a>
                </div>
                
                <div class="activity-timeline">
                    <?php foreach ($profile_data['recent_entries'] as $entry): ?>
                    <article class="activity-item">
                        <div class="activity-icon">
                            <svg viewBox="0 0 24 24">
                                <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/>
                            </svg>
                        </div>
                        <div class="activity-content">
                            <h3 class="activity-title"><?php echo esc_html($entry['title']); ?></h3>
                            <p class="activity-description"><?php echo esc_html(wp_trim_words($entry['description'], 20)); ?></p>
                            <time class="activity-time" datetime="<?php echo esc_attr($entry['date']); ?>">
                                <?php echo myavana_time_ago($entry['date']); ?>
                            </time>
                        </div>
                        <?php if (!empty($entry['image'])): ?>
                        <div class="activity-image">
                            <img src="<?php echo esc_url($entry['image']); ?>" alt="<?php echo esc_attr($entry['title']); ?>" loading="lazy">
                        </div>
                        <?php endif; ?>
                    </article>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>
        
        <!-- AI Analysis History -->
        <?php if (!empty($profile_data['ai_analyses'])): ?>
        <section class="ai-analysis-section">
            <div class="section-header">
                <h2 class="section-title">
                    <svg viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                    </svg>
                    AI Analysis History
                </h2>
            </div>
            
            <div class="ai-analysis-grid">
                <?php foreach (array_slice($profile_data['ai_analyses'], 0, 6) as $analysis): ?>
                <div class="analysis-card">
                    <div class="analysis-image">
                        <img src="<?php echo esc_url($analysis['image']); ?>" alt="Hair analysis" loading="lazy">
                    </div>
                    <div class="analysis-content">
                        <time class="analysis-date" datetime="<?php echo esc_attr($analysis['date']); ?>">
                            <?php echo date_i18n('M j, Y', strtotime($analysis['date'])); ?>
                        </time>
                        <p class="analysis-summary"><?php echo esc_html(wp_trim_words($analysis['summary'], 15)); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
    </div>
    
    <!-- Edit Profile Modal -->
    <?php if ($is_owner && $atts['show_edit'] === 'true'): ?>
    <div class="modal-overlay" id="editProfileModal" role="dialog" aria-labelledby="editProfileTitle" aria-hidden="true">
        <div class="modal-container">
            <header class="modal-header">
                <h2 id="editProfileTitle">Edit Profile</h2>
                <button type="button" class="modal-close" aria-label="Close modal">×</button>
            </header>
            
            <div class="modal-body">
                <form id="profileForm" class="profile-form">
                    <div class="form-section">
                        <h3 class="section-title">Basic Information</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="displayName" class="form-label">Display Name</label>
                                <input type="text" id="displayName" name="display_name" 
                                       value="<?php echo esc_attr($user_data->display_name); ?>" 
                                       class="form-input" maxlength="100">
                            </div>
                            
                            <div class="form-group">
                                <label for="location" class="form-label">Location</label>
                                <input type="text" id="location" name="location" 
                                       value="<?php echo esc_attr($profile_data['location']); ?>" 
                                       class="form-input" maxlength="100">
                            </div>
                            
                            <div class="form-group full-width">
                                <label for="aboutMe" class="form-label">About Me</label>
                                <textarea id="aboutMe" name="about_me" class="form-textarea" 
                                          rows="4" maxlength="500"><?php echo esc_textarea($profile_data['about_me']); ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3 class="section-title">Hair Information</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="hairType" class="form-label">Hair Type</label>
                                <select id="hairType" name="hair_type" class="form-select">
                                    <option value="">Select hair type</option>
                                    <option value="1A" <?php selected($profile_data['hair_type'], '1A'); ?>>1A - Fine, Straight</option>
                                    <option value="1B" <?php selected($profile_data['hair_type'], '1B'); ?>>1B - Medium, Straight</option>
                                    <option value="1C" <?php selected($profile_data['hair_type'], '1C'); ?>>1C - Coarse, Straight</option>
                                    <option value="2A" <?php selected($profile_data['hair_type'], '2A'); ?>>2A - Fine, Wavy</option>
                                    <option value="2B" <?php selected($profile_data['hair_type'], '2B'); ?>>2B - Medium, Wavy</option>
                                    <option value="2C" <?php selected($profile_data['hair_type'], '2C'); ?>>2C - Coarse, Wavy</option>
                                    <option value="3A" <?php selected($profile_data['hair_type'], '3A'); ?>>3A - Fine, Curly</option>
                                    <option value="3B" <?php selected($profile_data['hair_type'], '3B'); ?>>3B - Medium, Curly</option>
                                    <option value="3C" <?php selected($profile_data['hair_type'], '3C'); ?>>3C - Coarse, Curly</option>
                                    <option value="4A" <?php selected($profile_data['hair_type'], '4A'); ?>>4A - Fine, Coily</option>
                                    <option value="4B" <?php selected($profile_data['hair_type'], '4B'); ?>>4B - Medium, Coily</option>
                                    <option value="4C" <?php selected($profile_data['hair_type'], '4C'); ?>>4C - Coarse, Coily</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="journeyStage" class="form-label">Journey Stage</label>
                                <select id="journeyStage" name="hair_journey_stage" class="form-select">
                                    <option value="Not set" <?php selected($profile_data['hair_journey_stage'], 'Not set'); ?>>Not set</option>
                                    <option value="Beginning" <?php selected($profile_data['hair_journey_stage'], 'Beginning'); ?>>Just Starting</option>
                                    <option value="Learning" <?php selected($profile_data['hair_journey_stage'], 'Learning'); ?>>Learning & Experimenting</option>
                                    <option value="Progressing" <?php selected($profile_data['hair_journey_stage'], 'Progressing'); ?>>Making Progress</option>
                                    <option value="Advanced" <?php selected($profile_data['hair_journey_stage'], 'Advanced'); ?>>Advanced Care</option>
                                    <option value="Maintenance" <?php selected($profile_data['hair_journey_stage'], 'Maintenance'); ?>>Maintenance Mode</option>
                                </select>
                            </div>
                            
                            <div class="form-group full-width">
                                <label for="hairGoals" class="form-label">Hair Goals</label>
                                <textarea id="hairGoals" name="hair_goals" class="form-textarea" 
                                          rows="3" maxlength="500" placeholder="What are your hair goals?"><?php echo esc_textarea($profile_data['hair_goals']); ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <?php wp_nonce_field('myavana_profile_update', 'profile_nonce'); ?>
                </form>
            </div>
            
            <footer class="modal-footer">
                <button type="button" class="btn btn-secondary cancel-edit-btn">Cancel</button>
                <button type="submit" form="profileForm" class="btn btn-primary save-profile-btn">
                    <span class="btn-text">Save Changes</span>
                    <span class="btn-loader" style="display: none;">Saving...</span>
                </button>
            </footer>
        </div>
    </div>
    <?php endif; ?>
    
    <?php
    return ob_get_clean();
}

// Helper functions
function myavana_render_auth_required($context = 'general') {
    return '<div class="profile-auth-required">
        <div class="auth-card">
            <h3>🔒 Login Required</h3>
            <p>Please log in to view ' . esc_html($context) . ' information.</p>
            <a href="' . wp_login_url(get_permalink()) . '" class="auth-btn">Login</a>
        </div>
    </div>';
}

function myavana_get_enhanced_profile_data($user_id) {
    global $wpdb;
    
    // Get base profile data
    $table_name = $wpdb->prefix . 'myavana_profiles';
    $profile = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE user_id = %d", $user_id));
    
    if (!$profile) {
        // Create default profile
        $wpdb->insert($table_name, [
            'user_id' => $user_id,
            'hair_journey_stage' => 'Not set',
            'hair_health_rating' => 5,
            'life_journey_stage' => 'Not set',
            'birthday' => '',
            'location' => '',
            'hair_type' => '',
            'hair_goals' => ''
        ]);
        $profile = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE user_id = %d", $user_id));
    }
    
    // Get entries for analytics
    $entries = new WP_Query([
        'post_type' => 'hair_journey_entry',
        'author' => $user_id,
        'posts_per_page' => -1,
        'orderby' => 'date',
        'order' => 'DESC'
    ]);
    
    $ratings = [];
    $chart_data = [];
    $products = [];
    $recent_entries = [];
    
    while ($entries->have_posts()) {
        $entries->the_post();
        $entry_id = get_the_ID();
        $rating = get_post_meta($entry_id, 'health_rating', true);
        $products_used = get_post_meta($entry_id, 'products_used', true);
        
        if ($rating) {
            $ratings[] = intval($rating);
            $chart_data[] = [
                'date' => get_the_date('Y-m-d'),
                'rating' => intval($rating)
            ];
        }
        
        if ($products_used) {
            $product_list = array_map('trim', explode(',', $products_used));
            $products = array_merge($products, $product_list);
        }
        
        if (count($recent_entries) < 5) {
            $recent_entries[] = [
                'id' => $entry_id,
                'title' => get_the_title(),
                'description' => get_the_content(),
                'date' => get_the_date('Y-m-d H:i:s'),
                'image' => get_the_post_thumbnail_url($entry_id, 'medium')
            ];
        }
    }
    wp_reset_postdata();
    
    // Calculate stats
    $avg_health = $ratings ? round(array_sum($ratings) / count($ratings), 1) : 0;
    $health_trend = 0;
    if (count($ratings) > 1) {
        $recent_avg = array_slice($ratings, 0, 3);
        $older_avg = array_slice($ratings, -3);
        if (count($recent_avg) && count($older_avg)) {
            $recent_avg = array_sum($recent_avg) / count($recent_avg);
            $older_avg = array_sum($older_avg) / count($older_avg);
            $health_trend = (($recent_avg - $older_avg) / $older_avg) * 100;
        }
    }
    
    // Get AI analysis history
    $ai_analyses = get_user_meta($user_id, 'myavana_hair_analysis_history', true) ?: [];
    
    // Get most used product
    $product_counts = array_count_values($products);
    arsort($product_counts);
    $top_product = !empty($product_counts) ? array_key_first($product_counts) : 'N/A';
    
    // Calculate streak
    $current_streak = myavana_calculate_user_streak($user_id);
    
    return [
        'hair_journey_stage' => $profile->hair_journey_stage ?: 'Not set',
        'hair_health_rating' => $profile->hair_health_rating ?: 0,
        'location' => $profile->location ?: '',
        'hair_type' => $profile->hair_type ?: '',
        'hair_goals' => $profile->hair_goals ?: '',
        'about_me' => get_user_meta($user_id, 'myavana_about_me', true) ?: '',
        'avg_health_rating' => $avg_health,
        'total_entries' => count($ratings),
        'health_trend' => round($health_trend, 1),
        'current_streak' => $current_streak,
        'chart_data' => $chart_data,
        'recent_entries' => $recent_entries,
        'ai_analyses' => array_slice($ai_analyses, 0, 6),
        'top_product' => $top_product,
        'best_period' => myavana_get_best_health_period($chart_data)
    ];
}

function myavana_get_user_status($user_id) {
    $last_activity = get_user_meta($user_id, 'last_activity', true);
    if (!$last_activity) return 'offline';
    
    $time_diff = time() - strtotime($last_activity);
    if ($time_diff < 300) return 'online';
    if ($time_diff < 3600) return 'away';
    return 'offline';
}

function myavana_get_user_status_text($user_id) {
    $status = myavana_get_user_status($user_id);
    $texts = [
        'online' => 'Active now',
        'away' => 'Away',
        'offline' => 'Offline'
    ];
    return $texts[$status] ?? 'Unknown';
}

function myavana_is_verified_user($user_id) {
    return (bool) get_user_meta($user_id, 'myavana_verified', true);
}

function myavana_time_ago($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    if ($time < 31536000) return floor($time/2592000) . ' months ago';
    return floor($time/31536000) . ' years ago';
}

function myavana_calculate_user_streak($user_id) {
    global $wpdb;
    
    $entries = $wpdb->get_results($wpdb->prepare("
        SELECT DATE(post_date) as entry_date 
        FROM {$wpdb->posts} 
        WHERE post_author = %d 
        AND post_type = 'hair_journey_entry' 
        AND post_status = 'publish'
        ORDER BY post_date DESC
    ", $user_id));
    
    if (empty($entries)) return 0;
    
    $streak = 0;
    $current_date = date('Y-m-d');
    $entry_dates = array_column($entries, 'entry_date');
    $entry_dates = array_unique($entry_dates);
    
    foreach ($entry_dates as $entry_date) {
        if ($entry_date === $current_date || (strtotime($current_date) - strtotime($entry_date)) <= 86400) {
            $streak++;
            $current_date = date('Y-m-d', strtotime($current_date . ' -1 day'));
        } else {
            break;
        }
    }
    
    return $streak;
}

function myavana_get_best_health_period($chart_data) {
    if (empty($chart_data)) return 'N/A';
    
    $max_rating = max(array_column($chart_data, 'rating'));
    $best_entries = array_filter($chart_data, function($entry) use ($max_rating) {
        return $entry['rating'] == $max_rating;
    });
    
    if (empty($best_entries)) return 'N/A';
    
    $best_date = reset($best_entries)['date'];
    return date_i18n('F Y', strtotime($best_date));
}

// Register the enhanced shortcode
add_shortcode('myavana_enhanced_profile', 'myavana_enhanced_profile_shortcode');