<?php
/**
 * Entry Form Component - Lazy Loaded
 * Hair journey entry form with image upload and AI analysis
 */

// Security check
if (!defined('ABSPATH')) {
    exit;
}

// Get current user
$user_id = get_current_user_id();
if (!$user_id) {
    wp_send_json_error('Not authenticated');
    return;
}

// Get user profile data
$profile = get_user_meta($user_id, 'myavana_profile', true);
$current_routine = get_user_meta($user_id, 'myavana_current_routine', true) ?: [];
?>

<div class="myavana-entry-form" id="myavanaEntryForm">
    <form id="hairEntryForm" class="myavana-form" enctype="multipart/form-data">
        <!-- Header Section -->
        <div class="form-header">
            <h3 class="form-title">Add Hair Journey Entry</h3>
            <p class="form-subtitle">Document your hair progress with photos and notes</p>
        </div>

        <!-- Photo Upload Section -->
        <div class="form-section photo-section">
            <h4 class="section-title">📸 Progress Photos</h4>

            <div class="photo-upload-grid">
                <div class="photo-upload-item" data-type="front">
                    <div class="upload-area" id="frontPhotoUpload">
                        <div class="upload-placeholder">
                            <svg viewBox="0 0 24 24" class="upload-icon">
                                <path d="M12 2L13.09 8.26L22 9L13.09 9.74L12 16L10.91 9.74L2 9L10.91 8.26L12 2Z"/>
                            </svg>
                            <span class="upload-text">Front View</span>
                            <span class="upload-hint">Tap to add photo</span>
                        </div>
                        <input type="file" id="frontPhoto" name="front_photo" accept="image/*" capture="environment">
                        <div class="photo-preview" style="display: none;">
                            <img class="preview-image" alt="Front view preview">
                            <button type="button" class="remove-photo" aria-label="Remove photo">×</button>
                        </div>
                    </div>
                </div>

                <div class="photo-upload-item" data-type="side">
                    <div class="upload-area" id="sidePhotoUpload">
                        <div class="upload-placeholder">
                            <svg viewBox="0 0 24 24" class="upload-icon">
                                <path d="M12 2L13.09 8.26L22 9L13.09 9.74L12 16L10.91 9.74L2 9L10.91 8.26L12 2Z"/>
                            </svg>
                            <span class="upload-text">Side View</span>
                            <span class="upload-hint">Tap to add photo</span>
                        </div>
                        <input type="file" id="sidePhoto" name="side_photo" accept="image/*" capture="environment">
                        <div class="photo-preview" style="display: none;">
                            <img class="preview-image" alt="Side view preview">
                            <button type="button" class="remove-photo" aria-label="Remove photo">×</button>
                        </div>
                    </div>
                </div>

                <div class="photo-upload-item" data-type="back">
                    <div class="upload-area" id="backPhotoUpload">
                        <div class="upload-placeholder">
                            <svg viewBox="0 0 24 24" class="upload-icon">
                                <path d="M12 2L13.09 8.26L22 9L13.09 9.74L12 16L10.91 9.74L2 9L10.91 8.26L12 2Z"/>
                            </svg>
                            <span class="upload-text">Back View</span>
                            <span class="upload-hint">Tap to add photo</span>
                        </div>
                        <input type="file" id="backPhoto" name="back_photo" accept="image/*" capture="environment">
                        <div class="photo-preview" style="display: none;">
                            <img class="preview-image" alt="Back view preview">
                            <button type="button" class="remove-photo" aria-label="Remove photo">×</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Entry Details Section -->
        <div class="form-section details-section">
            <h4 class="section-title">📝 Entry Details</h4>

            <div class="form-group">
                <label for="entryTitle" class="form-label">Entry Title</label>
                <input type="text" id="entryTitle" name="entry_title" class="form-input"
                       placeholder="e.g., Week 4 Progress" required>
            </div>

            <div class="form-group">
                <label for="entryNotes" class="form-label">Notes & Observations</label>
                <textarea id="entryNotes" name="entry_notes" class="form-textarea" rows="4"
                          placeholder="How does your hair feel? Any changes you've noticed? Products used today..."></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="moodRating" class="form-label">Hair Mood Today</label>
                    <div class="mood-selector">
                        <input type="radio" id="mood1" name="mood_rating" value="1">
                        <label for="mood1" class="mood-label">😞</label>

                        <input type="radio" id="mood2" name="mood_rating" value="2">
                        <label for="mood2" class="mood-label">😐</label>

                        <input type="radio" id="mood3" name="mood_rating" value="3">
                        <label for="mood3" class="mood-label">🙂</label>

                        <input type="radio" id="mood4" name="mood_rating" value="4">
                        <label for="mood4" class="mood-label">😊</label>

                        <input type="radio" id="mood5" name="mood_rating" value="5">
                        <label for="mood5" class="mood-label">🤩</label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="lengthProgress" class="form-label">Length Progress</label>
                    <select id="lengthProgress" name="length_progress" class="form-select">
                        <option value="">Select progress</option>
                        <option value="significant_growth">Significant Growth 📈</option>
                        <option value="some_growth">Some Growth 📊</option>
                        <option value="no_change">No Change ➡️</option>
                        <option value="trim_needed">Trim Needed ✂️</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Products Used Section -->
        <div class="form-section products-section">
            <h4 class="section-title">🧴 Products Used Today</h4>

            <div class="products-grid">
                <div class="product-category">
                    <label class="category-label">Shampoo</label>
                    <input type="text" name="products[shampoo]" class="form-input"
                           placeholder="Product name or 'None'">
                </div>

                <div class="product-category">
                    <label class="category-label">Conditioner</label>
                    <input type="text" name="products[conditioner]" class="form-input"
                           placeholder="Product name or 'None'">
                </div>

                <div class="product-category">
                    <label class="category-label">Leave-in Treatment</label>
                    <input type="text" name="products[leave_in]" class="form-input"
                           placeholder="Product name or 'None'">
                </div>

                <div class="product-category">
                    <label class="category-label">Styling Products</label>
                    <input type="text" name="products[styling]" class="form-input"
                           placeholder="Product name or 'None'">
                </div>
            </div>
        </div>

        <!-- AI Analysis Toggle -->
        <div class="form-section ai-section">
            <div class="ai-toggle-wrapper">
                <label class="ai-toggle">
                    <input type="checkbox" id="requestAIAnalysis" name="request_ai_analysis" value="1" checked>
                    <span class="toggle-slider"></span>
                    <span class="toggle-label">
                        <strong>🤖 Request AI Analysis</strong>
                        <small>Get personalized insights about your hair progress</small>
                    </span>
                </label>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="form-actions">
            <button type="button" id="cancelEntry" class="myavana-btn myavana-btn-outline">
                Cancel
            </button>
            <button type="submit" id="saveEntry" class="myavana-btn myavana-btn-primary">
                <span class="btn-text">Save Entry</span>
                <span class="btn-loading" style="display: none;">
                    <svg class="loading-spinner" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"
                                fill="none" stroke-dasharray="60" stroke-dashoffset="60"
                                style="animation: spin 1s linear infinite;"/>
                    </svg>
                    Saving...
                </span>
            </button>
        </div>

        <!-- Hidden fields -->
        <input type="hidden" name="action" value="myavana_save_hair_entry">
        <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('myavana_entry'); ?>">
        <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
    </form>
