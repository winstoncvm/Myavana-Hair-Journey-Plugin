<?php
class Myavana_Timeline {
    public function timeline_shortcode() {
        if (!is_user_logged_in()) {
            return '<p>Please <a href="' . home_url('/login') . '">log in</a> to view your timeline.</p>';
        }
        $user_id = get_current_user_id();
        $args = [
            'post_type' => 'myavana_journey',
            'author' => $user_id,
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'ASC'
        ];
        $entries = new WP_Query($args);
        ob_start();
        ?>
        <div class="myavana-timeline">
            <h2>Your Hair Journey Timeline</h2>
            <div class="timeline-container">
                <?php while ($entries->have_posts()) : $entries->the_post(); ?>
                    <div class="timeline-entry">
                        <div class="timeline-date"><?php the_date(); ?></div>
                        <div class="timeline-content">
                            <h3><?php the_title(); ?></h3>
                            <p><?php the_content(); ?></p>
                            <?php if (has_post_thumbnail()) : ?>
                                <img src="<?php the_post_thumbnail_url('medium'); ?>" alt="Entry Image">
                            <?php endif; ?>
                            <p><strong>Hairstyle:</strong> <?php echo esc_html(get_post_meta(get_the_ID(), 'hairstyle', true)); ?></p>
                            <p><strong>Challenges:</strong> <?php echo esc_html(get_post_meta(get_the_ID(), 'challenges', true)); ?></p>
                            <p><strong>Goals:</strong> <?php echo esc_html(get_post_meta(get_the_ID(), 'goals', true)); ?></p>
                        </div>
                    </div>
                <?php endwhile; wp_reset_postdata(); ?>
            </div>
            <form method="post" enctype="multipart/form-data">
                <h3>Add New Entry</h3>
                <input type="text" name="title" placeholder="Entry Title" required>
                <textarea name="description" placeholder="Description" required></textarea>
                <input type="text" name="hairstyle" placeholder="Hairstyle (e.g., Wash and Go)">
                <input type="text" name="challenges" placeholder="Challenges (e.g., Dryness)">
                <input type="text" name="goals" placeholder="Goals (e.g., Length Retention)">
                <input type="file" name="media" accept="image/*,application/pdf">
                <?php wp_nonce_field('myavana_entry', 'myavana_nonce'); ?>
                <button type="submit" name="myavana_entry">Add Entry</button>
            </form>
        </div>
        <style>
            .myavana-timeline { max-width: 800px; margin: 0 auto; padding: 20px; }
            .timeline-container { position: relative; }
            .timeline-entry { margin: 20px 0; padding: 20px; background: #f9f9f9; border-radius: 10px; }
            .timeline-date { font-weight: bold; color: #4CAF50; }
            .timeline-content img { max-width: 100%; height: auto; }
            .myavana-timeline form { margin-top: 20px; }
            .myavana-timeline input, .myavana-timeline textarea { width: 100%; padding: 10px; margin: 10px 0; }
            .myavana-timeline button { background: #4CAF50; color: white; padding: 10px; }
        </style>
        <?php
        if (isset($_POST['myavana_entry']) && wp_verify_nonce($_POST['myavana_nonce'], 'myavana_entry')) {
            $post_id = wp_insert_post([
                'post_title' => sanitize_text_field($_POST['title']),
                'post_content' => sanitize_textarea_field($_POST['description']),
                'post_type' => 'myavana_journey',
                'post_status' => 'publish',
                'post_author' => $user_id
            ]);
            if ($post_id) {
                update_post_meta($post_id, 'hairstyle', sanitize_text_field($_POST['hairstyle']));
                update_post_meta($post_id, 'challenges', sanitize_text_field($_POST['challenges']));
                update_post_meta($post_id, 'goals', sanitize_text_field($_POST['goals']));
                if (!empty($_FILES['media']['name'])) {
                    $attachment_id = media_handle_upload('media', $post_id);
                    if (!is_wp_error($attachment_id)) {
                        set_post_thumbnail($post_id, $attachment_id);
                    }
                }
                echo '<p style="color: green;">Entry added successfully!</p>';
            }
        }
        return ob_get_clean();
    }
}
?>