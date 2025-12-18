/**
 * Hair Journey Diary JavaScript
 */

// Global variables
let currentDate = new Date();
let selectedDate = null;
let entries = [];
let uploadedImage = null;
let currentEditingEntry = null;

// Initialize the app
jQuery(document).ready(function($) {
    initializeHairDiary();
});

function initializeHairDiary() {
    loadEntries();
    renderCalendar();
    setupEventListeners();
}

function setupEventListeners() {
    const $ = jQuery;
    
    // Calendar navigation
    $('#prevMonth').on('click', function() {
        currentDate.setMonth(currentDate.getMonth() - 1);
        renderCalendar();
    });

    $('#nextMonth').on('click', function() {
        currentDate.setMonth(currentDate.getMonth() + 1);
        renderCalendar();
    });

    // Image upload
    const imageUpload = $('#imageUpload');
    const imageInput = $('#imageInput');

    imageUpload.on('click', function() {
        imageInput.click();
    });

    imageUpload.on('dragover', handleDragOver);
    imageUpload.on('drop', handleDrop);
    imageInput.on('change', handleImageSelect);

    // Rating slider
    $('#ratingSlider').on('input', function() {
        const value = $(this).val();
        $('#ratingValue').text(value);
        $('#ratingStars').html('⭐'.repeat(value));
    });

    // Form submission
    $('#entryForm').on('submit', saveEntry);

    // Modal close events
    $(window).on('click', function(e) {
        if (e.target.id === 'entryModal') {
            closeModal();
        }
        if (e.target.id === 'viewModal') {
            closeViewModal();
        }
    });

    // Keyboard shortcuts
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModal();
            closeViewModal();
        }
        if (e.key === 'n' && e.ctrlKey) {
            e.preventDefault();
            openModal();
        }
    });
}

function loadEntries() {
    const $ = jQuery;
    
    $.ajax({
        url: hair_diary_ajax.ajax_url,
        type: 'POST',
        data: {
            action: 'myavana_get_entries',
            nonce: hair_diary_ajax.nonce
        },
        success: function(response) {
            if (response.success) {
                entries = response.data;
                renderCalendar();
                updateStats();
                updateRecentEntries();
            } else {
                showNotification('Error loading entries: ' + response.data, 'error');
            }
        },
        error: function() {
            showNotification('Error loading entries. Please refresh the page.', 'error');
        }
    });
}

function renderCalendar() {
    const $ = jQuery;
    const calendar = $('#calendar');
    const monthYear = $('#monthYear');
    
    const year = currentDate.getFullYear();
    const month = currentDate.getMonth();
    
    monthYear.text(new Date(year, month).toLocaleDateString('en-US', { 
        month: 'long', 
        year: 'numeric' 
    }));

    calendar.empty();

    // Add day headers
    const dayHeaders = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    dayHeaders.forEach(day => {
        calendar.append(`<div class="hair-diary-day-header">${day}</div>`);
    });

    // Get first day of month and number of days
    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const daysInPrevMonth = new Date(year, month, 0).getDate();

    // Add previous month's trailing days
    for (let i = firstDay - 1; i >= 0; i--) {
        calendar.append(createDayElement(daysInPrevMonth - i, true));
    }

    // Add current month's days
    for (let day = 1; day <= daysInMonth; day++) {
        calendar.append(createDayElement(day, false));
    }

    // Add next month's leading days
    const totalCells = calendar.children().length - 7; // Subtract headers
    const remainingCells = 42 - totalCells; // 6 rows × 7 days
    for (let day = 1; day <= remainingCells; day++) {
        calendar.append(createDayElement(day, true));
    }
}

