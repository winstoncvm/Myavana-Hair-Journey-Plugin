<?php
/**
 * Unified Profile Edit Offcanvas
 *
 * Comprehensive profile editing interface with avatar upload,
 * personal info, hair profile, goals, routines, and preferences
 *
 * @package Myavana_Hair_Journey
 * @version 1.0.0
 */

// This file is included in unified-profile.php and has access to all its variables
?>

<!-- Profile Edit Offcanvas -->
<div class="myavana-up-edit-offcanvas" id="myavanaUpEditOffcanvas">
    <div class="myavana-up-edit-overlay" onclick="myavanaUpCloseEditOffcanvas()"></div>

    <div class="myavana-up-edit-panel">
        <!-- Header -->
        <div class="myavana-up-edit-header">
            <div class="myavana-up-edit-header-content">
                <h2 class="myavana-up-edit-title">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                    </svg>
                    Edit Your Profile
                </h2>
                <p class="myavana-up-edit-subtitle">Personalize your hair journey experience</p>
            </div>
            <button class="myavana-up-edit-close" onclick="myavanaUpCloseEditOffcanvas()">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>

        <!-- Edit Form -->
        <form id="myavanaUpEditForm" class="myavana-up-edit-form">
            <input type="hidden" name="user_id" value="<?php echo esc_attr($user_id); ?>">
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('myavana_up_edit_profile'); ?>">

            <!-- Avatar Section -->
            <div class="myavana-up-edit-section myavana-up-avatar-section">
                <h3 class="myavana-up-section-title">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                        <circle cx="12" cy="13" r="4"></circle>
                    </svg>
                    Profile Picture
                </h3>

                <div class="myavana-up-avatar-upload-container">
                    <div class="myavana-up-avatar-preview">
                        <img id="myavanaUpAvatarPreview" src="<?php echo esc_url(get_avatar_url($user_id, ['size' => 200])); ?>" alt="Profile Picture">
                        <div class="myavana-up-avatar-overlay">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                                <circle cx="12" cy="13" r="4"></circle>
                            </svg>
                        </div>
                    </div>

                    <div class="myavana-up-avatar-actions">
                        <label for="myavanaUpAvatarInput" class="myavana-up-btn-secondary">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="17 8 12 3 7 8"></polyline>
                                <line x1="12" y1="3" x2="12" y2="15"></line>
                            </svg>
                            Upload New Photo
                        </label>
                        <input type="file" id="myavanaUpAvatarInput" accept="image/*" style="display: none;">

                        <button type="button" class="myavana-up-btn-ghost" id="myavanaUpRemoveAvatar">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="3 6 5 6 21 6"></polyline>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                            </svg>
                            Remove
                        </button>
                    </div>

                    <p class="myavana-up-field-hint">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="16" x2="12" y2="12"></line>
                            <line x1="12" y1="8" x2="12.01" y2="8"></line>
                        </svg>
                        Recommended: Square image, at least 400x400px. Max size: 2MB.
                    </p>
                </div>
            </div>

            <!-- Personal Information -->
            <div class="myavana-up-edit-section">
                <h3 class="myavana-up-section-title">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    Personal Information
                </h3>

                <div class="myavana-up-field-group">
                    <label for="myavanaUpDisplayName" class="myavana-up-field-label">
                        Display Name
                        <span class="myavana-up-required">*</span>
                    </label>
                    <input
                        type="text"
                        id="myavanaUpDisplayName"
                        name="display_name"
                        class="myavana-up-field-input"
                        value="<?php echo esc_attr($current_user->display_name); ?>"
                        required
                        placeholder="How should we call you?"
                    >
                </div>

                <div class="myavana-up-field-group">
                    <label for="myavanaUpUsername" class="myavana-up-field-label">
                        Username
                        <span class="myavana-up-badge">Read-only</span>
                    </label>
                    <input
                        type="text"
                        id="myavanaUpUsername"
                        class="myavana-up-field-input"
                        value="@<?php echo esc_attr($current_user->user_login); ?>"
                        disabled
                    >
                    <p class="myavana-up-field-hint">Usernames cannot be changed</p>
                </div>

                <div class="myavana-up-field-group">
                    <label for="myavanaUpBio" class="myavana-up-field-label">
                        Bio
                    </label>
                    <textarea
                        id="myavanaUpBio"
                        name="bio"
                        class="myavana-up-field-textarea"
                        rows="4"
                        maxlength="500"
                        placeholder="Tell us about your hair journey..."
                    ><?php echo esc_textarea($user_profile->bio ?? ''); ?></textarea>
                    <div class="myavana-up-char-counter">
                        <span id="myavanaUpBioCounter">0</span> / 500
                    </div>
                </div>

                <div class="myavana-up-field-row">
                    <div class="myavana-up-field-group">
                        <label for="myavanaUpLocation" class="myavana-up-field-label">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                <circle cx="12" cy="10" r="3"></circle>
                            </svg>
                            Location
                        </label>
                        <input
                            type="text"
                            id="myavanaUpLocation"
                            name="location"
                            class="myavana-up-field-input"
                            value="<?php echo esc_attr(get_user_meta($user_id, 'myavana_up_location', true)); ?>"
                            placeholder="City, Country"
                        >
                    </div>

                    <div class="myavana-up-field-group">
                        <label for="myavanaUpWebsite" class="myavana-up-field-label">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="2" y1="12" x2="22" y2="12"></line>
                                <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
                            </svg>
                            Website
                        </label>
                        <input
                            type="url"
                            id="myavanaUpWebsite"
                            name="website"
                            class="myavana-up-field-input"
                            value="<?php echo esc_url(get_user_meta($user_id, 'myavana_up_website', true)); ?>"
                            placeholder="https://yourwebsite.com"
                        >
                    </div>
                </div>
            </div>

            <!-- Hair Profile -->
            <div class="myavana-up-edit-section">
                <h3 class="myavana-up-section-title">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/>
                    </svg>
                    Hair Profile
                </h3>

                <div class="myavana-up-field-row">
                    <div class="myavana-up-field-group">
                        <label for="myavanaUpHairType" class="myavana-up-field-label">Hair Type</label>
                        <select id="myavanaUpHairType" name="hair_type" class="myavana-up-field-select">
                            <option value="">Select type...</option>
                            <option value="1A" <?php selected($user_profile->hair_type, '1A'); ?>>1A - Straight, Fine</option>
                            <option value="1B" <?php selected($user_profile->hair_type, '1B'); ?>>1B - Straight, Medium</option>
                            <option value="1C" <?php selected($user_profile->hair_type, '1C'); ?>>1C - Straight, Coarse</option>
                            <option value="2A" <?php selected($user_profile->hair_type, '2A'); ?>>2A - Wavy, Fine</option>
                            <option value="2B" <?php selected($user_profile->hair_type, '2B'); ?>>2B - Wavy, Medium</option>
                            <option value="2C" <?php selected($user_profile->hair_type, '2C'); ?>>2C - Wavy, Coarse</option>
                            <option value="3A" <?php selected($user_profile->hair_type, '3A'); ?>>3A - Curly, Loose</option>
                            <option value="3B" <?php selected($user_profile->hair_type, '3B'); ?>>3B - Curly, Tight</option>
                            <option value="3C" <?php selected($user_profile->hair_type, '3C'); ?>>3C - Curly, Corkscrew</option>
                            <option value="4A" <?php selected($user_profile->hair_type, '4A'); ?>>4A - Coily, S-Pattern</option>
                            <option value="4B" <?php selected($user_profile->hair_type, '4B'); ?>>4B - Coily, Z-Pattern</option>
                            <option value="4C" <?php selected($user_profile->hair_type, '4C'); ?>>4C - Coily, Tight</option>
                        </select>
                    </div>

                    <div class="myavana-up-field-group">
                        <label for="myavanaUpHairPorosity" class="myavana-up-field-label">Porosity</label>
                        <select id="myavanaUpHairPorosity" name="hair_porosity" class="myavana-up-field-select">
                            <option value="">Select porosity...</option>
                            <option value="Low" <?php selected(get_user_meta($user_id, 'hair_porosity', true), 'Low'); ?>>Low Porosity</option>
                            <option value="Normal" <?php selected(get_user_meta($user_id, 'hair_porosity', true), 'Normal'); ?>>Normal Porosity</option>
                            <option value="High" <?php selected(get_user_meta($user_id, 'hair_porosity', true), 'High'); ?>>High Porosity</option>
                        </select>
                    </div>
                </div>

                <div class="myavana-up-field-row">
                    <div class="myavana-up-field-group">
                        <label for="myavanaUpHairLength" class="myavana-up-field-label">Hair Length</label>
                        <select id="myavanaUpHairLength" name="hair_length" class="myavana-up-field-select">
                            <option value="">Select length...</option>
                            <option value="Ear Length" <?php selected(get_user_meta($user_id, 'hair_length', true), 'Ear Length'); ?>>Ear Length</option>
                            <option value="Chin Length" <?php selected(get_user_meta($user_id, 'hair_length', true), 'Chin Length'); ?>>Chin Length</option>
                            <option value="Shoulder Length" <?php selected(get_user_meta($user_id, 'hair_length', true), 'Shoulder Length'); ?>>Shoulder Length</option>
                            <option value="Armpit Length" <?php selected(get_user_meta($user_id, 'hair_length', true), 'Armpit Length'); ?>>Armpit Length</option>
                            <option value="Bra Strap Length" <?php selected(get_user_meta($user_id, 'hair_length', true), 'Bra Strap Length'); ?>>Bra Strap Length</option>
                            <option value="Mid-Back Length" <?php selected(get_user_meta($user_id, 'hair_length', true), 'Mid-Back Length'); ?>>Mid-Back Length</option>
                            <option value="Waist Length" <?php selected(get_user_meta($user_id, 'hair_length', true), 'Waist Length'); ?>>Waist Length</option>
                            <option value="Hip Length+" <?php selected(get_user_meta($user_id, 'hair_length', true), 'Hip Length+'); ?>>Hip Length+</option>
                        </select>
                    </div>

                    <div class="myavana-up-field-group">
                        <label for="myavanaUpJourneyStage" class="myavana-up-field-label">Journey Stage</label>
                        <select id="myavanaUpJourneyStage" name="journey_stage" class="myavana-up-field-select">
                            <option value="">Select stage...</option>
                            <option value="Just Starting" <?php selected($user_profile->hair_journey_stage, 'Just Starting'); ?>>Just Starting</option>
                            <option value="Transitioning" <?php selected($user_profile->hair_journey_stage, 'Transitioning'); ?>>Transitioning</option>
                            <option value="Growing" <?php selected($user_profile->hair_journey_stage, 'Growing'); ?>>Growing</option>
                            <option value="Maintaining" <?php selected($user_profile->hair_journey_stage, 'Maintaining'); ?>>Maintaining</option>
                            <option value="Protective Styling" <?php selected($user_profile->hair_journey_stage, 'Protective Styling'); ?>>Protective Styling</option>
                            <option value="Heat Damage Recovery" <?php selected($user_profile->hair_journey_stage, 'Heat Damage Recovery'); ?>>Heat Damage Recovery</option>
                            <option value="Chemical Recovery" <?php selected($user_profile->hair_journey_stage, 'Chemical Recovery'); ?>>Chemical Recovery</option>
                        </select>
                    </div>
                </div>

                <div class="myavana-up-field-group">
                    <label for="myavanaUpHairConcerns" class="myavana-up-field-label">Hair Concerns</label>
                    <div class="myavana-up-checkbox-group">
                        <?php
                        $concerns = ['Dryness', 'Breakage', 'Split Ends', 'Frizz', 'Tangling', 'Lack of Growth', 'Thinning', 'Scalp Issues'];
                        $user_concerns = get_user_meta($user_id, 'myavana_up_hair_concerns', true) ?: [];
                        foreach ($concerns as $concern):
                        ?>
                            <label class="myavana-up-checkbox-label">
                                <input
                                    type="checkbox"
                                    name="hair_concerns[]"
                                    value="<?php echo esc_attr($concern); ?>"
                                    <?php checked(in_array($concern, $user_concerns)); ?>
                                >
                                <span><?php echo esc_html($concern); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Hair Goals -->
            <div class="myavana-up-edit-section">
                <h3 class="myavana-up-section-title">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12,2L14.39,8.26L21,9.27L16.5,13.65L17.61,20.24L12,17.27L6.39,20.24L7.5,13.65L3,9.27L9.61,8.26L12,2Z"/>
                    </svg>
                    Hair Goals
                </h3>

                <div id="myavanaUpGoalsList" class="myavana-up-goals-list">
                    <?php if (!empty($hair_goals)): ?>
                        <?php foreach ($hair_goals as $index => $goal): ?>
                            <div class="myavana-up-goal-item" data-index="<?php echo $index; ?>">
                                <input
                                    type="text"
                                    name="goals[<?php echo $index; ?>][title]"
                                    class="myavana-up-field-input"
                                    value="<?php echo esc_attr($goal['title'] ?? ''); ?>"
                                    placeholder="Goal title..."
                                >
                                <button type="button" class="myavana-up-btn-icon myavana-up-remove-goal" onclick="myavanaUpRemoveGoal(this)">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <line x1="18" y1="6" x2="6" y2="18"></line>
                                        <line x1="6" y1="6" x2="18" y2="18"></line>
                                    </svg>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <button type="button" class="myavana-up-btn-secondary" onclick="myavanaUpAddGoal()">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    Add Goal
                </button>
            </div>

            <!-- Preferences -->
            <div class="myavana-up-edit-section">
                <h3 class="myavana-up-section-title">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3"></circle>
                        <path d="M12 1v6m0 6v6m0-12h6m-6 12H6m6-6V1m0 12h6M6 12H1"></path>
                    </svg>
                    Preferences
                </h3>

                <div class="myavana-up-toggle-group">
                    <div class="myavana-up-toggle-item">
                        <div class="myavana-up-toggle-info">
                            <label class="myavana-up-toggle-label">Public Profile</label>
                            <p class="myavana-up-toggle-desc">Allow others to view your profile</p>
                        </div>
                        <label class="myavana-up-toggle">
                            <input type="checkbox" name="public_profile" <?php checked(get_user_meta($user_id, 'myavana_up_public_profile', true), '1'); ?>>
                            <span class="myavana-up-toggle-slider"></span>
                        </label>
                    </div>

                    <div class="myavana-up-toggle-item">
                        <div class="myavana-up-toggle-info">
                            <label class="myavana-up-toggle-label">Show Activity Status</label>
                            <p class="myavana-up-toggle-desc">Let others see when you're active</p>
                        </div>
                        <label class="myavana-up-toggle">
                            <input type="checkbox" name="show_activity" <?php checked(get_user_meta($user_id, 'myavana_up_show_activity', true), '1'); ?>>
                            <span class="myavana-up-toggle-slider"></span>
                        </label>
                    </div>

                    <div class="myavana-up-toggle-item">
                        <div class="myavana-up-toggle-info">
                            <label class="myavana-up-toggle-label">Email Notifications</label>
                            <p class="myavana-up-toggle-desc">Receive updates via email</p>
                        </div>
                        <label class="myavana-up-toggle">
                            <input type="checkbox" name="email_notifications" <?php checked(get_user_meta($user_id, 'myavana_up_email_notifications', true), '1'); ?>>
                            <span class="myavana-up-toggle-slider"></span>
                        </label>
                    </div>

                    <div class="myavana-up-toggle-item">
                        <div class="myavana-up-toggle-info">
                            <label class="myavana-up-toggle-label">Community Updates</label>
                            <p class="myavana-up-toggle-desc">Get notified about likes, comments & follows</p>
                        </div>
                        <label class="myavana-up-toggle">
                            <input type="checkbox" name="community_notifications" <?php checked(get_user_meta($user_id, 'myavana_up_community_notifications', true), '1'); ?>>
                            <span class="myavana-up-toggle-slider"></span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="myavana-up-edit-actions">
                <button type="button" class="myavana-up-btn-ghost" onclick="myavanaUpCloseEditOffcanvas()">
                    Cancel
                </button>
                <button type="submit" class="myavana-up-btn-primary" id="myavanaUpSaveProfileBtn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17 21 17 13 7 13 7 21"></polyline>
                        <polyline points="7 3 7 8 15 8"></polyline>
                    </svg>
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<style>
/* Unified Profile Edit Offcanvas Styles */
.myavana-up-edit-offcanvas {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 10000;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.3s ease, visibility 0.3s ease;
}

