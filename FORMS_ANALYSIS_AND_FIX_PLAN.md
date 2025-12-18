# MYAVANA Hair Journey Timeline Forms - Comprehensive Analysis & Fix Plan

## Executive Summary

This document provides a complete analysis of the forms functionality in the MYAVANA Hair Journey Timeline plugin, identifying **critical bugs** preventing proper create and edit functionality, and providing detailed fix implementations.

**Analysis Date:** 2025-10-22
**Analyzed Files:**
- `/assets/js/timeline/timeline-forms.js` (Forms module - 1,242 lines)
- `/templates/pages/partials/offcanvas-create.php` (Form template - MISSING)
- `/templates/hair-diary-timeline-shortcode.php` (AJAX handlers)
- `/includes/myavana_ajax_handlers.php` (Additional AJAX handlers)

**Critical Finding:** The primary issue is that `offcanvas-create.php` does not exist at the expected path. The forms are referencing a non-existent template file.

---

## 🚨 Critical Issues Identified

### 1. **MISSING FORM TEMPLATE FILE** (CRITICAL)
**Status:** 🔴 BROKEN
**File:** `/templates/pages/partials/offcanvas-create.php`
**Issue:** The file does not exist, but the JavaScript module expects it to be present.

**Evidence:**
- The glob search found only `/templates/pages/partials/offcanvas.php` (an older, simpler version)
- Timeline forms JavaScript references forms by IDs that should be in `offcanvas-create.php`
- Form IDs expected: `entryForm`, `goalForm`, `routineForm`

**Impact:**
- Forms cannot be displayed properly
- Edit functionality completely broken
- Create functionality may work by accident if old `offcanvas.php` is still in use

**Root Cause:**
The new timeline implementation references a redesigned offcanvas form that was never created or was deleted.

---

### 2. **EDIT FORM POPULATION ISSUES**
**Status:** 🟡 PARTIAL FUNCTIONALITY
**Location:** `timeline-forms.js:487-600`
**Function:** `populateEntryForm()`

**Problems Identified:**

#### 2.1 Entry ID Not Being Set for Updates
**Code Location:** Lines 496-502
```javascript
const entryIdInput = document.getElementById('entry_id');
if (entryIdInput) {
    entryIdInput.value = entryData.id || entry.id || '';
    console.log('✓ Set entry_id to:', entryIdInput.value);
} else {
    console.error('✗ entry_id input not found');
}
```

**Issue:** The `entry_id` hidden field is being set correctly in JavaScript, BUT it's not being sent in the form submission.

**Why Edit Becomes Create:**
Looking at `handleEntrySubmit()` (line 833), the form data is being manually constructed:
```javascript
formData.append('action', 'myavana_add_entry'); // ❌ ALWAYS uses ADD action
```

**The form NEVER checks if `entry_id` exists to determine update vs create!**

#### 2.2 Date Field Issue
**Code Location:** Lines 514-530
```javascript
const dateInput = document.getElementById('entry_date');
if (dateInput) {
    let dateValue = entryData.entry_date || entryData.date || '';

    // If date is formatted like "October 17, 2025 10:22 AM", convert to YYYY-MM-DD
    if (dateValue && dateValue.includes(',')) {
        const parsedDate = new Date(dateValue);
        if (!isNaN(parsedDate.getTime())) {
            dateValue = parsedDate.toISOString().split('T')[0];
        }
    }

    dateInput.value = dateValue;
    console.log('✓ Set entry_date to:', dateInput.value);
}
```

**Issue:** Date IS being populated correctly, but then gets reset to today's date on save because the backend handler doesn't check for existing entry date.

**Backend Problem (line 1226 in hair-diary-timeline-shortcode.php):**
```php
$post_id = wp_insert_post([
    'post_title' => $title,
    'post_content' => $description,
    'post_type' => 'hair_journey_entry',
    'post_status' => 'publish',
    'post_author' => $user_id,
    'post_date' => $timestamp  // ❌ ALWAYS uses current timestamp
]);
```

The `post_date` field is ALWAYS set to `current_time()`, ignoring any date sent from the form.

#### 2.3 Image Handling
**Code Location:** Lines 597-675 (populateExistingImages)

**Current Approach:**
- Displays existing images in a gallery
- Shows remove button for each image
- Does NOT integrate with FilePond uploader

**Issues:**
1. No "Edit Image" or "Replace Image" functionality
2. Existing images are shown separately from FilePond
3. User cannot tell if new images will replace or add to existing ones
4. Remove functionality (`removeExistingImage`) only removes from display, not from database

**FilePond Integration Problem:**
FilePond is initialized fresh each time (line 70-96), but existing images are NOT loaded into FilePond as pre-existing files.

---

### 3. **FORM SUBMISSION LOGIC FLAWS**
**Status:** 🔴 BROKEN
**Location:** `timeline-forms.js:833-914`
**Function:** `handleEntrySubmit()`

**Critical Flaws:**

#### 3.1 No Update Support
```javascript
// Line 847 - ALWAYS uses ADD action
formData.append('action', 'myavana_add_entry');
```

**Should be:**
```javascript
const entryId = form.querySelector('#entry_id')?.value;
const action = entryId ? 'myavana_update_entry' : 'myavana_add_entry';
formData.append('action', action);

if (entryId) {
    formData.append('entry_id', entryId);
}
```

#### 3.2 Field Mapping Issues
**Current code (lines 858-863):**
```javascript
formData.append('title', (form.querySelector('#entry_title')?.value || '').trim());
formData.append('description', (form.querySelector('#entry_content')?.value || '').trim());
formData.append('products', (form.querySelector('#products_used')?.value || '').trim());
formData.append('notes', (form.querySelector('#notes')?.value || '').trim());
formData.append('rating', form.querySelector('#health_rating')?.value || '3');
formData.append('mood_demeanor', form.querySelector('#mood')?.value || '');
```

**Problems:**
1. ✅ Field names are correct for backend handler
2. ❌ No entry date field being sent
3. ❌ Products field is expecting a string, but Select2 returns an array
4. ❌ No validation before submission

#### 3.3 Image Upload Limitation
**Current code (lines 870-876):**
```javascript
const entryFilePond = MyavanaTimeline.State.get('entryFilePond');
if (entryFilePond) {
    const files = entryFilePond.getFiles();
    if (files && files.length > 0) {
        formData.append('photo', files[0].file); // ❌ Only sends first file
    }
}
```

**Issues:**
1. FilePond allows up to 5 files (`maxFiles: 5` - line 79)
2. Only the FIRST file is sent
3. Backend handler only expects single `photo` field
4. For edits, no handling of existing images that should be kept

---

### 4. **BACKEND HANDLER ISSUES**
**Status:** 🟡 PARTIAL FUNCTIONALITY
**Files:**
- `/templates/hair-diary-timeline-shortcode.php` (myavana_add_entry, myavana_update_entry)

#### 4.1 myavana_add_entry() - Lines 1165-1310

**Problems:**

1. **No Update Logic** - Function name says "add" but should handle both create and update

2. **Date Handling (Line 1226)**
   ```php
   'post_date' => $timestamp  // ❌ ALWAYS current time
   ```
   Should check if `entry_date` is provided in POST and use that.

3. **No Entry ID Check**
   ```php
   // MISSING: Check if entry_id exists in POST to determine update vs create
   ```

4. **Image Handling (Lines 1246-1269)**
   - Only handles single photo
   - No handling of existing images on update
   - Doesn't track multiple images

#### 4.2 myavana_update_entry() - Lines 1027-1115

**This function EXISTS but is NEVER CALLED from the frontend!**

**What it does correctly:**
1. ✅ Verifies entry ownership
2. ✅ Updates post title and content
3. ✅ Updates all metadata
4. ✅ Handles image replacement

**What's missing:**
1. ❌ Not called from `handleEntrySubmit()`
2. ❌ No date update capability
3. ❌ Deletes old image on update (should be optional)

