<?php
/**
 * Onboarding Step 2: Hair Goals
 * Collects 1-3 hair goals with metadata
 */

if (!defined('ABSPATH')) exit;
?>

<div class="onboarding-container">
    <div class="onboarding-card">
        <div class="onboarding-header">
            <div class="onboarding-logo">MYAVANA</div>

            <div class="onboarding-progress">
                <div class="progress-step active"></div>
                <div class="progress-step active"></div>
                <div class="progress-step"></div>
            </div>

            <h1>Set Your Hair Goals</h1>
            <p>What do you want to achieve with your hair?</p>
        </div>

        <div class="onboarding-body">
            <h2 class="form-section-title">Your Hair Goals</h2>
            <p class="form-section-description">
                Add up to 3 goals to focus on. You can always add or modify goals later from your profile.
            </p>

            <form id="onboardingStep2Form" class="onboarding-form">
                <?php wp_nonce_field('myavana_onboarding', 'onboarding_nonce'); ?>

                <div class="goal-list" id="goalList">
                    <!-- Goal 1 (default) -->
                    <div class="goal-card" data-goal-index="0">
                        <div class="goal-card-header">
                            <span class="goal-number">Goal 1</span>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Goal Title</label>
                            <input
                                type="text"
                                class="form-input goal-title"
                                placeholder="e.g., Grow hair to waist length"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label class="form-label">Category</label>
                            <select class="form-select goal-category" required>
                                <option value="">Select a category</option>
                                <option value="growth">Growth</option>
                                <option value="health">Health</option>
                                <option value="moisture">Moisture</option>
                                <option value="strength">Strength</option>
                                <option value="styling">Styling</option>
                                <option value="retention">Retention</option>
                                <option value="volume">Volume</option>
                                <option value="other">Other</option>
                            </select>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Start Date</label>
                                <input
                                    type="date"
                                    class="form-input goal-start-date"
                                    required
                                >
                            </div>

                            <div class="form-group">
                                <label class="form-label">Target Date</label>
                                <input
                                    type="date"
                                    class="form-input goal-target-date"
                                    required
                                >
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Description (Optional)</label>
                            <textarea
                                class="form-textarea goal-description"
                                placeholder="Describe your goal and why it's important to you..."
                            ></textarea>
                        </div>
                    </div>
                </div>

                <button type="button" class="add-goal-btn" id="addGoalBtn">
                    <span>+</span>
                    <span>Add Another Goal</span>
                </button>

                <div class="popular-goals">
                    <div class="form-label" style="width: 100%; margin-bottom: 8px;">Popular Goals:</div>
                    <button type="button" class="popular-goal-chip" data-goal="Grow hair 6 inches">Grow 6 inches</button>
                    <button type="button" class="popular-goal-chip" data-goal="Achieve healthy scalp">Healthy scalp</button>
                    <button type="button" class="popular-goal-chip" data-goal="Reduce breakage">Reduce breakage</button>
                    <button type="button" class="popular-goal-chip" data-goal="Increase moisture retention">More moisture</button>
                    <button type="button" class="popular-goal-chip" data-goal="Define natural curls">Define curls</button>
                    <button type="button" class="popular-goal-chip" data-goal="Strengthen hair">Strengthen hair</button>
                </div>
            </form>
        </div>

        <div class="onboarding-footer">
            <button type="button" class="btn btn-secondary" id="step2BackBtn">
                Back
            </button>

            <div class="step-indicator">Step 2 of 3</div>

            <button type="button" class="btn btn-primary" id="step2NextBtn">
                Next: Create Routine
            </button>
        </div>
    </div>
</div>
