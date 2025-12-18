/**
 * MYAVANA Hair Journey Fixes
 * Centralized JavaScript fixes for various issues across the application
 *
 * Fixes Applied:
 * 1. Profile edit offcanvas functionality
 * 2. Duplicate daily check-in prevention
 * 3. Mobile sidebar visibility
 * 4. Decoupled Add Analysis button
 * 5. Unified Core 403 AJAX error fix
 * 6. Enhanced view offcanvas handlers
 *
 * 
 * @package Myavana_Hair_Journey
 * @since 2.3.6
 */

(function($) {
    'use strict';

    // Initialize on DOM ready
    $(document).ready(function() {
        initializeHairJourneyFixes();
    });

    /**
     * Main initialization function
     */
    function initializeHairJourneyFixes() {
        fixProfileEditOffcanvas();
        fixDuplicateCheckInButtons();
        fixMobileSidebar();
        decoupleAddAnalysisButton();
        // fixUnifiedCoreAjax();
        initializeViewOffcanvasHandlers();
        addMobileCancelButtons();
        fixTimelineFilters();

        console.log('✅ MYAVANA Hair Journey fixes initialized');
    }

    /**
     * FIX 1: Profile Edit Offcanvas
     * Ensure openProfileEditOffcanvas is always available globally
     */
    function fixProfileEditOffcanvas() {
        // Make sure the function is globally accessible
        if (typeof window.openProfileEditOffcanvas !== 'function') {
            window.openProfileEditOffcanvas = function() {
                console.log('Opening profile edit offcanvas...');

                // Show overlay and offcanvas
                $('.offcanvas-overlay-hjn').addClass('active');
                $('.offcanvas-hjn.profile-edit').addClass('active');

                // Prevent body scroll
                $('body').css('overflow', 'hidden');

                // Load current profile data if available
                if (typeof window.myavanaSidebarProfile !== 'undefined' &&
                    typeof window.myavanaSidebarProfile.loadData === 'function') {
                    window.myavanaSidebarProfile.loadData();
                }
            };
        }

        // Ensure close handler is attached
        $(document).on('click', '.sidebar-avatar-edit, button[onclick*="openProfileEditOffcanvas"]', function(e) {
            e.preventDefault();
            e.stopPropagation();
            window.openProfileEditOffcanvas();
        });

        console.log('✅ Profile edit offcanvas fixed');
    }

    /**
     * FIX 2: Remove Duplicate Daily Check-in Buttons
     * Ensure only ONE check-in button exists
     */
    function fixDuplicateCheckInButtons() {
        const checkInButtons = $('.sidebar-checkin-btn, #myavana-checkin-btn');

        if (checkInButtons.length > 1) {
            console.warn(`Found ${checkInButtons.length} check-in buttons, removing duplicates...`);

            // Keep only the first one
            checkInButtons.slice(1).remove();
        }

        // Ensure button has proper event handler
        $(document).off('click', '.sidebar-checkin-btn, #myavana-checkin-btn');
        $(document).on('click', '.sidebar-checkin-btn, #myavana-checkin-btn', function(e) {
            e.preventDefault();
            e.stopPropagation();

            // Trigger gamification check-in
            if (typeof window.MyavanaGamification !== 'undefined' &&
                typeof window.MyavanaGamification.showDailyCheckIn === 'function') {
                window.MyavanaGamification.showDailyCheckIn();
            } else {
                console.error('Gamification system not loaded');
                alert('Check-in system is loading. Please try again in a moment.');
            }
        });

        console.log('✅ Duplicate check-in buttons fixed');
    }

    /**
     * FIX 3: Mobile Sidebar Visibility
     * Ensure sidebar is visible and functional on mobile
     */
    function fixMobileSidebar() {
        const sidebar = $('#sidebar, .sidebar');
        const mobileSidebarHeader = $('.sidebar-mobile-header');

        // Ensure sidebar has proper mobile classes
        if (sidebar.length && !sidebar.hasClass('mobile-optimized')) {
            sidebar.addClass('mobile-optimized');
        }

        // Add toggle functionality if missing
        if (mobileSidebarHeader.length && !mobileSidebarHeader.data('toggle-attached')) {
            mobileSidebarHeader.on('click', function() {
                toggleMobileSidebar();
            });
            mobileSidebarHeader.data('toggle-attached', true);
        }

        // Create toggle function if it doesn't exist
        if (typeof window.toggleMobileSidebar !== 'function') {
            window.toggleMobileSidebar = function() {
                const sidebar = $('#sidebar, .sidebar');
                const sidebarContent = $('#sidebarContent, .sidebar-content');
                const icon = $('#mobileSidebarIcon, .sidebar-mobile-toggle-icon');

                if (sidebar.hasClass('mobile-collapsed')) {
                    // Expand
                    sidebar.removeClass('mobile-collapsed');
                    sidebarContent.slideDown(300);
                    icon.addClass('rotated');
                } else {
                    // Collapse
                    sidebar.addClass('mobile-collapsed');
                    sidebarContent.slideUp(300);
                    icon.removeClass('rotated');
                }
            };
        }

        // Fix mobile visibility with media query
        function applyMobileStyles() {
            if ($(window).width() <= 768) {
                // On mobile, ensure sidebar is visible but collapsible
                sidebar.addClass('mobile-mode');

                // Start collapsed on mobile
                if (!sidebar.hasClass('mobile-collapsed')) {
                    sidebar.addClass('mobile-collapsed');
                    $('#sidebarContent, .sidebar-content').hide();
                }
            } else {
                // On desktop, always show sidebar
                sidebar.removeClass('mobile-mode mobile-collapsed');
                $('#sidebarContent, .sidebar-content').show();
            }
        }

        // Apply on load and resize
        applyMobileStyles();
        $(window).on('resize', applyMobileStyles);

        console.log('✅ Mobile sidebar visibility fixed');
    }

    /**
     * FIX 4: Decouple Add Analysis Button
     * Remove old event handlers and use clean, single handler
     */
    function decoupleAddAnalysisButton() {
        // Remove ALL existing handlers
        $(document).off('click', '#addAnalysisBtn, #start-first-analysis, .section-edit[data-section="analysis"]');

        // Attach single, clean handler
        $(document).on('click', '#addAnalysisBtn, #start-first-analysis, button.section-edit[data-section="analysis"]', function(e) {
            e.preventDefault();
            e.stopPropagation();

            console.log('Add Analysis button clicked');

            // Open AI analysis modal if available
            if (typeof window.openAIAnalysisModal === 'function') {
                window.openAIAnalysisModal();
            } else {
                // Fallback: toggle the tryon interface
                $('.hair-analysis-container').addClass('hidden');
                $('.myavana-tryon').removeClass('hidden');

                // Update button state
                const btn = $(this);
                btn.addClass('cancel-active');
                btn.find('i').removeClass('fa-plus').addClass('fa-times');
                btn.find('span').text('Cancel Analysis');
            }
        });

        console.log('✅ Add Analysis button decoupled');
    }

    /**
     * FIX 5: Unified Core AJAX 403 Error Fix
     * Add proper nonce to AJAX requests
     */
    // function fixUnifiedCoreAjax() {
    //     // Intercept Myavana unified core AJAX calls
    //     if (window.Myavana && window.Myavana.API) {
    //         const originalCall = window.Myavana.API.call;

    //         window.Myavana.API.call = function(endpoint, data, options) {
    //             // Add nonce if not present - use 'security' parameter as expected by WordPress
    //             if (!data.nonce && !data.security && window.myavanaAjax && window.myavanaAjax.nonce) {
    //                 data.security = window.myavanaAjax.nonce;
    //             }

    //             // Add security token (alternative parameter name)
    //             if (!data._wpnonce && window.myavanaAjax && window.myavanaAjax.nonce) {
    //                 data._wpnonce = window.myavanaAjax.nonce;
    //             }

    //             return originalCall.call(this, endpoint, data, options);
    //         };
    //     }

    //     // Also fix direct fetch calls
    //     const originalFetch = window.fetch;
    //     window.fetch = function(url, options) {
    //         // Only intercept WordPress AJAX calls
    //         if (url.includes('admin-ajax.php')) {
    //             options = options || {};
    //             options.credentials = options.credentials || 'same-origin';
    //             options.headers = options.headers || {};

    //             // Add WordPress headers that may be required
    //             if (!options.headers['X-Requested-With']) {
    //                 options.headers['X-Requested-With'] = 'XMLHttpRequest';
    //             }

    //             // Ensure nonce is in the body for POST requests
    //             if (options.method === 'POST' && options.body) {
    //                 const formData = new FormData();

    //                 if (options.body instanceof FormData) {
    //                     // Clone existing FormData
    //                     for (let [key, value] of options.body.entries()) {
    //                         formData.append(key, value);
    //                     }
    //                 } else if (typeof options.body === 'string') {
    //                     // Parse URL-encoded or JSON string
    //                     try {
    //                         const data = JSON.parse(options.body);
    //                         for (let key in data) {
    //                             formData.append(key, data[key]);
    //                         }
    //                     } catch(e) {
    //                         // URL-encoded
    //                         new URLSearchParams(options.body).forEach((value, key) => {
    //                             formData.append(key, value);
    //                         });
    //                     }
    //                 }

    //                 // Add nonce if available and not already present - use the correct nonce for the action
    //                 if (!formData.has('nonce') && !formData.has('security')) {
    //                     // Try to get the correct nonce for the specific action
    //                     let nonce = null;
    //                     if (window.myavanaTimelineSettings && window.myavanaTimelineSettings.getEntryDetailsNonce) {
    //                         nonce = window.myavanaTimelineSettings.getEntryDetailsNonce;
    //                     } else if (window.myavanaAjax && window.myavanaAjax.nonce) {
    //                         nonce = window.myavanaAjax.nonce;
    //                     }
    //                     if (nonce) {
    //                         formData.append('security', nonce);
    //                     }
    //                 }

    //                 options.body = formData;
    //             } else if (options.method === 'POST' && !options.body) {
    //                 // Create FormData for POST requests without body
    //                 const formData = new FormData();

    //                 // Add nonce - use the correct nonce for the specific action
    //                 let nonce = null;
    //                 if (window.myavanaTimelineSettings && window.myavanaTimelineSettings.getEntryDetailsNonce) {
    //                     nonce = window.myavanaTimelineSettings.getEntryDetailsNonce;
    //                 } else if (window.myavanaAjax && window.myavanaAjax.nonce) {
    //                     nonce = window.myavanaAjax.nonce;
    //                 }
    //                 if (nonce) {
    //                     formData.append('security', nonce);
    //                 }
    //                 options.body = formData;
    //             }
    //         }

    //         return originalFetch(url, options);
    //     };

    //     console.log('✅ Unified Core AJAX error fixed');
    // }

    /**
     * FIX 6: Enhanced View Offcanvas Handlers
     * Goal and Routine view offcanvas functionality
     */
    function initializeViewOffcanvasHandlers() {
        // Entry view click handler (for timeline entries)
        $(document).on('click', '.entry-view-btn, .view-entry-btn, .floating-action-btn', function(e) {
            e.preventDefault();
            e.stopPropagation();

            const entryId = $(this).data('entry-id') || $(this).data('id') || $(this).closest('[data-entry-id]').data('entry-id');
            if (entryId) {
                openViewOffcanvas('entry', entryId);
            } else {
                console.error('No entry ID found for view button');
            }
        });

        // Goal click handler in calendar
        $(document).on('click', '.calendar-list-goal-hjn, .goal-bar-span-new, .calendar-week-goal-bar-hjn', function(e) {
            e.preventDefault();
            e.stopPropagation();

            const goalId = $(this).data('goal-id') || $(this).data('index') || 0;
            openViewOffcanvas('goal', goalId);
        });

        // Routine click handler in calendar
        $(document).on('click', '.calendar-list-routine-hjn, .routine-stack-card', function(e) {
            e.preventDefault();
            e.stopPropagation();

            const routineId = $(this).data('routine-id') || $(this).data('index') || 0;
            openViewOffcanvas('routine', routineId);
        });

        console.log('✅ Enhanced view offcanvas handlers initialized');
    }

    /**
     * Open view offcanvas for entry, goal, or routine
     */
    function openViewOffcanvas(type, id) {
        console.log(`Opening ${type} view offcanvas for ID: ${id}`);

        // Use the new modular timeline view system if available
        if (typeof window.MyavanaTimeline !== 'undefined' &&
            typeof window.MyavanaTimeline.View !== 'undefined' &&
            typeof window.MyavanaTimeline.View.openView === 'function') {
            window.MyavanaTimeline.View.openView(type, id);
        } else if (typeof window.openViewOffcanvas === 'function') {
            // Fallback to global function
            window.openViewOffcanvas(type, id);
        } else {
            // Manual fallback: manually trigger offcanvas
            const offcanvas = $(`#${type}ViewOffcanvas, .offcanvas-hjn.view-${type}`);
            if (offcanvas.length) {
                $('#viewOffcanvasOverlay, .offcanvas-overlay-hjn').addClass('active');
                offcanvas.addClass('active');
                $('body').css('overflow', 'hidden');

                // Load data for the specific item
                loadOffcanvasData(type, id);
            } else {
                console.error(`Offcanvas for ${type} not found`);
            }
        }
    }

    /**
     * Load data into offcanvas
     */
    function loadOffcanvasData(type, id) {
        // Fetch data via AJAX
        $.ajax({
            url: window.myavanaAjax.ajax_url,
            type: 'POST',
            data: {
                action: `myavana_get_${type}_details`,
                nonce: window.myavanaAjax.nonce,
                id: id
            },
            success: function(response) {
                if (response.success && response.data) {
                    populateOffcanvas(type, response.data);
                } else {
                    console.error(`Failed to load ${type} data:`, response);
                }
            },
            error: function(xhr, status, error) {
                console.error(`AJAX error loading ${type}:`, error);
            }
        });
    }

    /**
     * Populate offcanvas with data
     */
    function populateOffcanvas(type, data) {
        const offcanvas = $(`.offcanvas-hjn.view-${type}`);

        if (type === 'goal') {
            offcanvas.find('.offcanvas-title-hjn').text(data.title || 'Goal Details');
            offcanvas.find('.goal-description').text(data.description || '');
            offcanvas.find('.progress-percentage').text(`${data.progress || 0}%`);
            offcanvas.find('.progress-fill').css('width', `${data.progress || 0}%`);
            // ... populate other fields
        } else if (type === 'routine') {
            offcanvas.find('.offcanvas-title-hjn').text(data.title || data.name || 'Routine Details');
            offcanvas.find('.routine-time').text(data.time || '');
            offcanvas.find('.routine-frequency').text(data.frequency || '');
            // ... populate other fields
        }
    }

    /**
     * FIX 7: Add Mobile Cancel Buttons
     * Add cancel buttons at bottom of forms for better mobile UX
     */
    function addMobileCancelButtons() {
        // Find all offcanvas forms that don't have bottom cancel buttons
        $('.offcanvas-hjn form').each(function() {
            const form = $(this);
            const footer = form.closest('.offcanvas-hjn').find('.offcanvas-footer-hjn');

            // Check if cancel button already exists in footer
            if (footer.length && footer.find('.btn-cancel, .btn-secondary-hjn').length === 0) {
                // Add cancel button if missing
                const cancelBtn = $('<button type="button" class="btn-secondary-hjn mobile-cancel-btn">Cancel</button>');
                cancelBtn.on('click', function() {
                    form.closest('.offcanvas-hjn').removeClass('active');
                    $('.offcanvas-overlay-hjn').removeClass('active');
                    $('body').css('overflow', '');
                });

                footer.prepend(cancelBtn);
            }
        });

        // Also add cancel button to entry creation modal close button area
        const entryModal = $('.create-offcanvas-hjn, .offcanvas-hjn[data-type="entry"]');
        if (entryModal.length) {
            const closeBtn = entryModal.find('.offcanvas-close-hjn');
            if (closeBtn.length) {
                // Make close button more touch-friendly on mobile
                closeBtn.css({
                    'min-width': '44px',
                    'min-height': '44px',
                    'display': 'flex',
                    'align-items': 'center',
                    'justify-content': 'center'
                });
            }
        }

        console.log('✅ Mobile cancel buttons added');
    }

    /**
     * FIX 8: Timeline Filters, Sort and Search
     * Fix non-working timeline filters
     */
    function fixTimelineFilters() {
        // Ensure filter handlers are attached
        if (typeof window.applyTimelineFilters !== 'function') {
            window.applyTimelineFilters = function() {
                const searchTerm = $('#timelineSearch').val().toLowerCase();
                const selectedType = $('#timelineTypeFilter').val();
                const selectedSort = $('#timelineSortFilter').val();

                let $entries = $('.timeline-entry-card, .timeline-entry, .entry-card');

                // Apply search filter
                $entries.each(function() {
                    const $entry = $(this);
                    const title = $entry.find('.entry-title, h3, h4').text().toLowerCase();
                    const content = $entry.find('.entry-content, p').text().toLowerCase();

                    const matchesSearch = searchTerm === '' ||
                        title.includes(searchTerm) ||
                        content.includes(searchTerm);

                    const matchesType = selectedType === 'all' ||
                        $entry.data('type') === selectedType;

                    if (matchesSearch && matchesType) {
                        $entry.show();
                    } else {
                        $entry.hide();
                    }
                });

                // Apply sorting
                if (selectedSort && selectedSort !== 'default') {
                    const $container = $entries.parent();
                    const $sorted = $entries.sort(function(a, b) {
                        const $a = $(a);
                        const $b = $(b);

                        if (selectedSort === 'date-asc') {
                            return new Date($a.data('date')) - new Date($b.data('date'));
                        } else if (selectedSort === 'date-desc') {
                            return new Date($b.data('date')) - new Date($a.data('date'));
                        } else if (selectedSort === 'rating-high') {
                            return ($b.data('rating') || 0) - ($a.data('rating') || 0);
                        } else if (selectedSort === 'rating-low') {
                            return ($a.data('rating') || 0) - ($b.data('rating') || 0);
                        }
                        return 0;
                    });

                    $container.html($sorted);
                }

                console.log('Timeline filters applied');
            };
        }

        // Attach filter event handlers
        $(document).on('input change', '#timelineSearch, #timelineTypeFilter, #timelineSortFilter', function() {
            window.applyTimelineFilters();
        });

        // Clear filters button
        $(document).on('click', '#clearTimelineFilters, .clear-filters-btn', function() {
            $('#timelineSearch').val('');
            $('#timelineTypeFilter').val('all');
            $('#timelineSortFilter').val('default');
            window.applyTimelineFilters();
        });

        console.log('✅ Timeline filters fixed');
    }

    /**
     * Make List View Responsive
     */
    function makeListViewResponsive() {
        // This will be handled by CSS primarily
        // Just ensure proper classes are applied
        $('.list-view-entry, .list-view-goal, .list-view-routine').each(function() {
            $(this).addClass('responsive-list-item');
        });
    }

    // Initialize responsive list view
    makeListViewResponsive();

})(jQuery);
