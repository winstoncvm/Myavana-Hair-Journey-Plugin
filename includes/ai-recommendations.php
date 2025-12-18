<?php
/**
 * AI-Powered Hair Recommendations System
 * 
 * This class provides intelligent hair care recommendations based on:
 * - User's hair history and data
 * - Environmental factors (weather, humidity)
 * - Product usage patterns
 * - Hair goals and preferences
 * - Community data and trends
 */

if (!defined('ABSPATH')) {
    exit;
}

class Myavana_AI_Recommendations {
    
    private $user_id;
    private $hair_data;
    private $environmental_factors;
    private $product_history;
    
    public function __construct($user_id = null) {
        $this->user_id = $user_id ?: get_current_user_id();
        $this->init();
    }
    
    private function init() {
        add_action('wp_ajax_get_ai_recommendations', array($this, 'get_recommendations'));
        add_action('wp_ajax_nopriv_get_ai_recommendations', array($this, 'get_recommendations'));
        add_action('wp_ajax_rate_recommendation', array($this, 'rate_recommendation'));
        add_action('wp_ajax_get_personalized_routine', array($this, 'get_personalized_routine'));
    }
    
    /**
     * Get AI-powered recommendations for the user
     */
    public function get_recommendations() {
        if (!wp_verify_nonce($_POST['nonce'], 'myavana_nonce')) {
            wp_die('Security check failed');
        }
        
        $recommendation_type = sanitize_text_field($_POST['type'] ?? 'general');
        
        // Load user's hair data
        $this->load_user_hair_data();
        
        // Load environmental factors
        $this->load_environmental_factors();
        
        // Load product history
        $this->load_product_history();
        
        $recommendations = array();
        
        switch ($recommendation_type) {
            case 'products':
                $recommendations = $this->get_product_recommendations();
                break;
            case 'routine':
                $recommendations = $this->get_routine_recommendations();
                break;
            case 'treatments':
                $recommendations = $this->get_treatment_recommendations();
                break;
            case 'styling':
                $recommendations = $this->get_styling_recommendations();
                break;
            default:
                $recommendations = $this->get_general_recommendations();
                break;
        }
        
        wp_send_json_success($recommendations);
    }
    
    private function load_user_hair_data() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'myavana_hair_journey';
        
