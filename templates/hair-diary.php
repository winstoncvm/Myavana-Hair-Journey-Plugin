<?php
/**
 * Hair Journey Diary WordPress Shortcode - Redirected to New Implementation
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Include the new implementation
require_once plugin_dir_path(__FILE__) . 'hair-diary-new.php';

// Register the shortcode - this will use the function from hair-diary-new.php
// The add_shortcode call is already in hair-diary-new.php, so we don't need to duplicate it here

// Keep the old AJAX handlers for backward compatibility if needed
// But they are now replaced by the new handlers in hair-diary-new.php
?>