</div>

<style>
.myavana-entry-form {
    max-width: 600px;
    margin: 0 auto;
    font-family: 'Archivo', sans-serif;
}

.form-header {
    text-align: center;
    margin-bottom: 24px;
}

.form-title {
    font-family: 'Archivo Black', sans-serif;
    font-size: 20px;
    color: var(--myavana-onyx);
    margin: 0 0 8px 0;
    text-transform: uppercase;
}

.form-subtitle {
    color: var(--myavana-blueberry);
    font-size: 14px;
    margin: 0;
}

.form-section {
    margin-bottom: 24px;
    padding: 20px;
    background: var(--myavana-stone);
    border-radius: 12px;
    border: 1px solid var(--myavana-sand);
}

.section-title {
    font-family: 'Archivo', sans-serif;
    font-weight: 600;
    font-size: 16px;
    color: var(--myavana-onyx);
    margin: 0 0 16px 0;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.photo-upload-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 16px;
}

.upload-area {
    position: relative;
    aspect-ratio: 1;
    border: 2px dashed var(--myavana-coral);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
    background: var(--myavana-white);
}

.upload-area:hover {
    border-color: var(--myavana-onyx);
    background: var(--myavana-light-coral);
}

.upload-placeholder {
    text-align: center;
    pointer-events: none;
}

