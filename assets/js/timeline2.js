if (typeof hair_diary_ajax === 'undefined') {
    console.error('hair_diary_ajax is not defined. Ensure wp_localize_script is working.');
    var hair_diary_ajax = {
        ajax_url: '/wp-admin/admin-ajax.php',
        nonce: '',
        user_id: '0'
    };
}

// Global variables
let currentDate = new Date();
let entries = [];
let currentEntryId = null;

// Initialize the app
document.addEventListener('DOMContentLoaded', function() {
    console.log('Initializing hair journey diary...');
    setupEventListeners();
    fetchEntries();
});

function setupEventListeners() {
    console.log('Setting up event listeners...');
    
    const prevMonthBtn = document.getElementById('prevMonth');
    const nextMonthBtn = document.getElementById('nextMonth');
    if (prevMonthBtn) {
        prevMonthBtn.addEventListener('click', () => {
            console.log('Previous month clicked');
            currentDate.setMonth(currentDate.getMonth() - 1);
            renderCalendar();
            updateStats();
        });
    } else {
        console.error('Previous month button not found');
    }
    if (nextMonthBtn) {
        nextMonthBtn.addEventListener('click', () => {
            console.log('Next month clicked');
            currentDate.setMonth(currentDate.getMonth() + 1);
            renderCalendar();
            updateStats();
        });
    } else {
        console.error('Next month button not found');
    }

    const quickAddBtn = document.getElementById('quickAddBtn');
    if (quickAddBtn) {
        quickAddBtn.addEventListener('click', () => {
            console.log('Quick add button clicked');
            openModal();
        });
    } else {
        console.error('Quick add button not found');
    }

    const form = document.getElementById('myavana-entry-form');
    if (form) {
        form.addEventListener('submit', saveEntry);
    } else {
        console.error('Form not found');
    }

    const fileInput = document.getElementById('photo');
    const filepondContainer = document.getElementById('filepond-container');
    if (fileInput && filepondContainer) {
        fileInput.addEventListener('change', function(e) {
            if (e.target.files.length > 0) {
                const file = e.target.files[0];
                const reader = new FileReader();
                reader.onload = function(e) {
                    filepondContainer.innerHTML = `
                        <img src="${e.target.result}" class="uploaded-image" alt="Uploaded photo">
                        <div style="margin-top: 10px;">Click to change photo</div>
                    `;
                };
                reader.readAsDataURL(file);
            }
        });

        filepondContainer.addEventListener('click', function() {
            fileInput.click();
        });
    } else {
        console.error('File input or filepond container not found');
    }

    const modal = document.getElementById('entryModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeModal();
            }
        });
    } else {
        console.error('Modal not found');
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModal();
        }
        if (e.key === 'n' && e.ctrlKey) {
            e.preventDefault();
            openModal();
        }
    });
}

async function fetchEntries() {
    console.log('Fetching entries for user:', hair_diary_ajax.user_id);
    jQuery.ajax({
        url: hair_diary_ajax.ajax_url,
        type: 'POST',
        data: {
            action: 'myavana_get_diary_entries',
            security: hair_diary_ajax.nonce,
            user_id: hair_diary_ajax.user_id
        },
        success: function(response) {
            console.log('Fetch entries response:', response);
            if (response.success) {
                entries = response.data.entries || [];
                entries.forEach(entry => {
                    entry.ai_tags = entry.ai_tags ? (Array.isArray(entry.ai_tags) ? entry.ai_tags : JSON.parse(entry.ai_tags)) : [];
                    entry.date = entry.date ? new Date(entry.date).toISOString().split('T')[0] : new Date().toISOString().split('T')[0];
                });
                console.log('Processed entries:', entries);
                renderCalendar();
                updateStats();
                updateRecentEntries();
            } else {
                console.error('Error fetching entries:', response.data || 'Unknown error');
                showNotification('Error loading entries: ' + (response.data || 'Unknown error'), true);
            }
        },
        error: function(xhr, status, error) {
            console.error('Fetch entries error:', status, error, xhr.responseText);
            showNotification('Error: ' + (xhr.responseText || error), true);
        }
    });
}

