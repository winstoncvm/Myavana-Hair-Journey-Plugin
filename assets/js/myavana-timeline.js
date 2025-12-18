/**
 * MYAVANA Hair Journey Timeline - Enhanced & Smooth
 * Optimized for performance and user experience
 */

class MyavanaTimeline {
    constructor() {
        this.splide = null;
        this.entries = [];
        this.currentIndex = 0;
        this.isLoading = false;
        this.animations = {
            duration: 300,
            easing: 'cubic-bezier(0.4, 0, 0.2, 1)'
        };
        
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.loadEntries();
        this.setupIntersectionObserver();
        this.initProgressIndicator();
    }
    
    bindEvents() {
        const $ = jQuery;
        
        // Smooth page transitions
        $('#startJourneyBtn').on('click', () => this.startJourney());
        $('#restartJourneyBtn').on('click', () => this.restartJourney());
        $('#viewTimelineBtn').on('click', () => this.viewTimeline());
        $('#shareProgressBtn').on('click', () => this.shareProgress());
        
        // Modal handling with smooth transitions
        $('#addEntryBtn').on('click', () => this.openAddEntryModal());
        $('#modalClose').on('click', () => this.closeModal());
        $('#cancelDeleteBtn, #deleteModalClose').on('click', () => this.closeDeleteModal());
        
        // Form submission with loading states
        $('#myavana-entry-form').on('submit', (e) => this.handleFormSubmission(e));
        $('#confirmDeleteBtn').on('click', () => this.confirmDelete());
        
        // Keyboard navigation
        $(document).on('keydown', (e) => this.handleKeyboardNavigation(e));
        
        // Smooth scrolling for navigation
        this.setupSmoothScrolling();
    }
    
    async startJourney() {
        this.showLoader();
        
        try {
            console.log('starting journey')
            await this.loadEntries();
            this.animatePageTransition('#startPage', '#timelineContainer');
        } catch (error) {
            this.showError('Failed to load your journey. Please try again.');
        } finally {
            this.hideLoader();
        }
    }
    
    restartJourney() {
        this.animatePageTransition('#endPage', '#timelineContainer');
        if (this.splide) {
            this.splide.go(0);
        }
    }
    
    viewTimeline() {
        this.animatePageTransition('#endPage', '#timelineContainer');
    }
    
    shareProgress() {
        // Enhanced share functionality with Web Share API
        if (navigator.share) {
            const stats = this.calculateStats();
            navigator.share({
                title: 'My MYAVANA Hair Journey',
                text: `I've made amazing progress! ${stats.entries} entries, ${stats.growth}" growth, and ${stats.health}% health improvement!`,
                url: window.location.href
            }).catch(console.error);
        } else {
            // Fallback to copy to clipboard
            this.copyProgressToClipboard();
        }
    }
    
    animatePageTransition(fromPage, toPage) {
        const $ = jQuery;
        const $from = $(fromPage);
        const $to = $(toPage);
        
        // Smooth fade transition
        $from.fadeOut(this.animations.duration, () => {
            $to.fadeIn(this.animations.duration, () => {
                // Trigger any page-specific initializations
                if (toPage === '#timelineContainer') {
                    this.initSlider();
                }
            });
        });
    }
    
    async loadEntries() {
        if (this.isLoading) return;
        this.isLoading = true;
        
        try {
            const response = await this.makeAjaxRequest('myavana_get_entries', {});
            
            if (response.success) {
                this.entries = response.data.entries || [];
                this.updateUI(response.data);
                this.initSlider();
            } else {
                throw new Error(response.data || 'Failed to load entries');
            }
        } catch (error) {
            this.showError(error.message);
        } finally {
            this.isLoading = false;
        }
    }
    
    updateUI(data) {
        const $ = jQuery;
        
        // Smooth content updates with fade transitions
        $('.splide__list').fadeOut(200, function() {
            $(this).html(data.entries_html).fadeIn(200);
        });
        
        $('#timelineDates').fadeOut(200, function() {
            $(this).html(data.dates_html).fadeIn(200);
        });
        
        // Update stats with counter animation
        this.animateStats(data.stats);
        
        // Check if should show end page
        if (data.reached_end) {
            setTimeout(() => {
                this.animatePageTransition('#timelineContainer', '#endPage');
            }, 1000);
        }
    }
    
