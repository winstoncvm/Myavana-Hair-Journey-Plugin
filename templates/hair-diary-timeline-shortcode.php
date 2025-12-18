<?php
/**
 * Myavana Hair Journey Timeline Shortcode
 */
function myavana_hair_journey_timeline_shortcode($atts = []) {
    // Parse attributes
    $atts = shortcode_atts([
        'show_progress' => 'true', 
        'show_stats' => 'true',
        'autoplay' => 'false',
        'entries_per_page' => '10'
    ], $atts, 'myavana_hair_journey_timeline');
    // Only show to logged-in users
    if (!is_user_logged_in()) {
        return '<div class="myavana-auth-required">
            <div class="auth-card">
                <h3>🔒 Authentication Required</h3>
                <p>Please log in to view your hair journey timeline.</p>
                <a href="' . wp_login_url(get_permalink()) . '" class="myavana-btn-primary">LOGIN</a>
            </div>
        </div>';
    }
    
    $user_id = get_current_user_id();
    
    // Enqueue MYAVANA brand styles
    wp_enqueue_style('myavana-styles', MYAVANA_URL . 'assets/css/myavana-styles.css', [], '2.3.7');
    wp_enqueue_style('myavana-timeline-modals', MYAVANA_URL . 'assets/css/myavana-timeline-modals.css', ['myavana-styles'], '2.3.7');

    // Enqueue Splide slider
    wp_enqueue_style('splide', 'https://cdn.jsdelivr.net/npm/@splidejs/splide@4.1.4/dist/css/splide.min.css', [], '4.1.4');
    wp_enqueue_script('splide', 'https://cdn.jsdelivr.net/npm/@splidejs/splide@4.1.4/dist/js/splide.min.js', [], '4.1.4', true);

    // Enqueue FilePond
    wp_enqueue_style('filepond', 'https://unpkg.com/filepond@4.30.4/dist/filepond.min.css', [], '4.30.4');
    wp_enqueue_style('filepond-image-preview', 'https://unpkg.com/filepond-plugin-image-preview@4.6.11/dist/filepond-plugin-image-preview.min.css', [], '4.6.11');
    wp_enqueue_script('filepond', 'https://unpkg.com/filepond@4.30.4/dist/filepond.min.js', [], '4.30.4', true);
    wp_enqueue_script('filepond-image-preview', 'https://unpkg.com/filepond-plugin-image-preview@4.6.11/dist/filepond-plugin-image-preview.min.js', ['filepond'], '4.6.11', true);
    wp_enqueue_script('filepond-validate-size', 'https://unpkg.com/filepond-plugin-image-validate-size@1.2.6/dist/filepond-plugin-image-validate-size.min.js', ['filepond'], '1.2.6', true);

    // Enqueue custom timeline JS
    wp_enqueue_script('myavana-hair-timeline', MYAVANA_URL . 'assets/js/myavana-hair-timeline.js', ['jquery', 'splide', 'filepond'], '2.3.7', true);

    // Localize script with settings
    wp_localize_script('myavana-hair-timeline', 'myavanaTimelineSettings', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'getEntriesNonce' => wp_create_nonce('myavana_get_entries'),
        'getEntryDetailsNonce' => wp_create_nonce('myavana_get_entry_details'),
        'updateEntryNonce' => wp_create_nonce('myavana_update_entry'),
        'deleteEntryNonce' => wp_create_nonce('myavana_delete_entry'),
        'addEntryNonce' => wp_create_nonce('myavana_add_entry'),
        'addGoalNonce' => wp_create_nonce('myavana_add_goal'),
        'addRoutineNonce' => wp_create_nonce('myavana_add_routine'),
        'deleteGoalNonce' => wp_create_nonce('myavana_delete_goal'),
        'deleteRoutineNonce' => wp_create_nonce('myavana_delete_routine'),
        'nonce' => wp_create_nonce('myavana_nonce'),
        'autoStartTimeline' => (isset($_GET['start']) && $_GET['start'] === '1')
    ));

    ob_start();
    ?>
   
    <div class="myvana-page-container">
        <!-- Start Page -->
        <div class="start-page" id="startPage">
            <!-- Floating Elements -->
            <div class="floating-element" style="--delay: 0s; top: 10%; left: 15%;"></div>
            <div class="floating-element" style="--delay: 2s; top: 20%; right: 10%;"></div>
            <div class="floating-element" style="--delay: 4s; bottom: 30%; left: 10%;"></div>
            <div class="floating-element" style="--delay: 6s; bottom: 15%; right: 20%;"></div>

            <div class="start-hero">
                <h1 class="start-title">TRANSFORM YOUR HAIR JOURNEY</h1>
                <p class="start-subtitle">Document, analyze, and celebrate every step of your beautiful transformation</p>

                <button class="start-btn" id="startJourneyBtn">
                    <span class="btn-content">
                        <svg viewBox="0 0 24 24" style="width: 20px; height: 20px; fill: currentColor;">
                            <path d="M8 5v14l11-7z"/>
                        </svg>
                        BEGIN YOUR JOURNEY
                    </span>
                    <div class="btn-shimmer"></div>
                </button>
            </div>

            <div class="start-features">
                <div class="feature-card-timeline">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                        </svg>
                    </div>
                    <h3>AI-Powered Analysis</h3>
                    <p>Get personalized insights about your hair health, growth patterns, and product effectiveness.</p>
                </div>

                <div class="feature-card-timeline">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M16 6l2.29 2.29-4.88 4.88-4-4L2 16.59 3.41 18l6-6 4 4 6.3-6.29L22 12V6h-6z"/>
                        </svg>
                    </div>
                    <h3>Progress Tracking</h3>
                    <p>Visualize your hair journey with beautiful timelines and detailed progress metrics.</p>
                </div>

                <div class="feature-card-timeline">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                        </svg>
                    </div>
                    <h3>Community Love</h3>
                    <p>Share your achievements and get inspired by other hair warriors on similar journeys.</p>
                </div>
            </div>
        </div>

        <!-- Main Timeline Container -->
        <div class="hair-diary-container" id="timelineContainer" style="display: none;">
            <div class="diary-header">
                <h1 class="myavana-h1">MY HAIR JOURNEY</h1>
                <p class="myavana-body">Document your transformation with our beautiful horizontal timeline</p>
            </div>

            <div class="timeline-slider">
                <div class="splide" id="slider">
                    <div class="splide__track">
                        <div class="splide__list">
                            <!-- Entries will be loaded via AJAX -->
                        </div>
                    </div>
                </div>

                <!-- Timeline Navigation -->
                <div class="timeline-nav">
                    <div class="timeline-track">
                        <div class="timeline-progress" id="progress"></div>
                    </div>
                    <div class="timeline-dates" id="timelineDates">
                        <!-- Date markers will be loaded via AJAX -->
                    </div>
                </div>
            </div>
        </div>

        <!-- End Page - Redesigned MYAVANA Summary -->
        <div class="myavana-end-page" id="endPage" style="display:none;">
            <!-- Background Pattern & Confetti -->
            <div class="myavana-celebration-bg">
                <div class="confetti-container"></div>
                <div class="bg-pattern"></div>
            </div>

            <!-- Hero Section -->
            <div class="myavana-celebration-hero">
                <div class="celebration-icon-container">
                    <div class="celebration-icon-ring"></div>
                    <div class="celebration-icon">✨</div>
                </div>
                <div class="myavana-preheader">HAIR JOURNEY MILESTONE</div>
                <div class="myavana-h1-celebration">INCREDIBLE</div>
                <h4 class="myavana-subheader-celebration">Your Transformation Story</h4>
                <p class="myavana-body-celebration">You've reached an amazing milestone in your hair journey. Every entry, every moment of care, has led to this beautiful progress.</p>
            </div>

            <!-- Progress Visualization -->
            <div class="myavana-progress-section">
                <!-- Main Progress Ring -->
                <!-- <div class="myavana-progress-ring-container">
                    <svg class="myavana-progress-ring" width="200" height="200">
                        <circle cx="100" cy="100" r="85" stroke="var(--myavana-stone)" stroke-width="8" fill="transparent"/>
                        <circle cx="100" cy="100" r="85" stroke="var(--myavana-coral)" stroke-width="8" fill="transparent"
                                stroke-linecap="round" stroke-dasharray="534" stroke-dashoffset="134"
                                class="progress-circle" transform="rotate(-90 100 100)"/>
                    </svg>
                    <div class="myavana-progress-center">
                        <div class="progress-percentage" id="overallProgress">75%</div>
                        <div class="progress-label">Complete</div>
                    </div>
                </div> -->

                <!-- Stats Grid -->
                <div class="myavana-stats-grid">
                    <div class="myavana-stat-card">
                        <div class="stat-visual">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="var(--myavana-coral)">
                                <path d="M7 14c-1.66 0-3 1.34-3 3 0 1.31.84 2.41 2 2.83V22h2v-2.17c1.16-.42 2-1.52 2-2.83 0-1.66-1.34-3-3-3zm0 4c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm10-3c1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3 1.34 3 3 3zm0-4c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1z"/>
                            </svg>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number" id="growthProgress">+2.3"</div>
                            <div class="stat-label">Hair Growth</div>
                        </div>
                    </div>

                    <div class="myavana-stat-card">
                        <div class="stat-visual">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="var(--myavana-coral)">
                                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                            </svg>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number" id="healthProgress">+40%</div>
                            <div class="stat-label">Health Score</div>
                        </div>
                    </div>

                    <div class="myavana-stat-card">
                        <div class="stat-visual">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="var(--myavana-coral)">
                                <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/>
                            </svg>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number" id="entriesCount">12</div>
                            <div class="stat-label">Journal Entries</div>
                        </div>
                    </div>

                    <div class="myavana-stat-card">
                        <div class="stat-visual">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="var(--myavana-coral)">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                            </svg>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number" id="productsCount">8</div>
                            <div class="stat-label">Products Tried</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Achievement Badges -->
            <div class="myavana-achievement-section">
                <div class="myavana-preheader">ACHIEVEMENTS UNLOCKED</div>
                <div class="myavana-badge-grid">
                    <div class="myavana-achievement-badge earned" data-achievement="consistency">
                        <div class="badge-glow"></div>
                        <div class="badge-icon">🏆</div>
                        <div class="badge-title">CONSISTENCY CHAMPION</div>
                        <div class="badge-description">10+ entries this month</div>
                    </div>
                    <div class="myavana-achievement-badge earned" data-achievement="growth">
                        <div class="badge-glow"></div>
                        <div class="badge-icon">📈</div>
                        <div class="badge-title">GROWTH TRACKER</div>
                        <div class="badge-description">Measured visible progress</div>
                    </div>
                    <div class="myavana-achievement-badge earned" data-achievement="selfcare">
                        <div class="badge-glow"></div>
                        <div class="badge-icon">💎</div>
                        <div class="badge-title">SELF-CARE STAR</div>
                        <div class="badge-description">Dedicated to routine</div>
                    </div>
                </div>
            </div>

            <!-- Milestone Message -->
            <div class="myavana-milestone-message">
                <div class="message-content">
                    <h3 class="myavana-subheader">Your Hair Story Continues</h3>
                    <p class="myavana-body">Each entry in your timeline tells a story of dedication, growth, and self-love. You're not just caring for your hair – you're investing in yourself and creating a beautiful legacy of transformation.</p>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="myavana-celebration-actions">
                <button class="myavana-btn-primary celebration-primary" id="restartJourneyBtn">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 4V1L8 5l4 4V6c3.31 0 6 2.69 6 6 0 1.01-.25 1.97-.7 2.8l1.46 1.46C19.54 15.03 20 13.57 20 12c0-4.42-3.58-8-8-8zm0 14c-3.31 0-6-2.69-6-6 0-1.01.25-1.97.7-2.8L5.24 7.74C4.46 8.97 4 10.43 4 12c0 4.42 3.58 8 8 8v3l4-4-4-4v3z"/>
                    </svg>
                    BEGIN NEW CHAPTER
                </button>
                <button class="myavana-btn-secondary celebration-secondary" id="shareProgressBtn">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M18 16.08c-.76 0-1.44.3-1.96.77L8.91 12.7c.05-.23.09-.46.09-.7s-.04-.47-.09-.7l7.05-4.11c.54.5 1.25.81 2.04.81 1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3c0 .24.04.47.09.7L8.04 9.81C7.5 9.31 6.79 9 6 9c-1.66 0-3 1.34-3 3s1.34 3 3 3c.79 0 1.50-.31 2.04-.81l7.12 4.16c-.05.21-.08.43-.08.65 0 1.61 1.31 2.92 2.92 2.92 1.61 0 2.92-1.31 2.92-2.92s-1.31-2.92-2.92-2.92z"/>
                    </svg>
                    SHARE YOUR JOURNEY
                </button>
                <button class="myavana-btn-secondary celebration-secondary" id="viewTimelineBtn">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/>
                    </svg>
                    REVISIT TIMELINE
                </button>
            </div>

            <!-- Celebration Quote -->
            <div class="myavana-celebration-quote">
                <blockquote class="myavana-body">
                    "Your hair journey is a testament to your commitment to self-care and growth. Every strand tells a story of transformation."
                </blockquote>
                <cite>— MYAVANA Hair Care Philosophy</cite>
            </div>
        </div>

        <!-- Add Entry Button - MYAVANA Style -->
        <div class="myavana-add-entry-btn" id="addEntryBtn">
            <svg viewBox="0 0 24 24" style="width: 24px; height: 24px; fill: currentColor;">
                <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
            </svg>
        </div>

        <!-- Add Entry Modal -->
        <div class="myavana-timeline-modal-overlay" id="entryModal" style="display: none;">
            <div class="myavana-timeline-modal-container myavana-timeline-modal-bg">
                <div class="myavana-timeline-modal-close" id="modalClose">
                    <i class="fas fa-times"></i>
                </div>
                
                <h2 class="myavana-subheader mb-3">ADD HAIR JOURNEY ENTRY</h2>
                <div id="error-message" class="error-message mb-3" style="display: none; background: #fee; border: 1px solid #f5c6cb; color: #721c24; padding: 12px; border-radius: 6px; margin-bottom: 16px;"></div>
                <div id="success-message" class="success-message mb-3" style="display: none; background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 12px; border-radius: 6px; margin-bottom: 16px;"></div>
                
                <form id="myavana-entry-form"  method="post" enctype="multipart/form-data">
                    <div class="coolinput">
                        <label for="input" class="text">Entry Title:</label>
                        <input type="text" id="title" name="title" placeholder="e.g., New Haircut" required class="input">
                    </div>
                    
                    <div class="coolinput">
                        <label for="textarea" class="text">Description:</label>
                        <textarea id="description" name="description" placeholder="Describe your hair journey moment" rows="5" class="input"></textarea>
                    </div>
                    
                    <div class="coolinput">
                        <label for="input" class="text">Products Used:</label>
                        <input type="text" id="products" name="products" placeholder="e.g., Moisturizing Shampoo" class="input">
                    </div>
                    
                    <div class="coolinput">
                        <label for="textarea" class="text">Stylist Notes:</label>
                        <textarea id="notes" name="notes" placeholder="Notes from your stylist" rows="5" class="input"></textarea>
                    </div>
                    
                    <div class="coolinput">
                        <label for="input" class="text">Hair Health Rating (1-5):</label>
                        <input type="number" pattern="\d+" id="rating" name="rating" min="1" max="5" value="3" required class="input">
                    </div>
                    <div class="div-center">
                        <label for="filepond-container">Photo</label>
                        <div id="filepond-container"></div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">How's Your Hair Feeling?</label>
                        <div class="mood-selector">
                            <div class="mood-option">
                                <input type="radio" id="mood1" name="mood_demeanor" value="Excited" checked>
                                <label for="mood1">
                                    <i class="fas fa-smile mood-icon-lg"></i>
                                    <span>Excited</span>
                                </label>
                            </div>
                            <div class="mood-option">
                                <input type="radio" id="mood2" name="mood_demeanor" value="Happy">
                                <label for="mood2">
                                    <i class="fas fa-grin-stars mood-icon-lg"></i>
                                    <span>Happy</span>
                                </label>
                            </div>
                            <div class="mood-option">
                                <input type="radio" id="mood3" name="mood_demeanor" value="Optimistic">
                                <label for="mood3">
                                    <i class="fas fa-smile-beam mood-icon-lg"></i>
                                    <span>Optimistic</span>
                                </label>
                            </div>
                            <div class="mood-option">
                                <input type="radio" id="mood4" name="mood_demeanor" value="Nervous">
                                <label for="mood4">
                                    <i class="fas fa-meh mood-icon-lg"></i>
                                    <span>Nervous</span>
                                </label>
                            </div>
                            <div class="mood-option">
                                <input type="radio" id="mood5" name="mood_demeanor" value="Determined">
                                <label for="mood5">
                                    <i class="fas fa-tired mood-icon-lg"></i>
                                    <span>Determined</span>
                                </label>
                            </div>
                        </div>
                    </div>
                        
                    <div class="form-group">
                        <label class="form-label">Environment</label>
                        <select name="environment" class="form-control">
                            <option value="home">At Home</option>
                            <option value="salon">At Salon</option>
                            <option value="vacation">On Vacation</option>
                            <option value="work">At Work</option>
                            <option value="outdoors">Outdoors</option>
                        </select>
                    </div>
                    
                    <?php wp_nonce_field('myavana_entry', 'myavana_nonce'); ?>
                    <input type="hidden" name="myavana_entry" value="1">
                    <input type="hidden" name="is_automated" value="0">
                    <button class="myavana-btn-primary" type="submit" id="submitEntry">
                        <svg viewBox="0 0 24 24" style="width: 20px; height: 20px; fill: currentColor;">
                            <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
                        </svg>
                        ADD ENTRY
                    </button>
                </form>
            </div>
        </div>

        <!-- View Entry Modal -->
        <div class="myavana-timeline-modal-overlay myavana-timeline-view-entry-modal" id="viewEntryModal" style="display: none;">
            <div class="myavana-timeline-modal-container">
                <div class="myavana-modal-header">
                    <h2 class="myavana-modal-title">Hair Journey Entry</h2>
                    <p class="myavana-modal-subtitle">View your beautiful progress moment</p>
                    <div class="myavana-modal-close" id="viewEntryModalClose">
                        <i class="fas fa-times"></i>
                    </div>
                </div>
                <div class="myavana-modal-content">
                    <div class="view-entry-content">
                        <img class="view-entry-image" id="viewEntryImage" src="" alt="Hair progress" style="display: none;">

                        <div class="view-entry-meta">
                            <div class="view-entry-meta-item">
                                <div class="view-entry-meta-label">Health Rating</div>
                                <div class="view-entry-meta-value" id="viewEntryRating">5/5</div>
                            </div>
                            <div class="view-entry-meta-item">
                                <div class="view-entry-meta-label">Date Added</div>
                                <div class="view-entry-meta-value" id="viewEntryDate">Today</div>
                            </div>
                            <div class="view-entry-meta-item">
                                <div class="view-entry-meta-label">Mood</div>
                                <div class="view-entry-meta-value" id="viewEntryMood">Happy</div>
                            </div>
                            <div class="view-entry-meta-item">
                                <div class="view-entry-meta-label">Environment</div>
                                <div class="view-entry-meta-value" id="viewEntryEnvironment">Home</div>
                            </div>
                        </div>

                        <div>
                            <h3 id="viewEntryTitle" style="color: var(--onyx); margin-bottom: var(--space-2);"></h3>
                            <div class="view-entry-description" id="viewEntryDescription"></div>
                        </div>

                        <div id="viewEntryProductsContainer" style="display: none;">
                            <h4 style="color: var(--coral); margin-bottom: var(--space-2);">Products Used</h4>
                            <div class="view-entry-tags" id="viewEntryProducts"></div>
                        </div>

                        <div id="viewEntryNotesContainer" style="display: none;">
                            <h4 style="color: var(--coral); margin-bottom: var(--space-2);">Stylist Notes</h4>
                            <div class="view-entry-description" id="viewEntryNotes"></div>
                        </div>
                    </div>
                </div>
                <div class="myavana-modal-actions">
                    <button class="myavana-modal-btn secondary" id="editEntryFromView">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <button class="myavana-modal-btn secondary" id="shareEntryFromView">
                        <i class="fas fa-share-alt"></i> Share
                    </button>
                    <button class="myavana-modal-btn primary" id="closeViewEntry">
                        <i class="fas fa-check"></i> Close
                    </button>
                </div>
            </div>
        </div>

        <!-- Edit Entry Modal -->
        <div class="myavana-timeline-modal-overlay myavana-timeline-edit-entry-modal" id="editEntryModal" style="display: none;">
            <div class="myavana-timeline-modal-container">
                <div class="myavana-modal-header">
                    <h2 class="myavana-modal-title">Edit Hair Journey Entry</h2>
                    <p class="myavana-modal-subtitle">Update your hair progress moment</p>
                    <div class="myavana-modal-close" id="editEntryModalClose">
                        <i class="fas fa-times"></i>
                    </div>
                </div>
                <div class="myavana-modal-content">
                    <form class="edit-entry-form" id="editEntryForm">
                        <div class="edit-form-group">
                            <label class="edit-form-label">Entry Title</label>
                            <input type="text" class="edit-form-input" id="editEntryTitleInput" required>
                        </div>

                        <div class="edit-form-group">
                            <label class="edit-form-label">Description</label>
                            <textarea class="edit-form-textarea" id="editEntryDescriptionInput" rows="4"></textarea>
                        </div>

                        <div class="edit-form-group">
                            <label class="edit-form-label">Products Used</label>
                            <input type="text" class="edit-form-input" id="editEntryProductsInput" placeholder="Separate with commas">
                        </div>

                        <div class="edit-form-group">
                            <label class="edit-form-label">Stylist Notes</label>
                            <textarea class="edit-form-textarea" id="editEntryNotesInput" rows="3"></textarea>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-3);">
                            <div class="edit-form-group">
                                <label class="edit-form-label">Health Rating</label>
                                <select class="edit-form-select" id="editEntryRatingInput">
                                    <option value="1">1 - Poor</option>
                                    <option value="2">2 - Fair</option>
                                    <option value="3">3 - Good</option>
                                    <option value="4">4 - Very Good</option>
                                    <option value="5">5 - Excellent</option>
                                </select>
                            </div>

                            <div class="edit-form-group">
                                <label class="edit-form-label">Mood</label>
                                <select class="edit-form-select" id="editEntryMoodInput">
                                    <option value="Excited">Excited</option>
                                    <option value="Happy">Happy</option>
                                    <option value="Optimistic">Optimistic</option>
                                    <option value="Nervous">Nervous</option>
                                    <option value="Determined">Determined</option>
                                </select>
                            </div>
                        </div>

                        <div class="edit-form-group">
                            <label class="edit-form-label">Environment</label>
                            <select class="edit-form-select" id="editEntryEnvironmentInput">
                                <option value="home">At Home</option>
                                <option value="salon">At Salon</option>
                                <option value="vacation">On Vacation</option>
                                <option value="work">At Work</option>
                                <option value="outdoors">Outdoors</option>
                            </select>
                        </div>

                        <div class="edit-form-group">
                            <label class="edit-form-label">Photo</label>
                            <div class="edit-image-upload" id="editImageUpload">
                                <div class="edit-upload-icon">📸</div>
                                <div class="edit-upload-text">Click to change photo or drag & drop</div>
                                <input type="file" id="editImageInput" accept="image/*" style="display: none;">
                                <img class="edit-current-image" id="editCurrentImage" style="display: none;">
                            </div>
                        </div>

                        <input type="hidden" id="editEntryId">
                    </form>
                </div>
                <div class="myavana-modal-actions">
                    <button type="button" class="myavana-modal-btn secondary" id="cancelEditEntry">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" form="editEntryForm" class="myavana-modal-btn primary" id="saveEditEntry">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </div>
        </div>

        <!-- Share Entry Modal -->
        <div class="myavana-timeline-modal-overlay myavana-timeline-share-modal" id="shareEntryModal" style="display: none;">
            <div class="myavana-timeline-modal-container">
                <div class="myavana-modal-header">
                    <h2 class="myavana-modal-title">Share Your Progress</h2>
                    <p class="myavana-modal-subtitle">Inspire others with your hair journey</p>
                    <div class="myavana-modal-close" id="shareEntryModalClose">
                        <i class="fas fa-times"></i>
                    </div>
                </div>
                <div class="myavana-modal-content">
                    <div class="share-preview">
                        <div class="share-preview-title" id="sharePreviewTitle">My Hair Journey Progress</div>
                        <div class="share-preview-text" id="sharePreviewText">
                            Check out my amazing hair transformation! I'm documenting my journey with MYAVANA and seeing incredible results.
                        </div>
                    </div>

                    <div class="share-options">
                        <div class="share-option facebook" id="shareFacebook">
                            <div class="share-option-icon" style="color: #1877f2;">📘</div>
                            <div class="share-option-label">Facebook</div>
                        </div>
                        <div class="share-option twitter" id="shareTwitter">
                            <div class="share-option-icon" style="color: #1da1f2;">🐦</div>
                            <div class="share-option-label">Twitter</div>
                        </div>
                        <div class="share-option instagram" id="shareInstagram">
                            <div class="share-option-icon" style="color: #e1306c;">📷</div>
                            <div class="share-option-label">Instagram</div>
                        </div>
                        <div class="share-option copy" id="shareCopyLink">
                            <div class="share-option-icon" style="color: var(--coral);">🔗</div>
                            <div class="share-option-label">Copy Link</div>
                        </div>
                    </div>
                </div>
                <div class="myavana-modal-actions">
                    <button class="myavana-modal-btn secondary" id="closeShareModal">
                        <i class="fas fa-times"></i> Close
                    </button>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div class="myavana-timeline-modal-overlay myavana-timeline-delete-modal" id="deleteEntryModal" style="display: none;">
            <div class="myavana-timeline-modal-container">
                <div class="myavana-modal-header">
                    <h2 class="myavana-modal-title">Delete Entry</h2>
                    <p class="myavana-modal-subtitle">This action cannot be undone</p>
                    <div class="myavana-modal-close" id="deleteEntryModalClose">
                        <i class="fas fa-times"></i>
                    </div>
                </div>
                <div class="myavana-modal-content">
                    <div class="delete-warning">
                        <div class="delete-warning-icon">⚠️</div>
                        <div class="delete-warning-text">
                            Are you sure you want to permanently delete this hair journey entry? This action cannot be undone and all associated data will be lost.
                        </div>
                    </div>

                    <div class="delete-entry-preview" id="deleteEntryPreview">
                        <img class="delete-entry-image" id="deleteEntryImage" src="" alt="Entry image">
                        <div class="delete-entry-info">
                            <h4 id="deleteEntryTitle">Entry Title</h4>
                            <p id="deleteEntryDate">Entry Date</p>
                        </div>
                    </div>

                    <input type="hidden" id="deleteEntryId">
                </div>
                <div class="myavana-modal-actions">
                    <button class="myavana-modal-btn secondary" id="cancelDeleteEntry">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button class="myavana-modal-btn danger" id="confirmDeleteEntry">
                        <i class="fas fa-trash"></i> Delete Entry
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!-- Scripts are enqueued above via wp_enqueue_script -->

    <?php
    return ob_get_clean();
}
add_shortcode('myavana_hair_journey_timeline', 'myavana_hair_journey_timeline_shortcode');


