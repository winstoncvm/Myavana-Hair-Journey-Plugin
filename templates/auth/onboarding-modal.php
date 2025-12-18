<?php
/**
 * MYAVANA Streamlined Onboarding Modal
 *
 * 2-step quick onboarding that actually saves data
 */

if (!defined('ABSPATH')) exit;

$user_id = get_current_user_id();
$user = wp_get_current_user();
$current_name = $user->display_name ?: $user->first_name ?: '';
?>

<style>
/* MYAVANA Onboarding Modal */
.myavana-onboarding-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(34, 35, 35, 0.9);
    backdrop-filter: blur(10px);
    z-index: 1000000;
    align-items: center;
    justify-content: center;
    animation: myavanaFadeIn 0.3s ease;
    font-family: 'Archivo', -apple-system, sans-serif;
}

.myavana-onboarding-overlay.show {
    display: flex;
}

@keyframes myavanaFadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.myavana-onboarding-card {
    background: white;
    border-radius: 20px;
    width: 95%;
    max-width: 600px;
    max-height: 90vh;
    overflow: hidden;
    box-shadow: 0 25px 80px rgba(0, 0, 0, 0.3);
    animation: myavanaSlideUp 0.4s ease;
}

@keyframes myavanaSlideUp {
    from { transform: translateY(40px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

.myavana-onboarding-header {
    background: linear-gradient(135deg, var(--myavana-coral, #e7a690) 0%, var(--myavana-light-coral, #fce5d7) 100%);
    padding: 30px;
    text-align: center;
    position: relative;
}

.myavana-onboarding-logo {
    font-family: 'Archivo Black', sans-serif;
    font-size: 24px;
    color: white;
    text-transform: uppercase;
    letter-spacing: 2px;
    margin-bottom: 8px;
}

.myavana-onboarding-title {
    color: white;
    font-size: 20px;
    font-weight: 600;
    margin: 0;
}

.myavana-onboarding-skip {
    position: absolute;
    top: 15px;
    right: 15px;
    background: rgba(255,255,255,0.2);
    border: none;
    color: white;
    padding: 8px 16px;
    border-radius: 20px;
    cursor: pointer;
    font-size: 13px;
    transition: all 0.2s ease;
}

.myavana-onboarding-skip:hover {
    background: rgba(255,255,255,0.3);
}

.myavana-onboarding-progress {
    display: flex;
    justify-content: center;
    gap: 8px;
    padding: 20px;
    background: #f8f9fa;
}

.myavana-progress-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #ddd;
    transition: all 0.3s ease;
}

.myavana-progress-dot.active {
    background: var(--myavana-coral, #e7a690);
    transform: scale(1.2);
}

.myavana-progress-dot.completed {
    background: #10b981;
}

.myavana-onboarding-body {
    padding: 30px;
    overflow-y: auto;
    max-height: 60vh;
}

.myavana-onboarding-step {
    display: none;
}

.myavana-onboarding-step.active {
    display: block;
    animation: myavanaStepFade 0.3s ease;
}

@keyframes myavanaStepFade {
    from { opacity: 0; transform: translateX(20px); }
    to { opacity: 1; transform: translateX(0); }
}

.myavana-onboarding-step h3 {
    font-size: 22px;
    font-weight: 700;
    color: var(--myavana-onyx, #222323);
    margin: 0 0 8px 0;
}

.myavana-onboarding-step p {
    color: #666;
    margin: 0 0 24px 0;
    font-size: 14px;
}

.myavana-onboarding-field {
    margin-bottom: 24px;
}

.myavana-onboarding-field label {
    display: block;
    font-weight: 600;
    color: var(--myavana-onyx, #222323);
    margin-bottom: 8px;
    font-size: 14px;
}

.myavana-onboarding-field input[type="text"] {
    width: 100%;
    padding: 14px 16px;
    border: 2px solid #eeece1;
    border-radius: 10px;
    font-size: 16px;
    transition: all 0.2s ease;
    box-sizing: border-box;
}

.myavana-onboarding-field input:focus {
    outline: none;
    border-color: var(--myavana-coral, #e7a690);
}

/* Selection Grid */
.myavana-selection-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
}

.myavana-selection-option {
    border: 2px solid #eeece1;
    border-radius: 12px;
    padding: 16px;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s ease;
    background: white;
}

.myavana-selection-option:hover {
    border-color: var(--myavana-coral, #e7a690);
    background: #fef7f4;
}

.myavana-selection-option.selected {
    border-color: var(--myavana-coral, #e7a690);
    background: linear-gradient(135deg, rgba(231,166,144,0.1) 0%, rgba(252,229,215,0.3) 100%);
}

.myavana-selection-option .icon {
    font-size: 28px;
    margin-bottom: 8px;
}

.myavana-selection-option .label {
    font-weight: 600;
    color: var(--myavana-onyx, #222323);
    font-size: 14px;
}

/* Multi-select chips */
.myavana-chips-container {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.myavana-chip {
    padding: 10px 18px;
    border: 2px solid #eeece1;
    border-radius: 25px;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 14px;
    font-weight: 500;
    background: white;
}

.myavana-chip:hover {
    border-color: var(--myavana-coral, #e7a690);
}

.myavana-chip.selected {
    border-color: var(--myavana-coral, #e7a690);
    background: var(--myavana-coral, #e7a690);
    color: white;
}

/* Footer */
.myavana-onboarding-footer {
    padding: 20px 30px;
    border-top: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.myavana-onboarding-back {
    background: none;
    border: none;
    color: #666;
    cursor: pointer;
    font-size: 14px;
    padding: 10px;
}

.myavana-onboarding-back:hover {
    color: var(--myavana-onyx, #222323);
}

.myavana-onboarding-next {
    background: linear-gradient(135deg, var(--myavana-coral, #e7a690) 0%, #d4956f 100%);
    border: none;
    color: white;
    padding: 14px 32px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 15px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.myavana-onboarding-next:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(231, 166, 144, 0.4);
}

.myavana-onboarding-next:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
}

.myavana-onboarding-next.loading {
    color: transparent;
    position: relative;
}

.myavana-onboarding-next.loading::after {
    content: '';
    position: absolute;
    width: 20px;
    height: 20px;
    top: 50%;
    left: 50%;
    margin: -10px 0 0 -10px;
    border: 2px solid rgba(255,255,255,0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Mobile */
@media (max-width: 480px) {
    .myavana-onboarding-card {
        width: 100%;
        height: 100%;
        max-height: 100vh;
        border-radius: 0;
    }

    .myavana-selection-grid {
        grid-template-columns: 1fr;
    }

    .myavana-onboarding-body {
        max-height: calc(100vh - 250px);
    }
}
</style>

<div class="myavana-onboarding-overlay" id="myavanaOnboardingOverlay">
    <div class="myavana-onboarding-card">
        <div class="myavana-onboarding-header">
            <button class="myavana-onboarding-skip" id="onboardingSkip">Skip for now</button>
            <div class="myavana-onboarding-logo">MYAVANA</div>
            <h2 class="myavana-onboarding-title">Let's Personalize Your Journey</h2>
        </div>

        <div class="myavana-onboarding-progress">
            <div class="myavana-progress-dot active" data-step="1"></div>
            <div class="myavana-progress-dot" data-step="2"></div>
        </div>

        <div class="myavana-onboarding-body">
            <!-- Step 1: Name & Hair Type -->
            <div class="myavana-onboarding-step active" data-step="1">
                <h3>Welcome! Tell us about yourself</h3>
                <p>This helps us personalize your hair care recommendations.</p>

                <div class="myavana-onboarding-field">
                    <label for="onboarding-name">What should we call you?</label>
                    <input type="text" id="onboarding-name" placeholder="Your name" value="<?php echo esc_attr($current_name); ?>">
                </div>

                <div class="myavana-onboarding-field">
                    <label>What's your hair type?</label>
                    <div class="myavana-selection-grid" id="hair-type-selection">
                        <div class="myavana-selection-option" data-value="straight">
                            <div class="icon">〰️</div>
                            <div class="label">Straight</div>
                        </div>
                        <div class="myavana-selection-option" data-value="wavy">
                            <div class="icon">🌊</div>
                            <div class="label">Wavy</div>
                        </div>
                        <div class="myavana-selection-option" data-value="curly">
                            <div class="icon">🌀</div>
                            <div class="label">Curly</div>
                        </div>
                        <div class="myavana-selection-option" data-value="coily">
                            <div class="icon">➰</div>
                            <div class="label">Coily/Kinky</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 2: Goals & Texture -->
            <div class="myavana-onboarding-step" data-step="2">
                <h3>Almost done! Your hair goals</h3>
                <p>Select all that apply - we'll tailor your experience.</p>

                <div class="myavana-onboarding-field">
                    <label>What are your main hair goals?</label>
                    <div class="myavana-chips-container" id="hair-goals-selection">
                        <div class="myavana-chip" data-value="growth">Hair Growth</div>
                        <div class="myavana-chip" data-value="moisture">More Moisture</div>
                        <div class="myavana-chip" data-value="strength">Stronger Hair</div>
                        <div class="myavana-chip" data-value="damage">Repair Damage</div>
                        <div class="myavana-chip" data-value="definition">Better Definition</div>
                        <div class="myavana-chip" data-value="frizz">Reduce Frizz</div>
                        <div class="myavana-chip" data-value="shine">More Shine</div>
                        <div class="myavana-chip" data-value="volume">Add Volume</div>
                    </div>
                </div>

                <div class="myavana-onboarding-field">
                    <label>How would you describe your hair texture?</label>
                    <div class="myavana-selection-grid" id="hair-texture-selection">
                        <div class="myavana-selection-option" data-value="fine">
                            <div class="icon">🪶</div>
                            <div class="label">Fine</div>
                        </div>
                        <div class="myavana-selection-option" data-value="medium">
                            <div class="icon">🧵</div>
                            <div class="label">Medium</div>
                        </div>
                        <div class="myavana-selection-option" data-value="coarse">
                            <div class="icon">🧶</div>
                            <div class="label">Coarse</div>
                        </div>
                        <div class="myavana-selection-option" data-value="mixed">
                            <div class="icon">🎨</div>
                            <div class="label">Mixed</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="myavana-onboarding-footer">
            <button class="myavana-onboarding-back" id="onboardingBack" style="visibility: hidden;">← Back</button>
            <button class="myavana-onboarding-next" id="onboardingNext">Continue</button>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    const overlay = $('#myavanaOnboardingOverlay');
    let currentStep = 1;
    const totalSteps = 2;

    // Data storage
    const onboardingData = {
        name: $('#onboarding-name').val(),
        hair_type: '',
        hair_goals: [],
        hair_texture: ''
    };

    // Show onboarding
    function showOnboarding() {
        overlay.addClass('show');
        $('body').css('overflow', 'hidden');
    }

    // Hide onboarding
    function hideOnboarding() {
        overlay.removeClass('show');
        $('body').css('overflow', '');
    }

    // Update progress dots
    function updateProgress() {
        $('.myavana-progress-dot').each(function() {
            const step = $(this).data('step');
            $(this).removeClass('active completed');
            if (step < currentStep) {
                $(this).addClass('completed');
            } else if (step === currentStep) {
                $(this).addClass('active');
            }
        });
    }

    // Show step
    function showStep(step) {
        currentStep = step;
        $('.myavana-onboarding-step').removeClass('active');
        $(`.myavana-onboarding-step[data-step="${step}"]`).addClass('active');
        updateProgress();

        // Update back button visibility
        $('#onboardingBack').css('visibility', step > 1 ? 'visible' : 'hidden');

        // Update next button text
        $('#onboardingNext').text(step === totalSteps ? 'Get Started' : 'Continue');
    }

    // Validate current step
    function validateStep() {
        if (currentStep === 1) {
            onboardingData.name = $('#onboarding-name').val().trim();
            if (!onboardingData.name) {
                alert('Please enter your name');
                return false;
            }
            if (!onboardingData.hair_type) {
                alert('Please select your hair type');
                return false;
            }
            return true;
        }

        if (currentStep === 2) {
            // Goals and texture are optional but recommended
            return true;
        }

        return true;
    }

    // Save onboarding data
    function saveOnboarding() {
        const nextBtn = $('#onboardingNext');
        nextBtn.addClass('loading').prop('disabled', true);

        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'myavana_save_onboarding',
                nonce: '<?php echo wp_create_nonce('myavana_onboarding_save'); ?>',
                name: onboardingData.name,
                hair_type: onboardingData.hair_type,
                hair_goals: onboardingData.hair_goals.join(','),
                hair_texture: onboardingData.hair_texture
            },
            success: function(response) {
                if (response.success) {
                    // Redirect to hair journey page
                    window.location.href = '<?php echo home_url('/hair-journey/?welcome=1'); ?>';
                } else {
                    alert(response.data.message || 'Something went wrong. Please try again.');
                    nextBtn.removeClass('loading').prop('disabled', false);
                }
            },
            error: function() {
                alert('Connection error. Please try again.');
                nextBtn.removeClass('loading').prop('disabled', false);
            }
        });
    }

    // Skip onboarding
    function skipOnboarding() {
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'myavana_skip_onboarding',
                nonce: '<?php echo wp_create_nonce('myavana_onboarding'); ?>'
            },
            success: function() {
                hideOnboarding();
            }
        });
    }

    // Event handlers
    $('#onboardingNext').on('click', function() {
        if (!validateStep()) return;

        if (currentStep < totalSteps) {
            showStep(currentStep + 1);
        } else {
            saveOnboarding();
        }
    });

    $('#onboardingBack').on('click', function() {
        if (currentStep > 1) {
            showStep(currentStep - 1);
        }
    });

    $('#onboardingSkip').on('click', function() {
        if (confirm('Are you sure? Completing setup helps us personalize your experience.')) {
            skipOnboarding();
        }
    });

    // Single selection (hair type, texture)
    $('#hair-type-selection .myavana-selection-option').on('click', function() {
        $('#hair-type-selection .myavana-selection-option').removeClass('selected');
        $(this).addClass('selected');
        onboardingData.hair_type = $(this).data('value');
    });

    $('#hair-texture-selection .myavana-selection-option').on('click', function() {
        $('#hair-texture-selection .myavana-selection-option').removeClass('selected');
        $(this).addClass('selected');
        onboardingData.hair_texture = $(this).data('value');
    });

    // Multi-selection (goals)
    $('#hair-goals-selection .myavana-chip').on('click', function() {
        $(this).toggleClass('selected');
        onboardingData.hair_goals = [];
        $('#hair-goals-selection .myavana-chip.selected').each(function() {
            onboardingData.hair_goals.push($(this).data('value'));
        });
    });

    // Name input
    $('#onboarding-name').on('input', function() {
        onboardingData.name = $(this).val().trim();
    });

    // Auto-show if needed
    <?php if (get_user_meta($user_id, 'myavana_show_onboarding', true)): ?>
    showOnboarding();
    <?php endif; ?>

    // Global function to trigger onboarding
    window.showMyavanaOnboarding = showOnboarding;
});
</script>
