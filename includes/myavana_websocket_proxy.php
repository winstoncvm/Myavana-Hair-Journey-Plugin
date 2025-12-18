<?php
function myavana_handle_websocket_proxy() {
    check_ajax_referer('myavana_chatbot_nonce', 'nonce');

    $openai_api_key = get_option('myavana_openai_api_key');
    if (!$openai_api_key) {
        wp_send_json_error(['message' => 'OpenAI API key not configured']);
        return;
    }

    wp_send_json_success(['websocket_url' => 'wss://your-server.com/websocket-proxy']);
}
add_action('wp_ajax_myavana_websocket_proxy', 'myavana_handle_websocket_proxy');
add_action('wp_ajax_nopriv_myavana_websocket_proxy', 'myavana_handle_websocket_proxy');
?>