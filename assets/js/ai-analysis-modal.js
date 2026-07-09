/**
 * MYAVANA AI Hair Analysis Modal
 * Mobile-first modal for logged-in AI analysis flow.
 */
(function($) {
    'use strict';

    const EXTERNAL_AI_TOOL_URL = 'https://www.myavana.com/pages/consumer';

    const STATE = {
        imageData: '',
        cameraStream: null,
        progressInterval: null,
        latestAnalysis: null,
        latestResponse: null,
        eventsBound: false
    };

    const STEPS = {
        terms: 'terms',
        upload: 'upload',
        analyzing: 'analyzing',
        results: 'results'
    };

    const SELECTORS = {
        modal: '#myavanaAIModal',
        content: '#myavanaAiModalContent'
    };

    window.myavanaAiToolUrl = window.myavanaAiToolUrl || EXTERNAL_AI_TOOL_URL;
    window.openAIAnalysisModal = openExternalAiTool;
    window.openInternalAIAnalysisModal = openModal;
    window.closeAIModal = closeModal;
    window.closeAndRefresh = function() {
        closeModal();
        setTimeout(function() {
            window.location.reload();
        }, 200);
    };

    function openExternalAiTool() {
        window.location.href = window.myavanaAiToolUrl || EXTERNAL_AI_TOOL_URL;
    }

    function openModal() {
        closeModal(true);
        createModal();
        bindEvents();
        showTermsStep();

        requestAnimationFrame(function() {
            $(SELECTORS.modal).addClass('is-visible');
        });

        $('body').addClass('myavana-ai-modal-open');
    }

    function closeModal(silent) {
        stopCamera();
        clearProgressInterval();

        const $modal = $(SELECTORS.modal);
        if (!$modal.length) {
            if (!silent) {
                $('body').removeClass('myavana-ai-modal-open');
            }
            return;
        }

        $modal.removeClass('is-visible');

        setTimeout(function() {
            $modal.remove();
            $('body').removeClass('myavana-ai-modal-open');
        }, silent ? 0 : 200);

        resetState();
    }

    function createModal() {
        const html = [
            '<div id="myavanaAIModal" class="myavana-ai-modal-overlay" role="dialog" aria-modal="true" aria-label="AI Hair Analysis">',
            '  <div class="myavana-ai-modal-panel">',
            '    <div class="myavana-ai-modal-header">',
            '      <h2 class="myavana-ai-modal-title">AI Hair Analysis</h2>',
            '      <button type="button" class="myavana-ai-close-btn" data-ai-action="close" aria-label="Close">',
            '        <i class="fas fa-times"></i>',
            '      </button>',
            '    </div>',
            '    <div class="myavana-ai-modal-content" id="myavanaAiModalContent"></div>',
            '  </div>',
            '</div>'
        ].join('');

        $('body').append(html);
    }

    function bindEvents() {
        if (STATE.eventsBound) {
            return;
        }

        STATE.eventsBound = true;

        $(document).on('click.myavanaAiModal', '[data-ai-action]', function(event) {
            event.preventDefault();
            const action = $(this).data('ai-action');
            handleAction(action);
        });

        $(document).on('change.myavanaAiModal', '#myavanaAiUploadInput', function(event) {
            const file = event.target.files && event.target.files[0] ? event.target.files[0] : null;
            if (file) {
                handleImageFile(file);
            }
        });

        $(document).on('click.myavanaAiModal', SELECTORS.modal, function(event) {
            if (event.target && event.target.id === 'myavanaAIModal') {
                closeModal();
            }
        });

        $(document).on('keydown.myavanaAiModal', function(event) {
            if (event.key === 'Escape' && $(SELECTORS.modal).length) {
                closeModal();
            }
        });
    }

    function handleAction(action) {
        switch (action) {
            case 'close':
                closeModal();
                break;
            case 'continue_terms':
                showUploadStep();
                break;
            case 'open_upload':
                $('#myavanaAiUploadInput').trigger('click');
                break;
            case 'open_camera':
                showCameraArea();
                break;
            case 'cancel_upload':
                hideCameraArea();
                break;
            case 'start_camera':
                startCamera();
                break;
            case 'capture_photo':
                capturePhoto();
                break;
            case 'retry_upload':
                showUploadStep();
                break;
            case 'analyze_photo':
                if (STATE.imageData) {
                    showAnalyzingStep();
                    performAnalysis();
                }
                break;
            case 'view_journey':
                window.closeAndRefresh();
                break;
            case 'analyze_again':
                showUploadStep();
                break;
            case 'share_native':
                shareNative();
                break;
            case 'share_x':
                shareTo('x');
                break;
            case 'share_facebook':
                shareTo('facebook');
                break;
            case 'share_linkedin':
                shareTo('linkedin');
                break;
            case 'copy_summary':
                copySummary();
                break;
            default:
                break;
        }
    }

    function showTermsStep() {
        const html = [
            '<div class="myavana-ai-hero">',
            '  <div class="myavana-ai-hero-icon">🧠</div>',
            '  <p>Get a detailed AI-powered assessment and save it to your journey.</p>',
            '</div>',
            '<div class="myavana-ai-card">',
            '  <ol class="myavana-ai-steps-list">',
            '    <li>Upload a clear photo in good lighting.</li>',
            '    <li>AI evaluates your hair type, health, hydration, and concerns.</li>',
            '    <li>Results are saved to profile snapshots and your hair journey entry feed.</li>',
            '  </ol>',
            '</div>',
            '<label class="myavana-ai-checkbox-row">',
            '  <input id="myavanaAiTermsCheck" type="checkbox" />',
            '  <span>I agree to the analysis terms and understand this is educational guidance.</span>',
            '</label>',
            '<div class="myavana-ai-actions">',
            '  <button type="button" class="myavana-ai-btn myavana-ai-btn-primary" id="myavanaAiContinueBtn" data-ai-action="continue_terms" disabled>',
            '    Continue',
            '  </button>',
            '</div>'
        ].join('');

        render(html);

        $('#myavanaAiTermsCheck').on('change', function() {
            $('#myavanaAiContinueBtn').prop('disabled', !this.checked);
        });
    }

    function showUploadStep() {
        resetTransientStepState();

        const html = [
            '<div class="myavana-ai-hero">',
            '  <div class="myavana-ai-hero-icon">📷</div>',
            '  <p>Upload or capture a photo to begin analysis.</p>',
            '</div>',
            '<div class="myavana-ai-upload-options">',
            '  <button type="button" class="myavana-ai-upload-option" data-ai-action="open_camera">',
            '    <i class="fas fa-camera"></i>',
            '    <strong>Use Camera</strong>',
            '    <span>Capture live photo</span>',
            '  </button>',
            '  <button type="button" class="myavana-ai-upload-option" data-ai-action="open_upload">',
            '    <i class="fas fa-upload"></i>',
            '    <strong>Upload Photo</strong>',
            '    <span>Select from gallery</span>',
            '  </button>',
            '</div>',
            '<input type="file" id="myavanaAiUploadInput" accept="image/*" style="display:none;" />',
            '<div id="myavanaAiCameraArea" class="myavana-ai-camera-wrap" style="display:none;">',
            '  <video id="myavanaAiVideo" autoplay playsinline muted></video>',
            '  <div class="myavana-ai-actions" style="margin-top:10px;">',
            '    <button type="button" class="myavana-ai-btn myavana-ai-btn-primary" data-ai-action="start_camera">Start Camera</button>',
            '    <button type="button" class="myavana-ai-btn myavana-ai-btn-primary" data-ai-action="capture_photo">Capture</button>',
            '    <button type="button" class="myavana-ai-btn myavana-ai-btn-secondary" data-ai-action="cancel_upload">Cancel</button>',
            '  </div>',
            '</div>',
            '<div id="myavanaAiPreviewArea" style="display:none;">',
            '  <img id="myavanaAiPreviewImage" class="myavana-ai-preview-image" src="" alt="Selected hair photo" />',
            '  <div class="myavana-ai-actions" style="margin-top:10px;">',
            '    <button type="button" class="myavana-ai-btn myavana-ai-btn-primary" data-ai-action="analyze_photo">Analyze Photo</button>',
            '    <button type="button" class="myavana-ai-btn myavana-ai-btn-secondary" data-ai-action="retry_upload">Choose Another</button>',
            '  </div>',
            '</div>'
        ].join('');

        render(html);
    }

    function showAnalyzingStep() {
        const html = [
            '<div class="myavana-ai-hero">',
            '  <div class="myavana-ai-hero-icon"><i class="fas fa-spinner fa-spin"></i></div>',
            '  <p id="myavanaAiProgressText">Preparing analysis...</p>',
            '</div>',
            '<div class="myavana-ai-progress-track">',
            '  <div class="myavana-ai-progress-fill" id="myavanaAiProgressFill"></div>',
            '</div>',
            '<div class="myavana-ai-progress-meta">',
            '  <span>AI scan in progress</span>',
            '  <span id="myavanaAiProgressPercent">0%</span>',
            '</div>'
        ].join('');

        render(html);
        animateProgress();
    }

    function showResultsStep(responseData) {
        const analysis = responseData.analysis || responseData;
        const hair = analysis.hair_analysis || {};
        const saveStatus = responseData.save_status || {};
        const usage = responseData.monthly_usage || {};

        STATE.latestAnalysis = analysis;
        STATE.latestResponse = responseData;

        const recommendations = normalizeTextArray(analysis.recommendations);
        const products = normalizeProductArray(analysis.products);

        const summaryText = escapeHtml(analysis.summary || 'Analysis complete.');
        const details = [
            ['Type', hair.type],
            ['Curl Pattern', hair.curl_pattern],
            ['Porosity', hair.porosity],
            ['Length', hair.length],
            ['Texture', hair.texture],
            ['Density', hair.density],
            ['Hairstyle', hair.hairstyle],
            ['Hair Color', hair.hair_color],
            ['Scalp', hair.scalp_health]
        ].filter(function(item) {
            return item[1];
        });

        const saveFlags = [
            saveStatus.analysis_saved ? 'Analysis saved to profile history.' : 'Analysis completed.',
            saveStatus.snapshot_saved ? 'Snapshot saved for trend tracking.' : 'Snapshot save pending.',
            saveStatus.entry_created ? 'New hair journey entry created automatically.' : 'Entry creation pending.'
        ];

        const usageLine = usage.limit
            ? 'Monthly AI usage: ' + escapeHtml(String(usage.used || 0)) + '/' + escapeHtml(String(usage.limit)) + ' used.'
            : '';

        const html = [
            '<div class="myavana-ai-hero">',
            '  <div class="myavana-ai-hero-icon">✨</div>',
            '  <p>Your AI hair analysis is ready.</p>',
            '</div>',
            '<div class="myavana-ai-results-grid">',
            metricCard('Health', formatPercent(hair.health_score)),
            metricCard('Hydration', formatPercent(hair.hydration)),
            metricCard('Elasticity', formatPercent(hair.elasticity)),
            '</div>',
            details.length ? '<div class="myavana-ai-detail-grid">' + details.map(function(item) {
                return '<div class="myavana-ai-detail-pill"><strong>' + escapeHtml(item[0]) + ':</strong> ' + escapeHtml(String(item[1])) + '</div>';
            }).join('') + '</div>' : '',
            '<div class="myavana-ai-card"><strong>Summary</strong><p style="margin:8px 0 0;color:#4a4d68;">' + summaryText + '</p></div>',
            recommendations.length ? '<div class="myavana-ai-card"><strong>Recommendations</strong><ol class="myavana-ai-list">' + recommendations.map(function(rec) {
                return '<li>' + escapeHtml(rec) + '</li>';
            }).join('') + '</ol></div>' : '',
            products.length ? '<div class="myavana-ai-card"><strong>Suggested Products</strong><ul class="myavana-ai-list">' + products.map(function(productName) {
                return '<li>' + escapeHtml(productName) + '</li>';
            }).join('') + '</ul></div>' : '',
            '<div class="myavana-ai-save-flags">',
            '  <div>✅ ' + escapeHtml(saveFlags[0]) + '</div>',
            '  <div>✅ ' + escapeHtml(saveFlags[1]) + '</div>',
            '  <div>✅ ' + escapeHtml(saveFlags[2]) + '</div>',
            usageLine ? '  <div style="margin-top:4px;">📊 ' + usageLine + '</div>' : '',
            '</div>',
            '<div class="myavana-ai-share-row" style="margin-bottom:10px;">',
            '  <button type="button" class="myavana-ai-btn myavana-ai-btn-secondary" data-ai-action="share_native">Share</button>',
            '  <button type="button" class="myavana-ai-btn myavana-ai-btn-secondary" data-ai-action="copy_summary">Copy Summary</button>',
            '  <button type="button" class="myavana-ai-btn myavana-ai-btn-secondary" data-ai-action="share_x">Share X</button>',
            '  <button type="button" class="myavana-ai-btn myavana-ai-btn-secondary" data-ai-action="share_linkedin">Share LinkedIn</button>',
            '</div>',
            '<div class="myavana-ai-actions">',
            '  <button type="button" class="myavana-ai-btn myavana-ai-btn-primary" data-ai-action="view_journey">View Journey</button>',
            '  <button type="button" class="myavana-ai-btn myavana-ai-btn-secondary" data-ai-action="analyze_again">Analyze Another</button>',
            '</div>'
        ].join('');

        render(html);
    }

    function performAnalysis() {
        const ajaxUrl = (window.myavanaAjax && (window.myavanaAjax.ajax_url || window.myavanaAjax.ajaxurl)) || '/wp-admin/admin-ajax.php';
        const nonce = window.myavanaAjax && window.myavanaAjax.nonce ? window.myavanaAjax.nonce : '';
        const payloadImage = STATE.imageData.replace(/^data:image\/[a-zA-Z0-9.+-]+;base64,/, '');

        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'myavana_handle_vision_api_hair_analysis',
                nonce: nonce,
                image_data: payloadImage
            }
        }).done(function(response) {
            clearProgressInterval();
            if (response && response.success && response.data) {
                showResultsStep(response.data);
                return;
            }

            const errorData = response && response.data ? response.data : {};
            const message = errorData.message || 'Analysis failed. Please try again.';
            showErrorStep(message);
        }).fail(function() {
            clearProgressInterval();
            showErrorStep('Network error while analyzing image. Please try again.');
        });
    }

    function animateProgress() {
        clearProgressInterval();

        const statuses = [
            'Checking photo quality...',
            'Analyzing texture and curl pattern...',
            'Scoring hair health indicators...',
            'Building personalized recommendations...',
            'Finalizing results...'
        ];

        let progress = 0;
        STATE.progressInterval = setInterval(function() {
            progress = Math.min(progress + 20, 100);

            $('#myavanaAiProgressFill').css('width', progress + '%');
            $('#myavanaAiProgressPercent').text(progress + '%');

            const index = Math.min(Math.max(Math.floor(progress / 20) - 1, 0), statuses.length - 1);
            $('#myavanaAiProgressText').text(statuses[index]);

            if (progress >= 100) {
                clearProgressInterval();
            }
        }, 850);
    }

    function showErrorStep(message) {
        const html = [
            '<div class="myavana-ai-hero">',
            '  <div class="myavana-ai-hero-icon">⚠️</div>',
            '  <p>' + escapeHtml(message) + '</p>',
            '</div>',
            '<div class="myavana-ai-actions">',
            '  <button type="button" class="myavana-ai-btn myavana-ai-btn-primary" data-ai-action="retry_upload">Try Again</button>',
            '  <button type="button" class="myavana-ai-btn myavana-ai-btn-secondary" data-ai-action="close">Close</button>',
            '</div>'
        ].join('');

        render(html);
    }

    function showCameraArea() {
        $('#myavanaAiCameraArea').show();
        $('#myavanaAiPreviewArea').hide();
        $('.myavana-ai-upload-options').hide();
    }

    function hideCameraArea() {
        stopCamera();
        $('#myavanaAiCameraArea').hide();
        $('#myavanaAiPreviewArea').hide();
        $('.myavana-ai-upload-options').show();
    }

    function startCamera() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            showErrorStep('Camera is not supported on this device. Please upload a photo instead.');
            return;
        }

        navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } })
            .then(function(stream) {
                STATE.cameraStream = stream;
                const video = document.getElementById('myavanaAiVideo');
                if (video) {
                    video.srcObject = stream;
                    video.play().catch(function() {});
                }
            })
            .catch(function() {
                showErrorStep('Unable to access camera. Please allow permission or upload a photo.');
            });
    }

    function capturePhoto() {
        const video = document.getElementById('myavanaAiVideo');
        if (!video || !video.videoWidth || !video.videoHeight) {
            showErrorStep('Camera preview not ready. Please start camera first.');
            return;
        }

        const canvas = document.createElement('canvas');
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        const context = canvas.getContext('2d');
        context.drawImage(video, 0, 0, canvas.width, canvas.height);

        STATE.imageData = canvas.toDataURL('image/jpeg', 0.9);
        stopCamera();

        $('#myavanaAiPreviewImage').attr('src', STATE.imageData);
        $('#myavanaAiPreviewArea').show();
        $('#myavanaAiCameraArea').hide();
    }

    function stopCamera() {
        if (!STATE.cameraStream) {
            return;
        }

        STATE.cameraStream.getTracks().forEach(function(track) {
            track.stop();
        });

        STATE.cameraStream = null;
    }

    function handleImageFile(file) {
        if (!file.type.match('image.*')) {
            showErrorStep('Please upload a valid image file.');
            return;
        }

        if (file.size > (10 * 1024 * 1024)) {
            showErrorStep('Image must be smaller than 10MB.');
            return;
        }

        const reader = new FileReader();
        reader.onload = function(event) {
            STATE.imageData = event.target.result || '';
            if (!STATE.imageData) {
                showErrorStep('Unable to process image. Please try another file.');
                return;
            }

            $('#myavanaAiPreviewImage').attr('src', STATE.imageData);
            $('#myavanaAiPreviewArea').show();
            $('#myavanaAiCameraArea').hide();
            $('.myavana-ai-upload-options').hide();
        };

        reader.readAsDataURL(file);
    }

    function shareNative() {
        if (!STATE.latestAnalysis) {
            return;
        }

        const shareText = buildShareText();
        if (navigator.share) {
            navigator.share({
                title: 'MYAVANA AI Hair Analysis',
                text: shareText,
                url: window.location.href
            }).catch(function() {});
            return;
        }

        shareTo('x');
    }

    function shareTo(network) {
        if (!STATE.latestAnalysis) {
            return;
        }

        const shareText = encodeURIComponent(buildShareText());
        const shareUrl = encodeURIComponent(window.location.href);
        let url = '';

        if (network === 'x') {
            url = 'https://twitter.com/intent/tweet?text=' + shareText + '&url=' + shareUrl;
        } else if (network === 'facebook') {
            url = 'https://www.facebook.com/sharer/sharer.php?u=' + shareUrl;
        } else if (network === 'linkedin') {
            url = 'https://www.linkedin.com/sharing/share-offsite/?url=' + shareUrl;
        }

        if (url) {
            window.open(url, '_blank', 'width=640,height=700,noopener,noreferrer');
        }
    }

    function copySummary() {
        if (!STATE.latestAnalysis) {
            return;
        }

        const text = buildShareText();

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(function() {
                flashButton('[data-ai-action="copy_summary"]', 'Copied');
            }).catch(function() {
                fallbackCopy(text);
            });
            return;
        }

        fallbackCopy(text);
    }

    function fallbackCopy(text) {
        const el = document.createElement('textarea');
        el.value = text;
        document.body.appendChild(el);
        el.select();
        document.execCommand('copy');
        document.body.removeChild(el);
        flashButton('[data-ai-action="copy_summary"]', 'Copied');
    }

    function flashButton(selector, text) {
        const $button = $(selector).first();
        if (!$button.length) {
            return;
        }
        const original = $button.text();
        $button.text(text);
        setTimeout(function() {
            $button.text(original);
        }, 1200);
    }

    function buildShareText() {
        const analysis = STATE.latestAnalysis || {};
        const hair = analysis.hair_analysis || {};

        const type = hair.type || analysis.hair_type || 'Not determined';
        const health = typeof hair.health_score !== 'undefined' ? hair.health_score : analysis.health_score;
        const summary = analysis.summary || '';

        return 'I just completed my MYAVANA AI hair analysis. Hair type: ' + type +
            (health ? ', Health score: ' + health + '%' : '') +
            (summary ? '. ' + summary : '.');
    }

    function normalizeTextArray(value) {
        if (!Array.isArray(value)) {
            return [];
        }
        return value.map(function(item) {
            return typeof item === 'string' ? item : '';
        }).filter(Boolean);
    }

    function normalizeProductArray(value) {
        if (!Array.isArray(value)) {
            return [];
        }

        return value.map(function(item) {
            if (typeof item === 'string') {
                return item;
            }
            if (item && typeof item === 'object' && item.name) {
                return String(item.name);
            }
            return '';
        }).filter(Boolean);
    }

    function metricCard(label, value) {
        return [
            '<div class="myavana-ai-metric">',
            '  <div class="myavana-ai-metric-value">' + escapeHtml(value) + '</div>',
            '  <div class="myavana-ai-metric-label">' + escapeHtml(label) + '</div>',
            '</div>'
        ].join('');
    }

    function formatPercent(value) {
        if (value === null || typeof value === 'undefined' || value === '') {
            return '--';
        }
        const num = parseInt(value, 10);
        if (Number.isNaN(num)) {
            return '--';
        }
        return num + '%';
    }

    function render(html) {
        $(SELECTORS.content).html(html);
    }

    function clearProgressInterval() {
        if (STATE.progressInterval) {
            clearInterval(STATE.progressInterval);
            STATE.progressInterval = null;
        }
    }

    function resetTransientStepState() {
        stopCamera();
        clearProgressInterval();
        STATE.imageData = '';
    }

    function resetState() {
        STATE.imageData = '';
        STATE.latestAnalysis = null;
        STATE.latestResponse = null;
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
})(jQuery);
