// Calendar state management
const calendarState = {
    currentView: 'month', // month, week, day
    currentDate: new Date(),
    calendarData: null
};
let calendarInitialized = false;

/**
 * Initialize calendar view
 */
function initCalendarView() {
    console.log('Initializing calendar view...');
    // Prevent multiple initializations
    if (calendarInitialized) {
        console.log('Calendar already initialized, skipping...');
        // Ensure the view is up-to-date even on re-init
        switchCalendarView(calendarState.currentView);
        return;
    }

    const calendarView = document.getElementById('calendarView');
    if (!calendarView) {
        console.log('Calendar view not found');
        return;
    }

    // Load calendar data from hidden JSON
    const calendarDataEl = document.getElementById('calendarDataHjn');
    if (calendarDataEl) {
        try {
            calendarState.calendarData = JSON.parse(calendarDataEl.textContent);
            console.log('Calendar data loaded:', calendarState.calendarData);
        } catch (error) {
            console.error('Error parsing calendar data:', error);
        }
    }
    // Set the flag AFTER successful initialization
    calendarInitialized = true;

    // Set initial view
    switchCalendarView('month');
    console.log('Calendar view initialized');
}

/**
 * Switch between calendar views (month/week/day)
 */
function switchCalendarView(view) {
    console.log('Switching to calendar view:', view);
    if (!calendarInitialized || !calendarState.calendarData) {
        console.warn('Cannot switch view, calendar not ready.');
        return;
    }

    calendarState.currentView = view;

    // Update toggle buttons
    document.querySelectorAll('.calendar-view-toggle-hjn').forEach(toggle => {
        toggle.classList.toggle('active', toggle.getAttribute('data-view') === view);
    });

    // Hide all views
    document.getElementById('monthViewHjn')?.classList.remove('active');
    document.getElementById('weekViewHjn')?.classList.remove('active');
    document.getElementById('dayViewHjn')?.classList.remove('active');

    // Show selected view and update its content
    switch (view) {
        case 'month':
            document.getElementById('monthViewHjn')?.classList.add('active');
            updateMonthView();
            break;
        case 'week':
            document.getElementById('weekViewHjn')?.classList.add('active');
            updateWeekView();
            break;
        case 'day':
            document.getElementById('dayViewHjn')?.classList.add('active');
            updateDayView();
            break;
    }

    // Update date range display
    updateDateRangeDisplay();
}

/**
 * Navigate calendar (prev/next/today)
 */
function navigateCalendar(direction) {
    console.log('Navigating calendar:', direction);
    const d = calendarState.currentDate;

    if (direction === 'today') {
        calendarState.currentDate = new Date();
    } else {
        const sign = (direction === 'prev') ? -1 : 1;
        switch (calendarState.currentView) {
            case 'month':
                // Set to the first of the month to avoid day overflow issues
                d.setDate(1);
                d.setMonth(d.getMonth() + sign);
                break;
            case 'week':
                d.setDate(d.getDate() + (7 * sign));
                break;
            case 'day':
                d.setDate(d.getDate() + sign);
                break;
        }
    }
    // Refresh the current view with the new date
    switchCalendarView(calendarState.currentView);
}


/**
 * Update date range display based on current view and date
 */
function updateDateRangeDisplay() {
    const dateRangeEl = document.getElementById('calendarDateRange');
    if (!dateRangeEl) return;

    const date = calendarState.currentDate;
    let displayText = '';

    switch (calendarState.currentView) {
        case 'month':
            displayText = date.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
            break;
        case 'week':
            const dayOfWeek = date.getDay();
            const diff = date.getDate() - dayOfWeek + (dayOfWeek === 0 ? -6 : 1); // Adjust for Sunday being 0
            const monday = new Date(date);
            monday.setDate(diff);

            const sunday = new Date(monday);
            sunday.setDate(monday.getDate() + 6);

            const monthStart = monday.toLocaleDateString('en-US', { month: 'short' });
            const monthEnd = sunday.toLocaleDateString('en-US', { month: 'short' });

            if (monday.getFullYear() !== sunday.getFullYear()) {
                 displayText = `${monday.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })} - ${sunday.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}`;
            } else if (monthStart !== monthEnd) {
                displayText = `${monday.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} - ${sunday.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })}, ${sunday.getFullYear()}`;
            } else {
                displayText = `${monthStart} ${monday.getDate()} - ${sunday.getDate()}, ${sunday.getFullYear()}`;
            }
            break;
        case 'day':
            displayText = date.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
            break;
    }

    dateRangeEl.textContent = displayText;
}


/**
 * Update month view with current data — now includes goals, routines, and entry indicators
 */
