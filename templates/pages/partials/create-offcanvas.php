<?php
/**
 * Create/Edit Offcanvas Forms - Hair Journey
 * Reusable offcanvas for creating and editing entries, goals, and routines
 * Mobile-first responsive design with MYAVANA branding
 * Uses FilePond for file uploads
 */
?>

<!-- Create Offcanvas Overlay -->
<div class="offcanvas-overlay-hjn" id="createOffcanvasOverlay" onclick="closeOffcanvas()"></div>

<!-- ============================================
     ENTRY CREATE/EDIT OFFCANVAS
     ============================================ -->
<div class="offcanvas-hjn create-offcanvas-hjn" id="entryOffcanvas" data-type="entry">
    <div class="offcanvas-header-hjn">
        <h2 class="offcanvas-title-hjn" id="entryOffcanvasTitle">Add Hair Journey Entry</h2>
        <button class="offcanvas-close-hjn" onclick="closeOffcanvas()" aria-label="Close">
            <svg viewBox="0 0 24 24" width="24" height="24">
                <path fill="currentColor" d="M19,6.41L17.59,5L12,10.59L6.41,5L5,6.41L10.59,12L5,17.59L6.41,19L12,13.41L17.59,19L19,17.59L13.41,12L19,6.41Z"/>
            </svg>
        </button>
    </div>

    <div class="offcanvas-content-hjn">
        <form id="entryForm" class="hair-journey-form-hjn" enctype="multipart/form-data">
            <input type="hidden" name="entry_id" id="entry_id" value="">
            <input type="hidden" name="action" value="myavana_entry_action">
            <?php wp_nonce_field('myavana_entry_action', 'myavana_nonce'); ?>
            <input type="hidden" name="myavana_entry" value="1">
            <input type="hidden" name="is_automated" value="0">

            <!-- Entry Title -->
            <div class="form-group-hjn">
                <label for="entry_title" class="form-label-hjn">
                    Title
                    <span class="form-required-hjn">*</span>
                </label>
                <input
                    type="text"
                    id="entry_title"
                    name="title"
                    class="form-input-hjn"
                    placeholder="e.g., Wash Day, Deep Conditioning, Trim"
                    required
                    maxlength="100"
                >
            </div>

            <!-- Entry Date & Time -->
            <div class="form-row-hjn">
                <div class="form-group-hjn">
                    <label for="entry_date" class="form-label-hjn">
                        Date
                        <span class="form-required-hjn">*</span>
                    </label>
                    <input
                        type="date"
                        id="entry_date"
                        name="entry_date"
                        class="form-input-hjn"
                        required
                        max="<?php echo date('Y-m-d'); ?>"
                    >
                </div>
                <div class="form-group-hjn">
                    <label for="entry_time" class="form-label-hjn">
                        Time
                    </label>
                    <input
                        type="time"
                        id="entry_time"
                        name="entry_time"
                        class="form-input-hjn"
                        value="<?php echo date('H:i'); ?>"
                    >
                </div>
            </div>

            <!-- Entry Content/Description -->
            <div class="form-group-hjn">
                <label for="entry_content" class="form-label-hjn">
                    Description
                </label>
                <textarea
                    id="entry_content"
                    name="description"
                    class="form-textarea-hjn"
                    rows="5"
                    placeholder="Describe what you did, how your hair felt, any observations..."
                    maxlength="2000"
                ></textarea>
                <div class="form-hint-hjn">
                    <span id="entry_content_count">0</span>/2000 characters
                </div>
            </div>

            <!-- Existing Images Gallery (for edit mode) -->
            <div class="form-group-hjn existing-images-gallery-hjn" id="existingImagesGallery" style="display: none;">
                <label class="form-label-hjn">
                    Current Images
                </label>
                <div class="existing-images-grid-hjn" id="existingImagesGrid">
                    <!-- Existing images will be populated here -->
                </div>
            </div>

            <!-- New Photos Upload (FilePond) -->
            <div class="form-group-hjn">
                <label class="form-label-hjn">
                    Add More Photos
                </label>
                <div class="filepond-wrapper-hjn">
                    <input
                        type="file"
                        id="entry_photos"
                        name="entry_photos[]"
                        class="filepond-hjn"
                        multiple
                        accept="image/*"
                        data-max-files="5"
                    >
                </div>
                <div class="form-hint-hjn">
                    Upload additional photos. Total limit: 5 photos. Supported: JPG, PNG, WebP (max 5MB each)
                </div>
            </div>

            <!-- Health Rating -->
            <div class="form-group-hjn">
                <label for="health_rating" class="form-label-hjn">
                    Hair Health Rating
                </label>
                <div class="rating-input-hjn">
                    <div class="rating-stars-hjn" id="health_rating_stars">
                        <button type="button" class="rating-star-hjn" data-value="1">
                            <svg viewBox="0 0 24 24" width="28" height="28">
                                <path fill="currentColor" d="M12,15.39L8.24,17.66L9.23,13.38L5.91,10.5L10.29,10.13L12,6.09L13.71,10.13L18.09,10.5L14.77,13.38L15.76,17.66M22,9.24L14.81,8.63L12,2L9.19,8.63L2,9.24L7.45,13.97L5.82,21L12,17.27L18.18,21L16.54,13.97L22,9.24Z"/>
                            </svg>
                        </button>
                        <button type="button" class="rating-star-hjn" data-value="2">
                            <svg viewBox="0 0 24 24" width="28" height="28">
                                <path fill="currentColor" d="M12,15.39L8.24,17.66L9.23,13.38L5.91,10.5L10.29,10.13L12,6.09L13.71,10.13L18.09,10.5L14.77,13.38L15.76,17.66M22,9.24L14.81,8.63L12,2L9.19,8.63L2,9.24L7.45,13.97L5.82,21L12,17.27L18.18,21L16.54,13.97L22,9.24Z"/>
                            </svg>
                        </button>
                        <button type="button" class="rating-star-hjn" data-value="3">
                            <svg viewBox="0 0 24 24" width="28" height="28">
                                <path fill="currentColor" d="M12,15.39L8.24,17.66L9.23,13.38L5.91,10.5L10.29,10.13L12,6.09L13.71,10.13L18.09,10.5L14.77,13.38L15.76,17.66M22,9.24L14.81,8.63L12,2L9.19,8.63L2,9.24L7.45,13.97L5.82,21L12,17.27L18.18,21L16.54,13.97L22,9.24Z"/>
                            </svg>
                        </button>
                        <button type="button" class="rating-star-hjn" data-value="4">
                            <svg viewBox="0 0 24 24" width="28" height="28">
                                <path fill="currentColor" d="M12,15.39L8.24,17.66L9.23,13.38L5.91,10.5L10.29,10.13L12,6.09L13.71,10.13L18.09,10.5L14.77,13.38L15.76,17.66M22,9.24L14.81,8.63L12,2L9.19,8.63L2,9.24L7.45,13.97L5.82,21L12,17.27L18.18,21L16.54,13.97L22,9.24Z"/>
                            </svg>
                        </button>
                        <button type="button" class="rating-star-hjn" data-value="5">
                            <svg viewBox="0 0 24 24" width="28" height="28">
                                <path fill="currentColor" d="M12,15.39L8.24,17.66L9.23,13.38L5.91,10.5L10.29,10.13L12,6.09L13.71,10.13L18.09,10.5L14.77,13.38L15.76,17.66M22,9.24L14.81,8.63L12,2L9.19,8.63L2,9.24L7.45,13.97L5.82,21L12,17.27L18.18,21L16.54,13.97L22,9.24Z"/>
                            </svg>
                        </button>
                    </div>
                    <input type="hidden" id="health_rating" name="rating" value="3">
                    <span class="rating-value-hjn" id="health_rating_value">Not Rated</span>
                </div>
            </div>

            <!-- Mood/Feeling -->
            <div class="form-group-hjn">
                <label for="mood" class="form-label-hjn">
                    How are you feeling about your hair?
                </label>
                <select id="mood" name="mood_demeanor" class="form-select-hjn">
                    <option value="">Select mood...</option>
                    <option value="Amazing">Amazing - Best hair day ever!</option>
                    <option value="Great">Great - Hair is looking good</option>
                    <option value="Good">Good - Feeling positive</option>
                    <option value="Okay">Okay - It's alright</option>
                    <option value="Struggling">Struggling - Need help</option>
                    <option value="Frustrated">Frustrated - Having issues</option>
                </select>
            </div>

            <!-- Products Used (Multi-Select Dropdown) -->
            <div class="form-group-hjn">
                <label for="products_used" class="form-label-hjn">
                    Products Used
                </label>
                <select id="products_used" name="products[]" class="form-select-hjn" multiple="multiple" style="height: auto;">
                    <!-- Options will be populated via JavaScript -->
                </select>
                <div class="form-hint-hjn">
                    Select all products you used for this entry
                </div>
            </div>

            <!-- Stylist Notes -->
            <div class="form-group-hjn">
                <label for="notes" class="form-label-hjn">
                    Stylist Notes
                </label>
                <textarea
                    id="notes"
                    name="notes"
                    class="form-textarea-hjn"
                    rows="3"
                    placeholder="Notes from your stylist"
                ></textarea>
            </div>

            <!-- Techniques -->
            <div class="form-group-hjn">
                <label for="techniques" class="form-label-hjn">
                    Techniques/Methods Used
                </label>
                <input
                    type="text"
                    id="techniques"
                    name="techniques"
                    class="form-input-hjn"
                    placeholder="e.g., LOC Method, Shingling, Twist-out"
                >
            </div>

            <!-- AI Analysis Request -->
            <div class="form-group-hjn">
                <label class="form-checkbox-label-hjn">
                    <input
                        type="checkbox"
                        id="request_ai_analysis"
                        name="request_ai_analysis"
                        class="form-checkbox-hjn"
                        value="1"
                    >
                    <span>Request AI analysis of uploaded photos (if photos are added)</span>
                </label>
            </div>

            <!-- Form Actions -->
            <div class="form-actions-hjn">
                <button type="button" class="btn-secondary-hjn" onclick="closeOffcanvas()">
                    Cancel
                </button>
                <button type="submit" class="btn-primary-hjn" id="myavana-entry-form">
                    <svg viewBox="0 0 24 24" width="18" height="18">
                        <path fill="currentColor" d="M17,3H5A2,2 0 0,0 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19V7L17,3M19,19H5V5H16.17L19,7.83V19M12,12A3,3 0 0,0 9,15A3,3 0 0,0 12,18A3,3 0 0,0 15,15A3,3 0 0,0 12,12M6,6H15V10H6V6Z"/>
                    </svg>
                    Save Entry
                </button>
            </div>

            <!-- Loading State -->
            <div class="form-loading-hjn" id="entryFormLoading" style="display: none;">
                <div class="loading-spinner-hjn"></div>
                <p>Saving your entry...</p>
            </div>
        </form>
    </div>