.myavana-up-edit-offcanvas.active {
    opacity: 1;
    visibility: visible;
}

.myavana-up-edit-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(4px);
}

.myavana-up-edit-panel {
    position: absolute;
    top: 0;
    right: 0;
    width: 600px;
    max-width: 100%;
    height: 100%;
    background: var(--myavana-white);
    box-shadow: -4px 0 24px rgba(0, 0, 0, 0.15);
    transform: translateX(100%);
    transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex;
    flex-direction: column;
}

.myavana-up-edit-offcanvas.active .myavana-up-edit-panel {
    transform: translateX(0);
}

.myavana-up-edit-header {
    background: linear-gradient(135deg, var(--myavana-coral) 0%, var(--myavana-light-coral) 100%);
    padding: 32px 24px;
    color: var(--myavana-white);
    flex-shrink: 0;
    position: relative;
}

.myavana-up-edit-title {
    font-family: 'Archivo Black', sans-serif;
    font-size: 24px;
    margin: 0 0 8px 0;
    display: flex;
    align-items: center;
    gap: 12px;
}

.myavana-up-edit-subtitle {
    margin: 0;
    opacity: 0.9;
    font-size: 14px;
}

.myavana-up-edit-close {
    position: absolute;
    top: 24px;
    right: 24px;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: var(--myavana-white);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
}

