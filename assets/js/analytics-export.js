// ===============================================
// ANALYTICS EXPORT & SHARE FUNCTIONS
// ===============================================

/**
 * Export analytics report as PDF or JSON
 */
function exportAnalyticsReport() {
    const analyticsData = gatherAnalyticsData();

    // Create downloadable JSON file
    const dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(analyticsData, null, 2));
    const downloadAnchorNode = document.createElement('a');
    downloadAnchorNode.setAttribute("href", dataStr);
    downloadAnchorNode.setAttribute("download", `myavana-hair-journey-report-${getCurrentDate()}.json`);
    document.body.appendChild(downloadAnchorNode);
    downloadAnchorNode.click();
    downloadAnchorNode.remove();

    // Show success notification
    showAnalyticsNotification('Report exported successfully!', 'success');
    console.log('Analytics report exported');
}

/**
 * Share progress on social media or copy to clipboard
 */
function shareProgress() {
    const analyticsData = gatherAnalyticsData();

    // Generate shareable text
    const shareText = generateShareText(analyticsData);

    // Check if Web Share API is available (mobile devices)
    if (navigator.share) {
        navigator.share({
            title: 'My Hair Journey Progress',
            text: shareText,
            url: window.location.href
        })
        .then(() => {
            console.log('Successfully shared');
            showAnalyticsNotification('Progress shared!', 'success');
        })
        .catch((error) => {
            console.log('Error sharing:', error);
            // Fallback to copy to clipboard
            copyToClipboard(shareText);
        });
    } else {
        // Fallback: Show modal with share options
        showShareModal(shareText);
    }
}

/**
 * Gather all analytics data from the page
 */
function gatherAnalyticsData() {
    const data = {
        exportDate: getCurrentDate(),
        userName: getUserName(),
        stats: {
            totalEntries: document.getElementById('totalEntries')?.textContent || '0',
            currentStreak: document.getElementById('currentStreak')?.textContent || '0',
            avgHealthScore: document.getElementById('avgHealthScore')?.textContent || '0',
            totalPhotos: document.getElementById('totalPhotos')?.textContent || '0'
        },
        insights: {
            mostActiveDay: document.getElementById('mostActiveDay')?.textContent || 'N/A',
            favoriteMood: document.getElementById('favoriteMood')?.textContent || 'N/A',
            bestHealthMonth: document.getElementById('bestHealthMonth')?.textContent || 'N/A',
            progressScore: document.getElementById('progressScore')?.querySelector('.myavana-score-number')?.textContent || '0'
        },
        period: document.getElementById('myavanaAnalyticsPeriod')?.value || '30'
    };

    return data;
}

/**
 * Generate shareable text from analytics data
 */
function generateShareText(data) {
    const emoji = data.stats.currentStreak >= 7 ? '🔥' : (data.stats.currentStreak >= 3 ? '💪' : '✨');

    return `${emoji} My Hair Journey Progress with Myavana!\n\n` +
           `📊 ${data.stats.totalEntries} entries logged\n` +
           `🔥 ${data.stats.currentStreak} day streak\n` +
           `💎 ${data.stats.avgHealthScore}/10 average health score\n` +
           `📸 ${data.stats.totalPhotos} progress photos\n` +
           `🎯 Progress Score: ${data.insights.progressScore}/100\n\n` +
           `Track your hair journey with Myavana! #HairJourney #Myavana`;
}

/**
 * Show share modal with copy to clipboard option
 */
