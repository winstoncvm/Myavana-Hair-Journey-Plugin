<?php
/**
 * MYAVANA Onboarding Overlay Template
 *
 * Interactive onboarding experience for new users
 */

if (!defined('ABSPATH')) exit;

$user_id = get_current_user_id();
$user_name = get_user_meta($user_id, 'first_name', true) ?: wp_get_current_user()->display_name;
?>

<script>
// Make sure onboarding data is available globally
if (typeof myavanaOnboarding === 'undefined') {
    window.myavanaOnboarding = {
        ajax_url: '<?php echo admin_url('admin-ajax.php'); ?>',
        nonce: '<?php echo wp_create_nonce('myavana_onboarding'); ?>',
        user_id: <?php echo $user_id; ?>
    };
}
console.log('MYAVANA: Onboarding nonce set:', window.myavanaOnboarding.nonce);
</script>

<style>
/* MYAVANA Onboarding Overlay Styles */
.myavana-onboarding-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(34, 35, 35, 0.95);
    backdrop-filter: blur(15px);
    z-index: 1000000;
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: 'Archivo', sans-serif;
    animation: myavanaOnboardingFadeIn 0.6s ease-out;
}

@keyframes myavanaOnboardingFadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.myavana-onboarding-container {
    background: var(--myavana-white);
    width: 95%;
    max-width: 800px;
    max-height: 90vh;
    border-radius: 20px;
    overflow: hidden;
    position: relative;
    box-shadow: 0 30px 80px rgba(0, 0, 0, 0.3);
    animation: myavanaOnboardingSlideUp 0.8s ease-out;
}

