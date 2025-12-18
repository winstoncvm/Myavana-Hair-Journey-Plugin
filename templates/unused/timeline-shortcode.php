<?php
function myavana_timeline_shortcode_old($atts = []) {
    $atts = shortcode_atts(['user_id' => get_current_user_id()], $atts);
    $user_id = intval($atts['user_id']);

    if (!is_user_logged_in()) {
        return '<p>Please <a href="' . esc_url(home_url('/login')) . '" class="text-blue-600 underline">Log in</a> to view the timeline.</p>';
    }

    if (!get_userdata($user_id)) {
        return '<p class="text-red-600">Invalid user timeline.</p>';
    }

    // Fetch hair journey entries
    $args = [
        'post_type' => 'hair_journey_entry',
        'author' => $user_id,
        'posts_per_page' => -1, // Fetch all entries to ensure complete timeline
        'orderby' => 'date',
        'order' => 'ASC'
    ];
    $entries = new WP_Query($args);
    $events = [];
    $entry_dates = [];
    $grouped_events = [];

    // Group entries by date to handle multiple entries per day
    while ($entries->have_posts()) : $entries->the_post();
        $thumbnail_url = get_the_post_thumbnail_url(get_the_ID(), 'medium');
        $thumbnail_small = get_the_post_thumbnail_url(get_the_ID(), 'thumbnail');
        $entry_date = get_the_date('Y-m-d');

        if (!$thumbnail_url || !filter_var($thumbnail_url, FILTER_VALIDATE_URL)) {
            $thumbnail_url = null;
            error_log('Myavana Hair Journey: Invalid or missing thumbnail URL for post ' . get_the_ID());
        }
        if (!$thumbnail_small || !filter_var($thumbnail_small, FILTER_VALIDATE_URL)) {
            $thumbnail_small = null;
        }

        $event = [
            'start_date' => [
                'year' => get_the_date('Y'),
                'month' => get_the_date('m'),
                'day' => get_the_date('d')
            ],
            'text' => [
                'headline' => get_the_title(),
                'text' => get_the_content()
            ],
            'media' => $thumbnail_url ? [
                'url' => $thumbnail_url,
                'thumbnail' => $thumbnail_small ?: $thumbnail_url
            ] : null,
            'custom_fields' => [
                'products_used' => get_post_meta(get_the_ID(), 'products_used', true),
                'health_rating' => get_post_meta(get_the_ID(), 'health_rating', true)
            ]
        ];

        // Group events by date
        if (!isset($grouped_events[$entry_date])) {
            $grouped_events[$entry_date] = [
                'start_date' => $event['start_date'],
                'text' => [
                    'headline' => 'Hair Journey: ' . get_the_date('F j, Y'),
                    'text' => ''
                ],
                'group' => $entry_date,
                'sub_events' => []
            ];
        }
        $grouped_events[$entry_date]['sub_events'][] = $event;
        $entry_dates[] = $entry_date;
    endwhile;
    wp_reset_postdata();

    // Combine sub-events into main events for TimelineJS
    foreach ($grouped_events as $date => $group) {
        $combined_text = array_map(function($e) {
            return '<h4>' . esc_html($e['text']['headline']) . '</h4><p>' . esc_html($e['text']['text']) . '</p>';
        }, $group['sub_events']);
        $group['text']['text'] = implode('', $combined_text);
        if (!empty($group['sub_events'][0]['media'])) {
            $group['media'] = $group['sub_events'][0]['media'];
        }
        $events[] = $group;
    }

    // TimelineJS data
    $timeline_object = [
        'title' => [
            'text' => [
                'headline' => 'Myavana Hair Journey',
                'text' => 'Your Personal Hair Journey'
            ],
            'media' => [
                'url' => 'https://www.myavana.com/cdn/shop/files/myavana-homepage-hairai_3f6d7318-35d1-4d25-88c5-2b5faa0d6d63.jpg'
            ]
        ],
        'scale' => 'human',
        'events' => $events
    ];

    $timeline_json = json_encode($timeline_object, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log('Myavana Hair Journey: JSON encoding error: ' . json_last_error_msg());
        return '<p class="text-red-600">Error generating timeline. Please try again later.</p>';
    }

    $div_id = 'timeline-' . $user_id;
    $add_entry_url = home_url('/members/' . bp_core_get_username($user_id) . '/hair_entry/');

    // Generate calendar dates (mockup for current month, e.g., June 2025)
    $current_year = 2025;
    $current_month = 6;
    $days_in_month = cal_days_in_month(CAL_GREGORIAN, $current_month, $current_year);
    $first_day = date('w', strtotime("$current_year-$current_month-01"));
    $calendar_days = array_fill(0, $first_day, null);
    for ($day = 1; $day <= $days_in_month; $day++) {
        $calendar_days[] = $day;
    }

    ob_start();
    ?>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.knightlab.com/libs/timeline3/latest/css/timeline.css">
    

    <div class="myavana-timeline" id="myavanaTimelineContent">
        <div class="timeline-content">
            <?php if (empty($events)) : ?>
                <div class="no-entries bg-white rounded-lg shadow p-4 mb-4">
                    <p class="no-entries-message text-gray-600">You haven't added any hair journey entries yet. Start your journey today!</p>
                    <a href="<?php echo esc_url($add_entry_url); ?>" class="mt-3 inline-block text-blue-600 hover:underline">Add Your First Entry</a>
                </div>
            <?php else : ?>
                <!-- Diary-like Timeline -->
                <div class="diary-timeline-container flex flex-col md:flex-row gap-6 mb-8">
                    <!-- Calendar Section -->
                    <div class="calendar-container w-full md:w-1/3">
                        <h3 class="myavana-title my-3">Hair Journey Calendar</h3>
                        <div class="calendar-header flex justify-between mb-4">
                            <button id="prev-month" class="nxt-prev">Prev</button>
                            <h4 class="start-subtitle"><?php echo date('F Y', mktime(0, 0, 0, $current_month, 1, $current_year)); ?></h4>
                            <button id="next-month" class="nxt-prev">Next</button>
                        </div>
                        <div class="calendar-grid">
                            <div class="text-sm font-semibold text-gray-600">Sun</div>
                            <div class="text-sm font-semibold text-gray-600">Mon</div>
                            <div class="text-sm font-semibold text-gray-600">Tue</div>
                            <div class="text-sm font-semibold text-gray-600">Wed</div>
                            <div class="text-sm font-semibold text-gray-600">Thu</div>
                            <div class="text-sm font-semibold text-gray-600">Fri</div>
                            <div class="text-sm font-semibold text-gray-600">Sat</div>
                            <?php foreach ($calendar_days as $day) : ?>
                                <div class="calendar-day <?php echo $day && in_array("$current_year-$current_month-" . sprintf('%02d', $day), $entry_dates) ? 'entry' : ''; ?>" data-date="<?php echo $day ? "$current_year-$current_month-" . sprintf('%02d', $day) : ''; ?>">
                                    <?php echo $day ?: ''; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <!-- Timeline Section -->
                    <div class="timeline-container w-full md:w-2/3">
                        <h4 class="start-title mb-2">Your Hair Journey</h4>
                        <div class="timeline-line"></div>
                        <?php
                        $entries->rewind_posts();
                        while ($entries->have_posts()) : $entries->the_post();
                            $thumbnail_url = get_the_post_thumbnail_url(get_the_ID(), 'thumbnail');
                            $entry_date = get_the_date('Y-m-d');
                            ?>
                            <div class="timeline-entry" data-entry-date="<?php echo esc_attr($entry_date); ?>">
                                <div class="flex items-start space-x-3">
                                    <?php if ($thumbnail_url && filter_var($thumbnail_url, FILTER_VALIDATE_URL)) : ?>
                                        <img src="<?php echo esc_url($thumbnail_url); ?>" alt="<?php echo esc_attr(get_the_title()); ?>" class="w-16 h-16 object-cover rounded-md">
                                    <?php else : ?>
                                        <div class="w-16 h-16 bg-gray-200 rounded-md flex items-center justify-center text-gray-500 text-sm">No Image</div>
                                    <?php endif; ?>
                                    <div>
                                        <h4 class="font-semibold text-gray-700"><?php echo esc_html(get_the_title()); ?></h4>
                                        <p class="text-sm text-gray-500"><?php echo esc_html(get_the_date('F j, Y')); ?></p>
                                        <p class="text-sm text-gray-600"><?php echo esc_html(wp_trim_words(get_the_content(), 20)); ?></p>
                                        <a href="<?php echo esc_url(get_permalink()); ?>" class="text-sm hover:underline">View Details</a>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
                <!-- Original TimelineJS -->
                <div class="timelinejs-container bg-white rounded-lg shadow p-4">
                    <h3 class="text-lg font-bold mb-4 text-gray-800">Classic Timeline View</h3>
                    <div id="<?php echo esc_attr($div_id); ?>" style="width: 100%; height: 600px;"></div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.knightlab.com/libs/timeline3/latest/js/timeline-min.js"></script>
    <script>
        jQuery(document).ready(function($) {
            // Calendar date filtering
            $('.calendar-day').on('click', function() {
                if (!$(this).text()) return; // Skip empty days
                $('.calendar-day').removeClass('selected');
                $(this).addClass('selected');
                const selectedDate = $(this).data('date');
                $('.timeline-entry').each(function() {
                    if (selectedDate === $(this).data('entry-date')) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
                // Show all entries if no date is selected or reset
                if (!selectedDate) {
                    $('.timeline-entry').show();
                }
            });

            // Prev/Next month buttons
            $('#prev-month, #next-month').on('click', function() {
                console.log($(this).text() + ' month clicked');
                // Future: Implement AJAX to load new month data
            });

            // TimelineJS initialization
            const timelineDiv = $('#<?php echo esc_attr($div_id); ?>');
            <?php if (!empty($events)) : ?>
                try {
                    const timeline = new TL.Timeline('<?php echo esc_attr($div_id); ?>', <?php echo $timeline_json; ?>, {
                        start_at_end: false, // Start at the beginning to ensure all slides are accessible
                        timenav_position: 'bottom', // Place navigation at bottom for better visibility
                        hash_bookmark: true, // Enable URL hash for slide navigation
                        slide_padding_lr: 100, // Increase padding to prevent cutoff
                        scale_factor: 1, // Adjust scale to fit all slides
                        debug: true
                    });

                    // Ensure last slide is reachable
                    timeline.on('change', function(data) {
                        const totalSlides = timeline._getSlideCount();
                        if (data.current_slide >= totalSlides) {
                            timeline.goTo(0); // Loop back to start
                        } else if (data.current_slide < 0) {
                            timeline.goTo(totalSlides - 1); // Loop to end
                        }
                    });

                    // Force re-render to fix slide skipping
                    $(window).on('resize', function() {
                        timeline.updateDisplay();
                    });

                    const checkTimeline = setInterval(() => {
                        if (timelineDiv.height() > 0 && timeline._loaded) {
                            clearInterval(checkTimeline);
                            $('.timelinejs-container').fadeIn(300);
                            timeline.updateDisplay(); // Refresh display to ensure all slides load
                        }
                    }, 100);
                    setTimeout(() => {
                        clearInterval(checkTimeline);
                    }, 5000); // Increased timeout for slower connections
                } catch (e) {
                    console.error('TimelineJS Error:', e);
                }
            <?php endif; ?>
        });
    </script>
    <?php
    return ob_get_clean();
}
?>