<?php
/**
 * Header and Sidebar Partial for Hair Journey Page
 *
 * This partial expects $shared_data to be passed from the main shortcode.
 * All data is fetched ONCE by Myavana_Data_Manager to avoid redundant queries.
 *
 * Available variables from $shared_data:
 * - $user_id, $profile, $typeform_data, $hair_goals, $about_me
 * - $analysis_history, $current_routine, $user_stats, $analytics_data
 * - $analysis_limit_info (limit, count, can_analyze, remaining)
 */

// These variables are already available from parent scope (hair-journey.php)
// No need to re-fetch them - they're passed via $shared_data

$is_owner = true; // Current user viewing their own journey

// Extract analysis snapshots from profile
$snapshots = $user_profile->hair_analysis_snapshots ? json_decode($user_profile->hair_analysis_snapshots, true) : [];
usort($snapshots, function($a, $b) {
    return strtotime($b['timestamp']) <=> strtotime($a['timestamp']);
});

// Get analytics data (already cached in $shared_data)
$analytics_data = $shared_data['analytics'];

// Generate dynamic AI insight (already calculated)
$ai_insight = myavana_generate_ai_insights_new($user_id);

// Calculate total entries for display
$total_entries = $analytics_data['total_entries'];

// Hair profile details (already available from $shared_data)
$hair_porosity = $shared_data['hair_porosity'];
$hair_length = $shared_data['hair_length'];
global $wpdb;
$table_name = $wpdb->prefix . 'myavana_profiles';
$profile = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE user_id = %d", $user_id));
?>
<div>
    <!-- Dashboard Header -->
    <header class="dashboard-header">
        <div class="header-content">
            <div class="welcome-section">
                <div class="welcome-message">
                    <h1 class="welcome-title">
                        Good Morning,  <?php echo esc_html( isset($current_user->display_name) ? $current_user->display_name : 'Friend' ); ?>!  ✨
                    </h1>
                    <p class="welcome-subtitle">You're making amazing progress on your hair journey</p>
                </div>
                
                <div class="dashboard-controls">
                    <div class="view-controls">
                        <button type="button" class="view-btn active" data-view="calendar" onclick="switchView('calendar')" title="Calendar View">
                            <svg viewBox="0 0 24 24"><path d="M19,19H5V8H19M16,1V3H8V1H6V3H5C3.89,3 3,3.89 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19V5C21,3.89 20.1,3 19,3H18V1M17,12H12V17H17V12Z"/></svg>
                            <span class="view-label">Calendar</span>
                        </button>
                        <button type="button" class="view-btn" data-view="timeline" onclick="switchView('timeline')" title="Hair Journey Timeline">
                            <svg viewBox="0 0 24 24"><path d="M23,12L20.56,9.22L20.9,5.54L17.29,4.72L15.4,1.54L12,3L8.6,1.54L6.71,4.72L3.1,5.53L3.44,9.21L1,12L3.44,14.78L3.1,18.47L6.71,19.29L8.6,22.47L12,21L15.4,22.46L17.29,19.28L20.9,18.46L20.56,14.78L23,12Z"/></svg>
                            <span class="view-label">Timeline</span>
                        </button>
                        
                        <button type="button" class="view-btn" data-view="slider" onclick="switchView('slider')" title="Slider View">
                            <svg viewBox="0 0 24 24"><path d="M22,16V4A2,2 0 0,0 20,2H8A2,2 0 0,0 6,4V16A2,2 0 0,0 8,18H20A2,2 0 0,0 22,16M11,12L13.03,14.71L16,11L20,16H8M2,6V20A2,2 0 0,0 4,22H18V20H4V6"/></svg>
                            <span class="view-label">Slider</span>
                        </button>
                        <button type="button" class="view-btn" data-view="list" onclick="switchView('list')" title="List View">
                            <svg viewBox="0 0 24 24"><path d="M3 13h2v-2H3v2zm0 4h2v-2H3v2zm0-8h2V7H3v2zm4 4h14v-2H7v2zm0 4h14v-2H7v2zM7 7v2h14V7H7z"/></svg>
                            <span class="view-label">List</span>
                        </button>
                    </div>
                    
                    <div class="action-buttons">
                        <button type="button" class="action-btn action-btn-secondary" onclick="createGoal()">
                            + Goal
                        </button>
                        <button type="button" class="action-btn action-btn-secondary" onclick="createRoutine()">
                            + Routine
                        </button>
                        <button type="button" class="action-btn action-btn-smart" onclick="openAIAnalysisModal()" title="AI-powered entry with camera">
                            ✨ Smart Entry
                        </button>
                        <button type="button" class="action-btn action-btn-primary" onclick="createEntry()">
                            + Entry
                        </button>
                    </div>

                    <!-- <button type="button" class="theme-toggle" id="themeToggle" title="Toggle dark mode">
                        <svg class="sun-icon" viewBox="0 0 24 24">
                            <path d="M12 2.25a.75.75 0 01.75.75v2.25a.75.75 0 01-1.5 0V3a.75.75 0 01.75-.75zM7.5 12a4.5 4.5 0 119 0 4.5 4.5 0 01-9 0zM18.894 6.166a.75.75 0 00-1.06-1.06l-1.591 1.59a.75.75 0 101.06 1.061l1.591-1.59zM21.75 12a.75.75 0 01-.75.75h-2.25a.75.75 0 010-1.5H21a.75.75 0 01.75.75zM17.834 18.894a.75.75 0 001.06-1.06l-1.59-1.591a.75.75 0 10-1.061 1.06l1.59 1.591zM12 18a.75.75 0 01.75.75V21a.75.75 0 01-1.5 0v-2.25A.75.75 0 0112 18zM7.758 17.303a.75.75 0 00-1.061-1.06l-1.591 1.59a.75.75 0 001.06 1.061l1.591-1.59zM6 12a.75.75 0 01-.75.75H3a.75.75 0 010-1.5h2.25A.75.75 0 016 12zM6.697 7.757a.75.75 0 001.06-1.06l-1.59-1.591a.75.75 0 00-1.061 1.06l1.59 1.591z"/>
                        </svg>
                        <svg class="moon-icon" viewBox="0 0 24 24" style="display: none;">
                            <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"/>
                        </svg>
                    </button> -->
                </div>
            </div>
            
            <div class="streak-section">
                <div class="streak-card">
                    <div class="streak-flame">🔥</div>
                    <div class="streak-content">
                        <div class="streak-number"><?php echo esc_html( intval( $user_stats['days_active'] ) ); ?></div>
                        <div class="streak-label">Days Active</div>
                    </div>
                </div>
            </div>
            <!-- Mobile Sidebar Header (Collapsible) -->
        <div class="sidebar-mobile-header">
            <div class="sidebar-mobile-title">
                <svg viewBox="0 0 24 24" width="20" height="20">
                    <path fill="currentColor" d="M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M12,4A8,8 0 0,1 20,12A8,8 0 0,1 12,20A8,8 0 0,1 4,12A8,8 0 0,1 12,4M11,16.5L18,9.5L16.59,8.09L11,13.67L7.91,10.59L6.5,12L11,16.5Z"/>
                </svg>
                <span>My Hair Journey Dashboard</span>
            </div>
            <div class="sidebar-mobile-toggle-icon" id="mobileSidebarIcon">
                <svg viewBox="0 0 24 24" width="24" height="24">
                    <path fill="currentColor" d="M7.41,8.58L12,13.17L16.59,8.58L18,10L12,16L6,10L7.41,8.58Z"/>
                </svg>
            </div>
        </div>
        </div>
    </header>

    <div class="main-content">
       

        <!-- Compare Analysis Modal -->
        <div id="compareAnalysisModal" class="modal">
            <div class="modal-content" style="max-width: 1200px;">
                <div class="modal-header">
                    <h2 style="font-family: 'Archivo Black', sans-serif; color: var(--myavana-onyx); margin: 0;">Compare Hair Analyses</h2>
                    <span class="modal-close" onclick="closeCompareModal()">&times;</span>
                </div>
                <div class="modal-body">
                    <div class="compare-selection" style="margin-bottom: 2rem;">
                        <p style="color: var(--myavana-blueberry); margin-bottom: 1rem;">Select two analyses to compare:</p>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                            <div>
                                <label style="font-family: 'Archivo', sans-serif; font-weight: 600; color: var(--myavana-onyx); display: block; margin-bottom: 0.5rem;">First Analysis</label>
                                <select id="compareAnalysis1" class="compare-select" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 8px; font-family: 'Archivo', sans-serif;">
                                    <option value="">Select an analysis...</option>
                                </select>
                            </div>
                            <div>
                                <label style="font-family: 'Archivo', sans-serif; font-weight: 600; color: var(--myavana-onyx); display: block; margin-bottom: 0.5rem;">Second Analysis</label>
                                <select id="compareAnalysis2" class="compare-select" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 8px; font-family: 'Archivo', sans-serif;">
                                    <option value="">Select an analysis...</option>
                                </select>
                            </div>
                        </div>
                        <button id="startComparison" onclick="generateComparison()" style="margin-top: 1.5rem; padding: 0.75rem 2rem; background: var(--myavana-coral); color: var(--myavana-white); border: none; border-radius: 8px; font-family: 'Archivo', sans-serif; font-weight: 600; cursor: pointer; transition: all 0.3s;">
                            Compare Selected Analyses
                        </button>
                    </div>
                    <div id="comparisonResults" style="display: none;">
                        <!-- Comparison results will be populated here -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Profile Edit Offcanvas -->
        <div class="offcanvas-overlay-hjn"></div>
        <div class="offcanvas-hjn profile-edit">
            <div class="offcanvas-header-hjn">
                <h3 class="offcanvas-title-hjn">Edit Profile</h3>
                <button class="offcanvas-close-hjn">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            <div class="offcanvas-body-hjn">
                <form id="sidebar-profile-edit-form">
                    <!-- Bio Section -->
                    <div class="sidebar-edit-section">
                        <h4 class="sidebar-edit-title">About Me</h4>
                        <textarea
                            id="sidebar-bio"
                            name="bio"
                            class="sidebar-edit-textarea"
                            rows="3"
                            placeholder="Tell us about your hair journey..."><?php echo esc_textarea($about_me); ?></textarea>
                    </div>

                    <!-- Hair Profile Section -->
                    <div class="sidebar-edit-section">
                        <h4 class="sidebar-edit-title">Hair Profile</h4>
                        <div class="sidebar-edit-grid">
                            <div class="sidebar-edit-field">
                                <label for="sidebar-hair-type" class="sidebar-edit-label">Hair Type</label>
                                <select id="sidebar-hair-type" name="hair_type" class="sidebar-edit-select">
                                    <option value="">Select type...</option>
                                    <option value="1A" <?php selected($profile->hair_type, '1A'); ?>>1A - Straight, Fine</option>
                                    <option value="1B" <?php selected($profile->hair_type, '1B'); ?>>1B - Straight, Medium</option>
                                    <option value="1C" <?php selected($profile->hair_type, '1C'); ?>>1C - Straight, Coarse</option>
                                    <option value="2A" <?php selected($profile->hair_type, '2A'); ?>>2A - Wavy, Fine</option>
                                    <option value="2B" <?php selected($profile->hair_type, '2B'); ?>>2B - Wavy, Medium</option>
                                    <option value="2C" <?php selected($profile->hair_type, '2C'); ?>>2C - Wavy, Coarse</option>
                                    <option value="3A" <?php selected($profile->hair_type, '3A'); ?>>3A - Curly, Loose</option>
                                    <option value="3B" <?php selected($profile->hair_type, '3B'); ?>>3B - Curly, Tight</option>
                                    <option value="3C" <?php selected($profile->hair_type, '3C'); ?>>3C - Curly, Corkscrew</option>
                                    <option value="4A" <?php selected($profile->hair_type, '4A'); ?>>4A - Coily, Soft</option>
                                    <option value="4B" <?php selected($profile->hair_type, '4B'); ?>>4B - Coily, Wiry</option>
                                    <option value="4C" <?php selected($profile->hair_type, '4C'); ?>>4C - Coily, Very Wiry</option>
                                </select>
                            </div>

                            <div class="sidebar-edit-field">
                                <label for="sidebar-hair-porosity" class="sidebar-edit-label">Porosity</label>
                                <select id="sidebar-hair-porosity" name="hair_porosity" class="sidebar-edit-select">
                                    <option value="">Select...</option>
                                    <option value="Low" <?php selected($hair_porosity, 'Low'); ?>>Low</option>
                                    <option value="Medium" <?php selected($hair_porosity, 'Medium'); ?>>Medium</option>
                                    <option value="High" <?php selected($hair_porosity, 'High'); ?>>High</option>
                                </select>
                            </div>

                            <div class="sidebar-edit-field">
                                <label for="sidebar-hair-length" class="sidebar-edit-label">Length</label>
                                <select id="sidebar-hair-length" name="hair_length" class="sidebar-edit-select">
                                    <option value="">Select...</option>
                                    <option value="Ear Length" <?php selected($hair_length, 'Ear Length'); ?>>Ear Length</option>
                                    <option value="Chin Length" <?php selected($hair_length, 'Chin Length'); ?>>Chin Length</option>
                                    <option value="Shoulder Length" <?php selected($hair_length, 'Shoulder Length'); ?>>Shoulder Length</option>
                                    <option value="Mid-Back" <?php selected($hair_length, 'Mid-Back'); ?>>Mid-Back</option>
                                    <option value="Waist Length" <?php selected($hair_length, 'Waist Length'); ?>>Waist Length</option>
                                    <option value="Hip Length" <?php selected($hair_length, 'Hip Length'); ?>>Hip Length</option>
                                </select>
                            </div>

                            <div class="sidebar-edit-field">
                                <label for="sidebar-journey-stage" class="sidebar-edit-label">Journey Stage</label>
                                <select id="sidebar-journey-stage" name="journey_stage" class="sidebar-edit-select">
                                    <option value="Not set" <?php selected($profile->hair_journey_stage, 'Not set'); ?>>Not set</option>
                                    <option value="Postpartum haircare" <?php selected($profile->hair_journey_stage, 'Postpartum haircare'); ?>>Postpartum haircare</option>
                                    <option value="Nourishing and growing" <?php selected($profile->hair_journey_stage, 'Nourishing and growing'); ?>>Nourishing and growing</option>
                                    <option value="Experimenting" <?php selected($profile->hair_journey_stage, 'Experimenting'); ?>>Experimenting</option>
                                    <option value="Bored/Stuck" <?php selected($profile->hair_journey_stage, 'Bored/Stuck'); ?>>Bored/Stuck</option>
                                    <option value="Repairing and restoring" <?php selected($profile->hair_journey_stage, 'Repairing and restoring'); ?>>Repairing and restoring</option>
                                    <option value="Desperate for a change" <?php selected($profile->hair_journey_stage, 'Desperate for a change'); ?>>Desperate for a change</option>
                                    <option value="Trying something new" <?php selected($profile->hair_journey_stage, 'Trying something new'); ?>>Trying something new</option>
                                    <option value="Loving my recent hairstyle change" <?php selected($profile->hair_journey_stage, 'Loving my recent hairstyle change'); ?>>Loving my recent hairstyle change</option>
                                </select>
                            </div>
                            <div class="sidebar-edit-field" style="margin-top: 8px;">
                                <label for="birthday" class="form-label">Birthday</label>
                                <input type="date" id="birthday" name="birthday" value="<?php echo esc_attr($profile->birthday ?? ''); ?>" class="form-input">
                            </div>
                            <div class="sidebar-edit-field">
                                <label for="location" class="form-label">Location (City, State)</label>
                                <input type="text" id="location" name="location" value="<?php echo esc_attr($profile->location ?? ''); ?>" class="form-input" placeholder="e.g., Atlanta, GA">
                            </div>
                            <div class="sidebar-edit-field">
                                <label for="life_journey_stage" class="form-label">Life Journey Stage</label>
                                <input type="text" id="life_journey_stage" name="life_journey_stage" value="<?php echo esc_attr($profile->life_journey_stage ?? ''); ?>" class="form-input" placeholder="e.g., New mother, Career change">
                            </div>
                            <div class="sidebar-edit-field" style="margin-top: 8px;">
                                <label for="hair_health_rating" class="form-label">Hair Health Rating (1-5)</label>
                                <input type="number" id="hair_health_rating" name="hair_health_rating" min="1" max="5" step="1" value="<?php echo esc_attr($profile->hair_health_rating ?? 5); ?>" class="form-input" disabled required>
                            </div>
                        </div>
                    </div>

                    <!-- Goals Section -->
                    <div class="sidebar-edit-section">
                        <div class="sidebar-edit-section-header">
                            <h4 class="sidebar-edit-title">Hair Goals</h4>
                            <button type="button" class="sidebar-add-btn add-goal-btn-sidebar">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="12" y1="5" x2="12" y2="19"></line>
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                </svg>
                                Add Goal
                            </button>
                        </div>
                        <div id="sidebar-goals-edit-list" class="sidebar-edit-list">
                            <?php
                            $hair_goals = get_user_meta($user_id, 'myavana_hair_goals_structured', true) ?: [];
                            if (!empty($hair_goals)) {
                                foreach ($hair_goals as $index => $goal) {
                                    ?>
                                    <div class="sidebar-goal-edit-item">
                                        <span class="sidebar-goal-edit-text"><?php echo esc_html($goal['title']); ?></span>
                                        <button type="button" class="sidebar-goal-remove-btn remove-goal-chip" data-index="<?php echo $index; ?>">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                                <line x1="6" y1="6" x2="18" y2="18"></line>
                                            </svg>
                                        </button>
                                    </div>
                                    <?php
                                }
                            }
                            ?>
                        </div>
                    </div>

                    <!-- Routine Section -->
                    <div class="sidebar-edit-section">
                        <div class="sidebar-edit-section-header">
                            <h4 class="sidebar-edit-title">Current Routine</h4>
                            <button type="button" class="sidebar-add-btn add-routine-btn-sidebar">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="12" y1="5" x2="12" y2="19"></line>
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                </svg>
                                Add Step
                            </button>
                        </div>
                        <div id="sidebar-routine-edit-list" class="sidebar-edit-list">
                            <?php
                            $routine = get_user_meta($user_id, 'current_routine', true);
                            if (!empty($routine) && is_array($routine)) {
                                foreach ($routine as $index => $step) {
                                    $step_name = is_array($step) ? ($step['name'] ?? '') : $step;
                                    $step_frequency = is_array($step) ? ($step['frequency'] ?? 'daily') : 'daily';
                                    $freq_label = [
                                        'daily' => 'Daily',
                                        'weekly' => 'Weekly',
                                        'biweekly' => 'Bi-weekly',
                                        'monthly' => 'Monthly',
                                        'asneeded' => 'As Needed'
                                    ][$step_frequency] ?? ucfirst($step_frequency);
                                    ?>
                                    <div class="sidebar-routine-edit-item">
                                        <div class="sidebar-routine-edit-info">
                                            <span class="sidebar-routine-edit-name"><?php echo esc_html($step_name); ?></span>
                                            <span class="sidebar-routine-edit-freq"><?php echo esc_html($freq_label); ?></span>
                                        </div>
                                        <button type="button" class="sidebar-routine-remove-btn remove-routine-item" data-index="<?php echo $index; ?>">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                                <line x1="6" y1="6" x2="18" y2="18"></line>
                                            </svg>
                                        </button>
                                    </div>
                                    <?php
                                }
                            }
                            ?>
                        </div>
                    </div>
                </form>
            </div>
            <div class="offcanvas-footer-hjn">
                <button type="button" class="btn-secondary-hjn" id="cancel-profile-edit">
                    Cancel
                </button>
                <button type="submit" form="sidebar-profile-edit-form" class="btn-primary-hjn" id="save-profile-edit">
                    Save Changes
                </button>
            </div>
        </div>

    

    <script>
        /**
         * MYAVANA Timeline - UI State Management Module
         * Handles dark mode, sidebar, theme persistence, and responsive behavior
         *
         * @package Myavana_Hair_Journey
         * @version 2.3.5
         */

        // Initialize namespace if not exists
        window.MyavanaTimeline = window.MyavanaTimeline || {};

        // UI State Module
        MyavanaTimeline.UI = (function() {
            'use strict';

            /**
             * Toggle dark mode theme
             */
            function toggleDarkMode() {
                const container = document.querySelector('.hair-journey-container');
                const sunIcon = document.querySelector('.sun-icon');
                const moonIcon = document.querySelector('.moon-icon');

                if (!container) {
                    console.error('[Dark Mode] Container .hair-journey-container not found');
                    return;
                }

                if (!sunIcon || !moonIcon) {
                    console.error('[Dark Mode] Sun or moon icon not found');
                    return;
                }

                const currentTheme = container.getAttribute('data-theme') || 'light';
                const newTheme = currentTheme === 'light' ? 'dark' : 'light';

                console.log(`[Dark Mode] Switching from ${currentTheme} to ${newTheme}`);

                container.setAttribute('data-theme', newTheme);

                if (newTheme === 'dark') {
                    sunIcon.style.display = 'none';
                    moonIcon.style.display = 'block';
                } else {
                    sunIcon.style.display = 'block';
                    moonIcon.style.display = 'none';
                }

                // Save preference to localStorage
                localStorage.setItem('theme', newTheme);
                console.log('[Dark Mode] Theme saved to localStorage:', newTheme);
            }

            /**
             * Load theme preference from localStorage
             */
            function loadTheme() {
                const savedTheme = localStorage.getItem('theme') || 'light';
                const container = document.querySelector('.hair-journey-container');
                const sunIcon = document.querySelector('.sun-icon');
                const moonIcon = document.querySelector('.moon-icon');

                if (!container) {
                    console.error('[Load Theme] Container .hair-journey-container not found');
                    return;
                }

                if (!sunIcon || !moonIcon) {
                    console.error('[Load Theme] Sun or moon icon not found');
                    return;
                }

                console.log('[Load Theme] Loading saved theme:', savedTheme);

                container.setAttribute('data-theme', savedTheme);

                if (savedTheme === 'dark') {
                    sunIcon.style.display = 'none';
                    moonIcon.style.display = 'block';
                } else {
                    sunIcon.style.display = 'block';
                    moonIcon.style.display = 'none';
                }
            }

            /**
             * Toggle sidebar collapse (desktop only)
             */
            function toggleSidebar() {
                // Disable collapsing on mobile (screens <= 1024px)
                if (window.innerWidth <= 1024) {
                    return;
                }

                const sidebar = document.getElementById('sidebar');
                const toggle = document.getElementById('sidebarToggle');

                sidebar.classList.toggle('collapsed');

                if (sidebar.classList.contains('collapsed')) {
                    toggle.innerHTML = '›';
                    toggle.style.left = '10px';
                    // Save collapsed state
                    localStorage.setItem('sidebarCollapsed', 'true');
                } else {
                    toggle.innerHTML = '‹';
                    toggle.style.left = '400px';
                    // Save expanded state
                    localStorage.setItem('sidebarCollapsed', 'false');
                }
            }

            /**
             * Switch between sidebar tabs
             */
            function switchSidebarTab(tabName) {
                // Update tab buttons
                document.querySelectorAll('.sidebar-tab').forEach(tab => {
                    tab.classList.remove('active');
                });
                document.querySelector(`.sidebar-tab[data-tab="${tabName}"]`).classList.add('active');

                // Update tab content
                document.querySelectorAll('.sidebar-tab-content').forEach(content => {
                    content.classList.remove('active');
                });
                document.getElementById(tabName + 'Tab').classList.add('active');

                // Save active tab preference
                localStorage.setItem('activeSidebarTab', tabName);
            }

            /**
             * Load sidebar state from localStorage
             */
            function loadSidebarState() {
                const sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
                const activeSidebarTab = localStorage.getItem('activeSidebarTab') || 'insights';

                const sidebar = document.getElementById('sidebar');
                const toggle = document.getElementById('sidebarToggle');

                // Only restore collapsed state on desktop (> 1024px)
                if (window.innerWidth > 1024) {
                    if (sidebarCollapsed) {
                        sidebar.classList.add('collapsed');
                        toggle.innerHTML = '›';
                        toggle.style.left = '10px';
                    } else {
                        toggle.style.left = '400px';
                    }
                } else {
                    // On mobile, always show sidebar expanded
                    sidebar.classList.remove('collapsed');
                }

                // Restore active tab
                switchSidebarTab(activeSidebarTab);
            }

            /**
             * Handle window resize events
             */
            function handleResize() {
                const sidebar = document.getElementById('sidebar');
                const toggle = document.getElementById('sidebarToggle');

                if (window.innerWidth <= 1024) {
                    // On mobile, remove desktop collapsed class
                    sidebar.classList.remove('collapsed');
                    // Restore mobile collapsed state
                    const mobileCollapsed = localStorage.getItem('mobileSidebarCollapsed') === 'true';
                    if (mobileCollapsed) {
                        sidebar.classList.add('mobile-collapsed');
                    } else {
                        sidebar.classList.remove('mobile-collapsed');
                    }
                } else {
                    // On desktop, remove mobile collapsed class
                    sidebar.classList.remove('mobile-collapsed');
                    // Restore desktop collapsed state from localStorage
                    const sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
                    if (sidebarCollapsed) {
                        sidebar.classList.add('collapsed');
                        toggle.innerHTML = '›';
                        toggle.style.left = '10px';
                    } else {
                        sidebar.classList.remove('collapsed');
                        toggle.innerHTML = '‹';
                        toggle.style.left = '400px';
                    }
                }
            }

            /**
             * Reset sidebar state (for debugging)
             */
            function resetSidebar() {
                localStorage.removeItem('sidebarCollapsed');
                localStorage.removeItem('mobileSidebarCollapsed');
                localStorage.removeItem('activeSidebarTab');
                location.reload();
            }

            /**
             * Toggle mobile sidebar (accordion style)
             */
            function toggleMobileSidebar() {
                console.log('toggleMobileSidebar in timeline-ui-state.js');
                // Only work on mobile/tablet screens
                if (window.innerWidth > 1024) {
                    return;
                }

                const sidebar = document.getElementById('sidebar');
                const icon = document.getElementById('mobileSidebarIcon');
                console.log('sidebar.classList.toggle("mobile-collapsed")', sidebar.classList.toggle('mobile-collapsed'));

                sidebar.classList.toggle('mobile-collapsed');
                console.log('sidebar.classList.toggle("mobile-collapsed")', sidebar.classList.toggle('mobile-collapsed'));
                // Save mobile collapsed state
                const isCollapsed = sidebar.classList.contains('mobile-collapsed');
                localStorage.setItem('mobileSidebarCollapsed', isCollapsed);
            }

            /**
             * Load mobile sidebar state from localStorage
             */
            function loadMobileSidebarState() {
                if (window.innerWidth <= 1024) {
                    const mobileCollapsed = localStorage.getItem('mobileSidebarCollapsed') === 'true';
                    const sidebar = document.getElementById('sidebar');

                    if (mobileCollapsed) {
                        sidebar.classList.add('mobile-collapsed');
                    }
                }
            }

            /**
             * Edit profile handler
             */
            function editProfile() {
                alert('Edit Profile: Opens modal to edit hair type, porosity, goals, and regimen details!');
            }

            /**
             * Initialize UI state module
             */
            function init() {
                loadTheme();
                loadSidebarState();
                loadMobileSidebarState();

                // Setup resize handler
                window.addEventListener('resize', handleResize);
            }

            // Public API
            return {
                init: init,
                toggleDarkMode: toggleDarkMode,
                loadTheme: loadTheme,
                toggleSidebar: toggleSidebar,
                switchSidebarTab: switchSidebarTab,
                loadSidebarState: loadSidebarState,
                handleResize: handleResize,
                resetSidebar: resetSidebar,
                toggleMobileSidebar: toggleMobileSidebar,
                loadMobileSidebarState: loadMobileSidebarState,
                editProfile: editProfile
            };
        })();

        // Expose global functions for backward compatibility
        window.toggleDarkMode = MyavanaTimeline.UI.toggleDarkMode;
        window.toggleSidebar = MyavanaTimeline.UI.toggleSidebar;
        window.switchSidebarTab = MyavanaTimeline.UI.switchSidebarTab;
        window.toggleMobileSidebar = MyavanaTimeline.UI.toggleMobileSidebar;
        window.editProfile = MyavanaTimeline.UI.editProfile;
        window.resetSidebar = MyavanaTimeline.UI.resetSidebar;

    </script>
