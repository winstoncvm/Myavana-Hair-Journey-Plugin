<?php
/**
 * MYAVANA Smart Entry AI Analysis Handlers
 * Camera-first workflow with AI pre-population
 * @version 2.3.5
 */

if (!defined('ABSPATH')) exit;

/**
 * Handle Smart Entry AI Analysis
 * Analyzes uploaded/captured image and returns structured hair data
 */
function myavana_smart_entry_analyze() {
    // Security check
    check_ajax_referer('myavana_gamification', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'User not authenticated']);
        return;
    }

    $user_id = get_current_user_id();
    $image_data = isset($_POST['image_data']) ? $_POST['image_data'] : '';

    if (empty($image_data)) {
        wp_send_json_error(['message' => 'No image data provided']);
        return;
    }

    // Check AI analysis limit (30 per week)
    global $wpdb;
    $profile_table = $wpdb->prefix . 'myavana_profiles';
    $profile = $wpdb->get_row($wpdb->prepare(
        "SELECT ai_analyses_used, ai_analyses_reset FROM $profile_table WHERE user_id = %d",
        $user_id
    ));

    // Reset counter if week has passed
    $now = current_time('timestamp');
    if ($profile && $profile->ai_analyses_reset && strtotime($profile->ai_analyses_reset) < $now) {
        $wpdb->update(
            $profile_table,
            [
                'ai_analyses_used' => 0,
                'ai_analyses_reset' => date('Y-m-d H:i:s', strtotime('+1 week'))
            ],
            ['user_id' => $user_id],
            ['%d', '%s'],
            ['%d']
        );
        $profile->ai_analyses_used = 0;
    }

    // Check limit
    if ($profile && $profile->ai_analyses_used >= 30) {
        wp_send_json_error([
            'message' => 'You have reached your weekly AI analysis limit (30). Resets in ' .
                         human_time_diff(strtotime($profile->ai_analyses_reset), $now) . '.'
        ]);
        return;
    }

    try {
        // Initialize AI class
        if (!class_exists('Myavana_AI')) {
            require_once MYAVANA_DIR . 'includes/ai-integration.php';
        }

        $ai = new Myavana_AI();

        // Process image data (remove data:image prefix if present)
        if (strpos($image_data, 'data:image') === 0) {
            $image_parts = explode(',', $image_data);
            $image_base64 = isset($image_parts[1]) ? $image_parts[1] : $image_data;
        } else {
            $image_base64 = $image_data;
        }

        // Craft AI prompt for structured analysis
        $prompt = "Analyze this hair image and provide a structured assessment. Return your analysis in this exact format:

HEALTH_SCORE: [number from 1-10]
HAIR_TYPE: [e.g., 4C, 3B, 2A, etc. or 'Unable to determine']
TEXTURE: [Fine/Medium/Coarse]
POROSITY: [Low/Medium/High or 'Unable to determine']
MOISTURE_LEVEL: [Dry/Balanced/Well-moisturized]
SUMMARY: [2-3 sentence summary of hair condition and observations]
RECOMMENDATIONS: [3-5 specific actionable recommendations, separated by |]

Focus on: overall hair health, moisture, shine, texture definition, scalp visibility, damage indicators.";

        // Call Gemini API
        $ai_response = $ai->analyze_with_vision($image_base64, $prompt);

        if (is_wp_error($ai_response)) {
            throw new Exception($ai_response->get_error_message());
        }

        // Parse AI response
        $parsed_data = parse_smart_entry_ai_response($ai_response);

        // Increment AI usage counter
        if ($profile) {
            $wpdb->update(
                $profile_table,
                ['ai_analyses_used' => intval($profile->ai_analyses_used) + 1],
                ['user_id' => $user_id],
                ['%d'],
                ['%d']
            );
        } else {
            // Create profile if doesn't exist
            $wpdb->insert(
                $profile_table,
                [
                    'user_id' => $user_id,
                    'ai_analyses_used' => 1,
                    'ai_analyses_reset' => date('Y-m-d H:i:s', strtotime('+1 week'))
                ],
                ['%d', '%d', '%s']
            );
        }

        // Log the analysis
        error_log(sprintf('[Smart Entry] AI analysis completed for user %d. Score: %d/10', $user_id, $parsed_data['health_rating']));

        wp_send_json_success($parsed_data);

    } catch (Exception $e) {
        error_log('[Smart Entry] AI analysis error: ' . $e->getMessage());
        wp_send_json_error(['message' => 'AI analysis failed: ' . $e->getMessage()]);
    }
}
add_action('wp_ajax_myavana_smart_entry_analyze', 'myavana_smart_entry_analyze');

