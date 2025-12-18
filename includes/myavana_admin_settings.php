<?php
function myavana_add_admin_menu() {
    add_menu_page(
        'Myavana Settings',
        'Myavana',
        'manage_options',
        'myavana-settings',
        'myavana_settings_page',
        'dashicons-admin-generic',
        80
    );
}
add_action('admin_menu', 'myavana_add_admin_menu');

function myavana_settings_init() {
    register_setting('myavanaSettings', 'myavana_xai_api_key', [
        'sanitize_callback' => 'sanitize_text_field'
    ]);
    register_setting('myavanaSettings', 'myavana_openai_api_key', [
        'sanitize_callback' => 'sanitize_text_field'
    ]);
    register_setting('myavanaSettings', 'myavana_gemini_api_key', [
        'sanitize_callback' => 'sanitize_text_field'
    ]);

    add_settings_section(
        'myavana_api_section',
        'API Keys',
        null,
        'myavana-settings'
    );

    add_settings_field(
        'myavana_xai_api_key',
        'xAI API Key',
        'myavana_xai_api_key_render',
        'myavana-settings',
        'myavana_api_section'
    );

    add_settings_field(
        'myavana_openai_api_key',
        'OpenAI API Key',
        'myavana_openai_api_key_render',
        'myavana-settings',
        'myavana_api_section'
    );
    add_settings_field(
        'myavana_gemini_api_key',
        'Gemini API Key',
        'myavana_gemini_api_key_render',
        'myavana-settings',
        'myavana_api_section'
    );
}
add_action('admin_init', 'myavana_settings_init');

function myavana_xai_api_key_render() {
    $value = get_option('myavana_xai_api_key', '');
    echo '<input type="password" name="myavana_xai_api_key" value="' . esc_attr($value) . '" size="50">';
}

function myavana_openai_api_key_render() {
    $value = get_option('myavana_openai_api_key', '');
    echo '<input type="password" name="myavana_openai_api_key" value="' . esc_attr($value) . '" size="50">';
}

function myavana_gemini_api_key_render() {
    $value = get_option('myavana_gemini_api_key', '');
    echo '<input type="password" name="myavana_gemini_api_key" value="' . esc_attr($value) . '" size="50">';
}

function myavana_settings_page() {
    ?>
    <div class="wrap">
        <h1>Myavana Settings</h1>
        <form action="options.php" method="post">
            <?php
            settings_fields('myavanaSettings');
            do_settings_sections('myavana-settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}
?>