@keyframes myavanaOnboardingSlideUp {
    from {
        opacity: 0;
        transform: translateY(100px) scale(0.9);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.myavana-onboarding-header {
    background: linear-gradient(135deg, var(--myavana-coral) 0%, var(--myavana-light-coral) 100%);
    padding: 30px 40px;
    text-align: center;
    color: var(--myavana-white);
    position: relative;
    overflow: hidden;
}

.myavana-onboarding-header::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="30" fill="rgba(255,255,255,0.1)"/></svg>');
    animation: myavanaRotatePattern 20s linear infinite;
    pointer-events: none;
}

@keyframes myavanaRotatePattern {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.myavana-onboarding-logo {
    max-width: 100px;
    height: auto;
    margin-bottom: 16px;
    position: relative;
    z-index: 2;
}

.myavana-onboarding-title {
    font-family: 'Archivo Black', sans-serif;
    font-size: 28px;
    font-weight: 900;
    text-transform: uppercase;
    margin: 0 0 12px 0;
    position: relative;
    z-index: 2;
}

.myavana-onboarding-subtitle {
    font-size: 16px;
    opacity: 0.9;
    margin: 0;
    position: relative;
    z-index: 2;
}

.myavana-onboarding-content {
    padding: 40px;
    overflow-y: auto;
    max-height: calc(90vh - 200px);
}

.myavana-onboarding-step {
    display: none;
    text-align: center;
}

.myavana-onboarding-step.active {
    display: block;
    animation: myavanaStepFadeIn 0.5s ease-out;
}

@keyframes myavanaStepFadeIn {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.myavana-step-icon {
    font-size: 60px;
    margin-bottom: 24px;
    display: block;
}

.myavana-step-title {
    font-family: 'Archivo Black', sans-serif;
    font-size: 24px;
    font-weight: 900;
    color: var(--myavana-onyx);
    margin: 0 0 16px 0;
    text-transform: uppercase;
}

.myavana-step-description {
    font-size: 16px;
    color: var(--myavana-blueberry);
    line-height: 1.6;
    margin: 0 0 32px 0;
    max-width: 500px;
    margin-left: auto;
    margin-right: auto;
}

/* Step-specific styling */
.myavana-hair-type-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 16px;
    margin: 32px 0;
}

.myavana-hair-type-option {
    background: var(--myavana-stone);
    border: 2px solid transparent;
    border-radius: 12px;
    padding: 20px 16px;
    cursor: pointer;
    transition: all 0.3s ease;
    text-align: center;
}

.myavana-hair-type-option:hover {
    border-color: var(--myavana-coral);
    background: var(--myavana-sand);
    transform: translateY(-2px);
}

.myavana-hair-type-option.selected {
    border-color: var(--myavana-coral);
    background: var(--myavana-light-coral);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(231, 166, 144, 0.3);
}

.myavana-hair-type-option.selected .emoji {
    transform: scale(1.2);
    transition: transform 0.3s ease;
}

.myavana-hair-type-option.selected .label {
    color: var(--myavana-coral);
    font-weight: 700;
}

.myavana-hair-type-option .emoji {
    font-size: 32px;
    display: block;
    margin-bottom: 8px;
}

.myavana-hair-type-option .label {
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    color: var(--myavana-onyx);
}

.myavana-goals-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin: 32px 0;
}

.myavana-goal-option {
    background: var(--myavana-stone);
    border: 2px solid transparent;
    border-radius: 12px;
    padding: 24px 20px;
    cursor: pointer;
    transition: all 0.3s ease;
    text-align: left;
}

.myavana-goal-option:hover {
    border-color: var(--myavana-coral);
    background: var(--myavana-sand);
    transform: translateY(-2px);
}

.myavana-goal-option.selected {
    border-color: var(--myavana-coral);
    background: var(--myavana-light-coral);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(231, 166, 144, 0.3);
}

.myavana-goal-option.selected .emoji {
    transform: scale(1.15);
    transition: transform 0.3s ease;
}

.myavana-goal-option.selected .title {
    color: var(--myavana-coral);
    font-weight: 700;
}

.myavana-goal-option .emoji {
    font-size: 24px;
    margin-bottom: 8px;
    display: block;
}

.myavana-goal-option .title {
    font-size: 14px;
    font-weight: 600;
    color: var(--myavana-onyx);
    margin-bottom: 4px;
}

.myavana-goal-option .description {
    font-size: 12px;
    color: var(--myavana-blueberry);
    line-height: 1.4;
}

.myavana-onboarding-actions {
    display: flex;
    gap: 16px;
    justify-content: center;
    margin-top: 40px;
    flex-wrap: wrap;
}

.myavana-onboarding-btn {
    padding: 16px 32px;
    border: none;
    border-radius: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-size: 14px;
    font-family: 'Archivo', sans-serif;
}

.myavana-onboarding-btn-primary {
    background: linear-gradient(135deg, var(--myavana-coral) 0%, #d4956f 100%);
    color: var(--myavana-white);
    min-width: 140px;
}

.myavana-onboarding-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(231, 166, 144, 0.4);
}

.myavana-onboarding-btn-secondary {
    background: var(--myavana-stone);
    color: var(--myavana-blueberry);
    border: 2px solid var(--myavana-sand);
}

.myavana-onboarding-btn-secondary:hover {
    background: var(--myavana-sand);
    transform: translateY(-1px);
}

.myavana-onboarding-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

.myavana-progress-bar {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: rgba(231, 166, 144, 0.2);
}

.myavana-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--myavana-coral), #d4956f);
    width: 0%;
    transition: width 0.5s ease;
}