/**
 * Get hair journey entries with stats
 */
function myavana_get_entries() {
    check_ajax_referer('myavana_get_entries', 'security');
    
    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error('User not logged in');
        return;
    }
    
    $entries = new WP_Query([
        'post_type' => 'hair_journey_entry',
        'author' => $user_id,
        'posts_per_page' => -1,
        'orderby' => 'date',
        'order' => 'DESC'
    ]);
    
    $entries_html = '';
    $dates_html = '';
    $index = 0;
    $products = [];
    $health_improvement = 0;
    $growth = 0;
    $first_rating = null;
    $last_rating = null;

    
    if ($entries->have_posts()) {
        while ($entries->have_posts()) {
            $entries->the_post();
            $post_id = get_the_ID();
            $date = get_the_date('F j, Y');
            $time = get_the_date('g:i A');
            $short_date = get_the_date('M j');
            $title = get_the_title();
            $content = get_the_content();
            $thumbnail = get_the_post_thumbnail_url($post_id, 'large');
            $rating = (int)get_post_meta($post_id, 'health_rating', true);
            $entry_products = get_post_meta($post_id, 'products_used', true);
            $notes = get_post_meta($post_id, 'stylist_notes', true);
            $mood = get_post_meta($post_id, 'mood_demeanor', true);
            $environment = get_post_meta($post_id, 'environment', true);
            $ai_tags = get_post_meta($post_id, 'ai_tags', true);
            
            // Calculate entry completeness percentage
            $completeness = 0;
            if ($title) $completeness += 20;
            if ($content) $completeness += 20;
            if ($thumbnail) $completeness += 25;
            if ($entry_products) $completeness += 15;
            if ($mood) $completeness += 10;
            if ($rating) $completeness += 10;

            // Fetch all meta fields for this first entry
            $post_meta = get_post_meta($post_id);

            // Log them
            if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                error_log("Meta fields for FIRST entry ID $post_id: " . print_r($post_meta, true));
            }

            
            // Track first and last rating for health improvement calculation
            if ($first_rating === null) $first_rating = $rating;
            $last_rating = $rating;
            
            // Track products
            if ($entry_products) {
                $product_list = array_map('trim', explode(',', $entry_products));
                $products = array_merge($products, $product_list);
            }
            
            // Check if there are multiple entries for this date
            $same_day_entries = get_posts([
                'post_type' => 'hair_journey_entry',
                'author' => $user_id,
                'date_query' => [
                    'year'  => get_the_date('Y'),
                    'month' => get_the_date('m'), 
                    'day'   => get_the_date('d')
                ],
                'posts_per_page' => -1
            ]);
            $multiple_entries = count($same_day_entries) > 1;
            
            // Generate entry HTML with all interactive features
            $entries_html .= '
            <div class="splide__slide">
                <div class="main-entry' . ($multiple_entries ? ' has-multiple' : '') . '"
                     data-entry-id="' . $post_id . '"
                     data-index="' . $index . '"
                     data-completeness="' . $completeness . '"
                     data-rating="' . esc_attr($rating) . '"
                     data-mood="' . esc_attr($mood) . '"
                     data-environment="' . esc_attr($environment) . '"
                     data-notes="' . esc_attr($notes) . '"
                     data-products="' . esc_attr($entry_products) . '">
                    
                    <!-- Floating Entry Details Card -->
                    <div class="floating-entry-card">
                        <div class="floating-card-header">
                            <div class="floating-card-image" style="background-image: url(' . ($thumbnail ? esc_url($thumbnail) : 'data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 50 50%22><rect width=%2250%22 height=%2250%22 fill=%22%23f0f0f0%22/><text x=%2250%%22 y=%2250%%22 text-anchor=%22middle%22 dy=%22.3em%22 font-size=%2214%22 fill=%22%23999%22>📸</text></svg>') . ')"></div>
                            <div class="floating-card-meta">
                                <div class="floating-card-title">' . esc_html($title) . '</div>
                                <div class="floating-card-time">' . esc_html($time) . '</div>
                            </div>
                        </div>
                        <div class="floating-card-content">
                            <div class="floating-card-preview">' . esc_html(wp_trim_words($content, 15)) . '</div>
                        </div>
                        <div class="floating-card-stats">
                            <div class="floating-stat">
                                <span class="floating-stat-icon">💚</span>
                                <span>' . $rating . '/5</span>
                            </div>
                            <div class="floating-stat">
                                <span class="floating-stat-icon">📝</span>
                                <span>' . $completeness . '%</span>
                            </div>
                        </div>
                        <div class="floating-card-actions">
                            <button class="floating-action-btn" onclick="viewFullEntry(' . $post_id . ')">
                                <span>👁️</span> View
                            </button>
                            <button class="floating-action-btn secondary" onclick="shareEntry(' . $post_id . ')">
                                <span>📤</span> Share
                            </button>
                        </div>
                    </div>
                    
                    <!-- Love Heart Button -->
                    <div class="entry-love-heart" data-entry-id="' . $post_id . '">
                        <i class="fas fa-heart love-heart-icon"></i>
                    </div>
                    
                    <!-- Multiple Entries Indicator -->
                    ' . ($multiple_entries ? '<div class="multiple-entries-indicator">' . count($same_day_entries) . '</div>' : '') . '
                    
                    <!-- Entry Mini Stack Effect -->
                    ' . ($multiple_entries ? '<div class="entry-mini-stack"></div>' : '') . '
                    
                    <div class="entry-content">
                        <div class="entry-image-container">
                            ' . ($thumbnail ? '
                                <img src="' . esc_url($thumbnail) . '" alt="Hair progress" class="entry-image">
                                <div class="entry-image-zoom-overlay">
                                    <div class="zoom-icon">
                                        <i class="fas fa-search-plus"></i>
                                    </div>
                                </div>
                            ' : '<div class="no-image-placeholder">
                                <i class="fas fa-camera"></i>
                                <span>No Photo</span>
                            </div>') . '
                            
                            ' . ($ai_tags ? format_ai_tags($ai_tags) : '') . '
                        </div>
                        
                        <div class="entry-details">
                            <div class="entry-date">
                                
                                <div class="entry-time">' . esc_html($date) . ' ' . esc_html($time) . '</div>
                            </div>
                            <h3 class="entry-title">' . esc_html($title) . '</h3>
                            <p class="entry-text">' . esc_html($content) . '</p>
                            <div class="entry-meta">
                                ' . ($mood ? '<div class="entry-mood">
                                    <i class="fas fa-smile mood-icon"></i> ' . esc_html(wp_trim_words($mood, 15)) . '
                                </div>' : '') . '
                            </div>
                        </div>
                    </div>
                    
                    <!-- Entry Products -->
                   
                    
                    <!-- Entry Completeness Ring -->
                    <div class="entry-completeness-ring">
                        <div class="completeness-circle" style="background: conic-gradient(var(--myavana-coral) 0deg ' . ($completeness * 3.6) . 'deg, rgba(231, 166, 144, 0.2) ' . ($completeness * 3.6) . 'deg 360deg)">
                            <div class="completeness-percentage">' . $completeness . '%</div>
                        </div>
                    </div>
                    
                    <!-- Quick Actions Menu -->
                    <div class="entry-quick-actions">
                        <button class="quick-action-btn" title="Edit Entry" data-action="edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="quick-action-btn" title="Duplicate Entry" data-action="duplicate">
                            <i class="fas fa-copy"></i>
                        </button>
                        <button class="quick-action-btn" title="Share Entry" data-action="share">
                            <i class="fas fa-share-alt"></i>
                        </button>
                        <button class="quick-action-btn" title="Delete Entry" data-action="delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>';
            
            // Generate date marker HTML
            $dates_html .= '
            <div class="date-marker" data-index="' . $index . '">
                <div class="mini-card">
                    ' . ($thumbnail ? '<div class="mini-card-image">
                    <a href="#" class="timeline-small-card-image" style="background-image:url(' . esc_url($thumbnail) . ');"></a>
                        
                    </div>' : '') . '
                    <div class="mini-card-title">' . esc_html($title) . '</div>
                </div>
                <div class="marker-dot"></div>
                <div class="marker-date">' . esc_html($short_date) . '</div>
            </div>';
            
            $index++;
        }
        wp_reset_postdata();
        
        // Calculate stats
        $health_improvement = $first_rating ? round(($last_rating - $first_rating) / $first_rating * 100) : 0;
        $unique_products = count(array_unique($products));
        $entries_count = $entries->post_count;
        $growth = round($entries_count * 0.5); // Simplified growth calculation
        
        $reached_end = $entries_count >= 5; // Demo logic - show end page after 5 entries
    } else {
        $entries_html = '<div class="splide__slide">
            <div class="no-entries" style="text-align: center; padding: 60px 20px; color: var(--myavana-gray-500);">
                <div style="font-size: 3rem; margin-bottom: 20px; color: var(--coral);">✨</div>
                <h3 style="color: var(--onyx); margin-bottom: 15px; font-family: \'Archivo Black\', sans-serif; text-transform: uppercase;">Your Hair Journey Awaits</h3>
                <p style="margin-bottom: 25px; line-height: 1.6;">You haven\'t added any hair journey entries yet. Click the + button below to add your first entry and start documenting your transformation!</p>
                <div style="color: var(--coral); font-size: 2rem;">↓</div>
            </div>
        </div>';
        $health_improvement = 0;
        $unique_products = 0;
        $entries_count = 0;
        $growth = 0;
        $reached_end = false;
    }
    
    wp_send_json_success([
        'entries_html' => $entries_html,
        'dates_html' => $dates_html,
        'stats' => [
            'health' => $health_improvement,
            'growth' => $growth,
            'products_count' => $unique_products,
            'entries_count' => $entries_count
        ],
        'reached_end' => $reached_end
    ]);
}
add_action('wp_ajax_myavana_get_entries', 'myavana_get_entries');