function showShareModal(shareText) {
    // Create modal HTML
    const modalHTML = `
        <div id="shareProgressModal" class="modal active">
            <div class="modal-content" style="max-width: 500px;">
                <div class="modal-header">
                    <h2 style="font-family: 'Archivo Black', sans-serif; color: var(--myavana-onyx); margin: 0;">
                        Share Your Progress
                    </h2>
                    <span class="modal-close" onclick="closeShareModal()">&times;</span>
                </div>
                <div class="modal-body">
                    <p style="color: var(--myavana-blueberry); margin-bottom: 1rem;">
                        Share your hair journey progress with friends and family!
                    </p>
                    <textarea id="shareTextArea" readonly style="width: 100%; min-height: 200px; padding: 1rem; border: 1px solid var(--border-color); border-radius: 8px; font-family: 'Archivo', sans-serif; font-size: 0.875rem; resize: vertical; margin-bottom: 1rem;">${shareText}</textarea>
                    <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                        <button onclick="copyShareText()" style="flex: 1; padding: 0.75rem 1.5rem; background: var(--myavana-coral); color: var(--myavana-white); border: none; border-radius: 8px; font-family: 'Archivo', sans-serif; font-weight: 600; cursor: pointer;">
                            📋 Copy to Clipboard
                        </button>
                        <button onclick="shareToTwitter()" style="flex: 1; padding: 0.75rem 1.5rem; background: #1DA1F2; color: white; border: none; border-radius: 8px; font-family: 'Archivo', sans-serif; font-weight: 600; cursor: pointer;">
                            🐦 Share on Twitter
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Remove existing modal if present
    const existingModal = document.getElementById('shareProgressModal');
    if (existingModal) {
        existingModal.remove();
    }

    // Append modal to body
    document.body.insertAdjacentHTML('beforeend', modalHTML);
}

/**
 * Close share modal
 */
function closeShareModal() {
    const modal = document.getElementById('shareProgressModal');
    if (modal) {
        modal.remove();
    }
}

/**
 * Copy share text to clipboard
 */
function copyShareText() {
    const textarea = document.getElementById('shareTextArea');
    if (!textarea) return;

    copyToClipboard(textarea.value);
}

/**
 * Copy text to clipboard (universal function)
 */
function copyToClipboard(text) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text)
            .then(() => {
                showAnalyticsNotification('Copied to clipboard!', 'success');
            })
            .catch(err => {
                console.error('Failed to copy:', err);
                fallbackCopyToClipboard(text);
            });
    } else {
        fallbackCopyToClipboard(text);
    }
}

/**
 * Fallback copy to clipboard for older browsers
 */
function fallbackCopyToClipboard(text) {
    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.focus();
    textarea.select();

    try {
        const successful = document.execCommand('copy');
        if (successful) {
            showAnalyticsNotification('Copied to clipboard!', 'success');
        } else {
            showAnalyticsNotification('Failed to copy. Please copy manually.', 'error');
        }
    } catch (err) {
        console.error('Fallback copy failed:', err);
        showAnalyticsNotification('Failed to copy. Please copy manually.', 'error');
    }

    document.body.removeChild(textarea);
}

/**
 * Share to Twitter
 */
function shareToTwitter() {
    const textarea = document.getElementById('shareTextArea');
    if (!textarea) return;

    const text = encodeURIComponent(textarea.value);
    const url = `https://twitter.com/intent/tweet?text=${text}`;
    window.open(url, '_blank', 'width=550,height=420');

    closeShareModal();
}

/**
 * Show notification
 */
function showAnalyticsNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = 'myavana-notification';
    notification.style.cssText = `
        position: fixed;
        top: 2rem;
        right: 2rem;
        background: ${type === 'success' ? 'var(--myavana-coral)' : (type === 'error' ? '#e74c3c' : 'var(--myavana-blueberry)')};
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        font-family: 'Archivo', sans-serif;
        font-weight: 600;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        z-index: 10000;
        animation: slideInRight 0.3s ease;
    `;
    notification.textContent = message;

    // Add animation keyframes if not already present
    if (!document.getElementById('notificationStyles')) {
        const style = document.createElement('style');
        style.id = 'notificationStyles';
        style.textContent = `
            @keyframes slideInRight {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOutRight {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
        `;
        document.head.appendChild(style);
    }

    document.body.appendChild(notification);

    // Auto-remove after 3 seconds
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}

/**
 * Helper: Get current date in YYYY-MM-DD format
 */
function getCurrentDate() {
    const now = new Date();
    return now.toISOString().split('T')[0];
}

/**
 * Helper: Get user name from page
 */
function getUserName() {
    // Try to get from various possible locations
    const nameEl = document.querySelector('.profile-name, .welcome-title, .user-display-name');
    return nameEl ? nameEl.textContent.trim().replace('Good Morning, ', '').replace('!', '').replace('✨', '').trim() : 'User';
}

// Initialize export/share buttons
document.addEventListener('DOMContentLoaded', function() {
    const exportBtn = document.getElementById('exportAnalytics');
    const shareBtn = document.getElementById('shareProgress');

    if (exportBtn) {
        exportBtn.addEventListener('click', exportAnalyticsReport);
        console.log('[Analytics Export] Export button initialized');
    }

    if (shareBtn) {
        shareBtn.addEventListener('click', shareProgress);
        console.log('[Analytics Export] Share button initialized');
    }
});

console.log('Analytics export/share functions loaded');
