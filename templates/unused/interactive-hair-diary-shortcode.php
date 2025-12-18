<?php
/**
 * Interactive Hair Journey Diary Shortcode
 * Beautiful calendar-based diary with journal-style entries
 */

function myavana_hair_journey_shortcode_new($atts = []) {
    // Parse attributes
    $atts = shortcode_atts([
        'view' => 'calendar', // calendar, journal, grid
        'theme' => 'elegant', // elegant, minimal, modern
        'show_mood_tracking' => 'true',
        'show_weather' => 'true',
        'show_analytics' => 'true'
    ], $atts, 'myavana_hair_journey');
    
    // Authentication check
    if (!is_user_logged_in()) {
        return myavana_render_auth_required('diary');
    }
    
    $user_id = get_current_user_id();
    $user_data = get_userdata($user_id);
    
    // Enqueue assets
    wp_enqueue_style('myavana-hair-diary', MYAVANA_URL . 'assets/css/hair-diary.css', [], '1.0.0');
    wp_enqueue_script('fullcalendar', 'https://unpkg.com/fullcalendar@6.1.8/index.global.min.js', [], '6.1.8', true);
    wp_enqueue_script('myavana-hair-diary', MYAVANA_URL . 'assets/js/hair-diary.js', ['jquery', 'fullcalendar'], '1.0.0', true);
    
    // Localize script
    wp_localize_script('myavana-hair-diary', 'myavanaDiary', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('myavana_diary'),
        'user_id' => $user_id,
        'view' => $atts['view'],
        'theme' => $atts['theme'],
        'features' => [
            'mood_tracking' => $atts['show_mood_tracking'] === 'true',
            'weather' => $atts['show_weather'] === 'true',
            'analytics' => $atts['show_analytics'] === 'true'
        ],
        'strings' => [
            'loading' => __('Loading your diary...', 'myavana'),
            'no_entries' => __('No entries for this date. Click to add your first entry!', 'myavana'),
            'save_success' => __('Entry saved successfully!', 'myavana'),
            'delete_confirm' => __('Are you sure you want to delete this entry?', 'myavana'),
            'mood_placeholder' => __('How are you feeling today?', 'myavana'),
            'notes_placeholder' => __('Share your thoughts about your hair journey...', 'myavana')
        ]
    ]);
    
    ob_start();
    ?>
    <div class="myavana-hair-diary" data-view="<?php echo esc_attr($atts['view']); ?>" data-theme="<?php echo esc_attr($atts['theme']); ?>">
        
        <!-- Diary Header -->
        <header class="diary-header">
            <div class="header-content">
                <div class="diary-branding">
                    <div class="diary-icon">
                        <svg viewBox="0 0 24 24" class="icon">
                            <path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z"/>
                        </svg>
                    </div>
                    <div class="diary-title-section">
                        <h1 class="diary-title myavana-h1">HAIR JOURNEY DIARY</h1>
                        <p class="diary-subtitle myavana-body">Your personal hair care sanctuary</p>
                    </div>
                </div>
                
                <div class="header-actions">
                    <div class="view-switcher">
                        <button class="view-btn active" data-view="calendar" title="Calendar View">
                            <svg viewBox="0 0 24 24"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11z"/></svg>
                        </button>
                        <button class="view-btn" data-view="journal" title="Journal View">
                            <svg viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zm4 18H6V4h7v5h5v11z"/></svg>
                        </button>
                        <button class="view-btn" data-view="grid" title="Grid View">
                            <svg viewBox="0 0 24 24"><path d="M4 11h5V5H4v6zm0 7h5v-6H4v6zm6 0h5v-6h-5v6zm6 0h5v-6h-5v6zm-6-7h5V5h-5v6zm6-6v6h5V5h-5z"/></svg>
                        </button>
                    </div>
                    
                    <div class="header-controls">
                        <button class="myavana-btn-secondary" id="todayBtn">TODAY</button>
                        <button class="myavana-btn-primary" id="addEntryBtn">
                            <svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                            NEW ENTRY
                        </button>
                    </div>
                </div>
            </div>
            
            <?php if ($atts['show_analytics'] === 'true'): ?>
            <div class="diary-stats">
                <div class="stat-item">
                    <div class="stat-icon">📖</div>
                    <div class="stat-info">
                        <span class="stat-number" id="totalEntries">0</span>
                        <span class="stat-label">Entries</span>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">🔥</div>
                    <div class="stat-info">
                        <span class="stat-number" id="currentStreak">0</span>
                        <span class="stat-label">Day Streak</span>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">💚</div>
                    <div class="stat-info">
                        <span class="stat-number" id="avgMood">😊</span>
                        <span class="stat-label">Avg Mood</span>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">⭐</div>
                    <div class="stat-info">
                        <span class="stat-number" id="avgHealth">0</span>
                        <span class="stat-label">Health Score</span>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </header>
        
        <!-- Main Content Area -->
        <main class="diary-main">
            
            <!-- Calendar View -->
            <div class="diary-view active" id="calendar-view">
                <div class="calendar-container">
                    <div class="calendar-header">
                        <div class="calendar-nav">
                            <button class="nav-btn" id="prevMonth">
                                <svg viewBox="0 0 24 24"><path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/></svg>
                            </button>
                            <h2 class="calendar-title" id="calendarTitle">Loading...</h2>
                            <button class="nav-btn" id="nextMonth">
                                <svg viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
                            </button>
                        </div>
                    </div>
                    
                    <div id="calendar" class="calendar-widget"></div>
                </div>
                
                <div class="daily-summary" id="dailySummary">
                    <div class="summary-header">
                        <h3 class="summary-date">Select a date</h3>
                        <button class="btn btn-sm btn-primary" id="addEntryForDate" style="display: none;">
                            Add Entry
                        </button>
                    </div>
                    <div class="summary-content">
                        <div class="empty-state">
                            <div class="empty-icon">📅</div>
                            <p>Click on a date to view or add entries</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Journal View -->
            <div class="diary-view" id="journal-view">
                <div class="journal-container">
                    <div class="journal-sidebar">
                        <div class="journal-filters">
                            <h3>Filter Entries</h3>
                            <div class="filter-group">
                                <label>Date Range</label>
                                <select id="dateFilter">
                                    <option value="all">All Time</option>
                                    <option value="week">This Week</option>
                                    <option value="month">This Month</option>
                                    <option value="3months">Last 3 Months</option>
                                </select>
                            </div>
                            <div class="filter-group">
                                <label>Mood</label>
                                <select id="moodFilter">
                                    <option value="all">All Moods</option>
                                    <option value="😊">😊 Happy</option>
                                    <option value="😌">😌 Content</option>
                                    <option value="😐">😐 Neutral</option>
                                    <option value="😔">😔 Sad</option>
                                    <option value="😤">😤 Frustrated</option>
                                </select>
                            </div>
                            <div class="filter-group">
                                <label>Health Score</label>
                                <select id="healthFilter">
                                    <option value="all">All Scores</option>
                                    <option value="8-10">Excellent (8-10)</option>
                                    <option value="6-7">Good (6-7)</option>
                                    <option value="4-5">Fair (4-5)</option>
                                    <option value="1-3">Needs Work (1-3)</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="journal-tags" id="journalTags">
                            <h3>Popular Tags</h3>
                            <!-- Tags will be loaded dynamically -->
                        </div>
                    </div>
                    
                    <div class="journal-entries" id="journalEntries">
                        <div class="loading-state">
                            <div class="spinner"></div>
                            <p>Loading your journal entries...</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Grid View -->
            <div class="diary-view" id="grid-view">
                <div class="grid-container">
                    <div class="grid-controls">
                        <div class="sort-controls">
                            <label>Sort by:</label>
                            <select id="gridSort">
                                <option value="date_desc">Newest First</option>
                                <option value="date_asc">Oldest First</option>
                                <option value="mood">Mood</option>
                                <option value="health">Health Score</option>
                            </select>
                        </div>
                        
                        <div class="grid-size-controls">
                            <label>Grid Size:</label>
                            <div class="size-buttons">
                                <button class="size-btn active" data-size="medium">M</button>
                                <button class="size-btn" data-size="large">L</button>
                                <button class="size-btn" data-size="small">S</button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="entries-grid" id="entriesGrid">
                        <div class="loading-state">
                            <div class="spinner"></div>
                            <p>Loading your entries...</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        
        <!-- Entry Modal -->
        <div class="modal" id="entryModal">
            <div class="modal-backdrop"></div>
            <div class="modal-content entry-modal-content">
                <div class="modal-header">
                    <h3 class="modal-title myavana-subheader" id="entryModalTitle">NEW ENTRY</h3>
                    <button class="modal-close" id="closeEntryModal">
                        <svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
                    </button>
                </div>
                
                <div class="modal-body">
                    <form id="entryForm" class="entry-form">
                        <input type="hidden" id="entryId" name="entry_id">
                        <input type="hidden" id="entryDate" name="entry_date">
                        
                        <div class="form-section">
                            <div class="section-header">
                                <h4>✍️ Journal Entry</h4>
                            </div>
                            
                            <div class="form-group">
                                <label for="entryTitle" class="form-label">Title</label>
                                <input type="text" id="entryTitle" name="title" class="form-input" placeholder="Give your entry a title..." required>
                            </div>
                            
                            <div class="form-group">
                                <label for="entryDescription" class="form-label">Your Thoughts</label>
                                <textarea id="entryDescription" name="description" class="form-textarea" rows="4" placeholder="Share your hair journey thoughts, experiences, and observations..."></textarea>
                                <div class="character-count">
                                    <span id="descriptionCount">0</span>/500 characters
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <div class="section-header">
                                <h4>📸 Photo & Products</h4>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Photo</label>
                                <div class="photo-upload" id="photoUpload">
                                    <input type="file" id="photoInput" name="photo" accept="image/*" style="display: none;">
                                    <div class="upload-area" id="uploadArea">
                                        <svg viewBox="0 0 24 24" class="upload-icon">
                                            <path d="M9 16h6v-6h4l-7-7-7 7h4zm-4 2h14v2H5z"/>
                                        </svg>
                                        <p>Click to upload or drag & drop a photo</p>
                                    </div>
                                    <div class="photo-preview" id="photoPreview" style="display: none;">
                                        <img id="previewImage" src="" alt="Preview">
                                        <button type="button" class="remove-photo" id="removePhoto">×</button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="productsUsed" class="form-label">Products Used</label>
                                <textarea id="productsUsed" name="products" class="form-textarea" rows="2" placeholder="List the products you used today..."></textarea>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <div class="section-header">
                                <h4>💚 Health & Mood</h4>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Hair Health (1-10)</label>
                                    <div class="health-rating">
                                        <div class="rating-scale">
                                            <?php for ($i = 1; $i <= 10; $i++): ?>
                                            <label class="rating-item">
                                                <input type="radio" name="rating" value="<?php echo $i; ?>" <?php echo $i === 5 ? 'checked' : ''; ?>>
                                                <span class="rating-number"><?php echo $i; ?></span>
                                            </label>
                                            <?php endfor; ?>
                                        </div>
                                        <div class="rating-labels">
                                            <span>Needs Work</span>
                                            <span>Excellent</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php if ($atts['show_mood_tracking'] === 'true'): ?>
                                <div class="form-group">
                                    <label class="form-label">Today's Mood</label>
                                    <div class="mood-selector">
                                        <label class="mood-item">
                                            <input type="radio" name="mood_demeanor" value="😊" checked>
                                            <span class="mood-emoji">😊</span>
                                        </label>
                                        <label class="mood-item">
                                            <input type="radio" name="mood_demeanor" value="😌">
                                            <span class="mood-emoji">😌</span>
                                        </label>
                                        <label class="mood-item">
                                            <input type="radio" name="mood_demeanor" value="😐">
                                            <span class="mood-emoji">😐</span>
                                        </label>
                                        <label class="mood-item">
                                            <input type="radio" name="mood_demeanor" value="😔">
                                            <span class="mood-emoji">😔</span>
                                        </label>
                                        <label class="mood-item">
                                            <input type="radio" name="mood_demeanor" value="😤">
                                            <span class="mood-emoji">😤</span>
                                        </label>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <div class="section-header">
                                <h4>🌟 Additional Notes</h4>
                            </div>
                            
                            <div class="form-group">
                                <label for="stylistNotes" class="form-label">Notes & Observations</label>
                                <textarea id="stylistNotes" name="notes" class="form-textarea" rows="3" placeholder="Any additional thoughts, observations, or notes about your hair journey..."></textarea>
                            </div>
                            
                            <?php if ($atts['show_weather'] === 'true'): ?>
                            <div class="form-group">
                                <label for="environment" class="form-label">Environment & Weather</label>
                                <input type="text" id="environment" name="environment" class="form-input" placeholder="Weather conditions, humidity, etc.">
                            </div>
                            <?php endif; ?>
                            
                            <div class="form-group">
                                <label for="entryTags" class="form-label">Tags</label>
                                <input type="text" id="entryTags" name="tags" class="form-input" placeholder="Add tags separated by commas (e.g., wash day, deep condition, trim)">
                                <small class="form-help">Tags help you organize and find entries later</small>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="button" class="myavana-btn-secondary" id="cancelEntry">CANCEL</button>
                            <button type="submit" class="myavana-btn-primary" id="saveEntry">
                                <span class="btn-text">SAVE ENTRY</span>
                                <div class="btn-loader" style="display: none;">
                                    <div class="spinner"></div>
                                </div>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Entry Detail Modal -->
        <div class="modal" id="entryDetailModal">
            <div class="modal-backdrop"></div>
            <div class="modal-content entry-detail-content">
                <div class="modal-header">
                    <h3 class="modal-title" id="detailModalTitle">Entry Details</h3>
                    <div class="detail-actions">
                        <button class="btn btn-sm btn-secondary" id="editEntryBtn">Edit</button>
                        <button class="btn btn-sm btn-danger" id="deleteEntryBtn">Delete</button>
                        <button class="modal-close" id="closeDetailModal">×</button>
                    </div>
                </div>
                <div class="modal-body" id="entryDetailContent">
                    <!-- Entry details will be loaded here -->
                </div>
            </div>
        </div>
        
    </div>
    
    <style>
    /* Quick inline styles for immediate visibility */
    .myavana-hair-diary {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
        max-width: 100% !important;
        margin: 0 auto !important;
        background: #f8f9fa !important;
        min-height: 100vh !important;
    }
    
    .diary-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        padding: 2rem !important;
        color: white !important;
        border-radius: 0 0 1rem 1rem !important;
        margin-bottom: 2rem !important;
    }
    
    .diary-title {
        font-size: 2.5rem !important;
        font-weight: 700 !important;
        margin: 0 !important;
        text-shadow: 0 2px 4px rgba(0,0,0,0.3) !important;
    }
    
    .view-btn.active {
        background: rgba(255,255,255,0.3) !important;
        border-radius: 0.5rem !important;
    }
    </style>
    
    <?php
    return ob_get_clean();
}

