/**
 * MYAVANA Mobile-Optimized Components Library
 * Touch-friendly, performant components for mobile experience
 *
 * @package Myavana_Hair_Journey
 * @version 1.0.0
 */

(function($) {
    'use strict';

    window.MyavanaMobileComponents = {
        imageObserver: null,
        cameraStream: null,
        touchTargetMinSize: 44
    };

    /**
     * Initialize All Mobile Components
     */
    function initMobileComponents() {
        initTouchFriendlyButtons();
        initLazyLoadImages();
        initProgressiveImages();
        initMobileOptimizedForms();
        initMobileDatePickers();
        initCameraIntegration();
        initResponsiveImageGallery();

        console.log('[Mobile Components] Initialized successfully');
    }

    /**
     * Touch-Friendly Buttons
     * Ensures minimum 44x44px tap targets for accessibility
     */
    function initTouchFriendlyButtons() {
        // Add touch-friendly class to all interactive elements
        const $interactiveElements = $('button, a, .clickable, [role="button"]');

        $interactiveElements.each(function() {
            const $el = $(this);
            const width = $el.outerWidth();
            const height = $el.outerHeight();

            // If element is too small, add padding or wrap in larger tap target
            if (width < MyavanaMobileComponents.touchTargetMinSize ||
                height < MyavanaMobileComponents.touchTargetMinSize) {
                $el.addClass('myavana-touch-enhanced');
            }
        });

        // Enhanced touch feedback
        $interactiveElements.on('touchstart', function() {
            $(this).addClass('myavana-touch-active');
        });

        $interactiveElements.on('touchend touchcancel', function() {
            $(this).removeClass('myavana-touch-active');
        });

        // Prevent 300ms click delay on mobile
        if ('ontouchstart' in window) {
            FastClick.attach(document.body);
        }
    }

    /**
     * Lazy Load Images with Intersection Observer
     * Progressive loading for better performance
     */
    function initLazyLoadImages() {
        if (!('IntersectionObserver' in window)) {
            // Fallback: load all images immediately
            $('.myavana-lazy-image').each(function() {
                loadImage($(this));
            });
            return;
        }

        const options = {
            root: null,
            rootMargin: '50px', // Start loading 50px before entering viewport
            threshold: 0.01
        };

        MyavanaMobileComponents.imageObserver = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    const $img = $(entry.target);
                    loadImage($img);
                    MyavanaMobileComponents.imageObserver.unobserve(entry.target);
                }
            });
        }, options);

        // Observe all lazy images
        $('.myavana-lazy-image').each(function() {
            MyavanaMobileComponents.imageObserver.observe(this);
        });
    }

    /**
     * Load Image with Progressive Enhancement
     */
    function loadImage($img) {
        const src = $img.data('src');
        const srcset = $img.data('srcset');
        const sizes = $img.data('sizes');

        if (!src) return;

        // Create new image to preload
        const tempImg = new Image();

        tempImg.onload = function() {
            $img.attr('src', src);

            if (srcset) {
                $img.attr('srcset', srcset);
            }

            if (sizes) {
                $img.attr('sizes', sizes);
            }

            $img.addClass('myavana-loaded');
            $img.removeClass('myavana-lazy-image');
        };

        tempImg.onerror = function() {
            $img.addClass('myavana-load-error');
            console.error('[Mobile Components] Failed to load image:', src);
        };

        tempImg.src = src;

        if (srcset) {
            tempImg.srcset = srcset;
        }
    }

    /**
     * Progressive Image Loading
     * Shows low-quality placeholder while loading full image
     */
    function initProgressiveImages() {
        $('.myavana-progressive-image').each(function() {
            const $container = $(this);
            const $placeholder = $container.find('.myavana-image-placeholder');
            const $fullImage = $container.find('.myavana-image-full');
            const fullSrc = $fullImage.data('src');

            if (!fullSrc) return;

            // Load full resolution image
            const img = new Image();

            img.onload = function() {
                $fullImage.attr('src', fullSrc);
                $fullImage.addClass('loaded');

                // Fade out placeholder after full image loads
                setTimeout(function() {
                    $placeholder.fadeOut(300);
                }, 100);
            };

            img.src = fullSrc;
        });
    }

    /**
     * Mobile-Optimized Forms
     * Native inputs, better UX for mobile
     */
    function initMobileOptimizedForms() {
        // Auto-detect and optimize input types
        $('input[type="text"]').each(function() {
            const $input = $(this);
            const name = $input.attr('name') || '';
            const id = $input.attr('id') || '';
            const placeholder = $input.attr('placeholder') || '';

            // Email detection
            if (name.includes('email') || id.includes('email') || placeholder.toLowerCase().includes('email')) {
                $input.attr('type', 'email');
                $input.attr('autocomplete', 'email');
                $input.attr('inputmode', 'email');
            }

            // Phone detection
            if (name.includes('phone') || name.includes('tel') || id.includes('phone') || id.includes('tel')) {
                $input.attr('type', 'tel');
                $input.attr('autocomplete', 'tel');
                $input.attr('inputmode', 'tel');
            }

            // URL detection
            if (name.includes('url') || name.includes('website') || id.includes('url')) {
                $input.attr('type', 'url');
                $input.attr('inputmode', 'url');
            }

            // Number detection
            if (name.includes('age') || name.includes('number') || id.includes('number')) {
                $input.attr('inputmode', 'numeric');
            }
        });

        // Add mobile-friendly autocomplete
        $('input[name*="name"]').attr('autocomplete', 'name');
        $('input[name*="address"]').attr('autocomplete', 'street-address');
        $('input[name*="city"]').attr('autocomplete', 'address-level2');
        $('input[name*="state"]').attr('autocomplete', 'address-level1');
        $('input[name*="zip"]').attr('autocomplete', 'postal-code');

        // Enhance textareas for mobile
        $('textarea').each(function() {
            const $textarea = $(this);

            // Auto-resize on input
            $textarea.on('input', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });

            // Add character counter if maxlength is set
            const maxLength = $textarea.attr('maxlength');
            if (maxLength) {
                const $counter = $('<div class="myavana-char-counter"></div>');
                $textarea.after($counter);

                function updateCounter() {
                    const remaining = maxLength - $textarea.val().length;
                    $counter.text(`${remaining} characters remaining`);
                }

                updateCounter();
                $textarea.on('input', updateCounter);
            }
        });

        // Better file input for mobile
        $('input[type="file"]').each(function() {
            const $input = $(this);
            const accept = $input.attr('accept') || '';

            // If accepting images, add camera option
            if (accept.includes('image')) {
                $input.attr('capture', 'environment');
            }

            // Create custom file input UI
            const $label = $input.closest('label');
            if ($label.length === 0) {
                const $customInput = $(`
                    <div class="myavana-file-input-wrapper">
                        <button type="button" class="myavana-file-input-btn">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="17 8 12 3 7 8"></polyline>
                                <line x1="12" y1="3" x2="12" y2="15"></line>
                            </svg>
                            <span>Choose File</span>
                        </button>
                        <span class="myavana-file-input-name">No file chosen</span>
                    </div>
                `);

                $input.after($customInput);
                $input.hide();

                $customInput.find('.myavana-file-input-btn').on('click', function() {
                    $input.click();
                });

                $input.on('change', function() {
                    const fileName = this.files[0] ? this.files[0].name : 'No file chosen';
                    $customInput.find('.myavana-file-input-name').text(fileName);
                });
            }
        });

        // Form validation feedback
        $('form').on('submit', function(e) {
            const $form = $(this);
            const $inputs = $form.find('input[required], textarea[required], select[required]');
            let isValid = true;

            $inputs.each(function() {
                const $input = $(this);
                if (!this.validity.valid) {
                    isValid = false;
                    $input.addClass('myavana-input-error');

                    // Show error message
                    let errorMsg = this.validationMessage;
                    let $error = $input.next('.myavana-error-message');

                    if ($error.length === 0) {
                        $error = $('<div class="myavana-error-message"></div>');
                        $input.after($error);
                    }

                    $error.text(errorMsg);
                } else {
                    $input.removeClass('myavana-input-error');
                    $input.next('.myavana-error-message').remove();
                }
            });

            if (!isValid) {
                e.preventDefault();

                // Scroll to first error
                const $firstError = $form.find('.myavana-input-error').first();
                if ($firstError.length > 0) {
                    $('html, body').animate({
                        scrollTop: $firstError.offset().top - 100
                    }, 300);
                }
            }
        });

        // Clear error on input
        $('input, textarea, select').on('input change', function() {
            $(this).removeClass('myavana-input-error');
            $(this).next('.myavana-error-message').remove();
        });
    }

    /**
     * Mobile Date Picker
     * Native date/time inputs for mobile
     */
    function initMobileDatePickers() {
        if (!isMobileDevice()) return;

        // Convert text inputs to date inputs on mobile
        $('.myavana-datepicker').each(function() {
            const $input = $(this);
            const currentValue = $input.val();

            // Change type to date for native picker
            $input.attr('type', 'date');

            // Set min/max if specified
            const minDate = $input.data('min-date');
            const maxDate = $input.data('max-date');

            if (minDate) {
                $input.attr('min', minDate);
            }

            if (maxDate) {
                $input.attr('max', maxDate);
            }

            // Convert value format if needed (from MM/DD/YYYY to YYYY-MM-DD)
            if (currentValue && currentValue.includes('/')) {
                const parts = currentValue.split('/');
                if (parts.length === 3) {
                    const formattedDate = `${parts[2]}-${parts[0].padStart(2, '0')}-${parts[1].padStart(2, '0')}`;
                    $input.val(formattedDate);
                }
            }
        });

        // Time pickers
        $('.myavana-timepicker').each(function() {
            const $input = $(this);
            $input.attr('type', 'time');
        });

        // DateTime pickers
        $('.myavana-datetimepicker').each(function() {
            const $input = $(this);
            $input.attr('type', 'datetime-local');
        });
    }

    /**
     * Camera Integration for Photo Uploads
     * Direct camera access on mobile
     */
    function initCameraIntegration() {
        // Camera button in forms
        $('.myavana-camera-btn').on('click', function() {
            const $btn = $(this);
            const targetInput = $btn.data('target');
            const $input = $(targetInput);

            if ($input.length === 0) {
                console.warn('[Mobile Components] Target input not found:', targetInput);
                return;
            }

            // Trigger file input with camera
            $input.attr('capture', 'environment');
            $input.attr('accept', 'image/*');
            $input.click();
        });

        // Advanced camera with preview
        $('.myavana-camera-advanced').on('click', function() {
            openAdvancedCamera();
        });
    }

    /**
     * Open Advanced Camera Modal
     */
    function openAdvancedCamera() {
        const cameraHTML = `
            <div id="myavana-camera-modal" class="myavana-modal myavana-camera-modal">
                <div class="myavana-modal-overlay"></div>
                <div class="myavana-camera-container">
                    <div class="myavana-camera-header">
                        <button class="myavana-camera-close">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </button>
                        <h3>Take Photo</h3>
                    </div>

                    <div class="myavana-camera-view">
                        <video id="myavana-camera-video" autoplay playsinline></video>
                        <canvas id="myavana-camera-canvas" style="display: none;"></canvas>
                        <img id="myavana-camera-preview" style="display: none;" />
                    </div>

                    <div class="myavana-camera-controls">
                        <button class="myavana-camera-flip" title="Flip Camera">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="23 4 23 10 17 10"></polyline>
                                <polyline points="1 20 1 14 7 14"></polyline>
                                <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
                            </svg>
                        </button>

                        <button class="myavana-camera-capture" id="myavana-camera-capture">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                            </svg>
                        </button>

                        <button class="myavana-camera-retake" id="myavana-camera-retake" style="display: none;">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="1 4 1 10 7 10"></polyline>
                                <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                            </svg>
                            Retake
                        </button>

                        <button class="myavana-camera-use" id="myavana-camera-use" style="display: none;">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                            Use Photo
                        </button>
                    </div>
                </div>
            </div>
        `;

        $('body').append(cameraHTML);

        const $modal = $('#myavana-camera-modal');
        $modal.fadeIn(200);

        startCamera();
        bindCameraControls();
    }

    /**
     * Start Camera Stream
     */
    function startCamera(facingMode = 'environment') {
        const video = document.getElementById('myavana-camera-video');

        const constraints = {
            video: {
                facingMode: facingMode,
                width: { ideal: 1920 },
                height: { ideal: 1080 }
            },
            audio: false
        };

        navigator.mediaDevices.getUserMedia(constraints)
            .then(function(stream) {
                MyavanaMobileComponents.cameraStream = stream;
                video.srcObject = stream;
                video.play();
            })
            .catch(function(error) {
                console.error('[Mobile Components] Camera error:', error);
                alert('Unable to access camera. Please check permissions.');
                closeCameraModal();
            });
    }

    /**
     * Bind Camera Controls
     */
    function bindCameraControls() {
        let currentFacingMode = 'environment';

        // Close camera
        $('.myavana-camera-close').on('click', function() {
            closeCameraModal();
        });

        // Flip camera (front/back)
        $('.myavana-camera-flip').on('click', function() {
            stopCamera();
            currentFacingMode = currentFacingMode === 'environment' ? 'user' : 'environment';
            startCamera(currentFacingMode);
        });

        // Capture photo
        $('#myavana-camera-capture').on('click', function() {
            capturePhoto();
        });

        // Retake photo
        $('#myavana-camera-retake').on('click', function() {
            resetCamera();
        });

        // Use photo
        $('#myavana-camera-use').on('click', function() {
            const preview = document.getElementById('myavana-camera-preview');

            // Convert canvas to blob
            const canvas = document.getElementById('myavana-camera-canvas');
            canvas.toBlob(function(blob) {
                // Trigger custom event with photo data
                $(document).trigger('myavana:photo-captured', {
                    blob: blob,
                    dataUrl: preview.src
                });

                closeCameraModal();
            }, 'image/jpeg', 0.9);
        });
    }

    /**
     * Capture Photo from Video Stream
     */
    function capturePhoto() {
        const video = document.getElementById('myavana-camera-video');
        const canvas = document.getElementById('myavana-camera-canvas');
        const preview = document.getElementById('myavana-camera-preview');

        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;

        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

        const imageData = canvas.toDataURL('image/jpeg', 0.9);
        preview.src = imageData;

        // Hide video, show preview
        $(video).hide();
        $(preview).show();

        // Update controls
        $('#myavana-camera-capture').hide();
        $('.myavana-camera-flip').hide();
        $('#myavana-camera-retake').show();
        $('#myavana-camera-use').show();

        // Stop camera stream
        stopCamera();
    }

    /**
     * Reset Camera to Capture Mode
     */
    function resetCamera() {
        const video = document.getElementById('myavana-camera-video');
        const preview = document.getElementById('myavana-camera-preview');

        $(video).show();
        $(preview).hide();

        $('#myavana-camera-capture').show();
        $('.myavana-camera-flip').show();
        $('#myavana-camera-retake').hide();
        $('#myavana-camera-use').hide();

        startCamera();
    }

    /**
     * Stop Camera Stream
     */
    function stopCamera() {
        if (MyavanaMobileComponents.cameraStream) {
            MyavanaMobileComponents.cameraStream.getTracks().forEach(track => track.stop());
            MyavanaMobileComponents.cameraStream = null;
        }
    }

    /**
     * Close Camera Modal
     */
    function closeCameraModal() {
        stopCamera();
        $('#myavana-camera-modal').fadeOut(200, function() {
            $(this).remove();
        });
    }

    /**
     * Responsive Image Gallery
     * Swipeable, zoomable gallery for mobile
     */
    function initResponsiveImageGallery() {
        $('.myavana-image-gallery').each(function() {
            const $gallery = $(this);
            const $images = $gallery.find('.myavana-gallery-item');

            // Add click to open lightbox
            $images.on('click', function() {
                const index = $(this).index();
                openGalleryLightbox($gallery, index);
            });
        });
    }

    /**
     * Open Gallery Lightbox
     */
    function openGalleryLightbox($gallery, startIndex = 0) {
        const images = [];

        $gallery.find('.myavana-gallery-item img').each(function() {
            images.push({
                src: $(this).attr('src') || $(this).data('src'),
                alt: $(this).attr('alt') || ''
            });
        });

        if (images.length === 0) return;

        let currentIndex = startIndex;

        const lightboxHTML = `
            <div id="myavana-gallery-lightbox" class="myavana-lightbox">
                <div class="myavana-lightbox-overlay"></div>
                <div class="myavana-lightbox-content">
                    <button class="myavana-lightbox-close">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>

                    <div class="myavana-lightbox-image-container">
                        <img src="${images[currentIndex].src}" alt="${images[currentIndex].alt}" />
                    </div>

                    ${images.length > 1 ? `
                        <button class="myavana-lightbox-prev">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="15 18 9 12 15 6"></polyline>
                            </svg>
                        </button>

                        <button class="myavana-lightbox-next">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="9 18 15 12 9 6"></polyline>
                            </svg>
                        </button>

                        <div class="myavana-lightbox-counter">
                            <span class="myavana-lightbox-current">${currentIndex + 1}</span>
                            /
                            <span class="myavana-lightbox-total">${images.length}</span>
                        </div>
                    ` : ''}
                </div>
            </div>
        `;

        $('body').append(lightboxHTML);
        $('body').addClass('myavana-modal-open');

        const $lightbox = $('#myavana-gallery-lightbox');
        $lightbox.fadeIn(200);

        // Close handlers
        $lightbox.find('.myavana-lightbox-close, .myavana-lightbox-overlay').on('click', function() {
            closeLightbox();
        });

        // Navigation
        if (images.length > 1) {
            $lightbox.find('.myavana-lightbox-prev').on('click', function() {
                currentIndex = (currentIndex - 1 + images.length) % images.length;
                updateLightboxImage();
            });

            $lightbox.find('.myavana-lightbox-next').on('click', function() {
                currentIndex = (currentIndex + 1) % images.length;
                updateLightboxImage();
            });

            // Keyboard navigation
            $(document).on('keydown.lightbox', function(e) {
                if (e.key === 'ArrowLeft') {
                    $lightbox.find('.myavana-lightbox-prev').click();
                } else if (e.key === 'ArrowRight') {
                    $lightbox.find('.myavana-lightbox-next').click();
                } else if (e.key === 'Escape') {
                    closeLightbox();
                }
            });

            // Swipe navigation (integrate with gestures)
            const $imageContainer = $lightbox.find('.myavana-lightbox-image-container');
            $imageContainer.addClass('myavana-swipeable');

            $(document).on('myavana:swipe-left', function(e, target) {
                if ($(target).closest('#myavana-gallery-lightbox').length > 0) {
                    $lightbox.find('.myavana-lightbox-next').click();
                }
            });

            $(document).on('myavana:swipe-right', function(e, target) {
                if ($(target).closest('#myavana-gallery-lightbox').length > 0) {
                    $lightbox.find('.myavana-lightbox-prev').click();
                }
            });
        }

        function updateLightboxImage() {
            const $img = $lightbox.find('.myavana-lightbox-image-container img');
            $img.fadeOut(150, function() {
                $img.attr('src', images[currentIndex].src);
                $img.attr('alt', images[currentIndex].alt);
                $lightbox.find('.myavana-lightbox-current').text(currentIndex + 1);
                $img.fadeIn(150);
            });
        }

        function closeLightbox() {
            $(document).off('keydown.lightbox');
            $(document).off('myavana:swipe-left myavana:swipe-right');
            $lightbox.fadeOut(200, function() {
                $(this).remove();
                $('body').removeClass('myavana-modal-open');
            });
        }
    }

    /**
     * Check if Mobile Device
     */
    function isMobileDevice() {
        return window.innerWidth < 768 || /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    }

    /**
     * Public API
     */
    window.MyavanaMobileComponents.init = initMobileComponents;
    window.MyavanaMobileComponents.loadImage = loadImage;
    window.MyavanaMobileComponents.openCamera = openAdvancedCamera;
    window.MyavanaMobileComponents.openGalleryLightbox = openGalleryLightbox;

    // Initialize on document ready
    $(document).ready(function() {
        initMobileComponents();
    });

    console.log('[Mobile Components] Module loaded');

})(jQuery);