// First, let's create a helper function to parse and format the AI tags
function format_ai_tags($ai_tags) {
    if (empty($ai_tags) || !is_array($ai_tags)) {
        return '';
    }

    // If the first element is a serialized string, unserialize it
    if (is_string($ai_tags[0])) {
        $unserialized = @unserialize($ai_tags[0]);
        if ($unserialized !== false) {
            $ai_tags = $unserialized;
        }
    }

    $formatted_tags = [];
    
    foreach ($ai_tags as $tag) {
        // Skip if tag is empty
        if (empty($tag)) continue;
        
        // Clean up the tag
        $clean_tag = str_replace(['_', '-'], ' ', $tag);
        $clean_tag = ucfirst($clean_tag);
        
        // Extract key/value pairs if they exist
        if (strpos($clean_tag, ':') !== false) {
            $parts = explode(':', $clean_tag, 2);
            $key = trim($parts[0]);
            $value = trim($parts[1]);
            $formatted_tags[$key] = $value;
        } else {
            $formatted_tags[] = $clean_tag;
        }
    }
    
    // Create the HTML for the first 3 relevant tags
    $html = '<div class="ai-tags">';
    $count = 0;
    
    foreach ($formatted_tags as $key => $value) {
        if ($count >= 2) break;
        
        // Determine icon and display text based on tag content
        if (is_numeric($key)) {
            // For non-key-value tags
            if (stripos($value, 'curl') !== false || preg_match('/\d[a-z]/i', $value)) {
                $html .= '<div class="ai-tag"><i class="fas fa-curl"></i> ' . esc_html($value) . '</div>';
                $count++;
            } elseif (stripos($value, 'health') !== false) {
                $html .= '<div class="ai-tag"><i class="fas fa-heart"></i> Health: ' . esc_html(str_replace('health', '', $value)) . '</div>';
                $count++;
            } elseif (stripos($value, 'hydration') !== false) {
                $html .= '<div class="ai-tag"><i class="fas fa-tint"></i> Hydration: ' . esc_html(str_replace('hydration', '', $value)) . '</div>';
                $count++;
            }
        } else {
            // For key-value pairs
            $icon = '';
            $display_text = $key . ': ' . $value;
            
            if (stripos($key, 'health') !== false) {
                $icon = 'fa-heart';
            } elseif (stripos($key, 'hydration') !== false) {
                $icon = 'fa-tint';
            } elseif (stripos($key, 'curl') !== false) {
                $icon = 'fa-curl';
            } elseif (stripos($key, 'length') !== false) {
                $icon = 'fa-ruler';
            }
            
            $html .= '<div class="ai-tag"><i class="fas ' . esc_attr($icon) . '"></i> ' . esc_html($display_text) . '</div>';
            $count++;
        }
    }
    
    $html .= '</div>';
    return $html;
}

