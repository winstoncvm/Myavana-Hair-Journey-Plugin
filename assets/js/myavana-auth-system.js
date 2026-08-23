/**
 * MYAVANA Authentication System JavaScript
 *
 * Handles authentication modal, form validation, and user interactions
 */
(function($) {
    'use strict';

    // Authentication system object
    window.MyavanaAuth = {
        modal: null,
        currentForm: 'signin',
        isModalShown: false,
        settings: {
            showOnce: true,
            delay: 2000
        },

        // Initialize the authentication system
        init: function() {
            const incomingSettings = window.myavanaAuth || {};
            this.settings = {
                ...this.settings,
                ...incomingSettings,
                showOnce: typeof incomingSettings.show_once !== 'undefined' ? !!incomingSettings.show_once : this.settings.showOnce,
                delay: typeof incomingSettings.delay !== 'undefined' ? incomingSettings.delay : this.settings.delay
            };

            this.bindEvents();
            this.initGoogleAuth();
            this.autoShowModal();
            this.setupAccessibility();
        },

        // Bind event listeners
        bindEvents: function() {
            $(document).on('click', '#myavanaCloseBtn', this.closeModal.bind(this));
            $(document).on('click', '#myavanaSigninTab', () => this.switchForm('signin'));
            $(document).on('click', '#myavanaSignupTab', () => this.switchForm('signup'));
            $(document).on('click', '#myavanaForgotLink', this.showForgotForm.bind(this));
            $(document).on('click', '#myavanaBackToSignin', () => this.switchForm('signin'));
            $(document).on('click', '.myavana-password-toggle', this.handlePasswordToggle.bind(this));

            // Form submissions
            $(document).on('submit', '#myavanaSigninForm', this.handleSignIn.bind(this));
            $(document).on('submit', '#myavanaSignupForm', this.handleSignUp.bind(this));
            $(document).on('submit', '#myavanaForgotForm', this.handleForgotPassword.bind(this));

            // Modal backdrop click
            $(document).on('click', '#myavanaAuthModal', this.handleBackdropClick.bind(this));

            // Keyboard events
            $(document).on('keydown', this.handleKeyboard.bind(this));

            // Real-time form validation
            $(document).on('input', '.myavana-form-group input', this.validateInput.bind(this));
        },

        handlePasswordToggle: function(e) {
            e.preventDefault();

            const $button = $(e.currentTarget);
            const targetId = $button.data('target');
            if (!targetId) {
                return;
            }

            const $input = $('#' + targetId);
            if ($input.length === 0) {
                return;
            }

            const isPassword = $input.attr('type') === 'password';
            $input.attr('type', isPassword ? 'text' : 'password');
            $button.attr('aria-label', isPassword ? 'Hide password' : 'Show password');
            $button.find('.eye-open').toggle(!isPassword);
            $button.find('.eye-closed').toggle(isPassword);

            // Preserve cursor position and keep the field active after toggling.
            const inputEl = $input.get(0);
            if (inputEl) {
                const valueLength = $input.val() ? String($input.val()).length : 0;
                inputEl.focus();
                try {
                    inputEl.setSelectionRange(valueLength, valueLength);
                } catch (err) {
                    // Ignore browsers that do not support selection on this input state.
                }
            }
        },

        initGoogleAuth: function() {
            if (!this.settings.google_auth_enabled || !this.settings.google_client_id) {
                $('[data-google-auth-helper]').show();
                return;
            }

            const tryRender = () => {
                if (!(window.google && window.google.accounts && window.google.accounts.id)) {
                    return false;
                }

                window.google.accounts.id.initialize({
                    client_id: this.settings.google_client_id,
                    callback: this.handleGoogleCredential.bind(this),
                    auto_select: false,
                    cancel_on_tap_outside: true
                });

                ['myavanaGoogleSignin', 'myavanaGoogleSignup'].forEach((id) => {
                    const slot = document.getElementById(id);
                    if (!slot) {
                        return;
                    }

                    slot.innerHTML = '';
                    window.google.accounts.id.renderButton(slot, {
                        theme: 'outline',
                        size: 'large',
                        shape: 'pill',
                        text: id === 'myavanaGoogleSignup' ? 'signup_with' : 'signin_with',
                        width: 320
                    });
                });

                $('[data-google-auth-helper]').hide();
                return true;
            };

            if (tryRender()) {
                return;
            }

            let attempts = 0;
            const interval = window.setInterval(() => {
                attempts += 1;
                if (tryRender() || attempts >= 20) {
                    window.clearInterval(interval);
                    if (attempts >= 20 && !(window.google && window.google.accounts && window.google.accounts.id)) {
                        $('[data-google-auth-helper]').show();
                    }
                }
            }, 400);
        },

        // Auto-show modal based on settings
        autoShowModal: function() {
            if (!this.shouldShowModal()) {
                return;
            }

            const forceAuthPrompt = this.shouldForceAuthPrompt();
            setTimeout(() => {
                this.showModal(forceAuthPrompt ? this.getForcedFormType() : 'signin');
            }, forceAuthPrompt ? 150 : (this.settings.delay || 2000));
        },

        // Check if modal should be shown
        shouldShowModal: function() {
            // Explicit auth prompt from protected-page redirect flow.
            if (this.shouldForceAuthPrompt()) {
                return true;
            }

            // Don't show if user is logged in
            if (this.settings.is_logged_in) {
                return false;
            }

            // Check session storage only if show_once is enabled
            if (this.settings.showOnce && sessionStorage.getItem('myavana_modal_shown')) {
                return false;
            }

            // Show based on page settings
            if (this.settings.show_site_wide) {
                return true;
            }

            if (this.settings.show_on_homepage && window.location.pathname === '/') {
                return true;
            }

            // Default: show the modal (can be controlled by settings)
            return true;
        },

        shouldForceAuthPrompt: function() {
            try {
                const params = new URLSearchParams(window.location.search);
                return params.get('auth') === '1';
            } catch (e) {
                return false;
            }
        },

        getForcedFormType: function() {
            try {
                const params = new URLSearchParams(window.location.search);
                return this.normalizeFormType(params.get('form') || 'signin');
            } catch (e) {
                return 'signin';
            }
        },

        getPostLoginRedirectUrl: function() {
            let rawTarget = '';

            try {
                const params = new URLSearchParams(window.location.search);
                rawTarget = params.get('redirect_to') || '';
            } catch (e) {
                return '';
            }

            if (!rawTarget) {
                return '';
            }

            let decodedTarget = rawTarget;
            try {
                decodedTarget = decodeURIComponent(rawTarget);
            } catch (e) {
                decodedTarget = rawTarget;
            }

            try {
                const targetUrl = new URL(decodedTarget, window.location.origin);
                if (targetUrl.origin !== window.location.origin) {
                    return '';
                }
                return targetUrl.toString();
            } catch (e) {
                return '';
            }
        },

        // Show the authentication modal
        showModal: function(formType = 'signin') {
            const $modal = $('#myavanaAuthModal');

            if ($modal.length === 0) {
                console.warn('MYAVANA: Auth modal not found in DOM');
                return;
            }

            // Normalize form type aliases
            const normalizedFormType = this.normalizeFormType(formType);

            $modal.addClass('show');
            $('body').addClass('myavana-auth-open');
            this.switchForm(normalizedFormType);
            this.isModalShown = true;

            // Mark as shown for session
            if (this.settings.showOnce) {
                sessionStorage.setItem('myavana_modal_shown', 'true');
            }

            // Focus management
            this.focusModal();

            // Track event
            this.trackEvent('modal_shown', { form_type: normalizedFormType });
        },

        // Normalize form type to handle aliases
        normalizeFormType: function(formType) {
            const aliases = {
                'login': 'signin',
                'register': 'signup',
                'sign-in': 'signin',
                'sign-up': 'signup',
                'forgot-password': 'forgot',
                'reset': 'forgot'
            };

            return aliases[formType.toLowerCase()] || formType.toLowerCase();
        },

        // Close the modal
        closeModal: function() {
            const $modal = $('#myavanaAuthModal');
            $modal.removeClass('show');
            $('body').removeClass('myavana-auth-open');
            this.isModalShown = false;

            // Clear form data and errors
            this.resetForms();

            // Track event
            this.trackEvent('modal_closed');
        },

        // Switch between forms
        switchForm: function(formType) {
            this.currentForm = formType;

            // Update tabs
            $('.myavana-auth-toggle button').removeClass('active').attr('aria-selected', 'false');
            $('.myavana-auth-form').removeClass('active');

            // Clear messages
            $('.myavana-error-message, .myavana-success-message').fadeOut().empty();

            switch(formType) {
                case 'signin':
                    $('#myavanaSigninTab').addClass('active').attr('aria-selected', 'true');
                    $('#myavanaSigninForm').addClass('active');
                    $('#myavanaForgotForm').hide();
                    setTimeout(() => $('#myavana-signin-login').focus(), 300);
                    break;

                case 'signup':
                    $('#myavanaSignupTab').addClass('active').attr('aria-selected', 'true');
                    $('#myavanaSignupForm').addClass('active');
                    $('#myavanaForgotForm').hide();
                    setTimeout(() => $('#myavana-signup-name').focus(), 300);
                    break;

                case 'forgot':
                    $('.myavana-auth-toggle button').removeClass('active').attr('aria-selected', 'false');
                    $('.myavana-auth-form').removeClass('active');
                    $('#myavanaForgotForm').addClass('active').show();
                    setTimeout(() => $('#myavana-forgot-email').focus(), 300);
                    break;
            }

            // Track form switch
            this.trackEvent('form_switched', { form_type: formType });
        },

        // Show forgot password form
        showForgotForm: function(e) {
            e.preventDefault();
            this.switchForm('forgot');
        },

        // Handle backdrop clicks
        handleBackdropClick: function(e) {
            if (e.target === e.currentTarget) {
                this.closeModal();
            }
        },

        // Handle keyboard events
        handleKeyboard: function(e) {
            if (!this.isModalShown) return;

            switch(e.key) {
                case 'Escape':
                    this.closeModal();
                    break;

                case 'Tab':
                    this.handleTabNavigation(e);
                    break;
            }
        },

        // Handle tab navigation within modal
        handleTabNavigation: function(e) {
            const $modal = $('#myavanaAuthModal');
            const $focusableElements = $modal.find('button, input, a').filter(':visible');
            const $firstElement = $focusableElements.first();
            const $lastElement = $focusableElements.last();

            if (e.shiftKey && document.activeElement === $firstElement[0]) {
                e.preventDefault();
                $lastElement.focus();
            } else if (!e.shiftKey && document.activeElement === $lastElement[0]) {
                e.preventDefault();
                $firstElement.focus();
            }
        },

        // Focus management for accessibility
        focusModal: function() {
            const $activeForm = $('.myavana-auth-form.active');
            const $firstInput = $activeForm.find('input:first');

            if ($firstInput.length) {
                setTimeout(() => $firstInput.focus(), 300);
            }
        },

        // Setup accessibility features
        setupAccessibility: function() {
            // Add ARIA labels and descriptions
            $('#myavanaAuthModal').attr({
                'role': 'dialog',
                'aria-labelledby': 'myavanaAuthTitle',
                'aria-modal': 'true'
            });

            // Add hidden title for screen readers
            if ($('#myavanaAuthTitle').length === 0) {
                $('<h2 id="myavanaAuthTitle" class="sr-only">Authentication Dialog</h2>')
                    .prependTo('.myavana-modal-container');
            }
        },

        // Handle sign in form submission
        handleSignIn: function(e) {
            e.preventDefault();

            const form = e.target;
            const formData = new FormData(form);

            // Client-side validation
            if (!this.validateSignInForm(formData)) {
                return;
            }

            this.submitForm(form, 'signin', formData);
        },

        // Handle sign up form submission
        handleSignUp: function(e) {
            e.preventDefault();

            const form = e.target;
            const formData = new FormData(form);

            // Client-side validation
            if (!this.validateSignUpForm(formData)) {
                return;
            }

            this.submitForm(form, 'signup', formData);
        },

        // Handle forgot password form submission
        handleForgotPassword: function(e) {
            e.preventDefault();

            const form = e.target;
            const formData = new FormData(form);

            // Client-side validation
            if (!this.validateForgotForm(formData)) {
                return;
            }

            this.submitForm(form, 'forgot', formData);
        },

        handleGoogleCredential: function(googleResponse) {
            const credential = googleResponse && googleResponse.credential ? googleResponse.credential : '';
            const $errorDiv = $(`#myavana${this.currentForm === 'signup' ? 'Signup' : 'Signin'}Form`).find('.myavana-error-message');
            const $successDiv = $(`#myavana${this.currentForm === 'signup' ? 'Signup' : 'Signin'}Form`).find('.myavana-success-message');

            if (!credential) {
                $errorDiv.html('Google sign-in did not return a valid credential.').fadeIn();
                return;
            }

            $errorDiv.fadeOut();
            $successDiv.fadeOut();

            this.refreshAuthNonces().done(() => {
                $.ajax({
                    url: this.settings.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'myavana_google_auth',
                        nonce: this.settings.google_nonce,
                        credential: credential
                    },
                    timeout: 15000,
                    success: (response) => {
                        if (!response || !response.success) {
                            $errorDiv.html((response && response.data && response.data.message) ? response.data.message : 'Google sign-in failed.').fadeIn();
                            return;
                        }

                        $successDiv.html(response.data.message || 'Signed in successfully.').fadeIn();
                        this.trackEvent('google_auth_success', {
                            user_id: response.data.user_id || null
                        });

                        setTimeout(() => {
                            if (response.data.trigger_onboarding) {
                                this.closeModal();
                                this.triggerOnboarding();
                                return;
                            }

                            const redirectUrl = this.getPostLoginRedirectUrl();
                            if (redirectUrl) {
                                window.location.href = redirectUrl;
                            } else {
                                window.location.reload();
                            }
                        }, 1200);
                    },
                    error: () => {
                        $errorDiv.html('Unable to sign in with Google right now. Please try again.').fadeIn();
                    }
                });
            }).fail(() => {
                $errorDiv.html('Unable to verify the sign-in session right now. Please refresh and try again.').fadeIn();
            });
        },

        refreshAuthNonces: function() {
            if (!this.settings.ajax_url) {
                return $.Deferred().reject().promise();
            }

            return $.ajax({
                url: this.settings.ajax_url,
                type: 'POST',
                data: {
                    action: 'myavana_refresh_auth_nonce'
                },
                timeout: 10000
            }).then((response) => {
                if (response && response.success && response.data) {
                    if (response.data.nonce) {
                        this.settings.nonce = response.data.nonce;
                    }
                    if (response.data.google_nonce) {
                        this.settings.google_nonce = response.data.google_nonce;
                    }
                    return response.data;
                }

                return $.Deferred().reject(response).promise();
            });
        },

        // Generic form submission handler
        submitForm: function(form, action, formData) {
            const $form = $(form);
            const $submitBtn = $form.find('.myavana-submit-btn');
            const $errorDiv = $form.find('.myavana-error-message');
            const $successDiv = $form.find('.myavana-success-message');

            // UI updates
            $submitBtn.prop('disabled', true).addClass('loading');
            $errorDiv.fadeOut();
            $successDiv.fadeOut();

            // Track submission attempt
            this.trackEvent('form_submitted', {
                form_type: action,
                timestamp: Date.now()
            });

            this.refreshAuthNonces().done(() => {
                if (typeof formData.set === 'function') {
                    formData.set('action', 'myavana_auth_submit');
                    formData.set('auth_action', action);
                    formData.set('nonce', this.settings.nonce);
                } else {
                    formData.append('action', 'myavana_auth_submit');
                    formData.append('auth_action', action);
                    formData.append('nonce', this.settings.nonce);
                }

                // Submit via AJAX
                $.ajax({
                    url: this.settings.ajax_url,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    timeout: 15000, // 15 second timeout
                    success: this.handleSubmissionSuccess.bind(this, action, $successDiv, $errorDiv),
                    error: this.handleSubmissionError.bind(this, action, $errorDiv),
                    complete: () => {
                        $submitBtn.prop('disabled', false).removeClass('loading');
                    }
                });
            }).fail(() => {
                $submitBtn.prop('disabled', false).removeClass('loading');
                $errorDiv.html('Unable to refresh the security session. Please refresh and try again.').fadeIn();
            });
        },

        // Handle successful form submission
        handleSubmissionSuccess: function(action, $successDiv, $errorDiv, response) {
            if (response.success) {
                let message = response.data.message;

                // Add onboarding hint for new users
                if (action === 'signup' && response.data.trigger_onboarding) {
                    message += '<br><small>🎉 Get ready for your personalized setup!</small>';
                }

                $successDiv.html(message).fadeIn();

                // Track successful submission
                this.trackEvent('form_success', {
                    form_type: action,
                    user_id: response.data.user_id || null
                });

                // Handle post-success actions
                if (action === 'signin' || action === 'signup') {
                    setTimeout(() => {
                        // Check if onboarding should be triggered
                        if (response.data.trigger_onboarding) {
                            this.closeModal();
                            this.triggerOnboarding();
                        } else {
                            const redirectUrl = this.getPostLoginRedirectUrl();
                            if (redirectUrl) {
                                window.location.href = redirectUrl;
                            } else {
                                window.location.reload();
                            }
                        }
                    }, 2000);
                } else if (action === 'forgot') {
                    // For password reset, show additional info
                    setTimeout(() => {
                        $successDiv.append('<br><br><a href="#" id="backToSigninFromSuccess" style="color: var(--myavana-coral); text-decoration: underline; font-weight: 500;">← Return to Sign In</a>');

                        $('#backToSigninFromSuccess').on('click', (e) => {
                            e.preventDefault();
                            this.switchForm('signin');
                        });
                    }, 2000);
                }
            } else {
                $errorDiv.html(response.data.message).fadeIn();

                // Track failed submission
                this.trackEvent('form_error', {
                    form_type: action,
                    error: response.data.message
                });

                // Re-focus first input for better UX
                setTimeout(() => {
                    $(`.myavana-auth-form.active input:first`).focus();
                }, 100);
            }
        },

        // Handle form submission error
        handleSubmissionError: function(action, $errorDiv, xhr, status, error) {
            console.error('MYAVANA Auth Error:', error);

            let errorMessage = '⚠️ Connection error. Please check your internet and try again.';

            if (status === 'timeout') {
                errorMessage = '⏱️ Request timed out. Please try again.';
            }

            $errorDiv.html(errorMessage).fadeIn();

            // Track submission error
            this.trackEvent('form_ajax_error', {
                form_type: action,
                error: error,
                status: status
            });
        },

        // Client-side form validation
        validateSignInForm: function(formData) {
            const login = formData.get('login');
            const password = formData.get('password');

            if (!login || !password) {
                this.showValidationError('#myavanaSigninForm', 'Please fill in all required fields.');
                return false;
            }

            return true;
        },

        validateSignUpForm: function(formData) {
            const name = formData.get('name');
            const email = formData.get('email');
            const password = formData.get('password');
            const terms = formData.get('terms');

            if (!name || !email || !password || !terms) {
                this.showValidationError('#myavanaSignupForm', 'Please fill in all fields and accept the terms.');
                return false;
            }

            if (!this.isValidEmail(email)) {
                this.showValidationError('#myavanaSignupForm', 'Please enter a valid email address.');
                $('#myavana-signup-email').focus();
                return false;
            }

            if (password.length < 8) {
                this.showValidationError('#myavanaSignupForm', 'Password must be at least 8 characters long.');
                $('#myavana-signup-password').focus();
                return false;
            }

            return true;
        },

        validateForgotForm: function(formData) {
            const email = formData.get('email');

            if (!email) {
                this.showValidationError('#myavanaForgotForm', 'Please enter your email address.');
                return false;
            }

            if (!this.isValidEmail(email)) {
                this.showValidationError('#myavanaForgotForm', 'Please enter a valid email address.');
                return false;
            }

            return true;
        },

        // Show validation error
        showValidationError: function(formSelector, message) {
            const $errorDiv = $(formSelector).find('.myavana-error-message');
            $errorDiv.html(message).fadeIn();

            // Auto-hide after 5 seconds
            setTimeout(() => $errorDiv.fadeOut(), 5000);
        },

        // Validate individual input on the fly
        validateInput: function(e) {
            const $input = $(e.target);
            const value = $input.val();
            const type = $input.attr('type');
            let isValid = true;
            let message = '';

            // Remove previous validation styling
            $input.removeClass('invalid valid');

            if (value.length === 0) {
                return; // Don't validate empty fields in real-time
            }

            switch(type) {
                case 'email':
                    isValid = this.isValidEmail(value);
                    message = 'Please enter a valid email address';
                    break;

                case 'password':
                    const minLength = parseInt($input.attr('minlength')) || 8;
                    isValid = value.length >= minLength;
                    message = `Password must be at least ${minLength} characters`;
                    break;

                default:
                    isValid = value.trim().length > 0;
                    message = 'This field is required';
            }

            // Apply validation styling
            $input.addClass(isValid ? 'valid' : 'invalid');

            // Show/hide inline error
            let $inlineError = $input.siblings('.inline-error');
            if (!$inlineError.length) {
                $inlineError = $('<span class="inline-error" style="color: #d32f2f; font-size: 12px; margin-top: 4px; display: none;"></span>');
                $input.parent().append($inlineError);
            }

            if (!isValid) {
                $inlineError.text(message).fadeIn();
            } else {
                $inlineError.fadeOut();
            }
        },

        // Email validation helper
        isValidEmail: function(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        },

        // Reset all forms
        resetForms: function() {
            $('.myavana-auth-form')[0]?.reset();
            $('.myavana-error-message, .myavana-success-message').fadeOut().empty();
            $('.myavana-form-group input').removeClass('invalid valid');
            $('.inline-error').remove();
            $('.myavana-submit-btn').removeClass('loading').prop('disabled', false);
        },

        // Trigger onboarding flow
        triggerOnboarding: function() {
            console.log('MYAVANA: Triggering onboarding...');

            // Try to trigger onboarding via AJAX first
            $.ajax({
                url: this.settings.ajax_url,
                type: 'POST',
                data: {
                    action: 'myavana_trigger_onboarding',
                    nonce: this.settings.nonce
                },
                success: (response) => {
                    console.log('MYAVANA: Onboarding trigger response:', response);

                    // The onboarding overlay only exists on logged-in renders.
                    // If it is already present, open it immediately; otherwise reload.
                    if (typeof window.showMyavanaOnboarding === 'function') {
                        setTimeout(() => {
                            window.showMyavanaOnboarding();
                        }, 500);
                    } else {
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    }
                },
                error: (error) => {
                    console.error('MYAVANA: Failed to trigger onboarding:', error);
                    // Fallback to page reload
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                }
            });
        },

        // Redirect to onboarding (legacy method)
        redirectToOnboarding: function() {
            this.triggerOnboarding();
        },

        // Event tracking
        trackEvent: function(eventName, data = {}) {
            // Integration with analytics system
            if (window.Myavana && window.Myavana.Analytics) {
                window.Myavana.Analytics.track('auth_' + eventName, data);
            }

            // Fallback to console in debug mode
            if (this.settings.debug) {
                console.log('MYAVANA Auth Event:', eventName, data);
            }
        },

        // Public API methods
        api: {
            showModal: function(formType = 'signin') {
                // Force show the modal regardless of settings
                const $modal = $('#myavanaAuthModal');
                if ($modal.length === 0) {
                    console.warn('MYAVANA: Auth modal not found in DOM');
                    return;
                }

                // Normalize form type aliases
                const normalizedFormType = window.MyavanaAuth.normalizeFormType(formType);

                $modal.addClass('show');
                window.MyavanaAuth.switchForm(normalizedFormType);
                window.MyavanaAuth.isModalShown = true;
                window.MyavanaAuth.focusModal();
                window.MyavanaAuth.trackEvent('modal_shown_manual', { form_type: normalizedFormType });
            },

            closeModal: function() {
                window.MyavanaAuth.closeModal();
            },

            switchForm: function(formType) {
                window.MyavanaAuth.switchForm(formType);
            }
        }
    };

    // Initialize when DOM is ready
    $(document).ready(function() {
        console.log('MYAVANA: Auth system script loaded');

        window.MyavanaAuth.init();

        // Expose public API globally - multiple ways to ensure it works
        window.showMyavanaModal = window.MyavanaAuth.api.showModal;

        // Also expose on window object for broader compatibility
        window.MyavanaAuth.showModal = window.MyavanaAuth.api.showModal;

        // Always debug log for now
        console.log('MYAVANA Auth System initialized', {
            settings: window.MyavanaAuth.settings,
            modal_exists: $('#myavanaAuthModal').length > 0,
            is_logged_in: window.MyavanaAuth.settings.is_logged_in,
            show_modal: window.MyavanaAuth.shouldShowModal()
        });

        // Test if modal appears automatically
        setTimeout(function() {
            console.log('MYAVANA: Testing modal visibility after 3 seconds', {
                modal_visible: $('#myavanaAuthModal').hasClass('show'),
                modal_exists: $('#myavanaAuthModal').length > 0
            });
        }, 3000);
    });

    // Add CSS for real-time validation
    const validationCSS = `
        <style>
        .myavana-form-group input.invalid {
            border-color: #d32f2f !important;
            box-shadow: 0 0 0 3px rgba(211, 47, 47, 0.1) !important;
        }
        .myavana-form-group input.valid {
            border-color: #388e3c !important;
            box-shadow: 0 0 0 3px rgba(56, 142, 60, 0.1) !important;
        }
        .sr-only {
            position: absolute !important;
            width: 1px !important;
            height: 1px !important;
            padding: 0 !important;
            margin: -1px !important;
            overflow: hidden !important;
            clip: rect(0,0,0,0) !important;
            white-space: nowrap !important;
            border: 0 !important;
        }
        </style>
    `;

    $('head').append(validationCSS);

})(jQuery);
