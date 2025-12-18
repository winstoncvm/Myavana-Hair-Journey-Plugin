<?php
// Analytics tracking handler
function myavana_track_analytics() {
    // Don't use check_ajax_referer as it dies - verify manually instead
    $nonce_valid = false;

    if (isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], 'myavana_nonce')) {
        $nonce_valid = true;
    } elseif (isset($_POST['security']) && wp_verify_nonce($_POST['security'], 'myavana_nonce')) {
        $nonce_valid = true;
    }

    // For analytics, we'll be lenient - just log the error but continue
    if (!$nonce_valid) {
        error_log('MYAVANA Analytics: Invalid nonce, but continuing...');
    }

    if (!isset($_POST['events'])) {
        wp_send_json_error('No events provided');
        return;
    }

    $events = json_decode(stripslashes($_POST['events']), true);

    if (!is_array($events)) {
        wp_send_json_error('Invalid events format');
        return;
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'myavana_analytics';

    // Ensure analytics table exists
    $wpdb->query("CREATE TABLE IF NOT EXISTS {$table_name} (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) UNSIGNED DEFAULT NULL,
        session_id VARCHAR(255) NOT NULL,
        event_name VARCHAR(255) NOT NULL,
        event_properties LONGTEXT DEFAULT NULL,
        page_url TEXT DEFAULT NULL,
        user_agent TEXT DEFAULT NULL,
        timestamp DATETIME NOT NULL,
        PRIMARY KEY (id),
        KEY idx_user_id (user_id),
        KEY idx_session_id (session_id),
        KEY idx_event_name (event_name),
        KEY idx_timestamp (timestamp)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $processed = 0;
    foreach ($events as $event) {
        if (!isset($event['event']) || !isset($event['properties'])) {
            continue;
        }

        $result = $wpdb->insert(
            $table_name,
            [
                'user_id' => $event['properties']['user_id'] ?? null,
                'session_id' => sanitize_text_field($event['properties']['session_id'] ?? ''),
                'event_name' => sanitize_text_field($event['event']),
                'event_properties' => wp_json_encode($event['properties']),
                'page_url' => sanitize_text_field($event['properties']['page_url'] ?? ''),
                'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''),
                'timestamp' => gmdate('Y-m-d H:i:s', $event['properties']['timestamp'] / 1000)
            ],
            ['%d', '%s', '%s', '%s', '%s', '%s', '%s']
        );

        if ($result) {
            $processed++;
        }
    }

    wp_send_json_success([
        'processed' => $processed,
        'total' => count($events)
    ]);
}
add_action('wp_ajax_myavana_track_analytics', 'myavana_track_analytics');
add_action('wp_ajax_nopriv_myavana_track_analytics', 'myavana_track_analytics');

// WebSocket connection endpoint
function myavana_websocket_auth() {
    check_ajax_referer('myavana_nonce', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error('Not authenticated');
        return;
    }

    $user_id = get_current_user_id();
    $token = wp_generate_uuid4();

    // Store token temporarily for WebSocket authentication
    set_transient('myavana_ws_token_' . $user_id, $token, 300); // 5 minutes

    wp_send_json_success([
        'token' => $token,
        'user_id' => $user_id,
        'websocket_url' => get_option('myavana_websocket_url', '')
    ]);
}
add_action('wp_ajax_myavana_websocket_auth', 'myavana_websocket_auth');

// Performance metrics handler
function myavana_record_performance() {
    // Don't use check_ajax_referer as it dies - verify manually instead
    $nonce_valid = false;

    if (isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], 'myavana_nonce')) {
        $nonce_valid = true;
    } elseif (isset($_POST['security']) && wp_verify_nonce($_POST['security'], 'myavana_nonce')) {
        $nonce_valid = true;
    }

    // For performance tracking, we'll be lenient - just log the error but continue
    if (!$nonce_valid) {
        error_log('MYAVANA Performance: Invalid nonce, but continuing...');
    }

    if (!isset($_POST['metrics'])) {
        wp_send_json_error('No metrics provided');
        return;
    }

    $metrics = json_decode(stripslashes($_POST['metrics']), true);

    if (!is_array($metrics)) {
        wp_send_json_error('Invalid metrics format');
        return;
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'myavana_performance';

    // Ensure performance table exists
    $wpdb->query("CREATE TABLE IF NOT EXISTS {$table_name} (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) UNSIGNED DEFAULT NULL,
        metric_name VARCHAR(255) NOT NULL,
        metric_value DECIMAL(10,2) NOT NULL,
        page_url TEXT DEFAULT NULL,
        user_agent TEXT DEFAULT NULL,
        timestamp DATETIME NOT NULL,
        PRIMARY KEY (id),
        KEY idx_metric_name (metric_name),
        KEY idx_timestamp (timestamp),
        KEY idx_user_id (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $processed = 0;
    foreach ($metrics as $metric_name => $metric_data) {
        $result = $wpdb->insert(
            $table_name,
            [
                'user_id' => get_current_user_id() ?: null,
                'metric_name' => sanitize_text_field($metric_name),
                'metric_value' => floatval($metric_data['value']),
                'page_url' => sanitize_text_field($_POST['page_url'] ?? ''),
                'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''),
                'timestamp' => gmdate('Y-m-d H:i:s', $metric_data['timestamp'] / 1000)
            ],
            ['%d', '%s', '%f', '%s', '%s', '%s']
        );

        if ($result) {
            $processed++;
        }
    }

    wp_send_json_success([
        'processed' => $processed,
        'total' => count($metrics)
    ]);
}
add_action('wp_ajax_myavana_record_performance', 'myavana_record_performance');
add_action('wp_ajax_nopriv_myavana_record_performance', 'myavana_record_performance');

// Load component handler for code splitting
function myavana_load_component() {
    check_ajax_referer('myavana_nonce', 'nonce');

    if (!isset($_POST['component'])) {
        wp_send_json_error('No component specified');
        return;
    }

    $component = sanitize_text_field($_POST['component']);
    $allowed_components = [
        'entry_form',
        'chatbot_embed',
        'analytics_dashboard',
        'profile_editor',
        'timeline_viewer'
    ];

    if (!in_array($component, $allowed_components)) {
        wp_send_json_error('Invalid component');
        return;
    }

    ob_start();

    switch ($component) {
        case 'entry_form':
            include MYAVANA_DIR . 'templates/components/entry-form.php';
            break;
        case 'chatbot_embed':
            include MYAVANA_DIR . 'templates/components/chatbot-embed.php';
            break;
        case 'analytics_dashboard':
            include MYAVANA_DIR . 'templates/components/analytics-dashboard.php';
            break;
        case 'profile_editor':
            include MYAVANA_DIR . 'templates/components/profile-editor.php';
            break;
        case 'timeline_viewer':
            include MYAVANA_DIR . 'templates/components/timeline-viewer.php';
            break;
    }

    $html = ob_get_clean();

    wp_send_json_success([
        'html' => $html,
        'component' => $component
    ]);
}
add_action('wp_ajax_myavana_load_component', 'myavana_load_component');

// Load timeline embed for dashboard
function myavana_load_timeline_embed() {
    check_ajax_referer('myavana_nonce', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error('Not authenticated');
        return;
    }

    ob_start();

    // Use the existing timeline shortcode function
    if (function_exists('myavana_hair_journey_timeline_shortcode')) {
        echo myavana_hair_journey_timeline_shortcode([
            'user_id' => get_current_user_id(),
            'theme' => 'dashboard',
            'compact' => 'true',
            'show_filters' => 'true'
        ]);
    } else {
        echo '<div class="timeline-error">Timeline shortcode not available. Please ensure the plugin is properly installed.</div>';
    }

    $html = ob_get_clean();

    wp_send_json_success([
        'html' => $html,
        'component' => 'timeline_embed'
    ]);
}
add_action('wp_ajax_myavana_load_timeline_embed', 'myavana_load_timeline_embed');

function myavana_save_conversation() {
    check_ajax_referer('myavana_chatbot', 'nonce');
    global $wpdb;
    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error('User not logged in');
        return;
    }

    // Validate required fields
    if (empty($_POST['message']) || empty($_POST['message_type']) || empty($_POST['session_id'])) {
        wp_send_json_error('Missing required fields');
        return;
    }

    $message = sanitize_textarea_field($_POST['message']);
    $message_type = sanitize_text_field($_POST['message_type']);
    $session_id = sanitize_text_field($_POST['session_id']);
    $timestamp = current_time('mysql');

    // Validate message type
    if (!in_array($message_type, ['user', 'agent', 'system', 'error'], true)) {
        wp_send_json_error('Invalid message type');
        return;
    }

    // Validate session ID format (UUID)
    if (!preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i', $session_id)) {
        wp_send_json_error('Invalid session ID format');
        return;
    }

    $wpdb->insert(
        $wpdb->prefix . 'myavana_conversations',
        [
            'user_id' => $user_id,
            'session_id' => $session_id,
            'message_text' => $message,
            'message_type' => in_array($message_type, ['user', 'agent', 'system', 'error']) ? $message_type : 'system',
            'timestamp' => $timestamp
        ],
        ['%d', '%s', '%s', '%s', '%s']
    );

    if ($wpdb->last_error) {
        error_log('Myavana Hair Journey: Failed to save conversation: ' . $wpdb->last_error);
        wp_send_json_error('Failed to save conversation');
    } else {
        wp_send_json_success('Conversation saved');
    }
}
add_action('wp_ajax_myavana_save_conversation', 'myavana_save_conversation');

function myavana_create_auto_entry() {
    check_ajax_referer('myavana_chatbot', 'nonce');
    global $wpdb;
    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error('User not logged in');
        return;
    }

    $analysis = json_decode(stripslashes($_POST['analysis']), true);
    $image_data = $_POST['image_data'];
    $session_id = sanitize_text_field($_POST['session_id']);
    $timestamp = current_time('mysql');

    // Generate AI tags
    $tags = myavana_generate_ai_tags($analysis);

    // Create hair journey entry
    $post_id = wp_insert_post([
        'post_title' => 'Automated Hair Journey Entry - ' . $timestamp,
        'post_content' => sanitize_textarea_field($analysis['summary'] ?? 'Automated entry from chatbot analysis.'),
        'post_type' => 'hair_journey_entry',
        'post_status' => 'publish',
        'post_author' => $user_id
    ]);

    if ($post_id && !is_wp_error($post_id)) {
        // Save metadata
        update_post_meta($post_id, 'products_used', sanitize_text_field(implode(', ', $analysis['products'] ?? [])));
        update_post_meta($post_id, 'stylist_notes', sanitize_text_field($analysis['recommendations'] ? implode("\n", $analysis['recommendations']) : ''));
        update_post_meta($post_id, 'health_rating', min(max(intval($analysis['hair_analysis']['health_score'] ?? 5) / 20, 1), 5));
        update_post_meta($post_id, 'ai_tags', $tags);
        update_post_meta($post_id, 'session_id', $session_id);
        update_post_meta($post_id, 'analysis_data', wp_json_encode($analysis));
        update_post_meta($post_id, 'environment', sanitize_text_field($analysis['environment'] ?? ''));
        update_post_meta($post_id, 'mood_demeanor', sanitize_text_field($analysis['mood_demeanor'] ?? ''));

        // Save screenshot
        $attachment_id = myavana_save_screenshot($image_data, $post_id, $user_id);
        if ($attachment_id && !is_wp_error($attachment_id)) {
            set_post_thumbnail($post_id, $attachment_id);

            // Add to BuddyPress activity
            if (function_exists('bp_is_active') && bp_is_active('activity')) {
                $activity_content = sprintf(
                    esc_html__('Shared a new automated hair journey entry: %s', 'myavana'),
                    'Automated Hair Analysis'
                );
                $activity_args = [
                    'user_id' => $user_id,
                    'type' => 'activity_update',
                    'action' => bp_core_get_userlink($user_id) . ' ' . $activity_content,
                    'content' => sanitize_textarea_field($analysis['summary'] ?? 'Automated hair analysis entry.'),
                    'component' => 'activity',
                    'item_id' => 0,
                    'secondary_item_id' => $attachment_id,
                    'recorded_time' => $timestamp
                ];
                $activity_id = bp_activity_add($activity_args);
                if ($activity_id) {
                    $media_data = [
                        'media_id' => $attachment_id,
                        'type' => 'photo',
                        'user_id' => $user_id,
                        'activity_id' => $activity_id,
                        'uploaded' => $timestamp
                    ];
                    bp_activity_update_meta($activity_id, 'yz_media', [$media_data]);
                }
            }

            // Update hair profile
            myavana_update_hair_profile($user_id, $analysis, $timestamp);

            wp_send_json_success(['post_id' => $post_id, 'message' => 'Automated entry created']);
        } else {
            wp_delete_post($post_id, true);
            wp_send_json_error('Failed to save screenshot');
        }
    } else {
        error_log('Myavana Hair Journey: Failed to create automated entry for user ' . $user_id);
        wp_send_json_error('Failed to create entry');
    }
}
add_action('wp_ajax_myavana_create_auto_entry', 'myavana_create_auto_entry');

