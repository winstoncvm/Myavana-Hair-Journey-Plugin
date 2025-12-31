/**
 * MYAVANA Gesture Handlers
 * Touch gestures: Swipe, Pull-to-Refresh, Long-Press, Pinch-to-Zoom
 *
 * @package Myavana_Hair_Journey
 * @version 1.0.0
 */

(function($) {
    'use strict';

    window.MyavanaGestures = {
        enabled: true,
        touchStartX: 0,
        touchStartY: 0,
        touchEndX: 0,
        touchEndY: 0,
        pullStartY: 0,
        isPulling: false,
        longPressTimer: null,
        longPressDelay: 500, // ms
        swipeThreshold: 50, // px
        pullThreshold: 80 // px
    };

    /**
     * Initialize All Gesture Handlers
     */
    function initGestures() {
        if (!isTouchDevice()) {
            console.log('[Gestures] Not a touch device, skipping initialization');
            return;
        }

        initSwipeGestures();
        initPullToRefresh();
        initLongPress();
        initSwipeToDismiss();

        console.log('[Gestures] Initialized successfully');
    }

    /**
     * Check if device supports touch
     */
    function isTouchDevice() {
        return ('ontouchstart' in window) || (navigator.maxTouchPoints > 0);
    }

    /* ============================================
       SWIPE GESTURES
       ============================================ */
    function initSwipeGestures() {
        let touchStartX = 0;
        let touchStartY = 0;
        let touchEndX = 0;
        let touchEndY = 0;

        // Swipeable containers
        const swipeSelectors = [
            '.myavana-timeline-slider',
            '.upm-cm-story-viewer',
            '.myavana-photo-gallery',
            '.myavana-swipeable'
        ];

        swipeSelectors.forEach(selector => {
            $(document).on('touchstart', selector, function(e) {
                touchStartX = e.changedTouches[0].screenX;
                touchStartY = e.changedTouches[0].screenY;
            });

            $(document).on('touchend', selector, function(e) {
                touchEndX = e.changedTouches[0].screenX;
                touchEndY = e.changedTouches[0].screenY;

                handleSwipe(this, touchStartX, touchStartY, touchEndX, touchEndY);
            });
        });
    }

    /**
     * Handle Swipe Direction
     */
    function handleSwipe(element, startX, startY, endX, endY) {
        const deltaX = endX - startX;
        const deltaY = endY - startY;
        const absDeltaX = Math.abs(deltaX);
        const absDeltaY = Math.abs(deltaY);

        // Horizontal swipe
        if (absDeltaX > absDeltaY && absDeltaX > MyavanaGestures.swipeThreshold) {
            if (deltaX > 0) {
                onSwipeRight(element);
            } else {
                onSwipeLeft(element);
            }
        }

        // Vertical swipe
        if (absDeltaY > absDeltaX && absDeltaY > MyavanaGestures.swipeThreshold) {
            if (deltaY > 0) {
                onSwipeDown(element);
            } else {
                onSwipeUp(element);
            }
        }
    }

    /**
     * Swipe Right Handler
     */
    function onSwipeRight(element) {
        console.log('[Gestures] Swipe Right');
        $(element).trigger('myavana:swipeRight');

        // Timeline navigation
        if ($(element).hasClass('myavana-timeline-slider')) {
            navigateTimeline('prev');
        }

        // Story viewer
        if ($(element).hasClass('upm-cm-story-viewer')) {
            navigateStory('prev');
        }

        // Photo gallery
        if ($(element).hasClass('myavana-photo-gallery')) {
            navigateGallery('prev');
        }
    }

    /**
     * Swipe Left Handler
     */
    function onSwipeLeft(element) {
        console.log('[Gestures] Swipe Left');
        $(element).trigger('myavana:swipeLeft');

        // Timeline navigation
        if ($(element).hasClass('myavana-timeline-slider')) {
            navigateTimeline('next');
        }

        // Story viewer
        if ($(element).hasClass('upm-cm-story-viewer')) {
            navigateStory('next');
        }

        // Photo gallery
        if ($(element).hasClass('myavana-photo-gallery')) {
            navigateGallery('next');
        }
    }

    /**
     * Swipe Up Handler
     */
    function onSwipeUp(element) {
        console.log('[Gestures] Swipe Up');
        $(element).trigger('myavana:swipeUp');
    }

    /**
     * Swipe Down Handler
     */
    function onSwipeDown(element) {
        console.log('[Gestures] Swipe Down');
        $(element).trigger('myavana:swipeDown');
    }

    /**
     * Navigate Timeline
     */
    function navigateTimeline(direction) {
        if (typeof window.MyavanaTimeline !== 'undefined') {
            if (direction === 'next') {
                window.MyavanaTimeline.nextEntry();
            } else {
                window.MyavanaTimeline.prevEntry();
            }
        }
    }

    /**
     * Navigate Story
     */
    function navigateStory(direction) {
        // Story navigation logic
        const $viewer = $('.upm-cm-story-viewer');
        if ($viewer.length) {
            $viewer.trigger(`story:${direction}`);
        }
    }

    /**
     * Navigate Gallery
     */
    function navigateGallery(direction) {
        // Photo gallery navigation
        const $gallery = $('.myavana-photo-gallery');
        if ($gallery.length) {
            $gallery.trigger(`gallery:${direction}`);
        }
    }

    /* ============================================
       PULL TO REFRESH
       ============================================ */
    function initPullToRefresh() {
        let pullStartY = 0;
        let isPulling = false;
        let canPull = false;

        // Scrollable containers that support pull-to-refresh
        const refreshContainers = [
            '.myavana-feed',
            '.upm-cm-tab-panel',
            '.myavana-timeline-area'
        ];

        $(document).on('touchstart', refreshContainers.join(','), function(e) {
            const scrollTop = $(this).scrollTop();
            canPull = scrollTop === 0; // Only allow pull at top

            if (canPull) {
                pullStartY = e.changedTouches[0].screenY;
            }
        });

        $(document).on('touchmove', refreshContainers.join(','), function(e) {
            if (!canPull) return;

            const currentY = e.changedTouches[0].screenY;
            const pullDistance = currentY - pullStartY;

            if (pullDistance > 0) {
                isPulling = true;
                updatePullIndicator(pullDistance);
            }
        });

        $(document).on('touchend', refreshContainers.join(','), function(e) {
            if (!isPulling) return;

            const endY = e.changedTouches[0].screenY;
            const pullDistance = endY - pullStartY;

            if (pullDistance > MyavanaGestures.pullThreshold) {
                triggerRefresh(this);
            }

            resetPullIndicator();
            isPulling = false;
            canPull = false;
        });
    }

    /**
     * Update Pull-to-Refresh Indicator
     */
    function updatePullIndicator(distance) {
        const $indicator = $('#myavana-pull-refresh');
        const progress = Math.min(distance / MyavanaGestures.pullThreshold, 1);

        $indicator.css({
            display: 'flex',
            opacity: progress,
            transform: `translateY(${distance * 0.5}px)`
        });

        if (progress >= 1) {
            $indicator.find('.myavana-pull-refresh-text').text('Release to refresh');
            $indicator.addClass('ready');
        } else {
            $indicator.find('.myavana-pull-refresh-text').text('Pull to refresh');
            $indicator.removeClass('ready');
        }
    }

    /**
     * Reset Pull Indicator
     */
    function resetPullIndicator() {
        $('#myavana-pull-refresh').fadeOut(200, function() {
            $(this).css({transform: 'translateY(0)', opacity: 0});
            $(this).removeClass('ready');
        });
    }

    /**
     * Trigger Content Refresh
     */
    function triggerRefresh(container) {
        const $indicator = $('#myavana-pull-refresh');
        $indicator.find('.myavana-pull-refresh-spinner').addClass('spinning');
        $indicator.find('.myavana-pull-refresh-text').text('Refreshing...');

        console.log('[Gestures] Pull-to-refresh triggered');

        // Trigger custom event
        $(container).trigger('myavana:refresh');

        // Determine what to refresh based on container
        if ($(container).hasClass('myavana-feed')) {
            refreshFeed();
        } else if ($(container).hasClass('upm-cm-tab-panel')) {
            refreshCurrentTab();
        } else if ($(container).hasClass('myavana-timeline-area')) {
            refreshTimeline();
        } else {
            // Generic refresh - reload page data
            setTimeout(() => {
                resetPullIndicator();
            }, 1000);
        }
    }

    /**
     * Refresh Feed
     */
    function refreshFeed() {
        if (typeof window.myavanaCommunity !== 'undefined') {
            window.myavanaCommunity.refreshFeed().then(() => {
                resetPullIndicator();
                showRefreshToast('Feed refreshed!');
            });
        } else {
            setTimeout(() => {
                resetPullIndicator();
            }, 1000);
        }
    }

    /**
     * Refresh Current Tab
     */
    function refreshCurrentTab() {
        if (typeof window.UPM_CM !== 'undefined') {
            const currentTab = window.UPM_CM.currentTab;
            // Reload tab content
            setTimeout(() => {
                resetPullIndicator();
                showRefreshToast('Content refreshed!');
            }, 1000);
        }
    }

    /**
     * Refresh Timeline
     */
    function refreshTimeline() {
        if (typeof window.MyavanaTimeline !== 'undefined') {
            window.MyavanaTimeline.reload().then(() => {
                resetPullIndicator();
                showRefreshToast('Timeline refreshed!');
            });
        } else {
            setTimeout(() => {
                resetPullIndicator();
            }, 1000);
        }
    }

    /**
     * Show Refresh Toast
     */
    function showRefreshToast(message) {
        const $toast = $(`
            <div class="myavana-toast">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                ${message}
            </div>
        `);

        $('body').append($toast);
        setTimeout(() => $toast.addClass('show'), 100);
        setTimeout(() => {
            $toast.removeClass('show');
            setTimeout(() => $toast.remove(), 300);
        }, 2000);
    }

    /* ============================================
       LONG PRESS
       ============================================ */
    function initLongPress() {
        const longPressSelectors = [
            '.myavana-entry-card',
            '.myavana-feed-card',
            '.upm-cm-goal-card',
            '.upm-cm-routine-card',
            '.myavana-long-press'
        ];

        $(document).on('touchstart', longPressSelectors.join(','), function(e) {
            const $element = $(this);

            MyavanaGestures.longPressTimer = setTimeout(() => {
                onLongPress($element);
            }, MyavanaGestures.longPressDelay);
        });

        $(document).on('touchend touchmove', longPressSelectors.join(','), function() {
            if (MyavanaGestures.longPressTimer) {
                clearTimeout(MyavanaGestures.longPressTimer);
            }
        });
    }

    /**
     * Long Press Handler
     */
    function onLongPress($element) {
        console.log('[Gestures] Long press detected');

        // Haptic feedback
        if (navigator.vibrate) {
            navigator.vibrate(50);
        }

        // Trigger custom event
        $element.trigger('myavana:longPress');

        // Show context menu
        showContextMenu($element);
    }

    /**
     * Show Context Menu
     */
    function showContextMenu($element) {
        const options = [];

        // Entry card options
        if ($element.hasClass('myavana-entry-card')) {
            options.push(
                {icon: '✏️', text: 'Edit', action: 'edit'},
                {icon: '👁️', text: 'View', action: 'view'},
                {icon: '🔗', text: 'Share', action: 'share'},
                {icon: '🗑️', text: 'Delete', action: 'delete', danger: true}
            );
        }

        // Feed card options
        if ($element.hasClass('myavana-feed-card')) {
            options.push(
                {icon: '🔖', text: 'Save', action: 'save'},
                {icon: '🔗', text: 'Share', action: 'share'},
                {icon: '📋', text: 'Copy Link', action: 'copy'},
                {icon: '🚫', text: 'Report', action: 'report', danger: true}
            );
        }

        // Goal/Routine card options
        if ($element.hasClass('upm-cm-goal-card') || $element.hasClass('upm-cm-routine-card')) {
            options.push(
                {icon: '✏️', text: 'Edit', action: 'edit'},
                {icon: '🔗', text: 'Share', action: 'share'},
                {icon: '✅', text: 'Complete', action: 'complete'},
                {icon: '🗑️', text: 'Delete', action: 'delete', danger: true}
            );
        }

        if (options.length === 0) return;

        const $menu = createContextMenu(options, $element);
        $('body').append($menu);

        setTimeout(() => $menu.addClass('show'), 10);
    }

    /**
     * Create Context Menu HTML
     */
    function createContextMenu(options, $targetElement) {
        const $menu = $(`
            <div class="myavana-context-menu">
                <div class="myavana-context-menu-overlay"></div>
                <div class="myavana-context-menu-content">
                    ${options.map(opt => `
                        <button class="myavana-context-option ${opt.danger ? 'danger' : ''}" data-action="${opt.action}">
                            <span class="myavana-context-icon">${opt.icon}</span>
                            <span>${opt.text}</span>
                        </button>
                    `).join('')}
                    <button class="myavana-context-option cancel">
                        <span>Cancel</span>
                    </button>
                </div>
            </div>
        `);

        // Handle option clicks
        $menu.find('.myavana-context-option').on('click', function() {
            const action = $(this).data('action');
            if (action) {
                handleContextAction(action, $targetElement);
            }
            closeContextMenu($menu);
        });

        // Close on overlay click
        $menu.find('.myavana-context-menu-overlay').on('click', function() {
            closeContextMenu($menu);
        });

        return $menu;
    }

    /**
     * Handle Context Menu Action
     */
    function handleContextAction(action, $element) {
        console.log('[Gestures] Context action:', action);

        const elementId = $element.data('id');

        switch (action) {
            case 'edit':
                $element.trigger('myavana:edit', [elementId]);
                break;
            case 'view':
                $element.trigger('myavana:view', [elementId]);
                break;
            case 'share':
                $element.trigger('myavana:share', [elementId]);
                break;
            case 'delete':
                $element.trigger('myavana:delete', [elementId]);
                break;
            case 'save':
                $element.trigger('myavana:save', [elementId]);
                break;
            case 'copy':
                copyToClipboard(window.location.href + '#' + elementId);
                showRefreshToast('Link copied!');
                break;
            case 'report':
                $element.trigger('myavana:report', [elementId]);
                break;
            case 'complete':
                $element.trigger('myavana:complete', [elementId]);
                break;
        }
    }

    /**
     * Close Context Menu
     */
    function closeContextMenu($menu) {
        $menu.removeClass('show');
        setTimeout(() => $menu.remove(), 300);
    }

    /* ============================================
       SWIPE TO DISMISS
       ============================================ */
    function initSwipeToDismiss() {
        const dismissSelectors = [
            '.myavana-modal',
            '.myavana-notification',
            '.myavana-dismissible'
        ];

        $(document).on('touchstart', dismissSelectors.join(','), function(e) {
            if ($(e.target).closest('.myavana-modal-content, .myavana-notification-content').length) {
                return; // Don't dismiss if touching content
            }

            MyavanaGestures.touchStartY = e.changedTouches[0].screenY;
        });

        $(document).on('touchend', dismissSelectors.join(','), function(e) {
            if ($(e.target).closest('.myavana-modal-content, .myavana-notification-content').length) {
                return;
            }

            MyavanaGestures.touchEndY = e.changedTouches[0].screenY;
            const swipeDistance = MyavanaGestures.touchEndY - MyavanaGestures.touchStartY;

            // Swipe down to dismiss
            if (swipeDistance > 100) {
                $(this).fadeOut(200, function() {
                    if ($(this).hasClass('myavana-modal')) {
                        $('body').removeClass('myavana-modal-open');
                    }
                    $(this).remove();
                });
            }
        });
    }

    /* ============================================
       UTILITY FUNCTIONS
       ============================================ */
    function copyToClipboard(text) {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text);
        } else {
            // Fallback for older browsers
            const $temp = $('<input>');
            $('body').append($temp);
            $temp.val(text).select();
            document.execCommand('copy');
            $temp.remove();
        }
    }

    // Public API
    window.MyavanaGestures.init = initGestures;
    window.MyavanaGestures.enable = function() {
        MyavanaGestures.enabled = true;
    };
    window.MyavanaGestures.disable = function() {
        MyavanaGestures.enabled = false;
    };

    // Auto-initialize
    $(document).ready(function() {
        if (isTouchDevice()) {
            initGestures();
        }
    });

    console.log('[Gestures] Module loaded');

})(jQuery);
