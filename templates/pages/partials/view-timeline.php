<?php
/**
 * Luxury Timeline View - Hair Journey
 * Vertical timeline with alternating cards
 * Mobile-first responsive design with MYAVANA branding
 */

// Determine current user ID safely
$user_id = 0;
if ( isset($current_user) && ! empty($current_user->ID) ) {
    $user_id = intval($current_user->ID);
} else {
    $user_id = get_current_user_id();
}

// Fallback: if no user, render auth required message
if ( ! $user_id ) : ?>
    <div id="timelineView" class="view-content timeline-view-hjn">
        <div class="timeline-empty-hjn">
            <h2>Sign in to view your journey timeline</h2>
        </div>
    </div>
<?php
    return;
endif;

// Fetch all data
$hair_goals = get_user_meta($user_id, 'myavana_hair_goals_structured', true) ?: [];
$current_routine = get_user_meta($user_id, 'myavana_current_routine', true) ?: [];

$entries_args = [
    'post_type' => 'hair_journey_entry',
    'author' => $user_id,
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'orderby' => 'post_date',
    'order' => 'DESC',
];
$entries = get_posts($entries_args);

// Combine all items with dates for sorting
$timeline_items = [];

// Add goals
foreach ($hair_goals as $idx => $goal) {
    $start_date = $goal['start_date'] ?? $goal['start'] ?? '';
    if ($start_date) {
        $timeline_items[] = [
            'type' => 'goal',
            'date' => strtotime($start_date),
            'data' => $goal,
            'index' => $idx,
        ];
    }
}

// Add entries
foreach ($entries as $entry) {
    $timeline_items[] = [
        'type' => 'entry',
        'date' => strtotime($entry->post_date),
        'data' => $entry,
        'index' => $entry->ID,
    ];
}

// Sort by date descending
usort($timeline_items, function($a, $b) {
    return $b['date'] - $a['date'];
});

// Group by month
$grouped_items = [];
foreach ($timeline_items as $item) {
    $month_key = date('F Y', $item['date']);
    if (!isset($grouped_items[$month_key])) {
        $grouped_items[$month_key] = [];
    }
    $grouped_items[$month_key][] = $item;
}
?>

