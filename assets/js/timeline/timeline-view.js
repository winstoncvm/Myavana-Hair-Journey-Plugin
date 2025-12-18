/**
 * MYAVANA Timeline View Module
 *
 * Handles viewing entry, goal, and routine details in offcanvas panels.
 * Largest view module with comprehensive display functionality.
 *
 * @package MyavanaHairJourney
 * @subpackage Timeline
 */

(function() {
    'use strict';

    // Ensure namespace exists
    window.MyavanaTimeline = window.MyavanaTimeline || {};

    /**
     * View Module
     * Handles displaying entries, goals, and routines in view offcanvas
     */
    MyavanaTimeline.View = (function() {

        /**
         * Open view offcanvas for viewing details
         *
         * @param {string} type - Type of item ('entry', 'goal', 'routine')
         * @param {number|string} id - ID or index of item to view
         */
        function openView(type, id) {
            console.log('Opening view offcanvas:', type, id);

            // Map type to offcanvas ID
            const offcanvasMap = {
                'entry': 'entryViewOffcanvas',
                'goal': 'goalViewOffcanvas',
                'routine': 'routineViewOffcanvas'
            };

            const currentViewOffcanvas = document.getElementById(offcanvasMap[type]);
            const overlay = document.getElementById('viewOffcanvasOverlay');

            if (!currentViewOffcanvas || !overlay) {
                console.error('View offcanvas elements not found');
                return;
            }

            // Store in state
            MyavanaTimeline.State.set('currentViewOffcanvas', currentViewOffcanvas);

            // Show offcanvas and overlay
            overlay.classList.add('active');
            currentViewOffcanvas.classList.add('active');

            // Load data based on type
            switch(type) {
                case 'entry':
                    loadEntry(id);
                    break;
                case 'goal':
                    loadGoal(id);
                    break;
                case 'routine':
                    loadRoutine(id);
                    break;
            }

            // Prevent body scroll
            document.body.style.overflow = 'hidden';
        }

        /**
         * Load Entry View
         *
         * @param {number|string} entryId - Entry ID to load
         */
        function loadEntry(entryId) {
            console.log('[loadEntry] Loading entry with ID:', entryId);

            const body = document.getElementById('entryViewBody');
            if (!body) return;

            const loadingEl = body.querySelector('.view-loading-hjn');
            const contentEl = body.querySelector('.view-content-hjn');

            // Show loading
            if (loadingEl) loadingEl.style.display = 'flex';
            if (contentEl) contentEl.style.display = 'none';

            // FIRST: Try to get entry from pre-loaded cache (avoids AJAX issues)
            if (window.myavanaEntryCache && window.myavanaEntryCache[entryId]) {
                console.log('[loadEntry] Found entry in cache:', entryId);
                setTimeout(() => {
                    populateEntryView(window.myavanaEntryCache[entryId]);
                }, 100); // Small delay for smooth UX
                return;
            }

            console.log('[loadEntry] Entry not in cache, attempting AJAX fallback');

            // FALLBACK: Fetch entry data via AJAX (may fail with nonce issues)
            const settings = window.myavanaTimelineSettings || window.myavanaTimeline || window.myavanaTimelineInstance || {};

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

        /**
         * Populate Entry View with data
         *
         * @param {Object} entry - Entry data object
         */
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
            if (titleEl) titleEl.textContent = entry.title || entry.entry_title || 'Untitled Entry';

            // Populate date
            const dateEl = document.getElementById('entryDate');
            if (dateEl) dateEl.textContent = entry.entry_date ? new Date(entry.entry_date).toLocaleDateString() : '';

            // Populate gallery - FIXED: Handle gallery correctly
            populateEntryGallery(entry);

            // Populate rating - FIXED: 5 stars instead of 10
            if (entry.rating) {
                const ratingSection = document.getElementById('entryRatingSection');
                const ratingStars = document.getElementById('entryRatingStars');
                const ratingValue = document.getElementById('entryRatingValue');

                if (ratingSection) ratingSection.style.display = 'block';

                if (ratingStars) {
                    const ratingNum = parseInt(entry.rating) || 0;
                    const starsHTML = Array.from({length: 5}, (_, i) => {
                        const filled = i < ratingNum;
                        return `<svg class="rating-star-hjn ${filled ? '' : 'empty'}" viewBox="0 0 24 24"><path fill="currentColor" d="M12,17.27L18.18,21L16.54,13.97L22,9.24L14.81,8.62L12,2L9.19,8.62L2,9.24L7.45,13.97L5.82,21L12,17.27Z"/></svg>`;
                    }).join('');
                    ratingStars.innerHTML = starsHTML;
                }

                if (ratingValue) {
                    const ratingNum = parseInt(entry.rating) || 0;
                    ratingValue.textContent = `${ratingNum}/5`;
                }
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
            handleProducts(entry);

            // Populate AI analysis
            if (entry.ai_analysis) {
                const aiSection = document.getElementById('entryAISection');
                const aiEl = document.getElementById('entryAI');

                if (aiSection) aiSection.style.display = 'block';
                if (aiEl) aiEl.textContent = entry.ai_analysis;
            }

            // Store data for edit functionality (handle both id and entry_id)
            const currentEntryId = entry.id || entry.entry_id || entryId;
            console.log('[loadEntry] Storing currentViewData with ID:', currentEntryId, 'Full entry:', entry);
            MyavanaTimeline.State.set('currentViewData', { 
                type: 'entry', 
                id: currentEntryId, 
                entry_id: currentEntryId, 
                data: entry 
            });
        }

        /**
         * Populate entry gallery correctly
         *
         * @param {Object} entry - Entry data object
         */
        function populateEntryGallery(entry) {
            const galleryEl = document.getElementById('entryGallery');
            if (!galleryEl) return;

            // Get all images from entry
            const images = [];
            
            // Check for gallery images first
            if (entry.images && Array.isArray(entry.images)) {
                entry.images.forEach(img => {
                    if (img && (img.url || img.thumbnail || img.src)) {
                        images.push(img.url || img.thumbnail || img.src);
                    }
                });
            }
            
            // Check for single image field
            if (entry.image && !images.includes(entry.image)) {
                images.push(entry.image);
            }
            
            // Check for image_url field
            if (entry.image_url && !images.includes(entry.image_url)) {
                images.push(entry.image_url);
            }

            console.log('[populateEntryGallery] Found images:', images);

            // Display gallery
            if (images.length === 0) {
                galleryEl.style.display = 'none';
                galleryEl.innerHTML = '';
            } else if (images.length === 1) {
                galleryEl.innerHTML = `
                    <div class="view-gallery-grid-hjn">
                        <div class="view-gallery-item-hjn">
                            <img src="${images[0]}" alt="${entry.title || 'Entry image'}" class="view-gallery-img-hjn" onclick="MyavanaTimeline.View.openImageOverlay('${images[0]}')" />
                        </div>
                    </div>
                `;
                galleryEl.style.display = 'block';
            } else {
                const gridHTML = images.map(imgUrl => `
                    <div class="view-gallery-item-hjn">
                        <img src="${imgUrl}" alt="${entry.title || 'Entry image'}" class="view-gallery-img-hjn" onclick="MyavanaTimeline.View.openImageOverlay('${imgUrl}')" />
                    </div>
                `).join('');
                
                galleryEl.innerHTML = `<div class="view-gallery-grid-hjn">${gridHTML}</div>`;
                galleryEl.style.display = 'block';
            }
        }

        /**
         * Open image overlay for full-screen viewing
         *
         * @param {string} imageUrl - Image URL to display
         */
        function openImageOverlay(imageUrl) {
            let overlay = document.getElementById('imageOverlayHjn');
            if (!overlay) {
                overlay = document.createElement('div');
                overlay.id = 'imageOverlayHjn';
                overlay.className = 'image-overlay-hjn';
                overlay.innerHTML = `
                    <div class="image-overlay-content-hjn">
                        <button class="image-overlay-close-hjn" onclick="MyavanaTimeline.View.closeImageOverlay()">&times;</button>
                        <img src="${imageUrl}" alt="Full size view" class="image-overlay-img-hjn" />
                    </div>
                `;
                document.body.appendChild(overlay);
                
                // Close on background click
                overlay.addEventListener('click', function(e) {
                    if (e.target === overlay) {
                        closeImageOverlay();
                    }
                });
            } else {
                overlay.querySelector('.image-overlay-img-hjn').src = imageUrl;
            }
            
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        /**
         * Close image overlay
         */
        function closeImageOverlay() {
            const overlay = document.getElementById('imageOverlayHjn');
            if (overlay) {
                overlay.classList.remove('active');
            }
            document.body.style.overflow = '';
        }

        /**
         * Handle products display
         *
         * @param {Object} entry - Entry data object
         */
        function handleProducts(entry) {
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
        }

        /**
         * Load Goal View
         *
         * @param {number|string} goalIndex - Goal index to load
         */
        function loadGoal(goalIndex) {
            console.log('[loadGoal] Loading goal with index:', goalIndex);
            const body = document.getElementById('goalViewBody');
            if (!body) return;

            // For goals, get data from the list item
            const listItem = document.querySelector(`[data-goal-index="${goalIndex}"]`);
            if (!listItem) {
                showViewError('Goal not found');
                return;
            }

            const loadingEl = body.querySelector('.view-loading-hjn');
            const contentEl = body.querySelector('.view-content-hjn');

            // Show loading
            if (loadingEl) loadingEl.style.display = 'flex';
            if (contentEl) contentEl.style.display = 'none';

            // Extract data from list item - THIS IS THE KEY: extract the real database ID
            setTimeout(() => {
                const goalData = extractGoalData(listItem);
                console.log('[loadGoal] Extracted goal data:', goalData);
                populateGoalView(goalData);
            }, 300);
        }

        /**
         * Extract goal data from list item
         *
         * @param {HTMLElement} listItem - List item element
         * @returns {Object} Goal data object
         */
        function extractGoalData(listItem) {
            const title = listItem.querySelector('.list-item-title-hjn')?.textContent || 'Untitled Goal';
            const dateRange = listItem.querySelector('.list-item-date-hjn')?.textContent || '';
            const progressText = listItem.querySelector('.list-item-badge-hjn')?.textContent || '0%';
            const progress = parseInt(progressText) || 0;
            const description = listItem.querySelector('.list-item-description-hjn')?.textContent || '';

            // Extract goal ID from data attribute or goal index
            const goalId = listItem.getAttribute('data-goal-id') ||
                          listItem.getAttribute('data-id') ||
                          listItem.getAttribute('data-goal-index');

            console.log('[extractGoalData] Raw goalId from attribute:', goalId);
            console.log('[extractGoalData] data-goal-id:', listItem.getAttribute('data-goal-id'));
            console.log('[extractGoalData] data-id:', listItem.getAttribute('data-id'));
            console.log('[extractGoalData] data-goal-index:', listItem.getAttribute('data-goal-index'));
            console.log('[extractGoalData] listItem:', listItem);

            const parsedId = goalId ? parseInt(goalId) : null;
            console.log('[extractGoalData] Parsed goal ID:', parsedId);

            return {
                id: parsedId,
                goal_id: parsedId,
                title,
                dateRange,
                progress,
                description,
                milestones: [] // Would come from server in real implementation
            };
        }

        /**
         * Populate Goal View
         *
         * @param {Object} goal - Goal data object
         */
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
            console.log('[populateGoalView] Storing state with goal:', goal);
            console.log('[populateGoalView] goal.id:', goal.id);
            console.log('[populateGoalView] goal.goal_id:', goal.goal_id);

            const stateData = {
                type: 'goal',
                id: goal.id,
                goal_id: goal.goal_id || goal.id,
                data: goal
            };

            console.log('[populateGoalView] State data to store:', stateData);
            MyavanaTimeline.State.set('currentViewData', stateData);

            // Verify it was stored
            const storedData = MyavanaTimeline.State.get('currentViewData');
            console.log('[populateGoalView] Verified stored state:', storedData);
        }

        /**
         * Populate goal progress history timeline
         *
         * @param {Array} history - Array of progress history entries
         */
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

        /**
         * Populate progress notes in goal edit form
         *
         * @param {Array} notes - Array of progress notes
         */
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

        /**
         * Initialize character counter for progress note textarea
         *
         * @param {HTMLElement} textarea - Textarea element
         */
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

        /**
         * Populate goal progress notes
         *
         * @param {Array} notes - Array of progress notes
         */
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

        /**
         * Load Routine View
         *
         * @param {number|string} routineId - Routine ID to load
         */
        function loadRoutine(routineId) {
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

        /**
         * Extract routine data from list item
         *
         * @param {HTMLElement} listItem - List item element
         * @returns {Object} Routine data object
         */
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

        /**
         * Populate Routine View
         *
         * @param {Object} routine - Routine data object
         */
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
            MyavanaTimeline.State.set('currentViewData', { type: 'routine', id: routine.id, data: routine });
            console.log('Routine view populated successfully');
        }

        /**
         * Show error in view offcanvas
         *
         * @param {string} message - Error message to display
         */
        function showViewError(message) {
            const currentViewOffcanvas = MyavanaTimeline.State.get('currentViewOffcanvas');
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

        /**
         * Edit Entry - Opens edit offcanvas
         */
        function editEntry() {
            const currentViewData = MyavanaTimeline.State.get('currentViewData');
            console.log('[editEntry] Current view data:', currentViewData);

            if (!currentViewData || currentViewData.type !== 'entry') {
                console.error('[editEntry] Invalid or missing currentViewData');
                return;
            }

            // IMPORTANT: Extract entry ID BEFORE closing offcanvas
            // because closeTimelineViewOffcanvas() clears currentViewData after 400ms
            const entryId = currentViewData.id ||
                           currentViewData.entry_id ||
                           (currentViewData.data && (currentViewData.data.id || currentViewData.data.entry_id));

            console.log('[editEntry] Extracted entry ID:', entryId);

            if (!entryId) {
                console.error('[editEntry] No entry ID found in currentViewData:', currentViewData);
                alert('Could not find entry ID. Please try again.');
                return;
            }

            // Close view offcanvas (this will clear state after 400ms)
            if (typeof closeTimelineViewOffcanvas === 'function') {
                closeTimelineViewOffcanvas();
            } else if (MyavanaTimeline.Offcanvas && MyavanaTimeline.Offcanvas.closeView) {
                MyavanaTimeline.Offcanvas.closeView();
            }

            // Use NEW form system with the extracted ID
            if (MyavanaTimeline.EntryForm && MyavanaTimeline.EntryForm.edit) {
                console.log('[editEntry] Calling new form system with ID:', entryId);
                MyavanaTimeline.EntryForm.edit(entryId);
            } else {
                console.error('[editEntry] New form system not available');
                alert('Form system not loaded. Please refresh the page.');
            }
        }

        /**
         * Delete Entry - Deletes current entry
         */
        function deleteEntry() {
            const currentViewData = MyavanaTimeline.State.get('currentViewData');
            console.log('[deleteEntry] Current view data:', currentViewData);

            if (!currentViewData || currentViewData.type !== 'entry') {
                console.error('[deleteEntry] Invalid or missing currentViewData');
                return;
            }

            const entryId = currentViewData.id ||
                           currentViewData.entry_id ||
                           (currentViewData.data && (currentViewData.data.id || currentViewData.data.entry_id));

            console.log('[deleteEntry] Deleting entry ID:', entryId);

            if (!entryId) {
                alert('Could not find entry ID to delete.');
                return;
            }

            if (!confirm('Are you sure you want to delete this entry? This action cannot be undone.')) {
                return;
            }

            const settings = window.myavanaTimelineSettings || window.myavanaTimeline || window.myavanaTimelineInstance || {};

            // Show loading on delete button
            const deleteBtn = document.getElementById('deleteEntryBtn');
            if (deleteBtn) {
                const originalText = deleteBtn.innerHTML;
                deleteBtn.innerHTML = '<div class="loading-spinner-hjn small"></div> Deleting...';
                deleteBtn.disabled = true;
            }

            fetch(settings.ajaxUrl || settings.ajaxurl || '/wp-admin/admin-ajax.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'myavana_delete_entry',
                    security: settings.deleteEntryNonce || settings.nonce || '',
                    entry_id: entryId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    const notification = document.createElement('div');
                    notification.className = 'myavana-notification success';
                    notification.innerHTML = `
                        <svg viewBox="0 0 24 24" width="20" height="20">
                            <path fill="currentColor" d="M12,2C6.48,2 2,6.48 2,12C2,17.52 6.48,22 12,22C17.52,22 22,17.52 22,12C22,6.48 17.52,2 12,2ZM10,17L5,12L6.41,10.59L10,14.17L17.59,6.58L19,8L10,17Z"/>
                        </svg>
                        Entry deleted successfully!
                    `;
                    document.body.appendChild(notification);
                    
                    // Remove notification after 3 seconds
                    setTimeout(() => {
                        notification.classList.add('fade-out');
                        setTimeout(() => notification.remove(), 300);
                    }, 3000);
                    
                    // Close view offcanvas
                    if (typeof closeTimelineViewOffcanvas === 'function') {
                        closeTimelineViewOffcanvas();
                    }
                    
                    // Reload the timeline to show updated list
                    setTimeout(() => {
                        if (window.MyavanaTimeline && window.MyavanaTimeline.Timeline && window.MyavanaTimeline.Timeline.refresh) {
                            window.MyavanaTimeline.Timeline.refresh();
                        } else {
                            location.reload();
                        }
                    }, 500);
                } else {
                    alert(data.data || 'Failed to delete entry');
                    // Reset delete button
                    if (deleteBtn) {
                        deleteBtn.innerHTML = originalText;
                        deleteBtn.disabled = false;
                    }
                }
            })
            .catch(error => {
                console.error('Error deleting entry:', error);
                alert('Network error. Please try again.');
                // Reset delete button
                if (deleteBtn) {
                    deleteBtn.innerHTML = originalText;
                    deleteBtn.disabled = false;
                }
            });
        }

        /**
         * Edit Goal - Opens edit offcanvas
         */
        function editGoal() {
            const currentViewData = MyavanaTimeline.State.get('currentViewData');
            console.log('[editGoal] Current view data:', currentViewData);

            if (!currentViewData || currentViewData.type !== 'goal') {
                console.error('[editGoal] Invalid or missing currentViewData');
                return;
            }

            // IMPORTANT: Extract goal ID BEFORE closing offcanvas
            const goalId = currentViewData.id || currentViewData.goal_id || (currentViewData.data && currentViewData.data.id);
            console.log('[editGoal] Extracted goal ID:', goalId);

            if (!goalId) {
                console.error('[editGoal] No goal ID found in currentViewData:', currentViewData);
                alert('Could not find goal ID. Please try again.');
                return;
            }

            // Close view offcanvas (this will clear state after 400ms)
            if (typeof closeTimelineViewOffcanvas === 'function') {
                closeTimelineViewOffcanvas();
            } else if (MyavanaTimeline.Offcanvas && MyavanaTimeline.Offcanvas.closeView) {
                MyavanaTimeline.Offcanvas.closeView();
            }

            // Use NEW form system with the extracted ID
            if (MyavanaTimeline.GoalForm && MyavanaTimeline.GoalForm.edit) {
                MyavanaTimeline.GoalForm.edit(goalId);
            } else {
                console.error('[editGoal] New form system not available');
                alert('Form system not loaded. Please refresh the page.');
            }
        }

        /**
         * Edit Routine - Opens edit offcanvas
         */
        function editRoutine() {
            const currentViewData = MyavanaTimeline.State.get('currentViewData');
            console.log('[editRoutine] Current view data:', currentViewData);

            if (!currentViewData || currentViewData.type !== 'routine') {
                console.error('[editRoutine] Invalid or missing currentViewData');
                return;
            }

            // IMPORTANT: Extract routine ID BEFORE closing offcanvas
            const routineId = currentViewData.id || currentViewData.routine_id || (currentViewData.data && currentViewData.data.id);
            console.log('[editRoutine] Extracted routine ID:', routineId);

            if (!routineId) {
                console.error('[editRoutine] No routine ID found in currentViewData:', currentViewData);
                alert('Could not find routine ID. Please try again.');
                return;
            }

            // Close view offcanvas (this will clear state after 400ms)
            if (typeof closeTimelineViewOffcanvas === 'function') {
                closeTimelineViewOffcanvas();
            } else if (MyavanaTimeline.Offcanvas && MyavanaTimeline.Offcanvas.closeView) {
                MyavanaTimeline.Offcanvas.closeView();
            }

            // Use NEW form system with the extracted ID
            if (MyavanaTimeline.RoutineForm && MyavanaTimeline.RoutineForm.edit) {
                MyavanaTimeline.RoutineForm.edit(routineId);
            } else {
                console.error('[editRoutine] New form system not available');
                alert('Form system not loaded. Please refresh the page.');
            }
        }

        // Public API
        return {
            openView: openView,
            loadEntry: loadEntry,
            loadGoal: loadGoal,
            loadRoutine: loadRoutine,
            editEntry: editEntry,
            deleteEntry: deleteEntry,
            editGoal: editGoal,
            editRoutine: editRoutine,
            initProgressNoteCounter: initProgressNoteCounter,
            openImageOverlay: openImageOverlay,
            closeImageOverlay: closeImageOverlay
        };

    })();

    // Backward compatibility - expose functions globally
    if (typeof window.openViewOffcanvas === 'undefined') {
        window.openViewOffcanvas = MyavanaTimeline.View.openView;
    }
    if (typeof window.loadEntryView === 'undefined') {
        window.loadEntryView = MyavanaTimeline.View.loadEntry;
    }
    if (typeof window.loadGoalView === 'undefined') {
        window.loadGoalView = MyavanaTimeline.View.loadGoal;
    }
    if (typeof window.loadRoutineView === 'undefined') {
        window.loadRoutineView = MyavanaTimeline.View.loadRoutine;
    }
    // Edit functions with parameter support for new form system
    if (typeof window.editEntry === 'undefined') {
        window.editEntry = function(entryId) {
            console.log('[window.editEntry] Called with ID:', entryId);
            console.log('[window.editEntry] State exists:', !!MyavanaTimeline.State);

            // If no ID provided, try to get from currentViewData
            if (!entryId) {
                const currentViewData = MyavanaTimeline.State ? MyavanaTimeline.State.get('currentViewData') : null;
                console.log('[window.editEntry] currentViewData:', currentViewData);
                if (currentViewData) {
                    entryId = currentViewData.id ||
                             currentViewData.entry_id ||
                             (currentViewData.data && (currentViewData.data.id || currentViewData.data.entry_id));
                }
                console.log('[window.editEntry] Extracted ID from state:', entryId);
            }

            if (!entryId) {
                console.error('[window.editEntry] No entry ID available');
                console.error('[window.editEntry] Dumping state:', MyavanaTimeline.State ? MyavanaTimeline.State.dump() : 'State not available');
                alert('Could not find entry ID. Please try again.');
                return;
            }

            console.log('[window.editEntry] Proceeding with ID:', entryId);

            // Close view offcanvas first (this schedules state cleanup after 400ms)
            if (typeof closeTimelineViewOffcanvas === 'function') {
                closeTimelineViewOffcanvas();
            }

            // Use new form system - pass the extracted ID immediately
            if (MyavanaTimeline.EntryForm && MyavanaTimeline.EntryForm.edit) {
                console.log('[window.editEntry] Calling MyavanaTimeline.EntryForm.edit with ID:', entryId);
                MyavanaTimeline.EntryForm.edit(entryId);
            } else {
                console.error('[window.editEntry] Form system not loaded');
                alert('Form system not loaded. Please refresh the page.');
            }
        };
    }
    // Add delete entry function
    if (typeof window.deleteEntry === 'undefined') {
        window.deleteEntry = MyavanaTimeline.View.deleteEntry;
    }
    if (typeof window.editGoal === 'undefined') {
        window.editGoal = function(goalId) {
            console.log('[window.editGoal] Called with ID:', goalId);

            if (!goalId) {
                const currentViewData = MyavanaTimeline.State ? MyavanaTimeline.State.get('currentViewData') : null;
                if (currentViewData) {
                    goalId = currentViewData.id ||
                            currentViewData.goal_id ||
                            (currentViewData.data && (currentViewData.data.id || currentViewData.data.goal_id));
                }
                console.log('[window.editGoal] Got ID from state:', goalId);
            }

            if (goalId) {
                if (typeof closeTimelineViewOffcanvas === 'function') {
                    closeTimelineViewOffcanvas();
                }

                if (MyavanaTimeline.GoalForm && MyavanaTimeline.GoalForm.edit) {
                    MyavanaTimeline.GoalForm.edit(goalId);
                } else {
                    console.error('[editGoal] Form system not loaded');
                    alert('Form system not loaded. Please refresh the page.');
                }
            } else {
                console.error('[window.editGoal] No goal ID available');
                alert('Could not find goal ID. Please try again.');
            }
        };
    }
    if (typeof window.editRoutine === 'undefined') {
        window.editRoutine = function(routineId) {
            console.log('[window.editRoutine] Called with ID:', routineId);

            if (!routineId) {
                const currentViewData = MyavanaTimeline.State ? MyavanaTimeline.State.get('currentViewData') : null;
                if (currentViewData) {
                    routineId = currentViewData.id ||
                               currentViewData.routine_id ||
                               (currentViewData.data && (currentViewData.data.id || currentViewData.data.routine_id));
                }
                console.log('[window.editRoutine] Got ID from state:', routineId);
            }

            if (routineId) {
                if (typeof closeTimelineViewOffcanvas === 'function') {
                    closeTimelineViewOffcanvas();
                }

                if (MyavanaTimeline.RoutineForm && MyavanaTimeline.RoutineForm.edit) {
                    MyavanaTimeline.RoutineForm.edit(routineId);
                } else {
                    console.error('[editRoutine] Form system not loaded');
                    alert('Form system not loaded. Please refresh the page.');
                }
            } else {
                console.error('[window.editRoutine] No routine ID available');
                alert('Could not find routine ID. Please try again.');
            }
        };
    }

})();