function renderCalendar() {
    const calendar = document.getElementById('calendar');
    const monthYear = document.getElementById('monthYear');
    
    if (!calendar || !monthYear) {
        console.error('Calendar or monthYear element not found');
        return;
    }
    
    const year = currentDate.getFullYear();
    const month = currentDate.getMonth();
    
    monthYear.textContent = new Date(year, month).toLocaleDateString('en-US', { 
        month: 'long', 
        year: 'numeric' 
    });

    calendar.innerHTML = '';

    const dayHeaders = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    dayHeaders.forEach(day => {
        const dayHeader = document.createElement('div');
        dayHeader.className = 'day-header';
        dayHeader.textContent = day;
        calendar.appendChild(dayHeader);
    });

    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const daysInPrevMonth = new Date(year, month, 0).getDate();

    for (let i = firstDay - 1; i >= 0; i--) {
        const dayElement = createDayElement(daysInPrevMonth - i, true);
        calendar.appendChild(dayElement);
    }

    for (let day = 1; day <= daysInMonth; day++) {
        const dayElement = createDayElement(day, false);
        calendar.appendChild(dayElement);
    }

    const totalCells = calendar.children.length - 7;
    const remainingCells = 42 - totalCells;
    for (let day = 1; day <= remainingCells; day++) {
        const dayElement = createDayElement(day, true);
        calendar.appendChild(dayElement);
    }
}