function myavana_save_screenshot($base64_image, $post_id, $user_id) {
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $image_data = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64_image));
    $filename = 'hair_analysis_' . $post_id . '_' . time() . '.jpg';
    $upload_dir = wp_upload_dir();
    $file_path = $upload_dir['path'] . '/' . $filename;

    if (file_put_contents($file_path, $image_data)) {
        $attachment = [
            'post_mime_type' => 'image/jpeg',
            'post_title' => sanitize_file_name($filename),
            'post_content' => '',
            'post_status' => 'inherit',
            'post_author' => $user_id
        ];
        $attachment_id = wp_insert_attachment($attachment, $file_path, $post_id);
        if (!is_wp_error($attachment_id)) {
            $attachment_data = wp_generate_attachment_metadata($attachment_id, $file_path);
            wp_update_attachment_metadata($attachment_id, $attachment_data);
            return $attachment_id;
        } else {
            error_log('Myavana Hair Journey: Failed to create attachment: ' . $attachment_id->get_error_message());
            return false;
        }
    } else {
        error_log('Myavana Hair Journey: Failed to save screenshot for post ' . $post_id);
        return false;
    }
}

function myavana_generate_ai_tags($analysis) {
    $tags = [];
    if ($analysis['hair_analysis']) {
        $hair = $analysis['hair_analysis'];
        if ($hair['type']) $tags[] = 'hair_type_' . strtolower(str_replace(' ', '_', $hair['type']));
        if ($hair['curl_pattern']) $tags[] = 'curl_' . strtolower($hair['curl_pattern']);
        if ($hair['length']) $tags[] = 'length_' . strtolower(str_replace(' ', '_', $hair['length']));
        if ($hair['hydration']) $tags[] = 'hydration_' . ($hair['hydration'] < 30 ? 'low' : ($hair['hydration'] < 70 ? 'medium' : 'high'));
        if ($hair['health_score']) $tags[] = 'health_' . ($hair['health_score'] < 30 ? 'low' : ($hair['health_score'] < 70 ? 'medium' : 'high'));
        if ($hair['damage']) $tags[] = 'damage_' . strtolower(str_replace(' ', '_', $hair['damage']));
    }
    if ($analysis['environment']) $tags[] = 'env_' . strtolower(str_replace(' ', '_', $analysis['environment']));
    if ($analysis['mood_demeanor']) $tags[] = 'mood_' . strtolower(str_replace(' ', '_', $analysis['mood_demeanor']));
    return array_unique($tags);
}

