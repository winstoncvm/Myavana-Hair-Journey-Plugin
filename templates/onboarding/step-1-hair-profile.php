<?php
/**
 * Onboarding Step 1: Hair Profile
 * Collects hair type, concerns, length, and notes
 */

if (!defined('ABSPATH')) exit;
?>

<div class="onboarding-container">
    <div class="onboarding-card">
        <div class="onboarding-header">
            <div class="onboarding-logo">MYAVANA</div>

            <div class="onboarding-progress">
                <div class="progress-step active"></div>
                <div class="progress-step"></div>
                <div class="progress-step"></div>
            </div>

            <h1>Tell Us About Your Hair</h1>
            <p>Help us personalize your experience</p>
        </div>

        <div class="onboarding-body">
            <form id="onboardingStep1Form" class="onboarding-form">
                <?php wp_nonce_field('myavana_onboarding', 'onboarding_nonce'); ?>

                <!-- Hair Type Section -->
                <div class="form-section">
                    <h2 class="form-section-title">What's Your Hair Type?</h2>
                    <p class="form-section-description">
                        Select the pattern that best matches your natural hair texture. This helps us provide personalized recommendations.
                    </p>

                    <div class="option-grid">
                        <label class="option-card">
                            <input type="radio" name="hairType" value="1A" required>
                            <div class="option-icon">💧</div>
                            <div class="option-title">Type 1 (Straight)</div>
                            <div class="option-description">1A, 1B, 1C</div>
                        </label>

                        <label class="option-card">
                            <input type="radio" name="hairType" value="2A" required>
                            <div class="option-icon">〰️</div>
                            <div class="option-title">Type 2 (Wavy)</div>
                            <div class="option-description">2A, 2B, 2C</div>
                        </label>

                        <label class="option-card">
                            <input type="radio" name="hairType" value="3A" required>
                            <div class="option-icon">🌀</div>
                            <div class="option-title">Type 3 (Curly)</div>
                            <div class="option-description">3A, 3B, 3C</div>
                        </label>

                        <label class="option-card">
                            <input type="radio" name="hairType" value="4A" required>
                            <div class="option-icon">🔘</div>
                            <div class="option-title">Type 4 (Coily)</div>
                            <div class="option-description">4A, 4B, 4C</div>
                        </label>
                    </div>
                </div>

                <!-- Hair Concerns Section -->
                <div class="form-section">
                    <h2 class="form-section-title">What Are Your Main Hair Concerns?</h2>
                    <p class="form-section-description">
                        Select all that apply. We'll help you address these challenges.
                    </p>

                    <div class="option-grid">
                        <label class="option-card">
                            <input type="checkbox" name="concerns[]" value="dryness">
                            <div class="option-icon">🏜️</div>
                            <div class="option-title">Dryness</div>
                            <div class="option-description">Lack of moisture</div>
                        </label>

                        <label class="option-card">
                            <input type="checkbox" name="concerns[]" value="breakage">
                            <div class="option-icon">💔</div>
                            <div class="option-title">Breakage</div>
                            <div class="option-description">Hair snapping</div>
                        </label>

                        <label class="option-card">
                            <input type="checkbox" name="concerns[]" value="thinning">
                            <div class="option-icon">📉</div>
                            <div class="option-title">Thinning</div>
                            <div class="option-description">Hair loss</div>
                        </label>

                        <label class="option-card">
                            <input type="checkbox" name="concerns[]" value="scalp_health">
                            <div class="option-icon">🔬</div>
                            <div class="option-title">Scalp Health</div>
                            <div class="option-description">Dandruff, itching</div>
                        </label>

                        <label class="option-card">
                            <input type="checkbox" name="concerns[]" value="frizz">
                            <div class="option-icon">⚡</div>
                            <div class="option-title">Frizz</div>
                            <div class="option-description">Unmanageable</div>
                        </label>

                        <label class="option-card">
                            <input type="checkbox" name="concerns[]" value="damage">
                            <div class="option-icon">🔥</div>
                            <div class="option-title">Damage</div>
                            <div class="option-description">Heat/chemical</div>
                        </label>
                    </div>
                </div>

                <!-- Current Hair Details -->
                <div class="form-section">
                    <h2 class="form-section-title">Current Hair Details</h2>

                    <div class="form-group">
                        <label class="form-label" for="hairLength">Current Hair Length (inches)</label>
                        <input
                            type="number"
                            id="hairLength"
                            name="hairLength"
                            class="form-input"
                            placeholder="e.g., 12"
                            min="0"
                            step="0.5"
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="additionalNotes">Additional Notes (Optional)</label>
                        <textarea
                            id="additionalNotes"
                            name="additionalNotes"
                            class="form-input form-textarea"
                            placeholder="Tell us anything else about your hair that might be helpful..."
                        ></textarea>
                    </div>
                </div>
            </form>
        </div>

        <div class="onboarding-footer">
            <button type="button" class="btn btn-secondary" id="step1BackBtn" style="visibility: hidden;">
                Back
            </button>

            <div class="step-indicator">Step 1 of 3</div>

            <button type="button" class="btn btn-primary" id="step1NextBtn">
                Next: Set Your Goals
            </button>
        </div>
    </div>
</div>
