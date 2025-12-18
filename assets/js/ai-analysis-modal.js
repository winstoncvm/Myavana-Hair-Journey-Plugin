/**
 * MYAVANA AI Hair Analysis Modal JavaScript
 * Dynamically created modal using the same pattern as entry modal
 */

(function($) {
    'use strict';

    let uploadedImage = null;
    let imageData = null;
    let cameraStream = null;
    let currentStep = 'terms';

    // Expose globally
    window.openAIAnalysisModal = function() {
        createAIModal();
    };

    function createAIModal() {
        // Remove existing modal if present
        $('#myavanaAIModal').remove();

        const modalHTML = `
            <div id="myavanaAIModal" class="myavana-modal-overlay" style="
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.8);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 10002;
                opacity: 0;
                transition: opacity 0.3s ease;
            ">
                <div class="myavana-modal-content" style="
                    background: #ffffff;
                    border-radius: 16px;
                    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                    max-width: 700px;
                    width: 90%;
                    max-height: 90vh;
                    overflow-y: auto;
                    position: relative;
                    transform: scale(0.9);
                    transition: transform 0.3s ease;
                ">
                    <button class="myavana-modal-close" onclick="closeAIModal()" style="
                        position: absolute;
                        top: 20px;
                        right: 20px;
                        background: rgba(34, 35, 35, 0.1);
                        border: none;
                        font-size: 18px;
                        color: #222323;
                        cursor: pointer;
                        z-index: 10;
                        width: 40px;
                        height: 40px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        border-radius: 50%;
                        transition: all 0.3s ease;
                    ">
                        <i class="fas fa-times"></i>
                    </button>
                    <div id="aiModalContent" style="padding: 40px;">
                        <!-- Content will be loaded here -->
                    </div>
                </div>
            </div>
        `;

        $('body').append(modalHTML);
        $('body').addClass('modal-open');

        // Animate in
        setTimeout(() => {
            $('#myavanaAIModal').css('opacity', '1');
            $('#myavanaAIModal .myavana-modal-content').css('transform', 'scale(1)');
        }, 50);

        // Initialize with terms step
        showTermsStep();
    }

    window.closeAIModal = function() {
        $('#myavanaAIModal').css('opacity', '0');
        $('#myavanaAIModal .myavana-modal-content').css('transform', 'scale(0.9)');

        setTimeout(() => {
            $('#myavanaAIModal').remove();
            $('body').removeClass('modal-open');
            stopCamera();
            resetState();
        }, 300);
    };

    window.closeAndRefresh = function() {
        window.closeAIModal();
        // Refresh the page to show the new entry
        setTimeout(() => {
            location.reload();
        }, 400);
    };

    function resetState() {
        uploadedImage = null;
        imageData = null;
        currentStep = 'terms';
    }

    function showTermsStep() {
        const content = `
            <div style="text-align: center; margin-bottom: 32px;">
                <div style="font-size: 64px; margin-bottom: 16px;">🧠</div>
                <h2 style="font-family: 'Archivo Black', sans-serif; font-size: 32px; color: #222323; margin-bottom: 12px;">
                    AI Hair Analysis
                </h2>
                <p style="color: #4a4d68; font-size: 16px;">Get personalized insights powered by Google Myavana AI</p>
            </div>

            <div style="background: #f5f5f7; padding: 24px; border-radius: 12px; margin-bottom: 24px;">
                <h4 style="font-family: 'Archivo', sans-serif; font-weight: 600; color: #222323; margin-bottom: 16px;">
                    <i class="fas fa-shield-alt" style="color: #e7a690; margin-right: 8px;"></i>
                    How It Works
                </h4>
                <ul style="list-style: none; padding: 0; margin: 0;">
                    <li style="padding: 8px 0; color: #4a4d68;">
                        <i class="fas fa-check-circle" style="color: #e7a690; margin-right: 8px;"></i>
                        Upload a clear photo of your hair
                    </li>
                    <li style="padding: 8px 0; color: #4a4d68;">
                        <i class="fas fa-check-circle" style="color: #e7a690; margin-right: 8px;"></i>
                        AI analyzes hair type, health, and characteristics
                    </li>
                    <li style="padding: 8px 0; color: #4a4d68;">
                        <i class="fas fa-check-circle" style="color: #e7a690; margin-right: 8px;"></i>
                        Receive personalized recommendations
                    </li>
                    <li style="padding: 8px 0; color: #4a4d68;">
                        <i class="fas fa-check-circle" style="color: #e7a690; margin-right: 8px;"></i>
                        Results saved to your profile for tracking
                    </li>
                </ul>
            </div>

            <div style="margin-bottom: 24px; display: flex; align-items: start; gap: 12px;">
                <input type="checkbox" id="aiTermsCheck" style="margin-top: 4px; width: 18px; height: 18px; cursor: pointer;">
                <label for="aiTermsCheck" style="color: #4a4d68; font-size: 14px; cursor: pointer;">
                    I agree to the <a href="/terms" target="_blank" style="color: #e7a690; font-weight: 600;">hair analysis terms of use</a>
                    and understand my images are processed securely.
                </label>
            </div>

            <p style="font-size: 13px; color: #666; margin-bottom: 24px;">
                <i class="fas fa-info-circle" style="margin-right: 8px; color: #e7a690;"></i>
                Your photos are analyzed using Myavana AI and are not stored permanently.
            </p>

            <button id="aiContinueBtn" disabled style="
                width: 100%;
                padding: 16px 32px;
                background: #e7a690;
                color: white;
                border: none;
                border-radius: 8px;
                font-family: 'Archivo', sans-serif;
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s ease;
            ">
                <i class="fas fa-arrow-right" style="margin-right: 8px;"></i>
                Continue to Analysis
            </button>
        `;

        $('#aiModalContent').html(content);

        // Bind events
        $('#aiTermsCheck').on('change', function() {
            const $btn = $('#aiContinueBtn');
            if ($(this).is(':checked')) {
                $btn.prop('disabled', false).css({
                    'opacity': '1',
                    'cursor': 'pointer'
                });
            } else {
                $btn.prop('disabled', true).css({
                    'opacity': '0.5',
                    'cursor': 'not-allowed'
                });
            }
        });

        $('#aiContinueBtn').on('click', showUploadStep);
    }

    function showUploadStep() {
        const content = `
            <div style="text-align: center; margin-bottom: 32px;">
                <h3 style="font-family: 'Archivo Black', sans-serif; font-size: 24px; color: #222323; margin-bottom: 12px;">
                    Upload Your Hair Photo
                </h3>
                <p style="color: #4a4d68;">For best results, ensure good lighting and that your hair is clearly visible</p>
            </div>

            <div id="uploadOptions" style="display: flex; gap: 20px; justify-content: center; margin-bottom: 24px;">
                <button onclick="selectCamera()" style="
                    min-width: 200px;
                    padding: 24px;
                    background: #f5f5f7;
                    border: 2px solid #e7a690;
                    border-radius: 12px;
                    cursor: pointer;
                    transition: all 0.3s ease;
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    gap: 12px;
                ">
                    <i class="fas fa-camera" style="font-size: 32px; color: #e7a690;"></i>
                    <span style="font-weight: 600; color: #222323;">Use Camera</span>
                    <small style="font-size: 12px; color: #666;">Take a photo now</small>
                </button>
                <button onclick="selectUpload()" style="
                    min-width: 200px;
                    padding: 24px;
                    background: #f5f5f7;
                    border: 2px solid #e7a690;
                    border-radius: 12px;
                    cursor: pointer;
                    transition: all 0.3s ease;
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    gap: 12px;
                ">
                    <i class="fas fa-upload" style="font-size: 32px; color: #e7a690;"></i>
                    <span style="font-weight: 600; color: #222323;">Upload Photo</span>
                    <small style="font-size: 12px; color: #666;">Choose from gallery</small>
                </button>
            </div>

            <div id="cameraSection" style="display: none;">
                <div id="cameraView" style="margin-bottom: 16px;"></div>
                <div style="display: flex; gap: 12px; justify-content: center;">
                    <button id="startCameraBtn" onclick="startCamera()" style="padding: 12px 24px; background: #e7a690; color: white; border: none; border-radius: 8px; cursor: pointer;">
                        Start Camera
                    </button>
                    <button id="captureBtn" onclick="capturePhoto()" style="display: none; padding: 12px 24px; background: #e7a690; color: white; border: none; border-radius: 8px; cursor: pointer;">
                        Capture Photo
                    </button>
                    <button onclick="showUploadStep()" style="padding: 12px 24px; background: #f5f5f7; color: #222323; border: 1px solid #ddd; border-radius: 8px; cursor: pointer;">
                        Cancel
                    </button>
                </div>
            </div>

            <div id="uploadSection" style="display: none;">
                <input type="file" id="photoUpload" accept="image/*" style="display: block; margin: 0 auto 16px; width: 100%;" />
                <button onclick="showUploadStep()" style="padding: 12px 24px; background: #f5f5f7; color: #222323; border: 1px solid #ddd; border-radius: 8px; cursor: pointer; width: 100%;">
                    Cancel
                </button>
            </div>

            <div id="previewSection" style="display: none; text-align: center;">
                <img id="photoPreview" src="" style="max-width: 100%; border-radius: 12px; margin-bottom: 16px;" />
                <button onclick="analyzePhoto()" style="
                    width: 100%;
                    padding: 18px 36px;
                    background: linear-gradient(135deg, #e7a690 0%, #d4956f 100%);
                    color: white;
                    border: none;
                    border-radius: 8px;
                    font-size: 18px;
                    font-weight: 600;
                    cursor: pointer;
                    margin-bottom: 12px;
                ">
                    <i class="fas fa-magic" style="margin-right: 8px;"></i>
                    Analyze My Hair
                </button>
                <button onclick="showUploadStep()" style="padding: 12px 24px; background: #f5f5f7; color: #222323; border: 1px solid #ddd; border-radius: 8px; cursor: pointer; width: 100%;">
                    Try Another Photo
                </button>
            </div>
        `;

        $('#aiModalContent').html(content);

        // Bind upload handler
        $('#photoUpload').on('change', function(e) {
            const file = e.target.files[0];
            if (file && file.type.match('image.*')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imageData = e.target.result;
                    $('#photoPreview').attr('src', imageData);
                    $('#uploadOptions, #uploadSection').hide();
                    $('#previewSection').show();
                };
                reader.readAsDataURL(file);
            }
        });
    }

    window.selectCamera = function() {
        $('#uploadOptions').hide();
        $('#cameraSection').show();
    };

    window.selectUpload = function() {
        $('#uploadOptions').hide();
        $('#uploadSection').show();
    };

    window.startCamera = function() {
        navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } })
            .then(function(stream) {
                cameraStream = stream;
                const video = document.createElement('video');
                video.srcObject = stream;
                video.autoplay = true;
                video.style.width = '100%';
                video.style.borderRadius = '12px';
                video.setAttribute('id', 'cameraVideo');
                $('#cameraView').html(video);
                $('#startCameraBtn').hide();
                $('#captureBtn').show();
            })
            .catch(function(error) {
                alert('Unable to access camera. Please upload a photo instead.');
            });
    };

    window.capturePhoto = function() {
        const video = document.getElementById('cameraVideo');
        const canvas = document.createElement('canvas');
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        canvas.getContext('2d').drawImage(video, 0, 0);
        imageData = canvas.toDataURL('image/jpeg', 0.9);

        stopCamera();
        $('#photoPreview').attr('src', imageData);
        $('#cameraSection').hide();
        $('#previewSection').show();
    };

    function stopCamera() {
        if (cameraStream) {
            cameraStream.getTracks().forEach(track => track.stop());
            cameraStream = null;
        }
    }

    window.analyzePhoto = function() {
        if (!imageData) return;

        showAnalyzingStep();
        performAnalysis();
    };

    function showAnalyzingStep() {
        const content = `
            <div style="text-align: center; padding: 40px 20px;">
                <div style="margin-bottom: 24px;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 48px; color: #e7a690;"></i>
                </div>
                <h3 style="font-family: 'Archivo Black', sans-serif; font-size: 24px; color: #222323; margin-bottom: 12px;">
                    Analyzing Your Hair...
                </h3>
                <p id="analyzeStatus" style="color: #4a4d68;">Our AI is examining your photo</p>
                <div style="margin-top: 24px;">
                    <div style="width: 100%; height: 8px; background: #f5f5f7; border-radius: 4px; overflow: hidden;">
                        <div id="analyzeProgress" style="width: 0%; height: 100%; background: linear-gradient(90deg, #e7a690, #d4956f); transition: width 0.3s ease;"></div>
                    </div>
                    <span id="analyzePercent" style="display: block; margin-top: 8px; color: #666;">0%</span>
                </div>
            </div>
        `;

        $('#aiModalContent').html(content);

        // Animate progress
        const messages = [
            'Examining hair texture...',
            'Analyzing hair health...',
            'Identifying hair type...',
            'Generating recommendations...',
            'Finalizing analysis...'
        ];

        let progress = 0;
        const interval = setInterval(() => {
            progress += 20;
            $('#analyzeProgress').css('width', progress + '%');
            $('#analyzePercent').text(progress + '%');
            const index = Math.floor(progress / 20) - 1;
            if (index >= 0 && index < messages.length) {
                $('#analyzeStatus').text(messages[index]);
            }
            if (progress >= 100) clearInterval(interval);
        }, 1000);
    }

    function performAnalysis() {
        $.ajax({
            url: myavanaAjax.ajax_url || myavanaAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'myavana_handle_vision_api_hair_analysis',
                nonce: myavanaAjax.nonce,
                image_data: imageData.replace(/^data:image\/(png|jpg|jpeg);base64,/, '')
            },
            success: function(response) {
                if (response.success && response.data) {
                    showResults(response.data);
                } else {
                    const errorData = response.data || {};
                    const message = errorData.message || 'Analysis failed';
                    const isLimitReached = errorData.limit_reached || false;
                    showError(message, isLimitReached);
                }
            },
            error: function() {
                showError('Network error. Please try again.', false);
            }
        });
    }

    function showResults(data) {
        const analysis = data.analysis || data;
        const hairAnalysis = analysis.hair_analysis || {};

        let resultsHTML = `
            <div style="text-align: center; margin-bottom: 32px;">
                <div style="font-size: 48px; margin-bottom: 16px;">✨</div>
                <h3 style="font-family: 'Archivo Black', sans-serif; font-size: 24px; color: #222323; margin-bottom: 8px;">
                    Your Hair Analysis Results
                </h3>
                <p style="color: #4a4d68;">AI-powered insights just for you</p>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
        `;

        if (hairAnalysis.type) {
            resultsHTML += `
                <div style="background: #f5f5f7; padding: 20px; border-radius: 12px; text-align: center;">
                    <div style="font-size: 32px; margin-bottom: 8px;">🎯</div>
                    <div style="font-weight: 600; color: #222323; margin-bottom: 4px;">Hair Type</div>
                    <div style="font-size: 18px; color: #e7a690; font-weight: 600;">${hairAnalysis.type}</div>
                </div>
            `;
        }

        if (hairAnalysis.health_score) {
            resultsHTML += `
                <div style="background: #f5f5f7; padding: 20px; border-radius: 12px; text-align: center;">
                    <div style="font-size: 32px; margin-bottom: 8px;">💪</div>
                    <div style="font-weight: 600; color: #222323; margin-bottom: 4px;">Health Score</div>
                    <div style="font-size: 18px; color: #e7a690; font-weight: 600;">${hairAnalysis.health_score}/100</div>
                </div>
            `;
        }

        resultsHTML += `</div>`;

        if (analysis.recommendations && analysis.recommendations.length > 0) {
            resultsHTML += `
                <div style="background: #fff; padding: 24px; border-radius: 12px; border: 2px solid #f5f5f7; margin-bottom: 16px;">
                    <h4 style="font-family: 'Archivo', sans-serif; font-weight: 600; color: #222323; margin-bottom: 16px;">
                        💡 Recommendations
                    </h4>
                    <ol style="padding-left: 20px; color: #4a4d68;">
                        ${analysis.recommendations.map(r => `<li style="margin-bottom: 8px;">${r}</li>`).join('')}
                    </ol>
                </div>
            `;
        }

        resultsHTML += `
            <div style="text-align: center; margin-top: 24px;">
                <p style="color: #666; font-size: 13px; margin-bottom: 16px;">
                    ✅ Analysis saved to your profile<br>
                    📝 Hair journey entry created automatically
                </p>
                <button onclick="closeAndRefresh()" style="
                    padding: 16px 32px;
                    background: #e7a690;
                    color: white;
                    border: none;
                    border-radius: 8px;
                    font-weight: 600;
                    cursor: pointer;
                ">
                    View My Journey
                </button>
            </div>
        `;

        $('#aiModalContent').html(resultsHTML);
    }  

    function showError(message, isLimitReached = false) {
        const emoji = isLimitReached ? '📊' : '⚠️';
        const title = isLimitReached ? 'Monthly Limit Reached' : 'Analysis Error';

        const content = `
            <div style="text-align: center; padding: 40px 20px;">
                <div style="font-size: 48px; margin-bottom: 16px;">${emoji}</div>
                <h3 style="font-family: 'Archivo Black', sans-serif; font-size: 24px; color: #222323; margin-bottom: 12px;">
                    ${title}
                </h3>
                <p style="color: #4a4d68; margin-bottom: 24px; max-width: 400px; margin-left: auto; margin-right: auto;">
                    ${message}
                </p>
                <button onclick="closeAIModal()" style="
                    padding: 16px 32px;
                    background: ${isLimitReached ? '#4a4d68' : '#e7a690'};
                    color: white;
                    border: none;
                    border-radius: 8px;
                    font-weight: 600;
                    cursor: pointer;
                ">
                    ${isLimitReached ? 'Understood' : 'Close'}
                </button>
            </div>
        `;

        $('#aiModalContent').html(content);
    }

})(jQuery);