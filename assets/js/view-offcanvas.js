// ===== VIEW OFFCANVAS FUNCTIONALITY =====

let currentViewOffcanvas = null;
let currentViewData = null;

// Open view offcanvas for viewing details
function openViewOffcanvas(type, id) {
    console.log('Opening view offcanvas1:', type, id);

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
function closeViewOffcanvas() {
    console.log('Closing view offcanvas');
    if (!currentViewOffcanvas) return;

    const overlay = document.getElementById('viewOffcanvasOverlay');

    if (overlay) {
        overlay.classList.remove('active');
    }

    currentViewOffcanvas.classList.remove('active');

    // Re-enable body scroll
    document.body.style.overflow = '';

    // Clear data after animation
    setTimeout(() => {
        currentViewOffcanvas = null;
        currentViewData = null;
    }, 400);
}

// Dedicated function for timeline view offcanvas (namespaced to avoid global collisions)
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
            populateEntryForm(data);
            break;
        case 'goal':
            populateGoalForm(data);
            break;
        case 'routine':
            populateRoutineForm(data);
            break;
    }
}

console.log('View Offcanvas JavaScript loaded');