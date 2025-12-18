<?php

function myavana_profile_shortcode($atts = []) {
    $atts = shortcode_atts(['user_id' => get_current_user_id()], $atts, 'myavana_profile');
    $user_id = intval($atts['user_id']);
    $current_user_id = get_current_user_id();
    $is_owner = $user_id === $current_user_id;

    // Get user data
    $user_data = get_userdata($user_id);
    $typeform_data = get_user_meta($user_id, 'myavana_typeform_data', true) ?: [];
    $hair_goals = get_user_meta($user_id, 'myavana_hair_goals_structured', true) ?: [];
    $about_me = get_user_meta($user_id, 'myavana_about_me', true) ?: ($typeform_data['hair_goals'] ?? '');
    $analysis_history = get_user_meta($user_id, 'myavana_hair_analysis_history', true) ?: [];
    $current_routine = get_user_meta($user_id, 'myavana_current_routine', true) ?: [];

    // Check analysis limit
    $analysis_limit = 30;
    $current_week = date('W');
    $weekly_analysis = array_filter($analysis_history, function($item) use ($current_week) {
        return date('W', strtotime($item['date'])) === $current_week;
    });
    $analysis_count = count($weekly_analysis);
    $can_analyze = $analysis_count < $analysis_limit;

    if (!is_user_logged_in()) {
        return '<p>Please <a href="' . esc_url(wp_login_url(get_permalink())) . '" class="text-blue-600 underline">Log in</a> to view profiles.</p>';
    }

    if (!get_userdata($user_id)) {
        return '<p class="text-red-600">Invalid user profile.</p>';
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'myavana_profiles';
    $profile = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE user_id = %d", $user_id));

    if (!$profile) {
        if ($is_owner) {
            $wpdb->insert(
                $table_name,
                [
                    'user_id' => $user_id,
                    'hair_journey_stage' => 'Not set',
                    'hair_health_rating' => 5,
                    'life_journey_stage' => 'Not set',
                    'birthday' => '',
                    'location' => '',
                    'hair_type' => '',
                    'hair_goals' => '',
                    'hair_analysis_snapshots' => ''
                ],
                ['%d', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s']
            );
            $profile = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE user_id = %d", $user_id));
            if (!$profile) {
                error_log('Myavana Hair Journey: Failed to create default profile for user ' . $user_id);
                return '<p class="text-red-600">Error loading profile. Please try again later.</p>';
            }
        } else {
            return '<p class="text-red-600">Profile not found.</p>';
        }
    }

    // Get hair journey entries
    $entries = new WP_Query([
        'post_type' => 'hair_journey_entry',
        'author' => $user_id,
        'posts_per_page' => -1,
        'orderby' => 'date',
        'order' => 'DESC'
    ]);

    // Calculate stats
    $total_entries = $entries->found_posts;
    $ratings = [];
    $chart_data = [];
    while ($entries->have_posts()) : $entries->the_post();
        $rating = get_post_meta(get_the_ID(), 'health_rating', true);
        if ($rating) {
            $chart_data[] = [
                'date' => get_the_date('Y-m-d'),
                'rating' => intval($rating)
            ];
            $ratings[] = intval($rating);
        }
    endwhile;
    wp_reset_postdata();

    $avg_rating = $ratings ? round(array_sum($ratings) / count($ratings), 1) : 0;
    $highest_rating = $ratings ? max($ratings) : 0;
    $trend = count($ratings) > 1 ? ($ratings[0] > $ratings[count($ratings) - 1] ? 'Improving' : ($ratings[0] < $ratings[count($ratings) - 1] ? 'Declining' : 'Stable')) : 'N/A';

    // Parse hair analysis snapshots
    $snapshots = $profile->hair_analysis_snapshots ? json_decode($profile->hair_analysis_snapshots, true) : [];
    usort($snapshots, function($a, $b) {
        return strtotime($b['timestamp']) <=> strtotime($a['timestamp']);
    });

    // Get recent entries for history section
    $recent_entries = new WP_Query([
        'post_type' => 'hair_journey_entry',
        'author' => $user_id,
        'posts_per_page' => 3,
        'orderby' => 'date',
        'order' => 'DESC'
    ]);
    wp_localize_script('myavana-script', 'myavanaAjax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('myavana_entry')
    ]);

    ob_start();
    ?>
    <link rel="stylesheet" href="https://unpkg.com/@sjmc11/tourguidejs/dist/css/tour.min.css">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/filepond@4.30.4/dist/filepond.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/filepond-plugin-image-preview@4.6.11/dist/filepond-plugin-image-preview.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
     <script src="https://cdn.jsdelivr.net/npm/@splidejs/splide@4.1.4/dist/js/splide.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/@splidejs/splide@4.1.4/dist/css/splide.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #e7a690;
            --secondary: #222323;
            --light: #F9F4FF;
            --dark: #222323;
            --text: #222323;
            --white: #FFFFFF;
            --shadow: 0 4px 12px rgba(108, 77, 138, 0.15);
            --shadow-lg: 0 8px 24px rgba(108, 77, 138, 0.2);
            --onyx: #222323;
            --stone: #f5f5f7;
            --white: #ffffff;
            --sand: #eeece1;
            --coral: #e7a690;
            --light-coral: #fce5d7;
            --blueberry: #4a4d68;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .filepond--root {
            background: var(--stone);
            border: 2px dashed var(--coral);
            border-radius: 8px;
        }

        .filepond--panel-root {
            background-color: transparent;
        }

        .filepond--item {
            border-radius: 8px;
            box-shadow: var(--shadow);
        }

        .error-message {
            background: #fee2e2;
            color: #dc2626;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .success-message {
            background: #d1fae5;
            color: #047857;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .select2-container--default .select2-selection--multiple {
            border: 1px solid #d1d5db;
            border-radius: 4px;
            background: var(--white);
        }

        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background: var(--light-coral);
            border: 1px solid var(--coral);
            color: var(--text);
        }
    </style>
    
    <div class="container">
        <!-- Profile Header -->
        <div class="profile-header">
            <?php echo bp_core_fetch_avatar(['item_id' => $user_id, 'type' => 'full', 'class' => 'profile-avatar']); ?>
            <h2 class="profile-name"><?php echo esc_html(get_userdata($user_id)->display_name); ?></h2>
            <div class="profile-handle">@<?php echo esc_html(get_userdata($user_id)->user_login); ?></div>
            
            <?php if ($about_me) : ?>
                <p class="profile-bio"><?php echo esc_html($about_me); ?></p>
            <?php endif; ?>
            
            <div class="profile-stats">
                <div class="stat-item">
                    <div class="stat-value"><?php echo esc_html($total_entries); ?></div>
                    <div class="stat-label">Entries</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?php echo esc_html($profile->hair_health_rating); ?>/5</div>
                    <div class="stat-label">Health</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?php echo esc_html($profile->hair_type ?: '--'); ?></div>
                    <div class="stat-label">Type</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?php echo esc_html($trend); ?></div>
                    <div class="stat-label">Trend</div>
                </div>
            </div>
            
            <?php if ($is_owner) : ?>
                <div class="profile-actions" >
                    <button id="edit-profile-btn" class="btn btn-primary">
                        <i class="fas fa-pencil-alt"></i> Edit Profile
                    </button>
                </div>
            <?php endif; ?>
        </div>
        
        <div id="error-message" class="error-message hidden"></div>
        <div id="success-message" class="success-message hidden"></div>
        <div id="ai-tip" class="ai-tip hidden"></div>

        <div id="profile-edit" class="profile-edit-form hidden mb-6">
            <form id="profile-form" method="post">  
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="hair_journey_stage" class="form-label">Hair Journey Stage</label>
                        <select id="hair_journey_stage" name="hair_journey_stage" class="form-select">
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
                    <div class="col-md-6 relative">
                        <label for="hair_type" class="form-label">Hair Type</label>
                        <select id="hair_type" name="hair_type" class="form-select">
                            <option value="" <?php selected($profile->hair_type, ''); ?>>Select Hair Type</option>
                            <option value="1A" <?php selected($profile->hair_type, '1A'); ?>>1A - Straight (Fine/Thin)</option>
                            <option value="1B" <?php selected($profile->hair_type, '1B'); ?>>1B - Straight (Medium)</option>
                            <option value="2A" <?php selected($profile->hair_type, '2A'); ?>>2A - Wavy (Fine/Thin)</option>
                            <option value="2B" <?php selected($profile->hair_type, '2B'); ?>>2B - Wavy (Medium)</option>
                            <option value="2C" <?php selected($profile->hair_type, '2C'); ?>>2C - Wavy (Coarse/Thick)</option>
                            <option value="3A" <?php selected($profile->hair_type, '3A'); ?>>3A - Curly (Loose curls)</option>
                            <option value="3B" <?php selected($profile->hair_type, '3B'); ?>>3B - Curly (Tight curls)</option>
                            <option value="3C" <?php selected($profile->hair_type, '3C'); ?>>3C - Curly (Corkscrews)</option>
                            <option value="4A" <?php selected($profile->hair_type, '4A'); ?>>4A - Coily (Soft coils)</option>
                            <option value="4B" <?php selected($profile->hair_type, '4B'); ?>>4B - Coily (Zig-zag pattern)</option>
                            <option value="4C" <?php selected($profile->hair_type, '4C'); ?>>4C - Coily (Tightest pattern)</option>
                        </select>
                        <div class="info-tooltip" id="hairTypeGuideButton">
                        <div class="icon">i</div>
                        <div class="tooltiptext">Need help determining your hair type?</div>
                        </div>

                    
                        
                    </div>

                    <!-- Hair Type Guide Modal -->
                    <div class="myavana-modal" style="display: none;" id="hairTypeGuide" >
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                 <span id="close-info-modal" class="close-modal">×</span>
                                <div class="modal-header">
                                    <h5 class="modal-title" id="hairTypeGuideLabel">Hair Type Guide</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <h6 class="start-subtitle">Type 1 - Straight</h6>
                                    <ul>
                                        <li><strong>1A</strong>: Very straight, fine, thin, soft, shiny, hard to hold curls</li>
                                        <li><strong>1B</strong>: Straight with more volume, medium texture</li>
                                    </ul>
                                    
                                    <h6 class="start-subtitle">Type 2 - Wavy</h6>
                                    <ul>
                                        <li><strong>2A</strong>: Loose, stretched S-shape waves, fine/thin</li>
                                        <li><strong>2B</strong>: More defined S-shape waves, medium thickness</li>
                                        <li><strong>2C</strong>: Thick, coarse waves with more defined S-shape</li>
                                    </ul>
                                    
                                    <h6 class="start-subtitle">Type 3 - Curly</h6>
                                    <ul>
                                        <li><strong>3A</strong>: Loose curls with large circumference (sharpie size)</li>
                                        <li><strong>3B</strong>: Tighter curls with medium circumference (pencil size)</li>
                                        <li><strong>3C</strong>: Tight corkscrew curls with small circumference (straw size)</li>
                                    </ul>
                                    
                                    <h6 class="start-subtitle">Type 4 - Coily/Kinky</h6>
                                    <ul>
                                        <li><strong>4A</strong>: Tight coils with S-pattern when stretched</li>
                                        <li><strong>4B</strong>: Z-shaped coils with less defined curl pattern</li>
                                        <li><strong>4C</strong>: Tightest coils with almost no defined pattern, most fragile</li>
                                    </ul>
                                    
                                    <p class="mt-3"><small>Note: Hair can have multiple patterns. Choose the type that represents most of your hair.</small></p>
                                </div>
                                <div class="modal-footer">
                                    <button id="cancel-info-modal" type="button" class="btn btn-secondary" >Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="birthday" class="form-label">Birthday</label>
                        <input type="date" id="birthday" name="birthday" value="<?php echo esc_attr($profile->birthday ?? ''); ?>" class="form-input">
                    </div>
                    <div class="col-md-6">
                        <label for="hair_health_rating" class="form-label">Hair Health Rating (1-5)</label>
                        <input type="number" id="hair_health_rating" name="hair_health_rating" min="1" max="5" step="1" value="<?php echo esc_attr($profile->hair_health_rating ?? 5); ?>" class="form-input" disabled required>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="life_journey_stage" class="form-label">Life Journey Stage</label>
                        <input type="text" id="life_journey_stage" name="life_journey_stage" value="<?php echo esc_attr($profile->life_journey_stage ?? ''); ?>" class="form-input" placeholder="e.g., New mother, Career change">
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="location" class="form-label">Location (City, State)</label>
                        <input type="text" id="location" name="location" value="<?php echo esc_attr($profile->location ?? ''); ?>" class="form-input" placeholder="e.g., Atlanta, GA">
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="about_me" class="form-label">About Me</label>
                        <textarea id="about_me" name="about_me" rows="4" class="form-textarea"><?php echo esc_textarea($about_me); ?></textarea>
                    </div>
                </div>
                <input type="hidden" name="user_id" value="<?php echo esc_attr($user_id); ?>">
                <input type="hidden" name="action" value="myavana_update_profile">
                <?php wp_nonce_field('myavana_profile', 'myavana_nonce'); ?>
                <div class="form-actions mt-4 flex space-x-2">
                    <button type="submit" class="myavana-button">Save Changes</button>
                    <button type="button" id="cancel-edit-btn" class="btn btn-secondary">Cancel</button>
                </div>
            </form>
        </div>

        <!-- About Me Section -->
        <div class="profile-section">
            <div class="section-header">
                <h2 class="section-title">About Me</h2>
                <?php if ($is_owner) : ?>
                    <div class="section-edit" data-section="about-me">
                        <i class="fas fa-pencil-alt"></i>
                        <span>Edit</span>
                    </div>
                <?php endif; ?>
            </div>
            <div class="about-me-section">
                <?php if ($is_owner): ?>
                    <textarea id="about-me-text" class="myavana-textarea w-full" placeholder="Tell us about yourself and your hair journey..."><?php echo esc_textarea($about_me); ?></textarea>
                    <div class="flex space-x-2 mt-2">
                        <button id="save-about-me" class="myavana-button">Save</button>
                        <button id="cancel-about-me" class="myavana-button secondary hidden">Cancel</button>
                    </div>
                <?php else: ?>
                    <p><?php echo esc_html($about_me ?: 'This user hasn\'t shared anything about themselves yet.'); ?></p>
                <?php endif; ?>
            </div>
        </div>

         <!-- Hair Analysis Section -->
        <div class="profile-section">
            <div class="section-header">
                <h2 class="section-title">Hair Analysis</h2>
                <?php if ($is_owner && $can_analyze) : ?>
                    <div class="section-edit" data-section="analysis">
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
                
                <!-- Custom Hair Analysis Slider -->
                <div class="myavana-hair-slider-container">
                    <?php if (!empty($snapshots)) : ?>
                        <div class="myavana-hair-slider">
                            <?php foreach ($snapshots as $snapshot) : ?>
                                <div class="myavana-hair-slide">
                                    <div class="myavana-hair-visual">
                                        <?php if ($snapshot['image_url'] ?? false) : ?>
                                            <img src="<?php echo esc_url($snapshot['image_url']); ?>" alt="Hair Analysis" class="myavana-hair-image">
                                        <?php else : ?>
                                            <div class="myavana-hair-placeholder">
                                                <i class="fas fa-camera"></i>
                                                <span>No image available</span>
                                            </div>
                                        <?php endif; ?>
                                        <div class="myavana-hair-date">
                                            <?php echo esc_html(date('M j, Y', strtotime($snapshot['timestamp']))); ?>
                                        </div>
                                    </div>
                                    
                                    <div class="myavana-hair-details">
                                        <div class="myavana-hair-type">
                                            <div class="myavana-hair-type-icon">
                                                <img src="<?php echo MYAVANA_URL; ?>assets/images/washing-hair-icon.png" alt="washing-hair-icon">
                                            </div>
                                            <div class="myavana-hair-type-info">
                                                <h3><?php echo esc_html($snapshot['hair_analysis']['curl_pattern'] ?? '--'); ?></h3>
                                                <p><?php echo esc_html($snapshot['hair_analysis']['type'] ?? 'Type not set'); ?></p>
                                            </div>
                                        </div>
                                        
                                    <div class="myavana-hair-metrics">
                                            <div class="myavana-hair-metric">
                                                <div class="myavana-metric-value"><?php echo esc_html($snapshot['hair_analysis']['health_score'] ?? '--'); ?>%</div>
                                                <div class="myavana-metric-label">Health</div>
                                                <div class="progress-bar">
                                                    <div class="progress-fill" style="width: <?php echo esc_attr(($snapshot['hair_analysis']['health_score'] ?? 0) . '%'); ?>"></div>
                                                </div>
                                            </div>
                                            <div class="myavana-hair-metric">
                                                <div class="myavana-metric-value"><?php echo esc_html($snapshot['hair_analysis']['hydration'] ?? '--'); ?>%</div>
                                                <div class="myavana-metric-label">Hydration</div>
                                                <div class="progress-bar">
                                                    <div class="progress-fill" style="width: <?php echo esc_attr(($snapshot['hair_analysis']['hydration'] ?? 0) . '%'); ?>"></div>
                                                </div>
                                            </div>
                                            <div class="myavana-hair-metric">
                                                <div class="myavana-metric-value"><?php echo esc_html($snapshot['hair_analysis']['elasticity'] ?? '--'); ?></div>
                                                <div class="myavana-metric-label">Elasticity</div>
                                                <div class="progress-bar">
                                                    <div class="progress-fill" style="width: <?php echo esc_attr(($snapshot['hair_analysis']['elasticity'] ?? 0) . '%'); ?>"></div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="myavana-hair-summary">
                                            <p><?php echo esc_html(wp_trim_words($snapshot['summary'] ?? '', 25)); ?></p>
                                        </div>
                                        
                                        <div class="myavana-hair-actions">
                                            <button class="myavana-hair-action-btn view-details" data-analysis='<?php echo htmlspecialchars(json_encode($snapshot), ENT_QUOTES, 'UTF-8'); ?>'>
                                                <i class="fas fa-search"></i> View Details
                                            </button>
                                            <button class="myavana-hair-action-btn compare">
                                                <i class="fas fa-exchange-alt"></i> Compare
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="myavana-slider-controls">
                            <button class="myavana-slider-prev"><i class="fas fa-chevron-left"></i></button>
                            <div class="myavana-slider-dots"></div>
                            <button class="myavana-slider-next"><i class="fas fa-chevron-right"></i></button>
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
                                    
                                    <button class="myavana-history-details-btn" data-analysis='<?php echo htmlspecialchars(json_encode($analysis), ENT_QUOTES, 'UTF-8'); ?>'>
                                        View Full Analysis <i class="fas fa-chevron-right"></i>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="myavana-tryon hidden">
                
                <div id="tryon-terms" class="mb-3">
                   <div class="myavana-checkbox-content my-3">
                        <div class="checkbox-wrapper">
                            <input id="terms-agree" type="checkbox">
                            <label for="terms-agree"><div class="tick_mark"></div></label>
                            <span class="myavana-checkbox-text">I agree to the <a href="/terms" target="_blank" style="color: var(--coral);">hair analysis terms of use</a>.</span>
                        </div>
                        <p class="myavana-checkbox-desc">Please agree to the terms to use the hair analysis feature.</p>
                    </div>
                    <button id="terms-accept" class="myavana-button">Continue</button>
                </div>
                <div id="tryon-interface" class="my-3" style="display: none;">
                    <div id="image-source" class="mb-3">
                        <button id="use-camera" class="myavana-button">Use Camera</button>
                        <button id="upload-photo" class="myavana-button">Upload Photo</button>
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
                            <button id="generate-preview" class="myavana-button">Analyse My Hair</button>
                            <button id="download-image" class="myavana-button" style="display: none;">Download Image</button>
                            <button id="try-another" class="myavana-button">Try Another</button>
                            <button id="cancel-preview" class="myavana-button cancel">Cancel</button>
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

        <!-- Hair Goals Section -->
        <div class="profile-section">
            <div class="section-header">
                <h2 class="section-title">My Hair Goals</h2>
                <?php if ($is_owner) : ?>
                    <div class="section-edit" data-section="hair-goals">
                        <i class="fas fa-plus"></i>
                        <span>Add Goal</span>
                    </div>
                <?php endif; ?>
            </div>
            
            <div id="hair-goals-list" class="goals-container">
                <?php if (empty($hair_goals)): ?>
                    <p class="empty-state">No hair goals set yet.</p>
                <?php else: ?>
                    <?php foreach ($hair_goals as $index => $goal): ?>
                        <div class="goal-card" data-index="<?php echo $index; ?>">
                            <h3 class="goal-title"><?php echo esc_html($goal['title']); ?></h3>
                            <p class="goal-description"><?php echo esc_html($goal['description']); ?></p>
                            <div class="goal-progress">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo esc_attr($goal['progress']); ?>%"></div>
                                </div>
                                <div class="progress-text">
                                    <span class="progress-percentage"><?php echo esc_attr($goal['progress']); ?>%</span>
                                    <span>Complete</span>
                                </div>
                                <?php if ($is_owner): ?>
                                    <div class="progress-update mt-2">
                                        <input type="range" class="goal-progress-slider" data-index="<?php echo $index; ?>" min="0" max="100" value="<?php echo esc_attr($goal['progress']); ?>">
                                        <button class="update-progress-btn myavana-button" data-index="<?php echo $index; ?>">Update Progress</button>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="goal-dates">
                                <span>Started: <?php echo esc_html(date('M j, Y', strtotime($goal['start_date']))); ?></span>
                                <span>Target: <?php echo esc_html(date('M j, Y', strtotime($goal['target_date']))); ?></span>
                            </div>
                            <div class="goal-updates">
                                <h4>Progress Updates</h4>
                                <?php if (!empty($goal['progress_text'])): ?>
                                    <ul>
                                        <?php foreach ($goal['progress_text'] as $update): ?>
                                            <li><?php echo esc_html($update['text']); ?> <small>(<?php echo esc_html(date('M j', strtotime($update['date']))); ?>)</small></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <p>No updates yet.</p>
                                <?php endif; ?>
                                <?php if ($is_owner): ?>
                                    <div class="add-update">
                                        <input type="text" class="update-text" placeholder="Add progress update...">
                                        <button class="add-update-btn" data-index="<?php echo $index; ?>">Add</button>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php if ($is_owner) : ?>
                                <div class="goal-actions">
                                    <button class="edit-goal" data-index="<?php echo $index; ?>">Edit</button>
                                    <button class="delete-goal" data-index="<?php echo $index; ?>">Delete</button>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
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

        <!-- Hair Health Chart -->
        <?php if (!empty($chart_data)) : ?>
            <div class="analytics-chart mb-6">
                <h2 class="section-title mb-3">Hair Health Over Time</h2>
                <div class="chart-summary mb-3 p-4 bg-white rounded-lg shadow">
                    <p class="mb-1"><strong>Average Rating:</strong> <?php echo esc_html($avg_rating); ?>/5</p>
                    <p class="mb-1"><strong>Highest Rating:</strong> <?php echo esc_html($highest_rating); ?>/5</p>
                    <p class="mb-1"><strong>Lowest Rating:</strong> <?php echo esc_html(min($ratings) ? min($ratings) : 0); ?>/5</p>
                </div>
                <canvas id="hairHealthChart" height="200"></canvas>
            </div>
        <?php endif; ?>

        <!-- Recent History Section -->
        <div class="profile-section">
            <div class="section-header">
                <h2 class="section-title">Recent Hair Journey</h2>
                <a href="<?php echo esc_url(home_url('/hair-journey')); ?>" class="section-edit">
                    <i class="fas fa-history"></i>
                    <span>View All</span>
                </a>
            </div>
            
            <div class="history-timeline">
                <?php if ($recent_entries->have_posts()) : 
                    while ($recent_entries->have_posts()) : 
                        $recent_entries->the_post();
                        $post_id = get_the_ID();
                        $date = get_the_date('F j, Y');
                        $title = get_the_title();
                        $content = get_the_content();
                        $thumbnail = get_the_post_thumbnail_url($post_id, 'large');
                        $rating = (int)get_post_meta($post_id, 'health_rating', true);
                        $entry_products = get_post_meta($post_id, 'products_used', true);
                        $notes = get_post_meta($post_id, 'stylist_notes', true);
                        $mood = get_post_meta($post_id, 'mood_demeanor', true);
                        $environment = get_post_meta($post_id, 'environment', true);
                        $ai_tags = get_post_meta($post_id, 'ai_tags', true);
                ?>
                    <div class="history-item">
                        <div class="history-dot">
                            <i class="fas fa-<?php echo $rating >= 4 ? 'smile' : ($rating >= 2 ? 'meh' : 'frown'); ?>"></i>
                        </div>
                        <div class="history-content">
                            <div class="history-date"><?php echo esc_html($date); ?></div>
                            <h3 class="history-title"><?php echo esc_html($title); ?></h3>
                            <p class="history-description"><?php echo esc_html(wp_trim_words($content, 20)); ?></p>
                            <?php if ($entry_products) : 
                                $products = explode(',', $entry_products); ?>
                                <div class="history-products">
                                    <?php foreach ($products as $product) : ?>
                                        <span class="product-badge"><?php echo esc_html(trim($product)); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; 
                    wp_reset_postdata();
                else : ?>
                    <div class="no-entries" style="text-align: center; padding: 30px;">
                        <p>No hair journey entries yet</p>
                        <?php if ($is_owner) : ?>
                            <a href="<?php echo esc_url(home_url('/add-hair-journey-entry')); ?>" class="btn btn-outline" style="margin-top: 15px;">
                                <i class="fas fa-plus"></i> Add First Entry
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        

        <!-- Enhanced Modal -->
        <div id="hair-goal-modal" class="myavana-modal" style="display: none;">
            <div class="modal-content">
                <span class="close-modal" onclick="closeModal()">×</span>
                <h2 class="myavana-title" style="margin-bottom: 24px; color: #222323;">Add New Hair Goal</h2>
                <form id="hair-goal-form" class="hair-goal-form-enhanced">
                    <input type="hidden" id="goal-index" value="">
                    
                    <div class="enhanced-form-group">
                        <div class="form-field-with-help">
                            <label for="goal-title" style="margin-bottom: 16px;">Goal Title</label>
                            <div class="help-icon-enhanced" style="margin-bottom: 16px;">
                                ?
                                <div class="help-tooltip-enhanced">
                                    Choose a specific goal like "Grow 4 inches" or "Reduce breakage by 50%"
                                </div>
                            </div>
                        </div>
                        <select id="goal-title" class="myavana-select-enhanced" required>
                            <option value="">Select a hair goal...</option>
                            <option value="Length Growth">Length Growth</option>
                            <option value="Thickness & Volume">Thickness & Volume</option>
                            <option value="Damage Repair">Damage Repair</option>
                            <option value="Moisture Balance">Moisture Balance</option>
                            <option value="Curl Definition">Curl Definition</option>
                            <option value="Scalp Health">Scalp Health</option>
                            <option value="Color Protection">Color Protection</option>
                            <option value="Styling Goals">Styling Goals</option>
                            <option value="Protective Styling">Protective Styling</option>
                            <option value="Custom Goal">Custom Goal</option>
                        </select>
                    </div>
                    
                    <div class="enhanced-form-group">
                        <div class="form-field-with-help">
                            <label for="goal-description" style="margin-bottom: 16px;">Description</label>
                            <div class="help-icon-enhanced" style="margin-bottom: 16px;">
                                ?
                                <div class="help-tooltip-enhanced">
                                    Describe your goal in detail, including current state and desired outcome
                                </div>
                            </div>
                        </div>
                        <textarea id="goal-description" class="myavana-textarea-enhanced" placeholder="Describe your hair goal in detail..." required></textarea>
                    </div>
                    
                    <div class="enhanced-form-group">
                        <div class="form-field-with-help">
                            <label for="goal-progress" style="margin-bottom: 16px;">Progress</label>
                            <div class="help-icon-enhanced" style="margin-bottom: 16px;">
                                ?
                                <div class="help-tooltip-enhanced">
                                    Set your current progress towards this goal (0% = just starting, 100% = goal achieved)
                                </div>
                            </div>
                        </div>
                        <div class="progress-container-enhanced">
                            <input type="range" id="goal-progress" min="0" max="100" value="0" class="myavana-range-enhanced">
                            <div class="progress-bar-bg-enhanced" style="margin-bottom: 16px;">
                                <div class="progress-bar-fill-enhanced" id="progress-bar-fill" style="width: 0%"></div>
                            </div>
                            <div class="progress-display-enhanced" style="margin-top: 24px;">
                                <span>0%</span>
                                <span id="progress-value-enhanced" class="progress-value-enhanced">0%</span>
                                <span>100%</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="enhanced-form-group">
                        <div class="form-field-with-help">
                            <label for="goal-start-date" style="margin-bottom: 16px;">Start Date</label>
                            <div class="help-icon-enhanced" style="margin-bottom: 16px;">
                                ?
                                <div class="help-tooltip-enhanced">
                                    When did you start working on this goal?
                                </div>
                            </div>
                        </div>
                        <input type="date" id="goal-start-date" class="myavana-date-enhanced" required>
                    </div>
                    
                    <div class="enhanced-form-group">
                        <div class="form-field-with-help">
                            <label for="goal-target-date" style="margin-bottom: 16px;">Target Date</label>
                            <div class="help-icon-enhanced" style="margin-bottom: 16px;">
                                ?
                                <div class="help-tooltip-enhanced">
                                    When do you want to achieve this goal?
                                </div>
                            </div>
                        </div>
                        <input type="date" id="goal-target-date" class="myavana-date-enhanced" required>
                    </div>
                    
                    <button type="submit" class="myavana-button-enhanced">Save Hair Goal</button>
                </form>
            </div>
        </div>

        <script>

            // Enhanced progress slider functionality
            const progressSlider = document.getElementById('goal-progress');
            const progressValue = document.getElementById('progress-value-enhanced');
            const progressBarFill = document.getElementById('progress-bar-fill');

            progressSlider.addEventListener('input', function() {
                const value = this.value;
                progressValue.textContent = value + '%';
                progressBarFill.style.width = value + '%';
            });

        

            // Set default dates
            document.addEventListener('DOMContentLoaded', function() {
                const today = new Date().toISOString().split('T')[0];
                document.getElementById('goal-start-date').value = today;
                
                const futureDate = new Date();
                futureDate.setMonth(futureDate.getMonth() + 3);
                document.getElementById('goal-target-date').value = futureDate.toISOString().split('T')[0];
            });
        </script>

        <div id="routine-step-modal" class="myavana-modal" style="display: none;">
            <div class="modal-content">
                <span class="close-modal">×</span>
                <h3 id="step-modal-title">Add Routine Step</h3>
                <form id="routine-step-form">
                    <input type="hidden" id="step-index" value="">
                    <div class="form-group">
                        <label for="step-name">Step Name</label>
                        <select id="step-name" class="myavana-select-enhanced" required>
                            <option value="">Select step type</option>
                            <option value="Cleanse">Cleanse</option>
                            <option value="Condition">Condition</option>
                            <option value="Moisturize">Moisturize</option>
                            <option value="Style">Style</option>
                            <option value="Treat">Treat</option>
                            <option value="Protect">Protect</option>
                            <option value="Maintain">Maintain</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="step-frequency">Frequency</label>
                        <select id="step-frequency" class="myavana-select-enhanced" required>
                            <option value="">Select frequency</option>
                            <option value="Daily">Daily</option>
                            <option value="Weekly">Weekly</option>
                            <option value="Bi-Weekly">Bi-Weekly</option>
                            <option value="Monthly">Monthly</option>
                            <option value="As Needed">As Needed</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="step-time">Time of Day</label>
                        <select id="step-time" class="myavana-select-enhanced">
                            <option value="">Any time</option>
                            <option value="Morning">Morning</option>
                            <option value="Evening">Evening</option>
                            <option value="Night">Night</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="step-description">Description (Optional)</label>
                        <textarea id="step-description" class="myavana-textarea"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="step-products">Products Used</label>
                        <select id="step-products" class="myavana-select-enhanced" multiple="multiple">
                            <option value="Shampoo">Shampoo</option>
                            <option value="Cleansing Cream">Cleansing Cream</option>
                            <option value="Conditioner">Conditioner</option>
                            <option value="Deep Conditioner">Deep Conditioner</option>
                            <option value="Leave-In Conditioner">Leave-In Conditioner</option>
                            <option value="Hair Oil">Hair Oil</option>
                            <option value="Scalp Treatment">Scalp Treatment</option>
                            <option value="Heat Protectant">Heat Protectant</option>
                            <option value="Styling Cream">Styling Cream</option>
                            <option value="Gel">Gel</option>
                            <option value="Mousse">Mousse</option>
                            <option value="Satin Bonnet">Satin Bonnet</option>
                        </select>
                    </div>
                    <button type="submit" class="myavana-button">Save Step</button>
                </form>
            </div>
        </div>

        <div id="analysis-detail-modal" class="myavana-modal" style="display: none;">
            <div class="modal-content wide">
                <span class="close-modal">×</span>
                <div id="analysis-detail-content"></div>
            </div>
        </div>

        <!-- Hair Care Tip -->
        <div class="hair-tip mb-6">
            <h3 class="text-xl font-bold mb-3">Hair Care Tip</h3>
            <p id="random-tip" class="mb-3"></p>
            <button id="new-tip-btn" class="btn-primary-outline">Get Another Tip</button>
        </div>

       

        <!-- Analysis Detail Modal -->
        <div class="myavana-analysis-modal" style="display: none;">
            <div class="myavana-modal-content">
                <span class="myavana-modal-close">&times;</span>
                <div class="myavana-modal-header">
                    <h3 class="myavana-modal-title">Hair Analysis Details</h3>
                    <div class="myavana-modal-date" id="analysis-modal-date"></div>
                </div>
                
                <div class="myavana-modal-body">
                    <div class="myavana-modal-section">
                        <h4 class="myavana-section-title">Hair Characteristics</h4>
                        <div class="myavana-detail-grid">
                            <div class="summary-card">
                                <span class="myavana-detail-label">Type</span>
                                <span class="myavana-detail-value" id="detail-type"></span>
                            </div>
                            <div class="summary-card">
                                <span class="myavana-detail-label">Curl Pattern</span>
                                <span class="myavana-detail-value" id="detail-curl-pattern"></span>
                            </div>
                            <div class="summary-card">
                                <span class="myavana-detail-label">Length</span>
                                <span class="myavana-detail-value" id="detail-length"></span>
                            </div>
                            <div class="summary-card">
                                <span class="myavana-detail-label">Texture</span>
                                <span class="myavana-detail-value" id="detail-texture"></span>
                            </div>
                            <div class="summary-card">
                                <span class="myavana-detail-label">Density</span>
                                <span class="myavana-detail-value" id="detail-density"></span>
                            </div>
                            <div class="summary-card">
                                <span class="myavana-detail-label">Porosity</span>
                                <span class="myavana-detail-value" id="detail-porosity"></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="myavana-modal-section">
                        <h4 class="myavana-section-title">Health Metrics</h4>
                        <div class="myavana-metrics-grid">
                            <div class="myavana-metric-item">
                                <div class="myavana-metric-header">
                                    <span class="myavana-metric-name">Health Score</span>
                                    <span class="myavana-metric-value" id="metric-health-score"></span>
                                </div>
                                <div class="myavana-metric-bar">
                                    <div class="myavana-metric-fill" id="fill-health-score"></div>
                                </div>
                            </div>
                            <div class="myavana-metric-item">
                                <div class="myavana-metric-header">
                                    <span class="myavana-metric-name">Hydration</span>
                                    <span class="myavana-metric-value" id="metric-hydration"></span>
                                </div>
                                <div class="myavana-metric-bar">
                                    <div class="myavana-metric-fill" id="fill-hydration"></div>
                                </div>
                            </div>
                            <div class="myavana-metric-item">
                                <div class="myavana-metric-header">
                                    <span class="myavana-metric-name">Elasticity</span>
                                    <span class="myavana-metric-value" id="metric-elasticity"></span>
                                </div>
                                <div class="myavana-metric-bar">
                                    <div class="myavana-metric-fill" id="fill-elasticity"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="myavana-modal-section">
                        <h4 class="myavana-section-title">Recommendations</h4>
                        <ul class="myavana-recommendations-list" id="recommendations-list"></ul>
                    </div>
                    
                    <div class="myavana-modal-section">
                        <h4 class="myavana-section-title">Suggested Products</h4>
                        <div class="myavana-products-grid" id="products-grid"></div>
                    </div>
                    
                    <div class="myavana-modal-section">
                        <h4 class="myavana-section-title">Full Analysis Summary</h4>
                        <div class="myavana-full-summary" id="full-summary"></div>
                    </div>
                </div>
            </div>
        </div>



        <script>
            jQuery(document).ready(function($) {
                // Initialize custom slider
                const slider = $('.myavana-hair-slider');
                const slides = $('.myavana-hair-slide');
                const dotsContainer = $('.myavana-slider-dots');
                let currentSlide = 0;
                
                // Create dots
                slides.each(function(index) {
                    dotsContainer.append(`<div class="myavana-slider-dot ${index === 0 ? 'active' : ''}" data-index="${index}"></div>`);
                });
                
                // Update slider position
                function updateSlider() {
                    const slideWidth = slides.eq(0).outerWidth(true);
                    slider.css('transform', `translateX(-${currentSlide * slideWidth}px)`);
                    $('.myavana-slider-dot').removeClass('active').eq(currentSlide).addClass('active');
                }
                
                // Next slide
                $('.myavana-slider-next').click(function() {
                    if (currentSlide < slides.length - 1) {
                        currentSlide++;
                        updateSlider();
                    }
                });
                
                // Previous slide
                $('.myavana-slider-prev').click(function() {
                    if (currentSlide > 0) {
                        currentSlide--;
                        updateSlider();
                    }
                });
                // Progress Slider and Update
                $(document).on('input', '.goal-progress-slider', function() {
                    const index = $(this).data('index');
                    const value = $(this).val();
                    $(this).closest('.goal-progress').find('.progress-fill').css('width', value + '%');
                    $(this).closest('.goal-progress').find('.progress-percentage').text(value + '%');
                });

                $(document).on('click', '.update-progress-btn', function() {
                    const index = $(this).data('index');
                    const progress = $(this).closest('.goal-progress').find('.goal-progress-slider').val();
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'myavana_update_goal_progress',
                            index: index,
                            progress: progress,
                            nonce: '<?php echo wp_create_nonce('myavana_profile_nonce'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                $('#success-message').text('Goal progress updated successfully!').removeClass('hidden').fadeIn(300);
                                setTimeout(() => $('#success-message').fadeOut(300), 5000);
                            } else {
                                $('#error-message').text('Error: ' + response.data).removeClass('hidden').fadeIn(300);
                                setTimeout(() => $('#error-message').fadeOut(300), 5000);
                            }
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            $('#error-message').text('Error: Failed to connect to server. Please try again.').removeClass('hidden').fadeIn(300);
                            setTimeout(() => $('#error-message').fadeOut(300), 5000);
                        }
                    });
                });
                
                // Dot navigation
                $('.myavana-slider-dot').click(function() {
                    currentSlide = $(this).data('index');
                    updateSlider();
                });
                
                // Keyboard navigation
                $(document).keydown(function(e) {
                    if ($('.myavana-analysis-modal').is(':visible')) return;
                    
                    if (e.key === 'ArrowRight' && currentSlide < slides.length - 1) {
                        currentSlide++;
                        updateSlider();
                    } else if (e.key === 'ArrowLeft' && currentSlide > 0) {
                        currentSlide--;
                        updateSlider();
                    }
                });
                
                // Initialize slider
                updateSlider();
                
                // Analysis Modal
                const modal = $('.myavana-analysis-modal');
                
                // Open modal with analysis data
                function openAnalysisModal(analysisData) {
                    // Parse the data if it's a string
                    const analysis = typeof analysisData === 'string' ? JSON.parse(analysisData) : analysisData;
                    
                    // Set basic info
                    $('#analysis-modal-date').text(new Date(analysis.timestamp || analysis.date).toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                    }));
                    
                    // Set hair characteristics
                    const hair = analysis.hair_analysis || (analysis.full_analysis ? analysis.full_analysis.hair_analysis : {});
                    $('#detail-type').text(hair.type || '--');
                    $('#detail-curl-pattern').text(hair.curl_pattern || '--');
                    $('#detail-length').text(hair.length || '--');
                    $('#detail-texture').text(hair.texture || '--');
                    $('#detail-density').text(hair.density || '--');
                    $('#detail-porosity').text(hair.porosity || '--');
                    
                    // Set metrics
                    $('#metric-health-score').text((hair.health_score || '--') + '%');
                    $('#metric-hydration').text((hair.hydration || '--') + '%');
                    $('#metric-elasticity').text(hair.elasticity || '--');
                    
                    // Animate metric bars
                    setTimeout(() => {
                        $('#fill-health-score').css('width', (hair.health_score || 0) + '%');
                        $('#fill-hydration').css('width', (hair.hydration || 0) );
                        $('#fill-elasticity').css('width', (hair.elasticity ? hair.elasticity.replace(/\D/g, '') : 0) + '%');
                    }, 100);
                    
                    // Set recommendations
                    const recList = $('#recommendations-list').empty();
                    const recommendations = analysis.recommendations || (analysis.full_analysis ? analysis.full_analysis.recommendations : []);
                    if (recommendations && recommendations.length) {
                        recommendations.forEach(rec => {
                            recList.append(`<li>${rec}</li>`);
                        });
                    } else {
                        recList.append('<li>No specific recommendations available.</li>');
                    }
                    
                    // Set products
                    const productsGrid = $('#products-grid').empty();
                    const products = analysis.products || (analysis.full_analysis ? analysis.full_analysis.products : []);
                    if (products && products.length) {
                        products.forEach(prod => {
                            productsGrid.append(`
                                <div class="myavana-product-card">
                                    <div class="myavana-product-name">${prod.name || 'Unnamed Product'}</div>
                                    <div class="myavana-product-match">
                                        <span class="myavana-product-match-value">${prod.match || 0}% match</span>
                                    </div>
                                    <div class="myavana-product-match-bar">
                                        <div class="myavana-product-match-fill" style="width: ${prod.match || 0}%"></div>
                                    </div>
                                </div>
                            `);
                        });
                    } else {
                        productsGrid.append('<p>No product recommendations available.</p>');
                    }
                    
                    // Set summary
                    $('#full-summary').text(analysis.full_context || analysis.summary || 'No detailed summary available.');
                    
                    // Show modal
                    modal.addClass('active');
                    $('.myavana-analysis-modal').show();
                    $('body').css('overflow', 'hidden');
                }
                
                // Close modal
                $('.myavana-modal-close').click(function() {
                    modal.removeClass('active');
                    $('body').css('overflow', '');
                });
                
                // Click outside modal to close
                modal.click(function(e) {
                    if ($(e.target).hasClass('myavana-analysis-modal')) {
                        modal.removeClass('active');
                        $('body').css('overflow', '');
                    }
                });
                
                // View details buttons
                $('.view-details, .myavana-history-details-btn').click(function() {
                    const analysisData = $(this).data('analysis');
                    openAnalysisModal(analysisData);
                });
                
                // Initialize metric bars on slide change
                function initMetricBars() {
                    $('.myavana-metric-fill').each(function() {
                        const width = $(this).data('width') || '0%';
                        $(this).css('width', width);
                    });
                }
                
                // Initialize on load
                initMetricBars();
            });
        </script>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/filepond@4.30.4/dist/filepond.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/filepond-plugin-image-preview@4.6.11/dist/filepond-plugin-image-preview.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>


    <script>
        jQuery(document).ready(function($) {
            // Register FilePond plugins
            FilePond.registerPlugin(FilePondPluginImagePreview);
            // const tg = new TourGuideClient({} : TourGuideOptions)

            // Initialize FilePond for photo upload
            const uploadInput = $('#photo-upload-analysis')[0];
            const pond = FilePond.create(uploadInput, {
                acceptedFileTypes: ['image/jpeg', 'image/png'],
                maxFileSize: '10MB',
                allowMultiple: false,
                instantUpload: false,
                imagePreviewHeight: 200,
                stylePanelLayout: 'compact',
                styleLoadIndicatorPosition: 'center bottom',
                styleButtonRemoveItemPosition: 'center bottom',
                onaddfile: (error, file) => {
                    if (!error) {
                        $('#upload-setup-analysis').show();
                        $('.analysis-interface').hide();
                    }
                },
                onremovefile: () => {
                    $('#upload-setup-analysis').hide();
                    $('.analysis-interface').show();
                }
            });

            // Initialize Select2 for products
            $('#step-products').select2({
                placeholder: "Select products",
                allowClear: true,
                width: '100%',
                dropdownCssClass: 'myavana-select2-dropdown'
            });

            // Toggle edit form
            $('#edit-profile-btn').on('click', function(e) {
                e.preventDefault();
                $('.profile-header').fadeOut(300, function() {
                    $('#profile-edit').removeClass('hidden').fadeIn(300);
                    window.scrollTo(0, 0);
                });
            });

            $('#cancel-edit-btn').on('click', function(e) {
                e.preventDefault();
                $('#profile-edit').fadeOut(300, function() {
                    $('.profile-header').removeClass('hidden').fadeIn(300);
                });
            });

            // Section edit toggles
            $('.section-edit').on('click', function() {
                const section = $(this).data('section');

                if (section === 'analysis') {
                    const analysisButton = $(this);
                    const isActive = analysisButton.hasClass('cancel-active');

                    if (!isActive) {
                        // Switch to active/cancel state
                        $('.hair-analysis-container').addClass('hidden');
                        $('.myavana-tryon').removeClass('hidden');
                        analysisButton.addClass('cancel-active');
                        analysisButton.find('i').removeClass('fa-plus').addClass('fa-times'); // switch to cancel icon
                        analysisButton.find('span').text('Cancel Analysis');
                    } else {
                        // Cancel: revert UI + button
                        $('.hair-analysis-container').removeClass('hidden');
                        $('.myavana-tryon').addClass('hidden');
                        analysisButton.removeClass('cancel-active');
                        analysisButton.find('i').removeClass('fa-times').addClass('fa-plus'); // back to add icon
                        analysisButton.find('span').text('Add Analysis');
                    }
                } else if (section === 'about-me') {
                    $('#about-me-text').prop('disabled', false);
                    $('#save-about-me, #cancel-about-me').removeClass('hidden');
                } else if (section === 'hair-goals') {
                    $('#hair-goal-modal').show();
                } else if (section === 'routine') {
                    $('#routine-step-modal').show();
                }
            });
            $('#hairTypeGuideButton').on('click', function() {
                $('#youzify-profile-navmenu').hide();
                $('#hairTypeGuide').show();
            });
            $('#close-info-modal').on('click', function() {
                $('#youzify-profile-navmenu').show();
                $('#hairTypeGuide').hide();
            });
            $('#cancel-info-modal').on('click', function() {
                $('#youzify-profile-navmenu').show();
                $('#hairTypeGuide').hide();
            });

            $('#cancel-about-me').on('click', function() {
                $('#about-me-text').prop('disabled', true);
                $('#save-about-me, #cancel-about-me').addClass('hidden');
                $('#about-me-text').val(<?php echo json_encode($about_me); ?>);
            });

            // Form submission
            $('#profile-form').on('submit', function(e) {
                e.preventDefault();
                const formData = $(this).serialize();
                console.log('Submitting form data:', formData); // Debug: Log form data

                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        console.log('Profile update response:', response); // Debug: Log response
                        if (response.success) {
                            $('#profile-edit').fadeOut(300, function() {
                                $('.profile-header').removeClass('hidden').fadeIn(300);
                                $('#success-message').text(response.data.message).removeClass('hidden').fadeIn(300);
                                $('#ai-tip').html(response.data.tip).removeClass('hidden').fadeIn(300);
                                setTimeout(() => {
                                    $('#success-message, #ai-tip').fadeOut(300);
                                    location.reload();
                                }, 5000);
                            });
                        } else {
                            $('#error-message').text(response.data || 'Unknown error occurred.').removeClass('hidden').fadeIn(300);
                            setTimeout(() => $('#error-message').fadeOut(300), 5000);
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.error('Profile update error:', textStatus, errorThrown); // Debug: Log errors
                        $('#error-message').text('Error: Failed to connect to server. Please try again.').removeClass('hidden').fadeIn(300);
                        setTimeout(() => $('#error-message').fadeOut(300), 5000);
                    }
                });
            });

            // Save About Me
            $('#save-about-me').click(function() {
                const aboutMe = $('#about-me-text').val();
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'myavana_save_about_me',
                        about_me: aboutMe,
                        nonce: '<?php echo wp_create_nonce('myavana_profile_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#success-message').text('About me updated successfully!').removeClass('hidden').fadeIn(300);
                            $('#about-me-text').prop('disabled', true);
                            $('#save-about-me, #cancel-about-me').addClass('hidden');
                            setTimeout(() => $('#success-message').fadeOut(300), 5000);
                        } else {
                            $('#error-message').text('Error: ' + response.data).removeClass('hidden').fadeIn(300);
                            setTimeout(() => $('#error-message').fadeOut(300), 5000);
                        }
                    }
                });
            });

            // Initialize chart
            <?php if (!empty($chart_data)) : ?>
                const ctx = document.getElementById('hairHealthChart').getContext('2d');
                const chartData = <?php echo json_encode($chart_data, JSON_UNESCAPED_SLASHES); ?>;
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: chartData.map(entry => entry.date),
                        datasets: [{
                            label: 'Hair Health Rating',
                            data: chartData.map(entry => entry.rating),
                            borderColor: '#e7a690',
                            backgroundColor: 'rgba(231, 166, 144, 0.2)',
                            fill: true,
                            tension: 0.4,
                            pointBackgroundColor: '#4a4d68',
                            pointBorderColor: '#ffffff',
                            pointHoverBackgroundColor: '#e7a690',
                            pointHoverBorderColor: '#222323'
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: { labels: { font: { family: 'Avenir Next', size: 14 }, color: '#222323' } },
                            tooltip: { bodyFont: { family: 'Avenir Next' }, titleFont: { family: 'Avenir Next', weight: 'bold' } }
                        },
                        scales: {
                            y: { beginAtZero: true, max: 5, ticks: { font: { family: 'Avenir Next', size: 12 }, color: '#222323' }, grid: { color: '#eeece1' } },
                            x: { ticks: { font: { family: 'Avenir Next', size: 12 }, color: '#222323' }, grid: { color: '#eeece1' } }
                        }
                    }
                });
            <?php endif; ?>

            // Hair Goals Management
            $('#add-hair-goal').click(function() {
                $('#goal-modal-title').text('Add New Hair Goal');
                $('#goal-index').val('');
                $('#hair-goal-form')[0].reset();
                $('#hair-goal-modal').show();
            });

            $(document).on('click', '.edit-goal', function() {
                const index = $(this).data('index');
                const goal = <?php echo json_encode($hair_goals); ?>[index];
                
                $('#goal-modal-title').text('Edit Hair Goal');
                $('#goal-index').val(index);
                $('#goal-title').val(goal.title);
                $('#goal-description').val(goal.description);
                $('#goal-progress').val(goal.progress);
                $('#progress-value').text(goal.progress + '%');
                $('#goal-start-date').val(goal.start_date);
                $('#goal-target-date').val(goal.target_date);
                
                $('#hair-goal-modal').show();
            });

            $('#goal-progress').on('input', function() {
                $('#progress-value').text($(this).val() + '%');
            });

            $('#hair-goal-form').submit(function(e) {
                e.preventDefault();
                
                const index = $('#goal-index').val();
                const goal = {
                    title: $('#goal-title').val(),
                    description: $('#goal-description').val(),
                    progress: $('#goal-progress').val(),
                    start_date: $('#goal-start-date').val(),
                    target_date: $('#goal-target-date').val(),
                    progress_text: index !== '' ? (<?php echo json_encode($hair_goals); ?>[index]?.progress_text || []) : []
                };
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'myavana_save_hair_goal',
                        goal: JSON.stringify(goal),
                        index: index,
                        nonce: '<?php echo wp_create_nonce('myavana_profile_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            $('#error-message').text('Error: ' + response.data).removeClass('hidden').fadeIn(300);
                            setTimeout(() => $('#error-message').fadeOut(300), 5000);
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        $('#error-message').text('Error: Failed to connect to server. Please try again.').removeClass('hidden').fadeIn(300);
                        setTimeout(() => $('#error-message').fadeOut(300), 5000);
                    }
                });
            });

            $(document).on('click', '.add-update-btn', function() {
                const index = $(this).data('index');
                const updateText = $(this).siblings('.update-text').val();
                
                if (updateText.trim() === '') return;
                
                const update = {
                    text: updateText,
                    date: new Date().toISOString().split('T')[0]
                };
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'myavana_add_goal_update',
                        index: index,
                        update: JSON.stringify(update),
                        nonce: '<?php echo wp_create_nonce('myavana_profile_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            $('#error-message').text('Error: ' + response.data).removeClass('hidden').fadeIn(300);
                            setTimeout(() => $('#error-message').fadeOut(300), 5000);
                        }
                    }
                });
            });

            // Hair Analysis
            $(document).on('click', '.editBtn', function() {
                const index = $(this).data('index');
                const step = <?php echo json_encode($current_routine); ?>[index];
                
                $('#step-modal-title').text('Edit Routine Step');
                $('#step-index').val(index);
                $('#step-name').val(step.name);
                $('#step-frequency').val(step.frequency);
                $('#step-time').val(step.time_of_day);
                $('#step-description').val(step.description);
                $('#step-products').val(step.products).trigger('change');
                
                $('#routine-step-modal').show();
            });
            let cameraStreamAnalysis = null;
            $('#use-camera-analysis, #start-first-analysis').click(function() {
                $('.myavana-tryon').removeClass('hidden');
                $('.hair-analysis-container').addClass('hidden');
    
            });

            // Routine Management
            $('#add-routine-step').click(function() {
                $('#step-modal-title').text('Add Routine Step');
                $('#step-index').val('');
                $('#routine-step-form')[0].reset();
                $('#step-products').val(null).trigger('change');
                $('#routine-step-modal').show();
            });

            $(document).on('click', '.edit-step', function() {
                const index = $(this).data('index');
                const step = <?php echo json_encode($current_routine); ?>[index];
                
                $('#step-modal-title').text('Edit Routine Step');
                $('#step-index').val(index);
                $('#step-name').val(step.name);
                $('#step-frequency').val(step.frequency);
                $('#step-time').val(step.time_of_day);
                $('#step-description').val(step.description);
                $('#step-products').val(step.products).trigger('change');
                
                $('#routine-step-modal').show();
            });

            $('#routine-step-form').submit(function(e) {
                e.preventDefault();
                
                const index = $('#step-index').val();
                const step = {
                    name: $('#step-name').val(),
                    frequency: $('#step-frequency').val(),
                    time_of_day: $('#step-time').val(),
                    description: $('#step-description').val(),
                    products: $('#step-products').val() || []
                };
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'myavana_save_routine_step',
                        step: JSON.stringify(step),
                        index: index,
                        nonce: '<?php echo wp_create_nonce('myavana_profile_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            $('#error-message').text('Error: ' + response.data).removeClass('hidden').fadeIn(300);
                            setTimeout(() => $('#error-message').fadeOut(300), 5000);
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        $('#error-message').text('Error: Failed to connect to server. Please try again.').removeClass('hidden').fadeIn(300);
                        setTimeout(() => $('#error-message').fadeOut(300), 5000);
                    }
                });
            });

            $(document).on('click', '.delete-step', function() {
                if (confirm('Are you sure you want to delete this routine step?')) {
                    const index = $(this).data('index');
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'myavana_delete_routine_step',
                            index: index,
                            nonce: '<?php echo wp_create_nonce('myavana_profile_nonce'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                location.reload();
                            } else {
                                $('#error-message').text('Error: ' + response.data).removeClass('hidden').fadeIn(300);
                                setTimeout(() => $('#error-message').fadeOut(300), 5000);
                            }
                        }
                    });
                }
            });

            // Modal close
            $('.close-modal').click(function() {
                $(this).closest('.myavana-modal').hide();
            });

            $(window).click(function(event) {
                if ($(event.target).hasClass('myavana-modal')) {
                    $('.myavana-modal').hide();
                    try {
                        $('#youzify-profile-navmenu').show();
                    } catch (error) {
                        console.log(error.message)
                    }
                }
            });
        });
    </script>
         <script src="https://cdn.jsdelivr.net/npm/webcamjs@1.0.26/webcam.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/filepond-plugin-file-validate-type@1.2.8/dist/filepond-plugin-file-validate-type.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/filepond-plugin-file-validate-size@2.2.8/dist/filepond-plugin-file-validate-size.min.js"></script>
    <script>
        jQuery(document).ready(function($) {
            // Initialize FilePond
            const hairAnalysisContent = $('.hair-analysis-content');
            function resizeImage(file, maxWidth, maxHeight, callback) {
                const img = new Image();
                img.src = URL.createObjectURL(file);
                img.onload = () => {
                    const canvas = document.createElement('canvas');
                    let width = img.width;
                    let height = img.height;
                    if (width > height) {
                        if (width > maxWidth) {
                            height *= maxWidth / width;
                            width = maxWidth;
                        }
                    } else {
                        if (height > maxHeight) {
                            width *= maxHeight / height;
                            height = maxHeight;
                        }
                    }
                    canvas.width = width;
                    canvas.height = height;
                    canvas.getContext('2d').drawImage(img, 0, 0, width, height);
                    callback(canvas.toDataURL('image/jpeg', 0.8).split(',')[1]);
                    URL.revokeObjectURL(img.src);
                };
            }
            FilePond.registerPlugin(FilePondPluginImagePreview, FilePondPluginFileValidateType, FilePondPluginFileValidateSize);
            const pond = FilePond.create(document.querySelector('#photo-upload'), {
                acceptedFileTypes: ['image/jpeg', 'image/png'],
                maxFileSize: '5MB',
                allowImagePreview: true,
                stylePanelLayout: 'compact',
                styleButtonRemoveItemPosition: 'right',
                onaddfile: (error, file) => {
                    if (!error) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            const dataUrl = e.target.result;
                            if (dataUrl && dataUrl.startsWith('data:image/')) {                               
                                $('#preview-image').attr('src', dataUrl);
                                    $('#preview-image').data('base64', dataUrl.split(',')[1]); // Store base64 for later use
                                    $('#upload-setup').hide();
                                    $('#tryon-preview').show();
                                    $('#ai-suggestion').show();
                                    $('#generate-preview').prop('disabled', false); // Enable the button
                            } else {
                                alert('Invalid image format. Please upload a JPEG or PNG image.');
                            }
                        };
                        reader.readAsDataURL(file.file);
                    } else {
                        alert('Error uploading file: ' + error.message);
                    }
                },
                onremovefile: () => {
                    $('#preview-image').attr('src', '');
                    $('#preview-image').data('base64', '');
                    $('#upload-setup').hide();
                    $('#tryon-preview').hide();
                    $('#ai-suggestion').hide();
                    $('#generate-preview').prop('disabled', true);
                }
            });

            // Terms acceptance
            $('#terms-accept').click(function() {
                if ($('#terms-agree').is(':checked')) {
                    $('#tryon-terms').hide();
                    $('#tryon-interface').show();
                } else {
                    alert('Please agree to the terms.');
                }
            });

            // Image source selection
            $('#use-camera').click(function() {
                $('#image-source').hide();
                $('#camera-setup').show();
            });

            $('#upload-photo').click(function() {
                $('#image-source').hide();
                $('#upload-setup').show();
            });

            // Camera setup
            $('#start-camera').click(function() {
                Webcam.set({
                    width: 500,
                    height: 375,
                    image_format: 'jpeg',
                    jpeg_quality: 90,
                    facingMode: 'user'
                });
                Webcam.attach('#camera-view');
                $('#start-camera').hide();
                $('#capture-photo').show();
                $('#retake-photo').hide();
            });

            $('#capture-photo').click(function() {
                Webcam.snap(function(data_uri) {
                    $('#preview-image').attr('src', data_uri);
                    $('#camera-setup').hide();
                    $('#tryon-preview').show();
                    $('#ai-suggestion').show();
                    Webcam.reset();
                });
            });

            $('#retake-photo').click(function() {
                $('#tryon-preview').hide();
                $('#camera-setup').show();
                $('#start-camera').hide();
                $('#capture-photo').show();
                Webcam.attach('#camera-view');
            });

            // Cancel buttons
            $('#cancel-camera, #cancel-upload, #cancel-preview').click(function() {
                $('#camera-setup, #upload-setup, #tryon-preview').hide();
                $('#image-source').show();
                $('#ai-suggestion').hide();
                Webcam.reset();
                pond.removeFiles();
                $('#preview-image').attr('src', '');
                $('.style-option, .color-option').removeClass('active');
                $('#generate-preview').prop('disabled', true);
                $('#hair-overlay').empty();
            });

            
            // Generate preview click handler
            $('#generate-preview').click(async function() {
                // Get the image data from the preview image
                $("#myavanaLoader").fadeIn();
                const previewImage = $('#preview-image')[0];
                
                // Check if there's actually an image loaded
                if (!previewImage || !previewImage.src || previewImage.src.startsWith('data:,')) {
                    $('#error-message').text('Please upload an image first!').removeClass('hidden').fadeIn(300);
                    setTimeout(() => $('#error-message').fadeOut(300), 5000);
                    return;
                }

                // Get the base64 data URL from the image src
                let dataUrl = previewImage.src;
                
                // Ensure the data URL is valid
                if (!dataUrl.startsWith('data:image/')) {
                    $('#error-message').text('Invalid image format. Please upload a JPEG or PNG image.').removeClass('hidden').fadeIn(300);
                    setTimeout(() => $('#error-message').fadeOut(300), 5000);
                    return;
                }

                // Extract just the base64 part
                const base64Image = dataUrl.split(',')[1];
                
                // Show loading overlay
                $('#loading-overlay').show();
                $('#preview-controls').hide();

                try {
                    // Get analysis from xAI API
                    const analysis = await analyzeImageWithAI(base64Image);
                    console.log('xAI API response:', analysis);

                    // Save hair journey entry
                    const formData = new FormData();
                    formData.append('action', 'myavana_add_entry');
                    formData.append('myavana_nonce', '<?php echo wp_create_nonce('myavana_profile_nonce'); ?>');
                    formData.append('is_automated', '1');
                    formData.append('image_data', base64Image);
                    formData.append('analysis', JSON.stringify(analysis));
                    formData.append('session_id', 'session_' + Date.now());

                    const saveResponse = await fetch(ajaxurl, {
                        method: 'POST',
                        body: formData
                    });

                    if (!saveResponse.ok) {
                        throw new Error(`Failed to save hair journey entry: HTTP ${saveResponse.status}`);
                    }

                    const saveResult = await saveResponse.json();
                    if (!saveResult.success) {
                        throw new Error(saveResult.data || 'Failed to save hair journey entry');
                    }

                    // Update user meta with analysis history
                    const analysisHistoryEntry = {
                        image: base64Image,
                        date: new Date().toISOString(),
                        summary: analysis.summary,
                        full_analysis: analysis
                    };

                    await $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'myavana_save_hair_analysis',
                            analysis: JSON.stringify(analysisHistoryEntry),
                            nonce: '<?php echo wp_create_nonce('myavana_profile_nonce'); ?>'
                        },
                        success: function(response) {
                            if (!response.success) {
                                throw new Error(response.data || 'Failed to save analysis history');
                            }
                        }
                    });

                    // Update Analysis History section with results
                    const analysisContent = `
                        <div class="analysis-result-container p-6 bg-white rounded-lg shadow-lg">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-xl font-bold text-gray-800">Hair Analysis Results</h3>
                                <button id="dismiss-analysis" class="myavana-button secondary">Dismiss</button>
                            </div>
                            <div class="hair-analysis-results">
                                <div id="hair-analysis-content" class="analysis-placeholder"></div>
                                <div class="hair-metrics mt-4">
                                    <div class="metric">
                                        <div class="metric-label">Hydration</div>
                                        <div class="metric-bar"><div id="hydration-level" class="metric-fill bg-coral" style="width:0%"></div></div>
                                    </div>
                                    <div class="metric">
                                        <div class="metric-label">Curl Pattern</div>
                                        <div class="metric-bar"><div id="curl-pattern" class="metric-fill bg-coral" style="width:0%"></div></div>
                                    </div>
                                    <div class="metric">
                                        <div class="metric-label">Health Score</div>
                                        <div class="metric-bar"><div id="health-score" class="metric-fill bg-coral" style="width:0%"></div></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    $('#analysis-history').html(analysisContent);
                    updateHairAnalysis(analysis);
                    updateHairMetrics({
                        hydration: analysis.hair_analysis.hydration,
                        curl_pattern: analysis.hair_analysis.curl_pattern ? analysis.hair_analysis.curl_pattern.replace(/\D/g, '') * 20 : 0,
                        health_score: analysis.hair_analysis.health_score
                    });

                    // Show success message
                    $('#success-message').text('Hair analysis completed and saved successfully!').removeClass('hidden').fadeIn(300);
                    setTimeout(() => $('#success-message').fadeOut(300), 5000);

                    // Handle dismiss button
                    $('#dismiss-analysis').click(function() {
                        location.reload(); // Reload to restore default history grid
                    });

                } catch (error) {
                    console.error('Error:', error);
                    $('#error-message').text('Error: ' + error.message).removeClass('hidden').fadeIn(300);
                    setTimeout(() => $('#error-message').fadeOut(300), 5000);
                    $('#loading-overlay').hide();
                    $("#myavanaLoader").fadeOut(); 
                    //reload the page
                    window.location.reload(false);
                } finally {
                    // Hide loading overlay

                    $('#loading-overlay').hide();
                    $("#myavanaLoader").fadeOut(); 
                    //reload the page
                    window.location.reload(false);
                }
            });

            $('#download-image').click(function() {
                const imageData = $('#preview-image').attr('src');
                const link = document.createElement('a');
                link.href = imageData;
                link.download = 'myavana-hairstyle-preview.png';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            });

            // Try another
            $('#try-another').click(function() {
                $('#tryon-preview').hide();
                $('#image-source').show();
                $('#ai-suggestion').hide();
                $('#preview-image').attr('src', '');
                $('.style-option, .color-option').removeClass('active');
                $('#generate-preview').prop('disabled', true);
                $('#hair-overlay').empty();
                pond.removeFiles();
            });

            // Save look
            $('#save-look').click(function() {
                const imageData = $('#preview-image').attr('src');
                const selectedColor = $('.color-option.active').data('color') || 'black';
                const selectedStyle = $('.style-option.active').data('style') || 'bob';
                const metadata = $('#preview-image').data('metadata') || {};
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'myavana_save_tryon',
                        image_data: imageData,
                        color: selectedColor,
                        style: selectedStyle,
                        title: metadata.title || '',
                        description: metadata.description || '',
                        stylist_notes: metadata.stylist_notes || '',
                        products_used: metadata.products_used || '',
                        user_id: <?php echo $user_id; ?>,
                        nonce: '<?php echo wp_create_nonce('myavana_tryon_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('Look saved to your hair journey!');
                            $('#cancel-preview').click();
                        } else {
                            alert('Error saving look: ' + (response.data || 'Unknown error'));
                        }
                    },
                    error: function() {
                        alert('Error saving look. Please try again.');
                    }
                });
            });

            // Hair Analysis Function
            async function analyzeImageWithAI(imageData) {
                const prompt = `
                    Analyze the provided image for a comprehensive haircare consultation. Include:
                    1. **Environment**: Describe the setting (e.g., lighting, background, indoor/outdoor). If unclear, note as "Not visible".
                    2. **User Description**: Estimate visible characteristics (e.g., attire, posture). If not visible, note as "Not visible".
                    3. **Mood and Demeanor**: Infer the user's mood or demeanor based on visible cues (e.g., facial expression, body language). If not visible, note as "Not visible".
                    4. **Hair Analysis**:
                        - Hair type (e.g., straight, wavy, curly, coily). If unclear, note as "Unclear".
                        - Curl pattern (e.g., 1A, 2B, 3C, 4C). If unclear, note as "Unclear".
                        - Length (e.g., short, medium, long, measured in cm or inches if possible). If unclear, note as "Unclear".
                        - Texture (e.g., fine, medium, coarse). If unclear, note as "Unclear".
                        - Density (e.g., low, medium, high). If unclear, note as "Unclear".
                        - Hydration level (estimate as percentage 0-100 based on appearance). If unclear, note as "Unclear".
                        - Health score (0-100, based on shine, split ends, breakage, etc.). If unclear, note as "Unclear".
                        - Hairstyle (e.g., updo, loose, braided). If unclear, note as "Unclear".
                        - Damage (e.g., split ends, breakage, frizz). If none, note as "None observed".
                        - Scalp health (e.g., visible flaking, oiliness, dryness, 0-100 score). If unclear, note as "Unclear".
                        - Hair color (e.g., natural black, dyed blonde). If unclear, note as "Unclear".
                        - Porosity (e.g., low, medium, high). If unclear, note as "Unclear".
                        - Elasticity (e.g., low, medium, high, 0-100 score). If unclear, note as "Unclear".
                        - Strand thickness (e.g., fine, medium, thick, in micrometers if estimable). If unclear, note as "Unclear".
                        - Growth rate (e.g., cm/month, if estimable from visible growth patterns). If unclear, note as "Unclear".
                    5. **Recommendations**: Provide 4-6 specific haircare recommendations based on the analysis, tailored to hair type, damage, and goals.
                    6. **Products**: List 3-5 recommended products with name, id (e.g., prod_123), and match percentage (0-100).
                    7. **Summary**: Provide a concise summary (50-100 words) of the hair analysis for user communication.
                    8. **Full Context**: Combine all observations into a detailed narrative for use in future AI-driven features (e.g., trend analysis, personalized routines).

                    Return the response in JSON format with fields: environment, user_description, mood_demeanor, hair_analysis (with subfields: type, curl_pattern, length, texture, density, hydration, health_score, hairstyle, damage, scalp_health, hair_color, porosity, elasticity, strand_thickness, growth_rate), recommendations (array), products (array with name, id, match), summary, full_context.
                `;

                const formData = new FormData();
                formData.append('image_data', imageData); // Use the base64 string directly
                formData.append('prompt', prompt);
                formData.append('nonce', '<?php echo wp_create_nonce('myavana_profile_nonce'); ?>');
                formData.append('action', 'myavana_handle_vision_api_hair_analysis');

                try {
                    $('#hair-analysis-content').html('<p>Analyzing your hair... Please wait.</p>');
                    const response = await fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                        method: 'POST',
                        body: formData
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }

                    const result = await response.json();
                    
                    if (!result.success) {
                        throw new Error(result.data.message || 'Hair analysis failed');
                    }

                    return result.data.analysis; // Return the analysis data
                } catch (error) {
                    throw new Error('Failed to analyze image: ' + error.message);
                }

                // try {
                //     $('#hair-analysis-content').html('<p>Analyzing your hair... Please wait.</p>');
                //     const response = await fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                //         method: 'POST',
                //         body: formData
                //     });
                //     const result = await response.json();
                    
                //     if (!result.success) {
                //         throw new Error(result.data.message || 'Hair analysis failed');
                //     }

                //     const analysis = result.data.analysis;

                //     // Display analysis results
                //     $('#hair-analysis-content').html(`
                //         <p><strong>Summary:</strong> ${analysis.summary}</p>
                //         <img src="${imageData}" alt="Hair Analysis Image" style="max-width: 100%; border-radius: 8px; margin-top: 16px;">
                //     `);
                //     $('#analysis-details').show();
                //     $('#hair-type').text(analysis.hair_analysis.type || 'Unclear');
                //     $('#curl-pattern').text(analysis.hair_analysis.curl_pattern || 'Unclear');
                //     $('#hair-length').text(analysis.hair_analysis.length || 'Unclear');
                //     $('#texture').text(analysis.hair_analysis.texture || 'Unclear');
                //     $('#density').text(analysis.hair_analysis.density || 'Unclear');
                //     $('#hair-color').text(analysis.hair_analysis.hair_color || 'Unclear');
                //     $('#damage').text(analysis.hair_analysis.damage || 'None observed');
                //     $('#hydration-level').css('width', `${analysis.hair_analysis.hydration || 0}%`);
                //     $('#hydration-value').text(`${analysis.hair_analysis.hydration || 0}%`);
                //     $('#health-score').css('width', `${analysis.hair_analysis.health_score || 0}%`);
                //     $('#health-score-value').text(`${analysis.hair_analysis.health_score || 0}%`);
                //     $('#scalp-health').css('width', `${analysis.hair_analysis.scalp_health || 0}%`);
                //     $('#scalp-health-value').text(`${analysis.hair_analysis.scalp_health || 0}%`);
                //     $('#elasticity').css('width', `${analysis.hair_analysis.elasticity || 0}%`);
                //     $('#elasticity-value').text(`${analysis.hair_analysis.elasticity || 0}%`);

                //     $('#recommendations').html(`
                //         <h4><h4>Recommendations</h4>
                //         <ul>
                //             ${analysis.recommendations.map(rec => `<li>${rec}</li>`).join('')}
                //         </ul>
                //         `);
                //     $('#products').html(`
                //         <h4>Recommended Products</h4>
                //         <ul>
                //             ${analysis.products.map(prod => `<li>${prod.name} (Match: ${prod.match}%)</li>`).join('')}
                //         </ul>
                //     `);

                //     // Save analysis to history and snapshots
                //     $.ajax({
                //         url: '<?php echo admin_url('admin-ajax.php'); ?>',
                //         type: 'POST',
                //         data: {
                //             action: 'myavana_save_hair_analysis',
                //             analysis: JSON.stringify({
                //                 image: imageData,
                //                 date: new Date().toISOString(),
                //                 summary: analysis.summary,
                //                 full_analysis: analysis
                //             }),
                //             nonce: '<?php echo wp_create_nonce('myavana_profile_nonce'); ?>'
                //         },
                //         success: function(response) {
                //             if (response.success) {
                //                 $('#success-message').text('Hair analysis saved successfully!').removeClass('hidden').fadeIn(300);
                //                 setTimeout(() => {
                //                     $('#success-message').fadeOut(300);
                //                     location.reload(); // Reload to update history
                //                 }, 3000);
                //             } else {
                //                 $('#error-message').text('Error: ' + (response.data || 'Failed to save analysis.')).removeClass('hidden').fadeIn(300);
                //                 setTimeout(() => $('#error-message').fadeOut(300), 5000);
                //             }
                //         },
                //         error: function() {
                //             $('#error-message').text('Error: Failed to save analysis.').removeClass('hidden').fadeIn(300);
                //             setTimeout(() => $('#error-message').fadeOut(300), 5000);
                //         }
                //     });
                // } catch (error) {
                //     $('#error-message').text('Error: ' + error.message).removeClass('hidden').fadeIn(300);
                //     setTimeout(() => $('#error-message').fadeOut(300), 5000);
                // }
            }

            function updateHairAnalysis(analysis) {
                if (!analysis) {
                    hairAnalysisContent.innerHTML = `<p>No analysis data available.</p>`;
                    return;
                }

                let html = `
                    <div class="analysis-result">
                        <h4>Your Hair Profile</h4>
                        <p>${analysis.summary || 'No summary available.'}</p>
                        <div class="hair-details">
                            <div class="detail">
                                <span class="detail-label">Type:</span>
                                <span class="detail-value">${analysis.hair_analysis?.type || 'N/A'}</span>
                            </div>
                            <div class="detail">
                                <span class="detail-label">Curl Pattern:</span>
                                <span class="detail-value">${analysis.hair_analysis?.curl_pattern || 'N/A'}</span>
                            </div>
                            <div class="detail">
                                <span class="detail-label">Length:</span>
                                <span class="detail-value">${analysis.hair_analysis?.length || 'N/A'}</span>
                            </div>
                            <div class="detail">
                                <span class="detail-label">Texture:</span>
                                <span class="detail-value">${analysis.hair_analysis?.texture || 'N/A'}</span>
                            </div>
                            <div class="detail">
                                <span class="detail-label">Density:</span>
                                <span class="detail-value">${analysis.hair_analysis?.density || 'N/A'}</span>
                            </div>
                            <div class="detail">
                                <span class="detail-label">Hydration:</span>
                                <span class="detail-value">${analysis.hair_analysis?.hydration ? `${analysis.hair_analysis.hydration}%` : 'N/A'}</span>
                            </div>
                            <div class="detail">
                                <span class="detail-label">Health Score:</span>
                                <span class="detail-value">${analysis.hair_analysis?.health_score ? `${analysis.hair_analysis.health_score}%` : 'N/A'}</span>
                            </div>
                            <div class="detail">
                                <span class="detail-label">Hairstyle:</span>
                                <span class="detail-value">${analysis.hair_analysis?.hairstyle || 'N/A'}</span>
                            </div>
                            <div class="detail">
                                <span class="detail-label">Damage:</span>
                                <span class="detail-value">${analysis.hair_analysis?.damage || 'None observed'}</span>
                            </div>
                        </div>
                        <h4>Environment & Context</h4>
                        <p><strong>Setting:</strong> ${analysis.environment || 'N/A'}</p>
                        <p><strong>User Description:</strong> ${analysis.user_description || 'N/A'}</p>
                        <p><strong>Mood:</strong> ${analysis.mood_demeanor || 'N/A'}</p>
                        <h4>Recommended Products</h4>
                        <div class="product-recommendations">
                `;
                if (Array.isArray(analysis.products) && analysis.products.length > 0) {
                    analysis.products.forEach(product => {
                        html += `
                            <div class="product">
                                <div class="product-match" style="width: ${product.match || 0}%"></div>
                                <span class="product-name">${product.name || 'Unnamed Product'}</span>
                                <span class="product-match-value">${product.match || 0}% match</span>
                            </div>
                        `;
                    });
                } else {
                    html += `<p>No product recommendations available.</p>`;
                }
                html += `
                        </div>
                        <h4>Care Recommendations</h4>
                        <ul class="care-tips">
                `;
                if (Array.isArray(analysis.recommendations) && analysis.recommendations.length > 0) {
                    analysis.recommendations.forEach(tip => {
                        html += `<li>${tip}</li>`;
                    });
                } else {
                    html += `<li>No care recommendations available.</li>`;
                }
                html += `</ul></div>`;
                hairAnalysisContent.innerHTML = html;
            }

             function updateHairMetrics(metrics) {
                const safePercentage = (value) => {
                    const num = parseInt(value);
                    return isNaN(num) || num < 0 ? 0 : num > 100 ? 100 : num;
                };
                document.getElementById('hydration-level').style.width = `${safePercentage(metrics?.hydration)}%`;
                document.getElementById('curl-pattern').style.width = `${safePercentage(metrics?.curl_pattern)}%`;
                document.getElementById('health-score').style.width = `${safePercentage(metrics?.health_score)}%`;
                document.querySelectorAll('.metric-fill').forEach(bar => {
                    bar.style.transition = 'width 1s ease-in-out';
                });
            }

        });
    </script>
       
    </div>
    <?php
    return ob_get_clean();
}

?>