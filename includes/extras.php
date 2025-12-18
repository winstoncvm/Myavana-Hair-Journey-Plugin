<?php
class Myavana_Extras {
    public function __construct() {
        add_action('wp_ajax_check_first_login', [$this, 'check_first_login']);
    }

    public function check_first_login() {
        check_ajax_referer('myavana_nonce', 'nonce');
        $user_id = get_current_user_id();
        $is_first_login = get_user_meta($user_id, 'myavana_first_login', true) !== 'no';
        if ($is_first_login) {
            update_user_meta($user_id, 'myavana_first_login', 'no');
        }
        wp_send_json_success(['is_first_login' => $is_first_login]);
    }
}
?>