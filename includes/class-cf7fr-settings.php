<?php

if (!defined('ABSPATH'))
    exit;

class CF7FR_Settings
{

    public static function init()
    {
        add_action('admin_menu', [__CLASS__, 'register_settings_page']);
    }

    public static function register_settings_page()
    {
        add_submenu_page(
            'cf7fr-reviews',
            'CF7 Reviews Settings',
            'Settings',
            'manage_options',
            'cf7fr-settings',
            [__CLASS__, 'render_settings']
        );
    }

    public static function render_settings()
    {
        echo '<div class="wrap"><h1>Settings</h1>';
        echo '<p>Settings page coming soon.</p>';
        echo '</div>';
    }
}