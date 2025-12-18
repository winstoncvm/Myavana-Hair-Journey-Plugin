<?php
function myavana_entry_shortcode() {
    if (!is_user_logged_in()) {
        return '<p>Please <a href="' . esc_url(wp_login_url(get_permalink())) . '">Log in</a> to add an entry.</p>';
    }

    $user_id = get_current_user_id();
    ob_start();
?>
    <link rel="stylesheet" href="https://unpkg.com/filepond@4.30.4/dist/filepond.min.css">
    <link rel="stylesheet" href="https://unpkg.com/filepond-plugin-image-preview@4.6.11/dist/filepond-plugin-image-preview.min.css">
    <style>
        .myavana-entry-form {
            margin: 0;
            padding: 0;
            background: var(--background-color);
            border-radius: 8px;
            font-family: 'Avenir Next', sans-serif;
        }
        .myavana-entry-form label {
            display: block;
            margin: 12px 0 5px;
            font-weight: bold;
            color: var(--text-color);
        }
        .myavana-entry-form input[type="text"],
        .myavana-entry-form input[type="number"],
        .myavana-entry-form textarea {
            width: 100%;
            padding: 12px;
            margin-bottom: 6px;
            border: none;
            border-radius: 4px;
            font-family: 'Avenir Next', sans-serif;
            transition: all;
            background-color: var(--input-background-color);
            color: var(--text-color);
        }
        .myavana-entry-form input:focus,
        .myavana-entry-form textarea:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 2px rgba(231, 183, 144, 0.3);
        }
        .myavana-entry-form button {
            margin-top: 10px;
            padding: 12px 24px;
            background: var(--primary-color);
            color: var(--background-color);
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-family: 'Avenir Next', sans-serif;
            font-weight: normal;
            transition: all;
        }
        .myavana-entry-form button:hover {
            background: var(--secondary-color);
            color: var(--text-color);
            transform: translateY(-2px);
        }
        .filepond--root {
            font-family: 'Avenir Next', sans-serif;
            margin-bottom: 24px;
        }
        .filepond--panel-root {
            border: none;
            border-radius: 4px;
            background-color: var(--input-background-color);
        }
        .filepond--drop-label {
            color: var(--text-color);
        }
        .filepond--label-action {
            color: var(--primary-color);
            text-decoration: underline;
            cursor: pointer;
        }
        .error-message, .success-message, .myavana-ai-tip {
            padding: 12px;
            margin: 12px 0;
            border-radius: 4px;
            font-family: 'Avenir Next', sans-serif;
            background: var(--secondary-color);
            color: var(--text-color);
            border-left: 4px solid var(--primary-color);
        }
        .div-center {
            text-align: center;
        }
        .entry-file-upload {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .file-upload-icon svg {
            width: 48px;
            height: 48px;
            fill: var(--primary-color);
        }
    </style>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://unpkg.com/filepond@4.30.4/dist/filepond.min.js"></script>
    <script src="https://unpkg.com/filepond-plugin-image-preview@4.6.11/dist/filepond-plugin-image-preview.min.js"></script>
    <script src="https://unpkg.com/filepond-plugin-image-validate-size@1.2.6/dist/filepond-plugin-image-validate-size.min.js"></script>
    
    <div class="myvana-page-container">
        <div class="myavana-entry-form">
            <h2 class="myavana-title mb-3">Add Hair Journey Entry</h2>
            <div id="error-message" class="error-message mb-3" style="display: none;"></div>
            <div id="success-message" class="success-message mb-3" style="display: none;"></div>
            
            <form id="myavana-entry-form" method="post" enctype="multipart/form-data">
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
                <button type="submit">Add Entry</button>
            </form>
        </div>
    </div>

    <script>
        jQuery(document).ready(function($) {
            FilePond.registerPlugin(
                FilePondPluginImagePreview,
                FilePondPluginImageValidateSize
            );

            const pond = FilePond.create(document.querySelector('#filepond-container'), {
                name: 'photo',
                allowMultiple: false,
                maxFiles: 1,
                acceptedFileTypes: ['image/*'],
                maxFileSize: '30MB',
                labelIdle: `
                <div class="div-center entry-file-upload">
                    <div class="file-upload-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="" viewBox="0 0 24 24"><g stroke-width="0" id="SVGRepo_bgCarrier"></g><g stroke-linejoin="round" stroke-linecap="round" id="SVGRepo_tracerCarrier"></g><g id="SVGRepo_iconCarrier"> <path fill="" d="M10 1C9.73478 1 9.48043 1.10536 9.29289 1.29289L3.29289 7.29289C3.10536 7.48043 3 7.73478 3 8V20C3 21.6569 4.34315 23 6 23H7C7.55228 23 8 22.5523 8 22C8 21.4477 7.55228 21 7 21H6C5.44772 21 5 20.5523 5 20V9H10C10.5523 9 11 8.55228 11 8V3H18C18.5523 3 19 3.44772 19 4V9C19 9.55228 19.4477 10 20 10C20.5523 10 21 9.55228 21 9V4C21 2.34315 19.6569 1 18 1H10ZM9 7H6.41421L9 4.41421V7ZM14 15.5C14 14.1193 15.1193 13 16.5 13C17.8807 13 19 14.1193 19 15.5V16V17H20C21.1046 17 22 17.8954 22 19C22 20.1046 21.1046 21 20 21H13C11.8954 21 11 20.1046 11 19C11 17.8954 11.8954 17 13 17H14V16V15.5ZM16.5 11C14.142 11 12.2076 12.8136 12.0156 15.122C10.2825 15.5606 9 17.1305 9 19C9 21.2091 10.7909 23 13 23H20C22.2091 23 24 21.2091 24 19C24 17.1305 22.7175 15.5606 20.9844 15.122C20.7924 12.8136 18.858 11 16.5 11Z" clip-rule="evenodd" fill-rule="evenodd"></path> </g></svg>
                    </div>
                    <div class="text">
                        <span>Drag & Drop your photo or <span class="filepond--label-action">Browse</span></span>
                    </div>
                </div>
                `,
                storeAsFile: true
            });

            function showError(message) {
                $('#error-message').text(message).show();
                $('#success-message').hide();
            }  

            function showSuccess(message) {
                $('#success-message').text(message).show();
                $('#error-message').hide();
                setTimeout(() => $('#success-message').hide(), 3000);
            }

            $('#myavana-entry-form').submit(function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                formData.append('action', 'myavana_add_entry');
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            showSuccess(response.data.message);
                            $('#myavana-entry-form')[0].reset();
                            pond.removeFiles();
                            $('.myavana-ai-tip').html(response.data.tip).fadeIn(300);
                            setTimeout(() => $('.myavana-ai-tip').fadeOut(300), 5000);
                        } else {
                            showError(response.data);
                        }
                    },
                    error: function() {
                        showError('An error occurred. Please try again.');
                    }
                });
            });
        });
    </script>

