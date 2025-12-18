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
    console.log('toggleMobileSidebar in new-timeline.js');
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

// Master close function - handles all offcanvas types
function closeOffcanvas() {
    console.log('Closing offcanvas...');
    
    // Close view offcanvas if open
    if (currentViewOffcanvas) {
        closeTimelineViewOffcanvas();
        return;
    }
    
    // Close create/edit offcanvas if open
    if (currentOffcanvas) {
        closeCreateOffcanvas();
        return;
    }
}

// Dedicated function for view offcanvas (namespaced to avoid global collisions)
function closeTimelineViewOffcanvas() {
    if (!currentViewOffcanvas) return;
    
    const overlay = document.getElementById('viewOffcanvasOverlay');
    if (overlay) overlay.classList.remove('active');
    
    currentViewOffcanvas.classList.remove('active');
    document.body.style.overflow = '';
    
    setTimeout(() => {
        currentViewOffcanvas = null;
        currentViewData = null;
    }, 400);
}

// Expose namespaced closer for inline onclick handlers
try { window.closeTimelineViewOffcanvas = closeTimelineViewOffcanvas; } catch (e) {}

// Dedicated function for create/edit offcanvas  
function closeCreateOffcanvas() {
    if (!currentOffcanvas) return;

    const overlay1 = document.getElementById('createOffcanvasOverlay');
    if (overlay1) overlay1.classList.remove('active');
    
    const overlay = document.getElementById('offcanvasOverlay') || document.getElementById('createOffcanvasOverlay');
    if (overlay) overlay.classList.remove('active');
    
    currentOffcanvas.classList.remove('active');
    document.body.style.overflow = '';
    
    setTimeout(() => {
        currentOffcanvas = null;
    }, 300);
    
    resetOffcanvasForms();
}



// Master close function that closes any open offcanvas
function closeAllOffcanvases() {
    closeCreateOffcanvas();
    closeViewOffcanvas();
}

// ===== LIST VIEW FUNCTIONS =====
// List view functions moved to timeline-list-view.js module
// Using MyavanaTimeline.ListView for list management

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
// Prevent offcanvas click from closing
function initOffcanvasClickHandlers() {
    document.querySelectorAll('.offcanvas').forEach(offcanvas => {
        offcanvas.addEventListener('click', function(e) {
            // No e.stopPropagation() needed here.
        });
    });

    // Add a click listener to the overlay to close the offcanvas
    const overlay = document.getElementById('offcanvasOverlay');
    if (overlay) {
        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) {
                closeOffcanvas();
            }
        });
    }

    const viewOverlay = document.getElementById('viewOffcanvasOverlay');
    if (viewOverlay) {
        viewOverlay.addEventListener('click', function(e) {
            if (e.target === viewOverlay) {
                closeViewOffcanvas();
            }
        });
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

    // Initialize view-specific functionality
    if (viewName === 'list') {
        MyavanaTimeline.ListView.init();
    }
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
// function closeModal() {
//     document.getElementById('eventModal').classList.remove('active');
// }

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

    // Set calendar as default view on page load
    switchView('calendar');
    setCalendarView('month'); // Set default calendar view to month

    // Theme toggle - with null check
    const themeToggleBtn = document.getElementById('themeToggle');
    if (themeToggleBtn) {
        themeToggleBtn.addEventListener('click', toggleDarkMode);
        console.log('[Dark Mode] Toggle button found and event listener attached');
    } else {
        console.warn('[Dark Mode] Toggle button (#themeToggle) not found in DOM');
    }

    // View buttons (header)
    document.querySelectorAll('.view-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const viewName = this.getAttribute('data-view');
            switchView(viewName);
        });
    });
    // Overlay click handlers
    document.getElementById('offcanvasOverlay')?.addEventListener('click', closeOffcanvas);
    document.getElementById('viewOffcanvasOverlay')?.addEventListener('click', closeTimelineViewOffcanvas);
    
    // Escape key handler for all offcanvases
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeOffcanvas(); // This will close any open offcanvas
        }
    });

    // Timeline control tabs
    document.querySelectorAll('.tab').forEach(tab => {
        tab.addEventListener('click', function() {
            const viewName = this.getAttribute('onclick').match(/'([^']+)'/)[1];
            switchView(viewName);
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
    initProductSelector();
    initOffcanvasClickHandlers();


    // Initialize list view functionality
    MyavanaTimeline.ListView.init();

    console.log('Myavana Hair Journey Timeline loaded successfully!');
});

// ===== VIEW OFFCANVAS FUNCTIONALITY =====

let currentViewOffcanvas = null;
let currentViewData = null;

// Open view offcanvas for viewing details
function openViewOffcanvas(type, id) {
    console.log('Opening view offcanvas:', type, id);

    // Map type to offcanvas ID
    const offcanvasMap = {
        'entry': 'entryViewOffcanvas',
        'goal': 'goalViewOffcanvas',
        'routine': 'routineViewOffcanvas'
    };

    currentViewOffcanvas = document.getElementById(offcanvasMap[type]);
    const overlay = document.getElementById('viewOffcanvasOverlay');

    if (!currentViewOffcanvas || !overlay) {
        console.error('View offcanvas elements not found');
        return;
    }

    // Show offcanvas and overlay
    overlay.classList.add('active');
    currentViewOffcanvas.classList.add('active');

    // Load data based on type
    switch(type) {
        case 'entry':
            loadEntryView(id);
            break;
        case 'goal':
            loadGoalView(id);
            break;
        case 'routine':
            loadRoutineView(id);
            break;
    }

    // Prevent body scroll
    document.body.style.overflow = 'hidden';
}

// Close view offcanvas
// function closeViewOffcanvas() {
//     console.log('trying Closing view offcanvas');
//     if (!currentViewOffcanvas) return;

//     console.log('Closing view offcanvas', currentViewOffcanvas);

//     const overlay = document.getElementById('viewOffcanvasOverlay');

//     if (overlay) {
//         overlay.classList.remove('active');
//     }

//     currentViewOffcanvas.classList.remove('active');

//     // Re-enable body scroll
//     document.body.style.overflow = '';

//     // Clear data after animation
//     setTimeout(() => {
//         currentViewOffcanvas = null;
//         currentViewData = null;
//     }, 400);
// }

// Load Entry View
function loadEntryView(entryId) {
    const body = document.getElementById('entryViewBody');
    if (!body) return;

    const loadingEl = body.querySelector('.view-loading-hjn');
    const contentEl = body.querySelector('.view-content-hjn');

    // Show loading
    if (loadingEl) loadingEl.style.display = 'flex';
    if (contentEl) contentEl.style.display = 'none';

    // Resolve settings from possible global objects (backwards compatible)
    const settings = window.myavanaTimelineSettings || window.myavanaTimeline || window.myavanaTimelineInstance || {};

    // Fetch entry data via AJAX
    fetch(settings.ajaxUrl || settings.ajaxurl || '/wp-admin/admin-ajax.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'myavana_get_entry_details',
            security: settings.getEntryDetailsNonce || settings.getEntriesNonce || settings.nonce || '',
            entry_id: entryId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.data) {
            populateEntryView(data.data);
        } else {
            console.error('Failed to load entry:', data);
            showViewError('Failed to load entry details');
        }
    })
    .catch(error => {
        console.error('Error loading entry:', error);
        showViewError('Error loading entry details');
    });
}

// Populate Entry View with data
function populateEntryView(entry) {
    const body = document.getElementById('entryViewBody');
    if (!body) return;

    const loadingEl = body.querySelector('.view-loading-hjn');
    const contentEl = body.querySelector('.view-content-hjn');

    // Hide loading, show content
    if (loadingEl) loadingEl.style.display = 'none';
    if (contentEl) contentEl.style.display = 'flex';

    // Populate title
    const titleEl = document.getElementById('entryTitle');
    if (titleEl) titleEl.textContent = entry.entry_title || 'Untitled Entry';

    // Populate date
    const dateEl = document.getElementById('entryDate');
    if (dateEl) dateEl.textContent = entry.entry_date ? new Date(entry.entry_date).toLocaleDateString() : '';

    // Populate gallery (defensive: images may be array of objects with url/thumbnail/alt)
    console.log('Entry data:', entry);
    if (entry.images ) {
        console.log('Processing images:', entry.images);
        const galleryEl = document.getElementById('entryGallery');
        const primaryContainer = document.getElementById('entryPrimaryImage');
        // Normalize images: server returns array of objects {url, thumbnail, alt}
        const rawImages = Array.isArray(entry.images)
            ? entry.images
            : (typeof entry.images === 'string' && entry.images.trim() ? [{ url: entry.images.trim() }] : []);

        const images = rawImages.map(img => {
            console.log('Processing image:', img);
            if (!img) return null;
            if (typeof img === 'string') return img;
            if (typeof img === 'object') {
                const imageUrl = img.url || img.thumbnail || img.src || null;
                console.log('Extracted URL:', imageUrl);
                return imageUrl;
            }
            return null;
        }).filter(Boolean);
        console.log('Normalized image URLs:', images);

        if (galleryEl) {
            if (images.length === 0) {
                galleryEl.style.display = 'none';
            } else if (images.length === 1) {
                galleryEl.innerHTML = `<img src="${images[0]}" alt="${entry.title || ''}" />`;
                galleryEl.style.display = 'block';
            } else {
                const gridHTML = images.map(imgUrl =>
                    `<div class="view-gallery-item-hjn"><img src="${imgUrl}" alt="${entry.title || ''}" /></div>`
                ).join('');
                galleryEl.innerHTML = `<div class="view-gallery-grid-hjn">${gridHTML}</div>`;
                galleryEl.style.display = 'block';
            }
        } 

        
    }
    if(entry.image){
        // Populate primary image (prominent) and attach click-to-view
        (function handlePrimaryImage() {
            const primaryContainer = document.getElementById('entryPrimaryImage');
            console.log('Primary image container:', primaryContainer);
            if (!primaryContainer) return;

            // Determine primary image: prefer first item in images array, then entry.image, then entry.image_url
            let primary = null;
            if (typeof entry.image === 'string' && entry.image.trim()) primary = entry.image.trim();

            if (!primary) {
                primaryContainer.style.display = 'none';
                primaryContainer.innerHTML = '';
                return;
            }

            // Render image element
            primaryContainer.innerHTML = `<button class="primary-image-button-hjn" aria-label="View image"><img src="${primary}" alt="${entry.title || ''}" class="primary-image-hjn" /></button>`;
            primaryContainer.style.display = 'block';

            // Ensure overlay exists (create once)
            let overlay = document.getElementById('entryImageOverlay');
            if (!overlay) {
                overlay = document.createElement('div');
                overlay.id = 'entryImageOverlay';
                overlay.className = 'entry-image-overlay-hjn';
                overlay.style.display = 'none';
                overlay.innerHTML = `
                    <div class="entry-image-overlay-inner-hjn">
                        <button class="entry-image-overlay-close-hjn" id="entryImageOverlayClose" aria-label="Close image">&times;</button>
                        <img id="entryImageOverlayImg" src="" alt="" />
                    </div>`;
                document.body.appendChild(overlay);

                // Close handler
                overlay.addEventListener('click', function (e) {
                    if (e.target === overlay || e.target.id === 'entryImageOverlayClose') {
                        overlay.style.display = 'none';
                    }
                });
            }

            const overlayImg = document.getElementById('entryImageOverlayImg');
            const overlayClose = document.getElementById('entryImageOverlayClose');

            // Attach click to open overlay with full-size image
            const btn = primaryContainer.querySelector('.primary-image-button-hjn');
            if (btn) {
                btn.onclick = function (ev) {
                    ev.preventDefault();
                    if (overlayImg) overlayImg.src = primary;
                    if (overlay) overlay.style.display = 'flex';
                };
            }

            // Close button accessibility
            if (overlayClose) overlayClose.onclick = function (e) { e.preventDefault(); overlay.style.display = 'none'; };
        })();
    }

    // Populate rating
    if (entry.rating) {
        const ratingSection = document.getElementById('entryRatingSection');
        const ratingStars = document.getElementById('entryRatingStars');
        const ratingValue = document.getElementById('entryRatingValue');

        if (ratingSection) ratingSection.style.display = 'block';

        if (ratingStars) {
            const starsHTML = Array.from({length: 10}, (_, i) => {
                const filled = i < entry.rating;
                return `<svg class="rating-star-hjn ${filled ? '' : 'empty'}" viewBox="0 0 24 24"><path fill="currentColor" d="M12,17.27L18.18,21L16.54,13.97L22,9.24L14.81,8.62L12,2L9.19,8.62L2,9.24L7.45,13.97L5.82,21L12,17.27Z"/></svg>`;
            }).join('');
            ratingStars.innerHTML = starsHTML;
        }

        if (ratingValue) ratingValue.textContent = `${entry.rating}/10`;
    }

    // Populate content
    const contentTextEl = document.getElementById('entryContent');
    if (contentTextEl) {
        contentTextEl.textContent = entry.content || 'No description provided.';
    }

    // Populate mood
    if (entry.mood) {
        const moodSection = document.getElementById('entryMoodSection');
        const moodEl = document.getElementById('entryMood');

        if (moodSection) moodSection.style.display = 'block';
        if (moodEl) moodEl.textContent = entry.mood;
    }

    // Populate products (server may return array, comma-separated string, or empty)
    (function handleProducts() {
        const productsSection = document.getElementById('entryProductsSection');
        const productsEl = document.getElementById('entryProducts');

        let products = [];
        if (Array.isArray(entry.products)) {
            products = entry.products.slice();
        } else if (typeof entry.products === 'string' && entry.products.trim()) {
            // split comma-separated list and trim
            products = entry.products.split(',').map(p => p.trim()).filter(Boolean);
        } else if (entry.products && typeof entry.products === 'object') {
            // Sometimes products may be an object; attempt to extract values
            try {
                products = Object.values(entry.products).map(String).map(p => p.trim()).filter(Boolean);
            } catch (e) {
                products = [];
            }
        }

        if (!products || products.length === 0) {
            if (productsSection) productsSection.style.display = 'none';
            if (productsEl) productsEl.innerHTML = '';
            return;
        }

        if (productsSection) productsSection.style.display = 'block';
        if (productsEl) {
            const productsHTML = products.map(product => `<span class="view-tag-hjn">${product}</span>`).join('');
            productsEl.innerHTML = productsHTML;
        }
    })();

    // Populate AI analysis
    if (entry.ai_analysis) {
        const aiSection = document.getElementById('entryAISection');
        const aiEl = document.getElementById('entryAI');

        if (aiSection) aiSection.style.display = 'block';
        if (aiEl) aiEl.textContent = entry.ai_analysis;
    }

    // Store data for edit functionality
    currentViewData = { type: 'entry', id: entry.id, data: entry };
}