function myavana_update_hair_profile($user_id, $analysis, $timestamp) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'myavana_profiles';
    $profile = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE user_id = %d", $user_id));

    $snapshots = $profile && $profile->hair_analysis_snapshots ? json_decode($profile->hair_analysis_snapshots, true) : [];
    $snapshots[] = [
        'timestamp' => $timestamp,
        'hair_analysis' => $analysis['hair_analysis'] ?? [],
        'environment' => $analysis['environment'] ?? '',
        'mood_demeanor' => $analysis['mood_demeanor'] ?? '',
        'recommendations' => $analysis['recommendations'] ?? [],
        'products' => $analysis['products'] ?? []
    ];

    $update_data = [
        'hair_type' => sanitize_text_field($analysis['hair_analysis']['type'] ?? $profile->hair_type ?? ''),
        'hair_health_rating' => min(max(intval($analysis['hair_analysis']['health_score'] ?? $profile->hair_health_rating ?? 5) / 10, 1), 10),
        'hair_analysis_snapshots' => wp_json_encode($snapshots)
    ];

    $wpdb->update(
        $table_name,
        $update_data,
        ['user_id' => $user_id],
        ['%s', '%d', '%s'],
        ['%d']
    );

    if ($wpdb->last_error) {
        error_log('Myavana Hair Journey: Failed to update profile: ' . $wpdb->last_error);
    }
}

