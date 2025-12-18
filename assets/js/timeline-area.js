// Initialize Splide Slider
function initSlider() {
    if (splide) {
        splide.destroy();
    }

    const sliderElement = document.getElementById('hairJourneySlider');
    if (sliderElement) {
        splide = new Splide('#hairJourneySlider', {
            type: 'slide',
            perPage: 1,
            perMove: 1,
            gap: '2rem',
            padding: '5%',
            focus: 'center',
            trimSpace: false,
            arrows: true,
            pagination: false,
            breakpoints: {
                768: {
                    padding: '10%',
                }
            }
        }).mount();

        splide.on('moved', function(newIndex) {
            const progress = document.getElementById('progress');
            const total = splide.length;
            const percentage = ((newIndex + 1) / total) * 100;
            if (progress) {
                progress.style.width = percentage + '%';
            }

            document.querySelectorAll('.date-marker').forEach(marker => {
                marker.classList.remove('active');
            });
            const activeMarker = document.querySelector(`.date-marker[data-index="${newIndex}"]`);
            if (activeMarker) {
                activeMarker.classList.add('active');
            }
        });
    }
}

// Switch Main Views
function switchView(viewName) {
    // Update header view buttons
    document.querySelectorAll('.view-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelector(`.view-btn[data-view="${viewName}"]`)?.classList.add('active');

    // Update timeline control tabs
    document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
    document.querySelector(`.tab[onclick="switchView('${viewName}')"]`)?.classList.add('active');

    // Switch view content
    document.querySelectorAll('.view-content').forEach(view => view.classList.remove('active'));

    // Initialize view-specific functionality
    if (viewName === 'list') {
        initListView();
    }
    const targetView = document.getElementById(viewName + 'View');
    if (targetView) {
        targetView.classList.add('active');
    }

    if (viewName === 'slider') {
        setTimeout(() => {
            initSlider();
        }, 100);
    }
}

// Initialize list view
function initListView() {
    // Filter chip handlers
    document.querySelectorAll('.filter-chip-hjn').forEach(chip => {
        chip.addEventListener('click', function() {
            document.querySelectorAll('.filter-chip-hjn').forEach(c => c.classList.remove('active'));
            this.classList.add('active');
            currentFilter = this.dataset.filter;
            updateListView();
        });
    });

    // Search input handler
    const searchInput = document.getElementById('listSearchInput');
    const searchClearBtn = document.getElementById('searchClearBtn');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            currentSearch = this.value.toLowerCase().trim();
            searchClearBtn.style.display = currentSearch ? 'flex' : 'none';
            updateListView();
        });
    }
    if (searchClearBtn) {
        searchClearBtn.addEventListener('click', function() {
            if (searchInput) {
                searchInput.value = '';
                currentSearch = '';
                this.style.display = 'none';
                updateListView();
            }
        });
    }

    // Sort select handler
    const sortSelect = document.getElementById('listSortSelect');
    if (sortSelect) {
        sortSelect.addEventListener('change', function() {
            currentSort = this.value;
            updateListView();
        });
    }
}

// Filter, search, and sort list items
function updateListView() {
    const listGrid = document.getElementById('listGrid');
    if (!listGrid) return;

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
    sortListItems(visibleItems);

    // Show empty state if no items visible
    const emptyState = document.querySelector('.list-empty-state-hjn');
    if (emptyState) {
        const hasVisibleItems = items.some(item => item.style.display !== 'none');
        emptyState.style.display = hasVisibleItems ? 'none' : 'flex';
    }
}