function updateMonthView() {
    console.log('Updating month view...');

    // Update grid view for desktop
    const grid = document.querySelector('#monthViewHjn .calendar-days-grid-hjn');
    if (grid) {
        grid.innerHTML = ''; // Clear previous month's cells
    }

    // Update list view for mobile
    const list = document.querySelector('#monthViewHjn .calendar-month-list-hjn');
    if (list) {
        list.innerHTML = ''; // Clear previous month's list
    }

    const date = calendarState.currentDate;
    const year = date.getFullYear();
    const month = date.getMonth(); // 0-indexed

    const firstDayOfMonth = new Date(year, month, 1);
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    let startOffset = firstDayOfMonth.getDay() - 1; // 0=Mon, 1=Tue, ...
    if (startOffset === -1) startOffset = 6; // Adjust for Sunday

    // Add empty cells for offset
    for (let i = 0; i < startOffset; i++) {
        grid.insertAdjacentHTML('beforeend', '<div class="calendar-day-cell-hjn calendar-day-empty-hjn"></div>');
    }

    const today = new Date();
    const isCurrentMonth = today.getFullYear() === year && today.getMonth() === month;

    // Add cells for each day (Desktop Grid)
    for (let day = 1; day <= daysInMonth; day++) {
        const currentDateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        const isToday = isCurrentMonth && day === today.getDate();

        // ---- FILTER DATA ----
        const dayEntries = calendarState.calendarData.entries.filter(e => e.date === currentDateStr);
        const dayGoals = calendarState.calendarData.goals.filter(g => {
            const dayTs = new Date(currentDateStr).getTime();
            const startTs = new Date(g.start_date).getTime();
            const endTs = g.end_date ? new Date(g.end_date).getTime() : startTs;
            return dayTs >= startTs && dayTs <= endTs;
        });
        const dayRoutines = calendarState.calendarData.routines.filter(r => {
            if (r.frequency === 'daily') return true;
            if (r.frequency === 'weekly') {
                const routineDay = new Date(r.start_date).getDay();
                return new Date(currentDateStr).getDay() === routineDay;
            }
            return r.date === currentDateStr;
        });

        const hasContent = dayEntries.length > 0 || dayGoals.length > 0 || dayRoutines.length > 0;

        // Desktop Grid Cell
        if (grid) {
            let cellHTML = `
                <div class="calendar-day-cell-hjn ${isToday ? 'calendar-day-today-hjn' : ''} ${hasContent ? 'calendar-day-has-content-hjn' : ''}"
                     data-date="${currentDateStr}"
                     onclick="openCalendarDayDetail('${currentDateStr}')">
                    <div class="calendar-day-number-hjn">${day}</div>
            `;

        // ---- GOALS ----
        if (dayGoals.length > 0) {
            dayGoals.forEach((goal, index) => {
                const opacity = 1 - index * 0.25;
                cellHTML += `
                    <div class="goal-bar-span-new highlighted" 
                        style="left:0%; bottom:${index * 5}px; width:100%; z-index:${10 - index}; opacity:${opacity}">
                        <div class="goal-span-title" style="font-size:8px; width:100%;">
                            ${goal.title}
                        </div>
                    </div>
                `;
            });
        }

      
        /** ---- ROUTINES ---- **/
        const routines = calendarState.calendarData.routines || [];
        const weekday = new Date(currentDateStr).getDay(); // 0=Sun, 1=Mon, ..., 6=Sat
        const dayRoutines2 = routines.filter(routine => {
            switch (routine.frequency.toLowerCase()) {
                case 'daily': return true;
                case 'weekly': return weekday === 3; // Wednesday
                case 'bi-weekly': return weekday === 1 || weekday === 5; // Mon, Fri
                case 'monthly': return new Date(currentDateStr).getDate() === 1; // 1st of month
                default: return false;
            }
        });
        

        if (dayRoutines2.length > 0) {
             cellHTML += `<div class="routine-stack-container">`;
            dayRoutines2.forEach(routine => {
                // Determine routine hour based on time of day
                let routineHour = routine.hour;
                if (!routineHour) {
                    const timeText = routine.time?.toLowerCase() || '';
                    if (timeText.includes('morning')) routineHour = 8;
                    else if (timeText.includes('evening')) routineHour = 18;
                    else if (timeText.includes('night')) routineHour = 21;
                    else routineHour = 8;
                }

                const topPosition = routineHour * 60; // 1px per minute scale
                const isMorning = routineHour < 12;
                const icon = isMorning ? '☀️' : '🌙';
                
                cellHTML += `
                    <div class="routine-stack-card">
                        <div class="routine-stack-icon">${icon}</div>
                        <div class="routine-stack-content">
                            <div class="routine-stack-title">${routine.title}</div>
                            <div class="routine-stack-time">${routine.time}</div>
                        </div>
                    </div>
                `;
            });
            cellHTML += `</div>`;
        }

        // ---- ENTRIES (Hair Journey Stack + indicators) ----
        if (dayEntries.length > 0) {
            // Indicator badge showing number of entries
            cellHTML += `
                <div class="calendar-day-indicators-hjn">
                    <div class="calendar-day-indicator-hjn calendar-indicator-entry-hjn" 
                         title="${dayEntries.length} entries">
                        ${dayEntries.length}
                    </div>
                </div>
            `;

            cellHTML += `<div class="hair-journey-stack">`;

            // Limit to 5 cards for display
            dayEntries.slice(0, 5).forEach((entry, idx) => {
                const cardId = `hair-card-${day}-${idx + 1}`;
                cellHTML += `<input class="hair-stack-radio" type="radio" id="${cardId}" name="hair-stack-${day}" ${idx === 0 ? 'checked' : ''}>`;
            });

            dayEntries.slice(0, 2).forEach((entry, idx) => {
                const cardId = `hair-card-${day}-${idx + 1}`;
                const imageUrl = entry.thumbnail || 'https://via.placeholder.com/300x150?text=Myavana';
                const gradient = 'linear-gradient(135deg, rgba(238,236,225,0.3), rgba(232,196,184,0.3))';
                cellHTML += `
                    <label for="${cardId}" 
                        class="hair-stack-card card-style-${idx + 1} hair-card-style-${idx + 1}" 
                        style="width:100%;">
                        <div class="hair-card-visual" 
                            style="background: ${gradient}, url('${imageUrl}');
                                   background-size: cover; width:100%; height:40px;">
                        </div>
                        <div class="hair-card-content">
                            <div>
                                <div class="hair-card-time">${entry.time || ''}</div>
                                <div class="hair-card-title">${entry.title}</div>
                            </div>
                        </div>
                    </label>
                `;
            });

            // Show “+N more” if there are more than 2
            if (dayEntries.length > 2) {
                cellHTML += `<div class="calendar-day-more-hjn">+${dayEntries.length - 2} more</div>`;
            }

            cellHTML += `</div>`; // end .hair-journey-stack
        }

        cellHTML += `</div>`; // end cell
        grid.insertAdjacentHTML('beforeend', cellHTML);
        }

        // Mobile List Item
        if (list && hasContent) {
            let listHTML = `
                <div class="calendar-day-list-item-hjn ${isToday ? 'calendar-day-today-hjn' : ''} ${hasContent ? 'calendar-day-has-content-hjn' : ''}"
                     data-date="${currentDateStr}"
                     onclick="openCalendarDayDetail('${currentDateStr}')">
                    <div class="calendar-day-list-header-hjn">
                        <div class="calendar-day-list-date-hjn">
                            <div class="calendar-day-list-day-hjn">${day}</div>
                            <div class="calendar-day-list-weekday-hjn">${new Date(currentDateStr).toLocaleDateString('en-US', { weekday: 'short' }).toUpperCase()}</div>
                        </div>
                        <div class="calendar-day-list-indicators-hjn">
            `;

            if (dayEntries.length > 0) {
                listHTML += `<div class="calendar-day-indicator-hjn calendar-indicator-entry-hjn">${dayEntries.length}</div>`;
            }
            if (dayGoals.length > 0) {
                listHTML += `<div class="calendar-day-indicator-hjn calendar-indicator-goal-hjn">${dayGoals.length}</div>`;
            }
            if (dayRoutines.length > 0) {
                listHTML += `<div class="calendar-day-indicator-hjn calendar-indicator-routine-hjn">${dayRoutines.length}</div>`;
            }

            listHTML += `
                        </div>
                    </div>
                    <div class="calendar-day-list-content-hjn">
            `;

            // Goals
            if (dayGoals.length > 0) {
                dayGoals.forEach(goal => {
                    listHTML += `
                        <div class="calendar-list-goal-hjn">
                            <div class="goal-list-title-hjn">${goal.title}</div>
                            <div class="goal-list-progress-hjn">
                                <div class="goal-bar-fill-hjn" style="width: ${goal.progress}%"></div>
                            </div>
                        </div>
                    `;
                });
            }

            // Routines
            if (dayRoutines.length > 0) {
                listHTML += `<div class="calendar-list-routines-hjn">`;
                dayRoutines.forEach(routine => {
                    const icon = routine.hour < 12 ? '☀️' : '🌙';
                    listHTML += `
                        <div class="calendar-list-routine-hjn">
                            <div class="routine-list-icon-hjn">${icon}</div>
                            <div class="routine-list-content-hjn">
                                <div class="routine-list-title-hjn">${routine.title}</div>
                                <div class="routine-list-time-hjn">${routine.time}</div>
                            </div>
                        </div>
                    `;
                });
                listHTML += `</div>`;
            }

            // Entries
            const previewEntries = dayEntries.slice(0, 3);
            previewEntries.forEach(entry => {
                listHTML += `
                    <div class="calendar-list-entry-hjn">
                        <div class="entry-list-time-hjn">${entry.time}</div>
                        <div class="entry-list-title-hjn">${entry.title}</div>
                        ${entry.mood ? `<div class="entry-list-mood-hjn">${entry.mood}</div>` : ''}
                    </div>
                `;
            });

            if (dayEntries.length > 3) {
                listHTML += `<div class="calendar-list-more-hjn">+${dayEntries.length - 3} more entries</div>`;
            }

            listHTML += `
                    </div>
                </div>
            `;

            list.insertAdjacentHTML('beforeend', listHTML);
        }
    }
}



