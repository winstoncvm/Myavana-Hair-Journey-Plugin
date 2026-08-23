<?php
/*
 Plugin Name: Myavana Hair Journey
 Plugin URI: https://myavana.com
 Description: Your personalized hair care companion. Track your hair journey with AI-powered insights, progress photos, routines, goals, and community features. Includes gamification, daily check-ins, and smart recommendations.
 Version: 2.7.0
 Author: Myavana
 Author URI: https://myavana.com
 */

defined('ABSPATH') or die('No script kiddies please!');

define('MYAVANA_DIR', plugin_dir_path(__FILE__));
define('MYAVANA_URL', plugin_dir_url(__FILE__));
define('MYAVANA_HAIR_JOURNEY_PLUGIN_FILE', __FILE__);

// Include necessary files

require_once MYAVANA_DIR . 'includes/class-myavana-data-manager.php';
require_once MYAVANA_DIR . 'includes/shortcodes.php';

require_once MYAVANA_DIR . 'includes/extras.php';
require_once MYAVANA_DIR . 'includes/youzify-integration.php';
require_once MYAVANA_DIR . 'includes/ai-integration.php';
require_once MYAVANA_DIR . 'includes/ai-integration.php';
require_once MYAVANA_DIR . 'includes/class-myavana-analytics-model.php';
require_once MYAVANA_DIR . 'includes/class-myavana-admin-controller.php';
require_once MYAVANA_DIR . 'includes/admin-portal/class-myavana-admin-portal-permissions.php';
require_once MYAVANA_DIR . 'includes/admin-portal/class-myavana-admin-portal-audit-log.php';
require_once MYAVANA_DIR . 'includes/admin-portal/class-myavana-admin-portal.php';
$myavana_admin = new Myavana_Admin_Controller();
$myavana_admin_portal = new Myavana_Admin_Portal();
require_once MYAVANA_DIR . 'includes/myavana_admin_settings.php';
require_once MYAVANA_DIR . 'includes/myavana-gamification-admin.php';
require_once MYAVANA_DIR . 'includes/myavana_websocket_proxy.php';
require_once MYAVANA_DIR . 'actions/hair-entries.php';
require_once MYAVANA_DIR . 'actions/dashboard-ajax-handlers.php';
require_once MYAVANA_DIR . 'actions/gamification-handlers.php';
require_once MYAVANA_DIR . 'actions/smart-entry-handlers.php';
require_once MYAVANA_DIR . 'actions/insight-handlers.php';
require_once MYAVANA_DIR . 'actions/goal-routine-handlers.php';
require_once MYAVANA_DIR . 'actions/community-entry-sharing-handlers.php';
require_once MYAVANA_DIR . 'includes/myavana_database_setup.php';
require_once MYAVANA_DIR . 'includes/myavana_ajax_handlers.php';

require_once MYAVANA_DIR . 'includes/myavana-auth-system.php';
require_once MYAVANA_DIR . 'includes/error-handler.php';
require_once MYAVANA_DIR . 'includes/asset-optimizer.php';
require_once MYAVANA_DIR . 'includes/site-intelligence-ai.php';
require_once MYAVANA_DIR . 'includes/routine-tracking.php';

// Include new advanced features
require_once MYAVANA_DIR . 'includes/ai-recommendations.php';
require_once MYAVANA_DIR . 'includes/social-features.php';
require_once MYAVANA_DIR . 'includes/photo-comparison.php';
require_once MYAVANA_DIR . 'includes/gamification.php';
require_once MYAVANA_DIR . 'includes/community-integration.php';
require_once MYAVANA_DIR . 'includes/share-to-community.php';

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
require_once MYAVANA_DIR . 'templates/pages/goals.php';
require_once MYAVANA_DIR . 'templates/pages/routines.php';
require_once MYAVANA_DIR . 'templates/pages/legal.php';
require_once MYAVANA_DIR . 'templates/widgets/hair-profile.php';
require_once MYAVANA_DIR . 'templates/hair-diary-timeline-shortcode.php';
require_once MYAVANA_DIR . 'templates/hair-diary.php';
require_once MYAVANA_DIR . 'templates/hair-offcanvas.php';

//community
require_once MYAVANA_DIR . 'templates/pages/community/community-feed.php';
require_once MYAVANA_DIR . 'templates/pages/community/community-shortcodes.php';

// Unified Profile Page
require_once MYAVANA_DIR . 'templates/pages/unified-profile.php';

// Community Improvements - Database & AJAX Handlers
require_once MYAVANA_DIR . 'includes/myavana-ci-database.php';
require_once MYAVANA_DIR . 'actions/myavana-ci-handlers.php';

// Unified Profile - AJAX Handlers
require_once MYAVANA_DIR . 'actions/myavana-up-handlers.php';
// require_once MYAVANA_DIR . 'templates/enhanced-timeline-shortcode.php';
// require_once MYAVANA_DIR . 'templates/improved-timeline-shortcode.php';
require_once MYAVANA_DIR . 'templates/advanced-dashboard-shortcode.php';
// require_once MYAVANA_DIR . 'templates/interactive-hair-diary-shortcode.php';

// require_once MYAVANA_DIR . 'templates/widgets/recent-activity.php';
// require_once MYAVANA_DIR . 'templates/widgets/quick-stats.php';
// require_once MYAVANA_DIR . 'templates/widgets/recommended-products.php';

// Auth system initializes itself via its own `plugins_loaded` hook
// (see bottom of includes/myavana-auth-system.php). Do not also call
// myavana_init_auth_system() here — doing so instantiated Myavana_Auth_System
// twice per request, which double-registered every hook on that class
// (duplicate auth modal markup, duplicate onboarding overlay, and the
// register/login AJAX handlers running twice per submission).

// Force email from address to support@myavana.com
add_filter('wp_mail_from', function ($email) {
    return 'support@myavana.com';
});

add_filter('wp_mail_from_name', function ($name) {
    return 'MYAVANA';
});