<?php
    return ob_get_clean();
}

// New AJAX handler for both manual and automated entries
// function myavana_add_entry() {
//     check_ajax_referer('myavana_entry', 'myavana_nonce');
//     global $wpdb;
//     $user_id = get_current_user_id();
//     if (!$user_id) {
//         wp_send_json_error('User not logged in');
//         return;
//     }

//     $is_automated = isset($_POST['is_automated']) && $_POST['is_automated'] == '1';
//     $timestamp = current_time('mysql');

//     // Prepare entry data
//     if ($is_automated) {
//         $analysis = isset($_POST['analysis']) ? json_decode(stripslashes($_POST['analysis']), true) : null;
//         if (!$analysis) {
//             wp_send_json_error('Invalid analysis data for automated entry');
//             return;
//         }
//         $title = sanitize_text_field('Automated Hair Journey Entry - ' . $timestamp);
//         $description = sanitize_textarea_field($analysis['summary'] ?? 'Automated entry from chatbot analysis.');
//         $products = sanitize_text_field(implode(', ', $analysis['products'] ?? []));
//         $notes = sanitize_text_field($analysis['recommendations'] ? implode("\n", $analysis['recommendations']) : '');
//         $rating = min(max(intval($analysis['hair_analysis']['health_score'] ?? 5) / 20, 1), 5);
//         $session_id = sanitize_text_field($_POST['session_id'] ?? '');
//         $tags = myavana_generate_ai_tags($analysis);
//         $metadata = [
//             'analysis_data' => wp_json_encode($analysis),
//             'environment' => sanitize_text_field($analysis['environment'] ?? ''),
//             'mood_demeanor' => sanitize_text_field($analysis['mood_demeanor'] ?? '')
//         ];
//     } else {
//         // Manual form submission
//         if (empty($_POST['title']) || empty($_POST['rating'])) {
//             wp_send_json_error('Title and rating are required');
//             return;
//         }
//         $title = sanitize_text_field($_POST['title']);
//         $description = sanitize_textarea_field($_POST['description']);
//         $products = sanitize_text_field($_POST['products']);
//         $notes = sanitize_text_field($_POST['notes']);
//         $rating = min(max(intval($_POST['rating']), 1), 5);
//         $session_id = '';
//         $tags = [];
//         $metadata = [];
//     }

//     // Insert post
//     $post_id = wp_insert_post([
//         'post_title' => $title,
//         'post_content' => $description,
//         'post_type' => 'hair_journey_entry',
//         'post_status' => 'publish',
//         'post_author' => $user_id
//     ]);

