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
        'ajaxurl' => admin_url('admin-ajax.php'),
        'ajax_url' => admin_url('admin-ajax.php'), // Backup for compatibility
        'nonce' => wp_create_nonce('myavana_profile_nonce')
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
    <?php
    // Enqueue external CSS
    wp_enqueue_style('myavana-profile-css', plugin_dir_url(dirname(__FILE__)) . 'assets/css/profile-shortcode.css', [], '1.0.0');
    ?>
    
    <div class="container">
        <!-- Profile Header -->
        <div class="profile-header">
            <div class="profile-top">
                <div class="profile-identity">
                    <div class="profile-avatar">
                        <?php echo bp_core_fetch_avatar(['item_id' => $user_id, 'type' => 'full']); ?>
                    </div>
                    <div class="profile-info">
                        <h2 class="profile-name"><?php echo esc_html(get_userdata($user_id)->display_name); ?></h2>
                        <div class="profile-handle">@<?php echo esc_html(get_userdata($user_id)->user_login); ?></div>
                        <?php if ($about_me) : ?>
                            <p class="profile-bio"><?php echo esc_html($about_me); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ($is_owner) : ?>
                    <div class="profile-actions">
                        <button id="edit-profile-btn" class="myavana-btn-primary">
                            <i class="fas fa-pencil-alt"></i> Edit Profile
                        </button>
                    </div>
                <?php endif; ?>
            </div>

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
        </div>
        
        <div id="error-message" class="error-message hidden"></div>
        <div id="success-message" class="success-message hidden"></div>
        <div id="ai-tip" class="ai-tip hidden"></div>

        <div id="profile-edit" class="profile-edit-form hidden">
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
                    <div class="myavana-modal" style="display: none;" id="hairTypeGuide">
                        <div class="modal-content">
                            <span id="close-info-modal" class="close-modal">×</span>
                            <div class="modal-header">
                                <h5 class="modal-title">Hair Type Guide</h5>
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
                                <button id="cancel-info-modal" type="button" class="myavana-btn-secondary">Close</button>
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
                <div class="form-actions">
                    <button type="submit" class="myavana-btn-primary">Save Changes</button>
                    <button type="button" id="cancel-edit-btn" class="myavana-btn-secondary">Cancel</button>
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
                    <textarea id="about-me-text" class="myavana-textarea w-full" placeholder="Tell us about yourself and your hair journey..." data-original-value="<?php echo esc_attr($about_me); ?>"><?php echo esc_textarea($about_me); ?></textarea>
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
                
                <!-- Hair Analysis Slider with Splide.js -->
                <div class="myavana-hair-slider-container">
                    <?php if (!empty($snapshots)) : ?>
                        <div class="splide myavana-hair-analysis-splide" id="hair-analysis-splide">
                            <div class="splide__track">
                                <ul class="splide__list">
                                    <?php foreach ($snapshots as $index => $snapshot) : ?>
                                        <li class="splide__slide myavana-hair-slide">
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
                                    
                                    <button class="myavana-history-details-btn" data-analysis='<?php echo htmlspecialchars(json_encode($analysis), ENT_QUOTES, 'UTF-8'); ?>'>
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
                            Your photos are analyzed using Google's Myavana AI and are not stored permanently. Analysis results are saved to help track your hair journey progress.
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

        <!-- Hair Goals Section -->
        <div class="profile-section">
            <div class="section-header">
                <h2 class="section-title">My Hair Goals</h2>
                <?php if ($is_owner) : ?>
                    <button class="myavana-btn-primary add-new-myavana-hair-goal-selector" data-section="hair-goals" id="add-hair-goal">
                        <i class="fas fa-plus"></i>
                        <span>Add Goal</span>
                    </button>
                <?php endif; ?>
            </div>

            <div id="hair-goals-list" class="goals-grid">
                <?php if (empty($hair_goals)): ?>
                    <div class="empty-state">No hair goals set yet.</div>
                <?php else: ?>
                    <?php foreach ($hair_goals as $index => $goal): ?>
                        <div class="goal-card" data-index="<?php echo $index; ?>">
                            <!-- Header with Title and Actions -->
                            <div class="goal-header">
                                <h3 class="goal-title"><?php echo esc_html($goal['title']); ?></h3>
                                <?php if ($is_owner): ?>
                                <div class="goal-actions">
                                    <button class="goal-action-btn edit-goal" data-index="<?php echo $index; ?>" title="Edit Goal">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="goal-action-btn delete-goal" data-index="<?php echo $index; ?>" title="Delete Goal">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- Progress Section with Intelligence -->
                            <div class="goal-progress">
                                <div class="progress-info">
                                    <span class="progress-text">Progress</span>
                                    <span class="progress-percentage"><?php echo esc_attr($goal['progress']); ?>%</span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo esc_attr($goal['progress']); ?>%" data-progress="<?php echo esc_attr($goal['progress']); ?>"></div>
                                </div>

                                <!-- Progress Insights -->
                                <?php
                                $progress = intval($goal['progress']);
                                $start_date = strtotime($goal['start_date']);
                                $target_date = strtotime($goal['target_date']);
                                $today = time();
                                $total_days = max(1, ($target_date - $start_date) / 86400);
                                $days_elapsed = max(0, ($today - $start_date) / 86400);
                                $days_remaining = max(0, ($target_date - $today) / 86400);
                                $expected_progress = min(100, ($days_elapsed / $total_days) * 100);
                                $progress_delta = $progress - $expected_progress;

                                // Calculate weekly progress velocity
                                $progress_history = $goal['progress_history'] ?? [];
                                $recent_velocity = 0;
                                if (count($progress_history) >= 2) {
                                    $recent = array_slice($progress_history, -2);
                                    $velocity_change = $recent[1]['progress'] - $recent[0]['progress'];
                                    $time_diff = max(1, (strtotime($recent[1]['date']) - strtotime($recent[0]['date'])) / 86400);
                                    $recent_velocity = ($velocity_change / $time_diff) * 7; // Weekly velocity
                                }
                                ?>

                                <div class="goal-insights">
                                    <?php if ($progress_delta > 10): ?>
                                        <div class="insight-badge ahead">
                                            <i class="fas fa-rocket"></i> Ahead of schedule!
                                        </div>
                                    <?php elseif ($progress_delta < -10 && $progress < 100): ?>
                                        <div class="insight-badge behind">
                                            <i class="fas fa-clock"></i> Needs attention
                                        </div>
                                    <?php elseif ($progress >= 100): ?>
                                        <div class="insight-badge completed">
                                            <i class="fas fa-trophy"></i> Goal Achieved!
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($days_remaining > 0 && $progress < 100): ?>
                                        <div class="insight-text">
                                            <i class="fas fa-calendar-alt"></i>
                                            <?php echo ceil($days_remaining); ?> days remaining
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($recent_velocity > 0 && $progress < 100): ?>
                                        <div class="insight-text velocity">
                                            <i class="fas fa-chart-line"></i>
                                            +<?php echo number_format($recent_velocity, 1); ?>% per week
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <?php if ($is_owner): ?>
                                <div class="progress-update">
                                    <input type="range" class="goal-progress-slider" data-index="<?php echo $index; ?>" min="0" max="100" value="<?php echo esc_attr($goal['progress']); ?>">
                                    <button class="update-progress-btn" data-index="<?php echo $index; ?>">Update Progress</button>
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- Meta Information -->
                            <div class="goal-meta">
                                <span class="goal-category"><?php echo esc_html($goal['category'] ?? 'Hair Goal'); ?></span>
                                <div class="goal-dates">
                                    <span class="goal-date">Started: <?php echo esc_html(date('M j, Y', strtotime($goal['start_date']))); ?></span>
                                    <span class="goal-date">Target: <?php echo esc_html(date('M j, Y', strtotime($goal['target_date']))); ?></span>
                                </div>
                            </div>

                            <!-- Achievement Milestones -->
                            <?php
                            $milestones = [25 => 'First Quarter', 50 => 'Halfway There', 75 => 'Almost Done', 100 => 'Champion'];
                            $earned_badges = [];
                            foreach ($milestones as $threshold => $badge_name) {
                                if ($progress >= $threshold) {
                                    $earned_badges[$threshold] = $badge_name;
                                }
                            }
                            ?>
                            <?php if (!empty($earned_badges)): ?>
                            <div class="goal-achievements">
                                <div class="achievements-header">
                                    <h4 class="achievements-title">
                                        <i class="fas fa-medal"></i> Achievements
                                    </h4>
                                </div>
                                <div class="badges-container">
                                    <?php foreach ($milestones as $threshold => $badge_name): ?>
                                        <div class="achievement-badge <?php echo $progress >= $threshold ? 'earned' : 'locked'; ?>" data-milestone="<?php echo $threshold; ?>">
                                            <div class="badge-icon">
                                                <?php if ($threshold == 25): ?>
                                                    <i class="fas fa-seedling"></i>
                                                <?php elseif ($threshold == 50): ?>
                                                    <i class="fas fa-star-half-alt"></i>
                                                <?php elseif ($threshold == 75): ?>
                                                    <i class="fas fa-fire"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-crown"></i>
                                                <?php endif; ?>
                                            </div>
                                            <div class="badge-name"><?php echo esc_html($badge_name); ?></div>
                                            <?php if ($progress >= $threshold): ?>
                                                <div class="badge-earned">✓</div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- AI Recommendations -->
                            <?php if ($is_owner && $progress < 100): ?>
                            <div class="goal-ai-recommendations">
                                <div class="ai-rec-header">
                                    <h4 class="ai-rec-title">
                                        <i class="fas fa-sparkles"></i> AI Insights
                                    </h4>
                                    <button class="refresh-ai-btn" data-index="<?php echo $index; ?>" title="Get fresh recommendations">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                </div>
                                <div class="ai-recommendations-list" data-goal-index="<?php echo $index; ?>">
                                    <?php
                                    // Generate contextual recommendations
                                    $recommendations = [];

                                    if ($progress_delta < -10) {
                                        $recommendations[] = "Consider breaking this goal into smaller weekly milestones to stay on track.";
                                    }

                                    if ($recent_velocity > 5) {
                                        $recommendations[] = "Great momentum! Keep documenting your progress with photos in your timeline.";
                                    }

                                    if ($days_remaining < 30 && $progress < 80) {
                                        $recommendations[] = "Goal deadline approaching. Consider adjusting timeline or intensifying your routine.";
                                    }

                                    // Goal-specific recommendations
                                    $goal_title = strtolower($goal['title']);
                                    if (strpos($goal_title, 'length') !== false || strpos($goal_title, 'growth') !== false) {
                                        $recommendations[] = "For length retention: Deep condition weekly, protect ends, and minimize heat styling.";
                                    } elseif (strpos($goal_title, 'moisture') !== false || strpos($goal_title, 'hydration') !== false) {
                                        $recommendations[] = "Stay hydrated, use leave-in conditioners, and seal moisture with oils or butters.";
                                    } elseif (strpos($goal_title, 'thickness') !== false || strpos($goal_title, 'volume') !== false) {
                                        $recommendations[] = "Try scalp massages, protein treatments, and volumizing styling techniques.";
                                    }

                                    $stored_recommendations = $goal['ai_recommendations'] ?? [];
                                    $all_recommendations = array_merge($recommendations, $stored_recommendations);
                                    $all_recommendations = array_unique(array_slice($all_recommendations, 0, 3));
                                    ?>
                                    <?php if (!empty($all_recommendations)): ?>
                                        <?php foreach ($all_recommendations as $rec): ?>
                                            <div class="ai-rec-item">
                                                <i class="fas fa-lightbulb"></i>
                                                <span><?php echo esc_html($rec); ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="ai-rec-item loading">
                                            <i class="fas fa-magic"></i>
                                            <span>Click refresh to get personalized recommendations</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Progress History Chart (Collapsible) -->
                            <?php if (!empty($progress_history)): ?>
                            <div class="goal-history">
                                <div class="history-header">
                                    <h4 class="history-title">
                                        <i class="fas fa-chart-area"></i> Progress History
                                    </h4>
                                    <button class="history-toggle">
                                        <i class="fas fa-chevron-down"></i>
                                    </button>
                                </div>
                                <div class="history-content">
                                    <canvas class="progress-chart" data-history='<?php echo htmlspecialchars(json_encode($progress_history), ENT_QUOTES, 'UTF-8'); ?>'></canvas>
                                    <div class="history-stats">
                                        <div class="stat-box">
                                            <span class="stat-label">Total Updates</span>
                                            <span class="stat-value"><?php echo count($progress_history); ?></span>
                                        </div>
                                        <div class="stat-box">
                                            <span class="stat-label">Avg Progress/Week</span>
                                            <span class="stat-value"><?php echo number_format($recent_velocity, 1); ?>%</span>
                                        </div>
                                        <?php if (count($progress_history) > 1): ?>
                                        <div class="stat-box">
                                            <span class="stat-label">Best Week</span>
                                            <?php
                                            $max_gain = 0;
                                            for ($i = 1; $i < count($progress_history); $i++) {
                                                $gain = $progress_history[$i]['progress'] - $progress_history[$i-1]['progress'];
                                                $max_gain = max($max_gain, $gain);
                                            }
                                            ?>
                                            <span class="stat-value">+<?php echo $max_gain; ?>%</span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Progress Updates (Collapsible) -->
                            <div class="goal-updates">
                                <div class="updates-header">
                                    <h4 class="updates-title">Progress Notes</h4>
                                    <button class="updates-toggle">
                                        <i class="fas fa-chevron-down"></i>
                                    </button>
                                </div>
                                <div class="updates-content">
                                    <?php if (!empty($goal['progress_text'])): ?>
                                        <?php foreach ($goal['progress_text'] as $update): ?>
                                            <div class="update-item">
                                                <p class="update-text"><?php echo esc_html($update['text']); ?></p>
                                                <span class="update-date"><?php echo esc_html(date('M j', strtotime($update['date']))); ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>

                                    <?php if ($is_owner): ?>
                                    <div class="add-update">
                                        <input type="text" class="update-text-input" placeholder="Add progress note..." data-index="<?php echo $index; ?>">
                                        <button class="add-update-btn" data-index="<?php echo $index; ?>">Add</button>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
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
                    <button class="myavana-btn-primary add-new-myavana-current-routine" data-section="routine" id="add-routine-step">
                        <i class="fas fa-plus"></i>
                        <span>Add Step</span>
                    </button>
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
                                                <button class="editBtn edit-step" data-index="<?php echo $index; ?>">
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



        <!-- Inline JavaScript moved to external file -->

                
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/filepond@4.30.4/dist/filepond.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/filepond-plugin-image-preview@4.6.11/dist/filepond-plugin-image-preview.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>


    <?php
    // Enqueue external JavaScript files
    wp_enqueue_script('myavana-profile-js', plugin_dir_url(dirname(__FILE__)) . 'assets/js/profile-shortcode.js', ['jquery'], '1.0.0', true);

    // Localize scripts with AJAX data
    wp_localize_script('myavana-profile-js', 'myavanaProfileData', [
        'hairGoals' => $hair_goals,
        'currentRoutine' => $current_routine,
        'aboutMe' => $about_me,
        'chartData' => isset($chart_data) ? $chart_data : null,
        'userId' => $user_id
    ]);

    wp_localize_script('myavana-profile-js', 'myavanaProfileAjax', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('myavana_profile_nonce'),
        'tryonNonce' => wp_create_nonce('myavana_tryon_nonce'),
        'userId' => $user_id
    ]);

    // Enqueue inline functionality that was moved to external file
    wp_enqueue_script('myavana-profile-inline-js', plugin_dir_url(dirname(__FILE__)) . 'assets/js/profile-inline-functionality.js', ['jquery', 'myavana-profile-js'], '1.0.0', true);
    ?>
         <script src="https://cdn.jsdelivr.net/npm/webcamjs@1.0.26/webcam.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/filepond-plugin-file-validate-type@1.2.8/dist/filepond-plugin-file-validate-type.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/filepond-plugin-file-validate-size@2.2.8/dist/filepond-plugin-file-validate-size.min.js"></script>
    <?php
    // Enqueue hair analysis JavaScript
    wp_enqueue_script('myavana-hair-analysis-js', plugin_dir_url(dirname(__FILE__)) . 'assets/js/profile-hair-analysis.js', ['jquery', 'myavana-profile-js'], '1.0.0', true);

    // Localize script for hair analysis
    wp_localize_script('myavana-hair-analysis-js', 'myavanaAjax', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('myavana_profile_nonce')
    ]);
    ?>
       
    </div>
    <?php
    return ob_get_clean();
}

?>