// Load entry form for modal
function myavana_load_entry_form() {
    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error('Authentication required');
        return;
    }

    // Verify nonce for security (optional but recommended)
    if (isset($_POST['nonce']) && !wp_verify_nonce($_POST['nonce'], 'myavana_nonce')) {
        wp_send_json_error('Invalid security token');
        return;
    }

    try {
        // Start output buffering
        ob_start();

        // Include the entry form component
        $component_path = MYAVANA_DIR . 'templates/components/entry-form.php';

        if (file_exists($component_path)) {
            include $component_path;
        } else {
            // Fallback simple entry form
            echo myavana_simple_entry_form_fallback();
        }

        $form_html = ob_get_clean();

        // Return success response with form HTML
        wp_send_json_success($form_html);

    } catch (Exception $e) {
        error_log('MYAVANA: Entry form load error: ' . $e->getMessage());
        wp_send_json_error('Failed to load entry form: ' . $e->getMessage());
    }
}

// Simple fallback entry form
function myavana_simple_entry_form_fallback() {
    $user_id = get_current_user_id();
    $user_name = wp_get_current_user()->display_name;

    return '
    <div class="myavana-simple-entry-form" style="padding: 20px;">
        <div class="form-header" style="text-align: center; margin-bottom: 30px;">
            <h3 style="color: var(--myavana-onyx); margin-bottom: 10px;">Add Hair Journey Entry</h3>
            <p style="color: var(--myavana-onyx); opacity: 0.7;">Document your hair progress, ' . esc_html($user_name) . '</p>
        </div>

        <form id="simpleHairEntryForm" style="max-width: 500px; margin: 0 auto;">
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--myavana-onyx);">
                    Entry Title
                </label>
                <input type="text" name="entry_title" placeholder="e.g., Week 4 Progress"
                       style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 16px;" required>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--myavana-onyx);">
                    How are you feeling about your hair today?
                </label>
                <textarea name="entry_notes" rows="4" placeholder="Share your thoughts, observations, or any changes you\'ve noticed..."
                          style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 16px; resize: vertical;"></textarea>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--myavana-onyx);">
                    Hair Health Rating (1-10)
                </label>
                <select name="health_rating" style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 16px;">
                    <option value="">Select rating</option>
                    <option value="10">10 - Excellent</option>
                    <option value="9">9 - Very Good</option>
                    <option value="8">8 - Good</option>
                    <option value="7">7 - Above Average</option>
                    <option value="6">6 - Average</option>
                    <option value="5">5 - Below Average</option>
                    <option value="4">4 - Poor</option>
                    <option value="3">3 - Very Poor</option>
                    <option value="2">2 - Critical</option>
                    <option value="1">1 - Needs Immediate Attention</option>
                </select>
            </div>

            <div style="margin-bottom: 30px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--myavana-onyx);">
                    Upload Photos (Optional)
                </label>
                <input type="file" name="entry_photos[]" multiple accept="image/*"
                       style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 16px;">
                <p style="font-size: 12px; color: #666; margin-top: 5px;">Upload before/after photos to track your progress</p>
            </div>

            <div style="text-align: center; margin-top: 30px;">
                <button type="submit" style="
                    background: var(--myavana-coral, #e7a690);
                    color: white;
                    border: none;
                    padding: 15px 30px;
                    border-radius: 8px;
                    font-size: 16px;
                    font-weight: 600;
                    cursor: pointer;
                    transition: all 0.3s ease;
                    margin-right: 15px;
                " onmouseover="this.style.background=\'#d4956f\'" onmouseout="this.style.background=\'var(--myavana-coral, #e7a690)\'">
                    <i class="fas fa-save"></i> Save Entry
                </button>

                <button type="button" onclick="MyavanaLuxuryHomepage.closeEntryModal()" style="
                    background: transparent;
                    color: var(--myavana-onyx, #222323);
                    border: 2px solid var(--myavana-onyx, #222323);
                    padding: 15px 30px;
                    border-radius: 8px;
                    font-size: 16px;
                    font-weight: 600;
                    cursor: pointer;
                    transition: all 0.3s ease;
                ">
                    Cancel
                </button>
            </div>
        </form>

        <script>
        (function($) {
            $(document).ready(function() {
                $("#simpleHairEntryForm").on("submit", function(e) {
                    e.preventDefault();

                    const $form = $(this);
                    const $submitBtn = $form.find("button[type=submit]");
                    const originalText = $submitBtn.html();

                    // Show loading state
                    $submitBtn.html("<i class=\"fas fa-spinner fa-spin\"></i> Saving...").prop("disabled", true);

                    // Get AJAX data
                    const ajaxData = window.myavanaLuxuryData || {};

                    // Get form data
                    const formData = new FormData(this);
                    formData.append("action", "myavana_save_simple_entry");
                    formData.append("nonce", ajaxData.nonce || window.myavanaNonce || "");

                    // Submit via AJAX
                    $.ajax({
                        url: ajaxData.ajaxUrl || window.ajaxurl || "/wp-admin/admin-ajax.php",
                        type: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.success) {
                                // Show success message
                                $form.html(`
                                    <div style="text-align: center; padding: 40px;">
                                        <i class="fas fa-check-circle" style="font-size: 48px; color: #4caf50; margin-bottom: 20px;"></i>
                                        <h3 style="color: var(--myavana-onyx); margin-bottom: 15px;">Entry Saved Successfully!</h3>
                                        <p style="color: var(--myavana-onyx); opacity: 0.7; margin-bottom: 25px;">Your hair journey entry has been added.</p>
                                        <button onclick="MyavanaLuxuryHomepage.closeEntryModal()" style="
                                            background: var(--myavana-coral, #e7a690);
                                            color: white;
                                            border: none;
                                            padding: 12px 24px;
                                            border-radius: 8px;
                                            cursor: pointer;
                                        ">Close</button>
                                    </div>
                                `);

                                // Auto-close after 3 seconds
                                setTimeout(() => {
                                    if (typeof MyavanaLuxuryHomepage !== "undefined") {
                                        MyavanaLuxuryHomepage.closeEntryModal();
                                    }
                                }, 3000);
                            } else {
                                alert("Error saving entry: " + (response.data || "Unknown error"));
                                $submitBtn.html(originalText).prop("disabled", false);
                            }
                        },
                        error: function() {
                            alert("Network error. Please try again.");
                            $submitBtn.html(originalText).prop("disabled", false);
                        }
                    });
                });
            });
        })(jQuery);
        </script>
    </div>';
}

