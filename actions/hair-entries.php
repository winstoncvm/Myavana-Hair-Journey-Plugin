<?php

// Handle FilePond uploads
add_action('wp_ajax_handle_filepond_upload', 'handle_filepond_upload');
add_action('wp_ajax_nopriv_handle_filepond_upload', 'handle_filepond_upload');
function handle_filepond_upload() {
    // Allow CORS
    header("Access-Control-Allow-Origin: " . get_http_origin());
    header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Allow-Headers: Content-Type, X-WP-Nonce");
    
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        status_header(200);
        exit();
    }

    if (!wp_verify_nonce($_SERVER['HTTP_X_WP_NONCE'] ?? '', 'filepond_upload')) {
        wp_send_json_error('Invalid nonce', 403);
    }

    if (empty($_FILES)) {
        wp_send_json_error('No files uploaded', 400);
    }

    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');

    $uploaded_file = $_FILES['filepond'];
    $upload_overrides = ['test_form' => false];
    $movefile = wp_handle_upload($uploaded_file, $upload_overrides);

    if ($movefile && !isset($movefile['error'])) {
        wp_send_json_success([
            'url' => $movefile['url'],
            'path' => $movefile['file']
        ]);
    } else {
        wp_send_json_error($movefile['error'], 500);
    }
}

// Add CORS headers to AJAX requests
add_action('init', 'add_cors_headers');
function add_cors_headers() {
    if (isset($_SERVER['HTTP_ORIGIN'])) {
        header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400');
    }

    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
            header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
        
        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
            header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
        
        exit(0);
    }
}

add_filter('wp_nav_menu_items', 'myavana_add_hair_profile_menu_item', 10, 2);
function myavana_add_hair_profile_menu_item($items, $args) {
    if ($args->theme_location !== 'primary') {
        return $items;
    }

    // Define Myavana color variables
    $colors = [
        'onyx' => '#222323',
        'coral' => '#e7a690',
        'light-coral' => '#fce5d7',
        'white' => '#ffffff',
        'blueberry' => '#4a4d68'
    ];

    // User avatar and profile dropdown
    $auth_links = '';
    $logout_url = wp_logout_url(home_url());

    // Hair profile menu items (top-level for logged-in users)
    $profile_items = '';
    if (is_user_logged_in()) {
        $user_id = get_current_user_id();
        $username = bp_core_get_username($user_id);
        $base_url = esc_url(home_url('/members/' . $username . '/'));
        $profile_url = $base_url . 'hair-journey/';
        $add_entry_url = $base_url . 'add_entry/';
        $timeline_url = $base_url . 'hair_journey_timeline/';
        $insights_url = $base_url . 'hair_insights/';
        $chatbot_url = $base_url . 'virtual_chat/';
        $diary_url = 'http://myavana-hair-journey.local/myavana-diary/';

        $profile_items = <<<HTML
            <li class="menu-item myavana-profile-menu">
                <a href="/hair-journey" class="myavana-profile-link">
                    <i class="fas fa-id-card"></i> My Hair Journey
                </a>
            </li>
            <!-- <li class="menu-item myavana-profile-menu">
                <a href="{$diary_url}" class="myavana-profile-link">
                    <i class="fas fa-book"></i> Hair Diary
                </a>
            </li> -->
            
            <!-- <li class="menu-item myavana-profile-menu">
                <a href="{$timeline_url}" class="myavana-profile-link">
                    <i class="fas fa-history"></i> Hair Journey Timeline
                </a>
                 <ul class="myavana-profile-dropdown">
                    <li class="menu-item myavana-profile-menu">
                        <a href="{$add_entry_url}" class="myavana-profile-link myavana-dropdown-link">
                            <i class="fas fa-plus-circle"></i> Add New Entry
                        </a>
                    </li>
                </ul>
            </li> -->
            <li class="menu-item myavana-profile-menu">
                <a href="{$add_entry_url}" class="myavana-profile-link myavana-dropdown-link">
                    <i class="fas fa-plus-circle"></i> Add New Entry
                </a>
            </li>
            <li class="menu-item myavana-profile-menu">
                <a href="{$insights_url}" class="myavana-profile-link">
                    <i class="fas fa-chart-line"></i> Hair Insights
                </a>
            </li>
            <!-- <li class="menu-item myavana-profile-menu">
                <a href="{$chatbot_url}" class="myavana-profile-link">
                    <i class="fas fa-robot"></i> Virtual Chatbot
                </a>
            </li> -->
            
            <li class="menu-item myavana-profile-menu">
                <a href="{$logout_url}" class="myavana-profile-link">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </li>
        HTML;
    }
    else {
        $login_url = esc_url(home_url('/login-2/'));
        $register_url = esc_url(home_url('/register/'));
        $auth_links = <<<HTML
            <li class="menu-item myavana-profile-menu">
                <button class="myavana-profile-link myavana-signin-b lbutton">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </li>
            <li class="menu-item myavana-profile-menu">
                <button class="myavana-profile-link myavana-signup-b lbutton">
                    <i class="fas fa-user-plus"></i> Register
                </button>
            </li>
             <script>
        (function($) {
            'use strict';
            // Switch between forms
            function switchForm(formType) {
                $('.myavana-auth-toggle button').removeClass('active');
                $('.myavana-auth-form').removeClass('active');
                
                if (formType === 'signin') {
                    $('#myavanaSigninTab').addClass('active');
                    $('#myavanaSigninForm').addClass('active');
                } else {
                    $('#myavanaSignupTab').addClass('active');
                    $('#myavanaSignupForm').addClass('active');
                }
            }
            
          
            // Initialize modal on page load
            $(document).ready(function() {
                
                $('.myavana-signin-b').on('click', function(e) {
                    e.preventDefault();
                    $('#myavanaAuthModal').addClass('show');
                    switchForm('signin');
                });
                
                $('.myavana-signup-b').on('click', function(e) {
                    e.preventDefault();
                    $('#myavanaAuthModal').addClass('show');
                    switchForm('signup');
                });
                
          
            });
         
            
        })(jQuery);
        </script>
        HTML;
    }

    $html = <<<HTML
        <style>
            /* Base Styles */
            :root {
                --onyx: {$colors['onyx']};
                --coral: {$colors['coral']};
                --light-coral: {$colors['light-coral']};
                --white: {$colors['white']};
                --blueberry: {$colors['blueberry']};
                --transition: all 0.3s ease-in-out;
            }

            .myavana-nav-menu {
                background: var(--onyx);
                padding: 10px 20px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
                display: flex;
                align-items: center;
                justify-content: flex-end;
                flex-wrap: nowrap;
                font-family: 'Archivo', sans-serif;
            }

            .myavana-profile-menu {
                position: relative;
                margin: 0 6px;
                transition: var(--transition);
            }

            .myavana-profile-link {
                display: flex;
                align-items: center;
                font-weight: 600;
                color: var(--white) !important;
                text-decoration: none;
                padding: 10px 12px;
                text-transform: uppercase;
                font-size: 0.85em;
                border-radius: 6px;
                transition: var(--transition), border-bottom 0.3s ease;
                position: relative;
                white-space: nowrap;
                cursor: pointer;
            }
            .myavana-profile-link.lbutton {
                background: transparent;
                border: none;
                outline: none;
            }

            .myavana-profile-link:hover {
                border-bottom: 2px solid var(--coral);
                transform: translateY(-2px);
            }

            .myavana-profile-link i {
                margin-right: 6px;
                font-size: 0.9em;
            }

            .myavana-login-link {
                background: var(--light-coral);
                color: var(--onyx) !important;
            }

            .myavana-login-link:hover {
                background: var(--coral) !important;
                color: var(--white) !important;
                border-bottom: 2px solid var(--coral);
            }

            .myavana-register-link {
                background: var(--coral);
                color: var(--white) !important;
            }

            .myavana-register-link:hover {
                background: var(--blueberry) !important;
                border-bottom: 2px solid var(--coral);
            }

            .myavana-user-avatar {
                border-radius: 50%;
                margin-right: 6px;
                border: 2px solid var(--light-coral);
                transition: var(--transition);
            }

            .myavana-profile-link:hover .myavana-user-avatar {
                border-color: var(--coral);
            }

            .myavana-username {
                font-size: 0.85em;
            }

            .myavana-menu-arrow {
                margin-left: 6px;
                font-size: 0.7em;
                transition: transform 0.3s ease;
            }

            .myavana-profile-menu:hover .myavana-menu-arrow {
                transform: rotate(180deg);
            }
            .nav li ul, .menu li ul {
                width: 100% !important;
            }

            /* Dropdown Styles */
            .myavana-profile-dropdown {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                background: var(--white);
                border-radius: 6px;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
                padding: 10px 0;
                z-index: 1000;
                opacity: 0;
                transform: translateY(10px);
                transition: all 0.3s ease;
                animation: fadeIn 0.3s forwards;
                text-align: left;
            }

            .myavana-profile-menu:hover .myavana-profile-dropdown {
                display: block;
                opacity: 1;
                transform: translateY(0);
            }

            .myavana-dropdown-link {
                display: flex;
                align-items: center;
                padding: 10px 20px;
                color: var(--white) !important;
                text-decoration: none;
                text-transform: uppercase;
                font-size: 0.8em;
                transition: all 0.2s ease;
            }

            .myavana-dropdown-link:hover {
                background: var(--light-coral);
                color: var(--coral) !important;
                border-bottom: 2px solid var(--coral);
            }

            .myavana-dropdown-link i {
                width: 20px;
                text-align: center;
                margin-right: 8px;
                color: var(--coral);
            }

            /* Active Menu Item */
            .myavana-profile-menu.current-menu-item .myavana-profile-link,
            .myavana-profile-menu.current-menu-parent .myavana-profile-link,
            .myavana-profile-menu.current-menu-item .myavana-dropdown-link,
            .myavana-profile-menu.current-menu-parent .myavana-dropdown-link {
                border-bottom: 2px solid var(--light-coral);
                color: var(--white) !important;
                background: var(--coral);
            }

            .myavana-profile-menu.current-menu-item .myavana-profile-link:hover,
            .myavana-profile-menu.current-menu-parent .myavana-profile-link:hover,
            .myavana-profile-menu.current-menu-item .myavana-dropdown-link:hover,
            .myavana-profile-menu.current-menu-parent .myavana-dropdown-link:hover {
                border-bottom: 2px solid var(--light-coral);
            }

            /* Mobile Styles */
            @media (max-width: 768px) {
                .myavana-nav-menu {
                    flex-direction: column;
                    align-items: flex-start;
                    padding: 15px;
                }

                .myavana-profile-menu {
                    width: 100%;
                    margin: 5px 0;
                }

                .myavana-profile-link {
                    padding: 12px 10px;
                    font-size: 0.8em;
                    justify-content: flex-start;
                }

                .myavana-user-avatar {
                    width: 28px;
                    height: 28px;
                }

                .myavana-profile-dropdown {
                    display: none;
                    position: static;
                    box-shadow: none;
                    background: #f8f9fa;
                    animation: none;
                    opacity: 1;
                    width: 100%;
                    text-align: left;
                }

                .myavana-profile-menu.active .myavana-profile-dropdown {
                    display: block;
                }

                .myavana-dropdown-link {
                    padding: 10px 20px;
                    font-size: 0.75em;
                    border-left: 2px solid transparent;
                }

                .myavana-dropdown-link:hover {
                    border-left: 2px solid var(--coral);
                    border-bottom: none;
                }

                .myavana-profile-menu.current-menu-item .myavana-dropdown-link,
                .myavana-profile-menu.current-menu-parent .myavana-dropdown-link {
                    border-left: 2px solid var(--light-coral);
                    border-bottom: none;
                    color: var(--coral) !important;
                }
            }

            @media (max-width: 600px) {
                .myavana-profile-link {
                    font-size: 0.75em;
                    padding: 10px 8px;
                }

                .myavana-dropdown-link {
                    font-size: 0.7em;
                }
            }

            /* Animations */
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(10px); }
                to { opacity: 1; transform: translateY(0); }
            }
        </style>

        {$profile_items}
        {$auth_links}
    HTML;

    $items .= $html;

    return $items;
}

