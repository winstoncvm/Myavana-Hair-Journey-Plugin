/**
 * MYAVANA Smart Entry AI Workflow
 * Camera-first entry creation with AI analysis pre-population
 * @version 2.3.5
 */

(function($) {
    'use strict';

    window.MyavanaSmartEntry = {
        currentImage: null,
        aiAnalysisData: null,

        init: function() {
            console.log('[Smart Entry] Initializing...');
            this.bindEvents();
        },

        bindEvents: function() {
            $(document).on('click', '#myavana-smart-entry-btn', this.openSmartEntry.bind(this));
            $(document).on('click', '#addAnalysisBtn', this.openSmartEntry.bind(this)); // AI Analysis tab button
            $(document).on('click', '#smart-entry-take-photo', this.takePhoto.bind(this));
            $(document).on('click', '#smart-entry-upload-photo', this.uploadPhoto.bind(this));
            $(document).on('change', '#smart-entry-file-input', this.handleFileSelect.bind(this));
            $(document).on('click', '#smart-entry-analyze', this.analyzePhoto.bind(this));
            $(document).on('click', '#smart-entry-use-results', this.useAIResults.bind(this));
            $(document).on('click', '#smart-entry-retake', this.retakePhoto.bind(this));
        },

        openSmartEntry: function(e) {
            e.preventDefault();
            console.log('[Smart Entry] Opening camera interface');

            const modalHTML = `
                <div class="myavana-smart-entry-modal" id="smartEntryModal">
                    <div class="smart-entry-content">
                        <div class="smart-entry-header">
                            <h2 class="smart-entry-title">✨ Smart Entry</h2>
                            <button class="smart-entry-close" onclick="MyavanaSmartEntry.closeModal()">&times;</button>
                        </div>

                        <div class="smart-entry-body">
                            <!-- Step 1: Photo Capture -->
                            <div class="smart-entry-step" id="step-capture" style="display: block;">
                                <div class="camera-prompt">
                                    <div class="camera-icon">📸</div>
                                    <h3>Capture Your Hair Journey</h3>
                                    <p>Take a photo or upload one for instant AI analysis</p>
                                </div>

                                <div class="camera-actions">
                                    <button class="btn-camera" id="smart-entry-take-photo">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
                                            <circle cx="12" cy="13" r="4"/>
                                        </svg>
                                        Take Photo
                                    </button>
                                    <button class="btn-upload" id="smart-entry-upload-photo">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                            <polyline points="17 8 12 3 7 8"/>
                                            <line x1="12" y1="3" x2="12" y2="15"/>
                                        </svg>
                                        Upload Photo
                                    </button>
                                </div>

                                <input type="file" id="smart-entry-file-input" accept="image/*" style="display: none;">
                                <video id="smart-entry-video" autoplay playsinline style="display: none; width: 100%; border-radius: 12px;"></video>
                                <canvas id="smart-entry-canvas" style="display: none;"></canvas>
                            </div>

                            <!-- Step 2: Photo Preview & Analysis -->
                            <div class="smart-entry-step" id="step-preview" style="display: none;">
                                <div class="photo-preview-container">
                                    <img id="smart-entry-preview" src="" alt="Hair photo preview">
                                </div>

                                <div class="preview-actions">
                                    <button class="btn-secondary" id="smart-entry-retake">Retake Photo</button>
                                    <button class="btn-primary" id="smart-entry-analyze">
                                        <span class="analyze-text">Analyze with AI</span>
                                        <span class="analyze-loader" style="display: none;">
                                            <div class="spinner-small"></div>
                                            Analyzing...
                                        </span>
                                    </button>
                                </div>
                            </div>

                            <!-- Step 3: AI Results -->
                            <div class="smart-entry-step" id="step-results" style="display: none;">
                                <div class="ai-results-container">
                                    <div class="results-header">
                                        <div class="results-icon">🧠</div>
                                        <h3>AI Analysis Complete</h3>
                                    </div>

                                    <div id="ai-results-content" class="results-content">
                                        <!-- Dynamic AI results inserted here -->
                                    </div>

                                    <div class="results-actions">
                                        <button class="btn-secondary" id="smart-entry-retake">Try Another Photo</button>
                                        <button class="btn-primary" id="smart-entry-use-results">
                                            Create Entry
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <polyline points="9 18 15 12 9 6"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            $('body').append(modalHTML);
            setTimeout(() => $('#smartEntryModal').addClass('visible'), 10);
        },

        takePhoto: function(e) {
            e.preventDefault();
            console.log('[Smart Entry] Requesting camera access');

            const video = document.getElementById('smart-entry-video');
            const constraints = {
                video: {
                    facingMode: 'user',
                    width: { ideal: 1280 },
                    height: { ideal: 720 }
                }
            };

            navigator.mediaDevices.getUserMedia(constraints)
                .then(stream => {
                    video.srcObject = stream;
                    video.style.display = 'block';
                    $('.camera-prompt, .camera-actions').hide();

                    // Add capture button overlay
                    const captureBtn = $('<button class="btn-capture-overlay">Capture</button>');
                    captureBtn.on('click', () => this.captureFrame(video, stream));
                    $('#step-capture').append(captureBtn);
                })
                .catch(err => {
                    console.error('[Smart Entry] Camera access denied:', err);
                    this.showNotification('Camera access denied. Please use upload instead.', 'error');
                });
        },

        uploadPhoto: function(e) {
            e.preventDefault();
            $('#smart-entry-file-input').click();
        },

        handleFileSelect: function(e) {
            const file = e.target.files[0];
            if (!file) return;

            if (!file.type.startsWith('image/')) {
                this.showNotification('Please select an image file', 'error');
                return;
            }

            if (file.size > 5 * 1024 * 1024) {
                this.showNotification('Image size must be less than 5MB', 'error');
                return;
            }

            const reader = new FileReader();
            reader.onload = (e) => {
                this.currentImage = e.target.result;
                this.showPreview(e.target.result);
            };
            reader.readAsDataURL(file);
        },

        captureFrame: function(video, stream) {
            const canvas = document.getElementById('smart-entry-canvas');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;

            const ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

            this.currentImage = canvas.toDataURL('image/jpeg', 0.9);

            // Stop camera stream
            stream.getTracks().forEach(track => track.stop());
            video.style.display = 'none';
            $('.btn-capture-overlay').remove();

            this.showPreview(this.currentImage);
        },

        showPreview: function(imageSrc) {
            $('#smart-entry-preview').attr('src', imageSrc);
            $('#step-capture').hide();
            $('#step-preview').show();
        },

        analyzePhoto: async function(e) {
            e.preventDefault();

            if (!this.currentImage) {
                this.showNotification('No image to analyze', 'error');
                return;
            }

            const $btn = $(e.currentTarget);
            $btn.prop('disabled', true);
            $('.analyze-text').hide();
            $('.analyze-loader').show();

            try {
                const response = await $.ajax({
                    url: myavanaGamificationSettings.ajaxUrl,
                    method: 'POST',
                    data: {
                        action: 'myavana_smart_entry_analyze',
                        nonce: myavanaGamificationSettings.nonce,
                        image_data: this.currentImage
                    }
                });

                if (response.success && response.data) {
                    this.aiAnalysisData = response.data;
                    this.displayAIResults(response.data);
                    $('#step-preview').hide();
                    $('#step-results').show();
                } else {
                    throw new Error(response.data?.message || 'Analysis failed');
                }
            } catch (error) {
                console.error('[Smart Entry] Analysis error:', error);
                this.showNotification('AI analysis failed. Please try again.', 'error');
                $btn.prop('disabled', false);
                $('.analyze-text').show();
                $('.analyze-loader').hide();
            }
        },

        displayAIResults: function(data) {
            const resultsHTML = `
                <div class="result-card">
                    <div class="result-header">
                        <span class="result-label">Hair Health Score</span>
                        <span class="result-value health-score-${Math.ceil(data.health_rating / 2)}">${data.health_rating}/10</span>
                    </div>
                    <div class="health-bar">
                        <div class="health-bar-fill" style="width: ${data.health_rating * 10}%"></div>
                    </div>
                </div>

                <div class="result-card">
                    <div class="result-header">
                        <span class="result-label">Hair Type Detected</span>
                        <span class="result-value">${data.hair_type || 'Unknown'}</span>
                    </div>
                </div>

                <div class="result-card full-width">
                    <div class="result-label">AI Analysis Summary</div>
                    <p class="result-summary">${data.analysis_summary}</p>
                </div>

                ${data.recommendations ? `
                    <div class="result-card full-width">
                        <div class="result-label">Recommendations</div>
                        <ul class="recommendations-list">
                            ${data.recommendations.map(rec => `<li>${rec}</li>`).join('')}
                        </ul>
                    </div>
                ` : ''}
            `;

            $('#ai-results-content').html(resultsHTML);
        },

        useAIResults: function(e) {
            e.preventDefault();

            if (!this.aiAnalysisData) {
                this.showNotification('No AI analysis data available', 'error');
                return;
            }

            console.log('[Smart Entry] Pre-populating entry form with AI data');

            // Close smart entry modal
            this.closeModal();

            // Open regular entry form
            if (typeof openOffcanvas === 'function') {
                openOffcanvas('entry');
            }

            // Wait for form to render, then populate
            setTimeout(() => {
                const today = new Date().toISOString().split('T')[0];

                $('#entry_title').val(`Hair Analysis - ${new Date().toLocaleDateString()}`);
                $('#entry_date').val(today);
                $('#entry_category').val('progress');
                $('#entry_content').val(this.aiAnalysisData.analysis_summary || '');
                $('#health_rating').val(this.aiAnalysisData.health_rating || 5);

                // Update star rating display
                $('.rating-star').removeClass('active');
                for (let i = 1; i <= this.aiAnalysisData.health_rating; i++) {
                    $(`.rating-star[data-rating="${i}"]`).addClass('active');
                }

                // Store AI data for submission
                window.myavanaSmartEntryData = {
                    ai_analysis: this.aiAnalysisData,
                    image_data: this.currentImage
                };

                this.showNotification('✨ Form pre-filled with AI analysis', 'success');
            }, 500);
        },

        retakePhoto: function(e) {
            e.preventDefault();
            this.currentImage = null;
            this.aiAnalysisData = null;

            $('#step-preview, #step-results').hide();
            $('#step-capture').show();
            $('.camera-prompt, .camera-actions').show();
            $('#smart-entry-video').hide();
            $('.btn-capture-overlay').remove();
        },

        closeModal: function() {
            $('#smartEntryModal').removeClass('visible');
            setTimeout(() => {
                $('#smartEntryModal').remove();

                // Stop any active camera streams
                const video = document.getElementById('smart-entry-video');
                if (video && video.srcObject) {
                    video.srcObject.getTracks().forEach(track => track.stop());
                }
            }, 300);
        },

        showNotification: function(message, type = 'info') {
            const iconMap = {
                success: '✓',
                error: '✗',
                info: 'ℹ'
            };

            const notification = $(`
                <div class="myavana-notification ${type}">
                    <span class="notification-icon">${iconMap[type]}</span>
                    <span class="notification-message">${message}</span>
                </div>
            `);

            $('body').append(notification);
            setTimeout(() => notification.addClass('visible'), 10);

            setTimeout(() => {
                notification.removeClass('visible');
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        MyavanaSmartEntry.init();
    });

})(jQuery);
