/**
 * MYAVANA Improved Timeline - Interactive Navigation
 * Ultra compact responsive timeline with Swiper.js integration
 */

class MyavanaImprovedTimeline {
    constructor(container) {
        this.container = container;
        this.swiper = null;
        this.currentPage = 1;
        this.entriesPerPage = 10;
        this.totalEntries = 0;
        this.allEntries = [];
        this.filteredEntries = [];
        this.isLoading = false;

        this.init();
    }

    init() {
        this.initSwiper();
        this.bindEvents();
        this.loadTimelineData();
    }

    initSwiper() {
        const swiperContainer = this.container.querySelector('.timeline-swiper-container');
        if (!swiperContainer) return;

        // Initialize Swiper with minimal configuration
        this.swiper = new Swiper(swiperContainer, {
            direction: 'horizontal',
            loop: false,
            speed: 300,
            spaceBetween: 0,
            slidesPerView: 1,
            allowTouchMove: true,
            navigation: {
                nextEl: '.timeline-nav-next',
                prevEl: '.timeline-nav-prev',
            },
            pagination: {
                el: '.timeline-pagination',
                clickable: true,
                bulletClass: 'pagination-dot',
                bulletActiveClass: 'active',
                renderBullet: function (index, className) {
                    return `<span class="${className}"></span>`;
                },
            },
            on: {
                slideChange: () => {
                    this.onSlideChange();
                }
            }
        });
    }