// Enqueue menu-specific styles and dependencies
add_action('wp_enqueue_scripts', 'myavana_menu_styles');
function myavana_menu_styles() {
    // Ensure Font Awesome is enqueued for icons
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css', [], '5.15.4');

    // Inline styles for current menu item
    wp_add_inline_style('myavana-styles', '
        .myavana-profile-menu.current-menu-item .myavana-profile-link,
        .myavana-profile-menu.current-menu-parent .myavana-profile-link,
        .myavana-profile-menu.current-menu-item .myavana-dropdown-link,
        .myavana-profile-menu.current-menu-parent .myavana-dropdown-link {
            border-bottom: 2px solid var(--light-coral);
            color: var(--white) !important;
            background: var(--coral);
        }
        @media (max-width: 768px) {
            .myavana-profile-menu.current-menu-item .myavana-dropdown-link,
            .myavana-profile-menu.current-menu-parent .myavana-dropdown-link {
                border-left: 2px solid var(--light-coral);
                border-bottom: none;
            }
        }
    ');
}

// Add JavaScript to toggle dropdown on mobile
add_action('wp_footer', 'myavana_menu_scripts');
function myavana_menu_scripts() {
    ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const menuItems = document.querySelectorAll('.myavana-profile-menu.menu-item-has-children');
            menuItems.forEach(item => {
                item.addEventListener('click', function(e) {
                    if (window.innerWidth <= 768) {
                        e.preventDefault();
                        this.classList.toggle('active');
                    }
                });
            });
        });
    </script>
    <?php
}

function myavana_redirect_after_login($redirect_to, $request, $user) {
    if (isset($user->ID) && user_can($user, 'read')) {
        $username = bp_core_get_username($user->ID);
        return home_url('/members/' . $username . '/hair_profile/');
    }
    return $redirect_to;
}
add_filter('login_redirect', 'myavana_redirect_after_login', 10, 3);
add_filter('wp_mail_from', function ($email) {
    return 'dialogflow263@gmail.com'; // Set your desired From email
});

add_filter('wp_mail_from_name', function ($name) {
    return 'Myavana Hair Journey'; // Set your desired From name
});

add_action('wp_ajax_myavana_save_tryon', 'myavana_save_tryon_callback');
function myavana_save_tryon_callback() {
    check_ajax_referer('myavana_tryon_nonce', 'nonce');
    $user_id = intval($_POST['user_id']);
    if (!current_user_can('edit_user', $user_id)) {
        wp_send_json_error('Permission denied.');
    }

    $image_data = sanitize_text_field($_POST['image_data']);
    $color = sanitize_text_field($_POST['color']);
    $style = sanitize_text_field($_POST['style']);
    $title = sanitize_text_field($_POST['title'] ?? '');
    $description = sanitize_text_field($_POST['description'] ?? '');
    $stylist_notes = sanitize_text_field($_POST['stylist_notes'] ?? '');
    $products_used = sanitize_text_field($_POST['products_used'] ?? '');

    if (empty($image_data) || empty($color) || empty($style)) {
        wp_send_json_error('Missing required data.');
    }

    // Save to user meta
    $journey_data = get_user_meta($user_id, 'myavana_hair_journey', true) ?: [];
    $journey_data[] = [
        'image' => $image_data,
        'color' => $color,
        'style' => $style,
        'title' => $title ? "Hairstyle Suggestion by Myavana AI: $title" : 'Hairstyle Suggestion by Myavana AI',
        'description' => $description ? "Hairstyle Suggestion by Myavana AI: $description" : '',
        'stylist_notes' => $stylist_notes ? "Hairstyle Suggestion by Myavana AI: $stylist_notes" : '',
        'products_used' => $products_used ? "Hairstyle Suggestion by Myavana AI: $products_used" : '',
        'timestamp' => current_time('mysql')
    ];

    if (update_user_meta($user_id, 'myavana_hair_journey', $journey_data)) {
        wp_send_json_success('Look saved to your hair journey!');
    } else {
        error_log('Myavana AI: Failed to save journey data for user ' . $user_id . ': ' . print_r($journey_data, true));
        wp_send_json_error('Failed to save look. Please try again.');
    }
}
add_action('wp_ajax_myavana_generate_hairstyle', 'myavana_generate_hairstyle_callback');
function myavana_generate_hairstyle_callback() {
    check_ajax_referer('myavana_tryon_nonce', 'nonce');
    $user_id = intval($_POST['user_id']);
    if (!current_user_can('edit_user', $user_id)) {
        wp_send_json_error('Permission denied.');
    }

    $image_data = sanitize_text_field($_POST['image_data']);
    $style = sanitize_text_field($_POST['style']);
    $color = sanitize_text_field($_POST['color']);

    $typeform_data = get_user_meta($user_id, 'myavana_typeform_data', true) ?: [];
    $user_data = [
        'hair_journey' => $typeform_data['hair_journey'] ?? '',
        'hair_health' => $typeform_data['hair_health'] ?? '',
        'additional_info' => $typeform_data['additional_info'] ?? ''
    ];

    $ai = new Myavana_AI();
    $result = $ai->generate_hairstyle_preview($image_data, $style, $color, $user_data);

    if (isset($result['image']) && !$result['fallback']) {
        wp_send_json_success([
            'image' => $result['image'],
            'fallback' => false,
            'metadata' => $result['metadata']
        ]);
    } else {
        wp_send_json_success([
            'image' => $image_data,
            'fallback' => true,
            'style' => $style,
            'color' => $color,
            'error' => $result['error'] ?? 'Unknown error'
        ]);
    }
}