// Load Goal View
function loadGoalView(goalIndex) {
    const body = document.getElementById('goalViewBody');
    if (!body) return;

    // For goals, we can get data from the list item
    const listItem = document.querySelector(`[data-goal-index="${goalIndex}"]`);
    if (!listItem) {
        showViewError('Goal not found');
        return;
    }

    const loadingEl = body.querySelector('.view-loading-hjn');
    const contentEl = body.querySelector('.view-content-hjn');

    // Simulate loading
    if (loadingEl) loadingEl.style.display = 'flex';
    if (contentEl) contentEl.style.display = 'none';

    // Extract data from list item (this should ideally come from server)
    setTimeout(() => {
        const goalData = extractGoalData(listItem);
        populateGoalView(goalData);
    }, 500);
}

// Extract goal data from list item
function extractGoalData(listItem) {
    const title = listItem.querySelector('.list-item-title-hjn')?.textContent || 'Untitled Goal';
    const dateRange = listItem.querySelector('.list-item-date-hjn')?.textContent || '';
    const progressText = listItem.querySelector('.list-item-badge-hjn')?.textContent || '0%';
    const progress = parseInt(progressText) || 0;
    const description = listItem.querySelector('.list-item-description-hjn')?.textContent || '';

    return {
        title,
        dateRange,
        progress,
        description,
        milestones: [] // Would come from server in real implementation
    };
}

// Populate Goal View
function populateGoalView(goal) {
    const body = document.getElementById('goalViewBody');
    if (!body) return;

    const loadingEl = body.querySelector('.view-loading-hjn');
    const contentEl = body.querySelector('.view-content-hjn');

    // Hide loading, show content
    if (loadingEl) loadingEl.style.display = 'none';
    if (contentEl) contentEl.style.display = 'flex';

    // Populate title
    const titleEl = document.getElementById('goalTitle');
    if (titleEl) titleEl.textContent = goal.title;

    // Populate date range
    const dateEl = document.getElementById('goalDateRange');
    if (dateEl) dateEl.textContent = goal.dateRange;

    // Populate progress circle
    const progressPercent = document.getElementById('goalProgressPercent');
    const progressRing = document.getElementById('goalProgressRing');

    if (progressPercent) progressPercent.textContent = `${goal.progress}%`;

    if (progressRing) {
        const circumference = 2 * Math.PI * 60; // radius is 60
        const offset = circumference - (goal.progress / 100) * circumference;
        progressRing.style.strokeDashoffset = offset;
    }

    // Populate description
    const descEl = document.getElementById('goalDescription');
    if (descEl) descEl.textContent = goal.description || 'No description provided.';

    // Populate progress history
    if (goal.progress_history && goal.progress_history.length > 0) {
        const historySection = document.getElementById('goalProgressHistorySection');
        const historyEl = document.getElementById('goalProgressHistory');
        if (historySection && historyEl) {
            historySection.style.display = 'block';
            populateGoalProgressHistory(goal.progress_history);
        }
    }

    // Populate progress notes
    if (goal.progress_text && goal.progress_text.length > 0) {
        const notesSection = document.getElementById('goalNotesSection');
        const notesEl = document.getElementById('goalProgressNotes');
        if (notesSection && notesEl) {
            notesSection.style.display = 'block';
            populateGoalProgressNotes(goal.progress_text);
        }
    }

    // Store data for edit functionality
    currentViewData = { type: 'goal', data: goal };
}

// Populate goal progress history timeline
function populateGoalProgressHistory(history) {
    const historyEl = document.getElementById('goalProgressHistory');
    if (!historyEl || !history.length) return;

    // Sort history by date (most recent first)
    const sortedHistory = [...history].sort((a, b) => new Date(b.date) - new Date(a.date));

    const historyHTML = sortedHistory.map(entry => {
        const date = new Date(entry.date).toLocaleDateString();
        const change = entry.change >= 0 ? `+${entry.change}%` : `${entry.change}%`;
        const changeClass = entry.change >= 0 ? 'positive' : 'negative';

        return `
            <div class="progress-history-item-hjn">
                <div class="progress-history-date-hjn">${date}</div>
                <div class="progress-history-progress-hjn">${entry.progress}%</div>
                <div class="progress-history-change-hjn ${changeClass}">${change}</div>
            </div>
        `;
    }).join('');

    historyEl.innerHTML = historyHTML;
}

// Populate progress notes in goal edit form
function populateGoalEditProgressNotes(notes) {
    const notesList = document.getElementById('goalProgressNotesList');
    if (!notesList || !notes.length) return;

    // Sort notes by date (most recent first)
    const sortedNotes = [...notes].sort((a, b) => new Date(b.date) - new Date(a.date));

    const notesHTML = sortedNotes.map(note => {
        const date = new Date(note.date).toLocaleDateString();
        return `
            <div class="goal-edit-note-item-hjn">
                <div class="goal-edit-note-date-hjn">${date}</div>
                <div class="goal-edit-note-text-hjn">${note.text}</div>
            </div>
        `;
    }).join('');

    notesList.innerHTML = notesHTML;
}

// Initialize character counter for progress note textarea
function initProgressNoteCounter(textarea) {
    const counter = document.getElementById('progress_note_count');
    if (!counter) return;

    function updateCounter() {
        counter.textContent = textarea.value.length;
    }

    textarea.addEventListener('input', updateCounter);
    // Initial count
    updateCounter();
}

// Populate goal progress notes
function populateGoalProgressNotes(notes) {
    const notesEl = document.getElementById('goalProgressNotes');
    if (!notesEl) return;

    if (!notes || notes.length === 0) {
        notesEl.innerHTML = `
            <div class="goal-notes-empty-hjn">
                <svg viewBox="0 0 24 24" width="48" height="48" fill="currentColor" opacity="0.3">
                    <path d="M19,4H18V2H16V4H8V2H6V4H5C3.89,4 3,4.9 3,6V20A2,2 0 0,0 5,22H19A2,2 0 0,0 21,20V6C21,4.9 20.1,4 19,4M19,20H5V9H19V20Z"/>
                </svg>
                <p>No progress notes yet. Add some when updating your goal progress!</p>
            </div>
        `;
        return;
    }

    // Sort notes by date (most recent first)
    const sortedNotes = [...notes].sort((a, b) => new Date(b.date) - new Date(a.date));

    const notesHTML = sortedNotes.map(note => {
        if (!note || !note.text) return '';

        const date = new Date(note.date).toLocaleDateString();
        return `
            <div class="goal-note-item-hjn">
                <div class="goal-note-date-hjn">${date}</div>
                <div class="goal-note-text-hjn">${note.text}</div>
            </div>
        `;
    }).filter(note => note).join('');

    notesEl.innerHTML = notesHTML;
}

// Load Routine View
function loadRoutineView(routineId) {
    console.log('Loading routine view:', routineId);
    const body = document.getElementById('routineViewBody');
    if (!body) {
        console.error('Routine view body not found');
        return;
    }

    const loadingEl = body.querySelector('.view-loading-hjn');
    const contentEl = body.querySelector('.view-content-hjn');

    // Show loading
    if (loadingEl) loadingEl.style.display = 'flex';
    if (contentEl) contentEl.style.display = 'none';

    // Try to get routine data from calendar data first
    const calendarDataEl = document.getElementById('calendarDataHjn');
    if (calendarDataEl) {
        try {
            const calendarData = JSON.parse(calendarDataEl.textContent);
            const routine = calendarData.routines?.find(r => r.id == routineId);

            if (routine) {
                console.log('Found routine in calendar data:', routine);
                setTimeout(() => {
                    populateRoutineView(routine);
                }, 300);
                return;
            }
        } catch (error) {
            console.error('Error parsing calendar data:', error);
        }
    }

    // Fallback: try to find by data-routine-index attribute (for sidebar)
    const listItem = document.querySelector(`[data-routine-index="${routineId}"]`);
    if (listItem) {
        console.log('Found routine in sidebar list');
        setTimeout(() => {
            const routineData = extractRoutineData(listItem);
            populateRoutineView(routineData);
        }, 300);
        return;
    }

    // If not found anywhere, show error
    console.warn('Routine not found:', routineId);
    showViewError('Routine not found');
}

// Extract routine data from list item
function extractRoutineData(listItem) {
    const title = listItem.querySelector('.list-item-title-hjn')?.textContent || 'Untitled Routine';
    const schedule = listItem.querySelector('.list-item-badge-hjn')?.textContent || '';
    const description = listItem.querySelector('.list-item-description-hjn')?.textContent || '';

    return {
        title,
        schedule,
        description,
        steps: [],
        products: []
    };
}

// Populate Routine View
function populateRoutineView(routine) {
    console.log('Populating routine view with data:', routine);
    const body = document.getElementById('routineViewBody');
    if (!body) {
        console.error('Routine view body not found');
        return;
    }

    const loadingEl = body.querySelector('.view-loading-hjn');
    const contentEl = body.querySelector('.view-content-hjn');

    // Hide loading, show content
    if (loadingEl) loadingEl.style.display = 'none';
    if (contentEl) contentEl.style.display = 'flex';

    // Populate title
    const titleEl = document.getElementById('routineTitle');
    if (titleEl) titleEl.textContent = routine.title || 'Untitled Routine';

    // Populate schedule/frequency and time
    const scheduleEl = document.getElementById('routineSchedule');
    if (scheduleEl) {
        let scheduleText = '';
        if (routine.frequency) {
            scheduleText = routine.frequency.charAt(0).toUpperCase() + routine.frequency.slice(1);
        }
        if (routine.time || routine.hour !== undefined) {
            const time = routine.time || `${routine.hour}:00`;
            scheduleText += scheduleText ? ` at ${time}` : time;
        }
        scheduleEl.textContent = scheduleText || routine.schedule || 'No schedule set';
    }

    // Populate description
    const descEl = document.getElementById('routineDescription');
    if (descEl) descEl.textContent = routine.description || 'No description provided.';

    // Populate steps
    const stepsEl = document.getElementById('routineSteps');
    if (stepsEl) {
        // Handle both array of objects and array of strings
        let steps = routine.steps || [];
        if (typeof steps === 'string') {
            try {
                steps = JSON.parse(steps);
            } catch (e) {
                steps = steps.split(',').map(s => s.trim());
            }
        }

        if (steps.length > 0) {
            const stepsHTML = steps.map((step, index) => {
                const stepTitle = typeof step === 'object' ? (step.title || step.name || `Step ${index + 1}`) : step;
                const stepDesc = typeof step === 'object' ? step.description : '';

                return `
                    <div class="routine-step-hjn">
                        <div class="step-number-hjn">${index + 1}</div>
                        <div class="step-content-hjn">
                            <h5 class="step-title-hjn">${stepTitle}</h5>
                            ${stepDesc ? `<p class="step-description-hjn">${stepDesc}</p>` : ''}
                        </div>
                    </div>
                `;
            }).join('');
            stepsEl.innerHTML = stepsHTML;
        } else {
            stepsEl.innerHTML = '<p style="color: var(--text-secondary);">No steps defined yet.</p>';
        }
    }

    // Store data for edit functionality
    currentViewData = { type: 'routine', data: routine };
    console.log('Routine view populated successfully');
}

// Show error in view offcanvas
function showViewError(message) {
    if (!currentViewOffcanvas) return;

    const body = currentViewOffcanvas.querySelector('.offcanvas-body-hjn');
    if (!body) return;

    const loadingEl = body.querySelector('.view-loading-hjn');
    const contentEl = body.querySelector('.view-content-hjn');

    if (loadingEl) loadingEl.style.display = 'none';
    if (contentEl) contentEl.style.display = 'none';

    // Show error message
    const errorHTML = `
        <div style="text-align: center; padding: 4rem 2rem;">
            <svg viewBox="0 0 24 24" width="64" height="64" style="color: var(--myavana-coral); margin-bottom: 1.5rem;">
                <path fill="currentColor" d="M13,13H11V7H13M13,17H11V15H13M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2Z"/>
            </svg>
            <h3 style="font-family: 'Archivo Black', sans-serif; font-size: 1.5rem; margin-bottom: 0.75rem;">${message}</h3>
            <p style="color: var(--text-secondary);">Please try again or close this panel.</p>
        </div>
    `;

    body.innerHTML = errorHTML;
}

// Edit functions (will trigger edit mode or open edit offcanvas)
// Edit functions to open create offcanvases with pre-filled data
function editEntry() {
    if (!currentViewData || currentViewData.type !== 'entry') return;

    console.log('Edit entry:', currentViewData);
    
    // Close view offcanvas first
    closeTimelineViewOffcanvas();
    
    // Open create offcanvas in edit mode
    openEditOffcanvas('entry', currentViewData);
}

function editGoal() {
    if (!currentViewData || currentViewData.type !== 'goal') return;

    console.log('Edit goal:', currentViewData);
    
    closeTimelineViewOffcanvas();
    openEditOffcanvas('goal', currentViewData);
}

function editRoutine() {
    if (!currentViewData || currentViewData.type !== 'routine') return;

    console.log('Edit routine:', currentViewData);
    
    closeTimelineViewOffcanvas();
    openEditOffcanvas('routine', currentViewData);
}

