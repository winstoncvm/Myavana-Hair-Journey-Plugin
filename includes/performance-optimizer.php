<?php
/**
 * MYAVANA Performance Optimizer
 * Caching, code splitting, performance monitoring
 *
 * @package Myavana_Hair_Journey
 * @version 1.0.0
 */

defined('ABSPATH') or die('No script kiddies please!');

class Myavana_Performance_Optimizer {

    /**
     * Cache duration in seconds
     */
    const CACHE_DURATION = 3600; // 1 hour
    const LONG_CACHE_DURATION = 86400; // 24 hours

    /**
     * Cache group names
     */
    const CACHE_GROUP_ANALYTICS = 'myavana_analytics';
    const CACHE_GROUP_USER_DATA = 'myavana_user_data';
    const CACHE_GROUP_COMMUNITY = 'myavana_community';
    const CACHE_GROUP_JOURNEY = 'myavana_journey';

    /**
     * Initialize the optimizer
     */
    public static function init() {
        // Performance monitoring
        add_action('wp_footer', [__CLASS__, 'add_performance_monitoring'], 999);

        // Browser caching headers
        add_action('send_headers', [__CLASS__, 'add_caching_headers']);

        // Defer non-critical CSS
        add_filter('style_loader_tag', [__CLASS__, 'defer_non_critical_css'], 10, 4);

        // Defer non-critical JS
        add_filter('script_loader_tag', [__CLASS__, 'defer_non_critical_js'], 10, 3);

        // Preload critical assets
        add_action('wp_head', [__CLASS__, 'preload_critical_assets'], 1);

        // Database query caching
        add_filter('posts_pre_query', [__CLASS__, 'cache_query'], 10, 2);

        // AJAX response caching
        add_action('wp_ajax_myavana_get_cached_data', [__CLASS__, 'ajax_get_cached_data']);

        // Clear cache hooks
        add_action('save_post', [__CLASS__, 'clear_related_cache']);
        add_action('deleted_post', [__CLASS__, 'clear_related_cache']);
        add_action('myavana_entry_created', [__CLASS__, 'clear_journey_cache']);
        add_action('myavana_entry_updated', [__CLASS__, 'clear_journey_cache']);

        // Clean up old transients
        add_action('wp_scheduled_delete', [__CLASS__, 'cleanup_expired_cache']);
    }

    /**
     * Get cached data
     *
     * @param string $key Cache key
     * @param string $group Cache group
     * @return mixed|false Cached data or false
     */
    public static function get_cache($key, $group = '') {
        // Try object cache first (Redis/Memcached if available)
        $value = wp_cache_get($key, $group);

        if ($value !== false) {
            return $value;
        }

        // Fallback to transients
        $transient_key = self::get_transient_key($key, $group);
        return get_transient($transient_key);
    }

    /**
     * Set cached data
     *
     * @param string $key Cache key
     * @param mixed $value Data to cache
     * @param int $expiration Expiration in seconds
     * @param string $group Cache group
     * @return bool Success
     */
    public static function set_cache($key, $value, $expiration = self::CACHE_DURATION, $group = '') {
        // Set in object cache
        wp_cache_set($key, $value, $group, $expiration);

        // Also set as transient for fallback
        $transient_key = self::get_transient_key($key, $group);
        return set_transient($transient_key, $value, $expiration);
    }

    /**
     * Delete cached data
     *
     * @param string $key Cache key
     * @param string $group Cache group
     * @return bool Success
     */
    public static function delete_cache($key, $group = '') {
        // Delete from object cache
        wp_cache_delete($key, $group);

        // Delete transient
        $transient_key = self::get_transient_key($key, $group);
        return delete_transient($transient_key);
    }