// Register the shortcode
add_shortcode('myavana_hair_journey_new', 'myavana_hair_journey_shortcode_new');

// Helper function for auth required message (only if not already defined)
if (!function_exists('myavana_render_auth_required')) {
    function myavana_render_auth_required($context = 'feature') {
        return '<div class="myavana-auth-required">
            <div class="auth-card">
                <h3>🔒 Authentication Required</h3>
                <p>Please log in to access your hair journey ' . esc_html($context) . '.</p>
                <a href="' . wp_login_url(get_permalink()) . '" class="btn btn-primary">Login</a>
            </div>
        </div>';
    }
}

// AJAX handler for getting diary entries for calendar
add_action('wp_ajax_get_diary_calendar_entries', 'myavana_get_diary_calendar_entries');
function myavana_get_diary_calendar_entries() {
    check_ajax_referer('myavana_diary', 'security');
    
    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error('User not logged in');
        return;
    }
    
    $start = sanitize_text_field($_POST['start']);
    $end = sanitize_text_field($_POST['end']);
    
    $entries = new WP_Query([
        'post_type' => 'hair_journey_entry',
        'author' => $user_id,
        'date_query' => [
            [
                'after' => $start,
                'before' => $end,
                'inclusive' => true
            ]
        ],
        'posts_per_page' => -1,
        'orderby' => 'date',
        'order' => 'ASC'
    ]);
    
    $events = [];
    if ($entries->have_posts()) {
        while ($entries->have_posts()) {
            $entries->the_post();
            $post_id = get_the_ID();
            
            $mood = get_post_meta($post_id, 'mood_demeanor', true);
            $health_rating = get_post_meta($post_id, 'health_rating', true);
            
            $events[] = [
                'id' => $post_id,
                'title' => get_the_title(),
                'start' => get_the_date('Y-m-d'),
                'description' => get_post_meta($post_id, 'description', true),
                'mood' => $mood,
                'health_rating' => intval($health_rating),
                'image' => get_the_post_thumbnail_url($post_id, 'thumbnail'),
                'className' => 'hair-entry-event mood-' . str_replace(['😊','😌','😐','😔','😤'], ['happy','content','neutral','sad','frustrated'], $mood)
            ];
        }
        wp_reset_postdata();
    }
    
    wp_send_json_success(['events' => $events]);
}
?>