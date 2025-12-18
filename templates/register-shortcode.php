<?php
function myavana_register_shortcode() {
    if (is_user_logged_in()) {
        return '<p>You are already registered. <a href="' . wp_logout_url() . '">Log out</a></p>';
    }
    ob_start();
    ?>
    <div class="myavana-form">
        <h2>Register</h2>
        <form method="post" action="">
            <input type="text" name="username" placeholder="Username" required>
            <input type="email" name="email" placeholder="Email" required>
            <div class="password-field">
                <input type="password" name="password" placeholder="Password" required>
                <span class="toggle-password">Show</span>
            </div>
            <div class="password-field">
                <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                <span class="toggle-password">Show</span>
            </div>
            <?php wp_nonce_field('myavana_register', 'myavana_nonce'); ?>
            <button type="submit" name="myavana_register">Register</button>
        </form>
    </div>
    <style>
        .myavana-form { max-width: 400px; margin: 0 auto; padding: 20px; }
        .myavana-form input { width: 100%; padding: 10px; margin: 10px 0; }
        .password-field { position: relative; }
        .toggle-password { position: absolute; right: 10px; top: 50%; cursor: pointer; }
        .myavana-form button { background: #4CAF50; color: white; padding: 10px; }
    </style>
    <script>
        document.querySelectorAll('.toggle-password').forEach(toggle => {
            toggle.addEventListener('click', function() {
                const password = this.previousElementSibling;
                this.textContent = password.type === 'password' ? 'Hide' : 'Show';
                password.type = password.type === 'password' ? 'text' : 'password';
            });
        });
    </script>
    <?php
    if (isset($_POST['myavana_register']) && wp_verify_nonce($_POST['myavana_nonce'], 'myavana_register')) {
        if ($_POST['password'] === $_POST['confirm_password']) {
            $user_id = wp_create_user(sanitize_user($_POST['username']), $_POST['password'], sanitize_email($_POST['email']));
            if (!is_wp_error($user_id)) {
                global $wpdb;
                $wpdb->insert(
                    $wpdb->prefix . 'myavana_profiles',
                    [
                        'user_id' => $user_id,
                        'hair_journey_stage' => 'Not set',
                        'hair_health_rating' => 5,
                        'life_journey_stage' => 'Not set',
                        'birthday' => '',
                        'location' => ''
                    ]
                );
                wp_set_current_user($user_id);
                wp_set_auth_cookie($user_id);
                wp_redirect(home_url('/profile'));
                exit;
            } else {
                echo '<p style="color: red;">' . esc_html($user_id->get_error_message()) . '</p>';
            }
        } else {
            echo '<p style="color: red;">Passwords do not match.</p>';
        }
    }
    return ob_get_clean();
}
?>