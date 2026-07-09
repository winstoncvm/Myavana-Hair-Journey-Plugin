/**
 * MYAVANA Timeline - Offcanvas Management Module
 * Handles modal/offcanvas opening, closing, and state management
 *
 * @package Myavana_Hair_Journey
 * @version 2.3.5
 */

window.MyavanaTimeline = window.MyavanaTimeline || {};

MyavanaTimeline.Offcanvas = (function() {
    'use strict';

    // Use state management
    const State = MyavanaTimeline.State;

    /**
     * Close any open offcanvas (unified closer)
     */
    function closeOffcanvas() {
        console.log('Closing offcanvas...');

        // Close view offcanvas if open
        const currentViewOffcanvas = State.get('currentViewOffcanvas');
        if (currentViewOffcanvas) {
            closeTimelineViewOffcanvas();
            return;
        }

        // Close create/edit offcanvas if open
        const currentOffcanvas = State.get('currentOffcanvas');
        if (currentOffcanvas) {
            closeCreateOffcanvas();
            return;
        }
    }

    /**
     * Dedicated function for view offcanvas (namespaced to avoid global collisions)
     */
    function closeTimelineViewOffcanvas() {
        const currentViewOffcanvas = State.get('currentViewOffcanvas');
        if (!currentViewOffcanvas) return;

        const overlay = document.getElementById('viewOffcanvasOverlay');
        if (overlay) {
            overlay.classList.remove('active');
            overlay.classList.remove('is-open');
        }

        currentViewOffcanvas.classList.remove('active');
        currentViewOffcanvas.classList.remove('is-open');
        document.body.style.overflow = '';

        setTimeout(() => {
            State.set('currentViewOffcanvas', null);
            State.set('currentViewData', null);
        }, 400);
    }

    /**
     * Dedicated function for create/edit offcanvas
     */
    function closeCreateOffcanvas() {
        const currentOffcanvas = State.get('currentOffcanvas');
        if (!currentOffcanvas) return;

        const overlay1 = document.getElementById('createOffcanvasOverlay');
        if (overlay1) {
            overlay1.classList.remove('active');
            overlay1.classList.remove('is-open');
        }

        const overlay = document.getElementById('offcanvasOverlay') || document.getElementById('createOffcanvasOverlay');
        if (overlay) {
            overlay.classList.remove('active');
            overlay.classList.remove('is-open');
        }

        currentOffcanvas.classList.remove('active');
        currentOffcanvas.classList.remove('is-open');
        document.body.style.overflow = '';

        setTimeout(() => {
            State.set('currentOffcanvas', null);
        }, 300);

        resetOffcanvasForms();
    }

    /**
     * Master close function that closes any open offcanvas
     */
    function closeAllOffcanvases() {
        closeCreateOffcanvas();
        closeTimelineViewOffcanvas();
    }

    /**
     * Reset all offcanvas forms to their default state
     */
    function resetOffcanvasForms() {
        document.querySelectorAll('.offcanvas form, .offcanvas-hjn form').forEach(form => form.reset());

        State.set('selectedRating', 0);
        State.set('uploadedFiles', []);

        const previewGrid = document.getElementById('entryPreviewGrid');
        if (previewGrid) previewGrid.innerHTML = '';

        document.querySelectorAll('.chip').forEach(chip => chip.classList.remove('selected'));
        document.querySelectorAll('.rating-star').forEach(star => star.classList.remove('active'));
    }

    /**
     * Initialize offcanvas click handlers
     */
    function initOffcanvasClickHandlers() {
        document.querySelectorAll('.offcanvas').forEach(offcanvas => {
            offcanvas.addEventListener('click', function(e) {
                // No e.stopPropagation() needed here.
            });
        });

        // Add a click listener to the overlay to close the offcanvas
        const overlay = document.getElementById('offcanvasOverlay');
        if (overlay) {
            overlay.addEventListener('click', function(e) {
                if (e.target === overlay) {
                    closeOffcanvas();
                }
            });
        }

        const viewOverlay = document.getElementById('viewOffcanvasOverlay');
        if (viewOverlay) {
            viewOverlay.addEventListener('click', function(e) {
                if (e.target === viewOverlay) {
                    closeTimelineViewOffcanvas();
                }
            });
        }
    }

    /**
     * Open offcanvas for different types (entry, goal, routine)
     *
     * @param {string} type - Type of offcanvas to open ('entry', 'goal', 'routine')
     * @param {number|null} id - Optional ID for editing existing item
     */
    function openOffcanvas(type, id = null) {
        console.log('Opening offcanvas:', type, id);

        // Prefer the new create-offcanvas controller when available.
        if (window.HJN && typeof window.HJN.openOffcanvas === 'function') {
            if (id && typeof id === 'object') {
                window.HJN.openOffcanvas(type, id);
                return;
            }
            if (id === null || typeof id === 'undefined') {
                window.HJN.openOffcanvas(type, {});
                return;
            }
        }

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

        if (offcanvas) {
            // Track the currently open create/edit offcanvas so the unified closer works
            State.set('currentOffcanvas', offcanvas);
            if (overlay) {
                overlay.classList.add('active');
                overlay.classList.add('is-open');
            }
            offcanvas.classList.add('active');
            offcanvas.classList.add('is-open');
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
        const entryId = document.getElementById('entry_id');
        if (entryId) entryId.value = '';
        const titleEl = document.getElementById('entryOffcanvasTitle');
        if (titleEl) titleEl.textContent = 'New Entry';
        const subtitleEl = document.getElementById('entryOffcanvasSubtitle');
        if (subtitleEl) subtitleEl.textContent = 'Log your hair care session';

        // Reset date to today
        const today = new Date().toISOString().split('T')[0];
        const dateEl = document.getElementById('entry_date');
        if (dateEl) dateEl.value = today;

        // Reset rating stars
        const stars = document.querySelectorAll('.rating-star-hjn, #health_rating_stars .rating-star-hjn');
        stars.forEach(star => {
            star.classList.remove('active');
            star.classList.remove('is-active');
        });
        const ratingEl = document.getElementById('health_rating');
        if (ratingEl) ratingEl.value = '0';
        const ratingLabel = document.getElementById('health_rating_label') || document.getElementById('health_rating_value');
        if (ratingLabel) {
            ratingLabel.textContent = 'Not yet rated';
            ratingLabel.classList.remove('is-rated');
        }

        // Clear FilePond
        const entryFilePond = State.get('entryFilePond');
        if (entryFilePond) {
            entryFilePond.removeFiles();
        }

        const newPhotoGrid = document.getElementById('newMediaPreviewGrid');
        if (newPhotoGrid) newPhotoGrid.innerHTML = '';
        const newVideoGrid = document.getElementById('newVideoPreviewGrid');
        if (newVideoGrid) newVideoGrid.innerHTML = '';

        const existingImages = document.getElementById('existingImagesGallery');
        if (existingImages) existingImages.style.display = 'none';
        const existingVideos = document.getElementById('existingVideosGallery');
        if (existingVideos) existingVideos.style.display = 'none';
    }

    /**
     * Reset goal form
     */
    function resetGoalForm() {
        const form = document.getElementById('goalForm');
        if (!form) return;

        form.reset();
        const goalId = document.getElementById('goal_id');
        if (goalId) goalId.value = '';
        const goalTitle = document.getElementById('goalOffcanvasTitle');
        if (goalTitle) goalTitle.textContent = 'Create a Goal';

        // Reset progress
        const goalProgress = document.getElementById('goal_progress');
        if (goalProgress) goalProgress.value = '0';
        const goalProgressLabel = document.getElementById('goal_progress_value');
        if (goalProgressLabel) goalProgressLabel.textContent = '0%';

        // Clear milestones
        const milestones = document.getElementById('milestones_list');
        if (milestones) milestones.innerHTML = '';
    }

    /**
     * Reset routine form
     */
    function resetRoutineForm() {
        const form = document.getElementById('routineForm');
        if (!form) return;

        form.reset();
        const routineId = document.getElementById('routine_id');
        if (routineId) routineId.value = '';
        const routineTitle = document.getElementById('routineOffcanvasTitle');
        if (routineTitle) routineTitle.textContent = 'Build a Routine';
    }

    /**
     * Load entry data for editing
     *
     * @param {number} id - Entry ID to load
     */
    function loadEntryForEdit(id) {
        // This function will be implemented by the forms module
        console.log('Loading entry for edit:', id);
    }

    /**
     * Load goal data for editing
     *
     * @param {number} id - Goal ID to load
     */
    function loadGoalForEdit(id) {
        // This function will be implemented by the forms module
        console.log('Loading goal for edit:', id);
    }

    /**
     * Load routine data for editing
     *
     * @param {number} id - Routine ID to load
     */
    function loadRoutineForEdit(id) {
        // This function will be implemented by the forms module
        console.log('Loading routine for edit:', id);
    }

    // Public API
    return {
        open: openOffcanvas,
        close: closeOffcanvas,
        closeView: closeTimelineViewOffcanvas,
        closeCreate: closeCreateOffcanvas,
        closeAll: closeAllOffcanvases,
        resetForms: resetOffcanvasForms,
        initClickHandlers: initOffcanvasClickHandlers,

        // Form reset functions
        resetEntryForm: resetEntryForm,
        resetGoalForm: resetGoalForm,
        resetRoutineForm: resetRoutineForm
    };
})();

// Backward compatibility - expose as global functions
window.openOffcanvas = MyavanaTimeline.Offcanvas.open;
window.closeOffcanvas = MyavanaTimeline.Offcanvas.close;
window.closeTimelineViewOffcanvas = MyavanaTimeline.Offcanvas.closeView;
window.closeCreateOffcanvas = MyavanaTimeline.Offcanvas.closeCreate;
window.closeAllOffcanvases = MyavanaTimeline.Offcanvas.closeAll;
