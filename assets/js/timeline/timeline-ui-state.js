/**
 * MYAVANA Timeline - UI State Management Module
 * Handles dark mode, sidebar, theme persistence, and responsive behavior
 *
 * @package Myavana_Hair_Journey
 * @version 2.3.5
 */

// Initialize namespace if not exists
window.MyavanaTimeline = window.MyavanaTimeline || {};

// UI State Module
MyavanaTimeline.UI = (function() {
    'use strict';

    /**
     * Toggle dark mode theme
     */
    function toggleDarkMode() {
        const container = document.querySelector('.hair-journey-container');
        const sunIcon = document.querySelector('.sun-icon');
        const moonIcon = document.querySelector('.moon-icon');

        if (!container) {
            console.error('[Dark Mode] Container .hair-journey-container not found');
            return;
        }

        if (!sunIcon || !moonIcon) {
            console.error('[Dark Mode] Sun or moon icon not found');
            return;
        }

        const currentTheme = container.getAttribute('data-theme') || 'light';
        const newTheme = currentTheme === 'light' ? 'dark' : 'light';

        console.log(`[Dark Mode] Switching from ${currentTheme} to ${newTheme}`);

        container.setAttribute('data-theme', newTheme);

        if (newTheme === 'dark') {
            sunIcon.style.display = 'none';
            moonIcon.style.display = 'block';
        } else {
            sunIcon.style.display = 'block';
            moonIcon.style.display = 'none';
        }

        // Save preference to localStorage
        localStorage.setItem('theme', newTheme);
        console.log('[Dark Mode] Theme saved to localStorage:', newTheme);
    }

    /**
     * Load theme preference from localStorage
     */
    function loadTheme() {
        const savedTheme = localStorage.getItem('theme') || 'light';
        const container = document.querySelector('.hair-journey-container');
        const sunIcon = document.querySelector('.sun-icon');
        const moonIcon = document.querySelector('.moon-icon');

        if (!container) {
            console.error('[Load Theme] Container .hair-journey-container not found');
            return;
        }

        if (!sunIcon || !moonIcon) {
            console.error('[Load Theme] Sun or moon icon not found');
            return;
        }

        console.log('[Load Theme] Loading saved theme:', savedTheme);

        container.setAttribute('data-theme', savedTheme);

        if (savedTheme === 'dark') {
            sunIcon.style.display = 'none';
            moonIcon.style.display = 'block';
        } else {
            sunIcon.style.display = 'block';
            moonIcon.style.display = 'none';
        }
    }

    /**
     * Toggle sidebar collapse (desktop only)
     */
    function toggleSidebar() {
        // Disable collapsing on mobile (screens <= 1024px)
        if (window.innerWidth <= 1024) {
            return;
        }

        const sidebar = document.getElementById('sidebar');
        const toggle = document.getElementById('sidebarToggle');

        sidebar.classList.toggle('collapsed');

        if (sidebar.classList.contains('collapsed')) {
            toggle.innerHTML = '›';
            toggle.style.left = '10px';
            // Save collapsed state
            localStorage.setItem('sidebarCollapsed', 'true');
        } else {
            toggle.innerHTML = '‹';
            toggle.style.left = '400px';
            // Save expanded state
            localStorage.setItem('sidebarCollapsed', 'false');
        }
    }

    /**
     * Switch between sidebar tabs
     */
    function switchSidebarTab(tabName) {
        // Update tab buttons
        document.querySelectorAll('.sidebar-tab').forEach(tab => {
            tab.classList.remove('active');
        });
        document.querySelector(`.sidebar-tab[data-tab="${tabName}"]`).classList.add('active');

        // Update tab content
        document.querySelectorAll('.sidebar-tab-content').forEach(content => {
            content.classList.remove('active');
        });
        document.getElementById(tabName + 'Tab').classList.add('active');

        // Save active tab preference
        localStorage.setItem('activeSidebarTab', tabName);
    }

    /**
     * Load sidebar state from localStorage
     */
    function loadSidebarState() {
        const sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        const activeSidebarTab = localStorage.getItem('activeSidebarTab') || 'insights';

        const sidebar = document.getElementById('sidebar');
        const toggle = document.getElementById('sidebarToggle');

        // Only restore collapsed state on desktop (> 1024px)
        if (window.innerWidth > 1024) {
            if (sidebarCollapsed) {
                sidebar.classList.add('collapsed');
                toggle.innerHTML = '›';
                toggle.style.left = '10px';
            } else {
                toggle.style.left = '400px';
            }
        } else {
            // On mobile, always show sidebar expanded
            sidebar.classList.remove('collapsed');
        }

        // Restore active tab
        switchSidebarTab(activeSidebarTab);
    }

    /**
     * Handle window resize events
     */
    function handleResize() {
        const sidebar = document.getElementById('sidebar');
        const toggle = document.getElementById('sidebarToggle');

        if (window.innerWidth <= 1024) {
            // On mobile, remove desktop collapsed class
            sidebar.classList.remove('collapsed');
            // Restore mobile collapsed state
            const mobileCollapsed = localStorage.getItem('mobileSidebarCollapsed') === 'true';
            if (mobileCollapsed) {
                sidebar.classList.add('mobile-collapsed');
            } else {
                sidebar.classList.remove('mobile-collapsed');
            }
        } else {
            // On desktop, remove mobile collapsed class
            sidebar.classList.remove('mobile-collapsed');
            // Restore desktop collapsed state from localStorage
            const sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            if (sidebarCollapsed) {
                sidebar.classList.add('collapsed');
                toggle.innerHTML = '›';
                toggle.style.left = '10px';
            } else {
                sidebar.classList.remove('collapsed');
                toggle.innerHTML = '‹';
                toggle.style.left = '400px';
            }
        }
    }

    /**
     * Reset sidebar state (for debugging)
     */
    function resetSidebar() {
        localStorage.removeItem('sidebarCollapsed');
        localStorage.removeItem('mobileSidebarCollapsed');
        localStorage.removeItem('activeSidebarTab');
        location.reload();
    }

    /**
     * Toggle mobile sidebar (accordion style)
     */
    function toggleMobileSidebar() {
        console.log('toggleMobileSidebar in timeline-ui-state.js');
        // Only work on mobile/tablet screens
        if (window.innerWidth > 1024) {
            return;
        }

        const sidebar = document.getElementById('sidebar');
        const icon = document.getElementById('mobileSidebarIcon');
        console.log('sidebar.classList.toggle("mobile-collapsed")', sidebar.classList.toggle('mobile-collapsed'));

        sidebar.classList.toggle('mobile-collapsed');
        console.log('sidebar.classList.toggle("mobile-collapsed")', sidebar.classList.toggle('mobile-collapsed'));
        // Save mobile collapsed state
        const isCollapsed = sidebar.classList.contains('mobile-collapsed');
        localStorage.setItem('mobileSidebarCollapsed', isCollapsed);
    }

    /**
     * Load mobile sidebar state from localStorage
     */
    function loadMobileSidebarState() {
        if (window.innerWidth <= 1024) {
            const mobileCollapsed = localStorage.getItem('mobileSidebarCollapsed') === 'true';
            const sidebar = document.getElementById('sidebar');

            if (mobileCollapsed) {
                sidebar.classList.add('mobile-collapsed');
            }
        }
    }

    /**
     * Edit profile handler
     */
    function editProfile() {
        alert('Edit Profile: Opens modal to edit hair type, porosity, goals, and regimen details!');
    }

    /**
     * Initialize UI state module
     */
    function init() {
        loadTheme();
        loadSidebarState();
        loadMobileSidebarState();

        // Setup resize handler
        window.addEventListener('resize', handleResize);
    }

    // Public API
    return {
        init: init,
        toggleDarkMode: toggleDarkMode,
        loadTheme: loadTheme,
        toggleSidebar: toggleSidebar,
        switchSidebarTab: switchSidebarTab,
        loadSidebarState: loadSidebarState,
        handleResize: handleResize,
        resetSidebar: resetSidebar,
        toggleMobileSidebar: toggleMobileSidebar,
        loadMobileSidebarState: loadMobileSidebarState,
        editProfile: editProfile
    };
})();

// Expose global functions for backward compatibility
window.toggleDarkMode = MyavanaTimeline.UI.toggleDarkMode;
window.toggleSidebar = MyavanaTimeline.UI.toggleSidebar;
window.switchSidebarTab = MyavanaTimeline.UI.switchSidebarTab;
window.toggleMobileSidebar = MyavanaTimeline.UI.toggleMobileSidebar;
window.editProfile = MyavanaTimeline.UI.editProfile;
window.resetSidebar = MyavanaTimeline.UI.resetSidebar;