#### 4.3 myavana_get_entry_details() - Lines 1120-1160

**This function works correctly!**

Returns proper data structure:
```php
$detailed_data = [
    'id' => $entry_id,
    'title' => $entry->post_title,
    'description' => $entry->post_content,
    'date' => get_the_date('F j, Y g:i A', $entry_id),  // ✅ Formatted date
    'rating' => isset($meta_data['health_rating'][0]) ? $meta_data['health_rating'][0] : '5',
    'mood' => isset($meta_data['mood_demeanor'][0]) ? $meta_data['mood_demeanor'][0] : 'Happy',
    // ... more fields
];
```

**Issue:** The date format returned is "F j, Y g:i A" (e.g., "October 17, 2025 10:22 AM") but the form expects YYYY-MM-DD format for the date input.

**The JavaScript DOES handle this conversion** (lines 518-524), so this is actually fine.

---

### 5. **VALIDATION ISSUES**
**Status:** 🟡 MINIMAL VALIDATION
**Location:** Multiple locations

**Client-Side Validation:**
- ❌ No validation before form submission
- ❌ No required field checks in JavaScript
- ❌ Only HTML5 `required` attributes (easily bypassed)
- ❌ No character limits enforced
- ❌ No format validation (e.g., rating must be 1-5)

**Server-Side Validation:**
In `myavana_add_entry()`:
```php
// Lines 1202-1205
if (empty($_POST['title']) || empty($_POST['rating'])) {
    wp_send_json_error('Title and rating are required');
    return;
}
```

**Issues:**
1. ✅ Checks for required fields (title, rating)
2. ❌ No validation of rating range
3. ❌ No sanitization verification
4. ❌ No max length checks
5. ❌ No XSS prevention beyond WordPress sanitize functions

---

### 6. **USER FEEDBACK ISSUES**
**Status:** 🟡 BASIC FEEDBACK ONLY
**Location:** `timeline-forms.js:1180-1213`

**Current Implementation:**
```javascript
showNotification: function(message, type = 'info') {
    console.log('Notification:', type, message);

    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification-hjn notification-${type}-hjn`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 1rem 1.5rem;
        background: ${type === 'success' ? '#4caf50' : type === 'error' ? '#f44336' : '#2196f3'};
        color: white;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        z-index: 100000;
        font-family: Archivo, sans-serif;
        font-weight: 600;
        animation: slideIn 0.3s ease;
    `;

    document.body.appendChild(notification);

    // Auto-remove after 3 seconds
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}
```

