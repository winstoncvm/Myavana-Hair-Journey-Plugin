/**
 * Myavana Profile Hair Analysis JavaScript
 * 
 * This file handles all hair analysis functionality including:
 * - Terms acceptance and UI transitions
 * - Image analysis with AI
 * - Results display and metrics visualization
 */

jQuery(function($) {
    'use strict';

    // Immediate execution to verify script loading
    console.log('🔍 [Debug] profile-hair-analysis.js is loading');

    // Initialize DOM elements
    var domElements = {
        acceptButton: null,
        termsCheckbox: null,
        termsSection: null,
        interfaceSection: null
    };

    // Utility functions
    var debugElement = function(selector) {
        var element = document.querySelector(selector);
        return {
            exists: !!element,
            visible: element ? window.getComputedStyle(element).display !== 'none' : false,
            position: element ? window.getComputedStyle(element).position : null,
            zIndex: element ? window.getComputedStyle(element).zIndex : null,
            clickable: element ? window.getComputedStyle(element).pointerEvents !== 'none' : false
        };
    };

    // Global error handling
    window.onerror = function(msg, url, lineNo, columnNo, error) {
        console.error('🚨 [Hair Analysis Error]', {
            message: msg,
            url: url,
            line: lineNo,
            column: columnNo,
            error: error
        });
        return false;
    };

    // Initialize element references
    domElements.acceptButton = $('#terms-accept');
    domElements.termsCheckbox = $('#terms-agree');
    domElements.termsSection = $('#tryon-terms');
    domElements.interfaceSection = $('#tryon-interface');

    // Validate required elements
    if (!domElements.acceptButton.length || !domElements.termsCheckbox.length) {
        console.error('❌ [Debug] Missing required elements:', {
            buttonFound: domElements.acceptButton.length > 0,
            checkboxFound: domElements.termsCheckbox.length > 0
        });
        return;
    }

    // Handle terms acceptance with enhanced debugging
    var handleTermsAccept = function() {
        console.log('🎯 [Debug] handleTermsAccept called');
        
        try {
            // Ensure checkbox is checked
            if (!domElements.termsCheckbox.is(':checked')) {
                console.warn('⚠️ [Debug] Terms not accepted');
                return;
            }

            // Hide terms section and show interface
            domElements.termsSection.hide();
            domElements.interfaceSection.show();
            console.log('✨ [Debug] UI transition successful');
        } catch (error) {
            console.error('❌ [Debug] Error in handleTermsAccept:', error);
        }
    };

    // Event binding with enhanced error handling
    try {
        // Direct jQuery binding
        domElements.acceptButton.on('click', function(e) {
            console.log('🖱 [Debug] Terms accept clicked (jQuery direct)');
            e.preventDefault();
            handleTermsAccept();
        });

        // Delegated binding for dynamic elements
        $(document).on('click', '#terms-accept', function(e) {
            console.log('🖱 [Debug] Terms accept clicked (jQuery delegated)');
            e.preventDefault();
            handleTermsAccept();
        });

        // Terms checkbox handler
        domElements.termsCheckbox.on('change', function() {
            var isChecked = $(this).is(':checked');
            console.log('📝 [Debug] Terms checkbox changed:', isChecked);
            domElements.acceptButton.prop('disabled', !isChecked);
            console.log('🔘 [Debug] Accept button ' + (!isChecked ? 'disabled' : 'enabled'));
        });

        // Set initial checkbox state
        domElements.acceptButton.prop('disabled', !domElements.termsCheckbox.is(':checked'));
    } catch (error) {
        console.error('❌ [Debug] Critical error during event binding:', error);
    }

    // Debug execution completion
    console.log('✨ [Debug] profile-hair-analysis.js initialization complete');
});

