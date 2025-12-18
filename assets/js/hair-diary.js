/**
 * Hair Journey Diary JavaScript
 * Interactive calendar-diary functionality
 */

class HairJourneyDiary {
    constructor() {
        this.currentView = myavanaDiary.view || 'calendar';
        this.calendar = null;
        this.selectedDate = new Date();
        this.entries = [];
        this.currentEntry = null;
        this.filters = {
            dateRange: 'all',
            mood: 'all',
            health: 'all'
        };
        
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.loadDiaryStats();
        this.setupViewSwitching();
        this.initializePhotoUpload();
        this.loadEntries();
        
        // Initialize calendar with delay to ensure FullCalendar is loaded
        setTimeout(() => {
            this.initializeCalendar();
        }, 100);
        
        // If calendar still hasn't loaded after 3 seconds, show fallback
        setTimeout(() => {
            const calendarEl = document.getElementById('calendar');
            if (calendarEl && calendarEl.innerHTML.trim() === '') {
                console.warn('Calendar empty after 3 seconds, showing fallback');
                this.showFallbackCalendar();
            }
        }, 3000);
    }
    
    bindEvents() {
        // Header actions
        document.getElementById('addEntryBtn')?.addEventListener('click', () => {
            this.openEntryModal();
        });
        
        document.getElementById('todayBtn')?.addEventListener('click', () => {
            this.goToToday();
        });
        
        // Modal events
        document.getElementById('closeEntryModal')?.addEventListener('click', () => {
            this.closeEntryModal();
        });
        
        document.getElementById('closeDetailModal')?.addEventListener('click', () => {
            this.closeDetailModal();
        });
        
        document.getElementById('cancelEntry')?.addEventListener('click', () => {
            this.closeEntryModal();
        });
        
        // Form submission
        document.getElementById('entryForm')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.saveEntry();
        });
        
        // Calendar navigation
        document.getElementById('prevMonth')?.addEventListener('click', () => {
            this.calendar?.prev();
        });
        
        document.getElementById('nextMonth')?.addEventListener('click', () => {
            this.calendar?.next();
        });
        
        // Entry actions
        document.getElementById('editEntryBtn')?.addEventListener('click', () => {
            this.editCurrentEntry();
        });
        
        document.getElementById('deleteEntryBtn')?.addEventListener('click', () => {
            this.deleteCurrentEntry();
        });
        
        // Filters
        document.getElementById('dateFilter')?.addEventListener('change', (e) => {
            this.filters.dateRange = e.target.value;
            this.applyFilters();
        });
        
        document.getElementById('moodFilter')?.addEventListener('change', (e) => {
            this.filters.mood = e.target.value;
            this.applyFilters();
        });
        
        document.getElementById('healthFilter')?.addEventListener('change', (e) => {
            this.filters.health = e.target.value;
            this.applyFilters();
        });
        
        // Grid controls
        document.getElementById('gridSort')?.addEventListener('change', (e) => {
            this.sortEntries(e.target.value);
        });
        
        document.querySelectorAll('.size-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.changeGridSize(e.target.dataset.size);
            });
        });
        
        // Character counter
        document.getElementById('entryDescription')?.addEventListener('input', (e) => {
            this.updateCharacterCount(e.target);
        });
        
        // Modal backdrop clicks
        document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
            backdrop.addEventListener('click', (e) => {
                if (e.target === backdrop) {
                    e.target.closest('.modal').classList.remove('active');
                }
            });
        });
    }
    
    initializeCalendar() {
        const calendarEl = document.getElementById('calendar');
        if (!calendarEl) {
            console.error('Calendar element not found');
            this.showCalendarError();
            return;
        }
        
        if (typeof FullCalendar === 'undefined') {
            console.error('FullCalendar library not loaded');
            // Try to wait for it to load
            let attempts = 0;
            const waitForFullCalendar = () => {
                attempts++;
                if (typeof FullCalendar !== 'undefined') {
                    this.initializeCalendar();
                } else if (attempts < 20) { // Wait up to 2 seconds
                    setTimeout(waitForFullCalendar, 100);
                } else {
                    console.warn('FullCalendar failed to load, showing fallback calendar');
                    this.showFallbackCalendar();
                }
            };
            waitForFullCalendar();
            return;
        }
        
        console.log('Initializing FullCalendar...');
        
        this.calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: false,
            height: 'auto',
            selectable: true,
            selectMirror: true,
            dayMaxEvents: true,
            eventDisplay: 'block',
            eventBackgroundColor: 'var(--myavana-coral)',
            eventBorderColor: 'var(--myavana-coral)',
            
            // Custom styling
            dayCellClassNames: (arg) => {
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                const cellDate = new Date(arg.date);
                cellDate.setHours(0, 0, 0, 0);
                
                if (cellDate.getTime() === today.getTime()) {
                    return ['diary-today'];
                }
                return [];
            },
            
            // Event handling
            dateClick: (info) => {
                this.selectDate(new Date(info.date));
            },
            
            eventClick: (info) => {
                this.viewEntry(parseInt(info.event.id));
            },
            
            select: (info) => {
                this.selectDate(new Date(info.start));
                this.openEntryModal(new Date(info.start));
            },
            
            // Load events
            events: (info, successCallback, failureCallback) => {
                this.loadCalendarEvents(info, successCallback, failureCallback);
            },
            
            // Update title when view changes
            datesSet: (info) => {
                this.updateCalendarTitle(info);
            }
        });
        
        try {
            this.calendar.render();
            console.log('FullCalendar rendered successfully');
        } catch (error) {
            console.error('Error rendering calendar:', error);
            this.showCalendarError();
        }
    }
    
    showCalendarError() {
        const calendarEl = document.getElementById('calendar');
        if (calendarEl) {
            calendarEl.innerHTML = `
                <div class="calendar-error" style="
                    padding: 40px;
                    text-align: center;
                    background: var(--myavana-stone);
                    border-radius: 8px;
                    border: 1px solid var(--myavana-sand);
                ">
                    <div style="font-size: 48px; margin-bottom: 16px;">📅</div>
                    <h3 style="color: var(--myavana-onyx); font-family: 'Archivo', sans-serif; margin-bottom: 8px;">Calendar Loading Error</h3>
                    <p style="color: var(--myavana-blueberry); font-family: 'Archivo', sans-serif;">
                        Unable to load the calendar. Please refresh the page or try again later.
                    </p>
                    <button class="myavana-btn-primary" onclick="window.diaryInstance.showFallbackCalendar()" style="margin-top: 16px;">
                        SHOW SIMPLE CALENDAR
                    </button>
                    <button class="myavana-btn-secondary" onclick="location.reload()" style="margin-left: 8px; margin-top: 16px;">
                        REFRESH PAGE
                    </button>
                </div>
            `;
        }
    }
    
    showFallbackCalendar() {
        const calendarEl = document.getElementById('calendar');
        if (!calendarEl) return;
        
        const now = new Date();
        const year = now.getFullYear();
        const month = now.getMonth();
        
        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);
        const startingDayOfWeek = firstDay.getDay();
        const monthLength = lastDay.getDate();
        
        const monthNames = [
            'JANUARY', 'FEBRUARY', 'MARCH', 'APRIL', 'MAY', 'JUNE',
            'JULY', 'AUGUST', 'SEPTEMBER', 'OCTOBER', 'NOVEMBER', 'DECEMBER'
        ];
        
        let html = `
            <div class="fallback-calendar" style="background: var(--myavana-white); border-radius: 8px; padding: 20px;">
                <div class="calendar-header" style="text-align: center; margin-bottom: 20px;">
                    <h3 style="color: var(--myavana-onyx); font-family: 'Archivo Black', sans-serif; margin: 0;">
                        ${monthNames[month]} ${year}
                    </h3>
                </div>
                <div class="calendar-grid" style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 1px; background: var(--myavana-stone);">
                    <div style="padding: 10px; text-align: center; background: var(--myavana-sand); font-family: 'Archivo', sans-serif; font-weight: 600; font-size: 11px; color: var(--myavana-blueberry);">SUN</div>
                    <div style="padding: 10px; text-align: center; background: var(--myavana-sand); font-family: 'Archivo', sans-serif; font-weight: 600; font-size: 11px; color: var(--myavana-blueberry);">MON</div>
                    <div style="padding: 10px; text-align: center; background: var(--myavana-sand); font-family: 'Archivo', sans-serif; font-weight: 600; font-size: 11px; color: var(--myavana-blueberry);">TUE</div>
                    <div style="padding: 10px; text-align: center; background: var(--myavana-sand); font-family: 'Archivo', sans-serif; font-weight: 600; font-size: 11px; color: var(--myavana-blueberry);">WED</div>
                    <div style="padding: 10px; text-align: center; background: var(--myavana-sand); font-family: 'Archivo', sans-serif; font-weight: 600; font-size: 11px; color: var(--myavana-blueberry);">THU</div>
                    <div style="padding: 10px; text-align: center; background: var(--myavana-sand); font-family: 'Archivo', sans-serif; font-weight: 600; font-size: 11px; color: var(--myavana-blueberry);">FRI</div>
                    <div style="padding: 10px; text-align: center; background: var(--myavana-sand); font-family: 'Archivo', sans-serif; font-weight: 600; font-size: 11px; color: var(--myavana-blueberry);">SAT</div>
        `;
        
        // Add empty cells for days before month starts
        for (let i = 0; i < startingDayOfWeek; i++) {
            html += '<div style="padding: 15px; background: var(--myavana-white); min-height: 50px;"></div>';
        }
        
        // Add days of the month
        for (let day = 1; day <= monthLength; day++) {
            const date = new Date(year, month, day);
            const isToday = date.toDateString() === now.toDateString();
            const bgColor = isToday ? 'var(--myavana-light-coral)' : 'var(--myavana-white)';
            const textColor = isToday ? 'var(--myavana-onyx)' : 'var(--myavana-onyx)';
            
            html += `
                <div style="
                    padding: 15px 10px; 
                    background: ${bgColor}; 
                    min-height: 50px; 
                    cursor: pointer;
                    transition: background-color 0.3s ease;
                    border: ${isToday ? '2px solid var(--myavana-coral)' : 'none'};
                " 
                onclick="window.diaryInstance.openEntryModal(new Date(${year}, ${month}, ${day}))"
                onmouseover="this.style.background='var(--myavana-light-coral)'"
                onmouseout="this.style.background='${bgColor}'">
                    <div style="color: ${textColor}; font-family: 'Archivo', sans-serif; font-weight: 600;">${day}</div>
                </div>
            `;
        }
        
        html += `
                </div>
                <div style="text-align: center; margin-top: 20px;">
                    <p style="color: var(--myavana-blueberry); font-family: 'Archivo', sans-serif; font-size: 13px;">
                        Click any date to add a new diary entry
                    </p>
                </div>
            </div>
        `;
        
        calendarEl.innerHTML = html;
    }
    
    loadCalendarEvents(info, successCallback, failureCallback) {
        fetch(myavanaDiary.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'get_diary_calendar_entries',
                security: myavanaDiary.nonce,
                start: info.startStr,
                end: info.endStr
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                successCallback(data.data.events);
                this.updateDailyView(data.data.events);
            } else {
                failureCallback(data.data);
            }
        })
        .catch(error => {
            console.error('Error loading calendar events:', error);
            failureCallback(error);
        });
    }
    
    updateCalendarTitle(info) {
        const titleEl = document.getElementById('calendarTitle');
        if (titleEl) {
            const date = new Date(info.view.currentStart);
            titleEl.textContent = date.toLocaleDateString('en-US', {
                month: 'long',
                year: 'numeric'
            });
        }
    }
    
    setupViewSwitching() {
        document.querySelectorAll('.view-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const view = e.target.closest('.view-btn').dataset.view;
                this.switchView(view);
            });
        });
    }
    
    switchView(view) {
        // Update active button
        document.querySelectorAll('.view-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelector(`[data-view="${view}"]`)?.classList.add('active');
        
        // Switch view content
        document.querySelectorAll('.diary-view').forEach(viewEl => {
            viewEl.classList.remove('active');
        });
        document.getElementById(`${view}-view`)?.classList.add('active');
        
        this.currentView = view;
        
        // Load view-specific data
        if (view === 'journal') {
            this.loadJournalView();
        } else if (view === 'grid') {
            this.loadGridView();
        }
    }
    
    selectDate(date) {
        this.selectedDate = date;
        this.loadDailyEntries(date);
        
        // Update calendar selection
        if (this.calendar) {
            this.calendar.select(date);
        }
    }
    
    loadDailyEntries(date) {
        const summaryEl = document.getElementById('dailySummary');
        if (!summaryEl) return;
        
        const dateStr = date.toISOString().split('T')[0];
        const dayEntries = this.entries.filter(entry => {
            return new Date(entry.date).toISOString().split('T')[0] === dateStr;
        });
        
        const headerEl = summaryEl.querySelector('.summary-header h3');
        const contentEl = summaryEl.querySelector('.summary-content');
        const addBtn = document.getElementById('addEntryForDate');
        
        if (headerEl) {
            headerEl.textContent = date.toLocaleDateString('en-US', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        }
        
        if (addBtn) {
            addBtn.style.display = 'inline-flex';
            addBtn.onclick = () => this.openEntryModal(date);
        }
        
        if (dayEntries.length === 0) {
            contentEl.innerHTML = `
                <div class="empty-state">
                    <div class="empty-icon">📝</div>
                    <p>${myavanaDiary.strings.no_entries}</p>
                </div>
            `;
        } else {
            contentEl.innerHTML = dayEntries.map(entry => this.renderDailySummaryEntry(entry)).join('');
        }
    }
    
    renderDailySummaryEntry(entry) {
        return `
            <div class="daily-entry" onclick="window.diaryInstance.viewEntry(${entry.id})">
                <div class="entry-header">
                    <h4 class="entry-title">${this.escapeHtml(entry.title)}</h4>
                    <div class="entry-meta">
                        <span class="entry-mood">${entry.mood || '😊'}</span>
                        <span class="entry-health health-score health-${this.getHealthClass(entry.health_rating)}">${entry.health_rating}/10</span>
                    </div>
                </div>
                ${entry.description ? `<p class="entry-excerpt">${this.truncateText(entry.description, 100)}</p>` : ''}
                ${entry.image ? `<div class="entry-thumbnail">
                    <img src="${entry.image}" alt="Entry photo" loading="lazy">
                </div>` : ''}
            </div>
        `;
    }
    
    loadEntries() {
        fetch(myavanaDiary.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'myavana_get_diary_entries',
                security: myavanaDiary.nonce
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.entries = data.data.entries || [];
                this.updateDiaryStats();
                this.updateJournalTags();
            }
        })
        .catch(error => {
            console.error('Error loading entries:', error);
        });
    }
    
    loadJournalView() {
        const container = document.getElementById('journalEntries');
        if (!container) return;
        
        container.innerHTML = '<div class="loading-state"><div class="spinner"></div><p>Loading journal entries...</p></div>';
        
        setTimeout(() => {
            const filteredEntries = this.applyEntryFilters(this.entries);
            
            if (filteredEntries.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-icon">📖</div>
                        <p>No entries match your current filters.</p>
                        <button class="myavana-btn-primary" onclick="window.diaryInstance.openEntryModal()">CREATE YOUR FIRST ENTRY</button>
                    </div>
                `;
                return;
            }
            
            container.innerHTML = filteredEntries.map(entry => this.renderJournalEntry(entry)).join('');
        }, 300);
    }
    
    renderJournalEntry(entry) {
        return `
            <article class="journal-entry" onclick="window.diaryInstance.viewEntry(${entry.id})">
                <div class="entry-header">
                    <h3 class="entry-title">${this.escapeHtml(entry.title)}</h3>
                    <time class="entry-date">${new Date(entry.date).toLocaleDateString('en-US', {
                        weekday: 'short',
                        month: 'short',
                        day: 'numeric',
                        year: 'numeric'
                    })}</time>
                </div>
                
                ${entry.image ? `
                    <div class="entry-image">
                        <img src="${entry.image}" alt="Entry photo" loading="lazy">
                    </div>
                ` : ''}
                
                <div class="entry-content">
                    <p>${this.truncateText(entry.description || '', 200)}</p>
                </div>
                
                <div class="entry-meta">
                    <div class="entry-mood">
                        <span>${entry.mood || '😊'}</span>
                        <span>Mood</span>
                    </div>
                    <div class="entry-health">
                        <span class="health-score health-${this.getHealthClass(entry.health_rating)}">${entry.health_rating || 5}</span>
                        <span>Health</span>
                    </div>
                    ${entry.products ? `
                        <div class="entry-products">
                            <span>🧴</span>
                            <span>Products</span>
                        </div>
                    ` : ''}
                </div>
            </article>
        `;
    }
    
    loadGridView() {
        const container = document.getElementById('entriesGrid');
        if (!container) return;
        
        container.innerHTML = '<div class="loading-state"><div class="spinner"></div><p>Loading entries...</p></div>';
        
        setTimeout(() => {
            const filteredEntries = this.applyEntryFilters(this.entries);
            
            if (filteredEntries.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-icon">📅</div>
                        <p>No entries to display.</p>
                    </div>
                `;
                return;
            }
            
            container.innerHTML = filteredEntries.map(entry => this.renderGridEntry(entry)).join('');
        }, 200);
    }
    
    renderGridEntry(entry) {
        return `
            <div class="grid-entry" onclick="window.diaryInstance.viewEntry(${entry.id})">
                ${entry.image ? `
                    <div class="grid-entry-image">
                        <img src="${entry.image}" alt="Entry photo" loading="lazy">
                    </div>
                ` : `
                    <div class="grid-entry-placeholder">
                        <span class="entry-mood-large">${entry.mood || '😊'}</span>
                    </div>
                `}
                
                <div class="grid-entry-content">
                    <h4 class="grid-entry-title">${this.escapeHtml(entry.title)}</h4>
                    <time class="grid-entry-date">${new Date(entry.date).toLocaleDateString('en-US', {
                        month: 'short',
                        day: 'numeric'
                    })}</time>
                    <div class="grid-entry-meta">
                        <span class="health-indicator health-${this.getHealthClass(entry.health_rating)}"></span>
                        <span>${entry.health_rating || 5}/10</span>
                    </div>
                </div>
            </div>
        `;
    }
    
    applyEntryFilters(entries) {
        return entries.filter(entry => {
            // Date filter
            if (this.filters.dateRange !== 'all') {
                const entryDate = new Date(entry.date);
                const now = new Date();
                let cutoffDate = new Date();
                
                switch (this.filters.dateRange) {
                    case 'week':
                        cutoffDate.setDate(now.getDate() - 7);
                        break;
                    case 'month':
                        cutoffDate.setMonth(now.getMonth() - 1);
                        break;
                    case '3months':
                        cutoffDate.setMonth(now.getMonth() - 3);
                        break;
                }
                
                if (entryDate < cutoffDate) return false;
            }
            
            // Mood filter
            if (this.filters.mood !== 'all' && entry.mood !== this.filters.mood) {
                return false;
            }
            
            // Health filter
            if (this.filters.health !== 'all') {
                const rating = parseInt(entry.health_rating || 5);
                const [min, max] = this.filters.health.split('-').map(n => parseInt(n));
                if (rating < min || rating > max) {
                    return false;
                }
            }
            
            return true;
        });
    }
    
    applyFilters() {
        if (this.currentView === 'journal') {
            this.loadJournalView();
        } else if (this.currentView === 'grid') {
            this.loadGridView();
        }
    }
    
    sortEntries(sortBy) {
        const container = document.getElementById('entriesGrid');
        if (!container) return;
        
        let sortedEntries = [...this.entries];
        
        switch (sortBy) {
            case 'date_desc':
                sortedEntries.sort((a, b) => new Date(b.date) - new Date(a.date));
                break;
            case 'date_asc':
                sortedEntries.sort((a, b) => new Date(a.date) - new Date(b.date));
                break;
            case 'mood':
                const moodOrder = {'😊': 5, '😌': 4, '😐': 3, '😔': 2, '😤': 1};
                sortedEntries.sort((a, b) => (moodOrder[b.mood] || 3) - (moodOrder[a.mood] || 3));
                break;
            case 'health':
                sortedEntries.sort((a, b) => (parseInt(b.health_rating) || 5) - (parseInt(a.health_rating) || 5));
                break;
        }
        
        container.innerHTML = sortedEntries.map(entry => this.renderGridEntry(entry)).join('');
    }
    
    changeGridSize(size) {
        const container = document.getElementById('entriesGrid');
        if (!container) return;
        
        // Update active button
        document.querySelectorAll('.size-btn').forEach(btn => btn.classList.remove('active'));
        document.querySelector(`[data-size="${size}"]`)?.classList.add('active');
        
        // Update grid
        container.dataset.size = size;
    }
    
    openEntryModal(date = null) {
        const modal = document.getElementById('entryModal');
        const form = document.getElementById('entryForm');
        const title = document.getElementById('entryModalTitle');
        
        if (!modal || !form) return;
        
        // Reset form
        form.reset();
        document.getElementById('entryId').value = '';
        
        // Set date
        const targetDate = date || this.selectedDate || new Date();
        document.getElementById('entryDate').value = targetDate.toISOString().split('T')[0];
        
        // Update modal title
        if (title) {
            title.textContent = date ? 
                `New Entry - ${date.toLocaleDateString('en-US', { month: 'long', day: 'numeric' })}` : 
                'New Entry';
        }
        
        // Clear photo preview
        this.clearPhotoPreview();
        
        // Show modal
        modal.classList.add('active');
        document.getElementById('entryTitle')?.focus();
    }
    
    closeEntryModal() {
        const modal = document.getElementById('entryModal');
        modal?.classList.remove('active');
    }
    
    closeDetailModal() {
        const modal = document.getElementById('entryDetailModal');
        modal?.classList.remove('active');
    }
    
    viewEntry(entryId) {
        fetch(myavanaDiary.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'myavana_get_single_diary_entry',
                security: myavanaDiary.nonce,
                entry_id: entryId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.showEntryDetail(data.data);
                this.currentEntry = data.data;
            }
        })
        .catch(error => {
            console.error('Error loading entry:', error);
        });
    }
    
    showEntryDetail(entry) {
        const modal = document.getElementById('entryDetailModal');
        const title = document.getElementById('detailModalTitle');
        const content = document.getElementById('entryDetailContent');
        
        if (!modal || !content) return;
        
        if (title) {
            title.textContent = entry.title;
        }
        
        content.innerHTML = this.renderEntryDetail(entry);
        modal.classList.add('active');
    }
    
    renderEntryDetail(entry) {
        return `
            <div class="entry-detail">
                <div class="detail-header">
                    <time class="detail-date">${new Date(entry.date).toLocaleDateString('en-US', {
                        weekday: 'long',
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                    })}</time>
                    <div class="detail-meta">
                        <div class="meta-item">
                            <span class="meta-label">Mood:</span>
                            <span class="meta-value">${entry.mood_demeanor || '😊'}</span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">Health:</span>
                            <span class="meta-value health-score health-${this.getHealthClass(entry.rating)}">${entry.rating}/10</span>
                        </div>
                    </div>
                </div>
                
                ${entry.image ? `
                    <div class="detail-image">
                        <img src="${entry.image}" alt="Entry photo">
                    </div>
                ` : ''}
                
                <div class="detail-content">
                    <div class="content-section">
                        <h4>Description</h4>
                        <p>${this.escapeHtml(entry.description || 'No description provided.')}</p>
                    </div>
                    
                    ${entry.products ? `
                        <div class="content-section">
                            <h4>Products Used</h4>
                            <p>${this.escapeHtml(entry.products)}</p>
                        </div>
                    ` : ''}
                    
                    ${entry.notes ? `
                        <div class="content-section">
                            <h4>Notes</h4>
                            <p>${this.escapeHtml(entry.notes)}</p>
                        </div>
                    ` : ''}
                    
                    ${entry.environment ? `
                        <div class="content-section">
                            <h4>Environment</h4>
                            <p>${this.escapeHtml(entry.environment)}</p>
                        </div>
                    ` : ''}
                </div>
            </div>
        `;
    }
    
    editCurrentEntry() {
        if (!this.currentEntry) return;
        
        this.closeDetailModal();
        this.populateEntryForm(this.currentEntry);
        this.openEntryModal();
    }
    
    populateEntryForm(entry) {
        document.getElementById('entryId').value = entry.id;
        document.getElementById('entryTitle').value = entry.title;
        document.getElementById('entryDescription').value = entry.description || '';
        document.getElementById('productsUsed').value = entry.products || '';
        document.getElementById('stylistNotes').value = entry.notes || '';
        document.getElementById('environment').value = entry.environment || '';
        document.getElementById('entryDate').value = entry.date;
        
        // Set rating
        const ratingInput = document.querySelector(`input[name="rating"][value="${entry.rating}"]`);
        if (ratingInput) {
            ratingInput.checked = true;
        }
        
        // Set mood
        const moodInput = document.querySelector(`input[name="mood_demeanor"][value="${entry.mood_demeanor}"]`);
        if (moodInput) {
            moodInput.checked = true;
        }
        
        // Handle image
        if (entry.image) {
            this.showPhotoPreview(entry.image);
        }
        
        // Update character count
        this.updateCharacterCount(document.getElementById('entryDescription'));
        
        // Update modal title
        const title = document.getElementById('entryModalTitle');
        if (title) {
            title.textContent = 'Edit Entry';
        }
    }
    
    deleteCurrentEntry() {
        if (!this.currentEntry) return;
        
        if (!confirm(myavanaDiary.strings.delete_confirm)) return;
        
        fetch(myavanaDiary.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'myavana_delete_diary_entry',
                security: myavanaDiary.nonce,
                entry_id: this.currentEntry.id
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.showNotification('Entry deleted successfully', 'success');
                this.closeDetailModal();
                this.refreshViews();
            } else {
                this.showNotification(data.data || 'Error deleting entry', 'error');
            }
        })
        .catch(error => {
            console.error('Error deleting entry:', error);
            this.showNotification('Error deleting entry', 'error');
        });
    }
    
    saveEntry() {
        const form = document.getElementById('entryForm');
        if (!form) return;
        
        const formData = new FormData(form);
        const entryId = document.getElementById('entryId').value;
        
        formData.append('action', entryId ? 'myavana_edit_diary_entry' : 'myavana_add_diary_entry');
        formData.append('myavana_nonce', myavanaDiary.nonce);
        
        const saveBtn = document.getElementById('saveEntry');
        const btnText = saveBtn?.querySelector('.btn-text');
        const btnLoader = saveBtn?.querySelector('.btn-loader');
        
        // Show loading state
        if (saveBtn) {
            saveBtn.disabled = true;
            if (btnText) btnText.style.display = 'none';
            if (btnLoader) btnLoader.style.display = 'flex';
        }
        
        fetch(myavanaDiary.ajax_url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.showNotification(myavanaDiary.strings.save_success, 'success');
                this.closeEntryModal();
                this.refreshViews();
            } else {
                this.showNotification(data.data || 'Error saving entry', 'error');
            }
        })
        .catch(error => {
            console.error('Error saving entry:', error);
            this.showNotification('Error saving entry', 'error');
        })
        .finally(() => {
            // Reset button state
            if (saveBtn) {
                saveBtn.disabled = false;
                if (btnText) btnText.style.display = 'inline';
                if (btnLoader) btnLoader.style.display = 'none';
            }
        });
    }
    
    initializePhotoUpload() {
        const uploadArea = document.getElementById('uploadArea');
        const photoInput = document.getElementById('photoInput');
        const removeBtn = document.getElementById('removePhoto');
        
        if (!uploadArea || !photoInput) return;
        
        uploadArea.addEventListener('click', () => {
            photoInput.click();
        });
        
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('drag-over');
        });
        
        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('drag-over');
        });
        
        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('drag-over');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                photoInput.files = files;
                this.handlePhotoUpload(files[0]);
            }
        });
        
        photoInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                this.handlePhotoUpload(e.target.files[0]);
            }
        });
        
        removeBtn?.addEventListener('click', () => {
            this.clearPhotoPreview();
        });
    }
    
    handlePhotoUpload(file) {
        if (!file || !file.type.startsWith('image/')) {
            this.showNotification('Please select a valid image file', 'error');
            return;
        }
        
        if (file.size > 5 * 1024 * 1024) { // 5MB limit
            this.showNotification('Image file is too large. Please select a file under 5MB.', 'error');
            return;
        }
        
        const reader = new FileReader();
        reader.onload = (e) => {
            this.showPhotoPreview(e.target.result);
        };
        reader.readAsDataURL(file);
    }
    
    showPhotoPreview(src) {
        const uploadArea = document.getElementById('uploadArea');
        const preview = document.getElementById('photoPreview');
        const image = document.getElementById('previewImage');
        
        if (!uploadArea || !preview || !image) return;
        
        image.src = src;
        uploadArea.style.display = 'none';
        preview.style.display = 'block';
    }
    
    clearPhotoPreview() {
        const uploadArea = document.getElementById('uploadArea');
        const preview = document.getElementById('photoPreview');
        const photoInput = document.getElementById('photoInput');
        
        if (uploadArea) uploadArea.style.display = 'block';
        if (preview) preview.style.display = 'none';
        if (photoInput) photoInput.value = '';
    }
    
    updateCharacterCount(textarea) {
        const counter = document.getElementById('descriptionCount');
        if (!counter || !textarea) return;
        
        const count = textarea.value.length;
        counter.textContent = count;
        
        if (count > 450) {
            counter.style.color = 'var(--diary-warning)';
        } else if (count > 500) {
            counter.style.color = 'var(--diary-error)';
        } else {
            counter.style.color = 'var(--diary-text-muted)';
        }
    }
    
    goToToday() {
        const today = new Date();
        this.selectDate(today);
        
        if (this.calendar) {
            this.calendar.today();
        }
        
        if (this.currentView === 'calendar') {
            // Scroll to today if needed
            this.loadDailyEntries(today);
        }
    }
    
    loadDiaryStats() {
        // Calculate stats from entries
        if (this.entries.length === 0) {
            setTimeout(() => this.loadDiaryStats(), 1000);
            return;
        }
        
        const totalEntries = this.entries.length;
        const currentStreak = this.calculateStreak();
        const avgMood = this.calculateAverageMood();
        const avgHealth = this.calculateAverageHealth();
        
        // Update display
        document.getElementById('totalEntries')?.textContent = totalEntries;
        document.getElementById('currentStreak')?.textContent = currentStreak;
        document.getElementById('avgMood')?.textContent = avgMood;
        document.getElementById('avgHealth')?.textContent = avgHealth;
    }
    
    updateDiaryStats() {
        this.loadDiaryStats();
    }
    
    calculateStreak() {
        if (this.entries.length === 0) return 0;
        
        const sortedEntries = [...this.entries].sort((a, b) => new Date(b.date) - new Date(a.date));
        let streak = 0;
        let currentDate = new Date();
        currentDate.setHours(0, 0, 0, 0);
        
        for (const entry of sortedEntries) {
            const entryDate = new Date(entry.date);
            entryDate.setHours(0, 0, 0, 0);
            
            const daysDiff = Math.floor((currentDate - entryDate) / (1000 * 60 * 60 * 24));
            
            if (daysDiff === streak) {
                streak++;
            } else if (daysDiff > streak) {
                break;
            }
        }
        
        return streak;
    }
    
    calculateAverageMood() {
        if (this.entries.length === 0) return '😊';
        
        const moods = this.entries.map(entry => entry.mood_demeanor || '😊');
        const moodCounts = {};
        
        moods.forEach(mood => {
            moodCounts[mood] = (moodCounts[mood] || 0) + 1;
        });
        
        return Object.keys(moodCounts).reduce((a, b) => 
            moodCounts[a] > moodCounts[b] ? a : b
        );
    }
    
    calculateAverageHealth() {
        if (this.entries.length === 0) return '0';
        
        const healthScores = this.entries
            .map(entry => parseInt(entry.rating || entry.health_rating || 5))
            .filter(score => !isNaN(score));
        
        if (healthScores.length === 0) return '0';
        
        const average = healthScores.reduce((sum, score) => sum + score, 0) / healthScores.length;
        return average.toFixed(1);
    }
    
    updateJournalTags() {
        // This would extract and display popular tags
        // Implementation would depend on tag system
    }
    
    refreshViews() {
        // Reload data and refresh current view
        this.loadEntries();
        
        if (this.calendar) {
            this.calendar.refetchEvents();
        }
        
        if (this.currentView === 'journal') {
            this.loadJournalView();
        } else if (this.currentView === 'grid') {
            this.loadGridView();
        }
        
        this.loadDailyEntries(this.selectedDate);
    }
    
    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.innerHTML = `
            <span>${message}</span>
            <button class="close-notification">&times;</button>
        `;
        
        document.body.appendChild(notification);
        
        const closeBtn = notification.querySelector('.close-notification');
        closeBtn?.addEventListener('click', () => {
            notification.remove();
        });
        
        setTimeout(() => {
            notification.remove();
        }, 5000);
    }
    
    // Utility methods
    escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }
    
    truncateText(text, maxLength) {
        if (text.length <= maxLength) return this.escapeHtml(text);
        return this.escapeHtml(text.substring(0, maxLength)) + '...';
    }
    
    getHealthClass(rating) {
        const score = parseInt(rating) || 5;
        if (score >= 8) return 'excellent';
        if (score >= 6) return 'good';
        if (score >= 4) return 'fair';
        return 'poor';
    }
}

// Initialize diary when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    if (document.querySelector('.myavana-hair-diary')) {
        window.diaryInstance = new HairJourneyDiary();
    }
});

// Add notification styles
const notificationStyles = `
<style>
.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 1rem 1.5rem;
    border-radius: 8px;
    color: white;
    font-family: 'Archivo', sans-serif;
    font-weight: 600;
    z-index: 10000;
    display: flex;
    align-items: center;
    gap: 1rem;
    box-shadow: 0 4px 12px rgba(34, 35, 35, 0.2);
    animation: slideInRight 0.3s ease-out;
    max-width: 400px;
    font-size: 13.5px;
}

.notification.success {
    background: var(--myavana-coral, #e7a690);
}

.notification.error {
    background: var(--myavana-blueberry, #4a4d68);
}

.notification.info {
    background: var(--myavana-blueberry, #4a4d68);
}

.close-notification {
    background: none;
    border: none;
    color: white;
    font-size: 1.2rem;
    cursor: pointer;
    padding: 0;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
}

@keyframes slideInRight {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

@media (max-width: 768px) {
    .notification {
        left: 1rem;
        right: 1rem;
        top: 1rem;
    }
}
</style>
`;

document.head.insertAdjacentHTML('beforeend', notificationStyles);