        $this->hair_data = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE user_id = %d ORDER BY entry_date DESC LIMIT 50",
            $this->user_id
        ));
        
        // Get hair profile data
        $this->hair_profile = array(
            'hair_type' => get_user_meta($this->user_id, 'hair_type', true),
            'porosity' => get_user_meta($this->user_id, 'hair_porosity', true),
            'density' => get_user_meta($this->user_id, 'hair_density', true),
            'length' => get_user_meta($this->user_id, 'hair_length', true),
            'goals' => get_user_meta($this->user_id, 'hair_goals', true),
            'concerns' => get_user_meta($this->user_id, 'hair_concerns', true),
            'allergies' => get_user_meta($this->user_id, 'hair_allergies', true),
        );
    }
    
    private function load_environmental_factors() {
        // Get user location for weather data
        $location = get_user_meta($this->user_id, 'location', true);
        
        if (!$location) {
            $location = 'New York'; // Default location
        }
        
        // Simulate weather API call (in real implementation, use actual weather API)
        $this->environmental_factors = array(
            'humidity' => rand(30, 80),
            'temperature' => rand(60, 90),
            'season' => $this->get_current_season(),
            'air_quality' => rand(1, 5), // 1-5 scale
            'uv_index' => rand(1, 10)
        );
    }
    
    private function load_product_history() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'myavana_product_usage';
        
        $this->product_history = $wpdb->get_results($wpdb->prepare(
            "SELECT product_name, product_type, usage_frequency, effectiveness_rating, last_used 
             FROM $table_name WHERE user_id = %d ORDER BY last_used DESC",
            $this->user_id
        ));
    }
    
    private function get_product_recommendations() {
        $recommendations = array();
        
        // Analyze hair type and concerns
        $hair_type = $this->hair_profile['hair_type'];
        $concerns = $this->hair_profile['concerns'];
        $porosity = $this->hair_profile['porosity'];
        
        // AI Logic for product recommendations
        if (strpos($concerns, 'dryness') !== false) {
            $recommendations[] = array(
                'type' => 'moisturizing_shampoo',
                'title' => 'Deep Moisturizing Shampoo',
                'reason' => 'Your hair shows signs of dryness. A moisturizing shampoo will help restore hydration.',
                'confidence' => 0.85,
                'products' => array(
                    'SheaMoisture Coconut & Hibiscus Curl & Shine Shampoo',
                    'Aussie 3 Minute Miracle Moist Deep Conditioner',
                    'Cantu Sulfate-Free Cleansing Shampoo'
                )
            );
        }
        
        if ($porosity === 'high') {
            $recommendations[] = array(
                'type' => 'protein_treatment',
                'title' => 'Protein Treatment',
                'reason' => 'High porosity hair benefits from protein treatments to strengthen hair structure.',
                'confidence' => 0.78,
                'products' => array(
                    'Aphogee Two-Step Protein Treatment',
                    'Shea Moisture Manuka Honey & Mafura Oil Intensive Hydration Masque',
                    'Olaplex Hair Perfector No 3'
                )
            );
        }
        
        // Weather-based recommendations
        if ($this->environmental_factors['humidity'] > 70) {
            $recommendations[] = array(
                'type' => 'humidity_protection',
                'title' => 'Anti-Humidity Products',
                'reason' => 'High humidity in your area calls for anti-frizz and humidity protection products.',
                'confidence' => 0.72,
                'products' => array(
                    'Living Proof No Frizz Nourishing Styling Cream',
                    'Moroccanoil Frizz Shield Spray',
                    'John Frieda Frizz Ease Extra Strength Serum'
                )
            );
        }
        
        // Season-based recommendations
        $season = $this->environmental_factors['season'];
        if ($season === 'winter') {
            $recommendations[] = array(
                'type' => 'winter_protection',
                'title' => 'Winter Hair Protection',
                'reason' => 'Cold weather can make hair dry and brittle. These products provide extra moisture and protection.',
                'confidence' => 0.68,
                'products' => array(
                    'Olaplex Hair Oil No. 7 Bonding Oil',
                    'Briogeo Don\'t Despair, Repair! Deep Conditioning Mask',
                    'Neutrogena Anti-Residue Clarifying Shampoo'
                )
            );
        }
        
        return $this->rank_recommendations($recommendations);
    }
    
    private function get_routine_recommendations() {
        $routine = array();
        
        $hair_type = $this->hair_profile['hair_type'];
        $goals = $this->hair_profile['goals'];
        
        // Base routine structure
        $routine = array(
            'wash_frequency' => $this->calculate_optimal_wash_frequency(),
            'weekly_routine' => array(),
            'monthly_treatments' => array(),
            'daily_care' => array()
        );
        
        // Customize based on hair type
        if (strpos($hair_type, 'curly') !== false || strpos($hair_type, 'coily') !== false) {
            $routine['weekly_routine'] = array(
                'monday' => 'Co-wash or water rinse',
                'wednesday' => 'Deep conditioning treatment',
                'friday' => 'Gentle shampoo + conditioner',
                'sunday' => 'Protein treatment (bi-weekly)'
            );
        } else {
            $routine['weekly_routine'] = array(
                'monday' => 'Clarifying shampoo',
                'wednesday' => 'Regular wash + conditioner',
                'friday' => 'Dry shampoo refresh',
                'sunday' => 'Hair mask treatment'
            );
        }
        
        // Daily care recommendations
        $routine['daily_care'] = array(
            'morning' => 'Apply leave-in conditioner and styling products',
            'night' => 'Use silk pillowcase and protective hairstyle',
            'ongoing' => 'Stay hydrated and maintain healthy diet'
        );
        
        // Monthly treatments based on goals
        if (strpos($goals, 'growth') !== false) {
            $routine['monthly_treatments'][] = 'Scalp massage with growth oils';
            $routine['monthly_treatments'][] = 'Professional scalp treatment';
        }
        
        if (strpos($goals, 'repair') !== false) {
            $routine['monthly_treatments'][] = 'Professional deep conditioning';
            $routine['monthly_treatments'][] = 'Protein reconstruction treatment';
        }
        
        return $routine;
    }
    
    private function get_treatment_recommendations() {
        $treatments = array();
        
        $concerns = $this->hair_profile['concerns'];
        $hair_condition = $this->analyze_hair_condition();
        
        // Scalp treatments
        if (strpos($concerns, 'scalp') !== false || $hair_condition['scalp_health'] < 0.7) {
            $treatments[] = array(
                'type' => 'scalp_treatment',
                'title' => 'Scalp Detox and Massage',
                'frequency' => 'Weekly',
                'duration' => '20 minutes',
                'instructions' => 'Apply scalp oil, massage gently for 10 minutes, leave for 10 minutes, then shampoo.',
                'benefits' => array('Improves circulation', 'Removes buildup', 'Promotes healthy hair growth')
            );
        }
        
        // Moisture treatments
        if ($hair_condition['moisture_level'] < 0.6) {
            $treatments[] = array(
                'type' => 'deep_conditioning',
                'title' => 'Intensive Moisture Treatment',
                'frequency' => 'Bi-weekly',
                'duration' => '30 minutes',
                'instructions' => 'Apply deep conditioner from mid-length to ends, cover with plastic cap, use heat for 15 minutes.',
                'benefits' => array('Restores moisture', 'Improves elasticity', 'Reduces breakage')
            );
        }
        
        // Protein treatments
        if ($hair_condition['protein_balance'] < 0.5) {
            $treatments[] = array(
                'type' => 'protein_treatment',
                'title' => 'Strengthening Protein Mask',
                'frequency' => 'Monthly',
                'duration' => '15 minutes',
                'instructions' => 'Apply to clean, damp hair. Do not exceed recommended time to avoid protein overload.',
                'benefits' => array('Strengthens hair structure', 'Reduces breakage', 'Improves resilience')
            );
        }
        
        return $treatments;
    }
    
    private function get_styling_recommendations() {
        $styling = array();
        
        $hair_type = $this->hair_profile['hair_type'];
        $length = $this->hair_profile['length'];
        $weather = $this->environmental_factors;
        
        // Weather-appropriate styling
        if ($weather['humidity'] > 70) {
            $styling[] = array(
                'style' => 'Protective Updo',
                'reason' => 'High humidity can cause frizz. Updos protect hair and look polished.',
                'products' => array('Anti-humidity serum', 'Strong-hold gel', 'Hair oil for shine'),
                'difficulty' => 'Medium',
                'time' => '15 minutes'
            );
        }
        
        if ($weather['temperature'] > 80) {
            $styling[] = array(
                'style' => 'Braided Crown',
                'reason' => 'Keeps hair off neck and shoulders in hot weather while looking elegant.',
                'products' => array('Texturizing spray', 'Light hold hairspray'),
                'difficulty' => 'Medium',
                'time' => '20 minutes'
            );
        }
        
        // Hair type specific styling
        if (strpos($hair_type, 'curly') !== false) {
            $styling[] = array(
                'style' => 'Define Your Curls',
                'reason' => 'Enhance your natural curl pattern with the right technique.',
                'products' => array('Curl defining cream', 'Diffuser', 'Light oil for shine'),
                'difficulty' => 'Easy',
                'time' => '25 minutes',
                'technique' => 'Apply products to wet hair, scrunch gently, diffuse on low heat'
            );
        }
        
        return $styling;
    }
    
    private function get_general_recommendations() {
        $general = array();
        
        // Analyze overall hair health
        $hair_health = $this->calculate_hair_health_score();
        
        if ($hair_health < 0.7) {
            $general[] = array(
                'category' => 'Health Improvement',
                'priority' => 'high',
                'recommendations' => array(
                    'Increase water intake to at least 8 glasses daily',
                    'Take biotin or hair-specific vitamins',
                    'Reduce heat styling to 2-3 times per week maximum',
                    'Use a silk or satin pillowcase to reduce friction',
                    'Schedule a professional hair assessment'
                )
            );
        }
        
        // Environmental recommendations
        $season = $this->environmental_factors['season'];
        $general[] = array(
            'category' => 'Seasonal Care',
            'priority' => 'medium',
            'season' => $season,
            'recommendations' => $this->get_seasonal_tips($season)
        );
        
        // Goal-specific recommendations
        $goals = $this->hair_profile['goals'];
        if (!empty($goals)) {
            $general[] = array(
                'category' => 'Goal Achievement',
                'priority' => 'high',
                'recommendations' => $this->get_goal_specific_tips($goals)
            );
        }
        
        return $general;
    }
    
    private function analyze_hair_condition() {
        $condition = array(
            'moisture_level' => 0.5,
            'protein_balance' => 0.5,
            'scalp_health' => 0.5,
            'overall_health' => 0.5
        );
        
        // Analyze recent entries for condition indicators
        if (!empty($this->hair_data)) {
            $recent_entries = array_slice($this->hair_data, 0, 10);
            
            $moisture_indicators = 0;
            $protein_indicators = 0;
            $scalp_indicators = 0;
            
            foreach ($recent_entries as $entry) {
                $content = strtolower($entry->content);
                
                // Check for moisture indicators
                if (strpos($content, 'soft') !== false || strpos($content, 'hydrated') !== false) {
                    $moisture_indicators++;
                } elseif (strpos($content, 'dry') !== false || strpos($content, 'brittle') !== false) {
                    $moisture_indicators--;
                }
                
                // Check for protein indicators
                if (strpos($content, 'strong') !== false || strpos($content, 'elastic') !== false) {
                    $protein_indicators++;
                } elseif (strpos($content, 'mushy') !== false || strpos($content, 'limp') !== false) {
                    $protein_indicators--;
                }
                
                // Check for scalp indicators
                if (strpos($content, 'itchy') !== false || strpos($content, 'flaky') !== false) {
                    $scalp_indicators--;
                } elseif (strpos($content, 'healthy scalp') !== false) {
                    $scalp_indicators++;
                }
            }
            
            // Calculate condition scores
            $condition['moisture_level'] = max(0, min(1, 0.5 + ($moisture_indicators * 0.1)));
            $condition['protein_balance'] = max(0, min(1, 0.5 + ($protein_indicators * 0.1)));
            $condition['scalp_health'] = max(0, min(1, 0.5 + ($scalp_indicators * 0.1)));
        }
        
        $condition['overall_health'] = ($condition['moisture_level'] + $condition['protein_balance'] + $condition['scalp_health']) / 3;
        
        return $condition;
    }
    
    private function calculate_hair_health_score() {
        $condition = $this->analyze_hair_condition();
        return $condition['overall_health'];
    }
    
    private function calculate_optimal_wash_frequency() {
        $hair_type = $this->hair_profile['hair_type'];
        
        // Default frequencies based on hair type
        $frequencies = array(
            'straight' => '2-3 times per week',
            'wavy' => '1-2 times per week',
            'curly' => '1-2 times per week',
            'coily' => '1 time per week'
        );
        
        return $frequencies[$hair_type] ?? '2 times per week';
    }
    
    private function get_current_season() {
        $month = date('n');
        
        if ($month >= 12 || $month <= 2) return 'winter';
        if ($month >= 3 && $month <= 5) return 'spring';
        if ($month >= 6 && $month <= 8) return 'summer';
        return 'fall';
    }
    
    private function get_seasonal_tips($season) {
        $tips = array(
            'winter' => array(
                'Use a humidifier to combat dry indoor air',
                'Deep condition weekly to prevent dryness',
                'Avoid going outside with wet hair',
                'Wear protective styles under hats'
            ),
            'spring' => array(
                'Clarify hair to remove winter buildup',
                'Trim ends to prepare for growth season',
                'Introduce lighter styling products',
                'Start protective UV treatments'
            ),
            'summer' => array(
                'Use UV protection products',
                'Rinse hair after swimming',
                'Try protective styles to minimize sun damage',
                'Stay hydrated for healthy hair growth'
            ),
            'fall' => array(
                'Begin deeper conditioning treatments',
                'Prepare hair for drier weather',
                'Consider switching to heavier oils',
                'Schedule professional treatments'
            )
        );
        
        return $tips[$season] ?? array();
    }
    
    private function get_goal_specific_tips($goals) {
        $tips = array();
        
        if (strpos($goals, 'growth') !== false) {
            $tips = array_merge($tips, array(
                'Massage scalp daily to stimulate circulation',
                'Take biotin and maintain proper nutrition',
                'Avoid tight hairstyles that cause tension',
                'Trim regularly to prevent split ends from traveling up'
            ));
        }
        
        if (strpos($goals, 'repair') !== false) {
            $tips = array_merge($tips, array(
                'Minimize heat styling and chemical processing',
                'Use protein treatments monthly',
                'Sleep on silk or satin pillowcases',
                'Apply leave-in treatments for ongoing protection'
            ));
        }
        
        if (strpos($goals, 'volume') !== false) {
            $tips = array_merge($tips, array(
                'Use volumizing products at the roots',
                'Try the plopping method for natural volume',
                'Avoid heavy oils near the scalp',
                'Consider layers to add movement and body'
            ));
        }
        
        return $tips;
    }
    
    private function rank_recommendations($recommendations) {
        // Sort recommendations by confidence score
        usort($recommendations, function($a, $b) {
            return ($b['confidence'] ?? 0) <=> ($a['confidence'] ?? 0);
        });
        
        return $recommendations;
    }
    
    /**
     * Rate a recommendation to improve AI learning
     */
    public function rate_recommendation() {
        if (!wp_verify_nonce($_POST['nonce'], 'myavana_nonce')) {
            wp_die('Security check failed');
        }
        
        $recommendation_id = sanitize_text_field($_POST['recommendation_id']);
        $rating = intval($_POST['rating']);
        $feedback = sanitize_textarea_field($_POST['feedback'] ?? '');
        
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'myavana_ai_feedback';
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'user_id' => $this->user_id,
                'recommendation_id' => $recommendation_id,
                'rating' => $rating,
                'feedback' => $feedback,
                'date_created' => current_time('mysql')
            ),
            array('%d', '%s', '%d', '%s', '%s')
        );
        
        if ($result) {
            wp_send_json_success('Feedback recorded successfully');
        } else {
            wp_send_json_error('Failed to record feedback');
        }
    }
    
    /**
     * Get personalized hair care routine
     */
    public function get_personalized_routine() {
        if (!wp_verify_nonce($_POST['nonce'], 'myavana_nonce')) {
            wp_die('Security check failed');
        }
        
        $this->load_user_hair_data();
        $this->load_environmental_factors();
        
        $routine = $this->get_routine_recommendations();
        
        wp_send_json_success($routine);
    }
}

// Initialize the AI recommendations system
new Myavana_AI_Recommendations();