// Initialize elements when document is ready
jQuery(document).ready(function($) {
    console.log('🌟 [Debug] Document ready event fired');

    // Initialize element references
    elements.acceptButton = $('#terms-accept');
    elements.termsCheckbox = $('#terms-agree');
    elements.termsSection = $('#tryon-terms');
    elements.interfaceSection = $('#tryon-interface');

    // Handle terms acceptance with enhanced debugging
    var handleTermsAccept = function() {
        console.log('🎯 [Debug] handleTermsAccept called');
        
        // Debug raw element states
        console.log('🔍 [Debug] Raw element states:', {
            termsButton: debugElement('#terms-accept'),
            termsCheckbox: debugElement('#terms-agree'),
            termsSection: debugElement('#tryon-terms'),
            interfaceSection: debugElement('#tryon-interface')
        });
        
        try {
            // Ensure checkbox is checked
            if (!domElements.termsCheckbox.is(':checked')) {
                console.warn('⚠️ [Debug] Terms not accepted');
                return;
            }

            // Hide terms section and show interface
            domElements.termsSection.hide();
            domElements.interfaceSection.show();
            console.log('✨ [Debug] UI transition successful');
        } catch (error) {
            console.error('❌ [Debug] Error in handleTermsAccept:', error);
        }
    };    // jQuery element checks
    const $termsCheck = jQuery('#terms-agree');
    const $button = jQuery('#terms-accept');
    const $termsSection = jQuery('#tryon-terms');
    const $interfaceSection = jQuery('#tryon-interface');

   

    if (!$termsCheck.length) {
        console.error('❌ [Debug] Terms checkbox not found in DOM');
        return false;
    }

    if (!$termsCheck.is(':checked')) {
        console.warn('⚠️ [Debug] Terms not agreed to');
        console.log('[Hair Analysis] Terms not accepted');
        const $errorMsg = jQuery('<div class="myavana-error-message">')
            .text('Please agree to the terms to continue.')
            .insertAfter($button)
            .fadeIn();
        
        setTimeout(() => $errorMsg.fadeOut(() => $errorMsg.remove()), 3000);
        return;
    }

    $button.prop('disabled', true)
        .html('<i class="fas fa-spinner fa-spin"></i> Loading...');
    
    console.log('[Hair Analysis] Starting interface transition');
    
    try {
        $termsSection.slideUp(300, function() {
            console.log('[Hair Analysis] Terms section hidden');
            $interfaceSection.slideDown(300, function() {
                console.log('[Hair Analysis] Interface section shown');
                $button.prop('disabled', false)
                    .html('<i class="fas fa-arrow-right" style="margin-right: 8px;"></i>Continue to Analysis');
            });
        });
    } catch (error) {
        showErrorMessage('An error occurred during transition. Please try again.');
    }
});

// Core Functions
function handleTermsAccept() {
    console.log('🎯 [Debug] handleTermsAccept called');
    
    // Debug raw element states
    console.log('🔍 [Debug] Raw element states:', {
        termsButton: debugElement('#terms-accept'),
        termsCheckbox: debugElement('#terms-agree'),
        termsSection: debugElement('#tryon-terms'),
        interfaceSection: debugElement('#tryon-interface')
    });
    
    const $termsCheck = jQuery('#terms-agree');
    const $button = jQuery('#terms-accept');
    const $termsSection = jQuery('#tryon-terms');
    const $interfaceSection = jQuery('#tryon-interface');

    console.log('🔍 [Debug] jQuery element states:', {
        termsCheckExists: $termsCheck.length > 0,
        termsChecked: $termsCheck.is(':checked'),
        buttonExists: $button.length > 0,
        buttonEnabled: !$button.prop('disabled'),
        termsSectionExists: $termsSection.length > 0,
        termsSectionVisible: $termsSection.is(':visible'),
        interfaceSectionExists: $interfaceSection.length > 0,
        interfaceSectionVisible: $interfaceSection.is(':visible')
    });

    if (!$termsCheck.length) {
        showErrorMessage('Terms checkbox not found');
        return false;
    }

    if (!$termsCheck.is(':checked')) {
        showErrorMessage('Please accept the terms to continue');
        return false;
    }

    // Show/hide sections with transition
    try {
        $termsSection.slideUp(300, function() {
            console.log('✅ [Debug] Terms section hidden');
            $interfaceSection.slideDown(300, function() {
                console.log('✅ [Debug] Interface section shown');
            });
        });
    } catch (error) {
        console.error('❌ [Debug] Section transition error:', error);
        showErrorMessage('Error during transition. Please try again.');
        return false;
    }

    console.log('✅ [Debug] Terms acceptance complete');
    return true;
}

function displayAnalysisResults(analysis) {
    console.log('📊 [Debug] Displaying analysis results:', analysis);

    const $history = jQuery('#analysis-history');
    if (!$history.length) {
        showErrorMessage('Analysis history container not found');
        return;
    }

    // Render main container
    $history.html(templates.analysisContainer());

    // Update analysis content
    if (analysis) {
        jQuery('#hair-analysis-content').html(templates.analysisResults(analysis));
        
        // Update metrics with safe values
        updateHairMetrics({
            hydration: analysis.hair_analysis?.hydration,
            curl_pattern: analysis.hair_analysis?.curl_pattern ? analysis.hair_analysis.curl_pattern.replace(/\D/g, '') * 20 : 0,
            health_score: analysis.hair_analysis?.health_score
        });
    } else {
        showErrorMessage('No analysis data available');
    }
}

