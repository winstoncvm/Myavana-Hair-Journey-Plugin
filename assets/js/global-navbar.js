(function($) {
    'use strict';

    function closeProfileMenus() {
        $('.myavana-app-profile-dropdown').removeClass('is-open');
        $('.myavana-app-profile-toggle').attr('aria-expanded', 'false');
    }

    $(document).ready(function() {
        const $navbar = $('#myavanaGlobalNavbar');
        const $headerToggle = $('#myavanaMobileHeaderToggle');
        if ($navbar.length === 0) {
            return;
        }

        window.myavanaAiToolUrl = window.myavanaAiToolUrl || 'https://www.myavana.com/pages/consumer';
        const storageKey = 'myavana-mobile-header-collapsed';

        function isMobileViewport() {
            return window.matchMedia('(max-width: 992px)').matches;
        }

        function isAppShell() {
            return document.body.classList.contains('myavana-app-shell');
        }

        function setMobileHeaderCollapsed(collapsed) {
            if (!isAppShell() || !isMobileViewport()) {
                document.body.classList.remove('myavana-mobile-header-collapsed');
                if ($headerToggle.length) {
                    $headerToggle.attr('aria-expanded', 'true');
                    $headerToggle.attr('aria-label', 'Minimize header');
                }
                return;
            }

            document.body.classList.toggle('myavana-mobile-header-collapsed', collapsed);
            if (collapsed) {
                closeProfileMenus();
            }

            if ($headerToggle.length) {
                $headerToggle.attr('aria-expanded', collapsed ? 'false' : 'true');
                $headerToggle.attr('aria-label', collapsed ? 'Expand header' : 'Minimize header');
            }
        }

        function syncMobileHeaderState() {
            if (!isAppShell() || !$headerToggle.length) {
                document.body.classList.remove('myavana-mobile-header-collapsed');
                return;
            }

            if (!isMobileViewport()) {
                document.body.classList.remove('myavana-mobile-header-collapsed');
                $headerToggle.attr('aria-expanded', 'true');
                return;
            }

            const stored = window.sessionStorage.getItem(storageKey);
            const collapsed = stored === null ? true : stored === '1';
            setMobileHeaderCollapsed(collapsed);
        }

        function syncScrolledState() {
            const isScrolled = window.scrollY > 6;
            $navbar.toggleClass('is-scrolled', isScrolled);
        }

        syncScrolledState();
        syncMobileHeaderState();
        $(window).on('scroll.myavanaGlobalNavbar', syncScrolledState);
        $(window).on('resize.myavanaGlobalNavbar', syncMobileHeaderState);

        $headerToggle.on('click', function(e) {
            e.preventDefault();
            if (!isAppShell() || !isMobileViewport()) {
                return;
            }

            const shouldCollapse = !document.body.classList.contains('myavana-mobile-header-collapsed');
            window.sessionStorage.setItem(storageKey, shouldCollapse ? '1' : '0');
            setMobileHeaderCollapsed(shouldCollapse);
        });

        $(document).on('click', '.myavana-app-profile-toggle', function(e) {
            e.preventDefault();
            e.stopPropagation();

            const $dropdown = $(this).closest('.myavana-app-profile-dropdown');
            const wasOpen = $dropdown.hasClass('is-open');

            closeProfileMenus();

            if (!wasOpen) {
                $dropdown.addClass('is-open');
                $(this).attr('aria-expanded', 'true');
            }
        });

        $(document).on('click', '.myavana-app-profile-menu a', function() {
            closeProfileMenus();
        });

        $(document).on('click', function(e) {
            if (!$(e.target).closest('.myavana-app-profile-dropdown').length) {
                closeProfileMenus();
            }
        });

        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                closeProfileMenus();
            }
        });
    });
})(jQuery);
