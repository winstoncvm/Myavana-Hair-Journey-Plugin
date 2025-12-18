/**
 * MYAVANA Timeline - Form Builder
 * Clean, JavaScript-based form system
 * @version 2.3.5
 */

(function() {
    'use strict';

    // Create namespace
    window.MyavanaTimeline = window.MyavanaTimeline || {};

    /**
     * Form Builder - Creates and manages forms dynamically
     */
    MyavanaTimeline.FormBuilder = {

        /**
         * Create a full modal with form
         */
        createFormModal: function(config) {
            const {
                title,
                fields,
                onSubmit,
                onClose,
                submitText = 'Save',
                cancelText = 'Cancel',
                width = '600px'
            } = config;

            // Create modal overlay
            const overlay = document.createElement('div');
            overlay.className = 'myavana-form-modal-overlay';
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
                animation: fadeIn 0.2s ease;
            `;

            // Create modal container
            const modal = document.createElement('div');
            modal.className = 'myavana-form-modal';
            modal.style.cssText = `
                background: var(--myavana-white, #fff);
                border-radius: 12px;
                width: ${width};
                max-width: 95vw;
                max-height: 90vh;
                display: flex;
                flex-direction: column;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                animation: slideUp 0.3s ease;
            `;

            // Create header
            const header = document.createElement('div');
            header.style.cssText = `
                padding: 24px;
                border-bottom: 1px solid var(--myavana-stone, #f5f5f7);
                display: flex;
                justify-content: space-between;
                align-items: center;
            `;

            const titleEl = document.createElement('h2');
            titleEl.textContent = title;
            titleEl.style.cssText = `
                margin: 0;
                font-family: 'Archivo Black', sans-serif;
                font-size: 20px;
                text-transform: uppercase;
                color: var(--myavana-onyx, #222323);
            `;

            const closeBtn = document.createElement('button');
            closeBtn.innerHTML = '&times;';
            closeBtn.style.cssText = `
                background: none;
                border: none;
                font-size: 32px;
                color: var(--myavana-onyx, #222323);
                cursor: pointer;
                padding: 0;
                width: 32px;
                height: 32px;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: color 0.2s ease;
            `;
            closeBtn.onmouseover = () => closeBtn.style.color = 'var(--myavana-coral, #e7a690)';
            closeBtn.onmouseout = () => closeBtn.style.color = 'var(--myavana-onyx, #222323)';

            header.appendChild(titleEl);
            header.appendChild(closeBtn);

            // Create form
            const form = document.createElement('form');
            form.style.cssText = `
                overflow-y: auto;
                flex: 1;
                display: flex;
                flex-direction: column;
            `;

            // Create fields container (for padding)
            const fieldsContainer = document.createElement('div');
            fieldsContainer.style.cssText = `
                padding: 24px;
                flex: 1;
            `;

            // Add fields to container
            fields.forEach(field => {
                const fieldGroup = this.createField(field);
                fieldsContainer.appendChild(fieldGroup);
            });

            // Add fields container to form
            form.appendChild(fieldsContainer);

            // Create footer
            const footer = document.createElement('div');
            footer.style.cssText = `
                padding: 24px;
                border-top: 1px solid var(--myavana-stone, #f5f5f7);
                display: flex;
                gap: 12px;
                justify-content: flex-end;
            `;

            const cancelButton = document.createElement('button');
            cancelButton.type = 'button';
            cancelButton.textContent = cancelText;
            cancelButton.style.cssText = `
                padding: 12px 24px;
                border: 1px solid var(--myavana-stone, #f5f5f7);
                background: var(--myavana-white, #fff);
                color: var(--myavana-onyx, #222323);
                font-family: 'Archivo', sans-serif;
                font-weight: 600;
                font-size: 14px;
                text-transform: uppercase;
                border-radius: 6px;
                cursor: pointer;
                transition: all 0.2s ease;
            `;
            cancelButton.onmouseover = () => cancelButton.style.background = 'var(--myavana-stone, #f5f5f7)';
            cancelButton.onmouseout = () => cancelButton.style.background = 'var(--myavana-white, #fff)';

            const submitButton = document.createElement('button');
            submitButton.type = 'submit';
            submitButton.textContent = submitText;
            submitButton.style.cssText = `
                padding: 12px 24px;
                border: none;
                background: var(--myavana-coral, #e7a690);
                color: var(--myavana-white, #fff);
                font-family: 'Archivo', sans-serif;
                font-weight: 600;
                font-size: 14px;
                text-transform: uppercase;
                border-radius: 6px;
                cursor: pointer;
                transition: all 0.2s ease;
            `;
            submitButton.onmouseover = () => {
                submitButton.style.background = '#d4956f';
                submitButton.style.transform = 'translateY(-2px)';
            };
            submitButton.onmouseout = () => {
                submitButton.style.background = 'var(--myavana-coral, #e7a690)';
                submitButton.style.transform = 'translateY(0)';
            };

            // DIAGNOSTIC: Add click handler to button itself
            submitButton.onclick = (e) => {
                console.log('[FormBuilder] 💥 BUTTON CLICKED!', e);
                console.log('[FormBuilder] Button type:', submitButton.type);
                console.log('[FormBuilder] Form element:', form);
                // Don't prevent default - let the form submission happen naturally
            };

            footer.appendChild(cancelButton);
            footer.appendChild(submitButton);

            // Assemble modal - IMPORTANT: Footer must be INSIDE form for submit button to work!
            modal.appendChild(header);
            form.appendChild(footer);  // Footer goes inside form
            modal.appendChild(form);
            overlay.appendChild(modal);

            // Handle close
            const close = () => {
                overlay.style.animation = 'fadeOut 0.2s ease';
                setTimeout(() => {
                    document.body.removeChild(overlay);
                    document.body.style.overflow = '';
                    if (onClose) onClose();
                }, 200);
            };

            closeBtn.onclick = close;
            cancelButton.onclick = close;
            overlay.onclick = (e) => {
                if (e.target === overlay) close();
            };

            // Handle submit
            form.onsubmit = async (e) => {
                console.log('[FormBuilder] 🚀 FORM SUBMIT TRIGGERED!', e);
                e.preventDefault();

                console.log('[FormBuilder] Submit button:', submitButton);
                console.log('[FormBuilder] Fields config:', fields);

                submitButton.disabled = true;
                submitButton.textContent = 'Saving...';
                console.log('[FormBuilder] Button disabled, text changed to Saving...');

                console.log('[FormBuilder] Getting form data...');
                const formData = this.getFormData(form, fields);
                console.log('[FormBuilder] Form data collected:', formData);

                try {
                    console.log('[FormBuilder] Calling onSubmit callback...');
                    await onSubmit(formData);
                    console.log('[FormBuilder] onSubmit completed successfully!');
                    close();
                } catch (error) {
                    console.error('[FormBuilder] Form submission error:', error);
                    alert(error.message || 'An error occurred. Please try again.');
                } finally {
                    submitButton.disabled = false;
                    submitButton.textContent = submitText;
                    console.log('[FormBuilder] Submit handler finished');
                }
            };

            // Add to DOM
            document.body.appendChild(overlay);
            document.body.style.overflow = 'hidden';

            // Add animations
            const style = document.createElement('style');
            style.textContent = `
                @keyframes fadeIn {
                    from { opacity: 0; }
                    to { opacity: 1; }
                }
                @keyframes fadeOut {
                    from { opacity: 1; }
                    to { opacity: 0; }
                }
                @keyframes slideUp {
                    from { transform: translateY(20px); opacity: 0; }
                    to { transform: translateY(0); opacity: 1; }
                }
            `;
            if (!document.getElementById('myavana-form-animations')) {
                style.id = 'myavana-form-animations';
                document.head.appendChild(style);
            }

            return { modal: overlay, form, close };
        },

        /**
         * Create a form field
         */
        createField: function(field) {
            const {
                type,
                name,
                label,
                placeholder,
                value = '',
                required = false,
                options = [],
                multiple = false,
                accept,
                rows = 4
            } = field;

            const fieldGroup = document.createElement('div');
            fieldGroup.style.cssText = 'margin-bottom: 20px;';

            // Label
            if (label) {
                const labelEl = document.createElement('label');
                labelEl.textContent = label + (required ? ' *' : '');
                labelEl.style.cssText = `
                    display: block;
                    margin-bottom: 8px;
                    font-family: 'Archivo', sans-serif;
                    font-weight: 600;
                    font-size: 13px;
                    color: var(--myavana-onyx, #222323);
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                `;
                fieldGroup.appendChild(labelEl);
            }

            let input;

            // Create input based on type
            switch (type) {
                case 'textarea':
                    input = document.createElement('textarea');
                    input.rows = rows;
                    break;
                case 'select':
                    input = document.createElement('select');
                    if (multiple) input.multiple = true;
                    options.forEach(opt => {
                        const option = document.createElement('option');
                        option.value = typeof opt === 'object' ? opt.value : opt;
                        option.textContent = typeof opt === 'object' ? opt.label : opt;
                        input.appendChild(option);
                    });
                    break;
                case 'file':
                    input = document.createElement('input');
                    input.type = 'file';
                    if (accept) input.accept = accept;
                    break;
                default:
                    input = document.createElement('input');
                    input.type = type || 'text';
            }

            input.name = name;
            if (placeholder) input.placeholder = placeholder;
            if (required) input.required = true;
            if (value !== undefined && value !== null) input.value = value;

            // Styling
            const baseStyle = `
                width: 100%;
                padding: 12px;
                border: 1px solid var(--myavana-stone, #f5f5f7);
                border-radius: 6px;
                font-family: 'Archivo', sans-serif;
                font-size: 14px;
                color: var(--myavana-onyx, #222323);
                transition: border-color 0.2s ease;
            `;
            input.style.cssText = baseStyle;
            input.onfocus = () => input.style.borderColor = 'var(--myavana-coral, #e7a690)';
            input.onblur = () => input.style.borderColor = 'var(--myavana-stone, #f5f5f7)';

            fieldGroup.appendChild(input);

            return fieldGroup;
        },

        /**
         * Get form data
         */
        getFormData: function(form, fields) {
            const data = {};
            const formData = new FormData(form);

            fields.forEach(field => {
                if (field.type === 'file') {
                    data[field.name] = form.querySelector(`[name="${field.name}"]`).files[0];
                } else if (field.multiple) {
                    const select = form.querySelector(`[name="${field.name}"]`);
                    data[field.name] = Array.from(select.selectedOptions).map(opt => opt.value);
                } else {
                    data[field.name] = formData.get(field.name);
                }
            });

            return data;
        },

        /**
         * Populate form with data
         */
        populateForm: function(form, data) {
            Object.keys(data).forEach(key => {
                const input = form.querySelector(`[name="${key}"]`);
                if (!input) return;

                if (input.type === 'checkbox') {
                    input.checked = data[key];
                } else if (input.tagName === 'SELECT' && input.multiple) {
                    const values = Array.isArray(data[key]) ? data[key] : [data[key]];
                    Array.from(input.options).forEach(option => {
                        option.selected = values.includes(option.value);
                    });
                } else {
                    input.value = data[key] || '';
                }
            });
        }
    };

})();