// Save simple entry form
function myavana_save_simple_entry() {
    try {
        // Check if user is logged in first
        if (!is_user_logged_in()) {
            wp_send_json_error('Authentication required');
            return;
        }

        // Flexible nonce validation - accept multiple nonce types
        $nonce_valid = false;
        if (isset($_POST['nonce'])) {
            $nonce_valid = wp_verify_nonce($_POST['nonce'], 'myavana_nonce') ||
                          wp_verify_nonce($_POST['nonce'], 'myavana_onboarding') ||
                          wp_verify_nonce($_POST['nonce'], 'luxury_home_nonce');
        }

        // For logged-in users, we can be more lenient with nonce (onboarding context)
        // But still validate if provided
        if (isset($_POST['nonce']) && !$nonce_valid) {
            error_log('MYAVANA: Invalid nonce in save_simple_entry. Nonce: ' . $_POST['nonce']);
            wp_send_json_error('Invalid nonce');
            return;
        }

        $user_id = get_current_user_id();
        $title = sanitize_text_field($_POST['entry_title'] ?? '');
        $notes = sanitize_textarea_field($_POST['entry_notes'] ?? '');
        $rating = intval($_POST['health_rating'] ?? 0);

        if (empty($title)) {
            wp_send_json_error('Entry title is required');
            return;
        }
        // Create WordPress post for the entry
        $post_data = [
            'post_title'    => $title,
            'post_content'  => $notes,
            'post_status'   => 'publish',
            'post_author'   => $user_id,
            'post_type'     => 'hair_journey_entry',
            'meta_input'    => [
                'health_rating' => $rating,
                'entry_date' => current_time('Y-m-d H:i:s'),
                'entry_source' => sanitize_text_field($_POST['entry_source'] ?? 'onboarding')
            ]
        ];

        $post_id = wp_insert_post($post_data);

        if (is_wp_error($post_id)) {
            wp_send_json_error('Failed to save entry: ' . $post_id->get_error_message());
            return;
        }

        // Handle photo uploads if any
        $uploaded_files = [];

        if (!empty($_FILES['entry_photos'])) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');

            $files = $_FILES['entry_photos'];

            // Check if it's an array of files or single file
            if (is_array($files['name'])) {
                // Multiple files
                $file_count = count($files['name']);

                for ($i = 0; $i < $file_count; $i++) {
                    if ($files['error'][$i] === UPLOAD_ERR_OK) {
                        $file_array = [
                            'name' => $files['name'][$i],
                            'type' => $files['type'][$i],
                            'tmp_name' => $files['tmp_name'][$i],
                            'error' => $files['error'][$i],
                            'size' => $files['size'][$i]
                        ];

                        $upload = wp_handle_upload($file_array, ['test_form' => false]);

                        if (!isset($upload['error'])) {
                            $uploaded_files[] = $upload['url'];

                            // Set as featured image if first photo
                            if ($i === 0 && !empty($upload['file'])) {
                                $attachment_id = wp_insert_attachment([
                                    'post_mime_type' => $upload['type'],
                                    'post_title' => sanitize_file_name(pathinfo($upload['file'], PATHINFO_FILENAME)),
                                    'post_content' => '',
                                    'post_status' => 'inherit'
                                ], $upload['file'], $post_id);

                                if (!is_wp_error($attachment_id)) {
                                    wp_update_attachment_metadata($attachment_id, wp_generate_attachment_metadata($attachment_id, $upload['file']));
                                    set_post_thumbnail($post_id, $attachment_id);
                                }
                            }
                        } else {
                            error_log('MYAVANA: Upload error for file ' . $i . ': ' . $upload['error']);
                        }
                    }
                }
            } else {
                // Single file
                if ($files['error'] === UPLOAD_ERR_OK) {
                    $upload = wp_handle_upload($files, ['test_form' => false]);

                    if (!isset($upload['error'])) {
                        $uploaded_files[] = $upload['url'];

                        // Set as featured image
                        if (!empty($upload['file'])) {
                            $attachment_id = wp_insert_attachment([
                                'post_mime_type' => $upload['type'],
                                'post_title' => sanitize_file_name(pathinfo($upload['file'], PATHINFO_FILENAME)),
                                'post_content' => '',
                                'post_status' => 'inherit'
                            ], $upload['file'], $post_id);

                            if (!is_wp_error($attachment_id)) {
                                wp_update_attachment_metadata($attachment_id, wp_generate_attachment_metadata($attachment_id, $upload['file']));
                                set_post_thumbnail($post_id, $attachment_id);
                            }
                        }
                    } else {
                        error_log('MYAVANA: Upload error: ' . $upload['error']);
                    }
                }
            }

            if (!empty($uploaded_files)) {
                update_post_meta($post_id, 'entry_photos', $uploaded_files);
            }
        }

        wp_send_json_success([
            'entry_id' => $post_id,  // Changed from post_id to entry_id
            'post_id' => $post_id,   // Keep for backward compatibility
            'message' => 'Entry saved successfully!',
            'photos' => $uploaded_files
        ]);
    } catch (Exception $e) {
        error_log('MYAVANA: Exception in save_simple_entry: ' . $e->getMessage());
        error_log('MYAVANA: Stack trace: ' . $e->getTraceAsString());
        wp_send_json_error('An error occurred while saving your entry: ' . $e->getMessage());
    }
}