.myavana-skip-link {
    position: absolute;
    top: 20px;
    right: 20px;
    background: rgba(255, 255, 255, 0.9);
    border: none;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 12px;
    color: var(--myavana-blueberry);
    cursor: pointer;
    transition: all 0.2s ease;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.myavana-skip-link:hover {
    background: var(--myavana-white);
    transform: scale(1.05);
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .myavana-onboarding-container {
        width: 98%;
        height: 95vh;
        border-radius: 12px;
    }

    .myavana-onboarding-header {
        padding: 24px 20px;
    }

    .myavana-onboarding-title {
        font-size: 22px;
    }

    .myavana-onboarding-content {
        padding: 24px 20px;
    }

    .myavana-hair-type-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .myavana-goals-grid {
        grid-template-columns: 1fr;
    }

    .myavana-onboarding-actions {
        flex-direction: column;
        align-items: center;
    }

    .myavana-onboarding-btn {
        width: 100%;
        max-width: 280px;
    }
}

/* First Entry Form Styles */
.myavana-first-entry-form {
    text-align: left;
    max-width: 500px;
    margin: 0 auto;
}

.myavana-entry-upload-area {
    margin-bottom: 24px;
    position: relative;
}

.myavana-upload-label {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 16px;
    padding: 32px 24px;
    background: var(--myavana-stone);
    border: 2px dashed var(--myavana-sand);
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
    text-align: center;
}

.myavana-upload-label:hover {
    border-color: var(--myavana-coral);
    background: var(--myavana-light-coral);
    transform: translateY(-2px);
}

.myavana-upload-icon {
    font-size: 48px;
}

.myavana-upload-text {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.myavana-upload-text strong {
    color: var(--myavana-onyx);
    font-size: 16px;
}

.myavana-upload-text span {
    color: var(--myavana-blueberry);
    font-size: 13px;
}

.myavana-photo-preview {
    position: relative;
    margin-top: 16px;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.myavana-photo-preview img {
    width: 100%;
    height: auto;
    max-height: 300px;
    object-fit: cover;
    display: block;
}

.myavana-remove-photo {
    position: absolute;
    top: 8px;
    right: 8px;
    background: var(--myavana-white);
    border: none;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    font-size: 24px;
    line-height: 1;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    transition: all 0.2s ease;
    color: var(--myavana-onyx);
}

.myavana-remove-photo:hover {
    background: var(--myavana-coral);
    color: var(--myavana-white);
    transform: scale(1.1);
}

.myavana-entry-field {
    margin-bottom: 20px;
}

.myavana-entry-field label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: var(--myavana-onyx);
    font-size: 14px;
}

.myavana-entry-field input[type="text"],
.myavana-entry-field textarea {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid var(--myavana-sand);
    border-radius: 8px;
    font-family: 'Archivo', sans-serif;
    font-size: 14px;
    transition: all 0.3s ease;
    background: var(--myavana-white);
}

.myavana-entry-field input[type="text"]:focus,
.myavana-entry-field textarea:focus {
    outline: none;
    border-color: var(--myavana-coral);
    box-shadow: 0 0 0 3px rgba(231, 166, 144, 0.1);
}

.myavana-mood-selector {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    justify-content: center;
}

.myavana-mood-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    padding: 12px 16px;
    background: var(--myavana-stone);
    border: 2px solid transparent;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.3s ease;
    min-width: 80px;
}

.myavana-mood-btn:hover {
    border-color: var(--myavana-coral);
    background: var(--myavana-sand);
    transform: translateY(-2px);
}

.myavana-mood-btn.selected {
    border-color: var(--myavana-coral);
    background: var(--myavana-light-coral);
    box-shadow: 0 4px 12px rgba(231, 166, 144, 0.3);
}

.myavana-mood-btn .emoji {
    font-size: 28px;
    display: block;
}

.myavana-mood-btn .label {
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    color: var(--myavana-blueberry);
}

.myavana-mood-btn.selected .label {
    color: var(--myavana-coral);
}

.myavana-char-count {
    display: block;
    text-align: right;
    font-size: 11px;
    color: var(--myavana-blueberry);
    margin-top: 4px;
}

.myavana-entry-tip {
    background: var(--myavana-light-coral);
    padding: 12px 16px;
    border-radius: 8px;
    font-size: 12px;
    color: var(--myavana-blueberry);
    margin-top: 16px;
}

/* Success Step Styles */
.myavana-entry-success-card {
    background: var(--myavana-stone);
    border-radius: 12px;
    padding: 20px;
    margin: 24px auto;
    max-width: 400px;
}

.myavana-success-stats {
    display: flex;
    justify-content: space-around;
    gap: 16px;
    margin: 32px 0;
    flex-wrap: wrap;
}

.myavana-success-stats .stat-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
}

.myavana-success-stats .stat-icon {
    font-size: 32px;
}

.myavana-success-stats .stat-number {
    font-family: 'Archivo Black', sans-serif;
    font-size: 28px;
    color: var(--myavana-coral);
    font-weight: 900;
}

.myavana-success-stats .stat-label {
    font-size: 11px;
    text-transform: uppercase;
    color: var(--myavana-blueberry);
    font-weight: 600;
    letter-spacing: 0.5px;
}

/* Accessibility */
@media (prefers-reduced-motion: reduce) {
    .myavana-onboarding-overlay,
    .myavana-onboarding-container,
    .myavana-onboarding-step,
    .myavana-onboarding-header::before {
        animation: none;
        transition: none;
    }
}
</style>