    animateStats(stats) {
        this.animateNumber('#entriesCount', 0, stats.entries_count);
        this.animateNumber('#productsCount', 0, stats.products_count);
        this.animateNumber('#healthProgress', 0, stats.health, '%');
        this.animateNumber('#growthProgress', 0, stats.growth, '"');
    }
    
    animateNumber(selector, start, end, suffix = '') {
        const $ = jQuery;
        const $element = $(selector);
        const duration = 1000;
        const startTime = performance.now();
        
        const animate = (currentTime) => {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            
            // Easing function for smooth animation
            const eased = 1 - Math.pow(1 - progress, 3);
            const current = Math.round(start + (end - start) * eased);
            
            $element.text((suffix === '"' || suffix === '%' ? '+' : '') + current + suffix);
            
            if (progress < 1) {
                requestAnimationFrame(animate);
            }
        };
        
        requestAnimationFrame(animate);
    }
    
    initSlider() {
        const $ = jQuery;
        const totalEntries = $('.splide__slide').length;
        
        if (totalEntries === 0) return;
        
        // Destroy existing slider
        if (this.splide) {
            this.splide.destroy();
        }
        
        // Enhanced slider configuration
        this.splide = new Splide('#slider', {
            type: 'loop',
            perPage: 1,
            perMove: 1,
            gap: '2rem',
            padding: { left: '10%', right: '10%' },
            arrows: true,
            pagination: false,
            speed: 600,
            easing: 'cubic-bezier(0.25, 1, 0.5, 1)',
            autoplay: myavanaTimeline.settings.autoplay === 'true',
            interval: 5000,
            pauseOnHover: true,
            pauseOnFocus: true,
            keyboard: true,
            wheel: true,
            wheelSleep: 300,
            breakpoints: {
                1024: {
                    padding: { left: '5%', right: '5%' },
                    gap: '1.5rem'
                },
                768: {
                    padding: { left: '2%', right: '2%' },
                    gap: '1rem'
                },
                480: {
                    padding: { left: '1%', right: '1%' },
                    gap: '0.5rem'
                }
            }
        });
        
        // Enhanced event handling
        this.splide.on('moved', (newIndex) => {
            this.onSlideChange(newIndex, totalEntries);
        });
        
        this.splide.on('ready', () => {
            this.onSliderReady(totalEntries);
        });
        
        // Add touch gesture support
        this.splide.on('drag', () => {
            this.addClass('is-dragging');
        });
        
        this.splide.on('dragged', () => {
            this.removeClass('is-dragging');
        });
        
        this.splide.mount();
        
        // Initialize entry interactions
        this.initEntryInteractions();
    }
    
    onSlideChange(newIndex, totalEntries) {
        this.currentIndex = newIndex;
        this.updateProgressBar(newIndex, totalEntries);
        this.updateActiveMarker(newIndex);
        this.handleEndOfJourney(newIndex, totalEntries);
        
        // Smooth scroll to current date marker
        this.scrollToActiveMarker();
    }
    
    onSliderReady(totalEntries) {
        this.updateProgressBar(0, totalEntries);
        this.updateActiveMarker(0);
        this.initDateMarkerClicks();
    }
    
    updateProgressBar(index, total) {
        const progressWidth = total > 0 ? ((index + 1) / total) * 100 : 0;
        $('#progress').css({
            width: `${progressWidth}%`,
            transition: 'width 0.3s ease'
        });
    }
    
    updateActiveMarker(index) {
        const $ = jQuery;
        $('.date-marker').removeClass('active');
        $(`.date-marker[data-index="${index}"]`).addClass('active');
    }
    
    scrollToActiveMarker() {
        const $ = jQuery;
        const $activeMarker = $('.date-marker.active');
        const $container = $('#timelineDates');
        
        if ($activeMarker.length && $container.length) {
            const scrollLeft = $activeMarker.position().left - ($container.width() / 2) + ($activeMarker.width() / 2);
            $container.animate({ scrollLeft: scrollLeft }, 300);
        }
    }
    
