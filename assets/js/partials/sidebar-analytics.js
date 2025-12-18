/**
 * MYAVANA Sidebar Analytics - Enhanced Components
 */

// ===============================================
// PROGRESS RING ANIMATION
// ===============================================

function initializeProgressRing() {
    const ring = document.getElementById('progressRingCircle');
    const valueEl = document.getElementById('progressRingValue');

    if (!ring || !valueEl) return;

    const value = parseInt(valueEl.textContent);
    const radius = 52;
    const circumference = 2 * Math.PI * radius;
    const offset = circumference - (value / 100) * circumference;

    // Animate the ring
    setTimeout(() => {
        ring.style.strokeDashoffset = offset;
    }, 300);

    console.log('[Sidebar Analytics] Progress ring initialized:', value + '%');
}

// ===============================================
// ACTIVITY HEATMAP
// ===============================================

function initializeActivityHeatmap() {
    const heatmapContainer = document.getElementById('activityHeatmap');
    if (!heatmapContainer) return;

    // Get last 28 days (4 weeks)
    const days = [];
    const today = new Date();

    for (let i = 27; i >= 0; i--) {
        const date = new Date(today);
        date.setDate(date.getDate() - i);
        days.push(date);
    }

    // Clear container
    heatmapContainer.innerHTML = '';

    // Get activity data from entries (if available)
    const activityData = getActivityData(days);

    // Create heatmap cells
    days.forEach((date, index) => {
        const dayEl = document.createElement('div');
        dayEl.className = 'myavana-heatmap-day';

        const dateStr = date.toISOString().split('T')[0];
        const level = activityData[dateStr] || 0;

        dayEl.setAttribute('data-level', level);
        dayEl.setAttribute('data-date', dateStr);
        dayEl.setAttribute('title', `${dateStr}: ${level} ${level === 1 ? 'entry' : 'entries'}`);

        heatmapContainer.appendChild(dayEl);
    });

    console.log('[Sidebar Analytics] Activity heatmap initialized with', days.length, 'days');
}

/**
 * Get activity data for heatmap
 * This is a simplified version - can be enhanced with actual entry data via AJAX
 */
function getActivityData(days) {
    const data = {};

    // Try to get data from calendar if available
    const calendarDataEl = document.getElementById('calendarDataHjn');
    if (calendarDataEl) {
        try {
            const calendarData = JSON.parse(calendarDataEl.textContent);
            const entries = calendarData.entries || [];

            entries.forEach(entry => {
                const dateStr = entry.date;
                data[dateStr] = (data[dateStr] || 0) + 1;
            });
        } catch (e) {
            console.warn('[Heatmap] Could not parse calendar data');
        }
    }

    // Convert counts to levels (0-3)
    Object.keys(data).forEach(date => {
        const count = data[date];
        if (count === 0) data[date] = 0;
        else if (count === 1) data[date] = 1;
        else if (count === 2) data[date] = 2;
        else data[date] = 3; // 3+ entries
    });

    return data;
}

// ===============================================
// CHART.JS RESPONSIVE CONFIGURATION
// ===============================================

function makeChartsResponsive() {
    // Get canvas elements
    const healthChart = document.getElementById('healthTrendChart');
    const activityChart = document.getElementById('activityChart');

    if (healthChart) {
        // Remove fixed width/height attributes
        healthChart.removeAttribute('width');
        healthChart.removeAttribute('height');

        // Set responsive parent container
        const healthContainer = healthChart.parentElement;
        if (healthContainer) {
            healthContainer.style.position = 'relative';
            healthContainer.style.height = '200px';
        }
    }

    if (activityChart) {
        // Remove fixed width/height attributes
        activityChart.removeAttribute('width');
        activityChart.removeAttribute('height');

        // Set responsive parent container
        const activityContainer = activityChart.parentElement;
        if (activityContainer) {
            activityContainer.style.position = 'relative';
            activityContainer.style.height = '200px';
        }
    }

    console.log('[Sidebar Analytics] Charts made responsive');
}

// ===============================================
// PERIOD CHANGE HANDLER
// ===============================================

function handlePeriodChange() {
    const periodSelect = document.getElementById('myavanaAnalyticsPeriod');
    if (!periodSelect) return;

    periodSelect.addEventListener('change', function() {
        const period = this.value;
        console.log('[Sidebar Analytics] Period changed to:', period, 'days');

        // Show loading state
        showAnalyticsLoading();

        // Fetch new data via AJAX
        fetchAnalyticsData(period);
    });
}

function showAnalyticsLoading() {
    const statsCards = document.querySelectorAll('.myavana-stat-number');
    statsCards.forEach(card => {
        card.style.opacity = '0.5';
    });
}

function hideAnalyticsLoading() {
    const statsCards = document.querySelectorAll('.myavana-stat-number');
    statsCards.forEach(card => {
        card.style.opacity = '1';
    });
}

function fetchAnalyticsData(period) {
    // This would fetch data from WordPress via AJAX
    // For now, just hide loading after delay
    setTimeout(() => {
        hideAnalyticsLoading();
        console.log('[Sidebar Analytics] Data fetched for period:', period);
    }, 500);
}

// ===============================================
// INITIALIZE ALL COMPONENTS
// ===============================================

function initializeSidebarAnalytics() {
    // Make sure we're on the analytics tab
    const analyticsTab = document.getElementById('analyticsTab');
    if (!analyticsTab) {
        console.warn('[Sidebar Analytics] Analytics tab not found');
        return;
    }

    console.log('[Sidebar Analytics] Initializing components...');

    // Initialize components
    makeChartsResponsive();
    initializeProgressRing();
    initializeActivityHeatmap();
    handlePeriodChange();

    // Re-initialize when tab is switched to analytics
    const analyticsTabBtn = document.querySelector('[data-tab="analytics"]');
    if (analyticsTabBtn) {
        analyticsTabBtn.addEventListener('click', function() {
            setTimeout(() => {
                initializeProgressRing();
                initializeActivityHeatmap();
                makeChartsResponsive();
            }, 100);
        });
    }

    console.log('[Sidebar Analytics] All components initialized');
}

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', function() {
    // Wait a bit for other scripts to load
    setTimeout(initializeSidebarAnalytics, 500);
});

// Also initialize when switching to analytics tab
document.addEventListener('click', function(e) {
    if (e.target.closest('[data-tab="analytics"]')) {
        setTimeout(initializeSidebarAnalytics, 200);
    }
});

console.log('Sidebar analytics enhancements loaded');