    /**
     * Clear all cache in a group
     *
     * @param string $group Cache group
     * @return bool Success
     */
    public static function clear_group_cache($group) {
        global $wpdb;

        // Clear object cache group
        wp_cache_flush_group($group);

        // Clear transients in this group
        $transient_pattern = '_transient_myavana_' . $group . '_%';
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
            $transient_pattern
        ));

        return true;
    }

    /**
     * Get transient key
     *
     * @param string $key Original key
     * @param string $group Group name
     * @return string Full transient key
     */
    private static function get_transient_key($key, $group = '') {
        $prefix = 'myavana_';
        if ($group) {
            $prefix .= $group . '_';
        }
        return $prefix . md5($key);
    }

    /**
     * Cache database query results
     *
     * @param array|null $posts Array of posts or null
     * @param WP_Query $query Query object
     * @return array|null Posts array or null
     */
    public static function cache_query($posts, $query) {
        // Only cache for non-admin requests
        if (is_admin() || $query->is_main_query()) {
            return $posts;
        }

        // Generate cache key from query vars
        $cache_key = 'query_' . md5(serialize($query->query_vars));

        // Check cache
        $cached = self::get_cache($cache_key, 'queries');
        if ($cached !== false) {
            return $cached;
        }

        return $posts; // Let WordPress run the query normally
    }

    /**
     * Add browser caching headers for static assets
     */
    public static function add_caching_headers() {
        if (is_admin()) {
            return;
        }

        // Set cache headers for static assets
        $uri = $_SERVER['REQUEST_URI'];

        if (preg_match('/\.(jpg|jpeg|png|gif|webp|css|js|woff|woff2|ttf|svg)$/i', $uri)) {
            header('Cache-Control: public, max-age=31536000'); // 1 year
            header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
        }
    }

    /**
     * Defer non-critical CSS
     *
     * @param string $html Link tag HTML
     * @param string $handle Style handle
     * @param string $href Stylesheet URL
     * @param string $media Media attribute
     * @return string Modified HTML
     */
    public static function defer_non_critical_css($html, $handle, $href, $media) {
        // List of critical CSS that should load immediately
        $critical_styles = [
            'myavana-styles',
            'myavana-responsive-fixes-css',
            'upm-cm-unified-profile-css'
        ];

        // Don't defer critical styles
        if (in_array($handle, $critical_styles)) {
            return $html;
        }

        // Defer non-critical styles
        $html = str_replace("media='all'", "media='print' onload=\"this.media='all'\"", $html);
        $html = str_replace('media="all"', 'media="print" onload="this.media=\'all\'"', $html);

        return $html;
    }

    /**
     * Defer non-critical JavaScript
     *
     * @param string $tag Script tag HTML
     * @param string $handle Script handle
     * @param string $src Script URL
     * @return string Modified HTML
     */
    public static function defer_non_critical_js($tag, $handle, $src) {
        // List of critical scripts that should load immediately
        $critical_scripts = [
            'jquery',
            'myavana-unified-core',
            'myavana-mobile-nav-js'
        ];

        // Don't defer critical scripts
        if (in_array($handle, $critical_scripts)) {
            return $tag;
        }

        // Add defer attribute
        if (strpos($tag, 'defer') === false) {
            $tag = str_replace(' src=', ' defer src=', $tag);
        }

        return $tag;
    }

    /**
     * Preload critical assets
     */
    public static function preload_critical_assets() {
        // Preload critical CSS
        echo '<link rel="preload" href="' . MYAVANA_URL . 'assets/css/myavana-styles.css" as="style">' . "\n";
        echo '<link rel="preload" href="' . MYAVANA_URL . 'assets/css/myavana-responsive-fixes.css" as="style">' . "\n";

        // Preload critical JS
        echo '<link rel="preload" href="' . MYAVANA_URL . 'assets/js/myavana-unified-core.js" as="script">' . "\n";

        // Preload fonts
        echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
        echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
    }

    /**
     * Add performance monitoring script
     */
    public static function add_performance_monitoring() {
        if (!is_user_logged_in() || is_admin()) {
            return;
        }

        ?>
        <script>
        // Performance monitoring
        (function() {
            if (!window.performance || !window.performance.timing) {
                return;
            }

            window.addEventListener('load', function() {
                setTimeout(function() {
                    var timing = window.performance.timing;
                    var metrics = {
                        pageLoadTime: timing.loadEventEnd - timing.navigationStart,
                        domReadyTime: timing.domContentLoadedEventEnd - timing.navigationStart,
                        firstPaintTime: timing.responseEnd - timing.navigationStart,
                        dns: timing.domainLookupEnd - timing.domainLookupStart,
                        tcp: timing.connectEnd - timing.connectStart,
                        ttfb: timing.responseStart - timing.navigationStart
                    };

                    // Log to console in debug mode
                    if (window.myavanaAjax && window.myavanaAjax.debug) {
                        console.log('[Performance Metrics]', metrics);
                    }

                    // Send to analytics if needed
                    if (metrics.pageLoadTime > 3000) {
                        console.warn('[Performance Warning] Page load time: ' + (metrics.pageLoadTime / 1000).toFixed(2) + 's');
                    }
                }, 0);
            });
        })();
        </script>
        <?php
    }

    /**
     * AJAX: Get cached data
     */
    public static function ajax_get_cached_data() {
        check_ajax_referer('myavana_nonce', 'nonce');

        $key = sanitize_text_field($_POST['key'] ?? '');
        $group = sanitize_text_field($_POST['group'] ?? '');

        if (!$key) {
            wp_send_json_error(['message' => 'Invalid cache key']);
        }

        $data = self::get_cache($key, $group);

        if ($data === false) {
            wp_send_json_error(['message' => 'Cache miss']);
        }

        wp_send_json_success(['data' => $data]);
    }

    /**
     * Clear related cache when post is updated
     *
     * @param int $post_id Post ID
     */
    public static function clear_related_cache($post_id) {
        $post_type = get_post_type($post_id);

        switch ($post_type) {
            case 'hair_journey_entry':
                self::clear_group_cache(self::CACHE_GROUP_JOURNEY);
                self::clear_group_cache(self::CACHE_GROUP_ANALYTICS);
                break;

            case 'community_post':
                self::clear_group_cache(self::CACHE_GROUP_COMMUNITY);
                break;
        }

        // Clear user-specific cache
        $author_id = get_post_field('post_author', $post_id);
        if ($author_id) {
            self::delete_cache('user_' . $author_id . '_data', self::CACHE_GROUP_USER_DATA);
        }
    }

    /**
     * Clear journey cache
     *
     * @param int $user_id User ID
     */
    public static function clear_journey_cache($user_id = null) {
        if ($user_id) {
            self::delete_cache('user_' . $user_id . '_journey', self::CACHE_GROUP_JOURNEY);
            self::delete_cache('user_' . $user_id . '_analytics', self::CACHE_GROUP_ANALYTICS);
        } else {
            self::clear_group_cache(self::CACHE_GROUP_JOURNEY);
            self::clear_group_cache(self::CACHE_GROUP_ANALYTICS);
        }
    }

    /**
     * Cleanup expired cache
     */
    public static function cleanup_expired_cache() {
        global $wpdb;

        // Delete expired transients
        $wpdb->query(
            "DELETE FROM {$wpdb->options}
            WHERE option_name LIKE '_transient_timeout_myavana_%'
            AND option_value < UNIX_TIMESTAMP()"
        );

        // Delete orphaned transient values
        $wpdb->query(
            "DELETE FROM {$wpdb->options}
            WHERE option_name LIKE '_transient_myavana_%'
            AND option_name NOT IN (
                SELECT REPLACE(option_name, '_timeout', '')
                FROM {$wpdb->options}
                WHERE option_name LIKE '_transient_timeout_myavana_%'
            )"
        );
    }

    /**
     * Get performance metrics
     *
     * @return array Performance data
     */
    public static function get_performance_metrics() {
        $metrics = [
            'cache_hit_rate' => self::calculate_cache_hit_rate(),
            'avg_page_load_time' => self::get_avg_page_load_time(),
            'total_cached_items' => self::count_cached_items(),
            'cache_size' => self::get_cache_size()
        ];

        return $metrics;
    }

    /**
     * Calculate cache hit rate
     *
     * @return float Hit rate percentage
     */
    private static function calculate_cache_hit_rate() {
        $hits = (int) get_transient('myavana_cache_hits') ?: 0;
        $misses = (int) get_transient('myavana_cache_misses') ?: 0;
        $total = $hits + $misses;

        if ($total === 0) {
            return 0;
        }

        return ($hits / $total) * 100;
    }

    /**
     * Get average page load time
     *
     * @return float Average time in seconds
     */
    private static function get_avg_page_load_time() {
        $times = get_transient('myavana_page_load_times') ?: [];

        if (empty($times)) {
            return 0;
        }

        return array_sum($times) / count($times);
    }

    /**
     * Count cached items
     *
     * @return int Number of cached items
     */
    private static function count_cached_items() {
        global $wpdb;

        $count = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->options}
            WHERE option_name LIKE '_transient_myavana_%'
            AND option_name NOT LIKE '_transient_timeout_%'"
        );

        return (int) $count;
    }

    /**
     * Get total cache size
     *
     * @return string Human readable size
     */
    private static function get_cache_size() {
        global $wpdb;

        $size = $wpdb->get_var(
            "SELECT SUM(LENGTH(option_value)) FROM {$wpdb->options}
            WHERE option_name LIKE '_transient_myavana_%'"
        );

        return size_format((int) $size);
    }

    /**
     * Helper: Cache analytics data
     *
     * @param int $user_id User ID
     * @param string $period Period (7, 30, 90 days)
     * @param array $data Analytics data
     * @return bool Success
     */
    public static function cache_analytics($user_id, $period, $data) {
        $key = 'analytics_' . $user_id . '_' . $period;
        return self::set_cache($key, $data, self::LONG_CACHE_DURATION, self::CACHE_GROUP_ANALYTICS);
    }

    /**
     * Helper: Get cached analytics
     *
     * @param int $user_id User ID
     * @param string $period Period (7, 30, 90 days)
     * @return mixed|false Analytics data or false
     */
    public static function get_cached_analytics($user_id, $period) {
        $key = 'analytics_' . $user_id . '_' . $period;
        return self::get_cache($key, self::CACHE_GROUP_ANALYTICS);
    }

    /**
     * Helper: Cache user journey data
     *
     * @param int $user_id User ID
     * @param array $data Journey data
     * @return bool Success
     */
    public static function cache_user_journey($user_id, $data) {
        $key = 'journey_' . $user_id;
        return self::set_cache($key, $data, self::CACHE_DURATION, self::CACHE_GROUP_JOURNEY);
    }

    /**
     * Helper: Get cached user journey
     *
     * @param int $user_id User ID
     * @return mixed|false Journey data or false
     */
    public static function get_cached_user_journey($user_id) {
        $key = 'journey_' . $user_id;
        return self::get_cache($key, self::CACHE_GROUP_JOURNEY);
    }

    /**
     * Helper: Cache community feed
     *
     * @param string $feed_type Feed type
     * @param array $data Feed data
     * @param int $page Page number
     * @return bool Success
     */
    public static function cache_community_feed($feed_type, $data, $page = 1) {
        $key = 'feed_' . $feed_type . '_page_' . $page;
        return self::set_cache($key, $data, self::CACHE_DURATION, self::CACHE_GROUP_COMMUNITY);
    }

    /**
     * Helper: Get cached community feed
     *
     * @param string $feed_type Feed type
     * @param int $page Page number
     * @return mixed|false Feed data or false
     */
    public static function get_cached_community_feed($feed_type, $page = 1) {
        $key = 'feed_' . $feed_type . '_page_' . $page;
        return self::get_cache($key, self::CACHE_GROUP_COMMUNITY);
    }
}

// Initialize the optimizer
Myavana_Performance_Optimizer::init();
