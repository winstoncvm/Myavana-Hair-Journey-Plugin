<?php
/**
 * Reusable AI Hair Analysis Modal Component
 * Can be included in any template for logged-in users
 */

if (!function_exists('myavana_render_ai_analysis_modal')) {
    function myavana_render_ai_analysis_modal() {
        if (!is_user_logged_in()) {
            return;
        }

        $current_user = wp_get_current_user();
        $user_id = $current_user->ID;
        ?>

        <!-- MYAVANA AI Hair Analysis Modal -->
        <div id="myavanaAIAnalysisModal" class="myavana-modal-overlay" style="display: none;">
            <div class="myavana-modal-container ai-analysis-modal">
                <div class="modal-controls">
                    <button class="modal-close" id="closeAIAnalysisModal" title="Close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="modal-content-wrapper">
                    <!-- Step 1: Terms -->
                    <div class="ai-modal-step active" id="aiTermsStep">
                        <div class="modal-header" style="text-align: center; margin-bottom: 32px;">
                            <div class="modal-icon" style="font-size: 48px; margin-bottom: 16px;">🧠</div>
                            <h3 class="modal-title">AI Hair Analysis</h3>
                            <p class="modal-description">Get personalized insights powered by Myavana AI</p>
                        </div>

                        <div style="background: #f5f5f7; padding: 24px; border-radius: 12px; margin-bottom: 24px;">
                            <h4 style="font-family: 'Archivo', sans-serif; font-weight: 600; color: #222323; margin-bottom: 16px;">
                                <i class="fas fa-shield-alt" style="color: var(--myavana-coral); margin-right: 8px;"></i>
                                How It Works
                            </h4>
                            <ul style="list-style: none; padding: 0; margin: 0;">
                                <li style="padding: 8px 0; color: #4a4d68;">
                                    <i class="fas fa-check-circle" style="color: var(--myavana-coral); margin-right: 8px;"></i>
                                    Upload a clear photo of your hair
                                </li>
                                <li style="padding: 8px 0; color: #4a4d68;">
                                    <i class="fas fa-check-circle" style="color: var(--myavana-coral); margin-right: 8px;"></i>
                                    AI analyzes hair type, health, and characteristics
                                </li>
                                <li style="padding: 8px 0; color: #4a4d68;">
                                    <i class="fas fa-check-circle" style="color: var(--myavana-coral); margin-right: 8px;"></i>
                                    Receive personalized recommendations
                                </li>
                                <li style="padding: 8px 0; color: #4a4d68;">
                                    <i class="fas fa-check-circle" style="color: var(--myavana-coral); margin-right: 8px;"></i>
                                    Results saved to your profile for tracking
                                </li>
                            </ul>
                        </div>

                        <div class="checkbox-wrapper" style="margin-bottom: 24px;">
                            <input id="aiTermsAgree" type="checkbox">
                            <label for="aiTermsAgree"><div class="tick_mark"></div></label>
                            <span class="myavana-checkbox-text">
                                I agree to the <a href="/terms" target="_blank" style="color: var(--myavana-coral); font-weight: 600;">hair analysis terms of use</a>
                                and understand my images are processed securely.
                            </span>
                        </div>

                        <p style="font-size: 13px; color: #666; margin-bottom: 24px;">
                            <i class="fas fa-info-circle" style="margin-right: 8px; color: var(--myavana-coral);"></i>
                            Your photos are analyzed using Myavana AI and are not stored permanently.
                            Analysis results are saved to help track your hair journey progress.
                        </p>

                        <button id="aiTermsAccept" class="modal-btn-primary" style="width: 100%;" disabled>
                            <i class="fas fa-arrow-right" style="margin-right: 8px;"></i>
                            Continue to Analysis
                        </button>
                    </div>

                    <!-- Step 2: Photo Upload -->
                    <div class="ai-modal-step" id="aiUploadStep">
                        <div class="modal-header" style="text-align: center; margin-bottom: 32px;">
                            <h3 class="modal-title">Choose Your Photo Method</h3>
                            <p class="modal-description">For best results, ensure good lighting and that your hair is clearly visible</p>
                        </div>

                        <div id="aiImageSource" style="display: flex; gap: 20px; justify-content: center; flex-wrap: wrap; margin-bottom: 24px;">
                            <button id="aiUseCamera" class="myavana-button" style="min-width: 200px; padding: 20px 24px; display: flex; flex-direction: column; align-items: center; gap: 8px;">
                                <i class="fas fa-camera" style="font-size: 24px; margin-bottom: 8px;"></i>
                                <span style="font-weight: 600;">Use Camera</span>
                                <small style="font-size: 12px; opacity: 0.8; text-transform: none;">Take a photo now</small>
                            </button>
                            <button id="aiUploadPhoto" class="myavana-button" style="min-width: 200px; padding: 20px 24px; display: flex; flex-direction: column; align-items: center; gap: 8px;">
                                <i class="fas fa-upload" style="font-size: 24px; margin-bottom: 8px;"></i>
                                <span style="font-weight: 600;">Upload Photo</span>
                                <small style="font-size: 12px; opacity: 0.8; text-transform: none;">Choose from gallery</small>
                            </button>
                        </div>

                        <div id="aiCameraSetup" style="display: none;">
                            <p style="margin-bottom: 16px;">Position your face in the frame. Ensure good lighting!</p>
                            <div class="camera-container" style="position: relative; margin-bottom: 16px;">
                                <div id="aiCameraView"></div>
                                <canvas id="aiCameraCanvas" style="display: none;"></canvas>
                                <div id="aiFaceGuide" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none;">
                                    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 70%; height: 80%; border: 3px dashed rgba(255,255,255,0.7); border-radius: 50%;"></div>
                                </div>
                            </div>
                            <div class="camera-controls" style="display: flex; gap: 12px; justify-content: center;">
                                <button id="aiStartCamera" class="myavana-button">Start Camera</button>
                                <button id="aiCapturePhoto" class="myavana-button" style="display: none;">Capture Photo</button>
                                <button id="aiRetakePhoto" class="myavana-button" style="display: none;">Retake</button>
                                <button id="aiCancelCamera" class="myavana-button cancel">Cancel</button>
                            </div>
                        </div>

                        <div id="aiUploadSetup" style="display: none;">
                            <p style="margin-bottom: 16px;">Upload a clear photo of your hair for the best results.</p>
                            <div class="file-upload-container" style="margin-bottom: 16px;">
                                <input type="file" id="aiPhotoUpload" accept="image/jpeg,image/png" />
                            </div>
                            <div class="upload-controls" style="text-align: center;">
                                <button id="aiCancelUpload" class="myavana-button cancel">Cancel</button>
                            </div>
                        </div>

                        <div id="aiPreview" style="display: none;">
                            <h3 class="modal-title" style="margin-bottom: 16px;">Preview Your Photo</h3>
                            <div class="preview-container" style="position: relative; margin-bottom: 16px;">
                                <img id="aiPreviewImage" src="" style="max-width: 100%; border-radius: 8px;">
                            </div>
                            <div class="preview-controls" style="display: flex; flex-direction: column; gap: 12px;">
                                <button id="aiAnalyzeNow" class="myavana-button" style="background: linear-gradient(135deg, var(--myavana-coral) 0%, #d4956f 100%); font-size: 18px; padding: 18px 36px; box-shadow: 0 6px 20px rgba(231, 166, 144, 0.4);">
                                    <i class="fas fa-magic" style="margin-right: 12px;"></i>
                                    <span style="font-weight: 600;">Analyze My Hair</span>
                                    <div style="font-size: 12px; opacity: 0.9; text-transform: none; margin-top: 4px;">Powered by Myavana AI</div>
                                </button>
                                <button id="aiTryAnother" class="myavana-button">
                                    <i class="fas fa-camera-retro" style="margin-right: 8px;"></i>
                                    Try Another Photo
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Analyzing -->
                    <div class="ai-modal-step" id="aiAnalyzingStep">
                        <div class="analyzing-content" style="text-align: center; padding: 40px 20px;">
                            <div class="analyzing-spinner" style="margin-bottom: 24px;">
                                <div class="spinner-ring"></div>
                                <div class="spinner-icon">🧠</div>
                            </div>
                            <h3>Analyzing Your Hair...</h3>
                            <p class="analyzing-text" id="aiAnalyzingText">Our AI is examining your photo</p>
                            <div class="analyzing-progress" style="margin-top: 24px;">
                                <div class="progress-bar">
                                    <div class="progress-fill" id="aiProgressFill"></div>
                                </div>
                                <span class="progress-percent" id="aiProgressPercent">0%</span>
                            </div>
                        </div>
                    </div>

                    <!-- Step 4: Results -->
                    <div class="ai-modal-step" id="aiResultsStep">
                        <div class="results-content" id="aiResultsContent">
                            <!-- Results will be dynamically inserted here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php
    }
}
?>