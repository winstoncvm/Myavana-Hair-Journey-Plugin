        let splide;
let currentCalendarView = 'month';

// Dark Mode Toggle
function toggleDarkMode() {
    const container = document.querySelector('.container');
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
    const container = document.querySelector('.container');
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
    const sidebar = document.getElementById('sidebar');
    const toggle = document.getElementById('sidebarToggle');
    
    sidebar.classList.toggle('collapsed');
    
    if (sidebar.classList.contains('collapsed')) {
        toggle.textContent = '›';
        toggle.style.right = '12px'; // Ensure toggle button is visible outside collapsed sidebar
    } else {
        toggle.textContent = '‹';
        toggle.style.right = '-12px'; // Reset to original position
    }
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
    // Load theme
    loadTheme();

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

    console.log('Myavana Hair Journey Timeline loaded successfully!');
});