class Myavana_Hair_Journey
{
    private $shortcodes;
    private $extras;
    private $youzify;
    private $ai;
    private $global_nav_rendered = false;

    public function __construct()
    {
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
        add_action('admin_init', [$this, 'maybe_seed_legal_pages']);
        add_action('admin_init', [$this, 'maybe_sync_app_pages']);
        add_action('bp_init', [$this->youzify, 'register_youzify_tab']);
        add_action('user_register', [$this, 'create_default_profile']);
        add_action('wp_footer', [$this, 'show_preloader']);
        add_action('wp_body_open', [$this, 'render_global_navbar'], 5);
        add_action('wp_footer', [$this, 'render_global_navbar_fallback'], 5);
        add_action('wp_footer', [$this, 'render_global_mobile_tabs'], 30);
        add_action('template_redirect', [$this, 'enforce_protected_page_access'], 1);
        add_filter('logout_redirect', [$this, 'force_logout_redirect'], 10, 3);
        add_filter('body_class', [$this, 'add_app_body_class']);
    }

    public function activate()
    {
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

        $this->sync_app_pages();
        $this->sync_navigation_menus();
        flush_rewrite_rules();

        Myavana_Admin_Portal::activate();
    }

    private function get_default_pages()
    {
        return [
            ['title' => 'Login', 'slug' => 'login', 'content' => '[myavana_login]'],
            ['title' => 'Register', 'slug' => 'register', 'content' => '[myavana_register]'],
            [
                'title' => 'Profile',
                'slug' => 'profile',
                'content' => '[myavana_unified_profile]',
                'shortcodes' => ['myavana_unified_profile', 'myavana_profile'],
            ],
            [
                'title' => 'My Timeline',
                'slug' => 'hair-journey',
                'content' => '[myavana_hair-journey-page]',
                'legacy_slugs' => ['timeline'],
                'legacy_titles' => ['Timeline', 'My Hair Journey'],
                'shortcodes' => ['myavana_hair-journey-page', 'myavana_timeline'],
            ],
            [
                'title' => 'Community',
                'slug' => 'community',
                'content' => '[myavana_community_feed]',
                'shortcodes' => ['myavana_community_feed'],
            ],
            ['title' => 'Goals', 'slug' => 'goals', 'content' => '[myavana_goals_page]'],
            ['title' => 'Routines', 'slug' => 'routines', 'content' => '[myavana_routines_page]'],
            ['title' => 'Virtual Try-On', 'slug' => 'virtual-try-on', 'content' => '[myavana_tryon]'],
            ['title' => 'Privacy Policy', 'slug' => 'privacy', 'content' => '[myavana_privacy_policy]'],
            ['title' => 'Terms of Service', 'slug' => 'terms', 'content' => '[myavana_terms]'],
        ];
    }

    public function maybe_sync_app_pages()
    {
        if (!is_admin() || !current_user_can('manage_options')) {
            return;
        }

        $this->sync_app_pages();
        $this->sync_navigation_menus();
    }

    private function sync_app_pages()
    {
        foreach ($this->get_default_pages() as $page) {
            $existing = $this->find_page_for_definition($page);
            $payload = [
                'post_title' => $page['title'],
                'post_name' => $page['slug'],
                'post_content' => $page['content'],
                'post_status' => 'publish',
                'post_type' => 'page',
            ];

            if ($existing) {
                $payload['ID'] = $existing->ID;
                wp_update_post($payload);
            } else {
                $payload['post_author'] = get_current_user_id() ?: 1;
                wp_insert_post($payload);
            }
        }

        $this->retire_legacy_pages();
    }

    private function find_page_for_definition($page)
    {
        $slugs = array_values(array_filter(array_merge(
            [$page['slug'] ?? ''],
            $page['legacy_slugs'] ?? []
        )));

        foreach ($slugs as $slug) {
            $existing = get_page_by_path($slug, OBJECT, 'page');
            if ($existing instanceof WP_Post) {
                return $existing;
            }
        }

        $titles = array_values(array_filter(array_merge(
            [$page['title'] ?? ''],
            $page['legacy_titles'] ?? []
        )));

        foreach ($titles as $title) {
            $existing = get_page_by_title($title, OBJECT, 'page');
            if ($existing instanceof WP_Post) {
                return $existing;
            }
        }

        $shortcodes = $page['shortcodes'] ?? [];
        if (empty($shortcodes) && !empty($page['content']) && preg_match('/\[([^\]\s]+)/', (string) $page['content'], $matches)) {
            $shortcodes[] = $matches[1];
        }

        foreach ($shortcodes as $shortcode) {
            $posts = get_posts([
                'post_type' => 'page',
                'post_status' => ['publish', 'draft', 'private'],
                'posts_per_page' => -1,
                'suppress_filters' => true,
                'meta_query' => [],
            ]);
            foreach ($posts as $post) {
                if (has_shortcode((string) $post->post_content, $shortcode)) {
                    return $post;
                }
            }
        }

        return null;
    }

    private function retire_legacy_pages()
    {
        $legacy_pages = [
            ['slug' => 'add-entry', 'titles' => ['Add Entry', 'Add New Entry'], 'shortcodes' => ['myavana_entry']],
            ['slug' => 'hair-insights', 'titles' => ['Hair Insights'], 'shortcodes' => ['myavana_analytics']],
        ];

        foreach ($legacy_pages as $legacy) {
            $post = get_page_by_path($legacy['slug'], OBJECT, 'page');
            if (!$post) {
                foreach ($legacy['titles'] as $title) {
                    $post = get_page_by_title($title, OBJECT, 'page');
                    if ($post) {
                        break;
                    }
                }
            }

            if (!$post) {
                $candidates = get_posts([
                    'post_type' => 'page',
                    'post_status' => ['publish', 'draft', 'private'],
                    'posts_per_page' => -1,
                    'suppress_filters' => true,
                ]);
                foreach ($candidates as $candidate) {
                    foreach ($legacy['shortcodes'] as $shortcode) {
                        if (has_shortcode((string) $candidate->post_content, $shortcode)) {
                            $post = $candidate;
                            break 2;
                        }
                    }
                }
            }

            if ($post instanceof WP_Post && $post->post_status === 'publish') {
                wp_update_post([
                    'ID' => $post->ID,
                    'post_status' => 'draft',
                ]);
            }
        }
    }

