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

    const OffcanvasRuntime = {
        initialized: false,
        removedGalleryIds: new Set(),
        removedVideoIds: new Set()
    };

    function isInlineHjnOwned(form) {
        return !!(form && form.dataset && form.dataset.submitOwner === 'hjn');
    }

    function getTimelineSettings() {
        return window.myavanaTimelineSettings || {};
    }

    function isBlank(value) {
        return value === undefined || value === null || value === '';
    }

    function openPanel(type) {
        if (typeof window.openOffcanvas === 'function') {
            window.openOffcanvas(type);
            return;
        }

        if (MyavanaTimeline.Offcanvas && typeof MyavanaTimeline.Offcanvas.open === 'function') {
            MyavanaTimeline.Offcanvas.open(type);
        }
    }

    function closePanel() {
        if (typeof window.closeOffcanvas === 'function') {
            window.closeOffcanvas();
            return;
        }

        if (MyavanaTimeline.Offcanvas && typeof MyavanaTimeline.Offcanvas.close === 'function') {
            MyavanaTimeline.Offcanvas.close();
        }
    }

    function flashMessage(message, type) {
        if (MyavanaTimeline.EntryForm && typeof MyavanaTimeline.EntryForm.showNotification === 'function') {
            MyavanaTimeline.EntryForm.showNotification(message, type || 'info');
            return;
        }
        alert(message);
    }

    function normalizeList(value) {
        if (Array.isArray(value)) {
            return value.filter(Boolean);
        }

        if (typeof value === 'string') {
            const trimmed = value.trim();
            if (!trimmed) return [];
            if (trimmed.startsWith('[')) {
                try {
                    const parsed = JSON.parse(trimmed);
                    return Array.isArray(parsed) ? parsed.filter(Boolean) : [];
                } catch (e) {
                    return trimmed.split(',').map(item => item.trim()).filter(Boolean);
                }
            }
            return trimmed.split(',').map(item => item.trim()).filter(Boolean);
        }

        return [];
    }

    function setActiveTagPill(containerId, value) {
        const normalized = String(value || '').trim();
        const pills = document.querySelectorAll(`#${containerId} .tag-pill-hjn`);
        pills.forEach(pill => {
            const matches = normalized !== '' && pill.dataset && pill.dataset.value === normalized;
            pill.classList.toggle('is-active', matches);
        });
    }

    function updateEntryRemovedMetaInputs() {
        const removedGalleryInput = document.getElementById('removed_gallery_ids');
        const removedVideoInput = document.getElementById('removed_video_ids');

        if (removedGalleryInput) {
            removedGalleryInput.value = JSON.stringify(Array.from(OffcanvasRuntime.removedGalleryIds));
        }
        if (removedVideoInput) {
            removedVideoInput.value = JSON.stringify(Array.from(OffcanvasRuntime.removedVideoIds));
        }
    }

    function renderExistingMediaList(containerId, items, mediaType) {
        const container = document.getElementById(containerId);
        if (!container) return;

        if (!Array.isArray(items) || !items.length) {
            container.innerHTML = '';
            return;
        }

        const html = items.map((item, index) => {
            const url = typeof item === 'string' ? item : (item.url || item.src || item.thumbnail || '');
            const id = typeof item === 'object' && item.id !== undefined ? item.id : '';
            const safeUrl = String(url || '').replace(/"/g, '&quot;');
            const safeId = String(id);

            const mediaMarkup = mediaType === 'video'
                ? `<video src="${safeUrl}" muted playsinline controls></video>`
                : `<img src="${safeUrl}" alt="Entry media ${index + 1}" />`;

            const removeButton = safeId !== ''
                ? `<button type="button" class="existing-media-remove-hjn" data-media-type="${mediaType}" data-media-id="${safeId}" title="Remove">✕</button>`
                : '';

            return `
                <div class="existing-media-card-hjn" data-media-type="${mediaType}" data-media-id="${safeId}">
                    ${mediaMarkup}
                    ${removeButton}
                </div>
            `;
        }).join('');

        container.innerHTML = html;
    }

    function setEntryRating(rating) {
        const ratingInput = document.getElementById('health_rating');
        const ratingValue = document.getElementById('health_rating_value');
        const stars = document.querySelectorAll('#health_rating_stars .rating-star-hjn');
        const normalized = Math.min(5, Math.max(1, parseInt(rating || 3, 10)));

        if (ratingInput) ratingInput.value = normalized;
        if (ratingValue) ratingValue.textContent = `${normalized}/5`;

        stars.forEach(star => {
            const value = parseInt(star.getAttribute('data-value') || '0', 10);
            if (value <= normalized) {
                star.classList.add('active');
            } else {
                star.classList.remove('active');
            }
        });
    }

    function resetEntryFormForCreate(prefillData) {
        const form = document.getElementById('entryForm');
        if (!form) return;

        form.reset();
        OffcanvasRuntime.removedGalleryIds.clear();
        OffcanvasRuntime.removedVideoIds.clear();
        updateEntryRemovedMetaInputs();

        const entryIdInput = document.getElementById('entry_id');
        const titleEl = document.getElementById('entryOffcanvasTitle');
        if (entryIdInput) entryIdInput.value = '';
        if (titleEl) titleEl.textContent = 'Add Hair Journey Entry';

        const dateInput = document.getElementById('entry_date');
        const timeInput = document.getElementById('entry_time');
        const now = new Date();
        if (dateInput) dateInput.value = now.toISOString().split('T')[0];
        if (timeInput) timeInput.value = `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;

        document.getElementById('existingImagesGallery')?.style.setProperty('display', 'none');
        document.getElementById('existingVideosGallery')?.style.setProperty('display', 'none');
        const quickPreview = document.getElementById('entryQuickMediaPreview');
        if (quickPreview) quickPreview.innerHTML = '';

        setEntryRating(3);

        if (prefillData && typeof prefillData === 'object') {
            if (prefillData.title) form.querySelector('#entry_title').value = prefillData.title;
            if (prefillData.description) form.querySelector('#entry_content').value = prefillData.description;
            if (prefillData.entry_type && document.getElementById('entry_type_hidden')) {
                document.getElementById('entry_type_hidden').value = prefillData.entry_type;
                document.querySelectorAll('#entryTypePills .tag-pill-hjn').forEach(function (pill) {
                    pill.classList.toggle('is-active', pill.dataset.value === prefillData.entry_type);
                    pill.classList.toggle('selected', pill.dataset.value === prefillData.entry_type);
                });
            }
            if (prefillData.mood) {
                const moodHidden = document.getElementById('mood_hidden');
                if (moodHidden) {
                    moodHidden.value = prefillData.mood;
                }
                document.querySelectorAll('#moodPills .mood-pill-hjn').forEach(function (pill) {
                    const input = pill.querySelector('input');
                    const isMatch = input && input.value === prefillData.mood;
                    pill.classList.toggle('selected', !!isMatch);
                    if (input) {
                        input.checked = !!isMatch;
                    }
                });
            }
        }
    }

    function fillEntryFormForEdit(entry) {
        const form = document.getElementById('entryForm');
        if (!form || !entry) return;

        OffcanvasRuntime.removedGalleryIds.clear();
        OffcanvasRuntime.removedVideoIds.clear();
        updateEntryRemovedMetaInputs();

        const titleEl = document.getElementById('entryOffcanvasTitle');
        if (titleEl) titleEl.textContent = 'Edit Hair Journey Entry';

        const entryId = entry.id || entry.entry_id || '';
        if (document.getElementById('entry_id')) document.getElementById('entry_id').value = entryId;
        if (document.getElementById('entry_title')) document.getElementById('entry_title').value = entry.title || entry.entry_title || '';
        if (document.getElementById('entry_date')) document.getElementById('entry_date').value = entry.entry_date || '';
        if (document.getElementById('entry_time')) document.getElementById('entry_time').value = entry.entry_time || '';
        if (document.getElementById('entry_content')) document.getElementById('entry_content').value = entry.description || entry.content || '';
        if (document.getElementById('notes')) document.getElementById('notes').value = entry.notes || '';
        if (document.getElementById('techniques')) document.getElementById('techniques').value = entry.techniques || '';
        if (document.getElementById('entry_video_notes')) document.getElementById('entry_video_notes').value = entry.video_notes || '';
        if (document.getElementById('mood')) document.getElementById('mood').value = entry.mood || entry.mood_demeanor || '';
        if (document.getElementById('entry_type')) document.getElementById('entry_type').value = entry.entry_type || '';
        if (document.getElementById('environment')) document.getElementById('environment').value = entry.environment || '';
        if (document.getElementById('scalp_condition')) document.getElementById('scalp_condition').value = entry.scalp_condition || '';
        if (document.getElementById('hair_feel')) document.getElementById('hair_feel').value = entry.hair_feel || '';
        if (document.getElementById('length_check_cm')) document.getElementById('length_check_cm').value = entry.length_check_cm || '';
        if (document.getElementById('entry_tags')) {
            const tagsValue = Array.isArray(entry.entry_tags_list)
                ? entry.entry_tags_list.join(', ')
                : (entry.entry_tags || '');
            document.getElementById('entry_tags').value = tagsValue;
        }
        if (document.getElementById('next_step')) document.getElementById('next_step').value = entry.next_step || '';

        const productsSelect = document.getElementById('products_used');
        if (productsSelect) {
            const normalizedProducts = normalizeList(entry.products || entry.products_used);
            Array.from(productsSelect.options || []).forEach(option => {
                option.selected = normalizedProducts.includes(option.value) || normalizedProducts.includes(option.text);
            });
        }

        setEntryRating(entry.rating || 3);

        const images = Array.isArray(entry.images) ? entry.images : [];
        const videos = Array.isArray(entry.videos) ? entry.videos : [];
        renderExistingMediaList('existingImagesGrid', images, 'image');
        renderExistingMediaList('existingVideosGrid', videos, 'video');

        const imagesGallery = document.getElementById('existingImagesGallery');
        const videosGallery = document.getElementById('existingVideosGallery');
        if (imagesGallery) imagesGallery.style.display = images.length ? 'block' : 'none';
        if (videosGallery) videosGallery.style.display = videos.length ? 'block' : 'none';
    }

    function fetchEntryDetails(entryId) {
        const settings = getTimelineSettings();
        const nonce = settings.getEntryDetailsNonce || settings.nonce || '';
        const formData = new FormData();
        formData.append('action', 'myavana_get_entry_details');
        formData.append('entry_id', entryId);
        formData.append('security', nonce);

        return fetch(settings.ajaxUrl || settings.ajaxurl || '/wp-admin/admin-ajax.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(result => {
            if (result && result.success && result.data) {
                return result.data;
            }
            throw new Error((result && result.data) || 'Failed to load entry');
        })
        .catch(() => MyavanaTimeline.EntryForm.fetchEntry(entryId));
    }

    function appendFilesToInput(targetInput, incomingFiles) {
        if (!targetInput || !incomingFiles || !incomingFiles.length) return;

        const dt = new DataTransfer();
        Array.from(targetInput.files || []).forEach(file => dt.items.add(file));
        Array.from(incomingFiles).forEach(file => dt.items.add(file));
        targetInput.files = dt.files;
    }

    function refreshQuickMediaPreview() {
        const preview = document.getElementById('entryQuickMediaPreview');
        const photos = document.getElementById('entry_photos');
        const videos = document.getElementById('entry_videos');
        if (!preview) return;

        const photoItems = Array.from((photos && photos.files) || []).map(file => ({
            type: 'Image',
            name: file.name
        }));
        const videoItems = Array.from((videos && videos.files) || []).map(file => ({
            type: 'Video',
            name: file.name
        }));
        const allItems = photoItems.concat(videoItems);

        if (!allItems.length) {
            preview.innerHTML = '';
            return;
        }

        preview.innerHTML = allItems.map(item => (
            `<div class="entry-quick-preview-item-hjn"><strong>${item.type}:</strong><br>${item.name}</div>`
        )).join('');
    }

    function collectInputValues(selector) {
        return Array.from(document.querySelectorAll(selector))
            .map(input => (input.value || '').trim())
            .filter(Boolean);
    }

    function isValidTimeString(value) {
        return /^\d{2}:\d{2}$/.test(String(value || ''));
    }

    function openGoalForCreate() {
        const form = document.getElementById('goalForm');
        if (!form) {
            MyavanaTimeline.GoalForm.create();
            return;
        }

        form.reset();
        const title = document.getElementById('goalOffcanvasTitle');
        if (title) title.textContent = 'Create Hair Goal';
        const action = form.querySelector('input[name="action"]');
        if (action) action.value = 'myavana_add_goal';
        const idField = document.getElementById('goal_id');
        if (idField) idField.value = '';
        const milestoneWrap = document.getElementById('milestones_list');
        if (milestoneWrap) milestoneWrap.innerHTML = '';
        const progress = document.getElementById('goal_progress');
        const progressValue = document.getElementById('goal_progress_value');
        if (progress) progress.value = 0;
        if (progressValue) progressValue.textContent = '0%';
        const progressNotesGroup = document.getElementById('goalProgressNotesGroup');
        const addProgressNoteGroup = document.getElementById('addProgressNoteGroup');
        if (progressNotesGroup) progressNotesGroup.style.display = 'none';
        if (addProgressNoteGroup) addProgressNoteGroup.style.display = 'none';
        if (document.getElementById('newProgressNote')) document.getElementById('newProgressNote').value = '';

        if (document.getElementById('goal_baseline_value')) document.getElementById('goal_baseline_value').value = '';
        if (document.getElementById('goal_target_value')) document.getElementById('goal_target_value').value = '';
        if (document.getElementById('goal_measure_unit')) document.getElementById('goal_measure_unit').value = '';
        if (document.getElementById('goal_reward')) document.getElementById('goal_reward').value = '';
        if (document.getElementById('goal_success_criteria')) document.getElementById('goal_success_criteria').value = '';

        openPanel('goal');
    }

    function openRoutineForCreate() {
        const form = document.getElementById('routineForm');
        if (!form) {
            MyavanaTimeline.RoutineForm.create();
            return;
        }

        form.reset();
        const title = document.getElementById('routineOffcanvasTitle');
        if (title) title.textContent = 'Create Hair Routine';
        const action = form.querySelector('input[name="action"]');
        if (action) action.value = 'myavana_add_routine';
        const idField = document.getElementById('routine_id');
        if (idField) idField.value = '';
        if (document.getElementById('routine_auto_track')) {
            document.getElementById('routine_auto_track').checked = true;
        }

        const stepsWrap = document.getElementById('routine_steps_list');
        if (stepsWrap) {
            stepsWrap.innerHTML = '';
            window.addRoutineStep('');
        }

        openPanel('routine');
    }

    function initGoalMilestones() {
        if (!document.getElementById('milestones_list')?.children.length) {
            window.addMilestone('');
        }
    }

    function initRoutineSteps() {
        const wrap = document.getElementById('routine_steps_list');
        if (wrap && !wrap.querySelector('.routine-step-item-hjn')) {
            window.addRoutineStep('');
        }
    }

    function bindOffcanvasEvents() {
        if (OffcanvasRuntime.initialized) return;
        OffcanvasRuntime.initialized = true;

        const entryForm = document.getElementById('entryForm');
        const goalForm = document.getElementById('goalForm');
        const routineForm = document.getElementById('routineForm');

        if (entryForm && !isInlineHjnOwned(entryForm)) {
            entryForm.addEventListener('submit', handleEntrySubmit);
        }
        if (goalForm && !isInlineHjnOwned(goalForm)) {
            goalForm.addEventListener('submit', handleGoalSubmit);
        }
        if (routineForm && !isInlineHjnOwned(routineForm)) {
            routineForm.addEventListener('submit', handleRoutineSubmit);
        }

        // Entry rating stars
        document.querySelectorAll('#health_rating_stars .rating-star-hjn').forEach(star => {
            star.addEventListener('click', function() {
                const value = parseInt(this.getAttribute('data-value') || '3', 10);
                setEntryRating(value);
            });
        });

        // Goal progress slider
        const progressInput = document.getElementById('goal_progress');
        if (progressInput) {
            progressInput.addEventListener('input', function() {
                const label = document.getElementById('goal_progress_value');
                if (label) label.textContent = `${this.value}%`;
            });
        }

        // Entry camera/gallery action buttons
        const capturePhotoBtn = document.getElementById('entryCapturePhotoBtn');
        const captureVideoBtn = document.getElementById('entryCaptureVideoBtn');
        const pickMediaBtn = document.getElementById('entryPickMediaBtn');
        const photoCameraInput = document.getElementById('entryCameraPhotoInput');
        const videoCameraInput = document.getElementById('entryCameraVideoInput');
        const mediaPickerInput = document.getElementById('entryMediaPickerInput');
        const entryPhotosInput = document.getElementById('entry_photos');
        const entryVideosInput = document.getElementById('entry_videos');

        if (capturePhotoBtn && photoCameraInput) {
            capturePhotoBtn.addEventListener('click', () => photoCameraInput.click());
        }
        if (captureVideoBtn && videoCameraInput) {
            captureVideoBtn.addEventListener('click', () => videoCameraInput.click());
        }
        if (pickMediaBtn && mediaPickerInput) {
            pickMediaBtn.addEventListener('click', () => mediaPickerInput.click());
        }

        if (photoCameraInput && entryPhotosInput) {
            photoCameraInput.addEventListener('change', function() {
                appendFilesToInput(entryPhotosInput, this.files);
                refreshQuickMediaPreview();
                this.value = '';
            });
        }

        if (videoCameraInput && entryVideosInput) {
            videoCameraInput.addEventListener('change', function() {
                appendFilesToInput(entryVideosInput, this.files);
                refreshQuickMediaPreview();
                this.value = '';
            });
        }

        if (mediaPickerInput) {
            mediaPickerInput.addEventListener('change', function() {
                const imageFiles = Array.from(this.files || []).filter(file => file.type.startsWith('image/'));
                const videoFiles = Array.from(this.files || []).filter(file => file.type.startsWith('video/'));
                appendFilesToInput(entryPhotosInput, imageFiles);
                appendFilesToInput(entryVideosInput, videoFiles);
                refreshQuickMediaPreview();
                this.value = '';
            });
        }

        if (entryPhotosInput) {
            entryPhotosInput.addEventListener('change', refreshQuickMediaPreview);
        }
        if (entryVideosInput) {
            entryVideosInput.addEventListener('change', refreshQuickMediaPreview);
        }

        // Existing media removal toggles
        document.addEventListener('click', function(event) {
            const removeBtn = event.target.closest('.existing-media-remove-hjn');
            if (!removeBtn) return;

            const mediaId = removeBtn.getAttribute('data-media-id');
            const mediaType = removeBtn.getAttribute('data-media-type');
            const card = removeBtn.closest('.existing-media-card-hjn');
            if (!mediaId || !mediaType || !card) return;

            const parsedId = parseInt(mediaId, 10);
            if (Number.isNaN(parsedId)) return;

            if (mediaType === 'image') {
                if (OffcanvasRuntime.removedGalleryIds.has(parsedId)) {
                    OffcanvasRuntime.removedGalleryIds.delete(parsedId);
                    card.classList.remove('is-removed');
                    removeBtn.classList.remove('is-removed');
                } else {
                    OffcanvasRuntime.removedGalleryIds.add(parsedId);
                    card.classList.add('is-removed');
                    removeBtn.classList.add('is-removed');
                }
            } else {
                if (OffcanvasRuntime.removedVideoIds.has(parsedId)) {
                    OffcanvasRuntime.removedVideoIds.delete(parsedId);
                    card.classList.remove('is-removed');
                    removeBtn.classList.remove('is-removed');
                } else {
                    OffcanvasRuntime.removedVideoIds.add(parsedId);
                    card.classList.add('is-removed');
                    removeBtn.classList.add('is-removed');
                }
            }

            updateEntryRemovedMetaInputs();
        });

        initGoalMilestones();
        initRoutineSteps();
    }

    function handleEntrySubmit(event) {
        event.preventDefault();
        const form = event.target;
        const settings = getTimelineSettings();
        const entryId = document.getElementById('entry_id')?.value || '';
        const submitBtn = form.querySelector('button[type="submit"]');
        const loading = document.getElementById('entryFormLoading');

        const nonce = entryId
            ? (settings.updateEntryNonce || settings.addEntryNonce || settings.nonce || '')
            : (settings.addEntryNonce || settings.nonce || '');

        const title = (document.getElementById('entry_title')?.value || '').trim();
        const entryDate = (document.getElementById('entry_date')?.value || '').trim();
        const entryTime = (document.getElementById('entry_time')?.value || '').trim();
        const rating = parseInt(document.getElementById('health_rating')?.value || '0', 10);
        const lengthCheckRaw = (document.getElementById('length_check_cm')?.value || '').trim();
        const photoCount = document.getElementById('entry_photos')?.files?.length || 0;
        const videoCount = document.getElementById('entry_videos')?.files?.length || 0;

        if (!title) {
            flashMessage('Entry title is required.', 'error');
            return;
        }
        if (!entryDate) {
            flashMessage('Entry date is required.', 'error');
            return;
        }
        if (entryTime && !isValidTimeString(entryTime)) {
            flashMessage('Entry time must be in HH:MM format.', 'error');
            return;
        }
        if (Number.isNaN(rating) || rating < 1 || rating > 5) {
            flashMessage('Please select a hair health rating from 1 to 5.', 'error');
            return;
        }
        if (lengthCheckRaw !== '') {
            const lengthCheck = Number(lengthCheckRaw);
            if (Number.isNaN(lengthCheck) || lengthCheck < 0 || lengthCheck > 200) {
                flashMessage('Length check must be between 0 and 200 cm.', 'error');
                return;
            }
        }
        if (photoCount > 5) {
            flashMessage('You can upload up to 5 photos per entry.', 'error');
            return;
        }
        if (videoCount > 3) {
            flashMessage('You can upload up to 3 videos per entry.', 'error');
            return;
        }

        updateEntryRemovedMetaInputs();

        const formData = new FormData(form);
        formData.set('action', 'myavana_entry_action');
        formData.set('security', nonce);
        formData.set('myavana_nonce', nonce);

        if (submitBtn) submitBtn.disabled = true;
        if (loading) loading.style.display = 'flex';

        fetch(settings.ajaxUrl || settings.ajaxurl || '/wp-admin/admin-ajax.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(result => {
            if (!result || !result.success) {
                throw new Error((result && result.data) || 'Unable to save entry');
            }
            flashMessage(entryId ? 'Entry updated successfully!' : 'Entry added successfully!', 'success');
            closePanel();
            setTimeout(() => window.location.reload(), 350);
        })
        .catch(error => {
            flashMessage(error.message || 'Unable to save entry', 'error');
        })
        .finally(() => {
            if (submitBtn) submitBtn.disabled = false;
            if (loading) loading.style.display = 'none';
        });
    }

    function handleGoalSubmit(event) {
        event.preventDefault();
        const form = event.target;
        const settings = getTimelineSettings();
        const goalId = document.getElementById('goal_id')?.value;
        const action = isBlank(goalId) ? 'myavana_add_goal' : 'myavana_update_goal';
        const nonce = isBlank(goalId)
            ? (settings.addGoalNonce || settings.nonce || '')
            : (settings.updateGoalNonce || settings.addGoalNonce || settings.nonce || '');

        const title = (document.getElementById('goal_title')?.value || '').trim();
        if (!title) {
            flashMessage('Goal title is required.', 'error');
            return;
        }

        const startDate = (document.getElementById('goal_start_date')?.value || '').trim();
        const targetDate = (document.getElementById('goal_end_date')?.value || '').trim();
        if (!startDate) {
            flashMessage('Goal start date is required.', 'error');
            return;
        }
        if (startDate && targetDate) {
            const startTs = new Date(startDate).getTime();
            const endTs = new Date(targetDate).getTime();
            if (!Number.isNaN(startTs) && !Number.isNaN(endTs) && endTs < startTs) {
                flashMessage('Goal target date must be on or after start date.', 'error');
                return;
            }
        }

        const description = (document.getElementById('goal_description')?.value || '').trim();
        const milestones = collectInputValues('input[name="goal_milestones[]"], input[name="milestones[]"]');
        const progressNote = (document.getElementById('newProgressNote')?.value || '').trim();
        const baselineValue = (document.getElementById('goal_baseline_value')?.value || '').trim();
        const targetValue = (document.getElementById('goal_target_value')?.value || '').trim();

        if (baselineValue !== '' && Number.isNaN(Number(baselineValue))) {
            flashMessage('Goal baseline value must be numeric.', 'error');
            return;
        }
        if (targetValue !== '' && Number.isNaN(Number(targetValue))) {
            flashMessage('Goal target value must be numeric.', 'error');
            return;
        }
        if (baselineValue !== '' && targetValue !== '' && Number(targetValue) < Number(baselineValue)) {
            flashMessage('Goal target value should be greater than or equal to baseline.', 'error');
            return;
        }

        const payload = new FormData();
        payload.append('action', action);
        if (!isBlank(goalId)) payload.append('goal_id', goalId);
        payload.append('security', nonce);
        payload.append('myavana_nonce', nonce);
        payload.append('title', title);
        payload.append('goal_title', title);
        payload.append('description', description);
        payload.append('goal_description', description);
        payload.append('start_date', startDate);
        payload.append('goal_category', document.getElementById('goal_category_hidden')?.value || '');
        payload.append('goal_start_date', startDate);
        payload.append('target_date', targetDate);
        payload.append('goal_end_date', targetDate);
        payload.append('goal_target', document.getElementById('goal_target')?.value || '');
        payload.append('goal_baseline_value', baselineValue);
        payload.append('goal_target_value', targetValue);
        payload.append('goal_measure_unit', document.getElementById('goal_measure_unit')?.value || '');
        payload.append('goal_reward', document.getElementById('goal_reward')?.value || '');
        payload.append('goal_success_criteria', document.getElementById('goal_success_criteria')?.value || '');
        payload.append('goal_priority', document.getElementById('goal_priority')?.value || 'Medium');
        payload.append('goal_checkin_frequency', document.getElementById('goal_checkin_frequency')?.value || 'Weekly');
        payload.append('goal_motivation', document.getElementById('goal_motivation')?.value || '');
        payload.append('goal_blockers', document.getElementById('goal_blockers')?.value || '');
        payload.append('goal_progress', document.getElementById('goal_progress')?.value || '0');
        payload.append('goal_milestones', JSON.stringify(milestones));
        if (progressNote) {
            payload.append('progress_note', progressNote);
        }

        const loading = document.getElementById('goalFormLoading');
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) submitBtn.disabled = true;
        if (loading) loading.style.display = 'flex';

        fetch(settings.ajaxUrl || settings.ajaxurl || '/wp-admin/admin-ajax.php', {
            method: 'POST',
            body: payload
        })
        .then(response => response.json())
        .then(result => {
            if (!result || !result.success) {
                throw new Error((result && result.data) || 'Unable to save goal');
            }
            flashMessage(!isBlank(goalId) ? 'Goal updated successfully!' : 'Goal created successfully!', 'success');
            closePanel();
            setTimeout(() => window.location.reload(), 350);
        })
        .catch(error => {
            flashMessage(error.message || 'Unable to save goal', 'error');
        })
        .finally(() => {
            if (submitBtn) submitBtn.disabled = false;
            if (loading) loading.style.display = 'none';
        });
    }

    function handleRoutineSubmit(event) {
        event.preventDefault();
        const form = event.target;
        const settings = getTimelineSettings();
        const routineId = document.getElementById('routine_id')?.value;
        const action = isBlank(routineId) ? 'myavana_add_routine' : 'myavana_update_routine';
        const nonce = isBlank(routineId)
            ? (settings.addRoutineNonce || settings.nonce || '')
            : (settings.updateRoutineNonce || settings.addRoutineNonce || settings.nonce || '');

        const title = (document.getElementById('routine_title')?.value || '').trim();
        if (!title) {
            flashMessage('Routine title is required.', 'error');
            return;
        }
        const frequency = (document.getElementById('routine_frequency')?.value || '').trim();
        if (!frequency) {
            flashMessage('Routine frequency is required.', 'error');
            return;
        }

        const steps = collectInputValues('input[name="routine_steps[]"]');
        if (!steps.length) {
            flashMessage('Please add at least one routine step.', 'error');
            return;
        }

        const payload = new FormData();
        payload.append('action', action);
        if (!isBlank(routineId)) payload.append('routine_id', routineId);
        payload.append('security', nonce);
        payload.append('myavana_nonce', nonce);
        payload.append('title', title);
        payload.append('routine_title', title);
        payload.append('routine_name', title);
        payload.append('description', document.getElementById('routine_notes')?.value || '');
        payload.append('routine_notes', document.getElementById('routine_notes')?.value || '');
        payload.append('routine_type', document.getElementById('routine_type_hidden')?.value || '');
        payload.append('frequency', frequency);
        payload.append('routine_frequency', frequency);
        payload.append('routine_time', document.getElementById('routine_time')?.value || '');
        payload.append('duration', document.getElementById('routine_duration')?.value || '');
        payload.append('routine_duration', document.getElementById('routine_duration')?.value || '');
        payload.append('routine_products', document.getElementById('routine_products')?.value || '');
        payload.append('products', document.getElementById('routine_products')?.value || '');
        payload.append('routine_focus', document.getElementById('routine_focus')?.value || '');
        payload.append('routine_difficulty', document.getElementById('routine_difficulty')?.value || 'Beginner');
        payload.append('routine_reminder_days', document.getElementById('routine_reminder_days')?.value || '');
        payload.append('routine_expected_result', document.getElementById('routine_expected_result')?.value || '');
        payload.append('routine_phase', document.getElementById('routine_phase')?.value || 'Anytime');
        payload.append('routine_goal_link', document.getElementById('routine_goal_link')?.value || '');
        payload.append('routine_tools', document.getElementById('routine_tools')?.value || '');
        payload.append('routine_auto_track', document.getElementById('routine_auto_track')?.checked ? '1' : '0');
        payload.append('routine_steps', JSON.stringify(steps));

        const loading = document.getElementById('routineFormLoading');
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) submitBtn.disabled = true;
        if (loading) loading.style.display = 'flex';

        fetch(settings.ajaxUrl || settings.ajaxurl || '/wp-admin/admin-ajax.php', {
            method: 'POST',
            body: payload
        })
        .then(response => response.json())
        .then(result => {
            if (!result || !result.success) {
                throw new Error((result && result.data) || 'Unable to save routine');
            }
            flashMessage(!isBlank(routineId) ? 'Routine updated successfully!' : 'Routine created successfully!', 'success');
            closePanel();
            setTimeout(() => window.location.reload(), 350);
        })
        .catch(error => {
            flashMessage(error.message || 'Unable to save routine', 'error');
        })
        .finally(() => {
            if (submitBtn) submitBtn.disabled = false;
            if (loading) loading.style.display = 'none';
        });
    }

    function openEntryCreate(prefillData) {
        const form = document.getElementById('entryForm');
        if (!form) {
            // Keep existing premium path as fallback only if offcanvas form is unavailable.
            if (typeof window.MyavanaPremiumEntryForm !== 'undefined') {
                window.MyavanaPremiumEntryForm.open(prefillData);
            } else {
                MyavanaTimeline.EntryForm.createFallback();
            }
            return;
        }

        resetEntryFormForCreate(prefillData);
        openPanel('entry');
    }

    function openEntryEdit(entryId) {
        fetchEntryDetails(entryId)
            .then(entry => {
                openPanel('entry');
                fillEntryFormForEdit(entry);
                refreshQuickMediaPreview();
            })
            .catch(error => {
                flashMessage(error.message || 'Unable to load entry details', 'error');
            });
    }

    function openGoalEdit(goalId) {
        const settings = getTimelineSettings();
        const nonce = settings.getGoalDetailsNonce || settings.getEntryDetailsNonce || settings.nonce || '';
        const payload = new FormData();
        payload.append('action', 'myavana_get_goal_details');
        payload.append('goal_id', goalId);
        payload.append('security', nonce);

        fetch(settings.ajaxUrl || settings.ajaxurl || '/wp-admin/admin-ajax.php', {
            method: 'POST',
            body: payload
        })
        .then(response => response.json())
        .then(result => {
            if (!result || !result.success || !result.data) {
                throw new Error((result && result.data) || 'Unable to load goal');
            }

            openPanel('goal');
            const goal = result.data;
            const form = document.getElementById('goalForm');
            if (!form) return;

            form.querySelector('input[name="action"]').value = 'myavana_update_goal';
            document.getElementById('goalOffcanvasTitle').textContent = 'Edit Hair Goal';
            document.getElementById('goal_id').value = goal.goal_id ?? goal.id ?? goalId;
            document.getElementById('goal_title').value = goal.title || goal.goal_title || '';
            const goalCategoryValue = goal.goal_category || goal.category || '';
            const goalCategoryHidden = document.getElementById('goal_category_hidden');
            if (goalCategoryHidden) goalCategoryHidden.value = goalCategoryValue;
            setActiveTagPill('goalCategoryPills', goalCategoryValue);
            document.getElementById('goal_description').value = goal.description || goal.goal_description || '';
            document.getElementById('goal_start_date').value = goal.start_date || goal.goal_start_date || '';
            document.getElementById('goal_end_date').value = goal.target_date || goal.goal_end_date || '';
            document.getElementById('goal_priority').value = goal.goal_priority || goal.priority || 'Medium';
            document.getElementById('goal_checkin_frequency').value = goal.goal_checkin_frequency || 'Weekly';
            document.getElementById('goal_motivation').value = goal.goal_motivation || '';
            document.getElementById('goal_blockers').value = goal.goal_blockers || '';
            document.getElementById('goal_baseline_value').value = goal.goal_baseline_value || '';
            document.getElementById('goal_target_value').value = goal.goal_target_value || '';
            document.getElementById('goal_measure_unit').value = goal.goal_measure_unit || '';
            document.getElementById('goal_reward').value = goal.goal_reward || '';
            document.getElementById('goal_success_criteria').value = goal.goal_success_criteria || '';
            const goalProgressEl = document.getElementById('goal_progress');
            const goalProgressValueEl = document.getElementById('goal_progress_value');
            if (goalProgressEl) goalProgressEl.value = goal.progress || goal.goal_progress || 0;
            if (goalProgressValueEl) goalProgressValueEl.textContent = `${goal.progress || goal.goal_progress || 0}%`;

            const addProgressNoteGroup = document.getElementById('addProgressNoteGroup');
            if (addProgressNoteGroup) {
                addProgressNoteGroup.style.display = 'block';
            }

            const milestonesWrap = document.getElementById('milestones_list');
            if (milestonesWrap) milestonesWrap.innerHTML = '';
            normalizeList(goal.milestones || goal.goal_milestones).forEach(milestone => window.addMilestone(milestone));
            if (!document.getElementById('milestones_list')?.children.length) {
                window.addMilestone('');
            }

            const progressNotes = normalizeList(goal.progress_text || goal.goal_progress_notes);
            const progressNotesGroup = document.getElementById('goalProgressNotesGroup');
            const progressNotesList = document.getElementById('goalProgressNotesList');
            if (progressNotesGroup && progressNotesList) {
                if (Array.isArray(goal.progress_text) && goal.progress_text.length) {
                    progressNotesGroup.style.display = 'block';
                    progressNotesList.innerHTML = goal.progress_text.map(note => {
                        const noteText = typeof note === 'object' ? (note.text || '') : String(note || '');
                        const noteDate = typeof note === 'object' && note.date
                            ? new Date(note.date).toLocaleDateString()
                            : '';
                        return `<div class="goal-edit-note-item-hjn"><div class="goal-edit-note-date-hjn">${noteDate}</div><div class="goal-edit-note-text-hjn">${noteText}</div></div>`;
                    }).join('');
                } else if (progressNotes.length) {
                    progressNotesGroup.style.display = 'block';
                    progressNotesList.innerHTML = progressNotes.map(note => `<div class="goal-edit-note-item-hjn"><div class="goal-edit-note-text-hjn">${String(note)}</div></div>`).join('');
                } else {
                    progressNotesGroup.style.display = 'none';
                    progressNotesList.innerHTML = '';
                }
            }
        })
        .catch(error => flashMessage(error.message || 'Unable to load goal', 'error'));
    }

    function openRoutineEdit(routineId) {
        const settings = getTimelineSettings();
        const nonce = settings.getRoutineDetailsNonce || settings.getEntryDetailsNonce || settings.nonce || '';
        const payload = new FormData();
        payload.append('action', 'myavana_get_routine_details');
        payload.append('routine_id', routineId);
        payload.append('security', nonce);

        fetch(settings.ajaxUrl || settings.ajaxurl || '/wp-admin/admin-ajax.php', {
            method: 'POST',
            body: payload
        })
        .then(response => response.json())
        .then(result => {
            if (!result || !result.success || !result.data) {
                throw new Error((result && result.data) || 'Unable to load routine');
            }

            openPanel('routine');
            const routine = result.data;
            const form = document.getElementById('routineForm');
            if (!form) return;

            form.querySelector('input[name="action"]').value = 'myavana_update_routine';
            document.getElementById('routineOffcanvasTitle').textContent = 'Edit Hair Routine';
            document.getElementById('routine_id').value = routine.routine_id ?? routine.id ?? routineId;
            document.getElementById('routine_title').value = routine.title || routine.routine_title || '';
            const routineTypeValue = routine.routine_type || routine.type || '';
            const routineTypeHidden = document.getElementById('routine_type_hidden');
            if (routineTypeHidden) routineTypeHidden.value = routineTypeValue;
            setActiveTagPill('routineTypePills', routineTypeValue);
            document.getElementById('routine_frequency').value = routine.routine_frequency || routine.frequency || 'Weekly';
            document.getElementById('routine_time').value = routine.routine_time || routine.time || '';
            document.getElementById('routine_duration').value = routine.routine_duration || routine.duration || '';
            document.getElementById('routine_products').value = Array.isArray(routine.products)
                ? routine.products.join('\n')
                : (routine.routine_products || routine.products || '');
            document.getElementById('routine_difficulty').value = routine.routine_difficulty || 'Beginner';
            document.getElementById('routine_reminder_days').value = routine.routine_reminder_days || '';
            document.getElementById('routine_expected_result').value = routine.routine_expected_result || '';
            document.getElementById('routine_phase').value = routine.routine_phase || 'Anytime';
            document.getElementById('routine_tools').value = routine.routine_tools || '';
            document.getElementById('routine_auto_track').checked = String(routine.routine_auto_track || '0') === '1';
            document.getElementById('routine_notes').value = routine.routine_notes || routine.description || '';

            const stepsWrap = document.getElementById('routine_steps_list');
            if (stepsWrap) stepsWrap.innerHTML = '';
            normalizeList(routine.steps || routine.routine_steps).forEach(step => window.addRoutineStep(step));
            if (!document.getElementById('routine_steps_list')?.children.length) {
                window.addRoutineStep('');
            }
        })
        .catch(error => flashMessage(error.message || 'Unable to load routine', 'error'));
    }

    window.addMilestone = function(value) {
        const wrap = document.getElementById('milestones_list');
        if (!wrap) return;

        const item = document.createElement('div');
        item.className = 'milestone-item-hjn';
        item.innerHTML = `
            <input type="text" name="goal_milestones[]" class="form-input-hjn" placeholder="Milestone..." value="${String(value || '').replace(/"/g, '&quot;')}">
            <button type="button" class="btn-remove-milestone-hjn" title="Remove milestone">✕</button>
        `;
        item.querySelector('.btn-remove-milestone-hjn').addEventListener('click', function() {
            item.remove();
            if (!wrap.children.length) {
                window.addMilestone('');
            }
        });
        wrap.appendChild(item);
    };

    window.addRoutineStep = function(value) {
        const wrap = document.getElementById('routine_steps_list');
        if (!wrap) return;

        const stepIndex = wrap.querySelectorAll('.routine-step-item-hjn').length + 1;
        const item = document.createElement('div');
        item.className = 'routine-step-item-hjn';
        item.innerHTML = `
            <div class="step-number-hjn">${stepIndex}</div>
            <input type="text" name="routine_steps[]" class="form-input-hjn step-input-hjn" placeholder="Describe this step..." value="${String(value || '').replace(/"/g, '&quot;')}" required>
            <button type="button" class="btn-remove-step-hjn" title="Remove step">✕</button>
        `;
        item.querySelector('.btn-remove-step-hjn').addEventListener('click', function() {
            item.remove();
            const remaining = wrap.querySelectorAll('.routine-step-item-hjn');
            remaining.forEach((row, index) => {
                const numberEl = row.querySelector('.step-number-hjn');
                if (numberEl) numberEl.textContent = String(index + 1);
            });
            if (!remaining.length) {
                window.addRoutineStep('');
            }
        });
        wrap.appendChild(item);
    };

    window.removeRoutineStep = function(button) {
        const row = button ? button.closest('.routine-step-item-hjn') : null;
        if (row) {
            row.remove();
        }
        const wrap = document.getElementById('routine_steps_list');
        const remaining = wrap ? wrap.querySelectorAll('.routine-step-item-hjn') : [];
        remaining.forEach((item, idx) => {
            const numberEl = item.querySelector('.step-number-hjn');
            if (numberEl) numberEl.textContent = String(idx + 1);
        });
        if (wrap && !remaining.length) {
            window.addRoutineStep('');
        }
    };

    // Expose module expected by timeline-init.js
    MyavanaTimeline.Forms = {
        init: bindOffcanvasEvents
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bindOffcanvasEvents);
    } else {
        bindOffcanvasEvents();
    }

    // Override default create/edit flow to offcanvas-first behavior
    MyavanaTimeline.EntryForm.create = openEntryCreate;
    MyavanaTimeline.EntryForm.edit = openEntryEdit;
    MyavanaTimeline.GoalForm.create = openGoalForCreate;
    MyavanaTimeline.GoalForm.edit = openGoalEdit;
    MyavanaTimeline.RoutineForm.create = openRoutineForCreate;
    MyavanaTimeline.RoutineForm.edit = openRoutineEdit;

    // Global shortcuts with smart ID extraction from state when not provided
    window.createEntry = (prefillData) => MyavanaTimeline.EntryForm.create(prefillData);

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

    window.createGoal = (prefillData) => {
        if (typeof window.myavanaOpenCollectionComposer === 'function') {
            return window.myavanaOpenCollectionComposer('goal', prefillData || {});
        }
        return MyavanaTimeline.GoalForm.create();
    };

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

    window.createRoutine = (prefillData) => {
        if (typeof window.myavanaOpenCollectionComposer === 'function') {
            return window.myavanaOpenCollectionComposer('routine', prefillData || {});
        }
        return MyavanaTimeline.RoutineForm.create();
    };

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
        if ((id === undefined || id === null || id === '') && MyavanaTimeline.State) {
            const currentViewData = MyavanaTimeline.State.get('currentViewData');
            if (currentViewData && currentViewData.type === 'entry') {
                id = currentViewData.id || currentViewData.entry_id;
            }
        }

        if (id === undefined || id === null || id === '') {
            alert('Could not find entry ID');
            return;
        }

        if (confirm('Are you sure you want to delete this entry? This action cannot be undone.')) {
            MyavanaTimeline.EntryForm.delete(id);
        }
    };

    window.deleteGoal = (id) => {
        if ((id === undefined || id === null || id === '') && MyavanaTimeline.State) {
            const currentViewData = MyavanaTimeline.State.get('currentViewData');
            if (currentViewData && currentViewData.type === 'goal') {
                id = currentViewData.id ?? currentViewData.goal_id;
            }
        }

        if (id === undefined || id === null || id === '') {
            alert('Could not find goal ID');
            return;
        }

        if (confirm('Are you sure you want to delete this goal? This action cannot be undone.')) {
            MyavanaTimeline.GoalForm.delete(id);
        }
    };

    window.deleteRoutine = (id) => {
        if ((id === undefined || id === null || id === '') && MyavanaTimeline.State) {
            const currentViewData = MyavanaTimeline.State.get('currentViewData');
            if (currentViewData && currentViewData.type === 'routine') {
                id = currentViewData.id ?? currentViewData.routine_id;
            }
        }

        if (id === undefined || id === null || id === '') {
            alert('Could not find routine ID');
            return;
        }

        if (confirm('Are you sure you want to delete this routine? This action cannot be undone.')) {
            MyavanaTimeline.RoutineForm.delete(id);
        }
    };

    console.log('[Forms] New form system initialized with smart state extraction and delete functions');

})();
