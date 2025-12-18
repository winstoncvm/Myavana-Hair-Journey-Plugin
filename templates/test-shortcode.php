<?php

function myavana_handle_gemini_vision_api() {
    check_ajax_referer('myavana_chatbot_nonce', 'nonce');

    if (!isset($_POST['image_data']) || !isset($_POST['prompt'])) {
        error_log('Myavana Gemini Vision API: Missing image data or prompt');
        wp_send_json_error(['message' => 'Missing image data or prompt']);
        return;
    }

    $image_data = sanitize_text_field($_POST['image_data']);
    $prompt = sanitize_text_field($_POST['prompt']);

    // Retrieve Gemini API key securely - use WordPress constants or options
    $gemini_api_key = defined('MYAVANA_GEMINI_API_KEY') ? MYAVANA_GEMINI_API_KEY : get_option('myavana_gemini_api_key', '');
    if (empty($gemini_api_key)) {
        error_log('Myavana Gemini Vision API: Gemini API key not configured');
        wp_send_json_error(['message' => 'API key not configured']);
        return;
    }

    // Clean image data
    $image_data = preg_replace('/^data:image\/(png|jpeg);base64,/', '', $image_data);
    $mime_type = strpos($_POST['image_data'], 'data:image/png') === 0 ? 'image/png' : 'image/jpeg';

    $hair_analysis_prompt = "Analyze this hair image and provide detailed hair health analysis. Focus on: curl pattern (1A-4C scale), hydration level (%), health score (1-10), porosity (low/normal/high), thickness, and elasticity. Also provide personalized hair care recommendations. Format as JSON with keys: curl_pattern, hydration_level, health_score, porosity, thickness, elasticity, recommendations, analysis_summary.";

    $body = json_encode([
        'contents' => [
            [
                'parts' => [
                    ['text' => $hair_analysis_prompt . ' User prompt: ' . $prompt],
                    [
                        'inline_data' => [
                            'mime_type' => $mime_type,
                            'data' => $image_data
                        ]
                    ]
                ]
            ]
        ],
        'generationConfig' => [
            'temperature' => 0.4,
            'topK' => 32,
            'topP' => 1,
            'maxOutputTokens' => 1024,
            'responseMimeType' => 'application/json'
        ]
    ]);

    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=" . $gemini_api_key;

    $response = wp_remote_post($url, [
        'headers' => [
            'Content-Type' => 'application/json'
        ],
        'body' => $body,
        'timeout' => 60
    ]);

    if (is_wp_error($response)) {
        error_log('Myavana Gemini Vision API Error: ' . $response->get_error_message());
        wp_send_json_error(['message' => 'AI analysis temporarily unavailable']);
        return;
    }

    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);
    
    if ($response_code !== 200) {
        error_log('Myavana Gemini Vision API HTTP Error: ' . $response_code);
        wp_send_json_error(['message' => 'AI analysis service error']);
        return;
    }

    $data = json_decode($response_body, true);

    if ($data && isset($data['candidates'][0]['content']['parts'][0]['text'])) {
        $analysis_json = $data['candidates'][0]['content']['parts'][0]['text'];
        $analysis = json_decode($analysis_json, true);
        
        if (json_last_error() === JSON_ERROR_NONE && $analysis) {
            wp_send_json_success(['analysis' => $analysis]);
        } else {
            error_log('Myavana Gemini Vision API: Invalid JSON response');
            wp_send_json_error(['message' => 'Invalid analysis response']);
        }
    } else {
        error_log('Myavana Gemini Vision API: Invalid response structure');
        wp_send_json_error(['message' => 'Invalid response from vision API']);
    }
}
add_action('wp_ajax_myavana_gemini_vision_api', 'myavana_handle_gemini_vision_api');
add_action('wp_ajax_nopriv_myavana_gemini_vision_api', 'myavana_handle_gemini_vision_api');

