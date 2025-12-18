<?php
/**
 * @deprecated This file is deprecated and no longer in use.
 * Use myavana-auth-system.php instead which provides all authentication functionality.
 *
 * This file is kept for reference only and should not be included in the plugin.
 * The Myavana_Auth_System class in myavana-auth-system.php has replaced all functionality.
 *
 * @since 2.4.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Log deprecation warning if this file somehow gets loaded
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('MYAVANA WARNING: auth-helper.php is deprecated. Use myavana-auth-system.php instead.');
}

// Return early - this class should not be instantiated
return;

class MyavanaAuthModal {
    
    private $options;
    
    public function __construct() {
        //add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_footer', array($this, 'render_modal'));
        add_action('wp_ajax_nopriv_myavana_auth_submit', array($this, 'handle_auth_submit'));
        add_action('wp_ajax_myavana_auth_submit', array($this, 'handle_auth_submit'));
        
        $this->options = get_option('myavana_auth_modal_options', array(
            'show_on_homepage' => true,
            'show_site_wide' => false,
            'delay_seconds' => 2,
            'show_once_per_session' => true
        ));
    }
    
    // public function init() {
    //     // Initialize plugin
    // }
    
    public function add_admin_menu() {
        add_submenu_page(
            'options-general.php',
            'Myavana Auth Modal',
            'Auth Modal',
            'manage_options',
            'myavana-auth-modal',
            array($this, 'admin_page')
        );
    }
    
    public function admin_init() {
        register_setting('myavana_auth_modal_group', 'myavana_auth_modal_options');
        
        add_settings_section(
            'myavana_auth_modal_section',
            'Modal Display Settings',
            array($this, 'section_callback'),
            'myavana-auth-modal'
        );
        
        add_settings_field(
            'show_on_homepage',
            'Show on Homepage',
            array($this, 'checkbox_callback'),
            'myavana-auth-modal',
            'myavana_auth_modal_section',
            array('name' => 'show_on_homepage', 'label' => 'Show modal on homepage for non-logged-in users')
        );
        
        add_settings_field(
            'show_site_wide',
            'Show Site Wide',
            array($this, 'checkbox_callback'),
            'myavana-auth-modal',
            'myavana_auth_modal_section',
            array('name' => 'show_site_wide', 'label' => 'Show modal on all pages for non-logged-in users')
        );
        
        add_settings_field(
            'delay_seconds',
            'Delay (seconds)',
            array($this, 'number_callback'),
            'myavana-auth-modal',
            'myavana_auth_modal_section',
            array('name' => 'delay_seconds', 'label' => 'Delay before showing modal (seconds)')
        );
        
        add_settings_field(
            'show_once_per_session',
            'Show Once Per Session',
            array($this, 'checkbox_callback'),
            'myavana-auth-modal',
            'myavana_auth_modal_section',
            array('name' => 'show_once_per_session', 'label' => 'Only show modal once per session')
        );
    }
    
    public function section_callback() {
        echo '<p>Configure when and how the authentication modal should appear.</p>';
    }
    
    public function checkbox_callback($args) {
        $value = isset($this->options[$args['name']]) ? $this->options[$args['name']] : false;
        echo '<input type="checkbox" id="' . $args['name'] . '" name="myavana_auth_modal_options[' . $args['name'] . ']" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label for="' . $args['name'] . '">' . $args['label'] . '</label>';
    }
    
    public function number_callback($args) {
        $value = isset($this->options[$args['name']]) ? $this->options[$args['name']] : 2;
        echo '<input type="number" id="' . $args['name'] . '" name="myavana_auth_modal_options[' . $args['name'] . ']" value="' . $value . '" min="0" max="60" />';
        echo '<label for="' . $args['name'] . '">' . $args['label'] . '</label>';
    }
    
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Myavana Auth Modal Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('myavana_auth_modal_group');
                do_settings_sections('myavana-auth-modal');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
    
    public function enqueue_scripts() {
        if (is_user_logged_in()) {
            return;
        }
        
        $should_show = false;
        
        if ($this->options['show_site_wide']) {
            $should_show = true;
        } elseif ($this->options['show_on_homepage'] && is_home()) {
            $should_show = true;
        }
        
        if ($should_show) {
            wp_enqueue_script('myavana-auth-modal', plugin_dir_url(__FILE__) . 'js/auth-modal.js', array('jquery'), '1.0.0', true);
            wp_localize_script('myavana-auth-modal', 'myavana_auth_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('myavana_auth_nonce'),
                'delay' => $this->options['delay_seconds'] * 1000,
                'show_once' => $this->options['show_once_per_session']
            ));
        }
    }
    
    public function render_modal() {
        if (is_user_logged_in()) {
            return;
        }
        
        $should_show = false;
        
        if ($this->options['show_site_wide']) {
            $should_show = true;
        } elseif ($this->options['show_on_homepage'] && is_home()) {
            $should_show = true;
        }
        
        if (!$should_show) {
            return;
        }
        
        ?>
        <style>
            .myavana-auth-modal {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.6);
                backdrop-filter: blur(8px);
                z-index: 999999;
                align-items: center;
                justify-content: center;
                animation: fadeIn 0.3s ease-out;
            }
            
            .myavana-auth-modal.show {
                display: flex;
            }
            
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            
            .myavana-modal-container {
                background: white;
                width: 90%;
                max-width: 900px;
                height: 600px;
                border-radius: 20px;
                overflow: hidden;
                display: flex;
                box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
                animation: slideUp 0.4s ease-out;
                position: relative;
            }
            
            @keyframes slideUp {
                from { 
                    opacity: 0;
                    transform: translateY(40px) scale(0.95);
                }
                to { 
                    opacity: 1;
                    transform: translateY(0) scale(1);
                }
            }
            
            .myavana-close-btn {
                position: absolute;
                top: 20px;
                right: 20px;
                background: rgba(255, 255, 255, 0.9);
                border: none;
                width: 40px;
                height: 40px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                z-index: 10;
                transition: all 0.2s ease;
                font-size: 18px;
                color: #222323;
            }
            
            .myavana-close-btn:hover {
                background: white;
                transform: scale(1.1);
            }
            
            .myavana-left-section {
                flex: 1;
                background: linear-gradient(135deg, #e7a690 0%, #fce5d7 100%);
                position: relative;
                overflow: hidden;
                display: flex;
                flex-direction: column;
                justify-content: center;
                padding: 60px 40px;
            }
            
            .myavana-left-section::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000"><defs><radialGradient id="a" cx="50%" cy="50%" r="50%"><stop offset="0%" style="stop-color:rgba(255,255,255,0.1)"/><stop offset="100%" style="stop-color:rgba(255,255,255,0.05)"/></radialGradient></defs><circle cx="200" cy="300" r="100" fill="url(%23a)"/><circle cx="800" cy="200" r="150" fill="url(%23a)"/><circle cx="600" cy="700" r="80" fill="url(%23a)"/></svg>');
                opacity: 0.3;
            }
            
            .myavana-floating-elements {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                overflow: hidden;
                pointer-events: none;
            }
            
            .myavana-floating-element {
                position: absolute;
                width: 60px;
                height: 60px;
                border-radius: 50%;
                background: rgba(255, 255, 255, 0.1);
                animation: float 6s ease-in-out infinite;
            }
            
            .myavana-floating-element:nth-child(1) {
                top: 10%;
                left: 10%;
                animation-delay: 0s;
            }
            
            .myavana-floating-element:nth-child(2) {
                top: 60%;
                right: 15%;
                animation-delay: 2s;
                width: 40px;
                height: 40px;
            }
            
            .myavana-floating-element:nth-child(3) {
                bottom: 20%;
                left: 20%;
                animation-delay: 4s;
                width: 80px;
                height: 80px;
            }
            
            @keyframes float {
                0%, 100% { transform: translateY(0px) rotate(0deg); }
                50% { transform: translateY(-20px) rotate(180deg); }
            }
            
            .myavana-left-content {
                position: relative;
                z-index: 2;
                color: white;
            }
            
            .myavana-logo {
                font-size: 32px;
                font-weight: 700;
                margin-bottom: 30px;
                letter-spacing: -1px;
            }
            
            .myavana-tagline {
                font-size: 28px;
                font-weight: 600;
                margin-bottom: 40px;
                line-height: 1.3;
                text-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            }
            
            .myavana-features {
                list-style: none;
                padding: 0;
                margin: 0;
            }
            
            .myavana-features li {
                display: flex;
                align-items: center;
                margin-bottom: 20px;
                font-size: 16px;
                font-weight: 500;
            }
            
            .myavana-features li::before {
                content: '✨';
                margin-right: 12px;
                font-size: 18px;
            }
            
            .myavana-right-section {
                flex: 1;
                background: white;
                display: flex;
                flex-direction: column;
                justify-content: center;
                padding: 60px 40px;
                position: relative;
            }
            
            .myavana-auth-toggle {
                display: flex;
                background: #f5f5f7;
                border-radius: 12px;
                padding: 4px;
                margin-bottom: 40px;
                position: relative;
            }
            
            .myavana-auth-toggle button {
                flex: 1;
                padding: 12px 24px;
                background: none;
                border: none;
                border-radius: 8px;
                font-weight: 500;
                cursor: pointer;
                transition: all 0.3s ease;
                position: relative;
                z-index: 2;
            }
            
            .myavana-auth-toggle button.active {
                background: white;
                color: #e7a690;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            }
            
            .myavana-auth-toggle button:not(.active) {
                color: #666;
            }
            
            .myavana-form-container {
                position: relative;
                min-height: 350px;
            }
            
            .myavana-auth-form {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                opacity: 0;
                visibility: hidden;
                transition: all 0.3s ease;
                transform: translateY(20px);
            }
            
            .myavana-auth-form.active {
                opacity: 1;
                visibility: visible;
                transform: translateY(0);
            }
            
            .myavana-form-group {
                margin-bottom: 24px;
            }
            
            .myavana-form-group label {
                display: block;
                margin-bottom: 8px;
                font-weight: 500;
                color: #222323;
                font-size: 14px;
            }
            
            .myavana-form-group input {
                width: 100%;
                padding: 16px 18px;
                border: 2px solid #eeece1;
                border-radius: 12px;
                font-size: 16px;
                transition: all 0.3s ease;
                background: white;
                box-sizing: border-box;
            }
            
            .myavana-form-group input:focus {
                outline: none;
                border-color: #e7a690;
                box-shadow: 0 0 0 3px rgba(231, 166, 144, 0.1);
            }
            
            .myavana-form-group input::placeholder {
                color: #999;
            }
            
            .myavana-checkbox-group {
                display: flex;
                align-items: center;
                margin-bottom: 24px;
            }
            
            .myavana-checkbox-group input[type="checkbox"] {
                width: 18px;
                height: 18px;
                margin-right: 12px;
                accent-color: #e7a690;
            }
            
            .myavana-checkbox-group label {
                margin-bottom: 0;
                font-size: 14px;
                color: #666;
            }
            
            .myavana-submit-btn {
                width: 100%;
                padding: 16px;
                background: linear-gradient(135deg, #e7a690 0%, #fce5d7 100%);
                border: none;
                border-radius: 12px;
                color: white;
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s ease;
                margin-bottom: 24px;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            
            .myavana-submit-btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 25px rgba(231, 166, 144, 0.3);
            }
            
            .myavana-submit-btn:active {
                transform: translateY(0);
            }
            
            .myavana-submit-btn:disabled {
                opacity: 0.6;
                cursor: not-allowed;
                transform: none;
            }
            
            .myavana-divider {
                display: flex;
                align-items: center;
                margin: 24px 0;
                color: #999;
                font-size: 14px;
            }
            
            .myavana-divider::before,
            .myavana-divider::after {
                content: '';
                flex: 1;
                height: 1px;
                background: #eeece1;
            }
            
            .myavana-divider::before {
                margin-right: 16px;
            }
            
            .myavana-divider::after {
                margin-left: 16px;
            }
            
            .myavana-social-buttons {
                display: flex;
                gap: 12px;
                margin-bottom: 24px;
            }
            
            .myavana-social-btn {
                flex: 1;
                padding: 12px;
                border: 2px solid #eeece1;
                border-radius: 12px;
                background: white;
                cursor: pointer;
                transition: all 0.3s ease;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: 500;
                color: #666;
            }
            
            .myavana-social-btn:hover {
                border-color: #e7a690;
                background: #fce5d7;
                color: #e7a690;
            }
            
            .myavana-forgot-link {
                text-align: center;
                color: #e7a690;
                text-decoration: none;
                font-size: 14px;
                font-weight: 500;
                display: block;
            }
            
            .myavana-forgot-link:hover {
                text-decoration: underline;
            }
            
            .myavana-error-message {
                color: #d32f2f;
                font-size: 14px;
                margin-top: 8px;
                display: none;
                padding: 12px;
                background: rgba(211, 47, 47, 0.1);
                border: 1px solid rgba(211, 47, 47, 0.2);
                border-radius: 8px;
                animation: slideIn 0.3s ease-out;
            }

            .myavana-success-message {
                color: #2e7d32;
                font-size: 14px;
                margin-top: 8px;
                display: none;
                padding: 12px;
                background: rgba(46, 125, 50, 0.1);
                border: 1px solid rgba(46, 125, 50, 0.2);
                border-radius: 8px;
                animation: slideIn 0.3s ease-out;
            }

            @keyframes slideIn {
                from {
                    opacity: 0;
                    transform: translateY(-10px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            /* Loading state for buttons */
            .myavana-submit-btn.loading {
                position: relative;
                color: transparent;
            }

            .myavana-submit-btn.loading::after {
                content: '';
                position: absolute;
                width: 20px;
                height: 20px;
                top: 50%;
                left: 50%;
                margin-left: -10px;
                margin-top: -10px;
                border: 2px solid rgba(255, 255, 255, 0.3);
                border-radius: 50%;
                border-top-color: white;
                animation: spin 1s ease-in-out infinite;
            }

            @keyframes spin {
                to {
                    transform: rotate(360deg);
                }
            }
            
            @media (max-width: 768px) {
                .myavana-modal-container {
                    width: 95%;
                    height: 90vh;
                    flex-direction: column;
                    border-radius: 16px;
                }
                
                .myavana-left-section {
                    padding: 30px 20px;
                    min-height: 200px;
                }
                
                .myavana-tagline {
                    font-size: 22px;
                    margin-bottom: 30px;
                }
                
                .myavana-right-section {
                    padding: 30px 20px;
                    justify-content: flex-start;
                }
                
                .myavana-form-container {
                    min-height: auto;
                }
            }

        </style>
        
        <div class="myavana-auth-modal" id="myavanaAuthModal">
            <div class="myavana-modal-container">
                <button class="myavana-close-btn" id="myavanaCloseBtn">×</button>
                
                <div class="myavana-left-section">
                    <div class="myavana-floating-elements">
                        <div class="myavana-floating-element"></div>
                        <div class="myavana-floating-element"></div>
                        <div class="myavana-floating-element"></div>
                    </div>
                    
                    <div class="myavana-left-content">
                        <div class="myavana-logo">MYAVANA</div>
                        <h2 class="myavana-tagline">YOUR HAIR JOURNEY STARTS HERE</h2>
                        <ul class="myavana-features">
                            <li>AI-powered hair analysis & recommendations</li>
                            <li>Track your progress with personalized insights</li>
                            <li>Connect with a community of hair enthusiasts</li>
                            <li>Expert guidance for all hair types & textures</li>
                        </ul>
                    </div>
                </div>
                
                <div class="myavana-right-section">
                    <div class="myavana-auth-toggle">
                        <button class="active" id="myavanaSigninTab">Sign In</button>
                        <button id="myavanaSignupTab">Sign Up</button>
                        <button id="myavanaForgotTab" style="display: none;">Reset Password</button>
                    </div>
                    
                    <div class="myavana-form-container">
                        <!-- Sign In Form -->
                        <form class="myavana-auth-form active" id="myavanaSigninForm">
                            <div class="myavana-form-group">
                                <label for="myavana-signin-email">Email or Username</label>
                                <input type="text" id="myavana-signin-email" name="email" placeholder="Enter your email or username" required>
                            </div>
                            <div class="myavana-form-group">
                                <label for="myavana-signin-password">Password</label>
                                <input type="password" id="myavana-signin-password" name="password" placeholder="Enter your password" required>
                            </div>
                            <div class="myavana-checkbox-group">
                                <input type="checkbox" id="myavana-remember" name="remember">
                                <label for="myavana-remember">Remember me</label>
                            </div>
                            <button type="submit" class="myavana-submit-btn">Sign In</button>
                            <div class="myavana-error-message" id="myavana-signin-error"></div>
                            <div class="myavana-success-message" id="myavana-signin-success"></div>
                                
                            
                            <a href="#" class="myavana-forgot-link" id="myavanaForgotLink">Forgot your password?</a>
                        </form>
                        
                        <!-- Sign Up Form -->
                        <form class="myavana-auth-form" id="myavanaSignupForm">
                            <div class="myavana-form-group">
                                <label for="myavana-signup-name">Full Name</label>
                                <input type="text" id="myavana-signup-name" name="name" placeholder="Enter your full name" required>
                            </div>
                            <div class="myavana-form-group">
                                <label for="myavana-signup-email">Email</label>
                                <input type="email" id="myavana-signup-email" name="email" placeholder="Enter your email" required>
                            </div>
                            <div class="myavana-form-group">
                                <label for="myavana-signup-password">Password</label>
                                <input type="password" id="myavana-signup-password" name="password" placeholder="Create a password" required>
                            </div>
                            <div class="myavana-checkbox-group">
                                <input type="checkbox" id="myavana-terms" name="terms" required>
                                <label for="myavana-terms">I agree to the Terms of Service and Privacy Policy</label>
                            </div>
                            <button type="submit" class="myavana-submit-btn">Create Account</button>
                            <div class="myavana-error-message" id="myavana-signup-error"></div>
                            <div class="myavana-success-message" id="myavana-signup-success"></div>
                        </form>

                        <!-- Forgot Password Form -->
                        <form class="myavana-auth-form" id="myavanaForgotForm">
                            <div class="myavana-form-group">
                                <label for="myavana-forgot-email">Email Address</label>
                                <input type="email" id="myavana-forgot-email" name="email" placeholder="Enter your email address" required>
                            </div>
                            <button type="submit" class="myavana-submit-btn">Send Reset Link</button>
                            <div class="myavana-error-message" id="myavana-forgot-error"></div>
                            <div class="myavana-success-message" id="myavana-forgot-success"></div>
                            <a href="#" class="myavana-forgot-link" id="myavanaBackToSignin">Back to Sign In</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        (function($) {
            'use strict';
            
            let modalShown = false;
            
            // Check if modal should be shown
            function shouldShowModal() {
                if (myavana_auth_ajax.show_once && sessionStorage.getItem('myavana_modal_shown')) {
                    return false;
                }
                return true;
            }
            
            // Show modal with delay
            function showModal(activeTab = 'signin') {
                if (!shouldShowModal()) return;
                
                setTimeout(function() {
                    $('#myavanaAuthModal').addClass('show');
                    switchForm(activeTab);
                    
                    if (myavana_auth_ajax.show_once) {
                        sessionStorage.setItem('myavana_modal_shown', 'true');
                    }
                    modalShown = true;
                }, myavana_auth_ajax.delay);
            }
            
            // Close modal
            function closeModal() {
                $('#myavanaAuthModal').removeClass('show');
                modalShown = false;
            }
            
            // Switch between forms with smooth transition
            function switchForm(formType) {
                // Fade out current form
                $('.myavana-auth-form.active').fadeOut(150, function() {
                    // Remove active classes
                    $('.myavana-auth-toggle button').removeClass('active');
                    $('.myavana-auth-form').removeClass('active');

                    // Clear all error/success messages
                    $('.myavana-error-message, .myavana-success-message').fadeOut();

                    if (formType === 'signin') {
                        $('#myavanaSigninTab').addClass('active').show();
                        $('#myavanaSignupTab').show();
                        $('#myavanaForgotTab').hide();
                        $('#myavanaSigninForm').addClass('active').fadeIn(150);
                    } else if (formType === 'signup') {
                        $('#myavanaSignupTab').addClass('active');
                        $('#myavanaSigninTab').show();
                        $('#myavanaForgotTab').hide();
                        $('#myavanaSignupForm').addClass('active').fadeIn(150);
                    } else if (formType === 'forgot') {
                        $('#myavanaForgotTab').addClass('active').show();
                        $('#myavanaSigninTab').hide();
                        $('#myavanaSignupTab').hide();
                        $('#myavanaForgotForm').addClass('active').fadeIn(150);
                    }
                });
            }
            
            // Handle form submission
            function handleFormSubmit(form, action) {
                const formData = new FormData(form);
                formData.append('action', 'myavana_auth_submit');
                formData.append('auth_action', action);
                formData.append('nonce', myavana_auth_ajax.nonce);
                
                const submitBtn = form.querySelector('.myavana-submit-btn');
                const errorDiv = form.querySelector('.myavana-error-message');
                const successDiv = form.querySelector('.myavana-success-message');

                submitBtn.disabled = true;
                $(submitBtn).addClass('loading');

                // Reset previous messages with fade out
                $(errorDiv).fadeOut();
                $(successDiv).fadeOut();
                
                $.ajax({
                    url: myavana_auth_ajax.ajax_url,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            $(successDiv).html(response.data.message).fadeIn();

                            if (action === 'signin' || action === 'signup') {
                                // For login/signup, reload after showing success
                                setTimeout(function() {
                                    window.location.reload();
                                }, 1500);
                            } else if (action === 'forgot') {
                                // For password reset, show success and offer to return to login
                                setTimeout(function() {
                                    $(successDiv).html(response.data.message + '<br><br><a href=\"#\" id=\"backToSigninFromSuccess\" style=\"color: #e7a690; text-decoration: underline; font-weight: 500;\">← Return to Sign In</a>');
                                    $('#backToSigninFromSuccess').on('click', function(e) {
                                        e.preventDefault();
                                        switchForm('signin');
                                        $(successDiv).fadeOut();
                                        $(errorDiv).fadeOut();
                                    });
                                }, 2000);
                            }
                        } else {
                            $(errorDiv).html(response.data.message).fadeIn();
                        }
                    },
                    error: function() {
                        $(errorDiv).html('⚠️ An error occurred. Please try again.').fadeIn();
                    },
                    complete: function() {
                        submitBtn.disabled = false;
                        $(submitBtn).removeClass('loading');
                    }
                });
            }
            
            // Initialize modal on page load
            $(document).ready(function() {
                // Show modal automatically
                showModal();
                
                // Event listeners
                $('#myavanaCloseBtn').on('click', closeModal);
                $('#myavanaSigninTab').on('click', function() { switchForm('signin'); });
                $('#myavanaSignupTab').on('click', function() { switchForm('signup'); });
                $('#myavanaForgotLink').on('click', function(e) {
                    e.preventDefault();
                    switchForm('forgot');
                });
                $('#myavanaBackToSignin').on('click', function(e) {
                    e.preventDefault();
                    switchForm('signin');
                });
                
                // Close modal when clicking outside
                $('#myavanaAuthModal').on('click', function(e) {
                    if (e.target === this) {
                        closeModal();
                    }
                });
                
                // Form submissions
                $('#myavanaSigninForm').on('submit', function(e) {
                    e.preventDefault();
                    handleFormSubmit(this, 'signin');
                });
                
                $('#myavanaSignupForm').on('submit', function(e) {
                    e.preventDefault();
                    handleFormSubmit(this, 'signup');
                });

                $('#myavanaForgotForm').on('submit', function(e) {
                    e.preventDefault();
                    handleFormSubmit(this, 'forgot');
                });
                
                // Close modal with Escape key
                $(document).on('keydown', function(e) {
                    if (e.key === 'Escape' && modalShown) {
                        closeModal();
                    }
                });
            });
            
            // Global function to show modal from menu clicks
            window.showMyavanaModal = function(tab) {
                if (myavana_auth_ajax.show_once) {
                    sessionStorage.removeItem('myavana_modal_shown');
                }
                $('#myavanaAuthModal').addClass('show');
                switchForm(tab);
                modalShown = true;
            };
            
        })(jQuery);
        </script>
        <?php
    }
    
    public function handle_auth_submit() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'myavana_auth_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            wp_die();
        }

        // Get action type (signin or signup)
        $action = isset($_POST['auth_action']) ? sanitize_text_field($_POST['auth_action']) : '';

        if ($action === 'signin') {
            // Handle sign-in
            $login = isset($_POST['email']) ? sanitize_text_field($_POST['email']) : '';
            $password = isset($_POST['password']) ? $_POST['password'] : ''; // Passwords shouldn't be sanitized
            $remember = isset($_POST['remember']) ? true : false;

            if (empty($login) || empty($password)) {
                wp_send_json_error(array('message' => 'Please fill in all required fields'));
                wp_die();
            }

            // Determine if the input is an email or username
            if (is_email($login)) {
                // It's an email, authenticate with email
                $user = wp_authenticate($login, $password);
            } else {
                // It's a username, authenticate with username
                $user = wp_authenticate($login, $password);
            }

            if (is_wp_error($user)) {
                wp_send_json_error(array('message' => 'Invalid email/username or password'));
                wp_die();
            }

            // Log the user in
            wp_set_current_user($user->ID);
            wp_set_auth_cookie($user->ID, $remember);

            wp_send_json_success(array('message' => 'Successfully signed in!'));
            wp_die();

        } elseif ($action === 'signup') {
            // Handle sign-up
            $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
            $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
            $password = isset($_POST['password']) ? $_POST['password'] : '';
            $terms = isset($_POST['terms']) ? true : false;

            if (empty($name) || empty($email) || empty($password) || !$terms) {
                wp_send_json_error(array('message' => 'Please fill in all required fields and accept the terms'));
                wp_die();
            }

            if (email_exists($email)) {
                wp_send_json_error(array('message' => 'This email is already registered'));
                wp_die();
            }

            // Create new user
            $username = sanitize_user(current(explode('@', $email)), true);
            $username = $this->generate_unique_username($username);

            $user_id = wp_create_user($username, $password, $email);

            if (is_wp_error($user_id)) {
                wp_send_json_error(array('message' => $user_id->get_error_message()));
                wp_die();
            }

            // Update user meta with full name
            update_user_meta($user_id, 'first_name', $name);

            // Set default user role
            wp_update_user(array(
                'ID' => $user_id,
                'role' => 'subscriber'
            ));

            // Log the user in
            wp_set_current_user($user_id);
            wp_set_auth_cookie($user_id, true);

            wp_send_json_success(array('message' => 'Account created successfully!'));
            wp_die();

        } elseif ($action === 'forgot') {
            // Handle password reset
            $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';

            if (empty($email)) {
                wp_send_json_error(array('message' => '📧 Please enter your email address'));
                wp_die();
            }

            if (!is_email($email)) {
                wp_send_json_error(array('message' => '⚠️ Please enter a valid email address'));
                wp_die();
            }

            if (!email_exists($email)) {
                wp_send_json_error(array('message' => '❌ No account found with this email address. Please check your email or create a new account.'));
                wp_die();
            }

            // Generate password reset key
            $user = get_user_by('email', $email);
            if (!$user) {
                wp_send_json_error(array('message' => 'No account found with this email address'));
                wp_die();
            }

            // Use WordPress built-in password reset functionality
            $key = get_password_reset_key($user);
            if (is_wp_error($key)) {
                wp_send_json_error(array('message' => $key->get_error_message()));
                wp_die();
            }

            // Send password reset email
            $reset_sent = $this->send_password_reset_email($user, $key);
            if ($reset_sent) {
                wp_send_json_success(array('message' => '✅ Password reset link sent successfully!<br><small>Check your email inbox (and spam folder) for the reset link. The link will expire in 24 hours.</small>'));
            } else {
                wp_send_json_error(array('message' => '❌ Failed to send reset email. Please try again or contact support if the problem persists.'));
            }
            wp_die();
        }

        wp_send_json_error(array('message' => 'Invalid action'));
        wp_die();
    }

    // Helper function to generate unique username
    private function generate_unique_username($username) {
        $original_username = $username;
        $counter = 1;

        while (username_exists($username)) {
            $username = $original_username . $counter;
            $counter++;
        }

        return $username;
    }

    /**
     * Send password reset email to user
     */
    private function send_password_reset_email($user, $key) {
        if (!function_exists('myavana_render_auth_required')) {
            function myavana_render_auth_required($context = 'feature') {
                return '<p style="color: #e7a690; text-align: center; margin: 20px;">Please login to access this feature.</p>';
            }
        }

        $site_name = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
        $site_url = home_url();

        // Log email attempt for debugging
        error_log('Myavana: Attempting to send password reset email to ' . $user->user_email);

        // Create reset URL - use WordPress default reset page
        $reset_url = network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user->user_login), 'login');

        // Log the reset URL for debugging
        error_log('Myavana: Reset URL generated: ' . $reset_url);

        // Get proper from email
        $from_email = get_option('admin_email');
        if (empty($from_email)) {
            $from_email = 'noreply@' . wp_parse_url(home_url(), PHP_URL_HOST);
        }

        // Email subject
        $subject = sprintf('[%s] Reset Your Password', $site_name);

        // Set up headers - fixed format
        $headers = array();
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        $headers[] = 'From: ' . $site_name . ' <' . $from_email . '>';
        $headers[] = 'Reply-To: ' . $from_email;

        // Create simplified HTML email template
        $html_message = '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Password Reset - ' . esc_html($site_name) . '</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 20px; background: #f5f5f7; }
        .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #e7a690 0%, #fce5d7 100%); color: white; padding: 30px 20px; text-align: center; }
        .logo { font-size: 28px; font-weight: bold; margin-bottom: 10px; text-transform: uppercase; }
        .content { padding: 30px 20px; }
        .button { display: inline-block; background: #e7a690; color: white !important; padding: 15px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; font-weight: bold; text-transform: uppercase; }
        .button:hover { background: #d4956f; }
        .footer { background: #f5f5f7; padding: 20px; text-align: center; color: #666; font-size: 12px; }
        .url-box { background: #f8f9fa; padding: 15px; border-left: 4px solid #e7a690; margin: 15px 0; word-break: break-all; font-family: monospace; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">Myavana</div>
            <h2 style="margin:0; font-size: 20px;">Password Reset Request</h2>
        </div>
        <div class="content">
            <p>Hi <strong>' . esc_html($user->display_name ?: $user->user_login) . '</strong>,</p>
            <p>We received a request to reset the password for your account on <strong>' . esc_html($site_name) . '</strong>.</p>
            <p style="text-align: center; margin: 30px 0;">
                <a href="' . esc_url($reset_url) . '" class="button">Reset My Password</a>
            </p>
            <p>If the button doesn\'t work, copy and paste this link into your browser:</p>
            <div class="url-box">' . esc_url($reset_url) . '</div>
            <p><strong>⚠️ Important Security Notes:</strong></p>
            <ul>
                <li>This link will expire in 24 hours</li>
                <li>Only use this link if you requested the password reset</li>
                <li>If you didn\'t request this, ignore this email</li>
            </ul>
            <p>Questions? Contact us at <a href="mailto:' . esc_attr($from_email) . '">' . esc_html($from_email) . '</a></p>
        </div>
        <div class="footer">
            <p>This email was sent from ' . esc_html($site_name) . '<br>
            <a href="' . esc_url($site_url) . '" style="color: #e7a690;">' . esc_html(wp_parse_url($site_url, PHP_URL_HOST)) . '</a></p>
        </div>
    </div>
</body>
</html>';

        // Add email filter for debugging
        add_action('wp_mail_failed', array($this, 'log_email_error'));

        // Send the email
        $sent = wp_mail($user->user_email, $subject, $html_message, $headers);

        // Log result
        if ($sent) {
            error_log('Myavana: Password reset email sent successfully to ' . $user->user_email);
        } else {
            error_log('Myavana: Failed to send password reset email to ' . $user->user_email);
        }

        return $sent;
    }

    public function log_email_error($wp_error) {
        error_log('Myavana Email Error: ' . $wp_error->get_error_message());
    }
}