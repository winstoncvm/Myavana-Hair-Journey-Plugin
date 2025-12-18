<?php
/**
 * MYAVANA Hair Journey Diary - Completely Redesigned
 * Modern calendar interface with MYAVANA branding and full functionality
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Hair Journey Diary Shortcode Handler
 */
function hair_journey_diary_shortcode($atts = []) {
    // Parse shortcode attributes
    $atts = shortcode_atts([
        'user_id' => get_current_user_id()
    ], $atts);

    $user_id = intval($atts['user_id']);
    $is_owner = $user_id === get_current_user_id();

    // Check if user is logged in
    if (!is_user_logged_in()) {
        return '<div class="myavana-auth-required">
            <div class="myavana-card">
                <h3>Hair Journey Diary</h3>
                <p>Please <a href="' . wp_login_url(get_permalink()) . '">log in</a> to access your hair journey diary.</p>
            </div>
        </div>';
    }

    // Check if user can view this diary
    if (!$is_owner && !current_user_can('manage_options')) {
        return '<div class="myavana-access-denied">
            <div class="myavana-card">
                <h3>Access Restricted</h3>
                <p>You can only view your own hair journey diary.</p>
            </div>
        </div>';  
    }

    // Enqueue assets
    wp_enqueue_style('myavana-hair-diary-css', plugin_dir_url(__FILE__) . '../assets/css/hair-diary-new.css', [], '1.0.0');
    wp_enqueue_script('myavana-hair-diary-js', plugin_dir_url(__FILE__) . '../assets/js/hair-diary-new.js', ['jquery'], '1.0.0', true);

    // Localize script for AJAX
    wp_localize_script('myavana-hair-diary-js', 'myavanaHairDiary', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonces' => [
            'get_entries' => wp_create_nonce('myavana_hair_diary_nonce'),
            'save_entry' => wp_create_nonce('myavana_hair_diary_nonce'),
            'delete_entry' => wp_create_nonce('myavana_hair_diary_nonce'),
            'get_entry' => wp_create_nonce('myavana_hair_diary_nonce')
        ],
        'user_id' => $user_id,
        'is_owner' => $is_owner,
        'current_user_id' => get_current_user_id(),
        'actions' => [
            'get_entries' => 'myavana_get_diary_entries2',
            'save_entry' => 'myavana_save_diary_entry',
            'delete_entry' => 'myavana_delete_diary_entry',
            'get_entry' => 'myavana_get_single_diary_entry'
        ]
    ]);

    ob_start();
    ?>
    <div class="myavana-hair-diary-container">
        <!-- Header Section -->
        <header class="myavana-diary-header">
            <div class="myavana-header-content">
                <div class="myavana-logo-section">
                    <img src="<?php echo esc_url(home_url()); ?>/wp-content/plugins/myavana-hair-journey/assets/images/myavana-primary-logo.png"
                         alt="Myavana Logo" class="myavana-logo" />
                </div>
                <div class="myavana-header-text">
                    <h1 class="myavana-h1">HAIR JOURNEY</h1>
                    <p class="myavana-preheader">Track Your Beautiful Transformation</p>
                </div>
                <div class="myavana-header-actions">
                    <button class="myavana-btn-primary" id="addEntryBtn">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        Add Entry
                    </button>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <div class="myavana-diary-main">
            <!-- Left Sidebar - Stats & Quick Actions -->
            <aside class="myavana-diary-sidebar">
                <div class="myavana-stats-card myavana-card">
                    <h3 class="myavana-subheader">Your Progress</h3>
                    <div class="myavana-stats-row">
                        <div class="myavana-stat-item">
                            <div class="myavana-stat-number" id="totalEntries">0</div>
                            <div class="myavana-stat-label">Total Entries</div>
                        </div>
                        <div class="myavana-stat-item">
                            <div class="myavana-stat-number" id="thisMonthEntries">0</div>
                            <div class="myavana-stat-label">This Month</div>
                        </div>
                    </div>
                    <div class="myavana-stats-row">
                        <div class="myavana-stat-item">
                            <div class="myavana-stat-number" id="averageRating">0.0</div>
                            <div class="myavana-stat-label">Avg Health</div>
                        </div>
                        <div class="myavana-stat-item">
                            <div class="myavana-stat-number" id="currentStreak">0</div>
                            <div class="myavana-stat-label">Day Streak</div>
                        </div>
                    </div>
                </div>

                <div class="myavana-quick-actions-card myavana-card">
                    <h3 class="myavana-subheader">Quick Actions</h3>
                    <div class="myavana-quick-actions">
                        <button class="myavana-quick-btn" data-entry-type="wash">
                            <div class="myavana-quick-icon">💧</div>
                            <span>Wash Day</span>
                        </button>
                        <button class="myavana-quick-btn" data-entry-type="treatment">
                            <div class="myavana-quick-icon">✨</div>
                            <span>Treatment</span>
                        </button>
                        <button class="myavana-quick-btn" data-entry-type="styling">
                            <div class="myavana-quick-icon">🎨</div>
                            <span>Styling</span>
                        </button>
                        <button class="myavana-quick-btn" data-entry-type="progress">
                            <div class="myavana-quick-icon">📸</div>
                            <span>Progress</span>
                        </button>
                    </div>
                </div>

                <div class="myavana-legend-card myavana-card">
                    <h3 class="myavana-subheader">Entry Types</h3>
                    <div class="myavana-legend">
                        <div class="myavana-legend-item">
                            <div class="myavana-legend-dot wash"></div>
                            <span>Wash Day</span>
                        </div>
                        <div class="myavana-legend-item">
                            <div class="myavana-legend-dot treatment"></div>
                            <span>Treatment</span>
                        </div>
                        <div class="myavana-legend-item">
                            <div class="myavana-legend-dot styling"></div>
                            <span>Styling</span>
                        </div>
                        <div class="myavana-legend-item">
                            <div class="myavana-legend-dot progress"></div>
                            <span>Progress Photo</span>
                        </div>
                        <div class="myavana-legend-item">
                            <div class="myavana-legend-dot general"></div>
                            <span>General</span>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- Center Content - Calendar -->
            <main class="myavana-diary-content">
                <div class="myavana-calendar-container myavana-card">
                    <div class="myavana-calendar-header">
                        <button class="myavana-calendar-nav" id="prevMonthBtn" aria-label="Previous Month">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="15,18 9,12 15,6"></polyline>
                            </svg>
                        </button>
                        <div class="myavana-calendar-title">
                            <h2 class="myavana-calendar-month" id="calendarMonth">January 2024</h2>
                        </div>
                        <button class="myavana-calendar-nav" id="nextMonthBtn" aria-label="Next Month">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="9,18 15,12 9,6"></polyline>
                            </svg>
                        </button>
                    </div>

                    <div class="myavana-calendar-grid" id="calendarGrid">
                        <!-- Calendar will be rendered here by JavaScript -->
                    </div>
                </div>

                <!-- Entry Details Panel -->
                <div class="myavana-entry-details" id="entryDetails" style="display: none;">
                    <div class="myavana-card">
                        <div class="myavana-entry-header">
                            <h3 id="entryDetailsTitle">Entry Details</h3>
                            <button class="myavana-close-btn" id="closeDetailsBtn">×</button>
                        </div>
                        <div class="myavana-entry-content" id="entryDetailsContent">
                            <!-- Entry details will be populated here -->
                        </div>
                    </div>
                </div>
            </main>
        </div>

        <!-- Entry Form Modal -->
        <div class="myavana-modal-overlay" id="entryModal" style="display: none;">
            <div class="mya-modal">
                <div class="mya-modal-header">
                    <h2 class="mya-modal-title" id="modalTitle">Add Hair Journey Entry</h2>
                    <button class="myavana-close-btn" id="closeModalBtn">×</button>
                </div>
                <div class="mya-modal-body">
                    <form id="entryForm" class="myavana-entry-form">
                        <input type="hidden" id="entryId" name="entry_id" value="">
                        <input type="hidden" id="entryDate" name="entry_date" value="">

                        <div class="myavana-form-group">
                            <label class="myavana-form-label" for="entryTitle">Entry Title *</label>
                            <input type="text" id="entryTitle" name="title" class="myavana-form-input"
                                   placeholder="e.g., Wash day with new products" required>
                        </div>

                        <div class="myavana-form-group">
                            <label class="myavana-form-label" for="entryType">Entry Type *</label>
                            <select id="entryType" name="entry_type" class="myavana-form-select" required>
                                <option value="">Select entry type</option>
                                <option value="wash">Wash Day</option>
                                <option value="treatment">Treatment</option>
                                <option value="styling">Styling</option>
                                <option value="progress">Progress Photo</option>
                                <option value="general">General</option>
                            </select>
                        </div>

                        <div class="myavana-form-group">
                            <label class="myavana-form-label" for="entryDescription">Description</label>
                            <textarea id="entryDescription" name="description" class="myavana-form-textarea"
                                      rows="4" placeholder="Describe your hair journey moment..."></textarea>
                        </div>

                        <div class="myavana-form-row">
                            <div class="myavana-form-group">
                                <label class="myavana-form-label" for="healthRating">Hair Health (1-10)</label>
                                <div class="myavana-rating-input">
                                    <input type="range" id="healthRating" name="health_rating"
                                           min="1" max="10" value="5" class="myavana-range-input">
                                    <div class="myavana-rating-display">
                                        <span id="ratingValue">5</span>/10
                                    </div>
                                </div>
                            </div>

                            <div class="myavana-form-group">
                                <label class="myavana-form-label" for="moodRating">How You Feel</label>
                                <select id="moodRating" name="mood" class="myavana-form-select">
                                    <option value="excited">😊 Excited</option>
                                    <option value="happy">😄 Happy</option>
                                    <option value="content">😌 Content</option>
                                    <option value="neutral">😐 Neutral</option>
                                    <option value="concerned">😟 Concerned</option>
                                    <option value="frustrated">😤 Frustrated</option>
                                </select>
                            </div>
                        </div>

                        <div class="myavana-form-group">
                            <label class="myavana-form-label" for="productsUsed">Products Used</label>
                            <input type="text" id="productsUsed" name="products" class="myavana-form-input"
                                   placeholder="e.g., Moisturizing shampoo, leave-in conditioner">
                        </div>

                        <div class="myavana-form-group">
                            <label class="myavana-form-label" for="entryNotes">Notes & Observations</label>
                            <textarea id="entryNotes" name="notes" class="myavana-form-textarea"
                                      rows="3" placeholder="Any additional notes or observations..."></textarea>
                        </div>

                        <div class="myavana-form-group">
                            <label class="myavana-form-label" for="entryPhoto">Upload Photo</label>
                            <div class="myavana-file-upload">
                                <input type="file" id="entryPhoto" name="photo" accept="image/*" class="myavana-file-input">
                                <label for="entryPhoto" class="myavana-file-label">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                                        <circle cx="8.5" cy="8.5" r="1.5"/>
                                        <polyline points="21,15 16,10 5,21"/>
                                    </svg>
                                    <span>Choose photo or drag & drop</span>
                                </label>
                                <div class="myavana-file-preview" id="photoPreview" style="display: none;"></div>
                            </div>
                        </div>

                        <div class="myavana-form-actions">
                            <button type="button" class="myavana-btn-secondary" id="cancelBtn">Cancel</button>
                            <button type="submit" class="myavana-btn-primary" id="saveBtn">
                                <span class="myavana-btn-text">Save Entry</span>
                                <span class="myavana-btn-loading" style="display: none;">
                                    <svg class="myavana-spinner" width="16" height="16" viewBox="0 0 24 24">
                                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/>
                                        <path d="M12,2 A10,10 0 0,1 22,12" stroke="currentColor" stroke-width="4" fill="none"/>
                                    </svg>
                                    Saving...
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Toast Notifications -->
        <div class="myavana-toast-container" id="toastContainer"></div>
    </div>
    <?php
    return ob_get_clean();
}