.upload-icon {
    width: 32px;
    height: 32px;
    fill: var(--myavana-coral);
    margin-bottom: 8px;
}

.upload-text {
    display: block;
    font-weight: 600;
    color: var(--myavana-onyx);
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.upload-hint {
    display: block;
    color: var(--myavana-blueberry);
    font-size: 11px;
    margin-top: 4px;
}

.upload-area input[type="file"] {
    position: absolute;
    inset: 0;
    opacity: 0;
    cursor: pointer;
}

.photo-preview {
    position: absolute;
    inset: 0;
    border-radius: 10px;
    overflow: hidden;
}

.preview-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.remove-photo {
    position: absolute;
    top: 8px;
    right: 8px;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: rgba(0, 0, 0, 0.7);
    color: white;
    border: none;
    font-size: 16px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

.form-group {
    margin-bottom: 16px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.form-label {
    display: block;
    font-weight: 600;
    color: var(--myavana-onyx);
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 6px;
}

.form-input, .form-select, .form-textarea {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid var(--myavana-sand);
    border-radius: 8px;
    font-family: 'Archivo', sans-serif;
    font-size: 14px;
    background: var(--myavana-white);
    transition: border-color 0.3s ease;
}

.form-input:focus, .form-select:focus, .form-textarea:focus {
    outline: none;
    border-color: var(--myavana-coral);
}

.mood-selector {
    display: flex;
    gap: 8px;
}

.mood-selector input[type="radio"] {
    display: none;
}

.mood-label {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    border: 2px solid var(--myavana-sand);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    cursor: pointer;
    transition: all 0.3s ease;
    background: var(--myavana-white);
}

.mood-selector input[type="radio"]:checked + .mood-label {
    border-color: var(--myavana-coral);
    background: var(--myavana-light-coral);
    transform: scale(1.1);
}

.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 12px;
}

.product-category {
    display: flex;
    flex-direction: column;
}

.category-label {
    font-weight: 600;
    color: var(--myavana-blueberry);
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 6px;
}

.ai-toggle-wrapper {
    display: flex;
    justify-content: center;
}

.ai-toggle {
    display: flex;
    align-items: center;
    gap: 12px;
    cursor: pointer;
    padding: 16px;
    border-radius: 12px;
    background: var(--myavana-white);
    border: 2px solid var(--myavana-light-coral);
    transition: all 0.3s ease;
}

.ai-toggle:hover {
    background: var(--myavana-light-coral);
}

.toggle-slider {
    position: relative;
    width: 44px;
    height: 24px;
    background: var(--myavana-sand);
    border-radius: 24px;
    transition: background 0.3s ease;
}

.toggle-slider::after {
    content: '';
    position: absolute;
    top: 2px;
    left: 2px;
    width: 20px;
    height: 20px;
    background: var(--myavana-white);
    border-radius: 50%;
    transition: transform 0.3s ease;
}

.ai-toggle input:checked + .toggle-slider {
    background: var(--myavana-coral);
}

.ai-toggle input:checked + .toggle-slider::after {
    transform: translateX(20px);
}

.toggle-label {
    display: flex;
    flex-direction: column;
}

.toggle-label strong {
    color: var(--myavana-onyx);
    font-size: 14px;
}

.toggle-label small {
    color: var(--myavana-blueberry);
    font-size: 12px;
}

.form-actions {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    margin-top: 24px;
}

.loading-spinner {
    width: 16px;
    height: 16px;
    margin-right: 8px;
}

@keyframes spin {
    to { stroke-dashoffset: 0; }
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }

    .photo-upload-grid {
        grid-template-columns: 1fr;
    }

    .products-grid {
        grid-template-columns: 1fr;
    }

    .form-actions {
        flex-direction: column;
    }
}
</style>

<script>
// Initialize entry form when component loads
function initializeEntryForm() {
    const form = document.getElementById('hairEntryForm');
    if (!form) return;

    // Photo upload handling
    setupPhotoUploads();

    // Form submission
    form.addEventListener('submit', handleFormSubmit);

    // Cancel button
    document.getElementById('cancelEntry').addEventListener('click', () => {
        if (window.Myavana && Myavana.UI) {
            // Close modal using unified system
            const modal = form.closest('.myavana-modal');
            if (modal) {
                modal.classList.remove('active');
                document.body.classList.remove('myavana-modal-open');
            }
        }
    });
}

function setupPhotoUploads() {
    const uploadAreas = document.querySelectorAll('.upload-area');

    uploadAreas.forEach(area => {
        const input = area.querySelector('input[type="file"]');
        const placeholder = area.querySelector('.upload-placeholder');
        const preview = area.querySelector('.photo-preview');
        const previewImage = area.querySelector('.preview-image');
        const removeBtn = area.querySelector('.remove-photo');

        // Click to upload
        area.addEventListener('click', (e) => {
            if (e.target !== removeBtn) {
                input.click();
            }
        });

        // File selection
        input.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    previewImage.src = e.target.result;
                    placeholder.style.display = 'none';
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });

        // Remove photo
        removeBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            input.value = '';
            placeholder.style.display = 'flex';
            preview.style.display = 'none';
        });
    });
}