.myavana-up-edit-close:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: rotate(90deg);
}

.myavana-up-edit-form {
    flex: 1;
    overflow-y: auto;
    padding: 32px 24px;
}

.myavana-up-edit-section {
    margin-bottom: 40px;
}

.myavana-up-section-title {
    font-family: 'Archivo', sans-serif;
    font-weight: 700;
    font-size: 18px;
    color: var(--myavana-onyx);
    margin: 0 0 24px 0;
    display: flex;
    align-items: center;
    gap: 12px;
    padding-bottom: 16px;
    border-bottom: 2px solid var(--myavana-sand);
}

.myavana-up-section-title svg {
    color: var(--myavana-coral);
}

/* Avatar Section */
.myavana-up-avatar-upload-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 20px;
}

.myavana-up-avatar-preview {
    position: relative;
    width: 160px;
    height: 160px;
    border-radius: 50%;
    overflow: hidden;
    border: 4px solid var(--myavana-sand);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
    cursor: pointer;
    transition: all 0.3s ease;
}

.myavana-up-avatar-preview:hover {
    transform: scale(1.05);
    border-color: var(--myavana-coral);
}

.myavana-up-avatar-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.myavana-up-avatar-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(231, 166, 144, 0.9);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.myavana-up-avatar-preview:hover .myavana-up-avatar-overlay {
    opacity: 1;
}

