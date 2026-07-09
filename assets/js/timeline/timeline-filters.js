/**
 * Timeline Filters Module
 * Handles filtering and search functionality for the timeline
 *
 * @package Myavana_Hair_Journey
 * @subpackage Timeline
 */

(function(window) {
    'use strict';

    // Ensure MyavanaTimeline namespace exists
    if (!window.MyavanaTimeline) {
        window.MyavanaTimeline = {};
    }

    /**
     * Timeline Filters Module
     * Manages filtering, searching, and visibility of timeline entries
     */
    window.MyavanaTimeline.Filters = (function() {

        /**
         * Set timeline filter by type
         *
         * @param {string} filterType - The filter type ('all', 'entry', 'goal', 'routine')
         */
        function setFilter(filterType) {
            const resolvedFilter = String(filterType || 'all').toLowerCase();
            // Update state
            if (window.MyavanaTimeline.State) {
                window.MyavanaTimeline.State.set('currentFilter', resolvedFilter);
            }

            console.log('Timeline filter set to:', resolvedFilter);

            // Update button states
            const filterButtons = document.querySelectorAll('.timeline-filter-btn-hjn[data-filter], .tl2-chip[data-filter]');
            filterButtons.forEach(btn => {
                if ((btn.dataset.filter || '').toLowerCase() === resolvedFilter) {
                    btn.classList.add('active');
                } else {
                    btn.classList.remove('active');
                }
            });

            // Apply the filters
            apply();
        }

        /**
         * Toggle timeline advanced filter panel visibility
         */
        function togglePanel() {
            const panel = document.getElementById('timelineFiltersPanel');
            if (!panel) {
                console.warn('Timeline filters panel not found');
                return;
            }

            if (panel.style.display === 'none' || !panel.style.display) {
                panel.style.display = 'block';
            } else {
                panel.style.display = 'none';
            }
        }

        /**
         * Apply timeline filters
         * Filters timeline items based on type, search term, and rating
         */
        function apply() {
            // Get current filter state
            const currentFilter = window.MyavanaTimeline.State
                ? window.MyavanaTimeline.State.get('currentFilter', 'all')
                : 'all';

            // Get filter inputs
            const searchInput = document.getElementById('timelineSearchInput');
            const filterRating = document.getElementById('timelineFilterRating');

            const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
            const minRating = filterRating ? parseInt(filterRating.value) : 0;

            console.log('Applying timeline filters:', {
                filter: currentFilter,
                search: searchTerm,
                minRating
            });

            // New TL2 weekly timeline structure
            const tl2Timeline = document.getElementById('tl2TimelineWrapper');
            if (tl2Timeline) {
                const weekGroups = tl2Timeline.querySelectorAll('.tl2-week');

                weekGroups.forEach(weekGroup => {
                    const items = weekGroup.querySelectorAll('.tl2-filter-item');
                    let visibleCount = 0;

                    items.forEach(item => {
                        const type = String(item.dataset.type || 'entry').toLowerCase();
                        const title = (
                            item.querySelector('.tl2-entry-title') ||
                            item.querySelector('.tl2-goal-card-title') ||
                            item.querySelector('.tl2-routine-tl-title')
                        )?.textContent.toLowerCase() || '';
                        const description = (
                            item.querySelector('.tl2-goal-card-desc') ||
                            item.querySelector('.tl2-routine-tl-meta') ||
                            item.querySelector('.tl2-entry-meta-row')
                        )?.textContent.toLowerCase() || '';
                        const fullText = (item.textContent || '').toLowerCase();
                        const dataRating = parseFloat(item.dataset.rating || '0');
                        const itemRating = Number.isFinite(dataRating) ? dataRating : 0;

                        const matchesType = currentFilter === 'all' || type === currentFilter;
                        const matchesSearch = !searchTerm ||
                            title.includes(searchTerm) ||
                            description.includes(searchTerm) ||
                            fullText.includes(searchTerm);
                        const matchesRating = type !== 'entry' ||
                            minRating === 0 ||
                            itemRating >= minRating;

                        const isVisible = matchesType && matchesSearch && matchesRating;
                        item.style.display = isVisible ? '' : 'none';
                        if (isVisible) {
                            visibleCount++;
                        }
                    });

                    weekGroup.style.display = visibleCount > 0 ? '' : 'none';
                });

                if (window.MyavanaTimeline.State) {
                    window.MyavanaTimeline.State.trigger('filters:applied', {
                        filter: currentFilter,
                        search: searchTerm,
                        rating: minRating
                    });
                }
                return;
            }

            // Legacy timeline structure
            const monthGroups = document.querySelectorAll('.timeline-month-group-hjn, .timeline-month-hjn');
            monthGroups.forEach(monthGroup => {
                const items = monthGroup.querySelectorAll('.timeline-item-hjn');
                let visibleCount = 0;

                items.forEach(item => {
                    const type = (item.dataset.type || 'entry').toLowerCase();
                    const title = (
                        item.querySelector('.timeline-item-title-hjn') ||
                        item.querySelector('.timeline-card-title-hjn')
                    )?.textContent.toLowerCase() || '';
                    const description = (
                        item.querySelector('.timeline-item-description-hjn') ||
                        item.querySelector('.timeline-card-description-hjn')
                    )?.textContent.toLowerCase() || '';
                    const dataRating = parseFloat(item.dataset.rating || '0');
                    const ratingStars = item.querySelectorAll('.timeline-rating-star-hjn.filled');
                    const itemRating = Number.isFinite(dataRating) && dataRating > 0 ? dataRating : ratingStars.length;

                    const matchesType = currentFilter === 'all' || type === currentFilter;
                    const matchesSearch = !searchTerm || title.includes(searchTerm) || description.includes(searchTerm);
                    const matchesRating = type !== 'entry' || minRating === 0 || itemRating >= minRating;
                    const isVisible = matchesType && matchesSearch && matchesRating;

                    item.style.display = isVisible ? '' : 'none';
                    if (isVisible) visibleCount++;
                });

                monthGroup.style.display = visibleCount > 0 ? '' : 'none';
            });

            console.log('Timeline filters applied');

            // Trigger event for other modules
            if (window.MyavanaTimeline.State) {
                window.MyavanaTimeline.State.trigger('filters:applied', {
                    filter: currentFilter,
                    search: searchTerm,
                    rating: minRating
                });
            }
        }

        /**
         * Clear all timeline filters and reset to default state
         */
        function clear() {
            // Clear input values
            const searchInput = document.getElementById('timelineSearchInput');
            const filterRating = document.getElementById('timelineFilterRating');

            if (searchInput) searchInput.value = '';
            if (filterRating) filterRating.value = '0';

            // Reset to 'all' filter
            setFilter('all');

            console.log('Timeline filters cleared');

            // Trigger event for other modules
            if (window.MyavanaTimeline.State) {
                window.MyavanaTimeline.State.trigger('filters:cleared');
            }
        }

        /**
         * Initialize filter event listeners
         */
        function initialize() {
            // Set up filter button listeners
            const filterButtons = document.querySelectorAll('.timeline-filter-btn-hjn[data-filter], .tl2-chip[data-filter]');
            filterButtons.forEach(btn => {
                btn.addEventListener('click', function() {
                    setFilter(this.dataset.filter);
                });
            });

            if (window.MyavanaTimeline.State && !window.MyavanaTimeline.State.get('currentFilter')) {
                window.MyavanaTimeline.State.set('currentFilter', 'all');
            }

            // Set up search input listener
            const searchInput = document.getElementById('timelineSearchInput');
            if (searchInput) {
                searchInput.addEventListener('input', apply);
            }

            // Set up rating filter listener
            const filterRating = document.getElementById('timelineFilterRating');
            if (filterRating) {
                filterRating.addEventListener('change', apply);
            }

            // Set up clear filters button
            const clearButton = document.getElementById('clearTimelineFilters');
            if (clearButton) {
                clearButton.addEventListener('click', clear);
            }

            // Set up toggle panel button
            const toggleButton = document.getElementById('toggleTimelineFilters');
            if (toggleButton) {
                toggleButton.addEventListener('click', togglePanel);
            }

            console.log('Timeline filters initialized');
        }

        /**
         * Get current filter state
         *
         * @returns {Object} Current filter configuration
         */
        function getFilterState() {
            const searchInput = document.getElementById('timelineSearchInput');
            const filterRating = document.getElementById('timelineFilterRating');
            const currentFilter = window.MyavanaTimeline.State
                ? window.MyavanaTimeline.State.get('currentFilter', 'all')
                : 'all';

            return {
                type: currentFilter,
                search: searchInput ? searchInput.value : '',
                rating: filterRating ? parseInt(filterRating.value) : 0
            };
        }

        // Public API
        return {
            setFilter: setFilter,
            togglePanel: togglePanel,
            apply: apply,
            clear: clear,
            initialize: initialize,
            getFilterState: getFilterState
        };

    })();

    // Backward-compatible global helpers for inline handlers in timeline template.
    window.setTimelineFilter = function(filterType) {
        return window.MyavanaTimeline.Filters.setFilter(filterType);
    };
    window.toggleTimelineFilterPanel = function() {
        return window.MyavanaTimeline.Filters.togglePanel();
    };
    window.applyTimelineFilters = function() {
        return window.MyavanaTimeline.Filters.apply();
    };
    window.clearTimelineFilters = function() {
        return window.MyavanaTimeline.Filters.clear();
    };

    console.log('MyavanaTimeline.Filters module loaded');

})(window);
