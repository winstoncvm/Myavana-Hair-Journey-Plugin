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
        if (overlay) overlay.classList.remove('active');

        currentViewOffcanvas.classList.remove('active');
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
        if (overlay1) overlay1.classList.remove('active');

        const overlay = document.getElementById('offcanvasOverlay') || document.getElementById('createOffcanvasOverlay');
        if (overlay) overlay.classList.remove('active');

        currentOffcanvas.classList.remove('active');
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
        document.querySelectorAll('.offcanvas form').forEach(form => form.reset());

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
            // Track the currently open create/edit offcanvas so the unified closer works
            State.set('currentOffcanvas', offcanvas);
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
        stars.forEach(star => star.classList.remove('active'));
        document.getElementById('health_rating').value = '0';
        document.getElementById('health_rating_value').textContent = 'Not Rated';

        // Clear FilePond
        const entryFilePond = State.get('entryFilePond');
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
