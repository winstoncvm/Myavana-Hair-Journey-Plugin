/* ================================================================
   CREATE/EDIT FORMS FUNCTIONALITY
   ================================================================ */

// FilePond instances
let entryFilePond = null;

/**
 * Initialize create/edit forms
 */
function initCreateForms() {
    console.log('Initializing create/edit forms...');

    // Initialize FilePond for entry photos
    initFilePond();

    // Initialize rating stars
    initRatingStars();

    // Initialize form submissions
    initFormSubmissions();

    // Initialize character counters
    initCharacterCounters();

    // Set default dates
    const today = new Date().toISOString().split('T')[0];
    const entryDateInput = document.getElementById('entry_date');
    if (entryDateInput && !entryDateInput.value) {
        entryDateInput.value = today;
    }

    console.log('Create/edit forms initialized');
}

/**
 * Initialize FilePond for image uploads
 */
function initFilePond() {
    const filePondEl = document.getElementById('entry_photos');
    if (!filePondEl || typeof FilePond === 'undefined') {
        console.log('FilePond not available');
        return;
    }

    entryFilePond = FilePond.create(filePondEl, {
        allowMultiple: true,
        maxFiles: 5,
        maxFileSize: '5MB',
        acceptedFileTypes: ['image/*'],
        labelIdle: 'Drag & Drop photos or <span class="filepond--label-action">Browse</span>',
        imagePreviewHeight: 150,
        imageCropAspectRatio: '1:1',
        imageResizeTargetWidth: 800,
        imageResizeTargetHeight: 800,
        imageResizeMode: 'cover',
        imageResizeUpscale: false,
        stylePanelLayout: 'compact',
        credits: false
    });

    console.log('FilePond initialized');
}

/**
 * Initialize rating stars
 */
function initRatingStars() {
    const ratingStars = document.getElementById('health_rating_stars');
    if (!ratingStars) return;

    const stars = ratingStars.querySelectorAll('.rating-star-hjn');
    const ratingInput = document.getElementById('health_rating');
    const ratingValue = document.getElementById('health_rating_value');

    stars.forEach((star, index) => {
        star.addEventListener('click', function(e) {
            e.preventDefault();
            const value = parseInt(this.getAttribute('data-value'));

            // Update hidden input
            ratingInput.value = value;

            // Update stars visual
            stars.forEach((s, i) => {
                if (i < value) {
                    s.classList.add('active');
                } else {
                    s.classList.remove('active');
                }
            });

            // Update value display
            ratingValue.textContent = value + '/5';
        });
    });
}

/**
 * Initialize form submissions
 */
function initFormSubmissions() {
    // Entry form
    const entryForm = document.getElementById('entryForm');
    if (entryForm) {
        entryForm.addEventListener('submit', handleEntrySubmit);
    }

    // Goal form
    const goalForm = document.getElementById('goalForm');
    if (goalForm) {
        goalForm.addEventListener('submit', handleGoalSubmit);
    }

    // Routine form
    const routineForm = document.getElementById('routineForm');
    if (routineForm) {
        routineForm.addEventListener('submit', handleRoutineSubmit);
    }
}

/**
 * Initialize character counters
 */
function initCharacterCounters() {
    const entryContent = document.getElementById('entry_content');
    const entryContentCount = document.getElementById('entry_content_count');

    if (entryContent && entryContentCount) {
        entryContent.addEventListener('input', function() {
            entryContentCount.textContent = this.value.length;
        });
    }
}

/**
 * Open offcanvas for creating/editing
 */