// Sort list items based on current sort option
function sortListItems(items) {
    const listGrid = document.getElementById('listGrid');
    if (!listGrid || !items.length) return;

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

// List view variables
let currentFilter = 'all';
let currentSearch = '';
let currentSort = 'date-desc';

// Timeline filter variables
let timelineCurrentFilter = 'all';

/**
 * Set timeline filter by type
 */
function setTimelineFilter(filterType) {
    timelineCurrentFilter = filterType;
    console.log('Timeline filter set to:', filterType);

    // Update button states
    const filterButtons = document.querySelectorAll('.timeline-filter-btn-hjn[data-filter]');
    filterButtons.forEach(btn => {
        if (btn.dataset.filter === filterType) {
            btn.classList.add('active');
        } else {
            btn.classList.remove('active');
        }
    });

    applyTimelineFilters();
}

/**
 * Toggle timeline advanced filter panel
 */
function toggleTimelineFilterPanel() {
    const panel = document.getElementById('timelineFiltersPanel');
    if (!panel) return;

    if (panel.style.display === 'none' || !panel.style.display) {
        panel.style.display = 'block';
    } else {
        panel.style.display = 'none';
    }
}

/**
 * Apply timeline filters
 */
function applyTimelineFilters() {
    const searchInput = document.getElementById('timelineSearchInput');
    const filterRating = document.getElementById('timelineFilterRating');

    const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
    const minRating = filterRating ? parseInt(filterRating.value) : 0;

    console.log('Applying timeline filters:', { filter: timelineCurrentFilter, search: searchTerm, minRating });

    // Get all timeline month groups
    const monthGroups = document.querySelectorAll('.timeline-month-group-hjn');

    monthGroups.forEach(monthGroup => {
        const items = monthGroup.querySelectorAll('.timeline-item-hjn');
        let visibleCount = 0;

        items.forEach(item => {
            const type = item.dataset.type || 'entry';
            const title = item.querySelector('.timeline-item-title-hjn')?.textContent.toLowerCase() || '';
            const description = item.querySelector('.timeline-item-description-hjn')?.textContent.toLowerCase() || '';
            const ratingStars = item.querySelectorAll('.timeline-rating-star-hjn.filled');
            const itemRating = ratingStars.length;

            // Check type filter
            let matchesType = timelineCurrentFilter === 'all' || type === timelineCurrentFilter;

            // Check search
            let matchesSearch = !searchTerm || title.includes(searchTerm) || description.includes(searchTerm);

            // Check rating (only for entries)
            let matchesRating = type !== 'entry' || minRating === 0 || itemRating >= minRating;

            const isVisible = matchesType && matchesSearch && matchesRating;

            item.style.display = isVisible ? 'flex' : 'none';
            if (isVisible) visibleCount++;
        });

        // Hide month group if no visible items
        monthGroup.style.display = visibleCount > 0 ? 'block' : 'none';
    });

    console.log('Timeline filters applied');
}

/**
 * Clear timeline filters
 */
function clearTimelineFilters() {
    const searchInput = document.getElementById('timelineSearchInput');
    const filterRating = document.getElementById('timelineFilterRating');

    if (searchInput) searchInput.value = '';
    if (filterRating) filterRating.value = '0';

    setTimelineFilter('all');

    console.log('Timeline filters cleared');
}

// Switch Calendar Views (Day/Week/Month)
function setCalendarView(view) {
    currentCalendarView = view;

    document.querySelectorAll('.view-toggle').forEach(toggle => {
        toggle.classList.remove('active');
    });
    document.querySelector(`.view-toggle[onclick="setCalendarView('${view}')"]`)?.classList.add('active');

    document.getElementById('monthView').style.display = 'none';
    document.getElementById('weekView').style.display = 'none';
    document.getElementById('dayView').style.display = 'none';

    if (view === 'month') {
        document.getElementById('monthView').style.display = 'block';
        document.getElementById('dateRange').textContent = '1 Oct - 14 Oct';
    } else if (view === 'week') {
        document.getElementById('weekView').style.display = 'block';
        document.getElementById('dateRange').textContent = 'Oct 7-13, 2025';
    } else if (view === 'day') {
        document.getElementById('dayView').style.display = 'block';
        document.getElementById('dateRange').textContent = 'Mon, Oct 14, 2025';
    }
}

// Action Buttons
function addGoal() {
    alert('Add Goal: Opens form to create a new hair goal with category, target date, and progress tracking!');
}

function addRoutine() {
    alert('Add Routine: Opens form to create a new haircare routine with frequency, time of day, and products!');
}

function addEntry() {
    alert('Add Entry: Opens form to log a new journey entry with photos, notes, mood, products, and health rating!');
}

// Modal Functions
// function closeModal() {
//     document.getElementById('eventModal').classList.remove('active');
// }

// Carousel Functions
function scrollCarousel(direction) {
    const track = document.getElementById('carouselTrack');
    const scrollAmount = 160;
    track.scrollBy({
        left: scrollAmount * direction,
        behavior: 'smooth'
    });
}

// Initialize timeline area functionality
document.addEventListener('DOMContentLoaded', function() {
    // Set calendar as default view on page load
    switchView('calendar');
    setCalendarView('month'); // Set default calendar view to month

    // View buttons (header)
    document.querySelectorAll('.view-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const viewName = this.getAttribute('data-view');
            switchView(viewName);
        });
    });

    // Timeline control tabs
    document.querySelectorAll('.tab').forEach(tab => {
        tab.addEventListener('click', function() {
            const viewName = this.getAttribute('onclick').match(/'([^']+)'/)[1];
            switchView(viewName);
        });
    });

    // Timeline cards
    // document.querySelectorAll('.timeline-card').forEach(card => {
    //     card.addEventListener('click', function() {
    //         document.getElementById('eventModal').classList.add('active');
    //     });
    // });

    // // Journey entries
    // document.querySelectorAll('.journey-entry').forEach(entry => {
    //     entry.addEventListener('click', function() {
    //         document.getElementById('eventModal').classList.add('active');
    //     });
    // });

    // // Goal bars
    // document.querySelectorAll('.goal-bar-span').forEach(bar => {
    //     bar.addEventListener('click', function() {
    //         document.getElementById('eventModal').classList.add('active');
    //     });
    // });

    // Routine bars
    // document.querySelectorAll('.routine-bar').forEach(bar => {
    //     bar.addEventListener('click', function() {
    //         document.getElementById('eventModal').classList.add('active');
    //     });
    // });

    // // List items
    // document.querySelectorAll('.list-item-action').forEach(btn => {
    //     btn.addEventListener('click', function(e) {
    //         e.stopPropagation();
    //         document.getElementById('eventModal').classList.add('active');
    //     });
    // });

    // document.querySelectorAll('.list-item').forEach(item => {
    //     item.addEventListener('click', function() {
    //         document.getElementById('eventModal').classList.add('active');
    //     });
    // });

    // Carousel items
    document.querySelectorAll('.carousel-item').forEach(item => {
        item.addEventListener('click', function() {
            document.querySelectorAll('.carousel-item').forEach(i => i.classList.remove('active'));
            this.classList.add('active');
        });
    });


    // Date markers
    document.querySelectorAll('.date-marker').forEach(marker => {
        marker.addEventListener('click', function() {
            const index = parseInt(this.dataset.index);
            if (splide) {
                splide.go(index);
            }
        });
    });

    // Modal close
    // document.getElementById('eventModal').addEventListener('click', function(e) {
    //     if (e.target === this) {
    //         closeModal();
    //     }
    // });

    // Goal items
    document.querySelectorAll('.goal-item').forEach(item => {
        item.addEventListener('click', function() {
            document.querySelectorAll('.goal-item').forEach(i => i.classList.remove('active'));
            this.classList.add('active');
        });
    });

    // Initialize list view functionality (only if list view is active)
    if (document.getElementById('listView') && document.getElementById('listView').classList.contains('active')) {
        initListView();
    }

    console.log('Myavana Timeline Area loaded successfully!');
});

console.log('Timeline Area JavaScript loaded');