// Main function to open edit offcanvas with pre-filled data
function openEditOffcanvas(type, data) {
    const offcanvasMap = {
        'entry': 'entryOffcanvas',
        'goal': 'goalOffcanvas',
        'routine': 'routineOffcanvas'
    };

    currentOffcanvas = document.getElementById(offcanvasMap[type]);
    const overlay = document.getElementById('createOffcanvasOverlay');

    // Prevent body scroll
    document.body.style.overflow = 'hidden';

    // Show overlay
    overlay.classList.add('active');

    // Show offcanvas after a short delay for smooth animation
    setTimeout(() => {
        currentOffcanvas.classList.add('active');
        // Populate form with data
        populateEditForm(type, data);
    }, 10);
}

// Populate form fields with existing data
function populateEditForm(type, data) {
    switch (type) {
        case 'entry':
            populateEntryForm_v1(data);
            break;
        case 'goal':
            populateGoalForm_v1(data);
            break;
        case 'routine':
            populateRoutineForm_v1(data);
            break;
    }
}

// Populate entry form with existing data (Legacy - V1)
function populateEntryForm_v1(entryData) {
    // Update title
    document.getElementById('entryOffcanvasTitle').textContent = 'Edit Hair Journey Entry';
    
    // Set hidden ID field
    const entryIdInput = document.getElementById('entry_id');
    if (entryIdInput) {
        entryIdInput.value = entryData.id || '';
    }

    // Populate form fields
    const titleInput = document.getElementById('entry_title');
    if (titleInput && entryData.title) titleInput.value = entryData.title;

    const dateInput = document.getElementById('entry_date');
    if (dateInput && entryData.date) {
        // Format date for input[type="date"]
        const date = new Date(entryData.date);
        dateInput.value = date.toISOString().split('T')[0];
    }

    const timeInput = document.getElementById('entry_time');
    if (timeInput && entryData.time) {
        timeInput.value = entryData.time;
    }

    const contentInput = document.getElementById('entry_content');
    if (contentInput && entryData.description) {
        contentInput.value = entryData.description;
        updateCharacterCount(contentInput, 'entry_content_count');
    }

    const ratingInput = document.getElementById('health_rating');
    if (ratingInput && entryData.rating) {
        ratingInput.value = entryData.rating;
        updateRatingStars(entryData.rating);
    }

    const moodSelect = document.getElementById('mood');
    if (moodSelect && entryData.mood_demeanor) {
        moodSelect.value = entryData.mood_demeanor;
    }

    const productsInput = document.getElementById('products_used');
    if (productsInput && entryData.products) {
        productsInput.value = Array.isArray(entryData.products) 
            ? entryData.products.join('\n')
            : entryData.products;
    }

    const notesInput = document.getElementById('notes');
    if (notesInput && entryData.notes) {
        notesInput.value = entryData.notes;
    }

    const techniquesInput = document.getElementById('techniques');
    if (techniquesInput && entryData.techniques) {
        techniquesInput.value = entryData.techniques;
    }

    // Handle photos - this would need integration with your FilePond instance
    if (entryData.photos && entryData.photos.length > 0) {
        console.log('Entry has photos:', entryData.photos);
        // You would need to implement FilePond file addition here
        // This depends on how your FilePond is set up
    }

    // Update submit button text
    const submitBtn = document.querySelector('#entryForm button[type="submit"]');
    if (submitBtn) {
        submitBtn.innerHTML = `
            <svg viewBox="0 0 24 24" width="18" height="18">
                <path fill="currentColor" d="M17,3H5A2,2 0 0,0 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19V7L17,3M19,19H5V5H16.17L19,7.83V19M12,12A3,3 0 0,0 9,15A3,3 0 0,0 12,18A3,3 0 0,0 15,15A3,3 0 0,0 12,12M6,6H15V10H6V6Z"/>
            </svg>
            Update Entry
        `;
    }
}

// Populate goal form with existing data (Legacy - V1)
function populateGoalForm_v1(goalData) {
    document.getElementById('goalOffcanvasTitle').textContent = 'Edit Hair Goal';
    
    // Set hidden ID field
    const goalIdInput = document.getElementById('goal_id');
    if (goalIdInput) {
        goalIdInput.value = goalData.index || '';
    }

    // Populate form fields
    const titleInput = document.getElementById('goal_title');
    if (titleInput && goalData.title) titleInput.value = goalData.title;

    const categorySelect = document.getElementById('goal_category');
    if (categorySelect && goalData.category) {
        categorySelect.value = goalData.category;
    }

    const descriptionInput = document.getElementById('goal_description');
    if (descriptionInput && goalData.description) {
        descriptionInput.value = goalData.description;
    }

    const startDateInput = document.getElementById('goal_start_date');
    if (startDateInput && goalData.start_date) {
        startDateInput.value = goalData.start_date;
    }

    const endDateInput = document.getElementById('goal_end_date');
    if (endDateInput && goalData.target_date) {
        endDateInput.value = goalData.target_date;
    }

    const targetInput = document.getElementById('goal_target');
    if (targetInput && goalData.target) {
        targetInput.value = goalData.target;
    }

    const progressInput = document.getElementById('goal_progress');
    if (progressInput && goalData.progress) {
        progressInput.value = goalData.progress;
        updateProgressValue(goalData.progress);
    }

    // Populate milestones/notes
    const milestonesList = document.getElementById('milestones_list');
    if (milestonesList && goalData.progress_text && Array.isArray(goalData.progress_text)) {
        milestonesList.innerHTML = '';
        goalData.progress_text.forEach((milestone, index) => {
            if (typeof milestone === 'string' && milestone.trim()) {
                addMilestone(milestone);
            } else if (typeof milestone === 'object' && milestone.text) {
                // Handle progress notes objects
                addMilestone(milestone.text);
            }
        });
    }

    // Show existing progress notes in edit mode
    const notesGroup = document.getElementById('goalProgressNotesGroup');
    const notesList = document.getElementById('goalProgressNotesList');
    const addNoteGroup = document.getElementById('addProgressNoteGroup');
    if (notesGroup && notesList && goalData.progress_text && Array.isArray(goalData.progress_text)) {
        const notes = goalData.progress_text.filter(note =>
            typeof note === 'object' && note.text && note.date
        );

        if (notes.length > 0) {
            notesGroup.style.display = 'block';
            populateGoalEditProgressNotes(notes);
        }
    }

    // Always show add progress note field in edit mode
    if (addNoteGroup) {
        addNoteGroup.style.display = 'block';
        // Initialize character counter for new progress note
        const newNoteTextarea = document.getElementById('newProgressNote');
        if (newNoteTextarea) {
            initProgressNoteCounter(newNoteTextarea);
        }
    }

    // Update submit button text
    const submitBtn = document.querySelector('#goalForm button[type="submit"]');
    if (submitBtn) {
        submitBtn.innerHTML = `
            <svg viewBox="0 0 24 24" width="18" height="18">
                <path fill="currentColor" d="M17,3H5A2,2 0 0,0 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19V7L17,3M19,19H5V5H16.17L19,7.83V19M12,12A3,3 0 0,0 9,15A3,3 0 0,0 12,18A3,3 0 0,0 15,15A3,3 0 0,0 12,12M6,6H15V10H6V6Z"/>
            </svg>
            Update Goal
        `;
    }
}

// Populate routine form with existing data (Legacy - V1)
function populateRoutineForm_v1(routineData) {
    document.getElementById('routineOffcanvasTitle').textContent = 'Edit Hair Routine';
    
    // Set hidden ID field
    const routineIdInput = document.getElementById('routine_id');
    if (routineIdInput) {
        routineIdInput.value = routineData.index || '';
    }

    // Populate form fields
    const titleInput = document.getElementById('routine_title');
    if (titleInput && routineData.name) titleInput.value = routineData.name;

    const typeSelect = document.getElementById('routine_type');
    if (typeSelect && routineData.routine_type) {
        typeSelect.value = routineData.routine_type;
    }

    const frequencySelect = document.getElementById('routine_frequency');
    if (frequencySelect && routineData.frequency) {
        frequencySelect.value = routineData.frequency;
    }

    const timeInput = document.getElementById('routine_time');
    if (timeInput && routineData.time_of_day) {
        timeInput.value = routineData.time_of_day;
    }

    const durationSelect = document.getElementById('routine_duration');
    if (durationSelect && routineData.duration) {
        durationSelect.value = routineData.duration;
    }

    const productsInput = document.getElementById('routine_products');
    if (productsInput && routineData.products) {
        productsInput.value = Array.isArray(routineData.products) 
            ? routineData.products.join('\n')
            : routineData.products;
    }

    const notesInput = document.getElementById('routine_notes');
    if (notesInput && routineData.notes) {
        notesInput.value = routineData.notes;
    }

    // Populate routine steps
    const stepsList = document.getElementById('routine_steps_list');
    if (stepsList && routineData.description) {
        stepsList.innerHTML = '';
        
        // Split description by newlines to get steps
        const steps = routineData.description.split('\n').filter(step => step.trim());
        
        steps.forEach((step, index) => {
            addRoutineStep(step);
        });
    }

    // Update submit button text
    const submitBtn = document.querySelector('#routineForm button[type="submit"]');
    if (submitBtn) {
        submitBtn.innerHTML = `
            <svg viewBox="0 0 24 24" width="18" height="18">
                <path fill="currentColor" d="M17,3H5A2,2 0 0,0 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19V7L17,3M19,19H5V5H16.17L19,7.83V19M12,12A3,3 0 0,0 9,15A3,3 0 0,0 12,18A3,3 0 0,0 15,15A3,3 0 0,0 12,12M6,6H15V10H6V6Z"/>
            </svg>
            Update Routine
        `;
    }
}

// Helper function to update character count
function updateCharacterCount(textarea, countElementId) {
    const countElement = document.getElementById(countElementId);
    if (countElement) {
        countElement.textContent = textarea.value.length;
    }
}

// Helper function to update rating stars display
function updateRatingStars(rating) {
    const stars = document.querySelectorAll('.rating-star-hjn');
    const ratingValue = document.getElementById('health_rating_value');
    
    stars.forEach((star, index) => {
        const value = index + 1;
        if (value <= rating) {
            star.classList.add('active');
        } else {
            star.classList.remove('active');
        }
    });
    
    if (ratingValue) {
        ratingValue.textContent = rating === 0 ? 'Not Rated' : `${rating}/5`;
    }
}

// Update your existing closeOffcanvas function to handle resetting forms
// Reset form to create mode
function resetEditForm(type) {
    const titleMap = {
        'entry': 'Add Hair Journey Entry',
        'goal': 'Create Hair Goal', 
        'routine': 'Create Hair Routine'
    };
    
    const buttonMap = {
        'entry': 'Save Entry',
        'goal': 'Save Goal',
        'routine': 'Save Routine'
    };
    
    // Reset title
    const titleElement = document.getElementById(`${type}OffcanvasTitle`);
    if (titleElement && titleMap[type]) {
        titleElement.textContent = titleMap[type];
    }
    
    // Reset submit button
    const submitBtn = document.querySelector(`#${type}Form button[type="submit"]`);
    if (submitBtn && buttonMap[type]) {
        submitBtn.innerHTML = `
            <svg viewBox="0 0 24 24" width="18" height="18">
                <path fill="currentColor" d="M17,3H5A2,2 0 0,0 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19V7L17,3M19,19H5V5H16.17L19,7.83V19M12,12A3,3 0 0,0 9,15A3,3 0 0,0 12,18A3,3 0 0,0 15,15A3,3 0 0,0 12,12M6,6H15V10H6V6Z"/>
            </svg>
            ${buttonMap[type]}
        `;
    }
    
    // Clear hidden ID fields
    const idInput = document.getElementById(`${type}_id`);
    if (idInput) {
        idInput.value = '';
    }
    
    // Reset form (this will clear all fields)
    const form = document.getElementById(`${type}Form`);
    if (form) {
        form.reset();
    }
    
    // Reset dynamic lists
    if (type === 'goal') {
        const milestonesList = document.getElementById('milestones_list');
        if (milestonesList) milestonesList.innerHTML = '';
    } else if (type === 'routine') {
        const stepsList = document.getElementById('routine_steps_list');
        if (stepsList) {
            stepsList.innerHTML = `
                <div class="routine-step-item-hjn" data-step="1">
                    <div class="step-number-hjn">1</div>
                    <input
                        type="text"
                        name="routine_steps[]"
                        class="form-input-hjn step-input-hjn"
                        placeholder="Describe this step..."
                        required
                    >
                    <button type="button" class="btn-remove-step-hjn" onclick="removeRoutineStep(this)" disabled>
                        <svg viewBox="0 0 24 24" width="18" height="18">
                            <path fill="currentColor" d="M19,6.41L17.59,5L12,10.59L6.41,5L5,6.41L10.59,12L5,17.59L6.41,19L12,13.41L17.59,19L19,17.59L13.41,12L19,6.41Z"/>
                        </svg>
                    </button>
                </div>
            `;
        }
    }
}


/* ================================================================
   CREATE/EDIT FORMS FUNCTIONALITY
   ================================================================ */

// FilePond instances
let entryFilePond = null;

/**
 * Initialize create/edit forms
 */
function initCreateForms() {
    console.log('Initializing create/edit forms...');

    // Initialize FilePond for entry photos
    initFilePond();

    // Initialize rating stars
    initRatingStars();

    // Initialize form submissions
    initFormSubmissions();

    // Initialize character counters
    initCharacterCounters();

    // Initialize goal form specific elements
    initGoalFormElements();

    // Set default dates
    const today = new Date().toISOString().split('T')[0];
    const entryDateInput = document.getElementById('entry_date');
    if (entryDateInput && !entryDateInput.value) {
        entryDateInput.value = today;
    }

    console.log('Create/edit forms initialized');
}

/**
 * Initialize FilePond for image uploads
 */
function initFilePond() {
    const filePondEl = document.getElementById('entry_photos');
    if (!filePondEl || typeof FilePond === 'undefined') {
        console.log('FilePond not available');
        return;
    }

    entryFilePond = FilePond.create(filePondEl, {
        allowMultiple: true,
        maxFiles: 5,
        maxFileSize: '5MB',
        acceptedFileTypes: ['image/*'],
        labelIdle: 'Drag & Drop photos or <span class="filepond--label-action">Browse</span>',
        imagePreviewHeight: 150,
        imageCropAspectRatio: '1:1',
        imageResizeTargetWidth: 800,
        imageResizeTargetHeight: 800,
        imageResizeMode: 'cover',
        imageResizeUpscale: false,
        stylePanelLayout: 'compact',
        credits: false
    });

    console.log('FilePond initialized');
}