function openOffcanvas(type, id) {
    id = id || null;
    console.log('Opening offcanvas:', type, id);

    const overlay = document.getElementById('createOffcanvasOverlay');
    let offcanvas;

    switch(type) {
        case 'entry':
            offcanvas = document.getElementById('entryOffcanvas');
            if (id) {
                loadEntryForEdit(id);
            } else {
                resetEntryForm();
            }
            break;
        case 'goal':
            offcanvas = document.getElementById('goalOffcanvas');
            if (id) {
                loadGoalForEdit(id);
            } else {
                resetGoalForm();
            }
            break;
        case 'routine':
            offcanvas = document.getElementById('routineOffcanvas');
            if (id) {
                loadRoutineForEdit(id);
            } else {
                resetRoutineForm();
            }
            break;
        default:
            console.error('Unknown offcanvas type:', type);
            return;
    }

    if (offcanvas && overlay) {
        // Track the currently open create/edit offcanvas for unified close handling
        try { currentOffcanvas = offcanvas; } catch (e) { /* global may not exist yet */ }
        overlay.classList.add('active');
        offcanvas.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}


/**
 * Reset entry form
 */
function resetEntryForm() {
    const form = document.getElementById('entryForm');
    if (!form) return;

    form.reset();
    document.getElementById('entry_id').value = '';
    document.getElementById('entryOffcanvasTitle').textContent = 'Add Hair Journey Entry';

    // Reset date to today
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('entry_date').value = today;

    // Reset rating stars
    const stars = document.querySelectorAll('.rating-star-hjn');
    stars.forEach(function(star) {
        star.classList.remove('active');
    });
    document.getElementById('health_rating').value = '0';
    document.getElementById('health_rating_value').textContent = 'Not Rated';

    // Clear FilePond
    if (entryFilePond) {
        entryFilePond.removeFiles();
    }
}

/**
 * Reset goal form
 */
function resetGoalForm() {
    const form = document.getElementById('goalForm');
    if (!form) return;

    form.reset();
    document.getElementById('goal_id').value = '';
    document.getElementById('goalOffcanvasTitle').textContent = 'Create Hair Goal';

    // Reset progress
    document.getElementById('goal_progress').value = '0';
    document.getElementById('goal_progress_value').textContent = '0%';

    // Clear milestones
    document.getElementById('milestones_list').innerHTML = '';
}

/**
 * Reset routine form
 */
function resetRoutineForm() {
    const form = document.getElementById('routineForm');
    if (!form) return;

    form.reset();
    document.getElementById('routine_id').value = '';
    document.getElementById('routineOffcanvasTitle').textContent = 'Create Hair Routine';

    // Reset steps to just one
    const stepsList = document.getElementById('routine_steps_list');
    stepsList.innerHTML = '<div class="routine-step-item-hjn" data-step="1"><div class="step-number-hjn">1</div><input type="text" name="routine_steps[]" class="form-input-hjn step-input-hjn" placeholder="Describe this step..." required><button type="button" class="btn-remove-step-hjn" onclick="removeRoutineStep(this)" disabled><svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M19,6.41L17.59,5L12,10.59L6.41,5L5,6.41L10.59,12L5,17.59L6.41,19L12,13.41L17.59,19L19,17.59L13.41,12L19,6.41Z"/></svg></button></div>';
}

/**
 * Handle entry form submission
 */
async function handleEntrySubmit(e) {
    e.preventDefault();
    console.log('Submitting entry form...');

    const form = e.target;
    const formData = new FormData(form);
    const loadingEl = document.getElementById('entryFormLoading');
    const submitBtn = document.getElementById('saveEntryBtn');

    // Show loading state
    if (loadingEl) loadingEl.style.display = 'flex';
    if (submitBtn) submitBtn.disabled = true;

    // Add FilePond files
    if (entryFilePond) {
        const files = entryFilePond.getFiles();
        files.forEach(function(file, index) {
            formData.append('entry_photos[' + index + ']', file.file);
        });
    }

    try {
        const response = await fetch(myavanaTimelineSettings.ajaxurl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });

        const data = await response.json();

        if (data.success) {
            console.log('Entry saved successfully:', data);
            showNotification('Entry saved successfully!', 'success');
            closeOffcanvas();
            refreshCurrentView();
        } else {
            console.error('Error saving entry:', data);
            showNotification(data.data || 'Error saving entry', 'error');
        }
    } catch (error) {
        console.error('Error submitting entry:', error);
        showNotification('Network error. Please try again.', 'error');
    } finally {
        if (loadingEl) loadingEl.style.display = 'none';
        if (submitBtn) submitBtn.disabled = false;
    }
}

/**
 * Handle goal form submission
 */
async function handleGoalSubmit(e) {
    e.preventDefault();
    console.log('Submitting goal form...');

    const form = e.target;
    const formData = new FormData(form);
    const loadingEl = document.getElementById('goalFormLoading');
    const submitBtn = document.getElementById('saveGoalBtn');

    // Collect milestones
    const milestones = [];
    const milestoneInputs = document.querySelectorAll('.milestone-item-hjn input');
    milestoneInputs.forEach(function(input) {
        if (input.value.trim()) {
            milestones.push(input.value.trim());
        }
    });
    formData.append('milestones', JSON.stringify(milestones));

    // Show loading state
    if (loadingEl) loadingEl.style.display = 'flex';
    if (submitBtn) submitBtn.disabled = true;

    try {
        const response = await fetch(myavanaTimelineSettings.ajaxurl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });

        const data = await response.json();

        if (data.success) {
            console.log('Goal saved successfully:', data);
            showNotification('Goal saved successfully!', 'success');
            closeOffcanvas();
            refreshCurrentView();
        } else {
            console.error('Error saving goal:', data);
            showNotification(data.data || 'Error saving goal', 'error');
        }
    } catch (error) {
        console.error('Error submitting goal:', error);
        showNotification('Network error. Please try again.', 'error');
    } finally {
        if (loadingEl) loadingEl.style.display = 'none';
        if (submitBtn) submitBtn.disabled = false;
    }
}

/**
 * Handle routine form submission
 */
