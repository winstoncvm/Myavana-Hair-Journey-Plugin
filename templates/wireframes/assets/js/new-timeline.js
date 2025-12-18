let splide;
let currentCalendarView = 'month';

// Dark Mode Toggle
function toggleDarkMode() {
    const container = document.querySelector('.hair-journey-container');
    const sunIcon = document.querySelector('.sun-icon');
    const moonIcon = document.querySelector('.moon-icon');
    
    const currentTheme = container.getAttribute('data-theme');
    const newTheme = currentTheme === 'light' ? 'dark' : 'light';
    
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
}

// Load theme preference
function loadTheme() {
    const savedTheme = localStorage.getItem('theme') || 'light';
    const container = document.querySelector('.hair-journey-container');
    const sunIcon = document.querySelector('.sun-icon');
    const moonIcon = document.querySelector('.moon-icon');
    
    container.setAttribute('data-theme', savedTheme);
    
    if (savedTheme === 'dark') {
        sunIcon.style.display = 'none';
        moonIcon.style.display = 'block';
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

    const sidebar = document.getElementById('sidebar');
    const icon = document.getElementById('mobileSidebarIcon');

    sidebar.classList.toggle('mobile-collapsed');

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

// ===== OFFCANVAS FUNCTIONS =====
let currentOffcanvas = null;
let selectedRating = 0;
let uploadedFiles = [];

// Open offcanvas
function openOffcanvas(type) {
    const offcanvasMap = {
        'entry': 'entryOffcanvas',
        'goal': 'goalOffcanvas',
        'routine': 'routineOffcanvas'
    };

    currentOffcanvas = document.getElementById(offcanvasMap[type]);
    const overlay = document.getElementById('offcanvasOverlay');

    // Prevent body scroll
    document.body.style.overflow = 'hidden';

    // Show overlay
    overlay.classList.add('active');

    // Show offcanvas after a short delay for smooth animation
    setTimeout(() => {
        currentOffcanvas.classList.add('active');
    }, 10);

    // Set default dates
    if (type === 'entry') {
        const entryDate = document.getElementById('entryDate');
        if (entryDate) entryDate.valueAsDate = new Date();
    } else if (type === 'goal') {
        const goalStartDate = document.getElementById('goalStartDate');
        if (goalStartDate) goalStartDate.valueAsDate = new Date();
    }
}

// Close offcanvas
function closeOffcanvas() {
    if (!currentOffcanvas) return;

    const overlay = document.getElementById('offcanvasOverlay');

    currentOffcanvas.classList.remove('active');
    overlay.classList.remove('active');

    // Re-enable body scroll
    setTimeout(() => {
        document.body.style.overflow = '';
        currentOffcanvas = null;
    }, 300);

    // Reset forms
    resetOffcanvasForms();
}

// Reset forms
function resetOffcanvasForms() {
    document.querySelectorAll('.offcanvas form').forEach(form => form.reset());
    selectedRating = 0;
    uploadedFiles = [];
    const previewGrid = document.getElementById('entryPreviewGrid');
    if (previewGrid) previewGrid.innerHTML = '';
    document.querySelectorAll('.chip').forEach(chip => chip.classList.remove('selected'));
    document.querySelectorAll('.rating-star').forEach(star => star.classList.remove('active'));
}

// Form submissions
function submitEntry() {
    console.log('Entry submitted');
    alert('Entry saved successfully!');
    closeOffcanvas();
}

function submitGoal() {
    console.log('Goal submitted');
    alert('Goal saved successfully!');
    closeOffcanvas();
}

function submitRoutine() {
    console.log('Routine submitted');
    alert('Routine saved successfully!');
    closeOffcanvas();
}

// Rating selector
function initRatingSelector() {
    document.querySelectorAll('.rating-star').forEach(star => {
        star.addEventListener('click', function() {
            selectedRating = parseInt(this.getAttribute('data-rating'));
            updateRatingDisplay(this.parentElement);
        });
    });
}

function updateRatingDisplay(container) {
    const stars = container.querySelectorAll('.rating-star');
    stars.forEach(star => {
        const rating = parseInt(star.getAttribute('data-rating'));
        star.classList.toggle('active', rating <= selectedRating);
    });
}

// Chip selector (mood and time)
function initChipSelectors() {
    document.querySelectorAll('.chip').forEach(chip => {
        chip.addEventListener('click', function(e) {
            e.preventDefault();
            // For single selection groups
            const parent = this.parentElement;
            parent.querySelectorAll('.chip').forEach(c => c.classList.remove('selected'));
            this.classList.add('selected');
        });
    });
}

// File upload for entry
function initFileUpload() {
    const fileInput = document.getElementById('entryFileInput');
    if (!fileInput) return;

    fileInput.addEventListener('change', function(e) {
        const files = Array.from(e.target.files);
        const previewGrid = document.getElementById('entryPreviewGrid');

        files.forEach(file => {
            if (file.type.startsWith('image/') && file.size <= 10 * 1024 * 1024) {
                uploadedFiles.push(file);

                const reader = new FileReader();
                reader.onload = function(e) {
                    const previewItem = document.createElement('div');
                    previewItem.className = 'photo-preview-item';
                    previewItem.innerHTML = `
                        <img src="${e.target.result}" alt="Preview">
                        <button class="photo-preview-remove" onclick="removePhoto(this, '${file.name}')">&times;</button>
                    `;
                    previewGrid.appendChild(previewItem);
                };
                reader.readAsDataURL(file);
            }
        });
    });
}

function removePhoto(btn, fileName) {
    btn.parentElement.remove();
    uploadedFiles = uploadedFiles.filter(f => f.name !== fileName);
}

// Prevent offcanvas click from closing
function initOffcanvasClickHandlers() {
    document.querySelectorAll('.offcanvas').forEach(offcanvas => {
        offcanvas.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    });
}

// Initialize Splide Slider
function initSlider() {
    if (splide) {
        splide.destroy();
    }
    
    const sliderElement = document.getElementById('hairJourneySlider');
    if (sliderElement) {
        splide = new Splide('#hairJourneySlider', {
            type: 'slide',
            perPage: 1,
            perMove: 1,
            gap: '2rem',
            padding: '5%',
            focus: 'center',
            trimSpace: false,
            arrows: true,
            pagination: false,
            breakpoints: {
                768: {
                    padding: '10%',
                }
            }
        }).mount();

        splide.on('moved', function(newIndex) {
            const progress = document.getElementById('progress');
            const total = splide.length;
            const percentage = ((newIndex + 1) / total) * 100;
            if (progress) {
                progress.style.width = percentage + '%';
            }

            document.querySelectorAll('.date-marker').forEach(marker => {
                marker.classList.remove('active');
            });
            const activeMarker = document.querySelector(`.date-marker[data-index="${newIndex}"]`);
            if (activeMarker) {
                activeMarker.classList.add('active');
            }
        });
    }
}

// Switch Main Views
function switchView(viewName) {
    // Update header view buttons
    document.querySelectorAll('.view-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelector(`.view-btn[data-view="${viewName}"]`)?.classList.add('active');

    // Update timeline control tabs
    document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
    document.querySelector(`.tab[onclick="switchView('${viewName}')"]`)?.classList.add('active');

    // Switch view content
    document.querySelectorAll('.view-content').forEach(view => view.classList.remove('active'));
    const targetView = document.getElementById(viewName + 'View');
    if (targetView) {
        targetView.classList.add('active');
    }

    if (viewName === 'slider') {
        setTimeout(() => {
            initSlider();
        }, 100);
    }
}

// Switch Calendar Views (Day/Week/Month)
function setCalendarView(view) {
    currentCalendarView = view;
    
    document.querySelectorAll('.view-toggle').forEach(toggle => {
        toggle.classList.remove('active');
    });
    document.querySelector(`.view-toggle[onclick="setCalendarView('${view}')"]`)?.classList.add('active');

    document.getElementById('monthView').style.display = 'none';
    document.getElementById('weekView').style.display = 'none';
    document.getElementById('dayView').style.display = 'none';

    if (view === 'month') {
        document.getElementById('monthView').style.display = 'block';
        document.getElementById('dateRange').textContent = '1 Oct - 14 Oct';
    } else if (view === 'week') {
        document.getElementById('weekView').style.display = 'block';
        document.getElementById('dateRange').textContent = 'Oct 7-13, 2025';
    } else if (view === 'day') {
        document.getElementById('dayView').style.display = 'block';
        document.getElementById('dateRange').textContent = 'Mon, Oct 14, 2025';
    }
}

// Action Buttons
function addGoal() {
    alert('Add Goal: Opens form to create a new hair goal with category, target date, and progress tracking!');
}

function addRoutine() {
    alert('Add Routine: Opens form to create a new haircare routine with frequency, time of day, and products!');
}

function addEntry() {
    alert('Add Entry: Opens form to log a new journey entry with photos, notes, mood, products, and health rating!');
}

// Modal Functions
function closeModal() {
    document.getElementById('eventModal').classList.remove('active');
}

// Carousel Functions
function scrollCarousel(direction) {
    const track = document.getElementById('carouselTrack');
    const scrollAmount = 160;
    track.scrollBy({
        left: scrollAmount * direction,
        behavior: 'smooth'
    });
}

// Event Listeners
document.addEventListener('DOMContentLoaded', function() {
    // Load theme and sidebar state
    loadTheme();
    loadSidebarState();
    loadMobileSidebarState();

    // Theme toggle
    document.getElementById('themeToggle').addEventListener('click', toggleDarkMode);

    // View buttons (header)
    document.querySelectorAll('.view-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const viewName = this.getAttribute('data-view');
            switchView(viewName);
        });
    });

    // Timeline control tabs
    document.querySelectorAll('.tab').forEach(tab => {
        tab.addEventListener('click', function() {
            const viewName = this.getAttribute('onclick').match(/'([^']+)'/)[1];
            switchView(viewName);
        });
    });

    // Timeline cards
    document.querySelectorAll('.timeline-card').forEach(card => {
        card.addEventListener('click', function() {
            document.getElementById('eventModal').classList.add('active');
        });
    });

    // Journey entries
    document.querySelectorAll('.journey-entry').forEach(entry => {
        entry.addEventListener('click', function() {
            document.getElementById('eventModal').classList.add('active');
        });
    });

    // Goal bars
    document.querySelectorAll('.goal-bar-span').forEach(bar => {
        bar.addEventListener('click', function() {
            document.getElementById('eventModal').classList.add('active');
        });
    });

    // Routine bars
    document.querySelectorAll('.routine-bar').forEach(bar => {
        bar.addEventListener('click', function() {
            document.getElementById('eventModal').classList.add('active');
        });
    });

    // List items
    document.querySelectorAll('.list-item-action').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            document.getElementById('eventModal').classList.add('active');
        });
    });

    document.querySelectorAll('.list-item').forEach(item => {
        item.addEventListener('click', function() {
            document.getElementById('eventModal').classList.add('active');
        });
    });

    // Carousel items
    document.querySelectorAll('.carousel-item').forEach(item => {
        item.addEventListener('click', function() {
            document.querySelectorAll('.carousel-item').forEach(i => i.classList.remove('active'));
            this.classList.add('active');
        });
    });

    // Date markers
    document.querySelectorAll('.date-marker').forEach(marker => {
        marker.addEventListener('click', function() {
            const index = parseInt(this.dataset.index);
            if (splide) {
                splide.go(index);
            }
        });
    });

    // Modal close
    document.getElementById('eventModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });

    // Goal items
    document.querySelectorAll('.goal-item').forEach(item => {
        item.addEventListener('click', function() {
            document.querySelectorAll('.goal-item').forEach(i => i.classList.remove('active'));
            this.classList.add('active');
        });
    });

    // Handle window resize
    window.addEventListener('resize', handleResize);

    // Initialize offcanvas handlers
    initRatingSelector();
    initChipSelectors();
    initFileUpload();
    initOffcanvasClickHandlers();

    // Close offcanvas on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && currentOffcanvas) {
            closeOffcanvas();
        }
    });

    console.log('Myavana Hair Journey Timeline loaded successfully!');
});