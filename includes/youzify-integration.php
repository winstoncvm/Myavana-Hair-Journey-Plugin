<?php
class Myavana_Youzify {
    public function register_youzify_tab() {
        if (!function_exists('youzify_add_profile_tab')) {
            return;
        }

        youzify_add_profile_tab([
            'slug' => 'hair-journey',
            'name' => __('Hair Journey', 'myavana'),
            'icon' => 'dashicons-heart',
            'position' => 10,
            'content_callback' => [$this, 'render_hair_journey_tab']
        ]);
    }

    public function render_hair_journey_tab() {
        ob_start();
        ?>
        <div class="myavana-hair-journey-tab">
            <h2>Your Hair Journey</h2>
            <?php echo do_shortcode('[myavana_timeline]'); ?>
            <h3>Add a New Entry</h3>
            <?php echo do_shortcode('[myavana_entry]'); ?>
        </div>
        <?php
        return ob_get_clean();
    }
}
?>