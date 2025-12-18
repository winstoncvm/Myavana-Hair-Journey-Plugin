<?php
/**
 * Onboarding Step 3: Hair Care Routine
 * Collects initial routine or allows skip
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
                <div class="progress-step active"></div>
            </div>

            <h1>Create Your Routine</h1>
            <p>Build your personalized hair care routine</p>
        </div>

        <div class="onboarding-body">
            <h2 class="form-section-title">Your Hair Care Routine</h2>
            <p class="form-section-description">
                Create a routine to help you stay consistent. You can always modify or add more routines later.
            </p>

            <form id="onboardingStep3Form" class="onboarding-form">
                <?php wp_nonce_field('myavana_onboarding', 'onboarding_nonce'); ?>

                <div class="routine-builder">
                    <div class="form-group">
                        <label class="form-label">Routine Name</label>
                        <input
                            type="text"
                            id="routineName"
                            class="form-input"
                            placeholder="e.g., My Wash Day Routine"
                        >
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Frequency</label>
                            <select id="frequency" class="form-select">
                                <option value="">Select frequency</option>
                                <option value="Daily">Daily</option>
                                <option value="Every Other Day">Every Other Day</option>
                                <option value="Weekly">Weekly</option>
                                <option value="Bi-Weekly">Bi-Weekly</option>
                                <option value="Monthly">Monthly</option>
                                <option value="As Needed">As Needed</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Duration (minutes)</label>
                            <input
                                type="number"
                                id="duration"
                                class="form-input"
                                placeholder="30"
                                min="5"
                                step="5"
                            >
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Time of Day</label>
                        <div class="time-of-day-selector">
                            <button type="button" class="time-chip" data-time="Morning">🌅 Morning</button>
                            <button type="button" class="time-chip" data-time="Afternoon">☀️ Afternoon</button>
                            <button type="button" class="time-chip" data-time="Evening">🌆 Evening</button>
                            <button type="button" class="time-chip" data-time="Night">🌙 Night</button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Routine Steps</label>
                        <div class="steps-list" id="stepsList">
                            <div class="step-item">
                                <div class="step-number">1</div>
                                <input
                                    type="text"
                                    class="form-input step-input"
                                    placeholder="e.g., Pre-poo with oil"
                                >
                            </div>

                            <div class="step-item">
                                <div class="step-number">2</div>
                                <input
                                    type="text"
                                    class="form-input step-input"
                                    placeholder="e.g., Shampoo with sulfate-free cleanser"
                                >
                            </div>

                            <div class="step-item">
                                <div class="step-number">3</div>
                                <input
                                    type="text"
                                    class="form-input step-input"
                                    placeholder="e.g., Deep condition for 30 minutes"
                                >
                            </div>
                        </div>

                        <button type="button" class="add-step-btn" id="addStepBtn" style="margin-top: 12px;">
                            <span>+</span>
                            <span>Add Step</span>
                        </button>
                    </div>
                </div>
            </form>

            <div class="skip-option">
                <p>Not ready to create a routine yet? You can set this up later from your dashboard.</p>
                <button type="button" class="skip-btn" id="skipRoutineBtn">Skip for Now</button>
            </div>
        </div>

        <div class="onboarding-footer">
            <button type="button" class="btn btn-secondary" id="step3BackBtn">
                Back
            </button>

            <div class="step-indicator">Step 3 of 3</div>

            <button type="button" class="btn btn-primary" id="step3FinishBtn">
                Finish Setup
            </button>
        </div>
    </div>
</div>
