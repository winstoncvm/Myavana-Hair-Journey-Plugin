/**
 * Advanced Analytics Dashboard - Data Insights & Visualizations
 * AI-powered hair journey analytics with predictive insights
 *
 * @package Myavana_Hair_Journey
 * @version 1.0.0
 */

(function($) {
    'use strict';

    window.UPM_CM_Analytics = {
        charts: {},
        data: null,
        period: '30'
    };

    /**
     * Initialize Advanced Analytics
     */
    function initializeAdvancedAnalytics() {
        if (typeof Chart === 'undefined') {
            console.warn('[UPM_CM Analytics] Chart.js not loaded');
            return;
        }

        // Set Chart.js defaults
        Chart.defaults.font.family = 'Archivo, sans-serif';
        Chart.defaults.color = '#6C757D';

        loadAnalyticsData();
    }

    /**
     * Load analytics data from server
     */
    function loadAnalyticsData() {
        const period = $('#upm-cm-analytics-period').val() || '30';

        $.ajax({
            url: window.UPM_CM.ajaxUrl,
            type: 'POST',
            data: {
                action: 'upm_cm_get_advanced_analytics',
                nonce: window.UPM_CM.nonce,
                user_id: window.UPM_CM.userId,
                period: period
            },
            success: function(response) {
                if (response.success) {
                    UPM_CM_Analytics.data = response.data;
                    renderAnalyticsDashboard(response.data);
                } else {
                    showAnalyticsError();
                }
            },
            error: function() {
                showAnalyticsError();
            }
        });
    }

    /**
     * Render Complete Analytics Dashboard
     */
    function renderAnalyticsDashboard(data) {
        const $container = $('#upm-cm-analytics-content');
        $container.empty();

        // Create dashboard sections
        const html = `
            <div class="upm-cm-analytics-overview">
                <div class="upm-cm-analytics-cards">
                    ${renderKeyMetricsCards(data)}
                </div>
            </div>

            <div class="upm-cm-analytics-charts">
                <div class="upm-cm-chart-section">
                    <h3>Hair Health Trend</h3>
                    <canvas id="upm-cm-health-trend-chart"></canvas>
                </div>

                <div class="upm-cm-chart-section">
                    <h3>Product Effectiveness</h3>
                    <canvas id="upm-cm-product-effectiveness-chart"></canvas>
                </div>

                <div class="upm-cm-chart-section">
                    <h3>Mood & Hair Health Correlation</h3>
                    <canvas id="upm-cm-mood-correlation-chart"></canvas>
                </div>

                <div class="upm-cm-chart-section">
                    <h3>Monthly Progress</h3>
                    <canvas id="upm-cm-monthly-progress-chart"></canvas>
                </div>
            </div>

            <div class="upm-cm-analytics-insights">
                <h3>AI-Powered Insights</h3>
                <div id="upm-cm-ai-insights">
                    ${renderAIInsights(data)}
                </div>
            </div>

            <div class="upm-cm-analytics-predictions">
                <h3>Predictive Analysis</h3>
                <div id="upm-cm-predictions">
                    ${renderPredictions(data)}
                </div>
            </div>
        `;

        $container.html(html);

        // Initialize all charts
        initializeHealthTrendChart(data.healthTrend || []);
        initializeProductEffectivenessChart(data.productEffectiveness || []);
        initializeMoodCorrelationChart(data.moodCorrelation || []);
        initializeMonthlyProgressChart(data.monthlyProgress || []);
    }

    /**
     * Render Key Metrics Cards
     */
    function renderKeyMetricsCards(data) {
        const metrics = data.keyMetrics || {
            averageHealth: 0,
            totalEntries: 0,
            consistencyScore: 0,
            improvementRate: 0
        };

        return `
            <div class="upm-cm-metric-card">
                <div class="upm-cm-metric-icon">💚</div>
                <div class="upm-cm-metric-value">${metrics.averageHealth.toFixed(1)}/10</div>
                <div class="upm-cm-metric-label">Average Health Score</div>
                <div class="upm-cm-metric-trend ${metrics.healthTrend >= 0 ? 'up' : 'down'}">
                    ${metrics.healthTrend >= 0 ? '↑' : '↓'} ${Math.abs(metrics.healthTrend)}%
                </div>
            </div>

            <div class="upm-cm-metric-card">
                <div class="upm-cm-metric-icon">📝</div>
                <div class="upm-cm-metric-value">${metrics.totalEntries}</div>
                <div class="upm-cm-metric-label">Total Entries</div>
                <div class="upm-cm-metric-info">Last ${UPM_CM_Analytics.period} days</div>
            </div>

            <div class="upm-cm-metric-card">
                <div class="upm-cm-metric-icon">🎯</div>
                <div class="upm-cm-metric-value">${metrics.consistencyScore}%</div>
                <div class="upm-cm-metric-label">Consistency Score</div>
                <div class="upm-cm-metric-info">Great job!</div>
            </div>

            <div class="upm-cm-metric-card">
                <div class="upm-cm-metric-icon">📈</div>
                <div class="upm-cm-metric-value">${metrics.improvementRate >= 0 ? '+' : ''}${metrics.improvementRate}%</div>
                <div class="upm-cm-metric-label">Improvement Rate</div>
                <div class="upm-cm-metric-info">vs. last period</div>
            </div>
        `;
    }

    /**
     * Initialize Health Trend Chart
     */
    function initializeHealthTrendChart(data) {
        const ctx = document.getElementById('upm-cm-health-trend-chart');
        if (!ctx) return;

        const labels = data.map(d => d.date);
        const values = data.map(d => d.healthScore);

        UPM_CM_Analytics.charts.healthTrend = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Health Score',
                    data: values,
                    borderColor: '#FF6B6B',
                    backgroundColor: 'rgba(255, 107, 107, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        titleFont: {
                            size: 14
                        },
                        bodyFont: {
                            size: 13
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 10,
                        ticks: {
                            stepSize: 2
                        }
                    }
                }
            }
        });
    }

    /**
     * Initialize Product Effectiveness Chart
     */
    function initializeProductEffectivenessChart(data) {
        const ctx = document.getElementById('upm-cm-product-effectiveness-chart');
        if (!ctx) return;

        const labels = data.map(d => d.productName);
        const values = data.map(d => d.effectiveness);

        UPM_CM_Analytics.charts.productEffectiveness = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Effectiveness Score',
                    data: values,
                    backgroundColor: '#4ECDC4',
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                indexAxis: 'y',
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        max: 10
                    }
                }
            }
        });
    }

    /**
     * Initialize Mood-Hair Correlation Chart
     */
    function initializeMoodCorrelationChart(data) {
        const ctx = document.getElementById('upm-cm-mood-correlation-chart');
        if (!ctx) return;

        const labels = data.map(d => d.mood);
        const healthScores = data.map(d => d.avgHealth);

        UPM_CM_Analytics.charts.moodCorrelation = new Chart(ctx, {
            type: 'radar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Avg Health Score',
                    data: healthScores,
                    backgroundColor: 'rgba(255, 107, 107, 0.2)',
                    borderColor: '#FF6B6B',
                    pointBackgroundColor: '#FF6B6B',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: '#FF6B6B'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    r: {
                        beginAtZero: true,
                        max: 10
                    }
                }
            }
        });
    }

    /**
     * Initialize Monthly Progress Chart
     */
    function initializeMonthlyProgressChart(data) {
        const ctx = document.getElementById('upm-cm-monthly-progress-chart');
        if (!ctx) return;

        const labels = data.map(d => d.month);
        const entries = data.map(d => d.entries);
        const avgHealth = data.map(d => d.avgHealth);

        UPM_CM_Analytics.charts.monthlyProgress = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Entries',
                        data: entries,
                        borderColor: '#4ECDC4',
                        backgroundColor: 'rgba(78, 205, 196, 0.1)',
                        fill: true,
                        yAxisID: 'y',
                        tension: 0.4
                    },
                    {
                        label: 'Avg Health',
                        data: avgHealth,
                        borderColor: '#FF6B6B',
                        backgroundColor: 'rgba(255, 107, 107, 0.1)',
                        fill: true,
                        yAxisID: 'y1',
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left'
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        max: 10,
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                }
            }
        });
    }

    /**
     * Render AI-Powered Insights
     */
    function renderAIInsights(data) {
        const insights = data.aiInsights || [];

        if (insights.length === 0) {
            return '<p>Collecting data for personalized insights...</p>';
        }

        let html = '<div class="upm-cm-insights-list">';

        insights.forEach(insight => {
            html += `
                <div class="upm-cm-insight-card ${insight.type}">
                    <div class="upm-cm-insight-icon">${getInsightIcon(insight.type)}</div>
                    <div class="upm-cm-insight-content">
                        <h4>${insight.title}</h4>
                        <p>${insight.message}</p>
                        ${insight.recommendation ? `<div class="upm-cm-insight-action">${insight.recommendation}</div>` : ''}
                    </div>
                </div>
            `;
        });

        html += '</div>';
        return html;
    }

    /**
     * Render Predictive Analysis
     */
    function renderPredictions(data) {
        const predictions = data.predictions || {};

        return `
            <div class="upm-cm-predictions-grid">
                <div class="upm-cm-prediction-card">
                    <h4>30-Day Health Forecast</h4>
                    <div class="upm-cm-prediction-value">${predictions.healthForecast || 'N/A'}</div>
                    <p>Based on current routine and consistency</p>
                </div>

                <div class="upm-cm-prediction-card">
                    <h4>Goal Achievement</h4>
                    <div class="upm-cm-prediction-value">${predictions.goalAchievement || 'N/A'}</div>
                    <p>Estimated time to reach your goal</p>
                </div>

                <div class="upm-cm-prediction-card">
                    <h4>Seasonal Recommendation</h4>
                    <div class="upm-cm-prediction-value">${predictions.seasonalCare || 'N/A'}</div>
                    <p>Suggested adjustments for current season</p>
                </div>
            </div>
        `;
    }

    /**
     * Get Icon for Insight Type
     */
    function getInsightIcon(type) {
        const icons = {
            success: '✨',
            warning: '⚠️',
            info: 'ℹ️',
            tip: '💡'
        };
        return icons[type] || '📊';
    }

    /**
     * Show Analytics Error
     */
    function showAnalyticsError() {
        $('#upm-cm-analytics-content').html(`
            <div class="upm-cm-error-state">
                <div class="upm-cm-error-icon">⚠️</div>
                <h3>Unable to Load Analytics</h3>
                <p>There was an error loading your analytics data.</p>
                <button class="upm-cm-btn upm-cm-btn-primary" onclick="location.reload()">Retry</button>
            </div>
        `);
    }

    // Export for global access
    window.UPM_CM_Analytics.init = initializeAdvancedAnalytics;
    window.UPM_CM_Analytics.reload = loadAnalyticsData;

    // Auto-initialize when document is ready
    $(document).ready(function() {
        if ($('#upm-cm-analytics-content').length > 0) {
            initializeAdvancedAnalytics();
        }
    });

    console.log('[UPM_CM Analytics] Advanced Analytics module loaded');

})(jQuery);