/**
 * Delete a hair journey entry
 */
function myavana_delete_entry() {
    check_ajax_referer('myavana_delete_entry', 'security');
    
    $user_id = get_current_user_id();
    $entry_id = isset($_POST['entry_id']) ? intval($_POST['entry_id']) : 0;
    
    if (!$user_id || !$entry_id) {
        wp_send_json_error('Invalid request');
        return;
    }
    
    // Verify the entry belongs to the current user
    $entry = get_post($entry_id);
    if (!$entry || $entry->post_author != $user_id) {
        wp_send_json_error('Entry not found or access denied');
        return;
    }
    
    // Delete the entry
    $result = wp_delete_post($entry_id, true);
    
    if ($result) {
        wp_send_json_success('Entry deleted successfully');
    } else {
        wp_send_json_error('Failed to delete entry');
    }
}
add_action('wp_ajax_myavana_delete_entry', 'myavana_delete_entry');

/**
 * Update a hair journey entry
 */
function myavana_update_entry() {
    // Check nonce - be flexible with nonce name for compatibility
    $nonce_verified = false;
    if (isset($_POST['security'])) {
        $nonce_verified = wp_verify_nonce($_POST['security'], 'myavana_update_entry');
    } elseif (isset($_POST['myavana_nonce'])) {
        $nonce_verified = wp_verify_nonce($_POST['myavana_nonce'], 'myavana_add_entry');
    } elseif (isset($_POST['myavana_add_entry_nonce'])) {
        $nonce_verified = wp_verify_nonce($_POST['myavana_add_entry_nonce'], 'myavana_add_entry');
    }

    if (!$nonce_verified) {
        wp_send_json_error('Security check failed');
        return;
    }

    $user_id = get_current_user_id();
    $entry_id = isset($_POST['entry_id']) ? intval($_POST['entry_id']) : 0;

    if (!$user_id || !$entry_id) {
        wp_send_json_error('Invalid request');
        return;
    }

    // Verify the entry belongs to the current user
    $entry = get_post($entry_id);
    if (!$entry || $entry->post_author != $user_id) {
        wp_send_json_error('Entry not found or access denied');
        return;
    }

    // Validate required fields
    if (empty($_POST['title'])) {
        wp_send_json_error('Title is required');
        return;
    }

    // Sanitize input data
    $title = sanitize_text_field($_POST['title']);
    $description = sanitize_textarea_field($_POST['description']);
    $products = isset($_POST['products']) ? sanitize_text_field($_POST['products']) : '';
    $notes = isset($_POST['notes']) ? sanitize_text_field($_POST['notes']) : '';
    $rating = isset($_POST['rating']) ? min(max(intval($_POST['rating']), 1), 5) : 3;
    $mood = isset($_POST['mood']) ? sanitize_text_field($_POST['mood']) : '';
    $environment = isset($_POST['environment']) ? sanitize_text_field($_POST['environment']) : '';

    // Prepare update array
    $update_data = [
        'ID' => $entry_id,
        'post_title' => $title,
        'post_content' => $description
    ];

    // Handle date if provided (allow user to change entry date)
    if (!empty($_POST['entry_date'])) {
        $entry_date = sanitize_text_field($_POST['entry_date']);
        // Convert YYYY-MM-DD to WordPress datetime format
        $update_data['post_date'] = $entry_date . ' ' . current_time('H:i:s');
        $update_data['post_date_gmt'] = get_gmt_from_date($update_data['post_date']);
    }

    // Update the post
    $updated_post = wp_update_post($update_data);

    if (is_wp_error($updated_post)) {
        wp_send_json_error('Failed to update entry');
        return;
    }

    // Update metadata
    update_post_meta($entry_id, 'products_used', $products);
    update_post_meta($entry_id, 'stylist_notes', $notes);
    update_post_meta($entry_id, 'health_rating', $rating);
    update_post_meta($entry_id, 'mood_demeanor', $mood);
    update_post_meta($entry_id, 'environment', $environment);

    // Handle photo upload if provided
    if (!empty($_FILES['photo']['name'])) {
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $upload = wp_handle_upload($_FILES['photo'], ['test_form' => false]);
        if ($upload && !isset($upload['error'])) {
            // Delete old thumbnail if exists
            $old_thumbnail_id = get_post_thumbnail_id($entry_id);
            if ($old_thumbnail_id) {
                wp_delete_attachment($old_thumbnail_id, true);
            }

            $attachment = [
                'post_mime_type' => $upload['type'],
                'post_title' => sanitize_file_name(basename($upload['file'])),
                'post_content' => '',
                'post_status' => 'inherit',
                'post_author' => $user_id
            ];

            $attachment_id = wp_insert_attachment($attachment, $upload['file'], $entry_id);
            if (!is_wp_error($attachment_id)) {
                $attachment_data = wp_generate_attachment_metadata($attachment_id, $upload['file']);
                wp_update_attachment_metadata($attachment_id, $attachment_data);
                set_post_thumbnail($entry_id, $attachment_id);
            }
        }
    }

    wp_send_json_success([
        'message' => 'Entry updated successfully!',
        'entry_id' => $entry_id
    ]);
}
add_action('wp_ajax_myavana_update_entry', 'myavana_update_entry');