// Register hair_routine custom post type
function myavana_register_hair_routine_post_type() {
    $args = [
        'public' => true,
        'label' => 'Hair Routines',
        'supports' => ['title', 'editor', 'custom-fields'],
        'show_in_rest' => true,
        'has_archive' => false,
        'exclude_from_search' => true,
    ];
    register_post_type('hair_routine', $args);
}
add_action('init', 'myavana_register_hair_routine_post_type');

function myavana_save_screenshot_callback($base64_data, $post_id, $user_id) {
    $upload_dir = wp_upload_dir();
    $file_name = 'hair_journey_' . $post_id . '_' . time() . '.jpg';
    $file_path = $upload_dir['path'] . '/' . $file_name;

    // Decode base64
    $image_data = base64_decode($base64_data);
    if ($image_data === false) {
        return false;
    }

    // Save file
    file_put_contents($file_path, $image_data);

    // Create attachment
    $attachment = [
        'post_mime_type' => 'image/jpeg',
        'post_title' => sanitize_file_name($file_name),
        'post_content' => '',
        'post_status' => 'inherit',
        'post_author' => $user_id
    ];
    $attachment_id = wp_insert_attachment($attachment, $file_path, $post_id);
    if (!is_wp_error($attachment_id)) {
        require_once ABSPATH . 'wp-admin/includes/image.php';
        $attachment_data = wp_generate_attachment_metadata($attachment_id, $file_path);
        wp_update_attachment_metadata($attachment_id, $attachment_data);
        return $attachment_id;
    }
    return false;
}
add_action('wp_ajax_myavana_save_screenshot', 'myavana_save_screenshot_callback');

/**
 * Get all hair journey entries for the current user
 */
function myavana_get_diary_entries() {
    
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : get_current_user_id();
    if (!$user_id) {
        wp_send_json_error('User not logged in');
        return;
    }
    
    $entries = new WP_Query([
        'post_type' => 'hair_journey_entry',
        'author' => $user_id,
        'posts_per_page' => -1,
        'orderby' => 'date',
        'order' => 'DESC'
    ]);
    
    $data = [];
    if ($entries->have_posts()) {
        while ($entries->have_posts()) {
            $entries->the_post();
            $post_id = get_the_ID();
            $ai_tags = get_post_meta($post_id, 'ai_tags', true);
            $data[] = [
                'id' => $post_id,
                'title' => get_the_title(),
                'date' => get_the_date('Y-m-d'),
                'image' => get_the_post_thumbnail_url($post_id, 'large') ?: '',
                'description' => get_post_meta($post_id, 'description', true) ?: '',
                'products' => get_post_meta($post_id, 'products', true) ?: '',
                'notes' => get_post_meta($post_id, 'stylist_notes', true) ?: '',
                'rating' => get_post_meta($post_id, 'health_rating', true) ?: '',
                'mood_demeanor' => get_post_meta($post_id, 'mood_demeanor', true) ?: '',
                'environment' => get_post_meta($post_id, 'environment', true) ?: '',
                'ai_tags' => $ai_tags ? (is_array($ai_tags) ? $ai_tags : json_decode($ai_tags, true)) : []
            ];
        }
        wp_reset_postdata();
    }
    
    wp_send_json_success(['entries' => $data]);
}
add_action('wp_ajax_myavana_get_diary_entries', 'myavana_get_diary_entries');

/**
 * Get a single hair journey entry
 */
function myavana_get_single_diary_entry() {
    check_ajax_referer('myavana_diary', 'security');
    
    $entry_id = isset($_GET['entry_id']) ? intval($_GET['entry_id']) : 0;
    if (!$entry_id) {
        wp_send_json_error('Invalid entry ID');
        return;
    }
    
    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error('User not logged in');
        return;
    }
    
    $entry = get_post($entry_id);
    if (!$entry || $entry->post_type !== 'hair_journey_entry' || $entry->post_author != $user_id) {
        wp_send_json_error('Entry not found or unauthorized');
        return;
    }
    
    $ai_tags = get_post_meta($entry_id, 'ai_tags', true);
    $data = [
        'id' => $entry_id,
        'title' => get_the_title($entry_id),
        'date' => get_the_date('Y-m-d', $entry_id),
        'image' => get_the_post_thumbnail_url($entry_id, 'large') ?: '',
        'description' => get_post_meta($entry_id, 'description', true) ?: '',
        'products' => get_post_meta($entry_id, 'products', true) ?: '',
        'notes' => get_post_meta($entry_id, 'stylist_notes', true) ?: '',
        'rating' => get_post_meta($entry_id, 'health_rating', true) ?: '',
        'mood_demeanor' => get_post_meta($entry_id, 'mood_demeanor', true) ?: '',
        'environment' => get_post_meta($entry_id, 'environment', true) ?: '',
        'ai_tags' => $ai_tags ? (is_array($ai_tags) ? $ai_tags : json_decode($ai_tags, true)) : []
    ];
    
    wp_send_json_success($data);
}
add_action('wp_ajax_myavana_get_single_diary_entry', 'myavana_get_single_diary_entry');

/**
 * Add a new hair journey entry
 */
function myavana_add_diary_entry() {
    check_ajax_referer('myavana_diary', 'myavana_nonce');
    
    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error('User not logged in');
        return;
    }
    
    $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
    $description = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '';
    $products = isset($_POST['products']) ? sanitize_text_field($_POST['products']) : '';
    $notes = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';
    $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 3;
    $mood_demeanor = isset($_POST['mood_demeanor']) ? sanitize_text_field($_POST['mood_demeanor']) : '';
    $environment = isset($_POST['environment']) ? sanitize_text_field($_POST['environment']) : '';
    
    $post_id = wp_insert_post([
        'post_title' => $title,
        'post_content' => $description,
        'post_type' => 'hair_journey_entry',
        'post_status' => 'publish',
        'post_author' => $user_id
    ]);
    
    if (is_wp_error($post_id)) {
        wp_send_json_error('Error creating entry');
        return;
    }
    
    update_post_meta($post_id, 'description', $description);
    update_post_meta($post_id, 'products', $products);
    update_post_meta($post_id, 'stylist_notes', $notes);
    update_post_meta($post_id, 'health_rating', $rating);
    update_post_meta($post_id, 'mood_demeanor', $mood_demeanor);
    update_post_meta($post_id, 'environment', $environment);
    update_post_meta($post_id, 'ai_tags', []); // Initialize empty ai_tags
    
    if (!empty($_FILES['photo']['name'])) {
        $attachment_id = media_handle_upload('photo', $post_id);
        if (!is_wp_error($attachment_id)) {
            set_post_thumbnail($post_id, $attachment_id);
        } else {
            error_log('Media upload error: ' . $attachment_id->get_error_message());
        }
    }
    
    wp_send_json_success(['message' => 'Entry added successfully']);
}
add_action('wp_ajax_myavana_add_diary_entry', 'myavana_add_diary_entry');

/**
 * Edit an existing hair journey entry
 */