<div class="myavana-onboarding-overlay" id="myavanaOnboardingOverlay">
    <div class="myavana-onboarding-container">
        <button class="myavana-skip-link" id="myavanaSkipOnboarding">Skip Tour</button>

        <div class="myavana-onboarding-header">
            <img src="<?php echo esc_url(MYAVANA_URL); ?>assets/images/myavana-primary-logo.png"
                 alt="MYAVANA Logo" class="myavana-onboarding-logo">
            <h1 class="myavana-onboarding-title">Welcome <?php echo esc_html($user_name); ?>!</h1>
            <p class="myavana-onboarding-subtitle">Let's personalize your hair journey in just a few steps</p>
        </div>

        <div class="myavana-onboarding-content">
            <!-- Step 1: Welcome & Hair Profile (Combined) -->
            <div class="myavana-onboarding-step active" data-step="welcome">
                <span class="myavana-step-icon">💇‍♀️</span>
                <h2 class="myavana-step-title">Welcome & Hair Profile</h2>
                <p class="myavana-step-description">
                    Let's start by understanding your hair type and primary goal.
                </p>

                <!-- Hair Type Selection -->
                <div style="margin: 32px 0 16px 0;">
                    <h3 style="font-size: 16px; font-weight: 600; color: var(--myavana-onyx); margin-bottom: 16px; text-align: left;">What's your hair type?</h3>
                    <div class="myavana-hair-type-grid">
                        <div class="myavana-hair-type-option" data-hair-type="straight">
                            <span class="emoji">📏</span>
                            <span class="label">Straight</span>
                        </div>
                        <div class="myavana-hair-type-option" data-hair-type="wavy">
                            <span class="emoji">〰️</span>
                            <span class="label">Wavy</span>
                        </div>
                        <div class="myavana-hair-type-option" data-hair-type="curly">
                            <span class="emoji">🌀</span>
                            <span class="label">Curly</span>
                        </div>
                        <div class="myavana-hair-type-option" data-hair-type="coily">
                            <span class="emoji">🌪️</span>
                            <span class="label">Coily</span>
                        </div>
                    </div>
                </div>

                <!-- Primary Goal Selection -->
                <div style="margin: 24px 0;">
                    <h3 style="font-size: 16px; font-weight: 600; color: var(--myavana-onyx); margin-bottom: 16px; text-align: left;">What's your main hair goal?</h3>
                    <div class="myavana-goals-grid" style="grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));">
                        <div class="myavana-goal-option" data-goal="growth">
                            <span class="emoji">📈</span>
                            <div class="title">Hair Growth</div>
                        </div>
                        <div class="myavana-goal-option" data-goal="health">
                            <span class="emoji">✨</span>
                            <div class="title">Hair Health</div>
                        </div>
                        <div class="myavana-goal-option" data-goal="styling">
                            <span class="emoji">💄</span>
                            <div class="title">Styling</div>
                        </div>
                        <div class="myavana-goal-option" data-goal="damage">
                            <span class="emoji">🔧</span>
                            <div class="title">Repair</div>
                        </div>
                        <div class="myavana-goal-option" data-goal="color">
                            <span class="emoji">🎨</span>
                            <div class="title">Color Care</div>
                        </div>
                        <div class="myavana-goal-option" data-goal="texture">
                            <span class="emoji">🌊</span>
                            <div class="title">Texture</div>
                        </div>
                    </div>
                </div>

                <div class="myavana-onboarding-actions">
                    <button class="myavana-onboarding-btn myavana-onboarding-btn-secondary" onclick="jQuery('#myavanaSkipOnboarding').click()">
                        Skip for Now
                    </button>
                    <button class="myavana-onboarding-btn myavana-onboarding-btn-primary myavana-next-step-btn" disabled>
                        Continue
                    </button>
                </div>
            </div>

            <!-- Step 2: Preferences (Optional Fields) -->
            <div class="myavana-onboarding-step" data-step="preferences">
                <span class="myavana-step-icon">✨</span>
                <h2 class="myavana-step-title">Your Hair Preferences</h2>
                <p class="myavana-step-description">
                    These are optional but help us personalize your experience better.
                </p>

                <!-- Hair Length Selection (Optional) -->
                <div style="margin: 32px 0 16px 0;">
                    <h3 style="font-size: 16px; font-weight: 600; color: var(--myavana-onyx); margin-bottom: 16px; text-align: left;">What's your current hair length? <span style="font-size: 13px; color: var(--myavana-blueberry); font-weight: 400;">(Optional)</span></h3>
                    <div class="myavana-hair-type-grid">
                        <div class="myavana-length-option myavana-hair-type-option" data-length="short">
                            <span class="emoji">✂️</span>
                            <span class="label">Short</span>
                        </div>
                        <div class="myavana-length-option myavana-hair-type-option" data-length="medium">
                            <span class="emoji">💁‍♀️</span>
                            <span class="label">Medium</span>
                        </div>
                        <div class="myavana-length-option myavana-hair-type-option" data-length="long">
                            <span class="emoji">👩‍🦰</span>
                            <span class="label">Long</span>
                        </div>
                        <div class="myavana-length-option myavana-hair-type-option" data-length="very_long">
                            <span class="emoji">🧜‍♀️</span>
                            <span class="label">Very Long</span>
                        </div>
                    </div>
                </div>

                <!-- Current Hair Concern (Optional) -->
                <div style="margin: 24px 0;">
                    <h3 style="font-size: 16px; font-weight: 600; color: var(--myavana-onyx); margin-bottom: 16px; text-align: left;">Any specific hair concerns? <span style="font-size: 13px; color: var(--myavana-blueberry); font-weight: 400;">(Optional)</span></h3>
                    <div class="myavana-goals-grid" style="grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));">
                        <div class="myavana-concern-option myavana-goal-option" data-concern="dryness">
                            <span class="emoji">💧</span>
                            <div class="title">Dryness</div>
                        </div>
                        <div class="myavana-concern-option myavana-goal-option" data-concern="breakage">
                            <span class="emoji">⚠️</span>
                            <div class="title">Breakage</div>
                        </div>
                        <div class="myavana-concern-option myavana-goal-option" data-concern="frizz">
                            <span class="emoji">🌪️</span>
                            <div class="title">Frizz</div>
                        </div>
                        <div class="myavana-concern-option myavana-goal-option" data-concern="thinning">
                            <span class="emoji">📉</span>
                            <div class="title">Thinning</div>
                        </div>
                        <div class="myavana-concern-option myavana-goal-option" data-concern="oiliness">
                            <span class="emoji">💦</span>
                            <div class="title">Oiliness</div>
                        </div>
                        <div class="myavana-concern-option myavana-goal-option" data-concern="split_ends">
                            <span class="emoji">✂️</span>
                            <div class="title">Split Ends</div>
                        </div>
                    </div>
                </div>

                <!-- Optional Name Field -->
                <div style="margin: 24px auto; max-width: 400px;">
                    <h3 style="font-size: 16px; font-weight: 600; color: var(--myavana-onyx); margin-bottom: 16px; text-align: left;">What should we call you? <span style="font-size: 13px; color: var(--myavana-blueberry); font-weight: 400;">(Optional)</span></h3>
                    <input type="text" id="myavanaNameInput" placeholder="Your preferred name"
                           style="width: 100%; padding: 12px 16px; border: 2px solid var(--myavana-sand); border-radius: 8px; font-family: 'Archivo', sans-serif; font-size: 14px;">
                </div>

                <div class="myavana-onboarding-actions">
                    <button class="myavana-onboarding-btn myavana-onboarding-btn-secondary myavana-prev-step-btn">
                        Back
                    </button>
                    <button class="myavana-onboarding-btn myavana-onboarding-btn-primary myavana-next-step-btn">
                        Continue
                    </button>
                </div>
            </div>

            <!-- Step 3: Complete -->
            <div class="myavana-onboarding-step" data-step="complete">
                <span class="myavana-step-icon">🎉</span>
                <h2 class="myavana-step-title">You're All Set!</h2>
                <p class="myavana-step-description">
                    Welcome to MYAVANA! Your personalized hair journey dashboard is ready.
                </p>

                <!-- Achievement Badge -->
                <div style="margin: 32px auto; max-width: 300px; background: var(--myavana-light-coral); border-radius: 16px; padding: 32px 24px; text-align: center;">
                    <div style="font-size: 64px; margin-bottom: 16px;">🏆</div>
                    <h3 style="font-size: 20px; font-weight: 700; color: var(--myavana-coral); margin: 0 0 8px 0;">50 Points Earned!</h3>
                    <p style="font-size: 14px; color: var(--myavana-blueberry); margin: 0;">For completing onboarding</p>
                </div>

                <!-- What's Next Cards -->
                <div style="margin: 32px 0;">
                    <h3 style="font-size: 18px; font-weight: 700; color: var(--myavana-onyx); margin-bottom: 20px;">What's Next?</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; text-align: left;">
                        <div style="background: var(--myavana-stone); border-radius: 12px; padding: 20px;">
                            <div style="font-size: 32px; margin-bottom: 8px;">📸</div>
                            <h4 style="font-size: 15px; font-weight: 600; color: var(--myavana-onyx); margin: 0 0 8px 0;">Create Your First Entry</h4>
                            <p style="font-size: 13px; color: var(--myavana-blueberry); margin: 0; line-height: 1.5;">Start tracking your progress with photos and notes</p>
                        </div>
                        <div style="background: var(--myavana-stone); border-radius: 12px; padding: 20px;">
                            <div style="font-size: 32px; margin-bottom: 8px;">📊</div>
                            <h4 style="font-size: 15px; font-weight: 600; color: var(--myavana-onyx); margin: 0 0 8px 0;">Explore Dashboard</h4>
                            <p style="font-size: 13px; color: var(--myavana-blueberry); margin: 0; line-height: 1.5;">See your hair journey timeline and analytics</p>
                        </div>
                        <div style="background: var(--myavana-stone); border-radius: 12px; padding: 20px;">
                            <div style="font-size: 32px; margin-bottom: 8px;">🤖</div>
                            <h4 style="font-size: 15px; font-weight: 600; color: var(--myavana-onyx); margin: 0 0 8px 0;">Try AI Analysis</h4>
                            <p style="font-size: 13px; color: var(--myavana-blueberry); margin: 0; line-height: 1.5;">Get personalized insights powered by AI</p>
                        </div>
                    </div>
                </div>

                <div class="myavana-onboarding-actions">
                    <button class="myavana-onboarding-btn myavana-onboarding-btn-primary" id="myavanaFinishOnboarding">
                        Start My Journey
                    </button>
                </div>
            </div>
        </div>

        <div class="myavana-progress-bar">
            <div class="myavana-progress-fill" id="myavanaProgressFill" style="width: 33.33%;"></div>
        </div>
    </div>
