<?php
/**
 * MYAVANA Image Optimizer
 * WebP conversion, responsive srcsets, lazy loading, thumbnail generation
 *
 * @package Myavana_Hair_Journey
 * @version 1.0.0
 */

defined('ABSPATH') or die('No script kiddies please!');

class Myavana_Image_Optimizer {

    /**
     * Image sizes for responsive srcsets
     */
    private static $image_sizes = [
        'thumbnail' => 150,
        'small' => 300,
        'medium' => 600,
        'large' => 1200,
        'xlarge' => 1920
    ];

    /**
     * Supported image formats
     */
    private static $supported_formats = ['jpg', 'jpeg', 'png', 'gif'];

    /**
     * Initialize the optimizer
     */
    public static function init() {
        // Register custom image sizes
        add_action('after_setup_theme', [__CLASS__, 'register_image_sizes']);

        // Add WebP support
        add_filter('upload_mimes', [__CLASS__, 'add_webp_mime']);
        add_filter('wp_check_filetype_and_ext', [__CLASS__, 'check_webp_filetype'], 10, 4);

        // Auto-generate WebP on upload
        add_filter('wp_handle_upload', [__CLASS__, 'generate_webp_on_upload']);

        // Filter image output for lazy loading
        add_filter('the_content', [__CLASS__, 'add_lazy_loading']);
        add_filter('post_thumbnail_html', [__CLASS__, 'add_lazy_loading_to_thumbnail'], 10, 5);

        // Add responsive srcsets
        add_filter('wp_get_attachment_image_attributes', [__CLASS__, 'add_responsive_attributes'], 10, 3);

        // AJAX handlers for image processing
        add_action('wp_ajax_myavana_optimize_image', [__CLASS__, 'ajax_optimize_image']);
        add_action('wp_ajax_myavana_generate_thumbnails', [__CLASS__, 'ajax_generate_thumbnails']);
    }

    /**
     * Register custom image sizes
     */
    public static function register_image_sizes() {
        foreach (self::$image_sizes as $name => $size) {
            if (!in_array($name, ['thumbnail', 'medium', 'large'])) {
                add_image_size('myavana_' . $name, $size, $size, false);
            }
        }
    }

    /**
     * Add WebP MIME type support
     */
    public static function add_webp_mime($mimes) {
        $mimes['webp'] = 'image/webp';
        return $mimes;
    }

    /**
     * Check WebP file type
     */
    public static function check_webp_filetype($data, $file, $filename, $mimes) {
        $filetype = wp_check_filetype($filename, $mimes);

        return [
            'ext' => $filetype['ext'],
            'type' => $filetype['type'],
            'proper_filename' => $data['proper_filename']
        ];
    }

    /**
     * Generate WebP version on image upload
     */
    public static function generate_webp_on_upload($upload) {
        $file_path = $upload['file'];
        $file_type = $upload['type'];

        // Only process supported image types
        if (!in_array($file_type, ['image/jpeg', 'image/png'])) {
            return $upload;
        }

        // Generate WebP version
        self::create_webp_image($file_path);

        return $upload;
    }

    /**
     * Create WebP version of image
     *
     * @param string $file_path Path to original image
     * @return string|false Path to WebP image or false on failure
     */
    public static function create_webp_image($file_path) {
        // Check if GD library supports WebP
        if (!function_exists('imagewebp')) {
            return false;
        }

        $info = pathinfo($file_path);
        $webp_path = $info['dirname'] . '/' . $info['filename'] . '.webp';

        // Skip if WebP already exists
        if (file_exists($webp_path)) {
            return $webp_path;
        }

        // Get image type
        $image_type = exif_imagetype($file_path);

        // Create image resource
        switch ($image_type) {
            case IMAGETYPE_JPEG:
                $image = imagecreatefromjpeg($file_path);
                break;
            case IMAGETYPE_PNG:
                $image = imagecreatefrompng($file_path);
                // Preserve transparency
                imagepalettetotruecolor($image);
                imagealphablending($image, true);
                imagesavealpha($image, true);
                break;
            default:
                return false;
        }

        if (!$image) {
            return false;
        }

        // Convert to WebP (quality: 85)
        $result = imagewebp($image, $webp_path, 85);
        imagedestroy($image);

        return $result ? $webp_path : false;
    }

