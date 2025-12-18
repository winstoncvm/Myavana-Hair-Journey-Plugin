/**
 * MYAVANA Premium 3-Step Entry Form
 * Optimized for Mobile with fixed upload functionality
 * @version 1.1.0
 */

(function($) {
    'use strict';

    // Check if device is mobile
    const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);

    window.MyavanaPremiumEntryForm = {
        currentStep: 1,
        totalSteps: 3,
        formData: {},
        uploadedImages: [],
        featuredImageIndex: 0,

        /**
         * Open the premium entry form
         */
        open: function(prefillData) {
            this.currentStep = 1;
            this.formData = prefillData || {};
            this.uploadedImages = [];
            this.createModal();
        },

        /**
         * Create the premium modal structure
         */
        createModal: function() {
            $('#myavanaPremiumEntryModal').remove();

            const modalHTML = `
                <div id="myavanaPremiumEntryModal" class="myavana-premium-modal-overlay">
                    <div class="myavana-premium-modal">
                        <!-- Header -->
                        <div class="premium-modal-header">
                            <div class="header-content">
                                <h2 class="modal-title">Add Hair Journey Entry</h2>
                                <p class="modal-subtitle">Document your progress with style</p>
                            </div>
                            <button class="modal-close" onclick="MyavanaPremiumEntryForm.close()">
                                <svg viewBox="0 0 24 24" width="24" height="24">
                                    <path fill="currentColor" d="M19,6.41L17.59,5L12,10.59L6.41,5L5,6.41L10.59,12L5,17.59L6.41,19L12,13.41L17.59,19L19,17.59L13.41,12L19,6.41Z"/>
                                </svg>
                            </button>
                        </div>

                        <!-- Progress Indicator -->
                        <div class="premium-progress-bar">
                            ${this.renderProgressSteps()}
                        </div>

                        <!-- Form Steps Container -->
                        <div class="premium-form-body">
                            <form id="premiumEntryForm">
                                <!-- Step 1: Photos & Title -->
                                <div class="form-step ${this.currentStep === 1 ? 'active' : ''}" data-step="1">
                                    ${this.renderStep1()}
                                </div>

                                <!-- Step 2: Hair Health & Mood -->
                                <div class="form-step ${this.currentStep === 2 ? 'active' : ''}" data-step="2">
                                    ${this.renderStep2()}
                                </div>

                                <!-- Step 3: Description & Products -->
                                <div class="form-step ${this.currentStep === 3 ? 'active' : ''}" data-step="3">
                                    ${this.renderStep3()}
                                </div>
                            </form>
                        </div>

                        <!-- Footer Navigation -->
                        <div class="premium-modal-footer">
                            <button type="button" class="btn-secondary" onclick="MyavanaPremiumEntryForm.previousStep()" id="btnPrevious" style="display: none;">
                                <svg viewBox="0 0 24 24" width="20" height="20">
                                    <path fill="currentColor" d="M15.41,16.58L10.83,12L15.41,7.41L14,6L8,12L14,18L15.41,16.58Z"/>
                                </svg>
                                Previous
                            </button>
                            <button type="button" class="btn-primary" onclick="MyavanaPremiumEntryForm.nextStep()" id="btnNext">
                                Continue
                                <svg viewBox="0 0 24 24" width="20" height="20">
                                    <path fill="currentColor" d="M8.59,16.58L13.17,12L8.59,7.41L10,6L16,12L10,18L8.59,16.58Z"/>
                                </svg>
                            </button>
                            <button type="button" class="btn-primary btn-save" onclick="MyavanaPremiumEntryForm.saveEntry()" id="btnSave" style="display: none;">
                                <svg viewBox="0 0 24 24" width="20" height="20">
                                    <path fill="currentColor" d="M9,20.42L2.79,14.21L5.62,11.38L9,14.77L18.88,4.88L21.71,7.71L9,20.42Z"/>
                                </svg>
                                Save Entry
                            </button>
                        </div>
                    </div>
                </div>
            `;

            $('body').append(modalHTML);
            $('body').addClass('modal-open');

            // Apply prefill data
            this.applyPrefillData();

            // Animate in
            setTimeout(() => {
                $('#myavanaPremiumEntryModal').addClass('show');
            }, 50);

            this.bindEvents();
        },

        /**
         * Render progress steps with mobile optimization
         */
        renderProgressSteps: function() {
            return `
                <div class="progress-step ${this.currentStep >= 1 ? 'active' : ''} ${this.currentStep > 1 ? 'completed' : ''}" data-step="1">
                    <div class="step-circle">
                        <span class="step-number">1</span>
                        <svg class="step-check" viewBox="0 0 24 24" width="16" height="16">
                            <path fill="currentColor" d="M9,20.42L2.79,14.21L5.62,11.38L9,14.77L18.88,4.88L21.71,7.71L9,20.42Z"/>
                        </svg>
                    </div>
                    <span class="step-label">${isMobile ? 'Photos' : 'Photos & Title'}</span>
                </div>
                <div class="progress-line ${this.currentStep > 1 ? 'active' : ''}"></div>
                <div class="progress-step ${this.currentStep >= 2 ? 'active' : ''} ${this.currentStep > 2 ? 'completed' : ''}" data-step="2">
                    <div class="step-circle">
                        <span class="step-number">2</span>
                        <svg class="step-check" viewBox="0 0 24 24" width="16" height="16">
                            <path fill="currentColor" d="M9,20.42L2.79,14.21L5.62,11.38L9,14.77L18.88,4.88L21.71,7.71L9,20.42Z"/>
                        </svg>
                    </div>
                    <span class="step-label">${isMobile ? 'Health' : 'Hair Health'}</span>
                </div>
                <div class="progress-line ${this.currentStep > 2 ? 'active' : ''}"></div>
                <div class="progress-step ${this.currentStep >= 3 ? 'active' : ''} ${this.currentStep > 3 ? 'completed' : ''}" data-step="3">
                    <div class="step-circle">
                        <span class="step-number">3</span>
                        <svg class="step-check" viewBox="0 0 24 24" width="16" height="16">
                            <path fill="currentColor" d="M9,20.42L2.79,14.21L5.62,11.38L9,14.77L18.88,4.88L21.71,7.71L9,20.42Z"/>
                        </svg>
                    </div>
                    <span class="step-label">${isMobile ? 'Details' : 'Final Details'}</span>
                </div>
            `;
        },

        /**
         * Render Step 1: Photos & Title - Optimized for mobile
         */
        renderStep1: function() {
            const mobileUploadText = isMobile ? 'Tap to take or select photos' : 'Drag & drop your photos here';
            const mobileHint = isMobile ? 'Take photo or select from gallery' : 'PNG, JPG up to 10MB';

            return `
                <div class="step-content">
                    <div class="step-header">
                        <div class="step-icon">📸</div>
                        <h3>${isMobile ? 'Add Photos' : 'Start with a Photo'}</h3>
                        <p>${isMobile ? 'Capture your hair visually' : 'Capture your hair journey visually'}</p>
                    </div>

                    <!-- Optimized Upload Zone -->
                    <div class="premium-upload-zone ${isMobile ? 'mobile-upload' : ''}" id="uploadZone">
                        <div class="upload-zone-content">
                            <div class="upload-icon">
                                <svg viewBox="0 0 24 24" width="48" height="48">
                                    <path fill="currentColor" d="M9,16V10H5L12,3L19,10H15V16H9M5,20V18H19V20H5Z"/>
                                </svg>
                            </div>
                            <h4>${mobileUploadText}</h4>
                            <p>${isMobile ? '' : 'or click to browse'}</p>
                            <span class="upload-hint">${mobileHint}</span>
                            ${isMobile ? '<div class="mobile-upload-buttons"><button type="button" class="btn-camera">📷 Take Photo</button><button type="button" class="btn-gallery">🖼️ Choose from Gallery</button></div>' : ''}
                        </div>
                        <input type="file" id="imageInput" accept="image/*" capture="environment" multiple style="display: none;">
                    </div>

                    <!-- Image Preview Gallery -->
                    <div class="image-preview-gallery" id="imageGallery" style="display: none;">
                        <div class="gallery-label">
                            <span>${isMobile ? 'Photos' : 'Your Photos'}</span>
                            <button type="button" class="btn-add-more">
                                + ${isMobile ? 'Add' : 'Add More'}
                            </button>
                        </div>
                        <div class="gallery-grid ${isMobile ? 'mobile-grid' : ''}" id="galleryGrid"></div>
                    </div>

                    <!-- Entry Title -->
                    <div class="form-group">
                        <label class="form-label">
                            ${isMobile ? 'Title' : 'Entry Title'}
                            <span class="required">*</span>
                        </label>
                        <input
                            type="text"
                            id="entryTitle"
                            class="form-input"
                            placeholder="${isMobile ? 'e.g., Wash Day' : 'e.g., Wash Day, Deep Conditioning, Trim'}"
                            maxlength="100"
                        >
                        <div class="form-feedback" id="titleFeedback"></div>
                    </div>

                    <!-- Entry Date - Mobile optimized date picker -->
                    <div class="form-group">
                        <label class="form-label">
                            ${isMobile ? 'Date' : 'When did this happen?'}
                            <span class="required">*</span>
                        </label>
                        <div class="date-input-wrapper">
                            <input
                                type="date"
                                id="entryDate"
                                class="form-input ${isMobile ? 'mobile-date' : ''}"
                                value="${new Date().toISOString().split('T')[0]}"
                                max="${new Date().toISOString().split('T')[0]}"
                            >
                            ${isMobile ? '<span class="date-calendar-icon">📅</span>' : ''}
                        </div>
                    </div>
                </div>
            `;
        },

        /**
         * Render Step 2: Hair Health & Mood - Optimized for mobile
         */
        renderStep2: function() {
            return `
                <div class="step-content">
                    <div class="step-header">
                        <div class="step-icon">✨</div>
                        <h3>${isMobile ? 'Hair Health' : 'How\'s Your Hair?'}</h3>
                        <p>${isMobile ? 'Rate your hair & mood' : 'Tell us about your hair health and mood'}</p>
                    </div>

                    <!-- Star Rating for Hair Health -->
                    <div class="form-group">
                        <label class="form-label">${isMobile ? 'Rating' : 'Hair Health Rating'}</label>
                        <div class="star-rating ${isMobile ? 'mobile-stars' : ''}" id="starRating">
                            ${[1,2,3,4,5].map(star => `
                                <button type="button" class="star" data-rating="${star}">
                                    <svg viewBox="0 0 24 24" width="${isMobile ? '32' : '40'}" height="${isMobile ? '32' : '40'}">
                                        <path fill="currentColor" d="M12,17.27L18.18,21L16.54,13.97L22,9.24L14.81,8.62L12,2L9.19,8.62L2,9.24L7.45,13.97L5.82,21L12,17.27Z"/>
                                    </svg>
                                </button>
                            `).join('')}
                        </div>
                        <div class="rating-label" id="ratingLabel">${isMobile ? 'Tap stars to rate' : 'Tap to rate'}</div>
                    </div>

                    <!-- Emoji Mood Selector - Mobile optimized -->
                    <div class="form-group">
                        <label class="form-label">${isMobile ? 'Your Mood' : 'How are you feeling about your hair?'}</label>
                        <div class="mood-selector ${isMobile ? 'mobile-moods' : ''}" id="moodSelector">
                            <button type="button" class="mood-option" data-mood="Amazing">
                                <span class="mood-emoji">🌟</span>
                                ${!isMobile ? '<span class="mood-label">Amazing</span>' : ''}
                            </button>
                            <button type="button" class="mood-option" data-mood="Great">
                                <span class="mood-emoji">✨</span>
                                ${!isMobile ? '<span class="mood-label">Great</span>' : ''}
                            </button>
                            <button type="button" class="mood-option" data-mood="Good">
                                <span class="mood-emoji">👌</span>
                                ${!isMobile ? '<span class="mood-label">Good</span>' : ''}
                            </button>
                            <button type="button" class="mood-option" data-mood="Okay">
                                <span class="mood-emoji">😊</span>
                                ${!isMobile ? '<span class="mood-label">Okay</span>' : ''}
                            </button>
                            <button type="button" class="mood-option" data-mood="Needs TLC">
                                <span class="mood-emoji">💆‍♀️</span>
                                ${!isMobile ? '<span class="mood-label">Needs TLC</span>' : ''}
                            </button>
                        </div>
                        ${isMobile ? '<div class="mood-labels-row"><span>Amazing</span><span>Great</span><span>Good</span><span>Okay</span><span>Needs TLC</span></div>' : ''}
                    </div>
                </div>
            `;
        },

        /**
         * Render Step 3: Description & Products - Optimized for mobile
         */
        renderStep3: function() {
            return `
                <div class="step-content">
                    <div class="step-header">
                        <div class="step-icon">📝</div>
                        <h3>${isMobile ? 'Details' : 'Final Details'}</h3>
                        <p>${isMobile ? 'Add notes & products' : 'Add description and products used'}</p>
                    </div>

                    <!-- Description -->
                    <div class="form-group">
                        <label class="form-label">
                            ${isMobile ? 'Notes' : 'What happened?'}
                            <span class="required">*</span>
                        </label>
                        <textarea
                            id="entryDescription"
                            class="form-textarea ${isMobile ? 'mobile-textarea' : ''}"
                            rows="${isMobile ? '4' : '5'}"
                            placeholder="${isMobile ? 'Describe what you did...' : 'Describe what you did, how your hair felt, any observations...'}"
                            maxlength="2000"
                        ></textarea>
                        <div class="char-count">
                            <span id="charCount">0</span>/2000
                        </div>
                    </div>

                    <!-- Products Used -->
                    <div class="form-group">
                        <label class="form-label">${isMobile ? 'Products' : 'Products Used'}</label>
                        <input
                            type="text"
                            id="productsUsed"
                            class="form-input"
                            placeholder="${isMobile ? 'Shampoo, Conditioner...' : 'e.g., Moisturizing Shampoo, Deep Conditioner'}"
                        >
                        <div class="form-hint">${isMobile ? 'Separate with commas' : 'Separate multiple products with commas'}</div>
                    </div>

                    <!-- Entry Preview Card - Only show on desktop -->
                    ${!isMobile ? `
                    <div class="entry-preview-card">
                        <div class="preview-header">
                            <span class="preview-icon">👁️</span>
                            <span>Preview</span>
                        </div>
                        <div class="preview-content" id="entryPreview"></div>
                    </div>
                    ` : ''}
                </div>
            `;
        },

        /**
         * Bind event listeners - Fixed for mobile
         */
        bindEvents: function() {
            const self = this;

            // Mobile-specific upload buttons
            if (isMobile) {
                $(document).on('click', '#myavanaPremiumEntryModal .btn-camera', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    // Trigger camera with environment (rear) camera
                    $('#imageInput').attr('capture', 'environment')[0].click();
                });

                $(document).on('click', '#myavanaPremiumEntryModal .btn-gallery', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    // Trigger gallery selection (no camera)
                    $('#imageInput').removeAttr('capture')[0].click();
                });
            }

            // Universal upload zone click handler
            $(document).on('click', '#myavanaPremiumEntryModal #uploadZone', function(e) {
                if ($(e.target).closest('.mobile-upload-buttons').length) return;
                
                e.preventDefault();
                e.stopPropagation();
                console.log('[Mobile Upload] Triggering file input');
                $('#imageInput')[0].click();
            });

            // Only bind drag & drop events for desktop
            if (!isMobile) {
                $(document).on('dragover', '#uploadZone', function(e) {
                    e.preventDefault();
                    $(this).addClass('dragover');
                });

                $(document).on('dragleave', '#uploadZone', function(e) {
                    e.preventDefault();
                    $(this).removeClass('dragover');
                });

                $(document).on('drop', '#uploadZone', function(e) {
                    e.preventDefault();
                    $(this).removeClass('dragover');
                    const files = e.originalEvent.dataTransfer.files;
                    self.handleImageUpload(files);
                });
            }

            // File input change handler
            $(document).on('change', '#imageInput', function(e) {
                if (e.target.files && e.target.files.length > 0) {
                    console.log('[Mobile Upload] Files selected:', e.target.files.length);
                    self.handleImageUpload(e.target.files);
                }
            });

            // Star rating - improved for mobile touch
            $(document).on('click touchstart', '.star', function(e) {
                e.preventDefault();
                const rating = $(this).data('rating');
                self.setRating(rating);
            });

            // Mood selector - improved for mobile touch
            $(document).on('click touchstart', '.mood-option', function(e) {
                e.preventDefault();
                $('.mood-option').removeClass('selected');
                $(this).addClass('selected');
                self.formData.mood = $(this).data('mood');
            });

            // Character count for description
            $(document).on('input', '#entryDescription', function() {
                const count = $(this).val().length;
                $('#charCount').text(count);
                if (!isMobile) self.updatePreview();
            });

            // Real-time title validation
            $(document).on('input', '#entryTitle', function() {
                self.validateTitle($(this).val());
                if (!isMobile) self.updatePreview();
            });

            // Add More button for images
            $(document).on('click', '.btn-add-more', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $('#imageInput')[0].click();
            });

            // Date input enhancement for mobile
            if (isMobile) {
                $(document).on('focus', '#entryDate', function() {
                    $(this).addClass('focused');
                }).on('blur', '#entryDate', function() {
                    $(this).removeClass('focused');
                });
            }
        },

        /**
         * Handle image upload - Mobile optimized
         */
        handleImageUpload: function(files) {
            if (!files || files.length === 0) return;

            // On mobile, limit to 4 photos for better UX
            if (isMobile && files.length > 4) {
                this.showNotification('Please select up to 4 photos', 'warning');
                files = Array.from(files).slice(0, 4);
            }

            let uploadedCount = 0;
            
            Array.from(files).forEach(file => {
                if (!file.type.startsWith('image/')) {
                    this.showNotification('Please upload only image files', 'error');
                    return;
                }

                if (file.size > 10 * 1024 * 1024) {
                    this.showNotification('Image must be less than 10MB', 'error');
                    return;
                }

                const reader = new FileReader();
                reader.onload = (e) => {
                    this.uploadedImages.push({
                        file: file,
                        dataUrl: e.target.result,
                        name: file.name
                    });
                    uploadedCount++;
                    
                    // Render gallery after all images are processed
                    if (uploadedCount === Math.min(files.length, isMobile ? 4 : files.length)) {
                        this.renderImageGallery();
                        if (isMobile && this.uploadedImages.length > 0) {
                            this.showNotification(`${this.uploadedImages.length} photo${this.uploadedImages.length > 1 ? 's' : ''} added`, 'success');
                        }
                    }
                };
                reader.onerror = () => {
                    this.showNotification('Failed to read image file', 'error');
                };
                reader.readAsDataURL(file);
            });
        },

        // ... (rest of the methods remain the same, but use isMobile where needed)

        /**
         * Update UI for current step
         */
        updateUI: function() {
            // Update steps
            $('.form-step').removeClass('active');
            $(`.form-step[data-step="${this.currentStep}"]`).addClass('active');

            // Update progress - re-render for mobile optimization
            $('.premium-progress-bar').html(this.renderProgressSteps());

            // Update buttons
            if (this.currentStep === 1) {
                $('#btnPrevious').hide();
                $('#btnNext').show();
                $('#btnSave').hide();
            } else if (this.currentStep === this.totalSteps) {
                $('#btnPrevious').show();
                $('#btnNext').hide();
                $('#btnSave').show();
                if (!isMobile) this.updatePreview();
            } else {
                $('#btnPrevious').show();
                $('#btnNext').show();
                $('#btnSave').hide();
            }

            // On mobile, ensure proper viewport handling
            if (isMobile) {
                setTimeout(() => {
                    const activeStep = $(`.form-step.active`);
                    if (activeStep.length) {
                        activeStep[0].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    }
                }, 100);
            }
        },

        /**
         * Close modal - Mobile optimized
         */
        close: function() {
            $('#myavanaPremiumEntryModal').removeClass('show');
            setTimeout(() => {
                $('#myavanaPremiumEntryModal').remove();
                $('body').removeClass('modal-open');
                // Reset viewport on mobile
                if (isMobile) {
                    $('html').css('overflow', '');
                    $('body').css('overflow', '');
                }
            }, 300);
        }
    };

    // The rest of your methods remain the same...

    // Expose to global scope
    window.createPremiumEntry = function() {
        MyavanaPremiumEntryForm.open();
    };

})(jQuery);