function createDayElement(day, isOtherMonth) {
    const dayElement = document.createElement('div');
    dayElement.className = 'day';
    if (isOtherMonth) dayElement.classList.add('other-month');

    const dayNumber = document.createElement('div');
    dayNumber.className = 'day-number';
    dayNumber.textContent = day;
    dayElement.appendChild(dayNumber);

    const entryPills = document.createElement('div');
    entryPills.className = 'entry-pills';
    dayElement.appendChild(entryPills);

    if (!isOtherMonth) {
        const dateString = `${currentDate.getFullYear()}-${String(currentDate.getMonth() + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        const dayEntries = entries.filter(entry => entry.date === dateString);

        if (dayEntries.length > 0) {
            dayElement.classList.add('has-entries');
            dayEntries.forEach(entry => {
                const pill = document.createElement('div');
                const type = entry.ai_tags && entry.ai_tags.length > 0 ? entry.ai_tags[0].toLowerCase() : (entry.environment || 'general').toLowerCase();
                pill.className = `entry-pill ${type}`;
                pill.title = entry.title;
                pill.addEventListener('click', (e) => {
                    e.stopPropagation();
                    viewEntry(entry.id);
                });
                entryPills.appendChild(pill);
            });
        }

        dayElement.addEventListener('click', () => {
            document.querySelectorAll('.day').forEach(d => d.classList.remove('selected'));
            dayElement.classList.add('selected');
            openModal();
        });
    }

    return dayElement;
}

function openModal() {
    console.log('Opening modal');
    const modal = document.getElementById('entryModal');
    const form = document.getElementById('myavana-entry-form');
    currentEntryId = null;
    
    if (modal && form) {
        form.reset();
        document.getElementById('filepond-container').innerHTML = '';
        document.getElementById('modal-title').textContent = 'Add Hair Journey Entry';
        document.getElementById('mood1').checked = true;
        document.querySelector('select[name="environment"]').value = 'home';
        modal.style.display = 'block';
    } else {
        console.error('Modal or form not found');
    }
}

function closeModal() {
    const modal = document.getElementById('entryModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

function saveEntry(e) {
    e.preventDefault();
    console.log('Saving entry...');
    
    const form = document.getElementById('myavana-entry-form');
    const formData = new FormData(form);
    formData.append('action', currentEntryId ? 'myavana_edit_diary_entry' : 'myavana_add_diary_entry');
    formData.append('myavana_nonce', hair_diary_ajax.nonce);
    if (currentEntryId) {
        formData.append('entry_id', currentEntryId);
    }

    jQuery.ajax({
        url: hair_diary_ajax.ajax_url,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            console.log('Save entry response:', response);
            if (response.success) {
                showNotification(response.data.message || 'Entry saved successfully');
                fetchEntries();
                closeModal();
            } else {
                showNotification(response.data || 'Error saving entry. Please try again.', true);
            }
        },
        error: function(xhr, status, error) {
            console.error('Save entry error:', status, error, xhr.responseText);
            showNotification('Error: ' + (xhr.responseText || error), true);
        }
    });
}

function updateStats() {
    const totalEntries = entries.length;
    const currentMonth = currentDate.getMonth();
    const currentYear = currentDate.getFullYear();
    
    const thisMonthEntries = entries.filter(entry => {
        const entryDate = new Date(entry.date);
        return entryDate.getMonth() === currentMonth && entryDate.getFullYear() === currentYear;
    });
    
    const washDays = entries.filter(entry => entry.ai_tags && entry.ai_tags.includes('wash')).length;
    const treatments = entries.filter(entry => entry.ai_tags && entry.ai_tags.includes('treatment')).length;
    
    const totalEntriesEl = document.getElementById('totalEntries');
    const thisMonthEl = document.getElementById('thisMonth');
    const washDaysEl = document.getElementById('washDays');
    const treatmentsEl = document.getElementById('treatments');
    
    if (totalEntriesEl) totalEntriesEl.textContent = totalEntries;
    if (thisMonthEl) thisMonthEl.textContent = thisMonthEntries.length;
    if (washDaysEl) washDaysEl.textContent = washDays;
    if (treatmentsEl) treatmentsEl.textContent = treatments;
}

function updateRecentEntries() {
    const timelineContainer = document.getElementById('timelineEntries');
    if (!timelineContainer) {
        console.error('Timeline entries container not found');
        return;
    }
    
    const recentEntries = entries
        .sort((a, b) => new Date(b.date) - new Date(a.date))
        .slice(0, 5);

    if (recentEntries.length === 0) {
        timelineContainer.innerHTML = '<p style="text-align: center; color: #666; padding: 20px;">No entries yet. Start your hair journey!</p>';
        return;
    }

    let timelineHTML = '';
    recentEntries.forEach((entry, index) => {
        const typeClass = `timeline__event--type${(index % 3) + 1}`;
        const isLast = index === recentEntries.length - 1;
        timelineHTML += `
            <div class="timeline__event animated fadeInUp delay-${3 - (index % 3)}s ${typeClass}" onclick="viewEntry(${entry.id})">
                <div class="timeline__event__icon"></div>
                <div class="timeline__event__date" style="${entry.image ? `background-image: url('${entry.image}'); background-size: cover; background-position: center;` : 'background: #ddd;'}">
                    ${!entry.image ? formatDate(entry.date) : ''}
                </div>
                <div class="timeline__event__content">
                    <div class="timeline__event__title">${entry.title || 'Untitled'}</div>
                    <div class="timeline__event__description">
                        <p>${entry.description || 'No description'}</p>
                        <p><strong>Environment:</strong> ${capitalizeFirst(entry.environment || 'N/A')}</p>
                        <p><strong>Mood:</strong> ${getMoodEmoji(entry.mood_demeanor)} ${capitalizeFirst(entry.mood_demeanor || 'N/A')}</p>
                    </div>
                </div>
            </div>
        `;
        if (!isLast) {
            const nextEntry = recentEntries[index + 1];
            timelineHTML += `
                <div class="timeline__date">${formatDate(entry.date)}</div>
            `;
        }
    });

    timelineContainer.innerHTML = timelineHTML;
}

function viewEntry(entryId) {
    console.log('Viewing entry:', entryId);
    jQuery.ajax({
        url: hair_diary_ajax.ajax_url + `?action=myavana_get_single_diary_entry&security=${hair_diary_ajax.nonce}&entry_id=${entryId}`,
        type: 'GET',
        success: function(response) {
            console.log('View entry response:', response);
            if (response.success) {
                const entry = response.data;
                // const offcanvas = document.getElementById('hairDiaryOffcanvas');
                // const toggleBtn = document.querySelector('.offcanvas-toggle-btn');
                
                const modal = document.createElement('div');
                modal.className = 'offcanvas offcanvas-open';
                modal.id = 'hairDiaryOffcanvas';
                // modal.style.display = 'block';
                modal.innerHTML = `
                        <div class="offcanvas-header">
                        <h2>✨ ${entry.title || 'Entry'}</h2>
                        <button class="offcanvas-close-btn" onclick="toggleOffcanvas()">×</button>
                    </div>
                    <div class="offcanvas-body">
                        
                        <div style="max-height: 80vh; overflow-y: auto;">
                            ${entry.image ? `<img src="${entry.image}" style="width: 100%; max-height: 300px; object-fit: cover; border-radius: 10px; margin-bottom: 20px;">` : ''}
                            
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                                <div>
                                    <strong>Date:</strong> ${formatDate(entry.date)}<br>
                                    <strong>Environment:</strong> ${capitalizeFirst(entry.environment || 'N/A')}<br>
                                    <strong>Mood:</strong> ${getMoodEmoji(entry.mood_demeanor)} ${capitalizeFirst(entry.mood_demeanor || 'N/A')}<br>
                                    <strong>Rating:</strong> ${entry.rating || 'N/A'}/5<br>
                                </div>
                            </div>
                            
                            ${entry.description ? `
                                <div style="margin-bottom: 20px;">
                                    <strong>Description:</strong><br>
                                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-top: 8px;">
                                        ${entry.description}
                                    </div>
                                </div>
                            ` : ''}
                            
                            ${entry.products ? `
                                <div style="margin-bottom: 20px;">
                                    <strong>Products Used:</strong><br>
                                    <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-top: 8px;">
                                        <span class="tag">${entry.products}</span>
                                    </div>
                                </div>
                            ` : ''}
                            
                            ${entry.notes ? `
                                <div style="margin-bottom: 20px;">
                                    <strong>Stylist Notes:</strong><br>
                                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-top: 8px;">
                                        ${entry.notes}
                                    </div>
                                </div>
                            ` : ''}
                            
                            <div style="text-align: center; margin-top: 30px;">
                                <button class="save-btn"  onclick="editEntry(${entry.id})">
                                    Edit Entry
                                </button>
                                <button class="cancel-btn"  onclick="deleteEntry(${entry.id})">
                                    Delete Entry
                                </button>
                            </div>
                        </div>
                    </div>
                `;
                document.body.appendChild(modal);
               
            } else {
                showNotification('Error loading entry: ' + (response.data || 'Unknown error'), true);
            }
        },
        error: function(xhr, status, error) {
            console.error('View entry error:', status, error, xhr.responseText);
            showNotification('Error: ' + (xhr.responseText || error), true);
        }
    });
}

function editEntry(entryId) {
    console.log('Editing entry:', entryId);
    jQuery.ajax({
        url: hair_diary_ajax.ajax_url + `?action=myavana_get_single_diary_entry&security=${hair_diary_ajax.nonce}&entry_id=${entryId}`,
        type: 'GET',
        success: function(response) {
            console.log('Edit entry response:', response);
            if (response.success) {
                const entry = response.data;
                currentEntryId = entry.id;
                toggleOffcanvas()
                const modal = document.getElementById('entryModal');
                document.getElementById('modal-title').textContent = 'Edit Hair Journey Entry';
                
                document.getElementById('title').value = entry.title || '';
                document.getElementById('description').value = entry.description || '';
                document.getElementById('products').value = entry.products || '';
                document.getElementById('notes').value = entry.notes || '';
                document.getElementById('rating').value = entry.rating || 3;
                document.getElementById('entryId').value = entry.id || '';
                if (entry.mood_demeanor) {
                    const moodInput = document.querySelector(`input[name="mood_demeanor"][value="${entry.mood_demeanor}"]`);
                    if (moodInput) moodInput.checked = true;
                }
                if (entry.environment) {
                    const envOption = document.querySelector(`select[name="environment"] option[value="${entry.environment}"]`);
                    if (envOption) envOption.selected = true;
                }
                
                const filepondContainer = document.getElementById('filepond-container');
                if (entry.image) {
                    filepondContainer.innerHTML = `
                        <img src="${entry.image}" class="uploaded-image" alt="Uploaded photo">
                        <div style="margin-top: 10px;">Click to change photo</div>
                    `;
                } else {
                    filepondContainer.innerHTML = '';
                }
                
                modal.classList.toggle('offcanvas-open');
                document.querySelectorAll('.modal').forEach(m => {
                    if (m !== modal) m.remove();
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('Edit entry error:', status, error, xhr.responseText);
            showNotification('Error: ' + (xhr.responseText || error), true);
        }
    });
}

function deleteEntry(entryId) {
    console.log('Deleting entry:', entryId);
    if (confirm('Are you sure you want to delete this entry?')) {
        jQuery.ajax({
            url: hair_diary_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'myavana_delete_diary_entry',
                security: hair_diary_ajax.nonce,
                entry_id: entryId
            },
            success: function(response) {
                console.log('Delete entry response:', response);
                if (response.success) {
                    showNotification(response.data.message || 'Entry deleted successfully');
                    fetchEntries();
                    document.querySelectorAll('.modal').forEach(m => m.remove());
                } else {
                    showNotification('Error deleting entry: ' + (response.data || 'Unknown error'), true);
                }
            },
            error: function(xhr, status, error) {
                console.error('Delete entry error:', status, error, xhr.responseText);
                showNotification('Error: ' + (xhr.responseText || error), true);
            }
        });
    }
}

function formatDate(dateString) {
    try {
        return new Date(dateString).toLocaleDateString('en-US', {
            weekday: 'short',
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    } catch (e) {
        console.error('Error formatting date:', dateString, e);
        return dateString;
    }
}

function capitalizeFirst(str) {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1);
}

function getMoodEmoji(mood) {
    const moods = {
        'Excited': '🤩',
        'Happy': '😊',
        'Optimistic': '😊',
        'Nervous': '😐',
        'Determined': '😤'
    };
    return moods[mood] || '😊';
}

function showNotification(message, isError = false) {
    console.log('Showing notification:', message, isError ? 'Error' : 'Success');
    const notification = document.createElement('div');
    notification.className = `notification ${isError ? 'notification-error' : ''}`;
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.classList.add('notification-show');
    }, 100);
    
    setTimeout(() => {
        notification.classList.remove('notification-show');
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}