// Load onboarding overlay
function myavana_load_onboarding_overlay() {
    if (!is_user_logged_in()) {
        wp_send_json_error('Authentication required');
        return;
    }

    try {
        // Start output buffering
        ob_start();

        // Include the onboarding overlay
        $onboarding_path = MYAVANA_DIR . 'templates/onboarding/onboarding-overlay.php';

        if (file_exists($onboarding_path)) {
            include $onboarding_path;
        } else {
            // Fallback - just return success, JavaScript will use fallback
            wp_send_json_error('Onboarding overlay not found');
            return;
        }

        $onboarding_html = ob_get_clean();

        // Return success response with onboarding HTML
        wp_send_json_success($onboarding_html);

    } catch (Exception $e) {
        error_log('MYAVANA: Onboarding overlay load error: ' . $e->getMessage());
        wp_send_json_error('Failed to load onboarding overlay: ' . $e->getMessage());
    }
}

// Skip onboarding
function myavana_skip_onboarding() {
    if (!is_user_logged_in()) {
        wp_send_json_error('Authentication required');
        return;
    }

    $user_id = get_current_user_id();

    // Mark onboarding as completed (but skipped)
    update_user_meta($user_id, 'myavana_onboarding_completed', 'skipped');
    update_user_meta($user_id, 'myavana_onboarding_skipped_date', current_time('mysql'));

    wp_send_json_success([
        'message' => 'Onboarding skipped successfully',
        'status' => 'skipped'
    ]);
}

