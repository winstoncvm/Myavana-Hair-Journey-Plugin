// Calendar state management
const calendarState = {
    currentView: 'month', // month, week, day
    currentDate: new Date(),
    calendarData: null,
    dayViewFilter: 'all'
};
let calendarInitialized = false;

function escapeCalendarText(value) {
    return String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function truncateCalendarText(value, maxLength = 32) {
    const text = String(value || '').trim();
    if (text.length <= maxLength) {
        return text;
    }
    return `${text.slice(0, Math.max(0, maxLength - 1)).trimEnd()}…`;
}

function getCalendarSettings() {
    return window.myavanaTimelineSettings || window.myavanaAjax || {};
}

function getCalendarDateKey(dateObj) {
    const date = dateObj instanceof Date ? dateObj : new Date();
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
}

function getRoutineRecordsMap() {
    if (!calendarState.calendarData) {
        return {};
    }

    if (!calendarState.calendarData.routine_records || typeof calendarState.calendarData.routine_records !== 'object') {
        calendarState.calendarData.routine_records = {};
    }

    return calendarState.calendarData.routine_records;
}

function getRoutineRecord(routineId, dateStr) {
    const records = getRoutineRecordsMap();
    const dateRecords = records[dateStr];
    if (!dateRecords || typeof dateRecords !== 'object') {
        return null;
    }

    return dateRecords[String(parseInt(routineId, 10))] || null;
}

function getRoutineStatusForDate(routineId, dateStr) {
    const record = getRoutineRecord(routineId, dateStr);
    return record && record.status ? String(record.status) : '';
}

function findRoutineById(routineId) {
    const routines = calendarState.calendarData && Array.isArray(calendarState.calendarData.routines)
        ? calendarState.calendarData.routines
        : [];

    return routines.find((routine) => parseInt(routine.id, 10) === parseInt(routineId, 10)) || null;
}

function formatRoutineStatusLabel(status) {
    switch (String(status || '')) {
        case 'completed':
            return 'Done';
        case 'skipped':
            return 'Skipped';
        case 'snoozed':
            return 'Snoozed';
        default:
            return 'Due';
    }
}

function formatRoutineDateLabel(dateStr) {
    if (!dateStr) return '';
    const parts = String(dateStr).split('-').map((part) => parseInt(part, 10));
    const date = new Date(parts[0], (parts[1] || 1) - 1, parts[2] || 1);
    if (Number.isNaN(date.getTime())) return '';
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}

function sortRoutinesByTime(routines) {
    return [...routines].sort((a, b) => String(a.time || '08:00').localeCompare(String(b.time || '08:00')));
}

function buildRoutineActionButtons(routineId, dateStr, status, compact = false) {
    const actionClass = compact ? ' is-compact' : '';
    const safeRoutineId = parseInt(routineId, 10);
    const safeDate = escapeCalendarText(dateStr);
    const currentStatus = String(status || '');

    if (currentStatus === 'completed') {
        return `
            <div class="calendar-routine-actions-hjn${actionClass}">
                <button type="button" class="calendar-routine-action-btn-hjn is-complete" onclick="event.stopPropagation(); calendarRoutineSetStatus(${safeRoutineId}, '${safeDate}', 'clear')">Undo</button>
                <button type="button" class="calendar-routine-action-btn-hjn" onclick="event.stopPropagation(); openViewOffcanvas('routine', ${safeRoutineId})">View</button>
            </div>
        `;
    }

    return `
        <div class="calendar-routine-actions-hjn${actionClass}">
            <button type="button" class="calendar-routine-action-btn-hjn is-complete" onclick="event.stopPropagation(); calendarRoutineSetStatus(${safeRoutineId}, '${safeDate}', 'completed')">Done</button>
            <button type="button" class="calendar-routine-action-btn-hjn" onclick="event.stopPropagation(); calendarRoutineSetStatus(${safeRoutineId}, '${safeDate}', 'snoozed')">Snooze</button>
            <button type="button" class="calendar-routine-action-btn-hjn ghost" onclick="event.stopPropagation(); calendarRoutineSetStatus(${safeRoutineId}, '${safeDate}', 'skipped')">Skip</button>
        </div>
    `;
}

function getRoutinePlannerSnapshot(dateObj) {
    const snapshotDate = dateObj instanceof Date ? dateObj : new Date();
    const dateStr = getCalendarDateKey(snapshotDate);
    const routines = Array.isArray(calendarState.calendarData?.routines) ? calendarState.calendarData.routines : [];
    const dueToday = [];
    const pendingToday = [];
    const completedToday = [];
    const overdue = [];
    const upcoming = [];

    let adherenceTotal = 0;
    let adherenceCount = 0;
    let bestStreak = 0;

    routines.forEach((routine) => {
        const routineId = parseInt(routine.id, 10);
        const status = getRoutineStatusForDate(routineId, dateStr);
        const due = routineMatchesDate(routine, snapshotDate);
        const adherence = parseInt(routine.completion_rate_30d || 0, 10) || 0;
        const streak = parseInt(routine.current_streak || 0, 10) || 0;

        adherenceTotal += adherence;
        adherenceCount += 1;
        bestStreak = Math.max(bestStreak, streak);

        if (due) {
            dueToday.push(routine);
            if (status === 'completed') {
                completedToday.push(routine);
            } else if (status !== 'skipped' && status !== 'snoozed') {
                pendingToday.push(routine);
            }
        }

        if (!due) {
            const lastDue = routine.last_due_date || '';
            const lastDueStatus = lastDue ? getRoutineStatusForDate(routineId, lastDue) : '';
            if (lastDue && lastDue < dateStr && lastDueStatus !== 'completed' && lastDueStatus !== 'skipped') {
                overdue.push(routine);
            }
        }

        const nextDue = routine.next_due_date || '';
        if (nextDue && nextDue > dateStr && nextDue <= getCalendarDateKey(new Date(snapshotDate.getFullYear(), snapshotDate.getMonth(), snapshotDate.getDate() + 7))) {
            upcoming.push(routine);
        }
    });

    const notifications = [];
    overdue.slice(0, 3).forEach((routine) => {
        notifications.push({
            type: 'overdue',
            routine,
            date: routine.last_due_date || dateStr,
            message: 'Missed the last scheduled session. Log it or skip it so your planner stays honest.'
        });
    });
    pendingToday.slice(0, 4).forEach((routine) => {
        notifications.push({
            type: 'due',
            routine,
            date: dateStr,
            message: 'Due today. Mark it complete here when you finish.'
        });
    });
    if (notifications.length < 4) {
        upcoming.slice(0, 4 - notifications.length).forEach((routine) => {
            notifications.push({
                type: 'upcoming',
                routine,
                date: routine.next_due_date || '',
                message: 'Coming up soon. Prep products and tools now so you stay on cadence.'
            });
        });
    }

    return {
        dateStr,
        dueToday,
        completedToday,
        pendingToday,
        overdue,
        upcoming,
        notifications,
        adherence30d: adherenceCount > 0 ? Math.round(adherenceTotal / adherenceCount) : 0,
        bestStreak
    };
}

function renderRoutinePlanner() {
    const summaryEl = document.getElementById('calendarRoutineSummary');
    const notificationsEl = document.getElementById('calendarRoutineNotifications');
    if (!summaryEl || !notificationsEl || !calendarState.calendarData) {
        return;
    }

    const snapshot = getRoutinePlannerSnapshot(calendarState.currentDate);
    const summaryCards = [
        { label: 'Due Today', value: snapshot.dueToday.length, meta: snapshot.pendingToday.length ? `${snapshot.pendingToday.length} still open` : 'All caught up' },
        { label: 'Completed', value: snapshot.completedToday.length, meta: `${snapshot.dateStr === (calendarState.calendarData.today || '') ? 'today' : formatRoutineDateLabel(snapshot.dateStr)}` },
        { label: 'Overdue', value: snapshot.overdue.length, meta: snapshot.overdue.length ? 'Needs attention' : 'Nothing slipping' },
        { label: '30-Day Adherence', value: `${snapshot.adherence30d}%`, meta: 'Across active routines' },
        { label: 'Best Streak', value: snapshot.bestStreak, meta: 'Routine sessions in a row' }
    ];

    summaryEl.innerHTML = summaryCards.map((card) => `
        <article class="calendar-routine-summary-card-hjn">
            <span class="calendar-routine-summary-label-hjn">${escapeCalendarText(card.label)}</span>
            <strong class="calendar-routine-summary-value-hjn">${escapeCalendarText(card.value)}</strong>
            <span class="calendar-routine-summary-meta-hjn">${escapeCalendarText(card.meta)}</span>
        </article>
    `).join('');

    if (snapshot.notifications.length === 0) {
        notificationsEl.innerHTML = `
            <div class="calendar-routine-empty-hjn">
                <strong>No routine reminders right now</strong>
                <span>Your current schedule is clear. Use the planner as you log completions through the week.</span>
            </div>
        `;
        return;
    }

    notificationsEl.innerHTML = snapshot.notifications.map((item) => {
        const routine = item.routine || {};
        const routineId = parseInt(routine.id, 10);
        const dateStr = item.date || snapshot.dateStr;
        const status = getRoutineStatusForDate(routineId, dateStr);
        return `
            <article class="calendar-routine-notice-hjn is-${escapeCalendarText(item.type)}">
                <div class="calendar-routine-notice-copy-hjn">
                    <span class="calendar-routine-notice-type-hjn">${escapeCalendarText(item.type === 'due' ? 'Due now' : item.type === 'overdue' ? 'Overdue' : 'Upcoming')}</span>
                    <h4>${escapeCalendarText(routine.title || 'Routine')}</h4>
                    <p>${escapeCalendarText(item.message)}</p>
                    <div class="calendar-routine-notice-meta-hjn">
                        <span>${escapeCalendarText(routine.time || '08:00')}</span>
                        <span>${escapeCalendarText(formatRoutineDateLabel(dateStr))}</span>
                        <span>${escapeCalendarText(formatRoutineStatusLabel(status || 'due'))}</span>
                    </div>
                </div>
                ${buildRoutineActionButtons(routineId, dateStr, status, true)}
            </article>
        `;
    }).join('');
}

function updateRoutineRecordLocal(routineId, dateStr, status, payload) {
    const records = getRoutineRecordsMap();
    const recordKey = String(parseInt(routineId, 10));
    if (status === 'clear') {
        if (records[dateStr]) {
            delete records[dateStr][recordKey];
            if (Object.keys(records[dateStr]).length === 0) {
                delete records[dateStr];
            }
        }
    } else {
        if (!records[dateStr] || typeof records[dateStr] !== 'object') {
            records[dateStr] = {};
        }
        const parts = String(dateStr).split('-').map((part) => parseInt(part, 10));
        const snoozeDate = new Date(parts[0], (parts[1] || 1) - 1, (parts[2] || 1) + 1);
        records[dateStr][recordKey] = {
            status,
            logged_at: new Date().toISOString(),
            source: 'calendar',
            snoozed_until: status === 'snoozed' ? getCalendarDateKey(snoozeDate) : ''
        };
    }

    const routine = findRoutineById(routineId);
    if (routine) {
        routine.today_status = getRoutineStatusForDate(routineId, calendarState.calendarData.today || getCalendarDateKey(new Date()));
        routine.last_completed = payload?.routine?.last_completed || routine.last_completed || '';
        routine.current_streak = payload?.routine?.current_streak ?? routine.current_streak ?? 0;
        routine.completion_rate_30d = payload?.routine?.completion_rate_30d ?? routine.completion_rate_30d ?? 0;
        routine.last_due_date = payload?.routine?.last_due_date || routine.last_due_date || '';
        routine.next_due_date = payload?.routine?.next_due_date || routine.next_due_date || '';
    }
}

function showCalendarNotice(message, type = 'info') {
    if (window.Myavana && window.Myavana.UI && typeof window.Myavana.UI.notify === 'function') {
        window.Myavana.UI.notify(message, type);
        return;
    }

    const notification = document.createElement('div');
    notification.className = `timeline-notification ${type}`;
    notification.textContent = message;
    notification.style.position = 'fixed';
    notification.style.top = '120px';
    notification.style.right = '18px';
    notification.style.zIndex = '100002';
    notification.style.padding = '12px 16px';
    notification.style.borderRadius = '14px';
    notification.style.background = type === 'success' ? '#1f7a52' : type === 'error' ? '#8f2d2d' : '#21242c';
    notification.style.color = '#fff';
    document.body.appendChild(notification);
    setTimeout(() => notification.remove(), 2800);
}

async function parseCalendarAjaxResponse(response) {
    const raw = await response.text();

    try {
        return JSON.parse(raw);
    } catch (error) {
        const fallbackMessage = raw
            .replace(/<[^>]+>/g, ' ')
            .replace(/\s+/g, ' ')
            .trim()
            .slice(0, 220);
        throw new Error(fallbackMessage || 'Unexpected server response.');
    }
}

async function calendarRoutineSetStatus(routineId, dateStr, status) {
    const settings = getCalendarSettings();
    const ajaxUrl = settings.ajaxUrl || settings.ajaxurl || settings.ajax_url;
    const security = settings.toggleRoutineNonce || settings.addRoutineNonce || settings.nonce || '';

    if (!ajaxUrl || !security) {
        showCalendarNotice('Routine tracking is not available right now.', 'error');
        return;
    }

    try {
        const response = await fetch(ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            body: new URLSearchParams({
                action: 'myavana_update_routine_tracking_status',
                security,
                routine_id: String(routineId),
                date: String(dateStr),
                status: String(status)
            }).toString()
        });

        const result = await parseCalendarAjaxResponse(response);
        if (!result || !result.success) {
            throw new Error((result && result.data) || 'Unable to update routine');
        }

        updateRoutineRecordLocal(routineId, dateStr, status, result.data || {});
        if (result.data && result.data.records) {
            calendarState.calendarData.routine_records = result.data.records;
        }
        if (result.data && result.data.summary) {
            calendarState.calendarData.routine_summary = result.data.summary;
        }
        if (result.data && result.data.notifications) {
            calendarState.calendarData.routine_notifications = result.data.notifications;
        }

        switchCalendarView(calendarState.currentView);
        renderRoutinePlanner();
        showCalendarNotice(result.data?.message || 'Routine updated.', 'success');
    } catch (error) {
        console.error('Routine tracking update failed:', error);
        showCalendarNotice(error.message || 'Routine update failed.', 'error');
    }
}