/**
 * Update week view with current data (entries, goals, and routines)
 */
function updateWeekView() {
    console.log('Updating week view...');

    // Update desktop grid
    const headersContainer = document.querySelector('#weekViewHjn .calendar-week-headers-hjn');
    const grid = document.querySelector('#weekViewHjn .calendar-week-grid-hjn');
    if (headersContainer) headersContainer.innerHTML = '';
    if (grid) grid.innerHTML = '';

    // Update mobile list
    const list = document.querySelector('#weekViewHjn .calendar-week-list-hjn');
    if (list) list.innerHTML = '';

    if (!calendarState.calendarData) return;

    const date = calendarState.currentDate;
    const dayOfWeek = date.getDay();
    const diff = date.getDate() - dayOfWeek + (dayOfWeek === 0 ? -6 : 1);
    const monday = new Date(date);
    monday.setDate(diff);

    /** --- HEADERS --- **/
    const today = new Date();
    let headersHTML = '';
    for (let i = 0; i < 7; i++) {
        const dayDate = new Date(monday);
        dayDate.setDate(monday.getDate() + i);
        const isToday = dayDate.toDateString() === today.toDateString();
        headersHTML += `
            <div class="calendar-week-day-header-hjn ${isToday ? 'calendar-week-today-hjn' : ''}">
                <div class="calendar-week-day-name-hjn">${dayDate.toLocaleDateString('en-US', { weekday: 'short' })}</div>
                <div class="calendar-week-day-number-hjn">${dayDate.getDate()}</div>
            </div>`;
    }
    headersContainer.innerHTML = headersHTML;

    /** --- GRID COLUMNS --- **/
    let gridHTML = '';
    for (let i = 0; i < 7; i++) {
        const dayDate = new Date(monday);
        dayDate.setDate(monday.getDate() + i);
        const dateStr = `${dayDate.getFullYear()}-${String(dayDate.getMonth() + 1).padStart(2, '0')}-${String(dayDate.getDate()).padStart(2, '0')}`;
        gridHTML += `<div class="calendar-week-day-column-hjn" data-date="${dateStr}"></div>`;
    }
    grid.innerHTML = gridHTML;

    // Desktop Grid Rendering
    if (grid && headersContainer) {
        /** --- HEADERS --- **/
        const today = new Date();
        let headersHTML = '';
        for (let i = 0; i < 7; i++) {
            const dayDate = new Date(monday);
            dayDate.setDate(monday.getDate() + i);
            const isToday = dayDate.toDateString() === today.toDateString();
            headersHTML += `
                <div class="calendar-week-day-header-hjn ${isToday ? 'calendar-week-today-hjn' : ''}">
                    <div class="calendar-week-day-name-hjn">${dayDate.toLocaleDateString('en-US', { weekday: 'short' })}</div>
                    <div class="calendar-week-day-number-hjn">${dayDate.getDate()}</div>
                </div>`;
        }
        headersContainer.innerHTML = headersHTML;

        /** --- GRID COLUMNS --- **/
        let gridHTML = '';
        for (let i = 0; i < 7; i++) {
            const dayDate = new Date(monday);
            dayDate.setDate(monday.getDate() + i);
            const dateStr = `${dayDate.getFullYear()}-${String(dayDate.getMonth() + 1).padStart(2, '0')}-${String(dayDate.getDate()).padStart(2, '0')}`;
            gridHTML += `<div class="calendar-week-day-column-hjn" data-date="${dateStr}"></div>`;
        }
        grid.innerHTML = gridHTML;

        const weekColumns = grid.querySelectorAll('.calendar-week-day-column-hjn');

        /** --- LOOP DAYS --- **/
        weekColumns.forEach(col => {
            const dateStr = col.dataset.date;
            const dayDate = new Date(dateStr);
            const weekday = dayDate.getDay(); // 0=Sun, 1=Mon, ..., 6=Sat

            /** ---- ENTRIES ---- **/
            const dayEntries = calendarState.calendarData.entries.filter(e => e.date === dateStr);

            // ---- ENTRIES (Hair Journey Stack + indicators) ----
            if (dayEntries.length > 0) {

                col.insertAdjacentHTML('beforeend', `
                    <div class="calendar-day-indicators-hjn">
                        <div class="calendar-day-indicator-hjn calendar-indicator-entry-hjn"
                              title="${dayEntries.length} entries">
                            ${dayEntries.length}
                        </div>
                    </div>
                `);
                col.insertAdjacentHTML('beforeend', `
                    <div class="hair-journey-stack">
                `);

                dayEntries.slice(0, 5).forEach((entry, idx) => {
                    const cardId = `hair-card-${dateStr}-${idx + 1}`;
                    col.insertAdjacentHTML('beforeend', `
                    <input class="hair-stack-radio" type="radio" id="${cardId}" name="hair-stack-${dateStr}" ${idx === 0 ? 'checked' : ''}>
                `);
                });

                dayEntries.slice(0, 2).forEach((entry, idx) => {
                    const cardId = `hair-card-${dateStr}-${idx + 1}`;
                    const imageUrl = entry.thumbnail || 'https://via.placeholder.com/300x150?text=Myavana';
                    const gradient = 'linear-gradient(135deg, rgba(238,236,225,0.3), rgba(232,196,184,0.3))';

                    col.insertAdjacentHTML('beforeend', `
                        <label for="${cardId}"
                            class="hair-stack-card card-style-${idx + 1} hair-card-style-${idx + 1}"
                            style="width:100%;">
                            <div class="hair-card-visual"
                                style="background: ${gradient}, url('${imageUrl}');
                                       background-size: cover; width:100%; height:40px;">
                            </div>
                            <div class="hair-card-content">
                                <div>
                                    <div class="hair-card-time">${entry.time || ''}</div>
                                    <div class="hair-card-title">${entry.title}</div>
                                </div>
                            </div>
                        </label>
                    `);
                });

                if (dayEntries.length > 2) {
                    col.insertAdjacentHTML('beforeend', `
                    <div class="calendar-day-more-hjn">+${dayEntries.length - 2} more</div>
                `);
                }

                col.insertAdjacentHTML('beforeend', `
                    </div>
                `);
            }


            /** ---- GOALS ---- **/
            const dayGoals = calendarState.calendarData.goals.filter(g => {
                const dayTs = new Date(dateStr).getTime();
                const startTs = new Date(g.start_date).getTime();
                const endTs = g.end_date ? new Date(g.end_date).getTime() : startTs;
                return dayTs >= startTs && dayTs <= endTs;
            });

            if (dayGoals.length > 0) {
                dayGoals.forEach((goal, index) => {
                    const opacity = 1 - index * 0.25;

                    col.insertAdjacentHTML('beforeend', `
                        <div class="goal-bar-span-new highlighted"
                            style="left:0%; bottom:${index * 5}px; width:100%; z-index:${10 - index}; opacity:${opacity}">
                            <div class="goal-span-title" style="font-size:8px; width:100%;">
                                ${goal.title}
                            </div>
                        </div>
                    `);
                });
            }

            /** ---- ROUTINES ---- **/
            const routines = calendarState.calendarData.routines || [];
            const dayRoutines = routines.filter(routine => {
                switch (routine.frequency.toLowerCase()) {
                    case 'daily': return true;
                    case 'weekly': return weekday === 3; // Wednesday
                    case 'bi-weekly': return weekday === 1 || weekday === 5; // Mon, Fri
                    case 'monthly': return dayDate.getDate() === 1; // 1st of month
                    default: return false;
                }
            });

            if (dayRoutines.length > 0) {
                dayRoutines.forEach(routine => {
                    let routineHour = routine.hour;
                    if (!routineHour) {
                        const timeText = routine.time?.toLowerCase() || '';
                        if (timeText.includes('morning')) routineHour = 8;
                        else if (timeText.includes('evening')) routineHour = 18;
                        else if (timeText.includes('night')) routineHour = 21;
                        else routineHour = 8;
                    }

                    const topPosition = routineHour * 60;
                    const isMorning = routineHour < 12;
                    const icon = isMorning ? '☀️' : '🌙';

                    col.insertAdjacentHTML('beforeend', `
                        <div class="routine-stack-container" style="top: ${topPosition}px;">
                            <div class="routine-stack-card" onclick="openViewOffcanvas('routine', ${routine.id})">
                                <div class="routine-stack-icon">${icon}</div>
                                <div class="routine-stack-content">
                                    <div class="routine-stack-title">${routine.title}</div>
                                    <div class="routine-stack-time">${routine.time}</div>
                                </div>
                            </div>
                        </div>
                    `);
                });
            }
        });
    }

    // Mobile List Rendering
    if (list && calendarState.calendarData) {
        const date = calendarState.currentDate;
        const dayOfWeek = date.getDay();
        const diff = date.getDate() - dayOfWeek + (dayOfWeek === 0 ? -6 : 1);
        const monday = new Date(date);
        monday.setDate(diff);

        for (let i = 0; i < 7; i++) {
            const dayDate = new Date(monday);
            dayDate.setDate(monday.getDate() + i);
            const dateStr = `${dayDate.getFullYear()}-${String(dayDate.getMonth() + 1).padStart(2, '0')}-${String(dayDate.getDate()).padStart(2, '0')}`;
            const isToday = dayDate.toDateString() === new Date().toDateString();

            // Filter data for this day
            const dayEntries = calendarState.calendarData.entries.filter(e => e.date === dateStr);
            const dayGoals = calendarState.calendarData.goals.filter(g => {
                const dayTs = new Date(dateStr).getTime();
                const startTs = new Date(g.start_date).getTime();
                const endTs = g.end_date ? new Date(g.end_date).getTime() : startTs;
                return dayTs >= startTs && dayTs <= endTs;
            });
            const weekday = dayDate.getDay();
            const routines = calendarState.calendarData.routines || [];
            const dayRoutines = routines.filter(routine => {
                switch (routine.frequency.toLowerCase()) {
                    case 'daily': return true;
                    case 'weekly': return true;
                    default: return false;
                }
            });

            const hasContent = dayEntries.length > 0 || dayGoals.length > 0 || dayRoutines.length > 0;
            if (!hasContent) continue; // Skip empty days

            let listHTML = `
                <div class="calendar-week-list-item-hjn ${isToday ? 'calendar-week-today-hjn' : ''}"
                     data-date="${dateStr}"
                     onclick="openCalendarDayDetail('${dateStr}')">
                    <div class="calendar-week-list-header-hjn">
                        <div class="calendar-week-list-date-hjn">
                            <div class="calendar-week-list-day-hjn">${dayDate.getDate()}</div>
                            <div class="calendar-week-list-weekday-hjn">${dayDate.toLocaleDateString('en-US', { weekday: 'short' }).toUpperCase()}</div>
                        </div>
                        <div class="calendar-week-list-indicators-hjn">
            `;

            if (dayEntries.length > 0) {
                listHTML += `<div class="calendar-day-indicator-hjn calendar-indicator-entry-hjn">${dayEntries.length}</div>`;
            }
            if (dayGoals.length > 0) {
                listHTML += `<div class="calendar-day-indicator-hjn calendar-indicator-goal-hjn">${dayGoals.length}</div>`;
            }
            if (dayRoutines.length > 0) {
                listHTML += `<div class="calendar-day-indicator-hjn calendar-indicator-routine-hjn">${dayRoutines.length}</div>`;
            }

            listHTML += `
                        </div>
                    </div>
                    <div class="calendar-week-list-content-hjn">
            `;

            // Goals
            if (dayGoals.length > 0) {
                dayGoals.forEach(goal => {
                    listHTML += `
                        <div class="calendar-list-goal-hjn">
                            <div class="goal-list-title-hjn">${goal.title}</div>
                            <div class="goal-list-progress-hjn">
                                <div class="goal-bar-fill-hjn" style="width: ${goal.progress}%"></div>
                            </div>
                        </div>
                    `;
                });
            }

            // Routines
            if (dayRoutines.length > 0) {
                listHTML += `<div class="calendar-list-routines-hjn">`;
                dayRoutines.forEach(routine => {
                    const icon = routine.hour < 12 ? '☀️' : '🌙';
                    listHTML += `
                        <div class="calendar-list-routine-hjn">
                            <div class="routine-list-icon-hjn">${icon}</div>
                            <div class="routine-list-content-hjn">
                                <div class="routine-list-title-hjn">${routine.title}</div>
                                <div class="routine-list-time-hjn">${routine.time}</div>
                            </div>
                        </div>
                    `;
                });
                listHTML += `</div>`;
            }

            // Entries
            const previewEntries = dayEntries.slice(0, 3);
            previewEntries.forEach(entry => {
                listHTML += `
                    <div class="calendar-list-entry-hjn">
                        <div class="entry-list-time-hjn">${entry.time}</div>
                        <div class="entry-list-title-hjn">${entry.title}</div>
                        ${entry.mood ? `<div class="entry-list-mood-hjn">${entry.mood}</div>` : ''}
                    </div>
                `;
            });

            if (dayEntries.length > 3) {
                listHTML += `<div class="calendar-list-more-hjn">+${dayEntries.length - 3} more entries</div>`;
            }

            listHTML += `
                    </div>
                </div>
            `;

            list.insertAdjacentHTML('beforeend', listHTML);
        }
    }
}