// Save onboarding step data
function myavana_onboarding_step() {
    // Accept either nonce format for flexibility
    $nonce_valid = false;
    if (isset($_POST['nonce'])) {
        $nonce_valid = wp_verify_nonce($_POST['nonce'], 'myavana_nonce') ||
                      wp_verify_nonce($_POST['nonce'], 'myavana_onboarding');
    }

    if (!$nonce_valid) {
        wp_send_json_error('Invalid nonce');
        return;
    }

    if (!is_user_logged_in()) {
        wp_send_json_error('Authentication required');
        return;
    }

    $user_id = get_current_user_id();
    $step = isset($_POST['step']) ? sanitize_text_field($_POST['step']) : '';
    $data = isset($_POST['data']) ? $_POST['data'] : [];

    if (empty($step)) {
        wp_send_json_error('Step name is required');
        return;
    }

    // Get current onboarding data
    $onboarding_data = get_user_meta($user_id, 'myavana_onboarding_data', true);
    if (!is_array($onboarding_data)) {
        $onboarding_data = [];
    }

    // Save step data
    $onboarding_data['last_step'] = $step;
    $onboarding_data['updated_at'] = current_time('mysql');

    global $wpdb;
    $table_name = $wpdb->prefix . 'myavana_profiles';

    // Prepare profile updates array
    $profile_updates = [];

    // Save specific data based on step
    // Handle new 3-step onboarding: welcome, preferences, complete
    if ($step === 'welcome' || $step === 'profile') {
        // Hair type
        if (isset($data['hair_type']) && !empty($data['hair_type'])) {
            $hair_type = sanitize_text_field($data['hair_type']);
            $onboarding_data['hair_type'] = $hair_type;
            $profile_updates['hair_type'] = $hair_type;
        }

        // Primary goal (single goal from welcome step)
        if (isset($data['primary_goal']) && !empty($data['primary_goal'])) {
            $primary_goal = sanitize_text_field($data['primary_goal']);
            $onboarding_data['primary_goal'] = $primary_goal;
            $profile_updates['hair_goals'] = $primary_goal;
        }
    }

    if ($step === 'preferences') {
        // Hair length (optional)
        if (isset($data['hair_length']) && !empty($data['hair_length'])) {
            $hair_length = sanitize_text_field($data['hair_length']);
            $onboarding_data['hair_length'] = $hair_length;
            $profile_updates['hair_length'] = $hair_length;
        }

        // Hair concern (optional)
        if (isset($data['hair_concern']) && !empty($data['hair_concern'])) {
            $hair_concern = sanitize_text_field($data['hair_concern']);
            $onboarding_data['hair_concern'] = $hair_concern;
            // Store concern as additional metadata
            update_user_meta($user_id, 'myavana_hair_concern', $hair_concern);
        }

        // Preferred name (optional)
        if (isset($data['name']) && !empty($data['name'])) {
            $name = sanitize_text_field($data['name']);
            $onboarding_data['preferred_name'] = $name;
            update_user_meta($user_id, 'myavana_preferred_name', $name);
            // Also update first_name if user wants
            if (get_user_meta($user_id, 'first_name', true) === '') {
                update_user_meta($user_id, 'first_name', $name);
            }
        }
    }

    // Legacy support: Handle old 'goals' step (array of goals)
    if ($step === 'goals' && isset($data['goals']) && is_array($data['goals'])) {
        $onboarding_data['goals'] = array_map('sanitize_text_field', $data['goals']);
        $goals_string = implode(', ', $onboarding_data['goals']);
        $profile_updates['hair_goals'] = $goals_string;
    }

    // Update profile table with accumulated data
    if (!empty($profile_updates)) {
        // Check if profile exists
        $existing_profile = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE user_id = %d",
            $user_id
        ));

        if ($existing_profile) {
            // Update existing profile
            $wpdb->update(
                $table_name,
                $profile_updates,
                ['user_id' => $user_id],
                array_fill(0, count($profile_updates), '%s'),
                ['%d']
            );
        } else {
            // Create new profile
            $profile_updates['user_id'] = $user_id;
            $wpdb->insert(
                $table_name,
                $profile_updates,
                array_merge(['%d'], array_fill(0, count($profile_updates) - 1, '%s'))
            );
        }
    }

    if ($step === 'complete') {
        // Mark onboarding as fully completed
        update_user_meta($user_id, 'myavana_onboarding_completed', 'completed');
        update_user_meta($user_id, 'myavana_onboarding_completed_date', current_time('mysql'));
        $onboarding_data['completed'] = true;

        // Award achievement points for completing onboarding
        if (function_exists('myavana_award_achievement')) {
            myavana_award_achievement($user_id, 'onboarding_complete');
        }
    }

    // Update onboarding data
    update_user_meta($user_id, 'myavana_onboarding_data', $onboarding_data);

    wp_send_json_success([
        'message' => 'Step data saved successfully',
        'step' => $step,
        'data' => $onboarding_data
    ]);
}

// Complete onboarding
function myavana_complete_onboarding() {
    if (!is_user_logged_in()) {
        wp_send_json_error('Authentication required');
        return;
    }

    $user_id = get_current_user_id();

    // Mark onboarding as completed
    update_user_meta($user_id, 'myavana_onboarding_completed', 'completed');
    update_user_meta($user_id, 'myavana_onboarding_completed_date', current_time('mysql'));

    // Set initial profile data if not exists
    $existing_profile = get_user_meta($user_id, 'myavana_profile', true);
    if (empty($existing_profile)) {
        $initial_profile = [
            'onboarding_completed' => true,
            'hair_journey_stage' => 'beginning',
            'setup_date' => current_time('mysql')
        ];
        update_user_meta($user_id, 'myavana_profile', $initial_profile);
    }

    wp_send_json_success([
        'message' => 'Onboarding completed successfully',
        'status' => 'completed'
    ]);
}

// Get profile data for editing
function myavana_get_profile_data() {
    // Accept both nonce types for compatibility
    $nonce_valid = false;
    if (isset($_POST['nonce'])) {
        $nonce_valid = wp_verify_nonce($_POST['nonce'], 'myavana_nonce') ||
                      wp_verify_nonce($_POST['nonce'], 'myavana_profile_nonce');
    }

    if (!$nonce_valid) {
        wp_send_json_error('Security check failed');
        return;
    }

    if (!is_user_logged_in()) {
        wp_send_json_error('Authentication required');
        return;
    }

    $user_id = get_current_user_id();

    global $wpdb;
    $table_name = $wpdb->prefix . 'myavana_profiles';

    // Get profile from database
    $profile = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$table_name} WHERE user_id = %d",
        $user_id
    ));

    // Get user meta - check both old and new meta keys
    $hair_porosity = get_user_meta($user_id, 'hair_porosity', true) ?: get_user_meta($user_id, 'myavana_hair_porosity', true) ?: '';
    $hair_length = get_user_meta($user_id, 'hair_length', true) ?: get_user_meta($user_id, 'myavana_hair_length', true) ?: '';
    $hair_goals = get_user_meta($user_id, 'myavana_hair_goals_structured', true) ?: [];
    $current_routine = get_user_meta($user_id, 'myavana_current_routine', true) ?: [];
    $about_me = get_user_meta($user_id, 'myavana_about_me', true) ?: ($profile->bio ?? '');

    // Build response
    $profile_data = [
        'hair_type' => $profile->hair_type ?? '',
        'hair_porosity' => $hair_porosity,
        'hair_length' => $hair_length,
        'hair_journey_stage' => $profile->hair_journey_stage ?? '',
        'bio' => $about_me, // Use about_me from meta
        'hair_goals' => $hair_goals,
        'current_routine' => $current_routine
    ];

    wp_send_json_success($profile_data);
}
add_action('wp_ajax_myavana_get_profile_data', 'myavana_get_profile_data');

