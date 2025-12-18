<?php
function myavana_login_shortcode() {
    if (is_user_logged_in()) {
        return '<p>You are already logged in. <a href="' . wp_logout_url() . '">Log out</a></p>';
    }
    ob_start();
    ?>
    <div class="myavana-form">
        <h2>Login</h2>
        <form method="post" action="">
            <input type="email" name="email" placeholder="Email" required>
            <div class="password-field">
                <input type="password" name="password" placeholder="Password" required>
                <span class="toggle-password">Show</span>
            </div>
            <?php wp_nonce_field('myavana_login', 'myavana_nonce'); ?>
            <button type="submit" name="myavana_login">Login</button>
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
        document.querySelector('.toggle-password').addEventListener('click', function() {
            const password = document.querySelector('input[name="password"]');
            this.textContent = password.type === 'password' ? 'Hide' : 'Show';
            password.type = password.type === 'password' ? 'text' : 'password';
        });
    </script>
    <?php
    if (isset($_POST['myavana_login']) && wp_verify_nonce($_POST['myavana_nonce'], 'myavana_login')) {
        $creds = [
            'user_login' => sanitize_email($_POST['email']),
            'user_password' => $_POST['password'],
            'remember' => true
        ];
        $user = wp_signon($creds, false);
        if (!is_wp_error($user)) {
            wp_redirect(home_url('/timeline'));
            exit;
        } else {
            echo '<p style="color: red;">' . esc_html($user->get_error_message()) . '</p>';
        }
    }
    return ob_get_clean();
}
?>