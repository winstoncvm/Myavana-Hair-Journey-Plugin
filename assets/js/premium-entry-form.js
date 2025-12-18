/**
 * MYAVANA Premium 3-Step Entry Form
 * Luxury UI with guided workflow, validation, and animations
 * @version 1.0.0
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
         * @param {Object} prefillData - Optional data to prefill the form
         */
        open: function(prefillData) {
            console.log('[Premium Entry Form] Opening with prefill data:', prefillData);
            this.currentStep = 1;
            this.formData = prefillData || {};
            this.uploadedImages = [];
            console.log('[Premium Entry Form] Form data initialized:', this.formData);
            this.createModal();
        },

        /**
         * Create the premium modal structure
         */
        createModal: function() {
            // Remove existing modal if present
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

            console.log('[Premium Entry Form] Setting DOM values from formData:', this.formData);

            // Set form values after DOM is ready (use specific modal selectors)
            if (this.formData.title) {
                $('#myavanaPremiumEntryModal #entryTitle').val(this.formData.title);
                console.log('[Premium Entry Form] Set title to:', this.formData.title);
                console.log('[Premium Entry Form] Title input value now:', $('#myavanaPremiumEntryModal #entryTitle').val());
            }
            if (this.formData.date) {
                $('#myavanaPremiumEntryModal #entryDate').val(this.formData.date);
            }
            if (this.formData.description) {
                $('#myavanaPremiumEntryModal #entryDescription').val(this.formData.description);
                $('#myavanaPremiumEntryModal #charCount').text(this.formData.description.length);
            }
            if (this.formData.products) {
                $('#myavanaPremiumEntryModal #productsUsed').val(this.formData.products);
            }

            // Animate in
            setTimeout(() => {
                $('#myavanaPremiumEntryModal').addClass('show');
            }, 50);

            this.bindEvents();
            this.applyPrefillData();
        },

        /**
         * Apply prefill data to form fields
         */
        applyPrefillData: function() {
            // Step 1: Trigger validation if title exists
            if (this.formData.title && this.currentStep === 1) {
                setTimeout(() => {
                    const titleInput = $('#myavanaPremiumEntryModal #entryTitle');
                    if (titleInput.length && titleInput.val()) {
                        this.validateTitle(titleInput.val());
                    }
                }, 100);
            }

            // Set rating if provided (when step 2 is rendered)
            if (this.formData.rating && this.currentStep === 2) {
                setTimeout(() => {
                    this.setRating(parseInt(this.formData.rating));
                }, 100);
            }

            // Set mood if provided (when step 2 is rendered)
            if (this.formData.mood && this.currentStep === 2) {
                setTimeout(() => {
                    $('#myavanaPremiumEntryModal .mood-option[data-mood="' + this.formData.mood + '"]').addClass('selected');
                }, 100);
            }
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
         * Bind event listeners
         */
        bindEvents: function() {
            const self = this;
            const $fileInput = $('#globalImageInput'); // Persistent reference

            // Mobile-specific upload buttons
            if (isMobile) {
                $(document).off('click', '#myavanaPremiumEntryModal .btn-camera').on('click', '#myavanaPremiumEntryModal .btn-camera', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    $fileInput.attr('capture', 'environment'); // Rear camera
                    $fileInput[0].click(); // Native click!
                });

                $(document).off('click', '#myavanaPremiumEntryModal .btn-gallery').on('click', '#myavanaPremiumEntryModal .btn-gallery', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    $fileInput.removeAttr('capture'); // Gallery only
                    $fileInput[0].click();
                });
            }
            // Universal upload zone click handler
            $(document).off('click', '#myavanaPremiumEntryModal #uploadZone').on('click', '#myavanaPremiumEntryModal #uploadZone', function(e) {
                if ($(e.target).closest('.mobile-upload-buttons').length) return;
                e.preventDefault();
                e.stopPropagation();
                $fileInput.removeAttr('capture'); // Desktop = normal picker
                $fileInput[0].click();
            });

            // Image upload zone - use direct binding to avoid conflicts
            // $(document).on('click', '#uploadZone', function(e) {
            //     e.preventDefault();
            //     e.stopPropagation();
            //     $('#imageInput')[0].click(); // Use native click to avoid jQuery event bubbling
            // });

            // $(document).on('dragover', '#uploadZone', function(e) {
            //     e.preventDefault();
            //     $(this).addClass('dragover');
            // });

            // $(document).on('dragleave', '#uploadZone', function(e) {
            //     e.preventDefault();
            //     $(this).removeClass('dragover');
            // });

            // $(document).on('drop', '#uploadZone', function(e) {
            //     e.preventDefault();
            //     $(this).removeClass('dragover');
            //     const files = e.originalEvent.dataTransfer.files;
            //     self.handleImageUpload(files);
            // });
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
            // $(document).on('change', '#imageInput', function(e) {
            //     self.handleImageUpload(e.target.files);
            // });
             // File input change handler
             // === FILE CHANGE HANDLER (Critical!) ===
            $fileInput.off('change').on('change', function(e) {
                if (this.files && this.files.length > 0) {
                    console.log('Files selected:', this.files.length);
                    self.handleImageUpload(this.files);
                    this.value = ''; // Reset so same file can be re-selected
                }
            });


            // Star rating
            // $(document).on('click', '.star', function() {
            //     const rating = $(this).data('rating');
            //     self.setRating(rating);
            // });

            // // Mood selector
            // $(document).on('click', '.mood-option', function() {
            //     $('.mood-option').removeClass('selected');
            //     $(this).addClass('selected');
            //     self.formData.mood = $(this).data('mood');
            // });
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

            // Character count for description (scoped to premium modal)
            $(document).on('input', '#myavanaPremiumEntryModal #entryDescription', function() {
                const count = $(this).val().length;
                $('#myavanaPremiumEntryModal #charCount').text(count);
                self.updatePreview();
            });

            // Real-time title validation (scoped to premium modal)
            $(document).on('input', '#myavanaPremiumEntryModal #entryTitle', function() {
                self.validateTitle($(this).val());
                self.updatePreview();
            });

            // Add More button for images
            $(document).off('click', '#myavanaPremiumEntryModal .btn-add-more').on('click', '#myavanaPremiumEntryModal .btn-add-more', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $fileInput.removeAttr('capture');
                $fileInput[0].click();
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
         * Handle image upload
         */
        // handleImageUpload: function(files) {
        //     if (!files || files.length === 0) return;

        //     Array.from(files).forEach(file => {
        //         if (!file.type.startsWith('image/')) {
        //             this.showNotification('Please upload only image files', 'error');
        //             return;
        //         }

        //         if (file.size > 10 * 1024 * 1024) {
        //             this.showNotification('Image must be less than 10MB', 'error');
        //             return;
        //         }

        //         const reader = new FileReader();
        //         reader.onload = (e) => {
        //             this.uploadedImages.push({
        //                 file: file,
        //                 dataUrl: e.target.result,
        //                 name: file.name
        //             });
        //             this.renderImageGallery();
        //         };
        //         reader.readAsDataURL(file);
        //     });
        // },

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
                    // if (uploadedCount === Math.min(files.length, isMobile ? 4 : files.length)) {
                    //     this.renderImageGallery();
                    //     if (isMobile && this.uploadedImages.length > 0) {
                    //         this.showNotification(`${this.uploadedImages.length} photo${this.uploadedImages.length > 1 ? 's' : ''} added`, 'success');
                    //     }
                    // }
                    this.renderImageGallery();
                };
                reader.onerror = () => {
                    this.showNotification('Failed to read image file', 'error');
                };
                reader.readAsDataURL(file);
            });
        },
        /**
         * Render image gallery
         */
        renderImageGallery: function() {
            const $modal = $('#myavanaPremiumEntryModal');
        
            if (this.uploadedImages.length === 0) {
                $modal.find('#imageGallery').hide();
                $modal.find('#uploadZone').show();
                return;
            }
        
            $modal.find('#uploadZone').hide();
            $modal.find('#imageGallery').show();
        
            const galleryHTML = this.uploadedImages.map((img, index) => `
                <div class="gallery-item ${index === this.featuredImageIndex ? 'featured' : ''}">
                    <img src="${img.dataUrl}" alt="${img.name}">
                    <button type="button" class="btn-remove" onclick="MyavanaPremiumEntryForm.removeImage(${index})">
                        <svg viewBox="0 0 24 24" width="16" height="16">
                            <path fill="currentColor" d="M19,6.41L17.59,5L12,10.59L6.41,5L5,6.41L10.59,12L5,17.59L6.41,19L12,13.41L17.59,19L19,17.59L13.41,12L19,6.41Z"/>
                        </svg>
                    </button>
                    ${index === this.featuredImageIndex ? '<span class="featured-badge">Featured</span>' : ''}
                    <button type="button" class="btn-set-featured" onclick="MyavanaPremiumEntryForm.setFeaturedImage(${index})">
                        Set as Featured
                    </button>
                </div>
            `).join('');
        
            $modal.find('#galleryGrid').html(galleryHTML);
        },

        /**
         * Remove image from gallery
         */
        removeImage: function(index) {
            this.uploadedImages.splice(index, 1);
            if (this.featuredImageIndex === index) {
                this.featuredImageIndex = 0;
            } else if (this.featuredImageIndex > index) {
                this.featuredImageIndex--;
            }
            this.renderImageGallery();
        },

        /**
         * Set featured image
         */
        setFeaturedImage: function(index) {
            this.featuredImageIndex = index;
            this.renderImageGallery();
        },

        /**
         * Set star rating
         */
        setRating: function(rating) {
            this.formData.rating = rating;

            // Update stars visual
            $('.star').each(function(index) {
                if (index < rating) {
                    $(this).addClass('selected');
                } else {
                    $(this).removeClass('selected');
                }
            });

            // Update label
            const labels = ['Poor', 'Fair', 'Good', 'Great', 'Excellent'];
            $('#ratingLabel').text(labels[rating - 1]);
        },

        /**
         * Validate title
         */
        validateTitle: function(title) {
            const feedback = $('#titleFeedback');

            if (!title || title.trim().length === 0) {
                feedback.html('<span class="error">Title is required</span>').show();
                return false;
            }

            if (title.length < 3) {
                feedback.html('<span class="warning">Title should be at least 3 characters</span>').show();
                return false;
            }

            feedback.html('<span class="success">✓ Looks good!</span>').show();
            return true;
        },

        /**
         * Validate current step
         */
        validateStep: function(step) {
            if (step === 1) {
                // Use specific selector for premium modal to avoid conflicts
                const titleInput = $('#myavanaPremiumEntryModal #entryTitle');
                const dateInput = $('#myavanaPremiumEntryModal #entryDate');

                console.log('[Premium Entry Form] Validating Step 1');
                console.log('  - Title input exists:', titleInput.length);
                console.log('  - Title input element:', titleInput[0]);

                const titleVal = titleInput.val();
                const formDataTitle = this.formData.title;
                const title = titleVal || formDataTitle || '';

                console.log('  - DOM value:', titleVal);
                console.log('  - formData.title:', formDataTitle);
                console.log('  - Final title:', title);

                if (!title || title.trim().length === 0) {
                    this.showNotification('Please enter a title', 'error');
                    return false;
                }
                if (title.length < 3) {
                    this.showNotification('Title should be at least 3 characters', 'error');
                    return false;
                }
                this.formData.title = title;
                this.formData.date = dateInput.val();
                console.log('  - Validation passed! Stored title:', this.formData.title);
                return true;
            }

            if (step === 2) {
                if (!this.formData.rating) {
                    this.showNotification('Please rate your hair health', 'error');
                    return false;
                }
                return true;
            }

            if (step === 3) {
                const description = $('#myavanaPremiumEntryModal #entryDescription').val();
                if (!description || description.trim().length === 0) {
                    this.showNotification('Please add a description', 'error');
                    return false;
                }
                this.formData.description = description;
                this.formData.products = $('#myavanaPremiumEntryModal #productsUsed').val();
                return true;
            }

            return true;
        },

        /**
         * Navigate to next step
         */
        nextStep: function() {
            if (!this.validateStep(this.currentStep)) {
                return;
            }

            this.currentStep++;
            this.updateUI();
            this.applyPrefillData(); // Apply prefills when navigating to new step
        },

        /**
         * Navigate to previous step
         */
        previousStep: function() {
            if (this.currentStep > 1) {
                this.currentStep--;
                this.updateUI();
            }
        },

        /**
         * Update UI for current step
         */
        // updateUI: function() {
        //     // Update steps
        //     $('.form-step').removeClass('active');
        //     $(`.form-step[data-step="${this.currentStep}"]`).addClass('active');

        //     // Update progress
        //     $('.progress-step').each((i, el) => {
        //         const stepNum = $(el).data('step');
        //         $(el).removeClass('active completed');
        //         if (stepNum < this.currentStep) {
        //             $(el).addClass('completed');
        //         } else if (stepNum === this.currentStep) {
        //             $(el).addClass('active');
        //         }
        //     });

        //     $('.progress-line').each((i, el) => {
        //         if (i < this.currentStep - 1) {
        //             $(el).addClass('active');
        //         } else {
        //             $(el).removeClass('active');
        //         }
        //     });

        //     // Update buttons
        //     if (this.currentStep === 1) {
        //         $('#btnPrevious').hide();
        //         $('#btnNext').show();
        //         $('#btnSave').hide();
        //     } else if (this.currentStep === this.totalSteps) {
        //         $('#btnPrevious').show();
        //         $('#btnNext').hide();
        //         $('#btnSave').show();
        //         this.updatePreview();
        //     } else {
        //         $('#btnPrevious').show();
        //         $('#btnNext').show();
        //         $('#btnSave').hide();
        //     }
        // },

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
         * Update entry preview
         */
        updatePreview: function() {
            const previewHTML = `
                <div class="preview-image">
                    ${this.uploadedImages.length > 0 ?
                        `<img src="${this.uploadedImages[this.featuredImageIndex].dataUrl}" alt="Preview">` :
                        '<div class="no-image">No image</div>'}
                </div>
                <div class="preview-details">
                    <h4>${$('#entryTitle').val() || 'Untitled Entry'}</h4>
                    <p class="preview-date">${$('#entryDate').val() || 'No date'}</p>
                    <p class="preview-description">${$('#entryDescription').val() || 'No description'}</p>
                    ${this.formData.rating ? `<div class="preview-rating">${'⭐'.repeat(this.formData.rating)}</div>` : ''}
                    ${this.formData.mood ? `<div class="preview-mood">Mood: ${this.formData.mood}</div>` : ''}
                </div>
            `;
            $('#entryPreview').html(previewHTML);
        },

       
        /**
         * Save entry
         */
        saveEntry: function() {
            if (!this.validateStep(3)) {
                return;
            }

            this.showNotification('Saving your entry...', 'info');  

            // Prepare form data for submission
            const formData = new FormData();
            formData.append('action', 'myavana_add_entry');
            formData.append('title', this.formData.title);
            formData.append('entry_date', this.formData.date);
            formData.append('description', this.formData.description);
            formData.append('rating', this.formData.rating || 3);
            formData.append('mood_demeanor', this.formData.mood || '');
            formData.append('products', this.formData.products || '');

            // Add security nonce
            const settings = window.myavanaTimelineSettings || {};
            formData.append('security', settings.addEntryNonce || settings.nonce);

            // Add images
            this.uploadedImages.forEach((img, index) => {
                formData.append(`entry_photos[${index}]`, img.file);
            });

            // Mark which image should be the featured/thumbnail
            // If no featured image is set (index is -1), default to 0 (first image)
            const featuredIndex = this.featuredImageIndex >= 0 ? this.featuredImageIndex : 0;
            formData.append('featured_image_index', featuredIndex);

            // Submit via AJAX
            $.ajax({
                url: settings.ajaxUrl || '/wp-admin/admin-ajax.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (response) => {
                    if (response.success) {
                        this.showNotification('Entry saved successfully! 🎉', 'success');
                        setTimeout(() => {
                            this.close();
                            location.reload();
                        }, 1500);
                    } else {
                        this.showNotification(response.data || 'Failed to save entry', 'error');
                    }
                },
                error: () => {
                    this.showNotification('Network error. Please try again.', 'error');
                }
            });
        },

        /**
         * Show notification
         */
        showNotification: function(message, type = 'info') {
            // Remove existing notification
            $('.premium-notification').remove();

            const icons = {
                success: '✓',
                error: '✗',
                warning: '⚠',
                info: 'ℹ'
            };

            const notification = $(`
                <div class="premium-notification ${type}">
                    <span class="notification-icon">${icons[type]}</span>
                    <span class="notification-message">${message}</span>
                </div>
            `);

            $('body').append(notification);

            setTimeout(() => notification.addClass('show'), 100);

            if (type === 'success' || type === 'info') {
                setTimeout(() => {
                    notification.removeClass('show');
                    setTimeout(() => notification.remove(), 300);
                }, 3000);
            }
        },

        /**
         * Close modal
         */
        // close: function() {
        //     $('#myavanaPremiumEntryModal').removeClass('show');
        //     setTimeout(() => {
        //         $('#myavanaPremiumEntryModal').remove();
        //         $('body').removeClass('modal-open');
        //     }, 300);
        // }
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
    
    // Ensure global file input exists
    $(function() {
        if (!$('#globalImageInput').length) {
            $('body').append('<input type="file" id="globalImageInput" accept="image/*" multiple style="display:none;">');
        }
    });

    // Expose to global scope
    window.createPremiumEntry = function() {
        MyavanaPremiumEntryForm.open();
    };

})(jQuery);