function myavana_edit_diary_entry() {
    check_ajax_referer('myavana_diary', 'myavana_nonce');
    
    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error('User not logged in');
        return;
    }
    
    $entry_id = isset($_POST['entry_id']) ? intval($_POST['entry_id']) : 0;
    $entry = get_post($entry_id);
    if (!$entry || $entry->post_type !== 'hair_journey_entry' || $entry->post_author != $user_id) {
        wp_send_json_error('Entry not found or unauthorized');
        return;
    }
    
    $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
    $description = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '';
    $products = isset($_POST['products']) ? sanitize_text_field($_POST['products']) : '';
    $notes = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';
    $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 3;
    $mood_demeanor = isset($_POST['mood_demeanor']) ? sanitize_text_field($_POST['mood_demeanor']) : '';
    $environment = isset($_POST['environment']) ? sanitize_text_field($_POST['environment']) : '';
    
    wp_update_post([
        'ID' => $entry_id,
        'post_title' => $title,
        'post_content' => $description
    ]);
    
    update_post_meta($entry_id, 'description', $description);
    update_post_meta($entry_id, 'products', $products);
    update_post_meta($entry_id, 'stylist_notes', $notes);
    update_post_meta($entry_id, 'health_rating', $rating);
    update_post_meta($entry_id, 'mood_demeanor', $mood_demeanor);
    update_post_meta($entry_id, 'environment', $environment);
    
    if (!empty($_FILES['photo']['name'])) {
        $attachment_id = media_handle_upload('photo', $entry_id);
        if (!is_wp_error($attachment_id)) {
            set_post_thumbnail($entry_id, $attachment_id);
        } else {
            error_log('Media upload error: ' . $attachment_id->get_error_message());
        }
    }
    
    wp_send_json_success(['message' => 'Entry updated successfully']);
}
add_action('wp_ajax_myavana_edit_diary_entry', 'myavana_edit_diary_entry');

/**
 * Delete a hair journey entry
 */
function myavana_delete_diary_entry() {
    check_ajax_referer('myavana_diary', 'security');
    
    $entry_id = isset($_POST['entry_id']) ? intval($_POST['entry_id']) : 0;
    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error('User not logged in');
        return;
    }
    
    $entry = get_post($entry_id);
    if (!$entry || $entry->post_type !== 'hair_journey_entry' || $entry->post_author != $user_id) {
        wp_send_json_error('Entry not found or unauthorized');
        return;
    }
    
    wp_delete_post($entry_id, true);
    wp_send_json_success(['message' => 'Entry deleted successfully']);
}
add_action('wp_ajax_myavana_delete_diary_entry', 'myavana_delete_diary_entry');

/**
 * Save a hair journey entry (used by entry form component)
 */
if (!function_exists('myavana_save_hair_entry')) {
    function myavana_save_hair_entry() {
        // Check authentication
        if (!is_user_logged_in()) {
            wp_send_json_error('User not logged in');
            return;
        }

        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'myavana_entry')) {
            wp_send_json_error('Security check failed');
            return;
        }

        $user_id = get_current_user_id();
        $entry_id = isset($_POST['entry_id']) ? intval($_POST['entry_id']) : 0;

        // Prepare post data
        $post_data = [
            'post_title' => sanitize_text_field($_POST['entry_title'] ?? 'Hair Entry'),
            'post_content' => sanitize_textarea_field($_POST['entry_notes'] ?? ''),
            'post_status' => 'publish',
            'post_type' => 'hair_journey_entry',
            'post_author' => $user_id
        ];

        // Update or create post
        if ($entry_id > 0) {
            // Update existing entry
            $existing_post = get_post($entry_id);
            if (!$existing_post || $existing_post->post_author != $user_id) {
                wp_send_json_error('Access denied');
                return;
            }
            $post_data['ID'] = $entry_id;
            $post_id = wp_update_post($post_data);
        } else {
            // Create new entry
            $post_id = wp_insert_post($post_data);
        }

        if (is_wp_error($post_id)) {
            wp_send_json_error('Failed to save entry: ' . $post_id->get_error_message());
            return;
        }

        // Save meta data
        if (isset($_POST['mood_rating'])) {
            update_post_meta($post_id, 'mood', intval($_POST['mood_rating']));
        }

        if (isset($_POST['length_progress'])) {
            update_post_meta($post_id, 'length_progress', sanitize_text_field($_POST['length_progress']));
        }

        if (isset($_POST['products'])) {
            update_post_meta($post_id, 'products', $_POST['products']);
        }

        // Handle photo uploads
        $uploaded_images = [];
        $photo_types = ['front_photo', 'side_photo', 'back_photo'];

        foreach ($photo_types as $photo_type) {
            if (!empty($_FILES[$photo_type]['tmp_name'])) {
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                require_once(ABSPATH . 'wp-admin/includes/media.php');

                $attachment_id = media_handle_upload($photo_type, $post_id);

                if (!is_wp_error($attachment_id)) {
                    $uploaded_images[] = $attachment_id;
                    // Set the first uploaded image as featured image
                    if (empty(get_post_thumbnail_id($post_id))) {
                        set_post_thumbnail($post_id, $attachment_id);
                    }
                }
            }
        }

        // Handle AI analysis request
        $ai_analysis_requested = isset($_POST['request_ai_analysis']) && $_POST['request_ai_analysis'] == '1';
        if ($ai_analysis_requested && !empty($uploaded_images)) {
            // Store flag that AI analysis is needed
            update_post_meta($post_id, 'ai_analysis_pending', 1);
        }

        wp_send_json_success([
            'message' => 'Entry saved successfully!',
            'entry_id' => $post_id,
            'ai_analysis_pending' => $ai_analysis_requested
        ]);
    }
}
add_action('wp_ajax_myavana_save_hair_entry', 'myavana_save_hair_entry');

