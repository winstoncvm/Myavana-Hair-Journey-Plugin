/**
 * Myavana Profile Shortcode JavaScript
 *
 * This file contains all JavaScript functionality for the profile shortcode
 * Split from the main PHP file for better organization and maintainability
 */

jQuery(document).ready(function($) {
    // Check if myavanaProfileAjax is available
    if (typeof myavanaProfileAjax === 'undefined') {
        console.error('myavanaProfileAjax is not defined. Please check script loading order.');
        return;
    }

    // Register FilePond plugins
    if (typeof FilePond !== 'undefined') {
        FilePond.registerPlugin(FilePondPluginImagePreview);
    }

    // Initialize FilePond for photo upload
    const uploadInput = $('#photo-upload-analysis')[0];
    if (uploadInput && typeof FilePond !== 'undefined') {
        const pond = FilePond.create(uploadInput, {
            acceptedFileTypes: ['image/jpeg', 'image/png'],
            maxFileSize: '10MB',
            allowMultiple: false,
            instantUpload: false,
            imagePreviewHeight: 200,
            stylePanelLayout: 'compact',
            styleLoadIndicatorPosition: 'center bottom',
            styleButtonRemoveItemPosition: 'center bottom',
            onaddfile: (error, file) => {
                if (!error) {
                    $('#upload-setup-analysis').show();
                    $('.analysis-interface').hide();
                }
            },
            onremovefile: () => {
                $('#upload-setup-analysis').hide();
                $('.analysis-interface').show();
            }
        });
    }

    // Initialize Select2 for products
    if (typeof $.fn.select2 !== 'undefined') {
        $('#step-products').select2({
            placeholder: "Select products",
            allowClear: true,
            width: '100%',
            dropdownCssClass: 'myavana-select2-dropdown'
        });
    }

    // Toggle edit form
    $('#edit-profile-btn').on('click', function(e) {
        e.preventDefault();
        $('.profile-header').fadeOut(300, function() {
            $('#profile-edit').removeClass('hidden').fadeIn(300);
            window.scrollTo(0, 0);
        });
    });

    $('#cancel-edit-btn').on('click', function(e) {
        e.preventDefault();
        $('#profile-edit').fadeOut(300, function() {
            $('.profile-header').removeClass('hidden').fadeIn(300);
        });
    });

    // Section edit toggles - THIS IS WHERE THE MAIN BUG IS
    $('.section-edit').on('click', function() {
        const section = $(this).data('section');

        if (section === 'analysis') {
            const analysisButton = $(this);
            const isActive = analysisButton.hasClass('cancel-active');

            if (!isActive) {
                // Switch to active/cancel state
                $('.hair-analysis-container').addClass('hidden');
                $('.myavana-tryon').removeClass('hidden');
                analysisButton.addClass('cancel-active');
                analysisButton.find('i').removeClass('fa-plus').addClass('fa-times');
                analysisButton.find('span').text('Cancel Analysis');
            } else {
                // Cancel: revert UI + button
                $('.hair-analysis-container').removeClass('hidden');
                $('.myavana-tryon').addClass('hidden');
                analysisButton.removeClass('cancel-active');
                analysisButton.find('i').removeClass('fa-times').addClass('fa-plus');
                analysisButton.find('span').text('Add Analysis');
            }
        } else if (section === 'about-me') {
            $('#about-me-text').prop('disabled', false);
            $('#save-about-me, #cancel-about-me').removeClass('hidden');
        } else if (section === 'hair-goals') {
            // FIX: This should trigger the add goal modal, not just show a generic modal
            $('#hair-goal-modal').show();
        } else if (section === 'routine') {
            // FIX: This should trigger the add routine step modal
            $('#routine-step-modal').show();
        }
    });

    $('#hairTypeGuideButton').on('click', function() {
        $('#youzify-profile-navmenu').hide();
        $('#hairTypeGuide').show();
    });

    $('#close-info-modal').on('click', function() {
        $('#youzify-profile-navmenu').show();
        $('#hairTypeGuide').hide();
    });

    $('#cancel-info-modal').on('click', function() {
        $('#youzify-profile-navmenu').show();
        $('#hairTypeGuide').hide();
    });

    $('#cancel-about-me').on('click', function() {
        $('#about-me-text').prop('disabled', true);
        $('#save-about-me, #cancel-about-me').addClass('hidden');
        // Note: The PHP value needs to be passed dynamically
        $('#about-me-text').val($('#about-me-text').data('original-value') || '');
    });

    // Form submission
    $('#profile-form').on('submit', function(e) {
        e.preventDefault();
        const formData = $(this).serialize();
        console.log('Submitting form data:', formData);

        $.ajax({
            url: myavanaProfileAjax.ajaxurl,
            type: 'POST',
            data: formData,
            success: function(response) {
                console.log('Profile update response:', response);
                if (response.success) {
                    $('#profile-edit').fadeOut(300, function() {
                        $('.profile-header').removeClass('hidden').fadeIn(300);
                        $('#success-message').text(response.data.message).removeClass('hidden').fadeIn(300);
                        $('#ai-tip').html(response.data.tip).removeClass('hidden').fadeIn(300);
                        setTimeout(() => {
                            $('#success-message, #ai-tip').fadeOut(300);
                            location.reload();
                        }, 5000);
                    });
                } else {
                    $('#error-message').text(response.data || 'Unknown error occurred.').removeClass('hidden').fadeIn(300);
                    setTimeout(() => $('#error-message').fadeOut(300), 5000);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('Profile update error:', textStatus, errorThrown);
                $('#error-message').text('Error: Failed to connect to server. Please try again.').removeClass('hidden').fadeIn(300);
                setTimeout(() => $('#error-message').fadeOut(300), 5000);
            }
        });
    });

    // Save About Me
    $('#save-about-me').click(function() {
        const aboutMe = $('#about-me-text').val();
        $.ajax({
            url: myavanaProfileAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'myavana_save_about_me',
                about_me: aboutMe,
                nonce: myavanaProfileAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#success-message').text('About me updated successfully!').removeClass('hidden').fadeIn(300);
                    $('#about-me-text').prop('disabled', true);
                    $('#save-about-me, #cancel-about-me').addClass('hidden');
                    setTimeout(() => $('#success-message').fadeOut(300), 5000);
                } else {
                    $('#error-message').text('Error: ' + response.data).removeClass('hidden').fadeIn(300);
                    setTimeout(() => $('#error-message').fadeOut(300), 5000);
                }
            }
        });
    });

    // Modal close functionality
    $(document).on('click', '.close-modal', function() {
        $(this).closest('.myavana-modal').hide();
    });

    // Close modal when clicking outside
    $(document).on('click', '.myavana-modal', function(e) {
        if (e.target === this) {
            $(this).hide();
        }
    });

    // Initialize chart if data is available
    initializeHairHealthChart();

    // Hair Goals Management - Enhanced Design
    $('.add-new-myavana-hair-goal-selector').click(function() {
        // Reset form for new goal
        $('#goal-index').val('');
        $('#hair-goal-form')[0].reset();
        $('#progress-bar-fill').css('width', '0%');
        $('#progress-value-enhanced').text('0%');
        $('#hair-goal-modal').show();
    });

    // Toggle updates section
    $(document).on('click', '.updates-header', function() {
        const content = $(this).next('.updates-content');
        const toggle = $(this).find('.updates-toggle');

        content.toggleClass('expanded');
        toggle.toggleClass('rotated');
    });

    // Update progress on slider change
    $(document).on('input', '.goal-progress-slider', function() {
        const index = $(this).data('index');
        const percentage = $(this).val();
        const goalCard = $(this).closest('.goal-card');
        const progressFill = goalCard.find('.progress-fill');
        const progressPercentage = goalCard.find('.progress-percentage');

        progressFill.css('width', percentage + '%');
        progressPercentage.text(percentage + '%');
    });

    $(document).on('click', '.edit-goal', function() {
        const index = $(this).data('index');
        const hairGoals = window.myavanaProfileData ? window.myavanaProfileData.hairGoals : [];
        const goal = hairGoals[index];

        if (goal) {
            $('#goal-modal-title').text('Edit Hair Goal');
            $('#goal-index').val(index);
            $('#goal-title').val(goal.title);
            $('#goal-description').val(goal.description);
            $('#goal-progress').val(goal.progress);
            $('#progress-value').text(goal.progress + '%');
            $('#goal-start-date').val(goal.start_date);
            $('#goal-target-date').val(goal.target_date);

            $('#hair-goal-modal').show();
        }
    });

    $('#goal-progress').on('input', function() {
        $('#progress-value').text($(this).val() + '%');
    });

    $('#hair-goal-form').submit(function(e) {
        e.preventDefault();
        console.log('Goal form submitted');
        console.log('myavanaProfileAjax available:', typeof myavanaProfileAjax !== 'undefined');

        const index = $('#goal-index').val();
        const goal = {
            title: $('#goal-title').val(),
            description: $('#goal-description').val(),
            progress: $('#goal-progress').val(),
            start_date: $('#goal-start-date').val(),
            target_date: $('#goal-target-date').val(),
            progress_text: index !== '' ? (window.myavanaProfileData?.hairGoals[index]?.progress_text || []) : []
        };

        console.log('Goal data:', goal);

        $.ajax({
            url: myavanaProfileAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'myavana_save_hair_goal',
                goal: JSON.stringify(goal),
                index: index,
                nonce: myavanaProfileAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    $('#error-message').text('Error: ' + response.data).removeClass('hidden').fadeIn(300);
                    setTimeout(() => $('#error-message').fadeOut(300), 5000);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                $('#error-message').text('Error: Failed to connect to server. Please try again.').removeClass('hidden').fadeIn(300);
                setTimeout(() => $('#error-message').fadeOut(300), 5000);
            }
        });
    });

    // Add update functionality for new design
    $(document).on('click', '.add-update-btn', function() {
        const index = $(this).data('index');
        const input = $(this).closest('.add-update').find('.update-text-input');
        const updateText = input.val();

        if (updateText.trim() === '') return;

        const update = {
            text: updateText,
            date: new Date().toISOString().split('T')[0]
        };

        // Optimistically add the update to the UI
        const updatesContent = $(this).closest('.updates-content');
        const today = new Date();
        const formattedDate = today.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });

        const updateItem = $(`
            <div class="update-item">
                <p class="update-text">${updateText}</p>
                <span class="update-date">${formattedDate}</span>
            </div>
        `);

        // Insert before the add-update form
        updatesContent.find('.add-update').before(updateItem);
        input.val('');

        $.ajax({
            url: myavanaProfileAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'myavana_add_goal_update',
                index: index,
                update: JSON.stringify(update),
                nonce: myavanaProfileAjax.nonce
            },
            success: function(response) {
                if (!response.success) {
                    // Remove the optimistic update if it failed
                    updateItem.remove();
                    $('#error-message').text('Error: ' + response.data).removeClass('hidden').fadeIn(300);
                    setTimeout(() => $('#error-message').fadeOut(300), 5000);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                // Remove the optimistic update if there was an error
                updateItem.remove();
                $('#error-message').text('Error: Failed to connect to server. Please try again.').removeClass('hidden').fadeIn(300);
                setTimeout(() => $('#error-message').fadeOut(300), 5000);
            }
        });
    });

    // Update progress button functionality for new design
    $(document).on('click', '.update-progress-btn', function() {
        const index = $(this).data('index');
        const goalCard = $(this).closest('.goal-card');
        const slider = goalCard.find('.goal-progress-slider');
        const newProgress = slider.val();

        $.ajax({
            url: myavanaProfileAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'myavana_update_goal_progress',
                index: index,
                progress: newProgress,
                nonce: myavanaProfileAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Update was successful - show success message
                    $('#success-message').text('Progress updated successfully!').removeClass('hidden').fadeIn(300);
                    setTimeout(() => $('#success-message').fadeOut(300), 3000);
                } else {
                    $('#error-message').text('Error: ' + response.data).removeClass('hidden').fadeIn(300);
                    setTimeout(() => $('#error-message').fadeOut(300), 5000);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                $('#error-message').text('Error: Failed to connect to server. Please try again.').removeClass('hidden').fadeIn(300);
                setTimeout(() => $('#error-message').fadeOut(300), 5000);
            }
        });
    });

    // Hair Analysis - Fix the camera/analysis handlers
    let cameraStreamAnalysis = null;
    $('#use-camera-analysis, #start-first-analysis').click(function() {
        $('.myavana-tryon').removeClass('hidden');
        $('.hair-analysis-container').addClass('hidden');
    });

    // Routine Management - Fixed class selectors
    $('.add-new-myavana-current-routine, #add-routine-step').click(function() {
        $('#step-modal-title').text('Add Routine Step');
        $('#step-index').val('');
        $('#routine-step-form')[0].reset();
        if (typeof $.fn.select2 !== 'undefined') {
            $('#step-products').val(null).trigger('change');
        }
        $('#routine-step-modal').show();
    });

    // Handle both .editBtn and .edit-step classes for routine step editing
    $(document).on('click', '.editBtn, .edit-step', function() {
        const index = $(this).data('index');
        const currentRoutine = window.myavanaProfileData ? window.myavanaProfileData.currentRoutine : [];
        const step = currentRoutine[index];

        if (step) {
            $('#step-modal-title').text('Edit Routine Step');
            $('#step-index').val(index);
            $('#step-name').val(step.name);
            $('#step-frequency').val(step.frequency);
            $('#step-time').val(step.time_of_day);
            $('#step-description').val(step.description);
            if (typeof $.fn.select2 !== 'undefined') {
                $('#step-products').val(step.products).trigger('change');
            }

            $('#routine-step-modal').show();
        }
    });

    $('#routine-step-form').submit(function(e) {
        e.preventDefault();
        console.log('Routine form submitted');
        console.log('myavanaProfileAjax available:', typeof myavanaProfileAjax !== 'undefined');

        const index = $('#step-index').val();
        const step = {
            name: $('#step-name').val(),
            frequency: $('#step-frequency').val(),
            time_of_day: $('#step-time').val(),
            description: $('#step-description').val(),
            products: $('#step-products').val() || []
        };

        console.log('Routine step data:', step);

        $.ajax({
            url: myavanaProfileAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'myavana_save_routine_step',
                step: JSON.stringify(step),
                index: index,
                nonce: myavanaProfileAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    $('#error-message').text('Error: ' + response.data).removeClass('hidden').fadeIn(300);
                    setTimeout(() => $('#error-message').fadeOut(300), 5000);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                $('#error-message').text('Error: Failed to connect to server. Please try again.').removeClass('hidden').fadeIn(300);
                setTimeout(() => $('#error-message').fadeOut(300), 5000);
            }
        });
    });

    $(document).on('click', '.delete-step', function() {
        if (confirm('Are you sure you want to delete this routine step?')) {
            const index = $(this).data('index');
            $.ajax({
                url: myavanaProfileAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'myavana_delete_routine_step',
                    index: index,
                    nonce: myavanaProfileAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        $('#error-message').text('Error: ' + response.data).removeClass('hidden').fadeIn(300);
                        setTimeout(() => $('#error-message').fadeOut(300), 5000);
                    }
                }
            });
        }
    });

    // Modal close
    $('.close-modal').click(function() {
        $(this).closest('.myavana-modal').hide();
    });

    $(window).click(function(event) {
        if ($(event.target).hasClass('myavana-modal')) {
            $('.myavana-modal').hide();
            try {
                $('#youzify-profile-navmenu').show();
            } catch (error) {
                console.log(error.message);
            }
        }
    });
});

