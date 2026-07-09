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
    $show_welcome_banner = isset($_GET['welcome']) && wp_unslash($_GET['welcome']) === '1';
    $start_first_entry = isset($_GET['start_entry']) && wp_unslash($_GET['start_entry']) === '1';
    $first_entry_prefill = [];

    if ($start_first_entry) {
        $saved_prefill = get_user_meta($user_id, 'myavana_first_entry_prefill', true);
        if (is_array($saved_prefill)) {
            $first_entry_prefill = $saved_prefill;
        }
        delete_user_meta($user_id, 'myavana_first_entry_prefill');
    }

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
    <div class="hair-journey-container journey-dashboard-layout" data-theme="light">
        
    <?php
    // The new sticky sidebar
    if ( file_exists( $partials_dir . '/journey-sidebar.php' ) ) {
        include $partials_dir . '/journey-sidebar.php';
    }
    ?>
    <div class="journey-main-content">
    <?php
    // Note: All assets (TourGuideJS, FilePond, Select2) are now properly enqueued
    // in the main plugin file via enqueue_hair_journey_assets() method
    if ( file_exists( $partials_dir . '/header-and-sidebar.php' ) ) {
        include $partials_dir . '/header-and-sidebar.php';
    }
    if ( file_exists( $partials_dir . '/timeline-area.php' ) ) {
        include $partials_dir . '/timeline-area.php';
    }
    ?>
    </div> <!-- /.journey-main-content -->
    <?php
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
    window.myavanaJourneyLaunchState = <?php echo wp_json_encode([
        'showWelcome' => $show_welcome_banner,
        'startEntry' => $start_first_entry,
        'entryPrefill' => $first_entry_prefill,
    ]); ?>;
    console.log('[HairJourney] Entry cache loaded:', Object.keys(window.myavanaEntryCache).length, 'entries');

    document.addEventListener('DOMContentLoaded', function () {
        const launchState = window.myavanaJourneyLaunchState || {};
        if (!launchState.startEntry) {
            return;
        }

        const launchEntryFlow = function (attempt) {
            if (typeof window.createEntry === 'function') {
                window.createEntry(launchState.entryPrefill || {});

                if (window.history && typeof window.history.replaceState === 'function') {
                    const nextUrl = new URL(window.location.href);
                    nextUrl.searchParams.delete('welcome');
                    nextUrl.searchParams.delete('start_entry');
                    window.history.replaceState({}, document.title, nextUrl.toString());
                }
                return;
            }

            if ((attempt || 0) < 8) {
                window.setTimeout(function () {
                    launchEntryFlow((attempt || 0) + 1);
                }, 350);
            }
        };

        window.setTimeout(function () {
            launchEntryFlow(0);
        }, 700);
    });
    </script>
    <?php

    return ob_get_clean();
}