// Register the shortcode
add_shortcode('hair_journey_diary', 'hair_journey_diary_shortcode');

/**
 * AJAX Handler - Get Hair Journey Entries
 */
function myavana_get_diary_entries_handler2() {
    // Verify nonce
    if (!wp_verify_nonce($_REQUEST['nonce'], 'myavana_hair_diary_nonce')) {
        wp_send_json_error('Security check failed');
        return;
    }

    $user_id = intval($_REQUEST['user_id']);
    $current_user_id = get_current_user_id();

    // Check permissions
    if (!$user_id || ($user_id !== $current_user_id && !current_user_can('manage_options'))) {
        wp_send_json_error('Access denied');
        return;
    }

    // Get entries from custom post type
    $args = [
        'post_type' => 'hair_journey_entry',
        'post_status' => 'publish',
        'author' => $user_id,
        'posts_per_page' => -1,
        'orderby' => 'date',
        'order' => 'DESC',
        'meta_query' => [
            'relation' => 'OR',
            [
                'key' => 'entry_type',
                'compare' => 'EXISTS'
            ],
            [
                'key' => 'entry_type',
                'compare' => 'NOT EXISTS'
            ]
        ]
    ];

    $posts = get_posts($args);
    $entries = [];

    foreach ($posts as $post) {
        $entry_data = [
            'id' => $post->ID,
            'title' => $post->post_title,
            'description' => $post->post_content,
            'date' => get_the_date('Y-m-d', $post),
            'formatted_date' => get_the_date('F j, Y', $post),
            'timestamp' => $post->post_date,
            'entry_type' => get_post_meta($post->ID, 'entry_type', true) ?: 'general',
            'health_rating' => intval(get_post_meta($post->ID, 'health_rating', true) ?: 5),
            'mood' => get_post_meta($post->ID, 'mood', true) ?: 'neutral',
            'products' => get_post_meta($post->ID, 'products', true) ?: '',
            'notes' => get_post_meta($post->ID, 'notes', true) ?: '',
            'image' => get_the_post_thumbnail_url($post->ID, 'medium') ?: '',
            'thumbnail' => get_the_post_thumbnail_url($post->ID, 'thumbnail') ?: ''
        ];
        $entries[] = $entry_data;
    }

    wp_send_json_success($entries);
}
add_action('wp_ajax_myavana_get_diary_entries2', 'myavana_get_diary_entries_handler2');