</div>

<!-- ============================================
     GOAL CREATE/EDIT OFFCANVAS
     ============================================ -->
<div class="offcanvas-hjn create-offcanvas-hjn" id="goalOffcanvas" data-type="goal">
    <div class="offcanvas-header-hjn">
        <h2 class="offcanvas-title-hjn" id="goalOffcanvasTitle">Create Hair Goal</h2>
        <button class="offcanvas-close-hjn" onclick="closeOffcanvas()" aria-label="Close">
            <svg viewBox="0 0 24 24" width="24" height="24">
                <path fill="currentColor" d="M19,6.41L17.59,5L12,10.59L6.41,5L5,6.41L10.59,12L5,17.59L6.41,19L12,13.41L17.59,19L19,17.59L13.41,12L19,6.41Z"/>
            </svg>
        </button>
    </div>

    <div class="offcanvas-content-hjn">
        <form id="goalForm" class="hair-journey-form-hjn">
            <input type="hidden" name="goal_id" id="goal_id" value="">
            <input type="hidden" name="action" value="myavana_save_hair_goal">
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('save_hair_goal'); ?>">

            <!-- Goal Title -->
            <div class="form-group-hjn">
                <label for="goal_title" class="form-label-hjn">
                    Goal Title
                    <span class="form-required-hjn">*</span>
                </label>
                <input
                    type="text"
                    id="goal_title"
                    name="goal_title"
                    class="form-input-hjn"
                    placeholder="e.g., Grow 2 inches, Improve moisture retention"
                    required
                    maxlength="100"
                >
            </div>

            <!-- Goal Category -->
            <div class="form-group-hjn">
                <label for="goal_category" class="form-label-hjn">
                    Category
                </label>
                <select id="goal_category" name="goal_category" class="form-select-hjn">
                    <option value="">Select category...</option>
                    <option value="Length">Length/Growth</option>
                    <option value="Health">Hair Health</option>
                    <option value="Moisture">Moisture/Hydration</option>
                    <option value="Strength">Strength/Protein</option>
                    <option value="Texture">Texture Definition</option>
                    <option value="Thickness">Thickness/Volume</option>
                    <option value="Scalp">Scalp Health</option>
                    <option value="Retention">Length Retention</option>
                    <option value="Style">Styling</option>
                    <option value="Other">Other</option>
                </select>
            </div>

            <!-- Goal Description -->
            <div class="form-group-hjn">
                <label for="goal_description" class="form-label-hjn">
                    Description
                </label>
                <textarea
                    id="goal_description"
                    name="goal_description"
                    class="form-textarea-hjn"
                    rows="4"
                    placeholder="Describe your goal and why it's important to you..."
                    maxlength="1000"
                ></textarea>
            </div>

            <!-- Start and End Dates -->
            <div class="form-row-hjn">
                <div class="form-group-hjn">
                    <label for="goal_start_date" class="form-label-hjn">
                        Start Date
                        <span class="form-required-hjn">*</span>
                    </label>
                    <input
                        type="date"
                        id="goal_start_date"
                        name="goal_start_date"
                        class="form-input-hjn"
                        required
                        value="<?php echo date('Y-m-d'); ?>"
                    >
                </div>
                <div class="form-group-hjn">
                    <label for="goal_end_date" class="form-label-hjn">
                        Target Date
                    </label>
                    <input
                        type="date"
                        id="goal_end_date"
                        name="goal_end_date"
                        class="form-input-hjn"
                        min="<?php echo date('Y-m-d'); ?>"
                    >
                </div>
            </div>

            <!-- Target/Measurable Metric -->
            <div class="form-group-hjn">
                <label for="goal_target" class="form-label-hjn">
                    Target/Measurable Metric
                </label>
                <input
                    type="text"
                    id="goal_target"
                    name="goal_target"
                    class="form-input-hjn"
                    placeholder="e.g., 2 inches length, 8/10 health rating"
                >
                <div class="form-hint-hjn">
                    What specific result are you aiming for?
                </div>
            </div>

            <!-- Milestones -->
            <div class="form-group-hjn">
                <label class="form-label-hjn">
                    Milestones (optional)
                </label>
                <div id="milestones_list" class="milestones-list-hjn">
                    <!-- Milestones will be added dynamically -->
                </div>
                <button type="button" class="btn-add-milestone-hjn" onclick="addMilestone()">
                    <svg viewBox="0 0 24 24" width="16" height="16">
                        <path fill="currentColor" d="M19,13H13V19H11V13H5V11H11V5H13V11H19V13Z"/>
                    </svg>
                    Add Milestone
                </button>
            </div>

            <!-- Initial Progress -->
            <div class="form-group-hjn">
                <label for="goal_progress" class="form-label-hjn">
                    Current Progress
                </label>
                <div class="progress-input-hjn">
                    <input
                        type="range"
                        id="goal_progress"
                        name="goal_progress"
                        class="form-range-hjn"
                        min="0"
                        max="100"
                        value="0"
                        oninput="updateProgressValue(this.value)"
                    >
                    <span class="progress-value-hjn" id="goal_progress_value">0%</span>
                </div>
            </div>

            <!-- Progress Notes (for edit mode) -->
            <div class="form-group-hjn" id="goalProgressNotesGroup" style="display: none;">
                <label class="form-label-hjn">
                    Progress Notes
                </label>
                <div class="goal-progress-notes-list-hjn" id="goalProgressNotesList">
                    <!-- Progress notes will be populated here in edit mode -->
                </div>
                <div class="form-hint-hjn">
                    Notes from previous progress updates
                </div>
            </div>

            <!-- Add Progress Note (for edit mode) -->
            <div class="form-group-hjn" id="addProgressNoteGroup" style="display: none;">
                <label class="form-label-hjn">
                    Add Progress Update
                </label>
                <textarea
                    id="newProgressNote"
                    class="form-textarea-hjn"
                    rows="3"
                    placeholder="Add a note about your current progress..."
                    maxlength="500"
                ></textarea>
                <div class="form-hint-hjn">
                    <span id="progress_note_count">0</span>/500 characters
                </div>
            </div>

            <!-- Form Actions -->
            <div class="form-actions-hjn">
                <button type="button" class="btn-secondary-hjn" onclick="closeOffcanvas()">
                    Cancel
                </button>
                <button type="submit" class="btn-primary-hjn" id="saveGoalBtn">
                    <svg viewBox="0 0 24 24" width="18" height="18">
                        <path fill="currentColor" d="M17,3H5A2,2 0 0,0 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19V7L17,3M19,19H5V5H16.17L19,7.83V19M12,12A3,3 0 0,0 9,15A3,3 0 0,0 12,18A3,3 0 0,0 15,15A3,3 0 0,0 12,12M6,6H15V10H6V6Z"/>
                    </svg>
                    Save Goal
                </button>
            </div>

            <!-- Loading State -->
            <div class="form-loading-hjn" id="goalFormLoading" style="display: none;">
                <div class="loading-spinner-hjn"></div>
                <p>Saving your goal...</p>
            </div>
        </form>
    </div>