function createDayElement(day, isOtherMonth) {
    const $ = jQuery;
    const dayElement = $('<div class="hair-diary-day"></div>');
    if (isOtherMonth) dayElement.addClass('hair-diary-other-month');

    const dayNumber = $(`<div class="hair-diary-day-number">${day}</div>`);
    const entryPills = $('<div class="hair-diary-entry-pills"></div>');
    
    dayElement.append(dayNumber).append(entryPills);

    if (!isOtherMonth) {
        const dateString = `${currentDate.getFullYear()}-${String(currentDate.getMonth() + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        const dayEntries = entries.filter(entry => entry.date === dateString);

        if (dayEntries.length > 0) {
            dayElement.addClass('hair-diary-has-entries');
            dayEntries.forEach(entry => {
                const entryType = getEntryTypeFromData(entry);
                const pill = $(`<div class="hair-diary-entry-pill ${entryType}" title="${entry.title}"></div>`);
                pill.on('click', function(e) {
                    e.stopPropagation();
                    viewEntry(entry.id);
                });
                entryPills.append(pill);
            });
        }

        dayElement.on('click', function() {
            selectedDate = dateString;
            $('.hair-diary-day').removeClass('hair-diary-selected');
            dayElement.addClass('hair-diary-selected');
            openModal(dateString);
        });
    }

    return dayElement;
}

function getEntryTypeFromData(entry) {
    // Determine entry type based on content or tags
    const title = entry.title.toLowerCase();
    const description = entry.description.toLowerCase();
    const tags = entry.ai_tags || [];
    
    if (title.includes('wash') || description.includes('wash') || tags.includes('wash')) {
        return 'wash';
    } else if (title.includes('treatment') || description.includes('treatment') || tags.includes('treatment')) {
        return 'treatment';
    } else if (title.includes('style') || description.includes('style') || tags.includes('styling')) {
        return 'style';
    } else if (title.includes('progress') || description.includes('progress') || entry.image) {
        return 'progress';
    }
    return 'general';
}

function openModal(date = null) {
    const $ = jQuery;
    const modal = $('#entryModal');
    const entryDate = $('#entryDate');
    const modalTitle = $('#modalTitle');
    const deleteBtn = $('#deleteBtn');
    
    if (date) {
        entryDate.val(date);
    } else {
        entryDate.val(new Date().toISOString().split('T')[0]);
    }
    
    modalTitle.text('Add Hair Journey Entry');
    deleteBtn.hide();
    currentEditingEntry = null;
    
    modal.show();
    resetForm();
}

function closeModal() {
    jQuery('#entryModal').hide();
}

function resetForm() {
    const $ = jQuery;
    
    $('#entryForm')[0].reset();
    $('#entryId').val('');
    $('#ratingValue').text('3');
    $('#ratingStars').html('⭐⭐⭐');
    
    const imageUpload = $('#imageUpload');
    imageUpload.html('<div>📸 Drag & drop or click to upload photo</div>');
    
    uploadedImage = null;
}

function handleDragOver(e) {
    e.preventDefault();
    jQuery(e.currentTarget).addClass('hair-diary-dragover');
}

function handleDrop(e) {
    e.preventDefault();
    jQuery(e.currentTarget).removeClass('hair-diary-dragover');
    const files = e.originalEvent.dataTransfer.files;
    if (files.length > 0) {
        handleImageFile(files[0]);
    }
}

function handleImageSelect(e) {
    const file = e.target.files[0];
    if (file) {
        handleImageFile(file);
    }
}

function handleImageFile(file) {
    const $ = jQuery;
    
    if (file && file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = function(e) {
            uploadedImage = e.target.result;
            const imageUpload = $('#imageUpload');
            imageUpload.html(`
                <img src="${uploadedImage}" class="hair-diary-uploaded-image" alt="Uploaded photo">
                <div style="margin-top: 10px;">Click to change photo</div>
            `);
        };
        reader.readAsDataURL(file);
    }
}

// function saveEntry(e) {
//     e.preventDefault();
//     const $ = jQuery;
    
//     const formData = new FormData();
//     const form = $('#entryForm')[0];
    
//     // Add all form data
//     const formDataObj = new FormData(form);
//     for (let [key, value] of formDataObj.entries()) {
//         formData.append(key, value);
//     }
    
//     // Add AJAX specific data
//     if (currentEditingEntry) {
//         formData.append('action', 'myavana_edit_entry');
//         formData.append('entry_id', currentEditingEntry);
//     } else {
//         formData.append('action', 'myavana_add_entry');
//     }
//     formData.append('n