/**
 * AJAX Handler - Save Hair Journey Entry
 */
function myavana_save_diary_entry_handler() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'myavana_hair_diary_nonce')) {
        wp_send_json_error('Security check failed');
        return;
    }

    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error('User not logged in');
        return;
    }

    // Sanitize input data
    $entry_id = intval($_POST['entry_id'] ?? 0);
    $title = sanitize_text_field($_POST['title'] ?? '');
    $description = sanitize_textarea_field($_POST['description'] ?? '');
    $entry_type = sanitize_text_field($_POST['entry_type'] ?? 'general');
    $entry_date = sanitize_text_field($_POST['entry_date'] ?? date('Y-m-d'));
    $health_rating = intval($_POST['health_rating'] ?? 5);
    $mood = sanitize_text_field($_POST['mood'] ?? 'neutral');
    $products = sanitize_text_field($_POST['products'] ?? '');
    $notes = sanitize_textarea_field($_POST['notes'] ?? '');

    // Validate required fields
    if (empty($title)) {
        wp_send_json_error('Title is required');
        return;
    }

    try {
        if ($entry_id > 0) {
            // Update existing entry
            $post_data = [
                'ID' => $entry_id,
                'post_title' => $title,
                'post_content' => $description,
                'post_type' => 'hair_journey_entry',
                'post_status' => 'publish',
                'post_author' => $user_id
            ];

            // Verify ownership
            $existing_post = get_post($entry_id);
            if (!$existing_post || $existing_post->post_author != $user_id) {
                wp_send_json_error('Access denied');
                return;
            }

            $result = wp_update_post($post_data);
        } else {
            // Create new entry
            $post_data = [
                'post_title' => $title,
                'post_content' => $description,
                'post_type' => 'hair_journey_entry',
                'post_status' => 'publish',
                'post_author' => $user_id,
                'post_date' => date('Y-m-d H:i:s', strtotime($entry_date))
            ];

            $result = wp_insert_post($post_data);
            $entry_id = $result;
        }

        if (is_wp_error($result)) {
            wp_send_json_error('Database error: ' . $result->get_error_message());
            return;
        }

        // Update meta fields
        update_post_meta($entry_id, 'entry_type', $entry_type);
        update_post_meta($entry_id, 'health_rating', $health_rating);
        update_post_meta($entry_id, 'mood', $mood);
        update_post_meta($entry_id, 'products', $products);
        update_post_meta($entry_id, 'notes', $notes);

        // Handle photo upload
        if (!empty($_FILES['photo']['name'])) {
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';

            // Delete old attachment if updating
            $old_thumbnail_id = get_post_thumbnail_id($entry_id);
            if ($old_thumbnail_id) {
                wp_delete_attachment($old_thumbnail_id, true);
            }

            // Upload new photo
            $upload = wp_handle_upload($_FILES['photo'], ['test_form' => false]);

            if ($upload && !isset($upload['error'])) {
                $attachment_data = [
                    'post_mime_type' => $upload['type'],
                    'post_title' => sanitize_file_name($title . ' - Hair Journey Photo'),
                    'post_content' => '',
                    'post_status' => 'inherit',
                    'post_author' => $user_id
                ];

                $attachment_id = wp_insert_attachment($attachment_data, $upload['file'], $entry_id);

                if (!is_wp_error($attachment_id)) {
                    $attachment_metadata = wp_generate_attachment_metadata($attachment_id, $upload['file']);
                    wp_update_attachment_metadata($attachment_id, $attachment_metadata);
                    set_post_thumbnail($entry_id, $attachment_id);
                }
            }
        }

        wp_send_json_success([
            'message' => 'Entry saved successfully!',
            'entry_id' => $entry_id,
            'entry' => [
                'id' => $entry_id,
                'title' => $title,
                'description' => $description,
                'date' => $entry_date,
                'entry_type' => $entry_type,
                'health_rating' => $health_rating,
                'mood' => $mood,
                'products' => $products,
                'notes' => $notes,
                'image' => get_the_post_thumbnail_url($entry_id, 'medium') ?: ''
            ]
        ]);

    } catch (Exception $e) {
        wp_send_json_error('Error saving entry: ' . $e->getMessage());
    }
}
add_action('wp_ajax_myavana_save_diary_entry', 'myavana_save_diary_entry_handler');

