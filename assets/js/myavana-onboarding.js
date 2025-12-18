/**
 * MYAVANA Onboarding System - JavaScript Controller
 * Handles multi-step onboarding flow with validation and AJAX
 * @version 2.3.6
 */

(function($) {
    'use strict';

    window.MyavanaOnboarding = {
        settings: {},
        currentStep: 1,
        formData: {},
        goalCount: 1,
        stepCount: 3,
        selectedTime: null,
        maxGoals: 3,

        /**
         * Initialize onboarding
         */
        init: function() {
            this.settings = window.myavanaOnboardingSettings || {};

            console.log('[Onboarding] Initializing...');

            // Check if onboarding wrapper exists
            if (!$('#myavanaOnboarding').length) {
                console.error('[Onboarding] Wrapper not found');
                return;
            }

            // Check progress and show appropriate step
            this.checkProgress();
        },

        /**
         * Check onboarding progress from server
         */
        checkProgress: function() {
            const self = this;

            $.ajax({
                url: self.settings.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'myavana_get_onboarding_progress',
                    nonce: self.settings.nonce
                },
                success: function(response) {
                    if (response.success) {
                        const data = response.data;

                        // If completed, redirect
                        if (data.onboarding_completed) {
                            console.log('[Onboarding] Already completed');
                            window.location.href = self.settings.redirectUrl;
                            return;
                        }

                        // Start at last incomplete step
                        const startStep = Math.max(1, data.onboarding_step || 1);
                        self.goToStep(startStep);
                        self.bindEvents();
                    } else {
                        console.error('[Onboarding] Progress check failed:', response);
                        // Default to step 1
                        self.goToStep(1);
                        self.bindEvents();
                    }
                },
                error: function(xhr, status, error) {
                    console.error('[Onboarding] AJAX error:', error);
                    // Default to step 1
                    self.goToStep(1);
                    self.bindEvents();
                }
            });
        },

        /**
         * Bind all event handlers
         */
        bindEvents: function() {
            const self = this;

            // Step 1: Hair Profile
            this.bindStep1Events();

            // Step 2: Goals
            this.bindStep2Events();

            // Step 3: Routine
            this.bindStep3Events();

            console.log('[Onboarding] Events bound');
        },

        /**
         * Bind Step 1 events
         */
        bindStep1Events: function() {
            const self = this;

            // Option card selection
            $(document).on('click', '.option-card', function() {
                const $card = $(this);
                const $input = $card.find('input');

                if ($input.attr('type') === 'radio') {
                    // Deselect all radio cards in the same group
                    $('input[name="' + $input.attr('name') + '"]').each(function() {
                        $(this).closest('.option-card').removeClass('selected');
                    });
                    $card.addClass('selected');
                    $input.prop('checked', true);
                } else if ($input.attr('type') === 'checkbox') {
                    $input.prop('checked', !$input.prop('checked'));
                    $card.toggleClass('selected', $input.prop('checked'));
                }
            });

            // Next button
            $('#step1NextBtn').off('click').on('click', function() {
                self.submitStep1();
            });
        },

        /**
         * Bind Step 2 events
         */
        bindStep2Events: function() {
            const self = this;

            // Set default start date
            $('.goal-start-date').first().val(self.getTodayDate());

            // Add goal button
            $('#addGoalBtn').off('click').on('click', function() {
                if (self.goalCount >= self.maxGoals) {
                    alert('You can add up to 3 goals during onboarding. You can add more later!');
                    return;
                }

                self.goalCount++;
                const $goalList = $('#goalList');
                const $goalCard = self.createGoalCard(self.goalCount - 1);
                $goalList.append($goalCard);

                // Hide button if max reached
                if (self.goalCount >= self.maxGoals) {
                    $(this).hide();
                }
            });

            // Popular goals quick add
            $(document).on('click', '.popular-goal-chip', function() {
                const goalTitle = $(this).data('goal');
                const $firstEmpty = $('.goal-title').filter(function() {
                    return $(this).val() === '';
                }).first();

                if ($firstEmpty.length) {
                    $firstEmpty.val(goalTitle).focus();
                } else {
                    alert('All goal slots are filled! Remove a goal or proceed to the next step.');
                }
            });

            // Back button
            $('#step2BackBtn').off('click').on('click', function() {
                self.goToStep(1);
            });

            // Next button
            $('#step2NextBtn').off('click').on('click', function() {
                self.submitStep2();
            });
        },

        /**
         * Bind Step 3 events
         */
        bindStep3Events: function() {
            const self = this;

            // Time of day selector
            $(document).on('click', '.time-chip', function() {
                $('.time-chip').removeClass('selected');
                $(this).addClass('selected');
                self.selectedTime = $(this).data('time');
            });

            // Add step button
            $('#addStepBtn').off('click').on('click', function() {
                self.stepCount++;
                const $stepsList = $('#stepsList');
                const $stepItem = self.createStepItem(self.stepCount);
                $stepsList.append($stepItem);
            });

            // Skip button
            $('#skipRoutineBtn').off('click').on('click', function() {
                if (confirm('Are you sure you want to skip creating a routine? You can always add one later.')) {
                    self.submitStep3(true);
                }
            });

            // Back button
            $('#step3BackBtn').off('click').on('click', function() {
                self.goToStep(2);
            });

            // Finish button
            $('#step3FinishBtn').off('click').on('click', function() {
                self.submitStep3(false);
            });
        },

        /**
         * Navigate to specific step
         */
        goToStep: function(step) {
            console.log('[Onboarding] Going to step', step);

            // Hide loading
            $('#onboardingLoading').hide();

            // Hide all steps
            $('.myavana-onboarding-step').hide();

            // Show target step
            $('#onboardingStep' + step).fadeIn(300);

            this.currentStep = step;
        },

        /**
         * Submit Step 1: Hair Profile
         */
        submitStep1: function() {
            const self = this;
            const $form = $('#onboardingStep1Form');

            // Validate
            const hairType = $('input[name="hairType"]:checked').val();
            if (!hairType) {
                alert('Please select your hair type to continue');
                return;
            }

            // Collect concerns
            const concerns = [];
            $('input[name="concerns[]"]:checked').each(function() {
                concerns.push($(this).val());
            });

            // Collect data
            const data = {
                action: 'myavana_save_onboarding_step_1',
                nonce: self.settings.nonce,
                hairType: hairType,
                concerns: concerns,
                hairLength: $('#hairLength').val() || '',
                additionalNotes: $('#additionalNotes').val() || ''
            };

            // Show loading
            $('#step1NextBtn').prop('disabled', true).text('Saving...');

            // Submit via AJAX
            $.ajax({
                url: self.settings.ajaxUrl,
                type: 'POST',
                data: data,
                success: function(response) {
                    console.log('[Onboarding] Step 1 response:', response);

                    if (response.success) {
                        // Save to local storage as backup
                        localStorage.setItem('myavana_onboarding_step1', JSON.stringify(data));

                        // Go to step 2
                        self.goToStep(2);
                    } else {
                        alert(response.data || 'Error saving hair profile. Please try again.');
                    }

                    $('#step1NextBtn').prop('disabled', false).text('Next: Set Your Goals');
                },
                error: function(xhr, status, error) {
                    console.error('[Onboarding] Step 1 error:', error);
                    alert('Network error. Please try again.');
                    $('#step1NextBtn').prop('disabled', false).text('Next: Set Your Goals');
                }
            });
        },

        /**
         * Submit Step 2: Goals
         */
        submitStep2: function() {
            const self = this;

            // Collect goals
            const goals = [];
            let isValid = true;

            $('.goal-card').each(function(index) {
                const $card = $(this);
                const title = $card.find('.goal-title').val().trim();
                const category = $card.find('.goal-category').val();
                const startDate = $card.find('.goal-start-date').val();
                const targetDate = $card.find('.goal-target-date').val();
                const description = $card.find('.goal-description').val().trim();

                if (!title || !category || !startDate || !targetDate) {
                    isValid = false;
                    alert('Please complete all required fields for Goal ' + (index + 1));
                    return false;
                }

                goals.push({
                    title: title,
                    category: category,
                    startDate: startDate,
                    targetDate: targetDate,
                    description: description
                });
            });

            if (!isValid) return;

            if (goals.length === 0) {
                alert('Please add at least one goal');
                return;
            }

            // Prepare data
            const data = {
                action: 'myavana_save_onboarding_step_2',
                nonce: self.settings.nonce,
                goals: goals
            };

            // Show loading
            $('#step2NextBtn').prop('disabled', true).text('Saving...');

            // Submit via AJAX
            $.ajax({
                url: self.settings.ajaxUrl,
                type: 'POST',
                data: data,
                success: function(response) {
                    console.log('[Onboarding] Step 2 response:', response);

                    if (response.success) {
                        // Save to local storage as backup
                        localStorage.setItem('myavana_onboarding_step2', JSON.stringify(data));

                        // Go to step 3
                        self.goToStep(3);
                    } else {
                        alert(response.data || 'Error saving goals. Please try again.');
                    }

                    $('#step2NextBtn').prop('disabled', false).text('Next: Create Routine');
                },
                error: function(xhr, status, error) {
                    console.error('[Onboarding] Step 2 error:', error);
                    alert('Network error. Please try again.');
                    $('#step2NextBtn').prop('disabled', false).text('Next: Create Routine');
                }
            });
        },

        /**
         * Submit Step 3: Routine
         */
        submitStep3: function(skipped) {
            const self = this;

            let data = {
                action: 'myavana_save_onboarding_step_3',
                nonce: self.settings.nonce,
                skipped: skipped ? 'true' : 'false'
            };

            if (!skipped) {
                // Validate
                const routineName = $('#routineName').val().trim();
                const frequency = $('#frequency').val();

                if (!routineName || !frequency) {
                    alert('Please fill in the routine name and frequency');
                    return;
                }

                if (!self.selectedTime) {
                    alert('Please select a time of day for your routine');
                    return;
                }

                // Collect steps
                const steps = [];
                $('.step-input').each(function() {
                    const val = $(this).val().trim();
                    if (val) {
                        steps.push(val);
                    }
                });

                if (steps.length === 0) {
                    alert('Please add at least one step to your routine');
                    return;
                }

                // Add routine data
                data.name = routineName;
                data.frequency = frequency;
                data.duration = $('#duration').val() || 0;
                data.timeOfDay = self.selectedTime;
                data.steps = steps;
            }

            // Show loading
            const $btn = skipped ? $('#skipRoutineBtn') : $('#step3FinishBtn');
            $btn.prop('disabled', true).text('Completing...');

            // Submit via AJAX
            $.ajax({
                url: self.settings.ajaxUrl,
                type: 'POST',
                data: data,
                success: function(response) {
                    console.log('[Onboarding] Step 3 response:', response);

                    if (response.success) {
                        // Clear local storage
                        localStorage.removeItem('myavana_onboarding_step1');
                        localStorage.removeItem('myavana_onboarding_step2');

                        // Show success message
                        self.showSuccess(response.data);
                    } else {
                        alert(response.data || 'Error completing onboarding. Please try again.');
                        $btn.prop('disabled', false).text(skipped ? 'Skip for Now' : 'Finish Setup');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('[Onboarding] Step 3 error:', error);
                    alert('Network error. Please try again.');
                    $btn.prop('disabled', false).text(skipped ? 'Skip for Now' : 'Finish Setup');
                }
            });
        },

        /**
         * Show success message and redirect
         */
        showSuccess: function(data) {
            const self = this;

            // Hide current step
            $('#onboardingStep3').fadeOut(300, function() {
                // Show success message
                const $success = $('<div class="onboarding-success"></div>');
                $success.html(`
                    <div class="success-animation">
                        <div class="success-checkmark">✓</div>
                    </div>
                    <h1>Welcome to MYAVANA!</h1>
                    <p>You've earned <strong>${data.points_earned || 50} points</strong> for completing your profile!</p>
                    <p>Redirecting to your hair journey...</p>
                `);

                $('#myavanaOnboarding').html($success);

                // Redirect after 3 seconds
                setTimeout(function() {
                    window.location.href = data.redirect || self.settings.redirectUrl;
                }, 3000);
            });
        },

        /**
         * Create goal card HTML
         */
        createGoalCard: function(index) {
            const $card = $(`
                <div class="goal-card" data-goal-index="${index}">
                    <div class="goal-card-header">
                        <span class="goal-number">Goal ${index + 1}</span>
                        <button type="button" class="goal-remove">×</button>
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
                                value="${this.getTodayDate()}"
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
            `);

            // Bind remove button
            $card.find('.goal-remove').on('click', () => this.removeGoal($card));

            return $card;
        },

        /**
         * Remove goal card
         */
        removeGoal: function($card) {
            if (this.goalCount <= 1) {
                alert('You must have at least one goal!');
                return;
            }

            $card.remove();
            this.goalCount--;

            // Show add button if under max
            if (this.goalCount < this.maxGoals) {
                $('#addGoalBtn').show();
            }

            // Renumber goals
            $('.goal-card').each(function(index) {
                $(this).attr('data-goal-index', index);
                $(this).find('.goal-number').text('Goal ' + (index + 1));
            });
        },

        /**
         * Create step item HTML
         */
        createStepItem: function(stepNum) {
            const $item = $(`
                <div class="step-item">
                    <div class="step-number">${stepNum}</div>
                    <input
                        type="text"
                        class="form-input step-input"
                        placeholder="Add another step..."
                    >
                    <button type="button" class="step-remove">×</button>
                </div>
            `);

            // Bind remove button
            $item.find('.step-remove').on('click', () => this.removeStep($item));

            return $item;
        },

        /**
         * Remove step item
         */
        removeStep: function($item) {
            if (this.stepCount <= 1) {
                alert('You must have at least one step!');
                return;
            }

            $item.remove();
            this.stepCount--;

            // Renumber steps
            $('.step-item').each(function(index) {
                $(this).find('.step-number').text(index + 1);
            });
        },

        /**
         * Get today's date in YYYY-MM-DD format
         */
        getTodayDate: function() {
            const today = new Date();
            const year = today.getFullYear();
            const month = String(today.getMonth() + 1).padStart(2, '0');
            const day = String(today.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }
    };

    // Auto-initialize on DOM ready
    $(document).ready(function() {
        if ($('#myavanaOnboarding').length) {
            MyavanaOnboarding.init();
        }
    });

})(jQuery);