/**
 * Get detailed entry data for modals
 */
function myavana_get_entry_details() {
    check_ajax_referer('myavana_get_entry_details', 'security');

    $user_id = get_current_user_id();
    $entry_id = isset($_POST['entry_id']) ? intval($_POST['entry_id']) : 0;

    if (!$user_id || !$entry_id) {
        wp_send_json_error('Invalid request');
        return;
    }

    // Verify the entry belongs to the current user
    $entry = get_post($entry_id);
    if (!$entry || $entry->post_author != $user_id) {
        wp_send_json_error('Entry not found or access denied');
        return;
    }

    // Get all meta data
    $meta_data = get_post_meta($entry_id);

    // Get thumbnail URL
    $thumbnail_url = get_the_post_thumbnail_url($entry_id, 'large');
    if (!$thumbnail_url) {
        // Fallback to medium or thumbnail size
        $thumbnail_url = get_the_post_thumbnail_url($entry_id, 'medium') ?: get_the_post_thumbnail_url($entry_id, 'thumbnail');
    }

    // Build detailed response
    $detailed_data = [
        'id' => $entry_id,
        'title' => $entry->post_title,
        'description' => $entry->post_content,
        'date' => get_the_date('F j, Y g:i A', $entry_id),
        'entry_date' => get_the_date('Y-m-d', $entry_id), // Raw date for form input
        'rating' => isset($meta_data['health_rating'][0]) ? $meta_data['health_rating'][0] : '5',
        'mood' => isset($meta_data['mood_demeanor'][0]) ? $meta_data['mood_demeanor'][0] : 'Happy',
        'environment' => isset($meta_data['environment'][0]) ? $meta_data['environment'][0] : 'Home',
        'products' => isset($meta_data['products_used'][0]) ? $meta_data['products_used'][0] : '',
        'notes' => isset($meta_data['stylist_notes'][0]) ? $meta_data['stylist_notes'][0] : '',
        'image' => $thumbnail_url ?: '',
        'thumbnail' => $thumbnail_url ?: '', // Alias for compatibility
        'ai_tags' => isset($meta_data['ai_tags'][0]) ? maybe_unserialize($meta_data['ai_tags'][0]) : [],
        'analysis_data' => isset($meta_data['analysis_data'][0]) ? json_decode($meta_data['analysis_data'][0], true) : null,
        'session_id' => isset($meta_data['session_id'][0]) ? $meta_data['session_id'][0] : ''
    ];

    wp_send_json_success($detailed_data);
}
add_action('wp_ajax_myavana_get_entry_details', 'myavana_get_entry_details');