    initDateMarkerClicks() {
        const $ = jQuery;
        $('.date-marker').on('click', (e) => {
            const index = parseInt($(e.currentTarget).data('index'));
            if (this.splide && !isNaN(index)) {
                this.splide.go(index);
            }
        });
    }
    
    handleEndOfJourney(index, totalEntries) {
        const $ = jQuery;
        $('.custom-end-arrow').remove();
        
        if (index === totalEntries - 1 && totalEntries > 0) {
            const customArrow = $(`
                <div class="custom-end-arrow">
                    <button class="myavana-btn-primary end-journey-btn">
                        <span>VIEW SUMMARY</span>
                        <svg viewBox="0 0 24 24" style="width: 20px; height: 20px; fill: currentColor;">
                            <path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/>
                        </svg>
                    </button>
                </div>
            `);
            
            customArrow.on('click', () => {
                this.animatePageTransition('#timelineContainer', '#endPage');
            });
            
            $('.splide__arrows').append(customArrow);
        }
    }
    
    initEntryInteractions() {
        const $ = jQuery;
        
        $('.main-entry').each((index, element) => {
            const $entry = $(element);
            const entryId = $entry.data('entry-id');
            
            if (entryId) {
                // Add delete button with smooth hover effects
                const $deleteBtn = $(`
                    <button class="entry-delete-btn" data-entry-id="${entryId}">
                        <svg viewBox="0 0 24 24" style="width: 18px; height: 18px; fill: currentColor;">
                            <path d="M19 7H5v12c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V7zm-3.5-4H8.5c-.28 0-.5.22-.5.5s.22.5.5.5h7c.28 0 .5-.22.5-.5s-.22-.5-.5-.5zm-1-1h-5C8.67 2 8 2.67 8 3.5v.5h8v-.5C16 2.67 15.33 2 14.5 2z"/>
                        </svg>
                    </button>
                `);
                
                $deleteBtn.on('click', (e) => {
                    e.stopPropagation();
                    this.openDeleteModal(entryId);
                });
                
                $entry.find('.entry-meta').append($deleteBtn);
            }
        });
    }
    
    openAddEntryModal() {
        const $ = jQuery;
        $('#entryModal').addClass('active');
        $('body').addClass('modal-open');
        
        // Focus first input for accessibility
        setTimeout(() => {
            $('#title').focus();
        }, 100);
    }
    
    closeModal() {
        const $ = jQuery;
        $('#entryModal').removeClass('active');
        $('body').removeClass('modal-open');
        
        // Reset form
        $('#myavana-entry-form')[0].reset();
        this.clearMessages();
    }
    
    openDeleteModal(entryId) {
        const $ = jQuery;
        $('#entryToDelete').val(entryId);
        $('#deleteModal').addClass('active');
        $('body').addClass('modal-open');
    }
    
    closeDeleteModal() {
        const $ = jQuery;
        $('#deleteModal').removeClass('active');
        $('body').removeClass('modal-open');
    }
    
    async handleFormSubmission(e) {
        e.preventDefault();
        const $ = jQuery;
        const $form = $(e.target);
        const $submitBtn = $form.find('button[type="submit"]');
        
        // Show loading state
        this.setButtonLoading($submitBtn, true);
        
        try {
            const formData = new FormData(e.target);
            formData.append('action', 'myavana_add_entry');
            
            const response = await this.makeAjaxRequest('myavana_add_entry', formData, true);
            
            if (response.success) {
                this.showSuccess(response.data.message);
                this.closeModal();
                await this.loadEntries();
                
                // Show AI tip with smooth animation
                if (response.data.tip) {
                    this.showAITip(response.data.tip);
                }
            } else {
                this.showError(response.data || 'Failed to save entry');
            }
        } catch (error) {
            this.showError(error.message);
        } finally {
            this.setButtonLoading($submitBtn, false);
        }
    }
    
    async confirmDelete() {
        const $ = jQuery;
        const entryId = $('#entryToDelete').val();
        const $deleteBtn = $('#confirmDeleteBtn');
        
        this.setButtonLoading($deleteBtn, true);
        
        try {
            const response = await this.makeAjaxRequest('myavana_delete_entry', {
                entry_id: entryId
            });
            
            if (response.success) {
                this.showSuccess('Entry deleted successfully');
                this.closeDeleteModal();
                await this.loadEntries();
            } else {
                this.showError(response.data || 'Failed to delete entry');
            }
        } catch (error) {
            this.showError(error.message);
        } finally {
            this.setButtonLoading($deleteBtn, false);
        }
    }
    