function myavana_handle_vision_api_hair_analysis() {
    global $wpdb;

    // Verify nonce - accept both profile and general nonce
    $nonce_verified = check_ajax_referer('myavana_profile_nonce', 'nonce', false) ||
                     check_ajax_referer('myavana_nonce', 'nonce', false);

    if (!$nonce_verified) {
        error_log('Myavana Hair Analysis: Nonce verification failed');
        wp_send_json_error(['message' => 'Security verification failed']);
        wp_die();
    }

    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'User not logged in']);
        wp_die();
    }

    // Get current user and image data
    $user_id = get_current_user_id();
    $image_data = isset($_POST['image_data']) ? $_POST['image_data'] : '';

    // Validate image data
    if (empty($image_data) || !base64_decode($image_data, true)) {
        wp_send_json_error(['message' => 'Invalid or missing image data']);
        wp_die();
    }

    // CHECK 30 ANALYSES PER MONTH LIMIT
    $analysis_history = get_user_meta($user_id, 'myavana_hair_analysis_history', true);
    if (!is_array($analysis_history)) {
        $analysis_history = [];
    }

    // Count analyses from current month
    $current_month = date('Y-m');
    $current_month_count = 0;
    foreach ($analysis_history as $past_analysis) {
        $analysis_date = $past_analysis['date'] ?? '';
        if (strpos($analysis_date, $current_month) === 0) {
            $current_month_count++;
        }
    }

    // Enforce 30 per month limit
    if ($current_month_count >= 30) {
        $next_month = date('F Y', strtotime('first day of next month'));
        wp_send_json_error([
            'message' => 'Monthly analysis limit reached. You have used all 30 AI analyses for this month. Your limit will reset on ' . $next_month . '.',
            'limit_reached' => true,
            'analyses_used' => $current_month_count,
            'analyses_remaining' => 0
        ]);
        wp_die();
    }

    try {
        // Include AI integration class
        require_once plugin_dir_path(__FILE__) . '../includes/ai-integration.php';

        // Get user context for personalized analysis
        $user_context = [
            'user_id' => $user_id,
            'profile_data' => get_user_meta($user_id, 'myavana_profile_data', true),
            'hair_journey_stage' => get_user_meta($user_id, 'hair_journey_stage', true),
            'hair_health_rating' => get_user_meta($user_id, 'hair_health_rating', true)
        ];

        // Initialize AI class and perform analysis
        $ai = new Myavana_AI();
        $result = $ai->analyze_hair_comprehensive($image_data, $user_context);

        // Check for errors in the result
        if (isset($result['error'])) {
            error_log('Myavana Hair Analysis Error: ' . $result['error']);
            wp_send_json_error(['message' => $result['error']]);
            wp_die();
        }

        // Validate analysis data structure
        if (!isset($result['analysis']) || !is_array($result['analysis'])) {
            error_log('Myavana Hair Analysis: Invalid analysis structure');
            wp_send_json_error(['message' => 'Invalid analysis data structure']);
            wp_die();
        }

        $analysis = $result['analysis'];

        // Ensure required fields exist with fallbacks
        $required_fields = [
            'environment' => 'Indoor setting',
            'user_description' => 'User visible in image',
            'mood_demeanor' => 'Neutral',
            'hair_analysis' => [],
            'recommendations' => [],
            'products' => [],
            'summary' => 'Hair analysis completed successfully.',
            'full_context' => 'Comprehensive hair analysis performed.'
        ];

        foreach ($required_fields as $field => $default) {
            if (!isset($analysis[$field]) || empty($analysis[$field])) {
                $analysis[$field] = $default;
            }
        }

        // Ensure hair_analysis subfields exist
        if (!is_array($analysis['hair_analysis'])) {
            $analysis['hair_analysis'] = [];
        }

        $hair_analysis_fields = [
            'type' => 'Not determined',
            'curl_pattern' => 'Not determined',
            'length' => 'Medium',
            'texture' => 'Medium',
            'density' => 'Medium',
            'hydration' => 70,
            'health_score' => 75,
            'hairstyle' => 'Natural',
            'damage' => 'None observed',
            'scalp_health' => 'Healthy',
            'hair_color' => 'Natural',
            'porosity' => 'Medium',
            'elasticity' => 75,
            'strand_thickness' => 'Medium',
            'growth_pattern' => 'Normal'
        ];

        foreach ($hair_analysis_fields as $field => $default) {
            if (!isset($analysis['hair_analysis'][$field])) {
                $analysis['hair_analysis'][$field] = $default;
            }
        }

        // Ensure arrays for recommendations and products
        if (!is_array($analysis['recommendations'])) {
            $analysis['recommendations'] = [
                'Use moisturizing shampoo and conditioner',
                'Apply leave-in treatment for hydration',
                'Regular deep conditioning treatments',
                'Protect hair from heat styling'
            ];
        }

        if (!is_array($analysis['products'])) {
            $analysis['products'] = [
                ['name' => 'Moisturizing Shampoo', 'id' => 'prod_001', 'match' => 80],
                ['name' => 'Deep Conditioner', 'id' => 'prod_002', 'match' => 85],
                ['name' => 'Leave-in Treatment', 'id' => 'prod_003', 'match' => 75]
            ];
        }

        // Add analysis metadata
        $analysis['analysis_date'] = current_time('Y-m-d H:i:s');
        $analysis['user_id'] = $user_id;
        $analysis['confidence_level'] = $result['analysis']['confidence_level'] ?? 85;

        // SAVE ANALYSIS TO USER META
        $analysis_history = get_user_meta($user_id, 'myavana_hair_analysis_history', true);
        if (!is_array($analysis_history)) {
            $analysis_history = [];
        }

        // Add new analysis to history
        $analysis_history[] = [
            'date' => current_time('Y-m-d H:i:s'),
            'hair_type' => $analysis['hair_analysis']['type'] ?? 'Not determined',
            'health_score' => $analysis['hair_analysis']['health_score'] ?? 75,
            'recommendations' => $analysis['recommendations'] ?? [],
            'full_analysis' => $analysis
        ];

        // Keep only last 100 analyses
        if (count($analysis_history) > 100) {
            $analysis_history = array_slice($analysis_history, -100);
        }

        update_user_meta($user_id, 'myavana_hair_analysis_history', $analysis_history);
        error_log('Myavana Hair Analysis: Saved to user meta for user ' . $user_id);

        // SAVE SNAPSHOT TO PROFILE TABLE
        $table_name = $wpdb->prefix . 'myavana_profiles';

        // Get existing profile
        $profile = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE user_id = %d", $user_id));

        // Prepare snapshot data matching template structure
        $snapshot = [
            'timestamp' => current_time('Y-m-d H:i:s'),
            'image_url' => '', // Will be populated after entry image is saved
            'summary' => $analysis['summary'] ?? 'AI-powered hair analysis completed.',
            'hair_analysis' => [
                'type' => $analysis['hair_analysis']['type'] ?? 'Not determined',
                'curl_pattern' => $analysis['hair_analysis']['curl_pattern'] ?? 'Not determined',
                'health_score' => $analysis['hair_analysis']['health_score'] ?? 75,
                'hydration' => $analysis['hair_analysis']['hydration'] ?? 70,
                'elasticity' => $analysis['hair_analysis']['elasticity'] ?? 75,
                'porosity' => $analysis['hair_analysis']['porosity'] ?? 'Medium',
                'density' => $analysis['hair_analysis']['density'] ?? 'Medium',
                'length' => $analysis['hair_analysis']['length'] ?? 'Medium',
                'texture' => $analysis['hair_analysis']['texture'] ?? 'Medium'
            ],
            'recommendations' => $analysis['recommendations'] ?? []
        ];

        if ($profile) {
            // Get existing snapshots
            $snapshots = $profile->hair_analysis_snapshots ? json_decode($profile->hair_analysis_snapshots, true) : [];
            if (!is_array($snapshots)) {
                $snapshots = [];
            }

            // Add new snapshot
            $snapshots[] = $snapshot;

            // Keep only last 50 snapshots
            if (count($snapshots) > 50) {
                $snapshots = array_slice($snapshots, -50);
            }

            // Update profile
            $wpdb->update(
                $table_name,
                ['hair_analysis_snapshots' => wp_json_encode($snapshots)],
                ['user_id' => $user_id],
                ['%s'],
                ['%d']
            );

            error_log('Myavana Hair Analysis: Snapshot saved to profile table for user ' . $user_id);
        } else {
            // Create new profile with snapshot
            $wpdb->insert(
                $table_name,
                [
                    'user_id' => $user_id,
                    'hair_analysis_snapshots' => wp_json_encode([$snapshot]),
                    'created_at' => current_time('mysql')
                ],
                ['%d', '%s', '%s']
            );

            error_log('Myavana Hair Analysis: New profile created with snapshot for user ' . $user_id);
        }

        // AUTO-CREATE HAIR JOURNEY ENTRY
        $entry_title = 'AI Hair Analysis - ' . date('M d, Y');
        $entry_description = $analysis['summary'] ?? 'AI-powered hair analysis completed.';

        // Extract product names
        $products = [];
        if (isset($analysis['products']) && is_array($analysis['products'])) {
            $products = array_map(function($p) {
                return $p['name'] ?? '';
            }, $analysis['products']);
        }
        $products_string = implode(', ', array_filter($products));

        // Calculate rating from health score (1-5 stars)
        $health_score = $analysis['hair_analysis']['health_score'] ?? 75;
        $rating = max(1, min(5, round($health_score / 20)));

        // Create entry
        $post_id = wp_insert_post([
            'post_title' => $entry_title,
            'post_content' => $entry_description,
            'post_type' => 'hair_journey_entry',
            'post_status' => 'publish',
            'post_author' => $user_id,
            'post_date' => current_time('mysql')
        ]);

        if ($post_id && !is_wp_error($post_id)) {
            // Save entry metadata
            update_post_meta($post_id, 'products_used', $products_string);
            update_post_meta($post_id, 'health_rating', $rating);
            update_post_meta($post_id, 'analysis_data', wp_json_encode($analysis));
            update_post_meta($post_id, 'entry_type', 'ai_analysis');
            update_post_meta($post_id, 'mood_demeanor', $analysis['mood_demeanor'] ?? '');
            update_post_meta($post_id, 'environment', $analysis['environment'] ?? '');

            // Save the uploaded image as entry thumbnail
            if (!empty($image_data)) {
                $upload_dir = wp_upload_dir();
                $image_data_decoded = base64_decode($image_data);
                $filename = 'hair-analysis-' . $user_id . '-' . time() . '.jpg';
                $filepath = $upload_dir['path'] . '/' . $filename;

                if (file_put_contents($filepath, $image_data_decoded)) {
                    $wp_filetype = wp_check_filetype($filename, null);
                    $attachment = [
                        'post_mime_type' => $wp_filetype['type'],
                        'post_title' => sanitize_file_name($filename),
                        'post_content' => '',
                        'post_status' => 'inherit',
                        'post_author' => $user_id
                    ];
                    $attachment_id = wp_insert_attachment($attachment, $filepath, $post_id);

                    if (!is_wp_error($attachment_id)) {
                        require_once(ABSPATH . 'wp-admin/includes/image.php');
                        $attachment_data = wp_generate_attachment_metadata($attachment_id, $filepath);
                        wp_update_attachment_metadata($attachment_id, $attachment_data);
                        set_post_thumbnail($post_id, $attachment_id);

                        // Update snapshot with image URL
                        $image_url = wp_get_attachment_url($attachment_id);
                        if ($image_url) {
                            // Get the snapshots array from profile
                            $profile = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE user_id = %d", $user_id));
                            if ($profile) {
                                $snapshots = json_decode($profile->hair_analysis_snapshots, true);
                                if (is_array($snapshots) && !empty($snapshots)) {
                                    // Update the last snapshot (the one we just added) with the image URL
                                    $last_index = count($snapshots) - 1;
                                    $snapshots[$last_index]['image_url'] = $image_url;

                                    // Save back to database
                                    $wpdb->update(
                                        $table_name,
                                        ['hair_analysis_snapshots' => wp_json_encode($snapshots)],
                                        ['user_id' => $user_id],
                                        ['%s'],
                                        ['%d']
                                    );

                                    error_log('Myavana Hair Analysis: Updated snapshot with image URL');
                                }
                            }
                        }
                    }
                }
            }

            error_log('Myavana Hair Analysis: Auto-created entry #' . $post_id . ' for user ' . $user_id);
            $analysis['entry_id'] = $post_id;
            $analysis['entry_created'] = true;
        }

        // Log successful analysis
        error_log('Myavana Hair Analysis: Successfully completed for user ' . $user_id);

        // Return success response
        wp_send_json_success(['analysis' => $analysis]);

    } catch (Exception $e) {
        error_log('Myavana Hair Analysis Exception: ' . $e->getMessage());
        wp_send_json_error(['message' => 'Hair analysis failed: ' . $e->getMessage()]);
    }

    wp_die();
}