/**
 * Initialize rating stars
 */
function initRatingStars() {
    const ratingStars = document.getElementById('health_rating_stars');
    if (!ratingStars) return;

    const stars = ratingStars.querySelectorAll('.rating-star-hjn');
    const ratingInput = document.getElementById('health_rating');
    const ratingValue = document.getElementById('health_rating_value');

    stars.forEach((star, index) => {
        star.addEventListener('click', function(e) {
            e.preventDefault();
            const value = parseInt(this.getAttribute('data-value'));
            
            // Update hidden input
            ratingInput.value = value;
            
            // Update stars visual
            stars.forEach((s, i) => {
                if (i < value) {
                    s.classList.add('active');
                } else {
                    s.classList.remove('active');
                }
            });
            
            // Update value display
            ratingValue.textContent = value + '/5';
        });
    });
}

/**
 * Initialize form submissions
 */
function initFormSubmissions() {
    // Entry form
    const entryForm = document.getElementById('entryForm');
    if (entryForm) {
        entryForm.addEventListener('submit', handleEntrySubmit);
    }

    // Goal form
    const goalForm = document.getElementById('goalForm');
    if (goalForm) {
        goalForm.addEventListener('submit', handleGoalSubmit);
    }

    // Routine form
    const routineForm = document.getElementById('routineForm');
    if (routineForm) {
        routineForm.addEventListener('submit', handleRoutineSubmit);
    }
}

/**
 * Initialize character counters
 */
function initCharacterCounters() {
    const entryContent = document.getElementById('entry_content');
    const entryContentCount = document.getElementById('entry_content_count');

    if (entryContent && entryContentCount) {
        entryContent.addEventListener('input', function() {
            entryContentCount.textContent = this.value.length;
        });
    }
}

/**
 * Initialize goal form specific elements
 */
function initGoalFormElements() {
    // Progress note character counter
    const progressNoteTextarea = document.getElementById('newProgressNote');
    if (progressNoteTextarea) {
        initProgressNoteCounter(progressNoteTextarea);
    }
}

/**
 * Open offcanvas for creating/editing
 */
function openOffcanvas(type, id = null) {
    console.log('Opening offcanvas:', type, id);

    const overlay = document.getElementById('createOffcanvasOverlay');
    let offcanvas;
    
    switch(type) {
        case 'entry':
            offcanvas = document.getElementById('entryOffcanvas');
            if (id) {
                loadEntryForEdit(id);
            } else {
                resetEntryForm();
            }
            break;
        case 'goal':
            offcanvas = document.getElementById('goalOffcanvas');
            if (id) {
                loadGoalForEdit(id);
            } else {
                resetGoalForm();
            }
            break;
        case 'routine':
            offcanvas = document.getElementById('routineOffcanvas');
            if (id) {
                loadRoutineForEdit(id);
            } else {
                resetRoutineForm();
            }
            break;
        default:
            console.error('Unknown offcanvas type:', type);
            return;
    }

    if (offcanvas && overlay) {
        // Track the currently open create/edit offcanvas so the unified closer works
        currentOffcanvas = offcanvas;
        overlay.classList.add('active');
        offcanvas.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}


/**
 * Reset entry form
 */
function resetEntryForm() {
    const form = document.getElementById('entryForm');
    if (!form) return;

    form.reset();
    document.getElementById('entry_id').value = '';
    document.getElementById('entryOffcanvasTitle').textContent = 'Add Hair Journey Entry';
    
    // Reset date to today
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('entry_date').value = today;
    
    // Reset rating stars
    const stars = document.querySelectorAll('.rating-star-hjn');
    stars.forEach(star => star.classList.remove('active'));
    document.getElementById('health_rating').value = '0';
    document.getElementById('health_rating_value').textContent = 'Not Rated';
    
    // Clear FilePond
    if (entryFilePond) {
        entryFilePond.removeFiles();
    }
}

/**
 * Reset goal form
 */
function resetGoalForm() {
    const form = document.getElementById('goalForm');
    if (!form) return;

    form.reset();
    document.getElementById('goal_id').value = '';
    document.getElementById('goalOffcanvasTitle').textContent = 'Create Hair Goal';
    
    // Reset progress
    document.getElementById('goal_progress').value = '0';
    document.getElementById('goal_progress_value').textContent = '0%';
    
    // Clear milestones
    document.getElementById('milestones_list').innerHTML = '';
}

/**
 * Reset routine form
 */
function resetRoutineForm() {
    const form = document.getElementById('routineForm');
    if (!form) return;

    form.reset();
    document.getElementById('routine_id').value = '';
    document.getElementById('routineOffcanvasTitle').textContent = 'Create Hair Routine';
    
    // Reset steps to just one
    const stepsList = document.getElementById('routine_steps_list');
    stepsList.innerHTML = `
        <div class="routine-step-item-hjn" data-step="1">
            <div class="step-number-hjn">1</div>
            <input type="text" name="routine_steps[]" class="form-input-hjn step-input-hjn" placeholder="Describe this step..." required>
            <button type="button" class="btn-remove-step-hjn" onclick="removeRoutineStep(this)" disabled>
                <svg viewBox="0 0 24 24" width="18" height="18">
                    <path fill="currentColor" d="M19,6.41L17.59,5L12,10.59L6.41,5L5,6.41L10.59,12L5,17.59L6.41,19L12,13.41L17.59,19L19,17.59L13.41,12L19,6.41Z"/>
                </svg>
            </button>
        </div>
    `;
}
/**
 * Form validation functions
 */

/**
 * Validate form based on type
 */
function validateForm(form, formType) {
    clearFormErrors(form);

    switch(formType) {
        case 'entry':
            return validateEntryForm(form);
        case 'goal':
            return validateGoalForm(form);
        case 'routine':
            return validateRoutineForm(form);
        default:
            return { isValid: true };
    }
}

/**
 * Validate entry form
 */
function validateEntryForm(form) {
    const title = form.querySelector('#entry_title')?.value?.trim();
    const content = form.querySelector('#entry_content')?.value?.trim();
    const date = form.querySelector('#entry_date')?.value;

    // Required fields
    if (!title) {
        return { isValid: false, message: 'Please enter a title for your entry.' };
    }

    if (!content) {
        return { isValid: false, message: 'Please add some content to your entry.' };
    }

    if (!date) {
        return { isValid: false, message: 'Please select a date for your entry.' };
    }

    // Length validation
    if (title.length > 100) {
        return { isValid: false, message: 'Title must be 100 characters or less.' };
    }

    if (content.length > 2000) {
        return { isValid: false, message: 'Content must be 2000 characters or less.' };
    }

    return { isValid: true };
}

/**
 * Validate goal form
 */
function validateGoalForm(form) {
    const title = form.querySelector('#goal_title')?.value?.trim();
    const startDate = form.querySelector('#goal_start_date')?.value;
    const endDate = form.querySelector('#goal_end_date')?.value;

    if (!title) {
        return { isValid: false, message: 'Please enter a title for your goal.' };
    }

    if (!startDate) {
        return { isValid: false, message: 'Please select a start date for your goal.' };
    }

    if (endDate && new Date(endDate) <= new Date(startDate)) {
        return { isValid: false, message: 'End date must be after the start date.' };
    }

    if (title.length > 100) {
        return { isValid: false, message: 'Goal title must be 100 characters or less.' };
    }

    return { isValid: true };
}

/**
 * Validate routine form
 */
function validateRoutineForm(form) {
    const title = form.querySelector('#routine_title')?.value?.trim();
    const steps = form.querySelectorAll('#routine_steps_list input');

    if (!title) {
        return { isValid: false, message: 'Please enter a title for your routine.' };
    }

    // Check if at least one step has content
    const hasValidStep = Array.from(steps).some(step => step.value?.trim());
    if (!hasValidStep) {
        return { isValid: false, message: 'Please add at least one step to your routine.' };
    }

    if (title.length > 100) {
        return { isValid: false, message: 'Routine title must be 100 characters or less.' };
    }

    return { isValid: true };
}

/**
 * Clear all form errors
 */
function clearFormErrors(form) {
    form.querySelectorAll('.form-error').forEach(el => {
        el.classList.remove('form-error');
        const errorMsg = el.parentNode.querySelector('.error-message');
        if (errorMsg) errorMsg.remove();
    });
}

/**
 * Highlight field with error
 */
function highlightFieldError(form, fieldName, message) {
    const field = form.querySelector(`#${fieldName}, [name="${fieldName}"]`);
    if (field) {
        field.classList.add('form-error');
        field.focus();

        // Add error message
        let errorMsg = field.parentNode.querySelector('.error-message');
        if (!errorMsg) {
            errorMsg = document.createElement('div');
            errorMsg.className = 'error-message';
            errorMsg.style.cssText = `
                color: var(--myavana-coral);
                font-size: 0.875rem;
                margin-top: 0.25rem;
                font-family: 'Archivo', sans-serif;
            `;
            field.parentNode.appendChild(errorMsg);
        }
        errorMsg.textContent = message;
    }
}

/**
 * Unified form submission handler for entries, goals, and routines
 */
async function handleUnifiedFormSubmit(e) {
    e.preventDefault();
    const form = e.target;

    // Determine form type from form ID or class
    let formType = 'entry'; // default
    if (form.id === 'goalForm' || form.classList.contains('goal-form')) formType = 'goal';
    if (form.id === 'routineForm' || form.classList.contains('routine-form')) formType = 'routine';

    console.log(`Submitting ${formType} form...`);

    // Validate form before submission
    const validationResult = validateForm(form, formType);
    if (!validationResult.isValid) {
        showNotification(validationResult.message, 'error');
        // Highlight first invalid field
        const firstInvalid = form.querySelector('.form-error');
        if (firstInvalid) firstInvalid.focus();
        return;
    }

    // Get form elements
    const loadingEl = document.getElementById(`${formType}FormLoading`);
    const submitBtn = document.getElementById(`save${formType.charAt(0).toUpperCase() + formType.slice(1)}Btn`);

    // Show loading state
    if (loadingEl) loadingEl.style.display = 'flex';
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Saving...';
    }

    try {
        const formData = new FormData();
        const settings = window.myavanaTimelineSettings || {};

        // Common fields
        formData.append('action', 'myavana_entry_action');
        formData.append('form_type', formType);

        // Nonce
        const nonceInput = form.querySelector('input[name="myavana_nonce"]');
        if (nonceInput) formData.append('myavana_nonce', nonceInput.value);

        // Handle specific form types
        switch(formType) {
            case 'entry':
                await prepareEntryFormData(form, formData);
                break;
            case 'goal':
                await prepareGoalFormData(form, formData);
                break;
            case 'routine':
                await prepareRoutineFormData(form, formData);
                break;
        }

        // Submit via AJAX
        const ajaxUrl = (settings.ajaxUrl || settings.ajaxurl) || '/wp-admin/admin-ajax.php';
        const response = await fetch(ajaxUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();

        if (data.success) {
            console.log(`${formType} saved successfully:`, data);
            showNotification(data.data?.message || `${formType} saved successfully!`, 'success');

            // Reset form
            resetFormByType(formType);

            // Close offcanvas and refresh
            closeOffcanvas();
            refreshCurrentView();
        } else {
            console.error(`Error saving ${formType}:`, data);
            const errorMessage = data.data?.message || data.message || `Error saving ${formType}`;
            showNotification(errorMessage, 'error');

            // Handle specific error types
            if (data.data?.field) {
                highlightFieldError(form, data.data.field, errorMessage);
            }
        }

    } catch (error) {
        console.error(`Error submitting ${formType}:`, error);
        let errorMessage = 'Network error. Please try again.';

        if (error.message.includes('HTTP error')) {
            errorMessage = 'Server error. Please try again later.';
        } else if (error.name === 'TypeError' && error.message.includes('fetch')) {
            errorMessage = 'Connection failed. Check your internet connection.';
        }

        showNotification(errorMessage, 'error');
    } finally {
        // Hide loading state
        if (loadingEl) loadingEl.style.display = 'none';
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = form.querySelector('#entry_id')?.value ? 'Update Entry' :
                                   form.querySelector('#goal_id')?.value ? 'Update Goal' :
                                   form.querySelector('#routine_id')?.value ? 'Update Routine' :
                                   `Save ${formType.charAt(0).toUpperCase() + formType.slice(1)}`;
        }
    }
/**
 * Enhanced image upload handling for entries
 */
async function handleImageUploads(form, formData) {
    const entryId = form.querySelector('#entry_id')?.value;

    // Handle existing images (for edit mode)
    const existingImages = [];
    const existingImageItems = document.querySelectorAll('.existing-image-item-hjn');
    existingImageItems.forEach(item => {
        const imageUrl = item.getAttribute('data-image-url');
        if (imageUrl) {
            existingImages.push(imageUrl);
        }
    });

    if (existingImages.length > 0) {
        formData.append('existing_images', JSON.stringify(existingImages));
        console.log('Including existing images:', existingImages);
    }

    // Handle new uploads via FilePond
    if (typeof entryFilePond !== 'undefined' && entryFilePond) {
        const files = entryFilePond.getFiles();

        if (files && files.length > 0) {
            console.log(`Processing ${files.length} new image(s) for upload`);

            // Validate file sizes and types
            const validFiles = [];
            const invalidFiles = [];

            files.forEach((fileItem, index) => {
                const file = fileItem.file;

                // Check file type
                if (!file.type.startsWith('image/')) {
                    invalidFiles.push(`${file.name}: Not an image file`);
                    return;
                }

                // Check file size (10MB limit)
                if (file.size > 10 * 1024 * 1024) {
                    invalidFiles.push(`${file.name}: File too large (max 10MB)`);
                    return;
                }

                validFiles.push(fileItem);
            });

            // Show warnings for invalid files
            if (invalidFiles.length > 0) {
                showNotification(`Some files were skipped:\n${invalidFiles.join('\n')}`, 'warning', 5000);
            }

            // Process valid files
            if (validFiles.length > 0) {
                validFiles.forEach((fileItem, index) => {
                    const file = fileItem.file;

                    // Compress image if needed
                    compressImage(file).then(compressedFile => {
                        // For new entries: single photo (legacy compatibility)
                        if (index === 0 && !entryId) {
                            formData.append('photo', compressedFile);
                        }

                        // For all entries: multiple photos array
                        formData.append('photos[]', compressedFile);

                        console.log(`Added ${compressedFile.name} (${(compressedFile.size / 1024 / 1024).toFixed(2)}MB)`);
                    }).catch(error => {
                        console.error('Error compressing image:', error);
                        // Fallback to original file
                        if (index === 0 && !entryId) {
                            formData.append('photo', file);
                        }
                        formData.append('photos[]', file);
                    });
                });

                // Show processing feedback
                showNotification(`Processing ${validFiles.length} image(s)...`, 'info', 2000);
            }
        }
    }

    // Handle images marked for deletion
    const imagesToDelete = [];
    const deletedImageItems = document.querySelectorAll('.existing-image-item-hjn.deleted');
    deletedImageItems.forEach(item => {
        const imageUrl = item.getAttribute('data-image-url');
        if (imageUrl) {
            imagesToDelete.push(imageUrl);
        }
    });

    if (imagesToDelete.length > 0) {
        formData.append('delete_images', JSON.stringify(imagesToDelete));
        console.log('Marking images for deletion:', imagesToDelete);
    }
}

/**
 * Compress image for better upload performance
 */
async function compressImage(file) {
    return new Promise((resolve, reject) => {
        // Only compress if file is larger than 2MB
        if (file.size <= 2 * 1024 * 1024) {
            resolve(file);
            return;
        }

        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        const img = new Image();

        img.onload = () => {
            // Calculate new dimensions (max 1200px width/height)
            let { width, height } = img;

            if (width > 1200 || height > 1200) {
                if (width > height) {
                    height = (height * 1200) / width;
                    width = 1200;
                } else {
                    width = (width * 1200) / height;
                    height = 1200;
                }
            }

            canvas.width = width;
            canvas.height = height;

            // Draw and compress
            ctx.drawImage(img, 0, 0, width, height);

            canvas.toBlob((blob) => {
                const compressedFile = new File([blob], file.name, {
                    type: file.type,
                    lastModified: Date.now()
                });

                console.log(`Compressed ${file.name}: ${(file.size / 1024 / 1024).toFixed(2)}MB → ${(compressedFile.size / 1024 / 1024).toFixed(2)}MB`);
                resolve(compressedFile);
            }, file.type, 0.85); // 85% quality
        };

        img.onerror = () => reject(new Error('Failed to load image for compression'));
        img.src = URL.createObjectURL(file);
    });
}
}

