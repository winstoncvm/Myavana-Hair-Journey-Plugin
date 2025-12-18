/**
 * MYAVANA Gamification System - Frontend
 * Handles daily check-ins, streak tracking, badges, and visual rewards
 * @version 2.3.5
 */

(function() {
    'use strict';

    // Create namespace
    window.MyavanaGamification = {

        settings: window.myavanaGamificationSettings || {},

        /**
         * Initialize gamification system
         */
        init: function() {
            console.log('[Gamification] Initializing...');

            this.loadStats();
            this.checkDailyCheckIn();
            this.bindEvents();
        },

        /**
         * Load user gamification stats
         */
        loadStats: async function() {
            try {
                const response = await fetch(this.settings.ajaxUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'myavana_get_gamification_stats',
                        security: this.settings.nonce
                    })
                });

                const data = await response.json();

                if (data.success) {
                    this.displayStats(data.data);
                }
            } catch (error) {
                console.error('[Gamification] Error loading stats:', error);
            }
        },

        /**
         * Display stats in sidebar
         */
        displayStats: function(stats) {
            console.log('[Gamification] Stats loaded:', stats);

            // Update stats widget if exists
            const statsWidget = document.getElementById('myavana-gamification-stats');
            if (statsWidget) {
                statsWidget.innerHTML = `
                    <div class="gamification-stat">
                        <div class="stat-icon">⭐</div>
                        <div class="stat-info">
                            <div class="stat-value">${stats.total_points}</div>
                            <div class="stat-label">Points</div>
                        </div>
                    </div>
                    <div class="gamification-stat">
                        <div class="stat-icon">🔥</div>
                        <div class="stat-info">
                            <div class="stat-value">${stats.current_streak}</div>
                            <div class="stat-label">Day Streak</div>
                        </div>
                    </div>
                    <div class="gamification-stat">
                        <div class="stat-icon">🏆</div>
                        <div class="stat-info">
                            <div class="stat-value">${stats.badges_earned}</div>
                            <div class="stat-label">Badges</div>
                        </div>
                    </div>
                    <div class="gamification-stat">
                        <div class="stat-icon">📈</div>
                        <div class="stat-info">
                            <div class="stat-value">Level ${stats.level}</div>
                            <div class="stat-label">Progress</div>
                        </div>
                    </div>
                `;

                // Animate count up
                this.animateStats(statsWidget, stats);
            }

            // Store stats for later use
            this.currentStats = stats;
        },

        /**
         * Animate stats with count-up effect
         */
        animateStats: function(container, stats) {
            const statValues = container.querySelectorAll('.stat-value');
            statValues.forEach((el, index) => {
                const text = el.textContent;
                const match = text.match(/\d+/);
                if (match) {
                    const target = parseInt(match[0]);
                    this.countUpAnimation(el, 0, target, 800);
                }
            });
        },

        /**
         * Count up animation
         */
        countUpAnimation: function(element, start, end, duration) {
            const range = end - start;
            const increment = range / (duration / 16);
            let current = start;

            const timer = setInterval(() => {
                current += increment;
                if (current >= end) {
                    element.textContent = element.textContent.replace(/\d+/, end);
                    clearInterval(timer);
                } else {
                    element.textContent = element.textContent.replace(/\d+/, Math.floor(current));
                }
            }, 16);
        },

        /**
         * Check if user needs to check in today
         */
        checkDailyCheckIn: function() {
            const today = new Date().toDateString();

            // Check if already checked in today (in localStorage)
            const lastCheckInDate = localStorage.getItem('myavana_last_checkin_date');
            if (lastCheckInDate === today) {
                console.log('[Gamification] Already checked in today (localStorage)');
                return;
            }

            // Check if user dismissed the prompt today
            const dismissedDate = localStorage.getItem('myavana_checkin_dismissed');
            if (dismissedDate === today) {
                console.log('[Gamification] Check-in prompt already dismissed today');
                return;
            }

            // Wait for stats to load
            setTimeout(() => {
                if (this.currentStats && !this.currentStats.checked_in_today) {
                    this.showCheckInPrompt();
                }
            }, 2000);
        },

        /**
         * Show daily check-in prompt
         */
        showCheckInPrompt: function() {
            const promptHtml = `
                <div class="myavana-checkin-prompt" id="myavanaCheckInPrompt">
                    <div class="checkin-prompt-inner">
                        <button class="checkin-close" onclick="MyavanaGamification.closeCheckInPrompt()">×</button>
                        <h3>How's your hair today?</h3>
                        <p>Take a moment to check in and track your progress!</p>
                        <div class="checkin-mood-options">
                            <button class="mood-btn" data-mood="Amazing">
                                <span class="mood-emoji">🌟</span>
                                <span class="mood-text">Amazing</span>
                            </button>
                            <button class="mood-btn" data-mood="Good">
                                <span class="mood-emoji">✨</span>
                                <span class="mood-text">Good</span>
                            </button>
                            <button class="mood-btn" data-mood="Okay">
                                <span class="mood-emoji">👌</span>
                                <span class="mood-text">Okay</span>
                            </button>
                            <button class="mood-btn" data-mood="Needs TLC">
                                <span class="mood-emoji">💆‍♀️</span>
                                <span class="mood-text">Needs TLC</span>
                            </button>
                        </div>
                        <div class="checkin-streak-info">
                            Current streak: <strong>${this.currentStats.current_streak} days 🔥</strong>
                        </div>
                    </div>
                </div>
            `;

            const promptEl = document.createElement('div');
            promptEl.innerHTML = promptHtml;
            document.body.appendChild(promptEl.firstElementChild);

            // Bind mood button clicks
            document.querySelectorAll('.mood-btn').forEach(btn => {
                btn.onclick = () => this.submitCheckIn(btn.dataset.mood);
            });

            // Animate in
            setTimeout(() => {
                document.getElementById('myavanaCheckInPrompt').classList.add('visible');
            }, 100);
        },

        /**
         * Close check-in prompt (and remember dismissal for today)
         */
        closeCheckInPrompt: function(saveDismissal = true) {
            const prompt = document.getElementById('myavanaCheckInPrompt');
            if (prompt) {
                prompt.classList.remove('visible');
                setTimeout(() => prompt.remove(), 300);
            }

            // Save dismissal to localStorage so it doesn't show again today
            if (saveDismissal) {
                const today = new Date().toDateString();
                localStorage.setItem('myavana_checkin_dismissed', today);
                console.log('[Gamification] Check-in dismissed for today');
            }
        },

        /**
         * Submit daily check-in
         */
        submitCheckIn: async function(mood) {
            console.log('[Gamification] Submitting check-in with mood:', mood);
            console.log('[Gamification] Settings:', this.settings);
            console.log('[Gamification] Ajax URL:', this.settings.ajaxUrl);
            console.log('[Gamification] Nonce:', this.settings.nonce);

            // Disable mood buttons to prevent double clicks
            document.querySelectorAll('.mood-btn').forEach(btn => {
                btn.disabled = true;
                btn.style.opacity = '0.6';
            });

            try {
                const requestBody = new URLSearchParams({
                    action: 'myavana_daily_checkin',
                    security: this.settings.nonce,
                    mood: mood
                });

                console.log('[Gamification] Request body:', requestBody.toString());

                const response = await fetch(this.settings.ajaxUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: requestBody
                });

                console.log('[Gamification] Response status:', response.status);
                console.log('[Gamification] Response OK:', response.ok);

                const data = await response.json();
                console.log('[Gamification] Response data:', data);

                // Always close and mark as done for the day (even if server fails)
                const today = new Date().toDateString();
                localStorage.setItem('myavana_last_checkin_date', today);
                this.closeCheckInPrompt(false);

                if (data.success) {
                    this.showCheckInSuccess(data.data);
                    this.loadStats(); // Refresh stats
                } else {
                    // Server failed but we still close - show simple thank you
                    console.log('[Gamification] Server check-in failed, but closing anyway:', data);
                    this.showNotification('Thanks for checking in! 🌟', 'success');
                }
            } catch (error) {
                // Network error - still close and thank user
                console.log('[Gamification] Network error, closing anyway:', error);
                const today = new Date().toDateString();
                localStorage.setItem('myavana_last_checkin_date', today);
                this.closeCheckInPrompt(false);
                this.showNotification('Thanks for checking in! 🌟', 'success');
            }
        },

        /**
         * Show check-in success message
         */
        showCheckInSuccess: function(result) {
            let message = `+${result.points_earned} points! `;
            if (result.streak > 1) {
                message += `🔥 ${result.streak} day streak!`;
            }
            if (result.milestone) {
                message += ` 🎉 ${result.milestone}!`;
            }

            // Show success notification
            this.showNotification(message, 'success');

            // Show badge unlocks if any
            if (result.new_badges && result.new_badges.length > 0) {
                setTimeout(() => {
                    result.new_badges.forEach((badge, index) => {
                        setTimeout(() => {
                            this.showBadgeUnlock(badge);
                        }, index * 2000);
                    });
                }, 1000);
            }
        },

        /**
         * Show badge unlock animation
         */
        showBadgeUnlock: function(badge) {
            const badgeHtml = `
                <div class="myavana-badge-unlock" id="myavanaBadgeUnlock">
                    <div class="badge-unlock-inner">
                        <div class="badge-glow"></div>
                        <div class="badge-icon ${badge.rarity}">🏆</div>
                        <h2>Badge Unlocked!</h2>
                        <h3>${badge.name}</h3>
                        <p>${badge.description}</p>
                        <div class="badge-reward">+${badge.points_reward} points</div>
                        <button class="badge-close-btn" onclick="document.getElementById('myavanaBadgeUnlock').remove()">
                            Awesome!
                        </button>
                    </div>
                </div>
            `;

            const badgeEl = document.createElement('div');
            badgeEl.innerHTML = badgeHtml;
            document.body.appendChild(badgeEl.firstElementChild);

            // Auto-close after 5 seconds
            setTimeout(() => {
                const el = document.getElementById('myavanaBadgeUnlock');
                if (el) el.remove();
            }, 5000);
        },

        /**
         * Show notification
         */
        showNotification: function(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `myavana-notification ${type}`;
            notification.textContent = message;
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${type === 'success' ? '#4caf50' : '#2196f3'};
                color: white;
                padding: 16px 24px;
                border-radius: 8px;
                font-family: 'Archivo', sans-serif;
                font-weight: 600;
                z-index: 100000;
                box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                animation: slideInRight 0.3s ease;
            `;

            document.body.appendChild(notification);

            setTimeout(() => {
                notification.style.animation = 'slideInRight 0.3s ease reverse';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        },

        /**
         * Bind events
         */
        bindEvents: function() {
            // Manual check-in button (if exists)
            const checkInBtn = document.getElementById('myavana-checkin-btn');
            if (checkInBtn) {
                checkInBtn.onclick = () => this.showCheckInPrompt();
            }
        }
    };

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', () => {
        if (window.myavanaGamificationSettings) {
            MyavanaGamification.init();
        }
    });

    console.log('[Gamification] Script loaded');

})();
