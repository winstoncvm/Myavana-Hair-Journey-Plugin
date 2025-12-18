/**
 * Profile Edit Fixes
 * Fixes profile edit offcanvas loading, saving, and closing
 *
 * @package Myavana_Hair_Journey
 * @since 2.3.6
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        initializeProfileEditFixes();
    });

    /**
     * Initialize profile edit fixes
     */
    function initializeProfileEditFixes() {
        fixProfileDataLoading();
        fixProfileSave();
        fixCloseButton();
        exposeGlobalFunctions();

        console.log('✅ Profile edit fixes initialized');
    }

    /**
     * Fix profile data loading into form
     */
    function fixProfileDataLoading() {
        // Override the loadProfileData function if it exists
        if (window.myavanaSidebarProfile) {
            const originalLoadData = window.myavanaSidebarProfile.loadData;

            window.myavanaSidebarProfile.loadData = function() {
                loadProfileDataFromDOM();
                if (originalLoadData) {
                    originalLoadData.call(window.myavanaSidebarProfile);
                }
            };
        }

        // Load data when offcanvas opens
        $(document).on('profileOffcanvasOpened', function() {
            loadProfileDataFromDOM();
        });
    }

    /**
     * Load profile data from DOM elements
     */
    function loadProfileDataFromDOM() {
        console.log('Loading profile data into form...');

        // Try to get data from various sources
        let profileData = {};

        // Method 1: From window.myavanaProfileData
        if (typeof window.myavanaProfileData !== 'undefined') {
            profileData = window.myavanaProfileData;
        }

        // Method 2: Extract from DOM elements in sidebar
        if (Object.keys(profileData).length === 0) {
            profileData = extractProfileDataFromDOM();
        }

        // Method 3: Fetch via AJAX if still empty
        if (Object.keys(profileData).length === 0) {
            fetchProfileDataAjax();
            return; // fetchProfileDataAjax will handle population
        }

        // Populate form with data
        populateProfileForm(profileData);
    }

    /**
     * Extract profile data from DOM elements
     */
    function extractProfileDataFromDOM() {
        const data = {};

        // Hair type
        const hairTypeElement = $('.sidebar-stat-value:contains("Hair Type")').closest('.sidebar-stat').find('.sidebar-stat-label');
        if (hairTypeElement.length) {
            data.hairType = hairTypeElement.text().trim();
        }

        // Hair porosity
        const porosityElement = $('.sidebar-detail-value').eq(0);
        if (porosityElement.length) {
            data.hairPorosity = porosityElement.text().trim();
        }

        // Hair length
        const lengthElement = $('.sidebar-detail-value').eq(1);
        if (lengthElement.length) {
            data.hairLength = lengthElement.text().trim();
        }

        // Journey stage
        const stageElement = $('.sidebar-journey-stage');
        if (stageElement.length) {
            data.journeyStage = stageElement.text().trim();
        }

        // Bio/About
        const bioElement = $('.sidebar-about-text');
        if (bioElement.length) {
            data.bio = bioElement.text().trim();
        }

        // Goals
        data.hairGoals = [];
        $('.sidebar-goal-item').each(function() {
            const goalText = $(this).find('.sidebar-goal-text').text().trim();
            if (goalText && goalText !== 'No goals set yet') {
                data.hairGoals.push(goalText);
            }
        });

        // Routine
        data.currentRoutine = [];
        $('.sidebar-routine-item').each(function() {
            const routineName = $(this).find('.sidebar-routine-name').text().trim();
            const routineFreq = $(this).find('.sidebar-routine-freq').text().trim();
            if (routineName) {
                data.currentRoutine.push({
                    name: routineName,
                    frequency: routineFreq.toLowerCase().replace(/\s+/g, '')
                });
            }
        });

        console.log('Extracted profile data from DOM:', data);
        return data;
    }

    /**
     * Fetch profile data via AJAX
     */
    function fetchProfileDataAjax() {
        console.log('Fetching profile data via AJAX...');

        $.ajax({
            url: window.myavanaAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'myavana_get_profile_data',
                nonce: window.myavanaAjax.nonce
            },
            success: function(response) {
                if (response.success && response.data) {
                    populateProfileForm(response.data);
                } else {
                    console.error('Failed to load profile data:', response);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error loading profile:', error);
            }
        });
    }

    /**
     * Populate profile form with data
     */
    function populateProfileForm(data) {
        console.log('Populating form with:', data);

        // Hair type
        if (data.hairType || data.hair_type) {
            $('#sidebar-hair-type').val(data.hairType || data.hair_type);
        }

        // Hair porosity
        if (data.hairPorosity || data.hair_porosity) {
            $('#sidebar-hair-porosity').val(data.hairPorosity || data.hair_porosity);
        }

        // Hair length
        if (data.hairLength || data.hair_length) {
            $('#sidebar-hair-length').val(data.hairLength || data.hair_length);
        }

        // Journey stage
        if (data.journeyStage || data.hair_journey_stage) {
            $('#sidebar-journey-stage').val(data.journeyStage || data.hair_journey_stage);
        }

        // Bio
        if (data.bio) {
            $('#sidebar-bio').val(data.bio);
        }

        // Goals
        if (data.hairGoals || data.hair_goals) {
            const goals = data.hairGoals || data.hair_goals;
            renderGoals(Array.isArray(goals) ? goals : goals.split(','));
        }

        // Routine
        if (data.currentRoutine || data.current_routine) {
            const routine = data.currentRoutine || data.current_routine;
            renderRoutine(Array.isArray(routine) ? routine : []);
        }

        console.log('✅ Form populated successfully');
    }

    /**
     * Render goals in form
     */
    function renderGoals(goals) {
        const container = $('#sidebar-goals-edit-list');
        container.empty();

        if (!goals || goals.length === 0) return;

        goals.forEach((goal, index) => {
            const goalText = typeof goal === 'string' ? goal : (goal.title || goal.goal_title || '');
            if (!goalText) return;

            const goalHTML = `
                <div class="sidebar-goal-edit-item" data-index="${index}">
                    <span class="sidebar-goal-edit-text">${escapeHtml(goalText)}</span>
                    <button type="button" class="sidebar-goal-remove-btn" data-index="${index}">
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
     * Render routine in form
     */
    function renderRoutine(routine) {
        const container = $('#sidebar-routine-edit-list');
        container.empty();

        if (!routine || routine.length === 0) return;

        routine.forEach((step, index) => {
            const stepHTML = `
                <div class="sidebar-routine-edit-item" data-index="${index}">
                    <div class="sidebar-routine-edit-info">
                        <span class="sidebar-routine-edit-name">${escapeHtml(step.name || '')}</span>
                        <span class="sidebar-routine-edit-freq">${getFrequencyLabel(step.frequency || '')}</span>
                    </div>
                    <button type="button" class="sidebar-routine-remove-btn" data-index="${index}">
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
     * Get frequency label
     */
    function getFrequencyLabel(frequency) {
        const labels = {
            'daily': 'Daily',
            'weekly': 'Weekly',
            'biweekly': 'Bi-weekly',
            'monthly': 'Monthly',
            'asneeded': 'As Needed'
        };
        return labels[frequency] || frequency || 'Daily';
    }

    /**
     * Fix profile save
     */
    function fixProfileSave() {
        // Override form submission
        $(document).off('submit', '#sidebar-profile-edit-form');
        $(document).on('submit', '#sidebar-profile-edit-form', function(e) {
            e.preventDefault();
            saveProfileDataFixed();
            return false;
        });

        // Also attach to save button directly
        $(document).off('click', '#save-profile-edit');
        $(document).on('click', '#save-profile-edit', function(e) {
            e.preventDefault();
            saveProfileDataFixed();
        });
    }

    /**
     * Save profile data with fixed nonce handling
     */
    function saveProfileDataFixed() {
        console.log('Saving profile data...');

        // Show loading state
        const submitBtn = $('#save-profile-edit');
        const originalText = submitBtn.html();
        submitBtn.html('<span class="spinner"></span> Saving...').prop('disabled', true);

        // Determine which nonce to use and log all available
        console.log('Available nonces:', {
            myavanaAjax: window.myavanaAjax?.nonce,
            myavanaProfileAjax: window.myavanaProfileAjax?.nonce
        });

        let nonce = null;
        if (window.myavanaAjax && window.myavanaAjax.nonce) {
            nonce = window.myavanaAjax.nonce;
            console.log('Using myavanaAjax.nonce:', nonce);
        } else if (window.myavanaProfileAjax && window.myavanaProfileAjax.nonce) {
            nonce = window.myavanaProfileAjax.nonce;
            console.log('Using myavanaProfileAjax.nonce:', nonce);
        } else {
            console.error('No nonce available!');
            showNotification('Security token missing. Please refresh the page.', 'error');
            submitBtn.html(originalText).prop('disabled', false);
            return;
        }

        // Collect form data
        const formData = {
            action: 'myavana_save_profile',
            nonce: nonce,
            hair_type: $('#sidebar-hair-type').val() || '',
            hair_porosity: $('#sidebar-hair-porosity').val() || '',
            hair_length: $('#sidebar-hair-length').val() || '',
            journey_stage: $('#sidebar-journey-stage').val() || '',
            bio: $('#sidebar-bio').val() || '',
            hair_goals: collectGoalsData(),
            current_routine: collectRoutineData()
        };

        console.log('Sending data:', formData);
        console.log('AJAX URL:', window.myavanaAjax?.ajax_url);

        // Send AJAX request
        $.ajax({
            url: window.myavanaAjax.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                console.log('Save response:', response);

                if (response.success) {
                    showNotification('Profile updated successfully!', 'success');

                    // Close offcanvas after short delay
                    setTimeout(function() {
                        closeProfileEditOffcanvasFixed();

                        // Clear cache and reload
                        if (typeof myavana_clear_user_cache !== 'undefined') {
                            myavana_clear_user_cache(formData.user_id);
                        }

                        // Reload page to reflect changes
                        location.reload();
                    }, 1000);
                } else {
                    showNotification(response.data || 'Error updating profile', 'error');
                    submitBtn.html(originalText).prop('disabled', false);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('Save error:', textStatus, errorThrown, jqXHR.responseText);
                showNotification('Failed to save. Please check console for details.', 'error');
                submitBtn.html(originalText).prop('disabled', false);
            }
        });
    }

    /**
     * Collect goals data
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
     * Collect routine data
     */
    function collectRoutineData() {
        const routine = [];
        $('#sidebar-routine-edit-list .sidebar-routine-edit-item').each(function() {
            const name = $(this).find('.sidebar-routine-edit-name').text().trim();
            const freq = $(this).find('.sidebar-routine-edit-freq').text().trim().toLowerCase().replace(/\s+/g, '');
            if (name) {
                routine.push({ name: name, frequency: freq || 'daily' });
            }
        });
        return routine;
    }

    /**
     * Fix close button
     */
    function fixCloseButton() {
        // Remove all existing handlers
        $(document).off('click', '.offcanvas-close-hjn, .offcanvas-overlay-hjn, #cancel-profile-edit');

        // Attach new handler
        $(document).on('click', '.offcanvas-close-hjn, .offcanvas-overlay-hjn, #cancel-profile-edit', function(e) {
            const target = $(e.target);

            // Don't close if clicking inside offcanvas content
            if (target.closest('.offcanvas-hjn').length && !target.hasClass('offcanvas-overlay-hjn') && !target.hasClass('offcanvas-close-hjn') && !target.attr('id') === 'cancel-profile-edit') {
                return;
            }

            e.preventDefault();
            e.stopPropagation();
            closeProfileEditOffcanvasFixed();
        });

        // ESC key handler
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && $('.offcanvas-hjn.active').length) {
                closeProfileEditOffcanvasFixed();
            }
        });
    }

    /**
     * Close profile edit offcanvas (fixed version)
     */
    function closeProfileEditOffcanvasFixed() {
        console.log('Closing profile edit offcanvas');

        $('.offcanvas-overlay-hjn').removeClass('active');
        $('.offcanvas-hjn.profile-edit').removeClass('active');
        $('body').css('overflow', '');

        // Trigger event
        $(document).trigger('profileOffcanvasClosed');
    }

    /**
     * Show notification
     */
    function showNotification(message, type = 'info') {
        const notification = $(`
            <div class="myavana-notification myavana-notification-${type}">
                <span>${escapeHtml(message)}</span>
            </div>
        `);

        $('body').append(notification);

        setTimeout(function() {
            notification.addClass('show');
        }, 10);

        setTimeout(function() {
            notification.removeClass('show');
            setTimeout(function() {
                notification.remove();
            }, 300);
        }, 3000);
    }

    /**
     * Escape HTML
     */
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    /**
     * Expose global functions
     */
    function exposeGlobalFunctions() {
        // Override global openProfileEditOffcanvas
        window.openProfileEditOffcanvas = function() {
            console.log('Opening profile edit offcanvas (fixed)');

            // Show overlay and offcanvas
            $('.offcanvas-overlay-hjn').addClass('active');
            $('.offcanvas-hjn.profile-edit').addClass('active');
            $('body').css('overflow', 'hidden');

            // Load data
            loadProfileDataFromDOM();

            // Trigger event
            $(document).trigger('profileOffcanvasOpened');
        };

        window.closeProfileEditOffcanvas = closeProfileEditOffcanvasFixed;

        // Expose utilities
        window.myavanaProfileEditFixes = {
            open: window.openProfileEditOffcanvas,
            close: closeProfileEditOffcanvasFixed,
            loadData: loadProfileDataFromDOM,
            save: saveProfileDataFixed
        };
    }

})(jQuery);