/**
 * Parse AI response into structured data
 */
function parse_smart_entry_ai_response($ai_text) {
    $data = [
        'health_rating' => 5, // Default middle value
        'hair_type' => 'Unknown',
        'texture' => 'Medium',
        'porosity' => 'Medium',
        'moisture_level' => 'Balanced',
        'analysis_summary' => '',
        'recommendations' => [],
        'raw_response' => $ai_text
    ];

    // Extract health score
    if (preg_match('/HEALTH_SCORE:\s*(\d+)/i', $ai_text, $matches)) {
        $score = intval($matches[1]);
        $data['health_rating'] = max(1, min(10, $score)); // Clamp between 1-10
    }

    // Extract hair type
    if (preg_match('/HAIR_TYPE:\s*([^\n]+)/i', $ai_text, $matches)) {
        $hair_type = trim($matches[1]);
        if (!empty($hair_type) && stripos($hair_type, 'unable') === false) {
            $data['hair_type'] = $hair_type;
        }
    }

    // Extract texture
    if (preg_match('/TEXTURE:\s*([^\n]+)/i', $ai_text, $matches)) {
        $data['texture'] = trim($matches[1]);
    }

    // Extract porosity
    if (preg_match('/POROSITY:\s*([^\n]+)/i', $ai_text, $matches)) {
        $data['porosity'] = trim($matches[1]);
    }

    // Extract moisture level
    if (preg_match('/MOISTURE_LEVEL:\s*([^\n]+)/i', $ai_text, $matches)) {
        $data['moisture_level'] = trim($matches[1]);
    }

    // Extract summary
    if (preg_match('/SUMMARY:\s*([^\n]+(?:\n(?!RECOMMENDATIONS:)[^\n]+)*)/i', $ai_text, $matches)) {
        $data['analysis_summary'] = trim($matches[1]);
    }

    // Extract recommendations
    if (preg_match('/RECOMMENDATIONS:\s*(.+?)(?:\n\n|\z)/is', $ai_text, $matches)) {
        $recommendations_text = trim($matches[1]);
        // Split by pipe or newlines
        $recommendations = preg_split('/\s*[|\n]\s*/', $recommendations_text);
        $recommendations = array_filter($recommendations, function($rec) {
            return !empty(trim($rec));
        });
        $data['recommendations'] = array_values($recommendations);
    }

    // Fallback: If no structured data found, use entire response as summary
    if (empty($data['analysis_summary']) && !empty($ai_text)) {
        $data['analysis_summary'] = substr($ai_text, 0, 500);
    }

    return $data;
}

/**
 * Get user's remaining AI analyses for the week
 */
function myavana_get_ai_analyses_remaining() {
    check_ajax_referer('myavana_gamification', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'User not authenticated']);
        return;
    }

    $user_id = get_current_user_id();
    global $wpdb;
    $profile_table = $wpdb->prefix . 'myavana_profiles';

    $profile = $wpdb->get_row($wpdb->prepare(
        "SELECT ai_analyses_used, ai_analyses_reset FROM $profile_table WHERE user_id = %d",
        $user_id
    ));

    $used = $profile ? intval($profile->ai_analyses_used) : 0;
    $remaining = max(0, 30 - $used);
    $reset_time = $profile && $profile->ai_analyses_reset ? $profile->ai_analyses_reset : date('Y-m-d H:i:s', strtotime('+1 week'));

    wp_send_json_success([
        'used' => $used,
        'remaining' => $remaining,
        'limit' => 30,
        'reset_at' => $reset_time,
        'reset_human' => human_time_diff(strtotime($reset_time), current_time('timestamp'))
    ]);
}
add_action('wp_ajax_myavana_get_ai_analyses_remaining', 'myavana_get_ai_analyses_remaining');
