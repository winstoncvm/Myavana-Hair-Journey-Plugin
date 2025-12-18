/**
 * MYAVANA Entry Form JavaScript
 * Enhanced form handling for hair journey entries
 */

class MyavanaEntryForm {
    constructor() {
        this.photos = [];
        this.currentRating = 5;
        this.selectedMood = null;
        this.isSubmitting = false;
        this.init();
    }

    init() {
        document.addEventListener('DOMContentLoaded', () => {
            this.bindEvents();
            this.initializeForm();
        });
    }

    bindEvents() {
        // Health rating slider
        const ratingSlider = document.getElementById('myavanaHealthRating');
        if (ratingSlider) {
            ratingSlider.addEventListener('input', (e) => this.updateRating(e.target.value));
        }

        // Mood selection
        const moodOptions = document.querySelectorAll('.myavana-mood-option');
        moodOptions.forEach(option => {
            option.addEventListener('click', () => this.selectMood(option));
        });

        // Photo upload
        const photoInput = document.getElementById('myavanaPhotos');
        if (photoInput) {
            photoInput.addEventListener('change', (e) => this.handlePhotoUpload(e));
        }

        // Form submission
        const entryForm = document.getElementById('myavanaEntryForm');
        if (entryForm) {
            entryForm.addEventListener('submit', (e) => this.handleSubmit(e));
        }

        // Photo removal
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('myavana-photo-remove')) {
                this.removePhoto(e.target.dataset.index);
            }
        });
    }

    initializeForm() {
        // Set current date
        const dateInput = document.getElementById('entryDate');
        if (dateInput) {
            dateInput.value = new Date().toISOString().split('T')[0];
        }

        // Initialize rating display
        this.updateRating(5);
    }

    updateRating(value) {
        this.currentRating = parseInt(value);
        const display = document.getElementById('ratingValue');
        if (display) {
            display.textContent = this.currentRating;
        }

        // Update slider track color based on value
        const slider = document.getElementById('myavanaHealthRating');
        if (slider) {
            const percentage = (this.currentRating - 1) / 9 * 100;
            slider.style.background = `linear-gradient(to right, var(--myavana-coral) 0%, var(--myavana-coral) ${percentage}%, var(--myavana-cream) ${percentage}%, var(--myavana-cream) 100%)`;
        }
    }

    selectMood(option) {
        // Clear previous selection
        document.querySelectorAll('.myavana-mood-option').forEach(opt => {
            opt.classList.remove('selected');
        });

        // Select current option
        option.classList.add('selected');
        this.selectedMood = option.dataset.mood;
    }

    handlePhotoUpload(event) {
        const files = Array.from(event.target.files);
        
        files.forEach(file => {
            if (this.validatePhoto(file)) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.addPhoto(e.target.result, file.name);
                };
                reader.readAsDataURL(file);
            }
        });
    }

    validatePhoto(file) {
        // Check file type
        if (!file.type.startsWith('image/')) {
            this.showMessage('Please select only image files.', 'error');
            return false;
        }

        // Check file size (5MB limit)
        if (file.size > 5 * 1024 * 1024) {
            this.showMessage('Photo must be smaller than 5MB.', 'error');
            return false;
        }

        // Check photo limit
        if (this.photos.length >= 5) {
            this.showMessage('Maximum 5 photos allowed per entry.', 'error');
            return false;
        }

        return true;
    }

    addPhoto(dataUrl, name) {
        const photo = {
            data: dataUrl,
            name: name,
            id: Date.now()
        };

        this.photos.push(photo);
        this.renderPhotoPreview(photo, this.photos.length - 1);
    }

    renderPhotoPreview(photo, index) {
        const previewContainer = document.getElementById('photoPreview');
        if (!previewContainer) return;

        const photoDiv = document.createElement('div');
        photoDiv.className = 'myavana-photo-item';
        photoDiv.innerHTML = `
            <img src="${photo.data}" alt="${photo.name}" />
            <button type="button" class="myavana-photo-remove" data-index="${index}">×</button>
        `;

        previewContainer.appendChild(photoDiv);
    }

    removePhoto(index) {
        this.photos.splice(index, 1);
        this.renderAllPhotos();
    }

    renderAllPhotos() {
        const previewContainer = document.getElementById('photoPreview');
        if (!previewContainer) return;

        previewContainer.innerHTML = '';
        this.photos.forEach((photo, index) => {
            this.renderPhotoPreview(photo, index);
        });
    }

    async handleSubmit(event) {
        event.preventDefault();

        if (this.isSubmitting) return;

        this.isSubmitting = true;
        this.showLoading(true);

        try {
            const formData = this.gatherFormData();
            const result = await this.submitEntry(formData);

            if (result.success) {
                this.showMessage('Entry saved successfully!', 'success');
                this.resetForm();
            } else {
                throw new Error(result.data?.message || 'Failed to save entry');
            }
        } catch (error) {
            console.error('Form submission error:', error);
            this.showMessage(error.message || 'Failed to save entry. Please try again.', 'error');
        } finally {
            this.isSubmitting = false;
            this.showLoading(false);
        }
    }

    gatherFormData() {
        const form = document.getElementById('myavanaEntryForm');
        const formData = new FormData(form);

        // Add photos
        this.photos.forEach((photo, index) => {
            formData.append(`photos[${index}]`, photo.data);
        });

        // Add rating and mood
        formData.append('health_rating', this.currentRating);
        formData.append('mood', this.selectedMood || 'neutral');

        return formData;
    }

    async submitEntry(formData) {
        if (!myavanaEntryForm) {
            throw new Error('Form configuration missing');
        }

        const response = await fetch(myavanaEntryForm.ajax_url, {
            method: 'POST',
            body: formData
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        return await response.json();
    }

    showLoading(show) {
        const form = document.getElementById('myavanaEntryForm');
        if (form) {
            if (show) {
                form.classList.add('myavana-form-loading');
            } else {
                form.classList.remove('myavana-form-loading');
            }
        }
    }

    showMessage(message, type) {
        // Remove existing messages
        const existingMessages = document.querySelectorAll('.myavana-message');
        existingMessages.forEach(msg => msg.remove());

        // Create new message
        const messageDiv = document.createElement('div');
        messageDiv.className = `myavana-message myavana-message-${type}`;
        messageDiv.innerHTML = `
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                ${type === 'success' 
                    ? '<path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22,4 12,14.01 9,11.01"/>'
                    : '<circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>'
                }
            </svg>
            <span>${message}</span>
        `;

        // Insert at top of form
        const form = document.getElementById('myavanaEntryForm');
        if (form) {
            form.insertBefore(messageDiv, form.firstChild);

            // Auto-remove after 5 seconds
            setTimeout(() => {
                if (messageDiv.parentNode) {
                    messageDiv.remove();
                }
            }, 5000);
        }
    }

    resetForm() {
        const form = document.getElementById('myavanaEntryForm');
        if (form) {
            form.reset();
        }

        // Reset custom states
        this.photos = [];
        this.selectedMood = null;
        this.currentRating = 5;

        // Clear photo preview
        const previewContainer = document.getElementById('photoPreview');
        if (previewContainer) {
            previewContainer.innerHTML = '';
        }

        // Clear mood selection
        document.querySelectorAll('.myavana-mood-option').forEach(opt => {
            opt.classList.remove('selected');
        });

        // Reset rating
        this.updateRating(5);

        // Set current date
        const dateInput = document.getElementById('entryDate');
        if (dateInput) {
            dateInput.value = new Date().toISOString().split('T')[0];
        }
    }
}

// Initialize when DOM is ready
new MyavanaEntryForm();