    private function sync_navigation_menus()
    {
        if (!function_exists('wp_get_nav_menus')) {
            return;
        }

        $required_pages = [
            'My Timeline' => get_page_by_path('hair-journey', OBJECT, 'page'),
            'Community' => get_page_by_path('community', OBJECT, 'page'),
            'Profile' => get_page_by_path('profile', OBJECT, 'page'),
            'Goals' => get_page_by_path('goals', OBJECT, 'page'),
            'Routines' => get_page_by_path('routines', OBJECT, 'page'),
        ];
        $legacy_titles = ['My Hair Journey', 'Add New Entry', 'Add Entry', 'Hair Insights'];

        foreach (wp_get_nav_menus() as $menu) {
            $items = wp_get_nav_menu_items($menu->term_id, ['post_status' => 'any']);
            $items = is_array($items) ? $items : [];

            $should_sync = false;
            foreach ($items as $item) {
                $item_title = trim(wp_strip_all_tags((string) $item->title));
                if (in_array($item_title, $legacy_titles, true) || array_key_exists($item_title, $required_pages)) {
                    $should_sync = true;
                    break;
                }
            }

            if (!$should_sync) {
                continue;
            }

            foreach ($items as $item) {
                $item_title = trim(wp_strip_all_tags((string) $item->title));
                if (in_array($item_title, $legacy_titles, true)) {
                    wp_delete_post((int) $item->ID, true);
                }
            }

            $fresh_items = wp_get_nav_menu_items($menu->term_id, ['post_status' => 'any']);
            $fresh_items = is_array($fresh_items) ? $fresh_items : [];

            foreach ($required_pages as $label => $page) {
                if (!($page instanceof WP_Post)) {
                    continue;
                }

                $exists = false;
                foreach ($fresh_items as $item) {
                    if ((int) $item->object_id === (int) $page->ID || trim(wp_strip_all_tags((string) $item->title)) === $label) {
                        $exists = true;
                        break;
                    }
                }

                if (!$exists) {
                    wp_update_nav_menu_item($menu->term_id, 0, [
                        'menu-item-object-id' => $page->ID,
                        'menu-item-object' => 'page',
                        'menu-item-type' => 'post_type',
                        'menu-item-title' => $label,
                        'menu-item-status' => 'publish',
                    ]);
                }
            }
        }
    }

    public function maybe_seed_legal_pages()
    {
        if (!is_admin() || !current_user_can('manage_options')) {
            return;
        }

        if (get_option('myavana_legal_pages_seeded_v1')) {
            return;
        }

        $legal_pages = [
            ['title' => 'Privacy Policy', 'slug' => 'privacy', 'content' => '[myavana_privacy_policy]'],
            ['title' => 'Terms of Service', 'slug' => 'terms', 'content' => '[myavana_terms]'],
        ];

        foreach ($legal_pages as $page) {
            if (get_page_by_path($page['slug'], OBJECT, 'page') || get_page_by_title($page['title'], OBJECT, 'page')) {
                continue;
            }

            $page_id = wp_insert_post([
                'post_title' => $page['title'],
                'post_name' => $page['slug'],
                'post_content' => $page['content'],
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_author' => get_current_user_id() ?: 1,
            ]);

            if (is_wp_error($page_id)) {
                error_log('Myavana Hair Journey: Failed to seed legal page ' . $page['title'] . ': ' . $page_id->get_error_message());
            }
        }

        update_option('myavana_legal_pages_seeded_v1', '1', false);
    }

