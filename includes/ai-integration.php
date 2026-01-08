<?php
// includes/ai-integration.php
class Myavana_AI {
    private $api_key;
    private $image_model = 'gemini-2.0-flash-preview-image-generation';
    private $text_model = 'gemini-2.0-flash';
    private $url = 'https://generativelanguage.googleapis.com/v1beta/models/%s:%s?key=%s';

    public function __construct() {
        // Get API key from environment or wp-config
        // Change this to get the API key from the database
        $this->api_key = get_option('myavana_gemini_api_key');
        // $this->api_key = defined('MYAVANA_GEMINI_API_KEY') ? MYAVANA_GEMINI_API_KEY : null;
        
        // if (empty($this->api_key)) {
        //     throw new Exception('Myavana AI: Gemini API key not configured. Please add MYAVANA_GEMINI_API_KEY to wp-config.php');
        // }
    }

    public function generate_hairstyle_preview($image_base64, $style, $color, $user_data = []) {
        $user_id = get_current_user_id();
        $today = date('Y-m-d');
        $usage = get_user_meta($user_id, 'myavana_image_usage_' . $today, true) ?: 0;

        if ($usage >= 30) {
            error_log('Myavana AI: Daily limit (30) reached for user ' . $user_id);
            return ['error' => 'Daily image generation limit reached. Try again tomorrow.', 'fallback' => true];
        }

        $url = sprintf($this->url, $this->image_model, 'generateContent', $this->api_key);

        $image_base64 = preg_replace('/^data:image\/(png|jpeg);base64,/', '', $image_base64);
        $mime_type = strpos($image_base64, 'data:image/png') === 0 ? 'image/png' : 'image/jpeg';

        if (!base64_decode($image_base64, true)) {
            $error = 'Invalid base64 image data';
            error_log('Myavana AI: ' . $error);
            return ['error' => $error, 'fallback' => true];
        }

        $image_size = strlen($image_base64) / 1024;
        error_log("Myavana AI: Image input - MIME: $mime_type, Size: $image_size KB");

        $prompt = $this->build_hairstyle_prompt($style, $color, $user_data);

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt . " Also provide a title, a short description, stylist notes, and recommended products used (e.g., shampoo, conditioner) in the format: 'Title: [title], Description: [description], Stylist Notes: [notes], Products Used: [products]'."],
                        [
                            'inline_data' => [
                                'mime_type' => $mime_type,
                                'data' => $image_base64
                            ]
                        ]
                    ]
                ]
            ],
            'generationConfig' => [
                'responseModalities' => ['TEXT', 'IMAGE']
            ]
        ];

        error_log('Myavana AI: Sending request to Imagen - ' . print_r($payload, true));

        $response = wp_remote_post($url, [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode($payload),
            'timeout' => 30
        ]);

        if (is_wp_error($response)) {
            $error = 'API error: ' . $response->get_error_message();
            error_log('Myavana AI: ' . $error);
            return ['error' => $error, 'fallback' => true];
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        error_log('Myavana AI: Response code: ' . $response_code);
        error_log('Myavana AI: Response body: ' . print_r($body, true));

        if ($response_code !== 200 || empty($body['candidates']) || !isset($body['candidates'][0]['content']['parts'][1]['inlineData']['data'])) {
            $error = 'No image generated: ' . ($body['error']['message'] ?? 'Unknown error');
            error_log('Myavana AI: ' . $error);
            return ['error' => $error, 'fallback' => true];
        }

        $generated_image = $body['candidates'][0]['content']['parts'][1]['inlineData']['data'];
        $metadata = $body['candidates'][0]['content']['parts'][0]['text'] ?? '';

        $metadata_array = [];
        if ($metadata) {
            preg_match('/Title: (.*), Description: (.*), Stylist Notes: (.*), Products Used: (.*)/', $metadata, $matches);
            if (!empty($matches)) {
                $metadata_array = [
                    'title' => trim($matches[1]),
                    'description' => trim($matches[2]),
                    'stylist_notes' => trim($matches[3]),
                    'products_used' => trim($matches[4])
                ];
            }
        }

        update_user_meta($user_id, 'myavana_image_usage_' . $today, $usage + 1);
        return [
            'image' => 'data:image/png;base64,' . $generated_image,
            'fallback' => false,
            'metadata' => $metadata_array
        ];
    }

    private function build_hairstyle_prompt($style, $color, $user_data) {
        $style_descriptions = [
            'bob' => 'short, sleek bob haircut, chin-length, smooth and polished',
            'curls' => 'voluminous, bouncy curls, shoulder-length, vibrant and full',
            'pixie' => 'edgy pixie cut with textured layers',
            'long-waves' => 'elegant long waves for a flowing style',
            'braids' => 'intricate cornrow braids for protective styling',
            'updo' => 'sophisticated updo with elegant twists'
        ];

        $color_descriptions = [
            'black' => 'jet black, glossy finish',
            'brown' => 'rich brown, warm undertones',
            'blonde' => 'bright blonde, golden tones',
            'red' => 'vibrant red, fiery hue'
        ];

        $base_prompt = "Generate a photorealistic portrait image of the person in the provided photo with a {$style_descriptions[$style]} hairstyle in {$color_descriptions[$color]}. Keep the face and skin tone unchanged. Use a neutral studio background with soft lighting to highlight hair details.";

        if (!empty($user_data['hair_journey'])) {
            $base_prompt .= " The hairstyle suits a person in the '{$user_data['hair_journey']}' stage.";
        }
        if (!empty($user_data['hair_health'])) {
            $base_prompt .= " The hair appears healthy, rated {$user_data['hair_health']}/10.";
        }
        if (!empty($user_data['additional_info'])) {
            $base_prompt .= " Consider: {$user_data['additional_info']}.";
        }

        return $base_prompt;
    }

    /**
     * Comprehensive Hair Analysis using Gemini Vision API
     *
     * @param string $image_base64 Base64 encoded image data
     * @param array $user_context Optional user context for personalized analysis
     * @return array Analysis results or error
     */
    public function analyze_hair_comprehensive($image_base64, $user_context = []) {
        $user_id = get_current_user_id();
        $today = date('Y-m-d');
        $usage = get_user_meta($user_id, 'myavana_analysis_usage_' . $today, true) ?: 0;

        // Check daily analysis limit
        if ($usage >= 30) {
            error_log('Myavana AI: Daily analysis limit (30) reached for user ' . $user_id);
            return ['error' => 'Daily hair analysis limit reached. Try again tomorrow.'];
        }

        $url = sprintf($this->url, $this->text_model, 'generateContent', $this->api_key);

        // Clean and validate image data
        $image_base64 = preg_replace('/^data:image\/(png|jpeg|jpg);base64,/', '', $image_base64);

        if (!base64_decode($image_base64, true)) {
            $error = 'Invalid base64 image data';
            error_log('Myavana AI: ' . $error);
            return ['error' => $error];
        }

        // Determine MIME type
        $mime_type = 'image/jpeg'; // Default
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $decoded_image = base64_decode($image_base64);
        if ($decoded_image) {
            $detected_mime = $finfo->buffer($decoded_image);
            if (in_array($detected_mime, ['image/jpeg', 'image/png', 'image/jpg'])) {
                $mime_type = $detected_mime;
            }
        }

        $image_size = strlen($image_base64) / 1024;
        error_log("Myavana AI Hair Analysis: MIME: $mime_type, Size: $image_size KB");

        // Build comprehensive analysis prompt
        $prompt = $this->build_hair_analysis_prompt($user_context);

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                        [
                            'inline_data' => [
                                'mime_type' => $mime_type,
                                'data' => $image_base64
                            ]
                        ]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.4,
                'topK' => 32,
                'topP' => 1,
                'maxOutputTokens' => 2048,
                'responseMimeType' => 'application/json'
            ]
        ];

        error_log('Myavana AI: Sending hair analysis request');

        $response = wp_remote_post($url, [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode($payload),
            'timeout' => 45
        ]);

        if (is_wp_error($response)) {
            $error = 'API error: ' . $response->get_error_message();
            error_log('Myavana AI: ' . $error);
            return ['error' => $error];
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        error_log('Myavana AI: Analysis response code: ' . $response_code);

        if ($response_code !== 200) {
            $error = 'API error: ' . ($body['error']['message'] ?? 'Unknown error');
            error_log('Myavana AI: ' . $error);
            return ['error' => $error];
        }

        if (empty($body['candidates'][0]['content']['parts'][0]['text'])) {
            $error = 'No analysis generated';
            error_log('Myavana AI: ' . $error);
            return ['error' => $error];
        }

        $analysis_text = $body['candidates'][0]['content']['parts'][0]['text'];
        $analysis_data = json_decode($analysis_text, true);

        if (!$analysis_data) {
            // If JSON parsing fails, try to extract structured data
            $analysis_data = $this->parse_analysis_fallback($analysis_text);
        }

        // Increment usage counter
        update_user_meta($user_id, 'myavana_analysis_usage_' . $today, $usage + 1);

        // Log successful analysis
        error_log('Myavana AI: Hair analysis completed successfully for user ' . $user_id);

        return [
            'success' => true,
            'analysis' => $analysis_data,
            'raw_response' => $analysis_text,
            'usage_count' => $usage + 1
        ];
    }

    /**
     * Build comprehensive hair analysis prompt
     */
    private function build_hair_analysis_prompt($user_context = []) {
        $user_info = '';
        if (!empty($user_context)) {
            $user_info = "User context: " . json_encode($user_context) . "\n\n";
        }

        return $user_info . "
Analyze this hair image for a comprehensive professional haircare consultation. Provide a detailed analysis in valid JSON format with the following structure:

{
  \"environment\": \"Description of setting, lighting, background (indoor/outdoor). If unclear, state 'Not visible'\",
  \"user_description\": \"Observable characteristics like attire, posture. If not visible, state 'Not visible'\",
  \"mood_demeanor\": \"Inferred mood based on visible cues. If not visible, state 'Not visible'\",
  \"hair_analysis\": {
    \"type\": \"Hair type (straight, wavy, curly, coily)\",
    \"curl_pattern\": \"Curl pattern (1A, 2B, 3C, 4C etc.)\",
    \"length\": \"Hair length (short, medium, long, or specific measurement)\",
    \"texture\": \"Hair texture (fine, medium, coarse)\",
    \"density\": \"Hair density (low, medium, high)\",
    \"hydration\": \"Hydration level as percentage (0-100)\",
    \"health_score\": \"Overall health score (0-100)\",
    \"hairstyle\": \"Current hairstyle description\",
    \"damage\": \"Visible damage (split ends, breakage, frizz) or 'None observed'\",
    \"scalp_health\": \"Scalp condition and score (0-100)\",
    \"hair_color\": \"Hair color description\",
    \"porosity\": \"Hair porosity (low, medium, high)\",
    \"elasticity\": \"Hair elasticity score (0-100)\",
    \"strand_thickness\": \"Individual strand thickness\",
    \"growth_pattern\": \"Observable growth patterns\"
  },
  \"recommendations\": [
    \"Specific haircare recommendation 1\",
    \"Specific haircare recommendation 2\",
    \"Specific haircare recommendation 3\",
    \"Specific haircare recommendation 4\"
  ],
  \"products\": [
    {\"name\": \"Product Name 1\", \"id\": \"prod_001\", \"match\": 85},
    {\"name\": \"Product Name 2\", \"id\": \"prod_002\", \"match\": 78},
    {\"name\": \"Product Name 3\", \"id\": \"prod_003\", \"match\": 92}
  ],
  \"summary\": \"Concise 50-100 word summary of hair analysis for user communication\",
  \"full_context\": \"Detailed narrative combining all observations for future AI features\",
  \"confidence_level\": \"Analysis confidence percentage (0-100)\",
  \"analysis_date\": \"" . date('Y-m-d H:i:s') . "\",
  \"recommendations_priority\": [\"high\", \"medium\", \"low\"]
}

Important guidelines:
- If any aspect is unclear or not visible, explicitly state so
- Provide realistic percentage scores based on visible evidence
- Focus on actionable recommendations
- Ensure all JSON is properly formatted and valid
- Be specific and professional in language
- Consider hair health, styling needs, and maintenance requirements
";
    }

    /**
     * Fallback parser for when JSON parsing fails
     */
    private function parse_analysis_fallback($text) {
        // Basic fallback structure
        $fallback = [
            'environment' => 'Analysis available but format parsing failed',
            'user_description' => 'See full analysis text',
            'mood_demeanor' => 'Not determined',
            'hair_analysis' => [
                'type' => 'Analysis available',
                'curl_pattern' => 'See full text',
                'length' => 'See full text',
                'texture' => 'See full text',
                'density' => 'See full text',
                'hydration' => 70,
                'health_score' => 75,
                'hairstyle' => 'See full text',
                'damage' => 'See full text',
                'scalp_health' => 'See full text',
                'hair_color' => 'See full text',
                'porosity' => 'medium',
                'elasticity' => 70,
                'strand_thickness' => 'medium',
                'growth_pattern' => 'See full text'
            ],
            'recommendations' => [
                'Regular moisturizing treatments',
                'Gentle sulfate-free shampoo',
                'Weekly deep conditioning',
                'Heat protection when styling'
            ],
            'products' => [
                ['name' => 'Moisturizing Shampoo', 'id' => 'prod_001', 'match' => 80],
                ['name' => 'Deep Conditioner', 'id' => 'prod_002', 'match' => 85],
                ['name' => 'Leave-in Treatment', 'id' => 'prod_003', 'match' => 75]
            ],
            'summary' => 'Hair analysis completed. See detailed recommendations for personalized care routine.',
            'full_context' => $text,
            'confidence_level' => 60,
            'analysis_date' => date('Y-m-d H:i:s'),
            'recommendations_priority' => ['high', 'medium', 'medium', 'low']
        ];

        return $fallback;
    }

    /**
     * Simple hair analysis with vision for free tier
     * No rate limiting (handled by caller)
     *
     * @param string $image_base64 Base64 encoded image data
     * @param string $custom_prompt Optional custom prompt
     * @return array Analysis results or error
     */
    public function analyze_hair_with_vision($image_base64, $custom_prompt = '') {
        $url = sprintf($this->url, $this->text_model, 'generateContent', $this->api_key);

        // Clean and validate image data
        $image_base64 = preg_replace('/^data:image\/(png|jpeg|jpg);base64,/', '', $image_base64);

        if (!base64_decode($image_base64, true)) {
            $error = 'Invalid base64 image data';
            error_log('Myavana AI: ' . $error);
            return ['error' => $error];
        }

        // Determine MIME type
        $mime_type = 'image/jpeg'; // Default
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $decoded_image = base64_decode($image_base64);
        if ($decoded_image) {
            $detected_mime = $finfo->buffer($decoded_image);
            if (in_array($detected_mime, ['image/jpeg', 'image/png', 'image/jpg'])) {
                $mime_type = $detected_mime;
            }
        }

        $image_size = strlen($image_base64) / 1024;
        error_log("Myavana AI Free Analysis: MIME: $mime_type, Size: $image_size KB");

        // Use custom prompt or default
        $prompt = !empty($custom_prompt) ? $custom_prompt : "Analyze this hair image and provide insights about hair type, health, and care recommendations.";

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                        [
                            'inline_data' => [
                                'mime_type' => $mime_type,
                                'data' => $image_base64
                            ]
                        ]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.4,
                'topK' => 32,
                'topP' => 1,
                'maxOutputTokens' => 2048
            ]
        ];

        error_log('Myavana AI: Sending free hair analysis request');

        $response = wp_remote_post($url, [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode($payload),
            'timeout' => 45
        ]);

        if (is_wp_error($response)) {
            $error = 'API error: ' . $response->get_error_message();
            error_log('Myavana AI: ' . $error);
            return ['error' => $error];
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        error_log('Myavana AI: Free analysis response code: ' . $response_code);

        if ($response_code !== 200) {
            $error = 'API error: ' . ($body['error']['message'] ?? 'Unknown error');
            error_log('Myavana AI: ' . $error);
            return ['error' => $error];
        }

        if (empty($body['candidates'][0]['content']['parts'][0]['text'])) {
            $error = 'No analysis generated';
            error_log('Myavana AI: ' . $error);
            return ['error' => $error];
        }

        $analysis_text = $body['candidates'][0]['content']['parts'][0]['text'];

        error_log('Myavana AI: Free hair analysis completed successfully');

        return [
            'success' => true,
            'analysis' => $analysis_text
        ];
    }

    /**
     * Get AI-generated tip based on user context
     *
     * HOW TO UPDATE AI RECOMMENDATIONS:
     *
     * 1. MODIFY THE PROMPT (Line 486):
     *    - Change the prompt text to adjust the style, tone, or focus of recommendations
     *    - Example prompts:
     *      * "Provide expert hair care advice for: $context"
     *      * "Give a personalized hair tip with emoji based on: $context"
     *      * "Suggest a product recommendation for: $context"
     *
     * 2. CHANGE THE AI MODEL:
     *    - Update $this->text_model in __construct() (around line 4)
     *    - Available models: gemini-pro, gemini-1.5-flash, etc.
     *
     * 3. ADJUST RESPONSE LENGTH:
     *    - Add maxOutputTokens parameter to the request body:
     *      'generationConfig' => ['maxOutputTokens' => 100]
     *
     * 4. CUSTOMIZE FOR DIFFERENT CONTEXTS:
     *    - Check $context parameter to provide different prompts based on user action
     *    - Example: if (strpos($context, 'entry') !== false) { ... }
     *
     * 5. ADD FILTERING/PERSONALIZATION:
     *    - Access user data before generating tip
     *    - Include user's hair type, goals, etc. in the prompt
     *
     * @param string $context - Description of user action (e.g., "User added a manual hair journey entry")
     * @return string - AI-generated tip or fallback message
     */
    public function get_ai_tip($context) {
        $url = sprintf($this->url, $this->text_model, 'generateContent', $this->api_key);

        // CUSTOMIZE THIS PROMPT to change recommendation style/content:
        $prompt = "Provide a concise, fun hair care tip based on this context: $context";

        $response = wp_remote_post($url, [
            'body' => json_encode([
                'contents' => [
                    ['parts' => [['text' => $prompt]]]
                ]
            ]),
            'headers' => ['Content-Type' => 'application/json']
        ]);

        if (is_wp_error($response)) {
            error_log('Myavana AI: API error - ' . $response->get_error_message());
            return 'Could not fetch tip. Try again later!';
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        return $body['candidates'][0]['content']['parts'][0]['text'] ?? 'No tip available.';
    }
  
}