</div>

<!-- ============================================
     ROUTINE CREATE/EDIT OFFCANVAS
     ============================================ -->
<div class="offcanvas-hjn create-offcanvas-hjn" id="routineOffcanvas" data-type="routine">
    <div class="offcanvas-header-hjn">
        <h2 class="offcanvas-title-hjn" id="routineOffcanvasTitle">Create Hair Routine</h2>
        <button class="offcanvas-close-hjn" onclick="closeOffcanvas()" aria-label="Close">
            <svg viewBox="0 0 24 24" width="24" height="24">
                <path fill="currentColor" d="M19,6.41L17.59,5L12,10.59L6.41,5L5,6.41L10.59,12L5,17.59L6.41,19L12,13.41L17.59,19L19,17.59L13.41,12L19,6.41Z"/>
            </svg>
        </button>
    </div>

    <div class="offcanvas-content-hjn">
        <form id="routineForm" class="hair-journey-form-hjn">
            <input type="hidden" name="routine_id" id="routine_id" value="">
            <input type="hidden" name="action" value="myavana_save_routine_step">
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('save_routine_step'); ?>">

            <!-- Routine Title -->
            <div class="form-group-hjn">
                <label for="routine_title" class="form-label-hjn">
                    Routine Name
                    <span class="form-required-hjn">*</span>
                </label>
                <input
                    type="text"
                    id="routine_title"
                    name="routine_title"
                    class="form-input-hjn"
                    placeholder="e.g., Morning Moisturizing, Wash Day, Night Routine"
                    required
                    maxlength="100"
                >
            </div>

            <!-- Routine Type -->
            <div class="form-group-hjn">
                <label for="routine_type" class="form-label-hjn">
                    Routine Type
                </label>
                <select id="routine_type" name="routine_type" class="form-select-hjn">
                    <option value="">Select type...</option>
                    <option value="Wash Day">Wash Day</option>
                    <option value="Daily Care">Daily Care</option>
                    <option value="Deep Conditioning">Deep Conditioning</option>
                    <option value="Protein Treatment">Protein Treatment</option>
                    <option value="Styling">Styling</option>
                    <option value="Protective Style">Protective Style Prep</option>
                    <option value="Night Routine">Night Routine</option>
                    <option value="Scalp Care">Scalp Care</option>
                    <option value="Other">Other</option>
                </select>
            </div>

            <!-- Frequency -->
            <div class="form-row-hjn">
                <div class="form-group-hjn">
                    <label for="routine_frequency" class="form-label-hjn">
                        Frequency
                    </label>
                    <select id="routine_frequency" name="routine_frequency" class="form-select-hjn">
                        <option value="Daily">Daily</option>
                        <option value="Weekly">Weekly</option>
                        <option value="Bi-weekly">Bi-weekly</option>
                        <option value="Monthly">Monthly</option>
                        <option value="As Needed">As Needed</option>
                    </select>
                </div>
                <div class="form-group-hjn">
                    <label for="routine_time" class="form-label-hjn">
                        Preferred Time
                    </label>
                    <input
                        type="time"
                        id="routine_time"
                        name="routine_time"
                        class="form-input-hjn"
                        value="08:00"
                    >
                </div>
            </div>

            <!-- Duration -->
            <div class="form-group-hjn">
                <label for="routine_duration" class="form-label-hjn">
                    Estimated Duration
                </label>
                <select id="routine_duration" name="routine_duration" class="form-select-hjn">
                    <option value="">Select duration...</option>
                    <option value="5">5 minutes</option>
                    <option value="10">10 minutes</option>
                    <option value="15">15 minutes</option>
                    <option value="20">20 minutes</option>
                    <option value="30">30 minutes</option>
                    <option value="45">45 minutes</option>
                    <option value="60">1 hour</option>
                    <option value="90">1.5 hours</option>
                    <option value="120">2 hours</option>
                    <option value="180">3+ hours</option>
                </select>
            </div>

            <!-- Steps -->
            <div class="form-group-hjn">
                <label class="form-label-hjn">
                    Routine Steps
                    <span class="form-required-hjn">*</span>
                </label>
                <div id="routine_steps_list" class="routine-steps-list-hjn">
                    <!-- Steps will be added dynamically -->
                    <div class="routine-step-item-hjn" data-step="1">
                        <div class="step-number-hjn">1</div>
                        <input
                            type="text"
                            name="routine_steps[]"
                            class="form-input-hjn step-input-hjn"
                            placeholder="Describe this step..."
                            required
                        >
                        <button type="button" class="btn-remove-step-hjn" onclick="removeRoutineStep(this)" disabled>
                            <svg viewBox="0 0 24 24" width="18" height="18">
                                <path fill="currentColor" d="M19,6.41L17.59,5L12,10.59L6.41,5L5,6.41L10.59,12L5,17.59L6.41,19L12,13.41L17.59,19L19,17.59L13.41,12L19,6.41Z"/>
                            </svg>
                        </button>
                    </div>
                </div>
                <button type="button" class="btn-add-step-hjn" onclick="addRoutineStep()">
                    <svg viewBox="0 0 24 24" width="16" height="16">
                        <path fill="currentColor" d="M19,13H13V19H11V13H5V11H11V5H13V11H19V13Z"/>
                    </svg>
                    Add Step
                </button>
            </div>

            <!-- Products for Routine -->
            <div class="form-group-hjn">
                <label for="routine_products" class="form-label-hjn">
                    Products Needed
                </label>
                <textarea
                    id="routine_products"
                    name="routine_products"
                    class="form-textarea-hjn"
                    rows="3"
                    placeholder="List all products needed for this routine (one per line)"
                ></textarea>
            </div>

            <!-- Notes -->
            <div class="form-group-hjn">
                <label for="routine_notes" class="form-label-hjn">
                    Notes/Tips
                </label>
                <textarea
                    id="routine_notes"
                    name="routine_notes"
                    class="form-textarea-hjn"
                    rows="3"
                    placeholder="Any special notes, tips, or reminders..."
                ></textarea>
            </div>

            <!-- Form Actions -->
            <div class="form-actions-hjn">
                <button type="button" class="btn-secondary-hjn" onclick="closeOffcanvas()">
                    Cancel
                </button>
                <button type="submit" class="btn-primary-hjn" id="saveRoutineBtn">
                    <svg viewBox="0 0 24 24" width="18" height="18">
                        <path fill="currentColor" d="M17,3H5A2,2 0 0,0 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19V7L17,3M19,19H5V5H16.17L19,7.83V19M12,12A3,3 0 0,0 9,15A3,3 0 0,0 12,18A3,3 0 0,0 15,15A3,3 0 0,0 12,12M6,6H15V10H6V6Z"/>
                    </svg>
                    Save Routine
                </button>
            </div>

            <!-- Loading State -->
            <div class="form-loading-hjn" id="routineFormLoading" style="display: none;">
                <div class="loading-spinner-hjn"></div>
                <p>Saving your routine...</p>
            </div>
        </form>
    </div>
</div>