/**
 * AJAX handler for saving hair journey entries (both manual and automated)
 */
function myavana_add_entry() {
    
    global $wpdb;
    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error('User not logged in');
        return;
    }

    $is_automated = isset($_POST['is_automated']) && $_POST['is_automated'] == '1';
    $timestamp = current_time('mysql');

    // Prepare entry data
    if ($is_automated) {
        $analysis = isset($_POST['analysis']) ? json_decode(stripslashes($_POST['analysis']), true) : null;
        if (!$analysis) {
            wp_send_json_error('Invalid analysis data for automated entry');
            return;
        }
        $title = sanitize_text_field('Automated Hair Journey Entry - ' . $timestamp);
        $description = sanitize_textarea_field($analysis['summary'] ?? 'Automated entry from chatbot analysis.');
        // Extract product names only
        $products = array_map(function($product) {
            return sanitize_text_field($product['name'] ?? '');
        }, $analysis['products'] ?? []);
        $products = implode(', ', array_filter($products)); // Filter out empty names
        $notes = sanitize_text_field($analysis['recommendations'] ? implode("\n", $analysis['recommendations']) : '');
        $rating = min(max(intval($analysis['hair_analysis']['health_score'] ?? 5) / 20, 1), 5);
        $session_id = sanitize_text_field($_POST['session_id'] ?? '');
        $tags = myavana_generate_ai_tags_new($analysis);
        $metadata = [
            'analysis_data' => wp_json_encode($analysis),
            'environment' => sanitize_text_field($analysis['environment'] ?? ''),
            'mood_demeanor' => sanitize_text_field($analysis['mood_demeanor'] ?? '')
        ];
    } else {
        // Manual form submission
        if (empty($_POST['title']) || empty($_POST['rating'])) {
            wp_send_json_error('Title and rating are required');
            return;
        }
        $title = sanitize_text_field($_POST['title']);
        $description = sanitize_textarea_field($_POST['description']);

        // Handle products - can be array, comma-separated string, or single value
        $products = '';
        if (isset($_POST['products'])) {
            if (is_array($_POST['products'])) {
                $products = implode(', ', array_map('sanitize_text_field', $_POST['products']));
            } else {
                $products = sanitize_text_field($_POST['products']);
            }
        }

        $notes = sanitize_text_field($_POST['notes'] ?? '');
        $rating = min(max(intval($_POST['rating']), 1), 5);
        $session_id = '';
        $tags = [];
        $metadata = [
            'environment' => sanitize_text_field($_POST['environment'] ?? ''),
            'mood_demeanor' => sanitize_text_field($_POST['mood_demeanor'] ?? '')
        ];
    }

    // Use user-selected date if provided, otherwise use current time
    $post_date = $timestamp;
    if (!empty($_POST['entry_date'])) {
        $entry_date = sanitize_text_field($_POST['entry_date']);
        // Validate date format (YYYY-MM-DD)
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $entry_date)) {
            $post_date = $entry_date . ' ' . current_time('H:i:s');
        }
    }

    // Insert post
    $post_id = wp_insert_post([
        'post_title' => $title,
        'post_content' => $description,
        'post_type' => 'hair_journey_entry',
        'post_status' => 'publish',
        'post_author' => $user_id,
        'post_date' => $post_date
    ]);

    if ($post_id && !is_wp_error($post_id)) {
        // Save metadata
        update_post_meta($post_id, 'products_used', $products);
        update_post_meta($post_id, 'stylist_notes', $notes);
        update_post_meta($post_id, 'health_rating', $rating);
        
        if ($is_automated) {
            update_post_meta($post_id, 'ai_tags', $tags);
            update_post_meta($post_id, 'session_id', $session_id);
        }
        
        // Save additional metadata
        foreach ($metadata as $key => $value) {
            update_post_meta($post_id, $key, $value);
        }

        // Handle photo upload
        $attachment_ids = [];
        $featured_image_index = isset($_POST['featured_image_index']) ? intval($_POST['featured_image_index']) : 0;

        if ($is_automated && !empty($_POST['image_data'])) {
            $attachment_id = myavana_save_screenshot($_POST['image_data'], $post_id, $user_id);
            if ($attachment_id && !is_wp_error($attachment_id)) {
                $attachment_ids[] = $attachment_id;
            }
        } elseif (!empty($_FILES['entry_photos'])) {
            // Handle multiple photos from premium form
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';

            foreach ($_FILES['entry_photos']['name'] as $key => $value) {
                if ($_FILES['entry_photos']['name'][$key]) {
                    $file = [
                        'name'     => $_FILES['entry_photos']['name'][$key],
                        'type'     => $_FILES['entry_photos']['type'][$key],
                        'tmp_name' => $_FILES['entry_photos']['tmp_name'][$key],
                        'error'    => $_FILES['entry_photos']['error'][$key],
                        'size'     => $_FILES['entry_photos']['size'][$key]
                    ];

                    $upload = wp_handle_upload($file, ['test_form' => false]);
                    if ($upload && !isset($upload['error'])) {
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
                            $attachment_ids[] = $attachment_id;
                        }
                    }
                }
            }
        } elseif (!empty($_FILES['photo']['name'])) {
            // Fallback: single photo upload (old form compatibility)
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';

            $upload = wp_handle_upload($_FILES['photo'], ['test_form' => false]);
            if ($upload && !isset($upload['error'])) {
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
                    $attachment_ids[] = $attachment_id;
                }
            }
        }

        // Set featured image (thumbnail) - use the specified index or first image
        if (!empty($attachment_ids)) {
            $featured_index = min($featured_image_index, count($attachment_ids) - 1);
            set_post_thumbnail($post_id, $attachment_ids[$featured_index]);

            // Save all attachment IDs as meta for gallery display
            update_post_meta($post_id, 'entry_photos', $attachment_ids);
        } elseif ($is_automated) {
            wp_delete_post($post_id, true);
            wp_send_json_error('Failed to save photo for automated entry');
            return;
        }

        // Generate AI tip
        try {
            $context = sprintf(
                'User added a %s hair journey entry with title: %s, health rating: %d.',
                $is_automated ? 'automated' : 'manual',
                $title,
                $rating
            );
            $ai = new Myavana_AI();
            $tip = $ai->get_ai_tip($context);
            wp_send_json_success([
                'message' => 'Entry added successfully!',
                'tip' => $tip,
                'entry_id' => $post_id
            ]);
        } catch (Exception $e) {
            wp_send_json_success([
                'message' => 'Entry added successfully!',
                'tip' => 'Keep up with your haircare routine!',
                'entry_id' => $post_id
            ]);
        }

        // Update hair profile for automated entries
        if ($is_automated) {
            myavana_update_hair_profile($user_id, $analysis, $timestamp);
        }
    } else {
        wp_send_json_error('Error adding entry. Please try again.');
    }
}
add_action('wp_ajax_myavana_add_entry', 'myavana_add_entry');