function handleFormSubmit(e) {
    e.preventDefault();

    const form = e.target;
    const submitBtn = form.querySelector('#saveEntry');
    const btnText = submitBtn.querySelector('.btn-text');
    const btnLoading = submitBtn.querySelector('.btn-loading');

    // Show loading state
    submitBtn.disabled = true;
    btnText.style.display = 'none';
    btnLoading.style.display = 'flex';

    const formData = new FormData(form);

    // Use unified API if available
    if (window.Myavana && Myavana.API) {
        Myavana.API.call('save_hair_entry', formData).then(response => {
            if (response.success) {
                Myavana.UI.notify('Entry saved successfully!', 'success');

                // Trigger data refresh
                Myavana.Events.trigger('data:changed:hair_entries');

                // Close modal
                const modal = form.closest('.myavana-modal');
                if (modal) {
                    modal.classList.remove('active');
                    document.body.classList.remove('myavana-modal-open');
                }
            } else {
                Myavana.UI.notify('Failed to save entry: ' + (response.data || 'Unknown error'), 'error');
            }
        }).catch(error => {
            Myavana.UI.notify('Failed to save entry', 'error');
        }).finally(() => {
            // Reset button state
            submitBtn.disabled = false;
            btnText.style.display = 'inline';
            btnLoading.style.display = 'none';
        });
    } else {
        // Fallback AJAX
        fetch(window.myavanaAjax.ajax_url, {
            method: 'POST',
            body: formData
        }).then(response => response.json()).then(data => {
            if (data.success) {
                alert('Entry saved successfully!');
            } else {
                alert('Failed to save entry: ' + (data.data || 'Unknown error'));
            }
        }).finally(() => {
            submitBtn.disabled = false;
            btnText.style.display = 'inline';
            btnLoading.style.display = 'none';
        });
    }
}

// Auto-initialize if the function exists (when loaded as component)
if (typeof initializeEntryForm === 'function') {
    document.addEventListener('DOMContentLoaded', initializeEntryForm);
}
</script>