</div>

<script>
(function($) {
    'use strict';

    let currentStep = 0;
    const steps = ['welcome', 'preferences', 'complete'];
    let onboardingData = {
        hair_type: null,
        primary_goal: null,
        hair_length: null,
        hair_concern: null,
        name: null
    };

    function updateProgress() {
        const progress = ((currentStep + 1) / steps.length) * 100;
        $('#myavanaProgressFill').css('width', progress + '%');
    }

    function showStep(stepIndex) {
        console.log('=== SHOWING STEP ===');
        console.log('Step:', steps[stepIndex], 'Index:', stepIndex);

        $('.myavana-onboarding-step').removeClass('active');
        $(`.myavana-onboarding-step[data-step="${steps[stepIndex]}"]`).addClass('active');

        // Update button states
        $('.myavana-prev-step-btn').toggle(stepIndex > 0);
        updateProgress();

        // Use setTimeout to ensure DOM is updated
        setTimeout(function() {
            // Special handling for certain steps - target buttons within active step
            const $activeStep = $('.myavana-onboarding-step.active');
            const $nextBtn = $activeStep.find('.myavana-next-step-btn');

            console.log('Active step:', $activeStep.data('step'));
            console.log('Next button found:', $nextBtn.length);
            console.log('Next button disabled state before:', $nextBtn.prop('disabled'));

            if (steps[stepIndex] === 'welcome') {
                // Welcome step: hair_type and primary_goal required
                const isDisabled = !onboardingData.hair_type || !onboardingData.primary_goal;
                $nextBtn.prop('disabled', isDisabled);
                console.log('Welcome step - Setting button disabled to:', isDisabled);
                console.log('Hair type:', onboardingData.hair_type, 'Primary goal:', onboardingData.primary_goal);
            } else {
                // Other steps have no required fields
                console.log('Preferences/Complete step - ENABLING button');
                $nextBtn.prop('disabled', false);
                $nextBtn.removeAttr('disabled'); // Extra insurance
            }

            console.log('Next button disabled state after:', $nextBtn.prop('disabled'));
            console.log('Next button HTML:', $nextBtn[0] ? $nextBtn[0].outerHTML : 'not found');
            console.log('=== END SHOWING STEP ===');
        }, 50);
    }

    function nextStep() {
        if (currentStep < steps.length - 1) {
            currentStep++;
            showStep(currentStep);

            // Save progress
            saveOnboardingProgress();
        }
    }

    function prevStep() {
        if (currentStep > 0) {
            currentStep--;
            showStep(currentStep);
        }
    }

    function saveOnboardingProgress() {
        // Get AJAX URL and nonce
        const ajaxUrl = (typeof myavanaOnboarding !== 'undefined' && myavanaOnboarding.ajax_url)
            ? myavanaOnboarding.ajax_url
            : (typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php');

        const nonce = (typeof myavanaOnboarding !== 'undefined' && myavanaOnboarding.nonce)
            ? myavanaOnboarding.nonce
            : (typeof myavana_nonce !== 'undefined' ? myavana_nonce : '');

        // Clean data for 3-step onboarding
        const cleanData = {
            hair_type: onboardingData.hair_type,
            primary_goal: onboardingData.primary_goal,
            hair_length: onboardingData.hair_length,
            hair_concern: onboardingData.hair_concern,
            name: onboardingData.name
        };

        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'myavana_onboarding_step',
                step: steps[currentStep],
                data: cleanData,
                nonce: nonce
            },
            success: function(response) {
                console.log('Progress saved:', response);
            },
            error: function(error) {
                console.error('Failed to save progress:', error);
            }
        });
    }

    function finishOnboarding() {
        console.log('=== FINISHING ONBOARDING ===');

        // Get AJAX URL and nonce
        const ajaxUrl = (typeof myavanaOnboarding !== 'undefined' && myavanaOnboarding.ajax_url)
            ? myavanaOnboarding.ajax_url
            : (typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php');

        const nonce = (typeof myavanaOnboarding !== 'undefined' && myavanaOnboarding.nonce)
            ? myavanaOnboarding.nonce
            : (typeof myavana_nonce !== 'undefined' ? myavana_nonce : '');

        // Clean data for 3-step onboarding
        const cleanData = {
            hair_type: onboardingData.hair_type,
            primary_goal: onboardingData.primary_goal,
            hair_length: onboardingData.hair_length,
            hair_concern: onboardingData.hair_concern,
            name: onboardingData.name
        };

        console.log('Sending completion data:', cleanData);
        console.log('Ajax URL:', ajaxUrl);
        console.log('Nonce:', nonce);

        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'myavana_onboarding_step',
                step: 'complete',
                data: cleanData,
                nonce: nonce
            },
            success: function(response) {
                console.log('✅ Onboarding completion response:', response);

                if (response.success) {
                    console.log('Onboarding marked as completed!');

                    $('#myavanaOnboardingOverlay').fadeOut(500, function() {
                        $(this).remove();

                        // Show success notification
                        if (window.Myavana && window.Myavana.UI) {
                            window.Myavana.UI.showNotification('🎉 Welcome to MYAVANA! Your journey begins now.', 'success');
                        }

                        // Redirect to dashboard or refresh
                        console.log('Reloading page...');
                        setTimeout(() => window.location.reload(), 1000);
                    });
                } else {
                    console.error('❌ Onboarding completion failed:', response);
                    alert('Failed to complete onboarding. Please try again.');
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ AJAX Error finishing onboarding:', {
                    status: status,
                    error: error,
                    response: xhr.responseText
                });
                alert('Error completing onboarding. Please check console and try again.');
            }
        });
    }

    function skipOnboarding() {
        if (confirm('Are you sure you want to skip the welcome tour? You can always complete it later from your profile settings.')) {
            // Get AJAX URL and nonce
            const ajaxUrl = (typeof myavanaOnboarding !== 'undefined' && myavanaOnboarding.ajax_url)
                ? myavanaOnboarding.ajax_url
                : (typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php');

            const nonce = (typeof myavanaOnboarding !== 'undefined' && myavanaOnboarding.nonce)
                ? myavanaOnboarding.nonce
                : (typeof myavana_nonce !== 'undefined' ? myavana_nonce : '');

            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'myavana_skip_onboarding',
                    nonce: nonce
                },
                success: function() {
                    $('#myavanaOnboardingOverlay').fadeOut(300, function() {
                        $(this).remove();
                    });
                }
            });
        }
    }

    // Initialize onboarding
    $(document).ready(function() {
        // Event listeners
        $(document).on('click', '.myavana-next-step-btn', nextStep);
        $(document).on('click', '.myavana-prev-step-btn', prevStep);
        $(document).on('click', '#myavanaSkipOnboarding', skipOnboarding);
        $(document).on('click', '#myavanaFinishOnboarding', finishOnboarding);

        // Hair type selection (ONLY for step 1 - welcome step)
        $(document).on('click', '.myavana-onboarding-step[data-step="welcome"] .myavana-hair-type-option', function() {
            $('.myavana-onboarding-step[data-step="welcome"] .myavana-hair-type-option').removeClass('selected');
            $(this).addClass('selected');
            onboardingData.hair_type = $(this).data('hair-type');

            // Check if both required fields are filled (only for welcome step)
            const isComplete = onboardingData.hair_type && onboardingData.primary_goal;
            $('.myavana-onboarding-step[data-step="welcome"] .myavana-next-step-btn').prop('disabled', !isComplete);

            console.log('Hair type selected:', onboardingData.hair_type, 'Can proceed:', isComplete);
        });

        // Primary goal selection (single selection for welcome step ONLY)
        $(document).on('click', '.myavana-onboarding-step[data-step="welcome"] .myavana-goal-option', function() {
            $('.myavana-onboarding-step[data-step="welcome"] .myavana-goal-option').removeClass('selected');
            $(this).addClass('selected');
            onboardingData.primary_goal = $(this).data('goal');

            // Check if both required fields are filled (only for welcome step)
            const isComplete = onboardingData.hair_type && onboardingData.primary_goal;
            $('.myavana-onboarding-step[data-step="welcome"] .myavana-next-step-btn').prop('disabled', !isComplete);

            console.log('Primary goal selected:', onboardingData.primary_goal, 'Can proceed:', isComplete);
        });

        // Hair length selection (optional for preferences step)
        $(document).on('click', '.myavana-length-option', function() {
            $('.myavana-length-option').removeClass('selected');
            $(this).addClass('selected');
            onboardingData.hair_length = $(this).data('length');
            console.log('Hair length selected:', onboardingData.hair_length);
        });

        // Hair concern selection (optional for preferences step)
        $(document).on('click', '.myavana-concern-option', function() {
            $('.myavana-concern-option').removeClass('selected');
            $(this).addClass('selected');
            onboardingData.hair_concern = $(this).data('concern');
            console.log('Hair concern selected:', onboardingData.hair_concern);
        });

        // Name input handler for preferences step (optional)
        $('#myavanaNameInput').on('input', function() {
            onboardingData.name = $(this).val().trim();
            console.log('Name entered:', onboardingData.name);
        });

        // Keyboard navigation
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                skipOnboarding();
            } else if (e.key === 'ArrowRight' || e.key === 'Enter') {
                const $nextBtn = $('.myavana-onboarding-step.active .myavana-next-step-btn:visible:not(:disabled)');
                if ($nextBtn.length) {
                    nextStep();
                }
            } else if (e.key === 'ArrowLeft') {
                const $prevBtn = $('.myavana-onboarding-step.active .myavana-prev-step-btn:visible');
                if ($prevBtn.length) {
                    prevStep();
                }
            }
        });

        // Initialize first step
        showStep(0);

        // Focus management for accessibility
        $('#myavanaOnboardingOverlay').focus();

        // Global test function for easy testing
        window.testMyavanaOnboarding = function() {
            console.log('Starting MYAVANA Onboarding test...');
            $('#myavanaOnboardingOverlay').show();
            showStep(0);
        };

        // Add test button for development
        if (window.location.search.includes('debug=1')) {
            $('body').append('<button id="testOnboardingBtn" style="position:fixed;top:50px;right:10px;z-index:99999;background:green;color:white;padding:10px;border-radius:5px;border:none;cursor:pointer;">🎯 Test Onboarding</button>');
            $('#testOnboardingBtn').click(function() {
                window.testMyavanaOnboarding();
            });
        }

        console.log('MYAVANA: Onboarding overlay template loaded');
    });

})(jQuery);
</script>