/**
 * Prepare entry-specific form data
 */
async function prepareEntryFormData(form, formData) {
    // Basic fields
    formData.append('title', (form.querySelector('#entry_title')?.value || '').trim());
    formData.append('description', (form.querySelector('#entry_content')?.value || '').trim());
    formData.append('products', (form.querySelector('#products_used')?.value || '').trim());
    formData.append('notes', (form.querySelector('#notes')?.value || '').trim());
    formData.append('rating', form.querySelector('#health_rating')?.value || '3');
    formData.append('mood_demeanor', form.querySelector('#mood')?.value || '');
    formData.append('entry_date', form.querySelector('#entry_date')?.value || '');
    formData.append('time', form.querySelector('#entry_time')?.value || '');
    formData.append('techniques', form.querySelector('#techniques')?.value || '');

    // Legacy flags for compatibility
    const isAutomatedInput = form.querySelector('input[name="is_automated"]');
    formData.append('is_automated', isAutomatedInput ? isAutomatedInput.value : '0');
    const entryFlagInput = form.querySelector('input[name="myavana_entry"]');
    if (entryFlagInput) formData.append('myavana_entry', entryFlagInput.value);

    // Optional extras
    const envInput = form.querySelector('select[name="environment"]');
    if (envInput) formData.append('environment', envInput.value);

    // Enhanced image handling
    await handleImageUploads(form, formData);
}

/**
 * Prepare goal-specific form data
 */
async function prepareGoalFormData(form, formData) {
    // Basic goal fields
    formData.append('goal_title', form.querySelector('#goal_title')?.value || '');
    formData.append('goal_description', form.querySelector('#goal_description')?.value || '');
    formData.append('goal_progress', form.querySelector('#goal_progress')?.value || '0');
    formData.append('goal_start_date', form.querySelector('#goal_start_date')?.value || '');
    formData.append('goal_end_date', form.querySelector('#goal_end_date')?.value || '');
    formData.append('goal_category', form.querySelector('#goal_category')?.value || '');
    formData.append('goal_target', form.querySelector('#goal_target')?.value || '');

    // Collect milestones
    const milestones = [];
    const milestoneInputs = document.querySelectorAll('#milestones_list input');
    milestoneInputs.forEach(input => {
        if (input.value.trim()) {
            milestones.push(input.value.trim());
        }
    });

    // Collect new progress note
    const newNoteText = document.getElementById('newProgressNote')?.value?.trim();
    if (newNoteText) {
        const progressNotes = milestones.concat([{
            text: newNoteText,
            date: new Date().toISOString()
        }]);
        formData.append('goal_progress_notes', JSON.stringify(progressNotes));
    } else {
        formData.append('goal_progress_notes', JSON.stringify(milestones));
    }
}

/**
 * Prepare routine-specific form data
 */
async function prepareRoutineFormData(form, formData) {
    // Basic routine fields
    formData.append('routine_title', form.querySelector('#routine_title')?.value || '');
    formData.append('routine_type', form.querySelector('#routine_type')?.value || '');
    formData.append('routine_frequency', form.querySelector('#routine_frequency')?.value || 'daily');
    formData.append('routine_time', form.querySelector('#routine_time')?.value || '');
    formData.append('routine_duration', form.querySelector('#routine_duration')?.value || '');
    formData.append('routine_products', form.querySelector('#routine_products')?.value || '');
    formData.append('routine_notes', form.querySelector('#routine_notes')?.value || '');

    // Collect routine steps
    const steps = [];
    const stepInputs = document.querySelectorAll('#routine_steps_list input');
    stepInputs.forEach(input => {
        if (input.value.trim()) {
            steps.push(input.value.trim());
        }
    });

    formData.append('routine_steps', JSON.stringify(steps));
}

/**
 * Reset form based on type
 */
function resetFormByType(formType) {
    const form = document.getElementById(`${formType}Form`);
    if (!form) return;

    form.reset();

    // Type-specific resets
    switch(formType) {
        case 'entry':
            // Reset FilePond
            if (typeof entryFilePond !== 'undefined' && entryFilePond) {
                entryFilePond.removeFiles();
            }
            // Reset rating stars
            document.querySelectorAll('.rating-star-hjn').forEach(star => star.classList.remove('active'));
            document.getElementById('health_rating').value = '0';
            document.getElementById('health_rating_value').textContent = 'Not Rated';
            break;

        case 'goal':
            // Reset progress and milestones
            document.getElementById('goal_progress').value = '0';
            document.getElementById('goal_progress_value').textContent = '0%';
            document.getElementById('milestones_list').innerHTML = '';
            break;

        case 'routine':
            // Reset steps to one default step
            const stepsList = document.getElementById('routine_steps_list');
            stepsList.innerHTML = `
                <div class="routine-step-item-hjn" data-step="1">
                    <div class="step-number-hjn">1</div>
                    <input type="text" name="routine_steps[]" class="form-input-hjn step-input-hjn" placeholder="Describe this step..." required>
                    <button type="button" class="btn-remove-step-hjn" onclick="removeRoutineStep(this)" disabled>
                        <svg viewBox="0 0 24 24" width="18" height="18">
                            <path fill="currentColor" d="M19,6.41L17.59,5L12,10.59L6.41,5L5,6.41L10.59,12L5,17.59L6.41,19L12,13.41L17.59,19L19,17.59L13.41,12L19,6.41Z"/>
                        </svg>
                    </button>
                </div>
            `;
            break;
    }
}

// Legacy function alias for backwards compatibility
const handleEntrySubmit = handleUnifiedFormSubmit;

/**
 * Legacy goal submit handler - now delegates to unified handler
 */
async function handleGoalSubmit(e) {
    return handleUnifiedFormSubmit(e);
}

/**
 * Legacy routine submit handler - now delegates to unified handler
 */
async function handleRoutineSubmit(e) {
    return handleUnifiedFormSubmit(e);
}

/**
 * Add milestone to goal form
 */
function addMilestone() {
    const milestonesList = document.getElementById('milestones_list');
    const milestoneCount = milestonesList.querySelectorAll('.milestone-item-hjn').length;
    
    const milestoneItem = document.createElement('div');
    milestoneItem.className = 'milestone-item-hjn';
    milestoneItem.innerHTML = `
        <input type="text" class="form-input-hjn" placeholder="Milestone ${milestoneCount + 1}...">
        <button type="button" class="btn-remove-milestone-hjn" onclick="removeMilestone(this)">
            <svg viewBox="0 0 24 24" width="18" height="18">
                <path fill="currentColor" d="M19,6.41L17.59,5L12,10.59L6.41,5L5,6.41L10.59,12L5,17.59L6.41,19L12,13.41L17.59,19L19,17.59L13.41,12L19,6.41Z"/>
            </svg>
        </button>
    `;
    
    milestonesList.appendChild(milestoneItem);
}

/**
 * Remove milestone
 */
function removeMilestone(button) {
    button.closest('.milestone-item-hjn').remove();
}

/**
 * Add routine step
 */
function addRoutineStep() {
    const stepsList = document.getElementById('routine_steps_list');
    const stepCount = stepsList.querySelectorAll('.routine-step-item-hjn').length + 1;
    
    const stepItem = document.createElement('div');
    stepItem.className = 'routine-step-item-hjn';
    stepItem.setAttribute('data-step', stepCount);
    stepItem.innerHTML = `
        <div class="step-number-hjn">${stepCount}</div>
        <input type="text" name="routine_steps[]" class="form-input-hjn step-input-hjn" placeholder="Describe this step..." required>
        <button type="button" class="btn-remove-step-hjn" onclick="removeRoutineStep(this)">
            <svg viewBox="0 0 24 24" width="18" height="18">
                <path fill="currentColor" d="M19,6.41L17.59,5L12,10.59L6.41,5L5,6.41L10.59,12L5,17.59L6.41,19L12,13.41L17.59,19L19,17.59L13.41,12L19,6.41Z"/>
            </svg>
        </button>
    `;
    
    stepsList.appendChild(stepItem);
    updateRemoveStepButtons();
}

/**
 * Remove routine step
 */
function removeRoutineStep(button) {
    const stepsList = document.getElementById('routine_steps_list');
    const steps = stepsList.querySelectorAll('.routine-step-item-hjn');
    
    if (steps.length <= 1) return; // Keep at least one step
    
    button.closest('.routine-step-item-hjn').remove();
    
    // Renumber steps
    const remainingSteps = stepsList.querySelectorAll('.routine-step-item-hjn');
    remainingSteps.forEach((step, index) => {
        step.setAttribute('data-step', index + 1);
        step.querySelector('.step-number-hjn').textContent = index + 1;
    });
    
    updateRemoveStepButtons();
}

/**
 * Update remove step buttons (disable if only one step)
 */
function updateRemoveStepButtons() {
    const stepsList = document.getElementById('routine_steps_list');
    const steps = stepsList.querySelectorAll('.routine-step-item-hjn');
    const removeButtons = stepsList.querySelectorAll('.btn-remove-step-hjn');
    
    removeButtons.forEach(btn => {
        btn.disabled = steps.length <= 1;
    });
}

/**
 * Update progress value display
 */
function updateProgressValue(value) {
    const progressValue = document.getElementById('goal_progress_value');
    if (progressValue) {
        progressValue.textContent = value + '%';
    }
}

/**
 * Enhanced view refresh with loading states and better UX
 */
function refreshCurrentView() {
    console.log('[EntryForm] Refreshing current view...');

    // Show loading overlay for better UX
    showLoadingOverlay('Refreshing your hair journey...');

    // Get current view from URL or local storage
    const urlParams = new URLSearchParams(window.location.search);
    const currentView = urlParams.get('view') || localStorage.getItem('currentView') || 'calendar';

    // If on calendar view, reload the page to refresh data
    if (currentView === 'calendar' || !currentView) {
        setTimeout(() => {
            hideLoadingOverlay();
            window.location.reload();
        }, 1500); // Slightly longer delay for better UX
    } else {
        // For other views, try to refresh the specific view
        setTimeout(() => {
            switchView(currentView);
            hideLoadingOverlay();

            // Show success feedback
            showNotification('Your changes have been saved and the view refreshed!', 'success', 2000);
        }, 1000);
    }
}

/**
 * Enhanced form reset with visual feedback
 */
