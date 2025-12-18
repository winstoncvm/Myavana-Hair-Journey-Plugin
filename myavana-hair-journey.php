<?php
/*
Plugin Name: Myavana Hair Journey
Plugin URI: https://myavana.com
Description: Your personalized hair care companion. Track your hair journey with AI-powered insights, progress photos, routines, goals, and community features. Includes gamification, daily check-ins, and smart recommendations.
Version: 2.4.7
Author: Myavana
Author URI: https://myavana.com
*/

defined('ABSPATH') or die('No script kiddies please!');

define('MYAVANA_DIR', plugin_dir_path(__FILE__));
define('MYAVANA_URL', plugin_dir_url(__FILE__));

// Include necessary files

require_once MYAVANA_DIR . 'includes/class-myavana-data-manager.php';
require_once MYAVANA_DIR . 'includes/shortcodes.php';

require_once MYAVANA_DIR . 'includes/extras.php';
require_once MYAVANA_DIR . 'includes/youzify-integration.php';
require_once MYAVANA_DIR . 'includes/ai-integration.php';
require_once MYAVANA_DIR . 'includes/myavana_admin_settings.php';
require_once MYAVANA_DIR . 'includes/myavana_websocket_proxy.php';
require_once MYAVANA_DIR . 'actions/hair-entries.php';
require_once MYAVANA_DIR . 'actions/dashboard-ajax-handlers.php';
require_once MYAVANA_DIR . 'actions/gamification-handlers.php';
require_once MYAVANA_DIR . 'actions/smart-entry-handlers.php';
require_once MYAVANA_DIR . 'actions/insight-handlers.php';
require_once MYAVANA_DIR . 'actions/goal-routine-handlers.php';
require_once MYAVANA_DIR . 'includes/myavana_database_setup.php';
require_once MYAVANA_DIR . 'includes/myavana_ajax_handlers.php';

require_once MYAVANA_DIR . 'includes/myavana-auth-system.php';
require_once MYAVANA_DIR . 'includes/error-handler.php';
require_once MYAVANA_DIR . 'includes/asset-optimizer.php';

// Include new advanced features
require_once MYAVANA_DIR . 'includes/ai-recommendations.php';
require_once MYAVANA_DIR . 'includes/social-features.php';
require_once MYAVANA_DIR . 'includes/photo-comparison.php';
require_once MYAVANA_DIR . 'includes/gamification.php';

// Include shortcode files
require_once MYAVANA_DIR . 'templates/login-shortcode.php';
require_once MYAVANA_DIR . 'templates/register-shortcode.php';
require_once MYAVANA_DIR . 'templates/profile-shortcode.php';
// require_once MYAVANA_DIR . 'templates/timeline-shortcode.php';
require_once MYAVANA_DIR . 'templates/entry-shortcode.php';
require_once MYAVANA_DIR . 'templates/tryon-shortcode.php';
require_once MYAVANA_DIR . 'templates/analytics-shortcode.php';
require_once MYAVANA_DIR . 'templates/test-shortcode.php';
require_once MYAVANA_DIR . 'templates/pages/home/home-one.php';
require_once MYAVANA_DIR . 'templates/pages/home/luxury-home.php';
require_once MYAVANA_DIR . 'templates/pages/hair-journey.php';
require_once MYAVANA_DIR . 'templates/widgets/hair-profile.php';
require_once MYAVANA_DIR . 'templates/hair-diary-timeline-shortcode.php';
require_once MYAVANA_DIR . 'templates/hair-diary.php';
require_once MYAVANA_DIR . 'templates/hair-offcanvas.php';
// require_once MYAVANA_DIR . 'templates/enhanced-timeline-shortcode.php';
// require_once MYAVANA_DIR . 'templates/improved-timeline-shortcode.php';
require_once MYAVANA_DIR . 'templates/advanced-dashboard-shortcode.php';
// require_once MYAVANA_DIR . 'templates/interactive-hair-diary-shortcode.php';

// require_once MYAVANA_DIR . 'templates/widgets/recent-activity.php';
// require_once MYAVANA_DIR . 'templates/widgets/quick-stats.php';
// require_once MYAVANA_DIR . 'templates/widgets/recommended-products.php';

// Initialize Auth System
if (function_exists('myavana_init_auth_system')) {
    $myavana_auth_system = myavana_init_auth_system();
}