<!-- Timeline View -->
<div id="timelineView" class="view-content timeline-view-hjn">
    <!-- Timeline Filter Controls -->
    <div class="timeline-filter-controls-hjn">
        <div class="timeline-search-bar-hjn">
            <svg viewBox="0 0 24 24" width="18" height="18">
                <path fill="currentColor" d="M9.5,3A6.5,6.5 0 0,1 16,9.5C16,11.11 15.41,12.59 14.44,13.73L14.71,14H15.5L20.5,19L19,20.5L14,15.5V14.71L13.73,14.44C12.59,15.41 11.11,16 9.5,16A6.5,6.5 0 0,1 3,9.5A6.5,6.5 0 0,1 9.5,3M9.5,5C7,5 5,7 5,9.5C5,12 7,14 9.5,14C12,14 14,12 14,9.5C14,7 12,5 9.5,5Z"/>
            </svg>
            <input
                type="search"
                id="timelineSearchInput"
                class="timeline-search-input-hjn"
                placeholder="Search timeline..."
                oninput="applyTimelineFilters()"
            >
        </div>
        <div class="timeline-filter-buttons-hjn">
            <button class="timeline-filter-btn-hjn active" data-filter="all" onclick="setTimelineFilter('all')">
                All
            </button>
            <button class="timeline-filter-btn-hjn" data-filter="entry" onclick="setTimelineFilter('entry')">
                <svg viewBox="0 0 24 24" width="14" height="14">
                    <path fill="currentColor" d="M14,3V5H17.59L7.76,14.83L9.17,16.24L19,6.41V10H21V3M19,19H5V5H12V3H5C3.89,3 3,3.9 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19V12H19V19Z"/>
                </svg>
                Entries
            </button>
            <button class="timeline-filter-btn-hjn" data-filter="goal" onclick="setTimelineFilter('goal')">
                <svg viewBox="0 0 24 24" width="14" height="14">
                    <path fill="currentColor" d="M12,2A10,10 0 0,1 22,12A10,10 0 0,1 12,22A10,10 0 0,1 2,12A10,10 0 0,1 12,2M12,4A8,8 0 0,0 4,12A8,8 0 0,0 12,20A8,8 0 0,0 20,12A8,8 0 0,0 12,4M12,6A6,6 0 0,1 18,12A6,6 0 0,1 12,18A6,6 0 0,1 6,12A6,6 0 0,1 12,6M12,8A4,4 0 0,0 8,12A4,4 0 0,0 12,16A4,4 0 0,0 16,12A4,4 0 0,0 12,8Z"/>
                </svg>
                Goals
            </button>
            <button class="timeline-filter-btn-hjn" onclick="toggleTimelineFilterPanel()">
                <svg viewBox="0 0 24 24" width="14" height="14">
                    <path fill="currentColor" d="M14,12V19.88C14.04,20.18 13.94,20.5 13.71,20.71C13.32,21.1 12.69,21.1 12.3,20.71L10.29,18.7C10.06,18.47 9.96,18.16 10,17.87V12H9.97L4.21,4.62C3.87,4.19 3.95,3.56 4.38,3.22C4.57,3.08 4.78,3 5,3V3H19V3C19.22,3 19.43,3.08 19.62,3.22C20.05,3.56 20.13,4.19 19.79,4.62L14.03,12H14Z"/>
                </svg>
                More
            </button>
        </div>
    </div>

    <!-- Advanced Filter Panel (Hidden by default) -->
    <div class="timeline-filters-panel-hjn" id="timelineFiltersPanel" style="display: none;">
        <div class="filter-section-hjn">
            <label class="filter-label-hjn">
                <svg viewBox="0 0 24 24" width="16" height="16">
                    <path fill="currentColor" d="M12,17.27L18.18,21L16.54,13.97L22,9.24L14.81,8.62L12,2L9.19,8.62L2,9.24L7.45,13.97L5.82,21L12,17.27Z"/>
                </svg>
                Minimum Rating
            </label>
            <select id="timelineFilterRating" class="filter-select-hjn" onchange="applyTimelineFilters()">
                <option value="0">All Ratings</option>
                <option value="5">5 Stars</option>
                <option value="4">4+ Stars</option>
                <option value="3">3+ Stars</option>
                <option value="2">2+ Stars</option>
                <option value="1">1+ Stars</option>
            </select>
        </div>
        <div class="filter-actions-hjn">
            <button class="filter-clear-btn-hjn" onclick="clearTimelineFilters()">
                Clear Filters
            </button>
        </div>
    </div>

    <?php if (empty($timeline_items)): ?>
        <!-- Empty State -->
        <div class="timeline-empty-state-hjn">
            <svg viewBox="0 0 24 24" width="64" height="64">
                <path fill="currentColor" d="M13.5,8H12V13L16.28,15.54L17,14.33L13.5,12.25V8M13,3A9,9 0 0,0 4,12H1L4.96,16.03L9,12H6A7,7 0 0,1 13,5A7,7 0 0,1 20,12A7,7 0 0,1 13,19C11.07,19 9.32,18.21 8.06,16.94L6.64,18.36C8.27,20 10.5,21 13,21A9,9 0 0,0 22,12A9,9 0 0,0 13,3"/>
            </svg>
            <h3>Start Your Hair Journey</h3>
            <p>Add your first entry, goal, or routine to see your timeline</p>
            <button class="timeline-cta-btn-hjn" onclick="openOffcanvas('entry')">
                Add Your First Entry
            </button>
        </div>
    <?php else: ?>
        <!-- Timeline Wrapper -->
        <div class="timeline-wrapper-hjn">
            <!-- Vertical Line -->
            <div class="timeline-line-hjn"></div>

            <?php
            $item_counter = 0;
            foreach ($grouped_items as $month_label => $month_items):
            ?>
                <!-- Month Section -->
                <div class="timeline-month-hjn">
                    <div class="timeline-month-label-hjn">
                        <span><?php echo esc_html($month_label); ?></span>
                    </div>

                    <?php foreach ($month_items as $item):
                        $side = ($item_counter % 2 === 0) ? 'left' : 'right';
                        $item_counter++;

                        // Render based on type
                        if ($item['type'] === 'entry'):
                            $entry = $item['data'];
                            $post_id = $entry->ID;
                            $title = get_the_title($post_id);
                            $content = wp_strip_all_tags($entry->post_content);
                            $excerpt = wp_trim_words($content, 20);
                            $date_formatted = get_the_date('M j, Y', $post_id);
                            $time_formatted = get_the_date('g:i A', $post_id);
                            $thumbnail = get_the_post_thumbnail_url($post_id, 'medium');
                            $rating = get_post_meta($post_id, 'health_rating', true);
                            $mood = get_post_meta($post_id, 'mood_demeanor', true);
                            $products = get_post_meta($post_id, 'products_used', true);
                        ?>
                            <div class="timeline-item-hjn timeline-item-<?php echo $side; ?>-hjn" data-type="entry">
                                <div class="timeline-content-hjn">
                                    <div class="timeline-card-hjn timeline-card-entry-hjn" onclick="openViewOffcanvas('entry', <?php echo $post_id; ?>)">
                                        <?php if ($thumbnail): ?>
                                        <div class="timeline-card-image-hjn">
                                            <img src="<?php echo esc_url($thumbnail); ?>" alt="<?php echo esc_attr($title); ?>" loading="lazy" />
                                            <?php if ($rating): ?>
                                            <div class="timeline-image-rating-hjn">
                                                <svg viewBox="0 0 24 24" width="14" height="14">
                                                    <path fill="currentColor" d="M12,17.27L18.18,21L16.54,13.97L22,9.24L14.81,8.62L12,2L9.19,8.62L2,9.24L7.45,13.97L5.82,21L12,17.27Z"/>
                                                </svg>
                                                <?php echo esc_html($rating); ?>/10
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        <?php endif; ?>

                                        <div class="timeline-card-body-hjn">
                                            <div class="timeline-card-header-hjn">
                                                <div class="timeline-card-type-hjn">
                                                    <svg viewBox="0 0 24 24" width="16" height="16">
                                                        <path fill="currentColor" d="M4,4H7L9,2H15L17,4H20A2,2 0 0,1 22,6V18A2,2 0 0,1 20,20H4A2,2 0 0,1 2,18V6A2,2 0 0,1 4,4M12,7A5,5 0 0,0 7,12A5,5 0 0,0 12,17A5,5 0 0,0 17,12A5,5 0 0,0 12,7M12,9A3,3 0 0,1 15,12A3,3 0 0,1 12,15A3,3 0 0,1 9,12A3,3 0 0,1 12,9Z"/>
                                                    </svg>
                                                    Entry
                                                </div>
                                                <div class="timeline-card-date-hjn"><?php echo esc_html($date_formatted); ?></div>
                                            </div>

                                            <h3 class="timeline-card-title-hjn"><?php echo esc_html($title); ?></h3>

                                            <?php if ($excerpt): ?>
                                            <p class="timeline-card-description-hjn"><?php echo esc_html($excerpt); ?></p>
                                            <?php endif; ?>

                                            <?php if ($mood || $products): ?>
                                            <div class="timeline-card-tags-hjn">
                                                <?php if ($mood): ?>
                                                <span class="timeline-tag-hjn"><?php echo esc_html($mood); ?></span>
                                                <?php endif; ?>
                                                <?php if ($products):
                                                    $product_count = count(array_filter(explode(',', $products)));
                                                ?>
                                                <span class="timeline-tag-hjn"><?php echo $product_count; ?> Products</span>
                                                <?php endif; ?>
                                            </div>
                                            <?php endif; ?>

                                            <div class="timeline-card-time-hjn"><?php echo esc_html($time_formatted); ?></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="timeline-dot-hjn"></div>
                            </div>

                        <?php elseif ($item['type'] === 'goal'):
                            $goal = $item['data'];
                            $title = $goal['title'] ?? $goal['goal_title'] ?? 'Untitled Goal';
                            $start = $goal['start_date'] ?? $goal['start'] ?? '';
                            $end = $goal['end_date'] ?? $goal['end'] ?? '';
                            $progress = isset($goal['progress']) ? intval($goal['progress']) : (isset($goal['progress_percent']) ? intval($goal['progress_percent']) : 0);
                            $description = $goal['description'] ?? $goal['notes'] ?? '';
                            $date_formatted = date('M j, Y', strtotime($start));

                            $date_range = '';
                            if ($start || $end) {
                                $start_formatted = $start ? date('M j', strtotime($start)) : '';
                                $end_formatted = $end ? date('M j, Y', strtotime($end)) : '';
                                $date_range = trim($start_formatted . ($start && $end ? ' - ' : '') . $end_formatted);
                            }
                        ?>
                            <div class="timeline-item-hjn timeline-item-<?php echo $side; ?>-hjn" data-type="goal">
                                <div class="timeline-content-hjn">
                                    <div class="timeline-card-hjn timeline-card-goal-hjn" onclick="openViewOffcanvas('goal', <?php echo $item['index']; ?>)">
                                        <div class="timeline-card-body-hjn">
                                            <div class="timeline-card-header-hjn">
                                                <div class="timeline-card-type-hjn">
                                                    <svg viewBox="0 0 24 24" width="16" height="16">
                                                        <path fill="currentColor" d="M12,2A10,10 0 0,1 22,12A10,10 0 0,1 12,22A10,10 0 0,1 2,12A10,10 0 0,1 12,2M12,4A8,8 0 0,0 4,12A8,8 0 0,0 12,20A8,8 0 0,0 20,12A8,8 0 0,0 12,4M12,6A6,6 0 0,1 18,12A6,6 0 0,1 12,18A6,6 0 0,1 6,12A6,6 0 0,1 12,6M12,8A4,4 0 0,0 8,12A4,4 0 0,0 12,16A4,4 0 0,0 16,12A4,4 0 0,0 12,8Z"/>
                                                    </svg>
                                                    Goal
                                                </div>
                                                <div class="timeline-card-progress-badge-hjn"><?php echo $progress; ?>%</div>
                                            </div>

                                            <h3 class="timeline-card-title-hjn"><?php echo esc_html($title); ?></h3>

                                            <?php if ($description): ?>
                                            <p class="timeline-card-description-hjn"><?php echo esc_html(wp_trim_words($description, 15)); ?></p>
                                            <?php endif; ?>

                                            <?php if ($date_range): ?>
                                            <div class="timeline-card-tags-hjn">
                                                <span class="timeline-tag-hjn"><?php echo esc_html($date_range); ?></span>
                                            </div>
                                            <?php endif; ?>

                                            <div class="timeline-card-progress-bar-hjn">
                                                <div class="timeline-progress-fill-hjn" style="width: <?php echo $progress; ?>%"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="timeline-dot-hjn"></div>
                            </div>

                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
