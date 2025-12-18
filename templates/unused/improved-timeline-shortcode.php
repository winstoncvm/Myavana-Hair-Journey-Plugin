<?php
/**
 * MYAVANA Improved Hair Journey Timeline Shortcode
 * Compact, responsive, secure implementation with modern UX
 */

if (!defined('ABSPATH')) {
    exit;
}

class MyavanaTimelineShortcode {
    private $user_id;
    private $atts;
    private $nonce_action = 'myavana_timeline_nonce';

    public function __construct($atts = []) {
        $this->atts = shortcode_atts([
            'show_progress' => 'true',
            'show_stats' => 'true',
            'autoplay' => 'false',
            'entries_per_page' => '8',
            'theme' => 'compact',
            'start_message' => 'Welcome to your hair journey!'
        ], $atts, 'myavana_improved_timeline');

        $this->user_id = get_current_user_id();
    }

    public function render() {
        if (!$this->is_user_authorized()) {
            return $this->render_auth_required();
        }

        $this->enqueue_assets();
        return $this->render_timeline();
    }

    private function is_user_authorized() {
        return is_user_logged_in();
    }

    private function enqueue_assets() {
        // Include AJAX handlers
        require_once MYAVANA_DIR . 'templates/timeline-ajax-handlers.php';

        // Only enqueue if not already done
        if (!wp_script_is('myavana-improved-timeline', 'enqueued')) {
            // Styles - Use main myavana styles which now includes improved timeline
            wp_enqueue_style(
                'myavana-styles',
                MYAVANA_URL . 'assets/css/myavana-styles.css',
                [],
                '2.1.0'
            );

            // Scripts
            wp_enqueue_script(
                'swiper-js',
                'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js',
                [],
                '11.0.0',
                true
            );

            wp_enqueue_script(
                'myavana-improved-timeline',
                MYAVANA_URL . 'assets/js/improved-timeline.js',
                ['jquery', 'swiper-js'],
                '2.1.0',
                true
            );

            // Localize script for AJAX
            wp_localize_script('myavana-improved-timeline', 'myavanaTimeline', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce($this->nonce_action),
                'user_id' => $this->user_id,
                'entries_per_page' => intval($this->atts['entries_per_page']),
                'strings' => [
                    'loading' => __('Loading your hair journey...', 'myavana'),
                    'error' => __('Error loading timeline. Please try again.', 'myavana'),
                    'no_entries' => __('No entries found. Start your journey!', 'myavana'),
                    'save_success' => __('Entry saved successfully!', 'myavana'),
                    'delete_confirm' => __('Are you sure you want to delete this entry?', 'myavana')
                ]
            ]);
        }
    }

    private function render_auth_required() {
        return sprintf(
            '<div class="myavana-auth-required">
                <div class="myavana-auth-card">
                    <div class="myavana-auth-icon">🔒</div>
                    <h3 class="myavana-auth-title">%s</h3>
                    <p class="myavana-auth-text">%s</p>
                    <a href="%s" class="myavana-btn-coral">%s</a>
                </div>
            </div>',
            esc_html__('Authentication Required', 'myavana'),
            esc_html__('Please log in to view your hair journey timeline.', 'myavana'),
            esc_url(wp_login_url(get_permalink())),
            esc_html__('LOGIN', 'myavana')
        );
    }

    private function render_timeline() {
        $user_stats = $this->get_user_stats();

        ob_start();
        ?>
        <div class="myavana-improved-timeline" data-theme="<?php echo esc_attr($this->atts['theme']); ?>">

            <!-- Timeline Container -->
            <div class="myavana-timeline-container">

                <!-- Header Section -->
                <header class="myavana-timeline-header">
                    <div class="myavana-timeline-brand">
                        <h1 class="myavana-timeline-title">My Hair Journey</h1>
                        <p class="myavana-timeline-subtitle">Track your transformation</p>
                    </div>

                    <?php if ($this->atts['show_stats'] === 'true'): ?>
                    <div class="myavana-timeline-stats">
                        <div class="myavana-stat-item">
                            <span class="myavana-stat-number" id="totalEntries"><?php echo esc_html($user_stats['total_entries']); ?></span>
                            <span class="myavana-stat-label">Entries</span>
                        </div>
                        <div class="myavana-stat-item">
                            <span class="myavana-stat-number" id="avgHealth"><?php echo esc_html($user_stats['avg_health']); ?></span>
                            <span class="myavana-stat-label">Avg Health</span>
                        </div>
                        <div class="myavana-stat-item">
                            <span class="myavana-stat-number" id="dayStreak"><?php echo esc_html($user_stats['day_streak']); ?></span>
                            <span class="myavana-stat-label">Day Streak</span>
                        </div>
                    </div>
                    <?php endif; ?>
                </header>

                <!-- Timeline Slider -->
                <div class="myavana-timeline-slider">
                    <div class="swiper myavana-swiper">
                        <div class="swiper-wrapper" id="timelineSlides">
                            <!-- Start Slide -->
                            <div class="swiper-slide myavana-start-slide">
                                <div class="myavana-start-content">
                                    <div class="myavana-start-icon">✨</div>
                                    <h2 class="myavana-start-title">Welcome to Your Hair Journey!</h2>
                                    <p class="myavana-start-description"><?php echo esc_html($this->atts['start_message']); ?></p>
                                    <div class="myavana-start-features">
                                        <div class="myavana-feature-item">
                                            <span class="myavana-feature-icon">📸</span>
                                            <span>Photo Timeline</span>
                                        </div>
                                        <div class="myavana-feature-item">
                                            <span class="myavana-feature-icon">📊</span>
                                            <span>Progress Tracking</span>
                                        </div>
                                        <div class="myavana-feature-item">
                                            <span class="myavana-feature-icon">🎯</span>
                                            <span>Goal Setting</span>
                                        </div>
                                    </div>
                                    <button class="myavana-btn-start" id="beginJourneyBtn">
                                        <span>Begin Journey</span>
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M8 5v14l11-7z"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Timeline entries will be loaded here via AJAX -->
                        </div>

                        <!-- Navigation -->
                        <div class="myavana-timeline-navigation">
                            <button class="myavana-nav-btn myavana-nav-prev" id="timelinePrev">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M15.41 16.58L10.83 12l4.58-4.58L14 6l-6 6 6 6 1.41-1.42z"/>
                                </svg>
                            </button>
                            <div class="myavana-timeline-progress">
                                <div class="myavana-progress-track">
                                    <div class="myavana-progress-fill" id="progressFill"></div>
                                </div>
                                <div class="myavana-progress-info">
                                    <span id="currentSlide">1</span> / <span id="totalSlides">1</span>
                                </div>
                            </div>
                            <button class="myavana-nav-btn myavana-nav-next" id="timelineNext">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M8.59 16.58L13.17 12 8.59 7.42 10 6l6 6-6 6-1.41-1.42z"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="myavana-timeline-actions">
                    <button class="myavana-action-btn myavana-add-entry" id="addEntryBtn">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
                        </svg>
                        <span>Add Entry</span>
                    </button>

                    <button class="myavana-action-btn myavana-view-all" id="viewAllBtn">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M3 13h2v-2H3v2zm0 4h2v-2H3v2zm0-8h2V7H3v2zm4 4h14v-2H7v2zm0 4h14v-2H7v2zM7 7v2h14V7H7z"/>
                        </svg>
                        <span>View All</span>
                    </button>

                    <?php if ($this->atts['show_progress'] === 'true'): ?>
                    <button class="myavana-action-btn myavana-progress" id="progressBtn">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M16 6l2.29 2.29-4.88 4.88-4-4L2 16.59 3.41 18l6-6 4 4 6.3-6.29L22 12V6z"/>
                        </svg>
                        <span>Progress</span>
                    </button>
                    <?php endif; ?>
                </div>

                <!-- Loading States -->
                <div class="myavana-timeline-loading" id="timelineLoading" style="display: none;">
                    <div class="myavana-loading-spinner"></div>
                    <p>Loading your hair journey...</p>
                </div>

                <!-- Empty State -->
                <div class="myavana-timeline-empty" id="timelineEmpty" style="display: none;">
                    <div class="myavana-empty-icon">📷</div>
                    <h3>Start Your Journey</h3>
                    <p>Add your first hair journey entry to begin tracking your progress.</p>
                    <button class="myavana-btn-coral" onclick="document.getElementById('addEntryBtn').click()">
                        Add First Entry
                    </button>
                </div>

            </div>

        </div>

        <!-- Add Entry Modal -->
        <?php echo $this->render_entry_modal(); ?>

        <!-- Progress Modal -->
        <?php if ($this->atts['show_progress'] === 'true'): ?>
        <?php echo $this->render_progress_modal(); ?>
        <?php endif; ?>

        <?php
        return ob_get_clean();
    }

    private function render_entry_modal() {
        ob_start();
        ?>
        <div class="myavana-modal-overlay" id="entryModal" aria-hidden="true">
            <div class="myavana-modal-container">
                <div class="myavana-modal-header">
                    <h2 class="myavana-modal-title" id="modalTitle">Add Hair Journey Entry</h2>
                    <button class="myavana-modal-close" id="modalClose" aria-label="Close modal">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                        </svg>
                    </button>
                </div>

                <div class="myavana-modal-body">
                    <form id="entryForm" class="myavana-entry-form" novalidate>
                        <div class="myavana-form-group">
                            <label for="entryTitle" class="myavana-form-label">Entry Title *</label>
                            <input type="text" id="entryTitle" name="title" required
                                   class="myavana-form-input" placeholder="e.g., Monthly Trim"
                                   maxlength="100">
                            <div class="myavana-form-error" id="titleError"></div>
                        </div>

                        <div class="myavana-form-group">
                            <label for="entryDescription" class="myavana-form-label">Description</label>
                            <textarea id="entryDescription" name="description"
                                      class="myavana-form-textarea" rows="4"
                                      placeholder="Describe your hair care experience..."
                                      maxlength="500"></textarea>
                            <div class="myavana-char-counter">
                                <span id="descCounter">0</span>/500
                            </div>
                        </div>

                        <div class="myavana-form-row">
                            <div class="myavana-form-group">
                                <label for="healthRating" class="myavana-form-label">Health Rating *</label>
                                <div class="myavana-rating-container">
                                    <input type="range" id="healthRating" name="rating"
                                           min="1" max="10" value="5" class="myavana-range-input">
                                    <div class="myavana-rating-display">
                                        <span id="ratingValue">5</span>/10
                                    </div>
                                </div>
                            </div>

                            <div class="myavana-form-group">
                                <label for="entryMood" class="myavana-form-label">Mood</label>
                                <select id="entryMood" name="mood_demeanor" class="myavana-form-select">
                                    <option value="excited">😊 Excited</option>
                                    <option value="happy">😄 Happy</option>
                                    <option value="optimistic">🌟 Optimistic</option>
                                    <option value="neutral">😐 Neutral</option>
                                    <option value="concerned">😟 Concerned</option>
                                </select>
                            </div>
                        </div>

                        <div class="myavana-form-group">
                            <label for="entryProducts" class="myavana-form-label">Products Used</label>
                            <input type="text" id="entryProducts" name="products"
                                   class="myavana-form-input"
                                   placeholder="e.g., Moisturizing Shampoo, Leave-in Conditioner">
                        </div>

                        <div class="myavana-form-group">
                            <label for="entryPhoto" class="myavana-form-label">Photo</label>
                            <div class="myavana-photo-upload">
                                <input type="file" id="entryPhoto" name="photo" accept="image/*" hidden>
                                <label for="entryPhoto" class="myavana-photo-label">
                                    <div class="myavana-photo-placeholder">
                                        <svg width="32" height="32" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M9 16.17L4.83 12l-1.42 1.42L9 19 21 7l-1.42-1.42z"/>
                                        </svg>
                                        <span>Choose Photo</span>
                                    </div>
                                </label>
                                <div class="myavana-photo-preview" id="photoPreview" style="display: none;">
                                    <img id="previewImage" alt="Preview">
                                    <button type="button" class="myavana-remove-photo" id="removePhoto">×</button>
                                </div>
                            </div>
                        </div>

                        <input type="hidden" id="entryId" name="entry_id" value="">
                        <?php wp_nonce_field($this->nonce_action, 'myavana_nonce'); ?>
                    </form>
                </div>

                <div class="myavana-modal-footer">
                    <button type="button" class="myavana-btn-secondary" id="cancelEntry">Cancel</button>
                    <button type="submit" form="entryForm" class="myavana-btn-coral" id="saveEntry">
                        <span class="myavana-btn-text">Save Entry</span>
                        <span class="myavana-btn-loading" style="display: none;">
                            <span class="myavana-loading-spinner-sm"></span>
                            Saving...
                        </span>
                    </button>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_progress_modal() {
        ob_start();
        ?>
        <div class="myavana-modal-overlay" id="progressModal" aria-hidden="true">
            <div class="myavana-modal-container myavana-modal-large">
                <div class="myavana-modal-header">
                    <h2 class="myavana-modal-title">Your Progress</h2>
                    <button class="myavana-modal-close" onclick="closeModal('progressModal')" aria-label="Close progress">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                        </svg>
                    </button>
                </div>

                <div class="myavana-modal-body">
                    <div class="myavana-progress-content" id="progressContent">
                        <!-- Progress content will be loaded via AJAX -->
                        <div class="myavana-loading-state">
                            <div class="myavana-loading-spinner"></div>
                            <p>Loading your progress...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function get_user_stats() {
        $cache_key = "myavana_user_stats_{$this->user_id}";
        $stats = wp_cache_get($cache_key);

        if (false === $stats) {
            global $wpdb;

            $query = $wpdb->prepare("
                SELECT
                    COUNT(*) as total_entries,
                    AVG(CAST(meta_rating.meta_value AS DECIMAL(3,1))) as avg_health
                FROM {$wpdb->posts} p
                LEFT JOIN {$wpdb->postmeta} meta_rating
                    ON p.ID = meta_rating.post_id
                    AND meta_rating.meta_key = 'hair_health_rating'
                WHERE p.post_type = 'hair_journey_entry'
                    AND p.post_author = %d
                    AND p.post_status = 'publish'
            ", $this->user_id);

            $result = $wpdb->get_row($query);

            $stats = [
                'total_entries' => intval($result->total_entries ?? 0),
                'avg_health' => $result->avg_health ? round(floatval($result->avg_health), 1) : 0,
                'day_streak' => $this->calculate_day_streak()
            ];

            wp_cache_set($cache_key, $stats, '', HOUR_IN_SECONDS);
        }

        return $stats;
    }

    private function calculate_day_streak() {
        global $wpdb;

        $query = $wpdb->prepare("
            SELECT post_date
            FROM {$wpdb->posts}
            WHERE post_type = 'hair_journey_entry'
                AND post_author = %d
                AND post_status = 'publish'
            ORDER BY post_date DESC
            LIMIT 30
        ", $this->user_id);

        $dates = $wpdb->get_col($query);

        if (empty($dates)) {
            return 0;
        }

        $streak = 1;
        $current_date = new DateTime($dates[0]);

        for ($i = 1; $i < count($dates); $i++) {
            $next_date = new DateTime($dates[$i]);
            $diff = $current_date->diff($next_date)->days;

            if ($diff <= 7) { // Within a week counts as maintaining streak
                $streak++;
                $current_date = $next_date;
            } else {
                break;
            }
        }

        return $streak;
    }
}

// Register the improved shortcode
function myavana_improved_timeline_shortcode($atts = []) {
    $timeline = new MyavanaTimelineShortcode($atts);
    return $timeline->render();
}
add_shortcode('myavana_improved_timeline', 'myavana_improved_timeline_shortcode');

// AJAX Handlers
require_once __DIR__ . '/timeline-ajax-handlers.php';