    setButtonLoading($button, isLoading) {
        if (isLoading) {
            $button.prop('disabled', true).data('original-text', $button.text()).html(`
                <svg class="spin" viewBox="0 0 24 24" style="width: 20px; height: 20px; fill: currentColor; animation: spin 1s linear infinite;">
                    <path d="M12 6v3l4-4-4-4v3c-4.42 0-8 3.58-8 8 0 1.57.46 3.03 1.24 4.26L6.7 14.8c-.45-.83-.7-1.79-.7-2.8 0-3.31 2.69-6 6-6z"/>
                </svg>
                SAVING...
            `);
        } else {
            $button.prop('disabled', false).text($button.data('original-text') || 'SAVE');
        }
    }
    
    async makeAjaxRequest(action, data, isFormData = false) {
        const requestData = isFormData ? data : {
            action: action,
            security: myavanaTimeline.nonce,
            ...data
        };
        
        const response = await jQuery.ajax({
            url: myavanaTimeline.ajax_url,
            type: 'POST',
            data: requestData,
            processData: !isFormData,
            contentType: isFormData ? false : 'application/x-www-form-urlencoded; charset=UTF-8',
            timeout: 30000
        });
        
        return response;
    }
    
    showLoader() {
        const $ = jQuery;
        if (!$('#myavanaLoader').length) {
            $('body').append(`
                <div id="myavanaLoader" class="myavana-loader">
                    <div class="loader-content">
                        <div class="loader-spinner"></div>
                        <p class="myavana-body">Loading your hair journey...</p>
                    </div>
                </div>
            `);
        }
        $('#myavanaLoader').fadeIn(200);
    }
    
    hideLoader() {
        jQuery('#myavanaLoader').fadeOut(200);
    }
    
    showError(message) {
        this.showNotification(message, 'error');
    }
    
    showSuccess(message) {
        this.showNotification(message, 'success');
    }
    
    showNotification(message, type = 'info') {
        const $ = jQuery;
        const notification = $(`
            <div class="myavana-notification ${type}">
                <div class="notification-content">
                    <span>${message}</span>
                    <button class="notification-close">×</button>
                </div>
            </div>
        `);
        
        $('body').append(notification);
        
        // Auto dismiss after 5 seconds
        setTimeout(() => {
            notification.fadeOut(300, () => notification.remove());
        }, 5000);
        
        // Manual dismiss
        notification.find('.notification-close').on('click', () => {
            notification.fadeOut(300, () => notification.remove());
        });
    }
    
    showAITip(tip) {
        const $ = jQuery;
        const tipElement = $(`
            <div class="myavana-ai-tip">
                <div class="ai-tip-content">
                    <div class="ai-tip-icon">🤖</div>
                    <div class="ai-tip-text">
                        <strong>AI Tip:</strong> ${tip}
                    </div>
                </div>
            </div>
        `);
        
        $('body').append(tipElement);
        
        tipElement.fadeIn(300);
        setTimeout(() => {
            tipElement.fadeOut(300, () => tipElement.remove());
        }, 7000);
    }
    
    clearMessages() {
        jQuery('#error-message, #success-message').hide();
    }
    
    calculateStats() {
        // Calculate journey statistics
        return {
            entries: this.entries.length,
            growth: Math.round(this.entries.length * 0.5),
            health: Math.round(Math.random() * 30 + 10) // Placeholder
        };
    }
    
