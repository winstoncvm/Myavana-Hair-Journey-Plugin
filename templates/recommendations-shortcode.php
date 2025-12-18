<?php
function myavana_recommendations_shortcode() {
    if (!is_user_logged_in()) {
        return '<p>Please <a href="' . home_url('/login') . '">log in</a> to view recommendations.</p>';
    }
    global $wpdb;
    $user_id = get_current_user_id();
    $profile = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}myavana_profiles WHERE user_id = %d", $user_id));
    
    // Mock AI API call (replace with actual xAI API integration)
    $api_response = [
        'products' => [
            ['name' => 'Moisturizing Shampoo', 'brand' => 'Myavana', 'description' => 'Ideal for dryness and curl definition.'],
            ['name' => 'Leave-In Conditioner', 'brand' => 'Myavana', 'description' => 'Helps with frizz and manageability.']
        ],
        'stylist' => [
            'name' => 'Local Stylist',
            'location' => esc_html($profile->location ?? 'Unknown'),
            'specialty' => 'Natural Hair Care'
        ]
    ];
    
    ob_start();
    ?>
    <div class="myavana-recommendations">
        <h2>Your personalized recommendations</h2>
        <h3>Products</h3>
        <ul>
            <?php foreach ($api_response['products'] as $product) : ?>
                <li><?php echo esc_html($product['name']) . ' by ' . esc_html($product['brand']) . ': ' . esc_html($product['description']); ?></li>
            <?php endforeach; ?>
        </ul>
        <h3>Stylist referral</h3>
        <p><?php echo esc_html($api_response['stylist']['name']) . ' in ' . esc_html($api_response['stylist']['location']) . ' (' . esc_html($api_response['stylist']['specialty']) . ')'; ?></p>
    </div>
    <style>
        .myavana-recommendations { max-width: 600px; margin: auto; padding: 20px; }
        .myavana-recommendations ul { list-style: disc; padding-left: 20px; }
    </style>
    <?php
    return ob_get_clean();
}
?>