window.calendarRoutineSetStatus = calendarRoutineSetStatus;

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

    const dayFilterGroup = document.getElementById('dayViewFilterGroup');
    if (dayFilterGroup && !dayFilterGroup.dataset.bound) {
        dayFilterGroup.dataset.bound = '1';
        dayFilterGroup.addEventListener('click', (event) => {
            const btn = event.target.closest('[data-day-filter]');
            if (!btn) return;
            calendarState.dayViewFilter = btn.dataset.dayFilter || 'all';
            dayFilterGroup.querySelectorAll('.calendar-day-filter-btn-hjn').forEach((node) => {
                node.classList.toggle('active', node === btn);
            });
            if (calendarState.currentView === 'day') {
                updateDayView();
            }
        });
    }

    // Set initial view
    switchCalendarView('month');
    renderRoutinePlanner();
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
    renderRoutinePlanner();
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
function routineMatchesDate(routine, dateObj) {
    if (!routine || !dateObj) return false;

    const frequency = String(routine.frequency || routine.routine_frequency || 'weekly').toLowerCase().trim();
    const createdAtRaw = routine.created_date || routine.created_at || routine.date_created || '';
    const createdAt = createdAtRaw ? new Date(createdAtRaw) : new Date(dateObj);
    const reminderRaw = String(routine.routine_reminder_days || '').trim();
    const weekdayShort = dateObj.toLocaleDateString('en-US', { weekday: 'short' });
    const weekdayCreated = createdAt.toLocaleDateString('en-US', { weekday: 'short' });

    const reminderDays = reminderRaw
        ? reminderRaw
            .split(/[,\s/]+/)
            .map((d) => d.trim())
            .filter(Boolean)
            .map((d) => d.slice(0, 3).toLowerCase())
        : [];

    const msPerDay = 24 * 60 * 60 * 1000;
    const diffDays = Math.floor(Math.abs(dateObj.getTime() - createdAt.getTime()) / msPerDay);

    if (reminderDays.length > 0 && !reminderDays.includes(weekdayShort.slice(0, 3).toLowerCase())) {
        return false;
    }

    switch (frequency) {
        case 'daily':
            return true;
        case 'weekly':
            return reminderDays.length > 0 ? true : weekdayShort === weekdayCreated;
        case 'bi-weekly':
        case 'biweekly':
            return (reminderDays.length > 0 || weekdayShort === weekdayCreated) && diffDays % 14 === 0;
        case 'monthly':
            return dateObj.getDate() === createdAt.getDate();
        case 'as needed':
        case 'as-needed':
        case 'asneeded':
            return false;
        default:
            return reminderDays.length > 0 ? true : weekdayShort === weekdayCreated;
    }
}

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
        const dayDate = new Date(currentDateStr);
        const dayEntries = calendarState.calendarData.entries.filter((e) => e.date === currentDateStr);
        const dayGoals = calendarState.calendarData.goals.filter((g) => {
            const dayTs = dayDate.getTime();
            const startTs = new Date(g.start_date).getTime();
            const endTs = g.end_date ? new Date(g.end_date).getTime() : startTs;
            return dayTs >= startTs && dayTs <= endTs;
        });
        const dayRoutines = (calendarState.calendarData.routines || []).filter((routine) => routineMatchesDate(routine, dayDate));

        const hasContent = dayEntries.length > 0 || dayGoals.length > 0 || dayRoutines.length > 0;

        // Desktop Grid Cell
        if (grid) {
            let cellHTML = `
                <div class="calendar-day-cell-hjn ${isToday ? 'calendar-day-today-hjn' : ''} ${hasContent ? 'calendar-day-has-content-hjn' : ''}"
                     data-date="${currentDateStr}"
                     onclick="openCalendarDayDetail('${currentDateStr}')">
                    <div class="calendar-day-number-hjn">${day}</div>
            `;

        if (hasContent) {
            cellHTML += `<div class="calendar-day-indicators-hjn">`;
            if (dayEntries.length > 0) {
                cellHTML += `<div class="calendar-day-indicator-hjn calendar-indicator-entry-hjn" title="${dayEntries.length} entries">${dayEntries.length}</div>`;
            }
            if (dayGoals.length > 0) {
                cellHTML += `<div class="calendar-day-indicator-hjn calendar-indicator-goal-hjn" title="${dayGoals.length} goals">${dayGoals.length}</div>`;
            }
            if (dayRoutines.length > 0) {
                cellHTML += `<div class="calendar-day-indicator-hjn calendar-indicator-routine-hjn" title="${dayRoutines.length} routines">${dayRoutines.length}</div>`;
            }
            cellHTML += `</div>`;

            cellHTML += `<div class="calendar-day-focus-hjn">`;
            if (dayGoals.length > 0) {
                const goalTitle = escapeCalendarText(truncateCalendarText(dayGoals[0].title || '', 24));
                cellHTML += `
                    <div class="calendar-day-focus-chip-hjn is-goal" title="${goalTitle}">
                        <span class="chip-icon-hjn">🎯</span>
                        <span class="chip-text-hjn">${goalTitle}</span>
                    </div>
                `;
            }
            if (dayRoutines.length > 0) {
                const routine = dayRoutines[0];
                const icon = Number(routine.hour || 0) < 12 ? '☀️' : '🌙';
                const routineTitle = escapeCalendarText(truncateCalendarText(routine.title || '', 24));
                const routineStatus = getRoutineStatusForDate(routine.id, currentDateStr);
                cellHTML += `
                    <div class="calendar-day-focus-chip-hjn is-routine ${routineStatus === 'completed' ? 'is-complete' : ''}" title="${routineTitle}">
                        <span class="chip-icon-hjn">${icon}</span>
                        <span class="chip-text-hjn">${routineTitle}</span>
                        <span class="chip-state-hjn">${escapeCalendarText(formatRoutineStatusLabel(routineStatus || 'due'))}</span>
                    </div>
                `;
            }
            const extraFocusCount = Math.max(0, dayGoals.length - 1) + Math.max(0, dayRoutines.length - 1);
            if (extraFocusCount > 0) {
                cellHTML += `<div class="calendar-day-focus-chip-hjn is-more">+${extraFocusCount} more</div>`;
            }
            cellHTML += `</div>`;
        }

        // ---- ENTRIES ----
        dayEntries.slice(0, 2).forEach((entry) => {
            const entryTitle = escapeCalendarText(entry.title || '');
            const entryTime = escapeCalendarText(entry.time || '');
            const entryThumb = entry.thumbnail ? escapeCalendarText(entry.thumbnail) : '';
            cellHTML += `
                <div class="calendar-day-entry-preview-hjn"
                     onclick="event.stopPropagation(); openViewOffcanvas('entry', ${entry.id})">
                    ${entryThumb
                        ? `<span class="calendar-entry-thumb-hjn" style="background-image:url('${entryThumb}');"></span>`
                        : `<span class="calendar-entry-thumb-hjn is-fallback">📸</span>`}
                    <span class="calendar-entry-preview-body-hjn">
                        <span class="calendar-entry-time-hjn">${entryTime}</span>
                        <span class="calendar-entry-title-hjn">${entryTitle}</span>
                    </span>
                </div>
            `;
        });
        if (dayEntries.length > 2) {
            cellHTML += `<div class="calendar-day-more-hjn">+${dayEntries.length - 2} more</div>`;
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
                dayGoals.slice(0, 2).forEach(goal => {
                    const goalTitle = escapeCalendarText(goal.title);
                    listHTML += `
                        <div class="calendar-list-goal-hjn">
                            <div class="goal-list-title-hjn">${goalTitle}</div>
                            <div class="goal-list-progress-hjn">
                                <div class="goal-bar-fill-hjn" style="width: ${goal.progress}%"></div>
                            </div>
                        </div>
                    `;
                });
                if (dayGoals.length > 2) {
                    listHTML += `<div class="calendar-list-more-hjn">+${dayGoals.length - 2} more goals</div>`;
                }
            }

            // Routines
            if (dayRoutines.length > 0) {
                listHTML += `<div class="calendar-list-routines-hjn">`;
                dayRoutines.slice(0, 2).forEach(routine => {
                    const icon = routine.hour < 12 ? '☀️' : '🌙';
                    const routineTitle = escapeCalendarText(truncateCalendarText(routine.title, 30));
                    const routineTime = escapeCalendarText(routine.time);
                    const routineStatus = getRoutineStatusForDate(routine.id, currentDateStr);
                    listHTML += `
                        <div class="calendar-list-routine-hjn ${routineStatus === 'completed' ? 'is-complete' : ''}">
                            <div class="routine-list-icon-hjn">${icon}</div>
                            <div class="routine-list-content-hjn">
                                <div class="routine-list-title-hjn">${routineTitle}</div>
                                <div class="routine-list-time-hjn">${routineTime} · ${escapeCalendarText(formatRoutineStatusLabel(routineStatus || 'due'))}</div>
                            </div>
                        </div>
                    `;
                });
                listHTML += `</div>`;
                if (dayRoutines.length > 2) {
                    listHTML += `<div class="calendar-list-more-hjn">+${dayRoutines.length - 2} more routines</div>`;
                }
            }

            // Entries
            const previewEntries = dayEntries.slice(0, 3);
            previewEntries.forEach(entry => {
                const entryTime = escapeCalendarText(entry.time);
                const entryTitle = escapeCalendarText(entry.title);
                const entryMood = escapeCalendarText(entry.mood);
                const entryThumb = entry.thumbnail ? escapeCalendarText(entry.thumbnail) : '';
                listHTML += `
                    <div class="calendar-list-entry-hjn">
                        ${entryThumb
                            ? `<span class="entry-list-thumb-hjn" style="background-image:url('${entryThumb}');"></span>`
                            : `<span class="entry-list-thumb-hjn is-fallback">📸</span>`}
                        <div class="entry-list-body-hjn">
                            <div class="entry-list-time-hjn">${entryTime}</div>
                            <div class="entry-list-title-hjn">${entryTitle}</div>
                            ${entry.mood ? `<div class="entry-list-mood-hjn">${entryMood}</div>` : ''}
                        </div>
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
            const dayRoutines = routines.filter((routine) => routineMatchesDate(routine, dayDate));

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
                    const routineStatus = getRoutineStatusForDate(routine.id, dateStr);
                    const routineTitle = escapeCalendarText(truncateCalendarText(routine.title, 18));

                    col.insertAdjacentHTML('beforeend', `
                        <div class="routine-stack-container" style="top: ${topPosition}px;">
                            <div class="routine-stack-card ${routineStatus === 'completed' ? 'is-complete' : ''}" onclick="openViewOffcanvas('routine', ${routine.id})">
                                <div class="routine-stack-icon">${icon}</div>
                                <div class="routine-stack-content">
                                    <div class="routine-stack-title">${routineTitle}</div>
                                    <div class="routine-stack-time">${routine.time} · ${escapeCalendarText(formatRoutineStatusLabel(routineStatus || 'due'))}</div>
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
            const routines = calendarState.calendarData.routines || [];
            const dayRoutines = routines.filter((routine) => routineMatchesDate(routine, dayDate));

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
                    const routineStatus = getRoutineStatusForDate(routine.id, dateStr);
                    const routineTitle = escapeCalendarText(truncateCalendarText(routine.title, 28));
                    listHTML += `
                        <div class="calendar-list-routine-hjn ${routineStatus === 'completed' ? 'is-complete' : ''}">
                            <div class="routine-list-icon-hjn">${icon}</div>
                            <div class="routine-list-content-hjn">
                                <div class="routine-list-title-hjn">${routineTitle}</div>
                                <div class="routine-list-time-hjn">${routine.time} · ${escapeCalendarText(formatRoutineStatusLabel(routineStatus || 'due'))}</div>
                            </div>
                        </div>
                    `;
                });
                listHTML += `</div>`;
            }

            // Entries
            const previewEntries = dayEntries.slice(0, 3);
            previewEntries.forEach(entry => {
                const entryThumb = entry.thumbnail ? escapeCalendarText(entry.thumbnail) : '';
                const entryTime = escapeCalendarText(entry.time);
                const entryTitle = escapeCalendarText(entry.title);
                const entryMood = escapeCalendarText(entry.mood);
                listHTML += `
                    <div class="calendar-list-entry-hjn">
                        ${entryThumb
                            ? `<span class="entry-list-thumb-hjn" style="background-image:url('${entryThumb}');"></span>`
                            : `<span class="entry-list-thumb-hjn is-fallback">📸</span>`}
                        <div class="entry-list-body-hjn">
                            <div class="entry-list-time-hjn">${entryTime}</div>
                            <div class="entry-list-title-hjn">${entryTitle}</div>
                            ${entry.mood ? `<div class="entry-list-mood-hjn">${entryMood}</div>` : ''}
                        </div>
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
    const dayViewSummary = document.getElementById('dayViewSummary');
    const dayGoalsStrip = document.getElementById('dayGoalsStrip');
    const dayViewFilterGroup = document.getElementById('dayViewFilterGroup');

    if (!singleDayGrid || !singleDayTitle || !timeColumn || !calendarState.calendarData) return;

    const date = calendarState.currentDate;
    const dateStr = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
    const activeFilter = calendarState.dayViewFilter || 'all';

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
    if (dayGoalsStrip) dayGoalsStrip.innerHTML = '';

    if (dayViewFilterGroup) {
        dayViewFilterGroup.querySelectorAll('.calendar-day-filter-btn-hjn').forEach((btn) => {
            btn.classList.toggle('active', btn.dataset.dayFilter === activeFilter);
        });
    }

    // --- Data Filtering ---
    const dayEntries = calendarState.calendarData.entries.filter(e => e.date === dateStr);

    // Routines filtered based on schedule
    const routines = calendarState.calendarData.routines || [];
    const dayRoutines = routines.filter((routine) => routineMatchesDate(routine, date));

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
        const parsedHour = parseInt(String(r.time).split(':')[0], 10);
        r.hour = Number.isFinite(parsedHour) ? parsedHour : 8;
    });

    if (dayViewSummary) {
        const completedRoutineCount = dayRoutines.filter((routine) => getRoutineStatusForDate(routine.id, dateStr) === 'completed').length;
        dayViewSummary.innerHTML = `
            <span class="calendar-day-summary-pill-hjn"><strong>${dayEntries.length}</strong> Entries</span>
            <span class="calendar-day-summary-pill-hjn"><strong>${dayRoutines.length}</strong> Routines</span>
            <span class="calendar-day-summary-pill-hjn"><strong>${completedRoutineCount}</strong> Completed</span>
            <span class="calendar-day-summary-pill-hjn"><strong>${dayGoals.length}</strong> Goals</span>
        `;
    }

    if (dayGoalsStrip) {
        if (dayGoals.length === 0) {
            dayGoalsStrip.innerHTML = '<div class="calendar-day-goal-empty-hjn">No active goals for this day.</div>';
        } else {
            const goalsHtml = dayGoals.map((goal) => {
                const goalTitle = escapeCalendarText(goal.title || 'Goal');
                const goalProgress = Math.max(0, Math.min(100, parseInt(goal.progress || 0, 10)));
                return `
                    <button type="button" class="calendar-day-goal-chip-hjn" onclick="openViewOffcanvas('goal', ${goal.id})">
                        <span class="calendar-day-goal-title-hjn">${goalTitle}</span>
                        <span class="calendar-day-goal-progress-hjn">${goalProgress}%</span>
                    </button>
                `;
            }).join('');
            dayGoalsStrip.innerHTML = goalsHtml;
        }
    }

    // --- Combine and sort visible events by active filter ---
    const dayEvents = [];
    if (activeFilter === 'all' || activeFilter === 'entries') {
        dayEvents.push(...dayEntries.map(e => ({ type: 'entry', time: e.time || '12:00', data: e })));
    }
    if (activeFilter === 'all' || activeFilter === 'routines') {
        dayEvents.push(...dayRoutines.map(r => ({ type: 'routine', time: r.time || '08:00', data: r })));
    }
    dayEvents.sort((a, b) => String(a.time).localeCompare(String(b.time)));

    // --- Dynamic Compaction of Timeline ---
    const hourHeights = {};
    const expandedHeight = 80;
    const compactedHeight = 20;
    let totalHeight = 0;

    if (dayEvents.length > 0) {
        const eventHours = dayEvents.map(e => parseInt(String(e.time).split(':')[0], 10) || 0);
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
    let maxEventBottom = 0;
    let previousBottom = -Infinity;

    // --- Render Entries + Routines on Timeline ---
    dayEvents.forEach(event => {
        const [hourStr, minuteStr] = String(event.time || '00:00').split(':');
        const hour = Math.max(0, Math.min(23, parseInt(hourStr, 10) || 0));
        const minute = Math.max(0, Math.min(59, parseInt(minuteStr, 10) || 0));
        const hourTop = positionMap[hour];
        const minuteOffset = (minute / 60) * hourHeights[hour];
        let topPosition = hourTop + minuteOffset;
        const estimatedHeight = event.type === 'entry'
            ? ((event.data && event.data.thumbnail) ? 90 : 74)
            : 72;

        // Prevent hard overlap when events have very close times.
        if (topPosition < previousBottom + 6) {
            topPosition = previousBottom + 6;
        }
        previousBottom = topPosition + estimatedHeight;
        maxEventBottom = Math.max(maxEventBottom, previousBottom);

        let html = '';

        if (event.type === 'entry') {
            const entry = event.data;
            const entryThumb = entry.thumbnail ? escapeCalendarText(entry.thumbnail) : '';
            const entryTime = escapeCalendarText(entry.time);
            const entryTitle = escapeCalendarText(entry.title);
            const entryMood = escapeCalendarText(entry.mood);
            html = `
                <div class="calendar-day-entry-block-hjn" style="top:${topPosition}px;" onclick="openViewOffcanvas('entry', ${entry.id})">
                    ${entryThumb
                        ? `<div class="calendar-day-entry-image-hjn" style="background-image:url('${entryThumb}');"></div>`
                        : `<div class="calendar-day-entry-icon-hjn">📸</div>`}
                    <div class="calendar-day-entry-content-hjn">
                        <div class="calendar-day-entry-meta-row-hjn">
                            <div class="calendar-day-entry-time-block-hjn">${entryTime}</div>
                            ${entry.rating ? `<div class="calendar-day-entry-rating-block-hjn">
                                <svg viewBox="0 0 24 24" width="12" height="12">
                                    <path fill="currentColor" d="M12,17.27L18.18,21L16.54,13.97L22,9.24L14.81,8.62L12,2L9.19,8.62L2,9.24L7.45,13.97L5.82,21L12,17.27Z"/>
                                </svg>${entry.rating}/10</div>` : ''}
                        </div>
                        <div class="calendar-day-entry-title-block-hjn">${entryTitle}</div>
                        ${entry.mood ? `<div class="calendar-day-entry-mood-block-hjn">${entryMood}</div>` : ''}
                    </div>
                </div>`;
        } else if (event.type === 'routine') {
            const routine = event.data;
            const routineHour = parseInt(routine.hour || String(routine.time || '').split(':')[0], 10) || 8;
            const icon = routineHour < 12 ? '☀️' : (routineHour < 18 ? '🌤' : '🌙');
            const routineTime = escapeCalendarText(routine.time);
            const routineTitle = escapeCalendarText(truncateCalendarText(routine.title, 42));
            const routineId = parseInt(routine.id, 10);
            const routineStatus = getRoutineStatusForDate(routineId, dateStr);
            const routineSteps = Array.isArray(routine.steps)
                ? routine.steps.length
                : (String(routine.steps || '').trim() ? String(routine.steps).split(/\r\n|\r|\n|,/).filter(Boolean).length : 0);
            html = `
                <div class="calendar-day-entry-block-hjn card-style-2 is-routine-hjn ${routineStatus === 'completed' ? 'is-complete' : ''}" style="top:${topPosition}px;" onclick="openViewOffcanvas('routine', ${Number.isFinite(routineId) ? routineId : 0})">
                    <div class="calendar-day-entry-icon-hjn">${icon}</div>
                    <div class="calendar-day-entry-content-hjn">
                        <div class="calendar-day-entry-meta-row-hjn">
                            <div class="calendar-day-entry-time-block-hjn">${routineTime}</div>
                            <div class="calendar-routine-inline-status-hjn is-${escapeCalendarText(routineStatus || 'due')}">${escapeCalendarText(formatRoutineStatusLabel(routineStatus || 'due'))}</div>
                        </div>
                        <div class="calendar-day-entry-title-block-hjn">Routine: ${routineTitle}</div>
                        ${routineSteps ? `<div class="calendar-day-entry-mood-block-hjn">${routineSteps} steps</div>` : ''}
                        ${buildRoutineActionButtons(Number.isFinite(routineId) ? routineId : 0, dateStr, routineStatus)}
                    </div>
                </div>`;
        }

        singleDayGrid.insertAdjacentHTML('beforeend', html);
    });

    if (dayEvents.length === 0) {
        const emptyLabel = activeFilter === 'goals'
            ? 'No timed events in Goals view. You can still review daily goals above.'
            : 'No items for this day yet. Add an entry or routine to start tracking.';
        singleDayGrid.insertAdjacentHTML('beforeend', `
            <div class="calendar-day-view-empty-hjn">
                <h4>Nothing Scheduled</h4>
                <p>${emptyLabel}</p>
            </div>
        `);
    }

    const finalHeight = Math.max(totalHeight, maxEventBottom + 36, 480);
    singleDayGrid.style.height = `${finalHeight}px`;

    const today = new Date();
    const isToday = today.toDateString() === date.toDateString();
    if (isToday) {
        const currentHour = today.getHours();
        const currentMinute = today.getMinutes();
        const hourTop = positionMap[currentHour] || 0;
        const minuteOffset = (currentMinute / 60) * (hourHeights[currentHour] || expandedHeight);
        const currentPosition = Math.min(finalHeight - 16, hourTop + minuteOffset);
        singleDayGrid.insertAdjacentHTML('beforeend', `
            <div class="calendar-current-time-indicator-hjn" style="top:${currentPosition}px;">
                <div class="calendar-time-indicator-dot-hjn"></div>
                <div class="calendar-time-indicator-line-hjn"></div>
            </div>
        `);
    }
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
        const title =
            entry.querySelector('.calendar-day-entry-title-block-hjn')?.textContent.toLowerCase() ||
            entry.querySelector('.calendar-day-entry-title-hjn')?.textContent.toLowerCase() ||
            '';
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