    /**
     * Generate responsive thumbnails for an image
     *
     * @param int $attachment_id Attachment ID
     * @return array Generated thumbnail paths
     */
    public static function generate_responsive_thumbnails($attachment_id) {
        $file_path = get_attached_file($attachment_id);
        if (!$file_path || !file_exists($file_path)) {
            return [];
        }

        $thumbnails = [];
        $info = pathinfo($file_path);
        $upload_dir = wp_upload_dir();

        foreach (self::$image_sizes as $name => $size) {
            $thumbnail_path = self::create_thumbnail($file_path, $size, $name);
            if ($thumbnail_path) {
                $thumbnails[$name] = $thumbnail_path;

                // Also create WebP version
                self::create_webp_image($thumbnail_path);
            }
        }

        // Store thumbnail metadata
        update_post_meta($attachment_id, '_myavana_thumbnails', $thumbnails);

        return $thumbnails;
    }

    /**
     * Create single thumbnail
     *
     * @param string $file_path Original file path
     * @param int $size Target size
     * @param string $suffix Size suffix for filename
     * @return string|false Thumbnail path or false on failure
     */
    private static function create_thumbnail($file_path, $size, $suffix) {
        $info = pathinfo($file_path);
        $thumbnail_path = $info['dirname'] . '/' . $info['filename'] . '-' . $suffix . '.' . $info['extension'];

        // Skip if thumbnail already exists
        if (file_exists($thumbnail_path)) {
            return $thumbnail_path;
        }

        $image_type = exif_imagetype($file_path);

        // Create image resource
        switch ($image_type) {
            case IMAGETYPE_JPEG:
                $source = imagecreatefromjpeg($file_path);
                break;
            case IMAGETYPE_PNG:
                $source = imagecreatefrompng($file_path);
                break;
            default:
                return false;
        }

        if (!$source) {
            return false;
        }

        // Get original dimensions
        list($width, $height) = getimagesize($file_path);

        // Calculate new dimensions maintaining aspect ratio
        if ($width > $height) {
            $new_width = $size;
            $new_height = ($height / $width) * $size;
        } else {
            $new_height = $size;
            $new_width = ($width / $height) * $size;
        }

        // Create new image
        $thumbnail = imagecreatetruecolor($new_width, $new_height);

        // Preserve transparency for PNG
        if ($image_type === IMAGETYPE_PNG) {
            imagealphablending($thumbnail, false);
            imagesavealpha($thumbnail, true);
            $transparent = imagecolorallocatealpha($thumbnail, 255, 255, 255, 127);
            imagefilledrectangle($thumbnail, 0, 0, $new_width, $new_height, $transparent);
        }

        // Resize
        imagecopyresampled($thumbnail, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

        // Save thumbnail
        $result = false;
        switch ($image_type) {
            case IMAGETYPE_JPEG:
                $result = imagejpeg($thumbnail, $thumbnail_path, 85);
                break;
            case IMAGETYPE_PNG:
                $result = imagepng($thumbnail, $thumbnail_path, 8);
                break;
        }

        imagedestroy($source);
        imagedestroy($thumbnail);

        return $result ? $thumbnail_path : false;
    }

    /**
     * Get responsive image HTML with srcset and lazy loading
     *
     * @param int $attachment_id Attachment ID
     * @param string $size Default size
     * @param array $attr Additional attributes
     * @return string HTML img tag
     */
    public static function get_responsive_image($attachment_id, $size = 'medium', $attr = []) {
        $src = wp_get_attachment_image_url($attachment_id, $size);
        $alt = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
        $title = get_the_title($attachment_id);

        // Build srcset
        $srcset = self::build_srcset($attachment_id);

        // Build sizes attribute
        $sizes = isset($attr['sizes']) ? $attr['sizes'] : '(max-width: 767px) 100vw, (max-width: 991px) 50vw, 33vw';

        // Default attributes
        $default_attr = [
            'class' => 'myavana-lazy-image',
            'data-src' => $src,
            'data-srcset' => $srcset,
            'data-sizes' => $sizes,
            'alt' => $alt,
            'title' => $title,
            'loading' => 'lazy'
        ];

        // Merge with custom attributes
        $attributes = array_merge($default_attr, $attr);

        // Build HTML
        $html = '<img';
        foreach ($attributes as $key => $value) {
            $html .= ' ' . esc_attr($key) . '="' . esc_attr($value) . '"';
        }
        $html .= ' />';

        // Add WebP fallback using picture element
        if (self::has_webp_version($attachment_id)) {
            $webp_src = self::get_webp_url($src);
            $webp_srcset = self::build_srcset($attachment_id, true);

            $html = '<picture>';
            $html .= '<source type="image/webp" data-srcset="' . esc_attr($webp_srcset) . '" data-sizes="' . esc_attr($sizes) . '" />';
            $html .= '<source type="image/' . self::get_image_extension($src) . '" data-srcset="' . esc_attr($srcset) . '" data-sizes="' . esc_attr($sizes) . '" />';
            $html .= '<img';
            foreach ($attributes as $key => $value) {
                $html .= ' ' . esc_attr($key) . '="' . esc_attr($value) . '"';
            }
            $html .= ' />';
            $html .= '</picture>';
        }

        return $html;
    }

    /**
     * Build srcset attribute
     *
     * @param int $attachment_id Attachment ID
     * @param bool $webp Whether to build WebP srcset
     * @return string Srcset attribute value
     */
    private static function build_srcset($attachment_id, $webp = false) {
        $srcset = [];

        foreach (self::$image_sizes as $name => $size) {
            $url = wp_get_attachment_image_url($attachment_id, 'myavana_' . $name);
            if ($url) {
                if ($webp) {
                    $url = self::get_webp_url($url);
                }
                $srcset[] = $url . ' ' . $size . 'w';
            }
        }

        return implode(', ', $srcset);
    }

    /**
     * Get WebP URL from original URL
     *
     * @param string $url Original image URL
     * @return string WebP URL
     */
    private static function get_webp_url($url) {
        $info = pathinfo($url);
        return $info['dirname'] . '/' . $info['filename'] . '.webp';
    }

    /**
     * Check if WebP version exists
     *
     * @param int $attachment_id Attachment ID
     * @return bool
     */
    private static function has_webp_version($attachment_id) {
        $file_path = get_attached_file($attachment_id);
        $info = pathinfo($file_path);
        $webp_path = $info['dirname'] . '/' . $info['filename'] . '.webp';

        return file_exists($webp_path);
    }

    /**
     * Get image extension from URL
     *
     * @param string $url Image URL
     * @return string Extension (jpg, png, etc.)
     */
    private static function get_image_extension($url) {
        $ext = strtolower(pathinfo($url, PATHINFO_EXTENSION));
        return $ext === 'jpeg' ? 'jpg' : $ext;
    }

    /**
     * Add lazy loading to content images
     *
     * @param string $content Post content
     * @return string Modified content
     */
    public static function add_lazy_loading($content) {
        if (is_feed() || is_admin()) {
            return $content;
        }

        // Match all img tags
        $content = preg_replace_callback('/<img([^>]+)>/i', function($matches) {
            $img = $matches[0];

            // Skip if already has lazy loading
            if (strpos($img, 'myavana-lazy-image') !== false) {
                return $img;
            }

            // Add lazy loading class
            if (strpos($img, 'class=') !== false) {
                $img = preg_replace('/class="([^"]*)"/', 'class="$1 myavana-lazy-image"', $img);
            } else {
                $img = str_replace('<img', '<img class="myavana-lazy-image"', $img);
            }

            // Convert src to data-src
            $img = preg_replace('/src="([^"]*)"/', 'data-src="$1" src=""', $img);

            // Convert srcset to data-srcset
            $img = preg_replace('/srcset="([^"]*)"/', 'data-srcset="$1"', $img);

            // Add loading="lazy" attribute
            $img = str_replace('<img', '<img loading="lazy"', $img);

            return $img;
        }, $content);

        return $content;
    }

    /**
     * Add lazy loading to post thumbnails
     *
     * @param string $html Post thumbnail HTML
     * @param int $post_id Post ID
     * @param int $post_thumbnail_id Thumbnail attachment ID
     * @param string|array $size Image size
     * @param string|array $attr Attributes
     * @return string Modified HTML
     */
    public static function add_lazy_loading_to_thumbnail($html, $post_id, $post_thumbnail_id, $size, $attr) {
        if (is_feed() || is_admin()) {
            return $html;
        }

        return self::add_lazy_loading($html);
    }

    /**
     * Add responsive attributes to images
     *
     * @param array $attr Image attributes
     * @param WP_Post $attachment Attachment post object
     * @param string|array $size Image size
     * @return array Modified attributes
     */
    public static function add_responsive_attributes($attr, $attachment, $size) {
        // Add sizes attribute if not set
        if (!isset($attr['sizes'])) {
            $attr['sizes'] = '(max-width: 767px) 100vw, (max-width: 991px) 50vw, 33vw';
        }

        return $attr;
    }

    /**
     * AJAX: Optimize image
     */
    public static function ajax_optimize_image() {
        check_ajax_referer('myavana_nonce', 'nonce');

        if (!current_user_can('upload_files')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
        }

        $attachment_id = intval($_POST['attachment_id']);

        if (!$attachment_id) {
            wp_send_json_error(['message' => 'Invalid attachment ID']);
        }

        $file_path = get_attached_file($attachment_id);

        // Generate WebP version
        $webp_path = self::create_webp_image($file_path);

        // Generate responsive thumbnails
        $thumbnails = self::generate_responsive_thumbnails($attachment_id);

        wp_send_json_success([
            'message' => 'Image optimized successfully',
            'webp' => $webp_path ? true : false,
            'thumbnails' => count($thumbnails)
        ]);
    }

    /**
     * AJAX: Generate thumbnails
     */
    public static function ajax_generate_thumbnails() {
        check_ajax_referer('myavana_nonce', 'nonce');

        if (!current_user_can('upload_files')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
        }

        $attachment_id = intval($_POST['attachment_id']);

        if (!$attachment_id) {
            wp_send_json_error(['message' => 'Invalid attachment ID']);
        }

        $thumbnails = self::generate_responsive_thumbnails($attachment_id);

        wp_send_json_success([
            'message' => 'Thumbnails generated successfully',
            'count' => count($thumbnails),
            'thumbnails' => $thumbnails
        ]);
    }

    /**
     * Bulk optimize all images (admin utility)
     *
     * @param array $args Query arguments
     * @return array Results
     */
    public static function bulk_optimize_images($args = []) {
        $defaults = [
            'post_type' => 'attachment',
            'post_mime_type' => 'image',
            'posts_per_page' => -1,
            'post_status' => 'inherit'
        ];

        $args = wp_parse_args($args, $defaults);
        $attachments = get_posts($args);

        $results = [
            'total' => count($attachments),
            'optimized' => 0,
            'failed' => 0
        ];

        foreach ($attachments as $attachment) {
            $file_path = get_attached_file($attachment->ID);

            // Generate WebP
            if (self::create_webp_image($file_path)) {
                $results['optimized']++;
            } else {
                $results['failed']++;
            }

            // Generate thumbnails
            self::generate_responsive_thumbnails($attachment->ID);
        }

        return $results;
    }
}

// Initialize the optimizer
Myavana_Image_Optimizer::init();
