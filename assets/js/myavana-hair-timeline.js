/**
 * MYAVANA Hair Journey Timeline
 * Comprehensive timeline functionality with modal system, entry management, and social sharing
 *
 * @package Myavana_Hair_Journey
 * @version 2.3.5
 */

(function($) {
    'use strict';

    // Global cache for entry data
    let entryDataCache = new Map();
    let currentEntryData = null;

    /**
     * Main Timeline Component
     */
    const MyavanaTimeline = {

        // Timeline elements
        startPage: null,
        timelineContainer: null,
        endPage: null,
        splide: null,

        // FilePond instance
        pond: null,

        // Hover preview
        hoverTimeout: null,
        quickPreviewElement: null,

        // Settings (will be populated via wp_localize_script)
        settings: window.myavanaTimelineSettings || {
            ajaxUrl: '/wp-admin/admin-ajax.php',
            getEntriesNonce: '',
            getEntryDetailsNonce: '',
            updateEntryNonce: '',
            deleteEntryNonce: '',
            addEntryNonce: '',
            autoStartTimeline: false
        },

        /**
         * Initialize the timeline
         */
        init: function() {
            console.log('MyavanaTimeline initializing...');

            // Cache DOM elements
            this.startPage = $('#startPage');
            this.timelineContainer = $('#timelineContainer');
            this.endPage = $('#endPage');

            // Initialize FilePond
            this.initFilePond();

            // Bind event handlers
            this.bindEvents();

            // Auto-start timeline if URL parameter is present
            if (this.settings.autoStartTimeline) {
                this.startPage.hide();
                this.timelineContainer.show();
                this.loadEntries();
            }

            console.log('MyavanaTimeline initialized successfully');
        },

        /**
         * Initialize FilePond for image uploads
         */
        initFilePond: function() {
            if (typeof FilePond === 'undefined') {
                console.error('FilePond library not loaded');
                return;
            }

            FilePond.registerPlugin(
                FilePondPluginImagePreview,
                FilePondPluginImageValidateSize
            );

            const pondElement = document.querySelector('#filepond-container');
            if (pondElement) {
                this.pond = FilePond.create(pondElement, {
                    name: 'photo',
                    allowMultiple: false,
                    maxFiles: 1,
                    acceptedFileTypes: ['image/*'],
                    maxFileSize: '30MB',
                    labelIdle: `
                        <div class="div-center entry-file-upload">
                            <div class="file-upload-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M10 1C9.73478 1 9.48043 1.10536 9.29289 1.29289L3.29289 7.29289C3.10536 7.48043 3 7.73478 3 8V20C3 21.6569 4.34315 23 6 23H7C7.55228 23 8 22.5523 8 22C8 21.4477 7.55228 21 7 21H6C5.44772 21 5 20.5523 5 20V9H10C10.5523 9 11 8.55228 11 8V3H18C18.5523 3 19 3.44772 19 4V9C19 9.55228 19.4477 10 20 10C20.5523 10 21 9.55228 21 9V4C21 2.34315 19.6569 1 18 1H10ZM9 7H6.41421L9 4.41421V7ZM14 15.5C14 14.1193 15.1193 13 16.5 13C17.8807 13 19 14.1193 19 15.5V16V17H20C21.1046 17 22 17.8954 22 19C22 20.1046 21.1046 21 20 21H13C11.8954 21 11 20.1046 11 19C11 17.8954 11.8954 17 13 17H14V16V15.5ZM16.5 11C14.142 11 12.2076 12.8136 12.0156 15.122C10.2825 15.5606 9 17.1305 9 19C9 21.2091 10.7909 23 13 23H20C22.2091 23 24 21.2091 24 19C24 17.1305 22.7175 15.5606 20.9844 15.122C20.7924 12.8136 18.858 11 16.5 11Z" clip-rule="evenodd" fill-rule="evenodd"></path></svg>
                            </div>
                            <div class="text">
                                <span>Drag & Drop your photo or <span class="filepond--label-action">Browse</span></span>
                            </div>
                        </div>
                    `,
                    storeAsFile: true
                });
            }
        },

        /**
         * Bind all event handlers
         */
        bindEvents: function() {
            const self = this;

            // Journey control buttons
            $('#startJourneyBtn').on('click', function() {
                self.startJourney($(this));
            });

            $('#restartJourneyBtn').on('click', function() {
                self.restartJourney();
            });

            $('#viewTimelineBtn').on('click', function() {
                self.viewTimeline();
            });

            $('#shareProgressBtn').on('click', function() {
                self.shareProgress();
            });

            // Add entry modal
            const addEntryBtn = document.getElementById('addEntryBtn');
            const entryModal = document.getElementById('entryModal');
            const modalClose = document.getElementById('modalClose');
            const cancelEntryBtn = document.getElementById('cancelEntryBtn');

            if (addEntryBtn && entryModal) {
                addEntryBtn.addEventListener('click', function() {
                    entryModal.classList.add('active');
                    document.body.style.overflow = 'hidden';
                });

                const closeAddModal = function() {
                    entryModal.classList.remove('active');
                    document.body.style.overflow = 'auto';
                };

                if (modalClose) modalClose.addEventListener('click', closeAddModal);
                if (cancelEntryBtn) cancelEntryBtn.addEventListener('click', closeAddModal);

                entryModal.addEventListener('click', function(e) {
                    if (e.target === entryModal) {
                        closeAddModal();
                    }
                });
            }

            // Add entry form submission
            $('#myavana-entry-form').on('submit', function(e) {
                self.handleAddEntry(e, $(this));
            });

            // List view: View Details buttons (delegated)
            $(document).on('click', '.list-item-action', function(e) {
                const btn = $(this);
                const action = btn.data('action');

                // Small helper to escape text for insertion into HTML
                function escapeHtml(str) {
                    return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
                }

                if (action === 'view-entry') {
                    const entryId = btn.data('entry-id');
                    if (!entryId) return;
                    // Open the entry offcanvas then fetch details and populate a full edit form
                    if (typeof openOffcanvas === 'function') {
                        openOffcanvas('entry');
                    }

                    const container = document.querySelector('#entryOffcanvas .offcanvas-body');
                    if (container) container.innerHTML = '<div class="modal-loading"><div class="modal-spinner"></div></div>';

                    self.fetchEntryDetails(entryId).then(function(data) {
                        // Normalize fields from different server shapes
                        const title = data.title || data.entry_title || '';
                        const description = data.description || data.entry_content || '';
                        const date = data.date || data.entry_date || '';
                        const rating = data.rating || data.mood_rating || data.hair_health_rating || '';
                        const mood = data.mood || data.mood_demeanor || '';
                        const environment = data.environment || '';
                        const products = data.products || data.products_used || '';
                        const notes = data.notes || data.stylist_notes || '';
                        const images = data.images || (data.image ? [{url: data.image}] : []);

                        // Build an editable form inside the offcanvas
                        let html = '';
                        html += '<form id="entryOffcanvasForm" enctype="multipart/form-data">';
                        html += '<input type="hidden" name="entry_id" value="' + escapeHtml(entryId) + '">';

                        html += '<div class="form-group">';
                        html += '<label class="form-label">Entry Title</label>';
                        html += '<input type="text" id="entryOffcanvasTitle" name="title" class="form-input" value="' + escapeHtml(title) + '">';
                        html += '</div>';

                        html += '<div class="form-row">';
                        html += '<div class="form-group">';
                        html += '<label class="form-label">Date</label>';
                        html += '<input type="text" id="entryOffcanvasDate" class="form-input" value="' + escapeHtml(date) + '" readonly>'; // date shown as read-only
                        html += '</div>';

                        html += '<div class="form-group">';
                        html += '<label class="form-label">Rating</label>';
                        html += '<select id="entryOffcanvasRating" name="rating" class="form-select">';
                        for (let i = 1; i <= 5; i++) {
                            html += '<option value="' + i + '"' + (String(i) === String(rating) ? ' selected' : '') + '>' + i + '</option>';
                        }
                        html += '</select>';
                        html += '</div>';
                        html += '</div>';

                        html += '<div class="form-group">';
                        html += '<label class="form-label">Description</label>';
                        html += '<textarea id="entryOffcanvasDescription" name="description" class="form-textarea">' + escapeHtml(description) + '</textarea>';
                        html += '</div>';

                        html += '<div class="form-group">';
                        html += '<label class="form-label">Products</label>';
                        html += '<input type="text" id="entryOffcanvasProducts" name="products" class="form-input" value="' + escapeHtml(products) + '">';
                        html += '</div>';

                        html += '<div class="form-group">';
                        html += '<label class="form-label">Notes</label>';
                        html += '<textarea id="entryOffcanvasNotes" name="notes" class="form-textarea">' + escapeHtml(notes) + '</textarea>';
                        html += '</div>';

                        html += '<div class="form-row">';
                        html += '<div class="form-group">';
                        html += '<label class="form-label">Mood</label>';
                        html += '<input type="text" id="entryOffcanvasMood" name="mood" class="form-input" value="' + escapeHtml(mood) + '">';
                        html += '</div>';
                        html += '<div class="form-group">';
                        html += '<label class="form-label">Environment</label>';
                        html += '<input type="text" id="entryOffcanvasEnvironment" name="environment" class="form-input" value="' + escapeHtml(environment) + '">';
                        html += '</div>';
                        html += '</div>';

                        // Images preview + file input
                        html += '<div class="form-group">';
                        html += '<label class="form-label">Photo</label>';
                        html += '<div id="entryOffcanvasPreview">';
                        if (images && images.length) {
                            for (let img of images) {
                                html += '<div class="photo-preview-item"><img src="' + escapeHtml(img.url || img) + '" style="max-width:100%;display:block;margin-bottom:8px;"/></div>';
                            }
                        }
                        html += '</div>';
                        html += '<input type="file" id="entryOffcanvasPhoto" name="photo" accept="image/*">';
                        html += '</div>';

                        html += '</form>';

                        if (container) container.innerHTML = html;

                        // Wire save button in offcanvas footer to perform update via AJAX
                        const saveBtn = document.querySelector('#entryOffcanvas .offcanvas-footer .btn-primary');
                        if (saveBtn) {
                            // Remove any previous handler
                            saveBtn.replaceWith(saveBtn.cloneNode(true));
                            const newSave = document.querySelector('#entryOffcanvas .offcanvas-footer .btn-primary');
                            newSave.addEventListener('click', function(ev) {
                                ev.preventDefault();
                                const form = document.getElementById('entryOffcanvasForm');
                                if (!form) return;

                                const formData = new FormData(form);
                                formData.append('action', 'myavana_update_entry');
                                formData.append('entry_id', entryId);
                                formData.append('security', self.settings.updateEntryNonce || self.settings.getEntryDetailsNonce || '');

                                const fileInput = document.getElementById('entryOffcanvasPhoto');
                                if (fileInput && fileInput.files && fileInput.files[0]) {
                                    formData.append('photo', fileInput.files[0]);
                                }

                                $.ajax({
                                    url: self.settings.ajaxUrl,
                                    type: 'POST',
                                    data: formData,
                                    processData: false,
                                    contentType: false,
                                    success: function(response) {
                                        if (response && response.success) {
                                            // close and refresh entries
                                            if (typeof closeOffcanvas === 'function') closeOffcanvas();
                                            if (typeof self.loadEntries === 'function') {
                                                self.loadEntries();
                                            } else {
                                                // fallback: reload page
                                                location.reload();
                                            }
                                        } else {
                                            console.error('Update failed', response);
                                            alert(response && response.data ? response.data : 'Failed to update entry');
                                        }
                                    },
                                    error: function(err) {
                                        console.error('AJAX error', err);
                                        alert('An error occurred while updating the entry');
                                    }
                                });
                            });
                        }

                    }).catch(function(err) {
                        if (container) container.innerHTML = '<div class="list-empty">Unable to load entry details.</div>';
                        console.error(err);
                    });

                } else if (action === 'view-goal') {
                    const index = btn.data('index');
                    // open goal offcanvas and populate with goal data from DOM or via AJAX
                    if (typeof openOffcanvas === 'function') openOffcanvas('goal');
                    const container = document.querySelector('#goalOffcanvas .offcanvas-body');
                    // try to get goal data from the DOM (list item fields)
                    const listItem = btn.closest('.list-item-goal');
                    let title = listItem ? listItem.querySelector('.list-item-title') : null;
                    let subtitle = listItem ? listItem.querySelector('.list-item-subtitle') : null;
                    if (container) {
                        container.innerHTML = '<h3>' + (title ? title.textContent : 'Goal') + '</h3>' + (subtitle ? ('<p>' + subtitle.textContent + '</p>') : '');
                    }

                } else if (action === 'view-routine') {
                    const idx = btn.data('index');
                    if (typeof openOffcanvas === 'function') openOffcanvas('routine');
                    const container = document.querySelector('#routineOffcanvas .offcanvas-body');
                    const listItem = btn.closest('.list-item-routine');
                    let title = listItem ? listItem.querySelector('.list-item-title') : null;
                    let subtitle = listItem ? listItem.querySelector('.list-item-subtitle') : null;
                    if (container) {
                        container.innerHTML = '<h3>' + (title ? title.textContent : 'Routine') + '</h3>' + (subtitle ? ('<p>' + subtitle.textContent + '</p>') : '');
                    }
                }
            });

            // View Modal Events
            $('#viewEntryModalClose, #closeViewEntry').on('click', function() {
                self.closeModal('viewEntryModal');
                if (currentEntryData) {
                    self.syncTimelineWithModal(currentEntryData.id);
                }
            });

            $('#editEntryFromView').on('click', function() {
                self.closeModal('viewEntryModal');
                setTimeout(() => self.openEditModal(currentEntryData.id), 100);
            });

            $('#shareEntryFromView').on('click', function() {
                self.closeModal('viewEntryModal');
                setTimeout(() => self.openShareModal(currentEntryData.id), 100);
            });

            // Edit Modal Events
            $('#editEntryModalClose, #cancelEditEntry').on('click', function() {
                self.closeModal('editEntryModal');
            });

            $('#editImageUpload').on('click', function() {
                $('#editImageInput').click();
            });

            $('#editImageInput').on('change', function(e) {
                self.handleEditImageChange(e);
            });

            $('#editEntryForm').on('submit', function(e) {
                self.handleEditEntry(e);
            });

            // Share Modal Events
            $('#shareEntryModalClose, #closeShareModal').on('click', function() {
                self.closeModal('shareEntryModal');
            });

            $('#shareFacebook').on('click', function() {
                self.shareToFacebook();
            });

            $('#shareTwitter').on('click', function() {
                self.shareToTwitter();
            });

            $('#shareInstagram').on('click', function() {
                self.shareToInstagram();
            });

            $('#shareCopyLink').on('click', function() {
                self.copyShareLink();
            });

            // Delete Modal Events
            $('#deleteEntryModalClose, #cancelDeleteEntry').on('click', function() {
                self.closeModal('deleteEntryModal');
            });

            $('#confirmDeleteEntry').on('click', function() {
                self.confirmDeleteEntry();
            });

            // Close modals when clicking outside - FIXED propagation issue
            $('.myavana-timeline-modal-overlay').on('click', function(e) {
                if ($(e.target).hasClass('myavana-timeline-modal-overlay')) {
                    $(this).removeClass('active');
                    $('body').css('overflow', 'auto');
                    currentEntryData = null;
                }
            });

            // Keyboard shortcuts for modal navigation
            $(document).on('keydown', function(e) {
                self.handleKeyboardShortcuts(e);
            });
        },

        /**
         * Start journey button handler
         */
        startJourney: async function($btn) {
            console.log('start button clicked');

            const originalText = $btn.html();
            $btn.prop('disabled', true).html('<span>Loading...</span>');

            try {
                await this.loadEntries();
                this.startPage.hide();
                this.timelineContainer.show();
                this.endPage.hide();
            } catch (error) {
                console.error("Error loading entries:", error);
                alert("There was an error loading your journey. Please try again.");
            } finally {
                $btn.prop('disabled', false).html(originalText);
            }
        },

        /**
         * Restart journey
         */
        restartJourney: function() {
            this.endPage.hide();
            this.timelineContainer.show();
            this.startPage.hide();
        },

        /**
         * View timeline
         */
        viewTimeline: function() {
            this.endPage.hide();
            this.timelineContainer.show();
            this.startPage.hide();
        },

        /**
         * Share progress to social media
         */
        shareProgress: function() {
            const stats = {
                entries: $('#entriesCount').text(),
                growth: $('#growthProgress').text(),
                health: $('#healthProgress').text()
            };

            const shareText = `Check out my MYAVANA hair journey progress! ${stats.entries} entries, ${stats.growth} growth, and ${stats.health} health improvement!`;

            if (navigator.share) {
                navigator.share({
                    title: 'My MYAVANA Hair Journey',
                    text: shareText,
                    url: window.location.href
                });
            } else if (navigator.clipboard) {
                navigator.clipboard.writeText(shareText).then(function() {
                    alert('Progress copied to clipboard!');
                });
            } else {
                alert('Share functionality would be implemented here!');
            }
        },

        /**
         * Create confetti animation
         */
        createConfetti: function() {
            const confettiContainer = $('.confetti-container');
            if (confettiContainer.length === 0) return;

            const colors = ['#e7a690', '#fce5d7', '#4a4d68', '#f5f5f7', '#222323'];

            for (let i = 0; i < 50; i++) {
                setTimeout(() => {
                    const confetti = $('<div class="confetti-piece"></div>');
                    const color = colors[Math.floor(Math.random() * colors.length)];
                    const left = Math.random() * 100;
                    const animationDelay = Math.random() * 3;
                    const fallDuration = 3 + Math.random() * 2;

                    confetti.css({
                        'background-color': color,
                        'left': left + '%',
                        'animation-delay': animationDelay + 's',
                        'animation-duration': fallDuration + 's'
                    });

                    confettiContainer.append(confetti);

                    setTimeout(() => confetti.remove(), (fallDuration + animationDelay) * 1000 + 500);
                }, i * 100);
            }
        },

        /**
         * Show end page with animations
         */
        showEndPage: function() {
            this.timelineContainer.hide();
            this.endPage.show();
            this.createConfetti();
            this.animateProgressVisualization();
            this.updateProgressData();
        },

        /**
         * Animate progress visualization
         */
        animateProgressVisualization: function() {
            const self = this;

            // Animate progress ring
            setTimeout(() => {
                const progressCircle = $('.progress-circle');
                const percentage = 75;
                const circumference = 534;
                const offset = circumference - (percentage / 100 * circumference);

                progressCircle.css('stroke-dashoffset', offset);
                $('#overallProgress').html(percentage + '%');
            }, 500);

            // Animate stat cards
            $('.myavana-stat-card').each(function(index) {
                const $card = $(this);
                setTimeout(() => {
                    $card.addClass('animate-in');
                }, 800 + (index * 200));
            });

            // Animate achievement badges
            $('.myavana-achievement-badge.earned').each(function(index) {
                const $badge = $(this);
                setTimeout(() => {
                    $badge.addClass('animate-earned');
                }, 1500 + (index * 300));
            });
        },

        /**
         * Update progress data from entries
         */
        updateProgressData: function() {
            const entries = $('.splide__slide').length - 1;
            const products = new Set();

            $('.product-tag').each(function() {
                products.add($(this).text().trim());
            });

            const growthInches = Math.max(0, (entries * 0.2)).toFixed(1);
            const healthImprovement = Math.min(100, entries * 8);

            this.animateNumber('#growthProgress', 0, parseFloat(growthInches), '+', '"', 1000);
            this.animateNumber('#healthProgress', 0, healthImprovement, '+', '%', 1200);
            this.animateNumber('#entriesCount', 0, entries, '', '', 800);
            this.animateNumber('#productsCount', 0, products.size, '', '', 1400);
        },

        /**
         * Animate numbers with easing
         */
        animateNumber: function(selector, start, end, prefix = '', suffix = '', duration = 1000) {
            const $element = $(selector);
            const startTime = performance.now();

            function updateNumber(currentTime) {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);

                const easeOut = 1 - Math.pow(1 - progress, 3);
                const current = start + (end - start) * easeOut;

                if (Number.isInteger(end)) {
                    $element.text(prefix + Math.floor(current) + suffix);
                } else {
                    $element.text(prefix + current.toFixed(1) + suffix);
                }

                if (progress < 1) {
                    requestAnimationFrame(updateNumber);
                }
            }

            requestAnimationFrame(updateNumber);
        },

        /**
         * Load entries via AJAX
         */
        loadEntries: function() {
            const self = this;
            console.log('loadEntries() function called');

            this.clearEntryCache();

            return new Promise((resolve, reject) => {
                console.log('Making AJAX request to:', self.settings.ajaxUrl);
                $.ajax({
                    url: self.settings.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'myavana_get_entries',
                        security: self.settings.getEntriesNonce
                    },
                    success: function(response) {
                        console.log('AJAX response received:', response);
                        if (response.success) {
                            console.log('Response successful, updating HTML');

                            $('.splide__list').html(response.data.entries_html);
                            $('#timelineDates').html(response.data.dates_html);

                            $('#entriesCount').text(response.data.stats.entries_count);
                            $('#productsCount').text(response.data.stats.products_count);
                            $('#growthProgress').text('+' + response.data.stats.growth + '"');
                            $('#healthProgress').text('+' + response.data.stats.health + '%');

                            self.initSlider();
                            self.initEntryActions();
                            self.setupDoubleClickEvents();

                            if (response.data.reached_end) {
                                self.showEndPage();
                            }

                            console.log('Entries successfully loaded and displayed');
                            resolve();
                        } else {
                            console.error('Response not successful:', response.data);
                            reject(response.data);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX error:', {xhr, status, error});
                        console.error('Response text:', xhr.responseText);
                        reject(error);
                    }
                });
            });
        },

        /**
         * Initialize Splide slider
         */
        initSlider: function() {
            const self = this;
            const totalEntries = $('.main-entry').length;

            if (this.splide) {
                this.splide.destroy();
            }

            this.splide = new Splide('#slider', {
                type: 'loop',
                perPage: 1,
                perMove: 1,
                gap: '15px',
                padding: { left: '20%', right: '20%' },
                arrows: true,
                pagination: false,
                speed: 400,
                easing: 'cubic-bezier(0.25, 1, 0.5, 1)',
                autoHeight: true,
                trimSpace: false,
                breakpoints: {
                    768: {
                        gap: '10px',
                        padding: { left: '15%', right: '15%' },
                        speed: 300,
                    },
                    480: {
                        gap: '8px',
                        padding: { left: '10%', right: '10%' },
                        speed: 250,
                    }
                }
            });

            this.splide.on('moved', function(newIndex) {
                self.updateProgress(newIndex, totalEntries);
                self.updateActiveMarker(newIndex);

                $('.custom-end-arrow').remove();

                if (newIndex === totalEntries - 1 && totalEntries > 0) {
                    const customArrow = $(`
                        <div class="custom-end-arrow" style="position: fixed; right: 20px; top: 50%; transform: translateY(-50%); z-index: 9999;">
                            <button class="end-journey-btn" style="pointer-events: auto; cursor: pointer;">
                                <span>View Summary</span>
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                    `);

                    customArrow.find('.end-journey-btn').on('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        self.showEndPage();
                    });

                    $('body').append(customArrow);
                    setTimeout(() => customArrow.remove(), 10000);
                }
            });

            $('.date-marker').on('click', function() {
                const index = parseInt($(this).data('index'));
                self.splide.go(index);
            });

            this.splide.mount();

            if (totalEntries > 0) {
                this.updateProgress(0, totalEntries);
                this.updateActiveMarker(0);
            }
        },

        /**
         * Update progress bar
         */
        updateProgress: function(index, total) {
            const progressWidth = total > 0 ? ((index + 1) / total) * 100 : 0;
            $('#progress').css('width', `${progressWidth}%`);
        },

        /**
         * Update active date marker
         */
        updateActiveMarker: function(index) {
            $('.date-marker').removeClass('active');
            $(`.date-marker[data-index="${index}"]`).addClass('active');
        },

        /**
         * Initialize entry action handlers
         */
        initEntryActions: function() {
            const self = this;

            // Love heart functionality
            $('.entry-love-heart').on('click', function(e) {
                e.stopPropagation();
                const $heart = $(this);
                const entryId = $heart.data('entry-id');

                $heart.toggleClass('loved');

                const lovedEntries = JSON.parse(localStorage.getItem('lovedEntries') || '[]');
                if ($heart.hasClass('loved')) {
                    if (!lovedEntries.includes(entryId)) {
                        lovedEntries.push(entryId);
                    }
                } else {
                    const index = lovedEntries.indexOf(entryId);
                    if (index > -1) {
                        lovedEntries.splice(index, 1);
                    }
                }
                localStorage.setItem('lovedEntries', JSON.stringify(lovedEntries));
            });

            // Restore loved states
            const lovedEntries = JSON.parse(localStorage.getItem('lovedEntries') || '[]');
            lovedEntries.forEach(entryId => {
                $(`.entry-love-heart[data-entry-id="${entryId}"]`).addClass('loved');
            });

            // Quick actions menu
            $('.main-entry').on('mouseenter', function() {
                $(this).addClass('show-actions');
            }).on('mouseleave', function() {
                $(this).removeClass('show-actions');
            });

            // Mobile tap for floating cards
            if (window.innerWidth <= 768) {
                $('.main-entry').on('click', function() {
                    $('.main-entry').removeClass('tapped');
                    $(this).addClass('tapped');
                });

                $(document).on('click', function(e) {
                    if (!$(e.target).closest('.main-entry, .floating-entry-card').length) {
                        $('.main-entry').removeClass('tapped');
                    }
                });
            }

            // Quick action buttons
            $('.quick-action-btn').on('click', function(e) {
                e.stopPropagation();
                const action = $(this).data('action');
                const entryId = $(this).closest('.main-entry').data('entry-id');

                switch(action) {
                    case 'edit':
                        self.openEditModal(entryId);
                        break;
                    case 'duplicate':
                        alert('Duplicate functionality would clone this entry');
                        break;
                    case 'share':
                        self.openShareModal(entryId);
                        break;
                    case 'delete':
                        self.openDeleteModal(entryId);
                        break;
                }
            });

            // Image zoom functionality
            $('.entry-image-zoom-overlay').on('click', function(e) {
                e.stopPropagation();
                const imageSrc = $(this).siblings('.entry-image').attr('src');
                if (imageSrc) {
                    const zoomModal = $(`
                        <div class="image-zoom-modal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); display: flex; align-items: center; justify-content: center; z-index: 10000; cursor: zoom-out;">
                            <img src="${imageSrc}" style="max-width: 90%; max-height: 90%; object-fit: contain;">
                            <div style="position: absolute; top: 20px; right: 20px; color: white; font-size: 30px; cursor: pointer;">&times;</div>
                        </div>
                    `);

                    zoomModal.on('click', function() {
                        $(this).fadeOut(200, function() { $(this).remove(); });
                    });

                    $('body').append(zoomModal);
                }
            });

            // Product tag interactions
            $('.product-tag').on('click', function() {
                const productName = $(this).text().trim();
                alert(`Learn more about: ${productName}`);
            });

            // Double-tap to shake (mobile)
            let tapCount = 0;
            $('.main-entry').on('touchstart', function() {
                tapCount++;
                setTimeout(() => { tapCount = 0; }, 600);

                if (tapCount === 2) {
                    $(this).addClass('shake');
                    setTimeout(() => $(this).removeClass('shake'), 500);
                }
            });
        },

        /**
         * Get entry data from DOM or cache
         */
        getEntryData: function(entryId) {
            if (entryDataCache.has(entryId)) {
                return entryDataCache.get(entryId);
            }

            const entryElement = $(`.main-entry[data-entry-id="${entryId}"]`);
            if (!entryElement.length) return null;

            const data = {
                id: entryId,
                title: entryElement.find('.entry-title').text().trim(),
                description: entryElement.find('.entry-text').text().trim(),
                image: entryElement.find('.entry-image').attr('src') || '',
                date: entryElement.find('.entry-time').text().trim(),
                rating: entryElement.data('rating') || '5',
                mood: entryElement.find('.entry-mood').text().replace('😊', '').trim() || 'Happy',
                environment: entryElement.data('environment') || 'Home',
                products: this.extractProductsFromEntry(entryElement),
                notes: entryElement.data('notes') || '',
                completeness: entryElement.data('completeness') || 0,
                aiTags: this.extractAITags(entryElement),
                timeAgo: this.calculateTimeAgo(entryElement.find('.entry-time').text().trim())
            };

            entryDataCache.set(entryId, data);
            return data;
        },

        /**
         * Extract products from entry element
         */
        extractProductsFromEntry: function(entryElement) {
            const productTags = entryElement.find('.product-tag');
            const products = [];
            productTags.each(function() {
                products.push($(this).text().trim());
            });
            return products.join(', ');
        },

        /**
         * Extract AI tags from entry
         */
        extractAITags: function(entryElement) {
            const aiTagsElement = entryElement.find('.ai-tags .ai-tag');
            const tags = [];
            aiTagsElement.each(function() {
                tags.push($(this).text().trim());
            });
            return tags;
        },

        /**
         * Calculate time ago from date string
         */
        calculateTimeAgo: function(dateString) {
            if (!dateString) return 'Recently';

            const now = new Date();
            const entryDate = new Date(dateString);
            const diffMs = now - entryDate;
            const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));

            if (diffDays === 0) return 'Today';
            if (diffDays === 1) return 'Yesterday';
            if (diffDays < 7) return `${diffDays} days ago`;
            if (diffDays < 30) return `${Math.floor(diffDays / 7)} weeks ago`;
            if (diffDays < 365) return `${Math.floor(diffDays / 30)} months ago`;
            return `${Math.floor(diffDays / 365)} years ago`;
        },

        /**
         * Fetch detailed entry data from server
         */
        fetchEntryDetails: function(entryId) {
            const self = this;
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: self.settings.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'myavana_get_entry_details',
                        entry_id: entryId,
                        security: self.settings.getEntryDetailsNonce
                    },
                    success: function(response) {
                        if (response.success) {
                            resolve(response.data);
                        } else {
                            reject(response.data);
                        }
                    },
                    error: function() {
                        reject('Failed to fetch entry details');
                    }
                });
            });
        },

        /**
         * Open View Modal
         */
        openViewModal: function(entryId) {
            const self = this;
            const data = this.getEntryData(entryId);
            if (!data) return;

            currentEntryData = data;

            $('#viewEntryModal .myavana-modal-content').html('<div class="modal-loading"><div class="modal-spinner"></div></div>');
            $('#viewEntryModal').addClass('active');
            $('body').css('overflow', 'hidden');

            this.fetchEntryDetails(entryId).then(detailedData => {
                const fullData = { ...data, ...detailedData };
                currentEntryData = fullData;

                $('#viewEntryModal .myavana-modal-content').html(`
                    <div class="view-entry-content">
                        <img class="view-entry-image" id="viewEntryImage" src="" alt="Hair progress" style="display: none;">

                        <div class="view-entry-meta">
                            <div class="view-entry-meta-item">
                                <div class="view-entry-meta-label">Health Rating</div>
                                <div class="view-entry-meta-value" id="viewEntryRating">5/5</div>
                            </div>
                            <div class="view-entry-meta-item">
                                <div class="view-entry-meta-label">Date Added</div>
                                <div class="view-entry-meta-value" id="viewEntryDate">Today</div>
                            </div>
                            <div class="view-entry-meta-item">
                                <div class="view-entry-meta-label">Mood</div>
                                <div class="view-entry-meta-value" id="viewEntryMood">Happy</div>
                            </div>
                            <div class="view-entry-meta-item">
                                <div class="view-entry-meta-label">Environment</div>
                                <div class="view-entry-meta-value" id="viewEntryEnvironment">Home</div>
                            </div>
                            <div class="view-entry-meta-item">
                                <div class="view-entry-meta-label">Completeness</div>
                                <div class="view-entry-meta-value" id="viewEntryCompleteness">${fullData.completeness}%</div>
                            </div>
                            <div class="view-entry-meta-item">
                                <div class="view-entry-meta-label">Time Ago</div>
                                <div class="view-entry-meta-value" id="viewEntryTimeAgo">${fullData.timeAgo}</div>
                            </div>
                        </div>

                        <div>
                            <h3 id="viewEntryTitle" style="color: var(--onyx); margin-bottom: var(--space-2);"></h3>
                            <div class="view-entry-description" id="viewEntryDescription"></div>
                        </div>

                        <div id="viewEntryProductsContainer" style="display: none;">
                            <h4 style="color: var(--coral); margin-bottom: var(--space-2);">Products Used</h4>
                            <div class="view-entry-tags" id="viewEntryProducts"></div>
                        </div>

                        <div id="viewEntryAITagsContainer" style="display: none;">
                            <h4 style="color: var(--coral); margin-bottom: var(--space-2);">AI Analysis Tags</h4>
                            <div class="view-entry-tags" id="viewEntryAITags"></div>
                        </div>

                        <div id="viewEntryNotesContainer" style="display: none;">
                            <h4 style="color: var(--coral); margin-bottom: var(--space-2);">Stylist Notes</h4>
                            <div class="view-entry-description" id="viewEntryNotes"></div>
                        </div>

                        <div class="entry-navigation" style="display: flex; justify-content: space-between; margin-top: var(--space-4); padding: var(--space-3); background: var(--stone); border-radius: var(--radius-md);">
                            <button class="myavana-modal-btn secondary" id="previousEntry" title="Previous Entry (← Arrow Key)">
                                <i class="fas fa-chevron-left"></i> Previous
                            </button>
                            <span style="color: var(--myavana-blueberry); font-size: 14px; align-self: center;">Entry ${self.getCurrentEntryIndex(entryId) + 1} of ${self.getTotalEntries()}</span>
                            <button class="myavana-modal-btn secondary" id="nextEntry" title="Next Entry (→ Arrow Key)">
                                Next <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                `);

                self.populateViewModal(fullData);
                self.setupEntryNavigation(entryId);

            }).catch(error => {
                console.error('Error fetching entry details:', error);
                self.populateViewModal(data);
                self.setupEntryNavigation(entryId);
            });
        },

        /**
         * Populate view modal with data
         */
        populateViewModal: function(data) {
            $('#viewEntryTitle').text(data.title);
            $('#viewEntryDescription').text(data.description);
            $('#viewEntryRating').text(data.rating + '/5');
            $('#viewEntryDate').text(data.date);
            $('#viewEntryMood').text(data.mood);
            $('#viewEntryEnvironment').text(data.environment);

            if (data.image) {
                $('#viewEntryImage').attr('src', data.image).show();
            } else {
                $('#viewEntryImage').hide();
            }

            if (data.products) {
                const products = data.products.split(',').map(p => p.trim()).filter(p => p);
                if (products.length > 0) {
                    $('#viewEntryProducts').html(products.map(p => `<span class="view-entry-tag">${p}</span>`).join(''));
                    $('#viewEntryProductsContainer').show();
                } else {
                    $('#viewEntryProductsContainer').hide();
                }
            } else {
                $('#viewEntryProductsContainer').hide();
            }

            if (data.aiTags && data.aiTags.length > 0) {
                $('#viewEntryAITags').html(data.aiTags.map(tag => `<span class="view-entry-tag">${tag}</span>`).join(''));
                $('#viewEntryAITagsContainer').show();
            } else {
                $('#viewEntryAITagsContainer').hide();
            }

            if (data.notes) {
                $('#viewEntryNotes').text(data.notes);
                $('#viewEntryNotesContainer').show();
            } else {
                $('#viewEntryNotesContainer').hide();
            }
        },

        /**
         * Get current entry index
         */
        getCurrentEntryIndex: function(entryId) {
            const allEntries = $('.main-entry');
            return allEntries.index($(`.main-entry[data-entry-id="${entryId}"]`));
        },

        /**
         * Get total number of entries
         */
        getTotalEntries: function() {
            return $('.main-entry').length;
        },

        /**
         * Setup entry navigation
         */
        setupEntryNavigation: function(currentEntryId) {
            const self = this;
            const currentIndex = this.getCurrentEntryIndex(currentEntryId);
            const totalEntries = this.getTotalEntries();

            $('#previousEntry').prop('disabled', currentIndex === 0);
            $('#nextEntry').prop('disabled', currentIndex === totalEntries - 1);

            $('#previousEntry').off('click').on('click', function() {
                if (currentIndex > 0) {
                    const prevEntryId = $('.main-entry').eq(currentIndex - 1).data('entry-id');
                    self.openViewModal(prevEntryId);
                }
            });

            $('#nextEntry').off('click').on('click', function() {
                if (currentIndex < totalEntries - 1) {
                    const nextEntryId = $('.main-entry').eq(currentIndex + 1).data('entry-id');
                    self.openViewModal(nextEntryId);
                }
            });
        },

        /**
         * Open Edit Modal
         */
        openEditModal: function(entryId) {
            const data = this.getEntryData(entryId);
            if (!data) return;

            currentEntryData = data;

            $('#editEntryId').val(data.id);
            $('#editEntryTitleInput').val(data.title);
            $('#editEntryDescriptionInput').val(data.description);
            $('#editEntryProductsInput').val(data.products);
            $('#editEntryNotesInput').val(data.notes);
            $('#editEntryRatingInput').val(data.rating);
            $('#editEntryMoodInput').val(data.mood);
            $('#editEntryEnvironmentInput').val(data.environment);

            if (data.image) {
                $('#editCurrentImage').attr('src', data.image).show();
                $('#editImageUpload').addClass('has-image');
                $('#editImageUpload .edit-upload-text').text('Click to change current photo');
            } else {
                $('#editCurrentImage').hide();
                $('#editImageUpload').removeClass('has-image');
                $('#editImageUpload .edit-upload-text').text('Click to upload photo or drag & drop');
            }

            $('#editEntryModal').addClass('active');
            $('body').css('overflow', 'hidden');
        },

        /**
         * Handle edit image change
         */
        handleEditImageChange: function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    $('#editCurrentImage').attr('src', e.target.result).show();
                    $('#editImageUpload').addClass('has-image');
                    $('#editImageUpload .edit-upload-text').text('Click to change current photo');
                };
                reader.readAsDataURL(file);
            }
        },

        /**
         * Handle edit entry form submission
         */
        handleEditEntry: function(e) {
            e.preventDefault();
            const self = this;

            const formData = new FormData();
            formData.append('action', 'myavana_update_entry');
            formData.append('entry_id', $('#editEntryId').val());
            formData.append('title', $('#editEntryTitleInput').val());
            formData.append('description', $('#editEntryDescriptionInput').val());
            formData.append('products', $('#editEntryProductsInput').val());
            formData.append('notes', $('#editEntryNotesInput').val());
            formData.append('rating', $('#editEntryRatingInput').val());
            formData.append('mood', $('#editEntryMoodInput').val());
            formData.append('environment', $('#editEntryEnvironmentInput').val());
            formData.append('security', self.settings.updateEntryNonce);

            const imageFile = $('#editImageInput')[0].files[0];
            if (imageFile) {
                formData.append('photo', imageFile);
            }

            $.ajax({
                url: self.settings.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        self.closeModal('editEntryModal');
                        self.showNotification('Entry updated successfully! ✨', 'success');

                        const currentIndex = self.getCurrentEntryIndex(currentEntryData.id);
                        self.loadEntries().then(() => {
                            if (self.splide && currentIndex >= 0) {
                                setTimeout(() => self.splide.go(currentIndex), 100);
                            }
                        });
                    } else {
                        self.showNotification('Error updating entry: ' + response.data, 'error');
                    }
                },
                error: function() {
                    alert('Error updating entry. Please try again.');
                }
            });
        },

        /**
         * Open Share Modal
         */
        openShareModal: function(entryId) {
            const data = this.getEntryData(entryId);
            if (!data) return;

            currentEntryData = data;

            $('#sharePreviewTitle').text(data.title || 'My Hair Journey Progress');
            $('#sharePreviewText').text(`Check out my hair transformation progress! "${data.description}" #MyHairJourney #MYAVANA`);

            $('#shareEntryModal').addClass('active');
            $('body').css('overflow', 'hidden');
        },

        /**
         * Share to Facebook
         */
        shareToFacebook: function() {
            const url = encodeURIComponent(window.location.href + '?entry=' + currentEntryData.id);
            const text = encodeURIComponent($('#sharePreviewText').text());
            window.open(`https://www.facebook.com/sharer/sharer.php?u=${url}&quote=${text}`, '_blank');
            this.showShareSuccess('Facebook');
            setTimeout(() => this.closeModal('shareEntryModal'), 1000);
        },

        /**
         * Share to Twitter
         */
        shareToTwitter: function() {
            const url = encodeURIComponent(window.location.href + '?entry=' + currentEntryData.id);
            const text = encodeURIComponent($('#sharePreviewText').text());
            window.open(`https://twitter.com/intent/tweet?text=${text}&url=${url}`, '_blank');
            this.showShareSuccess('Twitter');
            setTimeout(() => this.closeModal('shareEntryModal'), 1000);
        },

        /**
         * Share to Instagram
         */
        shareToInstagram: function() {
            this.showNotification('Instagram sharing: Copy the link and share manually in the Instagram app! 📱', 'info', 6000);
        },

        /**
         * Copy share link
         */
        copyShareLink: function() {
            const self = this;
            const shareText = $('#sharePreviewText').text() + ' ' + window.location.href + '?entry=' + currentEntryData.id;

            if (navigator.clipboard) {
                navigator.clipboard.writeText(shareText).then(function() {
                    self.showNotification('Share text and link copied to clipboard! 📋', 'success');
                    setTimeout(() => self.closeModal('shareEntryModal'), 1000);
                });
            } else {
                const textArea = document.createElement('textarea');
                textArea.value = shareText;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                self.showNotification('Share text and link copied to clipboard! 📋', 'success');
                setTimeout(() => self.closeModal('shareEntryModal'), 1000);
            }
        },

        /**
         * Open Delete Modal
         */
        openDeleteModal: function(entryId) {
            const data = this.getEntryData(entryId);
            if (!data) return;

            currentEntryData = data;

            $('#deleteEntryId').val(data.id);
            $('#deleteEntryTitle').text(data.title);
            $('#deleteEntryDate').text(data.date);

            if (data.image) {
                $('#deleteEntryImage').attr('src', data.image).show();
            } else {
                $('#deleteEntryImage').hide();
            }

            $('#deleteEntryModal').addClass('active');
            $('body').css('overflow', 'hidden');
        },

        /**
         * Confirm delete entry
         */
        confirmDeleteEntry: function() {
            const self = this;
            const entryId = $('#deleteEntryId').val();

            $.ajax({
                url: self.settings.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'myavana_delete_entry',
                    entry_id: entryId,
                    security: self.settings.deleteEntryNonce
                },
                success: function(response) {
                    if (response.success) {
                        self.closeModal('deleteEntryModal');
                        self.showNotification('Entry deleted successfully! 🗑️', 'success');
                        self.loadEntries();
                    } else {
                        self.showNotification('Error deleting entry: ' + response.data, 'error');
                    }
                },
                error: function() {
                    alert('Error deleting entry. Please try again.');
                }
            });
        },

        /**
         * Handle add entry form submission
         */
        handleAddEntry: function(e, $form) {
            e.preventDefault();
            const self = this;

            const formData = new FormData($form[0]);
            formData.append('action', 'myavana_add_entry');
            formData.append('security', self.settings.addEntryNonce);

            $.ajax({
                url: self.settings.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        self.showSuccess(response.data.message);
                        $form[0].reset();
                        if(self.pond) self.pond.removeFiles();
                        if(response.data.tip) {
                            setTimeout(() => {
                                alert('AI Tip: ' + response.data.tip);
                            }, 1000);
                        }
                        // Reload page to show new entry in timeline
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        self.showError(response.data);
                    }
                },
                error: function() {
                    self.showError('An error occurred. Please try again.');
                }
            });
        },

        /**
         * Show error message
         */
        showError: function(message) {
            $('#error-message').text(message).show();
            $('#success-message').hide();
        },

        /**
         * Show success message
         */
        showSuccess: function(message) {
            $('#success-message').text(message).show();
            $('#error-message').hide();
            setTimeout(() => $('#success-message').hide(), 3000);
        },

        /**
         * Close modal
         */
        closeModal: function(modalId) {
            $(`#${modalId}`).removeClass('active');
            $('body').css('overflow', 'auto');
            currentEntryData = null;
        },

        /**
         * Handle keyboard shortcuts
         */
        handleKeyboardShortcuts: function(e) {
            if (!$('.myavana-timeline-modal-overlay.active').length) return;

            switch(e.keyCode) {
                case 27: // Escape key
                    $('.myavana-timeline-modal-overlay.active').removeClass('active');
                    $('body').css('overflow', 'auto');
                    currentEntryData = null;
                    break;
                case 37: // Left arrow
                    e.preventDefault();
                    if ($('#viewEntryModal').hasClass('active')) {
                        $('#previousEntry').click();
                    }
                    break;
                case 39: // Right arrow
                    e.preventDefault();
                    if ($('#viewEntryModal').hasClass('active')) {
                        $('#nextEntry').click();
                    }
                    break;
                case 69: // E key for Edit
                    if (e.ctrlKey || e.metaKey) {
                        e.preventDefault();
                        if ($('#viewEntryModal').hasClass('active') && currentEntryData) {
                            this.openEditModal(currentEntryData.id);
                        }
                    }
                    break;
                case 83: // S key for Share
                    if (e.ctrlKey || e.metaKey) {
                        e.preventDefault();
                        if ($('#viewEntryModal').hasClass('active') && currentEntryData) {
                            this.openShareModal(currentEntryData.id);
                        }
                    }
                    break;
            }
        },

        /**
         * Setup double-click events
         */
        setupDoubleClickEvents: function() {
            const self = this;
            $('.main-entry').off('dblclick.modal').on('dblclick.modal', function() {
                const entryId = $(this).data('entry-id');
                self.openViewModal(entryId);
            });
        },

        /**
         * Sync timeline with modal
         */
        syncTimelineWithModal: function(entryId) {
            const entryIndex = this.getCurrentEntryIndex(entryId);
            if (this.splide && entryIndex >= 0) {
                this.splide.go(entryIndex);
            }
        },

        /**
         * Clear entry cache
         */
        clearEntryCache: function() {
            entryDataCache.clear();
            this.hideQuickPreview();
        },

        /**
         * Hide quick preview
         */
        hideQuickPreview: function() {
            if (this.quickPreviewElement) {
                this.quickPreviewElement.css({
                    opacity: 0,
                    visibility: 'hidden'
                });
            }
        },

        /**
         * Show notification
         */
        showNotification: function(message, type = 'info', duration = 4000) {
            const notification = $(`
                <div class="myavana-notification ${type}" style="
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: ${type === 'success' ? 'var(--coral)' : type === 'error' ? '#dc3545' : 'var(--myavana-blueberry)'};
                    color: var(--white);
                    padding: var(--space-3) var(--space-4);
                    border-radius: var(--radius-lg);
                    box-shadow: 0 8px 25px rgba(34, 35, 35, 0.15);
                    z-index: 10001;
                    font-family: 'Archivo', sans-serif;
                    font-weight: 600;
                    opacity: 0;
                    transform: translateX(100%);
                    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
                    max-width: 350px;
                    backdrop-filter: blur(10px);
                ">
                    ${message}
                    <button style="
                        background: none;
                        border: none;
                        color: var(--white);
                        font-size: 18px;
                        margin-left: var(--space-2);
                        cursor: pointer;
                        opacity: 0.8;
                    " onclick="$(this).parent().remove()">&times;</button>
                </div>
            `);

            $('body').append(notification);

            setTimeout(() => {
                notification.css({
                    opacity: 1,
                    transform: 'translateX(0)'
                });
            }, 100);

            setTimeout(() => {
                notification.css({
                    opacity: 0,
                    transform: 'translateX(100%)'
                });
                setTimeout(() => notification.remove(), 400);
            }, duration);
        },

        /**
         * Show share success notification
         */
        showShareSuccess: function(platform) {
            this.showNotification(`Shared to ${platform} successfully! 🚀`, 'success');
        }
    };

    /**
     * Global functions for backward compatibility
     */
    window.viewFullEntry = function(entryId) {
        MyavanaTimeline.openViewModal(entryId);
    };

    window.shareEntry = function(entryId) {
        MyavanaTimeline.openShareModal(entryId);
    };

    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        MyavanaTimeline.init();
    });

    // Expose to global scope for external access
    window.MyavanaTimeline = MyavanaTimeline;

})(jQuery);