    public function register_post_types()
    {
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

    public function add_pwa_meta_tags()
    {
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

    public function enqueue_scripts()
    {
        // Debug: Log that enqueue_scripts is being called
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Myavana: enqueue_scripts() called. User logged in: ' . (is_user_logged_in() ? 'YES' : 'NO'));
        }

        // Ensure jQuery is available for inline scripts printed by shortcodes/templates
        wp_enqueue_script('jquery');

        // Website intelligence tracker (page visits + time-on-page)
        wp_enqueue_script('myavana-site-intelligence', MYAVANA_URL . 'assets/js/site-intelligence-tracker.js', [], '1.0.0', true);
        wp_localize_script('myavana-site-intelligence', 'myavanaSiteIntelligence', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('myavana_site_intelligence'),
            'is_logged_in' => is_user_logged_in(),
            'user_id' => get_current_user_id(),
            'current_page' => $this->get_current_app_page(),
            'current_path' => parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/',
        ]);

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
        wp_enqueue_style('myavana-mobile-layout-global', MYAVANA_URL . 'assets/css/mobile-layout-global.css', [], '1.0.1');
        wp_enqueue_style('analysis-components', MYAVANA_URL . 'assets/css/analysis-components.css', [], '1.0.0');
        wp_enqueue_style('analysis-forms', MYAVANA_URL . 'assets/css/analysis-forms.css', [], '1.0.0');
        wp_enqueue_style('analysis-offcanvas', MYAVANA_URL . 'assets/css/analysis-offcanvas.css');
        wp_enqueue_style('myavana-ai-analysis-modal-css', MYAVANA_URL . 'assets/css/ai-analysis-modal.css', [], '1.0.0');

        if ($this->get_current_app_page() === 'home') {
            $luxury_home_css_version = file_exists(MYAVANA_DIR . 'assets/css/luxury-home.css')
                ? (string) filemtime(MYAVANA_DIR . 'assets/css/luxury-home.css')
                : '2.6.7';
            $luxury_home_js_version = file_exists(MYAVANA_DIR . 'assets/js/luxury-home.js')
                ? (string) filemtime(MYAVANA_DIR . 'assets/js/luxury-home.js')
                : '2.6.7';
            wp_enqueue_style('myavana-luxury-home', MYAVANA_URL . 'assets/css/luxury-home.css', [], $luxury_home_css_version);
            wp_enqueue_script('myavana-luxury-home', MYAVANA_URL . 'assets/js/luxury-home.js', ['jquery'], $luxury_home_js_version, true);
        }

        if ($this->should_show_global_navbar()) {
            wp_enqueue_style('myavana-global-navbar', MYAVANA_URL . 'assets/css/global-navbar.css', [], '1.0.3');
            wp_enqueue_script('myavana-global-navbar', MYAVANA_URL . 'assets/js/global-navbar.js', ['jquery'], '1.0.3', true);
        }

        if (is_user_logged_in() && $this->should_show_global_navbar()) {
            $timeline_url = $this->resolve_shortcode_page_url('myavana_hair-journey-page', '/hair-journey/');
            $goals_url = $this->resolve_shortcode_page_url('myavana_goals_page', '/goals/');
            $routines_url = $this->resolve_shortcode_page_url('myavana_routines_page', '/routines/');
            $community_url = $this->resolve_shortcode_page_url('myavana_community_feed', '/community/');

            $ai_intelligence_css_version = file_exists(MYAVANA_DIR . 'assets/css/ai-intelligence.css')
                ? (string) filemtime(MYAVANA_DIR . 'assets/css/ai-intelligence.css')
                : '2.6.7';
            $ai_intelligence_js_version = file_exists(MYAVANA_DIR . 'assets/js/ai-intelligence.js')
                ? (string) filemtime(MYAVANA_DIR . 'assets/js/ai-intelligence.js')
                : '2.6.7';
            wp_enqueue_style('myavana-ai-intelligence', MYAVANA_URL . 'assets/css/ai-intelligence.css', [], $ai_intelligence_css_version);
            wp_enqueue_script('myavana-ai-intelligence', MYAVANA_URL . 'assets/js/ai-intelligence.js', [], $ai_intelligence_js_version, true);
            wp_localize_script('myavana-ai-intelligence', 'myavanaAIIntelligence', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('myavana_site_intelligence'),
                'is_logged_in' => true,
                'current_page' => $this->get_current_app_page(),
                'current_path' => parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/',
                'routes' => [
                    'timeline' => $timeline_url,
                    'goals' => $goals_url,
                    'routines' => $routines_url,
                    'community' => $community_url,
                    'insights' => home_url('/hair-insights/'),
                ],
            ]);
        }

        // COMMUNITY SOCIAL FEED
        wp_enqueue_style('myavana-social-feed-css', MYAVANA_URL . 'assets/css/social-feed.css', [], '1.0.2');
        wp_enqueue_style('myavana-user-profile-css', MYAVANA_URL . 'assets/css/user-profile.css', [], '1.0.1');
        wp_enqueue_script('myavana-social-feed-js', MYAVANA_URL . 'assets/js/social-feed.js', ['jquery'], '1.0.3', true);
        wp_enqueue_script('myavana-share-to-community-js', MYAVANA_URL . 'assets/js/share-to-community.js', ['jquery'], '1.0.0', true);

        // Enqueue unified core framework FIRST (provides myavanaAjax for all scripts)
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

        // ENTRY SELECTOR FOR COMMUNITY SHARING (depends on myavanaAjax from unified-core)
        wp_enqueue_style('myavana-entry-selector-css', MYAVANA_URL . 'assets/css/entry-selector.css', [], '1.0.0');
        wp_enqueue_script('myavana-entry-selector-js', MYAVANA_URL . 'assets/js/entry-selector.js', ['jquery', 'myavana-unified-core'], '1.0.0', true);

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

