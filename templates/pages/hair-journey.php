<?php
/**
 * Renders the Myavana Hair Journey page via shortcode.
 * This file now includes smaller partials under templates/pages/partials/ for maintainability.
 */
function myavana_hair_journey_page_shortcode($atts = [], $content = null){
    // Check login
    $is_logged_in = is_user_logged_in();
    $current_user = wp_get_current_user();

    if (!$is_logged_in) {
        return '<div class="hair-journey-container"><div class="calendar-empty-hjn"><h2>Please sign in to view your hair journey</h2></div></div>';
    }

    // Parse attributes
    $atts = shortcode_atts([
        'show_progress' => 'true',
        'show_stats' => 'true',
        'autoplay' => 'false',
        'entries_per_page' => '10'
    ], (array) $atts, 'myavana_hair_journey_timeline');

    // Fetch ALL data ONCE using centralized data manager
    $shared_data = Myavana_Data_Manager::get_journey_data($current_user->ID);

    // Extract commonly used variables for backward compatibility
    $user_id = $current_user->ID;
    $user_data = $shared_data['user_data'];
    $user_profile = $shared_data['profile'];
    $typeform_data = $shared_data['typeform_data'];
    $hair_goals = $shared_data['hair_goals'];
    $about_me = $shared_data['about_me'];
    $analysis_history = $shared_data['analysis_history'];
    $current_routine = $shared_data['current_routine'];
    $user_stats = $shared_data['stats'];

    // Analysis limit info
    $analysis_limit_info = $shared_data['analysis_limit_info'];
    $analysis_limit = $analysis_limit_info['limit'];
    $analysis_count = $analysis_limit_info['count'];
    $can_analyze = $analysis_limit_info['can_analyze'];

    // Include partials (HTML markup)
    // Note: JavaScript settings and nonces are localized in main plugin file (myavana-hair-journey.php)
    $partials_dir = __DIR__ . '/partials';

    ob_start();
    ?>
    <div class="hair-journey-container" data-theme="light">
        <!-- Luxury Navigation -->
        <nav class="myavana-luxury-nav">
            <div class="myavana-luxury-nav-container">
                <a href="<?php echo home_url(); ?>" class="myavana-luxury-logo">
                    <div class="myavana-logo-section">
                        <img src="<?php echo esc_url(home_url()); ?>/wp-content/plugins/myavana-hair-journey/assets/images/myavana-primary-logo.png"
                            alt="Myavana Logo" class="myavana-logo" />
                    </div>
                </a>

                <?php if (!$is_logged_in): ?>
                    <!-- GUEST NAV -->
                    <div class="myavana-luxury-nav-menu">
                        <a href="#features" class="myavana-luxury-nav-link">Features</a>
                        <a href="#how-it-works" class="myavana-luxury-nav-link">How It Works</a>
                        <a href="#" onclick="showMyavanaModal('login'); return false;" class="myavana-luxury-nav-link myavana-nav-signin-mobile">
                            Sign In
                        </a>
                    </div>

                    <div class="myavana-luxury-nav-actions">
                        <button class="myavana-luxury-btn-secondary" onclick="showMyavanaModal('login')">Sign In</button>
                        <button class="myavana-luxury-btn-primary" onclick="showMyavanaModal('register')">Start Your Journey</button>
                    </div>

                <?php else: ?>
                    <!-- LOGGED-IN NAV -->
                    <div class="myavana-luxury-nav-menu" id="mainNavMenu">
                        <a href="/hair-journey/" class="myavana-luxury-nav-link">My Hair Journey</a>
                        <!-- <a href="/members/admin/hair_insights/" class="myavana-luxury-nav-link">Analytics</a> -->
                        <a style="cursor: pointer;" class="myavana-luxury-nav-link" onclick="createGoal()">+ Goal</a>
                            <a style="cursor: pointer;" class="myavana-luxury-nav-link" onclick="createRoutine()">+ Routine</a>
                            <a style="cursor: pointer;" class="myavana-luxury-nav-link" onclick="openAIAnalysisModal()">Smart Entry</a>
                            <a style="cursor: pointer;" class="myavana-luxury-nav-link" onclick="createEntry()">+ Entry</a>
                        <!-- Action Buttons - Desktop -->
                        <!-- <div class="myavana-luxury-nav-action-buttons desktop-only">
                           
                        </div> -->

                        <!-- Logout always visible on desktop -->
                        <a href="<?php echo wp_logout_url(home_url()); ?>" class="myavana-luxury-nav-link myavana-nav-logout-desktop">
                            Logout
                        </a>
                    </div>

                    

                    <!-- Mobile Menu Toggle -->
                    <!-- CORRECT — only jQuery handles it -->
                    <button class="myavana-luxury-mobile-toggle" aria-label="Toggle menu">
                        <span></span><span></span><span></span>
                    </button>
                <?php endif; ?>

                <!-- MOBILE SLIDE-OUT MENU (only for logged-in users) -->
                <?php if ($is_logged_in): ?>
                <div class="myavana-mobile-menu-overlay" id="mobileMenuOverlay" onclick="toggleMobileMenu()"></div>
                <div class="myavana-mobile-menu-panel" id="mobileMenuPanel">
                    <div class="mobile-menu-header">
                        <div class="mobile-menu-user">
                            <img src="<?php echo get_avatar_url($current_user->ID, ['size' => 60]); ?>" alt="Avatar" class="mobile-menu-avatar">
                            <div>
                                <strong><?php echo esc_html($current_user->display_name); ?></strong>
                                <small>Welcome back!</small>
                            </div>
                        </div>
                    </div>

                    <div class="mobile-menu-links">
                        <a href="/hair-journey/">My Hair Journey</a>
                        <a href="/members/admin/hair_insights/">Analytics</a>
                        <hr>
                        <button type="button" class="mobile-menu-action" onclick="createGoal(); toggleMobileMenu()">+ Goal</button>
                        <button type="button" class="mobile-menu-action" onclick="createRoutine(); toggleMobileMenu()">+ Routine</button>
                        <button type="button" class="mobile-menu-action smart" onclick="openAIAnalysisModal(); toggleMobileMenu()">Smart Entry</button>
                        <button type="button" class="mobile-menu-action primary" onclick="createEntry(); toggleMobileMenu()">+ Entry</button>
                        <hr>
                        <a href="<?php echo wp_logout_url(home_url()); ?>" class="mobile-menu-logout">Logout</a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </nav>
    <?php
    // Note: All assets (TourGuideJS, FilePond, Select2) are now properly enqueued
    // in the main plugin file via enqueue_hair_journey_assets() method
    if ( file_exists( $partials_dir . '/header-and-sidebar.php' ) ) {
        include $partials_dir . '/header-and-sidebar.php';
    }
    if ( file_exists( $partials_dir . '/timeline-area.php' ) ) {
        include $partials_dir . '/timeline-area.php';
    }
    // Include the detailed view offcanvas (entry/goal/routine) used by the new-timeline view handlers
    if ( file_exists( $partials_dir . '/view-offcanvas.php' ) ) {
        include $partials_dir . '/view-offcanvas.php';
    }
    // Include the create/edit offcanvas for adding/editing entries, goals, and routines
    if ( file_exists( $partials_dir . '/create-offcanvas.php' ) ) {
        include $partials_dir . '/create-offcanvas.php';
    }
    ?>
    <!-- Add this once to your page (e.g., in footer or after script) -->
    <input type="file" id="globalImageInput" accept="image/*" multiple style="display: none;">
    </div>
    <?php

    // Output entry data cache for JavaScript (prevents AJAX 400 errors)
    $entries_args = [
        'post_type' => 'hair_journey_entry',
        'author' => $user_id,
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'orderby' => 'post_date',
        'order' => 'DESC',
    ];
    $entries_for_cache = get_posts($entries_args);
    $entry_cache = [];

    foreach ($entries_for_cache as $entry) {
        $post_id = $entry->ID;
        $thumbnail = get_the_post_thumbnail_url($post_id, 'large');
        $rating = get_post_meta($post_id, 'health_rating', true);
        $mood = get_post_meta($post_id, 'mood_demeanor', true);
        $products = get_post_meta($post_id, 'products_used', true);

        $entry_cache[$post_id] = [
            'id' => $post_id,
            'entry_id' => $post_id,
            'title' => get_the_title($post_id),
            'entry_date' => get_the_date('Y-m-d', $post_id),
            'content' => $entry->post_content,
            'image' => $thumbnail ?: '',
            'rating' => intval($rating) ?: null,
            'mood' => $mood ?: '',
            'products' => $products ?: '',
        ];
    }
    ?>
    <script>
    // Pre-loaded entry data cache - avoids AJAX calls
    window.myavanaEntryCache = <?php echo json_encode($entry_cache); ?>;
    console.log('[HairJourney] Entry cache loaded:', Object.keys(window.myavanaEntryCache).length, 'entries');
    </script>
    <?php

    return ob_get_clean();
}