/**
 * AJAX Handler - Delete Hair Journey Entry
 */
function myavana_delete_diary_entry_handler() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'myavana_hair_diary_nonce')) {
        wp_send_json_error('Security check failed');
        return;
    }

    $user_id = get_current_user_id();
    $entry_id = intval($_POST['entry_id'] ?? 0);

    if (!$user_id || !$entry_id) {
        wp_send_json_error('Invalid request');
        return;
    }

    // Verify ownership
    $post = get_post($entry_id);
    if (!$post || $post->post_author != $user_id || $post->post_type !== 'hair_journey_entry') {
        wp_send_json_error('Access denied');
        return;
    }

    // Delete associated image
    $thumbnail_id = get_post_thumbnail_id($entry_id);
    if ($thumbnail_id) {
        wp_delete_attachment($thumbnail_id, true);
    }

    // Delete the entry
    $deleted = wp_delete_post($entry_id, true);

    if ($deleted) {
        wp_send_json_success([
            'message' => 'Entry deleted successfully!',
            'entry_id' => $entry_id
        ]);
    } else {
        wp_send_json_error('Failed to delete entry');
    }
}
add_action('wp_ajax_myavana_delete_diary_entry', 'myavana_delete_diary_entry_handler');

/**
 * AJAX Handler - Get Single Hair Journey Entry
 */