add_action('wp_ajax_myavana_handle_vision_api_hair_analysis', 'myavana_handle_vision_api_hair_analysis');
add_action('wp_ajax_nopriv_myavana_vision_api_hair_analysis', 'myavana_handle_vision_api_hair_analysis');

// Free hair analysis for non-logged-in users
function myavana_free_hair_analysis() {
    try {
        // Get user IP for rate limiting
        $user_ip = $_SERVER['REMOTE_ADDR'] ?? '';

        if (empty($user_ip)) {
            wp_send_json_error(['message' => 'Unable to identify your connection']);
            return;
        }

        // Check rate limit (3 analyses per day per IP)
        $rate_limit_key = 'myavana_free_analysis_' . md5($user_ip);
        $analyses_today = get_transient($rate_limit_key) ?: 0;

        if ($analyses_today >= 3) {
            wp_send_json_error([
                'message' => 'You\'ve reached your free analysis limit for today. Sign up to get unlimited analyses!',
                'limit_reached' => true,
                'analyses_used' => $analyses_today
            ]);
            return;
        }

        // Get image data
        $image_data = isset($_POST['image_data']) ? $_POST['image_data'] : '';

        // Validate image data
        if (empty($image_data)) {
            wp_send_json_error(['message' => 'No image provided']);
            return;
        }

        // Remove data URL prefix if present
        if (strpos($image_data, 'data:image') === 0) {
            $image_data = preg_replace('/^data:image\/\w+;base64,/', '', $image_data);
        }

        // Validate base64
        if (!base64_decode($image_data, true)) {
            wp_send_json_error(['message' => 'Invalid image format']);
            return;
        }

        // Include AI integration class
        require_once plugin_dir_path(__FILE__) . '../includes/ai-integration.php';

        // Create AI instance
        $ai = new Myavana_AI();

        // Free analysis prompt (simplified version)
        $prompt = "You are a professional hair care analyst. Analyze this hair image and provide:

1. **Hair Type**: Identify the hair type (straight, wavy, curly, or coily) and porosity level
2. **Hair Health**: Rate overall hair health (1-10) and identify any visible concerns
3. **Key Recommendations**: Provide 3 specific, actionable care tips
4. **Product Suggestions**: Recommend 2-3 product types that would benefit this hair

Keep the analysis professional, encouraging, and actionable. Focus on what you can see in the image.

Format your response as JSON:
{
    \"hair_type\": \"...\",
    \"porosity\": \"...\",
    \"health_score\": 0-10,
    \"concerns\": [\"...\"],
    \"recommendations\": [\"...\", \"...\", \"...\"],
    \"products\": [\"...\", \"...\"]
}";

        // Analyze with Gemini
        $analysis_result = $ai->analyze_hair_with_vision($image_data, $prompt);

        if (!$analysis_result || isset($analysis_result['error'])) {
            wp_send_json_error([
                'message' => 'Failed to analyze image. Please try again.',
                'error' => $analysis_result['error'] ?? 'Unknown error'
            ]);
            return;
        }

        // Parse JSON response
        $analysis_text = $analysis_result['analysis'] ?? '';

        // Try to extract JSON from markdown code blocks
        if (preg_match('/```json\s*(.*?)\s*```/s', $analysis_text, $matches)) {
            $analysis_text = $matches[1];
        } else if (preg_match('/```\s*(.*?)\s*```/s', $analysis_text, $matches)) {
            $analysis_text = $matches[1];
        }

        $analysis_data = json_decode($analysis_text, true);

        if (!$analysis_data) {
            // Fallback: use raw text
            $analysis_data = [
                'raw_analysis' => $analysis_text,
                'formatted' => true
            ];
        }

        // Increment rate limit counter
        set_transient($rate_limit_key, $analyses_today + 1, DAY_IN_SECONDS);

        // Calculate remaining analyses
        $remaining = 3 - ($analyses_today + 1);

        // Send success response
        wp_send_json_success([
            'analysis' => $analysis_data,
            'remaining_analyses' => $remaining,
            'analyses_used' => $analyses_today + 1,
            'signup_encouraged' => true,
            'message' => $remaining > 0
                ? "Analysis complete! You have {$remaining} free analyses remaining today."
                : "This was your last free analysis for today. Sign up for unlimited analyses!"
        ]);

    } catch (Exception $e) {
        error_log('MYAVANA: Free analysis error: ' . $e->getMessage());
        wp_send_json_error([
            'message' => 'An error occurred during analysis. Please try again.',
            'error' => $e->getMessage()
        ]);
    }
}
add_action('wp_ajax_nopriv_myavana_free_hair_analysis', 'myavana_free_hair_analysis');
add_action('wp_ajax_myavana_free_hair_analysis', 'myavana_free_hair_analysis');

// AJAX handlers for profile functionality
add_action('wp_ajax_myavana_save_about_me', 'myavana_save_about_me');
function myavana_save_about_me() {
    check_ajax_referer('myavana_profile_nonce', 'nonce');
    
    $user_id = get_current_user_id();
    $about_me = sanitize_textarea_field($_POST['about_me']);
    
    update_user_meta($user_id, 'myavana_about_me', $about_me);
    
    wp_send_json_success();
}

add_action('wp_ajax_myavana_save_hair_goal', 'myavana_save_hair_goal');
add_action('wp_ajax_myavana_entry_action', 'myavana_handle_entry_action');
function myavana_save_hair_goal() {
    check_ajax_referer('myavana_profile_nonce', 'nonce');

    $user_id = get_current_user_id();
    
    // Decode JSON string from $_POST['goal']
    $goal_json = isset($_POST['goal']) ? wp_unslash($_POST['goal']) : '';
    $goal = json_decode($goal_json, true);
    
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($goal)) {
        wp_send_json_error('Invalid goal data format.');
        return;
    }

    // Sanitize goal data recursively
    $goal = array_map_recursive('sanitize_text_field', $goal);
    $index = isset($_POST['index']) && $_POST['index'] !== '' ? intval($_POST['index']) : null;

    $hair_goals = get_user_meta($user_id, 'myavana_hair_goals_structured', true) ?: [];

    if ($index !== null && isset($hair_goals[$index])) {
        $hair_goals[$index] = $goal;
    } else {
        $hair_goals[] = $goal;
    }

    update_user_meta($user_id, 'myavana_hair_goals_structured', $hair_goals);

    wp_send_json_success();
}

add_action('wp_ajax_myavana_delete_hair_goal', 'myavana_delete_hair_goal');
function myavana_delete_hair_goal() {
    check_ajax_referer('myavana_profile_nonce', 'nonce');
    
    $user_id = get_current_user_id();
    $index = intval($_POST['index']);
    
    $hair_goals = get_user_meta($user_id, 'myavana_hair_goals_structured', true) ?: [];
    
    if (isset($hair_goals[$index])) {
        array_splice($hair_goals, $index, 1);
        update_user_meta($user_id, 'myavana_hair_goals_structured', $hair_goals);
        wp_send_json_success();
    } else {
        wp_send_json_error('Goal not found');
    }
}