    bindEvents() {
        // Begin Journey button
        const beginBtn = this.container.querySelector('.begin-journey-btn');
        if (beginBtn) {
            beginBtn.addEventListener('click', () => {
                this.beginJourney();
            });
        }

        // Entry click handlers
        this.container.addEventListener('click', (e) => {
            const entryElement = e.target.closest('.timeline-entry');
            if (entryElement) {
                const entryId = entryElement.dataset.entryId;
                this.openEntryModal(entryId);
            }

            const viewBtn = e.target.closest('.view-entry-btn');
            if (viewBtn) {
                e.stopPropagation();
                const entryId = viewBtn.dataset.entryId;
                this.openEntryModal(entryId);
            }

            const addFirstBtn = e.target.closest('.add-first-entry-btn');
            if (addFirstBtn) {
                this.openAddEntryModal();
            }

            const modalClose = e.target.closest('.modal-close');
            if (modalClose) {
                this.closeModal();
            }

            // Close modal when clicking outside
            if (e.target.classList.contains('timeline-modal')) {
                this.closeModal();
            }
        });

        // Navigation buttons
        const prevBtn = this.container.querySelector('.timeline-nav-prev');
        const nextBtn = this.container.querySelector('.timeline-nav-next');

        if (prevBtn) {
            prevBtn.addEventListener('click', () => {
                this.navigatePrevious();
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', () => {
                this.navigateNext();
            });
        }

        // Keyboard navigation
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeModal();
            }
        });
    }

    beginJourney() {
        if (this.swiper) {
            this.swiper.slideNext();
        }
    }

    navigatePrevious() {
        if (this.currentPage > 1) {
            this.currentPage--;
            this.loadTimelineData();
        }
    }

    navigateNext() {
        const totalPages = Math.ceil(this.totalEntries / this.entriesPerPage);
        if (this.currentPage < totalPages) {
            this.currentPage++;
            this.loadTimelineData();
        }
    }

    onSlideChange() {
        if (!this.swiper) return;

        const currentSlide = this.swiper.activeIndex;

        // Update navigation buttons
        this.updateNavigationState();

        // Load data for timeline slides
        if (currentSlide > 0) {
            this.loadTimelineData();
        }
    }

    updateNavigationState() {
        const prevBtn = this.container.querySelector('.timeline-nav-prev');
        const nextBtn = this.container.querySelector('.timeline-nav-next');

        if (prevBtn) {
            prevBtn.disabled = this.currentPage <= 1;
        }

        if (nextBtn) {
            const totalPages = Math.ceil(this.totalEntries / this.entriesPerPage);
            nextBtn.disabled = this.currentPage >= totalPages;
        }
    }

    async loadTimelineData() {
        if (this.isLoading) return;

        this.isLoading = true;
        this.showLoading();

        try {
            const response = await this.makeAjaxRequest('get_timeline_entries', {
                page: this.currentPage,
                per_page: this.entriesPerPage,
                user_id: this.getCurrentUserId()
            });

            if (response.success) {
                this.allEntries = response.data.entries || [];
                this.totalEntries = response.data.total || 0;
                this.filteredEntries = [...this.allEntries];

                this.renderTimelineEntries();
                this.updateStats(response.data.stats || {});
                this.updateNavigationState();
            } else {
                this.showError(response.data?.message || 'Failed to load timeline data');
            }
        } catch (error) {
            console.error('Timeline loading error:', error);
            this.showError('An error occurred while loading timeline data');
        } finally {
            this.isLoading = false;
            this.hideLoading();
        }
    }

    renderTimelineEntries() {
        const timelineContent = this.container.querySelector('.timeline-content');
        if (!timelineContent) return;

        if (this.filteredEntries.length === 0) {
            this.renderEmptyState(timelineContent);
            return;
        }

        const entriesContainer = timelineContent.querySelector('.timeline-entries') ||
                                document.createElement('div');
        entriesContainer.className = 'timeline-entries';
        entriesContainer.innerHTML = '';

        this.filteredEntries.forEach(entry => {
            const entryElement = this.createEntryElement(entry);
            entriesContainer.appendChild(entryElement);
        });

        timelineContent.innerHTML = '';
        timelineContent.appendChild(entriesContainer);
    }

    createEntryElement(entry) {
        const entryDiv = document.createElement('div');
        entryDiv.className = `timeline-entry ${entry.featured ? 'featured' : ''}`;
        entryDiv.dataset.entryId = entry.id;

        const formattedDate = this.formatDate(entry.entry_date);
        const excerpt = this.truncateText(entry.entry_content || '', 120);

        entryDiv.innerHTML = `
            <div class="entry-header">
                <div class="entry-date">${formattedDate}</div>
                <div class="entry-type">${this.getEntryTypeLabel(entry.entry_type)}</div>
            </div>
            <div class="entry-content">
                <h4 class="entry-title">${this.escapeHtml(entry.entry_title || 'Hair Journey Entry')}</h4>
                <p class="entry-excerpt">${this.escapeHtml(excerpt)}</p>
            </div>
            ${this.renderEntryMedia(entry)}
            <div class="entry-actions">
                <div class="entry-metrics">
                    ${this.renderEntryMetrics(entry)}
                </div>
                <button class="view-entry-btn" data-entry-id="${entry.id}">
                    View Entry
                </button>
            </div>
        `;

        return entryDiv;
    }

    renderEntryMedia(entry) {
        if (!entry.images || entry.images.length === 0) {
            return '';
        }

        const mediaHtml = entry.images.slice(0, 3).map(image =>
            `<img src="${image.thumbnail || image.url}" alt="Entry image" class="entry-thumbnail">`
        ).join('');

        return `<div class="entry-media">${mediaHtml}</div>`;
    }

    renderEntryMetrics(entry) {
        const metrics = [];

        if (entry.mood_rating) {
            metrics.push(`
                <div class="metric-item">
                    <svg class="metric-icon" viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zM8.5 11c.83 0 1.5-.67 1.5-1.5S9.33 8 8.5 8 7 8.67 7 9.5 7.67 11 8.5 11zm7 0c.83 0 1.5-.67 1.5-1.5S16.33 8 15.5 8 14 8.67 14 9.5 14.67 11 15.5 11zm-3.5 6.5c2.33 0 4.31-1.46 5.11-3.5H6.89c.8 2.04 2.78 3.5 5.11 3.5z"/>
                    </svg>
                    ${entry.mood_rating}/10
                </div>
            `);
        }

        if (entry.hair_health_rating) {
            metrics.push(`
                <div class="metric-item">
                    <svg class="metric-icon" viewBox="0 0 24 24">
                        <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
                    </svg>
                    ${entry.hair_health_rating}/10
                </div>
            `);
        }

        if (entry.images && entry.images.length > 0) {
            metrics.push(`
                <div class="metric-item">
                    <svg class="metric-icon" viewBox="0 0 24 24">
                        <path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/>
                    </svg>
                    ${entry.images.length}
                </div>
            `);
        }

        return metrics.join('');
    }

    renderEmptyState(container) {
        container.innerHTML = `
            <div class="timeline-empty">
                <div class="empty-icon">✨</div>
                <h4>Your Hair Journey Awaits</h4>
                <p>Start documenting your hair journey to see your progress and achievements here.</p>
                <button class="add-first-entry-btn">Add Your First Entry</button>
            </div>
        `;
    }

    updateStats(stats) {
        const statsContainer = this.container.querySelector('.timeline-stats');
        if (!statsContainer) return;

        const statItems = [
            { label: 'Total', value: stats.total_entries || 0 },
            { label: 'Days', value: stats.journey_days || 0 },
            { label: 'Health', value: stats.avg_health_rating ? `${stats.avg_health_rating}/10` : 'N/A' },
            { label: 'Growth', value: stats.hair_growth || 'N/A' }
        ];

        statsContainer.innerHTML = statItems.map(stat => `
            <div class="stat-item">
                <div class="stat-number">${stat.value}</div>
                <div class="stat-label">${stat.label}</div>
            </div>
        `).join('');
    }

    async openEntryModal(entryId) {
        try {
            const response = await this.makeAjaxRequest('get_entry_details', {
                entry_id: entryId
            });

            if (response.success) {
                this.renderEntryModal(response.data);
            } else {
                this.showError(response.data?.message || 'Failed to load entry details');
            }
        } catch (error) {
            console.error('Entry modal error:', error);
            this.showError('An error occurred while loading entry details');
        }
    }

    renderEntryModal(entry) {
        const modal = document.createElement('div');
        modal.className = 'timeline-modal';

        modal.innerHTML = `
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">${this.escapeHtml(entry.entry_title || 'Hair Journey Entry')}</h3>
                    <button class="modal-close">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="entry-date-full">${this.formatDateFull(entry.entry_date)}</div>
                    <div class="entry-content-full">
                        ${this.formatContent(entry.entry_content || '')}
                    </div>
                    ${this.renderModalMedia(entry)}
                    ${this.renderModalMetrics(entry)}
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        // Animate in
        requestAnimationFrame(() => {
            modal.style.opacity = '1';
        });
    }

    renderModalMedia(entry) {
        if (!entry.images || entry.images.length === 0) {
            return '';
        }

        const mediaHtml = entry.images.map(image => `
            <img src="${image.url}" alt="Entry image" class="modal-image"
                 onclick="this.classList.toggle('fullscreen')">
        `).join('');

        return `
            <div class="modal-media">
                <h4>Photos</h4>
                <div class="modal-image-grid">${mediaHtml}</div>
            </div>
        `;
    }

    renderModalMetrics(entry) {
        const metrics = [];

        if (entry.mood_rating) {
            metrics.push(`<div>Mood: ${entry.mood_rating}/10</div>`);
        }

        if (entry.hair_health_rating) {
            metrics.push(`<div>Hair Health: ${entry.hair_health_rating}/10</div>`);
        }

        if (entry.hair_length) {
            metrics.push(`<div>Length: ${entry.hair_length}</div>`);
        }

        if (metrics.length === 0) return '';

        return `
            <div class="modal-metrics">
                <h4>Metrics</h4>
                <div class="metrics-grid">${metrics.join('')}</div>
            </div>
        `;
    }

    openAddEntryModal() {
        // This would typically open the hair diary form
        // For now, we'll show a simple notification
        this.showNotification('Add entry functionality would open here', 'info');
    }

    closeModal() {
        const modal = document.querySelector('.timeline-modal');
        if (modal) {
            modal.style.opacity = '0';
            setTimeout(() => {
                modal.remove();
            }, 200);
        }
    }

    showLoading() {
        const timelineContent = this.container.querySelector('.timeline-content');
        if (timelineContent) {
            timelineContent.innerHTML = `
                <div class="timeline-loading">
                    <div class="loading-spinner"></div>
                    <p class="loading-text">Loading your hair journey...</p>
                </div>
            `;
        }
    }

    hideLoading() {
        // Loading will be hidden when content is rendered
    }

    showError(message) {
        const timelineContent = this.container.querySelector('.timeline-content');
        if (timelineContent) {
            timelineContent.innerHTML = `
                <div class="timeline-error">
                    <div class="error-icon">⚠️</div>
                    <h4>Oops! Something went wrong</h4>
                    <p>${this.escapeHtml(message)}</p>
                    <button class="retry-btn" onclick="location.reload()">Try Again</button>
                </div>
            `;
        }
    }

    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `timeline-notification ${type}`;
        notification.textContent = message;

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.remove();
        }, 3000);
    }

    async makeAjaxRequest(action, data = {}) {
        const formData = new FormData();
        formData.append('action', `myavana_${action}`);
        formData.append('nonce', this.getAjaxNonce());

        Object.keys(data).forEach(key => {
            formData.append(key, data[key]);
        });

        const response = await fetch(ajaxurl || '/wp-admin/admin-ajax.php', {
            method: 'POST',
            body: formData
        });

        return await response.json();
    }

    // Utility methods
    formatDate(dateString) {
        const date = new Date(dateString);
        const options = { month: 'short', day: 'numeric' };
        return date.toLocaleDateString('en-US', options);
    }

    formatDateFull(dateString) {
        const date = new Date(dateString);
        const options = {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        };
        return date.toLocaleDateString('en-US', options);
    }

    formatContent(content) {
        return content.replace(/\n/g, '<br>');
    }

    truncateText(text, maxLength) {
        if (text.length <= maxLength) return text;
        return text.substr(0, maxLength) + '...';
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    getEntryTypeLabel(type) {
        const labels = {
            'routine': 'Routine',
            'progress': 'Progress',
            'milestone': 'Milestone',
            'note': 'Note',
            'treatment': 'Treatment'
        };
        return labels[type] || 'Entry';
    }

    getCurrentUserId() {
        return window.myavana_user_id || 0;
    }

    getAjaxNonce() {
        return window.myavana_timeline_nonce || '';
    }
}

// Initialize timeline when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    const timelineContainer = document.querySelector('.myavana-improved-timeline');
    if (timelineContainer) {
        new MyavanaImprovedTimeline(timelineContainer);
    }
});

// Export for potential module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = MyavanaImprovedTimeline;
}