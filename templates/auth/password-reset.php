<?php
/**
 * MYAVANA Custom Password Reset Page Template
 *
 * Branded password reset experience
 */

if (!defined('ABSPATH')) exit;

// Get variables passed from the main class
$reset_key = get_query_var('reset_key', '');
$reset_login = get_query_var('reset_login', '');
$key_valid = get_query_var('key_valid', false);
$error_message = get_query_var('error_message', '');
$user = get_query_var('user', null);
?>

<style>
    .myavana-reset-container {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #f5f5f7 0%, #fce5d7 100%);
        padding: 20px;
        font-family: 'Archivo', -apple-system, BlinkMacSystemFont, sans-serif;
    }

    .myavana-reset-card {
        background: white;
        border-radius: 20px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
        width: 100%;
        max-width: 480px;
        overflow: hidden;
    }

    .myavana-reset-header {
        background: linear-gradient(135deg, #e7a690 0%, #fce5d7 100%);
        padding: 40px 30px;
        text-align: center;
    }

    .myavana-reset-logo {
        font-family: 'Archivo Black', sans-serif;
        font-size: 28px;
        font-weight: 900;
        color: white;
        text-transform: uppercase;
        letter-spacing: 2px;
        margin-bottom: 10px;
    }

    .myavana-reset-title {
        color: white;
        font-size: 20px;
        font-weight: 600;
        margin: 0;
    }

    .myavana-reset-body {
        padding: 40px 30px;
    }

    .myavana-reset-form-group {
        margin-bottom: 24px;
    }

    .myavana-reset-form-group label {
        display: block;
        font-weight: 600;
        color: #222323;
        font-size: 14px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 10px;
    }

    .myavana-reset-password-wrapper {
        position: relative;
    }

    .myavana-reset-form-group input {
        width: 100%;
        padding: 16px 50px 16px 18px;
        border: 2px solid #eeece1;
        border-radius: 12px;
        font-size: 16px;
        transition: all 0.3s ease;
        box-sizing: border-box;
    }

    .myavana-reset-form-group input:focus {
        outline: none;
        border-color: #e7a690;
        box-shadow: 0 0 0 4px rgba(231, 166, 144, 0.1);
    }

    .myavana-reset-toggle {
        position: absolute;
        right: 16px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        cursor: pointer;
        color: #4a4d68;
        opacity: 0.6;
        padding: 5px;
    }

    .myavana-reset-toggle:hover {
        opacity: 1;
        color: #e7a690;
    }

    .myavana-reset-btn {
        width: 100%;
        padding: 18px;
        background: linear-gradient(135deg, #e7a690 0%, #d4956f 100%);
        border: none;
        border-radius: 12px;
        color: white;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        text-transform: uppercase;
        letter-spacing: 1px;
        transition: all 0.3s ease;
    }

    .myavana-reset-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 30px rgba(231, 166, 144, 0.4);
    }

    .myavana-reset-btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
    }

    .myavana-reset-btn.loading {
        color: transparent;
        position: relative;
    }

    .myavana-reset-btn.loading::after {
        content: '';
        position: absolute;
        width: 24px;
        height: 24px;
        top: 50%;
        left: 50%;
        margin: -12px 0 0 -12px;
        border: 3px solid rgba(255,255,255,0.3);
        border-top-color: white;
        border-radius: 50%;
        animation: myavanaResetSpin 1s linear infinite;
    }

    @keyframes myavanaResetSpin {
        to { transform: rotate(360deg); }
    }

    .myavana-reset-message {
        padding: 16px;
        border-radius: 10px;
        margin-bottom: 24px;
        font-size: 14px;
        display: flex;
        align-items: flex-start;
        gap: 12px;
    }

    .myavana-reset-message.error {
        background: #fef2f2;
        border: 1px solid #fecaca;
        color: #dc2626;
    }

    .myavana-reset-message.success {
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        color: #16a34a;
    }

    .myavana-reset-message-icon {
        font-size: 20px;
        flex-shrink: 0;
    }

    .myavana-reset-strength {
        margin-top: 12px;
    }

    .myavana-reset-strength-bar {
        height: 6px;
        background: #eeece1;
        border-radius: 3px;
        overflow: hidden;
        margin-bottom: 8px;
    }

    .myavana-reset-strength-fill {
        height: 100%;
        width: 0%;
        border-radius: 3px;
        transition: all 0.3s ease;
    }

    .myavana-reset-strength-fill.weak { width: 25%; background: #ef4444; }
    .myavana-reset-strength-fill.fair { width: 50%; background: #f59e0b; }
    .myavana-reset-strength-fill.good { width: 75%; background: #10b981; }
    .myavana-reset-strength-fill.strong { width: 100%; background: #059669; }

    .myavana-reset-strength-label {
        font-size: 12px;
        font-weight: 500;
    }

    .myavana-reset-strength-label.weak { color: #ef4444; }
    .myavana-reset-strength-label.fair { color: #f59e0b; }
    .myavana-reset-strength-label.good { color: #10b981; }
    .myavana-reset-strength-label.strong { color: #059669; }

    .myavana-reset-footer {
        text-align: center;
        padding-top: 20px;
        border-top: 1px solid #eeece1;
        margin-top: 30px;
    }

    .myavana-reset-footer a {
        color: #e7a690;
        text-decoration: none;
        font-weight: 500;
    }

    .myavana-reset-footer a:hover {
        text-decoration: underline;
    }

    .myavana-reset-match {
        font-size: 12px;
        margin-top: 8px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .myavana-reset-match.match { color: #10b981; }
    .myavana-reset-match.no-match { color: #ef4444; }
</style>

<div class="myavana-reset-container">
    <div class="myavana-reset-card">
        <div class="myavana-reset-header">
            <div class="myavana-reset-logo">MYAVANA</div>
            <h1 class="myavana-reset-title">Reset Your Password</h1>
        </div>

        <div class="myavana-reset-body">
            <?php if (!$key_valid): ?>
                <div class="myavana-reset-message error">
                    <span class="myavana-reset-message-icon">&#9888;</span>
                    <div>
                        <strong>Link Expired</strong><br>
                        <?php echo esc_html($error_message); ?>
                    </div>
                </div>
                <div class="myavana-reset-footer" style="border: none; margin: 0; padding: 0;">
                    <a href="<?php echo esc_url(home_url()); ?>">Return to Homepage</a>
                </div>
            <?php else: ?>
                <form id="myavanaResetForm">
                    <input type="hidden" name="reset_key" value="<?php echo esc_attr($reset_key); ?>">
                    <input type="hidden" name="reset_login" value="<?php echo esc_attr($reset_login); ?>">

                    <div id="reset-message-container"></div>

                    <div class="myavana-reset-form-group">
                        <label for="reset-password">New Password</label>
                        <div class="myavana-reset-password-wrapper">
                            <input type="password" id="reset-password" name="password"
                                   placeholder="Enter your new password" required minlength="8">
                            <button type="button" class="myavana-reset-toggle" onclick="togglePassword('reset-password', this)">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                                </svg>
                            </button>
                        </div>
                        <div class="myavana-reset-strength" id="password-strength" style="display: none;">
                            <div class="myavana-reset-strength-bar">
                                <div class="myavana-reset-strength-fill" id="strength-fill"></div>
                            </div>
                            <span class="myavana-reset-strength-label" id="strength-label"></span>
                        </div>
                    </div>

                    <div class="myavana-reset-form-group">
                        <label for="reset-password-confirm">Confirm New Password</label>
                        <div class="myavana-reset-password-wrapper">
                            <input type="password" id="reset-password-confirm" name="password_confirm"
                                   placeholder="Confirm your new password" required>
                            <button type="button" class="myavana-reset-toggle" onclick="togglePassword('reset-password-confirm', this)">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                                </svg>
                            </button>
                        </div>
                        <div class="myavana-reset-match" id="password-match" style="display: none;"></div>
                    </div>

                    <button type="submit" class="myavana-reset-btn" id="reset-submit-btn">
                        Reset Password
                    </button>
                </form>

                <div class="myavana-reset-footer">
                    <a href="<?php echo esc_url(home_url()); ?>">Return to Homepage</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    function togglePassword(inputId, btn) {
        const input = document.getElementById(inputId);
        if (input.type === 'password') {
            input.type = 'text';
        } else {
            input.type = 'password';
        }
    }

    function checkStrength(password) {
        let score = 0;
        if (password.length >= 8) score++;
        if (/[A-Z]/.test(password)) score++;
        if (/[a-z]/.test(password)) score++;
        if (/[0-9]/.test(password)) score++;
        if (/[^A-Za-z0-9]/.test(password)) score++;
        if (password.length >= 12) score++;

        if (score <= 2) return { strength: 'weak', label: 'Weak password' };
        if (score <= 4) return { strength: 'fair', label: 'Fair password' };
        if (score <= 5) return { strength: 'good', label: 'Good password' };
        return { strength: 'strong', label: 'Strong password' };
    }

    document.getElementById('reset-password')?.addEventListener('input', function() {
        const strengthDiv = document.getElementById('password-strength');
        const fill = document.getElementById('strength-fill');
        const label = document.getElementById('strength-label');

        if (this.value.length === 0) {
            strengthDiv.style.display = 'none';
            return;
        }

        strengthDiv.style.display = 'block';
        const result = checkStrength(this.value);

        fill.className = 'myavana-reset-strength-fill ' + result.strength;
        label.className = 'myavana-reset-strength-label ' + result.strength;
        label.textContent = result.label;

        // Also check match
        const confirm = document.getElementById('reset-password-confirm').value;
        if (confirm.length > 0) {
            checkPasswordMatch();
        }
    });

    document.getElementById('reset-password-confirm')?.addEventListener('input', checkPasswordMatch);

    function checkPasswordMatch() {
        const password = document.getElementById('reset-password').value;
        const confirm = document.getElementById('reset-password-confirm').value;
        const matchDiv = document.getElementById('password-match');

        if (confirm.length === 0) {
            matchDiv.style.display = 'none';
            return;
        }

        matchDiv.style.display = 'flex';

        if (password === confirm) {
            matchDiv.className = 'myavana-reset-match match';
            matchDiv.innerHTML = '<svg width="14" height="14" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/></svg> Passwords match';
        } else {
            matchDiv.className = 'myavana-reset-match no-match';
            matchDiv.innerHTML = '<svg width="14" height="14" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"/></svg> Passwords do not match';
        }
    }

    document.getElementById('myavanaResetForm')?.addEventListener('submit', function(e) {
        e.preventDefault();

        const password = document.getElementById('reset-password').value;
        const confirm = document.getElementById('reset-password-confirm').value;
        const btn = document.getElementById('reset-submit-btn');
        const messageContainer = document.getElementById('reset-message-container');

        // Validate
        if (password.length < 8) {
            showMessage('error', 'Password must be at least 8 characters long.');
            return;
        }

        if (password !== confirm) {
            showMessage('error', 'Passwords do not match.');
            return;
        }

        // Submit
        btn.disabled = true;
        btn.classList.add('loading');

        const formData = new FormData(this);
        formData.append('action', 'myavana_reset_password');
        formData.append('nonce', '<?php echo wp_create_nonce('myavana_reset_password'); ?>');

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage('success', data.data.message);
                document.getElementById('myavanaResetForm').style.display = 'none';

                // Redirect after 3 seconds
                setTimeout(() => {
                    window.location.href = '<?php echo esc_url(home_url()); ?>';
                }, 3000);
            } else {
                showMessage('error', data.data.message || 'An error occurred. Please try again.');
                btn.disabled = false;
                btn.classList.remove('loading');
            }
        })
        .catch(error => {
            showMessage('error', 'An error occurred. Please try again.');
            btn.disabled = false;
            btn.classList.remove('loading');
        });
    });

    function showMessage(type, message) {
        const container = document.getElementById('reset-message-container');
        const icon = type === 'error' ? '&#9888;' : '&#10004;';
        container.innerHTML = `<div class="myavana-reset-message ${type}">
            <span class="myavana-reset-message-icon">${icon}</span>
            <div>${message}</div>
        </div>`;
    }
</script>