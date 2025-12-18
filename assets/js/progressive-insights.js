/**
 * MYAVANA Progressive Insight Unlocking
 * Gate analytics behind engagement milestones
 * @version 2.3.5
 */

(function($) {
    'use strict';

    window.MyavanaProgressiveInsights = {
        userInsights: {},
        allInsights: [],

        init: function() {
            console.log('[Progressive Insights] Initializing...');
            this.loadUserInsights();
            this.bindEvents();
        },

        bindEvents: function() {
            $(document).on('click', '.insight-unlock-trigger', this.showUnlockModal.bind(this));
            $(document).on('myavana:entry:created', this.checkNewUnlocks.bind(this));
            $(document).on('myavana:checkin:completed', this.checkNewUnlocks.bind(this));
        },

        loadUserInsights: async function() {
            try {
                const response = await $.ajax({
                    url: myavanaGamificationSettings.ajaxUrl,
                    method: 'POST',
                    data: {
                        action: 'myavana_get_user_insights',
                        nonce: myavanaGamificationSettings.nonce
                    }
                });

                if (response.success) {
                    this.userInsights = response.data.user_insights || {};
                    this.allInsights = response.data.all_insights || [];
                    console.log(`[Progressive Insights] Loaded: ${Object.keys(this.userInsights).length} unlocked, ${this.allInsights.length} total`);
                    this.renderInsightWidgets();
                }
            } catch (error) {
                console.error('[Progressive Insights] Failed to load insights:', error);
            }
        },

        renderInsightWidgets: function() {
            // Render locked/unlocked insights in analytics and dashboard
            const containers = $('.myavana-insights-container');

            containers.each((index, container) => {
                const $container = $(container);
                const category = $container.data('category') || 'all';

                let insightsHTML = '';

                this.allInsights.forEach(insight => {
                    if (category !== 'all' && insight.category !== category) return;

                    const isUnlocked = this.userInsights[insight.insight_key];
                    const iconMap = {
                        'analytics': '📊',
                        'recommendations': '💡',
                        'trends': '📈',
                        'milestones': '🏆'
                    };

                    if (isUnlocked) {
                        insightsHTML += `
                            <div class="insight-card unlocked" data-insight-key="${insight.insight_key}">
                                <div class="insight-header">
                                    <span class="insight-icon">${iconMap[insight.category] || '✨'}</span>
                                    <h4 class="insight-title">${insight.title}</h4>
                                    <span class="insight-status unlocked-badge">Unlocked</span>
                                </div>
                                <div class="insight-content">
                                    ${this.renderInsightContent(insight)}
                                </div>
                            </div>
                        `;
                    } else {
                        const progress = this.calculateProgress(insight);
                        insightsHTML += `
                            <div class="insight-card locked" data-insight-key="${insight.insight_key}">
                                <div class="insight-header">
                                    <span class="insight-icon locked-icon">🔒</span>
                                    <h4 class="insight-title">${insight.title}</h4>
                                </div>
                                <div class="insight-locked-content">
                                    <p class="unlock-description">${insight.description}</p>
                                    <div class="unlock-requirement">
                                        <div class="requirement-text">${insight.unlock_requirement}</div>
                                        ${progress.html}
                                    </div>
                                    <button class="insight-unlock-trigger btn-primary-outline" data-insight-key="${insight.insight_key}">
                                        Learn More
                                    </button>
                                </div>
                            </div>
                        `;
                    }
                });

                if (insightsHTML) {
                    $container.html(insightsHTML);
                } else {
                    $container.html('<p class="no-insights">No insights available yet. Keep logging your hair journey!</p>');
                }
            });
        },

        renderInsightContent: function(insight) {
            // This would dynamically load the actual insight data
            // For now, return placeholder
            return `
                <div class="insight-data-preview">
                    <p>📊 <strong>Insight unlocked!</strong> View your ${insight.title.toLowerCase()} in the analytics dashboard.</p>
                    <button class="btn-view-insight" data-insight-key="${insight.insight_key}">View Full Insight</button>
                </div>
            `;
        },

        calculateProgress: function(insight) {
            // Parse unlock requirement and calculate user progress
            const requirement = insight.unlock_requirement.toLowerCase();

            // Example requirements:
            // "Log 3 entries" -> needs 3 entries
            // "Maintain a 7-day streak" -> needs 7-day streak
            // "Complete 10 AI analyses" -> needs 10 AI analyses
            // "Reach level 5" -> needs level 5

            let progress = { current: 0, required: 1, percentage: 0, html: '' };

            if (requirement.includes('entries')) {
                const match = requirement.match(/(\d+)\s*entries/);
                if (match) {
                    const required = parseInt(match[1]);
                    // Would fetch actual user entry count from stats
                    const current = window.MyavanaGamification?.currentStats?.total_entries || 0;
                    progress = {
                        current: Math.min(current, required),
                        required: required,
                        percentage: Math.min(100, (current / required) * 100),
                        html: this.renderProgressBar(current, required, 'entries logged')
                    };
                }
            } else if (requirement.includes('streak')) {
                const match = requirement.match(/(\d+)-day streak/);
                if (match) {
                    const required = parseInt(match[1]);
                    const current = window.MyavanaGamification?.currentStats?.current_streak || 0;
                    progress = {
                        current: Math.min(current, required),
                        required: required,
                        percentage: Math.min(100, (current / required) * 100),
                        html: this.renderProgressBar(current, required, 'day streak')
                    };
                }
            } else if (requirement.includes('ai analyses')) {
                const match = requirement.match(/(\d+)\s*ai analyses/i);
                if (match) {
                    const required = parseInt(match[1]);
                    const current = window.MyavanaGamification?.currentStats?.ai_analyses || 0;
                    progress = {
                        current: Math.min(current, required),
                        required: required,
                        percentage: Math.min(100, (current / required) * 100),
                        html: this.renderProgressBar(current, required, 'AI analyses')
                    };
                }
            } else if (requirement.includes('level')) {
                const match = requirement.match(/level\s*(\d+)/i);
                if (match) {
                    const required = parseInt(match[1]);
                    const current = window.MyavanaGamification?.currentStats?.current_level || 1;
                    progress = {
                        current: Math.min(current, required),
                        required: required,
                        percentage: Math.min(100, (current / required) * 100),
                        html: this.renderProgressBar(current, required, 'level')
                    };
                }
            }

            return progress;
        },

        renderProgressBar: function(current, required, label) {
            const percentage = Math.min(100, (current / required) * 100);
            const isComplete = current >= required;

            return `
                <div class="unlock-progress">
                    <div class="progress-text">
                        <span>${current} / ${required} ${label}</span>
                        ${isComplete ? '<span class="progress-complete">✓ Ready to unlock!</span>' : ''}
                    </div>
                    <div class="progress-bar">
                        <div class="progress-bar-fill ${isComplete ? 'complete' : ''}" style="width: ${percentage}%"></div>
                    </div>
                </div>
            `;
        },

        checkNewUnlocks: async function() {
            console.log('[Progressive Insights] Checking for new unlocks...');

            try {
                const response = await $.ajax({
                    url: myavanaGamificationSettings.ajaxUrl,
                    method: 'POST',
                    data: {
                        action: 'myavana_check_insight_unlocks',
                        nonce: myavanaGamificationSettings.nonce
                    }
                });

                if (response.success && response.data.new_unlocks && response.data.new_unlocks.length > 0) {
                    console.log(`[Progressive Insights] ${response.data.new_unlocks.length} new insights unlocked!`);

                    // Show celebration for each new unlock
                    response.data.new_unlocks.forEach((insight, index) => {
                        setTimeout(() => {
                            this.showUnlockCelebration(insight);
                        }, index * 500); // Stagger celebrations
                    });

                    // Reload insights after celebrations
                    setTimeout(() => {
                        this.loadUserInsights();
                    }, response.data.new_unlocks.length * 500 + 2000);
                }
            } catch (error) {
                console.error('[Progressive Insights] Failed to check unlocks:', error);
            }
        },

        showUnlockCelebration: function(insight) {
            const celebrationHTML = `
                <div class="insight-unlock-celebration" id="insightUnlock-${insight.insight_key}">
                    <div class="unlock-celebration-inner">
                        <div class="unlock-glow"></div>
                        <div class="unlock-icon">🎉</div>
                        <h2 class="unlock-congrats">Insight Unlocked!</h2>
                        <h3 class="unlock-title">${insight.title}</h3>
                        <p class="unlock-description">${insight.description}</p>
                        <div class="unlock-reward">
                            <span>+${insight.points_reward} points</span>
                        </div>
                        <button class="btn-close-unlock" onclick="MyavanaProgressiveInsights.closeCelebration('${insight.insight_key}')">
                            View Insight
                        </button>
                    </div>
                </div>
            `;

            $('body').append(celebrationHTML);
            setTimeout(() => {
                $(`#insightUnlock-${insight.insight_key}`).addClass('visible');
            }, 100);

            // Auto-close after 5 seconds
            setTimeout(() => {
                this.closeCelebration(insight.insight_key);
            }, 5000);
        },

        closeCelebration: function(insightKey) {
            const $celebration = $(`#insightUnlock-${insightKey}`);
            $celebration.removeClass('visible');
            setTimeout(() => {
                $celebration.remove();
            }, 300);
        },

        showUnlockModal: function(e) {
            e.preventDefault();
            const insightKey = $(e.currentTarget).data('insight-key');
            const insight = this.allInsights.find(i => i.insight_key === insightKey);

            if (!insight) return;

            const progress = this.calculateProgress(insight);

            const modalHTML = `
                <div class="insight-unlock-modal" id="insightModal-${insightKey}">
                    <div class="insight-modal-content">
                        <div class="insight-modal-header">
                            <h2>${insight.title}</h2>
                            <button class="modal-close" onclick="MyavanaProgressiveInsights.closeModal('${insightKey}')">&times;</button>
                        </div>
                        <div class="insight-modal-body">
                            <div class="modal-lock-icon">🔒</div>
                            <p class="modal-description">${insight.description}</p>
                            <div class="modal-requirement">
                                <h4>Unlock Requirement:</h4>
                                <p>${insight.unlock_requirement}</p>
                                ${progress.html}
                            </div>
                            <div class="modal-benefits">
                                <h4>What You'll Get:</h4>
                                <ul>
                                    <li>📊 Detailed ${insight.title.toLowerCase()} dashboard</li>
                                    <li>💡 Personalized recommendations</li>
                                    <li>🏆 +${insight.points_reward} reward points</li>
                                </ul>
                            </div>
                        </div>
                        <div class="insight-modal-footer">
                            <button class="btn-primary" onclick="MyavanaProgressiveInsights.closeModal('${insightKey}')">
                                Got It!
                            </button>
                        </div>
                    </div>
                </div>
            `;

            $('body').append(modalHTML);
            setTimeout(() => {
                $(`#insightModal-${insightKey}`).addClass('visible');
            }, 10);
        },

        closeModal: function(insightKey) {
            const $modal = $(`#insightModal-${insightKey}`);
            $modal.removeClass('visible');
            setTimeout(() => {
                $modal.remove();
            }, 300);
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        MyavanaProgressiveInsights.init();
    });

})(jQuery);
