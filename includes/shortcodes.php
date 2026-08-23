<?php
class Myavana_Shortcodes {
    public function __construct_scripts() {
        
        // Include shortcode files. 
        require_once MYAVANA_DIR . 'templates/login-shortcode.php';
        require_once MYAVANA_DIR . 'templates/register-shortcode.php';
        require_once MYAVANA_DIR . 'templates/profile-shortcode.php';
        require_once MYAVANA_DIR . 'templates/timeline-shortcode.php';
        require_once MYAVANA_DIR . 'templates/entry-shortcode.php';
        require_once MYAVANA_DIR . 'templates/tryon-shortcode.php';
        require_once MYAVANA_DIR . 'templates/analytics-shortcode.php';
        require_once MYAVANA_DIR . 'templates/test-shortcode.php';
        require_once MYAVANA_DIR . 'templates/react-shortcode.php';
        

        require_once MYAVANA_DIR . 'templates/widgets/hair-profile.php';

        require_once MYAVANA_DIR . 'templates/pages/home/home-one.php';
        require_once MYAVANA_DIR . 'templates/pages/hair-journey.php';
        require_once MYAVANA_DIR . 'templates/pages/legal.php';
        // require_once MYAVANA_DIR . 'templates/widgets/recent-activity.php';
        // require_once MYAVANA_DIR . 'templates/widgets/quick-stats.php';
        // require_once MYAVANA_DIR . 'templates/widgets/recommended-products.php';
    }

    public function register_shortcodes() {
        add_shortcode('myavana_login', [$this, 'login_shortcode']);
        add_shortcode('myavana_register', [$this, 'register_shortcode']);
        add_shortcode('myavana_hair-journey-page', [$this, 'hair_journey_shortcode']);
        add_shortcode('myavana_profile', [$this, 'profile_shortcode']);
        add_shortcode('myavana_timeline', [$this, 'timeline_shortcode']);
        add_shortcode('myavana_entry', [$this, 'entry_shortcode']);
        add_shortcode('myavana_tryon', [$this, 'tryon_shortcode']);
        add_shortcode('myavana_analytics', [$this, 'analytics_shortcode']);
        add_shortcode('myavana_test', [$this, 'test_shortcode']);
        add_shortcode('myavana_react', [$this, 'react_shortcode']);
        add_shortcode('myavana_home_one', [$this, 'home_one_shortcode']);
        add_shortcode('myavana_luxury_home', [$this, 'luxury_home_shortcode']);
        add_shortcode('myavana_hairprofilewidget', [$this, 'hair_profile_widget_shortcode']);
        add_shortcode('myavana_recent_activity_widget', [$this, 'recent_activity_widget_shortcode']);
        add_shortcode('myavana_quick_stats_widget', [$this, 'quick_stats_widget_shortcode']);
        add_shortcode('myavana_recommended_products_widget', [$this, 'recommended_products_widget_shortcode']);
        add_shortcode('myavana_hair_diary_timeline', [$this, 'hair_timeline_shortcode']);
        add_shortcode('myavana_hair_diary', [$this, 'hair_diary_shortcode']);
        add_shortcode('myavana_goals_page', [$this, 'goals_page_shortcode']);
        add_shortcode('myavana_routines_page', [$this, 'routines_page_shortcode']);
        add_shortcode('myavana_community_feed', [$this, 'community_feed_shortcode']);
        add_shortcode('myavana_user_profile', [$this, 'user_profile_shortcode']);
        add_shortcode('myavana_unified_profile', [$this, 'unified_profile_shortcode']);
        add_shortcode('myavana_challenges', [$this, 'challenges_shortcode']);
        add_shortcode('myavana_trending_posts', [$this, 'trending_posts_shortcode']);
        add_shortcode('myavana_routine_library', [$this, 'routine_library_shortcode']);
        add_shortcode('myavana_community_stats', [$this, 'community_stats_shortcode']);
        add_shortcode('myavana_privacy_policy', [$this, 'privacy_policy_shortcode']);
        add_shortcode('myavana_terms', [$this, 'terms_shortcode']);
    }

