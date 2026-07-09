/**
 * MYAVANA Free Hair Analysis JavaScript
 * Handles upload, analysis, and results display
 */

(function($) {
    'use strict';

    let uploadedImage = null;
    let imageData = null;
    let latestAnalysisResult = null;

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
        $('#freeAnalysisModal .modal-overlay').on('click', closeModal);

        // Expand/Contract modal
        $('#expandModalBtn').on('click', toggleModalExpand);

        // Export results
        $('#exportResultsBtn').on('click', exportResults);
        $(document).on('click', '#shareFreeResultsBtn', shareFreeResults);
        $(document).on('click', '#copyFreeSummaryBtn', copyFreeSummary);
        $(document).on('click', '.myavana-free-social-btn', function() {
            const network = $(this).data('network');
            shareFreeTo(network);
        });

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

        // Keyboard close support
        $(document).on('keydown.myavanaFreeAnalysis', function(e) {
            if (e.key === 'Escape' && $('#freeAnalysisModal').hasClass('active')) {
                closeModal();
            }
        });
    }

    function openModal() {
        window.location.href = window.myavanaAiToolUrl || 'https://www.myavana.com/pages/consumer';
    }

    function closeModal() {
        $('#freeAnalysisModal').removeClass('active');
        $('body').removeClass('myavana-free-analysis-open');
        resetModal();
    }

    function resetModal() {
        const $modal = $('#freeAnalysisModal');
        $modal.find('.modal-step').removeClass('active');
        $modal.find('#uploadStep').addClass('active');
        $modal.find('#uploadPreview').hide();
        $modal.find('.upload-content').show();
        $modal.find('#analyzeBtn').prop('disabled', true);
        $modal.find('#exportResultsBtn').hide();
        $modal.find('.modal-container').removeClass('expanded');
        $('#expandModalBtn').html('<i class="fas fa-expand-alt"></i>').attr('title', 'Expand');
        uploadedImage = null;
        imageData = null;
        latestAnalysisResult = null;
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
        const $modal = $('#freeAnalysisModal');
        $modal.find('.modal-step').removeClass('active');
        $modal.find('#analyzingStep').addClass('active');

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
        const analysis = normalizeAnalysisData(data.analysis || {});
        const remaining = data.remaining_analyses;
        latestAnalysisResult = analysis;

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
                    <p class="result-value">${escapeHtml(analysis.hair_type)}</p>
                    ${analysis.curl_pattern ? `<p class="result-detail">Curl Pattern: ${escapeHtml(analysis.curl_pattern)}</p>` : ''}
                    ${analysis.porosity ? `<p class="result-detail">Porosity: ${escapeHtml(analysis.porosity)}</p>` : ''}
                </div>
                ` : ''}

                ${analysis.health_score ? `
                <div class="result-card">
                    <div class="result-card-icon">💪</div>
                    <h4>Health Score</h4>
                    <p class="result-value">${analysis.health_score}/10</p>
                    ${analysis.hydration ? `<p class="result-detail">Hydration: ${analysis.hydration}%</p>` : ''}
                </div>
                ` : ''}

                ${(analysis.texture || analysis.density || analysis.length) ? `
                <div class="result-card">
                    <div class="result-card-icon">🧬</div>
                    <h4>Hair Structure</h4>
                    ${analysis.texture ? `<p class="result-detail">Texture: ${escapeHtml(analysis.texture)}</p>` : ''}
                    ${analysis.density ? `<p class="result-detail">Density: ${escapeHtml(analysis.density)}</p>` : ''}
                    ${analysis.length ? `<p class="result-detail">Length: ${escapeHtml(analysis.length)}</p>` : ''}
                </div>
                ` : ''}

                ${analysis.concerns && analysis.concerns.length > 0 ? `
                <div class="result-card full-width">
                    <div class="result-card-icon">⚠️</div>
                    <h4>Areas of Concern</h4>
                    <ul class="result-list">
                        ${analysis.concerns.map(c => `<li>${escapeHtml(c)}</li>`).join('')}
                    </ul>
                </div>
                ` : ''}

                ${analysis.recommendations && analysis.recommendations.length > 0 ? `
                <div class="result-card full-width">
                    <div class="result-card-icon">💡</div>
                    <h4>Recommended Actions</h4>
                    <ol class="result-list numbered">
                        ${analysis.recommendations.map(r => `<li>${escapeHtml(r)}</li>`).join('')}
                    </ol>
                </div>
                ` : ''}

                ${analysis.products && analysis.products.length > 0 ? `
                <div class="result-card full-width">
                    <div class="result-card-icon">🛍️</div>
                    <h4>Product Suggestions</h4>
                    <ul class="result-list">
                        ${analysis.products.map(p => `<li>${escapeHtml(p)}</li>`).join('')}
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
                <div class="myavana-free-share">
                    <h4>Share Your Results</h4>
                    <p>Post your progress and inspire your community.</p>
                    <div class="myavana-free-share-actions">
                        <button class="modal-btn-secondary" id="shareFreeResultsBtn">Share</button>
                        <button class="modal-btn-secondary" id="copyFreeSummaryBtn">Copy Summary</button>
                    </div>
                    <div class="myavana-free-social-actions">
                        <button class="myavana-free-social-btn" data-network="x">X</button>
                        <button class="myavana-free-social-btn" data-network="facebook">Facebook</button>
                        <button class="myavana-free-social-btn" data-network="linkedin">LinkedIn</button>
                    </div>
                </div>

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
        const $modal = $('#freeAnalysisModal');
        $modal.find('.modal-step').removeClass('active');
        $modal.find('#resultsStep').addClass('active');

        // Show export button
        $modal.find('#exportResultsBtn').fadeIn();

        // Animate results
        $modal.find('.result-card').each(function(index) {
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
        const $modal = $('#freeAnalysisModal');
        $modal.find('.modal-step').removeClass('active');
        $modal.find('#resultsStep').addClass('active');
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
        const $modal = $('#freeAnalysisModal');
        $modal.find('.modal-step').removeClass('active');
        $modal.find('#resultsStep').addClass('active');
    }

    function formatRawAnalysis(text) {
        // Format markdown-style text to HTML
        const safeText = escapeHtml(text || '');
        return safeText
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\n/g, '<br>')
            .replace(/- (.*?)(<br>|$)/g, '<li>$1</li>');
    }

    function normalizeAnalysisData(analysis) {
        const data = analysis && typeof analysis === 'object' ? analysis : {};
        const nested = data.hair_analysis && typeof data.hair_analysis === 'object' ? data.hair_analysis : {};

        return {
            hair_type: data.hair_type || nested.type || 'Not determined',
            curl_pattern: data.curl_pattern || nested.curl_pattern || '',
            porosity: data.porosity || nested.porosity || '',
            texture: data.texture || nested.texture || '',
            density: data.density || nested.density || '',
            length: data.length || nested.length || '',
            health_score: normalizeHealthScore(data.health_score || nested.health_score || 0),
            hydration: parseInt(data.hydration || nested.hydration || 0, 10) || 0,
            elasticity: parseInt(data.elasticity || nested.elasticity || 0, 10) || 0,
            concerns: normalizeStringArray(data.concerns),
            recommendations: normalizeStringArray(data.recommendations),
            products: normalizeStringArray(data.products),
            summary: data.summary || '',
            raw_analysis: data.raw_analysis || ''
        };
    }

    function normalizeHealthScore(rawScore) {
        const score = parseInt(rawScore, 10);
        if (!score || Number.isNaN(score)) {
            return 0;
        }

        if (score <= 10) {
            return score;
        }

        return Math.max(1, Math.min(10, Math.round(score / 10)));
    }

    function normalizeStringArray(value) {
        if (!Array.isArray(value)) {
            return [];
        }

        return value.map(item => {
            if (typeof item === 'string') {
                return item;
            }
            if (item && typeof item === 'object' && item.name) {
                return String(item.name);
            }
            return '';
        }).filter(Boolean);
    }

    function buildShareText() {
        if (!latestAnalysisResult) {
            return '';
        }

        const a = latestAnalysisResult;
        const parts = [
            `I just used MYAVANA AI Hair Analysis.`,
            `Hair type: ${a.hair_type}.`
        ];

        if (a.health_score) {
            parts.push(`Health score: ${a.health_score}/10.`);
        }

        if (a.summary) {
            parts.push(a.summary);
        } else if (a.recommendations && a.recommendations[0]) {
            parts.push(`Top recommendation: ${a.recommendations[0]}`);
        }

        return parts.join(' ');
    }

    function shareFreeResults() {
        const shareText = buildShareText();
        if (!shareText) {
            return;
        }

        if (navigator.share) {
            navigator.share({
                title: 'MYAVANA Free Hair Analysis',
                text: shareText,
                url: window.location.href
            }).catch(() => {});
            return;
        }

        shareFreeTo('x');
    }

    function shareFreeTo(network) {
        const shareText = encodeURIComponent(buildShareText());
        const shareUrl = encodeURIComponent(window.location.href);
        let url = '';

        if (network === 'x') {
            url = `https://twitter.com/intent/tweet?text=${shareText}&url=${shareUrl}`;
        } else if (network === 'facebook') {
            url = `https://www.facebook.com/sharer/sharer.php?u=${shareUrl}`;
        } else if (network === 'linkedin') {
            url = `https://www.linkedin.com/sharing/share-offsite/?url=${shareUrl}`;
        }

        if (url) {
            window.open(url, '_blank', 'width=640,height=700,noopener,noreferrer');
        }
    }

    function copyFreeSummary() {
        const shareText = buildShareText();
        if (!shareText) {
            return;
        }

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(shareText).then(() => {
                $('#copyFreeSummaryBtn').text('Copied!');
                setTimeout(() => $('#copyFreeSummaryBtn').text('Copy Summary'), 1200);
            }).catch(() => {
                fallbackCopyText(shareText);
            });
            return;
        }

        fallbackCopyText(shareText);
    }

    function fallbackCopyText(text) {
        const temp = document.createElement('textarea');
        temp.value = text;
        document.body.appendChild(temp);
        temp.select();
        document.execCommand('copy');
        document.body.removeChild(temp);
        $('#copyFreeSummaryBtn').text('Copied!');
        setTimeout(() => $('#copyFreeSummaryBtn').text('Copy Summary'), 1200);
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/\"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function toggleModalExpand() {
        if (window.matchMedia('(max-width: 768px)').matches) {
            return;
        }

        const $container = $('#freeAnalysisModal .modal-container');
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