function updateHairMetrics(metrics) {
    console.log('📊 [Debug] Updating hair metrics:', metrics);
    
    const safePercentage = (value) => {
        const num = parseInt(value);
        return isNaN(num) || num < 0 ? 0 : num > 100 ? 100 : num;
    };

    // Get all metric bars and log their presence
    const bars = {
        hydration: document.getElementById('hydration-level'),
        curlPattern: document.getElementById('curl-pattern'),
        healthScore: document.getElementById('health-score')
    };

    console.log('🔍 [Debug] Found metric bars:', {
        hydration: !!bars.hydration,
        curlPattern: !!bars.curlPattern,
        healthScore: !!bars.healthScore
    });

    // Update each bar with debugging
    Object.entries(bars).forEach(([key, bar]) => {
        if (bar) {
            const metricKey = key.replace(/([A-Z])/g, '_$1').toLowerCase();
            const value = safePercentage(metrics?.[metricKey]);
            console.log(`📊 [Debug] Setting ${key} to ${value}%`);
            bar.style.width = `${value}%`;
        } else {
            console.warn(`⚠️ [Debug] ${key} bar not found in DOM`);
        }
    });

    // Add transition effect
    const fillBars = document.querySelectorAll('.metric-fill');
    console.log(`🎨 [Debug] Adding transitions to ${fillBars.length} metric bars`);
    fillBars.forEach(bar => bar.style.transition = 'width 1s ease-in-out');
}

// Helper Functions
function showErrorMessage(message) {
    console.error('❌ [Debug] Error:', message);
    const $errorMsg = jQuery('<div class="myavana-error-message">')
        .text(message)
        .appendTo('body')
        .fadeIn();
    setTimeout(() => $errorMsg.fadeOut(500, function() { jQuery(this).remove(); }), 3000);
}

function showSuccessMessage(message) {
    console.log('✅ [Debug] Success:', message);
    const $successMsg = jQuery('<div class="myavana-success-message">')
        .text(message)
        .appendTo('body')
        .fadeIn();
    setTimeout(() => $successMsg.fadeOut(500, function() { jQuery(this).remove(); }), 3000);
}

// Initialize when DOM is ready
jQuery(document).ready(function($) {
    console.log('🌟 [Debug] jQuery document ready fired');

    // Debug initial environment
    console.log('🔍 [Debug] Environment check:', {
        jQuery: typeof jQuery !== 'undefined',
        $: typeof $ !== 'undefined',
        FilePond: typeof FilePond !== 'undefined',
        handleTermsAccept: typeof handleTermsAccept !== 'undefined'
    });

    // Debug element presence
    const elements = {
        termsCheckbox: $('#terms-agree'),
        acceptButton: $('#terms-accept'),
        termsSection: $('#tryon-terms'),
        interfaceSection: $('#tryon-interface'),
        analysisContainer: $('.myavana-tryon')
    };

  

    // Verify required elements
    if (!elements.acceptButton.length || !elements.termsCheckbox.length) {
        console.error('❌ [Debug] Missing required elements:', {
            buttonFound: elements.acceptButton.length > 0,
            checkboxFound: elements.termsCheckbox.length > 0
        });
        return;
    }

        // Event binding with enhanced error handling
        try {
            // Direct jQuery binding
            domElements.acceptButton.on('click', function(e) {
                console.log('🖱 [Debug] Terms accept clicked (jQuery direct)');
                e.preventDefault();
                handleTermsAccept();
            });

            // Delegated binding for dynamic elements
            $(document).on('click', '#terms-accept', function(e) {
                console.log('🖱 [Debug] Terms accept clicked (jQuery delegated)');
                e.preventDefault();
                handleTermsAccept();
            });

            // Terms checkbox handler
            domElements.termsCheckbox.on('change', function() {
                const isChecked = $(this).is(':checked');
                console.log('📝 [Debug] Terms checkbox changed:', isChecked);
                domElements.acceptButton.prop('disabled', !isChecked);
                console.log('🔘 [Debug] Accept button ' + (!isChecked ? 'disabled' : 'enabled'));
            });

            // Set initial checkbox state
            domElements.acceptButton.prop('disabled', !domElements.termsCheckbox.is(':checked'));
        } catch (error) {
            console.error('❌ [Debug] Critical error during event binding:', error);
        }

        // Debug execution completion
        console.log('✨ [Debug] profile-hair-analysis.js initialization complete');
    });

    // Reverting to the old implementation for "Upload Photo" and "Use Camera" buttons
    $('#upload-photo').on('click', function() {
        console.log('📤 [Debug] Upload Photo button clicked');
        $('#upload-setup').show();
        $('#camera-setup, #tryon-preview').hide();
    });

    $('#use-camera').on('click', function() {
        console.log('📷 [Debug] Use Camera button clicked');
        $('#camera-setup').show();
        $('#upload-setup, #tryon-preview').hide();
    });
   

    $('#cancel-upload').on('click', function() {
        console.log('❌ [Debug] Cancel Upload button clicked');
        $('#upload-setup').hide();
    });

    $('#cancel-camera').on('click', function() {
        console.log('❌ [Debug] Cancel Camera button clicked');
        $('#camera-setup').hide();
    });
