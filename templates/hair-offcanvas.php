<?php
/**
 * Hair Journey Diary Offcanvas WordPress Shortcode
 */

// Register the shortcode
add_shortcode('hair_diary_offcanvas', 'hair_diary_offcanvas_shortcode');

function hair_diary_offcanvas_shortcode() {
    // Check if user is logged in
    if (!is_user_logged_in()) {
        return '<p>Please log in to access your hair diary.</p>';
    }

    // Enqueue scripts and styles
    wp_enqueue_script('hair-diary-offcanvas-js', plugin_dir_url(__FILE__) . '../assets/js/offcanvas.js', ['jquery'], '1.0.0', true);
    wp_enqueue_style('hair-diary-offcanvas-css', plugin_dir_url(__FILE__) . '../assets/css/offcanvas.css', [], '1.0.0');
    
    // Localize script for AJAX
    wp_localize_script('hair-diary-offcanvas-js', 'hair_diary_offcanvas_ajax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('myavana_entry'),
        'user_id' => get_current_user_id()
    ]);

    ob_start();
    ?>
   
    <div class="offcanvas-container">
        <button class="offcanvas-toggle-btn" onclick="toggleOffcanvas()">📝 Add Hair Diary Entry</button>
        
        <div class="offcanvas" id="hairDiaryOffcanvas">
            <div class="offcanvas-header">
                <h2>✨ New Hair Journey Entry</h2>
                <button class="offcanvas-close-btn" onclick="toggleOffcanvas()">×</button>
            </div>
            
            <div class="offcanvas-body">
                <form id="myavana-entry-form" method="post" enctype="multipart/form-data">
                    <div class="coolinput">
                        <label for="title" class="text">Entry Title:</label>
                        <input type="text" id="title" name="title" placeholder="e.g., New Haircut" required class="input">
                    </div>
                    
                    <div class="coolinput">
                        <label for="description" class="text">Description:</label>
                        <textarea id="description" name="description" placeholder="Describe your hair journey moment" rows="5" class="input"></textarea>
                    </div>
                    
                    <div class="coolinput">
                        <label for="products" class="text">Products Used:</label>
                        <input type="text" id="products" name="products" placeholder="e.g., Moisturizing Shampoo" class="input">
                    </div>
                    
                    <div class="coolinput">
                        <label for="notes" class="text">Stylist Notes:</label>
                        <textarea id="notes" name="notes" placeholder="Notes from your stylist" rows="5" class="input"></textarea>
                    </div>
                    
                    <div class="coolinput">
                        <label for="rating" class="text">Hair Health Rating (1-5):</label>
                        <input type="number" pattern="\d+" id="rating" name="rating" min="1" max="5" value="3" required class="input">
                    </div>
                    
                    <div class="div-center">
                        <label for="filepond-container">Photo</label>
                        <div id="filepond-container" class="filepond-container"></div>
                        <input type="file" id="photo" name="photo" accept="image/*" style="display: none;">
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
                    <button type="submit" class="save-btn">Add Entry</button>
                </form>
            </div>
        </div>
    </div>

    
    <?php
    return ob_get_clean();
}
?>