/**
 * Update day view with current data and compact timeline
 */
/**
 * Update day view with current data and compact timeline
 */
function updateDayView() {
    console.log('Updating day view...');
    const singleDayGrid = document.getElementById('singleDayGrid');
    const singleDayTitle = document.getElementById('singleDayTitle');
    const timeColumn = document.querySelector('#dayViewHjn .calendar-time-column-hjn');

    if (!singleDayGrid || !singleDayTitle || !timeColumn || !calendarState.calendarData) return;

    const date = calendarState.currentDate;
    const dateStr = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;

    // Update title
    singleDayTitle.textContent = date.toLocaleDateString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });

    // Clear previous content
    singleDayGrid.innerHTML = '';
    timeColumn.innerHTML = '';

    // --- Data Filtering ---
    const dayEntries = calendarState.calendarData.entries.filter(e => e.date === dateStr);

    // Routines filtered based on frequency
    const weekday = date.getDay(); // 0=Sun ... 6=Sat
    const routines = calendarState.calendarData.routines || [];
    const dayRoutines = routines.filter(r => {
        switch ((r.frequency || '').toLowerCase()) {
            case 'daily': return true;
            case 'weekly':
                const startDay = new Date(r.start_date).getDay();
                return weekday === startDay;
            case 'bi-weekly':
                return weekday === 1 || weekday === 5;
            case 'monthly':
                return date.getDate() === 1;
            default:
                return r.date === dateStr;
        }
    });

    const dayGoals = calendarState.calendarData.goals.filter(g => {
        const dayTs = new Date(dateStr).setHours(0,0,0,0);
        const startTs = new Date(g.start_date).setHours(0,0,0,0);
        const endTs = g.end_date ? new Date(g.end_date).setHours(0,0,0,0) : startTs;
        return dayTs >= startTs && dayTs <= endTs;
    });

    // --- Normalize routine times ---
    dayRoutines.forEach(r => {
        if (!r.time || !/^\d{2}:\d{2}/.test(r.time)) {
            const timeText = (r.time || '').toLowerCase();
            if (timeText.includes('morning')) r.time = '08:00';
            else if (timeText.includes('evening')) r.time = '18:00';
            else if (timeText.includes('night')) r.time = '21:00';
            else r.time = '08:00';
        }
    });

    // --- Combine and sort all events ---
    const dayEvents = [
        ...dayEntries.map(e => ({ type: 'entry', time: e.time || '12:00', data: e })),
        ...dayRoutines.map(r => ({ type: 'routine', time: r.time || '08:00', data: r }))
    ].sort((a, b) => a.time.localeCompare(b.time));

    // --- Dynamic Compaction of Timeline ---
    const hourHeights = {};
    const expandedHeight = 80;
    const compactedHeight = 20;
    let totalHeight = 0;

    if (dayEvents.length > 0) {
        const eventHours = dayEvents.map(e => parseInt(e.time.split(':')[0]));
        const minHour = Math.max(0, Math.min(...eventHours) - 1);
        const maxHour = Math.min(23, Math.max(...eventHours) + 1);
        for (let i = 0; i < 24; i++) {
            hourHeights[i] = (i >= minHour && i <= maxHour) ? expandedHeight : compactedHeight;
        }
    } else {
        for (let i = 0; i < 24; i++) hourHeights[i] = 40; // Default height if empty
    }

    // --- Render Time Column & Position Map ---
    const positionMap = {};
    let currentTop = 0;
    for (let i = 0; i < 24; i++) {
        timeColumn.insertAdjacentHTML(
            'beforeend',
            `<div class="calendar-time-slot-hjn" style="height:${hourHeights[i]}px;">${String(i).padStart(2,'0')}:00</div>`
        );
        positionMap[i] = currentTop;
        currentTop += hourHeights[i];
    }
    totalHeight = currentTop;
    singleDayGrid.style.height = `${totalHeight}px`;

    // --- Render Goals ---
    dayGoals.forEach((goal, index) => {
        singleDayGrid.insertAdjacentHTML('beforeend', `
            <div class="calendar-week-goal-bar-hjn"
                 style="position:absolute; left:20px; right:20px; top:${index * 45}px;"
                 onclick="openViewOffcanvas('goal', ${goal.id})">
                <div class="goal-bar-content-hjn">
                    <div class="goal-bar-title-hjn">Goal: ${goal.title}</div>
                </div>
            </div>
        `);
    });

    const goalOffset = dayGoals.length * 45;

    // --- Render Entries + Routines on Timeline ---
    dayEvents.forEach(event => {
        const [hourStr, minuteStr] = event.time.split(':');
        const hour = parseInt(hourStr);
        const minute = parseInt(minuteStr);
        const hourTop = positionMap[hour];
        const minuteOffset = (minute / 60) * hourHeights[hour];
        const topPosition = hourTop + minuteOffset + goalOffset;

        let html = '';

        if (event.type === 'entry') {
            const entry = event.data;
            html = `
                <div class="calendar-day-entry-block-hjn" style="top:${topPosition}px;" onclick="openViewOffcanvas('entry', ${entry.id})">
                    ${entry.thumbnail ? `<div class="calendar-day-entry-image-hjn" style="background-image:url('${entry.thumbnail}');"></div>` : ''}
                    <div class="calendar-day-entry-content-hjn">
                        <div class="calendar-day-entry-time-block-hjn">${entry.time}</div>
                        <div class="calendar-day-entry-title-block-hjn">${entry.title}</div>
                        ${entry.mood ? `<div class="calendar-day-entry-mood-block-hjn">${entry.mood}</div>` : ''}
                        ${entry.rating ? `<div class="calendar-day-entry-rating-block-hjn">
                            <svg viewBox="0 0 24 24" width="12" height="12">
                                <path fill="currentColor" d="M12,17.27L18.18,21L16.54,13.97L22,9.24L14.81,8.62L12,2L9.19,8.62L2,9.24L7.45,13.97L5.82,21L12,17.27Z"/>
                            </svg>${entry.rating}/10</div>` : ''}
                    </div>
                </div>`;
        } else if (event.type === 'routine') {
            const routine = event.data;
            const icon = routine.time.includes('08:') ? '☀️' : (routine.time.includes('18:') ? '🌇' : '🌙');
            html = `
                <div class="calendar-day-entry-block-hjn card-style-2" style="top:${topPosition}px;">
                    <div class="calendar-day-entry-content-hjn">
                        <div class="calendar-day-entry-time-block-hjn">${icon} ${routine.time}</div>
                        <div class="calendar-day-entry-title-block-hjn">Routine: ${routine.title}</div>
                        ${routine.steps ? `<div class="calendar-day-entry-mood-block-hjn">${routine.steps.length} steps</div>` : ''}
                    </div>
                </div>`;
        }

        singleDayGrid.insertAdjacentHTML('beforeend', html);
    });
}