// Gemini Live API session handler
function myavana_handle_gemini_live_session() {
    check_ajax_referer('myavana_chatbot_nonce', 'nonce');

    $gemini_api_key = defined('MYAVANA_GEMINI_API_KEY') ? MYAVANA_GEMINI_API_KEY : get_option('myavana_gemini_api_key', '');
    if (empty($gemini_api_key)) {
        wp_send_json_error(['message' => 'API configuration error']);
        return;
    }

    $action_type = sanitize_text_field($_POST['action_type'] ?? '');

    switch ($action_type) {
        case 'create_ephemeral_token':
            myavana_create_ephemeral_token($gemini_api_key);
            break;
        case 'start_session':
            myavana_start_gemini_live_session($gemini_api_key);
            break;
        case 'send_audio':
            myavana_send_audio_to_gemini($_POST, $gemini_api_key);
            break;
        case 'end_session':
            myavana_end_gemini_live_session();
            break;
        default:
            wp_send_json_error(['message' => 'Invalid action']);
    }
}
add_action('wp_ajax_myavana_gemini_live_session', 'myavana_handle_gemini_live_session');
add_action('wp_ajax_nopriv_myavana_gemini_live_session', 'myavana_handle_gemini_live_session');

// Create ephemeral token for secure client-side connection
function myavana_create_ephemeral_token($api_key) {
    try {
        $expire_time = date('c', time() + (30 * 60)); // 30 minutes from now
        $new_session_expire_time = date('c', time() + (2 * 60)); // 2 minutes from now

        $token_config = [
            'config' => [
                'uses' => 5, // Allow multiple reconnections
                'expireTime' => $expire_time,
                'newSessionExpireTime' => $new_session_expire_time,
                'liveConnectConstraints' => [
                    'model' => 'gemini-2.0-flash-live-001',
                    'config' => [
                        'responseModalities' => ['AUDIO'],
                        'sessionResumption' => [],
                        'contextWindowCompression' => [
                            'slidingWindow' => []
                        ],
                        'systemInstruction' => [
                            'parts' => [
                                ['text' => 'You are MYAVANA AI, a specialized hair care expert assistant. Provide personalized, professional hair care advice. Be friendly, knowledgeable, and supportive. Focus on natural hair care, curl patterns, hair health, and product recommendations. Keep responses conversational and encouraging. Respond with natural, expressive speech that shows empathy and expertise.']
                            ]
                        ],
                        'generationConfig' => [
                            'temperature' => 0.7,
                            'maxOutputTokens' => 512
                        ]
                    ]
                ]
            ]
        ];

        $response = wp_remote_post('https://generativelanguage.googleapis.com/v1alpha/authTokens', [
            'headers' => [
                'Content-Type' => 'application/json',
                'x-goog-api-key' => $api_key
            ],
            'body' => json_encode($token_config),
            'timeout' => 30
        ]);

        if (is_wp_error($response)) {
            throw new Exception('Token request failed: ' . $response->get_error_message());
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);

        if ($response_code !== 200) {
            error_log('Ephemeral token creation failed: ' . $response_code . ' - ' . $response_body);
            throw new Exception('Token creation failed');
        }

        $token_data = json_decode($response_body, true);

        if (!$token_data || !isset($token_data['name'])) {
            throw new Exception('Invalid token response');
        }

        wp_send_json_success([
            'token' => $token_data['name'],
            'expires_at' => $expire_time,
            'session_expires_at' => $new_session_expire_time
        ]);

    } catch (Exception $e) {
        error_log('Myavana Ephemeral Token Error: ' . $e->getMessage());
        wp_send_json_error(['message' => 'Failed to create session token']);
    }
}

function myavana_start_gemini_live_session($api_key) {
    // This function is now mainly for fallback/compatibility
    // The actual Live session is started client-side with ephemeral token
    wp_send_json_success([
        'session_id' => 'myavana_' . get_current_user_id() . '_' . time(),
        'status' => 'ready',
        'message' => 'Use ephemeral token for Live API connection'
    ]);
}

