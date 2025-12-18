/**
 * MYAVANA Timeline - Form Implementations
 * Clean form handlers for entries, goals, and routines
 * @version 2.3.5
 */

(function() {
    'use strict';

    window.MyavanaTimeline = window.MyavanaTimeline || {};

    /**
     * Entry Form Handler
     */
    MyavanaTimeline.EntryForm = {

        /**
         * Open create entry form (now uses Premium 3-Step Form)
         */
        create: function(prefillData) {
            console.log('[EntryForm] Opening premium 3-step form');

            // Check if premium form is available
            if (typeof window.MyavanaPremiumEntryForm !== 'undefined') {
                window.MyavanaPremiumEntryForm.open(prefillData);
                return;
            }

            // Fallback to old form if premium form not loaded
            console.warn('[EntryForm] Premium form not available, using fallback');
            this.createFallback();
        },

        /**
         * Fallback to old FormBuilder form (if premium form not available)
         */
        createFallback: function() {
            console.log('[EntryForm] Using fallback FormBuilder');

            const fields = [
                {
                    type: 'hidden',
                    name: 'action',
                    value: 'myavana_add_entry'
                },
                {
                    type: 'text',
                    name: 'title',
                    label: 'Entry Title',
                    placeholder: 'e.g., Week 4 Progress Update',
                    required: true
                },
                {
                    type: 'date',
                    name: 'entry_date',
                    label: 'Date',
                    value: new Date().toISOString().split('T')[0],
                    required: true
                },
                {
                    type: 'textarea',
                    name: 'description',
                    label: 'Description',
                    placeholder: 'Describe your progress, observations, or thoughts...',
                    required: true,
                    rows: 5
                },
                {
                    type: 'select',
                    name: 'rating',
                    label: 'Hair Health Rating',
                    required: true,
                    value: '5',
                    options: [
                        { value: '5', label: '⭐⭐⭐⭐⭐ Excellent' },
                        { value: '4', label: '⭐⭐⭐⭐ Good' },
                        { value: '3', label: '⭐⭐⭐ Average' },
                        { value: '2', label: '⭐⭐ Fair' },
                        { value: '1', label: '⭐ Needs Work' }
                    ]
                },
                {
                    type: 'select',
                    name: 'mood_demeanor',
                    label: 'Mood',
                    options: ['Happy', 'Confident', 'Neutral', 'Frustrated', 'Excited', 'Concerned']
                },
                {
                    type: 'text',
                    name: 'products',
                    label: 'Products Used',
                    placeholder: 'Enter products separated by commas'
                },
                {
                    type: 'textarea',
                    name: 'notes',
                    label: 'Additional Notes',
                    placeholder: 'Any other observations or notes...',
                    rows: 3
                },
                {
                    type: 'file',
                    name: 'photo',
                    label: 'Upload Photo',
                    accept: 'image/*'
                }
            ];

            MyavanaTimeline.FormBuilder.createFormModal({
                title: 'New Hair Journey Entry',
                fields: fields,
                submitText: 'Create Entry',
                width: '700px',
                onSubmit: async (data) => {
                    return await this.save(data);
                },
                onClose: () => {
                    console.log('[EntryForm] Form closed');
                }
            });
        },

        /**
         * Open edit entry form
         */
        edit: function(entryId) {
            console.log('[EntryForm] Loading entry for edit:', entryId);

            // Show loading
            const loadingModal = this.showLoading('Loading entry...');

            // Fetch entry data
            this.fetchEntry(entryId)
                .then(entry => {
                    loadingModal.close();
                    this.showEditForm(entry);
                })
                .catch(error => {
                    loadingModal.close();
                    alert('Error loading entry: ' + error.message);
                });
        },

        /**
         * Show edit form with data
         */  
        showEditForm: function(entry) {
            console.log('[EntryForm] Opening edit form with data:', entry);

            const fields = [
                {
                    type: 'hidden',
                    name: 'action',
                    value: 'myavana_update_entry'
                },
                {
                    type: 'hidden',
                    name: 'entry_id',
                    value: entry.id
                },
                {
                    type: 'text',
                    name: 'title',
                    label: 'Entry Title',
                    value: entry.title || '',
                    required: true
                },
                {
                    type: 'date',
                    name: 'entry_date',
                    label: 'Date',
                    value: entry.entry_date || entry.date,
                    required: true
                },
                {
                    type: 'textarea',
                    name: 'description',
                    label: 'Description',
                    value: entry.description || '',
                    required: true,
                    rows: 5
                },
                {
                    type: 'select',
                    name: 'rating',
                    label: 'Hair Health Rating',
                    value: entry.rating || '5',
                    required: true,
                    options: [
                        { value: '5', label: '⭐⭐⭐⭐⭐ Excellent' },
                        { value: '4', label: '⭐⭐⭐⭐ Good' },
                        { value: '3', label: '⭐⭐⭐ Average' },
                        { value: '2', label: '⭐⭐ Fair' },
                        { value: '1', label: '⭐ Needs Work' }
                    ]
                },
                {
                    type: 'select',
                    name: 'mood_demeanor',
                    label: 'Mood',
                    value: entry.mood || '',
                    options: ['Happy', 'Confident', 'Neutral', 'Frustrated', 'Excited', 'Concerned']
                },
                {
                    type: 'text',
                    name: 'products',
                    label: 'Products Used',
                    value: entry.products || '',
                    placeholder: 'Enter products separated by commas'
                },
                {
                    type: 'textarea',
                    name: 'notes',
                    label: 'Additional Notes',
                    value: entry.notes || '',
                    rows: 3
                },
                {
                    type: 'file',
                    name: 'photo',
                    label: 'Upload New Photo',
                    accept: 'image/*'
                }
            ];

            // Show current image if exists
            if (entry.image) {
                const imageNote = {
                    type: 'text',
                    name: '_current_image_note',
                    label: 'Current Image',
                    value: 'Image attached (upload new to replace)',
                    disabled: true
                };
                fields.splice(fields.length - 1, 0, imageNote);
            }

            console.log('[EntryForm] Calling FormBuilder.createFormModal...');
            const modalObj = MyavanaTimeline.FormBuilder.createFormModal({
                title: 'Edit Hair Journey Entry',
                fields: fields,
                submitText: 'Update Entry',
                width: '700px',
                onSubmit: async (data) => {
                    console.log('[EntryForm] onSubmit callback triggered with data:', data);
                    return await this.save(data);
                }
            });
            console.log('[EntryForm] Form modal created:', modalObj);
        },

        /**
         * Fetch entry from server (with cache support)
         */
        fetchEntry: async function(entryId) {
            // FIRST: Check pre-loaded cache (avoids AJAX 400 errors)
            if (window.myavanaEntryCache && window.myavanaEntryCache[entryId]) {
                console.log('[EntryForm] Found entry in cache:', entryId);
                const cached = window.myavanaEntryCache[entryId];
                // Map cached fields to expected format
                return {
                    id: cached.id || cached.entry_id,
                    entry_id: cached.entry_id || cached.id,
                    title: cached.title || '',
                    entry_date: cached.entry_date || '',
                    date: cached.entry_date || '',
                    description: cached.content || cached.description || '',
                    content: cached.content || cached.description || '',
                    rating: cached.rating || 5,
                    mood_demeanor: cached.mood || cached.mood_demeanor || '',
                    mood: cached.mood || cached.mood_demeanor || '',
                    products_used: cached.products || cached.products_used || '',
                    products: cached.products || cached.products_used || '',
                    image: cached.image || ''
                };
            }

            console.log('[EntryForm] Entry not in cache, attempting AJAX fallback');

            // FALLBACK: Fetch from server
            const settings = window.myavanaTimelineSettings || {};
            const response = await fetch(settings.ajaxUrl || '/wp-admin/admin-ajax.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'myavana_get_entry_details',
                    security: settings.getEntryDetailsNonce || settings.nonce,
                    entry_id: entryId
                })
            });

            const data = await response.json();
            if (!data.success) {
                throw new Error(data.data || 'Failed to load entry');
            }

            return data.data;
        },

        /**
         * Validate entry data before save
         */
        validate: function(formData) {
            const errors = [];

            // Required fields
            if (!formData.title || formData.title.trim() === '') {
                errors.push('Title is required');
            }

            if (!formData.description || formData.description.trim() === '') {
                errors.push('Description is required');
            }

            if (!formData.entry_date) {
                errors.push('Date is required');
            }

            // Title length
            if (formData.title && formData.title.length > 200) {
                errors.push('Title must be less than 200 characters');
            }

            // Rating validation
            if (formData.rating) {
                const rating = parseInt(formData.rating);
                if (isNaN(rating) || rating < 1 || rating > 5) {
                    errors.push('Rating must be between 1 and 5');
                }
            }

            return errors;
        },

        /**
         * Save entry (create or update)
         */
        save: async function(formData) {
            console.log('[EntryForm] === SAVE STARTED ===');
            console.log('[EntryForm] Form data received:', formData);
            console.log('[EntryForm] Is update?', !!formData.entry_id);

            // Validate form data
            const validationErrors = this.validate(formData);
            if (validationErrors.length > 0) {
                console.error('[EntryForm] Validation failed:', validationErrors);
                const errorMessage = 'Please fix the following errors:\n' + validationErrors.join('\n');
                this.showNotification(validationErrors[0], 'error');
                throw new Error(errorMessage);
            }

            console.log('[EntryForm] Validation passed');

            const settings = window.myavanaTimelineSettings || {};
            console.log('[EntryForm] Settings:', {
                ajaxUrl: settings.ajaxUrl,
                hasNonce: !!settings.nonce,
                hasAddEntryNonce: !!settings.addEntryNonce
            });

            const fd = new FormData();

            // Add all form data
            Object.keys(formData).forEach(key => {
                if (formData[key] !== null && formData[key] !== undefined && formData[key] !== '') {
                    // Handle file uploads
                    if (formData[key] instanceof File) {
                        fd.append(key, formData[key]);
                        console.log(`[EntryForm] Added file: ${key}`, formData[key].name);
                    } else {
                        fd.append(key, formData[key]);
                        console.log(`[EntryForm] Added field: ${key} = ${formData[key]}`);
                    }
                }
            });

            // Add nonce - use appropriate nonce based on whether it's update or create
            let nonce;
            if (formData.entry_id) {
                // This is an UPDATE
                nonce = settings.updateEntryNonce || settings.addEntryNonce || settings.nonce;
                console.log('[EntryForm] Using UPDATE nonce');
            } else {
                // This is a CREATE
                nonce = settings.addEntryNonce || settings.nonce;
                console.log('[EntryForm] Using ADD nonce');
            }

            fd.append('myavana_nonce', nonce);
            fd.append('security', nonce);
            console.log('[EntryForm] Nonce value:', nonce ? 'Present' : 'MISSING!');
            console.log('[EntryForm] Nonce source:', formData.entry_id ? 'updateEntryNonce' : 'addEntryNonce');

            console.log('[EntryForm] Sending request to:', settings.ajaxUrl || '/wp-admin/admin-ajax.php');
            console.log('[EntryForm] Action:', formData.action);

            try {
                const response = await fetch(settings.ajaxUrl || '/wp-admin/admin-ajax.php', {
                    method: 'POST',
                    body: fd,
                    credentials: 'same-origin'
                });

                console.log('[EntryForm] Response status:', response.status, response.statusText);

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();
                console.log('[EntryForm] Server response:', data);

                if (!data.success) {
                    console.error('[EntryForm] Server returned error:', data.data);
                    throw new Error(data.data || 'Failed to save entry');
                }

                // Show success message
                console.log('[EntryForm] Save successful!');
                this.showNotification('Entry saved successfully!', 'success');

                // Refresh timeline
                setTimeout(() => {
                    console.log('[EntryForm] Refreshing timeline...');
                    if (MyavanaTimeline.Navigation && MyavanaTimeline.Navigation.refreshTimeline) {
                        MyavanaTimeline.Navigation.refreshTimeline();
                    } else {
                        location.reload();
                    }
                }, 500);

                return data;
            } catch (error) {
                console.error('[EntryForm] Save error:', error);
                throw error;
            }
        },

        /**
         * Delete entry
         */
        delete: async function(entryId) {
            console.log('[EntryForm] Deleting entry:', entryId);

            const settings = window.myavanaTimelineSettings || {};
            const formData = new FormData();
            formData.append('action', 'myavana_delete_entry');
            formData.append('entry_id', entryId);
            formData.append('security', settings.deleteEntryNonce || settings.nonce);

            try {
                const response = await fetch(settings.ajaxUrl || '/wp-admin/admin-ajax.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                console.log('[EntryForm] Delete response:', data);

                if (!data.success) {
                    throw new Error(data.data || 'Failed to delete entry');
                }

                this.showNotification('Entry deleted successfully!', 'success');

                // Close offcanvas and refresh
                setTimeout(() => {
                    if (window.closeTimelineViewOffcanvas) {
                        window.closeTimelineViewOffcanvas();
                    }
                    location.reload();
                }, 500);

                return data;
            } catch (error) {
                console.error('[EntryForm] Delete error:', error);
                this.showNotification('Failed to delete entry', 'error');
                throw error;
            }
        },

        /**
         * Show loading modal
         */
        showLoading: function(message) {
            const overlay = document.createElement('div');
            overlay.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(34, 35, 35, 0.8);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 99999;
            `;

            const box = document.createElement('div');
            box.style.cssText = `
                background: white;
                padding: 40px;
                border-radius: 12px;
                text-align: center;
            `;

            const spinner = document.createElement('div');
            spinner.style.cssText = `
                width: 40px;
                height: 40px;
                border: 4px solid #f5f5f7;
                border-top-color: #e7a690;
                border-radius: 50%;
                animation: spin 0.8s linear infinite;
                margin: 0 auto 16px;
            `;

            const text = document.createElement('div');
            text.textContent = message;
            text.style.cssText = `
                font-family: 'Archivo', sans-serif;
                color: #222323;
            `;

            box.appendChild(spinner);
            box.appendChild(text);
            overlay.appendChild(box);
            document.body.appendChild(overlay);

            const style = document.createElement('style');
            style.textContent = '@keyframes spin { to { transform: rotate(360deg); } }';
            document.head.appendChild(style);

            return {
                close: () => document.body.removeChild(overlay)
            };
        },

        /**
         * Show notification with icon
         */
        showNotification: function(message, type = 'info') {
            // Remove any existing notifications first
            const existing = document.querySelectorAll('.myavana-notification');
            existing.forEach(n => n.remove());

            const notification = document.createElement('div');
            notification.className = 'myavana-notification';

            // Icons for different types
            const icons = {
                success: '✓',
                error: '✕',
                info: 'ℹ',
                warning: '⚠'
            };

            const colors = {
                success: '#4caf50',
                error: '#f44336',
                info: '#2196f3',
                warning: '#ff9800'
            };

            notification.innerHTML = `
                <div style="
                    display: flex;
                    align-items: center;
                    gap: 12px;
                ">
                    <span style="
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        width: 28px;
                        height: 28px;
                        background: rgba(255,255,255,0.2);
                        border-radius: 50%;
                        font-size: 16px;
                        font-weight: bold;
                    ">${icons[type] || icons.info}</span>
                    <span style="flex: 1;">${message}</span>
                </div>
            `;

            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${colors[type] || colors.info};
                color: white;
                padding: 16px 24px;
                border-radius: 8px;
                font-family: 'Archivo', sans-serif;
                font-weight: 600;
                font-size: 14px;
                z-index: 100000;
                box-shadow: 0 8px 24px rgba(0,0,0,0.3);
                animation: slideInRight 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
                max-width: 400px;
                min-width: 300px;
            `;

            const style = document.createElement('style');
            style.textContent = `
                @keyframes slideInRight {
                    from {
                        transform: translateX(120%);
                        opacity: 0;
                    }
                    to {
                        transform: translateX(0);
                        opacity: 1;
                    }
                }
                @keyframes slideOutRight {
                    from {
                        transform: translateX(0);
                        opacity: 1;
                    }
                    to {
                        transform: translateX(120%);
                        opacity: 0;
                    }
                }
            `;
            if (!document.getElementById('myavana-notification-styles')) {
                style.id = 'myavana-notification-styles';
                document.head.appendChild(style);
            }

            document.body.appendChild(notification);

            // Auto-dismiss after 4 seconds
            setTimeout(() => {
                notification.style.animation = 'slideOutRight 0.3s ease';
                setTimeout(() => {
                    if (notification.parentNode) {
                        document.body.removeChild(notification);
                    }
                }, 300);
            }, 4000);

            return notification;
        }
    };

    /**
     * Goal Form Handler
     */
    MyavanaTimeline.GoalForm = {

        create: function() {
            const fields = [
                {
                    type: 'hidden',
                    name: 'action',
                    value: 'myavana_add_goal'
                },
                {
                    type: 'text',
                    name: 'title',
                    label: 'Goal Title',
                    placeholder: 'e.g., Grow hair 6 inches',
                    required: true
                },
                {
                    type: 'textarea',
                    name: 'description',
                    label: 'Description',
                    placeholder: 'Describe your goal...',
                    required: true,
                    rows: 4
                },
                {
                    type: 'date',
                    name: 'start_date',
                    label: 'Start Date',
                    value: new Date().toISOString().split('T')[0],
                    required: true
                },
                {
                    type: 'date',
                    name: 'target_date',
                    label: 'Target Date',
                    required: true
                },
                {
                    type: 'select',
                    name: 'status',
                    label: 'Status',
                    value: 'active',
                    options: [
                        { value: 'active', label: 'Active' },
                        { value: 'paused', label: 'Paused' },
                        { value: 'completed', label: 'Completed' }
                    ]
                }
            ];

            MyavanaTimeline.FormBuilder.createFormModal({
                title: 'New Hair Goal',
                fields: fields,
                submitText: 'Create Goal',
                onSubmit: async (data) => {
                    return await this.save(data);
                }
            });
        },

        edit: function(goalId) {
            console.log('[GoalForm] Loading goal for edit:', goalId);

            // Show loading
            const loadingModal = MyavanaTimeline.EntryForm.showLoading('Loading goal...');

            // Fetch goal data
            this.fetchGoal(goalId)
                .then(goal => {
                    loadingModal.close();
                    this.showEditForm(goal);
                })
                .catch(error => {
                    loadingModal.close();
                    console.error('[GoalForm] Error loading goal:', error);

                    // Check if it's a "not found" error - likely mock data
                    if (error.message.includes('not found') || error.message.includes('access denied')) {
                        alert('This goal is template data. Please create your own goal using the "+" button to edit it.');
                    } else {
                        alert('Error loading goal: ' + error.message);
                    }
                });
        },

        /**
         * Fetch goal data from server
         */
        fetchGoal: async function(goalId) {
            console.log('[GoalForm] Fetching goal with ID:', goalId);
            const settings = window.myavanaTimelineSettings || {};
            console.log('[GoalForm] Settings:', {
                ajaxUrl: settings.ajaxUrl,
                hasGetEntryDetailsNonce: !!settings.getEntryDetailsNonce,
                hasNonce: !!settings.nonce
            });

            const formData = new FormData();
            formData.append('action', 'myavana_get_goal_details');
            formData.append('goal_id', goalId);

            const nonce = settings.getEntryDetailsNonce || settings.nonce;
            formData.append('security', nonce);
            console.log('[GoalForm] Using nonce for fetch:', nonce ? 'Present' : 'MISSING');

            try {
                const response = await fetch(settings.ajaxUrl || '/wp-admin/admin-ajax.php', {
                    method: 'POST',
                    body: formData
                });

                console.log('[GoalForm] Fetch response status:', response.status);

                const data = await response.json();
                console.log('[GoalForm] Fetch response data:', data);

                if (!data.success) {
                    throw new Error(data.data || 'Failed to load goal');
                }

                return data.data;
            } catch (error) {
                console.error('[GoalForm] Fetch error:', error);
                throw error;
            }
        },

        /**
         * Show edit form with goal data
         */
        showEditForm: function(goal) {
            console.log('[GoalForm] Opening edit form with data:', goal);

            const fields = [
                {
                    type: 'hidden',
                    name: 'action',
                    value: 'myavana_update_goal'
                },
                {
                    type: 'hidden',
                    name: 'goal_id',
                    value: goal.id
                },
                {
                    type: 'text',
                    name: 'title',
                    label: 'Goal Title',
                    value: goal.title || '',
                    required: true
                },
                {
                    type: 'textarea',
                    name: 'description',
                    label: 'Description',
                    value: goal.description || '',
                    required: true,
                    rows: 4
                },
                {
                    type: 'date',
                    name: 'start_date',
                    label: 'Start Date',
                    value: goal.start_date || '',
                    required: true
                },
                {
                    type: 'date',
                    name: 'target_date',
                    label: 'Target Date',
                    value: goal.target_date || '',
                    required: true
                },
                {
                    type: 'select',
                    name: 'status',
                    label: 'Status',
                    value: goal.status || 'active',
                    options: [
                        { value: 'active', label: 'Active' },
                        { value: 'paused', label: 'Paused' },
                        { value: 'completed', label: 'Completed' }
                    ]
                },
                {
                    type: 'number',
                    name: 'progress',
                    label: 'Progress (%)',
                    value: goal.progress || 0,
                    min: 0,
                    max: 100
                }
            ];

            MyavanaTimeline.FormBuilder.createFormModal({
                title: 'Edit Hair Goal',
                fields: fields,
                submitText: 'Update Goal',
                onSubmit: async (data) => {
                    return await this.save(data);
                }
            });
        },

        /**
         * Validate goal data before save
         */
        validate: function(formData) {
            const errors = [];

            if (!formData.title || formData.title.trim() === '') {
                errors.push('Goal title is required');
            }

            if (!formData.description || formData.description.trim() === '') {
                errors.push('Goal description is required');
            }

            if (!formData.start_date) {
                errors.push('Start date is required');
            }

            if (!formData.target_date) {
                errors.push('Target date is required');
            }

            // Check target date is after start date
            if (formData.start_date && formData.target_date) {
                const start = new Date(formData.start_date);
                const target = new Date(formData.target_date);
                if (target <= start) {
                    errors.push('Target date must be after start date');
                }
            }

            return errors;
        },

        save: async function(formData) {
            console.log('[GoalForm] === SAVE STARTED ===');
            console.log('[GoalForm] Form data received:', formData);
            console.log('[GoalForm] Is update?', !!formData.goal_id);

            // Validate form data
            const validationErrors = this.validate(formData);
            if (validationErrors.length > 0) {
                console.error('[GoalForm] Validation failed:', validationErrors);
                MyavanaTimeline.EntryForm.showNotification(validationErrors[0], 'error');
                throw new Error(validationErrors.join('\n'));
            }

            console.log('[GoalForm] Validation passed');

            const settings = window.myavanaTimelineSettings || {};
            const fd = new FormData();

            // Add all form data
            Object.keys(formData).forEach(key => {
                if (formData[key] !== null && formData[key] !== undefined && formData[key] !== '') {
                    fd.append(key, formData[key]);
                    console.log(`[GoalForm] Added field: ${key} = ${formData[key]}`);
                }
            });

            // Add appropriate nonce based on operation (update vs create)
            let nonce;
            if (formData.goal_id) {
                nonce = settings.updateGoalNonce || settings.addGoalNonce || settings.nonce;
                console.log('[GoalForm] Using UPDATE nonce');
            } else {
                nonce = settings.addGoalNonce || settings.nonce;
                console.log('[GoalForm] Using CREATE nonce');
            }
            fd.append('security', nonce);
            fd.append('myavana_nonce', nonce);
            console.log('[GoalForm] Nonce value:', nonce ? 'Present' : 'MISSING!');

            console.log('[GoalForm] Sending request to:', settings.ajaxUrl || '/wp-admin/admin-ajax.php');
            console.log('[GoalForm] Action:', formData.action);

            try {
                const response = await fetch(settings.ajaxUrl || '/wp-admin/admin-ajax.php', {
                    method: 'POST',
                    body: fd
                });

                console.log('[GoalForm] Response status:', response.status, response.statusText);

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();
                console.log('[GoalForm] Server response:', data);

                if (!data.success) {
                    console.error('[GoalForm] Server returned error:', data.data);
                    throw new Error(data.data || 'Failed to save goal');
                }

                console.log('[GoalForm] Save successful!');
                MyavanaTimeline.EntryForm.showNotification('Goal saved successfully!', 'success');

                setTimeout(() => {
                    console.log('[GoalForm] Refreshing page...');
                    location.reload();
                }, 500);

                return data;
            } catch (error) {
                console.error('[GoalForm] Save error:', error);
                throw error;
            }
        },

        /**
         * Delete goal
         */
        delete: async function(goalId) {
            console.log('[GoalForm] Deleting goal:', goalId);

            const settings = window.myavanaTimelineSettings || {};
            const formData = new FormData();
            formData.append('action', 'myavana_delete_goal');
            formData.append('goal_id', goalId);
            formData.append('security', settings.deleteGoalNonce || settings.nonce);

            try {
                const response = await fetch(settings.ajaxUrl || '/wp-admin/admin-ajax.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                console.log('[GoalForm] Delete response:', data);

                if (!data.success) {
                    throw new Error(data.data || 'Failed to delete goal');
                }

                MyavanaTimeline.EntryForm.showNotification('Goal deleted successfully!', 'success');

                // Close offcanvas and refresh
                setTimeout(() => {
                    if (window.closeTimelineViewOffcanvas) {
                        window.closeTimelineViewOffcanvas();
                    }
                    location.reload();
                }, 500);

                return data;
            } catch (error) {
                console.error('[GoalForm] Delete error:', error);
                MyavanaTimeline.EntryForm.showNotification('Failed to delete goal', 'error');
                throw error;
            }
        }
    };

    /**
     * Routine Form Handler
     */
    MyavanaTimeline.RoutineForm = {

        create: function() {
            const fields = [
                {
                    type: 'hidden',
                    name: 'action',
                    value: 'myavana_add_routine'
                },
                {
                    type: 'text',
                    name: 'title',
                    label: 'Routine Name',
                    placeholder: 'e.g., Morning Wash Day',
                    required: true
                },
                {
                    type: 'textarea',
                    name: 'description',
                    label: 'Steps',
                    placeholder: 'List the steps in your routine...',
                    required: true,
                    rows: 6
                },
                {
                    type: 'select',
                    name: 'frequency',
                    label: 'Frequency',
                    options: ['Daily', 'Weekly', 'Bi-Weekly', 'Monthly', 'As Needed']
                },
                {
                    type: 'text',
                    name: 'products',
                    label: 'Products Used',
                    placeholder: 'Enter products separated by commas'
                }
            ];

            MyavanaTimeline.FormBuilder.createFormModal({
                title: 'New Hair Routine',
                fields: fields,
                submitText: 'Create Routine',
                onSubmit: async (data) => {
                    return await this.save(data);
                }
            });
        },

        edit: function(routineId) {
            console.log('[RoutineForm] Loading routine for edit:', routineId);

            // Check if this is a mock/template routine (ID 0 or negative)
            // if (routineId === 0 || routineId < 0) {
            //     console.warn('[RoutineForm] Cannot edit template routine with ID:', routineId);
            //     alert('This is a template routine. Please create your own routine first using the "+" button.');
            //     return;
            // }

            // Show loading
            const loadingModal = MyavanaTimeline.EntryForm.showLoading('Loading routine...');

            // Fetch routine data
            this.fetchRoutine(routineId)
                .then(routine => {
                    loadingModal.close();
                    this.showEditForm(routine);
                })
                .catch(error => {
                    loadingModal.close();
                    alert('Error loading routine: ' + error.message);
                });
        },

        /**
         * Fetch routine data from server
         */
        fetchRoutine: async function(routineId) {
            console.log('[RoutineForm] Fetching routine with ID:', routineId);
            const settings = window.myavanaTimelineSettings || {};
            console.log('[RoutineForm] Settings:', {
                ajaxUrl: settings.ajaxUrl,
                hasGetEntryDetailsNonce: !!settings.getEntryDetailsNonce,
                hasNonce: !!settings.nonce
            });

            const formData = new FormData();
            formData.append('action', 'myavana_get_routine_details');
            formData.append('routine_id', routineId);

            const nonce = settings.getEntryDetailsNonce || settings.nonce;
            formData.append('security', nonce);
            console.log('[RoutineFormfetch] Using nonce for fetch:', nonce ? 'Present' : 'MISSING');

            try {
                const response = await fetch(settings.ajaxUrl || '/wp-admin/admin-ajax.php', {
                    method: 'POST',
                    body: formData
                });

                console.log('[RoutineForm] Fetch response status:', response.status);

                const data = await response.json();
                console.log('[RoutineForm] Fetch response data:', data);

                if (!data.success) {
                    throw new Error(data.data || 'Failed to load routine');
                }

                return data.data;
            } catch (error) {
                console.error('[RoutineForm] Fetch error:', error);
                throw error;
            }
        },

        /**
         * Show edit form with routine data
         */
        showEditForm: function(routine) {
            console.log('[RoutineForm] Opening edit form with data:', routine);

            const fields = [
                {
                    type: 'hidden',
                    name: 'action',
                    value: 'myavana_update_routine'
                },
                {
                    type: 'hidden',
                    name: 'routine_id',
                    value: routine.id
                },
                {
                    type: 'text',
                    name: 'title',
                    label: 'Routine Name',
                    value: routine.title || '',
                    required: true
                },
                {
                    type: 'textarea',
                    name: 'description',
                    label: 'Steps',
                    value: routine.description || '',
                    required: true,
                    rows: 6
                },
                {
                    type: 'select',
                    name: 'frequency',
                    label: 'Frequency',
                    value: routine.frequency || 'Weekly',
                    options: ['Daily', 'Weekly', 'Bi-Weekly', 'Monthly', 'As Needed']
                },
                {
                    type: 'text',
                    name: 'products',
                    label: 'Products Used',
                    value: routine.products || '',
                    placeholder: 'Enter products separated by commas'
                },
                {
                    type: 'text',
                    name: 'duration',
                    label: 'Duration',
                    value: routine.duration || '',
                    placeholder: 'e.g., 30 minutes'
                }
            ];

            MyavanaTimeline.FormBuilder.createFormModal({
                title: 'Edit Hair Routine',
                fields: fields,
                submitText: 'Update Routine',
                onSubmit: async (data) => {
                    return await this.save(data);
                }
            });
        },

        /**
         * Validate routine data before save
         */
        validate: function(formData) {
            const errors = [];

            if (!formData.title || formData.title.trim() === '') {
                errors.push('Routine name is required');
            }

            if (!formData.description || formData.description.trim() === '') {
                errors.push('Routine steps are required');
            }

            if (!formData.frequency) {
                errors.push('Frequency is required');
            }

            return errors;
        },

        save: async function(formData) {
            console.log('[RoutineForm] === SAVE STARTED ===');
            console.log('[RoutineForm] Form data received:', formData);
            console.log('[RoutineForm] Is update?', !!formData.routine_id);

            // Validate form data
            const validationErrors = this.validate(formData);
            if (validationErrors.length > 0) {
                console.error('[RoutineForm] Validation failed:', validationErrors);
                MyavanaTimeline.EntryForm.showNotification(validationErrors[0], 'error');
                throw new Error(validationErrors.join('\n'));
            }

            console.log('[RoutineForm] Validation passed');

            const settings = window.myavanaTimelineSettings || {};
            const fd = new FormData();

            // Add all form data
            Object.keys(formData).forEach(key => {
                if (formData[key] !== null && formData[key] !== undefined && formData[key] !== '') {
                    fd.append(key, formData[key]);
                    console.log(`[RoutineForm] Added field: ${key} = ${formData[key]}`);
                }
            });

            // Add appropriate nonce based on operation (update vs create)
            let nonce;
            if (formData.routine_id || formData.routine_id === 0) {
                nonce = settings.updateRoutineNonce || settings.addRoutineNonce || settings.nonce;
                console.log('[RoutineForm] Using UPDATE nonce');
            } else {
                nonce = settings.addRoutineNonce || settings.nonce;
                console.log('[RoutineForm] Using CREATE nonce');
            }
            fd.append('security', nonce);
            fd.append('myavana_nonce', nonce);
            console.log('[RoutineForm] Nonce value:', nonce ? 'Present' : 'MISSING!');

            console.log('[RoutineForm] Sending request to:', settings.ajaxUrl || '/wp-admin/admin-ajax.php');
            console.log('[RoutineForm] Action:', formData.action);

            try {
                const response = await fetch(settings.ajaxUrl || '/wp-admin/admin-ajax.php', {
                    method: 'POST',
                    body: fd
                });

                console.log('[RoutineForm] Response status:', response.status, response.statusText);

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();
                console.log('[RoutineForm] Server response:', data);

                if (!data.success) {
                    console.error('[RoutineForm] Server returned error:', data.data);
                    throw new Error(data.data || 'Failed to save routine');
                }

                console.log('[RoutineForm] Save successful!');
                MyavanaTimeline.EntryForm.showNotification('Routine saved successfully!', 'success');

                setTimeout(() => {
                    console.log('[RoutineForm] Refreshing page...');
                    location.reload();
                }, 500);

                return data;
            } catch (error) {
                console.error('[RoutineForm] Save error:', error);
                throw error;
            }
        },

        /**
         * Delete routine
         */
        delete: async function(routineId) {
            console.log('[RoutineForm] Deleting routine:', routineId);

            const settings = window.myavanaTimelineSettings || {};
            const formData = new FormData();
            formData.append('action', 'myavana_delete_routine');
            formData.append('routine_id', routineId);
            formData.append('security', settings.deleteRoutineNonce || settings.nonce);

            try {
                const response = await fetch(settings.ajaxUrl || '/wp-admin/admin-ajax.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                console.log('[RoutineForm] Delete response:', data);

                if (!data.success) {
                    throw new Error(data.data || 'Failed to delete routine');
                }

                MyavanaTimeline.EntryForm.showNotification('Routine deleted successfully!', 'success');

                // Close offcanvas and refresh
                setTimeout(() => {
                    if (window.closeTimelineViewOffcanvas) {
                        window.closeTimelineViewOffcanvas();
                    }
                    location.reload();
                }, 500);

                return data;
            } catch (error) {
                console.error('[RoutineForm] Delete error:', error);
                MyavanaTimeline.EntryForm.showNotification('Failed to delete routine', 'error');
                throw error;
            }
        }
    };

    // Global shortcuts with smart ID extraction from state when not provided
    window.createEntry = () => MyavanaTimeline.EntryForm.create();

    window.editEntry = (id) => {
        console.log('[Global editEntry] Called with ID:', id);

        // If no ID provided, try to get from currentViewData
        if (!id && MyavanaTimeline.State) {
            const currentViewData = MyavanaTimeline.State.get('currentViewData');
            console.log('[Global editEntry] currentViewData:', currentViewData);
            if (currentViewData && currentViewData.type === 'entry') {
                id = currentViewData.id ||
                     currentViewData.entry_id ||
                     (currentViewData.data && (currentViewData.data.id || currentViewData.data.entry_id));
                console.log('[Global editEntry] Extracted ID from state:', id);
            }
        }

        if (!id) {
            console.error('[Global editEntry] No ID available');
            alert('Could not find entry ID. Please try again.');
            return;
        }

        console.log('[Global editEntry] Calling EntryForm.edit with ID:', id);
        MyavanaTimeline.EntryForm.edit(id);
    };

    window.createGoal = () => MyavanaTimeline.GoalForm.create();

    window.editGoal = (id) => {
        console.log('[Global editGoal] Called with ID:', id);

        // Check if ID is valid (including 0 as valid)
        if (id === undefined || id === null || id === '') {
            const currentViewData = MyavanaTimeline.State ? MyavanaTimeline.State.get('currentViewData') : null;
            console.log('[Global editGoal] currentViewData:', currentViewData);
            if (currentViewData && currentViewData.type === 'goal') {
                id = currentViewData.id !== undefined ? currentViewData.id :
                     currentViewData.goal_id !== undefined ? currentViewData.goal_id :
                     (currentViewData.data && currentViewData.data.id !== undefined) ? currentViewData.data.id :
                     (currentViewData.data && currentViewData.data.goal_id !== undefined) ? currentViewData.data.goal_id : null;
                console.log('[Global editGoal] Extracted ID from state:', id);
            }
        }

        if (id === undefined || id === null || id === '') {
            console.error('[Global editGoal] No ID available');
            console.error('[Global editGoal] State dump:', MyavanaTimeline.State ? MyavanaTimeline.State.dump() : 'No state');
            alert('Could not find goal ID. Please try again.');
            return;
        }

        console.log('[Global editGoal] Calling GoalForm.edit with ID:', id);
        MyavanaTimeline.GoalForm.edit(id);
    };

    window.createRoutine = () => MyavanaTimeline.RoutineForm.create();

    window.editRoutine = (id) => {
        console.log('[Global editRoutine] Called with ID:', id);

        // Check if ID is valid (including 0 as valid for routines)
        if (id === undefined || id === null || id === '') {
            const currentViewData = MyavanaTimeline.State ? MyavanaTimeline.State.get('currentViewData') : null;
            console.log('[Global editRoutine] currentViewData:', currentViewData);
            if (currentViewData && currentViewData.type === 'routine') {
                id = currentViewData.id !== undefined ? currentViewData.id :
                     currentViewData.routine_id !== undefined ? currentViewData.routine_id :
                     (currentViewData.data && currentViewData.data.id !== undefined) ? currentViewData.data.id :
                     (currentViewData.data && currentViewData.data.routine_id !== undefined) ? currentViewData.data.routine_id : null;
                console.log('[Global editRoutine] Extracted ID from state:', id);
            }
        }

        if (id === undefined || id === null || id === '') {
            console.error('[Global editRoutine] No ID available');
            console.error('[Global editRoutine] State dump:', MyavanaTimeline.State ? MyavanaTimeline.State.dump() : 'No state');
            alert('Could not find routine ID. Please try again.');
            return;
        }

        console.log('[Global editRoutine] Calling RoutineForm.edit with ID:', id);
        MyavanaTimeline.RoutineForm.edit(id);
    };

    // Global delete functions with confirmation
    window.deleteEntry = (id) => {
        if (!id && MyavanaTimeline.State) {
            const currentViewData = MyavanaTimeline.State.get('currentViewData');
            if (currentViewData && currentViewData.type === 'entry') {
                id = currentViewData.id || currentViewData.entry_id;
            }
        }

        if (!id) {
            alert('Could not find entry ID');
            return;
        }

        if (confirm('Are you sure you want to delete this entry? This action cannot be undone.')) {
            MyavanaTimeline.EntryForm.delete(id);
        }
    };

    window.deleteGoal = (id) => {
        if (!id && MyavanaTimeline.State) {
            const currentViewData = MyavanaTimeline.State.get('currentViewData');
            if (currentViewData && currentViewData.type === 'goal') {
                id = currentViewData.id || currentViewData.goal_id;
            }
        }

        if (!id) {
            alert('Could not find goal ID');
            return;
        }

        if (confirm('Are you sure you want to delete this goal? This action cannot be undone.')) {
            MyavanaTimeline.GoalForm.delete(id);
        }
    };

    window.deleteRoutine = (id) => {
        if (!id && MyavanaTimeline.State) {
            const currentViewData = MyavanaTimeline.State.get('currentViewData');
            if (currentViewData && currentViewData.type === 'routine') {
                id = currentViewData.id || currentViewData.routine_id;
            }
        }

        if (!id) {
            alert('Could not find routine ID');
            return;
        }

        if (confirm('Are you sure you want to delete this routine? This action cannot be undone.')) {
            MyavanaTimeline.RoutineForm.delete(id);
        }
    };

    console.log('[Forms] New form system initialized with smart state extraction and delete functions');

})();