function myavana_get_single_diary_entry_handler() {
    // Check authentication first
    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error('User not logged in');
        return;
    }

    // Get nonce from POST or REQUEST
    $nonce = $_POST['nonce'] ?? $_REQUEST['nonce'] ?? '';

    // Verify nonce
    if (!wp_verify_nonce($nonce, 'myavana_hair_diary_nonce')) {
        error_log('MYAVANA: Nonce verification failed for get_single_diary_entry. Nonce: ' . $nonce);
        wp_send_json_error('Security check failed');
        return;
    }

    $entry_id = intval($_POST['entry_id'] ?? $_REQUEST['entry_id'] ?? 0);

    if (!$entry_id) {
        wp_send_json_error('Invalid request - no entry ID provided');
        return;
    }

    // Get the entry
    $post = get_post($entry_id);

    // Verify ownership and post type
    if (!$post || $post->post_type !== 'hair_journey_entry') {
        wp_send_json_error('Entry not found');
        return;
    }

    // Check permissions
    if ($post->post_author != $user_id && !current_user_can('manage_options')) {
        wp_send_json_error('Access denied - not your entry');
        return;
    }

    $entry_data = [
        'id' => $post->ID,
        'title' => $post->post_title,
        'description' => $post->post_content,
        'date' => get_the_date('Y-m-d', $post),
        'formatted_date' => get_the_date('F j, Y', $post),
        'entry_type' => get_post_meta($post->ID, 'entry_type', true) ?: 'general',
        'health_rating' => intval(get_post_meta($post->ID, 'health_rating', true) ?: 5),
        'mood' => get_post_meta($post->ID, 'mood', true) ?: 'neutral',
        'products' => get_post_meta($post->ID, 'products', true) ?: '',
        'notes' => get_post_meta($post->ID, 'notes', true) ?: '',
        'image' => get_the_post_thumbnail_url($post->ID, 'large') ?: '',
        'thumbnail' => get_the_post_thumbnail_url($post->ID, 'thumbnail') ?: ''
    ];

    wp_send_json_success($entry_data);
}
add_action('wp_ajax_myavana_get_single_diary_entry', 'myavana_get_single_diary_entry_handler');

