<?php
/**
 * Luxury List View - Hair Journey Timeline
 * Displays goals, routines, and entries in a beautiful, filterable list
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
    <div id="listView" class="view-content list-view-hjn active">
        <div class="list-header-hjn">
            <h2 class="list-title-hjn">Journey List</h2>
            <p class="list-description-hjn">Please sign in to see your goals, routines and entries.</p>
        </div>
    </div>
<?php
    return;
endif;

// Fetch user data
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

// Calculate totals for filter counts
$total_goals = count($hair_goals);
$total_routines = count($current_routine);
$total_entries = count($entries);
$total_all = $total_goals + $total_routines + $total_entries;
?>

<!-- Luxury List View -->
<div id="listView" class="view-content list-view-hjn active">
    <!-- Header Section -->
    <div class="list-header-hjn">
        <div class="list-header-content-hjn">
            <div class="list-title-section-hjn">
                <h2 class="list-title-hjn">Journey List</h2>
                <p class="list-description-hjn">All your goals, entries, and routines in one comprehensive view</p>
            </div>

            <!-- Filter & Sort Controls -->
        </div>
    </div>

    <!-- List Container -->
    <div class="list-container-hjn" id="listContainer">
        <div class="list-grid-hjn-profile" id="listGrid">
            <!-- ENTRIES -->
            <?php if ( ! empty( $entries ) ) : ?>
                <?php foreach ( $entries as $entry ) :
                    $post_id = $entry->ID;
                    $entry_title = get_the_title($post_id);
                    $entry_date = get_the_date('', $post_id);
                    $entry_date_formatted = get_the_date('F j, Y', $post_id);
                    
                    // Try to get post thumbnail first
                    $thumbnail = get_the_post_thumbnail_url($post_id, 'medium');
                    
                    // If no thumbnail, check for featured_image_index meta and gallery
                    if (empty($thumbnail)) {
                        $featured_image_index = get_post_meta($post_id, 'featured_image_index', true);
                        $gallery_images = get_post_meta($post_id, '_entry_gallery', true);
                        
                        // If we have a featured_image_index and gallery images exist
                        if ($featured_image_index !== '' && !empty($gallery_images) && is_array($gallery_images)) {
                            // Ensure the index is valid
                            $index = intval($featured_image_index);
                            if (isset($gallery_images[$index])) {
                                $thumbnail = wp_get_attachment_image_url($gallery_images[$index], 'medium');
                            }
                        }
                        
                        // If still no thumbnail, try the first gallery image
                        if (empty($thumbnail) && !empty($gallery_images) && is_array($gallery_images)) {
                            $thumbnail = wp_get_attachment_image_url($gallery_images[0], 'medium');
                        }
                    }
                    
                    $content = wp_strip_all_tags($entry->post_content);
                    $excerpt = wp_trim_words($content, 20);

                    // Get entry metadata
                    $rating = get_post_meta($post_id, 'health_rating', true);
                    $mood = get_post_meta($post_id, 'mood_demeanor', true);
                    $products = get_post_meta($post_id, 'products_used', true);

                    // Sort date
                    $sort_date = strtotime($entry->post_date);
                ?>
                <div class="list-item-hjn list-item-entry-hjn"
                    data-type="entries"
                    data-title="<?php echo esc_attr(strtolower($entry_title)); ?>"
                    data-date="<?php echo esc_attr($sort_date); ?>"
                    data-entry-id="<?php echo esc_attr($post_id); ?>">

                    <?php if ($thumbnail): ?>
                    <div class="list-item-thumbnail-hjn">
                        <img src="<?php echo esc_url($thumbnail); ?>" alt="<?php echo esc_attr($entry_title); ?>" loading="lazy" />
                        <?php if ($rating): ?>
                        <div class="thumbnail-rating-hjn">
                            <svg viewBox="0 0 24 24" width="14" height="14">
                                <path fill="currentColor" d="M12,17.27L18.18,21L16.54,13.97L22,9.24L14.81,8.62L12,2L9.19,8.62L2,9.24L7.45,13.97L5.82,21L12,17.27Z"/>
                            </svg>
                            <?php echo esc_html($rating); ?>/10
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <div class="list-item-icon-hjn list-icon-entry-hjn">
                        <svg viewBox="0 0 24 24" width="24" height="24">
                            <path fill="currentColor" d="M4,4H7L9,2H15L17,4H20A2,2 0 0,1 22,6V18A2,2 0 0,1 20,20H4A2,2 0 0,1 2,18V6A2,2 0 0,1 4,4M12,7A5,5 0 0,0 7,12A5,5 0 0,0 12,17A5,5 0 0,0 17,12A5,5 0 0,0 12,7M12,9A3,3 0 0,1 15,12A3,3 0 0,1 12,15A3,3 0 0,1 9,12A3,3 0 0,1 12,9Z"/>
                        </svg>
                    </div>
                    <?php endif; ?>

                    <div class="list-item-content-hjn">
                        <div class="list-item-header-hjn">
                            <h3 class="list-item-title-hjn"><?php echo esc_html($entry_title); ?></h3>
                        </div>

                        <div class="list-item-date-hjn">
                            <svg viewBox="0 0 24 24" width="14" height="14">
                                <path fill="currentColor" d="M12,20A8,8 0 0,0 20,12A8,8 0 0,0 12,4A8,8 0 0,0 4,12A8,8 0 0,0 12,20M12,2A10,10 0 0,1 22,12A10,10 0 0,1 12,22C6.47,22 2,17.5 2,12A10,10 0 0,1 12,2M12.5,7V12.25L17,14.92L16.25,16.15L11,13V7H12.5Z"/>
                            </svg>
                            <?php echo esc_html($entry_date_formatted); ?>
                        </div>

                        <p class="list-item-description-hjn"><?php echo esc_html($excerpt); ?></p>

                        <div class="list-item-meta-hjn">
                            <span class="meta-tag-hjn tag-type-hjn">Entry</span>
                            <?php if ($mood): ?>
                            <span class="meta-tag-hjn"><?php echo esc_html($mood); ?></span>
                            <?php endif; ?>
                            <?php if ($products): ?>
                            <span class="meta-tag-hjn">
                                <?php
                                $product_count = count(array_filter(explode(',', $products)));
                                echo $product_count . ' Product' . ($product_count !== 1 ? 's' : '');
                                ?>
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <button class="list-item-action-hjn"
                            data-action="view-entry"
                            data-entry-id="<?php echo esc_attr($post_id); ?>"
                            onclick="openViewOffcanvas('entry', <?php echo esc_js($post_id); ?>)">
                        View Details
                    </button>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>


        </div>

        <!-- Empty State -->
        <div class="list-empty-state-hjn" id="listEmptyState" style="display: none;">
            <svg viewBox="0 0 24 24" width="64" height="64">
                <path fill="currentColor" d="M9.5,3A6.5,6.5 0 0,1 16,9.5C16,11.11 15.41,12.59 14.44,13.73L14.71,14H15.5L20.5,19L19,20.5L14,15.5V14.71L13.73,14.44C12.59,15.41 11.11,16 9.5,16A6.5,6.5 0 0,1 3,9.5A6.5,6.5 0 0,1 9.5,3M9.5,5C7,5 5,7 5,9.5C5,12 7,14 9.5,14C12,14 14,12 14,9.5C14,7 12,5 9.5,5Z"/>
            </svg>
            <h3 class="empty-title-hjn">No Results Found</h3>
            <p class="empty-description-hjn">Try adjusting your filters or search terms</p>
        </div>

        <!-- No Data State -->
        <?php if (empty($hair_goals) && empty($entries) && empty($current_routine)): ?>
        <div class="list-empty-state-hjn">
            <svg viewBox="0 0 24 24" width="64" height="64">
                <path fill="currentColor" d="M19,13H13V19H11V13H5V11H11V5H13V11H19V13Z"/>
            </svg>
            <h3 class="empty-title-hjn">Start Your Journey</h3>
            <p class="empty-description-hjn">Add your first goal, entry, or routine to begin tracking your hair care journey</p>
            <button class="empty-action-btn-hjn" onclick="openOffcanvas('entry')">
                Add Your First Entry
            </button>
        </div>
        <?php endif; ?>
    </div>
</div>