    public function login_shortcode() {
        // Legacy plain-POST form (myavana_login_shortcode(), in
        // templates/login-shortcode.php) bypasses the canonical AJAX auth
        // system entirely — no rate limiting, no password policy, no shared
        // validation. Send visitors to the site-wide modal instead of
        // rendering it.
        $this->redirect_to_canonical_auth('signin');
    }

    public function register_shortcode() {
        // See login_shortcode() — myavana_register_shortcode() creates
        // accounts with none of the password/terms validation the modal
        // enforces. Redirect to the canonical modal instead.
        $this->redirect_to_canonical_auth('signup');
    }

    private function redirect_to_canonical_auth($form) {
        $target = add_query_arg(['auth' => '1', 'form' => $form], home_url('/'));

        if (!empty($_GET['redirect_to'])) {
            $redirect_to = wp_unslash($_GET['redirect_to']);
            // Only forward same-site redirect targets.
            if (wp_validate_redirect($redirect_to, false)) {
                $target = add_query_arg('redirect_to', rawurlencode($redirect_to), $target);
            }
        }

        wp_safe_redirect($target);
        exit;
    }

    public function hair_journey_shortcode(){
        return myavana_hair_journey_page_shortcode();
    }

    public function profile_shortcode() {
        return myavana_profile_shortcode();
    }

    public function timeline_shortcode() {
        return myavana_timeline_shortcode();
    }

    public function entry_shortcode() {
        return myavana_entry_shortcode();
    }

    public function tryon_shortcode() {
        return myavana_tryon_shortcode();
    }

    public function analytics_shortcode() {
        return myavana_analytics_shortcode();
    }

    public function test_shortcode() {
        return myavana_test_shortcode();
    }

    public function react_shortcode() {
        return myavana_react_shortcode();
    }

    public function home_one_shortcode() {
        return myavana_home_one_shortcode();
    }

    public function luxury_home_shortcode() {
        return myavana_luxury_home_shortcode();
    }

    public function hair_profile_widget_shortcode() {
        return myavana_profile_widget_shortcode();
    }

    public function recent_activity_widget_shortcode() {
        return myavana_recent_activity_widget_shortcode();
    }

    public function quick_stats_widget_shortcode() {
        return myavana_quick_stats_widget_shortcode();
    }

    public function recommended_products_widget_shortcode() {
        return myavana_recommended_products_widget_shortcode();
    }

    public function hair_timeline_shortcode() {
        return myavana_hair_journey_timeline_shortcode();
    }

    public function hair_diary_shortcode() {
        return hair_journey_diary_shortcode();
    }

    public function goals_page_shortcode($atts = []) {
        return myavana_goals_page_shortcode($atts);
    }

    public function routines_page_shortcode($atts = []) {
        return myavana_routines_page_shortcode($atts);
    }

    public function community_feed_shortcode($atts = []) {
        return myavana_community_feed_shortcode($atts);
    }

    public function user_profile_shortcode($atts = []) {
        return myavana_user_profile_shortcode($atts);
    }

    public function unified_profile_shortcode($atts = []) {
        // Enqueue unified profile assets
        wp_enqueue_style('myavana-unified-profile', MYAVANA_URL . 'assets/css/unified-profile.css', [], '1.0.0');
        wp_enqueue_script('myavana-unified-profile', MYAVANA_URL . 'assets/js/unified-profile.js', ['jquery'], '1.0.1', true);

        // Localize script with settings
        wp_localize_script('myavana-unified-profile', 'myavanaUpSettings', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('myavana_ajax_nonce'),
            'userId' => get_current_user_id(),
            'defaultAvatar' => get_avatar_url(get_current_user_id())
        ]);

        return myavana_unified_profile_shortcode($atts);
    }

    public function challenges_shortcode($atts = []) {
        return myavana_challenges_shortcode($atts);
    }

    public function trending_posts_shortcode($atts = []) {
        return myavana_trending_posts_shortcode($atts);
    }

    public function routine_library_shortcode($atts = []) {
        return myavana_routine_library_shortcode($atts);
    }

    public function community_stats_shortcode($atts = []) {
        return myavana_community_stats_shortcode($atts);
    }

    public function privacy_policy_shortcode($atts = []) {
        return myavana_privacy_policy_shortcode($atts);
    }

    public function terms_shortcode($atts = []) {
        return myavana_terms_shortcode($atts);
    }
}
?>