    setupIntersectionObserver() {
        // Optimize performance with intersection observer for animations
        if ('IntersectionObserver' in window) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('animate-in');
                    }
                });
            }, { threshold: 0.1 });
            
            // Observe elements for animation
            setTimeout(() => {
                document.querySelectorAll('.main-entry, .date-marker').forEach(el => {
                    observer.observe(el);
                });
            }, 500);
        }
    }
    
    initProgressIndicator() {
        // Add smooth progress indicator
        const $ = jQuery;
        $(window).on('scroll', () => {
            const winScroll = document.body.scrollTop || document.documentElement.scrollTop;
            const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
            const scrolled = (winScroll / height) * 100;
            $('.scroll-progress').css('width', scrolled + '%');
        });
    }
    
    handleKeyboardNavigation(e) {
        if (!this.splide) return;
        
        switch(e.code) {
            case 'ArrowLeft':
                if (document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'TEXTAREA') {
                    e.preventDefault();
                    this.splide.go('<');
                }
                break;
            case 'ArrowRight':
                if (document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'TEXTAREA') {
                    e.preventDefault();
                    this.splide.go('>');
                }
                break;
            case 'Escape':
                this.closeModal();
                this.closeDeleteModal();
                break;
        }
    }
    
    setupSmoothScrolling() {
        // Enable smooth scrolling for anchor links
        const $ = jQuery;
        $('a[href*="#"]:not([href="#"])').on('click', function(e) {
            const target = $(this.getAttribute('href'));
            if (target.length) {
                e.preventDefault();
                $('html, body').animate({
                    scrollTop: target.offset().top - 80
                }, 600);
            }
        });
    }
    
    copyProgressToClipboard() {
        const stats = this.calculateStats();
        const text = `I've made amazing progress on my MYAVANA hair journey! ${stats.entries} entries, ${stats.growth}" growth, and ${stats.health}% health improvement!`;
        
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(() => {
                this.showSuccess('Progress copied to clipboard!');
            }).catch(() => {
                this.showError('Failed to copy to clipboard');
            });
        }
    }
}

// Initialize when DOM is ready
jQuery(document).ready(function() {
    if (typeof myavanaTimeline !== 'undefined') {
        window.myavanaTimelineInstance = new MyavanaTimeline();
    }
});

// Add CSS for animations and notifications
const timelineStyles = `
<style>
.myavana-loader {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(34, 35, 35, 0.8);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 10000;
}

.loader-content {
    text-align: center;
    color: var(--myavana-white);
}

.loader-spinner {
    width: 50px;
    height: 50px;
    border: 3px solid var(--myavana-stone);
    border-top: 3px solid var(--myavana-coral);
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 20px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.myavana-notification {
    position: fixed;
    top: 20px;
    right: 20px;
    max-width: 400px;
    padding: 16px 20px;
    border-radius: 8px;
    color: var(--myavana-white);
    font-family: 'Archivo', sans-serif;
    font-weight: 600;
    z-index: 10001;
    box-shadow: 0 4px 12px rgba(34, 35, 35, 0.2);
    animation: slideInRight 0.3s ease-out;
}

.myavana-notification.success {
    background: var(--myavana-coral);
}

.myavana-notification.error {
    background: var(--myavana-blueberry);
}

.notification-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
}

.notification-close {
    background: none;
    border: none;
    color: inherit;
    font-size: 20px;
    cursor: pointer;
    padding: 0;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.myavana-ai-tip {
    position: fixed;
    bottom: 20px;
    left: 20px;
    max-width: 400px;
    background: var(--myavana-white);
    border: 1px solid var(--myavana-stone);
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 4px 12px rgba(34, 35, 35, 0.15);
    z-index: 10001;
    display: none;
}

.ai-tip-content {
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

.ai-tip-icon {
    font-size: 24px;
    flex-shrink: 0;
}

.ai-tip-text {
    font-family: 'Archivo', sans-serif;
    font-size: 13.5px;
    color: var(--myavana-onyx);
    line-height: 1.5;
}

@keyframes slideInRight {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

.animate-in {
    animation: fadeInUp 0.6s ease-out forwards;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.modal-open {
    overflow: hidden;
}

.scroll-progress {
    position: fixed;
    top: 0;
    left: 0;
    height: 3px;
    background: var(--myavana-coral);
    z-index: 9999;
    transition: width 0.1s ease;
}

.is-dragging {
    cursor: grabbing;
}

@media (max-width: 768px) {
    .myavana-notification,
    .myavana-ai-tip {
        left: 10px;
        right: 10px;
        max-width: none;
    }
}
</style>
`;

document.head.insertAdjacentHTML('beforeend', timelineStyles);