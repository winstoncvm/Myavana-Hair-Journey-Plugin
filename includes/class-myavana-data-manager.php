<?php
/**
 * Centralized Data Manager for Myavana Hair Journey
 *
 * Prevents redundant data fetching across partials by caching and providing
 * a single source of truth for all user-related data.
 *
 * @package Myavana_Hair_Journey
 * @since 2.3.5
 */

if (!class_exists('Myavana_Data_Manager')) {

    class Myavana_Data_Manager {

        /**
         * Cache duration in seconds (5 minutes)
         */
        const CACHE_DURATION = 300;

        /**
         * Get all journey data for a user in one call
         *
         * @param int $user_id User ID
         * @return array Comprehensive user data
         */
        public static function get_journey_data($user_id) {
            if (!$user_id) {
                return self::get_empty_data();
            }

            // Check transient cache first
            $cache_key = 'myavana_journey_data_' . $user_id;
            $cached_data = get_transient($cache_key);

            if ($cached_data !== false) {
                return $cached_data;
            }

            // Fetch all data in optimized manner
            $data = [
                'user_id' => $user_id,
                'user_data' => get_userdata($user_id),
                'profile' => self::get_user_profile($user_id),
                'typeform_data' => get_user_meta($user_id, 'myavana_typeform_data', true) ?: [],
                'hair_goals' => get_user_meta($user_id, 'myavana_hair_goals_structured', true) ?: [],
                'about_me' => get_user_meta($user_id, 'myavana_about_me', true),
                'analysis_history' => get_user_meta($user_id, 'myavana_hair_analysis_history', true) ?: [],
                'current_routine' => get_user_meta($user_id, 'myavana_current_routine', true) ?: [],
                'hair_porosity' => get_user_meta($user_id, 'hair_porosity', true) ?: get_user_meta($user_id, 'myavana_hair_porosity', true) ?: '--',
                'hair_length' => get_user_meta($user_id, 'hair_length', true) ?: get_user_meta($user_id, 'myavana_hair_length', true) ?: '--',
                'entries' => self::get_user_entries($user_id),
                'stats' => self::get_user_stats($user_id),
                'analytics' => self::get_analytics_data($user_id),
                'analysis_limit_info' => self::get_analysis_limit_info($user_id),
            ];

            // Fallback for about_me if not set
            if (empty($data['about_me']) && !empty($data['typeform_data']['hair_goals'])) {
                $data['about_me'] = $data['typeform_data']['hair_goals'];
            }

            // Cache for 5 minutes
            set_transient($cache_key, $data, self::CACHE_DURATION);

            return $data;
        }

        /**
         * Get user profile from database
         */
        private static function get_user_profile($user_id) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'myavana_profiles';

            $profile = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_name WHERE user_id = %d",
                $user_id
            ));

            return $profile;
        }

        /**
         * Get user entries with caching
         */
        public static function get_user_entries($user_id) {
            $entries_query = new WP_Query([
                'post_type' => 'hair_journey_entry',
                'author' => $user_id,
                'posts_per_page' => -1,
                'orderby' => 'date',
                'order' => 'DESC',
                'post_status' => 'publish'
            ]);

            return $entries_query->posts;
        }

        /**
         * Get user statistics
         */
        public static function get_user_stats($user_id) {
            global $wpdb;

            $entries_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}posts
                WHERE post_author = %d
                AND post_type = 'hair_journey_entry'
                AND post_status = 'publish'",
                $user_id
            ));

            $days_active = $wpdb->get_var($wpdb->prepare(
                "SELECT DATEDIFF(CURDATE(), MIN(post_date))
                FROM {$wpdb->prefix}posts
                WHERE post_author = %d
                AND post_type = 'hair_journey_entry'",
                $user_id
            ));

            $streak = self::calculate_streak($user_id);

            return [
                'entries' => $entries_count ?: 0,
                'days_active' => $days_active ?: 0,
                'streak' => $streak,
                'is_new_user' => $entries_count == 0,
                'show_onboarding' => $entries_count == 0,
            ];
        }

        /**
         * Calculate current streak
         */
        public static function calculate_streak($user_id) {
            global $wpdb;

            // Get all entry dates ordered by date descending
            $dates = $wpdb->get_col($wpdb->prepare(
                "SELECT DISTINCT DATE(post_date) as entry_date
                FROM {$wpdb->prefix}posts
                WHERE post_author = %d
                AND post_type = 'hair_journey_entry'
                AND post_status = 'publish'
                ORDER BY entry_date DESC",
                $user_id
            ));

            if (empty($dates)) {
                return 0;
            }

            $streak = 0;
            $today = date('Y-m-d');
            $yesterday = date('Y-m-d', strtotime('-1 day'));

            // Check if most recent entry is today or yesterday
            if ($dates[0] !== $today && $dates[0] !== $yesterday) {
                return 0;
            }

            // Count consecutive days
            $current_date = $dates[0];
            foreach ($dates as $date) {
                if ($date === $current_date) {
                    $streak++;
                    $current_date = date('Y-m-d', strtotime($current_date . ' -1 day'));
                } else {
                    break;
                }
            }

            return $streak;
        }

        /**
         * Get analytics data
         */
        public static function get_analytics_data($user_id) {
            $entries = self::get_user_entries($user_id);

            if (empty($entries)) {
                return self::get_empty_analytics();
            }

            $ratings = [];
            $moods = [];
            $days_of_week = [];
            $months = [];
            $total_photos = 0;

            foreach ($entries as $entry) {
                $rating = get_post_meta($entry->ID, 'health_rating', true);
                if ($rating) {
                    $ratings[] = intval($rating);
                    $months[date('F Y', strtotime($entry->post_date))][] = intval($rating);
                }

                $mood = get_post_meta($entry->ID, 'mood_demeanor', true);
                if ($mood) {
                    $moods[$mood] = ($moods[$mood] ?? 0) + 1;
                }

                $day = date('l', strtotime($entry->post_date));
                $days_of_week[$day] = ($days_of_week[$day] ?? 0) + 1;

                if (has_post_thumbnail($entry->ID)) {
                    $total_photos++;
                }
            }

            // Calculate averages and bests
            $avg_health_score = !empty($ratings) ? round(array_sum($ratings) / count($ratings), 1) : 0;
            $most_active_day = !empty($days_of_week) ? array_search(max($days_of_week), $days_of_week) : 'N/A';
            $favorite_mood = !empty($moods) ? array_search(max($moods), $moods) : 'N/A';

            // Find best health month
            $best_health_month = 'N/A';
            $best_month_avg = 0;
            foreach ($months as $month => $month_ratings) {
                $month_avg = array_sum($month_ratings) / count($month_ratings);
                if ($month_avg > $best_month_avg) {
                    $best_month_avg = $month_avg;
                    $best_health_month = $month;
                }
            }

            // Calculate progress score
            $progress_score = min(100, intval((count($entries) / 30) * 100));

            return [
                'total_entries' => count($entries),
                'current_streak' => self::calculate_streak($user_id),
                'avg_health_score' => $avg_health_score,
                'total_photos' => $total_photos,
                'most_active_day' => $most_active_day,
                'favorite_mood' => $favorite_mood,
                'best_health_month' => $best_health_month,
                'progress_score' => $progress_score,
            ];
        }

        /**
         * Get analysis limit information
         */
        public static function get_analysis_limit_info($user_id) {
            $analysis_history = get_user_meta($user_id, 'myavana_hair_analysis_history', true) ?: [];
            $analysis_limit = 30;
            $current_week = date('W');

            $weekly_analysis = array_filter($analysis_history, function($item) use ($current_week) {
                return date('W', strtotime($item['date'])) === $current_week;
            });

            $analysis_count = count($weekly_analysis);
            $can_analyze = $analysis_count < $analysis_limit;

            return [
                'limit' => $analysis_limit,
                'count' => $analysis_count,
                'can_analyze' => $can_analyze,
                'remaining' => max(0, $analysis_limit - $analysis_count),
            ];
        }

        /**
         * Generate AI insights based on user data
         */
        public static function generate_ai_insights($user_id) {
            $stats = self::get_user_stats($user_id);
            $analytics = self::get_analytics_data($user_id);

            if ($stats['entries'] == 0) {
                return "Start your hair journey today! Track your progress with your first entry.";
            }

            if ($stats['streak'] >= 7) {
                return "Amazing! You're on a {$stats['streak']}-day streak. Keep up the great work!";
            }

            if ($analytics['avg_health_score'] >= 8) {
                return "Your hair health is excellent! Your routine is working beautifully.";
            }

            if ($analytics['avg_health_score'] < 5) {
                return "Consider trying a deep conditioning treatment. Your hair might need extra moisture.";
            }

            return "Keep tracking your journey! Consistency is key to seeing progress.";
        }

        /**
         * Clear cache for user
         */
        public static function clear_user_cache($user_id) {
            $cache_key = 'myavana_journey_data_' . $user_id;
            delete_transient($cache_key);
        }

        /**
         * Get empty data structure
         */
        private static function get_empty_data() {
            return [
                'user_id' => 0,
                'user_data' => null,
                'profile' => null,
                'typeform_data' => [],
                'hair_goals' => [],
                'about_me' => '',
                'analysis_history' => [],
                'current_routine' => [],
                'hair_porosity' => '--',
                'hair_length' => '--',
                'entries' => [],
                'stats' => [
                    'entries' => 0,
                    'days_active' => 0,
                    'streak' => 0,
                    'is_new_user' => true,
                    'show_onboarding' => true,
                ],
                'analytics' => self::get_empty_analytics(),
                'analysis_limit_info' => [
                    'limit' => 30,
                    'count' => 0,
                    'can_analyze' => true,
                    'remaining' => 30,
                ],
            ];
        }

        /**
         * Get empty analytics structure
         */
        private static function get_empty_analytics() {
            return [
                'total_entries' => 0,
                'current_streak' => 0,
                'avg_health_score' => 0,
                'total_photos' => 0,
                'most_active_day' => 'N/A',
                'favorite_mood' => 'N/A',
                'best_health_month' => 'N/A',
                'progress_score' => 0,
            ];
        }
    }
}