function resetFormByType(formType) {
    const form = document.getElementById(`${formType}Form`);
    if (!form) return;

    // Show brief loading state during reset
    const resetLoading = document.createElement('div');
    resetLoading.className = 'form-reset-loading';
    resetLoading.style.cssText = `
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: rgba(255,255,255,0.9);
        padding: 1rem;
        border-radius: 8px;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        z-index: 1000;
        font-family: 'Archivo', sans-serif;
        font-size: 0.875rem;
        color: var(--text-secondary);
    `;
    resetLoading.innerHTML = `
        <div style="
            width: 16px;
            height: 16px;
            border: 2px solid var(--myavana-stone);
            border-top: 2px solid var(--myavana-blueberry);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        "></div>
        Resetting form...
    `;

    // Make form container relative for positioning
    const formContainer = form.closest('.offcanvas-body-hjn') || form.parentElement;
    if (formContainer) {
        formContainer.style.position = 'relative';
        formContainer.appendChild(resetLoading);
    }

    // Perform reset after a brief delay for UX
    setTimeout(() => {
        form.reset();
        clearFormErrors(form);

        // Type-specific resets
        switch(formType) {
            case 'entry':
                // Reset FilePond
                if (typeof entryFilePond !== 'undefined' && entryFilePond) {
                    entryFilePond.removeFiles();
                }
                // Reset rating stars
                document.querySelectorAll('.rating-star-hjn').forEach(star => star.classList.remove('active'));
                document.getElementById('health_rating').value = '0';
                document.getElementById('health_rating_value').textContent = 'Not Rated';
                // Reset character count
                document.getElementById('entry_content_count').textContent = '0';
                // Clear existing images gallery
                const gallery = document.getElementById('existingImagesGallery');
                if (gallery) gallery.style.display = 'none';
                break;

            case 'goal':
                // Reset progress and milestones
                document.getElementById('goal_progress').value = '0';
                document.getElementById('goal_progress_value').textContent = '0%';
                document.getElementById('milestones_list').innerHTML = '';
                // Clear progress notes
                const newNoteTextarea = document.getElementById('newProgressNote');
                if (newNoteTextarea) {
                    newNoteTextarea.value = '';
                    const counter = document.getElementById('progress_note_count');
                    if (counter) counter.textContent = '0';
                }
                break;

            case 'routine':
                // Reset steps to one default step
                const stepsList = document.getElementById('routine_steps_list');
                stepsList.innerHTML = `
                    <div class="routine-step-item-hjn" data-step="1">
                        <div class="step-number-hjn">1</div>
                        <input type="text" name="routine_steps[]" class="form-input-hjn step-input-hjn" placeholder="Describe this step..." required>
                        <button type="button" class="btn-remove-step-hjn" onclick="removeRoutineStep(this)" disabled>
                            <svg viewBox="0 0 24 24" width="18" height="18">
                                <path fill="currentColor" d="M19,6.41L17.59,5L12,10.59L6.41,5L5,6.41L10.59,12L5,17.59L6.41,19L12,13.41L17.59,19L19,17.59L13.41,12L19,6.41Z"/>
                            </svg>
                        </button>
                    </div>
                `;
                updateRemoveStepButtons();
                break;
        }

        // Update submit button text
        const submitBtn = document.getElementById(`save${formType.charAt(0).toUpperCase() + formType.slice(1)}Btn`);
        if (submitBtn) {
            submitBtn.textContent = `Save ${formType.charAt(0).toUpperCase() + formType.slice(1)}`;
        }

        // Remove loading indicator
        if (resetLoading.parentElement) {
            resetLoading.remove();
        }

        console.log(`Form reset complete for ${formType}`);
    }, 500);
}

/**
 * Enhanced notification system with better UX
 */
function showNotification(message, type = 'info', duration = 3000) {
    console.log('Notification:', type, message);

    // Remove any existing notifications to prevent spam
    const existingNotifications = document.querySelectorAll('.notification-hjn');
    existingNotifications.forEach(notification => {
        if (!notification.classList.contains('persistent')) {
            notification.remove();
        }
    });

    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification-hjn notification-${type}-hjn`;

    // Add icon based on type
    const iconSvg = getNotificationIcon(type);
    notification.innerHTML = `
        <div class="notification-content-hjn">
            <div class="notification-icon-hjn">${iconSvg}</div>
            <div class="notification-message-hjn">${message}</div>
            <button class="notification-close-hjn" onclick="this.parentElement.parentElement.remove()" aria-label="Close notification">
                <svg viewBox="0 0 24 24" width="16" height="16">
                    <path fill="currentColor" d="M19,6.41L17.59,5L12,10.59L6.41,5L5,6.41L10.59,12L5,17.59L6.41,19L12,13.41L17.59,19L19,17.59L13.41,12L19,6.41Z"/>
                </svg>
            </button>
        </div>
    `;

    // Set base styles
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        max-width: 400px;
        background: ${getNotificationColor(type)};
        color: white;
        border-radius: 12px;
        box-shadow: 0 8px 32px rgba(0,0,0,0.15);
        z-index: 100000;
        font-family: 'Archivo', sans-serif;
        font-weight: 500;
        animation: slideInFromRight 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        overflow: hidden;
    `;

    document.body.appendChild(notification);

    // Add progress bar for timed notifications
    if (duration > 0) {
        const progressBar = document.createElement('div');
        progressBar.className = 'notification-progress-hjn';
        progressBar.style.cssText = `
            position: absolute;
            bottom: 0;
            left: 0;
            height: 3px;
            background: rgba(255,255,255,0.3);
            animation: shrinkWidth ${duration}ms linear;
        `;
        notification.appendChild(progressBar);
    }

    // Auto-remove after specified duration
    if (duration > 0) {
        setTimeout(() => {
            if (notification.parentElement) {
                notification.style.animation = 'slideOutToRight 0.3s ease';
                setTimeout(() => {
                    if (notification.parentElement) {
                        notification.remove();
                    }
                }, 300);
            }
        }, duration);
    } else {
        // Persistent notifications have close button
        notification.classList.add('persistent');
    }
}

/**
 * Get notification icon based on type
 */
function getNotificationIcon(type) {
    switch(type) {
        case 'success':
            return `<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                <path d="M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M12,4A8,8 0 0,1 20,12A8,8 0 0,1 12,20A8,8 0 0,1 4,12A8,8 0 0,1 12,4M11,7V9H13V7H11M11,11V17H13V11H11Z"/>
            </svg>`;
        case 'error':
            return `<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                <path d="M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M13,17H11V15H13V17M13,13H11V7H13V13Z"/>
            </svg>`;
        case 'warning':
            return `<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                <path d="M12,2L3.09,21H20.91L12,2M13,18H11V16H13V18M13,14H11V10H13V14Z"/>
            </svg>`;
        default: // info
            return `<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                <path d="M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M12,4A8,8 0 0,1 20,12A8,8 0 0,1 12,20A8,8 0 0,1 4,12A8,8 0 0,1 12,4M11,6V9H13V6H11M11,11V17H13V11H11Z"/>
            </svg>`;
    }
}

/**
 * Get notification color based on type
 */
function getNotificationColor(type) {
    switch(type) {
        case 'success':
            return '#4caf50';
        case 'error':
            return '#f44336';
        case 'warning':
            return '#ff9800';
        default: // info
            return '#2196f3';
    }
}

/**
 * Show loading overlay for long operations
 */
function showLoadingOverlay(message = 'Processing...') {
    let overlay = document.getElementById('myavana-loading-overlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'myavana-loading-overlay';
        overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 99999;
            backdrop-filter: blur(4px);
        `;
        overlay.innerHTML = `
            <div class="loading-content-hjn" style="
                background: white;
                padding: 2rem;
                border-radius: 16px;
                text-align: center;
                box-shadow: 0 20px 60px rgba(0,0,0,0.2);
                max-width: 300px;
            ">
                <div class="loading-spinner-hjn" style="
                    width: 40px;
                    height: 40px;
                    border: 4px solid var(--myavana-stone, #f5f5f5);
                    border-top: 4px solid var(--myavana-blueberry, #4a4e64);
                    border-radius: 50%;
                    animation: spin 1s linear infinite;
                    margin: 0 auto 1rem;
                "></div>
                <p style="
                    margin: 0;
                    font-family: 'Archivo', sans-serif;
                    font-weight: 600;
                    color: var(--myavana-onyx, #333);
                ">${message}</p>
            </div>
        `;
        document.body.appendChild(overlay);
    } else {
        const messageEl = overlay.querySelector('p');
        if (messageEl) messageEl.textContent = message;
        overlay.style.display = 'flex';
    }
}

/**
 * Hide loading overlay
 */
function hideLoadingOverlay() {
    const overlay = document.getElementById('myavana-loading-overlay');
    if (overlay) {
        overlay.style.display = 'none';
    }
}

/**
 * Show confirmation dialog
 */
function showConfirmationDialog(message, onConfirm, onCancel = null) {
    const dialog = document.createElement('div');
    dialog.className = 'confirmation-dialog-hjn';
    dialog.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 100001;
        backdrop-filter: blur(4px);
    `;

    dialog.innerHTML = `
        <div class="dialog-content-hjn" style="
            background: white;
            padding: 2rem;
            border-radius: 16px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            max-width: 400px;
            margin: 1rem;
        ">
            <div class="dialog-icon-hjn" style="
                margin-bottom: 1rem;
                color: var(--myavana-blueberry, #4a4e64);
            ">
                <svg viewBox="0 0 24 24" width="48" height="48" fill="currentColor">
                    <path d="M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M12,4A8,8 0 0,1 20,12A8,8 0 0,1 12,20A8,8 0 0,1 4,12A8,8 0 0,1 12,4M11,6V9H13V6H11M11,11V17H13V11H11Z"/>
                </svg>
            </div>
            <h3 style="
                font-family: 'Archivo Black', sans-serif;
                font-size: 1.25rem;
                margin: 0 0 1rem 0;
                color: var(--myavana-onyx, #333);
            ">Are you sure?</h3>
            <p style="
                margin: 0 0 2rem 0;
                color: var(--text-secondary, #666);
                font-family: 'Archivo', sans-serif;
            ">${message}</p>
            <div class="dialog-buttons-hjn" style="
                display: flex;
                gap: 1rem;
                justify-content: center;
            ">
                <button class="btn-cancel-hjn" style="
                    padding: 0.75rem 1.5rem;
                    border: 2px solid var(--myavana-stone, #e0e0e0);
                    background: white;
                    color: var(--text-secondary, #666);
                    border-radius: 8px;
                    font-family: 'Archivo', sans-serif;
                    font-weight: 600;
                    cursor: pointer;
                    transition: all 0.2s ease;
                ">Cancel</button>
                <button class="btn-confirm-hjn" style="
                    padding: 0.75rem 1.5rem;
                    border: none;
                    background: var(--myavana-coral, #e7a694);
                    color: white;
                    border-radius: 8px;
                    font-family: 'Archivo', sans-serif;
                    font-weight: 600;
                    cursor: pointer;
                    transition: all 0.2s ease;
                ">Confirm</button>
            </div>
        </div>
    `;

    const cancelBtn = dialog.querySelector('.btn-cancel-hjn');
    const confirmBtn = dialog.querySelector('.btn-confirm-hjn');

    cancelBtn.addEventListener('click', () => {
        dialog.remove();
        if (onCancel) onCancel();
    });

    confirmBtn.addEventListener('click', () => {
        dialog.remove();
        if (onConfirm) onConfirm();
    });

    // Close on backdrop click
    dialog.addEventListener('click', (e) => {
        if (e.target === dialog) {
            dialog.remove();
            if (onCancel) onCancel();
        }
    });

    // Close on escape key
    const handleEscape = (e) => {
        if (e.key === 'Escape') {
            dialog.remove();
            document.removeEventListener('keydown', handleEscape);
            if (onCancel) onCancel();
        }
    };
    document.addEventListener('keydown', handleEscape);

    document.body.appendChild(dialog);
}

// Load entry for editing
function loadEntryForEdit(id) {
    console.log('[EntryForm] Loading entry for edit:', id);

    // Update offcanvas title
    const titleEl = document.getElementById('entryOffcanvasTitle');
    if (titleEl) titleEl.textContent = 'Edit Hair Journey Entry';

    // Show loading state
    const submitBtn = document.getElementById('saveEntryBtn');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Loading...';
    }

    // Use standardized nonce from timeline settings
    const settings = window.myavanaTimelineSettings || {};

    // Fetch entry data via AJAX
    fetch(settings.ajaxUrl || '/wp-admin/admin-ajax.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'myavana_get_entry_details',
            security: settings.getEntryDetailsNonce || settings.nonce || '',
            entry_id: id
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.data) {
            console.log('[EntryForm] Loaded entry data:', data.data);
            populateEntryForm(data.data);
        } else {
            console.error('[EntryForm] Failed to load entry:', data);
            showNotification('Failed to load entry data', 'error');
        }
    })
    .catch(error => {
        console.error('[EntryForm] Error loading entry:', error);
        showNotification('Network error loading entry', 'error');
    })
    .finally(() => {
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Update Entry';
        }
    });
}

function loadGoalForEdit(id) {
    console.log('[GoalForm] Loading goal for edit:', id);

    // Update offcanvas title
    const titleEl = document.getElementById('goalOffcanvasTitle');
    if (titleEl) titleEl.textContent = 'Edit Hair Goal';

    // Show loading state
    const submitBtn = document.getElementById('saveGoalBtn');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Loading...';
    }

    const settings = window.myavanaTimelineSettings || {};
    console.log('[GoalForm] Fetching goal with ID:', id);

    fetch(settings.ajaxUrl || '/wp-admin/admin-ajax.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'myavana_get_goal_details',
            security: settings.getGoalDetailsNonce || '',
            goal_id: id
        })
    })
    .then(response => response.json())
    .then(data => {
        console.log('[GoalForm] Fetched goal data:', data);
        if (data.success && data.data) {
            populateGoalForm(data.data);
        } else {
            console.error('[GoalForm] Failed to load goal:', data.data || 'Unknown error');
            showNotification(data.data || 'Failed to load goal data', 'error');
        }
    })
    .catch(error => {
        console.error('[GoalForm] Network error loading goal:', error);
        showNotification('Network error loading goal data', 'error');
    })
    .finally(() => {
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Update Goal';
        }
    });
}

function loadRoutineForEdit(id) {
    console.log('Loading routine for edit:', id);

    // Update offcanvas title
    const titleEl = document.getElementById('routineOffcanvasTitle');
    if (titleEl) titleEl.textContent = 'Edit Hair Routine';

    // Extract from calendar data
    const calendarDataEl = document.getElementById('calendarDataHjn');
    if (calendarDataEl) {
        try {
            const calendarData = JSON.parse(calendarDataEl.textContent);
            const routine = calendarData.routines?.find(r => r.id == id);

            if (routine) {
                populateRoutineForm(routine);
                return;
            }
        } catch (error) {
            console.error('Error parsing calendar data:', error);
        }
    }

    console.warn('Routine not found in calendar data');
    showNotification('Routine data not available for editing', 'warning');
}