function myavana_send_audio_to_gemini($post_data, $api_key) {
    if (!isset($post_data['audio_data'])) {
        wp_send_json_error(['message' => 'No audio data provided']);
        return;
    }

    $audio_data = $post_data['audio_data'];
    $text_input = sanitize_text_field($post_data['text_input'] ?? '');
    
    // For now, use text-based Gemini API as Gemini Live API requires WebSocket connection
    // This is a simplified implementation for the demo
    $body = json_encode([
        'contents' => [
            [
                'parts' => [
                    ['text' => "Hair care question: " . $text_input]
                ]
            ]
        ],
        'generationConfig' => [
            'temperature' => 0.7,
            'maxOutputTokens' => 512
        ]
    ]);

    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=" . $api_key;

    $response = wp_remote_post($url, [
        'headers' => ['Content-Type' => 'application/json'],
        'body' => $body,
        'timeout' => 30
    ]);

    if (is_wp_error($response)) {
        wp_send_json_error(['message' => 'AI response error']);
        return;
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);
    
    if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
        wp_send_json_success([
            'response' => $data['candidates'][0]['content']['parts'][0]['text'],
            'type' => 'text'
        ]);
    } else {
        wp_send_json_error(['message' => 'Invalid AI response']);
    }
}

function myavana_end_gemini_live_session() {
    delete_transient('myavana_gemini_session_' . get_current_user_id());
    wp_send_json_success(['status' => 'session_ended']);
}

