<?php
function myavana_profile_widget_shortcode($atts = []) {
    $atts = shortcode_atts(['user_id' => get_current_user_id()], $atts, 'myavana_profile');
    $user_id = intval($atts['user_id']);
    $current_user_id = get_current_user_id();
    $is_owner = $user_id === $current_user_id;

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

 
    ob_start();
    ?>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  

    <div class="myavana-profile-widget">
       

        <div id="error-message" class="error-message hidden"></div>
        <div id="success-message" class="success-message hidden"></div>
        <div id="ai-tip" class="ai-tip hidden"></div>

        <div id="profile-display" class="profile-details-widget mb-6">
            <div class="p-2">
                <p class="mb-2"><span class="text-gray-700 font-bold">Hair Journey Stage:</span> <?php echo esc_html($profile->hair_journey_stage ?? 'Not set'); ?></p>
                <p class="mb-2"><span class="text-gray-700 font-bold">Hair Health Rating:</span> <?php echo esc_html($profile->hair_health_rating ?? '5'); ?>/5</p>
                <p class="mb-2"><span class="text-gray-700 font-bold">Hair Type:</span> <?php echo esc_html($profile->hair_type ?? 'Not set'); ?></p>
                <p class="mb-2"><span class="text-gray-700 font-bold">Hair Goals:</span> <?php echo esc_html($profile->hair_goals ?? 'Not set'); ?></p>
                <p class="mb-2"><span class="text-gray-700 font-bold">Life Journey Stage:</span> <?php echo esc_html($profile->life_journey_stage ?? 'Not set'); ?></p>
                <p class="mb-2"><span class="text-gray-700 font-bold">Birthday:</span> <?php echo esc_html($profile->birthday ?? 'Not set'); ?></p>
                <p class="mb-2"><span class="text-gray-700 font-bold">Location:</span> <?php echo esc_html($profile->location ?? 'Not set'); ?></p>
                
            </div>
        </div>


        <!-- Hair Care Tip -->
        <div class="hair-tip m-3">
            <h3 class="text-xl font-bold mb-3">Hair Care Tip</h3>
            <p id="random-tip" class="mb-3"></p>
            <button id="new-tip-btn" class="btn-primary-outline">Get Another Tip</button>
        </div>

        <script>
        jQuery(document).ready(function($) {
           

            // Random tip
            const tips = [
                'Condition your hair regularly to keep it moisturized.',
                'Avoid hot tools on wet hair to prevent damage.',
                'Massage your scalp during shampooing for better health.',
                'Trim every 6-12 weeks to maintain healthy ends.',
                'Use a wide-tooth comb on wet hair to detangle gently.'
            ];
            function setRandomTip() {
                $('#random-tip').text(tips[Math.floor(Math.random() * tips.length)]);
            }
            setRandomTip();
            $('#new-tip-btn').on('click', setRandomTip);

          
        });
        </script>
    </div>
    <?php
    return ob_get_clean();
}
function myavana_recent_activity_widget_shortcode($atts = []) {
    $atts = shortcode_atts(['user_id' => get_current_user_id()], $atts, 'myavana_recent_activity_widget');
    $user_id = intval($atts['user_id']);
    $current_user_id = get_current_user_id();
    $is_owner = $user_id === $current_user_id;

    if (!is_user_logged_in()) {
        return '<p>Please <a href="' . esc_url(wp_login_url(get_permalink())) . '" class="text-blue-600 underline">Log in</a> to view recent activity.</p>';
    }

    if (!get_userdata($user_id)) {
        return '<p class="text-red-600">Invalid user profile.</p>';
    }

    // Fetch recent hair journey entries
    $recent_entries = get_posts([
        'post_type' => 'hair_journey_entry',
        'author' => $user_id,
        'posts_per_page' => 3,
        'orderby' => 'date',
        'order' => 'DESC'
    ]);
    wp_enqueue_script('hair-diary-offcanvas-js', plugin_dir_url(__FILE__) . '../../assets/js/offcanvas.js', ['jquery'], '1.0.0', true);
    wp_enqueue_style('hair-diary-offcanvas-css', plugin_dir_url(__FILE__) . '../../assets/css/offcanvas.css', [], '1.0.0');
    
    // Enqueue scripts and styles
    wp_enqueue_script('hair-diary-js', plugin_dir_url(__FILE__) . '../assets/js/timeline2.js', ['jquery'], '1.0.10', true);
    wp_enqueue_style('hair-diary-css', plugin_dir_url(__FILE__) . '../assets/css/timeline2.css', [], '1.0.10');

    ob_start();
    ?>
    
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <div class="myavana-recent-activity-widget bg-white rounded-lg shadow p-4 pi-3 mb-4">
        <h3 class="text-lg font-bold mb-3 text-gray-800">Recent Activity</h3>
        <?php if (!empty($recent_entries)) : ?>
            <ul class="space-y-3">
                <?php foreach ($recent_entries as $entry) : 
                    $thumbnail_url = get_the_post_thumbnail_url($entry->ID, 'thumbnail');
                    // Validate thumbnail URL
                    if (!$thumbnail_url || !filter_var($thumbnail_url, FILTER_VALIDATE_URL)) {
                        $thumbnail_url = false;
                        error_log('Myavana Hair Journey: Invalid or missing thumbnail URL for post ' . $entry->ID);
                    }
                ?>
                    <li class="border-b border-gray-200 pb-2 flex items-start space-x-3 pb-3">
                        <?php if ($thumbnail_url) : ?>
                            <img src="<?php echo esc_url($thumbnail_url); ?>" alt="<?php echo esc_attr($entry->post_title); ?>" class="w-16 h-16 object-cover rounded-md">
                        <?php else : ?>
                            <div class="w-16 h-16 bg-gray-200 rounded-md flex items-center justify-center text-gray-500 text-sm">No Image</div>
                        <?php endif; ?>
                        <div class="flex-1">
                            <p class="font-semibold text-gray-700"><?php echo esc_html($entry->post_title); ?></p>
                            <p class="text-sm text-gray-500"><?php echo esc_html(get_the_date('F j, Y', $entry)); ?></p>
                            <a href="<?php echo esc_url(home_url('/members/' . bp_core_get_username($user_id) . '/hair_journey_timeline/')); ?>" class="text-blue-600 text-sm hover:underline">View Timeline</a>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php if ($is_owner) : ?>
                <a href="<?php echo esc_url(home_url('/members/' . bp_core_get_username($user_id) . '/add_entry/')); ?>" class="mt-3 inline-block text-blue-600 hover:underline">Add New Entry</a>
            <?php endif; ?>
        <?php else : ?>
            <p class="text-gray-600">No recent activity. Start your hair journey today!</p>
            <?php if ($is_owner) : ?>
                <a href="<?php echo esc_url(home_url('/members/' . bp_core_get_username($user_id) . '/add_entry/')); ?>" class="mt-3 inline-block text-blue-600 hover:underline">Add Your First Entry</a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <div class="timeline-container">
        <div class="timeline" id="timelineEntries">
            <p style="text-align: center; color: #666; padding: 20px;">No entries yet. Start your hair journey!</p>
        </div>
        <div class="timeline__axis"></div>
    </div>
    <?php
    return ob_get_clean();
}
function myavana_quick_stats_widget_shortcode($atts = []) {
    $atts = shortcode_atts(['user_id' => get_current_user_id()], $atts, 'myavana_quick_stats_widget');
    $user_id = intval($atts['user_id']);
    $is_owner = $user_id === get_current_user_id();

    if (!is_user_logged_in()) {
        return '<p>Please <a href="' . esc_url(wp_login_url(get_permalink())) . '" class="text-blue-600 underline">Log in</a> to view stats.</p>';
    }

    if (!$is_owner) {
        return '<p class="text-red-600">You can only view stats for your own profile.</p>';
    }

    // Fetch hair journey entries for stats
    $args = [
        'post_type' => 'hair_journey_entry',
        'author' => $user_id,
        'posts_per_page' => -1,
        'orderby' => 'date',
        'order' => 'ASC'
    ];
    $entries = new WP_Query($args);
    $ratings = [];
    $total_entries = $entries->found_posts;

    while ($entries->have_posts()) : $entries->the_post();
        $rating = get_post_meta(get_the_ID(), 'health_rating', true);
        if ($rating) {
            $ratings[] = intval($rating);
        }
    endwhile;
    wp_reset_postdata();

    $avg_rating = $ratings ? round(array_sum($ratings) / count($ratings), 1) : 0;
    $trend = count($ratings) > 1 ? ($ratings[count($ratings) - 1] > $ratings[0] ? 'Improving' : ($ratings[count($ratings) - 1] < $ratings[0] ? 'Declining' : 'Stable')) : 'N/A';

    ob_start();
    ?>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <div class="myavana-quick-stats-widget bg-white rounded-lg shadow p-4 mb-4">
        <h3 class="text-lg font-bold mb-3 text-gray-800">Quick Stats</h3>
        <div class="space-y-2">
            <p class="text-sm"><span class="font-semibold text-gray-700">Total Entries:</span> <?php echo esc_html($total_entries); ?></p>
            <p class="text-sm"><span class="font-semibold text-gray-700">Avg. Health Rating:</span> <?php echo esc_html($avg_rating); ?>/5</p>
            <p class="text-sm"><span class="font-semibold text-gray-700">Trend:</span> <?php echo esc_html($trend); ?></p>
        </div>
        <a href="<?php echo esc_url(home_url('/members/' . bp_core_get_username($user_id) . '/hair_insights/')); ?>" class="mt-3 inline-block text-blue-600 hover:underline">View Full Analytics</a>
    </div>
    <?php
    return ob_get_clean();
}
function myavana_recommended_products_widget_shortcode($atts = []) {
    $atts = shortcode_atts(['user_id' => get_current_user_id()], $atts, 'myavana_recommended_products_widget');
    $user_id = intval($atts['user_id']);
    $is_owner = $user_id === get_current_user_id();

    if (!is_user_logged_in()) {
        return '<p>Please <a href="' . esc_url(wp_login_url(get_permalink())) . '" class="text-blue-600 underline">Log in</a> to view recommendations.</p>';
    }

    if (!get_userdata($user_id)) {
        return '<p class="text-red-600">Invalid user profile.</p>';
    }

    // Fetch product recommendations from recent entries
    $recent_entries = get_posts([
        'post_type' => 'hair_journey_entry',
        'author' => $user_id,
        'posts_per_page' => 5,
        'orderby' => 'date',
        'order' => 'DESC'
    ]);
    $products = [];
    foreach ($recent_entries as $entry) {
        $analysis_data = get_post_meta($entry->ID, 'analysis_data', true);
        if ($analysis_data) {
            $analysis = json_decode($analysis_data, true);
            if (!empty($analysis['products'])) {
                foreach ($analysis['products'] as $product) {
                    $products[] = [
                        'name' => sanitize_text_field($product['name'] ?? 'Unknown Product'),
                        'match' => floatval($product['match'] ?? 0),
                        'entry_date' => get_the_date('Y-m-d', $entry)
                    ];
                }
            }
        }
    }
    $products = array_unique($products, SORT_REGULAR);
    usort($products, function($a, $b) {
        return $b['match'] <=> $a['match'];
    });
    $products = array_slice($products, 0, 3);

    ob_start();
    ?>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <div class="myavana-recommended-products-widget bg-white rounded-lg shadow p-4 mb-4">
        <h3 class="text-lg font-bold mb-3 text-gray-800">Recommended Products</h3>
        <?php if (!empty($products)) : ?>
            <ul class="space-y-3">
                <?php foreach ($products as $product) : ?>
                    <li class="border-b border-gray-200 pb-2">
                        <p class="font-semibold text-gray-700"><?php echo esc_html($product['name']); ?></p>
                        <p class="text-sm text-gray-500">Match: <?php echo esc_html($product['match']); ?>%</p>
                        <a href="<?php echo esc_url(home_url('/products/' . sanitize_title($product['name']))); ?>" class="text-blue-600 text-sm hover:underline">View Product</a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else : ?>
            <p class="text-gray-600">No product recommendations yet. Add more hair journey entries!</p>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}
?>