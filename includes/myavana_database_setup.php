<?php
// includes/myavana_database_setup.php

function myavana_create_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    // Create wp_myavana_profiles table
    $profiles_table = $wpdb->prefix . 'myavana_profiles';
    $profiles_sql = "CREATE TABLE $profiles_table (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        hair_journey_stage VARCHAR(255) DEFAULT 'Not set',
        hair_health_rating INT DEFAULT 5,
        life_journey_stage VARCHAR(255) DEFAULT 'Not set',
        birthday VARCHAR(255) DEFAULT '',
        location VARCHAR(255) DEFAULT '',
        hair_type VARCHAR(255) DEFAULT '',
        hair_goals LONGTEXT DEFAULT NULL,
        hair_analysis_snapshots LONGTEXT DEFAULT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY user_id (user_id)
    ) $charset_collate;";
    
    // Create wp_myavana_conversations table
    $conversations_table = $wpdb->prefix . 'myavana_conversations';
    $conversations_sql = "CREATE TABLE $conversations_table (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        session_id VARCHAR(36) NOT NULL,
        message_text TEXT NOT NULL,
        message_type ENUM('user', 'agent', 'system', 'error') NOT NULL,
        timestamp DATETIME NOT NULL,
        PRIMARY KEY (id),
        KEY user_id (user_id),
        KEY session_id (session_id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($profiles_sql);
    dbDelta($conversations_sql);
    
    // Add performance indexes (check if they exist first)
    $index_exists = $wpdb->get_var("SHOW INDEX FROM {$conversations_table} WHERE Key_name = 'idx_user_conversations'");
    if (!$index_exists) {
        $wpdb->query("CREATE INDEX idx_user_conversations ON {$conversations_table} (user_id, session_id, timestamp)");
    }
    
    $index_exists = $wpdb->get_var("SHOW INDEX FROM {$conversations_table} WHERE Key_name = 'idx_session_timestamp'");
    if (!$index_exists) {
        $wpdb->query("CREATE INDEX idx_session_timestamp ON {$conversations_table} (session_id, timestamp)");
    }
    
    $index_exists = $wpdb->get_var("SHOW INDEX FROM {$profiles_table} WHERE Key_name = 'idx_user_hair_type'");
    if (!$index_exists) {
        $wpdb->query("CREATE INDEX idx_user_hair_type ON {$profiles_table} (user_id, hair_type)");
    }

    // Add hair_analysis_snapshots column if it doesn't exist
    $column_exists = $wpdb->get_var("SHOW COLUMNS FROM $profiles_table LIKE 'hair_analysis_snapshots'");
    if (!$column_exists) {
        $wpdb->query("ALTER TABLE $profiles_table ADD hair_analysis_snapshots LONGTEXT DEFAULT NULL");
    }

    // Add hair_goals column if it doesn't exist (for redundancy, in case it was missed)
    $goals_column_exists = $wpdb->get_var("SHOW COLUMNS FROM $profiles_table LIKE 'hair_goals'");
    if (!$goals_column_exists) {
        $wpdb->query("ALTER TABLE $profiles_table ADD hair_goals LONGTEXT DEFAULT NULL");
    }
}
add_action('init', 'myavana_create_tables');

function myavana_register_hair_entry_meta() {
    register_post_meta('hair_journey_entry', 'mood', [
        'type' => 'string',
        'single' => true,
        'show_in_rest' => true,
    ]);
    register_post_meta('hair_journey_entry', 'products_used', [
        'type' => 'array',
        'single' => true,
        'show_in_rest' => true,
    ]);
    register_post_meta('hair_journey_entry', 'ai_analysis', [
        'type' => 'string',
        'single' => true,
        'show_in_rest' => true,
    ]);
}
add_action('init', 'myavana_register_hair_entry_meta');

function myavana_migrate_hair_goals() {
    global $wpdb;
    $users = get_users();
    $table_name = $wpdb->prefix . 'myavana_profiles';

    foreach ($users as $user) {
        $user_id = $user->ID;
        $hair_goals = get_user_meta($user_id, 'myavana_hair_goals_structured', true);
        if ($hair_goals && is_array($hair_goals)) {
            $wpdb->update(
                $table_name,
                ['hair_goals' => wp_json_encode($hair_goals)],
                ['user_id' => $user_id],
                ['%s'],
                ['%d']
            );
            // Optionally, delete the old user meta to avoid duplication
            // delete_user_meta($user_id, 'myavana_hair_goals_structured');
        }
    }
}
// Run this once, e.g., via a temporary admin page or WP-CLI
// add_action('init', 'myavana_migrate_hair_goals');
?>