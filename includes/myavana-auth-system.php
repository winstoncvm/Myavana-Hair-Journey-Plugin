<?php
/**
 * Myavana Authentication & Onboarding System
 *
 * Comprehensive authentication, user registration, and onboarding experience
 * Replaces duplicate auth code from main plugin file
 */

if (!defined('ABSPATH')) {
    exit;
}

class Myavana_Auth_System {

    private $options;
    private $onboarding_steps = [
        'welcome' => 'Welcome & Hair Profile',
        'preferences' => 'Your Hair Preferences',
        'complete' => 'You\'re All Set!'
    ];

    public function __construct() {
        $this->init_hooks();
        $this->load_options();
    }

    private function init_hooks() {
        // Admin hooks
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'admin_init']);

        // Frontend hooks
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_footer', [$this, 'render_auth_modal']);
        add_action('wp_head', [$this, 'add_onboarding_meta']);

        // AJAX handlers
        add_action('wp_ajax_nopriv_myavana_auth_submit', [$this, 'handle_auth_submit']);
        add_action('wp_ajax_myavana_auth_submit', [$this, 'handle_auth_submit']);
        add_action('wp_ajax_myavana_onboarding_step', [$this, 'handle_onboarding_step']);
        add_action('wp_ajax_myavana_skip_onboarding', [$this, 'handle_skip_onboarding']);
        add_action('wp_ajax_myavana_get_onboarding_progress', [$this, 'get_onboarding_progress']);
        add_action('wp_ajax_myavana_reset_onboarding', [$this, 'handle_reset_onboarding']);
        add_action('wp_ajax_myavana_trigger_onboarding', [$this, 'handle_trigger_onboarding']);

        // Password reset AJAX handlers
        add_action('wp_ajax_nopriv_myavana_reset_password', [$this, 'handle_password_reset']);
        add_action('wp_ajax_myavana_reset_password', [$this, 'handle_password_reset']);

        // New onboarding save handler
        add_action('wp_ajax_myavana_save_onboarding', [$this, 'handle_save_onboarding']);

        // Manual rewrite flush handler
        add_action('wp_ajax_myavana_flush_rewrites', [$this, 'handle_flush_rewrites']);

        // Password reset shortcode
        add_shortcode('myavana_password_reset', [$this, 'password_reset_shortcode']);

        // Password reset URL rewrite
        add_action('init', [$this, 'add_password_reset_rewrite']);
        add_filter('query_vars', [$this, 'add_password_reset_query_vars']);
        add_action('template_redirect', [$this, 'handle_password_reset_page']);

        // User registration hooks
        add_action('user_register', [$this, 'on_user_register']);
        add_action('wp_login', [$this, 'on_user_login'], 10, 2);

        // Disable WordPress default user activation
        add_filter('wp_new_user_notification_email_admin', [$this, 'disable_admin_notification'], 10, 3);
        add_filter('wp_new_user_notification_email', [$this, 'customize_user_notification'], 10, 3);

        // Onboarding detection
        add_action('template_redirect', [$this, 'detect_onboarding_needed']);
    }

    // private function load_options() {
    //     $this->options = get_option('myavana_auth_options', [
    //         'show_on_homepage' => true,
    //         'show_site_wide' => true, // Changed to true for testing
    //         'delay_seconds' => 2,
    //         'show_once_per_session' => false, // Changed to false for testing
    //         'auto_user_approval' => true,
    //         'enable_onboarding' => true,
    //         'onboarding_completion_reward' => 50 // points
    //     ]);

    //     // Debug logging
    //     if (defined('WP_DEBUG') && WP_DEBUG) {
    //         error_log('MYAVANA Auth Options: ' . print_r($this->options, true));
    //     }
    // }
    private function load_options() {
        $this->options = get_option('myavana_auth_options', [
            'show_on_homepage' => true,
            'show_site_wide' => true,
            'delay_seconds' => 2,
            'show_once_per_session' => false,
            'auto_user_approval' => true,
            'enable_onboarding' => false, // Change this to false
            'onboarding_completion_reward' => 50
        ]);
    
        // Debug logging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MYAVANA Auth Options: ' . print_r($this->options, true));
        }
    }

    // =========================
    // ADMIN INTERFACE
    // =========================

    public function add_admin_menu() {
        add_submenu_page(
            'options-general.php',
            'MYAVANA Authentication & Onboarding',
            'MYAVANA Auth',
            'manage_options',
            'myavana-auth-system',
            [$this, 'admin_page']
        );
    }

    public function admin_init() {
        register_setting('myavana_auth_group', 'myavana_auth_options');

        // Modal Settings Section
        add_settings_section(
            'myavana_auth_modal_section',
            'Authentication Modal Settings',
            [$this, 'modal_section_callback'],
            'myavana-auth-system'
        );

        // Onboarding Settings Section
        add_settings_section(
            'myavana_auth_onboarding_section',
            'User Onboarding Settings',
            [$this, 'onboarding_section_callback'],
            'myavana-auth-system'
        );

        // User Management Section
        add_settings_section(
            'myavana_auth_user_section',
            'User Management Settings',
            [$this, 'user_section_callback'],
            'myavana-auth-system'
        );

        // Add fields
        $this->add_modal_settings_fields();
        $this->add_onboarding_settings_fields();
        $this->add_user_settings_fields();
    }

    private function add_modal_settings_fields() {
        add_settings_field('show_on_homepage', 'Show on Homepage',
            [$this, 'checkbox_callback'], 'myavana-auth-system', 'myavana_auth_modal_section',
            ['name' => 'show_on_homepage', 'label' => 'Show modal on homepage for guests']);

        add_settings_field('show_site_wide', 'Show Site Wide',
            [$this, 'checkbox_callback'], 'myavana-auth-system', 'myavana_auth_modal_section',
            ['name' => 'show_site_wide', 'label' => 'Show modal on all pages for guests']);

        add_settings_field('delay_seconds', 'Delay (seconds)',
            [$this, 'number_callback'], 'myavana-auth-system', 'myavana_auth_modal_section',
            ['name' => 'delay_seconds', 'label' => 'Delay before showing modal', 'min' => 0, 'max' => 60]);

        add_settings_field('show_once_per_session', 'Show Once Per Session',
            [$this, 'checkbox_callback'], 'myavana-auth-system', 'myavana_auth_modal_section',
            ['name' => 'show_once_per_session', 'label' => 'Only show modal once per session']);
    }

    private function add_onboarding_settings_fields() {
        add_settings_field('enable_onboarding', 'Enable Onboarding',
            [$this, 'checkbox_callback'], 'myavana-auth-system', 'myavana_auth_onboarding_section',
            ['name' => 'enable_onboarding', 'label' => 'Enable guided onboarding for new users']);

        add_settings_field('onboarding_completion_reward', 'Completion Reward Points',
            [$this, 'number_callback'], 'myavana-auth-system', 'myavana_auth_onboarding_section',
            ['name' => 'onboarding_completion_reward', 'label' => 'Points awarded for completing onboarding', 'min' => 0, 'max' => 1000]);
    }

    private function add_user_settings_fields() {
        add_settings_field('auto_user_approval', 'Auto-approve New Users',
            [$this, 'checkbox_callback'], 'myavana-auth-system', 'myavana_auth_user_section',
            ['name' => 'auto_user_approval', 'label' => 'Automatically approve new user registrations']);
    }

    public function modal_section_callback() {
        echo '<p>Configure when and how the authentication modal appears to visitors.</p>';
    }

    public function onboarding_section_callback() {
        echo '<p>Set up the welcome experience for new users joining MYAVANA.</p>';
    }

    public function user_section_callback() {
        echo '<p>Control user registration and approval workflow.</p>';
    }

    public function checkbox_callback($args) {
        $value = isset($this->options[$args['name']]) ? $this->options[$args['name']] : false;
        echo '<input type="checkbox" id="' . $args['name'] . '" name="myavana_auth_options[' . $args['name'] . ']" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label for="' . $args['name'] . '">' . $args['label'] . '</label>';
    }

    public function number_callback($args) {
        $value = isset($this->options[$args['name']]) ? $this->options[$args['name']] : ($args['name'] === 'delay_seconds' ? 2 : 50);
        $min = isset($args['min']) ? $args['min'] : 0;
        $max = isset($args['max']) ? $args['max'] : 100;
        echo '<input type="number" id="' . $args['name'] . '" name="myavana_auth_options[' . $args['name'] . ']" value="' . $value . '" min="' . $min . '" max="' . $max . '" />';
        echo '<label for="' . $args['name'] . '">' . $args['label'] . '</label>';
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>🚀 MYAVANA Authentication & Onboarding System</h1>

            <div style="background: white; padding: 20px; margin: 20px 0; border-left: 4px solid #e7a690;">
                <h3>🎯 System Status</h3>
                <p><strong>Total Registered Users:</strong> <?php echo count_users()['total_users']; ?></p>
                <p><strong>Users Completed Onboarding:</strong> <?php echo $this->get_onboarding_completion_stats(); ?>%</p>
                <p><strong>Recent Registrations:</strong> <?php echo $this->get_recent_registrations_count(); ?> (last 7 days)</p>
            </div>

            <form method="post" action="options.php">
                <?php
                settings_fields('myavana_auth_group');
                do_settings_sections('myavana-auth-system');
                submit_button('💾 Save Settings');
                ?>
            </form>

            <div style="background: #f5f5f7; padding: 20px; margin: 20px 0; border-radius: 8px;">
                <h3>🔧 Quick Actions</h3>
                <button type="button" class="button button-secondary" onclick="myavanaTestModal()">🧪 Test Auth Modal</button>
                <button type="button" class="button button-secondary" onclick="myavanaResetOnboarding()">🔄 Reset My Onboarding</button>
                <button type="button" class="button button-primary" onclick="myavanaExportUsers()">📊 Export User Data</button>
                <button type="button" class="button button-secondary" onclick="myavanaFlushRewrites()">🔄 Fix Password Reset (Flush Rewrites)</button>
            </div>
        </div>

        <script>
        function myavanaTestModal() {
            if (typeof window.showMyavanaModal === 'function') {
                window.showMyavanaModal('signin');
            } else {
                alert('Modal not available on admin pages. Test on frontend.');
            }
        }

        function myavanaResetOnboarding() {
            if (confirm('Reset your onboarding progress? This will restart your welcome experience.')) {
                fetch(ajaxurl, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=myavana_skip_onboarding&reset=true&nonce=' + '<?php echo wp_create_nonce('myavana_onboarding'); ?>'
                }).then(() => location.reload());
            }
        }

        function myavanaExportUsers() {
            window.open(ajaxurl + '?action=myavana_export_users&nonce=' + '<?php echo wp_create_nonce('myavana_export'); ?>');
        }

        function myavanaFlushRewrites() {
            if (confirm('This will flush WordPress rewrite rules to fix the password reset 404 error. Continue?')) {
                fetch(ajaxurl, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=myavana_flush_rewrites&nonce=' + '<?php echo wp_create_nonce('myavana_flush_rewrites'); ?>'
                }).then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('✅ Rewrite rules flushed successfully! Password reset should now work at /password-reset/');
                    } else {
                        alert('❌ Error: ' + data.data.message);
                    }
                });
            }
        }
        </script>
        <?php
    }

    // =========================
    // AUTHENTICATION HANDLING
    // =========================

    public function handle_auth_submit() {
        if (!wp_verify_nonce($_POST['nonce'], 'myavana_auth_nonce')) {
            wp_send_json_error(['message' => '🔒 Security check failed']);
        }

        $action = sanitize_text_field($_POST['auth_action'] ?? '');

        switch ($action) {
            case 'signin':
                $this->handle_signin();
                break;
            case 'signup':
                $this->handle_signup();
                break;
            case 'forgot':
                $this->handle_forgot_password();
                break;
            default:
                wp_send_json_error(['message' => '❌ Invalid action']);
        }
    }

    private function handle_signin() {
        $login = sanitize_text_field($_POST['login'] ?? '');
        $password = wp_unslash($_POST['password'] ?? '');
        $remember = isset($_POST['remember']);

        // Rate limiting check
        $rate_limit_check = $this->check_login_rate_limit($login);
        if ($rate_limit_check !== true) {
            wp_send_json_error($rate_limit_check);
        }

        if (empty($login)) {
            wp_send_json_error(['message' => 'Please enter your email or username.', 'field' => 'login']);
        }

        if (empty($password)) {
            wp_send_json_error(['message' => 'Please enter your password.', 'field' => 'password']);
        }

        // Check if user exists first
        $user_exists = is_email($login) ? get_user_by('email', $login) : get_user_by('login', $login);

        if (!$user_exists) {
            wp_send_json_error([
                'message' => 'No account found with this email/username. Would you like to create one?',
                'field' => 'login',
                'show_signup' => true
            ]);
        }

        $user = wp_authenticate($login, $password);

        if (is_wp_error($user)) {
            // Record failed attempt
            $this->record_failed_login_attempt($login);

            $error_code = $user->get_error_code();
            $attempts_remaining = $this->get_remaining_attempts($login);
            $attempts_msg = $attempts_remaining > 0 ? " ({$attempts_remaining} attempts remaining)" : "";

            if ($error_code === 'incorrect_password') {
                wp_send_json_error([
                    'message' => "Incorrect password. Please try again or reset your password.{$attempts_msg}",
                    'field' => 'password',
                    'show_forgot' => true,
                    'attempts_remaining' => $attempts_remaining
                ]);
            } elseif ($error_code === 'invalid_email') {
                wp_send_json_error([
                    'message' => 'Invalid email format. Please check and try again.',
                    'field' => 'login'
                ]);
            } else {
                wp_send_json_error([
                    'message' => "Unable to sign in. Please check your credentials.{$attempts_msg}",
                    'attempts_remaining' => $attempts_remaining
                ]);
            }
        }

        // Clear failed attempts on successful login
        $this->clear_failed_login_attempts($login);

        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID, $remember);

        // Track login
        $this->track_user_event($user->ID, 'login');

        $display_name = $user->display_name ?: $user->user_login;
        wp_send_json_success(['message' => "Welcome back, {$display_name}!"]);
    }

    private function handle_signup() {
        $name = sanitize_text_field($_POST['name'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');
        $password = wp_unslash($_POST['password'] ?? '');
        $terms = isset($_POST['terms']);

        // Specific field validation
        if (empty($name)) {
            wp_send_json_error(['message' => 'Please enter your name.', 'field' => 'name']);
        }

        if (strlen($name) < 2) {
            wp_send_json_error(['message' => 'Name must be at least 2 characters.', 'field' => 'name']);
        }

        if (empty($email)) {
            wp_send_json_error(['message' => 'Please enter your email address.', 'field' => 'email']);
        }

        if (!is_email($email)) {
            wp_send_json_error(['message' => 'Please enter a valid email address.', 'field' => 'email']);
        }

        if (empty($password)) {
            wp_send_json_error(['message' => 'Please create a password.', 'field' => 'password']);
        }

        if (strlen($password) < 8) {
            wp_send_json_error(['message' => 'Password must be at least 8 characters long.', 'field' => 'password']);
        }

        if (!$terms) {
            wp_send_json_error(['message' => 'Please accept the Terms of Service and Privacy Policy to continue.', 'field' => 'terms']);
        }

        if (email_exists($email)) {
            wp_send_json_error([
                'message' => 'This email is already registered. Please sign in or use a different email.',
                'field' => 'email',
                'show_signin' => true
            ]);
        }

        // Create user
        $username = $this->generate_unique_username($email);
        $user_id = wp_create_user($username, $password, $email);

        if (is_wp_error($user_id)) {
            $error_msg = $user_id->get_error_message();
            // Make WordPress errors more user-friendly
            if (strpos($error_msg, 'username') !== false) {
                $error_msg = 'There was an issue creating your account. Please try again.';
            }
            wp_send_json_error(['message' => $error_msg]);
        }

        // Set user meta
        update_user_meta($user_id, 'first_name', $name);
        update_user_meta($user_id, 'display_name', $name);
        update_user_meta($user_id, 'myavana_signup_date', current_time('mysql'));
        update_user_meta($user_id, 'myavana_onboarding_status', 'pending');

        // Update display name
        wp_update_user(['ID' => $user_id, 'display_name' => $name]);

        // Auto-approve if enabled
        if ($this->options['auto_user_approval']) {
            wp_update_user(['ID' => $user_id, 'role' => 'subscriber']);
        } else {
            wp_update_user(['ID' => $user_id, 'role' => 'pending']);
        }

        // Log them in
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id, true);

        // Track registration
        $this->track_user_event($user_id, 'registration');

        // Get first name for personalized message
        $first_name = explode(' ', $name)[0];

        wp_send_json_success([
            'message' => "Welcome to MYAVANA, {$first_name}! Let's get your hair journey started.",
            'user_id' => $user_id,
            'trigger_onboarding' => true
        ]);
    }

    private function handle_forgot_password() {
        $email = sanitize_email($_POST['email'] ?? '');

        if (empty($email)) {
            wp_send_json_error(['message' => 'Please enter your email address.', 'field' => 'email']);
        }

        if (!is_email($email)) {
            wp_send_json_error(['message' => 'Please enter a valid email address.', 'field' => 'email']);
        }

        if (!email_exists($email)) {
            wp_send_json_error([
                'message' => 'No account found with this email address. Please check the spelling or create a new account.',
                'field' => 'email',
                'show_signup' => true
            ]);
        }

        $user = get_user_by('email', $email);
        $key = get_password_reset_key($user);

        if (is_wp_error($key)) {
            $error_code = $key->get_error_code();
            if ($error_code === 'no_password_reset') {
                wp_send_json_error(['message' => 'Password reset is not allowed for this account.']);
            } else {
                wp_send_json_error(['message' => 'Unable to generate reset link. Please try again later.']);
            }
        }

        $sent = $this->send_password_reset_email($user, $key);

        if ($sent) {
            wp_send_json_success([
                'message' => 'Password reset link sent! Please check your email inbox (and spam folder). The link expires in 24 hours.'
            ]);
        } else {
            wp_send_json_error(['message' => 'Unable to send reset email. Please try again or contact support.']);
        }
    }

    // =========================
    // ONBOARDING SYSTEM
    // =========================

    public function detect_onboarding_needed() {
        if (!is_user_logged_in() || !$this->options['enable_onboarding']) {
            return;
        }

        $user_id = get_current_user_id();
        $onboarding_status = get_user_meta($user_id, 'myavana_onboarding_status', true);

        if ($onboarding_status === 'pending' && !$this->is_onboarding_page()) {
            $this->start_onboarding_experience();
        }
    }

    private function is_onboarding_page() {
        global $wp;
        return strpos($wp->request, 'myavana-onboarding') !== false;
    }

    private function start_onboarding_experience() {
        // Add onboarding overlay to current page
        add_action('wp_footer', [$this, 'render_onboarding_overlay']);
    }

    public function handle_onboarding_step() {
        if (!is_user_logged_in() || !wp_verify_nonce($_POST['nonce'], 'myavana_onboarding')) {
            wp_send_json_error(['message' => 'Security check failed']);
        }

        $user_id = get_current_user_id();
        $step = sanitize_text_field($_POST['step'] ?? '');
        $data = $_POST['data'] ?? [];

        $progress = $this->process_onboarding_step($user_id, $step, $data);

        wp_send_json_success([
            'message' => 'Step completed!',
            'progress' => $progress,
            'next_step' => $this->get_next_onboarding_step($progress)
        ]);
    }

    public function handle_skip_onboarding() {
        if (!is_user_logged_in() || !wp_verify_nonce($_POST['nonce'], 'myavana_onboarding')) {
            wp_send_json_error(['message' => 'Security check failed']);
        }

        $user_id = get_current_user_id();

        if (isset($_POST['reset']) && $_POST['reset'] === 'true') {
            update_user_meta($user_id, 'myavana_onboarding_status', 'pending');
            delete_user_meta($user_id, 'myavana_onboarding_progress');
        } else {
            update_user_meta($user_id, 'myavana_onboarding_status', 'skipped');
        }

        wp_send_json_success(['message' => 'Onboarding updated']);
    }

    public function get_onboarding_progress() {
        if (!is_user_logged_in() || !wp_verify_nonce($_POST['nonce'], 'myavana_onboarding')) {
            wp_send_json_error(['message' => 'Security check failed']);
        }

        $user_id = get_current_user_id();
        $progress = get_user_meta($user_id, 'myavana_onboarding_progress', true) ?: [];
        $status = get_user_meta($user_id, 'myavana_onboarding_status', true);

        wp_send_json_success([
            'progress' => $progress,
            'status' => $status,
            'steps' => $this->onboarding_steps
        ]);
    }

    public function handle_reset_onboarding() {
        if (!is_user_logged_in() || !wp_verify_nonce($_POST['nonce'], 'myavana_onboarding')) {
            wp_send_json_error(['message' => 'Security check failed']);
        }

        $user_id = get_current_user_id();

        // Reset onboarding status and progress
        update_user_meta($user_id, 'myavana_onboarding_status', 'pending');
        delete_user_meta($user_id, 'myavana_onboarding_progress');
        update_user_meta($user_id, 'myavana_show_onboarding', true);

        wp_send_json_success(['message' => 'Onboarding status reset successfully']);
    }

    public function handle_trigger_onboarding() {
        if (!is_user_logged_in() || !wp_verify_nonce($_POST['nonce'], 'myavana_onboarding')) {
            wp_send_json_error(['message' => 'Security check failed']);
        }

        $user_id = get_current_user_id();

        // Set onboarding to show immediately
        update_user_meta($user_id, 'myavana_show_onboarding', true);
        update_user_meta($user_id, 'myavana_onboarding_status', 'pending');

        wp_send_json_success(['message' => 'Onboarding triggered successfully']);
    }

    // =========================
    // USER MANAGEMENT
    // =========================

    public function on_user_register($user_id) {
        // Create default MYAVANA profile
        global $wpdb;

        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}myavana_profiles WHERE user_id = %d",
            $user_id
        ));

        if (!$exists) {
            $wpdb->insert(
                $wpdb->prefix . 'myavana_profiles',
                [
                    'user_id' => $user_id,
                    'hair_journey_stage' => 'Getting Started',
                    'hair_health_rating' => 5,
                    'life_journey_stage' => 'Exploring',
                    'hair_type' => '',
                    'hair_goals' => 'Healthy, beautiful hair'
                ]
            );
        }

        // Set onboarding status
        update_user_meta($user_id, 'myavana_onboarding_status', 'pending');
        update_user_meta($user_id, 'myavana_registration_source', 'myavana_modal');

        // Track registration
        $this->track_user_event($user_id, 'registration');
    }

    public function on_user_login($user_login, $user) {
        $this->track_user_event($user->ID, 'login');

        // Check if onboarding is needed
        $onboarding_status = get_user_meta($user->ID, 'myavana_onboarding_status', true);
        if ($onboarding_status === 'pending' && $this->options['enable_onboarding']) {
            update_user_meta($user->ID, 'myavana_show_onboarding', true);
        }
    }

    // =========================
    // FRONTEND ASSETS & RENDERING
    // =========================

    public function enqueue_scripts() {
        // Always enqueue for logged in users (for onboarding)
        if (is_user_logged_in()) {
            $this->enqueue_onboarding_assets();
        }

        // Always enqueue auth scripts for non-logged-in users
        if (!is_user_logged_in()) {
            wp_enqueue_script('myavana-auth-system', MYAVANA_URL . 'assets/js/myavana-auth-system.js', ['jquery'], '1.0.1', true);

            wp_localize_script('myavana-auth-system', 'myavanaAuth', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('myavana_auth_nonce'),
                'delay' => $this->options['delay_seconds'] * 1000,
                'show_once' => $this->options['show_once_per_session'],
                'onboarding_enabled' => $this->options['enable_onboarding'],
                'is_logged_in' => is_user_logged_in(),
                'debug' => defined('WP_DEBUG') && WP_DEBUG,
                'show_site_wide' => $this->options['show_site_wide'],
                'show_on_homepage' => $this->options['show_on_homepage']
            ]);
        }
    }

    private function enqueue_onboarding_assets() {
        $user_id = get_current_user_id();
        $show_onboarding = get_user_meta($user_id, 'myavana_show_onboarding', true);

        if (!$show_onboarding) {
            return;
        }

        wp_enqueue_script('myavana-onboarding', MYAVANA_URL . 'assets/js/myavana-onboarding.js', ['jquery'], '1.0.0', true);

        wp_localize_script('myavana-onboarding', 'myavanaOnboarding', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('myavana_onboarding'),
            'steps' => $this->onboarding_steps,
            'user_id' => $user_id
        ]);
    }

    public function add_onboarding_meta() {
        if (!is_user_logged_in()) {
            return;
        }

        $user_id = get_current_user_id();
        $show_onboarding = get_user_meta($user_id, 'myavana_show_onboarding', true);

        if ($show_onboarding) {
            echo '<meta name="myavana-onboarding" content="true">' . "\n";
        }
    }

    // =========================
    // HELPER METHODS
    // =========================

    private function should_show_auth_modal() {
        if ($this->options['show_site_wide']) {
            return true;
        }

        if ($this->options['show_on_homepage'] && is_home()) {
            return true;
        }

        return false;
    }

    private function generate_unique_username($email) {
        $base_username = sanitize_user(current(explode('@', $email)), true);
        $username = $base_username;
        $counter = 1;

        while (username_exists($username)) {
            $username = $base_username . $counter;
            $counter++;
        }

        return $username;
    }

    private function track_user_event($user_id, $event) {
        // Integration with analytics system if available
        if (function_exists('myavana_track_event')) {
            myavana_track_event($event, [
                'user_id' => $user_id,
                'timestamp' => current_time('mysql')
            ]);
        }

        // Log for debugging
        error_log("MYAVANA: User {$user_id} - {$event}");
    }

    private function send_password_reset_email($user, $key) {
        $site_name = get_bloginfo('name');
        // Use custom branded reset URL instead of wp-login.php
        $reset_url = $this->get_password_reset_url($key, $user->user_login);

        $subject = "🔑 Reset Your MYAVANA Password";
        $message = $this->get_password_reset_email_template($user, $reset_url, $site_name);

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $site_name . ' <noreply@' . wp_parse_url(home_url(), PHP_URL_HOST) . '>'
        ];

        return wp_mail($user->user_email, $subject, $message, $headers);
    }

    private function get_password_reset_email_template($user, $reset_url, $site_name) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>Reset Your Password - <?php echo esc_html($site_name); ?></title>
            <style>
                body { font-family: 'Archivo', Arial, sans-serif; background: #f5f5f7; margin: 0; padding: 20px; }
                .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
                .header { background: linear-gradient(135deg, #e7a690 0%, #fce5d7 100%); padding: 40px 20px; text-align: center; color: white; }
                .logo { font-size: 28px; font-weight: 900; text-transform: uppercase; margin-bottom: 10px; }
                .content { padding: 40px 30px; }
                .button { display: inline-block; background: #e7a690; color: white !important; padding: 16px 32px; text-decoration: none; border-radius: 8px; font-weight: 600; text-transform: uppercase; margin: 20px 0; }
                .footer { background: #f5f5f7; padding: 20px; text-align: center; color: #666; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <div class="logo">MYAVANA</div>
                    <p style="margin: 0; font-size: 18px;">Password Reset Request</p>
                </div>
                <div class="content">
                    <p>Hi <strong><?php echo esc_html($user->display_name ?: $user->user_login); ?></strong>,</p>
                    <p>Someone requested a password reset for your MYAVANA account. If this was you, click the button below to reset your password:</p>
                    <p style="text-align: center;">
                        <a href="<?php echo esc_url($reset_url); ?>" class="button">Reset My Password</a>
                    </p>
                    <p><strong>🔒 Security Notes:</strong></p>
                    <ul>
                        <li>This link expires in 24 hours</li>
                        <li>If you didn't request this, ignore this email</li>
                        <li>Your password remains secure until you use this link</li>
                    </ul>
                </div>
                <div class="footer">
                    <p>This email was sent from MYAVANA Hair Journey</p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    private function get_onboarding_completion_stats() {
        global $wpdb;

        $total = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users}");
        $completed = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key = 'myavana_onboarding_status' AND meta_value = 'completed'");

        return $total > 0 ? round(($completed / $total) * 100) : 0;
    }

    private function get_recent_registrations_count() {
        global $wpdb;

        $seven_days_ago = date('Y-m-d H:i:s', strtotime('-7 days'));
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->users} WHERE user_registered >= %s",
            $seven_days_ago
        ));
    }

    // Disable WordPress default user notifications
    public function disable_admin_notification($email, $user, $blogname) {
        return null; // Disable admin notification
    }

    public function customize_user_notification($email, $user, $blogname) {
        // Send custom welcome email instead
        $this->send_welcome_email($user);
        return $email;
    }

    private function send_welcome_email($user) {
        $subject = "🌟 Welcome to MYAVANA - Your Hair Journey Starts Now!";
        $message = $this->get_welcome_email_template($user);

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: MYAVANA <noreply@' . wp_parse_url(home_url(), PHP_URL_HOST) . '>'
        ];

        wp_mail($user->user_email, $subject, $message, $headers);
    }

    private function get_welcome_email_template($user) {
        $dashboard_url = home_url('/dashboard'); // Adjust based on your setup

        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>Welcome to MYAVANA</title>
            <style>
                body { font-family: 'Archivo', Arial, sans-serif; background: #f5f5f7; margin: 0; padding: 20px; }
                .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
                .header { background: linear-gradient(135deg, #e7a690 0%, #fce5d7 100%); padding: 40px 20px; text-align: center; color: white; }
                .logo { font-size: 32px; font-weight: 900; text-transform: uppercase; margin-bottom: 10px; }
                .content { padding: 40px 30px; }
                .button { display: inline-block; background: #e7a690; color: white !important; padding: 16px 32px; text-decoration: none; border-radius: 8px; font-weight: 600; text-transform: uppercase; margin: 20px 0; }
                .features { background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; }
                .feature { display: flex; align-items: center; margin: 10px 0; }
                .feature-icon { margin-right: 12px; font-size: 18px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <div class="logo">MYAVANA</div>
                    <h2 style="margin: 0; font-size: 24px;">Welcome to Your Hair Journey!</h2>
                </div>
                <div class="content">
                    <p>Hi <strong><?php echo esc_html($user->display_name ?: $user->user_login); ?></strong>,</p>
                    <p>🎉 Welcome to MYAVANA! We're thrilled you've joined our community of hair care enthusiasts.</p>

                    <div class="features">
                        <h3 style="margin-top: 0; color: #e7a690;">What You Can Do:</h3>
                        <div class="feature">
                            <span class="feature-icon">🤖</span>
                            <span>Get AI-powered hair analysis and personalized recommendations</span>
                        </div>
                        <div class="feature">
                            <span class="feature-icon">📸</span>
                            <span>Track your progress with photos and timeline entries</span>
                        </div>
                        <div class="feature">
                            <span class="feature-icon">👥</span>
                            <span>Connect with a supportive community</span>
                        </div>
                        <div class="feature">
                            <span class="feature-icon">🎯</span>
                            <span>Set and achieve your hair goals</span>
                        </div>
                    </div>

                    <p style="text-align: center;">
                        <a href="<?php echo esc_url($dashboard_url); ?>" class="button">Start Your Journey</a>
                    </p>

                    <p><small>💡 <strong>Pro Tip:</strong> Complete your profile setup to get the most personalized experience!</small></p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    // Render methods will be added in separate files for better organization
    public function render_auth_modal() {
        // Debug logging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MYAVANA: render_auth_modal called. User logged in: ' . (is_user_logged_in() ? 'yes' : 'no'));
        }

        // Always render for non-logged-in users
        if (is_user_logged_in()) {
            return;
        }

        // Check if template exists
        $template_path = MYAVANA_DIR . 'templates/auth/auth-modal.php';
        if (!file_exists($template_path)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('MYAVANA: Auth modal template not found at: ' . $template_path);
            }
            return;
        }

        // Include the auth modal template
        include $template_path;
    }

    public function render_onboarding_overlay() {
        if (!is_user_logged_in()) {
            return;
        }

        $user_id = get_current_user_id();
        $onboarding_status = get_user_meta($user_id, 'myavana_onboarding_status', true);

        // Only show if status is pending AND show_onboarding flag is set
        if ($onboarding_status !== 'pending') {
            return;
        }

        // Include the NEW streamlined onboarding template
        $template_path = MYAVANA_DIR . 'templates/auth/onboarding-modal.php';
        if (file_exists($template_path)) {
            include $template_path;
        }
    }

    private function process_onboarding_step($user_id, $step, $data) {
        $progress = get_user_meta($user_id, 'myavana_onboarding_progress', true) ?: [];

        // Process step-specific data (Simplified 3-step flow)
        switch ($step) {
            case 'welcome':
                // Step 1: Welcome + Hair Type + Primary Goal
                if (isset($data['hair_type'])) {
                    update_user_meta($user_id, 'myavana_hair_type', sanitize_text_field($data['hair_type']));
                }
                if (isset($data['primary_goal'])) {
                    update_user_meta($user_id, 'myavana_primary_goal', sanitize_text_field($data['primary_goal']));
                }
                $progress['welcome'] = true;
                break;

            case 'preferences':
                // Step 2: Additional profile preferences
                if (isset($data['hair_length'])) {
                    update_user_meta($user_id, 'myavana_hair_length', sanitize_text_field($data['hair_length']));
                }
                if (isset($data['hair_concern'])) {
                    update_user_meta($user_id, 'myavana_hair_concern', sanitize_text_field($data['hair_concern']));
                }
                if (isset($data['name'])) {
                    update_user_meta($user_id, 'first_name', sanitize_text_field($data['name']));
                }
                $progress['preferences'] = true;
                break;

            case 'complete':
                // Step 3: Completion - Award points and mark as complete
                update_user_meta($user_id, 'myavana_onboarding_status', 'completed');
                update_user_meta($user_id, 'myavana_onboarding_completed_date', current_time('mysql'));
                $progress['complete'] = true;

                // Award completion points
                $this->award_onboarding_points($user_id);
                break;
        }

        update_user_meta($user_id, 'myavana_onboarding_progress', $progress);
        return $progress;
    }

    private function get_next_onboarding_step($progress) {
        $steps = array_keys($this->onboarding_steps);

        foreach ($steps as $step) {
            if (!isset($progress[$step]) || !$progress[$step]) {
                return $step;
            }
        }

        return 'complete';
    }

    private function award_onboarding_points($user_id) {
        $points = $this->options['onboarding_completion_reward'];

        // Integration with gamification system if available
        if (function_exists('myavana_award_points')) {
            myavana_award_points($user_id, $points, 'onboarding_completion');
        } else {
            // Store points in user meta as fallback
            $current_points = get_user_meta($user_id, 'myavana_points', true) ?: 0;
            update_user_meta($user_id, 'myavana_points', $current_points + $points);
        }
    }

    /**
     * Handle new streamlined onboarding save
     */
    public function handle_save_onboarding() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'myavana_onboarding_save')) {
            wp_send_json_error(['message' => 'Security check failed']);
        }

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'Please log in first']);
        }

        $user_id = get_current_user_id();

        // Get and sanitize data
        $name = sanitize_text_field($_POST['name'] ?? '');
        $hair_type = sanitize_text_field($_POST['hair_type'] ?? '');
        $hair_goals = sanitize_text_field($_POST['hair_goals'] ?? '');
        $hair_texture = sanitize_text_field($_POST['hair_texture'] ?? '');

        // Validate required fields
        if (empty($name)) {
            wp_send_json_error(['message' => 'Please enter your name']);
        }

        if (empty($hair_type)) {
            wp_send_json_error(['message' => 'Please select your hair type']);
        }

        // Update WordPress user
        wp_update_user([
            'ID' => $user_id,
            'display_name' => $name,
            'first_name' => explode(' ', $name)[0]
        ]);

        // Save to user meta
        update_user_meta($user_id, 'first_name', explode(' ', $name)[0]);
        update_user_meta($user_id, 'myavana_hair_type', $hair_type);
        update_user_meta($user_id, 'myavana_hair_texture', $hair_texture);
        update_user_meta($user_id, 'myavana_hair_goals', $hair_goals);
        update_user_meta($user_id, 'myavana_onboarding_status', 'completed');
        update_user_meta($user_id, 'myavana_onboarding_completed_date', current_time('mysql'));
        delete_user_meta($user_id, 'myavana_show_onboarding');

        // Save to myavana_profiles table
        global $wpdb;
        $table_name = $wpdb->prefix . 'myavana_profiles';

        // Check if profile exists
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table_name} WHERE user_id = %d",
            $user_id
        ));

        $profile_data = [
            'hair_type' => $hair_type,
            'hair_goals' => $hair_goals,
            'hair_journey_stage' => 'Getting Started'
        ];

        if ($existing) {
            // Update existing profile
            $wpdb->update(
                $table_name,
                $profile_data,
                ['user_id' => $user_id]
            );
        } else {
            // Insert new profile
            $profile_data['user_id'] = $user_id;
            $wpdb->insert($table_name, $profile_data);
        }

        // Award onboarding points
        $this->award_onboarding_points($user_id);

        // Track event
        $this->track_user_event($user_id, 'onboarding_completed');

        // Create first hair journey entry
        $first_entry_id = wp_insert_post([
            'post_title' => 'Welcome to My Hair Journey!',
            'post_content' => 'This is my first entry. Excited to start tracking my hair care journey with MYAVANA!',
            'post_type' => 'hair_journey_entry',
            'post_status' => 'publish',
            'post_author' => $user_id,
            'post_date' => current_time('mysql')
        ]);

        if ($first_entry_id && !is_wp_error($first_entry_id)) {
            // Save entry metadata
            update_post_meta($first_entry_id, 'mood_demeanor', 'Excited');
            update_post_meta($first_entry_id, 'health_rating', 5);
            update_post_meta($first_entry_id, 'entry_type', 'onboarding');
            update_post_meta($first_entry_id, 'environment', 'home');

            // Update user meta to track first entry
            update_user_meta($user_id, 'myavana_first_entry_created', true);
            update_user_meta($user_id, 'myavana_first_entry_id', $first_entry_id);
            update_user_meta($user_id, 'myavana_first_entry_date', current_time('mysql'));

            error_log('MYAVANA: Created first entry (ID: ' . $first_entry_id . ') for user ' . $user_id . ' during onboarding');
        } else {
            error_log('MYAVANA: Failed to create first entry for user ' . $user_id . ' during onboarding');
        }

        wp_send_json_success([
            'message' => 'Profile saved successfully!',
            'redirect' => home_url('/hair-journey/?welcome=1')
        ]);
    }

    // =========================
    // RATE LIMITING
    // =========================

    private $max_login_attempts = 5;
    private $lockout_duration = 900; // 15 minutes in seconds
    private $attempt_window = 300; // 5 minutes window for counting attempts

    /**
     * Check if login is rate limited
     */
    private function check_login_rate_limit($login) {
        $ip = $this->get_client_ip();
        $ip_key = 'myavana_login_attempts_ip_' . md5($ip);
        $user_key = 'myavana_login_attempts_user_' . md5($login);

        // Check IP lockout
        $ip_lockout = get_transient('myavana_lockout_ip_' . md5($ip));
        if ($ip_lockout) {
            $remaining = $ip_lockout - time();
            $minutes = ceil($remaining / 60);
            return [
                'message' => "Too many login attempts. Please try again in {$minutes} minute(s).",
                'locked' => true,
                'retry_after' => $remaining
            ];
        }

        // Check user lockout
        if (!empty($login)) {
            $user_lockout = get_transient('myavana_lockout_user_' . md5($login));
            if ($user_lockout) {
                $remaining = $user_lockout - time();
                $minutes = ceil($remaining / 60);
                return [
                    'message' => "This account is temporarily locked due to too many failed attempts. Please try again in {$minutes} minute(s) or reset your password.",
                    'locked' => true,
                    'retry_after' => $remaining,
                    'show_forgot' => true
                ];
            }
        }

        return true;
    }

    /**
     * Record a failed login attempt
     */
    private function record_failed_login_attempt($login) {
        $ip = $this->get_client_ip();
        $ip_key = 'myavana_login_attempts_ip_' . md5($ip);
        $user_key = 'myavana_login_attempts_user_' . md5($login);

        // Increment IP attempts
        $ip_attempts = get_transient($ip_key) ?: 0;
        $ip_attempts++;
        set_transient($ip_key, $ip_attempts, $this->attempt_window);

        // Increment user attempts
        if (!empty($login)) {
            $user_attempts = get_transient($user_key) ?: 0;
            $user_attempts++;
            set_transient($user_key, $user_attempts, $this->attempt_window);

            // Lock user if max attempts reached
            if ($user_attempts >= $this->max_login_attempts) {
                set_transient('myavana_lockout_user_' . md5($login), time() + $this->lockout_duration, $this->lockout_duration);
                delete_transient($user_key);
            }
        }

        // Lock IP if max attempts reached
        if ($ip_attempts >= ($this->max_login_attempts * 2)) {
            set_transient('myavana_lockout_ip_' . md5($ip), time() + $this->lockout_duration, $this->lockout_duration);
            delete_transient($ip_key);
        }
    }

    /**
     * Get remaining login attempts
     */
    private function get_remaining_attempts($login) {
        $user_key = 'myavana_login_attempts_user_' . md5($login);
        $attempts = get_transient($user_key) ?: 0;
        return max(0, $this->max_login_attempts - $attempts);
    }

    /**
     * Clear failed login attempts on successful login
     */
    private function clear_failed_login_attempts($login) {
        $ip = $this->get_client_ip();
        delete_transient('myavana_login_attempts_ip_' . md5($ip));
        delete_transient('myavana_login_attempts_user_' . md5($login));
    }

    /**
     * Get client IP address
     */
    private function get_client_ip() {
        $ip = '';
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        }
        return sanitize_text_field($ip);
    }

    // =========================
    // PASSWORD RESET
    // =========================

    /**
     * Handle manual flush of rewrite rules
     */
    public function handle_flush_rewrites() {
        // Verify nonce and permission
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'myavana_flush_rewrites') || !current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied']);
        }

        // Flush rewrite rules
        flush_rewrite_rules();

        // Update version to force re-flush on next page load
        delete_option('myavana_auth_rewrite_version');

        wp_send_json_success(['message' => 'Rewrite rules flushed successfully!']);
    }

    /**
     * Password reset shortcode handler
     */
    // public function password_reset_shortcode($atts) {
    //     ob_start();
    //     include MYAVANA_DIR . 'templates/auth/password-reset.php';
    //     return ob_get_clean();
    // }
    public function password_reset_shortcode($atts) {
        // Get parameters from URL
        $reset_key = isset($_GET['key']) ? sanitize_text_field($_GET['key']) : '';
        $reset_login = isset($_GET['login']) ? sanitize_text_field($_GET['login']) : '';
        
        // Verify the reset key
        $user = null;
        $error_message = '';
        $key_valid = false;
        
        if (!empty($reset_key) && !empty($reset_login)) {
            $user = check_password_reset_key($reset_key, $reset_login);
            if (is_wp_error($user)) {
                $error_message = $user->get_error_message();
                if (strpos($error_message, 'Invalid key') !== false || strpos($error_message, 'expired') !== false) {
                    $error_message = 'This password reset link has expired or is invalid. Please request a new one.';
                }
            } else {
                $key_valid = true;
            }
        } else {
            $error_message = 'Invalid password reset link. Please request a new one.';
        }
        
        // Set variables for template
        set_query_var('reset_key', $reset_key);
        set_query_var('reset_login', $reset_login);
        set_query_var('key_valid', $key_valid);
        set_query_var('error_message', $error_message);
        set_query_var('user', $user);
        
        ob_start();
        $template_path = MYAVANA_DIR . 'templates/auth/password-reset.php';
        if (file_exists($template_path)) {
            include $template_path;
        }
        return ob_get_clean();
    }

    /**
     * Handle password reset AJAX request
     */
    public function handle_password_reset() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'myavana_reset_password')) {
            wp_send_json_error(['message' => 'Security check failed. Please refresh and try again.']);
        }

        $reset_key = sanitize_text_field($_POST['reset_key'] ?? '');
        $reset_login = sanitize_text_field($_POST['reset_login'] ?? '');
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';

        // Validate inputs
        if (empty($reset_key) || empty($reset_login)) {
            wp_send_json_error(['message' => 'Invalid reset link. Please request a new password reset.']);
        }

        if (empty($password)) {
            wp_send_json_error(['message' => 'Please enter a new password.']);
        }

        if (strlen($password) < 8) {
            wp_send_json_error(['message' => 'Password must be at least 8 characters long.']);
        }

        if ($password !== $password_confirm) {
            wp_send_json_error(['message' => 'Passwords do not match.']);
        }

        // Verify the reset key
        $user = check_password_reset_key($reset_key, $reset_login);

        if (is_wp_error($user)) {
            $error_code = $user->get_error_code();
            if ($error_code === 'expired_key') {
                wp_send_json_error(['message' => 'This reset link has expired. Please request a new one.']);
            } else {
                wp_send_json_error(['message' => 'Invalid reset link. Please request a new password reset.']);
            }
        }

        // Reset the password
        reset_password($user, $password);

        // Track the event
        $this->track_user_event($user->ID, 'password_reset');

        wp_send_json_success([
            'message' => 'Your password has been reset successfully! Redirecting you to sign in...'
        ]);
    }

    /**
     * Get custom password reset URL
     */
    public function get_password_reset_url($key, $login) {
        return add_query_arg([
            'key' => $key,
            'login' => rawurlencode($login)
        ], home_url('/password-reset/'));
    }

    /**
     * Add password reset rewrite rule
     */
    public function add_password_reset_rewrite() {
        add_rewrite_rule(
            '^password-reset/?$',
            'index.php?myavana_password_reset=1',
            'top'
        );

        // Flush rewrite rules on activation (add this to your plugin activation hook)
        // You only need to do this once when the plugin is activated or updated
        $current_version = '2.4.7';
        if (get_option('myavana_auth_rewrite_version') !== $current_version) {
            flush_rewrite_rules();
            update_option('myavana_auth_rewrite_version', $current_version);
            error_log('MYAVANA: Rewrite rules flushed for version ' . $current_version);
        }
    }
    
    /**
     * Add password reset query var
     */
    public function add_password_reset_query_vars($vars) {
        $vars[] = 'myavana_password_reset';
        return $vars;
    }

    /**
     * Handle password reset page display
     */
    
    public function handle_password_reset_page() {
        if (get_query_var('myavana_password_reset')) {
            // Prevent WordPress from trying to find a post
            global $wp_query;
            $wp_query->is_404 = false;
            
            status_header(200);
            
            // Load the template
            $this->load_password_reset_template();
            exit;
        }
    }
    /**
     * Load password reset template with proper WordPress headers/footers
     */
    private function load_password_reset_template() {
        // Get reset parameters
        $reset_key = isset($_GET['key']) ? sanitize_text_field($_GET['key']) : '';
        $reset_login = isset($_GET['login']) ? sanitize_text_field($_GET['login']) : '';
        
        // Verify the reset key
        $user = null;
        $error_message = '';
        $key_valid = false;
        
        if (!empty($reset_key) && !empty($reset_login)) {
            $user = check_password_reset_key($reset_key, $reset_login);
            if (is_wp_error($user)) {
                $error_message = $user->get_error_message();
                if (strpos($error_message, 'Invalid key') !== false || strpos($error_message, 'expired') !== false) {
                    $error_message = 'This password reset link has expired or is invalid. Please request a new one.';
                }
            } else {
                $key_valid = true;
            }
        } else {
            $error_message = 'Invalid password reset link. Please request a new one.';
        }
        
        // Output the page
        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo('charset'); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Reset Password - <?php bloginfo('name'); ?></title>
            <?php wp_head(); ?>
            <style>
                body.myavana-password-reset-page {
                    margin: 0;
                    padding: 0;
                }
                /* Ensure our page takes full height */
                html, body.myavana-password-reset-page {
                    height: 100%;
                }
            </style>
        </head>
        <body <?php body_class('myavana-password-reset-page'); ?>>
            <?php 
            // Include the password reset template
            $template_path = MYAVANA_DIR . 'templates/auth/password-reset.php';
            if (file_exists($template_path)) {
                // Make variables available to the template
                set_query_var('reset_key', $reset_key);
                set_query_var('reset_login', $reset_login);
                set_query_var('key_valid', $key_valid);
                set_query_var('error_message', $error_message);
                set_query_var('user', $user);
                
                include $template_path;
            } else {
                echo '<div style="padding: 40px; text-align: center;">';
                echo '<h1>Password Reset Template Not Found</h1>';
                echo '<p>Template path: ' . esc_html($template_path) . '</p>';
                echo '</div>';
            }
            ?>
            <?php wp_footer(); ?>
        </body>
        </html>
        <?php
    }
}



// Initialize the auth system
if (!function_exists('myavana_init_auth_system')) {
    function myavana_init_auth_system() {
        return new Myavana_Auth_System();
    }
}

// Auto-initialize when file is loaded
add_action('plugins_loaded', 'myavana_init_auth_system');