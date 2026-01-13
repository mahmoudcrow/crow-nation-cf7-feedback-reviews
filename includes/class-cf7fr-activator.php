<?php

if (!defined('ABSPATH')) {
    exit;
}

class CF7FR_Activator
{

    public static function activate()
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'cf7fr_messages';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            fields_json LONGTEXT NOT NULL,
            remote_ip VARCHAR(100) DEFAULT '' NOT NULL,
            user_agent TEXT DEFAULT '' NOT NULL,
            url TEXT DEFAULT '' NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIME,
            uuid VARCHAR(36) DEFAULT '' NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

}