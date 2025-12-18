/**
 * MYAVANA Timeline - Centralized State Management
 * Manages all global state variables for the timeline application
 *
 * @package Myavana_Hair_Journey
 * @version 2.3.5
 */

// Initialize namespace if not exists
window.MyavanaTimeline = window.MyavanaTimeline || {};

// State Management Module
MyavanaTimeline.State = (function() {
    'use strict';

    // Private state variables
    let state = {
        // Slider state
        splide: null,
        currentCalendarView: 'month',

        // Offcanvas state
        currentOffcanvas: null,
        currentViewOffcanvas: null,
        currentViewData: null,
        selectedRating: 0,
        uploadedFiles: [],

        // List view state
        currentFilter: 'all',
        currentSearch: '',
        currentSort: 'date-desc',

        // Timeline filter state
        timelineCurrentFilter: 'all',

        // FilePond instance
        entryFilePond: null
    };

    /**
     * Get a state value
     */
    function get(key) {
        return state[key];
    }

    /**
     * Set a state value
     */
    function set(key, value) {
        state[key] = value;
        // Trigger state change event for reactive updates
        document.dispatchEvent(new CustomEvent('myavana:state:changed', {
            detail: { key, value }
        }));
    }

    /**
     * Get multiple state values
     */
    function getAll(keys) {
        const result = {};
        keys.forEach(key => {
            result[key] = state[key];
        });
        return result;
    }

    /**
     * Set multiple state values
     */
    function setAll(updates) {
        Object.keys(updates).forEach(key => {
            state[key] = updates[key];
        });
        document.dispatchEvent(new CustomEvent('myavana:state:changed:bulk', {
            detail: updates
        }));
    }

    /**
     * Reset a state value to default
     */
    function reset(key) {
        const defaults = {
            splide: null,
            currentCalendarView: 'month',
            currentOffcanvas: null,
            currentViewOffcanvas: null,
            currentViewData: null,
            selectedRating: 0,
            uploadedFiles: [],
            currentFilter: 'all',
            currentSearch: '',
            currentSort: 'date-desc',
            timelineCurrentFilter: 'all',
            entryFilePond: null
        };

        if (defaults.hasOwnProperty(key)) {
            state[key] = defaults[key];
        }
    }

    /**
     * Reset all state to defaults
     */
    function resetAll() {
        state = {
            splide: null,
            currentCalendarView: 'month',
            currentOffcanvas: null,
            currentViewOffcanvas: null,
            currentViewData: null,
            selectedRating: 0,
            uploadedFiles: [],
            currentFilter: 'all',
            currentSearch: '',
            currentSort: 'date-desc',
            timelineCurrentFilter: 'all',
            entryFilePond: null
        };

        document.dispatchEvent(new CustomEvent('myavana:state:reset'));
    }

    /**
     * Subscribe to state changes
     */
    function subscribe(callback) {
        document.addEventListener('myavana:state:changed', (e) => {
            callback(e.detail.key, e.detail.value);
        });
    }

    /**
     * Subscribe to bulk state changes
     */
    function subscribeBulk(callback) {
        document.addEventListener('myavana:state:changed:bulk', (e) => {
            callback(e.detail);
        });
    }

    /**
     * Debug - dump all state
     */
    function dump() {
        console.log('[MyavanaTimeline State]', state);
        return state;
    }

    // Public API
    return {
        get: get,
        set: set,
        getAll: getAll,
        setAll: setAll,
        reset: reset,
        resetAll: resetAll,
        subscribe: subscribe,
        subscribeBulk: subscribeBulk,
        dump: dump
    };
})();

// Expose global state accessors for backward compatibility
// These will be migrated to use MyavanaTimeline.State in subsequent modules
window.myavanaTimelineGetState = MyavanaTimeline.State.get;
window.myavanaTimelineSetState = MyavanaTimeline.State.set;
