/**
 * Enhanced Timeline JavaScript
 * Modern, accessible, performant implementation
 */

class MyavanaEnhancedTimeline {
    constructor() {
        this.currentView = 'timeline';
        this.currentPage = 1;
        this.entriesPerPage = myavanaTimeline.entries_per_page || 12;
        this.filters = {
            search: '',
            mood: '',
            rating: '',
            sort: 'date_desc'
        };
        this.entries = [];
        this.totalPages = 1;
        this.loading = false;
        this.modal = null;
        this.photoPreview = null;
        
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.initializeModal();
        this.loadEntries();
        this.updateStats();
    }
    
    bindEvents() {
        // View toggles
        document.querySelectorAll('.view-toggle').forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.switchView(e.target.closest('.view-toggle').dataset.view);
            });
        });
        
        // Add entry button
        document.getElementById('addEntryBtn')?.addEventListener('click', () => {
            this.openEntryModal();
        });
        
        // Search functionality
        const searchInput = document.getElementById('timelineSearch');
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    this.filters.search = e.target.value.trim();
                    this.currentPage = 1;
                    this.loadEntries();
                }, 300);
            });
        }
        
        // Filter controls
        ['moodFilter', 'ratingFilter', 'sortBy'].forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                element.addEventListener('change', (e) => {
                    const filterMap = {
                        'moodFilter': 'mood',
                        'ratingFilter': 'rating',
                        'sortBy': 'sort'
                    };
                    this.filters[filterMap[id]] = e.target.value;
                    this.currentPage = 1;
                    this.loadEntries();
                });
            }
        });
        
        // Pagination
        document.getElementById('prevPage')?.addEventListener('click', () => {
            if (this.currentPage > 1) {
                this.currentPage--;
                this.loadEntries();
            }
        });
        
        document.getElementById('nextPage')?.addEventListener('click', () => {
            if (this.currentPage < this.totalPages) {
                this.currentPage++;
                this.loadEntries();
            }
        });
        
        // Keyboard navigation
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.modal?.classList.contains('active')) {
                this.closeEntryModal();
            }
        });
    }
    
    switchView(view) {
        if (this.currentView === view) return;
        
        this.currentView = view;
        
        // Update toggle buttons
        document.querySelectorAll('.view-toggle').forEach(btn => {
            const isActive = btn.dataset.view === view;
            btn.classList.toggle('active', isActive);
            btn.setAttribute('aria-pressed', isActive.toString());
        });
        
        // Update timeline container
        const container = document.querySelector('.myavana-enhanced-timeline');
        if (container) {
            container.setAttribute('data-view', view);
        }
        
        this.renderEntries();
    }
    
    async loadEntries() {
        if (this.loading) return;
        
        this.loading = true;
        this.showLoading();
        
        try {
            const response = await this.makeRequest('myavana_get_diary_entries', {
                page: this.currentPage,
                per_page: this.entriesPerPage,
                filters: this.filters
            });
            
            if (response.success) {
                this.entries = response.data.entries;
                this.totalPages = response.data.total_pages;
                this.renderEntries();
                this.updatePagination();
                this.updateStats(response.data.stats);
            } else {
                this.showError(response.data?.message || myavanaTimeline.strings.error);
            }
        } catch (error) {
            console.error('Timeline Error:', error);
            this.showError(myavanaTimeline.strings.error);
        } finally {
            this.loading = false;
            this.hideLoading();
        }
    }
    
    renderEntries() {
        const content = document.getElementById('timelineContent');
        const empty = document.getElementById('timelineEmpty');
        
        if (!content) return;
        
        if (this.entries.length === 0) {
            content.style.display = 'none';
            empty.style.display = 'block';
            return;
        }
        
        content.style.display = 'block';
        empty.style.display = 'none';
        
        const viewClass = `${this.currentView}-view`;
        content.className = `timeline-content ${viewClass}`;
        
        switch (this.currentView) {
            case 'timeline':
                content.innerHTML = this.renderTimelineView();
                break;
            case 'grid':
                content.innerHTML = this.renderGridView();
                break;
            case 'cards':
                content.innerHTML = this.renderCardsView();
                break;
        }
        
        // Bind entry actions
        this.bindEntryActions();
        
        // Add animation
        this.animateEntries();
    }
    
    renderTimelineView() {
        return this.entries.map((entry, index) => `
            <article class="timeline-entry" data-entry-id="${entry.id}" tabindex="0" 
                     role="article" aria-labelledby="entry-title-${entry.id}">
                <div class="timeline-entry-content">
                    ${this.renderEntryHeader(entry)}
                    ${this.renderEntryImage(entry)}
                    ${this.renderEntryDescription(entry)}
                    ${this.renderEntryMeta(entry)}
                    ${this.renderEntryTags(entry)}
                </div>
            </article>
        `).join('');
    }
    
    renderGridView() {
        return this.entries.map(entry => `
            <article class="grid-entry" data-entry-id="${entry.id}" tabindex="0"
                     role="article" aria-labelledby="entry-title-${entry.id}">
                ${this.renderEntryHeader(entry)}
                ${this.renderEntryImage(entry)}
                ${this.renderEntryDescription(entry)}
                ${this.renderEntryMeta(entry)}
                ${this.renderEntryTags(entry)}
            </article>
        `).join('');
    }
    
    renderCardsView() {
        return this.entries.map(entry => `
            <article class="card-entry" data-entry-id="${entry.id}" tabindex="0"
                     role="article" aria-labelledby="entry-title-${entry.id}">
                <div class="card-entry-content">
                    ${this.renderEntryHeader(entry)}
                    ${this.renderEntryDescription(entry)}
                    ${this.renderEntryMeta(entry)}
                    ${this.renderEntryTags(entry)}
                </div>
                ${entry.image ? `
                    <div class="card-entry-image">
                        <img src="${this.escapeHtml(entry.image)}" alt="${this.escapeHtml(entry.title)}" class="entry-image">
                    </div>
                ` : ''}
            </article>
        `).join('');
    }
    
    renderEntryHeader(entry) {
        return `
            <header class="entry-header">
                <div class="entry-header-content">
                    <h3 class="entry-title" id="entry-title-${entry.id}">
                        ${this.escapeHtml(entry.title)}
                    </h3>
                    <time class="entry-date" datetime="${entry.date}">
                        ${this.formatDate(entry.date)}
                    </time>
                </div>
                <div class="entry-actions">
                    <button type="button" class="entry-action-btn edit-entry" 
                            data-entry-id="${entry.id}" 
                            title="Edit entry" aria-label="Edit ${this.escapeHtml(entry.title)}">
                        <svg viewBox="0 0 24 24" width="16" height="16">
                            <path fill="currentColor" d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/>
                        </svg>
                    </button>
                    <button type="button" class="entry-action-btn delete-entry danger" 
                            data-entry-id="${entry.id}"
                            title="Delete entry" aria-label="Delete ${this.escapeHtml(entry.title)}">
                        <svg viewBox="0 0 24 24" width="16" height="16">
                            <path fill="currentColor" d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/>
                        </svg>
                    </button>
                </div>
            </header>
        `;
    }
    
    renderEntryImage(entry) {
        if (!entry.image) return '';
        return `
            <div class="entry-image-container">
                <img src="${this.escapeHtml(entry.image)}" 
                     alt="${this.escapeHtml(entry.title)}" 
                     class="entry-image"
                     loading="lazy">
            </div>
        `;
    }
    
    renderEntryDescription(entry) {
        if (!entry.description?.trim()) return '';
        return `
            <div class="entry-description">
                ${this.escapeHtml(entry.description)}
            </div>
        `;
    }
    
    renderEntryMeta(entry) {
        const rating = parseInt(entry.rating) || 0;
        const stars = Array.from({length: 5}, (_, i) => 
            `<span class="rating-star ${i < rating ? '' : 'empty'}">★</span>`
        ).join('');
        
        return `
            <div class="entry-meta">
                <div class="entry-rating" aria-label="Rating: ${rating} out of 5 stars">
                    <div class="rating-stars">${stars}</div>
                </div>
                ${entry.mood_demeanor ? `
                    <div class="entry-mood">
                        ${this.getMoodIcon(entry.mood_demeanor)}
                        ${this.escapeHtml(entry.mood_demeanor)}
                    </div>
                ` : ''}
            </div>
        `;
    }
    
    renderEntryTags(entry) {
        if (!entry.products?.trim() && !entry.ai_tags?.length) return '';
        
        const tags = [];
        
        if (entry.products?.trim()) {
            entry.products.split(',').forEach(product => {
                tags.push(`<span class="entry-tag">${this.escapeHtml(product.trim())}</span>`);
            });
        }
        
        if (entry.ai_tags?.length) {
            entry.ai_tags.slice(0, 3).forEach(tag => {
                tags.push(`<span class="entry-tag ai-tag">${this.escapeHtml(tag)}</span>`);
            });
        }
        
        return `<div class="entry-tags">${tags.join('')}</div>`;
    }
    
    bindEntryActions() {
        // Edit entry buttons
        document.querySelectorAll('.edit-entry').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const entryId = btn.dataset.entryId;
                this.openEntryModal(entryId);
            });
        });
        
        // Delete entry buttons
        document.querySelectorAll('.delete-entry').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const entryId = btn.dataset.entryId;
                this.confirmDeleteEntry(entryId);
            });
        });
        
        // Entry click for keyboard navigation
        document.querySelectorAll('[data-entry-id]').forEach(entry => {
            entry.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    const editBtn = entry.querySelector('.edit-entry');
                    if (editBtn) editBtn.click();
                }
            });
        });
    }
    
    animateEntries() {
        const entries = document.querySelectorAll('[data-entry-id]');
        entries.forEach((entry, index) => {
            entry.style.opacity = '0';
            entry.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                entry.style.transition = 'all 0.3s ease-out';
                entry.style.opacity = '1';
                entry.style.transform = 'translateY(0)';
            }, index * 100);
        });
    }
    
    updatePagination() {
        const pagination = document.getElementById('timelinePagination');
        const prevBtn = document.getElementById('prevPage');
        const nextBtn = document.getElementById('nextPage');
        const currentPage = document.getElementById('currentPage');
        const totalPages = document.getElementById('totalPages');
        
        if (!pagination) return;
        
        if (this.totalPages <= 1) {
            pagination.style.display = 'none';
            return;
        }
        
        pagination.style.display = 'flex';
        
        if (prevBtn) {
            prevBtn.disabled = this.currentPage <= 1;
        }
        
        if (nextBtn) {
            nextBtn.disabled = this.currentPage >= this.totalPages;
        }
        
        if (currentPage) {
            currentPage.textContent = this.currentPage;
        }
        
        if (totalPages) {
            totalPages.textContent = this.totalPages;
        }
    }
    
    updateStats(stats = {}) {
        const elements = {
            totalEntries: document.getElementById('totalEntries'),
            avgHealth: document.getElementById('avgHealth'),
            improvement: document.getElementById('improvement'),
            streak: document.getElementById('streak')
        };
        
        if (elements.totalEntries) {
            this.animateCounter(elements.totalEntries, stats.total_entries || 0);
        }
        
        if (elements.avgHealth) {
            this.animateCounter(elements.avgHealth, stats.avg_health || 0, 1);
        }
        
        if (elements.improvement) {
            const improvement = stats.improvement || 0;
            this.animateCounter(elements.improvement, Math.abs(improvement));
            elements.improvement.textContent = `${improvement >= 0 ? '+' : '-'}${Math.abs(improvement)}%`;
        }
        
        if (elements.streak) {
            this.animateCounter(elements.streak, stats.streak || 0);
        }
    }
    
    animateCounter(element, target, decimals = 0) {
        const start = parseFloat(element.textContent) || 0;
        const increment = (target - start) / 20;
        let current = start;
        
        const timer = setInterval(() => {
            current += increment;
            
            if ((increment > 0 && current >= target) || (increment < 0 && current <= target)) {
                current = target;
                clearInterval(timer);
            }
            
            element.textContent = decimals > 0 ? current.toFixed(decimals) : Math.round(current);
        }, 50);
    }
    
    initializeModal() {
        this.modal = document.getElementById('entryModal');
        
        if (!this.modal) return;
        
        // Close button
        document.getElementById('modalClose')?.addEventListener('click', () => {
            this.closeEntryModal();
        });
        
        // Cancel button
        document.getElementById('cancelEntry')?.addEventListener('click', () => {
            this.closeEntryModal();
        });
        
        // Click outside to close
        this.modal.addEventListener('click', (e) => {
            if (e.target === this.modal) {
                this.closeEntryModal();
            }
        });
        
        // Form submission
        const form = document.getElementById('entryForm');
        if (form) {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleFormSubmission(form);
            });
        }
        
        // Photo upload
        const photoInput = document.getElementById('entryPhoto');
        if (photoInput) {
            photoInput.addEventListener('change', (e) => {
                this.handlePhotoUpload(e.target.files[0]);
            });
        }
        
        // Remove photo
        document.getElementById('removePhoto')?.addEventListener('click', () => {
            this.removePhoto();
        });
        
        // Character counter for description
        const descTextarea = document.getElementById('entryDescription');
        const descCounter = document.getElementById('descCounter');
        if (descTextarea && descCounter) {
            descTextarea.addEventListener('input', () => {
                descCounter.textContent = descTextarea.value.length;
            });
        }
        
        // Rating interaction
        this.initRatingInput();
    }
    
    initRatingInput() {
        const ratingInputs = document.querySelectorAll('.rating-input input[type="radio"]');
        const ratingStars = document.querySelectorAll('.rating-input .rating-star');
        
        ratingStars.forEach((star, index) => {
            star.addEventListener('mouseover', () => {
                this.highlightStars(index + 1);
            });
            
            star.addEventListener('click', () => {
                ratingInputs[index].checked = true;
                this.highlightStars(index + 1);
            });
        });
        
        const ratingContainer = document.querySelector('.rating-input');
        if (ratingContainer) {
            ratingContainer.addEventListener('mouseleave', () => {
                const checkedIndex = Array.from(ratingInputs).findIndex(input => input.checked);
                this.highlightStars(checkedIndex + 1);
            });
        }
    }
    
    highlightStars(count) {
        const stars = document.querySelectorAll('.rating-input .rating-star');
        stars.forEach((star, index) => {
            star.style.color = index < count ? '#f59e0b' : '#e5e7eb';
        });
    }
    
    openEntryModal(entryId = null) {
        if (!this.modal) return;
        
        const title = document.getElementById('modalTitle');
        const form = document.getElementById('entryForm');
        
        if (entryId) {
            // Edit mode
            if (title) title.textContent = 'Edit Hair Journey Entry';
            this.populateForm(entryId);
        } else {
            // Create mode
            if (title) title.textContent = 'Add Hair Journey Entry';
            if (form) form.reset();
            this.removePhoto();
        }
        
        this.modal.classList.add('active');
        this.modal.setAttribute('aria-hidden', 'false');
        
        // Focus management
        const firstInput = this.modal.querySelector('input, textarea, select, button');
        if (firstInput) {
            setTimeout(() => firstInput.focus(), 100);
        }
        
        // Prevent background scrolling
        document.body.style.overflow = 'hidden';
    }
    
    closeEntryModal() {
        if (!this.modal) return;
        
        this.modal.classList.remove('active');
        this.modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        
        // Clear any validation errors
        this.clearFormErrors();
    }
    
    async populateForm(entryId) {
        try {
            const response = await this.makeRequest('myavana_get_single_entry', {
                entry_id: entryId
            });
            
            if (response.success) {
                const entry = response.data;
                
                // Populate form fields
                document.getElementById('entryId').value = entry.id;
                document.getElementById('entryTitle').value = entry.title || '';
                document.getElementById('entryDescription').value = entry.description || '';
                document.getElementById('entryProducts').value = entry.products || '';
                document.getElementById('entryNotes').value = entry.notes || '';
                document.getElementById('entryEnvironment').value = entry.environment || 'home';
                document.getElementById('entryMood').value = entry.mood_demeanor || 'excited';
                
                // Set rating
                const rating = parseInt(entry.rating) || 3;
                const ratingInput = document.getElementById(`rating${rating}`);
                if (ratingInput) {
                    ratingInput.checked = true;
                    this.highlightStars(rating);
                }
                
                // Handle image
                if (entry.image) {
                    this.showPhotoPreview(entry.image);
                }
                
                // Update character counter
                const descCounter = document.getElementById('descCounter');
                if (descCounter) {
                    descCounter.textContent = (entry.description || '').length;
                }
            }
        } catch (error) {
            console.error('Error loading entry:', error);
            this.showNotification('Error loading entry details', 'error');
        }
    }
    
    async handleFormSubmission(form) {
        const submitBtn = document.getElementById('saveEntry');
        const btnText = submitBtn?.querySelector('.btn-text');
        const btnLoader = submitBtn?.querySelector('.btn-loader');
        
        if (submitBtn) {
            submitBtn.disabled = true;
            if (btnText) btnText.style.display = 'none';
            if (btnLoader) btnLoader.style.display = 'flex';
        }
        
        try {
            // Validate form
            if (!this.validateForm(form)) {
                return;
            }
            
            const formData = new FormData(form);
            formData.append('action', 'myavana_add_diary_entry');
            formData.append('myavana_nonce', myavanaTimeline.nonce);
            
            const response = await fetch(myavanaTimeline.ajax_url, {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showNotification(result.data?.message || myavanaTimeline.strings.save_success, 'success');
                this.closeEntryModal();
                this.loadEntries();
                this.updateStats();
                
                // Show AI tip if available
                if (result.data?.tip) {
                    setTimeout(() => {
                        this.showNotification(`💡 AI Tip: ${result.data.tip}`, 'info', 8000);
                    }, 1000);
                }
            } else {
                this.showNotification(result.data?.message || 'Error saving entry', 'error');
            }
        } catch (error) {
            console.error('Form submission error:', error);
            this.showNotification('Error saving entry. Please try again.', 'error');
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                if (btnText) btnText.style.display = 'inline';
                if (btnLoader) btnLoader.style.display = 'none';
            }
        }
    }
    
    validateForm(form) {
        this.clearFormErrors();
        let isValid = true;
        
        // Required fields
        const requiredFields = [
            { id: 'entryTitle', message: 'Title is required' },
            { name: 'rating', message: 'Please select a health rating' }
        ];
        
        requiredFields.forEach(field => {
            let element;
            if (field.id) {
                element = document.getElementById(field.id);
            } else if (field.name) {
                element = form.querySelector(`input[name="${field.name}"]:checked`);
            }
            
            if (!element || !element.value?.trim()) {
                this.showFieldError(field.id || field.name, field.message);
                isValid = false;
            }
        });
        
        // Title length validation
        const title = document.getElementById('entryTitle')?.value;
        if (title && title.length > 100) {
            this.showFieldError('entryTitle', 'Title must be 100 characters or less');
            isValid = false;
        }
        
        // Description length validation
        const description = document.getElementById('entryDescription')?.value;
        if (description && description.length > 1000) {
            this.showFieldError('entryDescription', 'Description must be 1000 characters or less');
            isValid = false;
        }
        
        return isValid;
    }
    
    showFieldError(fieldId, message) {
        const field = document.getElementById(fieldId) || document.querySelector(`input[name="${fieldId}"]`);
        if (!field) return;
        
        const errorElement = field.closest('.form-group')?.querySelector('.form-error');
        if (errorElement) {
            errorElement.textContent = message;
            errorElement.style.display = 'block';
        }
        
        field.classList.add('error');
        field.setAttribute('aria-invalid', 'true');
    }
    
    clearFormErrors() {
        document.querySelectorAll('.form-error').forEach(error => {
            error.style.display = 'none';
        });
        
        document.querySelectorAll('.form-input, .form-textarea').forEach(field => {
            field.classList.remove('error');
            field.removeAttribute('aria-invalid');
        });
    }
    
    handlePhotoUpload(file) {
        if (!file) return;
        
        // Validate file type
        if (!file.type.startsWith('image/')) {
            this.showNotification('Please select a valid image file', 'error');
            return;
        }
        
        // Validate file size (10MB)
        if (file.size > 10 * 1024 * 1024) {
            this.showNotification('Image file must be smaller than 10MB', 'error');
            return;
        }
        
        // Show preview
        const reader = new FileReader();
        reader.onload = (e) => {
            this.showPhotoPreview(e.target.result);
        };
        reader.readAsDataURL(file);
    }
    
    showPhotoPreview(src) {
        const preview = document.getElementById('photoPreview');
        const image = document.getElementById('previewImage');
        
        if (preview && image) {
            image.src = src;
            preview.style.display = 'block';
        }
    }
    
    removePhoto() {
        const photoInput = document.getElementById('entryPhoto');
        const preview = document.getElementById('photoPreview');
        
        if (photoInput) {
            photoInput.value = '';
        }
        
        if (preview) {
            preview.style.display = 'none';
        }
    }
    
    async confirmDeleteEntry(entryId) {
        if (!confirm(myavanaTimeline.strings.delete_confirm)) {
            return;
        }
        
        try {
            const response = await this.makeRequest('myavana_delete_timeline_entry', {
                entry_id: entryId
            });
            
            if (response.success) {
                this.showNotification('Entry deleted successfully', 'success');
                this.loadEntries();
                this.updateStats();
            } else {
                this.showNotification(response.data?.message || 'Error deleting entry', 'error');
            }
        } catch (error) {
            console.error('Delete error:', error);
            this.showNotification('Error deleting entry. Please try again.', 'error');
        }
    }
    
    showLoading() {
        const loading = document.getElementById('timelineLoading');
        const content = document.getElementById('timelineContent');
        
        if (loading) loading.style.display = 'flex';
        if (content) content.style.display = 'none';
    }
    
    hideLoading() {
        const loading = document.getElementById('timelineLoading');
        
        if (loading) loading.style.display = 'none';
    }
    
    showError(message) {
        this.showNotification(message, 'error');
        this.hideLoading();
        
        const content = document.getElementById('timelineContent');
        const empty = document.getElementById('timelineEmpty');
        
        if (content) content.style.display = 'none';
        if (empty) {
            empty.innerHTML = `
                <div class="empty-state">
                    <div class="empty-icon error">
                        <svg viewBox="0 0 24 24">
                            <path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                        </svg>
                    </div>
                    <h3>Error Loading Timeline</h3>
                    <p>${this.escapeHtml(message)}</p>
                    <button type="button" class="empty-cta-btn" onclick="location.reload()">
                        Reload Page
                    </button>
                </div>
            `;
            empty.style.display = 'block';
        }
    }
    
    showNotification(message, type = 'info', duration = 5000) {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `timeline-notification ${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <span class="notification-message">${this.escapeHtml(message)}</span>
                <button type="button" class="notification-close" aria-label="Close notification">×</button>
            </div>
        `;
        
        // Add styles if not already present
        if (!document.getElementById('timeline-notifications-styles')) {
            const style = document.createElement('style');
            style.id = 'timeline-notifications-styles';
            style.textContent = `
                .timeline-notification {
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    z-index: 1001;
                    min-width: 300px;
                    max-width: 500px;
                    padding: 16px;
                    border-radius: 8px;
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                    transform: translateX(100%);
                    transition: transform 0.3s ease-out;
                }
                .timeline-notification.success { background: #d1fae5; color: #047857; border-left: 4px solid #10b981; }
                .timeline-notification.error { background: #fee2e2; color: #dc2626; border-left: 4px solid #ef4444; }
                .timeline-notification.info { background: #dbeafe; color: #1d4ed8; border-left: 4px solid #3b82f6; }
                .timeline-notification.warning { background: #fef3c7; color: #92400e; border-left: 4px solid #f59e0b; }
                .notification-content { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; }
                .notification-message { flex: 1; font-size: 14px; line-height: 1.4; }
                .notification-close { background: none; border: none; font-size: 18px; cursor: pointer; opacity: 0.7; }
                .notification-close:hover { opacity: 1; }
            `;
            document.head.appendChild(style);
        }
        
        // Add to DOM
        document.body.appendChild(notification);
        
        // Animate in
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
        }, 10);
        
        // Close button
        const closeBtn = notification.querySelector('.notification-close');
        const remove = () => {
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        };
        
        closeBtn.addEventListener('click', remove);
        
        // Auto remove
        if (duration > 0) {
            setTimeout(remove, duration);
        }
    }
    
    async makeRequest(action, data = {}) {
        const formData = new FormData();
        formData.append('action', action);
        
        // Use different nonce parameter names based on action
        if (action === 'myavana_get_diary_entries') {
            formData.append('security', myavanaTimeline.nonce);
        } else if (action === 'myavana_add_diary_entry' || action === 'myavana_delete_diary_entry' || action === 'myavana_edit_diary_entry') {
            formData.append('myavana_nonce', myavanaTimeline.nonce);
        } else {
            formData.append('nonce', myavanaTimeline.nonce);
        }
        
        Object.entries(data).forEach(([key, value]) => {
            if (typeof value === 'object') {
                formData.append(key, JSON.stringify(value));
            } else {
                formData.append(key, value);
            }
        });
        
        const response = await fetch(myavanaTimeline.ajax_url, {
            method: 'POST',
            body: formData
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        return await response.json();
    }
    
    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }
    
    getMoodIcon(mood) {
        const icons = {
            'excited': '😊',
            'happy': '😄',
            'optimistic': '🌟',
            'nervous': '😬',
            'determined': '💪'
        };
        return icons[mood.toLowerCase()] || '😊';
    }
    
    escapeHtml(text) {
        if (typeof text !== 'string') return '';
        
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    if (typeof myavanaTimeline !== 'undefined') {
        new MyavanaEnhancedTimeline();
    }
});