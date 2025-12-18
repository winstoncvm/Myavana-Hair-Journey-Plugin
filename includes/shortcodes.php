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
        add_shortcode('myavana_community_feed', [$this, 'community_feed_shortcode']);
        add_shortcode('myavana_user_profile', [$this, 'user_profile_shortcode']);
        add_shortcode('myavana_challenges', [$this, 'challenges_shortcode']);
        add_shortcode('myavana_trending_posts', [$this, 'trending_posts_shortcode']);
        add_shortcode('myavana_routine_library', [$this, 'routine_library_shortcode']);
        add_shortcode('myavana_community_stats', [$this, 'community_stats_shortcode']);
    }

    public function login_shortcode() {
        return myavana_login_shortcode();
    }

    public function register_shortcode() {
        return myavana_register_shortcode();
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
    public function community_feed_shortcode($atts = []) {
        return myavana_community_feed_shortcode($atts);
    }

    public function user_profile_shortcode($atts = []) {
        return myavana_user_profile_shortcode($atts);
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
}
?>