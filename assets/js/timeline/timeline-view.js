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

        function hasValue(value) {
            return value !== undefined && value !== null && value !== '';
        }

        function parseRefValue(rawValue) {
            if (!hasValue(rawValue)) {
                return null;
            }
            const normalized = String(rawValue).trim();
            if (normalized === '') {
                return null;
            }
            return /^-?\d+$/.test(normalized) ? parseInt(normalized, 10) : normalized;
        }

        function valuesMatch(reference, candidate) {
            if (!hasValue(reference) || !hasValue(candidate)) {
                return false;
            }
            const referenceText = String(reference).trim();
            const candidateText = String(candidate).trim();
            if (referenceText === candidateText) {
                return true;
            }
            if (/^-?\d+$/.test(referenceText) && /^-?\d+$/.test(candidateText)) {
                return parseInt(referenceText, 10) === parseInt(candidateText, 10);
            }
            return false;
        }

        function normalizeStringList(value) {
            if (Array.isArray(value)) {
                return value
                    .map(item => typeof item === 'string' ? item : (item && item.name ? item.name : String(item || '')))
                    .map(item => item.trim())
                    .filter(Boolean);
            }
            if (typeof value === 'string') {
                return value
                    .split(/[\n,]/)
                    .map(item => item.trim())
                    .filter(Boolean);
            }
            if (value && typeof value === 'object') {
                return Object.values(value)
                    .map(item => String(item || '').trim())
                    .filter(Boolean);
            }
            return [];
        }

        function escapeHtml(value) {
            return String(value || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function parseJsonSafe(value, fallback) {
            try {
                return JSON.parse(value);
            } catch (e) {
                return fallback;
            }
        }

        function normalizeMediaItems(value, mediaType) {
            const rawItems = [];
            const normalized = [];
            const seenUrls = new Set();

            const queue = item => {
                if (!hasValue(item)) return;
                rawItems.push(item);
            };

            if (Array.isArray(value)) {
                value.forEach(queue);
            } else if (typeof value === 'string') {
                const trimmed = value.trim();
                if (trimmed) {
                    if ((trimmed.startsWith('[') && trimmed.endsWith(']')) || (trimmed.startsWith('{') && trimmed.endsWith('}'))) {
                        const parsed = parseJsonSafe(trimmed, null);
                        if (Array.isArray(parsed)) {
                            parsed.forEach(queue);
                        } else if (parsed && typeof parsed === 'object') {
                            queue(parsed);
                        }
                    } else {
                        trimmed.split(/[\n,]/).map(part => part.trim()).filter(Boolean).forEach(queue);
                    }
                }
            } else if (value && typeof value === 'object') {
                queue(value);
            }

            rawItems.forEach(item => {
                let media = null;

                if (typeof item === 'string') {
                    media = { url: item.trim() };
                } else if (item && typeof item === 'object') {
                    const resolvedUrl = item.url || item.src || item.thumbnail || item.file || '';
                    media = {
                        id: hasValue(item.id) ? item.id : null,
                        url: String(resolvedUrl || '').trim(),
                        thumbnail: String(item.thumbnail || resolvedUrl || '').trim(),
                        mime: item.mime || ''
                    };
                }

                if (!media || !media.url) {
                    return;
                }

                const dedupeKey = media.url;
                if (seenUrls.has(dedupeKey)) {
                    return;
                }
                seenUrls.add(dedupeKey);

                if (mediaType === 'video' && media.mime && !String(media.mime).startsWith('video/')) {
                    return;
                }

                normalized.push(media);
            });

            return normalized;
        }

        function getEntryImageMedia(entry) {
            const aggregated = [];
            const sources = [
                entry.images,
                entry.gallery_images,
                entry.entry_photos,
                entry.photo_gallery,
                entry.image,
                entry.image_url,
                entry.thumbnail
            ];
            sources.forEach(source => {
                normalizeMediaItems(source, 'image').forEach(item => aggregated.push(item));
            });
            return normalizeMediaItems(aggregated, 'image');
        }

        function getEntryVideoMedia(entry) {
            const aggregated = [];
            const sources = [
                entry.videos,
                entry.entry_videos,
                entry.video_urls,
                entry.video,
                entry.video_url
            ];
            sources.forEach(source => {
                normalizeMediaItems(source, 'video').forEach(item => aggregated.push(item));
            });
            return normalizeMediaItems(aggregated, 'video');
        }

        function clampIndex(index, maxLength) {
            const length = Math.max(0, parseInt(maxLength || 0, 10));
            if (length < 1) return 0;
            const parsed = parseInt(index, 10);
            if (Number.isNaN(parsed)) return 0;
            return Math.min(length - 1, Math.max(0, parsed));
        }

        function formatEntryDateText(entry) {
            if (hasValue(entry.date)) {
                return String(entry.date);
            }

            const dateValue = hasValue(entry.entry_date) ? String(entry.entry_date) : '';
            const timeValue = hasValue(entry.entry_time) ? String(entry.entry_time) : '';
            if (!dateValue) {
                return timeValue;
            }

            const dateParts = dateValue.match(/^(\d{4})-(\d{2})-(\d{2})$/);
            let displayDate = dateValue;
            if (dateParts) {
                const parsedDate = new Date(
                    parseInt(dateParts[1], 10),
                    parseInt(dateParts[2], 10) - 1,
                    parseInt(dateParts[3], 10)
                );
                displayDate = parsedDate.toLocaleDateString(undefined, {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric'
                });
            }

            return timeValue ? `${displayDate} ${timeValue}` : displayDate;
        }

        function renderDetailGrid(containerId, sectionId, rows) {
            const sectionEl = document.getElementById(sectionId);
            const containerEl = document.getElementById(containerId);
            if (!sectionEl || !containerEl) return;

            const activeRows = (rows || []).filter(row => hasValue(row.value));
            if (!activeRows.length) {
                sectionEl.style.display = 'none';
                containerEl.innerHTML = '';
                return;
            }

            containerEl.innerHTML = activeRows.map(row => `
                <div class="view-detail-item-hjn">
                    <div class="view-detail-label-hjn">${escapeHtml(row.label)}</div>
                    <div class="view-detail-value-hjn">${escapeHtml(row.value)}</div>
                </div>
            `).join('');
            sectionEl.style.display = 'block';
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

            const cachedEntry = window.myavanaEntryCache && window.myavanaEntryCache[entryId]
                ? window.myavanaEntryCache[entryId]
                : null;

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
                    const payload = Object.assign({}, cachedEntry || {}, data.data || {});
                    if (!hasValue(payload.id)) payload.id = entryId;
                    if (!hasValue(payload.entry_id)) payload.entry_id = payload.id;
                    populateEntryView(payload);
                    return;
                }

                if (cachedEntry) {
                    console.warn('[loadEntry] AJAX details unavailable, using cached entry payload');
                    populateEntryView(cachedEntry);
                } else {
                    console.error('Failed to load entry:', data);
                    showViewError('Failed to load entry details');
                }
            })
            .catch(error => {
                console.error('Error loading entry:', error);
                if (cachedEntry) {
                    console.warn('[loadEntry] Network error, using cached entry payload');
                    populateEntryView(cachedEntry);
                } else {
                    showViewError('Error loading entry details');
                }
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

            resetEntryViewSections();

            // Populate title
            const titleEl = document.getElementById('entryTitle');
            if (titleEl) titleEl.textContent = entry.title || entry.entry_title || 'Untitled Entry';

            // Populate date
            const dateEl = document.getElementById('entryDate');
            if (dateEl) dateEl.textContent = formatEntryDateText(entry);

            // Populate media
            populateEntryGallery(entry);
            populateEntryVideos(entry);

            // Populate rating
            const ratingNum = parseInt(entry.rating || entry.health_rating || 0, 10);
            if (ratingNum > 0) {
                const ratingSection = document.getElementById('entryRatingSection');
                const ratingStars = document.getElementById('entryRatingStars');
                const ratingValue = document.getElementById('entryRatingValue');

                if (ratingSection) ratingSection.style.display = 'block';

                if (ratingStars) {
                    const starsHTML = Array.from({length: 5}, (_, i) => {
                        const filled = i < ratingNum;
                        return `<svg class="rating-star-hjn ${filled ? '' : 'empty'}" viewBox="0 0 24 24"><path fill="currentColor" d="M12,17.27L18.18,21L16.54,13.97L22,9.24L14.81,8.62L12,2L9.19,8.62L2,9.24L7.45,13.97L5.82,21L12,17.27Z"/></svg>`;
                    }).join('');
                    ratingStars.innerHTML = starsHTML;
                }

                if (ratingValue) {
                    ratingValue.textContent = `${ratingNum}/5`;
                }
            }

            // Populate content
            const contentTextEl = document.getElementById('entryContent');
            if (contentTextEl) {
                contentTextEl.textContent = entry.content || entry.description || 'No description provided.';
            }

            // Populate mood
            const mood = entry.mood || entry.mood_demeanor || '';
            if (mood) {
                const moodSection = document.getElementById('entryMoodSection');
                const moodEl = document.getElementById('entryMood');

                if (moodSection) moodSection.style.display = 'block';
                if (moodEl) moodEl.textContent = mood;
            }

            // Populate products (server may return array, comma-separated string, or empty)
            handleProducts(entry);
            populateEntryDetails(entry);
            populateEntryFollowUp(entry);

            // Populate AI analysis
            const aiSummary = entry.ai_analysis ||
                (entry.analysis_data && entry.analysis_data.summary) ||
                (entry.analysis_data && entry.analysis_data.overall_assessment) ||
                '';

            if (aiSummary) {
                const aiSection = document.getElementById('entryAISection');
                const aiEl = document.getElementById('entryAI');

                if (aiSection) aiSection.style.display = 'block';
                if (aiEl) aiEl.textContent = aiSummary;
            }

            // Store data for edit functionality (handle both id and entry_id)
            const currentEntryId = hasValue(entry.id) ? entry.id : (hasValue(entry.entry_id) ? entry.entry_id : null);
            console.log('[loadEntry] Storing currentViewData with ID:', currentEntryId, 'Full entry:', entry);
            MyavanaTimeline.State.set('currentViewData', { 
                type: 'entry', 
                id: currentEntryId, 
                entry_id: currentEntryId, 
                data: entry 
            });
        }

        function resetEntryViewSections() {
            const hiddenSectionIds = [
                'entryRatingSection',
                'entryMoodSection',
                'entryProductsSection',
                'entryDetailsSection',
                'entryFollowUpSection',
                'entryVideosSection',
                'entryAISection'
            ];
            hiddenSectionIds.forEach(sectionId => {
                const sectionEl = document.getElementById(sectionId);
                if (sectionEl) {
                    sectionEl.style.display = 'none';
                }
            });

            const clearIds = [
                'entryProducts',
                'entryDetailsGrid',
                'entryFollowUpStack',
                'entryAI',
                'entryVideos',
                'entryGallery',
                'entryPrimaryImage',
                'entryRatingStars'
            ];
            clearIds.forEach(id => {
                const el = document.getElementById(id);
                if (el) {
                    el.innerHTML = '';
                }
            });

            const primaryImageEl = document.getElementById('entryPrimaryImage');
            if (primaryImageEl) {
                primaryImageEl.style.display = 'none';
            }
        }

        /**
         * Populate entry gallery correctly
         *
         * @param {Object} entry - Entry data object
         */
        function populateEntryGallery(entry) {
            const galleryEl = document.getElementById('entryGallery');
            const primaryImageEl = document.getElementById('entryPrimaryImage');
            if (!galleryEl) return;

            const images = getEntryImageMedia(entry);
            if (!images.length) {
                galleryEl.style.display = 'none';
                galleryEl.innerHTML = '';
                if (primaryImageEl) {
                    primaryImageEl.style.display = 'none';
                    primaryImageEl.innerHTML = '';
                }
                return;
            }

            const featuredIndex = clampIndex(entry.featured_image_index, images.length);
            const featuredMedia = images[featuredIndex] || images[0];
            const featuredUrl = featuredMedia ? String(featuredMedia.url || '').trim() : '';

            if (primaryImageEl && featuredUrl) {
                primaryImageEl.innerHTML = `
                    <span class="view-primary-badge-hjn">Featured Photo</span>
                    <img src="${escapeHtml(featuredUrl)}"
                         alt="${escapeHtml(entry.title || 'Entry image')}"
                         class="view-gallery-img-hjn"
                         onclick='MyavanaTimeline.View.openImageOverlay(${JSON.stringify(featuredUrl)})' />
                `;
                primaryImageEl.style.display = 'block';
            }

            const remainingImages = images.filter((item, index) => index !== featuredIndex);
            if (!remainingImages.length) {
                galleryEl.style.display = 'none';
                galleryEl.innerHTML = '';
                return;
            }

            const gridHTML = remainingImages.map((imgItem, index) => {
                const imgUrl = String(imgItem.url || '').trim();
                return `
                    <div class="view-gallery-item-hjn">
                        <img src="${escapeHtml(imgUrl)}"
                             alt="${escapeHtml(`${entry.title || 'Entry image'} ${index + 1}`)}"
                             class="view-gallery-img-hjn"
                             onclick='MyavanaTimeline.View.openImageOverlay(${JSON.stringify(imgUrl)})' />
                    </div>
                `;
            }).join('');

            galleryEl.innerHTML = `<div class="view-gallery-grid-hjn">${gridHTML}</div>`;
            galleryEl.style.display = 'block';
        }

        function populateEntryVideos(entry) {
            const videosSection = document.getElementById('entryVideosSection');
            const videosEl = document.getElementById('entryVideos');
            if (!videosSection || !videosEl) return;

            const videos = getEntryVideoMedia(entry);
            if (!videos.length) {
                videosSection.style.display = 'none';
                videosEl.innerHTML = '';
                return;
            }

            const cards = videos
                .map(video => {
                    const videoUrl = typeof video === 'string' ? video : (video && video.url);
                    if (!videoUrl) return '';
                    return `
                        <div class="view-video-card-hjn">
                            <video controls playsinline preload="metadata">
                                <source src="${escapeHtml(videoUrl)}" type="${escapeHtml(video.mime || '')}" />
                            </video>
                        </div>
                    `;
                })
                .filter(Boolean)
                .join('');

            if (!cards) {
                videosSection.style.display = 'none';
                videosEl.innerHTML = '';
                return;
            }

            videosEl.innerHTML = cards;
            videosSection.style.display = 'block';
        }

        function populateEntryDetails(entry) {
            const tags = Array.isArray(entry.entry_tags_list)
                ? entry.entry_tags_list.join(', ')
                : (entry.entry_tags || '');
            const imageCount = getEntryImageMedia(entry).length;
            const videoCount = getEntryVideoMedia(entry).length;
            const mediaSummary = (imageCount || videoCount)
                ? `${imageCount} photo${imageCount === 1 ? '' : 's'}${videoCount ? `, ${videoCount} video${videoCount === 1 ? '' : 's'}` : ''}`
                : '';
            const createdAt = entry.created_at || '';
            const updatedAt = entry.updated_at || '';

            const detailRows = [
                { label: 'Date', value: entry.entry_date },
                { label: 'Type', value: entry.entry_type },
                { label: 'Time', value: entry.entry_time },
                { label: 'Environment', value: entry.environment },
                { label: 'Scalp Condition', value: entry.scalp_condition },
                { label: 'Hair Feel', value: entry.hair_feel },
                { label: 'Length Check', value: hasValue(entry.length_check_cm) ? `${entry.length_check_cm} cm` : '' },
                { label: 'Techniques', value: entry.techniques },
                { label: 'Tags', value: tags },
                { label: 'Media', value: mediaSummary },
                { label: 'Session', value: entry.session_id || '' },
                { label: 'Created', value: createdAt },
                { label: 'Updated', value: updatedAt },
            ];

            renderDetailGrid('entryDetailsGrid', 'entryDetailsSection', detailRows);
        }

        function populateEntryFollowUp(entry) {
            const sectionEl = document.getElementById('entryFollowUpSection');
            const stackEl = document.getElementById('entryFollowUpStack');
            if (!sectionEl || !stackEl) return;

            const blocks = [
                { title: 'Notes', value: entry.notes },
                { title: 'Video Notes', value: entry.video_notes },
                { title: 'Next Step', value: entry.next_step },
            ].filter(item => hasValue(item.value));

            if (!blocks.length) {
                sectionEl.style.display = 'none';
                stackEl.innerHTML = '';
                return;
            }

            stackEl.innerHTML = blocks.map(item => `
                <div class="view-followup-item-hjn">
                    <div class="view-followup-title-hjn">${escapeHtml(item.title)}</div>
                    <div class="view-followup-text-hjn">${escapeHtml(item.value)}</div>
                </div>
            `).join('');
            sectionEl.style.display = 'block';
        }

        /**
         * Open image overlay for full-screen viewing
         *
         * @param {string} imageUrl - Image URL to display
         */
        function openImageOverlay(imageUrl) {
            let overlay = document.getElementById('imageOverlayHjn');
            const safeUrl = String(imageUrl || '').trim();
            if (!safeUrl) return;
            if (!overlay) {
                overlay = document.createElement('div');
                overlay.id = 'imageOverlayHjn';
                overlay.className = 'image-overlay-hjn';
                overlay.innerHTML = `
                    <div class="image-overlay-content-hjn">
                        <button class="image-overlay-close-hjn" onclick="MyavanaTimeline.View.closeImageOverlay()">&times;</button>
                        <img src="${escapeHtml(safeUrl)}" alt="Full size view" class="image-overlay-img-hjn" />
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
                overlay.querySelector('.image-overlay-img-hjn').src = safeUrl;
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

            const products = normalizeStringList(
                entry.products_list ||
                entry.products_used_list ||
                entry.products ||
                entry.products_used
            );

            if (!products || products.length === 0) {
                if (productsSection) productsSection.style.display = 'none';
                if (productsEl) productsEl.innerHTML = '';
                return;
            }

            if (productsSection) productsSection.style.display = 'block';
            if (productsEl) {
                const productsHTML = products.map(product => `<span class="view-tag-hjn">${escapeHtml(product)}</span>`).join('');
                productsEl.innerHTML = productsHTML;
            }
        }

        function findGoalListItem(goalRef) {
            const candidates = document.querySelectorAll('[data-goal-index], [data-goal-id], .myavana-goal-card');
            return Array.from(candidates).find(item => {
                const idx = item.getAttribute('data-goal-index');
                const id = item.getAttribute('data-goal-id');
                const generic = item.getAttribute('data-id');
                return valuesMatch(goalRef, idx) || valuesMatch(goalRef, id) || valuesMatch(goalRef, generic);
            }) || null;
        }

        function parseJsonMaybe(value, fallback) {
            if (!value) return fallback;
            try {
                return JSON.parse(value);
            } catch (e) {
                return fallback;
            }
        }

        function loadGoal(goalIndex) {
            console.log('[loadGoal] Loading goal with reference:', goalIndex);
            const body = document.getElementById('goalViewBody');
            if (!body) return;

            const loadingEl = body.querySelector('.view-loading-hjn');
            const contentEl = body.querySelector('.view-content-hjn');
            if (loadingEl) loadingEl.style.display = 'flex';
            if (contentEl) contentEl.style.display = 'none';

            const listItem = findGoalListItem(goalIndex);
            const domGoal = listItem ? extractGoalData(listItem) : null;
            const resolvedGoalId = domGoal && (domGoal.goal_id ?? domGoal.id);

            const settings = window.myavanaTimelineSettings || {};
            const nonce = settings.getGoalDetailsNonce || settings.getEntryDetailsNonce || settings.nonce || '';
            const requestGoalId = hasValue(resolvedGoalId) ? resolvedGoalId : goalIndex;

            const formData = new FormData();
            formData.append('action', 'myavana_get_goal_details');
            formData.append('goal_id', requestGoalId);
            formData.append('security', nonce);

            fetch(settings.ajaxUrl || settings.ajaxurl || '/wp-admin/admin-ajax.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                if (result && result.success && result.data) {
                    const payload = Object.assign({}, domGoal || {}, result.data || {});
                    populateGoalView(payload);
                    return;
                }

                if (domGoal) {
                    populateGoalView(domGoal);
                    return;
                }

                showViewError('Goal not found');
            })
            .catch(() => {
                if (domGoal) {
                    populateGoalView(domGoal);
                    return;
                }
                showViewError('Goal not found');
            });
        }

        function extractGoalData(listItem) {
            const title = listItem.querySelector('.list-item-title-hjn, .myavana-goal-title')?.textContent?.trim() || 'Untitled Goal';
            const dateRange = listItem.querySelector('.list-item-date-hjn, .myavana-goal-dates')?.textContent?.trim() || '';
            const progressText = listItem.querySelector('.list-item-badge-hjn, .myavana-goal-progress-badge')?.textContent || '0%';
            const progress = parseInt(progressText, 10) || 0;
            const description = listItem.querySelector('.list-item-description-hjn, .myavana-goal-description')?.textContent?.trim() ||
                listItem.getAttribute('data-goal-description') ||
                '';

            const goalIndex = parseRefValue(listItem.getAttribute('data-goal-index'));
            const goalIdRaw = listItem.getAttribute('data-goal-id') || listItem.getAttribute('data-id');
            const goalId = parseRefValue(goalIdRaw);
            const resolvedGoalRef = hasValue(goalIndex) ? goalIndex : goalId;

            const milestones = parseJsonMaybe(listItem.getAttribute('data-goal-milestones'), []);
            const progressHistory = parseJsonMaybe(listItem.getAttribute('data-goal-progress-history'), []);
            const progressNotes = parseJsonMaybe(listItem.getAttribute('data-goal-progress-notes'), []);

            return {
                id: resolvedGoalRef,
                goal_id: resolvedGoalRef,
                goal_ref_id: goalId,
                title,
                dateRange,
                description,
                progress,
                goal_category: listItem.getAttribute('data-goal-category') || '',
                goal_target: listItem.getAttribute('data-goal-target') || '',
                goal_priority: listItem.getAttribute('data-goal-priority') || '',
                goal_checkin_frequency: listItem.getAttribute('data-goal-checkin-frequency') || '',
                milestones: Array.isArray(milestones) ? milestones : [],
                progress_history: Array.isArray(progressHistory) ? progressHistory : [],
                progress_text: Array.isArray(progressNotes) ? progressNotes : []
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

            resetGoalViewSections();

            // Populate title
            const titleEl = document.getElementById('goalTitle');
            if (titleEl) titleEl.textContent = goal.title || goal.goal_title || 'Untitled Goal';

            // Populate date range
            const dateEl = document.getElementById('goalDateRange');
            if (dateEl) {
                const startDate = goal.start_date || goal.goal_start_date || '';
                const endDate = goal.target_date || goal.goal_end_date || goal.end_date || '';
                const fallbackRange = [startDate, endDate].filter(Boolean).join(' - ');
                dateEl.textContent = goal.dateRange || fallbackRange;
            }

            // Populate progress circle
            const progressPercent = document.getElementById('goalProgressPercent');
            const progressRing = document.getElementById('goalProgressRing');
            const goalProgress = Math.max(0, Math.min(100, parseInt(goal.progress, 10) || 0));

            if (progressPercent) progressPercent.textContent = `${goalProgress}%`;

            if (progressRing) {
                const circumference = 2 * Math.PI * 60; // radius is 60
                const offset = circumference - (goalProgress / 100) * circumference;
                progressRing.style.strokeDashoffset = offset;
            }

            // Populate description
            const descEl = document.getElementById('goalDescription');
            if (descEl) descEl.textContent = goal.description || 'No description provided.';

            populateGoalMilestones(goal.milestones);
            populateGoalDetails(goal);

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

            const resolvedGoalId = hasValue(goal.goal_id) ? goal.goal_id : goal.id;

            const stateData = {
                type: 'goal',
                id: resolvedGoalId,
                goal_id: resolvedGoalId,
                data: goal
            };

            console.log('[populateGoalView] State data to store:', stateData);
            MyavanaTimeline.State.set('currentViewData', stateData);

            // Verify it was stored
            const storedData = MyavanaTimeline.State.get('currentViewData');
            console.log('[populateGoalView] Verified stored state:', storedData);
        }

        function resetGoalViewSections() {
            const sectionIds = [
                'goalMilestonesSection',
                'goalProgressHistorySection',
                'goalNotesSection',
                'goalDetailsSection'
            ];

            sectionIds.forEach(id => {
                const sectionEl = document.getElementById(id);
                if (sectionEl) {
                    sectionEl.style.display = 'none';
                }
            });

            const clearIds = ['goalMilestones', 'goalProgressHistory', 'goalProgressNotes', 'goalDetailsGrid'];
            clearIds.forEach(id => {
                const el = document.getElementById(id);
                if (el) {
                    el.innerHTML = '';
                }
            });
        }

        function populateGoalMilestones(milestones) {
            const sectionEl = document.getElementById('goalMilestonesSection');
            const milestonesEl = document.getElementById('goalMilestones');
            if (!sectionEl || !milestonesEl) return;

            const milestoneList = Array.isArray(milestones) ? milestones : [];
            if (!milestoneList.length) {
                sectionEl.style.display = 'none';
                milestonesEl.innerHTML = '';
                return;
            }

            const milestoneHtml = milestoneList
                .map(item => {
                    if (!item) return '';
                    const text = typeof item === 'object' ? (item.text || item.title || '') : String(item);
                    if (!text) return '';
                    const achieved = typeof item === 'object' ? !!item.achieved : false;
                    return `
                        <div class="milestone-item-hjn ${achieved ? 'achieved' : ''}">
                            <span class="milestone-dot-hjn">${achieved ? '&#10003;' : '&#9675;'}</span>
                            <span class="milestone-text-hjn">${escapeHtml(text)}</span>
                        </div>
                    `;
                })
                .filter(Boolean)
                .join('');

            if (!milestoneHtml) {
                sectionEl.style.display = 'none';
                milestonesEl.innerHTML = '';
                return;
            }

            milestonesEl.innerHTML = milestoneHtml;
            sectionEl.style.display = 'block';
        }

        function populateGoalDetails(goal) {
            const unit = goal.goal_measure_unit ? ` ${goal.goal_measure_unit}` : '';
            const detailRows = [
                { label: 'Status', value: goal.status },
                { label: 'Category', value: goal.goal_category },
                { label: 'Target Focus', value: goal.goal_target },
                { label: 'Priority', value: goal.goal_priority || goal.priority },
                { label: 'Check-In Frequency', value: goal.goal_checkin_frequency },
                { label: 'Baseline', value: hasValue(goal.goal_baseline_value) ? `${goal.goal_baseline_value}${unit}` : '' },
                { label: 'Target Value', value: hasValue(goal.goal_target_value) ? `${goal.goal_target_value}${unit}` : '' },
                { label: 'Reward', value: goal.goal_reward },
                { label: 'Success Criteria', value: goal.goal_success_criteria },
            ];

            renderDetailGrid('goalDetailsGrid', 'goalDetailsSection', detailRows);
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

        function findRoutineListItem(routineRef) {
            const candidates = document.querySelectorAll('[data-routine-index], [data-routine-id], .myavana-routine-card');
            return Array.from(candidates).find(item => {
                const idx = item.getAttribute('data-routine-index');
                const id = item.getAttribute('data-routine-id');
                const generic = item.getAttribute('data-id');
                return valuesMatch(routineRef, idx) || valuesMatch(routineRef, id) || valuesMatch(routineRef, generic);
            }) || null;
        }

        function loadRoutine(routineId) {
            console.log('Loading routine view:', routineId);
            const body = document.getElementById('routineViewBody');
            if (!body) {
                console.error('Routine view body not found');
                return;
            }

            const loadingEl = body.querySelector('.view-loading-hjn');
            const contentEl = body.querySelector('.view-content-hjn');
            if (loadingEl) loadingEl.style.display = 'flex';
            if (contentEl) contentEl.style.display = 'none';

            // Try calendar payload first (hair journey page)
            const calendarDataEl = document.getElementById('calendarDataHjn');
            if (calendarDataEl) {
                try {
                    const calendarData = JSON.parse(calendarDataEl.textContent);
                    const calendarRoutine = Array.isArray(calendarData.routines)
                        ? calendarData.routines.find(r => r.id == routineId)
                        : null;

                    if (calendarRoutine) {
                        populateRoutineView(calendarRoutine);
                        return;
                    }
                } catch (error) {
                    console.error('Error parsing calendar data:', error);
                }
            }

            const listItem = findRoutineListItem(routineId);
            const domRoutine = listItem ? extractRoutineData(listItem) : null;
            const resolvedRoutineId = domRoutine && (domRoutine.routine_id ?? domRoutine.id);

            const settings = window.myavanaTimelineSettings || {};
            const nonce = settings.getRoutineDetailsNonce || settings.getEntryDetailsNonce || settings.nonce || '';
            const requestRoutineId = hasValue(resolvedRoutineId) ? resolvedRoutineId : routineId;

            const formData = new FormData();
            formData.append('action', 'myavana_get_routine_details');
            formData.append('routine_id', requestRoutineId);
            formData.append('security', nonce);

            fetch(settings.ajaxUrl || settings.ajaxurl || '/wp-admin/admin-ajax.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                if (result && result.success && result.data) {
                    const payload = Object.assign({}, domRoutine || {}, result.data || {});
                    populateRoutineView(payload);
                    return;
                }

                if (domRoutine) {
                    populateRoutineView(domRoutine);
                    return;
                }

                showViewError('Routine not found');
            })
            .catch(() => {
                if (domRoutine) {
                    populateRoutineView(domRoutine);
                    return;
                }
                showViewError('Routine not found');
            });
        }

        function extractRoutineData(listItem) {
            const title = listItem.querySelector('.list-item-title-hjn, .myavana-routine-title')?.textContent?.trim() || 'Untitled Routine';
            const schedule = listItem.querySelector('.list-item-badge-hjn, .myavana-routine-schedule')?.textContent?.trim() || '';
            const description = listItem.querySelector('.list-item-description-hjn, .myavana-routine-description')?.textContent?.trim() ||
                listItem.getAttribute('data-routine-description') ||
                '';

            const routineIndex = parseRefValue(listItem.getAttribute('data-routine-index'));
            const routineIdRaw = listItem.getAttribute('data-routine-id') || listItem.getAttribute('data-id');
            const parsedRoutineId = parseRefValue(routineIdRaw);
            const resolvedRoutineRef = hasValue(routineIndex) ? routineIndex : parsedRoutineId;

            const steps = parseJsonMaybe(listItem.getAttribute('data-routine-steps'), []);
            const products = parseJsonMaybe(listItem.getAttribute('data-routine-products'), []);

            return {
                id: resolvedRoutineRef,
                routine_id: resolvedRoutineRef,
                routine_ref_id: parsedRoutineId,
                title,
                schedule,
                description,
                frequency: listItem.getAttribute('data-routine-frequency') || schedule,
                time: listItem.getAttribute('data-routine-time') || '',
                duration: listItem.getAttribute('data-routine-duration') || '',
                routine_phase: listItem.getAttribute('data-routine-phase') || '',
                routine_goal_link: listItem.getAttribute('data-routine-goal-link') || '',
                routine_tools: listItem.getAttribute('data-routine-tools') || '',
                steps: Array.isArray(steps) ? steps : [],
                products: Array.isArray(products) ? products : []
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

            resetRoutineViewSections();

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
                                    <h5 class="step-title-hjn">${escapeHtml(stepTitle)}</h5>
                                    ${stepDesc ? `<p class="step-description-hjn">${escapeHtml(stepDesc)}</p>` : ''}
                                </div>
                            </div>
                        `;
                    }).join('');
                    stepsEl.innerHTML = stepsHTML;
                } else {
                    stepsEl.innerHTML = '<p style="color: var(--text-secondary);">No steps defined yet.</p>';
                }
            }

            // Populate products
            const productsSection = document.getElementById('routineProductsSection');
            const productsEl = document.getElementById('routineProducts');
            const products = normalizeStringList(routine.products_list || routine.products);

            if (productsSection && productsEl) {
                if (products.length > 0) {
                    productsSection.style.display = 'block';
                    productsEl.innerHTML = products.map(product => `<span class="view-tag-hjn">${escapeHtml(product)}</span>`).join('');
                } else {
                    productsSection.style.display = 'none';
                    productsEl.innerHTML = '';
                }
            }

            populateRoutineDetails(routine);

            // Store data for edit functionality
            const resolvedRoutineId = hasValue(routine.routine_id) ? routine.routine_id : routine.id;
            MyavanaTimeline.State.set('currentViewData', {
                type: 'routine',
                id: resolvedRoutineId,
                routine_id: resolvedRoutineId,
                data: routine
            });
            syncRoutineCompleteButton(resolvedRoutineId);
            console.log('Routine view populated successfully');
        }

        function syncRoutineCompleteButton(routineId) {
            const completeBtn = document.getElementById('routineCompleteBtn');
            if (!completeBtn || !hasValue(routineId)) return;

            const today = completeBtn.getAttribute('data-date') || new Date().toISOString().split('T')[0];
            completeBtn.setAttribute('data-routine-id', String(routineId));
            completeBtn.setAttribute('data-date', today);

            let completed = false;
            const matchedToggle = document.querySelector(`[data-routine-complete-toggle][data-routine-id="${String(routineId)}"]`);
            if (matchedToggle) {
                completed = matchedToggle.classList.contains('is-complete') ||
                    matchedToggle.getAttribute('aria-pressed') === 'true';
            }

            completeBtn.classList.toggle('is-complete', completed);
            completeBtn.setAttribute('aria-pressed', completed ? 'true' : 'false');
            completeBtn.textContent = completed ? 'Completed today' : 'Mark Complete Today';
        }

        function resetRoutineViewSections() {
            const sectionIds = ['routineProductsSection', 'routineDetailsSection', 'routineHistorySection'];
            sectionIds.forEach(id => {
                const sectionEl = document.getElementById(id);
                if (sectionEl) {
                    sectionEl.style.display = 'none';
                }
            });

            const clearIds = ['routineProducts', 'routineDetailsGrid'];
            clearIds.forEach(id => {
                const el = document.getElementById(id);
                if (el) {
                    el.innerHTML = '';
                }
            });
        }

        function populateRoutineDetails(routine) {
            const tools = normalizeStringList(routine.routine_tools).join(', ');
            const autoTrackRaw = routine.routine_auto_track;
            const autoTrackValue = hasValue(autoTrackRaw)
                ? ((String(autoTrackRaw) === '1' || String(autoTrackRaw).toLowerCase() === 'true') ? 'Enabled' : 'Disabled')
                : '';

            const detailRows = [
                { label: 'Frequency', value: routine.frequency || routine.routine_frequency },
                { label: 'Time', value: routine.time || routine.routine_time },
                { label: 'Duration', value: routine.duration || routine.routine_duration },
                { label: 'Phase', value: routine.routine_phase },
                { label: 'Linked Goal', value: routine.routine_goal_link },
                { label: 'Tools', value: tools },
                { label: 'Auto Track', value: autoTrackValue },
            ];

            renderDetailGrid('routineDetailsGrid', 'routineDetailsSection', detailRows);
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
            let entryId = hasValue(currentViewData.id) ? currentViewData.id : null;
            if (!hasValue(entryId) && hasValue(currentViewData.entry_id)) {
                entryId = currentViewData.entry_id;
            }
            if (!hasValue(entryId) && currentViewData.data) {
                entryId = hasValue(currentViewData.data.id) ? currentViewData.data.id : currentViewData.data.entry_id;
            }

            console.log('[editEntry] Extracted entry ID:', entryId);

            if (!hasValue(entryId)) {
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

            let entryId = hasValue(currentViewData.id) ? currentViewData.id : null;
            if (!hasValue(entryId) && hasValue(currentViewData.entry_id)) {
                entryId = currentViewData.entry_id;
            }
            if (!hasValue(entryId) && currentViewData.data) {
                entryId = hasValue(currentViewData.data.id) ? currentViewData.data.id : currentViewData.data.entry_id;
            }

            console.log('[deleteEntry] Deleting entry ID:', entryId);

            if (!hasValue(entryId)) {
                alert('Could not find entry ID to delete.');
                return;
            }

            if (!confirm('Are you sure you want to delete this entry? This action cannot be undone.')) {
                return;
            }

            const settings = window.myavanaTimelineSettings || window.myavanaTimeline || window.myavanaTimelineInstance || {};

            // Show loading on delete button
            const deleteBtn = document.getElementById('deleteEntryBtn');
            let originalDeleteText = '';
            if (deleteBtn) {
                originalDeleteText = deleteBtn.innerHTML;
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
                        deleteBtn.innerHTML = originalDeleteText;
                        deleteBtn.disabled = false;
                    }
                }
            })
            .catch(error => {
                console.error('Error deleting entry:', error);
                alert('Network error. Please try again.');
                // Reset delete button
                if (deleteBtn) {
                    deleteBtn.innerHTML = originalDeleteText;
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
            let goalId = hasValue(currentViewData.id) ? currentViewData.id : null;
            if (!hasValue(goalId) && hasValue(currentViewData.goal_id)) {
                goalId = currentViewData.goal_id;
            }
            if (!hasValue(goalId) && currentViewData.data) {
                goalId = hasValue(currentViewData.data.id) ? currentViewData.data.id : currentViewData.data.goal_id;
            }
            console.log('[editGoal] Extracted goal ID:', goalId);

            if (!hasValue(goalId)) {
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
            let routineId = hasValue(currentViewData.id) ? currentViewData.id : null;
            if (!hasValue(routineId) && hasValue(currentViewData.routine_id)) {
                routineId = currentViewData.routine_id;
            }
            if (!hasValue(routineId) && currentViewData.data) {
                routineId = hasValue(currentViewData.data.id) ? currentViewData.data.id : currentViewData.data.routine_id;
            }
            console.log('[editRoutine] Extracted routine ID:', routineId);

            if (!hasValue(routineId)) {
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

        /**
         * Toggle routine completion for a date (defaults to today)
         *
         * @param {number|string} routineId
         * @param {string} dateStr YYYY-MM-DD
         * @param {HTMLElement|null} triggerEl
         */
        function toggleRoutineCompletion(routineId, dateStr, triggerEl) {
            const parsedId = parseInt(routineId, 10);
            if (Number.isNaN(parsedId) || parsedId < 0) {
                return;
            }

            const settings = window.myavanaTimelineSettings || {};
            const nonce = settings.toggleRoutineNonce || settings.addRoutineNonce || settings.nonce || '';
            const payloadDate = dateStr && /^\d{4}-\d{2}-\d{2}$/.test(String(dateStr))
                ? String(dateStr)
                : new Date().toISOString().split('T')[0];

            if (triggerEl) {
                triggerEl.disabled = true;
            }

            fetch(settings.ajaxUrl || settings.ajaxurl || '/wp-admin/admin-ajax.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'myavana_toggle_routine_completion',
                    security: nonce,
                    routine_id: String(parsedId),
                    date: payloadDate
                })
            })
            .then(response => response.json())
            .then(data => {
                if (!data || !data.success || !data.data) {
                    throw new Error((data && data.data) || 'Unable to update routine completion');
                }

                const completed = !!data.data.completed;
                const targets = [];
                if (triggerEl) {
                    targets.push(triggerEl);
                } else {
                    document.querySelectorAll(`[data-routine-complete-toggle][data-routine-id="${parsedId}"]`).forEach(el => targets.push(el));
                }
                const viewCompleteBtn = document.getElementById('routineCompleteBtn');
                if (
                    viewCompleteBtn &&
                    String(viewCompleteBtn.getAttribute('data-routine-id') || '') === String(parsedId) &&
                    !targets.includes(viewCompleteBtn)
                ) {
                    targets.push(viewCompleteBtn);
                }

                targets.forEach(btn => {
                    const card = btn.closest('.timeline-card-hjn, .myavana-routine-manage-card');
                    const isViewFooterBtn = btn.id === 'routineCompleteBtn';
                    btn.classList.toggle('is-complete', completed);
                    btn.setAttribute('aria-pressed', completed ? 'true' : 'false');
                    btn.textContent = completed
                        ? 'Completed today'
                        : (isViewFooterBtn ? 'Mark Complete Today' : 'Mark complete');
                    if (card) {
                        card.classList.toggle('is-completed-hjn', completed);
                    }
                });
            })
            .catch(error => {
                console.error('[Routine Completion] Error:', error);
                alert(error.message || 'Unable to update routine completion');
            })
            .finally(() => {
                if (triggerEl) {
                    triggerEl.disabled = false;
                }
            });
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
            toggleRoutineCompletion: toggleRoutineCompletion,
            toggleCurrentRoutineCompletion: function(triggerEl) {
                const currentViewData = MyavanaTimeline.State.get('currentViewData');
                if (!currentViewData || currentViewData.type !== 'routine') return;

                const routineId = hasValue(currentViewData.routine_id) ? currentViewData.routine_id : currentViewData.id;
                if (!hasValue(routineId)) return;

                const sourceBtn = triggerEl || document.getElementById('routineCompleteBtn');
                const buttonDate = sourceBtn ? sourceBtn.getAttribute('data-date') : '';
                toggleRoutineCompletion(routineId, buttonDate, sourceBtn || null);
            },
            initProgressNoteCounter: initProgressNoteCounter,
            openImageOverlay: openImageOverlay,
            closeImageOverlay: closeImageOverlay
        };

    })();

    function hasValue(value) {
        return value !== undefined && value !== null && value !== '';
    }

    // Backward compatibility - always map globals to the modular view controller.
    window.openViewOffcanvas = MyavanaTimeline.View.openView;
    window.loadEntryView = MyavanaTimeline.View.loadEntry;
    window.loadGoalView = MyavanaTimeline.View.loadGoal;
    window.loadRoutineView = MyavanaTimeline.View.loadRoutine;
    // Edit functions with parameter support for new form system
    if (typeof window.editEntry === 'undefined') {
        window.editEntry = function(entryId) {
            console.log('[window.editEntry] Called with ID:', entryId);
            console.log('[window.editEntry] State exists:', !!MyavanaTimeline.State);

            // If no ID provided, try to get from currentViewData
            if (!hasValue(entryId)) {
                const currentViewData = MyavanaTimeline.State ? MyavanaTimeline.State.get('currentViewData') : null;
                console.log('[window.editEntry] currentViewData:', currentViewData);
                if (currentViewData) {
                    entryId = hasValue(currentViewData.id) ? currentViewData.id : null;
                    if (!hasValue(entryId) && hasValue(currentViewData.entry_id)) {
                        entryId = currentViewData.entry_id;
                    }
                    if (!hasValue(entryId) && currentViewData.data) {
                        entryId = hasValue(currentViewData.data.id) ? currentViewData.data.id : currentViewData.data.entry_id;
                    }
                }
                console.log('[window.editEntry] Extracted ID from state:', entryId);
            }

            if (!hasValue(entryId)) {
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

            if (!hasValue(goalId)) {
                const currentViewData = MyavanaTimeline.State ? MyavanaTimeline.State.get('currentViewData') : null;
                if (currentViewData) {
                    goalId = hasValue(currentViewData.id) ? currentViewData.id : null;
                    if (!hasValue(goalId) && hasValue(currentViewData.goal_id)) {
                        goalId = currentViewData.goal_id;
                    }
                    if (!hasValue(goalId) && currentViewData.data) {
                        goalId = hasValue(currentViewData.data.id) ? currentViewData.data.id : currentViewData.data.goal_id;
                    }
                }
                console.log('[window.editGoal] Got ID from state:', goalId);
            }

            if (hasValue(goalId)) {
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

            if (!hasValue(routineId)) {
                const currentViewData = MyavanaTimeline.State ? MyavanaTimeline.State.get('currentViewData') : null;
                if (currentViewData) {
                    routineId = hasValue(currentViewData.id) ? currentViewData.id : null;
                    if (!hasValue(routineId) && hasValue(currentViewData.routine_id)) {
                        routineId = currentViewData.routine_id;
                    }
                    if (!hasValue(routineId) && currentViewData.data) {
                        routineId = hasValue(currentViewData.data.id) ? currentViewData.data.id : currentViewData.data.routine_id;
                    }
                }
                console.log('[window.editRoutine] Got ID from state:', routineId);
            }

            if (hasValue(routineId)) {
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

    if (typeof window.toggleRoutineCompletion === 'undefined') {
        window.toggleRoutineCompletion = function(routineId, dateStr, triggerEl) {
            return MyavanaTimeline.View.toggleRoutineCompletion(routineId, dateStr, triggerEl || null);
        };
    }

    if (typeof window.toggleCurrentRoutineCompletion === 'undefined') {
        window.toggleCurrentRoutineCompletion = function(triggerEl) {
            return MyavanaTimeline.View.toggleCurrentRoutineCompletion(triggerEl || null);
        };
    }

})();