// Helper functions for backward compatibility
if (!function_exists('myavana_get_analytics_data')) {
    function myavana_get_analytics_data($user_id) {
        return Myavana_Data_Manager::get_analytics_data($user_id);
    }
}

if (!function_exists('myavana_generate_ai_insights')) {
    function myavana_generate_ai_insights($user_id) {
        return Myavana_Data_Manager::generate_ai_insights($user_id);
    }
}

if (!function_exists('myavana_clear_user_cache')) {
    function myavana_clear_user_cache($user_id) {
        Myavana_Data_Manager::clear_user_cache($user_id);
    }
}

/**
 * Hook into post save/update/delete to clear cache
 */
add_action('save_post_hair_journey_entry', function($post_id, $post) {
    if ($post->post_author) {
        myavana_clear_user_cache($post->post_author);
    }
}, 10, 2);

add_action('delete_post', function($post_id) {
    $post = get_post($post_id);
    if ($post && $post->post_type === 'hair_journey_entry' && $post->post_author) {
        myavana_clear_user_cache($post->post_author);
    }
});

/**
 * Hook into user meta updates to clear cache
 */
add_action('updated_user_meta', function($meta_id, $user_id, $meta_key) {
    $cache_keys = [
        'myavana_typeform_data',
        'myavana_hair_goals_structured',
        'myavana_about_me',
        'myavana_hair_analysis_history',
        'myavana_current_routine',
        'hair_porosity',
        'myavana_hair_porosity',
        'hair_length',
        'myavana_hair_length',
    ];

    if (in_array($meta_key, $cache_keys)) {
        myavana_clear_user_cache($user_id);
    }
}, 10, 3);
