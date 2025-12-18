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
        <div class="sidebar" id="sidebar">
        <button class="sidebar-toggle" id="sidebarToggle" onclick="toggleSidebar()">
            ‹
        </button>

        

        <div class="sidebar-content" id="sidebarContent">
            <!-- Sidebar Tab Navigation -->
            <div class="sidebar-tabs">
                <button class="sidebar-tab active" data-tab="insights" onclick="switchSidebarTab('insights')">
                    <svg viewBox="0 0 24 24" width="18" height="18">
                        <path fill="currentColor" d="M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M12,4A8,8 0 0,1 20,12A8,8 0 0,1 12,20A8,8 0 0,1 4,12A8,8 0 0,1 12,4M11,16.5L18,9.5L16.59,8.09L11,13.67L7.91,10.59L6.5,12L11,16.5Z"/>
                    </svg>
                    <span>Ai Analysis</span>
                </button>
                <button class="sidebar-tab" data-tab="profile" onclick="switchSidebarTab('profile')">
                    <svg viewBox="0 0 24 24" width="18" height="18">
                        <path fill="currentColor" d="M12,4A4,4 0 0,1 16,8A4,4 0 0,1 12,12A4,4 0 0,1 8,8A4,4 0 0,1 12,4M12,14C16.42,14 20,15.79 20,18V20H4V18C4,15.79 7.58,14 12,14Z"/>
                    </svg>
                    <span>Profile</span>
                </button>
                <button class="sidebar-tab" data-tab="analytics" onclick="switchSidebarTab('analytics')">
                    <svg viewBox="0 0 24 24" width="18" height="18">
                        <path fill="currentColor" d="M22,21H2V3H4V19H6V10H10V19H12V6H16V19H18V14H22V21Z"/>
                    </svg>
                    <span>Analytics</span>
                </button>
            </div>

            <!-- AI Insights Tab -->
            <div class="sidebar-tab-content active" id="insightsTab">
                <div class="ai-insights">
                    <h3>✨ AI Insights</h3>
                    <p><?php echo esc_html($ai_insight); ?></p>
                </div>

                <div class="myavana-insights-container" data-category="all">
                    <!-- Insights will render here -->
                </div>

                <!-- Hair Analysis Section -->
                <div class="profile-section">
                    <div class="section-header">
                        <h2 class="section-title">Hair Analysis</h2>
                        <?php if ($is_owner && $can_analyze) : ?>
                            <div class="section-edit" data-section="analysis" id="addAnalysisBtn">
                                <i class="fas fa-plus"></i>
                                <span>Add Analysis</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="hair-analysis-container">
                        <?php if ($is_owner && $can_analyze): ?>
                            <div class="analysis-limit">
                                <p>Weekly analysis limit: <?php echo $analysis_count; ?>/<?php echo $analysis_limit; ?> used</p>
                            </div>
                        <?php elseif ($is_owner && !$can_analyze): ?>
                            <p class="limit-reached">You've reached your weekly analysis limit. New analyses will be available next week.</p>
                        <?php endif; ?>
                        
                        <!-- Hair Analysis Slider with Splide.js -->
                        <div class="analysis-slider-container">
                            <?php if (!empty($snapshots)) : ?>
                                <div class="splide analysis-splide" id="hair-analysis-splide">
                                    <div class="splide__track">
                                        <ul class="splide__list">
                                            <?php foreach ($snapshots as $index => $snapshot) : ?>
                                                <li class="splide__slide analysis-slide">
                                                    <div class="analysis-slide-visual">
                                                        <?php if ($snapshot['image_url'] ?? false) : ?>
                                                            <img src="<?php echo esc_url($snapshot['image_url']); ?>" alt="Hair Analysis" class="analysis-slide-image">
                                                        <?php else : ?>
                                                            <div class="analysis-slide-placeholder">
                                                                <i class="fas fa-camera"></i>
                                                                <span>No image available</span>
                                                            </div>
                                                        <?php endif; ?>
                                                        <div class="analysis-slide-date">
                                                            <?php echo esc_html(date('M j, Y', strtotime($snapshot['timestamp']))); ?>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="analysis-slide-content">
                                                        <div class="analysis-hair-type">
                                                            <div class="hair-type-icon">
                                                                <img src="<?php echo MYAVANA_URL; ?>assets/images/washing-hair-icon.png" alt="washing-hair-icon">
                                                            </div>
                                                            <div class="hair-type-info">
                                                                <h3><?php echo esc_html($snapshot['hair_analysis']['curl_pattern'] ?? '--'); ?></h3>
                                                                <p><?php echo esc_html($snapshot['hair_analysis']['type'] ?? 'Type not set'); ?></p>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="analysis-metrics-grid">
                                                            <div class="analysis-metric">
                                                                <div class="metric-value"><?php echo esc_html($snapshot['hair_analysis']['health_score'] ?? '--'); ?>%</div>
                                                                <div class="metric-label">Health</div>
                                                                <div class="metric-progress">
                                                                    <div class="metric-progress-fill" style="width: <?php echo esc_attr(($snapshot['hair_analysis']['health_score'] ?? 0) . '%'); ?>"></div>
                                                                </div>
                                                            </div>
                                                            <div class="analysis-metric">
                                                                <div class="metric-value"><?php echo esc_html($snapshot['hair_analysis']['hydration'] ?? '--'); ?>%</div>
                                                                <div class="metric-label">Hydration</div>
                                                                <div class="metric-progress">
                                                                    <div class="metric-progress-fill" style="width: <?php echo esc_attr(($snapshot['hair_analysis']['hydration'] ?? 0) . '%'); ?>"></div>
                                                                </div>
                                                            </div>
                                                            <div class="analysis-metric">
                                                                <div class="metric-value"><?php echo esc_html($snapshot['hair_analysis']['elasticity'] ?? '--'); ?></div>
                                                                <div class="metric-label">Elasticity</div>
                                                                <div class="metric-progress">
                                                                    <div class="metric-progress-fill" style="width: <?php echo esc_attr(($snapshot['hair_analysis']['elasticity'] ?? 0) . '%'); ?>"></div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="analysis-summary">
                                                            <?php echo esc_html(wp_trim_words($snapshot['summary'] ?? '', 25)); ?>
                                                        </div>
                                                        
                                                        <div class="analysis-actions">
                                                            <?php
                                                            // Ensure required data exists
                                                            $analysisData = [
                                                                'timestamp' => $snapshot['timestamp'] ?? '',
                                                                'image_url' => $snapshot['image_url'] ?? '',
                                                                'summary' => $snapshot['summary'] ?? '',
                                                                'hair_analysis' => [
                                                                    'health_score' => $snapshot['hair_analysis']['health_score'] ?? 0,
                                                                    'hydration' => $snapshot['hair_analysis']['hydration'] ?? 0,
                                                                    'elasticity' => $snapshot['hair_analysis']['elasticity'] ?? 0,
                                                                    'type' => $snapshot['hair_analysis']['type'] ?? '--',
                                                                    'curl_pattern' => $snapshot['hair_analysis']['curl_pattern'] ?? '--',
                                                                    'porosity' => $snapshot['hair_analysis']['porosity'] ?? '--'
                                                                ],
                                                                'recommendations' => $snapshot['recommendations'] ?? []
                                                            ];
                                                            ?>
                                                            <button class="analysis-action-btn action-view" data-analysis='<?php echo htmlspecialchars(json_encode($analysisData), ENT_QUOTES, 'UTF-8'); ?>'>
                                                                <i class="fas fa-search"></i> View Details
                                                            </button>
                                                            <button class="analysis-action-btn action-compare" onclick="openCompareModal()">
                                                                <i class="fas fa-exchange-alt"></i> Compare
                                                            </button>
                                                        </div>
                                                    </div>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                </div>
                            <?php else : ?>
                                <div class="myavana-no-analysis">
                                    <i class="fas fa-camera-retro"></i>
                                    <p style="margin-bottom: 24px;">No hair analysis data available</p>
                                    <?php if ($is_owner && $can_analyze) : ?>
                                        
                                        <button class="myavana-button-two" id="start-first-analysis">
                                        <div class="default-btn">
                                            <span> Create First Analysis</span>
                                        </div>
                                        <div class="hover-btn">
                                            <span>With Myavana Ai</span>
                                        </div>
                                        </button>

                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Analysis History Section -->
                        <div class="myavana-analysis-history">
                            <h3 class="myavana-history-title mb-4">Analysis History</h3>
                            <?php if (empty($analysis_history)): ?>
                                <div class="myavana-empty-history">
                                    <i class="fas fa-history"></i>
                                    <p>No analysis history yet</p>
                                </div>
                            <?php else: ?>
                                <div class="myavana-history-grid">
                                    <?php foreach ($analysis_history as $index => $analysis): ?>
                                        <div class="myavana-history-card">
                                            <div class="myavana-history-header">
                                                <h4 class="o-6"><?php echo esc_html(date('M j, Y', strtotime($analysis['date']))); ?></h4>
                                                <div class="myavana-history-score">
                                                    <?php echo esc_html($analysis['full_analysis']['hair_analysis']['health_score'] ?? '--'); ?>%
                                                    <span>Health</span>
                                                </div>
                                            </div>
                                            
                                            <div class="myavana-history-preview">
                                                <p><?php echo esc_html(wp_trim_words($analysis['summary'], 15)); ?></p>
                                            </div>
                                            
                                            <div class="myavana-history-meta">
                                                <span class="myavana-history-meta-item">
                                                    <i class="fas fa-curl"></i>
                                                    <?php echo esc_html($analysis['full_analysis']['hair_analysis']['curl_pattern'] ?? '--'); ?>
                                                </span>
                                                <span class="myavana-history-meta-item">
                                                    <i class="fas fa-tint"></i>
                                                    <?php echo esc_html($analysis['full_analysis']['hair_analysis']['hydration'] ?? '--'); ?>%
                                                </span>
                                            </div>
                                            
                                            <?php
                                            // Ensure required data exists for history view
                                            $historyAnalysisData = [
                                                'timestamp' => $analysis['date'],
                                                'image_url' => $analysis['full_analysis']['image_url'] ?? '',
                                                'summary' => $analysis['summary'] ?? '',
                                                'hair_analysis' => [
                                                    'health_score' => $analysis['full_analysis']['hair_analysis']['health_score'] ?? 0,
                                                    'hydration' => $analysis['full_analysis']['hair_analysis']['hydration'] ?? 0,
                                                    'elasticity' => $analysis['full_analysis']['hair_analysis']['elasticity'] ?? 0,
                                                    'type' => $analysis['full_analysis']['hair_analysis']['type'] ?? '--',
                                                    'curl_pattern' => $analysis['full_analysis']['hair_analysis']['curl_pattern'] ?? '--',
                                                    'porosity' => $analysis['full_analysis']['hair_analysis']['porosity'] ?? '--'
                                                ],
                                                'recommendations' => $analysis['full_analysis']['recommendations'] ?? []
                                            ];
                                            ?>
                                            <button class="myavana-history-details-btn" data-analysis='<?php echo htmlspecialchars(json_encode($historyAnalysisData), ENT_QUOTES, 'UTF-8'); ?>'>
                                                View Full Analysis <i class="fas fa-chevron-right"></i>
                                            </button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <!-- Run Hair Analysis Section -->
                    <div class="myavana-tryon hidden">
                        <div style="text-align: center; margin-bottom: 30px;">
                            <h3 class="myavana-title" style="margin-bottom: 16px;">
                                <i class="fas fa-magic" style="color: var(--myavana-coral); margin-right: 12px;"></i>
                                AI-Powered Hair Analysis
                            </h3>
                            <p style="font-family: 'Archivo', sans-serif; color: var(--myavana-blueberry); font-size: 16px; max-width: 600px; margin: 0 auto;">
                                Get personalized insights about your hair health, type, and care recommendations using advanced AI technology.
                            </p>
                        </div>

                        <div id="tryon-terms" class="mb-3">
                        <div class="myavana-checkbox-content my-3">
                                <div style="text-align: center; margin-bottom: 24px;">
                                    <i class="fas fa-shield-alt" style="font-size: 32px; color: var(--myavana-coral); margin-bottom: 16px;"></i>
                                    <h4 style="font-family: 'Archivo Black', sans-serif; color: var(--myavana-onyx); margin-bottom: 8px;">Privacy & Terms</h4>
                                </div>
                                <div class="checkbox-wrapper">
                                    <input id="terms-agree" type="checkbox">
                                    <label for="terms-agree"><div class="tick_mark"></div></label>
                                    <span class="myavana-checkbox-text">I agree to the <a href="/terms" target="_blank" style="color: var(--myavana-coral); font-weight: 600;">hair analysis terms of use</a> and understand my images are processed securely.</span>
                                </div>
                                <p class="myavana-checkbox-desc">
                                    <i class="fas fa-info-circle" style="margin-right: 8px; color: var(--myavana-coral);"></i>
                                    Your photos are analyzed using Myavana AI and are not stored permanently. Analysis results are saved to help track your hair journey progress.
                                </p>
                            </div>
                            <button id="terms-accept" class="myavana-button" style="font-size: 16px; padding: 16px 32px;">
                                <i class="fas fa-arrow-right" style="margin-right: 8px;"></i>
                                Continue to Analysis
                            </button>
                        </div>
                        <div id="tryon-interface" class="my-3" style="display: none;">
                            <div style="text-align: center; margin-bottom: 24px;">
                                <h4 style="font-family: 'Archivo Black', sans-serif; color: var(--myavana-onyx); margin-bottom: 16px;">Choose Your Photo Method</h4>
                                <p style="color: var(--myavana-blueberry); font-size: 14px;">For best results, ensure good lighting and that your hair is clearly visible</p>
                            </div>

                            <div id="image-source" class="mb-3" style="display: flex; gap: 20px; justify-content: center; flex-wrap: wrap;">
                                <button id="use-camera" class="myavana-button" style="min-width: 200px; padding: 20px 24px; display: flex; flex-direction: column; align-items: center; gap: 8px;">
                                    <i class="fas fa-camera" style="font-size: 24px; margin-bottom: 8px;"></i>
                                    <span style="font-weight: 600;">Use Camera</span>
                                    <small style="font-size: 12px; opacity: 0.8; text-transform: none;">Take a photo now</small>
                                </button>
                                <button id="upload-photo" class="myavana-button" style="min-width: 200px; padding: 20px 24px; display: flex; flex-direction: column; align-items: center; gap: 8px;">
                                    <i class="fas fa-upload" style="font-size: 24px; margin-bottom: 8px;"></i>
                                    <span style="font-weight: 600;">Upload Photo</span>
                                    <small style="font-size: 12px; opacity: 0.8; text-transform: none;">Choose from gallery</small>
                                </button>
                            </div>
                            <div id="camera-setup" style="display: none;">
                                <p class="mb-3">Position your face in the frame. Ensure good lighting!</p>
                                <div class="camera-container mb-3">
                                    <div id="camera-view"></div>
                                    <canvas id="camera-canvas" style="display: none;"></canvas>
                                    <div id="face-guide" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none;">
                                        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 70%; height: 80%; border: 3px dashed rgba(255,255,255,0.7); border-radius: 50%;"></div>
                                    </div>
                                </div>
                                <div class="camera-controls">
                                    <button id="start-camera" class="myavana-button">Start Camera</button>
                                    <button id="capture-photo" class="myavana-button" style="display: none;">Capture Photo</button>
                                    <button id="retake-photo" class="myavana-button" style="display: none;">Retake</button>
                                    <button id="cancel-camera" class="myavana-button cancel">Cancel</button>
                                </div>
                            </div>
                            <div id="upload-setup" style="display: none;">
                                <p class="mb-3">Upload a clear selfie for the best results.</p>
                                <div class="file-upload-container mb-3">
                                    <input type="file" id="photo-upload" accept="image/jpeg,image/png" />
                                </div>
                                <div class="upload-controls">
                                    <button id="cancel-upload" class="myavana-button cancel">Cancel</button>
                                </div>
                            </div>
                            <div id="tryon-preview" style="display: none;">
                                <h3 class="myavana-title mb-3">Preview Your Look</h3>
                                <div class="preview-container mb-3" style="position: relative;">
                                    <img id="preview-image" src="" style="max-width: 100%; border-radius: 8px;">
                                    <div id="hair-overlay" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none;"></div>
                                    <div id="loading-overlay" style="display: none; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); color: white; text-align: center; line-height: 100%;">Generating...</div>
                                </div>
                                <div class="preview-controls">
                                    <button id="generate-preview" class="myavana-button" style="background: linear-gradient(135deg, var(--myavana-coral) 0%, #d4956f 100%); font-size: 18px; padding: 18px 36px; box-shadow: 0 6px 20px rgba(231, 166, 144, 0.4); min-width: 240px;">
                                        <i class="fas fa-magic" style="margin-right: 12px;"></i>
                                        <span style="font-weight: 600;">Analyze My Hair</span>
                                        <div style="font-size: 12px; opacity: 0.9; text-transform: none; margin-top: 4px;">Powered by Myavana AI</div>
                                    </button>
                                    <button id="try-another" class="myavana-button" style="margin-top: 12px;">
                                        <i class="fas fa-camera-retro" style="margin-right: 8px;"></i>
                                        Try Another Photo
                                    </button>
                                    <button id="cancel-preview" class="myavana-button cancel" style="margin-top: 12px;">
                                        <i class="fas fa-times" style="margin-right: 8px;"></i>
                                        Cancel
                                    </button>
                                </div>
                            </div>
                            <!-- <div id="ai-suggestion" class="myavana-ai-tip mb-3" style="display: none;">
                                <?php
                                $ai = new Myavana_AI();
                                $suggestion = $ai->get_ai_tip('User is exploring hair colors and styles.');
                                echo esc_html($suggestion);
                                ?>
                            </div> -->
                        </div>
                    </div>
                </div>

                
            </div>

            <!-- Profile Tab -->
            <div class="sidebar-tab-content" id="profileTab">

                <!-- Enhanced Profile Header -->
                <div class="sidebar-profile-header">
                    <div class="sidebar-profile-cover">
                        <div class="sidebar-profile-avatar-wrapper">
                            <div class="sidebar-profile-avatar">
                                <?php echo bp_core_fetch_avatar(['item_id' => $user_id, 'type' => 'full']); ?>
                            </div>
                            <?php if ($is_owner) : ?>
                                <button class="sidebar-avatar-edit" onclick="openProfileEditOffcanvas()" title="Edit Profile">
                                    <svg viewBox="0 0 24 24" width="16" height="16">
                                        <path fill="currentColor" d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/>
                                    </svg>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="sidebar-profile-info">
                        <h2 class="sidebar-profile-name"><?php echo esc_html(get_userdata($user_id)->display_name); ?></h2>
                        <div class="sidebar-profile-handle">@<?php echo esc_html(get_userdata($user_id)->user_login); ?></div>
                        <?php if ($about_me) : ?>
                            <p class="sidebar-profile-bio"><?php echo esc_html($about_me); ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Stats Row -->
                    <div class="sidebar-profile-stats">
                        <div class="sidebar-stat-item">
                            <div class="sidebar-stat-value"><?php echo esc_html($total_entries); ?></div>
                            <div class="sidebar-stat-label">Entries</div>
                        </div>
                        <div class="sidebar-stat-item">
                            <div class="sidebar-stat-value"><?php echo esc_html($analytics_data['current_streak']); ?></div>
                            <div class="sidebar-stat-label">Streak</div>
                        </div>
                        <div class="sidebar-stat-item">
                            <div class="sidebar-stat-value"><?php echo esc_html(number_format($analytics_data['avg_health_score'], 1)); ?></div>
                            <div class="sidebar-stat-label">Health</div>
                        </div>
                    </div>
                    
                </div>

                <!-- GAMIFICATION STATS WIDGET -->
                <div class="sidebar-profile-card gamification-card">
                    <h4 class="sidebar-card-title">
                        <svg viewBox="0 0 24 24" width="18" height="18">
                            <path fill="currentColor" d="M12,2A2,2 0 0,1 14,4C14,4.74 13.6,5.39 13,5.73V7H14A7,7 0 0,1 21,14H22A1,1 0 0,1 23,15V18A1,1 0 0,1 22,19H21V20A2,2 0 0,1 19,22H5A2,2 0 0,1 3,20V19H2A1,1 0 0,1 1,18V15A1,1 0 0,1 2,14H3A7,7 0 0,1 10,7H11V5.73C10.4,5.39 10,4.74 10,4A2,2 0 0,1 12,2M7.5,13A2.5,2.5 0 0,0 5,15.5A2.5,2.5 0 0,0 7.5,18A2.5,2.5 0 0,0 10,15.5A2.5,2.5 0 0,0 7.5,13M16.5,13A2.5,2.5 0 0,0 14,15.5A2.5,2.5 0 0,0 16.5,18A2.5,2.5 0 0,0 19,15.5A2.5,2.5 0 0,0 16.5,13Z"/>
                        </svg>
                        Your Progress
                    </h4>
                    <div id="myavana-gamification-stats">
                        <!-- Dynamically loaded by gamification.js -->
                        <div class="gamification-loading">
                            <div class="loading-spinner"></div>
                            <p>Loading your stats...</p>
                        </div>
                    </div>
                    <?php if ($is_owner) : ?>
                        <button class="sidebar-checkin-btn" id="myavana-checkin-btn" style="margin-top: 16px; width: 100%; padding: 12px; background: var(--myavana-coral); color: white; border: none; border-radius: 8px; font-family: 'Archivo', sans-serif; font-weight: 600; cursor: pointer; transition: all 0.2s ease;">
                            <svg viewBox="0 0 24 24" width="18" height="18" style="vertical-align: middle; margin-right: 8px;">
                                <path fill="currentColor" d="M9,10H7V12H9V10M13,10H11V12H13V10M17,10H15V12H17V10M19,3H18V1H16V3H8V1H6V3H5C3.89,3 3,3.9 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19V5A2,2 0 0,0 19,3M19,19H5V8H19V19Z"/>
                            </svg>
                            Daily Check-In
                        </button>
                    <?php endif; ?>
                </div>

                <!-- Hair Profile Card -->
                <div class="sidebar-profile-card">
                    <h4 class="sidebar-card-title">
                        <svg viewBox="0 0 24 24" width="18" height="18">
                            <path fill="currentColor" d="M12,2A10,10 0 0,1 22,12A10,10 0 0,1 12,22A10,10 0 0,1 2,12A10,10 0 0,1 12,2M12,4A8,8 0 0,0 4,12A8,8 0 0,0 12,20A8,8 0 0,0 20,12A8,8 0 0,0 12,4Z"/>
                        </svg>
                        Hair Profile
                    </h4>
                    <div class="sidebar-profile-grid">
                        <div class="sidebar-detail-item">
                            <div class="sidebar-detail-label">Type</div>
                            <div class="sidebar-detail-value"><?php echo esc_html($profile->hair_type ?: '--'); ?></div>
                        </div>
                        <div class="sidebar-detail-item">
                            <div class="sidebar-detail-label">Porosity</div>
                            <div class="sidebar-detail-value"><?php echo esc_html($hair_porosity); ?></div>
                        </div>
                        <div class="sidebar-detail-item">
                            <div class="sidebar-detail-label">Length</div>
                            <div class="sidebar-detail-value"><?php echo esc_html($hair_length); ?></div>
                        </div>
                        <div class="sidebar-detail-item">
                            <div class="sidebar-detail-label">Journey Stage</div>
                            <div class="sidebar-detail-value"><?php echo esc_html($profile->hair_journey_stage ?: '--'); ?></div>
                        </div>
                    </div>
                </div>

                <!-- Goals Card -->
                <div class="sidebar-profile-card">
                    <h4 class="sidebar-card-title">
                        <svg viewBox="0 0 24 24" width="18" height="18">
                            <path fill="currentColor" d="M12,2L14.39,8.26L21,9.27L16.5,13.65L17.61,20.24L12,17.27L6.39,20.24L7.5,13.65L3,9.27L9.61,8.26L12,2Z"/>
                        </svg>
                        Hair Goals
                    </h4>
                    <div class="sidebar-goals-list">
                        <?php if (empty($hair_goals)): ?>
                            <div class="sidebar-empty-state">
                                <p>No hair goals set yet</p>
                                <?php if ($is_owner) : ?>
                                    <button class="sidebar-empty-cta" onclick="openProfileEditOffcanvas()">Add Your First Goal</button>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <?php foreach ($hair_goals as $index => $goal): ?>
                                <div class="sidebar-goal-chip">
                                    <svg viewBox="0 0 24 24" width="14" height="14">
                                        <path fill="currentColor" d="M21,7L9,19L3.5,13.5L4.91,12.09L9,16.17L19.59,5.59L21,7Z"/>
                                    </svg>
                                    <?php echo esc_html($goal['title']); ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Routine Card -->
                <div class="sidebar-profile-card">
                    <h4 class="sidebar-card-title">
                        <svg viewBox="0 0 24 24" width="18" height="18">
                            <path fill="currentColor" d="M12,20A8,8 0 0,1 4,12A8,8 0 0,1 12,4A8,8 0 0,1 20,12A8,8 0 0,1 12,20M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M12.5,7V12.25L17,14.92L16.25,16.15L11,13V7H12.5Z"/>
                        </svg>
                        Current Routine
                    </h4>
                    <div class="sidebar-routine-list">
                        <?php if (empty($current_routine)): ?>
                            <div class="sidebar-empty-state">
                                <p>No routine set yet</p>
                                <?php if ($is_owner) : ?>
                                    <button class="sidebar-empty-cta" onclick="openProfileEditOffcanvas()">Create Your Routine</button>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <?php foreach ($current_routine as $index => $step): ?>
                                <div class="sidebar-routine-item">
                                    <div class="sidebar-routine-frequency">
                                        <?php echo strtoupper(substr($step['frequency'], 0, 1)); ?>
                                    </div>
                                    <div class="sidebar-routine-details">
                                        <div class="sidebar-routine-name"><?php echo esc_html($step['name']); ?></div>
                                        <div class="sidebar-routine-freq"><?php echo esc_html($step['frequency']); ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Journey Milestones -->
                <div class="sidebar-profile-card">
                    <h4 class="sidebar-card-title">
                        <svg viewBox="0 0 24 24" width="18" height="18">
                            <path fill="currentColor" d="M12,6.5A2.5,2.5 0 0,1 14.5,9A2.5,2.5 0 0,1 12,11.5A2.5,2.5 0 0,1 9.5,9A2.5,2.5 0 0,1 12,6.5M12,2A7,7 0 0,1 19,9C19,14.25 12,22 12,22C12,22 5,14.25 5,9A7,7 0 0,1 12,2Z"/>
                        </svg>
                        Journey Milestones
                    </h4>
                    <div class="sidebar-milestones">
                        <div class="sidebar-milestone <?php echo $total_entries >= 1 ? 'completed' : ''; ?>">
                            <div class="milestone-icon">1st</div>
                            <div class="milestone-text">First Entry</div>
                        </div>
                        <div class="sidebar-milestone <?php echo $total_entries >= 10 ? 'completed' : ''; ?>">
                            <div class="milestone-icon">10</div>
                            <div class="milestone-text">10 Entries</div>
                        </div>
                        <div class="sidebar-milestone <?php echo $analytics_data['current_streak'] >= 7 ? 'completed' : ''; ?>">
                            <div class="milestone-icon">🔥</div>
                            <div class="milestone-text">7-Day Streak</div>
                        </div>
                        <div class="sidebar-milestone <?php echo $total_entries >= 30 ? 'completed' : ''; ?>">
                            <div class="milestone-icon">30</div>
                            <div class="milestone-text">30 Entries</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Analytics Tab -->
            <div class="sidebar-tab-content" id="analyticsTab">
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

                    <!-- Progress Ring -->
                    <div class="myavana-progress-ring-container">
                        <h4 style="font-family: 'Archivo', sans-serif; font-size: 0.875rem; font-weight: 600; text-transform: uppercase; color: var(--myavana-onyx); margin: 0 0 1rem 0;">Overall Progress</h4>
                        <div class="myavana-progress-ring">
                            <svg class="myavana-progress-ring-circle" width="120" height="120">
                                <circle class="myavana-progress-ring-bg" cx="60" cy="60" r="52"/>
                                <circle class="myavana-progress-ring-progress" cx="60" cy="60" r="52" id="progressRingCircle"
                                        style="stroke-dasharray: 326.73; stroke-dashoffset: 0;"/>
                            </svg>
                            <div class="myavana-progress-ring-text">
                                <div class="myavana-progress-ring-value" id="progressRingValue"><?php echo esc_html($analytics_data['progress_score']); ?>%</div>
                                <div class="myavana-progress-ring-label">Score</div>
                            </div>
                        </div>
                    </div>

                    <!-- Activity Heatmap -->
                    <div class="myavana-heatmap-container">
                        <h4 class="myavana-heatmap-title">Weekly Activity</h4>
                        <div class="myavana-heatmap-grid" id="activityHeatmap">
                            <!-- Generated by JavaScript -->
                        </div>
                    </div>

                    <!-- Charts Grid - Now Responsive -->
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
                                <canvas id="healthTrendChart"></canvas>
                            </div>
                        </div>

                        <div class="myavana-chart-card">
                            <div class="myavana-chart-header">
                                <h3>Entry Activity</h3>
                                <div class="myavana-chart-legend">
                                    <span class="myavana-legend-item">
                                        <span class="myavana-legend-color" style="background: var(--myavana-coral);"></span>
                                        Entries
                                    </span>
                                </div>
                            </div>
                            <div class="myavana-chart-container">
                                <canvas id="activityChart"></canvas>
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
            </div>
        </div>

    </div>
    <!-- /sidebar -->

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