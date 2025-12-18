/**
 * MYAVANA Hair Journey Timeline - Forms Module
 * Handles all form creation, editing, and submission functionality
 *
 * @namespace MyavanaTimeline.Forms
 * @version 1.0.0
 */

(function(window, $) {
    'use strict';

    // Namespace check
    if (typeof window.MyavanaTimeline === 'undefined') {
        console.error('MyavanaTimeline core not loaded. Forms module requires timeline-core.js');
        return;
    }

    /**
     * Forms Module
     */
    const Forms = {
        /**
         * Initialize forms module
         */
        init: function() {
            console.log('Initializing Timeline Forms module...');

            this.initCreateForms();

            console.log('Timeline Forms module initialized');
        },

        /**
         * Initialize all create/edit forms
         */
        initCreateForms: function() {
            console.log('Initializing create/edit forms...');

            // Initialize FilePond for entry photos
            this.initFilePond();

            // Initialize rating stars
            this.initRatingStars();

            // Initialize form submissions
            this.initFormSubmissions();

            // Initialize character counters
            this.initCharacterCounters();

            // Initialize goal form specific elements
            this.initGoalFormElements();

            // Initialize product selector
            this.initProductSelector();

            // Set default dates
            const today = new Date().toISOString().split('T')[0];
            const entryDateInput = document.getElementById('entry_date');
            if (entryDateInput && !entryDateInput.value) {
                entryDateInput.value = today;
            }

            console.log('Create/edit forms initialized');
        },

        /**
         * Initialize FilePond for image uploads
         */
        initFilePond: function() {
            const filePondEl = document.getElementById('entry_photos');
            if (!filePondEl || typeof FilePond === 'undefined') {
                console.log('FilePond not available');
                return;
            }

            const entryFilePond = FilePond.create(filePondEl, {
                allowMultiple: true,
                maxFiles: 5,
                maxFileSize: '5MB',
                acceptedFileTypes: ['image/*'],
                labelIdle: 'Drag & Drop photos or <span class="filepond--label-action">Browse</span>',
                imagePreviewHeight: 150,
                imageCropAspectRatio: '1:1',
                imageResizeTargetWidth: 800,
                imageResizeTargetHeight: 800,
                imageResizeMode: 'cover',
                imageResizeUpscale: false,
                stylePanelLayout: 'compact',
                credits: false
            });

            // Store in state
            MyavanaTimeline.State.set('entryFilePond', entryFilePond);

            console.log('FilePond initialized');
        },

        /**
         * Initialize rating stars
         */
        initRatingStars: function() {
            const ratingStars = document.getElementById('health_rating_stars');
            if (!ratingStars) return;

            const stars = ratingStars.querySelectorAll('.rating-star-hjn');
            const ratingInput = document.getElementById('health_rating');
            const ratingValue = document.getElementById('health_rating_value');

            stars.forEach((star, index) => {
                star.addEventListener('click', function(e) {
                    e.preventDefault();
                    const value = parseInt(this.getAttribute('data-value'));

                    // Update hidden input
                    ratingInput.value = value;

                    // Update stars visual
                    stars.forEach((s, i) => {
                        if (i < value) {
                            s.classList.add('active');
                        } else {
                            s.classList.remove('active');
                        }
                    });

                    // Update value display
                    ratingValue.textContent = value + '/5';
                });
            });
        },

        /**
         * Initialize form submissions
         */
        initFormSubmissions: function() {
            // Entry form
            const entryForm = document.getElementById('entryForm');
            if (entryForm) {
                entryForm.addEventListener('submit', this.handleEntrySubmit.bind(this));
            }

            // Goal form
            const goalForm = document.getElementById('goalForm');
            if (goalForm) {
                goalForm.addEventListener('submit', this.handleGoalSubmit.bind(this));
            }

            // Routine form
            const routineForm = document.getElementById('routineForm');
            if (routineForm) {
                routineForm.addEventListener('submit', this.handleRoutineSubmit.bind(this));
            }
        },

        /**
         * Initialize character counters
         */
        initCharacterCounters: function() {
            const entryContent = document.getElementById('entry_content');
            const entryContentCount = document.getElementById('entry_content_count');

            if (entryContent && entryContentCount) {
                entryContent.addEventListener('input', function() {
                    entryContentCount.textContent = this.value.length;
                });
            }
        },

        /**
         * Initialize goal form specific elements
         */
        initGoalFormElements: function() {
            // Progress note character counter
            const progressNoteTextarea = document.getElementById('newProgressNote');
            if (progressNoteTextarea) {
                this.initProgressNoteCounter(progressNoteTextarea);
            }
        },

        /**
         * Initialize progress note counter
         */
        initProgressNoteCounter: function(textarea) {
            const counter = document.getElementById('progress_note_count');
            if (!counter) return;

            function updateCounter() {
                counter.textContent = textarea.value.length;
            }

            textarea.addEventListener('input', updateCounter);
            // Initial count
            updateCounter();
        },

        /**
         * Initialize product selector with Select2
         */
        initProductSelector: function() {
            const productSelect = document.getElementById('products_used');
            if (!productSelect || typeof $ === 'undefined' || !$.fn.select2) {
                console.warn('Product selector or Select2 not available');
                return;
            }

            // Common hair care products
            const products = [
                'Shampoo', 'Conditioner', 'Leave-in Conditioner', 'Hair Oil', 'Hair Serum',
                'Hair Mask', 'Deep Conditioner', 'Hair Butter', 'Hair Cream', 'Hair Lotion',
                'Styling Gel', 'Hair Wax', 'Hair Spray', 'Heat Protectant Spray', 'Hair Mousse',
                'Edge Control', 'Hair Pomade', 'Hair Glue', 'Hair Extensions', 'Hair Weave',
                'Hair Color', 'Hair Bleach', 'Hair Toner', 'Hair Developer', 'Hair Relaxer',
                'Hair Perm Solution', 'Protective Hairstyle Product', 'Scalp Oil', 'Scalp Scrub',
                'Hair Supplements', 'Vitamins for Hair', 'Protein Treatment', 'Hair Growth Oil',
                'Anti-Dandruff Shampoo', 'Moisturizing Shampoo', 'Clarifying Shampoo'
            ];

            // Create options
            products.forEach(product => {
                const option = document.createElement('option');
                option.value = product;
                option.textContent = product;
                productSelect.appendChild(option);
            });

            // Initialize Select2
            $(productSelect).select2({
                placeholder: 'Select products you used...',
                allowClear: true,
                multiple: true,
                tags: true, // Allow custom entries
                tokenSeparators: [',', ';'],
                createTag: function (params) {
                    return {
                        id: params.term,
                        text: params.term,
                        newOption: true
                    };
                }
            });
        },

        /**
         * Open offcanvas for creating/editing
         */
        openOffcanvas: function(type, id = null) {
            console.log('Opening offcanvas:', type, id);

            const overlay = document.getElementById('createOffcanvasOverlay');
            let offcanvas;

            switch(type) {
                case 'entry':
                    offcanvas = document.getElementById('entryOffcanvas');
                    if (id) {
                        this.loadEntryForEdit(id);
                    } else {
                        this.resetEntryForm();
                    }
                    break;
                case 'goal':
                    offcanvas = document.getElementById('goalOffcanvas');
                    if (id) {
                        this.loadGoalForEdit(id);
                    } else {
                        this.resetGoalForm();
                    }
                    break;
                case 'routine':
                    offcanvas = document.getElementById('routineOffcanvas');
                    if (id) {
                        this.loadRoutineForEdit(id);
                    } else {
                        this.resetRoutineForm();
                    }
                    break;
                default:
                    console.error('Unknown offcanvas type:', type);
                    return;
            }

            if (offcanvas && overlay) {
                // Track the currently open create/edit offcanvas
                MyavanaTimeline.State.set('currentOffcanvas', offcanvas);
                overlay.classList.add('active');
                offcanvas.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
        },

        /**
         * Reset entry form
         */
        resetEntryForm: function() {
            const form = document.getElementById('entryForm');
            if (!form) return;

            form.reset();
            document.getElementById('entry_id').value = '';
            document.getElementById('entryOffcanvasTitle').textContent = 'Add Hair Journey Entry';

            // Reset date to today
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('entry_date').value = today;

            // Reset rating stars
            const stars = document.querySelectorAll('.rating-star-hjn');
            stars.forEach(star => star.classList.remove('active'));
            document.getElementById('health_rating').value = '0';
            document.getElementById('health_rating_value').textContent = 'Not Rated';

            // Clear FilePond
            const entryFilePond = MyavanaTimeline.State.get('entryFilePond');
            if (entryFilePond) {
                entryFilePond.removeFiles();
            }

            // Hide existing images gallery
            const existingGallery = document.getElementById('existingImagesGallery');
            if (existingGallery) {
                existingGallery.style.display = 'none';
            }
        },

        /**
         * Reset goal form
         */
        resetGoalForm: function() {
            const form = document.getElementById('goalForm');
            if (!form) return;

            form.reset();
            document.getElementById('goal_id').value = '';
            document.getElementById('goalOffcanvasTitle').textContent = 'Create Hair Goal';

            // Reset progress
            document.getElementById('goal_progress').value = '0';
            document.getElementById('goal_progress_value').textContent = '0%';

            // Clear milestones
            document.getElementById('milestones_list').innerHTML = '';
        },

        /**
         * Reset routine form
         */
        resetRoutineForm: function() {
            const form = document.getElementById('routineForm');
            if (!form) return;

            form.reset();
            document.getElementById('routine_id').value = '';
            document.getElementById('routineOffcanvasTitle').textContent = 'Create Hair Routine';

            // Reset steps to just one
            const stepsList = document.getElementById('routine_steps_list');
            stepsList.innerHTML = `
                <div class="routine-step-item-hjn" data-step="1">
                    <div class="step-number-hjn">1</div>
                    <input type="text" name="routine_steps[]" class="form-input-hjn step-input-hjn" placeholder="Describe this step..." required>
                    <button type="button" class="btn-remove-step-hjn" onclick="MyavanaTimeline.Forms.removeRoutineStep(this)" disabled>
                        <svg viewBox="0 0 24 24" width="18" height="18">
                            <path fill="currentColor" d="M19,6.41L17.59,5L12,10.59L6.41,5L5,6.41L10.59,12L5,17.59L6.41,19L12,13.41L17.59,19L19,17.59L13.41,12L19,6.41Z"/>
                        </svg>
                    </button>
                </div>
            `;
        },

        /**
         * Load entry for editing
         */
        loadEntryForEdit: function(id) {
            console.log('Loading entry for edit:', id);

            // Update offcanvas title
            const titleEl = document.getElementById('entryOffcanvasTitle');
            if (titleEl) titleEl.textContent = 'Edit Hair Journey Entry';

            // Show loading state
            const submitBtn = document.getElementById('saveEntryBtn');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Loading...';
            }

            // Resolve settings from possible global objects
            const settings = window.myavanaTimelineSettings || window.myavanaTimeline || {};

            // Fetch entry data via AJAX
            fetch(settings.ajaxUrl || settings.ajaxurl || '/wp-admin/admin-ajax.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'myavana_get_entry_details',
                    security: settings.getEntryDetailsNonce || settings.getEntriesNonce || settings.nonce || '',
                    entry_id: id
                })
            })
            .then(response => response.json())
            .then(data => {
                console.log('[loadEntryForEdit] AJAX response:', data);
                if (data.success && data.data) {
                    console.log('[loadEntryForEdit] Calling populateEntryForm with:', data.data);
                    this.populateEntryForm(data.data);
                } else {
                    console.error('[loadEntryForEdit] Failed to load entry:', data);
                    this.showNotification('Failed to load entry data', 'error');
                }
            })
            .catch(error => {
                console.error('Error loading entry:', error);
                this.showNotification('Network error loading entry', 'error');
            })
            .finally(() => {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Save Entry';
                }
            });
        },

        /**
         * Load goal for editing
         */
        loadGoalForEdit: function(id) {
            console.log('Loading goal for edit:', id);

            // Update offcanvas title
            const titleEl = document.getElementById('goalOffcanvasTitle');
            if (titleEl) titleEl.textContent = 'Edit Hair Goal';

            // For now, extract from sidebar since we don't have a separate AJAX endpoint
            const calendarDataEl = document.getElementById('calendarDataHjn');
            if (calendarDataEl) {
                try {
                    const calendarData = JSON.parse(calendarDataEl.textContent);
                    const goal = calendarData.goals?.find(g => g.id == id);

                    if (goal) {
                        this.populateGoalForm(goal);
                        return;
                    }
                } catch (error) {
                    console.error('Error parsing calendar data:', error);
                }
            }

            console.warn('Goal not found in calendar data');
            this.showNotification('Goal data not available for editing', 'warning');
        },

        /**
         * Load routine for editing
         */
        loadRoutineForEdit: function(id) {
            console.log('Loading routine for edit:', id);

            // Update offcanvas title
            const titleEl = document.getElementById('routineOffcanvasTitle');
            if (titleEl) titleEl.textContent = 'Edit Hair Routine';

            // Extract from calendar data
            const calendarDataEl = document.getElementById('calendarDataHjn');
            if (calendarDataEl) {
                try {
                    const calendarData = JSON.parse(calendarDataEl.textContent);
                    const routine = calendarData.routines?.find(r => r.id == id);

                    if (routine) {
                        this.populateRoutineForm(routine);
                        return;
                    }
                } catch (error) {
                    console.error('Error parsing calendar data:', error);
                }
            }

            console.warn('Routine not found in calendar data');
            this.showNotification('Routine data not available for editing', 'warning');
        },

        /**
         * Populate entry form with data for editing
         */
        populateEntryForm: function(entry) {
            console.log('=== Populating Entry Form ===');
            console.log('Entry data:', entry);

            // Handle nested data structure - if entry has a 'data' property, use that
            const entryData = entry.data || entry;
            console.log('Actual entry data to use:', entryData);

            // Set hidden entry ID
            const entryIdInput = document.getElementById('entry_id');
            if (entryIdInput) {
                entryIdInput.value = entryData.id || entry.id || '';
                console.log('✓ Set entry_id to:', entryIdInput.value);
            } else {
                console.error('✗ entry_id input not found');
            }

            // Set title
            const titleInput = document.getElementById('entry_title');
            if (titleInput) {
                titleInput.value = entryData.title || '';
                console.log('✓ Set entry_title to:', titleInput.value);
            } else {
                console.error('✗ entry_title input not found');
            }

            // Set date - handle formatted date from view vs raw date from AJAX
            const dateInput = document.getElementById('entry_date');
            if (dateInput) {
                let dateValue = entryData.entry_date || entryData.date || '';

                // If date is formatted like "October 17, 2025 10:22 AM", convert to YYYY-MM-DD
                if (dateValue && dateValue.includes(',')) {
                    const parsedDate = new Date(dateValue);
                    if (!isNaN(parsedDate.getTime())) {
                        dateValue = parsedDate.toISOString().split('T')[0];
                    }
                }

                dateInput.value = dateValue;
                console.log('✓ Set entry_date to:', dateInput.value);
            } else {
                console.error('✗ entry_date input not found');
            }

            // Set content
            const contentInput = document.getElementById('entry_content');
            if (contentInput) {
                contentInput.value = entryData.content || entryData.description || '';
                console.log('✓ Set entry_content to:', contentInput.value.substring(0, 50) + '...');
                // Update character count
                const charCount = document.getElementById('entry_content_count');
                if (charCount) charCount.textContent = contentInput.value.length;
            } else {
                console.error('✗ entry_content textarea not found');
            }

            // Set rating
            if (entryData.rating) {
                const ratingInput = document.getElementById('health_rating');
                const ratingValue = document.getElementById('health_rating_value');
                const stars = document.querySelectorAll('#health_rating_stars .rating-star-hjn');

                if (ratingInput) ratingInput.value = entryData.rating;
                if (ratingValue) ratingValue.textContent = entryData.rating + '/5';

                stars.forEach((star, index) => {
                    if (index < entryData.rating) {
                        star.classList.add('active');
                    } else {
                        star.classList.remove('active');
                    }
                });
                console.log('✓ Set rating to:', entryData.rating);
            }

            // Set mood (field ID is 'mood', not 'entry_mood')
            const moodInput = document.getElementById('mood');
            if (moodInput) {
                moodInput.value = entryData.mood || entryData.mood_demeanor || '';
                console.log('✓ Set mood to:', moodInput.value);
            } else {
                console.warn('✗ Mood input field not found');
            }

            // Set products (now a multi-select dropdown)
            const productsSelect = document.getElementById('products_used');
            if (productsSelect && typeof $ !== 'undefined' && $.fn.select2) {
                let products = [];
                if (Array.isArray(entryData.products)) {
                    products = entryData.products;
                } else if (typeof entryData.products === 'string' && entryData.products.trim()) {
                    products = entryData.products.split(',').map(p => p.trim()).filter(p => p);
                }

                // Set selected values
                $(productsSelect).val(products).trigger('change');
                console.log('✓ Set products to:', products);
            } else {
                console.warn('✗ Products select field not found or Select2 not available');
            }

            // Set notes
            const notesInput = document.getElementById('notes');
            if (notesInput && entryData.notes) {
                notesInput.value = entryData.notes;
                console.log('✓ Set notes to:', entryData.notes);
            }

            // Handle existing images gallery
            this.populateExistingImages(entryData);

            console.log('=== Entry form population complete ===');
        },

        /**
         * Populate existing images gallery for edit mode
         */
        populateExistingImages: function(entryData) {
            console.log('[populateExistingImages] Entry data received:', entryData);

            const galleryEl = document.getElementById('existingImagesGallery');
            const gridEl = document.getElementById('existingImagesGrid');

            if (!galleryEl || !gridEl) {
                console.warn('[populateExistingImages] Gallery elements not found');
                return;
            }

            // Clear existing content
            gridEl.innerHTML = '';

            // Get images from entry data
            let images = [];
            if (entryData.images && Array.isArray(entryData.images)) {
                images = entryData.images;
                console.log('[populateExistingImages] Using images array:', images);
            } else if (entryData.image) {
                images = [entryData.image];
                console.log('[populateExistingImages] Using single image:', entryData.image);
            } else if (entryData.thumbnail || entryData.image_url) {
                images = [entryData.thumbnail || entryData.image_url];
                console.log('[populateExistingImages] Using thumbnail/image_url:', images);
            }

            console.log('[populateExistingImages] Final images array:', images);

            if (images.length === 0) {
                console.log('[populateExistingImages] No images found, hiding gallery');
                galleryEl.style.display = 'none';
                return;
            }

            // Show gallery and populate with images
            galleryEl.style.display = 'block';

            images.forEach((imageUrl, index) => {
                if (!imageUrl) return;

                const imageItem = document.createElement('div');
                imageItem.className = 'existing-image-item-hjn';
                imageItem.setAttribute('data-image-url', imageUrl);
                imageItem.setAttribute('data-index', index);

                imageItem.innerHTML = `
                    <div class="existing-image-wrapper-hjn">
                        <img src="${imageUrl}" alt="Entry image ${index + 1}" class="existing-image-hjn" />
                        <button type="button" class="remove-existing-image-btn" onclick="MyavanaTimeline.Forms.removeExistingImage('${imageUrl}', ${index})" title="Remove this image">
                            <svg viewBox="0 0 24 24" width="16" height="16">
                                <path fill="currentColor" d="M19,6.41L17.59,5L12,10.59L6.41,5L5,6.41L10.59,12L5,17.59L6.41,19L12,13.41L17.59,19L19,17.59L13.41,12L19,6.41Z"/>
                            </svg>
                        </button>
                    </div>
                `;

                gridEl.appendChild(imageItem);
            });

            console.log('✓ Populated existing images gallery with', images.length, 'images');
        },

        /**
         * Remove existing image from gallery
         */
        removeExistingImage: function(imageUrl, index) {
            const imageItem = document.querySelector(`.existing-image-item-hjn[data-image-url="${imageUrl}"]`);
            if (imageItem) {
                imageItem.remove();
                console.log('Removed existing image:', imageUrl);

                // Hide gallery if no images left
                const remainingImages = document.querySelectorAll('.existing-image-item-hjn');
                if (remainingImages.length === 0) {
                    document.getElementById('existingImagesGallery').style.display = 'none';
                }
            }
        },

        /**
         * Populate goal form with data for editing
         */
        populateGoalForm: function(goal) {
            console.log('Populating goal form:', goal);

            // Set hidden goal ID
            const goalIdInput = document.getElementById('goal_id');
            if (goalIdInput) goalIdInput.value = goal.id || '';

            // Set title
            const titleInput = document.getElementById('goal_title');
            if (titleInput) titleInput.value = goal.title || '';

            // Set description
            const descInput = document.getElementById('goal_description');
            if (descInput) descInput.value = goal.description || '';

            // Set start date
            const startDateInput = document.getElementById('goal_start_date');
            if (startDateInput) startDateInput.value = goal.start_date || '';

            // Set end date
            const endDateInput = document.getElementById('goal_end_date');
            if (endDateInput) endDateInput.value = goal.end_date || goal.target_date || '';

            // Set progress
            const progressInput = document.getElementById('goal_progress');
            const progressValue = document.getElementById('goal_progress_value');
            if (progressInput) {
                progressInput.value = goal.progress || 0;
                if (progressValue) progressValue.textContent = (goal.progress || 0) + '%';
            }

            // Set category
            const categoryInput = document.getElementById('goal_category');
            if (categoryInput) categoryInput.value = goal.category || '';

            // Set target
            const targetInput = document.getElementById('goal_target');
            if (targetInput) targetInput.value = goal.target || '';

            // Clear new progress note field
            const newNoteTextarea = document.getElementById('newProgressNote');
            if (newNoteTextarea) {
                newNoteTextarea.value = '';
                const counter = document.getElementById('progress_note_count');
                if (counter) counter.textContent = '0';
            }
        },

        /**
         * Populate routine form with data for editing
         */
        populateRoutineForm: function(routine) {
            console.log('Populating routine form:', routine);

            // Set hidden routine ID
            const routineIdInput = document.getElementById('routine_id');
            if (routineIdInput) routineIdInput.value = routine.id || routine.index || '';

            // Set title (handle different field names)
            const titleInput = document.getElementById('routine_title');
            if (titleInput) titleInput.value = routine.title || routine.name || '';

            // Set type
            const typeInput = document.getElementById('routine_type');
            if (typeInput) typeInput.value = routine.routine_type || routine.type || '';

            // Set frequency
            const frequencyInput = document.getElementById('routine_frequency');
            if (frequencyInput) frequencyInput.value = routine.frequency || 'daily';

            // Set time - handle different formats
            const timeInput = document.getElementById('routine_time');
            if (timeInput) {
                if (routine.time_of_day) {
                    timeInput.value = routine.time_of_day;
                } else if (routine.time) {
                    timeInput.value = routine.time;
                } else if (routine.hour !== undefined) {
                    timeInput.value = `${String(routine.hour).padStart(2, '0')}:00`;
                }
            }

            // Set duration
            const durationInput = document.getElementById('routine_duration');
            if (durationInput) durationInput.value = routine.duration || '';

            // Set products
            const productsInput = document.getElementById('routine_products');
            if (productsInput) {
                if (Array.isArray(routine.products)) {
                    productsInput.value = routine.products.join('\n');
                } else {
                    productsInput.value = routine.products || '';
                }
            }

            // Set notes
            const notesInput = document.getElementById('routine_notes');
            if (notesInput) notesInput.value = routine.notes || '';

            // Set steps - handle different data structures
            if (routine.steps || routine.description) {
                const stepsList = document.getElementById('routine_steps_list');
                if (stepsList) {
                    let steps = [];

                    if (routine.steps) {
                        steps = routine.steps;
                        if (typeof steps === 'string') {
                            try {
                                steps = JSON.parse(steps);
                            } catch (e) {
                                steps = steps.split(',').map(s => s.trim());
                            }
                        }
                    } else if (routine.description) {
                        // If no steps array, split description by newlines
                        steps = routine.description.split('\n').filter(step => step.trim());
                    }

                    // Clear existing steps
                    stepsList.innerHTML = '';

                    // Ensure at least one step input
                    if (steps.length === 0) {
                        steps = [''];
                    }

                    // Add each step
                    steps.forEach((step, index) => {
                        const stepText = typeof step === 'object' ? (step.title || step.name || step) : step;
                        const stepHTML = `
                            <div class="routine-step-item-hjn" data-step="${index + 1}">
                                <div class="step-number-hjn">${index + 1}</div>
                                <input type="text" name="routine_steps[]" class="form-input-hjn step-input-hjn"
                                       placeholder="Describe this step..." value="${stepText}" required>
                                <button type="button" class="btn-remove-step-hjn" onclick="MyavanaTimeline.Forms.removeRoutineStep(this)"
                                        ${steps.length <= 1 ? 'disabled' : ''}>
                                    <svg viewBox="0 0 24 24" width="18" height="18">
                                        <path fill="currentColor" d="M19,6.41L17.59,5L12,10.59L6.41,5L5,6.41L10.59,12L5,17.59L6.41,19L12,13.41L17.59,19L19,17.59L13.41,12L19,6.41Z"/>
                                    </svg>
                                </button>
                            </div>
                        `;
                        stepsList.insertAdjacentHTML('beforeend', stepHTML);
                    });
                }
            }
        },

        /**
         * Handle entry form submission
         */
        handleEntrySubmit: async function(e) {
            e.preventDefault();
            console.log('Submitting entry form...');

            const form = e.target;
            const loadingEl = document.getElementById('entryFormLoading');
            const submitBtn = document.getElementById('saveEntryBtn');

            // Check if this is an edit (entry_id exists) or create (new entry)
            const entryIdInput = form.querySelector('#entry_id');
            const entryId = entryIdInput ? entryIdInput.value : '';
            const isEdit = entryId && entryId !== '';

            console.log(`Mode: ${isEdit ? 'EDIT' : 'CREATE'}, Entry ID: ${entryId || 'N/A'}`);

            // Show loading state
            if (loadingEl) loadingEl.style.display = 'flex';
            if (submitBtn) submitBtn.disabled = true;

            // Build payload
            const formData = new FormData();

            // Use different action based on create vs edit
            if (isEdit) {
                formData.append('action', 'myavana_update_entry');
                formData.append('entry_id', entryId);
            } else {
                formData.append('action', 'myavana_add_entry');
            }

            // Nonce and flags
            const nonceInput = form.querySelector('input[name="myavana_nonce"]');
            if (nonceInput) formData.append('myavana_nonce', nonceInput.value);
            const isAutomatedInput = form.querySelector('input[name="is_automated"]');
            formData.append('is_automated', isAutomatedInput ? isAutomatedInput.value : '0');
            const entryFlagInput = form.querySelector('input[name="myavana_entry"]');
            if (entryFlagInput) formData.append('myavana_entry', entryFlagInput.value);

            // Map fields to expected names
            formData.append('title', (form.querySelector('#entry_title')?.value || '').trim());
            formData.append('description', (form.querySelector('#entry_content')?.value || '').trim());
            formData.append('entry_date', form.querySelector('#entry_date')?.value || ''); // IMPORTANT: Preserve date
            formData.append('products', (form.querySelector('#products_used')?.value || '').trim());
            formData.append('notes', (form.querySelector('#notes')?.value || '').trim());
            formData.append('rating', form.querySelector('#health_rating')?.value || '3');
            formData.append('mood_demeanor', form.querySelector('#mood')?.value || '');

            // Optional extras
            const envInput = form.querySelector('select[name="environment"]');
            if (envInput) formData.append('environment', envInput.value);

            // Attach single photo like the shortcode (name: 'photo')
            const entryFilePond = MyavanaTimeline.State.get('entryFilePond');
            if (entryFilePond) {
                const files = entryFilePond.getFiles();
                if (files && files.length > 0) {
                    formData.append('photo', files[0].file);
                }
            }

            // Resolve AJAX URL (handle ajaxUrl vs ajaxurl)
            const ajaxUrl = (window.myavanaTimelineSettings && (myavanaTimelineSettings.ajaxUrl || myavanaTimelineSettings.ajaxurl)) || '/wp-admin/admin-ajax.php';

            try {
                const response = await fetch(ajaxUrl, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });
                const data = await response.json();

                if (data.success) {
                    console.log('Entry saved successfully:', data);
                    this.showNotification(data.data?.message || 'Entry saved successfully!', 'success');

                    // Reset form and FilePond
                    form.reset();
                    if (entryFilePond) {
                        entryFilePond.removeFiles();
                    }

                    // Close offcanvas and refresh
                    if (MyavanaTimeline.Offcanvas && MyavanaTimeline.Offcanvas.close) {
                        MyavanaTimeline.Offcanvas.close();
                    } else if (typeof closeOffcanvas === 'function') {
                        closeOffcanvas(); // Fallback to global function
                    }
                    this.refreshCurrentView();
                } else {
                    console.error('Error saving entry:', data);
                    this.showNotification((data && (data.data || data.message)) || 'Error saving entry', 'error');
                }
            } catch (error) {
                console.error('Error submitting entry:', error);
                this.showNotification('Network error. Please try again.', 'error');
            } finally {
                // Hide loading state
                if (loadingEl) loadingEl.style.display = 'none';
                if (submitBtn) submitBtn.disabled = false;
            }
        },

        /**
         * Handle goal form submission
         */
        handleGoalSubmit: async function(e) {
            e.preventDefault();
            console.log('Submitting goal form...');

            const form = e.target;
            const loadingEl = document.getElementById('goalFormLoading');
            const submitBtn = document.getElementById('saveGoalBtn');

            // Show loading state
            if (loadingEl) loadingEl.style.display = 'flex';
            if (submitBtn) submitBtn.disabled = true;

            try {
                // Collect milestones
                const milestones = [];
                const milestoneInputs = document.querySelectorAll('#milestones_list input');
                milestoneInputs.forEach(input => {
                    if (input.value.trim()) {
                        milestones.push(input.value.trim());
                    }
                });

                // Collect new progress note if entered
                const newNoteText = document.getElementById('newProgressNote')?.value?.trim();
                let progressNotes = milestones; // Start with existing milestones

                if (newNoteText) {
                    // Add new progress note
                    progressNotes.push({
                        text: newNoteText,
                        date: new Date().toISOString()
                    });
                }

                // Build goal object matching the expected structure
                const goal = {
                    title: document.getElementById('goal_title')?.value || '',
                    description: document.getElementById('goal_description')?.value || '',
                    progress: document.getElementById('goal_progress')?.value || '0',
                    start_date: document.getElementById('goal_start_date')?.value || '',
                    target_date: document.getElementById('goal_end_date')?.value || '',
                    category: document.getElementById('goal_category')?.value || '',
                    target: document.getElementById('goal_target')?.value || '',
                    progress_text: progressNotes
                };

                console.log('Goal data to save:', goal);

                const formData = new FormData();
                formData.append('action', 'myavana_save_hair_goal');
                formData.append('goal', JSON.stringify(goal));
                formData.append('index', document.getElementById('goal_id')?.value || '');
                formData.append('nonce', myavanaProfileAjax.nonce);

                const response = await fetch(myavanaProfileAjax.ajaxurl, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });

                const data = await response.json();

                if (data.success) {
                    console.log('Goal saved successfully:', data);
                    this.showNotification('Goal saved successfully!', 'success');

                    // Close offcanvas using correct method
                    if (MyavanaTimeline.Offcanvas && MyavanaTimeline.Offcanvas.close) {
                        MyavanaTimeline.Offcanvas.close();
                    } else if (typeof closeOffcanvas === 'function') {
                        closeOffcanvas();
                    }

                    this.refreshCurrentView();
                } else {
                    console.error('Error saving goal:', data);
                    this.showNotification(data.data || 'Error saving goal', 'error');
                }
            } catch (error) {
                console.error('Error submitting goal:', error);
                this.showNotification('Network error. Please try again.', 'error');
            } finally {
                if (loadingEl) loadingEl.style.display = 'none';
                if (submitBtn) submitBtn.disabled = false;
            }
        },

        /**
         * Handle routine form submission
         */
        handleRoutineSubmit: async function(e) {
            e.preventDefault();
            console.log('Submitting routine form...');

            const form = e.target;
            const loadingEl = document.getElementById('routineFormLoading');
            const submitBtn = document.getElementById('saveRoutineBtn');

            // Show loading state
            if (loadingEl) loadingEl.style.display = 'flex';
            if (submitBtn) submitBtn.disabled = true;

            try {
                // Collect routine steps
                const steps = [];
                const stepInputs = document.querySelectorAll('#routine_steps_list input');
                stepInputs.forEach(input => {
                    if (input.value.trim()) {
                        steps.push(input.value.trim());
                    }
                });

                // Build routine object matching the expected structure
                const step = {
                    name: document.getElementById('routine_title')?.value || '',
                    frequency: document.getElementById('routine_frequency')?.value || '',
                    time_of_day: document.getElementById('routine_time')?.value || '',
                    description: steps.join('\n'), // Combine steps as description
                    products: document.getElementById('routine_products')?.value?.split('\n').filter(p => p.trim()) || [],
                    routine_type: document.getElementById('routine_type')?.value || '',
                    duration: document.getElementById('routine_duration')?.value || '',
                    notes: document.getElementById('routine_notes')?.value || ''
                };

                console.log('Routine data to save:', step);

                const formData = new FormData();
                formData.append('action', 'myavana_save_routine_step');
                formData.append('step', JSON.stringify(step));
                formData.append('index', document.getElementById('routine_id')?.value || '');
                formData.append('nonce', myavanaProfileAjax.nonce);

                const response = await fetch(myavanaProfileAjax.ajaxurl, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });

                const data = await response.json();

                if (data.success) {
                    console.log('Routine saved successfully:', data);
                    this.showNotification('Routine saved successfully!', 'success');

                    // Close offcanvas using correct method
                    if (MyavanaTimeline.Offcanvas && MyavanaTimeline.Offcanvas.close) {
                        MyavanaTimeline.Offcanvas.close();
                    } else if (typeof closeOffcanvas === 'function') {
                        closeOffcanvas();
                    }

                    this.refreshCurrentView();
                } else {
                    console.error('Error saving routine:', data);
                    this.showNotification(data.data || 'Error saving routine', 'error');
                }
            } catch (error) {
                console.error('Error submitting routine:', error);
                this.showNotification('Network error. Please try again.', 'error');
            } finally {
                if (loadingEl) loadingEl.style.display = 'none';
                if (submitBtn) submitBtn.disabled = false;
            }
        },

        /**
         * Add milestone to goal form
         */
        addMilestone: function() {
            const milestonesList = document.getElementById('milestones_list');
            const milestoneCount = milestonesList.querySelectorAll('.milestone-item-hjn').length;

            const milestoneItem = document.createElement('div');
            milestoneItem.className = 'milestone-item-hjn';
            milestoneItem.innerHTML = `
                <input type="text" class="form-input-hjn" placeholder="Milestone ${milestoneCount + 1}...">
                <button type="button" class="btn-remove-milestone-hjn" onclick="MyavanaTimeline.Forms.removeMilestone(this)">
                    <svg viewBox="0 0 24 24" width="18" height="18">
                        <path fill="currentColor" d="M19,6.41L17.59,5L12,10.59L6.41,5L5,6.41L10.59,12L5,17.59L6.41,19L12,13.41L17.59,19L19,17.59L13.41,12L19,6.41Z"/>
                    </svg>
                </button>
            `;

            milestonesList.appendChild(milestoneItem);
        },

        /**
         * Remove milestone from goal form
         */
        removeMilestone: function(button) {
            button.closest('.milestone-item-hjn').remove();
        },

        /**
         * Add routine step
         */
        addRoutineStep: function() {
            const stepsList = document.getElementById('routine_steps_list');
            const stepCount = stepsList.querySelectorAll('.routine-step-item-hjn').length + 1;

            const stepItem = document.createElement('div');
            stepItem.className = 'routine-step-item-hjn';
            stepItem.setAttribute('data-step', stepCount);
            stepItem.innerHTML = `
                <div class="step-number-hjn">${stepCount}</div>
                <input type="text" name="routine_steps[]" class="form-input-hjn step-input-hjn" placeholder="Describe this step..." required>
                <button type="button" class="btn-remove-step-hjn" onclick="MyavanaTimeline.Forms.removeRoutineStep(this)">
                    <svg viewBox="0 0 24 24" width="18" height="18">
                        <path fill="currentColor" d="M19,6.41L17.59,5L12,10.59L6.41,5L5,6.41L10.59,12L5,17.59L6.41,19L12,13.41L17.59,19L19,17.59L13.41,12L19,6.41Z"/>
                    </svg>
                </button>
            `;

            stepsList.appendChild(stepItem);
            this.updateRemoveStepButtons();
        },

        /**
         * Remove routine step
         */
        removeRoutineStep: function(button) {
            const stepsList = document.getElementById('routine_steps_list');
            const steps = stepsList.querySelectorAll('.routine-step-item-hjn');

            if (steps.length <= 1) return; // Keep at least one step

            button.closest('.routine-step-item-hjn').remove();

            // Renumber steps
            const remainingSteps = stepsList.querySelectorAll('.routine-step-item-hjn');
            remainingSteps.forEach((step, index) => {
                step.setAttribute('data-step', index + 1);
                step.querySelector('.step-number-hjn').textContent = index + 1;
            });

            this.updateRemoveStepButtons();
        },

        /**
         * Update remove step buttons (disable if only one step)
         */
        updateRemoveStepButtons: function() {
            const stepsList = document.getElementById('routine_steps_list');
            const steps = stepsList.querySelectorAll('.routine-step-item-hjn');
            const removeButtons = stepsList.querySelectorAll('.btn-remove-step-hjn');

            removeButtons.forEach(btn => {
                btn.disabled = steps.length <= 1;
            });
        },

        /**
         * Update progress value display
         */
        updateProgressValue: function(value) {
            const progressValue = document.getElementById('goal_progress_value');
            if (progressValue) {
                progressValue.textContent = value + '%';
            }
        },

        /**
         * Refresh current view after saving
         */
        refreshCurrentView: function() {
            console.log('Refreshing current view...');

            // Simple page reload for now
            // Future enhancement: Dynamically update the view without full reload
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        },

        /**
         * Show notification
         */
        showNotification: function(message, type = 'info') {
            console.log('Notification:', type, message);

            // Create notification element
            const notification = document.createElement('div');
            notification.className = `notification-hjn notification-${type}-hjn`;
            notification.textContent = message;
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 1rem 1.5rem;
                background: ${type === 'success' ? '#4caf50' : type === 'error' ? '#f44336' : '#2196f3'};
                color: white;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.2);
                z-index: 100000;
                font-family: Archivo, sans-serif;
                font-weight: 600;
                animation: slideIn 0.3s ease;
            `;

            document.body.appendChild(notification);

            // Auto-remove after 3 seconds
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => {
                    notification.remove();
                }, 300);
            }, 3000);
        }
    };

    // Register module
    MyavanaTimeline.Forms = Forms;

    // Expose global helper functions for inline onclick handlers
    window.MyavanaTimelineForms = {
        openOffcanvas: Forms.openOffcanvas.bind(Forms),
        addMilestone: Forms.addMilestone.bind(Forms),
        removeMilestone: Forms.removeMilestone.bind(Forms),
        addRoutineStep: Forms.addRoutineStep.bind(Forms),
        removeRoutineStep: Forms.removeRoutineStep.bind(Forms),
        updateProgressValue: Forms.updateProgressValue.bind(Forms),
        removeExistingImage: Forms.removeExistingImage.bind(Forms)
    };

    // Backwards compatibility - expose to window for inline onclick handlers
    window.openOffcanvas = Forms.openOffcanvas.bind(Forms);
    window.addMilestone = Forms.addMilestone.bind(Forms);
    window.removeMilestone = Forms.removeMilestone.bind(Forms);
    window.addRoutineStep = Forms.addRoutineStep.bind(Forms);
    window.removeRoutineStep = Forms.removeRoutineStep.bind(Forms);
    window.updateProgressValue = Forms.updateProgressValue.bind(Forms);
    window.removeExistingImage = Forms.removeExistingImage.bind(Forms);

    console.log('Timeline Forms module loaded');

})(window, jQuery);