function myavana_save_hair_analysis() {
    global $wpdb;
    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error('User not logged in');
        return;
    }

    $analysis_data = isset($_POST['analysis']) ? json_decode(stripslashes($_POST['analysis']), true) : null;
    if (!$analysis_data) {
        wp_send_json_error('Invalid analysis data');
        return;
    }

    // Process and save the image if it's a base64 string
    $image_url = '';
    if (!empty($analysis_data['image'])) {
        $image_data = $analysis_data['image'];
        // Remove data URL prefix if present
        $image_data = preg_replace('/^data:image\/\w+;base64,/', '', $image_data);
        $decoded = base64_decode($image_data);

        if ($decoded === false) {
            wp_send_json_error('Failed to decode image');
            return;
        }

        // Save file to uploads
        $upload_dir = wp_upload_dir();
        $upload_path = trailingslashit($upload_dir['path']);
        $filename = 'myavana-analysis-' . time() . '.jpg';
        $full_path = $upload_path . $filename;

        if (file_put_contents($full_path, $decoded) === false) {
            wp_send_json_error('Failed to save image file');
            return;
        }

        // Create attachment
        $filetype = wp_check_filetype($filename, null);
        $attachment = [
            'post_mime_type' => $filetype['type'],
            'post_title'     => sanitize_file_name($filename),
            'post_content'   => '',
            'post_status'    => 'inherit',
            'post_author'    => $user_id,
        ];

        $attachment_id = wp_insert_attachment($attachment, $full_path, 0);
        require_once ABSPATH . 'wp-admin/includes/image.php';
        $attachment_data = wp_generate_attachment_metadata($attachment_id, $full_path);
        wp_update_attachment_metadata($attachment_id, $attachment_data);

        $image_url = wp_get_attachment_url($attachment_id);
    }

    // Get existing history
    $analysis_history = get_user_meta($user_id, 'myavana_hair_analysis_history', true) ?: [];
    $table_name = $wpdb->prefix . 'myavana_profiles';
    $profile = $wpdb->get_row($wpdb->prepare("SELECT hair_analysis_snapshots FROM $table_name WHERE user_id = %d", $user_id));
    $snapshots = $profile && $profile->hair_analysis_snapshots ? json_decode($profile->hair_analysis_snapshots, true) : [];

    // Add new analysis to history
    $analysis_history[] = [
        'image' => esc_url_raw($image_url),
        'date' => sanitize_text_field($analysis_data['date']),
        'summary' => sanitize_text_field($analysis_data['summary']),
        'full_analysis' => $analysis_data['full_analysis'],
    ];

    // Add to snapshots
    $snapshots[] = [
        'image_url' => esc_url_raw($image_url),
        'timestamp' => sanitize_text_field($analysis_data['date']),
        'hair_analysis' => $analysis_data['full_analysis']['hair_analysis'],
    ];

    // Update user meta and profile table
    update_user_meta($user_id, 'myavana_hair_analysis_history', $analysis_history);
    $wpdb->update(
        $table_name,
        ['hair_analysis_snapshots' => wp_json_encode($snapshots)],
        ['user_id' => $user_id],
        ['%s'],
        ['%d']
    );

    wp_send_json_success(['message' => 'Analysis saved successfully']);
}
add_action('wp_ajax_myavana_save_hair_analysis', 'myavana_save_hair_analysis');
/**
 * Helper function to generate AI tags for display
 */
