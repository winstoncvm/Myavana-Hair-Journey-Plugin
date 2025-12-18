<?php
/**
 * MYAVANA Authentication Modal Template
 *
 * Clean, modern authentication modal with enhanced UX
 */

if (!defined('ABSPATH')) exit;

$bg_url = MYAVANA_URL . 'assets/images/auth-bg.jpg';

// Debug output
if (defined('WP_DEBUG') && WP_DEBUG) {
    echo '<!-- MYAVANA: Auth modal template loaded -->';
}
?>

<style>
    /* CSS Variables - MYAVANA Luxury Design System */
    :root {
        /* MYAVANA Brand Colors */
        --myavana-onyx: #222323;
        --myavana-white: #ffffff;
        --myavana-coral: #e7a690;
        --myavana-light-coral: #fce5d7;
        --myavana-stone: #f5f5f7;
        --myavana-blueberry: #4a4d68;

        /* Typography */
        --font-primary: 'Archivo Black', sans-serif;
        --font-secondary: 'Archivo', sans-serif;

        /* Spacing System */
        --space-xs: 4px;
        --space-sm: 8px;
        --space-md: 16px;
        --space-lg: 24px;
        --space-xl: 32px;
        --space-2xl: 48px;
        --space-3xl: 64px;
        --space-4xl: 80px;
        --space-5xl: 120px;

        /* Layout */
        --max-width: 1400px;
        --container-padding: 0 2rem;
        --section-padding: var(--space-5xl) 0;

        /* Shadows */
        --shadow-soft: 0 4px 20px rgba(34, 35, 35, 0.06);
        --shadow-medium: 0 8px 40px rgba(34, 35, 35, 0.08);
        --shadow-strong: 0 20px 60px rgba(34, 35, 35, 0.12);

        /* Effects */
        --border-radius: 12px;
        --border-radius-lg: 20px;
        --transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        --transition-fast: all 0.2s ease;
        --hover-transform: translateY(-8px);

        /* Gradients */
        --gradient-coral: linear-gradient(135deg, var(--myavana-coral) 0%, #d4956f 100%);
        --gradient-text: linear-gradient(135deg, var(--myavana-coral) 0%, var(--myavana-onyx) 100%);
    }
    /* MYAVANA Authentication Modal Styles */
    .myavana-auth-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(34, 35, 35, 0.8);
        backdrop-filter: blur(12px);
        z-index: 999999;
        align-items: center;
        justify-content: center;
        animation: myavanaFadeIn 0.4s ease-out;
        font-family: 'Archivo', sans-serif;
    }

    .myavana-auth-modal.show {
        display: flex;
    }

    @keyframes myavanaFadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    .myavana-modal-container {
        background: #fff;
        width: 95%;
        max-width: 1000px;
        max-height: 90vh;
        border-radius: 16px;
        overflow: hidden;
        display: flex;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        animation: myavanaSlideUp 0.5s ease-out;
        position: relative;
    }

    @keyframes myavanaSlideUp {
        from {
            opacity: 0;
            transform: translateY(50px) scale(0.95);
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
        background: rgba(255, 255, 255, 0.95);
        border: none;
        width: 44px;
        height: 44px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        z-index: 10;
        transition: all 0.3s ease;
        font-size: 20px;
        color: var(--myavana-onyx);
        font-weight: 600;
    }

    .myavana-close-btn:hover {
        background: var(--myavana-white);
        transform: scale(1.1);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .myavana-left-section {
        flex: 1;
        background: linear-gradient(135deg, var(--myavana-coral) 0%, var(--myavana-light-coral) 100%),
                    url('<?php echo esc_url($bg_url); ?>');
        background-size: cover;
        background-position: center;
        background-blend-mode: soft-light;
        position: relative;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: 60px 40px;
        min-height: 600px;
    }

    .myavana-left-section::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(45deg,
            rgba(231, 166, 144, 0.1) 0%,
            rgba(252, 229, 215, 0.05) 50%,
            rgba(74, 77, 104, 0.1) 100%);
        opacity: 0.8;
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
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.1);
        animation: myavanaFloat 8s ease-in-out infinite;
    }

    .myavana-floating-element:nth-child(1) {
        top: 15%;
        left: 10%;
        width: 80px;
        height: 80px;
        animation-delay: 0s;
    }

    .myavana-floating-element:nth-child(2) {
        top: 60%;
        right: 20%;
        width: 60px;
        height: 60px;
        animation-delay: 3s;
    }

    .myavana-floating-element:nth-child(3) {
        bottom: 25%;
        left: 25%;
        width: 100px;
        height: 100px;
        animation-delay: 6s;
    }

    @keyframes myavanaFloat {
        0%, 100% { transform: translateY(0px) rotate(0deg); opacity: 0.3; }
        33% { transform: translateY(-30px) rotate(120deg); opacity: 0.6; }
        66% { transform: translateY(-15px) rotate(240deg); opacity: 0.4; }
    }

    .myavana-left-content {
        position: relative;
        z-index: 2;
        color: var(--myavana-white);
    }

    .myavana-header-section {
        text-align: center;
        margin-bottom: 40px;
    }

    .myavana-logo-section img {
        max-width: 120px;
        height: auto;
        margin-bottom: 16px;
    }

    .myavana-header-section .text {
        font-family: 'Archivo Black', sans-serif;
        font-size: 16px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 2px;
        color: var(--myavana-onyx);
    }

    .myavana-tagline {
        font-family: 'Archivo Black', sans-serif;
        font-size: 32px;
        font-weight: 900;
        text-transform: uppercase;
        margin-bottom: 40px;
        line-height: 1.2;
        color: var(--myavana-onyx);
        text-align: center;
    }

    .myavana-features {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .myavana-features li {
        display: flex;
        align-items: center;
        margin-bottom: 24px;
        font-size: 16px;
        font-weight: 400;
        color: var(--myavana-onyx);
        line-height: 1.5;
    }

    .myavana-features li::before {
        content: '✨';
        margin-right: 16px;
        font-size: 20px;
        flex-shrink: 0;
    }

    .myavana-right-section {
        flex: 1;
        background: var(--myavana-white);
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: 60px 40px;
        position: relative;
        overflow-y: auto;
    }

    .myavana-auth-toggle {
        display: flex;
        background: var(--myavana-stone);
        border-radius: 12px;
        padding: 6px;
        margin-bottom: 40px;
        position: relative;
    }

    .myavana-auth-toggle button {
        flex: 1;
        padding: 14px 24px;
        background: none;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
        z-index: 2;
        font-size: 14px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .myavana-auth-toggle button.active {
        background: var(--myavana-white);
        color: var(--myavana-coral);
        box-shadow: 0 2px 8px rgba(231, 166, 144, 0.2);
    }

    .myavana-auth-toggle button:not(.active) {
        color: var(--myavana-blueberry);
    }

    .myavana-form-container {
        position: relative;
        min-height: 400px;
    }

    .myavana-auth-form {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        opacity: 0;
        visibility: hidden;
        transition: all 0.4s ease;
        transform: translateY(30px);
    }

    .myavana-auth-form.active {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    .myavana-form-group {
        margin-bottom: 28px;
    }

    .myavana-form-group label {
        display: block;
        margin-bottom: 10px;
        font-weight: 600;
        color: var(--myavana-onyx);
        font-size: 14px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .myavana-form-group input {
        width: 100%;
        padding: 18px 20px;
        border: 2px solid var(--myavana-blueberry);
        border-radius: 12px;
        font-size: 16px;
        transition: all 0.3s ease;
        background: var(--myavana-sand);
        box-sizing: border-box;
        font-family: 'Archivo', sans-serif;
    }

    .myavana-form-group input:focus {
        outline: none;
        border-color: var(--myavana-coral);
        box-shadow: 0 0 0 4px rgba(231, 166, 144, 0.1);
        transform: translateY(-2px);
    }

    .myavana-form-group input::placeholder {
        color: #999;
    }

    /* Password Input Wrapper */
    .myavana-password-wrapper {
        position: relative;
        display: flex;
        align-items: center;
    }

    .myavana-password-wrapper input {
        padding-right: 50px;
    }

    .myavana-password-toggle {
        position: absolute;
        right: 16px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        cursor: pointer;
        padding: 8px;
        color: var(--myavana-blueberry);
        opacity: 0.6;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .myavana-password-toggle:hover {
        opacity: 1;
        color: var(--myavana-coral);
    }

    .myavana-password-toggle svg {
        width: 20px;
        height: 20px;
        fill: currentColor;
    }

    /* Password Strength Indicator */
    .myavana-password-strength {
        margin-top: 12px;
    }

    .myavana-strength-bar {
        height: 6px;
        background: var(--myavana-sand);
        border-radius: 3px;
        overflow: hidden;
        margin-bottom: 8px;
    }

    .myavana-strength-fill {
        height: 100%;
        width: 0%;
        border-radius: 3px;
        transition: all 0.3s ease;
    }

    .myavana-strength-fill.weak {
        width: 25%;
        background: #ef4444;
    }

    .myavana-strength-fill.fair {
        width: 50%;
        background: #f59e0b;
    }

    .myavana-strength-fill.good {
        width: 75%;
        background: #10b981;
    }

    .myavana-strength-fill.strong {
        width: 100%;
        background: linear-gradient(90deg, #10b981, #059669);
    }

    .myavana-strength-text {
        font-size: 12px;
        font-weight: 500;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .myavana-strength-label {
        color: var(--myavana-blueberry);
    }

    .myavana-strength-label.weak { color: #ef4444; }
    .myavana-strength-label.fair { color: #f59e0b; }
    .myavana-strength-label.good { color: #10b981; }
    .myavana-strength-label.strong { color: #059669; }

    .myavana-strength-requirements {
        font-size: 11px;
        color: #888;
        margin-top: 8px;
        padding: 10px 12px;
        background: var(--myavana-stone);
        border-radius: 8px;
    }

    .myavana-strength-requirements ul {
        margin: 6px 0 0 0;
        padding-left: 16px;
        list-style: none;
    }

    .myavana-strength-requirements li {
        margin-bottom: 4px;
        position: relative;
        padding-left: 20px;
    }

    .myavana-strength-requirements li::before {
        content: '○';
        position: absolute;
        left: 0;
        color: #ccc;
        font-size: 10px;
    }

    .myavana-strength-requirements li.met::before {
        content: '●';
        color: #10b981;
    }

    .myavana-strength-requirements li.met {
        color: var(--myavana-onyx);
    }

    /* Real-time Validation States */
    .myavana-form-group.valid input {
        border-color: #10b981;
    }

    .myavana-form-group.valid input:focus {
        box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1);
    }

    .myavana-form-group.invalid input {
        border-color: #ef4444;
    }

    .myavana-form-group.invalid input:focus {
        box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.1);
    }

    .myavana-validation-hint {
        font-size: 12px;
        margin-top: 6px;
        display: flex;
        align-items: center;
        gap: 6px;
        opacity: 0;
        transform: translateY(-5px);
        transition: all 0.2s ease;
    }

    .myavana-form-group.invalid .myavana-validation-hint,
    .myavana-form-group.valid .myavana-validation-hint {
        opacity: 1;
        transform: translateY(0);
    }

    .myavana-validation-hint.error {
        color: #ef4444;
    }

    .myavana-validation-hint.success {
        color: #10b981;
    }

    .myavana-validation-icon {
        width: 14px;
        height: 14px;
        flex-shrink: 0;
    }

    /* Validation for checkbox */
    .myavana-checkbox-group.invalid label {
        color: #ef4444;
    }

    .myavana-checkbox-group.invalid input[type="checkbox"] {
        outline: 2px solid #ef4444;
        outline-offset: 2px;
    }

    .myavana-checkbox-group {
        display: flex;
        align-items: flex-start;
        margin-bottom: 28px;
    }

    .myavana-checkbox-group input[type="checkbox"] {
        width: 20px;
        height: 20px;
        margin-right: 12px;
        margin-top: 2px;
        accent-color: var(--myavana-coral);
        cursor: pointer;
    }

    .myavana-checkbox-group label {
        margin-bottom: 0;
        font-size: 14px;
        color: var(--myavana-blueberry);
        font-weight: 400;
        text-transform: none;
        letter-spacing: 0;
        line-height: 1.5;
        cursor: pointer;
    }

    .myavana-submit-btn {
        width: 100%;
        padding: 18px 24px;
        background: linear-gradient(135deg, var(--myavana-coral) 0%, #d4956f 100%);
        border: none;
        border-radius: 12px;
        color: var(--myavana-white);
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        margin-bottom: 28px;
        text-transform: uppercase;
        letter-spacing: 1px;
        position: relative;
        overflow: hidden;
        font-family: 'Archivo', sans-serif;
    }

    .myavana-submit-btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 30px rgba(231, 166, 144, 0.4);
    }

    .myavana-submit-btn:active {
        transform: translateY(-1px);
    }

    .myavana-submit-btn:disabled {
        opacity: 0.7;
        cursor: not-allowed;
        transform: none;
    }

    .myavana-submit-btn.loading {
        color: transparent;
    }

    .myavana-submit-btn.loading::after {
        content: '';
        position: absolute;
        width: 24px;
        height: 24px;
        top: 50%;
        left: 50%;
        margin-left: -12px;
        margin-top: -12px;
        border: 3px solid rgba(255, 255, 255, 0.3);
        border-radius: 50%;
        border-top-color: var(--myavana-white);
        animation: myavanaSpinLoader 1s ease-in-out infinite;
    }

    @keyframes myavanaSpinLoader {
        to { transform: rotate(360deg); }
    }

    .myavana-forgot-link {
        text-align: center;
        color: var(--myavana-coral);
        text-decoration: none;
        font-size: 14px;
        font-weight: 500;
        display: block;
        transition: all 0.2s ease;
    }

    .myavana-forgot-link:hover {
        text-decoration: underline;
        color: #d4956f;
    }

    .myavana-error-message,
    .myavana-success-message {
        padding: 16px 20px;
        border-radius: 8px;
        font-size: 14px;
        margin-top: 16px;
        display: none;
        animation: myavanaSlideIn 0.3s ease-out;
    }

    .myavana-error-message {
        color: #d32f2f;
        background: rgba(211, 47, 47, 0.1);
        border: 1px solid rgba(211, 47, 47, 0.2);
    }

    .myavana-success-message {
        color: #388e3c;
        background: rgba(56, 142, 60, 0.1);
        border: 1px solid rgba(56, 142, 60, 0.2);
    }

    @keyframes myavanaSlideIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Mobile Responsive Design */
    @media (max-width: 768px) {
        .myavana-modal-container {
            width: 98%;
            height: 95vh;
            flex-direction: column;
            border-radius: 12px;
        }

        .myavana-left-section {
            padding: 30px 24px;
            min-height: 250px;
        }

        .myavana-tagline {
            font-size: 24px;
            margin-bottom: 30px;
        }

        .myavana-features li {
            font-size: 14px;
            margin-bottom: 16px;
        }

        .myavana-right-section {
            padding: 30px 24px;
            justify-content: flex-start;
        }

        .myavana-form-container {
            min-height: auto;
        }

        .myavana-form-group input {
            padding: 16px 18px;
        }

        .myavana-submit-btn {
            padding: 16px 20px;
        }
    }

    @media (max-width: 480px) {
        .myavana-modal-container {
            width: 100%;
            height: 100vh;
            border-radius: 0;
        }

        .myavana-close-btn {
            top: 16px;
            right: 16px;
            width: 40px;
            height: 40px;
        }

        .myavana-left-section {
            padding: 24px 20px;
            min-height: 200px;
        }

        .myavana-right-section {
            padding: 24px 20px;
        }
    }

    /* High contrast mode support */
    @media (prefers-contrast: high) {
        .myavana-form-group input {
            border-width: 3px;
        }

        .myavana-submit-btn {
            border: 2px solid var(--myavana-onyx);
        }
    }

    /* Reduced motion support */
    @media (prefers-reduced-motion: reduce) {
        .myavana-auth-modal,
        .myavana-modal-container,
        .myavana-floating-element,
        .myavana-auth-form,
        .myavana-submit-btn {
            animation: none;
            transition: none;
        }
    }
</style>

<div class="myavana-auth-modal" id="myavanaAuthModal">
    <div class="myavana-modal-container">
        <button class="myavana-close-btn" id="myavanaCloseBtn" aria-label="Close authentication modal">×</button>

        <div class="myavana-left-section">
            <div class="myavana-floating-elements">
                <div class="myavana-floating-element"></div>
                <div class="myavana-floating-element"></div>
                <div class="myavana-floating-element"></div>
            </div>

            <div class="myavana-left-content">
                <div class="myavana-header-section">
                    <span class="myavana-logo-section">
                        <img src="<?php echo esc_url(MYAVANA_URL); ?>assets/images/myavana-primary-logo.png"
                             alt="MYAVANA Logo" />
                    </span>
                    <div class="text">✨ Hair Journey ✨</div>
                </div>

                <h2 class="myavana-tagline">Your Hair Journey Starts Here</h2>

                <ul class="myavana-features">
                    <li>AI-powered hair analysis & personalized recommendations</li>
                    <li>Track your progress with photos and timeline entries</li>
                    <li>Connect with a supportive hair care community</li>
                    <li>Expert guidance for all hair types and textures</li>
                    <li>Achieve your hair goals with data-driven insights</li>
                </ul>
            </div>
        </div>

        <div class="myavana-right-section">
            <div class="myavana-auth-toggle" role="tablist">
                <button class="active" id="myavanaSigninTab" role="tab" aria-selected="true" aria-controls="myavanaSigninForm">Sign In</button>
                <button id="myavanaSignupTab" role="tab" aria-selected="false" aria-controls="myavanaSignupForm">Sign Up</button>
            </div>

            <div class="myavana-form-container">
                <!-- Sign In Form -->
                <form class="myavana-auth-form active" id="myavanaSigninForm" role="tabpanel" aria-labelledby="myavanaSigninTab">
                    <div class="myavana-form-group" data-validate="login">
                        <label for="myavana-signin-login">Email or Username</label>
                        <input type="text" id="myavana-signin-login" name="login"
                               placeholder="Enter your email or username"
                               required aria-required="true" autocomplete="username">
                        <div class="myavana-validation-hint"></div>
                    </div>

                    <div class="myavana-form-group">
                        <label for="myavana-signin-password">Password</label>
                        <div class="myavana-password-wrapper">
                            <input type="password" id="myavana-signin-password" name="password"
                                   placeholder="Enter your password"
                                   required aria-required="true" autocomplete="current-password">
                            <button type="button" class="myavana-password-toggle" aria-label="Toggle password visibility" data-target="myavana-signin-password">
                                <svg class="eye-open" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                                </svg>
                                <svg class="eye-closed" style="display:none;" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="myavana-checkbox-group">
                        <input type="checkbox" id="myavana-remember" name="remember" value="1">
                        <label for="myavana-remember">Remember me for 30 days</label>
                    </div>

                    <button type="submit" class="myavana-submit-btn">Sign In to MYAVANA</button>

                    <div class="myavana-error-message" id="myavana-signin-error" role="alert"></div>
                    <div class="myavana-success-message" id="myavana-signin-success" role="alert"></div>

                    <a href="#" class="myavana-forgot-link" id="myavanaForgotLink">Forgot your password?</a>
                </form>

                <!-- Sign Up Form -->
                <form class="myavana-auth-form" id="myavanaSignupForm" role="tabpanel" aria-labelledby="myavanaSignupTab">
                    <div class="myavana-form-group" data-validate="name">
                        <label for="myavana-signup-name">Full Name</label>
                        <input type="text" id="myavana-signup-name" name="name"
                               placeholder="Enter your full name"
                               required aria-required="true" autocomplete="name">
                        <div class="myavana-validation-hint"></div>
                    </div>

                    <div class="myavana-form-group" data-validate="email">
                        <label for="myavana-signup-email">Email Address</label>
                        <input type="email" id="myavana-signup-email" name="email"
                               placeholder="Enter your email address"
                               required aria-required="true" autocomplete="email">
                        <div class="myavana-validation-hint"></div>
                    </div>

                    <div class="myavana-form-group">
                        <label for="myavana-signup-password">Create Password</label>
                        <div class="myavana-password-wrapper">
                            <input type="password" id="myavana-signup-password" name="password"
                                   placeholder="Create a secure password (min 8 characters)"
                                   required aria-required="true" autocomplete="new-password" minlength="8">
                            <button type="button" class="myavana-password-toggle" aria-label="Toggle password visibility" data-target="myavana-signup-password">
                                <svg class="eye-open" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                                </svg>
                                <svg class="eye-closed" style="display:none;" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z"/>
                                </svg>
                            </button>
                        </div>
                        <!-- Password Strength Indicator -->
                        <div class="myavana-password-strength" id="myavana-password-strength" style="display: none;">
                            <div class="myavana-strength-bar">
                                <div class="myavana-strength-fill" id="myavana-strength-fill"></div>
                            </div>
                            <div class="myavana-strength-text">
                                <span class="myavana-strength-label" id="myavana-strength-label">Password strength</span>
                            </div>
                            <div class="myavana-strength-requirements">
                                <span>Password should have:</span>
                                <ul>
                                    <li id="req-length">At least 8 characters</li>
                                    <li id="req-uppercase">One uppercase letter</li>
                                    <li id="req-lowercase">One lowercase letter</li>
                                    <li id="req-number">One number</li>
                                    <li id="req-special">One special character (!@#$%^&*)</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="myavana-checkbox-group">
                        <input type="checkbox" id="myavana-terms" name="terms" required aria-required="true" value="1">
                        <label for="myavana-terms">I agree to the <a href="#" target="_blank" style="color: var(--myavana-coral);">Terms of Service</a> and <a href="#" target="_blank" style="color: var(--myavana-coral);">Privacy Policy</a></label>
                    </div>

                    <button type="submit" class="myavana-submit-btn">Create My MYAVANA Account</button>

                    <div class="myavana-error-message" id="myavana-signup-error" role="alert"></div>
                    <div class="myavana-success-message" id="myavana-signup-success" role="alert"></div>
                </form>

                <!-- Forgot Password Form -->
                <form class="myavana-auth-form" id="myavanaForgotForm" style="display: none;">
                    <div class="myavana-form-group">
                        <label for="myavana-forgot-email">Email Address</label>
                        <input type="email" id="myavana-forgot-email" name="email"
                               placeholder="Enter your email address"
                               required aria-required="true" autocomplete="email">
                    </div>

                    <button type="submit" class="myavana-submit-btn">Send Reset Link</button>

                    <div class="myavana-error-message" id="myavana-forgot-error" role="alert"></div>
                    <div class="myavana-success-message" id="myavana-forgot-success" role="alert"></div>

                    <a href="#" class="myavana-forgot-link" id="myavanaBackToSignin">← Back to Sign In</a>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript functionality is handled by the external myavana-auth-system.js file -->
<script>
// Test function to ensure modal can be shown manually
jQuery(document).ready(function($) {
    console.log('MYAVANA: Auth modal template loaded');
    console.log('MYAVANA: Modal element exists:', $('#myavanaAuthModal').length > 0);

    // Password visibility toggle
    $('.myavana-password-toggle').on('click', function(e) {
        e.preventDefault();
        const targetId = $(this).data('target');
        const input = $('#' + targetId);
        const eyeOpen = $(this).find('.eye-open');
        const eyeClosed = $(this).find('.eye-closed');

        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            eyeOpen.hide();
            eyeClosed.show();
            $(this).attr('aria-label', 'Hide password');
        } else {
            input.attr('type', 'password');
            eyeOpen.show();
            eyeClosed.hide();
            $(this).attr('aria-label', 'Show password');
        }
    });

    // Password strength checker
    function checkPasswordStrength(password) {
        let score = 0;
        const requirements = {
            length: password.length >= 8,
            uppercase: /[A-Z]/.test(password),
            lowercase: /[a-z]/.test(password),
            number: /[0-9]/.test(password),
            special: /[!@#$%^&*(),.?":{}|<>]/.test(password)
        };

        // Update requirement indicators
        $('#req-length').toggleClass('met', requirements.length);
        $('#req-uppercase').toggleClass('met', requirements.uppercase);
        $('#req-lowercase').toggleClass('met', requirements.lowercase);
        $('#req-number').toggleClass('met', requirements.number);
        $('#req-special').toggleClass('met', requirements.special);

        // Calculate score
        if (requirements.length) score++;
        if (requirements.uppercase) score++;
        if (requirements.lowercase) score++;
        if (requirements.number) score++;
        if (requirements.special) score++;

        // Bonus for length
        if (password.length >= 12) score++;
        if (password.length >= 16) score++;

        // Determine strength level
        let strength, label;
        if (score <= 2) {
            strength = 'weak';
            label = 'Weak - Add more variety';
        } else if (score <= 4) {
            strength = 'fair';
            label = 'Fair - Getting better';
        } else if (score <= 5) {
            strength = 'good';
            label = 'Good - Nice password!';
        } else {
            strength = 'strong';
            label = 'Strong - Excellent!';
        }

        return { strength, label, requirements };
    }

    // Password strength indicator
    $('#myavana-signup-password').on('input', function() {
        const password = $(this).val();
        const strengthContainer = $('#myavana-password-strength');
        const strengthFill = $('#myavana-strength-fill');
        const strengthLabel = $('#myavana-strength-label');

        if (password.length === 0) {
            strengthContainer.slideUp(200);
            return;
        }

        strengthContainer.slideDown(200);

        const result = checkPasswordStrength(password);

        // Update UI
        strengthFill.removeClass('weak fair good strong').addClass(result.strength);
        strengthLabel.removeClass('weak fair good strong').addClass(result.strength).text(result.label);
    });

    // Real-time form validation
    const validationRules = {
        login: {
            validate: (value) => value.trim().length >= 3,
            errorMsg: 'Please enter at least 3 characters',
            successMsg: 'Looks good!'
        },
        name: {
            validate: (value) => {
                const trimmed = value.trim();
                return trimmed.length >= 2 && /^[a-zA-Z\s'-]+$/.test(trimmed);
            },
            errorMsg: 'Please enter your full name (letters only)',
            successMsg: 'Nice to meet you!'
        },
        email: {
            validate: (value) => {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return emailRegex.test(value.trim());
            },
            errorMsg: 'Please enter a valid email address',
            successMsg: 'Valid email address'
        }
    };

    function setValidationState(formGroup, isValid, message) {
        const hint = formGroup.find('.myavana-validation-hint');

        formGroup.removeClass('valid invalid');

        if (isValid === null) {
            hint.removeClass('error success').html('');
            return;
        }

        if (isValid) {
            formGroup.addClass('valid');
            hint.removeClass('error').addClass('success').html(
                '<svg class="myavana-validation-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>' + message
            );
        } else {
            formGroup.addClass('invalid');
            hint.removeClass('success').addClass('error').html(
                '<svg class="myavana-validation-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>' + message
            );
        }
    }

    // Attach validation to inputs
    $('[data-validate]').each(function() {
        const formGroup = $(this);
        const validateType = formGroup.data('validate');
        const input = formGroup.find('input');
        const rules = validationRules[validateType];

        if (!rules) return;

        let hasInteracted = false;

        input.on('focus', function() {
            hasInteracted = true;
        });

        input.on('blur', function() {
            const value = $(this).val();
            if (value.length === 0 && !hasInteracted) {
                setValidationState(formGroup, null, '');
                return;
            }
            if (value.length > 0) {
                const isValid = rules.validate(value);
                setValidationState(formGroup, isValid, isValid ? rules.successMsg : rules.errorMsg);
            }
        });

        input.on('input', function() {
            const value = $(this).val();
            if (value.length === 0) {
                setValidationState(formGroup, null, '');
                return;
            }
            // Only show validation after user has typed a bit
            if (value.length >= 3 || hasInteracted) {
                const isValid = rules.validate(value);
                setValidationState(formGroup, isValid, isValid ? rules.successMsg : rules.errorMsg);
            }
        });
    });

    // Terms checkbox validation
    $('#myavana-terms').on('change', function() {
        const checkboxGroup = $(this).closest('.myavana-checkbox-group');
        if ($(this).is(':checked')) {
            checkboxGroup.removeClass('invalid');
        }
    });

    // Form submit validation
    $('#myavanaSignupForm').on('submit', function(e) {
        const termsCheckbox = $('#myavana-terms');
        if (!termsCheckbox.is(':checked')) {
            termsCheckbox.closest('.myavana-checkbox-group').addClass('invalid');
        }
    });

    // Global helper functions for testing
    window.resetMyavanaOnboarding = function() {
        console.log('MYAVANA: Resetting onboarding status...');
        fetch('/wp-admin/admin-ajax.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'myavana_reset_onboarding',
                nonce: window.myavanaAuth ? window.myavanaAuth.nonce : ''
            })
        })
        .then(response => response.json())
        .then(data => {
            console.log('MYAVANA: Reset response:', data);
            alert('Onboarding reset! Refresh the page to see the onboarding overlay.');
        })
        .catch(error => {
            console.error('MYAVANA: Reset failed:', error);
        });
    };

    // Add a test button for debugging
    if (window.location.search.includes('debug=1')) {
        $('body').append('<button id="myavanaTestBtn" style="position:fixed;top:10px;right:10px;z-index:99999;background:red;color:white;padding:10px;border:none;border-radius:5px;cursor:pointer;">🔧 Test Modal</button>');
        $('#myavanaTestBtn').click(function() {
            if (typeof window.showMyavanaModal === 'function') {
                window.showMyavanaModal('signin');
            } else {
                console.error('showMyavanaModal function not available');
            }
        });

        $('body').append('<button id="myavanaResetBtn" style="position:fixed;top:60px;right:10px;z-index:99999;background:orange;color:white;padding:10px;border:none;border-radius:5px;cursor:pointer;">🔄 Reset Onboarding</button>');
        $('#myavanaResetBtn').click(function() {
            window.resetMyavanaOnboarding();
        });
    }
});
</script>