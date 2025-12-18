/**
 * MYAVANA Hair Journey Diary - Complete Redesign
 * Modern JavaScript implementation with calendar functionality and entry management
 */

class MyavanaHairDiary {
    constructor() {
        this.currentDate = new Date();
        this.entries = [];
        this.selectedDate = null;
        this.currentEntryId = null;
        this.isLoading = false;

        // Entry type colors mapping
        this.entryTypeColors = {
            wash: '#4ecdc4',
            treatment: '#45b7d1',
            styling: '#96ceb4',
            progress: '#feca57',
            general: '#ff6b6b'
        };

        // Use floating overlay for cards (default off) — we'll render cards inside date cells and allow stacking
        this.useFloatingOverlay = false;

        // Default cell min height (px) to make room for stacked cards
        this.dayCellMinHeight = 220;

        this.init();
    }

    init() {
        console.log('🎯 Initializing MYAVANA Hair Diary...');

        // Check if configuration is available
        if (typeof myavanaHairDiary === 'undefined') {
            console.error('❌ MYAVANA Hair Diary configuration not found!');
            console.warn('⚠️ Falling back to default configuration...');

            // Create fallback configuration
            window.myavanaHairDiary = {
                ajax_url: '/wp-admin/admin-ajax.php',
                nonces: {
                    get_entries: 'fallback_nonce',
                    save_entry: 'fallback_nonce',
                    delete_entry: 'fallback_nonce',
                    get_entry: 'fallback_nonce'
                },
                user_id: '1',
                is_owner: true,
                current_user_id: '1',
                actions: {
                    get_entries: 'myavana_get_diary_entries2',
                    save_entry: 'myavana_save_diary_entry',
                    delete_entry: 'myavana_delete_diary_entry',
                    get_entry: 'myavana_get_single_diary_entry'
                }
            };
            console.log('⚠️ Using fallback configuration - entries may not load without proper nonce');
        }

        // Store configuration in instance for easy access
        this.config = window.myavanaHairDiary;

        // Debug configuration
        console.log('🔧 Configuration loaded:', this.config);

        // Handle both old and new nonce formats for compatibility
        if (this.config.nonce && !this.config.nonces) {
            // Old format - convert to new format
            this.config.nonces = {
                get_entries: this.config.nonce,
                save_entry: this.config.nonce,
                delete_entry: this.config.nonce,
                get_entry: this.config.nonce
            };
        }

        // Add fallback actions if missing
        if (!this.config.actions) {
            console.warn('⚠️ Actions not found in config, using fallbacks');
            this.config.actions = {
                get_entries: 'myavana_get_diary_entries2',
                save_entry: 'myavana_save_diary_entry',
                delete_entry: 'myavana_delete_diary_entry',
                get_entry: 'myavana_get_single_diary_entry'
            };
        }

        console.log('🔧 Final configuration:', this.config);

        // Ensure DOM elements are available
        if (!this.checkRequiredElements()) {
            console.error('❌ Required DOM elements not found, retrying in 500ms...');
            setTimeout(() => this.init(), 500);
            return;
        }

        this.setupEventListeners();
        this.renderCalendar(); // Render calendar first to show structure
        this.loadEntries();    // Then load entries asynchronously
        this.updateStatistics();

        console.log('✅ MYAVANA Hair Diary initialized successfully');
    }

    checkRequiredElements() {
        const requiredElements = [
            'calendarGrid',
            'calendarMonth',
            'prevMonthBtn',
            'nextMonthBtn'
        ];

        for (const elementId of requiredElements) {
            if (!document.getElementById(elementId)) {
                console.error(`❌ Required element not found: ${elementId}`);
                return false;
            }
        }

        return true;
    }