function myavana_generate_ai_tags_new($analysis) {
    $tags = [];
    
    if (isset($analysis['hair_analysis'])) {
        if (isset($analysis['hair_analysis']['health_score'])) {
            $health = intval($analysis['hair_analysis']['health_score']);
            $tags[] = '<div class="ai-tag"><i class="fas fa-heart"></i> Health: ' . $health . '%</div>';
        }
        if (isset($analysis['hair_analysis']['hydration'])) {
            $hydration = intval($analysis['hair_analysis']['hydration']);
            $tags[] = '<div class="ai-tag"><i class="fas fa-water"></i> Hydration: ' . $hydration . '%</div>';
        }
        if (isset($analysis['hair_analysis']['curl_pattern'])) {
            $curl_pattern = sanitize_text_field($analysis['hair_analysis']['curl_pattern']);
            $tags[] = '<div class="ai-tag"><i class="fas fa-curl"></i> ' . $curl_pattern . '</div>';
        }
    }
    
    return implode('', $tags);
}

/**
 * Helper function to save base64 image data
 */
// function myavana_save_screenshot($image_data, $post_id, $user_id) {
//     require_once ABSPATH . 'wp-admin/includes/media.php';
//     require_once ABSPATH . 'wp-admin/includes/file.php';
//     require_once ABSPATH . 'wp-admin/includes/image.php';

//     // Remove the data URL prefix
//     $image_data = str_replace('data:image/png;base64,', '', $image_data);
//     $image_data = str_replace(' ', '+', $image_data);
//     $decoded = base64_decode($image_data);
    
//     // Create a temporary file
//     $filename = 'hair-journey-' . $post_id . '-' . time() . '.png';
//     $upload_dir = wp_upload_dir();
//     $file_path = $upload_dir['path'] . '/' . $filename;
    
//     file_put_contents($file_path, $decoded);
    
//     // Prepare the attachment
//     $filetype = wp_check_filetype($filename, null);
//     $attachment = [
//         'post_mime_type' => $filetype['type'],
//         'post_title' => sanitize_file_name($filename),
//         'post_content' => '',
//         'post_status' => 'inherit',
//         'post_author' => $user_id
//     ];
    
//     $attach_id = wp_insert_attachment($attachment, $file_path, $post_id);
//     if (!is_wp_error($attach_id)) {
//         $attach_data = wp_generate_attachment_metadata($attach_id, $file_path);
//         wp_update_attachment_metadata($attach_id, $attach_data);
//         return $attach_id;
//     }
    
//     return 0;
// }