.myavana-up-avatar-actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    justify-content: center;
}

/* Form Fields */
.myavana-up-field-group {
    margin-bottom: 24px;
}

.myavana-up-field-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-family: 'Archivo', sans-serif;
    font-weight: 600;
    font-size: 14px;
    color: var(--myavana-onyx);
    margin-bottom: 8px;
}

.myavana-up-field-label svg {
    color: var(--myavana-coral);
}

.myavana-up-required {
    color: var(--myavana-coral);
}

.myavana-up-badge {
    display: inline-block;
    background: var(--myavana-sand);
    color: var(--myavana-blueberry);
    font-size: 11px;
    padding: 2px 8px;
    border-radius: 4px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.myavana-up-field-input,
.myavana-up-field-textarea,
.myavana-up-field-select {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid var(--myavana-border);
    border-radius: 8px;
    font-family: 'Archivo', sans-serif;
    font-size: 14px;
    color: var(--myavana-onyx);
    background: var(--myavana-white);
    transition: all 0.2s ease;
}

.myavana-up-field-input:focus,
.myavana-up-field-textarea:focus,
.myavana-up-field-select:focus {
    outline: none;
    border-color: var(--myavana-coral);
    box-shadow: 0 0 0 4px rgba(231, 166, 144, 0.1);
}

.myavana-up-field-input:disabled {
    background: var(--myavana-sand);
    cursor: not-allowed;
    opacity: 0.6;
}

.myavana-up-field-textarea {
    resize: vertical;
    min-height: 100px;
}

.myavana-up-field-hint {
    font-size: 13px;
    color: var(--myavana-blueberry);
    margin-top: 8px;
    display: flex;
    align-items: flex-start;
    gap: 6px;
}

.myavana-up-field-hint svg {
    flex-shrink: 0;
    margin-top: 2px;
}

.myavana-up-char-counter {
    text-align: right;
    font-size: 12px;
    color: var(--myavana-blueberry);
    margin-top: 4px;
}

.myavana-up-field-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

/* Checkbox Group */
.myavana-up-checkbox-group {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
}

.myavana-up-checkbox-label {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 12px;
    background: var(--myavana-sand);
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.myavana-up-checkbox-label:hover {
    background: var(--myavana-light-coral);
}

.myavana-up-checkbox-label input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.myavana-up-checkbox-label input[type="checkbox"]:checked + span {
    font-weight: 600;
    color: var(--myavana-coral);
}

/* Goals List */
.myavana-up-goals-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-bottom: 16px;
}

.myavana-up-goal-item {
    display: flex;
    gap: 12px;
    align-items: center;
}

.myavana-up-goal-item .myavana-up-field-input {
    flex: 1;
    margin-bottom: 0;
}

/* Toggle Group */
.myavana-up-toggle-group {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.myavana-up-toggle-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px;
    background: var(--myavana-sand);
    border-radius: 12px;
}

.myavana-up-toggle-label {
    font-family: 'Archivo', sans-serif;
    font-weight: 600;
    font-size: 14px;
    color: var(--myavana-onyx);
    margin-bottom: 4px;
}

.myavana-up-toggle-desc {
    font-size: 13px;
    color: var(--myavana-blueberry);
    margin: 0;
}

.myavana-up-toggle {
    position: relative;
    display: inline-block;
    width: 50px;
    height: 28px;
    flex-shrink: 0;
}

.myavana-up-toggle input {
    opacity: 0;
    width: 0;
    height: 0;
}

.myavana-up-toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: var(--myavana-stone);
    transition: 0.3s;
    border-radius: 28px;
}

