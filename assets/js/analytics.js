/**
 * MYAVANA Hair Journey Analytics
 * Premium analytics dashboard for tracking hair health progress
 */

class MyavanaAnalytics {
    constructor() {
        this.healthChart = null;
        this.activityChart = null;
        this.currentPeriod = 30;
        this.init();
    }

    init() {
        document.addEventListener('DOMContentLoaded', () => {
            this.initializeCharts();
            this.bindEvents();
            this.loadInitialData();
        });
    }

    initializeCharts() {
        this.initHealthTrendChart();
        this.initActivityChart();
    }

    initHealthTrendChart() {
        const canvas = document.getElementById('healthTrendChart');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        this.healthChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'Health Score',
                    data: [],
                    borderColor: 'var(--myavana-coral)',
                    backgroundColor: 'rgba(231, 166, 144, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: 'var(--myavana-coral)',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 6,
                    pointHoverRadius: 8,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(34, 35, 35, 0.95)',
                        titleColor: '#ffffff',
                        bodyColor: '#ffffff',
                        borderColor: 'var(--myavana-coral)',
                        borderWidth: 1,
                        cornerRadius: 8,
                        titleFont: {
                            family: 'Archivo Expanded',
                            size: 14,
                            weight: '600'
                        },
                        bodyFont: {
                            family: 'Archivo',
                            size: 13
                        },
                        callbacks: {
                            label: (context) => `Health Score: ${context.parsed.y.toFixed(1)}/10`
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            color: 'rgba(238, 236, 225, 0.5)',
                            drawBorder: false
                        },
                        ticks: {
                            color: 'var(--myavana-slate)',
                            font: {
                                family: 'Archivo',
                                size: 12
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        max: 10,
                        grid: {
                            color: 'rgba(238, 236, 225, 0.5)',
                            drawBorder: false
                        },
                        ticks: {
                            color: 'var(--myavana-slate)',
                            font: {
                                family: 'Archivo',
                                size: 12
                            },
                            callback: (value) => `${value}/10`
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                }
            }
        });
    }

    initActivityChart() {
        const canvas = document.getElementById('activityChart');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        this.activityChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: [],
                datasets: [{
                    label: 'Entries',
                    data: [],
                    backgroundColor: 'rgba(165, 178, 161, 0.8)',
                    borderColor: 'var(--myavana-sage)',
                    borderWidth: 2,
                    borderRadius: 6,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(34, 35, 35, 0.95)',
                        titleColor: '#ffffff',
                        bodyColor: '#ffffff',
                        borderColor: 'var(--myavana-sage)',
                        borderWidth: 1,
                        cornerRadius: 8,
                        titleFont: {
                            family: 'Archivo Expanded',
                            size: 14,
                            weight: '600'
                        },
                        bodyFont: {
                            family: 'Archivo',
                            size: 13
                        },
                        callbacks: {
                            label: (context) => {
                                const count = context.parsed.y;
                                return `${count} ${count === 1 ? 'entry' : 'entries'}`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false,
                            drawBorder: false
                        },
                        ticks: {
                            color: 'var(--myavana-slate)',
                            font: {
                                family: 'Archivo',
                                size: 12
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(238, 236, 225, 0.5)',
                            drawBorder: false
                        },
                        ticks: {
                            color: 'var(--myavana-slate)',
                            font: {
                                family: 'Archivo',
                                size: 12
                            },
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }

    bindEvents() {
        // Period selector
        const periodSelect = document.getElementById('myavanaAnalyticsPeriod');
        if (periodSelect) {
            periodSelect.addEventListener('change', (e) => {
                this.currentPeriod = parseInt(e.target.value);
                this.loadAnalyticsData();
            });
        }

        // Export functionality
        const exportBtn = document.getElementById('exportAnalytics');
        if (exportBtn) {
            exportBtn.addEventListener('click', () => this.exportReport());
        }

        // Share functionality
        const shareBtn = document.getElementById('shareProgress');
        if (shareBtn) {
            shareBtn.addEventListener('click', () => this.shareProgress());
        }
    }

    loadInitialData() {
        this.loadAnalyticsData();
    }

    async loadAnalyticsData() {
        if (!myavanaAnalytics) return;

        try {
            this.showLoadingState();
            
            const formData = new FormData();
            formData.append('action', 'myavana_get_analytics_data');
            formData.append('nonce', myavanaAnalytics.nonce);
            formData.append('user_id', myavanaAnalytics.user_id);
            formData.append('period', this.currentPeriod);

            const response = await fetch(myavanaAnalytics.ajax_url, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            
            if (result.success) {
                this.updateCharts(result.data);
                this.hideLoadingState();
            } else {
                console.error('Analytics data load failed:', result.data?.message);
                this.showErrorState();
            }
        } catch (error) {
            console.error('Analytics data load error:', error);
            this.showErrorState();
        }
    }

    updateCharts(data) {
        // Update health trend chart
        if (this.healthChart && data.health_data) {
            this.healthChart.data.labels = data.labels;
            this.healthChart.data.datasets[0].data = data.health_data;
            this.healthChart.update('active');
        }

        // Update activity chart
        if (this.activityChart && data.activity_data) {
            this.activityChart.data.labels = data.labels;
            this.activityChart.data.datasets[0].data = data.activity_data;
            this.activityChart.update('active');
        }
    }

    showLoadingState() {
        const chartContainers = document.querySelectorAll('.myavana-chart-container');
        chartContainers.forEach(container => {
            container.style.opacity = '0.5';
            container.style.pointerEvents = 'none';
        });
    }

    hideLoadingState() {
        const chartContainers = document.querySelectorAll('.myavana-chart-container');
        chartContainers.forEach(container => {
            container.style.opacity = '1';
            container.style.pointerEvents = 'auto';
        });
    }

    showErrorState() {
        this.hideLoadingState();
        console.error('Failed to load analytics data');
    }

    exportReport() {
        // Create a comprehensive report
        const reportData = this.gatherReportData();
        
        // Generate and download CSV
        const csv = this.generateCSV(reportData);
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        
        if (link.download !== undefined) {
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', `myavana-hair-analytics-${new Date().toISOString().split('T')[0]}.csv`);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
        
        // Show success message
        this.showNotification('Analytics report exported successfully!', 'success');
    }

    gatherReportData() {
        const stats = {
            totalEntries: document.getElementById('totalEntries')?.textContent || '0',
            currentStreak: document.getElementById('currentStreak')?.textContent || '0',
            avgHealthScore: document.getElementById('avgHealthScore')?.textContent || '0',
            totalPhotos: document.getElementById('totalPhotos')?.textContent || '0',
            mostActiveDay: document.getElementById('mostActiveDay')?.textContent || 'N/A',
            favoriteMood: document.getElementById('favoriteMood')?.textContent || 'N/A',
            bestHealthMonth: document.getElementById('bestHealthMonth')?.textContent || 'N/A',
            progressScore: document.getElementById('progressScore')?.querySelector('.myavana-score-number')?.textContent || '0'
        };

        return {
            exportDate: new Date().toISOString(),
            period: `${this.currentPeriod} days`,
            ...stats
        };
    }

    generateCSV(data) {
        const headers = [
            'Metric',
            'Value',
            'Export Date',
            'Period'
        ];

        const rows = [
            ['Total Entries', data.totalEntries, data.exportDate, data.period],
            ['Current Streak (days)', data.currentStreak, data.exportDate, data.period],
            ['Average Health Score', data.avgHealthScore, data.exportDate, data.period],
            ['Total Photos', data.totalPhotos, data.exportDate, data.period],
            ['Most Active Day', data.mostActiveDay, data.exportDate, data.period],
            ['Favorite Mood', data.favoriteMood, data.exportDate, data.period],
            ['Best Health Month', data.bestHealthMonth, data.exportDate, data.period],
            ['Progress Score', data.progressScore, data.exportDate, data.period]
        ];

        const csvContent = [
            headers.join(','),
            ...rows.map(row => row.map(cell => `"${cell}"`).join(','))
        ].join('\n');

        return csvContent;
    }

    shareProgress() {
        const stats = {
            totalEntries: document.getElementById('totalEntries')?.textContent || '0',
            currentStreak: document.getElementById('currentStreak')?.textContent || '0',
            avgHealthScore: document.getElementById('avgHealthScore')?.textContent || '0',
            progressScore: document.getElementById('progressScore')?.querySelector('.myavana-score-number')?.textContent || '0'
        };

        const shareText = `🌟 My MYAVANA Hair Journey Progress:\n\n` +
            `📊 Total Entries: ${stats.totalEntries}\n` +
            `🔥 Current Streak: ${stats.currentStreak} days\n` +
            `💯 Average Health Score: ${stats.avgHealthScore}/10\n` +
            `🎯 Progress Score: ${stats.progressScore}/100\n\n` +
            `Track your hair journey with MYAVANA! 💫`;

        if (navigator.share) {
            navigator.share({
                title: 'My MYAVANA Hair Journey Progress',
                text: shareText,
                url: window.location.href
            }).catch(err => {
                console.log('Error sharing:', err);
                this.fallbackShare(shareText);
            });
        } else {
            this.fallbackShare(shareText);
        }
    }

    fallbackShare(text) {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(() => {
                this.showNotification('Progress copied to clipboard!', 'success');
            }).catch(() => {
                this.showShareModal(text);
            });
        } else {
            this.showShareModal(text);
        }
    }

    showShareModal(text) {
        // Create modal for sharing
        const modal = document.createElement('div');
        modal.className = 'myavana-share-modal';
        modal.innerHTML = `
            <div class="myavana-share-content">
                <h3>Share Your Progress</h3>
                <textarea readonly>${text}</textarea>
                <div class="myavana-share-actions">
                    <button class="myavana-btn-primary" onclick="this.parentElement.parentElement.parentElement.remove()">Close</button>
                    <button class="myavana-btn-outline" onclick="document.querySelector('.myavana-share-modal textarea').select(); document.execCommand('copy'); this.textContent='Copied!';">Copy</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // Auto-remove after 10 seconds
        setTimeout(() => {
            if (modal.parentNode) {
                modal.remove();
            }
        }, 10000);
    }

    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `myavana-notification myavana-notification-${type}`;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        // Animate in
        requestAnimationFrame(() => {
            notification.style.transform = 'translateY(0)';
            notification.style.opacity = '1';
        });
        
        // Auto-remove after 3 seconds
        setTimeout(() => {
            notification.style.transform = 'translateY(-100%)';
            notification.style.opacity = '0';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 300);
        }, 3000);
    }
}

// Notification styles
const notificationCSS = `
<style>
.myavana-share-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 10000;
}

.myavana-share-content {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    max-width: 500px;
    width: 90%;
}

.myavana-share-content h3 {
    margin: 0 0 1rem 0;
    font-family: 'Archivo Expanded', sans-serif;
}

.myavana-share-content textarea {
    width: 100%;
    height: 150px;
    border: 2px solid var(--myavana-cream);
    border-radius: 8px;
    padding: 1rem;
    font-family: 'Archivo', sans-serif;
    resize: vertical;
    margin-bottom: 1rem;
}

.myavana-share-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
}

.myavana-notification {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 1rem 1.5rem;
    background: var(--myavana-white);
    border: 2px solid var(--myavana-coral);
    border-radius: 8px;
    box-shadow: var(--myavana-shadow-lg);
    font-family: 'Archivo', sans-serif;
    font-weight: 600;
    color: var(--myavana-onyx);
    transform: translateY(-100%);
    opacity: 0;
    transition: all 0.3s ease;
    z-index: 10001;
}

.myavana-notification-success {
    border-color: var(--myavana-sage);
    color: var(--myavana-sage);
}

.myavana-notification-error {
    border-color: #e74c3c;
    color: #e74c3c;
}
</style>
`;

// Add notification styles to head
document.head.insertAdjacentHTML('beforeend', notificationCSS);

// Initialize analytics when the script loads
const myavanaAnalyticsInstance = new MyavanaAnalytics();