//     if ($post_id && !is_wp_error($post_id)) {
//         // Save metadata
//         update_post_meta($post_id, 'products_used', $products);
//         update_post_meta($post_id, 'stylist_notes', $notes);
//         update_post_meta($post_id, 'health_rating', $rating);
//         if ($is_automated) {
//             update_post_meta($post_id, 'ai_tags', $tags);
//             update_post_meta($post_id, 'session_id', $session_id);
//             foreach ($metadata as $key => $value) {
//                 update_post_meta($post_id, $key, $value);
//             }
//         }

//         // Handle photo upload
//         $attachment_id = 0;
//         if ($is_automated && !empty($_POST['image_data'])) {
//             $attachment_id = myavana_save_screenshot($_POST['image_data'], $post_id, $user_id);
//         } elseif (!empty($_FILES['photo']['name'])) {
//             require_once ABSPATH . 'wp-admin/includes/media.php';
//             require_once ABSPATH . 'wp-admin/includes/file.php';
//             require_once ABSPATH . 'wp-admin/includes/image.php';

//             $upload = wp_handle_upload($_FILES['photo'], ['test_form' => false]);
//             if ($upload && !isset($upload['error'])) {
//                 $attachment = [
//                     'post_mime_type' => $upload['type'],
//                     'post_title' => sanitize_file_name(basename($upload['file'])),
//                     'post_content' => '',
//                     'post_status' => 'inherit',
//                     'post_author' => $user_id
//                 ];
//                 $attachment_id = wp_insert_attachment($attachment, $upload['file'], $post_id);
//                 if (!is_wp_error($attachment_id)) {
//                     $attachment_data = wp_generate_attachment_metadata($attachment_id, $upload['file']);
//                     wp_update_attachment_metadata($attachment_id, $attachment_data);
//                 } else {
//                     error_log('Myavana Hair Journey: Attachment error for post ' . $post_id . ': ' . $attachment_id->get_error_message());
//                 }
//             } else {
//                 error_log('Myavana Hair Journey: Upload error for post ' . $post_id . ': ' . ($upload['error'] ?? 'Unknown error'));
//             }
//         }

//         if ($attachment_id && !is_wp_error($attachment_id)) {
//             set_post_thumbnail($post_id, $attachment_id);

//             // Add to BuddyPress activity
//             if (function_exists('bp_is_active') && bp_is_active('activity')) {
//                 $activity_content = sprintf(
//                     esc_html__('Shared a new %s hair journey entry: %s', 'myavana'),
//                     $is_automated ? 'automated' : '',
//                     $title
//                 );
//                 $activity_args = [
//                     'user_id' => $user_id,
//                     'type' => 'activity_update',
//                     'action' => bp_core_get_userlink($user_id) . ' ' . $activity_content,
//                     'content' => $description,
//                     'component' => 'activity',
//                     'item_id' => 0,
//                     'secondary_item_id' => $attachment_id,
//                     'recorded_time' => $timestamp
//                 ];
//                 $activity_id = bp_activity_add($activity_args);
//                 if ($activity_id) {
//                     $media_data = [
//                         'media_id' => $attachment_id,
//                         'type' => 'photo',
//                         'user_id' => $user_id,
//                         'activity_id' => $activity_id,
//                         'uploaded' => $timestamp
//                     ];
//                     bp_activity_update_meta($activity_id, 'yz_media', [$media_data]);
//                 } else {
//                     error_log('Myavana Hair Journey: Failed to create BuddyPress activity for attachment ' . $attachment_id);
//                 }
//             }
//         } elseif ($is_automated) {
//             wp_delete_post($post_id, true);
//             wp_send_json_error('Failed to save photo for automated entry');
//             return;
//         }

//         // Generate AI tip
//         try {
//             $context = sprintf(
//                 'User added a %s hair journey entry with title: %s, health rating: %d.',
//                 $is_automated ? 'automated' : 'manual',
//                 $title,
//                 $rating
//             );
//             $ai = new Myavana_AI();
//             $tip = $ai->get_ai_tip($context);
//             wp_send_json_success([
//                 'message' => 'Entry added successfully!',
//                 'tip' => $tip
//             ]);
//         } catch (Exception $e) {
//             error_log('Myavana Hair Journey: AI Tip error - ' . $e->getMessage());
//             wp_send_json_success([
//                 'message' => 'Entry added successfully!',
//                 'tip' => 'Keep up with your haircare routine!'
//             ]);
//         }

//         // Update hair profile for automated entries
//         if ($is_automated) {
//             myavana_update_hair_profile($user_id, $analysis, $timestamp);
//         }
//     } else {
//         error_log('Myavana Hair Journey: Failed to insert post for user ' . $user_id);
//         wp_send_json_error('Error adding entry. Please try again.');
//     }
// }
// add_action('wp_ajax_myavana_add_entry', 'myavana_add_entry');
?>