/**
 * AJAX Handler - Get Hair Journey Entries for Calendar
 * Returns raw entry data suitable for calendar display
 */
function myavana_get_diary_entries_handler() {
    // Verify nonce
    if (!wp_verify_nonce($_REQUEST['security'] ?? '', 'myavana_get_entries')) {
        wp_send_json_error('Security check failed');
        return;
    }

    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error('User not logged in');
        return;
    }

    // Get all entries for the user
    $query = new WP_Query([
        'post_type' => 'hair_journey_entry',
        'author' => $user_id,
        'posts_per_page' => -1,
        'orderby' => 'date',
        'order' => 'DESC',
        'post_status' => 'publish'
    ]);

    $entries = [];
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();

            $entries[] = [
                'id' => $post_id,
                'title' => get_the_title(),
                'description' => get_the_excerpt(),
                'content' => get_the_content(),
                'date' => get_the_date('Y-m-d'),
                'formatted_date' => get_the_date('F j, Y'),
                'entry_type' => get_post_meta($post_id, 'entry_type', true) ?: 'general',
                'health_rating' => intval(get_post_meta($post_id, 'health_rating', true) ?: 5),
                'mood' => get_post_meta($post_id, 'mood', true) ?: 'neutral',
                'products' => get_post_meta($post_id, 'products', true) ?: '',
                'notes' => get_post_meta($post_id, 'notes', true) ?: '',
                'image' => get_the_post_thumbnail_url($post_id, 'large') ?: '',
                'thumbnail' => get_the_post_thumbnail_url($post_id, 'thumbnail') ?: ''
            ];
        }
        wp_reset_postdata();
    }

    // Calculate statistics
    $total_entries = count($entries);
    $avg_health_rating = 0;
    $entry_types = [];

    if ($total_entries > 0) {
        $health_sum = 0;
        foreach ($entries as $entry) {
            $health_sum += $entry['health_rating'];
            $type = $entry['entry_type'];
            $entry_types[$type] = ($entry_types[$type] ?? 0) + 1;
        }
        $avg_health_rating = round($health_sum / $total_entries, 1);
    }

    wp_send_json_success([
        'entries' => $entries,
        'stats' => [
            'total_entries' => $total_entries,
            'avg_health_rating' => $avg_health_rating,
            'entry_types' => $entry_types,
            'latest_entry' => !empty($entries) ? $entries[0]['date'] : null
        ]
    ]);
}
add_action('wp_ajax_myavana_get_diary_entries', 'myavana_get_diary_entries_handler');
?>