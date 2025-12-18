<?php
/**
 * Enhanced Error Handling and Logging System
 * Myavana Hair Journey Plugin
 */

class Myavana_Error_Handler {
    
    private static $instance = null;
    private $log_enabled;
    private $log_level;
    
    public function __construct() {
        $this->log_enabled = defined('MYAVANA_DEBUG') && MYAVANA_DEBUG;
        $this->log_level = defined('MYAVANA_LOG_LEVEL') ? MYAVANA_LOG_LEVEL : 'ERROR';
        
        // Set up error handlers
        if ($this->log_enabled) {
            add_action('wp_ajax_myavana_log_error', [$this, 'log_frontend_error']);
            add_action('wp_ajax_nopriv_myavana_log_error', [$this, 'log_frontend_error']);
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Log error with context and severity
     */
    public function log_error($message, $context = [], $level = 'ERROR') {
        if (!$this->should_log($level)) {
            return;
        }
        
        $log_entry = [
            'timestamp' => current_time('mysql'),
            'level' => $level,
            'message' => sanitize_text_field($message),
            'context' => $this->sanitize_context($context),
            'user_id' => get_current_user_id(),
            'ip_address' => $this->get_client_ip(),
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 255),
            'trace' => $level === 'ERROR' ? debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5) : null
        ];
        
        $this->write_log($log_entry);
    }
    
    /**
     * Log API errors specifically
     */
    public function log_api_error($service, $error, $request_data = []) {
        $context = [
            'service' => $service,
            'error' => $error,
            'request' => $this->sanitize_request_data($request_data)
        ];
        
        $this->log_error("API Error in {$service}", $context, 'ERROR');
    }
    
    /**
     * Log security events
     */
    public function log_security_event($event, $details = []) {
        $context = [
            'event' => $event,
            'details' => $details,
            'session_id' => session_id(),
            'referer' => wp_get_referer()
        ];
        
        $this->log_error("Security Event: {$event}", $context, 'SECURITY');
    }
    
    /**
     * Handle frontend error logging
     */
    public function log_frontend_error() {
        check_ajax_referer('myavana_error_log', 'nonce');
        
        if (!isset($_POST['error']) || !isset($_POST['level'])) {
            wp_send_json_error('Missing required fields');
            return;
        }
        
        $error = sanitize_text_field($_POST['error']);
        $level = sanitize_text_field($_POST['level']);
        $context = isset($_POST['context']) ? json_decode(stripslashes($_POST['context']), true) : [];
        
        $this->log_error("Frontend Error: {$error}", $context, $level);
        wp_send_json_success('Error logged');
    }
    
    /**
     * Create user-friendly error response
     */
    public function create_user_error($message, $log_context = []) {
        $error_id = uniqid('err_');
        $user_message = "An error occurred (ID: {$error_id}). Please try again or contact support.";
        
        $this->log_error($message, array_merge($log_context, ['error_id' => $error_id]), 'ERROR');
        
        return [
            'error_id' => $error_id,
            'user_message' => $user_message,
            'technical_message' => $this->log_enabled ? $message : null
        ];
    }
    
