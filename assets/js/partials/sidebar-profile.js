/**
 * MYAVANA Sidebar Profile JavaScript
 * Handles profile edit offcanvas functionality
 */

(function($) {
    'use strict';

    // Initialize on document ready
    $(document).ready(function() {
        initializeSidebarProfile();
    });

    /**
     * Initialize all profile functionality
     */
    function initializeSidebarProfile() {
        // Attach event listeners
        attachEventListeners();

        // Initialize any existing data
        loadProfileData();
    }

    /**
     * Attach all event listeners
     */
    function attachEventListeners() {
        // Close offcanvas
        $(document).on('click', '.offcanvas-close-hjn, .offcanvas-overlay-hjn', function() {
            closeProfileEditOffcanvas();
        });

        // ESC key to close
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                closeProfileEditOffcanvas();
            }
        });

        // Profile form submission
        $(document).on('submit', '#sidebar-profile-edit-form', function(e) {
            e.preventDefault();
            saveProfileData();
        });

        // Cancel button
        $(document).on('click', '#cancel-profile-edit', function(e) {
            e.preventDefault();
            closeProfileEditOffcanvas();
        });

        // Add goal button
        $(document).on('click', '.add-goal-btn-sidebar', function() {
            openGoalModal();
        });

        // Add routine button
        $(document).on('click', '.add-routine-btn-sidebar', function() {
            openRoutineModal();
        });

        // Remove goal
        $(document).on('click', '.remove-goal-chip', function() {
            const index = $(this).data('index');
            removeGoal(index);
        });

        // Remove routine
        $(document).on('click', '.remove-routine-item', function() {
            const index = $(this).data('index');
            removeRoutine(index);
        });
    }

    /**
     * Load current profile data into offcanvas
     */
    function loadProfileData() {
        // Get profile data from global variable if available
        if (typeof window.myavanaProfileData !== 'undefined') {
            const data = window.myavanaProfileData;

            // Populate form fields
            if (data.hairType) $('#sidebar-hair-type').val(data.hairType);
            if (data.hairPorosity) $('#sidebar-hair-porosity').val(data.hairPorosity);
            if (data.hairLength) $('#sidebar-hair-length').val(data.hairLength);
            if (data.journeyStage) $('#sidebar-journey-stage').val(data.journeyStage);
            if (data.bio) $('#sidebar-bio').val(data.bio);

            // Load goals
            if (data.hairGoals && data.hairGoals.length > 0) {
                renderGoalsInForm(data.hairGoals);
            }

            // Load routine
            if (data.currentRoutine && data.currentRoutine.length > 0) {
                renderRoutineInForm(data.currentRoutine);
            }
        }
    }

    /**
     * Render goals in edit form
     */
    function renderGoalsInForm(goals) {
        const container = $('#sidebar-goals-edit-list');
        container.empty();

        goals.forEach((goal, index) => {
            const goalHTML = `
                <div class="sidebar-goal-edit-item">
                    <span class="sidebar-goal-edit-text">${escapeHtml(goal.title || goal)}</span>
                    <button type="button" class="sidebar-goal-remove-btn remove-goal-chip" data-index="${index}">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>
            `;
            container.append(goalHTML);
        });
    }

    /**
     * Render routine in edit form
     */
    function renderRoutineInForm(routine) {
        const container = $('#sidebar-routine-edit-list');
        container.empty();

        routine.forEach((step, index) => {
            const stepHTML = `
                <div class="sidebar-routine-edit-item">
                    <div class="sidebar-routine-edit-info">
                        <span class="sidebar-routine-edit-name">${escapeHtml(step.name)}</span>
                        <span class="sidebar-routine-edit-freq">${getFrequencyLabel(step.frequency)}</span>
                    </div>
                    <button type="button" class="sidebar-routine-remove-btn remove-routine-item" data-index="${index}">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>
            `;
            container.append(stepHTML);
        });
    }

    /**
     * Get frequency label from value
     */
    function getFrequencyLabel(frequency) {
        const labels = {
            'daily': 'Daily',
            'weekly': 'Weekly',
            'biweekly': 'Bi-weekly',
            'monthly': 'Monthly',
            'asneeded': 'As Needed'
        };
        return labels[frequency] || frequency;
    }

    /**
     * Save profile data via AJAX
     */
    function saveProfileData() {
        // Validate form
        if (!validateProfileForm()) {
            return;
        }

        // Show loading state
        const submitBtn = $('#save-profile-edit');
        const originalText = submitBtn.html();
        submitBtn.html('<span class="spinner"></span> Saving...').prop('disabled', true);

        // Collect form data
        const formData = {
            action: 'myavana_save_profile',
            nonce: myavanaProfileAjax.nonce,
            hair_type: $('#sidebar-hair-type').val(),
            hair_porosity: $('#sidebar-hair-porosity').val(),
            hair_length: $('#sidebar-hair-length').val(),
            journey_stage: $('#sidebar-journey-stage').val(),
            bio: $('#sidebar-bio').val(),
            hair_goals: collectGoalsData(),
            current_routine: collectRoutineData()
        };

        // Send AJAX request
        $.ajax({
            url: myavanaProfileAjax.ajaxurl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    // Show success message
                    showNotification('Profile updated successfully!', 'success');

                    // Close offcanvas
                    setTimeout(function() {
                        closeProfileEditOffcanvas();

                        // Reload page to reflect changes
                        location.reload();
                    }, 1000);
                } else {
                    showNotification(response.data || 'Error updating profile', 'error');
                    submitBtn.html(originalText).prop('disabled', false);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('Profile update error:', textStatus, errorThrown);
                showNotification('Failed to connect to server. Please try again.', 'error');
                submitBtn.html(originalText).prop('disabled', false);
            }
        });
    }

    /**
     * Validate profile form
     */
    function validateProfileForm() {
        // Check required fields
        const hairType = $('#sidebar-hair-type').val();

        if (!hairType) {
            showNotification('Please select your hair type', 'error');
            return false;
        }

        return true;
    }

    /**
     * Collect goals data from form
     */
    function collectGoalsData() {
        const goals = [];
        $('#sidebar-goals-edit-list .sidebar-goal-edit-item').each(function() {
            const goalText = $(this).find('.sidebar-goal-edit-text').text().trim();
            if (goalText) {
                goals.push(goalText);
            }
        });
        return goals;
    }

    /**
     * Collect routine data from form
     */
    function collectRoutineData() {
        const routine = [];

        // Get existing routine from global data
        if (typeof window.myavanaProfileData !== 'undefined' &&
            window.myavanaProfileData.currentRoutine) {

            const existingRoutine = window.myavanaProfileData.currentRoutine;
            $('#sidebar-routine-edit-list .sidebar-routine-edit-item').each(function(index) {
                if (existingRoutine[index]) {
                    routine.push(existingRoutine[index]);
                }
            });
        }

        return routine;
    }

    /**
     * Remove goal from list
     */
    function removeGoal(index) {
        const goalItems = $('#sidebar-goals-edit-list .sidebar-goal-edit-item');
        if (goalItems.length > index) {
            $(goalItems[index]).fadeOut(300, function() {
                $(this).remove();
            });
        }
    }

    /**
     * Remove routine from list
     */
    function removeRoutine(index) {
        const routineItems = $('#sidebar-routine-edit-list .sidebar-routine-edit-item');
        if (routineItems.length > index) {
            $(routineItems[index]).fadeOut(300, function() {
                $(this).remove();
            });
        }
    }

    /**
     * Open goal modal
     * Reuses functionality from profile-shortcode.js
     */
    function openGoalModal() {
        // Close profile offcanvas first
        closeProfileEditOffcanvas();

        // Trigger existing goal modal if available
        if ($('#hair-goal-modal').length) {
            $('#goal-index').val('');
            $('#hair-goal-form')[0].reset();
            $('#hair-goal-modal').show();
        } else {
            // Show simple prompt as fallback
            const goal = prompt('Enter your hair goal:');
            if (goal && goal.trim()) {
                addGoalToList(goal.trim());
            }
        }
    }

    /**
     * Open routine modal
     * Reuses functionality from profile-shortcode.js
     */
    function openRoutineModal() {
        // Close profile offcanvas first
        closeProfileEditOffcanvas();

        // Trigger existing routine modal if available
        if ($('#routine-step-modal').length) {
            $('#step-modal-title').text('Add Routine Step');
            $('#step-index').val('');
            $('#routine-step-form')[0].reset();
            $('#routine-step-modal').show();
        } else {
            // Show simple prompt as fallback
            const routineName = prompt('Enter routine step name:');
            if (routineName && routineName.trim()) {
                addRoutineToList({
                    name: routineName.trim(),
                    frequency: 'daily'
                });
            }
        }
    }

    /**
     * Add goal to edit list
     */
    function addGoalToList(goalText) {
        const container = $('#sidebar-goals-edit-list');
        const index = container.find('.sidebar-goal-edit-item').length;

        const goalHTML = `
            <div class="sidebar-goal-edit-item">
                <span class="sidebar-goal-edit-text">${escapeHtml(goalText)}</span>
                <button type="button" class="sidebar-goal-remove-btn remove-goal-chip" data-index="${index}">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
        `;

        container.append(goalHTML);
    }

    /**
     * Add routine to edit list
     */
    function addRoutineToList(step) {
        const container = $('#sidebar-routine-edit-list');
        const index = container.find('.sidebar-routine-edit-item').length;

        const stepHTML = `
            <div class="sidebar-routine-edit-item">
                <div class="sidebar-routine-edit-info">
                    <span class="sidebar-routine-edit-name">${escapeHtml(step.name)}</span>
                    <span class="sidebar-routine-edit-freq">${getFrequencyLabel(step.frequency)}</span>
                </div>
                <button type="button" class="sidebar-routine-remove-btn remove-routine-item" data-index="${index}">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
        `;

        container.append(stepHTML);
    }

    /**
     * Show notification
     */
    function showNotification(message, type = 'info') {
        // Create notification element
        const notification = $(`
            <div class="sidebar-notification sidebar-notification-${type}">
                <span class="sidebar-notification-text">${escapeHtml(message)}</span>
            </div>
        `);

        // Add to body
        $('body').append(notification);

        // Show with animation
        setTimeout(function() {
            notification.addClass('show');
        }, 10);

        // Hide after 3 seconds
        setTimeout(function() {
            notification.removeClass('show');
            setTimeout(function() {
                notification.remove();
            }, 300);
        }, 3000);
    }

    /**
     * Escape HTML to prevent XSS
     */
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    /**
     * Global function to open profile edit offcanvas
     * Called from avatar edit button
     */
    window.openProfileEditOffcanvas = function() {
        // Load current data
        loadProfileData();

        // Show overlay and offcanvas
        $('.offcanvas-overlay-hjn').addClass('active');
        $('.offcanvas-hjn.profile-edit').addClass('active');

        // Prevent body scroll
        $('body').css('overflow', 'hidden');
    };

    /**
     * Close profile edit offcanvas
     */
    function closeProfileEditOffcanvas() {
        // Hide overlay and offcanvas
        $('.offcanvas-overlay-hjn').removeClass('active');
        $('.offcanvas-hjn.profile-edit').removeClass('active');

        // Re-enable body scroll
        $('body').css('overflow', '');
    }

    // Expose functions globally if needed
    window.myavanaSidebarProfile = {
        openEditOffcanvas: window.openProfileEditOffcanvas,
        closeEditOffcanvas: closeProfileEditOffcanvas,
        showNotification: showNotification
    };

})(jQuery);
