/**
 * MYAVANA Free Hair Analysis JavaScript
 * Handles upload, analysis, and results display
 */

(function($) {
    'use strict';

    let uploadedImage = null;
    let imageData = null;

    // Initialize
    $(document).ready(function() {
        initFreeAnalysis();
    });

    function initFreeAnalysis() {
        // Load html2canvas library for export functionality
        if (!window.html2canvas) {
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js';
            document.head.appendChild(script);
        }

        // Open modal
        $('#startFreeAnalysisBtn').on('click', openModal);

        // Close modal
        $('#closeFreeAnalysisModal, #cancelUpload').on('click', closeModal);

        // Expand/Contract modal
        $('#expandModalBtn').on('click', toggleModalExpand);

        // Export results
        $('#exportResultsBtn').on('click', exportResults);

        // File input handler
        $('#hairPhotoInput').on('change', handleFileSelect);

        // Drag and drop
        const uploadArea = $('#uploadArea');
        uploadArea.on('dragover', function(e) {
            e.preventDefault();
            $(this).addClass('drag-over');
        });

        uploadArea.on('dragleave', function() {
            $(this).removeClass('drag-over');
        });

        uploadArea.on('drop', function(e) {
            e.preventDefault();
            $(this).removeClass('drag-over');
            const files = e.originalEvent.dataTransfer.files;
            if (files.length > 0) {
                handleFile(files[0]);
            }
        });

        // Analyze button
        $('#analyzeBtn').on('click', startAnalysis);
    }

    function openModal() {
        $('#freeAnalysisModal').addClass('active');
        $('body').css('overflow', 'hidden');
        resetModal();
    }

    function closeModal() {
        $('#freeAnalysisModal').removeClass('active');
        $('body').css('overflow', '');
        resetModal();
    }

    function resetModal() {
        $('.modal-step').removeClass('active');
        $('#uploadStep').addClass('active');
        $('#uploadPreview').hide();
        $('.upload-content').show();
        $('#analyzeBtn').prop('disabled', true);
        $('#exportResultsBtn').hide();
        $('.modal-container').removeClass('expanded');
        $('#expandModalBtn').html('<i class="fas fa-expand-alt"></i>').attr('title', 'Expand');
        uploadedImage = null;
        imageData = null;
    }

    function handleFileSelect(e) {
        const file = e.target.files[0];
        if (file) {
            handleFile(file);
        }
    }

    function handleFile(file) {
        // Validate file
        if (!file.type.match('image.*')) {
            alert('Please upload an image file');
            return;
        }

        if (file.size > 10 * 1024 * 1024) {
            alert('File size must be less than 10MB');
            return;
        }

        uploadedImage = file;

        // Show preview
        const reader = new FileReader();
        reader.onload = function(e) {
            $('#previewImage').attr('src', e.target.result);
            $('#uploadPreview').fadeIn();
            $('.upload-content').hide();
            $('#analyzeBtn').prop('disabled', false);

            // Store base64 for API
            imageData = e.target.result;
        };
        reader.readAsDataURL(file);
    }

    function startAnalysis() {
        if (!imageData) {
            alert('Please upload an image first');
            return;
        }

        // Switch to analyzing step
        $('.modal-step').removeClass('active');
        $('#analyzingStep').addClass('active');

        // Animate progress
        animateProgress();

        // Call API
        analyzeImage();
    }

    function animateProgress() {
        const progressTexts = [
            'Examining hair texture...',
            'Analyzing hair health...',
            'Identifying hair type...',
            'Generating recommendations...',
            'Finalizing analysis...'
        ];

        let progress = 0;
        const interval = setInterval(function() {
            progress += 20;
            $('#progressFill').css('width', progress + '%');
            $('#progressPercent').text(progress + '%');

            const textIndex = Math.floor(progress / 20) - 1;
            if (textIndex >= 0 && textIndex < progressTexts.length) {
                $('#analyzingText').text(progressTexts[textIndex]);
            }

            if (progress >= 100) {
                clearInterval(interval);
            }
        }, 1000);
    }

    function analyzeImage() {
        $.ajax({
            url: myavanaLuxuryData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'myavana_free_hair_analysis',
                image_data: imageData
            },
            success: function(response) {
                if (response.success) {
                    displayResults(response.data);
                } else {
                    if (response.data && response.data.limit_reached) {
                        displayLimitReached(response.data);
                    } else {
                        displayError(response.data ? response.data.message : 'Analysis failed');
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('Analysis error:', error);
                displayError('Network error. Please try again.');
            }
        });
    }

    function displayResults(data) {
        const analysis = data.analysis;
        const remaining = data.remaining_analyses;

        let resultsHTML = `
            <div class="results-header">
                <div class="results-icon">✨</div>
                <h3>Your Hair Analysis Results</h3>
                <p class="results-subtitle">AI-powered insights just for you</p>
            </div>

            <div class="results-grid">
                ${analysis.hair_type ? `
                <div class="result-card">
                    <div class="result-card-icon">🎯</div>
                    <h4>Hair Type</h4>
                    <p class="result-value">${analysis.hair_type}</p>
                    ${analysis.porosity ? `<p class="result-detail">Porosity: ${analysis.porosity}</p>` : ''}
                </div>
                ` : ''}

                ${analysis.health_score ? `
                <div class="result-card">
                    <div class="result-card-icon">💪</div>
                    <h4>Health Score</h4>
                    <p class="result-value">${analysis.health_score}/10</p>
                </div>
                ` : ''}

                ${analysis.concerns && analysis.concerns.length > 0 ? `
                <div class="result-card full-width">
                    <div class="result-card-icon">⚠️</div>
                    <h4>Areas of Concern</h4>
                    <ul class="result-list">
                        ${analysis.concerns.map(c => `<li>${c}</li>`).join('')}
                    </ul>
                </div>
                ` : ''}

                ${analysis.recommendations && analysis.recommendations.length > 0 ? `
                <div class="result-card full-width">
                    <div class="result-card-icon">💡</div>
                    <h4>Recommended Actions</h4>
                    <ol class="result-list numbered">
                        ${analysis.recommendations.map(r => `<li>${r}</li>`).join('')}
                    </ol>
                </div>
                ` : ''}

                ${analysis.products && analysis.products.length > 0 ? `
                <div class="result-card full-width">
                    <div class="result-card-icon">🛍️</div>
                    <h4>Product Suggestions</h4>
                    <ul class="result-list">
                        ${analysis.products.map(p => `<li>${p}</li>`).join('')}
                    </ul>
                </div>
                ` : ''}

                ${analysis.raw_analysis ? `
                <div class="result-card full-width">
                    <div class="result-card-icon">📋</div>
                    <h4>Complete Analysis</h4>
                    <div class="raw-analysis">${formatRawAnalysis(analysis.raw_analysis)}</div>
                </div>
                ` : ''}
            </div>

            <div class="results-footer">
                <div class="remaining-analyses">
                    ${remaining > 0
                        ? `<p>You have <strong>${remaining}</strong> free ${remaining === 1 ? 'analysis' : 'analyses'} remaining today!</p>`
                        : `<p>You've used all your free analyses for today.</p>`
                    }
                </div>

                <div class="signup-cta">
                    <h4>Want Unlimited Analyses?</h4>
                    <p>Sign up for FREE and get:</p>
                    <ul>
                        <li>✨ Unlimited AI hair analyses</li>
                        <li>📊 Track your hair journey over time</li>
                        <li>💬 Personalized recommendations</li>
                        <li>📸 Progress photos & timeline</li>
                        <li>🤝 Join a supportive community</li>
                    </ul>
                    <button class="signup-btn" onclick="showMyavanaModal('register')">
                        Sign Up Free - It Takes 30 Seconds!
                    </button>
                    <p class="signup-note">No credit card required • 100% free forever</p>
                </div>

                <button class="modal-btn-secondary" onclick="location.reload()">
                    Try Another Analysis
                </button>
            </div>
        `;

        $('#resultsContent').html(resultsHTML);
        $('.modal-step').removeClass('active');
        $('#resultsStep').addClass('active');

        // Show export button
        $('#exportResultsBtn').fadeIn();

        // Animate results
        $('.result-card').each(function(index) {
            $(this).css({
                opacity: 0,
                transform: 'translateY(20px)'
            }).delay(index * 100).animate({
                opacity: 1
            }, 300, function() {
                $(this).css('transform', 'translateY(0)');
            });
        });
    }

    function displayLimitReached(data) {
        const resultsHTML = `
            <div class="results-header error">
                <div class="results-icon">🚫</div>
                <h3>Daily Limit Reached</h3>
                <p class="results-subtitle">${data.message}</p>
            </div>

            <div class="limit-reached-content">
                <p>You've used all <strong>3 free analyses</strong> for today.</p>

                <div class="signup-cta prominent">
                    <h4>Get Unlimited Analyses</h4>
                    <p>Sign up now and enjoy:</p>
                    <ul>
                        <li>✨ <strong>Unlimited</strong> AI hair analyses</li>
                        <li>📊 Complete hair journey tracking</li>
                        <li>💬 24/7 AI hair care assistant</li>
                        <li>📸 Before & after timelines</li>
                        <li>🎯 Personalized product recommendations</li>
                    </ul>
                    <button class="signup-btn large" onclick="showMyavanaModal('register')">
                        Create Free Account Now
                    </button>
                    <p class="signup-note">Join 50,000+ users • No credit card needed</p>
                </div>
            </div>
        `;

        $('#resultsContent').html(resultsHTML);
        $('.modal-step').removeClass('active');
        $('#resultsStep').addClass('active');
    }

    function displayError(message) {
        const resultsHTML = `
            <div class="results-header error">
                <div class="results-icon">⚠️</div>
                <h3>Analysis Error</h3>
                <p class="results-subtitle">${message}</p>
            </div>

            <div class="error-actions">
                <button class="modal-btn-primary" onclick="location.reload()">
                    Try Again
                </button>
                <button class="modal-btn-secondary" onclick="$('#closeFreeAnalysisModal').click()">
                    Close
                </button>
            </div>
        `;

        $('#resultsContent').html(resultsHTML);
        $('.modal-step').removeClass('active');
        $('#resultsStep').addClass('active');
    }

    function formatRawAnalysis(text) {
        // Format markdown-style text to HTML
        return text
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\n/g, '<br>')
            .replace(/- (.*?)(<br>|$)/g, '<li>$1</li>');
    }

    function toggleModalExpand() {
        const $container = $('.modal-container');
        const $btn = $('#expandModalBtn');
        const isExpanded = $container.hasClass('expanded');

        if (isExpanded) {
            $container.removeClass('expanded');
            $btn.html('<i class="fas fa-expand-alt"></i>');
            $btn.attr('title', 'Expand');
        } else {
            $container.addClass('expanded');
            $btn.html('<i class="fas fa-compress-alt"></i>');
            $btn.attr('title', 'Contract');
        }
    }

    function exportResults() {
        const $resultsContent = $('#resultsContent');

        if (!$resultsContent.length || !window.html2canvas) {
            alert('Export functionality is not available. Please try again.');
            return;
        }

        // Show loading state
        const $exportBtn = $('#exportResultsBtn');
        const originalHTML = $exportBtn.html();
        $exportBtn.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);

        // Add MYAVANA branding to export
        const $exportWrapper = $('<div>').css({
            position: 'absolute',
            left: '-9999px',
            top: 0,
            background: '#ffffff',
            padding: '40px',
            width: '800px',
            fontFamily: 'Archivo, sans-serif'
        });

        const $brandHeader = $('<div>').css({
            textAlign: 'center',
            marginBottom: '30px',
            paddingBottom: '20px',
            borderBottom: '2px solid #e7a690'
        }).html(`
            <h2 style="font-family: 'Archivo Black', sans-serif; font-size: 32px; color: #222323; margin: 0 0 10px 0;">
                MYAVANA
            </h2>
            <p style="font-size: 14px; color: #4a4d68; margin: 0;">
                AI-Powered Hair Analysis Results
            </p>
            <p style="font-size: 12px; color: #999; margin: 5px 0 0 0;">
                ${new Date().toLocaleDateString()}
            </p>
        `);

        const $resultsClone = $resultsContent.clone();

        $exportWrapper.append($brandHeader);
        $exportWrapper.append($resultsClone);
        $('body').append($exportWrapper);

        // Generate image
        html2canvas($exportWrapper[0], {
            backgroundColor: '#ffffff',
            scale: 2,
            logging: false,
            useCORS: true
        }).then(canvas => {
            // Convert to blob and download
            canvas.toBlob(function(blob) {
                const url = URL.createObjectURL(blob);
                const link = document.createElement('a');
                const timestamp = new Date().getTime();
                link.download = `myavana-hair-analysis-${timestamp}.png`;
                link.href = url;
                link.click();
                URL.revokeObjectURL(url);

                // Cleanup
                $exportWrapper.remove();
                $exportBtn.html(originalHTML).prop('disabled', false);
            });
        }).catch(error => {
            console.error('Export error:', error);
            alert('Failed to export results. Please try again.');
            $exportWrapper.remove();
            $exportBtn.html(originalHTML).prop('disabled', false);
        });
    }

})(jQuery);