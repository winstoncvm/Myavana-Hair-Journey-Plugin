<?php
/**
 * Asset Optimization and Caching System
 * Myavana Hair Journey Plugin
 */

class Myavana_Asset_Optimizer {
    
    private static $instance = null;
    private $cache_dir;
    private $cache_url;
    private $version;
    private $minify_enabled;
    private $combine_enabled;
    private $cdn_enabled;
    
    public function __construct() {
        $this->version = get_option('myavana_asset_version', '1.0.0');
        $this->minify_enabled = defined('MYAVANA_MINIFY_ASSETS') ? MYAVANA_MINIFY_ASSETS : false;
        $this->combine_enabled = defined('MYAVANA_COMBINE_ASSETS') ? MYAVANA_COMBINE_ASSETS : false;
        $this->cdn_enabled = defined('MYAVANA_CDN_ENABLED') ? MYAVANA_CDN_ENABLED : false;
        
        $upload_dir = wp_upload_dir();
        $this->cache_dir = $upload_dir['basedir'] . '/myavana-cache/';
        $this->cache_url = $upload_dir['baseurl'] . '/myavana-cache/';
        
        $this->init();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function init() {
        // Create cache directory
        if (!file_exists($this->cache_dir)) {
            wp_mkdir_p($this->cache_dir);
            
            // Add .htaccess for caching
            $htaccess_content = "
# Myavana Asset Cache
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css \"access plus 1 year\"
    ExpiresByType application/javascript \"access plus 1 year\"
    ExpiresByType image/png \"access plus 1 year\"
    ExpiresByType image/jpg \"access plus 1 year\"
    ExpiresByType image/jpeg \"access plus 1 year\"
    ExpiresByType image/gif \"access plus 1 year\"
    ExpiresByType image/webp \"access plus 1 year\"
</IfModule>

<IfModule mod_headers.c>
    <FilesMatch \"\\.(css|js|png|jpg|jpeg|gif|webp)$\">
        Header set Cache-Control \"public, max-age=31536000, immutable\"
    </FilesMatch>
</IfModule>
";
            file_put_contents($this->cache_dir . '.htaccess', $htaccess_content);
        }
        
        // Hook into WordPress asset system
        add_action('wp_enqueue_scripts', [$this, 'optimize_assets'], 999);
        add_action('wp_print_styles', [$this, 'combine_css'], 999);
        add_action('wp_print_scripts', [$this, 'combine_js'], 999);
        
        // Admin hooks
        add_action('admin_post_myavana_clear_cache', [$this, 'clear_cache']);
        add_action('wp_ajax_myavana_optimize_images', [$this, 'optimize_images']);
    }
    
    /**
     * Optimize enqueued assets
     */
    public function optimize_assets() {
        global $wp_styles, $wp_scripts;
        
        if (!$this->minify_enabled && !$this->combine_enabled) {
            return;
        }
        
        // Process CSS files
        if ($this->minify_enabled) {
            foreach ($wp_styles->registered as $handle => $style) {
                if (strpos($style->src, MYAVANA_URL) === 0) {
                    $optimized_url = $this->get_optimized_asset_url($style->src, 'css');
                    if ($optimized_url) {
                        $wp_styles->registered[$handle]->src = $optimized_url;
                    }
                }
            }
        }
        
        // Process JS files
        if ($this->minify_enabled) {
            foreach ($wp_scripts->registered as $handle => $script) {
                if (strpos($script->src, MYAVANA_URL) === 0) {
                    $optimized_url = $this->get_optimized_asset_url($script->src, 'js');
                    if ($optimized_url) {
                        $wp_scripts->registered[$handle]->src = $optimized_url;
                    }
                }
            }
        }
    }
    
    /**
     * Combine CSS files
     */
    public function combine_css() {
        if (!$this->combine_enabled) {
            return;
        }
        
        global $wp_styles;
        $myavana_styles = [];
        
        foreach ($wp_styles->queue as $handle) {
            if (isset($wp_styles->registered[$handle])) {
                $style = $wp_styles->registered[$handle];
                if (strpos($style->src, MYAVANA_URL) === 0) {
                    $myavana_styles[$handle] = $style;
                }
            }
        }
        
        if (count($myavana_styles) > 1) {
            $combined_url = $this->create_combined_css($myavana_styles);
            if ($combined_url) {
                // Remove individual styles
                foreach (array_keys($myavana_styles) as $handle) {
                    wp_dequeue_style($handle);
                }
                
                // Enqueue combined style
                wp_enqueue_style('myavana-combined', $combined_url, [], $this->version);
            }
        }
    }
    
    /**
     * Combine JS files
     */
    public function combine_js() {
        if (!$this->combine_enabled) {
            return;
        }
        
        global $wp_scripts;
        $myavana_scripts = [];
        
        foreach ($wp_scripts->queue as $handle) {
            if (isset($wp_scripts->registered[$handle])) {
                $script = $wp_scripts->registered[$handle];
                if (strpos($script->src, MYAVANA_URL) === 0 && !$script->extra) { // Skip scripts with localization
                    $myavana_scripts[$handle] = $script;
                }
            }
        }
        
        if (count($myavana_scripts) > 1) {
            $combined_url = $this->create_combined_js($myavana_scripts);
            if ($combined_url) {
                // Remove individual scripts
                foreach (array_keys($myavana_scripts) as $handle) {
                    wp_dequeue_script($handle);
                }
                
                // Enqueue combined script
                wp_enqueue_script('myavana-combined', $combined_url, ['jquery'], $this->version, true);
            }
        }
    }
    
    /**
     * Get optimized asset URL
     */
    private function get_optimized_asset_url($original_url, $type) {
        $file_path = str_replace(MYAVANA_URL, MYAVANA_DIR, $original_url);
        
        if (!file_exists($file_path)) {
            return null;
        }
        
        $file_hash = md5_file($file_path);
        $cache_filename = $file_hash . '.' . $type;
        $cache_file_path = $this->cache_dir . $cache_filename;
        
        // Check if optimized version exists and is current
        if (file_exists($cache_file_path) && filemtime($cache_file_path) >= filemtime($file_path)) {
            return $this->cache_url . $cache_filename;
        }
        
        // Create optimized version
        $original_content = file_get_contents($file_path);
        $optimized_content = $this->minify_asset($original_content, $type);
        
        if ($optimized_content && file_put_contents($cache_file_path, $optimized_content)) {
            return $this->cache_url . $cache_filename;
        }
        
        return null;
    }
    
    /**
     * Create combined CSS file
     */
    private function create_combined_css($styles) {
        $handles = array_keys($styles);
        $cache_key = 'combined_css_' . md5(implode('|', $handles));
        $cache_filename = $cache_key . '.css';
        $cache_file_path = $this->cache_dir . $cache_filename;
        
        // Check if combined file exists and is current
        $newest_time = 0;
        foreach ($styles as $style) {
            $file_path = str_replace(MYAVANA_URL, MYAVANA_DIR, $style->src);
            if (file_exists($file_path)) {
                $newest_time = max($newest_time, filemtime($file_path));
            }
        }
        
        if (file_exists($cache_file_path) && filemtime($cache_file_path) >= $newest_time) {
            return $this->cache_url . $cache_filename;
        }
        
        // Combine files
        $combined_content = "/* Myavana Combined CSS - Generated " . date('Y-m-d H:i:s') . " */\n";
        
        foreach ($styles as $handle => $style) {
            $file_path = str_replace(MYAVANA_URL, MYAVANA_DIR, $style->src);
            if (file_exists($file_path)) {
                $content = file_get_contents($file_path);
                $content = $this->process_css_urls($content, dirname($style->src));
                
                $combined_content .= "\n/* {$handle} */\n";
                $combined_content .= $content . "\n";
            }
        }
        
        // Minify combined content
        if ($this->minify_enabled) {
            $combined_content = $this->minify_asset($combined_content, 'css');
        }
        
        if (file_put_contents($cache_file_path, $combined_content)) {
            return $this->cache_url . $cache_filename;
        }
        
        return null;
    }
    
    /**
     * Create combined JS file
     */
    private function create_combined_js($scripts) {
        $handles = array_keys($scripts);
        $cache_key = 'combined_js_' . md5(implode('|', $handles));
        $cache_filename = $cache_key . '.js';
        $cache_file_path = $this->cache_dir . $cache_filename;
        
        // Check if combined file exists and is current
        $newest_time = 0;
        foreach ($scripts as $script) {
            $file_path = str_replace(MYAVANA_URL, MYAVANA_DIR, $script->src);
            if (file_exists($file_path)) {
                $newest_time = max($newest_time, filemtime($file_path));
            }
        }
        
        if (file_exists($cache_file_path) && filemtime($cache_file_path) >= $newest_time) {
            return $this->cache_url . $cache_filename;
        }
        
        // Combine files
        $combined_content = "/* Myavana Combined JS - Generated " . date('Y-m-d H:i:s') . " */\n";
        
        foreach ($scripts as $handle => $script) {
            $file_path = str_replace(MYAVANA_URL, MYAVANA_DIR, $script->src);
            if (file_exists($file_path)) {
                $content = file_get_contents($file_path);
                
                $combined_content .= "\n/* {$handle} */\n";
                $combined_content .= $content;
                $combined_content .= "\n;\n"; // Ensure proper separation
            }
        }
        
        // Minify combined content
        if ($this->minify_enabled) {
            $combined_content = $this->minify_asset($combined_content, 'js');
        }
        
        if (file_put_contents($cache_file_path, $combined_content)) {
            return $this->cache_url . $cache_filename;
        }
        
        return null;
    }
    
    /**
     * Minify asset content
     */
    private function minify_asset($content, $type) {
        switch ($type) {
            case 'css':
                return $this->minify_css($content);
            case 'js':
                return $this->minify_js($content);
            default:
                return $content;
        }
    }
    
    /**
     * Minify CSS
     */
    private function minify_css($css) {
        // Remove comments
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        
        // Remove unnecessary whitespace
        $css = str_replace(["\r\n", "\r", "\n", "\t"], '', $css);
        $css = preg_replace('/\s+/', ' ', $css);
        
        // Remove unnecessary spaces around operators
        $css = str_replace([' {', '{ ', ' }', '} ', ' :', ': ', ' ;', '; ', ' ,', ', '], ['{', '{', '}', '}', ':', ':', ';', ';', ',', ','], $css);
        
        // Remove trailing semicolon before }
        $css = str_replace(';}', '}', $css);
        
        // Remove unnecessary quotes
        $css = preg_replace('/url\(([\'"])([^\'"]+)\1\)/', 'url($2)', $css);
        
        return trim($css);
    }
    
    /**
     * Minify JavaScript (basic minification)
     */
    private function minify_js($js) {
        // Remove single-line comments (but preserve URLs and division)
        $js = preg_replace('/(?<!http:)(?<!https:)\/\/.*$/m', '', $js);
        
        // Remove multi-line comments
        $js = preg_replace('/\/\*[\s\S]*?\*\//', '', $js);
        
        // Remove unnecessary whitespace
        $js = preg_replace('/\s+/', ' ', $js);
        
        // Remove spaces around operators (careful with regex)
        $js = preg_replace('/\s*([{}();,=+\-*\/&|!<>?:])\s*/', '$1', $js);
        
        return trim($js);
    }
    
    /**
     * Process CSS URLs to make them relative to the combined file
     */
    private function process_css_urls($css, $original_dir) {
        return preg_replace_callback('/url\([\'"]?([^\'")]+)[\'"]?\)/', function($matches) use ($original_dir) {
            $url = $matches[1];
            
            // Skip absolute URLs and data URLs
            if (strpos($url, 'http') === 0 || strpos($url, 'data:') === 0 || strpos($url, '//') === 0) {
                return $matches[0];
            }
            
            // Convert relative URL to absolute
            if (strpos($url, '/') !== 0) {
                $url = $original_dir . '/' . $url;
            }
            
            return 'url(' . $url . ')';
        }, $css);
    }
    
    /**
     * Optimize images
     */
    public function optimize_images() {
        check_ajax_referer('myavana_optimize_images', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        $images_dir = MYAVANA_DIR . 'assets/images/';
        $optimized = 0;
        $errors = [];
        
        if (is_dir($images_dir)) {
            $files = glob($images_dir . '*.{jpg,jpeg,png,gif}', GLOB_BRACE);
            
            foreach ($files as $file) {
                $result = $this->optimize_single_image($file);
                if ($result === true) {
                    $optimized++;
                } elseif ($result !== null) {
                    $errors[] = basename($file) . ': ' . $result;
                }
            }
        }
        
        wp_send_json_success([
            'optimized' => $optimized,
            'errors' => $errors,
            'message' => sprintf('%d images optimized successfully', $optimized)
        ]);
    }
    
    /**
     * Optimize single image
     */
    private function optimize_single_image($file_path) {
        if (!file_exists($file_path)) {
            return 'File not found';
        }
        
        $info = getimagesize($file_path);
        if (!$info) {
            return 'Invalid image file';
        }
        
        $original_size = filesize($file_path);
        $max_size = 1024 * 1024; // 1MB
        
        // Skip if already small enough
        if ($original_size <= $max_size) {
            return null;
        }
        
        $mime_type = $info['mime'];
        $image = null;
        
        switch ($mime_type) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($file_path);
                break;
            case 'image/png':
                $image = imagecreatefrompng($file_path);
                break;
            case 'image/gif':
                $image = imagecreatefromgif($file_path);
                break;
            default:
                return 'Unsupported image type';
        }
        
        if (!$image) {
            return 'Failed to load image';
        }
        
        // Calculate new dimensions
        $width = $info[0];
        $height = $info[1];
        $max_dimension = 1920;
        
        if ($width > $max_dimension || $height > $max_dimension) {
            $ratio = min($max_dimension / $width, $max_dimension / $height);
            $new_width = round($width * $ratio);
            $new_height = round($height * $ratio);
            
            $resized_image = imagecreatetruecolor($new_width, $new_height);
            
            // Preserve transparency for PNG and GIF
            if ($mime_type === 'image/png' || $mime_type === 'image/gif') {
                imagealphablending($resized_image, false);
                imagesavealpha($resized_image, true);
                $transparent = imagecolorallocatealpha($resized_image, 255, 255, 255, 127);
                imagefill($resized_image, 0, 0, $transparent);
            }
            
            imagecopyresampled($resized_image, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
            imagedestroy($image);
            $image = $resized_image;
        }
        
        // Save optimized image
        $backup_path = $file_path . '.original';
        if (!file_exists($backup_path)) {
            copy($file_path, $backup_path);
        }
        
        $success = false;
        switch ($mime_type) {
            case 'image/jpeg':
                $success = imagejpeg($image, $file_path, 85);
                break;
            case 'image/png':
                $success = imagepng($image, $file_path, 6);
                break;
            case 'image/gif':
                $success = imagegif($image, $file_path);
                break;
        }
        
        imagedestroy($image);
        
        if ($success) {
            $new_size = filesize($file_path);
            if ($new_size < $original_size) {
                return true;
            } else {
                // Restore original if optimization didn't help
                copy($backup_path, $file_path);
                return null;
            }
        }
        
        return 'Failed to save optimized image';
    }
    
    /**
     * Clear cache
     */
    public function clear_cache() {
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        check_admin_referer('myavana_clear_cache');
        
        $cleared = 0;
        if (is_dir($this->cache_dir)) {
            $files = glob($this->cache_dir . '*');
            foreach ($files as $file) {
                if (is_file($file) && basename($file) !== '.htaccess') {
                    if (unlink($file)) {
                        $cleared++;
                    }
                }
            }
        }
        
        // Update asset version to force reload
        $this->version = time();
        update_option('myavana_asset_version', $this->version);
        
        wp_redirect(add_query_arg([
            'page' => 'myavana-settings',
            'cache_cleared' => $cleared
        ], admin_url('admin.php')));
        exit;
    }
    
    /**
     * Get cache statistics
     */
    public function get_cache_stats() {
        $stats = [
            'files' => 0,
            'size' => 0,
            'created' => null
        ];
        
        if (is_dir($this->cache_dir)) {
            $files = glob($this->cache_dir . '*');
            foreach ($files as $file) {
                if (is_file($file) && basename($file) !== '.htaccess') {
                    $stats['files']++;
                    $stats['size'] += filesize($file);
                    
                    if ($stats['created'] === null || filemtime($file) < $stats['created']) {
                        $stats['created'] = filemtime($file);
                    }
                }
            }
        }
        
        return $stats;
    }
    
    /**
     * Preload critical assets
     */
    public function preload_critical_assets() {
        $critical_assets = [
            'myavana-styles.css',
            'myavana-scripts.js'
        ];
        
        foreach ($critical_assets as $asset) {
            $asset_url = MYAVANA_URL . 'assets/' . (strpos($asset, '.css') ? 'css' : 'js') . '/' . $asset;
            
            if (strpos($asset, '.css')) {
                echo '<link rel="preload" href="' . esc_url($asset_url) . '" as="style">' . "\n";
            } else {
                echo '<link rel="preload" href="' . esc_url($asset_url) . '" as="script">' . "\n";
            }
        }
    }
}

// Initialize asset optimizer
if (!is_admin() || wp_doing_ajax()) {
    Myavana_Asset_Optimizer::getInstance();
}

// Add preload to head
add_action('wp_head', function() {
    if (!is_admin()) {
        Myavana_Asset_Optimizer::getInstance()->preload_critical_assets();
    }
}, 1);