        // Global mobile bottom tabs for core app pages
        if ($this->should_show_global_mobile_tabs()) {
            wp_enqueue_style('myavana-mobile-global-tabs', MYAVANA_URL . 'assets/css/mobile-global-tabs.css', [], '1.0.2');
            wp_enqueue_script('myavana-mobile-global-tabs', MYAVANA_URL . 'assets/js/mobile-global-tabs.js', ['jquery'], '1.0.2', true);

            wp_localize_script('myavana-mobile-global-tabs', 'myavanaMobileTabsData', [
                'activePage' => $this->get_current_app_page()
            ]);
        }
    }

    /**
     * Determine whether global mobile tabs should be shown.
     */
    private function should_show_global_mobile_tabs()
    {
        if (!is_user_logged_in()) {
            return false;
        }

        $page = $this->get_current_app_page();
        return in_array($page, ['home', 'journey', 'community', 'profile'], true);
    }

    /**
     * Detect current app page from shortcode content and URL path fallback.
     */
    private function get_current_app_page()
    {
        global $post;

        if (is_a($post, 'WP_Post') && !empty($post->post_content)) {
            if (has_shortcode($post->post_content, 'myavana_luxury_home')) {
                return 'home';
            }
            if (
            has_shortcode($post->post_content, 'myavana_hair-journey-page') ||
            has_shortcode($post->post_content, 'myavana_hair_journey_page') ||
            has_shortcode($post->post_content, 'myavana_hair_journey_timeline') ||
            has_shortcode($post->post_content, 'myavana_goals_page') ||
            has_shortcode($post->post_content, 'myavana_routines_page')
            ) {
                return 'journey';
            }
            if (has_shortcode($post->post_content, 'myavana_community_feed')) {
                return 'community';
            }
            if (has_shortcode($post->post_content, 'myavana_unified_profile')) {
                return 'profile';
            }
        }

        $path = trim(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
        if ($path === '') {
            return 'home';
        }
        if (strpos($path, 'hair-journey') !== false) {
            return 'journey';
        }
        if (strpos($path, 'goals') !== false || strpos($path, 'routines') !== false) {
            return 'journey';
        }
        if (strpos($path, 'community') !== false) {
            return 'community';
        }
        if (strpos($path, 'profile') !== false) {
            return 'profile';
        }

        return '';
    }

    /**
     * Redirect logged-out visitors away from protected app routes.
     */
    public function enforce_protected_page_access()
    {
        if (is_user_logged_in() || is_admin() || wp_doing_ajax() || wp_doing_cron()) {
            return;
        }

        if (defined('REST_REQUEST') && REST_REQUEST) {
            return;
        }

        if (!$this->is_protected_app_request()) {
            return;
        }

        $request_uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '/';
        $request_path = parse_url($request_uri, PHP_URL_PATH) ?: '/';
        $request_query = parse_url($request_uri, PHP_URL_QUERY);

        $requested_url = home_url($request_path);
        if (!empty($request_query)) {
            $requested_url .= '?' . $request_query;
        }

        $redirect_url = add_query_arg([
            'auth' => '1',
            'redirect_to' => rawurlencode($requested_url),
        ], home_url('/'));

        wp_safe_redirect($redirect_url);
        exit;
    }

    /**
     * Determine whether current request should require authentication.
     */
    private function is_protected_app_request()
    {
        global $post;

        $protected_shortcodes = [
            'myavana_hair-journey-page',
            'myavana_hair_journey_page',
            'myavana_hair_journey_timeline',
            'myavana_goals_page',
            'myavana_routines_page',
            'myavana_community_feed',
            'myavana_unified_profile',
        ];

        if (is_a($post, 'WP_Post') && !empty($post->post_content)) {
            foreach ($protected_shortcodes as $shortcode) {
                if (has_shortcode($post->post_content, $shortcode)) {
                    return true;
                }
            }
        }

        $request_uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '';
        $path = strtolower(trim((string)parse_url($request_uri, PHP_URL_PATH), '/'));
        if ($path === '') {
            return false;
        }

        return (bool)preg_match('#^(hair-journey|goals|routines|community|profile|members)(/|$)#', $path);
    }

    /**
     * Force all logout redirects to the site homepage.
     */
    public function force_logout_redirect($redirect_to, $requested_redirect_to, $user)
    {
        return home_url('/');
    }

    /**
     * Resolve a published page URL by shortcode, with a safe fallback.
     */
    private function resolve_shortcode_page_url($shortcode, $fallback_path)
    {
        global $wpdb;

        $shortcode_candidates = [$shortcode];
        if (strpos($shortcode, '-') !== false) {
            $shortcode_candidates[] = str_replace('-', '_', $shortcode);
        }

        foreach ($shortcode_candidates as $candidate) {
            $page_id = $wpdb->get_var($wpdb->prepare(
                "SELECT ID
                 FROM {$wpdb->posts}
                 WHERE post_type = 'page'
                   AND post_status = 'publish'
                   AND post_content LIKE %s
                 ORDER BY ID ASC
                 LIMIT 1",
                '%[' . $wpdb->esc_like($candidate) . '%'
            ));

            if ($page_id) {
                $page_url = get_permalink(intval($page_id));
                if ($page_url) {
                    return $page_url;
                }
            }
        }

        return home_url($fallback_path);
    }

    /**
     * Determine whether global navbar should be rendered.
     */
    private function should_show_global_navbar()
    {
        $page = $this->get_current_app_page();
        return in_array($page, ['home', 'journey', 'community', 'profile'], true);
    }

    /**
     * Add app shell body class only on core app pages.
     */
    public function add_app_body_class($classes)
    {
        if (!$this->should_show_global_navbar()) {
            return $classes;
        }

        $classes[] = 'myavana-app-shell';
        $classes[] = 'myavana-app-page-' . $this->get_current_app_page();
        return $classes;
    }

    /**
     * Render single global navbar for app pages.
     */
    public function render_global_navbar()
    {
        if ($this->global_nav_rendered || is_admin() || wp_doing_ajax() || wp_doing_cron()) {
            return;
        }

        if (!$this->should_show_global_navbar()) {
            return;
        }

        $active_page = $this->get_current_app_page();
        $is_logged_in = is_user_logged_in();
        $current_user = wp_get_current_user();
        $home_url = $this->resolve_shortcode_page_url('myavana_luxury_home', '/');
        $journey_url = $this->resolve_shortcode_page_url('myavana_hair-journey-page', '/hair-journey/');
        $goals_url = $this->resolve_shortcode_page_url('myavana_goals_page', '/goals/');
        $routines_url = $this->resolve_shortcode_page_url('myavana_routines_page', '/routines/');
        $community_url = $this->resolve_shortcode_page_url('myavana_community_feed', '/community/');
        $profile_url = $this->resolve_shortcode_page_url('myavana_unified_profile', '/profile/');
        $logout_url = wp_logout_url(home_url('/'));
        $ai_tool_url = 'https://www.myavana.com/pages/consumer';
        $admin_portal_url = Myavana_Admin_Portal::get_portal_url();
        $can_access_admin_portal = Myavana_Admin_Portal_Permissions::current_user_can_access_portal();

        $partials_file = MYAVANA_DIR . 'templates/pages/partials/global-navbar.php';
        if (!file_exists($partials_file)) {
            return;
        }

        $this->global_nav_rendered = true;
        include $partials_file;
    }

    /**
     * Fallback render for themes that do not call wp_body_open().
     */
    public function render_global_navbar_fallback()
    {
        if ($this->global_nav_rendered) {
            return;
        }

        $this->render_global_navbar();
    }

    /**
     * Render global mobile tabs in footer.
     */
    public function render_global_mobile_tabs()
    {
        if (!$this->should_show_global_mobile_tabs()) {
            return;
        }

        $active_page = $this->get_current_app_page();
        $goals_url = $this->resolve_shortcode_page_url('myavana_goals_page', '/goals/');
        $routines_url = $this->resolve_shortcode_page_url('myavana_routines_page', '/routines/');
        $admin_portal_url = Myavana_Admin_Portal::get_portal_url();
        $can_access_admin_portal = Myavana_Admin_Portal_Permissions::current_user_can_access_portal();
        $is_more_active = in_array($active_page, ['goals', 'routines'], true);
        $tabs = [
            ['id' => 'journey', 'label' => 'My Timeline', 'url' => $this->resolve_shortcode_page_url('myavana_hair-journey-page', '/hair-journey/'), 'icon' => '<svg viewBox="0 0 24 24"><path d="M12 2a10 10 0 100 20 10 10 0 000-20zm1 5v5h4v2h-6V7h2z"/></svg>'],
            ['id' => 'community', 'label' => 'Community', 'url' => $this->resolve_shortcode_page_url('myavana_community_feed', '/community/'), 'icon' => '<svg viewBox="0 0 24 24"><path d="M16 11a4 4 0 10-3.999-4A4 4 0 0016 11zM8 11a4 4 0 10-4-4 4 4 0 004 4zm8 2c-2.67 0-8 1.34-8 4v3h16v-3c0-2.66-5.33-4-8-4zM8 13c-.34 0-.73.02-1.14.05C4.9 13.2 0 14.2 0 17v3h6v-3c0-1.04.35-1.96 1-2.72A8.42 8.42 0 018 13z"/></svg>'],
            ['id' => 'profile', 'label' => 'Profile', 'url' => $this->resolve_shortcode_page_url('myavana_unified_profile', '/profile/'), 'icon' => '<svg viewBox="0 0 24 24"><path d="M12 12a5 5 0 10-5-5 5 5 0 005 5zm0 2c-4.42 0-8 2.24-8 5v3h16v-3c0-2.76-3.58-5-8-5z"/></svg>'],
        ];
?>
<nav class="myavana-premium-nav" id="myavanaGlobalMobileTabs" data-active-page="<?php echo esc_attr($active_page); ?>"
    style="display:none;">
    <button
        type="button"
        class="myavana-mobile-tabs-toggle"
        id="myavanaMobileTabsToggle"
        aria-expanded="true"
        aria-label="Minimize navigation tabs"
    >
        <span class="myavana-toggle-glyph" aria-hidden="true"></span>
    </button>
    <div class="myavana-premium-nav-container">
        <?php foreach ($tabs as $tab):
            $is_active = $active_page === $tab['id']; ?>
        <a href="<?php echo esc_url($tab['url']); ?>"
            class="myavana-global-mobile-tab <?php echo $is_active ? 'is-active' : ''; ?>"
            data-page="<?php echo esc_attr($tab['id']); ?>" aria-current="<?php echo $is_active ? 'page' : 'false'; ?>">
            <div class="icon-wrapper">
                <?php echo $tab['icon']; ?>
            </div>
            <span class="nav-label">
                <?php echo esc_html($tab['label']); ?>
            </span>
        </a>
        <?php
        endforeach; ?>

        <button type="button"
            class="myavana-global-mobile-tab myavana-global-mobile-tab-more <?php echo $is_more_active ? 'is-active' : ''; ?>"
            id="myavanaMobileMoreToggle" data-page-group="goals,routines"
            aria-current="<?php echo $is_more_active ? 'page' : 'false'; ?>" aria-haspopup="menu"
            aria-expanded="false" aria-controls="myavanaMobileMoreMenu">
            <div class="icon-wrapper">
                <svg viewBox="0 0 24 24">
                    <path
                        d="M5.5 10a1.5 1.5 0 110-3 1.5 1.5 0 010 3zm6.5 0A1.5 1.5 0 1012 7a1.5 1.5 0 000 3zm6.5 0A1.5 1.5 0 1018.5 7a1.5 1.5 0 000 3zM5.5 17a1.5 1.5 0 110-3 1.5 1.5 0 010 3zm6.5 0a1.5 1.5 0 100-3 1.5 1.5 0 000 3zm6.5 0a1.5 1.5 0 100-3 1.5 1.5 0 000 3z" />
                </svg>
            </div>
            <span class="nav-label">More</span>
        </button>

        <div class="myavana-global-mobile-more-menu" id="myavanaMobileMoreMenu" role="menu" hidden>
            <a href="<?php echo esc_url($goals_url); ?>" class="myavana-global-mobile-more-link" role="menuitem">
                <span class="myavana-global-mobile-more-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M12 3l2.8 5.67 6.25.91-4.52 4.4 1.07 6.23L12 17.27 6.4 20.2l1.07-6.23L2.95 9.58l6.25-.91L12 3z" />
                    </svg>
                </span>
                <span>Goals</span>
            </a>
            <a href="<?php echo esc_url($routines_url); ?>" class="myavana-global-mobile-more-link" role="menuitem">
                <span class="myavana-global-mobile-more-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M6 4h12v3H6V4zm1 5h10v3H7V9zm2 5h6v3H9v-3z" />
                    </svg>
                </span>
                <span>Routines</span>
            </a>
            <?php if ($can_access_admin_portal): ?>
            <a href="<?php echo esc_url($admin_portal_url); ?>" class="myavana-global-mobile-more-link" role="menuitem">
                <span class="myavana-global-mobile-more-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M4 5h16v4H4V5zm0 5h7v9H4v-9zm9 0h7v4h-7v-4zm0 5h7v4h-7v-4z" />
                    </svg>
                </span>
                <span>Admin Portal</span>
            </a>
            <?php endif; ?>
        </div>
    </div>
</nav>
<?php
    }

    /**
     * Enqueue assets specifically for the Hair Journey page shortcode
     * Moved from shortcode to ensure proper loading order
     */
    private function enqueue_hair_journey_assets()
    {
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
        $timeline_style_version = defined('WP_DEBUG') && WP_DEBUG ? time() : '1.1.4';
        wp_enqueue_style('myavana-new-timeline-hair-journey', MYAVANA_URL . 'assets/css/new-timeline.css', [], $timeline_style_version);

        // Staging-safe fallback: enqueue ALL timeline partials directly.
        // This protects against CSS combiners/minifiers that break @import paths.
        $timeline_partials = [
            'base' => 'base.css',
            'images' => 'images.css',
            'header' => 'header.css',
            'sidebar' => 'sidebar.css',
            'timeline' => 'timeline.css',
            'calendar' => 'calendar.css',
            'list' => 'list.css',
            'slider' => 'slider.css',
            'offcanvas' => 'offcanvas.css',
            'luxury-list' => 'luxury-list.css',
            'view-offcanvas' => 'view-offcanvas.css',
            'create-forms' => 'create-forms.css',
            'timeline-filters' => 'timeline-filters.css',
            'modal' => 'modal.css',
            'responsive' => 'responsive.css',
        ];

        foreach ($timeline_partials as $slug => $filename) {
            wp_enqueue_style(
                'myavana-timeline-' . $slug . '-hair-journey',
                MYAVANA_URL . 'assets/css/partials/' . $filename,
            ['myavana-new-timeline-hair-journey'],
                $timeline_style_version
            );
        }

        // Redesigned Timeline View — glassmorphic dark UI
        wp_enqueue_style(
            'myavana-timeline-redesign',
            MYAVANA_URL . 'assets/css/partials/timeline-redesign.css',
        ['myavana-new-timeline-hair-journey'],
            '1.0.0'
        );

        wp_enqueue_style(
            'myavana-goal-routine-pages',
            MYAVANA_URL . 'assets/css/goal-routine-pages.css',
        ['myavana-new-timeline-hair-journey'],
            $timeline_style_version
        );
        wp_enqueue_style('myavana-calendar-hair-journey', MYAVANA_URL . 'assets/css/calendar.css', [], $timeline_style_version);
        wp_enqueue_style('myavana-new-timeline-offcanvas-hair-journey', MYAVANA_URL . 'assets/css/new-offcanvas.css', [], $timeline_style_version);
        wp_enqueue_style('myavana-analytics-css', MYAVANA_URL . 'assets/css/analytics.css', [], '1.0.0');
        wp_enqueue_style('myavana-sidebar-analytics', MYAVANA_URL . 'assets/css/partials/sidebar-analytics.css', [], '1.0.0');
        wp_enqueue_style('myavana-sidebar-analytics2', MYAVANA_URL . 'assets/css/partials/sidebar-analytics.css', [], '1.0.0');
        wp_enqueue_style('myavana-slider-view', MYAVANA_URL . 'assets/css/slider-view.css', [], '1.0.0');
        wp_enqueue_style('myavana-sidebar-profile', MYAVANA_URL . 'assets/css/partials/sidebar-profile.css', [], '1.0.0');
        // Compact Redesign — additive overrides (loads last to win cascade)
        wp_enqueue_style('myavana-journey-redesign-compact', MYAVANA_URL . 'assets/css/partials/journey-redesign-compact.css', ['myavana-new-timeline-hair-journey'], $timeline_style_version);
        wp_enqueue_style('myavana-journey-dashboard', MYAVANA_URL . 'assets/css/partials/journey-dashboard.css', ['myavana-journey-redesign-compact'], $timeline_style_version);

        // Timeline & Calendar JS
        wp_enqueue_script('myavana-hair-timeline', MYAVANA_URL . 'assets/js/myavana-hair-timeline.js', ['jquery', 'splide-js', 'filepond'], '2.3.7', true);
        // wp_enqueue_script('myavana-hair-timeline', MYAVANA_URL . 'assets/js/myavana-hair-timeline.js', ['jquery','splide-js','filepond'], '2.3.7', true);

        // MYAVANA Timeline - Modular Architecture (v2.3.5)
        // Load modules in dependency order
        $timeline_version = defined('WP_DEBUG') && WP_DEBUG ? time() : '2.3.10';

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
        wp_enqueue_script('myavana-calendar-hair-journey-js', MYAVANA_URL . 'assets/js/calendar.js', ['jquery'], '1.0.2', true);

        // External CDN assets - properly enqueued
        wp_enqueue_style('tourguidejs-css', 'https://unpkg.com/@sjmc11/tourguidejs/dist/css/tour.min.css', [], '2.0.0');
        wp_enqueue_style('filepond-css', 'https://cdn.jsdelivr.net/npm/filepond@4.30.4/dist/filepond.min.css', [], '4.30.4');
        wp_enqueue_style('filepond-plugin-image-preview-css', 'https://cdn.jsdelivr.net/npm/filepond-plugin-image-preview@4.6.11/dist/filepond-plugin-image-preview.min.css', [], '4.6.11');
        wp_enqueue_style('select2-css', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', [], '4.1.0');
        wp_enqueue_script('select2-js', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', ['jquery'], '4.1.0', true);

        wp_enqueue_script('myavana-compare-analysis', MYAVANA_URL . 'assets/js/compare-analysis.js', ['jquery'], '1.0.0', true);

        // AI Analysis route handler (force all AI CTAs to official tool)
        $ai_modal_version = defined('WP_DEBUG') && WP_DEBUG ? time() : '1.0.2';
        wp_enqueue_script('myavana-ai-analysis-modal', MYAVANA_URL . 'assets/js/ai-analysis-modal.js', ['jquery'], $ai_modal_version, true);
        wp_add_inline_script('myavana-ai-analysis-modal', '
            window.myavanaAiToolUrl = window.myavanaAiToolUrl || "https://www.myavana.com/pages/consumer";
            window.openAIAnalysisModal = function() {
                window.location.href = window.myavanaAiToolUrl;
            };
        ', 'after');

        // Profile scripts
        $profile_js_version = defined('WP_DEBUG') && WP_DEBUG ? time() : '1.0.2';
        wp_enqueue_script('myavana-profile-js', MYAVANA_URL . 'assets/js/profile-shortcode.js', ['jquery'], $profile_js_version, true);
        wp_enqueue_script('myavana-profile-inline-js', MYAVANA_URL . 'assets/js/profile-inline-functionality.js', ['jquery', 'myavana-profile-js', 'splide-js'], '1.0.1', true);

        // Chart.js for analytics
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js', [], '4.4.0', true);
        wp_enqueue_script('myavana-analytics-js', MYAVANA_URL . 'assets/js/analytics.js', ['jquery', 'chart-js'], '1.0.0', true);
        wp_enqueue_script('myavana-analytics-export', MYAVANA_URL . 'assets/js/analytics-export.js', ['jquery'], '1.0.0', true);
        wp_enqueue_script('myavana-sidebar-analytics', MYAVANA_URL . 'assets/js/sidebar-analytics.js', ['jquery', 'chart-js'], '1.0.0', true);

        // GAMIFICATION SYSTEM (Phase 1: Daily Check-ins, Streaks, Badges, Points)
        wp_enqueue_style('myavana-gamification', MYAVANA_URL . 'assets/css/gamification.css', [], '1.0.1');
        wp_enqueue_script('myavana-gamification', MYAVANA_URL . 'assets/js/gamification.js', ['jquery'], '1.0.1', true);

        // SMART ENTRY SYSTEM is intentionally disabled for now.

        // PREMIUM 3-STEP ENTRY FORM (Luxury UI with interactive validation)
        wp_enqueue_style('myavana-premium-entry-form', MYAVANA_URL . 'assets/css/premium-entry-form.css', [], '1.0.1');
        wp_enqueue_script('myavana-premium-entry-form', MYAVANA_URL . 'assets/js/premium-entry-form.js', ['jquery'], '1.0.1', true);

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

        wp_localize_script('myavana-hair-analysis-js', 'myavanaHairAnalysisAjax', $ajax_data);

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
            'getRoutineDetailsNonce' => wp_create_nonce('myavana_get_routine_details'),
            'updateEntryNonce' => wp_create_nonce('myavana_update_entry'),
            'deleteEntryNonce' => wp_create_nonce('myavana_delete_entry'),
            'addEntryNonce' => wp_create_nonce('myavana_add_entry'),
            'updateGoalNonce' => wp_create_nonce('myavana_update_goal'),
            'addGoalNonce' => wp_create_nonce('myavana_add_goal'),
            'deleteGoalNonce' => wp_create_nonce('myavana_delete_goal'),
            'updateRoutineNonce' => wp_create_nonce('myavana_update_routine'),
            'addRoutineNonce' => wp_create_nonce('myavana_add_routine'),
            'deleteRoutineNonce' => wp_create_nonce('myavana_delete_routine'),
            'toggleRoutineNonce' => wp_create_nonce('myavana_toggle_routine_completion'),
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
            window.myavanaAppRoutes = window.myavanaAppRoutes || {
                goalsUrl: ' . wp_json_encode($this->resolve_shortcode_page_url('myavana_goals_page', '/goals/')) . ',
                routinesUrl: ' . wp_json_encode($this->resolve_shortcode_page_url('myavana_routines_page', '/routines/')) . '
            };
            window.myavanaOpenCollectionComposer = function(type, prefill) {
                const routes = window.myavanaAppRoutes || {};
                const normalizedType = type === "routine" ? "routine" : "goal";
                const pageOpeners = {
                    goal: window.myavanaOpenGoalForm,
                    routine: window.myavanaOpenRoutineForm
                };

                if (typeof pageOpeners[normalizedType] === "function") {
                    pageOpeners[normalizedType](prefill || {});
                    return false;
                }

                const targetUrl = normalizedType === "routine"
                    ? (routes.routinesUrl || "/routines/")
                    : (routes.goalsUrl || "/goals/");
                const url = new URL(targetUrl, window.location.origin);
                url.searchParams.set("create", normalizedType);

                if (prefill && typeof prefill === "object") {
                    Object.keys(prefill).forEach(function(key) {
                        const value = prefill[key];
                        if (value === undefined || value === null || value === "") {
                            return;
                        }
                        if (Array.isArray(value)) {
                            if (value.length) {
                                url.searchParams.set(key, value.join(","));
                            }
                            return;
                        }
                        url.searchParams.set(key, String(value));
                    });
                }

                window.location.href = url.toString();
                return false;
            };
            window.createGoal = function(prefill) {
                return window.myavanaOpenCollectionComposer("goal", prefill);
            };
            window.createRoutine = function(prefill) {
                return window.myavanaOpenCollectionComposer("routine", prefill);
            };
            window.myavanaAiToolUrl = window.myavanaAiToolUrl || "https://www.myavana.com/pages/consumer";
            window.openAIAnalysisModal = function() {
                window.location.href = window.myavanaAiToolUrl;
            };
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

    public function show_preloader()
    {
        if (Myavana_Admin_Portal::is_portal_request_static()) {
            return;
        }

?>

<div class="myavana-loader-container" id="myavanaLoader">
    <div class="myavana-loader">
        <div class="box">
            <div class="logo">
                <img src="https://6vt.d95.myftpupload.com/wp-content/uploads/2025/06/Signature-M-blueberry@2x.png"
                    alt="logo" />
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
    jQuery(document).ready(function ($) {
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
            $("#myavanaLoader").fadeOut(300, function () {
                $(this).remove();
            });
            $(".mask").fadeOut(500, function () {
                $(this).remove();
            });
        }

        // Method 1: Window load event
        $(window).on('load', function () {
            setTimeout(hideLoader, 500);
        });

        // Method 2: Document ready with timeout fallback
        $(document).ready(function () {
            setTimeout(hideLoader, 3000); // Hide after 3s max
        });

        // Method 3: Manual trigger for other scripts
        window.hideMyavanaLoader = hideLoader;

        // Emergency loader killer (for console debugging)
        window.killLoader = function () {
            console.warn('[Loader] EMERGENCY KILL ACTIVATED');
            $('#myavanaLoader, .mask, .myavana-loader-container').remove();
            loaderHidden = true;
        };
    });
</script>
<?php
    }

    public function debug_notices()
    {
        if (current_user_can('manage_options')) {
            global $wp_query;
            if (isset($wp_query->post->post_content) && strpos($wp_query->post->post_content, '[myavana_') !== false) {
                echo '<div class="notice notice-error"><p>Myavana Hair Journey: Shortcode detected but not rendering. Ensure plugin is active and shortcodes are registered.</p></div>';
            }
        }
    }

    public function create_default_profile($user_id)
    {
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