.myavana-up-toggle-slider:before {
    position: absolute;
    content: "";
    height: 22px;
    width: 22px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: 0.3s;
    border-radius: 50%;
}

.myavana-up-toggle input:checked + .myavana-up-toggle-slider {
    background-color: var(--myavana-coral);
}

.myavana-up-toggle input:checked + .myavana-up-toggle-slider:before {
    transform: translateX(22px);
}

/* Buttons */
.myavana-up-btn-primary,
.myavana-up-btn-secondary,
.myavana-up-btn-ghost,
.myavana-up-btn-icon {
    padding: 12px 24px;
    border-radius: 8px;
    font-family: 'Archivo', sans-serif;
    font-weight: 600;
    font-size: 14px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
    border: none;
}

.myavana-up-btn-primary {
    background: var(--myavana-coral);
    color: var(--myavana-white);
}

.myavana-up-btn-primary:hover {
    background: var(--myavana-onyx);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.myavana-up-btn-secondary {
    background: var(--myavana-sand);
    color: var(--myavana-onyx);
    border: 2px solid var(--myavana-border);
}

.myavana-up-btn-secondary:hover {
    background: var(--myavana-white);
    border-color: var(--myavana-coral);
}

.myavana-up-btn-ghost {
    background: transparent;
    color: var(--myavana-blueberry);
}

.myavana-up-btn-ghost:hover {
    background: var(--myavana-sand);
}

.myavana-up-btn-icon {
    width: 36px;
    height: 36px;
    padding: 0;
    justify-content: center;
    background: var(--myavana-sand);
    color: var(--myavana-blueberry);
}

.myavana-up-btn-icon:hover {
    background: #ffebeb;
    color: #d32f2f;
}

.myavana-up-edit-actions {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    padding-top: 24px;
    border-top: 2px solid var(--myavana-sand);
    position: sticky;
    bottom: 0;
    background: var(--myavana-white);
    margin: 0 -24px -32px;
    padding: 24px;
}

/* Responsive */
@media (max-width: 768px) {
    .myavana-up-edit-panel {
        width: 100%;
    }

    .myavana-up-field-row,
    .myavana-up-checkbox-group {
        grid-template-columns: 1fr;
    }

    .myavana-up-avatar-actions {
        flex-direction: column;
        width: 100%;
    }

    .myavana-up-avatar-actions label,
    .myavana-up-avatar-actions button {
        width: 100%;
        justify-content: center;
    }
}
</style>