async function handleRoutineSubmit(e) {
    e.preventDefault();
    console.log('Submitting routine form...');

    const form = e.target;
    const formData = new FormData(form);
    const loadingEl = document.getElementById('routineFormLoading');
    const submitBtn = document.getElementById('saveRoutineBtn');

    // Show loading state
    if (loadingEl) loadingEl.style.display = 'flex';
    if (submitBtn) submitBtn.disabled = true;

    try {
        const response = await fetch(myavanaTimelineSettings.ajaxurl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });

        const data = await response.json();

        if (data.success) {
            console.log('Routine saved successfully:', data);
            showNotification('Routine saved successfully!', 'success');
            closeOffcanvas();
            refreshCurrentView();
        } else {
            console.error('Error saving routine:', data);
            showNotification(data.data || 'Error saving routine', 'error');
        }
    } catch (error) {
        console.error('Error submitting routine:', error);
        showNotification('Network error. Please try again.', 'error');
    } finally {
        if (loadingEl) loadingEl.style.display = 'none';
        if (submitBtn) submitBtn.disabled = false;
    }
}

/**
 * Add milestone to goal form
 */
function addMilestone() {
    const milestonesList = document.getElementById('milestones_list');
    const milestoneCount = milestonesList.querySelectorAll('.milestone-item-hjn').length;

    const milestoneItem = document.createElement('div');
    milestoneItem.className = 'milestone-item-hjn';
    milestoneItem.innerHTML = '<input type="text" class="form-input-hjn" placeholder="Milestone ' + (milestoneCount + 1) + '..."><button type="button" class="btn-remove-milestone-hjn" onclick="removeMilestone(this)"><svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M19,6.41L17.59,5L12,10.59L6.41,5L5,6.41L10.59,12L5,17.59L6.41,19L12,13.41L17.59,19L19,17.59L13.41,12L19,6.41Z"/></svg></button>';

    milestonesList.appendChild(milestoneItem);
}

/**
 * Remove milestone
 */
function removeMilestone(button) {
    button.closest('.milestone-item-hjn').remove();
}

/**
 * Add routine step
 */
function addRoutineStep() {
    const stepsList = document.getElementById('routine_steps_list');
    const stepCount = stepsList.querySelectorAll('.routine-step-item-hjn').length + 1;

    const stepItem = document.createElement('div');
    stepItem.className = 'routine-step-item-hjn';
    stepItem.setAttribute('data-step', stepCount);
    stepItem.innerHTML = '<div class="step-number-hjn">' + stepCount + '</div><input type="text" name="routine_steps[]" class="form-input-hjn step-input-hjn" placeholder="Describe this step..." required><button type="button" class="btn-remove-step-hjn" onclick="removeRoutineStep(this)"><svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M19,6.41L17.59,5L12,10.59L6.41,5L5,6.41L10.59,12L5,17.59L6.41,19L12,13.41L17.59,19L19,17.59L13.41,12L19,6.41Z"/></svg></button>';

    stepsList.appendChild(stepItem);
    updateRemoveStepButtons();
}

/**
 * Remove routine step
 */
function removeRoutineStep(button) {
    const stepsList = document.getElementById('routine_steps_list');
    const steps = stepsList.querySelectorAll('.routine-step-item-hjn');

    if (steps.length <= 1) return;

    button.closest('.routine-step-item-hjn').remove();

    // Renumber steps
    const remainingSteps = stepsList.querySelectorAll('.routine-step-item-hjn');
    remainingSteps.forEach(function(step, index) {
        step.setAttribute('data-step', index + 1);
        step.querySelector('.step-number-hjn').textContent = index + 1;
    });

    updateRemoveStepButtons();
}

/**
 * Update remove step buttons
 */
function updateRemoveStepButtons() {
    const stepsList = document.getElementById('routine_steps_list');
    const steps = stepsList.querySelectorAll('.routine-step-item-hjn');
    const removeButtons = stepsList.querySelectorAll('.btn-remove-step-hjn');

    removeButtons.forEach(function(btn) {
        btn.disabled = steps.length <= 1;
    });
}

/**
 * Update progress value display
 */
function updateProgressValue(value) {
    const progressValue = document.getElementById('goal_progress_value');
    if (progressValue) {
        progressValue.textContent = value + '%';
    }
}

/**
 * Refresh current view
 */
function refreshCurrentView() {
    console.log('Refreshing current view...');
    setTimeout(function() {
        window.location.reload();
    }, 1000);
}

/**
 * Show notification
 */
function showNotification(message, type) {
    type = type || 'info';
    console.log('Notification:', type, message);

    const notification = document.createElement('div');
    notification.className = 'notification-hjn notification-' + type + '-hjn';
    notification.textContent = message;
    notification.style.cssText = 'position: fixed; top: 20px; right: 20px; padding: 1rem 1.5rem; background: ' + (type === 'success' ? '#4caf50' : type === 'error' ? '#f44336' : '#2196f3') + '; color: white; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.2); z-index: 100000; font-family: Archivo, sans-serif; font-weight: 600; animation: slideIn 0.3s ease;';

    document.body.appendChild(notification);

    setTimeout(function() {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(function() {
            notification.remove();
        }, 300);
    }, 3000);
}

// Load entry/goal/routine for editing
function loadEntryForEdit(id) {
    console.log('Loading entry for edit:', id);
}

function loadGoalForEdit(id) {
    console.log('Loading goal for edit:', id);
}

function loadRoutineForEdit(id) {
    console.log('Loading routine for edit:', id);
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        initCreateForms();
    }, 500);
});

console.log('Create/edit forms JavaScript loaded');