add_action('wp_ajax_myavana_add_goal_update', 'myavana_add_goal_update');
function myavana_add_goal_update() {
    check_ajax_referer('myavana_profile_nonce', 'nonce');

    $user_id = get_current_user_id();
    $index = intval($_POST['index']);

    // Decode JSON string from $_POST['update']
    $update_json = isset($_POST['update']) ? wp_unslash($_POST['update']) : '';
    $update = json_decode($update_json, true);

    if (json_last_error() !== JSON_ERROR_NONE || !is_array($update)) {
        wp_send_json_error('Invalid update data format.');
        return;
    }

    // Sanitize update data recursively
    $update = array_map_recursive('sanitize_text_field', $update);

    $hair_goals = get_user_meta($user_id, 'myavana_hair_goals_structured', true) ?: [];

    if (isset($hair_goals[$index])) {
        if (!isset($hair_goals[$index]['progress_text'])) {
            $hair_goals[$index]['progress_text'] = [];
        }
        $hair_goals[$index]['progress_text'][] = $update;
        update_user_meta($user_id, 'myavana_hair_goals_structured', $hair_goals);
        wp_send_json_success();
    } else {
        wp_send_json_error('Goal not found');
    }
}

add_action('wp_ajax_myavana_analyze_hair', 'myavana_analyze_hair');
function myavana_analyze_hair() {
    check_ajax_referer('myavana_profile_nonce', 'nonce');
    
    $user_id = get_current_user_id();
    $image_data = sanitize_text_field($_POST['image_data']);
    
    // Check weekly limit
    $analysis_history = get_user_meta($user_id, 'myavana_hair_analysis_history', true) ?: [];
    $current_week = date('W');
    $weekly_analysis = array_filter($analysis_history, function($item) use ($current_week) {
        return date('W', strtotime($item['date'])) === $current_week;
    });
    
    if (count($weekly_analysis) >= 3) {
        wp_send_json_error('Weekly analysis limit reached');
        return;
    }
    
    // Process image with AI (using your existing myavana_handle_vision_api function)
    $analysis = myavana_process_hair_analysis($image_data);
    
    if ($analysis) {
        // Save to history
        $analysis_history[] = [
            'date' => current_time('mysql'),
            'image' => $image_data,
            'summary' => $analysis['summary'],
            'hair_analysis' => $analysis['hair_analysis'],
            'recommendations' => $analysis['recommendations'],
            'products' => $analysis['products']
        ];
        
        update_user_meta($user_id, 'myavana_hair_analysis_history', $analysis_history);
        wp_send_json_success();
    } else {
        wp_send_json_error('Analysis failed');
    }
}

function myavana_process_hair_analysis($image_data) {
    // This would use your existing OpenAI integration
    // For demonstration, we'll return mock data
    
    $mock_analysis = [
        'summary' => 'Your hair appears to be wavy with medium porosity. It shows signs of dryness (hydration level at 45%) and some split ends. The overall health score is 68%.',
        'hair_analysis' => [
            'type' => 'Wavy',
            'curl_pattern' => '2B',
            'length' => 'Shoulder-length',
            'texture' => 'Medium',
            'density' => 'Medium',
            'hydration' => 45,
            'health_score' => 68,
            'hairstyle' => 'Loose',
            'damage' => 'Split ends, some breakage'
        ],
        'recommendations' => [
            'Use a moisturizing shampoo and conditioner',
            'Apply a leave-in conditioner after washing',
            'Limit heat styling to twice a week',
            'Get a trim every 8-10 weeks'
        ],
        'products' => [
            ['name' => 'Hydrating Shampoo', 'id' => 'prod_123', 'match' => 85],
            ['name' => 'Moisture Rich Conditioner', 'id' => 'prod_456', 'match' => 82],
            ['name' => 'Leave-In Conditioner', 'id' => 'prod_789', 'match' => 78]
        ]
    ];
    
    return $mock_analysis;
    
    // In production, you would call your actual AI function:
    // return myavana_handle_vision_api($image_data);
}

add_action('wp_ajax_myavana_save_routine_step', 'myavana_save_routine_step');
function myavana_save_routine_step() {
    check_ajax_referer('myavana_profile_nonce', 'nonce');

    $user_id = get_current_user_id();
    
    // Decode JSON string from $_POST['step']
    $step_json = isset($_POST['step']) ? wp_unslash($_POST['step']) : '';
    $step = json_decode($step_json, true);
    
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($step)) {
        wp_send_json_error('Invalid step data format.');
        return;
    }

    // Sanitize step data recursively
    $step = array_map_recursive('sanitize_text_field', $step);
    $index = isset($_POST['index']) && $_POST['index'] !== '' ? intval($_POST['index']) : null;

    $current_routine = get_user_meta($user_id, 'myavana_current_routine', true) ?: [];

    if ($index !== null && isset($current_routine[$index])) {
        $current_routine[$index] = $step;
    } else {
        $current_routine[] = $step;
    }

    update_user_meta($user_id, 'myavana_current_routine', $current_routine);

    wp_send_json_success();
}

add_action('wp_ajax_myavana_delete_routine_step', 'myavana_delete_routine_step');
function myavana_delete_routine_step() {
    check_ajax_referer('myavana_profile_nonce', 'nonce');
    
    $user_id = get_current_user_id();
    $index = intval($_POST['index']);
    
    $current_routine = get_user_meta($user_id, 'myavana_current_routine', true) ?: [];
    
    if (isset($current_routine[$index])) {
        array_splice($current_routine, $index, 1);
        update_user_meta($user_id, 'myavana_current_routine', $current_routine);
        wp_send_json_success();
    } else {
        wp_send_json_error('Step not found');
    }
}


// AJAX handler for profile updates
function myavana_update_profile() {
    check_ajax_referer('myavana_profile', 'myavana_nonce');
    global $wpdb;
    $user_id = get_current_user_id();
    $target_user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : $user_id;

    if ($user_id !== $target_user_id) {
        wp_send_json_error(['error' => 'Unauthorized to update this profile']);
        return;
    }

    $data = [
        'hair_journey_stage' => sanitize_text_field($_POST['hair_journey_stage'] ?? ''),
        'hair_health_rating' => min(max(intval($_POST['hair_health_rating'] ?? 5), 1), 5),
        'hair_type' => sanitize_text_field($_POST['hair_type'] ?? ''),
        'hair_goals' => sanitize_textarea_field($_POST['hair_goals'] ?? ''),
        'life_journey_stage' => sanitize_text_field($_POST['life_journey_stage'] ?? ''),
        'birthday' => sanitize_text_field($_POST['birthday'] ?? ''),
        'location' => sanitize_text_field($_POST['location'] ?? '')
    ];

    $result = $wpdb->update(
        $wpdb->prefix . 'myavana_profiles',
        $data,
        ['user_id' => $user_id],
        ['%s', '%d', '%s', '%s', '%s', '%s', '%s'],
        ['%d']
    );

    if ($result === false) {
        error_log('Myavana Hair Journey: Failed to update profile for user ' . $user_id . ': ' . $wpdb->last_error);
        wp_send_json_error(['error' => 'Failed to update profile. Please try again.']);
    } else {
        try {
            $context = sprintf('User updated their profile with hair journey stage: %s, hair type: %s.', $data['hair_journey_stage'], $data['hair_type']);
            $ai = new Myavana_AI();
            $tip = __('AI-generated tip: %s', 'myavana', $ai->get_ai_tip($context));
            wp_send_json_success([
                'message' => 'Profile updated successfully!',
                'tip' => $tip
            ]);
        } catch (Exception $e) {
            error_log('Myavana AI Tip Error: ' . $e->getMessage());
            wp_send_json_success([
                'message' => 'Profile updated successfully!',
                'tip' => 'Keep updating your profile to track your hair journey!'
            ]);
        }
    }
}
add_action('wp_ajax_myavana_update_profile', 'myavana_update_profile');

// Helper function to recursively sanitize arrays
function array_map_recursive($callback, $array) {
    if (!is_array($array)) {
        return $callback($array);
    }
    return array_map(function($item) use ($callback) {
        return is_array($item) ? array_map_recursive($callback, $item) : $callback($item);
    }, $array);
}

add_action('wp_ajax_myavana_update_goal_progress', 'myavana_update_goal_progress_callback');

function myavana_handle_entry_action() {
    // Check if this is an add or update action
    if (isset($_POST['entry_id']) && !empty($_POST['entry_id'])) {
        // This is an update action
        myavana_update_entry();
    } else {
        // This is an add action
        myavana_add_entry();
    }
}