// Save profile from sidebar
function myavana_save_profile() {
    // Log for debugging
    error_log('myavana_save_profile called');
    error_log('POST data: ' . print_r($_POST, true));
    error_log('Nonce received: ' . ($_POST['nonce'] ?? 'NONE'));

    // Try to verify nonce - accept both types for compatibility
    $nonce_check = false;
    if (isset($_POST['nonce'])) {
        $nonce_check = wp_verify_nonce($_POST['nonce'], 'myavana_nonce') ||
                      wp_verify_nonce($_POST['nonce'], 'myavana_profile_nonce');
    }
    error_log('Nonce verification result: ' . ($nonce_check ? 'VALID' : 'INVALID'));

    if (!$nonce_check) {
        error_log('Nonce verification failed!');
        wp_send_json_error('Security check failed. Please refresh the page and try again.');
        return;
    }

    if (!is_user_logged_in()) {
        error_log('User not logged in');
        wp_send_json_error('Authentication required');
        return;
    }

    $user_id = get_current_user_id();

    // Get and sanitize inputs
    $hair_type = isset($_POST['hair_type']) ? sanitize_text_field($_POST['hair_type']) : '';
    $hair_porosity = isset($_POST['hair_porosity']) ? sanitize_text_field($_POST['hair_porosity']) : '';
    $hair_length = isset($_POST['hair_length']) ? sanitize_text_field($_POST['hair_length']) : '';
    $journey_stage = isset($_POST['journey_stage']) ? sanitize_text_field($_POST['journey_stage']) : '';
    $bio = isset($_POST['bio']) ? sanitize_textarea_field($_POST['bio']) : '';
    $hair_goals = isset($_POST['hair_goals']) ? $_POST['hair_goals'] : [];
    $current_routine = isset($_POST['current_routine']) ? $_POST['current_routine'] : [];

    // Validate required fields
    if (empty($hair_type)) {
        wp_send_json_error('Hair type is required');
        return;
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'myavana_profiles';

    // Check if profile exists
    $profile = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$table_name} WHERE user_id = %d",
        $user_id
    ));

    $profile_data = [
        'user_id' => $user_id,
        'hair_type' => $hair_type,
        'hair_journey_stage' => $journey_stage,
        'bio' => $bio,
        'hair_goals' => is_array($hair_goals) ? implode(',', array_map('sanitize_text_field', $hair_goals)) : '',
        'updated_at' => current_time('mysql')
    ];

    if ($profile) {
        // Update existing profile
        $wpdb->update(
            $table_name,
            $profile_data,
            ['user_id' => $user_id]
        );
    } else {
        // Create new profile
        $profile_data['created_at'] = current_time('mysql');
        $wpdb->insert($table_name, $profile_data);
    }

    // Update user meta for additional fields (save to both keys for compatibility)
    update_user_meta($user_id, 'hair_porosity', $hair_porosity);
    update_user_meta($user_id, 'hair_length', $hair_length);
    update_user_meta($user_id, 'myavana_hair_porosity', $hair_porosity);
    update_user_meta($user_id, 'myavana_hair_length', $hair_length);

    // Save bio/about_me to user meta (this is where it's actually used)
    update_user_meta($user_id, 'myavana_about_me', $bio);

    // Save goals if provided
    if (!empty($hair_goals)) {
        update_user_meta($user_id, 'myavana_hair_goals_structured', $hair_goals);
    }

    // Save routine if provided
    if (!empty($current_routine) && is_array($current_routine)) {
        update_user_meta($user_id, 'myavana_current_routine', $current_routine);
    }

    // Clear the user's data cache so changes reflect immediately
    if (function_exists('myavana_clear_user_cache')) {
        myavana_clear_user_cache($user_id);
    }

    error_log('Profile save successful for user ' . $user_id);

    wp_send_json_success([
        'message' => 'Profile updated successfully!',
        'profile' => $profile_data,
        'user_id' => $user_id
    ]);
}

// Register AJAX handlers
add_action('wp_ajax_myavana_load_entry_form', 'myavana_load_entry_form');
add_action('wp_ajax_myavana_save_simple_entry', 'myavana_save_simple_entry');
add_action('wp_ajax_myavana_load_onboarding_overlay', 'myavana_load_onboarding_overlay');
add_action('wp_ajax_myavana_onboarding_step', 'myavana_onboarding_step');
add_action('wp_ajax_myavana_skip_onboarding', 'myavana_skip_onboarding');
add_action('wp_ajax_myavana_complete_onboarding', 'myavana_complete_onboarding');
add_action('wp_ajax_myavana_save_profile', 'myavana_save_profile');
?>