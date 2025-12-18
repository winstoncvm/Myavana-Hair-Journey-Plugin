let splide;
let currentCalendarView = 'month';

// Dark Mode Toggle
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

// Load theme preference
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

// Toggle Sidebar
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

// Switch Sidebar Tabs
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

// Load sidebar state
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

// Handle window resize
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

// Clear sidebar state (for debugging)
function resetSidebar() {
    localStorage.removeItem('sidebarCollapsed');
    localStorage.removeItem('mobileSidebarCollapsed');
    localStorage.removeItem('activeSidebarTab');
    location.reload();
}

// Toggle Mobile Sidebar (Accordion Style)
function toggleMobileSidebar() {
    // Only work on mobile/tablet screens
    if (window.innerWidth > 1024) {
        return;
    }
    console.log('toggleMobileSidebar');
    const sidebar = document.getElementById('sidebar');
    const icon = document.getElementById('mobileSidebarIcon');

    sidebar.classList.toggle('mobile-collapsed');
    console.log('sidebar.classList.toggle("mobile-collapsed")', sidebar.classList.toggle('mobile-collapsed'));
    // Save mobile collapsed state
    const isCollapsed = sidebar.classList.contains('mobile-collapsed');
    localStorage.setItem('mobileSidebarCollapsed', isCollapsed);
}

// Load mobile sidebar state
function loadMobileSidebarState() {
    if (window.innerWidth <= 1024) {
        const mobileCollapsed = localStorage.getItem('mobileSidebarCollapsed') === 'true';
        const sidebar = document.getElementById('sidebar');

        if (mobileCollapsed) {
            sidebar.classList.add('mobile-collapsed');
        }
    }
}

// Edit Profile Function
function editProfile() {
    alert('Edit Profile: Opens modal to edit hair type, porosity, goals, and regimen details!');
}

// Initialize header and sidebar functionality
document.addEventListener('DOMContentLoaded', function() {
    // Load theme and sidebar state
    loadTheme();
    loadSidebarState();
    loadMobileSidebarState();

    // Theme toggle - with null check
    const themeToggleBtn = document.getElementById('themeToggle');
    if (themeToggleBtn) {
        themeToggleBtn.addEventListener('click', toggleDarkMode);
        console.log('[Dark Mode] Toggle button found and event listener attached');
    } else {
        console.warn('[Dark Mode] Toggle button (#themeToggle) not found in DOM');
    }

    // Handle window resize
    window.addEventListener('resize', handleResize);

    console.log('Myavana Header & Sidebar scripts loaded successfully!');
});

console.log('Header & Sidebar JavaScript loaded');