    setupEventListeners() {
        console.log('🔧 Setting up event listeners...');

        // Calendar navigation
        const prevMonthBtn = document.getElementById('prevMonthBtn');
        const nextMonthBtn = document.getElementById('nextMonthBtn');

        if (prevMonthBtn) {
            prevMonthBtn.addEventListener('click', () => this.navigateMonth(-1));
        }

        if (nextMonthBtn) {
            nextMonthBtn.addEventListener('click', () => this.navigateMonth(1));
        }

        // Add entry button
        const addEntryBtn = document.getElementById('addEntryBtn');
        if (addEntryBtn) {
            addEntryBtn.addEventListener('click', () => this.openEntryModal());
        }

        // Quick action buttons
        const quickButtons = document.querySelectorAll('.myavana-quick-btn');
        quickButtons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                const entryType = btn.dataset.entryType;
                this.openEntryModal(null, entryType);
            });
        });

        // Modal controls
        const closeModalBtn = document.getElementById('closeModalBtn');
        const cancelBtn = document.getElementById('cancelBtn');
        const modalOverlay = document.getElementById('entryModal');

        if (closeModalBtn) {
            closeModalBtn.addEventListener('click', () => this.closeEntryModal());
        }

        if (cancelBtn) {
            cancelBtn.addEventListener('click', () => this.closeEntryModal());
        }

        if (modalOverlay) {
            modalOverlay.addEventListener('click', (e) => {
                if (e.target === modalOverlay) {
                    this.closeEntryModal();
                }
            });
        }

        // Entry form
        const entryForm = document.getElementById('entryForm');
        if (entryForm) {
            entryForm.addEventListener('submit', (e) => this.handleFormSubmit(e));
        }

        // Health rating slider
        const healthRating = document.getElementById('healthRating');
        const ratingValue = document.getElementById('ratingValue');

        if (healthRating && ratingValue) {
            healthRating.addEventListener('input', () => {
                ratingValue.textContent = healthRating.value;
            });
        }

        // File upload preview
        const photoInput = document.getElementById('entryPhoto');
        if (photoInput) {
            photoInput.addEventListener('change', (e) => this.handlePhotoPreview(e));
        }

        // Entry details panel
        const closeDetailsBtn = document.getElementById('closeDetailsBtn');
        if (closeDetailsBtn) {
            closeDetailsBtn.addEventListener('click', () => this.closeEntryDetails());
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => this.handleKeyboardShortcuts(e));

        // Recompute overlay positions on resize (debounced)
        window.addEventListener('resize', this.debounce(() => {
            try {
                this.renderEntryCardsOverlay();
            } catch (err) {
                console.error('Error re-rendering overlay on resize:', err);
            }
        }, 150));

        console.log('✅ Event listeners set up successfully');
    }

    navigateMonth(direction) {
        console.log(`📅 Navigating ${direction > 0 ? 'next' : 'previous'} month`);

        this.currentDate.setMonth(this.currentDate.getMonth() + direction);
        this.renderCalendar();
        this.updateStatistics();
    }

    renderCalendar() {
        console.log('📅 Rendering calendar...', {
            currentDate: this.currentDate,
            month: this.currentDate.getMonth(),
            year: this.currentDate.getFullYear()
        });

        const calendarGrid = document.getElementById('calendarGrid');
        const monthTitle = document.getElementById('calendarMonth');

        if (!calendarGrid || !monthTitle) {
            console.error('❌ Calendar elements not found', {
                calendarGrid: !!calendarGrid,
                monthTitle: !!monthTitle
            });

            // Try to find elements and show more debug info
            const allElements = document.querySelectorAll('[id*="calendar"]');
            console.log('📋 All calendar-related elements found:', Array.from(allElements).map(el => el.id));
            return;
        }

        // Update month title
        const monthNames = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ];

        monthTitle.textContent = `${monthNames[this.currentDate.getMonth()]} ${this.currentDate.getFullYear()}`;

        // Clear existing calendar
        calendarGrid.innerHTML = '';

        // Add day headers
        const dayHeaders = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        dayHeaders.forEach(day => {
            const dayHeader = document.createElement('div');
            dayHeader.className = 'myavana-calendar-day-header';
            dayHeader.textContent = day;
            calendarGrid.appendChild(dayHeader);
        });

        // Calculate calendar days
        const year = this.currentDate.getFullYear();
        const month = this.currentDate.getMonth();
        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);
        const startDate = new Date(firstDay);
        startDate.setDate(startDate.getDate() - firstDay.getDay());

        const today = new Date();
        today.setHours(0, 0, 0, 0);

        // Generate 42 days (6 weeks)
        console.log('📅 Generating calendar days...', {
            startDate: startDate.toDateString(),
            firstDay: firstDay.toDateString(),
            month: month,
            today: today.toDateString()
        });

        for (let i = 0; i < 42; i++) {
            const cellDate = new Date(startDate);
            cellDate.setDate(startDate.getDate() + i);

            const dayElement = this.createCalendarDay(cellDate, today, month);
            calendarGrid.appendChild(dayElement);
        }

        console.log('✅ Calendar rendered successfully with', calendarGrid.children.length, 'elements');
        console.log('📐 Calendar grid computed style:', window.getComputedStyle(calendarGrid).display);
        console.log('📐 Grid template columns:', window.getComputedStyle(calendarGrid).gridTemplateColumns);
        console.log('📐 Grid template rows:', window.getComputedStyle(calendarGrid).gridTemplateRows);

        // Render floating entry cards overlay only if enabled
        if (this.useFloatingOverlay) {
            try {
                this.renderEntryCardsOverlay();
            } catch (err) {
                console.error('Error rendering entry cards overlay:', err);
            }
        }
    }

    /**
     * Render floating entry cards above the calendar grid with SVG connectors to each date cell.
     * Cards are arranged horizontally around the date column so they don't stack on top of each other.
     */
    renderEntryCardsOverlay() {
        const calendarGrid = document.getElementById('calendarGrid');
        if (!calendarGrid) return;

        // Container should be the calendar view so overlay remains inside the calendar area
        const calendarView = document.getElementById('calendarView') || calendarGrid.parentElement || document.body;
        if (window.getComputedStyle(calendarView).position === 'static') {
            calendarView.style.position = 'relative';
        }

        // Remove existing overlay if present
        const existing = document.getElementById('myavanaCalendarOverlay');
        if (existing) existing.remove();

        // Dimensions and offsets relative to calendarView
        const overlayExtra = 120; // space above grid for cards
        const gridOffsetLeft = calendarGrid.offsetLeft;
        const gridOffsetTop = calendarGrid.offsetTop;
        const gridWidth = calendarGrid.offsetWidth;
        const gridHeight = calendarGrid.offsetHeight;

        // Create overlay container positioned inside calendarView
        const overlay = document.createElement('div');
        overlay.id = 'myavanaCalendarOverlay';
        overlay.style.position = 'absolute';
        overlay.style.left = `${gridOffsetLeft}px`;
        overlay.style.top = `${Math.max(0, gridOffsetTop - overlayExtra)}px`;
        overlay.style.width = `${gridWidth}px`;
        overlay.style.height = `${overlayExtra + gridHeight}px`;
        overlay.style.pointerEvents = 'none';
        overlay.style.zIndex = 40;
        calendarView.appendChild(overlay);

        // Create SVG for connectors
        const svgNS = 'http://www.w3.org/2000/svg';
        const svg = document.createElementNS(svgNS, 'svg');
        svg.setAttribute('class', 'myavana-calendar-connectors');
        svg.setAttribute('width', '100%');
        svg.setAttribute('height', '100%');
        svg.style.position = 'absolute';
        svg.style.left = '0';
        svg.style.top = '0';
        svg.style.pointerEvents = 'none';
        overlay.appendChild(svg);

        // Group entries by date
        const groups = {};
        this.entries.forEach(entry => {
            if (!entry || !entry.date) return;
            groups[entry.date] = groups[entry.date] || [];
            groups[entry.date].push(entry);
        });

        const cardW = 160;
        const cardH = 88;
        const spacing = 12;
        const overlayTopPadding = 8;

        Object.keys(groups).forEach(dateStr => {
            const dayEntries = groups[dateStr];
            if (!dayEntries || dayEntries.length === 0) return;

            const cell = calendarGrid.querySelector(`[data-date="${dateStr}"]`);
            if (!cell) return;

            const cellRect = cell.getBoundingClientRect();
            const gridRectNow = calendarGrid.getBoundingClientRect();
            // cell positions relative to calendarGrid
            const cellLeftRel = cellRect.left - gridRectNow.left;
            const cellTopRel = cellRect.top - gridRectNow.top;
            const cellCenterX = cellLeftRel + (cellRect.width / 2);
            const cellTopInOverlay = overlayExtra + cellTopRel; // top of cell relative to overlay

            // Arrange horizontally centered around the cell center
            const n = dayEntries.length;
            const maxShow = Math.min(n, 5); // cap cards shown to 5 to avoid overflow
            const totalWidth = (maxShow * cardW) + ((maxShow - 1) * spacing);
            const startX = cellCenterX - (totalWidth / 2);

            for (let i = 0; i < Math.min(n, maxShow); i++) {
                const entry = dayEntries[i];

                const left = Math.round(startX + i * (cardW + spacing));
                const top = overlayTopPadding; // fixed top so cards align in a single row above the grid

                // clamp within overlay
                const overlayWidth = overlay.offsetWidth || calendarView.clientWidth || document.documentElement.clientWidth;
                const clampedLeft = Math.max(8, Math.min(left, overlayWidth - cardW - 8));

                // Card element
                const card = document.createElement('div');
                card.className = 'journey-entry myavana-floating-entry';
                card.style.position = 'absolute';
                card.style.left = `${clampedLeft}px`;
                card.style.top = `${top}px`;
                card.style.width = `${cardW}px`;
                card.style.height = `${cardH}px`;
                card.style.boxShadow = '0 6px 20px rgba(0,0,0,0.12)';
                card.style.borderRadius = '10px';
                card.style.overflow = 'hidden';
                card.style.background = '#fff';
                card.style.pointerEvents = 'auto';
                card.style.cursor = 'pointer';

                // Image area
                const imageUrl = entry.image || entry.thumbnail || '';
                const safeBg = imageUrl ? `url('${encodeURI(imageUrl)}')` : "url('data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22%3E%3Crect fill=%22%23e7a690%22 width=%22100%22 height=%22100%22/%3E%3Ccircle cx=%2250%22 cy=%2250%22 r=%2230%22 fill=%22%23fce5d7%22/%3E%3C/svg%3E')";
                const img = document.createElement('div');
                img.className = 'entry-image';
                img.style.width = '100%';
                img.style.height = '100%';
                img.style.float = 'left';
                img.style.background = `linear-gradient(135deg, rgba(231,166,144,0.3), rgba(74,77,104,0.3)), ${safeBg}`;
                img.style.backgroundSize = 'cover';
                img.style.backgroundPosition = 'center';
                img.style.zIndex = '0';

                // Content
                const content = document.createElement('div');
                content.className = 'entry-content';
                content.style.padding = '8px 10px';
                content.style.width = '60%';
                content.style.boxSizing = 'border-box';
                img.style.zIndex = '1';

                const timeEl = document.createElement('div');
                timeEl.className = 'entry-time';
                timeEl.style.fontSize = '12px';
                timeEl.style.color = '#666';
                timeEl.textContent = entry.formatted_time || entry.time || '';

                const titleEl = document.createElement('div');
                titleEl.className = 'entry-title';
                titleEl.style.fontWeight = '400';
                titleEl.style.fontSize = '7px !important';
                titleEl.style.marginTop = '4px';
                titleEl.textContent = entry.title || 'Entry';

                const subtitleEl = document.createElement('div');
                subtitleEl.className = 'entry-subtitle';
                subtitleEl.style.fontSize = '12px';
                subtitleEl.style.color = '#888';
                subtitleEl.style.marginTop = '6px';
                subtitleEl.textContent = entry.subtitle || entry.products || '';

                const tagsEl = document.createElement('div');
                tagsEl.className = 'entry-tags';
                tagsEl.style.marginTop = '6px';
                if (entry.tags && Array.isArray(entry.tags)) {
                    entry.tags.slice(0,2).forEach(t => {
                        const tag = document.createElement('span');
                        tag.className = 'entry-tag';
                        tag.style.display = 'inline-block';
                        tag.style.marginRight = '6px';
                        tag.style.fontSize = '11px';
                        tag.style.color = '#444';
                        tag.textContent = t;
                        tagsEl.appendChild(tag);
                    });
                }

                content.appendChild(timeEl);
                content.appendChild(titleEl);
                content.appendChild(subtitleEl);
                content.appendChild(tagsEl);

                card.appendChild(img);
                card.appendChild(content);

                // Click behavior
                card.addEventListener('click', (e) => {
                    e.stopPropagation();
                    if (window.myavanaHairDiary) window.myavanaHairDiary.showSingleEntry(entry.id);
                });

                overlay.appendChild(card);

                // Connector from card bottom center to date cell top center (positions relative to overlay container)
                const x1 = clampedLeft + (cardW / 2);
                const y1 = top + cardH; // card bottom relative to overlay
                const x2 = cellCenterX;
                const y2 = cellTopInOverlay; // top of cell relative to overlay

                const line = document.createElementNS(svgNS, 'line');
                line.setAttribute('x1', x1);
                line.setAttribute('y1', y1);
                line.setAttribute('x2', x2);
                line.setAttribute('y2', y2);
                line.setAttribute('stroke', 'rgba(0,0,0,0.12)');
                line.setAttribute('stroke-width', '2');
                line.setAttribute('stroke-linecap', 'round');
                svg.appendChild(line);
            }
        });
    }
    
    createCalendarDay(date, today, currentMonth) {
        const dayElement = document.createElement('div');
        dayElement.className = 'myavana-calendar-day';
        dayElement.dataset.date = this.formatDate(date);

        function formatMood(mood) {
            const moods = {
                excited: '😊',
                happy: '😄',
                content: '😌',
                neutral: '😐',
                concerned: '😟',
                frustrated: '😤'
            };
            return moods[mood] || mood;
        }

        // Add classes based on date properties
        if (date.getMonth() !== currentMonth) {
            dayElement.classList.add('other-month');
        }

        if (date.getTime() === today.getTime()) {
            dayElement.classList.add('today');
        }

        // Check for entries on this date
        const dayEntries = this.getEntriesForDate(date);
        if (dayEntries.length > 0) {
            dayElement.classList.add('has-entries');
        }

        // Day number
        const dayNumber = document.createElement('div');
        dayNumber.className = 'myavana-calendar-day-number';
        dayNumber.textContent = date.getDate();
        dayElement.appendChild(dayNumber);

        // Ensure the day cell has extra vertical space for stacked cards
        dayElement.style.minHeight = `${this.dayCellMinHeight}px`;

        // In-cell stacked entry cards
        if (dayEntries.length > 0) {
            const stackedContainer = document.createElement('div');
            stackedContainer.className = 'myavana-calendar-stacked-entries';
            stackedContainer.style.display = 'flex';
            stackedContainer.style.flexDirection = 'column';
            stackedContainer.style.gap = '8px';
            stackedContainer.style.marginTop = '8px';

            // Render all entries (or we could cap if desired)
            dayEntries.forEach(entry => {
                const card = document.createElement('div');
                card.className = 'myavana-calendar-entry-card journey-entry';
                card.style.textAlign = 'center';
                card.style.zIndex = '30'
                card.style.marginTop = '6px';
                // card.style.display = 'flex';
                // card.style.alignItems = 'center';
                // card.style.justifyContent = 'center';
                // card.style.gap = '8px';
                // card.style.padding = '6px';
                // card.style.borderRadius = '8px';
                // card.style.background = '#fff';
                // card.style.boxShadow = '0 6px 14px rgba(0,0,0,0.06)';
                // card.style.cursor = 'pointer';
                // card.style.border = '1px solid rgba(0,0,0,0.03)';

                const img = document.createElement('div');
                img.className = 'entry-image';
                // img.style.width = '48px';
                // img.style.height = '48px';
                img.style.borderRadius = '6px';
                // img.style.flex = '0 0 48px';
                img.style.backgroundSize = 'cover';
                img.style.backgroundPosition = 'center';
                const imageUrl = entry.image || entry.thumbnail || '';
                img.style.backgroundImage = imageUrl ? `url('${encodeURI(imageUrl)}')` : "url('data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22%3E%3Crect fill=%22%23e7a690%22 width=%22100%22 height=%22100%22/%3E%3Ccircle cx=%2250%22 cy=%2250%22 r=%2230%22 fill=%22%23fce5d7%22/%3E%3C/svg%3E')";

                const meta = document.createElement('div');
                meta.style.flex = '1';

                const t = document.createElement('div');
                t.className = 'entry-title';
                t.style.fontWeight = '400';
                // t.style.fontSize = '10px';
                t.textContent = entry.title.substring(0, 40) + '..' || 'Entry';

                // const sub = document.createElement('div');
                // sub.className = 'entry-subtitle';
                // sub.style.fontSize = '7px';
                // sub.style.color = '#666';
                // sub.textContent = entry.subtitle || entry.products || '';

                const lastRow = document.createElement('div');
                lastRow.className = 'spacer-row';

                const timeEl = document.createElement('div');
                timeEl.className = 'entry-time';
                timeEl.style.fontSize = '8px';
                // timeEl.style.color = '#888';
                timeEl.textContent = entry.formatted_time || entry.time || '2:30 PM';
                
                const mood = document.createElement('div');
                mood.className = 'myavana-metric-value'
                mood.textContent = formatMood(entry.mood) || 'Not specified'

                meta.appendChild(t);
                lastRow.appendChild(timeEl)
                lastRow.appendChild(mood)
                // meta.appendChild(sub);
                meta.appendChild(lastRow);

                card.appendChild(img);
                card.appendChild(meta);

                card.addEventListener('click', (e) => {
                    e.stopPropagation();
                    if (window.myavanaHairDiary) window.myavanaHairDiary.showSingleEntry(entry.id);
                });

                stackedContainer.appendChild(card);
            });

            dayElement.appendChild(stackedContainer);
        }

        // Click handler
        dayElement.addEventListener('click', () => {
            if (date.getMonth() !== currentMonth) {
                // Navigate to the clicked month
                this.currentDate = new Date(date);
                this.renderCalendar();
            } else {
                this.selectDate(date);
            }
        });

        return dayElement;
    }

    selectDate(date) {
        console.log('📅 Date selected:', this.formatDate(date));

        this.selectedDate = new Date(date);

        // Update visual selection
        const prevSelected = document.querySelector('.myavana-calendar-day.selected');
        if (prevSelected) {
            prevSelected.classList.remove('selected');
        }

        const selectedDay = document.querySelector(`[data-date="${this.formatDate(date)}"]`);
        if (selectedDay) {
            selectedDay.classList.add('selected');
        }

        // Show entries for this date
        const dayEntries = this.getEntriesForDate(date);
        if (dayEntries.length > 0) {
            this.showEntryDetails(dayEntries);
        } else {
            this.openEntryModal(date);
        }
    }

    getEntriesForDate(date) {
        const dateStr = this.formatDate(date);
        // Ensure entries is always an array before filtering
        if (!Array.isArray(this.entries)) {
            console.warn('⚠️ this.entries is not an array:', typeof this.entries, this.entries);
            this.entries = [];
        }
        return this.entries.filter(entry => entry.date === dateStr);
    }

    formatDate(date) {
        return date.toISOString().split('T')[0];
    }

    async loadEntries() {
        console.log('📥 Loading entries...');

        if (this.isLoading) return;

        this.isLoading = true;
        this.showLoadingState(true);

        try {
            const response = await fetch(this.config.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: this.config.actions.get_entries,
                    nonce: this.config.nonces.get_entries,
                    user_id: this.config.user_id
                })
            });

            const result = await response.json();

            if (result.success) {
                console.log('📥 Raw AJAX response:', result);

                // The handler returns entries directly in result.data (not result.data.entries)
                if (result.data && Array.isArray(result.data)) {
                    this.entries = result.data;
                    console.log(`✅ Loaded ${this.entries.length} real entries from database`);

                    // Log sample entry for debugging
                    if (this.entries.length > 0) {
                        console.log('📝 Sample entry:', this.entries[0]);
                    }
                } else {
                    this.entries = [];
                    console.log('⚠️ No entry data found in response or data is not an array:', result.data);
                }

                this.renderCalendar();
                this.updateStatistics();
            } else {
                // If request fails, initialize with empty array
                this.entries = [];
                console.log('⚠️ AJAX request failed:', result.data);
                this.renderCalendar();
                this.updateStatistics();
                this.showToast('Failed to load entries: ' + (result.data || 'Unknown error'), 'error');
            }

        } catch (error) {
            console.error('❌ Error loading entries:', error);
            this.showToast('Failed to load entries. Please refresh the page.', 'error');
        } finally {
            this.isLoading = false;
            this.showLoadingState(false);
        }
    }

    updateStatistics() {
        console.log('📊 Updating statistics...');

        // Ensure entries is always an array before calculating statistics
        if (!Array.isArray(this.entries)) {
            console.warn('⚠️ this.entries is not an array in updateStatistics:', typeof this.entries, this.entries);
            this.entries = [];
        }

        const totalEntries = this.entries.length;
        const currentMonth = this.currentDate.getMonth();
        const currentYear = this.currentDate.getFullYear();

        const thisMonthEntries = this.entries.filter(entry => {
            const entryDate = new Date(entry.date);
            return entryDate.getMonth() === currentMonth && entryDate.getFullYear() === currentYear;
        }).length;

        const totalRatings = this.entries.reduce((sum, entry) => sum + (entry.health_rating || 0), 0);
        const averageRating = totalEntries > 0 ? (totalRatings / totalEntries).toFixed(1) : '0.0';

        // Calculate streak (consecutive days with entries)
        const sortedDates = [...new Set(this.entries.map(entry => entry.date))].sort();
        const currentStreak = this.calculateStreak(sortedDates);

        // Update DOM
        this.updateStatElement('totalEntries', totalEntries);
        this.updateStatElement('thisMonthEntries', thisMonthEntries);
        this.updateStatElement('averageRating', averageRating);
        this.updateStatElement('currentStreak', currentStreak);

        console.log('✅ Statistics updated');
    }

    updateStatElement(elementId, value) {
        const element = document.getElementById(elementId);
        if (element) {
            // Animate number change
            const currentValue = parseInt(element.textContent) || 0;
            if (currentValue !== value) {
                this.animateNumber(element, currentValue, value);
            }
        }
    }

    animateNumber(element, from, to) {
        const duration = 500;
        const steps = 20;
        const increment = (to - from) / steps;
        let current = from;
        let step = 0;

        const timer = setInterval(() => {
            step++;
            current += increment;
            element.textContent = typeof to === 'string' ? current.toFixed(1) : Math.round(current);

            if (step >= steps) {
                clearInterval(timer);
                element.textContent = to;
            }
        }, duration / steps);
    }

    calculateStreak(sortedDates) {
        if (sortedDates.length === 0) return 0;

        let streak = 0;
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        // Work backwards from today
        let checkDate = new Date(today);

        for (let i = 0; i < 30; i++) { // Check last 30 days max
            const checkDateStr = this.formatDate(checkDate);

            if (sortedDates.includes(checkDateStr)) {
                streak++;
                checkDate.setDate(checkDate.getDate() - 1);
            } else {
                break;
            }
        }

        return streak;
    }

    openEntryModal(date = null, entryType = null) {
        console.log('📝 Opening entry modal...', { date, entryType });

        const modal = document.getElementById('entryModal');
        const modalTitle = document.getElementById('modalTitle');
        const form = document.getElementById('entryForm');

        console.log('🔍 Modal elements check:', {
            modal: !!modal,
            modalTitle: !!modalTitle,
            form: !!form,
            modalDisplay: modal ? modal.style.display : 'null',
            modalClasses: modal ? modal.className : 'null'
        });

        if (!modal || !form) {
            console.error('❌ Modal elements not found', {
                modal: !!modal,
                modalTitle: !!modalTitle,
                form: !!form
            });
            return;
        }

        // Reset form
        form.reset();
        this.currentEntryId = null;

        // Set default values
        if (date) {
            document.getElementById('entryDate').value = this.formatDate(date);
        } else if (this.selectedDate) {
            document.getElementById('entryDate').value = this.formatDate(this.selectedDate);
        } else {
            document.getElementById('entryDate').value = this.formatDate(new Date());
        }

        if (entryType) {
            document.getElementById('entryType').value = entryType;
        }

        // Update modal title
        modalTitle.textContent = 'Add Hair Journey Entry';

        // Reset rating display
        const ratingValue = document.getElementById('ratingValue');
        if (ratingValue) {
            ratingValue.textContent = '5';
        }

        // Clear photo preview
        const photoPreview = document.getElementById('photoPreview');
        if (photoPreview) {
            photoPreview.style.display = 'none';
            photoPreview.innerHTML = '';
        }

        // Show modal
        console.log('🚀 Showing modal...');
        modal.classList.add('show');
        modal.style.display = 'flex';

        console.log('✅ Modal should now be visible:', {
            hasShowClass: modal.classList.contains('show'),
            display: modal.style.display,
            visibility: window.getComputedStyle(modal).visibility,
            opacity: window.getComputedStyle(modal).opacity
        });

        // Focus on title input
        const titleInput = document.getElementById('entryTitle');
        if (titleInput) {
            setTimeout(() => titleInput.focus(), 300);
        }
    }

    closeEntryModal() {
        console.log('❌ Closing entry modal...');

        const modal = document.getElementById('entryModal');
        if (modal) {
            modal.classList.remove('show');
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
        }
    }

    async handleFormSubmit(e) {
        e.preventDefault();
        console.log('💾 Submitting form...');

        if (this.isLoading) return;

        const form = e.target;
        const formData = new FormData(form);

        // Add AJAX parameters
        formData.append('action', this.config.actions.save_entry);
        formData.append('nonce', this.config.nonces.save_entry);

        // Validate required fields
        const title = formData.get('title').trim();
        const entryType = formData.get('entry_type');

        if (!title) {
            this.showToast('Please enter a title for your entry.', 'error');
            document.getElementById('entryTitle').focus();
            return;
        }

        if (!entryType) {
            this.showToast('Please select an entry type.', 'error');
            document.getElementById('entryType').focus();
            return;
        }

        this.isLoading = true;
        this.showSaveButton(false);

        try {
            const response = await fetch(this.config.ajax_url, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                console.log('✅ Entry saved successfully');
                this.showToast('Entry saved successfully!', 'success');
                this.closeEntryModal();
                await this.loadEntries(); // Reload entries
            } else {
                throw new Error(result.data || 'Failed to save entry');
            }

        } catch (error) {
            console.error('❌ Error saving entry:', error);
            this.showToast('Failed to save entry. Please try again.', 'error');
        } finally {
            this.isLoading = false;
            this.showSaveButton(true);
        }
    }

    handlePhotoPreview(e) {
        const file = e.target.files[0];
        const preview = document.getElementById('photoPreview');

        if (!preview) return;

        if (file && file.type.startsWith('image/')) {
            const reader = new FileReader();

            reader.onload = (e) => {
                preview.innerHTML = `<img src="${e.target.result}" alt="Photo preview" class="myavana-file-preview-img">`;
                preview.style.display = 'block';
            };

            reader.readAsDataURL(file);
        } else {
            preview.style.display = 'none';
            preview.innerHTML = '';
        }
    }

    showEntryDetails(entries) {
        console.log('👁️ Showing entry details...', entries);

        const detailsPanel = document.getElementById('entryDetails');
        const detailsContent = document.getElementById('entryDetailsContent');
        const detailsTitle = document.getElementById('entryDetailsTitle');

        if (!detailsPanel || !detailsContent || !detailsTitle) {
            console.error('❌ Entry details elements not found');
            return;
        }

        if (entries.length === 1) {
            const entry = entries[0];
            const entryTypeIcon = this.getEntryTypeIcon(entry.entry_type);
            const moodEmoji = this.getMoodEmoji(entry.mood);
            const healthColor = this.getHealthColor(entry.health_rating);

            detailsTitle.innerHTML = `
                <div class="myavana-entry-title-wrapper">
                    <span class="myavana-entry-icon">${entryTypeIcon}</span>
                    <span>${entry.title}</span>
                </div>
            `;

            detailsContent.innerHTML = `
                <div class="myavana-premium-memory-card">
                    <!-- Beautiful Header with Full Image -->
                    <div class="myavana-memory-hero" ${entry.image ? `onclick="window.myavanaHairDiary.openLightbox('${entry.image}', '${entry.title}')" style="cursor: pointer;"` : ''}>
                        ${entry.image ? `
                            <div class="myavana-hero-image-container">
                                <img src="${entry.image}" alt="${entry.title}" class="myavana-hero-image">
                                <div class="myavana-hero-overlay">
                                    <div class="myavana-expand-hint">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="15,3 21,3 21,9"/>
                                            <polyline points="9,21 3,21 3,15"/>
                                            <line x1="21" y1="3" x2="14" y2="10"/>
                                            <line x1="3" y1="21" x2="10" y2="14"/>
                                        </svg>
                                        <span>Click to expand</span>
                                    </div>
                                    <div class="myavana-hero-badge ${entry.entry_type}">
                                        <span class="myavana-badge-icon">${entryTypeIcon}</span>
                                        <span class="myavana-badge-text">${this.formatEntryType(entry.entry_type)}</span>
                                    </div>
                                </div>
                            </div>
                        ` : `
                            <div class="myavana-hero-placeholder ${entry.entry_type}">
                                <div class="myavana-hero-placeholder-content">
                                    <div class="myavana-hero-placeholder-icon">${entryTypeIcon}</div>
                                    <h3 class="myavana-hero-placeholder-title">${this.formatEntryType(entry.entry_type)} Journey</h3>
                                    <p class="myavana-hero-placeholder-subtitle">Your beautiful hair story continues...</p>
                                </div>
                            </div>
                        `}
                    </div>

                    <!-- Premium Content Area -->
                    <div class="myavana-premium-content">
                        <!-- Key Metrics Dashboard -->
                        <div class="myavana-metrics-grid">
                            <div class="myavana-metric-card health">
                                <div class="myavana-metric-icon" style="color: ${healthColor}">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                                    </svg>
                                </div>
                                <div class="myavana-metric-content">
                                    <div class="myavana-metric-value" style="color: ${healthColor}">${entry.health_rating || 'N/A'}</div>
                                    <div class="myavana-metric-label">Health Rating</div>
                                    <div class="myavana-metric-hint">Out of 10</div>
                                </div>
                            </div>

                            <div class="myavana-metric-card mood">
                                <div class="myavana-metric-icon">
                                    <span class="myavana-mood-emoji">${moodEmoji}</span>
                                </div>
                                <div class="myavana-metric-content">
                                    <div class="myavana-metric-value">${this.formatMood(entry.mood) || 'Not specified'}</div>
                                    <div class="myavana-metric-label">Mood</div>
                                    <div class="myavana-metric-hint">How you felt</div>
                                </div>
                            </div>

                            <div class="myavana-metric-card date">
                                <div class="myavana-metric-icon">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                        <line x1="16" y1="2" x2="16" y2="6"/>
                                        <line x1="8" y1="2" x2="8" y2="6"/>
                                        <line x1="3" y1="10" x2="21" y2="10"/>
                                    </svg>
                                </div>
                                <div class="myavana-metric-content">
                                    <div class="myavana-metric-value">${entry.formatted_date || entry.date}</div>
                                    <div class="myavana-metric-label">Date</div>
                                    <div class="myavana-metric-hint">Memory created</div>
                                </div>
                            </div>
                        </div>

                        <!-- Rich Content Sections -->
                        <div class="myavana-content-sections">
                            ${entry.description ? `
                                <div class="myavana-content-section">
                                    <div class="myavana-section-header">
                                        <div class="myavana-section-icon">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/>
                                                <polyline points="14,2 14,8 20,8"/>
                                                <line x1="16" y1="13" x2="8" y2="13"/>
                                                <line x1="16" y1="17" x2="8" y2="17"/>
                                            </svg>
                                        </div>
                                        <h3 class="myavana-section-title">Your Hair Story</h3>
                                    </div>
                                    <div class="myavana-section-content">
                                        <p class="myavana-story-text">${entry.description}</p>
                                    </div>
                                </div>
                            ` : ''}

                            ${entry.products ? `
                                <div class="myavana-content-section">
                                    <div class="myavana-section-header">
                                        <div class="myavana-section-icon">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <circle cx="9" cy="21" r="1"/>
                                                <circle cx="20" cy="21" r="1"/>
                                                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                                            </svg>
                                        </div>
                                        <h3 class="myavana-section-title">Products & Tools</h3>
                                    </div>
                                    <div class="myavana-section-content">
                                        <div class="myavana-product-showcase">
                                            ${entry.products.split(',').map(product =>
                                                `<div class="myavana-product-item">
                                                    <span class="myavana-product-icon">✨</span>
                                                    <span class="myavana-product-name">${product.trim()}</span>
                                                </div>`
                                            ).join('')}
                                        </div>
                                    </div>
                                </div>
                            ` : ''}

                            ${entry.notes ? `
                                <div class="myavana-content-section">
                                    <div class="myavana-section-header">
                                        <div class="myavana-section-icon">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                            </svg>
                                        </div>
                                        <h3 class="myavana-section-title">Notes & Reflections</h3>
                                    </div>
                                    <div class="myavana-section-content">
                                        <div class="myavana-notes-content">
                                            <blockquote class="myavana-reflection-quote">
                                                "${entry.notes}"
                                            </blockquote>
                                        </div>
                                    </div>
                                </div>
                            ` : ''}
                        </div>

                        <!-- Action Buttons -->
                        <div class="myavana-memory-actions">
                            <button class="myavana-action-btn myavana-edit-btn" onclick="window.myavanaHairDiary.editEntry(${entry.id})">
                                <div class="myavana-btn-icon">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                    </svg>
                                </div>
                                <div class="myavana-btn-content">
                                    <span class="myavana-btn-text">Edit Memory</span>
                                    <span class="myavana-btn-hint">Make changes to this entry</span>
                                </div>
                            </button>

                            <button class="myavana-action-btn myavana-share-btn" onclick="window.myavanaHairDiary.shareEntry(${entry.id})">
                                <div class="myavana-btn-icon">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="18" cy="5" r="3"/>
                                        <circle cx="6" cy="12" r="3"/>
                                        <circle cx="18" cy="19" r="3"/>
                                        <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/>
                                        <line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/>
                                    </svg>
                                </div>
                                <div class="myavana-btn-content">
                                    <span class="myavana-btn-text">Share Journey</span>
                                    <span class="myavana-btn-hint">Share this beautiful moment</span>
                                </div>
                            </button>

                            <button class="myavana-action-btn myavana-delete-btn" onclick="window.myavanaHairDiary.deleteEntry(${entry.id})">
                                <div class="myavana-btn-icon">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="3,6 5,6 21,6"/>
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2 2h4a2 2 0 0 1 2 2v2"/>
                                    </svg>
                                </div>
                                <div class="myavana-btn-content">
                                    <span class="myavana-btn-text">Delete</span>
                                    <span class="myavana-btn-hint">Remove this entry</span>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>
            `;
        } else {
            // Multiple entries - Timeline view
            detailsTitle.innerHTML = `
                <div class="myavana-entries-title-wrapper">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3"/>
                        <path d="M12 1v6m0 6v6"/>
                        <path d="M21 12h-6m-6 0H3"/>
                    </svg>
                    <span>${entries.length} Beautiful Memories</span>
                </div>
            `;

            let entriesHTML = entries.map((entry, index) => `
                <div class="myavana-timeline-entry" onclick="window.myavanaHairDiary.showSingleEntry(${entry.id})" style="animation-delay: ${index * 0.1}s">
                    <div class="myavana-timeline-marker ${entry.entry_type}">
                        <span class="myavana-timeline-icon">${this.getEntryTypeIcon(entry.entry_type)}</span>
                    </div>
                    <div class="myavana-timeline-content">
                        <div class="myavana-timeline-header">
                            <h4 class="myavana-timeline-title">${entry.title}</h4>
                            <div class="myavana-timeline-date">${entry.formatted_date}</div>
                        </div>
                        <div class="myavana-timeline-meta">
                            <span class="myavana-timeline-type">${this.formatEntryType(entry.entry_type)}</span>
                            <span class="myavana-timeline-health" style="color: ${this.getHealthColor(entry.health_rating)}">
                                Health: ${entry.health_rating}/10
                            </span>
                            <span class="myavana-timeline-mood">${this.getMoodEmoji(entry.mood)} ${this.formatMood(entry.mood)}</span>
                        </div>
                        ${entry.description ? `<p class="myavana-timeline-description">${entry.description.substring(0, 100)}${entry.description.length > 100 ? '...' : ''}</p>` : ''}
                        ${entry.image ? `
                            <div class="myavana-timeline-thumbnail">
                                <img src="${entry.thumbnail || entry.image}" alt="${entry.title}">
                            </div>
                        ` : ''}
                    </div>
                </div>
            `).join('');

            detailsContent.innerHTML = `
                <div class="myavana-timeline-container">
                    ${entriesHTML}
                    <div class="myavana-timeline-end">
                        <div class="myavana-timeline-end-marker">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                            </svg>
                        </div>
                        <p class="myavana-timeline-end-text">What a beautiful hair journey! ✨</p>
                    </div>
                </div>
            `;
        }

        detailsPanel.style.display = 'block';
        setTimeout(() => detailsPanel.classList.add('show'), 10);
    }

    closeEntryDetails() {
        console.log('❌ Closing entry details...');

        const detailsPanel = document.getElementById('entryDetails');
        if (detailsPanel) {
            detailsPanel.classList.remove('show');
            setTimeout(() => {
                detailsPanel.style.display = 'none';
            }, 300);
        }
    }

    formatEntryType(type) {
        const types = {
            wash: 'Wash Day',
            treatment: 'Treatment',
            styling: 'Styling',
            progress: 'Progress Photo',
            general: 'General'
        };
        return types[type] || type;
    }

    formatMood(mood) {
        const moods = {
            excited: '😊 Excited',
            happy: '😄 Happy',
            content: '😌 Content',
            neutral: '😐 Neutral',
            concerned: '😟 Concerned',
            frustrated: '😤 Frustrated'
        };
        return moods[mood] || mood;
    }

    async editEntry(entryId) {
        console.log('✏️ Editing entry:', entryId);

        try {
            const response = await fetch(this.config.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: this.config.actions.get_entry,
                    nonce: this.config.nonces.get_entry,
                    entry_id: entryId
                })
            });

            const result = await response.json();

            if (result.success) {
                this.populateEditForm(result.data);
            } else {
                throw new Error(result.data || 'Failed to load entry');
            }

        } catch (error) {
            console.error('❌ Error loading entry for editing:', error);
            this.showToast('Failed to load entry for editing.', 'error');
        }
    }

    populateEditForm(entry) {
        console.log('📝 Populating edit form...', entry);

        this.currentEntryId = entry.id;

        // Populate form fields
        document.getElementById('entryId').value = entry.id;
        document.getElementById('entryTitle').value = entry.title;
        document.getElementById('entryType').value = entry.entry_type;
        document.getElementById('entryDescription').value = entry.description || '';
        document.getElementById('entryDate').value = entry.date;
        document.getElementById('healthRating').value = entry.health_rating;
        document.getElementById('ratingValue').textContent = entry.health_rating;
        document.getElementById('moodRating').value = entry.mood;
        document.getElementById('productsUsed').value = entry.products || '';
        document.getElementById('entryNotes').value = entry.notes || '';

        // Update modal title
        document.getElementById('modalTitle').textContent = 'Edit Hair Journey Entry';

        // Show image preview if exists
        if (entry.image) {
            const preview = document.getElementById('photoPreview');
            if (preview) {
                preview.innerHTML = `<img src="${entry.image}" alt="Current photo">`;
                preview.style.display = 'block';
            }
        }

        // Close details panel and open modal
        this.closeEntryDetails();
        document.getElementById('entryModal').classList.add('show');
        document.getElementById('entryModal').style.display = 'flex';
    }

    async deleteEntry(entryId) {
        if (!confirm('Are you sure you want to delete this entry? This action cannot be undone.')) {
            return;
        }

        console.log('🗑️ Deleting entry:', entryId);

        try {
            const response = await fetch(this.config.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: this.config.actions.delete_entry,
                    nonce: this.config.nonces.delete_entry,
                    entry_id: entryId
                })
            });

            const result = await response.json();

            if (result.success) {
                console.log('✅ Entry deleted successfully');
                this.showToast('Entry deleted successfully!', 'success');
                this.closeEntryDetails();
                await this.loadEntries(); // Reload entries
            } else {
                throw new Error(result.data || 'Failed to delete entry');
            }

        } catch (error) {
            console.error('❌ Error deleting entry:', error);
            this.showToast('Failed to delete entry. Please try again.', 'error');
        }
    }

    handleKeyboardShortcuts(e) {
        // Escape key closes modals
        if (e.key === 'Escape') {
            const modal = document.getElementById('entryModal');
            const details = document.getElementById('entryDetails');

            if (modal && modal.classList.contains('show')) {
                this.closeEntryModal();
            } else if (details && details.classList.contains('show')) {
                this.closeEntryDetails();
            }
        }

        // Ctrl/Cmd + N opens new entry modal
        if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
            e.preventDefault();
            this.openEntryModal();
        }
    }

    showLoadingState(show) {
        // Could add loading spinners or skeleton screens here
        console.log(show ? '⏳ Loading...' : '✅ Loading complete');
    }

    showSaveButton(enabled) {
        const saveBtn = document.getElementById('saveBtn');
        const btnText = saveBtn?.querySelector('.myavana-btn-text');
        const btnLoading = saveBtn?.querySelector('.myavana-btn-loading');

        if (saveBtn) {
            saveBtn.disabled = !enabled;

            if (btnText) btnText.style.display = enabled ? 'inline' : 'none';
            if (btnLoading) btnLoading.style.display = enabled ? 'none' : 'inline-flex';
        }
    }

    showToast(message, type = 'info') {
        console.log(`🍞 Toast: ${message} (${type})`);

        const container = document.getElementById('toastContainer');
        if (!container) return;

        const toast = document.createElement('div');
        toast.className = `myavana-toast ${type}`;

        const icon = {
            success: '✅',
            error: '❌',
            info: 'ℹ️'
        }[type] || 'ℹ️';

        toast.innerHTML = `
            <div style="display: flex; align-items: center; gap: 8px;">
                <span>${icon}</span>
                <span>${message}</span>
            </div>
        `;

        container.appendChild(toast);

        // Show toast
        setTimeout(() => toast.classList.add('show'), 100);

        // Auto-hide after 4 seconds
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
                if (toast.parentNode) {
                    container.removeChild(toast);
                }
            }, 300);
        }, 4000);
    }

    // Helper functions for visual elements
    getEntryTypeIcon(type) {
        const icons = {
            'wash_day': '🚿',
            'treatment': '💆‍♀️',
            'styling': '💇‍♀️',
            'progress': '📈',
            'general': '✨',
            'protective': '🛡️',
            'product_test': '🧪',
            'consultation': '👩‍⚕️'
        };
        return icons[type] || '✨';
    }

    // Simple debounce helper
    debounce(fn, wait) {
        let t = null;
        return function(...args) {
            clearTimeout(t);
            t = setTimeout(() => fn.apply(this, args), wait);
        };
    }

    getMoodEmoji(mood) {
        const moods = {
            'excellent': '😍',
            'great': '😊',
            'good': '🙂',
            'okay': '😐',
            'bad': '😟',
            'terrible': '😢',
            'confident': '💪',
            'frustrated': '😤',
            'excited': '🤩',
            'hopeful': '🌟'
        };
        return moods[mood] || '🙂';
    }

    getHealthColor(rating) {
        const colors = {
            1: '#ff4757', // Red - Poor
            2: '#ff6b81', // Light Red
            3: '#ff9f43', // Orange
            4: '#ffa726', // Light Orange
            5: '#ffb300', // Yellow
            6: '#d4af37', // Gold
            7: '#8bc34a', // Light Green
            8: '#4caf50', // Green
            9: '#2e7d32', // Dark Green
            10: '#1b5e20' // Very Dark Green - Excellent
        };
        return colors[rating] || '#757575';
    }

    formatMood(mood) {
        if (!mood) return 'Not specified';
        return mood.charAt(0).toUpperCase() + mood.slice(1).replace('_', ' ');
    }

    // Function to show single entry (called from timeline)
    showSingleEntry(entryId) {
        const entry = this.entries.find(e => e.id == entryId);
        if (entry) {
            this.showEntryDetails([entry]);
        }
    }

    // Lightbox functionality for beautiful image viewing
    openLightbox(imageSrc, title) {
        console.log('🖼️ Opening lightbox for image:', imageSrc);

        // Create lightbox if it doesn't exist
        let lightbox = document.getElementById('myavanaLightbox');
        if (!lightbox) {
            lightbox = document.createElement('div');
            lightbox.id = 'myavanaLightbox';
            lightbox.className = 'myavana-lightbox';
            lightbox.innerHTML = `
                <div class="myavana-lightbox-overlay" onclick="window.myavanaHairDiary.closeLightbox()"></div>
                <div class="myavana-lightbox-content">
                    <div class="myavana-lightbox-header">
                        <h3 class="myavana-lightbox-title"></h3>
                        <button class="myavana-lightbox-close" onclick="window.myavanaHairDiary.closeLightbox()">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="18" y1="6" x2="6" y2="18"/>
                                <line x1="6" y1="6" x2="18" y2="18"/>
                            </svg>
                        </button>
                    </div>
                    <div class="myavana-lightbox-image-container">
                        <img class="myavana-lightbox-image" alt="">
                    </div>
                    <div class="myavana-lightbox-actions">
                        <button class="myavana-lightbox-btn" onclick="window.myavanaHairDiary.downloadImage()">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                <polyline points="7,10 12,15 17,10"/>
                                <line x1="12" y1="15" x2="12" y2="3"/>
                            </svg>
                            Download
                        </button>
                    </div>
                </div>
            `;
            document.body.appendChild(lightbox);
        }

        // Update content
        lightbox.querySelector('.myavana-lightbox-title').textContent = title;
        lightbox.querySelector('.myavana-lightbox-image').src = imageSrc;
        lightbox.querySelector('.myavana-lightbox-image').alt = title;
        lightbox.currentImageSrc = imageSrc;

        // Show lightbox
        lightbox.style.display = 'flex';
        setTimeout(() => lightbox.classList.add('show'), 10);

        // Prevent body scroll
        document.body.style.overflow = 'hidden';
    }

    closeLightbox() {
        console.log('❌ Closing lightbox...');
        const lightbox = document.getElementById('myavanaLightbox');
        if (lightbox) {
            lightbox.classList.remove('show');
            setTimeout(() => {
                lightbox.style.display = 'none';
                document.body.style.overflow = '';
            }, 300);
        }
    }

    downloadImage() {
        const lightbox = document.getElementById('myavanaLightbox');
        if (lightbox && lightbox.currentImageSrc) {
            const link = document.createElement('a');
            link.href = lightbox.currentImageSrc;
            link.download = 'myavana-hair-journey-photo.jpg';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            this.showToast('Photo downloaded! 📸', 'success');
        }
    }

    // Share entry functionality
    shareEntry(entryId) {
        console.log('📤 Sharing entry:', entryId);
        const entry = this.entries.find(e => e.id == entryId);

        if (!entry) {
            this.showToast('Entry not found', 'error');
            return;
        }

        const shareText = `Check out my hair journey progress! "${entry.title}" - Health Rating: ${entry.health_rating}/10 ${this.getMoodEmoji(entry.mood)}`;

        if (navigator.share) {
            // Use native sharing if available
            navigator.share({
                title: `MYAVANA Hair Journey: ${entry.title}`,
                text: shareText,
                url: window.location.href
            }).then(() => {
                this.showToast('Shared successfully! 🎉', 'success');
            }).catch(() => {
                this.fallbackShare(shareText);
            });
        } else {
            this.fallbackShare(shareText);
        }
    }

    fallbackShare(text) {
        // Fallback to clipboard copy
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(() => {
                this.showToast('Share text copied to clipboard! 📋', 'success');
            }).catch(() => {
                this.showShareModal(text);
            });
        } else {
            this.showShareModal(text);
        }
    }

    showShareModal(text) {
        // Create a simple share modal
        const modal = document.createElement('div');
        modal.className = 'myavana-share-modal';
        modal.innerHTML = `
            <div class="myavana-share-overlay" onclick="this.parentElement.remove()"></div>
            <div class="myavana-share-content">
                <h3>Share Your Hair Journey</h3>
                <textarea readonly class="myavana-share-text">${text}</textarea>
                <div class="myavana-share-actions">
                    <button onclick="this.parentElement.parentElement.parentElement.remove()" class="myavana-btn-secondary">Close</button>
                    <button onclick="document.querySelector('.myavana-share-text').select(); document.execCommand('copy'); this.textContent='Copied!'" class="myavana-btn-primary">Copy Text</button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
        setTimeout(() => modal.classList.add('show'), 10);
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    console.log('🚀 DOM ready, initializing Hair Diary...');
    window.myavanaHairDiary = new MyavanaHairDiary();
});

// Also try initializing after window load as fallback
window.addEventListener('load', () => {
    if (!window.myavanaHairDiary) {
        console.log('🔄 Fallback initialization after window load...');
        window.myavanaHairDiary = new MyavanaHairDiary();
    }
});

// Debug functions for manual testing
window.debugMyavanaCalendar = {
    renderCalendar: () => {
        if (window.myavanaHairDiary) {
            console.log('🐛 Manual calendar render triggered');
            window.myavanaHairDiary.renderCalendar();
        } else {
            console.error('❌ Hair Diary instance not found');
        }
    },

    checkElements: () => {
        const elements = {
            calendarGrid: document.getElementById('calendarGrid'),
            calendarMonth: document.getElementById('calendarMonth'),
            prevMonthBtn: document.getElementById('prevMonthBtn'),
            nextMonthBtn: document.getElementById('nextMonthBtn')
        };

        console.log('🔍 Element check:', elements);
        return elements;
    },

    initializeManually: () => {
        console.log('🔧 Manual initialization...');
        window.myavanaHairDiary = new MyavanaHairDiary();
    }
};

// Export for global access
window.MyavanaHairDiary = MyavanaHairDiary;