function myavana_update_goal_progress_callback() {
    check_ajax_referer('myavana_profile_nonce', 'nonce');

    $user_id = get_current_user_id();
    $index = isset($_POST['index']) ? intval($_POST['index']) : -1;
    $progress = isset($_POST['progress']) ? intval($_POST['progress']) : 0;

    if ($index < 0 || $progress < 0 || $progress > 100) {
        wp_send_json_error('Invalid input data.');
        wp_die();
    }

    // Use user meta to match template data source
    $hair_goals = get_user_meta($user_id, 'myavana_hair_goals_structured', true) ?: [];

    if (isset($hair_goals[$index])) {
        $old_progress = intval($hair_goals[$index]['progress']);

        // Only update if progress actually changed
        if ($old_progress !== $progress) {
            // Update current progress
            $hair_goals[$index]['progress'] = $progress;

            // Add to progress history for tracking
            if (!isset($hair_goals[$index]['progress_history'])) {
                $hair_goals[$index]['progress_history'] = [];
            }

            $hair_goals[$index]['progress_history'][] = [
                'progress' => $progress,
                'date' => current_time('mysql'),
                'change' => $progress - $old_progress
            ];

            // Check for milestone achievements
            $milestones = [25, 50, 75, 100];
            $new_milestone = null;
            foreach ($milestones as $milestone) {
                if ($old_progress < $milestone && $progress >= $milestone) {
                    $new_milestone = $milestone;
                }
            }

            update_user_meta($user_id, 'myavana_hair_goals_structured', $hair_goals);

            $response_data = [
                'message' => 'Progress updated successfully.',
                'new_milestone' => $new_milestone,
                'progress' => $progress
            ];

            wp_send_json_success($response_data);
        } else {
            wp_send_json_success('No change in progress.');
        }
    } else {
        wp_send_json_error('Goal not found.');
    }

    wp_die();
}
function myavana_update_goal_progress_callback_0le() {
    check_ajax_referer('myavana_profile_nonce', 'nonce');

    $user_id = get_current_user_id();
    $index = isset($_POST['index']) ? intval($_POST['index']) : -1;
    $progress = isset($_POST['progress']) ? intval($_POST['progress']) : 0;

    if ($index < 0 || $progress < 0 || $progress > 100) {
        wp_send_json_error('Invalid input data.');
        wp_die();
    }

    $hair_goals = get_user_meta($user_id, 'myavana_hair_goals_structured', true) ?: [];
    if (isset($hair_goals[$index])) {
        $hair_goals[$index]['progress'] = $progress;
        update_user_meta($user_id, 'myavana_hair_goals_structured', $hair_goals);
        wp_send_json_success('Progress updated successfully.');
    } else {
        wp_send_json_error('Goal not found.');
    }

    wp_die();
}

add_action('wp_ajax_myavana_update_profile', 'myavana_update_profile_callback');

function myavana_update_profile_callback() {
    check_ajax_referer('myavana_profile', 'myavana_nonce');

    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error('User not logged in.');
        wp_die();
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'myavana_profiles';

    $data = [
        'hair_journey_stage' => sanitize_text_field($_POST['hair_journey_stage'] ?? ''),
        'hair_type' => sanitize_text_field($_POST['hair_type'] ?? ''),
        'hair_health_rating' => intval($_POST['hair_health_rating'] ?? 5),
        'life_journey_stage' => sanitize_text_field($_POST['life_journey_stage'] ?? ''),
        'birthday' => sanitize_text_field($_POST['birthday'] ?? ''),
        'location' => sanitize_text_field($_POST['location'] ?? ''),
        'about_me' => sanitize_textarea_field($_POST['about_me'] ?? '')
    ];

    // Update user meta for about_me
    update_user_meta($user_id, 'myavana_about_me', $data['about_me']);

    // Update profile in custom table
    $result = $wpdb->update(
        $table_name,
        [
            'hair_journey_stage' => $data['hair_journey_stage'],
            'hair_type' => $data['hair_type'],
            'hair_health_rating' => $data['hair_health_rating'],
            'life_journey_stage' => $data['life_journey_stage'],
            'birthday' => $data['birthday'],
            'location' => $data['location']
        ],
        ['user_id' => $user_id],
        ['%s', '%s', '%d', '%s', '%s', '%s'],
        ['%d']
    );

    if ($result === false) {
        error_log('Myavana: Failed to update profile for user ' . $user_id);
        wp_send_json_error('Failed to update profile. Please try again.');
    } else {
        wp_send_json_success([
            'message' => 'Profile updated successfully!',
            'tip' => 'Great job updating your profile! Consider adding a new hair goal to track your progress.'
        ]);
    }

    wp_die();
}
/**
 * Handle simple entry creation for onboarding
 * Simplified version specifically for new user onboarding flow
 */
add_action('wp_ajax_myavana_save_simple_entry', 'myavana_handle_simple_entry_creation');

function myavana_handle_simple_entry_creation() {
    // Verify nonce - support multiple nonce types for flexibility
    $nonce_verified = false;
    $nonce = $_POST['nonce'] ?? '';

    if (wp_verify_nonce($nonce, 'myavana_onboarding')) {
        $nonce_verified = true;
    } elseif (wp_verify_nonce($nonce, 'myavana_entry')) {
        $nonce_verified = true;
    } elseif (wp_verify_nonce($nonce, 'myavana_nonce')) {
        $nonce_verified = true;
    }

    if (!$nonce_verified) {
        wp_send_json_error([
            'message' => 'Security verification failed. Please refresh and try again.'
        ]);
        return;
    }

    // Check authentication
    if (!is_user_logged_in()) {
        wp_send_json_error([
            'message' => 'You must be logged in to create an entry.'
        ]);
        return;
    }

    $user_id = get_current_user_id();

    // Sanitize input data
    $title = sanitize_text_field($_POST['title'] ?? 'My First Hair Journey Entry');
    $description = sanitize_textarea_field($_POST['description'] ?? '');
    $mood = sanitize_text_field($_POST['mood'] ?? 'Excited');
    $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 5;

    // Validate required fields
    if (empty($title)) {
        wp_send_json_error([
            'message' => 'Please add a title for your entry.'
        ]);
        return;
    }

    // Create the entry post
    $post_data = [
        'post_title' => $title,
        'post_content' => $description,
        'post_type' => 'hair_journey_entry',
        'post_status' => 'publish',
        'post_author' => $user_id
    ];

    $post_id = wp_insert_post($post_data);

    if (is_wp_error($post_id)) {
        wp_send_json_error([
            'message' => 'Failed to create entry: ' . $post_id->get_error_message()
        ]);
        return;
    }

    // Save metadata
    update_post_meta($post_id, 'mood_demeanor', $mood);
    update_post_meta($post_id, 'health_rating', $rating);
    update_post_meta($post_id, 'entry_type', 'onboarding');
    update_post_meta($post_id, 'environment', 'home');

    // Handle photo upload if present
    $image_url = '';
    if (!empty($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $upload_overrides = ['test_form' => false];
        $movefile = wp_handle_upload($_FILES['photo'], $upload_overrides);

        if ($movefile && !isset($movefile['error'])) {
            $image_url = $movefile['url'];
            $attachment = [
                'post_mime_type' => $movefile['type'],
                'post_title' => sanitize_file_name(pathinfo($movefile['file'], PATHINFO_FILENAME)),
                'post_content' => '',
                'post_status' => 'inherit'
            ];

            $attach_id = wp_insert_attachment($attachment, $movefile['file'], $post_id);

            if (!is_wp_error($attach_id)) {
                $attach_data = wp_generate_attachment_metadata($attach_id, $movefile['file']);
                wp_update_attachment_metadata($attach_id, $attach_data);
                set_post_thumbnail($post_id, $attach_id);
            }
        }
    }

    // Update onboarding progress
    update_user_meta($user_id, 'myavana_first_entry_created', true);
    update_user_meta($user_id, 'myavana_first_entry_id', $post_id);
    update_user_meta($user_id, 'myavana_first_entry_date', current_time('mysql'));

    // Track this milestone
    if (function_exists('myavana_track_event')) {
        myavana_track_event('first_entry_created', [
            'user_id' => $user_id,
            'entry_id' => $post_id,
            'has_photo' => !empty($image_url)
        ]);
    }

    // Return success response
    wp_send_json_success([
        'message' => '🎉 Your first entry has been created!',
        'entry_id' => $post_id,
        'title' => $title,
        'image_url' => $image_url,
        'tip' => 'Great start! Keep adding entries to track your hair journey progress.'
    ]);
}

/**
 * Ensure myavana_add_entry function is available globally
 * This function is defined in templates/hair-diary-timeline-shortcode.php
 * We need to ensure it's registered as an AJAX action even when that shortcode isn't loaded
 */
if (!function_exists('myavana_add_entry')) {
    require_once plugin_dir_path(__FILE__) . '../templates/hair-diary-timeline-shortcode.php';
}