function myavana_test_shortcode($atts = []) {
    $atts = shortcode_atts(['user_id' => get_current_user_id()], $atts);
    $user_id = intval($atts['user_id']);
    $is_owner = $user_id === get_current_user_id();

    if (!is_user_logged_in()) {
        return '<p>Please <a href="' . home_url('/login') . '">log in</a> to use the chatbot.</p>';
    }

    if (!$is_owner) {
        return '<p style="color: var(--myavana-onyx);">You can only use the chatbot for your own profile.</p>';
    }

    // Enqueue enhanced Gemini Live assets
    wp_enqueue_style('myavana-gemini-chatbot-css', plugin_dir_url(__FILE__) . '../assets/css/gemini-chatbot.css', [], '1.0.0');
    wp_enqueue_script('myavana-gemini-live-api-js', plugin_dir_url(__FILE__) . '../assets/js/gemini-live-api.js', [], '1.0.0', true);
    wp_enqueue_script('myavana-gemini-chatbot-js', plugin_dir_url(__FILE__) . '../assets/js/gemini-chatbot.js', ['jquery', 'myavana-gemini-live-api-js'], '1.0.0', true);

    // Enqueue test script for development/debugging
    if (defined('WP_DEBUG') && WP_DEBUG) {
        wp_enqueue_script('myavana-live-api-test-js', plugin_dir_url(__FILE__) . '../assets/js/live-api-test.js', ['myavana-gemini-live-api-js'], '1.0.0', true);
    }
    
    wp_localize_script('myavana-gemini-chatbot-js', 'myavanaGeminiChatbot', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('myavana_chatbot_nonce'),
        'user_id' => $user_id,
        'vision_action' => 'myavana_gemini_vision_api',
        'live_session_action' => 'myavana_gemini_live_session',
        'live_api_url' => 'wss://generativelanguage.googleapis.com/ws/google.ai.generativelanguage.v1alpha.GenerativeService/BidiGenerateContent'
    ]);

    ob_start();
    ?>
    <div class="myvana-page-container">
        <div class="myavana-video-chatbot-container">
            <div class="myavana-chatbot-header">
                <div class="myavana-header-section pt-3 pb-1">
                    <span class="myavana-logo-section">
                        <img src="<?php echo esc_url( home_url() ); ?>/wp-content/plugins/myavana-hair-journey/assets/images/myavana-primary-logo.png" alt="Myavana Logo" />
                    </span>
                    <div class="text">
                        AI Haircare Assistant 
                    </div>
                </div>
                <p class="subtitle mt-2">Get personalized hair advice with our AI-powered video analysis</p>
                <div class="controls">
                    <button class="control-btn maximize-btn" aria-label="Toggle Fullscreen">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"></path>
                        </svg>
                    </button>
                    
                    <button class="mode-toggle" aria-label="Toggle Voice/Video" data-mode="voice">
                        <div class="button-overlay"></div>
                        <span>Switch to Video Mode<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 53 58" height="58" width="53">
                        <path stroke-width="9" stroke="currentColor" d="M44.25 36.3612L17.25 51.9497C11.5833 55.2213 4.5 51.1318 4.50001 44.5885L4.50001 13.4115C4.50001 6.86824 11.5833 2.77868 17.25 6.05033L44.25 21.6388C49.9167 24.9104 49.9167 33.0896 44.25 36.3612Z"></path>
                        </svg></span>
                    </button>
                </div>
            </div>

            <div class="myavana-chatbot-main">
                <div class="video-analysis-panel">
                    <audio id="remoteAudio" autoplay></audio>
                    <div class="video-container">
                        
                        <div class="myavana-voice-control-ai-card myavana-voice-ai-grid-container">
                            <input class="myavana-voice-control-ai-input" hidden type="checkbox" aria-label="audio-command" name="audio-command" id="audio-command"/>
                            <div class="inner-card">
                                <!-- Connection Quality Indicator -->
                                <div class="myavana-connection-quality">
                                    <div class="myavana-connection-bar"></div>
                                    <div class="myavana-connection-bar"></div>
                                    <div class="myavana-connection-bar"></div>
                                    <div class="myavana-connection-bar"></div>
                                </div>
                                <!-- Volume button in top right corner -->
                                <div class="volume-control-container">
                                <button class="volume-button">
                                    <svg
                                        class="volume"
                                        xmlns="http://www.w3.org/2000/svg"
                                        version="1.1"
                                        xmlns:xlink="http://www.w3.org/1999/xlink"
                                        width="512"
                                        height="512"
                                        x="0"
                                        y="0"
                                        viewBox="0 0 24 24"
                                        style="enable-background:new 0 0 512 512"
                                        xml:space="preserve"
                                    >
                                        <g>
                                        <path
                                            d="M18.36 19.36a1 1 0 0 1-.705-1.71C19.167 16.148 20 14.142 20 12s-.833-4.148-2.345-5.65a1 1 0 1 1 1.41-1.419C20.958 6.812 22 9.322 22 12s-1.042 5.188-2.935 7.069a.997.997 0 0 1-.705.291z"
                                            fill="currentColor"
                                            data-original="#000000"
                                        ></path>
                                        <path
                                            d="M15.53 16.53a.999.999 0 0 1-.703-1.711C15.572 14.082 16 13.054 16 12s-.428-2.082-1.173-2.819a1 1 0 1 1 1.406-1.422A6 6 0 0 1 18 12a6 6 0 0 1-1.767 4.241.996.996 0 0 1-.703.289zM12 22a1 1 0 0 1-.707-.293L6.586 17H4c-1.103 0-2-.897-2-2V9c0-1.103.897-2 2-2h2.586l4.707-4.707A.998.998 0 0 1 13 3v18a1 1 0 0 1-1 1z"
                                            fill="currentColor"
                                            data-original="#000000"
                                        ></path>
                                        </g>
                                    </svg>
                                </button>
                                
                                <label class="new-volume-slider">
                                    <input type="range" class="level" id="volumeSlider" min="0" max="1" step="0.1" value="1"/>
                                    
                                        <svg
                                        class="volume"
                                        id="volume-close-icon"
                                        xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 512 512"
                                        stroke-width="0"
                                        fill="currentColor"
                                        stroke="currentColor"
                                        class="icon"
                                        >
                                        <path
                                            d="M256 48a208 208 0 1 1 0 416 208 208 0 1 1 0-416zm0 464A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM175 175c-9.4 9.4-9.4 24.6 0 33.9l47 47-47 47c-9.4 9.4-9.4 24.6 0 33.9s24.6 9.4 33.9 0l47-47 47 47c9.4 9.4 24.6 9.4 33.9 0s9.4-24.6 0-33.9l-47-47 47-47c9.4-9.4 9.4-24.6 0-33.9s-24.6-9.4-33.9 0l-47 47-47-47c-9.4-9.4-24.6-9.4-33.9 0z"
                                        ></path>
                                        </svg>
                                </label>
                            

                                </div>
                                
                                <!-- Mute button in top left corner -->
                                <button id="muteButton" class="mute-button">🔇</button>
                                
                                <!-- Rest of your existing content -->
                                <div class="trigger-wrap">
                                <label class="trigger" for="audio-command"></label>
                                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" class="mic" stroke-linejoin="round" stroke-linecap="round" stroke-width="2.5" stroke="currentColor" fill="none">
                                    <path d="m19.5,10.89c0,4.44-3.36,8.04-7.5,8.04s-7.5-3.6-7.5-8.04"></path>
                                    <line x1="12" y1="22.42" x2="12" y2="18.93"></line>
                                    <rect x="8.38" y="1.81" width="7.23" height="13.25" rx="3.62" ry="3.62"></rect>
                                </svg>
                                <div class="spectrum">
                                    <b style="--index: 0;"></b><b style="--index: 1;"></b><b style="--index: 2;"></b><b style="--index: 3;"></b><b style="--index: 4;"></b>
                                    <b style="--index: 5;"></b><b style="--index: 6;"></b><b style="--index: 7;"></b><b style="--index: 8;"></b><b style="--index: 9;"></b>
                                </div>
                                </div>
                                <div class="content">
                                <span>Myavana Voice AI</span>. Start your hair journey hands-free with instant voice guidance and personalized care.
                                </div>
                            </div>
                        </div>
                        <div id="the-video" class="the-video hidden">
                            <video id="myavana-video" playsinline></video>
                            <canvas id="myavana-canvas" class="hidden" width="640" height="480"></canvas>
                            <div class="video-controls">
                                <button id="newphoto" class="control-btn" title="Take Photo">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <circle cx="12" cy="12" r="3"/><path d="M19 4H5a2 2 0 00-2 2v12a2 2 0 002 2h14a2 2 0 002-2V6a2 2 0 00-2-2z"/>
                                    </svg>
                                </button>
                                <button id="download" class="control-btn" title="Download Photo" disabled>
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
                                    </svg>
                                </button>
                            </div>
                            <p id="msg"></p>
                        </div>
                        
                    </div>
                    <div class="myavana-voice-ai-wrap">
                            <label for="voice">Select Voice: </label>
                            <select id="voice">
                                <option value="alloy" selected>Alloy</option>
                                <option value="echo">Echo</option>
                                <option value="shimmer">Shimmer</option>
                                <option value="ash">Ash</option>
                                <option value="ballad">Ballad</option>
                                <option value="coral">Coral</option>
                                <option value="sage">Sage</option>
                                <option value="verse">Verse</option>
                            </select>
                        </div>
                    <div class="hair-analysis-results">
                        <h3>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#e7a690" style="vertical-align:middle;margin-right:8px;">
                                <path d="M12 2.69l5.66 5.66a8 8 0 11-11.31 0z"/>
                            </svg>
                            Hair Insights
                        </h3>
                        <div id="hair-analysis-content" class="analysis-placeholder">
                            <p>Your hair analysis will appear here after we capture your first image.</p>
                        </div>
                        <div class="hair-metrics">
                            <div class="metric">
                                <div class="metric-label">Hydration</div>
                                <div class="metric-bar"><div id="hydration-level" class="metric-fill" style="width:0%"></div></div>
                            </div>
                            <div class="metric">
                                <div class="metric-label">Curl Pattern</div>
                                <div class="metric-bar"><div id="curl-pattern" class="metric-fill" style="width:0%"></div></div>
                            </div>
                            <div class="metric">
                                <div class="metric-label">Health Score</div>
                                <div class="metric-bar"><div id="health-score" class="metric-fill" style="width:0%"></div></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="chat-container">
                    <div id="chat-transcript" class="chat-messages">
                        <div id="log">
                            <div id="inputContainer"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="myavana-chatbot-footer m-20">
                <p>Myavana AI uses advanced computer vision to analyze your hair and provide personalized recommendations.</p>
            </div>
        </div>
    </div>
   
    <?php
    return ob_get_clean();
}
add_shortcode('myavana_test', 'myavana_test_shortcode');
?>