    /**
     * Validate and sanitize input data
     */
    public function validate_input($data, $rules) {
        $errors = [];
        $sanitized = [];
        
        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;
            
            // Check if required
            if ($rule['required'] && empty($value)) {
                $errors[] = "Field '{$field}' is required";
                continue;
            }
            
            if (empty($value)) {
                $sanitized[$field] = $rule['default'] ?? null;
                continue;
            }
            
            // Apply sanitization
            switch ($rule['type']) {
                case 'email':
                    $sanitized[$field] = sanitize_email($value);
                    if (!is_email($sanitized[$field])) {
                        $errors[] = "Field '{$field}' must be a valid email";
                    }
                    break;
                    
                case 'int':
                    $sanitized[$field] = intval($value);
                    if (isset($rule['min']) && $sanitized[$field] < $rule['min']) {
                        $errors[] = "Field '{$field}' must be at least {$rule['min']}";
                    }
                    if (isset($rule['max']) && $sanitized[$field] > $rule['max']) {
                        $errors[] = "Field '{$field}' must not exceed {$rule['max']}";
                    }
                    break;
                    
                case 'string':
                    $sanitized[$field] = sanitize_text_field($value);
                    if (isset($rule['max_length']) && strlen($sanitized[$field]) > $rule['max_length']) {
                        $errors[] = "Field '{$field}' must not exceed {$rule['max_length']} characters";
                    }
                    break;
                    
                case 'textarea':
                    $sanitized[$field] = sanitize_textarea_field($value);
                    break;
                    
                case 'enum':
                    $sanitized[$field] = sanitize_text_field($value);
                    if (!in_array($sanitized[$field], $rule['values'], true)) {
                        $errors[] = "Field '{$field}' must be one of: " . implode(', ', $rule['values']);
                    }
                    break;
                    
                case 'uuid':
                    $sanitized[$field] = sanitize_text_field($value);
                    if (!preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i', $sanitized[$field])) {
                        $errors[] = "Field '{$field}' must be a valid UUID";
                    }
                    break;
                    
                default:
                    $sanitized[$field] = sanitize_text_field($value);
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'data' => $sanitized
        ];
    }
    
    private function should_log($level) {
        if (!$this->log_enabled) {
            return false;
        }
        
        $levels = ['DEBUG' => 0, 'INFO' => 1, 'WARNING' => 2, 'ERROR' => 3, 'SECURITY' => 4];
        return $levels[$level] >= $levels[$this->log_level];
    }
    
    private function sanitize_context($context) {
        if (!is_array($context)) {
            return [];
        }
        
        $sanitized = [];
        foreach ($context as $key => $value) {
            $key = sanitize_key($key);
            if (is_string($value)) {
                $sanitized[$key] = sanitize_text_field($value);
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitize_context($value);
            } else {
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }
    
    private function sanitize_request_data($data) {
        if (!is_array($data)) {
            return [];
        }
        
        // Remove sensitive data
        $sensitive_keys = ['password', 'api_key', 'token', 'secret'];
        foreach ($sensitive_keys as $key) {
            if (isset($data[$key])) {
                $data[$key] = '[REDACTED]';
            }
        }
        
        return $this->sanitize_context($data);
    }
    
    private function get_client_ip() {
        $ip_keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = sanitize_text_field($_SERVER[$key]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        return 'Unknown';
    }
    
    private function write_log($entry) {
        if (!$this->log_enabled) {
            return;
        }
        
        $log_message = sprintf(
            "[%s] %s: %s\n",
            $entry['timestamp'],
            $entry['level'],
            $entry['message']
        );
        
        if (!empty($entry['context'])) {
            $log_message .= "Context: " . wp_json_encode($entry['context']) . "\n";
        }
        
        if (!empty($entry['trace'])) {
            $log_message .= "Trace: " . wp_json_encode($entry['trace']) . "\n";
        }
        
        $log_message .= str_repeat('-', 50) . "\n";
        
        // Write to WordPress debug log if enabled
        if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            error_log($log_message);
        }
        
        // Also write to plugin-specific log file
        $log_file = WP_CONTENT_DIR . '/debug-myavana.log';
        file_put_contents($log_file, $log_message, FILE_APPEND | LOCK_EX);
    }
}

// Initialize error handler
Myavana_Error_Handler::getInstance();

// Convenience functions
if (!function_exists('myavana_log_error')) {
    function myavana_log_error($message, $context = [], $level = 'ERROR') {
        Myavana_Error_Handler::getInstance()->log_error($message, $context, $level);
    }
}

if (!function_exists('myavana_log_api_error')) {
    function myavana_log_api_error($service, $error, $request_data = []) {
        Myavana_Error_Handler::getInstance()->log_api_error($service, $error, $request_data);
    }
}

if (!function_exists('myavana_validate_input')) {
    function myavana_validate_input($data, $rules) {
        return Myavana_Error_Handler::getInstance()->validate_input($data, $rules);
    }
}