// Populate entry form with data for editing
function populateEntryForm(entry) {
    console.log('[EntryForm] === Populating Entry Form ===');
    console.log('[EntryForm] Entry data:', entry);

    try {
        // Validate entry data
        if (!entry) {
            throw new Error('Entry data is null or undefined');
        }

        // Handle nested data structure - if entry has a 'data' property, use that
        const entryData = entry.data || entry;
        console.log('[EntryForm] Actual entry data to use:', entryData);

        if (!entryData.id && !entry.id) {
            console.warn('[EntryForm] ⚠️ Warning: Entry data has no ID, this may cause issues with updates');
        }

        // Set hidden entry ID
        const entryIdInput = document.getElementById('entry_id');
        if (entryIdInput) {
            entryIdInput.value = entryData.id || entry.id || '';
            console.log('[EntryForm] ✓ Set entry_id to:', entryIdInput.value);
        } else {
            console.error('[EntryForm] ✗ entry_id input not found');
        }

        // Set title
        const titleInput = document.getElementById('entry_title');
        if (titleInput) {
            titleInput.value = entryData.title || '';
            console.log('[EntryForm] ✓ Set entry_title to:', titleInput.value);
        } else {
            console.error('[EntryForm] ✗ entry_title input not found');
        }

        // Set date - handle formatted date from view vs raw date from AJAX
        const dateInput = document.getElementById('entry_date');
        if (dateInput) {
            let dateValue = entryData.entry_date || entryData.date || '';

            // If date is formatted like "October 17, 2025 10:22 AM", convert to YYYY-MM-DD
            if (dateValue && dateValue.includes(',')) {
                const parsedDate = new Date(dateValue);
                if (!isNaN(parsedDate.getTime())) {
                    dateValue = parsedDate.toISOString().split('T')[0];
                }
            }

            dateInput.value = dateValue;
            console.log('[EntryForm] ✓ Set entry_date to:', dateInput.value);
        } else {
            console.error('[EntryForm] ✗ entry_date input not found');
        }

        // Set time - if available
        const timeInput = document.getElementById('entry_time');
        if (timeInput) {
            let timeValue = entryData.time || '';
            // If we have a formatted date with time, extract the time
            if (entryData.date && entryData.date.includes(',')) {
                const parsedDate = new Date(entryData.date);
                if (!isNaN(parsedDate.getTime())) {
                    timeValue = parsedDate.toTimeString().slice(0, 5); // HH:MM format
                }
            }
            timeInput.value = timeValue;
            console.log('[EntryForm] ✓ Set entry_time to:', timeInput.value);
        }

        // Set content
        const contentInput = document.getElementById('entry_content');
        if (contentInput) {
            contentInput.value = entryData.content || entryData.description || '';
            console.log('[EntryForm] ✓ Set entry_content');
            // Update character count
            const charCount = document.getElementById('entry_content_count');
            if (charCount) charCount.textContent = contentInput.value.length;
        } else {
            console.error('[EntryForm] ✗ entry_content textarea not found');
        }

        // Set rating
        if (entryData.rating) {
            const ratingInput = document.getElementById('health_rating');
            const ratingValue = document.getElementById('health_rating_value');
            const stars = document.querySelectorAll('#health_rating_stars .rating-star-hjn');

            if (ratingInput) ratingInput.value = entryData.rating;
            if (ratingValue) ratingValue.textContent = entryData.rating + '/5';

            stars.forEach((star, index) => {
                if (index < entryData.rating) {
                    star.classList.add('active');
                } else {
                    star.classList.remove('active');
                }
            });
            console.log('[EntryForm] ✓ Set rating to:', entryData.rating);
        }

        // Set mood
        const moodInput = document.getElementById('mood');
        if (moodInput) {
            moodInput.value = entryData.mood || entryData.mood_demeanor || '';
            console.log('[EntryForm] ✓ Set mood to:', moodInput.value);
        } else {
            console.warn('[EntryForm] ✗ Mood input field not found');
        }

        // Set products (multi-select dropdown)
        const productsSelect = document.getElementById('products_used');
        if (productsSelect && typeof $ !== 'undefined' && $.fn.select2) {
            let products = [];
            if (Array.isArray(entryData.products)) {
                products = entryData.products;
            } else if (typeof entryData.products === 'string' && entryData.products.trim()) {
                products = entryData.products.split(',').map(p => p.trim()).filter(p => p);
            } else if (entryData.products && typeof entryData.products === 'object') {
                try {
                    products = Object.values(entryData.products).map(String).map(p => p.trim()).filter(Boolean);
                } catch (e) {
                    products = [];
                }
            }

            // Set selected values
            $(productsSelect).val(products).trigger('change');
            console.log('[EntryForm] ✓ Set products to:', products);
        } else {
            console.warn('[EntryForm] ✗ Products select field not found or Select2 not available');
        }

        // Set notes
        const notesInput = document.getElementById('notes');
        if (notesInput && entryData.notes) {
            notesInput.value = entryData.notes;
            console.log('[EntryForm] ✓ Set notes');
        }

        // Set techniques
        const techniquesInput = document.getElementById('techniques');
        if (techniquesInput && entryData.techniques) {
            techniquesInput.value = entryData.techniques;
            console.log('[EntryForm] ✓ Set techniques');
        }

        // Handle existing images gallery
        populateExistingImages(entryData);

        console.log('[EntryForm] === Entry form population complete ===');
    } catch (error) {
        console.error('[EntryForm] Error populating form:', error);
        showNotification('Error loading entry data for editing', 'error');
    }
}

// Populate existing images gallery for edit mode
function populateExistingImages(entryData) {
    const galleryEl = document.getElementById('existingImagesGallery');
    const gridEl = document.getElementById('existingImagesGrid');

    if (!galleryEl || !gridEl) {
        console.warn('Existing images gallery elements not found');
        return;
    }

    // Clear existing content
    gridEl.innerHTML = '';

    // Get images from entry data
    let images = [];
    if (entryData.images && Array.isArray(entryData.images)) {
        images = entryData.images;
    } else if (entryData.image) {
        images = [entryData.image];
    } else if (entryData.thumbnail || entryData.image_url) {
        images = [entryData.thumbnail || entryData.image_url];
    }

    if (images.length === 0) {
        galleryEl.style.display = 'none';
        return;
    }

    // Show gallery and populate with images
    galleryEl.style.display = 'block';

    images.forEach((imageUrl, index) => {
        if (!imageUrl) return;

        const imageItem = document.createElement('div');
        imageItem.className = 'existing-image-item-hjn';
        imageItem.setAttribute('data-image-url', imageUrl);
        imageItem.setAttribute('data-index', index);

        imageItem.innerHTML = `
            <div class="existing-image-wrapper-hjn">
                <img src="${imageUrl}" alt="Entry image ${index + 1}" class="existing-image-hjn" />
                <button type="button" class="remove-existing-image-btn" onclick="removeExistingImage('${imageUrl}', ${index})" title="Remove this image">
                    <svg viewBox="0 0 24 24" width="16" height="16">
                        <path fill="currentColor" d="M19,6.41L17.59,5L12,10.59L6.41,5L5,6.41L10.59,12L5,17.59L6.41,19L12,13.41L17.59,19L19,17.59L13.41,12L19,6.41Z"/>
                    </svg>
                </button>
            </div>
        `;

        gridEl.appendChild(imageItem);
    });

    console.log('✓ Populated existing images gallery with', images.length, 'images');
}

// Remove existing image from gallery
function removeExistingImage(imageUrl, index) {
    const imageItem = document.querySelector(`.existing-image-item-hjn[data-image-url="${imageUrl}"]`);
    if (imageItem) {
        imageItem.remove();
        console.log('Removed existing image:', imageUrl);

        // Hide gallery if no images left
        const remainingImages = document.querySelectorAll('.existing-image-item-hjn');
        if (remainingImages.length === 0) {
            document.getElementById('existingImagesGallery').style.display = 'none';
        }
    }
}

// Initialize product selector with Select2
function initProductSelector() {
    const productSelect = document.getElementById('products_used');
    if (!productSelect || typeof $ === 'undefined' || !$.fn.select2) {
        console.warn('Product selector or Select2 not available');
        return;
    }

    // Common hair care products
    const products = [
        'Shampoo', 'Conditioner', 'Leave-in Conditioner', 'Hair Oil', 'Hair Serum',
        'Hair Mask', 'Deep Conditioner', 'Hair Butter', 'Hair Cream', 'Hair Lotion',
        'Styling Gel', 'Hair Wax', 'Hair Spray', 'Heat Protectant Spray', 'Hair Mousse',
        'Edge Control', 'Hair Pomade', 'Hair Glue', 'Hair Extensions', 'Hair Weave',
        'Hair Color', 'Hair Bleach', 'Hair Toner', 'Hair Developer', 'Hair Relaxer',
        'Hair Perm Solution', 'Protective Hairstyle Product', 'Scalp Oil', 'Scalp Scrub',
        'Hair Supplements', 'Vitamins for Hair', 'Protein Treatment', 'Hair Growth Oil',
        'Anti-Dandruff Shampoo', 'Moisturizing Shampoo', 'Clarifying Shampoo'
    ];

    // Create options
    products.forEach(product => {
        const option = document.createElement('option');
        option.value = product;
        option.textContent = product;
        productSelect.appendChild(option);
    });

    // Initialize Select2
    $(productSelect).select2({
        placeholder: 'Select products you used...',
        allowClear: true,
        multiple: true,
        tags: true, // Allow custom entries
        tokenSeparators: [',', ';'],
        createTag: function (params) {
            return {
                id: params.term,
                text: params.term,
                newOption: true
            };
        }
    });
}

// Populate goal form with data for editing
function populateGoalForm(goal) {
    console.log('Populating goal form:', goal);

    // Set hidden goal ID
    const goalIdInput = document.getElementById('goal_id');
    if (goalIdInput) goalIdInput.value = goal.id || '';

    // Set title
    const titleInput = document.getElementById('goal_title');
    if (titleInput) titleInput.value = goal.title || '';

    // Set description
    const descInput = document.getElementById('goal_description');
    if (descInput) descInput.value = goal.description || '';

    // Set start date
    const startDateInput = document.getElementById('goal_start_date');
    if (startDateInput) startDateInput.value = goal.start_date || '';

    // Set end date
    const endDateInput = document.getElementById('goal_end_date');
    if (endDateInput) endDateInput.value = goal.end_date || goal.target_date || '';

    // Set progress
    const progressInput = document.getElementById('goal_progress');
    const progressValue = document.getElementById('goal_progress_value');
    if (progressInput) {
        progressInput.value = goal.progress || 0;
        if (progressValue) progressValue.textContent = (goal.progress || 0) + '%';
    }

    // Set category
    const categoryInput = document.getElementById('goal_category');
    if (categoryInput) categoryInput.value = goal.category || '';

    // Set target
    const targetInput = document.getElementById('goal_target');
    if (targetInput) targetInput.value = goal.target || '';

    // Clear new progress note field
    const newNoteTextarea = document.getElementById('newProgressNote');
    if (newNoteTextarea) {
        newNoteTextarea.value = '';
        const counter = document.getElementById('progress_note_count');
        if (counter) counter.textContent = '0';
    }
}

// Populate routine form with data for editing
function populateRoutineForm(routine) {
    console.log('Populating routine form:', routine);

    // Set hidden routine ID
    const routineIdInput = document.getElementById('routine_id');
    if (routineIdInput) routineIdInput.value = routine.id || routine.index || '';

    // Set title (handle different field names)
    const titleInput = document.getElementById('routine_title');
    if (titleInput) titleInput.value = routine.title || routine.name || '';

    // Set type
    const typeInput = document.getElementById('routine_type');
    if (typeInput) typeInput.value = routine.routine_type || routine.type || '';

    // Set frequency
    const frequencyInput = document.getElementById('routine_frequency');
    if (frequencyInput) frequencyInput.value = routine.frequency || 'daily';

    // Set time - handle different formats
    const timeInput = document.getElementById('routine_time');
    if (timeInput) {
        if (routine.time_of_day) {
            timeInput.value = routine.time_of_day;
        } else if (routine.time) {
            timeInput.value = routine.time;
        } else if (routine.hour !== undefined) {
            timeInput.value = `${String(routine.hour).padStart(2, '0')}:00`;
        }
    }

    // Set duration
    const durationInput = document.getElementById('routine_duration');
    if (durationInput) durationInput.value = routine.duration || '';

    // Set products
    const productsInput = document.getElementById('routine_products');
    if (productsInput) {
        if (Array.isArray(routine.products)) {
            productsInput.value = routine.products.join('\n');
        } else {
            productsInput.value = routine.products || '';
        }
    }

    // Set notes
    const notesInput = document.getElementById('routine_notes');
    if (notesInput) notesInput.value = routine.notes || '';

    // Set steps - handle different data structures
    if (routine.steps || routine.description) {
        const stepsList = document.getElementById('routine_steps_list');
        if (stepsList) {
            let steps = [];

            if (routine.steps) {
                steps = routine.steps;
                if (typeof steps === 'string') {
                    try {
                        steps = JSON.parse(steps);
                    } catch (e) {
                        steps = steps.split(',').map(s => s.trim());
                    }
                }
            } else if (routine.description) {
                // If no steps array, split description by newlines
                steps = routine.description.split('\n').filter(step => step.trim());
            }

            // Clear existing steps
            stepsList.innerHTML = '';

            // Ensure at least one step input
            if (steps.length === 0) {
                steps = [''];
            }

            // Add each step
            steps.forEach((step, index) => {
                const stepText = typeof step === 'object' ? (step.title || step.name || step) : step;
                const stepHTML = `
                    <div class="routine-step-item-hjn" data-step="${index + 1}">
                        <div class="step-number-hjn">${index + 1}</div>
                        <input type="text" name="routine_steps[]" class="form-input-hjn step-input-hjn"
                               placeholder="Describe this step..." value="${stepText}" required>
                        <button type="button" class="btn-remove-step-hjn" onclick="removeRoutineStep(this)"
                                ${steps.length <= 1 ? 'disabled' : ''}>
                            <svg viewBox="0 0 24 24" width="18" height="18">
                                <path fill="currentColor" d="M19,6.41L17.59,5L12,10.59L6.41,5L5,6.41L10.59,12L5,17.59L6.41,19L12,13.41L17.59,19L19,17.59L13.41,12L19,6.41Z"/>
                            </svg>
                        </button>
                    </div>
                `;
                stepsList.insertAdjacentHTML('beforeend', stepHTML);
            });
        }
    }
}

