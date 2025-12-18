/**
 * MYAVANA Timeline - Navigation Module
 * Handles all view switching, navigation, and carousel controls
 *
 * @package Myavana_Hair_Journey
 * @version 2.3.5
 */

// Initialize namespace if not exists
window.MyavanaTimeline = window.MyavanaTimeline || {};

// Navigation Module
MyavanaTimeline.Navigation = (function() {
    'use strict';

    // Dependencies
    const State = window.MyavanaTimeline.State;

    /**
     * Initialize Splide Slider
     * Creates/recreates the horizontal sliding timeline
     */
    function initSlider() {
        // Get current splide instance from state
        let splide = State.get('splide');

        // Destroy existing instance
        if (splide) {
            splide.destroy();
        }

        const sliderElement = document.getElementById('hairJourneySlider');
        if (!sliderElement) {
            console.warn('[Navigation] Slider element not found');
            return;
        }

        // Create new Splide instance
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

        // Save to state
        State.set('splide', splide);

        // Handle slide movement
        splide.on('moved', function(newIndex) {
            // Update progress bar
            const progress = document.getElementById('progress');
            const total = splide.length;
            const percentage = ((newIndex + 1) / total) * 100;

            if (progress) {
                progress.style.width = percentage + '%';
            }

            // Update date markers
            document.querySelectorAll('.date-marker').forEach(marker => {
                marker.classList.remove('active');
            });

            const activeMarker = document.querySelector(`.date-marker[data-index="${newIndex}"]`);
            if (activeMarker) {
                activeMarker.classList.add('active');
            }

            // Dispatch event for other modules
            document.dispatchEvent(new CustomEvent('myavana:slider:moved', {
                detail: { index: newIndex, total: total }
            }));
        });

        console.log('[Navigation] Slider initialized');
    }

    /**
     * Switch Main Views (calendar, slider, list)
     * @param {string} viewName - The view to switch to: 'calendar', 'slider', or 'list'
     */
    function switchView(viewName) {
        console.log('[Navigation] Switching to view:', viewName);

        // Update header view buttons
        document.querySelectorAll('.view-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        const headerBtn = document.querySelector(`.view-btn[data-view="${viewName}"]`);
        if (headerBtn) {
            headerBtn.classList.add('active');
        }

        // Update timeline control tabs
        document.querySelectorAll('.tab').forEach(tab => {
            tab.classList.remove('active');
        });
        const controlTab = document.querySelector(`.tab[onclick="switchView('${viewName}')"]`);
        if (controlTab) {
            controlTab.classList.add('active');
        }

        // Switch view content
        document.querySelectorAll('.view-content').forEach(view => {
            view.classList.remove('active');
        });

        // Initialize view-specific functionality
        if (viewName === 'list') {
            // Call the global function if available (for backward compatibility)
            if (typeof window.initListView === 'function') {
                window.initListView();
            } else if (typeof initListView === 'function') {
                initListView();
            }
        }

        // Activate target view
        const targetView = document.getElementById(viewName + 'View');
        if (targetView) {
            targetView.classList.add('active');
        }

        // Initialize slider with delay if slider view
        if (viewName === 'slider') {
            setTimeout(() => {
                initSlider();
            }, 100);
        }

        // Dispatch event for other modules
        document.dispatchEvent(new CustomEvent('myavana:view:changed', {
            detail: { view: viewName }
        }));
    }

    /**
     * Switch Calendar Views (Day/Week/Month)
     * @param {string} view - The calendar view: 'day', 'week', or 'month'
     */
    function setCalendarView(view) {
        console.log('[Navigation] Setting calendar view:', view);

        // Update state
        State.set('currentCalendarView', view);

        // Update view toggle buttons
        document.querySelectorAll('.view-toggle').forEach(toggle => {
            toggle.classList.remove('active');
        });
        const activeToggle = document.querySelector(`.view-toggle[onclick="setCalendarView('${view}')"]`);
        if (activeToggle) {
            activeToggle.classList.add('active');
        }

        // Hide all calendar views
        const monthView = document.getElementById('monthView');
        const weekView = document.getElementById('weekView');
        const dayView = document.getElementById('dayView');
        const dateRange = document.getElementById('dateRange');

        if (monthView) monthView.style.display = 'none';
        if (weekView) weekView.style.display = 'none';
        if (dayView) dayView.style.display = 'none';

        // Show selected view and update date range
        if (view === 'month' && monthView) {
            monthView.style.display = 'block';
            if (dateRange) {
                dateRange.textContent = '1 Oct - 14 Oct';
            }
        } else if (view === 'week' && weekView) {
            weekView.style.display = 'block';
            if (dateRange) {
                dateRange.textContent = 'Oct 7-13, 2025';
            }
        } else if (view === 'day' && dayView) {
            dayView.style.display = 'block';
            if (dateRange) {
                dateRange.textContent = 'Mon, Oct 14, 2025';
            }
        }

        // Dispatch event for other modules
        document.dispatchEvent(new CustomEvent('myavana:calendar:view:changed', {
            detail: { view: view }
        }));
    }

    /**
     * Scroll Carousel left or right
     * @param {number} direction - Direction to scroll: -1 for left, 1 for right
     */
    function scrollCarousel(direction) {
        const track = document.getElementById('carouselTrack');
        if (!track) {
            console.warn('[Navigation] Carousel track not found');
            return;
        }

        const scrollAmount = 160;
        track.scrollBy({
            left: scrollAmount * direction,
            behavior: 'smooth'
        });

        // Dispatch event for other modules
        document.dispatchEvent(new CustomEvent('myavana:carousel:scrolled', {
            detail: { direction: direction }
        }));
    }

    /**
     * Initialize navigation event listeners
     */
    function init() {
        console.log('[Navigation] Initializing navigation module');

        // Set calendar as default view on initialization
        switchView('calendar');
        setCalendarView('month');

        // View buttons (header) - Remove if already attached
        document.querySelectorAll('.view-btn').forEach(btn => {
            // Clone and replace to remove old listeners
            const newBtn = btn.cloneNode(true);
            btn.parentNode.replaceChild(newBtn, btn);

            newBtn.addEventListener('click', function() {
                const viewName = this.getAttribute('data-view');
                switchView(viewName);
            });
        });

        // Timeline control tabs - Remove if already attached
        document.querySelectorAll('.tab').forEach(tab => {
            const newTab = tab.cloneNode(true);
            tab.parentNode.replaceChild(newTab, tab);

            newTab.addEventListener('click', function() {
                const onclickAttr = this.getAttribute('onclick');
                if (onclickAttr) {
                    const match = onclickAttr.match(/'([^']+)'/);
                    if (match && match[1]) {
                        switchView(match[1]);
                    }
                }
            });
        });

        console.log('[Navigation] Navigation module initialized');
    }

    // Public API
    return {
        init: init,
        initSlider: initSlider,
        switchView: switchView,
        setCalendarView: setCalendarView,
        scrollCarousel: scrollCarousel
    };
})();

// Expose global functions for backward compatibility with inline onclick handlers
// These reference the module functions so existing HTML will continue to work
if (!window.switchView) {
    window.switchView = function(viewName) {
        MyavanaTimeline.Navigation.switchView(viewName);
    };
}

if (!window.setCalendarView) {
    window.setCalendarView = function(view) {
        MyavanaTimeline.Navigation.setCalendarView(view);
    };
}

if (!window.scrollCarousel) {
    window.scrollCarousel = function(direction) {
        MyavanaTimeline.Navigation.scrollCarousel(direction);
    };
}

if (!window.initSlider) {
    window.initSlider = function() {
        MyavanaTimeline.Navigation.initSlider();
    };
}