/**
 * Open calendar day detail (switches to day view for the clicked date)
 */
function openCalendarDayDetail(dateStr) {
    console.log('Opening calendar day detail for:', dateStr);
    const parts = dateStr.split('-').map(p => parseInt(p, 10));
    calendarState.currentDate = new Date(parts[0], parts[1] - 1, parts[2]);

    switchCalendarView('day');
}

/**
 * Navigates the calendar to a specific date from the entry carousel.
 * @param {string} dateStr - The date string in 'YYYY-MM-DD' format.
 */
function goToDateInCalendar(dateStr) {
    console.log('Navigating calendar to date:', dateStr);
    // Split the YYYY-MM-DD string to avoid timezone issues with the Date constructor.
    const parts = dateStr.split('-').map(p => parseInt(p, 10));
    // Note: The month for the Date constructor is 0-indexed (0=Jan, 1=Feb, etc.)
    calendarState.currentDate = new Date(parts[0], parts[1] - 1, parts[2]);

    // Refresh the current view to show the newly selected date.
    // This function handles updating the correct view (month/week/day) and the date range display.
    switchCalendarView(calendarState.currentView);
}


/**
 * Toggle calendar filters panel visibility
 */
function toggleCalendarFilters() {
    const filtersPanel = document.getElementById('calendarFiltersPanel');
    if (!filtersPanel) return;

    if (filtersPanel.style.display === 'none' || !filtersPanel.style.display) {
        filtersPanel.style.display = 'block';
    } else {
        filtersPanel.style.display = 'none';
    }
}