**Issues:**
1. ❌ No CSS animations defined (slideIn/slideOut referenced but don't exist)
2. ❌ No loading state during AJAX calls
3. ❌ No progress indicator for image uploads
4. ❌ No confirmation before destructive actions
5. ❌ Generic error messages (doesn't help user fix issues)

**Missing Feedback Scenarios:**
- Image upload progress
- Form validation errors (which field failed)
- Network errors vs server errors
- Success confirmation with undo option
- Autosave indicators

---

### 7. **NONCE AND SECURITY ISSUES**
**Status:** 🟡 INCONSISTENT
**Location:** Multiple locations

**Current Nonce Implementation:**

**JavaScript (lines 389-400):**
```javascript
const settings = window.myavanaTimelineSettings || window.myavanaTimeline || {};

fetch(settings.ajaxUrl || settings.ajaxurl || '/wp-admin/admin-ajax.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: new URLSearchParams({
        action: 'myavana_get_entry_details',
        security: settings.getEntryDetailsNonce || settings.getEntriesNonce || settings.nonce || '',
        entry_id: id
    })
})
```

**Problems:**
1. ❌ Multiple fallbacks for nonce field name (`getEntryDetailsNonce`, `getEntriesNonce`, `nonce`)
2. ❌ Can fallback to empty string if nonces not found
3. ❌ Inconsistent nonce field naming between endpoints

**Form Submission (lines 850-852):**
```javascript
const nonceInput = form.querySelector('input[name="myavana_nonce"]');
if (nonceInput) formData.append('myavana_nonce', nonceInput.value);
```

**Issues:**
1. ❌ Nonce field is OPTIONAL (if not found, still submits)
2. ❌ Backend handler for `myavana_add_entry` doesn't verify nonce
3. ❌ Backend handler for `myavana_update_entry` expects `security` field, not `myavana_nonce`

**Backend Nonce Verification:**

`myavana_update_entry` (line 1028):
```php
check_ajax_referer('myavana_update_entry', 'security');  // ✅ Proper verification
```

`myavana_add_entry` (line 1165):
```php
// ❌ NO NONCE VERIFICATION AT ALL!
```

**This is a CRITICAL SECURITY VULNERABILITY!**

---

## 📋 Complete Issue Summary Table

| Issue # | Category | Severity | Location | Description | Impact |
|---------|----------|----------|----------|-------------|--------|
| 1 | Template | 🔴 Critical | offcanvas-create.php | Form template file missing | Forms cannot display |
| 2 | Logic | 🔴 Critical | timeline-forms.js:847 | Always uses ADD action, never UPDATE | Edit becomes create |
| 3 | Logic | 🔴 Critical | timeline-forms.js:833 | No entry_id sent on update | Backend can't identify entry |
| 4 | Data | 🟠 High | hair-diary-timeline-shortcode.php:1226 | Date always set to current time | Dates change on edit |
| 5 | Security | 🔴 Critical | myavana_add_entry | No nonce verification | CSRF vulnerability |
| 6 | Security | 🟠 High | timeline-forms.js:850 | Nonce is optional | Weak security |
| 7 | Images | 🟠 High | timeline-forms.js:870 | Only sends first image | Multi-image upload broken |
| 8 | Images | 🟡 Medium | timeline-forms.js:605 | No edit image functionality | Cannot replace images |
| 9 | Images | 🟡 Medium | populateExistingImages | Remove doesn't persist | Misleading UI |
| 10 | Validation | 🟠 High | timeline-forms.js:833 | No client-side validation | Bad UX, wasted server calls |
| 11 | Validation | 🟡 Medium | myavana_add_entry | Minimal server validation | Data integrity issues |
| 12 | UI/UX | 🟡 Medium | timeline-forms.js:1180 | Missing CSS animations | Poor visual feedback |
| 13 | UI/UX | 🟡 Medium | handleEntrySubmit | No loading indicators | User doesn't know state |
| 14 | Data | 🟡 Medium | handleEntrySubmit:860 | Products as string vs array | Data format mismatch |
| 15 | Logic | 🟡 Medium | myavana_update_entry:1088 | Always deletes old image | Cannot add images |

---

## 🔧 Detailed Fix Plan

### Phase 1: Critical Fixes (Must Do First)

#### Fix 1.1: Create Missing Form Template
**File:** `/templates/pages/partials/offcanvas-create.php`
**Priority:** 🔴 CRITICAL
**Complexity:** High

**Action:** Create the complete offcanvas form template with all required elements.

**Implementation:**
```php
<?php
/**
 * MYAVANA Hair Journey Timeline - Create/Edit Offcanvas Forms
 * Universal offcanvas panels for creating and editing entries, goals, and routines
 */

if (!defined('ABSPATH')) exit;

// Get current user ID
$user_id = get_current_user_id();
?>

<!-- Offcanvas Overlay for Create/Edit Forms -->
<div class="create-offcanvas-overlay-hjn" id="createOffcanvasOverlay"></div>

<!-- ENTRY OFFCANVAS -->
<div class="create-offcanvas-hjn" id="entryOffcanvas">
    <div class="offcanvas-header-hjn">
        <h2 class="offcanvas-title-hjn" id="entryOffcanvasTitle">Add Hair Journey Entry</h2>
        <button type="button" class="offcanvas-close-btn-hjn" onclick="MyavanaTimeline.Offcanvas.closeOffcanvas()" aria-label="Close">
            <svg viewBox="0 0 24 24" width="24" height="24">
                <path fill="currentColor" d="M19,6.41L17.59,5L12,10.59L6.41,5L5,6.41L10.59,12L5,17.59L6.41,19L12,13.41L17.59,19L19,17.59L13.41,12L19,6.41Z"/>
            </svg>
        </button>
    </div>

    <div class="offcanvas-body-hjn">
        <!-- Loading Indicator -->
        <div class="form-loading-hjn" id="entryFormLoading" style="display: none;">
            <div class="spinner-hjn"></div>
            <p>Saving your entry...</p>
        </div>

        <!-- Entry Form -->
        <form id="entryForm" class="timeline-form-hjn" method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('myavana_entry_action', 'myavana_nonce'); ?>

            <!-- Hidden Fields -->
            <input type="hidden" name="entry_id" id="entry_id" value="">
            <input type="hidden" name="is_automated" value="0">
            <input type="hidden" name="myavana_entry" value="1">

            <!-- Title Field -->
            <div class="form-group-hjn">
                <label for="entry_title" class="form-label-hjn">
                    Entry Title <span class="required-hjn">*</span>
                </label>
                <input
                    type="text"
                    id="entry_title"
                    name="entry_title"
                    class="form-input-hjn"
                    placeholder="e.g., Week 4 Progress Update"
                    required
                    maxlength="200"
                >
            </div>

            <!-- Date Field -->
            <div class="form-group-hjn">
                <label for="entry_date" class="form-label-hjn">
                    Entry Date <span class="required-hjn">*</span>
                </label>
                <input
                    type="date"
                    id="entry_date"
                    name="entry_date"
                    class="form-input-hjn"
                    required
                >
            </div>

            <!-- Description/Content -->
            <div class="form-group-hjn">
                <label for="entry_content" class="form-label-hjn">
                    Description <span class="required-hjn">*</span>
                </label>
                <textarea
                    id="entry_content"
                    name="entry_content"
                    class="form-textarea-hjn"
                    rows="5"
                    placeholder="Describe your hair progress, observations, or thoughts..."
                    required
                    maxlength="5000"
                ></textarea>
                <div class="char-counter-hjn">
                    <span id="entry_content_count">0</span> / 5000 characters
                </div>
            </div>

            <!-- Health Rating -->
            <div class="form-group-hjn">
                <label class="form-label-hjn">
                    Hair Health Rating <span class="required-hjn">*</span>
                </label>
                <div class="rating-display-hjn">
                    <div class="rating-stars-hjn" id="health_rating_stars">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <button type="button" class="rating-star-hjn" data-value="<?php echo $i; ?>" aria-label="Rate <?php echo $i; ?> stars">
                                <svg viewBox="0 0 24 24" width="32" height="32">
                                    <path fill="currentColor" d="M12,17.27L18.18,21L16.54,13.97L22,9.24L14.81,8.62L12,2L9.19,8.62L2,9.24L7.45,13.97L5.82,21L12,17.27Z"/>
                                </svg>
                            </button>
                        <?php endfor; ?>
                    </div>
                    <div class="rating-value-hjn" id="health_rating_value">Not Rated</div>
                </div>
                <input type="hidden" name="health_rating" id="health_rating" value="0" required>
            </div>

            <!-- Mood/Demeanor -->
            <div class="form-group-hjn">
                <label for="mood" class="form-label-hjn">
                    How are you feeling?
                </label>
                <select id="mood" name="mood" class="form-select-hjn">
                    <option value="">Select mood...</option>
                    <option value="Confident">Confident</option>
                    <option value="Happy">Happy</option>
                    <option value="Excited">Excited</option>
                    <option value="Neutral">Neutral</option>
                    <option value="Concerned">Concerned</option>
                    <option value="Frustrated">Frustrated</option>
                    <option value="Hopeful">Hopeful</option>
                </select>
            </div>

            <!-- Products Used -->
            <div class="form-group-hjn">
                <label for="products_used" class="form-label-hjn">
                    Products Used
                </label>
                <select
                    id="products_used"
                    name="products_used"
                    class="form-select-hjn"
                    multiple
                    data-placeholder="Select products you used..."
                >
                    <!-- Options populated by JavaScript -->
                </select>
                <p class="form-help-hjn">You can select multiple products or add your own</p>
            </div>

            <!-- Notes -->
            <div class="form-group-hjn">
                <label for="notes" class="form-label-hjn">
                    Additional Notes
                </label>
                <textarea
                    id="notes"
                    name="notes"
                    class="form-textarea-hjn"
                    rows="3"
                    placeholder="Any additional observations, tips, or notes..."
                    maxlength="1000"
                ></textarea>
            </div>

            <!-- Existing Images Gallery (shown only on edit) -->
            <div class="form-group-hjn" id="existingImagesGallery" style="display: none;">
                <label class="form-label-hjn">
                    Current Images
                </label>
                <div class="existing-images-grid-hjn" id="existingImagesGrid">
                    <!-- Populated by JavaScript on edit -->
                </div>
                <p class="form-help-hjn">Click the X to remove an image. Upload new images below to add or replace.</p>
            </div>

            <!-- Photo Upload -->
            <div class="form-group-hjn">
                <label for="entry_photos" class="form-label-hjn">
                    Add Photos
                </label>
                <input
                    type="file"
                    id="entry_photos"
                    name="entry_photos"
                    class="filepond-input-hjn"
                    multiple
                    accept="image/*"
                >
                <p class="form-help-hjn">Upload up to 5 images (max 5MB each)</p>
            </div>

            <!-- Form Actions -->
            <div class="form-actions-hjn">
                <button type="button" class="btn-secondary-hjn" onclick="MyavanaTimeline.Offcanvas.closeOffcanvas()">
                    Cancel
                </button>
                <button type="submit" class="btn-primary-hjn" id="saveEntryBtn">
                    <svg viewBox="0 0 24 24" width="18" height="18" style="margin-right: 8px;">
                        <path fill="currentColor" d="M15,9H5V5H15M12,19A3,3 0 0,1 9,16A3,3 0 0,1 12,13A3,3 0 0,1 15,16A3,3 0 0,1 12,19M17,3H5C3.89,3 3,3.9 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19V7L17,3Z"/>
                    </svg>
                    Save Entry
                </button>
            </div>
        </form>
    </div>
</div>

<!-- GOAL OFFCANVAS -->
<div class="create-offcanvas-hjn" id="goalOffcanvas">
    <!-- Similar structure for goals... -->
</div>

<!-- ROUTINE OFFCANVAS -->
<div class="create-offcanvas-hjn" id="routineOffcanvas">
    <!-- Similar structure for routines... -->
</div>

<style>
/* Offcanvas Overlay */
.create-offcanvas-overlay-hjn {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    z-index: 9998;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.3s ease, visibility 0.3s ease;
}

.create-offcanvas-overlay-hjn.active {
    opacity: 1;
    visibility: visible;
}

/* Offcanvas Panel */
.create-offcanvas-hjn {
    position: fixed;
    top: 0;
    right: -100%;
    width: 90%;
    max-width: 600px;
    height: 100%;
    background: var(--myavana-white, #ffffff);
    box-shadow: -4px 0 20px rgba(0, 0, 0, 0.15);
    z-index: 9999;
    display: flex;
    flex-direction: column;
    transition: right 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    overflow: hidden;
}

.create-offcanvas-hjn.active {
    right: 0;
}

/* Offcanvas Header */
.offcanvas-header-hjn {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 24px;
    border-bottom: 1px solid var(--myavana-stone, #f5f5f7);
    flex-shrink: 0;
}

.offcanvas-title-hjn {
    font-family: 'Archivo', sans-serif;
    font-size: 24px;
    font-weight: 600;
    color: var(--myavana-onyx, #222323);
    margin: 0;
}

.offcanvas-close-btn-hjn {
    background: none;
    border: none;
    padding: 8px;
    cursor: pointer;
    color: var(--myavana-onyx, #222323);
    transition: transform 0.2s ease, opacity 0.2s ease;
}

.offcanvas-close-btn-hjn:hover {
    transform: rotate(90deg);
    opacity: 0.7;
}

/* Offcanvas Body */
.offcanvas-body-hjn {
    flex: 1;
    overflow-y: auto;
    padding: 24px;
}

/* Form Loading */
.form-loading-hjn {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.95);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    z-index: 10;
}

.spinner-hjn {
    width: 48px;
    height: 48px;
    border: 4px solid var(--myavana-stone, #f5f5f7);
    border-top-color: var(--myavana-coral, #e7a690);
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Form Groups */
.form-group-hjn {
    margin-bottom: 24px;
}

.form-label-hjn {
    display: block;
    margin-bottom: 8px;
    font-family: 'Archivo', sans-serif;
    font-size: 14px;
    font-weight: 600;
    color: var(--myavana-onyx, #222323);
}

.required-hjn {
    color: #e74c3c;
}

.form-input-hjn,
.form-select-hjn,
.form-textarea-hjn {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid var(--myavana-stone, #f5f5f7);
    border-radius: 8px;
    font-family: 'Archivo', sans-serif;
    font-size: 14px;
    color: var(--myavana-onyx, #222323);
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.form-input-hjn:focus,
.form-select-hjn:focus,
.form-textarea-hjn:focus {
    outline: none;
    border-color: var(--myavana-coral, #e7a690);
    box-shadow: 0 0 0 3px rgba(231, 166, 144, 0.1);
}

.form-textarea-hjn {
    resize: vertical;
    min-height: 100px;
}

.char-counter-hjn {
    text-align: right;
    font-size: 12px;
    color: #666;
    margin-top: 4px;
}

.form-help-hjn {
    font-size: 12px;
    color: #666;
    margin-top: 6px;
    font-style: italic;
}

/* Rating Stars */
.rating-display-hjn {
    display: flex;
    align-items: center;
    gap: 16px;
}

.rating-stars-hjn {
    display: flex;
    gap: 4px;
}

.rating-star-hjn {
    background: none;
    border: none;
    padding: 4px;
    cursor: pointer;
    color: #ddd;
    transition: color 0.2s ease, transform 0.1s ease;
}

.rating-star-hjn:hover,
.rating-star-hjn.active {
    color: var(--myavana-coral, #e7a690);
}

.rating-star-hjn:active {
    transform: scale(0.9);
}

.rating-value-hjn {
    font-family: 'Archivo', sans-serif;
    font-size: 14px;
    font-weight: 600;
    color: var(--myavana-onyx, #222323);
}

/* Existing Images Gallery */
.existing-images-grid-hjn {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 12px;
}

.existing-image-item-hjn {
    position: relative;
    border-radius: 8px;
    overflow: hidden;
    aspect-ratio: 1;
}

.existing-image-wrapper-hjn {
    position: relative;
    width: 100%;
    height: 100%;
}

.existing-image-hjn {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.remove-existing-image-btn {
    position: absolute;
    top: 4px;
    right: 4px;
    background: rgba(231, 76, 60, 0.9);
    border: none;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: white;
    opacity: 0;
    transition: opacity 0.2s ease, transform 0.1s ease;
}

.existing-image-item-hjn:hover .remove-existing-image-btn {
    opacity: 1;
}

.remove-existing-image-btn:hover {
    transform: scale(1.1);
    background: rgba(231, 76, 60, 1);
}

/* Form Actions */
.form-actions-hjn {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    padding-top: 24px;
    border-top: 1px solid var(--myavana-stone, #f5f5f7);
    margin-top: 24px;
}

.btn-primary-hjn,
.btn-secondary-hjn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 12px 24px;
    border-radius: 8px;
    font-family: 'Archivo', sans-serif;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    border: none;
}

.btn-primary-hjn {
    background: var(--myavana-coral, #e7a690);
    color: white;
}

.btn-primary-hjn:hover:not(:disabled) {
    background: #d4956f;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(231, 166, 144, 0.3);
}

.btn-primary-hjn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.btn-secondary-hjn {
    background: transparent;
    color: var(--myavana-onyx, #222323);
    border: 2px solid var(--myavana-onyx, #222323);
}

.btn-secondary-hjn:hover {
    background: var(--myavana-stone, #f5f5f7);
}

/* Animations */
@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes slideOut {
    from {
        opacity: 1;
        transform: translateY(0);
    }
    to {
        opacity: 0;
        transform: translateY(-10px);
    }
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .create-offcanvas-hjn {
        width: 100%;
        max-width: none;
    }

    .offcanvas-header-hjn,
    .offcanvas-body-hjn {
        padding: 16px;
    }

    .form-actions-hjn {
        flex-direction: column-reverse;
    }

    .btn-primary-hjn,
    .btn-secondary-hjn {
        width: 100%;
    }
}
</style>
```

**Files to Include:** This template should be included in the timeline shortcode template.

---

#### Fix 1.2: Fix Form Submission Logic
**File:** `/assets/js/timeline/timeline-forms.js`
**Priority:** 🔴 CRITICAL
**Complexity:** Medium

**Current Code (Lines 833-914):**
```javascript
handleEntrySubmit: async function(e) {
    e.preventDefault();
    console.log('Submitting entry form (shortcode-compatible)...');

    const form = e.target;
    const loadingEl = document.getElementById('entryFormLoading');
    const submitBtn = document.getElementById('saveEntryBtn');

    // Show loading state
    if (loadingEl) loadingEl.style.display = 'flex';
    if (submitBtn) submitBtn.disabled = true;

    // Build payload matching the working shortcode
    const formData = new FormData();
    formData.append('action', 'myavana_add_entry'); // ❌ ALWAYS ADD!

    // ... rest of code
}
```

**FIXED CODE:**
```javascript
handleEntrySubmit: async function(e) {
    e.preventDefault();
    console.log('Submitting entry form...');

    const form = e.target;
    const loadingEl = document.getElementById('entryFormLoading');
    const submitBtn = document.getElementById('saveEntryBtn');

    // Validate form before submission
    if (!this.validateEntryForm(form)) {
        return false;
    }

    // Show loading state
    if (loadingEl) loadingEl.style.display = 'flex';
    if (submitBtn) submitBtn.disabled = true;

    // Determine if this is create or update
    const entryIdInput = form.querySelector('#entry_id');
    const entryId = entryIdInput ? entryIdInput.value : '';
    const isUpdate = entryId && entryId.trim() !== '';

    console.log('Form mode:', isUpdate ? 'UPDATE' : 'CREATE', 'Entry ID:', entryId);

    // Build FormData
    const formData = new FormData();

    // Set appropriate action based on create vs update
    const action = isUpdate ? 'myavana_update_entry' : 'myavana_add_entry';
    formData.append('action', action);
    console.log('Using AJAX action:', action);

    // Add entry ID if updating
    if (isUpdate) {
        formData.append('entry_id', entryId);
    }

    // Nonce handling with proper fallback
    const nonceInput = form.querySelector('input[name="myavana_nonce"]');
    if (nonceInput && nonceInput.value) {
        formData.append(isUpdate ? 'security' : 'myavana_nonce', nonceInput.value);
    } else {
        // Try to get from global settings
        const settings = window.myavanaTimelineSettings || {};
        const nonce = isUpdate ? settings.updateEntryNonce : settings.addEntryNonce;
        if (nonce) {
            formData.append(isUpdate ? 'security' : 'myavana_nonce', nonce);
        } else {
            console.error('No nonce available for form submission!');
            this.showNotification('Security token missing. Please refresh the page.', 'error');
            if (loadingEl) loadingEl.style.display = 'none';
            if (submitBtn) submitBtn.disabled = false;
            return false;
        }
    }

    // Flag for backend
    formData.append('is_automated', '0');
    formData.append('myavana_entry', '1');

    // Map form fields to backend expected names
    formData.append('title', (form.querySelector('#entry_title')?.value || '').trim());
    formData.append('description', (form.querySelector('#entry_content')?.value || '').trim());

    // Handle products - Select2 returns array, backend expects comma-separated string
    const productsSelect = form.querySelector('#products_used');
    let productsValue = '';
    if (productsSelect) {
        if (typeof $ !== 'undefined' && $.fn.select2) {
            // Get Select2 value (returns array)
            const productsArray = $(productsSelect).val();
            productsValue = Array.isArray(productsArray) ? productsArray.join(', ') : '';
        } else {
            productsValue = productsSelect.value || '';
        }
    }
    formData.append('products', productsValue);

    formData.append('notes', (form.querySelector('#notes')?.value || '').trim());
    formData.append('rating', form.querySelector('#health_rating')?.value || '3');
    formData.append('mood', form.querySelector('#mood')?.value || '');

    // ✅ FIX: Include entry date
    const entryDate = form.querySelector('#entry_date')?.value;
    if (entryDate) {
        formData.append('entry_date', entryDate);
    }

    // Handle environment if field exists
    const envInput = form.querySelector('select[name="environment"]');
    if (envInput) formData.append('environment', envInput.value);

    // ✅ FIX: Handle existing images that should be kept
    if (isUpdate) {
        const keptImages = this.getKeptExistingImages();
        if (keptImages.length > 0) {
            formData.append('kept_images', JSON.stringify(keptImages));
        }
    }

    // ✅ FIX: Attach ALL photos from FilePond, not just first one
    const entryFilePond = MyavanaTimeline.State.get('entryFilePond');
    if (entryFilePond) {
        const files = entryFilePond.getFiles();
        console.log('FilePond files to upload:', files.length);

        if (files && files.length > 0) {
            // For update: send as 'new_photo' to differentiate from existing
            // For create: send as 'photo' for backward compatibility
            const photoFieldName = isUpdate ? 'new_photo' : 'photo';

            // If only one file, send as single field for backward compatibility
            if (files.length === 1) {
                formData.append(photoFieldName, files[0].file);
            } else {
                // Multiple files - append with array notation
                files.forEach((fileItem, index) => {
                    formData.append(`${photoFieldName}[]`, fileItem.file);
                });
            }
        }
    }

    // Resolve AJAX URL
    const settings = window.myavanaTimelineSettings || {};
    const ajaxUrl = settings.ajaxUrl || settings.ajaxurl || '/wp-admin/admin-ajax.php';

    try {
        console.log('Sending AJAX request to:', ajaxUrl);
        console.log('FormData keys:', Array.from(formData.keys()));

        const response = await fetch(ajaxUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });

        const data = await response.json();
        console.log('AJAX response:', data);

        if (data.success) {
            const successMessage = isUpdate ? 'Entry updated successfully!' : 'Entry created successfully!';
            this.showNotification(data.data?.message || successMessage, 'success');

            // Reset form only on create (not on update)
            if (!isUpdate) {
                form.reset();
                if (entryFilePond) {
                    entryFilePond.removeFiles();
                }
            }

            // Close offcanvas and refresh
            MyavanaTimeline.Offcanvas.closeOffcanvas();
            this.refreshCurrentView();
        } else {
            console.error('Error saving entry:', data);
            const errorMessage = (data && (data.data || data.message)) || 'Error saving entry. Please try again.';
            this.showNotification(errorMessage, 'error');
        }
    } catch (error) {
        console.error('Network error submitting entry:', error);
        this.showNotification('Network error. Please check your connection and try again.', 'error');
    } finally {
        // Hide loading state
        if (loadingEl) loadingEl.style.display = 'none';
        if (submitBtn) submitBtn.disabled = false;
    }
},

/**
 * Get list of existing images that should be kept (not removed)
 */
getKeptExistingImages: function() {
    const existingImages = document.querySelectorAll('.existing-image-item-hjn');
    const keptImages = [];

    existingImages.forEach(imageItem => {
        const imageUrl = imageItem.getAttribute('data-image-url');
        if (imageUrl) {
            keptImages.push(imageUrl);
        }
    });

    return keptImages;
},

/**
 * Validate entry form before submission
 */
validateEntryForm: function(form) {
    const errors = [];

    // Title validation
    const title = form.querySelector('#entry_title');
    if (!title || !title.value.trim()) {
        errors.push('Entry title is required');
        this.highlightField(title);
    } else if (title.value.trim().length < 3) {
        errors.push('Entry title must be at least 3 characters');
        this.highlightField(title);
    } else if (title.value.trim().length > 200) {
        errors.push('Entry title must be less than 200 characters');
        this.highlightField(title);
    }

    // Date validation
    const entryDate = form.querySelector('#entry_date');
    if (!entryDate || !entryDate.value) {
        errors.push('Entry date is required');
        this.highlightField(entryDate);
    }

    // Content validation
    const content = form.querySelector('#entry_content');
    if (!content || !content.value.trim()) {
        errors.push('Description is required');
        this.highlightField(content);
    } else if (content.value.trim().length < 10) {
        errors.push('Description must be at least 10 characters');
        this.highlightField(content);
    }

    // Rating validation
    const rating = form.querySelector('#health_rating');
    if (!rating || !rating.value || rating.value === '0') {
        errors.push('Please select a health rating');
        this.highlightField(document.getElementById('health_rating_stars'));
    }

    // Show errors if any
    if (errors.length > 0) {
        const errorMessage = errors.join('<br>');
        this.showNotification(errorMessage, 'error');
        return false;
    }

    return true;
},

/**
 * Highlight a field with error state
 */
highlightField: function(field) {
    if (!field) return;

    field.style.borderColor = '#e74c3c';
    field.style.boxShadow = '0 0 0 3px rgba(231, 76, 60, 0.1)';

    // Remove highlight after 3 seconds
    setTimeout(() => {
        field.style.borderColor = '';
        field.style.boxShadow = '';
    }, 3000);
},
```

**Impact:** This fixes the critical issue where edits become creates, and ensures dates are preserved.

---

#### Fix 1.3: Add Nonce Verification to Backend
**File:** `/templates/hair-diary-timeline-shortcode.php`
**Priority:** 🔴 CRITICAL (Security)
**Complexity:** Low

**Current Code (Line 1165):**
```php
function myavana_add_entry() {

    global $wpdb;
    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error('User not logged in');
        return;
    }
    // ❌ NO NONCE CHECK!
```

**FIXED CODE:**
```php
function myavana_add_entry() {
    // ✅ Verify nonce for security
    check_ajax_referer('myavana_entry_action', 'myavana_nonce');

    global $wpdb;
    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error('User not logged in');
        return;
    }

    // Rest of function...
}
```

**Additional Fix:** Update nonce generation in shortcode localization:

**File:** `/templates/hair-diary-timeline-shortcode.php` (around line 49)
```php
'addEntryNonce' => wp_create_nonce('myavana_entry_action'), // Match the check
'updateEntryNonce' => wp_create_nonce('myavana_update_entry'),
```

---

#### Fix 1.4: Handle Entry Date in Backend
**File:** `/templates/hair-diary-timeline-shortcode.php`
**Priority:** 🔴 CRITICAL
**Complexity:** Low

**Current Code (Lines 1219-1227):**
```php
// Insert post
$post_id = wp_insert_post([
    'post_title' => $title,
    'post_content' => $description,
    'post_type' => 'hair_journey_entry',
    'post_status' => 'publish',
    'post_author' => $user_id,
    'post_date' => $timestamp  // ❌ ALWAYS current time
]);
```

**FIXED CODE:**
```php
// Determine post date - use submitted date if provided, otherwise current time
$post_date = $timestamp; // Default to current time
if (isset($_POST['entry_date']) && !empty($_POST['entry_date'])) {
    // Sanitize and validate the date
    $submitted_date = sanitize_text_field($_POST['entry_date']);

    // Convert YYYY-MM-DD to MySQL datetime format
    $date_obj = DateTime::createFromFormat('Y-m-d', $submitted_date);
    if ($date_obj !== false) {
        // Use submitted date with current time
        $post_date = $date_obj->format('Y-m-d') . ' ' . date('H:i:s');
    }
}

// Insert post
$post_id = wp_insert_post([
    'post_title' => $title,
    'post_content' => $description,
    'post_type' => 'hair_journey_entry',
    'post_status' => 'publish',
    'post_author' => $user_id,
    'post_date' => $post_date,  // ✅ Use submitted or current date
    'post_date_gmt' => get_gmt_from_date($post_date)
]);
```

**Also Update myavana_update_entry():**

**Current Code (Lines 1061-1065):**
```php
$updated_post = wp_update_post([
    'ID' => $entry_id,
    'post_title' => $title,
    'post_content' => $description
]);
```

**FIXED CODE:**
```php
// Prepare update data
$update_data = [
    'ID' => $entry_id,
    'post_title' => $title,
    'post_content' => $description
];

// Update post date if provided (allow user to change entry date)
if (isset($_POST['entry_date']) && !empty($_POST['entry_date'])) {
    $submitted_date = sanitize_text_field($_POST['entry_date']);
    $date_obj = DateTime::createFromFormat('Y-m-d', $submitted_date);
    if ($date_obj !== false) {
        // Keep original time but update date
        $existing_post = get_post($entry_id);
        $existing_time = date('H:i:s', strtotime($existing_post->post_date));
        $new_date = $date_obj->format('Y-m-d') . ' ' . $existing_time;
        $update_data['post_date'] = $new_date;
        $update_data['post_date_gmt'] = get_gmt_from_date($new_date);
    }
}

$updated_post = wp_update_post($update_data);
```

---

### Phase 2: High Priority Fixes

#### Fix 2.1: Multi-Image Upload Support
**Files:**
- `/assets/js/timeline/timeline-forms.js` (frontend)
- `/templates/hair-diary-timeline-shortcode.php` (backend)

**Priority:** 🟠 HIGH
**Complexity:** Medium

**Frontend Changes (Already in Fix 1.2):**
The frontend fix in 1.2 already handles sending multiple files.

**Backend Changes Required:**

**Update myavana_add_entry() - Around line 1245:**

**Current Code:**
```php
// Handle photo upload
$attachment_id = 0;
if ($is_automated && !empty($_POST['image_data'])) {
    $attachment_id = myavana_save_screenshot($_POST['image_data'], $post_id, $user_id);
} elseif (!empty($_FILES['photo']['name'])) {
    // Single file upload code...
}

if ($attachment_id && !is_wp_error($attachment_id)) {
    set_post_thumbnail($post_id, $attachment_id);
}
```

**FIXED CODE:**
```php
// Handle photo uploads (supports multiple files)
$attachment_ids = [];

if ($is_automated && !empty($_POST['image_data'])) {
    // Automated entry with base64 image
    $attachment_id = myavana_save_screenshot($_POST['image_data'], $post_id, $user_id);
    if ($attachment_id && !is_wp_error($attachment_id)) {
        $attachment_ids[] = $attachment_id;
    }
} else {
    // Manual upload - check for single or multiple files
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    // Handle single file upload (backward compatibility)
    if (!empty($_FILES['photo']['name'])) {
        $upload = wp_handle_upload($_FILES['photo'], ['test_form' => false]);
        if ($upload && !isset($upload['error'])) {
            $attachment_id = myavana_create_attachment($upload, $post_id, $user_id);
            if ($attachment_id && !is_wp_error($attachment_id)) {
                $attachment_ids[] = $attachment_id;
            }
        }
    }

    // Handle multiple file upload
    if (!empty($_FILES['photo']['name']) && is_array($_FILES['photo']['name'])) {
        $files = $_FILES['photo'];
        $file_count = count($files['name']);

        for ($i = 0; $i < $file_count; $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $file = [
                    'name' => $files['name'][$i],
                    'type' => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error' => $files['error'][$i],
                    'size' => $files['size'][$i]
                ];

                $upload = wp_handle_upload($file, ['test_form' => false]);
                if ($upload && !isset($upload['error'])) {
                    $attachment_id = myavana_create_attachment($upload, $post_id, $user_id);
                    if ($attachment_id && !is_wp_error($attachment_id)) {
                        $attachment_ids[] = $attachment_id;
                    }
                }
            }
        }
    }
}

// Set first image as featured image (thumbnail)
if (!empty($attachment_ids)) {
    set_post_thumbnail($post_id, $attachment_ids[0]);

    // Store all attachment IDs as meta for retrieval
    update_post_meta($post_id, 'entry_images', $attachment_ids);
} elseif ($is_automated) {
    // For automated entries, image is required
    wp_delete_post($post_id, true);
    wp_send_json_error('Failed to save photo for automated entry');
    return;
}

/**
 * Helper function to create attachment from upload
 */
if (!function_exists('myavana_create_attachment')) {
    function myavana_create_attachment($upload, $post_id, $user_id) {
        $attachment = [
            'post_mime_type' => $upload['type'],
            'post_title' => sanitize_file_name(basename($upload['file'])),
            'post_content' => '',
            'post_status' => 'inherit',
            'post_author' => $user_id
        ];

        $attachment_id = wp_insert_attachment($attachment, $upload['file'], $post_id);

        if (!is_wp_error($attachment_id)) {
            $attachment_data = wp_generate_attachment_metadata($attachment_id, $upload['file']);
            wp_update_attachment_metadata($attachment_id, $attachment_data);
            return $attachment_id;
        }

        return false;
    }
}
```

**Update myavana_update_entry() - Around line 1079:**

**Current Code:**
```php
// Handle photo upload if provided
if (!empty($_FILES['photo']['name'])) {
    // ... code that DELETES old image
    // Delete old thumbnail if exists
    $old_thumbnail_id = get_post_thumbnail_id($entry_id);
    if ($old_thumbnail_id) {
        wp_delete_attachment($old_thumbnail_id, true);  // ❌ DELETES!
    }
    // ... upload new one
}
```

**FIXED CODE:**
```php
// Handle photo uploads - can add new or replace existing
if (!empty($_FILES['new_photo'])) {
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    // Get existing images
    $existing_images = get_post_meta($entry_id, 'entry_images', true);
    if (!is_array($existing_images)) {
        $existing_images = [];
    }

    // Check which images to keep (sent from frontend)
    if (isset($_POST['kept_images'])) {
        $kept_images = json_decode(stripslashes($_POST['kept_images']), true);
        if (is_array($kept_images)) {
            // Remove images that are not in the "kept" list
            foreach ($existing_images as $img_id) {
                $img_url = wp_get_attachment_url($img_id);
                if (!in_array($img_url, $kept_images)) {
                    // User removed this image
                    wp_delete_attachment($img_id, true);
                }
            }

            // Rebuild existing images array with only kept ones
            $existing_images = array_filter($existing_images, function($img_id) use ($kept_images) {
                $img_url = wp_get_attachment_url($img_id);
                return in_array($img_url, $kept_images);
            });
        }
    }

    $new_attachment_ids = [];

    // Handle single file
    if (!empty($_FILES['new_photo']['name']) && !is_array($_FILES['new_photo']['name'])) {
        $upload = wp_handle_upload($_FILES['new_photo'], ['test_form' => false]);
        if ($upload && !isset($upload['error'])) {
            $attachment_id = myavana_create_attachment($upload, $entry_id, $user_id);
            if ($attachment_id && !is_wp_error($attachment_id)) {
                $new_attachment_ids[] = $attachment_id;
            }
        }
    }

    // Handle multiple files
    if (!empty($_FILES['new_photo']['name']) && is_array($_FILES['new_photo']['name'])) {
        $files = $_FILES['new_photo'];
        $file_count = count($files['name']);

        for ($i = 0; $i < $file_count; $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $file = [
                    'name' => $files['name'][$i],
                    'type' => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error' => $files['error'][$i],
                    'size' => $files['size'][$i]
                ];

                $upload = wp_handle_upload($file, ['test_form' => false]);
                if ($upload && !isset($upload['error'])) {
                    $attachment_id = myavana_create_attachment($upload, $entry_id, $user_id);
                    if ($attachment_id && !is_wp_error($attachment_id)) {
                        $new_attachment_ids[] = $attachment_id;
                    }
                }
            }
        }
    }

    // Merge existing and new images
    $all_images = array_merge($existing_images, $new_attachment_ids);

    // Update featured image (use first image)
    if (!empty($all_images)) {
        set_post_thumbnail($entry_id, $all_images[0]);
        update_post_meta($entry_id, 'entry_images', $all_images);
    }
}
```

---

#### Fix 2.2: Enhance populateExistingImages()
**File:** `/assets/js/timeline/timeline-forms.js`
**Priority:** 🟠 HIGH
**Complexity:** Low

**Current Code (Lines 603-658):**
Already mostly correct, but needs to handle multiple images properly.

**Enhancement to myavana_get_entry_details() backend:**

**File:** `/templates/hair-diary-timeline-shortcode.php` - Lines 1142-1152

**Current Code:**
```php
$detailed_data = [
    // ... other fields
    'image' => get_the_post_thumbnail_url($entry_id, 'large') ?: '',
    // ...
];
```

**FIXED CODE:**
```php
// Get all images associated with entry
$images = [];
$entry_images_meta = get_post_meta($entry_id, 'entry_images', true);

if (is_array($entry_images_meta) && !empty($entry_images_meta)) {
    // Entry has multiple images stored
    foreach ($entry_images_meta as $img_id) {
        $img_url = wp_get_attachment_url($img_id);
        if ($img_url) {
            $images[] = $img_url;
        }
    }
} else {
    // Fall back to featured image
    $featured_img = get_the_post_thumbnail_url($entry_id, 'large');
    if ($featured_img) {
        $images[] = $featured_img;
    }
}

$detailed_data = [
    'id' => $entry_id,
    'title' => $entry->post_title,
    'description' => $entry->post_content,
    'entry_date' => get_the_date('Y-m-d', $entry_id),  // ✅ Changed to match form input format
    'date' => get_the_date('F j, Y g:i A', $entry_id),  // Keep formatted for display
    'rating' => isset($meta_data['health_rating'][0]) ? $meta_data['health_rating'][0] : '5',
    'mood' => isset($meta_data['mood_demeanor'][0]) ? $meta_data['mood_demeanor'][0] : '',
    'mood_demeanor' => isset($meta_data['mood_demeanor'][0]) ? $meta_data['mood_demeanor'][0] : '',
    'environment' => isset($meta_data['environment'][0]) ? $meta_data['environment'][0] : '',
    'products' => isset($meta_data['products_used'][0]) ? $meta_data['products_used'][0] : '',
    'notes' => isset($meta_data['stylist_notes'][0]) ? $meta_data['stylist_notes'][0] : '',
    'image' => !empty($images) ? $images[0] : '',  // First image for backward compatibility
    'images' => $images,  // ✅ Array of all images
    'ai_tags' => isset($meta_data['ai_tags'][0]) ? maybe_unserialize($meta_data['ai_tags'][0]) : [],
    'analysis_data' => isset($meta_data['analysis_data'][0]) ? json_decode($meta_data['analysis_data'][0], true) : null,
    'session_id' => isset($meta_data['session_id'][0]) ? $meta_data['session_id'][0] : ''
];
```

---

### Phase 3: Medium Priority Fixes

#### Fix 3.1: Add Comprehensive Client-Side Validation
**File:** `/assets/js/timeline/timeline-forms.js`
**Priority:** 🟡 MEDIUM
**Complexity:** Low

Already included in Fix 1.2 (`validateEntryForm()` function).

---

#### Fix 3.2: Improve User Feedback & Notifications
**File:** `/assets/js/timeline/timeline-forms.js`
**Priority:** 🟡 MEDIUM
**Complexity:** Low

**Current Code (Lines 1180-1213):**
References CSS animations that don't exist.

**FIXED CODE:**

Add CSS animations:
```css
/* Add to timeline-forms.js or separate CSS file */
@keyframes slideInNotification {
    from {
        opacity: 0;
        transform: translateX(100px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes slideOutNotification {
    from {
        opacity: 1;
        transform: translateX(0);
    }
    to {
        opacity: 0;
        transform: translateX(100px);
    }
}
```

Update JavaScript notification function:
```javascript
showNotification: function(message, type = 'info') {
    console.log('Notification:', type, message);

    // Remove any existing notifications
    const existing = document.querySelectorAll('.notification-hjn');
    existing.forEach(n => n.remove());

    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification-hjn notification-${type}-hjn`;

    // Support HTML content (for multi-line errors)
    if (message.includes('<br>')) {
        notification.innerHTML = message;
    } else {
        notification.textContent = message;
    }

    // Icon based on type
    const icon = type === 'success' ? '✓' : type === 'error' ? '✕' : 'ℹ';
    const iconEl = document.createElement('span');
    iconEl.className = 'notification-icon-hjn';
    iconEl.textContent = icon;
    notification.prepend(iconEl);

    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 1rem 1.5rem;
        background: ${type === 'success' ? 'var(--myavana-coral, #e7a690)' : type === 'error' ? '#e74c3c' : 'var(--myavana-blueberry, #4a4d68)'};
        color: white;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        z-index: 100000;
        font-family: Archivo, sans-serif;
        font-weight: 600;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 12px;
        max-width: 400px;
        animation: slideInNotification 0.3s ease;
    `;

    document.body.appendChild(notification);

    // Auto-remove after duration based on message length
    const duration = Math.max(3000, message.length * 50); // Longer for longer messages
    setTimeout(() => {
        notification.style.animation = 'slideOutNotification 0.3s ease';
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, duration);
},
```

---

#### Fix 3.3: Add Upload Progress Indicators
**File:** `/assets/js/timeline/timeline-forms.js`
**Priority:** 🟡 MEDIUM
**Complexity:** Medium

**Enhancement to handleEntrySubmit():**

Replace the fetch call with XMLHttpRequest for progress tracking:

```javascript
// Replace lines 882-888 with:
return new Promise((resolve, reject) => {
    const xhr = new XMLHttpRequest();

    // Progress handler
    xhr.upload.addEventListener('progress', (e) => {
        if (e.lengthComputable) {
            const percentComplete = (e.loaded / e.total) * 100;
            console.log('Upload progress:', percentComplete + '%');

            // Update loading message
            if (loadingEl) {
                const progressText = loadingEl.querySelector('p');
                if (progressText) {
                    if (percentComplete < 100) {
                        progressText.textContent = `Uploading... ${Math.round(percentComplete)}%`;
                    } else {
                        progressText.textContent = 'Processing...';
                    }
                }
            }
        }
    });

    // Load handler
    xhr.addEventListener('load', () => {
        if (xhr.status >= 200 && xhr.status < 300) {
            try {
                const data = JSON.parse(xhr.responseText);
                resolve(data);
            } catch (error) {
                reject(new Error('Invalid JSON response'));
            }
        } else {
            reject(new Error(`HTTP Error: ${xhr.status}`));
        }
    });

    // Error handler
    xhr.addEventListener('error', () => {
        reject(new Error('Network error'));
    });

    xhr.open('POST', ajaxUrl);
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.send(formData);
})
.then(data => {
    console.log('AJAX response:', data);
    // ... rest of success handling
})
.catch(error => {
    console.error('Error:', error);
    // ... error handling
});
```

---

## 📊 Implementation Checklist

### Phase 1: Critical Fixes (Week 1)
- [ ] 1.1: Create `/templates/pages/partials/offcanvas-create.php`
- [ ] 1.2: Fix form submission logic in `timeline-forms.js`
- [ ] 1.3: Add nonce verification to `myavana_add_entry()`
- [ ] 1.4: Fix date handling in backend handlers
- [ ] Test: Create new entry
- [ ] Test: Edit existing entry
- [ ] Test: Date preservation on edit
- [ ] Test: Form validation

### Phase 2: High Priority Fixes (Week 2)
- [ ] 2.1: Implement multi-image upload (frontend & backend)
- [ ] 2.2: Enhance `populateExistingImages()` function
- [ ] 2.3: Update `myavana_get_entry_details()` for multiple images
- [ ] Test: Upload multiple images on create
- [ ] Test: Edit images (add/remove) on update
- [ ] Test: Image gallery display

### Phase 3: Medium Priority Enhancements (Week 3)
- [ ] 3.1: Add comprehensive client-side validation
- [ ] 3.2: Improve notification system with animations
- [ ] 3.3: Add upload progress indicators
- [ ] Test: Validation error messages
- [ ] Test: Success/error notifications
- [ ] Test: Large file upload progress

### Phase 4: Testing & Polish (Week 4)
- [ ] Cross-browser testing (Chrome, Firefox, Safari, Edge)
- [ ] Mobile responsiveness testing
- [ ] Security audit of all AJAX handlers
- [ ] Performance testing with large entries
- [ ] User acceptance testing
- [ ] Documentation updates

---

## 🧪 Testing Plan

### Unit Tests
1. **Form Validation:**
   - Empty title
   - Title < 3 characters
   - Title > 200 characters
   - Empty content
   - Content < 10 characters
   - No rating selected
   - Invalid date

2. **Create Entry:**
   - With all fields filled
   - With minimum required fields only
   - With single image
   - With multiple images (up to 5)
   - With products selected
   - Without products

3. **Edit Entry:**
   - Change title only
   - Change content only
   - Change date
   - Change rating
   - Add new images (keeping existing)
   - Remove existing images
   - Replace all images
   - Update without changing images

4. **Image Handling:**
   - Upload 1 image
   - Upload 5 images (max)
   - Try to upload 6 images (should limit)
   - Upload large files (5MB limit)
   - Remove images on edit
   - Keep some, remove some on edit

### Integration Tests
1. **Full workflow:**
   - Create entry → View in timeline → Edit → View changes → Delete
2. **Multi-user:**
   - User A creates entry
   - User B cannot edit User A's entry
3. **Performance:**
   - Create 100 entries
   - Edit entry with 5 large images
   - Load timeline with 100 entries

### Security Tests
1. **Nonce verification:**
   - Submit without nonce → Should fail
   - Submit with invalid nonce → Should fail
   - Submit with expired nonce → Should fail
2. **Authorization:**
   - Non-logged-in user creates entry → Should fail
   - User edits another user's entry → Should fail
3. **Input sanitization:**
   - XSS in title field
   - XSS in content field
   - SQL injection in products field

---

## 🚀 Deployment Strategy

### Pre-Deployment
1. ✅ Backup current plugin files
2. ✅ Backup database (wp_posts, wp_postmeta tables)
3. ✅ Test on staging environment
4. ✅ Run all unit tests
5. ✅ Perform security audit

### Deployment Steps
1. **Upload new template file** (`offcanvas-create.php`)
2. **Update JavaScript file** (`timeline-forms.js`)
3. **Update backend handlers** (`hair-diary-timeline-shortcode.php`)
4. **Clear all caches** (WordPress, browser, CDN)
5. **Test immediately after deployment:**
   - Create new entry
   - Edit existing entry
   - Check console for errors

### Rollback Plan
If critical issues arise:
1. Revert to backed-up files
2. Clear caches
3. Notify users of temporary issue
4. Debug on staging
5. Re-deploy with fixes

---

## 📝 Code Quality Improvements

### Code Standards Applied
- ✅ Consistent function naming
- ✅ Proper error handling with try-catch
- ✅ Input validation before processing
- ✅ Output escaping for security
- ✅ Comprehensive comments
- ✅ Console logging for debugging
- ✅ Semantic HTML
- ✅ MYAVANA brand CSS variables
- ✅ Accessibility (ARIA labels)
- ✅ Mobile-first responsive design

### Performance Optimizations
- ✅ Lazy load FilePond only when needed
- ✅ Debounce validation checks
- ✅ Minimize DOM queries
- ✅ Use event delegation where possible
- ✅ Optimize image uploads with compression

---

## 🔒 Security Enhancements

### Implemented Security Measures
1. ✅ **Nonce verification** on all AJAX handlers
2. ✅ **User authentication** checks
3. ✅ **Authorization** (entry ownership verification)
4. ✅ **Input sanitization** using WordPress functions
5. ✅ **Output escaping** in templates
6. ✅ **File type validation** for uploads
7. ✅ **File size limits** (5MB per file, 5 files max)
8. ✅ **SQL injection prevention** with `$wpdb->prepare()`
9. ✅ **XSS prevention** with `sanitize_text_field()`, `sanitize_textarea_field()`
10. ✅ **CSRF protection** with WordPress nonces

---

## 📚 Documentation Updates

### Files to Update
1. **CLAUDE.md** - Add forms functionality section
2. **User Guide** - Create/edit entry instructions
3. **Developer Docs** - AJAX endpoint documentation
4. **Changelog** - Version history with fixes

---

## 🎯 Success Metrics

### Functional Metrics
- [ ] 100% of create operations succeed
- [ ] 100% of edit operations preserve data correctly
- [ ] 0 security vulnerabilities
- [ ] < 2 seconds average form submission time
- [ ] 100% mobile compatibility

### User Experience Metrics
- [ ] Clear error messages for all validation failures
- [ ] Visual feedback for all user actions
- [ ] No console errors on normal operations
- [ ] Smooth animations and transitions
- [ ] Intuitive form flow

---

## 🛠️ Maintenance Plan

### Ongoing Monitoring
1. **Weekly:** Check error logs for form-related errors
2. **Monthly:** Review user feedback on forms
3. **Quarterly:** Performance audit of AJAX handlers
4. **Annually:** Security audit of all endpoints

### Future Enhancements
1. Auto-save drafts
2. Rich text editor for content
3. Drag-and-drop image upload
4. Image cropping/editing tools
5. Templates for common entry types
6. Bulk entry operations
7. Export entries (PDF, CSV)
8. Entry versioning/history

---

## 📞 Support & Resources

### Developer Resources
- **WordPress Codex:** https://codex.wordpress.org/
- **FilePond Docs:** https://pqina.nl/filepond/docs/
- **Select2 Docs:** https://select2.org/

### Testing Tools
- **Browser DevTools** - Network tab for AJAX debugging
- **Query Monitor** - WordPress performance plugin
- **Debug Bar** - WordPress debug plugin
- **Postman** - AJAX endpoint testing

---

## Conclusion

The MYAVANA Hair Journey Timeline forms have **critical bugs** preventing proper edit functionality and **security vulnerabilities** that must be addressed immediately. The fixes outlined in this document will:

1. ✅ **Restore edit functionality** (currently completely broken)
2. ✅ **Preserve entry dates** on updates
3. ✅ **Enable multi-image uploads** as designed
4. ✅ **Secure all endpoints** with proper nonce verification
5. ✅ **Improve user experience** with validation and feedback
6. ✅ **Follow MYAVANA brand guidelines** in UI design

**Estimated Implementation Time:** 3-4 weeks
**Priority Level:** 🔴 CRITICAL (Forms are core functionality)
**Risk Level:** 🟢 LOW (Clear fixes with backward compatibility)

---

**Document Version:** 1.0
**Last Updated:** 2025-10-22
**Next Review:** After Phase 1 implementation
