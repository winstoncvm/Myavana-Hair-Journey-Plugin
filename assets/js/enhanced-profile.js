/**
 * Enhanced Profile JavaScript
 * Handles interactive functionality for the enhanced profile
 */

class MyavanaEnhancedProfile {
    constructor() {
        this.charts = {};
        this.currentUser = myavanaProfile.user_id;
        this.isOwner = myavanaProfile.is_owner;
        this.init();
    }

    init() {
        this.bindEvents();
        this.initializeCharts();
        this.loadProfileData();
    }

    bindEvents() {
        // Edit profile button
        document.getElementById('editProfileBtn')?.addEventListener('click', () => {
            this.openEditModal();
        });

        // Chart controls
        document.querySelectorAll('.chart-control-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.switchChartPeriod(e.target.dataset.period);
            });
        });

        // Avatar upload
        document.getElementById('avatarUpload')?.addEventListener('change', (e) => {
            this.handleAvatarUpload(e.target.files[0]);
        });

        // Theme toggle
        document.getElementById('themeToggle')?.addEventListener('click', () => {
            this.toggleTheme();
        });
    }

    async loadProfileData() {
        try {
            const response = await this.makeRequest('get_profile_data', {
                user_id: this.currentUser
            });

            if (response.success) {
                this.updateProfileDisplay(response.data);
                this.updateCharts(response.data.analytics);
            }
        } catch (error) {
            console.error('Error loading profile data:', error);
        }
    }

    updateProfileDisplay(data) {
        // Update stats
        document.getElementById('totalEntries')?.textContent = data.stats.total_entries || 0;
        document.getElementById('avgHealthScore')?.textContent = (data.stats.avg_health_score || 0).toFixed(1);
        document.getElementById('currentStreak')?.textContent = data.stats.current_streak || 0;
        document.getElementById('achievementsEarned')?.textContent = data.stats.achievements_earned || 0;

        // Update hair profile
        this.updateHairProfile(data.hair_profile);
        
        // Update goals
        this.updateGoals(data.goals);
        
        // Update recent activity
        this.updateRecentActivity(data.recent_activity);
        
        // Update achievements
        this.updateAchievements(data.achievements);
    }

    updateHairProfile(hairProfile) {
        const container = document.getElementById('hairProfileData');
        if (!container || !hairProfile) return;

        const profileData = [
            { label: 'Hair Type', value: hairProfile.hair_type || 'Not set' },
            { label: 'Porosity', value: hairProfile.porosity || 'Not set' },
            { label: 'Density', value: hairProfile.density || 'Not set' },
            { label: 'Length', value: hairProfile.length || 'Not set' },
            { label: 'Texture', value: hairProfile.texture || 'Not set' },
            { label: 'Elasticity', value: hairProfile.elasticity || 'Not set' }
        ];

        container.innerHTML = profileData.map(item => `
            <div class="hair-attribute">
                <span class="attribute-label">${item.label}</span>
                <span class="attribute-value">${item.value}</span>
            </div>
        `).join('');
    }

    updateGoals(goals) {
        const container = document.getElementById('goalsList');
        if (!container || !goals) return;

        container.innerHTML = goals.map(goal => `
            <div class="goal-item">
                <div class="goal-icon">
                    <i class="${this.getGoalIcon(goal.category)}"></i>
                </div>
                <div class="goal-text">${goal.title}</div>
                <div class="goal-status ${goal.status.toLowerCase()}">${goal.status}</div>
            </div>
        `).join('');
    }

    updateRecentActivity(activities) {
        const container = document.getElementById('recentActivity');
        if (!container || !activities) return;

        container.innerHTML = activities.slice(0, 10).map(activity => `
            <div class="activity-item">
                <div class="activity-icon">
                    <i class="${this.getActivityIcon(activity.type)}"></i>
                </div>
                <div class="activity-content">
                    <div class="activity-title">${activity.title}</div>
                    <div class="activity-description">${activity.description}</div>
                </div>
                <div class="activity-time">${this.formatTimeAgo(activity.date)}</div>
            </div>
        `).join('');
    }

    updateAchievements(achievements) {
        const container = document.getElementById('achievementsList');
        if (!container || !achievements) return;

        container.innerHTML = achievements.map(achievement => `
            <div class="achievement-item ${achievement.earned ? '' : 'locked'}" title="${achievement.description}">
                <div class="achievement-icon">${achievement.icon}</div>
                <div class="achievement-title">${achievement.title}</div>
                ${achievement.earned ? `<div class="achievement-date">${this.formatDate(achievement.earned_date)}</div>` : ''}
            </div>
        `).join('');
    }

    initializeCharts() {
        // Health Progress Chart
        const healthCtx = document.getElementById('healthProgressChart');
        if (healthCtx && typeof Chart !== 'undefined') {
            this.charts.healthProgress = new Chart(healthCtx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Health Score',
                        data: [],
                        borderColor: 'rgb(0, 124, 186)',
                        backgroundColor: 'rgba(0, 124, 186, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 10,
                            grid: {
                                color: 'rgba(0,0,0,0.1)'
                            }
                        },
                        x: {
                            grid: {
                                color: 'rgba(0,0,0,0.1)'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }

        // Hair Length Chart
        const lengthCtx = document.getElementById('lengthProgressChart');
        if (lengthCtx && typeof Chart !== 'undefined') {
            this.charts.lengthProgress = new Chart(lengthCtx, {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Length (inches)',
                        data: [],
                        backgroundColor: 'rgba(80, 200, 120, 0.8)',
                        borderColor: 'rgb(80, 200, 120)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0,0,0,0.1)'
                            }
                        },
                        x: {
                            grid: {
                                color: 'rgba(0,0,0,0.1)'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }
    }

    updateCharts(analyticsData) {
        if (!analyticsData) return;

        // Update health progress chart
        if (this.charts.healthProgress && analyticsData.health_history) {
            this.charts.healthProgress.data.labels = analyticsData.health_history.labels;
            this.charts.healthProgress.data.datasets[0].data = analyticsData.health_history.data;
            this.charts.healthProgress.update();
        }

        // Update length progress chart
        if (this.charts.lengthProgress && analyticsData.length_history) {
            this.charts.lengthProgress.data.labels = analyticsData.length_history.labels;
            this.charts.lengthProgress.data.datasets[0].data = analyticsData.length_history.data;
            this.charts.lengthProgress.update();
        }
    }

    switchChartPeriod(period) {
        // Update active button
        document.querySelectorAll('.chart-control-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelector(`[data-period="${period}"]`)?.classList.add('active');

        // Reload chart data for new period
        this.loadChartData(period);
    }

    async loadChartData(period) {
        try {
            const response = await this.makeRequest('get_analytics_data', {
                user_id: this.currentUser,
                period: period
            });

            if (response.success) {
                this.updateCharts(response.data);
            }
        } catch (error) {
            console.error('Error loading chart data:', error);
        }
    }

    openEditModal() {
        if (!this.isOwner) return;

        // Create and show edit modal
        const modal = this.createModal('Edit Profile', this.getEditProfileForm());
        document.body.appendChild(modal);

        // Bind form submission
        modal.querySelector('#profileEditForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.saveProfile(new FormData(e.target));
        });
    }

    getEditProfileForm() {
        return `
            <form id="profileEditForm">
                <div class="form-group">
                    <label class="form-label">Display Name</label>
                    <input type="text" name="display_name" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Bio</label>
                    <textarea name="bio" class="form-textarea" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Location</label>
                    <input type="text" name="location" class="form-input">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Hair Goals</label>
                    <textarea name="hair_goals" class="form-textarea" rows="3"></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="this.closest('.modal').remove()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        `;
    }

    async saveProfile(formData) {
        try {
            formData.append('action', 'update_profile');
            formData.append('user_id', this.currentUser);

            const response = await fetch(myavanaProfile.ajax_url, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                this.showNotification(myavanaProfile.strings.save_success, 'success');
                document.querySelector('.modal')?.remove();
                this.loadProfileData(); // Refresh data
            } else {
                this.showNotification(result.data || myavanaProfile.strings.save_error, 'error');
            }
        } catch (error) {
            console.error('Error saving profile:', error);
            this.showNotification(myavanaProfile.strings.save_error, 'error');
        }
    }

    handleAvatarUpload(file) {
        if (!file || !this.isOwner) return;

        const formData = new FormData();
        formData.append('avatar', file);
        formData.append('action', 'upload_avatar');
        formData.append('user_id', this.currentUser);

        // Show upload progress
        this.showNotification('Uploading avatar...', 'info');

        fetch(myavanaProfile.ajax_url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                this.showNotification('Avatar updated successfully!', 'success');
                // Update avatar display
                document.getElementById('profileAvatar').src = result.data.avatar_url;
            } else {
                this.showNotification(result.data || 'Error uploading avatar', 'error');
            }
        })
        .catch(error => {
            console.error('Avatar upload error:', error);
            this.showNotification('Error uploading avatar', 'error');
        });
    }

    toggleTheme() {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('myavana-theme', newTheme);

        // Update chart colors for new theme
        this.updateChartTheme(newTheme);
    }

    updateChartTheme(theme) {
        const textColor = theme === 'dark' ? '#ffffff' : '#212529';
        const gridColor = theme === 'dark' ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.1)';

        Object.values(this.charts).forEach(chart => {
            if (chart.options.scales) {
                Object.values(chart.options.scales).forEach(scale => {
                    if (scale.ticks) scale.ticks.color = textColor;
                    if (scale.grid) scale.grid.color = gridColor;
                });
            }
            chart.update();
        });
    }

    async makeRequest(action, data = {}) {
        const formData = new FormData();
        formData.append('action', action);
        formData.append('nonce', myavanaProfile.nonce);

        Object.entries(data).forEach(([key, value]) => {
            formData.append(key, value);
        });

        const response = await fetch(myavanaProfile.ajax_url, {
            method: 'POST',
            body: formData
        });

        return await response.json();
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
        return modal;
    }

    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.remove();
        }, 5000);
    }

    getGoalIcon(category) {
        const icons = {
            growth: 'fas fa-arrow-up',
            health: 'fas fa-heart',
            style: 'fas fa-cut',
            routine: 'fas fa-calendar-check'
        };
        return icons[category] || 'fas fa-bullseye';
    }

    getActivityIcon(type) {
        const icons = {
            entry: 'fas fa-edit',
            photo: 'fas fa-camera',
            goal: 'fas fa-bullseye',
            achievement: 'fas fa-trophy',
            analysis: 'fas fa-chart-line'
        };
        return icons[type] || 'fas fa-circle';
    }

    formatDate(dateString) {
        return new Date(dateString).toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric'
        });
    }

    formatTimeAgo(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diffMs = now - date;
        const diffMins = Math.floor(diffMs / 60000);
        const diffHours = Math.floor(diffMins / 60);
        const diffDays = Math.floor(diffHours / 24);

        if (diffMins < 60) return `${diffMins}m ago`;
        if (diffHours < 24) return `${diffHours}h ago`;
        if (diffDays < 7) return `${diffDays}d ago`;
        return this.formatDate(dateString);
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    if (document.querySelector('.myavana-enhanced-profile')) {
        new MyavanaEnhancedProfile();
    }
});

// Load saved theme
const savedTheme = localStorage.getItem('myavana-theme');
if (savedTheme) {
    document.documentElement.setAttribute('data-theme', savedTheme);
}