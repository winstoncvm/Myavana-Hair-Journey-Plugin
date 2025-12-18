class AdvancedDashboard {
    constructor() {
        this.currentView = 'overview';
        this.charts = {};
        this.calendar = null;
        this.isDarkMode = localStorage.getItem('dashboard-theme') === 'dark';
        
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.initializeTheme();
        this.loadDashboardData();
        this.setupCharts();
        this.initializeCalendar();
        this.startRealTimeUpdates();
    }

    setupEventListeners() {
        // View switching
        document.querySelectorAll('[data-view]').forEach(button => {
            button.addEventListener('click', (e) => {
                this.switchView(e.target.dataset.view);
            });
        });

        // Theme toggle
        const themeToggle = document.getElementById('theme-toggle');
        if (themeToggle) {
            themeToggle.addEventListener('click', () => this.toggleTheme());
        }

        // Quick actions
        document.querySelectorAll('.quick-action-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.handleQuickAction(e.target.dataset.action);
            });
        });

        // Goal management
        const addGoalBtn = document.getElementById('add-goal-btn');
        if (addGoalBtn) {
            addGoalBtn.addEventListener('click', () => {
                this.showGoalModal();
            });
        }

        // Filters
        const dateFilter = document.getElementById('date-filter');
        if (dateFilter) {
            dateFilter.addEventListener('change', (e) => {
                this.filterByDate(e.target.value);
            });
        }

        const categoryFilter = document.getElementById('category-filter');
        if (categoryFilter) {
            categoryFilter.addEventListener('change', (e) => {
                this.filterByCategory(e.target.value);
            });
        }

        // Search
        const dashboardSearch = document.getElementById('dashboard-search');
        if (dashboardSearch) {
            dashboardSearch.addEventListener('input', (e) => {
                this.searchActivities(e.target.value);
            });
        }

        // Export functionality
        const exportBtn = document.getElementById('export-data-btn');
        if (exportBtn) {
            exportBtn.addEventListener('click', () => {
                this.exportData();
            });
        }
    }

    initializeTheme() {
        if (this.isDarkMode) {
            document.documentElement.setAttribute('data-theme', 'dark');
            const themeToggle = document.getElementById('theme-toggle');
            if (themeToggle) themeToggle.classList.add('active');
        }
    }

    toggleTheme() {
        this.isDarkMode = !this.isDarkMode;
        document.documentElement.setAttribute('data-theme', this.isDarkMode ? 'dark' : 'light');
        localStorage.setItem('dashboard-theme', this.isDarkMode ? 'dark' : 'light');
        
        const toggle = document.getElementById('theme-toggle');
        if (toggle) toggle.classList.toggle('active', this.isDarkMode);
        
        // Update charts for theme
        this.updateChartsTheme();
    }

    switchView(viewName) {
        // Update active nav
        document.querySelectorAll('[data-view]').forEach(btn => {
            btn.classList.remove('active');
        });
        const targetBtn = document.querySelector(`[data-view="${viewName}"]`);
        if (targetBtn) targetBtn.classList.add('active');

        // Show/hide content sections
        document.querySelectorAll('.dashboard-section').forEach(section => {
            section.style.display = 'none';
        });
        
        const targetSection = document.getElementById(`${viewName}-view`);
        if (targetSection) {
            targetSection.style.display = 'block';
            this.currentView = viewName;
            
            // Load view-specific data
            this.loadViewData(viewName);
        }
    }

    loadDashboardData() {
        // Load diary entries
        fetch(myavana_ajax.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'myavana_get_diary_entries',
                security: myavana_ajax.nonce
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.processEntryData(data.data.entries);
            }
        })
        .catch(error => {
            console.error('Error loading entries:', error);
        });

        // Load user stats if gamification is available
        if (typeof myavana_ajax.user_id !== 'undefined') {
            this.loadGamificationStats();
        }
    }

    loadGamificationStats() {
        fetch(myavana_ajax.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'get_user_stats',
                nonce: myavana_ajax.nonce
            })
        })
        .then(response => {
            // Check if response is JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                console.warn('Gamification stats response is not JSON, skipping...');
                return { success: false, message: 'Non-JSON response' };
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                this.updateGamificationStats(data.data);
            } else {
                console.log('Gamification stats not available:', data.message || 'Unknown error');
            }
        })
        .catch(error => {
            console.warn('Gamification stats not available:', error.message);
            // Don't treat this as a critical error - gamification is optional
        });
    }

    processEntryData(entries) {
        if (!entries || entries.length === 0) return;

        // Calculate basic stats
        const totalEntries = entries.length;
        const healthRatings = entries
            .map(entry => parseInt(entry.rating))
            .filter(rating => !isNaN(rating));
        
        const avgHealth = healthRatings.length > 0 
            ? (healthRatings.reduce((a, b) => a + b, 0) / healthRatings.length).toFixed(1)
            : 0;

        // Calculate streak (simplified - entries in consecutive days)
        const currentStreak = this.calculateStreakFromEntries(entries);

        // Update dashboard stats
        this.updateDashboardStats({
            total_entries: totalEntries,
            health_score: avgHealth,
            current_streak: currentStreak,
            goals_completed: 0 // Will be updated from gamification if available
        });

        // Update timeline with recent entries
        this.updateTimeline(entries.slice(0, 10));
    }

    calculateStreakFromEntries(entries) {
        if (!entries || entries.length === 0) return 0;
        
        // Sort entries by date (most recent first)
        const sortedEntries = entries.sort((a, b) => new Date(b.date) - new Date(a.date));
        
        let streak = 0;
        let currentDate = new Date();
        currentDate.setHours(0, 0, 0, 0);
        
        for (const entry of sortedEntries) {
            const entryDate = new Date(entry.date);
            entryDate.setHours(0, 0, 0, 0);
            
            const daysDiff = Math.floor((currentDate - entryDate) / (1000 * 60 * 60 * 24));
            
            if (daysDiff === streak) {
                streak++;
            } else if (daysDiff > streak) {
                break;
            }
        }
        
        return streak;
    }

    updateGamificationStats(stats) {
        // Update additional stats from gamification system
        const currentStreakEl = document.getElementById('current-streak');
        if (currentStreakEl) currentStreakEl.textContent = stats.current_streak || '0';

        const goalsCompletedEl = document.getElementById('goals-completed');
        if (goalsCompletedEl) goalsCompletedEl.textContent = stats.total_goals_completed || '0';

        const totalPointsEl = document.getElementById('total-points');
        if (totalPointsEl) totalPointsEl.textContent = stats.total_points || '0';

        const currentLevelEl = document.getElementById('current-level');
        if (currentLevelEl) currentLevelEl.textContent = stats.current_level || '1';
        
        // Update achievements if available
        if (stats.recent_achievements) {
            this.updateAchievements(stats.recent_achievements);
        }
    }

    setupCharts() {
        // Progress Chart
        const progressCtx = document.getElementById('progress-chart');
        if (progressCtx) {
            this.charts.progress = new Chart(progressCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Completed', 'In Progress', 'Remaining'],
                    datasets: [{
                        data: [65, 25, 10],
                        backgroundColor: [
                            'var(--success-color)',
                            'var(--warning-color)',
                            'var(--neutral-color)'
                        ],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                color: 'var(--text-color)',
                                usePointStyle: true
                            }
                        }
                    }
                }
            });
        }

        // Activity Chart
        const activityCtx = document.getElementById('activity-chart');
        if (activityCtx) {
            this.charts.activity = new Chart(activityCtx, {
                type: 'line',
                data: {
                    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                    datasets: [{
                        label: 'Hair Care Activities',
                        data: [12, 19, 15, 25, 22, 18, 24],
                        borderColor: 'var(--primary-color)',
                        backgroundColor: 'var(--primary-color-alpha)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            labels: {
                                color: 'var(--text-color)'
                            }
                        }
                    },
                    scales: {
                        x: {
                            ticks: { color: 'var(--text-color)' },
                            grid: { color: 'var(--border-color)' }
                        },
                        y: {
                            ticks: { color: 'var(--text-color)' },
                            grid: { color: 'var(--border-color)' }
                        }
                    }
                }
            });
        }

        // Goal Progress Chart
        const goalCtx = document.getElementById('goal-progress-chart');
        if (goalCtx) {
            this.charts.goalProgress = new Chart(goalCtx, {
                type: 'bar',
                data: {
                    labels: ['Hair Growth', 'Moisture Level', 'Strength', 'Overall Health'],
                    datasets: [{
                        label: 'Progress %',
                        data: [78, 85, 92, 82],
                        backgroundColor: [
                            'var(--primary-color)',
                            'var(--secondary-color)',
                            'var(--success-color)',
                            'var(--accent-color)'
                        ],
                        borderRadius: 8
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        x: {
                            ticks: { color: 'var(--text-color)' },
                            grid: { display: false }
                        },
                        y: {
                            ticks: { color: 'var(--text-color)' },
                            grid: { color: 'var(--border-color)' },
                            max: 100
                        }
                    }
                }
            });
        }
    }

    initializeCalendar() {
        const calendarEl = document.getElementById('dashboard-calendar');
        if (calendarEl && typeof FullCalendar !== 'undefined') {
            this.calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                events: this.loadCalendarEvents.bind(this),
                eventClick: this.handleEventClick.bind(this),
                dateClick: this.handleDateClick.bind(this),
                eventColor: 'var(--primary-color)',
                height: 'auto'
            });
            this.calendar.render();
        }
    }

    loadCalendarEvents(info, successCallback, failureCallback) {
        const data = {
            action: 'get_calendar_events',
            start: info.startStr,
            end: info.endStr,
            nonce: myavana_ajax.nonce
        };

        fetch(myavana_ajax.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                successCallback(data.data);
            } else {
                failureCallback(data.data);
            }
        })
        .catch(error => {
            failureCallback(error);
        });
    }

    handleQuickAction(action) {
        switch (action) {
            case 'add-entry':
                this.showAddEntryModal();
                break;
            case 'add-photo':
                this.showAddPhotoModal();
                break;
            case 'track-product':
                this.showTrackProductModal();
                break;
            case 'schedule-reminder':
                this.showReminderModal();
                break;
            case 'view-analytics':
                this.switchView('analytics');
                break;
            case 'set-goal':
                this.showGoalModal();
                break;
        }
    }

    showAddEntryModal() {
        // Implementation for add entry modal
        const modal = this.createModal('Add Hair Journey Entry', `
            <form id="add-entry-form">
                <div class="form-group">
                    <label for="entry-title">Title</label>
                    <input type="text" id="entry-title" name="title" required>
                </div>
                <div class="form-group">
                    <label for="entry-content">Description</label>
                    <textarea id="entry-content" name="content" rows="4" required></textarea>
                </div>
                <div class="form-group">
                    <label for="entry-category">Category</label>
                    <select id="entry-category" name="category">
                        <option value="wash">Hair Wash</option>
                        <option value="treatment">Treatment</option>
                        <option value="styling">Styling</option>
                        <option value="measurement">Measurement</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="this.closest('.modal').remove()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Entry</button>
                </div>
            </form>
        `);

        document.getElementById('add-entry-form').addEventListener('submit', (e) => {
            e.preventDefault();
            this.submitEntry(new FormData(e.target));
            modal.remove();
        });
    }

    showGoalModal() {
        const modal = this.createModal('Set New Goal', `
            <form id="goal-form">
                <div class="form-group">
                    <label for="goal-title">Goal Title</label>
                    <input type="text" id="goal-title" name="title" required>
                </div>
                <div class="form-group">
                    <label for="goal-description">Description</label>
                    <textarea id="goal-description" name="description" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label for="goal-target">Target Date</label>
                    <input type="date" id="goal-target" name="target_date" required>
                </div>
                <div class="form-group">
                    <label for="goal-category">Category</label>
                    <select id="goal-category" name="category">
                        <option value="growth">Hair Growth</option>
                        <option value="health">Hair Health</option>
                        <option value="style">Styling</option>
                        <option value="routine">Routine</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="this.closest('.modal').remove()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Goal</button>
                </div>
            </form>
        `);

        document.getElementById('goal-form').addEventListener('submit', (e) => {
            e.preventDefault();
            this.submitGoal(new FormData(e.target));
            modal.remove();
        });
    }

    createModal(title, content) {
        const modal = document.createElement('div');
        modal.className = 'modal fade-in';
        modal.innerHTML = `
            <div class="modal-content">
                <div class="modal-header">
                    <h3>${title}</h3>
                    <button type="button" class="modal-close" onclick="this.closest('.modal').remove()">&times;</button>
                </div>
                <div class="modal-body">
                    ${content}
                </div>
            </div>
        `;
        document.body.appendChild(modal);
        return modal;
    }

    submitEntry(formData) {
        formData.append('action', 'myavana_add_diary_entry');
        formData.append('myavana_nonce', myavana_ajax.nonce);

        fetch(myavana_ajax.ajax_url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.showNotification('Entry added successfully!', 'success');
                this.loadDashboardData(); // Refresh data
            } else {
                this.showNotification(data.data || 'Error adding entry', 'error');
            }
        })
        .catch(error => {
            this.showNotification('Error adding entry', 'error');
        });
    }

    submitGoal(formData) {
        formData.append('action', 'add_hair_goal');
        formData.append('nonce', myavana_ajax.nonce);

        fetch(myavana_ajax.ajax_url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.showNotification('Goal created successfully!', 'success');
                this.loadDashboardData();
            } else {
                this.showNotification(data.data || 'Error creating goal', 'error');
            }
        })
        .catch(error => {
            this.showNotification('Error creating goal', 'error');
        });
    }

    exportData() {
        const data = {
            action: 'export_hair_journey_data',
            format: 'json', // Could be extended to support CSV, PDF
            nonce: myavana_ajax.nonce
        };

        fetch(myavana_ajax.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams(data)
        })
        .then(response => response.blob())
        .then(blob => {
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `hair-journey-data-${new Date().toISOString().split('T')[0]}.json`;
            a.click();
            window.URL.revokeObjectURL(url);
            this.showNotification('Data exported successfully!', 'success');
        })
        .catch(error => {
            this.showNotification('Error exporting data', 'error');
        });
    }

    updateChartsTheme() {
        Object.values(this.charts).forEach(chart => {
            chart.options.plugins.legend.labels.color = 'var(--text-color)';
            if (chart.options.scales) {
                Object.values(chart.options.scales).forEach(scale => {
                    if (scale.ticks) scale.ticks.color = 'var(--text-color)';
                    if (scale.grid) scale.grid.color = 'var(--border-color)';
                });
            }
            chart.update();
        });
    }

    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type} fade-in`;
        notification.innerHTML = `
            <span>${message}</span>
            <button onclick="this.parentElement.remove()">&times;</button>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 5000);
    }

    filterByDate(dateRange) {
        // Implementation for date filtering
        console.log('Filtering by date:', dateRange);
    }

    filterByCategory(category) {
        // Implementation for category filtering
        console.log('Filtering by category:', category);
    }

    searchActivities(query) {
        // Implementation for activity search
        console.log('Searching activities:', query);
    }

    startRealTimeUpdates() {
        // Update dashboard every 5 minutes
        setInterval(() => {
            this.loadDashboardData();
        }, 300000);
    }

    loadViewData(viewName) {
        switch (viewName) {
            case 'analytics':
                this.loadAnalyticsData();
                break;
            case 'calendar':
                if (this.calendar) {
                    this.calendar.refetchEvents();
                }
                break;
            case 'goals':
                this.loadGoalsData();
                break;
        }
    }

    loadAnalyticsData() {
        // Load detailed analytics data
        const data = {
            action: 'get_analytics_data',
            nonce: myavana_ajax.nonce
        };

        fetch(myavana_ajax.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.updateAnalyticsCharts(data.data);
            }
        });
    }

    loadGoalsData() {
        // Load goals data
        const data = {
            action: 'get_goals_data',
            nonce: myavana_ajax.nonce
        };

        fetch(myavana_ajax.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.updateGoalsDisplay(data.data);
            }
        });
    }

    updateDashboardStats(data) {
        // Update stats cards with real data
        const totalEntriesEl = document.getElementById('total-entries');
        if (totalEntriesEl) totalEntriesEl.textContent = data.total_entries || '0';

        const currentStreakEl = document.getElementById('current-streak');
        if (currentStreakEl) currentStreakEl.textContent = data.current_streak || '0';

        const goalsCompletedEl = document.getElementById('goals-completed');
        if (goalsCompletedEl) goalsCompletedEl.textContent = data.goals_completed || '0';

        const healthScoreEl = document.getElementById('hair-health-score');
        if (healthScoreEl) healthScoreEl.textContent = data.health_score || '0';
    }

    updateTimeline(timelineData) {
        const timelineContainer = document.querySelector('.activity-timeline');
        if (timelineContainer && timelineData) {
            // Update timeline with real data
            timelineContainer.innerHTML = timelineData.map(item => `
                <div class="timeline-item">
                    <div class="timeline-marker"></div>
                    <div class="timeline-content">
                        <h4>${item.title}</h4>
                        <p>${item.description}</p>
                        <span class="timeline-date">${item.date}</span>
                    </div>
                </div>
            `).join('');
        }
    }

    updateAchievements(achievementsData) {
        const achievementsContainer = document.querySelector('.achievements-grid');
        if (achievementsContainer && achievementsData) {
            // Update achievements with real data
            achievementsContainer.innerHTML = achievementsData.map(achievement => `
                <div class="achievement-badge ${achievement.earned ? 'earned' : ''}">
                    <div class="achievement-icon">${achievement.icon}</div>
                    <div class="achievement-info">
                        <h4>${achievement.title}</h4>
                        <p>${achievement.description}</p>
                    </div>
                </div>
            `).join('');
        }
    }
}

// Initialize dashboard when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    if (document.querySelector('.myavana-advanced-dashboard')) {
        new AdvancedDashboard();
    }
});

// PWA Service Worker Registration
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function() {
        navigator.serviceWorker.register('/wp-content/plugins/myavana-hair-journey/assets/js/sw.js')
            .then(function(registration) {
                console.log('ServiceWorker registration successful');
            })
            .catch(function(err) {
                console.log('ServiceWorker registration failed: ', err);
            });
    });
}