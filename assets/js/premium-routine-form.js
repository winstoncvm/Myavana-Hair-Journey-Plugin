/**
 * MYAVANA Premium Routine Form
 * Luxury UI for creating hair care routines
 * @version 1.0.0
 */

(function($) {
    'use strict';

    window.MyavanaPremiumRoutineForm = {
        formData: {},
        steps: [],

        /**
         * Open the premium routine form
         */
        open: function(prefillData) {
            console.log('[Premium Routine Form] Opening with prefill data:', prefillData);
            this.formData = prefillData || {};
            this.steps = [];
            this.createModal();
        },

        /**
         * Create the premium modal structure
         */
        createModal: function() {
            // Remove existing modal if present
            $('#myavanaPremiumRoutineModal').remove();

            const modalHTML = `
                <div id="myavanaPremiumRoutineModal" class="myavana-premium-modal-overlay">
                    <div class="myavana-premium-modal">
                        <!-- Header -->
                        <div class="premium-modal-header">
                            <div class="header-content">
                                <h2 class="modal-title">✨ New Hair Routine</h2>
                                <p class="modal-subtitle">Create your care regimen</p>
                            </div>
                            <button class="modal-close" onclick="MyavanaPremiumRoutineForm.close()">
                                <svg viewBox="0 0 24 24" width="24" height="24">
                                    <path fill="currentColor" d="M19,6.41L17.59,5L12,10.59L6.41,5L5,6.41L10.59,12L5,17.59L6.41,19L12,13.41L17.59,19L19,17.59L13.41,12L19,6.41Z"/>
                                </svg>
                            </button>
                        </div>

                        <!-- Form Body -->
                        <div class="premium-form-body">
                            <form id="premiumRoutineForm">
                                <div class="form-step active">
                                    <div class="step-content">
                                        <!-- Routine Name -->
                                        <div class="form-group">
                                            <label class="form-label">
                                                Routine Name
                                                <span class="required">*</span>
                                            </label>
                                            <input
                                                type="text"
                                                id="routineName"
                                                class="form-input"
                                                placeholder="e.g., Weekly Deep Condition, Wash Day Routine"
                                                maxlength="100"
                                            >
                                        </div>

                                        <!-- Frequency -->
                                        <div class="form-group">
                                            <label class="form-label">
                                                How often?
                                                <span class="required">*</span>
                                            </label>
                                            <select id="routineFrequency" class="form-input">
                                                <option value="">Select frequency...</option>
                                                <option value="Daily">Daily</option>
                                                <option value="Every Other Day">Every Other Day</option>
                                                <option value="Twice a Week">Twice a Week</option>
                                                <option value="Weekly">Weekly</option>
                                                <option value="Bi-Weekly">Bi-Weekly</option>
                                                <option value="Monthly">Monthly</option>
                                                <option value="As Needed">As Needed</option>
                                            </select>
                                        </div>

                                        <!-- Routine Steps -->
                                        <div class="form-group">
                                            <label class="form-label">
                                                Routine Steps
                                                <span class="required">*</span>
                                            </label>
                                            <div id="routineStepsList"></div>
                                            <button type="button" class="btn-add-step" onclick="MyavanaPremiumRoutineForm.addStep()">
                                                + Add Step
                                            </button>
                                        </div>

                                        <!-- Notes -->
                                        <div class="form-group">
                                            <label class="form-label">Notes (Optional)</label>
                                            <textarea
                                                id="routineNotes"
                                                class="form-textarea"
                                                rows="3"
                                                placeholder="Any special instructions or tips..."
                                                maxlength="500"
                                            ></textarea>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- Footer -->
                        <div class="premium-modal-footer">
                            <button type="button" class="btn-secondary" onclick="MyavanaPremiumRoutineForm.close()">
                                Cancel
                            </button>
                            <button type="button" class="btn-primary" onclick="MyavanaPremiumRoutineForm.saveRoutine()">
                                <svg viewBox="0 0 24 24" width="20" height="20">
                                    <path fill="currentColor" d="M9,20.42L2.79,14.21L5.62,11.38L9,14.77L18.88,4.88L21.71,7.71L9,20.42Z"/>
                                </svg>
                                Save Routine
                            </button>
                        </div>
                    </div>
                </div>
            `;

            $('body').append(modalHTML);
            $('body').addClass('modal-open');

            // Set form values if prefill data exists
            if (this.formData.name) {
                $('#myavanaPremiumRoutineModal #routineName').val(this.formData.name);
            }
            if (this.formData.frequency) {
                $('#myavanaPremiumRoutineModal #routineFrequency').val(this.formData.frequency);
            }
            if (this.formData.notes) {
                $('#myavanaPremiumRoutineModal #routineNotes').val(this.formData.notes);
            }

            // Animate in
            setTimeout(() => {
                $('#myavanaPremiumRoutineModal').addClass('show');
            }, 50);

            // Add initial step
            this.addStep();
        },

        /**
         * Add a routine step
         */
        addStep: function() {
            const stepIndex = this.steps.length;
            const stepHTML = `
                <div class="routine-step-item" data-step-index="${stepIndex}">
                    <div class="step-number">${stepIndex + 1}</div>
                    <div class="step-inputs">
                        <input
                            type="text"
                            class="step-title-input"
                            placeholder="Step title (e.g., Cleanse, Condition)"
                            maxlength="100"
                        >
                        <input
                            type="text"
                            class="step-products-input"
                            placeholder="Products (comma-separated)"
                            maxlength="200"
                        >
                    </div>
                    <button type="button" class="btn-remove-step" onclick="MyavanaPremiumRoutineForm.removeStep(${stepIndex})">
                        <svg viewBox="0 0 24 24" width="20" height="20">
                            <path fill="currentColor" d="M19,6.41L17.59,5L12,10.59L6.41,5L5,6.41L10.59,12L5,17.59L6.41,19L12,13.41L17.59,19L19,17.59L13.41,12L19,6.41Z"/>
                        </svg>
                    </button>
                </div>
            `;

            $('#myavanaPremiumRoutineModal #routineStepsList').append(stepHTML);
            this.steps.push({ title: '', products: '' });
        },

        /**
         * Remove a routine step
         */
        removeStep: function(index) {
            $(`#myavanaPremiumRoutineModal .routine-step-item[data-step-index="${index}"]`).remove();
            this.steps.splice(index, 1);
            this.renumberSteps();
        },

        /**
         * Renumber steps after removal
         */
        renumberSteps: function() {
            $('#myavanaPremiumRoutineModal .routine-step-item').each(function(idx) {
                $(this).attr('data-step-index', idx);
                $(this).find('.step-number').text(idx + 1);
            });
        },

        /**
         * Save routine
         */
        saveRoutine: function() {
            const name = $('#myavanaPremiumRoutineModal #routineName').val();
            const frequency = $('#myavanaPremiumRoutineModal #routineFrequency').val();
            const notes = $('#myavanaPremiumRoutineModal #routineNotes').val();

            // Validation
            if (!name || name.trim().length === 0) {
                this.showNotification('Please enter a routine name', 'error');
                return;
            }
            if (!frequency) {
                this.showNotification('Please select a frequency', 'error');
                return;
            }

            // Collect steps
            const steps = [];
            $('#myavanaPremiumRoutineModal .routine-step-item').each(function() {
                const title = $(this).find('.step-title-input').val();
                const products = $(this).find('.step-products-input').val();
                if (title) {
                    steps.push({ title, products });
                }
            });

            if (steps.length === 0) {
                this.showNotification('Please add at least one step', 'error');
                return;
            }

            this.showNotification('Saving your routine...', 'info');

            // Prepare form data
            const formData = new FormData();
            formData.append('action', 'myavana_add_routine');
            formData.append('routine_name', name);
            formData.append('frequency', frequency);
            formData.append('notes', notes);
            formData.append('steps', JSON.stringify(steps));

            const settings = window.myavanaTimelineSettings || {};
            formData.append('security', settings.addRoutineNonce || settings.nonce);

            // Submit via AJAX
            $.ajax({
                url: settings.ajaxUrl || '/wp-admin/admin-ajax.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (response) => {
                    if (response.success) {
                        this.showNotification('Routine saved successfully! ✨', 'success');
                        setTimeout(() => {
                            this.close();
                            location.reload();
                        }, 1500);
                    } else {
                        this.showNotification(response.data || 'Failed to save routine', 'error');
                    }
                },
                error: () => {
                    this.showNotification('Network error. Please try again.', 'error');
                }
            });
        },

        /**
         * Close modal
         */
        close: function() {
            $('#myavanaPremiumRoutineModal').removeClass('show');
            setTimeout(() => {
                $('#myavanaPremiumRoutineModal').remove();
                $('body').removeClass('modal-open');
            }, 300);
        },

        /**
         * Show notification
         */
        showNotification: function(message, type = 'info') {
            $('.premium-notification').remove();

            const icons = {
                success: '✓',
                error: '✗',
                warning: '⚠',
                info: 'ℹ'
            };

            const notification = $(`
                <div class="premium-notification ${type}">
                    <span class="notification-icon">${icons[type]}</span>
                    <span class="notification-message">${message}</span>
                </div>
            `);

            $('body').append(notification);
            setTimeout(() => notification.addClass('show'), 100);

            if (type === 'success' || type === 'info') {
                setTimeout(() => {
                    notification.removeClass('show');
                    setTimeout(() => notification.remove(), 300);
                }, 3000);
            }
        }
    };

    // Global shortcut
    window.createRoutine = () => MyavanaPremiumRoutineForm.open();

})(jQuery);
