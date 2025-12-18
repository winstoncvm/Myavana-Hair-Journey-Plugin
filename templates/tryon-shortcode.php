<?php
function myavana_tryon_shortcode($atts = []) {
    // Unchanged auth and Typeform data
    $atts = shortcode_atts(['user_id' => get_current_user_id()], $atts);
    $user_id = intval($atts['user_id']);
    $is_owner = $user_id === get_current_user_id();

    if (!is_user_logged_in()) {
        return '<p>Please <a href="' . home_url('/login') . '">log in</a> to try on styles.</p>';
    }

    if (!$is_owner) {
        return '<p style="color: var(--onyx);">You can only access try-on for your own profile.</p>';
    }

    $typeform_data = get_user_meta($user_id, 'myavana_typeform_data', true) ?: [];
    $user_data = [
        'hair_journey' => $typeform_data['hair_journey'] ?? '',
        'hair_health' => $typeform_data['hair_health'] ?? '',
        'additional_info' => $typeform_data['additional_info'] ?? ''
    ];

    ob_start();
    ?>
    <!-- HTML unchanged except for adding hair-overlay back for fallback -->
    <div class="myavana-tryon">
        <h2 class="myavana-title mb-3">Virtual Hair Try-On</h2>
        <div id="tryon-terms" class="mb-3">
            <div class="myavana-checkbox-content my-3">
                <div class="checkbox-wrapper">
                    <input id="terms-agree" type="checkbox">
                    <label for="terms-agree"><div class="tick_mark"></div></label>
                    <span class="myavana-checkbox-text">I agree to the <a href="/terms" target="_blank" style="color: var(--coral);">terms of use</a>.</span>
                </div>
                <p class="myavana-checkbox-desc">Please agree to the terms to use the virtual try-on feature.</p>
            </div>
            <button id="terms-accept" class="myavana-button">Continue</button>
        </div>
        <div id="tryon-interface" class="my-3" style="display: none;">
            <div id="image-source" class="mb-3">
                <button id="use-camera" class="myavana-button">Use Camera</button>
                <button id="upload-photo" class="myavana-button">Upload Photo</button>
            </div>
            <div id="camera-setup" style="display: none;">
                <p class="mb-3">Position your face in the frame. Ensure good lighting!</p>
                <div class="camera-container mb-3">
                    <div id="camera-view"></div>
                    <canvas id="camera-canvas" style="display: none;"></canvas>
                    <div id="face-guide" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none;">
                        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 70%; height: 80%; border: 3px dashed rgba(255,255,255,0.7); border-radius: 50%;"></div>
                    </div>
                </div>
                <div class="camera-controls">
                    <button id="start-camera" class="myavana-button">Start Camera</button>
                    <button id="capture-photo" class="myavana-button" style="display: none;">Capture Photo</button>
                    <button id="retake-photo" class="myavana-button" style="display: none;">Retake</button>
                    <button id="cancel-camera" class="myavana-button cancel">Cancel</button>
                </div>
            </div>
            <div id="upload-setup" style="display: none;">
                <p class="mb-3">Upload a clear selfie for the best results.</p>
                <div class="file-upload-container mb-3">
                    <input type="file" id="photo-upload" accept="image/jpeg,image/png" />
                </div>
                <div class="upload-controls">
                    <button id="cancel-upload" class="myavana-button cancel">Cancel</button>
                </div>
            </div>
            <div id="tryon-preview" style="display: none;">
                <h3 class="myavana-title mb-3">Preview Your Look</h3>
                <div class="preview-container mb-3" style="position: relative;">
                    <img id="preview-image" src="" style="max-width: 100%; border-radius: 8px;">
                    <div id="hair-overlay" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none;"></div>
                    <div id="loading-overlay" style="display: none; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); color: white; text-align: center; line-height: 100%;">Generating...</div>
                </div>
                <div class="options-panel mb-3">
                    <h4 class="mb-3 text-black">Choose a Hairstyle</h4>
                    <div class="style-options">
                        <div class="style-option" data-style="bob">
                            <img src="<?php echo MYAVANA_URL; ?>assets/images/bob-thumbnail.png" alt="Bob">
                            <span class="tooltip">Short, sleek bob perfect for a chic look.</span>
                        </div>
                        <div class="style-option" data-style="curls">
                            <img src="<?php echo MYAVANA_URL; ?>assets/images/curls-thumbnail.png" alt="Curls">
                            <span class="tooltip">Bouncy curls for volume and vibrance.</span>
                        </div>
                        <div class="style-option" data-style="pixie">
                            <img src="<?php echo MYAVANA_URL; ?>assets/images/pixie-thumbnail.png" alt="Pixie">
                            <span class="tooltip">Edgy pixie cut with textured layers.</span>
                        </div>
                        <div class="style-option" data-style="long-waves">
                            <img src="<?php echo MYAVANA_URL; ?>assets/images/long-waves-thumbnail.png" alt="Long Waves">
                            <span class="tooltip">Elegant long waves for a flowing style.</span>
                        </div>
                        <div class="style-option" data-style="braids">
                            <img src="<?php echo MYAVANA_URL; ?>assets/images/braids-thumbnail.png" alt="Braids">
                            <span class="tooltip">Protective braids for low-maintenance care.</span>
                        </div>
                        <div class="style-option" data-style="updo">
                            <img src="<?php echo MYAVANA_URL; ?>assets/images/updo-thumbnail.png" alt="Updo">
                            <span class="tooltip">Sophisticated updo for special occasions.</span>
                        </div>
                    </div>
                </div>
                <div class="tryon-options">
                    <h4 class="mb-3 text-black">Choose a Color</h4>
                    <div class="color-slider mb-3">
                        <div class="color-option" data-color="black" style="background-color: #000;">
                            <span class="tooltip">Black hair is low-maintenance but needs moisture.</span>
                        </div>
                        <div class="color-option" data-color="brown" style="background-color: #8B4513;">
                            <span class="tooltip">Brown hair is versatile and suits most tones.</span>
                        </div>
                        <div class="color-option" data-color="blonde" style="background-color: #F5DEB3;">
                            <span class="tooltip">Blonde requires toning to prevent brassiness.</span>
                        </div>
                        <div class="color-option" data-color="red" style="background-color: #FF4500;">
                            <span class="tooltip">Red hair fades fast; use color-safe products.</span>
                        </div>
                    </div>
                </div>
                <div class="preview-controls">
                    <button id="generate-preview" class="myavana-button" disabled>Generate Preview</button>
                    <button id="download-image" class="myavana-button" style="display: none;">Download Image</button>
                    <button id="try-another" class="myavana-button">Try Another</button>
                    <button id="save-look" class="myavana-button">Save to Journey</button>
                    <button id="cancel-preview" class="myavana-button cancel">Cancel</button>
                </div>
            </div>
            <div id="ai-suggestion" class="myavana-ai-tip mb-3" style="display: none;">
                <?php
                $ai = new Myavana_AI();
                $suggestion = $ai->get_ai_tip('User is exploring hair colors and styles.');
                echo esc_html($suggestion);
                ?>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/webcamjs@1.0.26/webcam.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/filepond@4.30.4/dist/filepond.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/filepond-plugin-image-preview@4.6.11/dist/filepond-plugin-image-preview.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/filepond-plugin-file-validate-type@1.2.8/dist/filepond-plugin-file-validate-type.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/filepond-plugin-file-validate-size@2.2.8/dist/filepond-plugin-file-validate-size.min.js"></script>
    <script>
        jQuery(document).ready(function($) {
            // Initialize FilePond
            FilePond.registerPlugin(FilePondPluginImagePreview, FilePondPluginFileValidateType, FilePondPluginFileValidateSize);
            const pond = FilePond.create(document.querySelector('#photo-upload'), {
                acceptedFileTypes: ['image/jpeg', 'image/png'],
                maxFileSize: '5MB',
                allowImagePreview: true,
                stylePanelLayout: 'compact',
                styleButtonRemoveItemPosition: 'right',
                onaddfile: (error, file) => {
                    if (!error) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            $('#preview-image').attr('src', e.target.result);
                            $('#upload-setup').hide();
                            $('#tryon-preview').show();
                            $('#ai-suggestion').show();
                            checkGenerateButtonState();
                        };
                        reader.readAsDataURL(file.file);
                    }
                }
            });

            // Terms acceptance
            $('#terms-accept').click(function() {
                if ($('#terms-agree').is(':checked')) {
                    $('#tryon-terms').hide();
                    $('#tryon-interface').show();
                } else {
                    alert('Please agree to the terms.');
                }
            });

            // Image source selection
            $('#use-camera').click(function() {
                $('#image-source').hide();
                $('#camera-setup').show();
            });

            $('#upload-photo').click(function() {
                $('#image-source').hide();
                $('#upload-setup').show();
            });

            // Camera setup
            $('#start-camera').click(function() {
                Webcam.set({
                    width: 500,
                    height: 375,
                    image_format: 'jpeg',
                    jpeg_quality: 90,
                    facingMode: 'user'
                });
                Webcam.attach('#camera-view');
                $('#start-camera').hide();
                $('#capture-photo').show();
                $('#retake-photo').hide();
            });

            $('#capture-photo').click(function() {
                Webcam.snap(function(data_uri) {
                    $('#preview-image').attr('src', data_uri);
                    $('#camera-setup').hide();
                    $('#tryon-preview').show();
                    $('#ai-suggestion').show();
                    checkGenerateButtonState();
                    Webcam.reset();
                });
            });

            $('#retake-photo').click(function() {
                $('#tryon-preview').hide();
                $('#camera-setup').show();
                $('#start-camera').hide();
                $('#capture-photo').show();
                Webcam.attach('#camera-view');
            });

            // Cancel buttons
            $('#cancel-camera, #cancel-upload, #cancel-preview').click(function() {
                $('#camera-setup, #upload-setup, #tryon-preview').hide();
                $('#image-source').show();
                $('#ai-suggestion').hide();
                Webcam.reset();
                pond.removeFiles();
                $('#preview-image').attr('src', '');
                $('.style-option, .color-option').removeClass('active');
                $('#generate-preview').prop('disabled', true);
                $('#hair-overlay').empty();
            });

            // Style and color selection
            $('.style-option, .color-option').click(function() {
                $(this).siblings().removeClass('active');
                $(this).addClass('active');
                checkGenerateButtonState();
            });

            // Generate preview
            $('#generate-preview').click(function() {
                const imageData = $('#preview-image').attr('src');
                const style = $('.style-option.active').data('style') || 'bob';
                const color = $('.color-option.active').data('color') || 'black';
                $('#loading-overlay').show();
                updatePreview(imageData, style, color);
            });

            $('#download-image').click(function() {
                const imageData = $('#preview-image').attr('src');
                const link = document.createElement('a');
                link.href = imageData;
                link.download = 'myavana-hairstyle-preview.png';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            });

            // Try another
            $('#try-another').click(function() {
                $('#tryon-preview').hide();
                $('#image-source').show();
                $('#ai-suggestion').hide();
                $('#preview-image').attr('src', '');
                $('.style-option, .color-option').removeClass('active');
                $('#generate-preview').prop('disabled', true);
                $('#hair-overlay').empty();
                pond.removeFiles();
            });

            // Save look
            $('#save-look').click(function() {
                const imageData = $('#preview-image').attr('src');
                const selectedColor = $('.color-option.active').data('color') || 'black';
                const selectedStyle = $('.style-option.active').data('style') || 'bob';
                const metadata = $('#preview-image').data('metadata') || {};
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'myavana_save_tryon',
                        image_data: imageData,
                        color: selectedColor,
                        style: selectedStyle,
                        title: metadata.title || '',
                        description: metadata.description || '',
                        stylist_notes: metadata.stylist_notes || '',
                        products_used: metadata.products_used || '',
                        user_id: <?php echo $user_id; ?>,
                        nonce: '<?php echo wp_create_nonce('myavana_tryon_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('Look saved to your hair journey!');
                            $('#cancel-preview').click();
                        } else {
                            alert('Error saving look: ' + (response.data || 'Unknown error'));
                        }
                    },
                    error: function() {
                        alert('Error saving look. Please try again.');
                    }
                });
            });

            function checkGenerateButtonState() {
                const hasStyle = $('.style-option.active').length > 0;
                const hasColor = $('.color-option.active').length > 0;
                const hasImage = $('#preview-image').attr('src') !== '';
                $('#generate-preview').prop('disabled', !(hasStyle && hasColor && hasImage));
            }

            function updatePreview(imageData, style, color) {
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'myavana_generate_hairstyle',
                        image_data: imageData,
                        style: style,
                        color: color,
                        user_id: <?php echo $user_id; ?>,
                        nonce: '<?php echo wp_create_nonce('myavana_tryon_nonce'); ?>'
                    },
                    success: function(response) {
                        $('#loading-overlay').hide();
                        if (response.success) {
                            $('#hair-overlay').empty();
                            if (response.data.fallback) {
                                updateHairStyle(style, color);
                                $('#preview-image').attr('src', response.data.image);
                                alert('Unable to generate AI preview: ' + response.data.error + '. Using basic overlay instead.');
                                $('#download-image').hide();
                            } else {
                                $('#preview-image').attr('src', response.data.image);
                                $('#preview-image').data('metadata', response.data.metadata || {});
                                $('#download-image').show();
                            }
                        } else {
                            $('#hair-overlay').empty();
                            updateHairStyle(style, color);
                            $('#preview-image').attr('src', response.data.image);
                            alert('Error generating preview: ' + (response.data || 'Unknown error') + '. Using basic overlay instead.');
                            $('#download-image').hide();
                        }
                    },
                    error: function() {
                        $('#loading-overlay').hide();
                        $('#hair-overlay').empty();
                        updateHairStyle(style, color);
                        $('#preview-image').attr('src', imageData);
                        alert('Error generating preview. Using basic overlay instead.');
                        $('#download-image').hide();
                    }
                });
            }

            function updateHairStyle(style, color) {
                $('#hair-overlay').empty();
                const overlay = $('<div class="hair-img-overlay"></div>').css({
                    'position': 'absolute',
                    'top': '10%',
                    'left': '20%',
                    'width': '60%',
                    'height': '50%',
                    'background-image': `url('<?php echo MYAVANA_URL; ?>assets/hair-overlay/${style}.png')`,
                    'background-size': 'contain',
                    'background-position': 'center',
                    'background-repeat': 'no-repeat',
                    'pointer-events': 'none',
                    'filter': `hue-rotate(${getHueValue(color)})`,
                    'opacity': '0.9'
                });
                $('#hair-overlay').append(overlay);
            }

            function getHueValue(color) {
                switch (color) {
                    case 'black': return '0deg';
                    case 'brown': return '30deg';
                    case 'blonde': return '60deg';
                    case 'red': return '0deg';
                    default: return '0deg';
                }
            }
        });
    </script>
    <link href="https://cdn.jsdelivr.net/npm/filepond@4.30.4/dist/filepond.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/filepond-plugin-image-preview@4.6.11/dist/filepond-plugin-image-preview.min.css" rel="stylesheet">
    <?php
    return ob_get_clean();
}
