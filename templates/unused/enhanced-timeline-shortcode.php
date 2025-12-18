<?php
/**
 * Enhanced Myavana Hair Journey Timeline Shortcode
 * Modern, responsive, accessible design with improved functionality
 */

function myavana_enhanced_timeline_shortcode($atts = []) {
    // Parse attributes
    $atts = shortcode_atts([
        'view' => 'timeline', // timeline, grid, cards
        'entries_per_page' => 12,
        'show_filters' => 'true',
        'show_search' => 'true',
        'show_analytics' => 'true'
    ], $atts, 'myavana_enhanced_timeline');
    
    // Check authentication
    if (!is_user_logged_in()) {
        return '<div class="myavana-auth-required">
            <div class="auth-card">
                <h3>🔒 Login Required</h3>
                <p>Please log in to view your hair journey timeline.</p>
                <a href="' . wp_login_url(get_permalink()) . '" class="auth-btn">Login</a>
            </div>
        </div>';
    }
    
    // Enqueue enhanced assets
    wp_enqueue_style('myavana-enhanced-timeline', MYAVANA_URL . 'assets/css/enhanced-timeline.css', [], '1.0.0');
    wp_enqueue_script('myavana-enhanced-timeline', MYAVANA_URL . 'assets/js/enhanced-timeline.js', ['jquery'], '1.0.0', true);
    
    // Localize script
    wp_localize_script('myavana-enhanced-timeline', 'myavanaTimeline', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('myavana_diary'),
        'user_id' => get_current_user_id(),
        'entries_per_page' => intval($atts['entries_per_page']),
        'strings' => [
            'loading' => __('Loading your hair journey...', 'myavana'),
            'no_entries' => __('No entries found. Start your journey!', 'myavana'),
            'error' => __('Error loading timeline. Please try again.', 'myavana'),
            'delete_confirm' => __('Are you sure you want to delete this entry?', 'myavana'),
            'save_success' => __('Entry saved successfully!', 'myavana')
        ]
    ]);
    
    ob_start();
    ?>
    <div class="myavana-enhanced-timeline" data-view="<?php echo esc_attr($atts['view']); ?>">
        
        <!-- Header Section -->
        <header class="timeline-header">
            <div class="header-content">
                <div class="brand-section">
                    <img src="<?php echo esc_url(MYAVANA_URL . 'assets/images/myavana-primary-logo.png'); ?>" 
                         alt="Myavana Logo" class="brand-logo" width="120" height="40">
                    <div class="header-text">
                        <h1 class="timeline-title">✨ Your Hair Journey ✨</h1>
                        <p class="timeline-subtitle">Track, analyze, and celebrate your progress</p>
                    </div>
                </div>
                
                <?php if ($atts['show_analytics'] === 'true'): ?>
                <div class="quick-stats" id="quickStats">
                    <div class="stat-card">
                        <div class="stat-number" id="totalEntries">0</div>
                        <div class="stat-label">Total Entries</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" id="avgHealth">0</div>
                        <div class="stat-label">Avg Health</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" id="improvement">0%</div>
                        <div class="stat-label">Improvement</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" id="streak">0</div>
                        <div class="stat-label">Day Streak</div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </header>
        
        <!-- Controls Section -->
        <div class="timeline-controls">
            <div class="controls-left">
                <?php if ($atts['show_search'] === 'true'): ?>
                <div class="search-container">
                    <input type="search" id="timelineSearch" placeholder="Search your entries..." 
                           class="search-input" aria-label="Search timeline entries">
                    <button type="button" class="search-btn" aria-label="Search">
                        <svg class="search-icon" viewBox="0 0 24 24">
                            <path d="M15.5 14h-.79l-.28-.27a6.5 6.5 0 1 0-.7.7l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0A4.5 4.5 0 1 1 14 9.5 4.5 4.5 0 0 1 9.5 14z"/>
                        </svg>
                    </button>
                </div>
                <?php endif; ?>
                
                <?php if ($atts['show_filters'] === 'true'): ?>
                <div class="filter-container">
                    <select id="moodFilter" class="filter-select" aria-label="Filter by mood">
                        <option value="">All Moods</option>
                        <option value="excited">Excited</option>
                        <option value="happy">Happy</option>
                        <option value="optimistic">Optimistic</option>
                        <option value="nervous">Nervous</option>
                        <option value="determined">Determined</option>
                    </select>
                    
                    <select id="ratingFilter" class="filter-select" aria-label="Filter by health rating">
                        <option value="">All Ratings</option>
                        <option value="5">5 Stars</option>
                        <option value="4">4 Stars</option>
                        <option value="3">3 Stars</option>
                        <option value="2">2 Stars</option>
                        <option value="1">1 Star</option>
                    </select>
                    
                    <select id="sortBy" class="filter-select" aria-label="Sort entries">
                        <option value="date_desc">Latest First</option>
                        <option value="date_asc">Oldest First</option>
                        <option value="rating_desc">Highest Rated</option>
                        <option value="rating_asc">Lowest Rated</option>
                    </select>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="controls-right">
                <div class="view-toggles" role="radiogroup" aria-label="Timeline view options">
                    <button type="button" class="view-toggle active" data-view="timeline" 
                            aria-pressed="true" title="Timeline View">
                        <svg viewBox="0 0 24 24">
                            <path d="M4 10.5c-.83 0-1.5.67-1.5 1.5s.67 1.5 1.5 1.5 1.5-.67 1.5-1.5-.67-1.5-1.5-1.5zm0-6c-.83 0-1.5.67-1.5 1.5S3.17 7.5 4 7.5 5.5 6.83 5.5 6 4.83 4.5 4 4.5zm0 12c-.83 0-1.5.67-1.5 1.5s.67 1.5 1.5 1.5 1.5-.67 1.5-1.5-.67-1.5-1.5-1.5zM7 19h14v-2H7v2zm0-6h14v-2H7v2zm0-8v2h14V5H7z"/>
                        </svg>
                    </button>
                    <button type="button" class="view-toggle" data-view="grid" 
                            aria-pressed="false" title="Grid View">
                        <svg viewBox="0 0 24 24">
                            <path d="M3 3v8h8V3H3zm6 6H5V5h4v4zm-6 4v8h8v-8H3zm6 6H5v-4h4v4zm4-16v8h8V3h-8zm6 6h-4V5h4v4zm-6 4v8h8v-8h-8zm6 6h-4v-4h4v4z"/>
                        </svg>
                    </button>
                    <button type="button" class="view-toggle" data-view="cards" 
                            aria-pressed="false" title="Card View">
                        <svg viewBox="0 0 24 24">
                            <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/>
                        </svg>
                    </button>
                </div>
                
                <button type="button" class="add-entry-btn" id="addEntryBtn" 
                        aria-label="Add new hair journey entry">
                    <svg class="plus-icon" viewBox="0 0 24 24">
                        <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
                    </svg>
                    <span>Add Entry</span>
                </button>
            </div>
        </div>
        
        <!-- Loading State -->
        <div class="timeline-loading" id="timelineLoading">
            <div class="loading-spinner">
                <div class="spinner"></div>
                <p>Loading your hair journey...</p>
            </div>
        </div>
        
        <!-- Timeline Content -->
        <div class="timeline-content" id="timelineContent">
            <!-- Content will be loaded via AJAX -->
        </div>
        
        <!-- Pagination -->
        <div class="timeline-pagination" id="timelinePagination" style="display: none;">
            <button type="button" class="pagination-btn" id="prevPage" disabled>
                <svg viewBox="0 0 24 24">
                    <path d="M15.41 16.58L10.83 12l4.58-4.58L14 6l-6 6 6 6 1.41-1.42z"/>
                </svg>
                Previous
            </button>
            <div class="pagination-info">
                <span id="currentPage">1</span> of <span id="totalPages">1</span>
            </div>
            <button type="button" class="pagination-btn" id="nextPage">
                Next
                <svg viewBox="0 0 24 24">
                    <path d="M8.59 16.58L13.17 12 8.59 7.42 10 6l6 6-6 6-1.41-1.42z"/>
                </svg>
            </button>
        </div>
        
        <!-- Empty State -->
        <div class="timeline-empty" id="timelineEmpty" style="display: none;">
            <div class="empty-state">
                <div class="empty-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                    </svg>
                </div>
                <h3>Start Your Hair Journey</h3>
                <p>Document your hair care routine and track your progress over time.</p>
                <button type="button" class="empty-cta-btn" onclick="document.getElementById('addEntryBtn').click()">
                    Add Your First Entry
                </button>
            </div>
        </div>
    </div>
    
    <!-- Enhanced Entry Modal -->
    <div class="modal-overlay" id="entryModal" role="dialog" aria-labelledby="modalTitle" aria-hidden="true">
        <div class="modal-container">
            <header class="modal-header">
                <h2 id="modalTitle">Add Hair Journey Entry</h2>
                <button type="button" class="modal-close" id="modalClose" aria-label="Close modal">
                    <svg viewBox="0 0 24 24">
                        <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                    </svg>
                </button>
            </header>
            
            <div class="modal-body">
                <form id="entryForm" class="entry-form" novalidate>
                    <div class="form-section">
                        <h3 class="section-title">📝 Basic Information</h3>
                        <div class="form-grid">
                            <div class="form-group full-width">
                                <label for="entryTitle" class="form-label">Entry Title *</label>
                                <input type="text" id="entryTitle" name="title" required 
                                       class="form-input" placeholder="e.g., First Wash Day of 2024"
                                       maxlength="100" aria-describedby="titleHelp">
                                <small id="titleHelp" class="form-help">Choose a descriptive title for your entry</small>
                                <div class="form-error" id="titleError"></div>
                            </div>
                            
                            <div class="form-group full-width">
                                <label for="entryDescription" class="form-label">Description</label>
                                <textarea id="entryDescription" name="description" 
                                          class="form-textarea" rows="4" 
                                          placeholder="Describe your hair care experience..."
                                          maxlength="1000"></textarea>
                                <div class="char-counter">
                                    <span id="descCounter">0</span>/1000
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="healthRating" class="form-label">Hair Health Rating *</label>
                                <div class="rating-input" role="radiogroup" aria-labelledby="healthRating">
                                    <input type="radio" id="rating1" name="rating" value="1" required>
                                    <label for="rating1" class="rating-star" title="1 star">⭐</label>
                                    <input type="radio" id="rating2" name="rating" value="2">
                                    <label for="rating2" class="rating-star" title="2 stars">⭐</label>
                                    <input type="radio" id="rating3" name="rating" value="3" checked>
                                    <label for="rating3" class="rating-star" title="3 stars">⭐</label>
                                    <input type="radio" id="rating4" name="rating" value="4">
                                    <label for="rating4" class="rating-star" title="4 stars">⭐</label>
                                    <input type="radio" id="rating5" name="rating" value="5">
                                    <label for="rating5" class="rating-star" title="5 stars">⭐</label>
                                </div>
                                <div class="form-error" id="ratingError"></div>
                            </div>
                            
                            <div class="form-group">
                                <label for="entryMood" class="form-label">How are you feeling?</label>
                                <select id="entryMood" name="mood_demeanor" class="form-select">
                                    <option value="excited">😊 Excited</option>
                                    <option value="happy">😄 Happy</option>
                                    <option value="optimistic">🌟 Optimistic</option>
                                    <option value="nervous">😬 Nervous</option>
                                    <option value="determined">💪 Determined</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3 class="section-title">🧴 Products & Care</h3>
                        <div class="form-grid">
                            <div class="form-group full-width">
                                <label for="entryProducts" class="form-label">Products Used</label>
                                <input type="text" id="entryProducts" name="products" 
                                       class="form-input" placeholder="e.g., Moisturizing Shampoo, Leave-in Conditioner"
                                       maxlength="255">
                            </div>
                            
                            <div class="form-group full-width">
                                <label for="entryNotes" class="form-label">Stylist Notes</label>
                                <textarea id="entryNotes" name="notes" 
                                          class="form-textarea" rows="3" 
                                          placeholder="Professional recommendations or observations..."
                                          maxlength="500"></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="entryEnvironment" class="form-label">Environment</label>
                                <select id="entryEnvironment" name="environment" class="form-select">
                                    <option value="home">🏠 At Home</option>
                                    <option value="salon">💇‍♀️ At Salon</option>
                                    <option value="vacation">🏖️ On Vacation</option>
                                    <option value="work">🏢 At Work</option>
                                    <option value="outdoors">🌳 Outdoors</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3 class="section-title">📷 Photo</h3>
                        <div class="photo-upload-container">
                            <input type="file" id="entryPhoto" name="photo" accept="image/*" 
                                   class="photo-input" aria-describedby="photoHelp">
                            <label for="entryPhoto" class="photo-upload-label">
                                <div class="upload-icon">
                                    <svg viewBox="0 0 24 24">
                                        <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z"/>
                                    </svg>
                                </div>
                                <div class="upload-text">
                                    <strong>Choose a photo</strong> or drag and drop
                                </div>
                            </label>
                            <small id="photoHelp" class="form-help">Upload a clear photo of your hair (max 10MB)</small>
                            <div class="photo-preview" id="photoPreview" style="display: none;">
                                <img id="previewImage" alt="Photo preview">
                                <button type="button" class="remove-photo" id="removePhoto" aria-label="Remove photo">×</button>
                            </div>
                        </div>
                    </div>
                    
                    <input type="hidden" id="entryId" name="entry_id" value="">
                    <?php wp_nonce_field('myavana_timeline', 'myavana_timeline_nonce'); ?>
                </form>
            </div>
            
            <footer class="modal-footer">
                <button type="button" class="btn btn-secondary" id="cancelEntry">Cancel</button>
                <button type="submit" form="entryForm" class="btn btn-primary" id="saveEntry">
                    <span class="btn-text">Save Entry</span>
                    <span class="btn-loader" style="display: none;">
                        <svg class="spinner" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" opacity="0.25"/>
                            <path d="M12 2a10 10 0 0 1 10 10" stroke="currentColor" stroke-width="4" fill="none" stroke-linecap="round"/>
                        </svg>
                        Saving...
                    </span>
                </button>
            </footer>
        </div>
    </div>
    
    <!-- Progress Analytics Modal -->
    <div class="modal-overlay" id="analyticsModal" role="dialog" aria-labelledby="analyticsTitle" aria-hidden="true">
        <div class="modal-container large">
            <header class="modal-header">
                <h2 id="analyticsTitle">📊 Your Hair Journey Analytics</h2>
                <button type="button" class="modal-close" onclick="closeModal('analyticsModal')" aria-label="Close analytics">
                    <svg viewBox="0 0 24 24">
                        <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                    </svg>
                </button>
            </header>
            
            <div class="modal-body">
                <div class="analytics-content" id="analyticsContent">
                    <!-- Analytics content loaded via AJAX -->
                </div>
            </div>
        </div>
    </div>
    
    <?php
    return ob_get_clean();
}

// Register the enhanced shortcode
add_shortcode('myavana_enhanced_timeline', 'myavana_enhanced_timeline_shortcode');