// Force email from address to support@myavana.com
add_filter('wp_mail_from', function($email) {
    return 'support@myavana.com';
});

add_filter('wp_mail_from_name', function($name) {
    return 'MYAVANA';
});



class Myavana_Hair_Journey {
    private $shortcodes;
    private $extras;
    private $youzify;
    private $ai;

    public function __construct() {
        // Instantiate classes
        $this->shortcodes = new Myavana_Shortcodes();
        $this->extras = new Myavana_Extras();
        $this->youzify = new Myavana_Youzify();
        $this->ai = new Myavana_AI();
        

        // Register hooks
        register_activation_hook(__FILE__, [$this, 'activate']);
        add_action('init', [$this, 'register_post_types']);
        add_action('init', [$this->shortcodes, 'register_shortcodes']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_head', [$this, 'add_pwa_meta_tags']);
        add_action('admin_notices', [$this, 'debug_notices']);
        add_action('bp_init', [$this->youzify, 'register_youzify_tab']);
        add_action('user_register', [$this, 'create_default_profile']);
        add_action('wp_footer', [$this, 'show_preloader']);
    }

    public function activate() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'myavana_profiles';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            hair_journey_stage VARCHAR(255),
            hair_health_rating INT,
            life_journey_stage VARCHAR(255),
            birthday DATE,
            location VARCHAR(255),
            hair_type VARCHAR(50),
            hair_goals TEXT,
            PRIMARY KEY (id)
        ) $charset_collate;";
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        // Create pages
        $pages = [
            'Login' => '[myavana_login]',
            'Register' => '[myavana_register]',
            'Profile' => '[myavana_profile]',
            'Timeline' => '[myavana_timeline]',
            'Add Entry' => '[myavana_entry]',
            'Virtual Try-On' => '[myavana_tryon]',
            'Hair Insights' => '[myavana_analytics]'
        ];
        foreach ($pages as $title => $content) {
            if (!get_page_by_title($title)) {
                $page_id = wp_insert_post([
                    'post_title' => $title,
                    'post_content' => $content,
                    'post_status' => 'publish',
                    'post_type' => 'page',
                    'post_author' => 1
                ]);
                if (is_wp_error($page_id)) {
                    error_log('Myavana Hair Journey: Failed to create page ' . $title . ': ' . $page_id->get_error_message());
                }
            }
        }
        flush_rewrite_rules();
    }

    public function register_post_types() {
        register_post_type('hair_journey_entry', [
            'labels' => [
                'name' => __('Hair Journey Entries'),
                'singular_name' => __('Hair Journey Entry')
            ],
            'public' => false,
            'show_ui' => true,
            'supports' => ['title', 'editor', 'author', 'thumbnail', 'custom-fields'],
            'show_in_rest' => true,
            'capability_type' => 'post',
            'capabilities' => [
                'create_posts' => 'edit_posts'
            ],
            'map_meta_cap' => true
        ]);
    }

    public function add_pwa_meta_tags() {
        // PWA meta tags
        echo '<meta name="theme-color" content="#e7a690">' . "\n";
        echo '<meta name="background-color" content="#ffffff">' . "\n";
        echo '<meta name="apple-mobile-web-app-capable" content="yes">' . "\n";
        echo '<meta name="apple-mobile-web-app-status-bar-style" content="default">' . "\n";
        echo '<meta name="apple-mobile-web-app-title" content="MYAVANA">' . "\n";
        echo '<meta name="mobile-web-app-capable" content="yes">' . "\n";
        echo '<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">' . "\n";

        // PWA manifest
        echo '<link rel="manifest" href="' . MYAVANA_URL . 'manifest.json">' . "\n";

        // Apple touch icons
        echo '<link rel="apple-touch-icon" sizes="72x72" href="' . MYAVANA_URL . 'assets/images/icon-72x72.png">' . "\n";
        echo '<link rel="apple-touch-icon" sizes="96x96" href="' . MYAVANA_URL . 'assets/images/icon-96x96.png">' . "\n";
        echo '<link rel="apple-touch-icon" sizes="128x128" href="' . MYAVANA_URL . 'assets/images/icon-128x128.png">' . "\n";
        echo '<link rel="apple-touch-icon" sizes="144x144" href="' . MYAVANA_URL . 'assets/images/icon-144x144.png">' . "\n";
        echo '<link rel="apple-touch-icon" sizes="152x152" href="' . MYAVANA_URL . 'assets/images/icon-152x152.png">' . "\n";
        echo '<link rel="apple-touch-icon" sizes="192x192" href="' . MYAVANA_URL . 'assets/images/icon-192x192.png">' . "\n";
        echo '<link rel="apple-touch-icon" sizes="384x384" href="' . MYAVANA_URL . 'assets/images/icon-384x384.png">' . "\n";
        echo '<link rel="apple-touch-icon" sizes="512x512" href="' . MYAVANA_URL . 'assets/images/icon-512x512.png">' . "\n";

        // Microsoft tiles
        echo '<meta name="msapplication-TileImage" content="' . MYAVANA_URL . 'assets/images/icon-144x144.png">' . "\n";
        echo '<meta name="msapplication-TileColor" content="#e7a690">' . "\n";

        // Preload critical resources
        echo '<link rel="preload" href="' . MYAVANA_URL . 'assets/js/myavana-unified-core.js" as="script">' . "\n";
        echo '<link rel="preload" href="' . MYAVANA_URL . 'assets/css/myavana-styles.css" as="style">' . "\n";
        echo '<link rel="preload" href="https://fonts.googleapis.com/css2?family=Archivo:wght@400;600&family=Archivo+Black:wght@400&display=swap" as="style">' . "\n";
    }

    public function enqueue_scripts() {
        // Debug: Log that enqueue_scripts is being called
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Myavana: enqueue_scripts() called. User logged in: ' . (is_user_logged_in() ? 'YES' : 'NO'));
        }

        // Ensure jQuery is available for inline scripts printed by shortcodes/templates
        wp_enqueue_script('jquery');

         // Enqueue Google Fonts and Archivo Expanded
        wp_enqueue_style('myavana-fonts', 'https://fonts.googleapis.com/css2?family=Archivo:wght@400;600&display=swap', [], null);
        wp_enqueue_style('myavana-expanded-font', 'https://db.onlinewebfonts.com/c/79e533eca247728ccfc8113ddc2c56ca?family=Archivo+Expanded+Medium', [], null);
        wp_enqueue_style(
            'my-plugin-fonts',
            plugin_dir_url(__FILE__) . 'assets/css/fonts.css',
            [],
            '1.0'
        );

       
        // Enqueue TourGuideJS
        wp_enqueue_style('tourguide-stylesheet', 'https://unpkg.com/@sjmc11/tourguidejs/dist/css/tour.min.css', [], '1.0.5');
        wp_enqueue_script('tourguide-javascript', 'https://unpkg.com/@sjmc11/tourguidejs/dist/tour.js', [], '1.0.5', true);
        
        // Add Splide.js for sliders
        wp_enqueue_script('splide-js', 'https://cdn.jsdelivr.net/npm/@splidejs/splide@4.1.4/dist/js/splide.min.js', [], '4.1.4', true);
        wp_enqueue_style('splide-css', 'https://cdn.jsdelivr.net/npm/@splidejs/splide@4.1.4/dist/css/splide.min.css', [], '4.1.4');

        // Load custom stylesheets
        wp_enqueue_style('myavana-styles', MYAVANA_URL . 'assets/css/myavana-styles.css');
        wp_enqueue_style('analysis-components', MYAVANA_URL . 'assets/css/analysis-components.css', [], '1.0.0');
        wp_enqueue_style('analysis-forms', MYAVANA_URL . 'assets/css/analysis-forms.css', [], '1.0.0');
        wp_enqueue_style('analysis-offcanvas', MYAVANA_URL . 'assets/css/analysis-offcanvas.css');

        wp_enqueue_style('myavana-luxury-home', MYAVANA_URL . 'assets/css/luxury-home.css', [], '1.0.0');
        wp_enqueue_script('myavana-luxury-home', MYAVANA_URL . 'assets/js/luxury-home.js', ['jquery'], '1.0.0', true);

        // Enqueue unified core framework
        wp_enqueue_script('myavana-unified-core', MYAVANA_URL . 'assets/js/myavana-unified-core.js', ['jquery'], '1.0.0', true);
        wp_enqueue_script('myavana-scripts', MYAVANA_URL . 'assets/js/myavana-scripts.js', ['jquery', 'myavana-unified-core'], '2.0.0', true);
        
        // Localize scripts for both unified core and regular scripts
        wp_localize_script('myavana-unified-core', 'myavanaAjax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('myavana_nonce'),
            'user_id' => get_current_user_id(),
            'is_logged_in' => is_user_logged_in(),
            'plugin_url' => MYAVANA_URL,
            'websocket_url' => get_option('myavana_websocket_url', ''),
            'debug' => defined('WP_DEBUG') && WP_DEBUG
        ]);

        wp_localize_script('myavana-scripts', 'myavana', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('myavana_nonce')
        ]);
        // Only localize API keys if properly configured
        $openai_key = defined('MYAVANA_OPENAI_API_KEY') ? MYAVANA_OPENAI_API_KEY : '';
        $xai_key = defined('MYAVANA_XAI_API_KEY') ? MYAVANA_XAI_API_KEY : '';
        
        wp_localize_script('myavana-chatbot-scripts', 'myavanaData', [
            'nonce' => wp_create_nonce('myavana_chatbot_nonce'),
            'user_id' => get_current_user_id(),
            'ajax_url' => admin_url('admin-ajax.php'),
            'openai_realtime_api' => 'wss://api.openai.com/v1/realtime',
            'openai_api_key' => $openai_key,
            'xai_api_key' => $xai_key
        ]);

        // Hair Journey Page specific assets
        $this->enqueue_hair_journey_assets();
    }

    /**
     * Enqueue assets specifically for the Hair Journey page shortcode
     * Moved from shortcode to ensure proper loading order
     */
    private function enqueue_hair_journey_assets() {
        // Only load for logged-in users
        if (!is_user_logged_in()) {
            return;
        }

        // Debug log - always log when this method is called
        if (defined('WP_DEBUG') && WP_DEBUG) {
            global $post;
            $post_id = is_a($post, 'WP_Post') ? $post->ID : 'no-post';
            error_log('Myavana: enqueue_hair_journey_assets() called for post: ' . $post_id);
        }

        // Timeline & Calendar CSS
        wp_enqueue_style('myavana-new-timeline-hair-journey', MYAVANA_URL . 'assets/css/new-timeline.css', [], '1.0.0');
        wp_enqueue_style('myavana-calendar-hair-journey', MYAVANA_URL . 'assets/css/calendar.css', [], '1.0.0');
        wp_enqueue_style('myavana-new-timeline-offcanvas-hair-journey', MYAVANA_URL . 'assets/css/new-offcanvas.css', [], '1.0.0');
        wp_enqueue_style('myavana-analytics-css', MYAVANA_URL . 'assets/css/analytics.css', [], '1.0.0');
        wp_enqueue_style('myavana-sidebar-analytics', MYAVANA_URL . 'assets/css/sidebar-analytics.css', [], '1.0.0');
        wp_enqueue_style('myavana-sidebar-analytics2', MYAVANA_URL . 'assets/css/partials/sidebar-analytics.css', [], '1.0.0');
        wp_enqueue_style('myavana-slider-view', MYAVANA_URL . 'assets/css/slider-view.css', [], '1.0.0');
        wp_enqueue_style('myavana-sidebar-profile', MYAVANA_URL . 'assets/css/partials/sidebar-profile.css', [], '1.0.0');

        // Timeline & Calendar JS
        wp_enqueue_script('myavana-hair-timeline', MYAVANA_URL . 'assets/js/myavana-hair-timeline.js', ['jquery','splide-js','filepond'], '2.3.7', true);
        // wp_enqueue_script('myavana-hair-timeline', MYAVANA_URL . 'assets/js/myavana-hair-timeline.js', ['jquery','splide-js','filepond'], '2.3.7', true);

        // MYAVANA Timeline - Modular Architecture (v2.3.5)
        // Load modules in dependency order
        $timeline_version = '2.3.5';

        // 1. State Management (no dependencies)
        wp_enqueue_script('myavana-timeline-state', MYAVANA_URL . 'assets/js/timeline/timeline-state.js', [], $timeline_version, true);

        // 2. UI State (depends on State)
        wp_enqueue_script('myavana-timeline-ui', MYAVANA_URL . 'assets/js/timeline/timeline-ui-state.js', ['myavana-timeline-state'], $timeline_version, true);

        // 3. Offcanvas (depends on State)
        wp_enqueue_script('myavana-timeline-offcanvas', MYAVANA_URL . 'assets/js/timeline/timeline-offcanvas.js', ['myavana-timeline-state'], $timeline_version, true);

        // 4. Navigation (depends on State, Splide)
        wp_enqueue_script('myavana-timeline-navigation', MYAVANA_URL . 'assets/js/timeline/timeline-navigation.js', ['myavana-timeline-state', 'splide-js'], $timeline_version, true);

        // 5. List View (depends on State)
        wp_enqueue_script('myavana-timeline-list-view', MYAVANA_URL . 'assets/js/timeline/timeline-list-view.js', ['myavana-timeline-state'], $timeline_version, true);

        // 6. View (depends on State, Offcanvas)
        wp_enqueue_script('myavana-timeline-view', MYAVANA_URL . 'assets/js/timeline/timeline-view.js', ['myavana-timeline-state', 'myavana-timeline-offcanvas'], $timeline_version, true);

        // 7. OLD Forms (DISABLED - replaced by new form system)
        // wp_enqueue_script('myavana-timeline-forms', MYAVANA_URL . 'assets/js/timeline/timeline-forms.js', ['myavana-timeline-state', 'myavana-timeline-offcanvas', 'filepond'], $timeline_version, true);

        // 8. Filters (depends on State)
        wp_enqueue_script('myavana-timeline-filters', MYAVANA_URL . 'assets/js/timeline/timeline-filters.js', ['myavana-timeline-state'], $timeline_version, true);

        // 9. Comparison (depends on State)
        wp_enqueue_script('myavana-timeline-comparison', MYAVANA_URL . 'assets/js/timeline/timeline-comparison.js', ['myavana-timeline-state'], $timeline_version, true);

        // NEW: Clean JavaScript-based form system (Priority loading - before other modules)
        wp_enqueue_script('myavana-timeline-form-builder', MYAVANA_URL . 'assets/js/timeline/timeline-form-builder.js', ['jquery'], $timeline_version, true);
        wp_enqueue_script('myavana-timeline-forms-new', MYAVANA_URL . 'assets/js/timeline/timeline-forms-new.js', ['jquery', 'myavana-timeline-form-builder', 'myavana-timeline-state'], $timeline_version, true);

        // 10. Init/Orchestrator (depends on ALL modules)
        wp_enqueue_script('myavana-timeline-init', MYAVANA_URL . 'assets/js/timeline/timeline-init.js', [
            'jquery',
            'myavana-timeline-state',
            'myavana-timeline-ui',
            'myavana-timeline-offcanvas',
            'myavana-timeline-navigation',
            'myavana-timeline-list-view',
            'myavana-timeline-view',
            'myavana-timeline-forms-new',
            'myavana-timeline-filters',
            'myavana-timeline-comparison'
        ], $timeline_version, true);

        // Calendar functionality
        wp_enqueue_script('myavana-calendar-hair-journey-js', MYAVANA_URL . 'assets/js/calendar.js', ['jquery'], '1.0.0', true);

        // External CDN assets - properly enqueued
        wp_enqueue_style('tourguidejs-css', 'https://unpkg.com/@sjmc11/tourguidejs/dist/css/tour.min.css', [], '2.0.0');
        wp_enqueue_style('filepond-css', 'https://cdn.jsdelivr.net/npm/filepond@4.30.4/dist/filepond.min.css', [], '4.30.4');
        wp_enqueue_style('filepond-plugin-image-preview-css', 'https://cdn.jsdelivr.net/npm/filepond-plugin-image-preview@4.6.11/dist/filepond-plugin-image-preview.min.css', [], '4.6.11');
        wp_enqueue_style('select2-css', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', [], '4.1.0');
        wp_enqueue_script('select2-js', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', ['jquery'], '4.1.0', true);

        wp_enqueue_script('myavana-compare-analysis', MYAVANA_URL . 'assets/js/compare-analysis.js', ['jquery'], '1.0.0', true);

        // AI Analysis Modal (for Smart Entry button)
        wp_enqueue_script('myavana-ai-analysis-modal', MYAVANA_URL . 'assets/js/ai-analysis-modal.js', ['jquery'], '1.0.0', true);

        // Profile scripts
        wp_enqueue_script('myavana-profile-js', MYAVANA_URL . 'assets/js/profile-shortcode.js', ['jquery'], '1.0.0', true);
        wp_enqueue_script('myavana-profile-inline-js', MYAVANA_URL . 'assets/js/profile-inline-functionality.js', ['jquery', 'myavana-profile-js', 'splide-js'], '1.0.1', true);

        // Chart.js for analytics
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js', [], '4.4.0', true);
        wp_enqueue_script('myavana-analytics-js', MYAVANA_URL . 'assets/js/analytics.js', ['jquery', 'chart-js'], '1.0.0', true);
        wp_enqueue_script('myavana-analytics-export', MYAVANA_URL . 'assets/js/analytics-export.js', ['jquery'], '1.0.0', true);
        wp_enqueue_script('myavana-sidebar-analytics', MYAVANA_URL . 'assets/js/sidebar-analytics.js', ['jquery', 'chart-js'], '1.0.0', true);

        // GAMIFICATION SYSTEM (Phase 1: Daily Check-ins, Streaks, Badges, Points)
        wp_enqueue_style('myavana-gamification', MYAVANA_URL . 'assets/css/gamification.css', [], '1.0.0');
        wp_enqueue_script('myavana-gamification', MYAVANA_URL . 'assets/js/gamification.js', ['jquery'], '1.0.0', true);

        // SMART ENTRY SYSTEM (Phase 2: Camera-first AI workflow)
        wp_enqueue_style('myavana-smart-entry', MYAVANA_URL . 'assets/css/smart-entry.css', [], '1.0.0');
        wp_enqueue_script('myavana-smart-entry', MYAVANA_URL . 'assets/js/smart-entry.js', ['jquery'], '1.0.0', true);

        // PREMIUM 3-STEP ENTRY FORM (Luxury UI with interactive validation)
        wp_enqueue_style('myavana-premium-entry-form', MYAVANA_URL . 'assets/css/premium-entry-form.css', [], '1.0.0');
        wp_enqueue_script('myavana-premium-entry-form', MYAVANA_URL . 'assets/js/premium-entry-form.js', ['jquery'], '1.0.0', true);

        // PREMIUM GOAL FORM (Luxury UI for goals)
        wp_enqueue_script('myavana-premium-goal-form', MYAVANA_URL . 'assets/js/premium-goal-form.js', ['jquery'], '1.0.0', true);

        // PREMIUM ROUTINE FORM (Luxury UI for routines)
        wp_enqueue_script('myavana-premium-routine-form', MYAVANA_URL . 'assets/js/premium-routine-form.js', ['jquery'], '1.0.0', true);

        // CRITICAL FIXES (v2.3.6) - Centralized bug fixes and enhancements
        wp_enqueue_script('myavana-hair-journey-fixes', MYAVANA_URL . 'assets/js/hair-journey-fixes.js', ['jquery', 'myavana-unified-core'], '2.3.6', true);

        // PROFILE EDIT FIXES (v2.3.6) - Fix profile edit loading, saving, and closing
        wp_enqueue_style('myavana-profile-edit-fixes', MYAVANA_URL . 'assets/css/profile-edit-fixes.css', [], '2.3.6');
        wp_enqueue_script('myavana-profile-edit-fixes', MYAVANA_URL . 'assets/js/profile-edit-fixes.js', ['jquery', 'myavana-hair-journey-fixes'], '2.3.6', true);

        // PROGRESSIVE INSIGHTS (Phase 3: Milestone-based analytics unlocking)
        wp_enqueue_style('myavana-progressive-insights', MYAVANA_URL . 'assets/css/progressive-insights.css', [], '1.0.0');
        wp_enqueue_script('myavana-progressive-insights', MYAVANA_URL . 'assets/js/progressive-insights.js', ['jquery'], '1.0.0', true);

        // Hair Analysis
        $debug_version = defined('WP_DEBUG') && WP_DEBUG ? time() : '1.0.1';
        wp_enqueue_script(
            'myavana-hair-analysis-js',
            MYAVANA_URL . 'assets/js/profile-hair-analysis.js',
            ['jquery', 'myavana-profile-js', 'filepond', 'filepond-plugin-file-validate-type', 'filepond-image-preview'],
            $debug_version,
            true
        );
        wp_add_inline_script('myavana-hair-analysis-js', 'console.log("[Hair Analysis] Script registered with WordPress");', 'before');

        // FilePond and plugins
        wp_enqueue_style('filepond', 'https://unpkg.com/filepond@4.30.4/dist/filepond.min.css', [], '4.30.4');
        wp_enqueue_style('filepond-image-preview', 'https://unpkg.com/filepond-plugin-image-preview@4.6.11/dist/filepond-plugin-image-preview.min.css', [], '4.6.11');
        wp_enqueue_script('filepond', 'https://unpkg.com/filepond@4.30.4/dist/filepond.min.js', [], '4.30.4', true);
        wp_enqueue_script('filepond-plugin-file-validate-type', 'https://unpkg.com/filepond-plugin-file-validate-type/dist/filepond-plugin-file-validate-type.js', ['filepond'], '1.2.8', true);
        wp_enqueue_script('filepond-image-preview', 'https://unpkg.com/filepond-plugin-image-preview@4.6.11/dist/filepond-plugin-image-preview.min.js', ['filepond'], '4.6.11', true);
        wp_enqueue_script('filepond-validate-size', 'https://unpkg.com/filepond-plugin-image-validate-size@1.2.6/dist/filepond-plugin-image-validate-size.min.js', ['filepond'], '1.2.6', true);

        // Localize scripts
        $user_id = get_current_user_id();
        $ajax_data = [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('myavana_profile_nonce')
        ];

        wp_localize_script('myavana-profile-js', 'myavanaProfileAjax', array_merge($ajax_data, [
            'tryonNonce' => wp_create_nonce('myavana_tryon_nonce'),
            'userId' => $user_id
        ]));

        wp_localize_script('myavana-hair-analysis-js', 'myavanaAjax', $ajax_data);

        wp_localize_script('myavana-analytics-js', 'myavanaAnalytics', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('myavana_analytics'),
            'user_id' => $user_id
        ]);

        // Localize for MODULAR timeline system (timeline-view.js and other modules)
        wp_localize_script('myavana-timeline-view', 'myavanaTimelineSettings', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'ajaxurl' => admin_url('admin-ajax.php'), // Backward compatibility
            'getEntriesNonce' => wp_create_nonce('myavana_get_entries'),
            'getEntryDetailsNonce' => wp_create_nonce('myavana_get_entry_details'),
            'getGoalDetailsNonce' => wp_create_nonce('myavana_get_goal_details'),
            'updateEntryNonce' => wp_create_nonce('myavana_update_entry'),
            'deleteEntryNonce' => wp_create_nonce('myavana_delete_entry'),
            'addEntryNonce' => wp_create_nonce('myavana_add_entry'),
            'updateGoalNonce' => wp_create_nonce('myavana_update_goal'),
            'addGoalNonce' => wp_create_nonce('myavana_add_goal'),
            'updateRoutineNonce' => wp_create_nonce('myavana_update_routine'),
            'addRoutineNonce' => wp_create_nonce('myavana_add_routine'),
            'nonce' => wp_create_nonce('myavana_get_entry_details'), // Fallback generic nonce
            'autoStartTimeline' => (isset($_GET['start']) && $_GET['start'] === '1')
        ]);

        // GAMIFICATION SETTINGS
        wp_localize_script('myavana-gamification', 'myavanaGamificationSettings', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('myavana_gamification'),
            'userId' => $user_id
        ]);

        // Debug: Add inline script to verify loading
        wp_add_inline_script('jquery', '
            console.log("[Myavana] Hair Journey Assets Enqueued");
            console.log("[Myavana] Plugin URL: " + "' . MYAVANA_URL . '");
            console.log("[Myavana] User ID: " + ' . get_current_user_id() . ');
        ', 'after');

        wp_add_inline_script('myavana-hair-timeline', 'console.log("[Myavana] Hair Timeline script loaded");', 'after');
        /*
        wp_add_inline_script('myavana-header-sidebar', 'console.log("[Myavana] Header Sidebar script loaded");', 'after');
        wp_add_inline_script('myavana-timeline-area', 'console.log("[Myavana] Timeline Area script loaded");', 'after');
        wp_add_inline_script('myavana-view-offcanvas', 'console.log("[Myavana] View Offcanvas script loaded");', 'after');
        */
        wp_add_inline_script('myavana-new-timeline-hair-journey', 'console.log("[Myavana] New Timeline script loaded");', 'after');
        wp_add_inline_script('myavana-calendar-hair-journey-js', 'console.log("[Myavana] Calendar script loaded");', 'after');
        wp_add_inline_script('myavana-profile-js', 'console.log("[Myavana] Profile script loaded");', 'after');
    }

    public function show_preloader() {
		?>

            <div class="myavana-loader-container" id="myavanaLoader">
                <div class="myavana-loader">
                    <div class="box">
                        <div class="logo">
                            <img src="https://6vt.d95.myftpupload.com/wp-content/uploads/2025/06/Signature-M-blueberry@2x.png" alt="logo" />
                        </div>
                    </div>
                    <div class="box"></div>
                    <div class="box"></div>
                    <div class="box"></div>
                    <div class="box"></div>
                </div>
                <div class="loader-tips">
                    <div class="loader-tip"></div>
                </div>
            </div>
            <script>
                jQuery(document).ready(function($) {
                    const tips = [
                        'Moisturize daily to keep hair hydrated.',
                        'Use a wide-tooth comb to detangle wet hair.',
                        'Trim ends every 6-8 weeks to prevent split ends.',
                        'Avoid heat styling on damp hair.',
                        'Massage your scalp to boost circulation.',
                        'Use a silk pillowcase to reduce frizz.',
                        'Apply a leave-in conditioner for extra moisture.',
                        'Protect hair from sun exposure with a hat.',
                        'Deep condition weekly for stronger strands.',
                        'Avoid tight hairstyles to prevent breakage.',
                        'Use sulfate-free shampoos to preserve natural oils.',
                        'Incorporate protein treatments for hair strength.',
                        'Stay hydrated to promote healthy hair growth.',
                        'Use a clarifying shampoo monthly to remove buildup.',
                        'Apply oil to ends to seal in moisture.',
                        'Avoid brushing curly hair when dry.',
                        'Use a microfiber towel to reduce drying time.',
                        'Limit chemical treatments to prevent damage.',
                        'Eat a balanced diet rich in vitamins for hair health.',
                        'Test products on a small section before full use.'
                    ];

                    function setRandomTip() {
                        const tipElement = $('.loader-tip');
                        const currentTip = tipElement.text();
                        let newTip;
                        do {
                            newTip = tips[Math.floor(Math.random() * tips.length)];
                        } while (newTip === currentTip && tips.length > 1);
                        tipElement.text(newTip).css('animation', 'none').show();
                        setTimeout(() => {
                            tipElement.css('animation', 'slideUp 5s ease-in-out infinite');
                        }, 10);
                    }
                    $('body').prepend($(".mask"));
                    setRandomTip();
                    setInterval(setRandomTip, 5000);

                    // Hide loader with multiple fallback methods
                    let loaderHidden = false;

                    function hideLoader() {
                        if (loaderHidden) return;
                        loaderHidden = true;
                        console.log('[Loader] Hiding loader');
                        $("#myavanaLoader").fadeOut(300, function() {
                            $(this).remove();
                        });
                        $(".mask").fadeOut(500, function() {
                            $(this).remove();
                        });
                    }

                    // Method 1: Window load event
                    $(window).on('load', function() {
                        setTimeout(hideLoader, 500);
                    });

                    // Method 2: Document ready with timeout fallback
                    $(document).ready(function() {
                        setTimeout(hideLoader, 3000); // Hide after 3s max
                    });

                    // Method 3: Manual trigger for other scripts
                    window.hideMyavanaLoader = hideLoader;

                    // Emergency loader killer (for console debugging)
                    window.killLoader = function() {
                        console.warn('[Loader] EMERGENCY KILL ACTIVATED');
                        $('#myavanaLoader, .mask, .myavana-loader-container').remove();
                        loaderHidden = true;
                    };
                });
            </script>
        <?php
	}

    public function debug_notices() {
        if (current_user_can('manage_options')) {
            global $wp_query;
            if (isset($wp_query->post->post_content) && strpos($wp_query->post->post_content, '[myavana_') !== false) {
                echo '<div class="notice notice-error"><p>Myavana Hair Journey: Shortcode detected but not rendering. Ensure plugin is active and shortcodes are registered.</p></div>';
            }
        }
    }

    public function create_default_profile($user_id) {
        global $wpdb;
        $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}myavana_profiles WHERE user_id = %d", $user_id));
        if (!$exists) {
            $result = $wpdb->insert(
                $wpdb->prefix . 'myavana_profiles',
                [
                    'user_id' => $user_id,
                    'hair_journey_stage' => 'Not set',
                    'hair_health_rating' => 5,
                    'life_journey_stage' => 'Not set',
                    'birthday' => '',
                    'location' => '',
                    'hair_type' => '',
                    'hair_goals' => ''
                ]
            );
            if ($result === false) {
                error_log('Myavana Hair Journey: Failed to create default profile for user ' . $user_id);
            }
        }
    }

}

new Myavana_Hair_Journey();


?>
