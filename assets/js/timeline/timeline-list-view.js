/**
 * MYAVANA Timeline - List View Module
 * Manages the list view interface including filtering, searching, and sorting
 *
 * @package Myavana_Hair_Journey
 * @version 2.3.5
 */

// Initialize namespace if not exists
window.MyavanaTimeline = window.MyavanaTimeline || {};

// List View Module
MyavanaTimeline.ListView = (function() {
    'use strict';

    /**
     * Initialize list view event handlers
     */
    function init() {
        // Filter chip handlers
        document.querySelectorAll('.filter-chip-hjn').forEach(chip => {
            chip.addEventListener('click', function() {
                document.querySelectorAll('.filter-chip-hjn').forEach(c => c.classList.remove('active'));
                this.classList.add('active');
                MyavanaTimeline.State.set('currentFilter', this.dataset.filter);
                update();
            });
        });

        // Search input handler
        const searchInput = document.getElementById('listSearchInput');
        const searchClearBtn = document.getElementById('searchClearBtn');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const searchValue = this.value.toLowerCase().trim();
                MyavanaTimeline.State.set('currentSearch', searchValue);
                if (searchClearBtn) {
                    searchClearBtn.style.display = searchValue ? 'flex' : 'none';
                }
                update();
            });
        }
        if (searchClearBtn) {
            searchClearBtn.addEventListener('click', function() {
                if (searchInput) {
                    searchInput.value = '';
                    MyavanaTimeline.State.set('currentSearch', '');
                    this.style.display = 'none';
                    update();
                }
            });
        }

        // Sort select handler
        const sortSelect = document.getElementById('listSortSelect');
        if (sortSelect) {
            sortSelect.addEventListener('change', function() {
                MyavanaTimeline.State.set('currentSort', this.value);
                update();
            });
        }
    }

    /**
     * Filter, search, and sort list items
     */
    function update() {
        const listGrid = document.getElementById('listGrid');
        if (!listGrid) return;

        // Get current filter state
        const currentFilter = MyavanaTimeline.State.get('currentFilter');
        const currentSearch = MyavanaTimeline.State.get('currentSearch');

        const items = Array.from(listGrid.querySelectorAll('.list-item-hjn'));
        items.forEach(item => {
            const type = item.dataset.type;
            const title = item.dataset.title || '';
            const content = item.textContent.toLowerCase();
            const date = parseInt(item.dataset.date) || 0;

            // Apply filter
            const passesFilter = currentFilter === 'all' || type === currentFilter;

            // Apply search
            const passesSearch = !currentSearch ||
                content.includes(currentSearch) ||
                title.includes(currentSearch);

            // Show/hide based on filter & search
            item.style.display = (passesFilter && passesSearch) ? 'flex' : 'none';
        });

        // Sort visible items
        const visibleItems = items.filter(item => item.style.display !== 'none');
        sort(visibleItems);

        // Show empty state if no items visible
        const emptyState = document.querySelector('.list-empty-state-hjn');
        if (emptyState) {
            const hasVisibleItems = items.some(item => item.style.display !== 'none');
            emptyState.style.display = hasVisibleItems ? 'none' : 'flex';
        }
    }

    /**
     * Sort list items based on current sort option
     * @param {Array} items - Array of DOM elements to sort
     */
    function sort(items) {
        const listGrid = document.getElementById('listGrid');
        if (!listGrid || !items.length) return;

        const currentSort = MyavanaTimeline.State.get('currentSort');

        items.sort((a, b) => {
            switch (currentSort) {
                case 'date-desc':
                    return (parseInt(b.dataset.date) || 0) - (parseInt(a.dataset.date) || 0);
                case 'date-asc':
                    return (parseInt(a.dataset.date) || 0) - (parseInt(b.dataset.date) || 0);
                case 'title-asc':
                    return (a.dataset.title || '').localeCompare(b.dataset.title || '');
                case 'title-desc':
                    return (b.dataset.title || '').localeCompare(a.dataset.title || '');
                case 'type':
                    return (a.dataset.type || '').localeCompare(b.dataset.type || '') ||
                           (parseInt(b.dataset.date) || 0) - (parseInt(a.dataset.date) || 0);
                default:
                    return 0;
            }
        });

        // Reorder DOM
        items.forEach(item => listGrid.appendChild(item));
    }

    // Public API
    return {
        init: init,
        update: update,
        sort: sort
    };
})();