/**
 * Apply calendar filters based on search and filter inputs
 */
function applyCalendarFilters() {
    const searchInput = document.getElementById('calendarSearchInput');
    const filterEntries = document.getElementById('filterEntries');
    const filterGoals = document.getElementById('filterGoals');
    const filterRoutines = document.getElementById('filterRoutines');
    const filterRating = document.getElementById('filterRating');

    const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
    const showEntries = filterEntries ? filterEntries.checked : true;
    const showGoals = filterGoals ? filterGoals.checked : true;
    const showRoutines = filterRoutines ? filterRoutines.checked : true;
    const minRating = filterRating ? parseInt(filterRating.value) : 0;

    console.log('Applying filters:', { searchTerm, showEntries, showGoals, showRoutines, minRating });

    // Filter entry preview cards in month view
    const entryPreviews = document.querySelectorAll('.calendar-day-entry-preview-hjn');
    entryPreviews.forEach(preview => {
        const title = preview.querySelector('.calendar-entry-title-hjn')?.textContent.toLowerCase() || '';
        const matchesSearch = !searchTerm || title.includes(searchTerm);
        const isVisible = showEntries && matchesSearch;

        preview.style.display = isVisible ? 'flex' : 'none';
    });

    // Filter goal connectors in month view
    const goalConnectors = document.querySelectorAll('.goal-connector-hjn');
    goalConnectors.forEach(connector => {
        const label = connector.querySelector('.goal-label-hjn')?.textContent.toLowerCase() || '';
        const matchesSearch = !searchTerm || label.includes(searchTerm);
        const isVisible = showGoals && matchesSearch;

        connector.style.display = isVisible ? 'block' : 'none';
    });

    // Filter routine cards in week/day views
    const routineCards = document.querySelectorAll('.routine-stack-card');
    routineCards.forEach(card => {
        const title = card.querySelector('.routine-stack-title')?.textContent.toLowerCase() || '';
        const matchesSearch = !searchTerm || title.includes(searchTerm);
        const isVisible = showRoutines && matchesSearch;

        card.closest('.routine-stack-container').style.display = isVisible ? 'block' : 'none';
    });

    // Filter week view entries
    const weekEntries = document.querySelectorAll('.calendar-week-entry-hjn');
    weekEntries.forEach(entry => {
        const title = entry.querySelector('.calendar-week-entry-title-hjn')?.textContent.toLowerCase() || '';
        const ratingStars = entry.querySelectorAll('.entry-rating-star-hjn.filled');
        const entryRating = ratingStars.length;

        const matchesSearch = !searchTerm || title.includes(searchTerm);
        const matchesRating = minRating === 0 || entryRating >= minRating;
        const isVisible = showEntries && matchesSearch && matchesRating;

        entry.style.display = isVisible ? 'block' : 'none';
    });

    // Filter day view entry blocks
    const dayEntries = document.querySelectorAll('.calendar-day-entry-block-hjn');
    dayEntries.forEach(entry => {
        const title = entry.querySelector('.calendar-day-entry-title-hjn')?.textContent.toLowerCase() || '';
        const ratingStars = entry.querySelectorAll('.entry-rating-star-hjn.filled');
        const entryRating = ratingStars.length;

        const matchesSearch = !searchTerm || title.includes(searchTerm);
        const matchesRating = minRating === 0 || entryRating >= minRating;
        const isVisible = showEntries && matchesSearch && matchesRating;

        entry.style.display = isVisible ? 'flex' : 'none';
    });

    // Filter mobile week list items
    const weekListItems = document.querySelectorAll('.calendar-week-list-item-hjn');
    weekListItems.forEach(item => {
        const title = item.querySelector('.week-list-item-title-hjn')?.textContent.toLowerCase() || '';
        const type = item.dataset.type || 'entry';

        const matchesSearch = !searchTerm || title.includes(searchTerm);
        let isVisible = matchesSearch;

        if (type === 'entry') isVisible = isVisible && showEntries;
        if (type === 'goal') isVisible = isVisible && showGoals;
        if (type === 'routine') isVisible = isVisible && showRoutines;

        item.style.display = isVisible ? 'flex' : 'none';
    });

    console.log('Filters applied successfully');
}

/**
 * Clear all calendar filters and reset to defaults
 */
function clearCalendarFilters() {
    const searchInput = document.getElementById('calendarSearchInput');
    const filterEntries = document.getElementById('filterEntries');
    const filterGoals = document.getElementById('filterGoals');
    const filterRoutines = document.getElementById('filterRoutines');
    const filterRating = document.getElementById('filterRating');

    if (searchInput) searchInput.value = '';
    if (filterEntries) filterEntries.checked = true;
    if (filterGoals) filterGoals.checked = true;
    if (filterRoutines) filterRoutines.checked = true;
    if (filterRating) filterRating.value = '0';

    applyCalendarFilters();

    console.log('Filters cleared');
}

/**
 * Carousel scroll functionality
 */
function scrollCarousel(direction) {
    const track = document.getElementById('carouselTrack');
    if (track) {
        const itemWidth = track.querySelector('.carousel-item')?.offsetWidth || 200;
        track.scrollBy({ left: direction * itemWidth, behavior: 'smooth' });
    }
}


// Initialize calendar when the DOM is loaded and ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing calendar.');
    initCalendarView();
});