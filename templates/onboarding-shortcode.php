<?php
/**
 * MYAVANA Onboarding Shortcode
 *
 * Multi-step onboarding flow for new users
 * Usage: [myavana_onboarding]
 *
 * @package Myavana_Hair_Journey
 * @version 2.3.6
 */

if (!defined('ABSPATH')) exit;

// Check if user is logged in
$user_id = get_current_user_id();
if (!$user_id) {
    return '<p>Please log in to access onboarding.</p>';
}

// Check if onboarding is already completed
$completed = myavana_user_completed_onboarding($user_id);
if ($completed) {
    return '<p>You have already completed onboarding! <a href="' . home_url('/hair-journey/') . '">Go to your Hair Journey</a></p>';
}

?>

<div class="myavana-onboarding-wrapper" id="myavanaOnboarding">
    <!-- Step 1: Hair Profile -->
    <div class="myavana-onboarding-step" id="onboardingStep1" data-step="1" style="display: none;">
        <?php include MYAVANA_PATH . 'templates/onboarding/step-1-hair-profile.php'; ?>
    </div>

    <!-- Step 2: Hair Goals -->
    <div class="myavana-onboarding-step" id="onboardingStep2" data-step="2" style="display: none;">
        <?php include MYAVANA_PATH . 'templates/onboarding/step-2-goals.php'; ?>
    </div>

    <!-- Step 3: Routine -->
    <div class="myavana-onboarding-step" id="onboardingStep3" data-step="3" style="display: none;">
        <?php include MYAVANA_PATH . 'templates/onboarding/step-3-routine.php'; ?>
    </div>

    <!-- Loading State -->
    <div class="myavana-onboarding-loading" id="onboardingLoading" style="display: flex;">
        <div class="loading-spinner"></div>
        <p>Loading your personalized onboarding...</p>
    </div>
</div>

<style>
.myavana-onboarding-wrapper {
    min-height: 100vh;
    background: linear-gradient(135deg, var(--myavana-light-coral) 0%, var(--myavana-stone) 100%);
}

.myavana-onboarding-loading {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
    gap: 24px;
}

.loading-spinner {
    width: 48px;
    height: 48px;
    border: 4px solid var(--myavana-stone);
    border-top-color: var(--myavana-coral);
    border-radius: 50%;
    animation: spinner-rotation 0.8s linear infinite;
}

@keyframes spinner-rotation {
    to { transform: rotate(360deg); }
}

.myavana-onboarding-loading p {
    font-family: 'Archivo', sans-serif;
    font-size: 14px;
    color: var(--myavana-onyx);
}
</style>

<script>
// Initialize onboarding on page load
document.addEventListener('DOMContentLoaded', function() {
    if (typeof MyavanaOnboarding !== 'undefined') {
        MyavanaOnboarding.init();
    }
});
</script>
<?php