// Initialize create forms when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize forms after a short delay to ensure everything is loaded
    setTimeout(() => {
        initGoalFormElements();
    }, 500);
});

console.log('Create/edit forms JavaScript loaded');

// ===============================================
// TIMELINE FILTER FUNCTIONS
// ===============================================

let timelineCurrentFilter = 'all';

/**
 * Set timeline filter by type
 */
function setTimelineFilter(filterType) {
    timelineCurrentFilter = filterType;
    console.log('Timeline filter set to:', filterType);

    // Update button states
    const filterButtons = document.querySelectorAll('.timeline-filter-btn-hjn[data-filter]');
    filterButtons.forEach(btn => {
        if (btn.dataset.filter === filterType) {
            btn.classList.add('active');
        } else {
            btn.classList.remove('active');
        }
    });

    applyTimelineFilters();
}

/**
 * Toggle timeline advanced filter panel
 */
function toggleTimelineFilterPanel() {
    const panel = document.getElementById('timelineFiltersPanel');
    if (!panel) return;

    if (panel.style.display === 'none' || !panel.style.display) {
        panel.style.display = 'block';
    } else {
        panel.style.display = 'none';
    }
}

/**
 * Apply timeline filters
 */
function applyTimelineFilters() {
    const searchInput = document.getElementById('timelineSearchInput');
    const filterRating = document.getElementById('timelineFilterRating');

    const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
    const minRating = filterRating ? parseInt(filterRating.value) : 0;

    console.log('Applying timeline filters:', { filter: timelineCurrentFilter, search: searchTerm, minRating });

    // Get all timeline month groups
    const monthGroups = document.querySelectorAll('.timeline-month-group-hjn');

    monthGroups.forEach(monthGroup => {
        const items = monthGroup.querySelectorAll('.timeline-item-hjn');
        let visibleCount = 0;

        items.forEach(item => {
            const type = item.dataset.type || 'entry';
            const title = item.querySelector('.timeline-item-title-hjn')?.textContent.toLowerCase() || '';
            const description = item.querySelector('.timeline-item-description-hjn')?.textContent.toLowerCase() || '';
            const ratingStars = item.querySelectorAll('.timeline-rating-star-hjn.filled');
            const itemRating = ratingStars.length;

            // Check type filter
            let matchesType = timelineCurrentFilter === 'all' || type === timelineCurrentFilter;

            // Check search
            let matchesSearch = !searchTerm || title.includes(searchTerm) || description.includes(searchTerm);

            // Check rating (only for entries)
            let matchesRating = type !== 'entry' || minRating === 0 || itemRating >= minRating;

            const isVisible = matchesType && matchesSearch && matchesRating;

            item.style.display = isVisible ? 'flex' : 'none';
            if (isVisible) visibleCount++;
        });

        // Hide month group if no visible items
        monthGroup.style.display = visibleCount > 0 ? 'block' : 'none';
    });

    console.log('Timeline filters applied');
}

/**
 * Clear timeline filters
 */
function clearTimelineFilters() {
    const searchInput = document.getElementById('timelineSearchInput');
    const filterRating = document.getElementById('timelineFilterRating');

    if (searchInput) searchInput.value = '';
    if (filterRating) filterRating.value = '0';

    setTimelineFilter('all');

    console.log('Timeline filters cleared');
}

// ===============================================
// COMPARE ANALYSIS FUNCTIONS
// ===============================================

/**
 * Open compare analysis modal and populate dropdowns
 */
function openCompareModal() {
    const modal = document.getElementById('compareAnalysisModal');
    if (!modal) {
        console.error('Compare modal not found');
        return;
    }

    // Gather all analyses from the sidebar
    const analyses = [];

    // Get from Splide slider
    const splideSlides = document.querySelectorAll('.analysis-slide');
    splideSlides.forEach((slide, index) => {
        const dateEl = slide.querySelector('.analysis-slide-date');
        const healthEl = slide.querySelector('.analysis-metric .metric-value');
        const date = dateEl ? dateEl.textContent.trim() : `Analysis ${index + 1}`;
        const health = healthEl ? healthEl.textContent.replace('%', '') : '--';

        analyses.push({
            index: index,
            date: date,
            label: `${date} (Health: ${health}%)`,
            element: slide
        });
    });

    // Populate dropdown menus
    const select1 = document.getElementById('compareAnalysis1');
    const select2 = document.getElementById('compareAnalysis2');

    if (!select1 || !select2) {
        console.error('Compare dropdowns not found');
        return;
    }

    // Clear existing options
    select1.innerHTML = '<option value="">Select an analysis...</option>';
    select2.innerHTML = '<option value="">Select an analysis...</option>';

    // Add analysis options
    analyses.forEach((analysis, idx) => {
        const option1 = document.createElement('option');
        option1.value = idx;
        option1.textContent = analysis.label;
        select1.appendChild(option1);

        const option2 = document.createElement('option');
        option2.value = idx;
        option2.textContent = analysis.label;
        select2.appendChild(option2);
    });

    // Store analyses data for later use
    window.availableAnalyses = analyses;

    // Show modal
    modal.classList.add('active');
    console.log('Compare modal opened with', analyses.length, 'analyses');
}

/**
 * Close compare analysis modal
 */
function closeCompareModal() {
    const modal = document.getElementById('compareAnalysisModal');
    if (modal) {
        modal.classList.remove('active');
    }

    // Reset comparison results
    const resultsDiv = document.getElementById('comparisonResults');
    if (resultsDiv) {
        resultsDiv.style.display = 'none';
        resultsDiv.innerHTML = '';
    }
}

/**
 * Generate comparison between two analyses
 */
function generateComparison() {
    const select1 = document.getElementById('compareAnalysis1');
    const select2 = document.getElementById('compareAnalysis2');

    if (!select1 || !select2) return;

    const index1 = parseInt(select1.value);
    const index2 = parseInt(select2.value);

    if (isNaN(index1) || isNaN(index2)) {
        alert('Please select two analyses to compare');
        return;
    }

    if (index1 === index2) {
        alert('Please select two different analyses');
        return;
    }

    const analyses = window.availableAnalyses || [];
    const analysis1Element = analyses[index1]?.element;
    const analysis2Element = analyses[index2]?.element;

    if (!analysis1Element || !analysis2Element) {
        alert('Error loading analysis data');
        return;
    }

    // Extract data from both analyses
    const data1 = extractAnalysisData(analysis1Element, analyses[index1].date);
    const data2 = extractAnalysisData(analysis2Element, analyses[index2].date);

    // Generate comparison HTML
    displayComparison(data1, data2);
}

/**
 * Extract analysis data from slide element
 */
function extractAnalysisData(slideElement, date) {
    const metrics = slideElement.querySelectorAll('.analysis-metric');
    const data = {
        date: date,
        health: '--',
        hydration: '--',
        elasticity: '--',
        type: '--',
        curlPattern: '--'
    };

    // Extract metrics
    metrics.forEach(metric => {
        const label = metric.querySelector('.metric-label')?.textContent.trim().toLowerCase();
        const value = metric.querySelector('.metric-value')?.textContent.trim().replace('%', '');

        if (label && value) {
            if (label.includes('health')) data.health = value;
            else if (label.includes('hydration')) data.hydration = value;
            else if (label.includes('elasticity')) data.elasticity = value;
        }
    });

    // Extract hair type info
    const typeInfo = slideElement.querySelector('.hair-type-info h3');
    const typeDesc = slideElement.querySelector('.hair-type-info p');
    if (typeInfo) data.curlPattern = typeInfo.textContent.trim();
    if (typeDesc) data.type = typeDesc.textContent.trim();

    return data;
}

/**
 * Display comparison results
 */
function displayComparison(data1, data2) {
    const resultsDiv = document.getElementById('comparisonResults');
    if (!resultsDiv) return;

    // Calculate differences
    const healthDiff = calculateDiff(data1.health, data2.health);
    const hydrationDiff = calculateDiff(data1.hydration, data2.hydration);
    const elasticityDiff = calculateDiff(data1.elasticity, data2.elasticity);

    // Generate comparison HTML
    const html = `
        <h3 style="font-family: 'Archivo Black', sans-serif; color: var(--myavana-onyx); margin-bottom: 1.5rem; text-align: center;">
            Comparison Results
        </h3>
        <div style="display: grid; grid-template-columns: 1fr auto 1fr; gap: 2rem; margin-bottom: 2rem;">
            <div style="text-align: center;">
                <h4 style="font-family: 'Archivo', sans-serif; font-weight: 600; color: var(--myavana-blueberry); margin-bottom: 1rem;">
                    ${data1.date}
                </h4>
                <div style="font-size: 0.875rem; color: var(--myavana-onyx);">
                    <div style="margin-bottom: 0.5rem;"><strong>${data1.curlPattern}</strong></div>
                    <div style="opacity: 0.7;">${data1.type}</div>
                </div>
            </div>
            <div style="display: flex; align-items: center;">
                <svg viewBox="0 0 24 24" width="24" height="24" style="fill: var(--myavana-coral);">
                    <path d="M13.172 12l-4.95-4.95 1.414-1.414L16 12l-6.364 6.364-1.414-1.414z"/>
                </svg>
            </div>
            <div style="text-align: center;">
                <h4 style="font-family: 'Archivo', sans-serif; font-weight: 600; color: var(--myavana-blueberry); margin-bottom: 1rem;">
                    ${data2.date}
                </h4>
                <div style="font-size: 0.875rem; color: var(--myavana-onyx);">
                    <div style="margin-bottom: 0.5rem;"><strong>${data2.curlPattern}</strong></div>
                    <div style="opacity: 0.7;">${data2.type}</div>
                </div>
            </div>
        </div>

        <div style="background: var(--myavana-stone); border-radius: 12px; padding: 1.5rem;">
            <h4 style="font-family: 'Archivo', sans-serif; font-weight: 600; color: var(--myavana-onyx); margin-bottom: 1rem;">
                Metric Comparison
            </h4>

            ${generateMetricRow('Health Score', data1.health, data2.health, healthDiff)}
            ${generateMetricRow('Hydration', data1.hydration, data2.hydration, hydrationDiff)}
            ${generateMetricRow('Elasticity', data1.elasticity, data2.elasticity, elasticityDiff)}
        </div>

        <div style="margin-top: 1.5rem; padding: 1rem; background: ${healthDiff.isPositive ? 'rgba(231, 166, 144, 0.1)' : 'rgba(74, 77, 104, 0.1)'}; border-radius: 8px; border-left: 4px solid ${healthDiff.isPositive ? 'var(--myavana-coral)' : 'var(--myavana-blueberry)'};">
            <p style="font-family: 'Archivo', sans-serif; color: var(--myavana-onyx); margin: 0;">
                <strong>Overall Progress:</strong> ${generateInsight(healthDiff, hydrationDiff, elasticityDiff)}
            </p>
        </div>
    `;

    resultsDiv.innerHTML = html;
    resultsDiv.style.display = 'block';
}

/**
 * Calculate difference between two values
 */
function calculateDiff(val1, val2) {
    const num1 = parseFloat(val1);
    const num2 = parseFloat(val2);

    if (isNaN(num1) || isNaN(num2)) {
        return { diff: 0, percent: 0, isPositive: false, hasData: false };
    }

    const diff = num2 - num1;
    const percent = num1 > 0 ? ((diff / num1) * 100).toFixed(1) : 0;

    return {
        diff: diff.toFixed(1),
        percent: percent,
        isPositive: diff > 0,
        hasData: true
    };
}

/**
 * Generate metric comparison row HTML
 */
function generateMetricRow(label, val1, val2, diffData) {
    const arrow = diffData.isPositive ? '↑' : (diffData.diff < 0 ? '↓' : '→');
    const color = diffData.isPositive ? 'var(--myavana-coral)' : (diffData.diff < 0 ? 'var(--myavana-blueberry)' : '#888');

    return `
        <div style="display: grid; grid-template-columns: 150px 1fr 80px 1fr; gap: 1rem; align-items: center; padding: 1rem 0; border-bottom: 1px solid rgba(0,0,0,0.05);">
            <div style="font-family: 'Archivo', sans-serif; font-weight: 600; color: var(--myavana-onyx);">
                ${label}
            </div>
            <div style="text-align: center; font-size: 1.25rem; font-weight: 600; color: var(--myavana-blueberry);">
                ${val1}${val1 !== '--' ? '%' : ''}
            </div>
            <div style="text-align: center; font-size: 1.5rem; color: ${color};">
                ${arrow} ${diffData.hasData ? Math.abs(diffData.diff) + '%' : ''}
            </div>
            <div style="text-align: center; font-size: 1.25rem; font-weight: 600; color: var(--myavana-coral);">
                ${val2}${val2 !== '--' ? '%' : ''}
            </div>
        </div>
    `;
}

/**
 * Generate insight text based on diffs
 */
function generateInsight(healthDiff, hydrationDiff, elasticityDiff) {
    if (!healthDiff.hasData) {
        return "Insufficient data for detailed comparison.";
    }

    const improvements = [];
    if (healthDiff.isPositive) improvements.push(`health improved by ${healthDiff.diff}%`);
    if (hydrationDiff.isPositive) improvements.push(`hydration improved by ${hydrationDiff.diff}%`);
    if (elasticityDiff.isPositive) improvements.push(`elasticity improved by ${elasticityDiff.diff}%`);

    if (improvements.length === 0) {
        return "Your metrics have remained stable or decreased. Consider adjusting your routine for better results.";
    } else if (improvements.length === 1) {
        return `Great progress! Your ${improvements[0]}.`;
    } else {
        return `Excellent progress! Your ${improvements.join(', ')}.`;
    }
}

console.log('Compare analysis functions loaded');