// Initialize chart function
function initializeHairHealthChart() {
    if (typeof Chart === 'undefined' || !window.myavanaProfileData?.chartData) {
        return;
    }

    const ctx = document.getElementById('hairHealthChart');
    if (!ctx) return;

    const chartData = window.myavanaProfileData.chartData;
    new Chart(ctx.getContext('2d'), {
        type: 'line',
        data: {
            labels: chartData.map(entry => entry.date),
            datasets: [{
                label: 'Hair Health Rating',
                data: chartData.map(entry => entry.rating),
                borderColor: '#e7a690',
                backgroundColor: 'rgba(231, 166, 144, 0.2)',
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#4a4d68',
                pointBorderColor: '#ffffff',
                pointHoverBackgroundColor: '#e7a690',
                pointHoverBorderColor: '#222323'
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { labels: { font: { family: 'Avenir Next', size: 14 }, color: '#222323' } },
                tooltip: { bodyFont: { family: 'Avenir Next' }, titleFont: { family: 'Avenir Next', weight: 'bold' } }
            },
            scales: {
                y: { beginAtZero: true, max: 5, ticks: { font: { family: 'Avenir Next', size: 12 }, color: '#222323' }, grid: { color: '#eeece1' } },
                x: { ticks: { font: { family: 'Avenir Next', size: 12 }, color: '#222323' }, grid: { color: '#eeece1' } }
            }
        }
    });
}