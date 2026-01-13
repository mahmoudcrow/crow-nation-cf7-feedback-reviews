<?php

if (!defined('ABSPATH'))
    exit;

class CF7FR_Settings
{

    public static function init()
    {
        add_action('admin_menu', [__CLASS__, 'register_settings_page']);
    }
    public static function render_settings()
    {
        echo '<div class="wrap"><h1>Settings</h1>';
        echo '<p>Settings page coming soon.</p>';
        echo '</div>';
    }
}